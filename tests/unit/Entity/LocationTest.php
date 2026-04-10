<?php

declare(strict_types=1);

namespace Survos\LocationBundle\Tests\Unit\Entity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Survos\LocationBundle\Entity\Location;

class LocationTest extends TestCase
{
    #[Test]
    public function buildAssignsTheParametersToTheCorrectFields()
    {
        $testCode = 'NC';
        $testName = 'North Carolina';
        $testLevel = 2;

        $result = Location::build($testCode, $testName, $testLevel);

        $this->assertSame($testCode, $result->getCode());

        $location = new Location($testCode, $testName, $testLevel);
        $this->assertEquals($result, $location);
    }
}
