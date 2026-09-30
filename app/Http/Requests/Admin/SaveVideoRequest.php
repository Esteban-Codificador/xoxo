<?php

namespace App\Http\Requests\Admin;

use App\Domain\Content\Videos\VideoDuration;
use App\Domain\Content\Videos\YouTubeId;
use App\Enums\Difficulty;
use App\Enums\VideoProvider;
use App\Models\Video;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Creates (no route model) or updates a video of the catalog. The video
 * is a YouTube link or ID; one entry per video.
 */
class SaveVideoRequest extends FormRequest
{
    public function authorize(): Response
    {
        $video = $this->video();

        return $video === null
            ? Gate::inspect('create', Video::class)
            : Gate::inspect('update', $video);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
                $id = is_string($value) ? YouTubeId::parse($value) : null;

                if ($id === null) {
                    $fail(__('videos.invalid_url'));

                    return;
                }

                $taken = Video::query()->where('provider', VideoProvider::Youtube->value)->where('external_id', $id)
                    ->when($this->video() !== null, fn ($query) => $query->whereKeyNot($this->video()?->id))
                    ->exists();

                if ($taken) {
                    $fail(__('videos.taken'));
                }
            }],
            'title' => ['required', 'string', 'max:200'],
            'instructor' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration' => ['nullable', 'string', 'regex:'.VideoDuration::PATTERN],
            'difficulty' => ['nullable', Rule::enum(Difficulty::class)],
            'language' => ['required', Rule::in(['es', 'en'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['duration.regex' => __('videos.invalid_duration')];
    }

    public function video(): ?Video
    {
        $video = $this->route('video');

        return $video instanceof Video ? $video : null;
    }
}
