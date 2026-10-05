(() => {
  'use strict';

  const S = window.SHCDCustomer || {};
  const api = S.restUrl || '';
  const $ = (selector) => document.querySelector(selector);
  const $$ = (selector) => Array.from(document.querySelectorAll(selector));

  const I18N = {
    fa: {
      hero_kicker:'انتقال هوشمند؛ انتخاب داده با شما',
      hero_subtitle:'فقط آنچه نیاز دارید منتقل کنید: سفارش‌ها، مشتریان یا هر دو. Kerishna مبدا را دست‌نخورده نگه می‌دارد، از ساخت حساب‌های تکراری جلوگیری می‌کند و مسیر انتقال را ساده و قابل‌پیگیری نگه می‌دارد.',
      connection_title:'همه‌چیز برای یک انتقال مطمئن آماده است؟', connection_subtitle:'وضعیت ذخیره‌سازی سفارش‌ها و اتصال سایت را قبل از شروع بررسی کنید.', refresh:'بررسی دوباره',
      destination_credentials:'اتصال به سایت مقصد', destination_intro:'برای انتقال مستقیم، مقصد را یک‌بار متصل کنید و دفعات بعد با چند کلیک بکاپ را بفرستید.', destination_url:'آدرس سایت مقصد', destination_api_key:'API Key مقصد', destination_api_secret:'API Secret مقصد', save_settings:'ذخیره اتصال',
      destination_help:'Kerishna را روی سایت مقصد هم نصب کنید، کلیدهای اتصال را در مقصد بسازید و اینجا وارد کنید. خالی گذاشتن Key یا Secret، مقدار ذخیره‌شده قبلی را تغییر نمی‌دهد.',
      local_credentials:'کلیدهای اتصال این سایت', local_credentials_intro:'برای دریافت انتقال مستقیم روی این سایت از این کلیدها استفاده کنید.', local_credentials_help:'API Secret را محرمانه نگه دارید و فقط در سایت مبدا وارد کنید.', copy:'کپی', generate_credentials:'ساخت کلیدهای جدید',
      workspace_kicker:'فضای انتقال داده', backup_title:'فقط همان داده‌ای را جابه‌جا کنید که نیاز دارید', backup_intro:'سفارش‌ها و مشتریان مستقل از هم خروجی می‌گیرند. برای ورود هم کافی است فایل را رها کنید؛ Kerishna نوع فایل و داده را خودش تشخیص می‌دهد.', ready:'● آماده برای شروع', batch_size:'تعداد رکورد در هر مرحله', direct_customers:'در انتقال مستقیم، حساب مشتریان هم منتقل شود',
      export_title:'خروجی بگیرید، انتخاب با شماست', export_intro:'فرمت را انتخاب کنید، سپس فقط سفارش‌ها یا فقط مشتریان را دانلود کنید.', export_format:'فرمت خروجی', export_orders:'خروجی سفارش‌ها', export_customers:'خروجی مشتریان',
      smart_import_title:'ورود هوشمند با Drag & Drop', smart_import_intro:'JSON یا CSV را اینجا رها کنید. Kerishna تشخیص می‌دهد فایل مربوط به سفارش‌ها، مشتریان یا بکاپ ترکیبی نسخه‌های قبلی است.', dropzone_title:'فایل را اینجا رها کنید', dropzone_hint:'یا برای انتخاب فایل کلیک کنید — JSON / CSV',
      direct_title:'انتقال مستقیم بین دو سایت', direct_intro:'بدون دانلود و آپلود دستی فایل، بکاپ سفارش‌ها را مستقیم به مقصد متصل‌شده بفرستید.', direct_backup:'ارسال مستقیم به مقصد',
      nothing_done:'هنوز عملیاتی شروع نشده است.', preparing:'در حال آماده‌سازی...', processed:'{done} از {total} رکورد پردازش شد ({percent}٪)', source_readonly_notice:'مبدا در فرایند خروجی و انتقال مستقیم فقط خوانده می‌شود؛ سفارش‌ها، مشتریان، محصولات و شناسه‌های آن حذف یا بازنویسی نمی‌شوند.',
      developed_by:'توسعه داده‌شده توسط SHABNAM.DEV', developer_note:'مستندات، پشتیبانی و نسخه‌های جدید در shabnam.dev در دسترس است.',
      storage_method:'شیوه ذخیره سفارش‌ها', main_table:'جدول فعال سفارش‌ها', charset:'Charset', saved:'اتصال ذخیره شد و برای انتقال بعدی آماده است.', generic_error:'عملیات کامل نشد؛ جزئیات در گزارش پایین صفحه ثبت شده است.', http_error:'خطای HTTP {status}', credentials_generated:'کلیدهای اتصال جدید آماده شد.',
      created:'ایجاد', updated:'به‌روزرسانی', skipped:'رد شده', failed:'خطا', warnings:'هشدار', source_order:'سفارش مبدا', warning_source_order:'هشدار سفارش مبدا', unknown_error:'خطای نامشخص', warning:'هشدار', customers:'مشتریان', reused:'حساب موجود', source_customer:'مشتری مبدا',
      orders_export_start:'در حال آماده‌سازی خروجی سفارش‌ها...', orders_export_ready:'خروجی {count} سفارش آماده شد.', customers_export_start:'در حال آماده‌سازی خروجی مشتریان...', customers_export_ready:'خروجی {count} مشتری آماده شد.', export_ready:'فایل خروجی آماده دانلود است.',
      destination_required:'ابتدا آدرس مقصد را وارد و ذخیره کنید.', transfer_start:'اتصال برقرار شد؛ انتقال مستقیم شروع شد...', batch_prefix:'بسته {page}: ', transfer_done:'انتقال پایان یافت؛ {total} سفارش بررسی شد و {failed} مورد خطا داشت.', transfer_partial:'انتقال انجام شد اما {failed} سفارش خطا داشت؛ گزارش را بررسی کنید.', transfer_success:'انتقال با موفقیت انجام شد و مبدا بدون تغییر باقی ماند.',
      import_file_detected:'{name} — {format} / {type}', type_orders:'سفارش‌ها', type_customers:'مشتریان', type_combined:'سفارش‌ها + مشتریان', type_unknown:'نوع ناشناخته',
      smart_import_start:'فایل شناسایی شد؛ واردسازی {type} شروع می‌شود...', csv_import_done:'ورود CSV پایان یافت.', json_reading:'در حال بررسی فایل JSON...', json_parse_error:'JSON قابل خواندن نیست: {message}', wrong_plugin:'این فایل متعلق به Kerishna Shop Migrator نیست.', invalid_source:'شناسه سایت مبدا معتبر نیست.', invalid_payload:'نوع داده فایل قابل تشخیص نیست.',
      json_valid:'فایل آماده است: {orders} سفارش و {customers} مشتری شناسایی شد.', final_prefix:'نتیجه نهایی: ', import_partial:'ورود پایان یافت؛ سفارش خطادار: {orders}، مشتری خطادار: {customers}.', import_success:'داده‌ها با موفقیت در سایت مقصد ثبت شدند.',
      key_missing:'کلید هنوز ساخته نشده است.', copied:'کلید کپی شد.', copy_failed:'کپی انجام نشد؛ دسترسی Clipboard را بررسی کنید.', language_changed:'زبان پنل به فارسی تغییر کرد.',
      errors:{shcd_destination_url:'آدرس مقصد معتبر نیست.',shcd_file:'فایل معتبر ارسال نشده است.',shcd_file_size:'حجم فایل بیشتر از حد مجاز است.',shcd_json:'ساختار JSON نامعتبر است.',shcd_plugin:'فرمت فایل متعلق به Kerishna Shop Migrator نیست.',shcd_orders:'لیست سفارش‌ها معتبر نیست.',shcd_source:'شناسه سایت مبدا معتبر نیست.',shcd_batch:'تعداد رکوردهای بسته بیشتر از حد مجاز است.',shcd_destination:'اطلاعات مقصد کامل نیست.',shcd_source_protected:'برای حفاظت از مبدا، واردسازی روی خود سایت مبدا مجاز نیست.',shcd_payload_size:'حجم بسته زیاد است؛ تعداد هر بسته را کمتر کنید.',shcd_csv_format:'نوع فایل CSV قابل تشخیص نیست.'}
    },
    en: {
      hero_kicker:'Smart migration. You choose the data.',
      hero_subtitle:'Move only what you need—orders, customers, or both. Kerishna keeps the source untouched, prevents avoidable customer duplicates, and makes each migration easier to follow.',
      connection_title:'Ready for a confident migration?', connection_subtitle:'Check order storage and connection readiness before you begin.', refresh:'Check again',
      destination_credentials:'Connect the destination site', destination_intro:'Connect the destination once, then send future backups directly with a few clicks.', destination_url:'Destination site URL', destination_api_key:'Destination API Key', destination_api_secret:'Destination API Secret', save_settings:'Save connection',
      destination_help:'Install Kerishna on the destination, generate its connection keys, and paste them here. Leaving Key or Secret empty keeps the saved value unchanged.',
      local_credentials:'This site connection keys', local_credentials_intro:'Use these keys when this site is receiving a direct migration.', local_credentials_help:'Keep the API Secret private and enter it only on the source site.', copy:'Copy', generate_credentials:'Generate new keys',
      workspace_kicker:'Data migration workspace', backup_title:'Move only the data you actually need', backup_intro:'Orders and customers export independently. For imports, drop in a file and Kerishna detects both its format and data type automatically.', ready:'● Ready to start', batch_size:'Records per step', direct_customers:'Include customer accounts in direct transfers',
      export_title:'Export on your terms', export_intro:'Choose a format, then download orders or customers independently.', export_format:'Export format', export_orders:'Export orders', export_customers:'Export customers',
      smart_import_title:'Smart Drag & Drop import', smart_import_intro:'Drop a JSON or CSV file here. Kerishna detects orders, customers, or a legacy combined backup automatically.', dropzone_title:'Drop your file here', dropzone_hint:'or click to choose — JSON / CSV',
      direct_title:'Direct site-to-site transfer', direct_intro:'Send the order backup straight to the connected destination without manually downloading and uploading files.', direct_backup:'Send directly to destination',
      nothing_done:'No operation has started yet.', preparing:'Preparing...', processed:'{done} of {total} records processed ({percent}%)', source_readonly_notice:'The source is read-only during export and direct transfer; its orders, customers, products, and IDs are not deleted or overwritten.',
      developed_by:'Developed by SHABNAM.DEV', developer_note:'Documentation, support, and new releases are available at shabnam.dev.',
      storage_method:'Order storage method', main_table:'Active order table', charset:'Charset', saved:'Connection saved and ready for the next transfer.', generic_error:'The operation did not finish. Check the activity log below.', http_error:'HTTP error {status}', credentials_generated:'New connection keys are ready.',
      created:'Created', updated:'Updated', skipped:'Skipped', failed:'Failed', warnings:'Warnings', source_order:'Source order', warning_source_order:'Source order warning', unknown_error:'Unknown error', warning:'Warning', customers:'Customers', reused:'Existing account', source_customer:'Source customer',
      orders_export_start:'Preparing the orders export...', orders_export_ready:'Export with {count} orders is ready.', customers_export_start:'Preparing the customers export...', customers_export_ready:'Export with {count} customers is ready.', export_ready:'Your export file is ready to download.',
      destination_required:'Enter and save the destination URL first.', transfer_start:'Connected. Direct transfer has started...', batch_prefix:'Batch {page}: ', transfer_done:'Transfer finished; {total} orders checked. Errors: {failed}', transfer_partial:'Transfer finished with {failed} failed orders. Check the log.', transfer_success:'Transfer completed successfully and the source remained unchanged.',
      import_file_detected:'{name} — {format} / {type}', type_orders:'Orders', type_customers:'Customers', type_combined:'Orders + customers', type_unknown:'Unknown type',
      smart_import_start:'File detected. Importing {type}...', csv_import_done:'CSV import completed.', json_reading:'Checking the JSON file...', json_parse_error:'Unable to parse JSON: {message}', wrong_plugin:'This file does not belong to Kerishna Shop Migrator.', invalid_source:'The source site identifier is invalid.', invalid_payload:'The file data type could not be detected.',
      json_valid:'File is ready: {orders} orders and {customers} customers detected.', final_prefix:'Final result: ', import_partial:'Import finished; failed orders: {orders}, failed customers: {customers}.', import_success:'Data was imported successfully on the destination site.',
      key_missing:'Credentials have not been generated yet.', copied:'Copied to clipboard.', copy_failed:'Could not copy. Check Clipboard permissions.', language_changed:'Panel language changed to English.',
      errors:{shcd_destination_url:'The destination URL is invalid.',shcd_file:'A valid file was not provided.',shcd_file_size:'The file is larger than the allowed limit.',shcd_json:'The JSON structure is invalid.',shcd_plugin:'This file does not belong to Kerishna Shop Migrator.',shcd_orders:'The orders list is invalid.',shcd_source:'The source site identifier is invalid.',shcd_batch:'The batch contains too many records.',shcd_destination:'Destination credentials are incomplete.',shcd_source_protected:'Importing a backup into its own source site is blocked.',shcd_payload_size:'The batch is too large; reduce the batch size.',shcd_csv_format:'The CSV data type could not be detected.'}
    },
    ar: {
      hero_kicker:'نقل ذكي، والاختيار لك',
      hero_subtitle:'انقل ما تحتاجه فقط: الطلبات أو العملاء أو كليهما. يحافظ Kerishna على المصدر دون تغيير، ويحد من تكرار حسابات العملاء، ويجعل خطوات النقل أسهل في المتابعة.',
      connection_title:'هل كل شيء جاهز لعملية نقل موثوقة؟', connection_subtitle:'تحقق من تخزين الطلبات وحالة الاتصال قبل البدء.', refresh:'إعادة الفحص',
      destination_credentials:'الاتصال بالموقع الوجهة', destination_intro:'اربط الموقع الوجهة مرة واحدة ثم أرسل النسخ التالية مباشرة ببضع نقرات.', destination_url:'رابط الموقع الوجهة', destination_api_key:'API Key للوجهة', destination_api_secret:'API Secret للوجهة', save_settings:'حفظ الاتصال',
      destination_help:'ثبّت Kerishna في الموقع الوجهة، أنشئ مفاتيح الاتصال هناك ثم أدخلها هنا. ترك Key أو Secret فارغاً يحافظ على القيمة السابقة.',
      local_credentials:'مفاتيح اتصال هذا الموقع', local_credentials_intro:'استخدم هذه المفاتيح عندما يكون هذا الموقع هو مستلم النقل المباشر.', local_credentials_help:'احتفظ بـ API Secret سرياً وأدخله فقط في الموقع المصدر.', copy:'نسخ', generate_credentials:'إنشاء مفاتيح جديدة',
      workspace_kicker:'مساحة نقل البيانات', backup_title:'انقل فقط البيانات التي تحتاجها', backup_intro:'يمكن تصدير الطلبات والعملاء كلٌ على حدة. وللاستيراد يكفي إسقاط الملف ليكتشف Kerishna الصيغة ونوع البيانات تلقائياً.', ready:'● جاهز للبدء', batch_size:'عدد السجلات في كل خطوة', direct_customers:'تضمين حسابات العملاء في النقل المباشر',
      export_title:'صدّر بالطريقة التي تناسبك', export_intro:'اختر الصيغة ثم نزّل الطلبات أو العملاء بشكل مستقل.', export_format:'صيغة التصدير', export_orders:'تصدير الطلبات', export_customers:'تصدير العملاء',
      smart_import_title:'استيراد ذكي بالسحب والإفلات', smart_import_intro:'أسقط ملف JSON أو CSV هنا. يكتشف Kerishna تلقائياً إن كان للطلبات أو العملاء أو نسخة قديمة مشتركة.', dropzone_title:'أسقط الملف هنا', dropzone_hint:'أو انقر لاختيار ملف — JSON / CSV',
      direct_title:'نقل مباشر بين موقعين', direct_intro:'أرسل نسخة الطلبات مباشرة إلى الموقع الوجهة المتصل دون تنزيل ورفع يدوي.', direct_backup:'إرسال مباشر إلى الوجهة',
      nothing_done:'لم تبدأ أي عملية بعد.', preparing:'جارٍ التحضير...', processed:'تمت معالجة {done} من {total} سجل ({percent}٪)', source_readonly_notice:'المصدر للقراءة فقط أثناء التصدير والنقل المباشر؛ لا يتم حذف أو استبدال الطلبات أو العملاء أو المنتجات أو المعرّفات.',
      developed_by:'تم التطوير بواسطة SHABNAM.DEV', developer_note:'التوثيق والدعم والإصدارات الجديدة متاحة عبر shabnam.dev.',
      storage_method:'طريقة تخزين الطلبات', main_table:'جدول الطلبات النشط', charset:'Charset', saved:'تم حفظ الاتصال وهو جاهز للنقل التالي.', generic_error:'لم تكتمل العملية؛ راجع سجل النشاط أدناه.', http_error:'خطأ HTTP {status}', credentials_generated:'تم إنشاء مفاتيح اتصال جديدة.',
      created:'تم الإنشاء', updated:'تم التحديث', skipped:'تم التجاوز', failed:'فشل', warnings:'تحذيرات', source_order:'طلب المصدر', warning_source_order:'تحذير طلب المصدر', unknown_error:'خطأ غير معروف', warning:'تحذير', customers:'العملاء', reused:'حساب موجود', source_customer:'عميل المصدر',
      orders_export_start:'جارٍ تجهيز تصدير الطلبات...', orders_export_ready:'تم تجهيز تصدير {count} طلب.', customers_export_start:'جارٍ تجهيز تصدير العملاء...', customers_export_ready:'تم تجهيز تصدير {count} عميل.', export_ready:'ملف التصدير جاهز للتنزيل.',
      destination_required:'أدخل رابط الوجهة واحفظه أولاً.', transfer_start:'تم الاتصال وبدأ النقل المباشر...', batch_prefix:'الدفعة {page}: ', transfer_done:'انتهى النقل؛ تمت مراجعة {total} طلب. الأخطاء: {failed}', transfer_partial:'اكتمل النقل مع فشل {failed} طلب. راجع السجل.', transfer_success:'اكتمل النقل بنجاح وبقي المصدر دون تغيير.',
      import_file_detected:'{name} — {format} / {type}', type_orders:'الطلبات', type_customers:'العملاء', type_combined:'الطلبات + العملاء', type_unknown:'نوع غير معروف',
      smart_import_start:'تم اكتشاف الملف؛ جارٍ استيراد {type}...', csv_import_done:'اكتمل استيراد CSV.', json_reading:'جارٍ فحص ملف JSON...', json_parse_error:'تعذر قراءة JSON: {message}', wrong_plugin:'هذا الملف لا يخص Kerishna Shop Migrator.', invalid_source:'معرّف الموقع المصدر غير صالح.', invalid_payload:'تعذر تحديد نوع بيانات الملف.',
      json_valid:'الملف جاهز: تم اكتشاف {orders} طلب و{customers} عميل.', final_prefix:'النتيجة النهائية: ', import_partial:'انتهى الاستيراد؛ الطلبات الفاشلة: {orders}، العملاء الفاشلون: {customers}.', import_success:'تم استيراد البيانات بنجاح في الموقع الوجهة.',
      key_missing:'لم يتم إنشاء المفاتيح بعد.', copied:'تم النسخ إلى الحافظة.', copy_failed:'تعذر النسخ؛ تحقق من صلاحية الحافظة.', language_changed:'تم تغيير لغة اللوحة إلى العربية.',
      errors:{shcd_destination_url:'رابط الوجهة غير صالح.',shcd_file:'لم يتم إرسال ملف صالح.',shcd_file_size:'حجم الملف أكبر من الحد المسموح.',shcd_json:'بنية JSON غير صالحة.',shcd_plugin:'هذا الملف لا يخص Kerishna Shop Migrator.',shcd_orders:'قائمة الطلبات غير صالحة.',shcd_source:'معرّف الموقع المصدر غير صالح.',shcd_batch:'عدد السجلات في الدفعة أكبر من الحد المسموح.',shcd_destination:'بيانات الوجهة غير مكتملة.',shcd_source_protected:'تم منع الاستيراد إلى الموقع المصدر نفسه.',shcd_payload_size:'حجم الدفعة كبير؛ قلّل عدد السجلات.',shcd_csv_format:'تعذر تحديد نوع بيانات CSV.'}
    }
  };

  const state = {
    total:0, done:0, busy:false, lastStatus:null, progressMode:'nothing',
    credentials:{api_key:'',api_secret:''},
    lang:['fa','en','ar'].includes(localStorage.getItem('shcd-kerishna-lang')) ? localStorage.getItem('shcd-kerishna-lang') : (S.defaultLang || 'fa')
  };

  const localeFor = (lang = state.lang) => lang === 'fa' ? 'fa-IR' : (lang === 'ar' ? 'ar-SA' : 'en-US');
  const fmt = (value) => Number(value || 0).toLocaleString(localeFor());
  function t(key, vars = {}) {
    const parts = String(key).split('.');
    let value = I18N[state.lang] || I18N.fa;
    for (const part of parts) value = value && Object.prototype.hasOwnProperty.call(value, part) ? value[part] : undefined;
    if (typeof value !== 'string') {
      value = I18N.en;
      for (const part of parts) value = value && Object.prototype.hasOwnProperty.call(value, part) ? value[part] : undefined;
    }
    if (typeof value !== 'string') return key;
    return value.replace(/\{([a-z0-9_]+)\}/gi, (_, name) => Object.prototype.hasOwnProperty.call(vars, name) ? String(vars[name]) : `{${name}}`);
  }

  function applyLanguage(lang, announce = false) {
    if (!['fa','en','ar'].includes(lang)) lang = 'fa';
    state.lang = lang;
    localStorage.setItem('shcd-kerishna-lang', lang);
    const app = $('#shcd-customer-app');
    if (app) { app.setAttribute('lang', lang); app.setAttribute('dir', (lang === 'fa' || lang === 'ar') ? 'rtl' : 'ltr'); }
    $$('[data-i18n]').forEach((el) => { el.textContent = t(el.dataset.i18n); });
    $$('.shcd-lang-btn').forEach((btn) => {
      const active = btn.dataset.lang === lang;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    const menuName = document.querySelector('#toplevel_page_shcd-kerishna .wp-menu-name');
    if (menuName) menuName.textContent = 'Kerishna';
    if (state.lastStatus) renderStatus(state.lastStatus);
    renderProgressText();
    refreshI18nLogs();
    if (announce) toast(t('language_changed'));
  }

  function toast(message, error = false) {
    const el = $('#shcd-toast'); if (!el) return;
    el.textContent = message;
    el.className = 'shcd-toast show' + (error ? ' error' : '');
    clearTimeout(toast.timer);
    toast.timer = setTimeout(() => { el.className = 'shcd-toast'; }, 3400);
  }
  function appendLogRow(text, meta = null) {
    const el = $('#shcd-log'); if (!el) return;
    const row = document.createElement('div'); row.className = 'shcd-log-row'; row.dataset.time = String(Date.now());
    if (meta) { row.dataset.i18nLog = meta.key; row.dataset.i18nVars = JSON.stringify(meta.vars || {}); }
    row.textContent = `[${new Date(Number(row.dataset.time)).toLocaleTimeString(localeFor())}] ${text}`;
    el.prepend(row);
  }
  const log = (message) => appendLogRow(message);
  const logI18n = (key, vars = {}) => appendLogRow(t(key, vars), {key, vars});
  function refreshI18nLogs() {
    $$('.shcd-log-row[data-i18n-log]').forEach((row) => {
      let vars = {}; try { vars = JSON.parse(row.dataset.i18nVars || '{}'); } catch (_) {}
      row.textContent = `[${new Date(Number(row.dataset.time || Date.now())).toLocaleTimeString(localeFor())}] ${t(row.dataset.i18nLog, vars)}`;
    });
  }
  function localizedApiMessage(data, status) {
    const code = String(data?.code || '');
    if (code && t(`errors.${code}`) !== `errors.${code}`) return t(`errors.${code}`);
    if (state.lang === 'fa' && data?.message) return String(data.message);
    return t('http_error', {status});
  }
  async function request(path, options = {}) {
    const opts = Object.assign({}, options);
    opts.headers = Object.assign({'X-WP-Nonce':S.nonce,'Accept':'application/json','X-SHCD-Language':state.lang}, opts.headers || {});
    const response = await fetch(api + path, opts);
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(localizedApiMessage(data, response.status));
    return data;
  }
  function escapeHtml(value) { const d = document.createElement('div'); d.textContent = String(value ?? ''); return d.innerHTML; }

  function renderStatus(data) {
    state.lastStatus = data;
    const st = data.storage || {};
    const target = $('#shcd-status');
    if (target) target.innerHTML = `
      <div class="shcd-status-item"><strong>${escapeHtml(st.mode || '—')}</strong><small>${escapeHtml(t('storage_method'))}</small></div>
      <div class="shcd-status-item"><strong>${escapeHtml(st.orders_table || '—')}</strong><small>${escapeHtml(t('main_table'))}</small></div>
      <div class="shcd-status-item"><strong>${escapeHtml(st.database_charset || 'UTF-8')}</strong><small>${escapeHtml(t('charset'))}</small></div>`;
    const s = data.settings || {};
    if ($('#destination_url')) $('#destination_url').value = s.destination_url || '';
    if ($('#batch_size')) $('#batch_size').value = s.batch_size || 500;
    if ($('#create_customers')) $('#create_customers').checked = !!s.create_customers;
    state.credentials.api_key = s.api_key || ''; state.credentials.api_secret = s.api_secret || '';
    if (state.credentials.api_key && $('#local_api_key')) $('#local_api_key').textContent = state.credentials.api_key;
    if (state.credentials.api_secret && $('#local_api_secret')) $('#local_api_secret').textContent = state.credentials.api_secret;
  }
  async function refresh() { renderStatus(await request('/status')); }
  async function save() {
    const body = {
      destination_url:$('#destination_url').value.trim(), destination_api_key:$('#destination_api_key').value.trim(), destination_api_secret:$('#destination_api_secret').value.trim(),
      batch_size:Number($('#batch_size').value || 500), create_customers:$('#create_customers').checked ? 1 : 0, create_missing_products:1
    };
    await request('/settings', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
    $('#destination_api_key').value=''; $('#destination_api_secret').value=''; toast(t('saved'));
  }
  async function generate() {
    const data = await request('/credentials/generate', {method:'POST'});
    $('#local_api_key').textContent=data.api_key; $('#local_api_secret').textContent=data.api_secret;
    state.credentials.api_key=data.api_key; state.credentials.api_secret=data.api_secret; toast(t('credentials_generated'));
  }

  function resetProgress() { state.total=0; state.done=0; state.progressMode='preparing'; $('#shcd-progress-bar').style.width='0%'; renderProgressText(); }
  function updateProgress(total, done) { state.total=Number(total||0); state.done=Number(done||0); state.progressMode='processed'; const percent=state.total?Math.min(100,Math.round(state.done/state.total*100)):0; $('#shcd-progress-bar').style.width=`${percent}%`; renderProgressText(); }
  function renderProgressText() {
    const el=$('#shcd-progress-text'); if(!el)return;
    if(state.progressMode==='preparing'){el.textContent=t('preparing');return;}
    if(state.progressMode==='processed'){const percent=state.total?Math.min(100,Math.round(state.done/state.total*100)):0;el.textContent=t('processed',{done:fmt(state.done),total:fmt(state.total),percent:fmt(percent)});return;}
    el.textContent=t('nothing_done');
  }

  function copyToClipboard(value){
    if(navigator.clipboard&&window.isSecureContext)return navigator.clipboard.writeText(value);
    return new Promise((resolve,reject)=>{try{const ta=document.createElement('textarea');ta.value=value;ta.setAttribute('readonly','');ta.style.position='fixed';ta.style.top='-1000px';ta.style.opacity='0';document.body.appendChild(ta);ta.focus();ta.select();const ok=document.execCommand('copy');document.body.removeChild(ta);ok?resolve():reject(new Error('copy failed'));}catch(err){reject(err);}});
  }
  function downloadText(text,filename,type){const blob=new Blob([text],{type});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download=filename;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1500);}
  const csvEscape = (value) => { const text=String(value??''); return /[",\r\n]/.test(text)?'"'+text.replace(/"/g,'""')+'"':text; };

  const orderCsvFields=['source_site_hash','source_order_id','order_number','status','currency','prices_include_tax','date_created','date_paid','date_completed','customer_user_id','customer_email','customer_phone','customer_first_name','customer_last_name','billing','shipping','payment_method','payment_title','transaction_id','customer_note','customer_ip','user_agent','created_via','cart_hash','totals','items','shipping_items','fees','coupons'];
  function orderToCsvRow(order,sourceHash){const c=order.customer||{};return [sourceHash,order.source_order_id,order.order_number,order.status,order.currency,order.prices_include_tax?'1':'0',order.date_created,order.date_paid,order.date_completed,c.user_id||'',c.email||'',c.phone||'',c.first_name||'',c.last_name||'',JSON.stringify(order.billing||{}),JSON.stringify(order.shipping||{}),order.payment_method||'',order.payment_title||'',order.transaction_id||'',order.customer_note||'',order.customer_ip||'',order.user_agent||'',order.created_via||'',order.cart_hash||'',JSON.stringify(order.totals||{}),JSON.stringify(order.items||[]),JSON.stringify(order.shipping_items||[]),JSON.stringify(order.fees||[]),JSON.stringify(order.coupons||[])];}
  const customerCsvFields=['source_site_hash','source_user_id','user_login','user_email','display_name','first_name','last_name','registered_at','phone','billing','shipping'];
  function customerToCsvRow(customer,sourceHash){return [sourceHash,customer.source_user_id||'',customer.user_login||'',customer.user_email||'',customer.display_name||'',customer.first_name||'',customer.last_name||'',customer.registered_at||'',customer.phone||'',JSON.stringify(customer.billing||{}),JSON.stringify(customer.shipping||{})];}

  function translateDetail(message){
    const raw=String(message||''); if(state.lang==='fa'||!raw)return raw;
    const maps={en:[[/^ساختار سفارش نامعتبر است\.?$/,'Invalid order structure.'],[/^شناسه سفارش مبدا نامعتبر است\.?$/,'Invalid source order ID.'],[/^ساخت نام کاربری یکتا برای مشتری ممکن نشد\.?$/,'Could not create a unique username for the customer.'],[/^محصول مقصد پیدا نشد و آیتم به‌صورت بکاپ مستقل ثبت شد:\s*(.*)$/,'Destination product was not found; the line item was preserved: $1']],ar:[[/^ساختار سفارش نامعتبر است\.?$/,'بنية الطلب غير صالحة.'],[/^شناسه سفارش مبدا نامعتبر است\.?$/,'معرّف طلب المصدر غير صالح.'],[/^ساخت نام کاربری یکتا برای مشتری ممکن نشد\.?$/,'تعذر إنشاء اسم مستخدم فريد للعميل.'],[/^محصول مقصد پیدا نشد و آیتم به‌صورت بکاپ مستقل ثبت شد:\s*(.*)$/,'لم يتم العثور على المنتج في الوجهة؛ تم الاحتفاظ ببند الطلب: $1']]};
    for(const [pattern,replacement] of (maps[state.lang]||[])){if(pattern.test(raw))return raw.replace(pattern,replacement);} return raw;
  }
  function showStats(stats,prefix=''){const s=stats||{};const count=Number(s.created||0)+Number(s.updated||0)+Number(s.skipped||0)+Number(s.failed||0);if(!count&&!Number(s.warning_count||0))return;log(`${prefix}${t('created')}: ${fmt(s.created||0)} | ${t('updated')}: ${fmt(s.updated||0)} | ${t('skipped')}: ${fmt(s.skipped||0)} | ${t('failed')}: ${fmt(s.failed||0)} | ${t('warnings')}: ${fmt(s.warning_count||0)}`);for(const error of(s.errors||[]).slice(0,10))log(`${t('source_order')} ${error.order||'—'}: ${translateDetail(error.message)||t('unknown_error')}`);for(const warning of(s.warnings||[]).slice(0,10))log(`${t('warning_source_order')} ${warning.order||'—'}: ${translateDetail(warning.message)||t('warning')}`);}
  function showCustomerStats(stats,prefix=''){const s=stats||{};const count=Number(s.created||0)+Number(s.reused||0)+Number(s.skipped||0)+Number(s.failed||0);if(!count)return;log(`${prefix}${t('customers')} — ${t('created')}: ${fmt(s.created||0)} | ${t('reused')}: ${fmt(s.reused||0)} | ${t('skipped')}: ${fmt(s.skipped||0)} | ${t('failed')}: ${fmt(s.failed||0)}`);for(const error of(s.errors||[]).slice(0,10))log(`${t('source_customer')} ${error.customer||'—'}: ${translateDetail(error.message)||t('unknown_error')}`);}
  function mergeStats(total,part){for(const key of ['created','updated','skipped','failed'])total[key]+=Number(part?.[key]||0);total.warning_count+=Number(part?.warning_count||0);for(const e of(part?.errors||[]))if(total.errors.length<20)total.errors.push(e);for(const w of(part?.warnings||[]))if(total.warnings.length<50)total.warnings.push(w);return total;}
  function mergeCustomerStats(total,part){for(const key of ['created','reused','skipped','failed'])total[key]+=Number(part?.[key]||0);for(const e of(part?.errors||[]))if(total.errors.length<20)total.errors.push(e);return total;}

  async function collectOrders(){const orders=[];let page=1,total=0,meta={};while(true){const data=await request(`/export-batch?page=${page}&per_page=${Math.min(500,Number($('#batch_size').value||500))}`)||{};meta=data;total=Number(data.total||total||0);orders.push(...(data.orders||[]));updateProgress(total,orders.length);if(!data.has_more)break;page++;}return {orders,meta,total};}
  async function collectCustomers(){const customers=[];let page=1,total=0,meta={};while(true){const data=await request(`/export-customers-batch?page=${page}&per_page=${Math.min(500,Number($('#batch_size').value||500))}`)||{};meta=data;total=Number(data.total||total||0);customers.push(...(data.customers||[]));updateProgress(total,customers.length);if(!data.has_more)break;page++;}return {customers,meta,total};}

  async function exportOrders(){if(state.busy)return;state.busy=true;resetProgress();logI18n('orders_export_start');try{const {orders,meta}=await collectOrders();const format=$('#export_format')?.value==='csv'?'csv':'json';if(format==='csv'){const rows=[orderCsvFields,...orders.map(o=>orderToCsvRow(o,meta.site_hash||''))];const csv='\uFEFF'+rows.map(row=>row.map(csvEscape).join(',')).join('\r\n')+'\r\n';downloadText(csv,`kerishna-orders-${new Date().toISOString().slice(0,10)}.csv`,'text/csv;charset=utf-8');}else{const payload={plugin:'shcd-kerishna',version:meta.version||S.version||'1.0.0',schema_version:6,data_type:'orders',transfer_mode:'copy_backup',source_read_only:true,site_hash:meta.site_hash||'',exported_at:new Date().toISOString(),total:orders.length,orders,customers:[]};downloadText(JSON.stringify(payload,null,2),`kerishna-orders-${Date.now()}.json`,'application/json;charset=utf-8');}logI18n('orders_export_ready',{count:fmt(orders.length)});toast(t('export_ready'));}catch(e){log(e.message);toast(e.message,true);}finally{state.busy=false;}}
  async function exportCustomers(){if(state.busy)return;state.busy=true;resetProgress();logI18n('customers_export_start');try{const {customers,meta}=await collectCustomers();const format=$('#export_format')?.value==='csv'?'csv':'json';if(format==='csv'){const rows=[customerCsvFields,...customers.map(c=>customerToCsvRow(c,meta.site_hash||''))];const csv='\uFEFF'+rows.map(row=>row.map(csvEscape).join(',')).join('\r\n')+'\r\n';downloadText(csv,`kerishna-customers-${new Date().toISOString().slice(0,10)}.csv`,'text/csv;charset=utf-8');}else{const payload={plugin:'shcd-kerishna',version:meta.version||S.version||'1.0.0',schema_version:6,data_type:'customers',transfer_mode:'copy_backup',source_read_only:true,site_hash:meta.site_hash||'',exported_at:new Date().toISOString(),customer_total:customers.length,customers,orders:[]};downloadText(JSON.stringify(payload,null,2),`kerishna-customers-${Date.now()}.json`,'application/json;charset=utf-8');}logI18n('customers_export_ready',{count:fmt(customers.length)});toast(t('export_ready'));}catch(e){log(e.message);toast(e.message,true);}finally{state.busy=false;}}

  async function transferAll(){if(state.busy)return;if(!$('#destination_url').value.trim()){toast(t('destination_required'),true);return;}state.busy=true;resetProgress();logI18n('transfer_start');try{let page=1,total=0,copied=0,failed=0;const perPage=Math.min(500,Number($('#batch_size').value||500));while(true){const data=await request('/transfer-batch',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({page})});total=Number(data.total||total);const stats=data.remote?.stats||{};const processed=Number(stats.created||0)+Number(stats.updated||0)+Number(stats.skipped||0)+Number(stats.failed||0);copied+=processed;failed+=Number(stats.failed||0);updateProgress(total,Math.min(total,Math.max(copied,page*perPage)));showStats(stats,t('batch_prefix',{page:fmt(page)}));showCustomerStats(data.remote?.customer_stats||{},t('batch_prefix',{page:fmt(page)}));if(!data.has_more)break;page++;}updateProgress(total,total);logI18n('transfer_done',{total:fmt(total),failed:fmt(failed)});toast(failed?t('transfer_partial',{failed:fmt(failed)}):t('transfer_success'),failed>0);}catch(e){log(e.message);toast(e.message,true);}finally{state.busy=false;}}

  function inferJsonType(payload){const explicit=String(payload?.data_type||'');if(['orders','customers','combined'].includes(explicit))return explicit;const oc=Array.isArray(payload?.orders)?payload.orders.length:0;const cc=Array.isArray(payload?.customers)?payload.customers.length:0;if(oc&&cc)return 'combined';if(oc)return 'orders';if(cc)return 'customers';return '';}
  function typeLabel(type){return t(type==='orders'?'type_orders':type==='customers'?'type_customers':type==='combined'?'type_combined':'type_unknown');}
  function showDetectedFile(file,format,type){const el=$('#shcd-detected-file');if(!el)return;el.hidden=false;el.textContent=t('import_file_detected',{name:file.name,format:format.toUpperCase(),type:typeLabel(type)});}

  async function importCsvFile(file){if(!file||state.busy)return;state.busy=true;resetProgress();try{showDetectedFile(file,'csv','');logI18n('smart_import_start',{type:'CSV'});const fd=new FormData();fd.append('file',file);const data=await request('/import-csv',{method:'POST',body:fd});showDetectedFile(file,'csv',data.data_type||'');showStats(data.stats||{});showCustomerStats(data.customer_stats||{});const csvTotal=Number(data.total||0)+Number(data.customer_total||0);updateProgress(csvTotal||1,csvTotal||1);const hasError=Number(data.stats?.failed||0)>0||Number(data.customer_stats?.failed||0)>0;toast(hasError?t('import_partial',{orders:fmt(data.stats?.failed||0),customers:fmt(data.customer_stats?.failed||0)}):t('import_success'),hasError);}catch(e){log(e.message);toast(e.message,true);}finally{state.busy=false;if($('#smart_import_file'))$('#smart_import_file').value='';}}

  async function importJsonFile(file){if(!file||state.busy)return;state.busy=true;resetProgress();logI18n('json_reading');try{let text=await file.text();if(text.charCodeAt(0)===0xFEFF)text=text.slice(1);let payload;try{payload=JSON.parse(text);}catch(err){throw new Error(t('json_parse_error',{message:err.message}));}if(!payload||!['shcd-kerishna','shabnam-karvan-migrator','shcd-customer'].includes(String(payload.plugin||'')))throw new Error(t('wrong_plugin'));if(!/^[a-f0-9]{64}$/i.test(String(payload.site_hash||'')))throw new Error(t('invalid_source'));
      const type=inferJsonType(payload);if(!type)throw new Error(t('invalid_payload'));showDetectedFile(file,'json',type);const orders=Array.isArray(payload.orders)?payload.orders:[];const customers=Array.isArray(payload.customers)?payload.customers:[];logI18n('json_valid',{orders:fmt(orders.length),customers:fmt(customers.length)});logI18n('smart_import_start',{type:typeLabel(type)});
      const batchSize=Math.min(500,Math.max(1,Number($('#batch_size').value||500)));const stats={created:0,updated:0,skipped:0,failed:0,errors:[],warnings:[],warning_count:0};const customerStats={created:0,reused:0,skipped:0,failed:0,errors:[]};
      if(type==='customers'){updateProgress(customers.length,0);for(let offset=0;offset<customers.length;offset+=batchSize){const chunk=customers.slice(offset,offset+batchSize);const batchPayload={plugin:'shcd-kerishna',version:payload.version||'',schema_version:payload.schema_version||6,data_type:'customers',site_hash:payload.site_hash,customers:chunk,orders:[]};const data=await request('/import-batch',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(batchPayload)});mergeCustomerStats(customerStats,data.customer_stats||{});updateProgress(customers.length,Math.min(customers.length,offset+chunk.length));}}
      else if(type==='orders'){updateProgress(orders.length,0);for(let offset=0;offset<orders.length;offset+=batchSize){const chunk=orders.slice(offset,offset+batchSize);const batchPayload={plugin:'shcd-kerishna',version:payload.version||'',schema_version:payload.schema_version||6,data_type:'orders',site_hash:payload.site_hash,customers:[],orders:chunk};const data=await request('/import-batch',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(batchPayload)});mergeStats(stats,data.stats||{});updateProgress(orders.length,Math.min(orders.length,offset+chunk.length));if(Number(data.stats?.failed||0)>0||Number(data.stats?.warning_count||0)>0)showStats(data.stats,t('batch_prefix',{page:fmt(Math.floor(offset/batchSize)+1)}));}}
      else {const customersById=new Map(customers.map(c=>[Number(c.source_user_id||0),c]));updateProgress(orders.length,0);for(let offset=0;offset<orders.length;offset+=batchSize){const chunk=orders.slice(offset,offset+batchSize);const ids=new Set(chunk.map(order=>Number(order?.customer?.user_id||0)).filter(Boolean));const chunkCustomers=Array.from(ids).map(id=>customersById.get(id)).filter(Boolean);const batchPayload={plugin:'shcd-kerishna',version:payload.version||'',schema_version:payload.schema_version||5,data_type:'combined',site_hash:payload.site_hash,customers:chunkCustomers,orders:chunk};const data=await request('/import-batch',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(batchPayload)});mergeStats(stats,data.stats||{});mergeCustomerStats(customerStats,data.customer_stats||{});updateProgress(orders.length,Math.min(orders.length,offset+chunk.length));if(Number(data.stats?.failed||0)>0||Number(data.stats?.warning_count||0)>0)showStats(data.stats,t('batch_prefix',{page:fmt(Math.floor(offset/batchSize)+1)}));}}
      showStats(stats,t('final_prefix'));showCustomerStats(customerStats,t('final_prefix'));const hasError=stats.failed>0||customerStats.failed>0;toast(hasError?t('import_partial',{orders:fmt(stats.failed),customers:fmt(customerStats.failed)}):t('import_success'),hasError);
    }catch(e){log(e.message);toast(e.message,true);}finally{state.busy=false;if($('#smart_import_file'))$('#smart_import_file').value='';}}

  async function processSmartFile(file){if(!file)return;const name=String(file.name||'').toLowerCase();if(name.endsWith('.csv')||String(file.type||'').includes('csv'))return importCsvFile(file);if(name.endsWith('.json')||String(file.type||'').includes('json')||!file.type)return importJsonFile(file);toast(t('invalid_payload'),true);}

  document.addEventListener('click',(e)=>{
    const langBtn=e.target.closest('[data-lang]');if(langBtn){e.preventDefault();applyLanguage(langBtn.dataset.lang,true);return;}
    const copyBtn=e.target.closest('[data-copy-target]');if(copyBtn){e.preventDefault();const target=document.getElementById(copyBtn.dataset.copyTarget);const value=target?.textContent?.trim()||'';if(!value||value==='••••••••'){toast(t('key_missing'),true);return;}copyToClipboard(value).then(()=>toast(t('copied'))).catch(()=>toast(t('copy_failed'),true));return;}
    const action=e.target.closest('[data-action]')?.dataset.action;if(!action)return;e.preventDefault();const fn={refresh,save,generate,'export-orders':exportOrders,'export-customers':exportCustomers,transfer:transferAll}[action];if(fn)fn().catch(err=>{log(err.message);toast(err.message,true);});
  });

  const fileInput=$('#smart_import_file');
  fileInput?.addEventListener('change',e=>processSmartFile(e.target.files[0]));
  const dropzone=$('#shcd-dropzone');
  if(dropzone){
    ['dragenter','dragover'].forEach(name=>dropzone.addEventListener(name,e=>{e.preventDefault();e.stopPropagation();dropzone.classList.add('is-dragging');}));
    ['dragleave','drop'].forEach(name=>dropzone.addEventListener(name,e=>{e.preventDefault();e.stopPropagation();dropzone.classList.remove('is-dragging');}));
    dropzone.addEventListener('drop',e=>processSmartFile(e.dataTransfer?.files?.[0]));
    dropzone.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();fileInput?.click();}});
  }

  applyLanguage(state.lang,false);
  refresh().catch(err=>{if($('#shcd-status'))$('#shcd-status').textContent=err.message;toast(err.message,true);});
})();
