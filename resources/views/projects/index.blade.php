@extends('layouts.app')
@section('title', 'مشاريع المحافظة')

@push('css')
    <link rel="stylesheet" href="{{ asset('css') }}/projects.css">
    {{-- Leaflet CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        /* الألوان والهوية */
        :root {
            --prj-dark: #1e272e;
            --prj-gold: #e1b12c;
            --prj-bg: #f7f9fb;
            --tm-gold: #e1b12c;
            --tm-navy: #1e272e;
            --tm-light: #f8fafc;
        }

        /* سكشن الخريطة والسايدبار */
        .projects-interactive-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            /* السايدبار على اليمين والخريطة يسارها */
            direction: rtl;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            height: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .map-sidebar-list {
            background: #fff;
            border-left: 1px solid #eee;
            display: flex;
            flex-direction: column;
        }

        .list-header {
            background: var(--prj-dark);
            color: #fff;
            padding: 20px;
            font-weight: bold;
            text-align: center;
            border-bottom: 4px solid var(--prj-gold);
        }

        .list-body {
            flex-grow: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .side-project-card {
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid #f0f0f0;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
        }

        .side-project-card:hover,
        .side-project-card.active {
            background: #fdfaf2;
            border-color: var(--prj-gold);
            box-shadow: 0 5px 15px rgba(225, 177, 44, 0.15);
        }

        .side-thumb {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }

        /* الخريطة والتحميل */
        .map-view-box {
            position: relative;
            background: #e5e9ec;
        }

        #interactiveProjectsMap {
            height: 100%;
            width: 100%;
        }

        .map-loading-overlay {
            position: absolute;
            inset: 0;
            background: #fff;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .text-gold {
            color: var(--prj-gold) !important;
        }

        /* للموبايل */
        @media (max-width: 991px) {
            .projects-interactive-grid {
                grid-template-columns: 1fr;
                height: 800px;
            }

            .map-sidebar-list {
                height: 300px;
                order: 2;
                /* السايدبار تحت الخريطة في الشاشات الصغيرة */
            }
        }

        .project-archive-card:hover {
            transform: translateY(-5px);
            transition: 0.3s;
        }

        .card-image {
            height: 200px;
            border-bottom: 1px solid #eee;
            overflow: hidden;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-title a {
            text-decoration: none;
            color: #1e272e;
            transition: 0.3s;
        }

        .card-title a:hover {
            color: var(--prj-gold);
        }

        /* =========================================
                                       === سكشن كافة المشاريع والأنشطة ===
                                       ========================================= */

        /* عنوان القسم الرئيسي مع الخط الجانبي */
        /* Grid Cards Style */
        .section-title-premium {
            font-weight: 900;
            color: var(--tm-navy);
            position: relative;
            margin-top: 50px;
            text-align: center
        }

        .section-title-premium::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: var(--tm-gold);
            margin: 15px auto;
            border-radius: 10px;
        }

        /* بطاقة المشروع (Archive Card) */
        .project-archive-card {
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            border: 1px solid #f0f0f0;
        }

        /* تأثير عند المرور بالماوس على الكارت */
        .project-archive-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(30, 39, 46, 0.1);
            border-color: rgba(225, 177, 44, 0.3);
        }

        /* غلاف الصورة */
        .card-image-link {
            position: relative;
            display: block;
            width: 100%;
            height: 220px;
            /* ارتفاع ثابت للصور لتوحيد الشبكة */
            overflow: hidden;
            background-color: #f8f9fa;
        }

        .project-archive-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* لضمان ملء المساحة بدون تشوه */
            transition: transform 0.6s ease;
        }

        .project-archive-card:hover img {
            transform: scale(1.1);
            /* تأثير الزووم الهادئ */
        }

        /* حالة المشاريع التي تستخدم لوجو بدل صورة */
        .card-image.is-logo {
            padding: 30px;
        }

        .card-image.is-logo img {
            object-fit: contain;
        }

        /* بادج التمييز (مشروع مميز) */
        .highlight-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(45deg, #e1b12c, #f1c40f);
            color: #1e272e;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 800;
            z-index: 2;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* محتوى البطاقة */
        .card-content {
            padding: 25px 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-content .card-title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1.5;
        }

        .card-content .card-title a {
            text-decoration: none;
            color: var(--prj-dark);
            transition: color 0.3s;
        }

        .card-archive-link:hover,
        .card-title a:hover {
            color: var(--prj-gold);
        }

        /* البادج الصغير للتصنيف تحت العنوان */
        .category-pill {
            display: inline-block;
            background-color: #f1f3f5;
            color: #636e72;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 15px;
        }

        /* تخصيص زر "التفاصيل" إذا رغبت بإضافته لاحقاً */
        .project-details-btn {
            margin-top: 15px;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--prj-gold);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        /* تحسين شكل الـ Pagination (أرقام الصفحات) */
        .pagination-wrapper {
            margin-top: 50px;
            display: flex;
            justify-content: center;
        }

        .pagination-wrapper .page-link {
            border-radius: 10px;
            margin: 0 5px;
            color: var(--prj-dark);
            font-weight: bold;
            border: 1px solid #eee;
        }

        .pagination-wrapper .page-item.active .page-link {
            background-color: var(--prj-gold);
            border-color: var(--prj-gold);
            color: #fff;
        }

        /* تحسين التجاوب للهواتف */
        @media (max-width: 767px) {
            .section-main-title {
                font-size: 1.4rem;
                text-align: center;
                border-right: none;
                border-bottom: 3px solid var(--prj-gold);
                padding-bottom: 10px;
            }

            .card-image-link {
                height: 180px;
            }
        }
    </style>
@endpush

@section('content')
    <main class="main-content">
        {{-- Page Header --}}
        <x-kfs.page-hero title="خريطة المشروعات القومية" subtitle="استكشف أهم المشاريع التنموية والقومية وتوزيعها الجغرافي بجميع المراكز." icon="fa-solid fa-diagram-project" :image="asset('images/bg/projects.jpg')" />

        <div class="container pb-5">
            {{-- سكشن الخريطة التفاعلية والسايدبار --}}
            <x-kfs.section-title title="المشروعات على الخريطة" subtitle="اختر مشروعاً من القائمة لعرض موقعه." />
            <div class="projects-interactive-grid mb-5">
                <div class="map-sidebar-list">
                    <div class="list-header">
                        <i class="fas fa-list-check me-2"></i>
                        قائمة المشاريع القومية
                    </div>
                    <div class="list-body custom-scrollbar" id="projectSideList">
                        {{-- سيتم التعبئة من خلال الـ JavaScript --}}
                    </div>
                </div>

                <div class="map-view-box">
                    <div id="interactiveProjectsMap"></div>
                    <div class="map-loading-overlay">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">جارٍ تحميل الخريطة</span></div>
                    </div>
                </div>
            </div>

            {{-- الأرشيف الشبكي (الكود القديم تحت الخريطة) --}}
            <x-kfs.section-title title="كافة المشاريع والأنشطة" />
            @if ($projects->isNotEmpty())
                <div class="kfs-grid kfs-grid--3" data-stagger>
                    @foreach ($projects as $project)
                        <a href="{{ route('projects.show', $project->slug) }}" class="kfs-media">
                            <span class="kfs-card__media" @if ($project->type == 'logo') style="background:#fff" @endif>
                                <img src="{{ $project->thumbnail ? Storage::url($project->thumbnail) : asset('images/kfs.png') }}" alt="" loading="lazy"
                                    @if ($project->type == 'logo') style="object-fit:contain;padding:12%" @endif>
                            </span>
                            <span class="kfs-card__body">
                                <span class="kfs-card__meta">
                                    @if ($project->category)<span class="kfs-badge {{ $project->category === 'حياة كريمة' ? 'kfs-badge--olive' : '' }}">{{ $project->category }}</span>@endif
                                    @if ($project->is_highlighted)<span class="kfs-badge kfs-badge--gold"><i class="fa-solid fa-medal" aria-hidden="true"></i> مشروع قومي</span>@endif
                                </span>
                                <span class="kfs-card__title">{{ $project->name }}</span>
                                <span class="kfs-card__more">استعرض الإنجاز <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
                            </span>
                        </a>
                    @endforeach
                </div>
                <div class="pagination-wrapper mt-5">{{ $projects->links() }}</div>
            @else
                <x-kfs.empty-state icon="box" title="لا توجد مشروعات منشورة حالياً" />
            @endif
        </div>
    </main>

    {{-- تحضير بيانات الجافا سكريبت --}}
    @php
        $mapData = $projects
            ->filter(fn($p) => $p->latitude && $p->longitude)
            ->map(
                fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'category' => $p->category,
                    'lat' => $p->latitude,
                    'lng' => $p->longitude,
                    'slug' => $p->slug,
                    'thumb' => Storage::url($p->thumbnail),
                ],
            )
            ->values();
    @endphp

@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const projectsData = @json($mapData);
            const projectUrl = @json(route('projects.show', '__SLUG__'));
            const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const mapElement = document.getElementById('interactiveProjectsMap');
            const sideList = document.getElementById('projectSideList');

            if (mapElement && projectsData.length > 0) {
                // إنشاء الخريطة باستخدام طبقة شوارع احترافية باللغة العربية (Esri World Street)
                const map = L.map(mapElement, {
                        scrollWheelZoom: false
                    })
                    .setView([31.1143, 30.9416], 10);

                L.tileLayer(
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
                        attribution: '&copy; Esri World Street Map'
                    }).addTo(map);

                const markerGroup = L.featureGroup();

                projectsData.forEach((project) => {
                    // 1. بناء عنصر السايدبار
                    const sideItem = document.createElement('div');
                    sideItem.className = 'side-project-card';
                    sideItem.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <img src="${esc(project.thumb)}" class="side-thumb" alt="">
                    <div class="info">
                        <h6 class="mb-0 text-dark">${esc(project.name)}</h6>
                        <small class="text-muted">${esc(project.category)}</small>
                    </div>
                </div>
            `;
                    sideList.appendChild(sideItem);

                    // 2. إنشاء الماركر
                    const marker = L.marker([project.lat, project.lng])
                        .addTo(markerGroup)
                        .bindPopup(`
                    <div class="map-popup-premium">
                        <img src="${esc(project.thumb)}" alt="" style="width:100%; border-radius:8px; margin-bottom:8px">
                        <strong>${esc(project.name)}</strong><br>
                        <a href="${esc(projectUrl.replace('__SLUG__', encodeURIComponent(project.slug)))}" class="btn btn-sm btn-dark w-100 mt-2 text-white">عرض التفاصيل</a>
                    </div>
                `);

                    // 3. إضافة الحدث عند الضغط في السايدبار
                    sideItem.addEventListener('click', () => {
                        document.querySelectorAll('.side-project-card').forEach(i => i.classList
                            .remove('active'));
                        sideItem.classList.add('active');
                        map.flyTo([project.lat, project.lng], 15);
                        marker.openPopup();
                    });
                });

                markerGroup.addTo(map);
                map.fitBounds(markerGroup.getBounds().pad(0.1));

                // إخفاء الـ loading
                document.querySelector('.map-loading-overlay').style.display = 'none';
            }
        });
    </script>
@endpush
