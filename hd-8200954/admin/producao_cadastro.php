<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";
include "funcoes.php";

$admin_privilegios="cadastros";
include "autentica_admin.php";

$msg = "";

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

if (strlen($_POST["producao"]) > 0) $producao = $_POST["producao"];
if (strlen($_GET["producao"]) > 0)  $producao = $_GET["producao"];

if (strlen($acao) > 0 && $acao == "GRAVAR") {

	$produto_referencia = trim($_POST["produto_referencia"]);
	$produto_descricao  = trim($_POST["produto_descricao"]) ;
	$mes                = trim($_POST["mes"])               ;
	$ano                = trim($_POST["ano"])               ;
	$serie_inicial      = trim($_POST["serie_inicial"])     ;
	$serie_final        = trim($_POST["serie_final"])       ;
	$qtde               = trim($_POST["qtde"])              ;
	$nova_serie         = trim($_POST["nova_serie"])        ;

	
	if (strlen($mes) == 0) $msg .= " Favor informar o mês. ";
	if (strlen($ano) == 0) $msg .= " Favor informar o ano. ";
	if($nova_serie == "sim") $serie_inicial = "0";
	else{
		if (strlen($serie_inicial) == 0) $msg .= " Favor informar o nº de série inicial. ";
	}
	if (strlen($serie_final) == 0) $msg .= " Favor informar o nº de série final. ";

	if (strlen($produto_referencia) > 0 || strlen($produto_descricao) > 0) {
		$sql =	"SELECT tbl_produto.produto    ,
						tbl_produto.referencia ,
						tbl_produto.descricao
				FROM tbl_produto
				JOIN tbl_linha USING (linha)
				WHERE tbl_linha.fabrica = $login_fabrica";
		if (strlen($produto_referencia) > 0) {
			$produto_pesquisa = str_replace("-", "", $produto_referencia);
			$sql .= " AND tbl_produto.referencia_pesquisa = '$produto_pesquisa'";
		}
		if (strlen($produto_descricao) > 0) $sql .= " AND tbl_produto.descricao ILIKE '%$produto_descricao%'";
		$res = pg_exec($con,$sql);
		
		if (pg_numrows($res) == 1) {
			$produto            = pg_result($res,0,produto);
			$produto_referencia = pg_result($res,0,referencia);
			$produto_descricao  = pg_result($res,0,descricao);
		}else{
			$msg .= " Produto não encontrado. ";
		}
	}else{
		$msg .= " Favor informar o produto. ";
	}
	
	if (strlen($msg) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($producao) == 0) {
			$sql = "INSERT INTO tbl_producao (
						produto       ,
						mes           ,
						ano           ,
						serie_inicial ,
						serie_final   ,
						qtde 
					) VALUES (
						$produto       ,
						$mes           ,
						$ano           ,
						$serie_inicial ,
						$serie_final   ,
						$qtde   
					);";
		}else{
			$sql = "UPDATE tbl_producao SET
						produto       = $produto       ,
						mes           = $mes           ,
						ano           = $ano           ,
						serie_inicial = $serie_inicial ,
						serie_final   = $serie_final   ,
						qtde          = $qtde
					WHERE producao = $producao;";
		}
		$res = @pg_exec($con,$sql);
		$msg = pg_errormessage($con);
		
		if (strlen($msg) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if (strlen($acao) > 0 && $acao == "APAGAR") {
	$produto_referencia = trim($_POST["produto_referencia"]);
	$produto_descricao  = trim($_POST["produto_descricao"]);
	$mes                = trim($_POST["mes"]);
	$ano                = trim($_POST["ano"]);
	$serie_inicial      = trim($_POST["serie_inicial"]);
	$serie_final        = trim($_POST["serie_final"]);
	
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_producao  WHERE tbl_producao.producao = $producao;";
	$res = @pg_exec($con,$sql);
	$msg = pg_errormessage($con);
	
	if (strlen($msg) == 0) {
		$producao = "";
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "cadastro";
$title = "Cadastro de Produção";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
.Menu {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #000000;
}
</style>

<script language="JavaScript">
function FuncMouseOver (linha, cor) {
	linha.style.cursor = "hand";
	linha.style.backgroundColor = cor;
}
function FuncMouseOut (linha, cor) {
	linha.style.cursor = "default";
	linha.style.backgroundColor = cor;
}
</script>

<? include "javascript_pesquisas.php"; ?>

<br>

<? if (strlen($msg) > 0) { ?>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg?></td>
	</tr>
</table>
<br>
<?
}

if (strlen($producao) > 0) {
	$sql =	"SELECT tbl_producao.producao      ,
					tbl_producao.mes           ,
					tbl_producao.ano           ,
					tbl_producao.serie_inicial ,
					tbl_producao.serie_final   ,
					tbl_produto.referencia     ,
					tbl_produto.descricao      ,
					tbl_producao.qtde          
			FROM tbl_producao
			JOIN tbl_produto USING (produto)
			JOIN tbl_linha USING (linha)
			WHERE tbl_linha.fabrica     = $login_fabrica
			AND   tbl_producao.producao = $producao;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$producao           = pg_result($res,0,producao);
		$mes                = pg_result($res,0,mes);
		$ano                = pg_result($res,0,ano);
		$serie_inicial      = pg_result($res,0,serie_inicial);
		$serie_final        = pg_result($res,0,serie_final);
		$produto_referencia = pg_result($res,0,referencia);
		$produto_descricao  = pg_result($res,0,descricao);
		$qtde               = pg_result($res,0,qtde);
	}
}
?>

<form name="frm_producao" method="post" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="acao">
<input type="hidden" name="producao" value="<? echo $producao; ?>">
<?if($producao){?>
<div name='novo' id='novo'<? if($qtde>0) echo "style = 'visibility:visible'"; else echo "style = 'visibility:hidden'";?>><font color='FF9900' size='2'><b>NOVA NUMERAÇÃO - SÉRIE</b></font></div>
<div name='velho' id='velho'<? if($qtde>0) echo "style = 'visibility:hidden'";  else echo "style = 'visibility:visible'";?>><font color='FF9900' size='2'><b>VELHA NUMERAÇÃO - SÉRIE</b></font></div>
<?}?>

<table width="450" border="0" cellpadding="2" cellspacing="1" class="Menu" align='center'>
	<tr bgcolor="#D9E2EF">
		<td colspan="2">Produto Referência</td>
		<td colspan="2">Produto Descrição</td>
	</tr>
	<tr bgcolor="#FFFFFF">
		<td colspan="2">
			<input type="text" class="frm" size="14" name="produto_referencia" value="<? echo $produto_referencia ?>" maxlength="20">
			<img border="0" src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_producao.produto_referencia, document.frm_producao.produto_descricao, 'referencia')" style="cursor: hand;" alt="Clique aqui para pesquisar o produto">
		</td>
		<td colspan="2">
			<input type="text" class="frm" size="25" name="produto_descricao" value="<? echo $produto_descricao ?>" maxlength="50">
			<img border="0" src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_producao.produto_referencia, document.frm_producao.produto_descricao, 'descricao')" style="cursor: hand;" alt="Clique aqui para pesquisar o produto">
		</td>
	</tr>
	<tr bgcolor="#FFFFFF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td>Mês</td>
		<td>Ano</td>
		<td>Nº de Série Inicial</td>
		<td>Nº de Série Final</td>
	</tr>
	<tr bgcolor="#FFFFFF">
		<td>
			<select name="mes" size="1" class="frm">
				<option value=''></option>
				<?
				for ($i = 1 ; $i <= count($meses) ; $i++) {
					echo "<option value='$i'";
					if ($mes == $i) echo " selected";
					echo ">" . $meses[$i] . "</option>";
				}
				?>
			</select>
		</td>
		<td>
			<select name="ano" size="1" class="frm">
				<option value=''></option>
				<?
				for ($i = 2003 ; $i <= date("Y") ; $i++) {
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
					echo ">$i</option>";
				}
				?>
			</select>
		</td>
		<td><input type="text" class="frm" name="serie_inicial" value="<? echo $serie_inicial ?>" size="15" <? if($qtde>0)echo "style = 'visibility:hidden'"; ?>></td>
		<td><input type="text" class="frm" name="serie_final" value="<? echo $serie_final ?>" size="15"></td>
	</tr>
	<tr bgcolor="#FFFFFF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan='2'>Novo número de série</td>
		<td colspan='2'>Quantidade Produzida</td>
	</tr>
	<tr>
		<td colspan='2'>
			<input type='radio'value='sim' name='nova_serie' onclick='javascript: if(this.value=="sim"){this.form.serie_inicial.style.visibility="hidden";this.form.qtde.style.visibility="visible";}' <?if($qtde>0) echo "CHECKED";?>> SIM 
			<input type='radio'value='nao' name='nova_serie' onclick='javascript: if(this.value=="nao"){this.form.serie_inicial.style.visibility="visible";this.form.qtde.style.visibility="hidden";}'<?if($qtde>0){}else echo "CHECKED";?>> NÃO
			</td>
		<td colspan='2'><input type="text" class="frm" name="qtde" value="<? echo $qtde ?>" size="15" <? if($qtde>0){}else echo "style = 'visibility:hidden'";?>></td>	
	</tr>

</table>

<br>

<center>
<img border="0" src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_producao.acao.value == '') { document.frm_producao.acao.value='GRAVAR'; document.frm_producao.submit(); }else{ alert('Aguarde submissão'); }" alt="Gravar" style="cursor: hand;">
<? if (strlen($producao) > 0) { ?>
<img border="0" src="imagens_admin/btn_apagar.gif" onclick="javascript: if (document.frm_producao.acao.value == '') { document.frm_producao.acao.value='APAGAR' ; document.frm_producao.submit(); }else{ alert('Aguarde submissão'); }" ALT="Apagar" style="cursor: hand;">
<? } ?>
</center>

</form>


<?
$sql =	"SELECT tbl_producao.producao      ,
				tbl_producao.mes           ,
				tbl_producao.ano           ,
				tbl_producao.serie_inicial ,
				tbl_producao.serie_final   ,
				tbl_producao.qtde          ,
				tbl_produto.referencia     ,
				tbl_produto.descricao
		FROM tbl_producao
		JOIN tbl_produto USING (produto)
		JOIN tbl_linha USING (linha)
		WHERE tbl_linha.fabrica = $login_fabrica
		ORDER BY tbl_producao.ano ASC, tbl_producao.mes ASC;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<table align='center'width='700'><tr><td bgcolor='#87CEFF' width='20'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td align='left'>Nova Série</td></tr></table>";
	echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center' width='700'>";
	
	echo "<tr height='15' class='Titulo'>";
	echo "<td> MÊS </td>";
	echo "<td> ANO </td>";
	echo "<td> PRODUTO </td>";
	echo "<td> SÉRIE INICIAL </td>";
	echo "<td> SÉRIE FINAL </td>";
	echo "<td> QTDE </td>";
	echo "</tr>";
	
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$producao      = pg_result($res,$i,producao)     ;
		$mes           = pg_result($res,$i,mes)          ;
		$ano           = pg_result($res,$i,ano)          ;
		$serie_inicial = pg_result($res,$i,serie_inicial);
		$serie_final   = pg_result($res,$i,serie_final)  ;
		$referencia    = pg_result($res,$i,referencia)   ;
		$descricao     = pg_result($res,$i,descricao)    ;
		$qtde          = pg_result($res,$i,qtde)         ;
		
		$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
		
		if($qtde > 0 )$cor = "#87CEFF";
		
		echo "<tr class='Conteudo' height='15' bgcolor='$cor' onclick=\"javascript: window.location = '$PHP_SELF?producao=$producao';\" onmouseover=\"javascript: FuncMouseOver(this, '#FFCC99');\" onmouseout=\"javascript: FuncMouseOut(this, '$cor');\">";
		echo "<td nowrap>" . $meses[$mes] . "</td>";
		echo "<td nowrap>" . $ano . "</td>";
		echo "<td nowrap align='left'>" . $referencia . " - " . $descricao . "</td>";
		if($qtde>0)$serie_inicial = 'Nova Série';
		echo "<td nowrap>" . $serie_inicial . "</td>";

		echo "<td nowrap>" . $serie_final . "</td>";

		if($qtde>0){}else $qtde = '-';

		echo "<td nowrap>" . $qtde . "</td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<h3>Obs.: Clique na linha para efetuar as alterações.</h3>";
}
echo "<h3>Total de " . pg_numrows($res) . " registro(s) cadastrado(s).</h3>";

echo "<br>";

include "rodape.php";
?>
