<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * ==========================================================
 *  Groot Vision — امنیت و سرعت (نسخه بهینه‌شده)
 *  ۱) کپچای ساده ریاضی برای فرم‌های بومی وردپرس (کامنت / ورود / ثبت‌نام)
 *  ۲) بلاک درخواست‌های سرور-به-سرور غیرضروری برای افزایش سرعت
 *     (مخصوصاً برای هاست‌های ایران که با تحریم کند می‌شوند)
 *  ۳) لیست سفید همیشگی درگاه‌های پرداخت (هرگز بلاک نمی‌شوند)
 *  ۴) تشخیص خودکار دامنه‌های کند برای پیشنهاد بلاک
 *  ۵) بهینه‌سازی‌های سریع و کم‌خطر (XML-RPC ،RSD ،WLW و ...)
 * ==========================================================
 */
define( 'GV_SEC_OPT', 'gv_security_settings' );
define( 'GV_SEC_NONCE', 'gv_sec_nonce_action' );
define( 'GV_SEC_SLOW_OPT', 'gv_sec_slow_hosts' );

/* ==========================================================================
   ۱) مقادیر پیش‌فرض و دریافت تنظیمات
   ========================================================================== */

function gv_sec_default_settings() {
	return array(
		// --- کپچا ---
		'captcha_enabled'  => 1,
		'captcha_comments' => 1,
		'captcha_login'    => 1,
		'captcha_register' => 1,

		// --- سرعت ---
		'speed_enabled'    => 1,
		'blocked_defaults' => gv_sec_default_blocklist_values(),
		'custom_domains'   => '',
		'disable_emojis'   => 1,

		// --- بهینه‌سازی‌های اضافه (کم‌خطر) ---
		'disable_xmlrpc'      => 0,
		'remove_rsd_link'     => 1,
		'remove_wlw_link'     => 1,
		'remove_shortlink'    => 1,
		'remove_generator'    => 1,

		// --- درگاه پرداخت (لیست سفید سفارشی کاربر) ---
		'payment_whitelist_custom' => '',

		// --- تشخیص خودکار دامنه‌های کند ---
		'detector_enabled'   => 1,
		'detector_threshold' => 1.5, // ثانیه

		// --- آمار ---
		'blocked_count'    => 0,
		'active'           => 1,
	);
}

/**
 * لیست کامل دامنه‌های شناخته‌شده به همراه وضعیت پیش‌فرض روشن/خاموش.
 * دامنه‌هایی که در gv_sec_recommended_domains() هستند به‌صورت پیش‌فرض روشن (۱) هستند
 * چون بلاک کردن‌شان تقریباً همیشه بی‌خطر و مفید برای سرعت است.
 */
function gv_sec_default_blocklist_values() {
	$recommended = gv_sec_recommended_domains();
	$all         = array_keys( gv_sec_domain_labels() );
	$out         = array();
	foreach ( $all as $domain ) {
		$out[ $domain ] = in_array( $domain, $recommended, true ) ? 1 : 0;
	}
	return $out;
}

/**
 * دامنه‌هایی که تیم گروت ویژن پیشنهاد می‌کند به‌صورت پیش‌فرض بلاک باشند.
 * این‌ها زیرساخت‌های خودِ وردپرس هستند و در ۹۹٪ سایت‌ها بلاک کردنشان
 * هیچ آسیبی نمی‌زند، فقط باعث سریع‌تر شدن صفحاتی مثل «افزونه‌ها» و
 * «قالب‌ها» و «بروزرسانی‌ها» در پنل مدیریت می‌شود.
 */
function gv_sec_recommended_domains() {
	return array(
		'api.wordpress.org',
		'downloads.wordpress.org',
		's.w.org',
		'ps.w.org',                 // تصاویر و اسکرین‌شات افزونه/قالب در صفحه نصب
		'wordpress.org',
		'plugins.svn.wordpress.org',
		'themes.svn.wordpress.org',
	);
}

function gv_sec_get_settings() {
	$saved    = get_option( GV_SEC_OPT, array() );
	$defaults = gv_sec_default_settings();
	$settings = wp_parse_args( $saved, $defaults );

	// اگر دامنه جدیدی به لیست پیش‌فرض ما اضافه شده ولی در تنظیمات ذخیره‌شده کاربر نیست، اضافه‌اش کن
	if ( isset( $saved['blocked_defaults'] ) && is_array( $saved['blocked_defaults'] ) ) {
		$settings['blocked_defaults'] = wp_parse_args( $saved['blocked_defaults'], $defaults['blocked_defaults'] );
	}
	return $settings;
}

/** توضیح کوتاه هر دامنه پیش‌فرض برای نمایش در پنل مدیریت */
function gv_sec_domain_labels() {
	return array(
		'api.wordpress.org'         => 'بررسی بروزرسانی هسته/افزونه/قالب وردپرس',
		'downloads.wordpress.org'   => 'دانلود فایل‌های بروزرسانی و ترجمه‌ها',
		's.w.org'                   => 'ایموجی و منابع استاتیک wordpress.org',
		'ps.w.org'                  => 'تصاویر و اسکرین‌شات افزونه/قالب در صفحه نصب',
		'wordpress.org'             => 'زیرساخت عمومی wordpress.org (فید و اعلان‌ها)',
		'plugins.svn.wordpress.org' => 'مخزن SVN افزونه‌ها',
		'themes.svn.wordpress.org'  => 'مخزن SVN قالب‌ها',
		'stats.wp.com'              => 'ارسال آمار به جت‌پک / وردپرس‌دات‌کام',
		'public-api.wordpress.com'  => 'ارتباط عمومی با API وردپرس‌دات‌کام',
		'jetpack.wordpress.com'     => 'سرویس افزونه Jetpack',
		'rest.akismet.com'          => 'بررسی اسپم بودن کامنت توسط Akismet',
		'akismet.com'               => 'زیرساخت عمومی Akismet',
		'fonts.googleapis.com'      => 'درخواست سمت‌سرور به فونت گوگل',
		'fonts.gstatic.com'         => 'فایل‌های فونت گوگل',
		'www.googleapis.com'        => 'سرویس‌های عمومی گوگل (نقشه، ترجمه و ...)',
		'ajax.googleapis.com'       => 'کتابخانه‌های جاوااسکریپت گوگل',
		'google-analytics.com'      => 'ارسال آمار به گوگل آنالیتیکس از سمت سرور',
		'gravatar.com'              => 'دریافت آواتار کاربران از Gravatar',
		'secure.gravatar.com'       => 'دریافت آواتار امن از Gravatar',
	);
}

/* ==========================================================================
   ۲) منوی مدیریت (زیرمجموعه هاب گروت ویژن)
   ========================================================================== */

add_action( 'admin_menu', 'gv_sec_admin_menu' );
function gv_sec_admin_menu() {
	add_submenu_page(
		'groot-vision-hub',
		'امنیت و سرعت | Groot Vision',
		'🛡️ امنیت و سرعت',
		'manage_options',
		'gv-security-speed',
		'gv_sec_render_admin_page'
	);
}

add_action( 'admin_enqueue_scripts', 'gv_sec_admin_assets' );
function gv_sec_admin_assets( $hook ) {
	if ( strpos( $hook, 'gv-security-speed' ) === false ) { return; }
	wp_add_inline_script( 'jquery-core', "jQuery(function($){
		$('.gvsec-tab-btn').on('click', function(e){
			e.preventDefault();
			var target = $(this).data('tab');
			$('.gvsec-tab-btn').removeClass('is-active');
			$(this).addClass('is-active');
			$('.gvsec-tab-panel').removeClass('is-active').hide();
			$('#gvsec-tab-' + target).addClass('is-active').show();
		});

		$('#gvsec-apply-recommended').on('click', function(e){
			e.preventDefault();
			$('input[data-recommended=\"1\"]').prop('checked', true);
			alert('دامنه‌های پیشنهادی گروت ویژن فعال شدند. برای اعمال، دکمه ذخیره تنظیمات را بزنید.');
		});

		$('#gvsec-select-all-slow').on('click', function(e){
			e.preventDefault();
			$('.gvsec-slow-checkbox').prop('checked', true);
		});
	});" );
}

/* ---- پردازش فرم ذخیره تنظیمات ---- */

add_action( 'admin_post_gv_sec_save_settings', 'gv_sec_save_settings' );
function gv_sec_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_SEC_NONCE );

	$existing = gv_sec_get_settings();

	$blocked_defaults = array();
	foreach ( array_keys( $existing['blocked_defaults'] ) as $domain ) {
		$blocked_defaults[ $domain ] = isset( $_POST['blocked_defaults'][ $domain ] ) ? 1 : 0;
	}

	$captcha_enabled = isset( $_POST['captcha_enabled'] ) ? 1 : 0;
	$speed_enabled   = isset( $_POST['speed_enabled'] ) ? 1 : 0;

	// دامنه‌های کند تشخیص‌داده‌شده‌ای که کاربر برای افزودن به بلاک‌لیست تیک زده
	$custom_domains_text = sanitize_textarea_field( wp_unslash( $_POST['custom_domains'] ?? '' ) );
	if ( ! empty( $_POST['add_slow_hosts'] ) && is_array( $_POST['add_slow_hosts'] ) ) {
		$to_add = array_map( 'sanitize_text_field', wp_unslash( $_POST['add_slow_hosts'] ) );
		$to_add = array_map( 'strtolower', $to_add );
		$custom_domains_text = trim( $custom_domains_text . "\n" . implode( "\n", $to_add ) );
	}

	$settings = array(
		'captcha_enabled'  => $captcha_enabled,
		'captcha_comments' => isset( $_POST['captcha_comments'] ) ? 1 : 0,
		'captcha_login'    => isset( $_POST['captcha_login'] ) ? 1 : 0,
		'captcha_register' => isset( $_POST['captcha_register'] ) ? 1 : 0,

		'speed_enabled'    => $speed_enabled,
		'blocked_defaults' => $blocked_defaults,
		'custom_domains'   => $custom_domains_text,
		'disable_emojis'   => isset( $_POST['disable_emojis'] ) ? 1 : 0,

		'disable_xmlrpc'   => isset( $_POST['disable_xmlrpc'] ) ? 1 : 0,
		'remove_rsd_link'  => isset( $_POST['remove_rsd_link'] ) ? 1 : 0,
		'remove_wlw_link'  => isset( $_POST['remove_wlw_link'] ) ? 1 : 0,
		'remove_shortlink' => isset( $_POST['remove_shortlink'] ) ? 1 : 0,
		'remove_generator' => isset( $_POST['remove_generator'] ) ? 1 : 0,

		'payment_whitelist_custom' => sanitize_textarea_field( wp_unslash( $_POST['payment_whitelist_custom'] ?? '' ) ),

		'detector_enabled'   => isset( $_POST['detector_enabled'] ) ? 1 : 0,
		'detector_threshold' => isset( $_POST['detector_threshold'] ) ? max( 0.3, floatval( $_POST['detector_threshold'] ) ) : 1.5,

		'blocked_count'    => intval( $existing['blocked_count'] ), // شمارنده قبلی حفظ می‌شود
		'active'           => ( $captcha_enabled || $speed_enabled ) ? 1 : 0,
	);

	update_option( GV_SEC_OPT, $settings );
	wp_safe_redirect( admin_url( 'admin.php?page=gv-security-speed&updated=1&tab=' . ( isset( $_POST['current_tab'] ) ? sanitize_key( $_POST['current_tab'] ) : 'captcha' ) ) );
	exit;
}

/* ---- ریست شمارنده آمار ---- */
add_action( 'admin_post_gv_sec_reset_counter', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_SEC_NONCE );
	$settings = gv_sec_get_settings();
	$settings['blocked_count'] = 0;
	update_option( GV_SEC_OPT, $settings );
	wp_safe_redirect( admin_url( 'admin.php?page=gv-security-speed&tab=stats&reset=1' ) );
	exit;
} );

/* ---- پاک کردن لیست دامنه‌های کند تشخیص‌داده‌شده ---- */
add_action( 'admin_post_gv_sec_clear_slow', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_SEC_NONCE );
	delete_option( GV_SEC_SLOW_OPT );
	wp_safe_redirect( admin_url( 'admin.php?page=gv-security-speed&tab=detector&cleared=1' ) );
	exit;
} );

/* ---- صفحه مدیریت ---- */

function gv_sec_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$s         = gv_sec_get_settings();
	$labels    = gv_sec_domain_labels();
	$recommended = gv_sec_recommended_domains();
	$tab       = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'captcha';
	$slow_hosts = get_option( GV_SEC_SLOW_OPT, array() );
	if ( is_array( $slow_hosts ) ) {
		uasort( $slow_hosts, function ( $a, $b ) { return $b['count'] <=> $a['count']; } );
	}
	?>
	<div class="wrap" dir="rtl" style="font-family: Tahoma, sans-serif;">

		<style>
			.gvsec-header{display:flex;align-items:center;justify-content:space-between;background:linear-gradient(120deg,#1e3a8a,#2563eb);color:#fff;padding:22px 26px;border-radius:14px;margin:20px 0;box-shadow:0 6px 20px rgba(0,0,0,.12);}
			.gvsec-header h1{margin:0;font-size:20px;color:#fff;}
			.gvsec-header span{opacity:.85;font-size:12.5px;}
			.gvsec-tabs{display:flex;gap:6px;margin-bottom:18px;flex-wrap:wrap;}
			.gvsec-tab-btn{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:9px 18px;cursor:pointer;font-size:13px;font-family:inherit;font-weight:600;transition:.15s;}
			.gvsec-tab-btn.is-active{background:#1e3a8a;color:#fff;border-color:#1e3a8a;}
			.gvsec-tab-panel{display:none;}
			.gvsec-tab-panel.is-active{display:block;}
			.gvsec-card{background:#fff;border:1px solid #e2e4e7;border-radius:12px;padding:20px 22px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.04);max-width:820px;}
			.gvsec-card h2{margin-top:0;font-size:15px;display:flex;align-items:center;gap:8px;border-bottom:1px solid #f0f0f1;padding-bottom:10px;}
			.gvsec-hint{color:#666;font-size:12px;margin-top:6px;line-height:1.9;}
			.gvsec-toggle-row{display:flex;align-items:center;gap:8px;background:#f6f7f7;padding:10px 14px;border-radius:8px;margin-bottom:10px;}
			.gvsec-toggle-row label{margin:0;font-weight:600;font-size:13px;}
			.gvsec-domain-row{display:flex;align-items:flex-start;gap:10px;padding:9px 12px;border-radius:8px;background:#f9fafb;margin-bottom:6px;}
			.gvsec-domain-row.is-recommended{background:#ecfdf5;border:1px solid #6ee7b7;}
			.gvsec-domain-row code{font-size:12px;background:#eef2ff;color:#3730a3;padding:2px 7px;border-radius:6px;direction:ltr;display:inline-block;}
			.gvsec-domain-row span.gvsec-domain-desc{font-size:11.5px;color:#6b7280;margin-right:6px;}
			.gvsec-badge-rec{background:#059669;color:#fff;font-size:10px;padding:2px 8px;border-radius:10px;margin-right:6px;}
			.gvsec-note{background:#fff7ed;border:1px solid #fdba74;color:#9a3412;padding:12px 16px;border-radius:10px;font-size:12.5px;line-height:1.9;margin-bottom:18px;max-width:820px;}
			.gvsec-note.gvsec-note-green{background:#ecfdf5;border-color:#6ee7b7;color:#065f46;}
			.gvsec-note.gvsec-note-blue{background:#eff6ff;border-color:#93c5fd;color:#1e40af;}
			.gvsec-stat-cards{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:20px;max-width:820px;}
			.gvsec-stat{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px 20px;}
			.gvsec-stat b{display:block;font-size:26px;color:#2563eb;}
			.gvsec-stat span{font-size:13px;color:#64748b;}
			.gvsec-credit{font-size:11.5px;color:#888;text-align:center;margin-top:24px;}
			textarea.gvsec-textarea{width:100%;max-width:780px;border-radius:8px;border:1px solid #cbd5e1;padding:10px;font-family:monospace;direction:ltr;text-align:left;}
			.gvsec-btn-secondary{background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;padding:8px 16px;font-size:12.5px;cursor:pointer;font-weight:600;}
			.gvsec-slow-table{width:100%;max-width:820px;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;}
			.gvsec-slow-table th,.gvsec-slow-table td{padding:9px 12px;text-align:right;font-size:12.5px;border-bottom:1px solid #f1f5f9;}
			.gvsec-slow-table th{background:#f8fafc;color:#475569;}
			.gvsec-slow-table code{direction:ltr;display:inline-block;background:#fef2f2;color:#991b1b;padding:2px 7px;border-radius:6px;}
		</style>

		<div class="gvsec-header">
			<h1>🛡️ امنیت و سرعت — Groot Vision</h1>
			<span>کپچای ضدربات + بلاک درخواست‌های غیرضروری + محافظت دائمی درگاه پرداخت</span>
		</div>

		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>✅ تنظیمات با موفقیت ذخیره شد.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['reset'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>🔄 شمارنده آمار بازنشانی شد.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['cleared'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>🧹 لیست دامنه‌های کند پاک شد.</p></div>
		<?php endif; ?>

		<div class="gvsec-tabs">
			<button type="button" class="gvsec-tab-btn <?php echo 'captcha' === $tab ? 'is-active' : ''; ?>" data-tab="captcha">🤖 کپچای امنیتی</button>
			<button type="button" class="gvsec-tab-btn <?php echo 'speed' === $tab ? 'is-active' : ''; ?>" data-tab="speed">⚡ سرعت سایت</button>
			<button type="button" class="gvsec-tab-btn <?php echo 'payment' === $tab ? 'is-active' : ''; ?>" data-tab="payment">💳 درگاه پرداخت</button>
			<button type="button" class="gvsec-tab-btn <?php echo 'detector' === $tab ? 'is-active' : ''; ?>" data-tab="detector">🔍 تشخیص خودکار کندی</button>
			<button type="button" class="gvsec-tab-btn <?php echo 'stats' === $tab ? 'is-active' : ''; ?>" data-tab="stats">📊 آمار</button>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gv_sec_save_settings">
			<input type="hidden" name="current_tab" value="<?php echo esc_attr( $tab ); ?>">
			<?php wp_nonce_field( GV_SEC_NONCE ); ?>

			<!-- تب کپچا -->
			<div class="gvsec-tab-panel <?php echo 'captcha' === $tab ? 'is-active' : ''; ?>" id="gvsec-tab-captcha" <?php echo 'captcha' === $tab ? '' : 'style="display:none;"'; ?>>

				<div class="gvsec-note">
					💡 این کپچا فقط دو عدد تصادفی ساده جمع می‌کند (مثلاً <code style="direction:ltr;display:inline-block;">3 + 5 = ؟</code>) و روی فرم‌های <b>بومی وردپرس</b> فعال می‌شود: فرم کامنت، فرم ورود و فرم ثبت‌نام کاربر. برای فرم‌های اختصاصی (مثل ووکامرس یا Contact Form 7) نیاز به اتصال جداگانه دارد.
				</div>

				<div class="gvsec-card">
					<h2>وضعیت کلی</h2>
					<div class="gvsec-toggle-row">
						<input type="checkbox" name="captcha_enabled" <?php checked( $s['captcha_enabled'], 1 ); ?>>
						<label>فعال‌سازی کپچای ریاضی در کل سایت</label>
					</div>
				</div>

				<div class="gvsec-card">
					<h2>فرم‌هایی که کپچا در آن‌ها نمایش داده شود</h2>
					<div class="gvsec-toggle-row">
						<input type="checkbox" name="captcha_comments" <?php checked( $s['captcha_comments'], 1 ); ?>>
						<label>💬 فرم ارسال کامنت</label>
					</div>
					<div class="gvsec-toggle-row">
						<input type="checkbox" name="captcha_login" <?php checked( $s['captcha_login'], 1 ); ?>>
						<label>🔑 فرم ورود به سایت (wp-login.php)</label>
					</div>
					<div class="gvsec-toggle-row">
						<input type="checkbox" name="captcha_register" <?php checked( $s['captcha_register'], 1 ); ?>>
						<label>📝 فرم ثبت‌نام کاربر جدید</label>
					</div>
					<p class="gvsec-hint">هر عدد و پاسخ با یک کد امنیتی (هش رمزنگاری‌شده بر پایه کلید امنیتی سایت شما) به هم متصل می‌شود؛ بنابراین ربات‌ها امکان دستکاری اعداد یا حدس پاسخ را ندارند و نیازی به ذخیره‌سازی در دیتابیس یا سشن هم نیست.</p>
				</div>
			</div>

			<!-- تب سرعت -->
			<div class="gvsec-tab-panel <?php echo 'speed' === $tab ? 'is-active' : ''; ?>" id="gvsec-tab-speed" <?php echo 'speed' === $tab ? '' : 'style="display:none;"'; ?>>

				<div class="gvsec-note">
					⚡ این بخش فقط <b>درخواست‌های سرور-به-سرور</b> وردپرس (مثل بررسی خودکار بروزرسانی، ارسال آمار، بررسی اسپم) را که به‌خاطر تحریم ممکن است چندین ثانیه معطل بمانند و کل سایت را کند کنند، مسدود می‌کند. این کار به فایل‌های ظاهری سایت شما (مثل تصاویر یا فونت‌هایی که مستقیم در مرورگر کاربر لود می‌شوند) آسیبی نمی‌زند. درگاه‌های پرداخت جدا از این لیست و همیشه امن هستند (تب «درگاه پرداخت» را ببینید).
				</div>

				<div class="gvsec-card">
					<h2>وضعیت کلی</h2>
					<div class="gvsec-toggle-row">
						<input type="checkbox" name="speed_enabled" <?php checked( $s['speed_enabled'], 1 ); ?>>
						<label>فعال‌سازی مسدودسازی درخواست‌های غیرضروری</label>
					</div>
					<div class="gvsec-toggle-row">
						<input type="checkbox" name="disable_emojis" <?php checked( $s['disable_emojis'], 1 ); ?>>
						<label>غیرفعال‌سازی اسکریپت ایموجی وردپرس (کاهش بار اضافه در هر صفحه)</label>
					</div>
				</div>

				<div class="gvsec-card">
					<h2>بهینه‌سازی‌های سریع و کم‌خطر پنل مدیریت</h2>
					<p class="gvsec-hint">این موارد باعث سبک‌تر شدن هدر سایت و کاهش درخواست‌های اضافه می‌شوند و برای اکثر سایت‌ها کاملاً بی‌خطرند.</p>
					<div class="gvsec-toggle-row">
						<input type="checkbox" name="remove_generator" <?php checked( $s['remove_generator'], 1 ); ?>>
						<label>حذف تگ نسخه وردپرس از سورس سایت (generator)</label>
					</div>
					<div class="gvsec-toggle-row">
						<input type="checkbox" name="remove_rsd_link" <?php checked( $s['remove_rsd_link'], 1 ); ?>>
						<label>حذف لینک RSD از هدر سایت</label>
					</div>
					<div class="gvsec-toggle-row">
						<input type="checkbox" name="remove_wlw_link" <?php checked( $s['remove_wlw_link'], 1 ); ?>>
						<label>حذف لینک Windows Live Writer از هدر سایت</label>
					</div>
					<div class="gvsec-toggle-row">
						<input type="checkbox" name="remove_shortlink" <?php checked( $s['remove_shortlink'], 1 ); ?>>
						<label>حذف Shortlink از هدر سایت</label>
					</div>
					<div class="gvsec-toggle-row">
						<input type="checkbox" name="disable_xmlrpc" <?php checked( $s['disable_xmlrpc'], 1 ); ?>>
						<label>غیرفعال‌سازی کامل XML-RPC (اگر از اپلیکیشن موبایل وردپرس یا Jetpack استفاده نمی‌کنید، پیشنهاد می‌شود روشن کنید)</label>
					</div>
				</div>

				<div class="gvsec-card">
					<h2>دامنه‌های پیشنهادی گروت ویژن ⭐</h2>
					<p class="gvsec-hint">این دامنه‌ها زیرساخت خودِ <code style="direction:ltr;display:inline-block;">wordpress.org</code> هستند و علت اصلی کند بودن صفحاتی مثل «افزونه‌ها»، «قالب‌ها» و «بروزرسانی‌ها» در پنل مدیریت‌اند. بلاک کردن‌شان تقریباً همیشه بی‌خطر است و به‌صورت پیش‌فرض روشن شده‌اند.</p>
					<button type="button" id="gvsec-apply-recommended" class="gvsec-btn-secondary">⭐ فعال‌سازی همه پیشنهادهای گروت ویژن</button>
					<div style="margin-top:14px;">
					<?php foreach ( $s['blocked_defaults'] as $domain => $on ) :
						$is_rec = in_array( $domain, $recommended, true );
						if ( ! $is_rec ) { continue; }
						?>
						<div class="gvsec-domain-row is-recommended">
							<input type="checkbox" data-recommended="1" name="blocked_defaults[<?php echo esc_attr( $domain ); ?>]" <?php checked( $on, 1 ); ?> id="gvsec-dom-<?php echo esc_attr( $domain ); ?>">
							<label for="gvsec-dom-<?php echo esc_attr( $domain ); ?>">
								<span class="gvsec-badge-rec">پیشنهادی</span>
								<code><?php echo esc_html( $domain ); ?></code>
								<span class="gvsec-domain-desc"><?php echo esc_html( $labels[ $domain ] ?? '' ); ?></span>
							</label>
						</div>
					<?php endforeach; ?>
					</div>
				</div>

				<div class="gvsec-card">
					<h2>سایر دامنه‌های شناخته‌شده (اختیاری)</h2>
					<p class="gvsec-hint">فقط مواردی را فعال کنید که مطمئنید به آن‌ها نیاز ندارید (مثلاً اگر از افزونه Akismet یا Jetpack استفاده می‌کنید، آن‌ها را بلاک نکنید).</p>
					<?php foreach ( $s['blocked_defaults'] as $domain => $on ) :
						if ( in_array( $domain, $recommended, true ) ) { continue; }
						?>
						<div class="gvsec-domain-row">
							<input type="checkbox" name="blocked_defaults[<?php echo esc_attr( $domain ); ?>]" <?php checked( $on, 1 ); ?> id="gvsec-dom-<?php echo esc_attr( $domain ); ?>">
							<label for="gvsec-dom-<?php echo esc_attr( $domain ); ?>">
								<code><?php echo esc_html( $domain ); ?></code>
								<span class="gvsec-domain-desc"><?php echo esc_html( $labels[ $domain ] ?? '' ); ?></span>
							</label>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="gvsec-card">
					<h2>دامنه‌ها / آدرس‌های سفارشی شما</h2>
					<p class="gvsec-hint">هر خط یک دامنه یا URL. کافیست فقط دامنه اصلی را بنویسید (نیازی به http/https نیست)، اما اگر کل لینک را هم بچسبانید مشکلی نیست. مثال:<br>
					<code style="direction:ltr;display:inline-block;">example-slow-api.com</code></p>
					<textarea name="custom_domains" class="gvsec-textarea" rows="6" placeholder="example.com&#10;another-slow-service.net"><?php echo esc_textarea( $s['custom_domains'] ); ?></textarea>
					<p class="gvsec-hint">⚠️ درگاه‌های پرداخت را اینجا اضافه نکنید؛ آن‌ها را در تب «درگاه پرداخت» مدیریت کنید تا هرگز به‌اشتباه بلاک نشوند.</p>
				</div>
			</div>

			<!-- تب درگاه پرداخت -->
			<div class="gvsec-tab-panel <?php echo 'payment' === $tab ? 'is-active' : ''; ?>" id="gvsec-tab-payment" <?php echo 'payment' === $tab ? '' : 'style="display:none;"'; ?>>

				<div class="gvsec-note gvsec-note-green">
					💳 دامنه‌های درگاه پرداخت زیر <b>همیشه</b> از بلاک‌شدن مستثنی هستند؛ حتی اگر به‌اشتباه در بخش «سرعت سایت» هم اضافه شوند، این افزونه هرگز اتصال به آن‌ها را قطع نمی‌کند. این لیست شامل مهم‌ترین درگاه‌های ایرانی و جهانی است و همیشه فعال است.
				</div>

				<div class="gvsec-card">
					<h2>لیست سفید همیشگی (ثابت)</h2>
					<p class="gvsec-hint">این دامنه‌ها بخشی از هسته افزونه‌اند و قابل خاموش کردن نیستند:</p>
					<div style="display:flex;flex-wrap:wrap;gap:6px;">
						<?php foreach ( gv_sec_builtin_payment_whitelist() as $pd ) : ?>
							<code style="font-size:11.5px;background:#ecfdf5;color:#065f46;padding:3px 9px;border-radius:6px;direction:ltr;display:inline-block;"><?php echo esc_html( $pd ); ?></code>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="gvsec-card">
					<h2>درگاه‌های پرداخت یا وب‌سرویس‌های اضافه شما</h2>
					<p class="gvsec-hint">اگر از درگاه پرداخت دیگری استفاده می‌کنید (یا هر وب‌سرویسی که قطعاً نباید بلاک شود، مثل سرویس پیامک یا احراز هویت بانکی)، دامنه‌اش را اینجا اضافه کنید — هر خط یک دامنه.</p>
					<textarea name="payment_whitelist_custom" class="gvsec-textarea" rows="5" placeholder="mypaymentgateway.com"><?php echo esc_textarea( $s['payment_whitelist_custom'] ); ?></textarea>
				</div>
			</div>

			<!-- تب تشخیص خودکار -->
			<div class="gvsec-tab-panel <?php echo 'detector' === $tab ? 'is-active' : ''; ?>" id="gvsec-tab-detector" <?php echo 'detector' === $tab ? '' : 'style="display:none;"'; ?>>

				<div class="gvsec-note gvsec-note-blue">
					🔍 این ابزار درخواست‌های خروجی سایت را که بیشتر از حد آستانه طول می‌کشند، شناسایی و در جدول زیر لیست می‌کند. اگر پنل مدیریت هنوز کند است، احتمالاً دامنه مقصر همین‌جا نشان داده می‌شود؛ می‌توانید با یک کلیک آن را به لیست بلاک اضافه کنید (به‌جز درگاه‌های پرداخت که هرگز نشان داده یا بلاک نمی‌شوند).
				</div>

				<div class="gvsec-card">
					<h2>تنظیمات تشخیص</h2>
					<div class="gvsec-toggle-row">
						<input type="checkbox" name="detector_enabled" <?php checked( $s['detector_enabled'], 1 ); ?>>
						<label>فعال‌سازی تشخیص خودکار دامنه‌های کند</label>
					</div>
					<p class="gvsec-hint" style="margin-top:4px;">آستانه زمانی (ثانیه) — درخواست‌هایی کندتر از این مقدار ثبت می‌شوند:</p>
					<input type="number" step="0.1" min="0.3" name="detector_threshold" value="<?php echo esc_attr( $s['detector_threshold'] ); ?>" style="width:100px;padding:6px 8px;border-radius:6px;border:1px solid #cbd5e1;">
				</div>

				<div class="gvsec-card">
					<h2>دامنه‌های کند شناسایی‌شده</h2>
					<?php if ( empty( $slow_hosts ) ) : ?>
						<p class="gvsec-hint">هنوز موردی ثبت نشده. کمی به سایت سر بزنید و پنل مدیریت را باز کنید تا داده جمع‌آوری شود.</p>
					<?php else : ?>
						<button type="button" id="gvsec-select-all-slow" class="gvsec-btn-secondary" style="margin-bottom:10px;">انتخاب همه</button>
						<table class="gvsec-slow-table">
							<thead>
								<tr>
									<th></th>
									<th>دامنه</th>
									<th>تعداد دفعات کند بودن</th>
									<th>کندترین زمان (ثانیه)</th>
									<th>آخرین بار</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ( $slow_hosts as $host => $info ) : ?>
								<tr>
									<td><input type="checkbox" class="gvsec-slow-checkbox" name="add_slow_hosts[]" value="<?php echo esc_attr( $host ); ?>"></td>
									<td><code><?php echo esc_html( $host ); ?></code></td>
									<td><?php echo esc_html( number_format_i18n( $info['count'] ) ); ?></td>
									<td><?php echo esc_html( number_format( $info['max'], 2 ) ); ?></td>
									<td><?php echo esc_html( human_time_diff( $info['last'] ) . ' پیش' ); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
						<p class="gvsec-hint">با تیک زدن دامنه(ها) و زدن دکمه «ذخیره تنظیمات» پایین صفحه، به لیست دامنه‌های سفارشی بخش سرعت اضافه می‌شوند.</p>
					<?php endif; ?>
				</div>
			</div>

			<?php submit_button( '💾 ذخیره تنظیمات' ); ?>
		</form>

		<?php if ( 'detector' === $tab && ! empty( $slow_hosts ) ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:820px;">
				<input type="hidden" name="action" value="gv_sec_clear_slow">
				<?php wp_nonce_field( GV_SEC_NONCE ); ?>
				<button type="submit" class="button">🧹 پاک کردن کامل لیست تشخیص</button>
			</form>
		<?php endif; ?>

		<!-- تب آمار -->
		<div class="gvsec-tab-panel <?php echo 'stats' === $tab ? 'is-active' : ''; ?>" id="gvsec-tab-stats" <?php echo 'stats' === $tab ? '' : 'style="display:none;"'; ?>>
			<div class="gvsec-stat-cards">
				<div class="gvsec-stat"><b><?php echo esc_html( number_format_i18n( $s['blocked_count'] ) ); ?></b><span>درخواست مسدودشده تاکنون</span></div>
				<div class="gvsec-stat"><b><?php echo esc_html( array_sum( $s['blocked_defaults'] ) + count( gv_sec_get_custom_domains( $s ) ) ); ?></b><span>دامنه در لیست بلاک فعال</span></div>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="gv_sec_reset_counter">
				<?php wp_nonce_field( GV_SEC_NONCE ); ?>
				<button type="submit" class="button">🔄 بازنشانی شمارنده</button>
			</form>
		</div>

		<p class="gvsec-credit">ساخته و توسعه‌یافته توسط <strong>Groot Vision</strong> | اینستاگرام: grootvision</p>
	</div>
	<?php
}

/* ==========================================================================
   ۳) توابع کمکی مسدودسازی درخواست‌ها
   ========================================================================== */

function gv_sec_normalize_host( $line ) {
	$line = trim( $line );
	if ( '' === $line ) { return ''; }
	if ( false === strpos( $line, '://' ) ) {
		$line = 'https://' . $line;
	}
	$host = wp_parse_url( $line, PHP_URL_HOST );
	return $host ? strtolower( $host ) : '';
}

function gv_sec_get_custom_domains( $settings ) {
	$raw   = isset( $settings['custom_domains'] ) ? $settings['custom_domains'] : '';
	$lines = preg_split( '/[\r\n]+/', $raw );
	$out   = array();
	foreach ( $lines as $line ) {
		$host = gv_sec_normalize_host( $line );
		if ( $host ) { $out[] = $host; }
	}
	return array_unique( $out );
}

function gv_sec_host_matches( $host, $pattern ) {
	$host    = strtolower( $host );
	$pattern = strtolower( $pattern );
	if ( '' === $pattern ) { return false; }
	if ( $host === $pattern ) { return true; }
	return ( strlen( $host ) > strlen( $pattern ) && substr( $host, -strlen( $pattern ) - 1 ) === '.' . $pattern );
}

function gv_sec_bump_blocked_count() {
	$settings = get_option( GV_SEC_OPT, array() );
	$count    = isset( $settings['blocked_count'] ) ? intval( $settings['blocked_count'] ) : 0;
	$settings['blocked_count'] = $count + 1;
	update_option( GV_SEC_OPT, $settings, false );
}

/**
 * لیست سفید ثابت درگاه‌های پرداخت — این لیست هرگز از تنظیمات کاربر خوانده
 * نمی‌شود و همیشه، حتی اگر بخش سرعت خاموش هم نباشد، معتبر است.
 */
function gv_sec_builtin_payment_whitelist() {
	return array(
		// درگاه‌های پرداخت ایرانی
		'zarinpal.com', 'www.zarinpal.com', 'sandbox.zarinpal.com', 'staging.zarinpal.com',
		'nextpay.org', 'idpay.ir', 'api.idpay.ir',
		'zibal.ir', 'gateway.zibal.ir',
		'sep.shaparak.ir', 'sadad.shaparak.ir', 'pep.shaparak.ir', 'asan.shaparak.ir',
		'mellat.shaparak.ir', 'saman.shaparak.ir', 'sepehr.shaparak.ir', 'parsian.shaparak.ir',
		'pasargad.shaparak.ir', 'ipg.shaparak.ir', 'fanava.shaparak.ir',
		'pay.ir', 'api.pay.ir',
		'payline.ir', 'snappay.ir',
		'jibit.ir', 'api.jibit.ir',
		'vandar.io', 'api.vandar.io',
		'digipay.ir', 'behpardakht.com',
		// درگاه‌های جهانی
		'api.stripe.com', 'checkout.stripe.com', 'js.stripe.com',
		'www.paypal.com', 'api.paypal.com', 'api-m.paypal.com', 'api-m.sandbox.paypal.com',
	);
}

function gv_sec_get_payment_whitelist( $settings ) {
	$builtin = gv_sec_builtin_payment_whitelist();
	$custom  = array();
	$raw     = isset( $settings['payment_whitelist_custom'] ) ? $settings['payment_whitelist_custom'] : '';
	foreach ( preg_split( '/[\r\n]+/', $raw ) as $line ) {
		$host = gv_sec_normalize_host( $line );
		if ( $host ) { $custom[] = $host; }
	}
	return array_unique( array_merge( $builtin, $custom ) );
}

function gv_sec_is_payment_whitelisted( $host, $settings ) {
	foreach ( gv_sec_get_payment_whitelist( $settings ) as $pattern ) {
		if ( gv_sec_host_matches( $host, $pattern ) ) { return true; }
	}
	return false;
}

/* ---- قلب سیستم: مسدودسازی سریع درخواست‌های سمت سرور ---- */
add_filter( 'pre_http_request', 'gv_sec_maybe_block_request', 5, 3 );
function gv_sec_maybe_block_request( $preempt, $parsed_args, $url ) {
	if ( false !== $preempt ) { return $preempt; } // یک افزونه دیگر قبلاً پاسخ داده

	$settings = gv_sec_get_settings();

	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( ! $host ) { return $preempt; }

	// درگاه‌های پرداخت همیشه مستثنی هستند، فارغ از روشن یا خاموش بودن بخش سرعت
	if ( gv_sec_is_payment_whitelisted( $host, $settings ) ) { return $preempt; }

	if ( empty( $settings['speed_enabled'] ) ) { return $preempt; }

	$blocklist = array();
	foreach ( $settings['blocked_defaults'] as $domain => $on ) {
		if ( $on ) { $blocklist[] = $domain; }
	}
	foreach ( gv_sec_get_custom_domains( $settings ) as $domain ) {
		$blocklist[] = $domain;
	}

	foreach ( $blocklist as $pattern ) {
		if ( gv_sec_host_matches( $host, $pattern ) ) {
			gv_sec_bump_blocked_count();
			return new WP_Error(
				'gv_request_blocked',
				sprintf( 'درخواست به %s توسط افزونه «امنیت و سرعت گروت ویژن» مسدود شد.', $host )
			);
		}
	}
	return $preempt;
}

/* ---- غیرفعال‌سازی ایموجی وردپرس برای کاهش بار اضافه ---- */
add_action( 'init', 'gv_sec_maybe_disable_emojis' );
function gv_sec_maybe_disable_emojis() {
	$settings = gv_sec_get_settings();
	if ( empty( $settings['disable_emojis'] ) ) { return; }

	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

	add_filter( 'tiny_mce_plugins', function ( $plugins ) {
		return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
	} );

	add_filter( 'wp_resource_hints', function ( $urls, $relation_type ) {
		if ( 'dns-prefetch' === $relation_type ) {
			$urls = array_filter( (array) $urls, function ( $u ) {
				return false === strpos( $u, 's.w.org' );
			} );
		}
		return $urls;
	}, 10, 2 );
}

/* ---- بهینه‌سازی‌های سریع و کم‌خطر ---- */
add_action( 'init', 'gv_sec_apply_quick_optimizations' );
function gv_sec_apply_quick_optimizations() {
	$settings = gv_sec_get_settings();

	if ( ! empty( $settings['remove_generator'] ) ) {
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
	}
	if ( ! empty( $settings['remove_rsd_link'] ) ) {
		remove_action( 'wp_head', 'rsd_link' );
	}
	if ( ! empty( $settings['remove_wlw_link'] ) ) {
		remove_action( 'wp_head', 'wlwmanifest_link' );
	}
	if ( ! empty( $settings['remove_shortlink'] ) ) {
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
	}
	if ( ! empty( $settings['disable_xmlrpc'] ) ) {
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'wp_headers', function ( $headers ) {
			unset( $headers['X-Pingback'] );
			return $headers;
		} );
		remove_action( 'wp_head', 'rsd_link' );
	}
}

/* ==========================================================================
   ۴) تشخیص خودکار دامنه‌های کند (برای پیشنهاد بلاک به کاربر)
   ========================================================================== */

add_filter( 'http_request_args', 'gv_sec_mark_request_start', 5, 2 );
function gv_sec_mark_request_start( $args, $url ) {
	if ( ! isset( $GLOBALS['gv_sec_req_start'] ) ) {
		$GLOBALS['gv_sec_req_start'] = array();
	}
	$GLOBALS['gv_sec_req_start'][ $url ] = microtime( true );
	return $args;
}

add_action( 'http_api_debug', 'gv_sec_log_slow_request', 10, 5 );
function gv_sec_log_slow_request( $response, $context, $class, $args, $url ) {
	$settings = gv_sec_get_settings();
	if ( empty( $settings['detector_enabled'] ) ) { return; }

	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( ! $host ) { return; }

	// درگاه‌های پرداخت را در لیست تشخیص نشان نمی‌دهیم تا کسی به‌اشتباه بلاکشان نکند
	if ( gv_sec_is_payment_whitelisted( $host, $settings ) ) { return; }

	if ( empty( $GLOBALS['gv_sec_req_start'][ $url ] ) ) { return; }
	$elapsed = microtime( true ) - $GLOBALS['gv_sec_req_start'][ $url ];
	unset( $GLOBALS['gv_sec_req_start'][ $url ] );

	$threshold = isset( $settings['detector_threshold'] ) ? floatval( $settings['detector_threshold'] ) : 1.5;
	if ( $elapsed < $threshold ) { return; }

	gv_sec_record_slow_host( $host, $elapsed );
}

function gv_sec_record_slow_host( $host, $elapsed ) {
	$hosts = get_option( GV_SEC_SLOW_OPT, array() );
	if ( ! is_array( $hosts ) ) { $hosts = array(); }

	if ( ! isset( $hosts[ $host ] ) ) {
		$hosts[ $host ] = array( 'count' => 0, 'max' => 0, 'last' => time() );
	}
	$hosts[ $host ]['count']++;
	$hosts[ $host ]['max']  = max( $hosts[ $host ]['max'], $elapsed );
	$hosts[ $host ]['last'] = time();

	// حداکثر ۴۰ دامنه ذخیره می‌شود، در صورت پر شدن، کم‌تکرارترین حذف می‌شود
	if ( count( $hosts ) > 40 ) {
		uasort( $hosts, function ( $a, $b ) { return $a['count'] <=> $b['count']; } );
		$hosts = array_slice( $hosts, -40, 40, true );
	}

	update_option( GV_SEC_SLOW_OPT, $hosts, false );
}

/* ==========================================================================
   ۵) کپچای ریاضی — تولید، نمایش و اعتبارسنجی
   ========================================================================== */

function gv_cap_generate_pair() {
	return array( wp_rand( 1, 9 ), wp_rand( 1, 9 ) );
}

function gv_cap_make_token( $a, $b ) {
	return wp_hash( 'gvcap|' . $a . '|' . $b . '|' . wp_salt( 'nonce' ) );
}

function gv_cap_render_field( $context = 'default' ) {
	list( $a, $b ) = gv_cap_generate_pair();
	$token = gv_cap_make_token( $a, $b );
	?>
	<p class="gv-captcha-wrap" style="margin:14px 0;">
		<label for="gv_captcha_answer_<?php echo esc_attr( $context ); ?>" style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;">
			🤖 برای اثبات انسان بودن، حاصل جمع زیر را وارد کنید:
		</label>
		<span style="display:inline-flex;align-items:center;gap:8px;direction:ltr;">
			<strong style="font-size:16px;"><?php echo esc_html( $a ); ?> + <?php echo esc_html( $b ); ?> =</strong>
			<input type="text" inputmode="numeric" autocomplete="off"
				id="gv_captcha_answer_<?php echo esc_attr( $context ); ?>"
				name="gv_captcha_answer" required
				style="width:70px;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;">
		</span>
		<input type="hidden" name="gv_captcha_a" value="<?php echo esc_attr( $a ); ?>">
		<input type="hidden" name="gv_captcha_b" value="<?php echo esc_attr( $b ); ?>">
		<input type="hidden" name="gv_captcha_token" value="<?php echo esc_attr( $token ); ?>">
	</p>
	<?php
}

function gv_cap_verify_posted() {
	if ( ! isset( $_POST['gv_captcha_a'], $_POST['gv_captcha_b'], $_POST['gv_captcha_token'], $_POST['gv_captcha_answer'] ) ) {
		return false;
	}
	$a      = intval( $_POST['gv_captcha_a'] );
	$b      = intval( $_POST['gv_captcha_b'] );
	$token  = sanitize_text_field( wp_unslash( $_POST['gv_captcha_token'] ) );
	$answer = intval( $_POST['gv_captcha_answer'] );

	$expected_token = gv_cap_make_token( $a, $b );
	if ( ! hash_equals( $expected_token, $token ) ) { return false; }

	return ( $answer === ( $a + $b ) );
}

/* ---- فرم کامنت ---- */
add_action( 'comment_form_after_fields', 'gv_cap_output_comment_field' );     // کاربران مهمان
add_action( 'comment_form_logged_in_after', 'gv_cap_output_comment_field' );  // کاربران عضو
function gv_cap_output_comment_field() {
	$s = gv_sec_get_settings();
	if ( empty( $s['captcha_enabled'] ) || empty( $s['captcha_comments'] ) ) { return; }
	gv_cap_render_field( 'comment' );
}

add_filter( 'preprocess_comment', 'gv_cap_validate_comment' );
function gv_cap_validate_comment( $commentdata ) {
	$s = gv_sec_get_settings();
	if ( empty( $s['captcha_enabled'] ) || empty( $s['captcha_comments'] ) ) { return $commentdata; }

	if ( ! gv_cap_verify_posted() ) {
		wp_die(
			'پاسخ کپچا نادرست است. لطفاً به صفحه قبل برگردید و دوباره تلاش کنید.',
			'خطای کپچا',
			array( 'response' => 403, 'back_link' => true )
		);
	}
	return $commentdata;
}

/* ---- فرم ورود ---- */
add_action( 'login_form', 'gv_cap_output_login_field' );
function gv_cap_output_login_field() {
	$s = gv_sec_get_settings();
	if ( empty( $s['captcha_enabled'] ) || empty( $s['captcha_login'] ) ) { return; }
	gv_cap_render_field( 'login' );
}

add_filter( 'wp_authenticate_user', 'gv_cap_validate_login', 30, 2 );
function gv_cap_validate_login( $user, $password ) {
	if ( is_wp_error( $user ) ) { return $user; }
	$s = gv_sec_get_settings();
	if ( empty( $s['captcha_enabled'] ) || empty( $s['captcha_login'] ) ) { return $user; }

	if ( ! gv_cap_verify_posted() ) {
		return new WP_Error( 'gv_captcha_failed', '<strong>خطا:</strong> پاسخ کپچا نادرست است.' );
	}
	return $user;
}

/* ---- فرم ثبت‌نام ---- */
add_action( 'register_form', 'gv_cap_output_register_field' );
function gv_cap_output_register_field() {
	$s = gv_sec_get_settings();
	if ( empty( $s['captcha_enabled'] ) || empty( $s['captcha_register'] ) ) { return; }
	gv_cap_render_field( 'register' );
}

add_filter( 'registration_errors', 'gv_cap_validate_register', 10, 3 );
function gv_cap_validate_register( $errors, $sanitized_user_login, $user_email ) {
	$s = gv_sec_get_settings();
	if ( empty( $s['captcha_enabled'] ) || empty( $s['captcha_register'] ) ) { return $errors; }

	if ( ! gv_cap_verify_posted() ) {
		$errors->add( 'gv_captcha_failed', '<strong>خطا:</strong> پاسخ کپچا نادرست است.' );
	}
	return $errors;
}