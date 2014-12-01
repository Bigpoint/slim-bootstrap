<?php
namespace Rest\Api\Authentication;

use \Monolog;
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
     * @param array          $config
     * @param Monolog\Logger $logger
     */
    public function __construct(array $config, Monolog\Logger $logger)
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
