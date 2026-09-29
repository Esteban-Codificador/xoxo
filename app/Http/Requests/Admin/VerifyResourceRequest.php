<?php

namespace App\Http\Requests\Admin;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/** No input: whoever can edit the resource can ask for its URL to be checked. */
class VerifyResourceRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('resource'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
