{{-- PAGE 1 — Contact --}}
<div class="form-page active" id="page-1">
  <div class="intro-card">
    <h2>الدليل المتكامل لمراكز ومدن بمحافظة كفر الشيخ (قاعدة البيانات المركزية) 2026</h2>
    <div class="description">
      <p>يهدف هذا الدليل إلى جمع بيانات شاملة ومحدثة عن مراكز ومدن محافظة كفر الشيخ لدعم التخطيط والتنمية المحلية.</p>
      <ul>
        <li>بيانات المراكز والمدن: الجهوية، البشرية، الخدمية</li>
        <li>الفرص الاستثمارية وأبرز المشروعات التنموية</li>
        <li>المقومات الاقتصادية والبيئية والسياحية</li>
        <li>أهم أولويات ومتطلبات التنمية لكل مركز أو مدينة</li>
      </ul>
    </div>
    <p class="required-note">* يشير إلى أن السؤال مطلوب</p>
  </div>

  <div class="section-block">
    <div class="section-header"><span class="section-icon">📍</span> بيانات التواصل</div>
    <div class="question-card">
      <label class="field-label" for="q_city">المركز - المدينة <span class="req">*</span></label>
      <div class="select-wrapper">
        <select id="q_city" name="city" required>
          <option value="">-- اختر --</option>
          <option>كفر الشيخ</option><option>دسوق</option><option>الرياض</option>
          <option>قلين</option><option>بلطيم</option><option>الحامول</option>
          <option>مصيف بلطيم</option><option>سيدي سالم</option><option>سيدي غازي</option>
          <option>مطوبس</option><option>فوه</option><option>بيلا</option>
          <option>مسير</option><option>برج البرلس</option><option>الديوان العام</option>
          <option>أخرى</option>
        </select>
      </div>
    </div>
    <div class="question-card">
      <label class="field-label">النشاط الرئيسي للمركز/المدينة <span class="req">*</span></label>
      <div class="radio-group" id="q_main_activity">
        <label class="radio-option"><input type="radio" name="main_activity" value="زراعة" required> زراعة</label>
        <label class="radio-option"><input type="radio" name="main_activity" value="تجارة"> تجارة</label>
        <label class="radio-option"><input type="radio" name="main_activity" value="صناعة"> صناعة</label>
        <label class="radio-option"><input type="radio" name="main_activity" value="صيد"> صيد</label>
        <label class="radio-option"><input type="radio" name="main_activity" value="سياحة"> سياحة</label>
        <label class="radio-option"><input type="radio" name="main_activity" value="استزراع سمكي"> استزراع سمكي</label>
        <label class="radio-option"><input type="radio" name="main_activity" value="تعدين"> تعدين</label>
        <label class="radio-option"><input type="radio" name="main_activity" value="أخرى"> أخرى</label>
      </div>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_other_activities">أي أنشطة أخرى</label>
      <textarea id="q_other_activities" name="other_activities" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_head">رئيس المدينة/ وكيل الوزارة / مدير الإدارة <span class="req">*</span></label>
      <input type="text" id="q_head" name="head" placeholder="الاسم الكامل" required>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_contact">مسؤول التواصل <span class="req">*</span></label>
      <input type="text" id="q_contact" name="contact_person" placeholder="الاسم الكامل" required>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_phone">رقم التليفون <span class="req">*</span></label>
      <input type="tel" id="q_phone" name="phone" placeholder="01xxxxxxxxx" required>
    </div>
  </div>
  <div class="nav-buttons">
    <span></span>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 2 — Investment --}}
<div class="form-page" id="page-2">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">💼</span> القسم الأول: الاستثمار</div>
    <div class="question-card">
      <label class="field-label" for="q_projects">عدد المشروعات القائمة</label>
      <input type="number" id="q_projects" name="projects_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_investment_size">حجم الاستثمارات</label>
      <input type="text" id="q_investment_size" name="investment_size" placeholder="بالجنيه المصري">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_permanent_workers">عدد العاملين الدائمين</label>
      <input type="number" id="q_permanent_workers" name="permanent_workers" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_seasonal_workers">عدد العاملين الموسميين</label>
      <input type="number" id="q_seasonal_workers" name="seasonal_workers" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label">جهة الولاية</label>
      <div class="radio-group">
        <label class="radio-option"><input type="radio" name="quality_level" value="المحافظة"> المحافظة</label>
        <label class="radio-option"><input type="radio" name="quality_level" value="الزراعة"> الزراعة</label>
        <label class="radio-option"><input type="radio" name="quality_level" value="الأوقاف"> الأوقاف</label>
        <label class="radio-option"><input type="radio" name="quality_level" value="الري"> الري</label>
        <label class="radio-option"><input type="radio" name="quality_level" value="الإصلاح الزراعي">الإصلاح الزراعي</label>
        <label class="radio-option"><input type="radio" name="quality_level" value="أخرى"> أخرى</label>
      </div>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_opp_name">اسم الفرصة</label>
      <input type="text" id="q_opp_name" name="opportunity_name" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_utility_status">الموقف من المرافق</label>
      <div class="select-wrapper">
        <select id="q_utility_status" name="utility_status"><option value="">-- اختر --</option><option>مرفق</option><option>غير مرفق</option></select>
      </div>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_utility_type">نوع المرافق</label>
      <div class="select-wrapper">
        <select id="q_utility_type" name="utility_type"><option value="">-- اختر --</option><option>كهرباء</option><option>مياه</option><option>طرق</option><option>غاز طبيعي</option><option>صرف صحي</option></select>
      </div>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_utility_pct">نسبة المرافق</label>
      <div class="select-wrapper">
        <select id="q_utility_pct" name="utility_percentage"><option value="">-- اختر --</option><option>أقل من 25%</option><option>25% - 50%</option><option>50% - 75%</option><option>أكثر من 75%</option><option>100%</option></select>
      </div>
    </div>
    <div class="question-card">
      <label class="field-label">نوع النشاط المقترح</label>
      <div class="radio-group">
        <label class="radio-option"><input type="radio" name="activity_type" value="خدمي"> خدمي</label>
        <label class="radio-option"><input type="radio" name="activity_type" value="سياحي"> سياحي</label>
        <label class="radio-option"><input type="radio" name="activity_type" value="صناعي"> صناعي</label>
        <label class="radio-option"><input type="radio" name="activity_type" value="زراعي"> زراعي</label>
        <label class="radio-option"><input type="radio" name="activity_type" value="أخرى"> أخرى</label>
      </div>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_opp_location">مكان الفرصة</label>
      <input type="text" id="q_opp_location" name="opportunity_location" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_opp_desc">وصف الفرصة</label>
      <textarea id="q_opp_desc" name="opportunity_desc" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_coord_x">إحداثي X</label>
      <input type="text" id="q_coord_x" name="coord_x" placeholder="مثال: 31.12345">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_coord_y">إحداثي Y</label>
      <input type="text" id="q_coord_y" name="coord_y" placeholder="مثال: 30.12345">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_occupant">شاغل المكان</label>
      <input type="text" id="q_occupant" name="occupant" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_opp_status">الفرصة قائمة / مقترحة</label>
      <input type="text" id="q_opp_status" name="opportunity_status" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_assumed_workers">عدد العمالة المقترحة</label>
      <input type="number" id="q_assumed_workers" name="assumed_workers" placeholder="أدخل العدد" min="0">
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 3 — Population --}}
<div class="form-page" id="page-3">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">👥</span> القسم الثاني: البيانات السكانية</div>
    <div class="question-card">
      <label class="field-label" for="q_total_pop">عدد السكان الإجمالي</label>
      <input type="number" id="q_total_pop" name="total_population" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_males">عدد الذكور</label>
      <input type="number" id="q_males" name="males_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_females">عدد الإناث</label>
      <input type="number" id="q_females" name="females_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_growth_rate">معدل النمو السكاني</label>
      <input type="text" id="q_growth_rate" name="growth_rate" placeholder="مثال: 2.5%">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_youth">عدد الشباب (18-35)</label>
      <input type="number" id="q_youth" name="youth_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_elderly">عدد كبار السن</label>
      <input type="number" id="q_elderly" name="elderly_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_children">عدد الأطفال</label>
      <input type="number" id="q_children" name="children_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_illiteracy">نسبة الأمية</label>
      <input type="text" id="q_illiteracy" name="illiteracy_rate" placeholder="مثال: 15%">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_university">نسبة التعليم الجامعي</label>
      <input type="text" id="q_university" name="university_rate" placeholder="مثال: 20%">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_disabled">عدد ذوي الهمم</label>
      <input type="number" id="q_disabled" name="disabled_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_internal_migration">هل توجد هجرة داخلية؟</label>
      <div class="select-wrapper">
        <select id="q_internal_migration" name="internal_migration"><option value="">-- اختر --</option><option>نعم</option><option>لا</option></select>
      </div>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_external_migration">هل توجد هجرة خارجية؟</label>
      <div class="select-wrapper">
        <select id="q_external_migration" name="external_migration"><option value="">-- اختر --</option><option>نعم</option><option>لا</option></select>
      </div>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_migration_reasons">أهم أسباب الهجرة</label>
      <textarea id="q_migration_reasons" name="migration_reasons" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 4 — Water, Sewage, Electricity, Gas, Lighting --}}
<div class="form-page" id="page-4">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">💧</span> مياه الشرب</div>
    <div class="question-card">
      <label class="field-label" for="q_water_stations">عدد محطات المياه</label>
      <input type="number" id="q_water_stations" name="water_stations" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_water_capacity">الطاقة الإنتاجية</label>
      <input type="text" id="q_water_capacity" name="water_capacity" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_water_coverage">نسبة التغطية</label>
      <input type="text" id="q_water_coverage" name="water_coverage" placeholder="مثال: 85%">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_water_problems">أهم المشكلات</label>
      <textarea id="q_water_problems" name="water_problems" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_water_service_name">اسم الخدمة <span class="req">*</span></label>
      <input type="text" id="q_water_service_name" name="water_service_name" placeholder="اكتب هنا..." required>
    </div>
  </div>
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🏗️</span> الصرف الصحي</div>
    <div class="question-card">
      <label class="field-label" for="q_sewage_coverage">نسبة التغطية</label>
      <input type="text" id="q_sewage_coverage" name="sewage_coverage" placeholder="مثال: 70%">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_sewage_plants">عدد محطات المعالجة</label>
      <input type="number" id="q_sewage_plants" name="sewage_plants" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_sewage_deprived">القرى المحرومة</label>
      <textarea id="q_sewage_deprived" name="sewage_deprived" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_sewage_needs">الاحتياجات المستقبلية</label>
      <textarea id="q_sewage_needs" name="sewage_future_needs" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="section-block">
    <div class="section-header"><span class="section-icon">⚡</span> الكهرباء</div>
    <div class="question-card">
      <label class="field-label" for="q_elec_transformers">عدد المحولات</label>
      <input type="number" id="q_elec_transformers" name="electricity_transformers" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_elec_coverage">نسبة التغطية</label>
      <input type="text" id="q_elec_coverage" name="electricity_coverage" placeholder="مثال: 95%">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_elec_outages">متوسط الانقطاعات</label>
      <input type="text" id="q_elec_outages" name="electricity_outages" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_elec_needs">الاحتياجات المطلوبة</label>
      <textarea id="q_elec_needs" name="electricity_needs" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🔥</span> الغاز الطبيعي</div>
    <div class="question-card">
      <label class="field-label" for="q_gas_coverage">نسبة التغطية</label>
      <input type="text" id="q_gas_coverage" name="gas_coverage" placeholder="مثال: 60%">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_gas_served">عدد المناطق المخدومة</label>
      <input type="number" id="q_gas_served" name="gas_served_areas" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_gas_target">المناطق المستهدفة</label>
      <textarea id="q_gas_target" name="gas_target_areas" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="section-block">
    <div class="section-header"><span class="section-icon">💡</span> القسم الخامس: الإنارة العامة</div>
    <div class="question-card">
      <label class="field-label" for="q_light_poles">عدد أعمدة الإنارة</label>
      <input type="number" id="q_light_poles" name="light_poles" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_light_lamps">عدد الكشافات</label>
      <input type="number" id="q_light_lamps" name="light_lamps" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_light_coverage">نسبة التغطية</label>
      <input type="text" id="q_light_coverage" name="light_coverage" placeholder="مثال: 80%">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_light_unserved">المناطق غير المخدومة</label>
      <textarea id="q_light_unserved" name="light_unserved" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_light_needs">الاحتياجات المطلوبة</label>
      <textarea id="q_light_needs" name="light_needs" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_light_other">أخرى</label>
      <textarea id="q_light_other" name="light_other" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 5 — Roads & Transport --}}
<div class="form-page" id="page-5">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🛣️</span> القسم الرابع: الطرق والنقل — شبكة الطرق</div>
    <div class="question-card">
      <label class="field-label" for="q_roads_total">إجمالي أطوال الطرق (كم)</label>
      <input type="number" id="q_roads_total" name="roads_total_km" placeholder="أدخل الطول" min="0" step="0.1">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_roads_regional">طرق إقليمية</label>
      <input type="text" id="q_roads_regional" name="roads_regional" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_roads_main">طرق رئيسية</label>
      <input type="text" id="q_roads_main" name="roads_main" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_roads_internal">طرق داخلية</label>
      <input type="text" id="q_roads_internal" name="roads_internal" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_roads_upgrade">طرق تحتاج إلى رفع كفاءة</label>
      <textarea id="q_roads_upgrade" name="roads_need_upgrade" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🚌</span> النقل</div>
    <div class="question-card">
      <label class="field-label" for="q_parking">مواقف السيارات الرئيسية</label>
      <textarea id="q_parking" name="main_parking" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_public_transport">خطوط النقل الجماعي</label>
      <textarea id="q_public_transport" name="public_transport_lines" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_traffic_problems">المشكلات المرورية</label>
      <textarea id="q_traffic_problems" name="traffic_problems" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 6 — Education --}}
<div class="form-page" id="page-6">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🏫</span> القسم السادس: التعليم — المدارس</div>
    <div class="question-card">
      <label class="field-label" for="q_primary_schools">عدد المدارس الابتدائية</label>
      <input type="number" id="q_primary_schools" name="primary_schools" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_prep_schools">عدد المدارس الإعدادية</label>
      <input type="number" id="q_prep_schools" name="prep_schools" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_secondary_schools">عدد المدارس الثانوية</label>
      <input type="number" id="q_secondary_schools" name="secondary_schools" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_tech_schools">عدد المدارس الفنية</label>
      <input type="number" id="q_tech_schools" name="technical_schools" placeholder="أدخل العدد" min="0">
    </div>
  </div>
  <div class="section-block">
    <div class="section-header"><span class="section-icon">📊</span> الطاقة الاستيعابية</div>
    <div class="question-card">
      <label class="field-label" for="q_total_students">إجمالي عدد الطلاب</label>
      <input type="number" id="q_total_students" name="total_students" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_male_students">عدد الطلاب من البنين</label>
      <input type="number" id="q_male_students" name="male_students" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_female_students">عدد الطلاب من البنات</label>
      <input type="number" id="q_female_students" name="female_students" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_classrooms">عدد الفصول</label>
      <input type="number" id="q_classrooms" name="classrooms_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_class_density">متوسط كثافة الفصل</label>
      <input type="number" id="q_class_density" name="class_density" placeholder="عدد الطلاب بالفصل" min="0">
    </div>
  </div>
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🎓</span> التعليم الجامعي</div>
    <div class="question-card">
      <label class="field-label" for="q_gov_uni">إجمالي عدد الجامعات الحكومية</label>
      <input type="number" id="q_gov_uni" name="govt_universities" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_ahli_uni">إجمالي عدد الجامعات الأهلية</label>
      <input type="number" id="q_ahli_uni" name="ahli_universities" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_private_uni">إجمالي عدد الجامعات الخاصة</label>
      <input type="number" id="q_private_uni" name="private_universities" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_institutes">المعاهد</label>
      <input type="text" id="q_institutes" name="institutes" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_colleges">الكليات</label>
      <input type="text" id="q_colleges" name="colleges" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_uni_density">متوسط كثافة الطلاب</label>
      <input type="number" id="q_uni_density" name="university_density" placeholder="أدخل العدد" min="0">
    </div>
  </div>
  <div class="section-block">
    <div class="section-header"><span class="section-icon">⚠️</span> التحديات في مجال التعليم</div>
    <div class="question-card">
      <label class="field-label" for="q_edu_problems">أهم المشكلات</label>
      <textarea id="q_edu_problems" name="education_problems" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_edu_needs">أهم الاحتياجات</label>
      <textarea id="q_edu_needs" name="education_needs" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 7 — Health --}}
<div class="form-page" id="page-7">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🏥</span> القسم السابع: الصحة</div>
    <div class="question-card">
      <label class="field-label" for="q_hospitals">عدد المستشفيات</label>
      <input type="number" id="q_hospitals" name="hospitals_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_health_units">عدد الوحدات الصحية</label>
      <input type="number" id="q_health_units" name="health_units" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_family_centers">عدد مراكز طب الأسرة</label>
      <input type="number" id="q_family_centers" name="family_medicine_centers" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_ambulances">عدد سيارات الإسعاف</label>
      <input type="number" id="q_ambulances" name="ambulances_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_doctors">القوى البشرية (عدد الأطباء)</label>
      <input type="number" id="q_doctors" name="doctors_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_doctor_ratio">نسبة الأطباء على المواطنين</label>
      <input type="text" id="q_doctor_ratio" name="doctor_citizen_ratio" placeholder="مثال: 1 لكل 500">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_nurses">عدد التمريض</label>
      <input type="number" id="q_nurses" name="nurses_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_nurse_ratio">نسبة التمريض على المواطنين</label>
      <input type="text" id="q_nurse_ratio" name="nurse_citizen_ratio" placeholder="مثال: 1 لكل 200">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_specialties_available">التخصصات المتوفرة</label>
      <textarea id="q_specialties_available" name="specialties_available" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_specialties_lacking">التخصصات غير كافية</label>
      <textarea id="q_specialties_lacking" name="specialties_lacking" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_health_challenges">أهم التحديات في القطاع الطبي</label>
      <textarea id="q_health_challenges" name="health_challenges" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_health_needs">أهم الاحتياجات في القطاع الطبي</label>
      <textarea id="q_health_needs" name="health_needs" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 8 — Youth & Sports --}}
<div class="form-page" id="page-8">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">⚽</span> القسم الثامن: الشباب والرياضة</div>
    <div class="question-card">
      <label class="field-label" for="q_youth_centers">عدد مراكز الشباب</label>
      <input type="number" id="q_youth_centers" name="youth_centers" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_sports_clubs">عدد الأندية الرياضية</label>
      <input type="number" id="q_sports_clubs" name="sports_clubs" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_stadiums">الملاعب القانونية</label>
      <input type="text" id="q_stadiums" name="legal_stadiums" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_youth_needs">الاحتياجات المطلوبة</label>
      <textarea id="q_youth_needs" name="youth_needs" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 9 — State Property --}}
<div class="form-page" id="page-9">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🏛️</span> القسم التاسع: أملاك الدولة</div>
    <div class="question-card">
      <label class="field-label" for="q_land_plots">عدد قطع الأراضي</label>
      <input type="number" id="q_land_plots" name="land_plots_count" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_land_total_area">المساحة الإجمالية</label>
      <input type="text" id="q_land_total_area" name="land_total_area" placeholder="بالمتر المربع أو الفدان">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_land_legalization">حالة التقنين</label>
      <input type="text" id="q_land_legalization" name="land_legalization_status" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_buildings">المباني</label>
      <input type="text" id="q_buildings" name="state_buildings" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_warehouses">المخازن</label>
      <input type="text" id="q_warehouses" name="state_warehouses" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_markets">الأسواق</label>
      <input type="text" id="q_markets" name="state_markets" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_garages">الجراجات</label>
      <input type="text" id="q_garages" name="state_garages" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_support_land">الأراضي المسانِدة (المساحة)</label>
      <input type="text" id="q_support_land" name="support_land_area" placeholder="اكتب هنا...">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_current_use">الاستخدام الحالي</label>
      <textarea id="q_current_use" name="current_land_use" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_state_challenges">أهم التحديات في أملاك الدولة</label>
      <textarea id="q_state_challenges" name="state_property_challenges" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_state_needs">أهم الاحتياجات في أملاك الدولة</label>
      <textarea id="q_state_needs" name="state_property_needs" placeholder="اكتب هنا..."></textarea>
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>

{{-- PAGE 10 — Public Shops --}}
<div class="form-page" id="page-10">
  <div class="section-block">
    <div class="section-header"><span class="section-icon">🏪</span> القسم العاشر: المحال العامة</div>
    <div class="question-card">
      <label class="field-label" for="q_licensed_shops">عدد المحال المرخصة</label>
      <input type="number" id="q_licensed_shops" name="licensed_shops" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_unlicensed_shops">عدد المحال غير المرخصة</label>
      <input type="number" id="q_unlicensed_shops" name="unlicensed_shops" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_popular_activities">الأنشطة الأكثر انتشاراً</label>
      <textarea id="q_popular_activities" name="popular_activities" placeholder="اكتب هنا..."></textarea>
    </div>
    <div class="question-card">
      <label class="field-label" for="q_commercial_licensed">عدد المحال التجارية المرخصة</label>
      <input type="number" id="q_commercial_licensed" name="commercial_shops_licensed" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_commercial_unlicensed">عدد المحال التجارية الغير مرخصة</label>
      <input type="number" id="q_commercial_unlicensed" name="commercial_shops_unlicensed" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_workshops_licensed">عدد الورش الصناعية المرخصة</label>
      <input type="number" id="q_workshops_licensed" name="industrial_workshops_licensed" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_workshops_unlicensed">عدد الورش الصناعية الغير مرخصة</label>
      <input type="number" id="q_workshops_unlicensed" name="industrial_workshops_unlicensed" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_service_licensed">عدد المحال الخدمية المرخصة</label>
      <input type="number" id="q_service_licensed" name="service_shops_licensed" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_service_unlicensed">عدد المحال الخدمية الغير مرخصة</label>
      <input type="number" id="q_service_unlicensed" name="service_shops_unlicensed" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_entertainment_licensed">عدد المحال الترفيهية المرخصة</label>
      <input type="number" id="q_entertainment_licensed" name="entertainment_shops_licensed" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_entertainment_unlicensed">عدد المحال الترفيهية الغير مرخصة</label>
      <input type="number" id="q_entertainment_unlicensed" name="entertainment_shops_unlicensed" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_tourism_licensed">عدد المحال السياحية المرخصة</label>
      <input type="number" id="q_tourism_licensed" name="tourism_shops_licensed" placeholder="أدخل العدد" min="0">
    </div>
    <div class="question-card">
      <label class="field-label" for="q_tourism_unlicensed">عدد المحال السياحية الغير مرخصة</label>
      <input type="number" id="q_tourism_unlicensed" name="tourism_shops_unlicensed" placeholder="أدخل العدد" min="0">
    </div>
  </div>
  <div class="nav-buttons">
    <button type="button" class="btn btn-prev" onclick="goPrev()">→ رجوع</button>
    <button type="button" class="btn btn-next" onclick="goNext()">التالي ←</button>
  </div>
</div>
