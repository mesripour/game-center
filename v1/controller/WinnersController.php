<?php

namespace controller;

use main\WinnersMain;

class WinnersController extends MainController
{
    private function winnersMain(): WinnersMain
    {
        return $this->container->get('winnersMain');
    }
    public function getAllWinner()
    {
        $this->winnersMain()->getAllWinners();
    }
}