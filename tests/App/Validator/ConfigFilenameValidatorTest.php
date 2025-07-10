<?php

namespace Tests\App\Validator;

use App\Validator\ConfigFilenameValidator;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ConfigFilenameValidatorTest extends TestCase
{
    #[TestWith(['good-extension-good-type.yaml'])]
    #[TestWith(['good-extension-good-type.yml'])]
    public function testValidateCorrect(string $configPath): void
    {
        $validator = new ConfigFilenameValidator();
        $validator->validate(__DIR__.'/'.$configPath);

        $this->expectNotToPerformAssertions();
    }

    #[TestWith(['bad-extension-good-type.txt', 'The extension of the file is invalid ("txt"). Allowed extensions are "yaml", "yml". (code c8c7315c-6186-4719-8b71-5659e16bdcb7)'])]
    #[TestWith(['empty-file.yaml', 'An empty file is not allowed. (code 5d743385-9775-4aa5-8ff5-495fb1e60137)'])]
    #[TestWith(['good-extension-bad-type.yaml', 'The mime type of the file is invalid ("text/x-shellscript"). Allowed mime types are "application/yaml", "application/x-yaml", "text/x-yaml", "text/yaml", "text/plain". (code 744f00bc-4389-4c74-92de-9a43cde55534)'])]
    public function testValidateIncorrect(string $configPath, string $validationError): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage($validationError);

        $validator = new ConfigFilenameValidator();
        $validator->validate(__DIR__.'/'.$configPath);
    }
}
