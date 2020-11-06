<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';

if($login_fabrica==1){
    include "defeito_reclamado_cadastro_sem_integridade_teste.php";
    exit;
}

include 'funcoes.php';
include_once '../class/AuditorLog.php';

unset($msg_erro);

$msg_erro = array();
$btn_acao = trim($_REQUEST['btn_acao']);

if (strlen($_GET["tecnico"]) > 0)       $tecnico_id = trim($_GET["tecnico"]);


if (isset($_POST["ajax_ativa_inativa"])) {

    $tecnico = $_POST["tecnico"];
    $ativo   = $_POST["ativo"];

    $ativo = ($ativo == "Sim") ? "false" : "true";

    $sql = "
        UPDATE tbl_tecnico SET
            ativo = {$ativo}
        WHERE tecnico = {$tecnico}
        AND fabrica = {$login_fabrica};";
    pg_query($con,$sql);

    if (!pg_last_error()) {
        echo "sucesso";
    } else {
        echo "erro";
    }
    exit;
}

if (strlen($btn_acao) > 0) 
{
    if ($btn_acao == "submit") {

        $tecnico_nome     = trim($_POST["tecnico_nome"]);
        $tecnico_cpf      = trim($_POST["tecnico_cpf"]);
        $tecnico_email    = trim($_POST["tecnico_email"]);
        $tecnico_empresa  = trim($_POST["tecnico_empresa"]);
        $ativo            = trim($_POST['ativo']);

        if (strlen($ativo)==0)               $aux_ativo      = "f";
        else                                 $aux_ativo      = "t";

        if (strlen($tecnico_nome) == 0) {
            $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios <br />";
            $msg_erro["campos"][] = "tecnico_nome";
        }

        if (strlen($tecnico_cpf) == 0) {
            $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios <br />";
            $msg_erro["campos"][] = "tecnico_cpf";
        }

        if (strlen($tecnico_email) == 0) {
            $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios <br />";
            $msg_erro["campos"][] = "tecnico_email";
        }

        if (strlen($tecnico_empresa) == 0) {
            $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios <br />";
            $msg_erro["campos"][] = "tecnico_empresa";
        }

        if (strlen($msg_erro["msg"]["obg"]) == 0){ 
            $dados_complementares = json_encode(array("empresa" => utf8_encode($tecnico_empresa)));

            if (in_array($login_fabrica, array(169,170)))
            {
                $tipo_tecnico = 'TF';
                $campos_add   = ', tipo_tecnico, posto, ativo, tecnico';
            }

            $sql_check = "SELECT 
                                cpf
                                {$campos_add}
                            FROM tbl_tecnico
                            WHERE cpf = '{$tecnico_cpf}'
                            AND fabrica = {$login_fabrica}";
            $res_check = pg_query($con,$sql_check);
            if (pg_num_rows($res_check) > 0)
            {   
                if (strlen($_POST["tecnico_id"]) > 0){
                    $tecnico_id = trim($_POST['tecnico_id']);

                    $sql = "UPDATE tbl_tecnico SET
                            nome                 = '{$tecnico_nome}',
                            cpf                  = '{$tecnico_cpf}',
                            email                = '{$tecnico_email}',
                            dados_complementares = '{$dados_complementares}',
                            tipo_tecnico         = '{$tipo_tecnico}',
                            ativo                = '{$aux_ativo}'
                        WHERE fabrica = {$login_fabrica}
                        AND   tecnico = {$tecnico_id};";
                    $res = pg_query($con,$sql);

                    if (strlen(pg_last_error()) > 0) {
                        $msg_erro["msg"][] = pg_last_error();
                    }
                }else{
                    if (in_array($login_fabrica, [169,170])) {
                        $tipo_tecnico_res = pg_fetch_result($res_check, 0, 'tipo_tecnico');
                        $posto_id_res     = pg_fetch_result($res_check, 0, 'posto');
                        $ativo            = pg_fetch_result($res_check, 0, 'ativo');
                        $tecnico_id_res   = pg_fetch_result($res_check, 0, 'tecnico');

                        if ($tipo_tecnico_res == 'TF') {
                            $msg_erro["msg"][] = "Tenico já cadastrado!";    
                        } else if (!empty($posto_id_res)) {
                            $sql_posto = "SELECT 
                                              tbl_posto_fabrica.credenciamento,
                                              tbl_posto_fabrica.ativo
                                        FROM  tbl_posto_fabrica
                                              INNER JOIN tbl_tecnico ON tbl_tecnico.tecnico = {$tecnico_id_res}
                                        WHERE tbl_posto_fabrica.fabrica             = {$login_fabrica}
                                              AND tbl_posto_fabrica.posto           = {$posto_id_res}
                                              AND (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO')
                                              AND tbl_tecnico.ativo IS TRUE";
                            $res_posto = pg_query($con, $sql_posto);
                            
                            if (pg_num_rows($res_posto) > 0) {
                                $msg_erro["msg"][] = "Tenico já cadastrado!";        
                            }
                        }

                        if (count($msg_erro['msg']) == 0) {
                            if ($ativo == 'f') { $aux_ativo = 't'; }

                            /* SQL UPDATE */
                            $sql = "UPDATE tbl_tecnico SET
                                        nome                 = '{$tecnico_nome}',
                                        cpf                  = '{$tecnico_cpf}',
                                        email                = '{$tecnico_email}',
                                        dados_complementares = '{$dados_complementares}',
                                        tipo_tecnico         = '{$tipo_tecnico}',
                                        ativo                = '{$aux_ativo}'
                                    WHERE fabrica = {$login_fabrica}
                                    AND   tecnico = {$tecnico_id};
                                ";

                            if ($ativo == 'f') {
                                /* FAZ O UPDATE */
                                $res = pg_query($con,$sql);

                                if (strlen(pg_last_error()) > 0) {
                                    $msg_erro["msg"][] = pg_last_error();
                                }
                            }
                        }

                    } else {
                        $msg_erro["msg"][] = "Tenico já cadastrado!";
                    }
                }

                
            }else
            {
                $sql = "INSERT INTO tbl_tecnico (
                            nome,
                            cpf,
                            email,
                            dados_complementares,
                            tipo_tecnico,
                            ativo,
                            fabrica
                        ) VALUES (
                            '{$tecnico_nome}',
                            '{$tecnico_cpf}',
                            '{$tecnico_email}',
                            '{$dados_complementares}',
                            '{$tipo_tecnico}',
                            '{$aux_ativo}',
                            {$login_fabrica}
                        );
                    ";
                $res = pg_query($con,$sql);

                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["msg"][] = pg_last_error();
                }
            }        

            if (count($msg_erro['msg']) == 0) {
                pg_query($con,"COMMIT");
                header("location:$PHP_SELF?suc=Gravado com sucesso");
            } else {
                pg_query($con,"ROLLBACK");
            }       
        }
    }
}

if (isset($_GET['excluir']) && $_GET['excluir'] == 'sim') {

    $tecnico = (int) $_GET['tecnico'];

    if(!empty($tecnico)) {

        $sql_check = "SELECT tecnico 
                        FROM tbl_tecnico
                    WHERE tecnico = {$tecnico} 
                    AND fabrica = {$login_fabrica}";
        $res_check = pg_query($con,$sql_check);
        if (pg_num_rows($res_check) > 0)
        {
            $sql_del = "DELETE FROM
                            tbl_tecnico
                        WHERE tecnico = {$tecnico}
                        AND fabrica = {$login_fabrica}";
            $res_del = pg_query($con,$sql_del);
            if (strlen(pg_last_error()) > 0) {
                $msg_erro["msg"][] = "Não foi possível deletar o Tenico #1";
            }
        }
    }
}

if (strlen($tecnico_id) > 0) {
    $sql =  "SELECT 
                nome,
                cpf,
                email,
                dados_complementares,
                tecnico,
                ativo
            FROM tbl_tecnico
                WHERE fabrica = {$login_fabrica}
                AND tecnico = {$tecnico_id}";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
            $tecnico_id           = trim(pg_result($res,0,tecnico));
            $tecnico_nome         = trim(pg_result($res,0,nome));
            $tecnico_cpf          = trim(pg_result($res,0,cpf));
            $tecnico_email        = trim(pg_result($res,0,email));
            $dados_complementares = json_decode(trim(pg_result($res,0,dados_complementares)));
            $ativo                = trim(pg_result($res,0,ativo));
            $tecnico_empresa      = $dados_complementares->empresa;
    }
}

####### RES - TABLE ############
$sqlTabela = "SELECT 
                nome,
                cpf,
                email,
                dados_complementares,
                tecnico,
                ativo
            FROM tbl_tecnico
                WHERE fabrica = {$login_fabrica}
                AND tipo_tecnico = 'TF'";
$resTabela = pg_query($con,$sqlTabela);


$layout_menu = "info_tecnica";
$title = "CADASTRO DE CONVIDADOS";

include 'cabecalho_new.php';

$plugins = array("dataTable", "shadowbox", "mask");
include("plugin_loader.php");

?>

<script type="text/javascript" src="js/thickbox.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="js/jquery.maskmoney.js"></script>
<script type='text/javascript' src='../js/FancyZoom.js'></script>
<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>

<script type="text/javascript">
    $(function () {
        $(".btn_ativo_inativo").click(function(){

            var btn      = $(this);
            var tecnico  = $(btn).data("tecnico");
            var ativo    = $(btn).data("ativo");

            $.ajax({
                url: "cadastro_convidado.php",
                type: "POST",
                data: { 
                    ajax_ativa_inativa : true,
                    tecnico : tecnico,
                    ativo : ativo
                },
                beforeSend:function(){
                    $(btn).text("Alterando...");
                },
                complete: function (data) {
                    if (data != 'erro') {
                        $(btn).toggleClass("btn-success btn-danger");

                        if (ativo == "Sim") {
                            $(btn).text("Inativo");
                        } else {
                            $(btn).text("Ativo");
                        }

                    } else {
                        alert("Erro ao Ativar/Inativar Defeito");
                    }
                }
            });
        });
        $("#tecnico_cpf").mask("999.999.999-99",{placeholder:""});
    });
</script>

<style type="text/css">

.titulo_tabela{
background-color:#596d9b;
font: bold 14px "Arial";
color:#FFFFFF;
text-align:center;
}

.titulo_coluna{
background-color:#596d9b;
font: bold 11px "Arial";
color:#FFFFFF;
text-align:center;
}

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
margin: 0 auto;
}
.texto_avulso{
       font: 14px Arial; color: rgb(89, 109, 155);
       background-color: #d9e2ef;
       text-align: center;
       width:700px;
       margin: 0 auto;
}
.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{
background-color: #7092BE;
font:bold 11px Arial;
color: #FFFFFF;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
width: 700px;
}

.sucesso{
    color:#FFFFFF;
    font:bold 16px "Arial";
    text-align:center;
}

</style>

<?php
if ($_GET['suc']) {
    $msg = trim($_GET['suc'])
?>
    <div class="alert alert-success">
        <h4><?=$msg?></h4>
    </div>
<?php
}else{
    if (pg_last_error() || count($msg_erro['msg']) > 0) {
    ?>
        <div class="alert alert-error">
            <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
        </div>
    <?php
    }
}

?>
<div class="row">
    <b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>
<form name='frm_defeito' method='post' class="form-search form-inline tc_formulario" action='<?=$PHP_SELF?>'>
    <div class="titulo_tabela "><?=$title_page?></div>
    <br/>
    
   <div class="row-fluid">
        <div class="span1"></div>
        <input type="hidden" name="tecnico_id" value="<? echo $tecnico_id ?>">
        <div class="span4">
            <div class='control-group <?=(in_array('tecnico_nome', $msg_erro['campos'])) ? "error" : "" ?>'>
                <label class="control-label" for="tecnico_nome">Nome</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class='asteristico'>*</h5>
                        <input id="tecnico_nome" name="tecnico_nome" class="span12" type="text" value="<?=$tecnico_nome;?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span3">
            <div class='control-group <?=(in_array('tecnico_cpf', $msg_erro['campos'])) ? "error" : "" ?>'>
                <label class="control-label" for="tecnico_cpf">CPF</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class='asteristico'>*</h5>
                        <input id="tecnico_cpf" name="tecnico_cpf" class="span12" type="text" value="<?=$tecnico_cpf;?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span3">
            <div class='control-group <?=(in_array('tecnico_empresa', $msg_erro['campos'])) ? "error" : "" ?>'>
                <label class="control-label" for="tecnico_empresa">Empresa</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class='asteristico'>*</h5>
                        <input id="tecnico_empresa" name="tecnico_empresa" class="span12" type="text" value="<?=$tecnico_empresa;?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span1"></div>
    </div>
    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span3">
            <div class='control-group <?=(in_array('tecnico_email', $msg_erro['campos'])) ? "error" : "" ?>'>
                <label class="control-label" for="tecnico_email">Email</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class='asteristico'>*</h5>
                        <input id="tecnico_email" name="tecnico_email" class="span12" type="text" value="<?=$tecnico_email;?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span1"></div>
        <div class="span3">
            <div class='control-group <?=(in_array('ativo', $msg_erro['campos'])) ? "error" : "" ?>'>
                <label class="control-label" for="ativo">Ativo</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <input id="ativo" name="ativo"  type="checkbox" value="TRUE" <?php echo ($ativo == 't' || $ativo == 'TRUE') ? "checked=checked" : ""; ?>/>
                    </div>
                </div>
            </div>
        </div>
    </div>  
    <p>
        <br/>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
        <button class='btn' type="button" onclick="submitForm($(this).parents('form'));">Gravar</button>
    </p>
    <br/>
</form>

<table id="table_convidados" class="table table-striped table-bordered table-hover table-fixed">
    <thead>
        <tr class="titulo_tabela" >
            <th colspan="6">
                Convidados Cadastrados
            </th>
        </tr>
        <tr class='titulo_coluna'>
            <th>Nome</th>
            <th>CPF</th>
            <th>E-mail</th>
            <th>Empresa</th>
            <th>Ativo</th>
            <th>Ação</th>
        </tr>
    </thead>
    <tbody>
        <? for ($y = 0; $y < pg_num_rows($resTabela); $y++) {

            $php_self = $_SERVER['PHP_SELF'];

            $tecnico_id           = trim(pg_result($resTabela,$y,tecnico));
            $tecnico_nome         = trim(pg_result($resTabela,$y,nome));
            $tecnico_cpf          = trim(pg_result($resTabela,$y,cpf));
            $tecnico_email        = trim(pg_result($resTabela,$y,email));
            $dados_complementares = json_decode(trim(pg_result($resTabela,$y,dados_complementares)));
            $ativo                = trim(pg_result($resTabela,$y,ativo));
            $ativo                = ($ativo == "t") ? "Sim" : "Não";
            $tecnico_empresa      = utf8_decode($dados_complementares->empresa);

            $cor = ($y % 2 == 0) ? "#F7F5F0": '#F1F4FA'; ?>
            <tr style="background-color:<?=$cor?>">
                <td class="tac"> <?= $tecnico_nome; ?> </td>
                <td class="tac"> <?= $tecnico_cpf; ?> </td>
                <td class="tac"> <?= $tecnico_email; ?> </td>
                <td class="tac"> <?= $tecnico_empresa; ?> </td>
                <td class="tac">
                    <button data-tecnico="<?= $tecnico_id ?>" data-ativo="<?= $ativo ?>" type="button" class="btn_ativo_inativo btn btn-small <?=($ativo == 'Sim') ? 'btn-success' : 'btn-danger'?>"><?=($ativo == 'Sim') ? 'Ativo' : 'Inativo'?></button>
                </td>
                <td class="tac">
                    <a href="<?=$php_self;?>?tecnico=<?=$tecnico_id;?>" class='btn btn-warning'>Alterar</a>
                </td>
            </tr>
        <? } ?>
    </tbody>
</table>

<script>
    $(function(){

        Shadowbox.init();

        $.dataTableLoad({ table: "#defeito_reclamado" });

        <?php if (in_array($login_fabrica, [169,170])) { ?>
                $.dataTableLoad({ table: "#table_convidados" });
        <?php } ?> 
    });
</script>

<? include "rodape.php"; ?>
</body>
</html>
