<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
delete_option('wel_settings');
