<?php

if (empty($include_aguarda_inspecao)) {
    die('Erro');
}
echo '
    <style>
        table.tabela {border-collapse: collapse; font-size: 14px;}
        table.tabela tr th {border:1px solid #808080; background: #E1E1E1; }
        table.tabela tr td {border:1px solid #808080;}
    </style>';

$sql = "select tbl_extrato.extrato,
                tbl_os_extra.os,
                tbl_peca.referencia,
                tbl_peca.descricao
            from tbl_extrato
            join tbl_os_extra using(extrato)
            join tbl_os_produto using(os)
            join tbl_os_item using(os_produto)
            join tbl_peca using(peca) 
            where tbl_extrato.fabrica = $login_fabrica
            and tbl_extrato.posto = $login_posto
	    and tbl_peca.aguarda_inspecao 
	    and tbl_extrato.liberado notnull
            and tbl_extrato.data_geracao > (now() - interval '90 days')";
$qry = pg_query($con, $sql);

$i = 0;

if (pg_num_rows($qry) > 0) {
    echo '<br/>';
    echo '<table width="700" class="tabela">';
    echo '<thead>';
        echo '<tr>';
            echo '<th colspan="3"><a href="javascript:void(0);" id="inspecao">Peças aguardando inspeção</a></th>';
        echo '</tr>';
        echo '<tr class="inspecao" style="display:none;">';
            echo '<th>Extrato</th>';
            echo '<th>OS</th>';
            echo '<th>Peça</th>';
        echo '</tr>';
    echo '</thead>';
    
    echo '<tbody>';
    while ($fetch = pg_fetch_assoc($qry)) {
        $extrato = $fetch['extrato'];
        $os = $fetch['os'];
        $peca = $fetch['referencia'] . ' - ' . $fetch['descricao'];

        if ($i % 2 == 0) {
            $bgcolor = '#FFFFFF';
        } else {
            $bgcolor = '#EAEAEA';
        }

        echo '<tr class="inspecao" style="text-align: center; background: ' . $bgcolor . '; display:none;">';
            echo '<td><a target="_blank" href="os_extrato_detalhe.php?extrato=' . $extrato . '">' . $extrato . '</a></td>';
            echo '<td><a target="_blank" href="os_press.php?os=' . $os . '">' . $os . '</a></td>';
            echo '<td align="left">' . $peca . '</td>';
        echo '</tr>';
        
        $i++;
    }

    echo '<tr style="display:none;" class="inspecao">';
        echo '<td colspan="3"><em>Estas peças devem ficar à disposição para inspeção do fabricante por 90 dias.</em></td>';
    echo '</tr>';
    
    echo '</tbody>';
    echo '</table>';
    echo '<br/>';
}
