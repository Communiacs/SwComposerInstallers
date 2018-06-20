<?php
namespace Communiacs\Sw\Composer\Installer;

use Communiacs\Sw\Composer\Plugin\Config;
use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;

/**
 * Installs the shopware package
 * inside the configured web directory
 * and keeps files that are already there
 *
 * Class CoreInstaller
 * @package Communiacs\Sw\Composer\Installer
 */
class CoreInstaller extends LibraryInstaller
{

    /**
     * Shopware installation directory
     *
     * @var string
     */
    protected $installDir;

    /**
     * Composer exclude directories
     *
     * @var array
     */
    protected $composerExcludes;

    public function __construct(
        IOInterface $io,
        Composer $composer,
        Config $pluginConfig
    ) {
        parent::__construct($io, $composer, 'shopware-core');
        $this->installDir = $this->filesystem->normalizePath($pluginConfig->get('web-dir'));
        $this->composerExcludes = $pluginConfig->get('exclude-from-composer');
    }

    /**
     * Returns the installation path of a package
     *
     * @param PackageInterface $package
     * @return string path
     */
    public function getInstallPath(PackageInterface $package)
    {
        return $this->installDir;
    }

    /**
     * Installs the code
     * @param PackageInterface $package
     */
    protected function installCode(PackageInterface $package)
    {
        $this->io->writeError('<info>Shopware Installer: Installing the code</info>', true, IOInterface::VERBOSE);

        $backupDir = $this->installDir . '_backup';

        $this->filesystem->ensureDirectoryExists($backupDir);
        // backup files
        foreach($this->composerExcludes as $file){
            $from = $this->installDir . '/' . $file;
            $to = $backupDir . '/' . $file;

            $this->io->writeError('<info>Shopware Installer: Install - Backup ' . $from .' to ' . $to . '</info>', true, IOInterface::VERY_VERBOSE);

            $this->moveComposerExcludes($from, $to);
        }

        parent::installCode($package);

        // restore files
        foreach($this->composerExcludes as $file){
            $from = $backupDir . '/' . $file;
            $to = $this->installDir . '/' . $file;

            $this->io->writeError('<info>Shopware Installer: Install - Restore ' . $from .' to ' . $to . '</info>', true, IOInterface::VERY_VERBOSE);

            $this->moveComposerExcludes($from, $to);
        }

        $this->rmdir($backupDir);
    }

    /**
     * Updates the code
     *
     * @param PackageInterface $initial
     * @param PackageInterface $target
     */
    protected function updateCode(PackageInterface $initial, PackageInterface $target)
    {
        $this->io->writeError('<info>Shopware Installer: Updating the code</info>', true, IOInterface::VERBOSE);

        parent::updateCode($initial, $target);
    }

    /**
     * Moves composer excludes
     * from source to destination
     *
     * @param $source
     * @param $dest
     */
    protected function moveComposerExcludes($source, $dest) {
        if( is_file($source)){
            copy($source, $dest);
            unlink($source);
        } else if(is_dir($source)) {
            if(is_dir($dest)) {
                $this->rmdir($dest);
            }
            mkdir($dest, 0777, true);
            $dir = new \DirectoryIterator($source);
            foreach($dir as $fileInfo){
                if(!$fileInfo->isDot()) {
                    // recursive call because of subdirs
                    $this->moveComposerExcludes($source . '/' . $fileInfo->getFilename(), $dest . '/' . $fileInfo->getFilename());
                }
            }
            $this->rmdir($source);
        }
    }

    /**
     * Remove directory
     * @param $dir
     */
    protected function rmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir."/".$object))
                        $this->rmdir($dir."/".$object);
                    else
                        unlink($dir."/".$object);
                }
            }
            rmdir($dir);
        }
    }
}