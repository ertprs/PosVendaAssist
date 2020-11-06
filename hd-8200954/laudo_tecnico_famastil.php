<!DOCTYPE html />

<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if($_GET['admin']){
	include "admin/autentica_admin.php";
}else{
	include "autentica_usuario.php";
}
echo "<script type='text/javascript' src='inc_soMAYSsemAcento.js'></script>";

if ($_GET["os"]) {
	$sql = "SELECT laudo_tecnico,tecnico FROM tbl_os_extra WHERE os = {$_GET['os']}";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$nome_tecnico = pg_fetch_result($res, 0, 'tecnico');
		$observacao   = pg_fetch_result($res, 0, 'laudo_tecnico');
	}
	

	$defeito_constatado = $_GET['defeito_constatado'];
	$sql = "SELECT descricao FROM tbl_defeito_constatado WHERE defeito_constatado = {$defeito_constatado}";
	$res = pg_query($con,$sql);
	$defeito_constatado_descricao = pg_fetch_result($res, 0, 'descricao');
	

	$sql = "SELECT tbl_marca.logo 
			FROM tbl_os
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto 
			AND tbl_produto.fabrica_i = $login_fabrica
			LEFT JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca 
			AND tbl_marca.fabrica = $login_fabrica
			WHERE tbl_os.os = {$_GET['os']}";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$logo = pg_fetch_result($res, 0, 'logo');
		$logo = (empty($logo)) ? "logos/famastil.png" : $logo;
	}

}

if ($_POST["gravar"]) {
	$nome_tecnico = $_POST['nome_tecnico'];
	$observacao   = $_POST['observacao'];

	$sql = "UPDATE tbl_os_extra SET tecnico = '$nome_tecnico', laudo_tecnico = '$observacao' WHERE os = {$_GET['os']}";
	$res = pg_query($con,$sql);

	$sql = "UPDATE tbl_os SET finalizada = current_timestamp, data_fechamento = current_date WHERE os = {$_GET['os']}";
	$res = pg_query($con,$sql);

	$sql = "INSERT INTO tbl_os_status (
								os,
								status_os,
								data,
								observacao
							) values (
								{$_GET['os']},
								64,
								current_timestamp,
								E'OS liberada intervenção técnica, produto com {$defeito_constatado_descricao} '
							)";

	$res = pg_query ($con, $sql);

	if (pg_last_error()) {
		$erro = "Ocorreu um erro ao gravar o laudo!";
	} else {
		header("Location: $PHP_SELF?os={$_GET['os']}&imprimir=ok");
	}
	
}
?>
<html>
<head>
	<title>Laudo Técnico <?=$login_fabrica_nome?></title>

	<script src="js/jquery-1.6.2.js"></script>
	<script>
		$(function () {
			$("input,textarea").each(function(){
				if($(this).attr('name') =='nome_tecnico' || $(this).attr('name') == 'observacao' ){
					$(this).attr('onkeyup','somenteMaiusculaSemAcento(this)');
				}
			})
		});

		function gravaLaudo(){
			var tecnico = $("#nome_tecnico").val();
			var obs = $("textarea[name=observacao]").val();
			
			if(tecnico == ""){
				$("#erro").html('Informe o nome do técnico');
				$("#erro").show();
				$("#nome_tecnico").focus();
				setTimeout(function(){$("#erro").hide();},3000);
				return false;
			}

			else if(obs == ""){
                                $("#erro").html('Informe a observação do laudo');
                                $("#erro").show();
                                $("textarea[name=observacao]").focus();
                                setTimeout(function(){$("#erro").hide();},3000);
                                return false;
                        }else{
				document.frm_laudo.submit();
			}

		}
	</script>

<style>
	#principal {
		position: relative;
		margin: auto;
		width: 900px;
	}

	#cabecalho {
		width: 100%;
		height: 100px;
	}

	#cabecalho .logo {
		position: relative;
		float: left;
		left: 50px;
		border: 0px;
		width: 250px;
	}

	#cabecalho h1 {
		position: relative;
		top: 45px;
		margin: auto;
		left: 80px;
		color: #000;
	}

	#conteudo {
		width: 100%;
		color: #281F20;
		text-align: center;
	}

	#rodape {
		width: 100%;
	}

	#rodape .logo {
		position: relative;
		float: right;
		top: 10px;
		right: 10px;
		border: 0px;
		width: 200px;
	}

	#rodape .link {
		position: relative;
		float: left;
		background: #000;
		top: 15px;
		left: 20px;
		font-size: 16px;
		border-radius: 20px;
		line-height: 50px;
		width: 200px;
		text-align: center;
	}

	.link a {
		text-decoration: none;
		color: #FFF;
	}

	hr {
		width:  100%;
	}

	table {
		margin: auto;
		width: 100%;
	}

	td {
		text-align: left;
		font-size: 17px;
	}

	th {
		text-align: left;
		font-size: 18px;
	}

	input[type=text] {
		border-top: 0;
		border-left: 0;
		border-right: 0;
		border-bottom: black solid 1px;
		font-size: 16px;
	}

	textarea{
		text-align: justify;
	}

	#erro{
		margin:auto;
		color:#FF0000;
		display:none;
		text-align:center;
		font-weight:bold;
	}
</style>
</head>
<body>
<form method="post" name="frm_laudo">
		<div id="principal">
			<div id="cabecalho">
				<img class="logo" src="<?=$logo?>" />
				<h1>Laudo Técnico</h1>
			</div>

			<br />
			<div id='erro'></div>
			<div id="conteudo">	
				
				<table border="0">
					<tr>
						<th style="width: 200px;"><label for="nome_tecnico">Nome do Técnico:</label></th>
						<td><input type="text" id="nome_tecnico" name="nome_tecnico" value="<?=$nome_tecnico?>" style="width: 100%;" /></td>
					</tr>
					<tr><td>&nbsp;</td></tr>
					<tr>
						<th style="width: 200px;"><label for="defeito_constatado">Defeito constatado:</label></th>
						<td><input type="text" id="defeito_constatado" name="defeito_constatado" value="<?php echo $defeito_constatado_descricao ;?>" readonly style="width: 100%;" /></td>
					</tr>
				</table>

				<hr color="#000" background-color="#000" size="2" />

				
				<?php
				if ($_GET["imprimir"]) {
					echo "<br /> ";

				}
				?>

				<table border="0">
					<tr>
						<td style="text-align: left;">
							<b>Observações:</b>
						</td>
					</tr>
					<?php
					if ($_GET["imprimir"]) {
					?>
					<tr>
						<td style="text-align: justify;">
							<?=$observacao?>
						</td>
					</tr>
					<?php
					}else {
					?>
						<tr>
							<td>
								<textarea name="observacao" rows="15" style="width: 100%;"><?=$observacao?></textarea>
							</td>
						</tr>
					<?php	
					}
					?>
				</table>

				<br /> <br /> <br /> <br />

				<table border="0" style="margin-left: 20px; width: 100%;">
					<tr>
						<td>
							_____________________________________________<br />
							TÉCNICO (ASSINATURA)
						</td>
					
						<td>
							______________________________________________ <br />
							CLIENTE (ASSINATURA)
						</td>
					</tr>
					<tr><td>&nbsp;</td></tr>
					<tr>
						<td colspan='2' align='center'>
							<span style='color:#FF0000;font-weight:bold'>
								* Este documento só é válido se for impresso e assinado pelo técnico do posto e pelo consumidor dono do produto.
							</span>
						</td>
					</tr>
				</table>

				</br ></br ></br >

				<?php
				if (!$_GET["imprimir"]) {
				?>
					<input type="button" value="Gravar" onclick="gravaLaudo()" style="width: 90px; height: 30px; font-size: 16px; font-weight: bold;" />
					<input type='hidden' name='gravar' value='gravar'>

					<br /> <br />
				<?php
				}
				?>
			</div>
			<div id="rodape">
				<div class="link">
					<a href="http://www.famastil.com.br" target="_blank" >www.famastil.com.br</a>
				</div>
				<img class="logo" src="<?=$logo?>" />
			</div>
		</div>
	</form>
	<?php
		if ($_GET["imprimir"]) {
		?>
			<script>
				window.print();
				window.location="os_press.php?os=<?=$_GET['os']?>";
			</script>
		<?php
		}
		?>
	</body>
</html>



