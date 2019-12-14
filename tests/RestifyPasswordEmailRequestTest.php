<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Requests\RestifyPasswordEmailRequest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyPasswordEmailRequestTest extends IntegrationTest
{
    /** @var RestifyPasswordEmailRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RestifyPasswordEmailRequest;
    }

    public function testRules()
    {
        $this->assertEquals([
            'email' => 'required|email',
        ],
            $this->subject->rules()
        );
    }

    public function testAuthorize()
    {
        $this->assertTrue($this->subject->authorize());
    }
}
