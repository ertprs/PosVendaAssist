<?
//liberado tela nova 17/10 takashi
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
//ESTÁ EM TESTE PARA A DYNACOM 10/07/2007 - IGOR

include 'funcoes.php';

$msg_debug = "";

if (strlen($_GET["defeito_constatado"]) > 0) {
	$defeito_constatado = trim($_GET["defeito_constatado"]);
}

if (strlen($_POST["defeito_constatado"]) > 0) {
	$defeito_constatado = trim($_POST["defeito_constatado"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if ($btnacao == "gravar") {
	
	if (strlen($_POST["codigo"]) > 0) {
		$aux_codigo = "'". trim($_POST["codigo"]) ."'";
	}else{
		$aux_codigo = 'null';
	}

	if (strlen($_POST["descricao"]) > 0) {
		$aux_descricao = "'". trim($_POST["descricao"]) ."'";
	}else{
		$msg_erro = "Informe o defeito constatado.";
	}
	
	if (strlen($_POST["familia"]) > 0) {
		$aux_familia = "'". trim($_POST["familia"]) ."'";
	}else{
		$aux_familia = 'null';
		//$msg_erro = "Selecione a familia.";
	}

	if (strlen($_POST["linha"]) > 0) {
		$aux_linha = "'". trim($_POST["linha"]) ."'";
	}else{
		$aux_linha = 'null';
		//$msg_erro = "Selecione uma linha.";
	}
	$descricao_es = trim($_POST["descricao_es"]);


	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($defeito_constatado) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_defeito_constatado (
						fabrica  ,
						linha    ,
						familia  ,
						descricao,
						codigo
					) VALUES (
						$login_fabrica,
						$aux_linha,
						$aux_familia,
						$aux_descricao,
						$aux_codigo
					);";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		$res = @pg_exec ($con,"SELECT CURRVAL ('seq_defeito_constatado')");
		$x_defeito_constatado  = pg_result ($res,0,0);

		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_defeito_constatado SET
					linha      = $aux_linha,
					familia    = $aux_familia,
					descricao  = $aux_descricao,
					codigo     = $aux_codigo
			WHERE  tbl_defeito_constatado.fabrica            = $login_fabrica
			AND    tbl_defeito_constatado.defeito_constatado = $defeito_constatado";
			//AND    tbl_defeito_constatado.linha              = tbl_linha.linha

			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			$x_defeito_constatado = $defeito_constatado;

		}

		if(strpos($msg_erro, 'duplicate key violates unique constraint "tbl_defeito_constatado_codigo"'))
			$msg_erro= "O código digitado já esta cadastrado em outro defeito";

		#----------------- Grava valores diferenciados de mão-de-obra de acordo com o defeito constatado no produto
		$qtde_produtos = $_POST['qtde_produtos'];
		for ($i = 0 ; $i < $qtde_produtos ; $i++) {
			$mao_de_obra = $_POST['mao_de_obra_' . $i];
			$produto     = $_POST['produto_'     . $i];
		
			if (strlen ($mao_de_obra) > 0) {
				$mao_de_obra = str_replace (".","",$mao_de_obra);
				$mao_de_obra = str_replace (",",".",$mao_de_obra);
				//$mao_de_obra = number_format ($mao_de_obra,2,"",".");
			}

			if (strlen ($mao_de_obra) == 0) {
				$sql = "DELETE FROM tbl_produto_defeito_constatado WHERE produto = $produto AND defeito_constatado = $defeito_constatado";
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			if (strlen ($mao_de_obra) > 0) {
				$sql = "SELECT * FROM tbl_produto_defeito_constatado WHERE produto = $produto AND defeito_constatado = $defeito_constatado";
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
				echo $mao_de_obra; exit;
				if ($mao_de_obra > 100) $msg_erro .= 'Valor muito acima do normal, entrar em contato com TELECONTROL!';
				if (pg_numrows ($res) == 0) {
					$sql = "INSERT INTO tbl_produto_defeito_constatado (produto, defeito_constatado, mao_de_obra) VALUES ($produto, $defeito_constatado, $mao_de_obra)";
				}else{
						$sql = "UPDATE tbl_produto_defeito_constatado SET mao_de_obra = $mao_de_obra WHERE produto = $produto AND defeito_constatado = $defeito_constatado";
				}
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

		}

		if($login_fabrica==20){

			$sql = "SELECT * FROM tbl_defeito_constatado_idioma WHERE defeito_constatado = $x_defeito_constatado AND idioma = 'ES'";
			$res = @pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) {
				$x_defeito_constatado  = trim(pg_result($res,0,defeito_constatado));
				$sql2 = "UPDATE tbl_defeito_constatado_idioma SET descricao = '$descricao_es' 
					WHERE defeito_constatado = $x_defeito_constatado 
					AND   idioma            = 'ES' ; ";
			}else{
	
				$sql2 = "INSERT INTO tbl_defeito_constatado_idioma (
							defeito_constatado  ,
							descricao           ,
							idioma
						) VALUES (
							$x_defeito_constatado   ,
							'$descricao_es',
							'ES'
						);";
			}

			$res = @pg_exec ($con,$sql2);
			$msg_erro = pg_errormessage($con);
			
		}

	}


	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");

		header ("Location: $PHP_SELF");
		exit;
	}else{
		$defeito_constatado    = $_POST["defeito_constatado"];
		$linha                 = $_POST["linha"];
		$familia               = $_POST["familia"];
		$codigo                = $_POST["codigo"];
		$descricao             = $_POST["descricao"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

###CARREGA REGISTRO
if (strlen($defeito_constatado) > 0) {

	$sql = "SELECT  tbl_defeito_constatado.linha    ,
					tbl_defeito_constatado.familia  ,
					tbl_defeito_constatado.codigo   ,
					tbl_defeito_constatado.descricao
			FROM    tbl_defeito_constatado
			LEFT JOIN tbl_linha using(linha)
			WHERE   tbl_defeito_constatado.fabrica            = $login_fabrica
			AND     tbl_defeito_constatado.defeito_constatado = $defeito_constatado";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$linha     = trim(pg_result($res,0,linha));
		$familia   = trim(pg_result($res,0,familia));
		$codigo    = trim(pg_result($res,0,codigo));
		$descricao = trim(pg_result($res,0,descricao));

		$sql2 = "SELECT  descricao
			FROM    tbl_defeito_constatado_idioma
			WHERE   defeito_constatado = $defeito_constatado
			AND     idioma = 'ES'  ";
		$res2 = @pg_exec ($con,$sql2);

		if (pg_numrows($res2) > 0) $descricao_es   = trim(pg_result($res2,0,descricao));
	}
}
?>
<?
	$layout_menu = "cadastro";
	$title = "Cadastramento de Defeitos Constatados";
	include 'cabecalho.php';
?>

<style type='text/css'>
.conteudo {
	font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
}
</style>

<form name="frm_defeito_constatado" method="post" action="<? $PHP_SELF ?>">
<input type="hidden" name="defeito_constatado" value="<? echo $defeito_constatado ?>">

<? if (strlen($msg_erro) > 0) { ?>

<div class='error'>
	<? echo $msg_erro; ?>
</div>

<?
 } 

echo "<table width='600' border='0' bgcolor='#D9E2EF'  align='center' cellpadding='3' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
echo "<td align='left' colspan='2' bgcolor='#596D9B' background='imagens_admin/azul.gif'><font color='#FFFFFF'><B>CAUSAS DE DEFEITOS</B></font></td>";

echo "<tr>";
echo "<td align='left'>Código (*)<br><input class='frm' type='text' name='codigo' value='$codigo' size='20' maxlength='20'></td>";
echo "<td align='left'>Descrição (*)<br><input class='frm' type='text' name='descricao' value='$descricao' size='50' maxlength='100'></td>";
echo "</tr>";
if($login_fabrica==20){
	echo "<tr>";
	echo "<td></td><td align='left'>Descrição Espanhol(*)<BR><input type='text' name='descricao_es' value='$descricao_es' size='40' maxlength='50' class='frm'></td>";
	echo "</tr>";
}
?>

</table>

<h3>Os campos com esta marcação (*) não podem ser nulos. </h3>





<!-- Famílias a que o defeito constatado se aplica -->

<?
if (strlen ($defeito_constatado) > 0) {
	$sql = "SELECT tbl_familia.descricao AS familia_descricao, tbl_familia.familia, tbl_produto.produto, tbl_produto.referencia, tbl_produto.nome_comercial, tbl_produto.voltagem, tbl_produto_defeito_constatado.mao_de_obra AS mao_de_obra_defeito
			FROM tbl_familia
			JOIN tbl_familia_defeito_constatado ON tbl_familia.familia = tbl_familia_defeito_constatado.familia AND tbl_familia_defeito_constatado.defeito_constatado = $defeito_constatado
			JOIN tbl_produto ON tbl_familia.familia = tbl_produto.familia
			LEFT JOIN tbl_produto_defeito_constatado ON tbl_produto.produto = tbl_produto_defeito_constatado.produto AND tbl_produto_defeito_constatado.defeito_constatado = tbl_familia_defeito_constatado.defeito_constatado
			ORDER BY tbl_familia.descricao, tbl_produto.nome_comercial, tbl_produto.referencia";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$familia = "";
		echo "<table align='center' border='1' cellspacing='0'>";
		echo "<tr bgcolor='#6699FF' align='center' style='color:#ffffff; font-size:12px; font-weight:bold' >";
		echo "<td colspan='2'> Informe a mão-de-obra quando o produto apresentar este defeito. <br> Deixe o campo em branco para usar o valor padrão. <br> Digite \"0\" para não pagar mão-de-obra. </td>";
		echo "</tr>";


		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			if ($familia <> pg_result ($res,$i,familia) ) {
				if (strlen ($familia) <> "") {
					echo "</tr>";
				}

				$familia = pg_result ($res,$i,familia);

				echo "<tr bgcolor='#6699FF' align='center' style='color:#ffffff; font-size:12px; font-weight:bold' >";
				echo "<td width='300'>" . pg_result ($res,$i,familia_descricao) . "</td>";
				echo "<td width='120'> MÃO-DE-OBRA </td>";
				echo "</tr>";
			}
			
			echo "<tr>";

			$nome = trim (pg_result ($res,$i,nome_comercial));
			if (strlen ($nome) == 0) $nome = trim (pg_result ($res,$i,referencia));

			$voltagem = trim (pg_result ($res,$i,voltagem));

			$mao_de_obra = pg_result ($res,$i,mao_de_obra_defeito);
			$produto     = pg_result ($res,$i,produto);

			if (strlen ($mao_de_obra) > 0) $mao_de_obra = number_format ($mao_de_obra,2,",",".");


			if (strlen ($_POST['mao_de_obra_' . $i]) > 0) $mao_de_obra = $_POST['mao_de_obra_' . $i];

			echo "<td>";
			echo $nome ." - ". $voltagem;
			echo "</td>";

			echo "<td>";
			echo "<input type='text' size='5' maxlength='10' name='mao_de_obra_$i' value='$mao_de_obra' >";
			echo "<input type='hidden' name='produto_$i' value='$produto'>";
			echo "</td>";

			echo "</tr>";

		}

		echo "<input type='hidden' name='qtde_produtos' value='$i'>";

		echo "</table>";
		echo "</P>";
	}
}

?>

<center>
	<input type='hidden' name='btnacao' value=''>
	<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_defeito_constatado.btnacao.value == '' ) { document.frm_defeito_constatado.btnacao.value='gravar' ; document.frm_defeito_constatado.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style='cursor:pointer;'>
	<IMG SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_defeito_constatado.btnacao.value == '' ) { document.frm_defeito_constatado.btnacao.value='deletar' ; document.frm_defeito_constatado.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Informação" border='0' style='cursor:pointer;'>
	<IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_defeito_constatado.btnacao.value == '' ) { document.frm_defeito_constatado.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style='cursor:pointer;'>
</center>

</form>

<br>



<?
if (strlen ($defeito_constatado) == 0) {
	echo "<br><br><center><font size='2'><b>Relação de Defeitos Constatados</b><BR>
	<I>Para efetuar alterações, clique na descrição do defeito constatado.</i></font>
	</center>";

	$sql = "SELECT  tbl_defeito_constatado.defeito_constatado,
				tbl_defeito_constatado.codigo           ,
				tbl_defeito_constatado.descricao        ,
				tbl_linha.nome        AS nome_linha     ,
				tbl_familia.descricao AS nome_familia
			FROM    tbl_defeito_constatado
			LEFT JOIN tbl_linha USING (linha)
			LEFT JOIN tbl_familia USING (familia)
			WHERE   tbl_defeito_constatado.fabrica = $login_fabrica
			AND     tbl_defeito_constatado.ativo IS TRUE
			ORDER BY tbl_defeito_constatado.linha, tbl_defeito_constatado.familia, tbl_defeito_constatado.descricao;";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table  align='center' width='400' border='0' class='conteudo' cellpadding='2' cellspacing='1'>\n";
		echo "<tr bgcolor='#D9E2EF'>";
		echo "<td nowrap><b>CÓDIGO</b></td>";
		echo "<td nowrap>DESCRIÇÃO</td>";
		if($login_fabrica==20)echo "<td align='left'>Espanhol</td>";
		echo "</tr>";

		for ($x = 0 ; $x < pg_numrows($res) ; $x++){

			$defeito_constatado   = trim(pg_result($res,$x,defeito_constatado));
			$descricao            = trim(pg_result($res,$x,descricao));
			$codigo               = trim(pg_result($res,$x,codigo));

			$cor = ($x % 2 == 0) ? "#FFFFFF" : "#F1F4FA";

			echo "<tr bgcolor='$cor'>";
			echo "<td nowrap><a href='$PHP_SELF?defeito_constatado=$defeito_constatado'>$codigo</a></td>";
			echo "<td nowrap align='left'><a href='$PHP_SELF?defeito_constatado=$defeito_constatado'>$descricao</a></td>";
			if($login_fabrica==20){
				$sql2 = "SELECT  descricao
					FROM    tbl_defeito_constatado_idioma
					WHERE   defeito_constatado = $defeito_constatado
					AND     idioma = 'ES'  ";
				$res2 = @pg_exec ($con,$sql2);
		
				if (pg_numrows($res2) > 0)  echo "<td align='left'>".trim(pg_result($res2,0,descricao))."</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}
}

echo "<br>";

include "rodape.php";
?>
