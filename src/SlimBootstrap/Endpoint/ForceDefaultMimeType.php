<?php
namespace SlimBootstrap\Endpoint;

/**
 * Interface ForceDefaultMimeType
 *
 * @package SlimBootstrap\Endpoint
 */
interface ForceDefaultMimeType
{
    /**
     * Accept header string to determine the default mime type for this
     * endpoint.
     *
     * @return string
     */
    public function getDefaultMimeType();
}
