<?php

namespace App\Domain\Curriculum\Actions;

use App\Models\Module;

final class UpdateModule
{
    /**
     * @param  array{title: string, slug: string, summary: string}  $data
     */
    public function handle(Module $module, array $data): Module
    {
        $module->forceFill([
            'title' => trim($data['title']),
            'slug' => $data['slug'],
            'summary' => trim($data['summary']),
        ])->save();

        return $module;
    }
}
