<?php

namespace App\Http\Requests\Admin;

use App\Domain\Content\Videos\YouTubeId;
use App\Models\Video;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/** Asking YouTube about a link is part of adding a video (or one to a lesson). */
class LookupVideoRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('create', Video::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || YouTubeId::parse($value) === null) {
                    $fail(__('videos.invalid_url'));
                }
            }],
        ];
    }

    public function videoId(): string
    {
        return (string) YouTubeId::parse((string) $this->validated('url'));
    }
}
