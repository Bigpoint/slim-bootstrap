<?php
namespace SlimBootstrap\Authentication;

/**
 * Class FactoryTest
 *
 * @package SlimBootstrap\Authentication
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_mockLogger = null;

    /**
     * @var \SlimBootstrap\Authentication\Factory
     */
    private $_authenticationFactory = null;

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

        $this->_authenticationFactory = new Factory(
            array(
                'authenticationUrl' => 'mockApiUrl',
            ),
            $this->_mockLogger
        );
    }

    public function testCreateOauth()
    {
        $actual = $this->_authenticationFactory->createOauth();

        $this->assertInstanceOf(
            '\SlimBootstrap\Authentication\Oauth',
            $actual
        );
    }
}
