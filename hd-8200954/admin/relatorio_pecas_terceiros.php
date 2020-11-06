<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

include "funcoes.php";
include "monitora.php";

if ( in_array($login_fabrica, array(11,172)) ){
	$posto_da_fabrica = "20321";
}

if (strlen($_POST["acao"]) > 0 ) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0 )  $acao = strtoupper($_GET["acao"]);

if (strlen($acao) > 0 && $acao == "PESQUISAR") {

	if (strlen(trim($_POST["data_inicial"])) > 0) $x_data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0) $x_data_inicial = trim($_GET["data_inicial"]);

	if (strlen(trim($_POST["data_final"])) > 0) $x_data_final = trim($_POST["data_final"]);
	if (strlen(trim($_GET["data_final"])) > 0) $x_data_final = trim($_GET["data_final"]);

	if (strlen(trim($_POST["codigo_posto"])) > 0) $codi_posto = trim($_POST["codigo_posto"]);
	if (strlen(trim($_GET["codigo_posto"])) > 0)  $codi_posto = trim($_GET["codigo_posto"]);

	if (strlen($codi_posto)>0){
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto AS cod, tbl_posto.nome as nome, tbl_posto.posto as posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica=$login_fabrica
				AND tbl_posto_fabrica.codigo_posto = '$codi_posto'";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res)>0){
			$posto_codigo = pg_result ($res,0,cod);
			$posto_nome   = pg_result ($res,0,nome);
			$posto        = pg_result ($res,0,posto);
			$sql_posto         = " AND tbl_faturamento.posto =  $posto";
			$sql_posto_distrib = " AND tbl_faturamento.distribuidor =  $posto";
		}else{
			$sql_posto = " AND 1=2 ";
		}
	}
}

$layout_menu = "gerencia";
$title = "Relatório de Peças em Poder de Terceiros";
?>
<?
include "cabecalho.php";
?>


<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$("input[@rel='data']").datePicker({startDate:'01/01/2000'});
		$("input[@rel='data']").maskedinput("99/99/9999");
	});
</script>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<script>
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}
</script>

<br>

<? if (strlen($erro) > 0) { ?>
<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" class="Error">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">
<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Relatório de Peças em Poder de Terceiros</td>
	</tr>

	<tr>
		<td bgcolor='#DBE5F5'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>
				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Data Inicial:&nbsp;</td>
					<td colspan='2' align='left'>
						<input class='frm' type="text" name="data_inicial" rel="data" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_inicial; ?>">

					</td>
				</tr>
				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Data Final:&nbsp;</td>
					<td colspan='2' align='left'>
						<input class='frm' type="text" name="data_final" rel="data" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" >
					</td>
				</tr>
				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Código Posto:&nbsp;</td>
					<td colspan='2' align='left'>
						<input class="frm" type="text" name="codigo_posto" size="10" value="<? echo $codi_posto ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></A>
					</td>
				</tr>
				<tr>
					<td colspan='2' align='right'>Razão Social:&nbsp;</td>
					<td colspan='2' align='left'><input class="frm" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
				</tr>

				<tr bgcolor="#D9E2EF">
					<td colspan="4" align="center" ><br><img border="0" src="imagens/btn_pesquisar_400.gif"
					onClick="if (document.frm_relatorio.acao.value=='PESQUISAR')
					alert('Aguarde submissão');
					else{
					document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();}" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
				</tr>

			</table>
		</td>
	</tr>
</table>

<br>

<?
if (strlen($acao) > 0 && strlen($erro) == 0) {

	if (strlen($posto)>0) {
		$cond_posto  = " AND tbl_faturamento.distribuidor = $posto";
		$cond_posto2 = " AND tbl_extrato_lgr.posto = $posto";
	}

	$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
	$x_data_final   = fnc_formata_data_pg($x_data_final);

	if (strlen($x_data_inicial)>0 AND strlen($x_data_final)>0 AND $x_data_inicial != "null" AND $x_data_final != "null" ){
		$sql_data = " AND tbl_faturamento.emissao BETWEEN $x_data_inicial AND $x_data_final";
	}

	/* TODO O SQL FOI ALTERADO - HD 45765 - PARA VER SQL ANTERIOR, VEJA PROGRAMA relatorio_pecas_terceiros-20081024.php */
	$sql = "/* NF Enviadas AO posto */
			SELECT	tbl_faturamento.extrato_devolucao,
					tbl_faturamento.posto,
					tbl_faturamento.nota_fiscal,
					tbl_faturamento_item.peca,
					sum (tbl_faturamento_item.qtde)
			INTO TEMP tmp_lenoxx_pecas_terceiro1
			FROM tbl_faturamento
			JOIN tbl_faturamento_item     USING (faturamento)
			JOIN tbl_peca                 USING (peca)
			WHERE tbl_faturamento.fabrica = $login_fabrica
			$sql_posto
			$sql_data
			/*AND  tbl_faturamento.extrato_devolucao IS NOT NULL*/
			AND  tbl_faturamento.emissao > '2007-08-30'
			AND  tbl_peca.devolucao_obrigatoria IS TRUE
			AND  tbl_faturamento.distribuidor IS NULL
			AND  tbl_faturamento.cancelada is null
			AND  (tbl_faturamento.cfop LIKE '59%' OR tbl_faturamento.cfop LIKE '69%')
			AND  (tbl_peca.devolucao_obrigatoria IS TRUE)
			GROUP BY	tbl_faturamento.extrato_devolucao,
						tbl_faturamento.posto,
						tbl_faturamento.nota_fiscal,
						tbl_faturamento_item.peca
			;

			/* NF Emitidas pelo Posto - Devolucao */
			SELECT 	tbl_faturamento.extrato_devolucao,
					tbl_faturamento.distribuidor As posto,
					tbl_faturamento_item.nota_fiscal_origem,
					tbl_faturamento_item.peca,
					SUM (case when tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END)
			INTO TEMP tmp_lenoxx_pecas_terceiro2
			FROM tbl_faturamento
			JOIN tbl_faturamento_item     USING (faturamento)
			JOIN tbl_peca                 USING (peca)
			WHERE tbl_faturamento.fabrica    = $login_fabrica
			$sql_posto_distrib
			/*$sql_data*/
			AND tbl_faturamento.extrato_devolucao IS NOT NULL
			AND tbl_peca.devolucao_obrigatoria    IS TRUE
			AND     tbl_faturamento.cancelada     IS NULL
			AND    (tbl_peca.devolucao_obrigatoria IS TRUE)
			GROUP BY	tbl_faturamento.extrato_devolucao,
						tbl_faturamento.distribuidor,
						tbl_faturamento_item.nota_fiscal_origem,
						tbl_faturamento_item.peca
			;

			SELECT	tmp_lenoxx_pecas_terceiro1.posto,
					tmp_lenoxx_pecas_terceiro1.peca,
					SUM(tmp_lenoxx_pecas_terceiro1.sum - CASE WHEN tmp_lenoxx_pecas_terceiro2.sum IS NULL THEN 0 ELSE tmp_lenoxx_pecas_terceiro2.sum END) AS qtde_total
			INTO TEMP tmp_lenoxx_pecas_terceiro3
			FROM tmp_lenoxx_pecas_terceiro1
			LEFT JOIN tmp_lenoxx_pecas_terceiro2 ON tmp_lenoxx_pecas_terceiro2.posto = tmp_lenoxx_pecas_terceiro1.posto
							AND tmp_lenoxx_pecas_terceiro2.nota_fiscal_origem   = tmp_lenoxx_pecas_terceiro1.nota_fiscal
							AND tmp_lenoxx_pecas_terceiro2.peca                 = tmp_lenoxx_pecas_terceiro1.peca
			GROUP BY	tmp_lenoxx_pecas_terceiro1.posto,
						tmp_lenoxx_pecas_terceiro1.peca
			";
	#echo nl2br($sql);
	$res = pg_exec($con, $sql);

	$sql = "SELECT tbl_posto_fabrica.posto                  ,
					tbl_posto_fabrica.codigo_posto          ,
					tbl_posto.nome                          ,
					tbl_peca.peca                           ,
					tbl_peca.referencia                     ,
					tbl_peca.descricao                      ,
					tmp_lenoxx_pecas_terceiro3.qtde_total
			FROM tmp_lenoxx_pecas_terceiro3
			JOIN tbl_posto           ON tbl_posto.posto         = tmp_lenoxx_pecas_terceiro3.posto
			JOIN tbl_posto_fabrica   ON tbl_posto_fabrica.posto = tmp_lenoxx_pecas_terceiro3.posto
			JOIN tbl_peca            ON tbl_peca.peca           = tmp_lenoxx_pecas_terceiro3.peca
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica
			AND tmp_lenoxx_pecas_terceiro3.qtde_total > 0
			";
#echo nl2br($sql);

	##### PAGINAÇÃO - INÍCIO #####

		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";

		require "_class_paginacao.php";

		// definicoes de variaveis
		$max_links = 11;				// máximo de links à serem exibidos
		$max_res   = 150;				// máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		#echo nl2br($sql);

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	##### PAGINAÇÃO - FIM #####


	if (pg_numrows($res) > 0) {
		echo "<table style='font-family: verdana ; font-size: 10px; border-collapse: collapse' align='center'  bordercolor='#d2e4fc' border='1'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='11' height='25'>RELAÇÃO DE PEÇAS PENDENTES</td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td>Código Posto</td>";
		echo "<td>Nome Posto</td>";
		echo "<td>Peça</td>";
		echo "<td>Qtde</td>";
		echo "</tr>";

		$posto_ant = "";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$codigo_posto	= trim(pg_result($res,$i,codigo_posto));
			$nome			= trim(pg_result($res,$i,nome));
			$peca			= trim(pg_result($res,$i,peca));
			$referencia		= trim(pg_result($res,$i,referencia));
			$descricao		= trim(pg_result($res,$i,descricao));
			$qtde			= trim(pg_result($res,$i,qtde_total));

			if($cor=="#F1F4FA")     $cor = '#F7F5F0';
			else                    $cor = '#F1F4FA';

			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td nowrap align='center'>";if ($nome!=$posto_ant) echo $codigo_posto; echo "</td>";
			echo "<td nowrap align='left'>";if ($nome!=$posto_ant) echo $nome; echo "</td>";
			echo "<td nowrap align='left'>".$referencia . " - ".$descricao."</td>";
			echo "<td nowrap align='center'>".$qtde ."</td>";
			echo "</tr>";

			$posto_ant = $nome;

		}
		echo "</table>";
		echo "<br>";
	}else{
		echo "<br><br><center><b class='Conteudo'>Nenhuma pendência encontrada</b></center><br><br>";
	}

	// ##### PAGINACAO ##### //

		echo "<br>";
		echo "<div>";

		if($pagina < $max_links) {
			$paginacao = pagina + 1;
		}else{
			$paginacao = pagina;
		}

		// paginacao com restricao de links da paginacao
		// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		$todos_links = $mult_pag->Construir_Links("strings", "sim");

		// função que limita a quantidade de links no rodape
		$links_limitados = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

		for ($n = 0; $n < count($links_limitados); $n++) {
			echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		}
		echo "</div>";

		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0){
			echo "<br>";
			echo "<div>";
			echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
			echo "<font color='#cccccc' size='1'>";
			echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>";
			echo "</div>";
		}
		echo "<br>";
		if($pagina == 0){
			 // HD 47985
			$resxls = pg_exec($con,$sql);
			if(pg_numrows($resxls) >0) {
				flush();
				$data = date ("d/m/Y H:i:s");

				$arquivo_nome     = "relatorio-pecas-terceiros-$login_fabrica.xls";
				$path             = "/www/assist/www/admin/xls/";
				$path_tmp         = "/tmp/";

				$arquivo_completo     = $path.$arquivo_nome;
				$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

				echo `rm $arquivo_completo_tmp `;
				echo `rm $arquivo_completo `;

				$fp = fopen ($arquivo_completo_tmp,"w");

				fputs ($fp,"<html>");
				fputs ($fp,"<head>");
				fputs ($fp,"<title>RELAÇÃO DE PEÇAS PENDENTES - $data");
				fputs ($fp,"</title>");
				fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
				fputs ($fp,"</head>");
				fputs ($fp,"<body>");
				fputs ($fp,"<table style='font-family: verdana ); font-size: 10px); border-collapse: collapse' align='center'  bordercolor='#d2e4fc' border='1'>");
				fputs ($fp,"<caption class='Titulo'>RELAÇÃO DE PEÇAS PENDENTES</caption>");
				fputs ($fp,"<tr class='Titulo'>");
				fputs ($fp,"<td>Código Posto</td>");
				fputs ($fp,"<td>Nome Posto</td>");
				fputs ($fp,"<td>Peça</td>");
				fputs ($fp,"<td>Qtde</td>");
				fputs ($fp,"</tr>");

				$posto_ant = "";
				
				//trocado $i por $x HD 56098
				for($x=0;$x<pg_numrows($resxls);$x++){
					$codigo_posto	= trim(pg_result($resxls,$x,codigo_posto));
					$nome			= trim(pg_result($resxls,$x,nome));
					$peca			= trim(pg_result($resxls,$x,peca));
					$referencia		= trim(pg_result($resxls,$x,referencia));
					$descricao		= trim(pg_result($resxls,$x,descricao));
					$qtde			= trim(pg_result($resxls,$x,qtde_total));

					if($cor=="#F1F4FA")     $cor = '#F7F5F0';
					else                    $cor = '#F1F4FA';

					fputs ($fp,"<tr class='Conteudo' bgcolor='$cor'>");
					fputs ($fp,"<td nowrap align='center'>");
					if ($nome!=$posto_ant) fputs ($fp,$codigo_posto);
					fputs ($fp,"</td>");
					fputs ($fp,"<td nowrap align='left'>");
					if ($nome!=$posto_ant) fputs ($fp,$nome);
					fputs ($fp,"</td>");
					fputs ($fp,"<td nowrap align='left'>".$referencia . " - ".$descricao."</td>");
					fputs ($fp,"<td nowrap align='center'>".$qtde ."</td>");
					fputs ($fp,"</tr>");

					$posto_ant = $nome;
				}
				fputs ($fp,"</table>");

				fputs ($fp,"</body>");
				fputs ($fp,"</html>");
				fclose ($fp);

				echo ` cp $arquivo_completo_tmp $path `;
				$data = date("Y-m-d").".".date("H-i-s");

				echo `htmlxls --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
				echo "<br>";
				echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
				echo"<tr>";
				echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/relatorio-pecas-terceiros-$login_fabrica.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
				echo "</tr>";
				echo "</table>";
			}
		}
	// ##### PAGINACAO ##### //

}

include "rodape.php";
?>
