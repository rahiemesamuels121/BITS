<?php

namespace App\Policies;

use App\Models\Form;
use App\Models\User;

class FormPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Form $form): bool
    {
        return (int) $form->user_id === (int) $user->id;
    }

    public function update(User $user, Form $form): bool
    {
        return $this->view($user, $form);
    }

    public function delete(User $user, Form $form): bool
    {
        return $this->view($user, $form);
    }
}
