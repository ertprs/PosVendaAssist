<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_GET['os']) > 0)   $os = $_GET['os'];
if (strlen($_POST['os']) > 0)  $os = $_POST['os'];

$familia = 226;

$sql = "SELECT  tbl_os.sua_os,
				tbl_os.fabrica
		FROM    tbl_os
		WHERE   tbl_os.os = $os";
$res = pg_exec ($con,$sql);

if (pg_result ($res,0,fabrica) <> $login_fabrica ) {
	header ("Location: os_cadastro.php");
	exit;
}

$sua_os = trim(pg_result($res,0,sua_os));

$btn_acao = strtolower ($_POST['btn_acao']);

//$msg_erro = "";

if ($btn_acao == "gravar") {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$defeito_constatado = $_POST ['defeito_constatado'];
	if (strlen ($defeito_constatado) > 0) {
		$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $login_posto;";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$causa_defeito = $_POST ['causa_defeito'];
		if (strlen ($causa_defeito) > 0) {
			$sql = "UPDATE tbl_os SET causa_defeito = $causa_defeito
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $login_posto;";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen ($msg_erro) == 0) {
		$sql = "DELETE FROM tbl_os_produto
				USING tbl_os, tbl_os_item
				WHERE  tbl_os_produto.os = tbl_os.os
				AND    tbl_os_item.os_produto = tbl_os_produto.os_produto
				AND    tbl_os_item.pedido IS NULL
				AND    tbl_os_produto.os = $os
				AND    tbl_os.fabrica    = $login_fabrica
				AND    tbl_os.posto      = $login_posto;";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	
	if (strlen ($msg_erro) == 0) {
		$qtde_item = $_POST['qtde_item'];
		
		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$produto    = $_POST['produto_'     . $i];
			$serie      = $_POST['serie_'       . $i];
			$peca       = $_POST['peca_'        . $i];
			$qtde       = $_POST['qtde_'        . $i];
			$defeito    = $_POST['defeito_'     . $i];
			$servico    = $_POST['servico_'     . $i];
			
			$produto = str_replace ("." , "" , $produto);
			$produto = str_replace ("-" , "" , $produto);
			$produto = str_replace ("/" , "" , $produto);
			$produto = str_replace (" " , "" , $produto);
			
			$peca    = str_replace ("." , "" , $peca);
			$peca    = str_replace ("-" , "" , $peca);
			$peca    = str_replace ("/" , "" , $peca);
			$peca    = str_replace (" " , "" , $peca);
			
			if (strlen($serie) == 0)
				$xserie = 'null';
			else
				$xserie = "'".$serie."'";
			
			if (strlen($peca) > 0) {
				$peca    = strtoupper ($peca);
				
				if (strlen ($qtde) == 0) $qtde = "1";
				
				if (strlen ($produto) == 0) {
					$sql = "SELECT tbl_os.produto
							FROM   tbl_os
							WHERE  tbl_os.os      = $os
							AND    tbl_os.fabrica = $login_fabrica;";
					$res = pg_exec ($con,$sql);
					
					if (pg_numrows($res) > 0) {
						$produto = pg_result ($res,0,0);
					}
				}else{
					$sql = "SELECT tbl_produto.produto
							FROM   tbl_produto
							JOIN   tbl_linha USING (linha)
							WHERE  tbl_produto.referencia_pesquisa = '$produto'
							AND    tbl_linha.fabrica = $login_fabrica";
					$res = pg_exec ($con,$sql);
					
					if (pg_numrows ($res) == 0) {
						$msg_erro = "Produto $produto não cadastrado";
						$linha_erro = $i;
					}else{
						$produto = pg_result ($res,0,produto);
					}
				}
				
				if (strlen ($msg_erro) == 0) {
					$sql = "INSERT INTO tbl_os_produto (
								os     ,
								produto,
								serie
							)VALUES(
								$os     ,
								$produto,
								$xserie
						);";
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);

					if (strlen ($msg_erro) > 0) {
						break ;
					}else{
						$res = pg_exec ($con,"SELECT CURRVAL ('seq_os_produto')");
						$os_produto  = pg_result ($res,0,0);
						
						$peca = strtoupper ($peca);
						
						if (strlen($peca) > 0) {
							$sql = "SELECT tbl_peca.*
									FROM   tbl_peca
									WHERE  tbl_peca.referencia_pesquisa = '$peca'
									AND    tbl_peca.fabrica = $login_fabrica;";
							$res = pg_exec ($con,$sql);
							
							if (pg_numrows ($res) == 0) {
								$msg_erro = "Peça $peca não cadastrada";
								$linha_erro = $i;
							}else{
								$peca = pg_result ($res,0,peca);
							}
							
							if (strlen($defeito) == 0) $msg_erro = "Favor informar o defeito da peça"; #$defeito = "null";
							if (strlen($servico) == 0) $msg_erro = "Favor informar o serviço realizado"; #$servico = "null";
							
							if (strlen ($msg_erro) == 0) {
								$sql = "INSERT INTO tbl_os_item (
											os_produto,
											peca      ,
											qtde      ,
											defeito   ,
											servico_realizado
										)VALUES(
											$os_produto,
											$peca      ,
											$qtde      ,
											$defeito   ,
											$servico
									);";
								$res = @pg_exec ($con,$sql);
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
	}
	
	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
		$res      = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

	}
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: os_finalizada.php?os=$os");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$title = "Assistência Técnica - Ordem de Serviço";
$body_onload = "javascript: document.frm_os.defeito_constatado.focus()";

$layout_menu = 'os';
include "cabecalho.php";

if (strlen($os) > 0) {
	#----------------- Le dados da OS --------------
	$sql = "SELECT  tbl_os.*,
					tbl_produto.referencia,
					tbl_produto.descricao ,
					tbl_produto.linha
			FROM    tbl_os
			JOIN    tbl_produto USING (produto)
			WHERE   tbl_os.os = $os";
	$res = pg_exec ($con,$sql) ;
	
	$defeito_constatado = pg_result ($res,0,defeito_constatado);
	$causa_defeito      = pg_result ($res,0,causa_defeito);
	$linha              = pg_result ($res,0,linha);
	$consumidor_nome    = pg_result ($res,0,consumidor_nome);
	$sua_os             = pg_result ($res,0,sua_os);
	$produto_os         = pg_result ($res,0,produto);
	$produto_referencia = pg_result ($res,0,referencia);
	$produto_descricao  = pg_result ($res,0,descricao);
	$produto_serie      = pg_result ($res,0,serie);
}

?>

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

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="os" value="<?echo $os?>">
<input type='hidden' name='qtde_item' value='<? echo $qtde_item ?>'>
<tr>
	<td>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $sua_os ?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Consumidor</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $consumidor_nome ?></b>
				</font>
			</td>
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
		<hr>
	</td>
</tr>
<tr>
	<td valign="top" align="center">
		<!-- ------------- Formulário ----------------- -->
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Defeito Constatado</b><br><br></font>
			</td>
		</tr>
<?

if (strlen($os) > 0) {
	$sql = "SELECT  tbl_defeito_constatado.descricao
			FROM    tbl_familia_defeito_constatado
			JOIN    tbl_familia USING (familia)
			JOIN	tbl_defeito_constatado USING(defeito_constatado)
			WHERE   tbl_familia.fabrica = $login_fabrica
			AND		tbl_familia_defeito_constatado.familia = $familia";
	$res = pg_exec ($con,$sql);
}

for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	echo "<tr>";
	echo "<td nowrap>";
	echo "<INPUT TYPE='checkbox' NAME='check'>&nbsp;";
	echo "<font size=2 face='Geneva, Arial, Helvetica, san-serif'>";
	echo pg_result ($res,$i,descricao);
	ECHO "</font>";
	echo "</td>";
	echo "</tr>";
}
?>
		</table>
	</td>
</tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar itens da Ordem de Serviço" border='0' style="cursor:pointer;">

	</td>
</tr>

</form>

</table>

<p>

<? include "rodape.php";?>
