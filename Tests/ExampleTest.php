<?php

namespace Axescloud\ApiBundle\Tests;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase {

    private $someArray;

    public function setUp() {
        $this->someArray = [1, 2];
    }

    public function testGetRoutes() {
        $expected = [1, 2];
        $this->assertEquals($expected, $this->someArray);
    }

    public function testGetRoutesWithPatterns() {
        $this->assertCount(2, $this->someArray, 'Array not equals');
    }

}
