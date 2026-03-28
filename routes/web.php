<?php

use App\Ai\Agents\DocumentAgent;
use App\Livewire\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Ai\Streaming\Events\TextDelta;

Route::get('/', fn () => redirect('/chat'));
Route::get('/chat', Chat::class);
Route::post('/chat/stream', function (Request $request) {
    $question = trim($request->string('question'));
    $document = $request->string('document') ?: null;

    abort_if(! $question, 422);

    $base          = session()->getId();
    $participantId = $document ? $base.':'.md5($document) : $base;

    $stream = DocumentAgent::make($document)
        ->continueLastConversation((object) ['id' => $participantId])
        ->stream($question);

    return response()->stream(function () use ($stream) {
        foreach ($stream as $event) {
            if ($event instanceof TextDelta) {
                echo 'data: '.json_encode(['delta' => $event->delta])."\n\n";
                ob_flush();
                flush();
            }
        }

        echo "data: [DONE]\n\n";
        ob_flush();
        flush();
    }, 200, [
        'Content-Type'      => 'text/event-stream',
        'Cache-Control'     => 'no-cache',
        'X-Accel-Buffering' => 'no',
    ]);
})->middleware('web');
