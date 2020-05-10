<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Authentication;

use Binaryk\LaravelRestify\Http\Requests\RestifyPasswordEmailRequest;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

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
