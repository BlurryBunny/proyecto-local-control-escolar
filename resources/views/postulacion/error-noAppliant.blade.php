<script> 
    const authUser = @json($user);
    const academicPrograms = @json($academic_programs);
    </script>
    
    @extends('layouts.app')
    
    @section('main')
    
    <p>no existe aplicante</p>
    
    @endsection
    
    {{-- @push('scripts')
    <script src="{{ asset('js/intention-letter.js') }}" defer></script>
    @endpush --}}