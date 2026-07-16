<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * ==========================================================
 *  Groot Vision — Hub / Dashboard (نسخه بهینه‌شده)
 *  این فایل باید همیشه با اولویت زودتر از سایر اسنیپت‌ها
 *  (نوار اعلان / کادر تبلیغاتی / پروگرس بار / اعلان خرید / امنیت و سرعت /
 *   استایل جدول‌ها / کلمات کلیدی سئو)
 *  لود شود تا منوی والد قبل از ثبت زیرمنوها ساخته شده باشد.
 * ==========================================================
 */

define( 'GV_HUB_SLUG', 'groot-vision-hub' );

/* ==========================================================
   ۱) ساخت منوی والد + آیتم داشبورد
   ========================================================== */
add_action( 'admin_menu', 'gv_hub_register_menu', 5 ); // اولویت ۵ = زودتر از بقیه (پیش‌فرض ۱۰)
function gv_hub_register_menu() {

	add_menu_page(
		'افزونه‌های گروت ویژن',
		'افزونه‌های گروت',
		'manage_options',
		GV_HUB_SLUG,
		'gv_hub_render_page',
		'dashicons-star-filled',
		58
	);

	// آیتم اول زیرمنو را خودمان به دلخواه تغییر نام می‌دهیم
	add_submenu_page(
		GV_HUB_SLUG,
		'داشبورد افزونه‌های گروت ویژن',
		'🏠 داشبورد',
		'manage_options',
		GV_HUB_SLUG,
		'gv_hub_render_page'
	);
}

/* ==========================================================
   ۲) اطلاعات کارت هر افزونه
   ------------------------------------------------------------
   'status_option' : نام آپشنی که وضعیت فعال/غیرفعال بودن
                     افزونه را نگه می‌دارد (برای نمایش روی کارت)
   'status_key'    : اگر تنظیمات داخل یک آرایه ذخیره شده،
                     کلیدی که فیلد enabled را نشان می‌دهد
   'page'          : اسلاگ صفحه‌ی زیرمنو (همیشه با admin.php?page=... باز می‌شود)
   برای افزودن افزونه جدید، فقط یک آیتم دیگر به این آرایه اضافه کنید.
   ========================================================== */
function gv_hub_get_items() {
	return array(
		array(
			'title'         => 'نوار اعلان بالای صفحه',
			'desc'          => 'نوار متحرک با افکت تایپ‌اسکرول برای نمایش پیام‌های تبلیغاتی و لینک‌دار در بالای سایت.',
			'icon'          => '📢',
			'page'          => 'gv-topbar-settings',
			'color'         => '#0e4037',
			'status_option' => 'gv_topbar_settings',
			'status_key'    => 'enabled',
		),
		array(
			'title'         => 'کادر تبلیغاتی گوشه صفحه',
			'desc'          => 'کادر شناور با شمارش معکوس، دکمه تماس فوری و دیتای سئوی Schema.org برای پیشنهاد ویژه.',
			'icon'          => '📣',
			'page'          => 'clx-discount-bar',
			'color'         => '#0EA5A4',
			'status_option' => 'clx_bar_settings',
			'status_key'    => 'enabled',
		),
		array(
			'title'         => 'پروگرس بار اسکرول',
			'desc'          => 'نوار پیشرفت گرادیانی در بالای صفحه که میزان اسکرول کاربر را به‌صورت زنده نمایش می‌دهد.',
			'icon'          => '📊',
			'page'          => 'clx-progress-bar',
			'color'         => '#7c3aed',
			'status_option' => 'clx_progress_settings',
			'status_key'    => 'enabled',
		),
		array(
			// ⚠️ اصلاح شد: قبلاً اسمش «همه اعلان‌ها» بود و اشتباهاً به صفحه‌ی
			// نوار اعلان (gv-topbar-settings) لینک می‌شد. الان درست به صفحه‌ی
			// تنظیمات خودِ اعلان خرید (notification.php) وصل است.
			'title'         => 'اعلان خرید',
			'desc'          => 'نمایش اعلان خریدهای اخیر روی سایت، افزودن خرید جدید، ایمپورت/اکسپورت CSV و مشاهده آمار کلیک‌ها.',
			'icon'          => '🛒',
			'page'          => 'vitrin_purchase_settings',
			'color'         => '#9f1239',
			'status_option' => 'vp_enabled',
			'status_key'    => null,
		),
		array(
			'title'         => 'امنیت و سرعت',
			'desc'          => 'کپچای ریاضی، بلاک درخواست‌های سرور-به-سرور غیرضروری، لیست سفید دائمی درگاه پرداخت و تشخیص خودکار دامنه‌های کند.',
			'icon'          => '🛡️',
			'page'          => 'gv-security-speed',
			'color'         => '#2563eb',
			'status_option' => 'gv_security_settings',
			'status_key'    => 'active',
		),
		array(
			'title'         => 'استایل جدول‌ها و تسویه‌حساب',
			'desc'          => '۳ تم آماده + رنگ‌بندی سفارشی برای همه جدول‌های سایت، به‌همراه ساده‌سازی فرم تسویه‌حساب ووکامرس.',
			'icon'          => '🎨',
			'page'          => 'gv-table-style',
			'color'         => '#7c3aed',
			'status_option' => 'gv_table_style_settings',
			'status_key'    => 'enabled',
		),
		array(
			'title'         => 'کلمات کلیدی سئو',
			'desc'          => 'جمع‌آوری تمام کلمات کلیدی فوکوس سایت از Yoast / RankMath / AIOSEO / SEOPress در یک‌جا، به‌همراه خروجی CSV.',
			'icon'          => '🔑',
			'page'          => 'gv-seo-keywords',
			'color'         => '#b45309',
			'status_option' => null,
			'status_key'    => null,
		),
		array(
			'title'         => 'به‌روزرسانی خودکار گیت‌هاب',
			'desc'          => 'اتصال افزونه به مخزن گیت‌هاب پروژه؛ با هر Release جدید، دکمه به‌روزرسانی و خلاصه تغییرات به‌صورت خودکار در پیشخوان نمایش داده می‌شود.',
			'icon'          => '🔄',
			'page'          => 'gv-github-updater',
			'color'         => '#0e4037',
			'status_option' => 'gv_github_updater_settings',
			'status_key'    => 'enabled',
		),
		array(
			'title'         => 'طراحی صفحه ورود وردپرس',
			'desc'          => 'چند تم آماده و زیبا برای صفحه لاگین وردپرس، همراه با امکان انتخاب لوگو و متن دلخواه.',
			'icon'          => '🔐',
			'page'          => 'gv-login-style',
			'color'         => '#6d28d9',
			'status_option' => 'gv_login_style_settings',
			'status_key'    => 'enabled',
		),
		array(
			'title'         => 'مدیریت فونت سایت',
			'desc'          => 'آپلود فونت دلخواه، پیش‌نمایش و اعمال خودکار روی کل سایت با مقیاس تایپوگرافی هوشمند برای سربرگ‌ها.',
			'icon'          => '🔤',
			'page'          => 'gv-font-manager',
			'color'         => '#db2777',
			'status_option' => 'gv_font_manager_settings',
			'status_key'    => 'enabled',
		),
		array(
			'title'         => 'تاریخ خودکار شمسی',
			'desc'          => 'افزودن خودکار تاریخ امروز (شمسی) به انتهای عنوان نوشته‌ها و برگه‌ها، هر روز به‌صورت زنده به‌روز می‌شود.',
			'icon'          => '📅',
			'page'          => 'gv-jalali-date',
			'color'         => '#0891b2',
			'status_option' => 'gv_jalali_date_settings',
			'status_key'    => 'enabled',
		),
		array(
			'title'         => 'آمار بازدید و رفتار کاربران',
			'desc'          => 'ثبت بازدید هر صفحه، مدت‌زمان حضور، مسیر حرکت بین صفحات و کلیک‌های کاربران به‌همراه داشبورد آماری.',
			'icon'          => '📈',
			'page'          => 'gv-visitor-analytics',
			'color'         => '#2563eb',
			'status_option' => 'gv_visitor_analytics_settings',
			'status_key'    => 'enabled',
		),
array(
			'title'         => 'حالت تعمیر',
			'desc'          => 'بخش حالت تعمیر برای بروزرسانی وبسایت، هنگام آپدیت های ضروری و احتمال مشکل ساز شدن بازدید مشتری.',
			'icon'          => '📈',
			'page'          => 'wpmc-maintenance',
			'color'         => '#2563eb',
			'status_option' => 'manage_options',
			'status_key'    => 'enabled',
		),

	);
}

/**
 * وضعیت فعال/غیرفعال بودن هر افزونه را برمی‌گرداند.
 */
function gv_hub_get_item_status( $item ) {
	if ( empty( $item['status_option'] ) ) {
		return null; // این افزونه وضعیت روشن/خاموش ندارد (مثل کلمات کلیدی سئو که همیشه در دسترس است)
	}

	$raw = get_option( $item['status_option'], null );

	if ( null === $raw ) {
		return null; // یعنی هنوز تنظیمات ذخیره نشده (افزونه تازه نصب شده)
	}

	if ( null === $item['status_key'] ) {
		return (bool) $raw;
	}

	if ( is_array( $raw ) && isset( $raw[ $item['status_key'] ] ) ) {
		return (bool) $raw[ $item['status_key'] ];
	}

	return null;
}

/* ==========================================================
   ۳) رندر صفحه داشبورد
   ========================================================== */
function gv_hub_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$items = gv_hub_get_items();

	$active_count = 0;
	foreach ( $items as $item ) {
		if ( true === gv_hub_get_item_status( $item ) ) { $active_count++; }
	}
	?>
	<div class="wrap gv-hub-wrap" dir="rtl">

		<div class="gv-hub-header">
			<div class="gv-hub-logo">
				<span class="dashicons dashicons-star-filled"></span>
				<div>
					<h1>افزونه‌های گروت ویژن</h1>
					<p>مدیریت یکپارچه‌ی تمام ابزارهای نصب‌شده روی سایت شما</p>
				</div>
			</div>
			<div class="gv-hub-header-stats">
				<span class="gv-hub-badge gv-hub-badge-total"><?php echo count( $items ); ?> افزونه نصب‌شده</span>
				<span class="gv-hub-badge gv-hub-badge-active"><?php echo esc_html( $active_count ); ?> فعال</span>
			</div>
		</div>

		<div class="gv-hub-grid">
			<?php foreach ( $items as $item ) :
				// همه‌ی صفحات، زیرمنوی «groot-vision-hub» هستند و از admin.php?page=... باز می‌شوند.
				$url    = admin_url( 'admin.php?page=' . $item['page'] );
				$status = gv_hub_get_item_status( $item );
				?>
				<a href="<?php echo esc_url( $url ); ?>" class="gv-hub-card" style="--gv-card-color: <?php echo esc_attr( $item['color'] ); ?>;">
					<div class="gv-hub-card-top">
						<div class="gv-hub-card-icon"><?php echo esc_html( $item['icon'] ); ?></div>
						<?php if ( true === $status ) : ?>
							<span class="gv-hub-status gv-hub-status-on"><i></i> فعال</span>
						<?php elseif ( false === $status ) : ?>
							<span class="gv-hub-status gv-hub-status-off"><i></i> غیرفعال</span>
						<?php elseif ( null !== $item['status_option'] ) : ?>
							<span class="gv-hub-status gv-hub-status-new"><i></i> تنظیم‌نشده</span>
						<?php endif; ?>
					</div>
					<h2><?php echo esc_html( $item['title'] ); ?></h2>
					<p><?php echo esc_html( $item['desc'] ); ?></p>
					<span class="gv-hub-card-btn">ورود به تنظیمات ←</span>
				</a>
			<?php endforeach; ?>
		</div>

		<!-- ============ بخش تبلیغاتی توسعه‌دهنده ============ -->
		<div class="gv-hub-promo">
			<div class="gv-hub-promo-glow"></div>
			<div class="gv-hub-promo-inner">
				<div class="gv-hub-promo-text">
					<span class="gv-hub-promo-badge">✦ توسعه‌یافته توسط تیم Groot Vision</span>
					<h2>نیاز به افزونه اختصاصی، سایت جدید یا سفارشی‌سازی دارید؟</h2>
					<p>
						تیم <strong>گروت ویژن</strong> طراح و توسعه‌دهنده‌ی همین افزونه‌هاست.
						اگر به یک افزونه اختصاصی، طراحی سایت حرفه‌ای، یا رفع اشکال و شخصی‌سازی
						افزونه‌های فعلی نیاز دارید، همین حالا با ما در ارتباط باشید.
					</p>
					<div class="gv-hub-promo-contacts">
						<a href="tel:+989130617187" class="gv-hub-promo-contact">
							<span class="gv-hub-promo-contact-icon">📞</span>
							<span>
								<b>تماس مستقیم</b>
								<bdi>0913 061 7187</bdi>
							</span>
						</a>
						<a href="https://www.grootvision.com" target="_blank" rel="noopener" class="gv-hub-promo-contact">
							<span class="gv-hub-promo-contact-icon">🌐</span>
							<span>
								<b>وب‌سایت</b>
								<bdi>grootvision.com</bdi>
							</span>
						</a>
						<a href="https://instagram.com/grootvision" target="_blank" rel="noopener" class="gv-hub-promo-contact">
							<span class="gv-hub-promo-contact-icon">📸</span>
							<span>
								<b>اینستاگرام</b>
								<bdi>grootvision</bdi>
							</span>
						</a>
					</div>
				</div>
				<a href="tel:+989130617187" class="gv-hub-promo-cta">
					☎ سفارش کار و مشاوره رایگان
				</a>
			</div>
		</div>

		<p class="gv-hub-footer">توسعه‌یافته توسط <strong>Groot Vision</strong> — تمامی حقوق محفوظ است.</p>
	</div>

	<style>
		.gv-hub-wrap{max-width:1100px;margin-top:20px;font-family:'Vazirmatn',Tahoma,sans-serif;}

		/* ---------- هدر ---------- */
		.gv-hub-header{display:flex;align-items:center;justify-content:space-between;background:linear-gradient(120deg,#0e4037,#145c4d);color:#fff;padding:26px 30px;border-radius:16px;margin-bottom:28px;box-shadow:0 10px 30px rgba(14,64,55,.3);flex-wrap:wrap;gap:14px;}
		.gv-hub-logo{display:flex;align-items:center;gap:16px;}
		.gv-hub-logo .dashicons{font-size:38px;width:38px;height:38px;color:#facc15;}
		.gv-hub-logo h1{margin:0;font-size:23px;color:#fff;}
		.gv-hub-logo p{margin:4px 0 0;font-size:13px;color:#cbd5e1;}
		.gv-hub-header-stats{display:flex;gap:10px;flex-wrap:wrap;}
		.gv-hub-badge{padding:7px 16px;border-radius:20px;font-size:12.5px;font-weight:600;white-space:nowrap;}
		.gv-hub-badge-total{background:rgba(250,204,21,.15);border:1px solid #facc15;color:#facc15;}
		.gv-hub-badge-active{background:rgba(74,222,128,.15);border:1px solid #4ade80;color:#4ade80;}

		/* ---------- گرید کارت‌ها ---------- */
		.gv-hub-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:30px;}
		@media(max-width:1200px){.gv-hub-grid{grid-template-columns:repeat(2,1fr);}}
		@media(max-width:640px){.gv-hub-grid{grid-template-columns:1fr;}}

		.gv-hub-card{display:block;background:#fff;border:1px solid #e2e8f0;border-top:4px solid var(--gv-card-color);border-radius:16px;padding:22px 20px;text-decoration:none;color:inherit;box-shadow:0 2px 10px rgba(0,0,0,.04);transition:transform .18s ease, box-shadow .18s ease;position:relative;}
		.gv-hub-card:hover{transform:translateY(-4px);box-shadow:0 14px 30px rgba(0,0,0,.1);color:inherit;}
		.gv-hub-card-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px;}
		.gv-hub-card-icon{font-size:28px;}
		.gv-hub-card h2{font-size:15.5px;margin:0 0 8px;color:#0f172a;}
		.gv-hub-card p{font-size:12.5px;color:#64748b;line-height:1.85;margin:0 0 16px;min-height:58px;}
		.gv-hub-card-btn{display:inline-block;font-size:12.5px;font-weight:700;color:var(--gv-card-color);}

		.gv-hub-status{display:inline-flex;align-items:center;gap:5px;font-size:10.5px;font-weight:700;padding:3px 9px;border-radius:20px;}
		.gv-hub-status i{width:6px;height:6px;border-radius:50%;display:inline-block;}
		.gv-hub-status-on{background:rgba(74,222,128,.15);color:#15803d;}
		.gv-hub-status-on i{background:#22c55e;}
		.gv-hub-status-off{background:rgba(148,163,184,.18);color:#64748b;}
		.gv-hub-status-off i{background:#94a3b8;}
		.gv-hub-status-new{background:rgba(250,204,21,.15);color:#a16207;}
		.gv-hub-status-new i{background:#facc15;}

		/* ---------- بخش تبلیغاتی ---------- */
		.gv-hub-promo{position:relative;overflow:hidden;border-radius:20px;background:linear-gradient(135deg,#0b1f26,#0e4037 60%,#145c4d);color:#fff;padding:2px;margin-bottom:26px;box-shadow:0 16px 40px rgba(14,64,55,.35);}
		.gv-hub-promo-glow{position:absolute;inset:-40%;background:radial-gradient(circle at 20% 20%, rgba(74,222,128,.25), transparent 55%), radial-gradient(circle at 85% 80%, rgba(250,204,21,.18), transparent 50%);pointer-events:none;}
		.gv-hub-promo-inner{position:relative;display:flex;align-items:center;justify-content:space-between;gap:28px;padding:32px 34px;flex-wrap:wrap;}
		.gv-hub-promo-text{flex:1;min-width:280px;}
		.gv-hub-promo-badge{display:inline-block;background:rgba(74,222,128,.15);border:1px solid #4ade80;color:#4ade80;font-size:11.5px;font-weight:700;padding:5px 14px;border-radius:20px;margin-bottom:14px;}
		.gv-hub-promo-text h2{margin:0 0 10px;font-size:19px;line-height:1.6;color:#fff;}
		.gv-hub-promo-text p{margin:0 0 20px;font-size:13.5px;line-height:1.95;color:#cbd5e1;max-width:560px;}
		.gv-hub-promo-contacts{display:flex;gap:12px;flex-wrap:wrap;}
		.gv-hub-promo-contact{display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);border-radius:12px;padding:9px 14px;text-decoration:none;color:#fff;transition:background .18s ease, transform .18s ease;}
		.gv-hub-promo-contact:hover{background:rgba(255,255,255,.14);transform:translateY(-2px);color:#fff;}
		.gv-hub-promo-contact-icon{font-size:18px;}
		.gv-hub-promo-contact b{display:block;font-size:11px;color:#94ded1;font-weight:700;}
		.gv-hub-promo-contact bdi{display:block;font-size:12.5px;font-weight:600;direction:ltr;text-align:right;}
		.gv-hub-promo-cta{flex-shrink:0;background:linear-gradient(120deg,#4ade80,#22c55e);color:#052e18 !important;font-weight:800;font-size:14.5px;padding:15px 26px;border-radius:14px;text-decoration:none;box-shadow:0 10px 24px rgba(34,197,94,.35);white-space:nowrap;transition:transform .18s ease, filter .18s ease;}
		.gv-hub-promo-cta:hover{transform:translateY(-3px);filter:brightness(1.05);color:#052e18 !important;}

		@media(max-width:700px){
			.gv-hub-promo-inner{flex-direction:column;align-items:stretch;text-align:center;}
			.gv-hub-promo-text p{max-width:none;}
			.gv-hub-promo-contacts{justify-content:center;}
			.gv-hub-promo-cta{text-align:center;}
		}

		.gv-hub-footer{text-align:center;color:#94a3b8;font-size:12.5px;margin:10px 0;}
	</style>
	<?php
}