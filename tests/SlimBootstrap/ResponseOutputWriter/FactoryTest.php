<?php
namespace SlimBootstrap\ResponseOutputWriter;

/**
 * Class FactoryTest
 *
 * @package SlimBootstrap\ResponseOutputWriter
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_mockRequest = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_mockResponse = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_mockHeaders = null;

    /**
     * @var \SlimBootstrap\ResponseOutputWriter\Factory
     */
    private $_outputWriterFactory = null;

    public function setUp()
    {
        parent::setUp();

        $this->_mockRequest = $this->getMock(
            '\Slim\Http\Request',
            array(),
            array(),
            '',
            false
        );
        $this->_mockResponse = $this->getMock('\Slim\Http\Response');
        $this->_mockHeaders  = $this->getMock('\Slim\Http\Headers');

        $this->_outputWriterFactory = new Factory(
            $this->_mockRequest,
            $this->_mockResponse,
            $this->_mockHeaders,
            'mockShortName'
        );
    }

    /**
     * @param string $acceptHeader
     * @param string $instance
     *
     * @dataProvider createSuccessProvider
     */
    public function testCreateSuccess($acceptHeader, $instance)
    {
        $actual = $this->_outputWriterFactory->create($acceptHeader);

        $this->assertInstanceOf($instance, $actual);
    }

    public function createSuccessProvider()
    {
        return array(
            array(
                'acceptHeader' => null,
                'instance' => '\SlimBootstrap\ResponseOutputWriter\JsonHal',
            ),
            array(
                'acceptHeader' => 'application/hal+json',
                'instance'     => '\SlimBootstrap\ResponseOutputWriter\JsonHal',
            ),
            array(
                'acceptHeader' => 'application/*',
                'instance'     => '\SlimBootstrap\ResponseOutputWriter\JsonHal',
            ),
            array(
                'acceptHeader' => '*/*',
                'instance'     => '\SlimBootstrap\ResponseOutputWriter\JsonHal',
            ),
            array(
                'acceptHeader' => 'application/json',
                'instance'     => '\SlimBootstrap\ResponseOutputWriter\Json',
            ),
            array(
                'acceptHeader' => 'application/json,application/hal+json',
                'instance'     => '\SlimBootstrap\ResponseOutputWriter\Json',
            ),
            array(
                'acceptHeader' => 'application/json;application/*',
                'instance'     => '\SlimBootstrap\ResponseOutputWriter\Json',
            ),
        );
    }

    /**
     * @param string $acceptHeader
     *
     * @dataProvider createFailureProvider
     *
     * @expectedException \SlimBootstrap\Exception
     * @expectedExceptionCode 406
     * @expectedExceptionMessage media type not supported (supported media types: application/hal+json, application/json)
     */
    public function testCreateFailure($acceptHeader)
    {
        $this->_outputWriterFactory->create($acceptHeader);
    }

    public function createFailureProvider()
    {
        return array(
            array(
                'acceptHeader' => '',
            ),
            array(
                'acceptHeader' => 'mockHeader',
            ),
            array(
                'acceptHeader' => 'application/hal+xml',
            ),
            array(
                'acceptHeader' => 'application/xml',
            ),
        );
    }
}
