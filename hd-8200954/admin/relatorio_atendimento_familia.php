<?php
/**
 *
 * admin/relatorio_atendimento_familia.php
 *
 * @author  Francisco Ambrozio
 * @version 2012.02.08
 *
 */

$admin_privilegios = 'callcenter';

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';

$layout_menu = 'callcenter';
$title = 'Relatório de Atendimento por Família';

include_once 'cabecalho.php';

$msg_erro = '';
$array_result = array();
$pesquisou = 0;

date_default_timezone_set('America/Sao_Paulo');

$meses = array(1=>"Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (!empty($_POST['btn_acao'])) {

	if (!empty($_POST['agrupar'])) {
		$agrupar = $_POST['agrupar'];

		if ($agrupar <> 'f' and $agrupar <> 'l') {
			$msg_erro.= 'Agrupamento inválido.<br/>';
		}
	} else {
		$msg_erro.= 'Selecione um agrupamento: Família ou Linha.<br/>';
	}

	$codigo_posto = trim($_POST['codigo_posto']);
	$posto_nome   = trim($_POST['posto_nome']);

	$cond_codigo_posto = ' AND 1 = 1 ';
	$cond_posto_nome = ' AND 1 = 1 ';
	$pesquisa_posto = 0;

	if (!empty($codigo_posto)) {
		$cond_codigo_posto = " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
		$pesquisa_posto = 1;
	}

	if (!empty($posto_nome)) {
		$cond_posto_nome = " AND TRIM(tbl_posto.nome) = '$posto_nome'  ";
		$pesquisa_posto = 1;
	}

	if ($pesquisa_posto == 1) {
		$sql_posto = "SELECT posto FROM tbl_posto_fabrica JOIN tbl_posto USING (posto)
					  WHERE tbl_posto_fabrica.fabrica = $login_fabrica $cond_codigo_posto $cond_posto_nome";
		$query_posto = pg_query($con, $sql_posto);

		if (pg_num_rows($query_posto) == 0) {
			$msg_erro.= 'Posto não encontrado.<br/>';
		} else {
			$posto = pg_fetch_result($query_posto, 0, 'posto');
			$sql_and = " AND tbl_posto_fabrica.posto = $posto ";
		}
	} else {
		$sql_and = '';
	}

	$ano_pesquisa = $_POST['ano_pesquisa'];

	if (empty($ano_pesquisa)) {
		$msg_erro.= 'Informe o Ano.<br/>';
	}

	if (empty($msg_erro)) {

		$pesquisou = 1;

		$cond_posto = '';

		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = $4 ";
		}

		switch ($agrupar) {
			case 'f':
				$prepare = pg_prepare($con, "query", 'SELECT count(os) AS qtde, tbl_familia.descricao AS descricao FROM tbl_os JOIN tbl_produto USING (produto) JOIN tbl_familia USING (familia) WHERE tbl_os.fabrica = $1 AND tbl_os.data_abertura BETWEEN $2 AND $3 ' . $cond_posto . ' GROUP BY tbl_familia.descricao ORDER BY tbl_familia.descricao');
				$agrupado_por = 'Família';
				$todas = "SELECT descricao FROM tbl_familia WHERE fabrica = $login_fabrica ORDER BY descricao";
				break;
			case 'l':
				$prepare = pg_prepare($con, "query", 'SELECT count(os) AS qtde, tbl_linha.nome AS descricao FROM tbl_os JOIN tbl_produto USING (produto) JOIN tbl_linha USING(linha) WHERE tbl_os.fabrica = $1 AND tbl_os.data_abertura BETWEEN $2 AND $3 ' . $cond_posto . ' GROUP BY tbl_linha.nome ORDER BY tbl_linha.nome');
				$agrupado_por = 'Linha';
				$todas = "SELECT nome FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY nome";
				break;
			default:
				$prepare = '';
				$agrupado_por = '';
				$todas = '';
		}

		$familias_linhas = array();
		$query = pg_query($con, $todas);
		while ($fetch = pg_fetch_array($query)) {
			$familias_linhas[$fetch[0]] = 0;
		}

		foreach ($meses as $k => $val) {
			if ($k == 12) {
				$proximo_mes = '1';
				$proximo_ano = $ano_pesquisa + 1;
			} else {
				$proximo_mes = $k + 1;
				$proximo_ano = $ano_pesquisa;
			}

			$proximo_mes = sprintf("%02d", $proximo_mes);
			$mes_pesquisa = sprintf("%02d", $k);

			$data_inicial = $ano_pesquisa . '-' . $mes_pesquisa . '-01 00:00:00';
			$data_final = $proximo_ano . '-' . $proximo_mes . '-01 00:00:00';

			$parametros = array($login_fabrica, $data_inicial, $data_final);

			if (!empty($posto)) {
				array_push($parametros, $posto);
			}

			$execute = pg_execute($con, "query", $parametros);

			if (pg_num_rows($execute) > 0) {
				while ($fetch = pg_fetch_array($execute)) {
					$familias_linhas[$fetch['descricao']] = $fetch['qtde'];
				}
				$array_result[$k] = $familias_linhas;
			}

		}

	}
}

?>

<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>

<script type="text/javascript">
	$().ready(function(){  Shadowbox.init(); });

	function pesquisaPosto(campo,tipo){
        var campo = campo.value;

        if (jQuery.trim(campo).length > 2){
            Shadowbox.open({
                content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
                player:	    "iframe",
                title:		"Pesquisa Posto",
                width:	    800,
                height:	    500
            });
        }else
            alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}

	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento,num_posto){
        gravaDados('codigo_posto',codigo_posto);
        gravaDados('posto_nome',nome);
    }

    function gravaDados(name, valor){
        try{
            $("input[name="+name+"]").val(valor);
        } catch(err){
            return false;
        }
    }


</script>

<style type="text/css">
	.menu_top {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: bold;
		border: 1px solid;
		color:#ffffff;
		background-color: #596D9B
	}

	.table_line {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		border: 0px solid;
	}

	.esconde{
		display:none;
		text-align: center;
		font-family: Verdana, Tahoma, Arial, Geneva, Helvetica, sans-serif;
		font-size: 10 px;
	}

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
		font:11px Arial;
	}

	.subtitulo{
		background-color: #7092BE;
		font:bold 11px Arial;
		color: #FFFFFF;
		text-align:center;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	.bold { font-weight: bold; }
	.left { text-align: left; padding: 0px 10px; }

</style>

<?php

$curr_year = date('Y');
$curr_month = date('m');
$anos = range(2009, $curr_year);

echo '<form name="frm_consulta" method="post" action="' , $_SERVER['PHP_SELF'] , '">';
echo '<table border="0" cellspacing="0" cellpadding="6" align="center" class="formulario" width="700">';
if (!empty($msg_erro)) {
	echo '<tr class="msg_erro"><td colspan="5">' , $msg_erro , '</td></tr>';
}
echo '<tr class="titulo_tabela">';
echo '<td colspan="5">Parâmetros de Pesquisa</td>';
echo '</tr>';

echo '<tr>';
	echo "<td width='30'>&nbsp;</td>";
	echo "<td align='left'>";
		echo 'Agrupar por<br/>';
		echo '<input type="radio" name="agrupar" value="f" ';
		if ($agrupar == 'f') {
			echo ' checked="checked" ';
		}
		echo '/> Família<br/>';
		echo '<input type="radio" name="agrupar" value="l" ';
		if ($agrupar == 'l') {
			echo ' checked="checked" ';
		}
		echo '/> Linha<br/>';
	echo "</td>";
	echo "<td align='left'>";
		echo 'Ano <br/>';
		echo '<select name="ano_pesquisa">';
		if (empty($ano_pesquisa)) {
			$ano_pesquisa = $curr_year;
		}

		foreach ($anos as $ano) {
			echo '<option value="' , $ano , '"';
			if ($ano == $ano_pesquisa) {
				echo ' selected="SELECTED"';
			}
			echo '>' , $ano , '</option>';
		}
		echo '</select>';
echo '</tr>';

echo "<tr >";
echo "<td width='30'>&nbsp;</td>";
echo "<td align='left'>";
echo "Código Posto <br><input type='text' name='codigo_posto' size='15' value='$codigo_posto' class='frm'>";
echo "<img border='0' src='imagens/lupa.png' style='cursor: pointer;' align='absmiddle' alt='Clique aqui para pesquisar postos pelo código' onclick='javascript: pesquisaPosto(document.frm_consulta.codigo_posto, \"codigo\")'>";
echo "</td>";
echo "<td align='left'>";
echo "Nome Posto<br><input type='text' name='posto_nome' size='30' value='$posto_nome' class='frm'>";
echo "<img border='0' src='imagens/lupa.png' style='cursor: pointer;' align='absmiddle' alt='Clique aqui para pesquisar postos pelo código' onclick='javascript: pesquisaPosto (document.frm_consulta.posto_nome, \"nome\")'>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td colspan='3' align='center'><input type='submit' name='btn_acao' value='Pesquisar'><br><br></td>";
echo "</tr>";

echo '</table>';
echo '</form>';

if (!empty($array_result)) {

	$array_totais = array();

	echo '<br/>';

	echo '<div align="center">';
		echo 'Relatório de Atendimento por ' , $agrupado_por , ' - Ano: <strong>' , $ano_pesquisa;
		if (!empty($posto)) {
			echo '<br/>' , $codigo_posto , ' - ' , $posto_nome;
		}
		echo '</strong>';
	echo '</div><br/>';

	echo "<table class='tabela' align='center'>";

	echo '<tr class="titulo_tabela bold">';
		echo '<td>&nbsp;</td>';

		foreach ($array_result as $mes => $resultado) {
			echo '<td colspan="2">' , strtoupper(substr($meses[$mes], 0, 3)) , '</td>';
			$array_totais[$mes] = array_sum($resultado);
		}

		echo '<td colspan="2">TOT</td>';
	echo '</tr>';

	echo '<tr class="titulo_coluna bold">';
		echo '<td class="left">' , $agrupado_por , '</td>';

		foreach ($array_result as $resultado) {
			echo '<td style="width: 40px;">Qtde</td>';
			echo '<td style="width: 40px;">%</td>';
		}

		echo '<td style="width: 40px;">Qtde</td>';
		echo '<td style="width: 40px;">%</td>';

	echo '</tr>';

	$f = count($familias_linhas);
	$total_geral = array_sum($array_totais);

	for ($i = 0; $i < $f; $i++) {
		$total_linha = 0;
		echo '<tr>';
			echo '<td class="left">' , key($familias_linhas) , '</td>';
			foreach ($array_result as $m => $v) {
				echo '<td>' , $v[key($familias_linhas)]  , '</td>';
				$porcento = ($v[key($familias_linhas)]/$array_totais[$m]) * 100;
				echo '<td>' , number_format(round($porcento, 2), 2, ',', '') , '</td>' ;
				$total_linha+= $v[key($familias_linhas)];
			}
			echo '<td>' , $total_linha , '</td>';
			$porcento = ($total_linha/$total_geral) * 100;
			echo '<td>' , number_format(round($porcento, 2), 2, ',', '') , '</td>' ;
			next($familias_linhas);
		echo '</tr>';
	}

	echo '<tr class="titulo_coluna">';
		echo '<td class="bold left">Total</td>';
		foreach ($array_totais as $tot) {
			echo '<td class="bold">' , $tot , '</td>';
			echo '<td>100</td>';
		}
		echo '<td class="bold">' , $total_geral , '</td>';
		echo '<td>100</td>';
	echo '</tr>';

	echo "</table>";

	echo '<br/><br/>';

}
else if ($pesquisou == 1) {
	echo '<br/><br/>Nenhum resultado encontrado.<br/><br/>';
}

include_once 'rodape.php';

?>
