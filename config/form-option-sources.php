<?php

use App\Models\Contract;
use App\Models\Form;
use App\Models\Role;
use App\Models\User;

// Explicitly exposed business columns; credentials and private responses are excluded.
return [
    'roles' => ['label' => 'Roles', 'model' => Role::class, 'columns' => ['name' => 'Role name', 'code' => 'Role code']],
    'users' => [
        'label' => 'Accounts / Clients', 'model' => User::class,
        'columns' => ['name' => 'Name', 'email' => 'Email', 'role_id' => 'Role ID', 'is_active' => 'Active (1 or 0)'],
        'filter_columns' => [
            'name' => ['label' => 'Name', 'type' => 'text'],
            'email' => ['label' => 'Email', 'type' => 'text'],
            'role_id' => ['label' => 'Role', 'type' => 'role'],
            'is_active' => ['label' => 'Account status', 'type' => 'select', 'choices' => ['1' => 'Active', '0' => 'Inactive']],
        ],
    ],
    'contracts' => [
        'label' => 'Contracts', 'model' => Contract::class,
        'columns' => ['name' => 'Contract name', 'id' => 'Contract ID'],
        'filter_columns' => [
            'name' => ['label' => 'Contract name', 'type' => 'text'],
            'id' => ['label' => 'Contract ID', 'type' => 'number'],
            'is_active' => ['label' => 'Contract status', 'type' => 'select', 'choices' => ['1' => 'Active', '0' => 'Inactive']],
            'is_indefinite' => ['label' => 'Duration', 'type' => 'select', 'choices' => ['1' => 'Indefinite', '0' => 'Dated']],
            'starts_at' => ['label' => 'Start date', 'type' => 'date'],
            'ends_at' => ['label' => 'End date', 'type' => 'date'],
        ],
    ],
    'forms' => [
        'label' => 'Forms', 'model' => Form::class,
        'columns' => ['title' => 'Title', 'status' => 'Status'],
        'filter_columns' => [
            'title' => ['label' => 'Title', 'type' => 'text'],
            'status' => ['label' => 'Publishing status', 'type' => 'select', 'choices' => ['published' => 'Published', 'draft' => 'Draft', 'archived' => 'Archived']],
        ],
    ],
];
