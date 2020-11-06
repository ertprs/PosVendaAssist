<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include_once dirname(__FILE__) . '/../class/AuditorLog.php';
include 'funcoes.php';

if( isset($_POST['gravar']) || isset($_POST['pesquisar']) ) {
    $serie              = $_POST['serie'];
    $peca_referencia    = $_POST['peca_referencia'];
    $peca_descricao     = $_POST['peca_descricao'];
    $numero_serie_peca  = $_POST['numero_serie_peca'];
    
    if (strlen($peca_referencia) > 0 or strlen($peca_descricao) > 0){
        $sql = "SELECT peca
                FROM tbl_peca
                WHERE fabrica = {$login_fabrica}
                AND (
                    (UPPER(referencia) = UPPER('{$peca_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$peca_descricao}'))
                )";
        $res = pg_query($con ,$sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][]    = "Peça não encontrada";
            $msg_erro["campos"][] = "peca";
        } else {
            $peca = pg_fetch_result($res, 0, "peca");
        }
    }

    if (!count($msg_erro["msg"])) {
        if (isset($_POST["gravar"])){
            if (empty($serie) AND empty($peca_referencia) AND empty($peca_descricao)){
                $msg_erro["msg"][] = "Preencha os campos obrigatórios";
                $msg_erro["campos"][] = "serie";
                $msg_erro["campos"][] = "peca";
            }else if (empty($serie) AND !empty($peca_descricao) AND !empty($peca_descricao)){
                $msg_erro["msg"][] = "Preencha os campos obrigatórios";
                $msg_erro["campos"][] = "serie";
            }else if (!empty($serie) AND empty($peca_descricao)){
                $msg_erro["msg"][] = "Preencha os campos obrigatórios";
                $msg_erro["campos"][] = "peca";
            }
            if (!count($msg_erro["msg"])) {
                if (!empty($serie) AND !empty($peca)){
                    
                    if (empty($numero_serie_peca)){
                        $sql = "SELECT numero_serie_peca FROM tbl_numero_serie_peca WHERE fabrica = {$login_fabrica} AND peca = {$peca} AND serie_peca = '{$serie}'";
                        $res = pg_query($con, $sql);
                        if (pg_num_rows($res) == 0){
                            $insert = "
                                INSERT INTO tbl_numero_serie_peca (
                                    fabrica,
                                    serie_peca,
                                    peca,
                                    referencia_peca
                                )VALUES(
                                    {$login_fabrica},
                                    '{$serie}',
                                    {$peca},
                                    '{$peca_referencia}'
                                )";
                            $res_insert = pg_query($con, $insert);

                            if (strlen(pg_last_error()) > 0){
                                $msg_erro["msg"][] = "Erro ao gravar número de série da peça";
                            }else{
                                $msg_success = "Número de série cadastrado com sucesso";
                                unset($numero_serie_peca);
                            }
                        }else{
                            $msg_erro["msg"][] = "Número de série já cadastrado para essa Peça";
                            $msg_erro["campos"][] = "serie";
                        }
                    }else{
                        $update = "UPDATE tbl_numero_serie_peca 
                                    SET serie_peca = '{$serie}', peca = {$peca}, referencia_peca = '{$referencia_peca}'
                                    WHERE tbl_numero_serie_peca.fabrica = {$login_fabrica}
                                    AND tbl_numero_serie_peca.numero_serie_peca = $numero_serie_peca ";
                        $res_update = pg_query($con, $update);
                        if (strlen(pg_last_error()) > 0){
                            $msg_erro["msg"][] = "Erro ao gravar número de série da peça";
                        }else{
                            $msg_success = "Número de série atualizado com sucesso";
                            unset($numero_serie_peca);
                        }
                    }
                }
            }
        }

        if (isset($_POST["pesquisar"])){
            if (empty($serie) AND empty($peca_referencia) AND empty($peca_descricao)){
                $msg_erro["msg"][] = "Preencha os campos Ref. Peça/Descrição Peça ou Número de série para realizar a pesquisa";
                $msg_erro["campos"][] = "serie";
                $msg_erro["campos"][] = "peca";
            }

            if (!count($msg_erro["msg"])) {
                if (!empty($serie)){
                    $cond_serie = " AND tbl_numero_serie_peca.serie_peca = '{$serie}' ";
                }
                if (!empty($peca)){
                    $cond_peca = " AND tbl_numero_serie_peca.peca = {$peca} ";
                }

                $sql_pesquisa = " 
                    SELECT 
                        tbl_peca.peca,
                        tbl_peca.referencia,
                        tbl_peca.descricao,
                        tbl_numero_serie_peca.serie_peca,
                        tbl_numero_serie_peca.numero_serie_peca
                    FROM tbl_numero_serie_peca
                    JOIN tbl_peca ON tbl_peca.peca = tbl_numero_serie_peca.peca AND tbl_peca.fabrica = {$login_fabrica}
                    WHERE tbl_numero_serie_peca.fabrica = {$login_fabrica}
                    {$cond_serie}
                    {$cond_peca} ";
                $res_pesquisa = pg_query($con, $sql_pesquisa);
            }
        }
    }
}

if (isset($_GET["nserie_peca"]) AND strlen(trim($_GET["nserie_peca"])) > 0){
    $numero_serie_peca = $_GET["nserie_peca"];

    $sql = "SELECT 
            tbl_peca.peca,
            tbl_peca.referencia,
            tbl_peca.descricao,
            tbl_numero_serie_peca.serie_peca,
            tbl_numero_serie_peca.numero_serie_peca
        FROM tbl_numero_serie_peca
        JOIN tbl_peca ON tbl_peca.peca = tbl_numero_serie_peca.peca AND tbl_peca.fabrica = {$login_fabrica}
        WHERE tbl_numero_serie_peca.fabrica = {$login_fabrica}
        AND tbl_numero_serie_peca.numero_serie_peca = $numero_serie_peca";
    $res = pg_query($con, $sql);

    $peca_referencia    = pg_fetch_result($res, 0, "referencia");
    $peca_descricao     = pg_fetch_result($res, 0, "descricao");
    $serie              = pg_fetch_result($res, 0, "serie_peca");
    $numero_serie_peca  = pg_fetch_result($res, 0, "numero_serie_peca");
}

$layout_menu = "cadastro";
$title = "MANUTENÇÃO NÚMERO SÉRIE PEÇA";
include 'cabecalho_new.php';


$plugins = array(
    "autocomplete",
    "shadowbox",
    "dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $.autocompleteLoad(Array("peca"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
    });

    function retorna_peca(retorno){
        $("#peca_referencia").val(retorno.referencia);
        $("#peca_descricao").val(retorno.descricao);
    }

    function alterar (id){
        window.location='manutencao_numero_serie_peca_new.php?nserie_peca='+id;
    }

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
if (!empty($msg_success)){
    echo "<div class='alert alert-success'>
        <h4>$msg_success</h4>
    </div>";
}
?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios para cadastro </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
    <input type="hidden" name="numero_serie_peca" value="<?=$numero_serie_peca?>">
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='peca_referencia'>Ref. Peça</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='peca_descricao'>Descrição Peça</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("serie", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='serie'>Número de série</label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="serie" name="serie" class='span12' maxlength="20" value="<? echo $serie ?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span6'></div>
    </div>
    <br/>
    <div class="row-fluid" style="text-align: center;">
        <button class='btn' type="submit" name="gravar">Gravar</button>
        <button class='btn btn-info' type="submit" name="pesquisar">Pesquisar</button>
    </div>
</form>

<?php
if(isset($_POST["pesquisar"])){
    if (pg_num_rows($res_pesquisa) > 0) {
        echo "<br />";
        $count = pg_num_rows($res_pesquisa);
    ?>
        <table id="resultado" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class='titulo_coluna' >
                    <th>Ref. Peça</th>
                    <th>Descrição Peça</th>
                    <th>Número de série</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php
                for ($i = 0; $i < $count; $i++) {
                    $peca_referencia = pg_fetch_result($res_pesquisa, $i, 'referencia');
                    $peca_descricao = pg_fetch_result($res_pesquisa, $i, 'descricao');
                    $numero_serie_peca = pg_fetch_result($res_pesquisa, $i, 'numero_serie_peca');
                    $serie_peca        = pg_fetch_result($res_pesquisa, $i, 'serie_peca');
                ?>
                    <tr>
                        <td><?=$peca_referencia?></td>
                        <td><?=$peca_descricao?></td>
                        <td><?=$serie_peca?></td>
                        <td class='tac'><button class="btn btn-small btn-primary" onclick="alterar('<?=$numero_serie_peca?>')" >Alterar</button></td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>

        <?php
        if ($count > 50) {
        ?>
            <script>
                $.dataTableLoad({ table: "#resultado" });
            </script>
        <?php
        }
        ?>
    <?php
    }else{
        echo '
        <div class="container">
        <div class="alert">
                <h4>Nenhum resultado encontrado</h4>
        </div>
        </div>';
    }
}



include 'rodape.php';?>
