<?php
namespace Communiacs\Sw\Composer\Plugin\Core\IncludeFile;


use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Communiacs\Sw\Composer\Plugin\Config as ShopwarePluginConfig;

class BaseDirToken implements TokenInterface
{
    /**
     * @var string
     */
    private $name = 'root-dir';

    /**
     * @var ShopwarePluginConfig
     */
    private $shopwarePluginConfig;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * BaseDirToken constructor.
     *
     * @param IOInterface $io
     * @param ShopwarePluginConfig $shopwarePluginConfig
     * @param Filesystem $filesystem
     */
    public function __construct(IOInterface $io, ShopwarePluginConfig $shopwarePluginConfig,  Filesystem $filesystem = null)
    {
        $this->io = $io;
        $this->shopwarePluginConfig = $shopwarePluginConfig;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getContent()
    {
        $includeFileFolder = dirname(dirname(dirname(dirname(__DIR__)))) . '/res/php';
        return $this->filesystem->findShortestPathCode(
            $includeFileFolder,
            $this->shopwarePluginConfig->getBaseDir(),
            true
        );
    }
}
