<?php

namespace controller;

use main\BotMain;
use main\GameMain;

class GameController extends MainController
{
    public function gameList()
    {
        # 1.find all game from database
        $this->gameMain()->findAllGames();

        # 2.create result for game list
        $this->gameMain()->createGameListResult();
    }

    /**
     * @return GameMain
     */
    private function gameMain(): GameMain
    {
        return $this->container->get('gameMain');
    }

    public function finishGameList()
    {
        # 1.check finish game parameters
        $this->gameMain()->finishListParameters();

        # 2.find all games from db
        $this->gameMain()->findAllGames();

        # 3.remove selected game from the list
        $this->gameMain()->removeSelectedGame();

        # 4.create result for finish game list
        $this->gameMain()->createFinishListResult();
    }

    public function playGame()
    {
        # 1.check play game parameters
        $this->gameMain()->playGameParameters();

        # 2.find game by id from database
        $this->gameMain()->findGame();

        # 3.redirect to game
        $this->gameMain()->redirectToGame();
    }

    public function competition()
    {
        # 1.check competition parameters
        $this->gameMain()->competitionParameters();

        # 2.find competition game
        $this->gameMain()->findCompetitionGame();

        # 3.get user score and rank if user is not guest
        $this->gameMain()->userScoreAndRank();

        # 4.get first score
        $this->gameMain()->firstScore();

        # 5.get competition finish time
        $this->gameMain()->competitionFinishTime();

        # 6.create result for competition
        $this->gameMain()->createCompetitionResult();
    }

    public function load()
    {
        # redirect to telegram bot
        $this->botMain()->load();
    }

    /**
     * @return BotMain
     */
    private function botMain(): BotMain
    {
        return $this->container->get('botMain');
    }

    public function loadGame()
    {
        # 1.check load games parameters
        $this->botMain()->loadParameters();

        # 2.redirect to game
        $this->botMain()->redirectToGame();
    }

    public function menu()
    {
        # 1.check menu parameters
        $this->gameMain()->menuParameters();

        # 2.get user score and user type
        $this->gameMain()->userScoreAndType();

        # 3.get user's assets
        $this->gameMain()->userAssets();

        # 4.get competition title
        $this->gameMain()->competitionTitle();

        # 5.get competition finish time
        $this->gameMain()->competitionFinishTime();

        # 6.create result for menu
        $this->gameMain()->createMenuResult();
    }

    public function like()
    {
        # 1.check like parameters
        $this->gameMain()->likeParameters();

        # 2.update user like in database
        $this->gameMain()->updateUserLike();
    }

    public function share()
    {
        # 1.check share parameters
        $this->gameMain()->shareParameters();

        # 2.redirect to telegram friends for share game
        $this->gameMain()->redirectToTelegram();
    }

    public function start()
    {
        # 1.verify client data
        $this->gameMain()->verifyClientData();

        # 2.process after client verify
        $this->gameMain()->processAfterVerify();
    }
}
