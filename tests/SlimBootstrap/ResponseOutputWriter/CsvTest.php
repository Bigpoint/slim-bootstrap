<?php
namespace SlimBootstrap\ResponseOutputWriter;

use \SlimBootstrap\DataObject;

/**
 * Class JsonTest
 *
 * @package SlimBootstrap\ResponseOutputWriter
 * @runTestsInSeparateProcesses
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
     * @var \PHPUnit_Framework_MockObject_MockObject
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

        $this->_csvTestOutputWriter = new Csv(
            $this->_mockRequest,
            $this->_mockResponse,
            $this->_mockHeaders,
            'mockShortName'
        );
    }

    /**
     * @dataProvider writeOneProvider
     */
    public function testWriteOne($data)
    {
        $localCsvTestOutputWriter = $this->getMock(
            '\SlimBootstrap\ResponseOutputWriter\Csv',
            array(
                '_normalizeAll',
                '_normalizeOne',
                '_buildStructure',
                '_csvEncode',
            ),
            array(
                $this->_mockRequest,
                $this->_mockResponse,
                $this->_mockHeaders,
                'mockShortName',
            )
        );
        $localCsvTestOutputWriter
            ->expects($this->once())
            ->method('_normalizeOne')
            ->will($this->returnValue($data));

        $localCsvTestOutputWriter
            ->expects($this->never())
            ->method('_normalizeAll');

        $localCsvTestOutputWriter
            ->expects($this->once())
            ->method('_buildStructure')
            ->will($this->returnValue($data));

        $this->_mockHeaders
            ->expects($this->once())
            ->method('set')
            ->with(
                $this->identicalTo("Content-Type"),
                $this->identicalTo("text/csv; charset=UTF-8")
            );

        $this->_mockResponse
            ->expects($this->once())
            ->method('setStatus')
            ->with($this->equalTo(200));


        $localCsvTestOutputWriter
            ->expects($this->once())
            ->method('_csvEncode');

        $this->_mockResponse
            ->expects($this->once())
            ->method('setBody');

        $localCsvTestOutputWriter->write($data, 200);
    }


    /**
     * @dataProvider normalizeAllDataProvider
     */
    public function testWriteArray($data)
    {
        $localCsvTestOutputWriter = $this->getMock(
            '\SlimBootstrap\ResponseOutputWriter\Csv',
            array(
                '_normalizeAll',
                '_normalizeOne',
                '_buildStructure',
                '_csvEncode',
            ),
            array(
                $this->_mockRequest,
                $this->_mockResponse,
                $this->_mockHeaders,
                'mockShortName',
            )
        );

        $localCsvTestOutputWriter
            ->expects($this->never())
            ->method('_normalizeOne');

        $localCsvTestOutputWriter
            ->expects($this->once())
            ->method('_normalizeAll')
            ->will($this->returnValue($data));

        $localCsvTestOutputWriter
            ->expects($this->exactly(\count($data)))
            ->method('_buildStructure')
            ->will($this->returnValue($data));

        $this->_mockHeaders
            ->expects($this->once())
            ->method('set')
            ->with(
                $this->identicalTo("Content-Type"),
                $this->identicalTo("text/csv; charset=UTF-8")
            );

        $this->_mockResponse
            ->expects($this->once())
            ->method('setStatus')
            ->with($this->equalTo(200));


        $localCsvTestOutputWriter
            ->expects($this->once())
            ->method('_csvEncode');

        $this->_mockResponse
            ->expects($this->once())
            ->method('setBody');

        $localCsvTestOutputWriter->write($data, 200);
    }

    /**
     * @expectedException \SlimBootstrap\CSVEncodingException
     * @expectedExceptionMessage Expected DataObject, NULL given.
     */
    public function testWriteUnencodable()
    {
        $this->_csvTestOutputWriter->write(null, 200);
    }

    /**
     * @return array
     */
    public function writeOneProvider()
    {
        return
            array(
                array(
                    new DataObject(
                        array(
                            'affiliateId' => 415,
                            'gameId'      => 14,
                        ),
                        array()
                    ),
                    array(),
                    "",
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
    public function testCsvEncode($data, $assertionEnclosed, $assertionEnclosedOnDemand){
        $method = new \ReflectionMethod(
            '\SlimBootstrap\ResponseOutputWriter\Csv',
            '_csvEncode'
        );
        $method->setAccessible(true);

        $enclosed           = $method->invoke($this->_csvTestOutputWriter, $data, true);
        $enclosedOnDemand   = $method->invoke($this->_csvTestOutputWriter, $data, false);
        $this->assertEquals($assertionEnclosed, $enclosed);
        $this->assertEquals($assertionEnclosedOnDemand, $enclosedOnDemand);
    }

    /**
     * @return array
     */
    public function csvFailureDataProvider()
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

        );
    }

    /**
     * @param array     $data                         Data to test with
     *
     * @dataProvider    csvFailureDataProvider
     * @expectedException \SlimBootstrap\CSVEncodingException
     */
    public function testCsvEncodeFailure($data){
        $method = new \ReflectionMethod(
            '\SlimBootstrap\ResponseOutputWriter\Csv',
            '_csvEncode'
        );
        $method->setAccessible(true);

        $method->invoke($this->_csvTestOutputWriter, $data);
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
        $method = new \ReflectionMethod(
            '\SlimBootstrap\ResponseOutputWriter\Csv',
            '_flatten'
        );
        $method->setAccessible(true);

        $enclosed = $method->invoke($this->_csvTestOutputWriter, $data);
        $this->assertEquals($assertion, $enclosed);
    }


    /**
     * @return array
     */
    public function normalizeOneDataProvider(){
        return array(
            array(
                new DataObject(
                    array(
                        "id" => "dummy"
                    ),
                    array(
                        "foo" => "bar"
                    )
                ),
                array(
                    "foo",
                    "test",
                ),
                new DataObject(
                    array(
                        "id" => "dummy",
                    ),
                    array(
                        "foo" => "bar",
                        "test" => null,
                    )
                ),
            ),
            array(
                new DataObject(
                    array(
                        "id" => "dummy",
                    ),
                    array(
                        "foo" => "bar",
                    )
                ),
                array(
                    "foo",
                ),
                new DataObject(
                    array(
                        "id" => "dummy",
                    ),
                    array(
                        "foo" => "bar",
                    )
                ),
            ),
            array(
                new DataObject(
                    array(
                        "id" => "dummy",
                    ),
                    array(
                        "foo" => "bar",
                    )
                ),
                null,
                new DataObject(
                    array(
                        "id" => "dummy",
                    ),
                    array(
                        "foo" => "bar",
                    )
                ),
            ),
            array(
                new DataObject(
                    array(
                        "foo" => "bar",
                    ),
                    array()
                ),
                null,
                new DataObject(
                    array(
                        "foo" => "bar",
                    ),
                    array(
                    )
                ),
            ),
            array(
                new DataObject(
                    array(
                        "foo" => "bar",
                    ),
                    array()
                ),
                array(),
                new DataObject(
                    array(
                        "foo" => "bar",
                    ),
                    array(
                    )
                ),
            ),
        );
    }

    /**
     * @param \SlimBootstrap\DataObject     $data       Data to test with
     * @param array     $keys       Data to test with
     * @param \SlimBootstrap\DataObject    $assertion  Expected result
     *
     * @dataProvider    normalizeOneDataProvider
     */
    public function test_normalizeOne($data, $keys, $assertion){
        $method = new \ReflectionMethod(
            '\SlimBootstrap\ResponseOutputWriter\Csv',
            '_normalizeOne'
        );
        $method->setAccessible(true);

        $enclosed = $method->invoke($this->_csvTestOutputWriter, $data, $keys);
        $this->assertEquals($assertion, $enclosed);
    }

    /**
     * @return array
     */
    public function normalizeAllDataProvider(){
        return array(
            array(
                array(
                    new DataObject(
                        array(
                            "id" => "dummy",
                        ),
                        array(
                            "foo" => "bar",
                        )
                    ),
                    new DataObject(
                        array(
                            "id" => "dummy",
                        ),
                        array(
                            "test" => "bar",
                        )
                    ),
                ),
                array(
                    new DataObject(
                        array(
                            "id" => "dummy",
                        ),
                        array(
                            "foo" => "bar",
                            "test" => null,
                        )
                    ),
                    new DataObject(
                        array(
                            "id" => "dummy",
                        ),
                        array(
                            "foo" => null,
                            "test" => "bar",
                        )
                    ),
                ),
            ),
            array(
                array(
                    new DataObject(
                        array(
                            'affiliateId' => 1,
                            'gameId'      => 1,
                        ),
                        array()
                    ),
                    new DataObject(
                        array(
                            'affiliateId' => 2,
                            'gameId'      => 2,
                        ),
                        array()
                    ),
                ),
                array(
                    new DataObject(
                        array(
                            'affiliateId' => 1,
                            'gameId'      => 1,
                        ),
                        array()
                    ),
                    new DataObject(
                        array(
                            'affiliateId' => 2,
                            'gameId'      => 2,
                        ),
                        array()
                    ),
                ),
            ),
        );
    }

    /**
     * @param \SlimBootstrap\DataObject     $data       Data to test with
     * @param \SlimBootstrap\DataObject    $assertion  Expected result
     *
     * @dataProvider    normalizeAllDataProvider
     */
    public function test_normalizeAll($data, $assertion){
        $method = new \ReflectionMethod(
            '\SlimBootstrap\ResponseOutputWriter\Csv',
            '_normalizeAll'
        );
        $method->setAccessible(true);

        $enclosed = $method->invoke($this->_csvTestOutputWriter, $data);
        $this->assertEquals($assertion, $enclosed);
    }

    /**
     * @return array
     */
    public function normalizeAllFailureDataProvider(){
        return array(
            array(
                array(
                    new DataObject(
                        array(
                            "id" => "dummy",
                        ),
                        array(
                            "foo" => "bar",
                        )
                    ),
                    new DataObject(
                        array(
                            "dummy" => "id",
                        ),
                        array(
                            "test" => "bar",
                        )
                    ),
                ),
            ),
        );
    }

    /**
     * @param \SlimBootstrap\DataObject     $data       Data to test with
     *
     * @dataProvider    normalizeAllFailureDataProvider
     * @expectedException \SlimBootstrap\CSVEncodingException
     * @expectedExceptionMessage Different identifiers!
     */
    public function test_normalizeAllFailure($data){
        $method = new \ReflectionMethod(
            '\SlimBootstrap\ResponseOutputWriter\Csv',
            '_normalizeAll'
        );
        $method->setAccessible(true);

        $method->invoke($this->_csvTestOutputWriter, $data);
    }

    /**
     * @return array
     */
    public function dataSetToLineMalformedPayloadDataProvider(){
        return array(
            array(
                array(
                    array(
                        "Malformed" => "payload!"
                    ),
                ),
            ),
        );
    }

    /**
     * @param \SlimBootstrap\DataObject     $data       Data to test with
     *
     * @dataProvider    dataSetToLineMalformedPayloadDataProvider
     * @expectedException \SlimBootstrap\CSVEncodingException
     * @expectedExceptionMessage Malformed payload!
     */
    public function test_dataSetToLineMalformedPayload($data){
        $method = new \ReflectionMethod(
            '\SlimBootstrap\ResponseOutputWriter\Csv',
            '_dataSetToLine'
        );
        $method->setAccessible(true);

        $method->invoke($this->_csvTestOutputWriter, $data);
    }
    /**
     * @return array
     */
    public function buildStructureDataProvider(){
        return array(
            array(
                new DataObject(
                    array(
                        "id" => "dummy",
                    ),
                    array(
                        "foo" => "bar",
                    )
                ),
                array(
                    array(
                        "identifier_id" => "dummy",
                        "foo" => "bar",
                    )
                )
            ),
        );
    }

    /**
     * @param \SlimBootstrap\DataObject     $data       Data to test with
     * @param array     $expected       Data to test with
     *
     * @dataProvider    buildStructureDataProvider
     */
    public function test_buildStructure(DataObject $data, $expected){
        $method = new \ReflectionMethod(
            '\SlimBootstrap\ResponseOutputWriter\Csv',
            '_buildStructure'
        );
        $method->setAccessible(true);

        $result = array();
        $arguments = array(
            $data,
            $data->getIdentifiers(),
            0,
            &$result
        );
        $method->invokeArgs($this->_csvTestOutputWriter, $arguments);
        $this->assertEquals($expected, $result);
    }
}
