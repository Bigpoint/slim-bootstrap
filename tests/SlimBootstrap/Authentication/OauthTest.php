<?php
namespace SlimBootstrap\Authentication;

/**
 * Class AuthenticationTest
 *
 * @package SlimBootstrap\Authentication
 */
class OauthTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_mockLogger = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_mockOauth = null;

    public function setUp()
    {
        parent::setUp();

        $this->_mockLogger = $this->getMock(
            '\Monolog\Logger',
            array(),
            array(),
            '',
            false
        );

        $this->_mockOauth = $this->getMock(
            '\SlimBootstrap\Authentication\Oauth',
            array('_call'),
            array('mockApiUrl', $this->_mockLogger)
        );
    }

    public function testAuthenticateValid()
    {
        $this->_mockOauth->expects($this->exactly(1))
            ->method('_call')
            ->with('mockToken')
            ->will(
                $this->returnValue(
                    '{"entity_id":"mockClientId","_links":{"self":{"href":"https://staging-2-api.bigpoint.com/clients/bppit"}}}'
                )
            );

        $actual = $this->_mockOauth->authenticate('mockToken');

        $this->assertEquals('mockClientId', $actual);
    }

    /**
     * @dataProvider authenticationInvalidProvider
     *
     * @expectedException \SlimBootstrap\Exception
     * @expectedExceptionCode 401
     * @expectedExceptionMessage Access token invalid
     */
    public function testAuthenticationInvalid($returnValue)
    {
        $this->_mockOauth->expects($this->exactly(1))
            ->method('_call')
            ->with('mockToken')
            ->will($this->returnValue($returnValue));

        $this->_mockOauth->authenticate('mockToken');
    }

    public function authenticationInvalidProvider()
    {
        return array(
            array(
                'returnValue' => '',
            ),
            array(
                'returnValue' => '{}',
            ),
            array(
                'returnValue' => 'mockReturn',
            ),
            array(
                'returnValue' => null,
            ),
            array(
                'returnValue' => false,
            ),
        );
    }
}
