import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

Alpine.plugin(collapse);
Alpine.plugin(focus);

Alpine.data('checkFieldEditor', (config) => ({
    open: Boolean(config.open),
    checkName: config.checkName || '',
    sections: [],
    initial: null,
    initialDraft: null,
    fieldTypes: [
        { value: 'text', label: 'Short text' },
        { value: 'textarea', label: 'Long text' },
        { value: 'number', label: 'Number' },
        { value: 'email', label: 'Email address' },
        { value: 'tel', label: 'Phone number' },
        { value: 'date', label: 'Date' },
        { value: 'file', label: 'Document upload' },
        { value: 'radio', label: 'Single choice' },
        { value: 'select', label: 'Dropdown' },
        { value: 'checkbox', label: 'Checkbox' },
    ],
    init() {
        this.sections = (config.sections || []).map(section => ({
            ...section,
            fields: (section.fields || []).map(field => ({
                ...field,
                type: field.type || (field.name?.startsWith('_file') ? 'file' : 'text'),
                optionsText: Array.isArray(field.options) ? field.options.join('\n') : (field.options || ''),
                required: Boolean(field.required),
                placeholder: field.placeholder || '',
                help: field.help || '',
                detailsOpen: false,
            })),
        }));
        this.initialDraft = JSON.stringify({ checkName: this.checkName, sections: this.sections });
        this.initial = this.snapshot();
    },
    get fieldCount() {
        return this.sections.reduce((total, section) => total + section.fields.length, 0);
    },
    get payloadSections() {
        return this.sections.filter(section => section.fields.length > 0).map(section => ({
            key: section.key,
            label: section.label,
            fields: section.fields.map(field => ({
                name: field.name,
                label: field.label,
                type: field.type,
                options: ['radio', 'select'].includes(field.type)
                    ? field.optionsText.split(/\r?\n/).map(option => option.trim()).filter(Boolean)
                    : [],
                required: field.required,
                placeholder: field.placeholder,
                help: field.help,
            })),
        }));
    },
    snapshot() {
        return JSON.stringify({ checkName: this.checkName, sections: this.payloadSections });
    },
    get hasChanges() {
        return this.initial !== null && this.snapshot() !== this.initial;
    },
    newKey(prefix) {
        const random = window.crypto?.randomUUID
            ? window.crypto.randomUUID().replace(/-/g, '')
            : Date.now().toString(36) + Math.random().toString(36).slice(2, 12);
        return prefix + random;
    },
    addSection() {
        if (this.sections.length >= 20) return;
        this.sections.push({ key: this.newKey('section_'), label: 'New section', fields: [] });
        this.$nextTick(() => {
            const inputs = this.$root.querySelectorAll('[data-section-name]');
            inputs[inputs.length - 1]?.focus();
        });
    },
    removeSection(index) {
        if (this.sections[index].fields.length && !window.confirm('Remove this section and its fields from the form? Previously saved values and files will be kept.')) return;
        this.sections.splice(index, 1);
    },
    addField(section) {
        if (section.fields.length >= 100 || this.fieldCount >= 200) return;
        section.fields.push({ name: this.newKey('field_'), label: '', type: 'text', optionsText: '', required: false, placeholder: '', help: '', detailsOpen: false });
        this.$nextTick(() => {
            const inputs = this.$root.querySelectorAll('[data-field-name]');
            const empty = [...inputs].reverse().find(input => !input.value);
            empty?.focus();
        });
    },
    move(items, index, direction) {
        const next = index + direction;
        if (next < 0 || next >= items.length) return;
        [items[index], items[next]] = [items[next], items[index]];
    },
    cancel() {
        if (this.hasChanges && !window.confirm('Discard your unsaved field changes?')) return;
        const initial = JSON.parse(this.initialDraft);
        this.checkName = initial.checkName;
        this.sections = initial.sections;
        this.open = false;
    },
}));

window.Alpine = Alpine;
Alpine.start();
