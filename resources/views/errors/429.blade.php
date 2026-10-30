@extends('errors.kfs')
@section('code', '429')
@section('title', 'طلبات كثيرة')
@section('tone', '#657342')
@section('icon', 'fa-solid fa-gauge-high')
@section('heading', 'طلبات كثيرة في وقت قصير')
@section('message', 'لحماية البوابة تم إيقاف طلباتك مؤقتاً. انتظر دقيقة واحدة ثم حاول مرة أخرى.')
@section('search', 'off')
@section('actions')
    <button type="button" class="btn btn-primary" onclick="location.reload()"><i class="fa-solid fa-rotate-right" aria-hidden="true"></i> إعادة المحاولة</button>
    <a class="btn btn-ghost" href="{{ url('/') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> الرئيسية</a>
@endsection
