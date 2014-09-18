<?php
namespace Rest\Api;

/**
 * Class DataObjectTest
 *
 * @package Rest\Api
 */
class DataObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testGetIdentifiers()
    {
        $candidate = new DataObject(
            array(
                'mockIdentifierKey' => 'mockIdentifierValue',
            ),
            array()
        );

        $expect = array(
            'mockIdentifierKey' => 'mockIdentifierValue',
        );
        $actual = $candidate->getIdentifiers();

        $this->assertEquals($expect, $actual);
    }

    public function testGetData()
    {
        $candidate = new DataObject(
            array(),
            array(
                'mockDataKey' => 'mockDataValue',
            )
        );

        $expect = array(
            'mockDataKey' => 'mockDataValue',
        );
        $actual = $candidate->getData();

        $this->assertEquals($expect, $actual);
    }

    public function testGetLinks()
    {
        $candidate = new DataObject(
            array(),
            array(),
            array(
                'mockLinkRel' => 'mockLinkUri',
            )
        );

        $expect = array(
            'mockLinkRel' => 'mockLinkUri',
        );
        $actual = $candidate->getLinks();

        $this->assertEquals($expect, $actual);
    }
}
