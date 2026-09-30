<?php

namespace App\Providers;

use App\Domain\Audit\AuditLogger;
use App\Domain\Learning\Recommendations\RecommendationEngine;
use App\Domain\Learning\Recommendations\RuleBasedRecommendationEngine;
use App\Models\ExternalResource;
use App\Models\LearningActivity;
use App\Models\Lesson;
use App\Models\LessonVersion;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\Roadmap;
use App\Models\Skill;
use App\Models\Track;
use App\Models\User;
use App\Models\Video;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->app->bind(RecommendationEngine::class, RuleBasedRecommendationEngine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureModels();
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        // Each upload decodes and re-encodes an image: cheap to send, not to process.
        RateLimiter::for('media-uploads', fn (Request $request) => Limit::perMinute(30)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        // Each lookup is a request to YouTube on the user's behalf.
        RateLimiter::for('video-lookups', fn (Request $request) => Limit::perMinute(60)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        // Starting and submitting quiz attempts: grading runs on the server.
        RateLimiter::for('quiz-attempts', fn (Request $request) => Limit::perMinute(20)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
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
            'video' => Video::class,
            'quiz' => Quiz::class,
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
