<?php

namespace App\Service;

use App\DTO\BackupFolder;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

readonly class RSyncProcess
{
    public function __construct(
        private ProcessRunner $processRunner,
    ) {
    }

    public function execute(BackupFolder $backup, OutputStyle $output, bool $dryRun): string
    {
        $output->title('Start copy');

        $this->buildFileList($backup);
        $output->note('Included files list is in '.$backup->includedListPath);
        $output->note('Excluded files list is in '.$backup->excludedListPath);

        new Filesystem()->mkdir($backup->to);

        $command = [
            'rsync',
            '--archive',
            '--verbose',
            '--files-from='.$backup->includedListPath,
            $backup->from,
            $backup->to,
        ];

        $workdir = getcwd() ?: throw new \RuntimeException("Can't determine current working directory");
        $process = $this->processRunner->startProcess($command, '', $workdir, $output, $dryRun);

        return $this->processRunner->waitProcess($process, null, $output);
    }

    private function buildFileList(BackupFolder $backup): void
    {
        $ignoreRules = array_merge($backup->ignorePattern, [
            "#^\.backups/(log|excluded|included|summary)\.txt$#",
        ]);

        $baseFinder = Finder::create()
            ->files()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->ignoreVCSIgnored(false)
            ->in($backup->from)
            ->filter(fn (SplFileInfo $file) => !in_array($file->getRelativePathName(), $backup->ignoreFolder), true)
        ;

        $excluded = [];

        foreach ($backup->ignoreFolder as $ignoreFolder) {
            $excluded[] = "### Folder $ignoreFolder";
        }

        $finder = clone $baseFinder;
        foreach ($ignoreRules as $ignoreRule) {
            $finder->notPath($ignoreRule);

            $excluded[] = "### Pattern $ignoreRule";
            foreach ((clone $baseFinder)->path($ignoreRule) as $file) {
                $excluded[] = $file->getRelativePathname();
            }
            $excluded[] = '';
        }

        $included = [];
        foreach ($finder as $file) {
            $included[] = $file->getRelativePathname();
        }

        file_put_contents($backup->includedListPath, implode("\n", $included));
        file_put_contents($backup->excludedListPath, implode("\n", $excluded));
    }
}
