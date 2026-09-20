<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes  Already-hashed password
     *                                            expected under 'password'.
     */
    public function create(array $attributes): User;
}
