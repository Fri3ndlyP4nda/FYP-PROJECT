<?php

use App\Http\Controllers\Admin\ApplicationManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Evaluator\ApplicationReviewController;
use App\Http\Controllers\Evaluator\AssessmentGradingController;
use App\Http\Controllers\Evaluator\AssessmentPaperController;
use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\Student\ApelAController;
use App\Http\Controllers\Student\ApelCController;
use App\Http\Controllers\Student\ApplicationController;
use App\Http\Controllers\Student\AssessmentSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

/*
 | Throttles on every credential-accepting endpoint.
 |
 | Named limiters are defined in AppServiceProvider::configureRateLimiters(); they
 | key on identity as well as IP so a shared campus NAT is not one bucket.
 |
 | The arithmetic captcha is the only anti-automation control here and a script
 | solves it with one regex plus an addition, so without these the six-digit OTP
 | is brute-forceable inside its own ten-minute window, passwords can be sprayed
 | across the whole user collection, and /forgot-password can be used to mail-bomb
 | any account and burn the sending quota.
 */
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:register')
    ->name('register.submit');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('login.submit');

Route::get('/2fa', [AuthController::class, 'showTwoFactor'])
    ->name('2fa.show');

Route::post('/2fa', [AuthController::class, 'verifyTwoFactor'])
    ->middleware('throttle:two-factor')
    ->name('2fa.verify');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])
    ->name('password.request');

Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
    ->middleware('throttle:password-reset')
    ->name('password.email');

Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
    ->name('password.reset');

Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
    ->middleware('throttle:password-reset')
    ->name('password.update');

Route::middleware('role:student')->group(function () {
    Route::get('/student/dashboard', [AuthController::class, 'studentDashboard'])
        ->name('student.dashboard');

    Route::get('/student/applications', [ApplicationController::class, 'index'])
        ->name('student.applications.index');

    Route::get('/student/applications/create', [ApplicationController::class, 'create'])
        ->name('student.applications.create');

    Route::post('/student/applications', [ApplicationController::class, 'store'])
        ->name('student.applications.store');

    Route::get('/student/apel-a/{id}', [ApelAController::class, 'show'])
        ->name('student.apel_a.show');

    Route::get('/student/applications/{applicationId}/assessment', [AssessmentSubmissionController::class, 'show'])
        ->name('student.assessment.show');

    Route::post('/student/applications/{applicationId}/assessment', [AssessmentSubmissionController::class, 'submit'])
        ->name('student.assessment.submit');

    Route::get('/student/apel-c/{id}', [ApelCController::class, 'show'])
        ->name('student.apel_c.show');

    Route::post('/student/applications/{id}/payment', [ApplicationController::class, 'submitPayment'])
        ->name('student.applications.payment');

    Route::post('/student/applications/{id}/appeal', [ApplicationController::class, 'submitAppeal'])
        ->name('student.applications.appeal');

    Route::post('/student/applications/{id}/upload-portfolio', [ApplicationController::class, 'uploadPortfolio'])
        ->name('student.applications.upload_portfolio');

    Route::post('/student/applications/{id}/submit-portfolio', [ApplicationController::class, 'submitPortfolio'])
        ->name('student.applications.submit_portfolio');

    Route::get('/student/applications/{id}/edit', [ApplicationController::class, 'edit'])
        ->name('student.applications.edit');

    Route::put('/student/applications/{id}', [ApplicationController::class, 'update'])
        ->name('student.applications.update');
});

Route::middleware('role:evaluator')->group(function () {
    Route::get('/evaluator/dashboard', [AuthController::class, 'evaluatorDashboard'])
        ->name('evaluator.dashboard');

    Route::get('/evaluator/applications', [ApplicationReviewController::class, 'index'])
        ->name('evaluator.applications.index');

    Route::get('/evaluator/applications/{id}', [ApplicationReviewController::class, 'show'])
        ->name('evaluator.applications.show');

    Route::post('/evaluator/applications/{id}', [ApplicationReviewController::class, 'update'])
        ->name('evaluator.applications.update');

    Route::get('/evaluator/assessment-papers', [AssessmentPaperController::class, 'index'])
        ->name('evaluator.assessment.papers.index');

    Route::get('/evaluator/applications/{applicationId}/assessment-paper/create', [AssessmentPaperController::class, 'create'])
        ->name('evaluator.assessment.papers.create');

    Route::post('/evaluator/applications/{applicationId}/assessment-paper', [AssessmentPaperController::class, 'store'])
        ->name('evaluator.assessment.papers.store');

    Route::delete('/evaluator/assessment-papers/{id}', [AssessmentPaperController::class, 'destroy'])
        ->name('evaluator.assessment.papers.destroy');

    Route::get('/evaluator/assessment-submissions', [AssessmentGradingController::class, 'index'])
        ->name('evaluator.assessment.grading.index');

    Route::get('/evaluator/assessment-submissions/{id}', [AssessmentGradingController::class, 'show'])
        ->name('evaluator.assessment.grading.show');

    Route::post('/evaluator/assessment-submissions/{id}/grade', [AssessmentGradingController::class, 'grade'])
        ->name('evaluator.assessment.grading.grade');

    Route::get('/evaluator/apel-a', [ApplicationReviewController::class, 'apelAIndex'])
        ->name('evaluator.apel_a.index');
});

Route::middleware('role:admin')->group(function () {
    Route::get('/admin/dashboard', [AuthController::class, 'adminDashboard'])
        ->name('admin.dashboard');

    Route::get('/admin/applications', [ApplicationManagementController::class, 'index'])
        ->name('admin.applications.index');

    Route::get('/admin/applications/{id}/assign', [ApplicationManagementController::class, 'assignForm'])
        ->name('admin.applications.assign.form');

    Route::get('/admin/applications/{id}/brief', [ApplicationManagementController::class, 'evaluatorBrief'])
        ->name('admin.applications.brief');

    Route::post('/admin/applications/{id}/assign', [ApplicationManagementController::class, 'assignEvaluator'])
        ->name('admin.applications.assign');

    Route::post('/admin/applications/{id}/advisor-approve', [ApplicationManagementController::class, 'advisorApprove'])
        ->name('admin.applications.advisor_approve');

    Route::post('/admin/applications/{id}/finalize-apel-a', [ApplicationManagementController::class, 'finalizeApelA'])
        ->name('admin.applications.finalize_apel_a');

    Route::get('/admin/apel-a', [ApplicationManagementController::class, 'apelAIndex'])
        ->name('admin.apel_a.index');

    /*
     | Account administration.
     |
     | These three routes were commented out, which left no way to create an
     | evaluator or an administrator anywhere in the system — registration
     | hardcodes the student role, so staff accounts could only be inserted by
     | hand in MongoDB. A fresh deployment could therefore accept applications
     | it had nobody to assess. The store route below is new: it is the only
     | supported way to create a member of staff.
     */
    Route::get('/admin/users', [UserManagementController::class, 'index'])
        ->name('admin.users.index');

    Route::get('/admin/users/create', [UserManagementController::class, 'create'])
        ->name('admin.users.create');

    Route::post('/admin/users', [UserManagementController::class, 'store'])
        ->name('admin.users.store');

    Route::get('/admin/users/{id}/edit', [UserManagementController::class, 'edit'])
        ->name('admin.users.edit');

    Route::put('/admin/users/{id}', [UserManagementController::class, 'update'])
        ->name('admin.users.update');

    Route::post('/admin/applications/{id}/finalize-apel-c', [ApplicationManagementController::class, 'finalizeApelC'])
        ->name('admin.applications.finalize_apel_c');

    Route::post('/admin/applications/{id}/status', [ApplicationManagementController::class, 'updateStatus'])
        ->name('admin.applications.update_status');

    Route::post('/admin/applications/{id}/payment', [ApplicationManagementController::class, 'updatePayment'])
        ->name('admin.applications.update_payment');

    Route::get('/admin/reports/apel-a', [ApplicationManagementController::class, 'printApelAReport'])
        ->name('admin.reports.apel_a');
    Route::get('/admin/reports/apel-a/export', [ApplicationManagementController::class, 'exportApelAReport'])
        ->name('admin.reports.apel_a.export');

    Route::get('/admin/reports/apel-c', [ApplicationManagementController::class, 'printApelCReport'])
        ->name('admin.reports.apel_c');
    Route::get('/admin/reports/apel-c/export', [ApplicationManagementController::class, 'exportApelCReport'])
        ->name('admin.reports.apel_c.export');
});

Route::middleware('auth')->group(function () {
    Route::get('/student/applications/{id}/print', [ApplicationController::class, 'printPortfolio'])
        ->name('student.applications.print');

    /*
     | Uploaded documents.
     |
     | These files used to sit on the public disk behind asset('storage/...'),
     | which meant every careful ownership check in the controllers stopped at
     | the page and never reached the document. Anyone holding the URL could
     | read another candidate's payment receipt, portfolio or answer script.
     |
     | They are now on a private disk with no public URL, and every read is
     | authorised against the owning application by SecureFileController.
     */
    Route::get('/files/application/{application}', [SecureFileController::class, 'application'])
        ->name('files.application');

    Route::get('/files/paper/{paper}', [SecureFileController::class, 'paper'])
        ->name('files.paper');

    Route::get('/files/submission/{submission}', [SecureFileController::class, 'submission'])
        ->name('files.submission');
});
