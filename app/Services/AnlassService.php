<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Anlass;


final class AnlassService
{
    public function __construct(private readonly Anlass $anlassModel)
    {
    }

    /**
     * Liefert alle erfassten Anlaesse fuer Uebersichten.
     */
    public function getAnlass(): array
    {
        return $this->anlassModel->getAll();
    }

    /**
     * Sucht einen einzelnen Anlass anhand seiner ID.
     */
    public function getAnlassById(int $id): ?array
    {
        return $this->anlassModel->findById($id);
    }

    /**
     * Erstellt einen neuen Anlass mit bereits validierten Daten.
     */
    public function createAnlass(array $data): int
    {
        return $this->anlassModel->create($data);
    }

    /**
     * Aktualisiert die Grunddaten eines bestehenden Anlasses.
     */
    public function updateAnlass(int $id, array $data): bool
    {
        return $this->anlassModel->update($id, $data);
    }
}
