<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
$layout_menu = "cadastro";

include 'autentica_admin.php';

$title = "Relatório de Posto x Uploads de Contratos";

include "cabecalho_new.php";

$plugins = array('datepicker', 'dataTable');

include 'plugin_loader.php';

?>

    <script>
        $(function(){

            $.datepickerLoad(Array("data_final", "data_inicial"));

        });
    </script>

    <form name="frm_relatorio" METHOD="POST" ACTION="<?php echo $PHP_SELF; ?>" align='center' class='form-search form-inline tc_formulario'>
        
        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
        <br/>

        <div class='row-fluid'>
            <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='data_inicial'>Data Inicial</label>
                        <div class='controls controls-row'>
                            <div class='span10'>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                            </div>
                        </div>
                    </div>
                </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_final'>Data Final</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

        <p>
            <br />
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p>

        <br />

    </form>

    <?php

    $cond_data_inicial = "";
    $cond_data_final   = "";

    if(strlen($_POST["data_inicial"]) > 0){

        list($dia, $mes, $ano) = explode("/", $_POST["data_inicial"]);
        $data_inicial = $ano."-".$mes."-".$dia;

        $cond_data_inicial = " AND tbl_tdocs.data_input >= '{$data_inicial} 00:00' ";

    }   

    if(strlen($_POST["data_final"]) > 0){

        list($dia, $mes, $ano) = explode("/", $_POST["data_final"]);
        $data_final = $ano."-".$mes."-".$dia;

        $cond_data_final = " AND tbl_tdocs.data_input <= '{$data_final} 23:59' ";

    }

    $sql_uploads = "SELECT 
                        count(tbl_tdocs.tdocs) AS uploads, 
                        tbl_posto.nome AS nome_posto,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_tdocs.referencia_id AS posto_tdocs  
                    FROM tbl_tdocs 
                    INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_tdocs.referencia_id AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                    INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto  
                    WHERE 
                        tbl_tdocs.fabrica = {$login_fabrica} 
                        AND tbl_tdocs.contexto = 'posto' 
                        AND tbl_tdocs.referencia = 'contrato' 
                        AND tbl_tdocs.situacao = 'ativo' 
                        {$cond_data_inicial}
                        {$cond_data_final}
                    GROUP BY posto_tdocs, nome_posto, codigo_posto ";
    $res_uploads = pg_query($con, $sql_uploads);

    $rows = pg_num_rows($res_uploads);

    ?>

    <?php if($rows > 0){ ?>

        <table id="relatorio" class='table table-striped table-bordered table-hover table-fixed'>
            <thead>
                <tr class="titulo_tabela">
                    <th colspan='3'> Relatório de Posto Autorizado x Uploads de Contratos </th>
                </tr>
                <tr class="titulo_coluna">
                    <th >Código</th>
                    <th >Nome</th>
                    <th >Qtde Uploads</th>
                </tr>
            </thead>

            <?php

            $total_uploads = 0;

            echo "<tbody>";

            for($i = 0; $i < $rows; $i++){

                $qtde_uploads = pg_fetch_result($res_uploads, $i, "uploads");
                $nome_posto   = pg_fetch_result($res_uploads, $i, "nome_posto");
                $codigo_posto = pg_fetch_result($res_uploads, $i, "codigo_posto");
                $posto        = pg_fetch_result($res_uploads, $i, "posto_tdocs");

                echo "
                    <tr>
                        <td class='tac'>{$codigo_posto}</td>
                        <td><a href='posto_cadastro.php?posto={$posto}' target='_blank'>{$nome_posto}</a></td>
                        <td class='tac'>{$qtde_uploads}</td>
                    </tr>
                ";

                $total_uploads += $qtde_uploads;

            }

            echo "</tbody>";

            echo "<tfoot>";
                echo "
                    <tr>
                        <td colspan='2' style='text-align: right;'>
                            <strong>Total de Uploads</strong>
                        </td>
                        <td class='tac'>
                            <strong>{$total_uploads}</strong>
                        </td> 
                    </tr>
                ";
            echo "</tfoot>";

            ?>

        </table>

        <script>
            $.dataTableLoad({
                table: "#relatorio"
            });
        </script>

    <?php }else{ ?>

        <div class="alert alert-warning">
            <h4>Nenhum posto realizou o upload de contratos</h4>
        </div>

    <?php } ?>

<?php include "rodape.php"; ?>
