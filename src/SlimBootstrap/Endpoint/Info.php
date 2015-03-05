<?php
namespace SlimBootstrap\Endpoint;

use \SlimBootstrap;

/**
 * Class Info
 *
 * @package SlimBootstrap\Endpoint
 */
class Info
{
    public function get()
    {
        $gitVersion   = $this->_getGitVersion();
        $composerData = $this->_readComposerPackageVersions(
            __DIR__ . '/../../../../../../composer.lock'
        );

        return new SlimBootstrap\DataObject(
            array(),
            array(
                'version'  => $gitVersion,
                'packages' => $composerData,
            )
        );
    }

    /**
     * Reads composer.lock and returns an array with used packages and its version.
     * When a git packages version contains dev, first characters of
     * reference will be added to version.
     *
     * @param string $composerLockPath
     *
     * @return array
     */
    private function _readComposerPackageVersions($composerLockPath)
    {
        $result = array();
        $data   = $this->_loadComposerFile($composerLockPath);

        if (false === is_array($data)
            || false === array_key_exists('packages', $data)
        ) {
            return array();
        }

        foreach ($data['packages'] as $package) {
            $versionString = $package['version'];

            if (true === array_key_exists('source', $package)
                && $package['source']['type'] === 'git'
                && false !== strpos($package['version'], 'dev')
            ) {
                $versionString .= ' (' . substr($package['source']['reference'], 0, 6) .'...)';
            }

            $packageUrl = 'https://packagist.org/';

            if (false !== strpos($package['notification-url'], 'packagist.bigpoint.net')) {
                $packageUrl = 'https://packagist.bigpoint.net/';
            }

            $packageUrl .= 'packages/' . $package['name'] . '#' . $package['version'];

            $result[$package['name']] = array(
                'version'       => $package['version'],
                'versionString' => $versionString,
                'packageUrl'    => $packageUrl,
            );
        }

        return $result;
    }

    /**
     * @param string $composerLockPath
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    protected function _loadComposerFile($composerLockPath)
    {
        return json_decode(file_get_contents($composerLockPath), true);
    }

    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    protected function _getGitVersion()
    {
        return trim(
            shell_exec(
                'git describe --tags --exact-match || git symbolic-ref -q --short HEAD'
            )
        );
    }
}
