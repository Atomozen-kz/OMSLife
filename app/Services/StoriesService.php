<?php
namespace App\Services;

use App\Models\StoriesCategory;
use App\Models\Stories;

class StoriesService
{
    /**
     * Get categories of stories based on language.
     *
     * @param array $data
     * @return array
     */
    public function getCategoryStories(array $data): array
    {
        return StoriesCategory::where('lang', $data['lang'])->get()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'avatar' => $category->avatar ? url($category->avatar) : null,
                'stories' => $this->getStories(['category_id' => $category->id]),
            ];
        })->toArray();
    }

    /**
     * Get stories by category ID.
     *
     * @param array $data
     * @return array
     */
    public function getStories(array $data): array
    {
        return Stories::where('category_id', $data['category_id'])->get()->map(function ($story) {
            return [
                'id' => $story->id,
                'title' => $story->title,
                'media' => $story->media ? url($story->media) : null,
                'type' => $story->type,
            ];
        })->toArray();
    }
}
