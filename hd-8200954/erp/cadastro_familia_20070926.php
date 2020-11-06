<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include "menu.php";

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$xfamilia                     = trim($_POST['familia']);
	$xpercentual_comissao        = trim($_POST['percentual_comissao']);
	$xpercentual_administrativos = trim($_POST['percentual_administrativos']);
	$xpercentual_vendas          = trim($_POST['percentual_vendas']);
	$xpercentual_lucro           = trim($_POST['percentual_lucro']);
	$xpercentual_marketing       = trim($_POST['percentual_marketing']);
	$xpercentual_perdas          = trim($_POST['percentual_perdas']);

	if(strlen($xpercentual_comissao)       > 0) $xpercentual_comissao        = "'".$xpercentual_comissao        ."'"; else $xpercentual_comissao        = "NULL";
	if(strlen($xpercentual_administrativos)> 0) $xpercentual_administrativos = "'".$xpercentual_administrativos ."'"; else $xpercentual_administrativos = "NULL";
	if(strlen($xpercentual_vendas)         > 0) $xpercentual_vendas          = "'".$xpercentual_vendas          ."'"; else $xpercentual_vendas          = "NULL";
	if(strlen($xpercentual_lucro)          > 0) $xpercentual_lucro           = "'".$xpercentual_lucro           ."'"; else $xpercentual_lucro           = "NULL";
	if(strlen($xpercentual_marketing)      > 0) $xpercentual_marketing       = "'".$xpercentual_marketing       ."'"; else $xpercentual_marketing       = "NULL";
	if(strlen($xpercentual_perdas)         > 0) $xpercentual_perdas          = "'".$xpercentual_perdas          ."'"; else $xpercentual_perdas          = "NULL";

	$sql = "UPDATE tbl_familia set
					percentual_comissao      = $xpercentual_comissao       ,
					percentual_administrativo= $xpercentual_administrativos,
					percentual_vendas        = $xpercentual_vendas         ,
					percentual_lucro         = $xpercentual_lucro          ,
					percentual_marketing     = $xpercentual_marketing      ,
					percentual_perdas        = $xpercentual_perdas
			WHERE familia = $xfamilia
			and fabrica = $login_empresa ";
	$res = pg_exec($con,$sql);
//echo nl2br($sql);

}
?>
<style>
a{
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	color:#3399FF;
}
.Label{
	font-family: Verdana;
	font-size: 10px;
}
.tabela{
	font-family: Verdana;
	font-size: 12px;
	
}
.Titulo_Tabela{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	color:#FFF;
}
.Titulo_Colunas{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
}
.ok{
	font-family: Verdana;
	font-size: 12px;
	color:blue;
	border:#39AED5 1px solid; background-color: #B0DFEE;
}
img{
	border:0;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}

caption{
	BACKGROUND-COLOR: #FFF;
	font-size:12px;
	font-weight:bold;
	text-align:center;
}

</style>
<script language="JavaScript">

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
</script>
<?

echo "<h2><center>A manutenção deste cadastro é realizado pelo TELECONTROL, favor enviar e-mail suporte@telecontrol.com.br</center></h2>";
if(strlen($yfamilia)==0){
	$sql = "SELECT familia,descricao
			FROM tbl_familia
			WHERE fabrica = $login_empresa
			ORDER BY descricao ASC";

	$res = pg_exec ($con,$sql) ;

	if (pg_numrows($res) > 0) {
		echo "<br>";
		echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";
		echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='650' border='0' class='tabela'>";
		echo "<caption>";
		echo "Relação das Famílias Cadastrados";
		echo "</caption>";
		echo "<tr height='20' bgcolor='#95ACCE'>";
		echo "<td align='center' class='Titulo_Colunas'><b>Código</b></td>";
		echo "<td align='left'   class='Titulo_Colunas'><b>Nome</b></td>";
		echo "<td align='left'   class='Titulo_Colunas'><b>Porcentagem</b></td>";
		echo "</tr>";	

		for ($k = 0; $k <pg_numrows($res) ; $k++) {
			$familia   = trim(pg_result($res,$k,familia));
			$descricao = trim(pg_result($res,$k,descricao));
			
			echo "<tr>";
			echo "<td align='center'><input type='hidden' name='marca' value='$marca'>$familia</td>";
			echo "<td align='left'  >$descricao</td>";
			echo "<td align='left'  ><a href='$PHP_SELF?yfamilia=$familia'>Alterar</a></td>";
			echo "</tr>";

		}
		echo "</table>";
	}else{
		echo "Nenhuma Família cadastrado.";
	}
}
$yfamilia = $_GET['yfamilia'];
if(strlen($yfamilia)>0){
	$sql = "SELECT familia,
				descricao,
				percentual_administrativo,
				percentual_comissao,
				percentual_marketing,
				percentual_lucro,
				percentual_vendas,
				percentual_perdas
			FROM tbl_familia
			WHERE fabrica = $login_empresa
			AND familia = $yfamilia";
	$res = pg_exec ($con,$sql) ;
	$familia   = trim(pg_result($res,0,familia));
	$descricao = trim(pg_result($res,0,descricao));
	$percentual_administrativos = trim(pg_result($res,0,percentual_administrativo));
	$percentual_comissao        = trim(pg_result($res,0,percentual_comissao));
	$percentual_marketing       = trim(pg_result($res,0,percentual_marketing));
	$percentual_lucro           = trim(pg_result($res,0,percentual_lucro));
	$percentual_vendas          = trim(pg_result($res,0,percentual_vendas));
	$percentual_perdas          = trim(pg_result($res,0,percentual_perdas));

?>
<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='700' border='0' class='tabela'>
<form name="frm_cadastro" method="post" action="<? echo $PHP_SELF ?>">
<input  type="hidden" name="familia" value="<? echo $familia ?>">
		<tr height='20' bgcolor='#7392BF'>
			<td class='Titulo_Tabela' colspan='2'>Familia</td>
		</tr>       
		<tr height='3'>
			<td  colspan='2'>&nbsp;</td>
		</tr>
		<tr>
			<td class='Label' width='150px'>Código</td>
			<td align='left'  width='550px'><input class="Caixa" type="text" name="referencia" size="10" maxlength="10" value="<? echo $familia ?>" disabled></td>
		</tr>
		<tr>
			<td class='Label'>Nome</td>
			<td align='left' ><input class="Caixa" type="text" name="referencia" size="50" maxlength="50" value="<? echo $descricao ?>" disabled></td>
		</tr>

		<tr>
			<td class='Label'>Percentual Administrativo</td>
			<td class='Label' align='left' >
				<input class="CaixaValor" type="text" name="percentual_administrativos"   size="2" maxlength="5" value="<? echo $percentual_administrativos ?>" onblur="javascript:checarNumero(this)">%</td>
		</tr>
		<tr>
			<td class='Label'>Percentual da Comissão</td>
			<td class='Label' align='left' >
				<input class="CaixaValor" type="text" name="percentual_comissao"   size="2" maxlength="5" value="<? echo $percentual_comissao ?>" onblur="javascript:checarNumero(this)">%</td>
		</tr>
		<tr>
			<td class='Label' nowrap>Percentual do Marketing</td>
			<td class='Label' align='left'>
				<input class="CaixaValor" type="text" name="percentual_marketing"   size="2" maxlength="5" value="<? echo $percentual_marketing ?>"  onblur="javascript:checarNumero(this)">%</td>
		</tr>
		<tr>
			<td class='Label' nowrap>Percentual do Lucro</td>
			<td class='Label' align='left'>
				<input class="CaixaValor" type="text" name="percentual_lucro"   size="2" maxlength="5" value="<? echo $percentual_lucro ?>"  onblur="javascript:checarNumero(this)">%</td>
		</tr>
		<tr>
			<td class='Label' nowrap>Percentual do Venda</td>
			<td class='Label' align='left'>
				<input class="CaixaValor" type="text" name="percentual_vendas"   size="2" maxlength="5" value="<? echo $percentual_vendas ?>"  onblur="javascript:checarNumero(this)">%</td>
		</tr>
		<tr>
			<td class='Label' nowrap>Percentual de Perdas</td>
			<td class='Label' align='left'>
				<input class="CaixaValor" type="text" name="percentual_perdas"   size="2" maxlength="5" value="<? echo $percentual_perdas ?>"  onblur="javascript:checarNumero(this)">%</td>
		</tr>
		
		<tr>
		<td class='Label' colspan='2' align='center'>
				<br>
				<?
					if (strlen($yfamilia)>0) $btn_msg="Gravar Alterações";
					else                    $btn_msg="Gravar";
				?>
				<input class="botao" type="hidden" name="btn_acao"  value=''>
				<input class="botao" type="button" name="bt"        value='<? echo $btn_msg ?>' onclick="javascript:if (this.form.btn_acao.value!='') alert('Aguarde Submissão'); else{this.form.btn_acao.value='Gravar';this.form.submit();}">
				<input class="botao" type="button" name="btn_cancelar" onclick='javascript:window.location="cadastro_familia.php"'  value='Cancelar' >
				<input class="botao" type="button" name="btn_limpar" onclick='limpar_form(this.form)'  value='Limpar' >
				<?
					if ($btn_acao=='Gravar'){
						echo "<input class='botao' type='button' name='btn_voltar' onclick=\"javascript:window.location='cadastro_familia.php'\"  value='Voltar ao Menu Cadastro' >";
					}
				?>
			</td>
		</tr>
</form>
</table>
<?}
include "rodape.php";


?>