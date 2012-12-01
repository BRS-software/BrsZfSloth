<?php

namespace BrsZfSlothTest;

use BrsZfSloth\Options;
use BrsZfSloth\Sloth;

/**
 * @group BrsZfSloth
 */
class SlothTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Sloth::reset();
    }

    public function testSetOptions()
    {
        $options = $this->getMock('BrsZfSloth\Options');
        Sloth::configure($options);
        $this->assertSame($options, Sloth::getOptions());
    }

    public function testDefaultOptions()
    {
        $this->assertInstanceOf('BrsZfSloth\Options', Sloth::getOptions());
    }
}