<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * ==========================================================
 *  Groot Vision — آمار بازدید و رفتار کاربران
 *  ثبت بازدید هر صفحه، مدت‌زمان حضور، مسیر حرکت کاربر بین
 *  صفحات (از کدام صفحه به کدام صفحه رفته) و کلیک‌ها — همراه
 *  با یک داشبورد آماری.
 *  داده‌ها در دو جدول اختصاصی دیتابیس ذخیره می‌شوند تا فشاری
 *  روی جدول postmeta/options وارد نشود.
 * ==========================================================
 */
define( 'GV_VA_OPT', 'gv_visitor_analytics_settings' );
define( 'GV_VA_NONCE', 'gv_va_nonce_action' );
define( 'GV_VA_DB_VERSION', '1.0' );

function gv_va_default_settings() {
	return array(
		'enabled'         => 0,
		'track_clicks'    => 1,
		'track_time'      => 1,
		'track_path'      => 1,
		'ignore_admins'   => 1, // آمار مدیران سایت ثبت نشود تا آمار خودتان را نبینید
		'retention_days'  => 90, // بعد از چند روز داده‌های قدیمی خودکار پاک شوند (0 = هرگز)
	);
}

function gv_va_get_settings() {
	return wp_parse_args( get_option( GV_VA_OPT, array() ), gv_va_default_settings() );
}

/* ==========================================================================
   ۱) ساخت جداول دیتابیس (خودکار، بدون نیاز به activation hook جداگانه)
   ========================================================================== */

add_action( 'plugins_loaded', 'gv_va_maybe_install_db' );
function gv_va_maybe_install_db() {
	if ( get_option( 'gv_va_db_version' ) === GV_VA_DB_VERSION ) { return; }

	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$t_visits = $wpdb->prefix . 'gv_visits';
	$t_events = $wpdb->prefix . 'gv_events';

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( "CREATE TABLE {$t_visits} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		session_id VARCHAR(40) NOT NULL,
		page_url TEXT NOT NULL,
		page_title VARCHAR(255) NULL,
		referrer TEXT NULL,
		entry_time DATETIME NOT NULL,
		last_seen DATETIME NOT NULL,
		time_spent INT UNSIGNED NOT NULL DEFAULT 0,
		device VARCHAR(20) NULL,
		ip_hash VARCHAR(64) NULL,
		PRIMARY KEY  (id),
		KEY session_id (session_id),
		KEY entry_time (entry_time)
	) {$charset_collate};" );

	dbDelta( "CREATE TABLE {$t_events} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		session_id VARCHAR(40) NOT NULL,
		event_type VARCHAR(20) NOT NULL,
		page_url TEXT NOT NULL,
		target_text VARCHAR(255) NULL,
		target_href TEXT NULL,
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY session_id (session_id),
		KEY event_type (event_type),
		KEY created_at (created_at)
	) {$charset_collate};" );

	update_option( 'gv_va_db_version', GV_VA_DB_VERSION );
}

/* ==========================================================================
   ۲) منوی مدیریت
   ========================================================================== */

add_action( 'admin_menu', 'gv_va_admin_menu' );
function gv_va_admin_menu() {
	add_submenu_page(
		'groot-vision-hub',
		'آمار بازدید | Groot Vision',
		'📈 آمار بازدید',
		'manage_options',
		'gv-visitor-analytics',
		'gv_va_render_admin_page'
	);
}

add_action( 'admin_post_gv_va_save_settings', 'gv_va_save_settings' );
function gv_va_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_VA_NONCE );

	$settings = array(
		'enabled'        => isset( $_POST['enabled'] ) ? 1 : 0,
		'track_clicks'   => isset( $_POST['track_clicks'] ) ? 1 : 0,
		'track_time'     => isset( $_POST['track_time'] ) ? 1 : 0,
		'track_path'     => isset( $_POST['track_path'] ) ? 1 : 0,
		'ignore_admins'  => isset( $_POST['ignore_admins'] ) ? 1 : 0,
		'retention_days' => max( 0, min( 730, intval( $_POST['retention_days'] ?? 90 ) ) ),
	);
	update_option( GV_VA_OPT, $settings );
	wp_safe_redirect( admin_url( 'admin.php?page=gv-visitor-analytics&updated=1' ) );
	exit;
}

add_action( 'admin_post_gv_va_clear_data', 'gv_va_clear_data' );
function gv_va_clear_data() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( GV_VA_NONCE );
	global $wpdb;
	$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}gv_visits" );
	$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}gv_events" );
	wp_safe_redirect( admin_url( 'admin.php?page=gv-visitor-analytics&cleared=1' ) );
	exit;
}

/* پاکسازی خودکار داده‌های قدیمی (یک‌بار در روز) */
add_action( 'gv_va_daily_cleanup', 'gv_va_run_cleanup' );
if ( ! wp_next_scheduled( 'gv_va_daily_cleanup' ) ) {
	wp_schedule_event( time(), 'daily', 'gv_va_daily_cleanup' );
}
function gv_va_run_cleanup() {
	$s = gv_va_get_settings();
	if ( empty( $s['retention_days'] ) ) { return; }
	global $wpdb;
	$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $s['retention_days'] * DAY_IN_SECONDS ) );
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}gv_visits WHERE entry_time < %s", $cutoff ) );
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}gv_events WHERE created_at < %s", $cutoff ) );
}

/* ==========================================================================
   ۳) اسکریپت ردیابی سمت کاربر (فرانت‌اند)
   ========================================================================== */

add_action( 'wp_footer', 'gv_va_output_tracking_script' );
function gv_va_output_tracking_script() {
	if ( is_admin() ) { return; }
	$s = gv_va_get_settings();
	if ( empty( $s['enabled'] ) ) { return; }
	if ( ! empty( $s['ignore_admins'] ) && current_user_can( 'manage_options' ) ) { return; }

	$endpoint = admin_url( 'admin-ajax.php' );
	$nonce    = wp_create_nonce( 'gv_va_track' );
	$page_title = wp_strip_all_tags( get_the_title() ?: wp_get_document_title() );
	?>
	<script>
	(function(){
		var GV_VA = {
			endpoint: <?php echo wp_json_encode( $endpoint ); ?>,
			nonce: <?php echo wp_json_encode( $nonce ); ?>,
			trackClicks: <?php echo $s['track_clicks'] ? 'true' : 'false'; ?>,
			trackTime: <?php echo $s['track_time'] ? 'true' : 'false'; ?>,
			pageTitle: <?php echo wp_json_encode( $page_title ); ?>
		};

		function getSessionId(){
			var key = 'gv_va_sid';
			var sid = sessionStorage.getItem(key);
			if (!sid) {
				sid = 'gv_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 10);
				sessionStorage.setItem(key, sid);
			}
			return sid;
		}

		function send(action, data, useBeacon){
			data = data || {};
			data.action = 'gv_va_track';
			data.sub_action = action;
			data.nonce = GV_VA.nonce;
			data.session_id = getSessionId();
			data.page_url = window.location.href;

			var body = new URLSearchParams(data).toString();

			if (useBeacon && navigator.sendBeacon) {
				var blob = new Blob([body], { type: 'application/x-www-form-urlencoded' });
				navigator.sendBeacon(GV_VA.endpoint, blob);
			} else {
				fetch(GV_VA.endpoint, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: body,
					keepalive: true
				}).catch(function(){});
			}
		}

		// ثبت شروع بازدید
		send('pageview', {
			referrer: document.referrer || '',
			page_title: GV_VA.pageTitle
		});

		// ثبت کلیک‌ها
		if (GV_VA.trackClicks) {
			document.addEventListener('click', function(e){
				var el = e.target.closest('a, button');
				if (!el) return;
				send('click', {
					target_text: (el.innerText || el.value || '').slice(0, 100),
					target_href: el.getAttribute('href') || ''
				});
			}, { passive: true });
		}

		// ثبت مدت‌زمان حضور، هنگام ترک صفحه
		if (GV_VA.trackTime) {
			var startTime = Date.now();
			var sent = false;
			function sendTime(){
				if (sent) return;
				sent = true;
				var seconds = Math.round((Date.now() - startTime) / 1000);
				send('timespent', { seconds: seconds }, true);
			}
			document.addEventListener('visibilitychange', function(){
				if (document.visibilityState === 'hidden') sendTime();
			});
			window.addEventListener('pagehide', sendTime);
		}
	})();
	</script>
	<?php
}

/* ==========================================================================
   ۴) دریافت داده در سمت سرور (AJAX)
   ========================================================================== */

add_action( 'wp_ajax_gv_va_track', 'gv_va_handle_track' );
add_action( 'wp_ajax_nopriv_gv_va_track', 'gv_va_handle_track' );
function gv_va_handle_track() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gv_va_track' ) ) {
		wp_send_json_error( 'bad_nonce', 403 );
	}

	$s = gv_va_get_settings();
	if ( empty( $s['enabled'] ) ) { wp_send_json_success(); }

	global $wpdb;
	$session_id = sanitize_text_field( $_POST['session_id'] ?? '' );
	$page_url   = esc_url_raw( $_POST['page_url'] ?? '' );
	$sub        = sanitize_key( $_POST['sub_action'] ?? '' );

	if ( empty( $session_id ) || empty( $page_url ) ) { wp_send_json_error( 'missing_data', 400 ); }

	$t_visits = $wpdb->prefix . 'gv_visits';
	$t_events = $wpdb->prefix . 'gv_events';
	$now      = current_time( 'mysql' );
	$ip_hash  = hash( 'sha256', ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() );
	$ua       = $_SERVER['HTTP_USER_AGENT'] ?? '';
	$device   = preg_match( '/mobile|android|iphone/i', $ua ) ? 'mobile' : ( preg_match( '/tablet|ipad/i', $ua ) ? 'tablet' : 'desktop' );

	if ( 'pageview' === $sub ) {
		$wpdb->insert( $t_visits, array(
			'session_id' => $session_id,
			'page_url'   => $page_url,
			'page_title' => sanitize_text_field( $_POST['page_title'] ?? '' ),
			'referrer'   => esc_url_raw( $_POST['referrer'] ?? '' ),
			'entry_time' => $now,
			'last_seen'  => $now,
			'time_spent' => 0,
			'device'     => $device,
			'ip_hash'    => $ip_hash,
		) );

		if ( ! empty( $s['track_path'] ) ) {
			$wpdb->insert( $t_events, array(
				'session_id'  => $session_id,
				'event_type'  => 'pageview',
				'page_url'    => $page_url,
				'target_text' => sanitize_text_field( $_POST['page_title'] ?? '' ),
				'target_href' => '',
				'created_at'  => $now,
			) );
		}
	} elseif ( 'click' === $sub && ! empty( $s['track_clicks'] ) ) {
		$wpdb->insert( $t_events, array(
			'session_id'  => $session_id,
			'event_type'  => 'click',
			'page_url'    => $page_url,
			'target_text' => sanitize_text_field( $_POST['target_text'] ?? '' ),
			'target_href' => esc_url_raw( $_POST['target_href'] ?? '' ),
			'created_at'  => $now,
		) );
	} elseif ( 'timespent' === $sub && ! empty( $s['track_time'] ) ) {
		$seconds = max( 0, min( 7200, intval( $_POST['seconds'] ?? 0 ) ) );
		// آخرین بازدید همین سشن/صفحه را آپدیت کن
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$t_visits} SET time_spent = %d, last_seen = %s WHERE session_id = %s AND page_url = %s ORDER BY id DESC LIMIT 1",
			$seconds, $now, $session_id, $page_url
		) );
	}

	wp_send_json_success();
}

/* ==========================================================================
   ۵) صفحه داشبورد آمار
   ========================================================================== */

function gv_va_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	global $wpdb;
	$s        = gv_va_get_settings();
	$t_visits = $wpdb->prefix . 'gv_visits';
	$t_events = $wpdb->prefix . 'gv_events';
	$tab      = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';
	$range    = isset( $_GET['range'] ) ? max( 1, min( 365, intval( $_GET['range'] ) ) ) : 7;
	$since    = gmdate( 'Y-m-d H:i:s', time() - ( $range * DAY_IN_SECONDS ) );

	$total_visits   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t_visits} WHERE entry_time >= %s", $since ) );
	$unique_sessions = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT session_id) FROM {$t_visits} WHERE entry_time >= %s", $since ) );
	$avg_time       = (float) $wpdb->get_var( $wpdb->prepare( "SELECT AVG(time_spent) FROM {$t_visits} WHERE entry_time >= %s AND time_spent > 0", $since ) );
	$total_clicks   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t_events} WHERE event_type = 'click' AND created_at >= %s", $since ) );

	$top_pages = $wpdb->get_results( $wpdb->prepare(
		"SELECT page_url, page_title, COUNT(*) as visits, AVG(NULLIF(time_spent,0)) as avg_time
		 FROM {$t_visits} WHERE entry_time >= %s GROUP BY page_url ORDER BY visits DESC LIMIT 10", $since
	) );

	$top_clicks = $wpdb->get_results( $wpdb->prepare(
		"SELECT target_text, target_href, COUNT(*) as clicks
		 FROM {$t_events} WHERE event_type = 'click' AND created_at >= %s AND target_text != ''
		 GROUP BY target_text, target_href ORDER BY clicks DESC LIMIT 10", $since
	) );

	// مسیر حرکت: برای هر session، ترتیب صفحات بازدیدشده
	$paths = array();
	if ( 'paths' === $tab ) {
		$sessions = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT session_id FROM {$t_events} WHERE event_type='pageview' AND created_at >= %s ORDER BY created_at DESC LIMIT 40", $since
		) );
		foreach ( $sessions as $sid ) {
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT page_url, target_text, created_at FROM {$t_events} WHERE session_id = %s AND event_type = 'pageview' ORDER BY created_at ASC", $sid
			) );
			if ( count( $rows ) > 0 ) { $paths[ $sid ] = $rows; }
		}
	}
	?>
	<div class="wrap" dir="rtl" style="font-family: Tahoma, sans-serif; max-width:1100px;">
		<style>
			.gvva-header{display:flex;align-items:center;justify-content:space-between;background:linear-gradient(120deg,#1e3a8a,#2563eb);color:#fff;padding:22px 26px;border-radius:14px;margin:20px 0;flex-wrap:wrap;gap:12px;}
			.gvva-header h1{margin:0;font-size:20px;color:#fff;}
			.gvva-tabs{display:flex;gap:6px;margin-bottom:18px;flex-wrap:wrap;}
			.gvva-tab-btn{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:9px 18px;font-size:13px;font-weight:600;text-decoration:none;color:#1e293b;}
			.gvva-tab-btn.is-active{background:#2563eb;color:#fff;border-color:#2563eb;}
			.gvva-stat-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;}
			@media(max-width:900px){.gvva-stat-cards{grid-template-columns:repeat(2,1fr);}}
			.gvva-stat{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px 20px;}
			.gvva-stat b{display:block;font-size:24px;color:#2563eb;}
			.gvva-stat span{font-size:12.5px;color:#64748b;}
			.gvva-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:22px;margin-bottom:18px;}
			.gvva-card h2{margin-top:0;font-size:15px;}
			.gvva-table{width:100%;border-collapse:collapse;}
			.gvva-table th{background:#eff6ff;color:#1e3a8a;padding:9px 12px;text-align:right;font-size:12px;}
			.gvva-table td{padding:8px 12px;border-top:1px solid #f1f5f9;font-size:12.5px;}
			.gvva-range-form select{padding:6px 10px;border-radius:8px;border:1px solid #d1d5db;}
			.gvva-path-chain{display:flex;flex-wrap:wrap;align-items:center;gap:6px;font-size:12px;margin-bottom:14px;padding-bottom:14px;border-bottom:1px dashed #e2e8f0;}
			.gvva-path-step{background:#eff6ff;color:#1e3a8a;padding:4px 10px;border-radius:8px;}
			.gvva-path-arrow{color:#94a3b8;}
			.gvva-field label{font-weight:700;font-size:13px;display:block;margin-bottom:5px;}
			.gvva-btn{background:#111827;color:#fff !important;border:none;padding:10px 22px;border-radius:10px;font-weight:600;cursor:pointer;}
			.gvva-btn-danger{background:#b91c1c;}
		</style>

		<div class="gvva-header">
			<h1>📈 آمار بازدید و رفتار کاربران</h1>
			<form class="gvva-range-form" method="get">
				<input type="hidden" name="page" value="gv-visitor-analytics">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
				<select name="range" onchange="this.form.submit()">
					<option value="1" <?php selected( $range, 1 ); ?>>امروز</option>
					<option value="7" <?php selected( $range, 7 ); ?>>۷ روز اخیر</option>
					<option value="30" <?php selected( $range, 30 ); ?>>۳۰ روز اخیر</option>
					<option value="90" <?php selected( $range, 90 ); ?>>۹۰ روز اخیر</option>
				</select>
			</form>
		</div>

		<?php if ( isset( $_GET['updated'] ) ) : ?><div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div><?php endif; ?>
		<?php if ( isset( $_GET['cleared'] ) ) : ?><div class="notice notice-success is-dismissible"><p>داده‌های آماری پاک شد.</p></div><?php endif; ?>

		<?php if ( empty( $s['enabled'] ) ) : ?>
			<div class="notice notice-warning"><p>ردیابی آمار در حال حاضر غیرفعال است. برای شروع ثبت آمار، در تب «تنظیمات» آن را فعال کنید.</p></div>
		<?php endif; ?>

		<div class="gvva-tabs">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=gv-visitor-analytics&tab=overview&range=' . $range ) ); ?>" class="gvva-tab-btn <?php echo 'overview' === $tab ? 'is-active' : ''; ?>">📊 نمای کلی</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=gv-visitor-analytics&tab=paths&range=' . $range ) ); ?>" class="gvva-tab-btn <?php echo 'paths' === $tab ? 'is-active' : ''; ?>">🧭 مسیر حرکت کاربران</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=gv-visitor-analytics&tab=settings' ) ); ?>" class="gvva-tab-btn <?php echo 'settings' === $tab ? 'is-active' : ''; ?>">⚙️ تنظیمات</a>
		</div>

		<?php if ( 'settings' === $tab ) : ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="gv_va_save_settings">
				<?php wp_nonce_field( GV_VA_NONCE ); ?>
				<div class="gvva-card">
					<h2>روشن/خاموش</h2>
					<div class="gvva-field"><label><input type="checkbox" name="enabled" <?php checked( $s['enabled'], 1 ); ?>> فعال‌سازی کلی ردیابی آمار</label></div>
					<div class="gvva-field"><label><input type="checkbox" name="track_clicks" <?php checked( $s['track_clicks'], 1 ); ?>> ثبت کلیک‌ها</label></div>
					<div class="gvva-field"><label><input type="checkbox" name="track_time" <?php checked( $s['track_time'], 1 ); ?>> ثبت مدت‌زمان حضور در سایت</label></div>
					<div class="gvva-field"><label><input type="checkbox" name="track_path" <?php checked( $s['track_path'], 1 ); ?>> ثبت مسیر حرکت بین صفحات</label></div>
					<div class="gvva-field"><label><input type="checkbox" name="ignore_admins" <?php checked( $s['ignore_admins'], 1 ); ?>> بازدید مدیران سایت ثبت نشود</label></div>
				</div>
				<div class="gvva-card">
					<h2>نگه‌داری داده‌ها</h2>
					<div class="gvva-field">
						<label>حذف خودکار داده‌های قدیمی‌تر از (روز) — عدد ۰ یعنی هرگز پاک نشود</label>
						<input type="number" name="retention_days" min="0" max="730" value="<?php echo esc_attr( $s['retention_days'] ); ?>" style="width:120px;padding:6px 10px;border-radius:8px;border:1px solid #d1d5db;">
					</div>
				</div>
				<button type="submit" class="gvva-btn">💾 ذخیره تنظیمات</button>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('تمام داده‌های آماری ثبت‌شده برای همیشه پاک شوند؟');" style="margin-top:14px;">
				<input type="hidden" name="action" value="gv_va_clear_data">
				<?php wp_nonce_field( GV_VA_NONCE ); ?>
				<button type="submit" class="gvva-btn gvva-btn-danger">🗑️ پاک‌سازی کامل داده‌های آماری</button>
			</form>

		<?php elseif ( 'paths' === $tab ) : ?>

			<div class="gvva-card">
				<h2>مسیر حرکت آخرین بازدیدکنندگان (هر ردیف = یک نفر)</h2>
				<?php if ( empty( $paths ) ) : ?>
					<p style="color:#94a3b8;">داده‌ای برای این بازه یافت نشد.</p>
				<?php else : ?>
					<?php foreach ( $paths as $sid => $rows ) : ?>
						<div class="gvva-path-chain">
							<?php foreach ( $rows as $i => $row ) : ?>
								<?php if ( $i > 0 ) : ?><span class="gvva-path-arrow">←</span><?php endif; ?>
								<span class="gvva-path-step" title="<?php echo esc_attr( $row->page_url ); ?>"><?php echo esc_html( $row->target_text ?: parse_url( $row->page_url, PHP_URL_PATH ) ); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

		<?php else : ?>

			<div class="gvva-stat-cards">
				<div class="gvva-stat"><b><?php echo esc_html( number_format_i18n( $total_visits ) ); ?></b><span>بازدید صفحه</span></div>
				<div class="gvva-stat"><b><?php echo esc_html( number_format_i18n( $unique_sessions ) ); ?></b><span>بازدیدکننده یکتا</span></div>
				<div class="gvva-stat"><b><?php echo esc_html( $avg_time ? gmdate( 'i:s', (int) $avg_time ) : '۰۰:۰۰' ); ?></b><span>میانگین مدت حضور</span></div>
				<div class="gvva-stat"><b><?php echo esc_html( number_format_i18n( $total_clicks ) ); ?></b><span>تعداد کلیک ثبت‌شده</span></div>
			</div>

			<div class="gvva-card">
				<h2>پربازدیدترین صفحات</h2>
				<?php if ( empty( $top_pages ) ) : ?>
					<p style="color:#94a3b8;">داده‌ای یافت نشد.</p>
				<?php else : ?>
					<table class="gvva-table">
						<thead><tr><th>صفحه</th><th>بازدید</th><th>میانگین زمان</th></tr></thead>
						<tbody>
						<?php foreach ( $top_pages as $row ) : ?>
							<tr>
								<td><a href="<?php echo esc_url( $row->page_url ); ?>" target="_blank"><?php echo esc_html( $row->page_title ?: $row->page_url ); ?></a></td>
								<td><?php echo esc_html( number_format_i18n( $row->visits ) ); ?></td>
								<td><?php echo esc_html( $row->avg_time ? gmdate( 'i:s', (int) $row->avg_time ) : '—' ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<div class="gvva-card">
				<h2>پرکلیک‌ترین لینک‌ها و دکمه‌ها</h2>
				<?php if ( empty( $top_clicks ) ) : ?>
					<p style="color:#94a3b8;">داده‌ای یافت نشد.</p>
				<?php else : ?>
					<table class="gvva-table">
						<thead><tr><th>متن المان</th><th>لینک</th><th>تعداد کلیک</th></tr></thead>
						<tbody>
						<?php foreach ( $top_clicks as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->target_text ); ?></td>
								<td><?php echo $row->target_href ? '<a href="' . esc_url( $row->target_href ) . '" target="_blank">' . esc_html( $row->target_href ) . '</a>' : '—'; ?></td>
								<td><?php echo esc_html( number_format_i18n( $row->clicks ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

		<?php endif; ?>

		<p style="font-size:11.5px;color:#888;text-align:center;margin-top:24px;">ساخته و توسعه‌یافته توسط <strong>Groot Vision</strong></p>
	</div>
	<?php
}
