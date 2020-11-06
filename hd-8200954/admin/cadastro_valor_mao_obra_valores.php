<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$familia = filter_input(INPUT_GET,"familia");

/**
 * - gravaValores()
 *
 * Função principal para gravação de
 * valores mão de obra
 *
 * @param $con Conexão global com BD
 * @param $login_fabrica ID da fábrica
 * @param $valores Dados serializados do formulário dinâmico
 *
 * @return mensagem de sucesso || Erro em campos
 */
function gravaValores($valores)
{
    global $con, $login_fabrica;

    /*
     * - Separação dos dados serializados
     */
    $valor = explode("&",$valores);
    $valor = array_map("rawurldecode", $valor);

    foreach($valor as $key => $value){
        $dados = explode("=",$value);

        if ($dados[1] == "") {
            return false;
        }
	
    	if (strpos($dados[0], "_") != false) {
    	    $chaves = explode("_", $dados[0]);
    	} else {
    	    $familia = $dados[1];
    	}

    	if (!empty($chaves)) {
    	    $results[$familia][$chaves[1]][$chaves[0]] = $dados[1];
    	}
    }

    $rollback = false;

    foreach ($results as $familia => $mobra) {
        pg_query($con, "BEGIN;");
	
        $deleteMobraFamilia = "
            DELETE FROM tbl_mao_obra_servico_realizado
            WHERE fabrica = {$login_fabrica}
            AND familia = {$familia}
            AND tempo_estimado IS NOT NULL
            AND servico_realizado IS NOT NULL;
        ";
        
        pg_query($con, $deleteMobraFamilia);

        if (strlen(pg_last_error()) == 0) {
            foreach ($mobra as $campos => $valores) {
                if ($valores['produto'] > 0) {
                    
                    $valores['produto'] = number_format($valores['produto'], 2, ".", "");
                    
                    $insertProduto = "
                        INSERT INTO tbl_mao_obra_servico_realizado
                        (fabrica,familia,tempo_estimado,mao_de_obra,servico_realizado)
                        SELECT fabrica,{$familia},{$valores['dias']},{$valores['produto']},servico_realizado
                        FROM tbl_servico_realizado
                        WHERE fabrica = {$login_fabrica}
                        AND troca_produto IS TRUE
                        AND ativo IS TRUE;
                    ";

                    pg_query($con, $insertProduto);

                }

                if ($valores['peca'] > 0) {
                    
                    $valores['peca'] = number_format($valores['peca'], 2, ".", "");
                    
                    $insertPeca = "
                        INSERT INTO tbl_mao_obra_servico_realizado
                        (fabrica,familia,tempo_estimado,mao_de_obra,servico_realizado)
                        SELECT fabrica,{$familia},{$valores['dias']},{$valores['peca']},servico_realizado
                        FROM tbl_servico_realizado
                        WHERE fabrica = {$login_fabrica}
                        AND solucao IS TRUE
                        AND troca_produto IS NOT TRUE
                        AND ativo IS TRUE;
                    ";
                    
                    pg_query($con, $insertPeca);

                }

                if ($valores['placa'] > 0) {
                    
                    $valores['placa'] = number_format($valores['placa'], 2, ".", "");
                    
                    $insertPlaca = "
                        INSERT INTO tbl_mao_obra_servico_realizado
                        (fabrica,familia,tempo_estimado,mao_de_obra,servico_realizado)
                        SELECT fabrica,{$familia},{$valores['dias']},{$valores['placa']},servico_realizado
                        FROM tbl_servico_realizado
                        WHERE fabrica = {$login_fabrica}
                        AND solucao IS NOT TRUE
                        AND troca_produto IS NOT TRUE
                        AND ativo IS TRUE;
                    ";
                    
                    pg_query($con, $insertPlaca);
                
                }

                if (strlen(pg_last_error()) > 0) {
                    $rollback = true;
                }

            }

        } else {
            
            $rollback = true;

        }

        if ($rollback == true) {

            pg_query($con, "ROLLBACK;");

        } else {

            pg_query($con, "COMMIT;");

        }
    }

    if ($rollback == false) {
        return json_encode(array("ok" => true));
    }

    return false;

}

/*
 * - Área de tratamento dos
 * dados em AJAX
 */
if (filter_input(INPUT_POST,"ajax",FILTER_VALIDATE_BOOLEAN)) {
    $ajaxType   = filter_input(INPUT_POST,"ajaxType");
    $valores    = filter_input(INPUT_POST,"valores");

    switch ($ajaxType) {
        case "gravar":
	    //print_r(gravaValores($valores));
            echo gravaValores($valores);
            break;
    }
    exit;
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

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<script src="plugins/dataTable.js"></script>
<script src="plugins/resize.js"></script>
<script src="plugins/shadowbox_lupa/lupa.js"></script>

<?php
$plugins = array(
    "alphanumeric",
    "price_format"
);

include("plugin_loader.php");
?>

<style type="text/css">

tr.linha_modelo {
    display:none;
}

</style>
</head>
<?php
$sql = "
    SELECT DISTINCT
        CASE WHEN troca_produto IS TRUE THEN 1 ELSE CASE WHEN troca_produto IS NOT TRUE AND solucao IS NOT TRUE THEN 2 ELSE 3 END END AS prioridade,
        mosr.tempo_estimado,
        mosr.mao_de_obra,
        sr.troca_produto,
        sr.solucao
    FROM tbl_mao_obra_servico_realizado mosr
    JOIN tbl_servico_realizado sr USING(servico_realizado,fabrica)
    WHERE mosr.fabrica = {$login_fabrica}
    AND mosr.familia = {$familia}
    ORDER BY prioridade ASC;
";

$res = pg_query($con, $sql);

$dados = pg_fetch_all($res);

foreach ($dados as $mobra) {
    $valores[$mobra['tempo_estimado']]['dias'] = (int) $mobra['tempo_estimado'];
    
    if ($mobra['troca_produto'] == 't') {
        $valores[$mobra['tempo_estimado']]['produto'] = number_format($mobra['mao_de_obra'],2,',','');
    }

    if ($mobra['solucao'] == 't' && $mobra['troca_produto'] != 't') {
        $valores[$mobra['tempo_estimado']]['peca'] = number_format($mobra['mao_de_obra'],2,',','');
    } else if ($mobra['troca_produto'] != 't') {
        $valores[$mobra['tempo_estimado']]['placa'] = number_format($mobra['mao_de_obra'],2,',','');
    }
} ?>
<body>
    <form action="<?=$_SERVER['PHP_SELF']?>" method='POST'>
        <input type="hidden" name="familia" class="span12" value='<?=$familia?>' />
        <div id="border_table">
            <table class="table table-striped table-bordered table-hover table-lupa" style="margin-bottom:60px !important;">
                <thead>
                    <tr class='titulo_coluna'>
                        <th>Qtde dias</th>
                        <th>Troca Produto</th>
                        <th>Troca Placa</th>
                        <th>Troca Peça / Ajuste</th>
                        <th><a class="btn btn-primary" id="addLinha" role="button">Adicionar Linha</a></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="linha_modelo">
                        <td><input type="text" class="tar span2 integer" name="dias__MODELO__" id="dias__MODELO__" maxlength="2" /></td>
                        <td><input type="text" class="tar span2 numeric" name="produto__MODELO__" id="produto__MODELO__" /></td>
                        <td><input type="text" class="tar span2 numeric" name="placa__MODELO__" id="placa__MODELO__" /></td>
                        <td><input type="text" class="tar span2 numeric" name="peca__MODELO__" id="peca__MODELO__" /></td>
                        <td>&nbsp;</td>
                    </tr>
                    <? if (!is_array($valores)) { ?>
                    	<tr class="linha_0">
                            <td><input type="text" class="tar span2 integer" name="dias_0" id="dias_0" maxlength="2" /></td>
                            <td><input type="text" class="tar span2 numeric" name="produto_0" id="produto_0" /></td>
                            <td><input type="text" class="tar span2 numeric" name="placa_0" id="placa_0" /></td>
                            <td><input type="text" class="tar span2 numeric" name="peca_0" id="peca_0" /></td>
                            <td>&nbsp;</td>
                        </tr>
                    <? } else {
                        $linha = 0;
                        foreach ($valores as $chaves) {
                            foreach ($chaves as $key => $val) {
                                if ($key == "dias") { ?>
                                    <tr class="linha_<?= $linha; ?>">
                                        <td><input type="text" class="tar span2 integer" name="dias_<?= $linha; ?>" id="dias_<?= $linha; ?>" value="<?= $val; ?>" maxlength="2" /></td>
                                <? } else if ($key != "peca") { ?>
                                    <td><input type="text" class="tar span2 numeric" name="<?= $key; ?>_<?= $linha; ?>" id="<?= $key; ?>_<?= $linha; ?>" value="<?= $val; ?>" /></td>
                                <? } else { ?>
                                        <td><input type="text" class="tar span2 numeric" name="peca_<?= $linha; ?>" id="peca_<?= $linha; ?>" value="<?= $val; ?>" /></td>
    				                    <td>&nbsp;</td>
    				                </tr>
    				            <? }
                            }
                            $linha++;
                        }
                    } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5">
                            <a class="btn btn-success" id="confirm" role="button">Confirmar</a>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </form>
</body>
<script type="text/javascript">
$(function(){

    $("#addLinha").click(function(){
        var linha   = $(".linha_modelo").clone();
        var ultima  = $("tbody > tr[class!=linha_modelo]").length;

        linha.attr({"class": "linha_"+ultima});
        $("tbody > tr").last().after(linha);

        $(".linha_"+ultima).find("input").each(function(){
            var local       = $(this);
            var name        = local.attr("name");
            var id          = local.attr("id");
            var novoNome    = name.replace(/_MODELO__/g, ultima);
            var novoId      = id.replace(/_MODELO__/g, ultima);

            local.attr({
                "name":novoNome,
                "id":novoId
            });

            $('.numeric').priceFormat({
                prefix: '',
                thousandsSeparator: '',
                centsSeparator: ',',
                centsLimit: 2
            });

            $(".integer").numeric();
        });
    });

    $("#confirm").click(function(){
        var valores = $("input:not([name*=_MODELO__])").serialize();
	
        $.ajax({
            url:"cadastro_valor_mao_obra_valores.php",
            type:"POST",
            data:{
                ajax:true,
                ajaxType:"gravar",
                valores:valores
            }
        })
        .fail(function(){
            alert("Erro ao gravar");
        })
        .done(function(data) {
            data = $.parseJSON(data);
            if (data.ok) {
                alert("Dados gravados com sucesso!");
                window.parent.Shadowbox.close();
            } else {
                alert("Ocorreu um erro durante a gravação dos dados!");
            }
        });
    });

    $('.numeric').priceFormat({
        prefix: '',
            thousandsSeparator: '',
            centsSeparator: ',',
            centsLimit: 2
    });

    $(".integer").numeric();

});
</script>
</html>
