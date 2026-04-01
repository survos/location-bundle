<?php


namespace Survos\LocationBundle\Service;

use GuzzleHttp\Promise\Promise;
use SplFileObject;

interface ImportInterface
{

    /**
     * @author Chris Bednarczyk <chris@tourradar.com>
     */
    public function import(string $filePath, ?callable $progress = null): bool;


}
