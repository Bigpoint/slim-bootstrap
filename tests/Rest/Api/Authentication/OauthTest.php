<?php
namespace Rest\Api\Authentication;

/**
 * Class AuthenticationTest
 *
 * @package Rest\Api\Authentication
 */
class OauthTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_candidate = null;

    public function setUp()
    {
        parent::setUp();

        $this->_candidate = $this->getMock(
            '\Rest\Api\Authentication\Oauth',
            array('_call'),
            array('mockApiUrl')
        );
    }

    public function testAuthenticateValid()
    {
        $this->_candidate->expects($this->exactly(1))
            ->method('_call')
            ->with('mockToken')
            ->will(
                $this->returnValue(
                    '{"entity_id":"mockClientId","_links":{"self":{"href":"https://staging-2-api.bigpoint.com/clients/bppit"}}}'
                )
            );

        $actual = $this->_candidate->authenticate('mockToken');

        $this->assertEquals('mockClientId', $actual);
    }

    /**
     * @dataProvider authenticationInvalidProvider
     *
     * @expectedException \Rest\Api\Exception
     * @expectedExceptionCode 401
     * @expectedExceptionMessage Access token invalid
     */
    public function testAuthenticationInvalid($returnValue)
    {
        $this->_candidate->expects($this->exactly(1))
            ->method('_call')
            ->with('mockToken')
            ->will($this->returnValue($returnValue));

        $this->_candidate->authenticate('mockToken');
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
