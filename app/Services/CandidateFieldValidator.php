<?php

namespace App\Services;

use App\Models\Cases;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CandidateFieldValidator
{
    /** Validate explicitly configured types and required fields against the case's own schema. */
    public function validate(Request $request, Cases $case, $schemas): void
    {
        $data = $request->all();
        $rules = [];
        $attributes = [];
        foreach ($case->checks as $check) {
            $slug = $check->check_type;
            $incoming = (array) $request->input("check_data.$slug", []);
            $data['check_data'][$slug] = array_replace_recursive($check->form_data ?? [], $incoming);
            foreach ($check->effectiveSchema($schemas[$slug] ?? []) as $section => $fields) {
                foreach ($fields as $field) {
                    $name = $field['name'];
                    $type = $field['type'] ?? (str_starts_with($name, '_file') ? 'file' : 'text');
                    $required = (bool) ($field['required'] ?? false);
                    $path = "check_data.$slug.$section.$name";
                    $attributes[$path] = $field['label'] ?? $name;
                    if ($type === 'file') {
                        $path = "check_files.$slug.$name";
                        $hasExistingFile = $check->documents()->where('field_key', $name)->exists();
                        $rules[$path] = [$required && ! $hasExistingFile ? 'required' : 'nullable', 'array', 'min:1'];
                        $attributes[$path] = $field['label'] ?? 'supporting documents';

                        continue;
                    }
                    $fieldRules = [$required ? 'required' : 'nullable'];
                    $fieldRules = array_merge($fieldRules, match ($type) {
                        'number' => ['numeric'],
                        'email' => ['email', 'max:255'],
                        'date' => ['date_format:Y-m-d'],
                        'checkbox' => [$required ? 'accepted' : 'boolean'],
                        'radio', 'select' => ['string', Rule::in($field['options'] ?? [])],
                        'textarea' => ['string', 'max:10000'],
                        default => ['string', 'max:2000'],
                    });
                    $rules[$path] = $fieldRules;
                }
            }
        }
        Validator::make($data, $rules, [], $attributes)->validate();
    }
}
