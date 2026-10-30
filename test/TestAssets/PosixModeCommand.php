<?php

/**
 * @see       https://github.com/laminas/laminas-console for the canonical source repository
 * @copyright https://github.com/laminas/laminas-console/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-console/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Console\TestAssets {
    final class PosixModeCommand {
        public static $scenario = 'success';
    }
}
namespace Laminas\Console\Adapter {
    function exec($command, &$output, &$exitCode) {
        if ($command !== 'mode') throw new \RuntimeException('Unexpected external command in isolated fixture');
        $scenario = \LaminasTest\Console\TestAssets\PosixModeCommand::$scenario;
        $output = $scenario === 'success' ? ['  synthetic ', ' columns  '] : [];
        $exitCode = $scenario === 'failure' ? 1 : 0;
        return '';
    }
}
