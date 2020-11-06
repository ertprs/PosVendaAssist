<?php
//  Procura dentro do PHP "principal" o jQuery (ou algum include que o carregue) para não carregá-lo de novo)
$filename = substr($PHP_SELF, strrpos($PHP_SELF, "/")+1);

// $file_content = file_get_contents($filename);
// if (strpos($file_content, "jquery-") === false and
// 	strpos($file_content, "javascript_calend") === false) {
// 	echo "<script src='../js/jquery-1.3.2.js' type='text/javascript'></script>\n";
// }

if (substr($filename, 0, 5) == "menu_") { ?>

<style type='text/css'>
/*	Estilo para o 'avisador de mensagens	*/
div#pl_msgs {
	position: fixed;
	_position: absolute;
	*position: absolute;
	bottom: 0;
	right: 10px;
	*height: 28px;
	_height: 28px;
	max-height: 28px;
	width: 200px;
	overflow: hide;
	display: none;
	line-height: 12px;
			border-radius: 5px 5px 0 0;
			-moz-border-radius: 5px 5px 0 0;
			border: 1px solid #ccb;
			background-color: #D9E2EF;
			font-family: Arial, Helvetica, sans-serif;
			font-size: 11px;
			color: #000;
			cursor: default;
	margin:0;
	padding:0;
	z-index: 15;
	transition: max-height 0.5s;
	-o-transition: max-height 0.5s;
	-moz-transition: max-height 0.5s;
	-webkit-transition: max-height 0.5s;
}
div#pl_msgs:hover {
	max-height: 230px;
	_height: 250px; /*IE 6*/
	*height: 250px; /*IE 5.5*/
}
div#pl_msgs p {
	cursor: default;
	margin: 0;
	height: 28px;
	padding-top: 5px;
	font-weight:bold;
	background-color: #596d9b;
	border-radius: 5px 5px 0 0;
	-moz-border-radius: 5px 5px 0 0;
	color: #fff;
	width: 100%;
}
div#pl_msgs p span { /* Botão com o total de mensagens */
	background-color: red;
	color: lightyellow;
	padding: 3px 5px;
/*	float: right;
	margin-top: -5px;
	_margin-top: px;
	margin-right: 5px;
*/
	position: absolute;
	top: 3px;
	right: 3px;
	border-radius: 6px;
	-moz-border-radius: 6px;
	box-shadow: -1px 1px 3px grey;
	-moz-box-shadow: -1px 1px 3px grey;
	-webkit-box-shadow: -1px 1px 3px grey;
}

#pl_msgs ol {
	color: darkgreen;
	width: 180px;
	height: 200px;
	margin-left: 20px;
	padding:0;
	text-align: left;
    white-space: nowrap;
	text-overflow: ellipsis;
	cursor: default;
	overflow-x: hidden;
	overflow-y: auto;
}

#pl_msgs ol li {
	color: navy;
	padding:1ex 0 0 0;
	cursor: pointer;
	font-size: 11px;
	margin-bottom: 0.4em;
	line-height: 14px;
	height: 14px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	-o-text-overflow: ellipsis;
	text-align: left;
	width: 165px;
		border: 1px solid #bbb;
		border-radius: 3px;
		-moz-border-radius: 3px;
		background: #BBCCDD;
		background: linear-gradient(top, #E2EBFA, #BBCCDD); /* IE6+ + PIE */
		background: -moz-linear-gradient(top, #E2EBFA, #BBCCDD); /* FF3.6 */
		background: -webkit-gradient(linear,left top,left bottom,from(#E2EBFA),to(#BBCCDD)); /* Saf4+, Chrome */
}
#pl_msgs ol li:hover {
	font-weight: bold;
	text-overflow: none;
	-o-text-overflow: none;
		background: #E2EBFA;
		background: -linear-gradient(top, #BBCCDD, #E2EBFA); /* IE6+ + PIE */
		background: -moz-linear-gradient(top, #BBCCDD, #E2EBFA); /* FF3.6 */
		background: -webkit-gradient(linear,left top,left bottom,from(#BBCCDD),to(#E2EBFA)); /* Saf4+, Chrome */
}

#pl_mail {
	position: fixed;
	top: 10%;
	height: 75%;
	left: 15%;
	width: 80%;
	background-color: white;
	border: 5px solid #bcd;
	border-radius: 6px;
	-moz-border-radius: 6px;
	box-shadow: -2px 2px 5px grey;
	-moz-box-shadow: -2px 2px 5px grey;
	-webkit-box-shadow: -2px 2px 5px grey;
	display: none;
	overflow-y: auto;
	text-align: left;
	z-index: 100;
}
#pl_mail #fechar_msg {
	position: absolute;
	top: 10px;
	right: 10px;
	color: #999;
	cursor: pointer;
	border: 1px solid #aaa;
	border-radius: 6px;
	-moz-border-radius: 6px;
	box-shadow: -2px 2px 4px grey;
	-moz-box-shadow: -2px 2px 4px grey;
	-webkit-box-shadow: -2px 2px 4px grey;
}
#pl_mail #fechar_msg:hover {
	border: 2px solid #aaa;
	box-shadow: -1px 1px 3px grey;
	-moz-box-shadow: -1px 1px 3px grey;
	-webkit-box-shadow: -1px 1px 3px grey;
}

#pl_mail fieldset {
	color: #222;
	font-size: 12px;
	width: 75%;
	margin: 1em;
	line-height: 1.4em;
	border: 1px solid lightgrey;
	padding-left: 1em;
	border-radius: 6px;
	-moz-border-radius: 6px;
	box-shadow: -1px 1px 3px grey;
	-moz-box-shadow: -1px 1px 3px grey;
	-webkit-box-shadow: -1px 1px 3px grey;
}
#pl_mail fieldset label {
	display: inline-block;
	font-weight: bold;
	width: 4.5em;
	padding-right: 3px;
	text-align: right;
}
#pl_mail div {
	font-size: 14px;
	margin: 1em;
	border-top: 1px dashed grey;
}
</style>

<script src='http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js' type='text/javascript'></script>
<script type='text/javascript'>
$(document).ready(function () {
	$.get("email_admin.php",
		  {"ajax":"consulta"},
		  function (data) {
			  if ( data == "error" ) { return false; }
			  var emailInfo = data.split('|');
			  emails = emailInfo[0];
			  emailn = emailInfo[1];
			  $('#pl_msgs p span').text(emailn);
			  if (emailn > 0) $('#pl_msgs').show();
/*				var msg_info = '';
				for (email in emails) {
					msg_info = "<li title='Mensagem do dia "+email.data+", assunto: "+email.subject+"'><button value='"+email.file+"'>"+email.subject+"</button></li>";
					alert(email.subject);
*/
				$('#pl_msg_list').html(emails);
				$('#pl_msg_list li').click(function () {
					var email = $(this).attr('alt');
					$.get("email_admin.php",
						  {'ajax':'getmsg','file':email},
						  function (msg_body) {
							if (msg_body.length != 0) {
								$('#pl_mail').html(msg_body).show('normal');
								$('#fechar_msg').click(function () {
									$('#pl_mail').hide('normal');
								});
							}
						  });
				});
// 				$('#pl_mail').dblclick(function() {$(this).hide('normal');});
		  });
});
</script>

<div id='pl_msgs'>
	<p>Mensagens do Sistema<span></span></p>
	<ol id='pl_msg_list'>
	 <li></li>
	</ol>
</div>
<div id='pl_mail'></div>
<?}?>
