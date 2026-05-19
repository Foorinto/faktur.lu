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

        // Projects (FEAT-081)
        Route::get('/projets', [EmployeePortal\PortalProjectController::class, 'index'])->name('projects.index');
        Route::get('/projets/{projectId}', [EmployeePortal\PortalProjectController::class, 'show'])->whereNumber('projectId')->name('projects.show');
        Route::patch('/taches/{taskId}/status', [EmployeePortal\PortalTaskController::class, 'updateStatus'])->whereNumber('taskId')->name('tasks.status');
        Route::post('/taches/{taskId}/toggle', [EmployeePortal\PortalTaskController::class, 'toggle'])->whereNumber('taskId')->name('tasks.toggle');

        // Shared calendar (FEAT-079)
        Route::get('/calendrier-partage', [EmployeePortal\PortalSharedCalendarController::class, 'index'])->name('shared-calendar.index');
        Route::get('/calendrier-partage/events', [EmployeePortal\PortalSharedCalendarController::class, 'events'])->name('shared-calendar.events');
        Route::get('/calendrier-partage/evenements/create', [EmployeePortal\PortalSharedCalendarController::class, 'createEvent'])->name('shared-calendar.events.create');
        Route::post('/calendrier-partage/evenements', [EmployeePortal\PortalSharedCalendarController::class, 'storeEvent'])->name('shared-calendar.events.store');
        Route::get('/calendrier-partage/evenements/{event}', [EmployeePortal\PortalSharedCalendarController::class, 'showEvent'])->name('shared-calendar.events.show');
        Route::get('/calendrier-partage/evenements/{event}/edit', [EmployeePortal\PortalSharedCalendarController::class, 'editEvent'])->name('shared-calendar.events.edit');
        Route::put('/calendrier-partage/evenements/{event}', [EmployeePortal\PortalSharedCalendarController::class, 'updateEvent'])->name('shared-calendar.events.update');
        Route::delete('/calendrier-partage/evenements/{event}', [EmployeePortal\PortalSharedCalendarController::class, 'destroyEvent'])->name('shared-calendar.events.destroy');
    });
});
