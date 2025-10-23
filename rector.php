<?php

declare(strict_types=1);

use Lacasera\ElasticBridge\Rector\OrderImportsAlphabetically;
use Lacasera\ElasticBridge\Rector\OrderTraitsAlphabetically;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/workbench',
    ])
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withSets([
        LevelSetList::UP_TO_PHP_84,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        SetList::PRIVATIZATION,
        SetList::CODING_STYLE,
        SetList::NAMING,
    ])
    ->withRules([
        OrderImportsAlphabetically::class,
        OrderTraitsAlphabetically::class,
    ])
    ->withImportNames(removeUnusedImports: true)
    ->withParallel()
    ->withCache(__DIR__.'/.rector-cache');
