<?php
include __DIR__."/dbconfig.php";
include __DIR__."/includes/dbconnect-inc.php";
include __DIR__."/autentica_usuario.php";

if ($_POST) {
    $peca_referencia = pg_escape_string(trim($_POST['peca_referencia']));
	$peca_descricao  = pg_escape_string(trim($_POST['peca_descricao']));

    if (strlen($peca_referencia) > 0 or strlen($peca_descricao) > 0){
		$sql = "
            SELECT peca
            FROM tbl_peca
            WHERE fabrica = {$login_fabrica}
            AND (
                (UPPER(referencia) = UPPER('{$peca_referencia}'))
                OR
                (UPPER(descricao) = UPPER('{$peca_descricao}'))
            )
            AND ativo IS TRUE
        ";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = traduz("Peça não encontrada");
			$msg_erro["campos"][] = traduz("peca");
		} else {
			$peca = pg_fetch_result($res, 0, "peca");

            if (in_array($login_fabrica, array(169,170))){
                $sqlsp = "
                    SELECT DISTINCT
                            tbl_produto.referencia,
                            tbl_produto.descricao,
                            tbl_produto.voltagem,
                            tbl_familia.descricao AS familia,
							tbl_numero_serie_peca.qtde
					from tbl_numero_serie_peca
					join tbl_numero_serie using(numero_serie,fabrica)
					join tbl_produto using(produto)
					join tbl_familia using(familia)
					join tbl_Posto_linha on tbl_produto.linha = tbl_posto_linha.linha
                    WHERE tbl_numero_serie_peca.peca = {$peca}
					AND tbl_numero_serie_peca.fabrica = {$login_fabrica}
					and posto = $login_posto
                    AND tbl_produto.ativo ";
                $resSubmit = pg_query($con, $sqlsp);

                if (pg_num_rows($resSubmit) == 0){
                    $sql = "
                        SELECT DISTINCT tbl_produto.referencia, tbl_produto.descricao, tbl_produto.voltagem, tbl_familia.descricao AS familia, tbl_lista_basica.qtde, tbl_lista_basica.posicao, tbl_lista_basica.type AS versao
                        FROM tbl_lista_basica
                        INNER JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                        INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
                        INNER JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = {$login_posto}
                        WHERE tbl_lista_basica.fabrica = {$login_fabrica}
                        AND tbl_lista_basica.peca = {$peca}
                        AND tbl_lista_basica.ativo IS NOT TRUE
                        AND tbl_produto.ativo IS TRUE
                    ";
                    $resSubmit = pg_query($con, $sql);
                }

            }else{
                $sql = "
                    SELECT DISTINCT tbl_produto.referencia, tbl_produto.descricao, tbl_produto.voltagem, tbl_familia.descricao AS familia, tbl_lista_basica.qtde, tbl_lista_basica.posicao, tbl_lista_basica.type AS versao
                    FROM tbl_lista_basica
                    INNER JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                    INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
                    INNER JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = {$login_posto}
                    WHERE tbl_lista_basica.fabrica = {$login_fabrica}
                    AND tbl_lista_basica.peca = {$peca}
                    AND tbl_lista_basica.ativo IS not TRUE
                    AND tbl_produto.ativo IS TRUE
                ";
                $resSubmit = pg_query($con, $sql);
            }
		}
	} else {
        $msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
		$msg_erro["campos"][] = traduz("peca");
    }
}

$layout_menu = "cadastro";
$title       = traduz('Consulta Peças');

include __DIR__."/cabecalho_new.php";

$plugins = array(
   "shadowbox",
   "autocomplete",
   "dataTable"
);

include __DIR__."/admin/plugin_loader.php";

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row" >
	<b class="obrigatorio pull-right" ><?=traduz('* Campos obrigatórios')?> </b>
</div>

<form method="post" class="form-search form-inline tc_formulario" >
		<div class="titulo_tabela" ><?=traduz('Parâmetros de Pesquisa')?></div>
		<br/>
        <div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_referencia'><?=traduz('Referência da Peça')?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
                            <h5 class='asteristico'>*</h5>
							<input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_descricao'><?=traduz('Descrição da Peça')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
                            <h5 class='asteristico'>*</h5>
							<input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
        <p><br/><button class='btn' name="pesquisar" type="submit" ><?=traduz('Pesquisar')?></button></p>
        <br/>
</form>

<?php
if (isset($resSubmit)) {
		if (pg_num_rows($resSubmit) > 0) {
        ?>
            <table id="resultado_pesquisa" class="table table-striped table-bordered table-hover table-fixed" style="margin: 0 auto;" >
                <thead>
                    <tr class="titulo_coluna" >
                        <th>Produto</th>
                        <th>Voltagem</th>
                        <?php
                        if (in_array($login_fabrica, array(151))) {
                        ?>
                            <th>Versão do Produto</th>
                        <?php
                        }
                        ?>
                        <th>Família</th>
                        <th>Quantidade</th>
                        <th>Posição</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = pg_fetch_object($resSubmit)) {
                    ?>
                        <tr>
                            <td><?=$row->referencia?> - <?=$row->descricao?></td>
                            <td class="tac" ><?=$row->voltagem?></td>
                            <?php
                            if (in_array($login_fabrica, array(151))) {
                            ?>
                                <td class="tac" ><?=$row->versao?></td>
                            <?php
                            }
                            ?>
                            <td><?=$row->familia?></td>
                            <td class="tac" ><?=$row->qtde?></td>
                            <td class="tac" ><?=$row->posicao?></td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        <?php
        } else {
        ?>
            <div class="container"><div class="alert"><h4><?=traduz('Nenhum resultado encontrado')?></h4></div></div>
        <?php
        }
}
?>

<script>

Shadowbox.init();
$.autocompleteLoad(["peca"]);

$("span[rel=lupa]").click(function () {
    $.lupa($(this));
});

function retorna_peca(retorno){
    $("#peca_referencia").val(retorno.referencia);
    $("#peca_descricao").val(retorno.descricao);
}

<?php
if (isset($resSubmit) && pg_num_rows($resSubmit) > 0) {
?>
    $.dataTableLoad({ table: "#resultado_pesquisa" });
<?php
}
?>

</script>

<?php
include __DIR__."/rodape.php";
