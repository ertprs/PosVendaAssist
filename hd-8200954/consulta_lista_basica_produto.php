<?php
include __DIR__."/dbconfig.php";
include __DIR__."/includes/dbconnect-inc.php";
include __DIR__."/autentica_usuario.php";

if ($imagemPeca) {
	include "anexaNFDevolucao_inc.php";
	$tDocs = new TDocs($con, $login_fabrica);
	if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
		$caminho_img_peca = "../";
	}else{
	        $caminho_img_peca = "";
	}
}

if ($_POST) {
    $produto_referencia = pg_escape_string(trim($_POST['produto_referencia']));
    $produto_descricao  = pg_escape_string(trim($_POST['produto_descricao']));

    if (in_array($login_fabrica, array(169,170))) {
        $produto_serie = pg_escape_string(trim($_POST["produto_serie"]));
    }

    if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
		$sql = "
            SELECT produto
            FROM tbl_produto
            WHERE fabrica_i = {$login_fabrica}
            AND UPPER(referencia) = UPPER('{$produto_referencia}')
            AND UPPER(descricao) = UPPER('{$produto_descricao}')
            AND ativo IS TRUE;
        ";
		$res = pg_query($con ,$sql);

		if (pg_num_rows($res) == 0) {
			$msg_erro["msg"][]    = traduz("Produto não encontrado");
			$msg_erro["campos"][] = "produto";
		} else {
			$produto = pg_fetch_result($res, 0, "produto");

            if (in_array($login_fabrica, array(169,170))){
                if (!empty($produto_serie)) {
                    $sql = "
                        SELECT numero_serie
                        FROM tbl_numero_serie
                        WHERE fabrica = {$login_fabrica}
                        AND produto = {$produto}
                        AND (serie = UPPER('{$produto_serie}')
                        OR serie = UPPER('S{$produto_serie}'));
                    ";
                    $res = pg_query($con ,$sql);
                    if (pg_num_rows($res) == 0) {
                        $msg_erro["msg"][]    = traduz("Número de Série não encontrado");
                        $msg_erro["campos"][] = "produto";
                    } else {
                        $numero_serie = pg_fetch_result($res, 0, 'numero_serie');

                        $sqlsp = "
                            SELECT
                                p.peca, p.referencia, p.descricao, nsp.qtde, nsp.ordem AS posicao
                            FROM tbl_numero_serie_peca nsp
                            INNER JOIN tbl_peca p ON p.peca = nsp.peca AND p.fabrica = {$login_fabrica} AND p.ativo IS TRUE AND p.produto_acabado IS NOT TRUE
                            INNER JOIN tbl_numero_serie ns ON ns.numero_serie = nsp.numero_serie AND ns.fabrica = {$login_fabrica}
                            INNER JOIN tbl_produto pr ON pr.produto = ns.produto AND pr.fabrica_i = {$login_fabrica} AND pr.ativo IS TRUE
                            INNER JOIN tbl_posto_linha pl ON pl.linha = pr.linha AND pl.posto = {$login_posto}
                            WHERE nsp.fabrica = {$login_fabrica}
                            AND nsp.numero_serie = {$numero_serie}
                            ORDER BY p.descricao
                        ";
                        $resSubmit = pg_query($con, $sqlsp);
                    }
                }

                if (empty($produto_serie) || pg_num_rows($resSubmit) == 0){
                    $sql = "
                        SELECT
                            p.peca, p.referencia, p.descricao, lb.qtde, lb.posicao
                        FROM tbl_lista_basica lb
                        INNER JOIN tbl_peca p ON p.peca = lb.peca AND p.fabrica = {$login_fabrica} AND p.ativo IS TRUE AND p.produto_acabado IS NOT TRUE
                        INNER JOIN tbl_produto pr ON pr.produto = lb.produto AND pr.fabrica_i = {$login_fabrica} AND pr.ativo IS TRUE
                        INNER JOIN tbl_posto_linha pl ON pl.linha = pr.linha AND pl.posto = {$login_posto}
                        WHERE lb.fabrica = {$login_fabrica}
                        AND lb.produto = {$produto}
                        ORDER BY p.descricao
                    ";
                    $resSubmit = pg_query($con, $sql);
                }

            }else{

                $join_posto_linha = "";
                $cond_ativo = "";
                
                if ($login_fabrica != 20) {
                    $join_posto_linha = " INNER JOIN tbl_posto_linha pl ON pl.linha = pr.linha AND pl.posto = {$login_posto} ";
                    $cond_ativo = " AND lb.ativo IS TRUE ";
                } 

                $sql = "
                    SELECT
                        p.peca, p.referencia, p.descricao, lb.qtde, lb.posicao
                    FROM tbl_lista_basica lb
                    INNER JOIN tbl_peca p ON p.peca = lb.peca AND p.fabrica = {$login_fabrica} AND p.ativo IS TRUE AND p.produto_acabado IS NOT TRUE
                    INNER JOIN tbl_produto pr ON pr.produto = lb.produto AND pr.fabrica_i = {$login_fabrica} AND pr.ativo IS TRUE
                    $join_posto_linha
                    WHERE lb.fabrica = {$login_fabrica}
                    AND lb.produto = {$produto}
                    $cond_ativo  
                    ORDER BY p.descricao
                ";
                $resSubmit = pg_query($con, $sql);
            }
		}
	} else {
        $msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
		$msg_erro["campos"][] = "produto";
    }
}

$layout_menu = "cadastro";
$title       = traduz('Consulta Lista Básica');

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
	<b class="obrigatorio pull-right" >  * <?=traduz("Campos obrigatórios")?> </b>
</div>

<form method="post" class="form-search form-inline tc_formulario" >
		<div class="titulo_tabela" ><?=traduz("Parâmetros de Pesquisa")?></div>
		<br/>
        <div class='row-fluid'>
			<div class='span1'></div>
            <?php
            if (in_array($login_fabrica, array(169,170))) {
            ?>
                <div class='span4'>
                    <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='produto_serie'><?=traduz("Número de Série do Produto")?></label>
                        <div class='controls controls-row'>
                            <div class='span7 input-append'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" id="produto_serie" name="produto_serie" class='span12' maxlength="30" value="<? echo $produto_serie ?>" >
                                <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                                <input
                                    type="hidden"
                                    name="lupa_config"
                                    tipo="produto"
                                    parametro="numero_serie"
                                    posto="<?=$login_posto?>"
                                    ativo="t"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            }
            ?>
			<div class='span3'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'><?=traduz("Referência do Produto")?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
                            <h5 class='asteristico'>*</h5>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="30" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input
                                type="hidden"
                                name="lupa_config"
                                tipo="produto"
                                parametro="referencia"
                                posto="<?=$login_posto?>"
                                ativo="t"
                            />
						</div>
					</div>
				</div>
			</div>
			<div class='span3'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'><?=traduz("Descrição do Produto")?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
                            <h5 class='asteristico'>*</h5>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input
                                type="hidden"
                                name="lupa_config"
                                tipo="produto"
                                parametro="descricao"
                                posto="<?=$login_posto?>"
                                ativo="t"
                            />
						</div>
					</div>
				</div>
			</div>
		</div>
        <p><br/><button class='btn' name="pesquisar" type="submit" ><?=traduz("Pesquisar")?></button></p>
        <br/>
</form>

<?php
if (isset($resSubmit)) {
		if (pg_num_rows($resSubmit) > 0) {
        ?>
            <table id="resultado_pesquisa" class="table table-striped table-bordered table-hover table-fixed" style="margin: 0 auto;" >
                <thead>
		    <tr class="titulo_coluna" >
			<? if ($imagemPeca) { ?>
			<th><?= traduz('Imagem') ?></th>
			<? } ?>
                        <th><?=traduz("Referência da Peça")?></th>
                        <th><?=traduz("Descrição da Peça")?></th>
                        <th><?=traduz("Quantidade")?></th>
                        <th><?=traduz("Posição")?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = pg_fetch_object($resSubmit)) {
                    ?>
			<tr>
			    <? if ($imagemPeca) { ?>
				<td>
				<?php
				$xpecas  = $tDocs->getDocumentsByRef($row->peca, "peca");
    
				if (!empty($xpecas->attachListInfo)) {
					$a = 1;
					foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
						$fotoPeca = $vFoto["link"];
						if ($a == 1){break;}
					}

					if (!empty($fotoPeca)) {
						echo "<a href='".$fotoPeca."'><img src='".$fotoPeca."' style='max-height: 200px;' class='img-polaroid'></a>";
					} else {
						echo "<img src='imagens/sem_imagem.jpg' style='max-height: 200px;' class='img-polaroid'>";
					}
				} else {
					echo "<img src='imagens/sem_imagem.jpg' style='max-height: 200px;' class='img-polaroid'>";
				}
	    			?>
			    </td>
			    <? } ?>
                            <td><?=$row->referencia?></td>
                            <td><?=$row->descricao?></td>
                            <td><?=$row->qtde?></td>
                            <td><?=$row->posicao?></td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        <?php
        } else {
        ?>
            <div class="container"><div class="alert"><h4><?=traduz("Nenhum resultado encontrado")?></h4></div></div>
        <?php
        }
}
?>

<script>

Shadowbox.init();

$("span[rel=lupa]").click(function () {
    var parametros = ["posto", "ativo"];
    $.lupa($(this), parametros);
});

function retorna_produto(retorno){
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);

    <?php
    if (in_array($login_fabrica, array(169,170))) {
    ?>
        $("#produto_serie").val(retorno.serie_produto);
    <?php
    }
    ?>
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
