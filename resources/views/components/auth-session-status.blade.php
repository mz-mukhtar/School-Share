@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-ss alert-ss-success']) }}>
        {{ $status }}
    </div>
@endif
