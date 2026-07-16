<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * ==========================================================
 *  Groot Vision — تاریخ خودکار شمسی در انتهای عنوان نوشته‌ها
 *  هر بار که کاربر صفحه را باز می‌کند، تاریخ امروز به‌صورت
 *  شمسی (مثلاً «۲۵ تیر») به‌صورت زنده به انتهای عنوان
 *  نوشته/برگه اضافه می‌شود — بدون نیاز به هیچ ویرایش دستی،
 *  فردا خودش می‌شود «۲۶ تیر» و به همین ترتیب ادامه پیدا می‌کند.
 * ==========================================================
 */
define( 'GV_JDATE_OPT', 'gv_jalali_date_settings' );
define( 'GV_JDATE_NONCE', 'gv_jdate_nonce_action' );

function gv_jdate_default_settings() {
	return array(
		'enabled'    => 0,
		'post_types' => array( 'post' => 1, 'page' => 0, 'product' => 0 ),
		'separator'  => ' - ',
		'context'    => 'single', // single | archive_and_single
	);
}

function gv_jdate_get_settings() {
	$saved = get_option( GV_JDATE_OPT, array() );
	$defaults = gv_jdate_default_settings();
	$settings = wp_parse_args( $saved, $defaults );
	if ( isset( $saved['post_types'] ) && is_array( $saved['post_types'] ) ) {
		$settings['post_types'] = wp_parse_args( $saved['post_types'], $defaults['post_types'] );
	}
	return $settings;
}

/* ==========================================================================
   ۱) تبدیل تاریخ میلادی به شمسی (بدون نیاز به هیچ کتابخانه خارجی)
   ========================================================================== */

function gv_jdate_gregorian_to_jalali( $g_y, $g_m, $g_d ) {
	$g_days_in_month = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
	$j_days_in_month = array( 31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29 );

	$gy = $g_y - 1600;
	$gm = $g_m - 1;
	$gd = $g_d - 1;

	$g_day_no = 365 * $gy + intdiv( $gy + 3, 4 ) - intdiv( $gy + 99, 100 ) + intdiv( $gy + 399, 400 );
	for ( $i = 0; $i < $gm; ++$i ) { $g_day_no += $g_days_in_month[ $i ]; }
	if ( $gm > 1 && ( ( $g_y % 4 === 0 && $g_y % 100 !== 0 ) || ( $g_y % 400 === 0 ) ) ) { $g_day_no++; }
	$g_day_no += $gd;

	$j_day_no = $g_day_no - 79;
	$j_np = intdiv( $j_day_no, 12053 );
	$j_day_no = $j_day_no % 12053;

	$jy = 979 + 33 * $j_np + 4 * intdiv( $j_day_no, 1461 );
	$j_day_no %= 1461;

	if ( $j_day_no >= 366 ) {
		$jy += intdiv( $j_day_no - 1, 365 );
		$j_day_no = ( $j_day_no - 1 ) % 365;
	}

	$jm = 0;
	for ( $i = 0; $i < 11 && $j_day_no >= $j_days_in_month[ $i ]; ++$i ) {
		$j_day_no -= $j_days_in_month[ $i ];
		$jm++;
	}
	$jd = $j_day_no + 1;

	return array( $jy, $jm + 1, $jd );
}

function gv_jdate_month_name( $m ) {
	$names = array(
		1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
		5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
		9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
	);
	return $names[ $m ] ?? '';
}

function gv_jdate_to_persian_digits( $str ) {
	$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
	return preg_replace_callback( '/[0-9]/', function ( $m ) use ( $fa ) { return $fa[ $m[0] ]; }, (string) $str );
}

/** رشته امروز به شمسی، مثل «۲۵ تیر» یا با سال اگر لازم شود «۲۵ تیر ۱۴۰۳» */
function gv_jdate_today_string( $with_year = false ) {
	$now = current_time( 'timestamp' ); // زمان محلی سایت
	list( $jy, $jm, $jd ) = gv_jdate_gregorian_to_jalali( (int) date( 'Y', $now ), (int) date( 'n', $now ), (int) date( 'j', $now ) );
	$str = gv_jdate_to_persian_digits( $jd ) . ' ' . gv_jdate_month_name( $jm );
	if ( $with_year ) { $str .= ' ' . gv_jdate_to_persian_digits( $jy ); }
	return $str;
}

/* ==========================================================================
   ۲) منوی مدیریت
   ========================================================================== */

add_action( 'admin_menu', 'gv_jdate_admin_menu' );
function gv_jdate_admin_menu() {
	add_submenu_page(
		'groot-vision-hub',
		'تاریخ خودکار شمسی | Groot Vision',
		'📅 تاریخ خودکار',
		'manage_options',
		'gv-jalali-date',
		'gv_jdate_render_admin_page'
	);
}

add_action( 'admin_post_gv_jdate_save_settings', 'gv_jdate_save_settings' );
function gv_jdate_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_JDATE_NONCE );

	$defaults = gv_jdate_default_settings();
	$post_types = array();
	foreach ( array_keys( $defaults['post_types'] ) as $pt ) {
		$post_types[ $pt ] = isset( $_POST['post_types'][ $pt ] ) ? 1 : 0;
	}

	$sep = sanitize_text_field( wp_unslash( $_POST['separator'] ?? ' - ' ) );
	if ( '' === trim( $sep ) ) { $sep = ' - '; }

	$settings = array(
		'enabled'    => isset( $_POST['enabled'] ) ? 1 : 0,
		'post_types' => $post_types,
		'separator'  => $sep,
		'context'    => in_array( $_POST['context'] ?? '', array( 'single', 'archive_and_single' ), true ) ? $_POST['context'] : 'single',
	);
	update_option( GV_JDATE_OPT, $settings );
	wp_safe_redirect( admin_url( 'admin.php?page=gv-jalali-date&updated=1' ) );
	exit;
}

function gv_jdate_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$s = gv_jdate_get_settings();
	$post_type_objects = get_post_types( array( 'public' => true ), 'objects' );
	?>
	<div class="wrap" dir="rtl" style="font-family: Tahoma, sans-serif; max-width:900px;">
		<style>
			.gvjd-header{background:linear-gradient(120deg,#0e4037,#145c4d);color:#fff;padding:22px 26px;border-radius:14px;margin:20px 0;}
			.gvjd-header h1{margin:0;font-size:20px;color:#fff;}
			.gvjd-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:22px;margin-bottom:18px;}
			.gvjd-card h2{margin-top:0;font-size:15px;}
			.gvjd-field{margin-bottom:14px;}
			.gvjd-field label{display:block;font-weight:700;font-size:13px;margin-bottom:5px;color:#334155;}
			.gvjd-field input[type=text]{width:100%;max-width:260px;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;}
			.gvjd-btn{background:#111827;color:#fff !important;border:none;padding:10px 22px;border-radius:10px;font-weight:600;cursor:pointer;}
			.gvjd-preview{background:#f0fdf9;border:1px dashed #0e4037;border-radius:12px;padding:16px;font-size:15px;font-weight:700;color:#0e4037;}
			.gvjd-toggle-row{display:flex;align-items:center;gap:8px;margin-bottom:8px;}
		</style>

		<div class="gvjd-header"><h1>📅 تاریخ خودکار شمسی روی عنوان نوشته‌ها</h1></div>

		<?php if ( isset( $_GET['updated'] ) ) : ?><div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div><?php endif; ?>

		<div class="gvjd-card">
			<h2>پیش‌نمایش امروز</h2>
			<div class="gvjd-preview">مثال: «عنوان نوشته شما<?php echo esc_html( $s['separator'] ); ?><?php echo esc_html( gv_jdate_today_string() ); ?>»</div>
			<p style="color:#94a3b8;font-size:12.5px;margin-top:10px;">این متن کاملاً خودکار است؛ نیازی به ویرایش نوشته‌ها نیست. فردا همین‌جا و روی سایت به‌صورت خودکار به روز بعد تغییر می‌کند.</p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gv_jdate_save_settings">
			<?php wp_nonce_field( GV_JDATE_NONCE ); ?>

			<div class="gvjd-card">
				<label style="font-weight:700;"><input type="checkbox" name="enabled" <?php checked( $s['enabled'], 1 ); ?>> فعال‌سازی تاریخ خودکار شمسی</label>
			</div>

			<div class="gvjd-card">
				<h2>روی چه نوع محتوایی اعمال شود؟</h2>
				<?php foreach ( array( 'post' => '📝 نوشته‌ها', 'page' => '📄 برگه‌ها', 'product' => '🛍️ محصولات (ووکامرس)' ) as $pt => $label ) :
					if ( 'product' === $pt && ! post_type_exists( 'product' ) ) { continue; }
					?>
					<div class="gvjd-toggle-row">
						<input type="checkbox" name="post_types[<?php echo esc_attr( $pt ); ?>]" <?php checked( ! empty( $s['post_types'][ $pt ] ), true ); ?>>
						<label style="margin:0;"><?php echo esc_html( $label ); ?></label>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="gvjd-card">
				<h2>محل نمایش</h2>
				<label class="gvjd-toggle-row"><input type="radio" name="context" value="single" <?php checked( $s['context'], 'single' ); ?>> فقط داخل صفحه خود نوشته/برگه</label>
				<label class="gvjd-toggle-row"><input type="radio" name="context" value="archive_and_single" <?php checked( $s['context'], 'archive_and_single' ); ?>> هم داخل صفحه نوشته و هم لیست/آرشیو مطالب</label>
			</div>

			<div class="gvjd-card">
				<h2>جداکننده بین عنوان و تاریخ</h2>
				<div class="gvjd-field">
					<input type="text" name="separator" value="<?php echo esc_attr( $s['separator'] ); ?>" placeholder=" - ">
				</div>
			</div>

			<button type="submit" class="gvjd-btn">💾 ذخیره تنظیمات</button>
		</form>

		<p style="font-size:11.5px;color:#888;text-align:center;margin-top:24px;">ساخته و توسعه‌یافته توسط <strong>Groot Vision</strong></p>
	</div>
	<?php
}

/* ==========================================================================
   ۳) اعمال روی عنوان — با فیلتر the_title (کاملاً زنده و بدون ذخیره در دیتابیس)
   ========================================================================== */

add_filter( 'the_title', 'gv_jdate_append_to_title', 20, 2 );
function gv_jdate_append_to_title( $title, $post_id = 0 ) {
	if ( is_admin() || empty( $post_id ) ) { return $title; }

	$s = gv_jdate_get_settings();
	if ( empty( $s['enabled'] ) ) { return $title; }

	$post_type = get_post_type( $post_id );
	if ( empty( $s['post_types'][ $post_type ] ) ) { return $title; }

	// جلوگیری از دوباره‌کاری وقتی the_title چند بار برای همان پست فراخوانی می‌شود (مثل ویجت‌ها)
	if ( false !== strpos( $title, gv_jdate_today_string() ) ) { return $title; }

	if ( 'single' === $s['context'] ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) { return $title; }
	} else {
		// archive_and_single: فقط داخل حلقه اصلی محتوا (نه منو/ویجت/سایدبار)
		if ( ! in_the_loop() ) { return $title; }
	}

	return $title . $s['separator'] . gv_jdate_today_string();
}
