<?php

namespace App\Http\Requests\Admin;

use App\Models\ExternalResource;

class ChangeResourceStatusRequest extends ChangeStatusRequest
{
    public function subject(): ExternalResource
    {
        $resource = $this->route('resource');

        return $resource instanceof ExternalResource ? $resource : abort(404);
    }
}
