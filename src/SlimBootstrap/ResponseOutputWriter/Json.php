<?php
namespace SlimBootstrap\ResponseOutputWriter;

use \SlimBootstrap;
use \Slim;

/**
 * This class is responsible to output the data to the client in valid JSON
 * format.
 *
 * @package SlimBootstrap\ResponseOutputWriter
 */
class Json implements SlimBootstrap\ResponseOutputWriter,
                      SlimBootstrap\ResponseOutputWriterPlainData
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
        $this->_request  = $request;
        $this->_response = $response;
        $this->_headers  = $headers;
    }

    /**
     * @param array $data
     * @param int   $statusCode
     */
    public function writePlain(array $data, $statusCode = 200)
    {
        $this->_headers->set(
            'Content-Type',
            'application/json; charset=UTF-8'
        );
        $this->_response->setStatus($statusCode);
        $this->_response->setBody($this->_jsonEncode($data));
    }

    /**
     * This function outputs the given $data as valid JSON to the client
     * and sets the HTTP Response Code to the given $statusCode.
     *
     * @param array|SlimBootstrap\DataObject $data       The data to output to
     *                                                   the client
     * @param int                            $statusCode The status code to set
     *                                                   in the response
     */
    public function write($data, $statusCode = 200)
    {
        $result = array();

        if (true === is_array($data)) {
            foreach ($data as $entry) {
                /** @var SlimBootstrap\DataObject $entry */
                $identifiers = array_values($entry->getIdentifiers());

                $this->_buildStructure($entry, $identifiers, 0, $result);
            }
        } else {
            $identifiers = array_values($data->getIdentifiers());

            $this->_buildStructure($data, $identifiers, 0, $result);
        }

        $body = $this->_jsonEncode($result);

        if (false === $body) {
            $this->_response->setStatus(500);
            $this->_response->setBody("Error encoding requested data.");
            return;
        }

        $this->_headers->set(
            'Content-Type',
            'application/json; charset=UTF-8'
        );

        $this->_response->setStatus($statusCode);
        $this->_response->setBody($body);
    }

    /**
     * Creates a structured array for each given DataObject.
     *
     * @param SlimBootstrap\DataObject $data        The DataObject to get the
     *                                              actual payload from
     * @param array                    $identifiers The identifiers to build
     *                                              the array structure
     * @param int                      $index       The index of the current
     *                                              element in the identifiers
     *                                              array
     * @param array                    $result      Reference of the result
     *                                              array to fill
     */
    private function _buildStructure(
        SlimBootstrap\DataObject $data,
        array $identifiers,
        $index,
        array &$result
    ) {
        if (true === array_key_exists($index, $identifiers)
            && false === array_key_exists($identifiers[$index], $result)
        ) {
            $result[$identifiers[$index]] = array();
        }

        if (true === array_key_exists($index + 1, $identifiers)) {
            $this->_buildStructure(
                $data,
                $identifiers,
                $index + 1,
                $result[$identifiers[$index]]
            );
        } else {
            if (true === array_key_exists($index, $identifiers)) {
                $result[$identifiers[$index]] = $data->getData();
            } else {
                $result = $data->getData();
            }
        }
    }

    /**
     * @param $data
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    protected function _jsonEncode($data)
    {
        return json_encode($data);
    }
}
