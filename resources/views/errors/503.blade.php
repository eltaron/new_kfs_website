@extends('errors.kfs')
@section('code', '503')
@section('title', 'البوابة تحت الصيانة')
@section('tone', '#00788A')
@section('icon', 'fa-solid fa-screwdriver-wrench')
@section('heading', 'نعمل على تحسين البوابة')
@section('message', 'بوابة محافظة كفر الشيخ في صيانة مجدولة لتقديم خدمة أفضل. نعود إليكم خلال وقت قصير، شكراً لصبركم.')
@section('search', 'off')
@section('actions')
    <button type="button" class="btn btn-primary" onclick="location.reload()"><i class="fa-solid fa-rotate-right" aria-hidden="true"></i> تحديث الصفحة</button>
@endsection
