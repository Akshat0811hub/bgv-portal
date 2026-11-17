<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Check;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckController extends Controller
{
    public function index()
    {
        $checks = Check::orderBy('id')->get();

        return view('admin.checks.index', compact('checks'));
    }

    public function update(Request $request, Check $check)
    {
        if ($request->boolean('reset_default')) {
            $defaults = config('checks');
            $matched = null;
            foreach ($defaults as $name => $def) {
                $slug = strtolower(trim(preg_replace('/[^\w]+/', '_', $name)));
                if ($slug === $check->slug) {
                    $matched = $def;
                    break;
                }
            }
            if ($matched !== null) {
                $check->update(['schema' => $matched]);
            }

            return back()->with('success', "{$check->name} reset to system default fields.");
        }

        $schema = [];

        foreach ((array) $request->input('existing_fields', []) as $row) {
            if (empty($row['enabled'])) {
                continue;
            }
            $section = trim((string) ($row['section'] ?? '_self')) ?: '_self';
            $name    = trim((string) ($row['name'] ?? ''));
            $label   = trim((string) ($row['label'] ?? ''));
            $type    = in_array($row['type'] ?? 'text', ['text', 'date', 'file', 'radio']) ? $row['type'] : 'text';

            if ($name === '' || $label === '') {
                continue;
            }

            $field = ['name' => $name, 'label' => $label];
            if ($type !== 'text') {
                $field['type'] = $type;
            }
            if ($type === 'radio' && ! empty($row['options'])) {
                $opts = array_values(array_filter(array_map('trim', explode(',', (string) $row['options']))));
                if (! empty($opts)) {
                    $field['options'] = $opts;
                }
            }

            $schema[$section][] = $field;
        }

        foreach ((array) $request->input('new_fields', []) as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $section = trim((string) ($row['section'] ?? '_self')) ?: '_self';
            $type    = in_array($row['type'] ?? 'text', ['text', 'date', 'file', 'radio']) ? $row['type'] : 'text';
            $slugName = Str::camel(Str::slug($label, '_')) ?: ('field_' . Str::random(5));
            if ($type === 'file' && ! str_starts_with($slugName, '_file')) {
                $slugName = '_file_' . ltrim($slugName, '_');
            }

            $field = ['name' => $slugName, 'label' => $label];
            if ($type !== 'text') {
                $field['type'] = $type;
            }
            if ($type === 'radio' && ! empty($row['options'])) {
                $opts = array_values(array_filter(array_map('trim', explode(',', (string) $row['options']))));
                if (! empty($opts)) {
                    $field['options'] = $opts;
                }
            }

            $schema[$section][] = $field;
        }

        $check->update([
            'schema' => $schema,
            'active' => $request->boolean('active', true),
        ]);

        return back()->with('success', "{$check->name} fields updated globally.");
    }
}
