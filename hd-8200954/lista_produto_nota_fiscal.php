<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
	define('APPBACK', '../');
	$areaAdmin = true;
	include_once "../class/tdocs.class.php";
	$tDocs = new TDocs($con, $login_fabrica);
} else {
	define('APPBACK', '');
	include 'autentica_usuario.php';
	include_once "class/tdocs.class.php";
	$tDocs = new TDocs($con, $login_fabrica);
}

include __DIR__.'/funcoes.php';
if ($_REQUEST["nota_fiscal"]){
	$nota_fiscal   = $_REQUEST["nota_fiscal"];
}

if ($_REQUEST['linha_posto_pedido']){
	$linha = $_REQUEST['linha_posto_pedido'];
	$cond_linha = "AND tbl_produto.linha = {$linha}";
}else{
	$cond_linha = "";
}

$sql_representante = "
	SELECT
		tbl_representante.representante
	FROM tbl_representante
	WHERE trim(tbl_representante.codigo) = '{$login_codigo_posto}'
	AND trim(tbl_representante.cnpj) = '{$login_cnpj}'";
$res_representante = pg_query($con, $sql_representante);

if (pg_num_rows($res_representante) > 0){
	$id_representante = pg_fetch_result($res_representante, 0, "representante");

	if (!empty($nota_fiscal)){
		$sql_venda = "
	        SELECT DISTINCT
	            tbl_produto.referencia,
	            tbl_produto.descricao,
	            tbl_produto.produto,
	            tbl_venda.serie,
	            tbl_venda.nota_fiscal,
	            TO_CHAR(tbl_venda.data_nf, 'DD/MM/YYYY') AS data_nf,
	            tbl_venda.qtde,
	            tbl_posto_fabrica.codigo_posto,
	            tbl_posto.nome,
	            tbl_posto.posto,
	            tbl_posto_fabrica.codigo_posto,
	            tbl_produto.linha,
	            tbl_posto.cnpj,
	            tbl_posto_fabrica.contato_endereco,
	            tbl_posto_fabrica.contato_numero,
	            tbl_posto_fabrica.contato_cidade,
	            tbl_posto_fabrica.contato_estado
	        FROM tbl_venda
	        JOIN tbl_produto ON tbl_produto.produto = tbl_venda.produto AND tbl_produto.fabrica_i = {$login_fabrica}
	        JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
	        JOIN tbl_posto ON tbl_posto.posto = tbl_venda.posto
	        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
	        JOIN tbl_posto_fabrica_representante ON tbl_posto_fabrica_representante.posto = tbl_venda.posto AND tbl_posto_fabrica_representante.representante = {$id_representante}
	        WHERE tbl_venda.fabrica = {$login_fabrica} 
	        AND tbl_venda.nota_fiscal = '{$nota_fiscal}'
	        {$cond_linha}";
	    $res_venda = pg_query($con, $sql_venda);

	    if (pg_num_rows($res_venda) > 0){
	    	$dados_nota = pg_fetch_all($res_venda);
	    }
	}
} 

?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
		<link type="text/css" rel="stylesheet" href="plugins/dataTable.css" />

		<style>
			.font_style{
				font-size: 12px;
			}
		</style>

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>
		<script src='plugins/jquery.alphanumeric.js'></script>
	</head>
	
	<body>
		<div id="container_lupa" style="overflow-y:auto;">
			<?php if (pg_num_rows($res_venda) > 0){ ?>
				<table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-fixed'>
					<thead>
						<tr class="titulo_tabela">
							<th colspan="8">Informações da Nota Fiscal: <?=$dados_nota[0]["nota_fiscal"]?>  - Data de Emissão: <?=$dados_nota[0]["data_nf"]?></th>
						</tr>
						<tr>
							<th class="titulo_coluna">Codigo cliente:</th>
							<th colspan="8" class='tal font_style'><?=$dados_nota[0]["codigo_posto"]?></th>
						</tr>
						<tr>
							<th class="titulo_coluna">Razão Social:</th>
							<th colspan="8" class='tal font_style'><?=$dados_nota[0]["nome"]?></th>
						</tr>
						<tr>
							<th class="titulo_coluna">CNPJ:</th>
							<th colspan="8" class='tal font_style'><?=formata_cpf_cnpj($dados_nota[0]["cnpj"])?></th>
						</tr>
						<tr>
							<th class="titulo_coluna">Endereço:</th>
							<th colspan="8" class='tal font_style'><?=$dados_nota[0]["contato_endereco"]?></th>
						</tr>
						<tr>
							<th class="titulo_coluna">Número:</th>
							<th class='tal font_style'><?=$dados_nota[0]["contato_numero"]?></th>
							<th class="titulo_coluna">Cidade:</th>
							<th class='tal font_style'><?=$dados_nota[0]["contato_cidade"]?></th>
							<th class="titulo_coluna">Estado:</th>
							<th class='tal font_style'><?=$dados_nota[0]["contato_estado"]?></th>
						</tr>
					</thead>
				</table>
				<table id="resultados" class='table table-striped table-bordered table-hover table-fixed'>
					<thead>
						<tr class="titulo_tabela">
							<th colspan="8">Informações dos Produtos da Nota Fiscal: <?=$dados_nota[0]["nota_fiscal"]?> Data: <?=$dados_nota[0]["data_nf"]?></th>
						</tr>
						<tr class="titulo_coluna">
							<th>Código Produto</th>
							<th>Descrição Produto</th>
							<th>Quantidade</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($dados_nota as $key => $value) {
							$value = array_filter($value);
							$contato_cidade = str_replace("'", " ", $value["contato_cidade"]);
							$contato_endereco = str_replace("'", " ", $value["contato_endereco"]);

							$value["contato_endereco"] = utf8_encode($contato_endereco);
							$value["contato_cidade"] = utf8_encode($contato_cidade);
							$value["descricao"] = utf8_encode($value["descricao"]);
							$value["nome"] = utf8_encode($value["nome"]);
						?>
							<tr class='click_retorno' data-dados='<?php echo json_encode($value);?>'>
								<td class='cursor_lupa'><?=$value["referencia"]?></td>
								<td class='cursor_lupa'><?=$value["descricao"]?></td>
								<td class='cursor_lupa tac'><?=$value["qtde"]?></td>					
							</tr>
						<?php } ?>
					</tbody>
				</table>
			<?php }else{ ?>
				<div class="alert alert-error">
					<h4>Nenhuma nota fiscal encontrada</h4>
				</div>
			<?php } ?>
		</div>
	</body>
</html>
<script type="text/javascript">
	$('#resultados').on('click', '.click_retorno', function() {
		var dados = JSON.parse($(this).attr('data-dados'));
		window.parent.retorna_dados_nota_fiscal(dados);
		window.parent.Shadowbox.close();
	});
</script>
