<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title> {{config('app.name')}}-@yield('title')</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{asset('assets/app.css')}}">
    </head>
    <body>
        {{--bar de navigation--}}
        @include('navbar.navbar')
        {{--tous nos contenus seront affiche ici --}}
        @yield('content')
       {{--nos script javascript--}}
        @include('script')
    </body>
