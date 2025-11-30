<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetCategoryStoriesRequest;
use App\Http\Requests\GetStoriesRequest;
use App\Services\StoriesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoriesController extends Controller
{
    protected StoriesService $storiesService;

    public function __construct(StoriesService $storiesService)
    {
        $this->storiesService = $storiesService;
    }

    /**
     * Get categories of stories.
     *
     * @param GetCategoryStoriesRequest $request
     * @return JsonResponse
     */
    public function getCategoryStories(GetCategoryStoriesRequest $request): JsonResponse
    {
        $categories = $this->storiesService->getCategoryStories($request->validated());
        return response()->json($categories);
    }

    /**
     * Get stories by category.
     *
     * @param GetStoriesRequest $request
     * @return JsonResponse
     */
    public function getStories(GetStoriesRequest $request): JsonResponse
    {
        $stories = $this->storiesService->getStories($request->validated());
        return response()->json($stories);
    }
}
