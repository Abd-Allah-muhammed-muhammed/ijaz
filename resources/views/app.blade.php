@php($locale = str_replace('_', '-', app()->getLocale()))
@use(App\Services\Translations\TranslationServices)
@php($direction = in_array($locale, ['ar', 'ur']) ? 'rtl' : 'ltr')
<!DOCTYPE html>
<html lang="{{ $locale }}" @class(['dark' => ($appearance ?? 'system') === 'dark'])
  data-bs-theme="{{$appearance ?? 'system'}}" dir="{{$direction}}" direction="{{$direction}}" data-inertia="true"
  data-direction="{{$direction}}" style="direction: {{$direction}};">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="csrf" content="{{csrf_token()}}" />
  <meta name="theme-color" content="#000000" />
  <meta name="description" content="Ijaz" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
  <title inertia>{{ config('app.name', 'Ijaz') }}</title>
  <link rel="shortcut icon" href="{{asset('/media/logos/default.svg')}}" />
  <link rel="stylesheet" id="layout-styles-anchor" href="{{asset('splash-screen.css')}}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Iceland&display=swap"
    rel="stylesheet">
  @viteReactRefresh
  {{-- {!!app(TranslationServices::class)->render($locale)!!}--}}
  @locales
  @if($direction === 'rtl')
    <link rel="stylesheet" href="{{asset('css/style.bundle.rtl.css')}}" />
  @else
    <link rel="stylesheet" href="{{asset('css/style.bundle.css')}}" />
  @endif
  @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
  @inertiaHead
  <style>
    #app,
    body {
      min-height: 100vh;
    }

    .dropdown-toggle::after {
      display: none !important;
    }
  </style>

  @if(!\Illuminate\Support\Facades\Route::currentRouteNamed('dashboard.*'))
    <!-- TikTok Pixel Code Start -->
    <script>
      !function (w, d, t) {
        w.TiktokAnalyticsObject = t;
        var ttq = w[t] = w[t] || [];
        ttq.methods = ["page", "track", "identify", "instances", "debug", "on", "off", "once", "ready", "alias", "group", "enableCookie", "disableCookie", "holdConsent", "revokeConsent", "grantConsent"], ttq.setAndDefer = function (t, e) {
          t[e] = function () {
            t.push([e].concat(Array.prototype.slice.call(arguments, 0)))
          }
        };
        for (var i = 0; i < ttq.methods.length; i++) ttq.setAndDefer(ttq, ttq.methods[i]);
        ttq.instance = function (t) {
          for (
            var e = ttq._i[t] || [], n = 0; n < ttq.methods.length; n++) ttq.setAndDefer(e, ttq.methods[n]);
          return e
        }, ttq.load = function (e, n) {
          var r = "https://analytics.tiktok.com/i18n/pixel/events.js", o = n && n.partner;
          ttq._i = ttq._i || {}, ttq._i[e] = [], ttq._i[e]._u = r, ttq._t = ttq._t || {}, ttq._t[e] = +new Date, ttq._o = ttq._o || {}, ttq._o[e] = n || {};
          n = document.createElement("script")
            ; n.type = "text/javascript", n.async = !0, n.src = r + "?sdkid=" + e + "&lib=" + t;
          e = document.getElementsByTagName("script")[0];
          e.parentNode.insertBefore(n, e)
        };
        ttq.load('D4O4TEBC77UCJ0N6P94G');
        ttq.page();
      }(window, document, 'ttq');
    </script>
    <!-- TikTok Pixel Code End -->
  @endif
</head>

<body id="kt_body" class="" direction="{{$direction}}" dir="{{$direction}}" style="direction: {{$direction}};">
  {{--

  <body id="kt_body" class="page-loading">--}}
    <noscript>You need to enable JavaScript to run this app.</noscript>
    <!--begin::Theme mode setup on page load-->
    <script>
      let themeMode = '{{$appearance ?? 'system'}}'
      if (themeMode === 'system') {
        themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
      }
      document.documentElement.setAttribute('data-bs-theme', themeMode)
    </script>


    {{--
    <script src="{{asset('pan.iife.js')}}"></script>--}}
    <!--end::Theme mode setup on page load-->
    @inertia
    {{--
    <script src="{{asset('')}}"></script>--}}
    <!--begin::Loading markup-->
    {{--<div id="splash-screen" class="splash-screen">--}}
      {{-- <img src="{{asset('logo.png')}}" class="dark-logo" alt="{{config('app.name')}} dark logo" />--}}
      {{-- <img src="{{asset('logo.png')}}" class="light-logo" alt="{{config('app.name')}} light logo" />--}}
      {{-- <div class="loader-wrapper">--}}
        {{-- <span class="loader"></span>--}}
        {{-- <span class="loading-text">coming soon Loading...</span>--}}
        {{-- </div>--}}
      {{--</div>--}}
    <!--end::Loading markup-->
  </body>

</html>