<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "auditoria";
include "autentica_admin.php";

include "funcoes.php";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0)  $acao = strtoupper($_GET["acao"]);

$msg = "";

if (strlen($acao) > "PESQUISAR") {
	##### Pesquisa entre datas #####
	if (strlen(trim($_POST["data_inicial"])) > 0) $x_data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0)  $x_data_inicial = trim($_GET["data_inicial"]);
	if (strlen(trim($_POST["data_final"])) > 0)   $x_data_final   = trim($_POST["data_final"]);
	if (strlen(trim($_GET["data_final"])) > 0)    $x_data_final   = trim($_GET["data_final"]);
	if ($x_data_inicial != "dd/mm/aaaa" && $x_data_final != "dd/mm/aaaa") {

		if (strlen($x_data_inicial) > 0) {
			$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$dia_inicial    = substr($x_data_inicial, 8, 2);
			$mes_inicial    = substr($x_data_inicial, 5, 2);
			$ano_inicial    = substr($x_data_inicial, 0, 4);
		}else{
			$msg .= " Preencha o campo Data Inicial para realizar a pesquisa. ";
		}

		if (strlen($x_data_final) > 0) {
			$x_data_final = fnc_formata_data_pg($x_data_final);
			$x_data_final = str_replace("'", "", $x_data_final);
			$dia_final    = substr($x_data_final, 8, 2);
			$mes_final    = substr($x_data_final, 5, 2);
			$ano_final    = substr($x_data_final, 0, 4);
		}else{
			$msg .= " Preencha o campo Data Final para realizar a pesquisa. ";
		}
	}else{
		$msg .= " Informe as datas corretas para realizar a pesquisa. ";
	}

	##### Pesquisa de produto #####
	if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo  = trim($_POST["posto_codigo"]);
	if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo  = trim($_GET["posto_codigo"]);
	if (strlen(trim($_POST["posto_nome"])) > 0)   $posto_nome    = trim($_POST["posto_nome"]);
	if (strlen(trim($_GET["posto_nome"])) > 0)    $posto_nome    = trim($_GET["posto_nome"]);
	if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
		$sql =	"SELECT tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome                ,
						tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$posto        = pg_result($res,0,posto);
			$posto_codigo = pg_result($res,0,codigo_posto);
			$posto_nome   = pg_result($res,0,nome);
		}else{
			$msg .= " Posto não encontrado. ";
		}

	}
	
	##### Situação do Extrato #####
	if (strlen(trim($_POST["situacao"])) > 0) $situacao = trim($_POST["situacao"]);
	if (strlen(trim($_GET["situacao"])) > 0)  $situacao = trim($_GET["situacao"]);
}

$layout_menu = "auditoria";
$title = "Relatório de Pagamentos";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<script LANGUAGE="JavaScript">
	function Redirect(pedido) {
		window.open('detalhe_pedido.php?pedido=' + pedido,'1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	}
</script>

<br>

<? if (strlen($msg) > 0) { ?>
<table width="600" align="center" class="error">
	<tr>
		<td><?echo $msg?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="450" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo">
		<td colspan="4">PESQUISA PAGAMENTOS</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>Data Inicial</td>
		<td>Data Final</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>
			<input size="12" maxlength="10" TYPE="text" NAME="data_inicial" value="<?if (strlen($data_inicial) == 0) echo 'dd/mm/aaaa'; else echo substr($data_inicial,0,10);?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') { this.value = ''; }" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript: showCal('DataPesquisaInicial');" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td>
			<input size="12" maxlength="10" TYPE="text" NAME="data_final" value="<?if (strlen($data_final) == 0) echo 'dd/mm/aaaa'; else echo substr($data_final,0,10);?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') { this.value = ''; }" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript: showCal('DataPesquisaFinal');" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4"><hr color="#EEEEEE"></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>Código do Posto</td>
		<td>Nome do Posto</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>
			<input type="text" name="posto_codigo" size="8" value="<?echo $posto_codigo?>" class="frm">
			<img src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome,'codigo');">
		</td>
		<td>
			<input type="text" name="posto_nome" size="15" value="<?echo $posto_nome?>" class="frm">
			<img src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome,'nome');">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4"><hr color="#EEEEEE"></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td colspan="2" align="center">
			Situação do Extrato
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>
			<input type="radio" name="situacao" value="GERACAO" <? if ($situacao == "GERACAO") echo "checked"; ?>> Aberto
		</td>
		<td>
			<input type="radio" name="situacao" value="APROVACAO" <? if ($situacao == "APROVACAO") echo "checked"; ?>> Aprovado
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td colspan="2" align="center">
			<input type="radio" name="situacao" value="FINANCEIRO" <? if (strlen($situacao) == 0 || $situacao == "FINANCEIRO") echo "checked"; ?>> Enviado p/ financeiro
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4"><hr color="#EEEEEE"></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_consulta.acao.value='PESQUISAR'; document.frm_consulta.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>

<?
if (strlen($msg) == 0 && strlen($acao) > 0) {
	$sql = "SELECT  DISTINCT
					tbl_extrato.extrato                                                                                                                         ,
					tbl_extrato.protocolo                                                                                                                       ,
					tbl_extrato.total                                                                                                                           ,
					TO_CHAR(MIN(tbl_os.data_digitacao),'DD/MM/YYYY')                                                                       AS digitacao_inicial ,
					TO_CHAR(MAX(tbl_os.data_digitacao),'DD/MM/YYYY')                                                                       AS digitacao_final   ,
					TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY')                                                                         AS data_extrato      ,
					TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')                                                                             AS data_aprovado    ,
					TO_CHAR(tbl_extrato_financeiro.data_envio,'DD/MM/YYYY')                                                                AS data_financeiro   ,
					(TO_CHAR(tbl_extrato_financeiro.data_envio,'YYYY-MM-DD')::date - TO_CHAR(tbl_extrato.data_geracao,'YYYY-MM-DD')::date) AS dias              ,
					tbl_posto_fabrica.codigo_posto                                                                                         AS posto_codigo      ,
					tbl_posto.nome                                                                                                         AS posto_nome        ,
					tbl_extrato_extra.nota_fiscal_mao_de_obra               
			FROM tbl_extrato
			JOIN tbl_extrato_extra      on  tbl_extrato_extra.extrato = tbl_extrato.extrato
			JOIN tbl_os_extra           ON  tbl_os_extra.extrato = tbl_extrato.extrato
			JOIN tbl_os                 ON  tbl_os.os  = tbl_os_extra.os
			LEFT JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
			JOIN tbl_posto              ON  tbl_posto.posto = tbl_extrato.posto
			JOIN tbl_posto_fabrica      ON  tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica";

	if ($situacao == "FINANCEIRO") $sql .= " WHERE tbl_extrato_financeiro.data_envio BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
											AND tbl_extrato_financeiro.data_envio IS NOT NULL";
	
	if ($situacao == "GERACAO") $sql .= " WHERE tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
										AND tbl_extrato.aprovado IS NULL";
										
	if ($situacao == "APROVACAO") $sql .= " WHERE tbl_extrato.aprovado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
											AND tbl_extrato_financeiro.data_envio IS NULL
											AND tbl_extrato.aprovado IS NOT NULL";

	if (strlen($posto) > 0) $sql .= " AND tbl_posto.posto = $posto";

	$sql .=	" AND tbl_extrato.fabrica = $login_fabrica
			GROUP BY  tbl_extrato.extrato               ,
						tbl_extrato.protocolo             ,
						tbl_extrato.total                 ,
						tbl_extrato.data_geracao          ,
						tbl_extrato.aprovado              ,
						tbl_extrato_financeiro.data_envio ,
						tbl_posto_fabrica.codigo_posto    ,
						tbl_posto.nome                    ,
						tbl_extrato_extra.nota_fiscal_mao_de_obra               
			ORDER BY tbl_posto_fabrica.codigo_posto";
	$res = pg_exec($con,$sql);
//if ($ip=="201.43.201.204")echo nl2br($sql);
//	if (getenv("REMOTE_ADDR") == "200.246.168.219") echo nl2br($sql) . "<br>" . pg_numrows(pg_exec($con,$sql));
//echo $sql;
	##### PAGINAÇÃO - INÍCIO #####
/*
	$sqlCount  = "SELECT COUNT(*) FROM (" . $sql . ") AS count";

	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
	##### PAGINAÇÃO - FIM #####
*/
	if (pg_numrows($res) > 0) {
		echo "<BR><BR><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td>CÓDIGO</td>";
		echo "<td>POSTO</td>";
		echo "<td>PERÍODO DIGITAÇÃO</td>";
		echo "<td>EXTRATO</td>";
		echo "<td>NF AUTORIZADO</td>";
		echo "<td>TOTAL</td>";
		echo "<td>GERAÇÃO</td>";
		echo "<td>APROVAÇÃO</td>";
		echo "<td>FINANCEIRO</td>";
		echo "<td>DIAS</td>";
		echo "</tr>";
		$total_final = "";
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$extrato           = trim(pg_result($res,$x,extrato));
			$protocolo         = trim(pg_result($res,$x,protocolo));
			$total             = trim(pg_result($res,$x,total));
			$digitacao_inicial = trim(pg_result($res,$x,digitacao_inicial));
			$digitacao_final   = trim(pg_result($res,$x,digitacao_final));
			$data_extrato      = trim(pg_result($res,$x,data_extrato));
			$data_aprovado     = trim(pg_result($res,$x,data_aprovado));
			$data_financeiro   = trim(pg_result($res,$x,data_financeiro));
			$dias              = trim(pg_result($res,$x,dias));
			$dias              = str_replace("days", "dias", $dias);
			$dias              = str_replace("day", "dia", $dias);
			$posto_codigo      = trim(pg_result($res,$x,posto_codigo));
			$posto_nome        = trim(pg_result($res,$x,posto_nome));
			$nf_mao_de_obra    = trim(pg_result($res,$x,nota_fiscal_mao_de_obra));

			$cor = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			$total_final = $total + $total_final;

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap>" . $posto_codigo . "</td>";
			echo "<td nowrap align='left'>" . $posto_nome . "</td>";
			echo "<td nowrap>";
			if (strlen($digitacao_inicial) && strlen($digitacao_final) > 0) echo $digitacao_inicial . " a " . $digitacao_final;
			else                                                            echo $data_extrato;
			echo "</td>";
			echo "<td nowrap>" .  $protocolo . "</td>";
			echo "<td nowrap>" . $nf_mao_de_obra . "</td>";
			echo "<td nowrap align='right'>R$ " . number_format($total,2,",",".") . "</td>";
			echo "<td nowrap>" . $data_extrato . "</td>";
			echo "<td nowrap>" . $data_aprovado . "</td>";
			echo "<td nowrap>" . $data_financeiro . "</td>";
			echo "<td nowrap>" . $dias . "</td>";
			echo "</tr>";
			
		}
		echo "</table>";
		
	}
echo $total_final;
/*
	##### PAGINAÇÃO - INÍCIO #####
	// links da paginacao
	echo "<br>";
	echo "<div>";

	if($pagina < $max_links) $paginacao = pagina + 1;
	else                     $paginacao = pagina;

	// paginacao com restricao de links da paginacao

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

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
		echo " <font color='#cccccc' size='1'>(Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)</font>";
		echo "</div>";
	}
	##### PAGINAÇÃO - FIM #####
*/
}

echo "<br>";

include "rodape.php";
?>
