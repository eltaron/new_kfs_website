@extends('marketplace::layouts.store')

@php
    $title = $category ? $category->name : (filled($filters['q'] ?? null) ? 'نتائج البحث عن «' . $filters['q'] . '»' : 'كل المنتجات');
@endphp

@section('store-title', $title)
@section('store-heading', $title)
@if ($category && $category->description)
    @section('store-lead', $category->description)
@endif
@section('store-breadcrumb')
    @if ($category)
        <li class="breadcrumb-item"><a href="{{ route('market.products.index') }}">المنتجات</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
    @else
        <li class="breadcrumb-item active" aria-current="page">المنتجات</li>
    @endif
@endsection

@section('store-content')
    <div class="mkt-layout">
        {{-- Filters --}}
        <aside aria-labelledby="filters-title">
            <form method="get" action="{{ $category ? route('market.categories.show', $category->slug) : route('market.products.index') }}" class="card-kfs mkt-filters mkt-sticky" role="search">
                <div class="card-body">
                    <h2 id="filters-title" style="font-size:1.2em">تصفية النتائج</h2>

                    <div class="form-group">
                        <label for="f-q">كلمة البحث</label>
                        <input type="search" id="f-q" name="q" value="{{ $filters['q'] ?? '' }}" class="field" maxlength="100">
                    </div>

                    @unless ($category)
                        <div class="form-group">
                            <label for="f-category">القسم</label>
                            <select id="f-category" name="category" class="field">
                                <option value="">كل الأقسام</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->slug }}" @selected(($filters['category'] ?? '') === $cat->slug)>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endunless

                    <fieldset>
                        <legend>السعر (ج.م)</legend>
                        <div class="form-group">
                            <label for="f-min">من</label>
                            <input type="number" id="f-min" name="min" min="0" step="1" inputmode="numeric" value="{{ $filters['min'] ?? '' }}" class="field">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label for="f-max">إلى</label>
                            <input type="number" id="f-max" name="max" min="0" step="1" inputmode="numeric" value="{{ $filters['max'] ?? '' }}" class="field">
                        </div>
                    </fieldset>

                    <label class="choice">
                        <input type="checkbox" name="in_stock" value="1" @checked(! empty($filters['in_stock']))>
                        المتاح فقط
                    </label>

                    <div class="form-group">
                        <label for="f-sort">الترتيب</label>
                        <select id="f-sort" name="sort" class="field">
                            @foreach ($sorts as $key => $label)
                                <option value="{{ $key }}" @selected(($filters['sort'] ?? 'latest') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn-kfs-cyan btn-block"><i class="fa-solid fa-filter" aria-hidden="true"></i> عرض النتائج</button>
                    <a href="{{ $category ? route('market.categories.show', $category->slug) : route('market.products.index') }}" class="btn-kfs-outline btn-block" style="margin-top:.5em">مسح التصفية</a>
                </div>
            </form>
        </aside>

        {{-- Results --}}
        <section aria-labelledby="results-title">
            <div class="mkt-toolbar">
                <h2 id="results-title" style="font-size:1.2em;margin:0">المنتجات</h2>
                <p role="status" style="margin:0">عدد النتائج: <strong>{{ $products->total() }}</strong></p>
            </div>

            @if ($products->isEmpty())
                <x-kfs.empty-state icon="search" title="لم نجد منتجات مطابقة" text="جرّب كلمة بحث أخرى أو امسح التصفية." class="mkt-empty-state" data-reveal>
                    <a href="{{ $category ? route('market.categories.show', $category->slug) : route('market.products.index') }}" class="btn-kfs-outline">مسح التصفية</a>
                </x-kfs.empty-state>
            @else
                <ul class="mkt-grid" data-stagger>
                    @foreach ($products as $product)
                        <li>@include('marketplace::partials.product-card', ['product' => $product])</li>
                    @endforeach
                </ul>
                <nav aria-label="صفحات النتائج">{{ $products->links() }}</nav>
            @endif
        </section>
    </div>
@endsection
