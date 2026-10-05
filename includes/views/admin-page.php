<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div id="shcd-customer-app" class="shcd-app" dir="rtl" lang="fa">
    <header class="shcd-hero">
        <div class="shcd-hero-main">
            <div class="shcd-brand">
                <img src="<?php echo esc_url( SHCD_CUSTOMER_URL . 'assets/images/kerishna-logo.png' ); ?>" alt="Kerishna logo" class="shcd-logo-hero">
                <div class="shcd-brand-copy">
                    <span class="shcd-kicker" data-i18n="hero_kicker">انتقال هوشمند؛ انتخاب داده با شما</span>
                    <h1>Kerishna Shop Migrator</h1>
                    <p data-i18n="hero_subtitle">فقط آنچه نیاز دارید منتقل کنید: سفارش‌ها، مشتریان یا هر دو. Kerishna مبدا را دست‌نخورده نگه می‌دارد، از ساخت حساب‌های تکراری جلوگیری می‌کند و مسیر انتقال را ساده و قابل‌پیگیری نگه می‌دارد.</p>
                </div>
            </div>
            <div class="shcd-hero-tools">
                <div class="shcd-language-switcher" role="group" aria-label="Language">
                    <button type="button" class="shcd-lang-btn" data-lang="fa" aria-pressed="true"><span>🇮🇷</span><b>فارسی</b></button>
                    <button type="button" class="shcd-lang-btn" data-lang="en" aria-pressed="false"><span>🇬🇧</span><b>English</b></button>
                    <button type="button" class="shcd-lang-btn" data-lang="ar" aria-pressed="false"><span>🇸🇦</span><b>العربية</b></button>
                </div>
            </div>
        </div>
        <div class="shcd-hero-footer" aria-label="Plugin metadata">
            <code class="shcd-hero-slug">shcd-kerishna</code>
            <div class="shcd-badge shcd-version-badge">v<?php echo esc_html( SHCD_CUSTOMER_VERSION ); ?></div>
        </div>
    </header>

    <div class="shcd-grid">
        <section class="shcd-card shcd-card-wide">
            <div class="shcd-card-head">
                <div>
                    <h2 data-i18n="connection_title">همه‌چیز برای یک انتقال مطمئن آماده است؟</h2>
                    <p class="shcd-section-copy" data-i18n="connection_subtitle">وضعیت ذخیره‌سازی سفارش‌ها و اتصال سایت را قبل از شروع بررسی کنید.</p>
                </div>
                <button class="shcd-btn shcd-btn-soft" data-action="refresh" data-i18n="refresh">بررسی دوباره</button>
            </div>
            <div id="shcd-status" class="shcd-status-grid"><div class="shcd-skeleton"></div><div class="shcd-skeleton"></div><div class="shcd-skeleton"></div></div>
        </section>

        <section class="shcd-card">
            <div class="shcd-card-head">
                <div>
                    <h2 data-i18n="destination_credentials">اتصال به سایت مقصد</h2>
                    <p class="shcd-section-copy" data-i18n="destination_intro">برای انتقال مستقیم، مقصد را یک‌بار متصل کنید و دفعات بعد با چند کلیک بکاپ را بفرستید.</p>
                </div>
            </div>
            <div class="shcd-field"><label data-i18n="destination_url">آدرس سایت مقصد</label><input id="destination_url" type="url" placeholder="https://example.com"></div>
            <div class="shcd-field"><label data-i18n="destination_api_key">API Key مقصد</label><input id="destination_api_key" type="text" autocomplete="off"></div>
            <div class="shcd-field"><label data-i18n="destination_api_secret">API Secret مقصد</label><input id="destination_api_secret" type="password" autocomplete="off"></div>
            <div class="shcd-actions"><button class="shcd-btn" data-action="save" data-i18n="save_settings">ذخیره اتصال</button></div>
            <p class="shcd-help" data-i18n="destination_help">Kerishna را روی سایت مقصد هم نصب کنید، کلیدهای اتصال را در مقصد بسازید و اینجا وارد کنید. خالی گذاشتن Key یا Secret، مقدار ذخیره‌شده قبلی را تغییر نمی‌دهد.</p>
        </section>

        <section class="shcd-card">
            <div class="shcd-card-head">
                <div>
                    <h2 data-i18n="local_credentials">کلیدهای اتصال این سایت</h2>
                    <p class="shcd-section-copy" data-i18n="local_credentials_intro">برای دریافت انتقال مستقیم روی این سایت از این کلیدها استفاده کنید.</p>
                </div>
            </div>
            <p class="shcd-help" data-i18n="local_credentials_help">API Secret را محرمانه نگه دارید و فقط در سایت مبدا وارد کنید.</p>
            <div class="shcd-secret-row"><div class="shcd-secret"><span id="local_api_key">••••••••</span></div><button class="shcd-copy-btn" data-copy-target="local_api_key" data-i18n="copy">کپی</button></div>
            <div class="shcd-secret-row"><div class="shcd-secret"><span id="local_api_secret">••••••••</span></div><button class="shcd-copy-btn" data-copy-target="local_api_secret" data-i18n="copy">کپی</button></div>
            <button class="shcd-btn shcd-btn-outline" data-action="generate" data-i18n="generate_credentials">ساخت کلیدهای جدید</button>
        </section>

        <section class="shcd-card shcd-card-wide shcd-transfer-workspace">
            <div class="shcd-card-head shcd-card-head-stack-mobile">
                <div>
                    <span class="shcd-section-kicker" data-i18n="workspace_kicker">فضای انتقال داده</span>
                    <h2 data-i18n="backup_title">فقط همان داده‌ای را جابه‌جا کنید که نیاز دارید</h2>
                    <p class="shcd-section-copy" data-i18n="backup_intro">سفارش‌ها و مشتریان مستقل از هم خروجی می‌گیرند. برای ورود هم کافی است فایل را رها کنید؛ Kerishna نوع فایل و داده را خودش تشخیص می‌دهد.</p>
                </div>
                <span class="shcd-live" data-i18n="ready">● آماده برای شروع</span>
            </div>

            <div class="shcd-options shcd-options-compact">
                <label><span data-i18n="batch_size">تعداد رکورد در هر مرحله</span><input id="batch_size" type="number" min="1" max="500" value="500"></label>
                <label class="shcd-checkbox-option"><span data-i18n="direct_customers">در انتقال مستقیم، حساب مشتریان هم منتقل شود</span><input id="create_customers" type="checkbox"></label>
            </div>

            <div class="shcd-data-grid">
                <div class="shcd-data-panel shcd-export-panel">
                    <div class="shcd-panel-icon" aria-hidden="true">↥</div>
                    <div class="shcd-panel-copy">
                        <h3 data-i18n="export_title">خروجی بگیرید، انتخاب با شماست</h3>
                        <p data-i18n="export_intro">فرمت را انتخاب کنید، سپس فقط سفارش‌ها یا فقط مشتریان را دانلود کنید.</p>
                    </div>
                    <div class="shcd-format-picker">
                        <label for="export_format" data-i18n="export_format">فرمت خروجی</label>
                        <div class="shcd-select-wrap">
                            <select id="export_format" aria-label="Export format">
                                <option value="json">JSON</option>
                                <option value="csv">CSV</option>
                            </select>
                            <span class="shcd-select-arrow" aria-hidden="true"></span>
                        </div>
                    </div>
                    <div class="shcd-export-actions">
                        <button class="shcd-btn shcd-btn-accent" data-action="export-orders"><span aria-hidden="true">🧾</span><span data-i18n="export_orders">خروجی سفارش‌ها</span></button>
                        <button class="shcd-btn" data-action="export-customers"><span aria-hidden="true">👥</span><span data-i18n="export_customers">خروجی مشتریان</span></button>
                    </div>
                </div>

                <div class="shcd-data-panel shcd-import-panel">
                    <div class="shcd-panel-copy">
                        <h3 data-i18n="smart_import_title">ورود هوشمند با Drag & Drop</h3>
                        <p data-i18n="smart_import_intro">JSON یا CSV را اینجا رها کنید. Kerishna تشخیص می‌دهد فایل مربوط به سفارش‌ها، مشتریان یا بکاپ ترکیبی نسخه‌های قبلی است.</p>
                    </div>
                    <label id="shcd-dropzone" class="shcd-dropzone" tabindex="0" role="button" for="smart_import_file">
                        <span class="shcd-dropzone-icon" aria-hidden="true">⇩</span>
                        <strong data-i18n="dropzone_title">فایل را اینجا رها کنید</strong>
                        <span data-i18n="dropzone_hint">یا برای انتخاب فایل کلیک کنید — JSON / CSV</span>
                        <input id="smart_import_file" type="file" accept="application/json,text/csv,.json,.csv" hidden>
                    </label>
                    <div id="shcd-detected-file" class="shcd-detected-file" hidden></div>
                </div>
            </div>

            <div class="shcd-direct-row">
                <div>
                    <strong data-i18n="direct_title">انتقال مستقیم بین دو سایت</strong>
                    <span data-i18n="direct_intro">بدون دانلود و آپلود دستی فایل، بکاپ سفارش‌ها را مستقیم به مقصد متصل‌شده بفرستید.</span>
                </div>
                <button class="shcd-btn shcd-btn-accent" data-action="transfer" data-i18n="direct_backup">ارسال مستقیم به مقصد</button>
            </div>

            <div class="shcd-progress"><div id="shcd-progress-bar"></div></div>
            <div id="shcd-progress-text" data-i18n="nothing_done">هنوز عملیاتی شروع نشده است.</div>
            <p class="shcd-help shcd-copy-warning" data-i18n="source_readonly_notice">مبدا در فرایند خروجی و انتقال مستقیم فقط خوانده می‌شود؛ سفارش‌ها، مشتریان، محصولات و شناسه‌های آن حذف یا بازنویسی نمی‌شوند.</p>
            <div id="shcd-log" class="shcd-log" aria-live="polite"></div>
        </section>

        <section class="shcd-card shcd-card-wide shcd-developer-card">
            <div class="shcd-dev-copy">
                <img src="<?php echo esc_url( SHCD_CUSTOMER_URL . 'assets/images/shcd-developer.png' ); ?>" alt="SHABNAM.DEV developer logo">
                <div>
                    <strong data-i18n="developed_by">توسعه داده‌شده توسط SHABNAM.DEV</strong>
                    <span data-i18n="developer_note">مستندات، پشتیبانی و نسخه‌های جدید در shabnam.dev در دسترس است.</span>
                </div>
            </div>
            <a href="https://shabnam.dev" target="_blank" rel="noopener noreferrer" class="shcd-dev-link">shabnam.dev</a>
        </section>
    </div>
    <div id="shcd-toast" class="shcd-toast" role="status"></div>
</div>
