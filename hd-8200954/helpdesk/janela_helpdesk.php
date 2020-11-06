<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$TITULO = "Cadastro de Janelas do HelpDesk";

if (!empty($_POST['btn_acao'])) {

	//TODO - Validar datas
	$data_inicial = implode('-', array_reverse(explode('/', $_POST['data_inicial'])));
	$data_final   = implode('-', array_reverse(explode('/', $_POST['data_final'])));

	list($diy,$dim,$did) = explode('-', $data_inicial);
	list($dfy,$dfm,$dfd) = explode('-', $data_final);

	if (!checkdate($dim, $did, $diy) || !checkdate($dfm, $dfd, $dfy)) {
		$msg_erro = 'A data inválida!';
	} else if (strtotime($data_inicial) > strtotime($data_final)) {
		$msg_erro = 'A data inicial não pode ser maior que a Data final!';
	}

	if (empty($msg_erro)) {

		if ($_POST['fabricas'] != NULL) {
			$vet_fabricas = "'{" . implode(',', $_POST['fabricas']) . "}'";
		} else {
			$vet_fabricas = 'NULL';
		}

		$data_final .= ' 23:59:59';

		if (!empty($_POST['hd_janela'])) {

			$sql = "UPDATE tbl_hd_janela
					   SET data_inicial = '$data_inicial',
						   data_final   = '$data_final',
						   admin        = '$login_admin',
						   fabricas     = $vet_fabricas
					 WHERE hd_janela = $hd_janela";

			$status = 'atualizar';

		} else {

			$sql = "INSERT INTO tbl_hd_janela (
						data_inicial,
						data_final,
						admin,
						fabricas
					) VALUES (
						'$data_inicial',
						'$data_final',
						'$login_admin',
						$vet_fabricas
					);";

			$status = 'inserir';

		}

		$res = @pg_query($con, $sql);

		$msg_erro = strlen(pg_errormessage($con)) > 0 ? "Erro ao $status registro!" : '';

	}

	unset($data_inicial);
	unset($data_final);
	unset($vet_fabricas);

}

if ($_GET['acao'] == 'excluir' && !empty($_GET['hd_janela'])) {

	$hd_janela = (int) $_GET['hd_janela'];

	$sql = "DELETE FROM tbl_hd_janela WHERE hd_janela = " . $hd_janela;
	$res = @pg_query($con, $sql);

	$msg_erro = strlen(pg_errormessage($con)) > 0 ? "Erro ao excluir registro!" : '';

	unset($hd_janela);

}

include "../js/js_css.php";
?>

<style type="text/css">
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.formulario{
		background-color:#D9E2EF;
		font:12px Arial;
	}

	#fabricas {
		width: 300px;
	}

</style><?php

include "menu.php";?>

<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" charset="utf-8">
	$(function() {
		 Shadowbox.init();
		 $('#data_inicial').datepick({startDate:'01/01/2000'});
		 $('#data_final').datepick({startDate:'01/01/2000'});
		 $("#data_inicial").mask("99/99/9999");
		 $("#data_final").mask("99/99/9999");

	});

	function limpar() {
		window.location = window.location.pathname;
	}

	function editar_janela(hd_janela) {
		window.location = '?hd_janela='+hd_janela;
	}

	function excluir_janela(hd_janela) {

		if (confirm("Deseja realmente excluir este registro?")) {
			window.location = '?acao=excluir&hd_janela='+hd_janela;
		}

	}
</script><?php

if ($_REQUEST['hd_janela']) {
	
	$hd_janela = (int) $_REQUEST['hd_janela'];

	$sql = "SELECT TO_CHAR(data_inicial, 'DD/MM/YYYY') as data_inicial,
				   TO_CHAR(data_final, 'DD/MM/YYYY')   as data_final,
				   fabricas
			  FROM tbl_hd_janela
			 WHERE hd_janela = " . $hd_janela;

	$res = pg_exec($con, $sql);
	$tot = pg_num_rows($res);

	if ($tot) {

		for ($i = 0; $i < $tot; $i++) {

			$data_inicial = pg_result($res, $i, 'data_inicial');
			$data_final   = pg_result($res, $i, 'data_final');

			$fabrica     = str_replace('{', '', pg_result($res, $i, 'fabricas'));
			$fabrica     = str_replace('}', '', $fabrica);

			if (!empty($fabrica)) {
				$vet_fabrica_janela = explode(',', $fabrica);
			}

		}

	}

}

echo '<form name="frm_janela" method="POST" ACTION="'.$PHP_SELF.'">';
echo '<input type="hidden" name="hd_janela" id="hd_janela" value="'.$_REQUEST['hd_janela'].'" />';
echo '<table width="400" align="center" cellpadding="3" cellspacing="0" border="0">';
	echo '<tr>';
		echo '<td>Data Inicial:</td>';
		echo '<td><input type="text" name="data_inicial" id="data_inicial" size="10" value="'.$data_inicial.'" /></td>';
		echo '<td>Data Final:</td>';
		echo '<td><input type="text" name="data_final" id="data_final" size="10" value="'.$data_final.'" /></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<td valign="top">Fabricas:</td>';
		echo '<td colspan="3">';

			$sql = "SELECT * FROM tbl_fabrica WHERE ativo_fabrica IS TRUE ORDER BY nome;";
			$res = pg_query($con, $sql);
			$tot = pg_num_rows($res);

			if ($tot) {

				echo '<select name="fabricas[]" id="fabricas" multiple="multiple" style="height: 300px">';

				for ($i = 0; $i < $tot; $i++) {

					$fabrica               = pg_result($res, $i, 'fabrica');
					$nome_fabrica          = ucwords(strtolower(pg_result($res, $i, 'nome')));
					$vet_fabrica[$fabrica] = $nome_fabrica;
					$valor                 = '('.str_pad($fabrica, 3, '0', STR_PAD_LEFT).') - '.$nome_fabrica;

					foreach ($vet_fabrica_janela as $val) {

						if ($val == $fabrica) {
							$selected = ' selected="selected"';
							break;
						} else {
							$selected = '';
						}

					}

					echo '<option value="'.$fabrica.'"'.$selected.'>'.$valor.'</option>';

				}

				echo '</select>';

			}

		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<td style="text-align:center" colspan="4">';
			echo '<input type="submit" name="btn_acao" id="btn_acao" value="Gravar" />';
			echo '<input type="button" name="btn_limpar" id="btn_limpar" value="Limpar" onclick="limpar()" />';
		echo '</td>';
	echo '</tr>';
echo '</table>';
echo '</form>';

$sql = "SELECT TO_CHAR(data_inicial, 'DD/MM/YYYY HH24:MI') as data_inicial,
               TO_CHAR(data_final, 'DD/MM/YYYY HH24:MI')   as data_final,
               data_inicial                                as data_inicial2,
               tbl_admin.nome_completo                     as nome,
               fabricas,
               hd_janela
          FROM tbl_hd_janela
          JOIN tbl_admin ON tbl_admin.admin = tbl_hd_janela.admin
         ORDER BY data_inicial2 DESC;";

$res = pg_exec($con, $sql);
$tot = pg_num_rows($res);

if ($tot) {

	  echo '<table class="tablesorter" align="center" style="border:solid 0px black; background:#1D3C75; width:800px; margin:auto; !important">';

		echo '<tr style="color:white;font-size:13px;font-weight:bold;text-align:center">';
			echo '<th>Data Inicial</th>';
			echo '<th>Data Final</th>';
			echo '<th>Admin</th>';
			echo '<th>Fábricas</th>';
			echo '<th colspan="2">Ações</th>';
		echo '</tr>';

		for ($i = 0; $i < $tot; $i++) {

			$hd_janela    = pg_result($res, $i, 'hd_janela');
			$data_inicial = pg_result($res, $i, 'data_inicial');
			$data_final   = pg_result($res, $i, 'data_final');
			$nome         = pg_result($res, $i, 'nome');

			$fabricas     = str_replace('{', '', pg_result($res, $i, 'fabricas'));
			$fabricas     = str_replace('}', '', $fabricas);

			if (!empty($fabricas)) {
				$fabricas = explode(',', $fabricas);
			} else {
				$fabricas = 'Todas';
			}

			echo '<tbody>';
				echo '<tr style="font-size:14px;font-weight:bold">';
					echo '<td>'.$data_inicial.'</td>';
					echo '<td>'.$data_final.'</td>';
					echo '<td>'.$nome.'</td>';
					echo '<td>';

					if (is_array($fabricas)) {

						foreach ($fabricas as $id) {
							echo $vet_fabrica[$id] . '<br />';
						}

					} else {

						echo $fabricas;

					}

					echo '</td>';
					echo '<td align="center">';
						echo '<input type="button" name="btn_editar" id="btn_editar" value="Editar" onclick="editar_janela('.$hd_janela.')" />';
						echo '<input type="button" name="btn_excluir" id="btn_excluir" value="Excluir" onclick="excluir_janela('.$hd_janela.')" />';
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';

		}

	 echo '</table>';

}

include "rodape.php"; ?>

</body>
</html>
