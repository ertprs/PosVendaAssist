<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
require_once "../class/communicator.class.php";

if($_POST['ajax'] == true){

	$codigo = $_POST['codigo_seguranca'];

	if(strlen($codigo) > 0){
		$sql = "SELECT parametros_adicionais::json->>'tokenMlg' AS token FROM tbl_admin WHERE fabrica = {$login_fabrica} AND admin = {$login_admin} AND parametros_adicionais::jsonb->>'codigo_seguranca' = '{$codigo}'";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$token = pg_fetch_result($res,0,'token');
			$retorno['erro'] = false;
			$retorno['token'] = $token;
		}else{
			$retorno['erro'] = true;
		}
	}
	
	echo json_encode($retorno);

	exit;
}

$sqlAdmin = "SELECT admin, email, parametros_adicionais FROM tbl_admin WHERE fabrica = 10 AND admin = $login_admin";
$resAdmin = pg_query($con, $sqlAdmin);
$sucesso = false;
if (pg_num_rows($resAdmin) > 0) {
	$xparametros_adicionais = json_decode(pg_fetch_result($resAdmin, 0, 'parametros_adicionais'),1);
	$xadmin = pg_fetch_result($resAdmin, 0, 'admin');
	$xemail = pg_fetch_result($resAdmin, 0, 'email');
	/*gera token*/
	$tokenMlg = sha1($xadmin.date('YmdHis'));
	/*seta token no paramentros add*/
	$codigo_seguranca = random_int(100, 99999);
	$xparametros_adicionais["codigo_seguranca"] = $codigo_seguranca;
	$xparametros_adicionais["tokenMlg"] = $tokenMlg;

	$novo_parametros = json_encode($xparametros_adicionais);

	$sqlUp = "UPDATE tbl_admin SET parametros_adicionais = '{$novo_parametros}' WHERE fabrica = 10 AND admin = $xadmin"; 
	$resUp = pg_query($con, $sqlUp);

	if (pg_last_error()) {
		$meg_erro["msg"][] = "Erro ao gravar";
	} else {
		$email          = explode("@", $xemail);
		$init_email     = substr($email[0], 0,2);
		$fim_email      = str_replace(substr($email[0], 2), "***", $xemail);

		/*dispara email para admin*/
		$mensagemEmail  = "Segue código de segurança para acesso ao MLG <h3>".$codigo_seguranca."</h3>";
		$assunto        = 'Código de Segurança para acesso ao MLG - Telecontrol';
		$mensagem       = $mensagemEmail;
		$externalId     = 'smtp@posvenda';
		$externalEmail  = 'noreply@telecontrol.com.br';

		$mailTc = new \TcComm($externalId);
		$res    = $mailTc->sendMail(
			$xemail,
			$assunto,
			$mensagem,
			$externalEmail
		);
		if ($res) {
			$sucesso = true;
		}

	}

} else {
	$meg_erro["msg"][] = "Admin não encontrado";
}

if ($sucesso) {

?>
<!doctype html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
		.box{
			margin: 0 auto;text-align:center;font-family: 'Arial';width: 100%
		}
		.box-int{
			margin: 0 auto;margin-top: 25px;background:#ddd;border:solid 1px #ddd;width: 90%
		}
	</style>
  </head>
  <body>
	<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	<?php 
		$plugins = array(
		    "bootstrap2"
		);
		include("plugin_loader.php");
	?>
<script>
	$(function(){

		$("#btn_valida").click(function(){
			var codigo = $("#codigo_seguranca").val();
			if(codigo == '') {
				$(".erro").show();
				$(".erro").html("Digite o C&oacute;digo inv&aacute;lido");
				$("#codigo_seguranca").focus();
				return false;
			}
			$("#btn_valida").attr("disabled", true);
			$("#btn_valida").text("Validando...");
			$.ajax({
				url: "acessa_mlg.php",
				method: "POST",
				data: {ajax:true,codigo_seguranca:codigo},
				success: function(retorno){
					var data = $.parseJSON(retorno);
					if(data.erro == true){
						$(".erro").show();
						$(".erro").html("C&oacute;digo inv&aacute;lido");
					}else{
						window.open("https://ww2.telecontrol.com.br/mlg/consultas.php?token="+data.token);
						window.parent.Shadowbox.close();
					}
					$("#btn_valida").removeAttr("disabled");
					$("#btn_valida").text("Validar");
				}
			});
		});
	});

</script>

<div class="box">
	<div class="box-int">
		<div class="alert alert-danger erro" style='display:none'></div>
		<h5>Foi enviado um c&oacute;digo de seguran&ccedil;a para o e-mail:</h5>
		<h3><?php echo $fim_email;?></h3>
		<label for="">C&oacute;digo de Seguran&ccedil;a</label>
		<div class="row-fluid">
			<div class="span4"></div>
			<div class="span4">
				<div class="input-append">
		      		<input type="text" class="form-control input-sm" name="codigo_seguranca" id="codigo_seguranca">
		        	<button type="button" data-loading-text="Validando..." class="btn btn-sm btn-success" id="btn_valida" >Validar</button>
		    	</div>
	    	</div>
    	</div>
    	<br>
	</div>
</div>
</body>
</html>
<?php }?>
