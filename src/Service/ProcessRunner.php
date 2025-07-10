<?php

namespace App\Service;

use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Process\Process;

readonly class ProcessRunner
{

    /** @noinspection PhpVoidFunctionResultUsedInspection */
    public function startProcess(array|string $script, string $outputPath, string $workdir, OutputStyle $output, bool $dryRun): Process
    {
        $errorOutput = method_exists($output, "getErrorStyle") ? $output->getErrorStyle() : $output;

        if ($dryRun && is_string($script)) {
            $process = new Process(["echo", $script]);
        } elseif ($dryRun) {
            $process = new Process(["echo", implode(" ", $script)]);
        } elseif (is_string($script)) {
            $process = Process::fromShellCommandline($script);
        } else {
            $process = new Process($script);
        }
        $process->setWorkingDirectory($workdir);
        $process->setEnv(["OUTPUT_PATH" => $outputPath]);
        $process->start(static fn ($type, $data) => match ($type) {
            Process::OUT => $output->write($data),
            default      => $errorOutput->write($data),
        });

        return $process;
    }

    public function waitProcess(Process $process, ?string $scriptName, OutputStyle $output): string
    {
        $exitCode = $process->wait();
        $duration = microtime(true) - $process->getStartTime();

        if ($scriptName !== null) {
            $message = sprintf("End script %s, exit code : %d, duration : %0.3fs", $scriptName, $exitCode, round($duration, 3));
        } else {
            $message = sprintf("Exit code : %d, duration : %0.3fs", $exitCode, round($duration, 3));
        }

        if ($exitCode === 0) {
            $output->success($message);

            return "🟢 " . $message;
        }

        $output->warning($message);

        return "🟠 " . $message;

    }
}
