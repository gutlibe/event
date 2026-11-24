<?php

declare(strict_types=1);

namespace App;

final class Validator
{
    /**
     * Validate input. Rules are "required|email|max:190" style strings.
     *
     * @return array<string, mixed> the validated data
     *
     * @throws ValidationException
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        $clean = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            $fieldRules = explode('|', $ruleString);
            $nullable = in_array('nullable', $fieldRules, true);

            if (($value === null || $value === '') && $nullable) {
                $clean[$field] = null;

                continue;
            }

            if ($value === null || $value === '') {
                $value = '';
            }

            foreach ($fieldRules as $rule) {
                if (in_array($rule, ['required', 'nullable', 'string'], true)) {
                    if ($rule === 'required' && ($value === '' || $value === null)) {
                        $errors[$field] ??= 'This field is required.';
                    }

                    continue;
                }

                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $string = (string) $value;
                $length = mb_strlen($string);

                switch ($name) {
                    case 'email':
                        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                            $errors[$field] ??= 'Enter a valid email address.';
                        }
                        break;

                    case 'integer':
                        if ($value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $errors[$field] ??= 'Enter a whole number.';
                        }
                        break;

                    case 'numeric':
                        if ($value !== '' && ! is_numeric($string)) {
                            $errors[$field] ??= 'Enter a number.';
                        }
                        break;

                    case 'min':
                        $min = (int) $param;

                        if (is_numeric($string)) {
                            if ((float) $string < $min) {
                                $errors[$field] ??= 'Must be at least '.$min.'.';
                            }
                        } elseif ($length < $min) {
                            $errors[$field] ??= 'Must be at least '.$min.' characters.';
                        }
                        break;

                    case 'max':
                        $max = (int) $param;

                        if (is_numeric($string) && mb_strlen($string) <= 12) {
                            if ((float) $string > $max) {
                                $errors[$field] ??= 'Must be no more than '.$max.'.';
                            }
                        } elseif ($length > $max) {
                            $errors[$field] ??= 'Must be no more than '.$max.' characters.';
                        }
                        break;

                    case 'in':
                        $allowed = explode(',', (string) $param);

                        if ($value !== '' && ! in_array($string, $allowed, true)) {
                            $errors[$field] ??= 'Choose a valid option.';
                        }
                        break;

                    case 'date':
                        if ($value !== '' && strtotime($string) === false) {
                            $errors[$field] ??= 'Enter a valid date and time.';
                        }
                        break;

                    case 'after':
                        $other = $param === 'now'
                            ? time()
                            : strtotime((string) ($data[$param] ?? ''));

                        if ($value !== '' && $other !== false && strtotime($string) !== false && strtotime($string) <= $other) {
                            $errors[$field] ??= $name === 'after' && $param === 'now'
                                ? 'This must be in the future.'
                                : 'This must be after '.str_replace('_', ' ', (string) $param).'.';
                        }
                        break;

                    case 'confirmed':
                        if (($data[$field.'_confirmation'] ?? null) !== $value) {
                            $errors[$field] ??= 'The confirmation does not match.';
                        }
                        break;

                    case 'timezone':
                        try {
                            new \DateTimeZone($string);
                        } catch (\Exception) {
                            $errors[$field] ??= 'Choose a valid timezone.';
                        }
                        break;

                    case 'url':
                        if ($value !== '' && filter_var($value, FILTER_VALIDATE_URL) === false) {
                            $errors[$field] ??= 'Enter a valid URL.';
                        }
                        break;
                }
            }

            $clean[$field] = $value === '' ? null : $value;
        }

        if ($errors !== []) {
            throw new ValidationException($errors, $data);
        }

        return $clean;
    }
}
