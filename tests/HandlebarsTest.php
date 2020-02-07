<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Bnomei\Handlebars;
use PHPUnit\Framework\TestCase;

class HandlebarsTest extends TestCase
{
    public function setUp(): void
    {
        kirby()->cache('bnomei.handlebars.render')->flush();
    }

    public function tearDown(): void
    {
        kirby()->cache('bnomei.handlebars.render')->flush();
    }

    public function testConstruct()
    {
        $hbs = new Handlebars();
        $this->assertInstanceOf(Handlebars::class, $hbs);

        // trigger flush
        $hbs = new Handlebars([
            'debug' => true,
        ]);
    }

    public function testOption()
    {
        $hbs = new Handlebars();
        $this->assertIsArray($hbs->option());
        $this->assertCount(5, $hbs->option());

        $hbs = new Handlebars([
            'debug' => true,
        ]);
        $this->assertFalse($hbs->option('render'));
    }

    public function testName()
    {
        $hbs = new Handlebars();
        $name = $hbs->name('/site/templates/default.' . $hbs->option('extension-input'));
        $this->assertEquals($name, 'default');
    }

    public function testFile()
    {
        $hbs = new Handlebars();

        $this->assertStringContainsString(
            '/site/templates/render-unto.' . $hbs->option('extension-input'),
            $hbs->file('render-unto')
        );
    }

    public function testPrune()
    {
        $hbs = new Handlebars();
        $data = [
            'page' => '', 'kirby' => '', 'pages' => '', 'site' => '', ['hello' => ''], 'world' => ''
        ];
        $this->assertCount(2, $hbs->prune($data));

    }

    public function testFieldsToValue()
    {
        $hbs = new Handlebars();
        $data = [
            'titleFromValue' => page('home')->title(),
            'titleFromField' => page('home')->title()->value(),
        ];
        $this->assertCount(2, $hbs->fieldsToValue($data));

    }

    public function testRead()
    {
        $hbs = new Handlebars();
        $this->assertNull($hbs->read('default', []));

        $data = ['hello' => 'world'];

        $hbs = new Handlebars(['render' => true, 'debug' => true]);
        $this->assertFalse($hbs->option('render'));
        $this->assertNull($hbs->read('default', $data));
        $this->assertFalse($hbs->write($hbs->renderCacheId(), 'hello world render'));

        $hbs = new Handlebars(['render' => true]);
        $this->assertTrue($hbs->option('render'));

        $this->assertNull($hbs->read('default', $data));
        $this->assertEquals('default-3624485329', $hbs->renderCacheId());
        $this->assertTrue($hbs->write($hbs->renderCacheId(), 'hello world render'));
        $this->assertEquals('default-3624485329', $hbs->renderCacheId());
        $this->assertEquals('hello world render', $hbs->read('default', $data));
    }

    public function testHandlebars()
    {
        $hbs = new Handlebars();
        $render = $hbs->handlebars('default', [
            'title' => 'Home',
            'c' => 'Cassia',
            'counting' => [
                ['label' => 1],
                ['label' => 2],
                ['label' => 3],
            ]
        ]);
        $this->assertStringContainsString('Home of <i>Cassia</i>.', $render);

        $render = $hbs->handlebars('call-a-partial', [
            'cake' => 'Pizza',
        ]);
        $this->assertStringContainsString('Piece of Pizza', $render);
    }

    public function testRender()
    {
        $this->setOutputCallback(function () {
        });
        $hbs = new Handlebars(['cache.render' => true]);
        // will echo
        $return = $hbs->render('default',  ['hello' => 'world'], null, null);
        $this->assertNull($return);

        $return = $hbs->render('default',  ['hello' => 'world'], null, null, true);
        $this->assertIsString($return);
    }

    public function testFlush()
    {
        $hbs = new Handlebars(['render' => true]);
        $data = ['hello' => 'world'];

        $this->assertNull($hbs->read('default', $data));
        $this->assertTrue($hbs->write($hbs->renderCacheId(), 'hello world render'));
        $this->assertEquals('hello world render', $hbs->read('default', $data));
        $hbs->flush();
        $this->assertNull($hbs->read('default', $data));
    }
}
