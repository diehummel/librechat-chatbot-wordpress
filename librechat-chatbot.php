<?php
/**
 * Plugin Name: LibreChat Chatbot
 * Plugin URI: https://github.com/diehummel/librechat-chatbot-wordpress
 * Description: Dein lokaler KI-Assistent mit LibreChat auf Port 3080
 * Version: 1.0
 * Author: diehummel
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) exit;

define('LCB_URL', plugin_dir_url(__FILE__));
define('LCB_PATH', plugin_dir_path(__FILE__));

require_once LCB_PATH . 'includes/admin.php';
require_once LCB_PATH . 'includes/frontend.php';

add_action('wp_enqueue_scripts', function () {
    if (is_admin()) return;
    wp_enqueue_script('lc-js', LCB_URL . 'assets/chat.js', ['jquery'], '2.0', true);
    wp_enqueue_style('lc-css', LCB_URL . 'assets/style.css', [], '2.0');
    wp_localize_script('lc-js', 'lc', [
        'ajax'    => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('lc'),
        'welcome' => nl2br(esc_html(get_option('lc_welcome', "Hallo! Ich bin dein KI-Assistent.\nFrag mich alles über diese Website! 😊")))
    ]);
});

add_action('wp_footer', function () {
    if (is_admin()) return; ?>
    <div id="lc-bubble">💬</div>
    <div id="lc-chat" class="closed">
        <div id="lc-header">LibreChat Assistent <span id="lc-close">✕</span></div>
        <div id="lc-messages"></div>
        <div id="lc-input">
            <input type="text" id="lc-text" placeholder="Deine Frage…">
            <button id="lc-send">➤</button>
        </div>
    </div>
<?php });
