<?php
namespace Rest\Api\Response;

/**
 * Class FactoryTest
 *
 * @package Rest\Api\Response
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
     * @var \Rest\Api\Response\Factory
     */
    private $_candidate = null;

    public function setUp()
    {
        parent::setUp();

        $this->_mockRequest = $this->getMock(
            'Slim\\Http\\Request',
            array(),
            array(),
            '',
            false
        );
        $this->_mockResponse = $this->getMock('Slim\\Http\\Response');
        $this->_mockHeaders  = $this->getMock('Slim\\Http\\Headers');

        $this->_candidate = new Factory(
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
        $actual = $this->_candidate->create($acceptHeader);

        $this->assertInstanceOf($instance, $actual);
    }

    public function createSuccessProvider()
    {
        return array(
            array(
                'acceptHeader' => null,
                'instance'     => 'Rest\\Api\\Response\\JsonHal',
            ),
            array(
                'acceptHeader' => 'application/hal+json',
                'instance'     => 'Rest\\Api\\Response\\JsonHal',
            ),
            array(
                'acceptHeader' => 'application/*',
                'instance'     => 'Rest\\Api\\Response\\JsonHal',
            ),
            array(
                'acceptHeader' => '*/*',
                'instance'     => 'Rest\\Api\\Response\\JsonHal',
            ),
            array(
                'acceptHeader' => 'application/json',
                'instance'     => 'Rest\\Api\\Response\\Json',
            ),
            array(
                'acceptHeader' => 'application/json,application/hal+json',
                'instance'     => 'Rest\\Api\\Response\\JsonHal',
            ),
            array(
                'acceptHeader' => 'application/json,application/*',
                'instance'     => 'Rest\\Api\\Response\\Json',
            ),
        );
    }

    /**
     * @param string $acceptHeader
     *
     * @dataProvider createFailureProvider
     *
     * @expectedException \Rest\Api\Exception
     * @expectedExceptionCode 406
     * @expectedExceptionMessage media type not supported (supported media types: application/hal+json, application/json)
     */
    public function testCreateFailure($acceptHeader)
    {
        $this->_candidate->create($acceptHeader);
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
