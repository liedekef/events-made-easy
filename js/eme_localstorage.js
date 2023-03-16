jQuery(document).ready( function($) {
	if ($("input#eme_rememberme").length) {
		var eme_rememberme_checked = localStorage.getItem('eme_rememberme');
		if (eme_rememberme_checked == 1) {
			$("input#eme_rememberme").prop("checked",true);
		}
	}
	if ($('input[name=lastname]').length) {
		$('input[name=lastname]').val(localStorage.getItem('eme_lastname'));
	}
	if ($('input[name=firstname]').length) {
		$('input[name=firstname]').val(localStorage.getItem('eme_firstname'));
	}
	if ($('input[name=email]').length) {
		$('input[name=email]').val(localStorage.getItem('eme_email'));
	}
	if ($('input[name=phone]').length) {
		$('input[name=phone]').val(localStorage.getItem('eme_phone'));
	}
	if ($('input[name=task_lastname]').length) {
		$('input[name=task_lastname]').val(localStorage.getItem('eme_lastname'));
	}
	if ($('input[name=task_firstname]').length) {
		$('input[name=task_firstname]').val(localStorage.getItem('eme_firstname'));
	}
	if ($('input[name=task_email]').length) {
		$('input[name=task_email]').val(localStorage.getItem('eme_email'));
	}
	if ($('input[name=task_phone]').length) {
		$('input[name=task_phone]').val(localStorage.getItem('eme_phone'));
	}
	$('[name=eme-rsvp-form]').on('submit', function(event) {
		event.preventDefault();
	});
	$('[name=eme-member-form]').on('submit', function(event) {
		event.preventDefault();
	});
	$(document).on('submit','form.eme-rememberme',function(){
		if ($('input#eme_rememberme').prop('checked')) {
			localStorage.setItem('eme_rememberme',1);
			if ($('input[name=lastname]').length) {
				localStorage.setItem('eme_lastname',$('input[name=lastname]').val());
			}
			if ($('input[name=firstname]').length) {
				localStorage.setItem('eme_firstname',$('input[name=firstname]').val());
			}
			if ($('input[name=email]').length) {
				localStorage.setItem('eme_email',$('input[name=email]').val());
			}
			if ($('input[name=phone]').length) {
				localStorage.setItem('eme_phone',$('input[name=phone]').val());
			}
			if ($('input[name=task_lastname]').length) {
				localStorage.setItem('eme_lastname',$('input[name=task_lastname]').val());
			}
			if ($('input[name=task_firstname]').length) {
				localStorage.setItem('eme_firstname',$('input[name=task_firstname]').val());
			}
			if ($('input[name=task_email]').length) {
				localStorage.setItem('eme_email',$('input[name=task_email]').val());
			}
			if ($('input[name=task_phone]').length) {
				localStorage.setItem('eme_phone',$('input[name=task_phone]').val());
			}
		} else {
			localStorage.removeItem('eme_lastname');
			localStorage.removeItem('eme_firstname');
			localStorage.removeItem('eme_email');
			localStorage.removeItem('eme_rememberme');
		}
	});
});
