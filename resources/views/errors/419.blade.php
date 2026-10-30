@extends('errors.kfs')
@section('code', '419')
@section('title', 'انتهت صلاحية الجلسة')
@section('tone', '#B88920')
@section('icon', 'fa-solid fa-hourglass-end')
@section('heading', 'انتهت صلاحية الصفحة')
@section('message', 'مرّ وقت طويل دون نشاط، فانتهت صلاحية الجلسة حفاظاً على أمان بياناتك. أعد تحميل الصفحة وحاول مرة أخرى.')
@section('search', 'off')
@section('actions')
    <button type="button" class="btn btn-primary" onclick="history.length > 1 ? history.back() : location.reload()"><i class="fa-solid fa-rotate-right" aria-hidden="true"></i> الرجوع وإعادة المحاولة</button>
    <a class="btn btn-ghost" href="{{ url('/') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> الرئيسية</a>
@endsection
