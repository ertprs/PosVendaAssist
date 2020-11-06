<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";

$msg = "";

$layout_menu = "callcenter";
$title       = "Consulta Pesquisa Satisfação";

$btn_acao = $_POST['acao'];
$produto = $_POST['produto'];

if(strlen($posto_codigo) > 0) {
	$sql="SELECT posto
			FROM tbl_posto
			WHERE nome ='$posto_nome'";
	$res=pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
		$posto=trim(pg_result($res,0,posto));
	}
}
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

.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}

.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<script language="JavaScript">
function fnc_pesquisa_posto (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.posto_codigo	= campo;
		janela.posto_nome	= campo2;
		janela.focus();
	}
}

</script>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language='javascript' src='../ajax.js'></script>


<? 
include "cabecalho.php";
include "javascript_pesquisas.php"; 

if(strlen($posto) >0 and strlen($produto) >0) {
	$msg="SELECIONE PELO PRODUTO OU PELO POSTO PARA PESQUISAR";
}
if(strlen($posto) >0 and strlen($data_inicial) >0){
	$msg="SELECIONE PELO POSTO OU PELA DATA PARA PESQUISAR";
}
if(strlen($produto) >0 and strlen($data_inicial) >0){
	$msg="SELECIONE PELO PRODUTO OU PELA DATA PARA PESQUISAR";
}
if(strlen($msg) > 0){
	echo "<font color='blue'>$msg</font>"; 
}

?>

<br>

<form name="frm_relatorio" method="POST" action="<?echo $PHP_SELF?>">
<table width="450" align="center" border="0" cellspacing="0" cellpadding="2">
<input type="hidden" name="acao">

	<tr class="Titulo">
		<td colspan="4">Selecione a data, o produto ou posto para pesquisar</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td align='right' nowrap><font size='2'>Data Inicial</td>
		<td align='left' nowrap>
			<input type="text" name="data_inicial" size="12" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
			<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td align='right' nowrap><font size='2'>Data Final</td> 
		<td align='left' nowrap>
			<input type="text" name="data_final" size="12" maxlength="10"  value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
			<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan=4><hr></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td align='right'><font size='2'>Produto</font></td>
	<td colspan='3'>
<?

$sql = "SELECT  tbl_produto.referencia,
				tbl_produto.descricao, tbl_produto.produto
		FROM tbl_produto
		JOIN tbl_os on tbl_os.produto = tbl_produto.produto
		JOIN tbl_suggar_questionario on tbl_suggar_questionario.os= tbl_os.os
		GROUP BY
			tbl_produto.referencia,
			tbl_produto.descricao, tbl_produto.produto
		ORDER BY
			tbl_produto.referencia";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){

	echo "<Select name='produto' value='1' style='width:350px'>";
	echo "<option value=''></option>";
	for($x=0;$x<pg_numrows($res);$x++){
		$xproduto = pg_result($res,$x,produto);
		$produto_referencia = pg_result($res,$x,referencia);
		$produto_descricao = pg_result($res,$x,descricao);
		echo "<option value='$xproduto' "; if($produto==$xproduto){echo "SELECTED";} 
		echo "> $produto_referencia - $produto_descricao</option>";
	}
	echo "</select>";
}else {
echo "Nenhum resultado";
}

?>
	</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan=4><hr></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
	<td nowrap colspan="2" align="center">
		<font size="2" face="Geneva, Arial, Helvetica, san-serif">Código do Posto</font>
		<br>
		<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>">&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.posto_codigo,document.frm_relatorio.posto_nome,'codigo')"></A>
	</td>

	<td nowrap colspan="2" align="center">
		<font size="2" face="Geneva, Arial, Helvetica, san-serif">Nome do Posto</font>
		<br>
		<input class="frm" type="text" name="posto_nome" size="20" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens/btn_lupa.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.posto_codigo,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></A>
	</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4" align="center"><img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>
<?
$data_inicial = $_POST['data_inicial'];
$data_final   = $_POST['data_final']  ;

if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
if (strlen($_GET['data_final']) > 0)   $data_final   = $_GET['data_final']  ;

$data_inicial = str_replace (" " , "" , $data_inicial);
$data_inicial = str_replace ("-" , "" , $data_inicial);
$data_inicial = str_replace ("/" , "" , $data_inicial);
$data_inicial = str_replace ("." , "" , $data_inicial);

$data_final   = str_replace (" " , "" , $data_final)  ;
$data_final   = str_replace ("-" , "" , $data_final)  ;
$data_final   = str_replace ("/" , "" , $data_final)  ;
$data_final   = str_replace ("." , "" , $data_final)  ;

if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

if (strlen ($data_inicial) > 0)  $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
if (strlen ($data_final)   > 0)  $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);
?>
<Br><BR>

<?
if(strlen($btn_acao)>0 AND strlen($msg) == 0){

	$data_inicial   = trim($_POST["data_inicial"]);
	$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

	if (strlen ( pg_errormessage ($con) ) > 0) {
		$erro = pg_errormessage ($con) ;
	}

	if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);

	if (strlen($_POST["data_final"]) == 0) $erro .= "Favor informar a data final para pesquisa<br>";
	if (strlen($erro) == 0) {
		$data_final   = trim($_POST["data_final"]);
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
		
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
		}
		
		if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);

	}

	$sql="SELECT tbl_os.os,
				 tbl_os.posto,
				 tbl_os.consumidor_nome, 
				 tbl_os.consumidor_cidade,
				 tbl_os.consumidor_estado,
				 tbl_produto.referencia,
				 tbl_produto.descricao,
				 tbl_posto.nome as posto_nome
			FROM tbl_suggar_questionario 
			JOIN tbl_os using(os)
			JOIN tbl_posto using(posto)
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto ";

	if(strlen($produto) > 0) {
		$sql.="WHERE tbl_os.produto = $produto";
	}
	
	if(strlen($posto) > 0) {
		$sql.="WHERE tbl_os.posto=$posto";
	}

	if(strlen($aux_data_inicial) >0 and strlen($aux_data_final) > 0) {
		$sql.="WHERE tbl_suggar_questionario.data between '$aux_data_inicial' and '$aux_data_final' ";
	}
	$res =pg_exec($con,$sql);

	if(pg_numrows($res) == 0) {
		echo "Nenhum resultado encontrado";
	} else {
	echo "<table border='1' cellpadding='4' cellspacing='1'  bgcolor='#92A8AD' width='600' align='center' style='font-family: verdana; font-size: 11px'>";
	echo "<tr>";
	echo "<TD align='center'>	";
	echo "OS";
	echo "</td>";

	if(strlen($produto) > 0) {
		echo "<TD align='center'>";
		echo "NOME DO POSTO";
		echo "</td>";
	}

	if(strlen($posto) > 0) {
		echo "<TD align='center'>";
		echo "REFERÊNCIA";
		echo "</td>";
		echo"<TD align='center'>";
		echo "DESCRICAO";
		echo "</td>";
	}

	if(strlen($aux_data_inicial) > 0) {
		echo "<TD align='center'>";
		echo "NOME DO POSTO";
		echo "</td>";
		echo "<TD align='center'>";
		echo "REFERÊNCIA";
		echo "</td>";
		echo"<TD align='center'>";
		echo "DESCRICAO";
		echo "</td>";
	}
	
	echo "</tr>";

		for($i=0; $i < pg_numrows($res); $i++){
			$os           = pg_result($res,$i,os);
			$posto_nome   = pg_result($res,$i,posto_nome);
			$referencia   = pg_result($res,$i,referencia);
			$descricao    = pg_result($res,$i,descricao);						
			
			echo "<tr>\n";
			echo "<td bgcolor='#FFFFFF' align='center'><a href='os_press.php?os=$os' target='blank'>$os</a></td>\n";
			if(strlen($produto) > 0) {
				echo "<td bgcolor='#FFFFFF' align='center'>$posto_nome</td>\n";
			}
			if(strlen($posto) > 0) {
				echo "<td bgcolor='#FFFFFF' align='center'>$referencia</td>\n";
				echo "<td bgcolor='#FFFFFF' align='center'>$descricao</td>\n";
			}
			if(strlen($aux_data_inicial) > 0) {
				echo "<td bgcolor='#FFFFFF' align='center'>$posto_nome</td>\n";
				echo "<td bgcolor='#FFFFFF' align='center'>$referencia</td>\n";
				echo "<td bgcolor='#FFFFFF' align='center'>$descricao</td>\n";
			}
			echo "</tr>\n";
		}
			echo "</table>";
	}
}

?>
<br>

<? include "rodape.php" ?>
