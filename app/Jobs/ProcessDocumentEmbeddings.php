<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Ai\Embeddings;

class ProcessDocumentEmbeddings implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public function __construct(
        public readonly string $name,
        public readonly string $originalFilename,
        public readonly string $chunk,
        public readonly int $totalChunks,
    ) {}

    public function handle(): void
    {
        $embedding = Embeddings::for([$this->chunk])->generate()->first();

        Document::create([
            'name'              => $this->name,
            'original_filename' => $this->originalFilename,
            'content'           => $this->chunk,
            'embedding'         => $embedding,
            'total_chunks'      => $this->totalChunks,
        ]);
    }
}
