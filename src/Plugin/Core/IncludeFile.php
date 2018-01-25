<?php
namespace Communiacs\Sw\Composer\Plugin\Core;


use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Communiacs\Sw\Composer\Plugin\Core\IncludeFile\TokenInterface;

class IncludeFile
{
    const RESOURCES_PATH = '/res/php';
    const INCLUDE_FILE = '/autoload-include.php';
    const INCLUDE_FILE_TEMPLATE = '/autoload-include.tmpl.php';

    /**
     * @var TokenInterface[]
     */
    private $tokens;

    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var IOInterface
     */
    private $io;
    /**
     * @var Composer
     */
    private $composer;

    /**
     * IncludeFile constructor.
     *
     * @param TokenInterface[] $tokens
     * @param Filesystem $filesystem
     */
    public function __construct(IOInterface $io, Composer $composer, array $tokens, Filesystem $filesystem = null)
    {
        $this->io = $io;
        $this->composer = $composer;
        $this->tokens = $tokens;
        $this->filesystem = $this->filesystem ?: new Filesystem();
    }

    public function register()
    {
        $this->io->writeError('<info>Register communiacs/shopware-composer-installer file in root package autoload definition</info>', true, IOInterface::VERBOSE);

        // Generate and write the file
        $includeFile = $this->filesystem->normalizePath(dirname(dirname(dirname(__DIR__))) . self::RESOURCES_PATH . '/' . self::INCLUDE_FILE);
        file_put_contents($includeFile, $this->getIncludeFileContent());

        // Register the file in the root package
        $rootPackage = $this->composer->getPackage();
        $autoloadDefinition = $rootPackage->getAutoload();
        $autoloadDefinition['files'][] = $includeFile;
        $rootPackage->setAutoload($autoloadDefinition);

        // Load it to expose the paths to further plugin functionality
        require $includeFile;
    }

    /**
     * Constructs the include file content
     *
     * @return string
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function getIncludeFileContent()
    {
        $includeFileTemplate = $this->filesystem->normalizePath(dirname(dirname(dirname(__DIR__))) . self::RESOURCES_PATH . '/' . self::INCLUDE_FILE_TEMPLATE);
        $includeFileContent = file_get_contents($includeFileTemplate);
        foreach ($this->tokens as $token) {
            $includeFileContent = self::replaceToken($token->getName(), $token->getContent(), $includeFileContent);
        }
        return $includeFileContent;
    }

    /**
     * Replaces a token in the subject (PHP code)
     *
     * @param string $name
     * @param string $content
     * @param string $subject
     * @return string
     */
    private static function replaceToken($name, $content, $subject)
    {
        return str_replace('\'{$' . $name . '}\'', $content, $subject);
    }
}
