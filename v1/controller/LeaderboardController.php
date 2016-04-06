<?php

namespace controller;

use main\LeaderboardMain;

class LeaderboardController extends MainController
{
    private function leaderboardMain(): LeaderboardMain
    {
        return $this->container->get('leaderboardMain');
    }

    public function setScore()
    {
        # 1.verify client data
        $this->leaderboardMain()->verifyClientData();

        # 2.process data after verify client
        $this->leaderboardMain()->processAfterVerify();
    }

    /**
     * click on leaderboard from menu (have no game_id and competition)
     */
    public function byDefault()
    {
        # 1.check default parameters
        $this->leaderboardMain()->defaultParameters();

        # 2.find competition game
        $this->leaderboardMain()->findCompetitionGame();

        # 3. get top rank
        $this->leaderboardMain()->topRank();

        # 4. get user rank
        $this->leaderboardMain()->userRank();

        # 5.create result for default leaderboard
        $this->leaderboardMain()->createDefaultResult();
    }

    /**
     * there are 2 types:
     * 1. with game_id (click on each game --> if game is competition i must return competition leaderboard)
     * 2. with game_id and competition (click on competition link)
     */
    public function specific()
    {
        # 1.check specific parameters
        $this->leaderboardMain()->specificParameters();

        # 2.find game from database
        $this->leaderboardMain()->findGame();

        # 3.set game leaderboard id (game is competition or not)
        $this->leaderboardMain()->setGameLbId();

        # 3.get top rank
        $this->leaderboardMain()->getTopRank();

        # 4.get user rank
        $this->leaderboardMain()->getUserRank();

        # 5.create result for specific leaderboard
        $this->leaderboardMain()->createSpecificResult();
    }

    public function syncLeaderBoard()
    {
        $this->leaderboardMain()->syncLeaderBoard();
    }

    public function report()
    {
        $this->leaderboardMain()->report();
    }

    public function getDiff()
    {
        $this->leaderboardMain()->getDiff();
    }

    public function convertWinnerList()
    {
        $this->leaderboardMain()->convertWinnerList();
    }
}
