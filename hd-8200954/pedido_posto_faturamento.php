<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$sql = "SELECT	tbl_tipo_posto.distribuidor
		FROM	tbl_tipo_posto
		JOIN	tbl_posto_fabrica USING (tipo_posto)
		WHERE	tbl_posto_fabrica.posto   = $login_posto
		AND		tbl_posto_fabrica.fabrica = $login_fabrica
		AND		tbl_tipo_posto.distribuidor IS true";
$res = pg_exec ($con,$sql);

if (pg_result($res,0,distribuidor) <> 't' OR strlen($pedido) == 0){
	header("Location: pedido_relacao.php");
	exit;
}


$os_item_nf_excluir = $_GET['os_item_nf_excluir'];
if (strlen($os_item_nf_excluir) > 0 and strlen($pedido) > 0) {
	$sql = "DELETE FROM tbl_os_item_nf WHERE os_item_nf = $os_item_nf_excluir";
	//echo $sql."<BR>";
	$res = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen($msg_erro) == 0) {
		$sql = "UPDATE tbl_pedido SET pedido_atendido_total = NULL WHERE pedido = $pedido";
		//echo $sql."<BR>";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
}

$pedido_item_nf_excluir = $_GET['pedido_item_nf_excluir'];
if (strlen($pedido_item_nf_excluir) > 0 and strlen($pedido) > 0) {
	$sql = "DELETE FROM tbl_pedido_item_nf WHERE pedido_item_nf = $pedido_item_nf_excluir";
	//echo $sql."<BR>";
	$res = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen($msg_erro) == 0) {
		$sql = "UPDATE tbl_pedido SET pedido_atendido_total = NULL WHERE pedido = $pedido";
		//echo $sql."<BR>";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
}


if ($btn_acao == "gravar") {

	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$pedido      = $_POST['pedido'];
	$tipo        = $_POST['tipo'];
	$total_pecas = $_POST['total_pecas'];

	$atendido_total            = 't';
	$aux_pedido_atendido_total = 't';

	for ($i = 0; $i < $total_pecas; $i++) {

		/*====================== PEDIDOS DE ORDEM DE SERVIÇO ======================*/
		if ($tipo == "OS") {
			$os_item_nf  = $_POST['os_item_nf_' .$i];
			$os_item     = $_POST['os_item_'    .$i];
			$qtde_pedida = $_POST['qtde_pedida_'.$i];
			$qtde_nf     = $_POST['qtde_nf_'    .$i];
			$nota_fiscal = $_POST['nota_fiscal_'.$i];
			$data_nf     = $_POST['data_nf_'    .$i];
			$qtde_saldo  = $_POST['qtde_saldo_' .$i];
			$peca        = $_POST['peca_'       .$i];

//
			if (strlen($msg_erro) == 0) {
				if (strlen($qtde_pedida) == 0) {
					$aux_qtde_pedida = 'null';
				} else {
					$aux_qtde_pedida = "'".trim($qtde_pedida)."'";
				}
			}

			if (strlen($msg_erro) == 0) {
				if (strlen($qtde_nf) == 0) {
					$aux_qtde_nf = 'null';
					//se foi digitado algo para esta peça, seta erro
					if (strlen($nota_fiscal) > 0 or strlen($data_nf) > 0)
						$msg_erro    = "Informe a quantidade atendida para a peça ".$peca;
				} else {
					$aux_qtde_nf = "'".trim($qtde_nf)."'";
				}
			}
	
			//compara com os valores da tela
			if (strlen($msg_erro) == 0) {
				if (trim($qtde_saldo) < trim($qtde_nf) ) {
					$msg_erro = "Quantidade atendida não pode ser maior que a quantidade saldo para a peça ".$peca;
				}
			}

			//em caso do usuario atualizar a tela, verifica os valores no banco para não duplicar
			if (strlen($msg_erro) == 0) {
				$sql = "SELECT (tbl_os_item.qtde - COALESCE(sum(tbl_os_item_nf.qtde_nf),0)) as qtde_restante
						FROM tbl_os_item
						LEFT JOIN tbl_os_item_nf using(os_item)
						WHERE tbl_os_item.os_item = $os_item
						GROUP BY tbl_os_item.os_item, tbl_os_item.qtde";
				$res = pg_exec($con, $sql);
				$qtde_restante = pg_result($res,0,0);

				if (trim($qtde_restante) < trim($qtde_nf) ) {
					$msg_erro = "A quantidade máxima a ser atendida para a peça ".$peca." é de ".$qtde_restante." peças.";
				}
			}

			if (strlen($msg_erro) == 0) {
				if (strlen($nota_fiscal) == 0) {
					$aux_nota_fiscal = 'null';
					//se foi digitado algo para esta peça, seta erro
					if (strlen($qtde_nf) > 0 or strlen($data_nf) > 0)
						$msg_erro        = "Informe o número da nota fiscal para a peça ".$peca;
				} else {
					$aux_nota_fiscal = "'".trim($nota_fiscal)."'";
				}
			}

			if (strlen($msg_erro) == 0) {
				if (strlen($data_nf) == 0) {
					$aux_data_nf = 'null';
					//se foi digitado algo para esta peça, seta erro
					if (strlen($nota_fiscal) > 0 or strlen($qtde_nf) > 0)
						$msg_erro = "Informe a data da nota fiscal para a peça ".$peca;
				} else {
					$aux_data_nf = "'".formata_data($data_nf)."'";
					
					//valida a data digitada
					$sql = "SELECT (current_date < $aux_data_nf)";
					$res = pg_exec($con,$sql);
					$resp = pg_result($res,0,0);
					if ($resp == 't') $msg_erro = "Data da nota fiscal não pode ser superior a data atual para a peça ".$peca;

					if (strlen($msg_erro) == 0) {
						$sql = "SELECT ($aux_data_nf < current_date - interval'60 days')";
						$res = pg_exec($con,$sql);
						$resp = pg_result($res,0,0);
						if ($resp == 't') $msg_erro = "Data da nota fiscal não pode ser anterior a 60 dias da data atual para a peça ".$peca;
					}
				}
			}


			if (strlen($aux_qtde_nf) > 0 AND $aux_qtde_nf <> 'null' AND strlen($msg_erro) == 0) {
				$sql = "INSERT INTO tbl_os_item_nf (
							os_item    ,
							qtde_nf    ,
							nota_fiscal,
							data_nf
						) VALUES (
							$os_item        ,
							$aux_qtde_nf    ,
							$aux_nota_fiscal,
							$aux_data_nf
						)";

				
				$res = pg_exec ($con,$sql);
				//echo $sql."<BR>";
				$msg_erro = pg_errormessage($con);
				
				if (strlen ($msg_erro) == 0) {
					if ($qtde_saldo > $qtde_nf) {
						$aux_pedido_atendido_total = 'f';
					}
				}
			}else{
				$atendido_total = 'f';
			}
		/*========================== FIM PEDIDOS DE OS ============================*/
		

		/*============ PEDIDOS INDEPENDENTES (NÃO FORAM GERADOS DE OS) ============*/
		}else{
			$pedido_item_nf  = $_POST['pedido_item_nf_'.$i];
			$pedido_item     = $_POST['pedido_item_'.   $i];
			$qtde_pedida     = $_POST['qtde_pedida_'.   $i];
			$qtde_nf         = $_POST['qtde_nf_'.       $i];
			$nota_fiscal     = $_POST['nota_fiscal_'.   $i];
			$data_nf         = $_POST['data_nf_'.       $i];
			$qtde_saldo      = $_POST['qtde_saldo_'.    $i];
			$peca            = $_POST['peca_'.          $i];

			if (strlen($msg_erro) == 0) {
				if (strlen($qtde_pedida) == 0) {
					$aux_qtde_pedida = 'null';
				} else {
					$aux_qtde_pedida = "'".trim($qtde_pedida)."'";
				}
			}

			if (strlen($msg_erro) == 0) {
				if (strlen($qtde_nf) == 0) {
					$aux_qtde_nf = 'null';
					//se foi digitado algo para esta peça, seta erro
					if (strlen($nota_fiscal) > 0 or strlen($data_nf) > 0)
						$msg_erro    = "Informe a quantidade atendida para a peça ".$peca;
				} else {
					$aux_qtde_nf = "'".trim($qtde_nf)."'";
				}
			}
	
			//compara com os valores da tela
			if (strlen($msg_erro) == 0) {
				if (trim($qtde_saldo) < trim($qtde_nf) ) {
					$msg_erro = "Quantidade atendida não pode ser maior que a quantidade saldo para a peça ".$peca;
				}
			}

			//em caso do usuario atualizar a tela, verifica os valores no banco para não duplicar
			if (strlen($msg_erro) == 0) {
				$sql = "SELECT (tbl_pedido_item.qtde - COALESCE(sum(tbl_pedido_item_nf.qtde_nf),0)) as qtde_restante
						FROM tbl_pedido_item
						LEFT JOIN tbl_pedido_item_nf using(pedido_item)
						WHERE tbl_pedido_item.pedido_item = $pedido_item
						GROUP BY tbl_pedido_item.pedido_item, tbl_pedido_item.qtde";
				$res = pg_exec($con, $sql);
				$qtde_restante = pg_result($res,0,0);

				if (trim($qtde_restante) < trim($qtde_nf) ) {
					$msg_erro = "A quantidade máxima a ser atendida para a peça ".$peca." é de ".$qtde_restante." peças.";
				}
			}

			if (strlen($msg_erro) == 0) {
				if (strlen($nota_fiscal) == 0) {
					$aux_nota_fiscal = 'null';
					//se foi digitado algo para esta peça, seta erro
					if (strlen($qtde_nf) > 0 or strlen($data_nf) > 0)
						$msg_erro        = "Informe o número da nota fiscal para a peça ".$peca;
				} else {
					$aux_nota_fiscal = "'".trim($nota_fiscal)."'";
				}
			}

			if (strlen($msg_erro) == 0) {
				if (strlen($data_nf) == 0) {
					$aux_data_nf = 'null';
					//se foi digitado algo para esta peça, seta erro
					if (strlen($nota_fiscal) > 0 or strlen($qtde_nf) > 0)
						$msg_erro = "Informe a data da nota fiscal para a peça ".$peca;
				} else {
					$aux_data_nf = "'".formata_data($data_nf)."'";
					
					//valida a data digitada
					$sql = "SELECT (current_date < $aux_data_nf)";
					$res = pg_exec($con,$sql);
					$resp = pg_result($res,0,0);
					if ($resp == 't') $msg_erro = "Data da nota fiscal não pode ser superior a data atual para a peça ".$peca;

					if (strlen($msg_erro) == 0) {
						$sql = "SELECT ($aux_data_nf < current_date - interval'60 days')";
						$res = pg_exec($con,$sql);
						$resp = pg_result($res,0,0);
						if ($resp == 't') $msg_erro = "Data da nota fiscal não pode ser anterior a 60 dias da data atual para a peça ".$peca;
					}
				}
			}

			if (strlen($aux_qtde_nf) > 0 AND $aux_qtde_nf <> 'null' AND strlen($msg_erro) == 0) {
				$sql = "INSERT INTO tbl_pedido_item_nf (
							pedido_item,
							qtde_nf    ,
							nota_fiscal,
							data_nf
						) VALUES (
							$pedido_item    ,
							$aux_qtde_nf    ,
							$aux_nota_fiscal,
							$aux_data_nf
						)";

				$res = pg_exec ($con,$sql);
				//echo $sql."<BR>";
				$msg_erro = pg_errormessage($con);
				
				if (strlen ($msg_erro) == 0) {
					if ($qtde_saldo > $qtde_nf) {
						$aux_pedido_atendido_total = 'f';
					}
				}
			}else{
				$atendido_total = 'f';
			}
		}
		/*====================== FIM PEDIDOS INDEPENDENTES ========================*/
	}


	if (strlen ($msg_erro) == 0) {
		if ($aux_pedido_atendido_total == 't' AND $atendido_total == 't'){
			$sql = "UPDATE tbl_pedido SET
						pedido_atendido_total = '$aux_pedido_atendido_total'
					WHERE pedido = $pedido";
		}else{
			$sql = "UPDATE tbl_pedido SET
						pedido_atendido_total = null
					WHERE pedido = $pedido";
		}
		$res = @pg_exec ($con,$sql);
		//echo $sql."<BR>";
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		//header ("Location: pedido_posto_relacao.php");
		//exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$title       = "Dados do Pedido do Posto";
$layout_menu = 'pedido';
include "cabecalho.php";

#------------ Le Pedido da Base de dados ------------#

$pedido = $_GET['pedido'];
if (strlen($_POST['pedido']) > 0) $pedido = $_POST['pedido'];

if (strlen ($pedido) > 0) {
	$sql = "SELECT  tbl_pedido.pedido                                ,
					tbl_pedido.condicao                              ,
					tbl_pedido.tabela                                ,
					tbl_pedido.pedido_cliente                        ,
					to_char(tbl_pedido.data,'DD/MM/YYYY') as data    ,
					tbl_condicao.descricao      AS condicao_descricao,
					tbl_tabela.tabela                                ,
					tbl_tabela.descricao        AS tabela_descricao  ,
					tbl_posto.nome                                   ,
					tbl_posto_fabrica.codigo_posto                   
			FROM    tbl_pedido
			LEFT JOIN tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
			LEFT JOIN tbl_tabela   ON tbl_tabela.tabela     = tbl_pedido.tabela
			JOIN    tbl_posto         USING (posto)
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
									 AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE   tbl_pedido.pedido       = $pedido
			AND     tbl_pedido.distribuidor = $login_posto
			AND     tbl_pedido.fabrica      = $login_fabrica;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$pedido           = trim(pg_result ($res,0,pedido));
		$data             = trim(pg_result ($res,0,data));
		$condicao         = trim(pg_result ($res,0,condicao_descricao));
		$tabela           = trim(pg_result ($res,0,tabela));
		$tabela_descricao = trim(pg_result ($res,0,tabela_descricao));
		$posto_nome       = trim(pg_result ($res,0,nome));
		$codigo_posto     = trim(pg_result ($res,0,codigo_posto));
		$detalhar = "ok";
	}
}
?>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}
</style>

<p>

<? 
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<? } ?>


<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<form name='frm_pedido' method='post' action='<? echo $PHP_SELF; ?>'>
<INPUT TYPE="hidden" name='pedido' value='<? echo $pedido; ?>'>
<tr>
	<td valign="top" align="center">
		<table width="100%" border="0" cellspacing="1" cellpadding="2">
		<tr>
			<td nowrap align='center' class='menu_top'>Posto</td>
			<td nowrap align='center' class='menu_top'>Pedido</td>
			<td nowrap align='center' class='menu_top'>Data</td>
			<td nowrap align='center' class='menu_top'>Cond. Pagto</td>
			<td nowrap align='center' class='menu_top'>Tabela de Preços</td>
		</tr>
		<tr>
			<td nowrap align='center' class='table_line'><? echo $codigo_posto ." - ". $posto_nome ?></td>
			<td nowrap align='center' class='table_line'><? echo $pedido ?></td>
			<td nowrap align='center' class='table_line'><? echo $data ?></td>
			<td nowrap align='center' class='table_line'><? echo $condicao?></td>
			<td nowrap align='center' class='table_line'><? echo $tabela_descricao?></td>
		</tr>
		</table>

		<br>
		
		<?
		$sql = "SELECT *
				FROM   tbl_os_item
				WHERE  tbl_os_item.pedido = $pedido;";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
		?>
			<table width="100%" border="0" cellspacing="1" cellpadding="2" align='center'>
			<tr>
				<td class='menu_top' colspan='8'>PENDENTES</td>
			</tr>
			<tr>
				<td class='menu_top'>OS</td>
				<td class='menu_top'>Peça</td>
				<td class='menu_top'>Qt. Pedida</td>
				<td class='menu_top'>Qt. Já atendida</td>
				<td class='menu_top'>Qt. Atendida</td>
				<td class='menu_top'>Qt. Saldo</td>
				<td class='menu_top'>NF</td>
				<td class='menu_top'>Dt. Emissão</td>
			</tr>
			
			<?
			$sql = "SELECT	tbl_os_item.os_item                                     ,
						tbl_os_item.qtde                                        ,
						tbl_os.os                                               ,
						tbl_os.sua_os                                           ,
						tbl_peca.referencia                                     ,
						tbl_peca.descricao                                      ,
						COALESCE(sum(tbl_os_item_nf.qtde_nf),0) as qtde_atendida
					FROM tbl_os_item
					JOIN tbl_os_produto   ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN tbl_os           ON tbl_os.os                 = tbl_os_produto.os
					JOIN tbl_peca         ON tbl_peca.peca             = tbl_os_item.peca
					LEFT JOIN tbl_os_item_nf ON tbl_os_item_nf.os_item    = tbl_os_item.os_item
					WHERE tbl_os_item.pedido = $pedido
					GROUP BY tbl_os_item.os_item,
							 tbl_os_item.qtde   ,
							 tbl_os.os          ,
							 tbl_os.sua_os      ,
							 tbl_peca.referencia,
							 tbl_peca.descricao
					HAVING tbl_os_item.qtde > COALESCE(sum(tbl_os_item_nf.qtde_nf),0)
					ORDER BY tbl_os_item.os_item,
							 tbl_os.os          ,
							 tbl_os.sua_os      ,
							 tbl_peca.referencia,
							 tbl_peca.descricao ,
							 tbl_os_item.qtde;";
			$res = pg_exec ($con,$sql);
			$total_pedido = 0 ;

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$cor = "#FFFFFF";
				if ($i % 2 == 0) $cor = '#F1F4FA';
				
				$os_item     = pg_result($res,$i,os_item);
				$os          = pg_result($res,$i,os);
				$sua_os      = pg_result($res,$i,sua_os);
				$peca        = pg_result($res,$i,referencia) . " - " . pg_result($res,$i,descricao);
				$qtde_pedida = pg_result($res,$i,qtde);
				$qtde_nf     = pg_result($res,$i,qtde_atendida);
				$qtde_saldo  = $qtde_pedida - $qtde_nf;

				echo "<tr bgcolor='".$cor."'>";
					echo "<td class='table_line'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
					echo "<td class='table_line'>".$peca."</td>";
					echo "<input type='hidden' name='peca_".$i."' value='".$peca."'>";

					echo "<td class='table_line' align='center'>";
						echo $qtde_pedida;
						echo "<input type='hidden' name='qtde_pedida_".$i."' value='".$qtde_pedida."'>";
						echo "<input type='hidden' name='os_item_".    $i."' value='".$os_item."'>";
						echo "<input type='hidden' name='os_item_nf_". $i."' value='".$os_item_nf."'>";
						echo "<input type='hidden' name='qtde_saldo_". $i."' value='".$qtde_saldo."'>";
					echo "</td>";
					echo "<td class='table_line' align='center'><INPUT TYPE='text' NAME='xqtde_nf_".$i."' value='".$qtde_nf."' size='3' disabled></td>";
					echo "<td class='table_line' align='center'><INPUT TYPE='text' NAME='qtde_nf_".$i."' value='";if (strlen($msg_erro) > 0) echo $_POST['qtde_nf_'.$i]; else echo ""; echo "' size='3' ></td>";
					echo "<td class='table_line' align='center'>".$qtde_saldo."</td>";
					echo "<input type='hidden' name='qtde_saldo_".$i."' value='".$qtde_saldo."'>";
					echo "<td class='table_line' align='center'><INPUT TYPE='text' NAME='nota_fiscal_".$i."' value='";if (strlen($msg_erro) > 0) echo $_POST['nota_fiscal_'.$i]; else echo ""; echo "' size='10' ></td>";
					echo "<td class='table_line' align='center'><INPUT TYPE='text' NAME='data_nf_".$i."' value='";if (strlen($msg_erro) > 0) echo $_POST['data_nf_'.$i]; else echo ""; echo "' size='12' ></td>";
				echo "</tr>";
			}
			echo "<input type='hidden' name='total_pecas' value='$i'>";
			echo "<input type='hidden' name='tipo' value='OS'>";
			?>
			</table><BR>


			<table width="100%" border="0" cellspacing="1" cellpadding="2" align='center'>
			<tr>
				<td height="27" valign="middle" align="center" >
					<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'>
					&nbsp;&nbsp;
					<img src='imagens/btn_voltar.gif' onclick="javascript: history.back();" ALT="Volta" border='0' style='cursor: pointer'>
				</td>
			</tr>
			</table><BR>


			<? //ja atendidos ?>
			<table width="100%" border="0" cellspacing="1" cellpadding="2" align='center'>
			<tr>
				<td class='menu_top' colspan='8'>ATENDIDOS</td>
			</tr>
			<tr>
				<td class='menu_top'>OS</td>
				<td class='menu_top'>Peça</td>
				<td class='menu_top'>Qt. Pedida</td>
				<td class='menu_top'>Qt. Atendida</td>
				<td class='menu_top'>Qt. Saldo</td>
				<td class='menu_top'>NF</td>
				<td class='menu_top'>Dt. Emissão</td>
				<td class='menu_top'>&nbsp;</td>
			</tr>
			
			<?
			$sql = "SELECT	distinct tbl_os_item.os_item                            ,
							tbl_os_item.qtde                                        ,
							tbl_os.os                                               ,
							tbl_os.sua_os                                           ,
							tbl_peca.referencia                                     ,
							tbl_peca.descricao
					FROM  tbl_os_item
					JOIN  tbl_os_produto   ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN  tbl_os           ON tbl_os.os                 = tbl_os_produto.os
					JOIN  tbl_peca         ON tbl_peca.peca             = tbl_os_item.peca
					JOIN  tbl_os_item_nf   ON tbl_os_item_nf.os_item    = tbl_os_item.os_item
					WHERE tbl_os_item.pedido = $pedido
					ORDER BY tbl_os_item.os_item";
			$res = pg_exec ($con,$sql);
			$total_pedido = 0 ;

			$cor = '#F1F4FA';
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$os_item     = pg_result($res,$i,os_item);
				$os          = pg_result($res,$i,os);
				$sua_os      = pg_result($res,$i,sua_os);
				$peca        = pg_result($res,$i,referencia) . " - " . pg_result($res,$i,descricao);
				$qtde_pedida = pg_result($res,$i,qtde);

				if ($os_item_ant <> $os_item and $cor=="#FFFFFF") 
					$cor = '#F1F4FA';
				elseif ($os_item_ant <> $os_item and $cor=="#F1F4FA") 
					$cor = '#FFFFFF';

				$sqli = "SELECT	tbl_os_item_nf.os_item_nf                               ,
								tbl_os_item_nf.qtde_nf                                  ,
								tbl_os_item_nf.nota_fiscal                              ,
								tbl_os_item_nf.data_nf                           as data,
								to_char(tbl_os_item_nf.data_nf, 'DD/MM/YYYY') as data_nf,
								(tbl_os_item.qtde - tbl_os_item_nf.qtde_nf) as qtd_saldo
						FROM    tbl_os_item
						JOIN    tbl_pedido       ON tbl_pedido.pedido      = tbl_os_item.pedido
						JOIN    tbl_peca         ON tbl_peca.peca          = tbl_os_item.peca
						LEFT JOIN tbl_os_item_nf ON tbl_os_item_nf.os_item = tbl_os_item.os_item
						WHERE   tbl_os_item.pedido  = $pedido
						AND     tbl_os_item.os_item = $os_item
						AND     tbl_os_item_nf.qtde_nf > 0
						ORDER BY tbl_os_item.os_item, data;";
				$resi = pg_exec ($con,$sqli);


				echo "<tr bgcolor=".$cor.">";
					echo "<td class='table_line'><a href='os_press.php?os=".$os."' target='_blank'>".$sua_os."</a></td>";
					echo "<td class='table_line'>$peca</td>";
					echo "<td class='table_line' align='center'>$qtde_pedida</td>";
					echo "<td class='table_line' align='center'>";
						for ($x=0; $x < pg_numrows($resi); $x++) {
							echo "<INPUT TYPE='text' value='".pg_result($resi,$x,qtde_nf)."' size='3' readonly disabled>";
							if ($x < pg_numrows($resi)-1) echo "<BR>";
						}
					echo "</td>";
					echo "<td class='table_line' align='center'>";
						$saldo_total = $qtde_pedida;
						for ($x=0; $x < pg_numrows($resi); $x++) {
							$saldo_total -= pg_result($resi,$x,qtde_nf);
						}
						echo $saldo_total;
					echo "</td>";
					echo "<td class='table_line' align='center'>";
						for ($x=0; $x < pg_numrows($resi); $x++) {
							echo "<INPUT TYPE='text' value='".pg_result($resi,$x,nota_fiscal)."' size='10' readonly disabled>";
							if ($x < pg_numrows($resi)-1) echo "<BR>";
						}
					echo "</td>";
					echo "<td class='table_line' align='center'>";
						for ($x=0; $x < pg_numrows($resi); $x++) {
							echo "<INPUT TYPE='text' value='".pg_result($resi,$x,data_nf)."' size='12' readonly disabled>";
							if ($x < pg_numrows($resi)-1) echo "<BR>";
						}
					echo "</td>";
					echo "<td class='table_line' align='center'>";
						for ($x=0; $x < pg_numrows($resi); $x++) {
							echo "<a href=\"javascript: if (confirm('Deseja realmente excluir este atendimento ? ".'\r\n'."OS: ".$sua_os.", Peça: ".$peca.'\r\n'."NF: ".pg_result($resi,$x,nota_fiscal).", Emissão: ".pg_result($resi,$x,data_nf)."') == true) { window.location='$PHP_SELF?pedido=".$pedido."&os_item_nf_excluir=".pg_result($resi,$x,os_item_nf)."'; }\"><img id='excluir_".$i."' border='0' src='imagens/btn_excluir.gif'></a>";
							if ($x < pg_numrows($resi)-1) echo "<BR>";
						}
					echo "</td>";
				echo "</tr>";

				$pedido_item_ant = $pedido_item;
			}
			?>
			</table>


		<? }else{ ?>
		
			<? //pendentes ?>
			<table width="100%" border="0" cellspacing="1" cellpadding="2" align='center'>
			<tr>
				<td class='menu_top' colspan='8'>PENDENTES</td>
			</tr>
			<tr>
				<td class='menu_top'>Peça</td>
				<td class='menu_top'>Qt. Pedida</td>
				<td class='menu_top'>Qt. Já atendida</td>
				<td class='menu_top'>Qt. Atendida</td>
				<td class='menu_top'>Qt. Saldo</td>
				<td class='menu_top'>NF</td>
				<td class='menu_top'>Dt. Emissão</td>
			</tr>
			
			<?
			$sql = "SELECT  tbl_pedido_item.pedido_item                                 ,
							tbl_pedido_item.qtde                                        ,
							tbl_pedido.pedido                                           ,
							tbl_peca.referencia                                         ,
							tbl_peca.descricao                                          ,
							COALESCE(sum(tbl_pedido_item_nf.qtde_nf),0) as qtde_atendida
					FROM    tbl_pedido_item
					JOIN    tbl_pedido           ON tbl_pedido.pedido              = tbl_pedido_item.pedido
					JOIN    tbl_peca             ON tbl_peca.peca                  = tbl_pedido_item.peca
					LEFT JOIN tbl_pedido_item_nf ON tbl_pedido_item_nf.pedido_item = tbl_pedido_item.pedido_item
					WHERE   tbl_pedido_item.pedido = $pedido
					GROUP BY tbl_pedido_item.pedido_item,
							 tbl_pedido_item.qtde       ,
							 tbl_pedido.pedido          ,
							 tbl_peca.referencia        ,
							 tbl_peca.descricao
					HAVING tbl_pedido_item.qtde > COALESCE(sum(tbl_pedido_item_nf.qtde_nf),0)
					ORDER BY tbl_pedido_item.pedido_item,
							 tbl_pedido_item.qtde       ,
							 tbl_pedido.pedido          ,
							 tbl_peca.referencia        ,
							 tbl_peca.descricao;";
			$res = pg_exec ($con,$sql);
			$total_pedido = 0 ;

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$cor = "#FFFFFF";
				if ($i % 2 == 0) $cor = '#F1F4FA';

				$pedido_item     = pg_result($res,$i,pedido_item);
				$pedido          = pg_result($res,$i,pedido);
				$peca            = trim(pg_result($res,$i,referencia)) . " - " . trim(pg_result($res,$i,descricao));
				$qtde_pedida     = pg_result($res,$i,qtde);
				$qtde_nf         = pg_result($res,$i,qtde_atendida);
				$qtde_saldo      = $qtde_pedida - $qtde_nf;

				echo "<tr bgcolor='".$cor."'>";
					echo "<td class='table_line'>".$peca."</td>";
					echo "<input type='hidden' name='peca_".$i."' value='".$peca."'>";
					echo "<td class='table_line' align='center'>";
						echo $qtde_pedida;
						echo "<input type='hidden' name='qtde_pedida_".   $i."' value='".$qtde_pedida."'>";
						echo "<input type='hidden' name='pedido_item_".   $i."' value='".$pedido_item."'>";
						echo "<input type='hidden' name='pedido_item_nf_".$i."' value='".$pedido_item_nf."'>";
						echo "<input type='hidden' name='qtde_saldo_".    $i."' value='".$qtde_saldo."'>";
					echo "</td>";
					echo "<td class='table_line' align='center'><INPUT TYPE='text' NAME='xqtde_nf_".$i."' value='".$qtde_nf."' size='3' disabled></td>";
					echo "<td class='table_line' align='center'><INPUT TYPE='text' NAME='qtde_nf_".$i."' value='";if (strlen($msg_erro) > 0) echo $_POST['qtde_nf_'.$i]; else echo ""; echo "' size='3' ></td>";
					echo "<td class='table_line' align='center'>".$qtde_saldo."</td>";
					echo "<input type='hidden' name='qtde_saldo_".$i."' value='".$qtde_saldo."'>";
					echo "<td class='table_line' align='center'><INPUT TYPE='text' NAME='nota_fiscal_".$i."' value='";if (strlen($msg_erro) > 0) echo $_POST['nota_fiscal_'.$i]; else echo ""; echo "' size='10' ></td>";
					echo "<td class='table_line' align='center'><INPUT TYPE='text' NAME='data_nf_".$i."' value='";if (strlen($msg_erro) > 0) echo $_POST['data_nf_'.$i]; else echo ""; echo "' size='12' ></td>";
				echo "</tr>";
			}
			echo "<input type='hidden' name='total_pecas' value='$i'>";
			echo "<input type='hidden' name='tipo' value='PEDIDO'>";
			?>
			</table><BR>


			<table width="100%" border="0" cellspacing="1" cellpadding="2" align='center'>
			<tr>
				<td height="27" valign="middle" align="center" >
					<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'>
					&nbsp;&nbsp;
					<img src='imagens/btn_voltar.gif' onclick="javascript: history.back();" ALT="Volta" border='0' style='cursor: pointer'>
				</td>
			</tr>
			</table><BR>


			<? //atendidos ?>
			<table width="100%" border="0" cellspacing="1" cellpadding="2" align='center'>
			<tr>
				<td class='menu_top' colspan='7'>ATENDIDOS</td>
			</tr>
			<tr>
				<td class='menu_top'>Peça</td>
				<td class='menu_top'>Qt. Pedida</td>
				<td class='menu_top'>Qt. Atendida</td>
				<td class='menu_top'>Qt. Saldo</td>
				<td class='menu_top'>NF</td>
				<td class='menu_top'>Dt. Emissão</td>
				<td class='menu_top'>&nbsp;</td>
			</tr>
			
			<?
			$sql = "SELECT	distinct tbl_pedido_item.pedido_item                                     ,
							tbl_pedido_item.qtde                                            ,
							tbl_pedido.pedido                                               ,
							tbl_peca.referencia                                             ,
							tbl_peca.descricao
					FROM    tbl_pedido_item
					JOIN    tbl_pedido           ON tbl_pedido.pedido              = tbl_pedido_item.pedido
					JOIN    tbl_peca             ON tbl_peca.peca                  = tbl_pedido_item.peca
					JOIN    tbl_pedido_item_nf   ON tbl_pedido_item_nf.pedido_item = tbl_pedido_item.pedido_item
					WHERE   tbl_pedido_item.pedido = $pedido
					ORDER BY tbl_pedido_item.pedido_item;";
			$res = pg_exec ($con,$sql);
			$total_pedido = 0 ;
		
			$cor = '#F1F4FA';
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$pedido_item     = pg_result($res,$i,pedido_item);
				$pedido          = pg_result($res,$i,pedido);
				$peca            = pg_result($res,$i,referencia) . " - " . pg_result($res,$i,descricao);
				$qtde_pedida     = pg_result($res,$i,qtde);

				if ($pedido_item_ant <> $pedido_item and $cor=="#FFFFFF") 
					$cor = '#F1F4FA';
				elseif ($pedido_item_ant <> $pedido_item and $cor=="#F1F4FA") 
					$cor = '#FFFFFF';

				$sqli = "SELECT	tbl_pedido_item_nf.pedido_item_nf                               ,
								tbl_pedido_item_nf.qtde_nf                                      ,
								tbl_pedido_item_nf.nota_fiscal                                  ,
								tbl_pedido_item_nf.data_nf                           as data    ,
								to_char(tbl_pedido_item_nf.data_nf, 'DD/MM/YYYY') as data_nf    ,
								(tbl_pedido_item.qtde - tbl_pedido_item_nf.qtde_nf) as qtd_saldo
						FROM    tbl_pedido_item
						JOIN    tbl_pedido           ON tbl_pedido.pedido              = tbl_pedido_item.pedido
						JOIN    tbl_peca             ON tbl_peca.peca                  = tbl_pedido_item.peca
						LEFT JOIN tbl_pedido_item_nf ON tbl_pedido_item_nf.pedido_item = tbl_pedido_item.pedido_item
						WHERE   tbl_pedido_item.pedido = $pedido
						AND     tbl_pedido_item.pedido_item = $pedido_item
						AND     tbl_pedido_item_nf.qtde_nf > 0
						ORDER BY tbl_pedido_item.pedido_item, data;";
				$resi = pg_exec ($con,$sqli);

				echo "<tr bgcolor=".$cor.">";
					echo "<td class='table_line'>$peca</td>";
					echo "<td class='table_line' align='center'>$qtde_pedida</td>";
					echo "<td class='table_line' align='center'>";
						for ($x=0; $x < pg_numrows($resi); $x++) {
							echo "<INPUT TYPE='text' value='".pg_result($resi,$x,qtde_nf)."' size='3' readonly disabled>";
							if ($x < pg_numrows($resi)-1) echo "<BR>";
						}
					echo "</td>";
					echo "<td class='table_line' align='center'>";
						$saldo_total = $qtde_pedida;
						for ($x=0; $x < pg_numrows($resi); $x++) {
							$saldo_total -= pg_result($resi,$x,qtde_nf);
						}
						echo $saldo_total;
					echo "</td>";
					echo "<td class='table_line' align='center'>";
						for ($x=0; $x < pg_numrows($resi); $x++) {
							echo "<INPUT TYPE='text' value='".pg_result($resi,$x,nota_fiscal)."' size='10' readonly disabled>";
							if ($x < pg_numrows($resi)-1) echo "<BR>";
						}
					echo "</td>";
					echo "<td class='table_line' align='center'>";
						for ($x=0; $x < pg_numrows($resi); $x++) {
							echo "<INPUT TYPE='text' value='".pg_result($resi,$x,data_nf)."' size='12' readonly disabled>";
							if ($x < pg_numrows($resi)-1) echo "<BR>";
						}
					echo "</td>";
					echo "<td class='table_line' align='center'>";
						for ($x=0; $x < pg_numrows($resi); $x++) {
							echo "<a href=\"javascript: if (confirm('Deseja realmente excluir este atendimento ? ".'\r\n'."Peça: ".$peca.'\r\n'."NF: ".pg_result($resi,$x,nota_fiscal).", Emissão: ".pg_result($resi,$x,data_nf)."') == true) { window.location='$PHP_SELF?pedido=".$pedido."&pedido_item_nf_excluir=".pg_result($resi,$x,pedido_item_nf)."'; }\"><img id='excluir_".$i."' border='0' src='imagens/btn_excluir.gif'></a>";
							if ($x < pg_numrows($resi)-1) echo "<BR>";
						}
					echo "</td>";
				echo "</tr>";

				$pedido_item_ant = $pedido_item;
			}
			?>
			</table>

		<? } ?>
	</td>
</tr>

<input type="hidden" name="btn_acao" value="">

</form>

</table>

<p>

<? include "rodape.php"; ?>
