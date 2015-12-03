<?php
namespace SlimBootstrap;

use \SlimBootstrap;
use \Slim;

/**
 * Interface ResponseOutputWriterStreamable
 *
 * @package SlimBootstrap
 */
interface ResponseOutputWriterStreamable
{
    /**
     * @param Slim\Http\Request  $request  The Slim request instance
     * @param Slim\Http\Response $response The Slim response instance
     * @param Slim\Http\Headers  $headers  The Slim request header instance
     * @param string             $shortName
     */
    public function __construct(
        Slim\Http\Request $request,
        Slim\Http\Response $response,
        Slim\Http\Headers $headers,
        $shortName
    );

    /**
     * @param int $statusCode
     */
    public function setStatusCode($statusCode = 200);

    /**
     * @param DataObject $data
     */
    public function writeToStream(SlimBootstrap\DataObject $data);
}
