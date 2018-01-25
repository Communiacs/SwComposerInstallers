<?php
namespace Communiacs\Sw\Composer\Plugin;

use Composer\Composer;
use Composer\Script\Event;
use Communiacs\Sw\Composer\Plugin\Config as PluginConfig;
use Communiacs\Sw\Composer\Plugin\Core\AutoloadConnector;
use Communiacs\Sw\Composer\Plugin\Core\IncludeFile;
use Communiacs\Sw\Composer\Plugin\Core\IncludeFile\BaseDirToken;
use Communiacs\Sw\Composer\Plugin\Core\IncludeFile\ComposerModeToken;
use Communiacs\Sw\Composer\Plugin\Core\IncludeFile\WebDirToken;
use Communiacs\Sw\Composer\Plugin\Core\ScriptDispatcher;
use Communiacs\Sw\Composer\Plugin\Core\WebDirectory;
use Communiacs\Sw\Composer\Plugin\Util\Filesystem;

class PluginImplementation
{
    /**
     * @var ScriptDispatcher
     */
    private $scriptDispatcher;

    /**
     * @var IncludeFile
     */
    private $includeFile;

    /**
     * @var AutoloadConnector
     */
    private $autoLoadConnector;

    /**
     * @var WebDirectory
     */
    private $webDirectory;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * PluginImplementation constructor.
     *
     * @param Event $event
     * @param ScriptDispatcher $scriptDispatcher
     * @param IncludeFile $includeFile
     * @param AutoloadConnector $autoLoadConnector
     */
    public function __construct(
        Event $event,
        ScriptDispatcher $scriptDispatcher = null,
        WebDirectory $webDirectory = null,
        IncludeFile $includeFile = null,
        AutoloadConnector $autoLoadConnector = null
    ) {
        $io = $event->getIO();
        $this->composer = $event->getComposer();
        $fileSystem = new Filesystem();
        $pluginConfig = PluginConfig::load($this->composer);

        $this->scriptDispatcher = $scriptDispatcher ?: new ScriptDispatcher($event);
        $this->autoLoadConnector = $autoLoadConnector ?: new AutoloadConnector($io, $this->composer, $fileSystem);
        $this->webDirectory = $webDirectory ?: new WebDirectory($io, $this->composer, $fileSystem, $pluginConfig);
        $this->includeFile = $includeFile
            ?: new IncludeFile(
                $io,
                $this->composer,
                [
                    new BaseDirToken($io, $pluginConfig),
                    new WebDirToken($io, $pluginConfig),
                    new ComposerModeToken($io, $pluginConfig),
                ],
                $fileSystem
            );
    }

    public function preAutoloadDump()
    {
        if ($this->composer->getPackage()->getName() === 'communiacs/shopware') {
            // Nothing to do, communiacs/shopware is root package
            return;
        }
        $this->includeFile->register();
    }

    public function postAutoloadDump()
    {
        $this->autoLoadConnector->linkAutoLoader();
        $this->webDirectory->ensureSymlinks();
        $this->scriptDispatcher->executeScripts();
    }
}
