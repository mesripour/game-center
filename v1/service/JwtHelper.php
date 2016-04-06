<?php

namespace service;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Monolog\Logger;
use Slim\Container;
use \Lcobucci\JWT\Token;

class JwtHelper
{
    const PRIVATE_KEY = '<privateKey>';
    const ONE_MONTH = 2592000;

    private $container;
    private $token;
    private $claimNames;
    private $claimValues;

    /**
     * Token constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->addClaim('exp', time() + self::ONE_MONTH);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function addClaim(string $name, $value)
    {
        $this->claimNames[] = $name;
        $this->claimValues[] = $value;

        return $this;
    }

    public function create()
    {
        $signer = new Sha256();
        $token = (new Builder())
            ->set('names', $this->claimNames)
            ->set('values', $this->claimValues)
            ->sign($signer, self::PRIVATE_KEY)
            ->getToken();

        return $this->token = (string)$token;
    }

    public function validate()
    {
        # 1.parse
        $parsedToken = $this->parse();

        # 2.verify private key
        $this->verifyKey($parsedToken);

        # 3.get claims
        $this->setClaims($parsedToken);

        # 4.verify expire time
        $this->verifyExpTime();
    }

    /**
     * @return mixed
     */
    public function getClaims()
    {
        return $this->pureClaims();
    }

    /**
     * @return array
     */
    private function pureClaims(): array
    {
        $names = $this->claimNames;
        $values = $this->claimValues;

        for ($i = 0; $i < count($names); $i++) {
            $cleanClaims[$names[$i]] = $values[$i];
        }

        return $cleanClaims ?? [];
    }

    /**
     * @param string $name
     * @return $this
     */
    public function removeClaim(string $name)
    {
        $key = array_search($name, $this->claimNames);

        if ($key) {
            unset($this->claimNames[$key]);
            $this->claimNames = array_values($this->claimNames);

            unset($this->claimValues[$key]);
            $this->claimValues = array_values($this->claimValues);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function updateClaim(string $name, $value)
    {
        $key = array_search($name, $this->claimNames);

        if ($key !== false) {
            $this->claimValues[$key] = $value;
        }

        return $this;
    }

    public function reset()
    {
        # delete all claims
        $this->claimNames = null;
        $this->claimValues = null;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return Logger
     */
    private function log(): Logger
    {
        return $this->container->get('logger');
    }

    /**
     * @return Token
     * @throws HubException
     */
    private function parse(): Token
    {
        try {
            $parsedToken = (new Parser())->parse((string)$this->token);
            return $parsedToken;
        } catch (\Exception $e) {
            throw new HubException(HubException::TNV_MESSAGE, HubException::TNV_CODE);
        }
    }

    /**
     * @param Token $parsedToken
     * @throws HubException
     */
    private function verifyKey(Token $parsedToken)
    {
        $signer = new Sha256();
        if (!$parsedToken->verify($signer, self::PRIVATE_KEY)) {
            throw new HubException(HubException::TNV_MESSAGE, HubException::TNV_CODE);
        }
    }

    /**
     * @param Token $parsedToken
     */
    private function setClaims(Token $parsedToken)
    {
        $this->claimNames = $parsedToken->getClaim('names');
        $this->claimValues = $parsedToken->getClaim('values');
    }

    private function verifyExpTime()
    {
        $expireTime = $this->getClaims()['exp'];
        if (time() > $expireTime) {
            throw new HubException(HubException::TE_MESSAGE, HubException::TE_CODE);
        }
    }
}