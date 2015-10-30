<?php
namespace SlimBootstrap;

/**
 * This class represents a result set for the endpoints.
 * One instance represents a HAL+JSON resource for one specific embedded
 * resource.
 *
 * @package SlimBootstrap
 */
class DataObject
{
    /**
     * The identifiers to show in the HAL+JSON output how this resource is
     * identified.
     *
     * @var array
     */
    private $_identifiers = array();

    /**
     * The actual data to pass to the output.
     *
     * @var array
     */
    private $_data = array();

    /**
     * The links to show in the HAL+JSON output for this resource.
     *
     * @var array
     */
    private $_links = array();

    /**
     * @param array $identifiers The identifiers to show in the HAL+JSON output.
     * @param array $data        The actual data to pass to the output.
     * @param array $links       The links to show in the HAL+JSON output for
     *                           this resource.
     */
    public function __construct(
        array $identifiers,
        array $data,
        array $links = array()
    ) {
        $this->_identifiers = $identifiers;
        $this->_data        = $data;
        $this->_links       = $links;
    }

    /**
     * Returns the HAL+JSON identifiers for this resource.
     *
     * @return array
     */
    public function getIdentifiers()
    {
        return $this->_identifiers;
    }

    /**
     * Returns the actual payload of this resource.
     *
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Updates the actual payload of this resource.
     *
     * @param array $data   payload
     */
    public function updateData(array $data)
    {
        $this->_data = $data;
    }

    /**
     * Returns the links for this HAL+JSON resource.
     *
     * @return array
     */
    public function getLinks()
    {
        return $this->_links;
    }
}
