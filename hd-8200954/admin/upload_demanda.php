<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
$layout_menu = "cadastro";
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include '../class/AuditorLog.php';

$listar_tudo = $_REQUEST['listar_tudo'];
$gerar_excel = $_REQUEST['gerar_excel'];

if ($listar_tudo || $_REQUEST["pesquisa_lista"]) {

    $peca_referencia = $_REQUEST['peca_referencia'];
    $peca_descricao  = $_REQUEST['peca_descricao'];
    $lista_pecas     = array();
    $limit           = "LIMIT 500";

    $cond_ref        = "";
    $cond_descr      = "";

    if (!empty($peca_referencia)) {
        $cond_ref = " AND referencia='$peca_referencia'";
        $limit  = "";
    } elseif (!empty($peca_descricao)) {
        $cond_descr = " AND descricao ilike '%$peca_descricao%'";
        $limit  = "";
    }

    $sql = "SELECT peca,JSON_FIELD('qtde_demanda', parametros_adicionais) AS qtde_demanda, descricao, referencia
              FROM tbl_peca 
             WHERE ativo IS TRUE 
               AND fabrica = {$login_fabrica} 
               AND JSON_FIELD('qtde_demanda', parametros_adicionais) IS NOT NULL
               AND JSON_FIELD('qtde_demanda', parametros_adicionais) ~'\\d'
               AND cast(JSON_FIELD('qtde_demanda', parametros_adicionais) as integer) >= 0 
                   {$cond_descr} 
                   {$cond_ref}
                   {$limit}";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {

        foreach (pg_fetch_all($res) as $key => $rows) {

            $lista_pecas[$key]["peca"]         = $rows['peca'];
            $lista_pecas[$key]["referencia"]   = $rows['referencia'];
            $lista_pecas[$key]["descricao"]    = $rows['descricao'];
            $lista_pecas[$key]["qtd_demanda"]  = $rows['qtde_demanda'];

        }

    }
}

if ($gerar_excel) {
 
    $lista_pecas_csv = array();
 

    $sql = "SELECT peca,JSON_FIELD('qtde_demanda', parametros_adicionais) AS qtde_demanda, descricao, referencia
              FROM tbl_peca 
             WHERE ativo IS TRUE 
               AND fabrica = {$login_fabrica} 
               AND JSON_FIELD('qtde_demanda', parametros_adicionais) IS NOT NULL";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $conteudo_csv    = "Codigo de referencia;Quantidade de Demanda\n";
        $data     = date("d-m-Y-H:i");
        $fileName = "listagem-demanda-{$data}.xls";
        $file     = fopen("/tmp/{$fileName}", "w");

        foreach (pg_fetch_all($res) as $key => $rows) {
            $conteudo_csv .= $rows['referencia'].";".$rows['qtde_demanda']."\n";
        }
        fwrite($file, $conteudo_csv);


        fclose($file);
        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");

            // devolve para o ajax o nome doa rquivo gerado
            echo "xls/{$fileName}";
        }
        exit;

}

    }
if ($_POST) {

    $msg_erro = "";
    $erro_csv = array();
    $log      = array();
    $upload   = $_FILES["upload"];
    $acrescentar_pecas   = $_POST["acrescentar_pecas"];

    if (empty($upload["name"])) {
        $msg_erro = "Selecione um Arquivo";
    }

    $conteudo = file_get_contents($upload["tmp_name"]);
    $conteudo = explode("\n", $conteudo);

    if (empty($conteudo)) {
        $msg_erro = "Arquivo sem conteúdo";
    }

    if (empty($msg_erro)) {

        $conteudo = array_filter(array_map(function($valor){           
            return explode(";", utf8_encode(trim($valor)));
        }, $conteudo), function($valor){
            global $erro_csv;
            if (count($valor) <> 2) {
                if (!empty($valor[0])) {
                    $erro_csv[] = "Layout do arquivo fora do padrão";
                }   
                return false;
            } else {
                return true;
            }
        });
		$i = 0 ; 
        foreach ($conteudo as $key => $rows) {
			$i++;
            $referencia   = trim($rows[0]);

            $sqlDemandaAnt = "SELECT peca, JSON_FIELD('qtde_demanda', parametros_adicionais) AS quantidade_demanda FROM tbl_peca WHERE referencia = '$referencia' AND fabrica = $login_fabrica";

            $resDemandaAnt = pg_query($con, $sqlDemandaAnt);

            $quantidade_demanda = pg_fetch_result($resDemandaAnt, 0, 'quantidade_demanda');
            $pecaDemandaAnt     = pg_fetch_result($resDemandaAnt, 0, 'peca');

            $arrayDemandaAnt[$pecaDemandaAnt]['quantidade_demanda'] = $quantidade_demanda;

        }

        if (!empty($erro_csv)) {
            $msg_erro = $erro_csv[0];
        }

        if (empty($msg_erro)) {
            $xAud = new AuditorLog();

            $pSqlUpdateX = "UPDATE tbl_peca 
                              SET parametros_adicionais = JSON_DELETE_FIELD(parametros_adicionais, 'qtde_demanda') 
                            WHERE ativo IS TRUE 
                              AND fabrica = $1 
                              AND peca = $2";

            pg_prepare($con, "updatePecaX", $pSqlUpdateX);

            $pSqlUpdate = "UPDATE tbl_peca 
                             SET parametros_adicionais = $1 
                           WHERE ativo IS TRUE 
                             AND fabrica = $2 
                             AND peca = $3";
            pg_prepare($con, "updatePeca", $pSqlUpdate);

            if ($acrescentar_pecas != 't' && $login_fabrica != 104) {

                $sql = "SELECT peca, parametros_adicionais, descricao, referencia
                          FROM tbl_peca 
                         WHERE ativo IS TRUE 
                           AND fabrica = {$login_fabrica} 
                           AND JSON_FIELD('qtde_demanda', parametros_adicionais) != ''";

                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {

                    $dadosPeca  = pg_fetch_all($res);

                    foreach ($dadosPeca as $key => $rowsPeca) {

                        $xpeca = $rowsPeca["peca"];

                        $xresUpdate = pg_execute($con, "updatePecaX", array($login_fabrica, $xpeca));

                    }
                }

            }
            foreach ($conteudo as $key => $rows) {

                $referencia   = trim($rows[0]);
                $qtde_demanda = trim($rows[1]);
				$qtde_demanda = str_replace(".","", $qtde_demanda);


                $sql = "SELECT peca, parametros_adicionais, descricao, referencia
                          FROM tbl_peca 
                         WHERE ativo IS TRUE 
                           AND fabrica = {$login_fabrica} 
                           AND referencia = '$referencia'";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) == 0) {

                    $log["pecas_nao_encontradas"][] = $referencia;

                } else {

                    $peca       = pg_fetch_result($res, 0, 'peca');

                    $qtd_anterior = (empty($arrayDemandaAnt[$peca]['quantidade_demanda'])) ? 0 : $arrayDemandaAnt[$peca]['quantidade_demanda'];
                    
                    $xAud->retornaDadosSelect("SELECT {$peca} AS peca,{$qtd_anterior} AS quantidade_demanda");

                    $descricao  = pg_fetch_result($res, 0, 'descricao');
                    $referencia = pg_fetch_result($res, 0, 'referencia');
                    $parametros_adicionais = pg_fetch_result($res, 0, 'parametros_adicionais');
                    $parametros_adicionais = json_decode($parametros_adicionais,1);
                    $parametros_adicionais["qtde_demanda"] = $qtde_demanda;
                    $novo_paramento = json_encode($parametros_adicionais);

                    $resUpdate = pg_execute($con, "updatePeca", array($novo_paramento, $login_fabrica, $peca));

                    $xAud->retornaDadosSelect("SELECT peca, JSON_FIELD('qtde_demanda', parametros_adicionais) AS quantidade_demanda FROM tbl_peca WHERE peca = $peca AND fabrica = $login_fabrica");
					if($i%500 == 0) sleep('1'); 
                    $xAud->enviarLog('update', 'tbl_peca', "$login_fabrica*$peca");
                    if (pg_last_error($con)) {

                        $log["pecas_nao_atualizadas"][$peca]["peca"]         = $peca;
                        $log["pecas_nao_atualizadas"][$peca]["referencia"]   = $referencia;
                        $log["pecas_nao_atualizadas"][$peca]["descricao"]    = $descricao;
                        $log["pecas_nao_atualizadas"][$peca]["qtd_demanda"]  = $qtde_demanda;
                        $log["pecas_nao_atualizadas"][$peca]["status"]       = "Erro ao atualizar";

                    } else {

                        $log["pecas_atualizadas"][$peca]["peca"]         = $peca;
                        $log["pecas_atualizadas"][$peca]["referencia"]   = $referencia;
                        $log["pecas_atualizadas"][$peca]["descricao"]    = $descricao;
                        $log["pecas_atualizadas"][$peca]["qtd_demanda"]  = $qtde_demanda;
                        $log["pecas_atualizadas"][$peca]["status"]       = "Atualizado com sucesso";
                    }
                }
            }
        }
    }
}

$title     = "Upload de Demanda";
$cabecalho = "Upload de Demanda";

include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "dataTable",
    "shadowbox"
);

include 'plugin_loader.php';
?>
<script>
    $(function(){
        Shadowbox.init();
        $.dataTableLoad({
            table: "#content"
        });

       $(document).on('click', ".show-log", function(){
            var url = 'relatorio_log_alteracao_new.php?' +
                'parametro=tbl_' + $(this).data('object') +
                '&id=' + $(this).data('value')+'&program_url='+$(this).data('program');

            if ($(this).data('title'))
                url += "&titulo=" + $(this).data('title');

            Shadowbox.open({
                content: url,
                player: "iframe",
                height: 600,
                width: 800
            });
        });

        $.autocompleteLoad(Array("produto", "peca", "posto"));

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

    });

    function retorna_peca(retorno){
        $("#peca_referencia").val(retorno.referencia);
        $("#peca_descricao").val(retorno.descricao);
    }

</script>
<?php if (!$listar_tudo) {?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_pesquisa_cadastro' method='post' action='<?=$PHP_SELF?>' class="form-search form-inline" enctype="multipart/form-data">
    <div id="div_consulta" class="tc_formulario">
        <div class="titulo_tabela">Upload de Demanda</div>
        <br>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <?php
                    if (!empty($msg_erro)) {
                        echo '<div class="alert alert-danger">'.$msg_erro.'</div>';
                    }
                ?>
                <div class="alert">
                    <p>A extensão do arquivo para Upload deve ser .CSV</p>
                    Layout do anexo com delimitador 'ponto e vírgula': <b>Código da Peça;Quantidade</b><br />
                </div>
                <div class='control-group'>
                    <label class='control-label'>Upload do Arquivo</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="file" name="upload" id="upload">
                        </div>
                    </div>
                </div>
                <?php if ($login_fabrica != 104) { ?>
                    <div class='control-group'>
                        <input type="checkbox" value="t" id="acrescentar_pecas" name="acrescentar_pecas" <?php echo ($_POST["acrescentar_pecas"] == 't') ? 'checked="checked"' : '';?> /> <b> Apenas acrescentar Demanda de Peças</b>
                    </div>
                <?php } ?>
            </div>
            <div class="span2"></div>
        </div>
        <br />
        <p class="tac">
            <input type="submit" class="btn btn-primary" name="gravar" value="Upload do Arquivo" />
            <a href="upload_demanda.php?listar_tudo=true" class="btn">Listar Demandas</a>
        </p><br />
    </div>
</form>

<?php if (!empty($log)) {//RESULTADO DO UPLOAD?>
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <caption class='titulo_coluna'><h4>Demandas Atualizadas</h4></caption>
        <thead>
            <tr class='titulo_coluna' >
                <th>Código</th>
                <th class="tal">Descrição</th>
                <th>Qtde Demanda</th>
                <th>Status</th>
                <th>Log</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        if (isset($log["pecas_atualizadas"])) {  
            foreach ($log["pecas_atualizadas"] as $k => $rows) {
        ?>
        <tr>
            <td class='tac'><?php echo $rows["referencia"];?></td>
            <td class='tal'><?php echo $rows["descricao"];?></td>
            <td class='tac'><?php echo $rows["qtd_demanda"];?></td>
            <td class='tac'>
                <span class="label label-success"><?php echo $rows["status"];?></span>
            </td>
            <td class='tac'>
                <a href="#" data-program='admin/upload_demanda.php' data-title="Log de Demanda de Peças" data-value='<?php echo $login_fabrica;?>*<?php echo $rows["peca"];?>' data-object='peca' class="btn btn-primary show-log">Log</button>
            </td>
        </tr>
        <?php }}?>
        <?php 
        if (isset($log["pecas_nao_atualizadas"])) {  
            foreach ($log["pecas_nao_atualizadas"] as $k => $rows) {
        ?>
        <tr>
            <td class='tac'><?php echo $rows["referencia"];?></td>
            <td class='tal'><?php echo $rows["descricao"];?></td>
            <td class='tac'><?php echo $rows["qtd_demanda"];?></td>
            <td class='tac'>
                <span class="label label-important"><?php echo $rows["status"];?></span>
            </td>
        </tr>
        <?php }}?>
        <?php 
        if (isset($log["pecas_nao_encontradas"])) {  
            foreach ($log["pecas_nao_encontradas"] as $rows) {
        ?>
        <tr>
            <td class='tal' colspan="3">Código de Referência: <b><?php echo $rows;?></b></td>
            <td class='tac' colspan="2">
                <span class="label label-important">Peça não encontrada</span>
            </td>
        </tr>
        <?php }}?>
        </tbody>
    </table>
<?php }?>
<?php }?>

<br />

<?php if ($listar_tudo) {//LISTAR DEMANDAS?>
<form name='frm_pesquisa_cadastro' method='post' action='upload_demanda.php?listar_tudo=true' class="form-search form-inline" enctype="multipart/form-data">
    <input type="hidden" name="pesquisa_lista" value="true" />

    <div id="div_consulta" class="tc_formulario">
        <div class="titulo_tabela">Listagem de Demandas</div>
        <br>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='peca_referencia'>Ref. Peças</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
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
                            <input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <br />
        <p class="tac">
            <input type="submit" class="btn btn-primary" name="gravar" value="Pesquisar" />
            <a href="upload_demanda.php" class="btn">Upload de Demanda </a>
        </p><br />
    </div>
</form>
    <div class="alert">
        Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo CSV no final da tela.
    </div>
    <table class='table table-striped table-bordered table-hover table-fixed' id='content'>
        <thead>
            <tr class='titulo_coluna' >
                <th>Código</th>
                <th class="tal">Descrição</th>
                <th>Qtde Demanda</th>
                <th>Log</th>
            </tr>
        </thead>
        <tbody>
        <?php 
            foreach ($lista_pecas as $k => $rows) {
        ?>
        <tr>
            <td class='tac'><?php echo $rows["referencia"];?></td>
            <td class='tal'><?php echo $rows["descricao"];?></td>
            <td class='tac'><?php echo $rows["qtd_demanda"];?></td>
            <td class='tac'>
                <a href="#" data-program='admin/upload_demanda.php' data-title="Log de Demanda de Peças" data-value='<?php echo $login_fabrica;?>*<?php echo $rows["peca"];?>' data-object='peca' class="btn btn-primary show-log">Log</button>
            </td>
        </tr>
        <?php }?>
        </tbody>
    </table>
    <?php 
        $jsonPOST = excelPostToJson($_REQUEST);
    ?>
    <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' /><br /><br />
    <div class="btn_excel" id='gerar_excel'>         
        <span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
        <span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
    </div>


<?php }?>
<br />
<?php include "rodape.php";?>
