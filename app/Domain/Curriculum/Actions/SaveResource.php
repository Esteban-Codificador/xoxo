<?php

namespace App\Domain\Curriculum\Actions;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Enums\LinkStatus;
use App\Enums\ResourceType;
use App\Jobs\VerifyResourceLinkJob;
use App\Models\ExternalResource;

/**
 * Creates or updates a resource. A new or changed URL loses its previous
 * check and is verified again off the request (VerifyResourceLinkJob).
 */
final class SaveResource
{
    /**
     * @param  array{title: string, url: string, type: string, provider: string, description: string, difficulty: string|null, language: string, is_official: bool|int|string}  $data
     */
    public function handle(?ExternalResource $resource, array $data): ExternalResource
    {
        $resource ??= new ExternalResource(['status' => ContentStatus::Draft]);
        $url = trim($data['url']);
        $urlChanged = $resource->url !== $url;

        $resource->forceFill([
            'title' => trim($data['title']),
            'url' => $url,
            'type' => ResourceType::from($data['type']),
            'provider' => trim($data['provider']),
            'description' => trim($data['description']),
            'difficulty' => $data['difficulty'] === null ? null : Difficulty::from($data['difficulty']),
            'language' => $data['language'],
            'is_official' => filter_var($data['is_official'], FILTER_VALIDATE_BOOLEAN),
        ]);

        if ($urlChanged) {
            $resource->forceFill(['link_status' => LinkStatus::Unchecked, 'last_http_status' => null, 'last_checked_at' => null]);
        }

        $resource->save();

        if ($urlChanged) {
            VerifyResourceLinkJob::dispatch($resource)->afterCommit();
        }

        return $resource;
    }
}
