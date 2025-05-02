<?php

return [
    'title' => 'Users',
    'user' => 'User',
    'list' => 'User List',
    'detail' => 'User Detail',
    'delete_title' => 'Delete User',
    'delete_warning' => 'This action cannot be undone. This will permanently delete the user and remove all associated data.',
    'create_title' => 'Create New User',
    'edit_title' => 'Edit User Details',
    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'role' => 'Role',
        'password' => 'Password',
        'password_confirmation' => 'Password Confirmation',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'photo' => 'Photo',
    ],
    'messages' => [
        'created' => 'User has been created successfully.',
        'updated' => 'User has been updated successfully.',
        'deleted' => 'User has been deleted successfully.',
        'delete_confirm' => 'Are you sure you want to delete this user?',
    ],
    'roles' => [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'staff' => 'Staff',
    ],
    'actions' => [
        'create' => 'Create New User',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'back' => 'Back',
    ],
];
