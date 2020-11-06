<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
    $codigo_posto       = $_POST['codigo_posto'];
    $nome_posto         = $_POST['descricao_posto'];
    $cpf_tecnico        = $_POST['cpf_tecnico'];
    $nome_tecnico       = $_POST['nome_tecnico'];
    $situacao           = $_POST["situacao"];

    if(strlen($nome_posto)>0){
        $sql="SELECT posto FROM tbl_posto_fabrica join tbl_posto using(posto) where fabrica=$login_fabrica and trim(tbl_posto_fabrica.codigo_posto)='$codigo_posto'";
        $res=pg_exec($con,$sql);
        $posto=pg_result($res,0,0);
        if(strlen($posto) >0 ){
            $condPosto = " AND tbl_posto_fabrica.posto = $posto ";
        }
    }

    if (!empty($cpf_tecnico) && !empty($nome_tecnico)) {
        $qTecnico = "
            SELECT
                tecnico
            FROM tbl_tecnico
            WHERE fabrica = {$login_fabrica}
            AND cpf = '{$cpf_tecnico}'
            AND nome ILIKE '{$nome_tecnico}';
        ";
        $rTecnico = pg_query($con, $qTecnico);

        if (pg_num_rows($rTecnico) > 0 AND strlen(pg_last_error()) == 0) {
            $tecnico_id = pg_fetch_result($rTecnico, 0, "tecnico");
            $condTecnico = " AND tbl_tecnico.tecnico = {$tecnico_id} ";
        }
    }

    if ($situacao == "A" ) {
        $condSituacao = " AND tbl_tecnico.ativo IS true ";
    } elseif ($situacao =="I") {
        $condSituacao = " AND tbl_tecnico.ativo IS NOT true ";
    }

    if(empty($condPosto) && empty($condTecnico) && empty($condSituacao)) {
        $msg_erro["msg"][] = "Informe um dos Parâmetros de Pesquisa";
        $msg_erro['campos'][] = "posto";
        $msg_erro['campos'][] = "situacao";
        $msg_erro['campos'][] = "tecnico";
    }

    if (count($msg_erro["msg"]) == 0) {
        $sql = "SELECT tbl_posto.nome as descricao_posto, tbl_posto_fabrica.codigo_posto, tbl_tecnico.nome, tbl_tecnico.cpf, tbl_tecnico.email, tbl_tecnico.telefone 
                FROM tbl_tecnico 
                INNER JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_tecnico.posto and tbl_posto_fabrica.fabrica = $login_fabrica 
                INNER JOIN tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto 
                WHERE tbl_tecnico.fabrica = $login_fabrica
                $condTecnico
                $condPosto 
                $condSituacao
                ORDER BY tbl_posto.nome, tbl_tecnico.nome ";
        $resSubmit = pg_query($con,$sql);
	}
    
    if ($_POST["gerar_excel"]) {

        $file     = "xls/relatorio-tecnico-{$login_fabrica}.csv";
        $fileTemp = "/tmp/relatorio-tecnico-{$login_fabrica}.csv" ;
        $fp     = fopen($fileTemp,'w');

        $head = "Técnico;Posto;CPF;E-mail;Telefone;\r\n";

        fwrite($fp, $head);
        
        for($i=0; $i<pg_num_rows($resSubmit); $i++){
            $nome           = pg_fetch_result($resSubmit, $i, nome);
            $codigo_posto   = pg_fetch_result($resSubmit, $i, codigo_posto);
            $cpf            = pg_fetch_result($resSubmit, $i, cpf);
            $telefone       = pg_fetch_result($resSubmit, $i, telefone);
            $email          = pg_fetch_result($resSubmit, $i, email);
            $descricao_posto = pg_fetch_result($resSubmit, $i, descricao_posto);

            $tbody .= "$nome;$codigo_posto - $descricao_posto;$cpf;$email;$telefone; \r\n";
        }

        fwrite($fp, $tbody);
        
            fclose($fp);
            if(file_exists($fileTemp)){
                system("mv $fileTemp $file");

                if(file_exists($file)){
                    echo $file;
                }
            }


        exit;
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
$title = "RELATÓRIO DE TÉCNICOS CADASTRADOS";

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

<script type="text/javascript">

    $(function() {

        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        <? if ($login_fabrica == 50) { ?>

        $("#motivo_atendimento").multiselect({
        selectedText: "# de # opções"

		});
        <? } ?>
	});

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

    function retorna_tecnico(response) {
        $("#cpf_tecnico").val(response.cpf);
        $("#nome_tecnico").val(response.nome);
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
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class='control-group <?= (in_array("tecnico", $msg_erro["campos"])) ? "error" : "" ?>'>
                <label class="control-label" for="cpf_tecnico">CPF</label>
                <div class="controls controls-row">
                    <div class="span7 input-append">
                        <input type="text" name="cpf_tecnico" id="cpf_tecnico" class="span12" value="<?= $cpf_tecnico ?>"> 
                        <span class="add-on" rel="lupa"><i class="icon-search"></i></span>
                        <input type="hidden" name="lupa_config" tipo="tecnico" parametro="cpf">
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class='control-group <?= (in_array("tecnico", $msg_erro["campos"])) ? "error" : "" ?>'>
                <label class="control-label" for="nome_tecnico">Nome Técnico</label>
                <div class="controls controls-row">
                    <div class="span12 input-append">
                        <input type="text" name="nome_tecnico" id ="nome_tecnico" class="span12" value="<?= $nome_tecnico ?>">
                        <span class="add-on" rel="lupa"><i class="icon-search"></i></span>
                        <input type="hidden" name="lupa_config" tipo="tecnico" parametro="nome">
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("situacao", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Situação</label>
                <div class='controls controls-row'>
                    <div class='span4' >
                        <input type="radio" name="situacao" id="ativo" <? if($situacao == "A"){echo " checked ";}?> value="A" > Ativo  
                    </div>
                    <div class='span4'>
                        <input type="radio" name="situacao" id="ativo" <?if($situacao == "I"){echo " checked ";}?> value="I" > Inativo
                    </div>
                </div>
            </div>
        </div>
    </div>

    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</FORM>
</div>
<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        echo "<br />";
        $count = pg_num_rows($resSubmit);
?>
        <div class="container-fluid">
            <table id="relatorio_tecnicos_cadastrados" class='table table-striped table-bordered table-hover table-fixed' >
                <thead>
                    <tr class='titulo_coluna'>
                        <th class='tel'>Técnico</th>
                        <th class='tel'>Posto</th>
                        <th class="tac">CPF</th>
                        <th class="tac">Telefone</th>
                        <th class="tac">E-mail</th>
                    </tr >
                </thead>
                <tbody>
                    <?php 
                    for($i=0; $i<$count; $i++){
                        $nome           = pg_fetch_result($resSubmit, $i, nome);
                        $codigo_posto   = pg_fetch_result($resSubmit, $i, codigo_posto);
                        $cpf            = pg_fetch_result($resSubmit, $i, cpf);
                        $telefone       = pg_fetch_result($resSubmit, $i, telefone);
                        $email          = pg_fetch_result($resSubmit, $i, email);
                        $descricao_posto = pg_fetch_result($resSubmit, $i, descricao_posto);
                    
                    ?>
                    <tr>
                        <td><?=$nome?></td>
                        <td><?=$codigo_posto." - ". $descricao_posto?></td>
                        <td><?=$cpf?></td>
                        <td><?=$telefone?></td>
                        <td><?=$email?></td>
                    </tr>
                    <?php 
                        }
                    ?>
                </tbody>			
            </table>
        </div>
        <script>
            $.dataTableLoad({ table: "#relatorio_tecnicos_cadastrados" });
        </script>

        <div class="excel">
            <?php
                $jsonPOST = excelPostToJson($_POST);
            ?>
            
            <div id='gerar_excel' class="btn_excel">
                <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
                <span><img src='imagens/excel.png' /></span>
                <span class="txt">Gerar Arquivo Excel</span>
            </div>
        </div>
		<br />
<?php
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

