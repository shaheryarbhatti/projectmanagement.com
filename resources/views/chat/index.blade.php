@extends('layouts.excel-insights')

@section('title', 'Chat Assistant')
@section('page-title', 'Chat Assistant')

@section('content')
<div class="surface-card">
    <div class="chat-shell">
        <div class="surface-card" style="padding:18px;">
            <!-- <h3 class="h6 mb-3">Uploaded File</h3>
            @if($latestUpload)
                <div class="border rounded-4 p-3 mb-3"><div class="fw-semibold">{{ $latestUpload->original_name }}</div><div class="text-secondary small">Processed {{ optional($latestUpload->processed_at)->format('d M Y, h:i A') }}</div><div class="mt-2"><span class="badge bg-success">Processed Successfully</span></div></div>
            @else
                <div class="text-secondary">No workbook has been processed yet.</div>
            @endif -->
            <div class="border rounded-4 p-3 mb-3">
                <div class="fw-semibold mb-2">Data Focus</div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('chat.index', ['scope' => 'smart']) }}" class="btn btn-sm rounded-pill {{ $scope === 'smart' ? 'btn-primary' : 'btn-outline-primary' }}">Smart Records</a>
                    <a href="{{ route('chat.index', ['scope' => 'suspension']) }}" class="btn btn-sm rounded-pill {{ $scope === 'suspension' ? 'btn-primary' : 'btn-outline-primary' }}">Suspension History</a>
                    <!-- <a href="{{ route('chat.index', ['scope' => 'pivot']) }}" class="btn btn-sm rounded-pill {{ $scope === 'pivot' ? 'btn-primary' : 'btn-outline-primary' }}">Pivot Tables</a> -->
                </div>
            </div>
            <div class="border rounded-4 p-3">
                <div class="fw-semibold mb-2">Suggested Questions</div>
                <div id="suggestions-list" class="small text-secondary d-grid gap-2">@foreach($suggestions as $suggestion)<span class="cursor-pointer hover-link">{{ $suggestion }}</span>@endforeach</div>
            </div>
            <form method="POST" action="{{ route('chat.clear') }}" class="mt-3">@csrf<button class="btn btn-outline-secondary rounded-pill w-100">Clear Chat</button></form>
        </div>
        <div class="surface-card" style="padding:18px;">
            <div id="chat-stream" class="chat-stream mb-3">
                @forelse($messages as $message)
                    <div class="chat-bubble user"><div>{{ $message->question }}</div><div class="small text-secondary mt-2">{{ $message->created_at->format('h:i A') }}</div></div>
                    @php
                        $payload = $message->context_payload['direct_payload'] ?? null;
                    @endphp
                    <div class="chat-bubble assistant" data-payload="{{ json_encode($payload) }}">
                        <div class="markdown-content" data-raw="{{ $message->answer }}">{{ $message->answer }}</div>
                        <div class="small text-secondary mt-2">Source: {{ $message->provider ?? 'assistant' }}</div>
                    </div>
                @empty
                    <div class="chat-bubble assistant">Hi! I'm your data assistant. Ask me anything about the uploaded workbook.</div>
                @endforelse
                
                <!-- Typing Indicator -->
                <div id="typing-indicator" class="chat-bubble assistant" style="display:none; width: fit-content;">
                    <div class="typing-animation">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
            <form id="chat-form" class="d-flex gap-2">@csrf<input id="chat-input" type="text" class="form-control rounded-pill" placeholder="Type your question here..." {{ $latestUpload ? '' : 'disabled' }}><button class="btn btn-primary rounded-pill px-4" {{ $latestUpload ? '' : 'disabled' }}><i class="fa fa-paper-plane"></i></button></form>
            <div class="text-secondary small mt-3">AI responses are based on the currently imported workbook and may need manual verification for business decisions.</div>
        </div>
    </div>
</div>

<style>
.markdown-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 12px 0;
    font-size: 0.95rem;
    background: white;
    border-radius: 8px;
    overflow: hidden;
}
.markdown-content th, .markdown-content td {
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    text-align: left;
}
.markdown-content th {
    background-color: #f8fafc;
    font-weight: 600;
}
.markdown-content tr:nth-child(even) {
    background-color: #f9fafb;
}
.markdown-content h1, .markdown-content h2, .markdown-content h3 {
    margin-top: 16px;
    margin-bottom: 8px;
    font-weight: 700;
    color: #1e293b;
}
.markdown-content ul, .markdown-content ol {
    padding-left: 20px;
    margin-bottom: 12px;
}
.markdown-content p {
    margin-bottom: 8px;
}
.typing-animation {
    display: flex;
    gap: 4px;
    padding: 4px 0;
}
.typing-animation span {
    width: 8px;
    height: 8px;
    background-color: #8b97ac;
    border-radius: 50%;
    display: inline-block;
    animation: typing 1.4s infinite ease-in-out both;
}
.typing-animation span:nth-child(1) { animation-delay: -0.32s; }
.typing-animation span:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1.0); }
}
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
const stream = document.getElementById('chat-stream');
const form = document.getElementById('chat-form');
const input = document.getElementById('chat-input');
let chartIdCounter = 0;

// Initialize marked options
marked.setOptions({
    breaks: true,
    gfm: true
});

function appendBubble(role, content, meta = '', payload = null) {
    if (!stream) return;
    const bubble = document.createElement('div');
    bubble.className = `chat-bubble ${role}`;
    
    let chartHtml = '';
    if (payload && payload.chart) {
        chartIdCounter++;
        const chartId = 'chat-canvas-' + chartIdCounter;
        chartHtml = `
            <div class="mt-3 p-2 bg-white rounded-3 shadow-sm border" style="height:250px; position:relative; overflow:hidden;">
                <canvas id="${chartId}"></canvas>
                <div id="loader-${chartId}" class="position-absolute top-50 start-50 translate-middle small text-secondary">Loading...</div>
            </div>`;
            
        setTimeout(() => {
            const canvas = document.getElementById(chartId);
            const loader = document.getElementById('loader-' + chartId);
            if (!canvas || typeof Chart === 'undefined') return;
            try {
                new Chart(canvas, {
                    type: payload.chart.type,
                    data: {
                        labels: payload.chart.data.labels,
                        datasets: [{
                            label: payload.chart.title,
                            data: payload.chart.data.values,
                            backgroundColor: ['#2f6bff', '#2fb66e', '#ff9c45', '#8d67ff', '#8b97ac', '#ff5f72', '#00cfd5'],
                            borderWidth: 1
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
                });
                if (loader) loader.remove();
            } catch (err) { console.error('Chart error:', err); }
        }, 300);
    }
    
    const htmlContent = marked.parse(content);
    bubble.innerHTML = `<div class="markdown-content">${htmlContent}</div>` + chartHtml + (meta ? `<div class="small text-secondary mt-2">${meta}</div>` : '');
    stream.appendChild(bubble);
    stream.scrollTop = stream.scrollHeight;
}

// Render historical messages & handle charts
document.addEventListener('DOMContentLoaded', () => {
    // 1. Render all historical markdown
    document.querySelectorAll('.chat-bubble.assistant .markdown-content').forEach(el => {
        el.innerHTML = marked.parse(el.getAttribute('data-raw') || el.innerHTML);
    });

    // 2. Render historical charts
    document.querySelectorAll('.chat-bubble.assistant[data-payload]').forEach(el => {
        try {
            const payloadStr = el.getAttribute('data-payload');
            if (!payloadStr || payloadStr === 'null' || payloadStr === '[]') return;
            const payload = JSON.parse(payloadStr);
            if (payload && payload.chart) {
                const textEl = el.querySelector('.markdown-content');
                const meta = el.querySelector('.text-secondary')?.innerHTML || '';
                
                chartIdCounter++;
                const chartId = 'chat-canvas-' + chartIdCounter;
                el.innerHTML = `<div class="markdown-content">${textEl.innerHTML}</div>
                    <div class="mt-3 p-2 bg-white rounded-3 shadow-sm border" style="height:250px; position:relative; overflow:hidden;">
                        <canvas id="${chartId}"></canvas>
                        <div id="loader-${chartId}" class="position-absolute top-50 start-50 translate-middle small text-secondary">Loading...</div>
                    </div>
                    <div class="small text-secondary mt-2">${meta}</div>`;
                
                setTimeout(() => {
                    const canvas = document.getElementById(chartId);
                    if (canvas && typeof Chart !== 'undefined') {
                        new Chart(canvas, {
                            type: payload.chart.type,
                            data: {
                                labels: payload.chart.data.labels,
                                datasets: [{
                                    label: payload.chart.title,
                                    data: payload.chart.data.values,
                                    backgroundColor: ['#2f6bff', '#2fb66e', '#ff9c45', '#8d67ff', '#8b97ac', '#ff5f72', '#00cfd5']
                                }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
                        });
                        document.getElementById('loader-' + chartId)?.remove();
                    }
                }, 400);
            }
        } catch (e) { console.error('History chart error', e); }
    });

    // 3. Auto-scroll
    if (stream) stream.scrollTop = stream.scrollHeight;
});

if (form) {
    const typingIndicator = document.getElementById('typing-indicator');
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const question = input.value.trim();
        if (!question) return;
        appendBubble('user', question);
        input.value = '';
        input.disabled = true;
        if (typingIndicator) {
            typingIndicator.style.display = 'block';
            stream.appendChild(typingIndicator);
            stream.scrollTop = stream.scrollHeight;
        }
        try {
            const response = await fetch(@json(route('chat.store')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ question, scope: @json($scope) })
            });
            const data = await response.json();
            if (typingIndicator) typingIndicator.style.display = 'none';
            appendBubble('assistant', data.answer, 'Source: ' + (data.provider || 'assistant'), data.payload);
        } catch (error) {
            if (typingIndicator) typingIndicator.style.display = 'none';
            appendBubble('assistant', 'Error: ' + error.message, 'Source: system');
        } finally {
            input.disabled = false;
            input.focus();
        }
    });
}

// Handle suggestions
document.getElementById('suggestions-list')?.addEventListener('click', (e) => {
    if (e.target.classList.contains('cursor-pointer')) {
        input.value = e.target.innerText;
        form.dispatchEvent(new Event('submit'));
    }
});
</script>
@endpush
