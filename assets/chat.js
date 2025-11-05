jQuery(function ($) {
    const $b = $('#lc-bubble');
    const $c = $('#lc-chat');
    const $m = $('#lc-messages');
    const $i = $('#lc-text');
    const $s = $('#lc-send');
    const $x = $('#lc-close');
    let first = true;

    // STARTET GESCHLOSSEN!
    $c.addClass('closed');

    $b.on('click', () => {
        $c.toggleClass('closed');
        if (first) { setTimeout(welcome, 400); first = false; }
        setTimeout(() => $i.focus(), 500);
    });

    $x.on('click', () => $c.addClass('closed'));
    $s.on('click', send);
    $i.on('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            send();
        }
    });

    function welcome() {
        $m.html('<div class="bot">' + lc.welcome + '</div>');
        scroll();
    }

    function send() {
        let msg = $i.val().trim();
        if (!msg) return;
        $m.append('<div class="user">Du: ' + msg + '</div>');
        $i.val(''); scroll();

        $.post(lc.ajax, {
            action: 'lc_chat',
            msg: msg,
            nonce: lc.nonce
        }, r => {
            let text = r.success ? r.data : 'Fehler';
            text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" rel="noopener" style="color:#0073aa; text-decoration:underline;">$1</a>');
            $m.append('<div class="bot">' + text + '</div>');
            scroll();
        });
    }

    function scroll() { $m.scrollTop($m[0].scrollHeight); }

    setInterval(() => $b.toggleClass('pulse'), 3000);
});
