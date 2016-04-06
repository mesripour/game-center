<?php

$container = $app->getContainer();

# custom error handling
$container['notAllowedHandler'] = function () {
    throw new \service\HubException(\service\HubException::HM_MESSAGE, \service\HubException::HM_CODE);
};
$container['phpErrorHandler'] = function () {
    throw new \service\HubException(\service\HubException::ISE_MESSAGE, \service\HubException::ISE_CODE);
};
$container['notFoundHandler'] = function () {
    throw new \service\HubException(\service\HubException::NF_MESSAGE, \service\HubException::NF_CODE);
};

$container['logger'] = function ($container) {
    /** @var  $container \Slim\Container*/
    $settings = $container->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

$container['token'] = function ($container) {
    return new \service\JwtHelper($container);
};

$container['io'] = function ($container) {
    return new \service\IO($container);
};

$container['mongo'] = function ($container) {
    /** @var  $container \Slim\Container*/
    $settings = $container->get('settings')['mongodb'];
    return (new MongoDB\Client($settings['host']))->selectDatabase($settings['dbname']);
};

$container['redis'] = function ($container) {
    /** @var  $container \Slim\Container*/
    $settings = $container->get('settings')['redis'];
    $connection = new \Predis\Client([
        'scheme' => $settings['schema'],
        'host'   => $settings['host'],
        'port'   => $settings['port'],
        'database' => $settings['defaultDB']
    ]);
    return $connection;
};

$container['gameMain'] = function ($container) {
    return new \main\GameMain($container);
};

$container['gameModel'] = function ($container) {
    return new \model\GameModel($container);
};

$container['leaderboardMain'] = function ($container) {
    return new \main\LeaderboardMain($container);
};

$container['leaderboardModel'] = function ($container) {
    return new \model\LeaderboardModel($container);
};

$container['botMain'] = function ($container) {
    return new \main\BotMain($container);
};

$container['userMain'] = function ($container) {
    return new \main\UserMain($container);
};

$container['userModel'] = function ($container) {
    return new \model\UserModel($container);
};

$container['newsMain'] = function ($container) {
    return new \main\NewsMain($container);
};

$container['newsModel'] = function ($container) {
    return new \model\NewsModel($container);
};

$container['shopMain'] = function ($container) {
    return new \main\ShopMain($container);
};

$container['shopModel'] = function ($container) {
    return new \model\ShopModel($container);
};

$container['logMain'] = function ($container) {
    return new \main\LogMain($container);
};

$container['logModel'] = function ($container) {
    return new \model\LogModel($container);
};

$container['winnerModel'] = function ($container) {
    return new \model\WinnerModel($container);
};

$container['redirectModel'] = function ($container) {
    return new \model\RedirectModel($container);
};

$container['winnersMain'] = function ($container) {
    return new \main\WinnersMain($container);
};
