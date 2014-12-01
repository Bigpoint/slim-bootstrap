<?php
namespace Rest\Api\ResponseOutputWriter;

use \Rest\Api\DataObject;

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
     * @var \Rest\Api\ResponseOutputWriter\JsonHal
     */
    private $_candidate = null;

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

        $this->_candidate = new JsonHal(
            $this->_mockRequest,
            $this->_mockResponse,
            $this->_mockHeaders,
            'mockShortName'
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
        $this->_mockRequest
            ->expects($this->exactly(1))
            ->method('getPath')
            ->will($this->returnValue($path));
        $this->_mockHeaders->expects($this->exactly(1))->method('set');
        $this->_mockResponse->expects($this->exactly(1))->method('setStatus');
        $this->_mockResponse->expects($this->exactly(1))->method('setBody');

        $this->_candidate->write($data, 200);
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
