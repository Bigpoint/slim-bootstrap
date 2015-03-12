<?php
namespace SlimBootstrap\Endpoint;

use \SlimBootstrap\DataObject;

/**
 * Class InfoTest
 *
 * @package SlimBootstrap\Endpoint
 */
class InfoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_infoEndpoint = null;

    public function setUp()
    {
        parent::setUp();

        $this->_infoEndpoint = $this->getMock(
            '\SlimBootstrap\Endpoint\Info',
            array(
                '_loadComposerFile',
                '_getGitVersion',
                '_getRepoUrl',
            )
        );
    }

    /**
     * @param array  $composerData
     * @param array  $packages
     * @param string $repoUrl
     *
     * @dataProvider getDataProvider
     */
    public function testGet(array $composerData, array $packages, $repoUrl)
    {
        $version = 'mockVersion';

        $this->_infoEndpoint
            ->expects($this->exactly(1))
            ->method('_getGitVersion')
            ->will($this->returnValue($version));
        $this->_infoEndpoint
            ->expects($this->exactly(1))
            ->method('_loadComposerFile')
            ->will($this->returnValue($composerData));
        $this->_infoEndpoint
            ->expects($this->exactly(1))
            ->method('_getRepoUrl')
            ->with($this->equalTo($version))
            ->will($this->returnValue($repoUrl));

        $expected = new DataObject(
            array(),
            array(
                'repoUrl'  => $repoUrl,
                'version'  => 'mockVersion',
                'packages' => $packages,
            )
        );
        $actual = $this->_infoEndpoint->get();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        return array(
            array(
                'composerData' => array(),
                'packages'     => array(),
                'repoUrl'      => '',
            ),
            array(
                'composerData' => array(
                    'packages' => array(),
                ),
                'packages'     => array(),
                'repoUrl'      => '',
            ),
            array(
                'composerData' => array(
                    'packages' => array(
                        array(
                            'name'             => 'mockName1',
                            'version'          => 'mockVersion1',
                            'notification-url' => '',
                        ),
                    ),
                ),
                'packages'     => array(
                    'mockName1' => array(
                        'version'       => 'mockVersion1',
                        'versionString' => 'mockVersion1',
                        'packageUrl'    => 'https://packagist.org/packages/mockName1#mockVersion1',
                    ),
                ),
                'repoUrl'      => '',
            ),
            array(
                'composerData' => array(
                    'packages' => array(
                        array(
                            'name'             => 'mockName1',
                            'version'          => 'dev-mockVersion1',
                            'notification-url' => '',
                            'source'           => array(
                                'type'      => 'git',
                                'reference' => 'abc123xxx',
                            ),
                        ),
                    ),
                ),
                'packages'     => array(
                    'mockName1' => array(
                        'version'       => 'dev-mockVersion1',
                        'versionString' => 'dev-mockVersion1 (abc123...)',
                        'packageUrl'    => 'https://packagist.org/packages/mockName1#dev-mockVersion1',
                    ),
                ),
                'repoUrl'      => '',
            ),
            array(
                'composerData' => array(
                    'packages' => array(
                        array(
                            'name'             => 'mockName1',
                            'version'          => 'mockVersion1',
                            'notification-url' => 'https://packagist.bigpoint.net/mockName1',
                        ),
                    ),
                ),
                'packages'     => array(
                    'mockName1' => array(
                        'version'       => 'mockVersion1',
                        'versionString' => 'mockVersion1',
                        'packageUrl'    => 'https://packagist.bigpoint.net/packages/mockName1#mockVersion1',
                    ),
                ),
                'repoUrl'      => 'mockUrl',
            ),
        );
    }
}
