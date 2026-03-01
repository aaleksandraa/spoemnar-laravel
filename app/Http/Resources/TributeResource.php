<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TributeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'authorName' => $this->author_name,
            'authorEmail' => $this->when(
                $request->user() &&
                ($request->user()->id === $this->memorial->user_id || $this->isAdmin($request->user())),
                $this->author_email
            ),
            'message' => $this->message,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Check if the user is an admin
     */
    private function isAdmin($user): bool
    {
        if (!$user) {
            return false;
        }

        // Check both roles table and legacy role column
        return $user->roles()->where('role', 'admin')->exists() || (string) $user->role === 'admin';
    }
}
