<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Http\Requests\RestifyRegisterRequest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyRegisterRequestTest extends IntegrationTest
{
    /** @var RestifyRegisterRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RestifyRegisterRequest;
    }

    public function testRules()
    {
        $this->assertEquals([
            'email' => 'required|email|max:255|unique:'.config('restify.auth.table', 'users'),
            'password' => 'required|confirmed|min:6',
        ],
            $this->subject->rules()
        );
    }

    public function testAuthorize()
    {
        $this->assertTrue($this->subject->authorize());
    }
}
