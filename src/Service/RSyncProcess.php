<?php

namespace App\Service;

use App\DTO\BackupFolder;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

readonly class RSyncProcess
{
    public function __construct(
        private ProcessRunner $processRunner,
    ) {
    }

    public function execute(BackupFolder $backup, OutputStyle $output, bool $dryRun): string
    {
        $output->title("Start copy");

        $this->buildFileList($backup);
        $output->note("Included files list is in " . $backup->includedListPath);
        $output->note("Excluded files list is in " . $backup->excludedListPath);

        $command = [
            "rsync",
            "--archive",
            "--verbose",
            "--files-from=" . $backup->includedListPath,
            $backup->from,
            $backup->to,
        ];

        $process = $this->processRunner->startProcess($command, "", $output, $dryRun);
        return $this->processRunner->waitProcess($process, null, $output);

    }

    private function buildFileList(BackupFolder $backup): void
    {
        $ignoreRules = array_merge($backup->ignore, [
            "#^\.backups/(log|excluded|included|summary)\.txt$#"
        ]);

        $baseFinder = Finder::create()
            ->files()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->ignoreVCSIgnored(false)
            ->in($backup->from)
        ;

        $excluded = [];

        $finder = clone $baseFinder;
        foreach ($ignoreRules as $ignoreRule) {
            $finder->notPath($ignoreRule);

            $excluded[] = "### $ignoreRule";
            foreach ((clone $baseFinder)->path($ignoreRule) as $file) {
                $excluded[] = $file->getRelativePathname();
            }
            $excluded[] = "";
        }

        $included = [];
        foreach ($finder as $file) {
            $included[] = $file->getRelativePathname();
        }

        file_put_contents($backup->includedListPath, implode("\n", $included));
        file_put_contents($backup->excludedListPath, implode("\n", $excluded));
    }
}
