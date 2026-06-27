<?php

namespace App\Http\Controllers;

use App\Models\AiConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Serves the embeddable chat widget: <script src="/embed.js?key=PUBLIC_KEY">.
 * Self-contained vanilla JS — injects a floating chat bubble that calls /public/chat.
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

        $base = rtrim(url('/'), '/');
        $js = $this->buildJs($key, $base, $config->name);

        return response($js, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'public, max-age=300');
    }

    private function buildJs(string $key, string $base, string $title): string
    {
        $template = <<<'JS'
(function () {
  var KEY = __KEY__, BASE = __BASE__, TITLE = __TITLE__;
  if (!KEY) return;
  var LS = 'gtuh_conv_' + KEY, VIS = 'gtuh_vis';
  var convId = localStorage.getItem(LS) || null;
  var visId = localStorage.getItem(VIS);
  if (!visId) { visId = 'v_' + Math.random().toString(36).slice(2) + Date.now().toString(36); localStorage.setItem(VIS, visId); }

  var css = '' +
    '.gtuh-btn{position:fixed;right:20px;bottom:20px;width:56px;height:56px;border-radius:50%;background:#4f46e5;color:#fff;font-size:24px;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 6px 20px rgba(0,0,0,.25);z-index:2147483000}' +
    '.gtuh-panel{position:fixed;right:20px;bottom:88px;width:360px;max-width:calc(100vw - 40px);height:520px;max-height:calc(100vh - 120px);background:#fff;border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.25);display:flex;flex-direction:column;overflow:hidden;z-index:2147483000;font-family:system-ui,Segoe UI,Roboto,sans-serif}' +
    '.gtuh-head{background:#4f46e5;color:#fff;padding:14px 16px;font-weight:600;display:flex;justify-content:space-between;align-items:center}' +
    '.gtuh-x{cursor:pointer;font-size:20px;line-height:1}' +
    '.gtuh-msgs{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:8px;background:#f8fafc}' +
    '.gtuh-msg{max-width:85%;padding:9px 12px;border-radius:12px;font-size:14px;line-height:1.45;white-space:pre-wrap;word-wrap:break-word}' +
    '.gtuh-user{align-self:flex-end;background:#4f46e5;color:#fff;border-bottom-right-radius:3px}' +
    '.gtuh-assistant{align-self:flex-start;background:#fff;border:1px solid #e2e8f0;color:#0f172a;border-bottom-left-radius:3px}' +
    '.gtuh-input{display:flex;gap:8px;padding:10px;border-top:1px solid #e2e8f0;background:#fff}' +
    '.gtuh-input textarea{flex:1;resize:none;border:1px solid #cbd5e1;border-radius:10px;padding:9px;font-size:14px;outline:none;font-family:inherit}' +
    '.gtuh-input button{background:#4f46e5;color:#fff;border:0;border-radius:10px;width:42px;font-size:16px;cursor:pointer}';
  var style = document.createElement('style'); style.textContent = css; document.head.appendChild(style);

  function escapeHtml(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

  var btn = document.createElement('div'); btn.className = 'gtuh-btn'; btn.textContent = '💬'; document.body.appendChild(btn);
  var panel = document.createElement('div'); panel.className = 'gtuh-panel'; panel.style.display = 'none';
  panel.innerHTML =
    '<div class="gtuh-head"><span>' + escapeHtml(TITLE) + '</span><span class="gtuh-x">×</span></div>' +
    '<div class="gtuh-msgs"></div>' +
    '<div class="gtuh-input"><textarea rows="1" placeholder="დაწერე შეტყობინება…"></textarea><button>➤</button></div>';
  document.body.appendChild(panel);

  var msgs = panel.querySelector('.gtuh-msgs');
  var ta = panel.querySelector('textarea');
  var open = false;
  function toggle() { open = !open; panel.style.display = open ? 'flex' : 'none'; if (open) ta.focus(); }
  btn.onclick = toggle; panel.querySelector('.gtuh-x').onclick = toggle;

  function addMsg(role, text) {
    var d = document.createElement('div'); d.className = 'gtuh-msg gtuh-' + role; d.textContent = text;
    msgs.appendChild(d); msgs.scrollTop = msgs.scrollHeight; return d;
  }
  addMsg('assistant', 'გამარჯობა! რით დაგეხმაროთ?');

  async function send() {
    var q = ta.value.trim(); if (!q) return;
    ta.value = ''; addMsg('user', q);
    var typing = addMsg('assistant', '…');
    try {
      var res = await fetch(BASE + '/public/chat', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ public_key: KEY, message: q, conversation_id: convId, visitor_id: visId })
      });
      var data = await res.json();
      typing.remove();
      if (data.conversation_id) { convId = data.conversation_id; localStorage.setItem(LS, convId); }
      addMsg('assistant', data.answer || (data.error ? ('შეცდომა: ' + data.error) : 'ბოდიში, ვერ ვუპასუხე.'));
    } catch (e) { typing.remove(); addMsg('assistant', 'კავშირის შეცდომა.'); }
  }
  panel.querySelector('button').onclick = send;
  ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); } });
})();
JS;

        return strtr($template, [
            '__KEY__' => json_encode($key),
            '__BASE__' => json_encode($base),
            '__TITLE__' => json_encode($title, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
