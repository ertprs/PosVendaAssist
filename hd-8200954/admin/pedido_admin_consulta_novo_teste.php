<?php
	include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include "autentica_admin.php";
    include "funcoes.php";

	if($_GET){
		$pedido = $_GET["pedido"];
		$sql = "
		SELECT
		tbl_pedido.pedido

		FROM
		tbl_pedido

		WHERE 
		tbl_pedido.pedido=$pedido
		AND tbl_pedido.fabrica=$login_fabrica
		";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
		}
		else {
			$msg_erro = "Pedido não encontrado";
		}
	}

	else {
			$msg_erro = "Pedido não encontrado";
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
			tbl_status_pedido.descricao                                                   ,
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
			postoA.nome                              AS nome_posto                        ,
			distribuidor.nome                        AS nome_distribuidor                 ,
			tbl_pedido.status_fabricante                                                  ,
			tbl_pedido.origem_cliente                                                     ,
			tbl_pedido.transportadora                                                     ,
			tbl_pedido.tipo_frete                                                         ,
			tbl_pedido.valor_frete                                                        ,
			tbl_pedido.pedido_os                                                          ,
			tbl_linha.nome AS nome_linha

			$sql_admin_select
		FROM    tbl_pedido
		JOIN    tbl_posto postoA                      ON postoA.posto             = tbl_pedido.posto 
		JOIN tbl_posto distribuidor ON distribuidor.posto = tbl_pedido.distribuidor
		LEFT JOIN tbl_posto_fabrica            ON tbl_posto_fabrica.posto     = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN tbl_condicao                 ON tbl_condicao.condicao       = tbl_pedido.condicao
		LEFT JOIN tbl_tipo_pedido              ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
		LEFT JOIN tbl_tabela                   ON tbl_tabela.tabela           = tbl_pedido.tabela
		LEFT JOIN tbl_admin                    ON tbl_pedido.admin            = tbl_admin.admin
		LEFT JOIN tbl_status_pedido            ON tbl_pedido.status_pedido    = 
		tbl_status_pedido.status_pedido
		LEFT JOIN tbl_linha                    ON tbl_pedido.linha            = 
		tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
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
		$status_pedido       = trim(pg_fetch_result ($res,0,descricao));
		$distribuidor        = trim(pg_fetch_result ($res,0,nome_distribuidor));
		$status_fabricante   = trim(pg_fetch_result ($res,0,status_fabricante));
		$origem_cliente     = trim(pg_fetch_result ($res,$i,origem_cliente));
		$pedido_os          = trim(pg_fetch_result ($res,$i,pedido_os));
		$transportadora     = trim(pg_fetch_result ($res,0,transportadora));
		$tipo_frete         = trim(pg_fetch_result ($res,$i,tipo_frete));
		$valor_frete        = trim(pg_fetch_result ($res,$i,valor_frete));
		$linha        = trim(pg_fetch_result ($res,$i,nome_linha));

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

?>

<?php
	$title = "PEDIDO DE PEÇAS";
	include "cabecalho.php";	
    include "javascript_pesquisas.php";
	include "javascript_calendario.php";
?>

<script type='text/javascript' src='js/bibliotecaAJAX.js'></script>
<script language="JavaScript">

$(document).ready(function(){
});

function mostra_qtde(pedido,peca) {
	var campo = "dados"+peca;

	if(document.getElementById(campo).style.display == "none")
		$("#dados" + peca).css("display", "table-cell");
	else
		$("#dados" + peca).css("display", "none");

	url = "pedido_admin_consulta_novo_ajax_teste.php?acao=pesquisaros&pedido="+pedido+"&peca="+peca;
	requisicaoHTTP("GET", url, true , "mostra_qtde_retorno", peca);
}

function mostra_qtde_retorno(retorno, peca) {
	partes = retorno.split("|")
	$("#dados" + peca).html(partes[2]);
}

function mostra_faturada(pedido,peca) {

	var campo = "dados"+peca;

	if(document.getElementById(campo).style.display == "none")
		$("#dados" + peca).css("display", "table-cell");
	else
		$("#dados" + peca).css("display", "none");

	url = "pedido_admin_consulta_novo_ajax_teste.php?acao=pesquisarosfaturada&pedido="+pedido+"&peca="+peca;
	requisicaoHTTP("GET", url, true , "mostra_retorno_faturada", peca);

}
function mostra_retorno_faturada(retorno, peca) {
	partes = retorno.split("|")
	$("#dados" + peca).html(partes[2]);
}

function mostra_cancelada(pedido,peca) {

	var campo = "dados"+peca;

	if(document.getElementById(campo).style.display == "none")
		$("#dados" + peca).css("display", "table-cell");
	else
		$("#dados" + peca).css("display", "none");

	url = "pedido_admin_consulta_novo_ajax_teste.php?acao=pesquisarpecacancelada&pedido="+pedido+"&peca="+peca;
	requisicaoHTTP("GET", url, true , "mostra_retorno_cancelada", peca);

}
function mostra_retorno_cancelada(retorno, peca) {
	partes = retorno.split("|")
	$("#dados" + peca).html(partes[2]);
}

function cancela_item(pedido_item,pedido,qtde,motivo,peca,posto,os) {

	$("#dados" + peca).css("display", "table-cell");

	url = "pedido_admin_consulta_novo_ajax_teste.php?acao=cancela_item&pedido_item="+pedido_item+"&pedido="+pedido+"&qtde="+qtde+"&motivo="+motivo+"&peca="+peca+"&posto="+posto+"&os="+os;
	requisicaoHTTP("GET", url, true , "mostra_qtde_retorno", peca);

}

</script>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco{
	padding-left:50px;
}

</style>
<?php

if ($login_fabrica <> 15) {
	echo "<div class='texto_avulso' style='width:700px;'><b>Atenção:&nbsp;</b>Pedidos a prazo dependerão de análise do departamento de crédito.</div><br />";
}


if(strlen($msg_erro)>0){
	echo "<center>$msg_erro</center>";
}
else{ ?>
<table width="700" border="0" cellspacing="5" cellpadding="0" class='formulario' align='center'>
	<tr class='titulo_tabela'>
		<td colspan='100%'>Dados do Pedido</td>
	</tr>
				<tr>
					<td nowrap class='espaco'>
						<b>Pedido</b>
						<br>
						<?
						echo ($login_fabrica == 1) ? $pedido_blackedecker : $pedido;
						?>
					</td>
					<? if(strlen($status_pedido) > 0 ){ ?>
						<td nowrap width='205'>
							<b>Status Pedido</b>
							<br>
							<?
							echo $status_pedido;
							?>
						</td>
					<? } ?>
					<? if (strlen($pedido_cliente) > 0) { ?>
						<td nowrap >
							<b>Pedido Cliente</b>
							<br>
							<?=$pedido_cliente?>
						</td>
					<? } ?>
					<td nowrap>
						<b>Condição Pagamento</b>
						<br>
						<?=$condicao?>
					</td>
				</tr>

				<tr>
					<td nowrap width='154' class='espaco'>
						<b>Tabela de Preços</b>
						<br>
						<?=$tabela_descricao?>
					</td>
				
					<td nowrap>
						<b>Responsável</b>
						<br>
						<?echo strtoupper ($login) ?>
					</td>

					<? if (strlen($linha) > 0) { ?>
						<td nowrap >
							<b>Linha</b>
							<br>
							<?=$linha?>
						</td>
					<? } ?>
				</tr>

			</table>
			
			<table width="700" border="0" cellspacing="5" cellpadding="0" class='formulario' align='center'>
				<tr>
						
					<? //HD 11871 Paulo
					if ($login_fabrica==24 and strlen($login_alteracao) > 0){?>
							<td nowrap class='espaco'>
								<b>Alterado Por</b>
								<br>
								<?echo strtoupper ($login_alteracao) ?>
							</td>
					<?}?>

					<?if ($login_fabrica==15) { # HD 117922?>
					<td nowrap class='espaco'>
						<strong>Pedido</strong>
						<br /><?=$pedido;?>
					</td>
					<? } ?>
					<td nowrap width='154' <? if ($login_fabrica<>15 AND $login_fabrica<>24) echo "class='espaco'";?>>
						<strong>Posto</strong>
						<br />
						<?=$codigo_posto?>
					</td>
					<td nowrap width='205'>
						<strong>Razão Social</strong>
						<br/>
						<?=$nome_posto?>
					</td>

					<td nowrap >
						<strong>Distribuidor</strong>
						<br/>
						<?=$distribuidor?>
					</td>
				</tr>

				<tr>
					<td nowrap class='espaco'>
						<strong>Data</strong>
						<br/>
						<?=$data_pedido?>
						&nbsp;
					</td>
					<? if(strlen($data_exportado) > 0){ ?>
						<td nowrap >
							<strong>Data Exportação</strong>
							<br/>
							<?=$data_exportado?>
							&nbsp;
						</td>
					<? } ?>
					<td nowrap >
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
				<table width="700" border="0" cellspacing="5" cellpadding="0" class='formulario' align='center'>
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

			<?}
}
			?>
	
			<br />

<?php
	if($_GET and strlen($msg_erro)==0){ 
		
		$sql = "
		SELECT
		tbl_peca.peca,
		tbl_peca.referencia,
		tbl_peca.descricao,
		tbl_peca.ipi, /* ISSO AQUI ESTÁ ERRADO, VOU PEGAR DAQUI PORQUE NÃO TEM NOS ITENS DOS PEDIDOS. O IPI DEVERIA FICAR GRAVADO NO PEDIDO, PRINCIPALMENTE SE JÁ FOI FATURADO */
		SUM(tbl_pedido_item.qtde) AS qtde,
		SUM(tbl_pedido_item.qtde_cancelada) AS qtde_cancelada,
		SUM(tbl_pedido_item.qtde_faturada) AS qtde_faturada,
		SUM(tbl_pedido_item.qtde_faturada_distribuidor) AS qtde_faturada_distribuidor,
		MAX(tbl_pedido_item.preco) AS preco /* O PREÇO VAI SER SEMPRE IGUAL PARA A MESMA PEÇA NO MESMO PEDIDO */

		FROM
		tbl_pedido_item
		JOIN tbl_peca ON tbl_pedido_item.peca=tbl_peca.peca

		WHERE
		tbl_pedido_item.pedido=$pedido

		GROUP BY
		tbl_peca.peca,
		tbl_peca.referencia,
		tbl_peca.descricao,
		tbl_peca.ipi

		ORDER BY
		tbl_peca.descricao,
		tbl_peca.peca
		";
		$res = pg_query($con, $sql);
?>
	<table width="700" align="center" cellspacing="1" class="tabela">
		<tr class="titulo_coluna">
			<td align='left'>Componente</td>
			<td>Qtde</td>
			<td>Canc</td>
			<td>Fat</td>
			<td>Pen</td>
			<td>IPI</td>
			<td>Preço</td>
			<td>Total</td>
		</tr>

		<?
		$num_rows = pg_num_rows($res);

		for($i=0;$i<$num_rows;$i++){
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			extract(pg_fetch_array($res));

			if ($qtde_faturada_distribuidor > 0) $qtde_faturada = $qtde_faturada_distribuidor;
			$qtde_pendente = $qtde - $qtde_cancelada - $qtde_faturada;
			$total = $preco * (1 + $ipi/100);

			echo "
			<tr bgcolor='$cor'>
				<td align='left'>$referencia - $descricao</td>
				<td>$qtde <img src='imagens/mais.bmp' onclick='mostra_qtde($pedido,$peca);' style='cursor:pointer' id='img$pedido_item'></td>
				<td>$qtde_cancelada <img src='imagens/mais.bmp' onclick='mostra_cancelada($pedido,$peca);' style='cursor:pointer'></td>
				<td>$qtde_faturada <img src='imagens/mais.bmp' onclick='mostra_faturada($pedido,$peca);' style='cursor:pointer'></td>
				<td>$qtde_pendente</td>
				<td>$ipi%</td>
				<td>$preco</td>
				<td>$total</td>
			</tr>
			<tr>
				<td colspan='8' style='display:none;' id='dados$peca'>
				
				</td>
			</tr>
			";
		}
		?>
	</table>
<?php
	}
?>
<?php
	include "rodape.php";
?>