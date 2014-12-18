<?php
namespace ###NAMESPACE###\Endpoint\V1\Ressource;

use \###NAMESPACE###;
use \Rest\Api;

/**
 * Class Dummy
 *
 * @package ###NAMESPACE###\Endpoint\V1\Ressource
 */
class Dummy implements Api\Endpoint\RessourceGet
{
    /**
     * This function is called on a GET request to get all data for this
     * endpoint and put them in a usable format.
     *
     * @param array $parameters The parameters for the endpoint from the GET
     *                          request.
     *
     * @return Api\DataObject
     *
     * @throws Api\Exception
     */
    public function get(array $parameters)
    {
        $dummyId = (int)$parameters[0];
        $data    = array(
            'id'    => $dummyId,
            'key'   => 'dummyKey',
            'value' => 'dummyValue',
        );

        return new Api\DataObject(
            array(
                'dummyId' => $dummyId,
            ),
            $data
        );
    }
}
