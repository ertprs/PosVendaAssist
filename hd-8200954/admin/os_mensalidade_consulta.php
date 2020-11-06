<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

include 'funcoes.php';

$layout_menu = "gerencia";
$title = "Relação de Ordens de Serviços relacionadas à mensalidade";

include "cabecalho.php";

?>

<script language="JavaScript">
function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}
</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

</style>

<? include "javascript_pesquisas.php" ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<br>

<FORM name="frm_pesquisa" METHOD="GET" ACTION="<? echo $PHP_SELF; ?>">
<input type="hidden" name="btn_acao" value='pesquisa'>
<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="4" class="menu_top"><div align="center"><b>Pesquisa por Intervalo entre Datas</b></div></TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" align='left'>Data Inicial</TD>
	<TD class="table_line" align='left'>Data Final</TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<?

if($_POST["data_inicial_01"])	$data_inicial_01    = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])		$data_final_01      = trim($_POST["data_final_01"]);

if($_GET["data_inicial_01"])	$data_inicial_01    = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])		$data_final_01      = trim($_GET["data_final_01"]);

?>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" align='left'><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value="<? if (strlen($data_inicial_01) == 0) echo "dd/mm/aaaa"; else echo $data_inicial_01?>" onclick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaInicial_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
	<TD class="table_line" align='left'><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value="<? if (strlen($data_final_01) == 0) echo "dd/mm/aaaa"; else echo $data_final_01?>" onclick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaFinal_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="4" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onClick="javascript:document.frm_pesquisa.submit();" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
</TABLE>
</FORM>

<BR>

<?

if (strlen($_POST['btn_acao']) > 0) $btn_acao = trim (strtolower ($_POST['btn_acao']));
if (strlen($_GET['btn_acao']) > 0)  $btn_acao = trim (strtolower ($_GET['btn_acao']));

if (strlen($btn_acao) > 0){
	if($_POST["posto"])	$posto = trim($_POST["posto"]);
	if($_GET["posto"])	$posto = trim($_GET["posto"]);
	
	if($_POST["data_inicial_01"])	$data_inicial_01    = trim($_POST["data_inicial_01"]);
	if($_POST["data_final_01"])		$data_final_01      = trim($_POST["data_final_01"]);
	
	if($_GET["data_inicial_01"])	$data_inicial_01    = trim($_GET["data_inicial_01"]);
	if($_GET["data_final_01"])		$data_final_01      = trim($_GET["data_final_01"]);
	
	$data_inicial = fnc_formata_data_pg ($data_inicial_01);
	$data_final   = fnc_formata_data_pg ($data_final_01);
	
	$ano = substr($data_final, 1, 4);
	$mes = substr($data_final, 6, 2);

	if (strlen($posto) == 0){
		$sql = "SELECT    count(tbl_os.os) as total     ,
						  tbl_posto_fabrica.posto       ,
						  tbl_posto_fabrica.codigo_posto,
						  tbl_posto.nome AS posto_nome
				FROM      tbl_os
				JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
				JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
				JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os
				LEFT JOIN tbl_posto_linha   ON  tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
				WHERE     tbl_os.fabrica = $login_fabrica
				AND       tbl_os.data_digitacao::date BETWEEN $data_inicial AND $data_final 
				GROUP BY  tbl_posto_fabrica.posto       ,
						  tbl_posto_fabrica.codigo_posto,
						  tbl_posto.nome 
				ORDER BY  count(tbl_os.os) ";
//				echo $sql; exit;
		$res = @pg_exec($con,$sql);
		//echo nl2br($sql);
		$msg_erro = pg_errormessage($con);
		if (@pg_numrows($res) > 0 AND strlen($msg_erro) == 0) {

			echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='0' align='center'>";
			echo "<tr class='table_line'>";
			echo "<td align='center' background='#d9e2ef'><H2>OSs do período entre $data_inicial_01 e $data_final_01</H2></td>";
			echo "</tr>";
			echo "</table>";

			$ROWS = pg_numrows($res);

			$total_geral = 0;
			for ($i = 0 ; $i < $ROWS ; $i++) {
				$total_geral += trim(pg_result($res,$i,total));
			}

			echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr><td colspan='8' align='center'>Total de OSs $total_geral</td></tr>";

			for ($i = 0 ; $i < $ROWS ; $i++) {
				if ($i == 0) {
					echo "<tr class='menu_top' height='15'>";
					echo "<td>CÓDIGO</td>";
					echo "<td>POSTO</td>";
					echo "<td>TOTAL</td>";
					echo "<td>AÇÕES</td>";
					echo "</tr>";
				}

				$posto        = trim(pg_result($res,$i,posto));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				$posto_nome   = trim(pg_result($res,$i,posto_nome));
				$total        = trim(pg_result($res,$i,total));

				if ($i % 2 == 0) {
					$cor   = "#F1F4FA";
					$botao = "azul";
				}else{
					$cor   = "#F7F5F0";
					$botao = "amarelo";
				}

				echo "<tr class='table_line' height='15' bgcolor='$cor' align='left'>";
				echo "<td nowrap>" . $codigo_posto . "</td>";
				echo "<td nowrap>" . $posto_nome .  "</td>";
				echo "<td nowrap>" . $total . "</td>";
				echo "<td width='60' align='center'>";
				echo "<a href='$PHP_SELF?posto=$posto&btn_acao=procurar&data_inicial_01=$data_inicial_01&data_final_01=$data_final_01' target='_blank'><img border='0' src='imagens/btn_detalhar_$botao.gif'></a>";
				echo "</td>\n";
				echo "</tr>";
			}
			echo "</table>";
		}
	}else{
		// OSs do período
		$sql = "SELECT tbl_os.os                                                          ,
						tbl_os.sua_os                                                      ,
						LPAD(tbl_os.sua_os,20,'0')                   AS ordem              ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao          ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
						tbl_os.serie                                                      ,
						tbl_os.excluida                                                   ,
						tbl_os.motivo_atraso                                              ,
						tbl_os.tipo_os_cortesia                                           ,
						tbl_os.consumidor_revenda                                         ,
						tbl_os.consumidor_nome                                            ,
						tbl_os.revenda_nome                                               ,
						tbl_posto_fabrica.codigo_posto                                    ,
						tbl_posto.nome                              AS posto_nome         ,
						tbl_os_extra.impressa                                             ,
						tbl_os_extra.extrato                                              ,
						tbl_os_extra.os_reincidente                                       ,
						tbl_produto.referencia                      AS produto_referencia ,
						tbl_produto.descricao                       AS produto_descricao  ,
						tbl_produto.voltagem                        AS produto_voltagem   ,
						distrib.codigo_posto                        AS codigo_distrib
				FROM      tbl_os
				JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
				JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
				JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os
				LEFT JOIN tbl_posto_linha   ON  tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
				WHERE   tbl_os.fabrica = $login_fabrica
				AND     tbl_os.posto   = $posto
				AND     tbl_os.data_digitacao::date BETWEEN $data_inicial AND $data_final ";
	//echo nl2br($sql); exit;
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (@pg_numrows($res) > 0 AND strlen($msg_erro) == 0) {
			$ROWS = pg_numrows($res);
			
			echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr><td colspan='8' align='center'>Total de OSs $ROWS</td></tr>";
			for ($i = 0 ; $i < $ROWS ; $i++) {
				if ($i == 0) {
					echo "<tr class='menu_top' height='15'>";
					echo "<td>OS</td>";
					echo "<td>SÉRIE</td>";
					echo "<td>AB</td>";
					echo "<td>FC</td>";
					echo "<td>POSTO</td>";
					echo "<td>CONSUMIDOR</td>";
					echo "<td>PRODUTO</td>";
					echo "<td>AÇÕES</td>";
					echo "</tr>";
				}

				$os                 = trim(pg_result($res,$i,os));
				$sua_os             = trim(pg_result($res,$i,sua_os));
				$digitacao          = trim(pg_result($res,$i,digitacao));
				$abertura           = trim(pg_result($res,$i,abertura));
				$fechamento         = trim(pg_result($res,$i,fechamento));
				$serie              = trim(pg_result($res,$i,serie));
				$excluida           = trim(pg_result($res,$i,excluida));
				$motivo_atraso      = trim(pg_result($res,$i,motivo_atraso));
				$tipo_os_cortesia   = trim(pg_result($res,$i,tipo_os_cortesia));
				$consumidor_revenda = trim(pg_result($res,$i,consumidor_revenda));
				$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
				$revenda_nome       = trim(pg_result($res,$i,revenda_nome));
				$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
				$posto_nome         = trim(pg_result($res,$i,posto_nome));
				$impressa           = trim(pg_result($res,$i,impressa));
				$extrato            = trim(pg_result($res,$i,extrato));
				$os_reincidente     = trim(pg_result($res,$i,os_reincidente));
				$produto_referencia = trim(pg_result($res,$i,produto_referencia));
				$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
				$produto_voltagem   = trim(pg_result($res,$i,produto_voltagem));

				if ($i % 2 == 0) {
					$cor   = "#F1F4FA";
					$botao = "azul";
				}else{
					$cor   = "#F7F5F0";
					$botao = "amarelo";
				}

				if (strlen($sua_os) == 0) $sua_os = $os;
				if ($login_fabrica == 1)  $sua_os = $posto_codigo.$sua_os;
				$produto = $produto_referencia . " - " . $produto_descricao;

				echo "<tr class='table_line' height='15' bgcolor='$cor' align='left'>";
				echo "<td nowrap>" . $sua_os . "</td>";
				echo "<td nowrap>" . $serie . "</td>";
				echo "<td nowrap ><acronym title='Data Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";
				echo "<td nowrap><acronym title='Data Fechamento: $fechamento' style='cursor: help;'>" . substr($fechamento,0,5) . "</acronym></td>";
				echo "<td nowrap><acronym title='Posto: $codigo_posto - $posto_nome' style='cursor: help;'>" . substr($posto_nome,0,15) . "</acronym></td>";
				echo "<td nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></td>";
				echo "<td nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao \nVoltagem: $produto_voltagem' style='cursor: help;'>" . substr($produto,0,20) . "</acronym></td>";
				echo "<td width='60' align='center'>";
				echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consultar_$botao.gif'></a>";
				echo "</td>\n";
				echo "</tr>";
			}
			echo "</table>";

		}
	}



# **********************************************************************************



	// OSs excluídas no período
	if (strlen($_GET['posto']) == 0){
		$sql = "SELECT    count(tbl_os_excluida.os) as total     ,
						  tbl_posto_fabrica.posto       ,
						  tbl_posto_fabrica.codigo_posto,
						  tbl_posto.nome AS posto_nome
				FROM      tbl_os_excluida
				JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os_excluida.posto
				JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os_excluida.produto
				LEFT JOIN tbl_posto_linha   ON  tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os_excluida.posto
				WHERE     tbl_os_excluida.fabrica = $login_fabrica
				AND       tbl_os_excluida.data_digitacao::date BETWEEN $data_inicial AND $data_final 
				GROUP BY  tbl_posto_fabrica.posto       ,
						  tbl_posto_fabrica.codigo_posto,
						  tbl_posto.nome 
				ORDER BY  count(tbl_os_excluida.os) ";
		//echo nl2br($sql);
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (@pg_numrows($res) > 0 AND strlen($msg_erro) == 0) {
			echo "<br><br>";
			echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='0' align='center'>";
			echo "<tr class='table_line'>";
			echo "<td align='center' background='#d9e2ef'><H2>OSs EXCLUÍDAS do período entre $data_inicial_01 e $data_final_01</H2></td>";
			echo "</tr>";
			echo "</table>";

			$ROWS = pg_numrows($res);

			$total_geral = 0;
			for ($i = 0 ; $i < $ROWS ; $i++) {
				$total_geral += trim(pg_result($res,$i,total));
			}

			echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr><td colspan='8' align='center'>Total de OSs $total_geral</td></tr>";

			for ($i = 0 ; $i < $ROWS ; $i++) {
				if ($i == 0) {
					echo "<tr class='menu_top' height='15'>";
					echo "<td>CÓDIGO</td>";
					echo "<td>POSTO</td>";
					echo "<td>TOTAL</td>";
					echo "<td>AÇÕES</td>";
					echo "</tr>";
				}

				$posto        = trim(pg_result($res,$i,posto));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				$posto_nome   = trim(pg_result($res,$i,posto_nome));
				$total        = trim(pg_result($res,$i,total));

				if ($i % 2 == 0) {
					$cor   = "#F1F4FA";
					$botao = "azul";
				}else{
					$cor   = "#F7F5F0";
					$botao = "amarelo";
				}

				echo "<tr class='table_line' height='15' bgcolor='$cor' align='left'>";
				echo "<td nowrap>" . $codigo_posto . "</td>";
				echo "<td nowrap>" . $posto_nome .  "</td>";
				echo "<td nowrap>" . $total . "</td>";
				echo "<td width='60' align='center'>";
				echo "<a href='$PHP_SELF?posto=$posto&btn_acao=procurar&data_inicial_01=$data_inicial_01&data_final_01=$data_final_01' target='_blank'><img border='0' src='imagens/btn_detalhar_$botao.gif'></a>";
				echo "</td>\n";
				echo "</tr>";
			}
			echo "</table>";
		}
	}else{
		// OSs do período
		$sql = "SELECT tbl_os.os                                                          ,
						tbl_os.sua_os                                                      ,
						LPAD(tbl_os.sua_os,20,'0')                   AS ordem              ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao          ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
						tbl_os.serie                                                      ,
						tbl_os.excluida                                                   ,
						tbl_os.motivo_atraso                                              ,
						tbl_os.tipo_os_cortesia                                           ,
						tbl_os.consumidor_revenda                                         ,
						tbl_os.consumidor_nome                                            ,
						tbl_os.revenda_nome                                               ,
						tbl_posto_fabrica.codigo_posto                                    ,
						tbl_posto.nome                              AS posto_nome         ,
						tbl_os_extra.impressa                                             ,
						tbl_os_extra.extrato                                              ,
						tbl_os_extra.os_reincidente                                       ,
						tbl_produto.referencia                      AS produto_referencia ,
						tbl_produto.descricao                       AS produto_descricao  ,
						tbl_produto.voltagem                        AS produto_voltagem   ,
						distrib.codigo_posto                        AS codigo_distrib
				FROM      tbl_os
				JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
				JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
				JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os
				LEFT JOIN tbl_posto_linha   ON  tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
				WHERE   tbl_os.fabrica = $login_fabrica
				AND     tbl_os.posto   = $posto
				AND     tbl_os.excluida IS TRUE
				AND     tbl_os.data_digitacao::date BETWEEN $data_inicial AND $data_final ";
//echo nl2br($sql); exit;
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (@pg_numrows($res) > 0 AND strlen($msg_erro) == 0) {
			$ROWS = pg_numrows($res);
			
			echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
			echo "<tr><td colspan='8' align='center'>Total de OSs $ROWS</td></tr>";
			for ($i = 0 ; $i < $ROWS ; $i++) {
				if ($i == 0) {
					echo "<tr class='menu_top' height='15'>";
					echo "<td>OS</td>";
					echo "<td>SÉRIE</td>";
					echo "<td>AB</td>";
					echo "<td>FC</td>";
					echo "<td>POSTO</td>";
					echo "<td>CONSUMIDOR</td>";
					echo "<td>PRODUTO</td>";
					echo "<td>AÇÕES</td>";
					echo "</tr>";
				}

				$os                 = trim(pg_result($res,$i,os));
				$sua_os             = trim(pg_result($res,$i,sua_os));
				$digitacao          = trim(pg_result($res,$i,digitacao));
				$abertura           = trim(pg_result($res,$i,abertura));
				$fechamento         = trim(pg_result($res,$i,fechamento));
				$serie              = trim(pg_result($res,$i,serie));
				$excluida           = trim(pg_result($res,$i,excluida));
				$motivo_atraso      = trim(pg_result($res,$i,motivo_atraso));
				$tipo_os_cortesia   = trim(pg_result($res,$i,tipo_os_cortesia));
				$consumidor_revenda = trim(pg_result($res,$i,consumidor_revenda));
				$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
				$revenda_nome       = trim(pg_result($res,$i,revenda_nome));
				$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
				$posto_nome         = trim(pg_result($res,$i,posto_nome));
				$impressa           = trim(pg_result($res,$i,impressa));
				$extrato            = trim(pg_result($res,$i,extrato));
				$os_reincidente     = trim(pg_result($res,$i,os_reincidente));
				$produto_referencia = trim(pg_result($res,$i,produto_referencia));
				$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
				$produto_voltagem   = trim(pg_result($res,$i,produto_voltagem));

				if ($i % 2 == 0) {
					$cor   = "#F1F4FA";
					$botao = "azul";
				}else{
					$cor   = "#F7F5F0";
					$botao = "amarelo";
				}

				if (strlen($sua_os) == 0) $sua_os = $os;
				if ($login_fabrica == 1)  $sua_os = $posto_codigo.$sua_os;
				$produto = $produto_referencia . " - " . $produto_descricao;

				echo "<tr class='table_line' height='15' bgcolor='$cor' align='left'>";
				echo "<td nowrap>" . $sua_os . "</td>";
				echo "<td nowrap>" . $serie . "</td>";
				echo "<td nowrap ><acronym title='Data Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";
				echo "<td nowrap><acronym title='Data Fechamento: $fechamento' style='cursor: help;'>" . substr($fechamento,0,5) . "</acronym></td>";
				echo "<td nowrap><acronym title='Posto: $codigo_posto - $posto_nome' style='cursor: help;'>" . substr($posto_nome,0,15) . "</acronym></td>";
				echo "<td nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></td>";
				echo "<td nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao \nVoltagem: $produto_voltagem' style='cursor: help;'>" . substr($produto,0,20) . "</acronym></td>";
				echo "<td width='60' align='center'>";
				echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consultar_$botao.gif'></a>";
				echo "</td>\n";
				echo "</tr>";
			}
			echo "</table>";

		}
	}

}
?>

<? include "rodape.php" ?>