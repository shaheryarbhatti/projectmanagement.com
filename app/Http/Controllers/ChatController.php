<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExcelInsightChatRequest;
use App\Models\ChatMessage;
use App\Models\WorkbookUpload;
use App\Services\ExcelInsight\ChatAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(): View
    {
        $scope = request('scope', 'smart');
        return view('chat.index', [
            'scope' => $scope,
            'latestUpload' => WorkbookUpload::query()->latest('processed_at')->latest('id')->first(),
            'messages' => ChatMessage::query()->where('user_id', auth()->id())->latest('id')->limit(20)->get()->reverse()->values(),
            'suggestions' => $this->getSuggestions($scope),
        ]);
    }

    private function getSuggestions(?string $scope): array
    {
        if ($scope === 'smart') return ['Total smart records.', 'How many projects are active?', 'Show me Engineering projects.'];
        if ($scope === 'suspension') return ['Total suspended records.', 'Reason for last suspension.', 'Show me recent resumptions.'];
        if ($scope === 'pivot') return ['Show pivot summaries.', 'Highest metric value.', 'List all metrics.'];
        
        return ['Show total records.', 'How many suspended records?', 'What is the total budget?', 'Which department has the most projects?'];
    }

    public function store(ExcelInsightChatRequest $request, ChatAssistantService $chatAssistantService): JsonResponse
    {
        return response()->json(
            $chatAssistantService->answer($request->user(), trim($request->validated('question')), $request->input('scope'))
        );
    }

    public function clear(): RedirectResponse
    {
        ChatMessage::query()->where('user_id', auth()->id())->delete();

        return redirect()->route('chat.index')->with('success', 'Chat history cleared.');
    }
}
