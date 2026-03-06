<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\SurveyQuestionnaire;
use App\Models\SurveySet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SurveyManagementController extends Controller
{
    /**
     * Display the Survey Management page.
     */
    public function index()
    {
        return Inertia::render('Utilities/SurveyManagement');
    }

    /**
     * Store a new survey (tbl_survey_set).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        SurveySet::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category' => $validated['category'] ?? null,
            'userId' => auth()->id(),
        ]);

        return back()->with('success', 'Survey created successfully.');
    }

    /**
     * Get a single survey with its questions (for edit modal).
     */
    public function show(int $id): JsonResponse
    {
        $survey = SurveySet::with('questions')->findOrFail($id);
        $questions = $survey->questions()->orderBy('survey_question_id')->get()->map(function ($q) {
            return [
                'survey_question_id' => $q->survey_question_id,
                'question' => $q->question,
                'frm_option' => $q->frm_option,
                'type' => $q->type ?? 'radio',
            ];
        });

        return response()->json([
            'survey' => [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
                'category' => $survey->category,
            ],
            'questions' => $questions,
        ]);
    }

    /**
     * Update a survey and its questions.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $survey = SurveySet::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'questions' => ['nullable', 'array'],
            'questions.*.survey_question_id' => ['nullable', 'integer'],
            'questions.*.question' => ['required', 'string'],
            'questions.*.frm_option' => ['nullable', 'string', 'max:255'],
            'questions.*.type' => ['nullable', 'string', 'max:50'],
        ]);

        $survey->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? $survey->description,
            'category' => $validated['category'] ?? $survey->category,
        ]);

        $questions = $validated['questions'] ?? [];
        $existingIds = [];
        foreach ($questions as $q) {
            $questionId = $q['survey_question_id'] ?? null;
            $payload = [
                'question' => $q['question'],
                'frm_option' => $q['frm_option'] ?? null,
                'type' => $q['type'] ?? 'radio',
            ];
            if ($questionId) {
                $question = SurveyQuestionnaire::where('survey_id', $id)->where('survey_question_id', $questionId)->first();
                if ($question) {
                    $question->update($payload);
                    $existingIds[] = $question->survey_question_id;
                }
            } else {
                $newQ = SurveyQuestionnaire::create(array_merge($payload, ['survey_id' => $id]));
                $existingIds[] = $newQ->survey_question_id;
            }
        }
        SurveyQuestionnaire::where('survey_id', $id)->whereNotIn('survey_question_id', $existingIds)->delete();

        return back()->with('success', 'Survey updated successfully.');
    }

    /**
     * Delete a survey and its questions.
     */
    public function destroy(int $id): RedirectResponse
    {
        $survey = SurveySet::findOrFail($id);
        SurveyQuestionnaire::where('survey_id', $id)->delete();
        $survey->delete();

        return back()->with('success', 'Survey deleted successfully.');
    }

    /**
     * DataTables server-side processing for survey list (tbl_survey_set).
     */
    public function datatables(Request $request)
    {
        try {
            $draw = (int) $request->get('draw', 1);
            $start = (int) $request->get('start', 0);
            $length = (int) $request->get('length', 10);
            $searchValue = $request->input('search.value');
            $searchValue = is_array($searchValue) ? ($searchValue['value'] ?? '') : (string) $searchValue;
            $searchValue = trim($searchValue);
            $orderColumnIndex = (int) $request->input('order.0.column', 0);
            $orderDir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

            $query = SurveySet::query();

            $totalRecords = SurveySet::count();

            if ($searchValue !== '') {
                $term = '%'.$searchValue.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('category', 'like', $term);
                });
            }

            $filteredRecords = $query->count();

            $columns = ['title', 'description', 'category'];
            $orderColumnIndex = max(0, min($orderColumnIndex, count($columns) - 1));
            $orderColumn = $columns[$orderColumnIndex];
            $query->orderBy($orderColumn, $orderDir);

            $length = $length > 0 ? $length : 10;
            $items = $query->skip($start)->take($length)->get();

            $data = $items->map(function ($row) {
                return [
                    'id' => $row->id,
                    'title' => $row->title ?? '',
                    'description' => $row->description ?? '',
                    'category' => $row->category ?? '',
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
            \Log::error('Survey Management DataTables Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'draw' => (int) $request->get('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while loading surveys.',
            ], 500);
        }
    }
}
