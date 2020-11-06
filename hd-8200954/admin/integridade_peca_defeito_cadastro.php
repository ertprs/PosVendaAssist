<?php

    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';

    $admin_privilegios="financeiro";
    $layout_menu = "cadastro";

    include 'autentica_admin.php';
    include 'funcoes.php';

    $motivo = "Defeito";
    if ($login_fabrica == 157) {
        $motivo = "Motivo";
    }

    $title="INTEGRIDADE - PEÇA X ".strtoupper($motivo);


    if (filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)) {

        $servico = filter_input(INPUT_POST,"servico");
        $cond = "";
        if (!empty($servico)) {
            $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = $login_fabrica AND servico_realizado = $servico";
            $res = pg_query($con,$sql);
            $cond = " AND servico_realizado = ".pg_fetch_result($res,0,servico_realizado);
        }
        if($_POST ["acao"] == "ativar"){

            $defeito =  $_POST['idDef'];
            $peca    =  $_POST['idPeca'];

            pg_query($con, "BEGIN");

                $sql = "UPDATE tbl_peca_defeito
                        SET ativo = TRUE
                        WHERE peca = {$peca}
                        AND defeito = {$defeito}
                        $cond
                ";
                $res = pg_query($con, $sql);

                if (!pg_last_error()) {
                    pg_query($con, "COMMIT");
                    echo "success";
                } else {
                    pg_query($con, "ROLLBACK");
                    echo "error";
                }

        } else if ($_POST ["acao"] == "inativar") {
            $defeito =  $_POST['idDef'];
            $peca    =  $_POST['idPeca'];

            pg_query($con, "BEGIN");

            $sql = "UPDATE tbl_peca_defeito
                        SET ativo = FALSE
                        WHERE peca = {$peca}
                        AND defeito = {$defeito}
                        $cond
                ";

                $res = pg_query($con, $sql);
                if (!pg_last_error()) {
                    pg_query($con, "COMMIT");
                    echo "success";
                } else {
                    pg_query($con, "ROLLBACK");
                    echo "error";
                }
        }

        exit;
    }


    include 'cabecalho_new.php';


    $plugins = array(
        "shadowbox",
        "mask",
        "dataTable",
        "multiselect"
    );

include("plugin_loader.php");

    if($_POST && $_POST["btn_acao"] != "importar_txt"){
        $btn_acao = $_POST['btn_lista'];
        $referencia = $_POST['referencia'];
        $descricao  = $_POST['descricao'];
        $ativo      = $_POST['ativo'];

        if(!isset($_POST['peca'])){
            if(empty($referencia) OR empty($descricao)){
                $btn_acao = "";
                $msg_erro ="Informe a Peça para Pesquisa";
            }

            $sql = "SELECT peca FROM tbl_peca WHERE fabrica = $login_fabrica AND referencia = '$referencia'";
            $res = pg_query($con,$sql);
            if(pg_numrows($res) == 0){
                $msg_erro = "Peça Inválida";
                $btn_acao = "";
            }
        }
    }

    // ----- Inicio do cadastro ----------

    if ( $btn_acao == "gravar" ) {
        $defeito_constatado = $_POST['defeito_constatado'];
        $peca = filter_input(INPUT_POST,'peca');
        $$servico_realizado = "";

        if ($login_fabrica == 120 or $login_fabrica == 201) {
            $servico_realizado = filter_input(INPUT_POST,'servico_realizado');

            if(empty($servico_realizado)){
                $msg_erro = "Selecione um Serviço Realizado";
            }
            $cond = " AND servico_realizado = $servico_realizado ";
        }

        if(count($defeito_constatado) == 0){
            $msg_erro = "Selecione um ". $motivo;
        }

        if(empty($servico_realizado)){
            $servico_realizado = "null";
        }

        pg_query($con, "BEGIN");

        $defeitosSelect = implode(",", $defeito_constatado);

        if ($login_fabrica == 157) {
            $sqlDiagExcluir = "DELETE FROM tbl_peca_defeito WHERE peca = {$peca} AND defeito NOT IN ({$defeitosSelect})";
            $resDiagExcluir = pg_query($con, $sqlDiagExcluir);
        }

        for ($i=0; $i < count($defeito_constatado); $i++) {

            if(empty($msg_erro)){
                $sql = "
                    SELECT  peca
                    FROM    tbl_peca_defeito
                    WHERE   peca    = $peca
                    AND     defeito = $defeito_constatado[$i]
                    $cond
                ";
                $res = pg_query($con,$sql);
                if(pg_numrows($res) > 0){
                    continue;
                }
            }
            

            if(empty($msg_erro)) {
                $sql = "INSERT INTO tbl_peca_defeito (
                            peca,
                            defeito,
                            servico_realizado,
                            ativo
                        ) VALUES (
                            $peca,
                            $defeito_constatado[$i],
                            $servico_realizado,
                            true
                        )";
                $res = pg_query($con,$sql);
                $msg_erro = pg_errormessage($con);
            }
            
        }

        if(empty($msg_erro)) {
            $msg = 'Gravado com Sucesso!';
            pg_query($con, "COMMIT");
        }
        else{
            pg_query($con, "ROLLBACK");
        }

        $btn_acao = "listar";
    }

    if ( $btn_acao == "nova_pesquisa" ) {
        $peca = "";
        $referencia = "";
        $descricao  = "";
    }

    if($_GET){
        $btn_acao = $_GET['btn_lista'];
    }

if ($_POST["btn_acao"] == "importar_txt") {
    try {
        $arquivo    = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

        $tmpPathInfo = pathinfo($arquivo['tmp_name']);
        $pathInfo = pathinfo($arquivo['name']);

        if (!in_array($pathInfo["extension"], array('csv', "txt" ))) {
            throw new Exception("Extensão do arquivo deve ser CSV ou TXT");
        }

        $maxFileSize = 2048000;

        if ($arquivo["size"] > $maxFileSize) {
            throw new Exception("Arquivo maior do que o permitido (2MB)");
        }

        $path = $tmpPathInfo["dirname"]."/".$tmpPathInfo["basename"];

        $file = fopen($path,'r');

        while (!feof($file)) {
            $buffer = fgets($file);
            if ($buffer <> "\n" && strlen(trim($buffer)) > 0) {
                list($codigo_peca,$defeito,$servico) = explode(";",$buffer);

                $codigo_peca    = trim($codigo_peca);
                $defeito        = trim($defeito);
                $servico        = trim($servico);

                if (empty($codigo_peca) || empty($defeito) || empty($servico)) {
                    throw new Exception("Falha no arquivo: Dados faltantes.");
                }

                /**
                 * - Verifica PEÇA
                 */
                $sqlPeca = "
                    SELECT  tbl_peca.peca
                    FROM    tbl_peca
                    WHERE   fabrica     = $login_fabrica
                    AND     ativo       IS TRUE
                    AND     referencia  = '$codigo_peca'
                ";
                $resPeca = pg_query($con,$sqlPeca);

                if (pg_num_rows($resPeca) == 0) {
                    throw new Exception("Falha no arquivo: Peça $codigo_peca não encontrada.");
                }

                /**
                 * - Verifica DEFEITO
                 */

                $sqlDef = "
                    SELECT  tbl_defeito.defeito
                    FROM    tbl_defeito
                    WHERE   fabrica = $login_fabrica
                    AND     ativo   IS TRUE
                    AND     codigo_defeito = '$defeito'
                ";
                $resDef = pg_query($con,$sqlDef);

                if (pg_num_rows($resDef) == 0) {
                    throw new Exception("Falha no arquivo: $motivo  $defeito não encontrado.");
                }

                /**
                 * - Verifica SERVIÇO
                 */
                $auxServ = str_replace(array('ç','ã'),array('c','a'),utf8_decode($servico));
                $sqlServ = "
                    SELECT  tbl_servico_realizado.servico_realizado
                    FROM    tbl_servico_realizado
                    WHERE   fabrica = $login_fabrica
                    AND     ativo   IS TRUE
                    AND     fn_retira_especiais(descricao) = fn_retira_especiais('$auxServ')
                ";
                $resServ = pg_query($con,$sqlServ);

                if (pg_num_rows($resDef) == 0) {
                    throw new Exception("Falha no arquivo: Serviço $servico não encontrado.");
                }

                $arqPeca                = pg_fetch_result($resPeca,0,peca);
                $arqDefeito             = pg_fetch_result($resDef,0,defeito);
                $arqServicoRealizado    = pg_fetch_result($resServ,0,servico_realizado);

                /**
                 * - Verifica Já existência de integridade
                 */

                $sqlVer = "
                    SELECT  COUNT(1) AS tem_registro
                    FROM    tbl_peca_defeito
                    WHERE   peca                = $arqPeca
                    AND     defeito             = $arqDefeito
                    AND     servico_realizado   = $arqServicoRealizado
                ";
//                 echo $sqlVer;exit;
                $resVer = pg_query($con,$sqlVer);

                if (pg_fetch_result($resVer,0,tem_registro) > 0) {
                    throw new Exception("Peça $codigo_peca já possui integridade com defeito $defeito e serviço $servico");
                }

                /**
                 * - Realiza a gravação
                 */

                pg_query($con,"BEGIN TRANSACTION");

                $sqlGrava = "
                    INSERT INTO tbl_peca_defeito (
                        peca,
                        defeito,
                        servico_realizado,
                        ativo
                    ) VALUES (
                        $arqPeca,
                        $arqDefeito,
                        $arqServicoRealizado,
                        true
                    )
                ";
                $resGrava = pg_query($con,$sqlGrava);
                if (pg_last_error($con)) {
                    throw new Exception("Não foi possível realizar a integração da peça $codigo_peca");
                }
                pg_query($con,"COMMIT TRANSACTION");
            }
        }
        $msg = "Importação realizada com sucesso";
    } catch (Exception $ex) {
        pg_query($con, "ROLLBACK TRANSACTION");
        $msg_erro = "Tente Novamente: ".$ex->getMessage();
    }
}
    // fim cadastro
?>

<script type="text/javascript">

    $(function() {
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
        $(".multiple").multiselect({
            selectedText: "selecionados # de #",
           minWidth: 400
        });

        $(document).on("click", ".btn-ver", function(){
            var idPeca  =  $(this).data('peca');
            if( $(".tr-"+idPeca).is(":visible")){
              $(".tr-"+idPeca).hide();
            }else{
              $(".tr-"+idPeca).show();
            }
        });


        // AJAX ATIVA / INATIVA
        $('.btn-ativar').click(function(){

            var botao   = this;
            var idPeca  =  $(botao).attr('peca');
            var idDef   =  $(botao).attr('defeito');
            var acao    =  $(botao).attr('acao');
            var linha   =  $(botao).attr('linha');
            var servico = "";
<?php
    if ($login_fabrica == 120 or $login_fabrica == 201) {
?>

            servico   =  $(botao).attr('servico');
<?php
    }
?>

            if (ajaxAction()) {

                $.ajax({
                    url: "<?=$_SERVER['PHP_SELF']?>",
                    type: "POST",
                    dataType: "JSON",
                    data: {
                        ajax:true,
                        acao: acao,
                        idPeca: idPeca,
                        idDef: idDef,
                        servico:servico
                    },

                    complete: function (data) {
                        data = data.responseText;
                         // console.log(acao);


                            if(acao == 'ativar'){

                                $(botao).attr({ "value": "Inativar"});
                                $(botao).removeClass("btn-success").addClass("btn-danger");
                                $(botao).attr({ "acao": "inativar"});
                                var img = $(botao).parents('tr').find('img[name=img_ativo]');
                                $(img).attr({ "src": "imagens/status_verde.png"});


                            }else if(acao == 'inativar'){
                                $(botao).attr({ "value": "Ativar"});
                                $(botao).removeClass("btn-danger").addClass("btn-success");
                                $(botao).attr({ "acao": "ativar"});
                                var img = $(botao).parents('tr').find('img[name=img_ativo]');
                                $(img).attr({ "src": "imagens/status_vermelho.png"});



                            }

                    }

                });
            }

        });
    //  FIM AJAX



    });

    function retorna_peca(retorno){
        $("#referencia").val(retorno.referencia);
        $("#descricao").val(retorno.descricao);
        Shadowbox.init();
     }

</script>

<?php

    if($btn_acao == "listar" OR !empty($peca)){

        if ($login_fabrica == 120 or $login_fabrica == 201) {
            $campo = " tbl_servico_realizado.descricao AS servico_realizado, tbl_servico_realizado.servico_realizado as servico_realizado_id , " ;
            $join  = " JOIN   tbl_servico_realizado     ON  tbl_servico_realizado.servico_realizado = tbl_peca_defeito.servico_realizado
                                                        AND tbl_servico_realizado.fabrica           = $login_fabrica
            ";
        }

        if(empty($peca)){
            $sqlPeca = "SELECT peca FROM tbl_peca WHERE referencia = '$referencia' AND fabrica = $login_fabrica";
            $resPeca = pg_query($con,$sqlPeca);

            $peca = pg_result($resPeca,0,0);
        }

        $sqlDef = "
            SELECT  tbl_peca_defeito.peca_defeito,
                    tbl_defeito.defeito,
                    tbl_defeito.descricao,
                    $campo
                    tbl_peca_defeito.ativo
            FROM    tbl_peca_defeito
            JOIN    tbl_defeito             ON  tbl_peca_defeito.defeito                = tbl_defeito.defeito
                                            AND tbl_defeito.fabrica                     = $login_fabrica
            $join
            WHERE   tbl_peca_defeito.peca = {$peca}";

        //echo $sqlDef;exit;

        $resDef = pg_query($con,$sqlDef);

        $defeito_constatado = [];
        while ($dadosDef = pg_fetch_object($resDef)) {
            $defeito_constatado[] = $dadosDef->defeito;
        } 

        if (strlen($msg_erro) > 0) {
        ?>
                <div class='alert alert-error'>
                    <h4><?php echo $msg_erro; ?></h4>
                </div>
        <?php
        }
        ?>
        <?php
        if (strlen($msg) > 0) {
        ?>
                <div class="alert alert-success">
                    <h4><?php echo $msg; ?></h4>
                </div>
        <?php
        }
        ?>

        <form name='frm_integridade' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' enctype='multipart/form-data' >
            <div class='titulo_tabela '>Cadastro</div>
            <br/>

            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label'><strong>Referência</strong></label>
                        <div class='controls controls-row'>
                            <?php echo $referencia; ?>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label'><strong>Descrição</strong></label>
                        <div class='controls controls-row'>
                            <?php echo $descricao; ?>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>

            <div class='row-fluid'>
                <div class="span12">
                    <div class='span2'></div>
                    <div class='span4'>
                        <div class='control-group'>
                            <label class='control-label' for='defeito_constatado'><?php echo $motivo;?></label>
                            <div class='controls controls-row'>
                                <div class='span4'>
                                    <select name="defeito_constatado[]" multiple="multiple" class="multiple" id="defeito_constatado">
                                        <option value="">Selecione <?php echo $motivo;?></option>
                                        <?php
                                                $sql = "SELECT defeito,
                                                        codigo_defeito,
                                                                descricao
                                                                FROM tbl_defeito
                                                                WHERE fabrica = $login_fabrica
                                                                AND ativo IS TRUE
                                                                ORDER BY descricao,codigo_defeito ";

                                                $res = pg_query($con,$sql);
                                                $total = pg_numrows($res);
                                                if($total > 0){
                                                    for($i = 0; $i < $total; $i++){
                                                        $codigo = pg_result($res,$i,defeito);
                                                        $defeito = pg_result($res,$i,descricao);
                                                        $codigo_defeito = pg_result($res,$i,codigo_defeito);

                                                        $selected = (in_array($codigo, $defeito_constatado)) ? "selected" : "";

                                                        if ($login_fabrica == 131){
                                                                echo "<option $selected value='$codigo'>$codigo_defeito - $defeito</option>";
                                                        } else {
                                                            echo "<option $selected value='$codigo'>$defeito</option>";
                                                        }
                                                    }
                                                }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
<?php
        if ($login_fabrica == 120 or $login_fabrica == 201) {
?>
                    <div class='span4'>
                        <div class='control-group'>
                            <label class='control-label' for='servico_realizado'>Serviço Realizado</label>
                            <div class='controls controls-row'>
                                <div class='span4'>
                                    <select name="servico_realizado" id="servico_realizado">
                                        <option value="">Selecione Defeito</option>
<?php
            $sqlServ = "
                SELECT  servico_realizado,
                        descricao
                FROM    tbl_servico_realizado
                WHERE   fabrica = $login_fabrica
                AND     ativo IS TRUE
            ";
            $resServ = pg_query($con,$sqlServ);

            while ($servicos = pg_fetch_object($resServ)) {
?>
                                        <option value="<?=$servicos->servico_realizado?>"><?=$servicos->descricao?></option>
<?php
            }
?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
<?php
        }
?>
                    <div class="span2"></div>
                </div>
            </div>

            <p><br />
                <input type='hidden' name='peca' value='<?php echo $peca;?>'>
                <input type='hidden' name='referencia' value='<?php echo $referencia;?>'>
                <input type='hidden' name='descricao' value='<?php echo $descricao;?>'>
                <input type='button' class="btn" value='Gravar' onclick='document.frm_integridade.btn_lista.value="gravar"; document.frm_integridade.submit();'>

                <input type='hidden' name='btn_lista' value=''>
                <input type='button' class="btn" value='Nova Pesquisa' onclick='document.frm_integridade.btn_lista.value="nova_pesquisa"; document.frm_integridade.submit();'>

            </p><br />
        </form>

        <?php
        if(!empty($peca)){

            if(pg_numrows($resDef) > 0){
            ?>

                <table class='table table-striped table-bordered table-hover table-fixed' style='table-layout: fixed !important;' >
                    <thead>
                        <tr class='titulo_coluna'>
                            <th><?php echo $motivo;?></th>
<?php
                if ($login_fabrica == 120 or $login_fabrica == 201) {
?>
                            <th>Serviço Realizado</th>

<?php
                }
?>
                            <th>Ativo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
                $total = pg_numrows($resDef);

                for($i = 0; $i < $total; $i++){
                    $def_codigo = pg_result($resDef, $i, defeito);
                    $def_descricao = pg_result($resDef, $i, descricao);
                    $def_ativo = pg_result($resDef, $i, ativo);

                    if ($login_fabrica == 120 or $login_fabrica == 201) {
                        $def_servico = pg_result($resDef, $i, servico_realizado);
                        $servico_realizado_id = pg_fetch_result($resDef, $i, 'servico_realizado_id');
                    }

                    if($def_ativo=='t') $def_icone = "<img title='Ativo' name ='img_ativo' class='img_ativo' src='imagens/status_verde.png'>";
                    else            $def_icone = "<img title='Inativo' name = 'img_ativo' class='img_ativo' src='imagens/status_vermelho.png'>";

?>
                    <tr>
                        <td><?=$def_descricao?></td>
<?php
                    if ($login_fabrica == 120 or $login_fabrica == 201) {
?>
                        <td>
                            <input type="hidden" name="servico" id="servico_<?=$i?>" value="<?=$def_servico?>" />
                            <?=$def_servico?>
                        </td>
<?php
                    }
?>
                        <td class="tac"><?=$def_icone; ?></td>
                        <td class="tac">
                            <!-- <input type='button' class="btn btn-small btn-danger" value='Excluir' onclick="window.location='<?php echo $PHP_SELF;?>?defeito=<?php echo $def_codigo;?>&peca=<?php echo $peca;?>&btn_lista=excluir'"> -->
                            <input type="hidden" name="condicao" value="<?=$condicao?>" />
                            <?php

                                if ($def_ativo == "f" or $def_ativo == "") {
                                        echo "<input type='button' name='ativar' value='Ativar' class='btn btn-small btn-success btn-ativar' acao='ativar' linha=".$i." peca=".$peca." defeito=".$def_codigo." servico=".$servico_realizado_id.">";
                                    } else {
                                        echo "<input type='button' name='inativar' value='Inativar' class='btn btn-small btn-danger btn-ativar' acao='inativar' linha=".$i." peca=".$peca." defeito=".$def_codigo." servico=".$servico_realizado_id.">";
                                }
                            ?>
                        </td>
                    </tr>
                <?php
                }
            }
        }
        echo "</tbody>";
        echo "</table>";
    } else {
?>
        <?php
            if (strlen($msg_erro) > 0) {
        ?>
        <div class='alert alert-error'>
            <h4><?php echo $msg_erro; ?></h4>
        </div>
        <?php
            } else if (strlen($msg) > 0) {
?>
        <div class='alert alert-success'>
            <h4><?=$msg?></h4>
        </div>

<?php
            }
        ?>
        <form name='frm_integridade' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' enctype='multipart/form-data' >

            <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
            <br/>

            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='referencia'>Referência</label>
                        <div class='controls controls-row'>
                            <div class='span7 input-append'>
                                <input type="text" id="referencia" name="referencia" class='span12' maxlength="20" value="<? echo $referencia ?>" >
                                <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='descricao'>Descrição</label>
                        <div class='controls controls-row'>
                            <div class='span12 input-append'>
                                <input type="text" id="descricao" name="descricao" class='span12' value="<? echo $descricao ?>" >
                                <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                                <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>

            <p><br/>
                <input type='hidden' name='btn_lista' value=''>
                <input type="button" class="btn" value="Pesquisar" onclick='document.frm_integridade.btn_lista.value="listar"; document.frm_integridade.submit();'  /> 
                <a href="integridade_peca_defeito_cadastro.php?listar_tudo=true" class="btn btn-primary"> Listar Todos</a>
            </p><br/>
        </form>

<?php
        if ($login_fabrica == 120 or $login_fabrica == 201) {
?>

        <form name='frm_integridade_txt' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
            <div class='titulo_tabela '>Cadastrar Integridade com arquivo TXT / CSV</div>
            <br/>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span8'>
                    <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='arquivo' style="color:#FFF;font-weight:bold;background-color:#B22;font-size:10px;">Layout de arquivo: Código Peça, Código <?php echo $motivo;?>, Serviço. Separadas por ponto-e-vírgula (";").</label>
                        <div class='controls controls-row'>
                            <div class='span7 input-append'>
                                <input type="file" id="arquivo" name="arquivo" class='span12' >
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
            <p><br/>
                <input type='hidden' value='<?=$produto?>' name='produto_txt'>
                <input type='hidden' name='btn_lista' value='listar'>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'importar_txt');">Importar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
            </p><br/>
        </form>
<?php
        }
    }


if (isset($_GET["listar_tudo"]) && $_GET["listar_tudo"] == true) {

    $sqlPecas = "SELECT  DISTINCT tbl_peca_defeito.peca,
                                tbl_peca.referencia,
                                tbl_peca.descricao
                           FROM tbl_defeito
                           JOIN tbl_peca_defeito ON tbl_peca_defeito.defeito = tbl_defeito.defeito 
                           JOIN tbl_peca ON tbl_peca.peca = tbl_peca_defeito.peca AND tbl_peca.fabrica = $login_fabrica
                          WHERE tbl_defeito.fabrica = $login_fabrica";
    $resPecas = pg_query($con, $sqlPecas);
    if (pg_num_rows($resPecas) > 0) {
?>

    <table class='table table-striped table-bordered table-fixed' style='table-layout: fixed !important;' >
        <thead>
            <tr class='titulo_coluna'>
                <th width="90%" class="tal">Referência - Descrição da Peça</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
                for ($ix = 0; $ix < pg_num_rows($resPecas); $ix++) {
                    $referencia_peca = pg_result($resPecas, $ix, 'referencia');
                    $descricao_peca  = pg_result($resPecas, $ix, 'descricao');
                    $peca            = pg_result($resPecas, $ix, 'peca');
            ?>
            <tr>
                <td>
                    <?php echo $referencia_peca . ' - ' . $descricao_peca;?>
                </td>
                <td class="tac">
                   <button type="button" class="btn btn-mini- btn-primary btn-ver" data-peca="<?php echo $peca;?>">Ver</button>
                </td>
            </tr>
            <tr class="tr-<?php echo $peca;?>" style="display: none;">
                <td colspan="2">
                    <?php
                        if (!empty($peca)) {

                            if ($login_fabrica == 120 or $login_fabrica == 201) {
                                $campo = " tbl_servico_realizado.descricao AS servico_realizado, tbl_servico_realizado.servico_realizado as servico_realizado_id , " ;
                                $join  = " JOIN   tbl_servico_realizado ON  tbl_servico_realizado.servico_realizado = tbl_peca_defeito.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica";
                            }

                            $sqlDef = "SELECT tbl_defeito.defeito,
                                              tbl_defeito.descricao,
                                              $campo
                                              tbl_peca_defeito.ativo
                                         FROM tbl_peca_defeito
                                         JOIN tbl_defeito ON tbl_peca_defeito.defeito = tbl_defeito.defeito AND tbl_defeito.fabrica = $login_fabrica
                                        $join
                                        WHERE tbl_peca_defeito.peca = $peca";

                            $resDef = pg_query($con,$sqlDef);
                            if (pg_numrows($resDef) > 0) {
                    ?>
                    <table class='table table-striped table-bordered table-hover table-fixed' style='table-layout: fixed !important;' >
                        <thead>
                            <tr class='titulo_coluna'>
                                <th><?php echo $motivo;?></th>
                                <?php if ($login_fabrica == 120 or $login_fabrica == 201) {?>
                                <th>Serviço Realizado</th>
                                <?php }?>
                                <th>Ativo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                                $total = pg_num_rows($resDef);

                                for ($i = 0; $i < $total; $i++) {

                                    $def_codigo = pg_result($resDef, $i, defeito);
                                    $def_descricao = pg_result($resDef, $i, descricao);
                                    $def_ativo = pg_result($resDef, $i, ativo);

                                    if ($login_fabrica == 120 or $login_fabrica == 201) {
                                        $def_servico = pg_result($resDef, $i, servico_realizado);
                                        $servico_realizado_id = pg_fetch_result($resDef, $i, 'servico_realizado_id');
                                    }

                                    if ($def_ativo == 't') {
                                        $def_icone = "<img title='Ativo' name ='img_ativo' class='img_ativo' src='imagens/status_verde.png'>";
                                    } else {
                                        $def_icone = "<img title='Inativo' name = 'img_ativo' class='img_ativo' src='imagens/status_vermelho.png'>";
                                    }
                            ?>
                            <tr>
                                <td><?=$def_descricao?></td>
                                <?php if ($login_fabrica == 120 or $login_fabrica == 201) {?>
                                <td><?=$def_servico?></td>
                                <?php }?>
                                <td class="tac"><?=$def_icone; ?></td>
                            </tr>
                            <?php }?>
                        </tbody>
                    </table>
                    <?php }?>
                    <?php }?>
                </td>
            </tr>
        <?php }//fecha for?>
        </tbody>
    </table>
<?php 
    } else { 
        echo '<div class="alert alert-warning"><h4>Nenhuma peça encontrada</h4></div>';
    }//fecha else num rows
}//fecha get lista tudo
include 'rodape.php';

