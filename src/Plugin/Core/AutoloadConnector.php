<?php
namespace Communiacs\Sw\Composer\Plugin\Core;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Semver\Constraint\EmptyConstraint;
use Communiacs\Sw\Composer\Plugin\Util\Filesystem;

/**
 * Creates a symlink of the central autoload.php file in the vendor directory of the Shopware core package
 * If symlinking is not possible, a proxy file is created, which requires the autoload file in the vendor directory
 * Nothing is done if the composer.json of communiacs/shopware is the root.
 *
 */
class AutoloadConnector
{
    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(IOInterface $io = null, Composer $composer = null, Filesystem $filesystem = null)
    {
        $this->io = $io;
        $this->composer = $composer;
        $this->filesystem = $this->filesystem ?: new Filesystem();
    }

    public function linkAutoLoader($event = null)
    {
        if ($this->composer === null) {
            // Old plugin called this method, let's be graceful
            $this->composer = $event->getComposer();
            $this->io = $event->getIO();
            $this->io->writeError('<warning>Shopware Composer Plugin incomplete update detected.</warning>');
            $this->io->writeError('<warning>To fully upgrade to the new Shopware Composer Plugin, call "composer update" again.</warning>');
        }

        if ($this->composer->getPackage()->getName() === 'communiacs/shopware') {
            // Nothing to do communiacs/shopware is root package
            return;
        }

        $this->io->writeError('<info>Writing SHOPWARE autoload proxy</info>', true, IOInterface::VERBOSE);

        $composerConfig = $this->composer->getConfig();
        $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $package = $localRepository->findPackage('communiacs/shopware', new EmptyConstraint());


        $defaultVendorDir = \Composer\Config::$defaultConfig['vendor-dir'];

        $packagePath = $this->composer->getInstallationManager()->getInstallPath($package);
        $jsonFile = new \Composer\Json\JsonFile($packagePath . DIRECTORY_SEPARATOR . 'composer.json', new \Composer\Util\RemoteFilesystem($this->io));
        $packageJson = $jsonFile->read();
        $packageVendorDir = !empty($packageJson['config']['vendor-dir']) ? $this->filesystem->normalizePath($packageJson['config']['vendor-dir']) : $defaultVendorDir;

        $autoLoaderSourceDir = $composerConfig->get('vendor-dir');
        $autoLoaderTargetDir = "$packagePath/$packageVendorDir";
        $autoLoaderFileName = 'autoload.php';

        $this->filesystem->ensureDirectoryExists($autoLoaderTargetDir);
        $this->filesystem->remove("$autoLoaderTargetDir/$autoLoaderFileName");
        $code = [
            '<?php',
            'return require ' . $this->filesystem->findShortestPathCode(
                "$autoLoaderTargetDir/$autoLoaderFileName",
                "$autoLoaderSourceDir/$autoLoaderFileName"
            ) . ';'
        ];
        file_put_contents("$autoLoaderTargetDir/$autoLoaderFileName", implode(chr(10), $code));
    }
}
