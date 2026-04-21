<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Adressen
{
    public function getAll(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT a.id, a.creator_adress_id, a.modifier_adress_id, a.anrede, a.firmen_anrede,
                    a.nachname, a.vorname, a.zusatz, a.strasse, a.postfach, a.nation, a.plz_id,
                    p.plz4, p.ortschaftsname, telefon, email, notiz, geburtsdatum, lizenz, passwort,
                    a.created_by_user_id, a.created_at, a.updated_by_user_id, a.updated_at
             FROM adressen a
             LEFT JOIN plz p ON p.id = a.plz_id
             ORDER BY a.nachname ASC, a.vorname ASC, a.id ASC'
        );
        $statement->execute([]);

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO adressen (
                creator_adress_id,
                modifier_adress_id,
                anrede,
                firmen_anrede,
                nachname,
                vorname,
                zusatz,
                strasse,
                postfach,
                nation,
                plz_id,
                telefon,
                email,
                notiz,
                geburtsdatum,
                lizenz,
                passwort,
                created_by_user_id,
                updated_by_user_id
             ) VALUES (
                :creator_adress_id,
                :modifier_adress_id,
                :anrede,
                :firmen_anrede,
                :nachname,
                :vorname,
                :zusatz,
                :strasse,
                :postfach,
                :nation,
                :plz_id,
                :telefon,
                :email,
                :notiz,
                :geburtsdatum,
                :lizenz,
                :passwort,
                :created_by_user_id,
                :updated_by_user_id
             )'
        );
        $statement->execute($this->buildPayload($data));

        return (int)Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE adressen
             SET creator_adress_id = :creator_adress_id,
                 modifier_adress_id = :modifier_adress_id,
                 anrede = :anrede,
                 firmen_anrede = :firmen_anrede,
                 nachname = :nachname,
                 vorname = :vorname,
                 zusatz = :zusatz,
                 strasse = :strasse,
                 postfach = :postfach,
                 nation = :nation,
                 plz_id = :plz_id,
                 telefon = :telefon,
                 email = :email,
                 notiz = :notiz,
                 geburtsdatum = :geburtsdatum,
                 lizenz = :lizenz,
                 passwort = :passwort,
                 created_by_user_id = :created_by_user_id,
                 updated_by_user_id = :updated_by_user_id
             WHERE id = :id'
        );

        $payload = $this->buildPayload($data);
        $payload['id'] = $id;

        return $statement->execute($payload);
    }

    public function delete(int $id): bool
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM adressen
             WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    private function buildPayload(array $data): array
    {
        return [
            'creator_adress_id' => $data['creator_adress_id'] ?? null,
            'modifier_adress_id' => $data['modifier_adress_id'] ?? null,
            'anrede' => $data['anrede'] ?? null,
            'firmen_anrede' => $data['firmen_anrede'] ?? null,
            'nachname' => $data['nachname'],
            'vorname' => $data['vorname'] ?? null,
            'zusatz' => $data['zusatz'] ?? null,
            'strasse' => $data['strasse'] ?? null,
            'postfach' => $data['postfach'] ?? null,
            'nation' => $data['nation'] ?? null,
            'plz_id' => $data['plz_id'] ?? null,
            'telefon' => $data['telefon'] ?? null,
            'email' => $data['email'] ?? null,
            'notiz' => $data['notiz'] ?? null,
            'geburtsdatum' => $data['geburtsdatum'] ?? null,
            'lizenz' => $data['lizenz'] ?? null,
            'passwort' => $data['passwort'] ?? null,
            'created_by_user_id' => $data['created_by_user_id'] ?? null,
            'updated_by_user_id' => $data['updated_by_user_id'] ?? null,
        ];
    }
}
