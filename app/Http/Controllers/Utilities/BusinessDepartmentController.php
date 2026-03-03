<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class BusinessDepartmentController extends Controller
{
    /**
     * Display the Business Unit and Department list page.
     */
    public function index(Request $request)
    {
        $businessUnits = BusinessUnit::orderBy('BusinessUnitId')->get(['id', 'BusinessUnitId', 'BusinessUnit']);

        return Inertia::render('Utilities/BusinessDepartmentList', [
            'businessUnitsForSelect' => $businessUnits->map(fn ($b) => [
                'value' => (string) $b->BusinessUnitId,
                'label' => $b->BusinessUnit.' ('.$b->BusinessUnitId.')',
            ])->values()->all(),
        ]);
    }

    /**
     * Store a new Business Unit.
     */
    public function storeBusinessUnit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'BusinessUnitId' => ['required', 'integer', 'min:1'],
            'BusinessUnit'   => ['required', 'string', 'max:255'],
        ]);

        $exists = BusinessUnit::where('BusinessUnitId', $validated['BusinessUnitId'])->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'BusinessUnitId' => ['This Business Code already exists.'],
            ]);
        }

        BusinessUnit::create([
            'office_id'       => null,
            'BusinessUnitId'  => $validated['BusinessUnitId'],
            'BusinessUnit'    => $validated['BusinessUnit'],
        ]);

        return back()->with('success', 'Business unit created successfully.');
    }

    /**
     * Store a new Department.
     */
    public function storeDepartment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_id'      => ['required', 'integer', 'min:1'],
            'department_id'    => ['required', 'integer', 'min:1'],
            'department_name'  => ['required', 'string', 'max:255'],
            'department_abbrev'=> ['nullable', 'string', 'max:250'],
        ]);

        $exists = Department::where('business_id', $validated['business_id'])
            ->where('department_id', $validated['department_id'])
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'department_id' => ['A department with this ID already exists for this Business Code.'],
            ]);
        }

        Department::create([
            'business_id'       => $validated['business_id'],
            'department_id'    => $validated['department_id'],
            'department_name'  => $validated['department_name'],
            'department_abbrev'=> $validated['department_abbrev'] ?? null,
        ]);

        return back()->with('success', 'Department created successfully.');
    }

    /**
     * Delete a Business Unit by id.
     */
    public function destroyBusinessUnit(int $id): RedirectResponse
    {
        $unit = BusinessUnit::findOrFail($id);
        $unit->delete();

        return back()->with('success', 'Business unit deleted successfully.');
    }

    /**
     * Delete a Department by id.
     */
    public function destroyDepartment(int $id): RedirectResponse
    {
        $dept = Department::findOrFail($id);
        $dept->delete();

        return back()->with('success', 'Department deleted successfully.');
    }

    /**
     * Update a Business Unit by id.
     */
    public function updateBusinessUnit(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'BusinessUnitId' => ['required', 'integer', 'min:1'],
            'BusinessUnit'   => ['required', 'string', 'max:255'],
        ]);

        $unit = BusinessUnit::findOrFail($id);
        $exists = BusinessUnit::where('BusinessUnitId', $validated['BusinessUnitId'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'BusinessUnitId' => ['This Business Code already exists.'],
            ]);
        }

        $unit->update([
            'BusinessUnitId' => $validated['BusinessUnitId'],
            'BusinessUnit'   => $validated['BusinessUnit'],
        ]);

        return back()->with('success', 'Business unit updated successfully.');
    }

    /**
     * Update a Department by id.
     */
    public function updateDepartment(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'business_id'       => ['required', 'integer', 'min:1'],
            'department_id'     => ['required', 'integer', 'min:1'],
            'department_name'   => ['required', 'string', 'max:255'],
            'department_abbrev' => ['nullable', 'string', 'max:250'],
        ]);

        $dept = Department::findOrFail($id);
        $exists = Department::where('business_id', $validated['business_id'])
            ->where('department_id', $validated['department_id'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'department_id' => ['A department with this ID already exists for this Business Code.'],
            ]);
        }

        $dept->update([
            'business_id'       => $validated['business_id'],
            'department_id'     => $validated['department_id'],
            'department_name'   => $validated['department_name'],
            'department_abbrev' => $validated['department_abbrev'] ?? null,
        ]);

        return back()->with('success', 'Department updated successfully.');
    }

    /**
     * DataTables server-side processing for List of Business Unit.
     */
    public function datatablesBusinessUnit(Request $request)
    {
        try {
            $draw = (int) $request->get('draw', 1);
            $start = (int) $request->get('start', 0);
            $length = (int) $request->get('length', 10);
            $searchValue = $request->input('search.value');
            $orderColumnIndex = (int) $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'asc');

            $query = BusinessUnit::query();

            $totalRecords = BusinessUnit::count();

            if ($searchValue && trim($searchValue) !== '') {
                $term = '%'.trim($searchValue).'%';
                $query->where(function ($q) use ($term) {
                    $q->where('BusinessUnitId', 'like', $term)
                        ->orWhere('BusinessUnit', 'like', $term);
                });
            }

            $filteredRecords = $query->count();

            $columns = ['BusinessUnitId', 'BusinessUnit'];
            $orderColumnIndex = max(0, min($orderColumnIndex, count($columns) - 1));
            $orderColumn = $columns[$orderColumnIndex];
            $orderDir = $orderDir === 'desc' ? 'desc' : 'asc';
            $query->orderBy($orderColumn, $orderDir);

            $length = $length > 0 ? $length : 10;
            $items = $query->skip($start)->take($length)->get();

            $data = $items->map(function ($row, $index) use ($start) {
                return [
                    'id' => $row->id,
                    'row_num' => $start + $index + 1,
                    'BusinessUnitId' => $row->BusinessUnitId ?? '',
                    'BusinessUnit' => $row->BusinessUnit ?? '',
                    '_raw' => $row,
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('BusinessUnit DataTables Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'draw' => (int) $request->get('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while loading business units.',
            ], 500);
        }
    }

    /**
     * DataTables server-side processing for List of Department.
     */
    public function datatablesDepartment(Request $request)
    {
        try {
            $draw = (int) $request->get('draw', 1);
            $start = (int) $request->get('start', 0);
            $length = (int) $request->get('length', 10);
            $searchValue = $request->input('search.value');
            $orderColumnIndex = (int) $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'asc');

            $query = Department::query();

            $totalRecords = Department::count();

            if ($searchValue && trim($searchValue) !== '') {
                $term = '%'.trim($searchValue).'%';
                $query->where(function ($q) use ($term) {
                    $q->where('business_id', 'like', $term)
                        ->orWhere('department_id', 'like', $term)
                        ->orWhere('department_name', 'like', $term);
                });
            }

            $filteredRecords = $query->count();

            $columns = ['business_id', 'department_id', 'department_name'];
            $orderColumnIndex = max(0, min($orderColumnIndex, count($columns) - 1));
            $orderColumn = $columns[$orderColumnIndex];
            $orderDir = $orderDir === 'desc' ? 'desc' : 'asc';
            $query->orderBy($orderColumn, $orderDir);

            $length = $length > 0 ? $length : 10;
            $items = $query->skip($start)->take($length)->get();

            $data = $items->map(function ($row, $index) use ($start) {
                return [
                    'id' => $row->id,
                    'row_num' => $start + $index + 1,
                    'business_id' => $row->business_id ?? '',
                    'department_id' => $row->department_id ?? '',
                    'department_name' => $row->department_name ?? '',
                    '_raw' => $row,
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('Department DataTables Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'draw' => (int) $request->get('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while loading departments.',
            ], 500);
        }
    }
}
