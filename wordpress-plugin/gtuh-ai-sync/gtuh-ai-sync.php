<?php
/**
 * Plugin Name: GTUH AI Sync
 * Description: Syncs WooCommerce products, blog posts and pages to the GTUH AI knowledge platform (manual "Sync now"). Re-syncs upsert by external_id, so data stays fresh without duplicates.
 * Version: 1.0.0
 * Author: GTUH AI
 * License: MIT
 */

if (! defined('ABSPATH')) {
    exit; // no direct access
}

const GTUH_AI_OPTION = 'gtuh_ai_settings';
const GTUH_AI_BATCH = 50;

/** Default settings. */
function gtuh_ai_settings(): array
{
    return wp_parse_args(get_option(GTUH_AI_OPTION, []), [
        'base_url' => '',
        'api_key' => '',
        'ds_products' => 'WooCommerce პროდუქტები',
        'ds_posts' => 'ბლოგი',
        'ds_pages' => 'გვერდები',
        'widget_key' => '',
    ]);
}

/** Admin menu. */
add_action('admin_menu', function () {
    add_menu_page('GTUH AI Sync', 'GTUH AI', 'manage_options', 'gtuh-ai', 'gtuh_ai_render_page', 'dashicons-rest-api', 80);
});

/** Fetch the tenant's widget-enabled agents (id, name, public_key, embed). */
function gtuh_ai_fetch_agents(array $s)
{
    $response = wp_remote_get($s['base_url'].'/v1/agents', [
        'timeout' => 20,
        'headers' => ['Authorization' => 'Bearer '.$s['api_key'], 'Accept' => 'application/json'],
    ]);
    if (is_wp_error($response)) {
        return $response;
    }
    $code = wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        return new WP_Error('gtuh_http', 'HTTP '.$code);
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return is_array($body['agents'] ?? null) ? $body['agents'] : [];
}

/** Auto-inject the selected widget into the site footer. */
add_action('wp_footer', function () {
    $s = gtuh_ai_settings();
    if (empty($s['widget_key']) || empty($s['base_url'])) {
        return;
    }
    echo '<script src="'.esc_url($s['base_url'].'/embed.js?key='.$s['widget_key']).'" async></script>';
});

/** Settings page + sync handling. */
function gtuh_ai_render_page(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $notice = '';

    // Save settings
    if (isset($_POST['gtuh_save']) && check_admin_referer('gtuh_ai_save')) {
        update_option(GTUH_AI_OPTION, [
            'base_url' => untrailingslashit(esc_url_raw($_POST['base_url'] ?? '')),
            'api_key' => sanitize_text_field($_POST['api_key'] ?? ''),
            'ds_products' => sanitize_text_field($_POST['ds_products'] ?? ''),
            'ds_posts' => sanitize_text_field($_POST['ds_posts'] ?? ''),
            'ds_pages' => sanitize_text_field($_POST['ds_pages'] ?? ''),
            'widget_key' => sanitize_text_field($_POST['widget_key'] ?? ''),
        ]);
        $notice = 'პარამეტრები შენახულია.';
    }

    // Run a sync
    if (isset($_POST['gtuh_sync']) && check_admin_referer('gtuh_ai_sync')) {
        $type = sanitize_text_field($_POST['gtuh_sync']);
        $notice = gtuh_ai_run_sync($type);
    }

    $s = gtuh_ai_settings();
    ?>
    <div class="wrap">
        <h1>GTUH AI Sync</h1>
        <?php if ($notice): ?><div class="notice notice-info is-dismissible"><p><?php echo esc_html($notice); ?></p></div><?php endif; ?>

        <h2>პარამეტრები</h2>
        <form method="post">
            <?php wp_nonce_field('gtuh_ai_save'); ?>
            <table class="form-table">
                <tr><th>API Base URL</th><td><input type="url" name="base_url" value="<?php echo esc_attr($s['base_url']); ?>" class="regular-text" placeholder="https://your-host"></td></tr>
                <tr><th>API Key</th><td><input type="text" name="api_key" value="<?php echo esc_attr($s['api_key']); ?>" class="regular-text" placeholder="gtuh_..."></td></tr>
                <tr><th>პროდუქტების დატასეტი</th><td><input type="text" name="ds_products" value="<?php echo esc_attr($s['ds_products']); ?>" class="regular-text"></td></tr>
                <tr><th>ბლოგის დატასეტი</th><td><input type="text" name="ds_posts" value="<?php echo esc_attr($s['ds_posts']); ?>" class="regular-text"></td></tr>
                <tr><th>გვერდების დატასეტი</th><td><input type="text" name="ds_pages" value="<?php echo esc_attr($s['ds_pages']); ?>" class="regular-text"></td></tr>
            </table>

            <h2>ჩატის ვიჯეტი საიტზე</h2>
            <p>აირჩიე რომელი აგენტის ჩატ-ვიჯეტი გამოჩნდეს მთელ საიტზე — ავტომატურად ჩაისმება (footer-ში), თემის ფაილების რედაქტირების გარეშე.</p>
            <?php
            $agents = (empty($s['base_url']) || empty($s['api_key'])) ? [] : gtuh_ai_fetch_agents($s);
            if (is_wp_error($agents)) {
                echo '<p style="color:#b32d2e">აგენტების წამოღება ვერ მოხერხდა: '.esc_html($agents->get_error_message()).' (შეინახე Base URL + API Key ჯერ).</p>';
                $agents = [];
            }
            ?>
            <label style="display:block;margin:4px 0"><input type="radio" name="widget_key" value="" <?php checked($s['widget_key'], ''); ?>> — არცერთი (გამორთული)</label>
            <?php foreach ($agents as $a): ?>
                <label style="display:block;margin:8px 0;padding:8px;border:1px solid #dcdcde;border-radius:6px;max-width:680px">
                    <input type="radio" name="widget_key" value="<?php echo esc_attr($a['public_key']); ?>" <?php checked($s['widget_key'], $a['public_key']); ?>>
                    <strong><?php echo esc_html($a['name']); ?></strong>
                    <br><code style="font-size:11px;word-break:break-all"><?php echo esc_html($a['embed']); ?></code>
                </label>
            <?php endforeach; ?>
            <?php if (empty($agents)): ?>
                <p class="description">აგენტები არ მოიძებნა. შექმენი widget-ჩართული აგენტი პლატფორმაზე და დააჭირე „შენახვა“.</p>
            <?php endif; ?>

            <p><button class="button button-primary" name="gtuh_save" value="1">შენახვა</button></p>
        </form>

        <hr>
        <h2>სინქრონიზაცია (Sync now)</h2>
        <p>თითო ღილაკი აგზავნის შესაბამის შიგთავსს. ხელახალი სინქრონი <strong>აახლებს</strong> ჩანაწერებს (external_id-ით), დუბლიკატის გარეშე.</p>
        <form method="post">
            <?php wp_nonce_field('gtuh_ai_sync'); ?>
            <?php if (function_exists('wc_get_products')): ?>
                <button class="button" name="gtuh_sync" value="products">🛒 პროდუქტების სინქი</button>
            <?php else: ?>
                <button class="button" disabled title="WooCommerce არ არის აქტიური">🛒 პროდუქტები (WooCommerce საჭიროა)</button>
            <?php endif; ?>
            <button class="button" name="gtuh_sync" value="posts">📝 ბლოგის სინქი</button>
            <button class="button" name="gtuh_sync" value="pages">📄 გვერდების სინქი</button>
        </form>
    </div>
    <?php
}

/** Run a sync for one content type; returns a status message. */
function gtuh_ai_run_sync(string $type): string
{
    $s = gtuh_ai_settings();
    if (empty($s['base_url']) || empty($s['api_key'])) {
        return 'ჯერ შეავსე Base URL და API Key.';
    }

    switch ($type) {
        case 'products': $records = gtuh_ai_collect_products(); $dataset = $s['ds_products']; break;
        case 'posts':    $records = gtuh_ai_collect_posts('post'); $dataset = $s['ds_posts']; break;
        case 'pages':    $records = gtuh_ai_collect_posts('page'); $dataset = $s['ds_pages']; break;
        default:         return 'უცნობი ტიპი.';
    }

    if (empty($records)) {
        return 'ჩასატვირთი ჩანაწერი ვერ მოიძებნა.';
    }

    $created = 0;
    $updated = 0;
    foreach (array_chunk($records, GTUH_AI_BATCH) as $batch) {
        $res = gtuh_ai_post_ingest($s, $dataset, $batch);
        if (is_wp_error($res)) {
            return 'შეცდომა: '.$res->get_error_message();
        }
        $created += (int) ($res['created'] ?? 0);
        $updated += (int) ($res['updated'] ?? 0);
    }

    return sprintf('„%s“: %d დაემატა, %d განახლდა (სულ %d).', $dataset, $created, $updated, count($records));
}

/** POST one batch to /v1/ingest. */
function gtuh_ai_post_ingest(array $s, string $dataset, array $records)
{
    $response = wp_remote_post($s['base_url'].'/v1/ingest', [
        'timeout' => 45,
        'headers' => [
            'Authorization' => 'Bearer '.$s['api_key'],
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
        'body' => wp_json_encode(['dataset' => $dataset, 'records' => $records]),
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code < 200 || $code >= 300) {
        return new WP_Error('gtuh_http', 'HTTP '.$code.': '.wp_remote_retrieve_body($response));
    }

    return is_array($body) ? $body : [];
}

/** @return array<int,array<string,mixed>> WooCommerce products as records. */
function gtuh_ai_collect_products(): array
{
    $records = [];
    $products = wc_get_products(['status' => 'publish', 'limit' => -1]);
    foreach ($products as $p) {
        $cats = wp_get_post_terms($p->get_id(), 'product_cat', ['fields' => 'names']);
        $records[] = array_filter([
            'external_id' => 'product-'.$p->get_id(),
            'name' => $p->get_name(),
            'price' => $p->get_price(),
            'sku' => $p->get_sku(),
            'category' => is_array($cats) ? implode(', ', $cats) : '',
            'url' => get_permalink($p->get_id()),
            'description' => wp_strip_all_tags($p->get_description() ?: $p->get_short_description()),
        ], fn ($v) => $v !== '' && $v !== null);
    }

    return $records;
}

/** @return array<int,array<string,mixed>> posts or pages as records. */
function gtuh_ai_collect_posts(string $postType): array
{
    $records = [];
    $posts = get_posts(['post_type' => $postType, 'post_status' => 'publish', 'numberposts' => -1]);
    foreach ($posts as $post) {
        $cats = $postType === 'post' ? wp_get_post_terms($post->ID, 'category', ['fields' => 'names']) : [];
        $records[] = array_filter([
            'external_id' => $postType.'-'.$post->ID,
            'title' => get_the_title($post),
            'category' => is_array($cats) ? implode(', ', $cats) : '',
            'date' => get_the_date('Y-m-d', $post),
            'url' => get_permalink($post),
            'text' => wp_strip_all_tags($post->post_content),
        ], fn ($v) => $v !== '' && $v !== null);
    }

    return $records;
}
