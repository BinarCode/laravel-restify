<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Http\Requests\ResetPasswordRequest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class ResetPasswordRequestTest extends IntegrationTest
{
    /** @var ResetPasswordRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ResetPasswordRequest;
    }

    public function testRules()
    {
        $this->assertEquals([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ],
            $this->subject->rules()
        );
    }

    public function testAuthorize()
    {
        $this->assertTrue($this->subject->authorize());
    }
}
