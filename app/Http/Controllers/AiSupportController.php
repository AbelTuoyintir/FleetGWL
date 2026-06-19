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

        $aiMessage = $this->aiSupportService->processMessage(Auth::id(), $request->message);

        return response()->json([
            'status' => 'success',
            'ai_message' => $aiMessage->message,
        ]);
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
