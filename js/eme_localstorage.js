jQuery(document).ready( function($) {
	if ($("form.eme-rememberme input#eme_rememberme").length) {
		let eme_rememberme_checked = localStorage.getItem('eme_rememberme');
		if (eme_rememberme_checked == 1) {
			$("form.eme-rememberme input#eme_rememberme").prop("checked",true);
		}
	}
        if ($('form.eme-rememberme input[name=lastname]').length && $('form.eme-rememberme input[name=lastname]').val() == '') {
                $('form.eme-rememberme input[name=lastname]').val(localStorage.getItem('eme_lastname'));
        }
        if ($('form.eme-rememberme input[name=firstname]').length && $('form.eme-rememberme input[name=firstname]').val() == '') {
                $('form.eme-rememberme input[name=firstname]').val(localStorage.getItem('eme_firstname'));
        }
        if ($('form.eme-rememberme input[name=email]').length && $('form.eme-rememberme input[name=email]').val() == '') {
                $('form.eme-rememberme input[name=email]').val(localStorage.getItem('eme_email'));
        }
        if ($('form.eme-rememberme input[name=phone]').length && $('form.eme-rememberme input[name=phone]').val() == '') {
                $('form.eme-rememberme input[name=phone]').val(localStorage.getItem('eme_phone'));
        }
	if ($('form.eme-rememberme input[name=task_lastname]').length) {
		$('form.eme-rememberme input[name=task_lastname]').val(localStorage.getItem('eme_lastname'));
	}
	if ($('form.eme-rememberme input[name=task_firstname]').length) {
		$('form.eme-rememberme input[name=task_firstname]').val(localStorage.getItem('eme_firstname'));
	}
	if ($('form.eme-rememberme input[name=task_email]').length) {
		$('form.eme-rememberme input[name=task_email]').val(localStorage.getItem('eme_email'));
	}
	if ($('form.eme-rememberme input[name=task_phone]').length) {
		$('form.eme-rememberme input[name=task_phone]').val(localStorage.getItem('eme_phone'));
	}
	$('form.eme-rememberme').on('submit',function(){
		if ($('input#eme_rememberme', this).prop('checked')) {
			localStorage.setItem('eme_rememberme',1);
			if ($('input[name=lastname]', this).length) {
				localStorage.setItem('eme_lastname',$('input[name=lastname]', this).val());
			}
			if ($('input[name=firstname]', this).length) {
				localStorage.setItem('eme_firstname',$('input[name=firstname]', this).val());
			}
			if ($('input[name=email]', this).length) {
				localStorage.setItem('eme_email',$('input[name=email]', this).val());
			}
			if ($('input[name=phone]', this).length) {
				localStorage.setItem('eme_phone',$('input[name=phone]', this).val());
			}
			if ($('input[name=task_lastname]', this).length) {
				localStorage.setItem('eme_lastname',$('input[name=task_lastname]', this).val());
			}
			if ($('input[name=task_firstname]', this).length) {
				localStorage.setItem('eme_firstname',$('input[name=task_firstname]', this).val());
			}
			if ($('input[name=task_email]', this).length) {
				localStorage.setItem('eme_email',$('input[name=task_email]', this).val());
			}
			if ($('input[name=task_phone]', this).length) {
				localStorage.setItem('eme_phone',$('input[name=task_phone]', this).val());
			}
		} else {
			localStorage.removeItem('eme_lastname');
			localStorage.removeItem('eme_firstname');
			localStorage.removeItem('eme_email');
			localStorage.removeItem('eme_phone');
			localStorage.removeItem('eme_rememberme');
		}
	});
});
