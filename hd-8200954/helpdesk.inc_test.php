<?php
/**
 * Pagina de funcoes relacionadas ao sistema de Help Desk para o Posto
 *
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */

/**
 * Telecontrol Helpdesk Upload Dir.
 * Diretório com os arquivos de upload do helpdesk.
 */
define('TC_HD_UPLOAD_DIR','/www/assist/www/helpdesk/documentos/blackedecker/');
/**
 * Telecontrol HekDesk Upload URL.
 * URL base para o acesso aos arquivos de upload.
 */
define('TC_HD_UPLOAD_URL','http://www.telecontrol.com.br/assist/helpdesk/documentos/blackedecker/');

/**
 *  Array com a configuração dos tipos de chamados ($categorias)
 *      key:    		nome da categoria (o que vai pro banco)
 *      descricao       Descrição da categoria (o que vai pra tela)
 *      atendente:  	'posto' para procurar pela função hdBuscarAtendentePorPosto, ou o 'tbl_admin.admin'
 *      campos:     	array com os campos a mostrar para esta categoria
 *      campos_obrig:	array com os campos que são obrigatórios
 */
$a_tipos = Array('telefone'=> 'Telefone',	'linha_atendimento'	=> 'Linha de atendimento',
				 'email'	=> 'E-mail',	'dados_bancarios'	=> 'Dados bancários',
				 'endereco'	=> 'Endereço',	'end_cnp_raz_ban'	=> 'Endereço/CNPJ/Razão Social/Dados bancários',
				 'cnpj'		=> 'CNPJ',		'razao_social'		=> 'Razão social');

$a_campos= Array('usuario_sac'	=> 'Usuário que abre o chamado','tipo_atualizacao'	=> 'Tipo de Atualização',
				 'garantia'     => 'Garantia', 'os' => 'OS',	'referencia'		=> 'Referência do Produto',
				 'produto_de'   => 'Produto de',				'hd_chamado_sac'	=> 'Número de Atendimento',
				 'nome_cliente' => 'Nome do Cliente',           'pedido'			=> 'Número do Pedido',
				 'data_pedido'  => 'Data do Pedido',            'peca_faltante'     => 'Peça(s) em falta',
				 'distribuidor'	=> 'Distribuidor');

$categorias = Array (
	'atualiza_cadastro'	=> array (
		'descricao'     => 'Atualização de cadastro',
		'atendente'		=> 1267,
		'campos'		=> array('usuario_sac','tipo_atualizacao'),
		'campos_obrig'	=> array('usuario_sac','tipo_atualizacao')
	),
	'digitacao_fechamento'	=> array (
		'descricao'     => 'Digitação e/ou fechamento de OS\'s',
		'atendente'		=> 'posto',
		'campos'		=> array('usuario_sac','garantia','referencia','os'),
		'campos_obrig'	=> array('usuario_sac','referencia','os')
	),
	'utilizacao_do_site'=> array (
		'descricao'     => 'Dúvidas na utilização do site',
		'atendente'		=> 'posto',
		'campos'		=> array('usuario_sac', 'garantia','referencia','os'),
		'campos_obrig'	=> array('usuario_sac')
	),
	'duvida_troca'	=> array (
		'descricao'     => 'Dúvidas na troca de produto',
		'atendente'		=> 'posto',
		'campos'		=> array('garantia','referencia'),
		'campos_obrig'	=> array('usuario_sac','referencia','os')
	),
	'duvida_produto'	=> array (
		'descricao'     => 'Dúvida técnica sobre o produto',
		'atendente'		=> 'posto',
		'campos'		=> array('garantia','referencia','os'),
		'campos_obrig'	=> array('usuario_sac','referencia','os')
	),
	'duvida_revenda'	=> array (
		'descricao'     => 'Dúvidas sobre atendimento à revenda',
		'atendente'		=> 2079,
		'campos'		=> array('garantia','referencia','os'),
		'campos_obrig'	=> array('usuario_sac')
	),
	'geo_metais'        => array(
		'descricao'     => 'Metais sanitários e Fechaduras',
		'atendente'		=> 1805,
		'campos'		=> array('garantia','referencia','os','produto_de'),
		'campos_obrig'	=> array('usuario_sac','produto_de')
	),
	'falha_no_site'	=> array (
		'descricao'     => 'Falha no site',
		'atendente'		=> 'posto',
		'campos'		=> array('garantia','referencia','os'),
		'campos_obrig'	=> array('usuario_sac')
	),
	'manifestacao_sac'	=> array (
		'descricao'     => 'Manifestação sobre o SAC',
		'atendente'		=> 'posto',
		'campos'		=> array('garantia','referencia','os','hd_chamado_sac'),
		'campos_obrig'	=> array('usuario_sac','nome_cliente')
	),
	'pendencias_de_pecas'=>array (
		'descricao'     => 'Pendências de peças com a fábrica',
		'atendente'		=> 'posto',
		'campos'		=> array('os','pedido','data_pedido','peca_faltante'),
		'campos_obrig'	=> array('usuario_sac','os','pedido','data_pedido','peca_faltante')
	),/*
	'pedido_de_pecas'	=> array (
		'descricao'     => 'Pedido de peças',
		'atendente'		=> 'posto',
		'campos'		=> array('peca_faltante','data_pedido'),
		'campos_obrig'	=> array('usuario_sac','pedido')
	),*/
	'pend_pecas_dist'	=> array (
		'descricao'     => 'Pendências de peças com o distribuidor',
		'atendente'		=> 'posto',
		'campos'		=> array('pedido','data_pedido','os'),
		'campos_obrig'	=> array('usuario_sac','data_pedido','peca_faltante','distribuidor')
	),
	'outros'	=> array (
		'descricao'     => 'Outros',
		'atendente'		=> 'posto',
		'campos'		=> array('usuario_sac'),
		'campos_obrig'	=> array('usuario_sac')
	),
);
$a_linhas= Array(
	199=>'Eletro',
	198=>'DeWalt',
	200=>'Ferramentas Black & Decker',
	467=>'Porter Cable',
	494=>'Metais Sanitarios',
	777=>'Fechaduras'
);

/**
 * Retorna se a fabrica logada permite ou não a abertura de chamados
 * a partir dos postos.
 *
 * @return boolean
 */
function hdPermitePostoAbrirChamado() {
	global $login_fabrica, $login_posto;

	// Se o posto for o posto de testes, liberar
	if ( $login_posto == 6359 ) {
		return true;
	}

	// Fabricas que permites os postos abrir chamados
	$aFabricas = array(1);
	$aFabricas = array_flip($aFabricas);
	return (boolean) ( isset($aFabricas[$login_fabrica]) );
}

/**
 * Busca o número de determinados chamados, seguindo condições que podem ser determinadas.
 *
 * @param array $condicoes Condicoes SQL para serem utilizadas na consulta
 * @param string $sufixo A string sera inserida ao final da consulta SQL apos a clausula WHERE
 * @return array
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */
function hdBuscarChamados($condicoes = array(), $sufixo = null) {
	global $con, $login_fabrica;

	$sCondicoes = '';
	if ( count($condicoes) ) {
		$sCondicoes = 'AND '.implode(' AND ',$condicoes);
	}
	$sql  = "SELECT tbl_hd_chamado.hd_chamado, tbl_hd_chamado.admin, tbl_hd_chamado.posto,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY HH24:MI:SS') as data,
					tbl_hd_chamado.titulo, tbl_hd_chamado.atendente,
					tbl_hd_chamado.fabrica_responsavel,
					tbl_hd_chamado.fabrica, tbl_hd_chamado.categoria,
					tbl_hd_chamado.resolvido,
					tbl_hd_chamado.tipo_chamado,
					to_char(tbl_hd_chamado.data_resolvido,'DD/MM/YYYY HH24:MI:SS') as data_resolvido,
					tbl_hd_chamado.status,
					tbl_hd_chamado.posto       ,
					(tbl_hd_chamado.data_resolvido::date-tbl_hd_chamado.data::date) as tempo_atendimento,
					tbl_posto.nome as posto_nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.reembolso_peca_estoque,
					admin.login as atentende_abriu_login,
					admin.nome_completo as atendente_abriu_nome,
					atendente.login as atendente_ultimo_login, atendente.nome_completo as atendente_ultimo_nome,
					tbl_produto.referencia,
					tbl_os.sua_os,
					tbl_os.os,
					substr(tbl_pedido.seu_pedido,4,5) as pedido,
					tbl_hd_chamado_extra.pedido as pedido_ex,
					tbl_hd_chamado_extra.garantia,
					tbl_hd_chamado_posto.tipo             ,
					tbl_hd_chamado_posto.fone             ,
					tbl_hd_chamado_posto.email            ,
					tbl_hd_chamado_posto.banco            ,
					tbl_hd_chamado_posto.agencia          ,
					tbl_hd_chamado_posto.conta            ,
					tbl_hd_chamado_posto.nome_cliente     ,
					tbl_hd_chamado_posto.atendente    as atendente_sac,
					tbl_hd_chamado_posto.hd_chamado_sac,
					to_char(tbl_hd_chamado_posto.data_pedido,'DD/MM/YYYY') as data_pedido,
					tbl_hd_chamado_posto.peca_faltante,
					(SELECT data FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS data_ultima_interacao
			 FROM tbl_hd_chamado
			 JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			 JOIN tbl_posto   ON (tbl_posto.posto = tbl_hd_chamado.posto)
			 JOIN tbl_posto_fabrica   ON (tbl_posto_fabrica.posto = tbl_hd_chamado.posto AND tbl_posto_fabrica.fabrica = $login_fabrica)
			 LEFT JOIN tbl_admin as admin ON (admin.admin = tbl_hd_chamado.admin)
			 LEFT JOIN tbl_admin as atendente ON (atendente.admin = tbl_hd_chamado.atendente)
			 LEFT JOIN tbl_produto ON (tbl_hd_chamado_extra.produto = tbl_produto.produto)
			 LEFT JOIN tbl_os      ON (tbl_hd_chamado_extra.os = tbl_os.os)
			 LEFT JOIN tbl_pedido ON (tbl_hd_chamado_extra.pedido = tbl_pedido.pedido)
			 LEFT JOIN tbl_hd_chamado_posto ON tbl_hd_chamado.hd_chamado= tbl_hd_chamado_posto.hd_chamado
			 WHERE tbl_hd_chamado.fabrica = $login_fabrica
			 {$sCondicoes}
			 ORDER BY data_ultima_interacao, tbl_hd_chamado.data
			 {$sufixo}";
	$res  = pg_query($con,$sql);
	if ( ! is_resource($res) ) {return false; }
	$rows = array();
	while ( $row = pg_fetch_assoc($res) ) {
		$rows[] = $row;
	}
	return $rows;
}

/**
 * Busca dados de apenas um chamado.
 *
 * @see hdBuscarChamados
 * @param int $hd_chamado
 * @return array
 */
function hdBuscarChamado($hd_chamado) {
	$rows = hdBuscarChamados(array("tbl_hd_chamado.hd_chamado = {$hd_chamado}"));
	if ( is_array($rows) && count($rows) > 0 ) {
		return array_shift($rows);
	}
	return array();
}

/**
 * Retorna se o posto logado possui chamados pendentes
 *
 * @return boolean Se TRUE o posto possui chamados pendentes
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */
function hdPossuiChamadosPendentes() {
	global $login_posto;

	$params = array("tbl_hd_chamado.status NOT IN ('Resolvido','Resolvido Posto','Cancelado')", "tbl_hd_chamado.posto = {$login_posto}");
	$rows   = hdbuscarChamados($params,"LIMIT 1");
	return (boolean) count($rows);
}


/**
 * Busca os admins de determinada fábrica
 *
 * @param array[optional] $condicoes. (default: array()) Passar as condicoes SQL que se deseja utilizar
 * @return array
 */
function hdBuscarAdmin($condicoes = array()) {
	global $con, $login_fabrica;

	$sCondicoes = '';
	if ( is_array($condicoes) && count($condicoes) > 0 ) {
		$sCondicoes = 'AND '.implode(' AND ',$condicoes);
	}
	$fabrica = pg_escape_string($login_fabrica);
	$sql = "SELECT admin,
				   login,
				   nome_completo,
                   nao_disponivel
			FROM tbl_admin
			WHERE tbl_admin.fabrica = {$fabrica}
			{$sCondicoes}
			ORDER BY tbl_admin.admin ASC";
	$res = @pg_query($con,$sql);
	if ( ! is_resource($res) || pg_num_rows($res) <= 0 ) {
		return array();
	}
	$rows = array();
	while ( $row = pg_fetch_assoc($res) ) {
		$rows[$row['admin']] = $row;
	}
	return $rows;
}

/**
 * Busca os atendentes de atendimento ao posto de determinada fábrica.
 * AS condicoes utilizadas por padrao para buscar os atendentes são:
 * - tbl_admin.admin_sap IS TRUE
 * - tbl_admin.ativo IS TRUE
 *
 * @param array[optional] $condicoes Condicoes SQL para filtrar os atendentes de posto
 * @return array
 */
function hdBuscarAtendentes($condicoes = array()) {
	$params   = ( is_array($condicoes) ) ? $condicoes : array();
	$params[] = "tbl_admin.admin_sap IS true";
	$params[] = "tbl_admin.ativo IS true";
	return hdBuscarAdmin($params);
}

/**
 * Retorna o atendente para determinado posto.
 * Caso não exista um atendente cadastrado para o posto,
 * retorna um atendente comum, revezando entre os já utilizados
 * para outros atendimentos.
 *
 * @param int $posto Código do posto
 * @return int ID do admin
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */
function hdBuscarAtendentePorPosto($posto) {
	global $con, $login_fabrica;

	$fabrica = pg_escape_string($login_fabrica);
	// Verificar a existencia de um atendente preferencia pro posto.
	$atendente = null;
	$sql = "SELECT admin_sap
			FROM tbl_posto_fabrica
			WHERE tbl_posto_fabrica.fabrica = {$fabrica}
			AND tbl_posto_fabrica.posto = {$posto}";
	$res = @pg_query($con,$sql);
	if ( is_resource($res) && pg_num_rows($res) > 0 ) {
		$atendente = pg_fetch_result($res, 0, 'admin_sap');
	}

	if ( empty($atendente) ) {
		/**
		 * Não existe atendente definido para o posto,
		 * retornar portanto um atendente que foi marcado
		 * como atendente de postos (tbl_admin.admin_sap)
		 */
		$sql = "SELECT admin
				FROM tbl_hd_chamado
				WHERE fabrica = {$fabrica}
				ORDER BY hd_chamado DESC
				LIMIT 1";
		$res = @pg_query($con,$sql);
		if ( is_resource($res) && pg_num_rows($res) > 0 ) {
			$admin = pg_result($res,0,0);
		}
		$params = array();
		if ( isset($admin) ) {
			$params[] = "tbl_admin.admin != {$admin}";
		}
		$aAdmins = hdBuscarAtendentes($params);
		// Se nao houver nenhum atendente cadastrado para atender postor, retornar o MASTER
		if ( count($aAdmins) <= 0 ) {
			$params   = array();
			$params[] = "tbl_admin.ativo IS true";
			$params[] = "tbl_admin.privilegios ILIKE '%*%'";
			$aMasters = hdBuscarAdmin($params);
			if ( is_array($aMasters) && count($aMasters) > 0 ) {
				$aMaster = array_shift($aMasters);
				return $aMaster['admin'];
			}
		}
		// Caso haja atendentes para postos, fazer o rodizio
		foreach ( $aAdmins as $admin_new=>$aAdmin ) {
			if ( ! empty($admin) && $admin_new <= $admin ) { continue; }
			$atendente = $admin_new;
			break;
		}
		// Se nao achou o atendente apos a primeira passada, pegar o primeiro atendenre
		if ( empty($atendente) ) {
			$aAdmin = array_shift($aAdmins);
			return $aAdmin['admin'];
		} else {
			return $atendente;
		}
		//echo '<pre style="display: block; text-align: left;">',print_r($aAdmins),'</pre>'; die();
		/*
		$sql_where = ( isset($admin) ) ? " AND tbl_admin.admin != {$admin} " : '' ;
		$sql = "SELECT tbl_admin.admin
				FROM tbl_admin
				WHERE 1=1
				AND tbl_admin.fabrica = {$login_fabrica}
				AND tbl_admin.admin
				{$sql_where}
				ORDER BY admin ASC
				LIMIT 1";
		$res = @pg_query($con,$sql);
		if ( is_resource($res) && pg_num_rows($res) ) {
			return (int) pg_result($res,0,0);
		}*/
	} else {
		return $atendente;
	}
	return false;
}

/**
 * hdCadastrarResposta function.
 *
 * @access public
 * @param mixed $hd_chamado
 * @param mixed $resposta
 * @param mixed $interno
 * @param mixed $status
 * @param mixed $admin. (default: null)
 * @param mixed $posto. (default: null)
 * @return void
 */
function hdCadastrarResposta($hd_chamado, $resposta, $interno, $status, $admin = null, $posto = null) {
	global $con;

	$resposta = pg_escape_string($resposta);
	$status   = pg_escape_string($status);
	$interno  = ( (boolean) $interno ) ? 'true' : 'false' ;
	$admin    = ( is_null($admin) || empty($admin) ) ? 'null' : $admin ;
	$posto    = ( is_null($posto) || empty($posto) ) ? 'null' : $posto ;
	$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado_item, hd_chamado, comentario, admin, posto, interno, status_item, enviar_email)
			VALUES (DEFAULT, {$hd_chamado},'{$resposta}', $admin, $posto, $interno, '{$status}', false)
			RETURNING hd_chamado_item";
	$res = @pg_query($con,$sql);
	if ( ! is_resource($res) ) {
		echo 'Erro resposta: '.pg_last_error($con);
		return false;
	}
	return pg_result($res,0,0);
}

/**
 * Retorna as respostas de determinado chamado
 *
 * @param int $hd_chamado
 * @return array
 */
function hdBuscarRespostas($hd_chamado) {
	global $con,$login_fabrica;

	$hd_chamado = (int) $hd_chamado;
	$fabrica    = pg_escape_string($login_fabrica);
	$sql = "SELECT
			tbl_hd_chamado_item.hd_chamado_item    ,
			to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY HH24:MI:SS') as data,
			tbl_hd_chamado_item.comentario         ,
			tbl_admin.login as atendente   		   ,
			tbl_posto.posto						   ,
			tbl_posto.nome as posto_nome		   ,
			tbl_hd_chamado_item.interno            ,
			tbl_hd_chamado_item.status_item        ,
			tbl_hd_chamado_item.enviar_email
		FROM tbl_hd_chamado_item
		LEFT JOIN tbl_admin USING (admin)
		LEFT JOIN tbl_posto ON (tbl_hd_chamado_item.posto = tbl_posto.posto)
		INNER JOIN tbl_hd_chamado ON (tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado)
		WHERE tbl_hd_chamado_item.hd_chamado = {$hd_chamado}
		AND   tbl_hd_chamado.fabrica_responsavel = {$fabrica}
		/* a fabrica fricon quer ver os chamados internos */
		AND   (tbl_hd_chamado_item.interno IS NOT TRUE  OR tbl_hd_chamado.fabrica_responsavel = {$fabrica} )
		ORDER BY tbl_hd_chamado_item.data";
	$res  = @pg_query($con,$sql);
	$rows = array();
	if ( ! is_resource($res) || pg_num_rows($res) <= 0 ) {
		return $rows;
	}
	while ($row = pg_fetch_assoc($res)) {
		$rows[] = $row;
	}
	return $rows;
}

/**
 * Retorna o nome do arquivo de upload no padrão do help desk
 * ou busca o arquivo de upload.
 * Se passado só o primeiro parâmetro, busca o arquivo que já sofreu upload.
 * Se passados dois parâmetros, retorna o noma para o arquivo de upload no padrão do sistema.
 *
 * @param int $hd_resposta_item
 * @param string $filename. (default: null) Nome do arquivo antes de sofrer upload
 * @return string
 */

function retira_acentos( $texto ){
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@" );
	$array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" );
	return str_replace( $array1, $array2, $texto );
}


function hdNomeArquivoUpload($hd_resposta_item, $filename = null) {
	$hd_resposta_item = (int) $hd_resposta_item;
	if ( empty($hd_resposta_item) ) {
		return false;
	}
	if ( ! empty($filename) ) {
		$filename=retira_acentos($filename);
		$filename= implode("", explode(" ",$filename));
		// Retornar nome de arquivo para upload
		return 'hd_anexo_'.$hd_resposta_item.'_'.$filename;
	} else {
		// Buscar arquivo de upload para resposta informada
		$h = opendir(TC_HD_UPLOAD_DIR);
		if ( ! is_resource($h) ) { return false; }
		$return = false;
		while ( $file = readdir($h) ) {
			if ( strpos($file,'_'.$hd_resposta_item.'_') === false ) { continue; }
			$return = $file;
		}
		closedir($h);
		return $return;
	}
}

/**
 * Trata o upload de um arquivo para um resposta de callcenter ou helpdesk
 *
 * @param string $name Nome do campo no <form>
 * @param int $hd_resposta_item ID da resposta
 * @param string &$erro_msg. (default: '') Se existe alguma string que trata os erros do formulário, fornece-a aqui, e os erros serão acrescentados a ela
 * @return boolean TRUE caso o upload dê certo
 */
function hdCadastrarUpload($name, $hd_resposta_item, $erro_msg = '') {
	if ( ! isset($_FILES[$name]) ) { return false; }
	$file = $_FILES[$name];

	if ( $file['error'] != 0 ) {
		$erro_msg .= '<p>Ocorreu um erro no upload do anexo !</p>';
	} else if ( $file['size'] > 2048000 ) {
		$erro_msg .= '<p>Arquivo de anexo muito grande !</p>';
	}
	$dir     = TC_HD_UPLOAD_DIR;

	if ( ! is_writeable($dir) ) {
		$erro_msg .= "<p>Não foi possível salvar o anexo, diretório de destino sem permissão de escrita !</p>";
	}
	if ( ! empty($erro_msg) ) { return false; }
	$filename = hdNomeArquivoUpload($hd_resposta_item,$file['name']);
	if ( ! move_uploaded_file($file['tmp_name'],$dir.$filename) ) {
		$msg_erro .= "<p>Erro ao salvar anexo !</p>";
		return false;
	}
	chmod($dir.$filename,0666);
	return true;
}

/**
 *  buscarCidadeId: Procura o ID da cidade do Posto na tbl_cidade
 *
 *  @param: $estado - Estado (BR) do posto
 *  @param: $cidade - Nome da cidade
 *
 *  @return int ID da cidade $cidade na tbl_cidade
 *
 **/
function buscarCidadeId($estado, $cidade) {
	global $con;

	if (strlen($estado) != 2) {
		return false;
	}

	$sql = "SELECT c.cidade
			FROM tbl_cidade c
			WHERE c.estado = '$estado'
			  AND c.nome ILIKE '$cidade'";
	$res = @pg_query($con, $sql);
	if ( ! is_resource($res) ) {
		echo pg_last_error($con);
		return false;
	}
	if ( pg_num_rows($res) <= 0 ) {
		return null;
	}
	return pg_fetch_result($res,0,0);
}

//  Funções... mais. 20/05/2010 MLG
if (!function_exists('anti_injection')) {
	function anti_injection($string) {
		$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
		return strtr(strip_tags(trim($string)), $a_limpa);
	}
}
//  Função para conferir cada campo do $_POST, devolve 'null' ou o que colocar como último argumento
if (!function_exists('check_post_field')) {
	function check_post_field($fieldname, $returns = null) {
		if (!isset($_POST[$fieldname])) return $returns;
		$raw_data = $_POST[$fieldname];
		$data = (is_array($raw_data)) ? array_map('anti_injection', $raw_data) : anti_injection($raw_data);
	// 	echo "<p><b>$fieldname</b>: $data</p>\n";
		return ((!is_array($data) and strlen($data)==0) or (is_array($data) and count($data)==0)) ? $returns : $data;
	}
}
function CreateHTMLOption($valor,$nome,$valor_sel='') {
	if (!$valor) $valor = $nome;
	$sel = ($valor_sel == $valor) ? ' SELECTED':'';
	return "\t\t\t\t<option value='$valor'$sel>$nome</option>\n";
}

//  Confere no banco de dados se é uma data (ou date e hora) válida. Devolve FALSE se não for, ou a data já no formato do banco.
if (!function_exists('pg_is_date')) {
	function pg_is_date($data)
    { // BEGIN function pg_is_date
		global $con;
		$formato = (strlen($date)>10) ? 'timestamp' : 'date';
		$res = @pg_query($con, "SELECT '$data'::$formato");
    	return (is_resource($res)) ? pg_fetch_result($res, 0, 0) : false;
    } // END function pg_is_date
}
