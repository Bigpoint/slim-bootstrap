<?php
namespace SlimBootstrap;

use \SlimBootstrap;
use \Slim;

/**
 * This interface represents the basic structure of all response classes.
 *
 * @package SlimBootstrap
 */
interface ResponseOutputWriter
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
     * This method is called to output the passed $data with the given
     * $statusCode.
     *
     * @param array|SlimBootstrap\DataObject $data       The actual data to
     *                                                   output
     * @param int                            $statusCode The HTTP status code to
     *                                                   return
     */
    public function write($data, $statusCode = 200);
}
