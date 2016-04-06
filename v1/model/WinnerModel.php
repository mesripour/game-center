<?php

namespace model;

class WinnerModel extends MainModel
{
    public function findAllWinners()
    {
        return $this->mongo('winners')->find()->toArray();
    }

    public function findOneWinner(string $title)
    {
        return $this->mongo('winners')->findOne(['en_title' => $title]);
    }

}
