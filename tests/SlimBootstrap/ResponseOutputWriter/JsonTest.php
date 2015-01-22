<?php
namespace SlimBootstrap\ResponseOutputWriter;

use \SlimBootstrap\DataObject;

/**
 * Class JsonTest
 *
 * @package SlimBootstrap\ResponseOutputWriter
 */
class JsonTest extends \PHPUnit_Framework_TestCase
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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_jsonTestOutputWriter = null;

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
        $this->_mockResponse = $this->getMock(
            '\Slim\Http\Response',
            array(
                'setStatus',
                'setBody',
            )
        );
        $this->_mockHeaders = $this->getMock(
            '\Slim\Http\Headers',
            array(
                'set',
            )
        );

        $this->_jsonTestOutputWriter = $this->getMock(
            '\SlimBootstrap\ResponseOutputWriter\Json',
            array(
                '_jsonEncode',
            ),
            array(
                $this->_mockRequest,
                $this->_mockResponse,
                $this->_mockHeaders,
                'mockShortName',
            )
        );
    }

    /**
     * @dataProvider writeProvider
     */
    public function testWrite($data)
    {
        $this->_jsonTestOutputWriter
            ->expects($this->exactly(1))
            ->method('_jsonEncode')
            ->will(
                $this->returnCallback(
                    function($data) {
                        return json_encode($data);
                    }
                )
            );

        $this->_mockHeaders
            ->expects($this->exactly(1))
            ->method('set');
        $this->_mockResponse
            ->expects($this->exactly(1))
            ->method('setStatus')
            ->with($this->equalTo(200));
        $this->_mockResponse
            ->expects($this->exactly(1))
            ->method('setBody');

        $this->_jsonTestOutputWriter->write($data, 200);
    }

    /**
     * @dataProvider writeProvider
     */
    public function testWriteUnencodable($data)
    {
        $this->_jsonTestOutputWriter
            ->expects($this->exactly(1))
            ->method('_jsonEncode')
            ->will($this->returnValue(false));

        $this->_mockResponse
            ->expects($this->exactly(1))
            ->method('setStatus')
            ->with($this->equalTo(500));
        $this->_mockResponse
            ->expects($this->exactly(1))
            ->method('setBody');

        $this->_jsonTestOutputWriter->write($data, 200);
    }

    /**
     * @return array
     */
    public function writeProvider()
    {
        return array(
            array(
                'data' => new DataObject(
                    array(
                        'affiliateId' => 415,
                        'gameId'      => 14,
                    ),
                    array()
                ),
            ),
            array(
                'data' => array(),
            ),
            array(
                'data' => array(
                    new DataObject(
                        array(
                            'affiliateId' => 415,
                            'gameId'      => 14,
                        ),
                        array()
                    ),
                    new DataObject(
                        array(
                            'affiliateId' => 415,
                            'gameId'      => 14,
                        ),
                        array()
                    ),
                ),
            ),
            array(
                'data' => new DataObject(
                    array(),
                    array(
                        'welcome' => 'Welcome.',
                    ),
                    array()
                ),
            ),
        );
    }
}
