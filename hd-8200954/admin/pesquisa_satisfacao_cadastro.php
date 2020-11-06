<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = 'cadastro';

include 'autentica_admin.php';

if ($_POST['btn_acao']) {
    $pesquisa      = $_POST['pesquisa'];
    $titulo        = trim($_POST['titulo']);
    $categoria     = $_POST['categoria'];
    $periodo_envio = $_POST['periodo_envio'];
    $ativo         = $_POST['ativo'];
    $texto_email   = $_POST['texto_email'];
    

    $arr_categorias = array(
        'os' => 'Ordem de Serviço',
        'posto_autorizado' => 'Posto Autorizado',
        "callcenter" => "Callcenter",
        "callcenter_email" =>"E-mail Callcenter",
        "os_email"=> "E-mail Ordem de Serviço",
        "os_sms"  => "SMS Ordem de Serviço"
    );

    if (empty($titulo)) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'titulo';
    }
    
    if (empty($categoria)) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'categoria';
    }
    
    if (empty($texto_email) && (in_array($categoria, ['callcenter_email','os_email','os_sms']) || ($login_fabrica == 175 && $categoria == "os"))) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'texto_email';
    }
    if (empty($texto_email) && (in_array($categoria, ['callcenter_email','os_email','os_sms']) || ($login_fabrica == 175 && $categoria == "os"))) {
        $texto_email = str_replace("'", "", $texto_email);
    }
    $titulo      = str_replace("'", "", $titulo);


    $ativo = ($ativo == 't') ? 'true' : 'false';
    
    if (!strlen($periodo_envio)) {
        $periodo_envio = 0;
    }
    
    if (in_array($categoria, ['posto_autorizado','callcenter_email','os_email'])) {
        $repeticao_automatica = $_POST['repeticao_automatica'];
        
        if (empty($_POST['data_inicial'])) {
            $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
            $msg_erro['campos'][]   = 'data_inicial';
        } else {
            list($dia, $mes, $ano) = explode('/', $_POST['data_inicial']);
            
            if (!strtotime("$ano-$mes-$dia")) {
                $msg_erro['msg'][]    = 'Data inicial inválida';
                $msg_erro['campos'][] = 'data_inicial';
            } else {
                $data_inicial = "$ano-$mes-$dia";
            }
        }
        
        if (empty($_POST['data_final'])) {
            $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
            $msg_erro['campos'][]   = 'data_final';
        } else {
            list($dia, $mes, $ano) = explode('/', $_POST['data_final']);
            
            if (!strtotime("$ano-$mes-$dia")) {
                $msg_erro['msg'][]    = 'Data final inválida';
                $msg_erro['campos'][] = 'data_final';
            } else {
                $data_final = "$ano-$mes-$dia";
            }
        }
        
        if (!empty($data_inicial) && !empty($data_final) && strtotime($data_final) < strtotime($data_inicial)) {
            $msg_erro['msg'][]    = 'Data final não pode ser inferior a data inicial';
            $msg_erro['campos'][] = 'data_inicial';
            $msg_erro['campos'][] = 'data_final';
        }

        if (!in_array($categoria, ['posto_autorizado'])) {

            if ($repeticao_automatica == "t" && $periodo_envio == "0") {
                $msg_erro['msg'][]    = "O intervalo para envio deve ser superior a 0";
                $msg_erro['campos'][] = "repeticao_automatica";
                $msg_erro['campos'][] = "periodo_envio";
            }
        }
    }
    
    $repeticao_automatica = ($repeticao_automatica == 't') ? 'true' : 'false';
    
    if ($categoria != 'os_sms') {

        $formularios = $_POST['formulario'];
        
        $qtde = array_filter($formularios, function($f) {
            if (!empty($f['formulario'])) {
                return true;
            } else {
                return false;
            }
        });

        if (!count($qtde)) {
            
            if ($login_fabrica != 35) { 

                $msg_erro['msg'][] = 'É necessário uma versão com o formulário preenchido';
            }

            if ($login_fabrica == 35 && $categoria != 'posto_autorizado') {

                $msg_erro['msg'][] = 'É necessário uma versão com o formulário preenchido';
            }
        }

    }
        
    if ($ativo === 'true') {
        if (!empty($pesquisa)) {
            $wherePesquisa = "AND pesquisa != {$pesquisa}";
        }
        
        $sql = "
            SELECT pesquisa
            FROM tbl_pesquisa
            WHERE fabrica = {$login_fabrica}
            AND categoria = '{$categoria}'
            AND ativo IS TRUE
            {$wherePesquisa}
        ";
        $res = pg_query($con, $sql);
        
        if (pg_num_rows($res) > 0) {
            $msg_erro['msg'][] = 'Somente pode haver uma pesquisa da categoria '.$arr_categorias[$categoria].' ativa cadastrada';
        }
    }
    
    if (empty($msg_erro['msg'])) {
        try {
            pg_query($con, 'BEGIN');
            
            if (in_array($categoria, ['os','callcenter','os_sms'])) {
                $data_inicial = 'null';
                $data_final   = 'null';
            } else {
                $data_inicial = "'{$data_inicial}'";
                $data_final   = "'{$data_final}'";
            }
            
            if (empty($pesquisa)) {
                $sql = "
                    INSERT INTO tbl_pesquisa
                    (fabrica, descricao, categoria, admin, ativo, data_inicial, data_final, periodo_envio, repeticao_automatica, texto_ajuda)
                    VALUES
                    ({$login_fabrica}, E'{$titulo}', '{$categoria}', {$login_admin}, {$ativo}, {$data_inicial}, {$data_final}, {$periodo_envio}, {$repeticao_automatica}, E'{$texto_email}')
                    RETURNING pesquisa
                ";
            } else {
                $sql = "
                    UPDATE tbl_pesquisa SET
                        descricao            = E'{$titulo}',
                        categoria            = '{$categoria}',
                        admin                = {$login_admin},
                        ativo                = {$ativo},
                        data_inicial         = {$data_inicial},
                        data_final           = {$data_final},
                        periodo_envio        = {$periodo_envio},
                        repeticao_automatica = {$repeticao_automatica},
                        texto_ajuda          = E'{$texto_email}'
                    WHERE fabrica = {$login_fabrica}
                    AND pesquisa = {$pesquisa}
                ";
            }
            $res = pg_query($con, $sql);
            if (strlen(pg_last_error()) > 0) {
                throw new \Exception('Erro ao gravar informações #1');
            }
            
            if (empty($pesquisa)) {
                $pesquisa = pg_fetch_result($res, 0, 'pesquisa');
            }
            

            if ($categoria != "os_sms") {

                foreach ($formularios as $f) {
                    $pesquisa_formulario = $f['pesquisa_formulario'];
                    $versao              = $f['versao'];
                    $formulario          = $f['formulario'];
                    $ativo               = ($f['ativo'] == 't') ? 'true': 'false';

                    $formulario = trim($formulario);
                    $formulario = str_replace('&nbsp;', ' ', $formulario);
                    $formulario = html_entity_decode($formulario);
                    $formulario = strip_tags($formulario);
                    $specialChars = array_flip(get_html_translation_table(HTML_ENTITIES, ENT_COMPAT | ENT_HTML401, "ISO-8859-1"));
                    $formulario = strtr($formulario, $specialChars);
                    
                    if (!strlen($pesquisa_formulario)) {
                        $sql = "
                            INSERT INTO tbl_pesquisa_formulario
                            (pesquisa, versao, formulario, ativo)
                            VALUES
                            ({$pesquisa}, {$versao}, '{$formulario}', {$ativo})
                        ";
                    } else {
                        $sql = "
                            UPDATE tbl_pesquisa_formulario SET
                                ativo = {$ativo}
                            WHERE pesquisa = {$pesquisa}
                            AND pesquisa_formulario = {$pesquisa_formulario}
                        ";
                    }
                    $res = pg_query($con, $sql);
                    
                    if (strlen(pg_last_error()) > 0) {
                        throw new \Exception('Erro ao gravar informações #2');
                    }

                }

            }
            
            $msg_success = true;
            unset($_POST, $formularios);
            
            pg_query($con, 'COMMIT');
        } catch(\Exception $e) {
            pg_query($con, 'ROLLBACK');
            $msg_erro['msg'][] = $e->getMessage();
        }
    }
}

if (!empty($_GET['pesquisa'])) {
    $pesquisa = $_GET['pesquisa'];
    
    $sql = "
        SELECT *
        FROM tbl_pesquisa
        WHERE fabrica = {$login_fabrica}
        AND pesquisa = {$pesquisa}
    ";
    $res = pg_query($con, $sql);
    
    if (!pg_num_rows($res)) {
        $msg_erro['msg'][] = 'Pesquisa de satisfação não encontrada';
    } else {
        $res = pg_fetch_assoc($res);
        
        $_RESULT = array(
            'pesquisa'      => $pesquisa,
            'titulo'        => $res['descricao'],
            'categoria'     => $res['categoria'],
            'periodo_envio' => $res['periodo_envio'],
            'ativo'         => $res['ativo'],
            'texto_email'   => $res['texto_ajuda']
        );
        
        if ( in_array($res['categoria'], ['posto_autorizado', 'os_email', 'callcenter', 'callcenter_email'])) {
            if (!empty($res['data_inicial'])) {
                $_RESULT['data_inicial'] = date('d/m/Y', strtotime($res['data_inicial']));
            }
            
            if (!empty($res['data_final'])) {
                $_RESULT['data_final'] = date('d/m/Y', strtotime($res['data_final']));
            }
            
            $_RESULT['repeticao_automatica'] = $res['repeticao_automatica'];
        }
        
        $sql = "SELECT * FROM tbl_pesquisa_formulario WHERE pesquisa = {$pesquisa} ORDER BY versao ASC";
        $res = pg_query($con, $sql);
        
        $formularios = array();
        
        while ($row = pg_fetch_object($res)) {
            $formularios[$row->versao] = array(
              'pesquisa_formulario' => $row->pesquisa_formulario,
              'versao'              => $row->versao,
              'formulario'          => $row->formulario,
              'ativo'               => $row->ativo
            );
        }
    }
}

$layout_menu = 'cadastro';
$title       = 'Cadastro de Pesquisa de Satisfação';
$title_page  = 'Pesquisa(s) de Satisfação';

include 'cabecalho_new.php';

$plugins = array(
    'datepicker',
    'font_awesome',
    'alphanumeric'
);
include 'plugin_loader.php';
?>

<style>

.fa-check, .fa-ban {
    cursor: pointer;
}

</style>

<?php
if ($msg_success) {
?>
    <div class='alert alert-success' >
        <h4>Pesquisa de satisfação, gravada com sucesso</h4>
    </div>
<?php
}

if (count($msg_erro['msg']) > 0) {
?>
    <div class='alert alert-error' >
        <h4><?=implode('<br />', $msg_erro['msg'])?></h4>
    </div>
<?php
}
?>

<div class='row' >
    <b class='obrigatorio pull-right' >  * Campos obrigatórios </b>
</div>

<form action='pesquisa_satisfacao_cadastro.php' method='POST' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela' ><?=$title_page?></div>
    <input type='hidden' name='pesquisa' value='<?=getValue("pesquisa")?>' />
    <br />

    <div class='row-fluid' >
        <div class='span2' ></div>
        <div class='span8' >
            <div class='control-group <?=(in_array("titulo", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='titulo' >Título</label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <h5 class='asteristico'>*</h5>
                        <input type='text' id='titulo' name='titulo' class='span12' value='<?=getValue("titulo")?>' />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class='row-fluid' >
        <div class='span2' ></div>
        <div class='span4' >
            <div class='control-group <?=(in_array("categoria", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='categoria' >Categoria</label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <h5 class='asteristico'>*</h5>
                        <select id='categoria' name='categoria' class='span12' />
                            <option value='' >Selecione</option>
                            <?php
                            $options = array(
                                'os' => 'Ordem de Serviço',
                                'posto_autorizado' => 'Posto Autorizado'
                            );

                            if (in_array($login_fabrica, [35,157])) {

                                $options["callcenter"] = "Callcenter";
                                $options["callcenter_email"] = "E-mail Callcenter";
                                $options["os_email"] = "E-mail Ordem de Serviço";
                            }


                            if (in_array($login_fabrica, [35,157])) {
                                $options["os_sms"] = "SMS Ordem de Serviço";
                            }


                            foreach ($options as $value => $label) {
                                $selected = (getValue('categoria') == $value) ? 'selected' : '';
                                echo "<option value='{$value}' {$selected} >{$label}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span4 intervalo' style='display: none;'>
            <div class='control-group <?=(in_array("periodo_envio", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='periodo_envio' >Intervalo para envio (em dias)</label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <input type='text' id='periodo_envio' name='periodo_envio' class='span12' value='<?=getValue("periodo_envio")?>' />
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="info_email">
        <div class='row-fluid alert-os' style='display: none;'>
            <div class='span2' ></div>
            <div class='span8' >
                <div class="alert alert-warning">
                    <strong>Ordem de Serviço:</strong> a pesquisa de satisfação será enviada ao consumidor após a finalização pelo posto autorizado da ordem de serviço + o intervalo para envio, para enviar no mesmo dia preencha com 0 ou deixe o campo em branco.
                </div>
            </div>
        </div>
        <div class='row-fluid alert-posto' style='display: none;'>
            <div class='span2' ></div>
            <div class='span8' >
                <div class="alert alert-warning">
                    <strong>Posto Autorizado:</strong> a pesquisa de satisfação será enviada na data inicial selecionada + intervalo para envio, caso a opção repetição automática for selecionada, a primeira pesquisa será enviada na data inicial informada e as outras serão enviadas no intervalo informado no campo intervalo para envio.
                    <p>É Obrigatório utilizar o coringa <b>:link</b></p>
                </div>
            </div>
        </div>
           <div class='row-fluid alert-callcenter' style='display: none;'>
            <div class='span2' ></div>
            <div class='span8' >
                <div class="alert alert-warning">
                    <strong>E-mail Callcenter:</strong> a pesquisa de satisfação será enviada via e-mail ao consumidor após a finalização do atendimento + o intervalo para envio, para enviar no mesmo dia preencha com 0 ou deixe o campo em branco.
                </div>
            </div>
        </div>
        
        <div class='row-fluid email' style='display: <?php echo (($login_fabrica == 175 && $_POST["texto_email"] == "os") || in_array($_POST["texto_email"], ["os_email", "callcenter_email", "posto_autorizado","os_sms"])) ? "" : "none";?>'>
            <div class='span2' ></div>
            <div class='span8' >
                <div class='control-group' >
                    <label class='control-label' for='texto_email' >
                        <?php echo ($login_fabrica != 175) ? "Texto do Comunicado" : "Texto do email";?>
                    </label>
                    <div class='controls controls-row' >
                        <div class='span12' >
                            <h5 class='asteristico'>*</h5>
                            <textarea class='span12' id='texto_email' name='texto_email' rows='5' ><?=getValue('texto_email')?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class='row-fluid email ' style='display: <?php echo (($login_fabrica == 175 && $_POST["texto_email"] == "os") || in_array($_POST["texto_email"], ["os_email", "callcenter_email", "posto_autorizado","os_sms"])) ? "" : "none";?>'>
            <div class='span2' ></div>
            <div class='span8' >
                <div class="alert alert-info">
                    <strong>Coringas:</strong>
                    <ul>
                        <li class='tal'>:link - link (clique aqui) para acessar o formulário da pesquisa</li>
                        <li class='tal'>:protocolo - número do atendimento</li>
                        <li class='tal'>:nome_consumidor_protocolo - nome do consumidor do atendimento</li>
                        <li class='tal'>:nome_produto_protocolo - nome do produto do atendimento</li>
                        <li class='tal'>:os - número da ordem de serviço</li>
                        <li class='tal'>:nome_consumidor_os - nome do consumidor do atendimento</li>
                        <li class='tal'>:finalizacao_os - data de finalização da ordem de serviço</li>
                        <li class='tal'>:posto_autorizado - razão social do posto autorizado</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class='row-fluid datas' style='display: none;'>
        <div class='span2' ></div>
        <div class='span4' >
            <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='data_inicial' >Data Inicial</label>
                <div class='controls controls-row' >
                    <div class='span12 input-append' >
                        <h5 class='asteristico'>*</h5>
                        <input type='text' id='data_inicial' name='data_inicial' class='span10' value='<?=getValue("data_inicial")?>' />
                        <span class='add-on' ><i class='fa fa-calendar'></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class='span4' >
            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='data_final' >Data Final</label>
                <div class='controls controls-row' >
                    <div class='span12 input-append' >
                        <input type='text' id='data_final' name='data_final' class='span10' value='<?=getValue("data_final")?>' />
                        <span class='add-on' ><i class='fa fa-calendar'></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class='row-fluid '>
        <div class='span2' ></div>
        <div class='span3'>
            <div class='control-group' >
                <label class='control-label' >Ativo</label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <label class='checkbox' >
                            <input type='checkbox' id='ativo' name='ativo' value='t' <?=(getValue("ativo") == "t") ? "checked" : ""?> /> 
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div class='span3 repeticao-automatica' style='display: none;'>
            <div class='control-group' >
                <label class='control-label' >Repetição Automática</label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <label class='checkbox' >
                            <input type='checkbox' id='repeticao_automatica' name='repeticao_automatica' value='t' <?=(getValue("repeticao_automatica") == "t") ? "checked" : ""?> /> 
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class='row-fluid versoes_div'>
        <div class='span2' ></div>
        <div class='span8' >
            <div class="alert alert-info">
                <strong>Somente pode haver uma versão ativa.</strong><br />
                <strong>Não será possível alterar uma versão após gravar.</strong>
            </div>
            
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr class='titulo_coluna'>
                        <th>Versão</th>
                        <th>Ativo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody class='versoes'>
                    <?php
                    if (empty($formularios)) {
                    ?>
                        <tr>
                            <td class='tac'>
                                <input type='hidden' class='v-id' name='formulario[1][pesquisa_formulario]' />
                                <input type='hidden' class='v-versao' name='formulario[1][versao]' value='1' />
                                <input type='hidden' class='v-formulario' name='formulario[1][formulario]' />
                                <input type='hidden' class='v-ativo' name='formulario[1][ativo]' value='t' />
                                1
                            </td>
                            <td class='tac'><i class='text-success fa fa-check'></i></td>
                            <td class='tac'>
                                <button type='button' title='Editar formulário' class='btn btn-info btn-small btn-editar-formulario' ><i class='fa fa-edit'></i></button>
                            </td>
                        </tr>
                    <?php
                    } else {
                        foreach ($formularios as $f) {
                        ?>
                             <tr>
                                <td class='tac'>
                                    <input type='hidden' class='v-id' name='formulario[<?=$f['versao']?>][pesquisa_formulario]' value='<?=$f['pesquisa_formulario']?>' />
                                    <input type='hidden' class='v-versao' name='formulario[<?=$f['versao']?>][versao]' value='<?=$f['versao']?>' />
                                    <input type='hidden' class='v-formulario' name='formulario[<?=$f['versao']?>][formulario]' value='<?=$f['formulario']?>' />
                                    <input type='hidden' class='v-ativo' name='formulario[<?=$f['versao']?>][ativo]' value='<?=($f['ativo'] == 't') ? 't' : 'f'?>' />
                                    <?=$f['versao']?>
                                </td>
                                <td class='tac'>
                                    <?php
                                    if ($f['ativo'] == 't') {
                                    ?>
                                        <i class='text-success fa fa-check'></i>
                                    <?php
                                    } else {
                                    ?>
                                        <i class='text-error fa fa-ban'></i>
                                    <?php
                                    }
                                    ?>
                                </td>
                                <td class='tac'>
                                    <?php
                                    if (!strlen($f['pesquisa_formulario'])) {
                                    ?>
                                        <button type='button' title='Editar formulário' class='btn btn-info btn-small btn-editar-formulario' ><i class='fa fa-edit'></i></button>
                                    <?php
                                    } else {
                                    ?>
                                        <button type='button' title='Visualizar formulário' class='btn btn-primary btn-small btn-visualizar-formulario' ><i class='fa fa-eye'></i></button>
                                    <?php
                                    }
                                    ?>
                                </td>
                            </tr>   
                        <?php
                        }
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr class='titulo_coluna'>
                        <th colspan='3 tac'>
                            <button type='button' class='btn btn-success btn-small btn-adicionar-versao' ><i class='fa fa-plus'></i> Adicionar nova versão</button>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <p>
        <br />
        <input type='hidden' id='btn_click' name='btn_acao' />
        <button class='btn' type='button' onclick='submitForm($(this).parents("form"));' ><i class='fa fa-save'></i> Gravar</button>
        <?php
        if (strlen(getValue('pesquisa')) > 0) {
        ?>
            <button class='btn btn-warning' type='button' onclick='window.location = "<?=$_SERVER["PHP_SELF"]?>";' ><i class='fa fa-eraser'></i> Limpar</button>
        <?php
        }
        ?>
    </p>
    <br />
</form>

<div class="modal hide fade" id="modal-editar-formulario">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <iframe src="pesquisa_satisfacao_iframe.php" id="iframe-editar-formulario" frameborder="0" style="width: 100%; height: 100%;" ></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success btn-salvar-alteracoes-formulario"><i class="fa fa-save"></i> Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>

<div class="modal hide fade" id="modal-visualizar-formulario">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <iframe src="pesquisa_satisfacao_iframe.php" id="iframe-visualizar-formulario" frameborder="0" style="width: 100%; height: 100%;" ></iframe>
            </div>
        </div>
    </div>
</div>

<div class="modal hide fade" id="modal-nova-versao">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Adicionar nova versão</h4>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>

<?php
$sql = "
    SELECT 
        pesquisa,
        descricao, 
        categoria, 
        ativo, 
        data_inicial, 
        data_final, 
        periodo_envio, 
        repeticao_automatica
    FROM tbl_pesquisa
    WHERE fabrica = {$login_fabrica}
    ORDER BY categoria, ativo DESC, data_input DESC
";
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
?>
    <table id='resultado' class='table table-striped table-bordered table-hover table-normal' >
        <thead>
            <tr class='titulo_coluna' >
                <th colspan='8' >Pesquisas de satisfação cadastradas</th>
            </tr>
            <tr class='titulo_coluna' >
                <th>Título</th>
                <th>Categoria</th>
                <th>Intervalo para envio</th>
                <th>Ativo</th>
                <th>Data inicial</th>
                <th>Data final</th>
                <th>Repetição automática</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($row = pg_fetch_object($res)) {
                switch ($row->categoria) {
                    case 'os':
                        $nome_categoria = "Ordem de Serviço";
                        break;
                    case 'posto_autorizado':
                        $nome_categoria = "Posto Autorizado";
                        break;
                    case 'callcenter':
                        $nome_categoria = "Callcenter";
                        break;
                    case 'callcenter_email':
                        $nome_categoria = "E-mail Callcenter";
                        break;
                    case 'os_email':
                        $nome_categoria = "E-mail Ordem de Serviço";
                        break;
                    case 'os_sms':
                        $nome_categoria = "SMS Ordem de Serviço";
                        break;
                    default:
                        $nome_categoria = "Posto Autorizado";
                        break;
                }
            ?>
                <tr>
                    <td><?=$row->descricao?></td>
                    <td><?=$nome_categoria;?></td>
                    <td class='tac'><?=$row->periodo_envio?></td>
                    <td class='tac'><?=($row->ativo == "t") ? "<img title='Ativo' src='imagens/status_verde.png' >" : "<img title='Inativo' src='imagens/status_vermelho.png' >" ?></td>
                    <td class='tac'>
                        <?php
                        if (!empty($row->data_inicial)) {
                            echo date('d/m/Y', strtotime($row->data_inicial));
                        }
                        ?>
                    </td>
                    <td class='tac'>
                        <?php
                        if (!empty($row->data_final)) {
                            echo date('d/m/Y', strtotime($row->data_final));
                        }
                        ?>
                    </td>
                    <td class='tac'>
                        <?php
                        if ($row->categoria == 'posto_autorizado') {
                            if ($row->repeticao_automatica == "t") {
                                echo "<img title='Repetição automática do envio da pesquisa de satisfação habilitado' src='imagens/status_verde.png' >";
                            } else {
                                echo "<img title='Repetição automática do envio da pesquisa de satisfação desabilitado' src='imagens/status_vermelho.png' >";
                            }
                        }
                        ?>
                    </td>
                    <td class='tac'>
                        <button type='button' class='btn btn-info' onclick='window.location = "<?="{$_SERVER["PHP_SELF"]}?pesquisa={$row->pesquisa}"?>";' title='Alterar' ><i class='fa fa-edit'></i></button>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
<?php
}
?>

<script>
var fabrica = <?php echo $login_fabrica;?>;
$('#categoria').on('change', function() {
    let c = $(this).val();

    if (c == 'os') {
        $('.versoes_div').show();
        $('.alert-posto').hide();
        $('.alert-callcenter').hide();
        $('.datas, .repeticao-automatica').fadeOut();
        if (fabrica == 175) {
            $('.alert-os').fadeIn();
        $('.email').show();
        }
    } else if (c == 'posto_autorizado') {
        $('.versoes_div').show();
        $('.alert-os').hide();
        $('.alert-callcenter').hide();
        $('.datas, .repeticao-automatica').fadeIn();
        $('.alert-posto').fadeIn();
        $('.email').show();

        if (fabrica == 35) {

            $("#info_email").hide();
        }
        
    } else if (c == 'callcenter_email') {
        $('.versoes_div').show();
        $('.alert-os').hide();
        $('.alert-posto').hide();
        $('.intervalo').show();
        $('.email').show();
        $('.datas, .repeticao-automatica').fadeIn();
        $('.alert-callcenter').fadeIn();
    } else if (c == 'os_email') {
        $('.versoes_div').show();
        $('.alert-os').hide();
        $('.alert-posto').hide();
        $('.intervalo').show();
        $('.email').show();
        $('.datas, .repeticao-automatica').fadeIn();
        $('.alert-callcenter').fadeOut();
        $('.alert-os').fadeIn();
    } else if (c == 'os_sms') {
        $('.intervalo').show();
        $('.versoes_div').hide();
        $('.email').show();
        $('.datas, .repeticao-automatica').hide();
        $('.alert-os').show();
        $('.alert-posto').hide();
        $('.alert-callcenter').hide();
    } else {
        $('.versoes_div').show();
        $('.intervalo').hide();
        $('.email').hide();
        $('.datas, .repeticao-automatica').hide();
        $('.alert-os').hide();
        $('.alert-posto').hide();
        $('.alert-callcenter').hide();
    }
});

$('#categoria').trigger('change');

$('#periodo_envio').numeric();

$('#data_inicial, #data_final').datepicker({ minDate: 0 });

$('.fa-calendar').on('click', function() {
    $(this).parent().prev().focus();
});

var iframeEditar     = document.querySelector('#iframe-editar-formulario');
var iframeVisualizar = document.querySelector('#iframe-visualizar-formulario');
var formularioVersao = 1;

$(document).on('click', '.btn-editar-formulario', function() {
    let formulario = $(this).parents('tr').find('.v-formulario').val();
    formularioVersao = $(this).parents('tr').find('.v-versao').val();
    
    $('#modal-editar-formulario').find('.modal-title').text('Edição do Formulário: Versão '+formularioVersao);
    $('#modal-editar-formulario').addClass('modal-full-screen').modal('show');
    
    if (formulario.length > 0) {
        iframeEditar.contentWindow.postMessage('setFbData|'+formulario, '*');
    } else {
        iframeEditar.contentWindow.postMessage('clearFbData', '*');
    }
});

$('.btn-salvar-alteracoes-formulario').on('click', function() {
    iframeEditar.contentWindow.postMessage('getFbData', '*');
});

$(document).on('click', '.btn-visualizar-formulario', function() {
    let formulario = $(this).parents('tr').find('.v-formulario').val();
    formularioVersao = $(this).parents('tr').find('.v-versao').val();
    
    $('#modal-visualizar-formulario').find('.modal-title').text('Visualização do Formulário: Versão '+formularioVersao);
    $('#modal-visualizar-formulario').addClass('modal-full-screen').modal('show');
    
    let form = {
        edit: true,
        title: 'Pesquisa de Satisfação',
        logo: '<?=$url_logo?>',
        noActions: true,
        data: formulario
    };

    iframeVisualizar.contentWindow.postMessage('viewFbForm|'+JSON.stringify(form), '*');
});

window.addEventListener('message', function(e) {
    [action, data] = e.data.split("|");
    
    if (action == 'getFbData') {
        $('input[name="formulario['+formularioVersao+'][formulario]"]').val(data);
        $('#modal-editar-formulario').modal('hide');
        e.source.postMessage('clearFbData', '*');
    }
}, false);

$('.btn-adicionar-versao').on('click', function() {
    $('#modal-nova-versao').find('.modal-body').html('\
        <table class="table table-bordered table-hover">\
            <tbody>\
                <tr>\
                    <th class="tac versao-selecionada" data-versao="em_branco" style="cursor: pointer;"><i class="fa fa-file"></i> Em branco</th>\
                </tr>\
            </tbody>\
        </table>\
    ');
    
    $('.versoes').find('tr').each(function() {
        var versao = $(this).find('.v-versao').val();
        
        $('#modal-nova-versao').find('tbody').append('\
            <tr>\
                <th class="tac versao-selecionada" data-versao="'+versao+'" style="cursor: pointer;"><i class="fa fa-clone"></i> Copiar a versão '+versao+'</th>\
            </tr>\
        ');
    });
    
    $('#modal-nova-versao').modal('show');
});

$(document).on('click', '.versao-selecionada', function() {
    let versao = $('.versoes').find('tr').length;
    versao += 1;
    
    $('.versoes').append("\
        <tr>\
            <td class='tac'>\
                <input type='hidden' class='v-id' name='formulario["+versao+"][pesquisa_formulario]' />\
                <input type='hidden' class='v-versao' name='formulario["+versao+"][versao]' value='"+versao+"' />\
                <input type='hidden' class='v-formulario' name='formulario["+versao+"][formulario]' />\
                <input type='hidden' class='v-ativo' name='formulario["+versao+"][ativo]' value='f' />\
                "+versao+"\
            </td>\
            <td class='tac'><i class='text-error fa fa-ban'></i></td>\
            <td class='tac'>\
                <button type='button' title='Editar formulário' class='btn btn-info btn-small btn-editar-formulario' ><i class='fa fa-edit'></i></button>\
            </td>\
        </tr>\
    ");
    
    let copiar = $(this).data('versao');
    
    if (copiar != 'em_branco') {
        $('input[name="formulario['+versao+'][formulario]"]').val($('input[name="formulario['+copiar+'][formulario]"]').val());
    }
    
    $('.versoes').find('.fa-times').parent().remove();
    
    let ultimaVersaoFormulario = $('.versoes').find('tr').last().find('.v-id').val();
    
    if (ultimaVersaoFormulario.length == 0) {
        $('.versoes').find('tr').last().find('td').last().append("\
            <button type='button' title='Deletar versão' class='btn btn-danger btn-small btn-deletar-versao' ><i class='fa fa-times'></i></button>\
        ");
    }
    
    $('#modal-nova-versao').modal('hide');
});

$(document).on('click', '.btn-deletar-versao', function() {
    $(this).parents('tr').remove();
    $('.versoes').find('.fa-times').parent().remove();
    
    if ($('.versoes').find('tr').length > 1) {
        var ultimaVersaoFormulario = $('.versoes').find('tr').last().find('.v-id').val();
        
        if (ultimaVersaoFormulario.length == 0) {
            $('.versoes').find('tr').last().find('td').last().append("\
                <button type='button' title='Deletar versão' class='btn btn-danger btn-small btn-deletar-versao' ><i class='fa fa-times'></i></button>\
            ");
        }
    }
});

$(document).on('click', '.fa-ban', function() {
    $(this).parents('tr').find('.v-ativo').val('t');
    $(this).removeClass('fa-ban text-error').addClass('fa-check text-success');
    
    $(this).parents('tr').prevAll().find('.v-ativo').val('f');
    $(this).parents('tr').prevAll().find('.fa-check').removeClass('fa-check text-success').addClass('fa-ban text-error');
    
    $(this).parents('tr').nextAll().find('.v-ativo').val('f');
    $(this).parents('tr').nextAll().find('.fa-check').removeClass('fa-check text-success').addClass('fa-ban text-error');
});

$(document).on('click', '.fa-check', function() {
    $(this).parents('tr').find('.v-ativo').val('f');
    $(this).removeClass('fa-check text-success').addClass('fa-ban text-error');
});

</script>

<?php
include 'rodape.php';
