<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class RestifyRequestForBulkRowTest extends IntegrationTestCase
{
    private const array ROWS = [
        ['title' => 'First', 'tags' => ['a', 'b']],
        ['title' => 'Second', 'tags' => ['c']],
    ];

    #[Test]
    public function it_narrows_a_json_body_to_the_row_keyed_by_its_index(): void
    {
        $request = $this->jsonRequest();

        $seen = $request->forBulkRow(1, static fn (): array => [
            $request->payload(),
            $request->all(),
            $request->input('1.title'),
            $request->input('0.title'),
            $request->input('1.tags.0'),
        ]);

        $this->assertSame([
            [1 => self::ROWS[1]],
            [1 => self::ROWS[1], 'page' => '2'],
            'Second',
            null,
            'c',
        ], $seen);
    }

    #[Test]
    public function it_narrows_a_form_body_to_the_row_keyed_by_its_index(): void
    {
        $request = RestifyRequest::create('/bulk?page=2', 'POST', self::ROWS);

        $seen = $request->forBulkRow(0, static fn (): array => [$request->payload(), $request->input('page')]);

        $this->assertSame([[0 => self::ROWS[0]], '2'], $seen);
    }

    #[Test]
    public function it_narrows_the_uploaded_files_to_the_row(): void
    {
        $first = UploadedFile::fake()->image('first.jpg');
        $second = UploadedFile::fake()->image('second.jpg');

        $request = RestifyRequest::create('/bulk', 'POST', self::ROWS, files: [
            0 => ['image' => $first],
            1 => ['image' => $second],
        ]);

        $this->assertCount(2, $request->allFiles());

        $seen = $request->forBulkRow(1, static fn (): array => [
            $request->file('1.image')?->getClientOriginalName(),
            $request->file('0.image'),
            array_keys($request->allFiles()),
        ]);

        $this->assertSame(['second.jpg', null, [1]], $seen);
        $this->assertSame([0, 1], array_keys($request->allFiles()));
    }

    #[Test]
    public function it_gives_a_row_missing_from_the_body_an_empty_body(): void
    {
        $request = $this->jsonRequest();

        $this->assertSame([], $request->forBulkRow(5, static fn (): array => $request->payload()));
    }

    #[Test]
    public function it_keeps_the_same_row_when_nested(): void
    {
        $request = $this->jsonRequest();

        $seen = $request->forBulkRow(1, static fn (): array => $request->forBulkRow(1, static fn (): array => $request->payload()));

        $this->assertSame([1 => self::ROWS[1]], $seen);
        $this->assertSame(self::ROWS, $request->payload());
    }

    #[Test]
    public function it_restores_the_whole_body_after_the_callback(): void
    {
        $request = $this->jsonRequest();

        $request->forBulkRow(0, static fn (): null => null);

        $this->assertSame(self::ROWS, $request->payload());
        $this->assertSame(self::ROWS, $request->json()->all());
        $this->assertSame('First', $request->input('0.title'));
        $this->assertSame('Second', $request->input('1.title'));
    }

    #[Test]
    public function it_restores_the_whole_body_when_the_callback_throws(): void
    {
        $request = $this->jsonRequest();

        try {
            $request->forBulkRow(0, static fn (): never => throw new RuntimeException('Row failed'));
            $this->fail('The row exception was swallowed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Row failed', $exception->getMessage());
        }

        $this->assertSame(self::ROWS, $request->payload());
    }

    #[Test]
    public function it_discards_a_merge_made_inside_the_row(): void
    {
        $request = $this->jsonRequest();

        $request->forBulkRow(0, static function () use ($request): void {
            $request->merge(['merged' => true]);
        });

        $this->assertSame(self::ROWS, $request->payload());
        $this->assertNull($request->input('merged'));
    }

    private function jsonRequest(): RestifyRequest
    {
        return RestifyRequest::create(
            '/bulk?page=2',
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(self::ROWS, JSON_THROW_ON_ERROR),
        );
    }
}
