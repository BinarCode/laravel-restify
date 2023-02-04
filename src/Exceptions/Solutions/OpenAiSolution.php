<?php

namespace Binaryk\LaravelRestify\Exceptions\Solutions;

use Illuminate\Support\Facades\Cache;
use OpenAI\Laravel\Facades\OpenAI;
use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame;
use Throwable;

class OpenAiSolution
{
    protected string $solution;

    public function __construct(protected Throwable $throwable)
    {
        $this->solution = Cache::remember('restify-solution-'.sha1($this->throwable->getTraceAsString()),
            now()->addHour(),
            fn () => OpenAI::completions()->create([
                'model' => 'text-davinci-003',
                'prompt' => $this->generatePrompt($this->throwable),
                'max_tokens' => 100,
                'temperature' => 0,
            ])->choices[0]->text
        );
    }

    public function getSolutionTitle(): string
    {
        return 'AI Generated Solution';
    }

    public function getSolutionDescription(): string
    {
        return view('restify::prompts.solution', [
            'solution' => $this->solution,
        ])->render();
    }

    public function getDocumentationLinks(): array
    {
        return [];
    }

    protected function getApplicationFrame(Throwable $throwable): ?Frame
    {
        $backtrace = Backtrace::createForThrowable($throwable)->applicationPath(base_path());
        $frames = $backtrace->frames();

        return $frames[$backtrace->firstApplicationFrameIndex()] ?? null;
    }

    protected function generatePrompt(Throwable $throwable): string
    {
        $applicationFrame = $this->getApplicationFrame($throwable);

        $snippet = $applicationFrame->getSnippet(15);

        return (string) view('restify::prompts.prompt', [
            'snippet' => collect($snippet)->map(fn ($line, $number) => $number.' '.$line)->join(PHP_EOL),
            'file' => $applicationFrame->file,
            'line' => $applicationFrame->lineNumber,
            'exception' => $throwable->getMessage(),
        ]);
    }
}
