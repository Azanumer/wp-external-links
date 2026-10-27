<?php
/**
 * Plugin Name: External Links
 * Description: Makes external links in post content open safely in a new tab — rel="noopener noreferrer", optional icon, per-domain exclusions.
 * Version: 1.0.0
 * Author: Azan Umer
 * License: MIT
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WEL_OPTION', 'wel_settings');

function wel_defaults() {
    return array(
        'add_nofollow'   => 0,
        'excluded_hosts' => '',
        'add_icon'       => 0,
    );
}

function wel_get_settings() {
    return wp_parse_args(get_option(WEL_OPTION, array()), wel_defaults());
}

/** Hosts listed one per line in settings are left untouched. Filter: wel_excluded_hosts. */
function wel_excluded_hosts() {
    $settings = wel_get_settings();
    $hosts = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $settings['excluded_hosts'])));
    return array_map('strtolower', apply_filters('wel_excluded_hosts', $hosts));
}

function wel_is_external($href) {
    if ($href === '' || $href[0] === '#' || stripos($href, 'mailto:') === 0 || stripos($href, 'tel:') === 0 || stripos($href, 'javascript:') === 0) {
        return false;
    }
    $host = strtolower((string) parse_url($href, PHP_URL_HOST));
    if ($host === '') {
        return false; // relative link — internal by definition
    }
    $site = strtolower((string) parse_url(home_url(), PHP_URL_HOST));
    if ($host === $site || substr($host, -strlen('.' . $site)) === '.' . $site) {
        return false; // same domain or subdomain
    }
    return !in_array($host, wel_excluded_hosts(), true);
}

/** Rewrite external <a> tags in rendered content. Existing target/rel attributes are respected, not clobbered. */
function wel_process_links($content) {
    if (stripos($content, '<a') === false) {
        return $content;
    }
    $settings = wel_get_settings();
    return preg_replace_callback(
        '/<a\s+([^>]*?)href=(["\'])([^"\']+)\2([^>]*)>/i',
        function ($m) use ($settings) {
            if (!wel_is_external($m[3])) {
                return $m[0];
            }
            $before = $m[1];
            $after = $m[4];
            $attrs = $before . ' ' . $after;
            if (stripos($attrs, 'target=') === false) {
                $after .= ' target="_blank"';
            }
            if (stripos($attrs, 'rel=') === false) {
                $rel = 'noopener noreferrer';
                if (!empty($settings['add_nofollow'])) {
                    $rel .= ' nofollow';
                }
                $after .= ' rel="' . $rel . '"';
            }
            $prefix = trim($before) !== '' ? trim($before) . ' ' : '';
            $tag = '<a ' . $prefix . 'href=' . $m[2] . $m[3] . $m[2] . $after . '>';
            return $tag;
        },
        $content
    );
}
add_filter('the_content', 'wel_process_links');
add_filter('comment_text', 'wel_process_links');

/** Optional CSS icon on external links (class-based so themes can restyle it). */
function wel_icon_css() {
    if (empty(wel_get_settings()['add_icon'])) {
        return;
    }
    echo '<style>a[target="_blank"]::after{content:"↗";font-size:.8em;margin-left:2px;}</style>';
}
add_action('wp_head', 'wel_icon_css');

function wel_register_settings() {
    register_setting('wel_group', WEL_OPTION, array(
        'sanitize_callback' => function ($input) {
            $out = wel_defaults();
            $out['add_nofollow'] = !empty($input['add_nofollow']) ? 1 : 0;
            $out['add_icon'] = !empty($input['add_icon']) ? 1 : 0;
            $out['excluded_hosts'] = sanitize_textarea_field($input['excluded_hosts'] ?? '');
            return $out;
        },
    ));
    add_options_page('External Links', 'External Links', 'manage_options', 'wel-settings', 'wel_render_settings');
}
add_action('admin_menu', 'wel_register_settings');

function wel_render_settings() {
    $s = wel_get_settings();
    ?>
    <div class="wrap">
        <h1>External Links</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wel_group'); ?>
            <table class="form-table">
                <tr><th>Add nofollow</th><td><input type="checkbox" name="<?php echo esc_attr(WEL_OPTION); ?>[add_nofollow]" value="1" <?php checked($s['add_nofollow'], 1); ?>></td></tr>
                <tr><th>External-link icon</th><td><input type="checkbox" name="<?php echo esc_attr(WEL_OPTION); ?>[add_icon]" value="1" <?php checked($s['add_icon'], 1); ?>></td></tr>
                <tr><th>Excluded hosts</th><td><textarea name="<?php echo esc_attr(WEL_OPTION); ?>[excluded_hosts]" rows="4" cols="40"><?php echo esc_textarea($s['excluded_hosts']); ?></textarea><p class="description">One host per line, e.g. partner-site.com</p></td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
