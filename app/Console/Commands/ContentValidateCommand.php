<?php

namespace App\Console\Commands;

use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\PackageReader;
use App\Domain\Content\Package\PackageValidator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('content:validate {path=content/ai-engineer : Directorio del paquete de contenido}')]
#[Description('Valida un paquete de contenido sin escribir en la base de datos')]
class ContentValidateCommand extends Command
{
    public function handle(PackageReader $reader, PackageValidator $validator): int
    {
        $package = $reader->read($this->packagePath());
        $issues = $validator->validate($package);

        $this->line(sprintf(
            'Paquete "%s": %d roadmap, %d tracks, %d módulos, %d lecciones, %d skills, %d recursos.',
            $package->name,
            $package->count(EntityType::Roadmap),
            $package->count(EntityType::Track),
            $package->count(EntityType::Module),
            $package->count(EntityType::Lesson),
            $package->count(EntityType::Skill),
            $package->count(EntityType::Resource),
        ));

        if ($issues === []) {
            $this->info('Sin problemas.');

            return self::SUCCESS;
        }

        foreach ($issues as $issue) {
            $this->error((string) $issue);
        }

        $this->newLine();
        $this->error(count($issues).' problema(s).');

        return self::FAILURE;
    }

    private function packagePath(): string
    {
        $path = (string) $this->argument('path');

        return str_starts_with($path, '/') ? $path : base_path($path);
    }
}
