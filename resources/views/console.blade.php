@extends('layout')
@section('title', 'ტესტ-კონსოლი')
@section('body')
<div class="h-screen flex flex-col">
    <header class="bg-white border-b shrink-0">
        <div class="px-4 h-14 flex items-center justify-between gap-4">
            <div class="font-bold">GTUH <span class="text-indigo-600">AI</span> · ტესტ-კონსოლი</div>
            <div class="flex items-center gap-3">
                <select id="config" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                    @foreach ($configs as $cfg)
                        <option value="{{ $cfg->id }}">{{ $cfg->name }} ({{ $cfg->model_tier }})</option>
                    @endforeach
                </select>
                <a href="/dashboard" class="text-sm text-slate-600 hover:text-slate-900">← პანელი</a>
            </div>
        </div>
    </header>

    <div class="flex-1 flex min-h-0">
        {{-- LEFT: trace --}}
        <div class="w-1/2 border-r bg-slate-900 text-slate-100 overflow-y-auto p-4" id="trace">
            <div class="text-slate-400 text-sm">დასვი კითხვა მარჯვნივ — აქ რეალურ დროში გამოჩნდება რა ხდება სისტემაში
                (query rewrite → embedding → ჰიბრიდული ძებნა → შერწყმა → Claude).</div>
        </div>

        {{-- RIGHT: chat --}}
        <div class="w-1/2 flex flex-col min-h-0 bg-slate-50">
            <div class="flex-1 overflow-y-auto p-4 space-y-3" id="chat"></div>
            <div class="border-t bg-white p-3 flex gap-2">
                <textarea id="q" rows="1" placeholder="დასვი კითხვა ქართულად…"
                          class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm resize-none"></textarea>
                <button id="send" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-5 font-medium">↑</button>
            </div>
        </div>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name=csrf-token]').content;
const chat = document.getElementById('chat');
const trace = document.getElementById('trace');
const qEl = document.getElementById('q');
const sendBtn = document.getElementById('send');
const configEl = document.getElementById('config');
let history = [];

configEl.addEventListener('change', () => { history = []; chat.innerHTML = ''; trace.innerHTML = ''; });

function esc(s){ return String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

function bubble(role, text){
  const wrap = document.createElement('div');
  wrap.className = 'flex ' + (role === 'user' ? 'justify-end' : 'justify-start');
  wrap.innerHTML = '<div class="max-w-[85%] rounded-2xl px-3 py-2 text-sm whitespace-pre-wrap ' +
    (role === 'user' ? 'bg-indigo-600 text-white' : 'bg-white border text-slate-800') + '">' + esc(text) + '</div>';
  chat.appendChild(wrap); chat.scrollTop = chat.scrollHeight;
  return wrap.firstChild;
}

function badge(p){
  const colors = { groq:'#f59e0b', gemini:'#3b82f6', anthropic:'#8b5cf6' };
  const label = { groq:'Groq', gemini:'Gemini', anthropic:'Claude' }[p] || p;
  return '<span style="background:'+(colors[p]||'#64748b')+'" class="text-white text-[10px] px-1.5 py-0.5 rounded">'+label+'</span>';
}
function card(title, badgeHtml, bodyHtml, ms){
  return '<div class="bg-slate-800 rounded-lg p-3 mb-2 border-l-4 border-indigo-500">' +
    '<div class="flex items-center justify-between mb-1"><div class="flex items-center gap-2 text-sm font-medium">'+esc(title)+' '+(badgeHtml||'')+'</div>' +
    (ms!=null?'<span class="text-[11px] text-slate-400">'+ms+' ms</span>':'') + '</div>' +
    '<div class="text-xs text-slate-300">'+bodyHtml+'</div></div>';
}
function cands(list){
  if(!list || !list.length) return '<div class="text-slate-500">—</div>';
  return list.map(c => '<div class="flex gap-2 py-0.5"><span class="text-indigo-300">#'+c.id+'</span>' +
    '<span class="text-emerald-300 w-14 shrink-0">'+c.score+'</span><span class="text-slate-400 truncate">'+esc(c.snippet)+'</span></div>').join('');
}

function renderTrace(t){
  if(!t){ trace.innerHTML = '<div class="text-slate-400 text-sm">trace არ მოვიდა.</div>'; return; }
  let html = '';
  // query / rewrite
  const rw = (t.steps||[]).find(s => s.step === 'query_rewrite');
  if(rw){
    html += card('საძიებო ფრაზის გადაწერა', badge('groq'),
      '<div>თავდაპირველი: <span class="text-slate-200">'+esc(rw.from)+'</span></div>' +
      '<div>გადაწერილი: <span class="text-amber-200">'+esc(rw.to)+'</span></div>', rw.ms);
  } else {
    html += card('საძიებო ფრაზა', '', '<span class="text-slate-200">'+esc(t.search_query)+'</span>', null);
  }
  const r = t.retrieval || {};
  if(r.embedding){
    html += card('ემბედინგი', badge('gemini'),
      '<div>მოდელი: '+esc(r.embedding.model)+'</div><div>განზომილება: '+r.embedding.dims+'</div>', r.embedding.ms);
  }
  if(r.semantic){
    html += card('სემანტიკური ძებნა (pgvector cosine)', '',
      '<div class="mb-1 text-slate-400">'+r.semantic.count+' კანდიდატი</div>'+cands(r.semantic.candidates), r.semantic.ms);
  }
  if(r.lexical){
    html += card('ლექსიკური ძებნა (BM25 / tsvector)', '',
      '<div class="mb-1 text-slate-400">'+r.lexical.count+' კანდიდატი</div>'+cands(r.lexical.candidates), r.lexical.ms);
  }
  if(r.fused){
    const chosen = (r.fused.chosen||[]).map(c => '<div class="flex gap-2 py-0.5"><span class="text-indigo-300">#'+c.id+'</span>' +
      '<span class="text-emerald-300 w-16 shrink-0">'+c.score+'</span><span class="text-slate-300">'+esc(c.title||'')+'</span></div>').join('');
    html += card('შერწყმა — '+esc(r.fused.method), '', chosen, null);
  }
  if(t.generate){
    html += card('პასუხის გენერაცია', badge('anthropic'),
      '<div>მოდელი: '+esc(t.generate.model)+'</div>' +
      '<div>ტოკენები: in '+t.generate.input_tokens+' / out '+t.generate.output_tokens+'</div>', t.generate.ms);
  }
  const tools = (t.generate && t.generate.tool_calls) || [];
  if(tools.length){
    const items = tools.map(tc => '<div class="py-1 border-b border-slate-700 last:border-0">' +
      '<span class="text-amber-300">🔧 '+esc(tc.name)+'</span> <span class="text-slate-500">'+esc(JSON.stringify(tc.input))+'</span>' +
      '<div class="text-slate-300 mt-0.5">→ '+esc(tc.result)+'</div></div>').join('');
    html += card('ხელსაწყოები (შესრულებული მოქმედებები)', '', items, null);
  }
  html += '<div class="text-[11px] text-slate-400 mt-2">სულ: '+t.total_ms+' ms · მეხსიერება: '+t.history_turns+' წინა შეტყობინება</div>';
  trace.innerHTML = html;
}

async function send(){
  const q = qEl.value.trim(); if(!q) return;
  qEl.value = ''; bubble('user', q);
  const thinking = bubble('assistant', '…');
  sendBtn.disabled = true;
  try {
    const res = await fetch('/dashboard/console/ask', {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
      body: JSON.stringify({ question:q, config_id: configEl.value, history })
    });
    const data = await res.json();
    let answer = data.answer || (data.message ? ('შეცდომა: '+data.message) : 'ბოდიში, ვერ ვუპასუხე.');
    if(data.sources && data.sources.length){
      answer += '\n\nწყაროები: ' + data.sources.map(s => '[#'+s.ref+'] '+(s.title||'')).join('  ');
    }
    thinking.textContent = answer;
    renderTrace(data.trace);
    history.push({role:'user', content:q}, {role:'assistant', content:data.answer || ''});
  } catch(e){
    thinking.textContent = 'შეცდომა: ' + e;
  } finally {
    sendBtn.disabled = false; qEl.focus();
  }
}
sendBtn.onclick = send;
qEl.addEventListener('keydown', e => { if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); send(); }});
</script>
@endsection
