<?php
namespace SlimBootstrap\ResponseOutputWriter;

use \SlimBootstrap\DataObject;

/**
 * Class JsonTest
 *
 * @package SlimBootstrap\ResponseOutputWriter
 */
class CsvTest extends \PHPUnit_Framework_TestCase
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
     * @var \PHPUnit_Framework_MockObject_MockObject|CSV
     */
    private $_csvTestOutputWriter = null;

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

        $this->_csvTestOutputWriter = $this->getMock(
            '\SlimBootstrap\ResponseOutputWriter\Csv',
            array(),
            array(
                $this->_mockRequest,
                $this->_mockResponse,
                $this->_mockHeaders,
                'mockShortName',
            )
        );
    }

    /**
     * @codeCoverageIgnore
     * @dataProvider writeProvider
     * broken
     */
    public function testWrite($data)
    {
        $this->_mockHeaders
            ->expects($this->exactly(1))
            ->method('set');
//        $this->_mockResponse
//            ->expects($this->exactly(1))
//            ->method('setStatus')
//            ->with($this->equalTo(200));
        $this->_mockResponse
            ->expects($this->exactly(1))
            ->method('setBody');

        $this->_csvTestOutputWriter->write($data, 200);
    }

    /**
     * @dataProvider writeProvider
     */
    public function testWriteUnencodable($data)
    {
        $this->_csvTestOutputWriter
            ->expects($this->exactly(1))
            ->method('_csvEncode')
            ->will($this->returnValue(false));

        $this->_mockResponse
            ->expects($this->exactly(1))
            ->method('setStatus')
            ->with($this->equalTo(500));
        $this->_mockResponse
            ->expects($this->exactly(1))
            ->method('setBody');

        $this->_csvTestOutputWriter->write($data, 200);
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
                            'id' => 9,
                            'game_Id'      => 4,
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


    /**
     * @return array
     */
    public function csvDataProvider()
    {
        return array(
            array(
                "invalid",
                false,
                false,
            ),
            array(
                array(),
                false,
                false,
            ),
            array(
                array(
                    "skipme because i am not an array",
                    array(
                        "useme" => "and abuse me",
                        "foo"   => "bar",
                    )
                ),
                "# useme,foo\r\n"
                . "\"and abuse me\",\"bar\"",
                "# useme,foo\r\n"
                . "\"and abuse me\",bar",
            ),
            array(
                array(
                    array("nullkey" => null)
                ),
                "# nullkey\r\nNULL",
                "# nullkey\r\nNULL",
            ),
            array(
                array(
                    array("linebreaktest" => "linebreak\ntest")
                ),
                "# linebreaktest\r\n"
                . "\"linebreak\ntest\"",
                "# linebreaktest\r\n"
                . "\"linebreak\ntest\"",
            ),
        );
    }

    /**
     * @param array     $data                         Data to test with
     * @param string    $assertionEnclosed            Expected result w/ forced enclosure
     * @param string    $assertionEnclosedOnDemand    Expected result w/o forced enclosure
     *
     * @dataProvider    csvDataProvider
     */
    public function testCSVencode($data, $assertionEnclosed, $assertionEnclosedOnDemand){
        $method = new \ReflectionMethod('\SlimBootstrap\ResponseOutputWriter\Csv', '_csvEncode');
        $method->setAccessible(true);

//        $instance           = $this->getMock(
//            '\SlimBootstrap\ResponseOutputWriter\Csv',
//            array(),
//            array(
//                $this->_mockRequest,
//                $this->_mockResponse,
//                $this->_mockHeaders,
//                'mockCSVTest',
//            )
//        );
        $enclosed           = $method->invoke($this->_csvTestOutputWriter, $data, true);
        $enclosedOnDemand   = $method->invoke($this->_csvTestOutputWriter, $data, false);
        $this->assertEquals($assertionEnclosed, $enclosed);
        $this->assertEquals($assertionEnclosedOnDemand, $enclosedOnDemand);
    }

    /**
     * @return array
     */
    public function arrayFlattenDataProvider(){
        return array(
            array(
                array(
                    "key"=> "value",
                    "foo" => array(
                        "test" => "test1234",
                        "bar" => "foobar",
                        "foo2" => array(
                            "test" => "test1234",
                            "bar" => "foobar",
                        ),
                    ),
                ),
                array(
                    "foo_test" => "test1234",
                    "foo_bar" => "foobar",
                    "foo_foo2_test" => "test1234",
                    "foo_foo2_bar" => "foobar",
                    "key" => "value"
                ),
            ),
            array(
                array(
                    "someDataObject" => new DataObject(
                        array(),
                        array(
                            "test" => "test1234",
                            "bar" => "foobar",
                        )
                    )
                ),
                array(
                    "someDataObject_test" => "test1234",
                    "someDataObject_bar" => "foobar",
                ),
            ),
            array(
                array(
                    "someDataObject" => new DataObject(
                        array(),
                        array(
                            "test" => "test1234",
                            "anotherDO" => new DataObject(
                                array(
                                    "dummyId" => 1
                                ),
                                array(
                                    "foo" => "bar",
                                )
                            )
                        )
                    )
                ),
                array(
                    "someDataObject_test" => "test1234",
                    "someDataObject_anotherDO_dummyId" => 1,
                    "someDataObject_anotherDO_foo" => "bar",
                ),
            ),
        );
    }

    /**
     * @param array     $data       Data to test with
     * @param string    $assertion  Expected result
     *
     * @dataProvider    arrayFlattenDataProvider
     */
    public function test_flatten($data, $assertion){
        $method = new \ReflectionMethod('\SlimBootstrap\ResponseOutputWriter\Csv', '_flatten');
        $method->setAccessible(true);

        $instance           = $this->getMock(
            '\SlimBootstrap\ResponseOutputWriter\Csv',
            array(),
            array(
                $this->_mockRequest,
                $this->_mockResponse,
                $this->_mockHeaders,
                'mockCSVTest',
            )
        );

        $enclosed = $method->invoke($instance, $data);
        $this->assertEquals($assertion, $enclosed);
    }
}
