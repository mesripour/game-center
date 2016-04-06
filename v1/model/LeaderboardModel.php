<?php

namespace model;

class LeaderboardModel extends MainModel
{
    /**
     * @param string $gameId
     * @param int $start
     * @param int $stop
     * @return mixed
     */
    public function findRange(string $gameId, int $start, int $stop)
    {
        return $this->redis()->executeRaw(['zrevrange', $gameId, $start, $stop, 'withscores']);
    }

    /**
     * @param string $gameId
     * @param string $userLbId
     * @return mixed
     */
    public function findUserScore(string $gameId, string $userLbId)
    {
        return $this->redis()->executeRaw(['zscore', $gameId, $userLbId]);
    }

    /**
     * @param string $gameId
     * @param string $userLbId
     * @return mixed
     */
    public function findUserRank(string $gameId, string $userLbId)
    {
        return $this->redis()->executeRaw(['zrevrank', $gameId, $userLbId]);
    }

    /**
     * @param string $gameId
     * @param string $userLbId
     * @param float $newScore
     * @return mixed
     */
    public function upsertUserScore(string $gameId, string $userLbId, float $newScore)
    {
        return $this->redis()->executeRaw(['zadd', $gameId, $newScore, $userLbId]);
    }

    /**
     * @param string $gameId
     * @param string $userLbId
     * @return mixed
     */
    public function deleteUser(string $gameId, string $userLbId)
    {
        return $this->redis()->executeRaw(['zrem', $gameId, $userLbId]);
    }

    /**
     * @param string $gameId
     * @return array
     */
    public function getAllKV(string $gameId)
    {
        return $this->redis()->zrange($gameId, 0, -1);
    }
}
