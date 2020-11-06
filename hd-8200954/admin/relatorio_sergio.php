<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';



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
	janela = window.open("relatorio_field_call_rate_pecas.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado,"produto",'scrollbars=yes,width=750,height=280,top=0,left=0');
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

<TABLE width="400" align="center" border="1" cellspacing="0" cellpadding="2">
  <TR>
	<TD colspan="4" class="menu_top"><div align="center"><b>Pesquisa</b></div></TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2'><center>Por favor entre com um periodo de datas:</center></TD>
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
  <TR>
    <input type='hidden' name='btn_finalizar' value='0'>
    <TD colspan="4" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
  </TR>
</TABLE>

</FORM>

<!-- =========== AQUI TERMINA O FORMULÁRIO FRM_PESQUISA =========== -->
</DIV>

<?
$btn_finalizar= $_POST['btn_finalizar'];
if ($btn_finalizar == 1) {
if (strlen($erro) == 0) {
				if (strlen($_POST["data_inicial_01"]) == 0) {
						$erro .= "Favor informar a data inicial para pesquisa<br>";
				}
								
				if (strlen($_POST["data_final_01"]) == 0) {
						$erro .= "Favor informar a data final para pesquisa<br>";
				}
			$data_inicial = trim($_POST["data_inicial_01"]);
			$data_final   = trim($_POST["data_final_01"]);
//		echo"data incial: $data_inicial";
//		echo"data incial: $data_final";
			




echo "<TABLE width='700' border='1' cellspacing='2' cellpadding='1' align='center'>";
echo "<tr bgcolor='#D9E2EF'>";
echo "<td colspan='10'> <font size='2'><B>Relatório de O.S dos últimos 5 dias de Maio</b></font></td>";
echo "</tr>";
echo "<tr bgcolor='#f4f4f4'>";
	echo "<td><font size='2'>N.O.S </font></td>";
	echo "<td><font size='2'>Nome Consumidor</font></td>";
	echo "<td><font size='2'>Telefone</font></td>";
	echo "<td><font size='2'>Posto</font></td>";
	echo "<td><font size='2'>Nome do Posto</font></td>";
	echo "<td><font size='2'>Produto</font></td>";
	echo "<td><font size='2'>Descrição</font></td>";
	echo "<td><font size='2'>Cidade</font></td>";
	echo "<td><font size='2'>Estado</font></td>";
	echo "<td><font size='2'>Dt. Aquisição</font></td>";
echo "</tr>";

$sql = "SELECT 
		tbl_os.sua_os, 
		tbl_os.consumidor_nome, 
		tbl_os.consumidor_fone, 
		tbl_posto_fabrica.codigo_posto,
		tbl_posto.nome,
		tbl_produto.referencia,
		tbl_produto.descricao, 
		tbl_os.consumidor_cidade, 
		tbl_os.consumidor_estado, 
		to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento   ,
		to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           
		FROM tbl_os 
		JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto 
		JOIN tbl_posto   ON tbl_os.posto   = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
		WHERE tbl_os.data_fechamento BETWEEN '2006-05-26 00:00:00' AND '2006-05-31 23:59:59' 
		AND tbl_os.fabrica=$login_fabrica
		ORDER BY tbl_os.sua_os
		LIMIT 1000";
//		ECHO nl2br($sql);
$res = pg_exec ($con,$sql);
if (@pg_numrows($res) >= 0) {
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$sua_os              = pg_result($res,$i,sua_os);
	$consumidor_nome     = pg_result($res,$i,consumidor_nome);
	$consumidor_fone     = pg_result($res,$i,consumidor_fone);
	$codigo_posto        = pg_result($res,$i,codigo_posto);
	$nome                = pg_result($res,$i,nome);
	$referencia          = pg_result($res,$i,referencia);
	$descricao           = pg_result($res,$i,descricao);
	$consumidor_cidade   = pg_result($res,$i,consumidor_cidade);
	$consumidor_estado   = pg_result($res,$i,consumidor_estado);
	$data_nf             = pg_result($res,$i,data_nf);
							
	$cor='#F2F7FF';
	if ($i % 2 == 0) $cor = '#FFFFFF';
							//parei aqui
	echo "<tr bgcolor='$cor'>";
	echo "<td><font size='2'>&nbsp; $sua_os </font></td>";
	echo "<td><font size='2'>&nbsp;$consumidor_nome</font></td>";
	echo "<td><font size='2'>&nbsp;$consumidor_fone</font></td>";
	echo "<td><font size='2'>&nbsp;$codigo_posto</font></td>";
	echo "<td><font size='2'>&nbsp;$nome</font></td>";
	echo "<td><font size='2'>&nbsp;$referencia</font></td>";
	echo "<td><font size='2'>&nbsp;$descricao</font></td>";
	echo "<td><font size='2'>&nbsp;$consumidor_cidade</font></td>";
	echo "<td><font size='2'>&nbsp;$consumidor_estado</font></td>";
	echo "<td><font size='2'>&nbsp;$data_nf</font></td>";
	echo "</tr>";
	}
}else{
	echo "<tr bgcolor='f4f4f4'>";
	echo "<td><font size='2'>Nenhum resultado encontrado</font></td>";
	echo "</tr>";
	}
echo "</table>";

}else{echo "<BR><BR><center>$erro</center>";}

}





?>

<p>

<? include "rodape.php" ?>
