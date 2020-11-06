<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include "funcoes.php";

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
	$resS = pg_query ($con,$sqlS);
	$pedido = $sedex;
}

if (strlen ($sedex) > 0 AND $login_admin=='112' ) {
	$sqlS = "UPDATE tbl_pedido SET
			pedido_sedex = 't'   ,
			admin = 112
			WHERE pedido = $sedex";
	//echo $sql;exit;
	$resS = pg_query ($con,$sqlS);
	$pedido = $sedex;
}


$lista_de_admin_altera_pedido = array("586", "568", "432", "399","567","398","1076","822", "1375","586","2151");
$ronaldo = 586;

if(strlen($pedido) > 0 AND $login_fabrica == 24) {
	$sql="SELECT  sum(qtde) AS qtde,
				  sum(qtde_cancelada) AS qtde_cancelada
			FROM  tbl_pedido
			JOIN  tbl_pedido_item USING(pedido)
			WHERE tbl_pedido.pedido=$pedido
			AND   tbl_pedido.status_pedido <> 14";
	$res=pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$qtde           = pg_fetch_result($res,0,qtde);
		$qtde_cancelada = pg_fetch_result($res,0,qtde_cancelada);
		if($qtde == $qtde_cancelada){
			$sql2="UPDATE tbl_pedido SET status_pedido=14
					WHERE pedido = $pedido";
			$res2=pg_query($con,$sql2);
		}
	}
}

if (strlen($_GET["cancelar"])>0 AND strlen($_GET["pedido"])>0) {

	$res = pg_query ($con,"BEGIN TRANSACTION");

	$pedido   = trim($_GET["pedido"]);
	$motivo   = trim($_GET["motivo"]);
	$cancelar = trim($_GET["cancelar"]); 
	$qtde_cancelar = trim($_GET["qtde_cancelar"]);
	$os_cancela  = trim($_GET["os"]);
	
	if(strlen($motivo)==0) $msg_erro = "Por favor informe o motivo de cancelamento da peça: $referencia - $qtde";
	else                   $aux_motivo = "'$motivo'";
	//Cancela todo o pedido quando ele é distribuidor

	if($login_fabrica == 51 or $login_fabrica == 81 or $login_fabrica == 2 or $login_fabrica == 10 or $login_fabrica == 80) {
		if(strlen($qtde_cancelar)==0 and $cancelar<>"todo") $msg_erro = "Por favor informe a quantidade a cancelar";
	}

	if (strlen($msg_erro)==0){
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

			if ( ($login_fabrica == 3 OR $login_fabrica == 51 OR $login_fabrica == 81 OR $login_fabrica == 10) AND $distribuidor == 4311 AND in_array($login_admin, $lista_de_admin_altera_pedido)   ) { // HD 46988
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
/*
	Tirei averificação de distribuidor, pois não há variável setada, nunca ia entrar no 'if'
	Adicionei o $login=$ronaldo para que só o admin $ronaldo possa cancelar um pedido inteiro
	Adicionei o filtro 'AND distribuidor = 4311' para que $ronaldo só possa cancelar pedidos
	distribuidos pela Telecontrol
*/
			if (( $login_fabrica == 81 or $login_fabrica == 51 or $login_fabrica == 10 ) and $login_admin == $ronaldo) {
				$sql = "SELECT  PE.pedido      ,
						        PE.distribuidor,
						        PI.pedido_item ,
						        PI.peca        ,
						        PI.qtde        ,
						        PI.qtde_cancelada,
						        OP.os,
						        (PI.qtde-PI.qtde_cancelada-qtde_faturada_distribuidor) AS cancelar
						    FROM   tbl_pedido        PE
						    JOIN   tbl_pedido_item   PI USING (pedido)
						    LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
						    LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
						    WHERE PE.pedido  = $pedido
						    AND   PE.fabrica = $login_fabrica
						    AND distribuidor = 4311
						    AND   PI.qtde > PI.qtde_cancelada + qtde_faturada_distribuidor";
			}
			
			$res = pg_query ($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for($i==0;$i<pg_num_rows($res);$i++){
					$peca			= pg_fetch_result ($res,$i,peca);
					$qtde			= pg_fetch_result ($res,$i,qtde);
					$os				= pg_fetch_result ($res,$i,os);
					$distribuidor	= pg_fetch_result ($res,$i,distribuidor);
					if ($login_admin == $ronaldo) {
						$qtde_item_cancelar = pg_fetch_result ($res,$i,cancelar);
						$qtde_cancelar = ($qtde<>$qtde_item_cancelar) ? $qtde_item_cancelar: $qtde;
					}

					if(strlen($qtde_cancelar) > 0) {
						$qtde=$qtde_cancelar;
					}

					if(strlen($distribuidor)>0){
						if($login_fabrica <>51 and $login_fabrica <>81 and $login_fabrica<>10){
							$sql  = "SELECT fn_pedido_cancela($distribuidor,$login_fabrica,$pedido,$peca,$aux_motivo)";
							$resY = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}else{
							$sql  = "SELECT fn_pedido_cancela_gama($distribuidor,$login_fabrica,$pedido,$peca,$qtde_cancelar,$aux_motivo)";
							$resY = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
					}
				}
			}
		}else{//Cancela uma peça do pedido

			$sql = "SELECT  PI.pedido_item,
					(PI.qtde - PI.qtde_faturada - PI.qtde_faturada_distribuidor) as qtde       ,
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
				AND     os not in (select os from tbl_pedido_cancelado where os = OP.os)";
				if ($login_fabrica <> 2 and $login_fabrica <> 51 and $login_fabrica <> 81 and $login_fabrica<>10) {
					$sql .= " AND     PE.exportado   IS NULL ";
				}
			$res = pg_query ($con,$sql);
			if (pg_num_rows ($res) > 0) {
	
				$peca         = pg_fetch_result ($res,peca);
				$referencia   = pg_fetch_result ($res,referencia);
				$descricao    = pg_fetch_result ($res,descricao);
				$qtde         = pg_fetch_result ($res,qtde);
				$os           = pg_fetch_result ($res,os);
				$posto        = pg_fetch_result ($res,posto);
				$distribuidor = pg_fetch_result ($res,distribuidor);

				if(strlen($qtde_cancelar) > 0) {
					$qtde=$qtde_cancelar;
				}
				if(strlen($msg_erro)==0){
					if(strlen($distribuidor)>0){
						if($login_fabrica <>51 and $login_fabrica <>81 and $login_fabrica<>10 and $login_fabrica <>2){
							$sql  = "SELECT fn_pedido_cancela($distribuidor,$login_fabrica,$pedido,$peca,$aux_motivo)";
							$resY = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}else {
							$sql = "SELECT embarque_item FROM tbl_embarque_item where pedido_item = $cancelar and qtde = $qtde_cancelar";
							$res = pg_exec($con,$sql);
							if(pg_num_rows($res)>0) {
								$embarque_item = pg_result($res,0,0);
								$sqlcancela = "SELECT fn_cancelar_embarque_item($embarque_item)";
								$rescancela = pg_exec($con,$sqlcancela);
								$msg_erro .= pg_errormessage($con);
							}else {							
								$sqlY  = "SELECT fn_pedido_cancela_gama($distribuidor,$login_fabrica,$pedido,$peca,$qtde_cancelar,$aux_motivo)";
								$resY = @pg_query ($con,$sqlY);
								$msg_erro .= pg_errormessage($con);
							}
							if (strlen($msg_erro)==0) {
								if($login_fabrica == 51){
									$subject  = "Pedido GamaItaly Cancelado";
								}
								if($login_fabrica == 81){
									$subject  = "Pedido Salton Cancelado";
								}
								if($login_fabrica == 10){
									$subject  = "Pedido Telecontrol Cancelado";
								}
									$message="<b>Cancelamento de Pedido</b><br><br>";
									$message .= "<b> Admin </b>: ".$login_admin."<br>";
									$message .= "<b> Posto </b>: ".$posto."<br>";
									$message .= "<b> OS </b>: ".$os."<br>";
									$message .= "<b> Pedido </b>: ".$pedido."<br>";

									$headers = "From: Telecontrol <helpdesk@telecontrol.com.br>\n";
									$headers .= "MIME-Version: 1.0\n";
									$headers .= "Cc: Waldir <waldir@telecontrol.com.br>\n";
									$headers .= "Content-type: text/html; charset=iso-8859-1\n";	

									mail("ronaldo@telecontrol.com.br",$subject,$message,$headers);
							}
						}
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

						$resY = pg_query ($con,$sql);
						if (pg_num_rows ($resY) > 0) {
							$msg_erro  .= "A peça $referencia - $descricao do pedido $pedido já está faturada com a nota fiscal". pg_fetch_result ($resY,nota_fiscal);
						}else{
							if($login_fabrica==2){
								$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde_cancelada + $qtde_cancelar WHERE pedido_item = $cancelar;";
							}else{
								$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde - qtde_faturada - qtde_faturada_distribuidor WHERE pedido_item = $cancelar;";
							}
							$res = pg_query ($con,$sql);

							$sql = "INSERT INTO tbl_pedido_cancelado (
									pedido ,
									posto  ,
									fabrica,
									os     ,
									peca   ,
									qtde   ,
									motivo ,
									data
								)VALUES(
									$pedido,
									$posto,
									$login_fabrica,
									$os_cancela,
									$peca,
									$qtde,
									$aux_motivo,
									current_date
								);";
							$res = @pg_query ($con,$sql);
						}
					}
				}
			}else $msg_erro .= "Pedido já exportado, não é possível excluir peças";
		}
	}
	if (strlen($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?pedido=$pedido");
		exit;
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

$aprovar    = trim($_GET["aprovar"]);

if(strlen($aprovar)>0 AND strlen($pedido)>0){

	$res_os = pg_query($con,"BEGIN TRANSACTION");

	$sql = "UPDATE tbl_pedido SET data_aprovacao = CURRENT_TIMESTAMP,status_pedido=null
			WHERE tbl_pedido.fabrica = $login_fabrica
			AND   tbl_pedido.pedido  = $pedido ";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);

	if (strlen($msg_erro)==0){
		$res = pg_query($con,"COMMIT TRANSACTION");
		$msg_ok = "Pedido $pedido aprovado.";
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}

# Somente o Ronaldo irá tirar a Finalização para conseguir Alterar o pedido da Loja Virtual
if(strlen($retirar_finalizado)>0 AND strlen($pedido)>0){

	$res_os = pg_query($con,"BEGIN TRANSACTION");

	$sql = "UPDATE tbl_pedido SET finalizado = null,status_pedido=null
			WHERE tbl_pedido.fabrica = 10
			AND   tbl_pedido.pedido  = $pedido ";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);

	if (strlen($msg_erro)==0){
		$res = pg_query($con,"COMMIT TRANSACTION");
		$msg_ok = "O Pedido $pedido foi desfeito a finalização. É NECESSÁRIO logar como posto na LOJA VIRTUAL alterar e finalizar NOVAMENTE!.";
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
		$msg_erro = "Não foi possível desfazer a FINALIZAÇÃO do Pedido $pedido para que fosse possível alterar e finalizar NOVAMENTE!.";	}
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
			tbl_pedido.seu_pedido                                                         ,
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
			tbl_posto.nome                              AS nome_posto                     ,
			tbl_pedido.status_fabricante                                                  ,
			tbl_pedido.origem_cliente                                                     ,
			tbl_pedido.transportadora                                                     ,
			tbl_pedido.tipo_frete                                                         ,
			tbl_pedido.valor_frete                                                        ,
			tbl_pedido.pedido_os

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
	$res = pg_query ($con,$sql);

	if (pg_num_rows ($res) > 0) {
		$pedido              = trim(pg_fetch_result ($res,0,pedido));
		$pedido_condicao     = trim(pg_fetch_result ($res,0,condicao));
		$condicao            = trim(pg_fetch_result ($res,0,condicao_descricao));
		$tabela              = trim(pg_fetch_result ($res,0,tabela));
		$tabela_descricao    = trim(pg_fetch_result ($res,0,tabela_descricao));
		$pedido_cliente      = trim(pg_fetch_result ($res,0,pedido_cliente));
		$pedido_acessorio    = trim(pg_fetch_result ($res,0,pedido_acessorio));
		$pedido_sedex        = trim(pg_fetch_result ($res,0,pedido_sedex));
		$data_pedido         = trim(pg_fetch_result ($res,0,data_pedido));
		$data_finalizado     = trim(pg_fetch_result ($res,0,data_finalizado));
		$data_exportado      = trim(pg_fetch_result ($res,0,data_exportado));
		$posto               = trim(pg_fetch_result ($res,0,posto));
		$codigo_posto        = trim(pg_fetch_result ($res,0,codigo_posto));
		$nome_posto          = trim(pg_fetch_result ($res,0,nome_posto));
		$pedido_blackedecker = trim(pg_fetch_result ($res,0,pedido_blackedecker));
		$seu_pedido          = trim(pg_fetch_result ($res,0,seu_pedido));
		$login               = trim(pg_fetch_result ($res,0,login));
		$data_recebido       = trim(pg_fetch_result ($res,0,recebido_posto));
		$tipo_pedido_id      = trim(pg_fetch_result ($res,0,tipo_pedido));
		$tipo_pedido         = trim(pg_fetch_result ($res,0,tipo_descricao));
		$pedido_desconto     = trim(pg_fetch_result ($res,0,pedido_desconto));
		$status_pedido       = trim(pg_fetch_result ($res,0,status_pedido));
		$distribuidor        = trim(pg_fetch_result ($res,0,distribuidor));
		$status_fabricante   = trim(pg_fetch_result ($res,0,status_fabricante));
		$origem_cliente     = trim(pg_fetch_result ($res,$i,origem_cliente));
		$pedido_os          = trim(pg_fetch_result ($res,$i,pedido_os));
		$transportadora     = trim(pg_fetch_result ($res,0,transportadora));
		$tipo_frete         = trim(pg_fetch_result ($res,$i,tipo_frete));
		$valor_frete        = trim(pg_fetch_result ($res,$i,valor_frete));

		if($login_fabrica==24){
			$login_alteracao     = trim(pg_fetch_result ($res,0,login_alteracao));
		}

		if (strlen ($login) == 0) $login = "Posto";

		if($login_fabrica <> 15) {
			$detalhar = "ok";
		}

		if ($login_fabrica == 1 AND $pedido_acessorio == "t"){
			$pedido_blackedecker = intval($pedido_blackedecker + 1000);
		}

		if (strlen($seu_pedido)>0){
			$pedido_blackedecker = fnc_so_numeros($seu_pedido);
		}
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
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}
.Tabela{
	font-family: Verdana,Sans;
	font-size: 10px;
}
.Tabela thead{
	font-size: 12px;
	font-weight:bold;
}
.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.table_line1_pendencia {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #FF0000;
}

.menu_top2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	color: #000000;
}

</style>

<script type="text/javascript">
function CancelaPedidoItem(tipo,parametros,motivo) {
	var url = "<?=$PHP_SELF?>?"+parametros + "&motivo="+motivo;
	if (motivo.length == 0) alert ("Não á motivo")
		else
	if (confirm('Deseja cancelar este pedido?\n\nMotivo:\n'+motivo)) window.location=url
}
</script>

<!------------------AQUI COMEÇA O SUB MENU ---------------------!-->
<? echo "<font color=red>$msg_erro</font>";?>
<? echo "<font color=blue>$msg_ok</font>";?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<tr>
		<td valign="top" align="center">
			<? if($login_fabrica <> 15) { # HD 117922?>
			<table width="700" border="0" cellspacing="5" cellpadding="0">
				<tr class='menu_top2'>
					<td nowrap align='center'>
					<b>Atenção:&nbsp;</b>Pedidos a prazo dependerão de análise do departamento de crédito.
					</td>
				</tr>
			</table>
			<table width="700" border="0" cellspacing="5" cellpadding="0">
				<tr class='menu_top2'>
					<td nowrap align='center'>
						<b>Pedido</b>
						<br>
						<?
						echo ($login_fabrica == 1) ? $pedido_blackedecker : $pedido;
						?>
					</td>
					<? if (strlen($pedido_cliente) > 0) { ?>
						<td nowrap align='center'>
							<b>Pedido Cliente</b>
							<br>
							<?=$pedido_cliente?>
						</td>
					<? } ?>
					<td nowrap align='center'>
						<b>Condição Pagamento</b>
						<br>
						<?=$condicao?>
					</td>
					<td nowrap align='center'>
						<b>Tabela de Preços</b>
						<br>
						<?=$tabela_descricao?>
					</td>
					<td nowrap align='center'>
						<b>Responsável</b>
						<br>
						<?echo strtoupper ($login) ?>
					</td>
					<? //HD 11871 Paulo
					if ($login_fabrica==24 and strlen($login_alteracao) > 0){?>
							<td nowrap align='center'>
								<b>Alterado Por</b>
								<br>
								<?echo strtoupper ($login_alteracao) ?>
							</td>
					<?}?>
				</tr>
			</table>
			<? } ?>
			<table width="700" border="0" cellspacing="5" cellpadding="0">
				<tr class='menu_top2'>
					<?if ($login_fabrica==15) { # HD 117922?>
					<td nowrap align='center'>
						<strong>Pedido</strong>
						<br /><?=$pedido;?>
					</td>
					<? } ?>
					<td nowrap align='center'>
						<strong>Posto</strong>
						<br />
						<?=$codigo_posto?>
					</td>
					<td nowrap align='center'>
						<strong>Razão Social</strong>
						<br/>
						<?=$nome_posto?>
					</td>
					<td nowrap align='center'>
						<strong>Data</strong>
						<br/>
						<?=$data_pedido?>
						&nbsp;
					</td>
					<td nowrap align='center'>
						<strong>Finalizado</strong>
						<br/>
						<?=$data_finalizado?>
						&nbsp;
					</td>
				</tr>
				<?
				if ($login_fabrica==1){
					$sql2 = "SELECT produto_locador,
							nota_fiscal_locador,
							data_nf_locador,
							serie_locador 
							FROM tbl_pedido_item 
							WHERE pedido=$pedido limit 1";
					$res2 = pg_query ($con,$sql2);
					if (pg_num_rows ($res2) > 0 and strlen(trim(pg_fetch_result ($res2,0,nota_fiscal_locador)))>0) {
						$produto_locador     = pg_fetch_result ($res2,0,produto_locador);
						$nota_fiscal_locador = pg_fetch_result ($res2,0,nota_fiscal_locador);
						$data_nf_locador     = pg_fetch_result ($res2,0,data_nf_locador);
						$serie_locador       = pg_fetch_result ($res2,0,serie_locador);
						?>
						<br>
						<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='600' border='0'>				
							<tr bgcolor='#C0C0C0'>
								<td align='center' colspan='4' >
									<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Projeto Locador - Nota Fiscal de compra do Locador</b>
									</font>
								</td>
							</tr>
							<tr>
								<td nowrap align='center'>
									<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Nota fiscal</b>
										<br>
										<?echo $nota_fiscal_locador;?>
									</font>
								</td>
								<td nowrap align='center'>
									<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Numero de série</b>
										<br>
										<?echo $serie_locador;?>
									</font>
								</td>
								<td nowrap align='center'>
									<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Modelo do produto</b>
										<br>
										<?echo $produto_locador;?>
									</font>
								</td>
								<td nowrap align='center'>
									<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Data nf locador</b>
										<br>
										<?echo $data_nf_locador;?>
									</font>
								</td>
							</tr>
						</table>
						<br>
					<?}
				}?>
				<tr>
				<?if ($login_fabrica == 24) {?>
					<td nowrap align='center'>
						<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Recebido Posto</b>
						<br>
						<?echo $data_recebido?>
						&nbsp;
						</font>
					</td>
				<?}?>
				<?if ($login_fabrica == 45) { 	// HD 27232?>
					<td nowrap align='center'>
						<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Status Fabricante</b>
						<br>
						<?echo $status_fabricante?>
						&nbsp;
						</font>
					</td>
				<?}?>
				</tr>
			</table>
			<?
			if ($login_fabrica == 7) {

				$pedido_os_descricao = ($pedido_os =='t') ? " Ordem Serviço" : " Compra Manual";
				$origem_descricao = ($origem_cliente == 't') ? "Cliente" : "PTA";

				?>
				<table width="700" border="0" cellspacing="5" cellpadding="0">
					<tr>
						<td nowrap align='center'>
							<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Tipo do Pedido</b>
							<br>
							<?echo $tipo_pedido?>
							</font>
						</td>
						<td nowrap align='center'>
							<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Origem (OS/Compra)</b>
							<br>
							<?echo $pedido_os_descricao?>
							</font>
						</td>
						<td nowrap align='center'>
							<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Solicitante (PTA/Cliente)</b>
							<br>
							<?echo $origem_descricao?>
							&nbsp;
							</font>
						</td>
						<td nowrap align='center'>
							<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Tipo Frete</b>
							<br>
							<?echo $tipo_frete?>
							&nbsp;
							</font>
						</td>
						<td nowrap align='center'>
							<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Valor Frete</b>
							<br>
							<?echo $valor_frete?>
							&nbsp;
							</font>
						</td>

					</tr>
				</table>
			<?}?>
			<table width="700" border="0" cellspacing="1" cellpadding="2" align='center' class='Tabela'>
				<?
				if($login_fabrica==43) { // HD 112647
					$sql = "SELECT  '' as pedido_item,
							tbl_pedido_item.peca,
							tbl_pedido_item.preco,
							tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))  as total,
							tbl_peca.referencia            ,
							tbl_peca.descricao             ,
							tbl_peca.ipi                   ,
							sum(tbl_pedido_item.qtde) as qtde,
							tbl_pedido_item.qtde_faturada  ,
							tbl_pedido_item.qtde_cancelada ,
							tbl_pedido_item.qtde_faturada_distribuidor,
							tbl_pedido_item.obs
						FROM  tbl_pedido
						JOIN  tbl_pedido_item USING (pedido)
						JOIN  tbl_peca        USING (peca)
						WHERE tbl_pedido_item.pedido = $pedido
						AND   tbl_pedido.fabrica     = $login_fabrica
						GROUP BY tbl_pedido_item.peca,
								tbl_pedido_item.preco,
								tbl_peca.referencia            ,
								tbl_peca.descricao             ,
								tbl_peca.ipi                   ,
								tbl_pedido_item.qtde           ,
								tbl_pedido_item.qtde_faturada  ,
								tbl_pedido_item.qtde_cancelada ,
								tbl_pedido_item.qtde_faturada_distribuidor,
								tbl_pedido_item.obs
						ORDER BY tbl_peca.descricao;";
				}else{
					
					$sql = "SELECT  tbl_pedido_item.pedido_item,
							tbl_pedido_item.peca,
							tbl_pedido_item.preco,
							case when $login_fabrica = 14 then rpad ((tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)))::text,7,'0')::float else tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)) end as total,
							tbl_peca.referencia            ,
							tbl_peca.descricao             ,
							tbl_peca.ipi                   ,
							tbl_pedido_item.qtde           ,
							tbl_pedido_item.qtde_faturada  ,
							tbl_pedido_item.qtde_cancelada ,
							tbl_pedido_item.qtde_faturada_distribuidor,
							tbl_pedido_item.obs 
							
						FROM  tbl_pedido
						JOIN  tbl_pedido_item USING (pedido)
						JOIN  tbl_peca        USING (peca)
						WHERE tbl_pedido_item.pedido = $pedido
						AND   tbl_pedido.fabrica     = $login_fabrica
						ORDER BY tbl_peca.descricao,tbl_peca.peca ;";
				}
				
				$res = pg_query ($con,$sql);
				$total_pedido = 0 ;

				$lista_os = array();
				$ExibeCabecalho = 0;						
				?>
				<?if($login_fabrica <> 15){?>
					<thead>
						<tr height="20" bgcolor="#C0C0C0">
							<?if ($login_fabrica == 1) {?>
								<td>SEQ</td>
							<?}?>
							<td>Componente</td>
							<td align='center'>Qtde</td>
							<td align='center' style='font-size:9px'>Qtde<br>Cancelada</td>
							<td align='center' style='font-size:9px'>Qtde<br>Faturada</td>
							<? if($login_fabrica== 51 or $login_fabrica== 81 or $login_fabrica== 10) {
								echo "<td align='center' style='font-size:9px'>Pendência<br>do Pedido</td>";
							}
							?>
							<td align='center'>IPI</td>
							<td align='center'>Preço</td>
							<?if ($login_fabrica == 1) {?>
								<td>Total s/ IPI</td>
							<?}?>
							<td align='center'>Total c/ IPI</td>
							<?if ( $condicao<>'GARANTIA' AND ($login_fabrica == 3 or $login_fabrica == 51 or $login_fabrica == 81 or $login_fabrica == 10) AND $distribuidor == 4311 AND in_array($login_admin, $lista_de_admin_altera_pedido )) {?>
								<td>Ação</td>
							<?}?>
						</tr>
					</thead>
				<?}
				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

					$seq = $i+1;
					$total_sem_ipi = number_format(0,2,",",".");
					$cor = ($i % 2 == 0) ? "#FFFFFF": "#F1F4FA";

					$pedido_item     = pg_fetch_result ($res,$i,pedido_item);
					$peca            = pg_fetch_result ($res,$i,peca);
					
					/*if($login_fabrica == 15) {
						$consumidor_revenda = pg_fetch_result ($res,$i,consumidor_revenda);
					}*/

					$peca_descricao  = pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,descricao);
					$qtde            = pg_fetch_result ($res,$i,qtde);
					$ipi             = trim(pg_fetch_result ($res,$i,ipi));
					$obs_pedido_item = trim(pg_fetch_result ($res,$i,obs));
					$qtde_faturada   = pg_fetch_result ($res,$i,qtde_faturada);
					$qtde_cancelada  = pg_fetch_result ($res,$i,qtde_cancelada);
					$qtde_faturada_distribuidor = pg_fetch_result ($res,$i,qtde_faturada_distribuidor);

					if($distribuidor ==4311) $qtde_faturada = $qtde_faturada_distribuidor;

					$total_qtde += $qtde;
					$total_faturada += $qtde_faturada;

					if ($login_fabrica <> 14 and $login_fabrica<>24) {
						if ($login_fabrica <> 1 and $login_fabrica <> 7 and $login_fabrica <> 10 and $login_fabrica <> 3) {
							$sql  = "SELECT tbl_tabela_item.preco  AS preco,
											''                     AS ipi
									FROM    tbl_tabela_item
									WHERE   tbl_tabela_item.tabela = $tabela
									AND     tbl_tabela_item.peca   = $peca;";
						}else{
							$sql  = "SELECT tbl_pedido_item.preco  AS preco,
											''                     AS ipi
									FROM    tbl_pedido_item
									WHERE   tbl_pedido_item.pedido = $pedido
									AND     tbl_pedido_item.peca   = $peca
									";
							if ($login_fabrica==7){
								$sql  = "SELECT tbl_pedido_item.preco AS preco,
												tbl_pedido_item.ipi   AS ipi
										FROM    tbl_pedido_item
										WHERE   tbl_pedido_item.pedido = $pedido
										AND     tbl_pedido_item.peca   = $peca
										AND     tbl_pedido_item.pedido_item = $pedido_item
										";
							}
						}
						$resT = pg_query ($con,$sql);

						if (pg_num_rows ($resT) > 0) {
							// unitario sem ipi
							$preco_unit = pg_fetch_result ($resT,0,preco);
							$preco_ipi = pg_fetch_result ($resT,0,ipi);
							if (strlen($preco_ipi)>0){
								$ipi = $preco_ipi;
							}
							// total s/ ipi
							$preco_sem_ipi = $preco_unit * $qtde;
							// total pecas c/ ipi
							$total         = $preco_sem_ipi + ($preco_sem_ipi * $ipi / 100);
							$total_sem_ipi = $preco_sem_ipi;
							// total acumulado do pedido
							if ($login_fabrica <> 30) {
								$total_pedido += $total;
							} else {
								$total_pedido += $total_sem_ipi; //hd 83369 waldir
							}

							$total_pedido_sem_ipi += $total_sem_ipi;

							$preco_unit    = number_format ($preco_unit,2,",",".");
							$total         = number_format ($total,2,",",".");
							$total_sem_ipi = number_format ($total_sem_ipi,2,",",".");

							echo "preco: ",$preco_unit,"<br>";
							echo "Total: ",$total,"<br>";
							echo "ipi: ",$ipi,"<br>";
							echo $preco_sem_ipi,"<br><br>";
						}else{
							$preco      = "***";
							$total      = "***";
							$preco_unit = "***";
						}
					}else{
						// unitario sem ipi
						$preco_unit    = trim(pg_fetch_result ($res,$i,preco));
						$total         = trim(pg_fetch_result ($res,$i,total));

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
						$resz = pg_query ($con,$sql);

						if (pg_num_rows($resz) > 0) $total_pedido  = trim(pg_fetch_result ($resz,0,total_pedido));

						$total_pedido_sem_ipi += $total_sem_ipi;

						$preco_unit    = str_replace (".",",",$preco_unit);
						$total         = str_replace (".",",",$total);
						$total_sem_ipi = str_replace (".",",",$total_sem_ipi);
					}
					
					if($login_fabrica == 15) {
						if ($peca_anterior == $peca) {
							$lista_os .= (!empty($lista_os)) ? ",".$os : $os;
							$condicao1 =  " AND tbl_os.os not in ($lista_os) ";
						}else{
							$condicao1 =  " AND 1 = 1 ";
							$lista_os = "";
						}

						$sql_os = "SELECT   distinct
											tbl_os.os,
											tbl_os.sua_os, 
											tbl_produto.descricao as descricao_produto,
											tbl_os.revenda_nome,
											tbl_os.consumidor_revenda,
											tbl_os.serie,
											tbl_produto.produto
									FROM    tbl_pedido
									JOIN    tbl_pedido_item   ON  tbl_pedido_item.pedido    = tbl_pedido.pedido
									LEFT JOIN tbl_os_item     ON  tbl_os_item.peca          = tbl_pedido_item.peca AND tbl_os_item.pedido         = tbl_pedido.pedido
									LEFT JOIN tbl_os_produto  ON tbl_os_produto.os_produto  = tbl_os_item.os_produto
									LEFT JOIN tbl_os          ON tbl_os.os                  = tbl_os_produto.os
									LEFT JOIN tbl_os_item_nf  ON tbl_os_item.os_item        = tbl_os_item_nf.os_item 
									LEFT JOIN tbl_produto     ON tbl_produto.produto        = tbl_os.produto 
									WHERE   tbl_pedido_item.pedido = $pedido
									AND     tbl_pedido_item.pedido_item  = $pedido_item
									AND   tbl_pedido.fabrica     = $login_fabrica
									$condicao1 
									ORDER BY tbl_os.sua_os;";

						$res_os = @pg_query($con,$sql_os);
						if(@pg_num_rows($res_os) > 0){
							$os     = pg_fetch_result($res_os,0,os);
							$sua_os = pg_fetch_result($res_os,0,sua_os);
							$descricao_produto = pg_fetch_result($res_os,0,descricao_produto);
							$revenda_nome = pg_fetch_result($res_os,0,revenda_nome);
							$consumidor_revenda = pg_fetch_result($res_os,0,consumidor_revenda);
							$serie = pg_fetch_result($res_os,0,serie);
						}
					}

					if($login_fabrica == 15) {



						$cabecalho_r="<thead><tr height='20' bgcolor='#C0C0C0'><td>OS</td><td>Revenda</td><td>Número de Série</td><td>Produto</td><td>Componente</td><td align='center'>Qtde</td><td align='center' style='font-size:9px'>Qtde<br>Cancelada</td><td align='center' style='font-size:9px'>Qtde<br>Faturada</td><td align='center'>Preço</td></tr></thead>";
						$cabecalho_c="<thead><tr height='20' bgcolor='#C0C0C0'><td>OS</td><td>Componente</td><td align='center'>Qtde</td><td align='center' style='font-size:9px'>Qtde<br>Cancelada</td><td align='center' style='font-size:9px'>Qtde<br>Faturada</td><td align='center'>Preço</td></tr></thead>";
						if($consumidor_revenda == "R"){
							$lista_r .= "<tr bgcolor='$cor' ><td align='center'><a href='os_press.php?os=$os'>$sua_os</a><td align='left'>$revenda_nome</td><td align='left'>$serie</td><td align='left'>$descricao_produto</td><td align='left'> $peca_descricao </td><td align='right'> $qtde </td><td align='right'><font color='#FF0000'> $qtde_cancelada </font></td><td align='right'> $qtde_faturada </td><td align='right'> $preco_unit </td></tr>";
						}else{
							$lista_c .= "<tr bgcolor='$cor' ><td align='center'><a href='os_press.php?os=$os'>$sua_os</a><td align='left'> $peca_descricao </td><td align='right'> $qtde </td><td align='right'><font color='#FF0000'> $qtde_cancelada </font></td><td align='right'> $qtde_faturada </td><td align='right'> $preco_unit</td></tr>";						
						}
						$peca_anterior = $peca;
					}else{

					?>				
						<tr bgcolor="<? echo $cor ?>" >
							<?if ($login_fabrica == 1) {?>
							<td align='center'><? echo $seq ?></td>
							<?}?>
							<td align='left'><? echo $peca_descricao ?></td>
							<td align='right'><? echo $qtde ?></td>
							<td align='right'><font color='#FF0000'><? echo $qtde_cancelada ?></font></td>
							<td align='right'><? echo $qtde_faturada ?></td>
							<?if($login_fabrica == 51 or $login_fabrica == 81 or $login_fabrica == 10) {
								$qtde_pendente = $qtde - $qtde_faturada - $qtde_cancelada;
								echo "<td class='table_line1_pendencia' align='right'>";
								if ($qtde_pendente == 0 OR strlen($qtde_pendente) == 0) echo "&nbsp;";
								else echo $qtde_pendente;
								echo "</td>";
							}?>
							<td align='right'><? echo $ipi."%"; ?></td>
							<td align='right'><? 
								if ($login_fabrica == 30 and $ipi <> 0) {
									$preco_unit = str_replace (",",".",$preco_unit);
									$percentual = $ipi/100;
									$percentual = 1+$percentual;
									$preco_unit = $preco_unit/$percentual;
									$preco_unit = number_format ($preco_unit,2,",",".");
								}
									echo $preco_unit ?></td>
							<?if ($login_fabrica == 1) {?>
							<td align='center'><? echo $total_sem_ipi ?></td>
							<?}?>
							<td align='right'><? 
								if ($login_fabrica ==30) {
									$total = $total_sem_ipi;
								}
								echo $total ?></td>
							
							<?
							if ( $condicao<>'GARANTIA' AND ($login_fabrica == 3 or $login_fabrica == 51 or $login_fabrica == 81 or $login_fabrica == 10) AND $distribuidor == 4311 AND in_array($login_admin, $lista_de_admin_altera_pedido) ) {
								$sql  = "SELECT * FROM tbl_pedido_cancelado WHERE pedido=$pedido AND peca=$peca";
								$resY = pg_query ($con,$sql);
								if (pg_num_rows ($resY) > 0) {
									echo "<td><acronym title='".pg_fetch_result ($resY,0,motivo)."'>Cancelado</acronym></td>" ;
								}else{
									echo "<td align='left'>";
									if($qtde > $qtde_faturada) {
										echo "Motivo:<br> <input type='text' id='motivo_cancelamento_$i' class='frm' size='10'>";
										echo "<a href=\"javascript: if(confirm('Deseja cancelar este item do pedido: $peca_descricao?')) window.location = '$PHP_SELF?cancelar=$pedido_item&pedido=$pedido&peca=$peca&motivo='+document.getElementById('motivo_cancelamento_$i').value\">";
										echo " <img src='imagens/btn_x.gif'><font size='1'>Cancelar</font></a>";
									}
									echo "</td>";
								}
							}
							?>
						</tr>
					<?					
					}

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
				if($login_fabrica == 15) {
					echo $cabecalho_c.$lista_c."</table><br><table width='700' border='0' cellspacing='1' cellpadding='2' align='center' class='Tabela'>".$cabecalho_r.$lista_r;
				}
				?>

				<tr>
					<?if ($login_fabrica == 1) {?>
						<td colspan='5' bgcolor='#cccccc' align='center'><b>TOTAL</b></td>
						<td bgcolor='#cccccc' align='right' nowrap><b><? echo number_format ($total_pedido_sem_ipi,2,",","."); ?></b></td>
						<td bgcolor='#cccccc' align='right' nowrap><b><? echo number_format ($total_pedido,2,",","."); ?></b></td>
					<?}else{?>

						<? if ($login_fabrica<>11) {
							$coluna = ($login_fabrica== 51 or $login_fabrica== 81 ) ? '7' : '6';
							if($login_fabrica == 15) $coluna = "7";
						?>
							<td colspan='<?echo $coluna;?>' bgcolor='#cccccc' align='center'><b>TOTAL</b></td>
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
					if ( $condicao<>'GARANTIA' AND ($login_fabrica == 3 or $login_fabrica == 51 or $login_fabrica == 81 or $login_fabrica == 10) AND $distribuidor == 4311 AND in_array($login_admin, $lista_de_admin_altera_pedido ) ) {
						echo "<td bgcolor='#cccccc' align='left' colspan='3'>";
						if($total_faturada ==0 or strlen($total_faturada)==0) {
							echo "Motivo:<br><input type='text' id='motivo_cancelamento_pedido' class='frm' size='10'>";
							echo "<a href='javascript: if(confirm(\"Deseja cancelar este pedido?\")) window.location = \"$PHP_SELF?cancelar=todo&pedido=$pedido&motivo=\"+document.getElementById(\"motivo_cancelamento_pedido\").value; ' >";
							echo " <img src='imagens/btn_x.gif'><font size='1'>Cancelar Pedido</font></a>";
						}
						echo "</td>";
					}
					?>
				</tr>
			</table>
			<? if ($login_fabrica == 7){
					$sql = "SELECT  os                                           AS os,
									sua_os                                       AS sua_os,
									to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura,
									to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento
								FROM    tbl_os
								JOIN    tbl_pedido ON tbl_pedido.pedido = tbl_os.pedido_cliente
								WHERE   tbl_pedido.pedido  = $pedido
								AND     tbl_pedido.fabrica = $login_fabrica
								AND     tbl_os.fabrica     = $login_fabrica
								ORDER BY sua_os
								;";
					$res2 = pg_query ($con,$sql);
					if (pg_num_rows($res2) > 0) {
						echo "<br>";
						echo "<table width='400' cellpadding='2' cellspacing='1'   align='center' class='Tabela' >";
						echo "<thead>";
						echo "<tr bgcolor='#C0C0C0'>";
						echo "<td align='center' colspan='3'><b>Ordens de Serviço que geraram o pedido acima</b></td>";
						echo "</tr>";
						echo "<tr bgcolor='#C0C0C0'>";
						echo "<td align='center'><b>OS</b></td>";
						echo "<td align='center'><b>Abertura</b></td>";
						echo "<td align='center'><b>Fechamento</b></td>";
						echo "</tr>";
						echo "</thead>";
						for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
							$os                = pg_fetch_result ($res2,$i,os);
							$sua_os            = pg_fetch_result ($res2,$i,sua_os);
							$data_abertura     = pg_fetch_result ($res2,$i,data_abertura);
							$data_fechamento   = pg_fetch_result ($res2,$i,data_fechamento);
							if ($i % 2 == 0) $cor = '#F1F4FA';

							echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left; font-weight:normal' >";
							echo "<td align='center'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
							echo "<td align='center'>$data_abertura</td>";
							echo "<td align='center'>$data_fechamento</td>";
							echo "</tr>";
						}
						echo "</table>";
					}
			}
			if ($login_fabrica == 15){ // HD 115459
				echo "<br>";
				echo "<a href='pedido_admin_consulta_impressao.php?pedido=$pedido' target='_blank'><img src='imagens/btn_imprimir.gif'></a>";
			}
	if ($detalhar == "ok") {
		echo "<br>";
		#Mostar somente para pedidos de OS - Fabrica 1 - HD  14831
		#Nao mostrar as OS do tipo de pedido LOCADOR -  HD 15114
		if ( ($tipo_pedido_id <> 94 and (strpos(strtoupper($condicao),"GARANTIA") !== false or $login_fabrica<>1) ) OR (strlen($pedido_cliente)>0 AND $login_fabrica==7 ) ) {

			if ($login_fabrica <> 11 AND $login_fabrica <> 51 AND $login_fabrica <> 81 AND $login_fabrica <> 59 AND $login_fabrica <> 80 AND $login_fabrica <> 10) {
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

				$sql_item='';
				if($login_fabrica <> 14 and $login_fabrica <> 43 and $login_fabrica <>1) { $sql_item = " ,tbl_pedido_item.pedido_item ";}

				$sql = "SELECT  distinct
								lpad(tbl_os.sua_os::text,10,'0'),
								tbl_peca.peca      ,
								tbl_peca.referencia,
								tbl_peca.descricao ,
								tbl_os.os          ,
								tbl_os.sua_os      ,
								tbl_os_item_nf.nota_fiscal,
								tbl_pedido.posto
								$sql_item
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
				if ($login_fabrica==7){
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
													AND (tbl_os_item.pedido_cliente = tbl_pedido.pedido
													OR tbl_os_item.pedido = tbl_pedido.pedido)
							LEFT JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
							LEFT JOIN tbl_os          ON tbl_os.os                 = tbl_os_produto.os
							LEFT JOIN tbl_os_item_nf  ON tbl_os_item.os_item       = tbl_os_item_nf.os_item
							WHERE   tbl_pedido_item.pedido = $pedido
							ORDER BY lpad(tbl_os.sua_os::text,10,'0');";
				}else{
					if($login_fabrica == 5){
						$sql = "SELECT  distinct
									lpad(tbl_os.sua_os::text,10,'0') ,
									tbl_peca.peca      ,
									tbl_peca.referencia,
									tbl_peca.descricao ,
									tbl_os.os          ,
									tbl_os.sua_os      ,
									tbl_os.revenda_nome,
									tbl_os_item_nf.nota_fiscal,
									tbl_pedido.posto,
									tbl_os_item.os_item
									FROM    tbl_pedido
									JOIN    tbl_pedido_item 		ON  tbl_pedido_item.pedido     = tbl_pedido.pedido
									JOIN    tbl_peca	 		ON  tbl_peca.peca              = tbl_pedido_item.peca
									JOIN    tbl_os_item	 		ON  tbl_os_item.peca           = tbl_pedido_item.peca
									AND tbl_os_item.pedido         = tbl_pedido.pedido
									LEFT JOIN tbl_os_produto 	ON  tbl_os_produto.os_produto  = tbl_os_item.os_produto
									LEFT JOIN tbl_os	 		ON  tbl_os.os                  = tbl_os_produto.os
									LEFT JOIN tbl_os_item_nf 	ON  tbl_os_item.os_item        = tbl_os_item_nf.os_item
									WHERE   tbl_pedido_item.pedido = $pedido
									and tbl_pedido.fabrica = $login_fabrica
									ORDER BY tbl_peca.descricao";
					}
				}
				$res = pg_query ($con,$sql);

			} else {
				if($login_fabrica == 51 or $login_fabrica == 81 or $login_fabrica == 80 or $login_fabrica==10){
					$sql = "SELECT  DISTINCT
						tbl_pedido_item.pedido_item,
						LPAD(tbl_os.sua_os::text,10,'0') as sua_osx,
						tbl_peca.peca           ,
						tbl_peca.referencia     ,
						tbl_peca.descricao      ,
						tbl_os.os               ,
						tbl_os.sua_os           ,
						tbl_pedido.posto        ,
						tbl_os_item.oid         ,
						tbl_os_item.os_item
					FROM    tbl_pedido
					JOIN    tbl_pedido_item   ON  tbl_pedido_item.pedido    = tbl_pedido.pedido
					JOIN    tbl_peca          ON  tbl_peca.peca             = tbl_pedido_item.peca
					LEFT JOIN tbl_os_item     ON  tbl_os_item.peca          = tbl_pedido_item.peca AND tbl_os_item.pedido        = tbl_pedido.pedido
					LEFT JOIN tbl_os_produto  ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
					LEFT JOIN tbl_os          ON  tbl_os.os                 = tbl_os_produto.os
					WHERE   tbl_pedido_item.pedido = $pedido
					AND     tbl_pedido.fabrica     = $login_fabrica ";
					if($login_fabrica <> 10) {
						$sql .= " AND     tbl_os.os NOTNULL ";
					}
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
						tbl_os_item.oid         ,
						tbl_os_item.os_item
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
								tbl_pedido_cancelado.oid,
								tbl_pedido_item.pedido_item as os_item
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
				}
				$res = pg_query ($con,$sql);
			}
			if (pg_num_rows($res) > 0) {
				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

					$peca             = pg_fetch_result ($res,$i,peca);
					$os               = pg_fetch_result ($res,$i,os);
					$sua_os           = pg_fetch_result ($res,$i,sua_os);
					$peca_descricao   = pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,descricao);
					$posto            = pg_fetch_result ($res,$i,posto);
					if($login_fabrica == 51 or $login_fabrica == 81 or $login_fabrica==10) {
						$os_item          = pg_fetch_result($res,$i,os_item);
					}
					if(!in_array($login_fabrica,array(14,43,5,1))) {
						$pedido_item      = pg_fetch_result ($res,$i,pedido_item);
					}
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
						if ((strpos($condicao,"GARANTIA") !== false or strpos($condicao,"Garantia") !== false or strpos($condicao,"Livre de débito") !== false or strpos($condicao,"Reposição Antecipada") !== false or (strlen($pedido_cliente)>0 AND $login_fabrica==7)) or strtoupper($tipo_pedido) == 'GARANTIA') {
							echo "<td align='center'><b>Sua OS</b></td>";
						}
						if($login_fabrica <> 11) echo "<td align='center'><b>Nota Fiscal</b></td>";
						else                     echo "<td align='center'><b>Situação</b></td>";

						if ($login_fabrica == 35) {
							echo "<td align='center'><b>Conhecimento</b></td>";
						}

						echo "<td align='center'><b>Peça</b></td>";
						if($login_fabrica==45 or $login_fabrica == 51 or $login_fabrica == 81 or $login_fabrica == 2 or $login_fabrica == 80 or $login_fabrica==10) echo "<td align='center'><b>Ação</b></td>";
						echo "</tr>";
						echo "</thead>";
					}

					$cor = ($i % 2 ) ? '#F1F4FA' : "#FFFFFF";

					if($login_fabrica <> 1 ){
						if(in_array($login_fabrica,array(3,24,35,45,50,43,5,7,14,10,81,40,30,90)))  {
							$sql_adicional = " AND tbl_faturamento_item.pedido = $pedido ";
						} else {
							$sql_adicional = "AND tbl_faturamento.pedido      = $pedido ";
						}

						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
								tbl_faturamento.faturamento                      ,
								tbl_faturamento.conhecimento
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento.posto     = $posto
							$sql_adicional
							AND     tbl_faturamento_item.peca = $peca;";
						if($login_fabrica == 2 ){
							$sql = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
											tbl_faturamento.faturamento                     ,
											tbl_faturamento.conhecimento
									FROM (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item 
									JOIN tbl_pedido_item_faturamento_item on tbl_pedido_item.pedido_item = tbl_pedido_item_faturamento_item.pedido_item
									JOIN tbl_faturamento_item ON tbl_pedido_item_faturamento_item.faturamento_item= tbl_faturamento_item.faturamento_item
									JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento 
										AND tbl_faturamento.fabrica = $login_fabrica
									JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca 
									WHERE   tbl_faturamento.posto     = $posto
									AND     tbl_faturamento_item.peca = $peca";
						}
						$resx = pg_query ($con,$sql);
			//if($ip=='187.39.214.147') echo nl2br($sql);

						if (pg_num_rows ($resx) > 0) {
							$nf = trim(pg_fetch_result($resx,0,nota_fiscal));
							$faturamento = trim(pg_fetch_result($resx,0,faturamento));
							//Gustavo 12/12/2007 HD 9590
							if($login_fabrica == 35) $conhecimento   = trim(pg_fetch_result($resx,0,conhecimento));
						}else{
							/*HD 20787 Não ver nf do distrib
							$sql = "SELECT tbl_faturamento.faturamento, tbl_faturamento.nota_fiscal , TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
							tbl_faturamento.conhecimento
									FROM tbl_faturamento
									JOIN tbl_faturamento_item USING (faturamento)
									WHERE tbl_faturamento.distribuidor = 4311
									AND   tbl_faturamento_item.pedido = $pedido
									AND   tbl_faturamento_item.peca   = $peca";

							$resY = pg_query ($con,$sql);
							*/
							/*ALTERADO IGOR 23/12/2008 estava: ($login_fabroca==3)*/
							if($login_fabrica==3) {
							/*HD 72977
								$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
											tbl_faturamento.faturamento                      ,
											tbl_faturamento.conhecimento
										FROM    tbl_faturamento
										JOIN    tbl_faturamento_item USING (faturamento)
										WHERE   tbl_faturamento.posto     = $posto
										AND tbl_faturamento.pedido        = $pedido
										AND     tbl_faturamento_item.peca = $peca;";*/
								$sql = "SELECT	trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
											tbl_faturamento.faturamento                                            ,
											tbl_faturamento.conhecimento
										FROM tbl_faturamento_item
										JOIN   tbl_faturamento  ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = $login_fabrica
										JOIN   tbl_peca             ON tbl_faturamento_item.peca = tbl_peca.peca
										WHERE tbl_faturamento_item.pedido = $pedido
										AND     tbl_faturamento.posto            =  $posto";
								$resY = pg_query ($con,$sql);

								if (pg_num_rows ($resY) == 0) {

									$sql = "SELECT tbl_faturamento.faturamento, tbl_faturamento.nota_fiscal , TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
									tbl_faturamento.conhecimento
											FROM tbl_faturamento
											JOIN tbl_faturamento_item USING (faturamento)
											WHERE tbl_faturamento.posto = 4311
											AND   tbl_faturamento_item.pedido = $pedido
											AND   tbl_faturamento_item.peca   = $peca";
									$resY = pg_query ($con,$sql);
								}
							}elseif($login_fabrica == 51 or $login_fabrica == 81 or $login_fabrica==10){
								$sql ="SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal,
								tbl_faturamento.faturamento                      ,
								tbl_faturamento.conhecimento
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento.fabrica in( $login_fabrica,10)
								AND     (tbl_faturamento.pedido    = $pedido OR tbl_faturamento_item.pedido=$pedido)
								AND     tbl_faturamento_item.peca = $peca ";
								if($login_fabrica <> 10) {
									$sql .= " AND     (tbl_faturamento_item.os_item = $os_item) ";
								}
								$sql .= "ORDER BY lpad(tbl_faturamento.nota_fiscal::text,20,'0') ASC;";

								$resY = pg_query ($con,$sql);

								if (pg_num_rows ($resY) == 0) {

									$sql = "SELECT tbl_faturamento.faturamento,
													tbl_faturamento.nota_fiscal ,
													TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
									tbl_faturamento.conhecimento
											FROM tbl_faturamento
											JOIN tbl_faturamento_item USING (faturamento)
											WHERE tbl_faturamento.posto = 4311
											AND   tbl_faturamento_item.pedido = $pedido";
									if($login_fabrica <> 10) {
										$sql .= " AND     (tbl_faturamento_item.os_item = $os_item ) ";
									}
									$sql .= "AND   tbl_faturamento_item.peca   = $peca";
									$resY = pg_query ($con,$sql);
								}

							}else{
								$sql = "SELECT tbl_faturamento.faturamento,
												tbl_faturamento.nota_fiscal ,
												TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
												tbl_faturamento.conhecimento
										FROM tbl_faturamento
										JOIN tbl_faturamento_item USING (faturamento)
										WHERE tbl_faturamento.posto = 4311
											AND   tbl_faturamento_item.pedido = $pedido
											AND   tbl_faturamento_item.peca   = $peca";
								$resY = pg_query ($con,$sql);
							}

							if (pg_num_rows ($resY) > 0) {
								$nf = pg_fetch_result ($resY,0,nota_fiscal);
								$faturamento = pg_fetch_result ($resY,0,faturamento);
								//Gustavo 12/12/2007 HD 9590
								if($login_fabrica == 35) $conhecimento   = trim(pg_fetch_result($resY,0,conhecimento));
							}else{
								$nf = "Pendente";
							}
						}
					}else{
						#HD 13653

						$nf  = pg_fetch_result ($res,$i,nota_fiscal);

						if (strlen($nf)==0){
							$sql  = "SELECT trim(nota_fiscal_saida) As nota_fiscal_saida ,
							TO_CHAR(data_nf_saida, 'DD/MM/YYYY') AS data_nf_saida
							FROM    tbl_os
							JOIN    tbl_os_produto USING (os)
							JOIN    tbl_os_item USING (os_produto)
							WHERE   tbl_os_item.pedido= $pedido
							AND     tbl_os_item.peca         = $peca";
							$resnf = pg_query ($con,$sql);
							if(pg_num_rows($resnf) >0){
								$nf   = trim(pg_fetch_result($resnf,0,nota_fiscal_saida));
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
					//echo "passei";
					echo "<tr bgcolor='$cor'>";
					if ((strpos($condicao,"GARANTIA") !== false or strpos($condicao,"Garantia") !== false or strpos($condicao,"Livre de débito") !== false or strpos($condicao,"Reposição Antecipada") !== false or (strlen($pedido_cliente)>0 AND $login_fabrica==7)) or strtoupper($tipo_pedido) == 'GARANTIA' ) {
						echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='os_press.php?os=$os' target='_new'>$sua_os</a></font></td>";
					}

					echo "<td align='center'>";
					$sql  = "SELECT * FROM tbl_pedido_cancelado WHERE pedido=$pedido AND peca=$peca";
					if($login_fabrica == 2){
						$sql .= " AND os = $os ";
					}
					$resY = pg_query ($con,$sql);
					if (pg_num_rows ($resY) > 0 and $login_fabrica<>3) {
						echo "<acronym title='".pg_fetch_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
					}else{
						if ($login_fabrica <> 11) {
							echo (strtolower($nf) <> 'pendente') ? "$nf" : "$nf &nbsp;";
						} elseif ($login_fabrica == 24 or $login_fabrica==35) {
							echo (strlen($nf)>0) ? $nf : "pendente";
						}
					}
					echo "</td>";

					//Gustavo 12/12/2007 HD 9590
					if($login_fabrica == 35){
						echo "<td align='left'>";
						echo "<a href='http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=$conhecimento' target = '_blank'>";
						echo $conhecimento;
						echo "</a>";
						echo "</font>";
						echo "</td>";
					}

					echo "<td align='left'>$peca_descricao</td>";

					if($login_fabrica == 45){
						echo "<td align='center'>";
						if( strtolower($nf)=='pendente' AND pg_num_rows ($resY) == 0){
							echo "<form name='acao_$i'>";
							echo "Motivo: <input type='text' name='motivo' class='frm'>";
							echo "<a href='javascript: if(confirm(\"Deseja cancelar este item do pedido: $peca_descricao?\")) window.location = \"$PHP_SELF?cancelar=$pedido_item&pedido=$pedido&os=$os&motivo=\"+document.acao_$i.motivo.value'>";
							echo " <img src='imagens/btn_x.gif'><font size='1'>Cancelar</font></a>";
							echo "</form>";
						}
						echo "</td>";
					}
					if($login_fabrica == 51 or $login_fabrica == 81 or $login_fabrica == 2 or $login_fabrica == 10 or $login_fabrica == 80) {
						if($login_fabrica == 2){
							$qtde_cancelada = 0;
							$sqli = "SELECT tbl_pedido_item.qtde,
											tbl_pedido_item.qtde_faturada,
											tbl_pedido_item.qtde_faturada_distribuidor,
											tbl_pedido_cancelado.qtde as qtde_cancelada
									FROM tbl_pedido_item
									LEFT JOIN tbl_pedido_cancelado on tbl_pedido_item.pedido	=tbl_pedido_cancelado.pedido and tbl_pedido_item.peca =tbl_pedido_cancelado.peca
									WHERE tbl_pedido_item.pedido=$pedido
									AND tbl_pedido_item.peca = $peca ";
							if(strlen($os)>0){
								$sqli .= " AND (tbl_pedido_cancelado.os = $os or tbl_pedido_cancelado.os is null)";
							}
						}else{
							$sqli = "SELECT tbl_pedido_item.qtde,
											tbl_pedido_item.qtde_faturada,
											tbl_pedido_item.qtde_faturada_distribuidor,
											tbl_pedido_cancelado.qtde as qtde_cancelada
									FROM tbl_pedido_item
									LEFT JOIN tbl_pedido_cancelado on tbl_pedido_item.pedido =tbl_pedido_cancelado.pedido and tbl_pedido_item.peca =tbl_pedido_cancelado.peca
									WHERE tbl_pedido_item.pedido=$pedido
									AND tbl_pedido_item.peca = $peca ";
						}
						//echo $sqli."<br>";
						$resi = pg_query($con,$sqli);
						if(pg_num_rows($resi) > 0){
							$qtde = pg_fetch_result($resi,0,qtde);
							$qtde_cancelada = (strlen(pg_fetch_result($resi,0,qtde_cancelada))> 0) ? pg_fetch_result($resi,0,qtde_cancelada) : 0;
							$qtde_faturada = (pg_fetch_result($resi,0,qtde_faturada_distribuidor) > 0) ? pg_fetch_result($resi,0,qtde_faturada_distribuidor) : pg_fetch_result($resi,0,qtde_faturada);
						}
						if( (strtolower($nf)=='pendente' AND pg_num_rows ($resY) == 0) or $qtde > ($qtde_faturada+$qtde_cancelada)) {
							if (pg_num_rows ($resY) == 0 and $login_fabrica<>3) {
								//echo ">".$nf."<".">".$resY."<".">".$qtde."<".">".$qtde_faturada."<".">".$qtde_cancelada."<";

								echo "<form name='acao_$i'>";
								echo "<td align='left' nowrap>";
								echo "Qtde Cancelar <input type='text' size='5' name='qtde_a_cancelar' class='frm'> <br><br>";
								echo "Motivo: <input type='text' name='motivo' class='frm'>";
								echo "<a href='javascript: if(confirm(\"Deseja cancelar este item do pedido: $peca_descricao?\")) window.location = \"$PHP_SELF?cancelar=$pedido_item&pedido=$pedido&os=$os&motivo=\"+document.acao_$i.motivo.value+\"&qtde_cancelar=\"+document.acao_$i.qtde_a_cancelar.value'>";
								echo " <img src='imagens/btn_x.gif'><font size='1'>Cancelar</font></a>";
								echo "</form>";
							}
						}
						echo "</td>";
					}
				}
				echo "</tr>";
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
			$res = pg_query ($con,$sql);

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
				if ($i == 0) {
					echo "<br>";
					echo "<table width='700' cellpadding='2' cellspacing='1'   align='center' class='Tabela' >";
					echo "<thead>";
					echo "<tr bgcolor='#C0C0C0'>";
					echo "<td align='center' colspan='4'><b>Posição deste pedido</b></td>";
					echo "</tr>";
					echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>";
					echo "<td align='left'>Componente</td>";
					echo "<td>Qtde<br>Pedida</td>";
					echo "<td>Qtde<br>Faturada</td>";
					echo "<td>Qtde<br>Pendente</td>";
					echo "</tr>";
					echo "</thead>";
				}

				$cor = ($i % 2 == 0) ? '#F1F4FA' : "#FFFFFF";

				echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left; font-weight:normal' >";
				echo "<td nowrap>" . pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,descricao) . "</td>";
				echo "<td align='right'>" . pg_fetch_result ($res,$i,qtde) . "</td>";
				echo "<td align='right'>" . pg_fetch_result ($res,$i,qtde_faturada) . "</td>";
				//if ($mostrar_pendencia == 1){
					echo "<td align='right'>" . pg_fetch_result ($res,$i,qtde_pendente) . "</td>";
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
			$res = pg_query($con,$sql);
			$resultado = pg_num_rows($res);

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

				$cor = ($i % 2 == 0) ? '#F1F4FA' : "#FFFFFF";

				$peca					=  pg_fetch_result ($res,$i,referencia_peca);
				$data					=  pg_fetch_result ($res,$i,data);
				$qtde_embarcada			=  pg_fetch_result ($res,$i,qtde_embarcada);
				$nota_fiscal			=  pg_fetch_result ($res,$i,nota_fiscal);
				$transportadora_nome	=  pg_fetch_result ($res,$i,transportadora_nome);
				$conhecimento			=  pg_fetch_result ($res,$i,conhecimento);

				$conhecimento = strtoupper($conhecimento);
				$conhecimento = str_replace("-","",$conhecimento);
				$conhecimento = "<a href='http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=".$conhecimento."BR' target='_blank'>$conhecimento</a>";

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
			$res = pg_query($con,$sql);
			$resultado = pg_num_rows($res);

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
					echo "<td>Nota Fiscal</td>";
					echo "<td>Data Nota</td>";
					echo "<td>Transportadora</td>";
					echo "<td>Nº Objeto</td>";
					echo "</tr>";
				}

				$cor = ($i % 2 == 0) ? '#F1F4FA' : "#FFFFFF";

				$peca					=  pg_fetch_result ($res,$i,peca_referencia);
				$peca_descricao			=  pg_fetch_result ($res,$i,descricao);
				$qtde_faturada_ped			=  pg_fetch_result ($res,$i,qtde_faturada_ped);
				$qtde_pendente			=  pg_fetch_result ($res,$i,qtde_pendente);
				$nota_fiscal			=  pg_fetch_result ($res,$i,nota_fiscal);
				$data_nota  			=  pg_fetch_result ($res,$i,data_nota);
				$transportadora_nome	=  pg_fetch_result ($res,$i,transportadora_nome);
				$conhecimento			=  pg_fetch_result ($res,$i,conhecimento);

				$conhecimento = strtoupper($conhecimento);
				$conhecimento = str_replace("-","",$conhecimento);
				$conhecimento = "<a href='http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=".$conhecimento."BR' target='_blank'>$conhecimento</a>";

				echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left' >";
				echo "<td nowrap>$peca</td>";
				echo "<td nowrap>$peca_descricao</td>";
				echo "<td align='center'>$qtde_faturada_ped</td>";
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
if (strlen($data_exportado)==0 AND ($login_admin == 232 OR $login_admin == 112) AND $pedido_sedex <> 't'){
	$chave=  md5($pedido);
	echo "<INPUT TYPE='submit' onclick=\"javascript: if (confirm('Deseja realmente transformar o Pedido nº $pedido_blackedecker em Pedido Sedex ?') == true) { window.location='$PHP_SELF?sedex=$pedido&pedido=$pedido&key=$chave'; }\" value='Transformar em Pedido Sedex'>";
}

if (strlen($data_exportado)==0 AND $pedido_condicao<>62 AND ($login_admin == 232 or $login_admin == 112 )){
		/*echo "<INPUT TYPE='submit' onclick=\"javascript: if (confirm('Deseja realmente transformar o Pedido nº $pedido_blackedecker para Pedido Garantia ?') == true) { window.location='$PHP_SELF?garantia=$pedido'; }\" value='Transformar em Pedido Garantia'>";
		*/
	}
echo "</td></tr>";
?>

</form>

</table>
<?
if($login_fabrica == 51 OR $login_fabrica == 81 OR $login_fabrica == 11 or $login_fabrica==10) { //  HD 46988
	$sql = "SELECT tbl_peca.referencia         ,
				tbl_peca.descricao             ,
				tbl_pedido_item.qtde           ,
				tbl_pedido_item.qtde_faturada  ,
				tbl_pedido_item.qtde_cancelada ,
				tbl_pedido_item.pedido_item    ,
				tbl_pedido_item.peca           ,
				tbl_pedido_item.qtde_faturada_distribuidor
			FROM  tbl_pedido
			JOIN  tbl_pedido_item USING (pedido)
			JOIN  tbl_peca        USING (peca)
			JOIN  tbl_tipo_pedido USING (tipo_pedido)
			WHERE tbl_pedido_item.pedido = $pedido
			AND   tbl_tipo_pedido.codigo = 'FAT'
			AND   tbl_pedido.fabrica     = $login_fabrica
			AND   (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor) < tbl_pedido_item.qtde
			ORDER BY tbl_peca.descricao;";
		$res = pg_query ($con,$sql);

		if(pg_num_rows($res) > 0) {
			echo "<br>";
			echo "<table width='450' border='0' cellspacing='1' cellpadding='3' align='center'>";
			echo "<caption>Pendências</caption>";
			echo "<tr height='20' class='menu_top'>";
			echo "<td>Componente</td>";
			echo "<td align='center'>Qtde</td>";
			echo "<td align='center'>Qtde Faturada</td>";
			echo "<td align='center' style='font-size:9px'>Pendência<br>do Pedido</td>";
			echo "<td align='center' style='font-size:9px'>Qtde a cancelar</td>";
			echo "<td>Ação</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
				$cor = ($i % 2 == 0) ? '#F1F4FA' : "#FFFFFF";
				
				$peca_descricao   = pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,descricao);
				$qtde             = pg_fetch_result ($res,$i,qtde);
				$qtde_faturada    = pg_fetch_result ($res,$i,qtde_faturada);
				$qtde_cancelada   = pg_fetch_result ($res,$i,qtde_cancelada);
				$pedido_item      = pg_fetch_result ($res,$i,pedido_item);
				$peca             = pg_fetch_result ($res,$i,peca);
				$qtde_faturada_distribuidor = pg_fetch_result ($res,$i,qtde_faturada_distribuidor);

				if($distribuidor == 4311) $qtde_faturada = $qtde_faturada_distribuidor;

				$total_faturada += $qtde_faturada;

				echo "<tr bgcolor='$cor' class='table_line1'>";
				echo "<td align='left'>$peca_descricao</td>";
				echo "<td align='right'>$qtde</td>";
				echo "<td align='right'>$qtde_faturada</td>";
				$qtde_pendente = $qtde - $qtde_faturada - $qtde_cancelada;
				echo "<td class='table_line1_pendencia' align='right'>";
				echo ($qtde_pendente == 0 OR strlen($qtde_pendente) == 0) ? "&nbsp;" : $qtde_pendente;
				echo "</td>";
				echo "<td align='right'>";
				echo "<input type=text name='qtde_a_cancelar' size =3 value='' id='qtde_cancelar_$i'>";
				echo "</td>";
				echo "<td align='center'>";
				if($qtde > $qtde_faturada AND in_array($login_admin, $lista_de_admin_altera_pedido)) {
					echo "Motivo:<br> <input type='text' id='motivo_cancelamento_item_$i' class='frm' size='10'>";
					echo "<a href=\"javascript: if(confirm('Deseja cancelar este item do pedido: $peca_descricao?')) window.location = '$PHP_SELF?cancelar=$pedido_item&pedido=$pedido&peca=$peca&motivo='+document.getElementById('motivo_cancelamento_item_$i').value+'&qtde_cancelar='+document.getElementById('qtde_cancelar_$i').value\">";
					echo " <img src='imagens/btn_x.gif'><font size='1'>Cancelar</font></a>";
				}
				echo "</td>";
				echo "</tr>";
			}
			echo "<tr>";
			echo "<td>";
						if($ip=='201.76.78.194') echo $login_admin.$ronaldo.$lista_de_admin_altera_pedido;
			echo"</td>";

			echo "<td bgcolor='#cccccc' align='right' colspan='6' nowrap>";
			if($ip=='201.76.78.194') echo $login_admin.$ronaldo.$lista_de_admin_altera_pedido;
			if(($total_faturada ==0 or strlen($total_faturada)==0) or $login_admin == $ronaldo AND in_array($login_admin, $lista_de_admin_altera_pedido)) {
				echo "Motivo: <input type='text' id='motivo_cancelamento_pedido' name='motivo_cancelamento_pedido' class='frm' size='10'>";
    			echo "<a href=\"javascript: CancelaPedidoItem('pedido','pedido=$pedido&cancelar=todo',document.getElementsByName('motivo_cancelamento_pedido')[0].value);\">";
				echo " <img src='imagens/btn_x.gif'><font size='1'>Cancelar Pedido</font></a>";
			}
			echo "</td>";
			echo "</tr>";
			echo "</table><br><br>";

		}
		$sql = "SELECT	distinct tbl_faturamento.faturamento ,
						tbl_faturamento.nota_fiscal ,
						to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
						tbl_faturamento.conhecimento,
						tbl_faturamento_item.faturamento_item,
						tbl_faturamento_item.peca ,
						tbl_faturamento_item.qtde ,
						tbl_peca.peca ,
						tbl_peca.referencia ,
						tbl_peca.descricao
				FROM    (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item
				JOIN    tbl_faturamento_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido AND tbl_pedido_item.peca = tbl_faturamento_item.peca
				JOIN    tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
											AND tbl_faturamento.fabrica in ( $login_fabrica, 10)
				JOIN    tbl_peca             ON tbl_pedido_item.peca = tbl_peca.peca
				ORDER   BY tbl_peca.descricao";

		$res = pg_query ($con,$sql);
		if(pg_num_rows($res) > 0) {

			echo "<h2 style='font-size:15px ; color:#000000 ; text-align:center ' >Notas Fiscais que atenderam a este pedido</h2>";
			echo "<table width='450' align='center' border='0' cellspacing='3'>";
			echo "<tr bgcolor='#663399' style='color: #FFFFFF ; font-weight:bold ; text-align:center ' >";
			echo "<td>Nota Fiscal</td>";
			echo "<td>Data</td>";
			echo "<td>Peça</td>";
			echo "<td>Qtde</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
				echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' >";
				echo "<td>" . pg_fetch_result ($res,$i,nota_fiscal) . "</td>";
				echo "<td>" . pg_fetch_result ($res,$i,emissao) . "</td>";
				echo "<td nowrap>" . pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,descricao) . "</td>";
				echo "<td align='right'>" . pg_fetch_result ($res,$i,qtde) . "</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
}

if(in_array($login_fabrica,array(3,43,14,15))){
	/*HD: 47887 - IGOR 10/11/2008 */
	$sql = "
			SELECT	distinct tbl_faturamento.faturamento ,
				tbl_faturamento.nota_fiscal ,
				to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
				tbl_faturamento.conhecimento,
				tbl_faturamento_item.faturamento_item,
				tbl_faturamento_item.peca ,
				tbl_faturamento_item.qtde ,
				tbl_peca.peca ,
				tbl_peca.referencia ,
				tbl_peca.descricao
			FROM    tbl_faturamento_item
			JOIN    tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
									AND tbl_faturamento.fabrica = $login_fabrica
			JOIN    tbl_peca             ON tbl_faturamento_item.peca = tbl_peca.peca
			WHERE tbl_faturamento_item.pedido = $pedido
			ORDER   BY tbl_peca.descricao";
	$res = pg_query ($con,$sql);
	if(pg_num_rows ($res)>0){
		echo "<h2 style='font-size:15px ; color:#000000 ; text-align:center ' >Notas Fiscais que atenderam a este pedido</h2>";
		echo "<table width='450' align='center' border='0' cellspacing='3'>";
		echo "<tr class='menu_top'>";
		echo "<td>Nota Fiscal</td>";
		echo "<td>Data</td>";
		echo "<td>Peça</td>";
		echo "<td>Qtde</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

			echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' >";
			echo "<td>" . pg_fetch_result ($res,$i,nota_fiscal) . "</td>";
			echo "<td>" . pg_fetch_result ($res,$i,emissao) . "</td>";
			echo "<td nowrap>" . pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,descricao) . "</td>";
			echo "<td align='right'>" . pg_fetch_result ($res,$i,qtde) . "</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br><br>";
	}

	$sql = "SELECT	distinct
					to_char (data_log,'DD/MM/YYYY') AS data,
					to_char (data_exportado,'DD/MM/YYYY') AS data_exportado,
					tbl_peca.referencia,
					tbl_peca.descricao,
					mensagem          ,
					tbl_pedido_log_exportacao.peca
			FROM    tbl_pedido_log_exportacao
			LEFT JOIN    tbl_peca ON tbl_peca.peca = tbl_pedido_log_exportacao.peca
			WHERE pedido = $pedido
			ORDER   BY data_exportado";

	$res = pg_query ($con,$sql);
	if(pg_num_rows($res) > 0) {
		$peca = pg_fetch_result($res,0,peca);

		if(strlen($peca)>0 AND strlen($pedido)>0){
			$sqlx = "SELECT tbl_os.os, sua_os
					 FROM tbl_os
					 JOIN tbl_os_produto USING(os)
					 JOIN tbl_os_item    USING(os_produto)
					 WHERE tbl_os_item.pedido = $pedido
					 AND   tbl_os_item.peca   = $peca";
			$resx  = pg_query($con, $sqlx);
			if(pg_num_rows($resx)>0){
				$os     = pg_fetch_result($resx, 0, os);
				$sua_os = pg_fetch_result($resx, 0, sua_os);
			}
		}

		echo "<h2 style='font-size:12px ; color:#000000 ; text-align:center ' >Recusas da Fabrica</h2>";
		echo "<table width='650' align='center' border='0' cellspacing='3'>";
		echo "<tr bgcolor='#663399' style='color: #FFFFFF ; font-weight:bold ; text-align:center ' >";
		echo "<td>Data Log</td>";
		echo "<td nowrap>Envio p/ Fábrica</td>";
		echo "<td nowrap><acronym title='OSs que geraram a solicitação'>OS</acronym></td>"; 
		echo "<td>Peça</td>";
		echo "<td>Motivo</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' >";
			echo "<td>" . pg_fetch_result ($res,$i,data) . "</td>";
			echo "<td>" . pg_fetch_result ($res,$i,data_exportado) . "</td>";
			echo "<td><A HREF='os_press.php?os=$os' target='_blank'>$sua_os</A></td>";
			echo "<td nowrap>" . pg_fetch_result ($res,$i,referencia) ." - ". pg_fetch_result ($res,$i,descricao) . "</td>";
			echo "<td nowrap>" . pg_fetch_result ($res,$i,mensagem) . "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}

echo "<h2 style='font-size:15px ; color:#000000 ; text-align:center ' >Pedidos cancelados que pertencem a este pedido</h2>";

$sql =	"SELECT tbl_peca.referencia         ,
				tbl_peca.descricao          ,
				tbl_pedido_cancelado.qtde   ,
				tbl_pedido_cancelado.motivo ,
				to_char (tbl_pedido_cancelado.data,'DD/MM/YYYY') AS data ,
				tbl_os.sua_os
		FROM tbl_pedido_cancelado
		JOIN tbl_peca USING (peca)
		LEFT JOIN tbl_os ON tbl_pedido_cancelado.os = tbl_os.os
		WHERE tbl_pedido_cancelado.pedido  = $pedido
		AND   tbl_pedido_cancelado.fabrica = $login_fabrica";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
			if ($i % 2 == 0) $cor = '#F1F4FA';
			else             $cor = "#FFFFFF";
			if ($i == 0) {
				echo "<table width='600' align='center' border='0' cellspacing='3'>";
				echo "<tr bgcolor='#663399' style='color:#FFFFFF; font-weight:bold; text-align:center'>";
				echo "<td>OS</td>";
				echo "<td>Data</td>";
				echo "<td>Peça</td>";
				echo "<td>Qtde</td>";
				echo "</tr>";
				echo "<tr bgcolor='#663399' style='color:#FFFFFF; font-weight:bold; text-align:center'>";
				echo "<td colspan='4'>Motivo</td>";
				echo "</tr>";
			}
			echo "<tr bgcolor='$cor' style='font-size:9px; color:#000000; text-align:left'>";
			echo "<td nowrap align='center' rowspan='2'>".pg_fetch_result($res,$i,sua_os)."</td>";
			echo "<td nowrap align='center'>".pg_fetch_result($res,$i,data)."</td>";
			echo "<td nowrap>".pg_fetch_result($res,$i,referencia)." - ".pg_fetch_result($res,$i,descricao)."</td>";
			echo "<td nowrap align='right'>".pg_fetch_result($res,$i,qtde)."</td>";
			echo "</tr>";
			echo "<tr bgcolor='$cor' style='font-size:9px; color:#000000; text-align:left'>";
			echo "<td colspan='3' nowrap>".pg_fetch_result($res,$i,motivo)."</td>";
			echo "</tr>";
		}
		echo "</table>";
	}else{
		echo "<p align='center'>Não há nenhum pedido cancelado.</p>";
	}

if($login_fabrica == 7 or  $login_fabrica == 43 ){

	$sql = "SELECT pedido
			FROM        tbl_pedido
			WHERE       tbl_pedido.fabrica          = $login_fabrica
				AND         tbl_pedido.pedido       = $pedido
				AND         tbl_pedido.finalizado       IS NOT NULL
				AND         tbl_pedido.exportado        IS NULL
				AND         tbl_pedido.troca            IS NOT TRUE
				AND         tbl_pedido.recebido_fabrica IS NULL
				AND         (tbl_pedido.status_pedido <> 14 OR tbl_pedido.status_pedido IS NULL )
				AND         tbl_pedido.data            > '2008-08-01'
				AND         tbl_pedido.data_aprovacao   IS NULL ";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res)>0){
		echo "<form name='aprova'>";
		echo "<a href='javascript: if(confirm(\"Deseja aprovar este pedido?\")) window.location = \"$PHP_SELF?aprovar=sim&pedido=$pedido\"'>";
		echo " <font size='1'>Clique aqui para aprovar esse pedido.</font></a>";
		echo "</form>";
	}
}
if($login_fabrica == 10 and ($login_admin == 586 or $login_admin == 432)){
		echo "<form name='retirar_finalizado'>";
		echo "<a href='javascript: if(confirm(\"Deseja tirar a finalização deste pedido?\")) window.location = \"$PHP_SELF?retirar_finalizado=sim&pedido=$pedido\"'>";
		echo " <font size='1'>Clique aqui para TIRAR A FINALIZAÇÃO deste pedido para conseguir alterar o pedido LOGADO como POSTO na LOJA VIRTUAL.</font></a>";
		echo "</form>";
}

?>
<p>
<?
if($login_fabrica==24){
	?>
<center><a href='<? echo "pedido_admin_consulta_txt.php?pedido=$pedido&exportar=true"; ?>'>EXPORTAR PEDIDO</a></center>
<?}?>
<? include "rodape.php"; ?>
