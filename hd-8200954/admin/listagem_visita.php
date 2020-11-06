<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

if (isset($_GET["posto"])) {
    $posto = $_GET["posto"];
}

if (isset($_GET["roteiro_posto"])) {
    $roteiro_posto = $_GET["roteiro_posto"];
}

function getLegendaTipoVisita($sigla) {
    $legenda = array("VT" => "Visita Técnica","VC" => "Visita Comercial","VA" => "Visita Administrativa","CM" => "Clínica Makita","FE" => "Feira/Evento","TN" => "Treinamento");
    return $legenda[$sigla];
}

function getLegendaTipoContato($sigla) {
    $aa = array("CL" => "Cliente","RV" => "Revenda","PA" => "Posto Autorizado");
    return $aa[$sigla];
}

function getLegendaStatus($sigla) {
    $aa =  array("AC" => "A Confirmar", "CF" => "Confirmado", "OK" => "Visita feita", "CC" => "Cancelado");
    return $aa[$sigla];
}

function getRoteiroList($posto, $roteiro_posto = null){
    global $con;
    global $login_fabrica;

    if($roteiro_posto != null){
        $where = "AND tbl_roteiro_posto.roteiro_posto = $roteiro_posto";
    }

    $sql = "SELECT  tbl_roteiro.roteiro,
                    tbl_roteiro.tipo_roteiro,
                    tbl_roteiro.ativo,
                    tbl_roteiro.data_inicio::date as data_inicio,
                    tbl_roteiro.data_termino::date as data_termino,
                    solicitante,
                    qtde_dias,
                    excecoes,
                    tbl_roteiro_posto.roteiro_posto,
                    tbl_roteiro_posto.qtde_horas,
                    tbl_roteiro_posto.tipo_de_visita,
                    tbl_roteiro_posto.tipo_de_local,
                    tbl_roteiro_posto.codigo,
                    tbl_roteiro_posto.status,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome as descricao_posto,
                    tbl_roteiro_tecnico.roteiro_tecnico,
                    tbl_tecnico.cpf,
                    tbl_roteiro_visita.checkin,
                    tbl_roteiro_visita.checkout,
                    tbl_roteiro_visita.tempo_visita,
                    tbl_roteiro_visita.descricao,
                    tbl_tecnico.nome as tecnico
            FROM tbl_roteiro
            JOIN tbl_roteiro_posto ON tbl_roteiro_posto.roteiro = tbl_roteiro.roteiro
            JOIN tbl_roteiro_tecnico ON tbl_roteiro_tecnico.roteiro = tbl_roteiro.roteiro
            JOIN tbl_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto 
                AND tbl_posto_fabrica.fabrica = $login_fabrica 
            JOIN tbl_roteiro_visita ON tbl_roteiro_visita.roteiro_posto = tbl_roteiro_posto.roteiro_posto
            JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_roteiro_tecnico.tecnico 
                AND tbl_tecnico.fabrica = tbl_roteiro.fabrica
        WHERE tbl_roteiro.fabrica        = $login_fabrica 
            AND tbl_posto_fabrica.posto = $posto
            $where
        ORDER BY tbl_roteiro.data_inicio";
    $res = pg_query($con, $sql);

    return pg_fetch_all($res);

}
$roteiros = getRoteiroList($posto, $roteiro_posto);

?>

<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<style>
    #btn-call-fileuploader, #div_anexos .titulo_tabela{display: none;}
    .tc_formulario {
        background-color: transparent;
        text-align: center;
    }
</style>
<?php 

$plugins = array(
    "shadowbox",
    "dataTable",
);

include("plugin_loader.php");
?>
<style type="text/css">
    .formulario_visita_tecnica {
        width: 100%;
    }

    .column {
        white-space: normal;
        width: 600px;
    }

    .col2 {
        white-space: normal;
        width: 20px;
    }

    .col3 {
        white-space: normal;
        width: 30px;
    }

    .col6 {
        white-space: normal;
        width: 60px;
    }

    .col7 {
        white-space: normal;
        width: 70px;
    }

    .col8 {
        white-space: normal;
        width: 80px;
    }

    .col10 {
        white-space: normal;
        width: 100px;
    }

    .col12 {
        white-space: normal;
        width: 125px;
    }

    .col14 {
        white-space: normal;
        width: 140px;
    }
</style>
<script language="javascript">
    $(function() {
        Shadowbox.init();
    });
</script>
    <br>
    <form name="fm_visita_tecnica" class="formulario_visita_tecnica">
        <table id="roteiros-list" class='table table-striped table-bordered table-hover ' >
            <thead>
                <tr class='titulo_coluna' >
                    <td colspan="100%" style="text-align: center;">DADOS DO ROTEIRO</td>
                </tr>

            </thead>
            <tbody>
            <?php
                if (empty($roteiros)) {
                    echo '<tr><td colspan="10" class="tac">Nenhum registro encontrado.</td></tr>';
                } else {
                    $primeira_linha = 0;

                    foreach ($roteiros as $item) {
                        if (!empty($item["roteiro_posto"])) {
                            $tempUniqueId = $item["roteiro_posto"];
                            $anexoNoHash = null;
                        } else if (strlen($_POST["anexo_chave"]) > 0) {
                            $tempUniqueId = $_POST["anexo_chave"];
                            $anexoNoHash = true;
                        } else {
                            $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
                            $anexoNoHash = true;
                        }

                        $sql_t = "SELECT posto,cidade FROM tbl_roteiro_posto WHERE roteiro = {$item['roteiro']}";
                        $res_t = pg_query($con,$sql_t);

                        $estadoTabela = array();
                        $cidadeTabela = array();

                        for ($t=0; $t < pg_num_rows($res_t) ; $t++) { 
                            $posto_t  = pg_fetch_result($res_t, $t, posto);
                            $cidade_t = pg_fetch_result($res_t, $t, cidade);

                            if (!empty($posto_t)) {
                                $sql_tp = "SELECT contato_estado,contato_cidade FROM tbl_posto_fabrica WHERE posto = {$posto_t} AND fabrica = {$login_fabrica}; ";
                                $res_tp = pg_query($con,$sql_tp);
                                $estadoPostoTabela = pg_fetch_result($res_tp, 0, contato_estado);
                                $cidadePostoTabela = pg_fetch_result($res_tp, 0, contato_cidade);

                                if(!in_array($cidadePostoTabela, $cidadeTabela)){
                                    $cidadeTabela[]=$cidadePostoTabela;
                                }
                                if (!in_array($estadoPostoTabela, $estadoTabela)) {
                                    $estadoTabela[]=$estadoPostoTabela;
                                }
                            }

                            if (!empty($cidade_t)) {
                                $sql_tp = "SELECT estado,nome FROM tbl_cidade WHERE cidade = {$cidade_t};";
                                $res_tp = pg_query($con,$sql_tp);
                                $estadoCidadeTabela = pg_fetch_result($res_tp, 0, estado);
                                $cidadeCidadeTabela = pg_fetch_result($res_tp, 0, nome);

                                if (!in_array($cidadeCidadeTabela, $cidadeTabela)) {
                                    $cidadeTabela[]=$cidadeCidadeTabela;
                                }

                                if (!in_array($estadoCidadeTabela, $estadoTabela)) {
                                    $estadoTabela[]=$estadoCidadeTabela;
                                }                           
                            }
                        }
                        
                        $estadoTabela = implode(" / ", $estadoTabela);
                        $cidadeTabela = implode(" / ", $cidadeTabela);

                        if ($item['ativo'] == 't') {
                            $ativoTabela = 'Ativo';
                        } else {
                            $ativoTabela = 'Inativo';
                        }

                        list($h1, $h2, $h3)                = explode(":", $item['qtde_horas']);
                        list($ano_ini, $mes_ini, $dia_ini) = explode("-", $item['data_inicio']);
                        list($ano_ter, $mes_ter, $dia_ter) = explode("-", $item['data_termino']);

                        $datacheckin  = new DateTime($item['checkin']);
                        $datacheckout = new DateTime($item['checkout']);

                        $data1       = $datacheckin->format('Y-m-d H:i:s');
                        $data2       = $datacheckout->format('Y-m-d H:i:s');
                        $data11      = $datacheckin->format('d/m/Y H:i:s');
                        $data22      = $datacheckout->format('d/m/Y H:i:s');
                        $diff        = $datacheckin->diff($datacheckout);
                        $tempovisita = $item['tempo_visita'];
                        $roteiroTipo = $item['tipo_roteiro'] == "RA" ? "Roteiro Administrativo" : "Roteiro Técnico";

                        if($primeira_linha > 0){
                            ?>
                            <tr>
                                <td colspan="100%"></td>
                            </tr>
                            <?php
                        } else {
                            $primeira_linha++;
                        }
                        ?>
                <tr>
                    <td colspan="100%" style="text-align: center; background-color: #ccc;"><b>VISITA TÉCNICA</b></td>
                </tr>
                <tr class='titulo_coluna' >
                    <th nowrap class="col2">Roteiro</th>
                    <th nowrap class="col12">Tipo Roteiro</th>
                    <th nowrap class="col14">Data Início</th>
                    <th nowrap class="col8">Data Término</th>
                    <th nowrap colspan="3">Responsável pelo roteiro</th>
                </tr>
                <tr>
                    <td class="tac col2" nowrap><?php echo $item['roteiro'];?></td>
                    <td class="tac col12" nowrap><?php echo $roteiroTipo ?></td>
                    <td class="tac col14" nowrap><?php echo $dia_ini."/".$mes_ini."/".$ano_ini; ?></td>
                    <td class="tac col8" nowrap><?php echo $dia_ter."/".$mes_ter."/".$ano_ter;?></td>
                    <td class="tal" nowrap colspan="3"><?php echo $item['tecnico'] ?></td>
                </tr>
                <tr class="titulo_coluna">
                    <th nowrap class="col2">Estado</th>
                    <th nowrap class="col12">Cidade</th>
                    <th nowrap class="col14">Solicitante</th>
                    <th nowrap class="col8">Tipo Visita</th>
                    <th nowrap class="col7">Qtde Horas</th>
                    <th nowrap class="col6">Qtde Dias</th>
                    <th nowrap class="col8">Status</th>
                </tr>
                <tr>
                    <td class="tac col2" nowrap><?php echo $estadoTabela ?></td>
                    <td nowrap class="col10" ><?php echo $cidadeTabela ?></td>
                    <td nowrap class="col12" ><?php echo $item['solicitante'] ?></td>
                    <td class="tac col8" nowrap><?php echo getLegendaTipoVisita($item['tipo_de_visita']);?></td>
                    <td class="tac col7" nowrap><?php echo $h1.":".$h2;?></td>
                    <td class="tac col6" nowrap><?php echo $item['qtde_dias'] ?></td>
                    <td class="tac col8" nowrap><?php echo getLegendaStatus($item['status']); ?></td>
                </tr> 
                 <tr class="titulo_coluna">
                    <th nowrap colspan="100%" style="text-align: center;">DADOS DA VISITA</th>
                </tr>
                 <tr >
                    <td colspan="2" class='titulo_coluna2'>Checkin</td>
                    <td colspan="10"  class="tal column" nowrap><?php echo $data11 ?></td>
                </tr>
                 <tr>
                    <td colspan="2"  class='titulo_coluna2'>Checkout</td>
                    <td colspan="10"  class="tal column" nowrap><?php echo $data22 ?></td>
                </tr>
                 <tr>
                    <td  colspan="2" class='titulo_coluna2'>Tempo da Visita</td>
                    <td colspan="10" class="tal column" nowrap><?php echo $tempovisita ?></td>
                </tr>
                 <tr >
                    <td colspan="2"  class='titulo_coluna2'>Fotos</td>
                    <td colspan="10" class="tac column" nowrap>
                     <?php
                        $boxUploader = array(
                            "div_id"    => "div_anexos",
                            "prepend"   => $anexo_prepend,
                            "context"   => "roteiro",
                            "unique_id" => $tempUniqueId,
                            "hash_temp" => $anexoNoHash,
                            "bootstrap" => true
                        );
                        include "../box_uploader.php";
                    ?>
                    </td>
                </tr>
                 <tr>
                    <td colspan="2"  class='titulo_coluna2'>Descrição</td>
                    <td colspan="10" class="tal column" nowrap><?php echo $item["descricao"] ?></td>
                </tr>        
             <?php }} ?>
        </tbody>
    </table>
</form>