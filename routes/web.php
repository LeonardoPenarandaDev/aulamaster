<?php

use App\Http\Controllers\AppearanceController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClassMaterialController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassScheduleController;
use App\Http\Controllers\ClassSessionController;
use App\Http\Controllers\ClassSessionImportController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ContractTemplateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentContractController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EnrollmentWizardController;
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
use App\Http\Controllers\RemoteContractSigningController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StaffUserController;
use App\Http\Controllers\StudentAccountController;
use App\Http\Controllers\StudentContractController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentImportController;
use App\Http\Controllers\StudentMaterialController;
use App\Http\Controllers\StudentPhotoController;
use App\Http\Controllers\StudentScheduleController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserPasswordResetController;
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
    Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::resource('teachers', TeacherController::class)->except('show');
    Route::resource('staff-users', StaffUserController::class)
        ->only(['index', 'create', 'store', 'edit', 'update'])
        ->parameters(['staff-users' => 'user']);
    Route::post('students/{student}/portal-access', [PortalAccessController::class, 'storeForStudent'])->name('students.portal-access.store');
    Route::post('teachers/{teacher}/portal-access', [PortalAccessController::class, 'storeForTeacher'])->name('teachers.portal-access.store');

    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('attendance/absences', [AttendanceController::class, 'absences'])->name('attendance.absences');
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
    Route::post('enrollments/{enrollment}/promote', [EnrollmentController::class, 'promote'])->name('enrollments.promote');

    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    Route::resource('contract-templates', ContractTemplateController::class)->except('show');
    Route::post('contract-templates/{contract_template}/publish', [ContractTemplateController::class, 'publish'])->name('contract-templates.publish');
    Route::post('contract-templates/{contract_template}/new-version', [ContractTemplateController::class, 'createVersion'])->name('contract-templates.new-version');
    Route::post('contract-templates/{contract_template}/archive', [ContractTemplateController::class, 'archive'])->name('contract-templates.archive');

    Route::get('apariencia', [AppearanceController::class, 'edit'])->name('appearance.edit');
    Route::put('apariencia', [AppearanceController::class, 'update'])->name('appearance.update');

    Route::get('institution-settings', [InstitutionSettingController::class, 'edit'])->name('institution-settings.edit');
    Route::post('institution-settings', [InstitutionSettingController::class, 'update'])->name('institution-settings.update');
});

// Coordinador de horarios (Fase 15): solo programa/organiza el calendario académico.
Route::middleware(['auth', 'verified', 'role:admin|coordinador'])->group(function () {
    Route::resource('courses', CourseController::class)->except('show');
    Route::resource('levels', LevelController::class)->except('show');
    Route::resource('classrooms', ClassroomController::class)->except('show');
    Route::get('class-sessions/import', [ClassSessionImportController::class, 'create'])->name('class-sessions.import.create');
    Route::get('class-sessions/import/template', [ClassSessionImportController::class, 'template'])->name('class-sessions.import.template');
    Route::post('class-sessions/import/preview', [ClassSessionImportController::class, 'preview'])->name('class-sessions.import.preview');
    Route::post('class-sessions/import', [ClassSessionImportController::class, 'store'])->name('class-sessions.import.store');
    Route::get('class-sessions/import/{token}/errors', [ClassSessionImportController::class, 'errors'])->name('class-sessions.import.errors');
    Route::resource('class-sessions', ClassSessionController::class)->except('show');
    Route::resource('class-schedules', ClassScheduleController::class)->only(['index', 'create', 'store', 'destroy']);
});

// Restablecer contraseñas con una temporal (parte 2 del plan de mejoras):
// el coordinador solo puede hacerlo con estudiantes y profesores (UserPolicy).
Route::middleware(['auth', 'verified', 'role:admin|coordinador'])->group(function () {
    Route::get('password-resets', [UserPasswordResetController::class, 'index'])->name('password-resets.index');
    Route::post('users/{user}/password-reset', [UserPasswordResetController::class, 'store'])->name('password-resets.store');
});

// Secretaria (parte 3 del plan de mejoras): registra estudiantes y matrículas,
// y consulta pagos sin poder registrarlos ni editarlos.
Route::middleware(['auth', 'verified', 'role:admin|secretaria'])->group(function () {
    Route::resource('students', StudentController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::post('students/{student}/photo', [StudentPhotoController::class, 'update'])->name('students.photo.update');
    Route::delete('students/{student}/photo', [StudentPhotoController::class, 'destroy'])->name('students.photo.destroy');
});

// Contratos de la matrícula (partes 6.5 a 6.8 del plan de mejoras).
Route::middleware(['auth', 'verified', 'role:admin|secretaria'])->group(function () {
    Route::get('enrollments/asistente', [EnrollmentWizardController::class, 'create'])->name('enrollments.wizard.create');
    Route::post('enrollments/asistente', [EnrollmentWizardController::class, 'store'])->name('enrollments.wizard.store');
    Route::post('enrollments/{enrollment}/contracts', [EnrollmentContractController::class, 'store'])->name('enrollments.contracts.store');
    Route::post('enrollments/{enrollment}/contracts/send', [EnrollmentContractController::class, 'send'])->name('enrollments.contracts.send');
    Route::get('enrollments/{enrollment}/contracts/sign', [EnrollmentContractController::class, 'signInOffice'])->name('enrollments.contracts.sign');
    Route::post('contract-signatures/{contract_signature}/sign', [EnrollmentContractController::class, 'storeOfficeSignature'])->name('contract-signatures.sign');
    Route::post('contract-signatures/{contract_signature}/void', [EnrollmentContractController::class, 'void'])->name('contract-signatures.void');
    Route::get('contract-signatures/{contract_signature}/id-photo/{side}', [EnrollmentContractController::class, 'idPhoto'])->name('contract-signatures.id-photo');
});

Route::middleware(['auth', 'verified', 'role:admin|cajero|secretaria'])->group(function () {
    Route::get('cartera-en-mora', [CollectionController::class, 'index'])->name('collections.index');
    Route::post('students/{student}/payment-follow-ups', [CollectionController::class, 'storeFollowUp'])->name('collections.follow-ups.store');
    Route::post('students/{student}/payment-agreements', [CollectionController::class, 'storeAgreement'])->name('collections.agreements.store');
    Route::post('payment-agreements/{payment_agreement}/cancel', [CollectionController::class, 'cancelAgreement'])->name('collections.agreements.cancel');
    Route::resource('enrollments', EnrollmentController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('students/{student}/account-statement', [PaymentController::class, 'accountStatement'])->name('students.account-statement');
});

// Cajero (Fase 15): matricula, gestiona pagos/promociones/referidos y consulta estados de cuenta.
Route::middleware(['auth', 'verified', 'role:admin|cajero'])->group(function () {
    Route::resource('promotions', PromotionController::class)->except('show');
    Route::post('promotions/{promotion}/notify', [PromotionController::class, 'notify'])->name('promotions.notify');
    Route::resource('referrals', ReferralController::class)->only(['index', 'create', 'store']);
    Route::resource('payments', PaymentController::class)->only(['create', 'store', 'edit', 'update']);

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
    Route::get('mi-cuenta/pagos-pendientes', [StudentAccountController::class, 'blocked'])->name('student-account.blocked');
    Route::get('mis-contratos', [StudentContractController::class, 'index'])->name('student-contracts.index');
    Route::post('mis-contratos/autorizacion-imagenes/revocar', [StudentContractController::class, 'revokeImageConsent'])->name('student-contracts.revoke-image-consent');
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

// PDF del contrato: el personal lo descarga siempre; el estudiante, solo
// los suyos ya firmados (ContractSignaturePolicy).
Route::get('contract-signatures/{contract_signature}/pdf', [EnrollmentContractController::class, 'pdf'])
    ->middleware(['auth', 'verified'])
    ->name('contract-signatures.pdf');

// Firma a distancia, sin cuenta (parte 6.8): el enlace es una URL firmada
// con vencimiento; los pasos siguientes usan el token guardado en la sesión.
Route::get('firmar/{enrollment}', [RemoteContractSigningController::class, 'show'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('contracts.remote.show');
Route::post('firmar/{enrollment}/codigo', [RemoteContractSigningController::class, 'code'])
    ->middleware('throttle:10,1')
    ->name('contracts.remote.code');
Route::post('firmar/{enrollment}/contratos/{contract_signature}', [RemoteContractSigningController::class, 'sign'])
    ->middleware('throttle:20,1')
    ->name('contracts.remote.sign');

// Foto de perfil del estudiante (privada: StudentPolicy@viewPhoto).
Route::get('students/{student}/photo', [StudentPhotoController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('students.photo');

// Calendario por rol (parte 10 del plan de mejoras).
Route::get('calendario', [CalendarController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:admin|coordinador|profesor|estudiante'])
    ->name('calendar.index');

// Archivo de material de clase (parte 12): docente, admin y estudiantes que
// asistieron a la clase (ClassMaterialPolicy@view).
Route::get('class-materials/{class_material}/file', [ClassMaterialController::class, 'file'])
    ->middleware(['auth', 'verified'])
    ->name('class-materials.file');

// Webhook público de Wompi: sin auth ni CSRF (ver bootstrap/app.php), la
// autenticidad se valida con la firma de eventos dentro del controlador.
Route::post('webhooks/wompi', [WompiPaymentController::class, 'webhook'])->name('webhooks.wompi');

require __DIR__.'/auth.php';
