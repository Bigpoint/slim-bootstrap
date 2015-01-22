<?php
namespace SlimBootstrap\ResponseOutputWriter;

use \SlimBootstrap\DataObject;

/**
 * Class JsonHalTest
 *
 * @package Pit\Api\ResponseOutputWriter
 */
class JsonHalTest extends \PHPUnit_Framework_TestCase
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
    private $_jsonHalOutputWriter = null;

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

        $this->_jsonHalOutputWriter = $this->getMock(
            '\SlimBootstrap\ResponseOutputWriter\JsonHal',
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
     * @param string           $path
     * @param array|DataObject $data
     *
     * @dataProvider writeProvider
     */
    public function testWrite($path, $data)
    {
        $this->_jsonHalOutputWriter
            ->expects($this->exactly(1))
            ->method('_jsonEncode')
            ->will(
                $this->returnCallback(
                    function($hal) {
                        return $hal->asJson();
                    }
                )
            );

        $this->_mockRequest
            ->expects($this->exactly(1))
            ->method('getPath')
            ->will($this->returnValue($path));
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

        $this->_jsonHalOutputWriter->write($data, 200);
    }

    /**
     * @param string           $path
     * @param array|DataObject $data
     *
     * @dataProvider writeProvider
     */
    public function testWriteUnencodable($path, $data)
    {
        $this->_jsonHalOutputWriter
            ->expects($this->exactly(1))
            ->method('_jsonEncode')
            ->will($this->returnValue(false));

        $this->_mockRequest
            ->expects($this->exactly(1))
            ->method('getPath')
            ->will($this->returnValue($path));
        $this->_mockResponse
            ->expects($this->exactly(1))
            ->method('setStatus')
            ->with($this->equalTo(500));
        $this->_mockResponse
            ->expects($this->exactly(1))
            ->method('setBody');

        $this->_jsonHalOutputWriter->write($data, 200);
    }

    public function writeProvider()
    {
        $dataObject = new DataObject(
            array(
                'affiliateId' => 415,
                'gameId'      => 14,
            ),
            array(),
            array(
                'mockLinkRel' => 'mockLinkUri',
            )
        );

        return array(
            array(
                'path' => '/global-footer/415/22',
                'data' => $dataObject,
            ),
            array(
                'path' => '/global-footer/415',
                'data' => array(),
            ),
            array(
                'path' => '/global-footer',
                'data' => array(
                    $dataObject,
                    $dataObject,
                ),
            ),
        );
    }
}
