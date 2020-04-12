$('#resetpassword').formValidation({
	message: 'This value is not valid',
        icon: {
           
        },
	fields: {
	    password: {
            validators: {
			    notEmpty: {
				message: "<?php echo __('Please enter password.'); ?>"
			    },
			    regexp: {
                    regexp: "^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9]).{6,}$",
                    message: "<?php echo __('Your password must be 6-20 characters with 1 uppercase, 1 lowercase and 1 number.'); ?>"
                },
    			stringLength: {
	                min: 6,
	                max: 30,
	                message: "<?php echo __('Password must be more than 6 and less than 30 characters only.'); ?>"
	            },
	      	}
        },
        confirm_password: {
            enabled: true,
            validators: {
                notEmpty: {
                    message: "<?php echo __('Please enter confirm password.'); ?>"
                },
                identical: {
                    field: 'password',
                    message: "<?php echo __('Password and confirm password should be same.'); ?>"
                },
            }
        }
	}
});

$('#contactus').formValidation({
	message: 'This value is not valid',
        icon: {
           
        },
	fields: {
	    subject: {
            validators: {
			    notEmpty: {
				message: "<?php echo __('Please enter subject.'); ?>"
			    },
	      	}
        },
        message: {
            validators: {
			    notEmpty: {
				message: "<?php echo __('Please enter message.'); ?>"
			    },
	      	}
        }
	}
});