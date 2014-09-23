<?php
namespace Rest\Api\Endpoint;

use \Rest\Api\DataObject;

/**
 * This class represents the index endpoint.
 *
 * @package Rest\Api\Endpoint
 */
class Index
{
    /**
     * The data array that holds all endpoint names
     *
     * @var array
     */
    private $_endpoints = array();

    /**
     * @param array $endpoints
     */
    public function __construct(array $endpoints)
    {
        $this->_endpoints = $endpoints;
    }

    /**
     * This function creates a ressource that has links to all existing
     * endpoints.
     *
     * @return DataObject
     */
    public function get()
    {
        $links = array();

        foreach ($this->_endpoints as $endpoint) {
            $links[$endpoint] = '/' . $endpoint;
        }

        return new DataObject(
            array(),
            array(
                'welcome' => 'Welcome.',
            ),
            $links
        );
    }
}
