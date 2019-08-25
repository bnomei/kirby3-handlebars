<?php

use Bnomei\HandlebarsTemplate;
use PHPUnit\Framework\TestCase;

class HandlebarsTemplateTest extends TestCase
{

    public function testConstruct()
    {
        $hbsT = new HandlebarsTemplate('default');
        $this->assertInstanceOf(HandlebarsTemplate::class, $hbsT);
    }

    public function testRender()
    {
        $hbsT = new HandlebarsTemplate('default');
        $render = $hbsT->render(['title' => 'Swamp', 'c' => 'Crocodile']);
        $this->assertIsString($render);
        $this->assertStringStartsWith("Swamp of <i>Crocodile</i>", $render);
    }

    public function testExtension()
    {
        $hbsT = new HandlebarsTemplate('default');
        $this->assertEquals('hbs', $hbsT->extension());
    }

    public function testFile()
    {
        $hbsT = new HandlebarsTemplate('default');
        $this->assertEquals(
            kirby()->roots()->templates() . '/default.hbs',
            $hbsT->file()
        );
    }

}
