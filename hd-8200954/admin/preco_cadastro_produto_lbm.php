<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if(strlen($_POST["btn_acao"]) > 0) $btn_acao = $_POST["btn_acao"];
else $btn_acao = $_GET["btn_acao"];

if (strlen($_POST["produto"]) > 0 ) $produto = $_POST["produto"];
else $produto = $_GET["produto"];

$qtde_itens = $_POST["qtde_itens"];

if ($btn_acao == "gravar") {

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		for ($i = 0 ; $i < $qtde_itens ; $i++) {
			$peca        = $_POST['peca_'.$i];
			$tabela      = $_POST['tabela_'.$i];
			$tabela_item = $_POST['tabela_item_'.$i];
			$preco       = trim($_POST['preco_'.$i]);
			if (strpos($preco, '.') === false) {
				$preco       = str_replace(" ","",$preco);
				$preco       = str_replace(".","",$preco);
				$preco       = str_replace(",",".",$preco);
			}

			### APAGA REGISTRO
			if (strlen($tabela_item) <> 0 AND (strlen($preco) == 0 OR $preco == 0)) {
				$sql =	"DELETE FROM tbl_tabela_item
						WHERE  tbl_tabela_item.tabela      = tbl_tabela.tabela
						AND    tbl_tabela.fabrica          = $login_fabrica
						AND    tbl_tabela_item.tabela_item = $tabela_item;";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			### ALTERA REGISTRO
			if (strlen($tabela_item) <> 0 AND strlen($preco) <> 0 AND $preco <> 0) {
				$sql =	"UPDATE tbl_tabela_item SET
								preco = '$preco'
						WHERE   tbl_tabela_item.tabela      = tbl_tabela.tabela
						AND     tbl_tabela.fabrica          = $login_fabrica
						AND     tbl_tabela_item.tabela_item = $tabela_item;";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			### INSERE REGISTRO
			if (strlen($tabela_item) == 0 AND strlen($preco) > 0 AND $preco <> 0) {
				$sql =	"INSERT INTO tbl_tabela_item (
							tabela,
							peca  ,
							preco
						) VALUES (
							$tabela ,
							$peca   ,
							'$preco'
						);";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		} # fim do for

		### CONCLUI OPERAÇÃO QUE APAGA/ALTERA/INSERE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?btn_acao=listar&produto=$produto");

	}else{
		### ABORTA OPERAÇÃO QUE APAGA/ALTERA/INSERE
		if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_tabela_item_unico") > 0)
			$msg_erro = "Peça já cadastrada nesta tabela.";
		if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_tabela_item_unico\"") > 0)
			$msg_erro = "Peça já cadastrada nesta tabela.";
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
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

<script language='JavaScript'>
function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=1" ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.produto		= document.frm_preco.produto;
		janela.focus();
	}
}
</script>

<?
if (strlen($msg_erro) > 0) {
	echo "<div class='error'>".$msg_erro."</div>";
}

if (strlen($produto) > 0 AND $btn_acao == 'listar') {
	$sql =	"SELECT produto,
					referencia,
					descricao
			FROM  tbl_produto
			WHERE produto = $produto";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) > 0) {
		$produto = pg_result($res,0,produto);
		$produto_referencia = pg_result($res,0,referencia);
		$produto_descricao = pg_result($res,0,descricao);
	}
}
?>

<FORM NAME="frm_preco" METHOD="POST" ACTION="<? $PHP_SELF ?>">

<INPUT TYPE="hidden" NAME="produto" VALUE="<? echo $produto ?>">
<INPUT TYPE="hidden" NAME="btn_acao" VALUE="">

<?
if ($btn_acao == 'listar') {
?>
<TABLE WIDTH="450" ALIGN='center' BORDER='0' CELLSPACING='2' CELLPADDING='3'>
	<TR CLASS="menu_top">
		<TD> &nbsp; Referência </TD>
		<TD> &nbsp; Descrição </TD>
	</TR>
	<TR CLASS="table_line" BGCOLOR="#F1F4FA">
		<TD> &nbsp; <? echo $produto_referencia ?> </TD>
		<TD> &nbsp; <? echo $produto_descricao ?> </TD>
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
		<TD>
			&nbsp; <INPUT TYPE="text" CLASS="frm" NAME="referencia_pesquisa" SIZE="20" VALUE="<? echo $produto_referencia  ?>">
			<img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_preco.referencia_pesquisa,document.frm_preco.descricao_pesquisa,'referencia')">
		</TD>
		<TD>
			&nbsp; <INPUT TYPE="text" CLASS="frm" NAME="descricao_pesquisa" SIZE="30" VALUE="<? echo $produto_descricao ?>">
			<img src='../imagens/btn_buscar5.gif' border='0' style="cursor:pointer" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_preco.referencia_pesquisa,document.frm_preco.descricao_pesquisa,'descricao')">
		</TD>
	</TR>
</TABLE>
<br>
<CENTER><IMG BORDER='0' SRC='imagens/btn_listabasicademateriais.gif' ONCLICK="javascript: if (document.frm_preco.btn_acao.value == '' ) {document.frm_preco.btn_acao.value='listar' ;  document.frm_preco.submit() } else { alert ('Aguarde submissão') }" ALT="Pesquisa preço de peça" STYLE="cursor:pointer"></CENTER>
<br>
<?
}

if ($btn_acao == "listar") {

	if (strlen($produto) > 0) {

		$sql =	"SELECT  tbl_tabela.tabela       ,
						 tbl_tabela.sigla_tabela
				FROM  tbl_tabela
				WHERE tbl_tabela.ativa IS true
				AND   tbl_tabela.fabrica = $login_fabrica
				ORDER BY tbl_tabela.sigla_tabela";
		$res1 = pg_exec ($con,$sql);
		if (pg_numrows($res1) > 0) {
			$colspan = pg_numrows($res1)+2;
			$qtde_tabela = pg_numrows($res1);
			for ($i = 0 ; $i < pg_numrows($res1) ; $i++){
				$sigla_tabela = trim(pg_result($res1,$i,sigla_tabela));
				$tabela = trim(pg_result($res1,$i,tabela));
				if ($i == 0) {
					echo "<TABLE ALIGN='center' BORDER='0' CELLSPACING='2' CELLPADDING='3'>\n";
					echo "	<TR CLASS='menu_top'>\n";
					echo "		<TD>Peça</TD>\n";
					echo "		<TD>Descrição</TD>\n";
				}
				echo "		<TD>$sigla_tabela</TD>\n";
				if ($i + 1 == pg_numrows($res1)) echo "	</TR>";
			}
			$sql =	"SELECT tbl_peca.peca       ,
							tbl_peca.referencia ,
							tbl_peca.descricao
					FROM  tbl_lista_basica
					JOIN  tbl_peca USING (peca)
					WHERE tbl_lista_basica.fabrica = $login_fabrica
					AND   tbl_lista_basica.produto = $produto
					AND   tbl_peca.ativo IS true
					ORDER BY tbl_peca.referencia, tbl_peca.descricao;";
			$res2 = pg_exec ($con,$sql);
			if (pg_numrows($res2) > 0) {
				$l = 0;
				for ($j = 0 ; $j < pg_numrows($res2) ; $j++){
					$xpeca = trim(pg_result($res2,$j,peca));
					$sql =	"SELECT z.peca                  ,
									z.referencia            ,
									z.descricao             ,
									tbl_tabela.tabela       ,
									tbl_tabela.sigla_tabela ,
									z.preco                 ,
									z.tabela_item
							FROM (
									SELECT  y.peca                            ,
											y.referencia                      ,
											y.descricao                       ,
											tbl_tabela_item.tabela            ,
											tbl_tabela_item.peca AS peca_item ,
											tbl_tabela_item.preco             ,
											tbl_tabela_item.tabela_item
									FROM (
											SELECT  x.peca       ,
													x.referencia ,
													x.descricao
											FROM (
													SELECT  tbl_peca.peca       ,
															tbl_peca.referencia ,
															tbl_peca.descricao
													FROM  tbl_peca
													WHERE tbl_peca.fabrica = $login_fabrica
													AND   tbl_peca.peca    = $xpeca
													AND   tbl_peca.ativo IS true
											) AS x
											ORDER BY x.peca
									) AS y
									LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = y.peca
									WHERE     y.peca = $xpeca
							) AS z
							RIGHT JOIN tbl_tabela ON tbl_tabela.tabela = z.tabela
							WHERE      tbl_tabela.ativa IS true
							AND        tbl_tabela.fabrica = $login_fabrica
							ORDER BY tbl_tabela.sigla_tabela;";
					$res3 = pg_exec ($con,$sql);
					if (pg_numrows($res3) > 0) {
						for ($k = 0 ; $k < pg_numrows($res3) ; $k++){
							if (strlen($msg_erro) > 0) {
								$referencia  = $_POST['referencia'];
								$descricao   = $_POST['descricao'];
								$xpeca       = $_POST['peca_'.$i];
								$tabela      = $_POST['tabela_'.$i];
								$tabela_item = $_POST['tabela_item_'.$i];
								$preco       = $_POST['preco_'.$i];
							}else{
								$referencia  = pg_result($res2,$j,referencia);
								$descricao   = pg_result($res2,$j,descricao);
								$tabela      = pg_result($res3,$k,tabela);
								$tabela_item = pg_result($res3,$k,tabela_item);
								$preco       = trim(pg_result($res3,$k,preco));
								$preco       = number_format($preco,2,",",".");
							}

							$cor = "#F7F5F0"; 
							if ($j % 2 == 0) $cor = '#F1F4FA';

							if ($k % pg_numrows($res3) == 0) {
								echo "	<TR CLASS='table_line' bgcolor='$cor'>\n";
								echo "		<td align='center' NOWRAP>$referencia</td>\n";
								echo "		<td NOWRAP>$descricao</td>\n";
							}

							echo "		<TD ALIGN='center' NOWRAP BGCOLOR='$tem_preco'>\n";
							echo "			<INPUT TYPE='hidden' NAME='peca_$l' VALUE='$xpeca'>\n";
							echo "			<INPUT TYPE='hidden' NAME='tabela_$l' VALUE='$tabela'>\n";
							echo "			<INPUT TYPE='hidden' NAME='tabela_item_$l' VALUE='$tabela_item'>\n";
							echo "			R$ <INPUT TYPE='text' CLASS='frm' NAME='preco_$l' SIZE='6' VALUE='$preco' STYLE='text-align: right'>\n";
							if (strlen($preco) == 0 OR $preco <> '0,00' OR $preco <> 0) echo "			<FONT COLOR='#FF0000'>*</FONT>\n";
							else echo "			<FONT COLOR='$cor'>*</FONT>\n";
							echo "		</TD>\n";
							$l++;
						}
						echo "	</TR>\n";
					}else{
						
					}
				}
				$qtde_itens = $l;
			}
			echo "</table>\n";
			echo "<INPUT TYPE='hidden' NAME='qtde_itens' VALUE='$qtde_itens'>\n";
			echo "<br>\n<CENTER><IMG BORDER='0' SRC='imagens/btn_gravar.gif' ONCLICK=\"javascript: if (document.frm_preco.btn_acao.value == '' ) { document.frm_preco.btn_acao.value='gravar' ; document.frm_preco.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' STYLE='cursor:pointer'></CENTER>";
		}else{
			echo "<P ALIGN='center'><H1>:: Peça não encontrada!</H1></P>\n";
		}
	}else{
		echo "<P ALIGN='center'><H1>:: Preencha o campo corretamente!</H1></P>\n";
	}
}
?>
</FORM>

<? include "rodape.php"; ?>

</body>
</html>