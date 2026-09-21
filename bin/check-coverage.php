<?php

declare(strict_types=1);

/**
 * Fail (non-zero exit) when total Clover coverage is below a threshold.
 *
 * Uses the blended "elements" metric (methods + statements + conditionals):
 * 100% elements means every method, line and branch is covered.
 *
 * Usage: php bin/check-coverage.php <clover.xml> [min-percent=100]
 */
$cloverPath = $argv[1] ?? 'build/logs/clover.xml';
$min = (float) ($argv[2] ?? 100);

if (! is_file($cloverPath)) {
    fwrite(STDERR, "Coverage report not found: {$cloverPath}\n");

    exit(1);
}

$xml = simplexml_load_file($cloverPath);

if ($xml === false || ! isset($xml->project->metrics)) {
    fwrite(STDERR, "Could not parse Clover report: {$cloverPath}\n");

    exit(1);
}

$metrics = $xml->project->metrics;
$elements = (int) $metrics['elements'];
$covered = (int) $metrics['coveredelements'];
$percent = $elements > 0 ? ($covered / $elements) * 100 : 100.0;

printf("Coverage: %.2f%% (%d/%d elements)\n", $percent, $covered, $elements);

if ($percent + 1e-9 < $min) {
    fwrite(STDERR, sprintf("FAIL: coverage %.2f%% is below the required %.2f%%.\n", $percent, $min));

    exit(1);
}

echo "PASS\n";
