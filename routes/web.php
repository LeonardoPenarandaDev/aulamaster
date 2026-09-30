<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ClassMaterialController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassScheduleController;
use App\Http\Controllers\ClassSessionController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\EvaluationResultController;
use App\Http\Controllers\ExtensionController;
use App\Http\Controllers\InstitutionSettingController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PortalAccessController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\RecoveryController;
use App\Http\Controllers\RecoverySettingController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentImportController;
use App\Http\Controllers\StudentMaterialController;
use App\Http\Controllers\StudentScheduleController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\WompiPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route(auth()->check() ? 'dashboard' : 'login'));

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('students/import', [StudentImportController::class, 'create'])->name('students.import.create');
    Route::get('students/import/template', [StudentImportController::class, 'template'])->name('students.import.template');
    Route::post('students/import', [StudentImportController::class, 'store'])->name('students.import.store');
    Route::resource('students', StudentController::class)->except('show');
    Route::resource('teachers', TeacherController::class)->except('show');
    Route::post('students/{student}/portal-access', [PortalAccessController::class, 'storeForStudent'])->name('students.portal-access.store');
    Route::post('teachers/{teacher}/portal-access', [PortalAccessController::class, 'storeForTeacher'])->name('teachers.portal-access.store');

    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/{attendance}/corrections', [AttendanceCorrectionController::class, 'store'])->name('attendance-corrections.store');

    Route::resource('evaluations', EvaluationController::class)->except('show');
    Route::get('evaluation-results', [EvaluationResultController::class, 'index'])->name('evaluation-results.index');
    Route::get('enrollments/{enrollment}/evaluation-results/create', [EvaluationResultController::class, 'create'])->name('enrollments.evaluation-results.create');
    Route::post('enrollments/{enrollment}/evaluation-results', [EvaluationResultController::class, 'store'])->name('enrollments.evaluation-results.store');

    Route::get('recovery', [RecoveryController::class, 'index'])->name('recovery.index');
    Route::get('recovery-settings', [RecoverySettingController::class, 'edit'])->name('recovery-settings.edit');
    Route::put('recovery-settings', [RecoverySettingController::class, 'update'])->name('recovery-settings.update');
    Route::post('enrollments/{enrollment}/extensions', [ExtensionController::class, 'store'])->name('enrollments.extensions.store');
    Route::get('enrollments/{enrollment}/certificate', [EnrollmentController::class, 'certificate'])->name('enrollments.certificate');

    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    Route::get('institution-settings', [InstitutionSettingController::class, 'edit'])->name('institution-settings.edit');
    Route::post('institution-settings', [InstitutionSettingController::class, 'update'])->name('institution-settings.update');
});

// Coordinador de horarios (Fase 15): solo programa/organiza el calendario académico.
Route::middleware(['auth', 'verified', 'role:admin|coordinador'])->group(function () {
    Route::resource('courses', CourseController::class)->except('show');
    Route::resource('levels', LevelController::class)->except('show');
    Route::resource('classrooms', ClassroomController::class)->except('show');
    Route::resource('class-sessions', ClassSessionController::class)->except('show');
    Route::resource('class-schedules', ClassScheduleController::class)->only(['index', 'create', 'store', 'destroy']);
});

// Cajero (Fase 15): matricula, gestiona pagos/promociones/referidos y consulta estados de cuenta.
Route::middleware(['auth', 'verified', 'role:admin|cajero'])->group(function () {
    Route::resource('enrollments', EnrollmentController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::resource('promotions', PromotionController::class)->except('show');
    Route::post('promotions/{promotion}/notify', [PromotionController::class, 'notify'])->name('promotions.notify');
    Route::resource('referrals', ReferralController::class)->only(['index', 'create', 'store']);
    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::get('students/{student}/account-statement', [PaymentController::class, 'accountStatement'])->name('students.account-statement');

    // El cajero solo ve el subconjunto financiero (ReportController@reportsFor filtra por rol).
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
});

Route::middleware(['auth', 'verified', 'role:admin|profesor'])->group(function () {
    Route::get('class-sessions/{class_session}/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('class-sessions/{class_session}/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    Route::patch('class-sessions/{class_session}/meeting-url', [ClassSessionController::class, 'updateMeetingUrl'])->name('class-sessions.meeting-url');

    Route::get('class-sessions/{class_session}/materials', [ClassMaterialController::class, 'index'])->name('class-materials.index');
    Route::post('class-sessions/{class_session}/materials', [ClassMaterialController::class, 'store'])->name('class-materials.store');
    Route::delete('class-materials/{class_material}', [ClassMaterialController::class, 'destroy'])->name('class-materials.destroy');
});

Route::middleware(['auth', 'verified', 'role:estudiante'])->group(function () {
    Route::get('mis-horarios', [StudentScheduleController::class, 'index'])->name('student-schedule.index');
    Route::get('mi-material', [StudentMaterialController::class, 'index'])->name('student-materials.index');
    Route::get('mis-certificados/{enrollment}', [EnrollmentController::class, 'certificate'])->name('student-certificates.download');
});

// Pago en línea con Wompi (Fase 17): lo inicia el propio estudiante desde su
// estado de cuenta; admin/cajero también pueden generar el link como apoyo.
Route::middleware(['auth', 'verified', 'role:admin|cajero|estudiante'])->group(function () {
    Route::get('payments/{payment}/pay-online', [WompiPaymentController::class, 'checkout'])->name('payments.pay-online');
    Route::get('payments/online/return', [WompiPaymentController::class, 'return'])->name('payments.online-return');
});

// Webhook público de Wompi: sin auth ni CSRF (ver bootstrap/app.php), la
// autenticidad se valida con la firma de eventos dentro del controlador.
Route::post('webhooks/wompi', [WompiPaymentController::class, 'webhook'])->name('webhooks.wompi');

require __DIR__.'/auth.php';
