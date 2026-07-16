<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * ==========================================================
 *  Groot Vision — مدیریت فونت سایت
 *  آپلود فایل فونت (woff2/woff/ttf)، ساخت فونت‌فیس خودکار،
 *  و اعمال آن روی کل سایت با یک مقیاس تایپوگرافی هوشمند
 *  (h1 بزرگ‌تر، h2 کمی کوچک‌تر و ...)
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

function gv_font_default_settings() {
	return array(
		'enabled'     => 0,
		'active_font' => '', // slug فونت انتخاب‌شده از کتابخانه
		'base_size'   => 16, // سایز فونت پایه بدنه سایت (px)
		'scale'       => 1.25, // نسبت مقیاس تایپوگرافی بین سربرگ‌ها (Major Third پیش‌فرض)
	);
}

function gv_font_get_settings() {
	return wp_parse_args( get_option( GV_FONT_OPT, array() ), gv_font_default_settings() );
}

/** کتابخانه فونت‌های آپلودشده: آرایه‌ای از slug => { name, regular, bold, italic } */
function gv_font_get_library() {
	return get_option( GV_FONT_LIST_OPT, array() );
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

/* ---- افزودن فونت جدید به کتابخانه ---- */
add_action( 'admin_post_gv_font_upload', 'gv_font_handle_upload' );
function gv_font_handle_upload() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_FONT_NONCE );

	$name = sanitize_text_field( $_POST['font_name'] ?? '' );
	if ( empty( $name ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=gv-font-manager&error=name' ) );
		exit;
	}
	$slug = sanitize_title( $name );
	$paths = gv_font_upload_dir();

	$allowed_ext = array( 'woff2', 'woff', 'ttf', 'otf' );
	$saved_files = array();

	foreach ( array( 'regular', 'bold', 'italic' ) as $variant ) {
		if ( empty( $_FILES[ 'font_' . $variant ] ) || empty( $_FILES[ 'font_' . $variant ]['name'] ) ) { continue; }

		$file = $_FILES[ 'font_' . $variant ];
		$ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, $allowed_ext, true ) ) { continue; }
		if ( ! empty( $file['error'] ) ) { continue; }

		$dest_name = $slug . '-' . $variant . '.' . $ext;
		$dest_path = trailingslashit( $paths['dir'] ) . $dest_name;

		if ( move_uploaded_file( $file['tmp_name'], $dest_path ) ) {
			$saved_files[ $variant ] = $dest_name;
		}
	}

	if ( empty( $saved_files['regular'] ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=gv-font-manager&error=file' ) );
		exit;
	}

	$library = gv_font_get_library();
	$library[ $slug ] = array(
		'name'    => $name,
		'regular' => $saved_files['regular'] ?? '',
		'bold'    => $saved_files['bold'] ?? '',
		'italic'  => $saved_files['italic'] ?? '',
	);
	update_option( GV_FONT_LIST_OPT, $library );

	wp_safe_redirect( admin_url( 'admin.php?page=gv-font-manager&uploaded=1' ) );
	exit;
}

add_action( 'admin_post_gv_font_delete', 'gv_font_handle_delete' );
function gv_font_handle_delete() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_FONT_NONCE );

	$slug = sanitize_key( $_GET['slug'] ?? '' );
	$library = gv_font_get_library();
	if ( isset( $library[ $slug ] ) ) {
		$paths = gv_font_upload_dir();
		foreach ( array( 'regular', 'bold', 'italic' ) as $v ) {
			if ( ! empty( $library[ $slug ][ $v ] ) ) {
				$f = trailingslashit( $paths['dir'] ) . $library[ $slug ][ $v ];
				if ( file_exists( $f ) ) { @unlink( $f ); }
			}
		}
		unset( $library[ $slug ] );
		update_option( GV_FONT_LIST_OPT, $library );

		$s = gv_font_get_settings();
		if ( $s['active_font'] === $slug ) {
			$s['active_font'] = '';
			update_option( GV_FONT_OPT, $s );
		}
	}
	wp_safe_redirect( admin_url( 'admin.php?page=gv-font-manager&deleted=1' ) );
	exit;
}

add_action( 'admin_post_gv_font_save_settings', 'gv_font_save_settings' );
function gv_font_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_FONT_NONCE );

	$library = gv_font_get_library();
	$active  = sanitize_key( $_POST['active_font'] ?? '' );
	if ( '' !== $active && ! isset( $library[ $active ] ) ) { $active = ''; }

	$settings = array(
		'enabled'     => isset( $_POST['enabled'] ) ? 1 : 0,
		'active_font' => $active,
		'base_size'   => max( 12, min( 22, intval( $_POST['base_size'] ?? 16 ) ) ),
		'scale'       => max( 1.05, min( 1.6, floatval( $_POST['scale'] ?? 1.25 ) ) ),
	);
	update_option( GV_FONT_OPT, $settings );
	wp_safe_redirect( admin_url( 'admin.php?page=gv-font-manager&updated=1' ) );
	exit;
}

/* ==========================================================================
   صفحه مدیریت
   ========================================================================== */

function gv_font_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$s       = gv_font_get_settings();
	$library = gv_font_get_library();
	?>
	<div class="wrap" dir="rtl" style="font-family: Tahoma, sans-serif; max-width:1000px;">
		<style>
			.gvfont-header{background:linear-gradient(120deg,#0e4037,#145c4d);color:#fff;padding:22px 26px;border-radius:14px;margin:20px 0;}
			.gvfont-header h1{margin:0;font-size:20px;color:#fff;}
			.gvfont-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:22px;margin-bottom:18px;}
			.gvfont-card h2{margin-top:0;font-size:15px;}
			.gvfont-field{margin-bottom:14px;}
			.gvfont-field label{display:block;font-weight:700;font-size:13px;margin-bottom:5px;color:#334155;}
			.gvfont-field input[type=text],.gvfont-field input[type=file],.gvfont-field input[type=number]{width:100%;max-width:380px;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;}
			.gvfont-btn{background:#111827;color:#fff !important;border:none;padding:10px 22px;border-radius:10px;font-weight:600;cursor:pointer;}
			.gvfont-lib-item{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:10px;}
			.gvfont-lib-item.is-active{border-color:#0e4037;background:#f0fdf9;}
			.gvfont-lib-name{font-weight:700;font-size:14px;}
			.gvfont-lib-tag{font-size:11px;color:#94a3b8;}
			.gvfont-del{color:#b91c1c;text-decoration:none;font-size:12.5px;}
			.gvfont-preview{border:1px dashed #cbd5e1;border-radius:12px;padding:20px;margin-top:10px;}
		</style>

		<div class="gvfont-header"><h1>🔤 مدیریت فونت سایت</h1></div>

		<?php if ( isset( $_GET['updated'] ) ) : ?><div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div><?php endif; ?>
		<?php if ( isset( $_GET['uploaded'] ) ) : ?><div class="notice notice-success is-dismissible"><p>فونت با موفقیت اضافه شد.</p></div><?php endif; ?>
		<?php if ( isset( $_GET['deleted'] ) ) : ?><div class="notice notice-success is-dismissible"><p>فونت حذف شد.</p></div><?php endif; ?>
		<?php if ( isset( $_GET['error'] ) ) : ?><div class="notice notice-error is-dismissible"><p>خطا: نام فونت یا فایل معتبر ارسال نشد (فرمت‌های مجاز: woff2, woff, ttf, otf).</p></div><?php endif; ?>

		<div class="gvfont-card">
			<h2>➕ افزودن فونت جدید به کتابخانه</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="gv_font_upload">
				<?php wp_nonce_field( GV_FONT_NONCE ); ?>
				<div class="gvfont-field">
					<label>نام فونت</label>
					<input type="text" name="font_name" placeholder="مثلاً: وزیرمتن، ایران‌یکان، دیره">
				</div>
				<div class="gvfont-field">
					<label>فایل حالت عادی (Regular) — الزامی</label>
					<input type="file" name="font_regular" accept=".woff2,.woff,.ttf,.otf">
				</div>
				<div class="gvfont-field">
					<label>فایل حالت ضخیم (Bold) — اختیاری</label>
					<input type="file" name="font_bold" accept=".woff2,.woff,.ttf,.otf">
				</div>
				<div class="gvfont-field">
					<label>فایل حالت کج (Italic) — اختیاری</label>
					<input type="file" name="font_italic" accept=".woff2,.woff,.ttf,.otf">
				</div>
				<button type="submit" class="gvfont-btn">📤 آپلود فونت</button>
			</form>
		</div>

		<div class="gvfont-card">
			<h2>📚 کتابخانه فونت‌ها</h2>
			<?php if ( empty( $library ) ) : ?>
				<p style="color:#94a3b8;">هنوز فونتی اضافه نکرده‌اید.</p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="gv_font_save_settings">
					<?php wp_nonce_field( GV_FONT_NONCE ); ?>

					<?php foreach ( $library as $slug => $font ) : ?>
						<label class="gvfont-lib-item <?php echo $s['active_font'] === $slug ? 'is-active' : ''; ?>">
							<span>
								<input type="radio" name="active_font" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $s['active_font'], $slug ); ?>>
								<span class="gvfont-lib-name"><?php echo esc_html( $font['name'] ); ?></span>
								<span class="gvfont-lib-tag"> — <?php echo $font['bold'] ? 'دارای Bold' : 'بدون Bold'; ?><?php echo $font['italic'] ? '، دارای Italic' : ''; ?></span>
							</span>
							<a class="gvfont-del" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=gv_font_delete&slug=' . $slug ), GV_FONT_NONCE ) ); ?>" onclick="return confirm('حذف این فونت؟');">🗑️ حذف</a>
						</label>
					<?php endforeach; ?>

					<div class="gvfont-field" style="margin-top:18px;">
						<label><input type="checkbox" name="enabled" <?php checked( $s['enabled'], 1 ); ?>> اعمال فونت انتخاب‌شده روی کل سایت</label>
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
				</form>
			<?php endif; ?>
		</div>

		<p style="font-size:11.5px;color:#888;text-align:center;margin-top:24px;">
			نکته: می‌توانید به‌جای آپلود، فایل‌های فونت را مستقیماً داخل پوشه <code>wp-content/plugins/groot-vision-tools/fonts/</code> هم قرار دهید؛ اما ساده‌ترین راه همین فرم بالاست چون خودکار در آدرس uploads سایت ذخیره و بارگذاری می‌شود.
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
	if ( empty( $s['enabled'] ) || empty( $s['active_font'] ) ) { return; }

	$library = gv_font_get_library();
	if ( empty( $library[ $s['active_font'] ] ) ) { return; }

	$font  = $library[ $s['active_font'] ];
	$paths = gv_font_upload_dir();
	$family_slug = 'GVFont-' . $s['active_font'];

	$base  = (float) $s['base_size'];
	$scale = (float) $s['scale'];
	// مقیاس تایپوگرافی: هر سربرگ بر اساس فاصله‌اش تا h6 با توان scale بزرگ می‌شود
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
		@font-face{
			font-family:'<?php echo esc_html( $family_slug ); ?>';
			src: url('<?php echo esc_url( trailingslashit( $paths['url'] ) . $font['regular'] ); ?>') format('<?php echo gv_font_format( $font['regular'] ); ?>');
			font-weight:400; font-style:normal; font-display:swap;
		}
		<?php if ( ! empty( $font['bold'] ) ) : ?>
		@font-face{
			font-family:'<?php echo esc_html( $family_slug ); ?>';
			src: url('<?php echo esc_url( trailingslashit( $paths['url'] ) . $font['bold'] ); ?>') format('<?php echo gv_font_format( $font['bold'] ); ?>');
			font-weight:700; font-style:normal; font-display:swap;
		}
		<?php endif; ?>
		<?php if ( ! empty( $font['italic'] ) ) : ?>
		@font-face{
			font-family:'<?php echo esc_html( $family_slug ); ?>';
			src: url('<?php echo esc_url( trailingslashit( $paths['url'] ) . $font['italic'] ); ?>') format('<?php echo gv_font_format( $font['italic'] ); ?>');
			font-weight:400; font-style:italic; font-display:swap;
		}
		<?php endif; ?>

		body, p, li, span, a, input, textarea, select, button{
			font-family:'<?php echo esc_html( $family_slug ); ?>', -apple-system, Tahoma, sans-serif !important;
			font-size:<?php echo esc_html( $base ); ?>px;
		}
		h1{ font-family:'<?php echo esc_html( $family_slug ); ?>', Tahoma, sans-serif !important; font-size:<?php echo esc_html( $sizes['h1'] ); ?>px !important; font-weight:700; line-height:1.35; }
		h2{ font-family:'<?php echo esc_html( $family_slug ); ?>', Tahoma, sans-serif !important; font-size:<?php echo esc_html( $sizes['h2'] ); ?>px !important; font-weight:700; line-height:1.4; }
		h3{ font-family:'<?php echo esc_html( $family_slug ); ?>', Tahoma, sans-serif !important; font-size:<?php echo esc_html( $sizes['h3'] ); ?>px !important; font-weight:700; line-height:1.45; }
		h4{ font-family:'<?php echo esc_html( $family_slug ); ?>', Tahoma, sans-serif !important; font-size:<?php echo esc_html( $sizes['h4'] ); ?>px !important; font-weight:700; line-height:1.5; }
		h5{ font-family:'<?php echo esc_html( $family_slug ); ?>', Tahoma, sans-serif !important; font-size:<?php echo esc_html( $sizes['h5'] ); ?>px !important; font-weight:700; line-height:1.5; }
		h6{ font-family:'<?php echo esc_html( $family_slug ); ?>', Tahoma, sans-serif !important; font-size:<?php echo esc_html( $sizes['h6'] ); ?>px !important; font-weight:700; line-height:1.55; }
	</style>
	<?php
}

function gv_font_format( $filename ) {
	$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	$map = array( 'woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype', 'otf' => 'opentype' );
	return $map[ $ext ] ?? 'woff2';
}
