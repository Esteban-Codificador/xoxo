<?php

namespace App\Http\Requests\Admin;

use App\Models\Track;

class ChangeTrackStatusRequest extends ChangeStatusRequest
{
    public function subject(): Track
    {
        $track = $this->route('track');

        return $track instanceof Track ? $track : abort(404);
    }
}
