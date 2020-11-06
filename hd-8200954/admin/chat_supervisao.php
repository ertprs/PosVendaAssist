<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include 'autentica_admin.php';

include 'rocketchat_api.php';


$msg = "";
$msg_erro = array();


$title = "SUPERVISÃO DE CHAT's ATIVOS";
$layout_menu = "gerencia";

include_once 'funcoes.php';

$admin_privilegios = "call_center";




$msg_erro = "";

$sql = "SELECT c.protocolo,c.chat_id,to_char(c.inicio_atendimento,'DD-MM-YYYY HH:MI:SS') as inicio_atendimento, a.nome_completo as admin,p.nome as posto
FROM tbl_chat c 
JOIN tbl_chat_atendente ca ON c.chat = ca.chat 
JOIN tbl_admin a ON ca.admin = a.admin 
JOIN tbl_chat_posto cp ON c.chat = cp.chat 
JOIN tbl_posto p ON cp.posto = p.posto  
WHERE fim_atendimento is null AND c.fabrica = $login_fabrica ORDER BY inicio_atendimento DESC;";

$atendimentos = pg_query($con,$sql);
$atendimentos = pg_fetch_all($atendimentos);

if(array_key_exists("ajax", $_POST)){

	if(array_key_exists("chatid", $_POST)){

		
		$sqlAdmin = "SELECT parametros_adicionais FROM tbl_admin where admin = $login_admin";
		$supervisor = pg_query($con,$sqlAdmin);
		$supervisor = pg_fetch_all($supervisor);
		$parametros_adicionais = json_decode($supervisor[0]['parametros_adicionais'],true);
		

		$response = supervisionarAtendimento($login_fabrica,$parametros_adicionais['rocketchat_id'],$_POST['chatid']);
		echo json_encode($response);exit;
	}

	foreach ($atendimentos as $key => $value) {
		$atendimentos[$key]['admin'] = utf8_encode($value['admin']);
		$atendimentos[$key]['posto'] = utf8_encode($value['posto']);
	}
	echo json_encode($atendimentos);exit;
}



if(count($atendimentos) == 0){
	$msg_erro = "Nenhum atendimento no momento";
}

include 'cabecalho_new.php';

$plugins = array(                
	"mask",
	"dataTable"
	);

include("plugin_loader.php");



if($msg_erro != ""){
	?>	
	<div class="alert">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<?=$msg_erro?>
	</div>
	<?php
}



?>
<ul id="legenda-lista">
	<li><b>Legendas</b></li>
	<li><span id="leg-span-novo"></span> Novo atendimento </li>
	<li><span id="leg-span-finalizado"></span> Atendimento Finalizado</li>
</ul>
<div class="row" id="env-atendimento" style="margin: 0 auto;">
	<?php

	foreach ($atendimentos as $value) {
		?>
		<div class="span5">
			<div class="well atendimento" data-chatid="<?=$value['chat_id']?>">
				<h5><?=substr($value['protocolo'], 0,38)?>...</h5>
				<b>Atendente:</b> <?=$value['admin']?><br>
				<b>Posto:</b> <?=$value['posto']?><br>
				<b>Inicio do atendimento:</b> <?=$value['inicio_atendimento']?><br>
				<hr>
				<button class="btn btn-supervisao" type="button"><i class="icon-eye-open"></i> Supervisionar no Chat</button>
			</div>		
		</div>
		<?php
	}
	?>
</div>

<script type="text/javascript">
	$(function(){

		chatId = new Array();
		


		$($(".atendimento")).each(function(idx,elem){
			chatId.push($(elem).data("chatid"));
		});

			

		$(".btn-supervisao").click(supervisionarAtendimento);
		
		setInterval(function(){
			console.log("Req");
			$.ajax("#",{
				method: "POST",
				data: {
					"ajax": true
				}
			}).done(function(response){
				res = JSON.parse(response);
				chatIdReal = new Array();		
				$(res).each(function(idx,elem){			
					
					if(chatId.indexOf(elem.chat_id) == -1){
						criarAtendimento(elem);
						chatId.push(elem.chat_id);
					}

					chatIdReal.push(elem.chat_id);
				});

				$(".atendimento").each(function(idx, elem){
					if(chatIdReal.indexOf($(elem).data('chatid')) == -1){
						$(elem).addClass('well-warning');
						setTimeout(function(){
							$(elem).parents('.span5').fadeOut(1000);
						},15000);
					}
				});

			});
		},15000);


		function criarAtendimento(data){
			console.log("Criando...");

			var span5 = $("<div class='span5'>");
			var well = $("<div class='well atendimento'>");
			$(well).data("chatid",data.chat_id);
			var h5 = $("<h5>"+data.protocolo.substr(0,38)+"...</h5>");
			var content = '<b>Atendente:</b> '+data.admin+'<br><b>Posto:</b> '+data.posto+'<br><b>Inicio do atendimento:</b> '+data.inicio_atendimento+'<br><hr>'
			var btn = $('<button class="btn" type="button"><i class="icon-eye-open"></i>Supervisionar no Chat</button>');			
			$(btn).click(supervisionarAtendimento);

			$(well).append(h5);
			$(well).append(content);
			$(well).append(btn);
			$(well).addClass("well-success");			
			$(span5).append(well);

			setTimeout(function(){
				$(span5).find(".well-success").removeClass("well-success");
				console.log("sa");
			},15000);

			$("#env-atendimento").prepend(span5);
		}

		function supervisionarAtendimento(){
			var chatid = $(this).parents('.atendimento').data('chatid');
			var btn = $(this);
			$(btn).text('Processando supervisão...');
			$(btn).attr('disabled','disabled');

			var env = $(this).parents('.atendimento');
			$.ajax("#",{
				method: "POST",
				data: {
					ajax: true,
					chatid: chatid
				}
			}).done(function(response){
				console.log(response);
				response = JSON.parse(response);	
				console.log(response);

				$(btn).fadeOut(1000,function(){
					$(env).append("<a target='_blank' href='http://colormaq.chat.telecontrol.com.br/group/"+response.invite_group.group.name+"' class='btn'><i class='icon-share'></i>Acessar Chat</a>")
				});

				setTimeout(function(){
					window.open("http://colormaq.chat.telecontrol.com.br/group/"+response.invite_group.group.name,"_blank");
				},2000)
				
			});
		}
	});
</script>

<style type="text/css">
	.well-success{
		background-color: #abceb1 !important;
	}

	.well-warning{
		background-color: #d6a1a1 !important;
	}

	#legenda-lista{
		list-style: none;
	    border: 1px solid #e2e2e2;
	    width: 180px;
	    padding-left: 10px;
	    margin: 0 0 10px 0px;
	}

	#leg-span-novo{
		width: 10px;
	    height: 10px;
	    background-color: #abceb1;
	    display: block;
	    float: left;
	    margin-top: 5px;
	    margin-right: 6px;
	}

	#leg-span-finalizado{
		width: 10px;
	    height: 10px;
	    background-color: #d6a1a1;
	    display: block;
	    float: left;
	    margin-top: 5px;
	    margin-right: 6px;
	}
</style>


<?php include "rodape.php"; ?>

