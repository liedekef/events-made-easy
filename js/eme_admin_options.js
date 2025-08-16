document.addEventListener('DOMContentLoaded', function() {
    function updateShowHideCaptcha() {
        const recaptchaEl = EME.$('input[name=eme_recaptcha_for_forms]');
        if (recaptchaEl) {
            if (recaptchaEl.checked) {
                eme_toggle(EME.$('#eme_recaptcha_site_key_row'), true);
                eme_toggle(EME.$('#eme_recaptcha_secret_key_row'), true);
                const siteKeyEl = EME.$('#eme_recaptcha_site_key');
                const secretKeyEl = EME.$('#eme_recaptcha_secret_key');
                if (siteKeyEl) siteKeyEl.required = true;
                if (secretKeyEl) secretKeyEl.required = true;
            } else {
                eme_toggle(EME.$('#eme_recaptcha_site_key_row'), false);
                eme_toggle(EME.$('#eme_recaptcha_secret_key_row'), false);
                const siteKeyEl = EME.$('#eme_recaptcha_site_key');
                const secretKeyEl = EME.$('#eme_recaptcha_secret_key');
                if (siteKeyEl) siteKeyEl.required = false;
                if (secretKeyEl) secretKeyEl.required = false;
            }
        }
        
        const cfcaptchaEl = EME.$('input[name=eme_cfcaptcha_for_forms]');
        if (cfcaptchaEl) {
            if (cfcaptchaEl.checked) {
                eme_toggle(EME.$('#eme_cfcaptcha_site_key_row'), true);
                eme_toggle(EME.$('#eme_cfcaptcha_secret_key_row'), true);
                const siteKeyEl = EME.$('#eme_cfcaptcha_site_key');
                const secretKeyEl = EME.$('#eme_cfcaptcha_secret_key');
                if (siteKeyEl) siteKeyEl.required = true;
                if (secretKeyEl) secretKeyEl.required = true;
            } else {
                eme_toggle(EME.$('#eme_cfcaptcha_site_key_row'), false);
                eme_toggle(EME.$('#eme_cfcaptcha_secret_key_row'), false);
                const siteKeyEl = EME.$('#eme_cfcaptcha_site_key');
                const secretKeyEl = EME.$('#eme_cfcaptcha_secret_key');
                if (siteKeyEl) siteKeyEl.required = false;
                if (secretKeyEl) secretKeyEl.required = false;
            }
        }
        
        const hcaptchaEl = EME.$('input[name=eme_hcaptcha_for_forms]');
        if (hcaptchaEl) {
            if (hcaptchaEl.checked) {
                eme_toggle(EME.$('#eme_hcaptcha_site_key_row'), true);
                eme_toggle(EME.$('#eme_hcaptcha_secret_key_row'), true);
                const siteKeyEl = EME.$('#eme_hcaptcha_site_key');
                const secretKeyEl = EME.$('#eme_hcaptcha_secret_key');
                if (siteKeyEl) siteKeyEl.required = true;
                if (secretKeyEl) secretKeyEl.required = true;
            } else {
                eme_toggle(EME.$('#eme_hcaptcha_site_key_row'), false);
                eme_toggle(EME.$('#eme_hcaptcha_secret_key_row'), false);
                const siteKeyEl = EME.$('#eme_hcaptcha_site_key');
                const secretKeyEl = EME.$('#eme_hcaptcha_secret_key');
                if (siteKeyEl) siteKeyEl.required = false;
                if (secretKeyEl) secretKeyEl.required = false;
            }
        }
        
        const friendlycaptchaEl = EME.$('input[name=eme_friendlycaptcha_for_forms]');
        if (friendlycaptchaEl) {
            if (friendlycaptchaEl.checked) {
                eme_toggle(EME.$('#eme_friendlycaptcha_site_key_row'), true);
                eme_toggle(EME.$('#eme_friendlycaptcha_secret_key_row'), true);
                const siteKeyEl = EME.$('#eme_friendlycaptcha_site_key');
                const secretKeyEl = EME.$('#eme_friendlycaptcha_secret_key');
                if (siteKeyEl) siteKeyEl.required = true;
                if (secretKeyEl) secretKeyEl.required = true;
            } else {
                eme_toggle(EME.$('#eme_friendlycaptcha_site_key_row'), false);
                eme_toggle(EME.$('#eme_friendlycaptcha_secret_key_row'), false);
                const siteKeyEl = EME.$('#eme_friendlycaptcha_site_key');
                const secretKeyEl = EME.$('#eme_friendlycaptcha_secret_key');
                if (siteKeyEl) siteKeyEl.required = false;
                if (secretKeyEl) secretKeyEl.required = false;
            }
        }
    }
    
    function updateShowHideMailQueueOptions() {
        const queueMailsEl = EME.$('input[name=eme_queue_mails]');
        if (!queueMailsEl) return;
        
        if (queueMailsEl.checked) {
            eme_toggle(EME.$('#eme_queued_mails_options_row'), true);
            eme_toggle(EME.$('#eme_mail_sleep_row'), true);
        } else {
            eme_toggle(EME.$('#eme_queued_mails_options_row'), false);
            eme_toggle(EME.$('#eme_mail_sleep_row'), false);
        }
    }
    
    function updateShowHideRsvpMailNotify() {
        const checkedEl = EME.$('input[name=eme_rsvp_mail_notify_is_active]');
        if (!checkedEl) return; // Exit early if element doesn't exist
        
        if (checkedEl.checked) {
            eme_toggle(EME.$('table#rsvp_mail_notify-data'), true);
        } else {
            eme_toggle(EME.$('table#rsvp_mail_notify-data'), false);
        }
    }

    function updateShowHideMailSendMethod() {
        const mailMethodEl = EME.$('select[name=eme_mail_send_method]');
        if (!mailMethodEl) return;
        
        if (mailMethodEl.value == 'smtp') {
            eme_toggle(EME.$('#eme_smtp_host_row'), true);
            eme_toggle(EME.$('#eme_smtp_port_row'), true);
            eme_toggle(EME.$('#eme_smtp_auth_row'), true);
            eme_toggle(EME.$('#eme_smtp_username_row'), true);
            eme_toggle(EME.$('#eme_smtp_password_row'), true);
            eme_toggle(EME.$('#eme_smtp_encryption_row'), true);
            eme_toggle(EME.$('#eme_smtp_debug_row'), true);
            eme_toggle(EME.$('#eme_smtp_verify_cert_row'), true);
        } else {
            eme_toggle(EME.$('#eme_smtp_host_row'), false);
            eme_toggle(EME.$('#eme_smtp_port_row'), false);
            eme_toggle(EME.$('#eme_smtp_auth_row'), false);
            eme_toggle(EME.$('#eme_smtp_username_row'), false);
            eme_toggle(EME.$('#eme_smtp_password_row'), false);
            eme_toggle(EME.$('#eme_smtp_encryption_row'), false);
            eme_toggle(EME.$('#eme_smtp_debug_row'), false);
            eme_toggle(EME.$('#eme_smtp_verify_cert_row'), false);
        }
    }

    function updateShowHideSMTPAuth() {
        const smtpAuthEl = EME.$('input[name=eme_smtp_auth]');
        const mailMethodEl = EME.$('select[name=eme_mail_send_method]');
        if (!smtpAuthEl || !mailMethodEl) return;
        
        if (smtpAuthEl.checked && mailMethodEl.value == 'smtp') {
            eme_toggle(EME.$('#eme_smtp_username_row'), true);
            eme_toggle(EME.$('#eme_smtp_password_row'), true);
        } else {
            eme_toggle(EME.$('#eme_smtp_username_row'), false);
            eme_toggle(EME.$('#eme_smtp_password_row'), false);
        }
    }

    function updateShowHideSMTPCert() {
        const smtpEncryptionEl = EME.$('select[name=eme_smtp_encryption]');
        const mailMethodEl = EME.$('select[name=eme_mail_send_method]');
        if (!smtpEncryptionEl || !mailMethodEl) return;
        
        if (smtpEncryptionEl.value != 'none' && mailMethodEl.value == 'smtp') {
            eme_toggle(EME.$('#eme_smtp_verify_cert_row'), true);
        } else {
            eme_toggle(EME.$('#eme_smtp_verify_cert_row'), false);
        }
    }

    // for the eme-options pages
    updateShowHideCaptcha();
    updateShowHideRsvpMailNotify();
    updateShowHideMailSendMethod();
    updateShowHideSMTPAuth();
    updateShowHideSMTPCert();
    updateShowHideMailQueueOptions();
    
    // Add event listeners with null checks
    const recaptchaEl = EME.$('input[name=eme_recaptcha_for_forms]');
    if (recaptchaEl) recaptchaEl.addEventListener("change", updateShowHideCaptcha);
    
    const hcaptchaEl = EME.$('input[name=eme_hcaptcha_for_forms]');
    if (hcaptchaEl) hcaptchaEl.addEventListener("change", updateShowHideCaptcha);
    
    const cfcaptchaEl = EME.$('input[name=eme_cfcaptcha_for_forms]');
    if (cfcaptchaEl) cfcaptchaEl.addEventListener("change", updateShowHideCaptcha);
    
    const friendlycaptchaEl = EME.$('input[name=eme_friendlycaptcha_for_forms]');
    if (friendlycaptchaEl) friendlycaptchaEl.addEventListener("change", updateShowHideCaptcha);
    
    const rsvpMailEl = EME.$('input[name=eme_rsvp_mail_notify_is_active]');
    if (rsvpMailEl) rsvpMailEl.addEventListener("change", updateShowHideRsvpMailNotify);
    
    const mailMethodEl = EME.$('select[name=eme_mail_send_method]');
    if (mailMethodEl) mailMethodEl.addEventListener("change", updateShowHideMailSendMethod);
    
    const smtpAuthEl = EME.$('input[name=eme_smtp_auth]');
    if (smtpAuthEl) smtpAuthEl.addEventListener("change", updateShowHideSMTPAuth);
    
    const smtpEncryptionEl = EME.$('select[name=eme_smtp_encryption]');
    if (smtpEncryptionEl) smtpEncryptionEl.addEventListener("change", updateShowHideSMTPCert);
    
    const queueMailsEl = EME.$('input[name=eme_queue_mails]');
    if (queueMailsEl) queueMailsEl.addEventListener("change", updateShowHideMailQueueOptions);
});
