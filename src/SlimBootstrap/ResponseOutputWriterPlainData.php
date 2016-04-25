<?php
namespace SlimBootstrap;

use \SlimBootstrap;
use \Slim;

/**
 * Interface ResponseOutputWriterPlainData
 *
 * @package SlimBootstrap
 */
interface ResponseOutputWriterPlainData
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
     * @param array $data
     */
    public function writePlain(array $data);
}
