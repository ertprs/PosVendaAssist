<?php
session_start();

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

//Só para testes
include "helpdesk/mlg_funciones.php";


if (is_null(getPost("pais")) or getPost("pais") == '') $pais = $login_pais;
if ($pais == '') $pais = 'BR';


$visual_black = "manutencao-admin";

$title     = "Lista de Representantes" ; 

$layout_menu = "cadastro";
include 'cabecalho.php';
?>

<?php
	include 'js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>


<script language='javascript' src='ajax_cep.js'></script>
<script type="text/javascript">
<?php
if ($pais == "BR") {
?>
	$(function () {
		$("#estado").change(function () {
			if ($(this).val().length > 0) {
				$("#cidade").removeAttr("readonly");
			} else {
				$("#cidade").attr({"readonly": "readonly"});
			}
		});	
	});
<?php
}
?>
</script>

<style type="text/css">
/*  Autocomplete    */
@import url(/assist/js/jquery.autocomplete.css);
/*  Form e table    */
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef;
    text-transform: uppercase;
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_lst {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef;
    text-transform: uppercase;
}

img, input[type=image] {border: 0 solid transparent}

.line_lst {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}
.line_lst td {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
    width: 700px;
    padding: 2px;
}
</style>
<?php
	if (strlen ($msg_erro) > 0 OR count($msg_erro)) {
		if (count($msg_erro)){
			$msg_erro = implode('<br>', $msg_erro);
		}

		echo "<div class='error' style='padding: 2px 0; width: 700px;'>{$msg_erro}</div>";
	} 

	if(@$_SESSION['msg_sucesso']){
		echo "<div class='sucesso'>{$_SESSION['msg_sucesso']}</div>";	
		unset($_SESSION['msg_sucesso']);
	}
?>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="5"class="menu_top">
			<font color='#36425C'><? echo "LISTA DE REPRESENTANTES";?></font>
		</td>
	</tr>
	<tr class="menu_top">
		<td>#</td>
		<td>Código</td>
		<td>Representação</td>
		<td>Contato</td>
		<td>CNPJ</td>
	</tr>
	<?php
		$sql = "SELECT * from tbl_representante where fabrica = $login_fabrica and ativo = 't' order by nome";
		$res = pg_query($sql);

		for($i=0; $i<pg_num_rows($res); $i++){
			$representante 		= pg_fetch_result($res, $i, 'representante');
			$codigo 			= pg_fetch_result($res, $i, 'codigo');
			$nome 		 		= pg_fetch_result($res, $i, 'nome');
			$contato 		 	= pg_fetch_result($res, $i, 'contato');
			$cnpj				= pg_fetch_result($res, $i, 'cnpj');

			echo "<tr class='table_line'>";
				echo "<td>".($i+1)."</td>";
				echo "<td>{$codigo}</td>";
				echo "<td>{$nome}</td>";
				echo "<td>{$contato}</td>";
				echo "<td>{$cnpj}</td>";
			echo "</tr>";
		}
	?>
</table>

<? include "rodape.php"; ?>
