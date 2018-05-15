<?php
namespace Communiacs\Sw\Composer\Installer;


use Composer\Composer;
use Composer\Downloader\DownloadManager;
use Composer\Installer\BinaryInstaller;
use Composer\Installer\BinaryPresenceInterface;
use Composer\Installer\InstallerInterface;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Communiacs\Sw\Composer\Plugin\Config;
use Communiacs\Sw\Composer\Plugin\Util\Filesystem;

class ExtensionInstaller implements InstallerInterface, BinaryPresenceInterface
{
    /**
     * @var string
     */
    protected $extensionDir;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var DownloadManager
     */
    protected $downloadManager;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Config
     */
    protected $pluginConfig;

    /**
     * @var BinaryInstaller
     */
    protected $binaryInstaller;

    /**
     * @param IOInterface $io
     * @param Composer $composer
     * @param Filesystem $filesystem
     * @param Config $pluginConfig
     * @param BinaryInstaller $binaryInstaller
     */
    public function __construct(IOInterface $io, Composer $composer, Filesystem $filesystem, Config $pluginConfig, BinaryInstaller $binaryInstaller)
    {
        $this->composer = $composer;
        $this->downloadManager = $composer->getDownloadManager();

        $this->filesystem = $filesystem;
        $this->binaryInstaller = $binaryInstaller;
        $this->pluginConfig = $pluginConfig;
        $this->extensionDir = $this->filesystem->normalizePath($pluginConfig->get('web-dir')) . '/custom/plugins/';
    }

    /**
     * Decides if the installer supports the given type
     *
     * @param  string $packageType
     * @return bool
     */
    public function supports($packageType)
    {
        return $packageType !== 'shopware-core'
            && strncmp('shopware-', $packageType, 9) === 0;
    }

    /**
     * Checks that provided package is installed.
     *
     * @param InstalledRepositoryInterface $repo repository in which to check
     * @param PackageInterface $package package instance
     *
     * @return bool
     */
    public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        return $repo->hasPackage($package) && is_readable($this->getInstallPath($package));
    }

    /**
     * Installs specific package.
     *
     * @param InstalledRepositoryInterface $repo repository in which to check
     * @param PackageInterface $package package instance
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $downloadPath = $this->getInstallPath($package);
        // Remove the binaries if it appears the package files are missing
        if (!is_readable($downloadPath) && $repo->hasPackage($package)) {
            $this->binaryInstaller->removeBinaries($package);
        }
        $this->installCode($package);
        $this->binaryInstaller->installBinaries($package, $downloadPath);
        if (!$repo->hasPackage($package)) {
            $repo->addPackage(clone $package);
        }
    }

    /**
     * Updates specific package.
     *
     * @param InstalledRepositoryInterface $repo repository in which to check
     * @param PackageInterface $initial already installed package version
     * @param PackageInterface $target updated version
     *
     * @throws \InvalidArgumentException if $initial package is not installed
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        if (!$repo->hasPackage($initial)) {
            throw new \InvalidArgumentException('Package is not installed: ' . $initial);
        }
        $this->binaryInstaller->removeBinaries($initial);
        $this->updateCode($initial, $target);
        $this->binaryInstaller->installBinaries($target, $this->getInstallPath($target));
        $repo->removePackage($initial);
        if (!$repo->hasPackage($target)) {
            $repo->addPackage(clone $target);
        }
    }

    /**
     * Uninstalls specific package.
     *
     * @param InstalledRepositoryInterface $repo repository in which to check
     * @param PackageInterface $package
     *
     * @throws \InvalidArgumentException if $package is not installed
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if (!$repo->hasPackage($package)) {
            throw new \InvalidArgumentException('Package is not installed: ' . $package);
        }

        $this->removeCode($package);
        $this->binaryInstaller->removeBinaries($package);
        $repo->removePackage($package);
    }

    /**
     * Returns the installation path of a package
     *
     * @param PackageInterface $package
     * @return string path
     */
    public function getInstallPath(PackageInterface $package)
    {
        $extensionInstallDir = $this->resolveExtensionKey($package);
        return $this->extensionDir . DIRECTORY_SEPARATOR . $extensionInstallDir;
    }

    /**
     * Make sure binaries are installed for a given package.
     *
     * @param PackageInterface $package Package instance
     */
    public function ensureBinariesPresence(PackageInterface $package)
    {
        $this->binaryInstaller->installBinaries($package, $this->getInstallPath($package), false);
    }

    /**
     * Re-install binary by removing previous one
     *
     * @param PackageInterface $package Package instance
     */
    public function installBinary(PackageInterface $package)
    {
        $this->binaryInstaller->removeBinaries($package);
        $this->binaryInstaller->installBinaries($package, $this->getInstallPath($package));
    }

    /**
     * Resolves the extension key from replaces or package name
     *
     * @param PackageInterface $package
     * @return string
     */
    protected function resolveExtensionKey(PackageInterface $package)
    {
        foreach ($package->getReplaces() as $packageName => $version) {
            if (strpos($packageName, '/') === false) {
                $extensionKey = trim($packageName);
                break;
            }
        }
        if (empty($extensionKey)) {
            list(, $extensionKey) = explode('/', $package->getName(), 2);
            $extensionKey = str_replace('-', '_', $extensionKey);
        }
        $extra = $package->getExtra();
        if (!empty($extra)) {
            if (!empty($extra['installer-name'])) {
                $extensionKey = $extra['installer-name'];
            } elseif (!empty($extra['communiacs/shopware']['extensionKey'])) {
                $extensionKey = $extra['communiacs/shopware']['extensionKey'];
            }
        }
        return $extensionKey;
    }

    /**
     * @param PackageInterface $package
     */
    protected function installCode(PackageInterface $package)
    {
        $this->downloadManager->download($package, $this->getInstallPath($package));
    }

    /**
     * @param PackageInterface $initial
     * @param PackageInterface $target
     */
    protected function updateCode(PackageInterface $initial, PackageInterface $target)
    {
        $initialDownloadPath = $this->getInstallPath($initial);
        $targetDownloadPath = $this->getInstallPath($target);
        if ($targetDownloadPath !== $initialDownloadPath) {
            // if the target and initial dirs intersect, we force a remove + install
            // to avoid the rename wiping the target dir as part of the initial dir cleanup
            if (substr($initialDownloadPath, 0, strlen($targetDownloadPath)) === $targetDownloadPath
                || substr($targetDownloadPath, 0, strlen($initialDownloadPath)) === $initialDownloadPath
            ) {
                $this->removeCode($initial);
                $this->installCode($target);

                return;
            }

            $this->filesystem->rename($initialDownloadPath, $targetDownloadPath);
        }
        $this->downloadManager->update($initial, $target, $targetDownloadPath);
    }

    /**
     * @param PackageInterface $package
     */
    protected function removeCode(PackageInterface $package)
    {
        $this->downloadManager->remove($package, $this->getInstallPath($package));
    }
}
