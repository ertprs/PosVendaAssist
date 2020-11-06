<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include_once dirname(__FILE__) . '/../class/AuditorLog.php';

include 'funcoes.php';

if ($_POST["btn_acao"] == "excluir") {

    $diagnostico = $_POST["diagnostico"];
    $sql_Auditor = "SELECT * FROM tbl_diagnostico WHERE tbl_diagnostico.diagnostico = $diagnostico";

    unset($AuditorLog);
    $AuditorLog = new AuditorLog;
    $AuditorLog->retornaDadosSelect($sql_Auditor);

    /* inicio exclusao de integridade */
    $sql = pg_query($con, 'DELETE FROM tbl_diagnostico WHERE diagnostico =' . $diagnostico );
    $AuditorLog->retornaDadosSelect($sql_Auditor)->EnviarLog('delete', 'tbl_diagnostico',"$login_fabrica");
    
    if (!pg_last_error()) {
        echo json_encode(array("retorno" => utf8_encode("success"),"diagnostico" => utf8_encode($diagnostico)));
    } else {
        echo json_encode(array("retorno" => utf8_encode("Erro ao deletar registro.")));
    }
    exit;
}

if ($_POST["btn_acao"] == "submit") {
    $linhas             = $_POST['linhas'];
    $defeito_constatado = $_POST['defeito_constatado'];
	$linha = $_POST['linha'];
	$defeito = $_POST['defeito'];
    if (empty($linha) || empty($defeito)){
        $msg_erro["msg"][] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "linhas";
        $msg_erro["campos"][] = "defeito_constatado";
    }
    
    if (!count($msg_erro["msg"])) {
        pg_query($con,"BEGIN TRANSACTION");

        foreach ($linha as $key => $value ) {
			unset($diagnostico_id);

            $sql = "SELECT *
                FROM tbl_diagnostico
                WHERE tbl_diagnostico.linha = $value
                AND tbl_diagnostico.defeito_constatado = $defeito[$key]
                AND tbl_diagnostico.defeito_reclamado isnull
                AND fabrica = $login_fabrica";
            $res = pg_query($con, $sql);
            $sql_Auditor = $res;
            if(pg_numrows($res) == 0){
                $sql = "INSERT INTO tbl_diagnostico (fabrica, linha, defeito_constatado, admin)
						VALUES($login_fabrica, $value, $defeito[$key],$login_admin) RETURNING diagnostico;";

                $query = pg_query($con,$sql);
                
                if (strlen (pg_last_error($con)) > 0) {
                    $msg_erro["msg"][] = 'Erro ao cadastrar';
                    break;
                }else{
                    $array_id_inserido[] = pg_fetch_result($query, 0, diagnostico);
                }
            }
        }
        
        if(!count($msg_erro["msg"])) {
            $msg_success = 'Gravado com sucesso';
            pg_query($con, "COMMIT TRANSACTION");
            if (count($array_id_inserido)) {
                unset($AuditorLog);
                $AuditorLog = new AuditorLog('insert');
                $AuditorLog->retornaDadosSelect('SELECT *
                    FROM tbl_diagnostico
                    WHERE tbl_diagnostico.diagnostico IN('.implode(',', $array_id_inserido).')')->EnviarLog('insert', 'tbl_diagnostico',"$login_fabrica");
            }
            unset($linhas);
            unset($defeito_constatado);
        }else{
            pg_query($con, "ROLLBACK TRANSACTION");
        }
    }
}

$layout_menu = "cadastro";
$title = "INTEGRAÇÃO LINHA - DEFEITO CONSTATADO";
include 'cabecalho_new.php';


$plugins = array(
    "select2",
    "dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $("#linhas").select2();
    });

    $(document).on('click','button.excluir', function(){
        if (confirm('Deseja excluir o registro ?')) {
            var btn = $(this);
            var text = $(this).text();
            var diagnostico = $(btn).data('diagnostico');
            
            var obj_datatable = $("#result_integridades").dataTable()

            $(btn).prop({disabled: true}).text("Excluindo...");
            $.ajax({
                method: "POST",
                url: "<?=$_SERVER['PHP_SELF']?>",
                data: { btn_acao: 'excluir', diagnostico: diagnostico},
                timeout: 8000
            }).fail(function(){
                alert("Não foi possível excluir o registro, tempo limite esgotado!");
                $(btn).prop({disabled: false}).text("Excluir");
            }).done(function(data) {
                data = JSON.parse(data);
                if (data.retorno == "success") {
                    $(btn).text("Excluido");
                    setTimeout(function(){
                        $(obj_datatable.fnGetData()).each(function(idx,elem){
                            if($(elem[2]).data('diagnostico') == diagnostico){
                                obj_datatable.fnDeleteRow(idx);
                                return;
                            }
                        });
                    }, 1000);
                }else{
                    $(btn).prop({disabled: false}).text(text);
                }
            });
        }else{
            return false;
        }
    });
    
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}else if (!empty($msg_success)){
?>
    <div class="alert alert-success">
        <h4><?=$msg_success?></h4>
    </div>
<?php
}
?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form METHOD='POST' class='frm_relatorio form-search form-inline tc_formulario' ACTION='<?=$PHP_SELF?>'>
    <div class='titulo_tabela '>Cadastro</div>
    <br/>
       
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("linhas", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='linhas'>Linha</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <h5 class='asteristico'>*</h5>
                        <select name="linhas[]" id="linhas" class="span12" multiple="multiple">
                            <option value=""></option>
                            <?php
                            $sql = "SELECT linha, nome
                                    FROM tbl_linha
                                    WHERE fabrica = $login_fabrica
                                    AND ativo";
                            $res = pg_query($con,$sql);

                            foreach (pg_fetch_all($res) as $key) {
                                $selected_linhas = ( isset($linhas) and ($linhas == $key['linha']) ) ? "SELECTED" : '' ;

                            ?>
                                <option value="<?php echo $key['linha']?>" <?php echo $selected_linhas ?> >

                                    <?php echo $key['nome']?>

                                </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("defeito_constatado", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='defeito_constatado'>Defeito Constatado</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <h5 class='asteristico'>*</h5>
                        <select name="defeito_constatado" id="defeito_constatado" class="span12">
                            <option value=""></option>
                            <?php
                                $sql ="SELECT defeito_constatado, descricao, codigo from tbl_defeito_constatado where fabrica=$login_fabrica and ativo='t' order by descricao;";
                                $res = pg_query($con,$sql);
                                foreach (pg_fetch_all($res) as $key) {

                                    $selected_defeito_constatado = ( isset($defeito_constatado) and ($defeito_constatado == $key['defeito_constatado']) ) ? "SELECTED" : '' ;

                                ?>
                                    <option value="<?php echo $key['defeito_constatado']?>" <?php echo $selected_defeito_constatado ?> >
                                        <?php echo $key['descricao']?>
                                    </option>
                                <?php
                                }
                            ?>
                        </select>
                    </div>
                    <div class='span2'></div>
                </div>
            </div>
        </div>
    </div>

    <div class='row-fluid'>
        <p align="center">
            <button type="button" class="btn" onclick="addDefeito()">Adicionar</button>
        </p>
    </div>

    <div id="tabela" style="display: none;">
        <br />
        <table id="integracao" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class="titulo_coluna">
                    <th>Linhas</th>
                    <th>Defeito Constatado</th>
                    <th>Ações</th>
                </tr>
            </thead>
        </table>
        <p align="center">
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
            <button class='btn' id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));">Gravar</button>
        </p>
    </div>
</form>
<?php
    $int_cadastrados = "
            SELECT
                tbl_diagnostico.diagnostico,
                tbl_diagnostico.mao_de_obra,
                tbl_diagnostico.tempo_estimado,
                tbl_diagnostico.garantia,
                tbl_defeito_constatado.descricao as defeito_descricao,
                tbl_defeito_constatado.codigo as defeito_codigo,
                tbl_linha.nome as linha_nome,
                tbl_linha.codigo_linha as codigo_linha
            FROM tbl_diagnostico
            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
            JOIN tbl_linha ON tbl_linha.linha = tbl_diagnostico.linha and tbl_linha.fabrica = {$login_fabrica}
            WHERE tbl_diagnostico.fabrica = {$login_fabrica}
            ORDER BY tbl_linha.nome, tbl_defeito_constatado.descricao";
    $query    = pg_query($con,$int_cadastrados);
    $num_rows = pg_num_rows($query);
    if ($num_rows > 0) {
?>
        <table id="result_integridades" class='table table-striped table-bordered table-hover table-fixed'>
                <thead>
                    <tr class='titulo_tabela'>
                        <th colspan="3">Defeitos Cadastrados</th>
                    </tr>
                    <tr class='titulo_coluna'>
                        <th>Linha</th>
                        <th>Defeito Constatado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        for ($i=0; $i < $num_rows; $i++) {
                            $linha          = trim(pg_fetch_result($query,$i,"linha_nome"));
                            $defeito        = trim(pg_fetch_result($query,$i,"defeito_descricao"));
                            $id             = trim(pg_fetch_result($query,$i,"diagnostico"));
                            $codigo_linha   = trim(pg_fetch_result($query,$i,"codigo_linha"));
                            $defeito_codigo = trim(pg_fetch_result($query,$i,"defeito_codigo"));
                            ?>
                            <tr>
                    <?php
                                echo "<td>$linha</td>";
                                echo "<td style='text-align: left;'>$defeito</td>";
                                echo "<td style='text-align: center;'>";
                                echo "<button class='btn btn-danger btn-small excluir' data-diagnostico='$id' >Remover</button> ";
                                echo "</td>";
                    ?>
                            </tr>
                    <?php
                        }
                    ?>
                </tbody>
            </table>

        <script type="text/javascript">
            $.dataTableLoad({ table: "#result_integridades" });

            function addDefeito() {
                var defeito = $('#defeito_constatado').val();
                var linhas = $("#linhas").val();
     
                var txt_defeito = $('#defeito_constatado').find('option').filter(':selected').text();
                var txt_linha = $('#linhas').find('option').filter(':selected').text();

                var html_input = '<tr id="'+i+'"><td><input type="hidden" value="' + linhas + '" name="linha['+i+']"  />' + txt_linha + '</td><td><input type="hidden" value="' + defeito + '" name="defeito['+i+']"  />' + txt_defeito+'</td> <td> <button class="btn btn-danger" onclick="deletaitem('+i+')">Remover</button></td></tr>';

                if (linhas  === '' || defeito  === '') {
                    $('#msg_alerta').find('h4').text('Preencha os campos obrigatórios');
                    $("#msg_alerta").fadeIn();
                    setTimeout(function(){
                        $('#msg_alerta').fadeOut();
                    }, 3000);

                    if (linha  === ''){
                        $('#cg_linha').addClass('error');
                    }
                    if (defeito  === ''){
                        $('#cg_defeito').addClass('error');
                    }
                    return false;
                } else {
                    console.log()
                    i++;
                    $("#tabela").css("display","block");
                    $(html_input).appendTo("#integracao");
                }
            }   

            function deletaitem(id) {
                $("#"+id).remove();

                var table = $('#integracao');

                var contador = 0;
                table.find('tr').each(function(indice){
                    contador += 1;
                });
                if (contador <= 1) { $('#tabela').hide();}
            }

        </script>
    <br />
    <p align="center">
        <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_diagnostico&id=<?php echo $login_fabrica; ?>' target="_blank" name="btnAuditorLog">Visualizar Log Auditor</a>
    </p>
    <br />
<?php }

include 'rodape.php';?>
