@php($__next = app()->getLocale() === 'ka' ? 'en' : 'ka')
<a href="{{ route('locale.switch', $__next) }}"
   class="js-lang-toggle w-9 h-9 rounded-lg flex items-center justify-center text-xs font-semibold leading-none hover:bg-slate-100 text-slate-600"
   title="{{ __('common.switch_language') }}" aria-label="{{ __('common.switch_language') }}"
   rel="nofollow">{{ $__next === 'en' ? 'EN' : 'ქა' }}</a>
