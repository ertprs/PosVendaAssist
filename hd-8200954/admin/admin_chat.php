<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include 'autentica_admin.php';

include 'rocketchat_api.php';


$msg = "";
$msg_erro = array();


$title = "GERÊNCIA DE CHAT's DE ADMINS";
$layout_menu = "gerencia";

include_once 'funcoes.php';

$admin_privilegios = "call_center";


if(array_key_exists("ajax", $_POST)){


	switch ($_POST['action']) {		
		case 'enable-admin':

		$_POST['admin'] = (int) $_POST['admin'];

		$sql = "SELECT admin, nome_completo, login, senha, email, parametros_adicionais FROM tbl_admin WHERE admin = ".$_POST['admin'];
		
		$res = pg_query($con,$sql);
		$res = pg_fetch_all($res);
		$res = $res[0];

		$parametros_adicionais = json_decode($res['parametros_adicionais'],true);
		if($parametros_adicionais == null){
			$parametros_adicionais = array();
		}

		if(array_key_exists("rocketchat_id", $parametros_adicionais)){
			$response = updateUser($parametros_adicionais['rocketchat_id'], true, $login_fabrica);
		}else{

			$response = postUsers($login_fabrica,$res['email'],$res['login'],$res['nome_completo'],$res['senha'],"atendente",array("posvendaUserId" => $res['admin'],"posvendUserType" => "admin"));
			
			if(array_key_exists("error", $response['user'])){
				echo json_encode(array("exception" => utf8_encode("Esse usuário pode já estar cadastrado: ".$response['user']['error'])));
				exit;
			}elseif(array_key_exists("exception", $response)){
				echo json_encode(array("exception" => utf8_encode("Ocorreu um erro ao cadastrar o usuário: ".$response['message'])));
				exit;
			}

			$parametros_adicionais['rocketchat_id'] = $response['user']['user']['_id'];
			$parametros_adicionais['rocketchat_username'] = $response['user']['user']['username'];
			$parametros_adicionais['rocketchat_roles'] = $response['user']['user']['roles'];	
		}

		
		$parametros_adicionais['active'] = true;

		$sql = "UPDATE tbl_admin SET parametros_adicionais = '".json_encode($parametros_adicionais)."' WHERE admin = ".$res['admin'];	
		$res = pg_query($con,$sql);

		echo json_encode($response);
		exit;	
		break;


		case 'activate-admin':
		$_POST['admin'] = (int) $_POST['admin'];

		$sql = "SELECT admin, nome_completo, login, senha, email, parametros_adicionais FROM tbl_admin WHERE admin = ".$_POST['admin'];
		
		$res = pg_query($con,$sql);
		$res = pg_fetch_all($res);
		$res = $res[0];

		$parametros_adicionais = json_decode($res['parametros_adicionais'],true);
		$rocketchat_id = $parametros_adicionais['rocketchat_id'];

		$response = updateUser($rocketchat_id, true, $login_fabrica);

		if($response['success'] == true){
			$parametros_adicionais['active'] = true;
			$sql = "UPDATE tbl_admin SET parametros_adicionais = '".json_encode($parametros_adicionais)."' WHERE admin = ".$res['admin'];
			$res = pg_query($con,$sql);
		}

		echo json_encode($response);
		exit;	
		break;

		case 'disable-admin':
		$_POST['admin'] = (int) $_POST['admin'];

		$sql = "SELECT admin, nome_completo, login, senha, email, parametros_adicionais FROM tbl_admin WHERE admin = ".$_POST['admin'];
		
		$res = pg_query($con,$sql);
		$res = pg_fetch_all($res);
		$res = $res[0];

		$parametros_adicionais = json_decode($res['parametros_adicionais'],true);
		$rocketchat_id = $parametros_adicionais['rocketchat_id'];

		$response = updateUser($rocketchat_id, false, $login_fabrica);

		if($response['success'] == true){
			$parametros_adicionais['active'] = false;
			$sql = "UPDATE tbl_admin SET parametros_adicionais = '".json_encode($parametros_adicionais)."' WHERE admin = ".$res['admin'];	
			$res = pg_query($con,$sql);
		}

		echo json_encode($response);
		exit;	
		break;



		case 'view-info':
		$_POST['admin'] = (int) $_POST['admin'];

		$sql = "SELECT admin, nome_completo, login, senha, email, parametros_adicionais FROM tbl_admin WHERE admin = ".$_POST['admin'];
		$res = pg_query($con,$sql);
		$res = pg_fetch_all($res);
		$res = $res[0];

		$parametros_adicionais = json_decode($res['parametros_adicionais'],true);

		$response = getUserInfo($parametros_adicionais['rocketchat_id'],$login_fabrica);


		echo json_encode($response);
		exit;
		break;


		case 'change-roles':
		$_POST['admin'] = (int) $_POST['admin'];

		//Define permissão de atendente padrão para todos usuário cadastrados a partir dessa tela
		$roles = array("atendente");
		foreach ($_POST['roles'] as $key => $value) {
			if($value == 'true'){
				$roles[] = $key;
			}
		}		
		

		$sql = "SELECT admin, nome_completo, login, senha, email, parametros_adicionais FROM tbl_admin WHERE admin = ".$_POST['admin'];
		
		$res = pg_query($con,$sql);
		$res = pg_fetch_all($res);
		$res = $res[0];

		$parametros_adicionais = json_decode($res['parametros_adicionais'],true);
		$rocketchat_id = $parametros_adicionais['rocketchat_id'];

		$response = updateUserRoles($rocketchat_id, $roles, $login_fabrica);

		$parametros_adicionais = json_decode($res['parametros_adicionais'],true);

		if($response['success'] == true){
			$parametros_adicionais['rocketchat_roles'] = $roles;

			$sql = "UPDATE tbl_admin SET parametros_adicionais = '".json_encode($parametros_adicionais)."' WHERE admin = ".$res['admin'];	
			$res = pg_query($con,$sql);
		}
		echo json_encode($response);

		break;
		
		default:
			# code...
		break;
	}

	exit;
}


include 'cabecalho_new.php';

$plugins = array(                
	"mask",
	"dataTable"
	);

include("plugin_loader.php");


if ((count($msg_erro["msg"]) > 0) ) {
	?>
	<div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	</div>
	<?php
}
?>

<style type="text/css">
	.btn-table{
		width: 100%;
		margin-top: 2px;
	}

	.table > tbody > tr > td{
		vertical-align: middle;
	}

	.td-integrado{
		border-left: 3px solid #46b700 !important;
	}

	.td-desabilitado{
		border-left: 3px solid #333 !important;
	}

	.success-label{
		color: #32ab00;
	}

	#leg-span-habilitado{
		width: 10px;
	    height: 10px;
	    background-color: #46b700;
	    display: block;
	    float: left;
	    margin-top: 5px;
	    margin-right: 6px;
	}

	#leg-span-desabilitado{
		width: 10px;
	    height: 10px;
	    background-color: #333;
	    display: block;
	    float: left;
	    margin-top: 5px;
	    margin-right: 6px;
	}

	#legenda-lista{
		list-style: none;
	    border: 1px solid #e2e2e2;
	    width: 149px;
	    padding-left: 10px;
	    margin: 0 0 10px 0px;
	}

	.td-permissoes > label{
		width: 100%;
	}
</style>



<ul id="legenda-lista">
	<li><b>Legendas</b></li>
	<li><span id="leg-span-habilitado"></span> Habilitado </li>
	<li><span id="leg-span-desabilitado"></span> Desabilitado</li>
</ul>


<table id="table-admins" class='table table-striped table-bordered table-hover' style="width: 100%;">
	<thead>
		<tr class="titulo_tabela">
			<th colspan="7">Admins X Chat</th>
		</tr>            
		<tr class="titulo_coluna">				
			<th style="text-align: left !important;">Login Pósvenda - Nome: Login Chat</th>				
			<th>Email</th>                
			<th style="width: 150px;">Ação</th>                
			<th style="width: 300px;">Permissões</th>                
		</tr>
	</thead> 
	<tbody>
		<?php
		$sql = "SELECT admin, nome_completo, login, email, parametros_adicionais FROM tbl_admin WHERE ativo is true AND fabrica = $login_fabrica ORDER BY nome_completo;";			
		$res = pg_query($con, $sql);
		$count = pg_num_rows($res);			
		for($i=0;$i<$count;$i++){
			$nome_completo = pg_fetch_result($res, $i, "nome_completo")	;
			$admin = pg_fetch_result($res, $i, "admin")	;
			$login = pg_fetch_result($res, $i, "login")	;
			$email = pg_fetch_result($res, $i, "email")	;
			$parametros_adicionais = pg_fetch_result($res, $i, "parametros_adicionais")	;

			$parametros_adicionais = json_decode($parametros_adicionais,true);
			if($parametros_adicionais == null){
				$parametros_adicionais = array();
			}

			$td_class = "";
			
			$integrado = false;
			if(array_key_exists("rocketchat_id", $parametros_adicionais)){
				$integrado = true;
			}

			if(array_key_exists("active", $parametros_adicionais) && $parametros_adicionais['active'] == true){			
				$td_class = "td-integrado";
			}elseif(array_key_exists("active", $parametros_adicionais) && $parametros_adicionais['active'] == false){
				$td_class = "td-desabilitado";
			}

			?>
			<tr>			 		
				<td class="<?=$td_class?>" ><?=$login." - ".$nome_completo ?><?=$parametros_adicionais['rocketchat_username']? ": <b>".$parametros_adicionais['rocketchat_username']."</b>": ""?></td>					
				<td><?=$email ?></td>
				<td data-sinc="<?=$integrado == true? "sinc":"unsinc" ?>"><?php
					if($integrado){
						?>							
						<button data-admin="<?=$admin?>" class="btn btn-mini btn-table btn-view-info" type="button">Visualizar Informações</button>														
						<?php
					}else{
						?>
						<button data-admin="<?=$admin?>"  class="btn btn-mini btn-table btn-enable-chat" type="button">Ativar Chat</button>
						<?php
					}

					if(array_key_exists("active", $parametros_adicionais) && $parametros_adicionais['active'] == true){
						?>
						<button data-admin="<?=$admin?>" class="btn btn-mini btn-table btn-disable-chat" type="button">Desativar Chat</button>
						<?php
					}elseif(array_key_exists("active", $parametros_adicionais) && $parametros_adicionais['active'] == false){
						?>
						<button data-admin="<?=$admin?>"  class="btn btn-mini btn-table btn-activate-chat" type="button">Ativar Chat</button>
						<?php
					}					
					?>					
					</td>
					<td class="td-permissoes">				
						<?php
						$disabled = true;
						$roles =array();
						if(array_key_exists("active", $parametros_adicionais) && $parametros_adicionais['active'] == true){
							$disabled = false;
							$roles = $parametros_adicionais['rocketchat_roles'];							
						}
						?>		
						<label><input <?=in_array("at-financeiro",$roles)? "checked='checked'": ""?> <?=$disabled==true?"disabled='disabled'": ""?> data-admin="<?=$admin?>" type="checkbox" class="ck-permissoes" name="at-financeiro"> Pagamento</label>
						<label><input <?=in_array("at-tecnico",$roles)? "checked='checked'": ""?> <?=$disabled==true?"disabled='disabled'": ""?> data-admin="<?=$admin?>" type="checkbox" class="ck-permissoes" name="at-tecnico"> Suporte técnico/Placa</label>
						<label><input <?=in_array("at-autorizacao-km",$roles)? "checked='checked'": ""?> <?=$disabled==true?"disabled='disabled'": ""?> data-admin="<?=$admin?>" type="checkbox" class="ck-permissoes" name="at-autorizacao-km"> Autorização de KM</label>
						<label><input <?=in_array("at-reincidencia",$roles)? "checked='checked'": ""?> <?=$disabled==true?"disabled='disabled'": ""?> data-admin="<?=$admin?>" type="checkbox" class="ck-permissoes" name="at-reincidencia"> Reincidência</label>
						<label><input <?=in_array("at-duvida-os",$roles)? "checked='checked'": ""?> <?=$disabled==true?"disabled='disabled'": ""?> data-admin="<?=$admin?>" type="checkbox" class="ck-permissoes" name="at-duvida-os"> Dúvida sobre OS</label>
						<label><input <?=in_array("at-coleta",$roles)? "checked='checked'": ""?> <?=$disabled==true?"disabled='disabled'": ""?> data-admin="<?=$admin?>" type="checkbox" class="ck-permissoes" name="at-coleta"> Solicitação de Coleta</label>
						<label><input <?=in_array("at-fechamento-os",$roles)? "checked='checked'": ""?> <?=$disabled==true?"disabled='disabled'": ""?> data-admin="<?=$admin?>" type="checkbox" class="ck-permissoes" name="at-fechamento-os"> Fechamento de OS</label>
						<label><input <?=in_array("at-credenciamento",$roles)? "checked='checked'": ""?> <?=$disabled==true?"disabled='disabled'": ""?> data-admin="<?=$admin?>" type="checkbox" class="ck-permissoes" name="at-credenciamento"> Credenciamento/Descredenciamento</label>
						<label><input <?=in_array("at-pedidos",$roles)? "checked='checked'": ""?> <?=$disabled==true?"disabled='disabled'": ""?> data-admin="<?=$admin?>" type="checkbox" class="ck-permissoes" name="at-pedidos"> Pedidos de Peça</label>
					</td>
				</tr>
				<?php
			}
			?>	        
		</tbody>        
	</table>
	


	<script type="text/javascript">
		$(function(){
			


			function viewInfo(btn){
				var admin = $(this).data("admin");
				$(this).attr("disabled","disabled");

				var tr = $(this).parents("tr")[0];
				
				$.ajax("#",{
					method: "POST",
					data: {
						ajax: true,
						admin: admin,
						action: "view-info"
					}
				}).done(function(response){			
					response = JSON.parse(response);
					if(response.success == true){
						if(response.user.active == true){
							response.user.active = "Sim";
						}else{
							response.user.active = "Não";
						}
						var list = "";
						list +=  "<li><b>Identificação: </b>"+response.user._id+"</li>";
						list +=  "<li><b>Nome: </b>"+response.user.name+"</li>";
						list +=  "<li><b>Status: </b>"+response.user.status+"</li>";
						list +=  "<li><b>Username: </b>"+response.user.username+"</li>";						
						var emails = "";
						$(response.user.emails).each(function(idx,elem){
							console.log(elem);
							if(emails != ""){
								emails += "; ";
							}
							emails += elem.address;
						});
						list +=  "<li><b>Emails: </b>"+emails+"</li>";
						list +=  "<li><b>Habilitado Em: </b>"+response.user.created_at+"</li>";
						list +=  "<li><b>Ativo: </b>"+response.user.active+"</li>";

						var line = "<tr class='warning'><td colspan='4'><ul>"+list+"</ul></td></tr>";

						$(tr).after(line);
					}

					console.log(response);
				});
			}

			function activateUser() {				
				console.log('activateUser');
				var admin = $(this).data("admin");
				var btn = $(this);
				console.log(admin);


				$.ajax("#",{
					method: "POST",
					data: {
						ajax: true,
						admin: admin,
						action: "activate-admin"
					}
				}).done(function(response){					
					response = JSON.parse(response);
					$(btn).fadeOut("fast",function(){
						
						var td = $(this).parent("td");						
						console.log(btn);
						if($(btn).parents("tr").find(".btn-view-info").length == 0){
							var btnView = $('<button class="btn btn-mini btn-table btn-view-info" type="button">Visualizar Informações</button>');						
							$(btnView).data("admin",admin);						
							$(btnView).click(viewInfo);	
							$(td).append(btnView);					
						}
												
						var btnDisable = $('<button data-admin="'+admin+'" class="btn btn-mini btn-table" type="button">Desativar Chat</button>');
						
						$(btnDisable).click(disableUser);						
											
						$(td).append(btnDisable);
						
					});
					$(btn).parents("tr").find(".ck-permissoes").removeAttr("disabled");
					$($(btn).parents("tr").find("td")[0]).removeClass("td-desabilitado");
					$($(btn).parents("tr").find("td")[0]).addClass("td-habilitado");
					console.log(response);
				});
			}

			function disableUser() {
				console.log('disableUser');
				var admin = $(this).data("admin");
				var btn = $(this);
				console.log(admin);

				$.ajax("#",{
					method: "POST",
					data: {
						ajax: true,
						admin: admin,
						action: "disable-admin"
					}
				}).done(function(response){					
					response = JSON.parse(response);
					$(btn).fadeOut("fast",function(){
						var td = $(btn).parents("td");

						var btnDisable = $('<button class="btn btn-mini btn-table btn-enable-chat" type="button">Ativar Chat</button>');
						$(btnDisable).data("admin",admin);
						$(btnDisable).click(enableUser)
						$(td).append(btnDisable);
					});

					$(btn).parents("tr").find(".ck-permissoes").attr("disabled","disabled");					
					$($(btn).parents("tr").find("td")[0]).removeClass("td-habilitado");
					$($(btn).parents("tr").find("td")[0]).addClass("td-desabilitado");
					console.log(response);
				});
			}

			function enableUser(){
				console.log('enableUser');
				var admin = $(this).data("admin");
				var btn = $(this);
				$(btn).html("Habilitando...");
				$(btn).attr("disabled","disabled");

				$.ajax("#",{
					method: "POST",
					data: {
						ajax: true,
						admin: admin,
						action: "enable-admin"
					}
				}).done(function(response){
					response = JSON.parse(response);
					if(response.exception == undefined){
						$(btn).fadeOut("fast",function(){
							console.log(this);
							var td = $(this).parent("td");
							console.log(td);

							var btnView = $('<button class="btn btn-mini btn-table btn-view-info" type="button">Visualizar Informações</button>');
							$(btnView).data("admin",admin);
							$(btnView).click(viewInfo);

							var btnDisable = $('<button data-admin="'+admin+'" class="btn btn-mini btn-table" type="button">Desativar Chat</button>');
							$(btnDisable).click(disableUser);	


							if($(td).find(".btn-view-info").length == 0){
								$(td).append(btnView);								
							}							
							
							$(td).append(btnDisable);							

						});	
						$(btn).parents("tr").find(".ck-permissoes").removeAttr("disabled");

						$($(btn).parents("tr").find("td")[0]).removeClass("td-desabilitado");
						$($(btn).parents("tr").find("td")[0]).addClass("td-habilitado");
					}else{
						$(btn).html("Habilitar Chat");
						$(btn).removeAttr("disabled");
						alert("Ocorreu um erro ao cadastrar: " + response.message);
					}
					console.log(response);
				});
			}


			$(".ck-permissoes").change(function(){				
				var ck = $(this);
				var admin = $(this).data("admin");

				var td = $(this).parents("td");
				console.log(td);
				var checks = $(td).find(".ck-permissoes");

				var roles = Object();
				$(checks).each(function(idx,elem){
					roles[$(elem).attr("name")] = $(elem).is(":checked");
				});
				

				$.ajax("#",{
					method: "POST",
					data:{
						ajax: true,
						admin: admin,
						roles: roles,
						action: "change-roles"
					}
				}).done(function(response){
					response = JSON.parse(response);
					if(response.success == true){
						if($(ck).is(":checked")){
							$(ck).parents("label").addClass("success-label");	
							setTimeout(function(){
								$(ck).parents("label").removeClass("success-label");	
							},4000);
						}
						
					}
				});				
			});

			
			$(".btn-disable-chat").click(disableUser);
			$(".btn-view-info").click(viewInfo);

			$(".btn-enable-chat").click(enableUser);
			$(".btn-activate-chat").click(activateUser);

			var table = new Object();
	        table['table'] = '#table-admins';	        
	        $.dataTableLoad(table);
		});
	</script>

	<?php include "rodape.php"; ?>