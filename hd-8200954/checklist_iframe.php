<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
if (isset($_GET["area_admin"]) && $_GET["area_admin"] == true) {
    include 'admin/autentica_admin.php';
} else {
    include_once 'autentica_usuario.php';
}

if ($_GET['defeitos_checklist'] == 'true') {

    $checklist = [];
    if ($_GET['checklist'] && $_GET['checklist'] != "undefined") {
        $checklist = explode(",", $_GET['checklist']);
    }

    $ja_preenchido      = (isset($_GET['ja_preenchido']) && $_GET['ja_preenchido'] == true) ? true : false;
    $familia            = $_GET['id_familia'];
    $defeito_constatado = $_GET['defeito_constatado'];
    $defeito_reclamado  = $_GET['defeito_reclamado'];
    $tipo_atendimento   = $_GET['tipo_atendimento'];

    $sql = "SELECT checklist_fabrica,
                    codigo,
                    descricao
                FROM tbl_checklist_fabrica
                WHERE fabrica = $login_fabrica
                AND familia = $familia
                AND tipo_atendimento = $tipo_atendimento
                AND codigo NOT IN ('51','52')";
    $res = pg_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checklist</title>
    <link href="plugins/bootstrap3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {height:100%;}
        .texto_avulso{
            font: 14px Arial;
            color: rgb(89, 109, 155);
            background-color: #d9e2ef;
            text-align: justify;
            width:700px;
            margin: 0 auto;
            border-collapse: collapse;
            border:1px solid #596d9b;
        }

        .titulo_tabela{
            background-color:#596d9b;
            font: bold 16px "Arial";
            color:#FFFFFF;
            text-align:center;
            padding: 10px;
        }

        .formulario{
            background-color:#D9E2EF;
            font:11px Arial;
            text-align:left;
        }
        .container-fluid{
            padding-top: 5px;
        }
        .tac{
            text-align: center;
        }
        .footer{
            position:absolute;
            bottom:0;
            width:100%;
        }
        label{
            font-weight: normal;
            cursor: pointer;
        }
    </style>
</head>
<body>
<script type='text/javascript' src='plugins/posvenda_jquery_ui/js/jquery-1.9.1.js'></script>

<script>
    $(function(){

        $(document).on("click", ".btn-seleciona-checklist", function(){
            var defeitoConstatado = $(this).data("constatado");
            var defeitoReclamado  = $(this).data("reclamado");
            var ja_preenchido  = $(this).data("japreenchido");
            var selecionadosIDS   = [];
            var selecionadosDESC  = [];
            $.each($("input[name^='check_list_fabrica"), function(index, val) {
                if ($(val).is(':checked')) {
                    selecionadosIDS.push($(this).val())
                    selecionadosDESC.push($(this).data("descricao"))
                }
            });
            if (selecionadosIDS.length == 0) {
                alert("Selecione pelo menos um item");
                return false;
            }
            window.parent.retornaCheckList(JSON.stringify(selecionadosIDS), JSON.stringify(selecionadosDESC), defeitoReclamado, defeitoConstatado, ja_preenchido);
        });
    });
</script>    
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="titulo_tabela">Checklist:</div>
        </div>
    </div>
    <div class="row">
    <?php 
        if (pg_num_rows($res) > 0) {

            for ($i=0; $i < pg_num_rows($res); $i++) {
                $checklist_fabrica = pg_fetch_result($res, $i, 'checklist_fabrica');
                $codigo = pg_fetch_result($res, $i, 'codigo');
                $descricao = pg_fetch_result($res, $i, 'descricao');

                if (mb_check_encoding($descricao, "UTF-8")) {
                    $descricao = utf8_decode($descricao);
                }

                $checked = (in_array($checklist_fabrica, $checklist)) ? "checked" : "";
                echo  "<div class='col-sm-6 col-md-6'>
                            <label for='$checklist_fabrica'>
                             <input type='checkbox' {$checked} id='$checklist_fabrica' data-descricao='".$codigo." - ".$descricao."' name='check_list_fabrica[]' value='$checklist_fabrica'> $codigo - $descricao
                            </label>
                        </div>";
            }
        }
    ?>
        <div class="col-sm-12 col-md-12 tac" style="margin-top: 20px;">
            <button type="button" data-japreenchido="<?php echo $ja_preenchido;?>" data-constatado="<?php echo $defeito_constatado;?>" data-reclamado="<?php echo $defeito_reclamado;?>" class="btn btn-success btn-seleciona-checklist" >Selecionar</button>
        </div>
    </div>
    <br /><br />
</div>
</body>
</html>
<?php } else {
    echo '<div class="alert alert-warning">CheckList não encontrado.</div>';
}
