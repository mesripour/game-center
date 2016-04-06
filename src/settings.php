<?php

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => true, // Allow the web server to send the content-length header

        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../log/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        'mongodb' => [
            'host' => '<host>',
            'dbname' => '<dnname>',
        ],

        'redis' => [
            'schema' => 'tcp',
            'host' => '<host>',
            'port' => 6379,
        ],

        'bot' => [
            'id' => '<id>',
            'name' => '<name>',
        ],

        'privateKey' =>[
            '<privateKey>'
        ],

        'baseUrl' => [
            'centerView' => '<view>',
            'smsUrl' => '<smsUrl>',
            'redirect' => [
                'center' => 'center',
                'moreGames' => '<moreGames>',
                'leaderboard' => '<leaderboard>',
            ]
        ]
    ],
];
