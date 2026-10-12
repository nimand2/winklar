<?php
declare(strict_types=1);
namespace App\Validation;

final class FormInput
{
    public array $errors = [];
    public function __construct(private readonly array $source) {}

    public function text(string $key, int $max, bool $required = false): ?string
    {
        $raw = $this->source[$key] ?? null;
        if ($raw !== null && !is_string($raw) && !is_int($raw)) {
            $this->errors[] = $key . ': ungültige Eingabe.';
            return null;
        }
        $value = trim((string) $raw);
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        if (($required && $value === '') || $length > $max || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)) {
            $this->errors[] = $key . ': gültiger Wert mit höchstens ' . $max . ' Zeichen erforderlich.';
        }
        return $value === '' ? null : $value;
    }

    public function integer(string $key, int $min, int $max, bool $required = false): ?int
    {
        $text = $this->text($key, 30, $required);
        if ($text === null) return null;
        $value = filter_var($text, FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max) {
            $this->errors[] = $key . ': ganze Zahl von ' . $min . ' bis ' . $max . ' erforderlich.';
            return null;
        }
        return $value;
    }

    public function decimal(string $key, float $min = 0, float $max = 99999999.99, int $scale = 2, bool $required = false): ?string
    {
        $raw = $this->source[$key] ?? null;
        $value = is_float($raw) && is_finite($raw) ? (string) $raw : $this->text($key, 40, $required);
        if ($value === null) return null;
        $value = str_replace(',', '.', $value);
        if (!preg_match('/^[+-]?\d+(?:\.\d{1,' . $scale . '})?$/D', $value)
            || !is_finite((float) $value) || (float) $value < $min || (float) $value > $max) {
            $this->errors[] = $key . ': gültige Zahl von ' . $min . ' bis ' . $max . ' mit höchstens ' . $scale . ' Nachkommastellen erforderlich.';
            return null;
        }
        return $value;
    }

    public function date(string $key): ?string
    {
        $value = $this->text($key, 10);
        if ($value === null) return null;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value || $value < '1000-01-01') {
            $this->errors[] = $key . ': gültiges Datum im Format JJJJ-MM-TT erforderlich.';
            return null;
        }
        return $value;
    }

    public static function timestamp(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        if (!is_string($value) || !preg_match('/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})(?::(\d{2})(?:\.\d{1,6})?)?(?:Z|[+-]\d{2}:\d{2})?$/D', $value, $matches)) {
            throw new \InvalidArgumentException('Ungültiger Schusszeitpunkt.');
        }
        $input = new self(['date' => $matches[1]]);
        $input->date('date');
        $invalidOffset = preg_match('/[+-](\d{2}):(\d{2})$/D', $value, $offset)
            && ((int) $offset[1] > 14 || (int) $offset[2] > 59 || ((int) $offset[1] === 14 && (int) $offset[2] !== 0));
        if ($input->errors !== [] || $invalidOffset || substr($matches[2], 0, 2) > '23'
            || substr($matches[2], 3, 2) > '59' || ($matches[3] ?? '00') > '59') {
            throw new \InvalidArgumentException('Ungültiger Schusszeitpunkt.');
        }
        try {
            return (new \DateTimeImmutable($value))->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s');
        } catch (\Exception $error) {
            throw new \InvalidArgumentException('Ungültiger Schusszeitpunkt.', 0, $error);
        }
    }
}
