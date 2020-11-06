<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";

include 'autentica_admin.php';
include 'funcoes.php';

$codigo       = $_GET['codigo'];

if(!empty($codigo)){
	$sql = "SELECT  tbl_aquarius_hd_chamado.codigo,
					to_char(tbl_aquarius_hd_chamado.datacadastro,'DD/MM/YYYY') AS data_abertura,
					nome         ,
					cpfcnpj      ,
					contato      ,
					email        ,
					endereco     ,
					bairro       ,
					cidade       ,
					estado       ,
					telefone     ,
					celular      ,
					tbl_aquarius_hd_chamado.data         ,
					horaini      ,
					horafim      ,
					origemticket ,
					status       ,
					tbl_aquarius_hd_chamado.usuario      ,
					acao         ,
					dispositivo      ,
					modelocanal      ,
					mtc              ,
					rma              ,
					localcompra      ,
					serie            ,
					resumoproblema   ,
					logisticareversa ,
					detalhesproblema ,
					obsinterna       ,
					tbl_aquarius_hd_item.usuario      as atendente ,
					tbl_aquarius_hd_item.data             ,
					hora             ,
					npedido          ,
					nnotafiscal      ,
					to_char(data_hora, 'DD/MM/YYYY HH24:MI') as data_hora
				FROM tbl_aquarius_hd_chamado 
		LEFT JOIN tbl_aquarius_hd_item ON tbl_aquarius_hd_chamado.codigo = tbl_aquarius_hd_item.codigo
		WHERE tbl_aquarius_hd_chamado.codigo = {$codigo}";
	$resSS = pg_query($con,$sql);
}



$layout_menu = "callcenter";
$title = "INFORMAÇÕES DE ATENDIMENTO";
include_once "cabecalho_new.php";

	
	if (pg_num_rows($resSS) > 0) {
		//$data_compra = explode(" ", pg_fetch_result($res, 0, 'data_compra'));
		//list($y,$m,$d) = explode("-", $data_compra[0]);
		//$data_compra = "$d/$m/$y";

?>

<style type="text/css">
	.titulo_coluna{
		background-color: #FFFFFF;
		color: #000000;
	}
</style>

	<table align="center" id="resultado_os" class='table table-bordered table-large' >

		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Atendimento - <?=$codigo?></td>
		</tr>

		<tr>
			<td class='titulo_coluna' width="100">Responsável</td>
			<td width="150" class="tac" style=""><?=pg_fetch_result($resSS, 0, 'usuario')?></td>
			<td class='titulo_coluna' width="100">Data Abertura</td>
			<td><?=pg_fetch_result($resSS, 0, 'data_abertura')?></td>
		</tr> 
		<tr>
			<td class='titulo_coluna' width="100">Anexos</td>
			<td colspan='2'>
			<?
			$sqla = "SELECT documento FROM tbl_aquarius_hd_anexo where codigo = $codigo";
			$resa = pg_query($con, $sqla); 	
			for($i=0; $i< pg_num_rows($resa); $i++) {
				$documento = pg_fetch_result($resa,$i, 'documento') ;
				$j++;
				echo "$j. - <a href= '$documento' target='_blank'><img src='imagens/clip.png'></a>  ";
			} ?>
			</td>
		</tr> 
	</table>

	<table align="center" id="resultado_os" class='table table-bordered table-large' >
	
		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Informações do Cliente</td>
		</tr>

		<tr>
			<td class='titulo_coluna'>Nome</td>
			<td nowrap><?=pg_fetch_result($resSS, 0, 'nome')?></td>
			<td class='titulo_coluna'>CPF/CNPJ</td>
			<td colspan="3"><?=pg_fetch_result($resSS, 0, 'cpfcnpj')?></td>			
		</tr>

		<tr>
			<td class='titulo_coluna'>E-mail</td>
			<td nowrap><?=pg_fetch_result($resSS, 0, 'email')?></td>
			<td class='titulo_coluna'>CELULAR</td>
			<td colspan="3" nowrap><?=pg_fetch_result($resSS, 0, 'celular')?></td>	
		</tr>

		<tr>	
			<td class='titulo_coluna'>Endereço</td>
			<td ><?=utf8_decode(pg_fetch_result($resSS, 0, 'endereco'))?></td>			
			<td class='titulo_coluna'>Bairro</td>
			<td ><?=utf8_decode(pg_fetch_result($resSS, 0, 'bairro'))?></td>	
		</tr>
	
		<tr>
			<td class="titulo_coluna">Cidade</td>
			<td><?=utf8_decode(pg_fetch_result($resSS,0,'cidade'))?></td>
			<td class='titulo_coluna'>Estado</td>
			<td colspan="3"><?=pg_fetch_result($resSS, 0, 'estado')?></td>
			
		</tr>
	</table>

	<table align="center" id="resultado_os" class='table table-bordered table-large' >

		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Informações do Produto</td>
		</tr>

		<tr>
			<td class='titulo_coluna'>Produto</td>
			<td ><?=pg_fetch_result($resSS, 0, 'dispositivo')?></td>
			<td class='titulo_coluna'>Local compra</td>
			<td><?=pg_fetch_result($resSS, 0, 'localcompra')?></td>	
			<td class='titulo_coluna' nowrap>Serie</td>
			<td><?=pg_fetch_result($resSS, 0, 'serie')?></td>			
		</tr>
	</table>
		

	<table align="center" id="resultado_os" class='table table-bordered table-large' >
		
		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Histórico</td>
		</tr>
		<? for($i=0; $i< pg_num_rows($resSS); $i++) { ?>
		<tr>
			<td class='titulo_coluna'>Atendente</td>
			<td ><?=utf8_decode(pg_fetch_result($resSS, $i, 'atendente'))?></td>
			<td class='titulo_coluna'>Data</td>
			<td ><?=pg_fetch_result($resSS, $i, 'data_hora')?></td>
		</tr>

		<tr>
			<td class='titulo_coluna'>Resumo Problema</td>
			<td ><?=utf8_decode(pg_fetch_result($resSS, $i, 'resumoproblema'))?></td>
			<td class='titulo_coluna'>Detalhe Problema</td>
			<td ><?=utf8_decode(pg_fetch_result($resSS, $i, 'detalhesproblema'))?></td>
		</tr>
		<tr>
			<td class='titulo_coluna' colspan='2'>Obs. interna</td>
			<td colspan='2'><?=utf8_decode(pg_fetch_result($resSS, $i, 'obsinterna'))?></td>	
		</tr>
		<tr>
			<td colspan='100%'></td>
		</tr>
		<? } ?>
	</table>
		
<?php
		
	}


/* Rodapé */
	include 'rodape.php';
?>
