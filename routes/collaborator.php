<?php

use App\Http\Controllers\Collaborator\CollaboratorDashboardController;
use App\Http\Controllers\Collaborator\CollaboratorInvitationController;
use App\Http\Controllers\Collaborator\CollaboratorProjectController;
use App\Http\Controllers\Collaborator\CollaboratorTaskController;
use App\Http\Controllers\Collaborator\CollaboratorTimeTrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Collaborator Routes
|--------------------------------------------------------------------------
|
| Routes for the collaborator portal. These routes are prefixed with
| /collaborateur and use the standard web guard with collaborator middleware.
|
*/

Route::prefix('collaborateur')->name('collaborator.')->group(function () {
    // Invitation acceptance (no auth required)
    Route::get('/invitation/{token}', [CollaboratorInvitationController::class, 'show'])->name('invitation.show');
    Route::post('/invitation/{token}', [CollaboratorInvitationController::class, 'accept'])->name('invitation.accept');

    // Authenticated collaborator routes
    Route::middleware(['auth', 'verified', 'collaborator'])->group(function () {
        Route::get('/', [CollaboratorDashboardController::class, 'index'])->name('dashboard');

        // Projects
        Route::get('/projets', [CollaboratorProjectController::class, 'index'])->name('projects.index');
        Route::get('/projets/create', [CollaboratorProjectController::class, 'create'])->name('projects.create');
        Route::post('/projets', [CollaboratorProjectController::class, 'store'])->name('projects.store');
        Route::get('/projets/{project}', [CollaboratorProjectController::class, 'show'])->name('projects.show');
        Route::put('/projets/{project}', [CollaboratorProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projets/{project}', [CollaboratorProjectController::class, 'destroy'])->name('projects.destroy');

        // Tasks (nested under projects)
        Route::post('/projets/{project}/taches', [CollaboratorTaskController::class, 'store'])->name('tasks.store');
        Route::put('/taches/{task}', [CollaboratorTaskController::class, 'update'])->name('tasks.update');
        Route::delete('/taches/{task}', [CollaboratorTaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('/taches/{task}/toggle', [CollaboratorTaskController::class, 'toggle'])->name('tasks.toggle');

        // Time Tracking
        Route::get('/temps', [CollaboratorTimeTrackingController::class, 'index'])->name('time.index');
        Route::post('/temps', [CollaboratorTimeTrackingController::class, 'store'])->name('time.store');
        Route::put('/temps/{timeEntry}', [CollaboratorTimeTrackingController::class, 'update'])->name('time.update');
        Route::delete('/temps/{timeEntry}', [CollaboratorTimeTrackingController::class, 'destroy'])->name('time.destroy');
        Route::post('/temps/start', [CollaboratorTimeTrackingController::class, 'start'])->name('time.start');
        Route::post('/temps/{timeEntry}/stop', [CollaboratorTimeTrackingController::class, 'stop'])->name('time.stop');
    });
});
