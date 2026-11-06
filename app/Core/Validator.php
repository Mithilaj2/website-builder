<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Rule-based validator. Rules: required, email, url, phone, slug, min:N, max:N,
 * in:a,b,c, integer, unique:table,column[,ignoreId], exists:table,column, confirmed, password, hex
 */
final class Validator
{
    private array $errors = [];

    public function __construct(private array $data, private array $rules, private array $labels = [])
    {
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        $v = new self($data, $rules, $labels);
        $v->run();
        return $v;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $label = $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $empty = $value === null || $value === '' || $value === [];

            foreach ($rules as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                if ($name !== 'required' && $empty) {
                    continue;
                }
                $error = $this->check($name, $arg, $value, $label, $field);
                if ($error !== null) {
                    $this->errors[$field] = $error;
                    break;
                }
            }
        }
    }

    private function check(string $name, ?string $arg, mixed $value, string $label, string $field): ?string
    {
        $str = is_scalar($value) ? (string) $value : '';
        switch ($name) {
            case 'required':
                return ($value === null || $value === '' || $value === []) ? "$label is required." : null;
            case 'email':
                return filter_var($str, FILTER_VALIDATE_EMAIL) ? null : "$label must be a valid email address.";
            case 'url':
                return (filter_var($str, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $str)) ? null : "$label must be a valid URL starting with http:// or https://.";
            case 'domain':
                return preg_match('/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.[a-z0-9-]{1,63})+$/i', $str) ? null : "$label must be a valid domain, e.g. example.com.";
            case 'phone':
                return preg_match('/^\+?[0-9 ()\-]{7,20}$/', $str) ? null : "$label must be a valid phone number.";
            case 'slug':
                return preg_match('/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/', $str) ? null : "$label may contain only lowercase letters, numbers, hyphens and underscores.";
            case 'min':
                return mb_strlen($str) >= (int) $arg ? null : "$label must be at least $arg characters.";
            case 'max':
                return mb_strlen($str) <= (int) $arg ? null : "$label may not be longer than $arg characters.";
            case 'integer':
                return filter_var($str, FILTER_VALIDATE_INT) !== false ? null : "$label must be a whole number.";
            case 'in':
                return in_array($str, explode(',', (string) $arg), true) ? null : "$label has an invalid value.";
            case 'hex':
                return preg_match('/^#[0-9a-f]{6}$/i', $str) ? null : "$label must be a colour like #1A2B3C.";
            case 'password':
                return (mb_strlen($str) >= 10 && preg_match('/[A-Za-z]/', $str) && preg_match('/\d/', $str))
                    ? null : "$label must be at least 10 characters and include letters and numbers.";
            case 'confirmed':
                return $str === (string) ($this->data[$field . '_confirmation'] ?? '') ? null : "$label confirmation does not match.";
            case 'unique':
                [$table, $column, $ignore] = array_pad(explode(',', (string) $arg), 3, null);
                Database::assertIdentifier((string) $table);
                Database::assertIdentifier((string) $column);
                $sql = "SELECT COUNT(*) FROM `$table` WHERE `$column` = ?";
                $params = [$str];
                if ($ignore !== null && $ignore !== '') {
                    $sql .= ' AND id <> ?';
                    $params[] = (int) $ignore;
                }
                return (int) Database::value($sql, $params) === 0 ? null : "This $label is already in use.";
            case 'exists':
                [$table, $column] = array_pad(explode(',', (string) $arg), 2, 'id');
                Database::assertIdentifier((string) $table);
                Database::assertIdentifier((string) $column);
                return (int) Database::value("SELECT COUNT(*) FROM `$table` WHERE `$column` = ?", [$str]) > 0 ? null : "Selected $label does not exist.";
        }
        return null;
    }
}
