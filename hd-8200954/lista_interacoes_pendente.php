<br />

<style>
#interacoes_pendentes tr.interacao_display_none {
    display: none;
}
</style>

<script>
$(function() {

    $("#mostrar_todas_interacoes_pendentes").click(function() {
        $("#interacoes_pendentes tr.interacao_display_none").each(function() {
            $(this).removeClass("interacao_display_none");
        });

        $("#interacoes_pendentes > tfoot").remove();
    });

});
</script>
<?php 
    $sqlInteracoesPendentes = "
            SELECT 
                descricao,
                registro_id,
                nome,
                posto,
                contexto,
                (SELECT to_char(data,'DD/MM/YYYY') FROM tbl_interacao i WHERE i.registro_id = e.registro_id ORDER BY data DESC limit 1 ) AS data
            FROM tbl_interacao e
                JOIN tbl_contexto USING (contexto)  
                JOIN tbl_posto USING (posto)        
            WHERE  e.fabrica = {$login_fabrica}
                AND e.admin is not null 
                AND e.confirmacao_leitura is null
                AND e.interno = 'f' 
                AND e.posto = {$login_posto}
            GROUP BY registro_id, nome, descricao, posto, contexto";
    $resInteracoesPendentes = pg_query($con, $sqlInteracoesPendentes);
if (pg_num_rows($resInteracoesPendentes) > 0 ) {
?>
    <table id="interacoes_pendentes" class='table_tc' style="margin: 0 auto; width: 55%;" >
        <thead>
            <tr>
                <th style="background-color: #DD0010; color: #FFFFFF; text-align: center;" colspan="3" ><?= traduz('Interações pendentes') ?></th>
            </tr>
            <tr class='titulo_coluna'>
                <th style="text-align: center;" ><?= traduz('Tipo Interação') ?></th>
                <th style="text-align: center;" ><?= traduz('Código') ?></th>
                <th style="text-align: center;" ><?= traduz('Data') ?></th>
            </tr>
        </thead>
        <tbody style="max-height: 50px; overflow-y: auto;">
        <?php while ($interacaoPendente = pg_fetch_object($resInteracoesPendentes)) { ?>
            <tr <?=($c > 5 ? "class='interacao_display_none'" : "")?> >
                <td style='text-align: center;' ><?=$interacaoPendente->descricao?></td>
                <?php if ($interacaoPendente->contexto == 2 ) { ?>
                    <td style='text-align: center;' ><a href="pedido_finalizado.php?pedido=<?=$interacaoPendente->registro_id?>" target="_blank"><?=$interacaoPendente->registro_id?></a></td>
                <?php } else { ?>
                    <td style='text-align: center;' ><?=$interacaoPendente->registro_id?></td>
                <?php } ?>
                <td style='text-align: center;' ><?=$interacaoPendente->data?></td>
            </tr>
            <?php 
            $c++;
        }
        ?>
        </tbody>
        <?php if (pg_num_rows($resInteracoesPendentes) > 5) { ?>
            <tfoot>
                <tr>
                    <th id="mostrar_todas_interacoes_pendentes" style="background-color: #485989; color: #FFFFFF; cursor: pointer;" colspan="3" ><?= traduz('Mostrar todas as Interaçõe') ?></th>
                </tr>
            </tfoot>
        <?php }?>
    </table>
<?php } ?>