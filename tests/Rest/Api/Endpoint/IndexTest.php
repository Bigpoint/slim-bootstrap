<?php
namespace Rest\Api\Endpoint;

use \Rest\Api\DataObject;

/**
 * Class IndexTest
 *
 * @package Rest\Api\Endpoint
 */
class IndexTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Rest\Api\Endpoint\Index
     */
    private $_candidate = null;

    public function setUp()
    {
        parent::setUp();

        $this->_candidate = new Index();
    }

    /**
     * @param array      $data
     * @param DataObject $result
     *
     * @dataProvider getProvider
     */
    public function testGet(array $data, DataObject $result)
    {
        $this->_candidate->setdata($data);

        $actual = $this->_candidate->get();

        $this->assertEquals($result, $actual);
    }

    public function getProvider()
    {
        return array(
            array(
                'data'   => array(
                    array(
                        'name' => 'mockUri1',
                    ),
                    array(
                        'name' => 'mockUri2',
                    ),
                ),
                'result' => new DataObject(
                    array(),
                    array(
                        'welcome' => 'Welcome.',
                    ),
                    array(
                        'mockUri1' => '/mockUri1',
                        'mockUri2' => '/mockUri2',
                    )
                ),
            ),
        );
    }
}
