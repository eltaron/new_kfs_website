@extends('errors.kfs')
@section('code', '500')
@section('title', 'خطأ في الخادم')
@section('tone', '#A63A32')
@section('icon', 'fa-solid fa-triangle-exclamation')
@section('heading', 'حدث خطأ غير متوقع')
@section('message', 'نعتذر، واجهت البوابة مشكلة أثناء تنفيذ طلبك وتم تسجيلها تلقائياً ليعالجها الفريق الفني. حاول مرة أخرى بعد قليل.')
@section('search', 'off')
@section('actions')
    <button type="button" class="btn btn-primary" onclick="location.reload()"><i class="fa-solid fa-rotate-right" aria-hidden="true"></i> إعادة المحاولة</button>
    <a class="btn btn-ghost" href="{{ url('/') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> الرئيسية</a>
@endsection
