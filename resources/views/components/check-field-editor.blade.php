@props(['check', 'case', 'schema' => [], 'action', 'canReset' => true])
@php
    $editorSections = [];
    foreach ($schema as $key => $fields) {
        $editorSections[] = [
            'key' => $key,
            'label' => $fields[0]['section_label'] ?? ($key === '_self' ? 'General details' : $key),
            'fields' => collect($fields)->map(fn ($field) => [
                'name' => $field['name'],
                'label' => $field['label'] ?? Illuminate\Support\Str::headline($field['name']),
                'type' => $field['type'] ?? (str_starts_with($field['name'], '_file') ? 'file' : 'text'),
                'options' => $field['options'] ?? [],
                'placeholder' => $field['placeholder'] ?? '',
                'help' => $field['help'] ?? '',
                'required' => (bool) ($field['required'] ?? false),
            ])->values()->all(),
        ];
    }
    $restoreEditor = (string) old('editing_check_id') === (string) $check->id;
    if ($restoreEditor && is_string(old('sections_payload'))) {
        $previous = json_decode(old('sections_payload'), true);
        if (is_array($previous)) $editorSections = $previous;
    }
    $editorConfig = [
        'sections' => $editorSections,
        'checkName' => $restoreEditor ? old('check_name', $check->custom_name ?: $check->display_name) : ($check->custom_name ?: ($check->display_name ?: Illuminate\Support\Str::headline($check->check_type))),
        'open' => $restoreEditor,
    ];
    $editorId = 'field-editor-'.$check->id;
@endphp
<section class="field-customizer" x-data="checkFieldEditor({{ Illuminate\Support\Js::from($editorConfig) }})" data-testid="field-editor-{{ $check->id }}" aria-label="Customize check fields">
    <div class="field-customizer-toolbar">
        <div class="field-customizer-intro">
            <span class="field-customizer-icon" aria-hidden="true"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 7h10m4 0h2M4 17h2m4 0h10M8 4v6m8 4v6M14 7a2 2 0 104 0 2 2 0 00-4 0zM6 17a2 2 0 104 0 2 2 0 00-4 0z"/></svg></span>
            <div><h3>Make this check your own</h3><p>Rename, arrange or add the details you need.</p></div>
        </div>
        <button type="button" class="btn-outline" @click="open = !open" :aria-expanded="open" aria-controls="{{ $editorId }}" data-testid="customize-fields-button">
            <span x-text="open ? 'Hide editor' : 'Customize fields'">Customize fields</span>
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="open ? 'rotate-180' : ''" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6"/></svg>
        </button>
    </div>
    <div x-show="open" x-collapse style="display:none" id="{{ $editorId }}">
        <div class="field-editor-body">
            <div class="field-editor-heading">
                <div><span class="field-editor-kicker">CHECK FIELD DESIGNER</span><h4>Build a form that fits this case</h4><p>Changes apply to this check in this case. Saved answers and uploaded documents are preserved.</p></div>
                <span class="field-editor-count" x-text="fieldCount + ' fields · ' + sections.length + ' sections'"></span>
            </div>
            <form method="POST" action="{{ $action }}" class="field-editor-form">
                @csrf @method('PUT')
                <input type="hidden" name="editing_check_id" value="{{ $check->id }}">
                <input type="hidden" name="sections_payload" :value="JSON.stringify(payloadSections)">
                <label class="field-editor-check-name"><span>Check display name</span><input type="text" name="check_name" x-model="checkName" maxlength="150" required class="form-input" placeholder="e.g. Previous employment verification" data-testid="check-display-name"><small>This name appears on the case and its report.</small></label>
                <div class="field-editor-section-list">
                    <template x-for="(section, sectionIndex) in sections" :key="section.key">
                        <article class="field-editor-section" data-testid="editor-section">
                            <div class="field-editor-section-head">
                                <span class="field-editor-number" x-text="String(sectionIndex + 1).padStart(2, '0')"></span>
                                <label class="field-editor-section-name"><span class="field-editor-label">Section title</span><input type="text" x-model="section.label" class="form-input" maxlength="120" required data-section-name :aria-label="'Section ' + (sectionIndex + 1) + ' title'" data-testid="section-label"></label>
                                <div class="field-editor-order-actions">
                                    <button type="button" @click="move(sections, sectionIndex, -1)" :disabled="sectionIndex === 0" aria-label="Move section up" title="Move section up">↑</button>
                                    <button type="button" @click="move(sections, sectionIndex, 1)" :disabled="sectionIndex === sections.length - 1" aria-label="Move section down" title="Move section down">↓</button>
                                    <button type="button" class="field-editor-delete" @click="removeSection(sectionIndex)" aria-label="Remove section" title="Remove section">×</button>
                                </div>
                            </div>
                            <div class="field-editor-fields">
                                <template x-for="(field, fieldIndex) in section.fields" :key="field.name">
                                    <div class="field-editor-field" data-testid="editor-field">
                                        <div class="field-editor-field-main">
                                            <span class="field-editor-field-position" x-text="fieldIndex + 1"></span>
                                            <label class="field-editor-field-label"><span class="field-editor-label">Field label</span><input type="text" x-model="field.label" maxlength="150" required class="form-input" placeholder="e.g. Employer name" data-field-name data-testid="field-label" :aria-label="'Field ' + (fieldIndex + 1) + ' label in ' + section.label"></label>
                                            <label class="field-editor-field-type"><span class="field-editor-label">Answer type</span><select x-model="field.type" class="form-select" data-testid="field-type" :aria-label="'Answer type for ' + (field.label || 'new field')"><template x-for="type in fieldTypes" :key="type.value"><option :value="type.value" x-text="type.label"></option></template></select></label>
                                            <div class="field-editor-order-actions field-editor-field-actions">
                                                <button type="button" @click="move(section.fields, fieldIndex, -1)" :disabled="fieldIndex === 0" :aria-label="'Move ' + (field.label || 'field') + ' up'" title="Move field up">↑</button>
                                                <button type="button" @click="move(section.fields, fieldIndex, 1)" :disabled="fieldIndex === section.fields.length - 1" :aria-label="'Move ' + (field.label || 'field') + ' down'" title="Move field down">↓</button>
                                                <button type="button" class="field-editor-delete" @click="section.fields.splice(fieldIndex, 1)" :aria-label="'Remove ' + (field.label || 'field')" title="Remove field">×</button>
                                            </div>
                                        </div>
                                        <div class="field-editor-field-settings">
                                            <label class="field-editor-required"><input type="checkbox" x-model="field.required" :aria-label="'Require ' + (field.label || 'field')"> Required</label>
                                            <span class="field-editor-file-note" x-show="field.type === 'file'">Supports multiple documents and folder selection</span>
                                            <button type="button" @click="field.detailsOpen = !field.detailsOpen" :aria-expanded="field.detailsOpen" class="field-editor-details-toggle"><span x-text="field.detailsOpen ? 'Less settings' : 'More settings'"></span><span aria-hidden="true" x-text="field.detailsOpen ? '−' : '+'"></span></button>
                                        </div>
                                        <div class="field-editor-options" x-show="['radio', 'select'].includes(field.type)">
                                            <label><span class="field-editor-label">Answer choices</span><textarea x-model="field.optionsText" class="form-textarea" rows="3" :required="['radio', 'select'].includes(field.type)" placeholder="Add one option per line" :aria-label="'Answer choices for ' + (field.label || 'new field')" data-testid="field-options"></textarea><small>Enter one option per line.</small></label>
                                        </div>
                                        <div class="field-editor-extra-settings" x-show="field.detailsOpen" x-collapse>
                                            <label x-show="!['file', 'radio', 'checkbox'].includes(field.type)"><span class="field-editor-label">Placeholder</span><input type="text" x-model="field.placeholder" class="form-input" maxlength="250" placeholder="Hint inside the input" :aria-label="'Placeholder for ' + (field.label || 'new field')"></label>
                                            <label><span class="field-editor-label">Helper text</span><input type="text" x-model="field.help" class="form-input" maxlength="500" placeholder="Explain what to enter or upload" :aria-label="'Helper text for ' + (field.label || 'new field')"></label>
                                        </div>
                                    </div>
                                </template>
                                <div class="field-editor-empty" x-show="section.fields.length === 0"><strong>Start with your first field</strong><p>Add the information or documents needed for this section.</p></div>
                            </div>
                            <button type="button" class="field-editor-add-field" @click="addField(section)" :disabled="section.fields.length >= 100 || fieldCount >= 200" data-testid="add-field"><span aria-hidden="true">＋</span> Add field</button>
                        </article>
                    </template>
                    <div class="field-editor-empty field-editor-empty-sections" x-show="sections.length === 0"><strong>Your form is a blank canvas</strong><p>Add a section, then add the fields you need. Existing answers and documents stay saved.</p></div>
                </div>
                <button type="button" class="field-editor-add-section" @click="addSection()" :disabled="sections.length >= 20" data-testid="add-section"><span aria-hidden="true">＋</span> Add section</button>
                <p class="field-editor-section-hint">Add at least one field to each section. Empty sections are not saved.</p>
                <div class="field-editor-footer"><div><strong x-show="hasChanges">You have unsaved changes</strong><span x-show="!hasChanges">Arrange the form, then save your changes.</span><small>Candidate forms and future report downloads will use this layout.</small></div><div class="field-editor-footer-actions"><button type="button" class="btn-outline" @click="cancel()">Cancel</button><button type="submit" class="btn-teal" data-testid="save-custom-fields">Save field layout</button></div></div>
            </form>
            @if($canReset)
                <form method="POST" action="{{ $action }}" class="field-editor-reset" onsubmit="return confirm('Restore the default check name and fields? Saved answers and documents will be kept.')">
                    @csrf @method('PUT')
                    <input type="hidden" name="reset_default" value="1">
                    <button type="submit">Restore default fields</button><span>Start again from the administrator's template.</span>
                </form>
            @endif
        </div>
    </div>
</section>
