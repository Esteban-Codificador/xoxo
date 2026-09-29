<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentType;
use App\Enums\Difficulty;
use App\Models\Lesson;
use App\Models\Module;
use App\Rules\Slug;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * A new lesson: where it goes and what it is about. Objectives, body and
 * relations are written in the editor it opens in.
 */
class StoreLessonRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('create', Lesson::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'module_id' => ['required', 'integer', Rule::exists('modules', 'id')],
            'title' => ['required', 'string', 'max:200'],
            // Part of the lesson URL, unique across the platform.
            'slug' => ['required', 'string', 'max:160', new Slug, Rule::unique('lessons', 'slug')],
            'summary' => ['required', 'string', 'max:2000'],
            'why_it_matters' => ['required', 'string', 'max:2000'],
            'content_type' => ['required', Rule::enum(ContentType::class)->only(ContentType::forLessons())],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'estimated_minutes' => ['required', 'integer', 'between:1,600'],
        ];
    }

    public function module(): Module
    {
        return Module::query()->findOrFail($this->integer('module_id'));
    }
}
