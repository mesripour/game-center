<?php

namespace service;

use \Slim\Container;
use Slim\Http\Request;
use stdClass;
use Monolog\Logger;
use Slim\Http\Response;
use \MongoDB\Collection;

class IO
{
    /** @var Request $request */
    private $request;
    /** @var Response $response */
    private $response;
    private $requestData;
    private $container;
    private $userType;

    /**
     * InputData constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->request = $container->get('request');
        $this->response = $container->get('response');
        $this->setRequestData();
        $this->getUserTypeFromDb();
        $this->setResponse();
    }

    /**
     * @return Logger
     */
    private function log(): Logger
    {
        return $this->container->get('logger');
    }

    private function mongo($collection): Collection
    {
        return $this->container->get('mongo')->{$collection};
    }

    /**
     * @return JwtHelper
     */
    private function token(): JwtHelper
    {
        return $this->container->get('token');
    }

    public function setRequestData()
    {
        # post and get http method parameters
        $getData = $this->request->getQueryParams() ?? [];
        $postData = $this->request->getParsedBody() ?? [];

        # get claim from jwt token
        $token = $getData['token'] ?? $postData['token'];
        $tokenData = $token ? $this->getTokenData($token) : [];

        # merge http parameters and jwt claims
        $mergedData = array_merge($getData, $postData, $tokenData);
        $this->requestData = (object)$mergedData;
    }

    /**
     * @return stdClass
     */
    public function getRequestData(): stdClass
    {
        return $this->requestData;
    }

    /**
     * @param $token
     * @return array
     */
    private function getTokenData($token): array
    {
        # 1.set token
        $this->token()->setToken($token);

        # 2.validate token
        $this->token()->validate();

        # 3.get token claims
        $tokenData = $this->token()->getClaims();

        return $tokenData;
    }

    /**
     * @param string $description
     */
    public function setResponse($description = '')
    {
        $this->response = $this->response->withJson(['code' => 200, 'description' => $description]);
    }

    /**
     * @param array $result
     */
    public function setPlainResponse(array $result)
    {
        $this->response = $this->response->withJson($result);
    }

    /**
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * @return mixed
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * @param mixed $userType
     */
    public function setUserType($userType)
    {
        $this->userType = $userType;
    }

    public function getUserTypeFromDb()
    {
        $userId = $this->requestData->uid;
        $userType = $this->mongo('user')->findOne(['_id' => $userId])->type ?? 'guest';
        $this->setUserType($userType);
    }
}