# Backup tool

This project is a simple tool scheduler to manage backup of multiple projects.
It require php > 8.4

## Configuration

To start, create a main config file like :
```yaml
backup:
  - from: ./src # Root folder to copy, must exist
    to: ./dest # Where to copy the files
    configName: backup.yml # Name of the folder config file, optional, backup.yml by default
```

Then in each folder, create a config file :
```yaml
dump_scripts:
  script1: mysqldump ... >$OUTPUT_PATH # $OUTPUT_PATH = .backups/output-script1
  script2: echo mysqldump # $OUTPUT_PATH = .backups/output-script1

ignore:
  - node_modules/
  - "#^.git/#"

before_backup: echo Before backup > $OUTPUT_PATH # $OUTPUT_PATH = .backups/before-backup-script
during_backup: echo During backup > $OUTPUT_PATH # $OUTPUT_PATH = .backups/during-backup-script
after_backup: echo After backup > $OUTPUT_PATH # $OUTPUT_PATH = .backups/after-backup-script
```

## Execution

Run the phar in the folder with the main config file :
```shell
./backup.phar backup main.yml
./backup.phar backup main.yml --dry-run
```
In dry-run mode, the command is prefixed by an echo to show the command that would be runned.
The include/exclude list is created.

## Scripts

All dump_scripts are executed sequentially until completion.
Then the before_backup script is executed until completion.
Then the during_backup script is executed in the background.

The rsync command then starts, it works in archive mode and uses --files-from to list the files to copy.
This list is constructed using symfony/finder. The ignore list is applied on the path.

Once the rsync is finished, the during_backup process is stopped.
Then the after_backup script is executed until completion.

## Output

Each script is provided an $OUTPUT_PATH env variable to be used as storage path.
The stdout and stderr are sent to the console. If a file must be stored for backup, it must be written using $OUTPUT_PATH.

A basic example for mysql would be:
```yaml
dump_scripts:
  mysqldump: |
    echo "Begin database dump"
    mysqldump ... >$OUTPUT_PATH
    echo "Done"
```

The two echo would be show in the console. The mysql dump will be stored in .backups/output-mysqldump to be copied using rsync.

## Summary console output

The summary output look like this :
```
./src
├── Date start : 2025-07-09 16:56:13
├── Dump scripts
│   └── mysql : 🟢 End script mysql, exit code : 0, duration : 0.001s
├── Before : 🟢 Exit code : 0, duration : 0.001s
├── RSync : 🟢 Exit code : 0, duration : 0.047s
├── During : 🟢 Exit code : 0, duration : 0.052s
├── After : 🟢 Exit code : 0, duration : 0.001s
└── Date end : 2025-07-09 16:56:1
```

It is shown in the console and also stored in the .backups/summary.txt file

## Storage

This tool create the following files which are always ignored by the backup :
- excluded.txt : List of all the excluded files
- included.txt : List of all the included files
- log.txt : Console output
- summary.txt : Tree summary console output
