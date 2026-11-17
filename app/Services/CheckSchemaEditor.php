<?php

namespace App\Services;

use App\Models\CaseCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckSchemaEditor
{
    public const FIELD_TYPES = ['text', 'textarea', 'number', 'email', 'tel', 'date', 'file', 'radio', 'select', 'checkbox'];

    public function update(CaseCheck $check, Request $request): void
    {
        if ($request->boolean('reset_default')) {
            DB::transaction(function () use ($check): void {
                CaseCheck::whereKey($check->id)->lockForUpdate()->firstOrFail()
                    ->update(['custom_schema' => null, 'custom_name' => null]);
            });

            return;
        }

        $input = $request->all();
        if ($request->exists('sections_payload')) {
            $payload = $request->input('sections_payload');
            if (! is_string($payload) || strlen($payload) > 262144) {
                throw ValidationException::withMessages(['sections_payload' => 'The field editor payload is invalid or too large.']);
            }
            try {
                $input['sections'] = json_decode($payload, true, 64, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw ValidationException::withMessages(['sections_payload' => 'The field editor payload is invalid. Please reload the editor and try again.']);
            }
        }

        $validator = Validator::make($input, [
            'check_name' => ['nullable', 'string', 'max:150'],
            'sections' => ['present', 'array', 'max:20'],
            'sections.*' => ['array:key,label,fields'],
            'sections.*.key' => ['required', 'string', 'max:120', 'regex:/^[^\[\].\x00-\x1F\x7F]+$/u'],
            'sections.*.label' => ['required', 'string', 'max:120'],
            'sections.*.fields' => ['present', 'array', 'max:100'],
            'sections.*.fields.*' => ['array:name,label,type,options,placeholder,help,required'],
            'sections.*.fields.*.name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z_][A-Za-z0-9_]*$/'],
            'sections.*.fields.*.label' => ['required', 'string', 'max:150'],
            'sections.*.fields.*.type' => ['required', Rule::in(self::FIELD_TYPES)],
            'sections.*.fields.*.placeholder' => ['nullable', 'string', 'max:250'],
            'sections.*.fields.*.help' => ['nullable', 'string', 'max:500'],
            'sections.*.fields.*.required' => ['sometimes', 'boolean'],
            'sections.*.fields.*.options' => [function ($attribute, $value, $fail): void {
                if ((! is_string($value) && ! is_array($value)) || (is_string($value) && strlen($value) > 8000)) {
                    $fail('Options must be a list or comma-separated text.');
                } elseif (is_array($value) && (count($value) > 100 || ! array_is_list($value)
                    || collect($value)->contains(fn ($option) => ! is_string($option) || mb_strlen($option) > 150))) {
                    $fail('Use at most 100 text options, each no longer than 150 characters.');
                }
            }],
        ]);
        $validator->after(function ($validator) use ($input): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $sectionKeys = [];
            $fileNames = [];
            $total = 0;
            foreach ($input['sections'] as $sectionIndex => $section) {
                $sectionKey = trim($section['key']);
                if (in_array($sectionKey, $sectionKeys, true)) {
                    $validator->errors()->add("sections.$sectionIndex.key", 'Section identifiers must be unique.');
                }
                $sectionKeys[] = $sectionKey;
                $names = [];
                foreach ($section['fields'] as $fieldIndex => $field) {
                    $total++;
                    $base = "sections.$sectionIndex.fields.$fieldIndex";
                    if (in_array($field['name'], $names, true)) {
                        $validator->errors()->add($base.'.name', 'Each field needs a unique identifier within its section.');
                    }
                    $names[] = $field['name'];
                    if ($field['type'] === 'file') {
                        if (in_array($field['name'], $fileNames, true)) {
                            $validator->errors()->add($base.'.name', 'Upload fields must have unique identifiers across this check.');
                        }
                        $fileNames[] = $field['name'];
                    }
                    if (in_array($field['type'], ['radio', 'select'], true)) {
                        $options = $this->options($field['options'] ?? []);
                        if ($options === [] || count($options) > 100 || collect($options)->contains(fn ($option) => mb_strlen($option) > 150)) {
                            $validator->errors()->add($base.'.options', 'Provide between 1 and 100 options, each no longer than 150 characters.');
                        }
                    }
                }
            }
            if ($total > 200) {
                $validator->errors()->add('sections', 'A check can contain at most 200 fields.');
            }
        });
        $data = $validator->validate();
        $schema = [];
        foreach ($data['sections'] as $section) {
            $key = trim($section['key']);
            $schema[$key] = [];
            foreach ($section['fields'] as $field) {
                $definition = [
                    'name' => $field['name'],
                    'label' => trim($field['label']),
                    'type' => $field['type'],
                    'section_label' => trim($section['label']),
                    'required' => (bool) ($field['required'] ?? false),
                ];
                foreach (['placeholder', 'help'] as $meta) {
                    if (isset($field[$meta]) && trim($field[$meta]) !== '') {
                        $definition[$meta] = trim($field[$meta]);
                    }
                }
                if (in_array($field['type'], ['radio', 'select'], true)) {
                    $definition['options'] = $this->options($field['options'] ?? []);
                }
                $schema[$key][] = $definition;
            }
        }

        DB::transaction(function () use ($check, $schema, $data): void {
            $locked = CaseCheck::whereKey($check->id)->lockForUpdate()->firstOrFail();
            $updates = ['custom_schema' => $schema];
            if (array_key_exists('check_name', $data)) {
                $updates['custom_name'] = trim($data['check_name'] ?? '') ?: null;
            }
            $locked->update($updates);
        });
    }

    private function options(array|string $options): array
    {
        $options = is_string($options) ? explode(',', $options) : $options;

        return array_values(array_unique(array_filter(array_map('trim', $options), fn ($option) => $option !== '')));
    }
}
