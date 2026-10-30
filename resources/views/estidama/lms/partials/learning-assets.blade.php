@push('css')
    <link rel="stylesheet" href="{{ asset('css/estidama-portal.css') }}?v={{ @filemtime(public_path('css/estidama-portal.css')) }}">
@endpush
@push('scripts')
    <script src="{{ asset('js/estidama-learning.js') }}?v={{ @filemtime(public_path('js/estidama-learning.js')) }}" defer></script>
@endpush
