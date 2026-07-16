<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'CLX_PB_OPT', 'clx_progress_settings' );
define( 'CLX_PB_NONCE', 'clx_pb_nonce_action' );

/* ==========================================================================
   ۱) مقادیر پیش‌فرض
   ========================================================================== */

function clx_pb_default_settings() {
	return array(
		'enabled'         => 1,
		'height'          => 5,
		'gradient_color_1'=> '#7c3aed',
		'gradient_color_2'=> '#06b6d4',
		'gradient_angle'  => 90,
		'track_color'     => 'rgba(0,0,0,.08)',
		'rounded'         => 1,
		'glow'            => 1,
		'show_bubble'     => 1,
		'mobile_visible'  => 1,
	);
}

function clx_pb_get_settings() {
	$saved = get_option( CLX_PB_OPT, array() );
	return wp_parse_args( $saved, clx_pb_default_settings() );
}

/* ==========================================================================
   ۲) منوی مدیریت در پنل وردپرس
   ========================================================================== */

add_action( 'admin_menu', 'clx_pb_admin_menu' );
function clx_pb_admin_menu() {
	add_submenu_page(
		'groot-vision-hub',
		'پروگرس بار اسکرول',
		'📊 پروگرس بار',
		'manage_options',
		'clx-progress-bar',
		'clx_pb_render_admin_page'
	);
}

/* ---- پردازش فرم ---- */

add_action( 'admin_post_clx_pb_save_settings', 'clx_pb_save_settings' );
function clx_pb_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
	check_admin_referer( CLX_PB_NONCE );

	$settings = array(
		'enabled'          => isset( $_POST['enabled'] ) ? 1 : 0,
		'height'           => max( 2, min( 14, intval( $_POST['height'] ?? 5 ) ) ),
		'gradient_color_1' => sanitize_hex_color( $_POST['gradient_color_1'] ?? '' ) ?: '#7c3aed',
		'gradient_color_2' => sanitize_hex_color( $_POST['gradient_color_2'] ?? '' ) ?: '#06b6d4',
		'gradient_angle'   => max( 0, min( 360, intval( $_POST['gradient_angle'] ?? 90 ) ) ),
		'track_color'      => sanitize_text_field( $_POST['track_color'] ?? 'rgba(0,0,0,.08)' ),
		'rounded'          => isset( $_POST['rounded'] ) ? 1 : 0,
		'glow'             => isset( $_POST['glow'] ) ? 1 : 0,
		'show_bubble'      => isset( $_POST['show_bubble'] ) ? 1 : 0,
		'mobile_visible'   => isset( $_POST['mobile_visible'] ) ? 1 : 0,
	);

	update_option( CLX_PB_OPT, $settings );
	wp_safe_redirect( admin_url( 'admin.php?page=clx-progress-bar&updated=1' ) );
	exit;
}

/* ---- صفحه مدیریت (طراحی مدرن، هم‌راستا با سایر افزونه‌های گروت ویژن) ---- */

function clx_pb_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$s = clx_pb_get_settings();
	?>
	<style>
		.clx-pb-wrap { max-width: 900px; margin-top: 20px; direction: rtl; font-family: inherit; }
		.clx-pb-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; flex-wrap:wrap; gap:10px; }
		.clx-pb-header h1 { font-size:22px; margin:0; display:flex; align-items:center; gap:8px; }
		.clx-pb-badge { background:linear-gradient(135deg,#7c3aed,#06b6d4); color:#fff; padding:5px 14px; border-radius:20px; font-size:12px; font-weight:600; letter-spacing:.3px; }
		.clx-pb-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:26px; box-shadow:0 1px 2px rgba(0,0,0,.03), 0 8px 24px -12px rgba(0,0,0,.08); margin-bottom:20px; }
		.clx-pb-card h2 { margin-top:0; font-size:16px; }
		.clx-pb-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
		@media (max-width:700px){ .clx-pb-grid{ grid-template-columns:1fr; } }
		.clx-pb-field label { display:block; font-weight:600; margin-bottom:6px; font-size:13px; color:#334155; }
		.clx-pb-field input[type=text], .clx-pb-field input[type=number] { width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box; }
		.clx-pb-field input[type=color] { width:60px; height:38px; border-radius:8px; border:1px solid #d1d5db; padding:2px; }
		.clx-pb-field small.clx-pb-hint { display:block; color:#94a3b8; font-size:11.5px; margin-top:4px; }
		.clx-pb-switch { display:flex; align-items:center; gap:8px; font-weight:600; font-size:13px; cursor:pointer; }
		.clx-pb-btn { background:#111827; color:#fff !important; border:none; padding:10px 22px; border-radius:10px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block; }
		.clx-pb-btn:hover { background:#1f2937; color:#fff; }
		.clx-pb-preview-track { position:relative; width:100%; overflow:visible; }
		.clx-pb-preview-fill { height:100%; width:62%; position:relative; }
		.clx-pb-preview-bubble { position:absolute; top:calc(100% + 8px); right:0; transform:translateX(50%); color:#fff; font-size:11px; font-weight:700; padding:3px 8px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,.25); white-space:nowrap; direction:ltr; }
		.clx-pb-footer { text-align:center; color:#94a3b8; font-size:12px; margin-top:24px; }
	</style>

	<div class="wrap clx-pb-wrap">
		<div class="clx-pb-header">
			<h1>📊 پروگرس بار اسکرول — تنظیمات</h1>
			<span class="clx-pb-badge">Powered by Groot Vision</span>
		</div>

		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت ذخیره شد.</p></div>
		<?php endif; ?>

		<div class="clx-pb-card">
			<h2>پیش‌نمایش زنده</h2>
			<div class="clx-pb-preview-track" style="height:<?php echo (int) $s['height']; ?>px; background:<?php echo esc_attr( $s['track_color'] ); ?>; <?php echo $s['rounded'] ? 'border-radius:999px;' : 'border-radius:0;'; ?>">
				<div class="clx-pb-preview-fill" style="background:linear-gradient(<?php echo (int) $s['gradient_angle']; ?>deg, <?php echo esc_attr( $s['gradient_color_1'] ); ?>, <?php echo esc_attr( $s['gradient_color_2'] ); ?>); <?php echo $s['rounded'] ? 'border-radius:999px;' : ''; ?> <?php echo $s['glow'] ? 'box-shadow:0 2px 14px 0 ' . esc_attr( $s['gradient_color_2'] ) . ';' : ''; ?>">
					<?php if ( ! empty( $s['show_bubble'] ) ) : ?>
						<span class="clx-pb-preview-bubble" style="background:linear-gradient(<?php echo (int) $s['gradient_angle']; ?>deg, <?php echo esc_attr( $s['gradient_color_1'] ); ?>, <?php echo esc_attr( $s['gradient_color_2'] ); ?>);">62%</span>
					<?php endif; ?>
				</div>
			</div>
			<small class="clx-pb-hint" style="display:block;margin-top:26px;color:#94a3b8;">این فقط یک پیش‌نمایش نمونه است؛ نوار واقعی روی سایت با اسکرول کاربر حرکت می‌کند.</small>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="clx_pb_save_settings">
			<?php wp_nonce_field( CLX_PB_NONCE ); ?>

			<div class="clx-pb-card">
				<h2>عمومی</h2>
				<div class="clx-pb-grid">
					<div class="clx-pb-field">
						<label class="clx-pb-switch"><input type="checkbox" name="enabled" <?php checked( $s['enabled'], 1 ); ?>> فعال‌سازی پروگرس بار در کل سایت</label>
					</div>
					<div class="clx-pb-field">
						<label class="clx-pb-switch"><input type="checkbox" name="mobile_visible" <?php checked( $s['mobile_visible'], 1 ); ?>> نمایش در موبایل هم فعال باشد</label>
					</div>
					<div class="clx-pb-field">
						<label>ارتفاع نوار (پیکسل)</label>
						<input type="number" min="2" max="14" name="height" value="<?php echo esc_attr( $s['height'] ); ?>">
					</div>
				</div>
			</div>

			<div class="clx-pb-card">
				<h2>رنگ و گرادیان</h2>
				<div class="clx-pb-grid">
					<div class="clx-pb-field">
						<label>رنگ شروع گرادیان</label>
						<input type="color" name="gradient_color_1" value="<?php echo esc_attr( $s['gradient_color_1'] ); ?>">
					</div>
					<div class="clx-pb-field">
						<label>رنگ پایان گرادیان</label>
						<input type="color" name="gradient_color_2" value="<?php echo esc_attr( $s['gradient_color_2'] ); ?>">
					</div>
					<div class="clx-pb-field">
						<label>زاویه گرادیان (درجه)</label>
						<input type="number" min="0" max="360" name="gradient_angle" value="<?php echo esc_attr( $s['gradient_angle'] ); ?>">
						<small class="clx-pb-hint">پیش‌فرض ۹۰ (افقی چپ به راست)</small>
					</div>
					<div class="clx-pb-field">
						<label>رنگ ریل پس‌زمینه (بخش پرنشده)</label>
						<input type="text" name="track_color" value="<?php echo esc_attr( $s['track_color'] ); ?>" placeholder="rgba(0,0,0,.08)">
						<small class="clx-pb-hint">می‌تواند hex، rgba یا هر مقدار معتبر CSS باشد.</small>
					</div>
				</div>
			</div>

			<div class="clx-pb-card">
				<h2>افکت‌ها</h2>
				<div class="clx-pb-grid">
					<div class="clx-pb-field">
						<label class="clx-pb-switch"><input type="checkbox" name="rounded" <?php checked( $s['rounded'], 1 ); ?>> گوشه‌های گرد</label>
					</div>
					<div class="clx-pb-field">
						<label class="clx-pb-switch"><input type="checkbox" name="glow" <?php checked( $s['glow'], 1 ); ?>> افکت درخشش (Glow) متناسب با گرادیان</label>
					</div>
					<div class="clx-pb-field">
						<label class="clx-pb-switch"><input type="checkbox" name="show_bubble" <?php checked( $s['show_bubble'], 1 ); ?>> نمایش حباب درصد پیشرفت</label>
					</div>
				</div>
			</div>

			<button type="submit" class="clx-pb-btn">ذخیره تنظیمات</button>
		</form>

		<p class="clx-pb-footer">طراحی و توسعه توسط <strong>Groot Vision</strong> | وبسایت: grootvision.com</p>
	</div>
	<?php
}

/* ==========================================================================
   ۳) نمایش نوار در سمت کاربر (فرانت‌اند)
   ========================================================================== */

add_action( 'wp_footer', 'clx_pb_render_frontend' );
function clx_pb_render_frontend() {
	if ( is_admin() ) { return; }

	$s = clx_pb_get_settings();
	if ( empty( $s['enabled'] ) ) { return; }
	?>
	<style>
	:root{
		--clx-pb-h:<?php echo (int) $s['height']; ?>px;
		--clx-pb-c1:<?php echo esc_html( $s['gradient_color_1'] ); ?>;
		--clx-pb-c2:<?php echo esc_html( $s['gradient_color_2'] ); ?>;
		--clx-pb-track:<?php echo esc_html( $s['track_color'] ); ?>;
	}
	#clx-pb-track{
		position:fixed;top:0;left:0;right:0;z-index:999999;
		height:var(--clx-pb-h);
		background:var(--clx-pb-track);
		<?php echo $s['rounded'] ? 'border-radius:0 0 999px 999px;' : ''; ?>
		overflow:visible;
		font-family:inherit;
		direction:ltr;
	}
	#clx-pb-fill{
		position:absolute;
		top:0;left:0;height:100%;width:0%;
		background:linear-gradient(<?php echo (int) $s['gradient_angle']; ?>deg, var(--clx-pb-c1), var(--clx-pb-c2));
		<?php echo $s['rounded'] ? 'border-radius:0 0 999px 999px;' : ''; ?>
		<?php echo $s['glow'] ? 'box-shadow:0 2px 14px 0 var(--clx-pb-c2);' : ''; ?>
		transition:width .08s linear;
	}
	<?php if ( $s['glow'] ) : ?>
	@supports (box-shadow: 0 0 0 color-mix(in srgb, red 50%, transparent)) {
		#clx-pb-fill{ box-shadow:0 2px 14px 0 color-mix(in srgb, var(--clx-pb-c2) 65%, transparent); }
	}
	<?php endif; ?>
	#clx-pb-bubble{
		position:absolute;top:calc(var(--clx-pb-h) + 6px);
		right:0;
		transform:translateX(50%);
		background:linear-gradient(<?php echo (int) $s['gradient_angle']; ?>deg, var(--clx-pb-c1), var(--clx-pb-c2));
		color:#fff;
		font-size:11px;font-weight:700;
		padding:3px 8px;border-radius:8px;
		box-shadow:0 4px 12px rgba(0,0,0,.25);
		opacity:0;transition:opacity .25s ease;
		white-space:nowrap;
		font-variant-numeric:tabular-nums;
		direction:ltr;
	}
	#clx-pb-bubble.clx-pb-visible{opacity:1;}
	#clx-pb-bubble::before{
		content:"";position:absolute;top:-5px;right:10px;
		border-left:5px solid transparent;border-right:5px solid transparent;
		border-bottom:5px solid var(--clx-pb-c1);
	}
	<?php if ( empty( $s['mobile_visible'] ) ) : ?>
	@media (max-width:640px){
		#clx-pb-track{display:none;}
	}
	<?php endif; ?>
	</style>

	<div id="clx-pb-track">
		<div id="clx-pb-fill">
			<?php if ( ! empty( $s['show_bubble'] ) ) : ?>
				<span id="clx-pb-bubble">0%</span>
			<?php endif; ?>
		</div>
	</div>

	<script>
	(function(){
		var fill = document.getElementById('clx-pb-fill');
		var bubble = document.getElementById('clx-pb-bubble');
		var ticking = false;

		function usePersianDigits(str){
			var fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
			return String(str).replace(/[0-9]/g, function(d){ return fa[d]; });
		}

		function update(){
			var scrollTop = window.scrollY || document.documentElement.scrollTop;
			var docHeight = (document.documentElement.scrollHeight || document.body.scrollHeight) - window.innerHeight;
			var pct = docHeight > 0 ? Math.min(100, Math.max(0, (scrollTop / docHeight) * 100)) : 0;

			fill.style.width = pct + '%';

			if (bubble) {
				if (pct > 1) {
					bubble.classList.add('clx-pb-visible');
				} else {
					bubble.classList.remove('clx-pb-visible');
				}
				bubble.textContent = usePersianDigits(Math.round(pct)) + '٪';
			}
			ticking = false;
		}

		function onScroll(){
			if (!ticking) {
				requestAnimationFrame(update);
				ticking = true;
			}
		}

		window.addEventListener('scroll', onScroll, { passive: true });
		window.addEventListener('resize', onScroll);
		update();
	})();
	</script>
	<?php
}