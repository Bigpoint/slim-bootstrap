<?php
namespace SlimBootstrap;

use \Slim;

/**
 * This class represents the basic API exception to be thrown if an error
 * occurs.
 *
 * @package SlimBootstrap
 */
class CSVEncodingException extends Exception
{
    /**
     * @param string $message
     * @param int    $code
     * @param int    $logLevel
     */
    public function __construct(
        $message = '',
        $code = 500,
        $logLevel = Slim\Log::CRITICAL
    ) {
        parent::__construct($message, $code, $logLevel);
    }

}
