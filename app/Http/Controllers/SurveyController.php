<?php

namespace App\Http\Controllers;

use App\Models\SurveyQuestionnaire;
use App\Models\SurveyResponse;
use App\Models\SurveySet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SurveyController extends Controller
{
    /**
     * List surveys with completion status for current user. Optional category filter (GAD, PRAISE, PASS).
     */
    public function gad(Request $request)
    {
        $query = SurveySet::query();
        $category = $request->query('category');
        if ($category && in_array((string) $category, ['GAD', 'PRAISE', 'PASS'], true)) {
            $query->where('category', $category);
        }
        $surveys = $query
            ->orderBy('date_created', 'desc')
            ->get()
            ->map(function (SurveySet $survey) {
                $completed = SurveyResponse::where('survey_id', $survey->id)
                    ->where('userId', auth()->id())
                    ->exists();

                return [
                    'id' => $survey->id,
                    'title' => $survey->title,
                    'description' => $survey->description ?? '',
                    'category' => $survey->category ?? '',
                    'completed' => $completed,
                ];
            });

        return Inertia::render('Survey/Gad', [
            'surveys' => $surveys,
            'category' => $category ?? null,
        ]);
    }

    /**
     * Show survey with questions for answering.
     */
    public function showAnswer(int $id)
    {
        $survey = SurveySet::with(['questions' => function ($q) {
            $q->orderBy('survey_question_id');
        }])->findOrFail($id);

        $completed = SurveyResponse::where('survey_id', $id)
            ->where('userId', auth()->id())
            ->exists();

        $questions = $survey->questions->map(function (SurveyQuestionnaire $q) {
            return [
                'survey_question_id' => $q->survey_question_id,
                'question' => $q->question,
                'frm_option' => $q->frm_option,
                'type' => $q->type ?? 'radio',
            ];
        });

        return Inertia::render('Survey/Answer', [
            'survey' => [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
                'category' => $survey->category,
            ],
            'questions' => $questions,
            'completed' => $completed,
        ]);
    }

    /**
     * Store survey answers (tbl_survey_response).
     */
    public function storeAnswer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'survey_id' => ['required', 'integer', 'exists:tbl_survey_set,id'],
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.answer' => ['nullable', 'string'],
        ]);

        $surveyId = (int) $validated['survey_id'];
        $userId = auth()->id();

        // Prevent duplicate submission: remove any existing responses for this user + survey
        SurveyResponse::where('survey_id', $surveyId)->where('userId', $userId)->delete();

        foreach ($validated['answers'] as $a) {
            SurveyResponse::create([
                'survey_id' => $surveyId,
                'userId' => $userId,
                'question_id' => (int) $a['question_id'],
                'answer' => (string) ($a['answer'] ?? ''),
                'is_limit' => 1,
            ]);
        }

        return redirect()->route('survey.gad')
            ->with('success', 'Survey submitted successfully.');
    }
}
