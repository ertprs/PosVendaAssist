<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = 'cadastros';

include 'autentica_admin.php';

if ($_POST['btn_acao']) {
    $regioes = array(
        'Norte'        => "'AM', 'RR', 'AP', 'PA', 'TO', 'RO', 'AC'",
        'Nordeste'     => "'MA', 'PI', 'CE', 'RN', 'PE', 'PB', 'SE', 'AL', 'BA'",
        'Centro-Oeste' => "'MT', 'MS', 'GO'",
        'Sul'          => "'SP', 'RJ', 'ES', 'MG'",
        'Sudeste'      => "'PR', 'RS', 'SC'"
    );
    
    $data_inicial = $_POST['data_inicial'];
    $data_final   = $_POST['data_final'];
    $categoria    = $_POST['categoria'];
    $regiao       = $_POST['regiao'];
    $estado       = $_POST['estado'];
    $status       = $_POST['status'];
    $codigo_posto       = $_POST['codigo_posto'];
    if (empty($data_inicial)) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'data_inicial';
    } else {
        list($dia, $mes, $ano) = explode('/', $data_inicial);
        
        if (!strtotime("$ano-$mes-$dia")) {
            $msg_erro['msg'][]    = 'Data inicial inválida';
            $msg_erro['campos'][] = 'data_inicial';
        } else {
            $aux_data_inicial = "$ano-$mes-$dia";
        }
    }
    
    if (empty($data_final)) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'data_final';
    } else {
        list($dia, $mes, $ano) = explode('/', $data_final);
        
        if (!strtotime("$ano-$mes-$dia")) {
            $msg_erro['msg'][]    = 'Data final inválida';
            $msg_erro['campos'][] = 'data_final';
        } else {
            $aux_data_final = "$ano-$mes-$dia";
        }
    }
    
    if (isset($aux_data_inicial) && isset($aux_data_final)) {
        if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
            $msg_erro['msg'][]    = 'Data final não pode ser inferior a data inicial';
            $msg_erro['campos'][] = 'data_final';
            $msg_erro['campos'][] = 'data_inicial';
        } else if (strtotime($aux_data_inicial.'+ 6 months') < strtotime($aux_data_final)) {
            $msg_erro['msg'][]    = 'O intervalo máximo entre as datas é de 6 meses';
            $msg_erro['campos'][] = 'data_final';
            $msg_erro['campos'][] = 'data_inicial';
        }
    }
    
    if (empty($categoria)) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'categoria';
    }
    
    if (!empty($regiao)) {
        $regiao = $regioes[$regiao];
    }

    if (!empty($status)) {

        if ($categoria != "os_sms") {

            if ($status  == 'respondidos') {
                $whereStatus = "AND r.sem_resposta = 'f'";
            } elseif ($status  == 'nao_respondidos') {
                $whereStatus = "AND r.sem_resposta = 't'";
            }

        } else {

            if ($status  == 'respondidos') {
                $whereStatus = "AND sr.sms_resposta IS NOT NULL";
            } elseif ($status  == 'nao_respondidos') {
                $whereStatus = "AND sr.sms_resposta IS NULL";
            } 

        }

    } else {
        $msg_erro['campos'][] = 'status';
        $msg_erro['msg'][]    = 'Escolha o status da Pesquisa';
    }

    if (empty($msg_erro['msg'])) {

        if (in_array($categoria, ['os', 'os_email'])) {
            if (!empty($regiao)) {
                $whereRegiao = "AND o.consumidor_estado IN ({$regiao})";
            }
            
            if (!empty($estado)) {
                $whereEstado = "AND o.consumidor_estado = '{$estado}'";
            }
	    $condCategoria = " AND p.categoria='{$categoria}'";

	    if(in_array($login_fabrica, [138])){
		$condAtivo = " AND p.ativo IS TRUE ";
	    }

	    $sql = "
                SELECT r.sem_resposta, 
                r.txt_resposta, 
                r.pesquisa, pf.versao, 
                pf.formulario, p.descricao AS pesquisa_titulo, 
                o.sua_os, r.os, pf.pesquisa_formulario, o.data_abertura, r.data_input::date AS data_envio,
                o.data_fechamento, 
                o.consumidor_nome,
                paf.codigo_posto || ' - ' || pa.nome AS nome_posto,
		o.consumidor_estado, o.consumidor_cidade,
		o.hd_chamado AS atendimento
                FROM tbl_resposta r
                INNER JOIN tbl_os o ON o.os = r.os AND o.fabrica = {$login_fabrica}
                INNER JOIN tbl_posto_fabrica paf ON paf.posto = o.posto AND paf.fabrica = {$login_fabrica}
                INNER JOIN tbl_posto pa ON pa.posto = paf.posto 
                INNER JOIN tbl_pesquisa_formulario pf ON pf.pesquisa_formulario = r.pesquisa_formulario
                INNER JOIN tbl_pesquisa p ON p.pesquisa = r.pesquisa AND p.fabrica = {$login_fabrica} $condAtivo
                INNER JOIN tbl_posto tp ON tp.posto = o.posto
                WHERE (r.data_input BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59')
                {$whereRegiao}
                {$whereEstado}
                {$whereStatus}
                {$condCategoria}
                ORDER BY o.data_abertura DESC
            ";
        } else if ($categoria == 'posto_autorizado') {

            if (isset($_POST['codigo_posto']) && strlen($_POST['codigo_posto']) > 0) {
                $wherePosto = "AND p.codigo_posto = '{$codigo_posto}'";
            }

            if (!empty($regiao)) {
                $whereRegiao = "AND p.contato_estado IN ({$regiao})";
            }
            
            if (!empty($estado)) {
                $whereEstado = "AND p.contato_estado = '{$estado}'";
            }
            
            $sql = "
                SELECT r.resposta, r.sem_resposta, r.txt_resposta, r.pesquisa, pf.versao, pf.formulario, ps.descricao AS pesquisa_titulo, r.posto, p.codigo_posto, pst.nome AS nome_posto, pf.pesquisa_formulario, r.data_input::date AS data_envio, p.contato_cidade, p.contato_estado
                FROM tbl_resposta r
                INNER JOIN tbl_posto_fabrica p ON p.posto = r.posto AND p.fabrica = {$login_fabrica}
                INNER JOIN tbl_posto pst ON pst.posto = p.posto
                INNER JOIN tbl_pesquisa_formulario pf ON pf.pesquisa_formulario = r.pesquisa_formulario
                INNER JOIN tbl_pesquisa ps ON ps.pesquisa = r.pesquisa AND ps.fabrica = {$login_fabrica}
                WHERE (r.data_input BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59')
                {$whereRegiao}
                {$whereEstado}
                {$whereStatus}
                {$wherePosto}
                ORDER BY r.data_input DESC
            ";
        } else if (in_array($categoria, ['callcenter', 'callcenter_email'])) {
            if (!empty($regiao)) {
                $whereRegiao = "AND cid.estado IN ({$regiao})";
            }
            
            if (!empty($estado)) {
                $whereEstado = "AND cid.estado = '{$estado}'";
            }
            $condCategoria = " AND p.categoria='{$categoria}'";
            $sql = "
                SELECT r.sem_resposta, 
                       r.txt_resposta, 
                       r.pesquisa, 
                       pf.versao, 
                       pf.formulario,
                       p.descricao AS pesquisa_titulo, 
                       r.hd_chamado,
                       hde.nome AS consumidor_nome, 
                       pf.pesquisa_formulario, 
                       cid.nome AS consumidor_cidade,r.data_input::date AS data_envio,
                       cid.estado AS consumidor_estado,
                       hd.data AS data_abertura 
                 FROM tbl_resposta r
                 JOIN tbl_hd_chamado hd ON hd.hd_chamado = r.hd_chamado AND hd.fabrica = {$login_fabrica}
                 JOIN tbl_pesquisa_formulario pf ON pf.pesquisa_formulario = r.pesquisa_formulario
                 JOIN tbl_pesquisa p ON p.pesquisa = r.pesquisa AND p.fabrica = {$login_fabrica}
                 JOIN tbl_hd_chamado_extra hde ON hde.hd_chamado = r.hd_chamado
                 JOIN tbl_cidade cid ON cid.cidade = hde.cidade
                WHERE (r.data_input BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59')
                {$whereRegiao}
                {$whereEstado}
                {$condCategoria}
                {$whereStatus}
                ORDER BY r.data_input DESC
            ";
        } else if (in_array($categoria, ['os_sms'])) {

            if (!empty($regiao)) {
                $whereRegiao = "AND o.consumidor_estado IN ({$regiao})";
            }
            
            if (!empty($estado)) {
                $whereEstado = "AND o.consumidor_estado = '{$estado}'";
            }

              $sql = "  SELECT DISTINCT
                            pe.descricao                            AS pesquisa_titulo,
                            o.os,
                            o.consumidor_nome                       AS consumidor_nome,
                            o.consumidor_cidade                     AS consumidor_cidade,
                            TO_CHAR(r.data_input, 'dd/mm/yyyy')      AS data_envio,
                            o.consumidor_estado                     AS consumidor_estado,
                            TO_CHAR(o.data_abertura, 'dd/mm/yyyy')  AS data_abertura,
                            sr.resposta                             AS resposta_pesquisa,
                            o.consumidor_celular
                        FROM tbl_os o
                        INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
                        INNER JOIN tbl_posto p ON p.posto = pf.posto
                        INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
                        INNER JOIN tbl_sms s ON s.os = o.os AND s.origem = 'sms_pesquisa'
                        INNER JOIN tbl_resposta r ON o.os = r.os
                        INNER JOIN tbl_pesquisa pe ON r.pesquisa = pe.pesquisa AND pe.fabrica = {$login_fabrica}
                        AND pe.categoria = '{$categoria}'
                        LEFT  JOIN tbl_sms_resposta sr ON sr.sms = s.sms
                        WHERE o.fabrica = {$login_fabrica}
                        AND (r.data_input BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59')
                        {$whereRegiao}
                        {$whereEstado}
                        {$condCategoria}
                        {$whereStatus}
                        ORDER BY o.os";

        }
        
        $resSubmit = pg_query($con, $sql);

        if (!pg_num_rows($resSubmit)) {
            $msg_erro['msg'][] = 'Nenhum resultado encontrado';
        }
    }
}
               
$layout_menu = 'cadastros';
$title       = 'Relatório da Pesquisa de Satisfação';
$title_page  = 'Parâmetros de Pesquisa';

include 'cabecalho_new.php';

$plugins = array(
    'font_awesome',
    'datepicker',
    'select2',
    "shadowbox",
);
include 'plugin_loader.php';
?>

<style>
th.th_nps{
    background: #eeeeee !important;
    color: #000000 !important;
}
.fa-calendar {
    cursor: pointer;
}

.table-respostas {
    display: none;
    margin-top: 20px;
}

.table-respostas td {
    color: #000;
    font-weight: normal;
}

.table-respostas td.text-info {
    color: #3a87ad;
    font-weight: bold;
    font-size: 14px;
}

.tbody-filtro {
    display: none;
}
    
</style>

<?php
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

<form method='POST' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela' ><?=$title_page?></div>
    <br />

    <div class='row-fluid' >
        <div class='span2' ></div>
        <div class='span4' >
            <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='data_inicial' >Data inicial</label>
                <div class='controls controls-row' >
                    <div class='span12 input-append' >
                        <h5 class='asteristico'>*</h5>
                        <input type='text' id='data_inicial' name='data_inicial' class='span10' value='<?=getValue("data_inicial")?>' />
                        <span class='add-on' ><i class='fa fa-calendar' ></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class='span4' >
            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='data_final' >Data final</label>
                <div class='controls controls-row' >
                    <div class='span12 input-append' >
                        <h5 class='asteristico'>*</h5>
                        <input type='text' id='data_final' name='data_final' class='span10' value='<?=getValue("data_final")?>' />
                        <span class='add-on' ><i class='fa fa-calendar' ></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class='row-fluid' >
        <div class='span2' ></div>
        <div class='span5' >
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
        <div class='span3' >
            <div class='control-group' >
                <label class='control-label' for='status' >Status Pesquisas</label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <h5 class='asteristico'>*</h5>
                        <select id='status' name='status' class='span12' />
                            <option value='' >Selecione</option>
                            <?php
                            $options = array(
                                'respondidos' => 'Pesquisas Respondidas',
                                'nao_respondidos' => 'Pesquisas não Respondidas'
                            );

                            foreach ($options as $value => $label) {
                                $selected = (getValue('status') == $value) ? 'selected' : '';
                                echo "<option value='{$value}' {$selected} >{$label}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
     <div class='row-fluid mostra_posto' style="display: <?php echo ($categoria == "posto_autorizado") ? "": "none";?>" >
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
                    <div class='span8 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
   
    <div class='row-fluid' >
        <div class='span2' ></div>
        <div class='span4' >
            <div class='control-group <?=(in_array("regiao", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='regiao' >Região</label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <select id='regiao' name='regiao' class='span12' />
                            <option value='' >Selecione</option>
                            <?php
                            $options = array(
                                'Centro-Oeste',
                                'Nordeste',
                                'Norte',
                                'Sudeste',
                                'Sul'
                            );
                            foreach ($options as $value) {
                                $selected = (getValue('regiao') == $value) ? 'selected' : '';
                                echo "<option value='{$value}' {$selected} >{$value}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span4' >
            <div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='estado' >Estado</label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <select id='estado' name='estado' class='span12' />
                            <option value='' >Selecione</option>
                            <?php
                            $options = $array_estados();
                            foreach ($options as $value => $label) {
                                $selected = (getValue('estado') == $value) ? 'selected' : '';
                                echo "<option value='{$value}' {$selected} >{$label}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <p>
        <br />
        <input type='hidden' id='btn_click' name='btn_acao' />
        <button class='btn' type='button' onclick='submitForm($(this).parents("form"));' >Pesquisar</button>
    </p>
    <br />
</form>
</div>

<?php
if (pg_num_rows($resSubmit) > 0 && !in_array($categoria, ['os_sms'])) {
    $enviadas    = array( 'geral' => 0 );
    $respondidas = array( 'geral' => 0 );
    $pesquisas   = array();
    $formularios = array();
    $respostas   = array();
    $perguntas   = array();
    $os          = array();
    $callcenter  = array();
    $posto       = array();
    
    $json = array(
        'pesquisas' => array(),
        'respostas' => array(),
        'perguntas' => array(),
        'os'        => array(),
        'posto'     => array(),
        'callcenter' => array()
    );
    
    array_map(function($r) {
        global $enviadas, $respondidas, $pesquisas, $formularios, $respostas, $os, $posto, $categoria, $json, $callcenter;
        
        $pesquisas[$r['pesquisa']] = $r['pesquisa_titulo'];
        
        $formularios[$r['pesquisa']][$r['versao']] = json_decode(utf8_encode($r['formulario']), true);
        $json['pesquisas'][$r['pesquisa_formulario']] = $formularios[$r['pesquisa']][$r['versao']];
        $enviadas['geral']++;
        
        if (!array_key_exists($r['pesquisa'], $enviadas)) {
            $enviadas[$r['pesquisa']] = 0;
        }
        
        $enviadas[$r['pesquisa']]++;
        
        if ($r['sem_resposta'] == 'f') {
            $respondidas['geral']++;
            
            if (!array_key_exists($r['pesquisa'], $respondidas)) {
                $respondidas[$r['pesquisa']] = 0;
            }
            
            $respondidas[$r['pesquisa']]++;
        }

        $r['txt_resposta'] = (!mb_detect_encoding($r['txt_resposta'], 'utf-8', true)) ? utf8_encode($r['txt_resposta']) : $r['txt_resposta']; 
        $resposta = json_decode(($r['txt_resposta']), true);

        if (!array_key_exists($r['pesquisa'], $respostas)) {
            $respostas[$r['pesquisa']] = array();
        }
        
        foreach ($resposta as $key => $value) {

            $key = str_replace(["?","--"], "", utf8_decode($key));

            if (!array_key_exists($key, $respostas[$r['pesquisa']])) {
                $respostas[$r['pesquisa']][$key] = array();
            }

            if (is_array($value)) {
        		foreach ($value as $v) {
        			$respostas[$r['pesquisa']][$key][] = $v;
        		}
    	    } else {
                $respostas[$r['pesquisa']][$key][] = $value;
    	    }
        }
        
        
            if (in_array($categoria, ['os','os_email'])) {
                if (!array_key_exists($r['pesquisa'], $os)) {
                    $os[$r['pesquisa']] = array();
                }
                
                $os[$r['pesquisa']][$r['os']] = array(
			'sua_os'              => $r['sua_os'],
			'atendimento'	  => $r['atendimento'],
                    'nome'                => utf8_encode($r['nome_posto'] ),
                    'data_abertura'       => date('d/m/Y', strtotime($r['data_abertura'])),
                    'data_fechamento'     => date('d/m/Y', strtotime($r['data_fechamento'])),
                    'nome'                => $r['consumidor_nome'],
                    'nome_posto'          => $r['nome_posto'],
                    'estado'              => $r['consumidor_estado'],
                    'cidade'              => utf8_encode($r['consumidor_cidade']),
                    'pesquisa_formulario' => $r['pesquisa_formulario'],
                    'versao'              => $r['versao'],
                    'pesquisa'            => $r['pesquisa'],
                    'data_envio'            => date('d/m/Y', strtotime($r['data_envio'])),
                    'pesquisa_titulo'     => utf8_encode($r['pesquisa_titulo']),
                    'resposta'            => $resposta
                );
                $json['respostas'][$r['pesquisa']][$r['os']] = $resposta;
            } else if ($categoria == 'posto_autorizado') {
                if (!array_key_exists($r['pesquisa'], $posto)) {
                    $posto[$r['pesquisa']] = array();
                }
                
                if (!array_key_exists($r['data_envio'], $posto[$r['pesquisa']])) {
                    $posto[$r['pesquisa']][$r['data_envio']] = array();
                }
                
                $posto[$r['pesquisa']][$r['data_envio']][$r['resposta']] = array(
                    'posto'               => $r['posto'],
                    'codigo'              => $r['codigo_posto'],
                    'nome'                => utf8_encode($r['nome_posto']),
                    'estado'              => $r['contato_estado'],
                    'cidade'              => utf8_encode($r['contato_cidade']),
                    'pesquisa_formulario' => $r['pesquisa_formulario'],
                    'versao'              => $r['versao'],
                    'pesquisa'            => $r['pesquisa'],
                    'pesquisa_titulo'     => utf8_encode($r['pesquisa_titulo']),
                    'data_envio'          => date('d/m/Y', strtotime($r['data_envio'])),
                    'resposta'            => $resposta
                );
                $json['respostas'][$r['pesquisa']][$r['data_envio']][$r['resposta']] = $resposta;
            } else if (in_array($categoria, ['callcenter','callcenter_email'])) {

                if (!array_key_exists($r['pesquisa'], $callcenter)) {
                    $callcenter[$r['pesquisa']] = array();
                }
                
                $callcenter[$r['pesquisa']][$r['hd_chamado']] = array(
                    'hd_chamado'          => $r['hd_chamado'],
                    'data_abertura'       => date('d/m/Y', strtotime($r['data_abertura'])),
                    'nome'                => utf8_encode($r['consumidor_nome']),
                    'estado'              => $r['consumidor_estado'],
                    'cidade'              => utf8_encode($r['consumidor_cidade']),
                    'pesquisa_formulario' => $r['pesquisa_formulario'],
                    'versao'              => $r['versao'],
                    'pesquisa'            => $r['pesquisa'],
                    'pesquisa_titulo'     => utf8_encode($r['pesquisa_titulo']),
                    'resposta'            => $resposta
                );
                $json['respostas'][$r['pesquisa']][$r['hd_chamado']] = $resposta;
            } 

    }, pg_fetch_all($resSubmit));

    if (in_array($categoria, ['os', 'os_email'])) {
        $json['os'] = $os;
    } else if ($categoria == 'posto_autorizado') {
        $json['posto'] = $posto;
    } else if (in_array($categoria, ['callcenter', 'callcenter_email'])) {
        $json['callcenter'] = $callcenter;
    } 

    foreach ($formularios as $pesquisa => $versoes) {
        if (!array_key_exists($pesquisa, $perguntas)) {
            $perguntas[$pesquisa] = array();
        }
        
        foreach ($versoes as $formulario) {
            foreach($formulario as $pergunta) {

                if (!array_key_exists($pergunta['name'], $perguntas[$pesquisa])) {
                    $perguntas[$pesquisa][$pergunta['name']] = array(
                        'label'  => utf8_decode($pergunta['label']),
                        'type'   => $pergunta['type'],
                        'values' => array()
                    );
                }
                
                if (is_array($pergunta['values'])) {
                    foreach($pergunta['values'] as $valor) {
                        $perguntas[$pesquisa][$pergunta['name']]['values'][$valor['value']] = utf8_decode($valor['label']);
                    }
                    
                    if (array_key_exists('other', $pergunta) && $pergunta['other'] == 1) {
                        $perguntas[$pesquisa][$pergunta['name']]['values']['outro'] = 'Outro';
                    }
                    
                    $perguntas[$pesquisa][$pergunta['name']]['values'] = array_unique($perguntas[$pesquisa][$pergunta['name']]['values']);
                }
            }
        }
    }

    $json['perguntas'] = array_map_recursive('utf8_encode', $perguntas);
    
    
    if (in_array($categoria, ['os', 'os_email'])) {
        $csv = 'relatorio-pesquisa-satisfacao-ordem-servico-'.date('dmYHis').'.csv';
    } else if ($categoria == 'posto_autorizado') {
        $csv = 'relatorio-pesquisa-satisfacao-posto-autorizado-'.date('dmYHis').'.csv';
    } else if (in_array($categoria, ['callcenter', 'callcenter_email'])) {
        $csv = 'relatorio-pesquisa-satisfacao-callcenter-'.date('dmYHis').'.csv';
    } else if (in_array($categoria, ['os_sms'])) {
        $csv = 'relatorio-pesquisa-satisfacao-os-sms-'.date('dmYHis').'.csv';
    }
    if (in_array($categoria, ['os', 'os_email'])) {

        $arquivo_download = fopen("/tmp/{$csv}", 'w');
    
        $cols = "'order de serviço';";
        $cols .= "'posto autorizado';";
        if (!in_array($login_fabrica, [138])) $cols .= "'Consumidor';";
        $cols .= "'data de abertura';'data de fechamento';'cidade';'estado';'data da pesquisa';'pesquisa';'pergunta';'resposta';'opção outro'";
        $cols .= "\n";

	if($login_fabrica == 138){
		$cols = "ordem de serviço;atendimento;posto autorizado;data de abertura;data fechamento;cidade;estado;data resposta;pesquisa;";

		foreach ($os as $pesquisa => $os_array) {

			foreach ($os_array as $os_id => $data) {
				foreach ($data["resposta"] as $pergunta => $resp) {
			 		if (mb_detect_encoding($pergunta, 'utf-8', true)) {
                        $pergunta = utf8_decode($pergunta);
                        
                        if (mb_detect_encoding($pergunta, 'utf-8', true)) {
                            $pergunta = utf8_decode($pergunta);
                        }
                    }

					$pergunta = str_replace(["\n","\r",";"]," ",$pergunta);
                    $pergunta = utf8_decode(utf8_encode($pergunta));
					$cols .= $pergunta.";";
				}

				break;
			}

			break;
		}

		$cols .= "opção outro" ."\n";

		fwrite($arquivo_download, $cols);

		foreach ($os as $pesquisa => $os_array) {
			foreach ($os_array as $os_id => $data) {

				$outro = null;
				if (array_key_exists('other', $pergunta) && $pergunta['other'] == 1) {
					$outro = $data['resposta'][$pergunta['name'].'-other'];
				}
				$xconsumidor = "";
				if (!in_array($login_fabrica, [138])) {
					$xconsumidor = "'{$data["nome"]}';";
				}

                $pesquisa_titulo = utf8_decode($data["pesquisa_titulo"]);

                $data_query = "SELECT TO_CHAR(data_input, 'dd/mm/yyyy') AS data_resposta 
                               FROM tbl_resposta 
                               WHERE os = {$data['sua_os']}";
                $data_resposta = pg_query($con, $data_query);
                $data_resposta = pg_fetch_result($data_resposta, 0, 'data_resposta');

				fwrite($arquivo_download, "{$data["sua_os"]};{$data["atendimento"]};{$data["nome_posto"]};{$xconsumidor}".$data[data_abertura].";". $data[data_fechamento] .";".utf8_decode($data["cidade"]).";{$data["estado"]};".$data_resposta.";". $pesquisa_titulo.";");

				foreach ($data["resposta"] as $pergunta => $resp) {

                    if (mb_detect_encoding($resp, 'utf-8', true)) {
                        $resp = utf8_decode($resp);
                        
                        if (mb_detect_encoding($resp, 'utf-8', true)) {
                            $resp = utf8_decode($resp);
                        }
                    }

					$resp = str_replace(["\n","\r",";"]," ",$resp);
					#$resp =  html_entity_decode($resp, ENT_QUOTES, "UTF-8");
                    $resp = utf8_encode($resp);
					$resp = utf8_decode($resp);

					fwrite($arquivo_download,$resp.";");
				}

				fwrite($arquivo_download,utf8_decode($outro)."\n");


			}
		}

	} else if (in_array($login_fabrica, [35,157])) {

        $cols = "'ordem de serviço';'posto autorizado';'data de abertura';'data de fechamento';'cidade';'estado';'data da pesquisa';'pesquisa'";

        $count_col_maior = 0;

        foreach ($os as $pesquisa => $os_array) {
            foreach ($os_array as $os_id => $data) {
                
                $linha_arquivo .= "'{$data["sua_os"]}';'{$data["nome_posto"]}';{$xconsumidor}'".$data['data_abertura']."';'".$data['data_fechamento']."';'".utf8_decode($data["cidade"])."';'{$data["estado"]}';'".$data['data_envio']."';'{$data["pesquisa_titulo"]}'";

                foreach ($data["resposta"] as $perg => $resp) {
                    $data["resposta"][str_replace("-","",$perg)] = $resp;
                }

                $count_col = 0;
                foreach ($formularios[$data['pesquisa']][$data['versao']] as $pergunta) {

                    if (in_array("header", [utf8_decode($pergunta["type"])])) {
                        continue;
                    }

                    $resposta = $data['resposta'][str_replace("-","",$pergunta['name'])];

                    $count_col++;

                    if ($count_col > $count_col_maior) {

                        $cols .= ";'pergunta';'resposta'";

                        $count_col_maior = $count_col;
                    }

                    $linha_arquivo .= ";'".utf8_decode($pergunta["label"])."';'{$resposta}'";

                    $outro = null;
                    if (array_key_exists('other', $pergunta) && $pergunta['other'] == 1) {
                        $outro = $data['resposta'][$pergunta['name'].'-other'];
                        $cols .= ";opção outros";
                        $linha_arquivo .= ";'{$outro}'";
                    }

                }

                $linha_arquivo .= "\n";

            }
        }

        fwrite($arquivo_download, $cols."\n".$linha_arquivo);

    } else {

		fwrite($arquivo_download, $cols);

		foreach ($os as $pesquisa => $os_array) {
			foreach ($os_array as $os_id => $data) {
				foreach ($formularios[$data['pesquisa']][$data['versao']] as $pergunta) {
					$resposta = $data['resposta'][$pergunta['name']];

					$outro = null;
					if (array_key_exists('other', $pergunta) && $pergunta['other'] == 1) {
						$outro = $data['resposta'][$pergunta['name'].'-other'];
					}
					$xconsumidor = "";
					if (!in_array($login_fabrica, [138])) {
						$xconsumidor = "'{$data["nome"]}';";
					}

				fwrite($arquivo_download, "'{$data["sua_os"]}';'{$data["nome_posto"]}';{$xconsumidor}'".$data['data_abertura']."';'".$data['data_fechamento']."';'".utf8_decode($data["cidade"])."';'{$data["estado"]}';'".$data['data_envio']."';'{$data["pesquisa_titulo"]}';'".utf8_decode($pergunta["label"])."';'{$resposta}';'{$outro}'\n");
				}
			}
		}
	}

    } else if (in_array($categoria, ['callcenter', 'callcenter_email'])) {
        fwrite($arquivo_download, "'nº atendimento';'data ';'cidade';'estado';'pesquisa';'pergunta';'resposta';'opção outro'\n");
        foreach ($callcenter as $pesquisa => $os_array) {
            foreach ($os_array as $os_id => $data) {
                foreach ($formularios[$data['pesquisa']][$data['versao']] as $pergunta) {
                    $resposta = $data['resposta'][$pergunta['name']];
                    $outro = null;
                    if (array_key_exists('other', $pergunta) && $pergunta['other'] == 1) {
                        $outro = $data['resposta'][$pergunta['name'].'-other'];
                    }
                    if (is_array($resposta)) {
                        $resposta = implode(", ", $resposta);
                    }

                    if (in_array($login_fabrica, [138]))  {
                        $fields = "'{$data["sua_os"]}';";
                        if (in_array($login_fabrica, [138])) $fields .= "'{$data["nome"]}';";
                        $fields .= "'" . $data['data_abertura'] . "';";
                        $fields .= "'" . $data['data_fechamento'] . "';";
                        $fields .= "'" . utf8_encode($data["cidade"]) . "';";
                        $fields .= "'{$data["estado"]}';";
                        $fields .= "'" . $data['data_envio'] . "';";
                        $fields .= "'{$data["pesquisa_titulo"]}';";
                        $fields .= "'" . utf8_decode($pergunta["label"]) . "';";
                        $fields .= "'{$resposta}'";
                        $fields .= "'{$outro}'";
                        $fields .= "\n";

                        fwrite($arquivo_download, $fields);

                    } else {
                        fwrite($arquivo_download, "'{$data["hd_chamado"]}';'".$data['data_abertura']."';'".utf8_decode($data["cidade"])."';'{$data["estado"]}';'{$data["pesquisa_titulo"]}';'".utf8_decode($pergunta["label"])."';'{$resposta}';'{$outro}'\n");

                    }
                }
            }
        }
    } else if ($categoria == 'posto_autorizado') {
        fwrite($arquivo_download, "'código';'razão social';'cidade';'estado';'data da pesquisa';'pesquisa';'pergunta';'resposta'\n");
        
        foreach ($posto as $pesquisa => $datas) {
            foreach ($datas as $data_envio => $r) {
                foreach ($r as $resposta_id => $data) {
                    foreach ($formularios[$data['pesquisa']][$data['versao']] as $pergunta) {
                        $resposta = $data['resposta'][$pergunta['name']];
                        
                        $outro = null;
                        if (array_key_exists('other', $pergunta) && $pergunta['other'] == 1) {
                            $outro = $data['resposta'][$pergunta['name'].'-other'];
                        }
                    
                        fwrite($arquivo_download, "'{$data["codigo"]}';'{$data["nome"]}';'".utf8_decode($data["cidade"])."';'{$data["estado"]}';'". $data['data_envio'] . "';'{$data["pesquisa_titulo"]}';'".utf8_decode($pergunta["label"])."';'{$resposta}';'{$outro}'\n");
                    }
                }
            }
        }
    }
    
    fclose($arquivo_download);
    system("mv /tmp/{$csv} xls/{$csv}");
    ?>
    <table class="table table-bordered table-large table-center table-hover">
        <thead>
            <tr class='titulo_coluna'>
                <th colspan='4'>Pesquisas Enviadas x Recebidas</th>
            </tr>
            <tr class='titulo_coluna'>
                <th>Pesquisa</th>
                <th>Enviadas</th>
                <th>Respondidas</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($status == "nao_respondidos") {
                $label_botao_ver = "Ver detalhes";
            } else {
                $label_botao_ver = "Ver respostas";
            }
            foreach ($enviadas as $pesquisa => $value) {
                if ($pesquisa == 'geral') {
                    continue;
                }
                
                $value_respondidas = $respondidas[$pesquisa];
                ?>
                <tr>
                    <th><?=$pesquisas[$pesquisa]?></th>
                    <td class='tac'><?=$value?></td>
                    <td class='tac'><?=(($value_respondidas / $value) * 100)?>% (<?=$value_respondidas?>)</td>
                    <td class='tac'>
                        <button type="button" class="btn btn-info btn-small btn-ver-respostas" data-pesquisa='<?=$pesquisa?>'><i class='fa fa-question-circle'></i> <?php echo $label_botao_ver;?></button>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
        <tfoot>
            <tr class='titulo_coluna'>
                <th>Total</th>
                <th><?=$enviadas['geral']?></th>
                <th><?=(($respondidas['geral'] / $enviadas['geral']) * 100)?>% (<?=$respondidas['geral']?>)</th>
                <th>&nbsp;</th>
            </tr>
            <tr>
                <td colspan='4' class='tac' >
                    <?php
                    if (in_array($categoria, ['os', 'os_email']) &&  $status <> "nao_respondidos") {
                        $label_email_os = "";
                        if ($categoria == "os_email") {
                            $label_email_os = "E-mail";
                        } 
                    ?>
                        <button type="button" class="btn btn-success btn-download-csv" data-arquivo='<?=$csv?>'><i class="fa fa-file-excel"></i> Download arquivo CSV (Ordens de Serviço <?php echo $label_email_os;?> x Respostas)</button>
                    <?php
                    } else if ($categoria == 'posto_autorizado') {
                    ?>
                        <button type="button" class="btn btn-success btn-download-csv" data-arquivo='<?=$csv?>'><i class="fa fa-file-excel"></i> Download arquivo CSV (Postos Autorizados x Respostas)</button>
                    <?php    
                    } else if (in_array($categoria, ['callcenter', 'callcenter_email']) &&  $status <> "nao_respondidos") {
                        $label_email_callcenter = "";
                        if ($categoria == "callcenter_email") {
                            $label_email_callcenter = "E-mail";
                        } 
                    ?>
                        <button type="button" class="btn btn-success btn-download-csv" data-arquivo='<?=$csv?>'><i class="fa fa-file-excel"></i> Download arquivo CSV (Callcenter <?php echo $label_email_callcenter;?> x Respostas)</button>
                    <?php    
                    }
                    ?>
                </td>
            </tr>
        </tfoot>
    </table>
    
    <?php
    foreach ($pesquisas as $id => $titulo) {
    ?>
        <table id='respostas-pesquisa-<?=$id?>' class='table table-striped table-bordered table-large table-center table-respostas' >
            <thead>
                <tr>
                    <th colspan='2' ><?=$titulo?></th>
                </tr>
                <?php if ($status <> "nao_respondidos") {?>
                <tr class='titulo_coluna' >
                    <th colspan='2' >Respostas</th>
                </tr>
                <?php }?>
            </thead>
            <tbody>
                <?php
                if ($status <> "nao_respondidos") {
			foreach ($perguntas[$id] as $name => $pergunta) {

				$name = str_replace("--","",utf8_decode($name));
                    if (!in_array($pergunta['type'], array('select', 'checkbox-group', 'radio-group', 'starRating'))) {
                        continue;
                    }
                    ?>
                    <tr class='titulo_coluna'>
                        <th style='vertical-align: middle; width: 300px;'><?=$pergunta['label']?></th>
                        <?php
                        if ($pergunta['type'] == 'starRating') {
                        ?>
                            <td style='padding: 0px;'>
                                <table class='table table-bordered' style='margin-bottom: 0px; table-layout: fixed;'>
                                    <thead>
                                        <tr class='titulo_coluna'>
                                            <th><i class='fa fa-star'></i></th>
                                            <th>
                                                <i class='fa fa-star'></i>
                                                <i class='fa fa-star'></i>
                                            </th>
                                            <th>
                                                <i class='fa fa-star'></i>
                                                <i class='fa fa-star'></i>
                                                <i class='fa fa-star'></i>
                                            </th>
                                            <th>
                                                <i class='fa fa-star'></i>
                                                <i class='fa fa-star'></i>
                                                <i class='fa fa-star'></i>
                                                <i class='fa fa-star'></i>
                                            </th>
                                            <th>
                                                <i class='fa fa-star'></i>
                                                <i class='fa fa-star'></i>
                                                <i class='fa fa-star'></i>
                                                <i class='fa fa-star'></i>
                                                <i class='fa fa-star'></i>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $total    = count($respostas[$id][$name]);
                                        $estrelas = array(
                                            1 => 0,
                                            2 => 0,
                                            3 => 0,
                                            4 => 0,
                                            5 => 0
                                        );
                                        
                                        foreach ($respostas[$id][$name] as $resposta) {
                                            $estrelas[$resposta]++;
                                        }
                                        
                                        arsort($estrelas);
                                        $maior = key($estrelas);
                                        ksort($estrelas);
                                        ?>
                                        
                                        <tr>
                                            <?php
                                            foreach ($estrelas as $estrela => $qtde)  {
                                                $class = null;
                                                $p = ($qtde / $total) * 100;
                                                
                                                if ($estrela == $maior || $qtde == $estrelas[$maior]) {
                                                    $class = 'text-info';
                                                }
                                                
                                                if ($qtde > 0) {
                                                ?>
                                                    <td class='tac <?=$class?> td-filtrar' 
                                                        style='cursor: pointer;' 
                                                        data-pesquisa='<?=$id?>' 
                                                        data-pergunta='<?=$name?>'
                                                        data-resposta='<?=$estrela?>'
                                                        nowrap >
                                                        <?=number_format($p, 2, '.', '')?>% (<?=$qtde?>)
                                                    </td>
                                                <?php
                                                } else {
                                                ?>
                                                    <td class='tac <?=$class?>' nowrap><?=number_format($p, 2, '.', '')?>% (<?=$qtde?>)</td>
                                                <?php
                                                }
                                            }
                                            ?>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        <?php
                        } else {
                            if (strtolower($pergunta['label'])  == "nps") {
                        ?>
                            <td style='padding: 0px;'>
                                <table class='table table-bordered' style='margin-bottom: 0px;'>
                                    <thead>
                                        <tr class='titulo_coluna'>
                                            <?php
                                            foreach ($pergunta['values'] as $value_id => $value_label) {
                                            
                                                if ($value_id <= 6) {
                                                    $iconLabel = "fa-frown";
                                                    $iconColor = "#d90000";
                                                } else if ($value_id > 6 && $value_id <= 8) {
                                                    $iconLabel = "fa-meh";
                                                    $iconColor = "#f0ad4e";
                                                } else {
                                                    $iconLabel = "fa-smile";
                                                    $iconColor = "#5cb85c";
                                                }

                                            ?>
                                                <th class="th_nps">  
                                                    <?php echo "<p style='color: ".$iconColor.";font-size:17px;'><i class='fa ".$iconLabel."'></i></p>";?>
                                                    <?=$value_label?>
                                                </th>
                                            <?php
                                            }
                                            ?>
                                            <th class="th_nps" style="background-color:#d90000 !important;color: #fff !important;">TOTAL % DETRATORES</th>
                                            <th class="th_nps" style="background-color:#f0ad4e !important;color: #fff !important;">TOTAL % PASSIVOS</th>
                                            <th class="th_nps" style="background-color:#5cb85c !important;color: #fff !important;">TOTAL % PROMOTORES</th>
                                            <th class="th_nps">NPS SCORE</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <?php
                                            $total = count($respostas[$id][$name]);
                                            $r = array();

                                            foreach ($pergunta['values'] as $value_id => $value_label) {
                                                $r[$value_id] = 0;
                                            }

                                            foreach ($respostas[$id][$name] as $resposta) {
                                                $r[$resposta]++;
                                            }
                                            $maior = $r;
                                            arsort($maior);
                                            $maior = key($maior);
                                            $detratores = array();
                                            $neutros = array();
                                            $promotores = array();
                                            foreach ($pergunta['values'] as $value_id => $value_label) {
                                                $class = null;
                                                $p = ($r[$value_id] / $total) * 100;
                                                
                                                if ($value_id == $maior || $r[$value_id] == $r[$maior]) {
                                                    $class = 'text-info';
                                                }
                                                if ($value_id <= 6) {
                                                    $detratores[] = $r[$value_id];
                                                } else if ($value_id > 6 && $value_id <= 8) {
                                                    $neutros[] = $r[$value_id];
                                                } else {
                                                    $promotores[] = $r[$value_id];
                                                }
                                                if ($r[$value_id] > 0) {
                                                ?>
                                                    <td class='tac <?=$class?> td-filtrar' 
                                                        style='cursor: pointer;' 
                                                        data-pesquisa='<?=$id?>' 
                                                        data-pergunta='<?=$name?>'
                                                        data-resposta='<?=$value_id?>'
                                                        nowrap >

                                                        <?=number_format($p, 2, '.', '')?>% (<?=$r[$value_id]?>)
                                                    </td>
                                                <?php
                                                } else {
                                                ?>
                                                    <td class='tac <?=$class?>' nowrap><?=number_format($p, 2, '.', '')?>% (<?=$r[$value_id]?>)</td>
                                                <?php
                                                }
                                                ?>
                                            <?php
                                            }
                                                    $totalDetratores = array_sum($detratores);
                                                    $totalNeutros    = array_sum($neutros);
                                                    $totalPromotores = array_sum($promotores);
                                                   
                                                    $totalDetratoresPor = ($totalDetratores / $total) * 100 ;
                                                    $totalNeutrosPor    = ($totalNeutros / $total) * 100 ;
                                                    $totalPromotoresPor = ($totalPromotores / $total) * 100;

                                                    $mediaPD = $totalPromotores-$totalDetratores;
                                                    $score = ($mediaPD / $total) * 100;
                                                    
                                            ?>
                                            <td class="tac"> <?=number_format($totalDetratoresPor, 2, '.', '')?>%</td>
                                            <td class="tac"> <?=number_format($totalNeutrosPor, 2, '.', '')?>%</td>
                                            <td class="tac"> <?=number_format($totalPromotoresPor, 2, '.', '')?>%</td>
                                            <td class="tac"> <?=number_format($score, 2, '.', '')?>%</td>
                                            
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        <?php
                            } else {
                        ?>
                        <td style='padding: 0px;'>
                                <table class='table table-bordered' style='margin-bottom: 0px;'>
                                    <thead>
                                        <tr class='titulo_coluna'>
                                            <?php
                                            foreach ($pergunta['values'] as $value_id => $value_label) {
                                            ?>
                                                <th><?=$value_label?></th>
                                            <?php
                                            }
                                            ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <?php
                                            $total = count($respostas[$id][$name]);
                                            $r = array();

                                            foreach ($pergunta['values'] as $value_id => $value_label) {
                                                $r[$value_id] = 0;
                                            }

                                            foreach ($respostas[$id][$name] as $resposta) {
                                                $r[$resposta]++;
                                            }

                                            $maior = $r;
                                            arsort($maior);
                                            $maior = key($maior);
                                            
                                            foreach ($pergunta['values'] as $value_id => $value_label) {
                                                $class = null;
                                                $p = ($r[$value_id] / $total) * 100;
                                                $p = (is_nan($p)) ? 0 : $p; 
                                                if ($value_id == $maior || $r[$value_id] == $r[$maior]) {
                                                    $class = 'text-info';
                                                }
                                                if ($r[$value_id] > 0) {
                                                ?>
                                                    <td class='tac <?=$class?> td-filtrar' 
                                                        style='cursor: pointer;' 
                                                        data-pesquisa='<?=$id?>' 
                                                        data-pergunta='<?=$name?>'
                                                        data-resposta='<?=$value_id?>'
                                                        nowrap >
                                                        <?=number_format($p, 2, '.', '')?>% (<?=$r[$value_id]?>)
                                                    </td>
                                                <?php
                                                } else {
                                                ?>
                                                    <td class='tac <?=$class?>' nowrap><?=number_format($p, 2, '.', '')?>% (<?=$r[$value_id]?>)</td>
                                                <?php
                                                }
                                                ?>
                                            <?php
                                            }
                                            ?>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        <?php
                            }
                        }
                        ?>
                    </tr>
                <?php
                }
                }
                ?>
            </tbody>
            <tfoot>
                <tr class="titulo_coluna">
                    <?php
                    if ($categoria == 'os') {
                    ?>
                        <th colspan='2'>Ordens de Serviço</th>
                    <?php    
                    } else if ($categoria == 'posto_autorizado') {
                    ?>
                        <th colspan='2'>Postos Autorizados</th>
                    <?php    
                    } else if ($categoria == 'callcenter') {
                    ?>
                        <th colspan='2'>Callcenter</th>
                    <?php    
                    } else if ($categoria == 'callcenter_email') {
                    ?>
                        <th colspan='2'>Callcenter - E-mail</th>
                    <?php
                    }
                    ?>
                </tr>
                <tr>
                    <td colspan='2' style='padding: 0px;'>
                        <table class='table table-bordered' style='margin-bottom: 0px;'>
                            <thead>
                                <tr class='titulo_coluna'>
                                    <?php
                                    if (in_array($categoria, ['os', 'os_email'])) {
                                    ?>
                                        <th>Ordem de serviço</th>
                                        <th>Posto Autorizado</th>
                                        <?php if (!in_array($login_fabrica, [138])) { ?>
                                        <th>Consumidor</th>
                                        <?php } ?>
                                        <th>Data de abertura</th>
                                        <th>Data de fechamento</th>
                                    <?php    
                                    } else if ($categoria == 'posto_autorizado') {
                                    ?>
                                        <th>Código</th>
                                        <th>Razão social</th>
                                     <?php    
                                    } else if (in_array($categoria, ['callcenter', 'callcenter_email'])) {
                                    ?>
                                        <th>Nº Atendimento</th>
                                        <th>Consumidor</th>
                                    <?php
                                    }
                                    ?>
                                    <th>Cidade</th>
                                    <th>Estado</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody class='tbody-lista'>
                                <?php
                                if (in_array($categoria, ['os', 'os_email'])) {
                                    foreach ($os[$id] as $os_id => $data) {
                                    ?>
                                        <tr>
                                            <td class='tac'>
                                                <a href='os_press.php?os=<?=$os_id?>' target='_blank'><?=$data['sua_os']?></a>
                                            </td>
                                            <td class='tal'><?=$data['nome_posto']?></td>
                                            <?php if (!in_array($login_fabrica, [138])) { ?>
                                            <td class='tal'><?=$data['nome']?></td>
                                            <?php } ?>
                                            <td class='tac'><?=$data['data_abertura']?></td>
                                            <td class='tac'><?=$data['data_fechamento']?></td>
                                            <td class='tac'><?=$data['cidade']?></td>
                                            <td class='tac'><?=$data['estado']?></td>
                                            <td class='tac'>
                                                <button type="button" class="btn btn-info btn-small btn-resposta" data-nome='<?=$data['nome']?>' data-tipo='<?=$categoria?>' data-pesquisa='<?=$data["pesquisa"]?>' data-pesquisa-formulario='<?=$data["pesquisa_formulario"]?>' data-os='<?=$os_id?>' data-sua-os='<?=$data["sua_os"]?>' data-pesquisa-titulo='<?=$titulo?>'><i class='fa fa-question-circle'></i> Ver resposta</button>
                                            </td>
                                        </tr>
                                    <?php
                                    }
                                } else if ($categoria == 'posto_autorizado') {
                                    foreach ($posto[$id] as $data_envio => $resposta) {
                                        foreach ($resposta as $resposta_id => $data) {
                                        ?>
                                            <tr>
                                                <td class='tac'><?=$data['codigo']?></td>
                                                <td><?=$data['nome']?></td>
                                                <td class='tac'><?=$data['cidade']?></td>
                                                <td class='tac'><?=$data['estado']?></td>
                                                <td class='tac'>
                                                    <button type="button" class="btn btn-info btn-small btn-resposta" data-resposta='<?=$resposta_id?>' data-data-envio='<?=$data_envio?>' data-pesquisa='<?=$data["pesquisa"]?>' data-pesquisa-formulario='<?=$data["pesquisa_formulario"]?>' data-codigo='<?=$data["codigo"]?>' data-nome='<?=$data["nome"]?>' data-pesquisa-titulo='<?=$titulo?>'><i class='fa fa-question-circle'></i> Ver resposta</button>
                                                </td>
                                            </tr>
                                        <?php
                                        }
                                    }
                                } else if (in_array($categoria, ['callcenter', 'callcenter_email'])) {

                                    foreach ($callcenter[$id] as $hd_chamado => $resposta) {
                                        ?>
                                            <tr>
                                                <td class='tac'>
                                                    <?php
                                                        if ($status == "nao_respondidos") {
                                                            echo "<a href='callcenter_interativo_new.php?callcenter=".$resposta['hd_chamado']."' target='_blank'>".$resposta['hd_chamado']."</a>";
                                                            $botal_ver_resposta = '';
                                                        }  else {
                                                            echo $resposta['hd_chamado'];
                                                            $botal_ver_resposta = '<button type="button" class="btn btn-info btn-small btn-resposta" data-nome="'.$resposta['nome'].'" data-pesquisa="'.$resposta["pesquisa"].'" data-pesquisa-formulario="'.$resposta["pesquisa_formulario"].'" data-hd="'.$resposta["hd_chamado"].'" data-resposta="'.$resposta['resposta'].'"
                                                        data-pesquisa-titulo="'.$titulo.'"><i class="fa fa-question-circle"></i> Ver resposta</button>';
                                                        }
                                                    ?>
                                                </td>
                                                <td><?=$resposta['nome']?></td>
                                                <td class='tac'><?=$resposta['cidade']?></td>
                                                <td class='tac'><?=$resposta['estado']?></td>
                                                <td class='tac'>
                                                    <?php echo $botal_ver_resposta;?>
                                                </td>
                                            </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                            <tbody class='tbody-filtro' ></tbody>
                        </table>
                    </td>
                </tr>
            </tfoot>
        </table>
    <?php
    }
    ?>
    <div class="modal hide fade" id="modal-resposta">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"></h4>
                </div>
                <div class="modal-body" style='max-height: 90%;'></div>
            </div>
        </div>
    </div>
<?php
} else if (in_array($categoria, ['os_sms']) && pg_num_rows($resSubmit) > 0) { 

    $csv              = 'relatorio-pesquisa-satisfacao-os-sms-'.date('dmYHis').'.csv';
    $arquivo_download = fopen("/tmp/{$csv}", 'w');

    fwrite($arquivo_download, "'Ordem de serviço';'Data Abertura';'Consumidor';'Celular';'Cidade';'Estado';'Pesquisa';'Resposta'\n");

    ?>
    <table class="table table-bordered">
        <thead>
            <tr class="titulo_tabela">
                <th colspan="100%">Lista respostas SMS</th>
            </tr>
            <tr class="titulo_coluna">
                <th>Ordem de serviço</th>
                <th>Data abertura</th>
                <th>Consumidor</th>
                <th>Celular</th>
                <th>Cidade</th>
                <th>Estado</th>
                <th>Pesquisa</th>
                <th>Resposta</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($dadosSms = pg_fetch_assoc($resSubmit)) { 

                fwrite($arquivo_download, "'{$dadosSms['os']}';'{$dadosSms['data_abertura']}';'{$dadosSms['consumidor_nome']}';'{$dadosSms['consumidor_celular']}';'{$dadosSms['consumidor_cidade']}';'{$dadosSms['consumidor_estado']}';'{$dadosSms['pesquisa_titulo']}';'{$dadosSms['resposta_pesquisa']}'\n");

                ?>
                <tr>
                    <td class="tac">
                        <a href="os_press.php?os=<?= $dadosSms['os'] ?>" target="_blank"><?= $dadosSms['os'] ?></a>
                    </td>
                    <td class="tac"><?= $dadosSms['data_abertura'] ?></td>
                    <td><?= $dadosSms['consumidor_nome'] ?></td>
                    <td class="tac"><?= $dadosSms['consumidor_celular'] ?></td>
                    <td><?= $dadosSms['consumidor_cidade'] ?></td>
                    <td class="tac"><?= $dadosSms['consumidor_estado'] ?></td>
                    <td ><?= $dadosSms['pesquisa_titulo'] ?></td>
                    <td><?= $dadosSms['resposta_pesquisa'] ?></td>
                </tr>
            <?php
            }

            fclose($arquivo_download);
            system("mv /tmp/{$csv} xls/{$csv}");

            ?>
        </tbody>
    </table>
    <center>
        <button type="button" class="btn btn-success btn-download-csv" data-arquivo='<?=$csv?>'><i class="fa fa-file-excel"></i> Download arquivo CSV</button>
    </center>
<?php

}
?>

<script>
    Shadowbox.init();
    $.autocompleteLoad(Array("posto"));
    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}
$('#data_inicial, #data_final').datepicker({ maxDate: 0 });

$('.fa-calendar').on('click', function() {
   $(this).parent().prev().focus();
});

$('#regiao, #estado').select2();

$('#regiao').on('change', function() {
    let value = $(this).val();
    
    if (value.length > 0) {
        $('#estado').val('').trigger('change');
    }
});

$('#estado').on('change', function() {
    let value = $(this).val();
    
    if (value.length > 0) {
        $('#regiao').val('').trigger('change');
    }
});




$('select[name=categoria]').on('change', function() {
    let value = $(this).val();
    if (value == "posto_autorizado") {
        $(".mostra_posto").show()
    } else {
        $(".mostra_posto").hide()
    }
    
});


<?php
if (pg_num_rows($resSubmit) > 0) {
?>
    $('.btn-ver-respostas').on('click', function() {
        let pesquisa = $(this).data('pesquisa');
        
        $('.table-respostas').hide();
        $('#respostas-pesquisa-'+pesquisa).fadeIn();
        $(window).scrollTop($('#respostas-pesquisa-'+pesquisa).offset().top);
    });

    var pesquisas = <?=json_encode($json['pesquisas'])?>;
    var respostas = <?=json_encode($json['respostas'])?>;
    
    <?php
    if (in_array($categoria, ['os', 'os_email'])) {
    ?>
        var os = <?=json_encode($json['os'])?>;
    <?php  
    } else if ($categoria == 'posto_autorizado') {
    ?>
        var posto = <?=json_encode($json['posto'])?>;
    
    <?php  
    } else if (in_array($categoria, ['callcenter', 'callcenter_email'])) {
    ?>
        var callcenter = <?=json_encode($json['callcenter'])?>;
    <?php
    }
    ?>
    
    var perguntas = <?=json_encode($json['perguntas'])?>;
    
    $(document).on('click', '.btn-resposta', function() {
        $('#modal-resposta').addClass('modal-full-screen').modal('show');
        
        let pesquisa            = $(this).data('pesquisa');
        let pesquisa_formulario = $(this).data('pesquisa-formulario');

        <?php
        if (in_array($categoria, ['os', 'os_email'])) {
        ?>
            let os     = $(this).data('os');
            let sua_os = $(this).data('sua-os');
            let nome      = $(this).data('nome');
        <?php
        } else if ($categoria == 'posto_autorizado') {
        ?>
            let resposta  = $(this).data('resposta');
            let codigo    = $(this).data('codigo');
            let nome      = $(this).data('nome');
            let dataEnvio = $(this).data('data-envio');
       
        <?php
        } else if (in_array($categoria, ['callcenter', 'callcenter_email'])) {
        ?>
            let nome      = $(this).data('nome');
            let hd_chamado = $(this).data('hd');
            let xpesquisa            = $(this).data('pesquisa');
        <?php
        }
        ?>
       
        let pesquisa_titulo = $(this).data('pesquisa-titulo');
        
        let iframe = $('<iframe></iframe>', { 
            src: 'pesquisa_satisfacao_iframe.php?readonly=true', 
            css: {
                height: '100%',
                width: '100%'
            }
        });
        
        $(iframe).on('load', function(e) {
          
            e.target.contentWindow.postMessage('setFbData|'+JSON.stringify(pesquisas[pesquisa_formulario]), '*');
            let data = {
                edit: false,
                title: pesquisa_titulo,
                logo: '<?=$url_logo?>',
                <?php
                if (in_array($categoria, ['os', 'os_email'])) {
                ?>
                    formData: respostas[pesquisa][os],
                <?php
                } else if ($categoria == 'posto_autorizado') {
                ?>
                    formData: respostas[pesquisa][dataEnvio][resposta],
                <?php
                } else if (in_array($categoria, ['callcenter', 'callcenter_email'])) {
                ?>
                    formData: respostas[xpesquisa][hd_chamado],
                <?php
                }
                ?>
                noActions: true
            };
            e.target.contentWindow.postMessage('toggleFbEdit|'+JSON.stringify(data), '*');
        });

        <?php
        if (in_array($categoria, ['callcenter', 'callcenter_email'])) {
        ?>
            $('#modal-resposta').find('.modal-title').text('Resposta do Consumidor '+hd_chamado+' - '+nome);
        <?php
        } elseif (in_array($categoria, ['os', 'os_email'])) {
        ?>
            $('#modal-resposta').find('.modal-title').text('Resposta do Consumidor '+os+' - '+nome);
        <?php
        } else {
        ?>
        $('#modal-resposta').find('.modal-title').text('Resposta do Posto Autorizado '+codigo+' - '+nome);
        <?php
        } 
        ?>

        $('#modal-resposta').find('.modal-body').html(iframe);
        $('#modal-resposta').find('.modal-body').css({ overflow: 'hidden' });
    });
    
    $('.td-filtrar').on('click', function() {
        let pesquisa    = $(this).data('pesquisa');
        let pergunta    = $(this).data('pergunta');
        let resposta    = $(this).data('resposta');
        let table       = $('#respostas-pesquisa-'+pesquisa);
        let tbodyLista  = $(table).find('.tbody-lista');
        let tbodyFiltro = $(table).find('.tbody-filtro');
        let filtro      = [];
       
        <?php
        if ($categoria == 'os') {
        ?>
            $.each(os[pesquisa], function(os, data) {
                let r = respostas[pesquisa][os];
                
                if (typeof r[pergunta] != 'undefined' && r[pergunta] == resposta) {
                    data['os'] = os;
                    filtro.push(data);
                }
            });
        <?php
        } else if ($categoria == 'posto_autorizado') {
        ?>
            $.each(posto[pesquisa], function(dataEnvio, i) {
                $.each(i, function(resposta_id, data) {
                    let r = respostas[pesquisa][dataEnvio][resposta_id];
                    
                    if (typeof r[pergunta] != 'undefined' && r[pergunta] == resposta) {
                        data['resposta']  = resposta_id;
                        data['dataEnvio'] = dataEnvio;
                        filtro.push(data);
                    }
                });
            });
         <?php
        } else if (in_array($categoria, ['callcenter', 'callcenter_email'])) {
        ?>
            $.each(callcenter[pesquisa], function(hd, data) {
                let r = respostas[pesquisa][hd];
                
                if (typeof r[pergunta] != 'undefined' && r[pergunta] == resposta) {
                    data['hd'] = data;
                    filtro.push(data);
                }
            });
        <?php
        }
        ?>
        
        $(tbodyLista).hide();
        $(tbodyFiltro).html('');
        
        if (perguntas[pesquisa][pergunta]['type'] == 'starRating') {
            $(tbodyFiltro).append('\
                <tr class="warning">\
                    <td class="tac" colspan=10">Filtro ativo: pergunta "'+perguntas[pesquisa][pergunta]['label']+'", resposta "'+'<i class="fa fa-star"></i>'.repeat(resposta)+'"</td>\
                </tr>\
            ');
        } else {
            $(tbodyFiltro).append('\
                <tr class="warning">\
                    <td class="tac" colspan="10">Filtro ativo: pergunta "'+perguntas[pesquisa][pergunta]['label']+'", resposta "'+perguntas[pesquisa][pergunta]['values'][resposta]+'"</td>\
                </tr>\
            ');
        }
        
        $(tbodyFiltro).append('\
            <tr class="warning">\
                <td class="tac" colspan="10">\
                    <button type="button" class="btn btn-warning btn-small btn-limpar-filtro" data-pesquisa="'+pesquisa+'"><i class="fa fa-times"></i> Limpar filtro</button>\
                </td>\
            </tr>\
        ');
        
        var colPosto = '';
        
        <?php
        if ($categoria == 'os') {
        ?>
            filtro.forEach(function(os, i) {
                <?php if (in_array($login_fabrica, [138])) { ?>
                colPosto = "<td class='tac'>" + os.nome + "</td>";
                <?php } ?>
                $(tbodyFiltro).append('\
                    <tr>\
                        <td class="tac">\
                            <a href="os_press.php?os='+os.os+'" target="_blank">'+os.sua_os+'</a>\
                        </td>\
                        ' + colPosto + '\
                        <td class="tac">'+os.data_abertura+'</td>\
                        <td class="tac">'+os.data_fechamento+'</td>\
                        <td class="tac">'+os.cidade+'</td>\
                        <td class="tac">'+os.estado+'</td>\
                        <td class="tac">\
                            <button type="button" class="btn btn-info btn-small btn-resposta" data-pesquisa="'+pesquisa+'" data-pesquisa-formulario="'+os.pesquisa_formulario+'" data-os="'+os.os+'" data-sua-os="'+os.sua_os+'" data-pesquisa-titulo="'+os.pesquisa_titulo+'"><i class="fa fa-question-circle"></i> Ver resposta</button>\
                        </td>\
                    </tr>\
                ');
            });
        <?php
        } else if ($categoria == 'posto_autorizado') {
        ?>
            filtro.forEach(function(posto, i) {
                $(tbodyFiltro).append('\
                    <tr>\
                        <td class="tac">'+posto.codigo+'</td>\
                        <td>'+posto.nome+'</td>\
                        <td class="tac">'+posto.cidade+'</td>\
                        <td class="tac">'+posto.estado+'</td>\
                        <td class="tac">\
                            <button type="button" class="btn btn-info btn-small btn-resposta" data-resposta="'+posto.resposta+'" data-data-envio="'+posto.dataEnvio+'" data-pesquisa="'+posto.pesquisa+'" data-pesquisa-formulario="'+posto.pesquisa_formulario+'" data-codigo="'+posto.codigo+'" data-nome="'+posto.nome+'" data-pesquisa-titulo="'+posto.pesquisa_titulo+'"><i class="fa fa-question-circle"></i> Ver resposta</button>\
                        </td>\
                    </tr>\
                ');
            });
        <?php
        }
        ?>
        
        $(tbodyFiltro).fadeIn();
        $(window).scrollTop($(tbodyFiltro).offset().top);
    });
    
    $(document).on('click', '.btn-limpar-filtro', function() {
        let pesquisa = $(this).data('pesquisa');
       
        let table       = $('#respostas-pesquisa-'+pesquisa);
        let tbodyLista  = $(table).find('.tbody-lista');
        let tbodyFiltro = $(table).find('.tbody-filtro');
        
        $(tbodyFiltro).hide();
        $(tbodyLista).fadeIn();
    });
    
    $('.btn-download-csv').on('click', function() {
        let arquivo = $(this).data('arquivo');
        
        window.open('xls/'+arquivo);
    });
<?php
}
?>

</script>

<br />

<?php
include 'rodape.php';
?>
