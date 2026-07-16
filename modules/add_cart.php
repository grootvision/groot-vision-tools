<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * ==========================================================================
 * کادر تبلیغاتی گوشه صفحه — ویژه و انحصاری «گروت ویژن»
 * موضوع: طراحی سایت اقساطی فقط ۵ میلیون تومان + تماس فوری
 * ==========================================================================
 */

define( 'CLX_BAR_OPT', 'clx_bar_settings' );
define( 'CLX_BAR_NONCE', 'clx_bar_nonce_action' );
define( 'CLX_BAR_BRAND', 'گروت ویژن' ); // برند انحصاری — در ویجت و اسکیمای سئو استفاده می‌شود

/* ==========================================================================
   ۱) مقادیر پیش‌فرض
   ========================================================================== */

function clx_bar_default_settings() {
	return array(
		'enabled'          => 1,

		// زمان‌بندی شمارش معکوس (اختیاری)
		'show_countdown'   => 1,
		'start_datetime'   => date( 'Y-m-d\TH:i' ),
		'end_datetime'     => date( 'Y-m-d\TH:i', strtotime( '+7 days' ) ),
		'expire_mode'      => 'message', // hide | message
		'expire_message'   => 'مهلت این پیشنهاد ویژه به پایان رسید. برای پیشنهاد جدید تماس بگیرید.',

		// متن‌های تبلیغ
		'badge_text'       => '🚀 پیشنهاد ویژه ' . CLX_BAR_BRAND,
		'headline'         => 'طراحی سایت حرفه‌ای فقط با ۵ میلیون تومان',
		'subheadline'      => 'به‌صورت کاملاً قسطی، بدون نگرانی برای هزینه اولیه!',
		'highlight_text'   => 'اقساط از ۵۰۰ هزار تومان',
		'secondary_text'   => 'پاسخگویی سریع حتی در تعطیلات ✅',

		// تماس
		'phone_raw'        => '', // مثال: 09123456789
		'phone_display'    => '', // اگر خالی باشد از phone_raw ساخته می‌شود
		'button_text'      => '☎ تماس فوری و رزرو نوبت',

		// ظاهر و رفتار
		'position'         => 'bottom-left', // bottom-left | bottom-right
		'closable'         => 1,
		'persian_digits'   => 1,
		'pulse_animation'  => 1,

		// رنگ‌ها
		'bg_color_1'       => '#0B1F26',
		'bg_color_2'       => '#0EA5A4',
		'text_color'       => '#FFF8EC',
		'accent_color'     => '#D4AF37',
		'digit_bg_color'   => 'rgba(255,255,255,.12)',

		// سئو
		'seo_schema'       => 1,
		'seo_service_name' => 'طراحی سایت اقساطی',
		'seo_description'  => 'طراحی سایت حرفه‌ای و اختصاصی به‌صورت اقساطی توسط تیم ' . CLX_BAR_BRAND . '، فقط با ۵ میلیون تومان.',
		'seo_price'        => '5000000',
		'seo_area_served'  => 'ایران',
	);
}

function clx_bar_get_settings() {
	$saved = get_option( CLX_BAR_OPT, array() );
	return wp_parse_args( $saved, clx_bar_default_settings() );
}

/* ==========================================================================
   ۲) منوی مدیریت در پنل وردپرس
   ========================================================================== */

add_action( 'admin_menu', 'clx_bar_admin_menu' );
function clx_bar_admin_menu() {
	add_submenu_page(
		'groot-vision-hub',                 // ← والد: هاب گروت ویژن
		'کادر تبلیغاتی گروت ویژن',
		'📣 کادر تبلیغاتی',
		'manage_options',
		'clx-discount-bar',
		'clx_bar_render_admin_page'
	);
}

add_action( 'admin_enqueue_scripts', 'clx_bar_admin_assets' );
function clx_bar_admin_assets( $hook ) {
	if ( strpos( $hook, 'clx-discount-bar' ) === false ) { return; }
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_add_inline_script( 'wp-color-picker', "jQuery(function($){
		$('.clx-color-field').wpColorPicker();

		// تب‌های پنل مدیریت
		$('.clx-tab-btn').on('click', function(e){
			e.preventDefault();
			var target = $(this).data('tab');
			$('.clx-tab-btn').removeClass('is-active');
			$(this).addClass('is-active');
			$('.clx-tab-panel').removeClass('is-active').hide();
			$('#clx-tab-' + target).addClass('is-active').show();
		});

		// پیش‌نمایش زنده متن‌ها
		function syncPreview(){
			$('#clx-prev-badge').text($('#clx_badge_text').val());
			$('#clx-prev-headline').text($('#clx_headline').val());
			$('#clx-prev-sub').text($('#clx_subheadline').val());
			$('#clx-prev-highlight').text($('#clx_highlight_text').val());
			$('#clx-prev-btn').text($('#clx_button_text').val());
			$('#clx-prev-secondary').text($('#clx_secondary_text').val());
		}
		$('#clx_badge_text,#clx_headline,#clx_subheadline,#clx_highlight_text,#clx_button_text,#clx_secondary_text').on('input', syncPreview);
		syncPreview();

		function syncColors(){
			$('#clx-preview-box').css({
				'background': 'linear-gradient(120deg,' + $('#clx_bg_color_1').val() + ',' + $('#clx_bg_color_2').val() + ')',
				'color': $('#clx_text_color').val()
			});
			$('.clx-preview-accent').css({'background': $('#clx_accent_color').val(), 'color':'#20140a'});
		}
		$('.clx-color-field').on('change', syncColors);
		setTimeout(syncColors, 300);
	});" );
}

/* ---- پردازش فرم ---- */

add_action( 'admin_post_clx_bar_save_settings', 'clx_bar_save_settings' );
function clx_bar_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( CLX_BAR_NONCE );

	$phone_raw = sanitize_text_field( $_POST['phone_raw'] ?? '' );

	$settings = array(
		'enabled'          => isset( $_POST['enabled'] ) ? 1 : 0,

		'show_countdown'   => isset( $_POST['show_countdown'] ) ? 1 : 0,
		'start_datetime'   => sanitize_text_field( $_POST['start_datetime'] ?? '' ),
		'end_datetime'     => sanitize_text_field( $_POST['end_datetime'] ?? '' ),
		'expire_mode'      => sanitize_text_field( $_POST['expire_mode'] ?? 'message' ),
		'expire_message'   => sanitize_text_field( $_POST['expire_message'] ?? '' ),

		'badge_text'       => sanitize_text_field( $_POST['badge_text'] ?? '' ),
		'headline'         => sanitize_text_field( $_POST['headline'] ?? '' ),
		'subheadline'      => sanitize_text_field( $_POST['subheadline'] ?? '' ),
		'highlight_text'   => sanitize_text_field( $_POST['highlight_text'] ?? '' ),
		'secondary_text'   => sanitize_text_field( $_POST['secondary_text'] ?? '' ),

		'phone_raw'        => $phone_raw,
		'phone_display'    => sanitize_text_field( $_POST['phone_display'] ?? '' ) ?: $phone_raw,
		'button_text'      => sanitize_text_field( $_POST['button_text'] ?? '' ),

		'position'         => in_array( $_POST['position'] ?? '', array( 'bottom-left', 'bottom-right' ), true ) ? $_POST['position'] : 'bottom-left',
		'closable'         => isset( $_POST['closable'] ) ? 1 : 0,
		'persian_digits'   => isset( $_POST['persian_digits'] ) ? 1 : 0,
		'pulse_animation'  => isset( $_POST['pulse_animation'] ) ? 1 : 0,

		'bg_color_1'       => sanitize_hex_color( $_POST['bg_color_1'] ?? '' ) ?: '#0B1F26',
		'bg_color_2'       => sanitize_hex_color( $_POST['bg_color_2'] ?? '' ) ?: '#0EA5A4',
		'text_color'       => sanitize_hex_color( $_POST['text_color'] ?? '' ) ?: '#FFF8EC',
		'accent_color'     => sanitize_hex_color( $_POST['accent_color'] ?? '' ) ?: '#D4AF37',
		'digit_bg_color'   => sanitize_text_field( $_POST['digit_bg_color'] ?? 'rgba(255,255,255,.12)' ),

		'seo_schema'       => isset( $_POST['seo_schema'] ) ? 1 : 0,
		'seo_service_name' => sanitize_text_field( $_POST['seo_service_name'] ?? '' ),
		'seo_description'  => sanitize_textarea_field( $_POST['seo_description'] ?? '' ),
		'seo_price'        => preg_replace( '/[^0-9]/', '', $_POST['seo_price'] ?? '' ),
		'seo_area_served'  => sanitize_text_field( $_POST['seo_area_served'] ?? '' ),
	);

	update_option( CLX_BAR_OPT, $settings );
	wp_safe_redirect( admin_url( 'admin.php?page=clx-discount-bar&updated=1' ) );
	exit;
}

/* ---- صفحه مدیریت (بازطراحی‌شده) ---- */

function clx_bar_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$s = clx_bar_get_settings();
	?>
	<div class="wrap" dir="rtl" style="font-family: Tahoma, sans-serif;">

		<style>
			.clx-admin-header{display:flex;align-items:center;justify-content:space-between;background:linear-gradient(120deg,#0B1F26,#0EA5A4);color:#fff;padding:22px 26px;border-radius:14px;margin:20px 0;box-shadow:0 6px 20px rgba(0,0,0,.12);}
			.clx-admin-header h1{margin:0;font-size:20px;display:flex;align-items:center;gap:10px;color:#fff;}
			.clx-admin-header span{opacity:.85;font-size:12.5px;}
			.clx-layout{display:flex;gap:20px;align-items:flex-start;max-width:1180px;}
			.clx-main-col{flex:1;min-width:0;}
			.clx-side-col{width:330px;position:sticky;top:32px;}
			.clx-card{background:#fff;border:1px solid #e2e4e7;border-radius:12px;padding:20px 22px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.04);}
			.clx-card h2{margin-top:0;font-size:15px;display:flex;align-items:center;gap:8px;border-bottom:1px solid #f0f0f1;padding-bottom:10px;}
			.clx-tabs{display:flex;gap:6px;margin-bottom:18px;flex-wrap:wrap;}
			.clx-tab-btn{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:8px 16px;cursor:pointer;font-size:13px;font-family:inherit;transition:.15s;}
			.clx-tab-btn.is-active{background:#0B1F26;color:#fff;border-color:#0B1F26;}
			.clx-tab-panel{display:none;}
			.clx-tab-panel.is-active{display:block;}
			.clx-field{margin-bottom:16px;}
			.clx-field label{display:block;font-weight:600;font-size:13px;margin-bottom:6px;}
			.clx-field .clx-hint{color:#666;font-size:11.5px;margin-top:4px;}
			.clx-field input[type="text"],.clx-field input[type="url"],.clx-field input[type="tel"],.clx-field input[type="datetime-local"],.clx-field textarea,.clx-field select{width:100%;max-width:460px;border-radius:6px;}
			.clx-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
			.clx-toggle-row{display:flex;align-items:center;gap:8px;background:#f6f7f7;padding:10px 14px;border-radius:8px;margin-bottom:10px;}
			.clx-toggle-row label{margin:0;font-weight:600;font-size:13px;}
			.clx-preview-wrap{background:#eef1f2;border-radius:14px;padding:26px 16px;min-height:340px;position:relative;overflow:hidden;}
			.clx-preview-note{font-size:11px;color:#666;text-align:center;margin-top:10px;}
			#clx-preview-box{position:absolute;bottom:16px;left:16px;width:270px;border-radius:16px;padding:16px 16px 14px;box-shadow:0 10px 30px rgba(0,0,0,.28);font-size:13px;}
			.clx-preview-badge{display:inline-block;font-size:10.5px;font-weight:700;background:rgba(255,255,255,.15);padding:3px 9px;border-radius:20px;margin-bottom:8px;}
			.clx-preview-headline{font-weight:800;font-size:14.5px;line-height:1.5;margin-bottom:4px;}
			.clx-preview-sub{font-size:11.5px;opacity:.85;margin-bottom:8px;}
			.clx-preview-accent{display:block;text-align:center;font-weight:800;font-size:12.5px;padding:8px 10px;border-radius:9px;margin-top:6px;}
			.clx-preview-secondary{font-size:10px;opacity:.75;text-align:center;margin-top:6px;}
			.clx-preview-highlight{display:inline-block;font-size:10.5px;border:1px dashed rgba(255,255,255,.5);padding:2px 8px;border-radius:20px;margin-bottom:8px;}
			.clx-credit{font-size:11.5px;color:#888;text-align:center;margin-top:24px;}
		</style>

		<div class="clx-admin-header">
			<h1>📣 کادر تبلیغاتی گوشه صفحه — <?php echo esc_html( CLX_BAR_BRAND ); ?></h1>
			<span>مخصوص تبلیغ طراحی سایت اقساطی و تماس فوری</span>
		</div>

		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>✅ تنظیمات با موفقیت ذخیره شد.</p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="clx_bar_save_settings">
			<?php wp_nonce_field( CLX_BAR_NONCE ); ?>

			<div class="clx-layout">
				<div class="clx-main-col">

					<div class="clx-tabs">
						<button type="button" class="clx-tab-btn is-active" data-tab="general">⚙️ عمومی</button>
						<button type="button" class="clx-tab-btn" data-tab="content">✏️ متن‌ها و تماس</button>
						<button type="button" class="clx-tab-btn" data-tab="timer">⏱️ شمارش معکوس</button>
						<button type="button" class="clx-tab-btn" data-tab="style">🎨 ظاهر</button>
						<button type="button" class="clx-tab-btn" data-tab="seo">🔍 سئو</button>
					</div>

					<!-- تب عمومی -->
					<div class="clx-tab-panel is-active" id="clx-tab-general">
						<div class="clx-card">
							<h2>وضعیت نمایش</h2>
							<div class="clx-toggle-row"><input type="checkbox" name="enabled" <?php checked( $s['enabled'], 1 ); ?>><label>نمایش کادر در کل سایت</label></div>
							<div class="clx-toggle-row"><input type="checkbox" name="closable" <?php checked( $s['closable'], 1 ); ?>><label>کاربر بتواند کادر را ببندد (تا پایان همان بازدید)</label></div>
							<div class="clx-toggle-row"><input type="checkbox" name="pulse_animation" <?php checked( $s['pulse_animation'], 1 ); ?>><label>افکت ضربان (پالس) روی دکمه تماس برای جلب توجه</label></div>
							<div class="clx-field">
								<label>موقعیت کادر روی صفحه</label>
								<select name="position">
									<option value="bottom-left" <?php selected( $s['position'], 'bottom-left' ); ?>>پایین چپ (پیش‌فرض)</option>
									<option value="bottom-right" <?php selected( $s['position'], 'bottom-right' ); ?>>پایین راست</option>
								</select>
							</div>
						</div>
					</div>

					<!-- تب متن‌ها -->
					<div class="clx-tab-panel" id="clx-tab-content">
						<div class="clx-card">
							<h2>متن‌های تبلیغ</h2>
							<div class="clx-field">
								<label>بج بالای کادر</label>
								<input type="text" id="clx_badge_text" name="badge_text" value="<?php echo esc_attr( $s['badge_text'] ); ?>">
							</div>
							<div class="clx-field">
								<label>عنوان اصلی (پیشنهاد ویژه)</label>
								<input type="text" id="clx_headline" name="headline" value="<?php echo esc_attr( $s['headline'] ); ?>">
							</div>
							<div class="clx-field">
								<label>زیرعنوان</label>
								<input type="text" id="clx_subheadline" name="subheadline" value="<?php echo esc_attr( $s['subheadline'] ); ?>">
							</div>
							<div class="clx-field">
								<label>بج کوچک (مثلاً شرایط اقساط)</label>
								<input type="text" id="clx_highlight_text" name="highlight_text" value="<?php echo esc_attr( $s['highlight_text'] ); ?>">
								<div class="clx-hint">خالی بگذارید تا نمایش داده نشود.</div>
							</div>
							<div class="clx-field">
								<label>متن ریز زیر دکمه</label>
								<input type="text" id="clx_secondary_text" name="secondary_text" value="<?php echo esc_attr( $s['secondary_text'] ); ?>">
							</div>
						</div>

						<div class="clx-card">
							<h2>شماره تماس و دکمه</h2>
							<div class="clx-grid2">
								<div class="clx-field">
									<label>شماره تماس (فقط عدد، بدون فاصله) *</label>
									<input type="tel" name="phone_raw" value="<?php echo esc_attr( $s['phone_raw'] ); ?>" placeholder="09123456789" required>
									<div class="clx-hint">با لمس دکمه، مستقیم تماس گرفته می‌شود.</div>
								</div>
								<div class="clx-field">
									<label>نمایش شماره (اختیاری)</label>
									<input type="text" name="phone_display" value="<?php echo esc_attr( $s['phone_display'] ); ?>" placeholder="0912-345-6789">
								</div>
							</div>
							<div class="clx-field">
								<label>متن دکمه تماس</label>
								<input type="text" id="clx_button_text" name="button_text" value="<?php echo esc_attr( $s['button_text'] ); ?>">
							</div>
						</div>
					</div>

					<!-- تب شمارش معکوس -->
					<div class="clx-tab-panel" id="clx-tab-timer">
						<div class="clx-card">
							<div class="clx-toggle-row"><input type="checkbox" name="show_countdown" <?php checked( $s['show_countdown'], 1 ); ?>><label>نمایش شمارش معکوس روی کادر</label></div>
							<div class="clx-grid2">
								<div class="clx-field">
									<label>شروع شمارش</label>
									<input type="datetime-local" name="start_datetime" value="<?php echo esc_attr( $s['start_datetime'] ); ?>">
								</div>
								<div class="clx-field">
									<label>پایان پیشنهاد</label>
									<input type="datetime-local" name="end_datetime" value="<?php echo esc_attr( $s['end_datetime'] ); ?>">
								</div>
							</div>
							<div class="clx-field">
								<label>پس از پایان زمان</label>
								<select name="expire_mode">
									<option value="message" <?php selected( $s['expire_mode'], 'message' ); ?>>نمایش پیام جایگزین</option>
									<option value="hide" <?php selected( $s['expire_mode'], 'hide' ); ?>>مخفی‌شدن کامل کادر</option>
								</select>
							</div>
							<div class="clx-field">
								<label>متن پیام پس از پایان</label>
								<input type="text" name="expire_message" value="<?php echo esc_attr( $s['expire_message'] ); ?>">
							</div>
							<div class="clx-toggle-row"><input type="checkbox" name="persian_digits" <?php checked( $s['persian_digits'], 1 ); ?>><label>نمایش اعداد شمارش‌گر به فارسی (۰۱۲۳...)</label></div>
						</div>
					</div>

					<!-- تب ظاهر -->
					<div class="clx-tab-panel" id="clx-tab-style">
						<div class="clx-card">
							<h2>رنگ‌بندی کادر</h2>
							<div class="clx-grid2">
								<div class="clx-field"><label>رنگ گرادیان (شروع)</label><input type="text" class="clx-color-field" id="clx_bg_color_1" name="bg_color_1" value="<?php echo esc_attr( $s['bg_color_1'] ); ?>"></div>
								<div class="clx-field"><label>رنگ گرادیان (پایان)</label><input type="text" class="clx-color-field" id="clx_bg_color_2" name="bg_color_2" value="<?php echo esc_attr( $s['bg_color_2'] ); ?>"></div>
								<div class="clx-field"><label>رنگ متن</label><input type="text" class="clx-color-field" id="clx_text_color" name="text_color" value="<?php echo esc_attr( $s['text_color'] ); ?>"></div>
								<div class="clx-field"><label>رنگ تأکیدی (دکمه تماس)</label><input type="text" class="clx-color-field" id="clx_accent_color" name="accent_color" value="<?php echo esc_attr( $s['accent_color'] ); ?>"></div>
							</div>
							<input type="hidden" name="digit_bg_color" value="<?php echo esc_attr( $s['digit_bg_color'] ); ?>">
						</div>
					</div>

					<!-- تب سئو -->
					<div class="clx-tab-panel" id="clx-tab-seo">
						<div class="clx-card">
							<h2>بهینه‌سازی برای موتورهای جستجو</h2>
							<div class="clx-toggle-row"><input type="checkbox" name="seo_schema" <?php checked( $s['seo_schema'], 1 ); ?>><label>افزودن دیتای ساختاریافته Schema.org (JSON-LD)</label></div>
							<div class="clx-field">
								<label>نام سرویس (برای موتور جستجو)</label>
								<input type="text" name="seo_service_name" value="<?php echo esc_attr( $s['seo_service_name'] ); ?>">
							</div>
							<div class="clx-field">
								<label>توضیحات سرویس</label>
								<textarea name="seo_description" rows="3"><?php echo esc_textarea( $s['seo_description'] ); ?></textarea>
							</div>
							<div class="clx-grid2">
								<div class="clx-field">
									<label>قیمت (تومان، فقط عدد)</label>
									<input type="text" name="seo_price" value="<?php echo esc_attr( $s['seo_price'] ); ?>">
								</div>
								<div class="clx-field">
									<label>محدوده جغرافیایی خدمات</label>
									<input type="text" name="seo_area_served" value="<?php echo esc_attr( $s['seo_area_served'] ); ?>">
								</div>
							</div>
							<div class="clx-hint">این داده به صورت نامرئی در انتهای صفحه درج می‌شود و به گوگل کمک می‌کند خدمت «طراحی سایت اقساطی» شما را بهتر بشناسد؛ روی ظاهر سایت اثری ندارد.</div>
						</div>
					</div>

					<?php submit_button( '💾 ذخیره تنظیمات' ); ?>
				</div>

				<!-- ستون پیش‌نمایش -->
				<div class="clx-side-col">
					<div class="clx-card">
						<h2>👁️ پیش‌نمایش زنده</h2>
						<div class="clx-preview-wrap">
							<div id="clx-preview-box">
								<span class="clx-preview-badge" id="clx-prev-badge"><?php echo esc_html( $s['badge_text'] ); ?></span><br>
								<span class="clx-preview-highlight" id="clx-prev-highlight"><?php echo esc_html( $s['highlight_text'] ); ?></span>
								<div class="clx-preview-headline" id="clx-prev-headline"><?php echo esc_html( $s['headline'] ); ?></div>
								<div class="clx-preview-sub" id="clx-prev-sub"><?php echo esc_html( $s['subheadline'] ); ?></div>
								<span class="clx-preview-accent clx-preview-accent" id="clx-prev-btn"><?php echo esc_html( $s['button_text'] ); ?></span>
								<div class="clx-preview-secondary" id="clx-prev-secondary"><?php echo esc_html( $s['secondary_text'] ); ?></div>
							</div>
						</div>
						<div class="clx-preview-note">این پیش‌نمایش تقریبی است، طرح واقعی در سایت انیمیشن و افکت هم دارد.</div>
					</div>
				</div>
			</div>
		</form>

		<p class="clx-credit">ساخته شده برای <?php echo esc_html( CLX_BAR_BRAND ); ?> |گروت ویژن| اینستاگرام: groot vision </p>
	</div>
	<?php
}

/* ==========================================================================
   ۳) نمایش کادر در سمت کاربر (فرانت‌اند)
   ========================================================================== */

add_action( 'wp_footer', 'clx_bar_render_frontend' );
function clx_bar_render_frontend() {
	if ( is_admin() ) { return; }

	$s = clx_bar_get_settings();
	if ( empty( $s['enabled'] ) ) { return; }
	if ( empty( $s['phone_raw'] ) ) { return; } // بدون شماره تماس، نمایش بی‌معناست

	$phone_href    = 'tel:' . preg_replace( '/[^0-9+]/', '', $s['phone_raw'] );
	$phone_display = ! empty( $s['phone_display'] ) ? $s['phone_display'] : $s['phone_raw'];

	$show_timer = ! empty( $s['show_countdown'] );
	$start_ts   = $show_timer ? strtotime( $s['start_datetime'] ) : 0;
	$end_ts     = $show_timer ? strtotime( $s['end_datetime'] ) : 0;
	if ( $show_timer && ( ! $start_ts || ! $end_ts || $end_ts <= $start_ts ) ) {
		$show_timer = false;
	}

	$side_prop = ( $s['position'] === 'bottom-right' ) ? 'right' : 'left';
	?>
	<style>
	:root{
		--clx-bar-bg1:<?php echo esc_html( $s['bg_color_1'] ); ?>;
		--clx-bar-bg2:<?php echo esc_html( $s['bg_color_2'] ); ?>;
		--clx-bar-text:<?php echo esc_html( $s['text_color'] ); ?>;
		--clx-bar-accent:<?php echo esc_html( $s['accent_color'] ); ?>;
		--clx-bar-digit-bg:<?php echo esc_html( $s['digit_bg_color'] ); ?>;
	}
	#clx-bar-wrap{
		position:fixed;bottom:18px;<?php echo esc_html( $side_prop ); ?>:18px;z-index:999999;
		width:300px;max-width:calc(100vw - 24px);
		font-family:inherit;
		background:linear-gradient(135deg, var(--clx-bar-bg1) 0%, var(--clx-bar-bg2) 140%);
		color:var(--clx-bar-text);
		border-radius:18px;
		box-shadow:0 14px 40px rgba(0,0,0,.35);
		padding:16px 18px 14px;
		direction:rtl;
		opacity:0;
		transform:translateY(24px) scale(.96);
		transition:opacity .5s ease, transform .5s cubic-bezier(.2,.9,.3,1.1);
	}
	#clx-bar-wrap.clx-bar-show{opacity:1;transform:translateY(0) scale(1);}
	#clx-bar-badge{
		display:inline-block;font-size:11px;font-weight:800;
		background:rgba(255,255,255,.16);
		padding:4px 11px;border-radius:20px;margin-bottom:9px;
	}
	#clx-bar-highlight{
		display:inline-block;font-size:11px;font-weight:700;
		border:1px dashed var(--clx-bar-accent);
		color:var(--clx-bar-accent);
		padding:2px 10px;border-radius:20px;margin-<?php echo $side_prop === 'left' ? 'right' : 'left'; ?>:6px;margin-bottom:9px;
	}
	#clx-bar-headline{
		font-size:16px;font-weight:800;line-height:1.55;margin-bottom:5px;
	}
	#clx-bar-sub{
		font-size:12.5px;opacity:.88;margin-bottom:11px;line-height:1.6;
	}
	#clx-bar-timer{
		display:flex;align-items:center;gap:5px;direction:ltr;margin-bottom:12px;
	}
	.clx-bar-tbox{display:flex;flex-direction:column;align-items:center;min-width:32px;}
	.clx-bar-tnum{
		background:var(--clx-bar-digit-bg);
		border:1px solid rgba(255,255,255,.18);
		border-radius:7px;padding:3px 6px;
		font-size:14px;font-weight:700;min-width:24px;text-align:center;
		font-variant-numeric:tabular-nums;
	}
	.clx-bar-tlabel{font-size:9px;opacity:.7;margin-top:2px;direction:rtl;}
	.clx-bar-tsep{opacity:.6;font-weight:700;}
	#clx-bar-btn{
		display:flex;align-items:center;justify-content:center;gap:6px;
		background:var(--clx-bar-accent);
		color:#20140a;font-weight:800;font-size:14px;
		padding:11px 14px;border-radius:12px;
		text-decoration:none;width:100%;box-sizing:border-box;
		transition:transform .15s ease, filter .15s ease;
	}
	#clx-bar-btn:hover{transform:translateY(-2px);filter:brightness(1.07);color:#20140a;}
	#clx-bar-secondary{
		font-size:10.5px;opacity:.75;text-align:center;margin-top:7px;
	}
	#clx-bar-close{
		position:absolute;top:-9px;<?php echo esc_html( $side_prop === 'left' ? 'left' : 'right' ); ?>:-9px;
		width:24px;height:24px;border-radius:50%;
		background:#fff;border:1px solid #ddd;color:#333;cursor:pointer;
		display:flex;align-items:center;justify-content:center;font-size:12px;line-height:1;
		box-shadow:0 2px 8px rgba(0,0,0,.25);
	}
	#clx-bar-close:hover{background:#f2f2f2;}
	@keyframes clxPulseRing{
		0%{box-shadow:0 0 0 0 rgba(212,175,55,.55);}
		70%{box-shadow:0 0 0 14px rgba(212,175,55,0);}
		100%{box-shadow:0 0 0 0 rgba(212,175,55,0);}
	}
	#clx-bar-wrap.clx-pulse-on #clx-bar-btn{animation:clxPulseRing 2.1s infinite;}
	@media (max-width:480px){
		#clx-bar-wrap{width:calc(100vw - 20px);bottom:10px;<?php echo esc_html( $side_prop ); ?>:10px;padding:14px 16px 12px;}
		#clx-bar-headline{font-size:15px;}
	}
	</style>

	<aside id="clx-bar-wrap"<?php echo ! empty( $s['pulse_animation'] ) ? ' class="clx-pulse-on"' : ''; ?> role="complementary" aria-label="<?php echo esc_attr( $s['headline'] ); ?>">

		<?php if ( ! empty( $s['closable'] ) ) : ?>
			<div id="clx-bar-close" title="بستن" role="button" aria-label="بستن پیشنهاد">✕</div>
		<?php endif; ?>

		<?php if ( ! empty( $s['badge_text'] ) ) : ?>
			<span id="clx-bar-badge"><?php echo esc_html( $s['badge_text'] ); ?></span>
		<?php endif; ?>

		<?php if ( ! empty( $s['highlight_text'] ) ) : ?>
			<span id="clx-bar-highlight"><?php echo esc_html( $s['highlight_text'] ); ?></span>
		<?php endif; ?>

		<div id="clx-bar-headline"><?php echo esc_html( $s['headline'] ); ?></div>
		<?php if ( ! empty( $s['subheadline'] ) ) : ?>
			<div id="clx-bar-sub"><?php echo esc_html( $s['subheadline'] ); ?></div>
		<?php endif; ?>

		<?php if ( $show_timer ) : ?>
		<div id="clx-bar-timer">
			<div class="clx-bar-tbox"><div class="clx-bar-tnum" data-unit="d">00</div><div class="clx-bar-tlabel">روز</div></div>
			<span class="clx-bar-tsep">:</span>
			<div class="clx-bar-tbox"><div class="clx-bar-tnum" data-unit="h">00</div><div class="clx-bar-tlabel">ساعت</div></div>
			<span class="clx-bar-tsep">:</span>
			<div class="clx-bar-tbox"><div class="clx-bar-tnum" data-unit="m">00</div><div class="clx-bar-tlabel">دقیقه</div></div>
			<span class="clx-bar-tsep">:</span>
			<div class="clx-bar-tbox"><div class="clx-bar-tnum" data-unit="s">00</div><div class="clx-bar-tlabel">ثانیه</div></div>
		</div>
		<?php endif; ?>

		<a id="clx-bar-btn" href="<?php echo esc_attr( $phone_href ); ?>" itemprop="telephone" onclick="if(window.dataLayer){window.dataLayer.push({event:'clx_phone_click', phone:'<?php echo esc_js( $phone_display ); ?>'});}">
			<?php echo esc_html( $s['button_text'] ); ?>
		</a>
		<?php if ( ! empty( $s['secondary_text'] ) ) : ?>
			<div id="clx-bar-secondary"><?php echo esc_html( $s['secondary_text'] ); ?> — <bdi><?php echo esc_html( $phone_display ); ?></bdi></div>
		<?php endif; ?>

	</aside>

	<?php if ( $show_timer ) : ?>
	<script>
	(function(){
		var startTs   = <?php echo (int) $start_ts; ?> * 1000;
		var endTs     = <?php echo (int) $end_ts; ?> * 1000;
		var expireMode    = <?php echo wp_json_encode( $s['expire_mode'] ); ?>;
		var expireMessage = <?php echo wp_json_encode( $s['expire_message'] ); ?>;
		var usePersianDigits = <?php echo $s['persian_digits'] ? 'true' : 'false'; ?>;

		var wrap = document.getElementById('clx-bar-wrap');

		function toPersianDigits(str){
			if (!usePersianDigits) return str;
			var fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
			return String(str).replace(/[0-9]/g, function(d){ return fa[d]; });
		}
		function pad(n){ return (n<10?'0':'')+n; }

		function renderExpired(){
			if (expireMode === 'hide') { wrap.style.display = 'none'; return; }
			var headline = document.getElementById('clx-bar-headline');
			if (headline) headline.textContent = expireMessage;
			var timer = document.getElementById('clx-bar-timer');
			if (timer) timer.style.display = 'none';
		}

		function tick(){
			var now = Date.now();
			if (now >= endTs) { clearInterval(timerHandle); renderExpired(); return; }
			var remaining = endTs - now;
			var d = Math.floor(remaining / 86400000);
			var h = Math.floor((remaining % 86400000) / 3600000);
			var m = Math.floor((remaining % 3600000) / 60000);
			var s = Math.floor((remaining % 60000) / 1000);
			var elD = document.querySelector('[data-unit="d"]');
			var elH = document.querySelector('[data-unit="h"]');
			var elM = document.querySelector('[data-unit="m"]');
			var elS = document.querySelector('[data-unit="s"]');
			if (elD) elD.textContent = toPersianDigits(pad(d));
			if (elH) elH.textContent = toPersianDigits(pad(h));
			if (elM) elM.textContent = toPersianDigits(pad(m));
			if (elS) elS.textContent = toPersianDigits(pad(s));
		}

		var timerHandle = setInterval(tick, 1000);
		tick();
	})();
	</script>
	<?php endif; ?>

	<script>
	(function(){
		var wrap = document.getElementById('clx-bar-wrap');
		var closeBtn = document.getElementById('clx-bar-close');

		if (sessionStorage.getItem('clx_bar_closed')) { wrap.style.display = 'none'; return; }

		requestAnimationFrame(function(){
			setTimeout(function(){ wrap.classList.add('clx-bar-show'); }, 400);
		});

		if (closeBtn) {
			closeBtn.addEventListener('click', function(){
				wrap.classList.remove('clx-bar-show');
				setTimeout(function(){ wrap.style.display = 'none'; }, 350);
				sessionStorage.setItem('clx_bar_closed', '1');
			});
		}
	})();
	</script>

	<?php
	// ---- دیتای ساختاریافته سئو (Schema.org / JSON-LD) ----
	if ( ! empty( $s['seo_schema'] ) ) {
		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Service',
			'serviceType' => $s['seo_service_name'],
			'name'        => $s['seo_service_name'] . ' | ' . CLX_BAR_BRAND,
			'description' => $s['seo_description'],
			'provider'    => array(
				'@type' => 'Organization',
				'name'  => CLX_BAR_BRAND,
				'telephone' => $s['phone_raw'],
			),
			'areaServed'  => $s['seo_area_served'],
			'offers'      => array(
				'@type'         => 'Offer',
				'price'         => $s['seo_price'],
				'priceCurrency' => 'IRR',
				'availability'  => 'https://schema.org/InStock',
			),
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}