<?php
namespace Rest\Api\ResponseOutputWriter;

use \Nocarrier as hal;
use \Rest\Api;
use \Slim;

/**
 * This class is responsible to output the data to the client in valid
 * HAL+JSON format.
 *
 * @package Rest\Api\ResponseOutputWriter
 */
class JsonHal implements Api\ResponseOutputWriter
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
     * This function outputs the given $data as valid HAL+JSON to the client
     * and sets the HTTP Response Code to the given $statusCode.
     *
     * @param array|Api\DataObject $data       The data to output to the client
     * @param int                  $statusCode The status code to set in the
     *                                         reponse
     */
    public function write($data, $statusCode = 200)
    {
        $path = $this->_request->getPath();
        $hal  = new hal\Hal($path);

        if (true === is_array($data)) {
            $pathData     = explode('/', $path);
            unset($pathData[0]);
            $endpointName = end($pathData);
            $endpointUri  = '/' . implode('/', $pathData) . '/';

            foreach ($data as $entry) {
                /** @var Api\DataObject $entry */
                $identifiers  = $entry->getIdentifiers();
                $resourceName = $endpointUri
                    . implode('/', array_values($identifiers));

                $resource = new hal\Hal(
                    $resourceName,
                    $entry->getData() + $entry->getIdentifiers()
                );

                $this->_addAdditionalLinks($resource, $entry->getLinks());

                $hal->addLink($endpointName, $resourceName);
                $hal->addResource($endpointName, $resource);
            }
        } else {
            $hal->setData(
                $data->getData() + $data->getIdentifiers()
            );

            $this->_addAdditionalLinks($hal, $data->getLinks());
        }

        $this->_headers->set(
            'Content-Type',
            'application/hal+json; charset=UTF-8'
        );

        $this->_response->setStatus($statusCode);
        $this->_response->setBody($hal->asJson());
    }

    /**
     * This function adds the given $links to the $hal object.
     *
     * @param hal\Hal $hal   The Hal object to add the links to
     * @param array   $links The links to add
     */
    private function _addAdditionalLinks(hal\Hal $hal, array $links)
    {
        foreach ($links as $rel => $uri) {
            $hal->addLink($this->_shortName . ':' . $rel, $uri);
        }
    }
}
