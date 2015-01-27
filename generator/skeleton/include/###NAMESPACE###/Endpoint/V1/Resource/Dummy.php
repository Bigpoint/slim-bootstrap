<?php
namespace ###NAMESPACE###\Endpoint\V1\Resource;

use \###NAMESPACE###;
use \SlimBootstrap;

/**
 * Class Dummy
 *
 * @package ###NAMESPACE###\Endpoint\V1\Resource
 */
class Dummy implements SlimBootstrap\Endpoint\ResourceGet
{
    /**
     * This function is called on a GET request to get all data for this
     * endpoint and put them in a usable format.
     *
     * @param array $parameters The parameters for the endpoint from the GET
     *                          request.
     *
     * @return SlimBootstrap\DataObject
     *
     * @throws SlimBootstrap\Exception
     */
    public function get(array $parameters)
    {
        $dummyId = (int)$parameters[0];
        $data    = array(
            'id'    => $dummyId,
            'key'   => 'dummyKey',
            'value' => 'dummyValue',
        );

        return new SlimBootstrap\DataObject(
            array(
                'dummyId' => $dummyId,
            ),
            $data
        );
    }
}
