 <?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include "funcoes.php";

if(isset($_GET['os']) || isset($_POST['os'])){
	if(isset($_GET['os'])){
		$os = $_GET['os'];
	}else if(isset($_POST['os'])){
		$os = $_POST['os'];
	}
}
if(isset($_GET['hdChamado']) || isset($_POST['hdChamado'])){
	if(isset($_GET['hdChamado'])){
		$hdChamado = $_GET['hdChamado'];
	}else if(isset($_POST['hdChamado'])){
		$hdChamado = $_POST['hdChamado'];
	}
}


if(isset($_GET['acao']) || isset($_POST['acao'])){
	if(isset($_GET['acao'])){
		$acao = $_GET['acao'];
	}else if(isset($_POST['acao'])){
		$acao = $_POST['acao'];
	}
}

if($acao == "ressarcimento"){
	$titulo = "Recompra";

}else if($acao == "troca"){
	$titulo = "Troca";
}

$condExt = "";
$campoExt = "";

	
	$sql = "SELECT hd_chamado FROM tbl_hd_chamado_item WHERE os = $os";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) == 0){
		$condExt .= " LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os 
					LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado_item.hd_chamado";
		$condExt .= " LEFT JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os AND tbl_os_troca.fabric = {$login_fabrica}
					left JOIN tbl_hd_chamado on tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado";
		$campoExt = "
			case when tbl_hd_chamado_extra.nota_fiscal isnull then tbl_os.nota_fiscal else tbl_hd_chamado_extra.nota_fiscal end as nota_fiscal,
		to_char(tbl_os.data_nf,'DD/MM/YYYY') AS data_nf,";
	}else{
		$condExt .= " JOIN tbl_hd_chamado_item ON tbl_os.os = tbl_hd_chamado_item.os";
		$condExt .= " LEFT JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os AND tbl_os_troca.fabric = {$login_fabrica} 
				left JOIN tbl_hd_chamado on tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
				LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado ";
		$campoExt = "
		case when tbl_hd_chamado_item.nota_fiscal isnull then tbl_os.nota_fiscal else tbl_hd_chamado_item.nota_fiscal end     as nota_fiscal,
		to_char(tbl_hd_chamado_item.data_nf,'DD/MM/YYYY') AS data_nf,";
	}

	if ($login_fabrica == 151) { /*HD - 6177097*/
		$campoTitular = " ,JSON_FIELD('nome_titular_nf', tbl_hd_chamado_extra.array_campos_adicionais) AS titular_nf, JSON_FIELD('cpf_titular_nf', tbl_hd_chamado_extra.array_campos_adicionais) AS cpf_titular_nota ";
	}


$sql = "SELECT
			tbl_hd_chamado.hd_chamado AS protocolo,
				to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data_protocolo,
				tbl_admin.nome_completo AS reponsavel,
				tbl_produto.referencia AS ref_produto,
				tbl_produto.descricao AS desc_produto,
				tbl_peca.referencia AS ref_produto_troca,
				tbl_peca.descricao AS desc_produto_troca,
				tbl_os_troca.causa_troca,
				tbl_os_troca.observacao AS descricao,
				tbl_os.consumidor_nome AS nome_consumidor,
				{$campoExt}			
				tbl_os.consumidor_cpf AS cpf_cnpj,
				tbl_os.consumidor_cep as cep,
				consumidor_cidade AS municipio,
				consumidor_estado AS uf,
				consumidor_endereco AS logradouro,
				consumidor_numero as numero,
				consumidor_complemento as complemento,
				consumidor_bairro as bairro,
				consumidor_fone as fone,
				tbl_os.sua_os,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
				to_char(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome AS nome_posto,
				tbl_ressarcimento.nome AS nome_nf,
				tbl_ressarcimento.cpf AS cpf_nf,
				tbl_banco.codigo AS banco,
				tbl_ressarcimento.agencia,
				tbl_ressarcimento.conta,
				tbl_ressarcimento.valor_original,
				tbl_ressarcimento.tipo_conta,
				tbl_os_item.qtde AS qtde_produto_troca,
				tbl_defeito_constatado.descricao AS defeito_constatado
				{$campoTitular}
				FROM tbl_os
   				
				{$condExt}
				
				LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_hd_chamado_extra.cidade				
				JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.peca = tbl_os_troca.peca
				LEFT JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
				LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_produto.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
				LEFT JOIN tbl_ressarcimento ON tbl_ressarcimento.os = tbl_os.os AND tbl_ressarcimento.fabrica = {$login_fabrica}
				LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
				LEFT JOIN tbl_banco ON tbl_banco.banco = tbl_ressarcimento.banco				
				WHERE tbl_os.os = {$os}"; //AND tbl_produto.fabrica_i = {$login_fabrica}
$res = pg_query($con,$sql);
$protocolo          = pg_fetch_result($res, 0, 'protocolo');
$data_protocolo     = pg_fetch_result($res, 0, 'data_protocolo');
$reponsavel         = pg_fetch_result($res, 0, 'reponsavel');
$causa_troca        = pg_fetch_result($res, 0, 'causa_troca');
$descricao          = pg_fetch_result($res, 0, 'descricao');
$nome_consumidor    = pg_fetch_result($res, 0, 'nome_consumidor');
$nota_fiscal        = pg_fetch_result($res, 0, 'nota_fiscal');
$data_nf            = pg_fetch_result($res, 0, 'data_nf');
$cpf_cnpj           = pg_fetch_result($res, 0, 'cpf_cnpj');
$cep                = pg_fetch_result($res, 0, 'cep');
$uf                 = pg_fetch_result($res, 0, 'uf');
$municipio          = pg_fetch_result($res, 0, 'municipio');
$logradouro         = pg_fetch_result($res, 0, 'logradouro');
$numero             = pg_fetch_result($res, 0, 'numero');
$complemento        = pg_fetch_result($res, 0, 'complemento');
$bairro             = pg_fetch_result($res, 0, 'bairro');
$fone               = pg_fetch_result($res, 0, 'fone');
$sua_os             = pg_fetch_result($res, 0, 'sua_os');
$data_abertura      = pg_fetch_result($res, 0, 'data_abertura');
$data_digitacao     = pg_fetch_result($res, 0, 'data_digitacao');
$defeito_constatado = pg_fetch_result($res, 0, 'defeito_constatado');
$ref_produto        = pg_fetch_result($res, 0, 'ref_produto');
$desc_produto       = pg_fetch_result($res, 0, 'desc_produto');
$ref_produto_troca  = pg_fetch_result($res, 0, 'ref_produto_troca');
$desc_produto_troca = pg_fetch_result($res, 0, 'desc_produto_troca');
$qtde_produto_troca = pg_fetch_result($res, 0, 'qtde_produto_troca');
$codigo_posto       = pg_fetch_result($res, 0, 'codigo_posto');
$nome_posto         = pg_fetch_result($res, 0, 'nome_posto');
$nome_nf            = pg_fetch_result($res, 0, 'nome_nf');
$cpf_nf		    = pg_fetch_result($res, 0, 'cpf_nf');

if ($login_fabrica == 151) { /*HD - 6177097*/
	$titular_nf       = pg_fetch_result($res, 0, 'titular_nf');
	$cpf_titular_nota = pg_fetch_result($res, 0, 'cpf_titular_nota');
}

$banco       = "";
$agencia     = "";
$conta       = "";
$tipo_conta  = "";
$operacao    = "";
$valor_nf    = "";
$correcao    = "";
$indenizacao = "";
$Multa       = "";
$outros      = "";
$valor_total = "";

if($acao == "ressarcimento"){
	$banco              = pg_fetch_result($res, 0, 'banco');
	$agencia            = pg_fetch_result($res, 0, 'agencia');
	$conta              = pg_fetch_result($res, 0, 'conta');
	$tipo_conta         = pg_fetch_result($res, 0, 'tipo_conta');

	if($tipo_conta == "C"){
		$tipo_conta = "Conta Corrente";
	}else{
		$tipo_conta = "Conta Poupança";
	}
	
	$nome_consumidor = (strlen($nome_nf) > 0) ? $nome_nf : $nome_consumidor;
	$cpf_cnpj = (strlen($cpf_nf) > 0) ? $cpf_nf : $cpf_cnpj;
}

$sql = "SELECT nome_completo FROM tbl_admin WHERE admin = {$login_admin}";
$resAdmin = pg_query($con,$sql);

$gravado_por    = pg_fetch_result($resAdmin, 0, "nome_completo");
$valor_original = number_format(pg_fetch_result($res, 0, 'valor_original'),2,',','.');

if(strlen($cpf_cnpj) == 11){
	$cpf_cnpj = substr($cpf_cnpj,0,3).".".substr($cpf_cnpj,3,3).".".substr($cpf_cnpj,6,3)."-".substr($cpf_cnpj, -2);
}else{
	$cpf_cnpj = substr($cpf_cnpj, 0,2).".".substr($cpf_cnpj, 2,3).".".substr($cpf_cnpj, 5,3)."/".substr($cpf_cnpj, 8,4)."-".substr($cpf_cnpj, -2);
}


?>
<title>Formulário</title>
<!-- <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" /> -->
<!-- <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" /> -->
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<!-- <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script> -->
<!-- <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script> -->
<!-- <script src="bootstrap/js/bootstrap.js"></script> -->

<style type="text/css">
	.frm_formulario_troca_compra {
		width: 85% !important;
	}
	.table, th, tr, td {
		border: 1px solid black;

	}

	table {
		border-collapse: collapse;
		width: 700px;
	}

	table > tbody > tr > td {
		text-align: center;
	}

	.informacao {
		text-align: center !important;
	}
</style>

<form name='frm_formulario_troca_compra' class="frm_formulario_troca_compra" METHOD='POST' ACTION='<?=$PHP_SELF?>'>
	<label class="tac"><h3><?=$titulo?></h3></label>
	<table class="table table-striped table-bordered table-fixed">
		<tr>
			<th>Tipo</th>
			<th>Protocolo</th>
			<th>Data Protocolo</th>
			<th>Responsável</th>
			<th>Gravado Por</th>
		</tr>
		<tr>
			<td><?=$titulo?></td>
			<td><?=$protocolo?></td>
			<td><?=$data_protocolo?></td>
			<td><?=$reponsavel?></td>
			<td><?=$gravado_por?></td>
		</tr>
		<tr>
			<th colspan="4">Produto</th>
			<th>Posto</th>
		</tr>
		<tr>
			<tr>
				<td  colspan="4">
					<?=$ref_produto?> - <?=$desc_produto?>
				</td>
				<td ><?=$codigo_posto?> - <?=$nome_posto?></td>
			</tr>
		</tr>
		<tr>
			<th colspan="5">Motivo da Troca</th>
		</tr>
		<tr>
			<tr>
				<td  colspan="5"><?=$descricao?></td>
			</tr>
		</tr>
	</table>
	<p>Descrição</p>
	<br/>
	<br/>
	<table class="table table-striped table-bordered table-fixed">
		<tr>
			<th colspan="2">Nome</th>
			<th>NF</th>
			<th>Data NF</th>
			<th>Nome Nota Fiscal / Deposito</th>
		</tr>
		<tr>
			<tr>
				<td  colspan="2"><?=$nome_consumidor?></td>
				<td ><?=$nota_fiscal?></td>
				<td ><?=$data_nf?></td>
				<td ><?=$nome_consumidor?></td>
			</tr>
		</tr>
		<tr>
			<th>CPF / CNPJ</th>
			<th>País</th>
			<th>CEP</th>
			<th>UF</th>
			<th>Município</th>
		</tr>
		<tr>
			<tr>
				<td ><?=$cpf_cnpj?></td>
				<td >BRASIL</td>
				<td ><?=$cep?></td>
				<td ><?=$uf?></td>
				<td ><?=$municipio?></td>
			</tr>
		</tr>
		<?php if ($login_fabrica == 151) { /*HD - 6177097*/ ?>
            <tr style="background-color: yellow;">
	            <th colspan="3">Titular da NF</th>
	            <th colspan="2">CPF do Titular</th>
			</tr>
			<tr style="background-color: yellow;">
	            <td colspan="3"><?=$titular_nf?></td>
				<td colspan="2"><?=$cpf_titular_nota?></td>
			</tr>
        <?php } ?>
		<tr>
			<th colspan="2">Logradouro</th>
			<th>Nº</th>
			<th colspan="2">Complemento</th>
		</tr>
		<tr>
			<tr>
				<td  colspan="2"><?=mb_strtoupper(utf8_decode($logradouro))?></td>
				<td ><?=$numero?></td>
				<td colspan="2"><?=$complemento?></td>
			</tr>
		</tr>
		<tr>
			<th colspan="2">Bairro</th>
			<th >Telefone</th>
			<th colspan="2">Condição de Pagamento</th>
		</tr>
		<tr>
			<tr>
				<td  colspan="2"><?=$bairro?></td>
				<td ><?=$fone?></td>
				<td  colspan="2">LIVRE DE DÉBITO</td>
			</tr>
		</tr>
		<tr>
			<th colspan="2">Produto Troca</th>
			<th>QTD</th>
			<th>Transportadora</th>
			<th>Frete</th>
		</tr>
		<tr>
			<?php
			if($acao == "troca"){
				$sql = "SELECT tbl_peca.referencia, 
						tbl_peca.descricao, 
						count(tbl_peca.referencia) AS qtde 
					FROM tbl_os_item 
						INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
						INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica} 
						INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado 
					WHERE tbl_os_produto.os = {$os} 
						AND tbl_peca.produto_acabado IS TRUE 
						AND tbl_servico_realizado.troca_produto IS TRUE 
						GROUP BY tbl_peca.descricao, tbl_peca.referencia";
				$resProduto = pg_query($con,$sql);

				if(pg_num_rows($resProduto) > 0){
					while($objeto_produto = pg_fetch_object($resProduto)){
						?>
						<tr>
							<td colspan="2">
								<?=$objeto_produto->referencia?> - <?=$objeto_produto->descricao?>
							</td>
							<td ><?=$objeto_produto->qtde?></td>
							<td >CIF</td>
							<td >CIF</td>
						</tr>
						<?php
					}
				}
			}else{
				?>
				<tr>
					<td colspan="2"></td>
					<td></td>
					<td></td>
					<td></td>
				</tr>
				<?php
			}
			?>
		</tr>
	</table>
	<br/>
	<table class="table table-striped table-bordered table-fixed">
		<tr>
			<th>Banco</th>
			<th>Agência</th>
			<th>Conta</th>
			<th colspan="2">Tipo</th>
			<th>Operação</th>
		</tr>
		<tr>
			<tr>
				<td ><?=$banco?></td>
				<td ><?=$agencia?></td>
				<td ><?=$conta?></td>
				<td colspan="2"><?=$tipo_conta?></td>
				<td ><?=$operacao?></td>
			</tr>
		</tr>
		<tr>
			<th>Valor NF</th>
			<th>Correção</th>
			<th>Indenização</th>
			<th>Multa</th>
			<th>Outro</th>
			<th>Valor Total</th>
		</tr>
		<tr>
			<tr>
				<td ><?=$valor_original?></td>
				<td ><?=$correcao?></td>
				<td ><?=$indenizacao?></td>
				<td ><?=$multa?></td>
				<td ><?=$outro?></td>
				<td ><?=$valor_original?></td>
			</tr>
		</tr>
		<tr>
			<th>OS</th>
			<th>Data Abertura</th>
			<th>Data Digitação</th>
			<th colspan="3">Defeito Constatado</th>
		</tr>
		<tr>
			<tr>
				<td><?=$sua_os?></td>
				<td><?=$data_abertura?></td>
				<td><?=$data_digitacao?></td>
				<td colspan="3"><?=$defeito_constatado?></td>
			</tr>
		</tr>
		<tr>
			<th colspan="2">Peças</th>
			<th colspan="3">Descrição</th>
			<th>Quantidade</th>
		</tr>
		<tr>

			<?php
			$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_os_item.qtde FROM tbl_os_item
					INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
					INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica =$login_fabrica 
					INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado 
				WHERE tbl_os_produto.os = $os AND tbl_servico_realizado.troca_produto IS NOT TRUE";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){
					while($objeto_peca = pg_fetch_object($res)){
						?>
						<tr>
							<td class="informacao" colspan="2"><?=$objeto_peca->referencia?></td>
							<td class="informacao" colspan="3"><?=$objeto_peca->descricao?></td>
							<td class="informacao" ><?=$objeto_peca->qtde?></td>
						</tr>
						<?php
					}
				}else{
					?>
					<tr>
						<td colspan="2"></td>
						<td colspan="3"></td>
						<td></td>
					</tr>
					<?php
				}
			?>
		</tr>
	</table>
	<br>
	<input type='button' onclick='javascript: window.print()' value='imprimir'>

</form>
