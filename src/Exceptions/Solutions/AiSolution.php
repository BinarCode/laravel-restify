<?php

namespace Binaryk\LaravelRestify\Exceptions\Solutions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Prism\Prism\Prism;
use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame;
use Throwable;

class AiSolution
{
    protected mixed $solution;

    public function __construct(protected Throwable $throwable)
    {
        $cacheKey = 'restify-solutions-'.sha1(
                $this->throwable::class.
                $this->throwable->getMessage().
                $this->throwable->getFile().
                $this->throwable->getLine().
                $this->throwable->getTraceAsString()
            );

        $this->solution = Cache::remember($cacheKey,
            now()->addHour(),
            fn() => trim(Prism::text()
                ->using(config('restify.ai_solutions.provider'), config('restify.ai_solutions.model'))
                ->withSystemPrompt('You are an expert PHP/Laravel developer. Provide concise, actionable solutions in a single paragraph without line breaks, code blocks, or formatting. Your response should be suitable for JSON API responses.')
                ->withPrompt($this->generatePrompt($this->throwable))
                ->asText()
                ->text)
        );
    }

    public static function canUse(Request $request): bool
    {
        return $request->expectsJson() &&
            config('restify.ai_solutions') &&
            config('app.debug') &&
            config('prism.providers.openai.api_key');
    }

    public function getSolutionTitle(): string
    {
        return 'AI Generated Solution';
    }

    public function getSolutionDescription(): string
    {
        return $this->solution;
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
            'snippet' => collect($snippet)->map(fn($line, $number) => $number.' '.$line)->join(PHP_EOL),
            'file' => $applicationFrame->file,
            'line' => $applicationFrame->lineNumber,
            'exception' => $throwable->getMessage(),
            'docs' => $this->restifyDocs(),
        ]);
    }

    public function restifyDocs(): string
    {
        $docs = [];

        // Get Field methods
        $fieldMethods = $this->getMethodsFrom(__DIR__.'/../../Fields/Field.php');
        if ($fieldMethods) {
            $docs[] = $fieldMethods;
        }

        // Get Repository methods
        $repositoryMethods = $this->getMethodsFrom(__DIR__.'/../../Repositories/Repository.php');
        if ($repositoryMethods) {
            $docs[] = $repositoryMethods;
        }

        return implode(' | ', array_filter($docs));
    }

    protected function getMethodsFrom($filePath): string
    {
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);

        if (! file_exists($filePath)) {
            return '';
        }

        $content = file_get_contents($filePath);
        $methods = [];

        // Extract public method signatures using regex
        preg_match_all('/public\s+function\s+(\w+)\s*\([^)]*\)(?:\s*:\s*[^{]+)?\s*{/m', $content, $matches,
            PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $match) {
            $methodSignature = trim($match[0]);
            // Remove the opening brace
            $methodSignature = rtrim($methodSignature, ' {');
            $methods[] = $methodSignature;
        }

        return empty($methods) ? '' : 'Available '.$fileName.' methods: '.implode(', ', $methods);
    }
}
