<?php

namespace App\Ai\Agents;

use App\Models\Document;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\SimilaritySearch;

class DocumentAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        protected ?string $documentFilter = null,
    ) {}

    public function instructions(): string
    {
        return 'You are a helpful assistant that answers questions about uploaded documents. Use the document search tool to find relevant content — search multiple times with different queries if needed to fully answer the question. Base your answer on what the search results contain. Only say you don\'t have the information if you have genuinely searched and found nothing relevant.';
    }

    public function tools(): iterable
    {
        $filter = $this->documentFilter;

        return [
            SimilaritySearch::usingModel(
                Document::class,
                'embedding',
                minSimilarity: 0.0,
                limit: 15,
                query: $filter
                    ? fn ($q) => $q->where('original_filename', $filter)
                    : null,
            ),
        ];
    }
}
