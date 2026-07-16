<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * ==========================================================
 *  Groot Vision — طراحی صفحه ورود وردپرس
 *  چند پیش‌فرض آماده و زیبا برای صفحه لاگین + امکان
 *  انتخاب لوگو و متن دلخواه
 * ==========================================================
 */
define( 'GV_LOGIN_OPT', 'gv_login_style_settings' );
define( 'GV_LOGIN_NONCE', 'gv_login_nonce_action' );

function gv_login_default_settings() {
	return array(
		'enabled'    => 0,
		'theme'      => 'aurora', // aurora | midnight | minimal | sunset
		'logo_url'   => '',
		'logo_width' => 84,
		'site_name'  => get_bloginfo( 'name' ),
		'tagline'    => '',
		'accent'     => '#0e4037',
	);
}

function gv_login_get_settings() {
	return wp_parse_args( get_option( GV_LOGIN_OPT, array() ), gv_login_default_settings() );
}

function gv_login_presets() {
	return array(
		'aurora'   => array(
			'label' => 'اورورا (گرادیان سبز-بنفش)',
			'bg'    => 'linear-gradient(135deg,#0e4037 0%,#145c4d 45%,#6d28d9 100%)',
			'card_bg' => 'rgba(255,255,255,.97)',
			'text'  => '#0f172a',
		),
		'midnight' => array(
			'label' => 'میدنایت (تیره و شیشه‌ای)',
			'bg'    => 'radial-gradient(circle at 20% 20%, #1e293b, #0b1220 70%)',
			'card_bg' => 'rgba(15,23,42,.72)',
			'text'  => '#e2e8f0',
			'dark'  => true,
		),
		'minimal'  => array(
			'label' => 'مینیمال روشن',
			'bg'    => '#f4f4f5',
			'card_bg' => '#ffffff',
			'text'  => '#0f172a',
		),
		'sunset'   => array(
			'label' => 'غروب (نارنجی-صورتی)',
			'bg'    => 'linear-gradient(135deg,#f97316,#db2777 60%,#7c3aed)',
			'card_bg' => 'rgba(255,255,255,.96)',
			'text'  => '#0f172a',
		),
	);
}

/* ==========================================================================
   منوی مدیریت
   ========================================================================== */

add_action( 'admin_menu', 'gv_login_admin_menu' );
function gv_login_admin_menu() {
	add_submenu_page(
		'groot-vision-hub',
		'صفحه ورود وردپرس | Groot Vision',
		'🔐 صفحه ورود',
		'manage_options',
		'gv-login-style',
		'gv_login_render_admin_page'
	);
}

add_action( 'admin_enqueue_scripts', 'gv_login_admin_assets' );
function gv_login_admin_assets( $hook ) {
	if ( strpos( $hook, 'gv-login-style' ) === false ) { return; }
	wp_enqueue_media();
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_add_inline_script( 'wp-color-picker', "jQuery(function($){
		$('.gvlogin-color').wpColorPicker();
		$('#gvlogin-pick-logo').on('click', function(e){
			e.preventDefault();
			var frame = wp.media({ title:'انتخاب لوگو', multiple:false, library:{type:'image'} });
			frame.on('select', function(){
				var att = frame.state().get('selection').first().toJSON();
				$('#gvlogin-logo-url').val(att.url);
				$('#gvlogin-logo-preview').attr('src', att.url).show();
			});
			frame.open();
		});
		$('#gvlogin-remove-logo').on('click', function(e){
			e.preventDefault();
			$('#gvlogin-logo-url').val('');
			$('#gvlogin-logo-preview').hide();
		});
		$('.gvlogin-theme-radio').on('change', function(){
			$('.gvlogin-theme-card').removeClass('is-selected');
			$(this).closest('.gvlogin-theme-card').addClass('is-selected');
		});
	});" );
}

add_action( 'admin_post_gv_login_save_settings', 'gv_login_save_settings' );
function gv_login_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_LOGIN_NONCE );

	$valid_themes = array_keys( gv_login_presets() );
	$theme = isset( $_POST['theme'] ) ? sanitize_key( $_POST['theme'] ) : 'aurora';
	if ( ! in_array( $theme, $valid_themes, true ) ) { $theme = 'aurora'; }

	$settings = array(
		'enabled'    => isset( $_POST['enabled'] ) ? 1 : 0,
		'theme'      => $theme,
		'logo_url'   => esc_url_raw( $_POST['logo_url'] ?? '' ),
		'logo_width' => max( 40, min( 300, intval( $_POST['logo_width'] ?? 84 ) ) ),
		'site_name'  => sanitize_text_field( $_POST['site_name'] ?? get_bloginfo( 'name' ) ),
		'tagline'    => sanitize_text_field( $_POST['tagline'] ?? '' ),
		'accent'     => sanitize_hex_color( $_POST['accent'] ?? '' ) ?: '#0e4037',
	);

	update_option( GV_LOGIN_OPT, $settings );
	wp_safe_redirect( admin_url( 'admin.php?page=gv-login-style&updated=1' ) );
	exit;
}

/* ==========================================================================
   صفحه مدیریت
   ========================================================================== */

function gv_login_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$s = gv_login_get_settings();
	$presets = gv_login_presets();
	?>
	<div class="wrap" dir="rtl" style="font-family: Tahoma, sans-serif; max-width:1000px;">
		<style>
			.gvlogin-header{background:linear-gradient(120deg,#0e4037,#145c4d);color:#fff;padding:22px 26px;border-radius:14px;margin:20px 0;}
			.gvlogin-header h1{margin:0;font-size:20px;color:#fff;}
			.gvlogin-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:22px;margin-bottom:18px;}
			.gvlogin-card h2{margin-top:0;font-size:15px;}
			.gvlogin-themes{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;}
			@media(max-width:800px){.gvlogin-themes{grid-template-columns:repeat(2,1fr);}}
			.gvlogin-theme-card{border:2px solid #e5e7eb;border-radius:14px;padding:12px;cursor:pointer;position:relative;transition:.15s;}
			.gvlogin-theme-card.is-selected{border-color:#0e4037;box-shadow:0 0 0 3px rgba(14,64,55,.12);}
			.gvlogin-theme-card input{position:absolute;top:10px;left:10px;}
			.gvlogin-swatch{height:70px;border-radius:9px;margin-bottom:8px;}
			.gvlogin-theme-card span{font-size:12.5px;font-weight:700;color:#334155;display:block;text-align:center;}
			.gvlogin-field{margin-bottom:14px;}
			.gvlogin-field label{display:block;font-weight:700;font-size:13px;margin-bottom:5px;color:#334155;}
			.gvlogin-field input[type=text],.gvlogin-field input[type=number]{width:100%;max-width:380px;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;}
			.gvlogin-btn{background:#111827;color:#fff !important;border:none;padding:10px 22px;border-radius:10px;font-weight:600;cursor:pointer;}
			#gvlogin-logo-preview{max-height:70px;display:<?php echo $s['logo_url'] ? 'inline-block' : 'none'; ?>;margin:8px 0;border-radius:8px;}
		</style>

		<div class="gvlogin-header"><h1>🔐 طراحی صفحه ورود وردپرس</h1></div>

		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد. برای دیدن نتیجه، از حالت خصوصی مرورگر وارد <a href="<?php echo esc_url( wp_login_url() ); ?>" target="_blank">صفحه ورود</a> شوید.</p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gv_login_save_settings">
			<?php wp_nonce_field( GV_LOGIN_NONCE ); ?>

			<div class="gvlogin-card">
				<label style="font-weight:700;"><input type="checkbox" name="enabled" <?php checked( $s['enabled'], 1 ); ?>> فعال‌سازی طراحی اختصاصی صفحه ورود</label>
			</div>

			<div class="gvlogin-card">
				<h2>انتخاب تم</h2>
				<div class="gvlogin-themes">
					<?php foreach ( $presets as $key => $p ) : ?>
						<label class="gvlogin-theme-card <?php echo $s['theme'] === $key ? 'is-selected' : ''; ?>">
							<input type="radio" class="gvlogin-theme-radio" name="theme" value="<?php echo esc_attr( $key ); ?>" <?php checked( $s['theme'], $key ); ?>>
							<div class="gvlogin-swatch" style="background:<?php echo esc_attr( $p['bg'] ); ?>;"></div>
							<span><?php echo esc_html( $p['label'] ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="gvlogin-card">
				<h2>لوگو و متن</h2>
				<div class="gvlogin-field">
					<label>لوگو</label>
					<input type="hidden" id="gvlogin-logo-url" name="logo_url" value="<?php echo esc_attr( $s['logo_url'] ); ?>">
					<br><img id="gvlogin-logo-preview" src="<?php echo esc_attr( $s['logo_url'] ); ?>">
					<br>
					<button type="button" id="gvlogin-pick-logo" class="button">📁 انتخاب از کتابخانه رسانه</button>
					<button type="button" id="gvlogin-remove-logo" class="button">حذف لوگو</button>
					<small style="display:block;color:#94a3b8;margin-top:6px;">اگر لوگو انتخاب نشود، به‌جای آن نام سایت به‌صورت متنی و شیک نمایش داده می‌شود.</small>
				</div>
				<div class="gvlogin-field">
					<label>عرض لوگو (پیکسل)</label>
					<input type="number" name="logo_width" min="40" max="300" value="<?php echo esc_attr( $s['logo_width'] ); ?>">
				</div>
				<div class="gvlogin-field">
					<label>نام سایت (وقتی لوگو انتخاب نشده)</label>
					<input type="text" name="site_name" value="<?php echo esc_attr( $s['site_name'] ); ?>">
				</div>
				<div class="gvlogin-field">
					<label>توضیح کوتاه زیر فرم (اختیاری)</label>
					<input type="text" name="tagline" value="<?php echo esc_attr( $s['tagline'] ); ?>" placeholder="مثلاً: به پنل مدیریت خوش آمدید">
				</div>
				<div class="gvlogin-field">
					<label>رنگ اصلی دکمه و لینک‌ها</label>
					<input type="text" class="gvlogin-color" name="accent" value="<?php echo esc_attr( $s['accent'] ); ?>">
				</div>
			</div>

			<button type="submit" class="gvlogin-btn">💾 ذخیره تنظیمات</button>
		</form>

		<p style="font-size:11.5px;color:#888;text-align:center;margin-top:24px;">ساخته و توسعه‌یافته توسط <strong>Groot Vision</strong></p>
	</div>
	<?php
}

/* ==========================================================================
   خروجی استایل روی صفحه لاگین واقعی
   ========================================================================== */

add_action( 'login_enqueue_scripts', 'gv_login_output_css' );
function gv_login_output_css() {
	$s = gv_login_get_settings();
	if ( empty( $s['enabled'] ) ) { return; }

	$presets = gv_login_presets();
	$p = isset( $presets[ $s['theme'] ] ) ? $presets[ $s['theme'] ] : $presets['aurora'];
	$is_dark = ! empty( $p['dark'] );
	?>
	<style>
		body.login{
			background: <?php echo $p['bg']; ?>;
			min-height:100vh;
		}
		body.login #login{
			width:380px;
			padding:0;
		}
		body.login h1 a{
			background-image:none !important;
			width:auto;height:auto;
			display:flex;flex-direction:column;align-items:center;gap:10px;
			text-indent:0;
			font-size:19px;font-weight:800;
			color:<?php echo $is_dark ? '#fff' : '#0f172a'; ?>;
			margin-bottom:18px;
			pointer-events:none;
		}
		<?php if ( $s['logo_url'] ) : ?>
		body.login h1 a{
			background-image:url('<?php echo esc_url( $s['logo_url'] ); ?>') !important;
			background-repeat:no-repeat;background-position:center top;background-size:contain;
			width:100%;height:<?php echo intval( $s['logo_width'] ); ?>px;
			text-indent:-9999px;
		}
		<?php endif; ?>
		body.login form#loginform,
		body.login #login .message,
		body.login #login .notice{
			background:<?php echo $p['card_bg']; ?>;
			border:none;
			border-radius:18px;
			box-shadow:0 24px 60px -20px rgba(0,0,0,.35), 0 0 0 1px rgba(255,255,255,.06);
			padding:32px 30px 24px;
			backdrop-filter:blur(14px);
		}
		body.login form#loginform label{
			color:<?php echo $is_dark ? '#cbd5e1' : '#334155'; ?>;
			font-weight:600;font-size:13px;
		}
		body.login form#loginform input[type=text],
		body.login form#loginform input[type=password]{
			border-radius:10px;
			border:1px solid <?php echo $is_dark ? 'rgba(255,255,255,.18)' : '#e2e8f0'; ?>;
			background:<?php echo $is_dark ? 'rgba(255,255,255,.06)' : '#f8fafc'; ?>;
			color:<?php echo $is_dark ? '#fff' : '#0f172a'; ?>;
			padding:10px 12px;font-size:14px;
			box-shadow:none;
		}
		body.login form#loginform input[type=text]:focus,
		body.login form#loginform input[type=password]:focus{
			border-color:<?php echo esc_html( $s['accent'] ); ?>;
			box-shadow:0 0 0 3px <?php echo esc_html( $s['accent'] ); ?>22;
		}
		body.login .button-primary{
			background:<?php echo esc_html( $s['accent'] ); ?> !important;
			border-color:<?php echo esc_html( $s['accent'] ); ?> !important;
			border-radius:10px !important;
			box-shadow:none !important;
			text-shadow:none !important;
			font-weight:700 !important;
			width:100%;padding:10px 0 !important;height:auto !important;
		}
		body.login #nav, body.login #backtoblog{
			text-align:center;
		}
		body.login #nav a, body.login #backtoblog a{
			color:<?php echo $is_dark ? '#e2e8f0' : '#334155'; ?> !important;
			font-size:12.5px;
		}
		body.login #login_error, body.login .message{
			border-radius:12px;
			border-right:4px solid <?php echo esc_html( $s['accent'] ); ?>;
		}
		<?php if ( $s['tagline'] ) : ?>
		body.login #login:after{
			content:"<?php echo esc_js( $s['tagline'] ); ?>";
			display:block;text-align:center;margin-top:16px;
			color:<?php echo $is_dark ? 'rgba(255,255,255,.7)' : 'rgba(15,23,42,.55)'; ?>;
			font-size:12.5px;
		}
		<?php endif; ?>
	</style>
	<?php
}

add_filter( 'login_headerurl', 'gv_login_header_url' );
function gv_login_header_url() {
	return home_url( '/' );
}

add_filter( 'login_headertext', 'gv_login_header_text' );
function gv_login_header_text() {
	$s = gv_login_get_settings();
	if ( empty( $s['enabled'] ) ) { return get_bloginfo( 'name' ); }
	return $s['logo_url'] ? get_bloginfo( 'name' ) : $s['site_name'];
}
