<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * ==========================================================
 *  Groot Vision — مدیریت فونت سایت (نسخه ۲)
 *  ------------------------------------------------------------
 *  تغییرات نسبت به نسخه قبل:
 *   ۱) هر فونت در کتابخانه می‌تواند چند وزن (Weight) داشته باشد
 *      (نازک تا خیلی ضخیم) + حالت Italic برای هرکدام — نه فقط
 *      Regular/Bold/Italic ثابت.
 *   ۲) فونت‌ها دیگر فقط "یک فونت برای کل سایت" نیستند؛ برای هر
 *      بخش (متن اصلی، سربرگ‌ها، منو، دکمه‌ها، لوگو/عنوان سایت)
 *      می‌توان یک فونت جدا از کتابخانه انتخاب کرد.
 *   ۳) پیش‌نمایش زنده‌ی هر فونت داخل کتابخانه.
 *   ۴) اعمال صحیح روی کل سایت با ترتیب اولویت درست بین
 *      انتخاب‌های عمومی و اختصاصی هر بخش.
 * ==========================================================
 */
define( 'GV_FONT_OPT', 'gv_font_manager_settings' );
define( 'GV_FONT_LIST_OPT', 'gv_font_manager_library' );
define( 'GV_FONT_NONCE', 'gv_font_nonce_action' );

/** پوشه‌ای که فونت‌های آپلودی داخلش ذخیره می‌شوند (داخل wp-content/uploads) */
function gv_font_upload_dir() {
	$upload = wp_upload_dir();
	$dir    = trailingslashit( $upload['basedir'] ) . 'gv-fonts';
	$url    = trailingslashit( $upload['baseurl'] ) . 'gv-fonts';
	if ( ! file_exists( $dir ) ) { wp_mkdir_p( $dir ); }
	return array( 'dir' => $dir, 'url' => $url );
}

/** نقش‌های تایپوگرافی سایت — هر کدام می‌توانند فونت اختصاصی خودشان را داشته باشند */
function gv_font_get_roles() {
	return array(
		'body'    => array(
			'label'    => 'متن اصلی سایت (بدنه)',
			'desc'     => 'پاراگراف‌ها، متن‌های عمومی، فرم‌ها',
			'selector' => 'body, p, li, span, a, input, textarea, select, label',
			'weight'   => 400,
		),
		'heading' => array(
			'label'    => 'سربرگ‌ها (h1 تا h6)',
			'desc'     => 'عنوان‌ها با مقیاس تایپوگرافی خودکار بزرگ می‌شوند',
			'selector' => 'h1, h2, h3, h4, h5, h6',
			'weight'   => 700,
		),
		'menu'    => array(
			'label'    => 'منوی سایت',
			'desc'     => 'آیتم‌های منوی اصلی و ناوبری',
			'selector' => 'nav a, .menu a, .menu-item a, .nav-menu a, #main-menu a, .main-navigation a',
			'weight'   => 500,
		),
		'button'  => array(
			'label'    => 'دکمه‌ها',
			'desc'     => 'دکمه‌های سایت، فروشگاه و فرم‌ها',
			'selector' => 'button, .button, .btn, input[type="submit"], input[type="button"], .wp-block-button__link',
			'weight'   => 600,
		),
		'logo'    => array(
			'label'    => 'عنوان سایت / لوگوی متنی',
			'desc'     => 'در صورتی که لوگوی سایت شما متنی باشد',
			'selector' => '.site-title, .site-title a, .site-branding .site-title, .custom-logo-link + .site-title',
			'weight'   => 800,
		),
	);
}

function gv_font_default_settings() {
	$defaults = array(
		'enabled'   => 0,
		'base_size' => 16, // سایز فونت پایه بدنه سایت (px)
		'scale'     => 1.25, // نسبت مقیاس تایپوگرافی بین سربرگ‌ها (Major Third پیش‌فرض)
		'roles'     => array(),
	);
	foreach ( gv_font_get_roles() as $key => $role ) {
		$defaults['roles'][ $key ] = array(
			'font'   => '',            // slug فونت انتخاب‌شده از کتابخانه، خالی = وراثت از بدنه یا تم
			'weight' => $role['weight'],
		);
	}
	return $defaults;
}

function gv_font_get_settings() {
	$saved = get_option( GV_FONT_OPT, array() );
	$defaults = gv_font_default_settings();

	// سازگاری با نسخه قدیمی: اگر تنظیمات قبلی active_font ذخیره کرده، آن را به‌عنوان فونت بدنه بیاور
	if ( ! empty( $saved['active_font'] ) && empty( $saved['roles']['body']['font'] ) ) {
		$saved['roles']['body']['font'] = $saved['active_font'];
	}

	$merged = wp_parse_args( $saved, $defaults );
	foreach ( $defaults['roles'] as $key => $role_defaults ) {
		$merged['roles'][ $key ] = wp_parse_args(
			isset( $saved['roles'][ $key ] ) && is_array( $saved['roles'][ $key ] ) ? $saved['roles'][ $key ] : array(),
			$role_defaults
		);
	}
	return $merged;
}

/** کتابخانه فونت‌های آپلودشده: آرایه‌ای از slug => { name, files: [ {weight,style,file}, ... ] } */
function gv_font_get_library() {
	$library = get_option( GV_FONT_LIST_OPT, array() );

	// سازگاری با ساختار قدیمی (regular/bold/italic) → تبدیل به files[]
	foreach ( $library as $slug => &$font ) {
		if ( ! isset( $font['files'] ) ) {
			$files = array();
			if ( ! empty( $font['regular'] ) ) { $files[] = array( 'weight' => 400, 'style' => 'normal', 'file' => $font['regular'] ); }
			if ( ! empty( $font['bold'] ) )    { $files[] = array( 'weight' => 700, 'style' => 'normal', 'file' => $font['bold'] ); }
			if ( ! empty( $font['italic'] ) )  { $files[] = array( 'weight' => 400, 'style' => 'italic', 'file' => $font['italic'] ); }
			$font['files'] = $files;
		}
	}
	unset( $font );
	return $library;
}

/** نزدیک‌ترین فایلِ یک وزن دلخواه را از میان فایل‌های یک فونت پیدا می‌کند (برای fallback هوشمند) */
function gv_font_closest_weight_file( $files, $target_weight, $style = 'normal' ) {
	if ( empty( $files ) ) { return null; }
	$best = null;
	$best_diff = 99999;
	foreach ( $files as $f ) {
		if ( $f['style'] !== $style ) { continue; }
		$diff = abs( (int) $f['weight'] - (int) $target_weight );
		if ( $diff < $best_diff ) { $best_diff = $diff; $best = $f; }
	}
	if ( ! $best && $style === 'italic' ) {
		// اگر ایتالیک نداشت، از نرمال استفاده کن
		return gv_font_closest_weight_file( $files, $target_weight, 'normal' );
	}
	return $best;
}

/* ==========================================================================
   منوی مدیریت
   ========================================================================== */

add_action( 'admin_menu', 'gv_font_admin_menu' );
function gv_font_admin_menu() {
	add_submenu_page(
		'groot-vision-hub',
		'مدیریت فونت سایت | Groot Vision',
		'🔤 مدیریت فونت',
		'manage_options',
		'gv-font-manager',
		'gv_font_render_admin_page'
	);
}

/* ---- افزودن فونت جدید (یا افزودن وزن جدید به فونت موجود) به کتابخانه ---- */
add_action( 'admin_post_gv_font_upload', 'gv_font_handle_upload' );
function gv_font_handle_upload() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_FONT_NONCE );

	$name = sanitize_text_field( $_POST['font_name'] ?? '' );
	if ( empty( $name ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=gv-font-manager&error=name' ) );
		exit;
	}
	$slug  = sanitize_title( $name );
	$paths = gv_font_upload_dir();

	$allowed_ext = array( 'woff2', 'woff', 'ttf', 'otf' );
	$allowed_weights = array( 100, 200, 300, 400, 500, 600, 700, 800, 900 );

	$weights = isset( $_POST['file_weight'] ) ? (array) $_POST['file_weight'] : array();
	$styles  = isset( $_POST['file_style'] ) ? (array) $_POST['file_style'] : array();
	$files   = isset( $_FILES['font_files'] ) ? $_FILES['font_files'] : array();

	$new_files = array();

	if ( ! empty( $files['name'] ) && is_array( $files['name'] ) ) {
		foreach ( $files['name'] as $i => $orig_name ) {
			if ( empty( $orig_name ) ) { continue; }
			if ( ! empty( $files['error'][ $i ] ) ) { continue; }

			$ext = strtolower( pathinfo( $orig_name, PATHINFO_EXTENSION ) );
			if ( ! in_array( $ext, $allowed_ext, true ) ) { continue; }

			$weight = isset( $weights[ $i ] ) ? intval( $weights[ $i ] ) : 400;
			if ( ! in_array( $weight, $allowed_weights, true ) ) { $weight = 400; }
			$style = ( isset( $styles[ $i ] ) && $styles[ $i ] === 'italic' ) ? 'italic' : 'normal';

			$dest_name = $slug . '-' . $weight . ( $style === 'italic' ? 'i' : '' ) . '-' . wp_generate_password( 4, false, false ) . '.' . $ext;
			$dest_path = trailingslashit( $paths['dir'] ) . $dest_name;

			if ( move_uploaded_file( $files['tmp_name'][ $i ], $dest_path ) ) {
				$new_files[] = array( 'weight' => $weight, 'style' => $style, 'file' => $dest_name );
			}
		}
	}

	if ( empty( $new_files ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=gv-font-manager&error=file' ) );
		exit;
	}

	$library = gv_font_get_library();

	if ( isset( $library[ $slug ] ) ) {
		// افزودن وزن‌های جدید به فونت موجود (وزن‌های تکراری با فایل جدید جایگزین می‌شوند)
		foreach ( $new_files as $nf ) {
			$replaced = false;
			foreach ( $library[ $slug ]['files'] as &$existing ) {
				if ( (int) $existing['weight'] === (int) $nf['weight'] && $existing['style'] === $nf['style'] ) {
					$old = trailingslashit( $paths['dir'] ) . $existing['file'];
					if ( file_exists( $old ) ) { @unlink( $old ); }
					$existing = $nf;
					$replaced = true;
					break;
				}
			}
			unset( $existing );
			if ( ! $replaced ) { $library[ $slug ]['files'][] = $nf; }
		}
	} else {
		$library[ $slug ] = array( 'name' => $name, 'files' => $new_files );
	}

	update_option( GV_FONT_LIST_OPT, $library );

	wp_safe_redirect( admin_url( 'admin.php?page=gv-font-manager&uploaded=1' ) );
	exit;
}

add_action( 'admin_post_gv_font_delete', 'gv_font_handle_delete' );
function gv_font_handle_delete() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_FONT_NONCE );

	$slug    = sanitize_key( $_GET['slug'] ?? '' );
	$library = gv_font_get_library();
	if ( isset( $library[ $slug ] ) ) {
		$paths = gv_font_upload_dir();
		foreach ( $library[ $slug ]['files'] as $f ) {
			$path = trailingslashit( $paths['dir'] ) . $f['file'];
			if ( file_exists( $path ) ) { @unlink( $path ); }
		}
		unset( $library[ $slug ] );
		update_option( GV_FONT_LIST_OPT, $library );

		// اگر این فونت در یکی از نقش‌ها استفاده شده بود، پاک شود
		$s = gv_font_get_settings();
		$changed = false;
		foreach ( $s['roles'] as $key => $role ) {
			if ( $role['font'] === $slug ) { $s['roles'][ $key ]['font'] = ''; $changed = true; }
		}
		if ( $changed ) { update_option( GV_FONT_OPT, $s ); }
	}
	wp_safe_redirect( admin_url( 'admin.php?page=gv-font-manager&deleted=1' ) );
	exit;
}

/** حذف فقط یک وزن خاص از یک فونت */
add_action( 'admin_post_gv_font_delete_weight', 'gv_font_handle_delete_weight' );
function gv_font_handle_delete_weight() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_FONT_NONCE );

	$slug   = sanitize_key( $_GET['slug'] ?? '' );
	$weight = intval( $_GET['weight'] ?? 0 );
	$style  = ( ( $_GET['style'] ?? 'normal' ) === 'italic' ) ? 'italic' : 'normal';

	$library = gv_font_get_library();
	if ( isset( $library[ $slug ] ) ) {
		$paths = gv_font_upload_dir();
		foreach ( $library[ $slug ]['files'] as $i => $f ) {
			if ( (int) $f['weight'] === $weight && $f['style'] === $style ) {
				$path = trailingslashit( $paths['dir'] ) . $f['file'];
				if ( file_exists( $path ) ) { @unlink( $path ); }
				unset( $library[ $slug ]['files'][ $i ] );
				$library[ $slug ]['files'] = array_values( $library[ $slug ]['files'] );
				break;
			}
		}
		// اگر دیگر هیچ فایلی نماند، کل فونت حذف شود
		if ( empty( $library[ $slug ]['files'] ) ) {
			unset( $library[ $slug ] );
		}
		update_option( GV_FONT_LIST_OPT, $library );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=gv-font-manager&deleted=1' ) );
	exit;
}

add_action( 'admin_post_gv_font_save_settings', 'gv_font_save_settings' );
function gv_font_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_FONT_NONCE );

	$library = gv_font_get_library();
	$roles   = gv_font_get_roles();

	$settings = array(
		'enabled'   => isset( $_POST['enabled'] ) ? 1 : 0,
		'base_size' => max( 12, min( 22, intval( $_POST['base_size'] ?? 16 ) ) ),
		'scale'     => max( 1.05, min( 1.6, floatval( $_POST['scale'] ?? 1.25 ) ) ),
		'roles'     => array(),
	);

	foreach ( $roles as $key => $role_def ) {
		$font = sanitize_key( $_POST[ 'role_font_' . $key ] ?? '' );
		if ( '' !== $font && ! isset( $library[ $font ] ) ) { $font = ''; }

		$weight = intval( $_POST[ 'role_weight_' . $key ] ?? $role_def['weight'] );
		if ( ! in_array( $weight, array( 100, 200, 300, 400, 500, 600, 700, 800, 900 ), true ) ) {
			$weight = $role_def['weight'];
		}

		$settings['roles'][ $key ] = array( 'font' => $font, 'weight' => $weight );
	}

	update_option( GV_FONT_OPT, $settings );
	wp_safe_redirect( admin_url( 'admin.php?page=gv-font-manager&updated=1' ) );
	exit;
}

/* ==========================================================================
   صفحه مدیریت
   ========================================================================== */

function gv_font_weight_label( $w ) {
	$labels = array(
		100 => 'خیلی نازک (100)', 200 => 'نازک (200)', 300 => 'سبک (300)',
		400 => 'معمولی (400)', 500 => 'متوسط (500)', 600 => 'نیمه‌ضخیم (600)',
		700 => 'ضخیم (700)', 800 => 'خیلی‌ضخیم (800)', 900 => 'سیاه (900)',
	);
	return $labels[ (int) $w ] ?? ( $w . '' );
}

function gv_font_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$s       = gv_font_get_settings();
	$library = gv_font_get_library();
	$roles   = gv_font_get_roles();
	$paths   = gv_font_upload_dir();
	?>
	<div class="wrap" dir="rtl" style="font-family: 'Vazirmatn', Tahoma, sans-serif; max-width:1080px;">
		<style>
			.gvfont-header{background:linear-gradient(120deg,#0e4037,#145c4d);color:#fff;padding:26px 30px;border-radius:16px;margin:20px 0 28px;box-shadow:0 10px 30px rgba(14,64,55,.3);}
			.gvfont-header h1{margin:0;font-size:22px;color:#fff;display:flex;align-items:center;gap:10px;}
			.gvfont-header p{margin:8px 0 0;font-size:13px;color:#cbd5e1;}
			.gvfont-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,.03);}
			.gvfont-card h2{margin-top:0;font-size:16px;color:#0f172a;display:flex;align-items:center;gap:8px;}
			.gvfont-field{margin-bottom:14px;}
			.gvfont-field label{display:block;font-weight:700;font-size:13px;margin-bottom:5px;color:#334155;}
			.gvfont-field input[type=text],.gvfont-field input[type=file],.gvfont-field input[type=number],.gvfont-field select{width:100%;max-width:380px;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;}
			.gvfont-btn{background:#0e4037;color:#fff !important;border:none;padding:10px 22px;border-radius:10px;font-weight:600;cursor:pointer;font-size:13.5px;}
			.gvfont-btn.secondary{background:#f1f5f9;color:#334155 !important;}
			.gvfont-lib-item{border:1px solid #e2e8f0;border-radius:14px;margin-bottom:14px;overflow:hidden;}
			.gvfont-lib-item.is-used{border-color:#0e4037;box-shadow:0 0 0 2px rgba(14,64,55,.08);}
			.gvfont-lib-item-top{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:#f8fafc;}
			.gvfont-lib-name{font-weight:800;font-size:14.5px;color:#0f172a;}
			.gvfont-lib-tags{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;}
			.gvfont-tag{font-size:10.5px;background:#e2e8f0;color:#475569;padding:3px 9px;border-radius:20px;display:inline-flex;align-items:center;gap:5px;}
			.gvfont-tag a{color:#b91c1c;text-decoration:none;}
			.gvfont-del{color:#b91c1c;text-decoration:none;font-size:12.5px;font-weight:600;}
			.gvfont-preview{padding:16px 18px;border-top:1px dashed #e2e8f0;font-size:20px;color:#0f172a;line-height:1.9;}
			.gvfont-role-row{display:grid;grid-template-columns:1.2fr 1.4fr 1fr;gap:14px;align-items:end;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;margin-bottom:12px;}
			.gvfont-role-row .role-info b{display:block;font-size:13.5px;color:#0f172a;}
			.gvfont-role-row .role-info span{font-size:11.5px;color:#94a3b8;}
			.gvfont-filerow{display:grid;grid-template-columns:1fr 1fr 1.4fr auto;gap:10px;align-items:center;margin-bottom:8px;}
			#gvfont-filerows input[type=file]{max-width:none;}
			.gvfont-addrow{font-size:12.5px;color:#0e4037;font-weight:700;cursor:pointer;background:none;border:1px dashed #0e4037;border-radius:8px;padding:6px 12px;}
			@media(max-width:782px){ .gvfont-role-row, .gvfont-filerow{ grid-template-columns:1fr; } }
		</style>

		<div class="gvfont-header">
			<h1>🔤 مدیریت فونت سایت</h1>
			<p>فونت دلخواه را برای هر بخش از سایت (متن، سربرگ، منو، دکمه، لوگو) جداگانه انتخاب کنید.</p>
		</div>

		<?php if ( isset( $_GET['updated'] ) ) : ?><div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div><?php endif; ?>
		<?php if ( isset( $_GET['uploaded'] ) ) : ?><div class="notice notice-success is-dismissible"><p>فونت با موفقیت اضافه شد.</p></div><?php endif; ?>
		<?php if ( isset( $_GET['deleted'] ) ) : ?><div class="notice notice-success is-dismissible"><p>مورد حذف شد.</p></div><?php endif; ?>
		<?php if ( isset( $_GET['error'] ) ) : ?><div class="notice notice-error is-dismissible"><p>خطا: نام فونت یا فایل معتبر ارسال نشد (فرمت‌های مجاز: woff2, woff, ttf, otf).</p></div><?php endif; ?>

		<div class="gvfont-card">
			<h2>➕ افزودن فونت / وزن جدید به کتابخانه</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="gv_font_upload">
				<?php wp_nonce_field( GV_FONT_NONCE ); ?>
				<div class="gvfont-field">
					<label>نام فونت</label>
					<input type="text" name="font_name" placeholder="مثلاً: وزیرمتن، ایران‌یکان، دیره">
					<small style="color:#94a3b8;">اگر نام فونتی که قبلاً اضافه کرده‌اید را دوباره وارد کنید، فایل‌های جدید به‌عنوان وزن‌های اضافه به همان فونت افزوده می‌شوند.</small>
				</div>

				<label style="display:block;font-weight:700;font-size:13px;margin-bottom:8px;color:#334155;">فایل‌های فونت (هر فایل = یک وزن مشخص)</label>
				<div id="gvfont-filerows">
					<div class="gvfont-filerow">
						<select name="file_weight[]">
							<?php foreach ( array( 100, 200, 300, 400, 500, 600, 700, 800, 900 ) as $w ) : ?>
								<option value="<?php echo $w; ?>" <?php selected( $w, 400 ); ?>><?php echo esc_html( gv_font_weight_label( $w ) ); ?></option>
							<?php endforeach; ?>
						</select>
						<select name="file_style[]">
							<option value="normal">حالت عادی</option>
							<option value="italic">کج (Italic)</option>
						</select>
						<input type="file" name="font_files[]" accept=".woff2,.woff,.ttf,.otf">
						<span></span>
					</div>
				</div>
				<button type="button" class="gvfont-addrow" onclick="gvFontAddRow()">➕ افزودن وزن دیگر</button>
				<div style="margin-top:18px;">
					<button type="submit" class="gvfont-btn">📤 آپلود</button>
				</div>
			</form>
			<script>
			function gvFontAddRow(){
				var wrap = document.getElementById('gvfont-filerows');
				var row = wrap.firstElementChild.cloneNode(true);
				row.querySelectorAll('input[type=file]').forEach(function(i){ i.value=''; });
				var del = document.createElement('button');
				del.type = 'button';
				del.textContent = '✕';
				del.className = 'gvfont-btn secondary';
				del.style.padding = '6px 12px';
				del.onclick = function(){ row.remove(); };
				row.lastElementChild.replaceWith(del);
				wrap.appendChild(row);
			}
			</script>
		</div>

		<div class="gvfont-card">
			<h2>📚 کتابخانه فونت‌ها</h2>
			<?php if ( empty( $library ) ) : ?>
				<p style="color:#94a3b8;">هنوز فونتی اضافه نکرده‌اید.</p>
			<?php else : ?>
				<?php
				$used_slugs = wp_list_pluck( $s['roles'], 'font' );
				foreach ( $library as $slug => $font ) :
					$is_used = in_array( $slug, $used_slugs, true );
					$regular_file = gv_font_closest_weight_file( $font['files'], 400, 'normal' );
					?>
					<div class="gvfont-lib-item <?php echo $is_used ? 'is-used' : ''; ?>">
						<div class="gvfont-lib-item-top">
							<div>
								<div class="gvfont-lib-name"><?php echo esc_html( $font['name'] ); ?> <?php echo $is_used ? '<span style="color:#0e4037;font-size:11px;">(در حال استفاده)</span>' : ''; ?></div>
								<div class="gvfont-lib-tags">
									<?php foreach ( $font['files'] as $f ) : ?>
										<span class="gvfont-tag">
											<?php echo esc_html( gv_font_weight_label( $f['weight'] ) . ( $f['style'] === 'italic' ? ' کج' : '' ) ); ?>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=gv_font_delete_weight&slug=' . $slug . '&weight=' . $f['weight'] . '&style=' . $f['style'] ), GV_FONT_NONCE ) ); ?>" onclick="return confirm('این وزن حذف شود؟');">✕</a>
										</span>
									<?php endforeach; ?>
								</div>
							</div>
							<a class="gvfont-del" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=gv_font_delete&slug=' . $slug ), GV_FONT_NONCE ) ); ?>" onclick="return confirm('کل این فونت (همه وزن‌ها) حذف شود؟');">🗑️ حذف کامل</a>
						</div>
						<?php if ( $regular_file ) : ?>
						<div class="gvfont-preview" style="font-family:'GVPreview-<?php echo esc_attr( $slug ); ?>', Tahoma, sans-serif;">
							<style>
								@font-face{ font-family:'GVPreview-<?php echo esc_attr( $slug ); ?>'; src:url('<?php echo esc_url( trailingslashit( $paths['url'] ) . $regular_file['file'] ); ?>') format('<?php echo gv_font_format( $regular_file['file'] ); ?>'); font-display:swap; }
							</style>
							سلام! این یک متن پیش‌نمایش برای «<?php echo esc_html( $font['name'] ); ?>» است — Aa 123
						</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $library ) ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gv_font_save_settings">
			<?php wp_nonce_field( GV_FONT_NONCE ); ?>

			<div class="gvfont-card">
				<h2>🧩 فونت هر بخش سایت</h2>
				<p style="color:#64748b;font-size:12.5px;margin-top:-6px;">برای هر بخش، یک فونت و وزن از کتابخانه انتخاب کنید. اگر «بدون تغییر» بگذارید، همان فونت بدنه یا تم استفاده می‌شود.</p>

				<?php foreach ( $roles as $key => $role ) : $rv = $s['roles'][ $key ]; ?>
					<div class="gvfont-role-row">
						<div class="role-info">
							<b><?php echo esc_html( $role['label'] ); ?></b>
							<span><?php echo esc_html( $role['desc'] ); ?></span>
						</div>
						<div>
							<label style="font-size:12px;font-weight:700;color:#334155;">فونت</label>
							<select name="role_font_<?php echo esc_attr( $key ); ?>" style="width:100%;">
								<option value=""><?php echo $key === 'body' ? '— فونت پیش‌فرض تم —' : '— وراثت از متن اصلی —'; ?></option>
								<?php foreach ( $library as $slug => $font ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $rv['font'], $slug ); ?>><?php echo esc_html( $font['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<label style="font-size:12px;font-weight:700;color:#334155;">وزن</label>
							<select name="role_weight_<?php echo esc_attr( $key ); ?>" style="width:100%;">
								<?php foreach ( array( 100, 200, 300, 400, 500, 600, 700, 800, 900 ) as $w ) : ?>
									<option value="<?php echo $w; ?>" <?php selected( $rv['weight'], $w ); ?>><?php echo esc_html( gv_font_weight_label( $w ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="gvfont-card">
				<h2>⚙️ تنظیمات عمومی</h2>
				<div class="gvfont-field">
					<label><input type="checkbox" name="enabled" <?php checked( $s['enabled'], 1 ); ?>> اعمال این تنظیمات روی کل سایت</label>
				</div>
				<div class="gvfont-field">
					<label>سایز پایه متن بدنه سایت (پیکسل)</label>
					<input type="number" name="base_size" min="12" max="22" value="<?php echo esc_attr( $s['base_size'] ); ?>">
				</div>
				<div class="gvfont-field">
					<label>ضریب بزرگ‌شدن سربرگ‌ها (h1 تا h6) — پیش‌فرض ۱.۲۵ توصیه می‌شود</label>
					<input type="number" step="0.01" name="scale" min="1.05" max="1.6" value="<?php echo esc_attr( $s['scale'] ); ?>">
					<small style="display:block;color:#94a3b8;margin-top:4px;">با این عدد، اندازه h1 تا h6 به‌صورت خودکار و متناسب محاسبه می‌شود؛ نیازی به تنظیم دستی هر سربرگ نیست.</small>
				</div>
				<button type="submit" class="gvfont-btn">💾 ذخیره و اعمال</button>
			</div>
		</form>
		<?php endif; ?>

		<p style="font-size:11.5px;color:#888;text-align:center;margin-top:24px;">
			نکته: برای بهترین نتیجه، حتماً فرمت <code>woff2</code> آپلود کنید (سبک‌ترین و سریع‌ترین فرمت برای وب).
		</p>
	</div>
	<?php
}

/* ==========================================================================
   خروجی CSS در سمت سایت
   ========================================================================== */

add_action( 'wp_head', 'gv_font_output_css', 40 );
function gv_font_output_css() {
	$s = gv_font_get_settings();
	if ( empty( $s['enabled'] ) ) { return; }

	$library = gv_font_get_library();
	$roles   = gv_font_get_roles();
	$paths   = gv_font_upload_dir();

	$body_font = $s['roles']['body']['font'];

	// فونت‌هایی که واقعاً استفاده می‌شوند را جمع می‌کنیم تا فقط برای آن‌ها @font-face بسازیم
	$used_fonts = array();
	foreach ( $s['roles'] as $key => $rv ) {
		$font = $rv['font'];
		if ( '' === $font && 'body' !== $key ) { $font = $body_font; } // وراثت
		if ( '' !== $font && isset( $library[ $font ] ) ) { $used_fonts[ $font ] = true; }
	}

	if ( empty( $used_fonts ) ) { return; }

	$base  = (float) $s['base_size'];
	$scale = (float) $s['scale'];
	$sizes = array(
		'h1' => round( $base * pow( $scale, 5 ), 1 ),
		'h2' => round( $base * pow( $scale, 4 ), 1 ),
		'h3' => round( $base * pow( $scale, 3 ), 1 ),
		'h4' => round( $base * pow( $scale, 2 ), 1 ),
		'h5' => round( $base * pow( $scale, 1 ), 1 ),
		'h6' => round( $base * 1.05, 1 ),
	);
	?>
	<style id="gv-font-manager-css">
		<?php foreach ( $used_fonts as $slug => $x ) :
			$font   = $library[ $slug ];
			$family = 'GVFont-' . $slug;
			foreach ( $font['files'] as $f ) : ?>
		@font-face{
			font-family:'<?php echo esc_html( $family ); ?>';
			src: url('<?php echo esc_url( trailingslashit( $paths['url'] ) . $f['file'] ); ?>') format('<?php echo gv_font_format( $f['file'] ); ?>');
			font-weight:<?php echo (int) $f['weight']; ?>; font-style:<?php echo esc_html( $f['style'] ); ?>; font-display:swap;
		}
			<?php endforeach;
		endforeach; ?>

		body{ font-size:<?php echo esc_html( $base ); ?>px; }

		<?php foreach ( $roles as $key => $role_def ) :
			$rv   = $s['roles'][ $key ];
			$font = $rv['font'];
			if ( '' === $font && 'body' !== $key ) { $font = $body_font; }
			if ( '' === $font || ! isset( $library[ $font ] ) ) { continue; }
			$family = 'GVFont-' . $font;
			?>
		<?php echo $role_def['selector']; ?>{
			font-family:'<?php echo esc_html( $family ); ?>', -apple-system, Tahoma, sans-serif !important;
			font-weight:<?php echo (int) $rv['weight']; ?> !important;
			<?php if ( 'heading' === $key ) : ?>
			line-height:1.4;
			<?php endif; ?>
		}
		<?php endforeach; ?>

		h1{ font-size:<?php echo esc_html( $sizes['h1'] ); ?>px !important; }
		h2{ font-size:<?php echo esc_html( $sizes['h2'] ); ?>px !important; }
		h3{ font-size:<?php echo esc_html( $sizes['h3'] ); ?>px !important; }
		h4{ font-size:<?php echo esc_html( $sizes['h4'] ); ?>px !important; }
		h5{ font-size:<?php echo esc_html( $sizes['h5'] ); ?>px !important; }
		h6{ font-size:<?php echo esc_html( $sizes['h6'] ); ?>px !important; }
	</style>
	<?php
}

function gv_font_format( $filename ) {
	$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	$map = array( 'woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype', 'otf' => 'opentype' );
	return $map[ $ext ] ?? 'woff2';
}
