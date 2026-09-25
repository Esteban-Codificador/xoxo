<?php

namespace App\Providers;

use App\Domain\Audit\AuditLogger;
use App\Models\ExternalResource;
use App\Models\LearningActivity;
use App\Models\Lesson;
use App\Models\LessonVersion;
use App\Models\Module;
use App\Models\Roadmap;
use App\Models\Skill;
use App\Models\Track;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(AuditLogger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureModels();
    }

    /**
     * Stable aliases for polymorphic columns: the database never stores PHP
     * class names, so models can be moved or renamed safely.
     */
    protected function configureModels(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'roadmap' => Roadmap::class,
            'track' => Track::class,
            'module' => Module::class,
            'lesson' => Lesson::class,
            'lesson_version' => LessonVersion::class,
            'skill' => Skill::class,
            'resource' => ExternalResource::class,
            'learning_activity' => LearningActivity::class,
        ]);

        // Catch N+1 queries and silently dropped attributes outside production.
        Model::shouldBeStrict(! app()->isProduction());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
