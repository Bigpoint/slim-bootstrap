<?php
namespace SlimBootstrap\Endpoint;

use \SlimBootstrap\DataObject;

/**
 * Class IndexTest
 *
 * @package SlimBootstrap\Endpoint
 */
class IndexTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \SlimBootstrap\Endpoint\Index
     */
    private $_candidate = null;

    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @param array      $data
     * @param DataObject $result
     *
     * @dataProvider getProvider
     */
    public function testGet(array $data, DataObject $result)
    {
        $this->_candidate = new Index($data);

        $actual = $this->_candidate->get();

        $this->assertEquals($result, $actual);
    }

    public function getProvider()
    {
        return array(
            array(
                'data'   => array(
                    'mockUri1' => '/mockUri1',
                    'mockUri2' => '/mockUri2',
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
