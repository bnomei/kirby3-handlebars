<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Bnomei\LncFile;
use Kirby\Toolkit\F;
use PHPUnit\Framework\TestCase;

class LncFileTest extends TestCase
{
    private $target;
    private $default;

    public function setUp(): void
    {
        $this->target = kirby()->roots()->cache() . '/plugins/bnomei/handlebars/lnc/default.lnc';
        F::write($this->target, 'tmp');

        $this->default = kirby()->roots()->templates() . '/default.hbs';
    }

    public function tearDown(): void
    {
        F::remove($this->target);
    }

    public function testConstruct()
    {
        $file = new LncFile([
            'source' => '',
        ]);
        $this->assertInstanceOf(LncFile::class, $file);
    }

    public function testRead()
    {
        $file = new LncFile([
            'partial' => false,
            'name' => 'default',
            'source' => $this->default,
            'modified' => F::modified($this->default),
        ]);
        $this->assertStringStartsWith("{{ title }} of <i>{{ c }}</i>.", $file->hbs());
        $this->assertFalse($file->needsUpdate());
        $this->assertFalse($file->partial());
        $this->assertEquals('default', $file->name());
        $this->assertEquals(F::modified($this->default), $file->modified());
        $this->assertNull($file->php());

        F::remove($this->target);
        $file = new LncFile([
            'source' => $this->default,
            'target' => $this->target,
            'modified' => F::modified($this->default),
        ]);
        $this->assertTrue($file->needsUpdate());
    }

    public function testPhp()
    {
        $file = new LncFile([
            'source' => $this->default,
            'target' => $this->target,
            'modified' => F::modified($this->default),
            'partial' => false,
            'name' => 'default',
            'lnc' => true,
        ]);
        $this->assertEquals('tmp', $file->php());

        $file->php('hello world');
        $this->assertEquals('hello world', $file->php());

        $php = F::read($this->target);
        $file->php($php);
        $this->assertEquals($php, $file->php());

        F::remove($this->target);

        $file = new LncFile([
            'source' => $this->default,
            'target' => $this->target,
            'modified' => F::modified($this->default),
        ]);
        $this->assertNull($file->php());
    }

    public function testHbs()
    {
        $file = new LncFile([
            'source' => $this->default,
            'modified' => F::modified($this->default),
        ]);

        $this->assertStringStartsWith("{{ title }} of <i>{{ c }}</i>.", $file->hbs());
    }

    public function testToArray()
    {
        $file = new LncFile([
            'source' => $this->default,
            'modified' => F::modified($this->default),
        ]);
        $this->assertIsArray($file->toArray());
        $this->assertCount(3, $file->toArray());
    }

    public function testModified()
    {
        F::write($this->default, F::read($this->default)); // aka touch
        $file = new LncFile([
            'source' => $this->default,
            'target' => $this->target,
            'modified' => F::modified($this->default) - 50,
        ]);
        $this->assertTrue($file->needsUpdate());
    }

    public function testNotModified()
    {
        F::write($this->target, 'tmp');
        $file = new LncFile([
            'source' => $this->default,
            'target' => $this->target,
            'modified' => F::modified($this->default),
        ]);
        $this->assertFalse($file->needsUpdate());
    }

    public function testTargetFound()
    {
        $this->assertFileExists($this->target);
        $this->assertEquals('tmp', F::read($this->target));

        $file = new LncFile([
            'source' => $this->default,
            'target' => $this->target,
            'modified' => F::modified($this->default),
            'lnc' => true,
        ]);

        $php = F::read($this->target);
        $this->assertEquals($php, $file->php());
    }
}
