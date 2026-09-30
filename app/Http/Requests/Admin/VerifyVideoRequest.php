<?php

namespace App\Http\Requests\Admin;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/** No input: whoever can edit the video can ask for it to be checked. */
class VerifyVideoRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('video'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
