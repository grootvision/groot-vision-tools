<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * ==========================================================
 *  Groot Vision — به‌روزرسانی خودکار از گیت‌هاب
 *  هر وقت یک Release جدید روی مخزن گیت‌هاب منتشر شود، وردپرس
 *  همان دکمه استاندارد «به‌روزرسانی موجود است» را در صفحه
 *  افزونه‌ها نشان می‌دهد + یک اعلان با لیست تغییرات (changelog)
 *  که مستقیماً از توضیحات Release در گیت‌هاب خوانده می‌شود.
 *
 *  نکته مهم: برای اینکه این بخش کار کند، باید:
 *   ۱) نام کاربری/سازمان و نام مخزن گیت‌هاب را در تنظیمات وارد کنید.
 *   ۲) هر بار که نسخه جدید آماده شد، عدد Version در بالای فایل
 *      groot-vision-tools.php را افزایش دهید (مثلاً از 1.1.0 به 1.2.0)
 *      و در گیت‌هاب یک Release جدید با تگ دقیقاً همان شماره نسخه
 *      (مثلاً v1.2.0 یا 1.2.0) بسازید و توضیحات تغییرات را در
 *      قسمت "Describe this release" بنویسید — همین متن، همان
 *      چیزی است که به‌عنوان changelog داخل وردپرس نمایش داده می‌شود.
 * ==========================================================
 */

define( 'GV_GHU_OPT', 'gv_github_updater_settings' );
define( 'GV_GHU_NONCE', 'gv_ghu_nonce_action' );
define( 'GV_GHU_SLUG', plugin_basename( GV_TOOLS_PATH . 'groot-vision-tools.php' ) ); // مسیر نسبی افزونه، مثل groot-vision-tools/groot-vision-tools.php
define( 'GV_GHU_CACHE_KEY', 'gv_ghu_latest_release_cache' );

function gv_ghu_default_settings() {
	return array(
		'enabled'     => 1,
		'repo_owner'  => '', // مثال: grootvision
		'repo_name'   => '', // مثال: groot-vision-tools
		'token'       => '', // فقط برای مخزن خصوصی لازم است، برای عمومی خالی بگذارید
		'last_seen_version'   => '', // آخرین نسخه‌ای که کاربر اعلانش را دیده
	);
}

function gv_ghu_get_settings() {
	return wp_parse_args( get_option( GV_GHU_OPT, array() ), gv_ghu_default_settings() );
}

/* ==========================================================================
   ۱) منوی مدیریت
   ========================================================================== */

add_action( 'admin_menu', 'gv_ghu_admin_menu' );
function gv_ghu_admin_menu() {
	add_submenu_page(
		'groot-vision-hub',
		'به‌روزرسانی از گیت‌هاب | Groot Vision',
		'🔄 به‌روزرسانی گیت‌هاب',
		'manage_options',
		'gv-github-updater',
		'gv_ghu_render_admin_page'
	);
}

add_action( 'admin_post_gv_ghu_save_settings', 'gv_ghu_save_settings' );
function gv_ghu_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_GHU_NONCE );

	$old = gv_ghu_get_settings();
	$settings = array(
		'enabled'    => isset( $_POST['enabled'] ) ? 1 : 0,
		'repo_owner' => sanitize_text_field( $_POST['repo_owner'] ?? '' ),
		'repo_name'  => sanitize_text_field( $_POST['repo_name'] ?? '' ),
		'token'      => sanitize_text_field( $_POST['token'] ?? '' ),
		'last_seen_version' => $old['last_seen_version'],
	);
	update_option( GV_GHU_OPT, $settings );
	delete_transient( GV_GHU_CACHE_KEY );
	wp_safe_redirect( admin_url( 'admin.php?page=gv-github-updater&updated=1' ) );
	exit;
}

add_action( 'admin_post_gv_ghu_force_check', 'gv_ghu_force_check' );
function gv_ghu_force_check() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_GHU_NONCE );
	delete_transient( GV_GHU_CACHE_KEY );
	delete_site_transient( 'update_plugins' );
	wp_safe_redirect( admin_url( 'admin.php?page=gv-github-updater&checked=1' ) );
	exit;
}

/* ==========================================================================
   ۲) گرفتن اطلاعات آخرین Release از API گیت‌هاب (با کش ۶ ساعته)
   ========================================================================== */

function gv_ghu_get_latest_release() {
	$cached = get_transient( GV_GHU_CACHE_KEY );
	if ( false !== $cached ) { return $cached; }

	$s = gv_ghu_get_settings();
	if ( empty( $s['repo_owner'] ) || empty( $s['repo_name'] ) ) { return null; }

	$url = sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', rawurlencode( $s['repo_owner'] ), rawurlencode( $s['repo_name'] ) );

	$args = array(
		'headers' => array(
			'Accept'     => 'application/vnd.github+json',
			'User-Agent' => 'GrootVisionTools-Updater',
		),
		'timeout' => 15,
	);
	if ( ! empty( $s['token'] ) ) {
		$args['headers']['Authorization'] = 'Bearer ' . $s['token'];
	}

	$response = wp_remote_get( $url, $args );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		set_transient( GV_GHU_CACHE_KEY, null, 15 * MINUTE_IN_SECONDS ); // خطا را کمی کش کن تا API اسپم نشود
		return null;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $body['tag_name'] ) ) {
		set_transient( GV_GHU_CACHE_KEY, null, 15 * MINUTE_IN_SECONDS );
		return null;
	}

	$version = ltrim( $body['tag_name'], 'vV' );

	// دنبال یک فایل zip دارایی (asset) بگرد؛ اگر نبود از zipball_url خود گیت‌هاب استفاده کن
	$package = $body['zipball_url'] ?? '';
	if ( ! empty( $body['assets'] ) && is_array( $body['assets'] ) ) {
		foreach ( $body['assets'] as $asset ) {
			if ( ! empty( $asset['browser_download_url'] ) && preg_match( '/\.zip$/i', $asset['name'] ?? '' ) ) {
				$package = $asset['browser_download_url'];
				break;
			}
		}
	}

	$data = array(
		'version'     => $version,
		'changelog'   => $body['body'] ?? '',
		'package'     => $package,
		'html_url'    => $body['html_url'] ?? '',
		'published_at'=> $body['published_at'] ?? '',
		'name'        => $body['name'] ?? $body['tag_name'],
	);

	set_transient( GV_GHU_CACHE_KEY, $data, 6 * HOUR_IN_SECONDS );
	return $data;
}

/* ==========================================================================
   ۳) قلاب‌های استاندارد وردپرس برای نمایش دکمه به‌روزرسانی
   ========================================================================== */

add_filter( 'pre_set_site_transient_update_plugins', 'gv_ghu_inject_update' );
function gv_ghu_inject_update( $transient ) {
	$s = gv_ghu_get_settings();
	if ( empty( $s['enabled'] ) ) { return $transient; }
	if ( empty( $transient ) || ! is_object( $transient ) ) { return $transient; }

	$release = gv_ghu_get_latest_release();
	if ( empty( $release ) ) { return $transient; }

	$current_version = defined( 'GV_TOOLS_VERSION' ) ? GV_TOOLS_VERSION : '0';

	if ( version_compare( $release['version'], $current_version, '>' ) ) {
		$item = new stdClass();
		$item->slug        = dirname( GV_GHU_SLUG );
		$item->plugin      = GV_GHU_SLUG;
		$item->new_version = $release['version'];
		$item->url         = $release['html_url'];
		$item->package     = $release['package'];
		$item->tested      = get_bloginfo( 'version' );
		$transient->response[ GV_GHU_SLUG ] = $item;
	}

	return $transient;
}

add_filter( 'plugins_api', 'gv_ghu_plugins_api_details', 20, 3 );
function gv_ghu_plugins_api_details( $result, $action, $args ) {
	if ( 'plugin_information' !== $action ) { return $result; }
	if ( empty( $args->slug ) || $args->slug !== dirname( GV_GHU_SLUG ) ) { return $result; }

	$release = gv_ghu_get_latest_release();
	if ( empty( $release ) ) { return $result; }

	$info = new stdClass();
	$info->name          = 'Groot Vision Tools';
	$info->slug          = dirname( GV_GHU_SLUG );
	$info->version       = $release['version'];
	$info->author        = '<a href="https://grootvision.com">Groot Vision</a>';
	$info->homepage      = $release['html_url'];
	$info->download_link = $release['package'];
	$info->sections      = array(
		'description' => 'مجموعه ابزارهای اختصاصی گروت ویژن',
		'changelog'   => nl2br( esc_html( $release['changelog'] ) ),
	);
	return $info;
}

/* بعد از نصب zip گیت‌هاب، پوشه‌اش اسمش تصادفی است (مثل owner-repo-hash)؛ باید آن را
   به نام درست پوشه افزونه تغییر نام دهیم تا وردپرس گمش نکند. */
add_filter( 'upgrader_source_selection', 'gv_ghu_fix_folder_name', 10, 4 );
function gv_ghu_fix_folder_name( $source, $remote_source, $upgrader, $hook_extra ) {
	global $wp_filesystem;
	if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== GV_GHU_SLUG ) { return $source; }

	$correct_name = dirname( GV_GHU_SLUG );
	$desired      = trailingslashit( $remote_source ) . $correct_name . '/';

	if ( $source !== $desired && $wp_filesystem->exists( $source ) ) {
		if ( $wp_filesystem->move( $source, $desired ) ) {
			return $desired;
		}
	}
	return $source;
}

/* ==========================================================================
   ۴) اعلان داخل پیشخوان با خلاصه تغییرات، وقتی نسخه جدیدی منتشر شود
   ========================================================================== */

add_action( 'admin_notices', 'gv_ghu_admin_notice' );
function gv_ghu_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$s = gv_ghu_get_settings();
	if ( empty( $s['enabled'] ) ) { return; }

	$release = gv_ghu_get_latest_release();
	if ( empty( $release ) ) { return; }

	$current_version = defined( 'GV_TOOLS_VERSION' ) ? GV_TOOLS_VERSION : '0';
	if ( version_compare( $release['version'], $current_version, '<=' ) ) { return; }
	if ( $s['last_seen_version'] === $release['version'] ) { return; } // یک بار دیده شده، دوباره مزاحم نشو مگر از صفحه آپدیت خودش

	$changelog_short = wp_trim_words( wp_strip_all_tags( $release['changelog'] ), 40, '…' );
	?>
	<div class="notice notice-info is-dismissible" style="border-right-color:#0e4037;">
		<p style="font-size:14px;">
			🚀 <strong>نسخه جدید افزونه‌های گروت ویژن (<?php echo esc_html( $release['version'] ); ?>) منتشر شده است.</strong>
		</p>
		<p style="color:#475569;"><?php echo esc_html( $changelog_short ); ?></p>
		<p>
			<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="button button-primary">مشاهده و به‌روزرسانی</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=gv-github-updater' ) ); ?>" class="button">جزئیات کامل تغییرات</a>
		</p>
	</div>
	<?php
}

/* ==========================================================================
   ۵) صفحه مدیریت
   ========================================================================== */

function gv_ghu_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$s = gv_ghu_get_settings();

	// اگر کاربر وارد این صفحه شد، یعنی تغییرات را دیده — ذخیره کن که دیگر اعلان تکراری نشان ندهد
	$release = gv_ghu_get_latest_release();
	if ( ! empty( $release ) ) {
		$s['last_seen_version'] = $release['version'];
		update_option( GV_GHU_OPT, $s );
	}

	$current_version = defined( 'GV_TOOLS_VERSION' ) ? GV_TOOLS_VERSION : '0';
	?>
	<div class="wrap" dir="rtl" style="font-family: Tahoma, sans-serif; max-width:900px;">
		<style>
			.gvghu-header{display:flex;align-items:center;justify-content:space-between;background:linear-gradient(120deg,#0e4037,#145c4d);color:#fff;padding:22px 26px;border-radius:14px;margin:20px 0;flex-wrap:wrap;gap:12px;}
			.gvghu-header h1{margin:0;font-size:20px;color:#fff;}
			.gvghu-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:22px;margin-bottom:18px;}
			.gvghu-card h2{margin-top:0;font-size:15px;}
			.gvghu-field{margin-bottom:14px;}
			.gvghu-field label{display:block;font-weight:700;font-size:13px;margin-bottom:5px;color:#334155;}
			.gvghu-field input[type=text],.gvghu-field input[type=password]{width:100%;max-width:420px;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;}
			.gvghu-badge-cur{display:inline-block;background:#eef2ff;color:#3730a3;padding:4px 12px;border-radius:20px;font-size:12.5px;font-weight:700;}
			.gvghu-badge-new{display:inline-block;background:#dcfce7;color:#15803d;padding:4px 12px;border-radius:20px;font-size:12.5px;font-weight:700;}
			.gvghu-changelog{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;white-space:pre-wrap;font-size:13px;line-height:2;color:#334155;max-height:320px;overflow:auto;}
			.gvghu-btn{background:#111827;color:#fff !important;border:none;padding:10px 20px;border-radius:10px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;}
		</style>

		<div class="gvghu-header">
			<h1>🔄 به‌روزرسانی خودکار از گیت‌هاب</h1>
		</div>

		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['checked'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>بررسی مجدد انجام شد.</p></div>
		<?php endif; ?>

		<div class="gvghu-card">
			<h2>وضعیت فعلی</h2>
			<p>نسخه نصب‌شده: <span class="gvghu-badge-cur">v<?php echo esc_html( $current_version ); ?></span></p>
			<?php if ( $release ) : ?>
				<p>آخرین نسخه در گیت‌هاب: <span class="gvghu-badge-new">v<?php echo esc_html( $release['version'] ); ?></span>
				<?php if ( version_compare( $release['version'], $current_version, '>' ) ) : ?>
					— <a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">به‌روزرسانی موجود است، از صفحه افزونه‌ها اقدام کنید ↗</a>
				<?php else : ?>
					— شما آخرین نسخه را دارید ✅
				<?php endif; ?>
				</p>
				<p style="font-weight:700;margin-top:18px;">📋 لیست تغییرات این نسخه (متن Release در گیت‌هاب):</p>
				<div class="gvghu-changelog"><?php echo esc_html( $release['changelog'] ?: 'توضیحاتی برای این نسخه ثبت نشده.' ); ?></div>
			<?php else : ?>
				<p style="color:#94a3b8;">هنوز اطلاعاتی از گیت‌هاب دریافت نشده. مخزن را در پایین همین صفحه تنظیم کنید.</p>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px;">
				<input type="hidden" name="action" value="gv_ghu_force_check">
				<?php wp_nonce_field( GV_GHU_NONCE ); ?>
				<button type="submit" class="gvghu-btn">🔍 بررسی دستی برای نسخه جدید</button>
			</form>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gv_ghu_save_settings">
			<?php wp_nonce_field( GV_GHU_NONCE ); ?>

			<div class="gvghu-card">
				<h2>تنظیمات مخزن گیت‌هاب</h2>
				<div class="gvghu-field">
					<label><input type="checkbox" name="enabled" <?php checked( $s['enabled'], 1 ); ?>> فعال بودن بررسی خودکار به‌روزرسانی</label>
				</div>
				<div class="gvghu-field">
					<label>نام کاربری / سازمان گیت‌هاب</label>
					<input type="text" name="repo_owner" value="<?php echo esc_attr( $s['repo_owner'] ); ?>" placeholder="مثال: grootvision">
				</div>
				<div class="gvghu-field">
					<label>نام مخزن (Repository)</label>
					<input type="text" name="repo_name" value="<?php echo esc_attr( $s['repo_name'] ); ?>" placeholder="مثال: groot-vision-tools">
				</div>
				<div class="gvghu-field">
					<label>Personal Access Token (فقط برای مخزن خصوصی — برای مخزن عمومی خالی بگذارید)</label>
					<input type="password" name="token" value="<?php echo esc_attr( $s['token'] ); ?>" placeholder="ghp_xxxxxxxxxxxx">
				</div>
				<button type="submit" class="gvghu-btn">💾 ذخیره تنظیمات</button>
			</div>
		</form>

		<div class="gvghu-card">
			<h2>راهنمای سریع انتشار نسخه جدید</h2>
			<ol style="line-height:2.2;font-size:13.5px;color:#334155;">
				<li>در فایل <code>groot-vision-tools.php</code> مقدار <code>Version</code> را بالا ببرید (مثلاً 1.1.0 → 1.2.0).</li>
				<li>در ریشه پروژه یک تگ نسخه بسازید: <code>git tag v1.2.0 && git push origin v1.2.0</code></li>
				<li>در گیت‌هاب، از بخش Releases روی «Draft a new release» بزنید، همان تگ را انتخاب کنید و توضیح تغییرات را بنویسید، سپس Publish کنید.</li>
				<li>حداکثر تا ۶ ساعت بعد (یا با دکمه «بررسی دستی» بالا، فوری) وردپرس دکمه به‌روزرسانی را نشان می‌دهد و اعلان تغییرات هم در پیشخوان ظاهر می‌شود.</li>
			</ol>
		</div>

		<p style="font-size:11.5px;color:#888;text-align:center;">ساخته و توسعه‌یافته توسط <strong>Groot Vision</strong></p>
	</div>
	<?php
}
