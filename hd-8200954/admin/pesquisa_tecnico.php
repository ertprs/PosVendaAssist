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
//    echo $tecnico;
}

// array_funcao
// Estão no include array
include 'array_funcao.php';

?>
<!DOCTYPE html />
<html>
    <head>
        <meta http-equiv=pragma content=no-cache>
        <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />


        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="bootstrap/js/bootstrap.js"></script>
        <script src="plugins/dataTable.js"></script>
        <script src="plugins/resize.js"></script>


        <script>
            // $(function () {
            //  $.dataTableLupa();
            // });
        </script>
    </head>

    <body>
        <div id="container_lupa" style=" width:760px; height:600px; overflow-x:scroll;">
            <div id="topo">
                <img class="espaco" src="imagens/logo_new_telecontrol.png">
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
                        cpf,
                        rg,
                        cep,
                        estado,
                        cidade,
                        bairro,
                        endereco,
                        numero,
                        complemento,
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
    $funcionario_cep         = pg_fetch_result($res_ed,0, 'cep');
    $funcionario_estado      = pg_fetch_result($res_ed,0, 'estado');
    $funcionario_cidade      = pg_fetch_result($res_ed,0, 'cidade');
    $funcionario_bairro      = pg_fetch_result($res_ed,0, 'bairro');
    $funcionario_endereco    = pg_fetch_result($res_ed,0, 'endereco');
    $funcionario_numero      = pg_fetch_result($res_ed,0, 'numero');
    $funcionario_complemento = pg_fetch_result($res_ed,0, 'complemento');
    $funcionario_telefone    = pg_fetch_result($res_ed,0, 'telefone');
    $funcionario_celular     = pg_fetch_result($res_ed,0, 'celular');
    $funcionario_cpf         = pg_fetch_result($res_ed,0, 'cpf');
    $funcionario_rg          = pg_fetch_result($res_ed,0, 'rg');
    $funcionario_email       = pg_fetch_result($res_ed,0, 'email');
    $observacao              = pg_fetch_result($res_ed,0, 'observacao');
    $data_admissao           = pg_fetch_result($res_ed, 0, 'data_admissao');
    $formacao                = pg_fetch_result($res_ed, 0, 'formacao');
    $anos_experiencia        = pg_fetch_result($res_ed, 0, 'anos_experiencia');
    $dados_complementares    = pg_fetch_result($res_ed, 0, 'dados_complementares');

    $dados_complementares = json_decode($dados_complementares);


    foreach ($dados_complementares as $key => $value) {
        switch ($key) {
            case 'whatsapp':
                $numero_whatsapp = $value;
                break;
            case 'cep';
                $funcionario_cep = $value;
                break;
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
        <td>Nome</td>
        <td>Data Nascimento</td>
        <td>Função</td>
        <td>CPF</td>
        <td>RG</td>
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
        <td><?echo $funcionario_cpf?></td>
        <td><?echo $funcionario_rg?></td>
        </tr>
        </tbody>
    </table>
    <br />
<table class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
        <tr class='titulo_coluna'>
        <td>CEP</td>
        <td>Endereço</td>
        <td>Numero</td>
        <td>Complemento</td>
        <td>Bairro</td>
        <td>Cidade</td>
        <td>Estado</td>
        </tr>
        </thead>
        <tbody>
        <tr>
        <td><?echo $funcionario_cep?></td>
        <td><?echo $funcionario_endereco?></td>
        <td><?echo $funcionario_numero?></td>
        <td><?echo $funcionario_complemento?></td>
        <td><?echo $funcionario_bairro?></td>
        <td><?echo $funcionario_estado?></td>
        <td><?echo $funcionario_cidade?></td>
        </tr>
        </tbody>
    </table>
    <br />

    <table class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
        <tr class='titulo_coluna'>
        <td>Formação Acadêmica</td>
        <td>Anos de Experiencia</td>
        <td>Data admissão</td>
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
        <td>Telefone</td>
        <td>Celular</td>
        <td>Whatsapp</td>
        </tr>
        </thead>
        <tbody>
        <tr>
        <td><?echo $funcionario_telefone?></td>
        <td><?echo $funcionario_celular?></td>
        <td><?echo $numero_whatsapp?></td>
        </tr>
        </tbody>
    </table>

    <br />
    <table class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
        <tr class='titulo_coluna'>
        <td>E-Mail</td>
        <td style="width:15%">Nº Calçado</td>
        <td style="width:15%">Nº Camiseta</td>
        </tr>
        </thead>
        <tbody>
        <tr>
        <td><?echo $funcionario_email?></td>
        <td style="width:15%"><?=$numero_calcado?></td>
        <td style="width:15%"><?=$numero_camiseta?></td>
        </tr>
        </tbody>
    </table>
    <br />

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
    <br />

<?
}
?>
</div>
</body>
</html>
