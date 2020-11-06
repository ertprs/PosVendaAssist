<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';

if (!defined('DEBUG'))
	// define('DEBUG', $_serverEnvironment === 'development');
	define('DEBUG', false);

// if (!in_array($_SERVER['SERVER_NAME'], array('local.telecontrol.com.br', 'devel.telecontrol.com.br', 'novodevel.telecontrol.com.br'))) {
	// Por enquanto apenas o Distrib pode acessar o relatório de LOG,
	// portanto, validamos se o login único que acessou tem permissão
	// para usar o módulo do Distrib.
	if (strpos($_SERVER['HTTP_REFERER'], 'distrib')) {
		include_once '../login_unico_autentica_usuario.php';
		if (!$acessa_distrib) {
			header(('HTTP/1.1 403 Forbidden'));
			die;
		}
	} else {
		$admin_privilegios="cadastros,gerencia,call_center";
		include_once 'autentica_admin.php';
	}
// }

include_once __DIR__ . DIRECTORY_SEPARATOR . '../class/AuditorLog.php';
header('Content-Type: text/html; charset=iso-8859-1');

$title = "RELATÓRIO DE LOG DE ALTERAÇÃO";
define('BI_BACK', (strpos($_SERVER['PHP_SELF'],'/bi/') == true)?'../':'');

function formatArrayItem($key, $val, $useHtml=false) {
	if (in_array($val, array('t', 'true', true), true)) {
		$val = $useHtml ? '<i class="icon-ok"></i>':'Sim';
	} else if (in_array($val, array('f', 'false', false), true)) {
		$val = $useHtml ? '<i class="icon-remove"></i>':'Não';
	// } else if (strpos($key, 'fone') || strpos($key, 'celu') !== false) {
	//     $val = phone_format($val);
	} else if ($useHtml and is_url($val)) {
		$val = createHTMLLink($val, $val, "target='_blank' class='azul_dn'");
	} else if ($useHtml and is_email($val)) {
		$val = "<a href='mailto:$val' target='_new'><i class='icon-envelope'></i> $val</a>";
    }else if (strpos(strtolower($key), 'senha') !== false) {
        $val = '<i>&lt;senha alterada&gt;</i>';
    } else if (strlen($val) > 60) {
        $text = substr($val, 0, 12) . '...' . substr($val, -12);
        $val = "<acronym title='$val'>$text</acronym>";
    } else if (in_array($val, array(null, 'null','NULL'), true))  // Os 'null' viram traço, pra não ficarem em branco
		#$val = ' &mdash; ';
        $val = '<i>&lt;vazio&gt;</i>';
	else if ($val === '')
        $val = '<i>&lt;vazio&gt;</i>';
    return $val;
}

$tabela           = preg_match('/^\w+$/', $_GET['parametro']) ? $_GET['parametro'] : null;
$id               = preg_match('/^(\d+\*)?\w+$/', $_GET['id']) ? $_GET['id'] : null;
$titulo           = preg_match('/^[^<>\/\$]*$/', $_GET['titulo']) ? $_GET['titulo'] : null;
$limit            = getPost('limit') ? : 50;
$esconder_coluna  = $_GET['esconder_coluna'];
$program_url = $_GET['program_url'];

$plugins = array(
	"dataTable"
);

$IPdev = array('novodevel' => '191.5.166.42','devel' => '54.232.125.171', 'localhost' => '127.0.0.1', 'telecontrol' => '191.5.166.42');

$LOG_template = array(
	'ext' => '<div class="fields %2$s">
	<span class="span" style="display:none"></span>%1$s
	</div>',
	'int' => "" .
		"<strong>%s: </strong>" .
		"<span>%s</span><br />".
		"",
	'CSS' => '.fields {
	  background: white;
	}
	.dl-item:nth-child(2n) {
	  background: whitesmoke;
	}
    td strong+span {margin-left: 1ex;}
	tr>th+th+th {width: 35%}'
);

/***************************************************************
 * Este array configura as regras para relatórios específicos: *
 * - Campos a serem ignorados                                  *
 * - `include` a serem executados futuramente                  *
 * - templates específicos do log (`$LOG_template`)            *
 * - `pk` nome da coluna chave quando não é [tbl_]nome_tabela  *
 * - etc.                                                      *
 ***************************************************************/

$configLog = array(
    'tbl_posto_fabrica' => array(
        'campos_chave' => array('linha'),
        'ignorar' => array('data_alteracao', 'admin'),
        'sql' => array(
            'tipo_posto' => array(
                'sql' => "SELECT descricao AS tipo_posto FROM tbl_tipo_posto WHERE fabrica = $1 and tipo_posto = $2",
                'filtro' => array('login_fabrica', 'val') // 'val' é o valor do campo com o nome da chave, neste caso 'tipo_posto'
            ),
            'admin_sap' => array(
                'sql' => "SELECT nome_completo FROM tbl_admin WHERE fabrica = $1 AND admin = $2",
                'filtro' => array('login_fabrica', 'val')
            ),
            'tx_administrativa' => array(
                'sql' => "SELECT CASE WHEN (($1 - 1.0) * 100) < 0 THEN 0 ELSE ($1 - 1.0) * 100 END",
                'filtro' => array('val')
            ),
        
            'distribuidor_sla' => array(
                'sql' => "SELECT 
                          ds.unidade_negocio || ' - ' || nome AS unidade_negocio
                          FROM tbl_unidade_negocio un 
                          JOIN tbl_distribuidor_sla ds on ds.unidade_negocio = un.codigo
                          WHERE fabrica = $1 AND distribuidor_sla = $2",
                'filtro' => array('login_fabrica', 'val')
            ),  
            'posto' => array(
                'sql' => "SELECT nome, fantasia AS nome_completo FROM tbl_posto WHERE posto = $1",
                'filtro' => array('val')
            ),          
        ),
        'join' => array('tbl_posto_linha', 'tbl_excecao_mobra','tbl_posto_grupo_cliente', 'tbl_posto_distribuidor_sla_default')
    ),
    'tbl_pedido' => array(
        'ignorar' => array('finalizado','admin'),
        'join' => array('tbl_pedido_item'),
        'campos_chave' => array('peca'),
        'sql' => array(
            'peca'  => array(
                'sql' => "SELECT referencia || ' - ' || descricao AS peca FROM tbl_peca WHERE peca= $1",
                'filtro' => array('val')
            )
        )
    ),
    'tbl_pedido_cancelado' => array(
        'campos_chave' => array('pedido','peca'),
        'sql' => array(
            'pedido'  => array(
                'sql' => "SELECT pedido FROM tbl_pedido_cancelado WHERE fabrica = $1 AND pedido = $2",
                'filtro' => array('login_fabrica','val')
            ),
            'peca'  => array(
                'sql' => "SELECT referencia || ' - ' || descricao AS peca FROM tbl_peca WHERE peca= $1",
                'filtro' => array('val')
            )
        )
    ),
    'tbl_programa_restrito' => array(
        'ignorar' => array('fabrica', 'programa'),
        'sql' => array(
            'admin' => array(
                'sql' => "SELECT nome_completo FROM tbl_admin WHERE fabrica = $1 AND admin = $2",
                'filtro' => array('login_fabrica', 'val')
            )
        )
    ),
    'tbl_os_item_pecas' => array(
        'sql' => array(
            'peca' => array(
                'sql' => "SELECT referencia || ' - ' || descricao AS peca FROM tbl_peca WHERE peca = $1",
                'filtro' => array('val')
            ),
        ),
    ),
    'tbl_os' => array(
        'ignorar' => array('data_modificacao'),
        'join' => array('tbl_os_produto','tbl_os_item','tbl_os_extra'),
        'sql' => array(
            'defeito_reclamado' => array(
                'sql' => "SELECT defeito_reclamado || ' - ' || descricao AS defeito_reclamado FROM tbl_defeito_reclamado WHERE defeito_reclamado = $1",
                'filtro' => array('val')
            ),
            'defeito_constatado' => array(
                'sql' => "SELECT defeito_constatado || ' - ' || descricao AS defeito_constatado FROM tbl_defeito_constatado WHERE defeito_constatado = $1",
                'filtro' => array('val')
            ),
            'servico_realizado' => array(
                'sql' => "SELECT servico_realizado || ' - ' || descricao AS servico_realizado FROM tbl_servico_realizado WHERE servico_realizado = $1",
                'filtro' => array('val')
            ),
            'tipo_atendimento' => array(
                'sql' => "SELECT tipo_atendimento || ' - ' || descricao AS tipo_atendimento FROM tbl_tipo_atendimento WHERE tipo_atendimento = $1",
                'filtro' => array('val')
            ),
            'tecnico' => array(
                'sql' => "SELECT tecnico || ' - ' || nome AS tecnico FROM tbl_tecnico WHERE tecnico = $1",
                'filtro' => array('val')
            ),
            'produto' => array(
                'sql' => "SELECT referencia || ' - ' || descricao AS produto FROM tbl_produto WHERE produto = $1",
                'filtro' => array('val')
            ),
            'status_checkpoint' => array(
                'sql' => "SELECT status_checkpoint || ' - ' || descricao AS status_checkpoint FROM tbl_status_checkpoint WHERE status_checkpoint = $1",
                'filtro' => array('val')
            ),
            'peca' => array(
                'sql' => "SELECT referencia || ' - ' || descricao AS peca FROM tbl_peca WHERE peca = $1",
                'filtro' => array('val')
            ),
            'revenda' => array(
                'sql' => "SELECT revenda || ' - ' || nome AS revenda FROM tbl_revenda WHERE revenda = $1",
                'filtro' => array('val')
            ),
            'pac' => array(
                'sql' => "SELECT CASE
                            WHEN pac = 'sem_rastreio' THEN 'Correios: Sem código de rastreio'
                            WHEN pac ~ 'balc.o'       THEN 'Retirada no balcão'
                            WHEN pac <> ''            THEN 'Correios: ' || pac
                            ELSE ''
                        END AS tipo_entrega FROM (SELECT $1::TEXT AS pac) AS os_extra",
                'filtro' => array('val'),
            ),
            'admin' => array(
                'sql' => "
                    SELECT  nome_completo
                    FROM    tbl_admin
                    WHERE   admin = $1",
                'filtro' => array('val')
            ),
            'admin_altera' => array(
                'sql' => "
                    SELECT  nome_completo
                    FROM    tbl_admin
                    WHERE   admin = $1",
                'filtro' => array('val')
            ),
            'status_os_ultimo' => array(    
                'filtro' => array('status_os_ultimo')
            ),
            'causa_defeito' => array(
                'sql' => "SELECT codigo || ' - ' || descricao AS causa_defeito FROM tbl_causa_defeito WHERE causa_defeito = $1",
                'filtro' => array('val')

            ),
            'solucao' => array(
                'sql' => "SELECT descricao from tbl_solucao where solucao = $1",
                'filtro' => array('val'),
            ),
        ),
    ),
    'tbl_os_item' => array(
        'ignorar' => array('admin'),
        'campos_chave' => array('peca', 'qtde','servico_realizado', 'defeito', 'devolucao_obrigatoria'),
        'sql' => array(
            'peca' => array(
                'sql' => "SELECT descricao AS peca FROM tbl_peca where peca = $1",
                "filtro" => array('val')
            ),
            'servico_realizado' => array(
                'sql' => "SELECT servico_realizado || ' - ' || descricao AS servico_realizado FROM tbl_servico_realizado WHERE servico_realizado = $1",
                'filtro' => array('val')
            ),
            'defeito' => array(
                'sql' => "SELECT descricao from tbl_defeito where fabrica = $2 and defeito = $1",
                'filtro' => array('val', 'login_fabrica')
            )
        )
    ),
    'tbl_os_produto' => array(
        'sql' => array(
            'produto' => array(
                'sql' => "SELECT referencia || ' - ' || descricao AS produto FROM tbl_produto WHERE produto = $1",
                'filtro' => array('val')
            ),
        )
    ),
    'tbl_tabela_item' => array(
        'ignorar' => array('data_input'),
        'campos_chave' => array('tabela','peca'),
        'sql' => array(
            'tabela' => array(
                'sql' => "SELECT sigla_tabela||'-'||descricao AS tabela FROM tbl_tabela where tabela = $1",
                "filtro" => array('val')
            ),
            'peca' => array(
                'sql' => "SELECT descricao AS peca FROM tbl_peca where peca = $1",
                "filtro" => array('val')
            )
        )
    ),
    'tbl_produto' => array(
        'ignorar' => array('data_atualizacao','referencia_pesquisa','referencia_fabrica', 'admin'),
        //'json' => array('valores_adicionais')
    ),
    'tbl_peca' => array(
        'ignorar' => array('data_atualizacao'),
        'sql' => array(
            'admin' => array(
                    'sql' => "
                        SELECT  nome_completo
                        FROM    tbl_admin
                        WHERE   admin = $1",
                    'filtro' => array('val')
                ),
            )
    ),
    'tbl_diagnostico' => array(
        'ignorar' => array('data_input', 'fabrica', 'admin', 'garantia'),
        'campos_chave' => (in_array($login_fabrica, array(50))) ? array('familia','defeito_reclamado','ativo') : array('familia', 'defeito_constatado'),
        'sql' => array(
            'mao_de_obra' => array(
                'sql' => "SELECT CASE WHEN $1 >= 0 THEN $1 || ',00' ELSE NULL END",
                'filtro' => array('val')
            ),
            'defeito_constatado' => array(
                'sql' => 'SELECT descricao FROM tbl_defeito_constatado WHERE defeito_constatado = $1 AND fabrica = $2',
                'filtro' => array('val', 'login_fabrica')
            ),
            'defeito_reclamado' => array(
                'sql' => 'SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado = $1 AND fabrica = $2;',
                'filtro' => array('val', 'login_fabrica')
            ),
            'familia' => array(
                'sql' => 'SELECT descricao FROM tbl_familia WHERE familia = $1 AND fabrica = $2;',
                'filtro' => array('val', 'login_fabrica')
            )
        )
    ),
    'tbl_peca_defeito' => array(
        'campos_chave' => array('peca', 'defeito', 'ativo'),
        'sql' => array(
            'defeito' => array(
                'sql' => "SELECT descricao from tbl_defeito where defeito  = $1 ",
                'filtro' => array('val')
            ),
            'peca' => array(
                'sql' => "SELECT referencia ||' - '|| descricao as descricao from tbl_peca where peca = $1 and fabrica = $2 ",
                'filtro' => array('val', 'login_fabrica')
            ),
        )
    ),
    'tbl_kit_peca' => array(
        'join' => array('tbl_kit_peca_produto','tbl_kit_peca_peca'),
        'campos_chave' => array('peca'),
        'sql' => array(
            'kit_peca' => array(
                'sql' => "SELECT referencia AS kit_peca FROM tbl_kit_peca WHERE kit_peca = $1",
                'filtro' => array('val')
            ),
            'produto' => array(
                'sql' => "SELECT referencia || ' - ' || descricao AS produto FROM tbl_produto WHERE produto = $1",
                'filtro' => array('val')
            ),
            'peca' => array(
                'sql' => "SELECT descricao AS peca FROM tbl_peca where peca = $1",
                "filtro" => array('val')
            )
        )
    ),
    'tbl_lista_basica' => array(
        'ignorar' => array('data_alteracao','data_input','fabrica'),
        'campos_chave' => array('peca'),
        'sql' => array(
            'produto' => array(
                'sql' => "SELECT referencia || ' - ' || descricao AS produto FROM tbl_produto WHERE produto = $1",
                'filtro' => array('val')
            ),
            'peca' => array(
                'sql' => "SELECT referencia || ' - ' || descricao AS peca FROM tbl_peca where peca = $1",
                "filtro" => array('val')
            ),
            'admin' => array(
                'sql' => "
                    SELECT  nome_completo
                    FROM    tbl_admin
                    WHERE   admin = $1",
                'filtro' => array('val')
            ),
        )
    ),
    'tbl_posto_condicao' => array(
        'ignorar' => array('tabela','visivel','acrescimo_financeiro','acrescimo_tabela_base','acrescimo_tabela_base_venda'),
        'join' => array('tbl_condicao'),
        'sql' => array(
            'condicao' => array(
                'sql' => "SELECT condicao || ' - ' || descricao AS condicao_descricao FROM tbl_condicao WHERE condicao = $1",
                'filtro' => array('val')
            )
        ),
    ),
    'tbl_black_posto_condicao' => array(
        'ignorar' => array('id_condicao', 'data'),
    ),
    'tbl_condicao' => array(
        'ignorar' => array('fabrica','tabela','parcelas','frete'),
    ),
    'tbl_posto_estoque_localizacao' => array(
        'campos_chave' => array('fabrica', 'referencia', 'qtde'),
    ),
    'tbl_admin_atendente_estado' => array(
        'campos_chave' => array('admin', 'estado', 'cidade'),
    ),
    'tbl_defeito_reclamado' => array(
        'ignorar' => array('fabrica'),
        'campos_chave' => array('descricao', 'codigo', 'ativo'),
    ),
    'tbl_programa_restrito' => array(
        'campos_chave' => array('programa'),
        'ignorar' => array('login_unico', 'fabrica'),
        'sql' => array(
            'admin' => array(
                'sql' => "SELECT login FROM tbl_admin WHERE admin = $1",
                'filtro' => array('val')
            )
        ),
    ),
    'tbl_peca_adicionais' => array(
         'campos_chave' => array('informacoes','parametros_adicionais','peso'),
         'sql' => array(
            'fabrica' => array(
                'sql' => "SELECT nome from tbl_fabrica where fabrica  = $1 ",
                'filtro' => array('val')
            ),
            'peca' => array(
                'sql' => "SELECT referencia ||' - '|| descricao as descricao from tbl_peca where peca = $1 and fabrica = $2 ",
                'filtro' => array('val', 'login_fabrica')
            ),
        ),
    ),
    'tbl_callcenter_email' => array(
        'ignorar' => array('fabrica', 'admin_cadastro', 'data_input'),
        'campos_chave' => array('email')
    ),
    'tbl_produto_troca_opcao' => array(
        'campos_chave' => array('produto', 'produto_opcao', 'kit'),
        'sql' => array(
            'produto' => array(
                'sql' => "SELECT referencia ||' - '|| descricao AS descricao FROM tbl_produto WHERE produto = $1",
                'filtro' => array('val')
            ),
            'produto_opcao' => array(
                'sql' => "SELECT referencia ||' - '|| descricao AS descricao FROM tbl_produto WHERE produto = $1",
                'filtro' => array('val')
            )
        )
    ),
    'tbl_pedido_item' => array(
        'campos_chave' => array('pedido', 'peca', 'qtde'),
        'ignorar' => array('preco', 'status_pedido', 'qtde_faturada', 'produto_locador', 'nota_fiscal_locador', 'data_nf_locador', 'qtde_cancelada', 'qtde_faturada_distribuidor', 'preco_base', 'acrescimo_financeiro', 'acrescimo_tabela_base', 'icms', 'troca_produto', 'tabela', 'data_item', 'ipi', 'estoque', 'obs', 'servico', 'serie_locador', 'defeito', 'pedido_item_atendido', 'condicao', 'peca_alternativa'),
        'sql' => array(
            'peca' => array(
                'sql' => "SELECT referencia ||' - '|| descricao AS descricao FROM tbl_peca WHERE peca = $1",
                'filtro' => array('val')
            )
        )
    ),
    'tbl_tipo_posto_condicao' => array(
        'campos_chave' => array('condicao'),
        'ignorar' => array('fabrica', 'data_input'),
        'sql' => array(
            'tipo_posto' => array(
                'sql' => "SELECT descricao FROM tbl_tipo_posto WHERE tipo_posto = $1",
                'filtro' => array('val')
            ),
            'condicao' => array(
                'sql' => "SELECT codigo_condicao ||' - '|| descricao AS descricao FROM tbl_condicao WHERE condicao = $1",
                'filtro' => array('val')
            )
        ),
    ),
    'tbl_fabrica_servico_diferenciado' => array(
        'campos_chave' => array('parametros_adicionais'),
    ),
    'tbl_hd_motivo_ligacao' => array('campos_chave' => array('hd_motivo_ligacao'))
);

try {
	$mostraTeste = isset($_serverEnvironment) and $_serverEnvironment == 'development';

    $configLog[$tabela]['json'][] = 'parametros_adicionais';

	$AuditorLog = new AuditorLog;
	$tabelas = (isset($configLog[$tabela]['join']))
		? array_merge((array)$tabela, $configLog[$tabela]['join'])
		: $tabela;


    $tables_array = array('tbl_diagnostico', 'tbl_defeito_reclamado', 'tbl_callcenter_email', 'tbl_admin_atendente_estado', 'tbl_depara','tbl_categoria_posto');

   $tables_array[] = 'tbl_numero_serie';

    if ($tabela == 'tbl_faq' && empty($id)) {
        $tables_array[] = 'tbl_faq';
    }

	if (in_array(strtolower($tabela), $tables_array)) {
		$res = $AuditorLog->getLog($tabelas, $login_fabrica, $limit);        
	} elseif (strpos($id, '*')) {
		$res = $AuditorLog->getLog($tabelas, $id, $limit);
	} else {        
		$res = $AuditorLog->getLog($tabelas, $login_fabrica."*".$id, $limit);
        //echo $tabelas." - ". $login_fabrica . " - " . $id . "<br>";
        //echo $limit."<br /><pre>"; print_r($res); echo "</pre>"; exit;        
	}

	if (!is_array($res)) {
		throw new Exception("Nenhum registro de LOG encontrado");
	}

	if (!array_key_exists(0, $res)) {
		$res = array(0 => array('data' => $res));
	}


    

	pg_prepare($con, 'nomeAdmin',      "SELECT login AS nome, nome_completo    FROM tbl_admin       WHERE fabrica = $1 AND admin = $2");
	pg_prepare($con, 'nomePosto',      "SELECT nome, fantasia AS nome_completo FROM tbl_posto       WHERE posto = $1");
	pg_prepare($con, 'nomeLoginUnico', "SELECT nome           AS nome_completo FROM tbl_login_unico WHERE login_unico = $1");

	if (array_key_exists($tabela, $configLog))
		extract($configLog[$tabela], EXTR_PREFIX_ALL, 'LOG');
	if (DEBUG === true)
		pecho("Entrou no TRY");

	// Prepara as consultas para usar depois
	if (isset($LOG_sql) and is_array($LOG_sql)) {
		foreach ($LOG_sql as $queryName => $queryStr) {
			if (!pg_prepare($con, $queryName, $queryStr['sql'])) {
				throw new Exception(
					"Erro na definição da consulta:<br />".
					"<h3>$queryName</h3>".
					"<code>$queryStr</code>".
					"<h3>Erro:</h3><pre>".pg_last_error($con).'</pre>'
				);
			}
		}
		$LOG_sql_keys = array_keys($LOG_sql);
	}

	if (count($LOG_campos_chave))
		AuditorLog::$campos_chave = $LOG_campos_chave;

	$dataTable = array();
	if (DEBUG === true)
		pre_echo($res, 'RESULTADOS');

	foreach ($res as $idx => $data) {

		//$data = $rec['data'];
		// Se o IP do registro é de desenvolvimento, mas o acesso é desde o ambiente de
		// produção, não mostra o registro.

        if (!empty($program_url) && strpos($data['program_url'], $program_url) === false) {
            continue;
        }

		$RegistroTeste = (substr($data['ip_access'], 0, 7) == '192.168' or in_array($data['ip_access'], $IPdev));

		if ($RegistroTeste and !$mostraTeste)
			continue;

		//$extraStyle = $develSrcData = $RegistroTeste ? ' alert' : '';

		$userType = $data['user_level'];

		if ($userType == 'login_unico') {
			$resUserName = pg_execute($con, 'nomeLoginUnico', (array)$data['user']);
		}

		if ($userType == 'posto') {
			$resUserName = pg_execute($con, 'nomePosto', (array)$data['user']);
		}

		if ($userType == 'admin') {
			$resUserName = pg_execute($con, 'nomeAdmin', array($login_fabrica, $data['user']));
		}

		list($login, $nome) = pg_fetch_array($resUserName);

		if(is_array($data)) {
    	   $Antes  = $data['content']['antes'];
	   	   $Depois = $data['content']['depois'];
		}
        // Unifica o formato do array: quando tem apenas um registro, a API não envolve o registro
        // dentro de um array, deixando os dados do registro no primeiro nível. Aqui adicionamos
        // um nível para usar apenas uma lógica de processamento.
        if (!array_key_exists(0, $Antes) && count($Antes)) {
            $Antes = array(0 => $Antes);
        }
        if (!array_key_exists(0, $Depois) && count($Depois)) {
            $Depois = array(0 => $Depois);            
        }

        $campo_tabela =  strlen($LOG_pk) ? $LOG_pk : str_replace('tbl_', '', $tabela);

        msort($Antes, $campo_tabela); msort($Depois, $campo_tabela);

        if (strlen($Depois[0]["mensagem"]) > 0) {
            $mensagem = true;
        } else {
            $mensagem = false;
        }
               
        $dadosLog = AuditorLog::verificaLog($Antes, $Depois, $LOG_ignorar, $mensagem, $tabela);

		if (isset($LOG_include) and !is_null($LOG_include)) {
			foreach ((array)$LOG_include as $filename) {
				include (__DIR__ . DIRECTORY_SEPARATOR . $filename);
			}
		}

		if (!is_array($dadosLog))
			continue;

		$Inserido = $Excluido = 0;

		if ($dadosLog['antes'][key($dadosLog['antes'])] == null) {
			$Inserido = 1;
		}
		if ($dadosLog['depois'][key($dadosLog['depois'])] == null) {
			$Excluido = 1;
        }

        if($telecontrol_distrib && $tabela == 'tbl_lista_basica'){
            if($Inserido){
                $tx = "Inserido";
            } else if ($Excluido){
                $tx = "Excluido";
            } else {
                $tx = "Alterado";
            }

            $dataTable[$idx] = array(
                traduz('usuario.nome') => $nome ? "$login ($nome)" : $login,
                traduz('data.horario') => is_date($data['created'], 'U', 'EUR'),
                traduz('acao') => $tx
            );
        }else{
            $dataTable[$idx] = array(
                traduz('usuario.nome') => $nome ? "$login ($nome)" : $login,
                traduz('data.horario') => is_date($data['created'], 'U', 'EUR')
            );
        }
        
        $dadosAntes = $dadosDepois = '';
        
		if($Inserido !== 1){
            
			foreach ($dadosLog['antes'] as $logItens) {
				$chave_primaria = 0;
				foreach ($logItens as $key=>$val) {
					if ($chave_primaria == 0 && $Excluido == 1) {
						$chave_primaria = 1;
                        if (is_numeric($val)) {
                            continue;
                        }
					}
					if (in_array(strtolower($key), $LOG_json)) {
						$parametros_adicionais = json_decode($val, true);
						foreach ($parametros_adicionais as $campo => $valor) {
							if (in_array($campo, array('bonificacoes'))) { //Campos para ignorar do parametros_adicionais
								continue;
							}
							// Se tem um SELECT para o campo, executa e altera o valor
							if ($LOG_sql_keys and in_array($campo, $LOG_sql_keys)) {
								$valor = pg_fetch_result(
									pg_execute(
										$con, $campo,
										compact($LOG_sql[$campo]['filtro'])
									), 0, 0
								) ? : $valor;
							}

							$campo = ucfirst(str_replace('_', ' ', $campo));
                            if ($login_fabrica == 1 && $campo == 'DadosAnteriores') {
                                continue;
                            }
							$valor = formatArrayItem($campo, $valor);
                            if (is_array($valor)) {
                                $xvalor = $valor;
                                $valor = "";
                                foreach ($xvalor as $y => $vl) {
                                    $xxcampo = ucfirst(str_replace('_', ' ', $y));
                                    $xxvalor = formatArrayItem($xxcampo, $vl);
                                    $valor .= sprintf($LOG_template['int'], $xxcampo, $xxvalor);
                                }
                           }
                            $dadosAntes .= sprintf($LOG_template['int'], $campo, $valor);
                            if ($Excluido == 1) {
                                $valor = formatArrayItem($campo, "");
                                $dadosDepois .= sprintf($LOG_template['int'], $campo, $valor);
                            }
                        }
					} else {

						// Se tem um SELECT para o campo, executa e altera o valor
						if ($LOG_sql_keys and in_array($key, $LOG_sql_keys)) {
							$val = pg_fetch_result(
								pg_execute(
									$con, $key,
									compact($LOG_sql[$key]['filtro'])
								), 0, 0
							) ? : $val;
						}
						if($login_fabrica == 1 and $key=='fale_conosco') {
							$key = "SAC";
						}
						$key = ucfirst(str_replace('_', ' ', $key));
						if (strtolower($key) == "contato telefones") {
							$val = str_replace('{', "", $val);
							$val = str_replace('}', "", $val);
							$val = str_replace('""', "<i>&lt;vazio&gt;</i>", $val);
						}
						$val = formatArrayItem($key, $val);
						if ($Excluido == 1 && strrpos($val, 'vazio') !== false) {
							continue;
						}
						$dadosAntes .= sprintf($LOG_template['int'], $key, $val);
						if ($Excluido == 1) {
							$val = formatArrayItem($key, "");
							$dadosDepois .= sprintf($LOG_template['int'], $key, $val);
						}
					}
				}
			}
		}

		if ($Excluido !== 1) {
			foreach ($dadosLog['depois'] as $logItens) {
				$chave_primaria = 0;
				foreach ($logItens as $key=>$val) {
					if ($chave_primaria == 0 && $Inserido == 1) {
						$chave_primaria = 1;
                        if (is_numeric($val)) {
                            continue;
                        }
					}

					if (in_array(strtolower($key), $LOG_json)) {
						$parametros_adicionais = json_decode($val, true);
						foreach ($parametros_adicionais as $campo => $valor) {
							// Se tem um SELECT para o campo, executa e altera o valor
							if ($LOG_sql_keys and in_array($campo, $LOG_sql_keys)) {
								$valor = pg_fetch_result(
									pg_execute(
										$con, $campo,
										compact($LOG_sql[$campo]['filtro'])
									), 0, 0
								) ? : $valor;
							}

                            $campo = ucfirst(str_replace('_', ' ', $campo));
                            if ($login_fabrica == 1 && $campo == 'DadosAnteriores') {
                                continue;
                            }
                            $valor = formatArrayItem($campo, $valor);
                            if (is_array($valor)) {
                                $xvalor = $valor;
                                $valor = "";
                                foreach ($xvalor as $y => $vl) {
                                    $xxcampo = ucfirst(str_replace('_', ' ', $y));
                                    $xxvalor = formatArrayItem($xxcampo, $vl);
                                    $valor .= sprintf($LOG_template['int'], $xxcampo, $xxvalor);
                                }
                           }
                           $dadosDepois .= sprintf($LOG_template['int'], $campo, $valor);
                        
                            if ($Excluido == 1) {
                                $valor = formatArrayItem($campo, "");
                                $dadosAntes .= sprintf($LOG_template['int'], $campo, $valor);
                            }
                        }
					}else{
						// Se tem um SELECT para o campo, executa e altera o valor
						if ($LOG_sql_keys and in_array($key, $LOG_sql_keys)) {
							$val = pg_fetch_result(
								pg_execute(
									$con, $key,
									compact($LOG_sql[$key]['filtro'])
								), 0, 0
							) ? : $val;
						}
						if($login_fabrica == 1 and $key=='fale_conosco') {
							$key = "SAC";
						}

						$key = ucfirst(str_replace('_', ' ', $key));
						if (strtolower($key) == "contato telefones") {
							$val = str_replace('{', "", $val);
							$val = str_replace('}', "", $val);
							$val = str_replace('""', "<i>&lt;vazio&gt;</i>", $val);
						}

						$val = formatArrayItem($key, $val);
						if ($Inserido == 1 && strrpos($val, 'vazio') !== false) {
							continue;
						}
						$dadosDepois .= sprintf($LOG_template['int'], $key, $val);
						if ($Inserido == 1) {
							$val = formatArrayItem($key, "");
							$dadosAntes .= sprintf($LOG_template['int'], $key, $val);
						}
					}
				}
			}
		}


		$dadosAntes  = sprintf($LOG_template['ext'], $dadosAntes, $extraStyle);
		$dadosDepois = sprintf($LOG_template['ext'], $dadosDepois, $extraStyle);


        

		if ($Inserido == 1 || $Excluido == 1) {
			if ($Inserido == 1) {
				//$dataTable[$idx]['Antes']  = "<p class='alert alert-success' style='text-align: center'><em>&lt;REGISTRO CADASTRADO&gt;</em></p>";

                if (!empty($esconder_coluna)) {
                    if ($esconder_coluna == 'antes') {
                        $dataTable[$idx][traduz('depois')] = $dadosDepois;
                    } else {
                        $dataTable[$idx]['Antes'] = $dadosAntes;
                    }
                } else {
                    $dataTable[$idx]['Antes'] = $dadosAntes;
                    $dataTable[$idx][traduz('depois')] = $dadosDepois;
                }

			}else{
                if (!empty($esconder_coluna)) {
                    if ($esconder_coluna == 'antes') {
                        $dataTable[$idx][traduz('depois')] = $dadosDepois;
                    } else {
                        $dataTable[$idx]['Antes'] = $dadosAntes;
                    }
                } else {
                    $dataTable[$idx]['Antes'] = $dadosAntes;
                    $dataTable[$idx][traduz('depois')] = $dadosDepois;
                }
				//$dataTable[$idx]['Depois'] = "<p class='alert alert-error' style='text-align: center'><em>&lt;REGISTRO EXCLUIDO&gt;</em></p>";
			}
		}else{
            if (!empty($esconder_coluna)) {
                if ($esconder_coluna == 'antes') {
                    $dataTable[$idx][traduz('depois')] = $dadosDepois;
                } else {
                    $dataTable[$idx]['Antes'] = $dadosAntes;
                }
            } else {
                $dataTable[$idx]['Antes'] = $dadosAntes;
                $dataTable[$idx][traduz('depois')] = $dadosDepois;
            }
		}
	}
	if (!count($dataTable)) {
	echo 2;
		throw new Exception("Nenhum registro de LOG encontrado");
	}
} catch (Exception $e) {
	$msg = $e->getMessage();
}

// Mostra a tabela com os resultados
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title><?=$title?></title>
		<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/extra.css" />
		<!-- <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>css/tc_css.css" /> -->
		<!-- <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>css/tooltips.css" /> -->
		<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/ajuste.css" />

		<script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="<?=BI_BACK?>bootstrap/js/bootstrap.js"></script>

		<?php // include("plugin_loader.php"); ?>
		<?php if (strlen($LOG_template['CSS'])): ?>
		<style>
			<?=$LOG_template['CSS']?>
		</style>
		<?php endif; ?>
	</head>
<body>
<?php if ($msg): ?>
	<div style="align-items: center; display: flex; min-height: 100%; min-height: 100vh;">
		<div class='container'>
			<div class='row-fluid'>
				<div class='span12'>
					<div class='alert alert-warning'>
						<h4><?=$msg?></h4>
						Data início do LOG: 03/2017
					</div>
				</div>
			</div>
		</div>
	</div>
<?php endif;

if (count($dataTable)):
	$tableAttrs = array(
		'tableAttrs'   => ' class="table table-striped table-bordered table-hover table-fixed"',
		'captionAttrs' => ' class="titulo_tabela"',
		'headerAttrs'  => ' class="titulo_coluna"',
	);
?>
	<div class="container-fluid">
		<div class="lead text-info"><?=$titulo?></div>
		<div class="row-fluid">
		<?=array2table($dataTable, traduz('logs.de.alteracao'))?>
		</div>
	</div>
<?php endif; ?>
</body>
</html>
