<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_options_page('LibreChat Chatbot', 'LibreChat Chatbot', 'manage_options', 'lc', 'lc_page');
});

function lc_page() {
    if ($_POST['save']) {
        update_option('lc_welcome', wp_kses_post($_POST['welcome']));
        echo '<div class="notice notice-success"><p>Gespeichert!</p></div>';
    }
    if ($_POST['crawl']) {
        $count = librechat_crawl();
        echo '<div class="notice notice-success"><p>✔ ' . $count . ' Seiten NEU gecrawlt!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>LibreChat Chatbot</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Willkommensnachricht</th>
                    <td><textarea name="welcome" rows="4" class="large-text"><?= esc_textarea(get_option('lc_welcome', "Hallo! Ich bin dein KI-Assistent.\nFrag mich alles über diese Website! 😊")) ?></textarea></td>
                </tr>
            </table>
            <?php submit_button('Speichern', 'primary', 'save'); ?>
        </form>
        <hr>
        <form method="post">
            <?php submit_button('🔄 Jetzt NEU crawlen', 'secondary', 'crawl'); ?>
        </form>
    </div>
    <?php
}
