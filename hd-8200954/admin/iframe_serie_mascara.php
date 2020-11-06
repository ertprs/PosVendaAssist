<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$linha   = "";
$familia = "";
$origem  = "";

if(isset($_GET["linha"])){
	$linha = $_GET["linha"];
}

if(isset($_GET["familia"])){
	$familia = $_GET["familia"];
}

if(isset($_GET["origem"])){
	$origem = $_GET["origem"];
}

if(!empty($linha) && !empty($familia) && !empty($origem)){
	$sql = "SELECT 
			tbl_produto.produto,
			tbl_produto.referencia,
			tbl_produto.descricao AS produto_descricao,
			tbl_linha.nome AS linha_nome,
			tbl_familia.descricao AS familia_nome,
			tbl_produto.origem,
			tbl_produto_valida_serie.mascara,
			tbl_produto_valida_serie.posicao_versao
		FROM tbl_produto
		INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica} AND tbl_linha.ativo IS TRUE
		INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica} AND tbl_familia.ativo IS TRUE
		LEFT JOIN tbl_produto_valida_serie ON tbl_produto_valida_serie.produto = tbl_produto.produto AND tbl_produto_valida_serie.fabrica = {$login_fabrica}
		WHERE tbl_produto.fabrica_i = {$login_fabrica}
			AND tbl_produto.ativo IS TRUE
			AND tbl_produto.linha = {$linha}
			AND tbl_produto.familia = {$familia}
			AND tbl_produto.origem = '{$origem}'
		ORDER BY produto_descricao";
	$res = pg_query($con,$sql);
}

?>
<style type="text/css">
form {
    width: 900px;
}
.div_iframe_serie_mascara {
    overflow-y: scroll;
    height: 470px;
}

.table td{
    text-align: center;
}

.table {
    width: 850px;
    margin: 0 auto;
}

input.numeric {
    width: 50px;
}

td.qtde_peca{
    width: 100px;
}

#btn_gravar {
    margin-top: 20px;
}

.error {
    border-color: #b94a48 !important;
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075) !important;
    color: #b94a48 !important;
}
</style>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" >
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<form id="fm_iframe_serie_mascara" method="POST" class="form-search form-inline" >
    <div class="div_iframe_serie_mascara" style="margin: 5px; padding-right: 20px;">
        <div id="mensagem_iframe_serie_mascara">
            <table id="table_iframe_serie_mascara" class='table table-striped table-bordered table-large' >
                <thead>
                    <tr class='titulo_coluna'>
                        <th>Produto</th>
                        <th>Linha</th>
                        <th>Família</th>
                        <th>Origem</th>
                        <th>Máscara</th>
                        <th>Posição Versão</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if(pg_num_rows($res) > 0){
                    while($objeto_produto = pg_fetch_object($res)){
                        ?>
                        <tr>
                            <td><a target="_blank" href="produto_cadastro.php?produto=<?=$objeto_produto->produto?>"><?=$objeto_produto->referencia?> - <?=$objeto_produto->produto_descricao?></a></td>
                            <td class="tac"><?=$objeto_produto->linha_nome?></td>
                            <td class="tac"><?=$objeto_produto->familia_nome?></td>
                            <td class="tac"><?=$objeto_produto->origem?></td>
                            <td class="tac"><?=$objeto_produto->mascara?></td>
                            <td class="tac"><?=$objeto_produto->posicao_versao?></td>
                        </tr>
                    <?php 
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</form>