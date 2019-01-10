<?php

namespace Magero\Composer\Magento2\Command;

use Symfony\Component\Console;
use Composer\Command\BaseCommand;

/**
 * Class ModulesIntegrateCommand
 * @package Magero\Composer\Magento2\Command
 */
class ModulesIntegrateCommand extends BaseCommand
{
    const ARGUMENT_JSON_FILES = 'json_files';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('modules-integrate')
            ->setDescription('Add modules to Magento');

        $this->addArgument(
            self::ARGUMENT_JSON_FILES,
            Console\Input\InputArgument::REQUIRED,
            'List of json files composer info will be read from'
        );
    }

    /**
     * @inheritdoc
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $jsonFiles = array_filter(
            array_map(function($value) {
                $value = trim($value);
                $value = trim($value, DIRECTORY_SEPARATOR);
                return $value;
            }, explode(',', $input->getArgument(self::ARGUMENT_JSON_FILES)))
        );
        $rootPath = rtrim($this->getComposer()->getConfig()->get('vendor-dir'), 'vendor');
        $magentoPath =  $rootPath . 'magento' . DIRECTORY_SEPARATOR;
        $mainJsonFile = $magentoPath . 'composer.json';
        $mainJsonFileData = json_decode(file_get_contents($mainJsonFile), true);
        $hasChanges = false;
        foreach ($jsonFiles as $jsonFile) {
            $filePath = $magentoPath . $jsonFile;
            $jsonDirectory = dirname($jsonFile) . DIRECTORY_SEPARATOR;
            if (!is_readable($filePath)) {
                throw new Console\Exception\InvalidArgumentException('Json file not found: ' . $filePath);
            }
            $fileData = json_decode(file_get_contents($filePath), true);
            if (!empty($fileData['require'])) {
                foreach ($fileData['require'] as $package => $version) {
                    if (!array_key_exists($package, $mainJsonFileData['require'])) {
                        $mainJsonFileData['require'][$package] = $version;
                    }
                }
            }
            if (!empty($fileData['autoload'])) {
                foreach ($fileData['autoload'] as $type => $items) {
                    if (!in_array($type, ['psr-4', 'psr-0', 'files', 'classmap', 'exclude-from-classmap'])) {
                        continue;
                    }
                    if (!array_key_exists($type, $mainJsonFileData['autoload'])) {
                        $mainJsonFileData['autoload'][$type] = [];
                    }
                    if (substr($type, 0, 3) === 'psr') {
                        foreach ($items as $namespace => $path) {
                            if (is_array($path)) {
                                if (!isset($mainJsonFileData['autoload'][$type][$namespace])) {
                                    $mainJsonFileData['autoload'][$type][$namespace] = [];
                                } elseif (!is_array($mainJsonFileData['autoload'][$type][$namespace])) {
                                    $mainJsonFileData['autoload'][$type][$namespace] = [$mainJsonFileData['autoload'][$type][$namespace]];
                                }

                                foreach ($path as $subPath) {
                                    if (!in_array($jsonDirectory . $subPath, $mainJsonFileData['autoload'][$type][$namespace])) {
                                        $hasChanges = true;
                                        $mainJsonFileData['autoload'][$type][$namespace][] = $jsonDirectory . $subPath;
                                    }
                                }
                            } else {
                                $jsonDirectoryPath = $jsonDirectory . $path;
                                if (!isset($mainJsonFileData['autoload'][$type][$namespace])) {
                                    $hasChanges = true;
                                    $mainJsonFileData['autoload'][$type][$namespace] = $jsonDirectoryPath;
                                } elseif (is_array($mainJsonFileData['autoload'][$type][$namespace]) &&
                                    !in_array($jsonDirectoryPath, $mainJsonFileData['autoload'][$type][$namespace])
                                ) {
                                    $hasChanges = true;
                                    $mainJsonFileData['autoload'][$type][$namespace][] = $jsonDirectoryPath;
                                } elseif ($mainJsonFileData['autoload'][$type][$namespace] != $jsonDirectoryPath) {
                                    $hasChanges = true;
                                    $mainJsonFileData['autoload'][$type][$namespace] = [
                                        $mainJsonFileData['autoload'][$type][$namespace],
                                        $jsonDirectoryPath
                                    ];
                                }
                            }
                        }
                    } else {
                        foreach ($items as $path) {
                            if (($type === 'files') && ($path === 'registration.php')) {
                                continue;
                            }
                            if (!in_array($jsonDirectory . $path, $mainJsonFileData['autoload'][$type])) {
                                $hasChanges = true;
                                $mainJsonFileData['autoload'][$type][] = $jsonDirectory . $path;
                            }
                        }
                    }
                }
            }
        }
        if ($hasChanges) {
            file_put_contents($mainJsonFile, json_encode(
                    $mainJsonFileData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                ) . PHP_EOL);
        }

        $output->writeln('Modules have been integrated to Magento');
    }
}
