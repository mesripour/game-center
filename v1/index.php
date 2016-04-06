<?php

error_reporting(0);
header('Access-Control-Allow-Origin: *');


# composer autoload
require __DIR__ . '/../vendor/autoload.php';

# app autoload
require __DIR__ . '/../src/autoload.php';

# Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

# Set up dependencies
require __DIR__ . '/../src/dependencies.php';

# Register middleware
require __DIR__ . '/../src/middleware.php';

# Register routes
require __DIR__ . '/../src/routes.php';

# Run app
$app->run();
