@foreach (['success' => 'fa-circle-check', 'error' => 'fa-circle-exclamation', 'info' => 'fa-circle-info'] as $kind => $icon)
    @if (session($kind))
        <div class="es-alert es-alert--{{ $kind }}" role="{{ $kind === 'error' ? 'alert' : 'status' }}">
            <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
            <div>{{ session($kind) }}</div>
        </div>
    @endif
@endforeach
@if ($errors->any())
    <div class="es-alert es-alert--error" role="alert">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
