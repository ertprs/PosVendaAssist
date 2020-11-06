<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";
include "funcoes.php";

$admin_privilegios="cadastros";
include "autentica_admin.php";

$msg_erro   = "";
$msg_update = "";

if (strlen($_GET['produto']) > 0) 
	$produto = strtoupper($_GET['produto']);

if (strlen($_GET['serie_controle']) > 0) 
	$serie_controle = strtoupper($_GET['serie_controle']);

if (strlen($_POST['acao']) > 0) 
	$acao = strtoupper($_POST['acao']);

if (strlen($acao) > 0 && $acao == "GRAVAR") {
	$xserie                = STRTOUPPER(trim($_POST['campo_serie']));
	$xquantidade_produzida = STRTOUPPER(trim($_POST['campo_quantidade_produzida']));
	$produto              = trim($_POST['campo_produto_gravar']);

	if (strlen($xserie) == 0)                $msg_erro .= " Favor informar o número de série. ";
	if (strlen($xquantidade_produzida) == 0) $msg_erro .= " Favor informar a quantidade produzida. ";
	
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	$sql = "INSERT INTO tbl_serie_controle (
				fabrica             ,
				serie               ,
				quantidade_produzida,
				produto
			) VALUES (
				$login_fabrica       ,
				'$xserie'             ,
				$xquantidade_produzida,
				$produto
			);";
	$res = @pg_exec($con,$sql);

	$msg_erro = pg_errormessage($con);
		
	if (strlen($msg_erro) == 0) {
		$res        = pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		$msg_update = "";
	}
}

if (strlen($acao) > 0 && $acao == "APAGAR") {
	$xserie                = STRTOUPPER(trim($_POST["campo_serie"]));
	$xquantidade_produzida = STRTOUPPER(trim($_POST["campo_quantidade_produzida"]));
	$xserie_controle       = $_POST["campo_serie_controle"];
	$produto               = trim($_POST['campo_produto_gravar']);

	if (strlen($xserie_controle) > 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		$sql = "DELETE FROM tbl_serie_controle 
				WHERE serie_controle = $xserie_controle";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF?produto=$produto");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	} else {
		$msg_erro = "Nenhum registro foi excluído.";
	}
}











$layout_menu = "cadastro";
$title = "Cadastro de Números de Série";

if (strlen($produto) == 0) include "cabecalho.php";
else echo "<link type='text/css' rel='stylesheet' href='css/css.css'>";

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
function checarNumero(campo){
	var num = campo.value.replace(",","");
	campo.value = parseInt(num);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
function isNumber(numero){
   var CaractereInvalido = false;
   for (i=0; i < numero.value.length; i++){
      var Caractere = numero.value.charAt(i);
      if (isNaN(parseInt(Caractere))) CaractereInvalido = true;
   }
   if (CaractereInvalido){
		alert('Numero de série inválido!');
		numero.value='';
   }
}
</script>







<br>

<? 
if (strlen($msg_erro) > 0) { 
	echo "<table width='700' border='0' cellpadding='2' cellspacing='2' align='center' class='error'>";
	echo "<tr>";
		echo "<td>".$msg_erro."</td>";
		echo "</tr>";
	echo "</table>";
	echo "<br>";
}

if (strlen($msg_update) > 0) { 
	echo "<table width='700' border='0' cellpadding='2' cellspacing='2' align='center' bgcolor='#FFCC00'>";
	echo "<tr>";
		echo "<td>".$msg_update."</td>";
		echo "</tr>";
	echo "</table>";
	echo "<br>";
}
?>


<?
//mostra todos os números de série do produto
if (strlen($produto) > 0) {
	$sql = "SELECT  tbl_produto.referencia||' - '||tbl_produto.descricao as prod_descricao
			FROM tbl_produto
			WHERE tbl_produto.produto = $produto";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		$xprod_descricao = trim(pg_result($res,0,prod_descricao));
		echo "<font face='verdana' size='3'><b>Números de Série do produto $xprod_descricao</b></font>";
	}

	if (strlen($serie_controle) > 0) {
		$sql = "SELECT  serie_controle     ,
						serie              ,
						quantidade_produzida
				FROM tbl_serie_controle 
				WHERE tbl_serie_controle.serie_controle = $serie_controle";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			$serie                = trim(pg_result($res,0,serie));
			$quantidade_produzida = trim(pg_result($res,0,quantidade_produzida));
		}
	}
	?>

	<form name="frm_serie_controle" method="post" action="<? echo $PHP_SELF ?>">
		<input type="hidden" name="acao">
		<BR>
		<table width="250" border="0" cellpadding="2" cellspacing="1" align='center'>
			<tr>
				<td><font face="Verdana" size="1">
					<b>Máscara do número de série SSDAAT:</b><BR>
					SS = Semana (01 até 52)<BR>
					D  = Dia da semana (1 até 7)<BR>
					AA = Ano (05 até ano atual)<BR>
					T  = Turno (1 a 3)
				</td></font>
			</tr>
		</table>
		<br>
		<table width="210" border="0" cellpadding="2" cellspacing="1" class="Menu" align='center'>
			<tr bgcolor="#D9E2EF">
				<td>N. Série</td>
				<td>Qtd. Prod.</td>
			</tr>
			<tr bgcolor="#FFFFFF">
				<td>
					<input type="hidden" name="campo_serie_controle" value="<? echo $serie_controle?>" >
					<input type="hidden" name="campo_produto_gravar" value="<? echo $produto?>" >
					<input onblur="isNumber(this)" type="text" class="frm" size="20" name="campo_serie"  id="campo_serie" value="<? echo $serie ?>" maxlength="16">
				</td>
				<td>
					<input onkeyup="checarNumero(this)" type="text" class="frm" size="7" name="campo_quantidade_produzida" value="<? echo $quantidade_produzida ?>" maxlength="7">
				</td>
			</tr>
		</table>
		<br>
		<center>
		<img border="0" src="imagens_admin/btn_gravar.gif" onclick="javascript: 
			if (document.frm_serie_controle.campo_serie.value == '') {
				alert('Digite o número de série.');
				return false;
			}
			if (document.frm_serie_controle.campo_quantidade_produzida.value == '') {
				alert('Digite a quantidade produzida.');
				return false;
			}
			if (document.frm_serie_controle.acao.value == '') { 
				document.frm_serie_controle.acao.value='GRAVAR'; 
				document.frm_serie_controle.submit(); 
			} else{ 
				alert('Aguarde submissão'); 
			}" 
		alt="Gravar" style="cursor: hand;">

		<img border="0" src="imagens_admin/btn_apagar.gif" onclick="javascript: 
			if (confirm('Deseja realmente excluir o número de série <? echo $serie ?>')) {
				if (document.frm_serie_controle.acao.value == '') { 
					document.frm_serie_controle.acao.value='APAGAR';
					document.frm_serie_controle.submit();
				} else { 
					alert('Aguarde submissão'); 
				} 
			}"
		ALT="Apagar" style="cursor: hand;">
		<BR>
		<a href="javascript: window.close();">Fechar Janela</a>
		</center>
	</form>

	<?
	$sql =	"SELECT serie_controle                                        ,
					serie                                                 ,
					quantidade_produzida                                  ,
					TO_CHAR(data_digitacao,'DD/MM/YYYY') as data_digitacao
			FROM tbl_serie_controle 
			WHERE produto = $produto
			ORDER BY serie_controle";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<BR>";
		echo "<h3><center><font size='3'>Clique na linha para efetuar as alterações.</font></center></h3>";
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center' width='300'>";
		
		echo "<tr height='15' class='Titulo'>";
		echo "<td nowrap>&nbsp;Nº. SÉRIE&nbsp;</td>";
		echo "<td nowrap>&nbsp;QTD. PROD.&nbsp;</td>";
		echo "<td nowrap>&nbsp;INCLUSÃO&nbsp;</td>";
		echo "</tr>";
		
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$serie_controle       = pg_result($res,$i,serie_controle);
			$serie                = pg_result($res,$i,serie);
			$quantidade_produzida = pg_result($res,$i,quantidade_produzida);
			$data_digitacao       = pg_result($res,$i,data_digitacao);
			
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			echo "<tr class='Conteudo' height='15' bgcolor='$cor' onclick=\"javascript: 
			window.location='$PHP_SELF?produto=$produto&serie_controle=$serie_controle'; \" onmouseover=\"javascript: FuncMouseOver(this, '#FFCC99');\" onmouseout=\"javascript: FuncMouseOut(this, '$cor');\">";
			echo "<input type='hidden' name='campo_produto' value=$produto>";
			echo "<td nowrap align='left'>&nbsp;".$serie."&nbsp;</td>";
			echo "<td nowrap align='left'>&nbsp;".$quantidade_produzida."&nbsp;</td>";
			echo "<td nowrap align='left'>&nbsp;".$data_digitacao."&nbsp;</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	echo "<h3>Total de " . pg_numrows($res) . " registro(s) cadastrado(s).</h3>";
}

//lista todos os produtos das familias liquidificador e ventilador
if (strlen($produto) == 0) {
	$sql =	"SELECT tbl_produto.produto                                                   ,
					tbl_produto.referencia||' - '||tbl_produto.descricao as prod_descricao
			FROM tbl_produto 
			WHERE tbl_produto.familia IN (472, 481)
			ORDER BY tbl_produto.descricao";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<h3><center><font size='3'>Clique na linha para efetuar as alterações.</font></center></h3>";
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center' width='300'>";
		
		echo "<tr height='15' class='Titulo'>";
		echo "<td nowrap>&nbsp;PRODUTO&nbsp;</td>";
		echo "</tr>";
		
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$produto              = pg_result($res,$i,produto);
			$prod_descricao       = pg_result($res,$i,prod_descricao);
			
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			echo "<tr class='Conteudo' height='15' bgcolor='$cor' onmouseover=\"javascript: FuncMouseOver(this, '#FFCC99');\" onmouseout=\"javascript: FuncMouseOut(this, '$cor');\">";
			echo "<input type='hidden' name='campo_produto' value=$produto>";
			echo "<td nowrap align='left'><a href='$PHP_SELF?produto=$produto' target=_blank>&nbsp;".$prod_descricao."&nbsp;</a></td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	echo "<h3>Total de " . pg_numrows($res) . " registro(s) cadastrado(s).</h3>";
}

if (strlen($produto) == 0) include "rodape.php";
?>
