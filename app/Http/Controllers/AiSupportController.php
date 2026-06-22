<?php

namespace App\Http\Controllers;

use App\Services\AiSupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiSupportController extends Controller
{
    protected $aiSupportService;

    public function __construct(AiSupportService $aiSupportService)
    {
        $this->aiSupportService = $aiSupportService;
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $aiMessage = $this->aiSupportService->processMessage(Auth::id(), $request->message);

            return response()->json([
                'status' => 'success',
                'ai_message' => (is_object($aiMessage) && isset($aiMessage->message))
                    ? (string) $aiMessage->message
                    : (string) ($aiMessage->message ?? $aiMessage ?? 'Sorry, I could not generate a response.'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'AI chat failed.',
            ], 500);
        }
    }

    public function getHistory()
    {
        $history = $this->aiSupportService->getChatHistory(Auth::id());

        return response()->json([
            'status' => 'success',
            'history' => $history,
        ]);
    }
}
