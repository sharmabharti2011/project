<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Warehouse Space</title>
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token()}}">
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    </head>
    <body>
        <div id="loaderOverlay">
            <div class="loader">
                <div class="side"></div>
                <div class="side"></div>
                <div class="side"></div>
                <div class="side"></div>
                <div class="side"></div>
                <div class="side"></div>
                <div class="side"></div>
                <div class="side"></div>
            </div>
        </div>
        @if(Request::segment(3) != "details")
        <nav class="navbar navbar-expand-lg navbar-dark default-color">
            <div class="container">
                <a class="ws-logo" href="{{ route('dashboard',$slug) }}">
                    <img src="{{ asset('/images/WSLogo.png') }}" alt="logo">
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarResponsive">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item @if(Request::route()->getName() == 'dashboard') active @endif">
                            <a class="nav-link" href="{{ route('dashboard',$slug) }}">Instructions</a>
                        </li>
                        <li class="nav-item @if(Request::route()->getName() == 'warehouse.setting') active @endif">
                            <a class="nav-link" href="{{ route('warehouse.setting',$slug) }}">Settings</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        @endif
        @if(Session::has('success-message') || Session::has('error-message'))
        <div id="redirect_alert" class="alert @if(Session::has('success-message')) alert-success @elseif(Session::has('error-message')) alert-danger @endif alert-dismissable">
            <a href="javascript:void(0);" onclick="$(this).parent().remove();" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
            <strong>@if(Session::has('success-message')) Success! @elseif(Session::has('error-message')) Error! @endif </strong>@if(Session::has('success-message')) {{ Session::pull('success-message') }} @elseif(Session::has('error-message')) {{ Session::pull('error-message') }} @endif
        </div>
        @endif
        <div class="main-wrapper-content">
            @if(Request::segment(3) != "details")
                <h1 class="text-center">Warehouse.Space</h1>
            @endif
            <div class="container">
                @yield('content')
            </div>
        </div>
        <script src="{{ asset('js/app.js') }}"></script>
        @stack('scripts')
    </body>
</html>
