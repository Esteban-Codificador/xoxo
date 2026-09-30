<?php

namespace App\Http\Requests\Admin;

use App\Enums\DependencyKind;
use App\Models\Lesson;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Skills (with weight), prerequisites, resources and videos (in order) of a lesson,
 * each as a full list. Cycles are checked by SyncLessonRelations.
 */
class SyncLessonRelationsRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->lesson());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $lesson = $this->lesson();

        return [
            'skills' => ['present', 'list', 'max:20'],
            'skills.*.id' => ['required', 'integer', 'distinct', Rule::exists('skills', 'id')],
            'skills.*.weight' => ['required', 'integer', 'between:1,5'],
            'prerequisites' => ['present', 'list', 'max:20'],
            // Prerequisites live in the lesson's roadmap and are not the lesson itself.
            'prerequisites.*.id' => [
                'required', 'integer', 'distinct', Rule::notIn([$lesson->id]),
                Rule::in(Lesson::query()->inRoadmapOf($lesson)->pluck('id')->all()),
            ],
            'prerequisites.*.kind' => ['required', Rule::enum(DependencyKind::class)],
            'resources' => ['present', 'list', 'max:30'],
            'resources.*' => ['required', 'integer', 'distinct', Rule::exists('resources', 'id')],
            // Optional: a client that does not send them leaves the videos as they are.
            'videos' => ['sometimes', 'list', 'max:20'],
            'videos.*' => ['required', 'integer', 'distinct', Rule::exists('videos', 'id')],
        ];
    }

    /**
     * @return array<int, int> skill id => weight
     */
    public function skills(): array
    {
        /** @var list<array{id: int|string, weight: int|string}> $rows */
        $rows = $this->validated('skills');

        return collect($rows)->mapWithKeys(fn (array $row) => [(int) $row['id'] => (int) $row['weight']])->all();
    }

    /**
     * @return array<int, string> lesson id => kind
     */
    public function prerequisites(): array
    {
        /** @var list<array{id: int|string, kind: string}> $rows */
        $rows = $this->validated('prerequisites');

        return collect($rows)->mapWithKeys(fn (array $row) => [(int) $row['id'] => $row['kind']])->all();
    }

    /**
     * @return list<int> resource ids in display order
     */
    public function resources(): array
    {
        /** @var list<int|string> $ids */
        $ids = $this->validated('resources');

        return array_map(intval(...), $ids);
    }

    /**
     * @return list<int>|null video ids in display order, or null when not sent
     */
    public function videos(): ?array
    {
        if (! $this->has('videos')) {
            return null;
        }

        /** @var list<int|string> $ids */
        $ids = $this->validated('videos');

        return array_map(intval(...), $ids);
    }

    public function lesson(): Lesson
    {
        $lesson = $this->route('lesson');

        return $lesson instanceof Lesson ? $lesson : abort(404);
    }
}
