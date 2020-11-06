<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

if ($_REQUEST) {
	$pesquisa = $_REQUEST["pesquisa"];
	$tipo     = $_REQUEST["tipo"];

	switch ($tipo) {
		case "referencia":
			if ($_GET) {
				$referencia = $_GET["valor"];
			}

			if ($_POST) {
				$referencia = $_POST["referencia"];
			}

			$valor = trim($referencia);
			break;
		
		case "descricao":
			if ($_GET) {
				$descricao = $_GET["valor"];
			}

			if ($_POST) {
				$descricao = $_POST["descricao"];
			}

			$valor = trim($descricao);
			break;
	}

	$valor = str_replace(".", "", $valor);
	$valor = str_replace("-", "", $valor);
	$valor = str_replace("/", "", $valor);

	if (!strlen($valor) && strlen($valor) >= 3) {
		$msg["erro"] = "Digite toda ou parte de uma informação para pesquisar";
	} else {
		$whereAdc = "AND UPPER(tbl_".(($pesquisa == "produto") ? "produto" : "peca").".".(($tipo == "referencia") ? "referencia" : "descricao").") ILIKE UPPER('%$valor%')";

		if ($pesquisa == "produto") {
			$sql = "SELECT 
						referencia, descricao
					FROM tbl_produto
					WHERE fabrica_i = {$login_fabrica}
					AND ativo IS TRUE
					{$whereAdc}
					AND produto IN (
						SELECT tbl_lista_basica.produto
						FROM tbl_lista_basica
						JOIN tbl_peca ON tbl_peca.peca = tbl_lista_basica.peca 
						AND tbl_peca.fabrica = {$login_fabrica}
						WHERE tbl_lista_basica.fabrica = {$login_fabrica}
						AND tbl_lista_basica.produto = tbl_produto.produto
						AND (
							(tbl_peca.parametros_adicionais ILIKE '%\"upload_fotos\":\"t\"%')
							OR
							(tbl_peca.parametros_adicionais ILIKE '%\"serial_lcd\":\"t\"%')
						)
					)";
		}

		if ($pesquisa == "peca") {
			$sql = "SELECT
						referencia, descricao, parametros_adicionais
					FROM tbl_peca
					WHERE fabrica = {$login_fabrica}
					AND ativo IS TRUE
					{$whereAdc}
					AND (
						(tbl_peca.parametros_adicionais ILIKE '%\"upload_fotos\":\"t\"%')
						OR
						(tbl_peca.parametros_adicionais ILIKE '%\"serial_lcd\":\"t\"%')
					)";
		}

		if (isset($sql)) {
			$res = pg_query($con, $sql);

			if (pg_last_error()) {
				$msg["erro"] = "Ocorreu um erro ao realizar a pesquisa";
			}
		}
	}
	

	
}

?>

<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css" />
<style>
	input[type=submit] {
		cursor: pointer;
	}

	td {
		text-align: left;
	}

	.lp_tabela {
		border-collapse: collapse;
		width: 98%;
		margin: 0 auto;
	}

	.lp_tabela thead tr {
		background-color: #596D9B;
	}

	.lp_tabela thead tr td {
		color: #FFF;
	}

	.lp_tabela tbody tr {
		background-color: #FFF;
	}

	.lp_tabela tbody tr:hover {
		background-color: #E8E8E8;
	}
</style>

<script src="../js/jquery-1.7.2.js" ></script>
<script>
	$(function () {
		$("input[name=referencia]").keypress(function () {
			$("input[name=descricao]").val("");
			$("input[name=tipo]").val("referencia");
		});

		$("input[name=descricao]").keypress(function () {
			$("input[name=referencia]").val("");
			$("input[name=tipo]").val("descricao");
		});
	});
</script>

<body style="background-color: #FFF;" >
	<div class="lp_header" >
		<a href="javascript: window.parent.Shadowbox.close();" >
			<img src="../css/modal/excluir.png" alt="Fechar" class="lp_btn_fechar" />
		</a>
	</div>
	<div class="lp_nova_pesquisa" >
		<br />
		<form method="POST" >
			<input type="hidden" name="pesquisa" value="<?=$pesquisa?>" />
			<input type="hidden" name="tipo" value="<?=$tipo?>" />

			<table border="0" >
				<tr>
					<td>Referência</td>
					<td>Descrição</td>
				</tr>
				<tr>
					<td>
						<input type="text" name="referencia" style="width: 140px;" value="<?=$referencia?>" />
					</td>
					<td>
						<input type="text" name="descricao" value="<?=$descricao?>" />
					</td>
					<td rowspan="2" style="vertical-align: bottom;">
						<input type="submit" name="pesquisar" value="Pesquisar" />
					</td>
				</tr>
			</table>
		</form>
	</div>

	<?php

	if ($_REQUEST) {
		if (!empty($msg["erro"])) {
			echo "<div class='lp_msg_erro'>{$msg['erro']}</div>";
		} else {
			echo "<div class='lp_pesquisando_por'>
				Pesquisando pela ".(($tipo == "referencia") ? "referência" : "descrição").": ".(($tipo == "referencia") ? $referencia : $descricao)."
			</div>";
			?>

			<table border="0" class="lp_tabela" >
				<thead>
					<tr>
						<td>Referência</td>
						<td>Descrição</td>
						<?php
						if ($pesquisa == "peca") {
						?>
							<td style="text-align: center;" >Upload Fotos</td>
							<td style="text-align: center;" >Serial LCD</td>
						<?php
						}
						?>
					</tr>
				</thead>
				<tbody>
					<?php
					if (!pg_num_rows($res)) {
					?>
						<tr>
							<td colspan="<?=($pesquisa == 'produto') ? 2 : 4?>" style="text-align: center; color: #CC3333;" >
								Nenhum resultado encontrado
							</td>
						</tr>
					<?php
					} else {
						while ($result = pg_fetch_object($res)) {
							$json = array(
								"referencia" => $result->referencia,
								"descricao"  => $result->descricao,
								"pesquisa"   => $pesquisa
							);

							$json = json_encode($json);

							$onclick = "window.parent.resultado_pesquisa(\"".addslashes($json)."\"); window.parent.Shadowbox.close();";
						?>
							<tr onclick='<?=$onclick?>' >
								<td><?=$result->referencia?></td>
								<td><?=$result->descricao?></td>
								<?php
								if ($pesquisa == "peca") {
									$pa = $result->parametros_adicionais;
									$pa = json_decode($pa, true);

									$upload_fotos     = ($pa["upload_fotos"] == "t") ? "img_ok.gif" : "inativo.png";
									$upload_fotos_alt = ($pa["upload_fotos"] == "t") ? "Sim" : "Não" ;

									$serial_lcd     = ($pa["serial_lcd"] == "t") ? "img_ok.gif" : "inativo.png";
									$serial_lcd_alt = ($pa["serial_lcd"] == "t") ? "Sim" : "Não" ;
								?>
									<td style="text-align: center;" >
										<img src="imagens/<?=$upload_fotos?>" alt="<?=$upload_fotos_alt?>" />
									</td>
									<td style="text-align: center;" >
										<img src="imagens/<?=$serial_lcd?>" alt="<?=$serial_lcd_alt?>" />
									</td>
								<?php
								}
								?>
							</tr>
						<?php
						}
					}
					?>
				</tbody>
			</table>
		<?php
		}
	}

	?>
</body>