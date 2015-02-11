<?php
namespace SlimBootstrap;

use \Slim;

/**
 * This class represents the basic API exception to be thrown if an error
 * occurs.
 *
 * @package SlimBootstrap
 */
class Exception extends \Exception
{
    /**
     * @var int
     */
    private $_logLevel = Slim\Log::ERROR;

    /**
     * @param string $message
     * @param int    $code
     * @param int    $logLevel
     */
    public function __construct(
        $message = '',
        $code = 0,
        $logLevel = Slim\Log::ERROR
    ) {
        parent::__construct($message, $code);

        $this->_logLevel = $logLevel;
    }

    /**
     * @return int
     */
    public function getLogLevel()
    {
        return $this->_logLevel;
    }
}
