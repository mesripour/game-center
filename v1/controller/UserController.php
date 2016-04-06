<?php

namespace controller;

use main\UserMain;

class UserController extends MainController
{
    private function userMain(): UserMain
    {
        return $this->container->get('userMain');
    }

    /**
     * created by m.alipour
     */
    public function safeDeleteUser()
    {
        $this->userMain()->safeDeleteUser();
    }

    public function myGame()
    {
        # 1.check my game parameters
        $this->userMain()->myGameParameters();

        # 2.find user's game from database
        $this->userMain()->findMyGame();

        # 3.find and create image url of game
        $this->userMain()->findImageUrl();

        # 4.create result for my game
        $this->userMain()->createMyGameResult();
    }

    public function setProfile()
    {
        # 1.check set profile parameters
        $this->userMain()->setProfileParameters();

        # 2.check duplicate username
        $this->userMain()->checkDuplicateUser();

        # 3.find user profile from database
        $this->userMain()->findUser();

        # 4.update user profile
        $this->userMain()->updateProfile();

        # 5.update [redis, token, redirectUrl] if username or state changed
        $this->userMain()->updates();

        # 6.create new result for set profile
        $this->userMain()->createSetProfileResult();
    }

    public function getProfile()
    {
        # 1.check get profile parameters
        $this->userMain()->getProfileParameters();

        # 2.find user from database
        $this->userMain()->findUserProfile();

        # 3.create result for get profile
        $this->userMain()->createGetProfileResult();
    }

    public function test()
    {
        # just for debugging
        $this->userMain()->test();
    }

    public function getAssets()
    {
        # get user assets
        $this->userMain()->getAssets();
    }

    public function userCount()
    {
        # just for show report and save to log file - log path: /log/usercount
        $this->userMain()->userCount();
    }
}