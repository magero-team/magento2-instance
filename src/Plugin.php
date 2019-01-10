<?php

namespace Magero\Composer\Magento2;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin as ComposerPlugin;

/**
 * Class Plugin
 * @package Magero\Composer\Magento2
 */
class Plugin implements ComposerPlugin\PluginInterface, ComposerPlugin\Capable
{
    /**
     * @inheritdoc
     */
    public function activate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * @return array
     */
    public function getCapabilities()
    {
        return [
            ComposerPlugin\Capability\CommandProvider::class => CommandProvider::class,
        ];
    }
}
