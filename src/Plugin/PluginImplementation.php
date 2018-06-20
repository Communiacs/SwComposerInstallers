<?php
namespace Communiacs\Sw\Composer\Plugin;

use Communiacs\Sw\Composer\Plugin\Core\AutoloadConnector;
use Composer\Composer;
use Composer\Script\Event;
use Composer\Util\Filesystem;

/**
 * Plugin implementation which
 * handles the different events
 * the plugin is subscribed to
 *
 * Class PluginImplementation
 * @package Communiacs\Sw\Composer\Plugin
 */
class PluginImplementation
{
    /**
     * @var AutoloadConnector
     */
    private $autoLoadConnector;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * PluginImplementation constructor.
     *
     * @param Event $event
     */
    public function __construct(
        Event $event
    ) {
        $fileSystem = new Filesystem();
        $this->composer = $event->getComposer();
        $this->autoLoadConnector = new AutoloadConnector($event->getIO(), $this->composer, $fileSystem);
    }

    public function preAutoloadDump()
    {
        if ($this->composer->getPackage()->getName() === 'communiacs/shopware') {
            // Nothing to do, communiacs/shopware is root package
            return;
        }
    }

    public function postAutoloadDump()
    {
        $this->autoLoadConnector->linkAutoLoader();
    }
}
