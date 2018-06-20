<?php
namespace Communiacs\Sw\Composer\Plugin\Core;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Semver\Constraint\EmptyConstraint;
use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;

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
     * @param IOInterface $io
     * @param Composer $composer
     * @param Filesystem $filesystem
     */
    public function __construct(IOInterface $io = null, Composer $composer = null, Filesystem $filesystem = null)
    {
        $this->io = $io;
        $this->composer = $composer;
        $this->filesystem = $this->filesystem ?: new Filesystem();
    }

    public function linkAutoLoader()
    {
        if ($this->composer->getPackage()->getName() === 'communiacs/shopware-dev') {
            $this->io->writeError('<info>Skipping SHOPWARE autoload proxy</info>', true, IOInterface::VERBOSE);
            return;
        }

        $this->io->writeError('<info>Writing SHOPWARE autoload proxy</info>', true, IOInterface::VERBOSE);

        $composerConfig = $this->composer->getConfig();
        $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $package = $localRepository->findPackage('communiacs/shopware-dev', new EmptyConstraint());


        $defaultVendorDir = Config::$defaultConfig['vendor-dir'];

        $packagePath = $this->composer->getInstallationManager()->getInstallPath($package);
        $jsonFile = new JsonFile($packagePath . DIRECTORY_SEPARATOR . 'composer.json', new RemoteFilesystem($this->io));
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
