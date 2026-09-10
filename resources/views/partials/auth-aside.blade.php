{{-- Brand panel beside the auth form: says what this is before you sign in. --}}
<aside class="auth-aside">
    <a href="/" class="auth-brand">
        <span class="side-mark">CG</span>
        <span>Cortex<b>Grid</b></span>
    </a>
    <h2 class="auth-h">{!! __('landing.hero') !!}</h2>
    <p class="auth-p">{{ __('landing.hero_sub') }}</p>
    <ul class="auth-list">
        @foreach (['hybrid', 'agents', 'glassbox'] as $k)
            <li><span class="auth-tick"></span>{{ __('landing.features.'.$k.'.title') }}</li>
        @endforeach
    </ul>
    <div class="auth-engines">
        @foreach (['Groq','Gemini','Cerebras','Claude'] as $e)<span>{{ $e }}</span>@endforeach
    </div>
</aside>
