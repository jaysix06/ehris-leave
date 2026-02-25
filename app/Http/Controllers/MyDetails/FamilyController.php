<?php

namespace App\Http\Controllers\MyDetails;

use App\Http\Controllers\Controller;
use App\Http\Requests\MyDetails\FamilyStoreRequest;
use App\Models\FamilyInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;

class FamilyController extends Controller
{
    /**
     * Store or replace the authenticated user's family information.
     */
    public function store(FamilyStoreRequest $request): RedirectResponse
    {
        $user = $request->user();
        $hrid = $user->hrId ?? $user->userId ?? $user->id ?? null;

        if ($hrid === null) {
            return back()->withErrors(['family' => 'Unable to identify employee.']);
        }

        if (! Schema::hasTable('tbl_emp_family_info')) {
            return back()->withErrors(['family' => 'Family information is not available.']);
        }

        $family = $request->input('family', []);

        if ($user->hrId !== null) {
            $user->familyInfo()->delete();
            foreach ($family as $row) {
                $user->familyInfo()->create([
                    'relationship' => $row['relationship'] ?? null,
                    'firstname' => $row['firstname'] ?? null,
                    'middlename' => $row['middlename'] ?? null,
                    'lastname' => $row['lastname'] ?? null,
                    'extension' => $row['extension'] ?? null,
                    'dob' => $row['dob'] ?? null,
                    'occupation' => $row['occupation'] ?? null,
                    'employer_name' => $row['employer_name'] ?? null,
                    'business_add' => $row['business_add'] ?? null,
                    'tel_num' => $row['tel_num'] ?? null,
                ]);
            }
        } else {
            FamilyInfo::query()->where('hrid', $hrid)->delete();
            foreach ($family as $row) {
                FamilyInfo::query()->create([
                    'hrid' => $hrid,
                    'relationship' => $row['relationship'] ?? null,
                    'firstname' => $row['firstname'] ?? null,
                    'middlename' => $row['middlename'] ?? null,
                    'lastname' => $row['lastname'] ?? null,
                    'extension' => $row['extension'] ?? null,
                    'dob' => $row['dob'] ?? null,
                    'occupation' => $row['occupation'] ?? null,
                    'employer_name' => $row['employer_name'] ?? null,
                    'business_add' => $row['business_add'] ?? null,
                    'tel_num' => $row['tel_num'] ?? null,
                ]);
            }
        }

        return redirect()->route('my-details')->with('status', 'Family background updated.');
    }
}
