@extends('errors.kfs')
@section('code', '401')
@section('title', 'تسجيل الدخول مطلوب')
@section('tone', '#B88920')
@section('icon', 'fa-solid fa-user-lock')
@section('heading', 'سجّل دخولك للمتابعة')
@section('message', 'هذه الصفحة متاحة للمستخدمين المسجلين فقط. سجّل الدخول بحسابك في البوابة، أو أنشئ حساباً جديداً في دقائق.')
@section('actions')
    <a class="btn btn-primary" href="{{ Route::has('login') ? route('login') : url('/login') }}"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> تسجيل الدخول</a>
    @if (Route::has('register.citizen'))
        <a class="btn btn-ghost" href="{{ route('register.citizen') }}"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> إنشاء حساب</a>
    @endif
    <a class="btn btn-ghost" href="{{ url('/') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> الرئيسية</a>
@endsection
