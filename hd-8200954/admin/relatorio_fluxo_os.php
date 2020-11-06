<?php
/**
 *
 * admin/relatorio_fluxo_os.php
 *
 * @author  Francisco Ambrozio
 * @version 2011.12.30
 *
 */

$admin_privilegios = 'financeiro';

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';

$layout_menu = 'financeiro';
$title = 'Relatório Fluxo de OSs';

include_once 'cabecalho.php';

$msg_erro = '';
$resultado = '';

date_default_timezone_set('America/Sao_Paulo');

if (!empty($_POST['btn_acao'])) {
	$codigo_posto = trim($_POST['codigo_posto']);
	$posto_nome   = trim($_POST['posto_nome']);

	if (!empty($codigo_posto) and !empty($posto_nome)) {
		$sql_posto = "SELECT posto FROM tbl_posto_fabrica JOIN tbl_posto USING (posto)
					WHERE codigo_posto = '$codigo_posto' and TRIM(nome) = '$posto_nome' and fabrica = $login_fabrica";
		$query_posto = pg_query($con, $sql_posto);

		if (pg_num_rows($query_posto) == 0) {
			$msg_erro.= 'Posto não encontrado.';
		} else {
			$posto = pg_fetch_result($query_posto, 0, 'posto');
			$sql_and = " AND tbl_posto_fabrica.posto = $posto ";
		}
	} else {
		$sql_and = '';
	}

	$ano_pesquisa = $_POST['ano_pesquisa'];
	$mes_pesquisa = $_POST['mes_pesquisa'];

	if (empty($ano_pesquisa)) {
		$msg_erro.= 'Informe o Ano.';
	}

	if (empty($mes_pesquisa)) {
		$msg_erro.= 'Informe o Mês.';
	}

	if (!checkdate($mes_pesquisa, 1, $ano_pesquisa)) {
		$msg_erro.= 'Período de pesquisa inválido.';
	}

	if (empty($msg_erro))  {

		if ($mes_pesquisa == "12") {
			$proximo_mes = "01";
			$proximo_ano = $ano_pesquisa + 1;
		} else {
			$proximo_mes = $mes_pesquisa + 1;
			$proximo_ano = $ano_pesquisa;
		}

		if ($mes_pesquisa < 10) {
			$mes_pesquisa = '0' . $mes_pesquisa;
		}

		if ($proximo_mes < 10) {
			$proximo_mes = '0' . $proximo_mes;
		}

		$inicio_pesquisa = $ano_pesquisa . '-' . $mes_pesquisa . '-01 00:00:00';
		$termino_pesquisa = $proximo_ano . '-' . $proximo_mes . '-01 00:00:00';

		$sql = "SELECT tbl_os.sua_os,
						tbl_linha.nome AS linha_nome,
						tbl_posto_fabrica.codigo_posto AS codigo,
						tbl_posto.nome AS posto,
						tbl_os.mao_de_obra AS valor_mo,
						TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS abertura,
						tbl_peca.referencia AS peca_referencia,
						tbl_peca.descricao AS peca_descricao,
						TO_CHAR(tbl_os_item.digitacao_item, 'DD/MM/YYYY') AS digitacao_item,
						TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY') AS data_conserto,
						TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY') AS fechamento,
						TO_CHAR(tbl_os.finalizada, 'DD/MM/YYYY') AS finalizada,
						TO_CHAR(tbl_extrato_conferencia.data_conferencia, 'DD/MM/YYYY') AS data_conferencia,
						TO_CHAR(tbl_extrato_conferencia.data_lancamento_nota, 'DD/MM/YYYY') AS emissao,
						TO_CHAR(tbl_extrato_agrupado.aprovado, 'DD/MM/YYYY') AS aprovado,
						TO_CHAR(tbl_extrato_conferencia.data_nf, 'DD/MM/YYYY') AS data_nf,
						TO_CHAR(tbl_extrato_conferencia.previsao_pagamento, 'DD/MM/YYYY') AS previsao_pagamento,
						tbl_admin.nome_completo as inspetor
					FROM tbl_os
					JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
					JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
					JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
					LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
					JOIN tbl_admin ON tbl_posto_fabrica.admin_sap = tbl_admin.admin AND tbl_admin.fabrica = $login_fabrica
					LEFT JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
					LEFT JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
					LEFT JOIN tbl_extrato_agrupado ON tbl_extrato_agrupado.extrato = tbl_extrato.extrato
					WHERE tbl_os.data_abertura BETWEEN '$inicio_pesquisa' AND '$termino_pesquisa'
					AND tbl_os.fabrica = $login_fabrica
					AND tbl_os.excluida = 'f'
					$sql_and
					ORDER BY tbl_os.data_abertura";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$arquivo_sem_ext = 'relatorio_fluxo_os-' . $login_admin;
			$arquivo_saida = 'xls/' . $arquivo_sem_ext . '.txt';
			$file = fopen($arquivo_saida, 'w');

			$header = "OS\tLinha\tCódigo Posto\tPosto\tMão-de-obra\tAbertura\tSolicitação Peça\tConserto\tFechamento\tFinalizada";
			$header.= "\tConferência\tAprovação\tEmissão\tLançamento NF\tPrevisão Pgto\tInspetor Responsável\n";

			fwrite($file, $header);

			while ($fetch = pg_fetch_array($res)) {
				$sua_os = $fetch['sua_os'];
				$linha = $fetch['linha_nome'];
				$codigo_resultado = $fetch['codigo'];
				$posto_nome_resultado = $fetch['posto'];
				$valor_mo = number_format($fetch['valor_mo'], 2, ',', '');
				$abertura = $fetch['abertura'];
				$solicitacao_peca = $fetch['peca_referencia'] . ' ' . trim($fetch['peca_descricao']) . ' - ' . $fetch['digitacao_item'];
				$peca = $fetch['peca'];
				$pedido = $fetch['pedido'];
				$data_conserto = $fetch['data_conserto'];
				$fechamento = $fetch['fechamento'];
				$finalizada = $fetch['finalizada'];
				$data_conferencia = $fetch['data_conferencia'];
				$aprovado = $fetch['aprovado'];
				$data_nf = $fetch['data_nf'];
				$previsao_pagamento = $fetch['previsao_pagamento'];
				$inspetor = $fetch['inspetor'];
				$emissao = $fetch['emissao'];

				$linha = "$sua_os\t$linha\t$codigo_resultado\t$posto_nome_resultado\t$valor_mo\t$abertura\t$solicitacao_peca\t$data_conserto\t$fechamento";
				$linha.= "\t$finalizada\t$data_conferencia\t$aprovado\t$emissao\t$data_nf\t$previsao_pagamento\t$inspetor\n";

				fwrite($file, $linha);

			}

			fclose($file);

			system("cd /var/www/assist/www/admin/xls/ && zip -o $arquivo_sem_ext.zip $arquivo_sem_ext.txt 1>/dev/null 2>/dev/null", $ret);

			if ($ret == 0) {
				$arquivo_download = 'xls/' . $arquivo_sem_ext . '.zip';
				unlink($arquivo_saida);
			} else {
				$arquivo_download = $arquivo_saida;
			}

			$resultado = '<br/>';
			$resultado.= '<p id="download">';
			$resultado.= '<a href="' .  $arquivo_download . '" style="text-decoration: none;"><img src="../imagens/excel.gif"><br/>Download do relatório.</a>';
			$resultado.= '</p>';

		} else {
			$resultado.= '<br/><br/>Nenhum resultado encontrado.';
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

	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,nome,credenciamento){
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
</style>

<?php

$curr_year = date('Y');
$curr_month = date('m');
$anos = range(2002, $curr_year);

$meses = array(1=>"Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

echo '<form name="frm_consulta" method="post" action="' , $_SERVER['PHP_SELF'] , '">';
echo '<table border="0" cellspacing="0" cellpadding="6" align="center" class="formulario" width="700">';
if (!empty($msg_erro)) {
	echo '<tr class="msg_erro"><td colspan="5">' , $msg_erro , '</td></tr>';
}
echo '<tr class="titulo_tabela">';
echo '<td colspan="5">Parâmetros de Pesquisa</td>';
echo '<tr>';

echo "<tr >";
echo "<td width='30'>&nbsp;</td>";
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
echo "</td>";
echo "<td align='left'>";
echo 'Mês <br/>';
echo '<select name="mes_pesquisa">';
if (empty($mes_pesquisa)) {
	$mes_pesquisa = $curr_month;
}

foreach ($meses as $idx => $mes) {
	echo '<option value="' , $idx , '"';
	if ($idx == $mes_pesquisa) {
		echo ' selected="SELECTED"';
	}
	echo '>' , $mes , '</option>';
}
echo '</select>';
echo "</td>";
echo "</tr>";

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

echo $resultado;

include_once 'rodape.php';

?>
