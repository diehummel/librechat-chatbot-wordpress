<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_lc_chat', 'lc_chat');
add_action('wp_ajax_nopriv_lc_chat', 'lc_chat');

function lc_chat() {
    check_ajax_referer('lc', 'nonce');
    $msg = sanitize_text_field($_POST['msg']);

    // 1. Seiten laden
    $site = get_option('lc_site', []);
    if (empty($site)) { lc_crawl(); $site = get_option('lc_site', []); }

    // 2. Beste lokale Seite
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

    // 3. Prompt
    $system = "Du bist ein schlanker Website-Assistent.\n";
    if ($best_score > 30) {
        $system .= "Lokaler Artikel: \"$best_title\"\n$best_url\n\n";
    }
    $system .= "Antworte kurz. Frage: $msg";

    $res = wp_remote_post('http://localhost:3080/v1/chat/completions', [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => json_encode([
            'model'       => 'gpt-4o-mini',   // <-- LibreChat Standard!
            'messages'    => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $msg]
            ],
            'temperature' => 0.7,
            'stream'      => false
        ]),
        'timeout' => 90
    ]);

    // ECHTE FEHLERMELDUNG!
    if (is_wp_error($res)) {
        wp_send_json_error('LibreChat offline: ' . $res->get_error_message());
        return;
    }

    $code = wp_remote_retrieve_response_code($res);
    $body = wp_remote_retrieve_body($res);

    if ($code !== 200) {
        wp_send_json_error("LibreChat sagt (Code $code):<br><pre>$body</pre>");
        return;
    }

    $json = json_decode($body, true);
    if (!$json || !isset($json['choices'][0]['message']['content'])) {
        wp_send_json_error("Falsches JSON:<br><pre>" . print_r($json, true) . "</pre>");
        return;
    }

    $answer = $json['choices'][0]['message']['content'];

    // Links klickbar
    $answer = preg_replace(
        '/(https?:\/\/[^\s\)]+)/',
        '<a href="$1" target="_blank" rel="noopener" style="color:#0073aa; text-decoration:underline;">$1</a>',
        $answer
    );

    wp_send_json_success($answer);
}

function lc_crawl() {
    $posts = get_posts([
        'numberposts' => -1,
        'post_status' => ['publish', 'private'],
        'post_type'   => 'any'
    ]);

    $data = [];
    foreach ($posts as $p) {
        $data[] = [
            'id'      => $p->ID,
            'title'   => $p->post_title,
            'content' => wp_strip_all_tags($p->post_content)
        ];
    }
    update_option('lc_site', $data);
    return count($data);
}
?>
