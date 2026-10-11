<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-ss-primary']) }}>
    {{ $slot }}
</button>
