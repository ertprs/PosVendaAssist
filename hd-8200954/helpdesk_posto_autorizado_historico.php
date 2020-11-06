<?
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin === true) {
	include __DIR__.'/admin/autentica_admin.php';
} else {
	include __DIR__.'/autentica_usuario.php';
}

$os = filter_input(INPUT_GET,'os');
?>
<link media="screen" rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.css">
<link media="screen" rel="stylesheet" type="text/css" href="bootstrap/css/glyphicon.css">
<link media="screen" rel="stylesheet" type="text/css" href="bootstrap/css/extra.css">
<link media="screen" rel="stylesheet" type="text/css" href="css/tc_css.css">
<link media="screen" rel="stylesheet" type="text/css" href="bootstrap/css/ajuste.css">

<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script type="text/javascript" src="bootstrap/js/bootstrap.js"></script>

<style type="text/css">
td.info {
	background-color: #D9EDF7 !important;
}

td.success {
	background-color: #DFF0D8 !important;
}

td.error {
	background-color: #F2DEDE !important;
}
</style>
<table class="table table-bordered table-striped" style="table-layout: fixed; width:600px; margin: 0 auto; border-collapse: collapse;"  >
    <tbody>
        <tr>
            <? if ($areaAdmin === true) { ?>
                <td class="tac warning" style="border: 1px solid #fff;" >Interação Interna</td>
            <? } ?>
            <td class="tac info" style="border: 1px solid #fff;" >Resposta Conclusiva</td>
            <td class="tac success" style="border: 1px solid #fff;" >Finalizado</td>
            <td class="tac error" style="border: 1px solid #fff;" >Cancelado</td>
        </tr>
    </tbody>
</table>

<br />

<table class="table table-bordered table-striped" >
    <thead>
        <tr class="titulo_coluna" >
            <th>Nº Help-Desk</th>
            <th>Status</th>
            <th>Mensagem</th>
            <th>Admin</th>
            <th>Data</th>
        </tr>
    </thead>
    <tbody>
<?php
    if($areaAdmin === false and $login_fabrica == 35){
        $condInterno = " and tbl_hd_chamado_item.interno <> 't' " ; 
    }

    $sqlInteracoes = "
        SELECT  tbl_admin.nome_completo AS admin,
                tbl_hd_chamado_item.hd_chamado,
                tbl_hd_chamado_item.posto,
                tbl_hd_chamado_item.comentario,
                tbl_hd_chamado_item.interno,
                tbl_hd_chamado_item.status_item,
                TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY HH24:MI') AS data
        FROM    tbl_hd_chamado_item
        JOIN    tbl_hd_chamado_extra    USING (hd_chamado)
        JOIN    tbl_hd_chamado          USING (hd_chamado)
   LEFT JOIN    tbl_admin               ON  tbl_admin.admin     = tbl_hd_chamado_item.admin
                                        AND tbl_admin.fabrica   = {$login_fabrica}
        WHERE   tbl_hd_chamado_extra.os = $os
        AND     tbl_hd_chamado.titulo   = 'Help-Desk Posto'
        $condInterno 
  ORDER BY      tbl_hd_chamado_item.data DESC";
        $resInteracoes = pg_query($con, $sqlInteracoes);

        $i = pg_num_rows($resInteracoes);

        while($interacao = pg_fetch_object($resInteracoes)) {
            if ($areaAdmin === false && $interacao->interno == "t") {
                $i--;
                continue;
            }

            $class_tr = "";

            switch ($interacao->status_item) {
                case 'Interação Interna':
                    $class_tr = "warning";
                    break;

                case 'Resposta Conclusiva':
                    $class_tr = "info";
                    break;

                case 'Finalizado':
                    $class_tr = "success";
                    break;

                case 'Cancelado':
                    $class_tr = "error";
                    break;
            }
            ?>
            <tr class="<?=$class_tr?>" >
                <td><a href="helpdesk_posto_autorizado_atendimento.php?hd_chamado=<?=$interacao->hd_chamado?>" target="_blank" ><?=$interacao->hd_chamado?></a></td>
                <td><?=$interacao->status_item?></td>
                <td><?=$interacao->comentario?></td>
                <td><?=(!empty($interacao->posto)) ? "Posto Autorizado" : $interacao->admin ?></td>
                <td><?=$interacao->data?></td>
            </tr>
        <?
            $i--;
        }
        ?>
    </tbody>
</table>