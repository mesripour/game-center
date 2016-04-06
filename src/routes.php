<?php

#------------------------ user

$app->get('/user/mygame', function () {
    (new \controller\UserController($this))->myGame();
})->add($login);

$app->get('/user/assets', function () {
    (new \controller\UserController($this))->getAssets();
})->add($register);

$app->get('/user/sdu', function () {
    (new \controller\UserController($this))->safeDeleteUser();
})->add($guest);

#------------------------ game

$app->get('/game/list', function () {
    (new \controller\GameController($this))->gameList();
})->add($guest);

$app->get('/game/insidelist', function () {
    (new \controller\GameController($this))->finishGameList();
})->add($guest);

$app->get('/game/send', function () {
    (new \controller\GameController($this))->playGame();
})->add($guest);

$app->get('/game/competition', function () {
    (new \controller\GameController($this))->competition();
})->add($guest);

$app->get('/game/menu', function () {
    (new \controller\GameController($this))->menu();
})->add($register);

$app->post('/game/start', function () {
    (new \controller\GameController($this))->start();
})->add($register);

$app->post('/game/like', function () {
    (new \controller\GameController($this))->like();
})->add($login);

$app->get('/game/share', function () {
    (new \controller\GameController($this))->share();
})->add($guest);

#------------------------ leaderboard

$app->post('/score/set', function () {
    (new \controller\LeaderboardController($this))->setScore();
})->add($register);

$app->get('/leaderboard/default', function () {
    (new \controller\LeaderboardController($this))->byDefault();
})->add($guest);

$app->get('/leaderboard', function () {
    (new \controller\LeaderboardController($this))->specific();
})->add($guest);

$app->get('/leaderboard/syncLeaderBoard', function () {
    (new \controller\LeaderboardController($this))->syncLeaderBoard();
})->add($guest);

#------------------------ news

# not use
$app->get('/news/all', function () {
    (new \controller\NewsController($this))->getAllNews();
})->add($guest);

# not use
$app->get('/news/one', function () {
    (new \controller\NewsController($this))->getOneNews();
})->add($guest);

# not use
$app->post('/news/edit', function () {
    (new \controller\NewsController($this))->editNews();
})->add($guest);

#------------------------ profile

$app->get('/profile/get', function () {
    (new \controller\UserController($this))->getProfile();
})->add($register);

$app->post('/profile/set', function () {
    (new \controller\UserController($this))->setProfile();
})->add($register);

#------------------------ bot

$app->get('/bot/load', function () {
    (new \controller\GameController($this))->load();
})->add($guest);

$app->get('/bot/loadgame', function () {
    (new \controller\GameController($this))->loadGame();
})->add($guest);

#------------------------ shop

$app->get('/shop/packageslist', function () {
    (new \controller\ShopController($this))->getPackagesList();
})->add($guest);

$app->post('/shop/packageinfo', function () {
    (new \controller\ShopController($this))->getPackageInfo();
})->add($guest);

$app->post('/shop/buy', function () {
    (new \controller\ShopController($this))->buyPackage();
})->add($login);

$app->post('/shop/subscribe', function () {
    (new \controller\ShopController($this))->subscribe();
})->add($login);

$app->get('/hidden/usercount', function () {
    (new \controller\UserController($this))->userCount();
})->add($guest);

$app->get('/report', function () {
    (new \controller\LeaderboardController($this))->report();
})->add($guest);

$app->get('/getdiff', function () {
    (new \controller\LeaderboardController($this))->getDiff();
})->add($guest);

$app->get('/convertwinnerlist', function () {
    (new \controller\LeaderboardController($this))->convertWinnerList();
})->add($guest);

#------------------------ Winners

$app->get('/winners', function () {
    (new \controller\WinnersController($this))->getAllWinner();
})->add($guest);


#------------------------ test

$app->get('/test', function () {
    (new \controller\UserController($this))->test();
})->add($guest);
