<?php
namespace Rest\Api\ResponseOutputWriter;

use \Rest\Api\DataObject;

/**
 * Class JsonTest
 *
 * @package Rest\Api\ResponseOutputWriter
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
     * @var \Rest\Api\ResponseOutputWriter\Json
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

        $this->_candidate = new Json(
            $this->_mockRequest,
            $this->_mockResponse,
            $this->_mockHeaders,
            'mockShortName'
        );
    }

    /**
     * @dataProvider writeProvider
     */
    public function testWrite($data)
    {
        $this->_mockHeaders->expects($this->exactly(1))->method('set');
        $this->_mockResponse->expects($this->exactly(1))->method('setStatus');
        $this->_mockResponse->expects($this->exactly(1))->method('setBody');

        $this->_candidate->write($data, 200);
    }

    /**
     * @return array
     */
    public function writeProvider()
    {
        $dataObject = new DataObject(
            array(
                'affiliateId' => 415,
                'gameId'      => 14,
            ),
            array()
        );

        return array(
            array(
                'data' => $dataObject,
            ),
            array(
                'data' => array(),
            ),
            array(
                'data' => array(
                    $dataObject,
                    $dataObject,
                ),
            ),
        );
    }
}
