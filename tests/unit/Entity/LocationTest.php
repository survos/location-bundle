<?php

namespace App\Tests\Unit\Entity;

use DateTime;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Survos\LocationBundle\Entity\Location;


class LocationTest extends TestCase
{
    // build
    #[Test()]
    public function buildAssignsTheParametersToTheCorrectFields()
    {

        // Arrange
        $testCode = 'NC';
        $testName = 'North Carolina';
        $testLevel = 2;

        // Act
        $result = Location::build($testCode, $testName, $testLevel);

        // Assert
        $this->assertEquals($testCode, $result->getCode());

        $location = new Location($testCode, $testName, $testLevel);
        $this->assertEquals($result, $location);
    }

}
