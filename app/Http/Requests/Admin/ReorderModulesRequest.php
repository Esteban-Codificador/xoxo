<?php

namespace App\Http\Requests\Admin;

use App\Models\Track;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class ReorderModulesRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->track());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'modules' => ['required', 'list'],
            'modules.*' => ['required', 'integer', 'distinct'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                // The new order must list exactly the track's modules.
                $expected = $this->track()->modules()->pluck('id')->sort()->values()->all();
                $given = collect($this->moduleIds())->sort()->values()->all();

                if ($expected !== $given) {
                    $validator->errors()->add('modules', __('cms.order_mismatch'));
                }
            },
        ];
    }

    /**
     * @return list<int>
     */
    public function moduleIds(): array
    {
        /** @var list<int|string> $ids */
        $ids = $this->input('modules', []);

        return array_map(intval(...), $ids);
    }

    public function track(): Track
    {
        $track = $this->route('track');

        return $track instanceof Track ? $track : abort(404);
    }
}
