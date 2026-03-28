<div class="flex flex-col h-screen bg-black">

    {{-- Main content --}}
    <div class="flex flex-1 overflow-hidden">

        {{-- Left panel: Documents --}}
        <div class="w-72 border-r border-[#222222] bg-[#0a0a0a] flex flex-col">
            <div class="px-4 pt-5 pb-3 border-b border-[#222222]">
                <h2 class="text-sm font-bold text-white tracking-wide">Documents</h2>
            </div>

            {{-- Document list --}}
            <div class="flex-1 overflow-y-auto px-3 py-3 space-y-2"
                @if ($processing) wire:poll.3s="pollDocuments" @endif>
                @forelse ($this->documents as $doc)
                    @php $active = $selectedDocument === $doc->original_filename; @endphp
                    <button wire:click="selectDocument('{{ $doc->original_filename }}')"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded border text-left transition-colors
                            {{ $active
                                ? 'bg-[#1e1133] border-[#6B2EFF]'
                                : 'bg-[#111111] border-[#222222] hover:border-[#444444]' }}">
                        <svg class="w-4 h-4 shrink-0 {{ $active ? 'text-[#6B2EFF]' : 'text-[#555555]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-xs truncate {{ $active ? 'text-white' : 'text-[#999999]' }}">{{ $doc->original_filename }}</span>
                    </button>
                @empty
                    <p class="text-xs text-[#444444] text-center pt-6">No documents yet.</p>
                @endforelse

                @if ($processing)
                    <div class="flex items-center gap-2 px-3 py-2 rounded bg-[#111111] border border-[#222222] border-dashed animate-pulse">
                        <svg class="w-4 h-4 text-[#6B2EFF] shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span class="text-xs text-[#555555]">Embedding...</span>
                    </div>
                @endif
            </div>

            {{-- Upload --}}
            <div class="p-3 border-t border-[#222222]">
                <form wire:submit="uploadDocument">
                    <label class="block w-full cursor-pointer">
                        <input type="file" wire:model="upload" class="hidden" accept=".pdf,.txt,.md">
                        <div class="flex items-center justify-center gap-2 px-3 py-2 rounded bg-[#111111] border border-[#333333] hover:border-[#6B2EFF] transition-colors text-xs font-semibold text-white">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Upload Document
                        </div>
                    </label>
                    @if ($upload)
                        <button type="submit"
                            class="mt-2 w-full py-1.5 rounded bg-[#6B2EFF] text-white text-xs font-semibold hover:bg-[#5a25d4] transition-colors">
                            Process &amp; Embed
                        </button>
                    @endif
                </form>
                <div wire:loading wire:target="uploadDocument"
                    class="mt-2 text-xs text-[#6B2EFF] text-center animate-pulse">
                    Generating embeddings...
                </div>
            </div>
        </div>

        {{-- Right panel: Chat --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            <div class="px-5 pt-5 pb-3 border-b border-[#222222] flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-white tracking-wide">Chat with your documents</h2>
                    @if ($selectedDocument)
                        <p class="text-xs text-[#6B2EFF] mt-0.5 truncate max-w-sm">{{ $selectedDocument }}</p>
                    @endif
                </div>
                @if (!empty($messages))
                    <button wire:click="newConversation"
                        class="text-xs text-[#666666] hover:text-white transition-colors">
                        + New chat
                    </button>
                @endif
            </div>

            {{-- Messages --}}
            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4" id="messages">
                @if (empty($messages))
                    <div class="flex items-center justify-center h-full" id="empty-state">
                        <p class="text-sm text-[#444444]">Upload a document and ask a question.</p>
                    </div>
                @endif

                @foreach ($messages as $message)
                    @if ($message['role'] === 'user')
                        <div class="flex justify-end">
                            <div class="max-w-xl px-4 py-2.5 rounded-lg bg-[#1a1a1a] border border-[#333333] text-sm text-white">
                                {{ $message['content'] }}
                            </div>
                        </div>
                    @else
                        <div class="flex justify-start">
                            <div class="max-w-xl px-4 py-2.5 rounded-lg bg-[#111111] border border-[#222222] text-sm text-[#cccccc] leading-relaxed prose prose-invert prose-sm max-w-none">
                                {!! Str::markdown($message['content']) !!}
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Input bar --}}
            <div class="px-5 py-4 border-t border-[#222222]">
                <form id="chat-form" data-doc="{{ $selectedDocument }}" class="flex gap-2">
                    <input
                        id="chat-input"
                        type="text"
                        wire:model="question"
                        placeholder="Ask a question about your documents..."
                        class="flex-1 px-4 py-2.5 rounded-lg bg-[#111111] border border-[#333333] text-sm text-white placeholder-[#444444] focus:outline-none focus:border-[#6B2EFF] transition-colors"
                        autocomplete="off"
                    >
                    <button
                        id="chat-submit"
                        type="submit"
                        class="px-4 py-2.5 rounded-lg bg-[#6B2EFF] hover:bg-[#5a25d4] transition-colors flex items-center justify-center disabled:opacity-50"
                    >
                        <svg id="submit-icon" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Bottom purple bar --}}
    <div class="h-2 bg-[#6B2EFF]"></div>

</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    const form     = document.getElementById('chat-form');
    const input    = document.getElementById('chat-input');
    const submit   = document.getElementById('chat-submit');
    const messages = document.getElementById('messages');

    marked.setOptions({ breaks: true, gfm: true });

    function scrollToBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    function appendAssistantBubble(initialContent) {
        const wrapper = document.createElement('div');
        wrapper.className = 'flex justify-start';
        const bubble = document.createElement('div');
        bubble.className = 'max-w-xl px-4 py-2.5 rounded-lg bg-[#111111] border border-[#222222] text-sm text-[#cccccc] leading-relaxed prose prose-invert prose-sm max-w-none';
        bubble.innerHTML = marked.parse(initialContent);
        wrapper.appendChild(bubble);
        messages.appendChild(wrapper);
        scrollToBottom();
        return bubble;
    }

    function appendThinking() {
        const el = document.createElement('div');
        el.id = 'thinking-bubble';
        el.className = 'flex justify-start';
        el.innerHTML = `<div class="px-4 py-2.5 rounded-lg bg-[#111111] border border-[#222222] text-sm text-[#666666] animate-pulse">Thinking...</div>`;
        messages.appendChild(el);
        scrollToBottom();
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const question = input.value.trim();
        if (!question) return;

        const selectedDoc = form.dataset.doc || null;
        const csrf        = document.querySelector('meta[name="csrf-token"]')?.content
                         ?? '{{ csrf_token() }}';

        input.value = '';
        @this.set('question', '');
        submit.disabled = true;

        await @this.call('appendUserMessage', question);
        appendThinking();

        let bubble   = null;
        let fullText = '';

        try {
            const res = await fetch('/chat/stream', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'text/event-stream',
                },
                body: JSON.stringify({ question, document: selectedDoc }),
            });

            if (!res.ok) {
                throw new Error(`Server error ${res.status}`);
            }

            const reader  = res.body.getReader();
            const decoder = new TextDecoder();
            let buffer    = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop();

                for (const line of lines) {
                    if (!line.startsWith('data: ')) continue;
                    const raw = line.slice(6).trim();
                    if (raw === '[DONE]') break;

                    try {
                        const { delta } = JSON.parse(raw);
                        fullText += delta;
                        if (!bubble) {
                            window.document.getElementById('thinking-bubble')?.remove();
                            bubble = appendAssistantBubble(fullText);
                        } else {
                            bubble.innerHTML = marked.parse(fullText);
                            scrollToBottom();
                        }
                    } catch {}
                }
            }
        } catch (err) {
            window.document.getElementById('thinking-bubble')?.remove();
            if (bubble) bubble.closest('.flex.justify-start')?.remove();
            appendAssistantBubble('_Something went wrong. Please try again._');
        } finally {
            submit.disabled = false;

            if (fullText) {
                window.document.getElementById('thinking-bubble')?.remove();
                if (bubble) bubble.closest('.flex.justify-start')?.remove();
                await @this.call('appendAssistantMessage', fullText);
                scrollToBottom();
            }
        }
    });

    document.addEventListener('livewire:updated', scrollToBottom);
</script>
