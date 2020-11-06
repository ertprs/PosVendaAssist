<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_POST["referencia_pesquisa"]) > 0) $peca_referencia = $_POST["referencia_pesquisa"];
else $peca_referencia = $_GET["referencia"];

if (strlen($_POST["descricao_pesquisa"]) > 0) $peca_descricao  = $_POST["descricao_pesquisa"];
$peca_descricao  = $_GET["descricao"];

$btn_acao  = $_POST["btn_acao"];
$qtde_item = $_POST["qtde_item"];

if (strlen($_POST["peca"]) > 0) $peca = $_POST["peca"];
else $peca = $_GET["peca"];

if ($btn_acao == "gravar") {

	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$tabela      = $_POST['tabela_'.$i];
		$tabela_item = $_POST['tabela_item_'.$i];
		$preco       = trim($_POST['preco_'.$i]);
		$preco       = str_replace(" ","",$preco);
		$preco       = str_replace(".","",$preco);
		$preco       = str_replace(",",".",$preco);

		if (strlen($msg_erro) == 0) {
			$res = pg_exec ($con,"BEGIN TRANSACTION");

			// APAGA REGISTRO
			if (strlen($tabela_item) <> 0 AND (strlen($preco) == 0 OR $preco == 0)) {
				$sql =	"DELETE FROM tbl_tabela_item
						WHERE  tbl_tabela_item.tabela      = tbl_tabela.tabela
						AND    tbl_tabela.fabrica          = $login_fabrica
						AND    tbl_tabela_item.tabela_item = $tabela_item;";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			// ALTERA REGISTRO
			if (strlen($tabela_item) <> 0 AND strlen($preco) <> 0 AND $preco <> 0) {
				$sql =	"UPDATE tbl_tabela_item SET
								preco = $preco
						WHERE   tbl_tabela_item.tabela      = tbl_tabela.tabela
						AND     tbl_tabela.fabrica          = $login_fabrica
						AND     tbl_tabela_item.tabela_item = $tabela_item;";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			// INSERE REGISTRO
			if (strlen($tabela_item) == 0 AND strlen($preco) > 0 AND $preco <> 0) {
				$sql =	"INSERT INTO tbl_tabela_item (
							tabela,
							peca  ,
							preco
						) VALUES (
							$tabela ,
							$peca   ,
							$preco
						);";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen ($msg_erro) == 0) {
				### CONCLUI OPERAÇÃO QUE APAGA/ALTERA/INSERE
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				
				header ("Location: $PHP_SELF?peca=$peca");
				exit;
			}else{
				### ABORTA OPERAÇÃO QUE APAGA/ALTERA/INSERE
				
				if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_tabela_item_unico") > 0)
					$msg_erro = "Peça já cadastrada nesta tabela.";

				if (strpos ($msg_erro,"duplicate key value violates unique constraint \"tbl_tabela_item_unico\"") > 0)
					$msg_erro = "Peça já cadastrada nesta tabela.";

				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		}
	} // fim do for
}

$layout_menu = "cadastro";
$title = "Cadastramento de Preços de Mercadorias";
include 'cabecalho.php';
?>

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
</style>

<?
if (strlen($msg_erro) > 0) {
	echo "<div class='error'>".$msg_erro."</div>";
}
?>

<FORM NAME="frm_preco" METHOD="POST" ACTION="<? $PHP_SELF ?>">

<INPUT TYPE="hidden" NAME="peca" VALUE="<? echo $peca ?>">
<INPUT TYPE="hidden" NAME="btn_acao" VALUE="">

<?
if ($btn_acao == 'pesquisar' OR strlen($peca) > 0) {
	if (strlen($_GET["peca"]) > 0 ) {
		$sql = "SELECT  tbl_peca.referencia ,
						tbl_peca.descricao
				FROM    tbl_peca
				WHERE   tbl_peca.fabrica = $login_fabrica
				AND     tbl_peca.peca    = $peca";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			$peca_referencia = pg_result($res,0,referencia);
			$peca_descricao  = pg_result($res,0,descricao);
		}
	}
?>
<TABLE WIDTH="450" ALIGN='center' BORDER='0' CELLSPACING='2' CELLPADDING='3'>
	<TR CLASS="menu_top">
		<TD> &nbsp; Referência </TD>
		<TD> &nbsp; Descrição </TD>
	</TR>
	<TR CLASS="table_line" BGCOLOR="#F1F4FA">
		<TD> &nbsp; <? echo $peca_referencia ?> </TD>
		<TD> &nbsp; <? echo $peca_descricao ?> </TD>
	</TR>
</TABLE>
<P ALIGN='center'><A HREF='<? echo $PHP_SELF ?>'>Clique aqui para realizar nova pesquisa</A></P>
<?
}else{
?>
<FONT FACE='Verdana, Tahoma, Arial' SIZE='2' COLOR='#596D9B'><B>Para pesquisar um produto, informe parte da referência ou descrição e clique na lupa.</B></FONT>
<TABLE WIDTH="450" ALIGN='center' BORDER='0' CELLSPACING='2' CELLPADDING='3'>
	<TR CLASS="menu_top">
		<TD> &nbsp; Referência </TD>
		<TD> &nbsp; Descrição </TD>
	</TR>
	<TR CLASS="table_line">
		<TD> &nbsp; <INPUT TYPE="text" CLASS="frm" NAME="referencia_pesquisa" SIZE="17" VALUE="<? echo $peca_referencia ?>"></TD>
		<TD> &nbsp; <INPUT TYPE="text" CLASS="frm" NAME="descricao_pesquisa" SIZE="17" VALUE="<? echo $peca_descricao ?>"></TD>
	</TR>
</TABLE>
<BR>
<CENTER><IMG BORDER='0' SRC='imagens/btn_pesquisarpecas.gif' ONCLICK="javascript: if (document.frm_preco.btn_acao.value == '' ) {document.frm_preco.btn_acao.value='pesquisar' ;  document.frm_preco.submit() } else { alert ('Aguarde submissão') }" ALT="Pesquisa preço de peça" STYLE="cursor:pointer"></CENTER>
<BR>
<?
}

if ($btn_acao == "pesquisar") {
	$referencia_pesquisa = str_replace(" ","",$referencia_pesquisa);
	$referencia_pesquisa = str_replace(".","",$referencia_pesquisa);
	$referencia_pesquisa = str_replace("/","",$referencia_pesquisa);
	$referencia_pesquisa = str_replace("-","",$referencia_pesquisa);

	if (strlen($referencia_pesquisa) > 0 OR strlen($descricao_pesquisa) > 0) {
		$sql =	"SELECT tbl_peca.peca       ,
						tbl_peca.referencia ,
						tbl_peca.descricao
				FROM    tbl_peca
				WHERE   tbl_peca.fabrica              = $login_fabrica";
		if (strlen($referencia_pesquisa) > 0) $sql .= " AND tbl_peca.referencia_pesquisa ILIKE '%$referencia_pesquisa%'";
		if (strlen($descricao_pesquisa) > 0) $sql .= " AND tbl_peca.descricao ILIKE '%$descricao_pesquisa%'";
		$sql .= " ORDER BY tbl_peca.descricao;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<TABLE ALIGN='center' BORDER='0' CELLSPACING='2' CELLPADDING='3'>\n";
			echo "	<TR CLASS='menu_top'>\n";
			echo "		<TD>Peça</TD>\n";
			echo "		<TD>Descrição</TD>\n";
			echo "	</TR>\n";
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$xpeca        = trim(pg_result($res,$x,peca));
				$referencia = trim(pg_result($res,$x,referencia));
				$descricao  = trim(pg_result($res,$x,descricao));

				$cor = "#F7F5F0";
				if ($x % 2 == 0) $cor = '#F1F4FA';

				echo "	<TR CLASS='table_line' bgcolor='$cor'>\n";
				echo "		<TD NOWRAP>$referencia</TD>\n";
				echo "		<TD NOWRAP><A HREF='$PHP_SELF?peca=$xpeca&referencia=$referencia&descricao=$descricao'>$descricao</A></TD>\n";
				echo "	</TR>";
			}
			echo "</TABLE>";
		}else{
			echo "<P ALIGN='center'><H1>:: Peça não encontrada!</H1></P>\n";
		}
	}else{
		echo "<P ALIGN='center'><H1>:: Preencha o campo corretamente!</H1></P>\n";
	}
}


if (strlen($peca) > 0) {
	$sql =	"SELECT z.peca                  ,
					z.referencia            ,
					z.descricao             ,
					tbl_tabela.tabela       ,
					tbl_tabela.sigla_tabela ,
					z.preco                 ,
					z.tabela_item
			FROM (SELECT y.peca                            ,
						 y.referencia                      ,
						 y.descricao                       ,
						 tbl_tabela_item.tabela            ,
						 tbl_tabela_item.peca AS peca_item ,
						 tbl_tabela_item.preco             ,
						 tbl_tabela_item.tabela_item
				  FROM (SELECT  x.peca       ,
								x.referencia ,
								x.descricao
						FROM (SELECT tbl_peca.peca       ,
									 tbl_peca.referencia ,
									 tbl_peca.descricao
							  FROM tbl_peca
							  WHERE tbl_peca.fabrica = $login_fabrica
							  AND tbl_peca.peca = $peca
							  AND tbl_peca.ativo IS true) AS x
						ORDER BY x.peca) AS y
				  LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = y.peca
				  WHERE y.peca = $peca) AS z
			RIGHT JOIN tbl_tabela ON tbl_tabela.tabela = z.tabela
			WHERE tbl_tabela.ativa IS true
			AND tbl_tabela.fabrica = $login_fabrica
			ORDER BY tbl_tabela.sigla_tabela;";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		echo "<TABLE ALIGN='center' BORDER='0' CELLSPACING='2' CELLPADDING='3'>\n";
		echo "	<TR CLASS='menu_top'>\n";
		echo "		<TD COLSPAN='2'>Tabelas de preço cadastradas</TD>\n";
		echo "	</TR>\n";
		echo "	<TR CLASS='menu_top'>\n";
		echo "		<TD>Tabela</TD>\n";
		echo "		<TD>Preço</TD>\n";
		echo "	</TR>\n";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++){
			$referencia = pg_result($res,$i,referencia);
			$descricao = pg_result($res,$i,descricao);
			if (strlen($referencia) > 0 AND strlen($descricao) AND $travado <> 't') {
				echo "	<TR CLASS='menu_top'>\n";
				echo "		<TD COLSPAN='2' ALIGN='center'>$referencia - $descricao</TD>\n";
				echo "	</TR>\n";
				$travado = 't';
			}
		}

		for ($i = 0 ; $i < pg_numrows($res) ; $i++){
			if (strlen($msg_erro) > 0) {
				$tabela      = $_POST['tabela_'.$i];
				$tabela_item = $_POST['tabela_item_'.$i];
				$sigla       = $_POST['sigla_'.$i];
				$preco       = $_POST['preco_'.$i];
			}else{
				$tabela = trim(pg_result($res,$i,tabela));
				$tabela_item = trim(pg_result($res,$i,tabela_item));
				$sigla = trim(pg_result($res,$i,sigla_tabela));
				$preco = trim(pg_result($res,$i,preco));
				$preco = number_format($preco,2,",",".");
			}

			$cor = "#F7F5F0"; 
			if ($i % 2 == 0) $cor = '#F1F4FA';

			echo "	<TR CLASS='table_line' BGCOLOR='$cor'>\n";
			echo "		<TD ALIGN='center' NOWRAP>\n";
			echo "			<INPUT TYPE='hidden' NAME='tabela_$i' VALUE='$tabela'>\n";
			echo "			<INPUT TYPE='hidden' NAME='tabela_item_$i' VALUE='$tabela_item'>\n";
			echo "			<INPUT TYPE='hidden' NAME='qtde_item' VALUE='".pg_numrows($res)."'>\n";
			echo "			<INPUT TYPE='hidden' NAME='sigla_$i' VALUE='$sigla'>\n";
			echo "			$sigla\n		</TD>\n";
			echo "		<TD ALIGN='center' NOWRAP>R$ <INPUT TYPE='text' CLASS='frm' NAME='preco_$i' SIZE='6' VALUE='$preco'></TD>\n";
			echo "	</TR>\n";
		}
		echo "</TABLE>";
	}else{
			echo "<P ALIGN='center'><H1>:: Tabela de Preço não encontrada!</H1></P>\n";
		}

	echo "<br>\n<CENTER><IMG BORDER='0' SRC='imagens/btn_gravar.gif' ONCLICK=\"javascript: if (document.frm_preco.btn_acao.value == '' ) { document.frm_preco.btn_acao.value='gravar' ; document.frm_preco.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' STYLE='cursor:pointer'></CENTER>";

}
?>
</FORM>

<? include "rodape.php"; ?>

</body>
</html>