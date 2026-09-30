<?php

namespace App\Http\Requests\Admin;

use App\Models\Roadmap;

class ChangeRoadmapStatusRequest extends ChangeStatusRequest
{
    public function subject(): Roadmap
    {
        $roadmap = $this->route('roadmap');

        return $roadmap instanceof Roadmap ? $roadmap : abort(404);
    }
}
