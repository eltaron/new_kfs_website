@if (session('success'))
    <div class="mkt-alert status-success" role="status">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif
@foreach (['error' => 'status-danger', 'warning' => 'status-pending', 'info' => 'status-info'] as $key => $class)
    @if (session($key))
        <div class="mkt-alert {{ $class }}" role="alert">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <div>{{ session($key) }}</div>
        </div>
    @endif
@endforeach
@if ($errors->any())
    <div class="mkt-alert status-danger" role="alert" tabindex="-1" id="mkt-errors">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <div>
            <strong>برجاء مراجعة ما يلي:</strong>
            <ul>
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @push('scripts')
        <script>document.getElementById('mkt-errors')?.focus();</script>
    @endpush
@endif
