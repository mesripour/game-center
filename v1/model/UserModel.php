<?php

namespace model;

class UserModel extends MainModel
{
    /**
     * @param string $userId
     * @return array|null|object
     */
    public function findUserById(string $userId)
    {
        return $this->mongo('user')->findOne(['_id' => $userId]);
    }

    /**
     * @param string $phoneNumber
     * @return array|null|object
     */
    public function findUserByPhoneNumber(string $phoneNumber)
    {
        return $this->mongo('user')->findOne(['profile.phone_number.telegram' => $phoneNumber]);
    }

    /**
     * @param string $userId
     * @param string $username
     * @return array|null|object
     */
    public function checkUsernameDuplicate(string $userId, string $username)
    {
        return $this->mongo('user')->findOne(['profile.username' => $username, '_id' => ['$ne' => $userId]]);
    }

    /**
     * @param String $gameId
     * @param string $gameName
     * @param string $userId
     * @param float $newScore
     * @return \MongoDB\UpdateResult
     */
    public function upsertNormalScore(String $gameId, string $gameName, string $userId, float $newScore)
    {
        # save user score to mongo
        return $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$set' => [
                    'game.' . $gameId . '.score.normal' => $newScore,
                    'game.' . $gameId . '.name' => $gameName,
                ],
            ]
        );
    }

    /**
     * @param String $gameId
     * @param string $gameName
     * @param string $userId
     * @param float $score
     * @return \MongoDB\UpdateResult
     */
    public function upsertCompetitionScore(String $gameId, string $gameName, string $userId, float $score)
    {
        # save user score to mongo
        return $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$set' => [
                    'game.' . $gameId . '.score.competition' => $score,
                    'game.' . $gameId . '.name' => $gameName,
                ],
            ]
        );
    }

    /**
     * @param string $userId
     * @return \MongoDB\UpdateResult
     */
    public function decrementUserLife(string $userId)
    {
        return $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$inc' => [
                    'asset.life' => -1,
                ]
            ]
        );
    }

    /**
     * @param string $userId
     * @param string $username
     * @param string $firstName
     * @param string $lastName
     * @param string $state
     * @param string $email
     * @param string $gender
     * @return \MongoDB\UpdateResult
     */
    public function updateProfile(
        string $userId,
        string $username,
        string $firstName,
        string $lastName,
        string $state,
        string $email,
        string $gender
    ) {
        return $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$set' => [
                    'profile.username' => $username,
                    'profile.first_name' => $firstName,
                    'profile.last_name' => $lastName,
                    'profile.state' => $state,
                    'profile.email' => $email,
                    'profile.gender' => $gender,
                ]
            ]
        );
    }

    /**
     * @param string $userId
     * @param int $coin
     * @return \MongoDB\UpdateResult
     */
    public function addCoin(string $userId, int $coin)
    {
        return $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$inc' => [
                    'asset.coin' => $coin
                ],
            ]
        );
    }

    /**
     * @param string $userId
     * @param string $gameId
     * @return \MongoDB\UpdateResult
     */
    public function updateNormalDetails(string $userId, string $gameId)
    {
        return $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$set' => [
                    'time.last_play_time.normal' => round(microtime(true), 3),
                    "game.$gameId.last_play_time" => time()
                ],
                '$inc' => [
                    "game.$gameId.play_count" => 1,
                ]
            ]
        );
    }

    /**
     * @param string $userId
     * @param string $gameId
     * @return \MongoDB\UpdateResult
     */
    public function updateCompetitionDetails(string $userId, string $gameId)
    {
        return $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$set' => [
                    'time.last_play_time.competition' => round(microtime(true), 3),
                    "game.$gameId.last_play_time" => time()
                ],
                '$inc' => [
                    "game.$gameId.play_count" => 1,
                ]
            ]
        );
    }

    /**
     * @param string $userId
     * @param string $gameId
     * @param bool $permanently
     * @param int $expireTime
     * @return \MongoDB\UpdateResult
     */
    public function userBan(
        string $userId,
        string $gameId,
        bool $permanently = true,
        int $expireTime = 0
    ) {
        return $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$set' => [
                    "game.$gameId.ban.permanently" => $permanently,
                    'game.' . $gameId . '.ban.expire_time' => $expireTime,
                ]
            ]
        );
    }

    /**
     * @return bool
     */
    public function verifySubscribe()
    {
        # TODO: get from vas
        return true;
    }

    /**
     * @param string $userId
     * @return \MongoDB\UpdateResult
     */
    public function incrementGemCount(string $userId)
    {
        return $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$inc' => [
                    'asset.gem' => 10,
                ]
            ]
        );
    }

    /**
     * @param string $userId
     * @param string $gameId
     * @param bool $status
     */
    public function updateGameLikeStatus(string $userId, string $gameId, bool $status)
    {
        $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$set' => [
                    "game.$gameId.like" => $status
                ]
            ]
        );
    }

    public function getUserAssetsById(string $userId)
    {
        return $this->mongo('user')->findOne(['_id' => $userId])->asset;
    }


    /**
     * @param string $userId
     * @param string $gameId
     * @return \MongoDB\UpdateResult
     */
    public function increaseTimeBan(string $userId, string $gameId)
    {
        return $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$inc' => [
                    "game.$gameId.ban.count" => 1,
                    "game.$gameId.ban.total_count" => 1,
                ]
            ]
        );
    }


    /**
     * returns last user life recharge time
     * @param string $userId
     * @return int
     */
    public function getLastLifeRechargeTime(string $userId)
    {
        return $this->mongo('user')->findOne(['_id' => $userId])->time->last_life_recharge ?? 0;
    }

    /**
     * @param string $userId
     */
    public function rechargeUserLife(string $userId)
    {
        $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$set' => [
                    'time.last_life_recharge' => time()
                ],
                '$inc' => [
                    "asset.life" => 1,
                ]
            ]
        );
    }

    /**
     * @param string $userId
     */
    public function updateLastLifeRecharge(string $userId)
    {
        $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$set' => [
                    'time.last_life_recharge' => time()
                ],
            ]
        );
    }

    /**
     * @param string $userId
     * @param $analyticId
     */
    public function updateUserAnalyticId(string $userId, $analyticId)
    {
        $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$set' => [
                    'analytic_id' => $analyticId
                ],
            ]
        );
    }

    /**
     * @param string $userId
     * @param string $gameId
     */
    public function resetTimeBan(string $userId, string $gameId)
    {
        $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$set' => [
                    "game.$gameId.ban.count" => 0,
                ]
            ]
        );
    }

    /**
     * @param string $userId
     * @param $logType
     * @param $analyticId
     * @param string $gameId
     */
    public function addAnalyticLog(string $userId, $logType, $analyticId, string $gameId)
    {
        $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$push' => [
                    "game.$gameId.ban.collect" => [
                        'type' => $logType,
                        'analytic_id' => $analyticId,
                        'time' => time(),
                    ],
                ]
            ]
        );
    }

    /**
     * @return array
     */
    public function userCount(): array
    {
        $response['all users'] = $this->mongo('user')->count();
        $response['users with phone number'] = $this->mongo('user')->count(['type' => 'login']);
        $response['users started bot'] = $this->mongo('user')->count(['profile.bot_initiate' => true]);
        return $response;
    }

    /**
     * @param array $docs
     * @param string $tbl_name
     */
    public function report(array $docs, string $tbl_name)
    {
        foreach (array_reverse($docs) as $doc) :
            $this->mongo($tbl_name)->updateOne(
                ['_id' => $doc['user_id']],
                [
                    '$set' => [
                        "username" => $doc['username'],
                        "phone_number" => $doc['phone_number'],
                        "provider" => $doc['provider'],
                        "score" => $doc['score'],
                    ]
                ],
                ['upsert' => true]
            );
        endforeach;
    }

    /**
     * @param string $userId
     * @param int $currency
     * @param int $price
     */
    public function decreaseUserAssets(string $userId, int $currency, int $price)
    {
        $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$inc' => [
                    "asset.$currency" => -$price
                ]
            ]
        );
    }

    /**
     * @param string $userId
     * @param int $gems
     * @param int $lives
     */
    public function increaseUserAssets(string $userId, int $gems, int $lives)
    {
        $this->mongo('user')->updateOne(
            ['_id' => $userId],
            [
                '$inc' => [
                    'asset.gem' => +$gems,
                    'asset.life' => +$lives,
                ]
            ]
        );
    }
}
