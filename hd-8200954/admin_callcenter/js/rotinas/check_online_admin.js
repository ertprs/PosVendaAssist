$(document).ready(function() {
	check_admin();
});

//alterar tambem em helpdesk/js/rotinas/check_online_admin.js
function check_admin(){
	$.ajax({
		type: "POST",
		url: "login_admin_check_online.php",
		cache: false,
		data: { url: window.location.href},
		success: function(data){

			if(data == '1'){
				setTimeout(function(){
                	check_admin();
            	}, 20000); //20s	
			}

			if(data == '0'){
				window.location.href = "login_session_invalid.php";
			}

		}
	});




}