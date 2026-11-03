<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Support\Money;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index()
    {
        return view('grades.index', [
            'grades' => Grade::withCount(['employees' => fn ($q) => $q->active()])->orderBy('code')->get(),
        ]);
    }

    public function edit(Grade $grade)
    {
        return view('grades.edit', ['grade' => $grade]);
    }

    public function update(Request $request, Grade $grade)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'basic' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'housing' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'transport' => ['required', 'numeric', 'min:0', 'max:100000000'],
        ]);

        $grade->update([
            'name' => $data['name'],
            'basic' => Money::fromNaira($data['basic']),
            'housing' => Money::fromNaira($data['housing']),
            'transport' => Money::fromNaira($data['transport']),
        ]);

        return redirect()->route('grades.index')->with('status', "Grade {$grade->code} updated.");
    }
}
