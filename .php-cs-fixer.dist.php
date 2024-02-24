<?php

declare(strict_types=1);

/*
 * This file is part of the Laravel-Strapi wrapper.
 *
 * (ɔ) Dave Blakey https://github.com/dbfx
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.md.
 */

use PhpCsFixer\Config;
use PhpCsFixer\ConfigurationException\InvalidConfigurationException;
use PhpCsFixer\Finder;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet;

$header = <<<'EOF'
    This file is part of the Laravel-Strapi wrapper.

    (ɔ) Dave Blakey https://github.com/dbfx

    This source file is subject to the MIT license that is bundled
    with this source code in the file LICENSE.md.
    EOF;

// exclude will work only for directories, so if you need to exclude file, try notPath
// directories passed as exclude() argument must be relative to the ones defined with the in() method
$finder = Finder::create()
    ->in([__DIR__])
    ->exclude(['vendor'])
    ->append([__DIR__.'/.php-cs-fixer.dist.php'])
;

$config = (new Config())
    ->setCacheFile(sys_get_temp_dir().'/.php_cs.cache')
    ->setRiskyAllowed(true)
    ->setRules([
        // https://mlocati.github.io/php-cs-fixer-configurator
        '@PHP80Migration:risky' => true,
        '@PHP82Migration' => true,
        '@PhpCsFixer' => true,
        // '@PhpCsFixer:risky' => true,
        'general_phpdoc_annotation_remove' => ['annotations' => ['expectedDeprecation']],
        'header_comment' => ['header' => $header],
    ])
    ->setFinder($finder)
;

// special handling of fabbot.io service if it's using too old PHP CS Fixer version
if (false !== getenv('FABBOT_IO')) {
    try {
        FixerFactory::create()
            ->registerBuiltInFixers()
            ->registerCustomFixers($config->getCustomFixers())
            ->useRuleSet(new RuleSet($config->getRules()))
        ;
    } catch (InvalidConfigurationException $e) {
        $config->setRules([]);
    } catch (UnexpectedValueException $e) {
        $config->setRules([]);
    } catch (InvalidArgumentException $e) {
        $config->setRules([]);
    }
}

return $config;
