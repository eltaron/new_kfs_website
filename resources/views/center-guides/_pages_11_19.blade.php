{{-- PAGE 11 — Economic Activities --}}
<div class="form-page" id="page-11">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🌾</span> القسم الحادي عشر: الأنشطة الاقتصادية الرئيسية</div>
    <div class="question-card">
      <label class="field-label" for="q_agri_activity">النشاط الزراعي (أهم المحاصيل)</label>
      <textarea id="q_agri_activity" name="agricultural_activity" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_industrial_activity">النشاط الصناعي (أهم الصناعات)</label>
      <textarea id="q_industrial_activity" name="industrial_activity" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_trade_activity">النشاط التجاري (التجارة الرئيسية)</label>
      <textarea id="q_trade_activity" name="trade_activity" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_fish_farms_count">النشاط السمكي (أعداد المزارع)</label>
      <input type="number" id="q_fish_farms_count" name="fish_farms_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_fish_farms_area">النشاط السمكي (مساحات المزارع)</label>
      <input type="text" id="q_fish_farms_area" name="fish_farms_area" placeholder="بالفدان أو الهكتار">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_fishing_ports">موانئ الصيد</label>
      <input type="text" id="q_fishing_ports" name="fishing_ports" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_fish_market">بورصة أسماك - شوادر</label>
      <input type="text" id="q_fish_market" name="fish_market" placeholder="اكتب هنا...">
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 12 — Vital Projects --}}
<div class="form-page" id="page-12">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🏗️</span> القسم الثاني عشر: المشروعات الحيوية</div>
    <div class="question-card">
      <label class="field-label" for="q_project_name">اسم المشروع</label>
      <input type="text" id="q_project_name" name="vital_project_name" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_project_sector">القطاع</label>
      <input type="text" id="q_project_sector" name="vital_project_sector" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_project_cost">التكلفة</label>
      <input type="text" id="q_project_cost" name="vital_project_cost" placeholder="بالجنيه المصري">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_project_progress">نسبة التنفيذ</label>
      <input type="text" id="q_project_progress" name="vital_project_progress" placeholder="مثال: 75%">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_project_executor">الجهة المنفذة</label>
      <input type="text" id="q_project_executor" name="vital_project_executor" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_project_end_date">تاريخ الانتهاء</label>
      <input type="date" id="q_project_end_date" name="vital_project_end_date">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_project_impact">الأثر المتوقع</label>
      <textarea id="q_project_impact" name="vital_project_impact" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 13 — Tourism --}}
<div class="form-page" id="page-13">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🏖️</span> القسم الثالث عشر: السياحة والترفيه</div>
    <div class="question-card">
      <label class="field-label" for="q_tourism_assets">المقومات السياحية</label>
      <textarea id="q_tourism_assets" name="tourism_assets" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_beaches">الشواطئ</label>
      <input type="text" id="q_beaches" name="beaches" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_lakes">البحيرات</label>
      <input type="text" id="q_lakes" name="lakes" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_heritage_sites">المواقع الأثرية</label>
      <textarea id="q_heritage_sites" name="heritage_sites" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_nature_reserves">المحميات الطبيعية</label>
      <input type="text" id="q_nature_reserves" name="nature_reserves" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_entertainment_venues">الأماكن الترفيهية</label>
      <textarea id="q_entertainment_venues" name="entertainment_venues" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_parks">الحدائق</label>
      <input type="text" id="q_parks" name="parks" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_promenades">المتنزهات</label>
      <input type="text" id="q_promenades" name="promenades" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_cultural_centers">المراكز الثقافية</label>
      <input type="text" id="q_cultural_centers" name="cultural_centers" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_annual_events">الفعاليات السنوية والمهرجانات والمؤتمرات</label>
      <textarea id="q_annual_events" name="annual_events" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 14 — Parks --}}
<div class="form-page" id="page-14">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🌳</span> القسم الرابع عشر: الحدائق والتجميل</div>
    <div class="question-card">
      <label class="field-label" for="q_parks_count">عدد الحدائق</label>
      <input type="number" id="q_parks_count" name="parks_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_parks_area">المساحة الإجمالية</label>
      <input type="text" id="q_parks_area" name="parks_total_area" placeholder="بالمتر المربع">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_parks_maintenance">حالة الصيانة</label>
      <input type="text" id="q_parks_maintenance" name="parks_maintenance_status" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_parks_current_density">متوسط الكثافة الحالية</label>
      <input type="text" id="q_parks_current_density" name="parks_current_density" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_parks_expected_density">متوسط الكثافة المتوقعة</label>
      <input type="text" id="q_parks_expected_density" name="parks_expected_density" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_parks_challenges">التحديات</label>
      <textarea id="q_parks_challenges" name="parks_challenges" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_parks_needs">الاحتياجات</label>
      <textarea id="q_parks_needs" name="parks_needs" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 15 — Government / Financial / Security --}}
<div class="form-page" id="page-15">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🏢</span> الخدمات الحكومية</div>
    <div class="question-card">
      <label class="field-label" for="q_service_complexes">مجمعات الخدمات</label>
      <input type="text" id="q_service_complexes" name="service_complexes" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_tech_centers">مراكز التكنولوجيا</label>
      <input type="text" id="q_tech_centers" name="technology_centers" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_post_offices">مكاتب البريد</label>
      <input type="text" id="q_post_offices" name="post_offices" placeholder="اكتب هنا...">
    </div>
  </div>
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🏦</span> الخدمات المالية</div>
    <div class="question-card">
      <label class="field-label" for="q_banks">البنوك</label>
      <textarea id="q_banks" name="banks" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_atms">ماكينات ATM</label>
      <input type="number" id="q_atms" name="atms_count" placeholder="أدخل العدد" min="0">
    </div>
  </div>
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🚔</span> الخدمات الأمنية</div>
    <div class="question-card">
      <label class="field-label" for="q_police_centers">مراكز الشرطة</label>
      <input type="number" id="q_police_centers" name="police_centers" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_police_depts">أقسام الشرطة</label>
      <input type="number" id="q_police_depts" name="police_departments" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_rescue_points">نقاط الإسعاف</label>
      <input type="number" id="q_rescue_points" name="rescue_points" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_civil_protection">الحماية المدنية</label>
      <input type="text" id="q_civil_protection" name="civil_protection" placeholder="اكتب هنا...">
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 16 — Environment --}}
<div class="form-page" id="page-16">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">♻️</span> القسم السادس عشر: البيئة والاستدامة</div>
    <div class="question-card">
      <label class="field-label" for="q_cleanliness_system">منظومة النظافة</label>
      <textarea id="q_cleanliness_system" name="cleanliness_system" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_daily_waste">كمية المخلفات اليومية</label>
      <input type="text" id="q_daily_waste" name="daily_waste_amount" placeholder="بالطن">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_recycling_sites">مواقع التدوير</label>
      <textarea id="q_recycling_sites" name="recycling_sites" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_env_problems">المشكلات البيئية</label>
      <textarea id="q_env_problems" name="environmental_problems" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 17 — SWOT --}}
<div class="form-page" id="page-17">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">📈</span> القسم السابع عشر: تحليل SWOT للمركز أو المدينة</div>
    <div class="question-card">
      <label class="field-label" for="q_strengths">نقاط القوة بالمركز أو المدينة</label>
      <textarea id="q_strengths" name="swot_strengths" placeholder="اكتب هنا..." style="min-height:100px;"></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_weaknesses">نقاط الضعف بالمركز أو المدينة</label>
      <textarea id="q_weaknesses" name="swot_weaknesses" placeholder="اكتب هنا..." style="min-height:100px;"></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_opportunities">الفرص بالمركز أو المدينة</label>
      <textarea id="q_opportunities" name="swot_opportunities" placeholder="اكتب هنا..." style="min-height:100px;"></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_threats">التهديدات بالمركز أو المدينة</label>
      <textarea id="q_threats" name="swot_threats" placeholder="اكتب هنا..." style="min-height:100px;"></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_swot_priorities">الاحتياجات التنموية والأولويات</label>
      <textarea id="q_swot_priorities" name="swot_development_priorities" placeholder="اكتب هنا..." style="min-height:100px;"></textarea>
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 18 — Development Priorities --}}
<div class="form-page" id="page-18">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🎯</span> القسم الثامن عشر: الاحتياجات التنموية والأولويات</div>
    <div class="question-card">
      <label class="field-label" for="q_dev_priorities">الاحتياجات التنموية والأولويات</label>
      <textarea id="q_dev_priorities" name="development_priorities" placeholder="اكتب هنا..." style="min-height:140px;"></textarea>
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 19 — Attachments --}}
<div class="form-page" id="page-19">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">📎</span> القسم التاسع عشر: المرفقات</div>

    <div class="question-card">
      <label class="field-label">خرائط GIS</label>
      <div class="file-upload-area" onclick="document.getElementById('f_gis').click()">
        <div class="upload-icon">🗺️</div>
        <p>انقر لرفع الملف — PDF, Document, Drawing, Image, Spreadsheet, Presentation</p>
        <p>الحد الأقصى: 10 ملفات - 10 MB لكل ملف</p>
        <input type="file" id="f_gis" name="gis_maps[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif">
        <p class="file-name" id="f_gis_name"></p>
      </div>
    </div>

    <div class="question-card">
      <label class="field-label">صور المشروعات</label>
      <div class="file-upload-area" onclick="document.getElementById('f_proj_photos').click()">
        <div class="upload-icon">📸</div>
        <p>انقر لرفع الملف</p>
        <p>الحد الأقصى: 10 ملفات - 10 MB لكل ملف</p>
        <input type="file" id="f_proj_photos" name="project_photos[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
        <p class="file-name" id="f_proj_photos_name"></p>
      </div>
    </div>

    <div class="question-card">
      <label class="field-label">صور المعالم السياحية</label>
      <div class="file-upload-area" onclick="document.getElementById('f_tourism_photos').click()">
        <div class="upload-icon">🖼️</div>
        <p>انقر لرفع الملف</p>
        <p>الحد الأقصى: 10 ملفات - 10 MB لكل ملف</p>
        <input type="file" id="f_tourism_photos" name="tourism_photos[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
        <p class="file-name" id="f_tourism_photos_name"></p>
      </div>
    </div>

    <div class="question-card">
      <label class="field-label">تقارير إحصائية</label>
      <div class="file-upload-area" onclick="document.getElementById('f_stats').click()">
        <div class="upload-icon">📊</div>
        <p>انقر لرفع الملف</p>
        <p>الحد الأقصى: 10 ملفات - 10 MB لكل ملف</p>
        <input type="file" id="f_stats" name="statistical_reports[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
        <p class="file-name" id="f_stats_name"></p>
      </div>
    </div>

    <div class="question-card">
      <label class="field-label">ملفات Word-Excel</label>
      <div class="file-upload-area" onclick="document.getElementById('f_docs').click()">
        <div class="upload-icon">📄</div>
        <p>انقر لرفع الملف</p>
        <p>الحد الأقصى: 10 ملفات - 10 MB لكل ملف</p>
        <input type="file" id="f_docs" name="word_excel_files[]" multiple accept=".doc,.docx,.xls,.xlsx">
        <p class="file-name" id="f_docs_name"></p>
      </div>
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-submit" onclick="submitForm()">إرسال ✓</button>
  </div>
</div>
