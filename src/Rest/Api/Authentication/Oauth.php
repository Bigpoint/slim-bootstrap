<?php
namespace Rest\Api\Authentication;

use \Monolog;
use \Rest\Api;
use \Slim;

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
     * @var Monolog\Logger
     */
    private $_logger = null;

    /**
     * @param string         $apiUrl URL of the P2 authentication service
     * @param Monolog\Logger $logger Logger instance
     */
    public function __construct($apiUrl, Monolog\Logger $logger)
    {
        $this->_apiUrl = $apiUrl;
        $this->_logger = $logger;
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

        $url = $this->_apiUrl . $token;

        $this->_logger->addDebug('calling GET: ' . $url);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Accept: application/json',
            )
        );

        $result       = curl_exec($ch);
        $responseCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno    = curl_errno($ch);
        $curlError    = curl_error($ch);

        if (0 !== $curlErrno) {
            $this->_logger->addError(
                'curl error (' . $curlErrno . '): ' . $curlError
            );
        }

        if ($responseCode >= 400) {
            $this->_logger->addError('curl call error: ' . $responseCode);

        } elseif ($responseCode >= 300) {
            $this->_logger->addWarning('curl call warning: ' . $responseCode);
        }

        $this->_logger->addDebug(
            'result: (' . $responseCode . ') ' . var_export($result, true)
        );

        curl_close($ch);

        return $result;
    }
}
