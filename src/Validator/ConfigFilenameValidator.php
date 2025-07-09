<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validation;

readonly class ConfigFilenameValidator
{
    public function validate(string $configPath): void
    {
        Validation::createCallable(new File(
            extensions: [
                'yaml' => ['application/yaml', 'application/x-yaml', 'text/x-yaml', 'text/yaml', 'text/plain'],
                'yml'  => ['application/yaml', 'application/x-yaml', 'text/x-yaml', 'text/yaml', 'text/plain'],
            ],
        ))($configPath);
    }
}
