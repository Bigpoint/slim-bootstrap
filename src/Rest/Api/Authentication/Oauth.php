<?php
namespace Rest\Api\Authentication;

use \Rest\Api;

/**
 * This class is reponsible for checking if the current user is authenticated
 * to call the API. It does that by validating the token parameter against the
 * P2 API.
 *
 * @package Rest\Api\Authentication
 */
class Oauth implements Api\Authentication
{
    /**
     * URL of the P2 authentication service.
     *
     * @var string
     */
    private $_apiUrl = '';

    /**
     * @param string $apiUrl URL of the P2 authentication service
     */
    public function __construct($apiUrl)
    {
        $this->_apiUrl = $apiUrl;
    }

    /**
     * @param string $token Access token from the calling client
     *
     * @return string The clientId of the calling client.
     *
     * @throws Api\Exception When the passed access $token is invalid.
     */
    public function authenticate($token)
    {
        $result = json_decode($this->_call($token), true);

        if (null === $result
            || false === array_key_exists('entity_id', $result)
        ) {
            throw new Api\Exception('Access token invalid', 401);
        }

        return $result['entity_id'];
    }

    /**
     * @param string $token Access token from the calling client
     *
     * @return string|false The result from the CURL call against the P2 API.
     *
     * @codeCoverageIgnore This function is not tested because we can't test
     *                     curl_* calls in PHPUnit.
     */
    protected function _call($token)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->_apiUrl . $token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Accept: application/json',
            )
        );

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }
}
