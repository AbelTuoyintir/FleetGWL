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
            $aiResponse = $this->aiSupportService->processMessage(
                Auth::user(),
                $request->message,
                $request->session()->getId()
            );

            $messageText = 'Sorry, I could not generate a response.';
            if (is_string($aiResponse)) {
                $messageText = $aiResponse;
            } elseif (is_object($aiResponse)) {
                if (isset($aiResponse->message)) {
                    $messageText = (string) $aiResponse->message;
                } elseif (isset($aiResponse->content)) {
                    $messageText = (string) $aiResponse->content;
                }
            }

            return response()->json([
                'status' => 'success',
                'ai_message' => $messageText,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI Support Chat Error: ' . $e->getMessage(), [
                'user_id' => optional(Auth::user())->id,
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
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
            Auth::user(),
            $request->session()->getId()
        );

        return response()->json([
            'status' => 'success',
            'history' => $history,
        ]);
    }
}
