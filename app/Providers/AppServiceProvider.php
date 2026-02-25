<?php

namespace App\Providers;

use App\Events\MyDetailsUpdated;
use App\Models\Affiliation;
use App\Models\Awards;
use App\Models\Document;
use App\Models\EmpCivilServiceInfo;
use App\Models\EmpContactInfo;
use App\Models\EmpEducationInfo;
use App\Models\EmpFamilyInfo;
use App\Models\EmpOfficialInfo;
use App\Models\EmpPersonalInfo;
use App\Models\EmpServiceRecord;
use App\Models\EmpTraining;
use App\Models\EmpWorkExperienceInfo;
use App\Models\Expertise;
use App\Models\LeaveHistory;
use App\Models\Performance;
use App\Models\Researches;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerMyDetailsRealtimeBroadcasts();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    /**
     * Broadcast my-details updates whenever employee profile records change.
     */
    protected function registerMyDetailsRealtimeBroadcasts(): void
    {
        $models = [
            User::class,
            EmpOfficialInfo::class,
            EmpPersonalInfo::class,
            EmpContactInfo::class,
            EmpFamilyInfo::class,
            EmpEducationInfo::class,
            EmpWorkExperienceInfo::class,
            EmpCivilServiceInfo::class,
            EmpServiceRecord::class,
            LeaveHistory::class,
            Document::class,
            EmpTraining::class,
            Awards::class,
            Performance::class,
            Researches::class,
            Expertise::class,
            Affiliation::class,
        ];

        foreach ($models as $modelClass) {
            $modelClass::saved(function (Model $model): void {
                $this->dispatchMyDetailsUpdated($model);
            });

            $modelClass::deleted(function (Model $model): void {
                $this->dispatchMyDetailsUpdated($model);
            });
        }
    }

    protected function dispatchMyDetailsUpdated(Model $model): void
    {
        $hrid = $this->resolveHrid($model);
        if ($hrid === null) {
            return;
        }

        MyDetailsUpdated::dispatch($hrid);
    }

    protected function resolveHrid(Model $model): ?int
    {
        $rawHrid = $model->getAttribute('hrid') ?? $model->getAttribute('hrId');

        if (! is_numeric($rawHrid)) {
            return null;
        }

        return (int) $rawHrid;
    }
}
