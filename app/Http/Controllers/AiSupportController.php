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
            $aiMessage = $this->aiSupportService->processMessage(
                Auth::id(),
                $request->message,
                $request->session()->getId()
            );

            return response()->json([
                'status' => 'success',
                'ai_message' => (is_object($aiMessage) && isset($aiMessage->message))
                    ? (string) $aiMessage->message
                    : (string) ($aiMessage->message ?? $aiMessage ?? 'Sorry, I could not generate a response.'),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI Support Chat Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'exception' => $e
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'I encountered an unexpected error processing your request. Please try again in a moment.',
            ], 500);
        }
    }

    public function getHistory(Request $request)
    {
        $history = $this->aiSupportService->getChatHistory(
            Auth::id(),
            $request->session()->getId()
        );

        return response()->json([
            'status' => 'success',
            'history' => $history,
        ]);
    }
}
