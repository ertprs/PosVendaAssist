<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include "funcoes.php";

$sql = "SELECT	tbl_posto.posto,
				tbl_posto.suframa,
				tbl_posto.estado
		FROM	tbl_posto
		JOIN	tbl_posto_fabrica USING(posto)
		WHERE	tbl_posto_fabrica.posto   = $login_posto
		AND		tbl_posto_fabrica.fabrica = $login_fabrica";

$res_posto = @pg_exec ($con,$sql);
if (@pg_numrows ($res_posto) == 0 OR strlen (trim (pg_errormessage($con))) > 0 ) {
	header ("Location: index.php");
	exit;
}

$cod_posto = trim(pg_result ($res_posto,0,posto));
$suframa   = trim(pg_result ($res_posto,0,suframa));
$estado    = trim(pg_result ($res_posto,0,estado));

$lista_pedido_suframa = "sim";

if (strlen($_GET['pedido']) > 0) {
	$cook_pedido = $_GET['pedido'];
	$lista_pedido_suframa = "nao";
}

if ($suframa == 't' or $estado == 'SC') {
	$sql = "SELECT 
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
				tbl_pedido.seu_pedido
			FROM   tbl_pedido
			WHERE  tbl_pedido.pedido_suframa = $cook_pedido";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$pedido_suframa = trim(pg_result($res,0,pedido_blackedecker));
		$seu_pedido     = trim(pg_result($res,0,seu_pedido));

		if (strlen($seu_pedido)>0){
			$$pedido_suframa = fnc_so_numeros($seu_pedido);
		}
	}
}

if (strlen($cook_pedido) > 0) {
	$sql = "SELECT  
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
				seu_pedido                                                         ,
				pedido_acessorio                                                   ,
				finalizado                                                         
			FROM   tbl_pedido
			WHERE  tbl_pedido.pedido = $cook_pedido";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$pedido_blackedecker = trim(pg_result($res,0,pedido_blackedecker));
		$ped_acess           = trim(pg_result($res,0,pedido_acessorio));
		$finalizado          = trim(pg_result($res,0,finalizado));
		$seu_pedido          = trim(pg_result($res,0,seu_pedido));

		if (strlen($seu_pedido)>0){
			$pedido_blackedecker = fnc_so_numeros($seu_pedido);
		}
	}
}

if ($ped_acess == "t") $title = "Pedido Acessório";
elseif(strlen($finalizado) > 0) $title     = "Pedido Finalizado";
else $title ="Pedido Não Finalizado";
if(strlen($finalizado) > 0) $cabecalho = "Pedido Finalizado";
else $cabecalho = "Pedido Não Finalizado";

$layout_menu = "pedido";

include "cabecalho.php";


$bloq =  $_GET["bloq"];

if (strlen($_GET['pedido']) == 0) {
?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align='center'>
<tr>
	<td align="center">
		<center>
		<br>
		<p>
<?
			if (strlen($_GET['msg']) == 0) {
				echo "<b>Seu pedido número <font color='#990000'>$pedido_blackedecker</font> foi finalizado com sucesso !</b>";
			}else{
				$msg = $_GET['msg'];
				$mens[1] = "<b>Seu pedido <font color='#990000'> $pedido_blackedecker </font> foi concluído com sucesso. Somando as pendências do pedido anterior. As pendências assumirão a condição de pagamento e o preço deste pedido atual. Acompanhe suas pendências através dos relatórios gerenciais.</b>";
				$mens[2] = "<b>Seu pedido <font color='#990000'> $pedido_blackedecker </font> foi concluído com sucesso e suas pendências canceladas.</b>";
				echo $mens[$msg];
			}
			
			if (strlen($pedido_suframa) > 0) {
				echo "<b>";
				echo "No pedido acima foram mantidas as peças Nacionais. <br><br>";
				echo "Foi gerado um novo pedido de número <font color='#990000'>$pedido_suframa</font> com as peças Importadas.<br>";
				echo "</b>";
				echo "<p>";
			}
?>
			
			<font face='Verdana, Arial' size='2' color='#C64533'><b>
			Os pedidos são exportados diariamente às 11:45h. <br>
			Caso precise INCLUIR ou CANCELAR algum item o pedido ficará em aberto até este horário. <br>
			Na tela de digitação de pedidos, faça a manutenção necessária GRAVE e FINALIZE o pedido. <br> <br>
			</b></font>
			
			<b>Acompanhe neste site o andamento da sua compra</b>.
		</center>
	</td>
</tr>
</table>
<br><br><hr width='700'>

<?
}

if (strlen ($cook_pedido) > 0) {
	if($login_fabrica == 1){
        $campo_pedido_offline = ' pedido_offline, ';
    }

	$sql = "SELECT  tbl_pedido.pedido                                              ,
					$campo_pedido_offline
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
					tbl_pedido.seu_pedido                                          ,
					tbl_pedido.condicao                                            ,
					tbl_pedido.tabela                                              ,
					tbl_pedido.pedido_cliente                                      ,
					tbl_pedido.tipo_pedido                                         ,
					tbl_pedido.obs                                                 ,
					tbl_pedido.pedido_sedex                                        ,
					tbl_pedido.status_pedido                                       ,
					tbl_pedido.pedido_acessorio                                    ,
					tbl_representante.codigo  		AS representante_codigo		  ,
					tbl_representante.nome 	     	AS representante_nome   		,
					to_char(tbl_pedido.data,'DD/MM/YYYY')    AS pedido_data        ,
					tbl_condicao.descricao                   AS condicao_descricao ,
					tbl_tabela.tabela                                              ,
					tbl_tabela.descricao                     AS tabela_descricao   ,
					tbl_faturamento.nota_fiscal
			FROM    tbl_pedido
			LEFT JOIN tbl_condicao    ON  tbl_condicao.condicao = tbl_pedido.condicao
			LEFT JOIN tbl_tabela      ON  tbl_tabela.tabela     = tbl_pedido.tabela
			LEFT JOIN tbl_faturamento ON  tbl_pedido.posto      = tbl_faturamento.posto
									  AND tbl_pedido.fabrica    = tbl_faturamento.fabrica
			LEFT JOIN tbl_representante ON tbl_pedido.representante = tbl_representante.representante 
										AND tbl_pedido.fabrica = $login_fabrica
			WHERE   tbl_pedido.pedido  = $cook_pedido
			AND     tbl_pedido.posto   = $login_posto
			AND     tbl_pedido.fabrica = $login_fabrica;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$obs_pedido          = trim(pg_result ($res,0,obs));
		$pedido              = trim(pg_result ($res,0,pedido));
		$pedido_blackedecker = trim(pg_result ($res,0,pedido_blackedecker));
		$seu_pedido          = trim(pg_result ($res,0,seu_pedido));
		$pedido_acessorio    = trim(pg_result ($res,0,pedido_acessorio));
		$condicao            = trim(pg_result ($res,0,condicao_descricao));
		$tabela              = trim(pg_result ($res,0,tabela));
		$tabela_descricao    = trim(pg_result ($res,0,tabela_descricao));
		$tipo_pedido		 = trim(pg_result ($res,0,tipo_pedido));
		$pedido_sedex		 = trim(pg_result ($res,0,pedido_sedex));
		$status_pedido		 = trim(pg_result ($res,0,status_pedido));
		$pedido_cliente      = trim(pg_result ($res,0,pedido_cliente));
		$pedido_data         = trim(pg_result ($res,0,pedido_data));
		$nota_fiscal         = trim(pg_result ($res,0,nota_fiscal));
		$representante_codigo = trim(pg_result ($res,0,representante_codigo));
		$representante_nome = trim(pg_result ($res,0,representante_nome));
		
		if ($condicao == "Garantia") {
			$detalhar = "ok";
		}

		if($login_fabrica == 1){
            $pedido_offline = pg_fetch_result($res, 0, pedido_offline);
            $sql_pedido_offline = "select pedido, seu_pedido from tbl_pedido where pedido_offline = $pedido_offline and fabrica = $login_fabrica AND pedido_offline is not null and pedido_offline > 0 ";
                $res_pedido_offline = pg_query($con, $sql_pedido_offline);
                for($x=0; $x<pg_num_rows($res_pedido_offline); $x++){
                    $seuPedido = pg_fetch_result($res_pedido_offline, $x, seu_pedido);
                    $numeroPedido     = pg_fetch_result($res_pedido_offline, $x, pedido);

            $link_pedidos .= " <a target='_blank' href='pedido_blackedecker_finalizado_new.php?pedido=$numeroPedido'>$seuPedido</a> ";
	        }
	    }

		if (strlen($seu_pedido)>0){
			$pedido_blackedecker = fnc_so_numeros($seu_pedido);
		}
	}
}
?>
<br>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<?php if(!empty($_GET["hd_chamado"])) {?>
	<script>alert("Foi aberto o Helpdesk "+<?= $_GET["hd_chamado"] ?>+". Aguarde o retorno com as orientações.");



</script>
<?php } ?>

<script type="text/javascript" src="js/jquery-1.2.1.pack.js"></script>
<script src="plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">

<script LANGUAGE="JavaScript">
	$(function(){
	Shadowbox.init();
	$("#visualiza_log_item").click(function () {
		
		let pedido_id = $(this).attr("data-pedido")
		let url_log = "relatorio_log_alteracao_new.php?parametro=tbl_pedido_item&id="+pedido_id

        Shadowbox.open({
            content: url_log,
            player: "iframe",
        });
	})


});
</script>


<?php if($bloq == "f"){?>
<tr>
    <Td style="background-color:#ff0000; padding: 10px; color:#ffffff; text-align:justify; font-weight:bold; font-size:14px">
        A condi&ccedil;&atilde;o do seu pedido foi alterada para "pagamento antecipado" e est&aacute; sujeita a an&aacute;lise
        de cr&eacute;dito. Favor abrir um chamado para o suporte de sua regi&atilde;o informando o n&uacute;mero deste pedido, 
        a fim de ser orientado sobre o procedimento."
    </Td>
</tr>
<?php } ?>
<tr>
	<td valign="top" align="center">
		<table width="650" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Pedido</b>
				<br>
				<?
				if ($pedido_acessorio == "t") {
					$pedido_blackedecker = $pedido_blackedecker;
				}
				echo $pedido_blackedecker;
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
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Data</b>
				<br>
				<?echo $pedido_data?>
				</font>
			</td>
			
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Condição Pagamento</b>
				<br>
				<?echo $condicao?>
				</font>
			</td>
			
			<? if (strlen($nota_fiscal) > 0 AND $login_fabrica <> 1) { ?>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Nota Fiscal</b>
				<br>
				<?echo $nota_fiscal?>
				</font>
			</td>
			<? } ?>
			
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Representante</b>
				<br>
				<?php if(!empty($representante_codigo)){ echo $representante_codigo; }else{ echo " - "; } ?>
				</font>
			</td>

			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Tabela de Preços</b>
				<br>
				<?echo $tabela_descricao?>
				</font>
			</td>
			<?php if($login_fabrica == 1 AND strlen($link_pedidos)> 0 ){ ?>
				<td nowrap align='center'>
					<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Pedidos Desmembrados</b>
					<br>
					<?=$link_pedidos?>
					</font>
				</td>
			<?php } ?>
		</tr>
		</table>

		<table width="650" border="0" cellspacing="3" cellpadding="0" align='center'>
		<tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>
			<td align='center'>Observações da Fábrica</td>
		</tr>
		<tr>
			<td style="background-color: #F1F4FA ;">
				<?= utf8_decode($obs_pedido) ?>
			</td>
		</tr>
		</table>
		
		<table width="650" border="0" cellspacing="3" cellpadding="0" align='center'>
		<tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>
			<td align='left'>Componente</td>
			<td align='center'>Qtde</td>
			<td align='center'>Preço</td>
			<?if ($login_tipo_posto <> 39 and $login_tipo_posto <> 79 and $login_tipo_posto <> 81 and $login_tipo_posto <> 80 and $login_tipo_posto <> 38 and $login_tipo_posto <> 85 and $login_tipo_posto <> 87 and $login_tipo_posto <> 86) {?>
			<td align='center'>IPI</td>
			<? } ?>
			<td align='center'>Total</td>
		</tr>
		
		<?
		if(!empty($cook_pedido)){
		/*QUANDO FOR PEDIDO DE TROCA EM GARANTIA, MOSTRA O VALOR ZERADO*/
		$sql = "SELECT tbl_os.troca_garantia
				from tbl_os 
				JOIN tbl_os_produto on tbl_os_produto.os  = tbl_os.os
				JOIN tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIN tbl_pedido on tbl_pedido.pedido = tbl_os_item.pedido and tbl_pedido.fabrica = 1
				WHERE tbl_os.fabrica = 1 
					AND tbl_pedido.pedido = $cook_pedido
					AND tbl_os.troca_garantia is true
					AND tbl_pedido.troca is true
					limit 1;";
		$res_troca = pg_exec ($con,$sql);
		$troca_garantia = "f";
		if(pg_numrows ($res_troca) ){
			$troca_garantia = "t";
		}

		$sql = "SELECT  tbl_pedido_item.peca  ,
						tbl_peca.referencia   ,
						tbl_peca.descricao    ,
						tbl_pedido_item.qtde  ,
						tbl_pedido_item.preco ,
						tbl_peca.ipi          ,
						(1 + (tbl_peca.ipi / 100)) AS ipi_agregado,
                        tbl_pedido_item.obs,
                        tbl_peca.origem
				FROM  tbl_pedido
				JOIN  tbl_pedido_item USING (pedido)
				JOIN  tbl_peca        USING (peca)
				WHERE tbl_pedido_item.pedido = $cook_pedido
				ORDER BY tbl_pedido_item.pedido_item;";
		$res = pg_exec ($con,$sql);
		$total_pedido = 0 ;
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			
			$peca           = pg_result ($res,$i,peca);
			$peca_descricao = pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao);
			$qtde           = pg_result ($res,$i,qtde);
			$preco          = pg_result ($res,$i,preco);
			$ipi            = pg_result ($res,$i,ipi);
			$ipi_agregado   = pg_result ($res,$i,ipi_agregado);
			$obs_pedido_item = pg_fetch_result($res, $i, 'obs');
            $origem = pg_fetch_result($res, $i, 'origem');
			
			if ($login_tipo_posto <> 39 and $login_tipo_posto <> 79 and $login_tipo_posto <> 81 and $login_tipo_posto <> 80 and $login_tipo_posto <> 38 and $login_tipo_posto <> 85 and $login_tipo_posto <> 87 and $login_tipo_posto <> 86) {
				$total = $preco * $qtde * $ipi_agregado;
			}else{
				$total = $preco * $qtde;
            }

            if (in_array($origem, array('FAB/SA', 'IMP/SA'))) {
				$total = $preco * $qtde;
            }

			$total_pedido += $total ;
			$preco = number_format ($preco,2,",",".");
			$total = number_format ($total,2,",",".");
			if($troca_garantia == "f"){
				//Nao faz nada, pois é falso
			}else{
				$total_pedido =0 ;
				$preco = 0;
				$total = 0;
			}
		?>
		<tr bgcolor='<?echo $cor?>' style='color: #000000 ; font-size:12px; text-align:center '>
			<td align='left'><? echo $peca_descricao ?></td>
			<td align='right'><? echo $qtde ?></td>
			<td align='right'><? echo $preco ?></td>
			<?if ($login_tipo_posto <> 39 and $login_tipo_posto <> 79 and $login_tipo_posto <> 81 and $login_tipo_posto <> 80 and $login_tipo_posto <> 38 and $login_tipo_posto <> 85 and $login_tipo_posto <> 87 and $login_tipo_posto <> 86) {?>
			<td align='right'><? echo $ipi ?>%</td>
			<? } ?>
			<td align='right'><? echo $total ?></td>
		</tr>
		<?php
		if ($obs_pedido_item AND 1==2) {
			echo "<tr bgcolor='$cor' style='color: #000000 ; font-size:12px; text-align:center '>";
				echo "<td colspan='100%' align='left'>";
					echo "<font face='Verdana' size='1' color='#000099'>";
					echo "OBS: $obs_pedido_item";
					echo "</font>";
				echo "</td>";
			echo "</tr>";
		}
		?>

		<?
		}
		}
		?>
		
		<tr>
		<?if ($login_tipo_posto <> 39 and $login_tipo_posto <> 79 and $login_tipo_posto <> 81 and $login_tipo_posto <> 80 and $login_tipo_posto <> 38 and $login_tipo_posto <> 85 and $login_tipo_posto <> 87 and $login_tipo_posto <> 86) {?>
		<td colspan='4' bgcolor='#cccccc' align='center'><b>TOTAL</b></td>
		<?}else{?>
		<td colspan='3' bgcolor='#cccccc' align='center'><b>TOTAL</b></td>
		<? } ?>
		<td bgcolor='#cccccc' align='right' nowrap><b><? echo number_format ($total_pedido,2,",","."); ?></b></td>
		</tr>
				</table>
		
	<?
	if ($detalhar == "ok") {

		#OS não é ligado com pedidos do tipo LOCADOR
		#HD 15114
		if ($tipo_pedido<>94){
			echo "<br>";
			
			$sql = "SELECT  distinct
							lpad(tbl_os.sua_os::text,10,'0'),
							tbl_peca.peca      ,
							tbl_peca.referencia,
							tbl_peca.descricao ,
							tbl_os.os          ,
							tbl_os.sua_os      ,
							tbl_os_item_nf.nota_fiscal
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
			
			if (pg_numrows($res) > 0) {
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
					$peca           = pg_result ($res,$i,peca);
					$os             = pg_result ($res,$i,os);
					$sua_os         = pg_result ($res,$i,sua_os);
					$peca_descricao = pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao);
					$nota_fiscal    = pg_result ($res,$i,nota_fiscal);
					#$nota_fiscal_2  = pg_result ($res,$i,nota_fiscal_2);

					#if (strlen($nota_fiscal)==0) $nota_fiscal = $nota_fiscal_2;

					# Chamado 10028
					if ($login_fabrica==1 AND $tipo_pedido!=86){
						if ($nota_fiscal == "pendente" OR $nota_fiscal == "Pendente"){
							$nota_fiscal = "-";
						}
					}
					
					
					$cor = "#FFFFFF";
					if ($i % 2 == 0) $cor = '#F1F4FA';
					
					if ($i == 0) {
						echo "<table width='650' border='0' cellspacing='5' cellpadding='0' align='center'>";
						if (strlen($os) > 0) {
							echo "<tr bgcolor='#C0C0C0'>";
							echo "<td align='center' colspan='3'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Ordens de Serviço que geraram o pedido acima</b></font></td>";
							echo "</tr>";
						}
						echo "<tr bgcolor='#C0C0C0'>";
						//if ($condicao == "Garantia") {
						if (strpos($condicao,"Garantia") !== false) {
							echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Sua OS</b></font></td>";
						}
						echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Nota Fiscal</b></font></td>";
						echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Peça</b></font></td>";
						echo "</tr>";
					}
									
					if (strlen ($nota_fiscal) == 0) {
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento.pedido    = $pedido
								AND     tbl_faturamento_item.peca = $peca;";
						$resx = pg_exec ($con,$sql);
						if (pg_numrows ($resx) > 0) {
							$nf = trim(pg_result($resx,0,nota_fiscal));
							$link = 0;
						}else{
							$sql  = "SELECT trim(nota_fiscal_saida) As nota_fiscal_saida ,
								TO_CHAR(data_nf_saida, 'DD/MM/YYYY') AS data_nf_saida
								FROM    tbl_os
								JOIN    tbl_os_produto USING (os)
								JOIN    tbl_os_item USING (os_produto)
								WHERE   posto        = $login_posto
								AND     tbl_os_item.pedido= $pedido
								AND     tbl_os_item.peca         = $peca";
							$resnf = pg_exec ($con,$sql);
							if(pg_numrows($resnf) >0){
								$nf   = trim(pg_result($resnf,0,nota_fiscal_saida));
								$link = 0;
							}else{
								$nf = "Pendente";
								$link = 0;
							}
						}
					}else{
						$nf = $nota_fiscal;
						$link = 0;
					}
					
					if (strlen($sua_os) == 0) $sua_os = $os;
					
					echo "<tr bgcolor='$cor'>";
					//if ($condicao == "Garantia") {
					if (strpos($condicao,"Garantia") !== false) {
						echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='os_press.php?os=$os' target='_new'>$sua_os</a></font></td>";
					}
					echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>";
					if (strtolower($nf) <> 'pendente'){
						if ($link == 1) {
							echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca' target='_blank'>$nf</a>";
						}else{
							$sql  = "SELECT * FROM tbl_pedido_cancelado WHERE pedido=$pedido AND peca=$peca";
							$resY = pg_exec ($con,$sql);
							if (pg_numrows ($resY) > 0) {
								$nf = "Cancelado";
							}
							echo "$nf";
						}
							
					}else{
						$sql  = "SELECT * FROM tbl_pedido_cancelado WHERE pedido=$pedido AND peca=$peca";
						$resY = pg_exec ($con,$sql);
						if (pg_numrows ($resY) > 0) {
							$nf = "Cancelado";
						}
						echo "$nf &nbsp;";
					}
					echo "</font></td>";
					echo "<td align='left'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$peca_descricao</font></td>";
					echo "</tr>";
				}
				echo "</table>";
			}
		}
	}
	?>
	</td>
</tr>
</table>

<p>

<?
if (strlen ($cook_pedido) > 0 and $lista_pedido_suframa == "sim") {
	$sql = "SELECT  tbl_pedido.pedido                                ,
					tbl_pedido.condicao                              ,
					tbl_pedido.tabela                                ,
					tbl_pedido.pedido_cliente                        ,
					tbl_condicao.descricao      AS condicao_descricao,
					tbl_tabela.tabela                                ,
					tbl_tabela.descricao        AS tabela_descricao
			FROM    tbl_pedido
			LEFT JOIN tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
			LEFT JOIN tbl_tabela   ON tbl_tabela.tabela     = tbl_pedido.tabela
			WHERE   tbl_pedido.pedido_suframa = $cook_pedido
			AND     tbl_pedido.posto          = $login_posto
			AND     tbl_pedido.fabrica        = $login_fabrica;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) > 0) {
		$pedido           = trim(pg_result ($res,0,pedido));
		$condicao         = trim(pg_result ($res,0,condicao_descricao));
		$tabela           = trim(pg_result ($res,0,tabela));
		$tabela_descricao = trim(pg_result ($res,0,tabela_descricao));
		$pedido_cliente   = trim(pg_result ($res,0,pedido_cliente));

		
		if ($condicao == "Garantia") $detalhar = "ok";
?>
<br>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="center">
		<table width="650" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Pedido</b>
				<br>
				<? if ($login_fabrica == 1 ) echo $pedido_suframa; else echo $pedido; ?>
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
		</tr>
		</table>
		
		<table width="650" border="0" cellspacing="3" cellpadding="0" align='center'>
		<tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>
			<td align='left'>Componente</td>
			<td align='center'>Qtde</td>
			<td align='center'>Preço</td>
			<td align='center'>Total</td>
		</tr>
		
		<?
		$sql = "SELECT  tbl_pedido_item.peca,
						tbl_peca.referencia ,
						tbl_peca.descricao  ,
						tbl_pedido_item.qtde
				FROM  tbl_pedido
				JOIN  tbl_pedido_item USING (pedido)
				JOIN  tbl_peca        USING (peca)
				WHERE tbl_pedido_item.pedido = $pedido
				ORDER BY tbl_pedido_item.pedido_item;";
		$res = pg_exec ($con,$sql);
		$total_pedido = 0 ;
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			
			$peca           = pg_result ($res,$i,peca);
			$peca_descricao = pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao);
			$qtde           = pg_result ($res,$i,qtde);
			
			$sql  = "SELECT tbl_tabela_item.preco
					FROM    tbl_tabela_item
					WHERE   tbl_tabela_item.tabela = $tabela
					AND     tbl_tabela_item.peca   = $peca;";
			$resT = pg_exec ($con,$sql);
			
			if (pg_numrows ($resT) > 0) {
				$preco = pg_result ($resT,0,0);
				$total = $preco * pg_result ($res,$i,qtde);
				$total_pedido += $total ;
				$preco = number_format ($preco,2,",",".");
				$total = number_format ($total,2,",",".");
			}else{
				$preco = "***";
				$total = "***";
			}
		?>
		<tr bgcolor='<?echo $cor?>' style='color: #000000 ; font-size:12px; text-align:center '>
			<td align='left'><? echo $peca_descricao ?></td>
			<td align='right'><? echo $qtde ?></td>
			<td align='right'><? echo $preco ?></td>
			<td align='right'><? echo $total ?></td>
		</tr>
		<?
		}
		?>

		<tr>
		<td colspan='3' bgcolor='#cccccc' align='center'><b>TOTAL</b></td>
		<td bgcolor='#cccccc' align='right' nowrap><b><? echo number_format ($total_pedido,2,",","."); ?></b></td>
		</tr>
		</table>
		
	<?
	if ($detalhar == "ok") {
		echo "<br>";
		
		#OS não é ligado com pedidos do tipo LOCADOR
		#HD 15114
		if ($tipo_pedido<>94){
			$sql = "SELECT  distinct
							lpad(tbl_os.sua_os::text,10,'0'),
							tbl_peca.peca      ,
							tbl_peca.referencia,
							tbl_peca.descricao ,
							tbl_os.os          ,
							tbl_os.sua_os      ,
							tbl_os_item_nf.nota_fiscal
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
			
			if (pg_numrows($res) > 0) {
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
					$peca           = pg_result ($res,$i,peca);
					$os             = pg_result ($res,$i,os);
					$sua_os         = pg_result ($res,$i,sua_os);
					$peca_descricao = pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao);
					$nota_fiscal    = pg_result ($res,$i,nota_fiscal);
					
					$cor = "#FFFFFF";
					if ($i % 2 == 0) $cor = '#F1F4FA';
					
					if ($i == 0) {
						echo "<table width='650' border='0' cellspacing='5' cellpadding='0' align='center'>";
						if (strlen($os) > 0) {
							echo "<tr bgcolor='#C0C0C0'>";
							echo "<td align='center' colspan='3'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Ordens de Serviço que geraram o pedido acima</b></font></td>";
							echo "</tr>";
						}
						echo "<tr bgcolor='#C0C0C0'>";
						//if ($condicao == "Garantia") {
						if (strpos($condicao,"Garantia") !== false) {
							echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Sua OS</b></font></td>";
						}
						echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Nota Fiscal</b></font></td>";
						echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Peça</b></font></td>";
						echo "</tr>";
					}
					
					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento.pedido    = $pedido
							AND     tbl_faturamento_item.peca = $peca;";
					$resx = pg_exec ($con,$sql);
					
					if (strlen ($nota_fiscal) == 0) {
						if (pg_numrows ($resx) > 0) {
							$nf = trim(pg_result($resx,0,nota_fiscal));
							$link = 0;
						}else{
							$nf = "Pendente";
							$link = 0;
						}
					}else{
						$nf = $nota_fiscal;
						$link = 0;
					}
					
					if (strlen($sua_os) == 0) $sua_os = $os;
					
					echo "<tr bgcolor='$cor'>";
					//if ($condicao == "Garantia") {
					if (strpos($condicao,"Garantia") !== false) {
						echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='os_press.php?os=$os' target='_new'>$sua_os</a></font></td>";
					}
					echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>";
					if (strtolower($nf) <> 'pendente'){
						if ($link == 1) 
							echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca' target='_blank'>$nf</a>";
						else
							echo "$nf";
					}else{
						echo "$nf &nbsp;";
					}
					echo "</font></td>";
					echo "<td align='left'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$peca_descricao</font></td>";
					echo "</tr>";
				}
				echo "</table>";
			}
		}
	}
	?>
	</td>
</tr>
</table>

<?
	}
}
?>

<!------------ Posição do Pedidos ------------------- -->
<?
/*$mostrar_pendencia = 0;

if ($login_fabrica == 1 and $status_pedido !=4 and ($tipo_pedido != 86 OR $pedido_sedex=='t')){
	$mostrar_pendencia = 1;
}*/


$sql = "SELECT	tbl_pedido_item.qtde         ,
				tbl_pedido_item.qtde_faturada,
				tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada - tbl_pedido_item.qtde_cancelada AS qtde_pendente,
				tbl_pedido_item.qtde_cancelada,
				tbl_peca.peca                ,
				tbl_peca.referencia          ,
				tbl_peca.descricao 			,
				tbl_peca.parametros_adicionais,
				tbl_pedido_item.obs
		FROM    tbl_pedido
		JOIN    tbl_pedido_item      ON tbl_pedido_item.pedido = tbl_pedido.pedido
		JOIN    tbl_peca             ON tbl_peca.peca          = tbl_pedido_item.peca
		WHERE   tbl_pedido.pedido = $pedido
		ORDER   BY  qtde_pendente DESC, tbl_pedido_item.pedido_item";
$res = pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	if ($i == 0) {
		echo "<table width='650' align='center' border='0' cellspacing='3'>";
		echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-weight:bold ; text-align:center ' >";
		echo "<td colspan='8'>Posição deste pedido</td>";
		echo "</tr>";
		echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>";
		echo "<td align='left'>Componente</td>";
		echo "<td>Qtde<br>Pedida</td>";
		echo "<td>Qtde<br>Faturada</td>";
		//if ($mostrar_pendencia == 1){
			echo "<td>Qtde<br>Pendente</td>";
		//}
		echo "<td>Previsão</td>";
		echo "</tr>";
	}

		$parametros_adicionais = pg_result ($res,$i,parametros_adicionais);
		$parametros_adicionais = json_decode($parametros_adicionais,true);

		$qtde_faturada = pg_result($res,$i,qtde_faturada);
		$qtde 		   = pg_result($res,$i,qtde);
		
		$estoque 	= $parametros_adicionais['estoque'];
		$previsao 	= $parametros_adicionais['previsao'];

		$estoque 	= ucfirst($parametros_adicionais["estoque"]);
	    $previsao 	= mostra_data($parametros_adicionais["previsao"]);
		$estoque = strtoupper($estoque); 
	    if($estoque == "DISPONIVEL" or $estoque == "DISPONÍVEL"){
	    	if($qtde_faturada == $qtde){
	    		$previsao = "Faturada";
	    	}else{
	    		$previsao = "<font face='arial' size='-2'>$estoque </font>";
	    	}
	    }	  

	$cor = "#FFFFFF";
	$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
	
	echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left' >";
	echo "<td nowrap>" . pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao) . "</td>";
	echo "<td align='right'>" . pg_result ($res,$i,qtde) . "</td>";
	echo "<td align='right'>" . pg_result ($res,$i,qtde_faturada) . "</td>";
	echo "<td align='right'>" . pg_result ($res,$i,qtde_pendente) . "</td>";

	echo "<td align='right'>" . $previsao . "</td>";
	
	echo "</tr>";
	
	if($login_fabrica == 1) {

		$qtde_cancelada = pg_result($res, $i, "qtde_cancelada");

		echo "<tr bgcolor='$cor' style='font-size:10px; color:#000000; text-align:left'>";			
		echo "<td class='titulo_coluna' style='background-color:#CCC;font: bold 11px;Arial;color:black;text-align:center;font-size: 15px;'>Observações</td>";
		echo "<td colspan='4' nowrap>";

		if ($qtde_cancelada > 0)
		{
			
			$sql_motivo = "SELECT tbl_peca.referencia,
									tbl_peca.descricao,
									tbl_pedido_cancelado.qtde,
									tbl_pedido_cancelado.motivo,
									to_char (tbl_pedido_cancelado.data,'DD/MM/YYYY') AS data,
									tbl_admin.login,
									tbl_os.sua_os
							FROM tbl_pedido_cancelado
							JOIN tbl_peca USING (peca)
							LEFT JOIN tbl_os ON tbl_pedido_cancelado.os = tbl_os.os
							LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_pedido_cancelado.admin
							WHERE tbl_pedido_cancelado.pedido  = $pedido
							AND   tbl_pedido_cancelado.fabrica = $login_fabrica
							ORDER BY tbl_peca.descricao";

			$res_motivo = pg_query($con, $sql_motivo);

			if (pg_num_rows($res_motivo) > 0) {

				$motivo = pg_result($res_motivo, 0, "motivo");

				echo "$motivo";
			}
		} else {
			echo (mb_check_encoding(pg_fetch_result ($res,$i,obs), "UTF-8")) ? utf8_decode(pg_fetch_result ($res,$i,obs)) : pg_fetch_result ($res,$i,obs);
		}
		echo "</td></tr>";
	}
	//}
}
echo "</table>";
echo "<br><br>";

echo "<table>";
				if($_GET['ver_log'] == true){
					echo "<tr>";
					echo "<button class='btn btn-warning' type='button' id='visualiza_log_item' data-pedido='$pedido' >Log Itens Pedido</button><br><br>";
					echo "</tr>";
				} 
				echo "<tr>";
					echo "<td align='center' bgcolor='#f4f4f4'><p align='center'>
						<font size='1'><b> A previsão informada refere-se a disponibilidade da peça na fábrica. Para entrega é necessário considerar o prazo de envio de acordo com sua região. <Br> Previsão sujeita a alteração.</b></font></p>
					</td>";
				echo "</tr>";
			echo "</table>";



?>

<!------------ Atendimento Direto de Pedidos ------------------- -->
<?

/*IGOR - HD 11103 COMO A BLACK NÃO IMPORTA FATURAMENTO, NÃO PRECISA MOSTRAR*/
if ($login_fabrica != 1 ) {
	$sql = "SELECT	tbl_faturamento.faturamento , 
					tbl_faturamento.nota_fiscal , 
					to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao , 
					tbl_faturamento_item.peca , 
					tbl_faturamento_item.qtde as qtde_fatura,
					tbl_pedido_item.qtde,
					tbl_peca.peca ,
					tbl_peca.referencia ,
					tbl_peca.descricao
			FROM    (
				SELECT *
				FROM   tbl_pedido_item
				WHERE  tbl_pedido_item.pedido = $pedido
			) tbl_pedido_item
			JOIN tbl_faturamento_item    ON tbl_pedido_item.pedido      = tbl_faturamento_item.pedido
										AND tbl_pedido_item.peca        = tbl_faturamento_item.peca
			JOIN    tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
			JOIN    tbl_peca             ON tbl_pedido_item.peca        = tbl_peca.peca
			ORDER   BY tbl_pedido_item.pedido_item";


	$sql = "SELECT  DISTINCT
					tbl_pedido_item.pedido_item ,
					tbl_faturamento.nota_fiscal ,
					to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao , 
					tbl_faturamento_item.peca , 
					tbl_faturamento_item.qtde as qtde_fatura,
					tbl_pedido_item.qtde,
					tbl_peca.peca ,
					tbl_peca.referencia ,
					tbl_peca.descricao
			FROM    tbl_pedido_item
			JOIN    tbl_faturamento_item     ON tbl_pedido_item.pedido      = tbl_faturamento_item.pedido
											AND tbl_pedido_item.peca        = tbl_faturamento_item.peca
			JOIN    tbl_faturamento          ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
			JOIN    tbl_peca                 ON tbl_pedido_item.peca        = tbl_peca.peca
			WHERE   tbl_pedido_item.pedido = $pedido
			ORDER   BY tbl_pedido_item.pedido_item";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		if ($i == 0) {
			echo "<table width='650' align='center' border='0' cellspacing='3'>";
			echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-weight:bold ; text-align:center ' >";
			echo "<td colspan='6'>Notas Fiscais que atenderam a este pedido</td>";
			echo "</tr>";
			echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>";
			echo "<td nowrap>Nota<br>Fiscal</td>";
			echo "<td>Data</td>";
			echo "<td align='left'>Componente</td>";
			echo "<td>Qtde<br>Pedida</td>";
			echo "<td>Qtde<br>Faturada</td>";
			//echo "<td>Qtde<br>Pendente</td>";
			echo "</tr>";
		}
		
		$cor = "#FFFFFF";
		if ($i % 2 == 0) $cor = '#F1F4FA';
		$pendente = pg_result ($res,$i,qtde) - pg_result ($res,$i,qtde_fatura);
		
		//if ($pendente > 0) $cor = "#ff6666";
		
		echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left' >";
		echo "<td>" . pg_result ($res,$i,nota_fiscal) . "</td>";
		echo "<td>" . pg_result ($res,$i,emissao) . "</td>";
		echo "<td nowrap>" . pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao) . "</td>";
		echo "<td align='right'>" . pg_result ($res,$i,qtde) . "</td>";
		echo "<td align='right'>" . pg_result ($res,$i,qtde_fatura) . "</td>";
		//echo "<td align='right'>$pendente</td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br>";
}

?>

<!------------EMBARQUES ------------------- -->
<?
$sql = "SELECT 
					tbl_pendencia_bd_novo_nf.pedido,
					tbl_pendencia_bd_novo_nf.referencia_peca,
					to_char(tbl_pendencia_bd_novo_nf.data,'DD/MM/YYYY') as data,
					tbl_pendencia_bd_novo_nf.qtde_embarcada,
					tbl_pendencia_bd_novo_nf.nota_fiscal,
					tbl_pendencia_bd_novo_nf.transportadora_nome,
					tbl_pendencia_bd_novo_nf.conhecimento
				FROM tbl_pendencia_bd_novo_nf
				WHERE posto=$login_posto
				AND   pedido = '$pedido'
				ORDER BY pedido,tbl_pendencia_bd_novo_nf.data DESC;
			";
//echo nl2br($sql);
$res = pg_exec($con,$sql);
$resultado = pg_numrows($res);

for ($i = 0 ; $i < $resultado ; $i++) {
	if ($i == 0) {
		echo "<table width='650' align='center' border='0' cellspacing='3'>";
		echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-weight:bold ; text-align:center ' >";
		echo "<td colspan='6'>Embarques</td>";
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
				WHERE tbl_ordem_programada_pedido_black.pedido = $pedido
				ORDER BY tbl_ordem_programada_pedido_black.pedido,tbl_ordem_programada_pedido_black.pedido_data, qtde_pendente DESC";
	
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
		echo "<td>Qtde<br>Pendente</td>";
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
	echo "<td align='center'>$qtde_pendente</td>";
	echo "<td align='center'>$nota_fiscal</td>";
	echo "<td align='center'>$data_nota</td>";

	echo "<td align='left'>$transportadora_nome</td>";
	echo "<td align='right'>$conhecimento</td>";
	echo "</tr>";
}
echo "</table>";

echo "<br>";
?>
<? include "rodape.php"; ?>
