<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";

include 'autentica_admin.php';
include 'funcoes.php';

$protocolo       = $_GET['protocolo'];

if(!empty($protocolo)){
	$sql = "SELECT  tbl_mondial_hd_chamado.protocolo,
		to_char(tbl_mondial_hd_chamado.data_atendimento,'DD/MM/YYYY') AS data_abertura,
		tbl_mondial_hd_chamado.historico,
		tbl_mondial_hd_chamado.responsavel,
		tbl_mondial_hd_chamado.fantasia,
		tbl_mondial_hd_chamado.tipo_providencia,
		tbl_mondial_hd_chamado.produto,
		tbl_mondial_hd_chamado.posto,
		tbl_mondial_hd_chamado.os,
		to_char(tbl_mondial_hd_chamado.data_retorno,'DD/MM/YYYY') AS data_retorno,
		tbl_mondial_hd_chamado.acao,
		tbl_mondial_hd_chamado.data_encerramento,
		tbl_mondial_hd_chamado.pais AS consumidor_pais,
		tbl_mondial_hd_chamado.cep AS consumidor_cep,
		tbl_mondial_hd_chamado.endereco AS consumidor_endereco,
		tbl_mondial_hd_chamado.complemento AS consumidor_complemento,
		tbl_mondial_hd_chamado.numero AS consumidor_numero,
		tbl_mondial_hd_chamado.bairro AS consumidor_bairro,
		tbl_mondial_hd_chamado.uf AS consumidor_estado,
		tbl_mondial_hd_chamado.cidade AS consumidor_cidade,
		tbl_mondial_hd_chamado.pais_nome,
		tbl_mondial_hd_chamado.cpf AS consumidor_cpf,
		tbl_mondial_hd_chamado.email AS consumidor_email,
		tbl_mondial_hd_chamado.consumidor AS conumidor_nome,
		tbl_mondial_hd_chamado_extra.responsavel AS resp_troca_recompra,
		tbl_mondial_hd_chamado_extra.motivo_troca,
		tbl_mondial_hd_chamado_extra.os AS os_troca_recompra,
		tbl_mondial_hd_chamado_extra.produto AS produto_troca_recompra,
		tbl_mondial_hd_chamado_extra.posto AS posto_troca_recompra,
		tbl_mondial_hd_chamado_extra.descricao_recompra,
		tbl_mondial_hd_chamado_extra.nome AS nome_troca_recompra,
		to_char(tbl_mondial_hd_chamado_extra.data_nota_fiscal, 'DD/MM/YYYY') AS data_nota_fiscal,
		tbl_mondial_hd_chamado_extra.nome_nota_fiscal,
		tbl_mondial_hd_chamado_extra.doc_nota_fiscal,
		tbl_mondial_hd_chamado_extra.uf AS estado_troca_recompra,
		tbl_mondial_hd_chamado_extra.pais AS pais_troca_recompra,
		tbl_mondial_hd_chamado_extra.cep AS cep_troca_recompra,
		tbl_mondial_hd_chamado_extra.tipo_endereco,
		tbl_mondial_hd_chamado_extra.endereco AS endereco_troca_recompra,
		tbl_mondial_hd_chamado_extra.numero AS numero_troca_recompra,
		tbl_mondial_hd_chamado_extra.complemento AS complemento_troca_recompra,
		tbl_mondial_hd_chamado_extra.bairro AS bairro_troca_recompra,
		tbl_mondial_hd_chamado_extra.municipio AS municipio_troca_recompra,
		tbl_mondial_hd_chamado_extra.telefone AS telefone_troca_recompra,
		tbl_mondial_hd_chamado_extra.condicao AS condicao_troca_recompra,
		tbl_mondial_hd_chamado_extra.produto_troca,
		tbl_mondial_hd_chamado_extra.qtde,
		tbl_mondial_hd_chamado_extra.obs,
		tbl_mondial_hd_chamado_extra.banco,
		tbl_mondial_hd_chamado_extra.agencia,
		tbl_mondial_hd_chamado_extra.digito_agencia,
		tbl_mondial_hd_chamado_extra.conta,
		tbl_mondial_hd_chamado_extra.digito_conta,
		tbl_mondial_hd_chamado_extra.tipo_conta,
		tbl_mondial_hd_chamado_extra.operacao,
		tbl_mondial_hd_chamado_extra.transportadora,
		tbl_mondial_hd_chamado_extra.frete,
		tbl_mondial_hd_chamado_extra.valor,
		tbl_mondial_hd_chamado_extra.data_recompra,
		tbl_mondial_hd_chamado_extra.correcao_monetaria,
		tbl_mondial_hd_chamado_extra.indenizacao,
		tbl_mondial_hd_chamado_extra.outros,
		tbl_mondial_hd_chamado_extra.multa,
		tbl_mondial_hd_chamado_extra.valor_total,
		tbl_mondial_hd_chamado_extra.forma_pagamento,
		tbl_mondial_hd_chamado_extra.tipo
		FROM tbl_mondial_hd_chamado 
		LEFT JOIN tbl_mondial_hd_chamado_extra ON tbl_mondial_hd_chamado.protocolo = tbl_mondial_hd_chamado_extra.protocolo
		WHERE tbl_mondial_hd_chamado.protocolo = {$protocolo}";
	$resSS = pg_query($con,$sql);
}



$layout_menu = "callcenter";
$title = "INFORMAÇÕES DE ATENDIMENTO";
include_once "cabecalho_new.php";

	
	if (pg_num_rows($resSS) > 0) {
		$res = $resSS;
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
			<td class='titulo_tabela tac' colspan='100%'>Protocolo - <?=$protocolo?></td>
		</tr>

		<tr>
			<td class='titulo_coluna' width="100">Responsável</td>
			<td width="150" class="tac" style=""><?=pg_fetch_result($res, 0, 'responsavel')?></td>
			<td class='titulo_coluna' width="100">Data Abertura</td>
			<td><?=pg_fetch_result($res, 0, 'data_abertura')?></td>
			<td class='titulo_coluna' width="100">Tipo Atendimento</td>
			<td><?=pg_fetch_result($res, 0, 'tipo')?></td>
		</tr> 
		<tr>
			<td class='titulo_coluna'>Providência</td>
			<td colspan="3"><?=pg_fetch_result($res,0,'tipo_providencia')?></td>
			<td class="titulo_coluna">OS</td>
			<td><?=pg_fetch_result($res,0,'os')?></td>
		</tr>
	</table>

	<table align="center" id="resultado_os" class='table table-bordered table-large' >
	
		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Informações do Cliente</td>
		</tr>

		<tr>
			<td class='titulo_coluna'>Nome</td>
			<td nowrap><?=pg_fetch_result($res, 0, 'conumidor_nome')?></td>
			<td class='titulo_coluna'>CPF/CNPJ</td>
			<td colspan="3"><?=pg_fetch_result($res, 0, 'consumidor_cpf')?></td>			
		</tr>

		<tr>
			<td class='titulo_coluna'>E-mail</td>
			<td nowrap><?=pg_fetch_result($res, 0, 'consumidor_email')?></td>
			<td class='titulo_coluna'>CEP</td>
			<td colspan="3" nowrap><?=pg_fetch_result($res, 0, 'consumidor_cep')?></td>	
		</tr>

		<tr>	
			<td class='titulo_coluna'>Endereço</td>
			<td ><?=pg_fetch_result($res, 0, 'consumidor_endereco')?></td>			
			<td class='titulo_coluna'>Número</td>
			<td ><?=pg_fetch_result($res, 0, 'consumidor_numero')?></td>				
			<td class='titulo_coluna'>Complemento</td>
			<td ><?=pg_fetch_result($res, 0, 'consumidor_complemento')?></td>	
		</tr>
	
		<tr>
			<td class="titulo_coluna">Cidade</td>
			<td><?=utf8_decode(pg_fetch_result($res,0,'consumidor_cidade'))?></td>
			<td class='titulo_coluna'>Estado</td>
			<td colspan="3"><?=pg_fetch_result($res, 0, 'consumidor_estado')?></td>
			
		</tr>
	</table>

	<table align="center" id="resultado_os" class='table table-bordered table-large' >

		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Informações do Produto</td>
		</tr>

		<tr>
			<td class='titulo_coluna'>Produto</td>
			<td ><?=pg_fetch_result($res, 0, 'produto')?></td>
			<td class='titulo_coluna'>Nota Fiscal</td>
			<td><?=pg_fetch_result($res, 0, 'doc_nota_fiscal')?></td>	
			<td class='titulo_coluna' nowrap>Data Nota Fiscal</td>
			<td><?=pg_fetch_result($res, 0, 'data_nota_fiscal')?></td>			
		</tr>
	</table>
		
	<table align="center" id="resultado_os" class='table table-bordered table-large' >

		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Informações do Posto</td>
		</tr>

		<tr>
			<td class='titulo_coluna'>Posto</td>
			<td nowrap><?=pg_fetch_result($res, 0, 'posto')?></td>	
		</tr>
	</table>

	<table align="center" id="resultado_os" class='table table-bordered table-large' >

		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Dados da <?=pg_fetch_result($res, 0, 'tipo')?></td>
		</tr>

		<tr>
			<td class='titulo_coluna'>Nome</td>
			<td colspan="5"><?=pg_fetch_result($res, 0, 'nome_troca_recompra')?></td>			
		</tr>

		<tr>
			<td class='titulo_coluna'>E-mail</td>
			<td nowrap><?=pg_fetch_result($res, 0, 'consumidor_email')?></td>
			<td class='titulo_coluna'>Telefone</td>
			<td nowrap><?=pg_fetch_result($res, 0, 'telefone_troca_recompra')?></td>
			<td class='titulo_coluna'>CEP</td>
			<td nowrap><?=pg_fetch_result($res, 0, 'cep_troca_recompra')?></td>	
		</tr>

		<tr>	
			<td class='titulo_coluna'>Endereço</td>
			<td ><?=pg_fetch_result($res, 0, 'endereco_troca_recompra')?></td>			
			<td class='titulo_coluna'>Número</td>
			<td><?=pg_fetch_result($res, 0, 'numero_troca_recompra')?></td>				
			<td class='titulo_coluna'>Complemento</td>
			<td><?=pg_fetch_result($res, 0, 'complemento_troca_recompra')?></td>	
		</tr>

		<?php
			if(in_array(pg_fetch_result($res, 0, 'tipo'), array("Recompra"))){
		?>
		<tr>	
			<td class='titulo_coluna'>Banco</td>
			<td ><?=pg_fetch_result($res, 0, 'banco')?></td>	
			<td class='titulo_coluna'>Agência</td>
			<td><?=pg_fetch_result($res, 0, 'agencia')?></td>		
			<td class='titulo_coluna'>Conta</td>
			<td><?=pg_fetch_result($res, 0, 'conta')?></td>							
		</tr>

		<tr>	
			<td class='titulo_coluna'>Bairro</td>
			<td ><?=pg_fetch_result($res, 0, 'bairro_troca_recompra')?></td>	
			<td class='titulo_coluna'>Estado</td>
			<td><?=pg_fetch_result($res, 0, 'estado_troca_recompra')?></td>		
			<td class='titulo_coluna'>Cidade</td>
			<td><?=pg_fetch_result($res, 0, 'cidade_troca_recompra')?></td>							
		</tr>
		<tr>	
			<td class='titulo_coluna'>Tipo conta</td>
			<td ><?=pg_fetch_result($res, 0, 'tipo_conta')?></td>	
			<td class='titulo_coluna'>Operação</td>
			<td colspan="3"><?=pg_fetch_result($res, 0, 'operacao')?></td>							
		</tr>
		<?php
			}
		?>

		<?php
			if(in_array(pg_fetch_result($res, 0, 'tipo'), array("Troca"))){
		?>
		<tr>	
			<td class='titulo_coluna'>Produto Troca</td>
			<td ><?=pg_fetch_result($res, 0, 'produto_troca')?></td>	
			<td class='titulo_coluna'>Estado</td>
			<td><?=pg_fetch_result($res, 0, 'estado_troca_recompra')?></td>		
			<td class='titulo_coluna'>Cidade</td>
			<td><?=utf8_decode(pg_fetch_result($res, 0, 'municipio_troca_recompra'))?></td>							
		</tr>
		<?php
			}
		?>
	</table>

	<table align="center" id="resultado_os" class='table table-bordered table-large' >
		
		<tr>
			<td class='titulo_tabela tac' colspan='100%'>Histórico</td>
		</tr>
		<tr>
			<td><?=utf8_decode(pg_fetch_result($res,0,'historico'))?></td>
		</tr>
	</table>
		
<?php
		
	}


/* Rodapé */
	include 'rodape.php';
?>
