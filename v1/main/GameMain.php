<?php

namespace main;

use model\{
    GameModel, LeaderboardModel, UserModel
};
use service\HubException;
use service\JwtHelper;

class GameMain extends MainMain
{
    const USER_LIFE = 10000;
    const GAME_GEM = 10;
    const EXPIRE_TIME = 86400;
    const DATA_PARTS = 5;
    const USER_LIVES = 50;

    public $logObject;

    private $runtimeVariable;

    public function competitionFinishTime()
    {
        $finishTime = $this->gameModel()->findCompetitionGame()->competition->finish_time;
        $this->runtimeVariable['finishTime'] = $finishTime - time();
    }

    /**
     * @return GameModel
     */
    private function gameModel(): GameModel
    {
        return $this->container->get('gameModel');
    }

    private function logMain(): LogMain
    {
        return $this->container->get('logMain');
    }

    /**
     * @return UserModel
     */
    private function userModel(): UserModel
    {
        return $this->container->get('userModel');
    }

    private function verifyDataHash()
    {
        $scoreServerHash = hash_hmac('sha256', $this->runtimeVariable['data'], $this->setting['privateKey']);
        if ($this->runtimeVariable['dataHash'] !== $scoreServerHash) {
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'start:controllers:verify_data_hash_pass',
                false
            );

            throw new HubException(HubException::EMPTY_MESSAGE, HubException::SUCCESS_CODE);
        }
    }

    private function userMain(): UserMain
    {
        return $this->container->get('userMain');
    }

    private function verifyDataCount()
    {
        $splitData = explode('.', $this->runtimeVariable['data']);
        if (count($splitData) != self::DATA_PARTS) {
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'start:controllers:verify_data_count_pass',
                false
            );
            throw new HubException(HubException::EMPTY_MESSAGE, HubException::SUCCESS_CODE);
        }
    }

    private function updateStartDetails()
    {
        if ($this->runtimeVariable['isCompetitionGame']) {
            $this->userModel()->updateCompetitionDetails(
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId']
            );
        } else {
            $this->userModel()->updateNormalDetails(
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId']
            );
        }
    }

    /**
     * created by h.soltani
     */
    private function verifyUserLife()
    {
        $userLives = $this->userModel()->getUserAssetsById($this->runtimeVariable['userId'])->life;
        if ($userLives == self::USER_LIVES) {
            $this->userModel()->updateLastLifeRecharge($this->runtimeVariable['userId']);
        } else {
            $this->userMain()->rechargeUserLife($this->runtimeVariable['userId']);
        }
    }

    public function finishListParameters()
    {
        $this->runtimeVariable['gameId'] = $this->request->game_id;
        if (!$this->runtimeVariable['gameId']) {
            throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
        }
    }

    /**
     * @throws HubException
     */
    public function findAllGames()
    {
        $this->runtimeVariable['allGames'] = $this->gameModel()->findAllGames();
        if (!$this->runtimeVariable['allGames']) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::FAG_CODE);
        }
    }

    public function createGameListResult()
    {
        foreach ($this->runtimeVariable['allGames'] as $key => $gameDocument) {
            $result[$key]['id'] = $gameDocument->_id;
            $result[$key]['name'] = $gameDocument->name;
            $result[$key]['competition'] = $gameDocument->competition->activate;
            $result[$key]['counter'] = $gameDocument->counter;
        }

        $this->io->setResponse($result);
    }

    public function removeSelectedGame()
    {
        $i = 0;
        foreach ($this->runtimeVariable['allGames'] as $key => $value) {
            if ($this->runtimeVariable['gameId'] != $value->_id) {
                $data[$i][][] = $value->resource_link->more;
                $data[$i][][] = $value->url;
                $data[$i][][] = $value->_id;
                $i++;
            }
        }
        $this->runtimeVariable['finishGames'] = $data;
    }

    public function createFinishListResult()
    {
        $result = [
            "c2array" => true,
            "size" => [
                count($this->runtimeVariable['finishGames']),
                4,
                1
            ],
            "data" => $this->runtimeVariable['finishGames']
        ];

        $this->io->setPlainResponse($result);
    }

    public function playGameParameters()
    {
        $this->runtimeVariable['gameId'] = $this->request->game_id;
        if (!$this->runtimeVariable['gameId']) {
            throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
        }
    }

    public function findGame()
    {
        $this->runtimeVariable['gameDocument'] = $this->gameModel()->findGameById($this->runtimeVariable['gameId']);
    }

    public function redirectToGame()
    {
        # game exist
        if ($this->runtimeVariable['gameDocument']) {
            $this->token()->addClaim('gid', $this->runtimeVariable['gameId']);
            $token = $this->token()->create();
            $url = $this->runtimeVariable['gameDocument']->url;
            header("Location: " . $url . '?token=' . $token . '&game_id=' . $this->runtimeVariable['gameId']);
        } else { # game don't exist
            $url = 'http://telegram.me/' . $this->setting['bot']['name'];
            header("Location: " . $url);
        }            # user score
            $userScore = $this->leaderboardModel()->findUserScore(
                $this->runtimeVariable['gameLbId'],
                $this->runtimeVariable['userLbId']
            );

            # other
            $this->runtimeVariable['otherStartRank'] = $userRank - self::LB_OTHER_COUNT;
            $other = $this->leaderboardModel()->findRange(
                $this->runtimeVariable['gameLbId'],
                $userRank - self::LB_OTHER_COUNT,
                $userRank + self::LB_OTHER_COUNT
            );
    }

    /**
     * @return JwtHelper
     */
    private function token(): JwtHelper
    {
        return $this->container->get('token');
    }

    public function competitionParameters()
    {
        if ($this->io->getUserType() != 'guest') {
            $this->runtimeVariable['userLbId'] = $this->request->ulbid;
            if (!$this->runtimeVariable['userLbId']) {
                throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
            }
        }
    }

    public function findCompetitionGame()
    {
        # find from db
        $competitionGameDocument = $this->gameModel()->findCompetitionGame();
        if (!$competitionGameDocument) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::CG_CODE);
        }

        # set variables
        $this->runtimeVariable['gameId'] = $competitionGameDocument->_id;
        $this->runtimeVariable['lbGameId'] = 'competition_' . $this->runtimeVariable['gameId'];
        $this->runtimeVariable['gameUrl'] = $competitionGameDocument->competition->url;
    }

    public function userScoreAndRank()
    {
        if ($this->io->getUserType() != 'guest') {
            $this->runtimeVariable['userRank'] = $this->leaderboardModel()->findUserRank(
                $this->runtimeVariable['lbGameId'],
                $this->runtimeVariable['userLbId']
            );
            $this->runtimeVariable['userScore'] = $this->leaderboardModel()->findUserScore(
                $this->runtimeVariable['lbGameId'],
                $this->runtimeVariable['userLbId']
            );
        }
    }

    /**
     * @return LeaderboardModel
     */
    private function leaderboardModel(): LeaderboardModel
    {
        return $this->container->get('leaderboardModel');
    }

    public function firstScore()
    {
        $this->runtimeVariable['firstScore'] = $this->leaderboardModel()->findRange(
            $this->runtimeVariable['lbGameId'],
            0,
            0
        )[1];
    }

    public function createCompetitionResult()
    {
        $result[] = [
            'game_id' => $this->runtimeVariable['gameId'],
            'competition_url' => $this->runtimeVariable['gameUrl'],
            'finish_time' => $this->runtimeVariable['finishTime'],
            'user_rank' => $this->runtimeVariable['userRank'] ?? null,
        ];

        $this->io->setResponse($result);
    }

    public function menuParameters()
    {
        $this->runtimeVariable['gameId'] = $this->request->game_id;
        $this->runtimeVariable['userId'] = $this->request->uid;

        if (!$this->runtimeVariable['gameId'] || !$this->runtimeVariable['userId']) {
            throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
        }
    }

    public function userScoreAndType()
    {
        # game is competition or not? ---> we need it for user score
        $this->runtimeVariable['isGameActivate'] = $this->gameModel()->findGameById($this->runtimeVariable['gameId'])
            ->competition->activate;

        # find user document
        $userDocument = $this->userModel()->findUserById($this->runtimeVariable['userId']);

        # get user type
        $this->runtimeVariable['userType'] = $userDocument->type;

        # get user score
        $userScore = $userDocument->game->{$this->runtimeVariable['gameId']}->score;
        $this->runtimeVariable['userScore'] =
            ($this->runtimeVariable['isGameActivate']) ? $userScore->competition : $userScore->normal;
    }

    public function userAssets()
    {
        $assets = $this->userMain()->calculateAssets();
        $this->runtimeVariable['userLife'] = $assets['life'];
        $this->runtimeVariable['userCoin'] = $assets['coin'];
        $this->runtimeVariable['userGem'] = $assets['gem'];
        $this->runtimeVariable['userLifeCounter'] = ($assets['lifeCoutner'] < 0) ? 0 : $assets['lifeCoutner'];
    }

    public function competitionTitle()
    {
        $this->runtimeVariable['competitionTitle'] = 'تا شروع مسابقه';
    }

    public function createMenuResult()
    {
        $result = [
            "c2array" => true,
            "size" => [1, 2, 5],
            "data" => [
                [
                    [
                        $this->runtimeVariable['finishTime'],
                        (int)$this->runtimeVariable['userScore'],
                        $this->runtimeVariable['competitionTitle'],
                        (string)$this->runtimeVariable['isGameActivate'],
                        $this->runtimeVariable['userType']
                    ],
                ],
            ]
        ];

        $this->io->setPlainResponse($result);
    }

    public function likeParameters()
    {
        $this->runtimeVariable['userId'] = $this->request->uid;
        $this->runtimeVariable['gameId'] = $this->request->game_id;
        $this->runtimeVariable['like'] = $this->request->like;

        # check exist
        if (!$this->runtimeVariable['userId'] || !$this->runtimeVariable['gameId'] || !$this->runtimeVariable['like']) {
            throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
        }
    }

    public function updateUserLike()
    {
        $state = ($this->runtimeVariable['like'] == 'true') ? true : false;

        $this->userModel()->updateGameLikeStatus(
            $this->runtimeVariable['userId'],
            $this->runtimeVariable['gameId'],
            $state
        );
    }

    public function shareParameters()
    {
        $this->runtimeVariable['gameId'] = $this->request->game_id;
        if (!$this->runtimeVariable['gameId']) {
            throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
        }
    }

    public function redirectToTelegram()
    {
        header("Location: https://telegram.me/" . $this->setting['bot']['name'] .
            "?game=" . $this->runtimeVariable['gameId']);
        exit;
    }

    private function setLogType()
    {
        $this->logMain()->setLogType('game-start');
    }

    private function startParameters()
    {
        $this->runtimeVariable['userId'] = $this->request->uid;
        $this->runtimeVariable['gameId'] = $this->request->game_id;
        $this->runtimeVariable['data'] = $this->request->b;
        $this->runtimeVariable['dataHash'] = $this->request->e;
        $this->runtimeVariable['gameRoundId'] = $this->request->n; # not use

        if (!$this->runtimeVariable['gameId']) {
            $this->logMain()->updateDatabaseProperty($this->logObject, 'start:controllers:check_parameter_pass', false);
            throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
        }
    }

    private function logging()
    {
        $userAdditionalInfo = $this->userModel()->findUserById($this->runtimeVariable['userId']);
        $this->logMain()->updateProperty('user_id', $this->runtimeVariable['userId']);
        $this->logMain()->updateProperty('username', $userAdditionalInfo->profile->username);
    }

    public function verifyClientData()
    {
        # 1.set log type as game-start
        $this->setLogType();

        # 2.check start parameters
        $this->startParameters();

        # 3.logging process
        $this->logging();

        # 4.verify hash
        $this->verifyDataHash();

        # 5.verify data count
        $this->verifyDataCount();
    }

    private function isCompetitionGame()
    {
        $this->runtimeVariable['isCompetitionGame'] = $this->gameModel()->findGameById($this->runtimeVariable['gameId'])
            ->competition->activate;
    }

    private function increaseGameCounter()
    {
        $this->gameModel()->increaseGameCounter($this->runtimeVariable['gameId']);
    }

    public function processAfterVerify()
    {
        # 1.game is competition or not
        $this->isCompetitionGame();

        # 2.check user life status
        $this->verifyUserLife();

        # 3.increase game counter
        $this->increaseGameCounter();

        # 4.update start details to database
        $this->updateStartDetails();
    }
}
