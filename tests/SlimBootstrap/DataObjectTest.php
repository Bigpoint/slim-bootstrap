<?php
namespace SlimBootstrap;

/**
 * Class DataObjectTest
 *
 * @package SlimBootstrap
 */
class DataObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testGetIdentifiers()
    {
        $dataObject = new DataObject(
            array(
                'mockIdentifierKey' => 'mockIdentifierValue',
            ),
            array()
        );

        $expect = array(
            'mockIdentifierKey' => 'mockIdentifierValue',
        );
        $actual = $dataObject->getIdentifiers();

        $this->assertEquals($expect, $actual);
    }

    public function testGetData()
    {
        $dataObject = new DataObject(
            array(),
            array(
                'mockDataKey' => 'mockDataValue',
            )
        );

        $expect = array(
            'mockDataKey' => 'mockDataValue',
        );
        $actual = $dataObject->getData();

        $this->assertEquals($expect, $actual);
    }

    public function testGetLinks()
    {
        $dataObject = new DataObject(
            array(),
            array(),
            array(
                'mockLinkRel' => 'mockLinkUri',
            )
        );

        $expect = array(
            'mockLinkRel' => 'mockLinkUri',
        );
        $actual = $dataObject->getLinks();

        $this->assertEquals($expect, $actual);
    }
}
