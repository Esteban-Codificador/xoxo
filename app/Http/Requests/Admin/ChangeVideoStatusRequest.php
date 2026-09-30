<?php

namespace App\Http\Requests\Admin;

use App\Models\Video;

class ChangeVideoStatusRequest extends ChangeStatusRequest
{
    public function subject(): Video
    {
        $video = $this->route('video');

        return $video instanceof Video ? $video : abort(404);
    }
}
