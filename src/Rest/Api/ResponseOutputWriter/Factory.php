<?php
namespace Rest\Api\ResponseOutputWriter;

use \Rest\Api;
use \Slim;

/**
 * This is the factory to create a response object depending on the HTTP
 * Accept header in the request.
 *
 * @package Pit\Api\ResponseOutputWriter
 */
class Factory
{
    /**
     * The Slim request object.
     *
     * @var Slim\Http\Request
     */
    private $_request = null;

    /**
     * The Slim response object.
     *
     * @var Slim\Http\Response
     */
    private $_response = null;

    /**
     * The Slim response headers object.
     *
     * @var Slim\Http\Headers
     */
    private $_headers = null;

    /**
     * An array with the accepted Accept headers and the function name to
     * create the response object for them.
     *
     * @var array
     */
    private $_supportedMediaTypes = array(
        'application/hal+json' => '_createJsonHal',
        'application/json'     => '_createJson',
    );

    /**
     * @var string
     */
    private $_shortName = '';

    /**
     * @param Slim\Http\Request  $request  The Slim request object.
     * @param Slim\Http\Response $response The Slim response object.
     * @param Slim\Http\Headers  $headers  The Slim response headers object.
     * @param String             $shortName
     */
    public function __construct(
        Slim\Http\Request $request,
        Slim\Http\Response $response,
        Slim\Http\Headers $headers,
        $shortName
    ) {
        $this->_request   = $request;
        $this->_response  = $response;
        $this->_headers   = $headers;
        $this->_shortName = $shortName;
    }

    /**
     * This method creates a response object determined by the given
     * $acceptHeader.
     *
     * @param string $acceptHeader The HTTP Accept header from the request.
     *
     * @return Api\ResponseOutputWriter The created response object.
     *
     * @throws Api\Exception If no suitable $acceptHeader was given.
     */
    public function create($acceptHeader)
    {
        if (null === $acceptHeader) {
            return $this->_createJsonHal();
        }

        $headers = preg_split('/[,;]/', $acceptHeader);

        /**
         * Loop through accept headers and check if they are supported.
         * Use first supported accept header and create fitting
         * ResponseOutputWriter
         */
        foreach ($headers as $header) {
            if (true === array_key_exists($header, $this->_supportedMediaTypes)) {
                $function = $this->_supportedMediaTypes[$header];
                $instance = $this->$function();

                return $instance;
            }
        }

        if (true === in_array('application/*', $headers)
            || in_array('*/*', $headers)
        ) {
            return $this->_createJsonHal();
        }

        throw new Api\Exception(
            'media type not supported (supported media types: '
            . implode(', ', array_keys($this->_supportedMediaTypes)) .  ')',
            406
        );
    }

    /**
     * This function creates a JsonHal response object.
     *
     * @return Api\ResponseOutputWriter\JsonHal
     */
    private function _createJsonHal()
    {
        return new Api\ResponseOutputWriter\JsonHal(
            $this->_request,
            $this->_response,
            $this->_headers,
            $this->_shortName
        );
    }

    /**
     * This function creates a Json reponse object.
     *
     * @return Api\ResponseOutputWriter\Json
     */
    private function _createJson()
    {
        return new Api\ResponseOutputWriter\Json(
            $this->_request,
            $this->_response,
            $this->_headers,
            $this->_shortName
        );
    }
}
