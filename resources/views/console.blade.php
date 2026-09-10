@extends('layout-app')
@section('title', __('console.title'))
@section('heading'){{ __('console.title') }}@endsection
@section('crumb'){{ __('common.app_title') }}@endsection
@section('actions')
    <select id="config" class="px-3 py-1.5 text-sm" style="max-width:280px">
        @foreach ($configs as $cfg)
            <option value="{{ $cfg->id }}">{{ $cfg->name }} ({{ $cfg->model_tier }})</option>
        @endforeach
    </select>
@endsection

@section('content')
<div class="console-fill">

    <div class="flex-1 flex min-h-0 console-split">
        {{-- LEFT: trace --}}
        <div class="w-1/2 border-r overflow-y-auto p-4" style="background:var(--code-bg)" id="trace">
            <div class="text-slate-400 text-sm">{{ __('console.trace_intro') }}</div>
        </div>

        {{-- RIGHT: chat --}}
        <div class="w-1/2 flex flex-col min-h-0 js-chat-pane">
            <div class="flex-1 overflow-y-auto p-4 space-y-3" id="chat"></div>
            <div class="border-t bg-white p-3 flex gap-2">
                <textarea id="q" rows="1" placeholder="{{ __('console.ask_placeholder') }}"
                          class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm resize-none"></textarea>
                <button id="send" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-5 font-medium">↑</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Break out of the shell's padding: the console is an instrument panel. */
.console-fill{ height:calc(100vh - 60px); display:flex; flex-direction:column;
    margin:-26px -22px -70px; border-top:0 }
.console-split{ border-top:1px solid var(--line) }

.js-chat-pane{background:linear-gradient(180deg,rgba(14,21,38,.5),rgba(8,13,26,.72))}
#trace{border-right:1px solid rgba(34,211,238,.22);box-shadow:inset -18px 0 32px -30px rgba(34,211,238,.9)}
#trace::-webkit-scrollbar-thumb{background:rgba(34,211,238,.25)}
/* the composer sits on its own bar so the split reads clearly */
.js-chat-pane > .border-t{background:rgba(4,8,18,.72)!important;backdrop-filter:blur(12px);
    border-top:1px solid var(--line)!important}

@keyframes traceIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
#trace .trace-card{animation:traceIn .42s ease both}
@keyframes thinkPulse{0%,100%{opacity:.35;transform:scale(.8)}50%{opacity:1;transform:scale(1)}}
.think-step{display:flex;align-items:center;gap:9px;padding:10px 12px;margin-bottom:8px;border-radius:10px;
    background:var(--surface);border:1px solid var(--line);border-left:3px solid var(--line);
    opacity:.45;transition:all .3s ease}
.think-step.on{opacity:1;border-left-color:var(--accent);background:rgba(34,211,238,.09);
    transform:translateX(5px);box-shadow:0 0 24px -8px rgba(34,211,238,.6)}
.think-dot{width:8px;height:8px;border-radius:50%;background:var(--accent);box-shadow:0 0 10px var(--accent);
    animation:thinkPulse 1s ease-in-out infinite;flex:0 0 auto}
.trace-card{background:var(--surface)!important;border:1px solid var(--line)!important;
    border-left:3px solid var(--accent)!important;border-radius:12px}
</style>
@php($__t = [
    'no_trace' => __('console.no_trace'),
    'rewrite' => __('console.rewrite'),
    'rewrite_from' => __('console.rewrite_from'),
    'rewrite_to' => __('console.rewrite_to'),
    'search_query' => __('console.search_query'),
    'embedding' => __('console.embedding'),
    'model' => __('console.model'),
    'dims' => __('console.dims'),
    'semantic' => __('console.semantic'),
    'lexical' => __('console.lexical'),
    'candidates' => __('console.candidates'),
    'fusion' => __('console.fusion'),
    'rerank' => __('console.rerank'),
    'rerank_body' => __('console.rerank_body'),
    'generate' => __('console.generate'),
    'tokens' => __('console.tokens'),
    'tools' => __('console.tools'),
    'total' => __('console.total'),
    'memory' => __('console.memory'),
    'prev_messages' => __('console.prev_messages'),
    'sources' => __('conversations.sources'),
    'error' => __('common.error'),
    'no_answer' => __('console.no_answer'),
    'think' => [
        'rewrite' => __('console.think.rewrite'),
        'embed' => __('console.think.embed'),
        'semantic' => __('console.think.semantic'),
        'lexical' => __('console.think.lexical'),
        'fuse' => __('console.think.fuse'),
        'generate' => __('console.think.generate'),
    ],
])
<script>
const csrf = document.querySelector('meta[name=csrf-token]').content;
const T = @json($__t);
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
  const colors = { groq:'#fbbf24', gemini:'#22d3ee', anthropic:'#a78bfa', cerebras:'#34d399', openrouter:'#f472b6', nvidia:'#84cc16' };
  const label = { groq:'Groq', gemini:'Gemini', anthropic:'Claude', cerebras:'Cerebras', openrouter:'OpenRouter', nvidia:'NVIDIA' }[p] || p;
  return '<span style="background:'+(colors[p]||'#64748b')+'22;border:1px solid '+(colors[p]||'#64748b')+'66;color:'+(colors[p]||'#94a3b8')+'" class="text-[10px] px-2 py-0.5 rounded-md font-medium">'+label+'</span>';
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
  if(!t){ trace.innerHTML = '<div class="text-slate-400 text-sm">'+esc(T.no_trace)+'</div>'; return; }
  let html = '';
  // query / rewrite
  const rw = (t.steps||[]).find(s => s.step === 'query_rewrite');
  if(rw){
    html += card(T.rewrite, badge('groq'),
      '<div>'+esc(T.rewrite_from)+': <span class="text-slate-200">'+esc(rw.from)+'</span></div>' +
      '<div>'+esc(T.rewrite_to)+': <span class="text-amber-200">'+esc(rw.to)+'</span></div>', rw.ms);
  } else {
    html += card(T.search_query, '', '<span class="text-slate-200">'+esc(t.search_query)+'</span>', null);
  }
  const r = t.retrieval || {};
  if(r.embedding){
    html += card(T.embedding, badge('gemini'),
      '<div>'+esc(T.model)+': '+esc(r.embedding.model)+'</div><div>'+esc(T.dims)+': '+r.embedding.dims+'</div>', r.embedding.ms);
  }
  if(r.semantic){
    html += card(T.semantic, '',
      '<div class="mb-1 text-slate-400">'+r.semantic.count+' '+esc(T.candidates)+'</div>'+cands(r.semantic.candidates), r.semantic.ms);
  }
  if(r.lexical){
    html += card(T.lexical, '',
      '<div class="mb-1 text-slate-400">'+r.lexical.count+' '+esc(T.candidates)+'</div>'+cands(r.lexical.candidates), r.lexical.ms);
  }
  if(r.fused){
    const chosen = (r.fused.chosen||[]).map(c => '<div class="flex gap-2 py-0.5"><span class="text-indigo-300">#'+c.id+'</span>' +
      '<span class="text-emerald-300 w-16 shrink-0">'+c.score+'</span><span class="text-slate-300">'+esc(c.title||'')+'</span></div>').join('');
    html += card(T.fusion+' — '+r.fused.method, '', chosen, null);
  }
  const rr = (t.steps||[]).find(s => s.step === 'rerank');
  if(rr && rr.used){
    html += card(T.rerank, badge('groq'), '<div>'+rr.pool+' → '+rr.kept+' '+esc(T.rerank_body)+'</div>', rr.ms);
  }
  if(t.generate){
    html += card(T.generate, badge('anthropic'),
      '<div>'+esc(T.model)+': '+esc(t.generate.model)+'</div>' +
      '<div>'+esc(T.tokens)+': in '+t.generate.input_tokens+' / out '+t.generate.output_tokens+'</div>', t.generate.ms);
  }
  const tools = (t.generate && t.generate.tool_calls) || [];
  if(tools.length){
    const items = tools.map(tc => '<div class="py-1 border-b border-slate-700 last:border-0">' +
      '<span class="text-amber-300">🔧 '+esc(tc.name)+'</span> <span class="text-slate-500">'+esc(JSON.stringify(tc.input))+'</span>' +
      '<div class="text-slate-300 mt-0.5">→ '+esc(tc.result)+'</div></div>').join('');
    html += card(T.tools, '', items, null);
  }
  html += '<div class="text-[11px] text-slate-400 mt-2">'+esc(T.total)+': '+t.total_ms+' ms · '+esc(T.memory)+': '+t.history_turns+' '+esc(T.prev_messages)+'</div>';
  trace.innerHTML = html;
  [...trace.querySelectorAll('.trace-card')].forEach((el,i)=>el.style.animationDelay=(i*70)+'ms');
}

let thinkTimer = null;
function showThinking(hasHistory){
  const steps = [];
  if(hasHistory) steps.push(['groq',T.think.rewrite]);
  steps.push(['gemini',T.think.embed],['',T.think.semantic],['',T.think.lexical],['',T.think.fuse],['anthropic',T.think.generate]);
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
    const answer = data.answer || (data.message ? (T.error+': '+data.message) : T.no_answer);
    thinking.textContent = '';
    let i = 0;
    (function step(){
      if (i < answer.length) { thinking.textContent += answer.slice(i, i+4); i += 4; chat.scrollTop = chat.scrollHeight; setTimeout(step, 10); }
      else if (data.sources && data.sources.length) {
        const sd = document.createElement('div'); sd.className = 'mt-1 text-xs text-slate-500';
        sd.innerHTML = esc(T.sources) + ': ' + data.sources.map(s => { const l='[#'+s.ref+'] '+esc(s.title||''); return s.url ? '<a href="'+esc(s.url)+'" target="_blank" class="underline text-indigo-600">'+l+'</a>' : l; }).join('  ');
        thinking.appendChild(sd);
      }
    })();
    renderTrace(data.trace);
    history.push({role:'user', content:q}, {role:'assistant', content:data.answer || ''});
  } catch(e){
    stopThinking();
    thinking.textContent = T.error + ': ' + e;
  } finally {
    sendBtn.disabled = false; qEl.focus();
  }
}
sendBtn.onclick = send;
qEl.addEventListener('keydown', e => { if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); send(); }});
</script>
@endsection
