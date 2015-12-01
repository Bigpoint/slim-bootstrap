<?php
namespace SlimBootstrap\Endpoint;

use \SlimBootstrap;
use \Slim;

/**
 * Interface Streamable
 *
 * @package SlimBootstrap\Endpoint
 */
interface Streamable
{
    /**
     * @param SlimBootstrap\ResponseOutputWriterStreamable $outputWriter
     */
    public function setOutputWriter(
        SlimBootstrap\ResponseOutputWriterStreamable $outputWriter
    );
}
