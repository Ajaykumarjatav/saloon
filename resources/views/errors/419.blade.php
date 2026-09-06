@extends('errors.layout')
@section('code', '419')
@section('title', 'Session Expired')
@section('message', 'Your session expired for security reasons. Sign in again to continue, or clear the old session and return to the login page.')
@section('actions')
    <a href="{{ route('logout') }}"
       class="px-6 py-3 text-sm font-semibold rounded-xl bg-velour-600 hover:bg-velour-700 text-white transition-colors">
        Sign In
    </a>
    <a href="javascript:history.back()"
       class="px-6 py-3 text-sm font-medium rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 transition-colors">
        Go Back
    </a>
@endsection
