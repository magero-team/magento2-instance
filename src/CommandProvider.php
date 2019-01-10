<?php

namespace Magero\Composer\Magento2;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

/**
 * Class CommandProvider
 * @package Magero\Composer\Magento2
 */
class CommandProvider implements CommandProviderCapability
{
    /**
     * @return array
     */
    public function getCommands()
    {
        return [
            new Command\ModulesIntegrateCommand()
        ];
    }
}
