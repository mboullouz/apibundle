<?php

namespace Axescloud\ApiBundle\Tests;

use Axescloud\ApiBundle\Utils\JsonSerializer;
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
    public function testJson() {
        $expected = '{"id":1,"name":"Amy"}';
        $this->assertEquals($expected, JsonSerializer::toJson(new FakeUser(1,"Amy")));
    }

    public function testGetRoutesWithPatterns() {
        $this->assertCount(2, $this->someArray, 'Array not equals');
    }

}
