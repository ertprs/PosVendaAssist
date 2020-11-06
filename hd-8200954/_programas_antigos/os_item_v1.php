<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_GET['os']) > 0)   $os = $_GET['os'];
if (strlen($_POST['os']) > 0)  $os = $_POST['os'];
if (strlen (trim ($os)) == 0 ) $os = $_POST['os'];

$sql = "SELECT  tbl_os.sua_os,
				tbl_os.fabrica
		FROM    tbl_os
		WHERE   tbl_os.os = $os";
$res = pg_exec ($con,$sql) ;

if (pg_result ($res,0,fabrica) <> $login_fabrica ) {
	header ("Location: os_cadastro.php");
	exit;
}

$sua_os = trim(pg_result($res,0,sua_os));

include 'funcoes.php';

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";

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
							
							if (strlen($defeito) == 0) $defeito = "null";
							if (strlen($servico) == 0) $servico = "null";
							
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


#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
	$os                 = $_POST['os'];
	$defeito_constatado = $_POST['defeito_constatado'];
	$defeito_reclamado  = $_POST['defeito_reclamado'];
	$causa_defeito      = $_POST['causa_defeito'];
}

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


#---------------- Carrega campos de configuração da Fabrica -------------
$sql = "SELECT  tbl_fabrica.os_item_subconjunto  ,
				tbl_fabrica.pergunta_qtde_os_item,
				tbl_fabrica.os_item_serie        ,
				tbl_fabrica.os_item_aparencia    ,
				tbl_fabrica.qtde_item_os
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica;";
$resX = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$os_item_subconjunto = pg_result ($resX,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
	
	$pergunta_qtde_os_item = pg_result ($resX,0,pergunta_qtde_os_item);
	if (strlen ($pergunta_qtde_os_item) == 0) $pergunta_qtde_os_item = 'f';
	
	$os_item_serie = pg_result ($resX,0,os_item_serie);
	if (strlen ($os_item_serie) == 0) $os_item_serie = 'f';
	
	$os_item_aparencia = pg_result ($resX,0,os_item_aparencia);
	if (strlen ($os_item_aparencia) == 0) $os_item_aparencia = 'f';
	
	$qtde_item = pg_result ($resX,0,qtde_item_os);
	if (strlen ($qtde_item) == 0) $qtde_item = 5;
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

include "javascript_pesquisas.php";
?>


<p>


<?
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0)
		$msg_erro = "Esta ordem de serviço já foi cadastrada";
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
	<td><img height="1" width="20" src="/os/spacer.gif"></td>
	<td valign="top" align="center">
		<!-- ------------- Formulário ----------------- -->
		
		<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input type="hidden" name="os" value="<?echo $os?>">
		<input type='hidden' name='qtde_item' value='<? echo $qtde_item ?>'>
		
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
				$sql = "SELECT defeito_constatado_por_familia FROM tbl_fabrica WHERE fabrica = $login_fabrica";
				$res = pg_exec ($con,$sql);
				$defeito_constatado_por_familia = pg_result ($res,0,0) ;
				
				if ($defeito_constatado_por_familia == 't') {
					$sql = "SELECT familia FROM tbl_produto WHERE produto = $produto_os";
					$res = pg_exec ($con,$sql);
					$familia = pg_result ($res,0,0) ;
					
					$sql = "SELECT tbl_defeito_constatado.*
							FROM   tbl_defeito_constatado
							JOIN   tbl_familia_defeito_constatado USING (defeito_constatado)
							WHERE  tbl_defeito_constatado.fabrica = $login_fabrica
							AND    tbl_familia_defeito_constatado.familia = $familia";
				}else{
					$sql = "SELECT tbl_defeito_constatado.*
							FROM   tbl_defeito_constatado
							WHERE  tbl_defeito_constatado.fabrica = $login_fabrica;";
				}
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
			
			<?
			$sql = "SELECT *
					FROM   tbl_causa_defeito
					WHERE  tbl_causa_defeito.fabrica = $login_fabrica;";
			$res = pg_exec ($con,$sql) ;
			
			if (pg_numrows($res) > 0) {
			?>
			
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Causa do Defeito</font>
				<br>
				<select name="causa_defeito" size="1">
					<option selected></option>
				<?
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
					echo "<option ";
					if ($causa_defeito == pg_result ($res,$i,causa_defeito) ) echo " selected ";
					echo " value='" . pg_result ($res,$i,causa_defeito) . "'>" ;
					echo pg_result ($res,$i,codigo) . " - " . pg_result ($res,$i,descricao) ;
					echo "</option>";
				}
				?>
				</select>
			</td>
			
			<? } ?>
		</tr>
		</table>
		
		<?
		if(strlen($os) > 0){
			$sql = "SELECT  tbl_os_item.pedido                                  ,
							tbl_os_item.qtde                                    ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os_produto.produto                              ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao
					FROM    tbl_os_item
					JOIN    tbl_os_produto             USING (os_produto)
					JOIN    tbl_produto                USING (produto)
					JOIN    tbl_os                     USING (os)
					JOIN    tbl_peca                   USING (peca)
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido NOTNULL
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_exec ($con,$sql) ;
			
			if(pg_numrows($res) > 0) {
				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>";
				echo "<tr height='20' bgcolor='#666666'>";
				
				echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças já faturadas</b></font></td>";
				
				echo "</tr>";
				echo "<tr height='20' bgcolor='#666666'>";
				
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>";
				
				echo "</tr>";
				
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
						$faturado      = pg_numrows($res);
						$fat_pedido    = pg_result($res,$i,pedido);
						$fat_peca      = pg_result($res,$i,referencia);
						$fat_descricao = pg_result($res,$i,descricao);
						$fat_qtde      = pg_result ($res,$i,qtde);
						
						echo "<tr height='20' bgcolor='#FFFFFF'>";
						
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_pedido</font></td>";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_peca</font></td>";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_descricao</font></td>";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_qtde</font></td>";
						
						echo "</tr>";
				}
				echo "</table>";
			}
			
			$sql = "SELECT  tbl_os_item.pedido                                  ,
							tbl_os_item.qtde                                    ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os_produto.produto                              ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao
					FROM    tbl_os_item
					JOIN    tbl_os_produto             USING (os_produto)
					JOIN    tbl_produto                USING (produto)
					JOIN    tbl_os                     USING (os)
					JOIN    tbl_peca                   USING (peca)
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido ISNULL
					ORDER BY tbl_os_item.os_item;";
			$res = pg_exec ($con,$sql) ;
			
			if(pg_numrows($res) > 0) {
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
						$pedido[$i]            = pg_result($res,$i,pedido);
						$peca[$i]              = pg_result($res,$i,referencia);
						$qtde[$i]              = pg_result ($res,$i,qtde);
						$produto[$i]           = pg_result($res,$i,subconjunto);
						$serie[$i]             = pg_result($res,$i,serie);
						$descricao[$i]         = pg_result($res,$i,descricao);
						$defeito[$i]           = pg_result($res,$i,defeito);
						$defeito_descricao[$i] = pg_result($res,$i,defeito_descricao);
						$servico[$i]           = pg_result($res,$i,servico_realizado);
						$servico_descricao[$i] = pg_result($res,$i,servico_descricao);
				}
			}
		}
		
		for ($i = 0 ; $i < $qtde_item ; $i++) {
			if (strlen ($msg_erro) > 0) {
				$produto[$i]    = $_POST["produto_"     . $i];
				$serie[$i]      = $_POST["serie_"       . $i];
				$peca[$i]       = $_POST["peca_"        . $i];
				$qtde[$i]       = $_POST["qtde_"        . $i];
				$defeito[$i]    = $_POST["defeito_"     . $i];
				$servico[$i]    = $_POST["servico_"     . $i];
			}
		}
		
		echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>";
		echo "<tr height='20' bgcolor='#666666'>";
		
		if ($os_item_subconjunto == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Subconjunto</b></font></td>";
		}
		
		if ($os_item_serie == 't' AND $os_item_subconjunto == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>N. Série</b></font></td>";
		}
		
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Código</b></font></td>";
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>";
		
		if ($pergunta_qtde_os_item == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>";
		}
		
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Defeito</b></font></td>";
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Serviço</b></font></td>";
		
		echo "</tr>";
		
		$loop = $qtde_item;
		if (strlen($faturado) > 0) $loop = $qtde_item - $faturado;
		
		$offset = 0;
		for ($i = 0 ; $i < $loop ; $i++) {
			echo "<tr>";
			
			echo "<input type='hidden' name='descricao'>";
			echo "<input type='hidden' name='preco'>";
			
			if ($os_item_subconjunto == 'f') {
				echo "<input type='hidden' name='produto_$i' value='$produto_referencia'>";
			}else{
				echo "<td align='center'>";
				echo "<select class='frm' size='1' name='produto_$i'>";
				#echo "<option></option>";
				
				$sql = "SELECT  tbl_produto.produto   ,
								tbl_produto.referencia,
								tbl_produto.descricao
						FROM    tbl_subproduto
						JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
						WHERE   tbl_subproduto.produto_pai = $produto_os
						ORDER BY tbl_produto.referencia;";
				$resX = pg_exec ($con,$sql) ;
				
				echo "<option value='$produto_referencia' ";
				if ($produto[$i] == $produto_referencia) echo " selected ";
				echo " >$produto_descricao</option>";
				
				for ($x = 0 ; $x < pg_numrows ($resX) ; $x++ ) {
					echo "<option ";
					if (trim ($produto[$i]) == trim (pg_result ($resX,$x,referencia))) echo " selected ";
					echo " value='" . pg_result ($resX,$x,referencia) . "'>" ;
					echo pg_result ($resX,$x,referencia) . " - " . substr(pg_result ($res,$x,descricao),0,15) ;
					echo "</option>";
				}
				
				echo "</select>";
				echo "</td>";
			}
			
			if ($os_item_subconjunto == 'f') {
				$xproduto = $produto[$i];
				echo "<input type='hidden' name='serie_$i'>";
			}else{
				if ($os_item_serie == 't') {
					echo "<td align='center'><input class='frm' type='text' name='serie_$' size='9' value='$serie[$i]'></td>";
				}
			}
			
			if (strlen($faturado) == 0 and $os_item_aparencia == 't' and $os_item_subconjunto == 'f' and strlen(strpos($sua_os, "-") > 0)) {
				$sql = "SELECT  tbl_peca.referencia,
								tbl_peca.descricao
						FROM    tbl_peca
						JOIN    tbl_lista_basica USING (peca)
						JOIN    tbl_produto      USING (produto)
						WHERE   tbl_produto.produto     = $produto_os
						AND     tbl_peca.fabrica        = $login_fabrica
						AND     tbl_peca.item_aparencia = 't'
						ORDER BY tbl_peca.referencia
						LIMIT 1 OFFSET $offset;";
				$resX = @pg_exec ($con,$sql) ;
				
				if (@pg_numrows($resX) > 0) {
					$xpeca      = trim(pg_result($resX,0,referencia));
					$xdescricao = trim(pg_result($resX,0,descricao));
					
					echo "<td align='center'><input class='frm' type='text' name='peca_$i' size='15' value='$xpeca'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , \"referencia\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";
					
					echo "<td align='center'><input class='frm' type='text' name='descricao_$i' size='25' value='$xdescricao'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";
				}else{
					echo "<td align='center'><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , \"tudo\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";
					
					echo "<td align='center'><input class='frm' type='text' name='descricao_$i' size='25' value='$descricao[$i]'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";
				}
			}else{
				echo "<td align='center'><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , \"referencia\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";
				
				echo "<td align='center'><input class='frm' type='text' name='descricao_$i' size='25' value='$descricao[$i]'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";
			}
			
			if ($pergunta_qtde_os_item == 't') {
				echo "<td align='center'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]'></td>";
			}
			
			echo "<td align='center'>";
			echo "<select class='frm' size='1' name='defeito_$i'>";
			echo "<option selected></option>";
			
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
			
			echo "</select>";
			echo "</td>";
			
			echo "<td align='center'>";
			echo "<select class='frm' size='1' name='servico_$i'>";
			echo "<option selected></option>";
			
			$sql = "SELECT *
					FROM   tbl_servico_realizado
					WHERE  tbl_servico_realizado.fabrica = $login_fabrica;";
			$res = pg_exec ($con,$sql) ;
			
			for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
				if ($login_fabrica == 3 AND $linha <> 3 AND pg_result ($res,$x,servico_realizado) == 20) {
				}else{
					echo "<option ";
					if ($servico[$i] == pg_result ($res,$x,servico_realizado)) echo " selected ";
					echo " value='" . pg_result ($res,$x,servico_realizado) . "'>" ;
					echo pg_result ($res,$x,descricao) ;
					echo "</option>";
				}
			}
			
			echo "</select>";
			echo "</td>";
			
			echo "</tr>";
			
			$offset = $offset + 1;
		}
		
		echo "</table>";
		?>
		
	</td>
	
	<td><img height="1" width="16" src="/os/spacer.gif"></td>
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