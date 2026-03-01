<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemorialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'birthDate' => $this->birth_date?->format('Y-m-d'),
            'deathDate' => $this->death_date?->format('Y-m-d'),
            'birthCountryId' => $this->birth_country_id,
            'birthPlaceId' => $this->birth_place_id,
            'birthPlace' => $this->birth_place,
            'deathCountryId' => $this->death_country_id,
            'deathPlaceId' => $this->death_place_id,
            'deathPlace' => $this->death_place,
            'biography' => $this->biography,
            'profileImageUrl' => MediaUrl::normalize($this->profile_image_url),
            'slug' => $this->slug,
            'isPublic' => $this->is_public,
            'birthCountry' => $this->whenLoaded('birthCountry', function () {
                return $this->birthCountry ? [
                    'id' => $this->birthCountry->id,
                    'code' => $this->birthCountry->code,
                    'name' => $this->birthCountry->name,
                ] : null;
            }),
            'deathCountry' => $this->whenLoaded('deathCountry', function () {
                return $this->deathCountry ? [
                    'id' => $this->deathCountry->id,
                    'code' => $this->deathCountry->code,
                    'name' => $this->deathCountry->name,
                ] : null;
            }),
            'birthPlaceEntity' => $this->whenLoaded('birthPlaceEntity', function () {
                return $this->birthPlaceEntity ? [
                    'id' => $this->birthPlaceEntity->id,
                    'name' => $this->birthPlaceEntity->name,
                    'type' => $this->birthPlaceEntity->type,
                    'countryId' => $this->birthPlaceEntity->country_id,
                ] : null;
            }),
            'deathPlaceEntity' => $this->whenLoaded('deathPlaceEntity', function () {
                return $this->deathPlaceEntity ? [
                    'id' => $this->deathPlaceEntity->id,
                    'name' => $this->deathPlaceEntity->name,
                    'type' => $this->deathPlaceEntity->type,
                    'countryId' => $this->deathPlaceEntity->country_id,
                ] : null;
            }),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'images' => $this->whenLoaded('images', function () {
                $profileImageUrl = MediaUrl::normalize($this->profile_image_url);
                $filteredImages = $this->images;

                if (is_string($profileImageUrl) && $profileImageUrl !== '') {
                    $filteredImages = $this->images->filter(static function ($image) use ($profileImageUrl): bool {
                        return MediaUrl::normalize($image->image_url) !== $profileImageUrl;
                    });
                }

                return $filteredImages->values()->map(static function ($image): array {
                    return [
                        'id' => (string) $image->id,
                        'imageUrl' => MediaUrl::normalize($image->image_url),
                        'caption' => $image->caption,
                        'displayOrder' => $image->display_order,
                    ];
                })->values();
            }),
            'videos' => $this->whenLoaded('videos', function () {
                return $this->videos->map(static function ($video): array {
                    return [
                        'id' => (string) $video->id,
                        'youtubeUrl' => $video->youtube_url,
                        'title' => $video->title,
                        'displayOrder' => $video->display_order,
                    ];
                })->values();
            }),
            'tributes' => TributeResource::collection($this->whenLoaded('tributes')),
        ];
    }
}
