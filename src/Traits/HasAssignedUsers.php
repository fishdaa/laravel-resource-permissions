<?php

namespace Fishdaa\LaravelResourcePermissions\Traits;

use Fishdaa\LaravelResourcePermissions\Models\ModelHasResourceAndPermission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

trait HasAssignedUsers
{
    /**
     * Get all users assigned to this resource (with permissions or roles).
     * Optionally filter to only specific users.
     *
     * @param  array|Collection|null  $users  Optional array of user IDs or User model instances to filter
     * @return Collection
     */
    public function getAssignedUsers($users = null): Collection
    {
        return ModelHasResourceAndPermission::getUsersForResource($this, $users);
    }

    /**
     * Check if a specific user is assigned to this resource.
     *
     * @param  mixed  $user
     * @return bool
     */
    public function hasUserAssigned($user): bool
    {
        return ModelHasResourceAndPermission::isUserAssignedToResource($user, $this);
    }

    /**
     * Check if all specified users are assigned to this resource.
     *
     * @param  array|Collection  $users
     * @return bool
     */
    public function hasAllUsersAssigned($users): bool
    {
        $userIds = collect($users)->map(function ($user) {
            return is_object($user) ? $user->id : $user;
        })->toArray();

        $assignedUserIds = ModelHasResourceAndPermission::forResource($this)
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        return count(array_intersect($userIds, $assignedUserIds)) === count($userIds);
    }

    /**
     * Check if any of the specified users are assigned to this resource.
     *
     * @param  array|Collection  $users
     * @return bool
     */
    public function hasAnyUserAssigned($users): bool
    {
        $userIds = collect($users)->map(function ($user) {
            return is_object($user) ? $user->id : $user;
        })->toArray();

        return ModelHasResourceAndPermission::forResource($this)
            ->whereIn('user_id', $userIds)
            ->exists();
    }
}

