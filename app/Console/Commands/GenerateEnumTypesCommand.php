<?php

namespace App\Console\Commands;

use App\Enums\ActivityType;
use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\DependencyKind;
use App\Enums\Difficulty;
use App\Enums\LinkStatus;
use App\Enums\Permission;
use App\Enums\ProfileVisibility;
use App\Enums\ProgressStatus;
use App\Enums\ResourceType;
use App\Enums\Role;
use App\Enums\UnlockPolicy;
use BackedEnum;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * PHP enums are the source of truth (ADR-018). This writes their TypeScript
 * mirror so the frontend never hardcodes a status or type string.
 */
#[Signature('types:enums {--check : Falla si el archivo generado no está al día}')]
#[Description('Genera resources/js/types/enums.ts a partir de los enums de PHP')]
class GenerateEnumTypesCommand extends Command
{
    public const string OUTPUT = 'resources/js/types/enums.ts';

    /** @var list<class-string<BackedEnum>> */
    private const array ENUMS = [
        ActivityType::class,
        AuditAction::class,
        ContentStatus::class,
        ContentType::class,
        DependencyKind::class,
        Difficulty::class,
        LinkStatus::class,
        Permission::class,
        ProfileVisibility::class,
        ProgressStatus::class,
        ResourceType::class,
        Role::class,
        UnlockPolicy::class,
    ];

    public function handle(): int
    {
        $path = base_path(self::OUTPUT);
        $generated = self::render();

        if ($this->option('check')) {
            if (! is_file($path) || file_get_contents($path) !== $generated) {
                $this->error(self::OUTPUT.' está desactualizado. Ejecuta: php artisan types:enums');

                return self::FAILURE;
            }

            $this->info(self::OUTPUT.' está al día.');

            return self::SUCCESS;
        }

        file_put_contents($path, $generated);
        $this->info('Generado '.self::OUTPUT.' ('.count(self::ENUMS).' enums).');

        return self::SUCCESS;
    }

    public static function render(): string
    {
        $blocks = [];

        foreach (self::ENUMS as $enum) {
            $name = class_basename($enum);
            $cases = array_map(
                fn (BackedEnum $case) => "    {$case->name}: '{$case->value}',",
                $enum::cases(),
            );

            $type = "export type {$name} = (typeof {$name})[keyof typeof {$name}];";

            // Match the formatter (printWidth 80) so the generated file passes `vp check` untouched.
            if (strlen($type) > 80) {
                $type = "export type {$name} =\n    (typeof {$name})[keyof typeof {$name}];";
            }

            $blocks[] = "export const {$name} = {\n".implode("\n", $cases)."\n} as const;\n\n".$type;
        }

        return "// GENERADO por `php artisan types:enums` a partir de app/Enums. No editar a mano.\n\n"
            .implode("\n\n", $blocks)."\n";
    }
}
