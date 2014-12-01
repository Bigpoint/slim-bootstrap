<?php
namespace Rest\Api\Authentication;

use \Rest\Api;
use \Slim;

/**
 * Class Factory
 *
 * @package Rest\Api\Authentication
 */
class Factory
{
    /**
     * @var array
     */
    private $_config = null;

    /**
     * @var Slim\Log
     */
    private $_logger = null;

    /**
     * @param array    $config
     * @param Slim\Log $logger
     */
    public function __construct(array $config, Slim\Log $logger)
    {
        $this->_config = $config;
        $this->_logger = $logger;
    }

    /**
     * @return Api\Authentication\Oauth
     */
    public function createOauth()
    {
        return new Api\Authentication\Oauth(
            $this->_config['apiUrl'],
            $this->_logger
        );
    }
}
