<?
//CHAMADO:		134895
//PROGRAMADOR:	EBANO LOPES
//SOLICITANTE:	11 - LENOXX

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';

$admin_privilegios="callcenter";
$layout_menu = "callcenter";
$title = "RELATÓRIO DE ATENDIMENTOS POR ATENDENTE";

include "cabecalho.php";
include "javascript_pesquisas.php";
include "javascript_calendario.php";

$btn_acao = $_POST['btn_acao'];

if ($btn_acao=="Pesquisar")
{
	$xdata_inicial	= implode("-", array_reverse(explode("/", $_POST["data_inicial"]))) . " 00:00:00";
	$xdata_final	= implode("-", array_reverse(explode("/", $_POST["data_final"]))) . " 23:59:59";

        //VALIDANDO AS DATAS
        $sql = "SELECT '$xdata_inicial'::timestamp, '$xdata_final'::timestamp";
        @$res = pg_query($sql);
        if (!$res)
        {
		$msg_erro = "Informe uma data válida";
		$btn_acao = "";
	}

}

?>

<!-- ******************************** JAVASCRIPT ******************************** -->

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script language='javascript' src='../ajax.js'></script>


<!-- ******************************** FIM JAVASCRIPT ******************************** -->

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />

<style>
.contitulo {
	text-align: center;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}

.linha0 {
	background-color: #F1F4FA;
}

.linha1 {
	background-color: #E6EEF7;
}

.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 10px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid;
	BORDER-TOP: #990000 1px solid;
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid;
	BORDER-BOTTOM: #990000 1px solid;
	BACKGROUND-COLOR: #FF0000;
}
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid;
	BORDER-TOP: #aaa 1px solid;
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid;
	BORDER-BOTTOM: #aaa 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}

.relerro{
	color: #FF0000;
	font-size: 11pt;
	padding: 20px;
	background-color: #F7F7F7;
	text-align: center;
}
</style>

<?
	if(strlen($msg_erro)>0){
		echo "<div class='relerro'>$msg_erro</div>";
	}
?>

<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<div>
Informe o período desejado
</div>
<div id='erro' style='position: absolute; top: 150px; left: 80px;visibility:hidden;opacity:.85;' class='Erro'><?php echo $msg_erro; ?></div>
<div id='carregando' align='center' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
<table width='450' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'><?=$title?></td>
	</tr>

	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>
			<table width='100%' border='0' cellspacing='1' cellpadding='2' >
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right'><font size='2'>Data Inicial</td>
					<td align='left'>
						<input type="text" id="data_inicial" name="data_inicial" size="12" maxlength="7" class="Caixa" value="<?=$data_inicial?>">
					</td>
					<td width="10">&nbsp;</td>
					<td align='right'><font size='2'>Data Final</td>
					<td align='left'>
						<input type="text" id="data_final" name="data_final" size="12" maxlength="7" class="Caixa" value="<?=$data_final?>">
					</td>
					<td width="10">&nbsp;</td>
				</tr>
			</table><br>
			<input type='submit' value='Pesquisar' id='btn_acao' name='btn_acao'>
		</td>
	</tr>
</table>
</FORM>

<?

if (strlen ($btn_acao) > 0) {
	
	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0){
		$sql = "
			SELECT
				tbl_admin.admin,
				tbl_admin.login AS nome_usuario,
				tbl_admin.nome_completo,
				COUNT(hd_chamado_item) AS interacoes
			
			FROM	tbl_hd_chamado_item
			JOIN	tbl_admin ON tbl_hd_chamado_item.admin = tbl_admin.admin
			
			WHERE	tbl_admin.fabrica=$login_fabrica
					AND tbl_hd_chamado_item.data>='$xdata_inicial'
					AND tbl_hd_chamado_item.data<='$xdata_final'
			
			GROUP BY tbl_admin.admin, tbl_admin.login, tbl_admin.nome_completo
			
			ORDER BY tbl_admin.nome_completo
			";
		$res = pg_exec($con, $sql);

		echo "
		<table align=center class=Conteudo>
		<tr class='Titulo'>
			<td width=80>
			Login
			</td>
			<td width=175>
			Nome Completo
			</td>
			<td width=90>
			Interações
			</td>
			<td width=90>
			Chamados Abertos
			</td>
		</tr>";

		for($i = 0; $i < pg_num_rows($res); $i++)
		{
			$xadmin		= pg_result($res, $i, admin);
			$nome_usuario	= pg_result($res, $i, nome_usuario);
			$nome_completo	= pg_result($res, $i, nome_completo);
			$interacoes	= pg_result($res, $i, interacoes);

			$sql = "
			SELECT
			COUNT(tbl_hd_chamado.hd_chamado) AS chamados
			
			FROM
			tbl_hd_chamado
			
			WHERE
			tbl_hd_chamado.fabrica = $login_fabrica
			AND tbl_hd_chamado.data between '$xdata_inicial' and '$xdata_final'
			AND admin = $xadmin
			";
			$res_chamados = pg_query($con, $sql);
			$chamados = pg_result($res_chamados, 0, 0);

			$linha_css = "linha" . $i % 2;

			echo "
			<tr class='$linha_css'>
				<td>
				$nome_usuario
				</td>
				<td>
				$nome_completo
				</td>
				<td>
				$interacoes
				</td>
				<td>
				$chamados
				</td>
			</tr>";
		}
	}
}
?>

<? include "rodape.php" ?>
