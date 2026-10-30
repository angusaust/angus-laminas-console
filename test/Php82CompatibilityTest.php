<?php

/**
 * @see       https://github.com/laminas/laminas-console for the canonical source repository
 * @copyright https://github.com/laminas/laminas-console/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-console/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Console;

use Laminas\Console\Getopt;
use Laminas\Console\RouteMatcher\DefaultRouteMatcher;
use LaminasTest\Console\TestAssets\ConsoleAdapter;
use PHPUnit\Framework\TestCase;

class Php82CompatibilityTest extends TestCase
{
    public function testLatin1ConversionAndSubstitutionPreserveHistoricalBytes()
    {
        $adapter = new ConsoleAdapter();
        $adapter->setTestUtf8(true);
        $this->assertSame("caf\xc3\xa9", $adapter->encodeText("caf\xe9"));
        $this->assertSame("caf\xc3\xa9", $adapter->encodeText("caf\xc3\xa9"));
        $adapter->setTestUtf8(false);
        $previous = mb_substitute_character();
        try {
            mb_substitute_character(0xfffd);
            $this->assertSame("caf\xe9", $adapter->encodeText("caf\xc3\xa9"));
            $this->assertSame('?', $adapter->encodeText("\xe2\x82\xac"));
            $this->assertSame(0xfffd, mb_substitute_character());
            $this->assertSame("\xff", $adapter->encodeText("\xff"));
        } finally {
            mb_substitute_character($previous);
        }
    }

    public function testGetoptXmlRetainsLatin1Conversion()
    {
        $options = new Getopt(['name=s' => 'Name'], ['--name=caf' . "\xe9"]);
        $document = new \DOMDocument();
        $this->assertTrue($document->loadXML($options->toXml()));
        $option = $document->getElementsByTagName('option')->item(0);
        $this->assertSame('name', $option->getAttribute('flag'));
        $this->assertSame("caf\xc3\xa9", $option->getAttribute('parameter'));
    }

    public function testHistoricalPublicConstraintsRemainMutableAndEnforced()
    {
        $matcher = new DefaultRouteMatcher('--id=', ['id' => '/^\\d+$/']);
        $this->assertTrue((new \ReflectionProperty($matcher, 'constraints'))->isPublic());
        $this->assertSame(['id' => '12'], $matcher->match(['--id=12']));
        $this->assertNull($matcher->match(['--id=no']));
        $matcher->constraints = ['id' => '/^[a-z]+$/'];
        $this->assertSame(['id' => 'yes'], $matcher->match(['--id=yes']));
    }
    
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPosixModeResultIsDeclaredForAllCommandOutcomes()
    {
        require __DIR__ . '/TestAssets/PosixModeCommand.php';
        $adapter = new \Laminas\Console\Adapter\Posix();
        $property = new \ReflectionProperty($adapter, 'modeResult');
        $this->assertTrue($property->isDefault());
        $this->assertTrue($property->isPublic());
        $this->assertNull($property->getType());
        $this->assertFalse(isset($adapter->modeResult));
        $run = new \ReflectionMethod($adapter, 'runModeCommand');
        $previous = set_error_handler(function ($level, $message) {
            if ($level === E_DEPRECATED && strpos($message, 'Creation of dynamic property') !== false) {
                throw new \ErrorException($message, 0, $level);
            }
            return false;
        });
        try {
            foreach (['success' => 'synthetic  columns', 'failure' => '', 'empty' => ''] as $scenario => $expected) {
                \LaminasTest\Console\TestAssets\PosixModeCommand::$scenario = $scenario;
                $run->invoke($adapter);
                $this->assertSame($expected, $adapter->modeResult);
            }
        } finally {
            restore_error_handler();
        }
    }
}
