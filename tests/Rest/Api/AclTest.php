<?php
namespace Rest\Api;

/**
 * Class AclTest
 *
 * @package Rest\Api
 */
class AclTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Rest\Api\Acl
     */
    private $_candidate = null;

    public function setUp()
    {
        parent::setUp();

        $config = new \stdClass();
        $config->{'roles'} = new \stdClass();
        $config->{'roles'}->{'role_bppit'} = new \stdClass();
        $config->{'roles'}->{'role_bppit'}->{'userdata-export'} = true;
        $config->{'roles'}->{'role_bppit'}->{'global-footer'} = false;
        $config->{'access'} = new \stdClass();
        $config->{'access'}->{'bppit'} = 'role_bppit';

        $this->_candidate = new Acl($config);
    }

    public function testAccessSuccess()
    {
        $this->assertNull(
            $this->_candidate->access('bppit', 'userdata-export')
        );
    }

    /**
     * @param string $clientId
     * @param string $endpointName
     *
     * @dataProvider accessFailureProvider
     *
     * @expectedException \Rest\Api\Exception
     * @expectedExceptionCode 403
     * @expectedExceptionMessage Access denied
     */
    public function testAccessFailure($clientId, $endpointName)
    {
        $this->_candidate->access($clientId, $endpointName);
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
