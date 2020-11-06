<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';

include 'funcoes.php';

$msg_erro = "";

$layout_menu = "auditoria";
$title = "Relatório de OSs digitadas";

include "cabecalho.php";

echo "<font color = 'red'> Sistema em Manutenção!</font>";
exit;

include "javascript_pesquisas.php";

?>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>

<script language="JavaScript">
// ========= Função PESQUISA DE POSTO POR CÓDIGO OU NOME ========= //
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
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
	color:#000000;
	background-color: #d9e2ef
}
.topo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
</style>

<p>

<?
$btn_acao = strtolower($_GET['btn_acao']);

$posto_codigo = trim($_GET["posto_codigo"]);
$posto_nome   = trim($_GET["posto_nome"]);
$ano          = trim($_GET["ano"]);
$mes          = trim($_GET["mes"]);

if (strlen($posto_codigo) == 0 AND strlen($posto_nome) == 0 AND strlen($ano) == 0 AND strlen($mes) == 0 AND strlen($btn_acao) > 0)
	$msg_erro = " Preencha pelo menos um dos campos. ";

if (strlen($msg_erro) > 0) { ?>
<table width='600' align='center' border='0' cellspacing='2' cellpadding='2'>
	<tr class='error'>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<? } ?>



<br>

<?

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0){

	$posto_codigo     = trim($_GET["posto_codigo"]);
	$posto_nome       = trim($_GET["posto_nome"]);
	$ano              = trim($_GET["ano"]);
	$mes              = trim($_GET["mes"]);
	$produto_ref      = trim($_GET['produto_ref']);
	$tipo_atendimento = trim($_GET['tipo_atendimento']);
	$pais             = trim($_GET['pais']);

	if (strlen($mes) > 0 OR strlen($ano) > 0){
		if (strlen($mes) > 0) {
			if (strlen($mes) == 1) $mes = "0".$mes;
			$data_inicial = "2005-$mes-01 00:00:00";
			$data_final   = "2005-$mes-".date("t", mktime(0, 0, 0, $mes, 1, 2005))." 23:59:59";
		}
		if (strlen($ano) > 0) {
			$data_inicial = "$ano-01-01 00:00:00";
			$data_final   = "$ano-12-".date("t", mktime(0, 0, 0, 12, 1, 2005))." 23:59:59";
		}
		if (strlen($mes) > 0 AND strlen($ano) > 0) {
			$data_inicial = "$ano-$mes-01 00:00:00";
			$data_final   = "$ano-$mes-".date("t", mktime(0, 0, 0, $mes, 1, 2005))." 23:59:59";
		}
	}

	if (strlen($posto_codigo) > 0){
		$sqlPosto =	"SELECT posto
					FROM tbl_posto_fabrica
					WHERE codigo_posto = '$posto_codigo'
					AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sqlPosto);
		if (pg_numrows($res) == 1){
			$posto = pg_result($res,0,0);
		}
	}
			//TAKASHI COLOCOU CAMPO DIGITACAO DO ITEM
			//TULIO - Tornar obrigatoria digitacao de DATA INICIAL E FINAL - junho/2007

	$sql =	"SELECT tbl_os.sua_os                                                           ,
					tbl_os.consumidor_nome                                                  ,
					tbl_os.consumidor_fone                                                  ,
					tbl_os.serie                                                            ,
					tbl_os.pecas                                                            ,
					tbl_os.mao_de_obra                                                      ,
					to_char (tbl_os.data_digitacao,'DD/MM/YYYY')      AS data_digitacao     ,
					to_char (tbl_os.data_abertura,'DD/MM/YYYY')       AS data_abertura      ,
					to_char (tbl_os.data_fechamento,'DD/MM/YYYY')     AS data_fechamento    ,
					to_char (tbl_os.finalizada,'DD/MM/YYYY')          AS data_finalizada    ,
					to_char (tbl_os.data_nf,'DD/MM/YYYY')             AS data_nf            ,
					data_abertura::date - data_nf::date               AS dias_uso           ,
					tbl_produto.referencia                            AS produto_referencia ,
					tbl_produto.descricao                             AS produto_descricao  ,
					tbl_peca.referencia                               AS peca_referencia    ,
					tbl_peca.descricao                                AS peca_descricao     ,
					tbl_servico_realizado.descricao                   AS servico            ,
					tbl_defeito_constatado.descricao                  AS defeito_constatado ,
					TO_CHAR (tbl_os_item.digitacao_item,'DD/MM')      AS data_digitacao_item,
					tbl_posto_fabrica.codigo_posto                                          ,
					tbl_posto.nome AS nome_posto                                            ,
					tbl_posto.pais AS posto_pais                                            ,
					tbl_tipo_atendimento.codigo                       AS ta_codigo          ,
					tbl_tipo_atendimento.descricao                    AS ta_descricao
			FROM      tbl_os
			JOIN      tbl_produto             ON  tbl_os.produto              = tbl_produto.produto
			JOIN      tbl_posto               ON  tbl_os.posto                 = tbl_posto.posto
			JOIN      tbl_posto_fabrica       ON  tbl_posto.posto              = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_os_produto          ON  tbl_os.os                    = tbl_os_produto.os
			LEFT JOIN tbl_os_item             ON  tbl_os_produto.os_produto    = tbl_os_item.os_produto
			LEFT JOIN tbl_peca                ON  tbl_os_item.peca             = tbl_peca.peca
			LEFT JOIN tbl_defeito_constatado  ON  tbl_os.defeito_constatado    = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN tbl_servico_realizado   ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			LEFT JOIN tbl_tipo_atendimento    USING(tipo_atendimento)
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final' ";

	if (strlen($posto) > 0)             $sql .= " AND tbl_os.posto = $posto ";
	if (strlen($uf) > 0)                $sql .= " AND tbl_posto.estado = '$uf' ";
	if (strlen($produto_ref) > 0)       $sql .= " AND tbl_produto.referencia = '$produto_ref' " ;
	if (strlen($pais) > 0)              $sql .= " AND tbl_posto.pais = '$pais' " ;
	if (strlen($tipo_atendimento) > 0)  $sql .= " AND tbl_os.tipo_atendimento = '$tipo_atendimento' " ;
	$sql .= " ORDER BY tbl_os.sua_os;";

/*if($ip == '201.42.109.201'){
	echo nl2br($sql);
	exit;
}*/

#echo $sql;
#exit;
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		if($login_fabrica==20){
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$sua_os      = pg_result($res,$i,sua_os);
				$pecas       = pg_result($res,$i,pecas);
				$mao_de_obra = pg_result($res,$i,mao_de_obra);
				$vet_pecas[$sua_os]= $pecas;
				$vet_mao_de_obra[$sua_os]= $mao_de_obra;
			}
		}


		$data = date ("dmY");

		echo `rm /tmp/assist/relatorio_os_por_posto_peca-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/relatorio_os_por_posto_peca-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE OS's DIGITADAS - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");


		fputs ($fp,"<table width='700'>");
		fputs ($fp,"<tr class='menu_top'>");
		fputs ($fp,"<td nowrap>OS</td>");
		if($login_fabrica==20)		fputs ($fp,"<td nowrap>Tipo Atendimento</td>");
		fputs ($fp,"<td nowrap>CONSUMIDOR</td>");
		fputs ($fp,"<td nowrap>TELEFONE</td>");
		fputs ($fp,"<td nowrap>Nº SÉRIE</td>");
		fputs ($fp,"<td nowrap>DIGITAÇÃO</td>");
		fputs ($fp,"<td nowrap>ABERTURA</td>");
		fputs ($fp,"<td nowrap>FECHAMENTO</td>");
		fputs ($fp,"<td nowrap>FINALIZADA</td>");
		fputs ($fp,"<td nowrap>DATA NF</td>");
		fputs ($fp,"<td nowrap>DIAS EM USO</td>");
		fputs ($fp,"<td nowrap>PRODUTO REFERÊNCIA</td>");
		fputs ($fp,"<td nowrap>PRODUTO DESCRIÇÃO</td>");
		fputs ($fp,"<td nowrap>PEÇA REFERÊNCIA</td>");
		fputs ($fp,"<td nowrap>PEÇA DESCRIÇÃO</td>");
		/* IGOR HD 6161 - 23/10/2007*/
		if($login_fabrica == 20 ){
			fputs ($fp,"<td nowrap>VLR TOTAL MO</td>");
			fputs ($fp,"<td nowrap>VLR TOT. PEÇAS</td>");
		}
		//TAKASHI COLOCOU CAMPO DIGITACAO DO ITEM
		fputs ($fp,"<td nowrap>DATA ITEM</TD>");
		fputs ($fp,"<td nowrap>DEFEITO CONSTATADO</td>");
		fputs ($fp,"<td nowrap>SERVIÇO REALIZADO</td>");
		fputs ($fp,"<td nowrap>CÓDIGO POSTO</td>");
		fputs ($fp,"<td nowrap>RAZÃO SOCIAL</td>");
		if($login_fabrica == 20) 		fputs ($fp,"<td nowrap>PAÍS</td>");
				fputs ($fp,"</tr>");

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$sua_os             = pg_result($res,$i,sua_os);
			$consumidor_nome    = pg_result($res,$i,consumidor_nome);
			$consumidor_fone    = pg_result($res,$i,consumidor_fone);
			$serie              = pg_result($res,$i,serie);
			$data_digitacao     = pg_result($res,$i,data_digitacao);
			$data_abertura      = pg_result($res,$i,data_abertura);
			$data_fechamento    = pg_result($res,$i,data_fechamento);
			$data_finalizada    = pg_result($res,$i,data_finalizada);
			$data_nf            = pg_result($res,$i,data_nf);
			$dias_uso           = pg_result($res,$i,dias_uso);
			$produto_referencia = pg_result($res,$i,produto_referencia);
			$produto_descricao  = pg_result($res,$i,produto_descricao);
			$peca_referencia    = pg_result($res,$i,peca_referencia);
			$peca_descricao     = pg_result($res,$i,peca_descricao);
			$servico            = pg_result($res,$i,servico);
			$codigo_posto       = pg_result($res,$i,codigo_posto);
			$nome_posto         = pg_result($res,$i,nome_posto);
			$defeito_constatado	= pg_result($res,$i,defeito_constatado);
			//TAKASHI COLOCOU CAMPO DIGITACAO DO ITEM
			$data_digitacao_item= pg_result($res,$i,data_digitacao_item);

			$posto_pais         = pg_result($res,$i,posto_pais);
			$ta_codigo          = pg_result($res,$i,ta_codigo);
			$ta_descricao       = pg_result($res,$i,ta_descricao);

			if ($i % 2 == 0) $cor = '#F1F4FA';
			else             $cor = '#F7F5F0';

			if ($login_fabrica == 1) $sua_os = $codigo_posto.$sua_os;

			fputs ($fp,"<tr class='table_line' bgcolor='$cor'>");
			fputs ($fp,"<td nowrap align='center'>$sua_os</td>");
			if($login_fabrica == 20) 		fputs ($fp,"<td nowrap align='left'>$ta_codigo - $ta_descricao</td>");
			if ($ant_consumidor_nome == $consumidor_nome) 		fputs ($fp,"<td>&nbsp;</td>");
			else 		fputs ($fp,"<td nowrap align='left'>$consumidor_nome</td>");
			if ($ant_consumidor_fone == $consumidor_fone) 		fputs ($fp,"<td>&nbsp;</td>");
			else 		fputs ($fp,"<td nowrap align='center'>$consumidor_fone</td>");
			if ($ant_serie == $serie) 		fputs ($fp,"<td>&nbsp;</td>");
			else 		fputs ($fp,"<td nowrap align='center'>$serie</td>");
			if ($ant_data_digitacao == $data_digitacao) 		fputs ($fp,"<td>&nbsp;</td>");
			else 		fputs ($fp,"<td nowrap align='center'>$data_digitacao</td>");
			if ($ant_data_abertura == $data_abertura) 		fputs ($fp,"<td>&nbsp;</td>");
			else 		fputs ($fp,"<td nowrap align='center'>$data_abertura</td>");
			if ($ant_data_fechamento == $data_fechamento) 		fputs ($fp,"<td>&nbsp;</td>");
			else 		fputs ($fp,"<td nowrap align='center'>$data_fechamento</td>");
			if ($ant_data_finalizada == $data_finalizada) 		fputs ($fp,"<td>&nbsp;</td>");
			else 		fputs ($fp,"<td nowrap align='center'>$data_finalizada</td>");
			if ($ant_data_nf == $data_nf) 		fputs ($fp,"<td>&nbsp;</td>");
			else 		fputs ($fp,"<td nowrap align='center'>$data_nf</td>");
			if ($ant_dias_uso == $dias_uso) 		fputs ($fp,"<td>&nbsp;</td>");
			else 		fputs ($fp,"<td nowrap align='center'>$dias_uso</td>");
			if ($ant_produto_referencia == $produto_referencia) 		fputs ($fp,"<td>&nbsp;</td>");
			else 		fputs ($fp,"<td nowrap align='center'>$produto_referencia</td>");
			if ($ant_produto_descricao == $produto_descricao) 		fputs ($fp,"<td>&nbsp;</td>");
			else 		fputs ($fp,"<td nowrap align='left'>$produto_descricao</td>");
					fputs ($fp,"<td nowrap align='center'>$peca_referencia</td>");
					fputs ($fp,"<td nowrap align='left'>$peca_descricao</td>");
			/* IGOR HD 6161 - 23/10/2007*/
			if($login_fabrica == 20 ){
				fputs ($fp,"<td nowrap align='right'>".number_format($vet_pecas[$sua_os],2,',','.')."</td>");
				fputs ($fp,"<td nowrap align='right'>".number_format($vet_mao_de_obra[$sua_os],2,',','.')."</td>");
			}
			//DEPOIS DE IMPRIMIR, APAGA O VALOR PARA NÃO DUPLICAR QUANTO TIVER VARIAS OS_ITEM
			$vet_pecas[$sua_os]= "";
			$vet_mao_de_obra[$sua_os]= "";

			//TAKASHI COLOCOU CAMPO DIGITACAO DO ITEM
			fputs ($fp,"<td nowrap align='center'>$data_digitacao_item</td>");
			fputs ($fp,"<td nowrap align='left'>$defeito_constatado</td>");
			fputs ($fp,"<td nowrap align='left'>$servico</td>");
			fputs ($fp,"<td nowrap align='center'>$codigo_posto</td>");
			fputs ($fp,"<td nowrap align='left'>$nome_posto</td>");
			if($login_fabrica == 20) 		fputs ($fp,"<td nowrap align='left'>$posto_pais</td>");
			fputs ($fp,"</tr>");

		}

		fputs ($fp,"</table>");
		fclose ($fp);

		rename("/tmp/assist/relatorio_os_por_posto_peca-$login_fabrica.html", "/www/assist/www/admin/xls/relatorio_os_por_posto_peca-$login_fabrica.$data.xls");
//		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio_os_por_posto_peca-$login_fabrica.$data.xls /tmp/assist/relatorio_os_por_posto_peca-$login_fabrica.html`;
		flush();
		echo"<table  border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td><img src='imagens/excell.gif'></td><td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/relatorio_os_por_posto_peca-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";

		echo "<br>";
	}
}

echo "<br>";

include "rodape.php";
?>
