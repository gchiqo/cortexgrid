<?php

namespace App\Http\Controllers;

use App\Models\AiConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Serves the embeddable chat widget: <script src="/embed.js?key=PUBLIC_KEY">.
 * Self-contained vanilla JS — streaming answers, clickable citations, feedback,
 * lead capture, and per-agent appearance.
 */
class WidgetController extends Controller
{
    public function embed(Request $request): Response
    {
        $key = (string) $request->query('key', '');
        $config = AiConfig::where('public_key', $key)->where('widget_enabled', true)->first();

        if (! $config) {
            return response("console.warn('GTUH widget: invalid or disabled key');", 200)
                ->header('Content-Type', 'application/javascript');
        }

        $js = $this->buildJs($key, rtrim(url('/'), '/'), $config->widget());

        return response($js, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'public, max-age=120');
    }

    private function buildJs(string $key, string $base, array $w): string
    {
        $template = <<<'JS'
(function () {
  var KEY = __KEY__, BASE = __BASE__, TITLE = __TITLE__, GREETING = __GREETING__,
      COLOR = __COLOR__, SIDE = __SIDE__, LAUNCHER = __LAUNCHER__;
  if (!KEY) return;
  var LS = 'gtuh_conv_' + KEY, VIS = 'gtuh_vis';
  var convId = localStorage.getItem(LS) || null;
  var visId = localStorage.getItem(VIS) || ('v_' + Math.random().toString(36).slice(2) + Date.now().toString(36));
  localStorage.setItem(VIS, visId);

  var sideCss = SIDE === 'left' ? 'left:20px' : 'right:20px';
  var css = '' +
    '.gtuh-btn{position:fixed;' + sideCss + ';bottom:20px;width:56px;height:56px;border-radius:50%;background:' + COLOR + ';color:#fff;font-size:24px;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 6px 20px rgba(0,0,0,.25);z-index:2147483000}' +
    '.gtuh-panel{position:fixed;' + sideCss + ';bottom:88px;width:370px;max-width:calc(100vw - 40px);height:540px;max-height:calc(100vh - 120px);background:#fff;border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.25);display:flex;flex-direction:column;overflow:hidden;z-index:2147483000;font-family:system-ui,Segoe UI,Roboto,sans-serif}' +
    '.gtuh-head{background:' + COLOR + ';color:#fff;padding:14px 16px;font-weight:600;display:flex;justify-content:space-between;align-items:center}' +
    '.gtuh-x{cursor:pointer;font-size:20px;line-height:1}' +
    '.gtuh-msgs{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:8px;background:#f8fafc}' +
    '.gtuh-msg{max-width:85%;padding:9px 12px;border-radius:12px;font-size:14px;line-height:1.45;white-space:pre-wrap;word-wrap:break-word}' +
    '.gtuh-user{align-self:flex-end;background:' + COLOR + ';color:#fff;border-bottom-right-radius:3px}' +
    '.gtuh-assistant{align-self:flex-start;background:#fff;border:1px solid #e2e8f0;color:#0f172a;border-bottom-left-radius:3px}' +
    '.gtuh-typing::after{content:"▍";animation:gtuhBlink 1s steps(2) infinite}' +
    '@keyframes gtuhBlink{0%,100%{opacity:1}50%{opacity:0}}' +
    '.gtuh-src{margin-top:6px;font-size:11px;color:#64748b}' +
    '.gtuh-src a{color:' + COLOR + ';text-decoration:underline}' +
    '.gtuh-fb{display:flex;gap:6px;margin-top:6px}' +
    '.gtuh-fb button{background:none;border:0;cursor:pointer;font-size:14px;opacity:.45;padding:1px 3px;border-radius:6px}' +
    '.gtuh-fb button:hover,.gtuh-fb button.sel{opacity:1;background:#eef2ff}' +
    '.gtuh-lead{margin-top:8px;border:1px solid #e2e8f0;border-radius:10px;padding:10px;background:#fff;display:flex;flex-direction:column;gap:6px}' +
    '.gtuh-lead input,.gtuh-lead textarea{border:1px solid #cbd5e1;border-radius:8px;padding:7px;font-size:13px;font-family:inherit}' +
    '.gtuh-lead button{background:' + COLOR + ';color:#fff;border:0;border-radius:8px;padding:8px;font-size:13px;cursor:pointer}' +
    '.gtuh-input{display:flex;gap:8px;padding:10px;border-top:1px solid #e2e8f0;background:#fff}' +
    '.gtuh-input textarea{flex:1;resize:none;border:1px solid #cbd5e1;border-radius:10px;padding:9px;font-size:14px;outline:none;font-family:inherit}' +
    '.gtuh-input button{background:' + COLOR + ';color:#fff;border:0;border-radius:10px;width:42px;font-size:16px;cursor:pointer}' +
    '.gtuh-foot{font-size:10px;color:#94a3b8;text-align:center;padding:0 0 6px;cursor:pointer}';
  var style = document.createElement('style'); style.textContent = css; document.head.appendChild(style);

  function esc(s){ return String(s == null ? '' : s).replace(/[&<>"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }

  var btn = document.createElement('div'); btn.className = 'gtuh-btn'; btn.textContent = LAUNCHER; document.body.appendChild(btn);
  var panel = document.createElement('div'); panel.className = 'gtuh-panel'; panel.style.display = 'none';
  panel.innerHTML =
    '<div class="gtuh-head"><span>' + esc(TITLE) + '</span><span class="gtuh-x">×</span></div>' +
    '<div class="gtuh-msgs"></div>' +
    '<div class="gtuh-foot">📩 დატოვე კონტაქტი</div>' +
    '<div class="gtuh-input"><textarea rows="1" placeholder="დაწერე შეტყობინება…"></textarea><button>➤</button></div>';
  document.body.appendChild(panel);

  var msgs = panel.querySelector('.gtuh-msgs');
  var ta = panel.querySelector('textarea');
  var open = false;
  function toggle(){ open = !open; panel.style.display = open ? 'flex' : 'none'; if (open) ta.focus(); }
  btn.onclick = toggle; panel.querySelector('.gtuh-x').onclick = toggle;

  function addMsg(role, text){
    var d = document.createElement('div'); d.className = 'gtuh-msg gtuh-' + role; d.textContent = text || '';
    msgs.appendChild(d); msgs.scrollTop = msgs.scrollHeight; return d;
  }
  function addSources(el, sources){
    if (!sources || !sources.length) return;
    var d = document.createElement('div'); d.className = 'gtuh-src';
    d.innerHTML = 'წყაროები: ' + sources.map(function(s){
      var label = '[#' + s.ref + '] ' + esc(s.title || '');
      return s.url ? '<a href="' + esc(s.url) + '" target="_blank" rel="noopener">' + label + '</a>' : label;
    }).join('  ');
    el.appendChild(d);
  }
  function addFeedback(el, msgId){
    var fb = document.createElement('div'); fb.className = 'gtuh-fb';
    [['👍',1],['👎',-1]].forEach(function(p){
      var b = document.createElement('button'); b.textContent = p[0];
      b.onclick = function(){
        fb.querySelectorAll('button').forEach(function(x){ x.classList.remove('sel'); });
        b.classList.add('sel');
        fetch(BASE + '/public/feedback', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({public_key:KEY, message_id:msgId, value:p[1]})}).catch(function(){});
      };
      fb.appendChild(b);
    });
    el.appendChild(fb);
  }
  function leadForm(parent){
    if (parent.querySelector('.gtuh-lead')) return;
    var f = document.createElement('div'); f.className = 'gtuh-lead';
    f.innerHTML = '<div style="font-size:12px;color:#475569">დატოვე კონტაქტი და დაგიკავშირდებით:</div>' +
      '<input placeholder="სახელი" data-n>' +
      '<input placeholder="ელ. ფოსტა ან ტელეფონი" data-c>' +
      '<button>გაგზავნა</button>';
    f.querySelector('button').onclick = function(){
      var name = f.querySelector('[data-n]').value;
      var contact = f.querySelector('[data-c]').value;
      if (!contact){ return; }
      var isEmail = contact.indexOf('@') >= 0;
      fetch(BASE + '/public/lead', {method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({public_key:KEY, conversation_id:convId, name:name, email:isEmail?contact:'', phone:isEmail?'':contact})
      }).then(function(){ f.innerHTML = '<div style="font-size:13px;color:#16a34a">✓ მადლობა! დაგიკავშირდებით.</div>'; }).catch(function(){});
    };
    parent.appendChild(f); msgs.scrollTop = msgs.scrollHeight;
  }
  panel.querySelector('.gtuh-foot').onclick = function(){ leadForm(msgs); };

  addMsg('assistant', GREETING);

  async function send(){
    var q = ta.value.trim(); if (!q) return;
    ta.value = ''; addMsg('user', q);
    var bubble = addMsg('assistant', ''); bubble.classList.add('gtuh-typing');
    var answer = '', done = null;
    try {
      var res = await fetch(BASE + '/public/chat/stream', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ public_key: KEY, message: q, conversation_id: convId, visitor_id: visId })
      });
      if (!res.ok || !res.body) throw new Error('no stream');
      var reader = res.body.getReader(), dec = new TextDecoder(), buf = '';
      while (true) {
        var r = await reader.read(); if (r.done) break;
        buf += dec.decode(r.value, { stream: true });
        var idx;
        while ((idx = buf.indexOf('\n\n')) >= 0) {
          var ev = buf.slice(0, idx); buf = buf.slice(idx + 2);
          var line = ev.split('\n').filter(function(l){ return l.indexOf('data:') === 0; })[0];
          if (!line) continue;
          var data; try { data = JSON.parse(line.slice(5).trim()); } catch (e) { continue; }
          if (data.t !== undefined) { answer += data.t; bubble.textContent = answer; msgs.scrollTop = msgs.scrollHeight; }
          else if (data.message_id !== undefined || data.sources !== undefined) { done = data; }
        }
      }
    } catch (e) { /* fall through to done handling */ }
    bubble.classList.remove('gtuh-typing');
    if (done) {
      if (done.conversation_id) { convId = done.conversation_id; localStorage.setItem(LS, convId); }
      if (!answer && done.answer) { bubble.textContent = done.answer; }
      addSources(bubble, done.sources);
      if (done.message_id) addFeedback(bubble, done.message_id);
      if (done.answered === false) leadForm(bubble);
    } else if (!answer) {
      bubble.textContent = 'კავშირის შეცდომა.';
    }
  }
  panel.querySelector('.gtuh-input button').onclick = send;
  ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); } });
})();
JS;

        return strtr($template, [
            '__KEY__' => json_encode($key),
            '__BASE__' => json_encode($base),
            '__TITLE__' => json_encode($w['title'], JSON_UNESCAPED_UNICODE),
            '__GREETING__' => json_encode($w['greeting'], JSON_UNESCAPED_UNICODE),
            '__COLOR__' => json_encode($w['color']),
            '__SIDE__' => json_encode($w['position']),
            '__LAUNCHER__' => json_encode($w['launcher'], JSON_UNESCAPED_UNICODE),
        ]);
    }
}
