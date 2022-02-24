<script> 
    const authUser = @json($user);
    const academicPrograms = @json($academic_programs);
    </script>
    
    @extends('layouts.app')
    
    @section('main')
    
    <p>cartas enviadas</p>

    @endsection
    
    {{-- @push('scripts')
    <script src="{{ asset('js/intention-letter.js') }}" defer></script>
    @endpush --}}