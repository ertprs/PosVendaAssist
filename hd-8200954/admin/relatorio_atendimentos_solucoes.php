<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $produto_referencia = $_POST['produto_referencia'];
    $produto_descricao  = $_POST['produto_descricao'];
    $xatendente         = $_POST['xatendente'];
    $marca              = $_POST['marca'];
    $solucao            = $_POST['solucao'];
    $n_atendimento      = $_POST['n_atendimento'];

    if(count($marca)>0){
        $cond_marca = " AND tbl_produto.marca in (". implode(',', $marca). ")";
    }

     if(count($solucao)>0){
        $cond_solucao = " AND tbl_hd_chamado_extra.solucao in (". implode(',', $solucao). ")";
    }

    if(strlen(trim($produto_referencia))>0 AND strlen(trim($produto_descricao))>0){
        $cond_produto = " AND tbl_produto.referencia = '$produto_referencia' ";
    }

    if(strlen(trim($xatendente))>0){
        $cond_atendente = " AND tbl_hd_chamado.atendente  = $xatendente "; 
    }    

    if(strlen(trim($n_atendimento))>0){
        $cond_n_atendimento = " AND tbl_hd_chamado.hd_chamado = $n_atendimento ";
    }

       
    
    if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
        $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
        $xdata_inicial = str_replace("'","",$xdata_inicial);
    }else{
        $msg_erro["msg"]['data']    ="Data Inválida";
        $msg_erro["campos"][] = "data_inicial";
    }

    if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
        $xdata_final =  fnc_formata_data_pg(trim($data_final));
        $xdata_final = str_replace("'","",$xdata_final);
    }else{
         $msg_erro["msg"]['data']    ="Data Inválida";
        $msg_erro["campos"][] = "data_final";
    }

    if(!count($msg_erro["msg"])){
        $dat = explode ("/", $data_inicial );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];

        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"]['data']    ="Data Inválida";
            $msg_erro["campos"][] = "data_inicial";
        }
    }
    if(!count($msg_erro["msg"])){
        $dat = explode ("/", $data_final );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"]['data']    ="Data Inválida";
            $msg_erro["campos"][] = "data_final";
        }
    }

    if($xdata_inicial > $xdata_final) {
        $msg_erro["msg"][]    ="Data Inicial maior que final";
        $msg_erro["campos"][] = "data_inicial";
    }

    if(strlen(trim($xdata_inicial))>0 AND strlen(trim($xdata_final)) >0 ){
        $cond_data = " AND tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ";
    }

    if(count($msg_erro) ==0){
        $sql = "SELECT 
                    tbl_hd_chamado.hd_chamado, 
                    tbl_admin.nome_completo,
                    (select count(1) from tbl_hd_chamado_item where hd_chamado = tbl_hd_chamado.hd_chamado) as qtde_interacoes, TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') as abertura, 
                    ( select TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY') from tbl_hd_chamado_item where hd_chamado = tbl_hd_chamado.hd_chamado and status_item = 'Resolvido' order by data desc limit 1) as data_resolvido,                    
                    tbl_produto.referencia, 
                    tbl_linha.nome as descricao_linha, 
                    tbl_familia.descricao as descricao_familia,
                    tbl_defeito_reclamado.descricao as descricao_defeito_reclamado,
                    tbl_solucao.descricao as descricao_solucao,
                    tbl_cidade.estado
                FROM tbl_hd_chamado
                INNER JOIN tbl_hd_chamado_extra using (hd_chamado)
                INNER JOIN tbl_produto using (produto)
                INNER JOIN tbl_admin on tbl_admin.admin = tbl_hd_chamado.admin AND tbl_admin.fabrica = $login_fabrica
                INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica 
                INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = $login_fabrica
                INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado 
                INNER JOIN tbl_solucao ON tbl_solucao.solucao = tbl_hd_chamado_extra.solucao AND tbl_solucao.fabrica = $login_fabrica
                INNER JOIN tbl_cidade on tbl_cidade.cidade = tbl_hd_chamado_extra.cidade
                where tbl_hd_chamado.fabrica = $login_fabrica
                AND tbl_hd_chamado_extra.solucao is not null 
                $cond_marca
                $cond_solucao
                $cond_produto 
                $cond_atendente
                $cond_data
                $cond_n_atendimento ";
        $resSubmit = pg_query($con, $sql);
    }
}

$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

$layout_menu = "callcenter";
$title = "RELATÓRIO DE ATENDIMENTO";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");
?>

<style>
    
.ui-multiselect{
    line-height: 15px;
}

</style>

<script type="text/javascript">

    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("produto", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        $("#marca, #solucao").multiselect({
            selectedText: "# de # opções"
        });

    });

    function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
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
?>

<!-- <script type="text/javascript" src="js/grafico/highcharts.js"></script> -->
<script type="text/javascript" src="js/novo_highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='xatendente'>Atendente</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="xatendente" id="xatendente">
                            <option value=""></option>
                        <?
                            $sql = "SELECT admin, nome_completo
                                    from tbl_admin
                                    where fabrica = $login_fabrica
                                    and ativo is true
                                    and (privilegios like '%call_center%' or privilegios like '*')
                                    $cond_admin_fale_conosco
                                    order by login";
                            $res = pg_exec($con,$sql);
                            foreach (pg_fetch_all($res) as $key) {

                                    $selected_atendente = ( isset($xatendente) and ($xatendente == $key['admin']) ) ? "SELECTED" : '' ;
?>
                            <option value="<?php echo $key['admin']?>" <?php echo $selected_atendente ?> >
                                <?php echo $key['nome_completo']?>
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
            <label class='control-label' for='produto_descricao'>Nº do Atendimento</label>
            <div class='controls controls-row'>
                <div class='span12 input-append'>
                <input type="text" name="n_atendimento" value="<?=$n_atendimento?>" class='span12'>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <label class='control-label' for='produto_descricao'>Solução</label>
            <div class='controls controls-row'>
                <div class='span12 input-append'>
                    <select name="solucao[]" id="solucao" multiple="multiple">
                        <?php
                            $sql = " SELECT solucao, descricao from tbl_solucao where fabrica = 81 AND ativo is true ";
                            $res = pg_query($con, $sql);
                            for($i=0; $i<pg_num_rows($res); $i++){
                                $solucao            = pg_fetch_result($res, $i, solucao);
                                $descricao_solucao  = pg_fetch_result($res, $i, descricao);

                                echo "<option value='$solucao'>$descricao_solucao </option>";
                            }                
                        ?>
                    </select>
                </div>
            </div>            
        </div>
        <div class='span4'>
            <label class='control-label' for='produto_descricao'>Marca</label>
            <div class='controls controls-row'>
                <div class='span12 input-append'>
                    <select name="marca[]" id="marca" multiple="multiple">
                        <?php
                            $sql = " SELECT nome, marca
                                    from tbl_marca 
                                    WHERE fabrica = $login_fabrica";
                            $res = pg_query($con, $sql);
                            for($i=0; $i<pg_num_rows($res); $i++){
                                $nome           = pg_fetch_result($res, $i, nome);
                                $marca_db       = pg_fetch_result($res, $i, marca);

                                if($marca == $marca_db){
                                    $selected = " selected ";
                                }else{
                                    $selected = " ";
                                }

                                echo "<option value='$marca_db' $selected >$nome </option>";
                            }                
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</FORM>

<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        echo "<br />";
        $count = pg_num_rows($resSubmit);
?>
</div>
<table id="callcenter_relatorio_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr>
            <th class='titulo_coluna' colspan='11'>Atendimentos X Soluções</th>
        </tr>
        <TR class='titulo_coluna'>
            <th>Atendimento</TD>
            <th>Qtde Interações</th>
            <th>Abertura</TD>
            <th>Fechamento</TD>
            <th>Referência Produto</TD>
            <th>Familia</TD>
            <th>Linha</TD>            
            <th>Defeito Reclamado</TD>
            <th>Solução</TD>
            <th>Atendente</TD>
            <th>Estado</TD>
        </TR >
    </thead>
    <tbody>
    <?php 
        for($a=0; $a<pg_num_rows($resSubmit); $a++){
            $atendimento = pg_fetch_result($resSubmit, $a, hd_chamado);
            $qtde_interacoes = pg_fetch_result($resSubmit, $a, qtde_interacoes);            
            $abertura = pg_fetch_result($resSubmit, $a, abertura);
            $data_resolvido = pg_fetch_result($resSubmit, $a, data_resolvido);
            $referencia = pg_fetch_result($resSubmit, $a, referencia);
            $descricao_familia = pg_fetch_result($resSubmit, $a, descricao_familia);
            $descricao_linha = pg_fetch_result($resSubmit, $a, descricao_linha);
            $descricao_defeito_reclamado = pg_fetch_result($resSubmit, $a, descricao_defeito_reclamado);
            $descricao_solucao = pg_fetch_result($resSubmit, $a, descricao_solucao);
            $atendente = pg_fetch_result($resSubmit, $a, nome_completo);
            $estado = pg_fetch_result($resSubmit, $a, estado);

            echo "<tr>";
                echo "<td class='tac'> <a href='callcenter_interativo_new.php?callcenter=$atendimento' target='_blank'> $atendimento </a></td>";
                echo "<td class='tac'>$qtde_interacoes</td>";
                echo "<td class='tac'>$abertura</td>";
                echo "<td class='tac'>$data_resolvido</td>";
                echo "<td class='tac'>$referencia</td>";
                echo "<td class='tac'>$descricao_familia</td>";
                echo "<td class='tac'>$descricao_linha</td>";
                echo "<td class='tac'>$descricao_defeito_reclamado</td>";
                echo "<td class='tac'>$descricao_solucao</td>";
                echo "<td class='tac'>$atendente</td>";
                echo "<td class='tac'>$estado</td>";
            echo "</tr>";

        }
    ?>
        
    </tbody>    
</table>

            <?php
            if ($count > 50) {
            ?>
                <script>
                    $.dataTableLoad({ table: "#callcenter_relatorio_atendimento" });
                </script>
            <?php
            }
            ?>
        <br />

            <?php
            echo $grafico_topo.$grafico_conteudo.$grafico_rodape;

        }else{
            echo "<div class='container'>
            <div class='alert'>
                    <h4>Nenhum resultado encontrado</h4>
            </div>
            </div>";
        }
    }
?>
<? include "rodape.php" ?>
