<?php

namespace App\Livewire;

use App\Jobs\ProcessDocumentEmbeddings;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Smalot\PdfParser\Parser;

class Chat extends Component
{
    use WithFileUploads;

    public string $question = '';
    public array $messages = [];
    public bool $processing = false;
    public ?string $selectedDocument = null;
    public $upload;

    public function mount(): void
    {
        $this->loadMessages();
    }

    #[Computed]
    public function documents()
    {
        return Document::selectRaw('original_filename, MAX(total_chunks) as total_chunks, COUNT(*) as chunk_count')
            ->groupBy('original_filename')
            ->havingRaw('COUNT(*) = MAX(total_chunks)')
            ->orderBy('original_filename')
            ->get();
    }

    public function appendUserMessage(string $content): void
    {
        if ($content) {
            $this->messages[] = ['role' => 'user', 'content' => $content];
        }
    }

    public function appendAssistantMessage(string $content): void
    {
        if ($content) {
            $this->messages[] = ['role' => 'assistant', 'content' => $content];
        }
    }

    public function selectDocument(?string $filename): void
    {
        $this->selectedDocument = $this->selectedDocument === $filename ? null : $filename;
        $this->loadMessages();
    }

    public function newConversation(): void
    {
        DB::table('agent_conversations')
            ->where('user_id', $this->participantId())
            ->orderByDesc('updated_at')
            ->limit(1)
            ->update(['updated_at' => now()->subYears(10)]);

        $this->messages = [];
    }

    public function uploadDocument(): void
    {
        $this->validate(['upload' => 'required|file|mimes:pdf,txt,md|max:10240']);

        $path     = $this->upload->store('documents');
        $content  = $this->extractText($path);
        $name     = pathinfo($this->upload->getClientOriginalName(), PATHINFO_FILENAME);
        $original = $this->upload->getClientOriginalName();

        $chunks = $this->chunk($content);
        $total  = count($chunks);

        foreach ($chunks as $chunk) {
            ProcessDocumentEmbeddings::dispatch($name, $original, $chunk, $total);
        }

        $this->upload = null;
        $this->processing = true;
        unset($this->documents);
    }

    public function pollDocuments(): void
    {
        unset($this->documents);

        if ($this->processing && ! DB::table('jobs')->exists()) {
            $this->processing = false;
        }
    }

    public function participantId(): string
    {
        $base = session()->getId();

        return $this->selectedDocument ? $base.':'.md5($this->selectedDocument) : $base;
    }

    private function loadMessages(): void
    {
        $conversationId = DB::table('agent_conversations')
            ->where('user_id', $this->participantId())
            ->orderByDesc('updated_at')
            ->value('id');

        if (! $conversationId) {
            $this->messages = [];
            return;
        }

        $this->messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->whereIn('role', ['user', 'assistant'])
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->orderBy('created_at')
            ->get(['role', 'content'])
            ->map(fn ($row) => ['role' => $row->role, 'content' => $row->content])
            ->all();
    }

    private function chunk(string $text, int $size = 150, int $overlap = 30): array
    {
        $words  = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        $i      = 0;

        while ($i < count($words)) {
            $chunk = implode(' ', array_slice($words, $i, $size));
            if (trim($chunk)) {
                $chunks[] = $chunk;
            }
            $i += ($size - $overlap);
        }

        return $chunks;
    }

    private function extractText(string $path): string
    {
        $fullPath = Storage::path($path);

        if (str_ends_with($fullPath, '.pdf')) {
            $parser = new Parser();
            return $parser->parseFile($fullPath)->getText();
        }

        return file_get_contents($fullPath);
    }

    public function render()
    {
        return view('livewire.chat')
            ->layout('components.layouts.app');
    }
}
