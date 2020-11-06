<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';

$os		= trim (strtolower ($_REQUEST['os']));
$codigo	= trim (strtolower ($_REQUEST['codigo']));
$nome	= trim (strtolower ($_REQUEST['nome']));
$tela   = $_REQUEST['origem'];

$num_posto = trim (strtolower ($_REQUEST['num_posto']));

function verificaValorCampo($campo){
	return strlen($campo) > 0 ? $campo : "&nbsp;";
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv='pragma' content='no-cache'>
	<style type="text/css">
		body {
			margin: 0;
			font-family: Arial, Verdana, Times, Sans;
			background: #fff;
		}
	</style>
	<script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
	<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
	<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
	<script type='text/javascript'>
			//função para fechar a janela caso a telca ESC seja pressionada!
			$(window).keypress(function(e) {
				if(e.keyCode == 27) {
					window.parent.Shadowbox.close();
				}
			});

			$(document).ready(function() {
				$("#gridRelatorio").tablesorter();
			});
		</script>
	</head>

	<body>
		<div class="lp_header">
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>
		<?
		echo "<div class='lp_nova_pesquisa'>";
		echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST' name='nova_pesquisa'>";
		echo "<input type='hidden' name='forma' value='$forma' />";
		echo "<table cellspacing='1' cellpadding='2' border='0'>";
		echo "<tr>";
		echo "<td>
		<label>" . traduz("Código do Posto") . "</label>
		<input type='text' name='codigo' value='$codigo' style='width: 150px' maxlength='20' />
	</td>";
	echo "<td>
	<label>" . traduz("Nome") . "</label>
	<input type='text' name='nome' value='$nome' style='width: 370px' maxlength='80' />
</td>";
echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='" . traduz("Pesquisar Novamente") . "' /></td>";
echo "</tr>";
echo "</table>";
echo "</form>";
echo "</div>";

$campos_add = '';

	if (isset($_GET['completo']) || $login_fabrica == 7) { // Usado em cadastro_auditoria.php; HD #896786

		$campos_add = "tbl_posto_fabrica.contato_email,
		tbl_posto_fabrica.contato_endereco || ' - ' || tbl_posto_fabrica.contato_numero  AS contato_endereco,
		tbl_posto_fabrica.contato_complemento,
		tbl_posto_fabrica.contato_bairro,
		tbl_posto_fabrica.contato_cep,
		tbl_posto_fabrica.contato_fone_comercial,
		tbl_posto_fabrica.contato_fax,
		tbl_posto_fabrica.contato_nome,
		tbl_posto_fabrica.contato_cidade,
		tbl_posto_fabrica.contato_estado,";

	}

	if ($login_fabrica == 35 && $tela == 'os_cadastro') {
		$condCredenciamento = " AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'";
	}

	if (strlen($nome) > 2) {
		echo "<div class='lp_pesquisando_por'>". traduz("Pesquisando pelo nome:") . "$nome</div>";

		$sql = "SELECT
		tbl_posto.posto,
		tbl_posto.cnpj,
		tbl_posto.nome,
		tbl_posto.pais,
		tbl_posto_fabrica.contato_endereco AS endereco,
		tbl_posto_fabrica.contato_numero AS numero,
		tbl_posto_fabrica.contato_bairro AS bairro,
		tbl_posto_fabrica.contato_cidade AS cidade,
		tbl_posto_fabrica.contato_estado AS estado,
		tbl_posto_fabrica.contato_cep AS cep,
		tbl_posto_fabrica.codigo_posto,
		$campos_add
		tbl_posto_fabrica.credenciamento
		FROM     tbl_posto
		JOIN     tbl_posto_fabrica USING (posto)
		WHERE   (tbl_posto.nome ilike '%$nome%' OR tbl_posto_fabrica.nome_fantasia ILIKE '%$nome%' OR tbl_posto.nome_fantasia ILIKE '%$nome%')
		AND      tbl_posto_fabrica.fabrica = $login_fabrica
		$condCredenciamento
		ORDER BY tbl_posto.nome";

	}elseif (strlen($codigo) > 2) {
		$codigo_posto = trim (strtoupper($codigo));
		$codigo_posto = str_replace (".","",$codigo_posto);
		$codigo_posto = str_replace (",","",$codigo_posto);
		$codigo_posto = str_replace ("-","",$codigo_posto);
		$codigo_posto = str_replace ("/","",$codigo_posto);

		echo "<div class='lp_pesquisando_por'>". traduz("Pesquisando pelo código:") . "$codigo</div>";

		$sql = "SELECT
		tbl_posto.posto,
		tbl_posto.cnpj,
		tbl_posto.nome,
		tbl_posto.pais,
		tbl_posto_fabrica.contato_endereco AS endereco,
		tbl_posto_fabrica.contato_numero AS numero,
		tbl_posto_fabrica.contato_bairro AS bairro,
		tbl_posto_fabrica.contato_cidade AS cidade,
		tbl_posto_fabrica.contato_estado AS estado,
		tbl_posto_fabrica.contato_cep AS cep,
		tbl_posto_fabrica.codigo_posto,
		$campos_add
		tbl_posto_fabrica.credenciamento
		FROM tbl_posto
		JOIN tbl_posto_fabrica USING (posto)
		WHERE tbl_posto_fabrica.codigo_posto ILIKE '%$codigo_posto%'
		AND tbl_posto_fabrica.fabrica = $login_fabrica
		$condCredenciamento
		ORDER BY tbl_posto.nome";
	}else{
		echo "<div class='lp_msg_erro'>" . traduz("Informar toda ou parte da informação para realizar a pesquisa!") . "</div>";
		exit;
	}
	$res = pg_query($con, $sql);

	if (pg_numrows ($res) > 0 ) {?>
	<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
		<thead>
			<tr>
				<th><?php echo traduz("Código"); ?></th>
				<th><?php echo traduz("CNPJ"); ?></th>
				<th><?php echo traduz("Nome"); ?></th>
				<th><?php echo traduz("Cidade"); ?></th>
				<th><?php echo traduz("Estado"); ?></th>
			</tr>
		</thead>
		<tbody>
			<?
			for ($i = 0 ; $i < pg_num_rows($res); $i++) {

				$credenciamento = pg_result($res,$i,credenciamento);
				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$posto          = trim(pg_result($res,$i,posto));
				$nome           = trim(pg_result($res,$i,nome));
				$cnpj           = trim(pg_result($res,$i,cnpj));
				$endereco       = trim(pg_result($res,$i,endereco));
				$numero         = trim(pg_result($res,$i,numero));
				$bairro         = trim(pg_result($res,$i,bairro));
				$cidade         = trim(pg_result($res,$i,cidade));
				$estado         = trim(pg_result($res,$i,estado));
				$cep            = trim(pg_result($res,$i,cep));
				$nome           = str_replace("'", "\'", $nome);
				$pais           = pg_fetch_result($res, $i, "pais");

				if (!empty($campos_add)) {

					$endereco 		= trim(pg_result($res, $i, 'contato_endereco'));
					$bairro 		= trim(pg_result($res, $i, 'contato_bairro'));
					$cep 			= trim(pg_result($res, $i, 'contato_cep'));
					$fone_comercial = trim(pg_result($res, $i, 'contato_fone_comercial'));
					$fax 			= trim(pg_result($res, $i, 'contato_fax'));
					$email 			= trim(pg_result($res, $i, 'contato_email'));
					$contato		= trim(pg_result($res, $i, 'contato_nome'));
					$endereco 		.= ' ' . pg_result($res, $i, 'contato_complemento');
					$campos_add = ", '$endereco', '$bairro', '$cep', '$fone_comercial', '$fax', '$email', '$contato'";

					$cidade         = trim(pg_result($res,$i,'contato_cidade'));
					$estado         = trim(pg_result($res,$i,'contato_estado'));

				}

				$nome = str_replace("'", " ", $nome);
				$cidade = str_replace("'", " ", $cidade);

				if(pg_num_rows($res) == 1){
					echo "<script type='text/javascript'>";
					if ($login_fabrica == 52)
						echo "window.parent.retorna_posto('$codigo_posto','$posto',\"$nome\",'$cnpj',\"$cidade\",'$estado','$credenciamento','$num_posto' $campos_add); window.parent.Shadowbox.close();";
					else if ($login_fabrica == 11)
						echo "window.parent.retorna_posto('$codigo_posto','$posto',\"$nome\",'$cnpj',\"$cidade\",'$estado','$credenciamento','$num_posto', '$cep', \"$endereco\", '$numero', \"$bairro\",\"$pais\"); window.parent.fncMostraBuscaOS('$codigo_posto', '$nome'); window.parent.Shadowbox.close();";
					else
						echo "window.parent.retorna_posto('$codigo_posto','$posto',\"$nome\",'$cnpj',\"$cidade\",'$estado','$credenciamento','$num_posto', '$cep', \"$endereco\", '$numero', \"$bairro\", \"$pais\"); window.parent.Shadowbox.close();";

					echo "</script>";
				}

				if ($login_fabrica == 52)
					$onclick = "onclick= \"javascript: window.parent.retorna_posto('$codigo_posto','$posto','$nome','$cnpj','$cidade','$estado','$credenciamento','$num_posto' $campos_add); window.parent.Shadowbox.close();\"";
				else if ($login_fabrica == 11)
					$onclick = "onclick= \"javascript: window.parent.retorna_posto('$codigo_posto','$posto','$nome','$cnpj','$cidade','$estado','$credenciamento','$num_posto', '$cep', '$endereco', '$numero', '$bairro','$pais'); window.parent.fncMostraBuscaOS('$codigo_posto', '$nome'); window.parent.Shadowbox.close();\"";
				else
					$onclick = "onclick= \"javascript: window.parent.retorna_posto('$codigo_posto','$posto','$nome','$cnpj','$cidade','$estado','$credenciamento','$num_posto', '$cep', '$endereco', '$numero', '$bairro','$pais'); window.parent.Shadowbox.close();\"";

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				echo "<tr style='background: $cor' $onclick>";
				echo "<td>".verificaValorCampo($codigo_posto)."</td>";
				echo "<td>".verificaValorCampo($cnpj)."</td>";
				echo "<td>".verificaValorCampo($nome)."</td>";
				echo "<td>".verificaValorCampo($cidade)."</td>";
				echo "<td>".verificaValorCampo($estado)."</td>";
				echo "</tr>";
			}
			echo "</tbody>";
			echo "</table>";
		}else{
			echo "<div class='lp_msg_erro'>" . traduz("Nenhum resultado encontrado") . "</div>";
		}?>
	</body>
	</html>
