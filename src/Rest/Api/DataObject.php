<?php
namespace Rest\Api;

/**
 * This class represents a result set for the endpoints.
 * One instance represents a HAL+JSON ressource for one specific embedded
 * ressource.
 *
 * @package Rest\Api
 */
class DataObject
{
    /**
     * The identifiers to show in the HAL+JSON output how this ressource is
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
     * The links to show in the HAL+JSON output for this ressource.
     *
     * @var array
     */
    private $_links = array();

    /**
     * @param array $identifiers The identifiers to show in the HAL+JSON output.
     * @param array $data        The actual data to pass to the output.
     * @param array $links       The links to show in the HAL+JSON output for
     *                           this ressource.
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
     * Returns the HAL+JSON identifiers for this ressource.
     *
     * @return array
     */
    public function getIdentifiers()
    {
        return $this->_identifiers;
    }

    /**
     * Returns the actual payload of this ressource.
     *
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Returns the links for this HAL+JSON ressource.
     *
     * @return array
     */
    public function getLinks()
    {
        return $this->_links;
    }
}
