<?php
namespace Communiacs\Sw\Composer\Installer;


use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\BinaryInstaller;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Communiacs\Sw\Composer\Plugin\Config;
use Communiacs\Sw\Composer\Plugin\PluginImplementation;
use Communiacs\Sw\Composer\Plugin\Util\Filesystem;


class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var PluginImplementation
     */
    private $pluginImplementation;

    /**
     * @var array
     */
    private $handledEvents = [];

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::PRE_AUTOLOAD_DUMP => ['listen'],
            ScriptEvents::POST_AUTOLOAD_DUMP => ['listen']
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->ensureComposerConstraints($io);
        $filesystem = new Filesystem();
        $binaryInstaller = new BinaryInstaller($io, rtrim($composer->getConfig()->get('bin-dir'), '/'), $composer->getConfig()->get('bin-compat'), $filesystem);
        $pluginConfig = Config::load($composer);
        $composer
            ->getInstallationManager()
            ->addInstaller(
                new CoreInstaller($io, $composer, $filesystem, $pluginConfig, $binaryInstaller)
            );
        $composer
            ->getInstallationManager()
            ->addInstaller(
                new ExtensionInstaller($io, $composer, $filesystem, $pluginConfig, $binaryInstaller)
            );

        $composer->getEventDispatcher()->addSubscriber($this);
    }

    /**
     * Listens to Composer events.
     *
     * This method is very minimalist on purpose. We want to load the actual
     * implementation only after updating the Composer packages so that we get
     * the updated version (if available).
     *
     * @param Event $event The Composer event.
     */
    public function listen(Event $event)
    {
        if (!empty($this->handledEvents[$event->getName()])) {
            return;
        }
        $this->handledEvents[$event->getName()] = true;
        // Plugin has been uninstalled
        if (!file_exists(__FILE__) || !file_exists(dirname(__DIR__) . '/Plugin/PluginImplementation.php')) {
            return;
        }

        // Load the implementation only after updating Composer so that we get
        // the new version of the plugin when a new one was installed
        if (null === $this->pluginImplementation) {
            $this->pluginImplementation = new PluginImplementation($event);
        }

        switch ($event->getName()) {
            case ScriptEvents::PRE_AUTOLOAD_DUMP:
                $this->pluginImplementation->preAutoloadDump();
                break;
            case ScriptEvents::POST_AUTOLOAD_DUMP:
                $this->pluginImplementation->postAutoloadDump();
                break;
        }
    }

    /**
     * @param IOInterface $io
     */
    private function ensureComposerConstraints(IOInterface $io)
    {
        if (
            !class_exists('Composer\\Installer\\BinaryInstaller')
            || !interface_exists('Composer\\Installer\\BinaryPresenceInterface')
        ) {
            $io->writeError('');
            $io->writeError(sprintf('<error>Composer version (%s) you are using is too low. Please upgrade Composer to 1.2.0 or higher!</error>',
                Composer::VERSION));
            $io->writeError('<error>Shopware installers plugin will be disabled!</error>');
            throw new \RuntimeException('Shopware Installer disabled!', 1469105842);
        }
    }
}
