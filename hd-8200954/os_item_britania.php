<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

if ($login_fabrica_nome <> "Britania") {
	header ("Location: login.php");
	exit;
}

$os = $HTTP_GET_VARS['os'];

if (strlen (trim ($os)) == 0 ) $os = $HTTP_POST_VARS['os'];



$sql = "SELECT tbl_os.fabrica FROM tbl_os WHERE tbl_os.os = $os";
$res = @pg_exec ($con,$sql);

if (pg_result ($res,0,fabrica) <> $login_fabrica ) {
	header ("Location: os_cadastro.php");
	exit;
}

$btn_acao = strtolower ($HTTP_POST_VARS['btn_acao']);

$msg_erro = "";

if ($btn_acao == "gravar") {
	$defeito_reclamado = $HTTP_POST_VARS ['defeito_reclamado'];
	
	$res = pg_exec ($con,"UPDATE tbl_os SET defeito_reclamado = $defeito_reclamado WHERE os = $os");
	$res = pg_exec ($con,"DELETE FROM tbl_os_produto WHERE os = $os");
	
	$qtde_item          = $HTTP_POST_VARS['qtde_item'];
	$produto_referencia = $HTTP_POST_VARS['produto_referencia'];
	
	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$peca       = $HTTP_POST_VARS['peca_'        . $i];
		$qtde       = $HTTP_POST_VARS['qtde_'        . $i];
		$defeito    = $HTTP_POST_VARS['defeito_'     . $i];
		
		if (strlen ($produto_referencia) > 0 and strlen($peca) > 0) {
			$produto = strtoupper ($produto);
			
			$sql = "SELECT tbl_produto.produto
					FROM   tbl_produto
					JOIN   tbl_linha USING (linha)
					WHERE  tbl_produto.referencia = '$produto_referencia'
					AND    tbl_linha.fabrica      = $login_fabrica;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows ($res) == 0) {
				$msg_erro = "Produto $produto não cadastrado";
				$linha_erro = $i;
			}else{
				$produto = pg_result ($res,0,produto);
			}
			
			if (strlen ($msg_erro) == 0) {
				$sql = "INSERT INTO tbl_os_produto (
							os     ,
							produto,
							serie
						) VALUES (
							$os     ,
							$produto,
							'$serie'
						)";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				
				if (strlen ($msg_erro) > 0) {
					break ;
				}else{
					$res = pg_exec ($con,"SELECT CURRVAL ('seq_os_produto')");
					$os_produto  = pg_result ($res,0,0);
					
					$peca = strtoupper ($peca);
					
					if (strlen($peca) > 0) {
						$sql = "SELECT tbl_peca.peca
								FROM   tbl_peca
								WHERE  trim(tbl_peca.referencia) = '$peca'
								AND    tbl_peca.fabrica          = $login_fabrica;";
						$res = pg_exec ($con,$sql);
						
						if (pg_numrows ($res) == 0) {
							$msg_erro = "Peça $peca não cadastrada";
							$linha_erro = $i;
						}else{
							$peca = pg_result ($res,0,peca);
						}
						
						if (strlen ($msg_erro) == 0) {
							$sql = "INSERT INTO tbl_os_item (
										os_produto,
										peca      ,
										qtde      ,
										defeito
									) VALUES (
										$os_produto,
										$peca      ,
										1          ,
										'$defeito'
									)";
							$res = pg_exec ($con,$sql);
							$msg_erro = pg_errormessage($con);
							
							if (strlen ($msg_erro) > 0) {
								break ;
							}
						}
					}
				}
			}
		}
	}
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: os_finalizada_britania.php?os=$os");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


#------------ Le OS da Base de dados ------------#
/*
if (strlen ($os) > 0) {
	$sql = "SELECT * FROM tbl_os WHERE oid = $os AND posto = $login_posto";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os				= pg_result ($res,0,sua_os);
		$data_abertura		= pg_result ($res,0,data_abertura);
		$data_fechamento	= pg_result ($res,0,data_fechamento);
		$consumidor_nome	= pg_result ($res,0,consumidor_nome);
		$consumidor_cidade	= pg_result ($res,0,consumidor_cidade);
		$consumidor_fone	= pg_result ($res,0,consumidor_fone);
		$consumidor_estado	= pg_result ($res,0,consumidor_estado);
		$revenda_cnpj		= pg_result ($res,0,revenda_cnpj);
		$revenda_nome		= pg_result ($res,0,revenda_nome);
		$nota_fiscal		= pg_result ($res,0,nota_fiscal);
		$data_nf			= pg_result ($res,0,data_nf);
	}
}
*/

#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
	$os					= $HTTP_POST_VARS['os'];
	$defeito_reclamado	= $HTTP_POST_VARS['defeito_reclamado'];
}

$title = "Cadastramento de Ítens - Ordem de Serviço";
$body_onload = "javascript: document.frm_os.defeito_reclamado.focus()";

$layout_menu = 'os';
include "cabecalho.php";

?>


<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->

<?
#----------------- Le dados da OS --------------
$os = $HTTP_GET_VARS['os'];
if (strlen (trim ($os)) == 0 ) $os = $HTTP_POST_VARS['os'];

$sql = "SELECT tbl_os.*, tbl_produto.referencia, tbl_produto.descricao, tbl_produto.linha FROM tbl_os JOIN tbl_produto USING (produto) WHERE tbl_os.os = $os";

$res = pg_exec ($con,$sql) ;

$linha = pg_result ($res,0,linha);
$produto_referencia = pg_result ($res,0,referencia);
$produto_descricao  = pg_result ($res,0,descricao);
$produto_serie      = pg_result ($res,0,serie);
?>


<script language="JavaScript">
function fnc_pesquisa_peca_lista (codigo, descricao, produto, preco, seq) {
	var url = "";
	if (codigo != "") {
		url = "pesquisa_peca_lista.php?peca=" + codigo.value + "&produto=" + produto.value + "&seq=" + seq + "&retorno=<?echo $PHP_SELF?>&faturado=sim";
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.peca      = codigo;
		janela.descricao = descricao;
		janela.preco     = preco;
		janela.focus();
	}
}
</script>

<p>

<? 
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";

?>
<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" color="#FF3333">
		<? echo $msg_erro ?>
		</font></b>
	</td>
</tr>
</table>
<? } ?>

<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	
	<td valign="top" align="center">
		<!-- ------------- Formulário ----------------- -->
		<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input type="hidden" name="os" value="<? echo $os ?>">
		
		<p>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Produto</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_referencia . " - " . $produto_descricao?></b>
				</font>
			</td>
			
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_serie ?></b>
				</font>
			</td>
		</tr>
		</table>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Reclamado</font>
				<br>
				<select name="defeito_reclamado" size="1">
				<?
				$sql = "SELECT * FROM tbl_defeito_reclamado WHERE linha = $linha";
				$res = pg_exec ($con,$sql) ;
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
					echo "<option ";
					if ($defeito_reclamado == pg_result ($res,$i,defeito_reclamado) ) echo " selected ";
					echo " value='" . pg_result ($res,$i,defeito_reclamado) . "'>" ;
					echo pg_result ($res,$i,descricao) ;
					echo "</option>";
				}
				?>
				</select>
			</td>
		</tr>
		</table>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr height="20" bgcolor="#666666">
			<td align='center'><font size="1" face="Geneva, Arial, Helvetica, san-serif" color='#ffffff'><b>Referência ou descrição da peça</b></font></td>
			<td align='center'><font size="1" face="Geneva, Arial, Helvetica, san-serif" color='#ffffff'><b>Quantidade</b></font></td>
			<td align='center'><font size="1" face="Geneva, Arial, Helvetica, san-serif" color='#ffffff'><b>Serviço</b></font></td>
			<td align='center'><font size="1" face="Geneva, Arial, Helvetica, san-serif" color='#ffffff'><b>Preço</b></font></td>
		</tr>
		
		<input type='hidden' name='descricao'>
		<input type='hidden' name='produto_referencia' value='<? echo $produto_referencia ?>'>
		
		<?
		$qtde_item = 5;
		echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";
		
		for ($i = 0 ; $i < $qtde_item ; $i++) {
			if (strlen ($msg_erro) > 0) {
				$peca       = $HTTP_POST_VARS["peca_"        . $i];
				$qtde       = $HTTP_POST_VARS["qtde_"        . $i];
				$defeito    = $HTTP_POST_VARS["defeito_"     . $i];
				$preco      = $HTTP_POST_VARS["preco_"       . $i];
			}
		?>
		
		<tr>
			<td align='center'><input type="text" name="peca_<? echo $i ?>"    size="20" value="<? echo $peca ?>"><img src='imagens/btn_buscar5.gif' style="margin-left:5px;" border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.peca_<? echo $i ?> , document.frm_os.descricao, document.frm_os.produto_referencia, document.frm_os.preco_<? echo $i ?>, <? echo $i ?>)'></td>
			<td align='center'><input type="text" name="qtde_<? echo $i ?>"    size="3" value="<? echo $qtde ?>"></td>
			<td align='center'>
				<select size="1" name="defeito_<? echo $i ?>">
				<?
				$sql = "SELECT *
						FROM   tbl_defeito
						WHERE  tbl_defeito.fabrica = $login_fabrica;";
				$res = pg_exec ($con,$sql) ;
				
				for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
					echo "<option ";
					if ($defeito == pg_result ($res,$x,defeito)) echo " selected ";
					echo " value='" . pg_result ($res,$x,defeito) . "'>" ;
					echo pg_result ($res,$x,descricao) ;
					echo "</option>";
				}
				?>
				</select>
			</td>
			<td align='center'><input type="text" name="preco_<? echo $i ?>"       size="9" value="<? echo $preco ?>" disabled></td>
		</tr>
		<?
		}
		?>
		</table>
	</td>
	
	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<input type="submit" name="btn_acao" value="Gravar">
	</td>
</tr>
</form>
</table>

<p>

<? include "rodape.php"; ?>