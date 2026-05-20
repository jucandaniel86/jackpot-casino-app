<?php

namespace App\Interfaces;

interface RewardInterface
{
    public function insert(array $params = []);

    public function update(array $params = []);

    public function delete($id);

    public function list(array $params = []): array;

    public function publicList(array $params = []): array;

    public function types(): array;
}
