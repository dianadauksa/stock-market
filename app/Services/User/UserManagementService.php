<?php

namespace App\Services\User;

use App\Repositories\Users\MySQLUsersRepository;
use App\Repositories\Users\UsersRepository;

class UserManagementService
{
    private UsersRepository $usersRepository;

    public function __construct()
    {
        $this->usersRepository = new MySQLUsersRepository();
    }

    public function register(RegisterUserServiceRequest $request): void
    {
        $this->usersRepository->add($request);
    }

    public function getUserByEmail(string $email): ?array
    {
        return $this->usersRepository->getByEmail($email);
    }

    //for ViewVariables
    public function getUserByID(int $id): ?array
    {
        return $this->usersRepository->getByID($id);
    }
}