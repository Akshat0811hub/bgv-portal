<x-layouts.app title="New Case">

<div class="max-w-4xl">
    <div class="page-header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.cases.index') }}" class="text-[#64748b] hover:text-[#0f2044] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="page-title">Create New Case</h1>
                <p class="page-subtitle">Enter candidate details, choose checks, then send a link or enter the details yourself</p>
            </div>
        </div>
    </div>

    @if($errors->any())
    <div class="alert-error mb-5">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.cases.store') }}" enctype="multipart/form-data" class="space-y-5" id="create-case-form">
        @csrf

        {{-- Candidate info --}}
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-[#0f2044]">Candidate Information</h2></div>
            <div class="card-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="form-label">Client Organization <span class="text-red-500">*</span></label>
                        <select name="client_organization_id" required class="form-select">
                            <option value="">Select organization…</option>
                            @foreach($organizations as $org)
                            <option value="{{ $org->id }}" @selected(old('client_organization_id') == $org->id)>{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                        <input name="candidate_name" type="text" required class="form-input" value="{{ old('candidate_name') }}" placeholder="Candidate's full name">
                    </div>
                    <div>
                        <label class="form-label">Email Address <span class="text-red-500">*</span></label>
                        <input name="candidate_email" type="email" required class="form-input" value="{{ old('candidate_email') }}" placeholder="candidate@email.com">
                    </div>
                    <div>
                        <label class="form-label">Father's Name</label>
                        <input name="candidate_father_name" type="text" class="form-input" value="{{ old('candidate_father_name') }}" placeholder="Father's full name">
                    </div>
                    <div>
                        <label class="form-label">Phone Number</label>
                        <input name="candidate_phone" type="tel" class="form-input" value="{{ old('candidate_phone') }}" placeholder="+91 XXXXX XXXXX">
                    </div>
                    <div>
                        <label class="form-label">Designation / Role</label>
                        <input name="candidate_designation" type="text" class="form-input" value="{{ old('candidate_designation') }}" placeholder="e.g. Software Engineer">
                    </div>
                </div>
            </div>
        </div>

        {{-- Mode --}}
        <div class="card">
            <div class="card-header"><h2 class="font-semibold text-[#0f2044]">How will documents be collected?</h2></div>
            <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="js-mode-label flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all border-[#e2e8f0]" data-mode="send_link">
                    <input type="radio" name="mode" value="send_link" class="js-mode mt-0.5 text-[#0d9488] focus:ring-[#0d9488]" @checked(old('mode', 'send_link') === 'send_link')>
                    <div>
                        <div class="font-medium text-sm text-[#1e293b]">Send a link to the candidate</div>
                        <div class="text-xs text-[#64748b] mt-0.5">Candidate fills the details and uploads documents themselves.</div>
                    </div>
                </label>
                <label class="js-mode-label flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all border-[#e2e8f0]" data-mode="admin_upload">
                    <input type="radio" name="mode" value="admin_upload" class="js-mode mt-0.5 text-[#0d9488] focus:ring-[#0d9488]" @checked(old('mode') === 'admin_upload')>
                    <div>
                        <div class="font-medium text-sm text-[#1e293b]">I'll enter the details now</div>
                        <div class="text-xs text-[#64748b] mt-0.5">Enter known details and upload documents on the candidate's behalf.</div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Check selection --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="font-semibold text-[#0f2044]">Select Verification Checks</h2>
                    <p class="text-xs text-[#64748b] mt-0.5">Choose which checks to run</p>
                </div>
                <span class="text-sm font-semibold text-[#0d9488]" id="checks-count">0 selected</span>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach($checks as $check)
                    <label class="js-check-label relative flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all duration-150 border-[#e2e8f0] hover:border-[#0d9488]/40 hover:bg-[#f8fafc]" data-slug="{{ $check->slug }}">
                        <input type="checkbox" name="checks[]" value="{{ $check->slug }}"
                               class="js-check mt-0.5 w-4 h-4 text-[#0d9488] border-[#d1d5db] rounded focus:ring-[#0d9488]"
                               data-slug="{{ $check->slug }}"
                               @checked(in_array($check->slug, old('checks', [])))>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm text-[#1e293b]">{{ $check->name }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Schema fields — shown for each selected check --}}
        <div id="check-details-section" class="space-y-4" style="display:none">
            <div class="flex items-center gap-2 px-1">
                <h2 class="font-semibold text-[#0f2044]">Check Details</h2>
                <span class="text-xs text-[#64748b] js-sendlink-only">— optional, the candidate can complete the rest</span>
            </div>
            @foreach($checks as $check)
            <div class="js-panel card" data-slug="{{ $check->slug }}" style="display:none">
                <div class="card-header"><h3 class="font-semibold text-[#0f2044]">{{ $check->name }}</h3></div>
                <div class="card-body">
                    <x-check-fields :slug="$check->slug" :schema="$schemas[$check->slug] ?? []" />
                </div>
            </div>
            @endforeach
        </div>

        <div class="alert-info js-sendlink-only">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>The candidate will receive an email with a secure link to submit their documents. Anything you enter above is pre-filled for them.</span>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" id="submit-btn" class="btn-primary btn-lg" disabled>
                <span class="js-label-send">Create Case &amp; Notify Candidate</span>
                <span class="js-label-upload" style="display:none">Create Case</span>
            </button>
            <a href="{{ route('admin.cases.index') }}" class="btn-outline btn-lg">Cancel</a>
        </div>
    </form>
</div>

<script>
(function () {
    var SELECTED = ['border-[#0d9488]', 'bg-teal-50'];
    var UNSELECTED = ['border-[#e2e8f0]', 'hover:border-[#0d9488]/40', 'hover:bg-[#f8fafc]'];

    function setSelected(el, on, unselectedClasses) {
        if (on) {
            unselectedClasses.forEach(function (c) { el.classList.remove(c); });
            SELECTED.forEach(function (c) { el.classList.add(c); });
        } else {
            SELECTED.forEach(function (c) { el.classList.remove(c); });
            unselectedClasses.forEach(function (c) { el.classList.add(c); });
        }
    }

    function refreshChecks() {
        var boxes = document.querySelectorAll('.js-check');
        var count = 0;
        boxes.forEach(function (box) {
            var slug = box.getAttribute('data-slug');
            var panel = document.querySelector('.js-panel[data-slug="' + slug + '"]');
            var label = box.closest('.js-check-label');
            if (panel) panel.style.display = box.checked ? '' : 'none';
            if (label) setSelected(label, box.checked, UNSELECTED);
            if (box.checked) count++;
        });

        var countEl = document.getElementById('checks-count');
        if (countEl) countEl.textContent = count + ' selected';

        var section = document.getElementById('check-details-section');
        if (section) section.style.display = count > 0 ? '' : 'none';

        var submit = document.getElementById('submit-btn');
        if (submit) submit.disabled = count === 0;
    }

    function refreshMode() {
        var mode = (document.querySelector('.js-mode:checked') || {}).value || 'send_link';

        document.querySelectorAll('.js-mode-label').forEach(function (label) {
            setSelected(label, label.getAttribute('data-mode') === mode, ['border-[#e2e8f0]']);
        });

        var isSend = mode === 'send_link';
        document.querySelectorAll('.js-sendlink-only').forEach(function (el) {
            el.style.display = isSend ? '' : 'none';
        });
        var lblSend = document.querySelector('.js-label-send');
        var lblUpload = document.querySelector('.js-label-upload');
        if (lblSend) lblSend.style.display = isSend ? '' : 'none';
        if (lblUpload) lblUpload.style.display = isSend ? 'none' : '';
    }

    function init() {
        document.querySelectorAll('.js-check').forEach(function (box) {
            box.addEventListener('change', refreshChecks);
        });
        document.querySelectorAll('.js-mode').forEach(function (radio) {
            radio.addEventListener('change', refreshMode);
        });
        refreshChecks();
        refreshMode();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

</x-layouts.app>
