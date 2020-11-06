<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';



$sql = "select tbl_peca.referencia, tbl_peca.descricao , preco 
		from tbl_tabela_item 
		join tbl_tabela using(tabela) 
		join tbl_peca using(peca)  
		where tabela = 144
		order by tbl_peca.descricao;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<table border='1'>\n";
	echo "<tr><td>Referencia</td><td>Descrição</td><td>Preço</td>";
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$referencia = trim(pg_result($res,$x,referencia));
		$descricao  = trim(pg_result($res,$x,descricao));
		$preco      = trim(pg_result($res,$x,preco));
		echo "<tr>\n";
		echo "<td>$referencia</td>";
		echo "<td>$descricao</td>";
		echo "<td>$preco</td>";
		echo "</tr>";
	}
	echo "</table>";
}
exit;


if (strlen($HTTP_POST_VARS["btnAcao"]) > 0) {
	$btnAcao = strtolower(trim($HTTP_POST_VARS["btnAcao"]));
}

if (strlen($HTTP_GET_VARS["peca"]) > 0) {
	$peca = trim($HTTP_GET_VARS["peca"]);
}

if (strlen($HTTP_POST_VARS["peca"]) > 0) {
	$peca = trim($HTTP_POST_VARS["peca"]);
}

if (strlen($HTTP_POST_VARS["tabela_item"]) > 0) {
	$tabela_item = trim($HTTP_POST_VARS["tabela_item"]);
}

if (strlen($HTTP_GET_VARS["tabela_item"]) > 0) {
	$tabela_item = trim($HTTP_GET_VARS["tabela_item"]);
}

if (strlen($HTTP_GET_VARS["apagar"]) > 0) {
	$apagar = trim($HTTP_GET_VARS["apagar"]);
}

if ($btnAcao == "pesquisar") {
	$peca = "";
}

if ($btnAcao == "gravar") {
	if (strlen($HTTP_POST_VARS["preco"]) > 0) {
		$aux_preco = "'". trim($HTTP_POST_VARS["preco"]) ."'";
	}else{
		$msg_erro = "Digite o preço da peça.";
	}
	
	if (strlen($HTTP_POST_VARS["tabela"]) > 0) {
		$aux_tabela = "'". trim($HTTP_POST_VARS["tabela"]) ."'";
	}else{
		$msg_erro = "Selecione uma tabela de preço.";
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		
		if (strlen($tabela_item) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_tabela_item (
						tabela,
						peca  ,
						preco
					) VALUES (
						$aux_tabela,
						$peca      ,
						fnc_limpa_moeda($aux_preco)
					);";
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE  tbl_tabela_item SET
							tabela = $aux_tabela,
							peca   = $peca,
							preco  = fnc_limpa_moeda($aux_preco)
					WHERE   tbl_tabela_item.tabela      = tbl_tabela.tabela
					AND     tbl_tabela.fabrica          = $login_fabrica
					AND     tbl_tabela_item.tabela_item = $tabela_item;";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
		
		if (strlen ($msg_erro) == 0) {
			###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			
			header ("Location: $PHP_SELF?peca=$peca");
			exit;
		}else{
			###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
			
			if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_tabela_item_unico") > 0)
				$msg_erro = "Peça já cadastrada nesta tabela.";

			if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_tabela_item_unico\"") > 0)
				$msg_erro = "Peça já cadastrada nesta tabela.";
			
			$peca = $HTTP_POST_VARS["peca"];
			
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}//fim if msg_erro
}

if (strlen($apagar) > 0) {
	#MPL6710007
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_tabela_item
			USING  tbl_tabela
			WHERE  tbl_tabela_item.tabela      = tbl_tabela.tabela
			AND    tbl_tabela.fabrica          = $login_fabrica
			AND    tbl_tabela_item.tabela_item = $apagar;";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF?peca=$peca");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$peca = $HTTP_POST_VARS["peca"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($produto) > 0) {
	$sql = "SELECT tbl_produto.referencia
			FROM   tbl_produto
			JOIN   tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE  tbl_produto.produto = $produto
			AND    tbl_linha.fabrica = $login_fabrica;";
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$produto_referencia = trim(pg_result($res,0,referencia));
	}
}

if (strlen($peca) > 0) {
	$sql = "SELECT  tbl_peca.peca,
					tbl_peca.referencia
			FROM    tbl_peca
			WHERE   tbl_peca.peca    = $peca
			AND     tbl_peca.fabrica = $login_fabrica;";
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$peca            = trim(pg_result($res,0,peca));
		$peca_referencia = trim(pg_result($res,0,referencia));
	}
	
	if (strlen($tabela_item) > 0) {
		$sql = "SELECT   tbl_tabela_item.tabela,
						 tbl_tabela_item.preco
				FROM     tbl_tabela_item
				JOIN     tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela
				WHERE    tbl_tabela.fabrica   = $login_fabrica
				AND      tbl_tabela_item.peca = $peca
				ORDER BY tbl_tabela.sigla_tabela;";
		$res = @pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$tabela = trim(pg_result($res,0,tabela));
			$preco  = trim(pg_result($res,0,preco));
		}
	}
}

?>
<?
	$layout_menu = "cadastro";
	$title = "Cadastramento de Preços de Mercadorias";
	include 'cabecalho.php';
?>

<div id="wrapper">
<form name="frm_lbm" method="post" action="<? $PHP_SELF ?>">
	<input type="hidden" name="peca"         value="<? echo $peca ?>">
	<input type="hidden" name="tabela_item"  value="<? echo $tabela_item ?>">
	<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '400'>
	<tr>
	<td>
	<? if (strlen($msg_erro) > 0) { 
	 echo $msg_erro; 
	 } ?>
	</td>
	</tr>
	
	<tr>
	<td>
		Para pesquisar um produto, informe parte da referência ou descrição da peça.
	</td>
	</tr>
	
	<tr>
	<td>
		<B>Referência / Descrição Peça</B>
	</td>
	</tr>
	
	<tr>
	<td>
	<input type="text" name="peca_pesquisa" value="<? echo $peca_referencia ?>" size="20" maxlength="20"><BR>
	<input type="hidden" name="btnAcao" value=""><img src='imagens/btn_pesquisarpecas.gif' style="cursor:pointer" onclick="javascript: if (document.frm_lbm.btnAcao.value == '' ) { document.frm_lbm.btnAcao.value='pesquisar' ;  document.frm_lbm.submit() } else { alert ('Aguarde submissão') }" ALT="Pesquisa preço de peça" border='0'>
	</td>
	</tr>
	</table>

		<?
			if ($btnAcao == "pesquisar" or $btn_acao == "pesquisar") {
				$xpeca_pesquisa = str_replace(" ","",$peca_pesquisa);
				$xpeca_pesquisa = str_replace(".","",$xpeca_pesquisa);
				$xpeca_pesquisa = str_replace("/","",$xpeca_pesquisa);
				$xpeca_pesquisa = str_replace("-","",$xpeca_pesquisa);
				
				if (strlen($xpeca_pesquisa) > 0) {
					$sql = "SELECT  tbl_peca.peca      ,
									tbl_peca.referencia,
									tbl_peca.descricao
							FROM    tbl_peca
							WHERE   tbl_peca.fabrica              = $login_fabrica
							AND     (tbl_peca.referencia_pesquisa ilike '%$xpeca_pesquisa%'
							OR      tbl_peca.descricao            ilike '%$xpeca_pesquisa%')
							ORDER BY tbl_peca.descricao;";
					$res = pg_exec ($con,$sql);

					if (pg_numrows($res) > 0) {
						echo "<div id=\"wrapper\">\n";
						echo "<div id='middleCol'\n>";
						echo "<h1>:: Relação de Peças</h1>\n";
						echo "</div>\n";
						echo "<div>\n";

						for ($x = 0 ; $x < pg_numrows($res) ; $x++){
							$pec        = trim(pg_result($res,$x,peca));
							$referencia = trim(pg_result($res,$x,referencia));
							$descricao  = trim(pg_result($res,$x,descricao));
							echo "<div id=\"wrapper\">\n";
							echo "<div id='middleCol'\n>";

							echo "    $referencia - <a href='$PHP_SELF?peca=$pec'>$descricao</a>\n";
							echo "</div>\n";
							echo "</div>\n";
						}

					}else{
						echo "<div id=\"wrapper\">\n";
						echo "<h1>:: Peça não encontrada </h1>\n";
						echo "</div>\n";
					}
				}
			}
			
			if (strlen($peca) > 0) {
				echo "<div id=\"wrapper\">\n";
					echo "<div id='middleCol'\n>";
						echo "<hr><b>Tabela de Preços</b>\n";
						echo "<b>- Preço</b>\n";
					echo "</div>\n";
				echo "</div>\n";
				echo "<div id='middleCol'\n>";
					$sql = "SELECT  tbl_tabela.tabela      ,
									tbl_tabela.sigla_tabela
						FROM        tbl_tabela
						WHERE       tbl_tabela.fabrica = $login_fabrica
						ORDER BY    tbl_tabela.sigla_tabela;";
					$res = pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) {
				echo "<div id=\"wrapper\">\n";
					echo "<div id='middleCol'\n>";
						echo "<select class='frm' name='tabela'>\n";
						echo "<option value=''>FAÇA SUA ESCOLHA</option>\n";
							for ($x = 0 ; $x < pg_numrows($res) ; $x++){
							$aux_tabela = trim(pg_result($res,$x,tabela));
							$aux_sigla  = trim(pg_result($res,$x,sigla_tabela));
							echo "<option value='$aux_tabela'"; if ($tabela == $aux_tabela) echo " SELECTED "; echo ">$aux_sigla</option>\n";
							}
						echo "</select>\n";
					echo "</div>\n";
					echo "<div id='middleCol'\n>";
						echo "<input type=\"text\" name=\"preco\" value=\"$preco\" size=\"10\" maxlength=\"\">\n";
					echo "</div>\n";
				echo "</div>\n";
			}

			$sql = "SELECT  tbl_tabela_item.tabela_item,
							tbl_tabela.tabela          ,
							tbl_tabela.sigla_tabela    ,
							tbl_tabela.ativa           ,
							tbl_tabela_item.preco      ,
							tbl_peca.referencia        ,
							tbl_peca.descricao
					FROM    tbl_tabela
					JOIN    tbl_tabela_item USING (tabela)
					JOIN    tbl_peca        ON tbl_peca.peca = tbl_tabela_item.peca
					WHERE   tbl_tabela_item.peca = $peca
					AND     tbl_tabela.fabrica   = $login_fabrica
					ORDER BY tbl_tabela.ativa, tbl_tabela.sigla_tabela DESC;";
			$res = pg_exec ($con,$sql);
	
			if (pg_numrows($res) > 0) {
				echo "<div id=\"wrapper\">\n";
					echo "<hr><h1>.:: Tabelas de preço cadastradas ::.</h1>\n";
				echo "</div>\n";
		
//echo $sql;
				for ($y = 0 ; $y < pg_numrows($res) ; $y++){
					$tabela_item     = trim(pg_result($res,$y,tabela_item));
					$tabela          = trim(pg_result($res,$y,tabela));
					$sigla           = trim(pg_result($res,$y,sigla_tabela));
					$ativa           = trim(pg_result($res,$y,ativa));
					$preco           = trim(pg_result($res,$y,preco));
					$peca_referencia = trim(pg_result($res,$y,referencia));
					$peca_descricao  = trim(pg_result($res,$y,descricao));
					if ($peca_referencia <> $peca_referencia_anterior) {
					echo "<div id=\"wrapper\">\n";
					echo "<div id='middleCol'\n>";
						echo "    <h1> $peca_referencia - $peca_descricao </h1>\n";
					echo "</div>\n";
					echo "</div>\n";
						$quebra = true;
					}else{
						$quebra = false;
					echo "<div id=\"wrapper\">\n";
					echo "<div id='middleCol'\n>";
					if($ativa == 't') {
					echo "<A HREF=\"javascript: if (confirm ('Deseja realmente excluir?') == true) { window.location='$PHP_SELF?peca=$peca&apagar=$tabela_item' }\"> Apagar </A>";
					} 
					echo "$sigla\n";
					if($ativa == 't'){ echo "Tabela ativa ";} else { echo "Tabela Inativa ";}
					echo "R$ ". number_format($preco,2,",",".");
					echo "</div>\n";
					echo "</div>\n";
					}

					if ($quebra == true) {
							echo "<div id=\"wrapper\">\n";
							echo "<div id='middleCol'\n>";
							if($ativa == 't') {	echo "<A HREF=\"javascript: if (confirm ('Deseja realmente excluir?') == true) { window.location='$PHP_SELF?peca=$peca&apagar=$tabela_item' }\"> Apagar </A>";
							}
							echo      "$sigla\n";
							if($ativa == 't'){ echo "Tabela ativa ";} else { echo "Tabela Inativa ";}
							echo      "R$ ". number_format($preco,2,",",".");
							echo "</div>\n";
							echo "</div>\n";
							}

							$peca_referencia_anterior = trim(@pg_result($res,$y+1,referencia));
						}

						echo "</div>\n";
					}
				echo "<div id=\"wrapper\">\n";

				echo "            <center>\n";
				
				echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_lbm.btnAcao.value == '' ) { document.frm_lbm.btnAcao.value='gravar' ; document.frm_lbm.submit() } else { alert ('Aguarde submissão') }\" alt='gravar' border='0' style='cursor:pointer'>";
				
				echo "</center>\n";
				echo "</div>\n";
				}
		?>
			</form>
	</div>
	<?
	include "rodape.php";
	?>

</body>
</html>
