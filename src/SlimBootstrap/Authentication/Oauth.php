<?php
namespace SlimBootstrap\Authentication;

use \Monolog;
use \SlimBootstrap;
use \Slim;

/**
 * This class is reponsible for checking if the current user is authenticated
 * to call the API. It does that by validating the token parameter against the
 * given oauth API.
 *
 * @package SlimBootstrap\Authentication
 */
class Oauth implements SlimBootstrap\Authentication
{
    /**
     * URL of the oauth authentication service.
     *
     * @var string
     */
    private $_apiUrl = '';

    /**
     * @var Monolog\Logger
     */
    private $_logger = null;

    /**
     * @param string         $apiUrl URL of the oauth authentication service
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
     * @throws SlimBootstrap\Exception When the passed access $token is invalid.
     */
    public function authenticate($token)
    {
        $result = json_decode($this->_call($token), true);

        if (null === $result
            || false === array_key_exists('entity_id', $result)
        ) {
            throw new SlimBootstrap\Exception(
                'Access token invalid',
                401,
                \Slim\Log::WARN
            );
        }

        return $result['entity_id'];
    }

    /**
     * @param string $token Access token from the calling client
     *
     * @return string|false The result from the cURL call against the oauth API.
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
                . ' url: ' . $url
            );
        }

        if ($responseCode >= 500) {
            $this->_logger->addError(
                'curl call error: ' . $responseCode . ' url: ' . $url
            );
        } elseif ($responseCode >= 400) {
            if ($responseCode === 404) {
                $this->_logger->addRecord(
                    Monolog\Logger::WARNING,
                    'curl call: ' . $responseCode . ' url: ' . $url
                );
            } else {
                $this->_logger->addWarning(
                    'curl call warning: ' . $responseCode . ' url: ' . $url
                );
            }
        } elseif ($responseCode >= 300) {
            $this->_logger->addWarning(
                'curl call warning: ' . $responseCode . ' url: ' . $url
            );
        }

        $this->_logger->addDebug(
            'result: (' . $responseCode . ') ' . var_export($result, true)
        );

        curl_close($ch);

        return $result;
    }
}
