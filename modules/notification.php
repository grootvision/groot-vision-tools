<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * ==========================================================
 *  Vitrin Purchase Notification Snippet
 *  Developed by: GROOT VISION (گروت ویژن)
 *  Instagram: grootvision
 *
 *  Features:
 *   - Custom post type for purchase notices
 *   - Bulk CSV Import / Export (no manual one-by-one entry)
 *   - Click tracking per notice (shown in admin list + stats tab)
 *   - Fully responsive / mobile-friendly popup card
 *     (independent mobile position, offset-from-top, and size/scale)
 *   - Modern tabbed admin settings page (Settings / Import-Export / Stats)
 *
 *  ⚠️ این فایل زیرمجموعه‌ی هاب گروت ویژن (groot-vision-hub.php) است.
 *     برای کارکرد درست، باید فایل هاب هم فعال باشد.
 * ==========================================================
 */

if ( ! defined( 'VP_GROOT_VISION' ) ) {
    define( 'VP_GROOT_VISION', 'Groot Vision' ); // برند این اسنیپت: گروت ویژن
}
define( 'VP_SETTINGS_SLUG', 'vitrin_purchase_settings' );

/* ------------------------------------------------------------------
 * 1) Custom Post Type — GROOT VISION
 * ---------------------------------------------------------------- */
add_action( 'init', function () {
    register_post_type( 'vitrin_purchase', [
        'labels' => [
            'name'          => 'اعلان خرید',
            'singular_name' => 'اعلان خرید',
            'add_new'       => 'افزودن خرید جدید',
            'add_new_item'  => 'افزودن اعلان خرید جدید',
            'edit_item'     => 'ویرایش اعلان خرید',
            'all_items'     => 'همه اعلان‌ها',
            'menu_name'     => 'لیست خریدها',
        ],
        'public'        => false,
        'show_ui'       => true,
        // ⚠️ اصلاح شد: قبلاً 'groot-vision-hub' بود و همین باعث می‌شد وردپرس
        // به‌صورت خودکار «افزودن خرید جدید» و «همه اعلان‌ها» را به‌عنوان
        // زیرمنوهای جداگانه، کنار «افزونه‌های گروت» در سایدبار اصلی نمایش دهد.
        // با false کردنش، این آیتم‌ها دیگر در سایدبار وردپرس دیده نمی‌شوند،
        // ولی صفحات ثبت/ویرایش همچنان از طریق دکمه‌های داخل خودِ صفحه‌ی
        // تنظیمات «اعلان خرید» در دسترس هستند (چون show_ui همچنان true است).
        'show_in_menu'  => false,
        'supports'      => [ 'title' ],

    ] );
} );

/* ------------------------------------------------------------------
 * 1.b) صفحه اصلی تنظیمات به‌عنوان زیرمنوی هاب — GROOT VISION
 * ---------------------------------------------------------------- */
add_action( 'admin_menu', 'vitrin_purchase_admin_menu' );
function vitrin_purchase_admin_menu() {
    add_submenu_page(
        'groot-vision-hub',                    // والد: هاب گروت ویژن
        'تنظیمات اعلان خرید | Groot Vision',
        '🛒 اعلان خرید',
        'manage_options',
        VP_SETTINGS_SLUG,
        'vitrin_purchase_settings_page'
    );
}

/* ------------------------------------------------------------------
 * 2) Meta box (single-item entry — still available besides CSV import)
 *    — GROOT VISION
 * ---------------------------------------------------------------- */
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'vitrin_purchase_details',
        'جزئیات خرید',
        function ( $post ) {
            wp_nonce_field( 'vitrin_purchase_save', 'vitrin_purchase_nonce' );
            $city    = get_post_meta( $post->ID, '_vp_city', true );
            $product = get_post_meta( $post->ID, '_vp_product', true );
            $link    = get_post_meta( $post->ID, '_vp_link', true );
            $minutes = get_post_meta( $post->ID, '_vp_minutes', true );
            $clicks  = intval( get_post_meta( $post->ID, '_vp_clicks', true ) );
            ?>
            <p><label>نام مشتری: از فیلد «عنوان» بالا استفاده می‌شود.</label></p>
            <p>
                <label>شهر مشتری:</label><br>
                <input type="text" name="vp_city" value="<?php echo esc_attr( $city ); ?>" style="width:100%" placeholder="مثلا: تهران">
            </p>
            <p>
                <label>نام محصول خریداری‌شده:</label><br>
                <input type="text" name="vp_product" value="<?php echo esc_attr( $product ); ?>" style="width:100%" placeholder="مثلا: قالب فروشگاهی وردپرس">
            </p>
            <p>
                <label>لینک محصول (اختیاری):</label><br>
                <input type="text" name="vp_link" value="<?php echo esc_attr( $link ); ?>" style="width:100%" placeholder="https://...">
            </p>
            <p>
                <label>چند دقیقه پیش این خرید انجام شده؟ (عدد)</label><br>
                <input type="number" name="vp_minutes" value="<?php echo esc_attr( $minutes ?: 5 ); ?>" style="width:100%">
                <small>این عدد فقط برای متن «X دقیقه پیش» استفاده می‌شود.</small>
            </p>
            <p>
                <label>تعداد کلیک ثبت‌شده روی این اعلان:</label><br>
                <strong style="font-size:16px;color:#9f1239;"><?php echo esc_html( $clicks ); ?></strong>
                <small>(این مقدار به صورت خودکار توسط اسکریپت گروت ویژن ثبت می‌شود)</small>
            </p>
            <p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . VP_SETTINGS_SLUG ) ); ?>">&larr; بازگشت به تنظیمات اعلان خرید</a>
            </p>
            <?php
        },
        'vitrin_purchase',
        'normal',
        'high'
    );
} );

add_action( 'save_post', function ( $post_id ) {
    if ( ! isset( $_POST['vitrin_purchase_nonce'] ) || ! wp_verify_nonce( $_POST['vitrin_purchase_nonce'], 'vitrin_purchase_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( isset( $_POST['vp_city'] ) )    update_post_meta( $post_id, '_vp_city', sanitize_text_field( $_POST['vp_city'] ) );
    if ( isset( $_POST['vp_product'] ) ) update_post_meta( $post_id, '_vp_product', sanitize_text_field( $_POST['vp_product'] ) );
    if ( isset( $_POST['vp_link'] ) )    update_post_meta( $post_id, '_vp_link', esc_url_raw( $_POST['vp_link'] ) );
    if ( isset( $_POST['vp_minutes'] ) ) update_post_meta( $post_id, '_vp_minutes', intval( $_POST['vp_minutes'] ) );
} );

/* ------------------------------------------------------------------
 * 3) Admin list columns — show city / product / clicks — GROOT VISION
 * ---------------------------------------------------------------- */
add_filter( 'manage_vitrin_purchase_posts_columns', function ( $columns ) {
    $new = [];
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'title' === $key ) {
            $new['vp_city']    = 'شهر';
            $new['vp_product'] = 'محصول';
            $new['vp_clicks']  = 'کلیک‌ها';
        }
    }
    return $new;
} );

add_action( 'manage_vitrin_purchase_posts_custom_column', function ( $column, $post_id ) {
    if ( 'vp_city' === $column ) {
        echo esc_html( get_post_meta( $post_id, '_vp_city', true ) ?: '—' );
    }
    if ( 'vp_product' === $column ) {
        echo esc_html( get_post_meta( $post_id, '_vp_product', true ) ?: '—' );
    }
    if ( 'vp_clicks' === $column ) {
        $clicks = intval( get_post_meta( $post_id, '_vp_clicks', true ) );
        if ( $clicks > 0 ) {
            echo '<span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-weight:600;">✔ کلیک شده (' . esc_html( $clicks ) . ')</span>';
        } else {
            echo '<span style="background:#f1f5f9;color:#64748b;padding:3px 10px;border-radius:20px;">کلیک نشده</span>';
        }
    }
}, 10, 2 );

add_filter( 'manage_edit-vitrin_purchase_sortable_columns', function ( $columns ) {
    $columns['vp_clicks'] = 'vp_clicks';
    return $columns;
} );

add_action( 'pre_get_posts', function ( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) return;
    if ( 'vitrin_purchase' !== $query->get( 'post_type' ) ) return;
    if ( 'vp_clicks' === $query->get( 'orderby' ) ) {
        $query->set( 'meta_key', '_vp_clicks' );
        $query->set( 'orderby', 'meta_value_num' );
    }
} );

/* ------------------------------------------------------------------
 * 3.b) لینک بازگشت به صفحه‌ی تنظیمات، بالای لیست/فرم ویرایش اعلان‌ها
 *      — GROOT VISION
 *      (چون این پست‌تایپ دیگر در سایدبار نیست، این‌جا یک راه برگشت
 *      واضح به کاربر می‌دهیم.)
 * ---------------------------------------------------------------- */
add_action( 'admin_notices', function () {
    global $pagenow, $typenow;
    if ( 'vitrin_purchase' !== $typenow ) return;
    if ( ! in_array( $pagenow, [ 'edit.php', 'post.php', 'post-new.php' ], true ) ) return;
    ?>
    <div class="notice notice-info" style="direction:rtl;">
        <p>
            این صفحه بخشی از افزونه‌ی «اعلان خرید» است.
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . VP_SETTINGS_SLUG ) ); ?>">بازگشت به تنظیمات اعلان خرید ←</a>
        </p>
    </div>
    <?php
} );

/* ------------------------------------------------------------------
 * 4) Click tracking — AJAX endpoint — GROOT VISION
 * ---------------------------------------------------------------- */
add_action( 'wp_ajax_vp_track_click', 'vp_handle_track_click' );
add_action( 'wp_ajax_nopriv_vp_track_click', 'vp_handle_track_click' );
function vp_handle_track_click() {
    check_ajax_referer( 'vp_track_click_nonce', 'nonce' );
    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id || 'vitrin_purchase' !== get_post_type( $post_id ) ) {
        wp_send_json_error( [ 'message' => 'invalid post' ] );
    }
    $clicks = intval( get_post_meta( $post_id, '_vp_clicks', true ) );
    $clicks++;
    update_post_meta( $post_id, '_vp_clicks', $clicks );
    update_post_meta( $post_id, '_vp_last_click', current_time( 'mysql' ) );
    wp_send_json_success( [ 'clicks' => $clicks ] );
}

/* ------------------------------------------------------------------
 * 5) CSV Import / Export handlers (admin-post.php) — GROOT VISION
 * ---------------------------------------------------------------- */

// --- Import CSV ---
add_action( 'admin_post_vp_import_csv', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'دسترسی غیرمجاز' );
    check_admin_referer( 'vp_import_csv_action', 'vp_import_csv_nonce' );

    $redirect_base = admin_url( 'admin.php?page=' . VP_SETTINGS_SLUG . '&tab=import' );

    if ( empty( $_FILES['vp_csv_file'] ) || empty( $_FILES['vp_csv_file']['tmp_name'] ) || UPLOAD_ERR_OK !== $_FILES['vp_csv_file']['error'] ) {
        wp_safe_redirect( add_query_arg( [ 'vp_import' => 'error' ], $redirect_base ) );
        exit;
    }

    $handle = fopen( $_FILES['vp_csv_file']['tmp_name'], 'r' );
    if ( ! $handle ) {
        wp_safe_redirect( add_query_arg( [ 'vp_import' => 'error' ], $redirect_base ) );
        exit;
    }

    $imported = 0;
    $row_index = 0;

    while ( ( $row = fgetcsv( $handle ) ) !== false ) {
        $row_index++;
        if ( empty( $row ) || ( count( $row ) === 1 && trim( $row[0] ) === '' ) ) continue;

        // Strip UTF-8 BOM from first cell if present
        if ( $row_index === 1 && isset( $row[0] ) ) {
            $row[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $row[0] );
        }

        $first_cell = isset( $row[0] ) ? trim( mb_strtolower( $row[0] ) ) : '';
        $header_words = [ 'نام', 'name', 'مشتری' ];
        if ( $row_index === 1 && in_array( $first_cell, $header_words, true ) ) {
            continue; // skip header row
        }

        $name    = isset( $row[0] ) ? sanitize_text_field( trim( $row[0] ) ) : '';
        $city    = isset( $row[1] ) ? sanitize_text_field( trim( $row[1] ) ) : '';
        $product = isset( $row[2] ) ? sanitize_text_field( trim( $row[2] ) ) : '';
        $link    = isset( $row[3] ) ? esc_url_raw( trim( $row[3] ) ) : '';
        $minutes = isset( $row[4] ) && is_numeric( $row[4] ) ? intval( $row[4] ) : rand( 3, 40 );

        if ( '' === $name ) continue;

        $post_id = wp_insert_post( [
            'post_type'   => 'vitrin_purchase',
            'post_status' => 'publish',
            'post_title'  => $name,
        ] );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_vp_city', $city );
            update_post_meta( $post_id, '_vp_product', $product );
            update_post_meta( $post_id, '_vp_link', $link );
            update_post_meta( $post_id, '_vp_minutes', $minutes );
            update_post_meta( $post_id, '_vp_clicks', 0 );
            $imported++;
        }
    }
    fclose( $handle );

    wp_safe_redirect( add_query_arg( [ 'vp_import' => 'success', 'count' => $imported ], $redirect_base ) );
    exit;
} );

// --- Export CSV ---
add_action( 'admin_post_vp_export_csv', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'دسترسی غیرمجاز' );
    check_admin_referer( 'vp_export_csv_action', 'vp_export_csv_nonce' );

    $posts = get_posts( [
        'post_type'      => 'vitrin_purchase',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );

    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=vitrin-purchase-groot-vision-export.csv' );

    $out = fopen( 'php://output', 'w' );
    fprintf( $out, chr(0xEF) . chr(0xBB) . chr(0xBF) ); // UTF-8 BOM for Excel/Persian support
    fputcsv( $out, [ 'نام', 'شهر', 'محصول', 'لینک', 'دقیقه', 'کلیک' ] );

    foreach ( $posts as $p ) {
        fputcsv( $out, [
            $p->post_title,
            get_post_meta( $p->ID, '_vp_city', true ),
            get_post_meta( $p->ID, '_vp_product', true ),
            get_post_meta( $p->ID, '_vp_link', true ),
            get_post_meta( $p->ID, '_vp_minutes', true ),
            intval( get_post_meta( $p->ID, '_vp_clicks', true ) ),
        ] );
    }
    fclose( $out );
    exit;
} );

// --- Download sample CSV template ---
add_action( 'admin_post_vp_sample_csv', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'دسترسی غیرمجاز' );
    check_admin_referer( 'vp_sample_csv_action', 'vp_sample_csv_nonce' );

    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=vitrin-purchase-sample-groot-vision.csv' );

    $out = fopen( 'php://output', 'w' );
    fprintf( $out, chr(0xEF) . chr(0xBB) . chr(0xBF) );
    fputcsv( $out, [ 'نام', 'شهر', 'محصول', 'لینک', 'دقیقه' ] );
    fputcsv( $out, [ 'علی رضایی', 'تهران', 'قالب فروشگاهی وردپرس', 'https://example.com/product', 12 ] );
    fputcsv( $out, [ 'سارا محمدی', 'اصفهان', 'افزونه سئو', 'https://example.com/product-2', 34 ] );
    fclose( $out );
    exit;
} );

/* ------------------------------------------------------------------
 * 6) Settings page — modern tabbed UI — GROOT VISION
 * ---------------------------------------------------------------- */
function vitrin_purchase_settings_page() {

    if ( isset( $_POST['vp_save_settings'] ) && check_admin_referer( 'vp_save_settings_action' ) ) {
        update_option( 'vp_enabled', isset( $_POST['vp_enabled'] ) ? 1 : 0 );
        update_option( 'vp_position', sanitize_text_field( $_POST['vp_position'] ) );
        update_option( 'vp_bg_color', sanitize_hex_color( $_POST['vp_bg_color'] ) );
        update_option( 'vp_text_color', sanitize_hex_color( $_POST['vp_text_color'] ) );
        update_option( 'vp_accent_color', sanitize_hex_color( $_POST['vp_accent_color'] ) );
        update_option( 'vp_display_seconds', intval( $_POST['vp_display_seconds'] ) );
        update_option( 'vp_min_interval', intval( $_POST['vp_min_interval'] ) );
        update_option( 'vp_max_interval', intval( $_POST['vp_max_interval'] ) );
        update_option( 'vp_shuffle', isset( $_POST['vp_shuffle'] ) ? 1 : 0 );
        // --- Mobile-specific display settings — GROOT VISION ---
        update_option( 'vp_mobile_position', sanitize_text_field( $_POST['vp_mobile_position'] ) );
        update_option( 'vp_mobile_top_offset', intval( $_POST['vp_mobile_top_offset'] ) );
        update_option( 'vp_mobile_scale', max( 40, min( 100, intval( $_POST['vp_mobile_scale'] ) ) ) );
        update_option( 'vp_mobile_max_width', max( 200, intval( $_POST['vp_mobile_max_width'] ) ) );
        echo '<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
    }

    $enabled  = get_option( 'vp_enabled', 1 );
    $position = get_option( 'vp_position', 'top-left' );
    $bg       = get_option( 'vp_bg_color', '#ffffff' );
    $text     = get_option( 'vp_text_color', '#1a1a1a' );
    $accent   = get_option( 'vp_accent_color', '#9f1239' );
    $duration = get_option( 'vp_display_seconds', 6 );
    $min_int  = get_option( 'vp_min_interval', 8 );
    $max_int  = get_option( 'vp_max_interval', 20 );
    $shuffle  = get_option( 'vp_shuffle', 1 );

    // Mobile-specific options — default: top-right, just below the header
    $mobile_position   = get_option( 'vp_mobile_position', 'top-right' );
    $mobile_top_offset = get_option( 'vp_mobile_top_offset', 70 );
    $mobile_scale      = get_option( 'vp_mobile_scale', 82 );
    $mobile_max_width  = get_option( 'vp_mobile_max_width', 260 );

    $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
    $base_url = admin_url( 'admin.php?page=' . VP_SETTINGS_SLUG );

    // Stats
    $all_posts   = get_posts( [ 'post_type' => 'vitrin_purchase', 'post_status' => 'any', 'posts_per_page' => -1 ] );
    $total_count = count( $all_posts );
    $total_clicks = 0;
    $rows = [];
    foreach ( $all_posts as $p ) {
        $c = intval( get_post_meta( $p->ID, '_vp_clicks', true ) );
        $total_clicks += $c;
        $rows[] = [
            'id'      => $p->ID,
            'name'    => $p->post_title,
            'city'    => get_post_meta( $p->ID, '_vp_city', true ),
            'product' => get_post_meta( $p->ID, '_vp_product', true ),
            'clicks'  => $c,
        ];
    }
    usort( $rows, function ( $a, $b ) { return $b['clicks'] <=> $a['clicks']; } );
    $ctr = $total_count > 0 ? round( ( $total_clicks / $total_count ), 2 ) : 0;
    ?>
    <style>
        .vp-gv-wrap { max-width: 980px; margin-top: 20px; direction: rtl; font-family: inherit; }
        .vp-gv-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; flex-wrap:wrap; gap:10px; }
        .vp-gv-header h1 { font-size:22px; margin:0; }
        .vp-gv-badge { background:linear-gradient(135deg,#9f1239,#e11d48); color:#fff; padding:5px 14px; border-radius:20px; font-size:12px; font-weight:600; letter-spacing:.3px; }
        .vp-gv-manage-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:20px 24px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px; box-shadow:0 1px 2px rgba(0,0,0,.03), 0 8px 24px -12px rgba(0,0,0,.08); }
        .vp-gv-manage-card h2 { margin:0 0 4px; font-size:15px; }
        .vp-gv-manage-card p { margin:0; color:#64748b; font-size:13px; }
        .vp-gv-manage-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .vp-gv-tabs { display:flex; gap:8px; margin-bottom:22px; flex-wrap:wrap; }
        .vp-gv-tab { padding:9px 18px; border-radius:10px; background:#fff; border:1px solid #e5e7eb; color:#334155; text-decoration:none; font-weight:600; font-size:13px; transition:.15s; }
        .vp-gv-tab:hover { background:#f8fafc; color:#111; }
        .vp-gv-tab.active { background:#111827; color:#fff; border-color:#111827; }
        .vp-gv-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:26px; box-shadow:0 1px 2px rgba(0,0,0,.03), 0 8px 24px -12px rgba(0,0,0,.08); margin-bottom:20px; }
        .vp-gv-card h2 { margin-top:0; font-size:16px; }
        .vp-gv-card h2 small { font-weight:400; color:#94a3b8; font-size:12px; margin-right:6px; }
        .vp-gv-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        @media (max-width:700px){ .vp-gv-grid{ grid-template-columns:1fr; } }
        .vp-gv-field label { display:block; font-weight:600; margin-bottom:6px; font-size:13px; color:#334155; }
        .vp-gv-field input[type=text], .vp-gv-field input[type=number], .vp-gv-field select { width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:8px; }
        .vp-gv-field input[type=color] { width:60px; height:38px; border-radius:8px; border:1px solid #d1d5db; padding:2px; }
        .vp-gv-field small.vp-gv-hint { display:block; color:#94a3b8; font-size:11.5px; margin-top:4px; }
        .vp-gv-switch { display:flex; align-items:center; gap:8px; font-weight:600; font-size:13px; }
        .vp-gv-btn { background:#111827; color:#fff !important; border:none; padding:10px 22px; border-radius:10px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block; }
        .vp-gv-btn:hover { background:#1f2937; color:#fff; }
        .vp-gv-btn.outline { background:#fff; color:#111827 !important; border:1px solid #d1d5db; }
        .vp-gv-stat-cards { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:20px; }
        @media (max-width:700px){ .vp-gv-stat-cards{ grid-template-columns:1fr; } }
        .vp-gv-stat { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:18px 20px; }
        .vp-gv-stat b { display:block; font-size:26px; color:#9f1239; }
        .vp-gv-stat span { font-size:13px; color:#64748b; }
        .vp-gv-table { width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; }
        .vp-gv-table th, .vp-gv-table td { padding:10px 12px; text-align:right; border-bottom:1px solid #f1f5f9; font-size:13px; }
        .vp-gv-table th { background:#f8fafc; font-weight:700; }
        .vp-gv-table td.vp-gv-row-actions a { font-size:12px; margin-left:10px; }
        .vp-gv-footer { text-align:center; color:#94a3b8; font-size:12px; margin-top:24px; }
        .vp-gv-badge-soft { display:inline-block; background:#eef2ff; color:#4338ca; font-size:11px; font-weight:700; padding:2px 9px; border-radius:20px; margin-right:6px; vertical-align:middle; }
    </style>

    <div class="wrap vp-gv-wrap">
        <div class="vp-gv-header">
            <h1>🛒 اعلان خرید — تنظیمات</h1>
            <span class="vp-gv-badge">Powered by Groot Vision</span>
        </div>

        <?php if ( isset( $_GET['vp_import'] ) ) : ?>
            <?php if ( 'success' === $_GET['vp_import'] ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo intval( $_GET['count'] ?? 0 ); ?> اعلان با موفقیت از فایل CSV وارد شد.</p></div>
            <?php else : ?>
                <div class="notice notice-error is-dismissible"><p>خطا در آپلود فایل CSV. لطفاً فایل را بررسی و دوباره تلاش کنید.</p></div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- ============ مدیریت سریع اعلان‌ها (چون این پست‌تایپ دیگر در سایدبار اصلی وردپرس نیست) ============ -->
        <div class="vp-gv-manage-card">
            <div>
                <h2>مدیریت اعلان‌ها</h2>
                <p>افزودن اعلان جدید یا مشاهده و ویرایش لیست کامل اعلان‌های ثبت‌شده.</p>
            </div>
            <div class="vp-gv-manage-actions">
                <a class="vp-gv-btn" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=vitrin_purchase' ) ); ?>">➕ افزودن اعلان جدید</a>
                <a class="vp-gv-btn outline" href="<?php echo esc_url( admin_url( 'edit.php?post_type=vitrin_purchase' ) ); ?>">📋 مشاهده همه اعلان‌ها</a>
            </div>
        </div>

        <div class="vp-gv-tabs">
            <a class="vp-gv-tab <?php echo 'general' === $tab ? 'active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'general', $base_url ) ); ?>">⚙️ تنظیمات نمایش</a>
            <a class="vp-gv-tab <?php echo 'import' === $tab ? 'active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'import', $base_url ) ); ?>">📥 ایمپورت / اکسپورت CSV</a>
            <a class="vp-gv-tab <?php echo 'stats' === $tab ? 'active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'stats', $base_url ) ); ?>">📊 آمار کلیک‌ها</a>
        </div>

        <?php if ( 'general' === $tab ) : ?>
            <form method="post">
                <?php wp_nonce_field( 'vp_save_settings_action' ); ?>
                <div class="vp-gv-card">
                    <h2>وضعیت نمایش</h2>
                    <label class="vp-gv-switch">
                        <input type="checkbox" name="vp_enabled" <?php checked( $enabled, 1 ); ?>>
                        نمایش اعلان‌ها در سایت فعال باشد
                    </label>
                </div>

                <div class="vp-gv-card">
                    <h2>ظاهر و موقعیت <small>(حالت دسکتاپ)</small></h2>
                    <div class="vp-gv-grid">
                        <div class="vp-gv-field">
                            <label>موقعیت نمایش</label>
                            <select name="vp_position">
                                <option value="bottom-left" <?php selected( $position, 'bottom-left' ); ?>>پایین چپ</option>
                                <option value="bottom-right" <?php selected( $position, 'bottom-right' ); ?>>پایین راست</option>
                                <option value="top-left" <?php selected( $position, 'top-left' ); ?>>بالا چپ</option>
                                <option value="top-right" <?php selected( $position, 'top-right' ); ?>>بالا راست</option>
                            </select>
                        </div>
                        <div class="vp-gv-field">
                            <label>ترتیب نمایش</label>
                            <label class="vp-gv-switch">
                                <input type="checkbox" name="vp_shuffle" <?php checked( $shuffle, 1 ); ?>>
                                نمایش تصادفی به‌جای ترتیب ثبت
                            </label>
                        </div>
                        <div class="vp-gv-field">
                            <label>رنگ پس‌زمینه</label>
                            <input type="color" name="vp_bg_color" value="<?php echo esc_attr( $bg ); ?>">
                        </div>
                        <div class="vp-gv-field">
                            <label>رنگ متن</label>
                            <input type="color" name="vp_text_color" value="<?php echo esc_attr( $text ); ?>">
                        </div>
                        <div class="vp-gv-field">
                            <label>رنگ تاکیدی (آواتار / نشان تایید / نوار پیشرفت)</label>
                            <input type="color" name="vp_accent_color" value="<?php echo esc_attr( $accent ); ?>">
                        </div>
                    </div>
                </div>

                <div class="vp-gv-card">
                    <h2>📱 نمایش در موبایل <span class="vp-gv-badge-soft">مستقل از دسکتاپ</span></h2>
                    <p style="color:#64748b;font-size:13px;margin-top:-6px;">در صفحه‌نمایش‌های کوچک (موبایل)، اعلان می‌تواند موقعیت، فاصله از بالا و اندازه‌ی جداگانه‌ای داشته باشد — مثلاً بالا-راست و زیر هدر سایت.</p>
                    <div class="vp-gv-grid">
                        <div class="vp-gv-field">
                            <label>موقعیت نمایش در موبایل</label>
                            <select name="vp_mobile_position">
                                <option value="top-right" <?php selected( $mobile_position, 'top-right' ); ?>>بالا راست (زیر هدر)</option>
                                <option value="top-left" <?php selected( $mobile_position, 'top-left' ); ?>>بالا چپ (زیر هدر)</option>
                                <option value="bottom-right" <?php selected( $mobile_position, 'bottom-right' ); ?>>پایین راست</option>
                                <option value="bottom-left" <?php selected( $mobile_position, 'bottom-left' ); ?>>پایین چپ</option>
                            </select>
                            <small class="vp-gv-hint">پیش‌فرض: بالا راست، دقیقاً زیر هدر سایت.</small>
                        </div>
                        <div class="vp-gv-field">
                            <label>فاصله از بالا/پایین صفحه (پیکسل)</label>
                            <input type="number" name="vp_mobile_top_offset" value="<?php echo esc_attr( $mobile_top_offset ); ?>" min="0" max="300">
                            <small class="vp-gv-hint">اگر موقعیت «بالا» انتخاب شده، این عدد باید کمی بیشتر از ارتفاع هدر سایت شما باشد تا اعلان زیر آن قرار بگیرد.</small>
                        </div>
                        <div class="vp-gv-field">
                            <label>اندازه نسبت به حالت دسکتاپ (٪)</label>
                            <input type="number" name="vp_mobile_scale" value="<?php echo esc_attr( $mobile_scale ); ?>" min="40" max="100">
                            <small class="vp-gv-hint">مثلاً ۸۰ یعنی کارت در موبایل ۲۰٪ کوچک‌تر از دسکتاپ نمایش داده شود.</small>
                        </div>
                        <div class="vp-gv-field">
                            <label>حداکثر عرض کارت در موبایل (پیکسل)</label>
                            <input type="number" name="vp_mobile_max_width" value="<?php echo esc_attr( $mobile_max_width ); ?>" min="200" max="400">
                        </div>
                    </div>
                </div>

                <div class="vp-gv-card">
                    <h2>زمان‌بندی</h2>
                    <div class="vp-gv-grid">
                        <div class="vp-gv-field">
                            <label>مدت نمایش هر اعلان (ثانیه)</label>
                            <input type="number" name="vp_display_seconds" value="<?php echo esc_attr( $duration ); ?>" min="2" max="30">
                        </div>
                        <div class="vp-gv-field"></div>
                        <div class="vp-gv-field">
                            <label>حداقل فاصله بین اعلان‌ها (ثانیه)</label>
                            <input type="number" name="vp_min_interval" value="<?php echo esc_attr( $min_int ); ?>" min="2">
                        </div>
                        <div class="vp-gv-field">
                            <label>حداکثر فاصله بین اعلان‌ها (ثانیه)</label>
                            <input type="number" name="vp_max_interval" value="<?php echo esc_attr( $max_int ); ?>" min="2">
                        </div>
                    </div>
                </div>

                <button type="submit" name="vp_save_settings" class="vp-gv-btn">ذخیره تنظیمات</button>
            </form>

        <?php elseif ( 'import' === $tab ) : ?>

            <div class="vp-gv-card">
                <h2>📥 ایمپورت گروهی از CSV</h2>
                <p>به‌جای ثبت دستی هر اعلان، می‌توانید یک فایل CSV با ستون‌های زیر آپلود کنید تا تمام ردیف‌ها به‌صورت خودکار اضافه شوند:</p>
                <p><code>نام, شهر, محصول, لینک, دقیقه</code> <small>(ستون «دقیقه» اختیاری است و در صورت خالی بودن به‌طور تصادفی پر می‌شود)</small></p>
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="vp_import_csv">
                    <?php wp_nonce_field( 'vp_import_csv_action', 'vp_import_csv_nonce' ); ?>
                    <p><input type="file" name="vp_csv_file" accept=".csv" required></p>
                    <button type="submit" class="vp-gv-btn">آپلود و افزودن خودکار</button>
                    <a class="vp-gv-btn outline" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=vp_sample_csv' ), 'vp_sample_csv_action', 'vp_sample_csv_nonce' ) ); ?>">دانلود نمونه CSV</a>
                </form>
            </div>

            <div class="vp-gv-card">
                <h2>📤 اکسپورت اعلان‌های فعلی</h2>
                <p>می‌توانید تمام اعلان‌های ثبت‌شده (به‌همراه آمار کلیک هرکدام) را در قالب CSV دانلود کنید.</p>
                <a class="vp-gv-btn" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=vp_export_csv' ), 'vp_export_csv_action', 'vp_export_csv_nonce' ) ); ?>">دانلود خروجی CSV</a>
            </div>

        <?php elseif ( 'stats' === $tab ) : ?>

            <div class="vp-gv-stat-cards">
                <div class="vp-gv-stat"><b><?php echo esc_html( $total_count ); ?></b><span>تعداد کل اعلان‌ها</span></div>
                <div class="vp-gv-stat"><b><?php echo esc_html( $total_clicks ); ?></b><span>مجموع کلیک‌ها</span></div>
                <div class="vp-gv-stat"><b><?php echo esc_html( $ctr ); ?></b><span>میانگین کلیک به‌ازای هر اعلان</span></div>
            </div>

            <div class="vp-gv-card">
                <h2>جزئیات کلیک هر اعلان</h2>
                <table class="vp-gv-table">
                    <thead>
                        <tr><th>نام</th><th>شهر</th><th>محصول</th><th>وضعیت</th><th>عملیات</th></tr>
                    </thead>
                    <tbody>
                        <?php if ( empty( $rows ) ) : ?>
                            <tr><td colspan="5">هنوز هیچ اعلانی ثبت نشده است.</td></tr>
                        <?php else : foreach ( $rows as $r ) : ?>
                            <tr>
                                <td><?php echo esc_html( $r['name'] ); ?></td>
                                <td><?php echo esc_html( $r['city'] ?: '—' ); ?></td>
                                <td><?php echo esc_html( $r['product'] ?: '—' ); ?></td>
                                <td>
                                    <?php if ( $r['clicks'] > 0 ) : ?>
                                        <span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-weight:600;">✔ کلیک شده (<?php echo esc_html( $r['clicks'] ); ?>)</span>
                                    <?php else : ?>
                                        <span style="background:#f1f5f9;color:#64748b;padding:3px 10px;border-radius:20px;">کلیک نشده</span>
                                    <?php endif; ?>
                                </td>
                                <td class="vp-gv-row-actions">
                                    <a href="<?php echo esc_url( get_edit_post_link( $r['id'] ) ); ?>">ویرایش</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

        <p class="vp-gv-footer">طراحی و توسعه توسط <strong>Groot Vision (گروت ویژن)</strong> | اینستاگرام: grootvision</p>
    </div>
    <?php
}

/* ------------------------------------------------------------------
 * 7) Front-end enqueue — GROOT VISION
 * ---------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', function () {
    if ( ! get_option( 'vp_enabled', 1 ) ) return;
    if ( is_admin() ) return;

    $posts = get_posts( [
        'post_type'      => 'vitrin_purchase',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );

    if ( empty( $posts ) ) return;

    $items = [];
    foreach ( $posts as $p ) {
        $minutes = intval( get_post_meta( $p->ID, '_vp_minutes', true ) ) ?: 5;
        $items[] = [
            'id'      => $p->ID,
            'name'    => esc_html( $p->post_title ),
            'city'    => esc_html( get_post_meta( $p->ID, '_vp_city', true ) ),
            'product' => esc_html( get_post_meta( $p->ID, '_vp_product', true ) ),
            'link'    => esc_url( get_post_meta( $p->ID, '_vp_link', true ) ),
            'minutes' => $minutes,
        ];
    }

    $settings = [
        'position'        => get_option( 'vp_position', 'top-left' ),
        'bgColor'         => get_option( 'vp_bg_color', '#ffffff' ),
        'textColor'       => get_option( 'vp_text_color', '#1a1a1a' ),
        'accentColor'     => get_option( 'vp_accent_color', '#9f1239' ),
        'displaySeconds'  => intval( get_option( 'vp_display_seconds', 6 ) ),
        'minInterval'     => intval( get_option( 'vp_min_interval', 8 ) ),
        'maxInterval'     => intval( get_option( 'vp_max_interval', 20 ) ),
        'shuffle'         => (bool) get_option( 'vp_shuffle', 1 ),
        // Mobile-specific — GROOT VISION
        'mobilePosition'  => get_option( 'vp_mobile_position', 'top-right' ),
        'mobileTopOffset' => intval( get_option( 'vp_mobile_top_offset', 70 ) ),
        'mobileScale'     => intval( get_option( 'vp_mobile_scale', 82 ) ) / 100,
        'mobileMaxWidth'  => intval( get_option( 'vp_mobile_max_width', 260 ) ),
        'mobileBreakpoint'=> 480,
        'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
        'trackNonce'      => wp_create_nonce( 'vp_track_click_nonce' ),
    ];

    wp_register_script( 'vp-purchase-notice', false, [], '2.1', true );
    wp_enqueue_script( 'vp-purchase-notice' );
    wp_localize_script( 'vp-purchase-notice', 'VP_DATA', [
        'items'    => $items,
        'settings' => $settings,
        'brand'    => VP_GROOT_VISION, // Groot Vision
    ] );

    wp_add_inline_style( 'vp-purchase-notice', vitrin_purchase_css() );
    wp_add_inline_script( 'vp-purchase-notice', vitrin_purchase_js() );
}, 20 );

add_action( 'wp_head', function () {
    if ( ! get_option( 'vp_enabled', 1 ) || is_admin() ) return;
    echo '<style id="vp-purchase-style">' . vitrin_purchase_css() . '</style>';
    echo "\n<!-- Purchase notification widget by Groot Vision -->\n";
} );

/* ------------------------------------------------------------------
 * 8) Front-end CSS — modern + responsive — GROOT VISION
 * ---------------------------------------------------------------- */
function vitrin_purchase_css() {
    return <<<CSS
    /* ===== Groot Vision — Purchase Notice Styles ===== */
    .vp-notice-wrap {
        position: fixed;
        z-index: 999999;
        max-width: 340px;
        width: calc(100% - 32px);
        pointer-events: none;
        font-family: inherit;
        transform: scale(var(--vp-scale, 1));
        transition: top .25s ease, bottom .25s ease, left .25s ease, right .25s ease;
    }
    .vp-notice-wrap.vp-bottom-left  { bottom: 24px; left: 24px; transform-origin: bottom left; }
    .vp-notice-wrap.vp-bottom-right { bottom: 24px; right: 24px; transform-origin: bottom right; }
    .vp-notice-wrap.vp-top-left     { top: 24px; left: 24px; transform-origin: top left; }
    .vp-notice-wrap.vp-top-right    { top: 24px; right: 24px; transform-origin: top right; }

    .vp-notice-card {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--vp-bg, #fff);
        color: var(--vp-text, #16181d);
        border-radius: 18px;
        padding: 14px 16px;
        box-shadow:
            0 1px 2px rgba(0,0,0,0.04),
            0 14px 34px -12px rgba(0,0,0,0.28);
        direction: rtl;
        font-family: inherit;
        opacity: 0;
        transform: translateY(14px) scale(.97);
        transition: opacity .45s cubic-bezier(.22,1,.36,1), transform .5s cubic-bezier(.22,1,.36,1);
        pointer-events: auto;
        cursor: pointer;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.06);
        backdrop-filter: blur(6px);
    }
    .vp-notice-card.vp-show { opacity: 1; transform: translateY(0) scale(1); }
    .vp-notice-card:hover { transform: translateY(-2px) scale(1.01); }

    .vp-notice-avatar-wrap { position: relative; flex: none; }
    .vp-notice-avatar {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        color: #fff;
        background: linear-gradient(135deg, var(--vp-accent, #9f1239), color-mix(in srgb, var(--vp-accent, #9f1239) 60%, #000 10%));
    }
    .vp-notice-badge {
        position: absolute;
        bottom: -3px;
        left: -3px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--vp-accent, #9f1239);
        border: 2px solid var(--vp-bg, #fff);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .vp-notice-badge svg { width: 9px; height: 9px; }

    .vp-notice-body { flex: 1; line-height: 1.7; min-width: 0; }
    .vp-notice-title {
        font-weight: 600;
        font-size: 14.5px;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .vp-notice-title b { font-weight: 700; }
    .vp-notice-sub {
        font-size: 13.5px;
        margin: 0;
        opacity: 0.68;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .vp-notice-time {
        font-size: 11.5px;
        letter-spacing: 0.02em;
        opacity: 0.5;
        margin-top: 3px;
    }

    .vp-notice-close {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity .2s ease, background .2s ease;
        font-size: 13px;
        color: var(--vp-text, #16181d);
        line-height: 1;
        z-index: 2;
        background: rgba(0,0,0,0.04);
    }
    .vp-notice-card:hover .vp-notice-close { opacity: 0.6; }
    .vp-notice-close:hover { opacity: 1 !important; background: rgba(0,0,0,0.08); }

    .vp-notice-progress {
        position: absolute;
        bottom: 0; right: 0; left: 0;
        height: 3px;
        background: rgba(0,0,0,0.06);
    }
    .vp-notice-progress-bar {
        height: 100%;
        width: 100%;
        background: var(--vp-accent, #9f1239);
        transform-origin: right;
        transform: scaleX(1);
        transition: transform linear;
    }

    /* ===== Mobile / responsive — GROOT VISION =====
       Position, top/bottom offset and transform-origin for mobile are
       applied inline via JS (settings.mobilePosition / mobileTopOffset),
       so the widget can sit top-right just below the site header.
       This block only handles sizing so the card is genuinely smaller
       and more compact on small screens, independent of desktop. */
    @media (max-width: 480px) {
        .vp-notice-wrap {
            max-width: var(--vp-mobile-max-width, 260px);
            width: calc(100% - 16px);
        }
        .vp-notice-card { padding: 10px 12px; border-radius: 14px; gap: 9px; }
        .vp-notice-avatar { width: 34px; height: 34px; border-radius: 11px; font-size: 13px; }
        .vp-notice-badge { width: 15px; height: 15px; }
        .vp-notice-title { font-size: 12.5px; }
        .vp-notice-sub { font-size: 11.5px; }
        .vp-notice-time { font-size: 10.5px; margin-top: 2px; }
        .vp-notice-close { width: 18px; height: 18px; font-size: 12px; opacity: 0.45; }
    }

    @media (prefers-reduced-motion: reduce) {
        .vp-notice-card { transition: opacity 0.3s ease; transform: none !important; }
    }
    CSS;
}

/* ------------------------------------------------------------------
 * 9) Front-end JS — click tracking + modern render — GROOT VISION
 * ---------------------------------------------------------------- */
function vitrin_purchase_js() {
    return <<<JS
    /* ===== Groot Vision — Purchase Notice Script ===== */
    (function () {
        if (!window.VP_DATA || !VP_DATA.items || !VP_DATA.items.length) return;
        var items = VP_DATA.items.slice();
        var settings = VP_DATA.settings;

        if (settings.shuffle) {
            for (var i = items.length - 1; i > 0; i--) {
                var j = Math.floor(Math.random() * (i + 1));
                var tmp = items[i]; items[i] = items[j]; items[j] = tmp;
            }
        }

        var wrap = document.createElement('div');
        wrap.className = 'vp-notice-wrap';
        wrap.style.setProperty('--vp-bg', settings.bgColor);
        wrap.style.setProperty('--vp-text', settings.textColor);
        wrap.style.setProperty('--vp-accent', settings.accentColor);
        wrap.style.setProperty('--vp-mobile-max-width', (settings.mobileMaxWidth || 260) + 'px');

        /* Groot Vision: apply independent mobile position / offset / scale
           so on small screens the widget can sit top-right just below the
           site header, at its own (smaller) size — fully separate from
           the desktop position & size chosen in settings. */
        function isMobileView() {
            return window.matchMedia('(max-width: ' + (settings.mobileBreakpoint || 480) + 'px)').matches;
        }

        function applyResponsiveLayout() {
            var mobile = isMobileView();
            var pos = mobile ? settings.mobilePosition : settings.position;
            var scale = mobile ? settings.mobileScale : 1;

            wrap.classList.remove('vp-top-left', 'vp-top-right', 'vp-bottom-left', 'vp-bottom-right');
            wrap.classList.add('vp-' + pos);
            wrap.style.setProperty('--vp-scale', scale);

            // Reset both axes, then set only the relevant offset for the chosen corner
            wrap.style.top = '';
            wrap.style.bottom = '';
            wrap.style.left = '';
            wrap.style.right = '';

            if (mobile) {
                var offset = (settings.mobileTopOffset || 0) + 'px';
                if (pos.indexOf('top') === 0) {
                    wrap.style.top = offset; // e.g. just below the site header
                } else {
                    wrap.style.bottom = offset;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            applyResponsiveLayout();
            document.body.appendChild(wrap);

            var resizeTimer;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(applyResponsiveLayout, 150);
            });

            var idx = 0;
            showNext();

            function trackClick(postId) {
                try {
                    var data = new URLSearchParams();
                    data.append('action', 'vp_track_click');
                    data.append('post_id', postId);
                    data.append('nonce', settings.trackNonce);
                    if (navigator.sendBeacon) {
                        var blob = new Blob([data.toString()], { type: 'application/x-www-form-urlencoded' });
                        navigator.sendBeacon(settings.ajaxUrl, blob);
                    } else {
                        fetch(settings.ajaxUrl, { method: 'POST', body: data, keepalive: true });
                    }
                } catch (e) { /* Groot Vision: fail silently, never block UX */ }
            }

            function showNext() {
                if (!items.length) return;
                var data = items[idx % items.length];
                idx++;

                var initial = (data.name || '?').trim().charAt(0).toUpperCase();
                var durationMs = settings.displaySeconds * 1000;

                var card = document.createElement('div');
                card.className = 'vp-notice-card';
                card.innerHTML =
                    '<button class="vp-notice-close" aria-label="بستن" type="button">&times;</button>' +
                    '<div class="vp-notice-avatar-wrap">' +
                        '<div class="vp-notice-avatar">' + escapeHtml(initial) + '</div>' +
                        '<div class="vp-notice-badge"><svg viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#fff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>' +
                    '</div>' +
                    '<div class="vp-notice-body">' +
                        '<p class="vp-notice-title"><b>' + escapeHtml(data.name) + '</b>' + (data.city ? ' از ' + escapeHtml(data.city) : '') + '</p>' +
                        '<p class="vp-notice-sub">' + escapeHtml(data.product) + ' رو خرید کرد</p>' +
                        '<p class="vp-notice-time">' + data.minutes + ' دقیقه پیش</p>' +
                    '</div>' +
                    '<div class="vp-notice-progress"><div class="vp-notice-progress-bar"></div></div>';

                card.addEventListener('click', function (e) {
                    if (e.target.closest('.vp-notice-close')) return;
                    trackClick(data.id);
                    if (data.link) window.open(data.link, '_blank');
                });

                var closeBtn = card.querySelector('.vp-notice-close');
                var dismissed = false;
                closeBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    dismiss();
                });

                wrap.innerHTML = '';
                wrap.appendChild(card);
                var bar = card.querySelector('.vp-notice-progress-bar');
                var remaining = durationMs;
                var startedAt = Date.now();
                var hideTimer;

                requestAnimationFrame(function () {
                    card.classList.add('vp-show');
                    requestAnimationFrame(function () {
                        bar.style.transition = 'transform ' + remaining + 'ms linear';
                        bar.style.transform = 'scaleX(0)';
                    });
                });

                startTimer();

                function startTimer() {
                    startedAt = Date.now();
                    hideTimer = setTimeout(dismiss, remaining);
                }

                function pauseTimer() {
                    clearTimeout(hideTimer);
                    remaining -= (Date.now() - startedAt);
                    if (remaining < 300) remaining = 300;
                    bar.style.transition = 'none';
                    bar.style.transform = 'scaleX(' + getScaleX(bar) + ')';
                }

                function resumeTimer() {
                    startTimer();
                    requestAnimationFrame(function () {
                        bar.style.transition = 'transform ' + remaining + 'ms linear';
                        bar.style.transform = 'scaleX(0)';
                    });
                }

                card.addEventListener('mouseenter', pauseTimer);
                card.addEventListener('mouseleave', resumeTimer);

                function getScaleX(el) {
                    var t = getComputedStyle(el).transform;
                    if (t === 'none') return 1;
                    var m = t.match(/matrix\\(([^)]+)\\)/);
                    if (!m) return 1;
                    return parseFloat(m[1].split(',')[0]) || 0;
                }

                function dismiss() {
                    if (dismissed) return;
                    dismissed = true;
                    clearTimeout(hideTimer);
                    card.classList.remove('vp-show');
                    setTimeout(function () {
                        var wait = randBetween(settings.minInterval, settings.maxInterval) * 1000;
                        setTimeout(showNext, wait);
                    }, 400);
                }
            }

            function randBetween(min, max) {
                min = Math.max(1, min); max = Math.max(min, max);
                return Math.floor(Math.random() * (max - min + 1)) + min;
            }
            function escapeHtml(str) {
                if (!str) return '';
                return String(str).replace(/[&<>"']/g, function (m) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
                });
            }
        });
    })();
    JS;
}