@extends('layouts.app')

@section('content')
    <div class="container py-4">

        {{-- DESKTOP --}}
        <div class="d-none d-lg-block">
            @include('backoffice.partial.request_table')
        </div>

        {{-- MOBILE --}}
        <div class="d-lg-none">
            @include('backoffice.partial.requestcard')
        </div>

    </div>
@endsection
