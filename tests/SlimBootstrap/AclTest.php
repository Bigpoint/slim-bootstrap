<?php
namespace SlimBootstrap;

/**
 * Class AclTest
 *
 * @package SlimBootstrap
 */
class AclTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \SlimBootstrap\Acl
     */
    private $_acl = null;

    public function setUp()
    {
        parent::setUp();

        $config = array(
            'roles' => array(
                'role_bppit' => array(
                    'userdata-export' => true,
                    'global-footer'   => false,
                ),
            ),
            'access' => array(
                'bppit' => 'role_bppit',
            ),
        );

        $this->_acl = new Acl($config);
    }

    public function testAccessSuccess()
    {
        $this->assertNull(
            $this->_acl->access('bppit', 'userdata-export')
        );
    }

    /**
     * @param string $clientId
     * @param string $endpointName
     *
     * @dataProvider accessFailureProvider
     *
     * @expectedException \SlimBootstrap\Exception
     * @expectedExceptionCode 403
     * @expectedExceptionMessage Access denied
     */
    public function testAccessFailure($clientId, $endpointName)
    {
        $this->_acl->access($clientId, $endpointName);
    }

    public function accessFailureProvider()
    {
        return array(
            array(
                'clientId'     => 'bppit',
                'endpointName' => 'global-footer',
            ),
            array(
                'clientId'     => '',
                'endpointName' => '',
            ),
            array(
                'clientId'     => 'bppit',
                'endpointName' => '',
            ),
            array(
                'clientId'     => '',
                'endpointName' => 'global-footer',
            ),
            array(
                'clientId'     => 'test',
                'endpointName' => 'userdata-export',
            ),
            array(
                'clientId'     => 'bppit',
                'endpointName' => 'cobrands',
            ),
        );
    }
}
