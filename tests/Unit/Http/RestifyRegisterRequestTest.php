<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit\Http;

use Binaryk\LaravelRestify\Http\Requests\RestifyRegisterRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RestifyRegisterRequestTest extends TestCase
{
    #[Test]
    public function it_keeps_the_framework_prepare_for_validation_signature_so_app_subclasses_still_load(): void
    {
        $parent = new ReflectionMethod(FormRequest::class, 'prepareForValidation');
        $override = new ReflectionMethod(RestifyRegisterRequest::class, 'prepareForValidation');

        $this->assertSame($parent->hasReturnType(), $override->hasReturnType());
    }
}
