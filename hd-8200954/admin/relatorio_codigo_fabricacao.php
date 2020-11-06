<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
#include 'includes/funcoes.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';

$btn_finalizar = $_POST['btn_finalizar'];

if ($btn_finalizar == 1) {
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0) {
			$erro = "Data Inválida";
		}
		
		if (strlen($erro) == 0) {
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
				if(strlen($erro)>0){
					$erro = "Data Inválida";
				}
			}
			
			//if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0) ." 00:00:00";
			if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
		}
	}
	
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_final_01"]) == 0) {
			$erro = "Data Inválida";
		}
		
		if (strlen($erro) == 0) {
			$data_final   = trim($_POST["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
				if(strlen($erro)>0){
					$erro = "Data Inválida";
				}
			}
			
			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}

	if(strlen($erro)==0){
		if($aux_data_inicial > $aux_data_final){
			$erro = "Data Inválida";
		}
	}
/*
	if(strlen($_POST["linha"]) > 0){
		$linha = trim($_POST["linha"]);
	}else{
		$erro = "Selecione a linha";
	}

	if(strlen($_POST["familia"]) > 0) {
		$familia = trim($_POST["familia"]);
	}else{
		$erro = "Selecione a família";
	}
*/


	##### Pesquisa de produto #####
	$produto_referencia = trim($_POST["produto_referencia"]);
	$produto_descricao  = trim($_POST["produto_descricao"]);
	$voltagem           = trim($_POST["voltagem"]);
	
	if(strlen($produto_referencia)==0){
		$erro = " Informe o Produto ";
	}
	if(strlen($erro)==0){
		$produto_referencia = str_replace (".","",$produto_referencia);
		$produto_referencia = str_replace ("-","",$produto_referencia);
		$produto_referencia = str_replace ("/","",$produto_referencia);
		$produto_referencia = str_replace (" ","",$produto_referencia);
		if (strlen($produto_referencia) > 0) {
			$sql = "SELECT tbl_produto.produto    ,
							tbl_produto.referencia ,
							tbl_produto.descricao  
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica
					AND   tbl_produto.ativo IS TRUE
					AND   tbl_produto.voltagem = '$voltagem'
					AND tbl_produto.referencia_pesquisa = '$produto_referencia'";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$produto            = pg_result($res,0,produto);
				$produto_referencia = pg_result($res,0,referencia);
				$produto_descricao  = pg_result($res,0,descricao);
			}else{
				$erro = " Produto não encontrado. ";
			}
		}

		if(strlen($_POST["codigo_fabricacao"]) > 0){
			$codigo_fabricacao = trim($_POST["codigo_fabricacao"]);
		}else{
			$erro = "Digite o código de fabricação.";
		}
	}
	
	
	if (strlen($erro) == 0) $listar = "ok";
	
	if (strlen($erro) > 0) {
		$data_inicial       = trim($_POST["data_inicial_01"]);
		$data_final         = trim($_POST["data_final_01"]);
		//$familia            = trim($_POST["familia"]);
		//$linha              = trim($_POST["linha"]);
		$produto_referencia = trim($_POST["produto_referencia"]);
		$produto_descricao  = trim($_POST["produto_descricao"]);
		$codigo_fabricacao  = trim($_POST["codigo_fabricacao"]);
		
		
		$msg = $erro;
	}
}



$layout_menu = "auditoria";
$title = "RELATÓRIO - CÓDIGO DE FABRICAÇÃO";

include "cabecalho.php";

include "javascript_pesquisas.php";
include "javascript_calendario.php";

?>

<script language="JavaScript">

	$(function(){
		$("#data_inicial_01").datePicker({startDate:'01/01/2000'});
		$("#data_final_01").datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});

</script>

<style type="text/css">

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
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
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

<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->
<!--
<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

 <script language="javascript" src="js/cal_conf2.js"></script> -->

<iframe style="visibility: hidden; position: absolute;" id="FrameFamilia"></iframe>

<DIV ID="container" style="width: 100%; ">

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
<? if (strlen($msg) > 0){ ?>
	<tr class="msg_erro">
		<td colspan="6">
			<? echo $msg ?>
		</td>
	</tr>
<? } ?>
  <TR>
	<TD colspan="6" class="titulo_tabela">Parâmetros de Pesquisa</TD>
  </TR>

  <tr>
	<td colspan="6">&nbsp;</td>
  </tr>
<!-- 
  <TR>
    <TD  style="width: 10px">&nbsp;</TD>
	<TD  colspan='2'>As datas se referem à Geração do Extrato<br>e somente OS aprovadas são consideradas</TD>
    <TD  style="width: 10px">&nbsp;</TD>
  </TR>
 -->
  <TR>
    <TD  style="width: 145px">&nbsp;</TD>
	<TD >Data Inicial</TD>
    <TD >Data Final</TD>
    <TD  style="width: 10px">&nbsp;</TD>
    <TD  style="width: 50px" colspan="2">&nbsp;</TD>
  </TR>
  <TR>
    <TD  style="width: 10px">&nbsp;</TD>
	<TD  style="width: 160px"><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''" class="frm"></TD>
	<TD  style="width: 180px"><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''" class="frm"></TD>
    <TD  style="width: 10px">&nbsp;</TD>
    <TD  style="width: 10px" colspan="2">&nbsp;</TD>
  </TR>


	<tr >
		<td width="10">&nbsp;</td>
		<td>Referência do Produto</td>
		<td>Descrição do Produto</td>
		<td >&nbsp;</td>
		<td width="10" colspan="2">&nbsp;</td>
	</tr>
	<tr >
		<td width="10">&nbsp;</td>
		<td>
			<input type="text" name="produto_referencia" size="15" value="<?echo $produto_referencia?>" class="frm">
			<img src="imagens/lupa.png" align="absmiddle" onclick="fnc_pesquisa_produto (document.forms[0].produto_referencia, document.forms[0].produto_descricao, 'referencia', document.forms[0].voltagem)" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td>
			<input type="text" name="produto_descricao" size="20" value="<?echo $produto_descricao?>" class="frm">
			<img src="imagens/lupa.png" align="absmiddle" onclick="fnc_pesquisa_produto (document.forms[0].produto_referencia, document.forms[0].produto_descricao, 'descricao', document.forms[0].voltagem)" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td>
			<input type="text" name="voltagem" size="5" value="<?echo $voltagem?>" class="frm">
		</td>
		<td width="10" colspan="2">&nbsp;</td>
	</tr>
<? 
if (1 == 2){
?>
<TR width = '100%' align="center">
	<TD colspan='6' CLASS='table_line' >Linha</TD>
</TR>

<TR width='100%' align="center">
	<TD colspan='6' CLASS='table_line'>
		
			<!-- começa aqui -->
			<?
			$sql = "SELECT  * 
					FROM    tbl_linha
					WHERE   tbl_linha.fabrica = $login_fabrica
					ORDER BY tbl_linha.nome";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select name=\"linha\" size=\"1\" class=\"frm\" onChange=\"javascript: CarregaFamilia(this)\">\n";
				echo "<option value=''>ESCOLHA</option>\n";
				
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha   = trim(pg_result($res,$x,linha));
					$aux_nome = trim(pg_result($res,$x,nome));
					
					echo "<option value='$aux_linha'"; 
					if ($linha == $aux_linha){
						echo " SELECTED "; 
						$mostraMsglinha = "<br> da Linha $aux_nome";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n";
			}
			?>
		
	</TD>
  </TR>
	 <TR width = '100%' align="center">
		<TD colspan='4' CLASS='table_line' > Família</TD>
     </TR>

  <TR width='100%' align="center">
	  <TD colspan='4' CLASS='table_line'>
		
			<!-- começa aqui -->
			<?
			$sql = "SELECT  *
					FROM     tbl_familia
					WHERE    tbl_familia.fabrica = $login_fabrica
					ORDER BY tbl_familia.descricao";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select name=\"familia\" size=\"1\" class=\"frm\" style=\"width: 209px\"\n";
				echo "<option value=''>ESCOLHA</option>\n";
/*
				
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_familia   = trim(pg_result($res,$x,familia));
					$aux_descricao = trim(pg_result($res,$x,descricao));
					
					echo "<option value='$aux_familia'"; 
					if ($familia == $aux_familia){
						echo " SELECTED "; 
						$mostraMsgfamilia = "<br> da Família $aux_descricao";
					}
					echo ">$aux_descricao</option>\n";
				}
*/
				echo "</select>\n";
			}
			?>
		
	</TD>
  </TR>
<? 
}
?>

  <TR width = '100%' align="left">
	<TD >&nbsp;</TD>
	<TD  nowrap colspan="5">
		
			Código de Fabricação<br>
			<input type="text" name="codigo_fabricacao" size="10" value="<?echo $codigo_fabricacao?>" class="frm">
		
	</TD>
  </TR> 
	<TR><TD colspan="6">&nbsp;</TD></TR>
  <TR>
    <input type='hidden' name='btn_finalizar' value='0'>
    <TD colspan="6"  style="text-align: center;"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" alt='Clique AQUI para pesquisar'></TD>
  </TR>
</TABLE>

</FORM>

<!-- =========== AQUI TERMINA O FORMULÁRIO FRM_PESQUISA =========== -->
</DIV>

<?
if ($listar == "ok") {
	$aux_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2) . " 00:00:00";
	$aux_data_final   = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2) . " 23:59:59";
	
	$sql = "SELECT count(tbl_os.os) as ocorrencia,
					tbl_os.codigo_fabricacao 
			FROM tbl_os 
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			WHERE   tbl_os.fabrica      = $login_fabrica 
			AND     tbl_os.data_abertura between '$aux_data_inicial' and '$aux_data_final'
			AND     tbl_produto.produto  = $produto
			AND     tbl_os.codigo_fabricacao LIKE '$codigo_fabricacao%'
			GROUP BY tbl_os.codigo_fabricacao
			ORDER BY ocorrencia; ";
//echo nl2br($sql) . "<br>";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<br>";
		
		echo "<font size='2'><b><font size='2'></font>";
		
		echo"<TABLE width='700' border='0' cellspacing='1' cellpadding='2' align='center' class='tabela'>";
		echo "<tr class='titulo_tabela'>";
		echo "<td colspan='3'>";
		echo "Período: $data_inicial e $data_final <br />";
		echo "Produto: $produto_referencia - $produto_descricao - $voltagem <br />";
		echo "Cód Fabricaça: $codigo_fabricacao";		
		echo "</td>";
		echo "</tr>";
		echo"	<TR class='titulo_coluna'>";
		echo"		<TD width='30%' height='15' align='left'><b>Cod Fabricação</b></TD>";
		echo"		<TD width='10%' height='15'><b>Ocorrência</b></TD>";
		echo"		<TD width='05%' height='15'><b>%</b></TD>";
		echo"	</TR>";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
		}
		
		for ($i=0; $i<pg_numrows($res); $i++){
			$codigo_fabricacao = trim(pg_result($res,$i,codigo_fabricacao));
			$ocorrencia = trim(pg_result($res,$i,ocorrencia));
			
			if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
			
			
			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
			
			echo "<TR bgcolor='$cor'>";
			echo "<TD align='left' nowrap>$codigo_fabricacao</TD>";
			echo "<TD align='center' nowrap>$ocorrencia</TD>";
			echo "<TD align='right' nowrap>". number_format($porcentagem,2,",",".") ." %</TD>";
			echo "</TR>";
		}
		echo"</TABLE>";
		
				
		// monta URL
		$data_inicial       = trim($_POST["data_inicial_01"]);
		$data_final         = trim($_POST["data_final_01"]);
		$codigo_fabricacao  = trim($_POST["codigo_fabricacao"]);
		
		echo"<table width='700' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'><input type='button' onclick=\" window.location='relatorio_field_call_rate_produto_familia-xls.php?data_inicial=$data_inicial&data_final=$data_final&linha=$linha&familia=$familia&estado=$estado&posto=$posto&criterio=$criterio'\" value='Download em Excel'>";
		
		echo "</tr>";
		echo "</table>";
		
	}else{
		echo "<br>";
		
		echo "<b>Nenhum resultado encontrado entre $data_inicial e $data_final </b>";
	}
	
}

flush();

?>

<p>

<? include "rodape.php" ?>
