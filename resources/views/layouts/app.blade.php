<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Payday') · {{ config('app.company.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @auth
        <header class="topbar no-print">
            <a class="brand" href="{{ route('home') }}">Payday</a>
            <nav>
                @if (auth()->user()->isStaff())
                    <a href="{{ route('payslips.mine') }}" @class(['active' => request()->routeIs('payslips.*')])>My payslips</a>
                @else
                    <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Dashboard</a>
                    <a href="{{ route('employees.index') }}" @class(['active' => request()->routeIs('employees.*')])>Staff</a>
                    <a href="{{ route('grades.index') }}" @class(['active' => request()->routeIs('grades.*')])>Grades</a>
                    <a href="{{ route('leave.index') }}" @class(['active' => request()->routeIs('leave.*')])>Leave</a>
                    <a href="{{ route('payroll.index') }}" @class(['active' => request()->routeIs('payroll.*', 'payslips.*')])>Payroll</a>
                @endif
            </nav>
            <div class="who">
                <span>{{ auth()->user()->name }} <small>({{ auth()->user()->roleLabel() }})</small></span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="link">Log out</button>
                </form>
            </div>
        </header>
    @endauth

    <main class="container">
        @if (session('status'))
            <div class="flash" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash error" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
