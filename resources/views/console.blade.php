@extends('layout')
@section('title', 'ტესტ-კონსოლი')
@section('body')
<div class="h-screen flex flex-col">
    <header class="bg-white border-b shrink-0">
        <div class="px-4 h-14 flex items-center justify-between gap-4">
            <div class="font-bold"><a href="/">GTUH <span class="text-indigo-600">AI</span></a> · ტესტ-კონსოლი</div>
            <div class="flex items-center gap-3">
                <select id="config" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                    @foreach ($configs as $cfg)
                        <option value="{{ $cfg->id }}">{{ $cfg->name }} ({{ $cfg->model_tier }})</option>
                    @endforeach
                </select>
                <a href="/dashboard" class="text-sm text-slate-600 hover:text-slate-900">← პანელი</a>
                @include('partials.theme-toggle')
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

<style>
@keyframes traceIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
#trace .trace-card{animation:traceIn .42s ease both}
@keyframes thinkPulse{0%,100%{opacity:.35;transform:scale(.8)}50%{opacity:1;transform:scale(1)}}
.think-step{display:flex;align-items:center;gap:8px;padding:9px 11px;margin-bottom:8px;border-radius:8px;background:#1e293b;border-left:3px solid #334155;opacity:.5;transition:all .3s ease}
.think-step.on{opacity:1;border-left-color:#6366f1;background:#27344b;transform:translateX(4px)}
.think-dot{width:9px;height:9px;border-radius:50%;background:#6366f1;animation:thinkPulse 1s ease-in-out infinite;flex:0 0 auto}
</style>
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
  return '<div class="trace-card bg-slate-800 rounded-lg p-3 mb-2 border-l-4 border-indigo-500">' +
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
  const rr = (t.steps||[]).find(s => s.step === 'rerank');
  if(rr && rr.used){
    html += card('reranker (გადარანჟირება)', badge('groq'), '<div>'+rr.pool+' → '+rr.kept+' საუკეთესო პასაჟი</div>', rr.ms);
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
  [...trace.querySelectorAll('.trace-card')].forEach((el,i)=>el.style.animationDelay=(i*70)+'ms');
}

let thinkTimer = null;
function showThinking(hasHistory){
  const steps = [];
  if(hasHistory) steps.push(['groq','საძიებო ფრაზის გადაწერა']);
  steps.push(['gemini','კითხვის ემბედინგი'],['','სემანტიკური ძებნა (pgvector)'],['','ლექსიკური ძებნა (BM25)'],['','შერწყმა (RRF)'],['anthropic','პასუხის გენერაცია (Claude)']);
  trace.innerHTML = steps.map(s => '<div class="think-step"><span class="think-dot"></span>'+
    (s[0]?badge(s[0])+' ':'')+'<span class="text-slate-300 text-sm">'+esc(s[1])+'</span></div>').join('');
  const els = [...trace.querySelectorAll('.think-step')];
  let i = 0; els.forEach((e,idx)=>e.classList.toggle('on', idx===0));
  thinkTimer = setInterval(()=>{ i=(i+1)%els.length; els.forEach((e,idx)=>e.classList.toggle('on', idx===i)); }, 600);
}
function stopThinking(){ if(thinkTimer){ clearInterval(thinkTimer); thinkTimer=null; } }

async function send(){
  const q = qEl.value.trim(); if(!q) return;
  qEl.value = ''; bubble('user', q);
  const thinking = bubble('assistant', '…');
  sendBtn.disabled = true;
  showThinking(history.length > 0);
  try {
    const res = await fetch('/dashboard/console/ask', {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
      body: JSON.stringify({ question:q, config_id: configEl.value, history })
    });
    const data = await res.json();
    stopThinking();
    const answer = data.answer || (data.message ? ('შეცდომა: '+data.message) : 'ბოდიში, ვერ ვუპასუხე.');
    thinking.textContent = '';
    let i = 0;
    (function step(){
      if (i < answer.length) { thinking.textContent += answer.slice(i, i+4); i += 4; chat.scrollTop = chat.scrollHeight; setTimeout(step, 10); }
      else if (data.sources && data.sources.length) {
        const sd = document.createElement('div'); sd.className = 'mt-1 text-xs text-slate-500';
        sd.innerHTML = 'წყაროები: ' + data.sources.map(s => { const l='[#'+s.ref+'] '+esc(s.title||''); return s.url ? '<a href="'+esc(s.url)+'" target="_blank" class="underline text-indigo-600">'+l+'</a>' : l; }).join('  ');
        thinking.appendChild(sd);
      }
    })();
    renderTrace(data.trace);
    history.push({role:'user', content:q}, {role:'assistant', content:data.answer || ''});
  } catch(e){
    stopThinking();
    thinking.textContent = 'შეცდომა: ' + e;
  } finally {
    sendBtn.disabled = false; qEl.focus();
  }
}
sendBtn.onclick = send;
qEl.addEventListener('keydown', e => { if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); send(); }});
</script>
@endsection
