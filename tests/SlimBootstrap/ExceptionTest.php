<?php
namespace SlimBootstrap;

use \Slim;

/**
 * Class ExceptionTest
 */
class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetLogLevel()
    {
        $expected  = Slim\Log::WARN;
        $exception = new Exception('mockMessage', 0, $expected);
        $actual    = $exception->getLogLevel();

        $this->assertEquals($expected, $actual);
    }
}
