<?php
	include 'dbconfig.php';
	include 'dbconnect-inc.php';
	$admin_privilegios="call_center";
	include 'autentica_admin.php';
	$layout_menu = "callcenter";
	$title = "Alterar Senha";
	include 'cabecalho.php';
	
	$fabricas_usam = array(96);
	if(!in_array($login_fabrica,$fabricas_usam) )
		die ('Você não possui permissão para acessar essa tela. Contate o Administrador');

	/* Processa requisição do formulario */
		if ( isset($_POST['enviar']) ) { 
		
			$antiga 	= $_POST['antiga'];
			$nova		= $_POST['senha_nova'];
			$confirma 	= $_POST['senha_confirma'];
		
			if (empty($antiga))
				$msg_erro = 'Preencha a Senha Atual';
			if (empty($nova) && !isset($msg_erro) )
				$msg_erro = 'Preencha a Nova Senha';
			if( ( $nova != $confirma ) && !isset($msg_erro) )
				$msg_erro = 'Os campos Nova Senha e Confirmação de Senha devem ser iguais';
			if( !isset($msg_erro) && ( strlen($nova) < 6 || strlen($nova) > 10 ) )
				$msg_erro = 'A senha deve conter no mínimo 6 e no máximo 10 caracteres';
				
			if( !isset($msg_erro)) {
				$count_letras = preg_match_all('/[a-z]/i', $nova, $a_letras);
				$count_nums = preg_match_all('/\d/', $nova, $a_nums);
				if ( ($count_nums + $count_letras ) != strlen($nova) )
					$msg_erro = 'A senha deve conter apenas letras e números';
				if( $count_nums < 2 || $count_letras < 2 )
					$msg_erro = 'A senha deve conter no mínimo 2 números e 2 caracteres';
			}

			if(!isset($msg_erro)) {
			
				$sql = "SELECT admin FROM tbl_admin WHERE admin = $login_admin AND senha = '$antiga'";
				$res = pg_query($con,$sql);
				if(!pg_num_rows($res))
					$msg_erro = 'Senha Atual Inválida';
				else {
				
					$sql = "UPDATE tbl_admin SET senha = '$nova' WHERE admin = $login_admin";
					//echo $sql;
					//$res = pg_query($con,$sql);
					if(!pg_errormessage($con)) 
						$msg_sucesso = 'Senha Atualizada com Sucesso';

				}
			
			}

		}
	/* Fim Requisição */
?>

<style type="text/css">
	#formulario {
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
		width:700px;
		margin:auto;
	}
	#formulario form {
		width:350px;
		margin:auto;
	}
	#msg { text-align:center; display:none;}
	.msg_erro{
		background-color:#FF0000;
		font: bold 15px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.sucesso{
		background-color:#008000;
		font: bold 15px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
</style>
<br />
<div id="formulario">
	<div id="msg" class="msg_erro">Os campos Nova Senha e Confirmação de Senha estão diferentes</div>
	<?php if(isset($msg_erro)) { ?>
		<div class="msg_erro"><?=$msg_erro?></div>
	<?php } ?>
	<?php if(isset($msg_sucesso)) { ?>
		<div class="sucesso"><?=$msg_sucesso?></div>
	<?php } ?>
	<div class="titulo_tabela">Alterar Senha</div>
	<form action="<?=$PHP_SELF?>" method="POST">
		<table border="0">
			<tr>
				<td>
					<label for="antiga">
						Senha Atual<br />
						<input type="password" name="antiga" id="antiga" class="frm" value="<?=$antiga?>" />
					</label>
				</td>
			</tr>
			<tr>
				<td>
					<label for="senha_nova">
						Nova Senha<br />
						<input type="password" name="senha_nova" id="senha_nova" class="frm" value="<?=$nova?>" />
					</label>
				</td>
				<td>
					<label for="senha_confirma">
						Confirmar Nova Senha<br />
						<input type="password" name="senha_confirma" id="senha_confirma" class="frm" value="<?=$confirma?>" />
					</label>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" name="enviar" value="Alterar" style="cursor:pointer;" />
				</td>
			</tr>
		</table>
	</form>
</div>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript">
	$('input[name^="senha"]').blur(function(){
		if( $("#senha_confirma").val() !== '' && ( $(this).val() !== $("#senha_nova").val() || $(this).val() !== $("#senha_confirma").val() ) )
			$("div#msg").show();
		else if($(this).val() === $("#senha_nova").val() || $(this).val() === $("#senha_confirma").val()) 
			$("div.msg_erro").each(function(){
				$(this).fadeOut("slow");
			});
		
	});
</script>
<?php include 'rodape.php'; ?>