<?php

namespace App\Policies;

use App\Models\Content;
use App\Models\User;

class ContentPolicy
{
    private function owns(User $user, Content $content): bool
    {
        return $user->isAdmin() || $user->id === $content->user_id;
    }

    /** Editor tidak membuat ide; hanya kreator (dan admin). */
    public function create(User $user): bool
    {
        return ! $user->isEditor();
    }

    public function view(User $user, Content $content): bool
    {
        if ($this->owns($user, $content)) {
            return true;
        }

        // Editor boleh melihat konten yang menunggu review, atau yang pernah ia review.
        return $user->isEditor() && (
            $content->status === 'editing'
            || $content->reviews()->where('reviewer_id', $user->id)->exists()
        );
    }

    public function update(User $user, Content $content): bool
    {
        return $this->owns($user, $content);
    }

    public function delete(User $user, Content $content): bool
    {
        return $this->owns($user, $content);
    }

    /** Memutuskan hasil review: hanya editor/admin, hanya saat "editing", dan bukan konten sendiri. */
    public function review(User $user, Content $content): bool
    {
        return ($user->isEditor() || $user->isAdmin())
            && $user->id !== $content->user_id
            && $content->status === 'editing';
    }
}
