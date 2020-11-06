<?
//liberado tela nova 17/10 takashi
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";

include 'autentica_admin.php';

include 'funcoes.php';

$msg_debug = "";

if (strlen($_GET["causa_troca"])  > 0) $causa_troca = trim($_GET["causa_troca"]);
if (strlen($_POST["causa_troca"]) > 0) $causa_troca = trim($_POST["causa_troca"]);

if (strlen($_POST["btnacao"])     > 0) $btnacao = trim($_POST["btnacao"]);

if ($btnacao == "deletar" and strlen($causa_troca) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_causa_troca
			WHERE  tbl_causa_troca.fabrica     = $login_fabrica
			AND    tbl_causa_troca.causa_troca = $causa_troca;";
	$res = @pg_exec ($con,$sql);

	$msg_erro = pg_errormessage($con);

	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$causa_troca   = $_POST["causa_troca"];
		$descricao     = $_POST["descricao"];
		$codigo        = $_POST["codigo"];
		$ativo         = $_POST["ativo"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {

	if (strlen($_POST["descricao"]) > 0) $aux_descricao = "'". trim($_POST["descricao"]) ."'"; else $msg_erro  = "Informe a causa da troca.";
	if (strlen($_POST["ativo"])     > 0) $aux_ativo     = "'t'";                               else $aux_ativo = "'f'";
	if (strlen($_POST["codigo"])    > 0) $aux_codigo    = "'". trim($_POST["codigo"]) ."'";    else $aux_codigo = "null";

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($causa_troca) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_causa_troca (
						fabrica  ,
						codigo   ,
						descricao,
						ativo
					) VALUES (
						$login_fabrica,
						$aux_codigo   ,
						$aux_descricao,
						$aux_ativo
					);";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		$res = @pg_exec ($con,"SELECT CURRVAL ('tbl_causa_troca_causa_troca_seq')");
		$x_causa_troca  = pg_result ($res,0,0);

		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_causa_troca SET
					codigo     = $aux_codigo   ,
					descricao  = $aux_descricao,
					ativo      = $aux_ativo
			WHERE  tbl_causa_troca.fabrica     = $login_fabrica
			AND    tbl_causa_troca.causa_troca = $causa_troca";

			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			$x_causa_troca = $causa_troca;

		}

	}


	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");

		header ("Location: $PHP_SELF");
		exit;
	}else{
		$causa_troca    = $_POST["causa_troca"];
		$ativo          = $_POST["ativo"];
		$codigo         = $_POST["codigo"];
		$descricao      = $_POST["descricao"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

###CARREGA REGISTRO
if (strlen($causa_troca) > 0) {

	$sql = "SELECT  tbl_causa_troca.codigo   ,
					tbl_causa_troca.descricao,
					tbl_causa_troca.ativo
			FROM    tbl_causa_troca
			WHERE   tbl_causa_troca.fabrica            = $login_fabrica
			AND     tbl_causa_troca.causa_troca = $causa_troca";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$ativo     = trim(pg_result($res,0,ativo));
		$codigo    = trim(pg_result($res,0,codigo));
		$descricao = trim(pg_result($res,0,descricao));
	}
}
?>
<?
	$layout_menu = "cadastro";
	$title = "Cadastramento da Causa da Troca de Produtos";
	include 'cabecalho.php';
?>

<style type='text/css'>
.conteudo {
	font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
}
</style>

<form name="frm_causa_troca" method="post" action="<? $PHP_SELF ?>">
<input type="hidden" name="causa_troca" value="<? echo $causa_troca ?>">

<? if (strlen($msg_erro) > 0) { ?>

<div class='error'>
	<? echo $msg_erro; ?>
</div>

<?
 } 

echo "<table width='600' border='0' bgcolor='#D9E2EF'  align='center' cellpadding='3' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
echo "<td align='left' colspan='3' bgcolor='#596D9B' background='imagens_admin/azul.gif'><font color='#FFFFFF'><B>Causa da Troca de Produtos</B></font></td>";

echo "<tr>";
echo "<td align='left'>Código (*)<br><input class='frm' type='text' name='codigo' value='$codigo' size='20' maxlength='20'></td>";
echo "<td align='left'>Descrição (*)<br><input class='frm' type='text' name='descricao' value='$descricao' size='50' maxlength='100'></td>";
echo "<td align='left'>Ativo (*)<br><input class='frm' type='checkbox' name='ativo' value='t' ";if($ativo == 't') echo "CHECKED"; echo "></td>";

echo "</tr>";
if($login_fabrica==20){
	echo "<tr>";
	echo "<td></td><td align='left'>Descrição Espanhol(*)<BR><input type='text' name='descricao_es' value='$descricao_es' size='40' maxlength='50' class='frm'></td>";
	echo "</tr>";
}
?>

</table>

<h3>Os campos com esta marcação (*) não podem ser nulos. </h3>


<center>
	<input type='hidden' name='btnacao' value=''>
	<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_causa_troca.btnacao.value == '' ) { document.frm_causa_troca.btnacao.value='gravar' ; document.frm_causa_troca.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style='cursor:pointer;'>
	<IMG SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_causa_troca.btnacao.value == '' ) { document.frm_causa_troca.btnacao.value='deletar' ; document.frm_causa_troca.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Informação" border='0' style='cursor:pointer;'>
	<IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_causa_troca.btnacao.value == '' ) { document.frm_causa_troca.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style='cursor:pointer;'>
</center>

</form>

<br>



<?
if (strlen ($causa_troca) == 0) {
	echo "<center><font size='2'><b>Relação de Defeitos Constatados</b><BR>
	<I>Para efetuar alterações, clique na descrição do defeito constatado.</i></font>
	</center>";

	$sql = "SELECT  tbl_causa_troca.causa_troca,
				tbl_causa_troca.codigo,
				tbl_causa_troca.descricao
			FROM    tbl_causa_troca
			WHERE   tbl_causa_troca.fabrica = $login_fabrica
			ORDER BY  tbl_causa_troca.codigo;";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table  align='center' width='400' border='0' class='conteudo' cellpadding='2' cellspacing='1'>\n";
		echo "<tr bgcolor='#D9E2EF'>";
		echo "<td nowrap><b>CÓDIGO</b></td>";
		echo "<td nowrap>DESCRIÇÃO</td>";
		echo "</tr>";

		for ($x = 0 ; $x < pg_numrows($res) ; $x++){

			$causa_troca   = trim(pg_result($res,$x,causa_troca));
			$descricao            = trim(pg_result($res,$x,descricao));
			$codigo               = trim(pg_result($res,$x,codigo));

			$cor = ($x % 2 == 0) ? "#FFFFFF" : "#F1F4FA";

			echo "<tr bgcolor='$cor'>";
			echo "<td nowrap><a href='$PHP_SELF?causa_troca=$causa_troca'>$codigo</a></td>";
			echo "<td nowrap align='left'><a href='$PHP_SELF?causa_troca=$causa_troca'>$descricao</a></td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}

echo "<br>";

include "rodape.php";
?>
