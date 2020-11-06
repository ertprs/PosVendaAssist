<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_GET['os']) > 0)   $os = $_GET['os'];

if (strlen($_POST['os']) > 0)  $os = $_POST['os'];

if (strlen (trim ($os)) == 0 ) $os = $_POST['os'];

$sql = "SELECT tbl_os.fabrica FROM tbl_os WHERE tbl_os.os = $os";
$res = pg_exec ($con,$sql) ;
if (pg_result ($res,0,fabrica) <> $login_fabrica ) {
	header ("Location: os_cadastro.php");
	exit;
}

include 'funcoes.php';

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";

if ($btn_acao == "gravar") {

	$res = pg_exec ($con,"BEGIN TRANSACTION");

//	$defeito_constatado = $_POST ['defeito_constatado'];
//	$res = pg_exec ($con,"UPDATE tbl_os SET defeito_constatado = $defeito_constatado WHERE os = $os AND posto = $login_posto");

	$res = pg_exec ($con,"DELETE FROM tbl_os_produto WHERE tbl_os_produto.os = $os AND tbl_os_produto.os = tbl_os.os AND tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.pedido IS NULL AND tbl_os.fabrica = $login_fabrica AND tbl_os.posto = $login_posto");

	$qtde_item = $_POST['qtde_item'];

	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$produto    = $_POST['produto_'     . $i];
		$serie      = $_POST['serie_'       . $i];
		$peca       = $_POST['peca_'        . $i];
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

		if (strlen ($produto) > 0 and strlen($peca) > 0) {
			$produto = strtoupper ($produto);
			$peca    = strtoupper ($peca);

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

			if (strlen ($msg_erro) == 0) {
				$sql = "INSERT INTO tbl_os_produto (
							os     ,
							produto,
							serie
						)VALUES(
							$os     ,
							$produto,
							'$serie'
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
										1          ,
										$defeito   ,
										$servico
								);";
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
		$res      = @pg_exec ($con,"SELECT fn_valida_os_item($os, $login_fabrica)");
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


#------------ Le OS da Base de dados ------------#
/*
$os = $_GET ['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT * FROM tbl_os WHERE oid = $os AND posto = $login_posto AND tbl_os.fabrica = $login_fabrica";
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
	$os                 = $_POST['os'];
	$defeito_constatado = $_POST['defeito_constatado'];
}

$title = "Telecontrol - Assistência Técnica - Ordem de Serviço";
$body_onload = "javascript: document.frm_os.defeito_constatado.focus()";

$layout_menu = 'os';
include "cabecalho.php";


$imprimir = $_GET['imprimir'];
$os       = $_GET['os'];

if (strlen ($imprimir) > 0 AND strlen ($os) > 0 ) {
	echo "<script language='javascript'>";
	echo "window.open ('os_print.php?os=$os','os_print','resizable=yes,resize=yes,toolbar=no,location=yes,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0')";
	echo "</script>";
}
?>


<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->
<?
#----------------- Le dados da OS --------------
$os = $_GET['os'];
if (strlen (trim ($os)) == 0 ) $os = $_POST['os'];

$sql = "SELECT  tbl_os.*,
				tbl_produto.referencia,
				tbl_produto.descricao ,
				tbl_produto.linha
		FROM    tbl_os
		JOIN    tbl_produto USING (produto)
		WHERE   tbl_os.os = $os";
$res = pg_exec ($con,$sql) ;

$linha              = pg_result ($res,0,linha);
$consumidor_nome    = pg_result ($res,0,consumidor_nome);
$sua_os             = pg_result ($res,0,sua_os);
$produto_os         = pg_result ($res,0,produto);
$produto_referencia = pg_result ($res,0,referencia);
$produto_descricao  = pg_result ($res,0,descricao);
$produto_serie      = pg_result ($res,0,serie);
?>

<? include "javascript_pesquisas.php" ?>



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

<table width="750" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">

<tr>

	<td><img height="1" width="20" src="imagens/spacer.gif"></td>

	<td valign="top" align="center">


		<!-- ------------- Formulário ----------------- -->

		<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input type="hidden" name="os" value="<?echo $os?>">

		<p>

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

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Constatado</font>
				<br>
				<select name="defeito_constatado" size="1">
					<option selected></option>
				<?
				$sql = "SELECT *
						FROM   tbl_defeito_constatado
						WHERE  tbl_defeito_constatado.fabrica = $login_fabrica;";
				$res = pg_exec ($con,$sql) ;
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
					echo "<option ";
					if ($defeito_constatado == pg_result ($res,$i,defeito_constatado) ) echo " selected ";
					echo " value='" . pg_result ($res,$i,defeito_constatado) . "'>" ;
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
			<td align='center'><font size="1" face="Geneva, Arial, Helvetica, san-serif" color='#ffffff'><b>Subconjunto</b></font></td>
			<td align='center'><font size="1" face="Geneva, Arial, Helvetica, san-serif" color='#ffffff'><b>N. Série</b></font></td>
			<td align='center'><font size="1" face="Geneva, Arial, Helvetica, san-serif" color='#ffffff'><b>Componente</b></font></td>
			<td align='center'><font size="1" face="Geneva, Arial, Helvetica, san-serif" color='#ffffff'><b>Defeito</b></font></td>
			<td align='center'><font size="1" face="Geneva, Arial, Helvetica, san-serif" color='#ffffff'><b>Serviço</b></font></td>
		</tr>

		<?

		$qtde_item = 5;
		echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>";

		if(strlen($os) > 0){
			$sql = "SELECT  tbl_os_item.pedido                                  ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao
					FROM    tbl_os_item
					JOIN    tbl_peca              USING (peca)
					JOIN    tbl_defeito           USING (defeito)
					JOIN    tbl_servico_realizado USING (servico_realizado)
					JOIN    tbl_os_produto        USING (os_produto)
					JOIN    tbl_os                USING (os)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica;";
			$res = pg_exec ($con,$sql) ;
			
			if(pg_numrows($res) > 0) {
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
						$pedido[$i]				= pg_result($res,$i,pedido);
						$peca[$i]				= pg_result($res,$i,referencia);
						$serie[$i]				= pg_result($res,$i,serie);
						$descricao[$i]			= pg_result($res,$i,descricao);
						$defeito[$i]			= pg_result($res,$i,defeito);
						$defeito_descricao[$i]	= pg_result($res,$i,defeito_descricao);
						$servico[$i]			= pg_result($res,$i,servico_realizado);
						$servico_descricao[$i]	= pg_result($res,$i,servico_descricao);
				}
			}
		}

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			if (strlen ($msg_erro) > 0) {
				$produto[$i]    = $_POST["produto_"     . $i];
				$serie[$i]      = $_POST["serie_"       . $i];
				$peca[$i]       = $_POST["peca_"        . $i];
				$defeito[$i]    = $_POST["defeito_"     . $i];
				$servico[$i]    = $_POST["servico_"     . $i];
			}
		}

		for ($i = 0 ; $i < $qtde_item ; $i++) {
		?>
		<tr>
			<input type='hidden' name='descricao'>
			<input type='hidden' name='preco'>

			<td align='center'>
<?			if(strlen($pedido[$i] > 0)){ ?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif" color='#c0c0c0'>
				<b><? echo $pedido[$i] ." - ". $descricao[$i]?></b>
				</font>
<?			}else{ ?>
				<select class='frm' size="1" name="produto_<? echo $i ?>">
					<option selected></option>
				<?
				$sql = "SELECT  tbl_produto.referencia, tbl_produto.descricao
						FROM    tbl_produto
						WHERE   tbl_produto.produto IN (
								SELECT DISTINCT tbl_subproduto.produto_filho
								FROM   tbl_subproduto
								JOIN   tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_pai
								JOIN   tbl_linha   USING (linha)
								WHERE  tbl_subproduto.produto_pai = $produto_os
								AND     tbl_linha.fabrica         = $login_fabrica
						)
						ORDER BY tbl_produto.referencia";
				$res = pg_exec ($con,$sql) ;

				if (pg_numrows($res) > 0) {
					$referencia = pg_result ($res,0,referencia);
					$descricao  = pg_result ($res,0,descricao);
					echo "<option value='$referencia'>".$referencia." - ".substr($descricao,0,15)."</option>";

					for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
						echo "<option ";
						if ($produto[$i] == pg_result ($res,$x,referencia)) echo " selected ";
						echo " value='" . pg_result ($res,$x,referencia) . "'>" ;
						echo pg_result ($res,$x,referencia) . " - " . substr(pg_result ($res,$x,descricao),0,15) ;
						echo "</option>";
					}
				}else{
					$sql = "SELECT  tbl_produto.referencia,
									tbl_produto.produto  ,
									tbl_produto.descricao
							FROM    tbl_produto
							JOIN    tbl_linha   USING (linha)
							WHERE   tbl_produto.linha   = $linha
							AND     tbl_produto.produto = $produto_os
							AND     tbl_linha.fabrica   = $login_fabrica;";
					$res = pg_exec ($con,$sql) ;

					if (pg_numrows($res) > 0) {
						for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
							echo "<option ";
							if ($produto == pg_result ($res,$x,referencia)) echo " selected ";
							echo " value='" . pg_result ($res,$x,referencia) . "'>" ;
							echo substr(pg_result ($res,$x,descricao),0,15) ;
							echo "</option>";
						}
					}
				}
				?>
				</select>
<?	}	?>
			</td>
			<td align='center'><input class='frm' type="text" name="serie_<? echo $i ?>"      size="9" value="<? echo $serie[$i] ?>"></td>
			<td align='center'><input class='frm' type="text" name="peca_<? echo $i ?>"       size="15" value="<? echo $peca[$i] ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_<? echo $i ?>.value , document.frm_os.peca_<? echo $i ?> , document.frm_os.descricao , document.frm_os.preco , "tudo" )' alt="Clique para efetuar a pesquisa" style="cursor:pointer;"></td>
			<td align='center'>
				<select class='frm' size="1" name="defeito_<? echo $i ?>">
					<option selected></option>
				<?
				$sql = "SELECT *
						FROM   tbl_defeito
						WHERE  tbl_defeito.fabrica = $login_fabrica;";
				$res = pg_exec ($con,$sql) ;

				for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
					echo "<option ";
					if ($defeito[$i] == pg_result ($res,$x,defeito)) echo " selected ";
					echo " value='" . pg_result ($res,$x,defeito) . "'>" ;
					echo pg_result ($res,$x,descricao) ;
					echo "</option>";
				}
				?>
				</select>
			</td>
			<td align='center'>
				<select class='frm' size="1" name="servico_<? echo $i ?>">
					<option selected></option>
				<?
				$sql = "SELECT *
						FROM   tbl_servico_realizado
						WHERE  tbl_servico_realizado.fabrica = $login_fabrica;";
				$res = pg_exec ($con,$sql) ;

				for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
					echo "<option ";
					if ($servico[$i] == pg_result ($res,$x,servico_realizado)) echo " selected ";
					echo " value='" . pg_result ($res,$x,servico_realizado) . "'>" ;
					echo pg_result ($res,$x,descricao) ;
					echo "</option>";
				}
				?>
				</select>
			</td>
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
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar itens da Ordem de Serviço" border='0' style="cursor:pointer;">

	</td>
</tr>

</form>

</table>

<p>

<? include "rodape.php";?>