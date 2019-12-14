<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Controllers\RestResponse;

/**
 * @package Binaryk\LaravelRestify\Tests;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestResponseTest extends IntegrationTest
{
    /**
     * @var RestResponse
     */
    private $restResponse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->restResponse = new RestResponse();
    }

}
