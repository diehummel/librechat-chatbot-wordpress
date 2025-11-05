<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_lc_chat', 'lc_chat');
add_action('wp_ajax_nopriv_lc_chat', 'lc_chat');

function lc_chat() {
    check_ajax_referer('lc', 'nonce');
    $msg = sanitize_text_field($_POST['msg']);

    $site = get_option('lc_site', []);
    if (empty($site)) { librechat_crawl(); $site = get_option('lc_site', []); }

    // Beste lokale Seite
    $words = preg_split('/\s+/', strtolower($msg));
    $best_url = $best_title = '';
    $best_score = 0;

    foreach ($site as $p) {
        $text = strtolower($p['title'] . ' ' . $p['content']);
        $score = 0;
        foreach ($words as $w) {
            if (strlen($w) < 3) continue;
            $score += substr_count($text, $w) * 10;
            if (stripos($p['title'], $w) !== false) $score += 100;
        }
        if ($score > $best_score) {
            $best_score = $score;
            $best_url   = get_permalink($p['id']) ?: '#';
            $best_title = $p['title'];
        }
    }

    // Prompt
    $system = "Du bist ein schlanker Website-Assistent.\n";
    if ($best_score > 30) {
        $system .= "Lokaler Artikel: \"$best_title\"\n$best_url\n\n";
    }
    $system .= "Antworte kurz und ehrlich. Frage: $msg";

    // LibreChat API (localhost:3008)
    $res = wp_remote_post('http://localhost:3080/v1/chat/completions', [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $msg]
            ],
            'temperature' => 0.7
        ]),
        'timeout' => 90
    ]);

    if (is_wp_error($res)) { wp_send_json_error('LibreChat offline?'); }
    $code = wp_remote_retrieve_response_code($res);
    $body = wp_remote_retrieve_body($res);
    if ($code !== 200) { wp_send_json_error("LibreChat (Code $code): $body"); }

    $json = json_decode($body, true);
    $answer = $json['choices'][0]['message']['content'] ?? 'Oops';

    // Links klickbar
    $answer = preg_replace(
        '/(https?:\/\/[^\s\)]+)/',
        '<a href="$1" target="_blank" rel="noopener" style="color:#0073aa; text-decoration:underline;">$1</a>',
        $answer
    );

    wp_send_json_success($answer);
}

function librechat_crawl() {
    $posts = get_posts([
        'numberposts' => -1,
        'post_status' => ['publish', 'private'],
        'post_type' => 'any'
    ]);

    $data = [];
    foreach ($posts as $p) {
        $data[] = [
            'id' => $p->ID,
            'title' => $p->post_title,
            'content' => wp_strip_all_tags($p->post_content)
        ];
    }
    update_option('lc_site', $data);
    return count($data);
}
?>
