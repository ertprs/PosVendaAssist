<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if(strlen($_POST['sedex'])>0)    $sedex    = $_POST['sedex'];    else $sedex    = $_GET['sedex'];
if(strlen($_POST['key'])>0)      $key      = $_POST['key'];      else $key      = $_GET['key'];
if(strlen($_POST['garantia'])>0) $garantia = $_POST['garantia']; else $garantia = $_GET['garantia'];
if(strlen($_POST['pedido'])>0)   $pedido   = $_POST['pedido'];   else $pedido   = $_GET['pedido'];

if (strlen ($sedex) > 0 AND $login_admin=='232' ) {
	$sqlS = "UPDATE tbl_pedido SET
			pedido_sedex = 't'   ,
			admin = 232
			WHERE pedido = $sedex";
	//echo $sql;exit;
	$resS = pg_exec ($con,$sqlS);
	$pedido = $sedex;
}

if (strlen ($sedex) > 0 AND $login_admin=='112' ) {
	$sqlS = "UPDATE tbl_pedido SET
			pedido_sedex = 't'   ,
			admin = 112
			WHERE pedido = $sedex";
	//echo $sql;exit;
	$resS = pg_exec ($con,$sqlS);
	$pedido = $sedex;
}

if(strlen($pedido) > 0 AND $login_fabrica == 24){ 
	$sql="SELECT  sum(qtde) AS qtde,
				  sum(qtde_cancelada) AS qtde_cancelada
			FROM  tbl_pedido
			JOIN  tbl_pedido_item USING(pedido)
			WHERE tbl_pedido.pedido=$pedido
			AND   tbl_pedido.status_pedido <> 14";
	$res=pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
		$qtde           = pg_result($res,0,qtde);
		$qtde_cancelada = pg_result($res,0,qtde_cancelada);
		if($qtde == $qtde_cancelada){
			$sql2="UPDATE tbl_pedido SET status_pedido=14
					WHERE pedido = $pedido";
			$res2=pg_exec($con,$sql2);
		}
	}
}

if (strlen($_GET["cancelar"])>0 AND strlen($_GET["pedido"])>0) {

	if(strlen($motivo)==0) $msg_erro = "Por favor informe o motivo de cancelamento da peça: $referencia - $qtde";
	else                   $aux_motivo = "'$motivo'";
	//Cancela todo o pedido quando ele é distribuidor
	if($cancelar=="todo"){
		$sql = "SELECT  PE.pedido      ,
				PE.distribuidor,
				PI.pedido_item ,
				PI.peca        ,
				PI.qtde        ,
				OP.os
			FROM   tbl_pedido        PE
			JOIN   tbl_pedido_item   PI ON PI.pedido     = PE.pedido
			LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
			LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
			WHERE PE.pedido  = $pedido
			AND   PE.fabrica = $login_fabrica
			AND   PI.qtde > PI.qtde_cancelada ";

		if( ($login_admin==586 OR $login_admin==396)and $login_fabrica==3 ) {
			$sql = "SELECT  PE.pedido      ,
					PE.distribuidor,
					PI.pedido_item ,
					PI.peca        ,
					PI.qtde        ,
					OP.os
				FROM   tbl_pedido        PE
				JOIN   tbl_pedido_item   PI ON PI.pedido     = PE.pedido
				LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
				LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
				WHERE PE.pedido  = $pedido
				AND   PE.fabrica = $login_fabrica
				AND   PI.qtde > PI.qtde_cancelada + qtde_faturada_distribuidor ";
		}
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			for($i==0;$i<pg_numrows($res);$i++){
				$peca         = pg_result ($res,$i,peca);
				$qtde         = pg_result ($res,$i,qtde);
				$os           = pg_result ($res,$i,os);
				$distribuidor = pg_result ($res,$i,distribuidor);
				if(strlen($distribuidor)>0){
					$sql  = "SELECT fn_pedido_cancela($distribuidor,$login_fabrica,$pedido,$peca,$aux_motivo,$login_admin)";
					$resY = @pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
		}
	}else{//Cancela uma peça do pedido
		$sql = "SELECT  PI.pedido_item,
				PI.qtde      ,
				PC.peca      ,
				PC.referencia,
				PC.descricao ,
				OP.os        ,
				PE.posto     ,
				PE.distribuidor
			FROM    tbl_pedido       PE
			JOIN    tbl_pedido_item  PI ON PI.pedido     = PE.pedido
			JOIN    tbl_peca         PC ON PC.peca       = PI.peca
			LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
			LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
			WHERE   PI.pedido      = $pedido
			AND     PI.pedido_item = $cancelar 
			AND     PE.fabrica     = $login_fabrica
			AND     PE.exportado   IS NULL";

		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
	
			$peca         = pg_result ($res,peca);
			$referencia   = pg_result ($res,referencia);
			$descricao    = pg_result ($res,descricao);
			$qtde         = pg_result ($res,qtde);
			$os           = pg_result ($res,os);
			$posto        = pg_result ($res,posto);
			$distribuidor = pg_result ($res,distribuidor);
	
			if(strlen($msg_erro)==0){
				if(strlen($distribuidor)>0){
					$sql  = "SELECT fn_pedido_cancela($distribuidor,$login_fabrica,$pedido,$peca,$aux_motivo,$login_admin)";
					$resY = @pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
		
				}else{
					if(strlen($os)==0) $os ="null";
					//Verifica se já foi faturada 
					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
									tbl_faturamento.faturamento,
									tbl_faturamento.conhecimento
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento.posto        = $posto
							AND     tbl_faturamento_item.pedido  = $pedido 
							AND     tbl_faturamento.pedido       = $pedido 
							AND     tbl_faturamento_item.peca    = $peca;";
			
					$resY = pg_exec ($con,$sql);
					if (pg_numrows ($resY) > 0) {
						$msg_erro  .= "A peça $referencia - $descricao do pedido $pedido já está faturada com a nota fiscal". pg_result ($resY,nota_fiscal);
					}else{
						$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde WHERE pedido_item = $cancelar;";
						$res = pg_exec ($con,$sql);
						$sql = "INSERT INTO tbl_pedido_cancelado (pedido,posto,fabrica,os,peca,qtde,motivo,data
							)VALUES(
								$pedido,
								$posto,
								$login_fabrica,
								$os,
								$peca,
								$qtde,
								$aux_motivo,
								current_date
								
							);";
						$res = @pg_exec ($con,$sql);
					}
				}
			}
		}else $msg_erro .= "Pedido já exportado, não é possível excluir peças";
	}
	if(strlen($msg_erro)==0) {
		header ("Location: $PHP_SELF?pedido=$pedido");
		exit;
	}
}


#------------ Le Pedido da Base de dados ------------#
//HD 11871 Paulo 
if($login_fabrica==24){
	$sql_admin_select=" ,admin_alteracao.login      AS login_alteracao              ";
	$sql_admin_join  =" LEFT JOIN tbl_admin as admin_alteracao ON tbl_pedido.admin_alteracao            = admin_alteracao.admin ";
}

if (strlen ($pedido) > 0) {
	$sql = "SELECT  tbl_pedido.pedido                                                     ,
			tbl_pedido.posto                                                              ,
			tbl_admin.login                                                               ,
			case 
				when tbl_pedido.pedido_blackedecker > 499999 then
					lpad ((tbl_pedido.pedido_blackedecker-500000)::text,5,'0')
				when tbl_pedido.pedido_blackedecker > 399999 then
					lpad ((tbl_pedido.pedido_blackedecker-400000)::text,5,'0')
				when tbl_pedido.pedido_blackedecker > 299999 then
					lpad ((tbl_pedido.pedido_blackedecker-300000)::text,5,'0')
				when tbl_pedido.pedido_blackedecker > 199999 then
					lpad ((tbl_pedido.pedido_blackedecker-200000)::text,5,'0')
				when tbl_pedido.pedido_blackedecker > 99999 then
					lpad ((tbl_pedido.pedido_blackedecker-100000)::text,5,'0')
			else
				lpad ((tbl_pedido.pedido_blackedecker)::text,5,'0')
			end                                          AS pedido_blackedecker,
			tbl_pedido.condicao                                                           ,
			tbl_pedido.tabela                                                             ,
			tbl_pedido.pedido_cliente                                                     ,
			tbl_pedido.pedido_acessorio                                                   ,
			tbl_pedido.pedido_sedex                                                       ,
			tbl_pedido.status_pedido                                                      ,
			tbl_pedido.distribuidor                                                       ,
			to_char(tbl_pedido.data,'DD/MM/YYYY HH24:MI:SS')       AS data_pedido         ,
			to_char(tbl_pedido.finalizado,'DD/MM/YYYY HH24:MI:SS') AS data_finalizado     ,
			to_char(tbl_pedido.exportado,'DD/MM/YYYY HH24:MI:SS')  AS data_exportado      ,
			to_char(tbl_pedido.recebido_posto,'DD/MM/YYYY')        AS recebido_posto      ,
			tbl_pedido.tipo_pedido            AS tipo_pedido                              ,
			tbl_tipo_pedido.descricao         AS tipo_descricao                           ,
			COALESCE(tbl_pedido.desconto, 0)  AS pedido_desconto                          ,
			tbl_condicao.descricao                      AS condicao_descricao             ,
			tbl_tabela.tabela                                                             ,
			tbl_tabela.descricao                        AS tabela_descricao               ,
			tbl_posto_fabrica.codigo_posto                                                ,
			tbl_posto.nome                              AS nome_posto                     
			$sql_admin_select
		FROM    tbl_pedido
		JOIN    tbl_posto                      ON tbl_posto.posto             = tbl_pedido.posto
		LEFT JOIN tbl_posto_fabrica            ON tbl_posto_fabrica.posto     = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN tbl_condicao                 ON tbl_condicao.condicao       = tbl_pedido.condicao
		LEFT JOIN tbl_tipo_pedido              ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
		LEFT JOIN tbl_tabela                   ON tbl_tabela.tabela           = tbl_pedido.tabela
		LEFT JOIN tbl_admin                    ON tbl_pedido.admin            = tbl_admin.admin 
		$sql_admin_join
		WHERE   tbl_pedido.pedido  = $pedido
		AND     tbl_pedido.fabrica = $login_fabrica;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$pedido              = trim(pg_result ($res,0,pedido));
		$pedido_condicao     = trim(pg_result ($res,0,condicao));
		$condicao            = trim(pg_result ($res,0,condicao_descricao));
		$tabela              = trim(pg_result ($res,0,tabela));
		$tabela_descricao    = trim(pg_result ($res,0,tabela_descricao));
		$pedido_cliente      = trim(pg_result ($res,0,pedido_cliente));
		$pedido_acessorio    = trim(pg_result ($res,0,pedido_acessorio));
		$pedido_sedex        = trim(pg_result ($res,0,pedido_sedex));
		$data_pedido         = trim(pg_result ($res,0,data_pedido));
		$data_finalizado     = trim(pg_result ($res,0,data_finalizado));
		$data_exportado      = trim(pg_result ($res,0,data_exportado));
		$posto               = trim(pg_result ($res,0,posto));
		$codigo_posto        = trim(pg_result ($res,0,codigo_posto));
		$nome_posto          = trim(pg_result ($res,0,nome_posto));
		$pedido_blackedecker = trim(pg_result ($res,0,pedido_blackedecker));
		$login               = trim(pg_result ($res,0,login));
		$data_recebido       = trim(pg_result ($res,0,recebido_posto));
		$tipo_pedido_id      = trim(pg_result ($res,0,tipo_pedido));
		$tipo_pedido         = trim(pg_result ($res,0,tipo_descricao));
		$pedido_desconto     = trim(pg_result ($res,0,pedido_desconto));
		$status_pedido       = trim(pg_result ($res,0,status_pedido));
		$distribuidor        = trim(pg_result ($res,0,distribuidor));
		//HD 11871 Paulo 
		if($login_fabrica==24){
			$login_alteracao     = trim(pg_result ($res,0,login_alteracao));
		}
		if (strlen ($login) == 0) $login = "Posto";
		
		if ($condicao == "Garantia") $detalhar = "ok";
		$detalhar = "ok";

		if ($login_fabrica == 1 AND $pedido_acessorio == "t") $pedido_blackedecker = intval($pedido_blackedecker + 1000);
	}
}


$title = "CONFIRMAÇÃO DE PEDIDO DE PEÇAS";
if ($login_fabrica == 1){
	$title = "CONFIRMAÇÃO DE PEDIDO DE PEÇAS / PRODUTOS";
}
$layout_menu = 'pedido';

include "cabecalho.php";
?>
<style>
.Tabela{
	font-family: Verdana,Sans;
	font-size: 10px;
}
.Tabela thead{
	font-size: 12px;
	font-weight:bold;
}
</style>
<!------------------AQUI COMEÇA O SUB MENU ---------------------!-->
<? echo "<font color=red>$msg_erro</font>";?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="center">
		<table width="700" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Atenção:</b>
				Pedidos a prazo dependerão de análise do departamento de crédito.
				</font>
			</td>
		</tr>
		</table>

		<table width="700" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Pedido</b>
				<br>
				<?
				if ($login_fabrica == 1) {
					if($pedido_acessorio == 't') {$pedido_blackedecker = $pedido_blackedecker - 1000 ; echo $pedido_blackedecker . " ." ; 
					}else{
						echo $pedido_blackedecker;
					}
				}else{
					echo $pedido;
				}
				?>
				</font>
			</td>
			
			<? if (strlen($pedido_cliente) > 0) { ?>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Pedido Cliente</b>
				<br>
				<?echo $pedido_cliente?>
				</font>
			</td>
			<? } ?>
			
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Condição Pagamento</b>
				<br>
				<?echo $condicao?>
				</font>
			</td>
			
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Tabela de Preços</b>
				<br>
				<?echo $tabela_descricao?>
				</font>
			</td>

			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Responsável</b>
				<br>
				<?echo strtoupper ($login) ?>
				</font>
			</td>
<? //HD 11871 Paulo
if ($login_fabrica==24 and strlen($login_alteracao) > 0){?>

			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Alterado Por</b>
				<br>
				<?echo strtoupper ($login_alteracao) ?>
				</font>
			</td>

<?}?>
		</tr>
		</table>

		<table width="700" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Posto</b>
				<br>
				<?echo $codigo_posto?>
				</font>
			</td>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Razão Social</b>
				<br>
				<?echo $nome_posto?>
				</font>
			</td>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Data</b>
				<br>
				<?echo $data_pedido?>
				&nbsp;
				</font>
			</td>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Finalizado</b>
				<br>
				<?echo $data_finalizado?>
				&nbsp;
				</font>
			</td>
<?if ($login_fabrica == 24) {?>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Recebido Posto</b>
				<br>
				<?echo $data_recebido?>
				&nbsp;
				</font>
			</td>
<?}?>
		</tr>
		</table>

		<table width="700" border="0" cellspacing="1" cellpadding="2" align='center' class='Tabela'>
		<thead>
		<tr height="20" bgcolor="#C0C0C0">
			<?if ($login_fabrica == 1) {?>
			<td>SEQ</td>
			<?}?>
			<td>Componente</td>
			<td align='center'>Qtde</td>
			<td align='center'>IPI</td>
			<td align='center'>Preço</td>
			<?if ($login_fabrica == 1) {?>
			<td>Total s/ IPI</td>
			<?}?>
			<td align='center'>Total c/ IPI</td>
			<?if ($login_fabrica == 3 AND $distribuidor = 4311 AND $condicao<>'Garantia' AND ($login_admin==586 OR $login_admin==396)) {?>
			<td>Ação</td>
			<?}?>

		</tr>
		</thead>
		<?
		$sql = "SELECT  tbl_pedido_item.pedido_item,
				tbl_pedido_item.peca ,
				tbl_pedido_item.preco,
				case when $login_fabrica = 14 then rpad ((tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)))::text,7,'0')::float else tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)) end as total,
				tbl_peca.referencia  ,
				tbl_peca.descricao   ,
				tbl_peca.ipi         ,
				tbl_pedido_item.qtde ,
				tbl_pedido_item.obs  
			FROM  tbl_pedido
			JOIN  tbl_pedido_item USING (pedido)
			JOIN  tbl_peca        USING (peca)
			WHERE tbl_pedido_item.pedido = $pedido
			AND   tbl_pedido.fabrica     = $login_fabrica
			ORDER BY tbl_peca.descricao;";
		$res = pg_exec ($con,$sql);
		$total_pedido = 0 ;
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$seq = $i+1;
			
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';

			$pedido_item     = pg_result ($res,$i,pedido_item);
			$peca            = pg_result ($res,$i,peca);
			$peca_descricao  = pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao);
			$qtde            = pg_result ($res,$i,qtde);
			$ipi             = trim(pg_result ($res,$i,ipi));
			$obs_pedido_item = trim(pg_result ($res,$i,obs));

			if ($login_fabrica <> 14 and $login_fabrica<>24) {
				if ($login_fabrica <> 1 and $login_fabrica <> 7 and $login_fabrica <> 10 and $login_fabrica <> 3) {
					$sql  = "SELECT tbl_tabela_item.preco
							FROM    tbl_tabela_item
							WHERE   tbl_tabela_item.tabela = $tabela
							AND     tbl_tabela_item.peca   = $peca;";
				}else{
					$sql  = "SELECT tbl_pedido_item.preco
							FROM    tbl_pedido_item
							WHERE   tbl_pedido_item.pedido = $pedido
							AND     tbl_pedido_item.peca   = $peca;";
				}
				$resT = pg_exec ($con,$sql);
				
				if (pg_numrows ($resT) > 0) {
					// unitario sem ipi
					$preco_unit = pg_result ($resT,0,0);
					// total s/ ipi
					$preco_sem_ipi = $preco_unit * $qtde;
					// total pecas c/ ipi
					$total         = $preco_sem_ipi + ($preco_sem_ipi * $ipi / 100);
					$total_sem_ipi = $preco_sem_ipi;
					// total acumulado do pedido
					$total_pedido += $total;
					$total_pedido_sem_ipi += $total_sem_ipi;
					
					$preco_unit    = number_format ($preco_unit,2,",",".");
					$total         = number_format ($total,2,",",".");
					$total_sem_ipi = number_format ($total_sem_ipi,2,",",".");
				}else{
					$preco      = "***";
					$total      = "***";
					$preco_unit = "***";
				}
			}else{
				// unitario sem ipi
				$preco_unit    = trim(pg_result ($res,$i,preco));
				$total         = trim(pg_result ($res,$i,total));
				
				// total s/ ipi
				$preco_sem_ipi = $preco_unit * $qtde;
				
				// total pecas c/ ipi
				$total_sem_ipi = $preco_sem_ipi;
				
				$sql = "SELECT  case when $login_fabrica = 14 then 
									rpad (sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)))::text,7,'0')::float 
								else 
									sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) 
								end as total_pedido
						FROM  tbl_pedido
						JOIN  tbl_pedido_item USING (pedido)
						JOIN  tbl_peca        USING (peca)
						WHERE tbl_pedido_item.pedido = $pedido
						GROUP BY tbl_pedido.pedido";
				$resz = pg_exec ($con,$sql);
				
				if (pg_numrows($resz) > 0) $total_pedido  = trim(pg_result ($resz,0,total_pedido));
				
				$total_pedido_sem_ipi += $total_sem_ipi;
				
				$preco_unit    = str_replace (".",",",$preco_unit);
				$total         = str_replace (".",",",$total);
				$total_sem_ipi = str_replace (".",",",$total_sem_ipi);
			}
		?>
		<tr bgcolor="<? echo $cor ?>" >
			<?if ($login_fabrica == 1) {?>
			<td align='center'><? echo $seq ?></td>
			<?}?>
			<td align='left'><? echo $peca_descricao ?></td>
			<td align='right'><? echo $qtde ?></td>
			<td align='right'><? echo $ipi."%"; ?></td>
			<td align='right'><? echo $preco_unit ?></td>
			<?if ($login_fabrica == 1) {?>
			<td align='center'><? echo $total_sem_ipi ?></td>
			<?}?>
			<td align='right'><? echo $total ?></td>
			<?//
			if ($login_fabrica == 3 AND $distribuidor == 4311 AND $condicao<>'Garantia' AND ($login_admin==586 OR $login_admin==396)) {
				$sql  = "SELECT * FROM tbl_pedido_cancelado WHERE pedido=$pedido AND peca=$peca";
				$resY = pg_exec ($con,$sql);
				if (pg_numrows ($resY) > 0) {
					echo "<td><acronym title='".pg_result ($resY,0,motivo)."'>Cancelado</acronym></td>" ;
				}else{
					echo "<td><form name='acao_$i'>";
					echo "Motivo: <input type='text' name='motivo' class='frm' size='10'>";
					echo "<a href='javascript: if(confirm(\"Deseja cancelar este item do pedido: $peca_descricao?\")) window.location = \"$PHP_SELF?cancelar=$pedido_item&pedido=$pedido&peca=$peca&motivo=\"+document.acao_$i.motivo.value'>";
					echo " <img src='imagens/btn_x.gif'><font size='1'>Cancelar</font></a>";
					echo "</form></td>";
				}
			}
			?>
		</tr>
		<?
			//HD  8412
			if($login_fabrica==35 and strlen($obs_pedido_item)>0){
				echo "<tr bgcolor='$cor'>";
				echo "<td colspan='100%' align='left'>";
				echo "<font face='Verdana' size='1' color='#000099'>";
				echo "OBS: $obs_pedido_item";
				echo "</font>";
				echo "</td>";
				echo "</tr>";
			}
		}
		?>
	
		<tr>
		<?if ($login_fabrica == 1) {?>
			<td colspan='5' bgcolor='#cccccc' align='center'><b>TOTAL</b></td>
			<td bgcolor='#cccccc' align='right' nowrap><b><? echo number_format ($total_pedido_sem_ipi,2,",","."); ?></b></td>
			<td bgcolor='#cccccc' align='right' nowrap><b><? echo number_format ($total_pedido,2,",","."); ?></b></td>
		<?}else{?>
			
			<? if ($login_fabrica<>11) { ?>
				<td colspan='4' bgcolor='#cccccc' align='center'><b>TOTAL</b></td>
			<? } else { ?>
				<td colspan='4' bgcolor='#cccccc' align='center'><b>SUBTOTAL</b></td>
			<? } ?>
			<? if ($login_fabrica <> 14) { ?>
				<td bgcolor='#cccccc' align='right' nowrap><b><? echo number_format ($total_pedido,2,",",".");?></b></td>
			<?}else{?>
				<td bgcolor='#cccccc' align='right' nowrap><b><? echo str_replace (".",",",$total_pedido); ?></b></td>
			<?}?>

			<? if ($login_fabrica==11 and strtoupper($tipo_pedido)=="VENDA") { 
				echo "<tr>";
				echo "<td colspan='4' bgcolor='#cccccc' align='center'><b>Desconto sobre pedido de venda ($pedido_desconto%)</b></td>";
				echo "<td bgcolor='#cccccc' align='right' nowrap><b>";
				echo str_replace ('.',',',$total_pedido * $pedido_desconto / 100)."</b></td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td colspan='4' bgcolor='#cccccc' align='center'><b>TOTAL</b></td>";
				echo "<td bgcolor='#cccccc' align='right' nowrap><b>";
				$total_geral = $total_pedido - ($total_pedido * $pedido_desconto / 100);
				echo str_replace ('.',',',number_format($total_geral,2,",","."))."</b></td>";
				echo "</tr>";
			} ?>
		<?}
		if ($login_fabrica == 3 AND $distribuidor == 4311 AND $condicao<>'Garantia' AND ($login_admin==586 OR $login_admin==396)) {

			echo "<td bgcolor='#cccccc'><form name='acao_x'>";
			echo "Motivo: <input type='text' name='motivo' class='frm' size='10'>";
			echo "<a href='javascript: if(confirm(\"Deseja cancelar este item do pedido: $peca_descricao?\")) window.location = \"$PHP_SELF?cancelar=todo&pedido=$pedido&motivo=\"+document.acao_x.motivo.value'>";
			echo " <img src='imagens/btn_x.gif'><font size='1'>Cancelar Pedido</font></a>";
			echo "</form></td>";
		}
		?>
		</tr>
		</table>
		
	<?
	if ($detalhar == "ok") {
		echo "<br>";
		

		#Mostar somente para pedidos de OS - Fabrica 1 - HD  14831
		#Nao mostrar as OS do tipo de pedido LOCADOR -  HD 15114
		if ($tipo_pedido_id <> 94 and (strpos(strtoupper($condicao),"GARANTIA") !== false or $login_fabrica<>1) ) {

			if ($login_fabrica <> 11 AND $login_fabrica <> 51 AND $login_fabrica <> 59) {
				$sql = "SELECT  distinct
								lpad(tbl_os.sua_os::text,10,'0'),
								tbl_peca.peca           ,
								tbl_peca.referencia     ,
								tbl_peca.descricao      ,
								tbl_os.os               ,
								tbl_os.sua_os           ,
								tbl_pedido.posto
						FROM    tbl_pedido
						JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido    = tbl_pedido.pedido
						JOIN    tbl_peca        ON  tbl_peca.peca             = tbl_pedido_item.peca
						LEFT JOIN tbl_os_item   ON  tbl_os_item.peca          = tbl_pedido_item.peca
												AND tbl_os_item.pedido        = tbl_pedido.pedido
						LEFT JOIN tbl_os_produto  ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
						LEFT JOIN tbl_os          ON  tbl_os.os                 = tbl_os_produto.os
						WHERE   tbl_pedido_item.pedido = $pedido
						AND     tbl_pedido.fabrica     = $login_fabrica
						ORDER BY tbl_peca.descricao;";


			$sql = "SELECT  distinct
							tbl_pedido_item.pedido_item,
							lpad(tbl_os.sua_os::text,10,'0'),
							tbl_peca.peca      ,
							tbl_peca.referencia,
							tbl_peca.descricao ,
							tbl_os.os          ,
							tbl_os.sua_os      ,
							tbl_os_item_nf.nota_fiscal,
							tbl_pedido.posto
					FROM    tbl_pedido
					JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido     = tbl_pedido.pedido
					JOIN    tbl_peca        ON  tbl_peca.peca              = tbl_pedido_item.peca
					LEFT JOIN tbl_os_item   ON  tbl_os_item.peca           = tbl_pedido_item.peca
											AND tbl_os_item.pedido         = tbl_pedido.pedido
					LEFT JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					LEFT JOIN tbl_os          ON tbl_os.os                 = tbl_os_produto.os
					LEFT JOIN tbl_os_item_nf  ON tbl_os_item.os_item       = tbl_os_item_nf.os_item
					WHERE   tbl_pedido_item.pedido = $pedido
					ORDER BY lpad(tbl_os.sua_os::text,10,'0');";

				$res = pg_exec ($con,$sql);
			} else {
				$sql = "SELECT  DISTINCT
						'' as pedido_item,
						LPAD(tbl_os.sua_os::text,10,'0') as sua_osx,
						tbl_peca.peca           ,
						tbl_peca.referencia     ,
						tbl_peca.descricao      ,
						tbl_os.os               ,
						tbl_os.sua_os           ,
						tbl_pedido.posto        ,
						tbl_os_item.oid
					FROM    tbl_pedido
					JOIN    tbl_pedido_item   ON  tbl_pedido_item.pedido    = tbl_pedido.pedido
					JOIN    tbl_peca          ON  tbl_peca.peca             = tbl_pedido_item.peca
					LEFT JOIN tbl_os_item     ON  tbl_os_item.peca          = tbl_pedido_item.peca AND tbl_os_item.pedido        = tbl_pedido.pedido
					LEFT JOIN tbl_os_produto  ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
					LEFT JOIN tbl_os          ON  tbl_os.os                 = tbl_os_produto.os
					WHERE   tbl_pedido_item.pedido = $pedido
					AND     tbl_pedido.fabrica     = $login_fabrica
					AND     tbl_os.os NOTNULL

					UNION

						SELECT  distinct
								'' as pedido_item,
								lpad(tbl_os.sua_os::text,10,'0') as sua_osx,
								tbl_peca.peca           ,
								tbl_peca.referencia     ,
								tbl_peca.descricao      ,
								tbl_os.os               ,
								tbl_os.sua_os           ,
								tbl_pedido.posto        ,
								tbl_pedido_cancelado.oid
						FROM    tbl_pedido
						JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido    = tbl_pedido.pedido
						JOIN    tbl_peca        ON  tbl_peca.peca             = tbl_pedido_item.peca
						JOIN    tbl_pedido_cancelado ON  tbl_pedido_cancelado.peca = tbl_pedido_item.peca
									AND tbl_pedido_cancelado.pedido    = tbl_pedido_item.pedido
						LEFT JOIN tbl_os ON  tbl_os.os = tbl_pedido_cancelado.os
						WHERE   tbl_pedido_item.pedido = $pedido
						AND     tbl_pedido.fabrica     = $login_fabrica
						AND     tbl_os.os notnull
							
						ORDER BY descricao
						;";

				$res = pg_exec ($con,$sql);
			}

			if (pg_numrows($res) > 0) {
	

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

					$pedido_item      = pg_result ($res,$i,pedido_item);
					$peca             = pg_result ($res,$i,peca);
					$os               = pg_result ($res,$i,os);
					$sua_os           = pg_result ($res,$i,sua_os);
					$peca_descricao   = pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao);
					$posto            = pg_result ($res,$i,posto);
					
					if($i==0) {
						// HD 22962
						echo "<table width='700' cellpadding='2' cellspacing='1'   align='center' class='Tabela' >";
						echo "<thead>";
						if(strlen($os) >0){
							echo "<tr bgcolor='#C0C0C0'>";
							echo "<td align='center' colspan='4'><b>Ordens de Serviço que geraram o pedido acima</b></td>";
							echo "</tr>";
						}
						echo "<tr bgcolor='#C0C0C0'>";
						//if ($condicao == "Garantia") {
							//strpos($condicao,"GARANTIA") !== false or coloquei 11/12/07 hd 9460
						if (strpos($condicao,"GARANTIA") !== false or strpos($condicao,"Garantia") !== false or strpos($condicao,"Livre de débito") !== false or strpos($condicao,"Reposição Antecipada") !== false and $login_fabrica<>24) {
							echo "<td align='center'><b>Sua OS</b></td>";
						}
						if($login_fabrica <> 11) echo "<td align='center'><b>Nota Fiscal</b></td>";
						else                     echo "<td align='center'><b>Situação</b></td>";
						
						if ($login_fabrica == 35) {
							echo "<td align='center'><b>Conhecimento</b></td>";
						}

						echo "<td align='center'><b>Peça</b></td>";
						if($login_fabrica==45) echo "<td align='center'><b>Ação</b></td>";
						echo "</tr>";
						echo "</thead>";
					}

					$cor = "#FFFFFF";
					if ($i % 2 == 0) $cor = '#F1F4FA';

					if($login_fabrica <> 1 ){
						if($login_fabrica==24 or $login_fabrica==35 or $login_fabrica==45) $sql_adicional = "AND tbl_faturamento_item.pedido = $pedido ";
						elseif($login_fabrica==3) $sql_adicional = "AND tbl_faturamento_item.pedido = $pedido ";
						else                                         $sql_adicional = "AND tbl_faturamento.pedido      = $pedido ";

						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
								tbl_faturamento.faturamento                      ,
								tbl_faturamento.conhecimento
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento.posto     = $posto 
							$sql_adicional
							AND     tbl_faturamento_item.peca = $peca;";

						$resx = pg_exec ($con,$sql);

						if (pg_numrows ($resx) > 0) {
							$nf = trim(pg_result($resx,0,nota_fiscal));
							$faturamento = trim(pg_result($resx,0,faturamento));
						//Gustavo 12/12/2007 HD 9590
							if($login_fabrica == 35) $conhecimento   = trim(pg_result($resx,0,conhecimento));
						}else{
							/*HD 20787 Não ver nf do distrib
							$sql = "SELECT tbl_faturamento.faturamento, tbl_faturamento.nota_fiscal , TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
							tbl_faturamento.conhecimento
									FROM tbl_faturamento
									JOIN tbl_faturamento_item USING (faturamento)
									WHERE tbl_faturamento.distribuidor = 4311
									AND   tbl_faturamento_item.pedido = $pedido
									AND   tbl_faturamento_item.peca   = $peca";

							$resY = pg_exec ($con,$sql);
							*/
							if($login_fabroca==3) {
								$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
											tbl_faturamento.faturamento                      ,
											tbl_faturamento.conhecimento
										FROM    tbl_faturamento
										JOIN    tbl_faturamento_item USING (faturamento)
										WHERE   tbl_faturamento.posto     = $posto 
										AND tbl_faturamento.pedido        = $pedido 
										AND     tbl_faturamento_item.peca = $peca;";
								$resY = pg_exec ($con,$sql);
							
								if (pg_numrows ($resY) == 0) {
								
									$sql = "SELECT tbl_faturamento.faturamento, tbl_faturamento.nota_fiscal , TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
									tbl_faturamento.conhecimento
											FROM tbl_faturamento
											JOIN tbl_faturamento_item USING (faturamento)
											WHERE tbl_faturamento.posto = 4311
											AND   tbl_faturamento_item.pedido = $pedido
											AND   tbl_faturamento_item.peca   = $peca";
									$resY = pg_exec ($con,$sql);
								}
							}else{
								$sql = "SELECT tbl_faturamento.faturamento, tbl_faturamento.nota_fiscal , TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
								tbl_faturamento.conhecimento
										FROM tbl_faturamento
										JOIN tbl_faturamento_item USING (faturamento)
										WHERE tbl_faturamento.posto = 4311
										AND   tbl_faturamento_item.pedido = $pedido
										AND   tbl_faturamento_item.peca   = $peca";
								$resY = pg_exec ($con,$sql);
							}

							if (pg_numrows ($resY) > 0) {
								$nf = pg_result ($resY,0,nota_fiscal);
								$faturamento = pg_result ($resY,0,faturamento);
								//Gustavo 12/12/2007 HD 9590
								if($login_fabrica == 35) $conhecimento   = trim(pg_result($resY,0,conhecimento));
							}else{
								$nf = "Pendente";
							}
						}
					}else{
						#HD 13653
						#$nota_fiscal_2    = pg_result ($res,$i,nota_fiscal_2);
						$nf  = pg_result ($res,$i,nota_fiscal);
						#HD 13653
						#if (strlen($nf)==0) $nf= $nota_fiscal_2;
						if (strlen($nf)==0){
							$sql  = "SELECT trim(nota_fiscal_saida) As nota_fiscal_saida ,
							TO_CHAR(data_nf_saida, 'DD/MM/YYYY') AS data_nf_saida
							FROM    tbl_os
							JOIN    tbl_os_produto USING (os)
							JOIN    tbl_os_item USING (os_produto)
							WHERE   tbl_os_item.pedido= $pedido
							AND     tbl_os_item.peca         = $peca";
							$resnf = pg_exec ($con,$sql);
							if(pg_numrows($resnf) >0){
								$nf   = trim(pg_result($resnf,0,nota_fiscal_saida));
						
							}else{
								$nf= "pendente";
							}
						}

					}
					if (strlen($sua_os) == 0) $sua_os = $os;

					# Chamado 10028
					if ($login_fabrica==1 AND $tipo_pedido_id != 86){
						if ($nf == "pendente" OR $nf == "Pendente"){
							$nf = "pendente";
						}
					}

					echo "<tr bgcolor='$cor'>";
					//if ($condicao == "Garantia") {
					 //strpos($condicao,"GARANTIA") !== false or coloquei 11/12/07 hd 9460
					if (strpos($condicao,"GARANTIA") !== false or strpos($condicao,"Garantia") !== false or strpos($condicao,"Livre de débito") !== false or strpos($condicao,"Reposição Antecipada") !== false  and $login_fabrica<>24 ) {
						echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='os_press.php?os=$os' target='_new'>$sua_os</a></font></td>";
					}

					echo "<td align='center'>";
					$sql  = "SELECT * FROM tbl_pedido_cancelado WHERE pedido=$pedido AND peca=$peca";
					$resY = pg_exec ($con,$sql);
					if (pg_numrows ($resY) > 0 and $login_fabrica<>3) {
						echo "<acronym title='".pg_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
					}else{
						if ($login_fabrica <> 11) {
							if (strtolower($nf) <> 'pendente'){
			#					echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca' target='_blank'>$nf</a>";
			/* A LINHA ACIMA ESTAVA COMENTADO, E NO DIA 01/12/2007  RETIREI POR CAUSA DO CHAMADO DA BLACK 8687 */
								echo "$nf";
							}else{
								echo "$nf &nbsp;";
							}
						} elseif ($login_fabrica == 24 or $login_fabrica==35) {
								if(strlen($nf)>0) echo $nf;
								else              echo "pendente";
						}
					}
					echo "</td>";

					
					//Gustavo 12/12/2007 HD 9590
					if($login_fabrica == 35){
						echo "<td align='left'>";
						echo "<a HREF='http://www.websro.com.br/correios.php?P_COD_UNI=$conhecimento' target = '_blank'>";
						echo $conhecimento;
						echo "</A>";
						echo "</font>";
						echo "</td>";
					}

					echo "<td align='left'>$peca_descricao</td>";
					if($login_fabrica == 45){
						echo "<td align='center'>";
						if( strtolower($nf)=='pendente' AND pg_numrows ($resY) == 0){
							echo "<form name='acao_$i'>";
							echo "Motivo: <input type='text' name='motivo' class='frm'>";
							echo "<a href='javascript: if(confirm(\"Deseja cancelar este item do pedido: $peca_descricao?\")) window.location = \"$PHP_SELF?cancelar=$pedido_item&pedido=$pedido&os=$os&motivo=\"+document.acao_$i.motivo.value'>";
							echo " <img src='imagens/btn_x.gif'><font size='1'>Cancelar</font></a>";
							echo "</form>";
						}
						echo "</td>";
						echo "</tr>";
					}
				}
				echo "</table>";
			}

		}

		/* ------------ Posição do Pedidos ------------------- */
		$mostrar_pendencia = 0;

		#Chamado 10028
		if ($login_fabrica == 1 ) {
			/*if ($login_fabrica == 1 and $status_pedido !=4 and ($tipo_pedido_id != 86 OR $pedido_sedex=='t')){1
				$mostrar_pendencia = 1;
			}*/

			$sql = "SELECT	tbl_pedido_item.qtde         ,
							tbl_pedido_item.qtde_faturada,
							tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada) AS qtde_pendente,
							tbl_peca.peca                ,
							tbl_peca.referencia          ,
							tbl_peca.descricao
					FROM    tbl_pedido
					JOIN    tbl_pedido_item      ON tbl_pedido_item.pedido = tbl_pedido.pedido
					JOIN    tbl_peca             ON tbl_peca.peca          = tbl_pedido_item.peca
					WHERE   tbl_pedido.pedido = $pedido
					ORDER   BY  qtde_pendente DESC, tbl_pedido_item.pedido_item";
			$res = pg_exec ($con,$sql);

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				if ($i == 0) {
					echo "<br>";
					echo "<table width='700' cellpadding='2' cellspacing='1'   align='center' class='Tabela' >";
					echo "<thead>";
					echo "<tr bgcolor='#C0C0C0'>";
					echo "<td align='center' colspan='4'><b>Posição deste pedido</b></td>";
					echo "</tr>";
					//echo "<tr bgcolor='#C0C0C0'>";
					echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>";
					echo "<td align='left'>Componente</td>";
					echo "<td>Qtde<br>Pedida</td>";
					echo "<td>Qtde<br>Faturada</td>";
					//if ($mostrar_pendencia == 1){
						echo "<td>Qtde<br>Pendente</td>";
					//}
					echo "</tr>";
					echo "</thead>";
				}
				
				$cor = "#FFFFFF";
				if ($i % 2 == 0) $cor = '#F1F4FA';
				
				echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left; font-weight:normal' >";
				echo "<td nowrap>" . pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao) . "</td>";
				echo "<td align='right'>" . pg_result ($res,$i,qtde) . "</td>";
				echo "<td align='right'>" . pg_result ($res,$i,qtde_faturada) . "</td>";
				//if ($mostrar_pendencia == 1){
					echo "<td align='right'>" . pg_result ($res,$i,qtde_pendente) . "</td>";
				//}
				echo "</tr>";
			}
			echo "</table>";

			echo "<br>";

			# Chamado 10028
			/* EMBARQUES */
			$sql = "SELECT 
								tbl_pendencia_bd_novo_nf.pedido,
								tbl_pendencia_bd_novo_nf.referencia_peca,
								to_char(tbl_pendencia_bd_novo_nf.data,'DD/MM/YYYY') as data,
								tbl_pendencia_bd_novo_nf.qtde_embarcada,
								tbl_pendencia_bd_novo_nf.nota_fiscal,
								tbl_pendencia_bd_novo_nf.transportadora_nome,
								tbl_pendencia_bd_novo_nf.conhecimento
							FROM tbl_pendencia_bd_novo_nf
							WHERE posto  = '$posto'
							AND   pedido = '$pedido'
							ORDER BY pedido,tbl_pendencia_bd_novo_nf.data DESC
						";
			//echo nl2br($sql);
			$res = pg_exec($con,$sql);
			$resultado = pg_numrows($res);

			for ($i = 0 ; $i < $resultado ; $i++) {
				if ($i == 0) {
					echo "<table width='700' cellpadding='2' cellspacing='1'   align='center' class='Tabela' >";
					echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-weight:bold ; text-align:center ' >";
					echo "<td colspan='7'>Embarques</td>";
					echo "</tr>";
					echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>";
					echo "<td align='left'>Componente</td>";
					echo "<td>Data</td>";
					echo "<td>Qtde<br>Embarcada</td>";
					echo "<td>Nota<br>Fiscal</td>";
					echo "<td>Transportadora</td>";
					echo "<td>Nº Objeto</td>";
					echo "</tr>";
				}
				
				$cor = "#FFFFFF";
				if ($i % 2 == 0) $cor = '#F1F4FA';
				
				$peca					=  pg_result ($res,$i,referencia_peca);
				$data					=  pg_result ($res,$i,data);
				$qtde_embarcada			=  pg_result ($res,$i,qtde_embarcada);
				$nota_fiscal			=  pg_result ($res,$i,nota_fiscal);
				$transportadora_nome	=  pg_result ($res,$i,transportadora_nome);
				$conhecimento			=  pg_result ($res,$i,conhecimento);
				
				$conhecimento = strtoupper($conhecimento);
				$conhecimento = str_replace("-","",$conhecimento);
				$conhecimento = "<a href='http://www.websro.com.br/correios.php?P_COD_UNI=".$conhecimento."BR' target='_blank'>$conhecimento</a>";

				echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left' >";
				echo "<td nowrap>$peca</td>";
				echo "<td align='center'>$data</td>";
				echo "<td align='center'>$qtde_embarcada</td>";
				echo "<td align='center'>$nota_fiscal</td>";
				echo "<td align='left'>$transportadora_nome</td>";
				echo "<td align='right'>$conhecimento</td>";
				echo "</tr>";
			}
			echo "</table>";

			echo "<br>";

			//hd 14024 25/2/2008
			/*MOSTRAR AS NOTAS FISCAIS DAS ORDENS PROGRAMADAS*/

			$sql = "SELECT 
								tbl_ordem_programada_pedido_black.pedido,
								tbl_ordem_programada_pedido_black.peca_referencia,
								tbl_peca.descricao,
								tbl_ordem_programada_pedido_black.qtde_faturada_ped,
								tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada AS qtde_pendente,
								tbl_ordem_programada_pedido_black.nota_fiscal,
								to_char(tbl_ordem_programada_pedido_black.data_nota,'DD/MM/YYYY') as data_nota,
								tbl_ordem_programada_pedido_black.transportadora_nome,
								tbl_ordem_programada_pedido_black.ar as conhecimento
							FROM tbl_ordem_programada_pedido_black
							JOIN tbl_peca using(peca)
							JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_ordem_programada_pedido_black.pedido
							AND tbl_pedido_item.peca = tbl_ordem_programada_pedido_black.peca
							WHERE tbl_ordem_programada_pedido_black.pedido = '$pedido'
							ORDER BY tbl_ordem_programada_pedido_black.pedido,tbl_ordem_programada_pedido_black.pedido_data, qtde_pendente DESC";
			//echo $sql;
			$res = pg_exec($con,$sql);
			$resultado = pg_numrows($res);

			for ($i = 0 ; $i < $resultado ; $i++) {
				if ($i == 0) {
				echo "<table width='700' align='center' border='0' cellspacing='3'>";
					echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-weight:bold ; text-align:center ' >";
					echo "<td colspan='8'>Embarques</td>";
					echo "</tr>";
					echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>";
					echo "<td align='left'>Componente</td>";
					echo "<td align='left'>Descricao</td>";
					echo "<td>Qtde<br>Embarcada</td>";
					#echo "<td>Qtde<br>Pendente</td>"; Retirado - HD 14831
					echo "<td>Nota Fiscal</td>";
					echo "<td>Data Nota</td>";
					echo "<td>Transportadora</td>";
					echo "<td>Nº Objeto</td>";
					echo "</tr>";
				}
				
				$cor = "#FFFFFF";
				if ($i % 2 == 0) $cor = '#F1F4FA';
				
				$peca					=  pg_result ($res,$i,peca_referencia);
				$peca_descricao			=  pg_result ($res,$i,descricao);
				$qtde_faturada_ped			=  pg_result ($res,$i,qtde_faturada_ped);
				$qtde_pendente			=  pg_result ($res,$i,qtde_pendente);
				$nota_fiscal			=  pg_result ($res,$i,nota_fiscal);
				$data_nota  			=  pg_result ($res,$i,data_nota);
				$transportadora_nome	=  pg_result ($res,$i,transportadora_nome);
				$conhecimento			=  pg_result ($res,$i,conhecimento);
				
				$conhecimento = strtoupper($conhecimento);
				$conhecimento = str_replace("-","",$conhecimento);
				$conhecimento = "<a href='http://www.websro.com.br/correios.php?P_COD_UNI=".$conhecimento."BR' target='_blank'>$conhecimento</a>";

				echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left' >";
				echo "<td nowrap>$peca</td>";
				echo "<td nowrap>$peca_descricao</td>";
				echo "<td align='center'>$qtde_faturada_ped</td>";
				#echo "<td align='center'>$qtde_pendente</td>"; HD -14831
				echo "<td align='center'>$nota_fiscal</td>";
				echo "<td align='center'>$data_nota</td>";
				echo "<td align='left'>$transportadora_nome</td>";
				echo "<td align='right'>$conhecimento</td>";
				echo "</tr>";
			}
			echo "</table>";

			echo "<br>";
		}
	}
	?>
	</td>


	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
<?
echo "<tr><td ><br>";
if (strlen($data_exportado)==0 AND ($login_admin == 232 or $login_admin == 112) AND $pedido_sedex <> 't'){
	$chave=  md5($pedido);
	echo "<INPUT TYPE='submit' onclick=\"javascript: if (confirm('Deseja realmente transformar o Pedido nº $pedido_blackedecker em Pedido Sedex ?') == true) { window.location='$PHP_SELF?sedex=$pedido&pedido=$pedido&key=$chave'; }\" value='Transformar em Pedido Sedex'>";
}

if (strlen($data_exportado)==0 AND $pedido_condicao<>62 AND ($login_admin == 232 or $login_admin == 112)){
		/*echo "<INPUT TYPE='submit' onclick=\"javascript: if (confirm('Deseja realmente transformar o Pedido nº $pedido_blackedecker para Pedido Garantia ?') == true) { window.location='$PHP_SELF?garantia=$pedido'; }\" value='Transformar em Pedido Garantia'>";
		*/
	}
echo "</td></tr>";
?>

</form>


</table>

<p>
<?
if($login_fabrica==24){
	?>
<center><a href='<? echo "pedido_admin_consulta_txt.php?pedido=$pedido&exportar=true"; ?>'>EXPORTAR PEDIDO</a></center>
<?}?>
<? include "rodape.php"; ?>
