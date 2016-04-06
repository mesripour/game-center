<?php

namespace model;

class GameModel extends MainModel
{
    /**
     * @return array
     */
    public function findAllGames(): array
    {
        return $this->mongo('game')->find()->toArray();
    }

    /**
     * @param string $gameId
     * @return array|null|object
     */
    public function findGameById(string $gameId)
    {
        return $this->mongo('game')->findOne(['_id' => $gameId]);
    }

    /**
     * @return array|null|object
     */
    public function findCompetitionGame()
    {
        return $this->mongo('game')->findOne(['competition.activate' => true]);
    }

    /**
     * @param string $gameId
     */
    public function increaseGameCounter(string $gameId)
    {
        $this->mongo('game')->updateOne(
            ['_id' => $gameId],
            ['$inc' => [
                'counter' => 1
            ]]
        );
    }
}
