<?php
namespace Wlwwt\Sw\Composer\Plugin\Core\IncludeFile;

use Composer\IO\IOInterface;
use Wlwwt\Sw\Composer\Plugin\Config as PluginConfig;

class ComposerModeToken implements TokenInterface
{
    /**
     * @var string
     */
    private $name = 'composer-mode';

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var PluginConfig
     */
    private $pluginConfig;

    /**
     * WebDirToken constructor.
     *
     * @param IOInterface $io
     * @param PluginConfig $pluginConfig
     */
    public function __construct(IOInterface $io, PluginConfig $pluginConfig)
    {
        $this->io = $io;
        $this->pluginConfig = $pluginConfig;
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
        if (!$this->pluginConfig->get('composer-mode')) {
            return 'Shopware is installed via composer, but for development reasons the additional class loader is activated. Handle with care!';
        }

        $this->io->writeError('<info>Inserting SHOPWARE_COMPOSER_MODE constant</info>', true, IOInterface::VERBOSE);

        return <<<COMPOSER_MODE
Shopware is installed via composer. Flag this with a constant.
if (!defined('SHOPWARE_COMPOSER_MODE')) {
    define('SHOPWARE_COMPOSER_MODE', TRUE);
}
COMPOSER_MODE;
    }
}
