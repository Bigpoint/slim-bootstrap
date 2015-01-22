<?php
namespace SlimBootstrap\Authentication;

use \Monolog;
use \SlimBootstrap;
use \Slim;

/**
 * Class Factory
 *
 * @package SlimBootstrap\Authentication
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
     * @return SlimBootstrap\Authentication\Oauth
     */
    public function createOauth()
    {
        return new SlimBootstrap\Authentication\Oauth(
            $this->_config['apiUrl'],
            $this->_logger
        );
    }
}
