<?php
if (!defined('ABSPATH')) exit;
/**
 * Plugin Name: Groot Vision - Top Bar Announcement
 * Description: نوار اعلان متحرک حرفه‌ای برای وردپرس با پنل مدیریت اختصاصی (زیرمجموعه هاب گروت ویژن).
 * Version: 2.2.0
 * Author: Groot Vision
 * Text Domain: groot-vision-topbar
 */

define('GV_TOPBAR_VERSION', '2.2.0');
define('GV_TOPBAR_OPTION', 'gv_topbar_settings');
define('GV_TOPBAR_SLUG', 'gv-topbar-settings');

/* =========================================================
   0. رجیستر به‌عنوان زیرمنوی هاب گروت ویژن
   ⚠️ توجه: این افزونه باید همراه با فایل هاب (groot-vision-hub.php)
   فعال باشد تا منوی والد «groot-vision-hub» وجود داشته باشد.
========================================================= */
add_action('admin_menu', function () {
    add_submenu_page(
        'groot-vision-hub',              // والد: هاب گروت ویژن
        'نوار اعلان Groot Vision',       // page title
        '📢 نوار اعلان',                 // menu title
        'manage_options',
        GV_TOPBAR_SLUG,
        'gv_topbar_settings_page'
    );
});

/* =========================================================
   1. تنظیمات پیش‌فرض
========================================================= */
function gv_topbar_defaults() {
    return array(
        'enabled'        => 1,
        'messages'       => "🔥 تخفیف ویژه امروز|\n🚀 ارسال سریع و مطمئن|\n💎 کیفیت حرفه‌ای، رضایت تضمینی|",
        'position'       => 'top',      // top | bottom
        'bg_start'       => '#0e4037',
        'bg_end'         => '#145c4d',
        'text_color'     => '#ffffff',
        'accent_color'   => '#4ade80',
        'font_size'      => 15,
        'height'         => 42,
        'speed_type'     => 45,   // ms per char (typing)
        'speed_erase'    => 20,   // ms per char (erasing)
        'pause_time'     => 1400, // ms pause after full text
        'closable'       => 1,
        'sticky'         => 1,
        'radius'         => 0,
    );
}
function gv_topbar_get_settings() {
    $saved = get_option(GV_TOPBAR_OPTION, array());
    return wp_parse_args($saved, gv_topbar_defaults());
}

/* =========================================================
   2. صفحه مدیریت
========================================================= */
add_action('admin_enqueue_scripts', function ($hook) {
    // برای زیرمنوی «groot-vision-hub»، هوک صفحه به‌صورت
    // groot-vision-hub_page_gv-topbar-settings ساخته می‌شود.
    if ($hook !== 'groot-vision-hub_page_' . GV_TOPBAR_SLUG) return;
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_script('jquery');
});

function gv_topbar_settings_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['gv_topbar_save']) && check_admin_referer('gv_topbar_save_action', 'gv_topbar_nonce')) {
        $data = array(
            'enabled'      => isset($_POST['enabled']) ? 1 : 0,
            'messages'     => sanitize_textarea_field(wp_unslash($_POST['messages'])),
            'position'     => in_array($_POST['position'], array('top', 'bottom'), true) ? sanitize_text_field($_POST['position']) : 'top',
            'bg_start'     => sanitize_hex_color($_POST['bg_start']),
            'bg_end'       => sanitize_hex_color($_POST['bg_end']),
            'text_color'   => sanitize_hex_color($_POST['text_color']),
            'accent_color' => sanitize_hex_color($_POST['accent_color']),
            'font_size'    => absint($_POST['font_size']),
            'height'       => absint($_POST['height']),
            'speed_type'   => absint($_POST['speed_type']),
            'speed_erase'  => absint($_POST['speed_erase']),
            'pause_time'   => absint($_POST['pause_time']),
            'closable'     => isset($_POST['closable']) ? 1 : 0,
            'sticky'       => isset($_POST['sticky']) ? 1 : 0,
            'radius'       => absint($_POST['radius']),
        );
        update_option(GV_TOPBAR_OPTION, $data);
        echo '<div class="updated notice"><p>✔ تنظیمات با موفقیت ذخیره شد.</p></div>';
    }

    $s = gv_topbar_get_settings();
    ?>
    <div class="wrap gv-wrap" dir="rtl">
        <div class="gv-header">
            <div class="gv-logo">
                <span class="dashicons dashicons-megaphone"></span>
                <div>
                    <h1>Groot Vision</h1>
                    <p>پنل مدیریت نوار اعلان سایت</p>
                </div>
            </div>
            <span class="gv-badge">نسخه <?php echo esc_html(GV_TOPBAR_VERSION); ?></span>
        </div>

        <form method="post" class="gv-form">
            <?php wp_nonce_field('gv_topbar_save_action', 'gv_topbar_nonce'); ?>

            <div class="gv-grid">
                <!-- ستون تنظیمات -->
                <div class="gv-col">

                    <div class="gv-card">
                        <h2>وضعیت نمایش</h2>
                        <label class="gv-switch">
                            <input type="checkbox" name="enabled" value="1" <?php checked($s['enabled'], 1); ?>>
                            <span class="gv-slider"></span>
                            <span class="gv-switch-label">فعال بودن نوار اعلان</span>
                        </label>
                    </div>

                    <div class="gv-card">
                        <h2>پیام‌ها</h2>
                        <p class="gv-hint">هر خط یک پیام جدا محسوب می‌شود. برای لینک‌دار کردن یک پیام، آدرس را با علامت <code>|</code> در انتهای خط اضافه کنید. مثال:<br><code>🔥 تخفیف ویژه امروز|https://example.com/sale</code></p>
                        <textarea name="messages" class="gv-textarea" rows="8"><?php echo esc_textarea($s['messages']); ?></textarea>
                    </div>

                    <div class="gv-card">
                        <h2>موقعیت و رفتار</h2>
                        <div class="gv-row">
                            <label>موقعیت نوار</label>
                            <select name="position">
                                <option value="top" <?php selected($s['position'], 'top'); ?>>بالای صفحه</option>
                                <option value="bottom" <?php selected($s['position'], 'bottom'); ?>>پایین صفحه</option>
                            </select>
                        </div>
                        <label class="gv-switch">
                            <input type="checkbox" name="closable" value="1" <?php checked($s['closable'], 1); ?>>
                            <span class="gv-slider"></span>
                            <span class="gv-switch-label">نمایش دکمه بستن (بستن تا پایان جلسه)</span>
                        </label>
                        <label class="gv-switch">
                            <input type="checkbox" name="sticky" value="1" <?php checked($s['sticky'], 1); ?>>
                            <span class="gv-slider"></span>
                            <span class="gv-switch-label">چسبیده باقی ماندن هنگام اسکرول (Sticky)</span>
                        </label>
                    </div>

                    <div class="gv-card">
                        <h2>ظاهر و رنگ‌بندی</h2>
                        <div class="gv-row">
                            <label>رنگ شروع گرادینت</label>
                            <input type="text" class="gv-color" name="bg_start" value="<?php echo esc_attr($s['bg_start']); ?>">
                        </div>
                        <div class="gv-row">
                            <label>رنگ پایان گرادینت</label>
                            <input type="text" class="gv-color" name="bg_end" value="<?php echo esc_attr($s['bg_end']); ?>">
                        </div>
                        <div class="gv-row">
                            <label>رنگ متن</label>
                            <input type="text" class="gv-color" name="text_color" value="<?php echo esc_attr($s['text_color']); ?>">
                        </div>
                        <div class="gv-row">
                            <label>رنگ تاکیدی (خط جداکننده و کرسر)</label>
                            <input type="text" class="gv-color" name="accent_color" value="<?php echo esc_attr($s['accent_color']); ?>">
                        </div>
                        <div class="gv-row">
                            <label>اندازه فونت (px)</label>
                            <input type="number" name="font_size" value="<?php echo esc_attr($s['font_size']); ?>" min="10" max="30">
                        </div>
                        <div class="gv-row">
                            <label>ارتفاع نوار (px)</label>
                            <input type="number" name="height" value="<?php echo esc_attr($s['height']); ?>" min="30" max="100">
                        </div>
                        <div class="gv-row">
                            <label>گردی گوشه‌ها (px)</label>
                            <input type="number" name="radius" value="<?php echo esc_attr($s['radius']); ?>" min="0" max="40">
                        </div>
                    </div>

                    <div class="gv-card">
                        <h2>سرعت انیمیشن تایپ</h2>
                        <div class="gv-row">
                            <label>سرعت تایپ (میلی‌ثانیه بین حروف)</label>
                            <input type="number" name="speed_type" value="<?php echo esc_attr($s['speed_type']); ?>" min="10" max="200">
                        </div>
                        <div class="gv-row">
                            <label>سرعت پاک شدن</label>
                            <input type="number" name="speed_erase" value="<?php echo esc_attr($s['speed_erase']); ?>" min="5" max="150">
                        </div>
                        <div class="gv-row">
                            <label>مکث بین پیام‌ها (میلی‌ثانیه)</label>
                            <input type="number" name="pause_time" value="<?php echo esc_attr($s['pause_time']); ?>" min="200" max="6000">
                        </div>
                    </div>

                    <button type="submit" name="gv_topbar_save" class="gv-save-btn">ذخیره تنظیمات</button>
                </div>

                <!-- ستون پیش‌نمایش -->
                <div class="gv-col gv-preview-col">
                    <div class="gv-card gv-sticky">
                        <h2>پیش‌نمایش زنده</h2>
                        <div id="gv-preview-box">
                            <div id="gv-preview-bar">
                                <span id="gv-preview-text"></span>
                            </div>
                        </div>
                        <p class="gv-hint">پیش‌نمایش تقریبی است؛ نمای واقعی در سایت مشاهده می‌شود.</p>
                    </div>
                </div>
            </div>
        </form>

        <div class="gv-footer">
            ساخته و توسعه‌یافته توسط <strong>Groot Vision</strong> — تمامی حقوق محفوظ است.
        </div>
    </div>

    <style>
        .gv-wrap{max-width:1200px;margin-top:20px;font-family:'Vazirmatn',Tahoma,sans-serif;}
        .gv-header{display:flex;align-items:center;justify-content:space-between;background:linear-gradient(90deg,#0e4037,#145c4d);color:#fff;padding:22px 28px;border-radius:14px;margin-bottom:24px;box-shadow:0 10px 30px rgba(14,64,55,.3);}
        .gv-logo{display:flex;align-items:center;gap:14px;}
        .gv-logo .dashicons{font-size:36px;width:36px;height:36px;color:#4ade80;}
        .gv-logo h1{margin:0;font-size:22px;color:#fff;}
        .gv-logo p{margin:2px 0 0;font-size:13px;color:#cbd5e1;}
        .gv-badge{background:rgba(74,222,128,.15);border:1px solid #4ade80;color:#4ade80;padding:6px 14px;border-radius:20px;font-size:12px;}
        .gv-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:20px;align-items:start;}
        .gv-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 2px 10px rgba(0,0,0,.03);}
        .gv-card h2{font-size:15px;margin:0 0 14px;color:#0f172a;border-bottom:2px solid #f1f5f9;padding-bottom:10px;}
        .gv-hint{font-size:12.5px;color:#64748b;margin:0 0 10px;line-height:1.9;}
        .gv-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:10px;}
        .gv-row label{font-size:13px;color:#334155;font-weight:600;flex:1;}
        .gv-row select,.gv-row input[type=number]{border-radius:8px;border:1px solid #cbd5e1;padding:6px 10px;width:140px;}
        .gv-textarea{width:100%;border-radius:10px;border:1px solid #cbd5e1;padding:12px;font-size:14px;font-family:'Vazirmatn',Tahoma,sans-serif;}
        .gv-switch{display:flex;align-items:center;gap:10px;margin-bottom:14px;cursor:pointer;}
        .gv-switch input{display:none;}
        .gv-slider{width:42px;height:22px;background:#cbd5e1;border-radius:20px;position:relative;transition:.25s;flex-shrink:0;}
        .gv-slider::before{content:"";position:absolute;width:18px;height:18px;background:#fff;border-radius:50%;top:2px;right:2px;transition:.25s;box-shadow:0 1px 3px rgba(0,0,0,.3);}
        .gv-switch input:checked + .gv-slider{background:#0e4037;}
        .gv-switch input:checked + .gv-slider::before{right:22px;}
        .gv-switch-label{font-size:13px;color:#334155;}
        .gv-save-btn{background:linear-gradient(90deg,#0e4037,#145c4d);color:#fff;border:none;padding:12px 30px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 6px 16px rgba(14,64,55,.3);}
        .gv-save-btn:hover{opacity:.9;}
        .gv-sticky{position:sticky;top:40px;}
        #gv-preview-box{background:#f8fafc;border-radius:10px;padding:16px;border:1px dashed #cbd5e1;}
        #gv-preview-bar{display:flex;align-items:center;justify-content:center;padding:10px;border-radius:8px;font-size:14px;min-height:42px;}
        .gv-footer{text-align:center;color:#94a3b8;font-size:12.5px;margin:30px 0 10px;}
        @media(max-width:960px){.gv-grid{grid-template-columns:1fr;}.gv-sticky{position:static;}}
    </style>

    <script>
    jQuery(function ($) {
        $('.gv-color').wpColorPicker({ change: gvUpdatePreview, clear: gvUpdatePreview });

        function gvUpdatePreview() {
            setTimeout(function () {
                const bg1 = $('input[name=bg_start]').val() || '#0f172a';
                const bg2 = $('input[name=bg_end]').val() || '#1e293b';
                const color = $('input[name=text_color]').val() || '#fff';
                const fs = $('input[name=font_size]').val() || 15;
                const radius = $('input[name=radius]').val() || 0;
                $('#gv-preview-bar').css({
                    background: 'linear-gradient(90deg,' + bg1 + ',' + bg2 + ')',
                    color: color,
                    fontSize: fs + 'px',
                    borderRadius: radius + 'px'
                });
            }, 50);
        }
        $('#gv-preview-text').text('🔥 پیش‌نمایش نوار اعلان شما');
        gvUpdatePreview();
        $('.gv-form input, .gv-form select').on('input change', gvUpdatePreview);
    });
    </script>
    <?php
}

/* =========================================================
   3. پردازش پیام‌ها (متن + لینک اختیاری)
========================================================= */
function gv_topbar_parse_messages($raw) {
    $lines = array_filter(array_map('trim', explode("\n", $raw)));
    $messages = array();
    foreach ($lines as $line) {
        if (strpos($line, '|') !== false) {
            list($text, $url) = array_map('trim', explode('|', $line, 2));
            $messages[] = array('text' => $text, 'url' => $url ? esc_url($url) : '');
        } else {
            $messages[] = array('text' => $line, 'url' => '');
        }
    }
    return $messages;
}

/* =========================================================
   4. خروجی فرانت (استایل + مارک‌آپ + اسکریپت)
========================================================= */
add_action('wp_head', function () {
    $s = gv_topbar_get_settings();
    if (!$s['enabled']) return;
    $position_css = $s['position'] === 'bottom' ? 'bottom:0;' : 'top:0;';
    $sticky_css   = $s['sticky'] ? 'position:sticky;' : 'position:relative;';
    ?>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;600;700&display=swap');
    #gv-topbar {
        <?php echo $sticky_css; ?>
        <?php echo $position_css; ?>
        z-index: 99999;
        width: 100%;
        min-height: <?php echo esc_attr($s['height']); ?>px;
        background: linear-gradient(90deg, <?php echo esc_attr($s['bg_start']); ?>, <?php echo esc_attr($s['bg_end']); ?>);
        color: <?php echo esc_attr($s['text_color']); ?>;
        font-family: 'Vazirmatn', sans-serif;
        font-size: <?php echo esc_attr($s['font_size']); ?>px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 8px 40px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        border-radius: <?php echo esc_attr($s['radius']); ?>px;
        box-sizing: border-box;
        direction: rtl;
        position: relative;
    }
    #gv-topbar a { color: inherit; text-decoration: none; }
    #gv-text {
        border-left: 2px solid <?php echo esc_attr($s['accent_color']); ?>;
        padding-left: 8px;
        white-space: nowrap;
        overflow: hidden;
    }
    #gv-topbar-close {
        cursor: pointer;
        background: rgba(255,255,255,0.12);
        border: none;
        color: <?php echo esc_attr($s['text_color']); ?>;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        font-size: 9px;
        line-height: 1;
        flex-shrink: 0;
        transition: background .2s;
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    #gv-topbar-close:hover { background: rgba(255,255,255,0.28); }
    </style>
    <?php
});

add_action('wp_body_open', function () {
    $s = gv_topbar_get_settings();
    if (!$s['enabled']) return;

    $messages = gv_topbar_parse_messages($s['messages']);
    if (empty($messages)) return;
    ?>
    <div id="gv-topbar" data-gv-bar>
        <span id="gv-text"></span>
        <?php if ($s['closable']) : ?>
            <button id="gv-topbar-close" aria-label="بستن نوار اعلان">✕</button>
        <?php endif; ?>
    </div>
    <script>
    (function () {
        const messages = <?php echo wp_json_encode($messages); ?>;
        const bar = document.getElementById('gv-topbar');
        if (!bar) return;

        if (sessionStorage.getItem('gv_topbar_closed') === '1') {
            bar.style.display = 'none';
            return;
        }

        const el = document.getElementById('gv-text');
        const closeBtn = document.getElementById('gv-topbar-close');
        let msgIndex = 0, charIndex = 0, paused = false;

        const TYPE_SPEED  = <?php echo (int) $s['speed_type']; ?>;
        const ERASE_SPEED = <?php echo (int) $s['speed_erase']; ?>;
        const PAUSE_TIME  = <?php echo (int) $s['pause_time']; ?>;

        function currentMsg() { return messages[msgIndex]; }

        function renderText(str) {
            el.textContent = str;
        }

        function type() {
            if (paused) return;
            const msg = currentMsg();
            const text = msg.text;
            if (charIndex < text.length) {
                renderText(text.substring(0, charIndex + 1));
                charIndex++;
                setTimeout(type, TYPE_SPEED);
            } else {
                setTimeout(erase, PAUSE_TIME);
            }
        }

        function erase() {
            if (paused) return;
            const msg = currentMsg();
            const text = msg.text;
            if (charIndex > 0) {
                renderText(text.substring(0, charIndex - 1));
                charIndex--;
                setTimeout(erase, ERASE_SPEED);
            } else {
                msgIndex = (msgIndex + 1) % messages.length;
                setTimeout(type, 300);
            }
        }

        bar.addEventListener('mouseenter', () => { paused = true; });
        bar.addEventListener('mouseleave', () => { paused = false; type(); });

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                bar.style.display = 'none';
                sessionStorage.setItem('gv_topbar_closed', '1');
            });
        }

        // اگر پیام لینک دارد، کل متن را قابل کلیک می‌کنیم
        function refreshClickable() {
            const msg = currentMsg();
            if (msg.url) {
                bar.style.cursor = 'pointer';
                bar.onclick = function (e) {
                    if (e.target === closeBtn) return;
                    window.open(msg.url, '_blank');
                };
            } else {
                bar.style.cursor = 'default';
                bar.onclick = null;
            }
        }
        setInterval(refreshClickable, 500);

        type();
    })();
    </script>
    <?php
}, 5);

/* fallback برای قالب‌هایی که wp_body_open را فراخوانی نمی‌کنند */
add_action('wp_footer', function () {
    if (did_action('wp_body_open')) return; // اگر قبلا اجرا شده، دوباره لازم نیست
    $s = gv_topbar_get_settings();
    if (!$s['enabled']) return;
    echo '<script>console.warn("Groot Vision Topbar: قالب شما از wp_body_open پشتیبانی نمی‌کند. لطفاً از یک قالب استاندارد وردپرسی استفاده کنید تا نوار اعلان به درستی نمایش داده شود.");</script>';
});