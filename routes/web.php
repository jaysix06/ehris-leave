<?php

use App\Http\Controllers\Auth\PasswordResetOtpController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeManagement\IdCardPrintingController;
use App\Http\Controllers\EmployeeManagement\LeaveRequestsController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MyDetailsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RequestStatus\MyLeaveController;
use App\Http\Controllers\SelfService\CalendarController;
use App\Http\Controllers\SelfService\IdCardController;
use App\Http\Controllers\SelfService\LeaveApplicationController;
use App\Http\Controllers\SelfService\TimeLogsController;
use App\Http\Controllers\SelfService\WfhTimeInOutController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\Utilities\ActivityLogController;
use App\Http\Controllers\Utilities\AnnouncementManagementController;
use App\Http\Controllers\Utilities\BusinessDepartmentController;
use App\Http\Controllers\Utilities\JobTitleMonthlySalaryController;
use App\Http\Controllers\Utilities\LeaveTypeController;
use App\Http\Controllers\Utilities\PopupMessageController;
use App\Http\Controllers\Utilities\ReportingManagerController;
use App\Http\Controllers\Utilities\SurveyManagementController;
use App\Http\Controllers\Utilities\UserListController;
use App\Models\Announcement;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    // Skip DB query when running in console (e.g. php artisan wayfinder:generate)
    // so the Wayfinder Vite plugin does not fail if DB is unavailable or migrations not run.
    $announcements = collect();
    if (! app()->runningInConsole()) {
        try {
            $announcements = Announcement::query()
                ->active()
                ->select(['id', 'title', 'content', 'links', 'created_at'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        } catch (\Throwable) {
            // Table may not exist yet; show no announcements
        }
    }

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'announcements' => $announcements,
    ]);
})->middleware('guest')->name('home');

Route::get('manual/wfh-attendance', fn () => view('manual.wfh-attendance'))
    ->middleware('guest')
    ->name('manual.wfh-attendance');

Route::get('auth/status', function (Request $request) {
    return response()
        ->json([
            'authenticated' => (bool) $request->user(),
        ])
        ->header('Cache-Control', 'no-store, private')
        ->header('Pragma', 'no-cache');
})->name('auth.status');

// Guest-only routes for OTP-based password reset. Rate limiting is handled in the
// controller (PasswordResetOtpController::send) so we keep this group unthrottled
// to avoid unexpected 429 errors when navigating between steps.
Route::middleware(['guest'])->group(function () {
    Route::post('forgot-password/otp/send', [PasswordResetOtpController::class, 'send'])
        ->name('password.otp.send');
    Route::get('forgot-password/otp/verify', [PasswordResetOtpController::class, 'showVerify'])
        ->name('password.otp.verify.form');
    Route::post('forgot-password/otp/verify', [PasswordResetOtpController::class, 'verify'])
        ->name('password.otp.verify');
    Route::get('forgot-password/otp/reset', [PasswordResetOtpController::class, 'showReset'])
        ->name('password.otp.reset.form');
    Route::post('forgot-password/otp/reset', [PasswordResetOtpController::class, 'reset'])
        ->name('password.otp.reset');
    Route::get('forgot-password/otp/success', [PasswordResetOtpController::class, 'success'])
        ->name('password.otp.success');
});

Route::get('email/verified-success', function (Request $request) {
    if ($request->user()) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    return Inertia::render('auth/EmailVerifiedSuccess');
})->name('verification.success');

Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::get('cot-rpms-summary', function () {
    return Inertia::render('CotRpmsSummary');
})->middleware(['auth', 'verified'])->name('cot-rpms-summary');
Route::get('cot-rpms-summary/total', function () {
    return Inertia::render('CotRpmsSummary/Total');
})->middleware(['auth', 'verified'])->name('cot-rpms-summary.total');
Route::get('cot-rpms-summary/quarterly-selectable-schools', function () {
    return Inertia::render('CotRpmsSummary/QuarterlySelectableSchools');
})->middleware(['auth', 'verified'])->name('cot-rpms-summary.quarterly-selectable-schools');
Route::get('cot-rpms-summary/by-grade', function () {
    return Inertia::render('CotRpmsSummary/ByGrade');
})->middleware(['auth', 'verified'])->name('cot-rpms-summary.by-grade');
Route::get('cot-rpms-summary/subject-area', function () {
    return Inertia::render('CotRpmsSummary/SubjectArea');
})->middleware(['auth', 'verified'])->name('cot-rpms-summary.subject-area');
Route::get('cot-rpms-summary/by-skills-teacher', function () {
    return Inertia::render('CotRpmsSummary/BySkillsTeacher');
})->middleware(['auth', 'verified'])->name('cot-rpms-summary.by-skills-teacher');
Route::get('cot-rpms-summary/by-skills-master-teacher', function () {
    return Inertia::render('CotRpmsSummary/BySkillsMasterTeacher');
})->middleware(['auth', 'verified'])->name('cot-rpms-summary.by-skills-master-teacher');

Route::get('sat-summary', function () {
    return Inertia::render('SatSummary');
})->middleware(['auth', 'verified'])->name('sat-summary');
Route::get('sat-summary/demographic-summary', function () {
    return Inertia::render('SatSummary/DemographicSummary');
})->middleware(['auth', 'verified'])->name('sat-summary.demographic-summary');
Route::get('sat-summary/core-behavioral-competencies', function () {
    return Inertia::render('SatSummary/CoreBehavioralCompetencies');
})->middleware(['auth', 'verified'])->name('sat-summary.core-behavioral-competencies');
Route::get('sat-summary/sat-teacher-i-iii', function () {
    return Inertia::render('SatSummary/SatTeacherIIii');
})->middleware(['auth', 'verified'])->name('sat-summary.sat-teacher-i-iii');
Route::get('sat-summary/sat-master-teacher-i-iv', function () {
    return Inertia::render('SatSummary/SatMasterTeacherIIv');
})->middleware(['auth', 'verified'])->name('sat-summary.sat-master-teacher-i-iv');

Route::get('employee-management', function () {
    return Inertia::render('EmployeeManagement');
})->middleware(['auth', 'verified'])->name('employee-management');
Route::get('employee-management/employee-profile', function () {
    return Inertia::render('EmployeeManagement/EmployeeProfile');
})->middleware(['auth', 'verified'])->name('employee-management.employee-profile');
Route::get('employee-management/psipop-update', function () {
    return Inertia::render('EmployeeManagement/PsipopUpdate');
})->middleware(['auth', 'verified'])->name('employee-management.psipop-update');
Route::get('employee-management/id-card-printing', [IdCardPrintingController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('employee-management.id-card-printing');
Route::get('employee-management/id-card-printing/{id}/eodb-id-bb', [IdCardPrintingController::class, 'eodbIdBb'])
    ->middleware(['auth', 'verified'])
    ->name('employee-management.id-card-printing.eodb-id-bb');
Route::get('employee-management/id-card-printing/{id}/eodb-id', [IdCardPrintingController::class, 'eodbId'])
    ->middleware(['auth', 'verified'])
    ->name('employee-management.id-card-printing.eodb-id');
Route::get('employee-management/deped-email-requests', function () {
    return Inertia::render('EmployeeManagement/DepedEmailRequests');
})->middleware(['auth', 'verified'])->name('employee-management.deped-email-requests');
Route::get('employee-management/leave-requests', [LeaveRequestsController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('employee-management.leave-requests');
Route::get('api/employee-management/leave-requests/datatables', [LeaveRequestsController::class, 'datatables'])
    ->middleware(['auth', 'verified'])
    ->name('api.employee-management.leave-requests.datatables');

Route::get('self-service', function () {
    return Inertia::render('SelfService');
})->middleware(['auth', 'verified'])->name('self-service');
Route::get('self-service/wfh-time-in-out', [WfhTimeInOutController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('self-service.wfh-time-in-out');
Route::post('self-service/wfh-time-in-out/clock-in', [WfhTimeInOutController::class, 'clockIn'])
    ->middleware(['auth', 'verified'])->name('self-service.wfh-time-in-out.clock-in');
Route::post('self-service/wfh-time-in-out/clock-out', [WfhTimeInOutController::class, 'clockOut'])
    ->middleware(['auth', 'verified'])->name('self-service.wfh-time-in-out.clock-out');
Route::post('self-service/wfh-time-in-out/tasks', [WfhTimeInOutController::class, 'storeTask'])
    ->middleware(['auth', 'verified'])->name('self-service.wfh-time-in-out.tasks.store');
Route::put('self-service/wfh-time-in-out/tasks/{task}', [WfhTimeInOutController::class, 'updateTaskStatus'])
    ->middleware(['auth', 'verified'])->name('self-service.wfh-time-in-out.tasks.update');
Route::patch('self-service/wfh-time-in-out/tasks/{task}', [WfhTimeInOutController::class, 'updateTask'])
    ->middleware(['auth', 'verified'])->name('self-service.wfh-time-in-out.tasks.edit');
Route::delete('self-service/wfh-time-in-out/tasks/{task}', [WfhTimeInOutController::class, 'destroyTask'])
    ->middleware(['auth', 'verified'])->name('self-service.wfh-time-in-out.tasks.destroy');
Route::match(['get', 'post'], 'self-service/wfh-time-in-out/export/pdf', [WfhTimeInOutController::class, 'exportPdf'])
    ->middleware(['auth', 'verified'])->name('self-service.wfh-time-in-out.export.pdf');
Route::get('self-service/user-manuals', fn () => Inertia::render('SelfService/UserManuals'))
    ->middleware(['auth', 'verified'])->name('self-service.user-manuals');
Route::get('self-service/calendar', [CalendarController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('self-service.calendar');
Route::get('api/self-service/calendar/events', [CalendarController::class, 'events'])
    ->middleware(['auth'])->name('api.self-service.calendar.events');
Route::post('api/self-service/calendar/events', [CalendarController::class, 'store'])
    ->middleware(['auth'])->name('api.self-service.calendar.events.store');
Route::delete('api/self-service/calendar/events/{event}', [CalendarController::class, 'destroy'])
    ->middleware(['auth'])->name('api.self-service.calendar.events.destroy');
Route::get('self-service/time-logs', [TimeLogsController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('self-service.time-logs');
Route::get('self-service/id-card', [IdCardController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('self-service.id-card');
Route::get('self-service/id-card/template/{filename}', [IdCardController::class, 'template'])
    ->middleware(['auth', 'verified'])
    ->name('self-service.id-card.template');
Route::put('self-service/id-card/update', [IdCardController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('self-service.id-card.update');
Route::get('self-service/service-record', function () {
    return Inertia::render('SelfService/ServiceRecord');
})->middleware(['auth', 'verified'])->name('self-service.service-record');
Route::get('self-service/leave-application', [LeaveApplicationController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('self-service.leave-application');
Route::post('self-service/leave-application', [LeaveApplicationController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('self-service.leave-application.store');
Route::get('self-service/leave-application/approvals', [LeaveApplicationController::class, 'approvals'])
    ->middleware(['auth', 'verified'])
    ->name('self-service.leave-application.approvals');
Route::patch('self-service/leave-application/{id}/decision', [LeaveApplicationController::class, 'decide'])
    ->middleware(['auth', 'verified'])
    ->name('self-service.leave-application.decision');
Route::get('self-service/deped-email-requests', function () {
    return Inertia::render('SelfService/DepedEmailRequests');
})->middleware(['auth', 'verified'])->name('self-service.deped-email-requests');

Route::get('request-status', function () {
    return Inertia::render('RequestStatus');
})->middleware(['auth', 'verified'])->name('request-status');
Route::get('request-status/my-requests', function () {
    return Inertia::render('RequestStatus/MyRequests');
})->middleware(['auth', 'verified'])->name('request-status.my-requests');
Route::get('request-status/my-leave', [MyLeaveController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('request-status.my-leave');
Route::get('api/request-status/my-leave/datatables', [MyLeaveController::class, 'datatables'])
    ->middleware(['auth'])
    ->name('api.request-status.my-leave.datatables');
Route::delete('request-status/my-leave/{id}', [MyLeaveController::class, 'cancel'])
    ->middleware(['auth', 'verified'])
    ->name('request-status.my-leave.cancel');
Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead'])
    ->middleware(['auth', 'verified'])
    ->name('notifications.read');

Route::get('my-details', [MyDetailsController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('my-details');
Route::get('my-profile', function () {
    return redirect()->route('profile.edit');
})->middleware(['auth', 'verified'])->name('my-profile');

Route::get('utilities', function () {
    return Inertia::render('Utilities');
})->middleware(['auth', 'verified'])->name('utilities');
Route::get('utilities/employee-list', function () {
    return Inertia::render('Utilities/EmployeeList');
})->middleware(['auth', 'verified'])->name('utilities.employee-list');
Route::get('utilities/user-list', [UserListController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.user-list');
Route::get('utilities/users', [UserListController::class, 'api'])
    ->middleware(['auth'])
    ->name('utilities.user-list.api');
Route::post('utilities/users', [UserListController::class, 'store'])
    ->middleware(['auth'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('utilities.user-list.store');
Route::get('utilities/users/datatables', [UserListController::class, 'datatables'])
    ->middleware(['auth'])
    ->name('utilities.user-list.datatables');
Route::get('utilities/user-list/summary-stats', [UserListController::class, 'summaryStats'])
    ->middleware(['auth'])
    ->name('utilities.user-list.summary-stats');
Route::get('utilities/users/export/excel', [UserListController::class, 'exportExcel'])
    ->middleware(['auth'])
    ->name('utilities.user-list.export-excel');
Route::get('utilities/users/{user}', [UserListController::class, 'show'])
    ->middleware(['auth'])
    ->name('utilities.user-list.show');
Route::get('utilities/departments', [UserListController::class, 'departments'])
    ->middleware(['auth'])
    ->name('utilities.departments');
Route::get('utilities/users/export/excel', [UserListController::class, 'exportExcel'])
    ->middleware(['auth'])
    ->name('utilities.user-list.export-excel');
Route::patch('utilities/users/{user}/status', [UserListController::class, 'updateStatus'])
    ->middleware(['auth'])
    // Use token-based auth (session "auth" only) for this JSON endpoint to
    // avoid CSRF 419 errors when called via fetch/DataTables.
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('utilities.user-list.update-status');
Route::patch('utilities/users/{user}/reset-password', [UserListController::class, 'resetPassword'])
    ->middleware(['auth'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('utilities.user-list.reset-password');
Route::patch('utilities/users/{user}', [UserListController::class, 'update'])
    ->middleware(['auth'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('utilities.user-list.update');
Route::delete('utilities/users/{user}', [UserListController::class, 'destroy'])
    ->middleware(['auth'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('utilities.user-list.destroy');
Route::get('utilities/business-department-list', function () {
    return Inertia::render('Utilities/BusinessDepartmentList');
})->middleware(['auth', 'verified'])->name('utilities.business-department-list');
Route::get('utilities/job-title-monthly-salary', [JobTitleMonthlySalaryController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.job-title-monthly-salary');

Route::get('my-details/pds-export', [MyDetailsController::class, 'exportPdsExcel'])
    ->middleware(['auth', 'verified'])
    ->name('my-details.export-pds');

Route::post('my-details/education', [MyDetailsController::class, 'updateEducation'])
    ->middleware(['auth', 'verified'])
    ->name('my-details.education.store');
Route::post('my-details/official', [MyDetailsController::class, 'updateOfficialInfo'])
    ->middleware(['auth', 'verified'])
    ->name('my-details.official.store');
Route::post('my-details/personal', [MyDetailsController::class, 'updatePersonalInfo'])
    ->middleware(['auth', 'verified'])
    ->name('my-details.personal.store');
Route::post('my-details/family', [MyDetailsController::class, 'updateFamilyBackground'])
    ->middleware(['auth', 'verified'])
    ->name('my-details.family.store');
Route::post('my-details/eligibility', [MyDetailsController::class, 'updateEligibility'])
    ->middleware(['auth', 'verified'])
    ->name('my-details.eligibility.store');
Route::post('my-details/work-experience', [MyDetailsController::class, 'updateWorkExperience'])
    ->middleware(['auth', 'verified'])
    ->name('my-details.work-experience.store');
Route::post('my-details/voluntary-work', [MyDetailsController::class, 'updateVoluntaryWork'])
    ->middleware(['auth', 'verified'])
    ->name('my-details.voluntary-work.store');
Route::post('my-details/training', [MyDetailsController::class, 'updateTraining'])
    ->middleware(['auth', 'verified'])
    ->name('my-details.training.store');
Route::post('my-details/awards', [MyDetailsController::class, 'updateAwards'])
    ->middleware(['auth', 'verified'])
    ->name('my-details.awards.store');

Route::get('utilities', function () {
    return Inertia::render('Utilities');
})->middleware(['auth', 'verified'])->name('utilities');
Route::get('utilities/employee-list', [App\Http\Controllers\Utilities\EmployeeListController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.employee-list');
Route::get('utilities/employee-list/datatables', [App\Http\Controllers\Utilities\EmployeeListController::class, 'datatables'])
    ->middleware(['auth', 'verified'])
    ->name('api.utilities.employee-list.datatables');
Route::post('utilities/employee-list', [App\Http\Controllers\Utilities\EmployeeListController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('api.utilities.employee-list.store');
Route::delete('utilities/employee-list/{employee}', [App\Http\Controllers\Utilities\EmployeeListController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('api.utilities.employee-list.destroy')
    ->where('employee', '[0-9]+');
Route::get('utilities/user-list', [UserListController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.user-list');
Route::get('utilities/users', [UserListController::class, 'api'])
    ->middleware(['auth'])
    ->name('utilities.user-list.api');
Route::post('utilities/users', [UserListController::class, 'store'])
    ->middleware(['auth'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('utilities.user-list.store');
Route::get('utilities/departments', [UserListController::class, 'departments'])
    ->middleware(['auth'])
    ->name('utilities.departments');
Route::patch('utilities/users/{user}/status', [UserListController::class, 'updateStatus'])
    ->middleware(['auth'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('utilities.user-list.update-status');
Route::patch('utilities/users/{user}/reset-password', [UserListController::class, 'resetPassword'])
    ->middleware(['auth'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('utilities.user-list.reset-password');
Route::patch('utilities/users/{user}', [UserListController::class, 'update'])
    ->middleware(['auth'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('utilities.user-list.update');
Route::delete('utilities/users/{user}', [UserListController::class, 'destroy'])
    ->middleware(['auth'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('utilities.user-list.destroy');
Route::get('utilities/business-department-list', function () {
    return Inertia::render('Utilities/BusinessDepartmentList');
})->middleware(['auth', 'verified'])->name('utilities.business-department-list');
Route::get('utilities/job-title-monthly-salary', [JobTitleMonthlySalaryController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.job-title-monthly-salary');

Route::get('utilities/job-title-monthly-salary/job-titles/datatables', [JobTitleMonthlySalaryController::class, 'jobTitlesDatatables'])
    ->middleware(['auth', 'verified'])
    ->name('api.utilities.job-titles.datatables');

Route::get('utilities/job-title-monthly-salary/monthly-salaries/datatables', [JobTitleMonthlySalaryController::class, 'monthlySalariesDatatables'])
    ->middleware(['auth', 'verified'])
    ->name('api.utilities.monthly-salaries.datatables');

Route::post('utilities/job-title-monthly-salary/job-titles', [JobTitleMonthlySalaryController::class, 'storeJobTitle'])
    ->middleware(['auth', 'verified'])
    ->name('api.utilities.job-titles.store');

Route::put('utilities/job-title-monthly-salary/job-titles/{id}', [JobTitleMonthlySalaryController::class, 'updateJobTitle'])
    ->middleware(['auth', 'verified'])
    ->name('api.utilities.job-titles.update');

Route::delete('utilities/job-title-monthly-salary/monthly-salaries/{id}', [JobTitleMonthlySalaryController::class, 'destroyMonthlySalary'])
    ->middleware(['auth', 'verified'])
    ->name('api.utilities.monthly-salaries.destroy');
Route::get('utilities/business-department-list', [BusinessDepartmentController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list');
Route::post('utilities/business-department-list/business-units', [BusinessDepartmentController::class, 'storeBusinessUnit'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list.business-units.store');
Route::put('utilities/business-department-list/business-units/{id}', [BusinessDepartmentController::class, 'updateBusinessUnit'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list.business-units.update');
Route::post('utilities/business-department-list/departments', [BusinessDepartmentController::class, 'storeDepartment'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list.departments.store');
Route::put('utilities/business-department-list/departments/{id}', [BusinessDepartmentController::class, 'updateDepartment'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list.departments.update');
Route::delete('utilities/business-department-list/business-units/{id}', [BusinessDepartmentController::class, 'destroyBusinessUnit'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list.business-units.destroy');
Route::delete('utilities/business-department-list/departments/{id}', [BusinessDepartmentController::class, 'destroyDepartment'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list.departments.destroy');
Route::get('utilities/business-department-list/business-units/datatables', [BusinessDepartmentController::class, 'datatablesBusinessUnit'])
    ->middleware(['auth', 'verified'])->name('api.utilities.business-department-list.business-units.datatables');
Route::get('utilities/business-department-list/departments/datatables', [BusinessDepartmentController::class, 'datatablesDepartment'])
    ->middleware(['auth', 'verified'])->name('api.utilities.business-department-list.departments.datatables');
Route::get('utilities/job-title-monthly-salary', function () {
    return Inertia::render('Utilities/JobTitleMonthlySalary');
})->middleware(['auth', 'verified'])->name('utilities.job-title-monthly-salary');
Route::get('utilities/reporting-manager', [ReportingManagerController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.reporting-manager');
Route::get('utilities/reporting-manager/data', [ReportingManagerController::class, 'api'])
    ->middleware(['auth'])
    ->name('utilities.reporting-manager.api');
Route::get('utilities/reporting-manager/datatables', [ReportingManagerController::class, 'datatables'])
    ->middleware(['auth'])
    ->name('utilities.reporting-manager.datatables');
Route::get('utilities/reporting-manager/managers', [ReportingManagerController::class, 'managers'])
    ->middleware(['auth'])
    ->name('utilities.reporting-manager.managers');
Route::post('utilities/reporting-manager/assign', [ReportingManagerController::class, 'assign'])
    ->middleware(['auth'])
    ->name('utilities.reporting-manager.assign');
Route::post('utilities/reporting-manager/auto-assign', [ReportingManagerController::class, 'autoAssignBySchoolOrDepartment'])
    ->middleware(['auth'])
    ->name('utilities.reporting-manager.auto-assign');
Route::delete('utilities/reporting-manager/{hrid}', [ReportingManagerController::class, 'remove'])
    ->middleware(['auth'])
    ->name('utilities.reporting-manager.remove');

Route::post('utilities/job-title-monthly-salary/monthly-salaries', [JobTitleMonthlySalaryController::class, 'storeMonthlySalary'])
    ->middleware(['auth', 'verified'])
    ->name('api.utilities.monthly-salaries.store');

Route::put('utilities/job-title-monthly-salary/monthly-salaries/{id}', [JobTitleMonthlySalaryController::class, 'updateMonthlySalary'])
    ->middleware(['auth', 'verified'])
    ->name('api.utilities.monthly-salaries.update');

Route::delete('utilities/job-title-monthly-salary/monthly-salaries/{id}', [JobTitleMonthlySalaryController::class, 'destroyMonthlySalary'])
    ->middleware(['auth', 'verified'])
    ->name('api.utilities.monthly-salaries.destroy');
Route::get('utilities/user-list', function () {
    return Inertia::render('Utilities/UserList');
})->middleware(['auth', 'verified'])->name('utilities.user-list');
Route::get('utilities/business-department-list', [BusinessDepartmentController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list');
Route::post('utilities/business-department-list/business-units', [BusinessDepartmentController::class, 'storeBusinessUnit'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list.business-units.store');
Route::put('utilities/business-department-list/business-units/{id}', [BusinessDepartmentController::class, 'updateBusinessUnit'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list.business-units.update');
Route::post('utilities/business-department-list/departments', [BusinessDepartmentController::class, 'storeDepartment'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list.departments.store');
Route::put('utilities/business-department-list/business-units/{id}', [BusinessDepartmentController::class, 'updateBusinessUnit'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list.business-units.update');
Route::put('utilities/business-department-list/departments/{id}', [BusinessDepartmentController::class, 'updateDepartment'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list.departments.update');
Route::delete('utilities/business-department-list/business-units/{id}', [BusinessDepartmentController::class, 'destroyBusinessUnit'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list.business-units.destroy');
Route::delete('utilities/business-department-list/departments/{id}', [BusinessDepartmentController::class, 'destroyDepartment'])
    ->middleware(['auth', 'verified'])->name('utilities.business-department-list.departments.destroy');
Route::get('utilities/business-department-list/business-units/datatables', [BusinessDepartmentController::class, 'datatablesBusinessUnit'])
    ->middleware(['auth', 'verified'])->name('api.utilities.business-department-list.business-units.datatables');
Route::get('utilities/business-department-list/departments/datatables', [BusinessDepartmentController::class, 'datatablesDepartment'])
    ->middleware(['auth', 'verified'])->name('api.utilities.business-department-list.departments.datatables');
Route::get('utilities/job-title-monthly-salary', function () {
    return Inertia::render('Utilities/JobTitleMonthlySalary');
})->middleware(['auth', 'verified'])->name('utilities.job-title-monthly-salary');
Route::get('utilities/reporting-manager', [ReportingManagerController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.reporting-manager');
Route::get('utilities/reporting-manager/data', [ReportingManagerController::class, 'api'])
    ->middleware(['auth'])
    ->name('utilities.reporting-manager.api');
Route::get('utilities/reporting-manager/datatables', [ReportingManagerController::class, 'datatables'])
    ->middleware(['auth'])
    ->name('utilities.reporting-manager.datatables');
Route::get('utilities/reporting-manager/managers', [ReportingManagerController::class, 'managers'])
    ->middleware(['auth'])
    ->name('utilities.reporting-manager.managers');
Route::post('utilities/reporting-manager/assign', [ReportingManagerController::class, 'assign'])
    ->middleware(['auth'])
    ->name('utilities.reporting-manager.assign');
Route::post('utilities/reporting-manager/auto-assign', [ReportingManagerController::class, 'autoAssignBySchoolOrDepartment'])
    ->middleware(['auth'])
    ->name('utilities.reporting-manager.auto-assign');
Route::delete('utilities/reporting-manager/{hrid}', [ReportingManagerController::class, 'remove'])
    ->middleware(['auth'])
    ->name('utilities.reporting-manager.remove');

Route::get('utilities/activity-log', [ActivityLogController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.activity-log');

// API route for Activity Log DataTables
Route::get('utilities/activity-log/datatables', [ActivityLogController::class, 'datatables'])
    ->middleware(['auth', 'verified'])
    ->name('api.utilities.activity-log.datatables');
Route::get('utilities/survey-management', [SurveyManagementController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('utilities.survey-management');
Route::post('utilities/survey-management', [SurveyManagementController::class, 'store'])
    ->middleware(['auth', 'verified'])->name('utilities.survey-management.store');
Route::get('utilities/survey-management/datatables', [SurveyManagementController::class, 'datatables'])
    ->middleware(['auth', 'verified'])->name('api.utilities.survey-management.datatables');
Route::get('utilities/survey-management/{id}', [SurveyManagementController::class, 'show'])
    ->middleware(['auth', 'verified'])->name('api.utilities.survey-management.show');
Route::put('utilities/survey-management/{id}', [SurveyManagementController::class, 'update'])
    ->middleware(['auth', 'verified'])->name('utilities.survey-management.update');
Route::delete('utilities/survey-management/{id}', [SurveyManagementController::class, 'destroy'])
    ->middleware(['auth', 'verified'])->name('utilities.survey-management.destroy');
Route::get('utilities/pop-up-management', [PopupMessageController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.pop-up-management');
Route::post('utilities/pop-up-management', [PopupMessageController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.pop-up-management.store');
Route::put('utilities/pop-up-management/{popupMessage}', [PopupMessageController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.pop-up-management.update');
Route::delete('utilities/pop-up-management/{popupMessage}', [PopupMessageController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.pop-up-management.destroy');
Route::get('utilities/announcement-management', [AnnouncementManagementController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.announcement-management');
Route::post('utilities/announcement-management', [AnnouncementManagementController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.announcement-management.store');
Route::put('utilities/announcement-management/{announcement}', [AnnouncementManagementController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.announcement-management.update');
Route::delete('utilities/announcement-management/{announcement}', [AnnouncementManagementController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.announcement-management.destroy');
Route::post('utilities/announcement-management/{announcement}/send-email', [AnnouncementManagementController::class, 'sendEmail'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.announcement-management.send-email');
Route::get('utilities/leave-types', [LeaveTypeController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.leave-types.index');
Route::post('utilities/leave-types', [LeaveTypeController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.leave-types.store');
Route::put('utilities/leave-types/{leaveType}', [LeaveTypeController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.leave-types.update');
Route::delete('utilities/leave-types/{leaveType}', [LeaveTypeController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('utilities.leave-types.destroy');

Route::get('reports', function () {
    return Inertia::render('Reports');
})->middleware(['auth', 'verified'])->name('reports');

Route::get('survey', function () {
    return Inertia::render('Survey');
})->middleware(['auth', 'verified'])->name('survey');
Route::get('survey/gad', [SurveyController::class, 'gad'])
    ->middleware(['auth', 'verified'])->name('survey.gad');
Route::get('survey/gad/{id}/answer', [SurveyController::class, 'showAnswer'])
    ->middleware(['auth', 'verified'])->name('survey.gad.answer');
Route::post('survey/gad/answer', [SurveyController::class, 'storeAnswer'])
    ->middleware(['auth', 'verified'])->name('survey.gad.answer.store');

Route::get('api/reports/employee-listing', [App\Http\Controllers\Reports\EmployeeListingController::class, 'api'])
    ->middleware(['auth', 'verified'])
    ->name('api.reports.employee-listing');
Route::get('api/reports/employee-listing/datatables', [App\Http\Controllers\Reports\EmployeeListingController::class, 'datatables'])
    ->middleware(['auth', 'verified'])
    ->name('api.reports.employee-listing.datatables');

Route::get('reports/employee-listing', [App\Http\Controllers\Reports\EmployeeListingController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('reports.employee-listing');

Route::get('reports/employee-listing/export/csv', [App\Http\Controllers\Reports\EmployeeListingController::class, 'exportCsv'])
    ->middleware(['auth', 'verified'])
    ->name('reports.employee-listing.export.csv');

Route::get('reports/employee-listing/export/excel', [App\Http\Controllers\Reports\EmployeeListingController::class, 'exportExcel'])
    ->middleware(['auth', 'verified'])
    ->name('reports.employee-listing.export.excel');

Route::get('reports/employee-listing/export/print', [App\Http\Controllers\Reports\EmployeeListingController::class, 'exportPrint'])
    ->middleware(['auth', 'verified'])
    ->name('reports.employee-listing.export.print');

Route::get('api/reports/employee-listing/summary-stats', [App\Http\Controllers\Reports\EmployeeListingController::class, 'summaryStats'])
    ->middleware(['auth', 'verified'])
    ->name('reports.employee-listing.summary-stats');

Route::get('api/messages/conversations', [MessageController::class, 'conversations'])
    ->middleware(['auth'])
    ->name('api.messages.conversations');
Route::get('api/messages/{contactId}', [MessageController::class, 'show'])
    ->middleware(['auth'])
    ->where('contactId', '[0-9]+')
    ->name('api.messages.show');
Route::post('api/messages', [MessageController::class, 'store'])
    ->middleware(['auth'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('api.messages.store');
Route::patch('api/messages/{contactId}/read', [MessageController::class, 'markRead'])
    ->middleware(['auth'])
    ->where('contactId', '[0-9]+')
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('api.messages.read');

require __DIR__.'/settings.php';
// });
