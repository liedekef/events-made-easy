// Don't wait for DOM ready.
let now = new Date();

fetch(emeclock.translate_ajax_url, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        action: 'eme_client_clock',
        client_unixtime: Math.round(now.getTime() / 1000), // make seconds
        client_seconds: now.getSeconds(),
        client_minutes: now.getMinutes(),
        client_hours: now.getHours(),
        client_wday: now.getDay(),
        client_mday: now.getDate(),
        client_month: now.getMonth() + 1, // make 1-12
        client_fullyear: now.getFullYear()
    })
})
.then(response => response.text())
.then(ret => {
    if (ret == '1') {
        // we refresh if the cookie is actually there
        // people can refuse the cookie ...
        if (document.cookie.indexOf('eme_client_time') != -1) {
            top.location.href = self.location.href;
        }
    }
})
.catch(error => {
    console.error('Error:', error);
});
