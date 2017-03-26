<?php
namespace Wlwwt\Sw\Composer\Plugin\Core\IncludeFile;

/*
 * This file was taken from the typo3 console plugin package.
 * (c) Helmut Hummel <info@helhum.io>
 *
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Wlwwt\Sw\Composer\Plugin\Config as ShopwarePluginConfig;

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
