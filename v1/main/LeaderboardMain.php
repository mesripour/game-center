<?php

namespace main;

use model\GameModel;
use model\LeaderboardModel;
use model\UserModel;
use service\HubException;

class LeaderboardMain extends MainMain
{
    const ROUND_TRIP_TIME = 10;
    const LB_TOP_COUNT = 9;
    const LB_OTHER_COUNT = 3;
    const HALF_HOUR = 1800;

    public $logObject;
    private $runtimeVariable;


    private function logMain(): LogMain
    {
        return $this->container->get('logMain');
    }

    private function userMain(): UserMain
    {
        return $this->container->get('userMain');
    }

    /**
     * @param $userId
     * @return array|null|object
     * @throws HubException
     */
    private function getUserDocument($userId)
    {
        $userDocument = $this->userModel()->findUserById($userId);
        if (!$userDocument) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::FUBI_CODE);
        }

        return $userDocument;
    }

    private function userModel(): UserModel
    {
        return $this->container->get('userModel');
    }

    /**
     * @param $gameId
     * @return array|null|object
     * @throws HubException
     */
    private function getGameDocument($gameId)
    {
        $gameDocument = $this->gameModel()->findGameById($gameId);
        if (!$gameDocument) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::FGBI_CODE);
        }
        return $gameDocument;
    }

    private function gameModel(): GameModel
    {
        return $this->container->get('gameModel');
    }

    /**
     * @return array
     */
    public function removeRedisKeys()
    {
        $games = $this->userModel()->mongo('game')->find();
        $this->leaderboardModel()->redis()->del([$games->_id]);
    }


    /**
 * @return true
 */
    public function removeRedisNormalKeys()
    {
        $games = $this->userModel()->mongo('game')->find();
        $this->leaderboardModel()->redis()->del([$games->_id]);
    }

    /**
     * @return array
     */
    public function removeRedisCompetitionKeys()
    {
        $games = $this->userModel()->mongo('game')->find();
        $this->leaderboardModel()->redis()->del(['competition_'.$games->_id]);
    }

    private function getCleanScore()
    {
        $this->runtimeVariable['cleanScore'] = base64_decode($this->runtimeVariable['base64Score']);
    }

    private function getCleanData()
    {
        $data = $this->runtimeVariable['base64data'];

        $data = explode('.', $data);
        $cleanData[] = base64_decode($data[0]);
        $cleanData[] = base64_decode($data[1]);
        $cleanData[] = base64_decode($data[2]);
        $cleanData[] = base64_decode($data[3]);

        $this->runtimeVariable['cleanData'] = $cleanData;
    }

    private function verifyUserLife()
    {
        if (!$this->runtimeVariable['isCompetitionGame']) {
            $userLife = $this->runtimeVariable['userDocument']->asset->life;
            if ($userLife == 0) {
                $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:play_count_pass', false);
                throw new HubException(HubException::EMPTY_MESSAGE, HubException::SUCCESS_CODE);
            } else {
                $this->userModel()->decrementUserLife($this->runtimeVariable['userId']);
            }
        }
    }

    private function serverPlayTime()
    {
        $currentTime = round(microtime(true), 3);

        if ($this->runtimeVariable['isCompetitionGame']) {
            $playTime = $currentTime - $this->runtimeVariable['userDocument']->time->last_play_time->competition;
        } else {
            $playTime = $currentTime - $this->runtimeVariable['userDocument']->time->last_play_time->normal;
        }

        $this->runtimeVariable['serverPlayTime'] = round($playTime, 3);
    }

    private function formula()
    {
        # set first score
        $this->firstScore();

        # set user's time ban count
        $this->userTimeBanCount();

        switch ($this->runtimeVariable['gameId']) {
            case 'river':
            case 'competition_river':
                $this->riverFormula();
                break;
            case 'wood':
            case 'competition_wood':
                $this->woodFormula();
                break;
            case 'wave':
            case 'competition_wave':
                $this->waveFormula();
                break;
            case 'commando':
            case 'competition_commando':
                $this->commandoFormula();
                break;
            case 'dodge':
            case 'competition_dodge':
                $this->dodgeFormula();
                break;
            case 'castle':
            case 'competition_castle':
                $this->castleFormula();
                break;
            break;
            case 'pong':
            case 'competition_pong':
                $this->pongFormula();
                break;
        }
    }

    /**
     * Play
     * @return LeaderboardModel play
     */
    private function leaderboardModel(): LeaderboardModel
    {
        return $this->container->get('leaderboardModel');
    }

    private function riverFormula()
    {
        $data = $this->runtimeVariable['cleanData'];
        $bridge = $data[1];
        $copter = $data[2];
        $ship = $data[4];
        $jet = $data[8];
        $pet = $data[9];

        # validate game play data
        $this->verifyGamePlayDataRiver($bridge, $copter, $ship, $jet, $pet);

        # validate time
        $this->verifyTimeRiver($bridge);

        # validate illogical score
        $this->verifyIllogicalScoreRiver();
    }

    /**
     * @param $bridge
     * @param $copter
     * @param $ship
     * @param $jet
     * @param $pet
     * @throws HubException
     */
    private function verifyGamePlayDataRiver($bridge, $copter, $ship, $jet, $pet)
    {
        # log
        $logArray = [
            'bridge' => $bridge,
            'copter' => $copter,
            'ship' => $ship,
            'jet' => $jet,
            'pet' => $pet
        ];
        $this->logMain()->updateDatabaseProperty(
            $this->logObject,
            'finish:formula:verify_game_play_data:data',
            $logArray
        );

        # verify
        if ($result != 1) {
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:verify_game_play_data:pass',
                false
            );
            throw new HubException(HubException::EMPTY_MESSAGE, HubException::SUCCESS_CODE);
        }
    }

    /**
     * @param $bridge
     */
    private function verifyTimeRiver($bridge)
    {
        $logArray = [
            'play_time' => $this->runtimeVariable['serverPlayTime'],
            'bridge' => $bridge,
        ];
        $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:formula:verify_time:data', $logArray);
        $time = (40 + $this->runtimeVariable['serverPlayTime']) / ($bridge + 2);
    }

    private function verifyIllogicalScoreRiver()
    {
        # log
        $logArray = [
            'score' => $this->runtimeVariable['cleanScore'],
            'top_score' => $this->runtimeVariable['firstScore']
        ];
        $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:formula:illogical_score:data', $logArray);

        # 1.ban user (critical)
        $this->banUserRiver();

        # 2.no save score (warning)
        $this->noSaveScoreRiver();
    }

    private function banUserRiver()
    {
        if ($this->runtimeVariable['cleanScore'] > 300000) {
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:illogical_score:pass:ban',
                false
            );
            throw new HubException(HubException::UB_MESSAGE, HubException::UB_CODE);
        }
    }

    private function noSaveScoreRiver()
    {
        $firstScore = $this->runtimeVariable['firstScore'];

        $x = (($firstScore * 50) / 100);
        if (($firstScore > 15000) && (($firstScore + $x) < $this->runtimeVariable['cleanScore'])) {
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:illogical_score:pass:no_save',
                false
            );
            throw new HubException(HubException::UNS_MESSAGE, HubException::UNS_CODE);
        }
    }

    private function woodFormula()
    {
        $data = $this->runtimeVariable['cleanData'];
        $s = $data[0];
        $wood = $data[1];
        $rabbit = $data[3];
        $tap = $data[5];

        # verify game play data
        $this->verifyGamePlayDataWood($rabbit, $wood, $tap);

        # verify time
        $this->verifyTime(
            $this->runtimeVariable['serverPlayTime'],
            $this->runtimeVariable['userId'],
            $this->runtimeVariable['gameId'],
            $s,
            $this->runtimeVariable['timeBanCount']
        );

        # validate illogical score
        $this->verifyIllogicalScoreWood();
    }

    /**
     * @param $rabbit
     * @param $wood
     * @param $tap
     * @throws HubException
     */
    private function verifyGamePlayDataWood($rabbit, $wood, $tap)
    {
        $logArray = [
            'rabbit' => $rabbit,
            'wood' => $wood,
            'tap' => $tap
        ];
        $this->logMain()->updateDatabaseProperty(
            $this->logObject,
            'finish:formula:verify_game_play_data:data',
            $logArray
        );
        $tap = ($tap == 0) ? 1 : $tap;
        $result = ($rabbit + $wood) / $tap;

        if (($result < 1) || ($result > 2)) {
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:verify_game_play_data:pass',
                false
            );
            throw new HubException(HubException::UB_MESSAGE, HubException::UB_CODE);
        }
    }

    /**
     * @param int $serverPlayTime
     * @param string $userId
     * @param string $gameId
     * @param $clientPlayTime
     * @param $timeBanCount
     */
    private function verifyTime(int $serverPlayTime, string $userId, string $gameId, $clientPlayTime, $timeBanCount)
    {
        $roundTripTime = $serverPlayTime - $clientPlayTime;

        $logArray = [
            'server_play_time' => $serverPlayTime,
            'client_play_time' => $clientPlayTime
        ];

        $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:formula:verify_time:data', $logArray);
    }

    private function verifyIllogicalScoreWood()
    {
        # 1.ban user (critical)
        $this->banUserWood();

        # 2.no save score (warning)
        $this->noSaveScoreWood();
    }

    private function banUserWood()
    {
        if ($this->runtimeVariable['cleanScore'] > 300) {
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:illogical_score:pass:ban',
                false
            );
            throw new HubException(HubException::UB_MESSAGE, HubException::UB_CODE);
        }
    }

    private function noSaveScoreWood()
    {
        $firstScore = $this->runtimeVariable['firstScore'];
        $x = (($firstScore * 50) / 100);
        if (($firstScore > 70) && (($firstScore + $x) < $this->runtimeVariable['cleanScore'])) {
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:illogical_score:pass:no_save',
                false
            );
            throw new HubException(HubException::UNS_MESSAGE, HubException::UNS_CODE);
        }
    }

    private function waveFormula()
    {
        $data = $this->runtimeVariable['cleanData'];
        $speed = $data[2];

        # verify game play data
        $this->verifyGamePlayDataWave($speed);

        # verify time
        $this->verifyTimeWave();

        # validate illogical score
        $this->verifyIllogicalScoreWave();
    }

    private function commandoFormula()
    {
        $data = $this->runtimeVariable['cleanData'];
        $countSpear = $data[1];
        $platform = $data[2];
        $clientTime = $data[3];

        $this->verifyGamePlayDataCommando($countSpear, $platform);

        $this->verifyTime(
            $this->runtimeVariable['serverPlayTime'],
            $this->runtimeVariable['userId'],
            $this->runtimeVariable['gameId'],
            $clientTime,
            $this->runtimeVariable['timeBanCount']
        );

        $this->verifyIllogicalScoreCommando();
    }

    private function verifyIllogicalScoreCommando()
    {
        # log
        $logArray = [
            'score' => $this->runtimeVariable['cleanScore'],
            'top_score' => $this->runtimeVariable['firstScore']
        ];
        $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:formula:illogical_score:data', $logArray);

        # 1.ban user (critical)
        $this->banUserCommando();

        # 2.no save score (warning)
        $this->noSaveScoreCommando();
    }

    private function banUserCommando()
    {
        if ($this->runtimeVariable['cleanScore'] > 400) {
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:illogical_score:pass:ban',
                false
            );
            throw new HubException(HubException::UB_MESSAGE, HubException::UB_CODE);
        }
    }

    private function noSaveScoreCommando()
    {
        $firstScore = $this->runtimeVariable['firstScore'];
        $score = $this->runtimeVariable['cleanScore'];

        $x = (($firstScore * 50) / 100);
        if (($firstScore > 40) && (($firstScore + $x) < $score)) {
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:illogical_score:pass:no_save',
                false
            );
            throw new HubException(HubException::UNS_MESSAGE, HubException::UNS_CODE);
        }
    }

    private function dodgeFormula()
    {
        $data = $this->runtimeVariable['cleanData'];
        $crown = $data[9];
        $gem = $data[8];
        $clientTime = $data[7];

        $this->verifyGamePlayDataDodge($crown, $gem);

        $this->verifyTime(
            $this->runtimeVariable['serverPlayTime'],
            $this->runtimeVariable['userId'],
            $this->runtimeVariable['gameId'],
            $clientTime,
            $this->runtimeVariable['timeBanCount']
        );

        $this->verifyIllogicalScoreDodge();
    }

    private function castleFormula()
    {
        $data = $this->runtimeVariable['cleanData'];
        $crown = $data[2];
        $clientTime = $data[4];

        $this->verifyGamePlayDataCastle($crown, $clientTime);

        $this->verifyTime(
            $this->runtimeVariable['serverPlayTime'],
            $this->runtimeVariable['userId'],
            $this->runtimeVariable['gameId'],
            $clientTime,
            $this->runtimeVariable['timeBanCount']
        );

        $this->verifyIllogicalScoreCastle();
    }


    private function pongFormula()
    {
        $data = $this->runtimeVariable['cleanData'];
        $racket = $data[8];
        $racket2 = $data[9];
        $clientTime = $data[6];

        $this->verifyGamePlayDataPong($racket, $racket2, $clientTime);

        $this->verifyTime(
            $this->runtimeVariable['serverPlayTime'],
            $this->runtimeVariable['userId'],
            $this->runtimeVariable['gameId'],
            $clientTime,
            $this->runtimeVariable['timeBanCount']
        );

        $this->verifyIllogicalScorePong();
    }

    private function verifyIllogicalScoreDodge()
    {
        $firstScore = $this->runtimeVariable['firstScore'];
        if ($this->runtimeVariable['isCompetitionGame']) {
            $firstScore = $this->leaderboardModel()
                    ->findRange('competition_'.$this->runtimeVariable['gameId'], 0, 0)[1] ?? 0;
        }

        # log
        $logArray = [
            'score' => $this->runtimeVariable['cleanScore'],
            'top_score' => $firstScore
        ];
        $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:formula:illogical_score:data', $logArray);

        # 1.ban user (critical)
        $this->banUserDodge();

        # 2.no save score (warning)
        $this->noSaveScoreDodge($firstScore);
    }

    private function verifyIllogicalScoreCastle()
    {
        $firstScore = $this->runtimeVariable['firstScore'];
        if ($this->runtimeVariable['isCompetitionGame']) {
            $firstScore = $this->leaderboardModel()
                    ->findRange('competition_'.$this->runtimeVariable['gameId'], 0, 0)[1] ?? 0;
        }

        # log
        $logArray = [
            'score' => $this->runtimeVariable['cleanScore'],
            'top_score' => $firstScore
        ];
        $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:formula:illogical_score:data', $logArray);

        # 1.ban user (critical)
        $this->banUserCastle();

        # 2.no save score (warning)
        $this->noSaveScoreCastle($firstScore);
    }

    private function verifyIllogicalScorePong()
    {
        $firstScore = $this->runtimeVariable['firstScore'];
        if ($this->runtimeVariable['isCompetitionGame']) {
            $firstScore = $this->leaderboardModel()
                    ->findRange('competition_'.$this->runtimeVariable['gameId'], 0, 0)[1] ?? 0;
        }

        $logArray = [
            'score' => $this->runtimeVariable['cleanScore'],
            'top_score' => $firstScore
        ];
        $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:formula:illogical_score:data', $logArray);
        # 1.ban user (critical)
        $this->banUserPong();

        # 2.no save score (warning)
        $this->noSaveScorePong($firstScore);
    }

    private function banUserDodge()
    {
        if ($this->runtimeVariable['cleanScore'] > 5000) {
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:illogical_score:pass:ban',
                false
            );
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            throw new HubException(HubException::UB_MESSAGE, HubException::UB_CODE);
        }
    }

    private function banUserPong()
    {
        $score = $this->runtimeVariable['cleanScore'];
        $serverPlayTime = $this->runtimeVariable['serverPlayTime'];

        if ($score > 500 || ($score / 4) > $serverPlayTime) {
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:illogical_score:pass:ban',
                false
            );
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            throw new HubException(HubException::UB_MESSAGE, HubException::UB_CODE);
        }
    }


    /**
     * @param int $topScore
     * @throws HubException
     */
    private function noSaveScoreDodge(int $topScore)
    {
        $x = (($topScore * 50) / 100);

        if (($topScore > 120) && (($topScore + $x) < $this->runtimeVariable['cleanScore'])) {
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:illogical_score:pass:no_save',
                false
            );
            throw new HubException(HubException::NSS_MESSAGE, HubException::NSS_CODE);
        }
    }

    private function banUserCastle()
    {
        if ($this->runtimeVariable['cleanScore'] > 2000) {
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:illogical_score:pass:ban',
                false
            );
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            throw new HubException(HubException::UB_MESSAGE, HubException::UB_CODE);
        }
    }

    /**
     * @param int $firstScore
     * @throws HubException
     */
    private function noSaveScoreCastle(int $firstScore)
    {
        $x = (($firstScore * 50) / 100);

        if (($firstScore > 100) && (($firstScore + $x) < $this->runtimeVariable['cleanScore'])) {
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:illogical_score:pass:no_save',
                false
            );
            throw new HubException(HubException::NSS_MESSAGE, HubException::NSS_CODE);
        }
    }

    /**
     * @param int $topScore
     * @throws HubException
     */
    private function noSaveScorePong(int $topScore)
    {
        $x = (($topScore * 50) / 100);

        if (($topScore > 40) && (($topScore + $x) < $this->runtimeVariable['cleanScore'])) {
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:illogical_score:pass:no_save',
                false
            );
            throw new HubException(HubException::NSS_MESSAGE, HubException::NSS_CODE);
        }
    }

    /**
     * @param $speed
     * @throws HubException
     */
    private function verifyGamePlayDataWave($speed)
    {
        $logArray = [
            'speed' => $speed
        ];
        $this->logMain()->updateDatabaseProperty(
            $this->logObject,
            'finish:formula:verify_game_play_data:data',
            $logArray
        );
        # handle division by zero
        $result = ($this->runtimeVariable['cleanScore'] != 0) ? (($speed - 200) /
            ($this->runtimeVariable['cleanScore'] * 8)) : 1;
        $this->logMain()->updateDatabaseProperty(
            $this->logObject,
            'finish:formula:verify_game_play_data:result',
            $result
        );
    }

    /**
     * @param int $countSpear
     * @param int $platform
     * @throws HubException
     */
    private function verifyGamePlayDataCommando(int $countSpear, int $platform)
    {
        # process formula
        $result = (($countSpear + $platform) - 4)== $this->runtimeVariable['cleanScore'] ? 1 : 0;

        if ($result != 1) {
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:verify_game_play_data:pass',
                false
            );
            throw new HubException(HubException::EMPTY_MESSAGE, HubException::SUCCESS_CODE);
        }
    }

    /**
     * @param int $crown
     * @param int $gem
     * @throws HubException
     */
    private function verifyGamePlayDataDodge(int $crown, int $gem)
    {
        # process formula
        $result = $gem + ($crown*10);

        $this->logMain()->updateDatabaseProperty(
            $this->logObject,
            'finish:formula:verify_game_play_data:result',
            $result
        );

        if ($result != $this->runtimeVariable['cleanScore']) {
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:verify_game_play_data:pass',
                false
            );
            throw new HubException(HubException::EMPTY_MESSAGE, HubException::SUCCESS_CODE);
        }
    }

    /**
     * @param int $crown
     * @param int $clientTime
     * @throws HubException
     */
    private function verifyGamePlayDataCastle(int $crown, int $clientTime)
    {
        # handle division by zero
        $result = $clientTime + ($crown*3);

        $this->logMain()->updateDatabaseProperty(
            $this->logObject,
            'finish:formula:verify_game_play_data:result',
            $result
        );

        if ($result != $this->runtimeVariable['cleanScore']) {
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:verify_game_play_data:pass',
                false
            );

            throw new HubException(HubException::EMPTY_MESSAGE, HubException::SUCCESS_CODE);
        }
    }


    /**
     * @param int $racket
     * @param int $racket2
     * @param int $clientTime
     * @throws HubException
     */
    private function verifyGamePlayDataPong(int $racket, int $racket2, int $clientTime)
    {
        if (ceil($result) != $this->runtimeVariable['cleanScore']) {
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:verify_game_play_data:pass',
                false
            );
            throw new HubException(HubException::EMPTY_MESSAGE, HubException::SUCCESS_CODE);
        }
    }

    private function verifyTimeWave()
    {
        $score = $this->runtimeVariable['cleanScore'];
        $playTime = $this->runtimeVariable['serverPlayTime'];
        $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:formula:verify_time:data', $logArray);

        # process formula
        if (($score < ($playTime - self::ROUND_TRIP_TIME)) || ($score > ($playTime + self::ROUND_TRIP_TIME))) {
            $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:formula:verify_time:pass', false);
            $this->hackTimeBehavior(
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->runtimeVariable['timeBanCount']
            );
        }
    }


    /**
     * @param int $score
     * @param int $playTime
     * @param string $userId
     * @param string $gameId
     * @param int $timeBanCount
     */
    private function verifyTimeCommando(int $score, int $playTime, string $userId, string $gameId, int $timeBanCount)
    {
        $logArray = [
            'score' => $score,
            'play_time' => $playTime,
        ];
        $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:formula:verify_time:data', $logArray);
        if (($score < ($playTime - self::ROUND_TRIP_TIME)) || ($score > ($playTime + self::ROUND_TRIP_TIME))) {
            $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:formula:verify_time:pass', false);
            $this->hackTimeBehavior($userId, $gameId, $timeBanCount);
        }
    }

    private function verifyIllogicalScoreWave()
    {
        #log
        $logArray = [
            'score' => $this->runtimeVariable['cleanScore'],
            'top_score' => $this->runtimeVariable['firstScore']
        ];
        $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:formula:illogical_score:data', $logArray);

        # 1.ban user (critical)
        $this->banUserWave();

        # 2.no save score (warning)
        $this->noSaveScoreWave();
    }

    private function banUserWave()
    {
        if ($this->runtimeVariable['cleanScore'] > 200) {
            $this->userMain()->userBan(
                'userBan',
                $this->runtimeVariable['userId'],
                $this->runtimeVariable['gameId'],
                $this->logObject
            );
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:illogical_score:pass:ban',
                false
            );
            throw new HubException(HubException::UB_MESSAGE, HubException::UB_CODE);
        }
    }

    private function noSaveScoreWave()
    {
        $score = $this->runtimeVariable['cleanScore'];
        $firstScore = $this->runtimeVariable['firstScore'];

        $x = (($firstScore * 50) / 100);
        if (($firstScore > 40) && (($firstScore + $x) < $score)) {
            $this->logMain()->updateDatabaseProperty(
                $this->logObject,
                'finish:formula:illogical_score:pass:no_save',
                false
            );
            throw new HubException(HubException::UNS_MESSAGE, HubException::UNS_CODE);
        }
    }

    private function botMain(): BotMain
    {
        return $this->container->get('botMain');
    }

    private function addCoin()
    {
        if (!$this->runtimeVariable['isCompetitionGame']) {
            $this->fixDivision();

            switch ($this->runtimeVariable['gameId']) {
                case "river":
                    $coin = $this->riverCoin();
                    break;
                case "wood":
                    $coin = $this->woodCoin();
                    break;
                case "wave":
                    $coin = $this->waveCoin();
                    break;
                case "commando":
                    $coin = $this->commandoCoin();
                    break;
                case "dodge":
                    $coin = $this->dodgeCoin();
                    break;
                case "castle":
                    $coin = $this->castleCoin();
                    break;

            }

            $this->runtimeVariable['coin'] = $coin ?? 0;
        }
    }

    private function riverCoin()
    {
        $coin = (($this->runtimeVariable['cleanScore'] / 4700) ** 1.8) * 38 *
            (60 / $this->runtimeVariable['serverPlayTime']);
        $coin = ceil($coin);
        $confirm = $this->userModel()->addCoin($this->runtimeVariable['userId'], $coin);
        if (!$confirm->getMatchedCount()) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::UC_CODE);
        }

        return $coin;
    }

    private function woodCoin()
    {
        $coin = (($this->runtimeVariable['cleanScore'] / 103) ** 1.9) * 38 *
            (60 / $this->runtimeVariable['serverPlayTime']);
        $coin = ceil($coin);
        $confirm = $this->userModel()->addCoin($this->runtimeVariable['userId'], $coin);
        if (!$confirm->getMatchedCount()) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::UC_CODE);
        }

        return $coin;
    }

    private function waveCoin()
    {
        $coin = (($this->runtimeVariable['cleanScore'] / 60) ** 2.3) * 38 *
            (60 / $this->runtimeVariable['serverPlayTime']);
        $coin = ceil($coin);
        $confirm = $this->userModel()->addCoin($this->runtimeVariable['userId'], $coin);
        if (!$confirm->getMatchedCount()) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::UC_CODE);
        }

        return $coin;
    }

    private function commandoCoin()
    {
        $coin = (($this->runtimeVariable['cleanScore'] / 120) ** 3) * 38 *
            (60 / $this->runtimeVariable['serverPlayTime']);
        $coin = ceil($coin);
        $confirm = $this->userModel()->addCoin($this->runtimeVariable['userId'], $coin);
        if (!$confirm->getMatchedCount()) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::UC_CODE);
        }

        return $coin;
    }

    private function dodgeCoin()
    {
        $coin = (($this->runtimeVariable['cleanScore'] / 300) ** 2.7) * 38 *
            (60 / $this->runtimeVariable['serverPlayTime']);
        $coin = ceil($coin);
        $confirm = $this->userModel()->addCoin($this->runtimeVariable['userId'], $coin);
        if (!$confirm->getMatchedCount()) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::UC_CODE);
        }

        return $coin;
    }

    private function castleCoin()
    {
        $coin = (($this->runtimeVariable['cleanScore'] / 150) ** 2.7) * 38 *
            (60 / $this->runtimeVariable['serverPlayTime']);
        $coin = ceil($coin);
        $confirm = $this->userModel()->addCoin($this->runtimeVariable['userId'], $coin);
        if (!$confirm->getMatchedCount()) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::UC_CODE);
        }

        return $coin;
    }

    private function bigger()
    {
        # get old score from database
        if ($this->runtimeVariable['isCompetitionGame']) {
            $oldScore = $this->runtimeVariable['userDocument']->game
                    ->{$this->runtimeVariable['gameId']}->score->competition ?? 0;
        }

        # new score must be more than old score and more than 1
        $newScore = $this->runtimeVariable['finalScore'];
        if (($newScore < $oldScore) || ($newScore < 1)) {
            throw new HubException(HubException::EMPTY_MESSAGE, HubException::SUCCESS_CODE);
        }
    }

    private function updateScoreToDb()
    {
        if ($this->runtimeVariable['isCompetitionGame']) { # this is competition game
            $this->competitionGame();
        } elseif (!$this->runtimeVariable['isCompetitionGame']) { # this is normal game
            $this->normalGame();
        }
    }

    private function competitionGame()
    {
        # save to redis
        $this->leaderboardModel()->upsertUserScore(
            $competitionGameId,
            $this->runtimeVariable['userLbId'],
            $this->runtimeVariable['finalScore']
        );
    }

    private function normalGame()
    {
        # save to mongo
        $confirm = $this->userModel()->upsertNormalScore(
            $this->runtimeVariable['gameId'],
            $this->runtimeVariable['gameName'],
            $this->runtimeVariable['userId'],
            $this->runtimeVariable['finalScore']
        );
        if (!$confirm->getMatchedCount()) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::UUS_CODE);
        }

        # save to redis
        # TODO: set error handle by try catch
        $this->leaderboardModel()->upsertUserScore(
            $this->runtimeVariable['gameId'],
            $this->runtimeVariable['userLbId'],
            $this->runtimeVariable['finalScore']
        );
    }

    private function verifyUserType()
    {
        if ($this->io->getUserType() != 'login') {
            $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:verify_user_type_pass', false);
            throw new HubException(HubException::EMPTY_MESSAGE, HubException::SUCCESS_CODE);
        }
    }

    private function verifyUserBan()
    {
        $permanently = $this->runtimeVariable['userDocument']->game
                ->{$this->runtimeVariable['gameId']}->ban->permanently ?? false;
        $expireTime = $this->runtimeVariable['userDocument']->game
                ->{$this->runtimeVariable['gameId']}->ban->expire_time ?? 0;

        if ($permanently) {
            $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:user_ban_pass', false);
            throw new HubException(HubException::UB_MESSAGE, HubException::UB_CODE);
        } elseif ($expireTime > time()) {
            $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:user_ban_pass', false);
            throw new HubException(HubException::UBL_MESSAGE, HubException::UBL_CODE);
        }
    }

    private function hackTimeBehavior($userId, $gameId, $timeBanCount)
    {
        switch ($timeBanCount) {
            case 0:
                $this->userMain()->userBan('increaseBanCount', $userId, $gameId, $this->logObject);
                throw new HubException(HubException::UNS_MESSAGE, HubException::UNS_CODE);
                break;
            case 1:
                $this->userMain()->userBan('increaseBanCount', $userId, $gameId, $this->logObject);
                throw new HubException(HubException::UNS_MESSAGE, HubException::UNS_CODE);
                break;
            case 2:
                $this->userMain()->userBan('increaseBanCount', $userId, $gameId, $this->logObject);
                throw new HubException(HubException::UNS_MESSAGE, HubException::UNS_CODE);
                break;
            case 3:
                $this->userMain()->userBan('increaseBanCount', $userId, $gameId, $this->logObject);
                throw new HubException(HubException::UNS_MESSAGE, HubException::UNS_CODE);
                break;
            case 4:
                $this->userMain()->userBan('increaseBanCount', $userId, $gameId, $this->logObject);
                throw new HubException(HubException::UNS_MESSAGE, HubException::UNS_CODE);
                break;
            case 5:
                $this->userModel()->resetTimeBan($userId, $gameId);
                $this->userMain()->userBan(
                    'userBan',
                    $userId,
                    $gameId,
                    $this->logObject,
                    false,
                    time() + self::HALF_HOUR
                );
                throw new HubException(HubException::UBL_MESSAGE, HubException::UBL_CODE);
                break;
        }


        if ($timeBanCount > 5) {
            $this->userMain()->userBan('userBan', $userId, $gameId, $this->logObject);
        } else {
            $this->userMain()->userBan('increaseBanCount', $userId, $gameId, $this->logObject);
        }
    }

    private function getFinalScore()
    {
        $playCount = $this->runtimeVariable['userDocument']->game->{$this->runtimeVariable['gameId']}->play_count;
        $result = ($this->runtimeVariable['serverPlayTime'] / 1000) + (log($playCount / 100));
        $decimalPart = $result - floor($result);
        $round3Digit = round($decimalPart, 3);
        $finalScore = $this->runtimeVariable['cleanScore'] + $round3Digit;

        $this->runtimeVariable['finalScore'] = $finalScore;
    }

    public function syncLeaderBoard()
    {
        $type = $this->request->type;
        $password = $this->request->password;
        if(isset($type) && $password == "w!n!2096"):
            //Get All Games User
            $users = $this->userModel()->mongo('user')->find(['type' => 'login', 'game' => ['$exists' => true]]);

            switch ($type):
                case "normal":
                    $this->removeRedisNormalKeys();
                    $this->createNormalKey($users);
                    break;
                case "competition":
                    $competition = $this->removeRedisCompetitionKeys();
                    $this->createCompititionKey($competition);
                    break;
                case "all":
                    $competition = $this->removeRedisKeys();
                    $this->createCompititionKey($competition);
                    $this->createNormalKey($users);
                    break;
            endswitch;
        else:
            return "error";
        endif;

    }

    /**
     * return string
     */
    public function report()
    {
        # TODO: for change
        $document = 'castle';
        $tbl_name = 'report_'.$document;
        $redisUsers = $this->leaderboardModel()->getAllKV('competition_'.$document);
        $this->userModel()->report($result,$tbl_name);
    }



    public function topRank()
    {
        $this->runtimeVariable['topRank'] = $this->leaderboardModel()->findRange(
            $this->runtimeVariable['gameLbId'],
            0,
            self::LB_TOP_COUNT
        );
    }

    public function userRank()
    {
        if ($this->io->getUserType() != 'guest') {
            # user rank
            $userRank = $this->leaderboardModel()->findUserRank(
                $this->runtimeVariable['gameLbId'],
                $this->runtimeVariable['userLbId']
            );

            $this->runtimeVariable['userScore'] = $userScore;
            $this->runtimeVariable['userRank'] = $userRank;
            $this->runtimeVariable['other'] = $other;
        }
    }

    public function createDefaultResult()
    {
        $result = [
            'game_name' => $this->runtimeVariable['gameName'],
            'game_id' => $this->runtimeVariable['gameId'],
            'competition' => true,
            'top' => $this->runtimeVariable['topRank'],
            'user' => [
                'name' => $this->runtimeVariable['userLbId'],
                'score' => $this->runtimeVariable['userScore'],
                'rank' => $this->runtimeVariable['userRank'],
            ],
            'other' => [
                'start_rank' => $this->runtimeVariable['otherStartRank'],
                'values' => $this->runtimeVariable['other']
            ]
        ];

        $this->io->setResponse($result);
    }

    public function findCompetitionGame()
    {
        $gameDocument = $this->gameModel()->findCompetitionGame();

        $this->runtimeVariable['gameId'] = $gameDocument->_id;
        $this->runtimeVariable['gameLbId'] = 'competition_' . $this->runtimeVariable['gameId'];
        $this->runtimeVariable['gameName'] = $gameDocument->name;
    }

    public function defaultParameters()
    {
        if ($this->io->getUserType() != 'guest') {
            $this->runtimeVariable['userLbId'] = $this->request->ulbid;
            if (!$this->runtimeVariable['userLbId']) {
                throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
            }
        }
    }

    public function specificParameters()
    {
        # game_id for all users
        $this->runtimeVariable['gameId'] = $this->request->game_id;
        if (!$this->runtimeVariable['gameId']) {
            throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
        }

        # userId just for registered user
        if ($this->io->getUserType() != 'guest') {
            $this->runtimeVariable['userLbId'] = $this->request->ulbid;
            if (!$this->runtimeVariable['userLbId']) {
                throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
            }
        }
    }

    public function findGame()
    {
        $gameDocument = $this->gameModel()->findGameById($this->runtimeVariable['gameId']);
        if (!$gameDocument) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::FGBI_CODE);
        }

        $this->runtimeVariable['competitionStatus'] = $gameDocument->competition->activate;
        $this->runtimeVariable['gameName'] = $gameDocument->name;
    }

    public function setGameLbId()
    {
        $this->runtimeVariable['gameLbId'] = ($this->runtimeVariable['competitionStatus']) ?
            'competition_' . $this->runtimeVariable['gameId'] : $this->runtimeVariable['gameId'];
    }

    public function getTopRank()
    {
        $this->runtimeVariable['topRank'] = $this->leaderboardModel()->findRange(
            $this->runtimeVariable['gameLbId'],
            0,
            self::LB_TOP_COUNT
        );
    }

    public function getUserRank()
    {
        if ($this->io->getUserType() != 'guest') {
            # user rank
            $userRank = $this->leaderboardModel()->findUserRank(
                $this->runtimeVariable['gameLbId'],
                $this->runtimeVariable['userLbId']
            );

            $this->runtimeVariable['userRank'] = $userRank;
            $this->runtimeVariable['userScore'] = $userScore;
            $this->runtimeVariable['other'] = $other;
        }
    }

    public function createSpecificResult()
    {
        $result = [
            'game_name' => $this->runtimeVariable['gameName'],
            'game_id' => $this->runtimeVariable['gameId'],
            'competition' => $this->runtimeVariable['competitionStatus'],
            'top' => $this->runtimeVariable['topRank'],
            'user' => [
                'name' => $this->runtimeVariable['userLbId'],
                'rank' => $this->runtimeVariable['userRank'],
                'score' => $this->runtimeVariable['userScore']
            ],
            'other' => [
                'start_rank' => $this->runtimeVariable['otherStartRank'],
                'values' => $this->runtimeVariable['other']
            ]
        ];

        $this->io->setResponse($result);
    }

    private function setLogType()
    {
        $this->logMain()->setLogType('set-score');
    }

    private function setScoreParameters()
    {
        $this->runtimeVariable['userId'] = $this->request->uid;
        $this->runtimeVariable['gameId'] = $this->request->game_id;
        $this->runtimeVariable['userLbId'] = $this->request->ulbid;
        $this->runtimeVariable['inlineMessageId'] = $this->request->imi;
        $this->runtimeVariable['base64Score'] = $this->request->f;

        if (!$this->runtimeVariable['userId'] ||
            !$this->runtimeVariable['gameId'] ||
            !$this->runtimeVariable['base64data'] ||
            !$this->runtimeVariable['hashData']
        ) {
            throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
        }
    }

    private function userAndGameDocument()
    {
        $this->runtimeVariable['userDocument'] = $this->getUserDocument($this->runtimeVariable['userId']);
        $this->logObject = $this->runtimeVariable['userDocument']->analytic_id;

        $gameDocument = $this->getGameDocument($this->runtimeVariable['gameId']);
        $this->runtimeVariable['isCompetitionGame'] = $gameDocument->competition->activate;
        $this->runtimeVariable['gameName'] = $gameDocument->name;
    }

    private function updateLog()
    {
        $this->logMain()->updateDatabaseArray($this->logObject);
    }

    private function updateLog2()
    {
        $this->logMain()->updateDatabaseProperty(
            $this->logObject,
            'finish:pure_score',
            $this->runtimeVariable['cleanScore']
        );
    }

    private function updateLog3()
    {
        $this->logMain()->updateDatabaseProperty(
            $this->logObject,
            'time:duration',
            $this->runtimeVariable['serverPlayTime']
        );
        $this->logMain()->updateDatabaseProperty($this->logObject, 'time:finish', time());
    }

    private function updateLog4()
    {
        $this->logMain()->updateDatabaseProperty(
            $this->logObject,
            'finish:final_score',
            $this->runtimeVariable['finalScore']
        );
    }

    public function verifyClientData()
    {
        # 1.set log type
        $this->setLogType();

        # 2.check set score parameters
        $this->setScoreParameters();

        # 3.get user and game document
        $this->userAndGameDocument();

        # 4.update log
        $this->updateLog();

        # 5.verify data and score hash
        $this->verifyHash();

        # 6.get clean score (decode from base64)
        $this->getCleanScore();

        # 7.update log
        $this->updateLog2();

        # 8.get clean data (decode from base64)
        $this->getCleanData();

        # 9.verify user ban
        $this->verifyUserBan();

        # 10.get play time
        $this->serverPlayTime();

        # 11.update log
        $this->updateLog3();

        # 12.get final score (this formula is same for all games)
        $this->getFinalScore();

        # 13.update log
        $this->updateLog4();

        # 14.verify formula
        $this->formula();
    }

    private function updateLog5()
    {
        $this->logMain()->updateDatabaseProperty($this->logObject, 'finish:asset:coin', $this->runtimeVariable['coin']);
    }

    private function updateTelegramScore()
    {
        $this->botMain()->upsertTelegramScore(
            $this->runtimeVariable['userId'],
            $this->runtimeVariable['inlineMessageId'],
            $this->runtimeVariable['cleanScore']
        );
    }

    public function processAfterVerify()
    {
        # 1.update score in telegram private chat
        $this->updateTelegramScore();

        # 2.verify user type (user must be login for save to db)
        $this->verifyUserType();

        # 3.this is just for normal game (verify play count)
        $this->verifyUserLife();

        # 4.add coin
        $this->addCoin();

        # 5.update log
        $this->updateLog5();

        # 6.check bigger (new score must be bigger than old score)
        $this->bigger();

        # 7.update score in database
        $this->updateScoreToDb();
    }

    private function firstScore()
    {
        $this->runtimeVariable['firstScore'] = $this->leaderboardModel()
                ->findRange($this->runtimeVariable['gameId'], 0, 0)[1] ?? 0;
    }

    private function userTimeBanCount()
    {
        $this->runtimeVariable['timeBanCount'] = $this->runtimeVariable['userDocument']->game
                ->{$this->runtimeVariable['gameId']}->ban->count ?? 0;
    }

    private function fixDivision()
    {
        # handle division by zero
        $this->runtimeVariable['serverPlayTime'] = ($this->runtimeVariable['serverPlayTime'] == 0) ? 1 :
            $this->runtimeVariable['serverPlayTime'];
    }
}
