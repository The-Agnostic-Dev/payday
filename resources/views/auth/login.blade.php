@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    <section class="card narrow">
        <h1>Payday</h1>
        <p class="muted">Payroll for {{ config('app.company.name') }}</p>

        <form method="POST" action="{{ url('/login') }}" class="stack">
            @csrf
            <label>Email
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>
            <label>Password
                <input type="password" name="password" required>
            </label>
            <label class="inline"><input type="checkbox" name="remember" value="1"> Keep me logged in</label>
            <button type="submit">Log in</button>
        </form>
    </section>
@endsection
