<?php

use App\Http\Controllers\EmployeePortal;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Employee Portal Routes
|--------------------------------------------------------------------------
|
| Routes for the employee self-service portal. These routes are prefixed
| with /mon-espace-rh and use the employee.portal middleware.
|
*/

Route::prefix('mon-espace-rh')->name('employee-portal.')->group(function () {
    Route::middleware(['auth', 'verified', 'employee.portal'])->group(function () {
        Route::get('/', [EmployeePortal\PortalDashboardController::class, 'index'])->name('dashboard');

        // Leaves
        Route::get('/conges', [EmployeePortal\PortalLeaveController::class, 'index'])->name('leaves.index');
        Route::post('/conges', [EmployeePortal\PortalLeaveController::class, 'store'])->name('leaves.store');
        Route::patch('/conges/{leaveRequest}/cancel', [EmployeePortal\PortalLeaveController::class, 'cancel'])->name('leaves.cancel');
        Route::delete('/conges/{leaveRequest}', [EmployeePortal\PortalLeaveController::class, 'destroy'])->name('leaves.destroy');

        // Expenses
        Route::get('/frais', [EmployeePortal\PortalExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/frais', [EmployeePortal\PortalExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('/frais/{expenseReport}/justificatifs/{expenseReceipt}', [EmployeePortal\PortalExpenseController::class, 'deleteReceipt'])->name('expenses.receipts.destroy');
        Route::delete('/frais/{expenseReport}', [EmployeePortal\PortalExpenseController::class, 'destroy'])->name('expenses.destroy');

        // Documents (read-only)
        Route::get('/documents', [EmployeePortal\PortalDocumentController::class, 'index'])->name('documents.index');

        // Evaluations (read-only)
        Route::get('/evaluations', [EmployeePortal\PortalEvaluationController::class, 'index'])->name('evaluations.index');
        Route::get('/evaluations/{evaluation}', [EmployeePortal\PortalEvaluationController::class, 'show'])->name('evaluations.show');
        Route::get('/evaluations/{evaluation}/pdf', [EmployeePortal\PortalEvaluationController::class, 'pdf'])->name('evaluations.pdf');

        // Profile
        Route::get('/profil', [EmployeePortal\PortalProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profil', [EmployeePortal\PortalProfileController::class, 'update'])->name('profile.update');
    });
});
