<?php

$sql_subitem = "SELECT 
        tbl_peca_container.peca_container,
        tbl_peca_container.qtde,
        tbl_peca.referencia,
        tbl_peca.descricao,
        tbl_peca_container.peca_filha
    FROM tbl_peca_container
        JOIN tbl_peca ON tbl_peca.peca = tbl_peca_container.peca_filha
            AND tbl_peca.fabrica = tbl_peca_container.fabrica
    WHERE tbl_peca_container.peca_mae = {$xpeca}
        AND tbl_peca_container.fabrica = {$login_fabrica}
        AND tbl_peca_container.produto = {$produto}";
$res_subitem = pg_query($con,$sql_subitem);

if($modelo == true){
    $i = "__model__";
}
?>
<form name='frm_table_subitem' METHOD='POST' align='center' class='form-search form-inline' >
    <table class='table table-striped table-bordered table-hover table-<?=$table_large_fixed;?> subitem_pecas_<?=$i?>'>
        <thead>
            <tr class="titulo_coluna">
                <th>Peça</th>
                <th>Descrição</th>
                <th>Qtde</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_subitem = pg_num_rows($res_subitem);

            for($j = 0; $j <= $total_subitem; $j++){
                $peca_container          = "modelo_".$i;
                $subitem_peca_qtde       = "";
                $subitem_peca_referencia = "";
                $subitem_peca_descricao  = "";
                $subitem_gravar_linha    = "";
                $subitem_peca_filha      = "";
                $subitem_remover_linha   = 'style="display: none;"';
                
                if($j < pg_num_rows($res_subitem)){
                    $peca_container          = pg_fetch_result($res_subitem, $j, peca_container);
                    $subitem_peca_qtde       = pg_fetch_result($res_subitem, $j, qtde);
                    $subitem_peca_referencia = pg_fetch_result($res_subitem, $j, referencia);
                    $subitem_peca_descricao  = pg_fetch_result($res_subitem, $j, descricao);
                    $subitem_peca_filha      = pg_fetch_result($res_subitem, $j, peca_filha);
                    $subitem_gravar_linha    = 'style="display: none;"';
                    $subitem_remover_linha   = "";
                }
                ?>
            <tr id="subitem_tr_<?=$peca_container?>">
                <td class="valign-center">
                    <input type='hidden' value="<?=$i?>" id="linha_pai_<?=$i?>_<?=$j?>" name="linha_pai_<?=$i?>_<?=$j?>" />
                    <input type='hidden' value="<?=$j?>" id="linha_filho_<?=$i?>_<?=$j?>" name="linha_filho_<?=$i?>_<?=$j?>" />
                    <input type='hidden' value="<?=$peca_container?>" id="subitem_peca_container_<?=$i?>_<?=$j?>" />
                    <input type='hidden' value="<?=$subitem_peca_filha?>" id="subitem_peca_filha_<?=$i?>_<?=$j?>" />
                    <div class='input-append'>
                        <input type="text" id="subitem_peca_referencia_<?=$i?>_<?=$j?>" name="subitem_peca_referencia_<?=$i?>_<?=$j?>" class='span2' maxlength="20" value="<?=$subitem_peca_referencia?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" subitem="<?=$j?>" posicao="<?=$i?>" parametro="referencia" />
                    </div>
                </td>
                <td class="valign-center">
                    <div class='input-append'>
                        <input type="text" id="subitem_peca_descricao_<?=$i?>_<?=$j?>" name="subitem_peca_descricao_<?=$i?>_<?=$j?>" class='span3' value="<?=$subitem_peca_descricao?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" subitem="<?=$j?>" posicao="<?=$i?>" parametro="descricao" />
                    </div>
                </td>
                <td class="valign-center tac">
                    <input type="text" id="subitem_qtde_<?=$i?>_<?=$j?>" name="subitem_qtde_<?=$i?>_<?=$j?>" class="inptc1" value="<?=$subitem_peca_qtde?>" />
                </td>
                <td class="valign-center tac" >
                    <button type="button" class="btn btn-small" id="subitem_gravar_linha_<?=$i?>_<?=$j?>" name="subitem_gravar_linha_<?=$i?>" <?=$subitem_gravar_linha?>>Gravar</button>
                    <button class='btn btn-danger btn-small' id="subitem_remover_linha_<?=$i?>_<?=$j?>" type="button" <?=$subitem_remover_linha?>>Excluir</button>
                </td>
            </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
</form>