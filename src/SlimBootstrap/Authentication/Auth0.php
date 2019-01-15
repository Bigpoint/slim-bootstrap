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
     * @var string
     */
    private $clientSecret;

    /**
     * @var Monolog\Logger
     */
    private $logger;

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
     * @param string         $clientSecret
     * @param Monolog\Logger $logger
     * @param array          $supportedAlgorithms
     * @param array          $validAudiences
     */
    public function __construct(
        array $authorizedIss,
        $clientSecret,
        Monolog\Logger $logger,
        array $supportedAlgorithms,
        array $validAudiences
    ) {
        $this->authorizedIss       = $authorizedIss;
        $this->clientSecret        = $clientSecret;
        $this->logger              = $logger;
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
            $this->logger->addDebug(
                $coreException->getMessage(),
                array(
                    'trace' => $coreException->getTrace(),
                )
            );
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
            'client_secret'   => $this->clientSecret,
            'supported_algs'  => $this->supportedAlgorithms,
            'valid_audiences' => $this->validAudiences,
        ));

        return $verifier;
    }
}
