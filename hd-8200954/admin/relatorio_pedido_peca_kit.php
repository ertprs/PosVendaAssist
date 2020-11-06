<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

if (strlen($_GET['extrato']) > 0)  $extrato = trim($_GET['extrato']);

$sql = "SELECT * FROM tbl_pedido WHERE pedido_kit_extrato = $extrato";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0) {
	header("Location: extrato_consulta.php");
	exit;
}

$msg_erro = "";

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if (strlen($_GET['pedido']) > 0)  $pedido = trim($_GET['pedido']);
if (strlen($_POST['pedido']) > 0) $pedido = trim($_POST['pedido']);

if ($btn_acao == "gravar") {
	$xtipo_pedido = "'Faturado'";
	
	if (strlen($_POST['posto']) > 0)          $xposto   = "'". $_POST['posto'] ."'";
	if (strlen($_POST['extrato']) > 0)        $xextrato = "'". $_POST['extrato'] ."'";
	
	if (strlen($_POST['tipo_pedido']) > 0)    $xtipo_pedido = "'". $_POST['tipo_pedido'] ."'";
	else                                      $msg_erro = "Selecione o Tipo de Pedido";
	
	if (strlen($_POST['tabela']) > 0)         $xtabela = "'". $_POST['tabela'] ."'";
	else                                      $xtabela = "null";
	
	if (strlen($_POST['condicao']) > 0)       $xcondicao = "'". $_POST['condicao'] ."'";
	else                                      $xcondicao = "null";
	
	if (strlen($_POST['tipo_frete']) > 0)     $xtipo_frete = "'". $_POST['tipo_frete'] ."'";
	else                                      $xtipo_frete = "null";

	if (strlen($_POST['pedido_cliente']) > 0) $xpedido_cliente = "'". $_POST['pedido_cliente'] ."'";
	else                                      $xpedido_cliente = "null";
	
	if (strlen($_POST['validade']) > 0)       $xvalidade = "'". $_POST['validade'] ."'";
	else                                      $xvalidade = "null";
	
	if (strlen($_POST['entrega']) > 0)        $xentrega = "'". $_POST['entrega'] ."'";
	else                                      $xentrega = "null";
	
	if (strlen($_POST['transportadora']) > 0) $xtransportadora = $_POST['transportadora'] ;
	else                                      $xtransportadora = "null";
	
	if (strlen($_POST['obs']) > 0)            $xobs = "'". $_POST['obs'] ."'";
	else                                      $xobs = "null";

	if (strlen($_POST['linha']) > 0)          $xlinha = "'". $_POST['linha'] ."'";
	else                                      $xlinha = "null";

	if ($xtipo_pedido <> "null") {
		$sql = "SELECT tipo_pedido
				FROM   tbl_tipo_pedido
				WHERE  tipo_pedido = $xtipo_pedido
				AND    fabrica     = $login_fabrica";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) $msg_erro = "Tipo de Pedido não cadastrado";
	}else{
		$msg_erro = "Tipo de Pedido não informado.";
	}

	if ($xtabela <> "null") {
		$sql = "SELECT tbl_tabela.tabela
				FROM   tbl_tabela
				WHERE  tbl_tabela.tabela  = $xtabela
				AND    tbl_tabela.fabrica = $login_fabrica
				AND    tbl_tabela.ativa   IS TRUE ;";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) $msg_erro = "Tabela de Preços não cadastrada";
	}else{
		$msg_erro = "Tabela de Preços não informada";
	}

	if ($xcondicao <> "null") {
		$sql = "SELECT tbl_condicao.condicao
				FROM   tbl_condicao
				WHERE  tbl_condicao.condicao = $xcondicao
				AND    tbl_condicao.fabrica  = $login_fabrica";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) $msg_erro = "Condição de Pagamento não cadastrada";
	}//else{
	//	$msg_erro = "Condição de Pagamento não informada";
	//}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen ($pedido) == 0) {
			#-------------- insere pedido ------------
			$sql = "INSERT INTO tbl_pedido (
						posto             ,
						fabrica           ,
						condicao          ,
						tabela            ,
						admin             ,
						tipo_pedido       ,
						pedido_cliente    ,
						validade          ,
						entrega           ,
						obs               ,
						linha             ,
						transportadora    ,
						tipo_frete        ,
						pedido_kit_extrato
					) VALUES (
						$xposto          ,
						$login_fabrica   ,
						$xcondicao       ,
						$xtabela         ,
						$login_admin     ,
						$tipo_pedido     ,
						$xpedido_cliente ,
						$xvalidade       ,
						$xentrega        ,
						$xobs            ,
						$xlinha          ,
						$xtransportadora ,
						$xtipo_frete     ,
						$xextrato        
					)";
		}else{
			$sql = "UPDATE tbl_pedido SET
						posto              = $xposto         ,
						fabrica            = $login_fabrica  ,
						condicao           = $xcondicao      ,
						tabela             = $xtabela        ,
						admin              = $login_admin    ,
						tipo_pedido        = $tipo_pedido    ,
						pedido_cliente     = $xpedido_cliente,
						validade           = $xvalidade      ,
						entrega            = $xentrega       ,
						obs                = $xobs           ,
						linha              = $xlinha         ,
						transportadora     = $xtransportadora,
						tipo_frete         = $xtipo_frete    ,
						pedido_kit_extrato = $xextrato
					WHERE tbl_pedido.pedido  = $pedido
					AND   tbl_pedido.fabrica = $login_fabrica";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		if (strlen($msg_erro) == 0 and strlen($pedido) == 0) {
			$res = pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
			$pedido   = pg_result ($res,0,0);
			$msg_erro = pg_errormessage($con);
		}
		
		if (strlen($msg_erro) == 0) {
			$qtd_itens = $_POST['qtd_itens'];
			
			$nacional  = 0;
			$importado = 0;
			
			for ($i = 0 ; $i < $qtd_itens ; $i++) {
				//$novo = $_POST["novo".$i];
				//$item = $_POST["item".$i];
				$novo = "t";
				$item = "";
				
				$peca       = $_POST['peca_' . $i];
				$referencia = $_POST['referencia_' . $i];
				$qtde       = $_POST['qtde_' . $i];
				
				if(strlen($qtde) == 0 OR strlen($peca) == 0) {
					if (strlen($item) > 0 AND $novo == 'f') {
						$sql = "DELETE FROM tbl_pedido_item
								WHERE  tbl_pedido_item.pedido      = $pedido
								AND    tbl_pedido_item.pedido_item = $item;";
						$res = @pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}
				
				if (strlen($msg_erro) == 0) {
					$sql = "SELECT tbl_depara.para FROM tbl_depara WHERE tbl_depara.fabrica = $login_fabrica AND de = '$referencia'";
					$res = @pg_exec ($con,$sql);
					if(pg_numrows($res) > 0) {
						$para = pg_result($res,0,0);
						$sql = "SELECT tbl_peca.peca FROM tbl_peca WHERE tbl_peca.fabrica = $login_fabrica AND referencia = '$para'";
						$res = @pg_exec ($con,$sql);
						if(pg_numrows($res) > 0) $peca = pg_result($res,0,0);
					}
					$msg_erro = pg_errormessage($con);

					if (strlen($pedido) == 0 OR $novo == 't') {
						$sql = "INSERT INTO tbl_pedido_item (
									pedido,
									peca  ,
									qtde
								) VALUES (
									$pedido,
									$peca  ,
									$qtde
								)";
						$res = pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
						
						if (strlen($msg_erro) == 0) {
							$res         = pg_exec ($con,"SELECT CURRVAL ('seq_pedido_item')");
							$pedido_item = pg_result ($res,0,0);
							$msg_erro = pg_errormessage($con);
						}
					}else{
						$sql = "UPDATE tbl_pedido_item SET
									peca = $peca,
									qtde = $qtde
								WHERE  tbl_pedido_item.pedido      = $pedido
								AND    tbl_pedido_item.pedido_item = $item;";
						$res = @pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0) {
						$sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$login_fabrica)";
						$res = pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
					
					if (strlen ($msg_erro) > 0) {
						break ;
					}
				}
			}
		}
	}
	
	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");

		header ("Location: extrato_consulta.php");

		echo "<script language='javascript'>";
		echo "window.open ('pedido_finalizado.php?pedido=$pedido','pedido', 'toolbar=yes, location=no, status=no, scrollbars=yes, directories=no, width=500, height=400')";
		echo "</script>";

		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}


$layout_menu = "callcenter";
$title = "Relatório de Devolução de Peças Obrigatória";

include "cabecalho.php";

?>

<p>

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

a:link.top{
	color:#ffffff;
}
a:visited.top{
	color:#ffffff;
}
a:hover.top{
	color:#ffffff;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

</style>

<? if (strlen($msg_erro) > 0){ ?>
<table width='750' align='center' border='0' cellspacing='0' cellpadding='0'>
<tr>
	<td class="error" align='center'><? echo $msg_erro; ?></td>
</td>
</table>
<? } ?>

<? 

//include "javascript_pesquisas.php" ;

#----------------------- Lista Pecas de um extrato -----------------

	$sql = "SELECT  tbl_posto.nome                 AS nome_posto     ,
					tbl_posto_fabrica.posto                          ,
					tbl_posto_fabrica.codigo_posto                   
			FROM    tbl_extrato
			JOIN    tbl_posto         ON tbl_posto.posto         = tbl_extrato.posto
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_extrato.extrato = $extrato
			AND     tbl_extrato.fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) > 0){
	
		$posto        = pg_result($res,0,posto);
		$nome_posto   = pg_result($res,0,nome_posto);
		$codigo_posto = pg_result($res,0,codigo_posto);

		echo "<form name='frm_pedido_pecas_kit' method='post' action='$PHP_SELF'>\n";

		echo "<input type='hidden' name='posto'   value='$posto'>\n";
		echo "<input type='hidden' name='extrato' value='$extrato'>\n";

		echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
		echo "<tr class='menu_top'>\n";
		echo "<td align='left'>EXTRATO: ";
		echo $extrato;
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr class='menu_top'>\n";
		echo "<td align='left'>POSTO: ";
		echo $codigo_posto ." - ". $nome_posto;
		echo "</td>\n";
		echo "</TABLE>\n";
		echo "<br>";
	}
	?>
		
	<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
		<tr class="menu_top">
			<td align='center'>
				<b>
					Tipo do Pedido
				</b>
			</td>
			<td align='center'>
				<b>
					Tabela de Preços
				</b>
			</td>
			<td align='center'>
				<b>
					Condição de Pagamento
				</b>
			</td>
			<td align='center'>
				<b>
					Tipo de Frete
				</b>
			</td>
		</tr>
		<tr class="table_line">
			<td align='center'>
				<?
				$sql = "SELECT * 
						FROM tbl_tipo_pedido 
						WHERE fabrica = $login_fabrica";
				$res = pg_exec ($con,$sql);
				if (pg_numrows ($res) > 0) {
					echo "<select name='tipo_pedido' size='1'>";
					echo "<option selected> </option>";
					for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
						echo "<option value='" . pg_result ($res,$i,tipo_pedido) . "' ";
						if ($tipo_pedido == pg_result ($res,$i,tipo_pedido) ) echo " selected ";
						echo ">";
						echo pg_result ($res,$i,descricao);
						echo "</option>";
					}
					echo "</select>";
				}
				?>
			</td>
			<td align='center'>
				<?
				$sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa IS TRUE";
				$res = pg_exec ($con,$sql);
				if (pg_numrows ($res) > 0) {
					echo "<select name='tabela' size='1'>";
					echo "<option selected> </option>";
					for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
						echo "<option value='" . pg_result ($res,$i,tabela) . "' ";
						if ($tabela == pg_result ($res,$i,tabela) ) echo " selected ";
						echo ">";
						echo pg_result ($res,$i,sigla_tabela);
						echo "</option>";
					}
					echo "</select>";
				}
				?>
			</td>
			<td align='center'>
				<?
				$sql = "SELECT * FROM tbl_condicao WHERE fabrica = $login_fabrica ORDER BY lpad(trim(tbl_condicao.codigo_condicao),10,'0');";
				$res = pg_exec ($con,$sql);
				if (pg_numrows ($res) > 0) {
					echo "<select name='condicao' size='1'>";
					echo "<option selected> </option>";
					for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
						echo "<option value='" . pg_result ($res,$i,condicao) . "' ";
						if ($condicao == pg_result ($res,$i,condicao) ) echo " selected ";
						echo ">";
						echo pg_result ($res,$i,descricao);
						echo "</option>";
					}
					echo "</select>";
				}
			?>
			</td>
			<td align='center'>
				<SELECT name="tipo_frete" size="1">
				<option selected> </option>
				<option value="FOB" <? if ($tipo_frete == "FOB") echo " selected " ?> >FOB</option>
				<option value="CIF" <? if ($tipo_frete == "CIF") echo " selected " ?> >CIF</option>
				</SELECT>
			</td>
		</tr>
	</table>

	<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
		<tr class="menu_top">
			<td align='center'>
				<b>
				Pedido Cliente
				</b>
			</td>
			<td align='center'>
				<b>
				Validade
				</b>
			</td>
			<td align='center'>
				<b>
				Entrega
				</b>
			</td>
		<?
			$sql = "SELECT  tbl_fabrica.pedido_escolhe_transportadora
					FROM    tbl_fabrica
					WHERE   tbl_fabrica.fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows ($res) > 0) {
				$pedido_escolhe_transportadora = trim(pg_result ($res,0,pedido_escolhe_transportadora));
			}

			if ($pedido_escolhe_transportadora == 't'){
		?>
			<td align='center'>
				<b>
				Transportadora
				</b>
			</td>
		<?
			}
		?>
		</tr>

		<tr class="table_line">
			<td align='center'>
				<input type="text" name="pedido_cliente" size="10" maxlength="20" value="<? echo $pedido_cliente ?>" class="textbox">
			</td>

			<?
			if (strlen ($validade) == 0) $validade = "10 dias";
			if (strlen ($entrega) == 0)  $entrega  = "15 dias";
			?>
			<td align='center'>
				<input type="text" name="validade" size="10" maxlength="20" value="<? echo $validade ?>" class="textbox">
			</td>
			<td align='center'>
				<input type="text" name="entrega" size="10" maxlength="20" value="<? echo $entrega ?>" class="textbox">
			</td>
		<?
			if ($pedido_escolhe_transportadora == 't'){
		?>
			<td align='center'>
		<?
			$sql = "SELECT	tbl_transportadora.transportadora        ,
					tbl_transportadora.cnpj                  ,
					tbl_transportadora.nome                  ,
					tbl_transportadora_fabrica.codigo_interno
					FROM	tbl_transportadora
					JOIN	tbl_transportadora_fabrica USING(transportadora)
					WHERE	tbl_transportadora_fabrica.fabrica = $login_fabrica
					AND		tbl_transportadora_fabrica.ativo  = 't' ";
			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) > 0) {

				if (pg_numrows ($res) <= 20) {

					echo "		<select name='transportadora'>";
					echo "			<option selected></option>";
					for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
						echo "<option value='".pg_result($res,$i,transportadora)."' ";
						if ($transportadora == pg_result($res,$i,transportadora) ) echo " selected ";
						echo ">";
						echo pg_result($res,$i,codigo_interno) ." - ".pg_result($res,$i,nome);
						echo "</option>\n";
					}
					echo "		</select>";

				}else{

					echo "		<input type='hidden' name='transportadora' value=''>";
					echo "		<input type='text'   name='transportadora_codigo' size='6' maxlength='10' value='$transportadora_codigo' class='textbox' >&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\" style='cursor:pointer;'>";
					echo "		<input type='hidden' name='transportadora_cnpj' value='$transportadora_cnpj' class='textbox' >";
					echo "		<input type='text'   name='transportadora_nome' size='15' maxlength='50' value='$transportadora_nome' class='textbox' >&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";

				}

			}else{

				echo " - - - ";

			}

		?>
			</td>
		<?
			}
		?>
		</tr>
		</table>

		<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
		<tr class="menu_top">
			<td align='center'>
				<b>
				Mensagem
				</b>
			</td>
		</tr>
		<tr>
			<td align='center'>
				<input type="text" name="obs" size="50" value="<? echo $obs ?>" class="textbox">
			</td>
		</tr>
		</table>

		<?
		
		$sql = "SELECT   tbl_peca.peca                 ,
						 tbl_peca.referencia           ,
						 tbl_peca.descricao            ,
						 SUM (tbl_os_item.qtde) AS qtde
				FROM     tbl_extrato
				JOIN     tbl_os_extra      ON tbl_os_extra.extrato    = tbl_extrato.extrato
				JOIN     tbl_os_produto    ON tbl_os_produto.os       = tbl_os_extra.os
				JOIN     tbl_os_item       ON tbl_os_item.os_produto  = tbl_os_produto.os_produto
				JOIN     tbl_peca          ON tbl_peca.peca           = tbl_os_item.peca
				AND      tbl_peca.fabrica = $login_fabrica
				WHERE    tbl_extrato.extrato = $extrato
				AND      tbl_extrato.fabrica = $login_fabrica
				AND      tbl_peca.acumular_kit is true
				GROUP BY tbl_peca.peca      ,
						 tbl_peca.referencia,
						 tbl_peca.descricao";
 		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 0) {?>
			<table width='500' align='center' border='0' cellspacing='2' cellpadding='2'>
				<tr class='table_line'>
					<td align='center'><font size='3' color=#FF0000><b>Não existe peça que compõe o KIT</b></font></td>
				</tr>
			</table>
		<?}else{

			echo "<table width='600' align='center' border='0' cellspacing='0' cellpadding='0'>\n";
			echo "<tr class='menu_top'><td align='center' colspan='3'>RELAÇÃO DAS PEÇAS QUE COMPÕE O KIT</td></tr>\n";

			echo "<td class='menu_top'>Peça</td>\n";
			echo "<td class='menu_top'>Descrição</td>\n";
			echo "<td class='menu_top'>Qtde</td>\n";

			for ($i=0; $i < pg_numrows($res); $i++){
				$peca            = trim(pg_result ($res,$i,peca));
				$referencia_peca = trim(pg_result ($res,$i,referencia));
				$descricao_peca  = trim(pg_result ($res,$i,descricao));
				$qtde            = trim(pg_result ($res,$i,qtde));
			
				$cor = "#F7F7F7";
				if ($i % 2 == 0) $cor = '#F1F4FA';

				echo "<tr class='table_line' bgcolor='$cor'>\n";
				echo "<td align='center'><input type='hidden' name='peca_$i' value='$peca'><input type='hidden' name='referencia_$i' value='$referencia_peca'>$referencia_peca</td>\n";
				echo "<td align='left'>$descricao_peca</td>\n";
				echo "<td align='center'><input type='text' name='qtde_$i' size='5' maxlength='10' value='$qtde' class='frm' style='text-align:right'></td>\n";
				echo "</tr>\n";
			
			}	
			echo "<input type='hidden' name='qtd_itens' value='$i'>";
			echo "</table>\n";
			echo "<br>";
		}
			
		echo "<table width='600'>";
		echo "<tr>";
		echo "<td align='right'>";
		echo "<input type='hidden' name='btn_acao' value=''>";
	
		echo "<img src='imagens/btn_gerarpedido.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_pedido_pecas_kit.btn_acao.value == '' ) { document.frm_pedido_pecas_kit.btn_acao.value='gravar' ; document.frm_pedido_pecas_kit.submit() } else { alert ('Aguarde submissão') }\" ALT='Gerar Pedido' border='0'>";

		echo "</td>";
		echo "</tr>";
		echo "</table>";

echo "<br>";
include "rodape.php"; 
?>
