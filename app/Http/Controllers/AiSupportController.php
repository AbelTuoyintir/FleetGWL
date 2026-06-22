<?php

namespace App\Http\Controllers;

use App\Services\AiSupportService;
use Illuminate\Http\Request;

class AiSupportController extends Controller
{
    protected $aiService;

    public function __construct(AiSupportService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Handle the AI assistance request.
     */
    public function ask(Request $request)
    {
        $request->validate([
            'concept' => 'required|string|max:255',
            'context' => 'nullable|string|max:500',
        ]);

        $explanation = $this->aiService->explainConcept(
            $request->input('concept'),
            $request->input('context')
        );

        return response()->json([
            'success' => true,
            'concept' => $request->input('concept'),
            'explanation' => $explanation,
        ]);
    }
}
