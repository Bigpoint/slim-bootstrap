<?php
namespace Rest\Api\Authentication;

use \Rest\Api;

/**
 * Class Factory
 *
 * @package Rest\Api\Authentication
 */
class Factory
{
    /**
     * @var \stdClass
     */
    private $_config = null;

    public function __construct(\stdClass $config)
    {
        $this->_config = $config;
    }

    /**
     * @return Api\Authentication\Oauth
     */
    public function createOauth()
    {
        return new Api\Authentication\Oauth($this->_config->apiUrl);
    }
}
