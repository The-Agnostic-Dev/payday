@extends('layouts.app')

@section('title', 'Edit grade '.$grade->code)

@section('content')
    <h1>Edit grade {{ $grade->code }}</h1>

    <form method="POST" action="{{ route('grades.update', $grade) }}" class="card form-grid">
        @csrf
        @method('PUT')
        <label>Name <input name="name" value="{{ old('name', $grade->name) }}" required></label>
        <span></span>
        <label>Basic a month (₦)
            <input type="number" step="0.01" min="0" name="basic" value="{{ old('basic', \App\Support\Money::plain($grade->basic)) }}" required>
        </label>
        <label>Housing allowance a month (₦)
            <input type="number" step="0.01" min="0" name="housing" value="{{ old('housing', \App\Support\Money::plain($grade->housing)) }}" required>
        </label>
        <label>Transport allowance a month (₦)
            <input type="number" step="0.01" min="0" name="transport" value="{{ old('transport', \App\Support\Money::plain($grade->transport)) }}" required>
        </label>
        <div class="actions">
            <button type="submit">Save grade</button>
            <a href="{{ route('grades.index') }}">Cancel</a>
        </div>
    </form>
@endsection
