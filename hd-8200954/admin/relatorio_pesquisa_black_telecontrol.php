<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';
include 'funcoes.php';
if ($_serverEnvironment == "production") {

    $pesquisa  = 117;
    $arr_tipos = array(605, 606, 600, 601, 602, 603, 604);

} else {

    $pesquisa  = 120;
    $arr_tipos = array(632, 633, 634, 635, 636, 637, 638);

}

$btn_acao     = $_POST['acao'];
$data_inicial = $_POST['data_inicial'];
$data_final   = $_POST['data_final'];
$msg_erro     = array();
$msgError     = "Preencha os campos obrigatórios.";

if (strlen($btn_acao) > 0) {

    if(count($msg_erro["msg"]) == 0) {
        $dat = explode ("/", $data_inicial );
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if (!checkdate($m,$d,$y)) {
            $msg_erro["msg"][]    = $msgError;
            $msg_erro["campos"][] = "data_inicial";
        }
    }

    if (count($msg_erro["msg"]) == 0) {
        $dat = explode ("/", $data_final );
        $d2  = $dat[0];
        $m2  = $dat[1];
        $y2  = $dat[2];
        if(!checkdate($m2,$d2,$y2)) {
            $msg_erro["msg"][]    = $msgError;
            $msg_erro["campos"][] = "data_final";
        }
    }

    if (count($msg_erro["msg"]) == 0) {

        $data      = date("d-m-Y-H:i");
        $fileName  = "relatorio_pesquisa_black_telecontrol_{$data}.xls";
        $file      = fopen("/tmp/{$fileName}", "w");

        $xdata_inicial =   $y . '-' . $m . '-' . $d . ' 00:00:00';
        $xdata_final   =   $y2 . '-' . $m2 . '-' . $d2 . ' 23:59:59';


        $sql = "    SELECT 
                                r.posto,
                                tbl_posto.nome AS nomeposto,
                                r.pesquisa,
                                tbl_pesquisa.descricao AS nome_pesquisa,
                                to_char (r.data_input,'DD/MM/YYYY') AS data_input
                          FROM tbl_resposta r
                          JOIN tbl_pesquisa USING (pesquisa)
                          JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = r.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                          JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                         WHERE r.pesquisa = $pesquisa 
                           AND r.data_input BETWEEN '".$xdata_inicial."' AND '".$xdata_final."'
                      GROUP BY r.posto, nomeposto, r.pesquisa, nome_pesquisa, r.data_input";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $i = 1;
            while($rows = pg_fetch_assoc($res)) {

                $xRespostas .= "<tr>";
                $xRespostas .= "<td nowrap>".$rows["nomeposto"]."</td>";
                $xRespostas .= "<td nowrap>".$rows["data_input"]."</td>";
                $xRespostas .= "<td nowrap>".$rows["nome_pesquisa"]."</td>";

                    $sqlPer ="SELECT tbl_pergunta.descricao, tbl_pergunta.pergunta
                                FROM tbl_pesquisa_pergunta
                                JOIN tbl_pergunta USING(pergunta)
                               WHERE tbl_pesquisa_pergunta.pesquisa = " . $rows["pesquisa"] . " 
                               ORDER BY tbl_pesquisa_pergunta.ordem";
                    $resPer = pg_query($con, $sqlPer);
                    $xperguntas = pg_fetch_all($resPer);
                    $totalPergunta = count($xperguntas);
                    foreach ($xperguntas as $k => $rowsPer) {

                        $sqlTipo = "SELECT DISTINCT tbl_tipo_resposta_item.tipo_resposta_item, tbl_tipo_resposta_item.descricao, tbl_pergunta.pergunta
                                               FROM tbl_tipo_resposta
                                               JOIN tbl_tipo_resposta_item ON tbl_tipo_resposta.tipo_resposta=tbl_tipo_resposta_item.tipo_resposta
                                               JOIN tbl_pergunta ON tbl_tipo_resposta.tipo_resposta=tbl_pergunta.tipo_resposta
                                               JOIN tbl_pesquisa_pergunta USING(pergunta)
                                              WHERE tbl_pesquisa_pergunta.pesquisa  = ". $rows["pesquisa"]." 
                                                AND tbl_tipo_resposta_item.tipo_resposta_item in(".implode(',', $arr_tipos).") order by tbl_pergunta.pergunta";
                        $resTipo = pg_query($con, $sqlTipo);
                        $xtipo_resposta = pg_fetch_all($resTipo);

                        $colspan = '';
                        $sub_perguntas = '';
                     

                        if ($k == 5) {

                            $sub_perguntas .= "<table border='0'><tr style='background: #2b2c50;color:#ffffff;'>";

                            foreach ($xtipo_resposta as $g => $sub) {
                                $sub_perguntas .= "<td style='padding: 5px 10px ;' nowrap>".$sub["descricao"]."</td>";
                            }

                            $sub_perguntas .= "</tr></table>";

                            $colspan = "colspan='7'";
                        } 
                        if ($i == 1) {

                            $xPerguntas .= "<th $colspan nowrap> " . $rowsPer["descricao"] . " $sub_perguntas </th>";
                        }

                        $sqlRes = "SELECT tbl_resposta.tipo_resposta_item, tbl_resposta.posto, tbl_resposta.txt_resposta, tbl_resposta.observacao 
                                     FROM tbl_resposta
                                    WHERE tbl_resposta.pesquisa = ".$rows['pesquisa']." 
                                      AND tbl_resposta.pergunta = ".$rowsPer["pergunta"]." 
                                      AND tbl_resposta.posto = ".$rows['posto']." 
                                      ORDER BY tbl_resposta.tipo_resposta_item";
                        $resResp = pg_query($con, $sqlRes);
                        if (pg_num_rows($resResp) == 0) {
                            if ($k == 5) {
                                $xRespostas .= "<td colspan='7'>  </td>";
                            } else {
                                $xRespostas .= "<td nowrap>  </td>";
                            }
                        } else {
                            while ($rowsR  =  pg_fetch_assoc($resResp)) {
                                $obs = "";
                                if (!empty($rowsR["observacao"])) {
                                    $obs = " - ".$rowsR["observacao"];
                                }
                                $xRespostas .= "<td nowrap> " . $rowsR["txt_resposta"] . " {$obs} </td>";
                            }
                        }

                }

                        $xRespostas .= "</tr>";

                            
                $i++;
            }
        }

        $conteudo =  "
            <table border='1'>
                <thead>
                        <tr style='background: #2b2c50;color:#ffffff;'>
                            <th nowrap> POSTO</th>
                            <th nowrap> DATA RESPOSTA</th>
                            <th nowrap> PESQUISA</th>
                            {$xPerguntas}
                        </tr>
                </thead>
                <tbody>
                {$xRespostas}
                </thead>
            </table>
            ";

        fwrite($file, $conteudo);
        fclose($file);

        $downloadExcel = "";

        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");
            $downloadExcel = "xls/{$fileName}";
        } else {
            $msg_erro = "Erro ao gerar excel.";
        }
    }
}

$layout_menu = "gerencia";
$title       = "PESQUISA BLACK & TELECONTROL";
include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
    );

include("plugin_loader.php");
?>

<script type="text/javascript" charset="utf-8">
    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        Shadowbox.init();

    });
</script>

<?php if (strlen($msg_erro) > 0) { ?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro; ?></h4> </div>
<?php } ?>
<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
<form class="form-search form-inline tc_formulario" name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">
    <div class="titulo_tabela">Parâmetros de Pesquisa</div>
    <br />
    <div class="container tc_container">
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span2'>
                <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_final'>Data Final</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span1'>
                <label class='control-label'>&nbsp;&nbsp;&nbsp; </label>
                <input type="button" class='btn' value="Pesquisar" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" alt="Preencha as opções e clique aqui para pesquisar">
                <input type="hidden" name="acao">
            </div>
            <div class='span2'></div>
        </div>
    </div>
    <br />
</form>
</div>
<?php if (strlen($downloadExcel) > 0) {?>
<br />
<div class="row tac">
    <a href='<?php echo $downloadExcel;?>' id='gerar_excel' class="btn_excel">
        <span><img src='imagens/excel.png' /></span>
        <span class="txt">Baixar Arquivo Excel</span>
    </a>
</div>
<?php }?>