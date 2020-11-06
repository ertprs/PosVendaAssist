<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

if ($login_fabrica == 14){
	header("Location: relatorio_field_call_rate_produto_familia.php");
	exit;
}

// Criterio padrão
$_POST["criterio"] = "data_digitacao";
//////////////////

if ($btn_finalizar == 1) {
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0) {
			$erro .= "Favor informar a data inicial para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0) ." 00:00:00";
			if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
		}
	}
	
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_final_01"]) == 0) {
			$erro .= "Favor informar a data final para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_final   = trim($_POST["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0) ." 23:59:59";
			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}
	
	if(strlen($_POST["linha"]) > 0) $linha = trim($_POST["linha"]);

	if(strlen($_POST["estado"]) > 0){
		$estado = trim($_POST["estado"]);
		$mostraMsgEstado = "<br>no ESTADO $estado";
	}

	/*if (strlen($erro) == 0) {
		if(strlen($_POST["criterio"]) == 0) {
			$erro .= "Favor informar o critério (Abertura ou Lançamento de OS) para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$aux_criterio = trim($_POST["criterio"]);
		}
	}*/
	
	if (strlen($erro) == 0) $listar = "ok";
	
	if (strlen($erro) > 0) {
		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_final   = trim($_POST["data_final_01"]);
		$linha        = trim($_POST["linha"]);
		$estado       = trim($_POST["estado"]);
		$criterio     = trim($_POST["criterio"]);
		
		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $erro;
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : LINHA DE PRODUTO";

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

function AbrePeca(produto,data_inicial,data_final,linha,estado){
	janela = window.open("relatorio_field_call_rate_pecas2.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado,"produto",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
	janela.focus();
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
	border: 0px solid;
	background-color: #D9E2EF
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

<style type="text/css">
<!--
.titPreto14 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titDatas12 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titChamada10{
	background-color: #596D9B;
	color: #ffffff;
	text-align: center;
	font:11px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.conteudo10 {
	color: #000000;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}

-->
</style>

<? include "javascript_pesquisas.php" ?>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>

<DIV ID="container" style="width: 100%; ">

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<?
if (strlen($msg) > 0){
?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $msg ?>
			
	</td>
</tr>
</table>

<br>
<?
}
?>

<br>

<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
  <TR>
	<TD colspan="4" class="menu_top"><div align="center"><b>Pesquisa</b></div></TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2'><center>Este relatório considera a data de geração do extrato aprovado.</center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line"><center>Data Inicial</center></TD>
    <TD class="table_line"><center>Data Final</center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaInicial_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></center></TD>
	<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaFinal_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>

  <TR width = '100%' align="center">
	  <TD colspan='4' CLASS='table_line' > <center>Linha</center></TD>
  </TR>

  <TR width='100%' align="center">
	  <TD colspan='4' CLASS='table_line'>
		<center>
			<!-- começa aqui -->
			<?
			$sql = "SELECT  *
					FROM    tbl_linha
					WHERE   tbl_linha.fabrica = $login_fabrica
					ORDER BY tbl_linha.nome;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select name='linha'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha = trim(pg_result($res,$x,linha));
					$aux_nome  = trim(pg_result($res,$x,nome));
					
					echo "<option value='$aux_linha'"; 
					if ($linha == $aux_linha){
						echo " SELECTED "; 
						$mostraMsgLinha = "<br> da LINHA $aux_nome";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n";
			}
			?>
		</center>
	</TD>
  </TR>

  <TR width = '100%' align="center">
	  <TD colspan = '4' CLASS='table_line' > <center>Por região</center></TD>
  </TR>
  <TR width = '100%' align="center">
	<td colspan = '4' CLASS='table_line'>
		<center>
		<select name="estado" size="1">
			<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
<!-- 			<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>UF</option> -->
			<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
			<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
			<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
			<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
			<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
			<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
			<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
			<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
			<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
			<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
			<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
			<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
			<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
			<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
			<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
			<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
			<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
			<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
			<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
			<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
			<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
			<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
			<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
			<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
			<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
			<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
			<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
		</select>
		</center>
	</td>
  </TR>

  <TR>
    <input type='hidden' name='btn_finalizar' value='0'>
    <TD colspan="4" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
  </TR>
</TABLE>

</FORM>

<!-- =========== AQUI TERMINA O FORMULÁRIO FRM_PESQUISA =========== -->
</DIV>

<?
if ($listar == "ok") {
/*
	$sql = "SELECT  vw_quebra_produto.fabrica   ,
					vw_quebra_produto.produto   ,
					vw_quebra_produto.referencia,
					vw_quebra_produto.descricao , ";
	
	if (strlen($linha) > 0)  $sql .= "vw_quebra_produto.linha , ";
	if (strlen($estado) > 0) $sql .= "vw_quebra_produto.estado, ";
	
	$sql .= "	sum(vw_quebra_produto.ocorrencia) AS ocorrencia,
				sum(vw_quebra_produto.soma_mobra) AS soma_mobra,
				sum(vw_quebra_produto.soma_peca)  AS soma_peca ,
				sum(vw_quebra_produto.soma_total) AS soma_total
			FROM (
				SELECT  tbl_os.fabrica        ,
						tbl_produto.produto   ,
						tbl_produto.referencia,
						tbl_produto.descricao , ";
	
	if (strlen($linha) > 0)  $sql .= "tbl_linha.linha, ";
	if (strlen($estado) > 0) $sql .= "tbl_posto.estado, ";
	
	$sql .= "			count(tbl_os.produto)                    AS ocorrencia,
						sum(tbl_os.mao_de_obra)                  AS soma_mobra,
						sum(tbl_os.pecas)                        AS soma_peca ,
						sum(tbl_os.pecas + tbl_os.mao_de_obra)   AS soma_total,
						date_trunc('day', tbl_os.data_digitacao) AS digitada  ,
						date_trunc('day', tbl_os.finalizada)     AS finalizada
				FROM        tbl_os
				JOIN        tbl_posto   ON tbl_posto.posto     = tbl_os.posto
				JOIN        tbl_produto ON tbl_produto.produto = tbl_os.produto
				JOIN        tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
				WHERE       tbl_os.fabrica = $login_fabrica ";
	if (strlen($linha) > 0)  $sql .= "AND tbl_produto.linha = $linha ";

	$sql .= "		GROUP BY    tbl_os.fabrica        ,
							tbl_produto.produto   ,
							tbl_produto.referencia,
							tbl_produto.descricao ";
	
	if (strlen($linha) > 0)  $sql .= ", tbl_linha.linha  ";
	if (strlen($estado) > 0) $sql .= ", tbl_posto.estado ";
	
	$sql .= "	, date_trunc('day', tbl_os.data_digitacao),
				date_trunc('day', tbl_os.finalizada)
				) AS vw_quebra_produto
			WHERE vw_quebra_produto.digitada BETWEEN '$aux_data_inicial' AND '$aux_data_final'
			AND   vw_quebra_produto.fabrica = $login_fabrica ";
	
	if (strlen($linha) > 0)  $sql .= "AND vw_quebra_produto.linha  = '$linha' ";
	if (strlen($estado) > 0) $sql .= "AND vw_quebra_produto.estado = '$estado' ";

	$sql .= "GROUP BY   vw_quebra_produto.fabrica   ,
						vw_quebra_produto.produto   ,
						vw_quebra_produto.referencia,
						vw_quebra_produto.descricao ";
	
	if (strlen($linha) > 0)  $sql .= ", vw_quebra_produto.linha ";
	if (strlen($estado) > 0) $sql .= ", vw_quebra_produto.estado ";
	
	$sql .= "ORDER BY sum(vw_quebra_produto.ocorrencia) DESC";

	$cond_1 = "1=1";
	$cond_2 = "1=1";
	if (strlen ($linha)  > 0) $cond_1 = " tbl_produto.linha = $linha ";
	if (strlen ($estado) > 0) $cond_2 = " tbl_posto.estado = '$estado' ";

	$aux_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2) . " 00:00:00";
	$aux_data_final   = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2) . " 23:59:59";
	
	$sql = "SELECT tbl_produto.produto, tbl_produto.ativo, tbl_produto.linha, tbl_produto.referencia, tbl_produto.descricao, os.ocorrencia
			FROM tbl_produto 
			JOIN (SELECT tbl_os.produto, COUNT(*) AS ocorrencia
					FROM tbl_os
					JOIN tbl_posto   USING (posto)
					JOIN tbl_produto USING (produto)
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.data_digitacao BETWEEN '$aux_data_inicial'::date AND '$aux_data_final'
					AND   tbl_os.excluida IS NOT TRUE
					AND   $cond_1
					AND   $cond_2
					GROUP BY tbl_produto.referencia
			) os ON tbl_produto.produto = os.produto
			ORDER BY os.ocorrencia DESC";
*/
	$cond_1 = "1=1";
	$cond_2 = "1=1";
	$cond_3 = "1=1";
	if (strlen ($linha)  > 0) $cond_1 = " tbl_produto.linha = $linha ";
	if (strlen ($estado) > 0) $cond_2 = " tbl_posto.estado  = '$estado' ";
	if (strlen ($posto)  > 0) $cond_3 = " tbl_posto.posto   = $posto ";

/*
$sql = "SELECT tbl_produto.produto, tbl_produto.ativo, tbl_produto.referencia, tbl_produto.descricao, fcr1.qtde AS ocorrencia, tbl_produto.familia, tbl_produto.linha
			FROM tbl_produto
			JOIN (SELECT tbl_os.produto, COUNT(*) AS qtde
					FROM tbl_os
					JOIN (SELECT tbl_os_extra.os , (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
							FROM tbl_os_extra
							JOIN tbl_extrato USING (extrato)
							WHERE tbl_extrato.fabrica = $login_fabrica
							AND   tbl_extrato.data_geracao BETWEEN '$aux_data_inicial' AND '$aux_data_final' ";
	if ($login_fabrica == 14) $sql .= " AND   tbl_extrato.liberado IS NOT NULL";
	$sql .= " ) fcr ON tbl_os.os = fcr.os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
					AND tbl_os.excluida IS NOT TRUE
					AND $cond_2
					AND $cond_3
					GROUP BY tbl_os.produto
			) fcr1 ON tbl_produto.produto = fcr1.produto
			WHERE $cond_1
			ORDER BY fcr1.qtde DESC " ;
//if ($ip == "201.43.11.216") { echo nl2br($sql); exit;}
*/



	$sql = "SELECT tbl_produto.produto, tbl_produto.ativo, tbl_produto.referencia, tbl_produto.descricao, fcr1.qtde AS ocorrencia, tbl_produto.familia, tbl_produto.linha
			FROM tbl_produto
			JOIN (SELECT tbl_os.produto, COUNT(*) AS qtde
					FROM tbl_os
					JOIN (SELECT tbl_os_extra.os , (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
							FROM tbl_os_extra
							JOIN tbl_os USING (os)
							WHERE tbl_os.fabrica = $login_fabrica
							AND  tbl_os.posto <> '6359'
							AND   tbl_os.data_digitacao BETWEEN '$aux_data_inicial' AND '$aux_data_final') fcr ON tbl_os.os = fcr.os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
					AND tbl_os.excluida IS NOT TRUE
					AND $cond_2
					AND $cond_3
					GROUP BY tbl_os.produto
			) fcr1 ON tbl_produto.produto = fcr1.produto
			WHERE $cond_1
			ORDER BY fcr1.qtde DESC " ;
//if ($ip == "201.43.11.216") { echo nl2br($sql); exit;}

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<br>";
		
		echo "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado</b>";
		
		echo "<br><br>";
		echo "<FONT SIZE=\"2\">(*) Peças que estão inativas.</FONT>";
		echo"<TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"	<TR>";
		echo"		<TD width='30%' height='15' class='table_line'><b>Referência</b></TD>";
		echo"		<TD width='55%' height='15' class='table_line'><b>Produto</b></TD>";
		echo"		<TD width='10%' height='15' class='table_line'><b>Ocorrência</b></TD>";
		echo"		<TD width='05%' height='15' class='table_line'><b>%</b></TD>";
		echo"	</TR>";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
		}
		
		for ($i=0; $i<pg_numrows($res); $i++){
			$referencia = trim(pg_result($res,$i,referencia));
			$ativo      = trim(pg_result($res,$i,ativo));
			$descricao  = trim(pg_result($res,$i,descricao));
			$produto    = trim(pg_result($res,$i,produto));
			if (strlen($linha) > 0) $linha = trim(pg_result($res,$i,linha));
			$ocorrencia = trim(pg_result($res,$i,ocorrencia));
			
#			if (strlen($estado) > 0)   $estado      = trim(pg_result($res,$i,estado));
			if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
			
			$cor = '2';
			if ($i % 2 == 0) $cor = '1';
// Todo produto que for inativo estará com um (*) na frente para indicar se está Inativo ou Ativo.
				if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';} 
			echo "<TR class='bgTRConteudo$cor'>";
			echo "<TD class='conteudo10' align='left' nowrap>$ativo<a href='javascript:AbrePeca(\"$produto\",\"$aux_data_inicial\",\"$aux_data_final\",\"$linha\",\"$estado\");'>$referencia</a></TD>";
			echo "<TD class='conteudo10' align='left' nowrap>$descricao</TD>";
			echo "<TD class='conteudo10' align='center' nowrap>$ocorrencia</TD>";
			echo "<TD class='conteudo10' align='right' nowrap>". number_format($porcentagem,2,",",".") ." %</TD>";
			echo "</TR>";
		}
		echo"</TABLE>";
		
		echo "<br>";
		echo "<hr width='600'>";
		echo "<br>";
		
		// monta URL
		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_final   = trim($_POST["data_final_01"]);
		$linha        = trim($_POST["linha"]);
		$estado       = trim($_POST["estado"]);
		$criterio     = trim($_POST["criterio"]);
		
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='relatorio_field_call_rate_produto-xls.php?data_inicial=$data_inicial&data_final=$data_final&linha=$linha&estado=$estado&criterio=$criterio' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
		
	}else{
		echo "<br>";
		
		echo "<b>Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado</b>";
	}
	
}

flush();

?>

<p>

<? include "rodape.php" ?>
