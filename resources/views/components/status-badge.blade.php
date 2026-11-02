@php
$map = [
    'Pending'                       => 'badge-pending',
    'In Progress'                   => 'badge-progress',
    'Clear'                         => 'badge-clear',
    'Amber'                         => 'badge-amber',
    'Discrepancy Found'             => 'badge-discrepancy',
    'Insufficiency'                 => 'badge-insufficiency',
    'Failed'                        => 'badge-failed',
    'Completed'                     => 'badge-completed',
    'On Hold'                       => 'badge-onhold',
    'Completed - Green (Clear)'     => 'badge-clear',
    'Completed - Discrepancy Found' => 'badge-discrepancy',
];
$class = $map[$status] ?? 'badge-pending';
@endphp
<span class="{{ $class }}">
    @if($status === 'Completed - Green (Clear)') Clear (Completed)
    @elseif($status === 'Completed - Discrepancy Found') Discrepancy (Completed)
    @else {{ $status }}
    @endif
</span>
