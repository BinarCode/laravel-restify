<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Authentication;

use Binaryk\LaravelRestify\Http\Requests\RestifyLoginRequest;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyLoginRequestTest extends IntegrationTest
{
    /** @var RestifyLoginRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RestifyLoginRequest;
    }

    public function testRules()
    {
        $this->assertEquals(
            [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ],
            $this->subject->rules()
        );
    }

    public function testAuthorize()
    {
        $this->assertTrue($this->subject->authorize());
    }
}
