<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVideoRequest;
use App\Http\Requests\UpdateVideoRequest;
use App\Models\Memorial;
use App\Models\MemorialVideo;
use App\Models\User;
use App\Services\VideoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    protected VideoService $videoService;

    public function __construct(VideoService $videoService)
    {
        $this->videoService = $videoService;
    }

    /**
     * Store a newly added video for a memorial
     *
     * @param StoreVideoRequest $request
     * @param Memorial $memorial
     * @return JsonResponse
     */
    public function store(StoreVideoRequest $request, Memorial $memorial): JsonResponse
    {
        // Check authorization - memorial owner or admin can add videos
        if (!$this->canManageMemorial($request->user(), $memorial)) {
            return response()->json([
                'message' => 'Unauthorized to add videos to this memorial'
            ], 403);
        }

        // Get next display order if not provided
        $displayOrder = $request->input('display_order');
        if ($displayOrder === null) {
            $maxOrder = $memorial->videos()->max('display_order') ?? -1;
            $displayOrder = $maxOrder + 1;
        }

        // Create memorial video record
        $memorialVideo = $memorial->videos()->create([
            'youtube_url' => $request->input('youtube_url'),
            'title' => $request->input('title'),
            'display_order' => $displayOrder,
        ]);

        return response()->json([
            'video' => $memorialVideo
        ], 201);
    }

    /**
     * Update an existing memorial video
     *
     * @param UpdateVideoRequest $request
     * @param MemorialVideo $video
     * @return JsonResponse
     */
    public function update(UpdateVideoRequest $request, MemorialVideo $video): JsonResponse
    {
        // Eager load memorial relationship for authorization check
        $video->load('memorial');

        // Check authorization using policy
        $this->authorize('update', $video);

        // Update video record
        $video->update($request->only(['youtube_url', 'title', 'display_order']));

        return response()->json([
            'video' => $video
        ]);
    }

    /**
     * Delete a memorial video
     *
     * @param Request $request
     * @param MemorialVideo $video
     * @return JsonResponse
     */
    public function destroy(Request $request, MemorialVideo $video): JsonResponse
    {
        // Eager load memorial relationship for authorization check
        $video->load('memorial');

        // Check authorization using policy
        $this->authorize('delete', $video);

        // Delete database record
        $video->delete();

        return response()->json(null, 204);
    }

    /**
     * Reorder videos for a memorial
     *
     * @param Request $request
     * @param Memorial $memorial
     * @return JsonResponse
     */
    public function reorder(Request $request, Memorial $memorial): JsonResponse
    {
        // Check authorization - memorial owner or admin can reorder videos
        if (!$this->canManageMemorial($request->user(), $memorial)) {
            return response()->json([
                'message' => 'Unauthorized to reorder videos for this memorial'
            ], 403);
        }

        // Validate request
        $request->validate([
            'videos' => 'required|array|min:1',
            'videos.*.id' => 'required|exists:memorial_videos,id',
            'videos.*.display_order' => 'required|integer|min:0|max:9999',
        ]);

        // Authorize each video individually
        foreach ($request->input('videos') as $videoData) {
            $video = MemorialVideo::with('memorial')->find($videoData['id']);
            $this->authorize('reorder', $video);
        }

        // Update display orders
        foreach ($request->input('videos') as $videoData) {
            MemorialVideo::where('id', $videoData['id'])
                ->where('memorial_id', $memorial->id)
                ->update(['display_order' => $videoData['display_order']]);
        }

        // Return updated videos
        $videos = $memorial->videos()->orderBy('display_order')->get();

        return response()->json([
            'videos' => $videos
        ]);
    }

    private function canManageMemorial(?User $user, Memorial $memorial): bool
    {
        if (!$user) {
            return false;
        }

        if ((string) $user->id === (string) $memorial->user_id) {
            return true;
        }

        return $user->roles()->where('role', 'admin')->exists()
            || (string) $user->role === 'admin';
    }
}
