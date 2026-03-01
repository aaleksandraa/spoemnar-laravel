<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\UpdateImageRequest;
use App\Models\Memorial;
use App\Models\MemorialImage;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Upload or replace memorial profile image without adding it to gallery.
     *
     * @param StoreImageRequest $request
     * @param Memorial $memorial
     * @return JsonResponse
     */
    public function storeProfileImage(StoreImageRequest $request, Memorial $memorial): JsonResponse
    {
        if (!$this->canManageMemorial($request->user(), $memorial)) {
            return response()->json([
                'message' => 'Unauthorized to update profile image for this memorial',
            ], 403);
        }

        $path = "memorials/{$memorial->id}";
        $imageUrl = $this->imageService->upload($request->file('image'), $path);

        $memorial->profile_image_url = $imageUrl;
        $memorial->save();

        return response()->json([
            'profile_image_url' => $memorial->profile_image_url,
        ], 201);
    }

    /**
     * Store a newly uploaded image for a memorial
     *
     * @param StoreImageRequest $request
     * @param Memorial $memorial
     * @return JsonResponse
     */
    public function store(StoreImageRequest $request, Memorial $memorial): JsonResponse
    {
        // Check authorization - memorial owner or admin can upload images
        if (!$this->canManageMemorial($request->user(), $memorial)) {
            return response()->json([
                'message' => 'Unauthorized to add images to this memorial'
            ], 403);
        }

        // Upload image
        $path = "memorials/{$memorial->id}";
        $imageUrl = $this->imageService->upload($request->file('image'), $path);

        // Get next display order if not provided
        $displayOrder = $request->input('display_order');
        if ($displayOrder === null) {
            $maxOrder = $memorial->images()->max('display_order') ?? -1;
            $displayOrder = $maxOrder + 1;
        }

        // Create memorial image record
        $memorialImage = $memorial->images()->create([
            'image_url' => $imageUrl,
            'caption' => $request->input('caption'),
            'display_order' => $displayOrder,
        ]);

        return response()->json([
            'image' => $memorialImage
        ], 201);
    }

    /**
     * Update an existing memorial image
     *
     * @param UpdateImageRequest $request
     * @param MemorialImage $image
     * @return JsonResponse
     */
    public function update(UpdateImageRequest $request, MemorialImage $image): JsonResponse
    {
        // Eager load memorial relationship for authorization check
        $image->load('memorial');

        // Check authorization using policy
        $this->authorize('update', $image);

        // Update image record
        $image->update($request->only(['caption', 'display_order']));

        return response()->json([
            'image' => $image
        ]);
    }

    /**
     * Delete a memorial image
     *
     * @param Request $request
     * @param MemorialImage $image
     * @return JsonResponse
     */
    public function destroy(Request $request, MemorialImage $image): JsonResponse
    {
        // Eager load memorial relationship for authorization check
        $image->load('memorial');

        // Check authorization using policy
        $this->authorize('delete', $image);

        // Delete file from storage
        $this->imageService->delete($image->image_url);

        // Delete database record
        $image->delete();

        return response()->json(null, 204);
    }

    /**
     * Reorder images for a memorial
     *
     * @param Request $request
     * @param Memorial $memorial
     * @return JsonResponse
     */
    public function reorder(Request $request, Memorial $memorial): JsonResponse
    {
        // Validate request
        $request->validate([
            'images' => 'required|array|min:1',
            'images.*.id' => 'required|exists:memorial_images,id',
            'images.*.display_order' => 'required|integer|min:0|max:9999',
        ]);

        // Check authorization for each image being reordered
        foreach ($request->input('images') as $imageData) {
            $image = MemorialImage::with('memorial')->find($imageData['id']);
            if ($image) {
                $this->authorize('reorder', $image);
            }
        }

        // Update display orders
        foreach ($request->input('images') as $imageData) {
            MemorialImage::where('id', $imageData['id'])
                ->where('memorial_id', $memorial->id)
                ->update(['display_order' => $imageData['display_order']]);
        }

        // Return updated images
        $images = $memorial->images()->orderBy('display_order')->get();

        return response()->json([
            'images' => $images
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
