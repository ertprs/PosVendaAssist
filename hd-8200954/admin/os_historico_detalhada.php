<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";

include 'autentica_admin.php';
include 'funcoes.php';

$os       = $_GET['os'];

if(strlen($os) > 0){

	$sql = "SELECT 	tbl_os_unicoba.os,
				    to_char(tbl_os_unicoba.data_abertura,'DD/MM/YYYY') AS data_abertura,
				    tbl_os_unicoba.serie,
				    tbl_os_unicoba.nota,
				    tbl_os_unicoba.data_compra,
				    tbl_os_unicoba.defeito_reclamado,
				    tbl_os_unicoba.acessorios,
				    tbl_os_unicoba.cliente,
				    tbl_os_unicoba.cpf,
				    tbl_os_unicoba.ddd_1,
				    tbl_os_unicoba.telefone_1,
				    tbl_os_unicoba.ddd_2,
				    tbl_os_unicoba.telefone_2,
				    tbl_os_unicoba.ddd_3,
				    tbl_os_unicoba.telefone_3,
				    tbl_os_unicoba.cep,
				    tbl_os_unicoba.endereco,
				    tbl_os_unicoba.complemento,
				    tbl_os_unicoba.bairro,
				    tbl_os_unicoba.cidade,
				    tbl_os_unicoba.uf,
				    tbl_os_unicoba.posto_autorizado,
				    tbl_os_unicoba.telefone_posto,
				    tbl_os_unicoba.ddd_dosto,
				    tbl_produto.referencia,
				    tbl_produto.descricao
				FROM tbl_os_unicoba 
				JOIN tbl_produto ON tbl_produto.referencia = tbl_os_unicoba.modelo AND tbl_produto.fabrica_i = $login_fabrica
				WHERE tbl_os_unicoba.os = $os";
	$res = pg_query($con,$sql);
}

$layout_menu = "callcenter";
$title = "CONFIRMAÇÃO DE ORDEM DE SERVIÇO";
include_once "cabecalho_new.php";

	
	if (pg_num_rows($res) > 0) {

		$data_compra = explode(" ", pg_fetch_result($res, 0, 'data_compra'));
		list($y,$m,$d) = explode("-", $data_compra[0]);
		$data_compra = "$d/$m/$y";

?>

		<table align="center" id="resultado_os" class='table table-bordered table-large' >

			<tr>
				<td class='titulo_tabela tac' colspan='100%'>Ordem de Serviço</td>
			</tr>

			<tr>
				<td width="150" class="tac" style="font-size:26px; font-weight:bold; color:orange;"><?=pg_fetch_result($res, 0, 'os')?></td>
				<td class='titulo_coluna' width="100">Data Abertura</td>
				<td><?=pg_fetch_result($res, 0, 'data_abertura')?></td>
			</tr> 
		</table>
		
		<table align="center" id="resultado_os" class='table table-bordered table-large' >

			<tr>
				<td class='titulo_tabela tac' colspan='100%'>Informações do Posto</td>
			</tr>

			<tr>
				<td class='titulo_coluna'>Posto</td>
				<td nowrap><?=pg_fetch_result($res, 0, 'posto_autorizado')?></td>
				<td class='titulo_coluna'>Telefone</td>
				<td nowrap><?=pg_fetch_result($res, 0, 'ddd_dosto')." ".pg_fetch_result($res, 0, 'telefone_posto') ?></td>		
			</tr>
		</table>

		<table align="center" id="resultado_os" class='table table-bordered table-large' >

			<tr>
				<td class='titulo_tabela tac' colspan='100%'>Informações do Produto</td>
			</tr>

			<tr>
				<td class='titulo_coluna'>Produto</td>
				<td ><?=pg_fetch_result($res, 0, 'referencia')." - ".pg_fetch_result($res, 0, 'descricao')?></td>
				<td class='titulo_coluna'>Série</td>
				<td><?=pg_fetch_result($res, 0, 'serie')?></td>	
				<td class='titulo_coluna' nowrap>Nota Fiscal</td>
				<td><?=pg_fetch_result($res, 0, 'nota')?></td>
				<td class='titulo_coluna' nowrap>Data Nota</td>
				<td><?=$data_compra?></td>			
			</tr>
			<tr>				
				<td class='titulo_coluna'>Defeito Reclamado</td>
				<td colspan='7'><?=pg_fetch_result($res, 0, 'defeito_reclamado')?></td>
			</tr>
			<tr>				
				<td class='titulo_coluna'>Acessórios</td>
				<td colspan='7'><?=pg_fetch_result($res, 0, 'acessorios')?></td>
			</tr>
		</table>

		<table align="center" id="resultado_os" class='table table-bordered table-large' >
		
			<tr>
				<td class='titulo_tabela tac' colspan='100%'>Informações do Cliente</td>
			</tr>

			<tr>
				<td class='titulo_coluna'>Cliente</td>
				<td nowrap><?=pg_fetch_result($res, 0, 'cliente')?></td>
				<td class='titulo_coluna'>CPF/CNPJ</td>
				<td><?=pg_fetch_result($res, 0, 'cpf')?></td>			
			</tr>

			<tr>
				<td class='titulo_coluna'>Telefone 1</td>
				<td nowrap><?=pg_fetch_result($res, 0, 'ddd_1')." ".pg_fetch_result($res, 0, 'telefone_1') ?></td>
				<td class='titulo_coluna'>Telefone 2</td>
				<td nowrap><?=pg_fetch_result($res, 0, 'ddd_2')." ".pg_fetch_result($res, 0, 'telefone_2') ?></td>
				<td class='titulo_coluna'>Telefone 3</td>
				<td nowrap><?=pg_fetch_result($res, 0, 'ddd_3')." ".pg_fetch_result($res, 0, 'telefone_3') ?></td>	
			</tr>

			<tr>	
				<td class='titulo_coluna'>Endereço</td>
				<td ><?=pg_fetch_result($res, 0, 'endereco'). " " .pg_fetch_result($res, 0, 'complemento') ?></td>			
				<td class='titulo_coluna'>CEP</td>
				<td colspan='4'><?=pg_fetch_result($res, 0, 'cep')?></td>				
			</tr>

			<tr>	
				<td class='titulo_coluna'>Bairro</td>
				<td ><?=pg_fetch_result($res, 0, 'bairro')?></td>			
				<td class='titulo_coluna'>Cidade</td>
				<td><?=pg_fetch_result($res, 0, 'cidade')?></td>	
				<td class='titulo_coluna'>Estado</td>
				<td><?=pg_fetch_result($res, 0, 'uf')?></td>			
			</tr>
		</table>
		
<?php
		$sql = "SELECT 	tbl_peca.referencia || ' ' || tbl_peca.descricao AS peca,
					 	tbl_os_item_unicoba.qtde
					FROM tbl_os_item_unicoba
					JOIN tbl_peca ON tbl_peca.referencia = tbl_os_item_unicoba.peca AND tbl_peca.fabrica = $login_fabrica
					WHERE tbl_os_item_unicoba.os = $os";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){

			echo "<table align='center' class='table table-bordered table-large' >
					<tr>
						<td class='titulo_tabela tac' colspan='100%'>Peças solicitadas</td>
					</tr>
					<tr>
						<td class='titulo_coluna'>Peça</td>
						<td class='titulo_coluna'>Qtde</td>
					</tr>";

			for($i = 0; $i < pg_num_rows($res); $i++){

				echo "<tr>
						<td>".pg_fetch_result($res, $i, 'peca')."</td>
						<td>".pg_fetch_result($res, $i, 'qtde')."</td>
					  </tr>";

			}
			echo "</table>";

		}
	}


/* Rodapé */
	include 'rodape.php';
?>
