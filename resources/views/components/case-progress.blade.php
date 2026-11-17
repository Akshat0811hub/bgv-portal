@props(['case'])
@php
    $total = $case->checks->count();
    $complete = $case->checks->whereIn('status', ['Clear', 'Completed', 'Failed', 'Discrepancy Found', 'Amber'])->count();
    $percent = $total ? (int) round($complete / $total * 100) : 0;
    $fileCount = $case->checks->sum(fn ($check) => $check->documents->count());
@endphp
<section class="case-progress card" aria-label="Verification progress">
    <div class="case-progress-top">
        <div><span class="case-progress-eyebrow">Case overview</span><h2>Verification progress</h2><p>{{ $complete }} of {{ $total }} checks completed</p></div>
        <div class="case-progress-percent">{{ $percent }}<span>%</span></div>
    </div>
    <div class="case-progress-track" role="progressbar" aria-label="Completed checks" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"><div style="width: {{ $percent }}%"></div></div>
    <div class="case-progress-stats">
        <div><span class="case-progress-dot case-progress-dot-complete"></span><strong>{{ $complete }}</strong> Completed</div>
        <div><span class="case-progress-dot case-progress-dot-pending"></span><strong>{{ $total - $complete }}</strong> Remaining</div>
        <div><strong>{{ $fileCount }}</strong> Supporting {{ $fileCount === 1 ? 'file' : 'files' }}</div>
    </div>
</section>
