<?php
namespace Wlwwt\Sw\Composer\Plugin\Core;

use Composer\Autoload\ClassLoader;
use Composer\IO\IOInterface;
use Composer\Script\Event;

class ScriptDispatcher
{
    /**
     * @var Event
     */
    private $event;

    /**
     * @var ClassLoader
     */
    private $loader;

    /**
     * ScriptDispatcher constructor.
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function executeScripts()
    {
        if (is_callable(['Wlwwt\\Sw\\Core\\Composer\\InstallerScripts', 'setupShopware'])) {
            $this->event->getIO()->writeError('<info>Executing wlwwt/shopware package scripts</info>', true, IOInterface::VERBOSE);
            $this->registerLoader();
            \Wlwwt\Sw\Core\Composer\InstallerScripts::setupShopware($this->event);
            $this->unRegisterLoader();
        }
    }

    private function registerLoader()
    {
        $composer = $this->event->getComposer();
        $package = $composer->getPackage();
        $generator = $composer->getAutoloadGenerator();
        $packages = $composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $packageMap = $generator->buildPackageMap($composer->getInstallationManager(), $package, $packages);
        $map = $generator->parseAutoloads($packageMap, $package);
        $this->loader = $generator->createLoader($map);
        $this->loader->register();
    }

    private function unRegisterLoader()
    {
        $this->loader->unregister();
    }
}
