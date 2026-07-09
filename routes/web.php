<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisposalController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentHistoryController;
use App\Http\Controllers\EquipmentInspectionController;
use App\Http\Controllers\EquipmentReceivingController;
use App\Http\Controllers\EquipmentRedistributionController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkshopController;
use Illuminate\Support\Facades\Route;

// Auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.forgot');
    Route::post('/forgot-password', [AuthController::class, 'sendOtp'])->name('password.send-otp');
    Route::get('/reset-password', [AuthController::class, 'showResetWithOtp'])->name('password.reset.otp');
    Route::post('/reset-password', [AuthController::class, 'resetWithOtp'])->name('password.reset');
});

// MFA verification (auth but MFA not yet verified)
Route::middleware('auth')->group(function () {
    Route::get('/mfa/verify', [AuthController::class, 'showMfaVerify'])->name('mfa.verify');
    Route::post('/mfa/verify', [AuthController::class, 'verifyMfa'])->name('mfa.verify.post');
    Route::get('/mfa/setup', [AuthController::class, 'showMfaSetup'])->name('mfa.setup');
    Route::post('/mfa/setup', [AuthController::class, 'enableMfa'])->name('mfa.enable');
    Route::post('/mfa/disable', [AuthController::class, 'disableMfa'])->name('mfa.disable');

    // Force password change
    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'changePassword'])->name('password.update');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Authenticated & MFA verified routes
Route::middleware(['auth', 'mfa', 'force.password'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Report exports (must be before resource routes so 'export' isn't treated as a record ID)
    Route::get('workshop/lookup-serial', [WorkshopController::class, 'lookupSerial'])->name('workshop.lookup-serial');
    Route::get('workshop/export', [ReportExportController::class, 'workshop'])->name('workshop.export');
    Route::get('equipment-receiving/export', [ReportExportController::class, 'equipmentReceiving'])->name('equipment-receiving.export')->middleware('role:super_admin,hod');
    Route::get('disposal/export', [ReportExportController::class, 'disposal'])->name('disposal.export');
    Route::get('audit-trail/export', [ReportExportController::class, 'auditTrail'])->name('audit-trail.export')->middleware('role:super_admin,hod');

    // All Equipment (Workshop + Registry, combined view) — super_admin, hod only
    Route::get('equipment/export', [ReportExportController::class, 'equipment'])->name('equipment.export')->middleware('role:super_admin,hod');
    Route::get('equipment', [EquipmentController::class, 'index'])->name('equipment.index')->middleware('role:super_admin,hod');

    // Workshop Register — all roles
    Route::resource('workshop', WorkshopController::class)->except(['destroy']);
    Route::post('workshop/{workshop}/comment', [WorkshopController::class, 'addComment'])->name('workshop.comment');
    Route::post('workshop/{workshop}/assign-self', [WorkshopController::class, 'assignSelf'])->name('workshop.assign-self');

    // Equipment Receiving — super_admin, hod only
    Route::resource('equipment-receiving', EquipmentReceivingController::class)->except(['destroy'])->middleware('role:super_admin,hod');
    Route::get('equipment-receiving/{equipmentReceiving}/inspect', [EquipmentInspectionController::class, 'create'])->name('equipment-receiving.inspect')->middleware('role:super_admin,hod');
    Route::post('equipment-receiving/{equipmentReceiving}/inspect', [EquipmentInspectionController::class, 'store'])->name('equipment-receiving.inspect.store')->middleware('role:super_admin,hod');
    Route::get('equipment-receiving/{equipmentReceiving}/redistribute', [EquipmentRedistributionController::class, 'create'])->name('equipment-receiving.redistribute')->middleware('role:super_admin,hod');
    Route::post('equipment-receiving/{equipmentReceiving}/redistribute', [EquipmentRedistributionController::class, 'store'])->name('equipment-receiving.redistribute.store')->middleware('role:super_admin,hod');

    // Disposal — all roles (permissions handled in controller)
    Route::get('disposal/lookup-serial', [DisposalController::class, 'lookupSerial'])->name('disposal.lookup-serial');
    Route::resource('disposal', DisposalController::class)->except(['destroy']);
    Route::post('disposal/{disposal}/approve', [DisposalController::class, 'approve'])->name('disposal.approve');
    Route::post('disposal/{disposal}/reject', [DisposalController::class, 'reject'])->name('disposal.reject');
    Route::post('disposal/{disposal}/mark-disposed', [DisposalController::class, 'markDisposed'])->name('disposal.mark-disposed');

    // Users — hod and super_admin
    Route::middleware('role:super_admin,hod')->group(function () {
        Route::resource('users', UserController::class)->except(['destroy']);
        Route::post('users/{user}/block', [UserController::class, 'block'])->name('users.block');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    });
    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->middleware('role:super_admin')
        ->name('users.destroy');

    // Audit Trail — hod, super_admin
    Route::get('audit-trail', [AuditTrailController::class, 'index'])->name('audit-trail.index')->middleware('role:super_admin,hod');

    // Equipment History — hod, super_admin
    Route::get('equipment-history', [EquipmentHistoryController::class, 'index'])->name('equipment-history.index')->middleware('role:super_admin,hod');
    Route::get('equipment-history/suggest', [EquipmentHistoryController::class, 'suggest'])->name('equipment-history.suggest')->middleware('role:super_admin,hod');
    Route::post('equipment-history/ask', [EquipmentHistoryController::class, 'ask'])->name('equipment-history.ask')->middleware('role:super_admin,hod');

    // Database Backup — super_admin only
    Route::middleware('role:super_admin')->group(function () {
        Route::get('backup', [BackupController::class, 'index'])->name('backup.index');
        Route::get('backup/download', [BackupController::class, 'download'])->name('backup.download');
        Route::post('backup/sharepoint', [BackupController::class, 'uploadToSharePoint'])->name('backup.sharepoint');
    });
});
