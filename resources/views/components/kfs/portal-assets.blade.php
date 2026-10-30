@props(['js' => false])
{{-- Loads the portal page stylesheet (auth / account / payments / lookup / syndicate) and, optionally, its small helper script. --}}
@once
    @push('css')
        <link rel="stylesheet" href="{{ asset('css/kfs-portal.css') }}?v={{ @filemtime(public_path('css/kfs-portal.css')) }}">
    @endpush
@endonce
@if ($js)
    @once
        @push('scripts')
            <script src="{{ asset('js/kfs-portal.js') }}?v={{ @filemtime(public_path('js/kfs-portal.js')) }}" defer></script>
        @endpush
    @endonce
@endif
