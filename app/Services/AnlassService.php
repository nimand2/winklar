<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Anlass;


final class AnlassService
{
    public function __construct(private readonly Anlass $anlassModel)
    {
    }

    public function getAnlass(): array
    {
        return $this->anlassModel->getAll();
    }

    public function getAnlassById(int $id): ?array
    {
        return $this->anlassModel->findById($id);
    }
}
