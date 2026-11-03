<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayslipController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/', fn () => request()->user()->isStaff()
        ? redirect()->route('payslips.mine')
        : redirect()->route('dashboard'))->name('home');

    Route::get('/payslips/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');
    Route::get('/my/payslips', [PayslipController::class, 'mine'])->name('payslips.mine');

    Route::middleware('role:payroll,owner')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/staff', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/staff/new', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/staff', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/staff/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('/staff/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/staff/{employee}', [EmployeeController::class, 'update'])->name('employees.update');

        Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');
        Route::get('/grades/{grade}/edit', [GradeController::class, 'edit'])->name('grades.edit');
        Route::put('/grades/{grade}', [GradeController::class, 'update'])->name('grades.update');

        Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
        Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
        Route::delete('/leave/{leave}', [LeaveController::class, 'destroy'])->name('leave.destroy');

        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::get('/payroll/{run}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::get('/payroll/{run}/export', [PayrollController::class, 'export'])->name('payroll.export');
        Route::delete('/payroll/{run}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
        Route::post('/payroll/{run}/approve', [PayrollController::class, 'approve'])
            ->middleware('role:owner')
            ->name('payroll.approve');
    });
});
