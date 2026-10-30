<!-- مودال 1: شروط الدفع الإلكتروني -->
<div class="modal fade" style="    z-index: 9999999999;" id="paymentTermsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-navy p-4">
                <h5 class="modal-title fw-bold text-light">سياسات الدفع الإلكتروني والتحصيل الرقمي</h5>
                <button type="button" style="    margin: -.5rem auto -.5rem -.5rem;" class="btn-close btn-close-white"
                    data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-end" style="line-height: 1.8;">
                <h6 class="fw-bold text-navy border-bottom pb-2 mb-3">شروط وأحكام السداد:</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check text-success ms-2"></i> يتم السداد حصراً عبر الوسائل
                        الإلكترونية المتاحة (فيزا - ميزة - فوري).</li>
                    <li class="mb-2"><i class="fas fa-check text-success ms-2"></i> الرسوم المدفوعة مقابل أعمال الفحص
                        والمعاينة غير قابلة للاسترداد.</li>
                    <li class="mb-2"><i class="fas fa-check text-success ms-2"></i> يجب الاحتفاظ ببيانات الإيصال
                        الإلكتروني كمرجع أساسي لإثبات المعاملة.</li>
                    <li class="mb-2"><i class="fas fa-check text-success ms-2"></i> أي محاولة تلاعب في بيانات السداد
                        تعرض صاحبها للمساءلة القانونية وإلغاء الطلب.</li>
                </ul>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-navy px-4 rounded-pill" data-bs-dismiss="modal">فهمت، العودة
                    للتقديم</button>
            </div>
        </div>
    </div>
</div>

<!-- مودال 2: شروط أداء الخدمة (ديناميكي) -->
<div class="modal fade" style="    z-index: 9999999999;" id="serviceExecutionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-gold text-navy p-4">
                <h5 class="modal-title fw-black">الضوابط الفنية لأداء خدمة: {{ $service->name }}</h5>
                <button type="button" style="    margin: -.5rem auto -.5rem -.5rem;" class="btn-close"
                    data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-5 text-end rich-text-content custom-scrollbar">
                {{-- سحب الشروط المحددة من حقل terms_conditions في قاعدة البيانات --}}
                @if ($service->terms_conditions)
                    {!! clean($service->terms_conditions, "cms") !!}
                @else
                    <p class="text-muted">لا توجد شروط فنية خاصة مسجلة لهذه الخدمة حالياً.</p>
                @endif
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-dark px-5 rounded-pill" data-bs-dismiss="modal">موافق،
                    البدء بالدفع</button>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-navy {
        background-color: #1e272e !important;
    }

    .bg-gold {
        background-color: #e1b12c !important;
    }

    .text-navy {
        color: #1e272e !important;
    }

    .fw-black {
        font-weight: 900;
    }

    /* تحسين السكرول داخل المودال */
    .custom-scrollbar::-webkit-scrollbar {
        width: 5px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e1b12c;
        border-radius: 10px;
    }
</style>
