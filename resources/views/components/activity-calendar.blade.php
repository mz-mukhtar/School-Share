@props(['contributions' => []])

<div class="activity-calendar">
    <div class="d-flex flex-wrap gap-1">
        @php
            $startDate = now()->subDays(364);
        @endphp
        @for($i = 0; $i < 365; $i++)
            @php
                $currentDate = $startDate->copy()->addDays($i)->format('Y-m-d');
                $count = $contributions[$currentDate] ?? 0;
                
                if ($count === 0) {
                    $color = 'var(--ss-dark-3)';
                } elseif ($count === 1) {
                    $color = 'rgba(10, 150, 10, 0.4)';
                } elseif ($count <= 3) {
                    $color = 'rgba(10, 180, 10, 0.6)';
                } elseif ($count <= 6) {
                    $color = 'rgba(10, 210, 10, 0.8)';
                } else {
                    $color = 'rgba(10, 255, 10, 1)';
                }
            @endphp
            <div style="width: 12px; height: 12px; border-radius: 2px; background-color: {{ $color }};" title="{{ $count }} contributions on {{ $currentDate }}"></div>
        @endfor
    </div>
    <div class="d-flex justify-content-between text-muted small mt-2">
        <span>Less</span>
        <div class="d-flex gap-1 align-items-center">
            <div style="width:12px; height:12px; background:var(--ss-dark-3); border-radius:2px;"></div>
            <div style="width:12px; height:12px; background:rgba(10,150,10,0.4); border-radius:2px;"></div>
            <div style="width:12px; height:12px; background:rgba(10,180,10,0.6); border-radius:2px;"></div>
            <div style="width:12px; height:12px; background:rgba(10,210,10,0.8); border-radius:2px;"></div>
            <div style="width:12px; height:12px; background:rgba(10,255,10,1); border-radius:2px;"></div>
        </div>
        <span>More</span>
    </div>
</div>
