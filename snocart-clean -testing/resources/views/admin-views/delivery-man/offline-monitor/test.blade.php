@extends('layouts.admin.app')

@section('title', 'Test Offline Monitor')

@section('content')
<div class="content container-fluid">
    <h1>Offline Monitor - Test Page</h1>
    <p>If you can see this, the route and basic setup is working!</p>

    <div class="alert alert-success">
        <strong>Success!</strong> The offline monitoring system is properly configured.
    </div>

    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">Back to Dashboard</a>
</div>
@endsection
