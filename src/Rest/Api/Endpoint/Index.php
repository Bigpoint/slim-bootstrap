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
     * The data array that holds all endpoint names and their instances.
     *
     * @var array
     */
    private $_data = array();

    /**
     * Sets the data array that holds all endpoint names and their instances.
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->_data = $data;
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

        foreach ($this->_data as $data) {
            $links[$data['name']] = '/' . $data['name'];
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
