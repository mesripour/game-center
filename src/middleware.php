<?php

use service\HubException;

$guest = function ($request, $response, $next) {
    $next($request, $response);
    return $this->get('io')->getResponse();
};

$register = function ($request, $response, $next) {
    /** @var \service\IO $io */
    $io = $this->get('io');

    if (($io->getUserType() == 'register') || ($io->getUserType() == 'login')) {
        $next($request, $response);
    } else {
        throw new HubException(HubException::MBL_MESSAGE, HubException::MBL_CODE);
    }

    return $this->get('io')->getResponse();
};

$login = function ($request, $response, $next) {
    /** @var \service\IO $io */
    $io = $this->get('io');

    if ($io->getUserType() == 'login') {
        $next($request, $response);
    } else {
        throw new HubException(HubException::MBS_MESSAGE, HubException::MBS_CODE);
    }

    return $this->get('io')->getResponse();
};
