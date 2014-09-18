<?php
namespace Rest\Api;

/**
 * This interface represents the basic structure of all response classes.
 *
 * @package Rest\Api
 */
interface Response
{
    /**
     * @param \Slim\Http\Request  $request  The Slim request instance
     * @param \Slim\Http\Response $response The Slim response instance
     * @param \Slim\Http\Headers  $headers  The Slim request header instance
     * @param String              $shortName
     */
    public function __construct(
        \Slim\Http\Request $request,
        \Slim\Http\Response $response,
        \Slim\Http\Headers $headers,
        $shortName
    );

    /**
     * This method is called to output the passed $data with the given
     * $statusCode.
     *
     * @param array|DataObject $data       The actual data to output
     * @param int              $statusCode The HTTP status code to return
     */
    public function output($data, $statusCode = 200);
}
