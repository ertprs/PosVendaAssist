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

if (pg_result($res,0,distribuidor) == 't' and $login_fabrica == 3) {
	header("Location: pedido_posto_faturamento_new.php?pedido=$pedido");
	exit;
}

if ($btn_acao == "gravar") {

	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$pedido      = $_POST['pedido'];
	$tipo        = $_POST['tipo'];
	$total_pecas = $_POST['total_pecas'];

	$atendido_total            = 't';
	$aux_pedido_atendido_total = 't';
	
	for ($i = 0; $i < $total_pecas; $i++){

//		if ($ip == '192.168.0.55') echo "[valor de I: $i ] <br>";

		if ($tipo == "OS") {
			$os_item_nf  = $_POST['os_item_nf_' .$i];
			$os_item     = $_POST['os_item_'    .$i];
			$qtde_pedida = $_POST['qtde_pedida_'.$i];
			$qtde_nf     = $_POST['qtde_nf_'    .$i];
			$nota_fiscal = $_POST['nota_fiscal_'.$i];
			$data_nf     = $_POST['data_nf_'    .$i];

			if (strlen($qtde_pedida) == 0)	
				$aux_qtde_pedida = 'null';
			else
				$aux_qtde_pedida = "'".trim($qtde_pedida)."'";
			
			if (strlen($qtde_nf) == 0)	
				$aux_qtde_nf = 'null';
			else
				$aux_qtde_nf = "'".trim($qtde_nf)."'";
			
			if (strlen($nota_fiscal) == 0)
				$aux_nota_fiscal = 'null';
			else
				$aux_nota_fiscal = "'".trim($nota_fiscal)."'";
			
			if (strlen($data_nf) == 0)
				$aux_data_nf = 'null';
			else
				$aux_data_nf = "'".formata_data($data_nf)."'";
			
			if (strlen($os_item_nf) > 0 AND $aux_qtde_nf == "null"){
				$sql = "DELETE FROM tbl_os_item_nf WHERE os_item_nf = $os_item_nf";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen($aux_qtde_nf) > 0 AND $aux_qtde_nf <> 'null' AND strlen($msg_erro) == 0) {
				if (strlen($os_item_nf) > 0 ){
					#-------------- insere pedido ------------
					$sql = "UPDATE tbl_os_item_nf SET
								os_item     = $os_item        ,
								qtde_nf     = $aux_qtde_nf    ,
								nota_fiscal = $aux_nota_fiscal,
								data_nf     = $aux_data_nf
							WHERE os_item_nf = $os_item_nf";
				}else{
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
				}
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				
				if (strlen ($msg_erro) == 0) {
					if ($qtde_pedida > $qtde_nf){
						$aux_pedido_atendido_total = 'f';
					}
/*
					else{
						break;
					}
*/
				}
			}else{
				$atendido_total = 'f';
			}
		}else{
			$pedido_item_nf  = $_POST['pedido_item_nf_'.$i];
			$pedido_item     = $_POST['pedido_item_'.$i];
			$qtde_pedida     = $_POST['qtde_pedida_'.$i];
			$qtde_nf         = $_POST['qtde_nf_'.$i];
			$nota_fiscal     = $_POST['nota_fiscal_'.$i];
			$data_nf         = $_POST['data_nf_'.$i];
			
			if (strlen($qtde_pedida) == 0)	
				$aux_qtde_pedida = 'null';
			else
				$aux_qtde_pedida = "'".trim($qtde_pedida)."'";
			
			if (strlen($qtde_nf) == 0)
				$aux_qtde_nf = 'null';
			else
				$aux_qtde_nf = "'".trim($qtde_nf)."'";
			
			if (strlen($nota_fiscal) == 0)
				$aux_nota_fiscal = 'null';
			else
				$aux_nota_fiscal = "'".trim($nota_fiscal)."'";
			
			if (strlen($data_nf) == 0)
				$aux_data_nf = 'null';
			else
				$aux_data_nf = "'".formata_data($data_nf)."'";
			
			if (strlen($pedido_item_nf) > 0 AND $aux_qtde_nf == "null"){
				$sql = "DELETE FROM tbl_pedido_item_nf WHERE pedido_item_nf = $pedido_item_nf";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
			
			if (strlen($aux_qtde_nf) > 0 AND $aux_qtde_nf <> 'null' AND strlen($msg_erro) == 0) {
				if (strlen($pedido_item_nf) > 0 ){
					#-------------- insere pedido ------------
					$sql = "UPDATE tbl_pedido_item_nf SET
								pedido_item      = $pedido_item    ,
								qtde_nf          = $aux_qtde_nf    ,
								nota_fiscal      = $aux_nota_fiscal,
								data_nf          = $aux_data_nf
							WHERE pedido_item_nf = $pedido_item_nf";
				}else{
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
				}
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				
				if (strlen ($msg_erro) == 0) {
					if ($qtde_pedida > $qtde_nf){
						$aux_pedido_atendido_total = 'f';
					}
/*
					else{
						break;
					}
*/
				}
			}else{
				$atendido_total = 'f';
			}
		}
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
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: pedido_posto_relacao.php");
		exit;
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
//		$pedido_cliente   = trim(pg_result ($res,0,pedido_cliente));
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
<!--
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Pedido Distribuidor</b>
				<br>
				<input type='text' name='pedido_distribuidor' value='<? echo $pedido_distribuidor; ?>'>
				</font>
			</td>
-->
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
			<td class='menu_top'>OS</td>
			<td class='menu_top'>Peça</td>
			<td class='menu_top'>Qt. Pedida</td>
			<td class='menu_top'>Qt. Atendida</td>
			<td class='menu_top'>Qt. Saldo</td>
			<td class='menu_top'>NF</td>
			<td class='menu_top'>Dt. Emissão</td>
<!-- 			<td class='menu_top'>Status</td> -->
		</tr>
		
<?
		$sql = "SELECT	tbl_os_item.os_item                                     ,
						tbl_os_item.qtde                                        ,
						tbl_os.os                                               ,
						tbl_os.sua_os                                           ,
						tbl_peca.referencia                                     ,
						tbl_peca.descricao                                      ,
						tbl_os_item_nf.os_item_nf                               ,
						tbl_os_item_nf.qtde_nf                                  ,
						tbl_os_item_nf.nota_fiscal                              ,
						to_char(tbl_os_item_nf.data_nf, 'DD/MM/YYYY') as data_nf
				FROM	tbl_os_item
				JOIN	tbl_os_produto   ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN	tbl_os           ON tbl_os.os                 = tbl_os_produto.os
				JOIN    tbl_peca         ON tbl_peca.peca             = tbl_os_item.peca
				LEFT JOIN tbl_os_item_nf ON tbl_os_item_nf.os_item    = tbl_os_item.os_item
				WHERE	tbl_os_item.pedido = $pedido
				ORDER BY tbl_os_item.os_item";
		$res = pg_exec ($con,$sql);
		$total_pedido = 0 ;
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			
			$os_item_nf  = pg_result($res,$i,os_item_nf);
			$os_item     = pg_result($res,$i,os_item);
			$os          = pg_result($res,$i,os);
			$sua_os      = pg_result($res,$i,sua_os);
			$peca        = pg_result($res,$i,referencia) . " - " . pg_result($res,$i,descricao);
			$qtde_pedida = pg_result($res,$i,qtde);
			$qtde_nf     = pg_result($res,$i,qtde_nf);
			$nota_fiscal = pg_result($res,$i,nota_fiscal);
			$data_nf     = pg_result($res,$i,data_nf);
			$qtde_saldo  = $qtde_pedida - $qtde_nf;
?>
		<tr bgcolor="<? echo $cor ?>" >
			<td class='table_line'><a href='os_press.php?os=<? echo $os; ?>' target='_blank'><? echo $sua_os ?></a></td>
			<td class='table_line'><? echo $peca ?></td>
			<td class='table_line' align='center'>
				<? echo $qtde_pedida ?>
				<input type='hidden' name='qtde_pedida_<? echo $i; ?>' value='<? echo $qtde_pedida; ?>'>
				<input type='hidden' name='os_item_<? echo $i; ?>' value='<? echo $os_item; ?>'>
				<input type='hidden' name='os_item_nf_<? echo $i; ?>' value='<? echo $os_item_nf; ?>'>
			</td>
			<td class='table_line' align='center'><INPUT TYPE="text" NAME="qtde_nf_<? echo $i; ?>" value='<? echo $qtde_nf ?>' size='3'></td>
			<td class='table_line' align='center'><? echo $qtde_saldo ?></td>
			<td class='table_line' align='center'><INPUT TYPE="text" NAME="nota_fiscal_<? echo $i; ?>" value='<? echo $nota_fiscal ?>' size='10'></td>
			<td class='table_line' align='center'><INPUT TYPE="text" NAME="data_nf_<? echo $i; ?>" value='<? echo $data_nf ?>' size='12' maxlength=10></td>
<!-- 			<td class='table_line' align='center'><? echo $status ?></td> -->
		</tr>
		<?
		}
		echo "<input type='hidden' name='total_pecas' value='$i'>";
		echo "<input type='hidden' name='tipo' value='OS'>";
		?>
		</table>
		
		<? }else{ ?>
		
		<table width="100%" border="0" cellspacing="1" cellpadding="2" align='center'>
		<tr>
			<td class='menu_top'>Peça</td>
			<td class='menu_top'>Qt. Pedida</td>
			<td class='menu_top'>Qt. Atendida</td>
			<td class='menu_top'>Qt. Saldo</td>
			<td class='menu_top'>NF</td>
			<td class='menu_top'>Dt. Emissão</td>
<!-- 			<td class='menu_top'>Status</td> -->
		</tr>
		
<?
		$sql = "SELECT	tbl_pedido_item.pedido_item                              ,
						tbl_pedido_item.qtde                                     ,
						tbl_pedido.pedido                                        ,
						tbl_peca.referencia                                      ,
						tbl_peca.descricao                                       ,
						tbl_pedido_item_nf.pedido_item_nf                        ,
						tbl_pedido_item_nf.qtde_nf                               ,
						tbl_pedido_item_nf.nota_fiscal                           ,
						to_char(tbl_pedido_item_nf.data_nf, 'DD/MM/YYYY') as data_nf
				FROM    tbl_pedido_item
				JOIN    tbl_pedido           ON tbl_pedido.pedido              = tbl_pedido_item.pedido
				JOIN    tbl_peca             ON tbl_peca.peca                  = tbl_pedido_item.peca
				LEFT JOIN tbl_pedido_item_nf ON tbl_pedido_item_nf.pedido_item = tbl_pedido_item.pedido_item
				WHERE   tbl_pedido_item.pedido = $pedido
				ORDER BY tbl_pedido_item.pedido_item;";
#echo $sql;
		$res = pg_exec ($con,$sql);
		$total_pedido = 0 ;
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			
			$pedido_item_nf  = pg_result($res,$i,pedido_item_nf);
			$pedido_item     = pg_result($res,$i,pedido_item);
			$pedido          = pg_result($res,$i,pedido);
			$peca            = pg_result($res,$i,referencia) . " - " . pg_result($res,$i,descricao);
			$qtde_pedida     = pg_result($res,$i,qtde);
			$qtde_nf         = pg_result($res,$i,qtde_nf);
			$nota_fiscal     = pg_result($res,$i,nota_fiscal);
			$data_nf         = pg_result($res,$i,data_nf);
			$qtde_saldo  = $qtde_pedida - $qtde_nf;
?>
		<tr bgcolor="<? echo $cor ?>" >
			<td class='table_line'><? echo $peca ?></td>
			<td class='table_line' align='center'>
				<? echo $qtde_pedida ?>
				<input type='hidden' name='qtde_pedida_<? echo $i; ?>' value='<? echo $qtde_pedida; ?>'>
				<input type='hidden' name='pedido_item_<? echo $i; ?>' value='<? echo $pedido_item; ?>'>
				<input type='hidden' name='pedido_item_nf_<? echo $i; ?>' value='<? echo $pedido_item_nf; ?>'>
			</td>
			<td class='table_line' align='center'><INPUT TYPE="text" NAME="qtde_nf_<? echo $i; ?>" value='<? echo $qtde_nf ?>' size='3'></td>
			<td class='table_line' align='center'><? echo $qtde_saldo ?></td>
			<td class='table_line' align='center'><INPUT TYPE="text" NAME="nota_fiscal_<? echo $i; ?>" value='<? echo $nota_fiscal ?>' size='10'></td>
			<td class='table_line' align='center'><INPUT TYPE="text" NAME="data_nf_<? echo $i; ?>" value='<? echo $data_nf ?>' size='12'></td>
<!-- 			<td class='table_line' align='center'><? echo $status ?></td> -->
		</tr>
		<?
		}
		echo "<input type='hidden' name='total_pecas' value='$i'>";
		echo "<input type='hidden' name='tipo' value='PEDIDO'>";
		?>
		</table>
		
		<? } ?>
	</td>
</tr>

<tr>
	<td height="27" valign="middle" align="center">
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'>
		&nbsp;&nbsp;
		<img src='imagens/btn_voltar.gif' onclick="javascript: history.back();" ALT="Volta" border='0' style='cursor: pointer'>
	</td>
</tr>

</form>

</table>

<p>

<? include "rodape.php"; ?>
