<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * ==========================================================
 *  Groot Vision — حالت تعمیر و نگهداری سایت (نسخه ۲)
 *  ------------------------------------------------------------
 *  تغییرات نسبت به نسخه قبل:
 *   ۱) استایل صفحه‌ی تنظیمات هماهنگ با بقیه‌ی افزونه‌های
 *      Groot Vision شد (هدر سبز گرادیانی + کارت‌های سفید).
 *   ۲) به‌جای منوی جداگانه، زیرمنوی هاب گروت ویژن شد.
 *   ۳) امکانات جدید:
 *      - سه پیش‌فرض رنگی آماده + رنگ‌بندی کاملاً دستی
 *      - زمان‌بندی خودکار (شروع/پایان تعمیر بدون نیاز به
 *        روشن/خاموش کردن دستی)
 *      - لیست سفید IP برای مشاهده‌ی سایت توسط تیم فنی
 *        در حین فعال بودن حالت تعمیر
 *      - لینک پیش‌نمایش اختصاصی برای مشتری (بدون نیاز به لاگین)
 *      - فرم «اطلاع‌رسانی ایمیلی هنگام بازگشایی» + خروجی CSV
 *      - فیلد CSS اختصاصی برای شخصی‌سازی بیشتر
 * ==========================================================
 */

define( 'WPMC_OPT', 'wpmc_options' );
define( 'WPMC_SUBS_OPT', 'wpmc_subscribers' );
define( 'WPMC_NONCE', 'wpmc_save_action' );

/* ==========================================================================
   1) پیش‌فرض‌ها و پریست‌های رنگی
   ========================================================================== */
function wpmc_color_presets() {
	return array(
		'emerald' => array(
			'label' => 'زمردی گروت ویژن (هماهنگ با پیشخوان)',
			'color_primary'   => '#4ade80',
			'color_secondary' => '#facc15',
			'color_accent'    => '#22c55e',
			'color_bg1'       => '#0b1f26',
			'color_bg2'       => '#0e4037',
		),
		'astrolabe' => array(
			'label' => 'اسطرلاب (طلایی/فیروزه‌ای/یاقوتی)',
			'color_primary'   => '#D4AF6A',
			'color_secondary' => '#2FB8B0',
			'color_accent'    => '#B23A48',
			'color_bg1'       => '#0B1220',
			'color_bg2'       => '#2A123B',
		),
		'ocean' => array(
			'label' => 'اقیانوسی (آبی/فیروزه‌ای)',
			'color_primary'   => '#38bdf8',
			'color_secondary' => '#2dd4bf',
			'color_accent'    => '#6366f1',
			'color_bg1'       => '#050b18',
			'color_bg2'       => '#0f2340',
		),
	);
}

function wpmc_default_options() {
	$preset = wpmc_color_presets()['emerald'];
	return array_merge( array(
		'enabled'          => 0,
		'title'            => 'در حال ساخته‌شدنیم!',
		'description'      => 'داریم یه چیز خفن‌تر می‌سازیم 🌟 یکم دیگه صبر کن، به‌زودی با ظاهری جدید و تجربه‌ای بهتر برمی‌گردیم.',
		'badge_text'       => 'در حال ساخت',
		'show_timer'       => 1,
		'end_datetime'     => '',
		'timer_label'      => 'شمارش معکوس تا بازگشایی',
		'timer_sentence'   => 'سایت تا {days} روز و {hours} ساعت و {minutes} دقیقه دیگر دوباره باز می‌شود!',
		'progress_label'   => 'پیشرفت بروزرسانی',
		'progress_percent' => 70,
		'feature1'         => 'امن و مطمئن',
		'feature2'         => 'سریع‌تر از قبل',
		'feature3'         => 'ظاهری تازه',
		'footer_text'      => 'در حال بروزرسانی سایت هستیم',
		'color_preset'     => 'emerald',

		// امکانات جدید
		'schedule_mode'    => 'manual', // manual | auto
		'start_datetime'   => '',
		'ip_whitelist'     => '',
		'preview_token'    => '',
		'email_capture'    => 1,
		'custom_css'       => '',
		'seo_noindex'      => 1,
	), $preset );
}

function wpmc_get_options() {
	$opts = get_option( WPMC_OPT, array() );
	return wp_parse_args( $opts, wpmc_default_options() );
}

/* ==========================================================================
   2) ثبت زیرمنو در هاب گروت ویژن
   ========================================================================== */
add_action( 'admin_menu', 'wpmc_add_admin_menu' );
function wpmc_add_admin_menu() {
	add_submenu_page(
		'groot-vision-hub',
		'حالت تعمیر سایت | Groot Vision',
		'🛠️ حالت تعمیر',
		'manage_options',
		'wpmc-maintenance',
		'wpmc_render_admin_page'
	);
}

add_action( 'admin_enqueue_scripts', 'wpmc_admin_assets' );
function wpmc_admin_assets( $hook ) {
	if ( strpos( $hook, 'wpmc-maintenance' ) === false ) return;
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_enqueue_media();
	wp_enqueue_script( 'jquery' );
}

/* ==========================================================================
   3) پردازش ذخیره تنظیمات + خروجی CSV مشترکین
   ========================================================================== */
add_action( 'admin_post_wpmc_export_subscribers', 'wpmc_export_subscribers' );
function wpmc_export_subscribers() {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( 'دسترسی ندارید.' );
	check_admin_referer( WPMC_NONCE );

	$subs = get_option( WPMC_SUBS_OPT, array() );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=maintenance-subscribers.csv' );
	echo "\xEF\xBB\xBF"; // BOM برای نمایش صحیح فارسی در اکسل
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'ایمیل', 'تاریخ ثبت' ) );
	foreach ( $subs as $row ) {
		fputcsv( $out, array( $row['email'], $row['date'] ) );
	}
	fclose( $out );
	exit;
}

add_action( 'admin_post_wpmc_clear_subscribers', 'wpmc_clear_subscribers' );
function wpmc_clear_subscribers() {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( 'دسترسی ندارید.' );
	check_admin_referer( WPMC_NONCE );
	update_option( WPMC_SUBS_OPT, array() );
	wp_safe_redirect( admin_url( 'admin.php?page=wpmc-maintenance&cleared=1' ) );
	exit;
}

/** ثبت ایمیل بازدیدکننده از فرم «اطلاع‌رسانی هنگام بازگشایی» */
add_action( 'admin_post_nopriv_wpmc_subscribe', 'wpmc_handle_subscribe' );
add_action( 'admin_post_wpmc_subscribe', 'wpmc_handle_subscribe' );
function wpmc_handle_subscribe() {
	$email = sanitize_email( wp_unslash( $_POST['wpmc_email'] ?? '' ) );
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

	if ( is_email( $email ) && isset( $_POST['wpmc_subscribe_nonce'] ) && wp_verify_nonce( $_POST['wpmc_subscribe_nonce'], 'wpmc_subscribe' ) ) {
		$subs = get_option( WPMC_SUBS_OPT, array() );
		$exists = false;
		foreach ( $subs as $row ) { if ( strtolower( $row['email'] ) === strtolower( $email ) ) { $exists = true; break; } }
		if ( ! $exists ) {
			$subs[] = array( 'email' => $email, 'date' => current_time( 'mysql' ) );
			if ( count( $subs ) > 10000 ) { $subs = array_slice( $subs, -10000 ); }
			update_option( WPMC_SUBS_OPT, $subs );
		}
		$redirect = add_query_arg( 'wpmc_sub', 'ok', $redirect );
	} else {
		$redirect = add_query_arg( 'wpmc_sub', 'err', $redirect );
	}
	wp_safe_redirect( $redirect );
	exit;
}

function wpmc_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;

	$date_error = false;
	$notice = '';

	if ( isset( $_POST['wpmc_save'] ) && check_admin_referer( WPMC_NONCE, 'wpmc_nonce' ) ) {
		$d = wpmc_default_options();

		$raw_date = sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) );
		$raw_time = sanitize_text_field( wp_unslash( $_POST['end_time'] ?? '' ) );
		$raw_datetime = '';
		if ( $raw_date !== '' ) {
			$raw_datetime = $raw_date . 'T' . ( $raw_time !== '' ? $raw_time : '00:00' );
			if ( strtotime( $raw_datetime ) === false ) { $date_error = true; $raw_datetime = ''; }
		} elseif ( $raw_time !== '' ) {
			$date_error = true;
		}

		$start_date = sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) );
		$start_time = sanitize_text_field( wp_unslash( $_POST['start_time'] ?? '' ) );
		$raw_start = '';
		if ( $start_date !== '' ) {
			$raw_start = $start_date . 'T' . ( $start_time !== '' ? $start_time : '00:00' );
			if ( strtotime( $raw_start ) === false ) { $raw_start = ''; }
		}

		// لیست سفید IP: هر خط یک آی‌پی، پاکسازی و اعتبارسنجی
		$ip_lines = preg_split( '/[\r\n]+/', wp_unslash( $_POST['ip_whitelist'] ?? '' ) );
		$ip_clean = array();
		foreach ( $ip_lines as $ip ) {
			$ip = trim( $ip );
			if ( $ip !== '' && ( filter_var( $ip, FILTER_VALIDATE_IP ) || $ip === '' ) ) { $ip_clean[] = $ip; }
		}

		$color_preset = sanitize_key( $_POST['color_preset'] ?? 'emerald' );
		$presets = wpmc_color_presets();
		if ( 'custom' === $color_preset ) {
			$colors = array(
				'color_primary'   => sanitize_hex_color( $_POST['color_primary'] ?? '' ) ?: $d['color_primary'],
				'color_secondary' => sanitize_hex_color( $_POST['color_secondary'] ?? '' ) ?: $d['color_secondary'],
				'color_accent'    => sanitize_hex_color( $_POST['color_accent'] ?? '' ) ?: $d['color_accent'],
				'color_bg1'       => sanitize_hex_color( $_POST['color_bg1'] ?? '' ) ?: $d['color_bg1'],
				'color_bg2'       => sanitize_hex_color( $_POST['color_bg2'] ?? '' ) ?: $d['color_bg2'],
			);
		} elseif ( isset( $presets[ $color_preset ] ) ) {
			$colors = $presets[ $color_preset ];
			unset( $colors['label'] );
		} else {
			$color_preset = 'emerald';
			$colors = $presets['emerald'];
			unset( $colors['label'] );
		}

		$new = array_merge( array(
			'enabled'          => isset( $_POST['enabled'] ) ? 1 : 0,
			'show_timer'       => isset( $_POST['show_timer'] ) ? 1 : 0,
			'title'            => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
			'description'      => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
			'badge_text'       => sanitize_text_field( wp_unslash( $_POST['badge_text'] ?? '' ) ),
			'end_datetime'     => $raw_datetime,
			'timer_label'      => sanitize_text_field( wp_unslash( $_POST['timer_label'] ?? '' ) ),
			'timer_sentence'   => sanitize_text_field( wp_unslash( $_POST['timer_sentence'] ?? '' ) ),
			'progress_label'   => sanitize_text_field( wp_unslash( $_POST['progress_label'] ?? '' ) ),
			'progress_percent' => max( 0, min( 100, intval( $_POST['progress_percent'] ?? 70 ) ) ),
			'feature1'         => sanitize_text_field( wp_unslash( $_POST['feature1'] ?? '' ) ),
			'feature2'         => sanitize_text_field( wp_unslash( $_POST['feature2'] ?? '' ) ),
			'feature3'         => sanitize_text_field( wp_unslash( $_POST['feature3'] ?? '' ) ),
			'footer_text'      => sanitize_text_field( wp_unslash( $_POST['footer_text'] ?? '' ) ),
			'color_preset'     => $color_preset,

			'schedule_mode'    => ( ( $_POST['schedule_mode'] ?? 'manual' ) === 'auto' ) ? 'auto' : 'manual',
			'start_datetime'   => $raw_start,
			'ip_whitelist'     => implode( "\n", $ip_clean ),
			'email_capture'    => isset( $_POST['email_capture'] ) ? 1 : 0,
			'custom_css'       => wp_strip_all_tags( wp_unslash( $_POST['custom_css'] ?? '' ) ),
			'seo_noindex'      => isset( $_POST['seo_noindex'] ) ? 1 : 0,
		), $colors );

		// توکن پیش‌نمایش دائمی است؛ فقط اگر خالی بود یا کاربر دکمه‌ی «ساخت لینک جدید» را زده، بازسازی شود
		$existing = wpmc_get_options();
		$new['preview_token'] = $existing['preview_token'];
		if ( empty( $new['preview_token'] ) || isset( $_POST['wpmc_regen_token'] ) ) {
			$new['preview_token'] = wp_generate_password( 20, false, false );
		}

		update_option( WPMC_OPT, $new );

		if ( $date_error ) {
			$notice = '<div class="notice notice-error is-dismissible"><p>⚠️ تاریخ/ساعتی که وارد کردید قابل ثبت نبود، بنابراین ذخیره نشد. لطفاً هم باکس تاریخ و هم باکس ساعت را از روی انتخابگر مرورگر پر کنید.</p></div>';
		} else {
			$notice = '<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت ذخیره شد ✅</p></div>';
		}
	}

	if ( isset( $_GET['cleared'] ) ) { $notice = '<div class="notice notice-success is-dismissible"><p>لیست مشترکین پاک شد.</p></div>'; }

	$o = wpmc_get_options();
	$presets = wpmc_color_presets();
	$subs_count = count( get_option( WPMC_SUBS_OPT, array() ) );

	$end_date_val = ''; $end_time_val = '';
	if ( ! empty( $o['end_datetime'] ) && strpos( $o['end_datetime'], 'T' ) !== false ) {
		list( $end_date_val, $end_time_val ) = explode( 'T', $o['end_datetime'], 2 );
	}
	$start_date_val = ''; $start_time_val = '';
	if ( ! empty( $o['start_datetime'] ) && strpos( $o['start_datetime'], 'T' ) !== false ) {
		list( $start_date_val, $start_time_val ) = explode( 'T', $o['start_datetime'], 2 );
	}

	$preview_url = ! empty( $o['preview_token'] ) ? add_query_arg( 'gv_preview', $o['preview_token'], home_url( '/' ) ) : '';
	$status_badge = wpmc_is_active_now( $o )
		? '<span class="wpmc-status-pill on">🟢 هم‌اکنون فعال است</span>'
		: '<span class="wpmc-status-pill off">⚪ هم‌اکنون غیرفعال است</span>';
	?>
	<div class="wrap" dir="rtl" style="font-family:'Vazirmatn',Tahoma,sans-serif; max-width:1000px;">
		<style>
			.wpmc-header{background:linear-gradient(120deg,#0e4037,#145c4d);color:#fff;padding:26px 30px;border-radius:16px;margin:20px 0 24px;box-shadow:0 10px 30px rgba(14,64,55,.3);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;}
			.wpmc-header h1{margin:0;font-size:22px;color:#fff;}
			.wpmc-header p{margin:8px 0 0;font-size:13px;color:#cbd5e1;}
			.wpmc-status-pill{padding:7px 16px;border-radius:20px;font-size:12.5px;font-weight:700;}
			.wpmc-status-pill.on{background:rgba(74,222,128,.15);border:1px solid #4ade80;color:#4ade80;}
			.wpmc-status-pill.off{background:rgba(148,163,184,.18);border:1px solid #94a3b8;color:#cbd5e1;}
			.wpmc-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,.03);}
			.wpmc-card h2{margin-top:0;font-size:16px;color:#0f172a;}
			.wpmc-card .description{color:#94a3b8;}
			.wpmc-preset-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px;}
			.wpmc-preset{border:2px solid #e2e8f0;border-radius:12px;padding:12px;cursor:pointer;text-align:center;}
			.wpmc-preset.active{border-color:#0e4037;box-shadow:0 0 0 2px rgba(14,64,55,.1);}
			.wpmc-preset-swatch{height:34px;border-radius:8px;margin-bottom:8px;}
			.wpmc-preset span{font-size:12px;font-weight:600;color:#334155;}
			.wpmc-copybox{display:flex;gap:8px;align-items:center;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-family:monospace;direction:ltr;text-align:left;font-size:12.5px;overflow-x:auto;}
			.wpmc-btn-mini{background:#0e4037;color:#fff !important;border:none;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;}
			@media(max-width:782px){ .wpmc-preset-grid{ grid-template-columns:1fr; } }
		</style>

		<div class="wpmc-header">
			<div>
				<h1>🛠️ حالت تعمیر و نگهداری سایت</h1>
				<p>صفحه تعمیر سایت را فعال/غیرفعال، شخصی‌سازی و زمان‌بندی کنید.</p>
			</div>
			<?php echo $status_badge; ?>
		</div>

		<?php echo $notice; ?>

		<form method="post" id="wpmc-form">
			<?php wp_nonce_field( WPMC_NONCE, 'wpmc_nonce' ); ?>

			<div class="wpmc-card">
				<h2>وضعیت و زمان‌بندی</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">حالت روشن/خاموش کردن</th>
						<td>
							<label style="margin-inline-end:22px;"><input type="radio" name="schedule_mode" value="manual" <?php checked( $o['schedule_mode'], 'manual' ); ?>> دستی (با تیک زیر کنترل می‌شود)</label>
							<label><input type="radio" name="schedule_mode" value="auto" <?php checked( $o['schedule_mode'], 'auto' ); ?>> خودکار (بر اساس تاریخ شروع و پایان)</label>
						</td>
					</tr>
					<tr class="wpmc-manual-row">
						<th scope="row">حالت تعمیر (دستی)</th>
						<td>
							<label style="font-size:15px;">
								<input type="checkbox" name="enabled" value="1" <?php checked( $o['enabled'], 1 ); ?> />
								فعال باشد (فقط وقتی حالت «دستی» انتخاب شده باشد اثر دارد)
							</label>
						</td>
					</tr>
					<tr class="wpmc-auto-row">
						<th scope="row">تاریخ و ساعت شروع تعمیر</th>
						<td>
							<input type="date" name="start_date" dir="ltr" style="direction:ltr;text-align:left;" value="<?php echo esc_attr( $start_date_val ); ?>" />
							<input type="time" name="start_time" dir="ltr" style="direction:ltr;text-align:left;" value="<?php echo esc_attr( $start_time_val ); ?>" />
							<p class="description">اگر خالی بگذارید، از همین الان در نظر گرفته می‌شود.</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="wpmc-card">
				<h2>متن‌های صفحه</h2>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label for="badge_text">متن بج بالای صفحه</label></th><td><input type="text" id="badge_text" name="badge_text" class="regular-text" value="<?php echo esc_attr( $o['badge_text'] ); ?>" /></td></tr>
					<tr><th scope="row"><label for="title">عنوان اصلی</label></th><td><input type="text" id="title" name="title" class="regular-text" value="<?php echo esc_attr( $o['title'] ); ?>" /></td></tr>
					<tr><th scope="row"><label for="description">توضیحات</label></th><td><textarea id="description" name="description" class="large-text" rows="3"><?php echo esc_textarea( $o['description'] ); ?></textarea></td></tr>
					<tr><th scope="row"><label for="feature1">ویژگی اول</label></th><td><input type="text" id="feature1" name="feature1" class="regular-text" value="<?php echo esc_attr( $o['feature1'] ); ?>" /></td></tr>
					<tr><th scope="row"><label for="feature2">ویژگی دوم</label></th><td><input type="text" id="feature2" name="feature2" class="regular-text" value="<?php echo esc_attr( $o['feature2'] ); ?>" /></td></tr>
					<tr><th scope="row"><label for="feature3">ویژگی سوم</label></th><td><input type="text" id="feature3" name="feature3" class="regular-text" value="<?php echo esc_attr( $o['feature3'] ); ?>" /></td></tr>
					<tr><th scope="row"><label for="footer_text">متن پایین صفحه</label></th><td><input type="text" id="footer_text" name="footer_text" class="regular-text" value="<?php echo esc_attr( $o['footer_text'] ); ?>" /></td></tr>
				</table>
			</div>

			<div class="wpmc-card">
				<h2>تایمر و پیشرفت</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">نمایش تایمر</th>
						<td><label style="font-size:15px;"><input type="checkbox" name="show_timer" value="1" <?php checked( $o['show_timer'], 1 ); ?> /> تایمر شمارش معکوس نمایش داده شود</label></td>
					</tr>
					<tr>
						<th scope="row"><label for="end_date">تاریخ بازگشایی سایت</label></th>
						<td>
							<input type="date" id="end_date" name="end_date" dir="ltr" style="direction:ltr;text-align:left;" value="<?php echo esc_attr( $end_date_val ); ?>" />
							<p class="description">روی باکس کلیک کنید تا تقویم مرورگر باز شود.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="end_time">ساعت بازگشایی سایت</label></th>
						<td>
							<input type="time" id="end_time" name="end_time" dir="ltr" style="direction:ltr;text-align:left;" value="<?php echo esc_attr( $end_time_val ); ?>" />
							<p class="description">اگر تاریخ را خالی بگذارید، به‌صورت پیش‌فرض ۲ روز دیگر در نظر گرفته می‌شود.</p>
						</td>
					</tr>
					<tr><th scope="row"><label for="timer_label">برچسب بالای تایمر</label></th><td><input type="text" id="timer_label" name="timer_label" class="regular-text" value="<?php echo esc_attr( $o['timer_label'] ); ?>" /></td></tr>
					<tr>
						<th scope="row"><label for="timer_sentence">جمله‌ی تایمر</label></th>
						<td><input type="text" id="timer_sentence" name="timer_sentence" class="large-text" value="<?php echo esc_attr( $o['timer_sentence'] ); ?>" /><p class="description">از {days}، {hours} و {minutes} استفاده کنید.</p></td>
					</tr>
					<tr><th scope="row"><label for="progress_label">برچسب نوار پیشرفت</label></th><td><input type="text" id="progress_label" name="progress_label" class="regular-text" value="<?php echo esc_attr( $o['progress_label'] ); ?>" /></td></tr>
					<tr><th scope="row"><label for="progress_percent">درصد پیشرفت</label></th><td><input type="number" min="0" max="100" id="progress_percent" name="progress_percent" value="<?php echo esc_attr( $o['progress_percent'] ); ?>" style="width:80px;" /> %</td></tr>
				</table>
			</div>

			<div class="wpmc-card">
				<h2>🎨 رنگ‌بندی</h2>
				<div class="wpmc-preset-grid">
					<?php foreach ( $presets as $key => $p ) : ?>
						<label class="wpmc-preset <?php echo $o['color_preset'] === $key ? 'active' : ''; ?>">
							<input type="radio" name="color_preset" value="<?php echo esc_attr( $key ); ?>" <?php checked( $o['color_preset'], $key ); ?> style="display:none;">
							<div class="wpmc-preset-swatch" style="background:linear-gradient(90deg, <?php echo esc_attr( $p['color_primary'] ); ?>, <?php echo esc_attr( $p['color_secondary'] ); ?>, <?php echo esc_attr( $p['color_accent'] ); ?>);"></div>
							<span><?php echo esc_html( $p['label'] ); ?></span>
						</label>
					<?php endforeach; ?>
					<label class="wpmc-preset <?php echo $o['color_preset'] === 'custom' ? 'active' : ''; ?>">
						<input type="radio" name="color_preset" value="custom" <?php checked( $o['color_preset'], 'custom' ); ?> style="display:none;">
						<div class="wpmc-preset-swatch" style="background:repeating-linear-gradient(45deg,#e2e8f0,#e2e8f0 6px,#cbd5e1 6px,#cbd5e1 12px);"></div>
						<span>دلخواه (پایین انتخاب کنید)</span>
					</label>
				</div>
				<table class="form-table" role="presentation" id="wpmc-custom-colors">
					<tr><th scope="row"><label for="color_primary">رنگ اصلی</label></th><td><input type="text" id="color_primary" name="color_primary" class="wpmc-color-field" value="<?php echo esc_attr( $o['color_primary'] ); ?>" /></td></tr>
					<tr><th scope="row"><label for="color_secondary">رنگ ثانویه</label></th><td><input type="text" id="color_secondary" name="color_secondary" class="wpmc-color-field" value="<?php echo esc_attr( $o['color_secondary'] ); ?>" /></td></tr>
					<tr><th scope="row"><label for="color_accent">رنگ تأکیدی</label></th><td><input type="text" id="color_accent" name="color_accent" class="wpmc-color-field" value="<?php echo esc_attr( $o['color_accent'] ); ?>" /></td></tr>
					<tr><th scope="row"><label for="color_bg1">پس‌زمینه ۱</label></th><td><input type="text" id="color_bg1" name="color_bg1" class="wpmc-color-field" value="<?php echo esc_attr( $o['color_bg1'] ); ?>" /></td></tr>
					<tr><th scope="row"><label for="color_bg2">پس‌زمینه ۲</label></th><td><input type="text" id="color_bg2" name="color_bg2" class="wpmc-color-field" value="<?php echo esc_attr( $o['color_bg2'] ); ?>" /></td></tr>
				</table>
			</div>

			<div class="wpmc-card">
				<h2>🔑 دسترسی هنگام فعال بودن حالت تعمیر</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">لینک پیش‌نمایش برای مشتری/تیم فنی</th>
						<td>
							<?php if ( $preview_url ) : ?>
							<div class="wpmc-copybox"><span id="wpmc-preview-url"><?php echo esc_html( $preview_url ); ?></span></div>
							<p class="description">هرکس این لینک را باز کند و روی مرورگرش کوکی بگیرد، تا ۷ روز سایت را بدون حالت تعمیر می‌بیند — بدون نیاز به عضویت در سایت.</p>
							<?php endif; ?>
							<button type="submit" name="wpmc_save" value="1" onclick="document.getElementById('wpmc_regen_token_field').value='1';" class="wpmc-btn-mini">🔄 ساخت لینک جدید (لینک قبلی از کار می‌افتد)</button>
							<input type="hidden" name="wpmc_regen_token" id="wpmc_regen_token_field" value="">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ip_whitelist">لیست سفید IP</label></th>
						<td>
							<textarea id="ip_whitelist" name="ip_whitelist" class="large-text" rows="3" placeholder="هر خط یک IP، مثلاً:&#10;1.2.3.4"><?php echo esc_textarea( $o['ip_whitelist'] ); ?></textarea>
							<p class="description">این IPها همیشه سایت را بدون حالت تعمیر می‌بینند. آی‌پی فعلی شما: <code><?php echo esc_html( wpmc_get_visitor_ip() ); ?></code></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="wpmc-card">
				<h2>📬 ثبت‌نام ایمیلی برای اطلاع بازگشایی</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">فعال باشد</th>
						<td><label><input type="checkbox" name="email_capture" <?php checked( $o['email_capture'], 1 ); ?>> فرم «به من ایمیل بزن وقتی سایت باز شد» زیر تایمر نمایش داده شود</label></td>
					</tr>
					<tr>
						<th scope="row">مشترکین ثبت‌شده</th>
						<td>
							<p><strong><?php echo esc_html( $subs_count ); ?></strong> ایمیل ثبت شده است.</p>
							<a class="wpmc-btn-mini" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wpmc_export_subscribers' ), WPMC_NONCE ) ); ?>">⬇️ خروجی CSV</a>
							<a class="wpmc-btn-mini" style="background:#b91c1c;" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wpmc_clear_subscribers' ), WPMC_NONCE ) ); ?>" onclick="return confirm('همه مشترکین پاک شوند؟');">🗑️ پاک کردن لیست</a>
						</td>
					</tr>
				</table>
			</div>

			<div class="wpmc-card">
				<h2>⚙️ سئو و پیشرفته</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">noindex برای موتورهای جستجو</th>
						<td><label><input type="checkbox" name="seo_noindex" <?php checked( $o['seo_noindex'], 1 ); ?>> در حین تعمیر، به گوگل بگو صفحه را ایندکس نکند (توصیه‌شده)</label></td>
					</tr>
					<tr>
						<th scope="row"><label for="custom_css">CSS اختصاصی</label></th>
						<td><textarea id="custom_css" name="custom_css" class="large-text code" rows="5" placeholder=".wpmc-title{ font-size: 50px; }"><?php echo esc_textarea( $o['custom_css'] ); ?></textarea></td>
					</tr>
				</table>
			</div>

			<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank">👁️ پیش‌نمایش سایت را در تب جدید ببینید</a> (چون شما مدیر هستید همیشه سایت عادی را می‌بینید؛ برای دیدن صفحه‌ی تعمیر از حالت ناشناس مرورگر استفاده کنید)</p>

			<?php submit_button( 'ذخیره تنظیمات', 'primary', 'wpmc_save' ); ?>
		</form>

		<hr style="margin-top:40px;">
		<p style="text-align:center; color:#888; font-size:13px;">
			ساخته شده توسط علیرضا رحمتی | اینستاگرام: <a href="https://instagram.com/alireza.webcode" target="_blank">alireza.webcode</a> | تلگرام: <a href="https://t.me/alireza_webcode" target="_blank">alireza_webcode</a>
		</p>
	</div>

	<script>
	jQuery(document).ready(function($){
		$('.wpmc-color-field').wpColorPicker();

		function toggleScheduleRows(){
			var mode = $('input[name=schedule_mode]:checked').val();
			$('.wpmc-manual-row').toggle(mode === 'manual');
			$('.wpmc-auto-row').toggle(mode === 'auto');
		}
		toggleScheduleRows();
		$('input[name=schedule_mode]').on('change', toggleScheduleRows);

		function toggleCustomColors(){
			$('#wpmc-custom-colors').toggle($('input[name=color_preset]:checked').val() === 'custom');
		}
		toggleCustomColors();
		$('input[name=color_preset]').on('change', function(){
			$('.wpmc-preset').removeClass('active');
			$(this).closest('.wpmc-preset').addClass('active');
			toggleCustomColors();
		});

		var url = $('#wpmc-preview-url').text();
		if(url){
			$('#wpmc-preview-url').after('<button type="button" class="wpmc-btn-mini" style="margin-inline-start:8px;" id="wpmc-copy-btn">کپی</button>');
			$('#wpmc-copy-btn').on('click', function(){
				navigator.clipboard.writeText(url);
				$(this).text('کپی شد ✓');
			});
		}
	});
	</script>
	<?php
}

function wpmc_get_visitor_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
}

/**
 * آیا با توجه به حالت دستی/خودکار، حالت تعمیر همین الان باید فعال باشد؟
 */
function wpmc_is_active_now( $o ) {
	if ( 'auto' === $o['schedule_mode'] ) {
		$start = ! empty( $o['start_datetime'] ) ? wpmc_resolve_end_timestamp( $o['start_datetime'] ) : 0;
		$end   = ! empty( $o['end_datetime'] ) ? wpmc_resolve_end_timestamp( $o['end_datetime'] ) : 0;
		$now   = time();
		if ( $start && $now < $start ) return false;
		if ( $end && $now > $end ) return false;
		return true;
	}
	return ! empty( $o['enabled'] );
}

/* ==========================================================================
   4) نمایش صفحه تعمیر در سمت سایت
   ========================================================================== */
add_action( 'template_redirect', 'wpmc_maybe_show_maintenance_page' );
function wpmc_maybe_show_maintenance_page() {

	$o = wpmc_get_options();

	if ( ! wpmc_is_active_now( $o ) ) return;

	// مدیر سایت و درخواست‌های ادمین/اجاکس از حالت تعمیر مستثنی هستند
	if ( current_user_can( 'manage_options' ) ) return;
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
	if ( is_admin() ) return;

	// لینک پیش‌نمایش: اگر توکن معتبر باشد، کوکی ۷ روزه ست کن و بگذر
	if ( ! empty( $o['preview_token'] ) && isset( $_GET['gv_preview'] ) && hash_equals( $o['preview_token'], sanitize_text_field( wp_unslash( $_GET['gv_preview'] ) ) ) ) {
		if ( ! headers_sent() ) {
			setcookie( 'gv_maintenance_preview', $o['preview_token'], time() + 7 * DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
		}
		return;
	}
	if ( ! empty( $o['preview_token'] ) && isset( $_COOKIE['gv_maintenance_preview'] ) && hash_equals( $o['preview_token'], $_COOKIE['gv_maintenance_preview'] ) ) {
		return;
	}

	// لیست سفید IP
	if ( ! empty( $o['ip_whitelist'] ) ) {
		$visitor_ip = wpmc_get_visitor_ip();
		$whitelist  = array_filter( array_map( 'trim', explode( "\n", $o['ip_whitelist'] ) ) );
		if ( in_array( $visitor_ip, $whitelist, true ) ) return;
	}

	status_header( 503 );
	header( 'Retry-After: 3600' );

	$end_ts = 0;
	if ( ! empty( $o['show_timer'] ) ) {
		$end_ts = wpmc_resolve_end_timestamp( $o['end_datetime'] );
	}

	wpmc_render_maintenance_html( $o, $end_ts );
	exit;
}

/**
 * تبدیل مقدار فیلد «تاریخ و ساعت» به یک timestamp قابل‌اتکا،
 * با در نظر گرفتن منطقه‌ی زمانیِ ثبت‌شده در پیشخوان ← تنظیمات ← عمومی.
 */
function wpmc_resolve_end_timestamp( $raw_datetime ) {
	$fallback = time() + 2 * DAY_IN_SECONDS;
	if ( empty( $raw_datetime ) ) return $fallback;
	try {
		$tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
		$dt = new DateTime( $raw_datetime, $tz );
		return $dt->getTimestamp();
	} catch ( Exception $e ) {
		return $fallback;
	}
}

/** تولید خطوط مدرج (تیک‌های) لبه‌ی اسطرلاب — عنصر تزئینی */
function wpmc_astrolabe_ticks() {
	$out = '';
	for ( $i = 0; $i < 24; $i++ ) {
		$deg      = $i * 15;
		$is_major = ( $i % 6 === 0 );
		$y1       = $is_major ? 4 : 6.5;
		$width    = $is_major ? 1.6 : 0.8;
		$opacity  = $is_major ? 0.95 : 0.4;
		$out .= '<line x1="50" y1="' . $y1 . '" x2="50" y2="11" stroke="var(--wpmc-gold)" stroke-width="' . $width . '" opacity="' . $opacity . '" transform="rotate(' . $deg . ' 50 50)"/>';
	}
	return $out;
}

function wpmc_render_maintenance_html( $o, $end_ts ) {
	$site_name = get_bloginfo( 'name' );
	$gold      = esc_attr( $o['color_primary'] );
	$turq      = esc_attr( $o['color_secondary'] );
	$ruby      = esc_attr( $o['color_accent'] ?? '#B23A48' );
	$bg1       = esc_attr( $o['color_bg1'] );
	$bg2       = esc_attr( $o['color_bg2'] );
	$favicon   = get_site_icon_url();
	$sub_nonce = wp_create_nonce( 'wpmc_subscribe' );
	?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if ( ! empty( $o['seo_noindex'] ) ) : ?><meta name="robots" content="noindex,nofollow"><?php endif; ?>
<title><?php echo esc_html( $o['title'] . ' — ' . $site_name ); ?></title>
<?php if ( $favicon ) : ?><link rel="icon" href="<?php echo esc_url( $favicon ); ?>"><?php endif; ?>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=vazirmatn:400,500,600,700,800,900" rel="stylesheet">
<style>
	:root{
		--wpmc-gold: <?php echo $gold; ?>;
		--wpmc-turq: <?php echo $turq; ?>;
		--wpmc-ruby: <?php echo $ruby; ?>;
		--wpmc-bg1: <?php echo $bg1; ?>;
		--wpmc-bg2: <?php echo $bg2; ?>;
		--wpmc-ivory: #F3ECDD;
	}
	*{ box-sizing:border-box; }
	html,body{ margin:0; padding:0; min-height:100vh; overflow-x:hidden; }
	body{
		position:relative;
		display:flex; align-items:center; justify-content:center;
		padding:28px 16px;
		font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
		color: var(--wpmc-ivory);
		background:
			radial-gradient(circle at 18% 22%, color-mix(in srgb, var(--wpmc-gold) 22%, transparent) 0%, transparent 45%),
			radial-gradient(circle at 82% 18%, color-mix(in srgb, var(--wpmc-turq) 26%, transparent) 0%, transparent 45%),
			radial-gradient(circle at 70% 88%, color-mix(in srgb, var(--wpmc-ruby) 22%, transparent) 0%, transparent 50%),
			linear-gradient(135deg, var(--wpmc-bg1), var(--wpmc-bg2) 55%, var(--wpmc-bg1));
		background-size: 140% 140%, 140% 140%, 140% 140%, 260% 260%;
		animation: wpmc-mesh 22s ease-in-out infinite;
	}
	@keyframes wpmc-mesh{
		0%{ background-position: 0% 0%, 100% 0%, 100% 100%, 0% 50%; }
		50%{ background-position: 15% 25%, 75% 25%, 65% 75%, 100% 50%; }
		100%{ background-position: 0% 0%, 100% 0%, 100% 100%, 0% 50%; }
	}
	.wpmc-girih{ position:fixed; inset:0; pointer-events:none; z-index:1; opacity:.06;
		background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='90' height='90'%3E%3Cg fill='none' stroke='%23D4AF6A' stroke-width='1.2'%3E%3Cpath d='M45 4 L62 22 L45 40 L28 22 Z M45 40 L62 58 L45 76 L28 58 Z M4 45 L22 28 L40 45 L22 62 Z M50 45 L68 28 L86 45 L68 62 Z'/%3E%3Ccircle cx='45' cy='45' r='6'/%3E%3C/g%3E%3C/svg%3E");
		background-size: 90px 90px;
		animation: wpmc-girih-move 60s linear infinite;
	}
	@keyframes wpmc-girih-move{ from{ background-position:0 0; } to{ background-position:360px 360px; } }
	.wpmc-blob{ position:fixed; border-radius:50%; filter: blur(80px); opacity:.28; z-index:1; pointer-events:none; }
	.wpmc-blob.b1{ width:300px; height:300px; background:var(--wpmc-gold); top:-70px; left:-70px; animation: wpmc-float1 16s ease-in-out infinite; }
	.wpmc-blob.b2{ width:320px; height:320px; background:var(--wpmc-turq); bottom:-90px; right:-70px; animation: wpmc-float2 18s ease-in-out infinite; }
	.wpmc-blob.b3{ width:220px; height:220px; background:var(--wpmc-ruby); top:42%; right:6%; animation: wpmc-float1 14s ease-in-out infinite reverse; }
	@keyframes wpmc-float1{ 0%,100%{ transform:translate(0,0); } 50%{ transform:translate(26px,34px); } }
	@keyframes wpmc-float2{ 0%,100%{ transform:translate(0,0); } 50%{ transform:translate(-26px,-26px); } }
	.wpmc-wrap{ position:relative; z-index:3; width:100%; max-width:700px; }
	.wpmc-card{
		position:relative;
		background: rgba(255,255,255,0.06);
		border: 1px solid rgba(212,175,106,0.28);
		backdrop-filter: blur(22px);
		-webkit-backdrop-filter: blur(22px);
		border-radius: 26px;
		padding: 48px 40px 34px;
		text-align:center;
		box-shadow: 0 30px 80px rgba(0,0,0,.5), inset 0 1px 0 rgba(255,255,255,.12);
		animation: wpmc-rise 1s cubic-bezier(.2,.8,.2,1);
	}
	@keyframes wpmc-rise{ from{ opacity:0; transform: translateY(26px) scale(.97); } to{ opacity:1; transform: translateY(0) scale(1); } }
	.wpmc-card::before, .wpmc-card::after,
	.wpmc-corner-l::before, .wpmc-corner-l::after{
		content:"✦"; position:absolute; color: var(--wpmc-gold); font-size:13px; opacity:.7;
	}
	.wpmc-card::before{ top:16px; right:18px; }
	.wpmc-card::after{ top:16px; left:18px; }
	.wpmc-corner-l{ position:absolute; inset:0; pointer-events:none; }
	.wpmc-corner-l::before{ bottom:16px; right:18px; top:auto; }
	.wpmc-corner-l::after{ bottom:16px; left:18px; top:auto; }
	.wpmc-badge{
		display:inline-flex; align-items:center; gap:7px;
		background: linear-gradient(90deg, var(--wpmc-ruby), var(--wpmc-gold));
		padding:7px 18px; border-radius:999px;
		font-size:13px; font-weight:700; color:#1a0e05;
		box-shadow: 0 8px 22px color-mix(in srgb, var(--wpmc-ruby) 45%, transparent);
		margin-bottom:26px;
	}
	.wpmc-astrolabe{ position:relative; width:118px; height:118px; margin:0 auto 22px; }
	.wpmc-astrolabe svg{ position:absolute; inset:0; width:100%; height:100%; overflow:visible; }
	.wpmc-astrolabe-limb{ animation: wpmc-spin 46s linear infinite; filter: drop-shadow(0 0 10px color-mix(in srgb, var(--wpmc-gold) 45%, transparent)); }
	.wpmc-astrolabe-arm{ transform-origin:50% 50%; animation: wpmc-spin-rev 24s linear infinite; }
	.wpmc-astrolabe-star{ transform-origin:50% 50%; animation: wpmc-breathe 3.6s ease-in-out infinite; filter: drop-shadow(0 0 8px color-mix(in srgb, var(--wpmc-turq) 55%, transparent)); }
	@keyframes wpmc-spin{ from{ transform: rotate(0deg); } to{ transform: rotate(360deg); } }
	@keyframes wpmc-spin-rev{ from{ transform: rotate(0deg); } to{ transform: rotate(-360deg); } }
	@keyframes wpmc-breathe{ 0%,100%{ transform: scale(1); opacity:1; } 50%{ transform: scale(1.06); opacity:.92; } }
	.wpmc-title{ font-size: clamp(28px, 4.4vw, 42px); font-weight:800; margin: 0 0 12px; line-height:1.4; letter-spacing:-0.3px; }
	.wpmc-title span{
		background: linear-gradient(90deg, var(--wpmc-gold), var(--wpmc-turq));
		-webkit-background-clip:text; background-clip:text; color:transparent;
	}
	.wpmc-desc{ font-size:16px; line-height:2; color: rgba(243,236,221,0.8); max-width:520px; margin:0 auto 24px; font-weight:400; }
	.wpmc-band{ width:220px; height:8px; margin:0 auto 26px; position:relative;
		background: repeating-linear-gradient(90deg, var(--wpmc-gold) 0 2px, transparent 2px 16px);
		opacity:.55;
	}
	.wpmc-band::before, .wpmc-band::after{ content:"◆"; position:absolute; top:50%; transform:translateY(-50%); color:var(--wpmc-turq); font-size:10px; }
	.wpmc-band::before{ right:-18px; } .wpmc-band::after{ left:-18px; }
	.wpmc-sentence{
		display:inline-block; max-width:100%;
		background: linear-gradient(90deg, color-mix(in srgb, var(--wpmc-gold) 20%, transparent), color-mix(in srgb, var(--wpmc-turq) 20%, transparent));
		border: 1px solid rgba(212,175,106,.35); border-radius: 14px; padding: 14px 22px;
		font-size: 16px; font-weight:700; line-height:1.9; margin-bottom: 24px;
	}
	.wpmc-sentence b{ color: var(--wpmc-turq); font-size:18px; }
	.wpmc-timer-label{ font-size:13px; color: rgba(243,236,221,.55); margin-bottom:14px; letter-spacing:.5px; }
	.wpmc-countdown{ display:flex;flex-direction:row-reverse; gap:14px; justify-content:center; flex-wrap:wrap; margin-bottom:32px; }
	.wpmc-countdown .box{
		position:relative;
		background:
			radial-gradient(circle at 9px 9px, color-mix(in srgb, var(--wpmc-gold) 65%, transparent) 2px, transparent 2.4px),
			radial-gradient(circle at calc(100% - 9px) 9px, color-mix(in srgb, var(--wpmc-gold) 65%, transparent) 2px, transparent 2.4px),
			linear-gradient(160deg, rgba(212,175,106,.16), rgba(212,175,106,.02));
		border: 1px solid rgba(212,175,106,.32); border-radius:12px; padding:16px 6px 12px; min-width:82px;
		box-shadow: 0 12px 30px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.12);
	}
	.wpmc-countdown .num{
		font-size:32px; font-weight:800; display:block;
		background: linear-gradient(180deg, var(--wpmc-ivory), var(--wpmc-gold));
		-webkit-background-clip:text; background-clip:text; color:transparent;
	}
	.wpmc-countdown .num.flip{ animation: wpmc-flip .45s ease; }
	@keyframes wpmc-flip{
		0%{ transform: rotateX(0deg) scale(1); }
		35%{ transform: rotateX(90deg) scale(.85); opacity:.4; }
		36%{ transform: rotateX(-90deg) scale(.85); }
		100%{ transform: rotateX(0deg) scale(1); opacity:1; }
	}
	.wpmc-countdown .label{ font-size:11.5px; color: rgba(243,236,221,.55); margin-top:6px; display:block; }
	.wpmc-countdown .sep{ align-self:center; font-size:24px; font-weight:800; color: rgba(243,236,221,.3); }
	.wpmc-progress-wrap{ max-width:420px; margin:0 auto 30px; }
	.wpmc-progress-top{ display:flex; justify-content:space-between; font-size:12px; color:rgba(243,236,221,.6); margin-bottom:7px; }
	.wpmc-progress-top b{ color: var(--wpmc-ivory); }
	.wpmc-progress{ width:100%; height:9px; border-radius:9px; background: rgba(255,255,255,0.09); overflow:hidden; position:relative; }
	.wpmc-progress-bar{ height:100%; border-radius:9px; background: linear-gradient(90deg, var(--wpmc-gold), var(--wpmc-turq)); position:relative; overflow:hidden; }
	.wpmc-progress-bar::after{
		content:""; position:absolute; inset:0;
		background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent);
		width:40%; animation: wpmc-shimmer 2.2s linear infinite;
	}
	@keyframes wpmc-shimmer{ from{ transform:translateX(-120%); } to{ transform:translateX(320%); } }
	.wpmc-features{ display:flex; justify-content:center; gap:10px; flex-wrap:wrap; margin-bottom:26px; }
	.wpmc-feature{
		display:flex; align-items:center; gap:7px;
		background: rgba(255,255,255,.05); border:1px solid rgba(212,175,106,.22);
		padding:9px 16px; border-radius:12px; font-size:13px; color:rgba(243,236,221,.82);
	}
	.wpmc-feature svg{ width:16px; height:16px; flex-shrink:0; color: var(--wpmc-turq); }

	/* فرم اطلاع‌رسانی ایمیلی */
	.wpmc-subscribe{ max-width:420px; margin:0 auto 28px; }
	.wpmc-subscribe-label{ font-size:12.5px; color:rgba(243,236,221,.55); margin-bottom:10px; }
	.wpmc-subscribe-row{ display:flex; gap:8px; }
	.wpmc-subscribe-row input[type=email]{
		flex:1; padding:12px 16px; border-radius:12px; border:1px solid rgba(212,175,106,.32);
		background:rgba(255,255,255,.06); color:var(--wpmc-ivory); font-family:inherit; font-size:14px; outline:none;
	}
	.wpmc-subscribe-row input[type=email]::placeholder{ color:rgba(243,236,221,.4); }
	.wpmc-subscribe-row button{
		background: linear-gradient(90deg, var(--wpmc-gold), var(--wpmc-turq)); color:#1a0e05; font-weight:800;
		border:none; border-radius:12px; padding:0 20px; cursor:pointer; font-family:inherit; font-size:13.5px; white-space:nowrap;
	}
	.wpmc-subscribe-msg{ margin-top:10px; font-size:12.5px; }
	.wpmc-subscribe-msg.ok{ color:#4ade80; }
	.wpmc-subscribe-msg.err{ color:#fca5a5; }

	.wpmc-social{ display:flex; justify-content:center; gap:12px; margin-bottom:24px; }
	.wpmc-social a{
		width:42px; height:42px; border-radius:50%;
		display:flex; align-items:center; justify-content:center;
		background: rgba(255,255,255,.06); border:1px solid rgba(212,175,106,.3);
		transition: transform .25s ease, background .25s ease; color: var(--wpmc-ivory); text-decoration:none;
	}
	.wpmc-social a:hover{ transform: translateY(-4px); background: linear-gradient(135deg, var(--wpmc-gold), var(--wpmc-turq)); color:#1a0e05; }
	.wpmc-social svg{ width:19px; height:19px; }
	.wpmc-footer{ font-size:13px; color: rgba(243,236,221,.5); }

	@media (max-width: 560px){
		.wpmc-card{ padding:38px 20px 26px; border-radius:20px; }
		.wpmc-countdown{ gap:8px; }
		.wpmc-countdown .box{ min-width: calc(25% - 8px); padding:12px 4px 10px; }
		.wpmc-countdown .num{ font-size:22px; }
		.wpmc-countdown .sep{ display:none; }
		.wpmc-title{ font-size:24px; }
		.wpmc-desc{ font-size:14px; }
		.wpmc-sentence{ font-size:14px; padding:12px 16px; }
		.wpmc-features{ gap:8px; }
		.wpmc-feature{ font-size:12px; padding:7px 12px; }
		.wpmc-subscribe-row{ flex-direction:column; }
	}
	@media (prefers-reduced-motion: reduce){
		*{ animation-duration: .001ms !important; animation-iteration-count: 1 !important; }
	}

	<?php if ( ! empty( $o['custom_css'] ) ) { echo $o['custom_css']; } ?>
</style>
</head>
<body>
	<div class="wpmc-girih"></div>
	<div class="wpmc-blob b1"></div>
	<div class="wpmc-blob b2"></div>
	<div class="wpmc-blob b3"></div>

	<div class="wpmc-wrap">
		<div class="wpmc-card">
			<div class="wpmc-corner-l"></div>

			<?php if ( ! empty( $o['badge_text'] ) ) : ?>
			<div class="wpmc-badge">✦ <?php echo esc_html( $o['badge_text'] ); ?></div>
			<?php endif; ?>

			<div class="wpmc-astrolabe" aria-hidden="true">
				<svg class="wpmc-astrolabe-limb" viewBox="0 0 100 100">
					<circle cx="50" cy="50" r="46" fill="none" stroke="var(--wpmc-gold)" stroke-width="1.1" opacity="0.5"/>
					<?php echo wpmc_astrolabe_ticks(); ?>
					<circle cx="50" cy="50" r="34" fill="none" stroke="var(--wpmc-turq)" stroke-width="1" stroke-dasharray="2 5" opacity="0.55"/>
				</svg>
				<svg class="wpmc-astrolabe-star" viewBox="0 0 100 100">
					<defs>
						<linearGradient id="wpmcStarGrad" x1="0%" y1="0%" x2="100%" y2="100%">
							<stop offset="0%" stop-color="var(--wpmc-gold)"/>
							<stop offset="100%" stop-color="var(--wpmc-ruby)"/>
						</linearGradient>
					</defs>
					<path fill="url(#wpmcStarGrad)" d="M50,34 L52.49,43.99 L61.31,38.69 L56.00,47.51 L66,50 L56.00,52.49 L61.31,61.31 L52.49,56.00 L50,66 L47.51,56.00 L38.69,61.31 L44.00,52.49 L34,50 L44.00,47.51 L38.69,38.69 L47.51,43.99 Z"/>
				</svg>
				<svg class="wpmc-astrolabe-arm" viewBox="0 0 100 100">
					<line x1="50" y1="50" x2="50" y2="9" stroke="var(--wpmc-ivory)" stroke-width="1.3" opacity="0.85"/>
					<circle cx="50" cy="9" r="2.2" fill="var(--wpmc-turq)"/>
				</svg>
			</div>

			<h1 class="wpmc-title"><span><?php echo esc_html( $o['title'] ); ?></span></h1>
			<p class="wpmc-desc"><?php echo nl2br( esc_html( $o['description'] ) ); ?></p>
			<div class="wpmc-band" aria-hidden="true"></div>

			<?php if ( $end_ts ) : ?>
			<div class="wpmc-sentence" id="wpmc-sentence" data-template="<?php echo esc_attr( $o['timer_sentence'] ); ?>"><?php echo esc_html( $o['timer_sentence'] ); ?></div>
			<div class="wpmc-timer-label"><?php echo esc_html( $o['timer_label'] ); ?></div>
			<div class="wpmc-countdown" id="wpmc-countdown">
				<div class="box"><span class="num" id="wpmc-d">۰۰</span><span class="label">روز</span></div>
				<div class="sep">:</div>
				<div class="box"><span class="num" id="wpmc-h">۰۰</span><span class="label">ساعت</span></div>
				<div class="sep">:</div>
				<div class="box"><span class="num" id="wpmc-m">۰۰</span><span class="label">دقیقه</span></div>
				<div class="sep">:</div>
				<div class="box"><span class="num" id="wpmc-s">۰۰</span><span class="label">ثانیه</span></div>
			</div>
			<?php endif; ?>

			<div class="wpmc-progress-wrap">
				<div class="wpmc-progress-top"><span><?php echo esc_html( $o['progress_label'] ); ?></span><b><?php echo esc_html( wpmc_to_fa_digits( $o['progress_percent'] ) ); ?>٪</b></div>
				<div class="wpmc-progress"><div class="wpmc-progress-bar" style="width:<?php echo (int) $o['progress_percent']; ?>%;"></div></div>
			</div>

			<?php if ( $o['feature1'] || $o['feature2'] || $o['feature3'] ) : ?>
			<div class="wpmc-features">
				<?php if ( $o['feature1'] ) : ?><div class="wpmc-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg> <?php echo esc_html( $o['feature1'] ); ?></div><?php endif; ?>
				<?php if ( $o['feature2'] ) : ?><div class="wpmc-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2 3 14h7l-1 8 10-12h-7z"/></svg> <?php echo esc_html( $o['feature2'] ); ?></div><?php endif; ?>
				<?php if ( $o['feature3'] ) : ?><div class="wpmc-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v18M3 12h18"/></svg> <?php echo esc_html( $o['feature3'] ); ?></div><?php endif; ?>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $o['email_capture'] ) ) : ?>
			<div class="wpmc-subscribe">
				<div class="wpmc-subscribe-label">📬 دوست دارید همان لحظه‌ی بازگشایی سایت با ایمیل باخبر شوید؟</div>
				<form class="wpmc-subscribe-row" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="wpmc_subscribe">
					<input type="hidden" name="wpmc_subscribe_nonce" value="<?php echo esc_attr( $sub_nonce ); ?>">
					<input type="email" name="wpmc_email" placeholder="ایمیل شما" required>
					<button type="submit">اطلاع بده</button>
				</form>
				<?php if ( isset( $_GET['wpmc_sub'] ) && 'ok' === $_GET['wpmc_sub'] ) : ?>
					<div class="wpmc-subscribe-msg ok">✓ ثبت شد! هنگام بازگشایی به شما خبر می‌دهیم.</div>
				<?php elseif ( isset( $_GET['wpmc_sub'] ) && 'err' === $_GET['wpmc_sub'] ) : ?>
					<div class="wpmc-subscribe-msg err">✕ ایمیل معتبر نبود، دوباره تلاش کنید.</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<div class="wpmc-social">
				<a href="https://instagram.com/alireza.webcode" target="_blank" rel="noopener" aria-label="اینستاگرام">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg>
				</a>
				<a href="https://t.me/alireza_webcode" target="_blank" rel="noopener" aria-label="تلگرام">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
				</a>
			</div>

			<div class="wpmc-footer"><?php echo esc_html( $o['footer_text'] ); ?></div>
		</div>
	</div>

	<?php if ( $end_ts ) : ?>
	<script>
	(function(){
		var target = <?php echo (int) $end_ts * 1000; ?>;
		var fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
		function toFa(num){ return String(num).replace(/[0-9]/g, function(d){ return fa[d]; }); }
		function pad(num){ return String(num).padStart(2,'0'); }
		var prev = { d:null, h:null, m:null, s:null };
		function setVal(id, val, key){
			var el = document.getElementById(id);
			var faVal = toFa(pad(val));
			if(prev[key] !== faVal){
				el.textContent = faVal;
				el.classList.remove('flip');
				void el.offsetWidth;
				el.classList.add('flip');
				prev[key] = faVal;
			}
		}
		var sentenceEl = document.getElementById('wpmc-sentence');
		var template = sentenceEl ? sentenceEl.getAttribute('data-template') : '';
		function tick(){
			var now = Date.now();
			var diff = Math.max(0, target - now);
			var d = Math.floor(diff / (1000*60*60*24));
			var h = Math.floor((diff / (1000*60*60)) % 24);
			var m = Math.floor((diff / (1000*60)) % 60);
			var s = Math.floor((diff / 1000) % 60);
			setVal('wpmc-d', d, 'd');
			setVal('wpmc-h', h, 'h');
			setVal('wpmc-m', m, 'm');
			setVal('wpmc-s', s, 's');
			if( sentenceEl && template ){
				var text = template
					.replace('{days}', '<b>' + toFa(d) + '</b>')
					.replace('{hours}', '<b>' + toFa(h) + '</b>')
					.replace('{minutes}', '<b>' + toFa(m) + '</b>');
				sentenceEl.innerHTML = text;
			}
			if( diff <= 0 ){ clearInterval(timer); }
		}
		tick();
		var timer = setInterval(tick, 1000);
	})();
	</script>
	<?php endif; ?>
</body>
</html>
	<?php
}

/** تبدیل اعداد انگلیسی به فارسی برای نمایش در صفحه. */
function wpmc_to_fa_digits( $num ) {
	$fa = array( '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹' );
	return preg_replace_callback( '/[0-9]/', function( $m ) use ( $fa ) {
		return $fa[ (int) $m[0] ];
	}, (string) $num );
}
