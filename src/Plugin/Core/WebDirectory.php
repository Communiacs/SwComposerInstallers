<?php
namespace Communiacs\Sw\Composer\Plugin\Core;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Semver\Constraint\EmptyConstraint;
use Communiacs\Sw\Composer\Plugin\Config;
use Communiacs\Sw\Composer\Plugin\Util\Filesystem;

/**
 * Shopware Core installer
 *
 */
class WebDirectory
{
    const SHOPWARE_DIR = 'backend';
    const SHOPWARE_INDEX_PHP = 'index.php';

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var array
     */
    private $symlinks = [];

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Config
     */
    private $pluginConfig;

    /**
     * @param IOInterface $io
     * @param Composer $composer
     * @param Filesystem $filesystem
     * @param Config $pluginConfig
     */
    public function __construct(IOInterface $io, Composer $composer, Filesystem $filesystem, Config $pluginConfig)
    {
        $this->io = $io;
        $this->composer = $composer;
        $this->filesystem = $filesystem;
        $this->pluginConfig = $pluginConfig;
    }

    public function ensureSymlinks()
    {
        $this->initializeSymlinks();
        if ($this->filesystem->someFilesExist($this->symlinks)) {
            $this->filesystem->removeSymlinks($this->symlinks);
        }
        $this->filesystem->establishSymlinks($this->symlinks, false);
    }

    /**
     * Initialize symlinks with configuration
     */
    private function initializeSymlinks()
    {
        if ($this->composer->getPackage()->getName() === 'communiacs/shopware') {
            // Nothing to do communiacs/shopware is root package
            return;
        }
        $this->io->writeError('<info>Establishing links to Shopware entry scripts in web directory</info>', true, IOInterface::VERBOSE);

        $webDir = $this->filesystem->normalizePath($this->pluginConfig->get('web-dir'));
        $this->filesystem->ensureDirectoryExists($webDir);
        $sourcesDir = $this->determineInstallPath();
        //       $backendDir = $webDir . DIRECTORY_SEPARATOR . self::SHOPWARE_DIR;
//        $this->symlinks = [
//            $sourcesDir . DIRECTORY_SEPARATOR . self::SHOPWARE_INDEX_PHP
//                => $webDir . DIRECTORY_SEPARATOR . self::SHOPWARE_INDEX_PHP,
//            $sourcesDir . DIRECTORY_SEPARATOR . self::SHOPWARE_DIR
//                => $backendDir
//        ];
    }

    private function determineInstallPath()
    {
        $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $package = $localRepository->findPackage('communiacs/shopware', new EmptyConstraint());
        return $this->composer->getInstallationManager()->getInstallPath($package);
    }
}
