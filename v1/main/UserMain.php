<?php

namespace main;

use model\{
    GameModel, LeaderboardModel, RedirectModel, UserModel
};
use service\HubException;
use service\JwtHelper;

/**
 * Class UserMain
 *
 * @package Main
 */
class UserMain extends MainMain
{
    public $runtimeVariable;

    /**
     * @return UserModel
     */
    private function userModel(): UserModel
    {
        return $this->container->get('userModel');
    }

    /**
     * Return game model from container
     *
     * @return GameModel
     */
    private function gameModel(): GameModel
    {
        return $this->container->get('gameModel');
    }

    /**
     * @param string $username
     * @param string $state
     * @param string $userId
     * @return string
     */
    public function createUserLbId(string $username, string $state, string $userId): string
    {
        $userLbId = $username . '.' . $state . '.' . $userId;
        return $userLbId;
    }

    /**
     * @return LeaderboardModel
     */
    private function leaderboardModel(): LeaderboardModel
    {
        return $this->container->get('leaderboardModel');
    }

    private function token(): JwtHelper
    {
        return $this->container->get('token');
    }

    /**
     * @return RedirectModel
     */
    private function redirectModel(): RedirectModel
    {
        return $this->container->get('redirectModel');
    }

    public function userBan($type, $userId, $gameId, $analyticId, bool $permanently = true, int $expireTime = 0)
    {
        switch ($type) {
            case 'increaseBanCount':
                $this->userModel()->increaseTimeBan($userId, $gameId);
                break;
            case 'userBan':
                $this->userModel()->userBan($userId, $gameId, $permanently, $expireTime);
                break;
        }
        $this->userModel()->addAnalyticLog($userId, $type, $analyticId, $gameId);
    }

    /**
     * just for show report and save to log file - log path: /log/usercount
     */
    public function userCount()
    {
        $detail = $this->userModel()->userCount();

        $this->io->setResponse($detail);
    }

    public function myGameParameters()
    {
        $this->runtimeVariable['userId'] = (string)$this->request->uid;
        if (!$this->runtimeVariable['userId']) {
            throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
        }
    }

    public function findMyGame()
    {
        $this->runtimeVariable['myGame'] = $this->userModel()->findUserById($this->runtimeVariable['userId'])->game;
    }

    public function findImageUrl()
    {
        $games = $this->gameModel()->findAllGames();
        foreach ($games as $key => $value) {
            $imageUrl[$value->_id] = $value->resource_link->my;
            if ($value->competition->activate == true) {
                $competitionGame = $value->_id;
            }
        }

        # set to variable
        $this->runtimeVariable['imageUrl'] = $imageUrl;
        $this->runtimeVariable['competition_game'] = $competitionGame;
    }

    public function createMyGameResult()
    {
        $result['games'] = $this->runtimeVariable['myGame'];
        $result['image_url'] = $this->runtimeVariable['imageUrl'];
        $result['competition_game'] = $this->runtimeVariable['competition_game'];

        $this->io->setResponse($result);
    }

    public function checkDuplicateUser()
    {
        $usernameExist = $this->userModel()->checkUsernameDuplicate(
            $this->runtimeVariable['userId'],
            $this->runtimeVariable['newUserName']
        );

        if ($usernameExist) {
            throw new HubException(HubException::UD_MESSAGE, HubException::UD_CODE);
        }
    }

    public function updateProfile()
    {
        $confirm = $this->userModel()->updateProfile(
            $this->runtimeVariable['userId'],
            $this->runtimeVariable['newUserName'],
            $this->runtimeVariable['firstName'],
            $this->runtimeVariable['lastName'] ?? '',
        );

        if (!$confirm->getMatchedCount()) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::UP_CODE);
        }
    }

    public function findUser()
    {
        $userDocument = $this->userModel()->findUserById($this->runtimeVariable['userId']);
        if (!$userDocument) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::FUBI_CODE);
        }

        $this->runtimeVariable['oldUserName'] = $userDocument->profile->username;
        $this->runtimeVariable['oldState'] = $userDocument->profile->state ?? '0';
        $this->runtimeVariable['userGames'] = $userDocument->game;
    }

    /**
     * @throws HubException
     */
    public function updates()
    {
        if (($this->runtimeVariable['oldUserName'] != $this->runtimeVariable['newUserName']) ||
            ($this->runtimeVariable['oldState'] != $this->runtimeVariable['newState'])
        ) {
            # 1.update redis (update just key(uLbId))
            $newUserLbId = $this->updateRedis();

            # 2.update token because uLbId changed
            $this->updateToken($newUserLbId);

            # 3.update redirect urls in mongo database because token changed
            $this->updateRedirectUrls();
        }
    }

    public function createSetProfileResult()
    {
        # get new token (maybe token changed)
        $newToken = $this->token()->getToken();

        $this->io->setResponse($newToken);
    }

    /**
     * @return string
     */
    private function updateRedis(): string
    {
        # 1.create new uLbId(user leaderboard id)
        $newUserLbId = $this->createUserLbId(
            $this->runtimeVariable['newUserName'],
            $this->runtimeVariable['newState'],
            $this->runtimeVariable['userId']
        );

        # 2.create old uLbId(user leaderboard id)
        $oldUserLbId = $this->createUserLbId(
            $this->runtimeVariable['oldUserName'],
            $this->runtimeVariable['oldState'],
            $this->runtimeVariable['userId']
        );

        # 3.update redis for normal games
        $this->onNormalGames($oldUserLbId, $newUserLbId);

        # 4.update redis for competition game
        $this->onCompetitionGame($oldUserLbId, $newUserLbId);

        return $newUserLbId;
    }

    /**
     * @param string $newUserLbId
     */
    private function updateToken(string $newUserLbId)
    {
        $this->token()->updateClaim('ulbid', $newUserLbId);

        $this->token()->create();
    }

    private function updateRedirectUrls()
    {
        $newToken = $this->token()->getToken();
        $url['h'] = $this->setting['baseUrl']['redirect']['hub'] . $newToken;
        $url['m'] = $this->setting['baseUrl']['redirect']['moreGames'] . $newToken;
        $url['l'] = $this->setting['baseUrl']['redirect']['leaderboard'] . $newToken;

        $this->redirectModel()->updateUrl($this->runtimeVariable['userId'], $url);
    }

    /**
     * @param string $oldUserLbId
     * @param string $newUserLbId
     */
    private function onNormalGames(string $oldUserLbId, string $newUserLbId)
    {
        foreach ($this->runtimeVariable['userGames'] as $gameId => $value) {
            $score = (int)$value->score->normal;
            $this->leaderboardModel()->deleteUser($gameId, $oldUserLbId);
            $this->leaderboardModel()->upsertUserScore($gameId, $newUserLbId, $score);
        }
    }

    /**
     * @param string $oldUserLbId
     * @param string $newUserLbId
     */
    private function onCompetitionGame(string $oldUserLbId, string $newUserLbId)
    {
        $competitionGameDocument = $this->gameModel()->findCompetitionGame();
        $competitionGameId = $competitionGameDocument->_id;
        $competitionScore = (int)$this->runtimeVariable['userGames']->{$competitionGameId}->score->competition;
        if ($competitionScore) {
            $competitionLbGameId = 'competition_' . $competitionGameId;
            $this->leaderboardModel()->deleteUser($competitionLbGameId, $oldUserLbId);
            $this->leaderboardModel()->upsertUserScore($competitionLbGameId, $newUserLbId, $competitionScore);
        }
    }

    public function getProfileParameters()
    {
        $this->runtimeVariable['userId'] = $this->request->uid;
        if (!$this->runtimeVariable['userId']) {
            throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
        }
    }

    public function findUserProfile()
    {
        $this->runtimeVariable['userProfile'] = $this->userModel()->findUserById($this->runtimeVariable['userId'])
            ->profile;
        if (!$this->runtimeVariable['userProfile']) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::FUBI_CODE);
        }
    }

    public function createGetProfileResult()
    {
        $result = [
            'username' => $this->runtimeVariable['userProfile']->username,
            'first_name' => $this->runtimeVariable['userProfile']->first_name,
            'last_name' => $this->runtimeVariable['userProfile']->last_name,
            'state' => $this->runtimeVariable['userProfile']->state,
            'email' => $this->runtimeVariable['userProfile']->email,
            'gender' => $this->runtimeVariable['userProfile']->gender,
        ];

        $this->io->setResponse($result);
    }
}
