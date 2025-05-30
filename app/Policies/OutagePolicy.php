<?php

namespace App\Policies;

use App\Models\Outage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OutagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true; // Users can view their own outages
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Outage  $outage
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Outage $outage)
    {
        return $user->id === $outage->user_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return true; // Authenticated users can create outages
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Outage  $outage
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Outage $outage)
    {
        return $user->id === $outage->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Outage  $outage
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Outage $outage)
    {
        return $user->id === $outage->user_id;
    }
}