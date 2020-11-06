<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$produto_referencia = $_POST["referencia_pesquisa"];
$produto_descricao  = $_POST["descricao_pesquisa"];

$btn_acao   = $_POST["btn_acao"];
$qtde_itens = $_POST["qtde_itens"];
$produto    = $_POST["produto"];

if ($btn_acao == "gravar") {

	for ($i = 0 ; $i < $qtde_itens ; $i++) {
		$peca        = $_POST['peca_'.$i];
		$tabela      = $_POST['tabela_'.$i];
		$tabela_item = $_POST['tabela_item_'.$i];
		$preco       = trim($_POST['preco_'.$i]);
		$preco       = str_replace(" ","",$preco);
		$preco       = str_replace(".","",$preco);
		$preco       = str_replace(",",".",$preco);
		echo $peca." - ".$tabela." - ".$tabela_item." - ".$preco."<br>";
		if (strlen($msg_erro) == 0) {
//			$res = pg_exec ($con,"BEGIN TRANSACTION");

			// APAGA REGISTRO
			if (strlen($tabela_item) <> 0 AND (strlen($preco) == 0 OR $preco == 0)) {
				$sql =	"DELETE FROM tbl_tabela_item
						WHERE  tbl_tabela_item.tabela      = tbl_tabela.tabela
						AND    tbl_tabela.fabrica          = $login_fabrica
						AND    tbl_tabela_item.tabela_item = $tabela_item;";
//				$res = pg_exec ($con,$sql);
//				$msg_erro = pg_errormessage($con);
				echo $sql."<br>";
			}
			// ALTERA REGISTRO
			if (strlen($tabela_item) <> 0 AND strlen($preco) <> 0 AND $preco <> 0) {
				$sql =	"UPDATE tbl_tabela_item SET
								preco = $preco
						WHERE   tbl_tabela_item.tabela      = tbl_tabela.tabela
						AND     tbl_tabela.fabrica          = $login_fabrica
						AND     tbl_tabela_item.tabela_item = $tabela_item;";
//				$res = pg_exec ($con,$sql);
//				$msg_erro = pg_errormessage($con);
				echo $sql."<br>";
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
//				$res = pg_exec ($con,$sql);
//				$msg_erro = pg_errormessage($con);
				echo $sql."<br>";
			}

/*			if (strlen ($msg_erro) == 0) {
				### CONCLUI OPERAÇÃO QUE APAGA/ALTERA/INSERE
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				
				header ("Location: $PHP_SELF?peca=$peca");
				exit;
			}else{
				### ABORTA OPERAÇÃO QUE APAGA/ALTERA/INSERE
				
				if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_tabela_item_unico") > 0)
					$msg_erro = "Peça já cadastrada nesta tabela.";

				if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_tabela_item_unico\"") > 0)
					$msg_erro = "Peça já cadastrada nesta tabela.";

				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}*/
		}
	} // fim do for
	exit;
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
?>

<FORM NAME="frm_preco" METHOD="POST" ACTION="<? $PHP_SELF ?>">

<INPUT TYPE="hidden" NAME="produto" VALUE="<? echo $produto ?>">
<INPUT TYPE="hidden" NAME="btn_acao" VALUE="">

<?
if ($btn_acao <> 'listar') {
?>
<TABLE WIDTH="450" ALIGN='center' BORDER='0' CELLSPACING='2' CELLPADDING='3'>
	<TR CLASS="menu_top">
		<TD colspan="2" align="center"><B>Para pesquisar um produto, informe parte da referência<br> ou descrição e clique na lupa.</B></TD>
	</TR>
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
}else{
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
	$sql = "SELECT  tbl_tabela.fabrica     ,
					tbl_tabela.tabela      ,
					tbl_tabela.sigla_tabela
			FROM    tbl_tabela
			WHERE   tbl_tabela.ativa IS TRUE
			AND     tbl_tabela.fabrica = $login_fabrica
			ORDER BY tbl_tabela.sigla_tabela;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<TABLE ALIGN='center' BORDER='0' CELLSPACING='2' CELLPADDING='3'>\n";
		echo "<TR CLASS='menu_top'>\n";
		echo "<TD>Peça</td>\n";
		echo "<TD>Descrição</td>\n";
		
		$array_coluna = "";
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$array_coluna [$i] = pg_result ($res,$i,fabrica) . ";" . pg_result ($res,$i,tabela);
			
			echo "<TD ALIGN='center' NOWRAP>" . pg_result ($res,$i,sigla_tabela) . "</td>\n";
		}
		
		echo "</tr>\n";
		
		$sql = "SELECT  tbl_peca.peca        ,
						tbl_peca.referencia  ,
						tbl_peca.descricao   ,
						tbl_tabela.tabela    ,
						tbl_tabela.fabrica   ,
						tbl_tabela_item.preco
				FROM    tbl_tabela_item
				JOIN    tbl_tabela           ON tbl_tabela.tabela        = tbl_tabela_item.tabela
				RIGHT JOIN tbl_peca          ON tbl_peca.peca            = tbl_tabela_item.peca
				LEFT  JOIN tbl_lista_basica  ON tbl_lista_basica.peca    = tbl_peca.peca
											AND tbl_lista_basica.fabrica = $login_fabrica
				JOIN    tbl_produto          ON tbl_produto.produto      = tbl_lista_basica.produto
											AND tbl_lista_basica.produto = $produto
				ORDER BY tbl_peca.referencia, tbl_tabela.sigla_tabela;";
		$res = pg_exec ($con,$sql);
		
		$peca       = "";
		$referencia = "";
		$descricao  = "";
		$preco      = 0;
		$k          = 0;
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			if ($i % 2 == 0) $cor = '#F1F4FA';
			else $cor = "#F7F5F0";
			
			if ($peca <> pg_result ($res,$i,peca) ) {
				if (strlen ($peca) > 0) {
					echo "<TR CLASS='table_line' bgcolor='$cor'>\n";
					echo "<TD align='center' NOWRAP>$referencia</TD>\n";
					echo "<TD NOWRAP>$descricao</TD>\n";
					echo implode (" " , $array_print) ;
					echo "</TR>\n";
				}
				
				$peca       = pg_result ($res,$i,peca);
				$fabrica    = pg_result ($res,$i,fabrica);
				$tabela     = pg_result ($res,$i,tabela);
				$referencia = pg_result ($res,$i,referencia);
				$descricao  = pg_result ($res,$i,descricao);
				
				$array_print = array_fill (0,count($array_coluna),"<TD NOWRAP><AA> $k R$ <INPUT TYPE='text' CLASS='frm' NAME='preco_$k' SIZE='6' VALUE='0,00' STYLE='text-align: right'></TD>\n");
			}
			
			$pesquisa = pg_result ($res,$i,fabrica) . ";" . pg_result ($res,$i,tabela) ;
			$coluna   = array_search ($pesquisa,$array_coluna);
			$array_print [$coluna] = "<TD NOWRAP><BB> $k R$ <INPUT TYPE='text' CLASS='frm' NAME='preco_$k' SIZE='6' VALUE='".number_format (pg_result ($res,$i,preco),2,",",".")."' STYLE='text-align: right'></td>\n";
			$k++;
		}
		
		if ($i % 2 == 0) $cor = '#F1F4FA';
		else $cor = "#F7F5F0";

		echo "	<TR CLASS='table_line' bgcolor='$cor'>\n" ;
		echo "		<TD ALIGN='center' NOWRAP>$referencia</td>\n";
		echo "		<TD NOWRAP>$descricao</td>\n";
		echo implode (" " , $array_print) ;
		echo "	</TD>\n";
		
		echo "</table>\n";
		echo "<INPUT TYPE='hidden' NAME='qtde_itens' VALUE='$qtde_itens'>\n";
		echo "<br>\n<CENTER><IMG BORDER='0' SRC='imagens/btn_gravar.gif' ONCLICK=\"javascript: if (document.frm_preco.btn_acao.value == '' ) { document.frm_preco.btn_acao.value='gravar' ; document.frm_preco.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' STYLE='cursor:pointer'></CENTER>";
	}
}
?>
</FORM>

<? include "rodape.php"; ?>

</body>
</html>