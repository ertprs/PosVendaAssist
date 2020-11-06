<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

include "funcoes.php";

$erro = "";


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}


if (strlen($_POST["acao"]) > 0 ) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0 )  $acao = strtoupper($_GET["acao"]);

if (strlen($acao) > 0 && $acao == "PESQUISAR") {
	if (strlen(trim($_POST["data_inicial"])) > 0) $x_data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0)  $x_data_inicial = trim($_GET["data_inicial"]);

	$x_data_inicial = fnc_formata_data_pg($x_data_inicial);

	if (strlen(trim($_POST["data_final"])) > 0) $x_data_final   = trim($_POST["data_final"]);
	if (strlen(trim($_GET["data_final"])) > 0) $x_data_final = trim($_GET["data_final"]);

	$x_data_final   = fnc_formata_data_pg($x_data_final);

	if (strlen($x_data_inicial) > 0 && $x_data_inicial != "null") {
		$x_data_inicial = str_replace("'", "", $x_data_inicial);
		$dia_inicial = substr($x_data_inicial, 8, 2);
		$mes_inicial = substr($x_data_inicial, 5, 2);
		$ano_inicial = substr($x_data_inicial, 0, 4);
		$data_inicial = $dia_inicial . "/" . $mes_inicial . "/" . $ano_inicial;
	}else{
		$erro .= " Informe a Data Inicial para realizar a pesquisa. ";
	}

	if (strlen($x_data_final) > 0 && $x_data_final != "null") {
		$x_data_final = str_replace("'", "", $x_data_final);
		$dia_final = substr($x_data_final, 8, 2);
		$mes_final = substr($x_data_final, 5, 2);
		$ano_final = substr($x_data_final, 0, 4);
		$data_final = $dia_final . "/" . $mes_final . "/" . $ano_final;
	}else{
		$erro .= " Informe a Data Inicial para realizar a pesquisa. ";
	}

	if (strlen(trim($_POST["status"])) > 0) $status = trim($_POST["status"]);
	if (strlen(trim($_GET["status"])) > 0)  $status = trim($_GET["status"]);

	if (strlen(trim($_POST["posto"])) > 0) $posto = trim($_POST["posto"]);
	if (strlen(trim($_GET["posto"])) > 0)  $posto = trim($_GET["posto"]);

	if (strlen($codigo_posto)==0) {
		$erro = "Informe o posto";
	} else {
		$sqlp = "SELECT posto
				 FROM tbl_posto_fabrica
				 WHERE fabrica = $login_fabrica
				 AND codigo_posto = '$codigo_posto'";
		$resp = pg_exec($con, $sqlp);

		if (pg_numrows($resp) > 0) {
			$posto = pg_result($resp, 0, 0);
		} else {
			$erro = "Posto informado não encontrado.";
		}
	}


	$link_status = "http://" . $HTTP_HOST . $REQUEST_URI . "?data_inicial=" . $_POST["data_inicial"] . "&data_final=" . $_POST["data_final"] . "&acao=PESQUISAR";
	setcookie("LinkStatus", $link_status);

}

$layout_menu = "auditoria";
$title = "Relação de Status da Ordem de Serviço";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12 px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12 px;
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

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>




<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[2]) ;
	});
});

</script>


<?
include "cabecalho.php";
?>


<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

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
		<td class='Titulo' background='imagens_admin/azul.gif'>Status da OS por posto</td>
	</tr>

	<tr>
		<td bgcolor='#DBE5F5'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>


				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Código Posto:&nbsp;</td>
					<td colspan='2' align='left'>
						<input class="Caixa" type="text" name="codigo_posto" id="codigo_posto" size="10" value="<? echo $codigo_posto ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></A>
					</td>
				</tr>
				<tr>
					<td colspan='2' align='right'>Razão Social:&nbsp;</td>
					<td colspan='2' align='left'><input class="Caixa" type="text" name="posto_nome" id="posto_nome" size="30" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens_admin/btn_lupa.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
				</tr>


				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Data Inicial:&nbsp;</td>
					<td colspan='2' align='left'>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_inicial; ?>" >
					</td>
				</tr>
				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Data Final:&nbsp;</td>
					<td colspan='2' align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final; ?>">
					</td>
				</tr>
				<tr>
					<td colspan='2' align='right'>Status:&nbsp;</td>
					<td colspan='2' align='left'>
						<select name="status" size="1" class="frm">
							<option <?if ($status == "00") echo " selected ";?> value='00'>Todas</option>
							<option <?if ($status == "01") echo " selected ";?> value='01'>Aprovadas</option>
							<option <?if ($status == "13") echo " selected ";?> value='13'>Recusadas</option>
							<option <?if ($status == "14") echo " selected ";?> value='14'>Acumuladas</option>
							<option <?if ($status == "15") echo " selected ";?> value='15'>Excluídas</option>
						</select>
					</td>
				</tr>
				<tr bgcolor="#D9E2EF">
					<td colspan="4" align="center" ><img border="0" src="imagens/btn_pesquisar_400.gif" onClick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
				</tr>

			</table>
		</td>
	</tr>
</table>

<br>

<?
flush();

if (strlen($acao) > 0 && strlen($erro) == 0) {
	//SOMENTE OSs QUE NÃO ESTÃO EXCLUIDAS
	if ($status <> "15") {
		$sql = "SELECT *FROM (
					SELECT  DISTINCT
						tbl_posto_fabrica.codigo_posto                                    ,
						tbl_posto.nome                                 AS nome_posto      ,
						tbl_os.os                                                         ,
						tbl_os.sua_os                                                     ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')    AS data_digitacao  ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')     AS data_abertura   ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento ,
						TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')        AS finalizada      ,
						tbl_os.pecas                                                      ,
						tbl_os.tipo_atendimento                                           ,
						tbl_os.mao_de_obra                                                ,
						tbl_extrato.extrato                                               ,
						tbl_extrato_extra.exportado                                       ,
						TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado        ,
						TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao    ,
						tbl_os.nota_fiscal                                                ,
						tbl_os.serie                                                      ,
						tbl_os.os_reincidente                                             ,
						(
							select tbl_os_status.status_os
							from tbl_os_status
							where tbl_os_status.os = tbl_os.os
							order by data desc limit 1
						)                                              AS status_os       ,
						(
							select tbl_os_status.observacao
							from tbl_os_status
							where tbl_os_status.os = tbl_os.os
							order by data desc limit 1
						)                                              AS observacao
					FROM  tbl_os
					LEFT JOIN  tbl_os_extra          ON tbl_os_extra.os           = tbl_os.os
					LEFT JOIN  tbl_extrato           ON tbl_extrato.extrato       = tbl_os_extra.extrato
					LEFT JOIN  tbl_extrato_extra     ON tbl_extrato_extra.extrato = tbl_os_extra.extrato
					JOIN  tbl_posto_fabrica          ON tbl_posto_fabrica.posto   = tbl_os.posto
													AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
					JOIN  tbl_posto                  ON tbl_posto.posto           = tbl_os.posto
					WHERE tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
					AND tbl_os.fabrica = $login_fabrica
					AND tbl_os.posto   = $posto
					GROUP BY tbl_posto_fabrica.codigo_posto,
						 tbl_posto.nome                    ,
						 tbl_os.os                         ,
						 tbl_os.sua_os                     ,
						 tbl_os.data_digitacao             ,
						 tbl_os.data_abertura              ,
						 tbl_os.data_fechamento            ,
						 tbl_os.finalizada                 ,
						 tbl_os.pecas                      ,
						 tbl_os.tipo_atendimento           ,
						 tbl_os.mao_de_obra                ,
						 tbl_extrato.extrato               ,
						 tbl_extrato_extra.exportado       ,
						 tbl_extrato.aprovado              ,
						 tbl_extrato.data_geracao          ,
						 tbl_os.nota_fiscal                ,
						 tbl_os.serie                      ,
						 tbl_os.os_reincidente              ) x";

		//TODAS
		if ($status == "00") {
			$sql.= " WHERE data_fechamento NOTNULL";
		}

		//APROVADA
		if ($status == "01") {
			if ($login_fabrica == 19) {
				$sql.= " WHERE status_os <> 13
						 AND aprovado NOTNULL
						 AND data_fechamento NOTNULL ";
			}else{
				$sql.= " WHERE aprovado NOTNULL
						 AND extrato NOTNULL
						 AND data_fechamento NOTNULL ";
			}
		}

		//PESQUISA POR RECUSADAS
		if ($status == "13") {
			if ($login_fabrica == 19)
				$sql.= " WHERE status_os = 13";
			else
				$sql.= " WHERE status_os = 13 AND data_fechamento IS NULL";
		}

		//ACUMULADA
		if ($status == "14") {
			$sql.= " WHERE status_os = 14
					 AND aprovado IS NULL
					 AND extrato IS NULL
					 AND data_fechamento NOTNULL";
		}

		//EXCLUIDA
		if ($status == "15") {
			$sql.= " WHERE status_os = 15
					 AND extrato IS NULL
					 AND data_fechamento NOTNULL";
		}

		$sql .= " ORDER BY codigo_posto, sua_os";
		echo "SQL1:" . $sql;
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<table width='700' border='0' cellspacing='2' cellpadding='0' align='center'>";
			echo "<tr>";
			echo "<td align='center' width='18' height='18' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td align='left'><font size=1>&nbsp; OS recusada pelo fabricante <br>&nbsp; <B>(Para alterar, acesse a <a href='os_parametros.php'>Consulta de OS</a> clique em Reabrir OS e faça as alterações necessárias)</B></font></td>";
			echo "</tr>";
			if($login_fabrica == 30) { // HD 50477
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; Reincidências</b></font></td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<br>";

			echo "<input type='hidden' name='qtde_os' value='" . pg_numrows($res) . "'>";

			echo "<table width='650' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Titulo'>";
			echo "<td colspan='100%'>RELAÇÃO DE OS</td>";
			echo "</tr>";

			if (strlen($posto) > 0) {
				$codigo_posto    = trim(pg_result($res,0,codigo_posto));
				$nome_posto      = trim(pg_result($res,0,nome_posto));

				echo "<tr class='Titulo'>";
				echo "<td colspan='100%'>$codigo_posto - $nome_posto</td>";
				echo "</tr>";
			}

			echo "<tr class='Titulo'>";
			echo "<td>OS</td>";
			echo "<td>DIGITAÇÃO</td>";
			echo "<td>ABERTURA</td>";
			echo "<td>FECHAMENTO</td>";
			echo "<td>TOTAL</td>";
			echo "<td>PROTOCOLO</td>";
			if($login_fabrica==30) { // HD 50477
				echo "<td>NOTA FISCAL</td>";
				echo "<td>SÉRIE</td>";
			}
			echo "<td>STATUS</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$codigo_posto     = trim(pg_result($res,$i,codigo_posto));
				$nome_posto       = trim(pg_result($res,$i,nome_posto));
				$os               = trim(pg_result($res,$i,os));
				$sua_os           = trim(pg_result($res,$i,sua_os));
				$data_digitacao   = trim(pg_result($res,$i,data_digitacao));
				$data_abertura    = trim(pg_result($res,$i,data_abertura));
				$data_fechamento  = trim(pg_result($res,$i,data_fechamento));
				$finalizada       = trim(pg_result($res,$i,finalizada));
				$pecas            = trim(pg_result($res,$i,pecas));
				$mao_de_obra      = trim(pg_result($res,$i,mao_de_obra));
				$total            = $custo_pecas + $mao_de_obra;
				$extrato          = trim(pg_result($res,$i,extrato));
				$exportado        = trim(pg_result($res,$i,exportado));
				$aprovado         = trim(pg_result($res,$i,aprovado));
				$data_geracao     = trim(pg_result($res,$i,data_geracao));
				$status_os        = trim(pg_result($res,$i,status_os));
				$observacao       = trim(pg_result($res,$i,observacao));
				$tipo_atendimento = trim(pg_result($res,$i,tipo_atendimento));
				$nota_fiscal      = trim(pg_result($res,$i,nota_fiscal));
				$serie            = trim(pg_result($res,$i,serie));
				$os_reincidente   = trim(pg_result($res,$i,os_reincidente));

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

				if ( ($login_fabrica == 19 AND $status_os == 13) OR
					 ($login_fabrica <> 19 AND $status_os == 13 AND strlen(trim($data_fechamento)) == 0) ) {
					$cor = "#FFE1E1";
					$rowspan = "2";
				}else{
					$rowspan = "1";
				}

				if ($status_os == 14 AND strlen($extrato) == 0) {
					$cor = "#D7FFE1";
				}
				if($login_fabrica ==30 AND $os_reincidente =='t') { // HD 50477
					$cor = "#D7FFE1";
				}

				echo "<tr class='Conteudo' bgcolor='$cor'>";
				echo "<td>". $sua_os ."</td>";
				echo "<td nowrap align='center'>" . $data_digitacao . "</td>";
				echo "<td nowrap align='center'>" . $data_abertura . "</td>";
				echo "<td nowrap align='center'><acronym title='Data de fechamento digitada: $data_fechamento' style='cursor: help;'>" . $finalizada . "</acronym></td>";
				echo "<td nowrap align='right'>" . number_format($total,2,",",".") . "</td>";
				echo "<td nowrap align='center'>" . $os . "</td>";
				if($login_fabrica == 30) { // HD 50477
					echo "<td nowrap align='center'>" . $nota_fiscal . "</td>";
					echo "<td nowrap align='center'>" . $serie . "</td>";
				}
				echo "<td nowrap align='center'>";
	//echo "\n\n<!-- DATA GERACAO: ".strlen($data_geracao)."<BR> APROVADO: ".strlen($aprovado)."<BR> STATUS: ".strlen($status_os)." -->\n\n";


				if ($status == "00") {
					if     (strlen($data_geracao) >  0  AND strlen($aprovado) == 0)                                                                     echo "Em aprovação";
					elseif ($status_os == 92) {
						echo "Aguardando Aprovação";
					}elseif ($status_os == 93 and $tipo_atendimento==13) {
						echo "Troca Aprovada";
					}elseif ($status_os == 94 and $tipo_atendimento==13) {
						echo "Troca Recusada";
					}
					elseif (strlen($data_geracao) == 0  AND strlen($aprovado) == 0 AND strlen($status_os) == 0 AND strlen(trim($data_fechamento)) <> 0) echo "Finalizada";
					elseif ($login_fabrica == 19 AND $status_os <> 13 AND strlen($aprovado) > 0)                                                        echo "Aprovada";
					elseif ($login_fabrica == 20 AND strlen($aprovado) > 0 AND strlen($extrato) > 0 AND strlen($exportado)>0)                           echo "Pagamento efetuado";
					elseif ($login_fabrica <> 19 AND strlen($aprovado) > 0 AND strlen($extrato) > 0)                                                    echo "Aprovada";
					elseif ($login_fabrica == 20 AND $status_os == 13)                                                                                  echo "Recusada";
					elseif ($login_fabrica == 20 AND $status_os == 14)                                                                                  echo "Acumulada";
					elseif ($login_fabrica == 19 AND $status_os == 13 AND strlen($extrato) > 0)                                                         echo "Recusada";
					elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) == 0)                echo "Recusada";
					elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) > 0)                 echo "Finalizada";
					elseif ($status_os == 14 AND strlen($extrato) == 0)                                                                                 echo "Acumulada";
					elseif ($status_os == 15 AND strlen($extrato) == 0)                                                                                 echo "Excluída";
					elseif ($login_fabrica == 20 AND strlen(trim($data_fechamento))>0 and strlen($extrato)==0)                                          echo "Finalizada";
				}

				if ($status == "01") {
					if ($login_fabrica == 19 AND $status_os <> 13 AND strlen($aprovado) > 0)         echo "Aprovada";
					elseif ($login_fabrica <> 19 AND strlen($aprovado) > 0 AND strlen($extrato) > 0) echo "Aprovada";
				}
				elseif ($status == "13") {
					if ($login_fabrica == 19 AND $status_os == 13 AND strlen($extrato) > 0)                                              echo "Recusada";
					elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) == 0) echo "Recusada";
				}
				elseif ($status == "14") {
					if ($status_os == 14 AND strlen($extrato) == 0) echo "Acumulada";
				}
				elseif ($status == "15") {
					if ($status_os == 15 AND strlen($extrato) == 0) echo "Excluída";
				}

				echo "</td>";
				echo "</tr>";

				if (strlen($aprovado) == 0 AND strlen($observacao) > 0 AND $status_os <> 14) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='100%'><b>Obs. Fábrica: </b>" . $observacao . "</td>";
					echo "</tr>";
				}
			}
			echo "</table>";
			flush();
			echo "<br>";
	/*		echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_relatorio.acao.value == '') { document.frm_relatorio.acao.value='GRAVAR'; document.frm_relatorio.submit(); }else{ alert('Aguarde submissão...'); } \" style='cursor: hand;'>";
			echo "<br><br>";*/

			$achou = "sim";
		}else{
			$achou = "nao";
		}
	}

	//PESQUISA POR TODAS E/OU EXCLUÍDAS
	if ($status == "00" OR $status == "15") {
		$sql = "SELECT  tbl_os_excluida.codigo_posto                                        ,
						tbl_posto.nome                                      AS nome_posto   ,
						tbl_os_excluida.admin                                               ,
						tbl_os_excluida.sua_os                                              ,
						tbl_os_excluida.referencia_produto                                  ,
						tbl_os_excluida.serie                                               ,
						tbl_os_excluida.nota_fiscal                                         ,
						to_char(tbl_os_excluida.data_nf,'DD/MM/YYYY')       AS data_nf      ,
						to_char(tbl_os_excluida.data_exclusao,'DD/MM/YYYY') AS data_exclusao,
						(
							select tbl_os_status.observacao
							from tbl_os_status
							where tbl_os_status.os = tbl_os_excluida.os
							order by data desc limit 1
						)                                              AS observacao
				FROM    tbl_os_excluida
				JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.codigo_posto = tbl_os_excluida.codigo_posto
											AND tbl_posto_fabrica.fabrica      = $login_fabrica
				JOIN    tbl_posto            ON tbl_posto.posto                = tbl_os_excluida.posto
				WHERE   tbl_os_excluida.fabrica = $login_fabrica
				AND     tbl_os_excluida.posto   = $posto";


		$sql .= "ORDER BY tbl_os_excluida.data_exclusao;";
		#echo "SQL2:" . nl2br($sql);
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<br>";

			echo "<input type='hidden' name='qtde_os' value='" . pg_numrows($res) . "'>";

			if($login_fabrica==1){
			echo "<table width='650' border='0' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
					echo "<TR>";
						echo "<TD width='20' bgcolor='#FFE1E1'>&nbsp;</TD>";
						echo "<TD>OSs excluidas pelo posto.</TD>";
					echo "</TR>";
					echo "</TABLE>";
				echo "<BR>";
			}

			echo "<table width='650' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Titulo'>";
			echo "<td colspan='7'>RELAÇÃO DE OS EXCLUÍDAS</td>";
			echo "</tr>";

			if (strlen($posto) > 0) {
				$codigo_posto    = trim(pg_result($res,0,codigo_posto));
				$nome_posto      = trim(pg_result($res,0,nome_posto));

				echo "<tr class='Titulo'>";
				echo "<td colspan='7'>$codigo_posto - $nome_posto</td>";
				echo "</tr>";
			}

			echo "<tr class='Titulo'>";
			echo "<td>OS</td>";
			echo "<td>PRODUTO</td>";
			echo "<td>SÉRIE</td>";
			echo "<td>NOTA FISCAL</td>";
			echo "<td>DATA NF</td>";
			echo "<td>DATA EXCLUSÃO</td>";
			echo "<td>STATUS</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$sua_os         = trim(pg_result($res,$i,sua_os));
				$produto        = trim(pg_result($res,$i,referencia_produto));
				$serie          = trim(pg_result($res,$i,serie));
				$nota_fiscal    = trim(pg_result($res,$i,nota_fiscal));
				$data_nf        = trim(pg_result($res,$i,data_nf));
				$data_exclusao  = trim(pg_result($res,$i,data_exclusao));
				$observacao     = trim(pg_result($res,$i,observacao));
				$admin          = trim(pg_result($res,$i,admin));

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

				if($login_fabrica==1){
					if (strlen($admin)==0) {
						$cor = "#FFE1E1";
					}
				}else{
					if ($status == "00" OR $status == "15") {
						$cor = "#FFE1E1";
					}
				}

				echo "<tr class='Conteudo' bgcolor='$cor'>";
				echo "<td>";
				if ($login_fabrica == 1) {
					echo $codigo_posto;
				}
				echo $sua_os;
				echo "</td>";
				echo "<td align='center'>" . $produto . "</td>";
				echo "<td align='right'>" . $serie . "</td>";
				echo "<td align='right'>" . $nota_fiscal . "</td>";
				echo "<td align='right'>" . $data_nf . "</td>";
				echo "<td align='center'>" . $data_exclusao . "</td>";
				echo "<td align='center'>Excluída</td>";
				echo "</tr>";
				if ($login_fabrica== 1 AND strlen($observacao) > 0) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='8'><b>Obs. Fábrica: </b>" . $observacao . "</td>";
					echo "</tr>";
				}
			}
			echo "</table>";
			echo "<br>";
			$achou = "sim";
		}else{
			$achou = "nao";
		}
	}

	if ($achou == "nao") {
		echo "<table border='0' cellpadding='2' cellspacing='0'>";
		echo "<tr height='50'>";
		echo "<td valign='middle' align='center'><img src='imagens/atencao.gif' border='0'>
			<font size=\"2\"><b>Não foram encontrados registros excluídos com os parâmetros informados/digitados!!!</b></font>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}

	##### OS NÃO FINALIZADAS (SOMENTE PESQUISA POR TODAS) #####
	if ($status == "00") {
		$sql =	"SELECT tbl_posto_fabrica.codigo_posto                                   ,
						tbl_posto.nome                                 AS nome_posto     ,
						tbl_os.os                                                        ,
						tbl_os.sua_os                                                    ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')    AS data_digitacao ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')     AS data_abertura  ,
						tbl_os.pecas                                                     ,
						tbl_os.mao_de_obra                                               ,
						TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado       ,
						TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao   ,
						tbl_os.nota_fiscal                                               ,
						tbl_os.serie                                                     ,
						tbl_os.os_reincidente                                            ,
						(
							SELECT tbl_os_status.status_os
							FROM tbl_os_status
							WHERE tbl_os_status.os = tbl_os.os
							ORDER BY data DESC LIMIT 1
						)                                              AS status_os      ,
						(
							SELECT tbl_os_status.observacao
							FROM tbl_os_status
							WHERE tbl_os_status.os = tbl_os.os
							ORDER BY data DESC LIMIT 1
						)                                              AS observacao
				FROM tbl_os
				JOIN tbl_os_extra USING (os)
				JOIN tbl_posto    USING (posto)
				JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_extrato    ON tbl_extrato.extrato = tbl_os_extra.extrato
				WHERE tbl_os.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final'
				AND tbl_os.finalizada      ISNULL
				AND tbl_os.data_fechamento ISNULL
				AND tbl_os.fabrica = $login_fabrica
				AND tbl_os.posto   = $posto";

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<table width='650' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Titulo'>";
			echo "<td colspan='100%'>RELAÇÃO DE OS NÃO FINALIZADAS</td>";
			echo "</tr>";

			if (strlen($posto) > 0) {
				$codigo_posto    = trim(pg_result($res,0,codigo_posto));
				$nome_posto      = trim(pg_result($res,0,nome_posto));

				echo "<tr class='Titulo'>";
				echo "<td colspan='100%'>$codigo_posto - $nome_posto</td>";
				echo "</tr>";
			}

			echo "<tr class='Titulo'>";
			echo "<td>OS</td>";
			echo "<td>DIGITAÇÃO</td>";
			echo "<td>ABERTURA</td>";
			echo "<td>TOTAL</td>";
			echo "<td>PROTOCOLO</td>";
			if($login_fabrica==30) { // HD 50477
				echo "<td>NOTA FISCAL</td>";
				echo "<td>SÉRIE</td>";
			}
			echo "<td>STATUS</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$os             = trim(pg_result($res,$i,os));
				$sua_os         = trim(pg_result($res,$i,sua_os));
				$data_digitacao = trim(pg_result($res,$i,data_digitacao));
				$data_abertura  = trim(pg_result($res,$i,data_abertura));
				$pecas          = trim(pg_result($res,$i,pecas));
				$mao_de_obra    = trim(pg_result($res,$i,mao_de_obra));
				$total          = $custo_pecas + $mao_de_obra;
				$aprovado       = trim(pg_result($res,$i,aprovado));
				$data_geracao   = trim(pg_result($res,$i,data_geracao));
				$status_os       = trim(pg_result($res,$i,status_os));
				$observacao      = trim(pg_result($res,$i,observacao));
				$nota_fiscal      = trim(pg_result($res,$i,nota_fiscal));
				$serie            = trim(pg_result($res,$i,serie));
				$os_reincidente   = trim(pg_result($res,$i,os_reincidente));

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

				if ($status_os == 13) {
					$cor = "#FFE1E1";
					$rowspan = "2";
				}else{
					$rowspan = "1";
				}

				if($login_fabrica == 30 AND $os_reincidente =='t') {
					$cor = "#D7FFE1";
				}

				echo "<tr class='Conteudo' bgcolor='$cor'>";
				echo "<td rowspan='$rowspan'>";
				if ($login_fabrica == 1) echo $codigo_posto;
				echo $sua_os;
				echo "</td>";
				echo "<td align='center'>" . $data_digitacao . "</td>";
				echo "<td align='center'>" . $data_abertura . "</td>";
				echo "<td align='right'>" . number_format($total,2,",",".") . "</td>";
				echo "<td align='center'>" . $os . "</td>";
				if($login_fabrica == 30) { // HD 50477
					echo "<td nowrap align='center'>" . $nota_fiscal . "</td>";
					echo "<td nowrap align='center'>" . $serie . "</td>";
				}
				echo "<td align='center'>";
				if     (strlen($data_geracao) > 0  AND strlen($aprovado) == 0) echo "Em aprovação";
				elseif (strlen($data_geracao) == 0 AND strlen($aprovado) == 0 AND strlen($status_os) == 0) echo "Não finalizada";
	#			elseif (strlen($aprovado) > 0)                                                             echo "Aprovada";
				elseif ($status_os == 13 and  strlen($extrato) == 0)                                       echo "Recusada";
				elseif ($status_os == 14 and  strlen($extrato) == 0)                                       echo "Acumulada";
				elseif ($status_os == 15 and  strlen($extrato) == 0)                                       echo "Excluída";
				echo "</td>";
				echo "</tr>";

				if (strlen($aprovado) == 0 AND strlen($observacao) > 0 AND $status_os <> 14) {
					echo "<tr class='Conteudo' bgcolor='$cor'>";
					echo "<td colspan='100%'><b>Obs. Fábrica: </b>" . $observacao . "</td>";
					echo "</tr>";
				}
			}
			echo "</table>";
			echo "<br>";
		}
	}
}

include "rodape.php";
?>
