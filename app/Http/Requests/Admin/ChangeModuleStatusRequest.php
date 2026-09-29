<?php

namespace App\Http\Requests\Admin;

use App\Models\Module;

class ChangeModuleStatusRequest extends ChangeStatusRequest
{
    public function subject(): Module
    {
        $module = $this->route('module');

        return $module instanceof Module ? $module : abort(404);
    }
}
