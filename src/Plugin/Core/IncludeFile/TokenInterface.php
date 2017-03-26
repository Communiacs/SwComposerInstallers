<?php
namespace Wlwwt\Sw\Composer\Plugin\Core\IncludeFile;

interface TokenInterface
{
    /**
     * The name of the token that shall be replaced
     *
     * @return string
     */
    public function getName();

    /**
     * The content the token should be replaced with
     *
     * @return string
     */
    public function getContent();
}
