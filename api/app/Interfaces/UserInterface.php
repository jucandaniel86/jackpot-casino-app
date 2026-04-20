<?php

  namespace App\Interfaces;

  interface UserInterface
  {
    public function getUsersList(array $params = []): array;

    public function createUser(array $params = []);

    public function deleteUser($id);

    public function changePassword($params);
  }
