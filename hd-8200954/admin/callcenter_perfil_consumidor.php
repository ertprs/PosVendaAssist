<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO DE PERFIL DO CONSUMIDOR";

if(isset($_POST["btn_acao"])){

    if(strlen($data_inicial) > 0 && $data_inicial != "dd/mm/aaaa"){
        $xdata_inicial = fnc_formata_data_pg(trim($data_inicial));
        $xdata_inicial = str_replace("'", "", $xdata_inicial);
    }else{
        $msg_erro["msg"][]    = "Data Inicial Inválida";
        $msg_erro["campos"][] = "data_inicial";
    }

    if(strlen($data_final) > 0 && $data_final != "dd/mm/aaaa"){
        $xdata_final = fnc_formata_data_pg(trim($data_final));
        $xdata_final = str_replace("'", "", $xdata_final);
    }else{
        $msg_erro["msg"][]    ="Data Final Inválida";
        $msg_erro["campos"][] = "data_final";
    }

    if(count($msg_erro) == 0){

        $dat = explode("/", $data_inicial); //tira a barra

        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];

        if(!checkdate($m,$d,$y)) {
            $msg_erro["msg"][]    = "Data Inicial Inválida";
            $msg_erro["campos"][] = "data_inicial";
        }

    }

    if(count($msg_erro) == 0){

        $dat = explode("/", $data_final); //tira a barra

        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];

        if(!checkdate($m,$d,$y)) {
            $msg_erro["msg"][]    = "Data Final Inválida";
            $msg_erro["campos"][] = "data_final";
        }

    }

    if(count($msg_erro) == 0){

        list($dia, $mes, $ano) = explode("/", $data_inicial);
        $xdata_inicial = $ano."-".$mes."-".$dia;

        list($dia, $mes, $ano) = explode("/", $data_final);
        $xdata_final = $ano."-".$mes."-".$dia;

        $cond_data = " AND tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' ";

        if(strlen($_POST["estado"]) > 0){
            $estado = $_POST["estado"];
            $cond_estado = " AND tbl_hd_chamado_extra.cidade IN (SELECT tbl_cidade.cidade FROM tbl_cidade WHERE tbl_cidade.estado = '{$estado}') ";
        }

        $sql = "SELECT 
                        tbl_hd_chamado.hd_chamado, 
                        tbl_admin.nome_completo AS atendente,
                        tbl_produto.referencia AS referencia_produto,
                        tbl_produto.descricao AS descricao_produto,
                        tbl_hd_chamado_extra.data_nf AS data_compra,
                        tbl_hd_chamado_extra.consumidor_revenda AS tipo_consumidor,
                        tbl_cidade.nome AS cidade,
                        tbl_cidade.estado,
                        tbl_hd_chamado_extra.nome,
                        tbl_hd_chamado_extra.email,
                        tbl_hd_chamado_extra.fone,
                        tbl_hd_chamado_extra.celular,
                        tbl_hd_chamado_extra.array_campos_adicionais AS campos_adicionais 
                FROM tbl_hd_chamado 
                INNER JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado 
                INNER JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente 
                LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto 
                LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_hd_chamado_extra.cidade 
                WHERE 
                    tbl_hd_chamado.fabrica = {$login_fabrica} 
                    {$cond_data}
                    {$cond_estado} ";
        $result = pg_query($con, $sql);

        $sql_num = pg_num_rows($result);

    }

}

include "cabecalho_new.php";

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
    $.dataTableLoad();

});

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
    <strong class="obrigatorio pull-right">  * Campos obrigatórios </strong>
</div>

<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>

    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span2'>
            <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'>Data Inicial</label>
                <div class='controls controls-row'>
                    <div class='span11'>
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
                    <div class='span11'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='data_final'>Estados</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <select id="estado" name="estado" class="span12" >
                            <option value="" >Selecione</option>
                            <?php
                            #O $array_estados() está no arquivo funcoes.php
                            foreach ($array_estados() as $sigla => $nome_estado) {
                                $selected = ($sigla == $estado) ? "selected" : "";
                                echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <br />

    <button class='btn' id="btn_acao">Pesquisar</button>
    <input type='hidden' id="btn_click" name='btn_acao' value='sim' />

    <br /> <br />

</form>

<?php

if(isset($result)){

    if($sql_num > 0){

        $data     = date("d-m-Y-H:i");
        $fileName = "csv_perfil_consumidor_{$data}.csv";
        $file     = fopen("/tmp/{$fileName}", "w");

        $cabecalho = array(
            "Protocolo",
            "Tipo Consumidor",
            "Nome",
            "Email",
            "Sexo",
            "Telefone",
            "Celular",
            "Cidade",
            "Estado",
            utf8_encode("Faxa Etária"),
            "Atendente",
            "Tentativas de Contato",
            "Data da Compra",
            "Ref. Produto",
            "Desc. Produto"
        );

        ?>

        </div>

        <br />

        <div style="margin: 5px !important;">

            <table class="table table-bordered table-striped">
                <thead>
                    <tr class="titulo_coluna">
                        <th>Protocolo</th>
                        <th>Tipo Consumidor</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Sexo</th>
                        <th>Telefone</th>
                        <th>Celular</th>
                        <th>Cidade</th>
                        <th>Estado</th>
                        <th>Faxa Etária</th>
                        <th>Atendente</th>
                        <th>Tentativas de Contato</th>
                        <th>Data da Compra</th>
                        <th>Ref. Produto</th>
                        <th>Desc. Produto</th>
                    </tr>
                </thead>
                <tbody>

                <?php

                for ($i = 0; $i < $sql_num; $i++) { 

                    $genero             = "";
                    $tentativas_contato = "";
                    $faixa_etaria       = "";

                    $campos_adicionais = pg_fetch_result($result, $i, "campos_adicionais");
                    extract(json_decode($campos_adicionais, true));
                    /* voltagem | tentativas_contato | genero | faixa_etaria */
                    
                    $protocolo          = pg_fetch_result($result, $i, "hd_chamado");
                    $tipo_consumidor    = pg_fetch_result($result, $i, "tipo_consumidor");
                    $nome               = pg_fetch_result($result, $i, "nome");
                    $email              = pg_fetch_result($result, $i, "email");
                    $sexo               = (strlen($genero) == 0) ? "" : $genero;
                    $telefone           = pg_fetch_result($result, $i, "fone");
                    $celular            = pg_fetch_result($result, $i, "celular");
                    $cidade             = pg_fetch_result($result, $i, "cidade");
                    $estado             = pg_fetch_result($result, $i, "estado");
                    $faixa_etaria       = (strlen($faixa_etaria) == 0) ? "" : $faixa_etaria." anos";
                    $atendente          = pg_fetch_result($result, $i, "atendente");
                    $tentativas_contato = (strlen($tentativas_contato) == 0) ? "" : $tentativas_contato;
                    $data_compra        = pg_fetch_result($result, $i, "data_compra");
                    $ref_produto        = pg_fetch_result($result, $i, "referencia_produto");
                    $desc_produto       = pg_fetch_result($result, $i, "descricao_produto");

                    $tipo_consumidor = ($tipo_consumidor == "C") ? "Consumidor" : "Revenda";

                    if(strlen($data_compra) > 0){

                        list($ano, $mes, $dia) = explode("-", $data_compra);
                        $data_compra = $dia."/".$mes."/".$ano;

                    }

                    echo "
                    <tr>
                        <td> <a href='callcenter_interativo_new.php?callcenter={$protocolo}' target='_blank'> {$protocolo} </a> </td>
                        <td> {$tipo_consumidor} </td>
                        <td> {$nome} </td>
                        <td> {$email} </td>
                        <td class='tac'> {$sexo} </td>
                        <td> {$telefone} </td>
                        <td> {$celular} </td>
                        <td> {$cidade} </td>
                        <td class='tac'> {$estado} </td>
                        <td nowrap> {$faixa_etaria} </td>
                        <td> {$atendente} </td>
                        <td class='tac'> {$tentativas_contato} </td>
                        <td class='tac'> {$data_compra} </td>
                        <td> {$ref_produto} </td>
                        <td> {$desc_produto} </td>
                    </tr>
                    ";

                    $dados = array(
                        $protocolo,
                        $tipo_consumidor,
                        utf8_encode($nome),
                        $email,
                        $sexo,
                        $telefone,
                        $celular,
                        $cidade,
                        $estado,
                        $faixa_etaria,
                        utf8_encode($atendente),
                        $tentativas_contato,
                        $data_compra,
                        utf8_encode($ref_produto),
                        utf8_encode($desc_produto)
                    );

                    $linha .= implode(';',$dados)."\r\n";

                }

                $arquivo = implode(';',$cabecalho)."\r\n".$linha;

                fwrite($file, $arquivo);
                fclose($file);

                if (file_exists("/tmp/{$fileName}")) {
                    system("mv /tmp/{$fileName} xls/{$fileName}");
                }

                ?>
                    
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="15" class="tac">
                            <a class="btn btn-success" href="xls/<?=$fileName?>" role="button">Gerar Arquivo CSV</a>
                        </td>
                    </tr>
                </tfoot>
            </table>

        </div>

        <div class="container">

        <?php

    }else{

        echo "
            <div class='alert'>
                <h4>Nenhum resultado encontrado</h4>
            </div>
        ";

    }
}

?>

<br /> <br />

<?php include "rodape.php"; ?>
