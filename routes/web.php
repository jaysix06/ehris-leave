<?php

use App\Http\Controllers\MyDetails\FamilyController;
use App\Http\Controllers\MyDetailsController;
use App\Http\Controllers\SelfService\LeaveApplicationController;
use App\Http\Controllers\Utilities\LeaveTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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
Route::get('employee-management/id-card-printing', function () {
    return Inertia::render('EmployeeManagement/IdCardPrinting');
})->middleware(['auth', 'verified'])->name('employee-management.id-card-printing');
Route::get('employee-management/deped-email-requests', function () {
    return Inertia::render('EmployeeManagement/DepedEmailRequests');
})->middleware(['auth', 'verified'])->name('employee-management.deped-email-requests');

Route::get('self-service', function () {
    return Inertia::render('SelfService');
})->middleware(['auth', 'verified'])->name('self-service');
Route::get('self-service/wfh-time-in-out', function () {
    return Inertia::render('SelfService/WfhTimeInOut');
})->middleware(['auth', 'verified'])->name('self-service.wfh-time-in-out');
Route::get('self-service/id-card', function () {
    return Inertia::render('SelfService/IdCard');
})->middleware(['auth', 'verified'])->name('self-service.id-card');
Route::get('self-service/service-record', function () {
    return Inertia::render('SelfService/ServiceRecord');
})->middleware(['auth', 'verified'])->name('self-service.service-record');
Route::get('self-service/leave-application', [LeaveApplicationController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('self-service.leave-application');
Route::post('self-service/leave-application', [LeaveApplicationController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('self-service.leave-application.store');
Route::get('self-service/deped-email-requests', function () {
    return Inertia::render('SelfService/DepedEmailRequests');
})->middleware(['auth', 'verified'])->name('self-service.deped-email-requests');

Route::get('request-status', function () {
    return Inertia::render('RequestStatus');
})->middleware(['auth', 'verified'])->name('request-status');
Route::get('request-status/my-requests', function () {
    return Inertia::render('RequestStatus/MyRequests');
})->middleware(['auth', 'verified'])->name('request-status.my-requests');
Route::get('request-status/my-leave', function () {
    return Inertia::render('RequestStatus/MyLeave');
})->middleware(['auth', 'verified'])->name('request-status.my-leave');

Route::get('my-details', function (Request $request) {
    $authUser = $request->user();
    $dbProfile = null;
    $hrid = null;
    $officialInfo = null;
    $personalInfo = null;
    $contactInfo = null;
    $family = [];
    $education = [];
    $workExperience = [];
    $eligibility = [];
    $serviceRecord = [];
    $leaveHistory = [];
    $documents = [];
    $training = [];
    $awards = [];
    $performance = [];
    $researches = [];
    $expertise = [];
    $affiliation = [];

    if ($authUser && Schema::hasTable('tbl_user')) {
        $dbProfile = DB::table('tbl_user')
            ->select([
                'hrId',
                'email',
                'lastname',
                'firstname',
                'middlename',
                'extname',
                'avatar',
                'job_title',
                'role',
                'fullname',
            ])
            ->where('email', $authUser->email)
            ->first();
        $hrid = $dbProfile->hrId ?? $authUser->hrId ?? null;
    }

    if ($hrid !== null) {
        $tables = [
            'tbl_emp_official_info' => fn () => DB::table('tbl_emp_official_info')->where('hrid', $hrid)->first(),
            'tbl_emp_personal_info' => fn () => DB::table('tbl_emp_personal_info')->where('hrid', $hrid)->first(),
            'tbl_emp_contact_info' => fn () => DB::table('tbl_emp_contact_info')->where('hrid', $hrid)->first(),
            'tbl_emp_family_info' => fn () => DB::table('tbl_emp_family_info')->where('hrid', $hrid)->get(),
            'tbl_emp_education_info' => fn () => DB::table('tbl_emp_education_info')->where('hrid', $hrid)->get(),
            'tbl_emp_work_experience_info' => fn () => DB::table('tbl_emp_work_experience_info')->where('hrid', $hrid)->get(),
            'tbl_emp_civil_service_info' => fn () => DB::table('tbl_emp_civil_service_info')->where('hrid', $hrid)->get(),
            'tbl_emp_service_record' => fn () => DB::table('tbl_emp_service_record')->where('hrid', $hrid)->get(),
            'tbl_leave_history' => fn () => DB::table('tbl_leave_history')->where('hrid', $hrid)->get(),
            'tbl_document' => fn () => DB::table('tbl_document')->where('hrid', $hrid)->get(),
            'tbl_emp_training' => fn () => DB::table('tbl_emp_training')->where('hrid', $hrid)->get(),
            'tbl_awards' => fn () => DB::table('tbl_awards')->where('hrid', $hrid)->get(),
            'tbl_performance' => fn () => DB::table('tbl_performance')->where('hrid', $hrid)->get(),
            'tbl_researches' => fn () => DB::table('tbl_researches')->where('hrid', $hrid)->get(),
            'tbl_expertise' => fn () => DB::table('tbl_expertise')->where('hrid', $hrid)->get(),
            'tbl_affiliation' => fn () => DB::table('tbl_affiliation')->where('hrid', $hrid)->get(),
        ];
        foreach ($tables as $table => $query) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            try {
                $result = $query();
                if ($result instanceof \Illuminate\Support\Collection) {
                    $result = $result->all();
                }
                switch ($table) {
                    case 'tbl_emp_official_info': $officialInfo = $result;
                        break;
                    case 'tbl_emp_personal_info': $personalInfo = $result;
                        break;
                    case 'tbl_emp_contact_info': $contactInfo = $result;
                        break;
                    case 'tbl_emp_family_info': $family = $result;
                        break;
                    case 'tbl_emp_education_info': $education = $result;
                        break;
                    case 'tbl_emp_work_experience_info': $workExperience = $result;
                        break;
                    case 'tbl_emp_civil_service_info': $eligibility = $result;
                        break;
                    case 'tbl_emp_service_record': $serviceRecord = $result;
                        break;
                    case 'tbl_leave_history': $leaveHistory = $result;
                        break;
                    case 'tbl_document': $documents = $result;
                        break;
                    case 'tbl_emp_training': $training = $result;
                        break;
                    case 'tbl_awards': $awards = $result;
                        break;
                    case 'tbl_performance': $performance = $result;
                        break;
                    case 'tbl_researches': $researches = $result;
                        break;
                    case 'tbl_expertise': $expertise = $result;
                        break;
                    case 'tbl_affiliation': $affiliation = $result;
                        break;
                }
            } catch (\Throwable $e) {
                // skip if table missing or query fails
            }
        }
    }

    return Inertia::render('MyDetails', [
        'profile' => $dbProfile,
        'officialInfo' => $officialInfo,
        'personalInfo' => $personalInfo,
        'contactInfo' => $contactInfo,
        'family' => $family,
        'education' => $education,
        'workExperience' => $workExperience,
        'eligibility' => $eligibility,
        'serviceRecord' => $serviceRecord,
        'leaveHistory' => $leaveHistory,
        'documents' => $documents,
        'training' => $training,
        'awards' => $awards,
        'performance' => $performance,
        'researches' => $researches,
        'expertise' => $expertise,
        'affiliation' => $affiliation,
        'familyUpdateUrl' => route('my-details.family.store'),
    ]);
})->middleware(['auth', 'verified'])->name('my-details');

Route::post('my-details/family', [FamilyController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('my-details.family.store');

Route::get('utilities', function () {
    return Inertia::render('Utilities');
})->middleware(['auth', 'verified'])->name('utilities');
Route::get('utilities/employee-list', function () {
    return Inertia::render('Utilities/EmployeeList');
})->middleware(['auth', 'verified'])->name('utilities.employee-list');
Route::get('utilities/user-list', function () {
    return Inertia::render('Utilities/UserList');
})->middleware(['auth', 'verified'])->name('utilities.user-list');
Route::get('utilities/business-department-list', function () {
    return Inertia::render('Utilities/BusinessDepartmentList');
})->middleware(['auth', 'verified'])->name('utilities.business-department-list');
Route::get('utilities/job-title-monthly-salary', function () {
    return Inertia::render('Utilities/JobTitleMonthlySalary');
})->middleware(['auth', 'verified'])->name('utilities.job-title-monthly-salary');
Route::get('utilities/reporting-manager', function () {
    return Inertia::render('Utilities/ReportingManager');
})->middleware(['auth', 'verified'])->name('utilities.reporting-manager');
Route::get('utilities/activity-log', function () {
    return Inertia::render('Utilities/ActivityLog');
})->middleware(['auth', 'verified'])->name('utilities.activity-log');
Route::get('utilities/survey-management', function () {
    return Inertia::render('Utilities/SurveyManagement');
})->middleware(['auth', 'verified'])->name('utilities.survey-management');
Route::get('utilities/pop-up-management', function () {
    return Inertia::render('Utilities/PopUpManagement');
})->middleware(['auth', 'verified'])->name('utilities.pop-up-management');
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
Route::get('survey/gad', function () {
    return Inertia::render('Survey/Gad');
})->middleware(['auth', 'verified'])->name('survey.gad');

require __DIR__.'/settings.php';
