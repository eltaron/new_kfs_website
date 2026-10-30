@extends('layouts.app')
@section('title', 'الدليل المتكامل لمراكز ومدن محافظة كفر الشيخ')

@push('css')
<style>
:root {
    --gold: #DAA520;
    --gold-dark: #b8941f;
    --gold-light: #f5e6b0;
    --primary: #1a3a5c;
    --bg: #f8f9fa;
    --card-shadow: 0 2px 20px rgba(0,0,0,0.08);
}
.page-guide { padding: 0 0 40px; background: var(--bg); min-height: 80vh; }
.guide-container { max-width: 960px; margin: 0 auto; }
.progress-wrapper {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 30px; background: #fff; border-bottom: 1px solid #e8ecf1;
    border-radius: 14px 14px 0 0;
}
.progress-bar-track { flex: 1; height: 8px; background: #e8ecf1; border-radius: 4px; overflow: hidden; }
.progress-bar-fill { height: 100%; background: linear-gradient(90deg, var(--gold), #f0c040); border-radius: 4px; transition: width .4s ease; }
.progress-label, .progress-pages { font-size: 13px; font-weight: 600; color: #666; white-space: nowrap; }
.form-body { background: #fff; border-radius: 0 0 14px 14px; box-shadow: var(--card-shadow); overflow: hidden; }
.form-page { display: none; padding: 30px; }
.form-page.active { display: block; }
.intro-card { margin-bottom: 24px; }
.intro-card h2 { color: var(--primary); font-weight: 700; font-size: 20px; margin-bottom: 12px; }
.intro-card .description { color: #555; font-size: 14px; line-height: 1.8; }
.intro-card .description ul { margin-top: 10px; padding-right: 20px; }
.intro-card .description ul li { margin-bottom: 4px; }
.required-note { color: #dc3545; font-size: 13px; margin-top: 12px; }
.section-block { margin-bottom: 28px; }
.section-header {
    font-size: 16px; font-weight: 700; color: var(--primary);
    padding: 12px 16px; background: #f0f4f8; border-radius: 8px;
    border-right: 4px solid var(--gold); display: flex; align-items: center; gap: 8px;
    margin-bottom: 16px;
}
.question-card {
    border: 1px solid #e8ecf1; border-radius: 10px; padding: 16px 20px;
    margin-bottom: 12px; transition: border-color .2s;
}
.question-card:focus-within { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(218,165,32,0.1); }
.field-label {
    display: block; font-size: 14px; font-weight: 600; color: #1a1a2e;
    margin-bottom: 6px;
}
.field-label .req { color: #dc3545; }
.question-card input[type="text"],
.question-card input[type="tel"],
.question-card input[type="number"],
.question-card input[type="date"],
.question-card select,
.question-card textarea {
    width: 100%; padding: 10px 14px; border: 1px solid #d0d5dd;
    border-radius: 8px; font-size: 14px; font-family: inherit;
    transition: border-color .2s; background: #fff;
}
.question-card input:focus,
.question-card select:focus,
.question-card textarea:focus {
    outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(218,165,32,0.12);
}
.question-card textarea { min-height: 80px; resize: vertical; }
.select-wrapper { position: relative; }
.select-wrapper::after {
    content: '\25BC'; position: absolute; left: 14px; top: 50%;
    transform: translateY(-50%); font-size: 10px; color: #888; pointer-events: none;
}
.select-wrapper select { appearance: none; padding-left: 32px; }
.radio-group { display: flex; flex-wrap: wrap; gap: 10px 18px; padding-top: 4px; }
.radio-option { display: flex; align-items: center; gap: 4px; font-size: 14px; cursor: pointer; }
.radio-option input[type="radio"] { accent-color: var(--gold); width: 18px; height: 18px; }
.file-upload-area {
    border: 2px dashed #d0d5dd; border-radius: 12px; padding: 30px;
    text-align: center; cursor: pointer; transition: all .2s; background: #fafbfc;
}
.file-upload-area:hover { border-color: var(--gold); background: #fefcf5; }
.file-upload-area input[type="file"] { display: none; }
.upload-icon { font-size: 36px; margin-bottom: 8px; }
.file-upload-area p { margin: 4px 0; font-size: 13px; color: #666; }
.file-name { color: var(--gold-dark) !important; font-weight: 600; margin-top: 8px !important; }
.nav-buttons {
    display: flex; justify-content: space-between; gap: 12px;
    padding: 20px 30px 30px; background: #fff;
    border-top: 1px solid #e8ecf1; margin: 0 -30px -30px;
}
.btn { padding: 10px 32px; border-radius: 8px; font-size: 15px; font-weight: 700; border: none; cursor: pointer; font-family: inherit; transition: all .2s; display: inline-flex; align-items: center; gap: 6px; }
.btn-next { background: var(--gold); color: #fff; }
.btn-next:hover { background: var(--gold-dark); }
.btn-prev { background: #e8ecf1; color: var(--primary); }
.btn-prev:hover { background: #d0d8e0; }
.btn-submit { background: #28a745; color: #fff; }
.btn-submit:hover { background: #218838; }
#success-screen { display: none; text-align: center; padding: 60px 30px; }
.checkmark { font-size: 64px; margin-bottom: 16px; }
#success-screen h2 { color: var(--primary); font-weight: 700; margin-bottom: 8px; }
#success-screen p { color: #666; font-size: 15px; }
@media (max-width: 768px) {
    .page-guide { padding: 0 0 16px; }
    .form-page { padding: 16px; }
    .progress-wrapper { padding: 12px 16px; }
    .nav-buttons { padding: 16px; margin: 0 -16px -16px; }
    .question-card { padding: 12px 14px; }
    .file-upload-area { padding: 20px; }
}
</style>
@endpush

@section('content')
<x-kfs.page-hero size="compact" title="الدليل المتكامل لمراكز ومدن بمحافظة كفر الشيخ" kicker="قاعدة البيانات المركزية 2026"
    subtitle="استمارة جمع بيانات الدليل المتكامل لمراكز ومدن المحافظة." icon="fa-solid fa-book-atlas" :image="asset('images/bg/directory.jpg')" />
<div class="page-guide">
<div class="guide-container">


<div class="progress-wrapper">
  <span class="progress-label">التقدم</span>
  <div class="progress-bar-track"><div class="progress-bar-fill" id="progressFill" style="width:3%"></div></div>
  <span class="progress-pages" id="progressText">صفحة 1 من 19</span>
</div>

<div class="form-body">
<form id="mainForm" enctype="multipart/form-data" novalidate>
@csrf
@include('center-guides._pages_1_10')
@include('center-guides._pages_11_19')
</form>

<div id="success-screen">
  <div class="checkmark">✅</div>
  <h2>تم إرسال البيانات بنجاح!</h2>
  <p>شكراً لمساهمتك في قاعدة البيانات المركزية لمحافظة كفر الشيخ 2026</p>
  <p style="margin-top:10px;font-size:0.9rem;">سيتم مراجعة البيانات والتواصل معك قريباً</p>
</div>
</div>
</div>
</div>

<script>
const TOTAL_PAGES = 19;
let currentPage = 1;

function showPage(n) {
    document.querySelectorAll('.form-page').forEach(p => p.classList.remove('active'));
    const el = document.getElementById('page-' + n);
    if (el) el.classList.add('active');
    currentPage = n;
    updateProgress();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateProgress() {
    const pct = Math.round((currentPage / TOTAL_PAGES) * 100);
    const fill = document.getElementById('progressFill');
    const text = document.getElementById('progressText');
    if (fill) fill.style.width = pct + '%';
    if (text) text.textContent = 'صفحة ' + currentPage + ' من ' + TOTAL_PAGES;
}

function goNext() { if (currentPage < TOTAL_PAGES) showPage(currentPage + 1); }
function goPrev() { if (currentPage > 1) showPage(currentPage - 1); }

function submitForm() {
    const requiredFields = ['q_city', 'q_main_activity', 'q_head', 'q_contact', 'q_phone', 'q_water_service_name'];
    for (let id of requiredFields) {
        const el = document.getElementById(id);
        if (!el || !el.value.trim()) {
            alert('الرجاء ملء جميع الحقول المطلوبة (*)');
            const firstMissing = document.getElementById(id);
            if (firstMissing) firstMissing.focus();
            return;
        }
    }

    const form = document.getElementById('mainForm');
    const formData = new FormData(form);
    const btn = document.querySelector('.btn-submit');
    const origText = btn.textContent;
    btn.textContent = 'جاري الإرسال...';
    btn.disabled = true;

    fetch('{{ route('center-guides.store') }}', {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            form.style.display = 'none';
            document.getElementById('success-screen').style.display = 'block';
            const fill = document.getElementById('progressFill');
            const text = document.getElementById('progressText');
            if (fill) fill.style.width = '100%';
            if (text) text.textContent = 'مكتمل ✓';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            alert('حدث خطأ في الإرسال. الرجاء المحاولة مجدداً.');
            btn.textContent = origText;
            btn.disabled = false;
        }
    })
    .catch(() => {
        alert('حدث خطأ في الاتصال. تأكد من اتصالك بالإنترنت.');
        btn.textContent = origText;
        btn.disabled = false;
    });
}

// File upload labels
['f_gis','f_proj_photos','f_tourism_photos','f_stats','f_docs'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('change', function() {
            const label = document.getElementById(id + '_name');
            if (label && this.files.length > 0) {
                label.textContent = Array.from(this.files).map(f => f.name).join('، ');
            }
        });
    }
});
</script>
@endsection
