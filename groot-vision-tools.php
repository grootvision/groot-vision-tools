<?php
/*
Plugin Name: Groot Vision Tools
Plugin URI: https://grootvision.com
Description: مجموعه ابزارهای اختصاصی گروت ویژن
Version: 2.1.0
Author: Groot Vision
Author URI: https://grootvision.com
*/
// جلوگیری از اجرای مستقیم فایل
if ( ! defined('ABSPATH') ) {
    exit;
}
/*
|--------------------------------------------------------------------------
| تعریف مسیر افزونه
|--------------------------------------------------------------------------
*/
define('GV_TOOLS_PATH', plugin_dir_path(__FILE__));
define('GV_TOOLS_URL', plugin_dir_url(__FILE__));
define('GV_TOOLS_VERSION', '2.1.0'); // هر بار نسخه جدید منتشر می‌کنید، این عدد را هم مثل بالای فایل تغییر دهید
/*
|--------------------------------------------------------------------------
| لود داشبورد اصلی
|--------------------------------------------------------------------------
*/
require_once GV_TOOLS_PATH . 'admin/admin.php';
/*
|--------------------------------------------------------------------------
| لود ماژول‌ها
|--------------------------------------------------------------------------
*/
require_once GV_TOOLS_PATH . 'modules/add_cart.php';
require_once GV_TOOLS_PATH . 'modules/notification.php';
require_once GV_TOOLS_PATH . 'modules/progress_bar.php';
require_once GV_TOOLS_PATH . 'modules/speed_and_security.php';
require_once GV_TOOLS_PATH . 'modules/topbar_news.php';
require_once GV_TOOLS_PATH . 'modules/table_styles.php';
require_once GV_TOOLS_PATH . 'modules/seo_keywords.php';

/*
|--------------------------------------------------------------------------
| ماژول‌های جدید
|--------------------------------------------------------------------------
*/
require_once GV_TOOLS_PATH . 'modules/github_updater.php';   // به‌روزرسانی خودکار از گیت‌هاب
require_once GV_TOOLS_PATH . 'modules/login_customizer.php'; // طراحی صفحه ورود وردپرس
require_once GV_TOOLS_PATH . 'modules/font_manager.php';     // مدیریت فونت سایت
require_once GV_TOOLS_PATH . 'modules/post_date_jalali.php'; // تاریخ خودکار شمسی روی عنوان
require_once GV_TOOLS_PATH . 'modules/visitor_analytics.php'; // آمار بازدید و رفتار کاربران

/*
|--------------------------------------------------------------------------
| پاکسازی هنگام غیرفعال‌سازی افزونه (فقط زمان‌بندی cron را پاک می‌کند،
| داده‌های آماری و تنظیمات دست‌نخورده باقی می‌مانند تا اگر دوباره
| فعال کردید چیزی از دست نرود)
|--------------------------------------------------------------------------
*/
register_deactivation_hook( __FILE__, 'gv_tools_on_deactivate' );
function gv_tools_on_deactivate() {
	$timestamp = wp_next_scheduled( 'gv_va_daily_cleanup' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'gv_va_daily_cleanup' );
	}
}
