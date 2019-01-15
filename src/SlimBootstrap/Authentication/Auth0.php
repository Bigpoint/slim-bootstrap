<?php
namespace SlimBootstrap\Authentication;

use \Auth0\SDK as auth0Sdk;
use \Monolog;
use \SlimBootstrap;

class Auth0 implements SlimBootstrap\Authentication
{
    /**
     * @var array
     */
    private $authorizedIss;

    /**
     * @var Monolog\Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $signingSecret;

    /**
     * @var array
     */
    private $supportedAlgorithms;

    /**
     * @var array
     */
    private $validAudiences;

    /**
     * @param array          $authorizedIss
     * @param Monolog\Logger $logger
     * @param string         $signingSecret
     * @param array          $supportedAlgorithms
     * @param array          $validAudiences
     */
    public function __construct(
        array $authorizedIss,
        Monolog\Logger $logger,
        $signingSecret,
        array $supportedAlgorithms,
        array $validAudiences
    ) {
        $this->authorizedIss       = $authorizedIss;
        $this->logger              = $logger;
        $this->signingSecret       = $signingSecret;
        $this->supportedAlgorithms = $supportedAlgorithms;
        $this->validAudiences      = $validAudiences;
    }

    /**
     * @param string $token Access token from the calling client
     *
     * @return string The clientId of the calling client.
     *
     * @throws \Exception
     */
    public function authenticate($token)
    {
        try {
            $verifier = $this->createVerifier();

            $tokenInfo = $verifier->verifyAndDecode($token);

            return $tokenInfo->sub;
        } catch (auth0Sdk\Exception\CoreException $coreException) {
            $this->logger->addDebug($coreException);

            throw new SlimBootstrap\Exception('Access token invalid', 401, \Slim\Log::WARN);
        }
    }

    /**
     * @return auth0Sdk\JWTVerifier
     *
     * @throws auth0Sdk\Exception\CoreException
     */
    private function createVerifier()
    {
        $verifier = new auth0Sdk\JWTVerifier(array(
            'authorized_iss'  => $this->authorizedIss,
            'client_secret'   => $this->signingSecret,
            'supported_algs'  => $this->supportedAlgorithms,
            'valid_audiences' => $this->validAudiences,
        ));

        return $verifier;
    }
}
