<?php
declare(strict_types=1);
namespace App\Validation;

final class StandblattFormService
{
    public static function parse(array $source, array $available): array
    {
        $input = new FormInput($source);
        $date = $input->date('datum');
        $ids = $source['stich_ids'] ?? [];
        $postedCounts = $source['stich_counts'] ?? [];
        $availableIds = array_column($available, 'id');
        $counts = [];
        if (!is_array($ids) || !array_is_list($ids) || !is_array($postedCounts)) {
            $input->errors[] = 'Ungültige Stichauswahl.';
        } else {
            foreach ($ids as $id) {
                $field = new FormInput(['id' => $id]);
                $id = $field->integer('id', 1, 4294967295, true);
                $countField = new FormInput(['count' => $id !== null ? ($postedCounts[$id] ?? 1) : null]);
                $count = $countField->integer('count', 1, 1000, true);
                $field->errors = array_merge($field->errors, $countField->errors);
                $input->errors = array_merge($input->errors, $field->errors);
                if ($id !== null && !in_array($id, array_map('intval', $availableIds), true)) $input->errors[] = 'Stich gehört nicht zu diesem Anlass.';
                if ($id !== null && $count !== null) $counts[$id] = $count;
            }
        }
        return ['datum' => $date, 'counts' => $counts, 'errors' => $input->errors];
    }
}
