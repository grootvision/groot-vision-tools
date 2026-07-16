<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * ==========================================================
 *  Groot Vision — استایل جدول‌ها و ساده‌سازی تسویه‌حساب
 *  ۱) ۳ تم آماده برای استایل تمام جدول‌های سایت + حالت سفارشی
 *  ۲) روشن/خاموش کردن فیلدهای فرم تسویه‌حساب ووکامرس
 * ==========================================================
 */
define( 'GV_TBL_OPT', 'gv_table_style_settings' );
define( 'GV_TBL_NONCE', 'gv_tbl_nonce_action' );

/* ==========================================================================
   ۱) تنظیمات پیش‌فرض و تم‌های آماده
   ========================================================================== */

function gv_tbl_default_settings() {
	return array(
		'enabled'        => 1,
		'theme'          => 'modern_blue', // modern_blue | dark_elegant | minimal_green | custom
		'custom_colors'  => array(
			'header_bg'    => '#2563eb',
			'header_text'  => '#ffffff',
			'border_color' => '#e2e8f0',
			'row_alt_bg'   => '#f8fafc',
			'row_hover_bg' => '#eff6ff',
			'text_color'   => '#1e293b',
			'radius'       => 10,
		),
		// فیلدهای فرم تسویه‌حساب ووکامرس (پیش‌فرض همه روشن)
		'checkout_fields' => gv_tbl_default_checkout_fields(),
	);
}

function gv_tbl_default_checkout_fields() {
	return array(
		'company'   => 1,
		'address_1' => 1, // خیابان / آدرس اصلی
		'address_2' => 1, // خیابان خط دوم (پلاک/واحد)
		'city'      => 1, // شهر
		'country'   => 1,
		'state'     => 1, // استان
		'postcode'  => 1, // کد پستی
		'phone'     => 1,
		'email'     => 1, // ایمیل
		'order_notes' => 1,
		'ship_to_different' => 1,
	);
}

/** پالت رنگی ۳ تم آماده */
function gv_tbl_presets() {
	return array(
		'modern_blue' => array(
			'label'        => 'مدرن آبی',
			'desc'         => 'استایل تمیز و کسب‌وکاری با هدر آبی و ردیف‌های راه‌راه ملایم',
			'header_bg'    => '#2563eb',
			'header_text'  => '#ffffff',
			'border_color' => '#e2e8f0',
			'row_alt_bg'   => '#f8fafc',
			'row_hover_bg' => '#eff6ff',
			'text_color'   => '#1e293b',
			'radius'       => 10,
		),
		'dark_elegant' => array(
			'label'        => 'شیک تیره',
			'desc'         => 'استایل تیره و لوکس، مناسب سایت‌های فروشگاهی و حرفه‌ای',
			'header_bg'    => '#111827',
			'header_text'  => '#f9fafb',
			'border_color' => '#1f2937',
			'row_alt_bg'   => '#1f2937',
			'row_hover_bg' => '#374151',
			'text_color'   => '#e5e7eb',
			'radius'       => 12,
		),
		'minimal_green' => array(
			'label'        => 'مینیمال سبز',
			'desc'         => 'ساده و تازه، مناسب سایت‌های محتوایی و فروشگاه‌های ارگانیک',
			'header_bg'    => '#059669',
			'header_text'  => '#ffffff',
			'border_color' => '#d1fae5',
			'row_alt_bg'   => '#f0fdf4',
			'row_hover_bg' => '#dcfce7',
			'text_color'   => '#14532d',
			'radius'       => 8,
		),
	);
}

function gv_tbl_get_settings() {
	$saved    = get_option( GV_TBL_OPT, array() );
	$defaults = gv_tbl_default_settings();
	$settings = wp_parse_args( $saved, $defaults );
	if ( isset( $saved['custom_colors'] ) && is_array( $saved['custom_colors'] ) ) {
		$settings['custom_colors'] = wp_parse_args( $saved['custom_colors'], $defaults['custom_colors'] );
	}
	if ( isset( $saved['checkout_fields'] ) && is_array( $saved['checkout_fields'] ) ) {
		$settings['checkout_fields'] = wp_parse_args( $saved['checkout_fields'], $defaults['checkout_fields'] );
	}
	return $settings;
}

/** رنگ‌های فعال (بر اساس تم انتخابی یا سفارشی) */
function gv_tbl_get_active_palette( $settings ) {
	if ( 'custom' === $settings['theme'] ) {
		return $settings['custom_colors'];
	}
	$presets = gv_tbl_presets();
	return isset( $presets[ $settings['theme'] ] ) ? $presets[ $settings['theme'] ] : $presets['modern_blue'];
}

/* ==========================================================================
   ۲) منوی مدیریت
   ========================================================================== */

add_action( 'admin_menu', 'gv_tbl_admin_menu' );
function gv_tbl_admin_menu() {
	add_submenu_page(
		'groot-vision-hub',
		'استایل جدول‌ها | Groot Vision',
		'🎨 استایل جدول‌ها',
		'manage_options',
		'gv-table-style',
		'gv_tbl_render_admin_page'
	);
}

add_action( 'admin_enqueue_scripts', 'gv_tbl_admin_assets' );
function gv_tbl_admin_assets( $hook ) {
	if ( strpos( $hook, 'gv-table-style' ) === false ) { return; }
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_add_inline_script( 'wp-color-picker', "jQuery(function($){
		$('.gvtbl-color-field').wpColorPicker();

		$('.gvtbl-tab-btn').on('click', function(e){
			e.preventDefault();
			var target = $(this).data('tab');
			$('.gvtbl-tab-btn').removeClass('is-active');
			$(this).addClass('is-active');
			$('.gvtbl-tab-panel').removeClass('is-active').hide();
			$('#gvtbl-tab-' + target).addClass('is-active').show();
		});

		$('input[name=\"theme\"]').on('change', function(){
			if ($(this).val() === 'custom') {
				$('#gvtbl-custom-box').slideDown(150);
			} else {
				$('#gvtbl-custom-box').slideUp(150);
			}
		});
	});" );
}

add_action( 'admin_post_gv_tbl_save_settings', 'gv_tbl_save_settings' );
function gv_tbl_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_TBL_NONCE );

	$defaults = gv_tbl_default_settings();

	$theme = isset( $_POST['theme'] ) ? sanitize_key( $_POST['theme'] ) : 'modern_blue';
	$valid_themes = array_merge( array_keys( gv_tbl_presets() ), array( 'custom' ) );
	if ( ! in_array( $theme, $valid_themes, true ) ) { $theme = 'modern_blue'; }

	$custom_colors = array(
		'header_bg'    => sanitize_hex_color( $_POST['custom_colors']['header_bg'] ?? '' ) ?: $defaults['custom_colors']['header_bg'],
		'header_text'  => sanitize_hex_color( $_POST['custom_colors']['header_text'] ?? '' ) ?: $defaults['custom_colors']['header_text'],
		'border_color' => sanitize_hex_color( $_POST['custom_colors']['border_color'] ?? '' ) ?: $defaults['custom_colors']['border_color'],
		'row_alt_bg'   => sanitize_hex_color( $_POST['custom_colors']['row_alt_bg'] ?? '' ) ?: $defaults['custom_colors']['row_alt_bg'],
		'row_hover_bg' => sanitize_hex_color( $_POST['custom_colors']['row_hover_bg'] ?? '' ) ?: $defaults['custom_colors']['row_hover_bg'],
		'text_color'   => sanitize_hex_color( $_POST['custom_colors']['text_color'] ?? '' ) ?: $defaults['custom_colors']['text_color'],
		'radius'       => isset( $_POST['custom_colors']['radius'] ) ? max( 0, min( 30, intval( $_POST['custom_colors']['radius'] ) ) ) : 10,
	);

	$checkout_fields = array();
	foreach ( array_keys( $defaults['checkout_fields'] ) as $key ) {
		$checkout_fields[ $key ] = isset( $_POST['checkout_fields'][ $key ] ) ? 1 : 0;
	}

	$settings = array(
		'enabled'         => isset( $_POST['enabled'] ) ? 1 : 0,
		'theme'           => $theme,
		'custom_colors'   => $custom_colors,
		'checkout_fields' => $checkout_fields,
	);

	update_option( GV_TBL_OPT, $settings );
	wp_safe_redirect( admin_url( 'admin.php?page=gv-table-style&updated=1&tab=' . ( isset( $_POST['current_tab'] ) ? sanitize_key( $_POST['current_tab'] ) : 'theme' ) ) );
	exit;
}

function gv_tbl_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$s       = gv_tbl_get_settings();
	$presets = gv_tbl_presets();
	$tab     = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'theme';
	$has_wc  = class_exists( 'WooCommerce' );
	?>
	<div class="wrap" dir="rtl" style="font-family: Tahoma, sans-serif;">
		<style>
			.gvtbl-header{display:flex;align-items:center;justify-content:space-between;background:linear-gradient(120deg,#7c3aed,#a855f7);color:#fff;padding:22px 26px;border-radius:14px;margin:20px 0;box-shadow:0 6px 20px rgba(0,0,0,.12);}
			.gvtbl-header h1{margin:0;font-size:20px;color:#fff;}
			.gvtbl-header span{opacity:.85;font-size:12.5px;}
			.gvtbl-tabs{display:flex;gap:6px;margin-bottom:18px;flex-wrap:wrap;}
			.gvtbl-tab-btn{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:9px 18px;cursor:pointer;font-size:13px;font-family:inherit;font-weight:600;}
			.gvtbl-tab-btn.is-active{background:#7c3aed;color:#fff;border-color:#7c3aed;}
			.gvtbl-tab-panel{display:none;}
			.gvtbl-tab-panel.is-active{display:block;}
			.gvtbl-card{background:#fff;border:1px solid #e2e4e7;border-radius:12px;padding:20px 22px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.04);max-width:860px;}
			.gvtbl-card h2{margin-top:0;font-size:15px;border-bottom:1px solid #f0f0f1;padding-bottom:10px;}
			.gvtbl-hint{color:#666;font-size:12px;margin-top:6px;line-height:1.9;}
			.gvtbl-theme-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
			@media(max-width:900px){.gvtbl-theme-grid{grid-template-columns:1fr;}}
			.gvtbl-theme-card{border:2px solid #e2e8f0;border-radius:12px;padding:14px;cursor:pointer;position:relative;transition:.15s;}
			.gvtbl-theme-card:hover{border-color:#c4b5fd;}
			.gvtbl-theme-card input{position:absolute;top:12px;left:12px;}
			.gvtbl-theme-card.is-checked{border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.15);}
			.gvtbl-theme-preview{border-radius:8px;overflow:hidden;margin-bottom:10px;border:1px solid #e2e8f0;}
			.gvtbl-theme-preview table{width:100%;border-collapse:collapse;font-size:11px;}
			.gvtbl-theme-preview th{padding:6px 8px;text-align:right;}
			.gvtbl-theme-preview td{padding:6px 8px;text-align:right;border-top:1px solid;}
			.gvtbl-theme-card label{font-weight:700;font-size:13px;display:block;}
			.gvtbl-theme-card p{font-size:11.5px;color:#64748b;margin:4px 0 0;}
			.gvtbl-color-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
			@media(max-width:800px){.gvtbl-color-grid{grid-template-columns:1fr 1fr;}}
			.gvtbl-color-item label{display:block;font-size:12.5px;font-weight:600;margin-bottom:6px;}
			.gvtbl-toggle-row{display:flex;align-items:center;gap:8px;background:#f6f7f7;padding:10px 14px;border-radius:8px;margin-bottom:10px;}
			.gvtbl-toggle-row label{margin:0;font-weight:600;font-size:13px;}
			.gvtbl-note{background:#f5f3ff;border:1px solid #c4b5fd;color:#5b21b6;padding:12px 16px;border-radius:10px;font-size:12.5px;line-height:1.9;margin-bottom:18px;max-width:860px;}
		</style>

		<div class="gvtbl-header">
			<h1>🎨 استایل جدول‌ها و تسویه‌حساب — Groot Vision</h1>
			<span>ظاهر یکپارچه برای همه جدول‌های سایت + فرم تسویه‌حساب کوتاه‌تر</span>
		</div>

		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>✅ تنظیمات با موفقیت ذخیره شد.</p></div>
		<?php endif; ?>

		<div class="gvtbl-tabs">
			<button type="button" class="gvtbl-tab-btn <?php echo 'theme' === $tab ? 'is-active' : ''; ?>" data-tab="theme">🎨 تم جدول‌ها</button>
			<?php if ( $has_wc ) : ?>
			<button type="button" class="gvtbl-tab-btn <?php echo 'checkout' === $tab ? 'is-active' : ''; ?>" data-tab="checkout">🧾 فرم تسویه‌حساب ووکامرس</button>
			<?php endif; ?>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gv_tbl_save_settings">
			<input type="hidden" name="current_tab" value="<?php echo esc_attr( $tab ); ?>">
			<?php wp_nonce_field( GV_TBL_NONCE ); ?>

			<!-- تب تم -->
			<div class="gvtbl-tab-panel <?php echo 'theme' === $tab ? 'is-active' : ''; ?>" id="gvtbl-tab-theme" <?php echo 'theme' === $tab ? '' : 'style="display:none;"'; ?>>

				<div class="gvtbl-note">
					🎨 با انتخاب یکی از تم‌های زیر و ذخیره، رنگ‌بندی و شکل <b>همه جدول‌های سایت</b> (جدول‌های محتوا، جدول‌های ووکامرس مثل سبد خرید و سفارش‌ها، و جدول‌های بلوک گوتنبرگ) یکدست و زیبا می‌شود.
				</div>

				<div class="gvtbl-card">
					<h2>وضعیت کلی</h2>
					<div class="gvtbl-toggle-row">
						<input type="checkbox" name="enabled" <?php checked( $s['enabled'], 1 ); ?>>
						<label>فعال‌سازی استایل یکپارچه جدول‌ها در سایت</label>
					</div>
				</div>

				<div class="gvtbl-card">
					<h2>انتخاب تم</h2>
					<div class="gvtbl-theme-grid">
						<?php foreach ( $presets as $key => $p ) :
							$checked = ( $s['theme'] === $key );
							?>
							<label class="gvtbl-theme-card <?php echo $checked ? 'is-checked' : ''; ?>">
								<input type="radio" name="theme" value="<?php echo esc_attr( $key ); ?>" <?php checked( $checked ); ?>>
								<div class="gvtbl-theme-preview" style="border-radius:<?php echo esc_attr( $p['radius'] ); ?>px;">
									<table>
										<thead>
											<tr style="background:<?php echo esc_attr( $p['header_bg'] ); ?>;color:<?php echo esc_attr( $p['header_text'] ); ?>;">
												<th>ستون ۱</th><th>ستون ۲</th><th>ستون ۳</th>
											</tr>
										</thead>
										<tbody style="background:#fff;color:<?php echo esc_attr( $p['text_color'] ); ?>;">
											<tr style="border-color:<?php echo esc_attr( $p['border_color'] ); ?>;"><td>داده</td><td>داده</td><td>داده</td></tr>
											<tr style="border-color:<?php echo esc_attr( $p['border_color'] ); ?>;background:<?php echo esc_attr( $p['row_alt_bg'] ); ?>;"><td>داده</td><td>داده</td><td>داده</td></tr>
										</tbody>
									</table>
								</div>
								<span><?php echo esc_html( $p['label'] ); ?></span>
								<p><?php echo esc_html( $p['desc'] ); ?></p>
							</label>
						<?php endforeach; ?>

						<label class="gvtbl-theme-card <?php echo 'custom' === $s['theme'] ? 'is-checked' : ''; ?>">
							<input type="radio" name="theme" value="custom" <?php checked( 'custom' === $s['theme'] ); ?>>
							<div class="gvtbl-theme-preview" style="height:60px;display:flex;align-items:center;justify-content:center;background:#faf5ff;">
								<span style="font-size:22px;">🎛️</span>
							</div>
							<span>سفارشی</span>
							<p>رنگ‌های دلخواه خودتان را انتخاب کنید؛ ساختار و افکت‌ها خودکار زیبا اعمال می‌شود.</p>
						</label>
					</div>
				</div>

				<div class="gvtbl-card" id="gvtbl-custom-box" style="<?php echo 'custom' === $s['theme'] ? '' : 'display:none;'; ?>">
					<h2>رنگ‌های سفارشی</h2>
					<div class="gvtbl-color-grid">
						<div class="gvtbl-color-item">
							<label>رنگ پس‌زمینه هدر جدول</label>
							<input type="text" class="gvtbl-color-field" name="custom_colors[header_bg]" value="<?php echo esc_attr( $s['custom_colors']['header_bg'] ); ?>">
						</div>
						<div class="gvtbl-color-item">
							<label>رنگ متن هدر جدول</label>
							<input type="text" class="gvtbl-color-field" name="custom_colors[header_text]" value="<?php echo esc_attr( $s['custom_colors']['header_text'] ); ?>">
						</div>
						<div class="gvtbl-color-item">
							<label>رنگ متن اصلی جدول</label>
							<input type="text" class="gvtbl-color-field" name="custom_colors[text_color]" value="<?php echo esc_attr( $s['custom_colors']['text_color'] ); ?>">
						</div>
						<div class="gvtbl-color-item">
							<label>رنگ خطوط و حاشیه</label>
							<input type="text" class="gvtbl-color-field" name="custom_colors[border_color]" value="<?php echo esc_attr( $s['custom_colors']['border_color'] ); ?>">
						</div>
						<div class="gvtbl-color-item">
							<label>رنگ ردیف‌های زوج (راه‌راه)</label>
							<input type="text" class="gvtbl-color-field" name="custom_colors[row_alt_bg]" value="<?php echo esc_attr( $s['custom_colors']['row_alt_bg'] ); ?>">
						</div>
						<div class="gvtbl-color-item">
							<label>رنگ هاور روی ردیف</label>
							<input type="text" class="gvtbl-color-field" name="custom_colors[row_hover_bg]" value="<?php echo esc_attr( $s['custom_colors']['row_hover_bg'] ); ?>">
						</div>
					</div>
					<p class="gvtbl-hint">میزان گردی گوشه‌ها (پیکسل):</p>
					<input type="number" min="0" max="30" name="custom_colors[radius]" value="<?php echo esc_attr( $s['custom_colors']['radius'] ); ?>" style="width:90px;padding:6px 8px;border-radius:6px;border:1px solid #cbd5e1;">
					<p class="gvtbl-hint">نیازی به تنظیم سایه، فاصله‌گذاری یا افکت هاور نیست — این‌ها خودکار و با ساختاری استاندارد و زیبا روی رنگ‌های شما اعمال می‌شوند.</p>
				</div>
			</div>

			<?php if ( $has_wc ) : ?>
			<!-- تب تسویه‌حساب -->
			<div class="gvtbl-tab-panel <?php echo 'checkout' === $tab ? 'is-active' : ''; ?>" id="gvtbl-tab-checkout" <?php echo 'checkout' === $tab ? '' : 'style="display:none;"'; ?>>

				<div class="gvtbl-note">
					🧾 فیلدهایی که خاموش کنید، به‌طور کامل از فرم تسویه‌حساب ووکامرس (هم بخش صورت‌حساب و هم ارسال) حذف می‌شوند تا فرم کوتاه‌تر و ساده‌تر شود. نام و نام‌خانوادگی همیشه نمایش داده می‌شوند چون ووکامرس بدون آن‌ها کار نمی‌کند.
					<br>⚠️ توجه: اگر ایمیل یا آدرس/شهر را خاموش کنید، دیگر امکان پیگیری خودکار سفارش یا ارسال ایمیل فاکتور به مشتری وجود نخواهد داشت — فقط در صورتی خاموش کنید که روش پرداخت/ارسال شما نیازی به آن‌ها ندارد.
				</div>

				<div class="gvtbl-card">
					<h2>فیلدهای قابل‌حذف</h2>
					<div class="gvtbl-toggle-row">
						<input type="checkbox" name="checkout_fields[company]" <?php checked( $s['checkout_fields']['company'], 1 ); ?>>
						<label>🏢 نام شرکت</label>
					</div>
					<div class="gvtbl-toggle-row">
						<input type="checkbox" name="checkout_fields[address_1]" <?php checked( $s['checkout_fields']['address_1'], 1 ); ?>>
						<label>🛣️ خیابان / آدرس اصلی</label>
					</div>
					<div class="gvtbl-toggle-row">
						<input type="checkbox" name="checkout_fields[address_2]" <?php checked( $s['checkout_fields']['address_2'], 1 ); ?>>
						<label>🏠 آدرس خط دوم (پلاک/واحد)</label>
					</div>
					<div class="gvtbl-toggle-row">
						<input type="checkbox" name="checkout_fields[city]" <?php checked( $s['checkout_fields']['city'], 1 ); ?>>
						<label>🏙️ شهر</label>
					</div>
					<div class="gvtbl-toggle-row">
						<input type="checkbox" name="checkout_fields[state]" <?php checked( $s['checkout_fields']['state'], 1 ); ?>>
						<label>🗺️ استان</label>
					</div>
					<div class="gvtbl-toggle-row">
						<input type="checkbox" name="checkout_fields[postcode]" <?php checked( $s['checkout_fields']['postcode'], 1 ); ?>>
						<label>📮 کد پستی</label>
					</div>
					<div class="gvtbl-toggle-row">
						<input type="checkbox" name="checkout_fields[country]" <?php checked( $s['checkout_fields']['country'], 1 ); ?>>
						<label>🌍 کشور (برای سایت‌های تک‌کشوره معمولاً غیرضروری است)</label>
					</div>
					<div class="gvtbl-toggle-row">
						<input type="checkbox" name="checkout_fields[phone]" <?php checked( $s['checkout_fields']['phone'], 1 ); ?>>
						<label>📱 شماره تلفن</label>
					</div>
					<div class="gvtbl-toggle-row">
						<input type="checkbox" name="checkout_fields[email]" <?php checked( $s['checkout_fields']['email'], 1 ); ?>>
						<label>✉️ ایمیل</label>
					</div>
					<div class="gvtbl-toggle-row">
						<input type="checkbox" name="checkout_fields[order_notes]" <?php checked( $s['checkout_fields']['order_notes'], 1 ); ?>>
						<label>📝 یادداشت سفارش</label>
					</div>
					<div class="gvtbl-toggle-row">
						<input type="checkbox" name="checkout_fields[ship_to_different]" <?php checked( $s['checkout_fields']['ship_to_different'], 1 ); ?>>
						<label>📦 گزینه «ارسال به آدرس دیگر»</label>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php submit_button( '💾 ذخیره تنظیمات' ); ?>
		</form>

		<p style="font-size:11.5px;color:#888;text-align:center;margin-top:24px;">ساخته و توسعه‌یافته توسط <strong>Groot Vision</strong> | اینستاگرام: grootvision</p>
	</div>
	<?php
}

/* ==========================================================================
   ۳) خروجی CSS در سمت سایت
   ========================================================================== */

add_action( 'wp_head', 'gv_tbl_output_css', 50 );
function gv_tbl_output_css() {
	$s = gv_tbl_get_settings();
	if ( empty( $s['enabled'] ) ) { return; }

	$c = gv_tbl_get_active_palette( $s );

	/*
	 * مهم: این استایل فقط باید روی جدول‌های داخل محتوای مقالات، برگه‌ها و
	 * ووکامرس (وبلاگ) اعمال شود — نه روی جدول‌های فرم تسویه‌حساب/سبد خرید
	 * که مبلغ و قیمت نمایش می‌دهند. برای همین:
	 * ۱) فقط جدول‌های داخل .entry-content / .post-content / .wp-block-table را هدف می‌گیریم.
	 * ۲) با body:not(.woocommerce-checkout):not(.woocommerce-cart) کل صفحات
	 *    تسویه‌حساب و سبد خرید ووکامرس را به‌طور کامل مستثنی می‌کنیم، حتی اگر
	 *    آن صفحه یک جدول محتوایی دیگر هم داشته باشد.
	 */
	$scope = 'body:not(.woocommerce-checkout):not(.woocommerce-cart)';

	$selectors_wrap  = "{$scope} .entry-content table, {$scope} .post-content table, {$scope} .wp-block-table table, {$scope} article.post table, {$scope} article.page table";
	$selectors_th    = "{$scope} .entry-content table th, {$scope} .post-content table th, {$scope} .wp-block-table table th, {$scope} article.post table th, {$scope} article.page table th";
	$selectors_td    = "{$scope} .entry-content table td, {$scope} .post-content table td, {$scope} .wp-block-table table td, {$scope} article.post table td, {$scope} article.page table td";
	$selectors_tr_even = "{$scope} .entry-content table tbody tr:nth-child(even), {$scope} .post-content table tbody tr:nth-child(even), {$scope} .wp-block-table table tbody tr:nth-child(even)";
	$selectors_tr_hover = "{$scope} .entry-content table tbody tr:hover, {$scope} .post-content table tbody tr:hover, {$scope} .wp-block-table table tbody tr:hover";
	?>
	<style id="gv-table-style-css">
		<?php echo esc_html( $selectors_wrap ); ?> {
			border-collapse: separate;
			border-spacing: 0;
			width: 100%;
			border: 1px solid <?php echo esc_html( $c['border_color'] ); ?>;
			border-radius: <?php echo intval( $c['radius'] ); ?>px;
			overflow: hidden;
			color: <?php echo esc_html( $c['text_color'] ); ?>;
		}
		<?php echo esc_html( $selectors_th ); ?> {
			background: <?php echo esc_html( $c['header_bg'] ); ?>;
			color: <?php echo esc_html( $c['header_text'] ); ?>;
			padding: 12px 14px;
			font-weight: 700;
			text-align: right;
		}
		<?php echo esc_html( $selectors_td ); ?> {
			padding: 10px 14px;
			border-top: 1px solid <?php echo esc_html( $c['border_color'] ); ?>;
			text-align: right;
		}
		<?php echo esc_html( $selectors_tr_even ); ?> {
			background: <?php echo esc_html( $c['row_alt_bg'] ); ?>;
		}
		<?php echo esc_html( $selectors_tr_hover ); ?> {
			background: <?php echo esc_html( $c['row_hover_bg'] ); ?>;
			transition: background .15s ease;
		}
	</style>
	<?php
}

/* ==========================================================================
   ۴) ساده‌سازی فرم تسویه‌حساب ووکامرس
   ========================================================================== */

add_filter( 'woocommerce_checkout_fields', 'gv_tbl_filter_checkout_fields' );
function gv_tbl_filter_checkout_fields( $fields ) {
	$s      = gv_tbl_get_settings();
	$toggle = $s['checkout_fields'];

	$map = array(
		'company'   => 'company',
		'address_1' => 'address_1',
		'address_2' => 'address_2',
		'city'      => 'city',
		'country'   => 'country',
		'state'     => 'state',
		'postcode'  => 'postcode',
		'phone'     => 'phone',
	);

	foreach ( array( 'billing', 'shipping' ) as $group ) {
		if ( empty( $fields[ $group ] ) ) { continue; }
		foreach ( $map as $toggle_key => $suffix ) {
			$field_key = $group . '_' . $suffix;
			if ( empty( $toggle[ $toggle_key ] ) && isset( $fields[ $group ][ $field_key ] ) ) {
				unset( $fields[ $group ][ $field_key ] );
			}
		}
	}

	// ایمیل فقط در بخش billing وجود دارد
	if ( empty( $toggle['email'] ) && isset( $fields['billing']['billing_email'] ) ) {
		unset( $fields['billing']['billing_email'] );
	}

	if ( empty( $toggle['order_notes'] ) && isset( $fields['order']['order_comments'] ) ) {
		unset( $fields['order']['order_comments'] );
	}

	return $fields;
}

add_filter( 'woocommerce_enable_order_notes_field', 'gv_tbl_filter_order_notes_toggle' );
function gv_tbl_filter_order_notes_toggle( $enabled ) {
	$s = gv_tbl_get_settings();
	if ( empty( $s['checkout_fields']['order_notes'] ) ) { return false; }
	return $enabled;
}

add_filter( 'woocommerce_ship_to_different_address_checked', 'gv_tbl_filter_ship_to_different' );
function gv_tbl_filter_ship_to_different( $checked ) {
	return $checked;
}

add_action( 'wp_head', 'gv_tbl_maybe_hide_ship_to_different', 60 );
function gv_tbl_maybe_hide_ship_to_different() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) { return; }
	$s = gv_tbl_get_settings();
	if ( ! empty( $s['checkout_fields']['ship_to_different'] ) ) { return; }
	echo '<style>.woocommerce-shipping-fields__field-wrapper, #ship-to-different-address{display:none !important;}</style>';
}