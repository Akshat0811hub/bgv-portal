@php
$map = [
    'Pending'                       => ['class' => 'badge-pending',       'dot' => 'bg-amber-500'],
    'In Progress'                   => ['class' => 'badge-progress',      'dot' => 'bg-blue-500 animate-pulse'],
    'Clear'                         => ['class' => 'badge-clear',         'dot' => 'bg-emerald-500'],
    'Amber'                         => ['class' => 'badge-amber',         'dot' => 'bg-orange-500'],
    'Discrepancy Found'             => ['class' => 'badge-discrepancy',   'dot' => 'bg-red-500'],
    'Insufficiency'                 => ['class' => 'badge-insufficiency', 'dot' => 'bg-purple-500 animate-pulse'],
    'Failed'                        => ['class' => 'badge-failed',        'dot' => 'bg-rose-500'],
    'Completed'                     => ['class' => 'badge-completed',     'dot' => 'bg-teal-500'],
    'On Hold'                       => ['class' => 'badge-onhold',        'dot' => 'bg-slate-400'],
    'Completed - Green (Clear)'     => ['class' => 'badge-clear',         'dot' => 'bg-emerald-500'],
    'Completed - Discrepancy Found' => ['class' => 'badge-discrepancy',   'dot' => 'bg-red-500'],
];
$info = $map[$status] ?? ['class' => 'badge-pending', 'dot' => 'bg-amber-500'];
@endphp
<span class="{{ $info['class'] }}">
    <span class="w-1.5 h-1.5 rounded-full {{ $info['dot'] }} shrink-0"></span>
    @if($status === 'Completed - Green (Clear)') Clear (Completed)
    @elseif($status === 'Completed - Discrepancy Found') Discrepancy (Completed)
    @else {{ $status }}
    @endif
</span>
