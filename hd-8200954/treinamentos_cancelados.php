<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

/*ini_set("display_errors", 1);
error_reporting(E_ALL);*/

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include_once 'helpdesk/mlg_funciones.php';
include __DIR__.'/funcoes.php';
include __DIR__.'/cabecalho_new.php';
include __DIR__.'/admin/plugin_loader.php';

$sql = "SELECT 
        t.treinamento, 
        t.titulo, 
        to_char(t.data_inicio,'DD/MM/YYYY') as data_inicio, 
        to_char(t.data_fim,'DD/MM/YYYY') as data_fim, 
        t.descricao,
        t.ativo
    FROM tbl_treinamento t 
        JOIN tbl_treinamento_tipo tt using(treinamento_tipo) 
    WHERE t.fabrica = $login_fabrica 
        AND t.ativo IS NOT TRUE 
        AND tt.nome <> 'Palestra';";

$res = pg_query($con,$sql);
$num = pg_num_rows($res);
?>

<body>
    <style type="text/css">
        #btn-voltar {
            background-color: white; 
            border-color: white; 
            color: #3a87ad;
            cursor: pointer;
            text-decoration: none;

        }
    </style>
    <div class="container-fluid">
        <div class="row-fluid">
            <?php if ($num > 0) { ?>
            <table class="table table-bordered table-striped" >
                <thead>
                    <tr class="titulo_coluna" >
                        <th>Treinamento</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th width="60">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($treinamento = pg_fetch_array($res)) {
                        ?>
                        <tr>
                            <td><?=$treinamento['titulo']?></td>
                            <td class="tac"><?=$treinamento['data_inicio']?></td>
                            <td class="tac"><?=$treinamento['data_fim']?></td>
                            <td class="tac">
                            <?php if($treinamento['ativo'] == 't'){ ?>
                                    <img src='admin/imagens_admin/status_verde.gif' alt='Treinamento Cancelado' title='Treinamento Cancelado' />
                        <?php   }else{ ?>
                                    <img src='admin/imagens_admin/status_vermelho.gif' alt='Treinamento Ativo' title='Treinamento Ativo' />
                        <?php } ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>                        
                </tbody>
            </table>
            <?php } else { ?>
                <div class="alert alert-info" role="alert">
                    Nenhum Treinamento Cancelado Foi Encontrado. 
                </div>
            <?php } ?>
        </div>
        <div class="alert alert-light" role="alert" id="btn-voltar">
            <a href="treinamento_agenda.php"><< Voltar</a>
        </div>
    </div>
</body>


<?php
include "rodape.php";
?>