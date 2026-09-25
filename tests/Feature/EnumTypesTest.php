<?php

use App\Console\Commands\GenerateEnumTypesCommand;

it('keeps the generated TypeScript enums in sync with PHP', function () {
    expect(file_get_contents(base_path(GenerateEnumTypesCommand::OUTPUT)))->toBe(GenerateEnumTypesCommand::render());
});

it('fails the check when the generated file is stale', function () {
    $path = base_path(GenerateEnumTypesCommand::OUTPUT);
    $original = file_get_contents($path);
    file_put_contents($path, $original."export const Stale = 1;\n");

    try {
        $this->artisan('types:enums', ['--check' => true])->assertFailed();
    } finally {
        file_put_contents($path, $original);
    }
});
