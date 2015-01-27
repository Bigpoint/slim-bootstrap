<?php
namespace ###NAMESPACE###\Endpoint\V1\Collection;

use \###NAMESPACE###;
use \SlimBootstrap;

/**
 * Class Dummy
 *
 * @package ###NAMESPACE###\Endpoint\V1\Collection
 */
class Dummy implements SlimBootstrap\Endpoint\CollectionGet
{
    /**
     * This function is called on a GET request to get all data for this
     * endpoint and put them in a usable format.
     *
     * @param array $filters array of key => value pairs to filter the result
     *
     * @return array
     */
    public function get(array $filters)
    {
        $data = array(
            new SlimBootstrap\DataObject(
                array(
                    'dummyId' => 1,
                ),
                array(
                    'id'    => 1,
                    'key'   => 'dummyKey1',
                    'value' => 'dummyValue1',
                )
            ),
            new SlimBootstrap\DataObject(
                array(
                    'dummyId' => 2,
                ),
                array(
                    'id'    => 2,
                    'key'   => 'dummyKey2',
                    'value' => 'dummyValue2',
                )
            ),
        );

        return $data;
    }
}
