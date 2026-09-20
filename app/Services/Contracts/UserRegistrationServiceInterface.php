<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\User;

interface UserRegistrationServiceInterface
{
    /**
     * Create a new user account from already-validated input. The
     * caller (a Form Request) is responsible for validating uniqueness
     * and password rules; this service owns what happens next - hashing
     * the password and firing the framework's Registered event.
     *
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): User;
}
