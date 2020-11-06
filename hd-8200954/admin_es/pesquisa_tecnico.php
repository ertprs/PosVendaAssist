<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'autentica_usuario.php';
include 'funcoes.php';


   // echo "<pre>";
   // print_r($_GET);
   // echo "</pre>";

if (!empty($_GET["tecnico"])) {
    $tecnico = $_GET["tecnico"];
}

// array_funcao
// Estão no include arrays_bosch
include '../admin/array_funcao.php';

?>
<!DOCTYPE html />
<html>
    <head>
        <meta http-equiv=pragma content=no-cache>
        <link href="../admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="../admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="../admin/css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="../admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="../admin/plugins/dataTable.css" type="text/css" rel="stylesheet" />


        <script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="../admin/bootstrap/js/bootstrap.js"></script>
        <script src="../admin/plugins/dataTable.js"></script>
        <script src="../admin/plugins/resize.js"></script>


        <script>
            // $(function () {
            //  $.dataTableLupa();
            // });
        </script>
    </head>

    <body>
        <div id="container_lupa" style="overflow-y:auto;">
            <div id="topo">
                <img class="espaco" src="../imagens/logo_new_telecontrol.png">
            </div>
            <br />
            <hr />
<?
//Lista todos os Funcionários cadastrados do posto
    $sql_ed = "SELECT fabrica,
                        posto,
                        tecnico,
                        nome,
                        to_char(data_nascimento, 'DD/MM/YYYY') AS data_nascimento,
                        funcao,
                        rg,
                        cep,
                        bairro,
                        estado,
                        cidade,
                        endereco,
                        numero,
                        complemento,
                        to_char(data_admissao, 'DD/MM/YYYY') AS data_admissao,
                        telefone,
                        celular,
                        email,
                        observacao,
                        to_char(data_admissao, 'DD/MM/YYYY') AS data_admissao,
                        formacao,
                        anos_experiencia,
                        dados_complementares
                FROM tbl_tecnico
                WHERE tecnico = $tecnico";
//echo $sql_ed;
    $res_ed = pg_query($con,$sql_ed);

    $tecnico                 = pg_fetch_result($res_ed,0, 'tecnico');
    $funcionario_nome        = pg_fetch_result($res_ed,0, 'nome');
    $data_nascimento         = pg_fetch_result($res_ed,0, 'data_nascimento');
    $funcao                  = pg_fetch_result($res_ed,0, 'funcao');
    $funcionario_estado      = pg_fetch_result($res_ed,0, 'estado');
    $funcionario_cidade      = pg_fetch_result($res_ed,0, 'cidade');
    $funcionario_bairro      = pg_fetch_result($res_ed,0, 'bairro');
    $funcionario_endereco    = pg_fetch_result($res_ed,0, 'endereco');
    $funcionario_complemento = pg_fetch_result($res_ed,0, 'complemento');
    $funcionario_telefone    = pg_fetch_result($res_ed,0, 'telefone');
    $funcionario_celular     = pg_fetch_result($res_ed,0, 'celular');
    $funcionario_rg          = pg_fetch_result($res_ed,0, 'rg');
    $funcionario_email       = pg_fetch_result($res_ed,0, 'email');
    $observacao              = pg_fetch_result($res_ed,0, 'observacao');
    $data_admissao           = pg_fetch_result($res_ed, 0, 'data_admissao');
    $formacao                = pg_fetch_result($res_ed, 0, 'formacao');
    $anos_experiencia        = pg_fetch_result($res_ed, 0, 'anos_experiencia');
    $numero_whatsapp         = pg_fetch_result($res_ed, 0, 'dados_complementares');

    $dados_complementares = json_decode($numero_whatsapp);

    foreach ($dados_complementares as $key => $value) {
        switch ($key) {
            case 'whatsapp':
                $numero_whatsapp = $value;
                break;
            case 'cep':
                $funcionario_cep = $value;
            case 'numero_calcado':
                $numero_calcado = $value;
            case 'numero_camiseta':
                $numero_camiseta = $value;
            break;

        }
    }

if(pg_num_rows($res_ed) > 0){
?>
<table class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
        <tr class='titulo_coluna'>
        <td>Nombre</td>
        <td>Fecha de Nascimiento</td>
        <td>Funcción</td>
        <td>Numero de Identificacion</td>
        </tr>
        </thead>
        <tbody>
        <tr>
        <td><?echo $funcionario_nome?></td>
        <td><?echo $data_nascimento?></td>
        <?if ($funcao === "T" ) {
            echo "<td>Técnico</td>";
        }elseif ($funcao === "A") {
            echo "<td>Administrativo</td>";
        }elseif ($funcao === "G") {
            echo "<td>Gerente AT</td>";
        }?>
        <td><?echo $funcionario_rg?></td>
        </tr>
        </tbody>
</table>
<table class='table table-striped table-bordered table-hover table-fixed'>
<thead>
    <tr class='titulo_coluna'>
        <td>Formación Academica</td>
        <td>Años de Experiencia</td>
        <td>Fecha Admisión</td>
    </tr>
</thead>
<tbody>
    <tr>
        <td><?echo $formacao?></td>
        <td><?echo $anos_experiencia?></td>
        <td><?echo $data_admissao?></td>
    </tr>
</tbody>
</table>

    <br />
<table class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
        <tr class='titulo_coluna'>
        <td>Código Postal</td>
        <td>Dirección</td>
        <td>Ciudad</td>
        <td>Provincia/Departamento</td>
        </tr>
        </thead>
        <tbody>
        <tr>
        <td><?echo $funcionario_cep?></td>
        <td><?echo $funcionario_endereco?></td>
        <td><?echo $funcionario_cidade?></td>
        <td><?echo $funcionario_bairro?></td>
        </tr>
        </tbody>
    </table>
    <br />
    <table class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
        <tr class='titulo_coluna'>
        <td>Telefono Fijo</td>
        <td>Telefono Movil</td>
        <td>Whatsapp</td>
        <td>Calzado</td>
        <td>Camiseta</td>
        <td>Correo Electronico</td>
        </tr>
        </thead>
        <tbody>
        <tr>
        <td><?echo $funcionario_telefone?></td>
        <td><?echo $funcionario_celular?></td>
        <td><?echo $numero_whatsapp?></td>
        <td><?echo $numero_calcado?></td>
        <td><?echo $numero_camiseta?></td>
        <td><?echo $funcionario_email?></td>
        </tr>
        </tbody>
    </table>
    <table class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
        <tr class='titulo_coluna'>
        <td>Observação</td>
        </tr>
        </thead>
        <tbody>
        <tr>
        <td><?echo $observacao?></td>
        </tr>
        </tbody>
    </table>

<?
}
?>
</div>
</body>
</html>
