<?php
/**
 * Pagina de funcoes relacionadas ao sistema de Help Desk para o Posto
 *
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */
if (!empty($aumenta_memory_limit)) {
	ini_set("memory_limit","512M");
}

/**
 * Telecontrol Helpdesk Upload Dir.
 * Diretório com os arquivos de upload do helpdesk.
 */
define('TC_HD_UPLOAD_DIR', dirname(__FILE__) . '/helpdesk/documentos/posto/');
/**
 * Telecontrol HekDesk Upload URL.
 * URL base para o acesso aos arquivos de upload.
 */
define('TC_HD_UPLOAD_URL', dirname(str_replace('admin/', '', $_SERVER['PHP_SELF'])) . '/helpdesk/documentos/posto/');

/**
 *  Array com a configuração dos tipos de chamados ($categorias)
 *      key:    		nome da categoria (o que vai pro banco)
 *      descricao       Descrição da categoria (o que vai pra tela)
 *      atendente:  	'posto' para procurar pela função hdBuscarAtendentePorPosto, ou o 'tbl_admin.admin'
 *      campos:     	array com os campos a mostrar para esta categoria
 *      campos_obrig:	array com os campos que são obrigatórios
 */
if ($login_fabrica == 1) {
	$a_tipos = Array('telefone'=> 'Telefone',	'linha_atendimento'	=> 'Linha de atendimento',
				 'email'	=> 'E-mail',	'dados_bancarios'	=> 'Dados bancários',
				 'endereco'	=> 'Endereço',	'end_cnp_raz_ban'	=> 'Endereço/CNPJ/Razão Social/Dados bancários',
				 'cnpj'		=> 'CNPJ',		'razao_social'		=> 'Razão social');
}

if ($login_fabrica == 11 || $login_fabrica == 42 or $login_fabrica == 172) {
	$a_tipos = Array('telefone'=> 'Telefone',	'email'	=> 'E-mail',	'dados_bancarios'	=> 'Dados bancários',
				 'endereco'	=> 'Endereço',	'end_cnp_raz_ban'	=> 'Endereço/CNPJ/Razão Social/Dados bancários',
				 'cnpj'		=> 'CNPJ',		'razao_social'		=> 'Razão social');
}

if ($login_fabrica == 42) {
	unset($a_tipos["end_cnp_raz_ban"]);
}

$a_campos= Array('usuario_sac'	=> 'Responsável pela Solicitação','tipo_atualizacao'	=> 'Tipo de Atualização',
				 'garantia'     => 'Garantia', 'os' => 'OS',	'referencia'		=> 'Referência do Produto',
				 'produto_de'   => 'Produto de',				'hd_chamado_sac'	=> 'Número de Atendimento',
				 'nome_cliente' => 'Nome do Cliente',           'pedido'			=> 'Número do Pedido',
				 'peca_faltante'     => 'Peça(s) em falta', 'produto_hidden'     => 'Produto',
				 'distribuidor'	=> 'Distribuidor', 'solicita_informacao_tecnica' => 'Informação Técnica referente',
				 'sugestao_critica' => 'Sugestões, críticas, reclamações ou elogios');

$categorias = Array(
	'atualiza_cadastro'	=> array (
		'descricao'     => 'Atualização de cadastro',
		'atendente'		=> 'posto',
		'campos'		=> array('usuario_sac','tipo_atualizacao'),
		'campos_obrig'	=> array('tipo_atualizacao'),
		'no_fabrica'    => array(3,11,172)
	),
	'comunicar_procon'	=> array (
		'descricao'     => 'Comunicar PROCON ou Casos Judiciais',
		'atendente'		=> 'posto',
		'campos'		=> array('usuario_sac'),
		'campos_obrig'	=> array(),
		'no_fabrica'    => array(1, 3)
	),
	'digitacao_fechamento'	=> array (
		'descricao'     => 'Digitação e/ou fechamento de OSs',
		'atendente'		=> 'posto',
		'campos'		=> array('usuario_sac','garantia','referencia','os'),
		'campos_obrig'	=> array(),
		'no_fabrica'    => array(1,3)
	),
	'duvida_troca'	=> array (
		'descricao'     => 'Dúvidas na troca de produto',
		'atendente'		=> 'posto',
		'campos'		=> array('garantia','referencia','os'),
		'campos_obrig'	=> array('usuario_sac','referencia'),
		'no_fabrica'    => array(42, 3)
	),
	'duvida_produto'	=> array (
		'descricao'     => (($login_fabrica == 42) ? 'Suporte Técnico' : 'Dúvida técnica sobre o produto'),
		'atendente'		=> 'posto',
		'campos'		=> array('garantia','referencia','os'),
		'campos_obrig'	=> array(),
		'no_fabrica'    => array(3)
	),
	'duvida_revenda'	=> array (
		'descricao'     => 'Dúvidas sobre atendimento à revenda',
		'atendente'		=> 'posto',
		'campos'		=> array('garantia','referencia','os'),
		'campos_obrig'	=> array('usuario_sac'),
		'no_fabrica'    => array(42, 3, 11, 172)
	),
	'duvida_peca_bloqueada_sempreco' => array(
		'descricao'    => 'Dúvidas de peças bloqueadas/sem preço',
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 42)
	),
	'duvida_administrativa' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas ADM [] NF [] ABERTURA DE OS [] PROCEDIMENTOS TELECONTROL []' : 'Dúvidas Administrativas - NF, Abertura de OS, procedimentos Telecontrol'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 11, 42, 172)
	),
	'duvida_adm_devolucao_prod' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas ADM [] DEVOLUÇÃO DE PRODUTOS []' : 'Dúvidas Adm Devolução de Produtos'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 11, 42, 172)
	),
	'duvida_financeira_duplicata' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Financeiras [] DUPLICATAS []' : 'Dúvidas Financeiras Duplicatas'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_financeira_mo' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Financeiras [] MÃO DE OBRA[]' : 'Dúvidas Financeiras Mão de Obra'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_nserie_eletro_pessoal_refri_av' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Número de Série [] ELETRO [] REFRIGERAÇÃO [] CUIDADOS PESSOAIS [] A&V []' : 'Dúvidas números de série eletro / cuidados pessoais / refrigeração / áudio e vídeo'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11, 172)
	),
	'duvida_numero_serie_informatica' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Número de Série [] INFORMÁTICA []' : 'Dúvidas número de série linha de informática'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11, 172)
	),
	'duvida_tecnica_audio_video' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Técnicas [] ÁUDIO E VÍDEO []' : 'Dúvidas técnicas áudio e vídeo'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => ($login_fabrica == 3) ? array('produto_hidden') : array(),
		'no_fabrica'   => array(1, 42, 11, 172)
	),
	'duvida_tecnica_celular' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Técnicas [] CELULAR [] SMARTPHONE []' : 'Dúvidas técnicas linha celular'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => ($login_fabrica == 3) ? array('produto_hidden') : array(),
		'no_fabrica'   => array(1, 42, 11, 172)
	),
	'duvida_tecnica_eletro_pessoal_refri' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Técnicas [] ELETRO [] REFRIGERAÇÃO [] CUIDADOS PESSOAIS []' : 'Dúvidas técnicas linha eletro / cuidados pessoais / refrigeração'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => ($login_fabrica == 3) ? array('produto_hidden') : array(),
		'no_fabrica'   => array(1, 42, 11, 172)
	),
	'duvida_tecnica_informatica' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Técnicas [] INFORMÁTICA []' : 'Dúvidas técnicas linha de informática'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => ($login_fabrica == 3) ? array('produto_hidden') : array(),
		'no_fabrica'   => array(1, 42, 11, 172)
	),
	'duvida_cadastro' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Cadastrais [] ALTERAÇÃO DE DADOS [] DESCREDENCIAMENTO []' : 'Dúvidas cadastrais (Alteração de dados/Descredenciamento)'),
		'atendente'    => 'posto',
		'campos'       => ($login_fabrica == 11 or $login_fabrica == 172) ? array() : array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_os_bloqueada' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas [] OSs BLOQUEADAS []' : 'Dúvidas de OSs bloqueadas'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 11, 42, 172)
	),
	'duvida_bloqueada_fora_linha' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas [] PEÇAS BLOQUEADAS [] FORA DE LINHA []' : 'Dúvidas de peças bloqueadas (fora de linha)'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 11, 42, 172)
	),
	'duvida_compra_venda' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas [] PEÇAS SEM PREÇO [] COMPRA [] VENDA []' : 'Dúvidas de peças sem preço, COMPRA/VENDA'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 11, 42, 172)
	),
	'peca_recebida_defeito' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Peça recebida [] COM DEFEITO []' : 'Peça recebida defeito'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 11, 42, 172)
	),
	'peca_recebida_errada' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Peça recebida [] ERRADA []' : 'Peça recebida errada'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'peca_defeito' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Peça recebida [] QUEBRADA []' : 'Peça com defeito / quebrada'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'pendencia_pedido_audio_video' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pendências de Pedidos [] ÁUDIO E VÍDEO []' : 'Pendências de pedidos linha áudios e vídeo'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11, 172)
	),
	'pendencia_pedido_pessoal' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pendências de Pedidos [] CUIDADOS PESSOAIS []' : 'Pendências de pedidos linha cuidados pessoais'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11, 172)
	),
	'pendencia_pedido_eletroportateis' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pendências de Pedidos [] ELETROPORTÁTEIS []' : 'Pendências de pedidos linha eletroportateis'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11, 172)
	),
	'pendencia_pedido_informatica' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pendências de Pedidos [] INFORMÁTICA []' : 'Pendências de pedidos linha informática'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11, 172)
	),
	'pendencia_pedido_lcd_led' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pendências de Pedidos [] LCD [] LED []' : 'Pendências de pedidos linha LCD/LED'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11, 172)
	),
	'rastreamento_nf' => array(
		'descricao'    => 'Rastreamento de NF',
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11, 172)
	),
	'soliticacao_lgr' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Solicitação [] COLETA LGR [] DEVOLUÇÃO OBRIGATÓRIA []' : 'Solicitação de coleta LGR (Devolução obrigatória)'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 11, 42, 172)
	),
	'dnf' => array(
		'descricao'    => (($login_fabrica == 3) ? 'DNF [] NOTIFICAÇÃO DE DIVERGÊNCIA []' : 'DNF - Notificação de Divergência de NF/Pç. Recebida com defeito/errada/divergente/faltante'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11, 172)
	),
	'duvida_cobertura_garantia_informatica' => array(
		'descricao'    => 'Dúvidas cobertura da garantia linha de informática',
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 42, 11, 172)
	),
	'duvida_financeiro' => array(
		'descricao'    => 'Dúvidas financeiras (Duplicatas/MO)',
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 11, 42, 172)
	),
	'duvida_juridica' => array(
		'descricao'    => 'Dúvidas jurídicas (Notificações PROCON/JEC)',
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 42)
	),
	'duvida_cobertura_garantia' => array(
		'descricao'    => 'Dúvidas cobertura de garantia - todas as linhas, exceto Informática',
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 42, 11, 172)
	),
	'erro_embarque'=> array (
		'descricao'     => (($login_fabrica == 1) ? 'Problemas com embarque' : 'Erro de embarque'),
		'atendente'		=> 'posto',
		'campos'		=> array(),
		'campos_obrig'	=> array(),
		'no_fabrica'    => array(42, 3, 11, 172)
	),
	'falha_no_site'	=> array (
		'descricao'     => (($login_fabrica == 42 || $login_fabrica == 1) ? 'Falha no site Telecontrol' : 'Falha no site'),
		'atendente'		=> 'posto',
		'campos'		=> array('garantia','os'),
		'campos_obrig'	=> array(),
		'no_fabrica'    => array(3, 11, 172)
	),
	'geo_metais'        => array(
		'descricao'     => 'Metais sanitários e Fechaduras',
		'atendente'		=> 'posto',
		'campos'		=> array('garantia','referencia','os','produto_de'),
		'campos_obrig'	=> array('usuario_sac','produto_de'),
		'no_fabrica'    => array(1,3,11,42, 172)
	),
	'manifestacao_sac'	=> array (
		'descricao'     => (($login_fabrica == 1) ? 'Chamados SAC' : 'Manifestação sobre o SAC'),
		'atendente'		=> 'posto',
		'campos'		=> array('garantia','referencia','os','hd_chamado_sac'),
		'campos_obrig'	=> array('usuario_sac','nome_cliente'),
		'no_fabrica'    => array(42, 3, 11, 172)
	),
	'outros'	=> array (
		'descricao'     => 'Outros',
		'atendente'		=> 'posto',
		'campos'		=> array('usuario_sac'),
		'campos_obrig'	=> array('usuario_sac'),
		'no_fabrica'    => array(1,3,11,42,172)
	),
	'pendencias_de_pecas'=>array (
		'descricao'     => (($login_fabrica == 42) ? 'Pendência de peças / Pedidos de peças' : 'Pendências de peças com a fábrica'),
		'atendente'		=> 'posto',
		'campos'		=> array('os','pedido','data_pedido','peca_faltante'),
		'campos_obrig'	=> array(),
		'no_fabrica'    => array(3,11,172)
	),
	'pend_pecas_dist'	=> array (
		'descricao'     => 'Pendências de peças com o distribuidor',
		'atendente'		=> 'posto',
		'campos'		=> array('pedido','data_pedido','os'),
		'campos_obrig'	=> array('usuario_sac','peca_faltante','distribuidor'),
		'no_fabrica'    => array(42, 3, 11, 172)
	),
	'pagamento_garantia'=> array (
		'descricao'     => (($login_fabrica == 42) ? 'Garantia - Procedimento ou Pagamento' :'Pagamento das garantias'),
		'atendente'		=> 'posto',
		'campos'		=> array(),
		'campos_obrig'	=> array(),
		'no_fabrica'    => array(3)
	),
	'pagamento_antecipado'=> array (
		'descricao'     => 'Pagamento Antecipado',
		'atendente'		=> 'posto',
		'campos'		=> array(),
		'campos_obrig'	=> array(),
		'no_fabrica'    => array(3,11,42,172)
	),
	'peca_recebida_defeito_informatica' => array(
		'descricao'    => 'Peças recebidas com defeito linha de informática',
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 42, 11, 172)
	),
	'solicita_informacao_tecnica'	=> array (
		'descricao'     => 'Solicitação de Informação Técnica',
		'atendente'		=> 'posto',
		'campos'		=> array(),
		'campos_obrig'	=> array('solicita_informacao_tecnica'),
		'no_fabrica'    => array(1, 3, 11, 172,42)
	),
	'solicitacao_coleta'=> array (
		'descricao'     => (($login_fabrica == 1) ? 'Devolução de Peça / Produto' : 'Solicitação de coleta'),
		'atendente'		=> 'posto',
		'campos'		=> array('fone'),
		'campos_obrig'	=> array(),
		'no_fabrica'    => array(3,11,172)
	),
	'sugestao_critica'	=> array (
		'descricao'     => 'Sugestao, Críticas, Reclamações ou Elogios',
		'atendente'		=> 'posto',
		'campos'		=> array(),
		'campos_obrig'	=> array('sugestao_critica'),
		'no_fabrica'    => array(1, 3, 11, 172)
	),
    'treinamento_makita'    => array (
        'descricao'     => 'Treinamentos Makita',
        'atendente'     => 'posto',
        'campos'        => array(),
        'campos_obrig'  => array('resposta'),
        'no_fabrica'    => array(1, 3, 11, 172)
    ),
    'duvidas_telecontrol'    => array (
        'descricao'     => 'Duvida na utilização Telecontrol',
        'atendente'     => 'posto',
        'campos'        => array(),
        'campos_obrig'  => array(),
        'no_fabrica'    => array(3,11,42, 172)
    ),
	'satisfacao_90_dewalt'	=> array (
		'descricao'     => 'Satisfação 90 dias DEWALT',
		'atendente'		=> 'posto',
		'campos'		=> array(),
		'campos_obrig'	=> array(),
		'no_fabrica'    => array(3,11,42, 172)
	),
	'comunicado_posto'	=> array (
		'descricao'     => 'Comunicado Posto',
		'atendente'		=> 'posto',
		'campos'		=> array(),
		'campos_obrig'	=> array(),
		'no_fabrica'    => array(3,11,42,172)
	),
	'gestao_carteira'	=> array (
		'descricao'     => 'Gestão Carteira',
		'atendente'		=> 'posto',
		'campos'		=> array(),
		'campos_obrig'	=> array(),
		'no_fabrica'    => array(3,11,42,172)
	),
	'utilizacao_do_site'=> array (
		'descricao'     => (($login_fabrica == 42) ? 'Dúvidas de utilização do Telecontrol' : 'Dúvidas na utilização do site'),
		'atendente'		=> 'posto',
		'campos'		=> array('usuario_sac', 'garantia','referencia','os'),
		'campos_obrig'	=> array(),
		'no_fabrica'    => array(3, 11, 172)
	),
	'patam_filiais_makita'=> array (
		'descricao'     => "Filiais Makita",
        'atendente'     => 'posto',
        'campos'        => array(),
        'campos_obrig'  => array(),
		'no_fabrica'    => array(1,3,11, 172)
	),
	"servico_atendimeto_sac" => array(
        "descricao" => "Serviço de atendimento ao consumidor - SAC",
        "no_fabrica" => array(3,11,42, 172)
    ),
    'makita_msi' => array(
		'descricao'    => 'Acesso MSI',
		'atendente'    => 'posto',
		'campos'       => array(),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1,3,11, 172)
	)

);

$telaCadastro = preg_match('/helpdesk_cadastrar/',$_SERVER['PHP_SELF']) > 0 ? true : false;

if (in_array($login_fabrica, [1]) && !empty($login_posto) && $telaCadastro) {
	unset($categorias["comunicado_posto"], $categorias["gestao_carteira"], $categorias["advertencia"]);
}

// 777=>'Fechaduras' - Coloquei 777 porque a Blackedecker não tem linha Fechaduras cadastrada no sistema HD 281195
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
	$aFabricas = array(1,42,3,11,151,172);
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
function hdBuscarChamados($condicoes = array(), $sufixo = null, $pendente = false) {
	global $con, $login_fabrica, $login_posto, $PHP_SELF, $categorias, $msg_erro;
	if($login_fabrica == 42){
		if($sufixo == 'peca_causadora'){
			$join_makita = "LEFT JOIN tbl_hd_chamado_posto_peca on tbl_hd_chamado_posto_peca.hd_chamado_posto = tbl_hd_chamado_posto.hd_chamado_posto";
			$distinct_makita = 'DISTINCT';
			$sufixo = null;
		}
	}
	$sCondicoes = '';
	if ( count($condicoes) ) {
		$sCondicoes = 'AND '.implode(' AND ',$condicoes);
	}

	if(in_array($login_fabrica, array(1,3,42,11,172))){
		$sCondicoes .= " AND upper(tbl_hd_chamado.titulo) = trim('HELP-DESK POSTO') ";
	}

	$verifica = strpos($PHP_SELF,"helpdesk_cadastrar.php") ? false : true;

	if (!$_POST and $verifica) {
		if($login_posto and $pendente == false){
			$sCondicoes .= " AND (tbl_hd_chamado.status='Ag. Posto' OR TO_ASCII(tbl_hd_chamado.status, 'LATIN-9') = TO_ASCII('Ag. Conclusao', 'LATIN-9')) ";
		} else {
		//	$sCondicoes .= " AND (tbl_hd_chamado.status='Ag. Fábrica' or (hh.status_item = 'Em Acomp.' and tbl_hd_chamado.status='Ag. Posto')) ";
		}
	} else{
		
		$no_fabrica = 0;

		foreach ($categorias as $key => $value) {
			if($key == $tipo_solicitacao){
				$fabricas = $value["no_fabrica"];
				foreach ($fabricas as $key1 => $valueFabrica) {
					if($valueFabrica == $login_fabrica){
						$no_fabrica++;
					}
				}
			}
		}

		if($no_fabrica > 0){

			echo $msg_no_fabrica = "Essa opção não pertence a fabrica!";
			exit;

		}
		if(in_array(basename($_SERVER['PHP_SELF']), ['helpdesk_cadastrar.php', 'helpdesk_listar.php'] )){
			$hd_chamado       = $_POST['hd_chamado'];
			$data_inicial     = $_POST['data_inicial'];
			$data_final       = $_POST['data_final'];
			$status_aux       = $_POST['status'];
			$tipo_solicitacao = $_POST['tipo_solicitacao'];
		}
		if(!in_array(basename($_SERVER['PHP_SELF']), ['helpdesk_cadastrar.php', 'helpdesk_listar.php'] )){
			if($login_posto){
				$sCondicoes .= " AND (tbl_hd_chamado.status='Ag. Posto') ";
			}
		}
		$cont_sac = strripos($hd_chamado,'SAC');

		if($cont_sac === false){

			if(strlen(trim($hd_chamado)) > 0){
				$hd_chamado = (int)$hd_chamado;

				if($hd_chamado == 0){
					$msg_erro .= "Por favor informar um número de chamado válido. <br>";
				}
			}
		}

		if ($login_fabrica == 3) {
			$os                 = $_POST["os"];
			$numero_serie       = $_POST["numero_serie"];
			$produto_referencia = $_POST["produto_referencia"];
			$produto_descricao  = $_POST["produto_descricao"];
		}

		if(!empty($hd_chamado)){
			list($hd_chamado,$digito) = explode('-',$hd_chamado);
			$hd_chamado = hdChamadoAnterior2($hd_chamado);
			list($hd_chamado,$digito) = explode('-',$hd_chamado);
			if ($login_fabrica == 3) {
				$sCondicoes .=  " AND (tbl_hd_chamado_posto.seu_hd = '$hd_chamado' OR tbl_hd_chamado.hd_chamado = $hd_chamado OR tbl_hd_chamado.hd_chamado_anterior = $hd_chamado)";
			} else {
				if($cont_sac === false){
					$sCondicoes .=  " AND (tbl_hd_chamado.hd_chamado = $hd_chamado OR tbl_hd_chamado.hd_chamado_anterior = $hd_chamado)";
				}else{
					$sCondicoes .=  " AND tbl_hd_chamado.protocolo_cliente = $hd_chamado";
				}
			}
		} else {
			if(!empty($data_inicial) OR !empty($data_final)){

				list($di, $mi, $yi) = explode("/", $data_inicial);
				if(!checkdate($mi,$di,$yi))
					$msg_erro = "Data inicial inválida";

				list($df, $mf, $yf) = explode("/", $data_final);
				if(!checkdate($mf,$df,$yf))
					$msg_erro = "Data final inválida";

				if(strlen($msg_erro)==0){
					$aux_data_inicial = "$yi-$mi-$di";
					$aux_data_final = "$yf-$mf-$df";

					if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
						$msg_erro = "Data final menor do que a data inicial";
					}
				}

				if(strlen($msg_erro)==0){
					$sCondicoes .= " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";
				}
			}

			if(!empty($status_aux)){
				$sCondicoes .= " AND tbl_hd_chamado.status = '$status_aux' ";
			}

			if(!empty($tipo_solicitacao)){
				$sCondicoes .= " AND tbl_hd_chamado.categoria = '$tipo_solicitacao' ";
			}

			if ($login_fabrica == 3) {
				if (!empty($os)) {
					$sCondicoes .= " AND tbl_os.sua_os = '$os' ";
				}

				if (!empty($numero_serie)) {
					$sCondicoes .= " AND tbl_hd_chamado_extra.serie = '$numero_serie' ";
				}

				if (!empty($produto_referencia) || !empty($produto_descricao)) {
					$sql = "SELECT produto
							FROM tbl_produto
							WHERE fabrica_i = $login_fabrica
							AND (
								UPPER(tbl_produto.referencia) = UPPER('$produto_referencia')
								OR
								UPPER(fn_retira_especiais(tbl_produto.descricao)) = UPPER(fn_retira_especiais('$produto_descricao'))
							)";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$sCondicoes .= " AND tbl_hd_chamado_extra.produto = ".pg_fetch_result($res, 0, "produto")." ";
					} else {
						$msg_erro = "Produto não encontrado";
					}
				}
			}
		}
	}

    if($login_fabrica == 3){
            $campos_defeito_solucao = " tbl_defeito_constatado.descricao AS defeito,
                                        tbl_solucao.descricao AS solucao, ";
            $left_join_defeito_solucao = " LEFT JOIN tbl_dc_solucao_hd ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado
                                        LEFT JOIN tbl_defeito_constatado_solucao ON tbl_dc_solucao_hd.defeito_constatado_solucao = tbl_defeito_constatado_solucao.defeito_constatado_solucao
										LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
										LEFT JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao ";
    }

    if ($login_fabrica == 1) {
    	$join_hd_chamado_item  = " LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
    							   LEFT JOIN tbl_cidade ON tbl_hd_chamado_posto.cidade = tbl_cidade.cidade
    							   LEFT JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin
    							 ";
    }

	$cond_fabrica_hd_chamado    = (in_array($login_fabrica, array(11,172))) ? " tbl_hd_chamado.fabrica_responsavel IN (11, 172) " : " tbl_hd_chamado.fabrica_responsavel = $login_fabrica ";
	$cond_fabrica_produto       = (in_array($login_fabrica, array(11,172))) ? " tbl_produto.fabrica_i IN (11, 172) " : " tbl_produto.fabrica_i = $login_fabrica ";
	$cond_fabrica_os            = (in_array($login_fabrica, array(11,172))) ? " tbl_os.fabrica IN (11, 172) " : " tbl_os.fabrica = $login_fabrica ";
	$cond_fabrica_posto_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_posto_fabrica.fabrica IN (11, 172) " : " tbl_posto_fabrica.fabrica = $login_fabrica ";
	$cond_fabrica_when          = (in_array($login_fabrica, array(11,172))) ? " 11 = 1 OR 172 = 1 " : " $login_fabrica = 1 ";
	$cond_fabrica_distinct      = (in_array($login_fabrica, array(1,11,172))) ? " DISTINCT " : "";
	if($login_fabrica == 3){
		$contato_cep = " contato_cep,  ";
	}

	if ($login_fabrica == 3) {
		$rs = " to_char(tbl_hd_chamado.resolvido,'DD/MM/YYYY HH24:MI:SS') as resolvido, ";
	} else {
		$rs = " tbl_hd_chamado.resolvido, ";
	}
	
	$sql  = "SELECT {$cond_fabrica_distinct} {$distinct_makita} tbl_hd_chamado_extra.serie,
                                     tbl_hd_chamado.hd_chamado,
                                     tbl_hd_chamado.admin,
                                     tbl_hd_chamado.protocolo_cliente,
                                     tbl_hd_chamado.posto,
                                     tbl_hd_chamado.hd_chamado_anterior,
                                     to_char(tbl_hd_chamado.data,'DD/MM/YYYY HH24:MI:SS') as data,
                                     tbl_hd_chamado.data AS data_abertura,
                                     tbl_hd_chamado.titulo, tbl_hd_chamado.atendente,
                                     tbl_hd_chamado.duracao,
                                     tbl_hd_chamado.fabrica_responsavel,
                                     tbl_hd_chamado.fabrica, tbl_hd_chamado.categoria,
                                     $rs
                                     tbl_hd_chamado.tipo_chamado,
                                     to_char(tbl_hd_chamado.data_resolvido,'DD/MM/YYYY HH24:MI:SS') as data_resolvido,
                                     tbl_hd_chamado.status,
                                     tbl_hd_chamado.posto       ,
                                     ((SELECT data FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY hd_chamado_item DESC LIMIT 1) - tbl_hd_chamado.data) as tempo_atendimento,
                                     tbl_posto.nome as posto_nome,
                                     tbl_posto_fabrica.codigo_posto,
                                     $contato_cep
                                     tbl_posto_fabrica.reembolso_peca_estoque,
                                     admin.login as atentende_abriu_login,
                                     admin.nome_completo as atendente_abriu_nome,
                                     atendente.nome_completo as atendente_ultimo_login, atendente.nome_completo as atendente_ultimo_nome,
                                     tbl_produto.referencia,
                                     tbl_os.sua_os,
                                     tbl_os.os,
                                     tbl_os.serie as serie_os,
                                     case when {$cond_fabrica_when} then substr(tbl_pedido.seu_pedido,4,5) else tbl_pedido.pedido::text end as pedido,
                                     tbl_hd_chamado_extra.pedido as pedido_ex,
                                     tbl_hd_chamado_extra.garantia,
                                     tbl_hd_chamado_extra.leitura_pendente,
                                     tbl_hd_chamado_extra.nota_fiscal,
                                     to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
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
                                     tbl_hd_chamado_posto.inf_adicionais,
                                     $campos_defeito_solucao
                    				(SELECT data FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS data_ultima_interacao,
									(SELECT status_item FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS ultima_resposta,
									(SELECT admin FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS ultimo_admin,
									(SELECT interno FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS ultimo_interno,
									(SELECT status_item FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_item.admin NOTNULL ORDER BY data DESC LIMIT 1) AS ultima_resposta_admin,
                                     CASE WHEN tbl_hd_chamado.hd_chamado_anterior ISNULL THEN
                                             tbl_hd_chamado.hd_chamado
                                     ELSE
                                             tbl_hd_chamado.hd_chamado_anterior
                                     END AS chamado_anterior ,
                                     tbl_hd_chamado_extra.array_campos_adicionais,
                                     tbl_hd_chamado_posto.seu_hd,
									 tbl_hd_chamado.campos_adicionais
                      FROM tbl_hd_chamado
                      JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                      JOIN tbl_posto   ON (tbl_posto.posto = tbl_hd_chamado.posto)
                      JOIN tbl_posto_fabrica   ON (tbl_posto_fabrica.posto = tbl_hd_chamado.posto AND {$cond_fabrica_posto_fabrica})
                      LEFT JOIN tbl_admin as admin ON (admin.admin = tbl_hd_chamado.admin)
                      LEFT JOIN tbl_admin as atendente ON (atendente.admin = tbl_hd_chamado.atendente)
                      LEFT JOIN tbl_produto ON (tbl_hd_chamado_extra.produto = tbl_produto.produto AND {$cond_fabrica_produto})
                      LEFT JOIN tbl_os ON (tbl_hd_chamado_extra.os = tbl_os.os AND {$cond_fabrica_os} AND tbl_os.posto = tbl_hd_chamado.posto)
                      LEFT JOIN tbl_pedido ON (tbl_hd_chamado_extra.pedido = tbl_pedido.pedido)
                      left JOIN tbl_hd_chamado_posto ON tbl_hd_chamado.hd_chamado= tbl_hd_chamado_posto.hd_chamado
					  $join_makita
                      $left_join_defeito_solucao
                      /*join (select max(hd_chamado_item) as item,hd_chamado from tbl_hd_chamado_item group by hd_chamado) h on h.hd_chamado= tbl_hd_chamado.hd_chamad
                      join tbl_hd_chamado_item hh on hh.hd_chamado_item = h.item */
                      $join_hd_chamado_item
                      WHERE {$cond_fabrica_hd_chamado}
                      {$sCondicoes}
                      ORDER BY chamado_anterior, tbl_hd_chamado.hd_chamado
					  {$sufixo}";

		$res  = pg_query($con,$sql);

     if ( ! is_resource($res) ) {return false; }
     $rows = array();
     while ( $row = pg_fetch_assoc($res) ) {
             $rows[] = $row;
     }
     return $rows;
}

function hdBuscarChamadosSAC($condicoes = array(), $sufixo = null) {
	global $con, $login_fabrica, $login_posto, $PHP_SELF, $categorias;

	$sCondicoes = '';
	if ( count($condicoes) ) {
		$sCondicoes = 'AND '.implode(' AND ',$condicoes);
	}

	if(in_array($login_fabrica, array(1,3,42,11,172))){
		$sCondicoes .= " AND upper(tbl_hd_chamado.titulo) = trim('HELP-DESK POSTO') ";
	}

	$verifica = strpos($PHP_SELF,"helpdesk_listar.php");
	if ( !$_POST AND $verifica !== false) {
		if($login_posto){
			$sCondicoes .= " AND (tbl_hd_chamado.status='Ag. Posto') ";
		} else {
		//	$sCondicoes .= " AND (tbl_hd_chamado.status='Ag. Fábrica' or (hh.status_item = 'Em Acomp.' and tbl_hd_chamado.status='Ag. Posto')) ";
		}
	} else{
		$hd_chamado       = $_POST['hd_chamado'];
		$data_inicial     = $_POST['data_inicial'];
		$data_final       = $_POST['data_final'];
		$status_aux       = $_POST['status'];
		$tipo_solicitacao = $_POST['tipo_solicitacao'];

		$no_fabrica = 0;

		foreach ($categorias as $key => $value) {
			if($key == $tipo_solicitacao){
				$fabricas = $value["no_fabrica"];
				foreach ($fabricas as $key1 => $valueFabrica) {
					if($valueFabrica == $login_fabrica){
						$no_fabrica++;
					}
				}
			}
		}

		if($no_fabrica > 0){

			echo $msg_no_fabrica = "Essa opção não pertence a fabrica!";
			exit;

		}

		$hd_chamado       = $_POST['hd_chamado'];
		$data_inicial     = $_POST['data_inicial'];
		$data_final       = $_POST['data_final'];
		$status_aux       = $_POST['status'];
		$tipo_solicitacao = $_POST['tipo_solicitacao'];

		if ($login_fabrica == 3) {
			$os                 = $_POST["os"];
			$numero_serie       = $_POST["numero_serie"];
			$produto_referencia = $_POST["produto_referencia"];
			$produto_descricao  = $_POST["produto_descricao"];
		}

		if(!empty($hd_chamado)){
			list($hd_chamado,$digito) = explode('-',$hd_chamado);
			$hd_chamado = hdChamadoAnterior2($hd_chamado);
			list($hd_chamado,$digito) = explode('-',$hd_chamado);
			if ($login_fabrica == 3) {
				$sCondicoes .=  " AND (tbl_hd_chamado_posto.seu_hd = '$hd_chamado' OR tbl_hd_chamado.hd_chamado = $hd_chamado OR tbl_hd_chamado.hd_chamado_anterior = $hd_chamado)";
			} else {
				$sCondicoes .=  " AND (tbl_hd_chamado.protocolo_cliente = '$hd_chamado')";
			}
		} else {
			if(!empty($data_inicial) OR !empty($data_final)){

				list($di, $mi, $yi) = explode("/", $data_inicial);
				if(!checkdate($mi,$di,$yi))
					$msg_erro = "Data inicial inválida";

				list($df, $mf, $yf) = explode("/", $data_final);
				if(!checkdate($mf,$df,$yf))
					$msg_erro = "Data final inválida";

				if(strlen($msg_erro)==0){
					$aux_data_inicial = "$yi-$mi-$di";
					$aux_data_final = "$yf-$mf-$df";

					if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
						$msg_erro = "Data final menor do que a data inicial";
					}
				}

				if(strlen($msg_erro)==0){
					$sCondicoes .= " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";
				}
			}

			if(!empty($status_aux)){
				$sCondicoes .= " AND tbl_hd_chamado.status = '$status_aux' ";
			}

			if(!empty($tipo_solicitacao)){
				$sCondicoes .= " AND tbl_hd_chamado.categoria = '$tipo_solicitacao' ";
			}

			if ($login_fabrica == 3) {
				if (!empty($os)) {
					$sCondicoes .= " AND tbl_os.sua_os = '$os' ";
				}

				if (!empty($numero_serie)) {
					$sCondicoes .= " AND tbl_hd_chamado_extra.serie = '$numero_serie' ";
				}

				if (!empty($produto_referencia) || !empty($produto_descricao)) {
					$sql = "SELECT produto
							FROM tbl_produto
							WHERE fabrica_i = $login_fabrica
							AND (
								UPPER(tbl_produto.referencia) = UPPER('$produto_referencia')
								OR
								UPPER(fn_retira_especiais(tbl_produto.descricao)) = UPPER(fn_retira_especiais('$produto_descricao'))
							)";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$sCondicoes .= " AND tbl_hd_chamado_extra.produto = ".pg_fetch_result($res, 0, "produto")." ";
					} else {
						$msg_erro = "Produto não encontrado";
					}
				}
			}
		}
	}

     if($login_fabrica == 3){
            $campos_defeito_solucao = " tbl_defeito_constatado.descricao AS defeito,
                                        tbl_solucao.descricao AS solucao, ";
            $left_join_defeito_solucao = " LEFT JOIN tbl_dc_solucao_hd ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado
                                        LEFT JOIN tbl_defeito_constatado_solucao ON tbl_dc_solucao_hd.defeito_constatado_solucao = tbl_defeito_constatado_solucao.defeito_constatado_solucao
										LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
										LEFT JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao ";
			
			$rs = " to_char(tbl_hd_chamado.resolvido,'DD/MM/YYYY HH24:MI:SS') as resolvido, ";
	 } else {
		$rs = " tbl_hd_chamado.resolvido, ";
     }

	 $sql  = "SELECT tbl_hd_chamado_extra.serie, tbl_hd_chamado.hd_chamado,
                                     tbl_hd_chamado.admin,
                                     tbl_hd_chamado.protocolo_cliente,
                                     tbl_hd_chamado.posto,
                                     tbl_hd_chamado.hd_chamado_anterior,
                                     to_char(tbl_hd_chamado.data,'DD/MM/YYYY HH24:MI:SS') as data,
                                     tbl_hd_chamado.data AS data_abertura,
                                     tbl_hd_chamado.titulo, tbl_hd_chamado.atendente,
                                     tbl_hd_chamado.duracao,
                                     tbl_hd_chamado.fabrica_responsavel,
                                     tbl_hd_chamado.fabrica, tbl_hd_chamado.categoria,
                                     $rs
                                     tbl_hd_chamado.tipo_chamado,
                                     to_char(tbl_hd_chamado.data_resolvido,'DD/MM/YYYY HH24:MI:SS') as data_resolvido,
                                     tbl_hd_chamado.status,
                                     tbl_hd_chamado.posto       ,
                                     ((SELECT data FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY hd_chamado_item DESC LIMIT 1) - tbl_hd_chamado.data) as tempo_atendimento,
                                     tbl_posto.nome as posto_nome,
                                     tbl_posto_fabrica.codigo_posto,
                                     tbl_posto_fabrica.reembolso_peca_estoque,
                                     admin.login as atentende_abriu_login,
                                     admin.nome_completo as atendente_abriu_nome,
                                     atendente.nome_completo as atendente_ultimo_login, atendente.nome_completo as atendente_ultimo_nome,
                                     tbl_produto.referencia,
                                     tbl_os.sua_os,
                                     tbl_os.os,
                                     case when $login_fabrica = 1 then substr(tbl_pedido.seu_pedido,4,5) else tbl_pedido.pedido::text end as pedido,
                                     tbl_hd_chamado_extra.pedido as pedido_ex,
                                     tbl_hd_chamado_extra.garantia,
                                     tbl_hd_chamado_extra.nota_fiscal,
                                     to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
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
                                     tbl_hd_chamado_posto.inf_adicionais,
                                     $campos_defeito_solucao
                                     (SELECT data FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS data_ultima_interacao,
									 (SELECT status_item FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS ultima_resposta,
									 (SELECT admin FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS ultimo_admin,
									 (SELECT interno FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS ultimo_interno,
									 (SELECT status_item FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_item.admin NOTNULL ORDER BY data DESC LIMIT 1) AS ultima_resposta_admin,
                                     CASE WHEN tbl_hd_chamado.hd_chamado_anterior ISNULL THEN
                                             tbl_hd_chamado.hd_chamado
                                     ELSE
                                             tbl_hd_chamado.hd_chamado_anterior
                                     END AS chamado_anterior ,
                                     tbl_hd_chamado_extra.array_campos_adicionais,
                                     tbl_hd_chamado_posto.seu_hd
                      FROM tbl_hd_chamado
                      JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                      JOIN tbl_posto   ON (tbl_posto.posto = tbl_hd_chamado.posto)
                      JOIN tbl_posto_fabrica   ON (tbl_posto_fabrica.posto = tbl_hd_chamado.posto AND tbl_posto_fabrica.fabrica = $login_fabrica)
                      LEFT JOIN tbl_admin as admin ON (admin.admin = tbl_hd_chamado.admin)
                      LEFT JOIN tbl_admin as atendente ON (atendente.admin = tbl_hd_chamado.atendente)
                      LEFT JOIN tbl_produto ON (tbl_hd_chamado_extra.produto = tbl_produto.produto AND tbl_produto.fabrica_i=$login_fabrica)
                      LEFT JOIN tbl_os ON (tbl_hd_chamado_extra.os = tbl_os.os AND tbl_os.fabrica=$login_fabrica AND tbl_os.posto = tbl_hd_chamado.posto)
                      LEFT JOIN tbl_pedido ON (tbl_hd_chamado_extra.pedido = tbl_pedido.pedido)
                      LEFT JOIN tbl_hd_chamado_posto ON tbl_hd_chamado.hd_chamado= tbl_hd_chamado_posto.hd_chamado
					  LEFT JOIN tbl_hd_chamado_posto_peca ON tbl_hd_chamado.hd_chamado= tbl_hd_chamado_posto_peca.hd_chamado_posto
                      $left_join_defeito_solucao
                      /*join (select max(hd_chamado_item) as item,hd_chamado from tbl_hd_chamado_item group by hd_chamado) h on h.hd_chamado= tbl_hd_chamado.hd_chamad
                      join tbl_hd_chamado_item hh on hh.hd_chamado_item = h.item */
                      WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
                      {$sCondicoes}
                      ORDER BY chamado_anterior, tbl_hd_chamado.hd_chamado
					  {$sufixo}";
     $res  = pg_query($con,$sql);
     if ( ! is_resource($res) ) {return false; }
     $rows = array();
     while ( $row = pg_fetch_assoc($res) ) {
             $rows[] = $row;
     }
     return $rows;
}

function hdBuscarChamadosSAP($condicoes = array(), $sufixo = null) {
	global $con, $login_fabrica, $login_posto, $PHP_SELF, $categorias;

	$sCondicoes = '';
	if ( count($condicoes) ) {
		$sCondicoes = 'AND '.implode(' AND ',$condicoes);
	}

	if(in_array($login_fabrica, array(1,3,42,11,172))){
		$sCondicoes .= " AND upper(tbl_hd_chamado.titulo) = trim('HELP-DESK POSTO') ";
	}

	$verifica = strpos($PHP_SELF,"helpdesk_listar.php");

	if ( !$_POST AND $verifica == true) {
		if($login_posto){
			$sCondicoes .= " AND (tbl_hd_chamado.status='Ag. Posto') ";
		} else {
		//	$sCondicoes .= " AND (tbl_hd_chamado.status='Ag. Fábrica' or (hh.status_item = 'Em Acomp.' and tbl_hd_chamado.status='Ag. Posto')) ";
		}
	} else{
		$hd_chamado       = $_POST['hd_chamado'];
		$data_inicial     = $_POST['data_inicial'];
		$data_final       = $_POST['data_final'];
		$status_aux       = $_POST['status'];
		$tipo_solicitacao = $_POST['tipo_solicitacao'];

		$no_fabrica = 0;

		foreach ($categorias as $key => $value) {
			if($key == $tipo_solicitacao){
				$fabricas = $value["no_fabrica"];
				foreach ($fabricas as $key1 => $valueFabrica) {
					if($valueFabrica == $login_fabrica){
						$no_fabrica++;
					}
				}
			}
		}

		if($no_fabrica > 0){

			echo $msg_no_fabrica = "Essa opção não pertence a fabrica!";
			exit;

		}

		$hd_chamado       = $_POST['hd_chamado'];
		$data_inicial     = $_POST['data_inicial'];
		$data_final       = $_POST['data_final'];
		$status_aux       = $_POST['status'];
		$tipo_solicitacao = $_POST['tipo_solicitacao'];

		if ($login_fabrica == 3) {
			$os                 = $_POST["os"];
			$numero_serie       = $_POST["numero_serie"];
			$produto_referencia = $_POST["produto_referencia"];
			$produto_descricao  = $_POST["produto_descricao"];
		}

		if(!empty($hd_chamado)){
			$cont_sac = strripos($hd_chamado,'SAC');

			list($hd_chamado,$digito) = explode('-',$hd_chamado);
			$hd_chamado = hdChamadoAnterior2($hd_chamado);
			list($hd_chamado,$digito) = explode('-',$hd_chamado);
			if ($login_fabrica == 3) {
				$sCondicoes .=  " AND (tbl_hd_chamado_posto.seu_hd = '$hd_chamado' OR tbl_hd_chamado.hd_chamado = $hd_chamado OR tbl_hd_chamado.hd_chamado_anterior = $hd_chamado)";
			} else {
				if($cont_sac === false){
					$sCondicoes .=  " AND (tbl_hd_chamado.hd_chamado = $hd_chamado OR tbl_hd_chamado.hd_chamado_anterior = $hd_chamado)";
				}else{
					$sCondicoes .=  " AND tbl_hd_chamado.protocolo_cliente = $hd_chamado";
				}
			}
			
		} else {
			if(!empty($data_inicial) OR !empty($data_final)){

				list($di, $mi, $yi) = explode("/", $data_inicial);
				if(!checkdate($mi,$di,$yi))
					$msg_erro = "Data inicial inválida";

				list($df, $mf, $yf) = explode("/", $data_final);
				if(!checkdate($mf,$df,$yf))
					$msg_erro = "Data final inválida";

				if(strlen($msg_erro)==0){
					$aux_data_inicial = "$yi-$mi-$di";
					$aux_data_final = "$yf-$mf-$df";

					if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
						$msg_erro = "Data final menor do que a data inicial";
					}
				}

				if(strlen($msg_erro)==0){
					$sCondicoes .= " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";
				}
			}

			if(!empty($status_aux)){
				$sCondicoes .= " AND tbl_hd_chamado.status = '$status_aux' ";
			}

			if(!empty($tipo_solicitacao)){
				$sCondicoes .= " AND tbl_hd_chamado.categoria = '$tipo_solicitacao' ";
			}

			if ($login_fabrica == 3) {
				if (!empty($os)) {
					$sCondicoes .= " AND tbl_os.sua_os = '$os' ";
				}

				if (!empty($numero_serie)) {
					$sCondicoes .= " AND tbl_hd_chamado_extra.serie = '$numero_serie' ";
				}

				if (!empty($produto_referencia) || !empty($produto_descricao)) {
					$sql = "SELECT produto
							FROM tbl_produto
							WHERE fabrica_i = $login_fabrica
							AND (
								UPPER(tbl_produto.referencia) = UPPER('$produto_referencia')
								OR
								UPPER(fn_retira_especiais(tbl_produto.descricao)) = UPPER(fn_retira_especiais('$produto_descricao'))
							)";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$sCondicoes .= " AND tbl_hd_chamado_extra.produto = ".pg_fetch_result($res, 0, "produto")." ";
					} else {
						$msg_erro = "Produto não encontrado";
					}
				}
			}
		}
	}

     if($login_fabrica == 3){
            $campos_defeito_solucao = " tbl_defeito_constatado.descricao AS defeito,
                                        tbl_solucao.descricao AS solucao, ";
            $left_join_defeito_solucao = " LEFT JOIN tbl_dc_solucao_hd ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado
                                        LEFT JOIN tbl_defeito_constatado_solucao ON tbl_dc_solucao_hd.defeito_constatado_solucao = tbl_defeito_constatado_solucao.defeito_constatado_solucao
										LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
										LEFT JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao ";
			$rs = " to_char(tbl_hd_chamado.resolvido,'DD/MM/YYYY HH24:MI:SS') as resolvido, ";
    } else {
		$rs = " tbl_hd_chamado.resolvido, ";
	}

	 $sql  = "SELECT tbl_hd_chamado_extra.serie, tbl_hd_chamado.hd_chamado,
                                     tbl_hd_chamado.admin,
                                     tbl_hd_chamado.protocolo_cliente,
                                     tbl_hd_chamado.posto,
                                     tbl_hd_chamado.hd_chamado_anterior,
                                     to_char(tbl_hd_chamado.data,'DD/MM/YYYY HH24:MI:SS') as data,
                                     tbl_hd_chamado.data AS data_abertura,
                                     tbl_hd_chamado.titulo, tbl_hd_chamado.atendente,
                                     tbl_hd_chamado.duracao,
                                     tbl_hd_chamado.fabrica_responsavel,
                                     tbl_hd_chamado.fabrica, tbl_hd_chamado.categoria,
                                     $rs
                                     tbl_hd_chamado.tipo_chamado,
                                     to_char(tbl_hd_chamado.data_resolvido,'DD/MM/YYYY HH24:MI:SS') as data_resolvido,
                                     tbl_hd_chamado.status,
                                     tbl_hd_chamado.posto       ,
                                     ((SELECT data FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY hd_chamado_item DESC LIMIT 1) - tbl_hd_chamado.data) as tempo_atendimento,
                                     tbl_posto.nome as posto_nome,
                                     tbl_posto_fabrica.codigo_posto,
                                     tbl_posto_fabrica.reembolso_peca_estoque,
                                     admin.login as atentende_abriu_login,
                                     admin.nome_completo as atendente_abriu_nome,
                                     atendente.nome_completo as atendente_ultimo_login, atendente.nome_completo as atendente_ultimo_nome,
                                     tbl_produto.referencia,
                                     tbl_os.sua_os,
                                     tbl_os.os,
                                     case when $login_fabrica = 1 then substr(tbl_pedido.seu_pedido,4,5) else tbl_pedido.pedido::text end as pedido,
                                     tbl_hd_chamado_extra.pedido as pedido_ex,
                                     tbl_hd_chamado_extra.garantia,
                                     tbl_hd_chamado_extra.nota_fiscal,
                                     to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
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
                                     tbl_hd_chamado_posto.inf_adicionais,
                                     $campos_defeito_solucao
                                     (SELECT data FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS data_ultima_interacao,
									 (SELECT status_item FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS ultima_resposta,
									 (SELECT admin FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS ultimo_admin,
									 (SELECT interno FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS ultimo_interno,
									 (SELECT status_item FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_item.admin NOTNULL ORDER BY data DESC LIMIT 1) AS ultima_resposta_admin,
                                     CASE WHEN tbl_hd_chamado.hd_chamado_anterior ISNULL THEN
                                             tbl_hd_chamado.hd_chamado
                                     ELSE
                                             tbl_hd_chamado.hd_chamado_anterior
                                     END AS chamado_anterior ,
                                     tbl_hd_chamado_extra.array_campos_adicionais,
                                     tbl_hd_chamado_posto.seu_hd
                      FROM tbl_hd_chamado
                      JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                      JOIN tbl_posto   ON (tbl_posto.posto = tbl_hd_chamado.posto)
                      JOIN tbl_posto_fabrica   ON (tbl_posto_fabrica.posto = tbl_hd_chamado.posto AND tbl_posto_fabrica.fabrica = $login_fabrica)
                      LEFT JOIN tbl_admin as admin ON (admin.admin = tbl_hd_chamado.admin)
                      LEFT JOIN tbl_admin as atendente ON (atendente.admin = tbl_hd_chamado.atendente)
                      LEFT JOIN tbl_produto ON (tbl_hd_chamado_extra.produto = tbl_produto.produto AND tbl_produto.fabrica_i=$login_fabrica)
                      LEFT JOIN tbl_os ON (tbl_hd_chamado_extra.os = tbl_os.os AND tbl_os.fabrica=$login_fabrica AND tbl_os.posto = tbl_hd_chamado.posto)
                      LEFT JOIN tbl_pedido ON (tbl_hd_chamado_extra.pedido = tbl_pedido.pedido)
                      LEFT JOIN tbl_hd_chamado_posto ON tbl_hd_chamado.hd_chamado= tbl_hd_chamado_posto.hd_chamado
                      $left_join_defeito_solucao
                      /*join (select max(hd_chamado_item) as item,hd_chamado from tbl_hd_chamado_item group by hd_chamado) h on h.hd_chamado= tbl_hd_chamado.hd_chamad
                      join tbl_hd_chamado_item hh on hh.hd_chamado_item = h.item */
                      WHERE tbl_hd_chamado.fabrica = $login_fabrica
                      {$sCondicoes}
                      ORDER BY chamado_anterior, tbl_hd_chamado.hd_chamado
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

	$params = array("(tbl_hd_chamado.status NOT IN('Resolvido Posto','Resolvido','Cancelado') OR tbl_hd_chamado.status NOT ILIKE 'Resolvido Posto%')", "tbl_hd_chamado.posto = {$login_posto}");
	$rows   = hdBuscarChamados($params,"LIMIT 1");

	return (boolean) count($rows);
}

function hdPossuiChamadosPendentesQtde() {
	global $login_posto;

	$params = array("(tbl_hd_chamado.status NOT IN('Resolvido Posto','Resolvido','Cancelado') OR tbl_hd_chamado.status NOT ILIKE 'Resolvido Posto%')", "tbl_hd_chamado.posto = {$login_posto}");
	$rows   = hdBuscarChamados($params);

	return count($rows);
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
			ORDER BY ultimo_acesso desc, tbl_admin.admin ASC";
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
function hdBuscarAtendentePorPosto($posto,$categoria, $posto_filial) {
	global $con, $login_fabrica;

	$fabrica = pg_escape_string($login_fabrica);
	$atendente = null;

	if ($login_fabrica == 1) {
		#Verifica se existe um atendente preferencial para o posto
		$sql = "SELECT tbl_posto_fabrica.admin_sap
				FROM tbl_posto_fabrica
				JOIN tbl_admin ON tbl_admin.admin = tbl_posto_fabrica.admin_sap
				WHERE tbl_posto_fabrica.fabrica = {$fabrica}
				AND tbl_admin.ativo IS TRUE
				AND tbl_posto_fabrica.posto = {$posto}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0 ) {

			return pg_fetch_result($res, 0, 'admin_sap');
		}

		$sql = "SELECT tbl_ibge.cod_ibge AS cidade, UPPER(contato_estado) AS estado
				FROM tbl_posto_fabrica
				LEFT JOIN tbl_ibge ON UPPER( fn_retira_especiais(tbl_posto_fabrica.contato_cidade)) = UPPER( fn_retira_especiais(tbl_ibge.cidade))
				WHERE fabrica = {$login_fabrica}
				AND posto = {$posto}";
		$res = pg_query($con, $sql);

		$cod_ibge = pg_fetch_result($res, 0, "cidade");
		$estado   = pg_fetch_result($res, 0, "estado");

		if(strlen(trim($cod_ibge)) > 0 and strlen(trim($estado))>0){
			#Verifica se existe um atendente para cidade + estado + tipo de solicitação
			$sql = "SELECT tbl_admin_atendente_estado.admin
					FROM tbl_admin_atendente_estado
					JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
					WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
					AND tbl_admin.ativo IS TRUE
					AND tbl_admin_atendente_estado.cod_ibge = {$cod_ibge}
					AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado}'
					AND tbl_admin_atendente_estado.categoria = '{$categoria}'";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {

				return pg_fetch_result($res, 0, "admin");
			}
		}

		if(strlen(trim($estado))>0){
			#Verifica se existe um atendente para estado + tipo de solicitação
			$sql = "SELECT tbl_admin_atendente_estado.admin
					FROM tbl_admin_atendente_estado
					JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
					WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
					AND tbl_admin.ativo IS TRUE
					AND tbl_admin_atendente_estado.cod_ibge IS NULL
					AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado}'
					AND tbl_admin_atendente_estado.categoria = '{$categoria}'";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				return pg_fetch_result($res, 0, "admin");
			}
		}
		#Verifica se existe um atendente para tipo de solicitação
		$sql = "SELECT tbl_admin_atendente_estado.admin
				FROM tbl_admin_atendente_estado
				JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
				WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
				AND tbl_admin.ativo IS TRUE
				AND tbl_admin_atendente_estado.cod_ibge IS NULL
				AND (tbl_admin_atendente_estado.estado IS NULL OR LENGTH(tbl_admin_atendente_estado.estado) = 0)
				AND tbl_admin_atendente_estado.categoria = '{$categoria}'";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {

			return pg_fetch_result($res, 0, "admin");
		}

		#Verifica se existe um atendente para categoria_posto
		$sql = "SELECT tbl_admin_atendente_estado.admin
				FROM tbl_admin_atendente_estado
				JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
				JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = {$posto} AND
					tbl_posto_fabrica.fabrica = tbl_admin_atendente_estado.fabrica AND
					tbl_posto_fabrica.categoria = tbl_admin_atendente_estado.categoria_posto
				WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
				AND tbl_admin.ativo IS TRUE
				AND tbl_admin_atendente_estado.cod_ibge IS NULL
				AND (tbl_admin_atendente_estado.estado IS NULL OR LENGTH(tbl_admin_atendente_estado.estado) = 0)
				";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {

			return pg_fetch_result($res, 0, "admin");
		}

		#Verifica se existe um atendente para tipo_posto
		$sql = "SELECT tbl_admin_atendente_estado.admin
				FROM tbl_admin_atendente_estado
				JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
				JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = {$posto} AND
					tbl_posto_fabrica.fabrica = tbl_admin_atendente_estado.fabrica AND
					tbl_posto_fabrica.tipo_posto = tbl_admin_atendente_estado.tipo_posto
				WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
				AND tbl_admin.ativo IS TRUE
				AND tbl_admin_atendente_estado.cod_ibge IS NULL
				AND (tbl_admin_atendente_estado.estado IS NULL OR LENGTH(tbl_admin_atendente_estado.estado) = 0)
				";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {

			return pg_fetch_result($res, 0, "admin");
		}

		if(strlen(trim($cod_ibge)) > 0 and strlen(trim($estado))>0){
			#Verifica se existe um atendente para cidade + estado
			$sql = "SELECT tbl_admin_atendente_estado.admin
					FROM tbl_admin_atendente_estado
					JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
					WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
					AND tbl_admin.ativo IS TRUE
					AND tbl_admin.nao_disponivel is null
					AND tbl_admin_atendente_estado.cod_ibge = {$cod_ibge}
					AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado}'
					AND tbl_admin_atendente_estado.categoria IS NULL";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				return pg_fetch_result($res, 0, "admin");
			}
		}

		if(strlen(trim($estado))>0){
			#Verifica se existe um atendente para estado
			$sql = "SELECT tbl_admin_atendente_estado.admin
					FROM tbl_admin_atendente_estado
					JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
					WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
					AND tbl_admin.ativo IS TRUE
					AND tbl_admin_atendente_estado.cod_ibge IS NULL
					AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado}'
					AND tbl_admin_atendente_estado.categoria IS NULL";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				return pg_fetch_result($res, 0, "admin");
			}
		}
		#Caso não ache nenhum irá pegar o admin master
		$params   = array();
		$params[] = "tbl_admin.ativo IS TRUE";
		$params[] = "tbl_admin.privilegios ILIKE '%*%'";
		$aMasters = hdBuscarAdmin($params);

		if (is_array($aMasters) && count($aMasters) > 0) {
			$aMaster = array_shift($aMasters);
			return $aMaster['admin'];
		}

	} else {
		//Verificar a existência de um atendente para a solicitação
		if ($login_fabrica == 42) {
			if (!empty($posto_filial)) {
				$where_filial = " AND tbl_admin_atendente_estado.posto_filial = $posto_filial ";
			}
			$sql_count_admins = "SELECT count(distinct admin) FROM tbl_admin_atendente_estado
								JOIN tbl_admin USING (admin)
								WHERE categoria = '$categoria'
								and tbl_admin_atendente_estado.fabrica = $login_fabrica
								$where_filial
								AND ativo is true AND admin_sap and (nao_disponivel is null or nao_disponivel = '')";
			$qry_count_admins = pg_query($con, $sql_count_admins);

			if (pg_fetch_result($qry_count_admins, 0, 0) > 1) {
				$naoRepetir = "AND admin <> (select coalesce(atendente,0) from tbl_hd_chamado where fabrica = $login_fabrica and categoria = '$categoria' order by hd_chamado desc limit 1)";
			} else {
				$naoRepetir = "";
			}

			$sql = "SELECT admin FROM tbl_admin_atendente_estado
					JOIN tbl_admin USING (admin)
					WHERE categoria = '$categoria'
					and tbl_admin_atendente_estado.fabrica = $login_fabrica
					$where_filial
					AND ativo is true AND admin_sap and (nao_disponivel is null or nao_disponivel = '')
					$naoRepetir
					ORDER BY random() LIMIT 1";
		} else {
			$sql = "
                SELECT  tbl_admin_atendente_estado.admin
                FROM    tbl_admin_atendente_estado
                JOIN    tbl_admin USING(admin)
                WHERE   tbl_admin_atendente_estado.categoria = '$categoria'
                AND     tbl_admin_atendente_estado.fabrica = $login_fabrica
                AND     (
                            tbl_admin.nao_disponivel IS NULL
                        OR  tbl_admin.nao_disponivel = '')
            ";
		}

		$res = pg_query($con,$sql);
		if ( is_resource($res) && pg_num_rows($res) > 0 ) {
			$atendente = pg_fetch_result($res, 0, 'admin');
		}

		if ( empty($atendente) ) {

			if ($login_fabrica == 42) {
				return 'NULL';
			}
			if($login_fabrica == 3) {
				$join = " JOIN tbl_admin ON tbl_admin.admin  = tbl_posto_fabrica.admin_sap_especifico ";
			}else{
				$join = " JOIN tbl_admin ON tbl_admin.admin  = tbl_posto_fabrica.admin_sap ";
			}

			// Verificar a existencia de um atendente preferencia pro posto.
			$sql = "SELECT tbl_posto_fabrica.admin_sap, admin_sap_especifico
					FROM tbl_posto_fabrica
					$join
					WHERE tbl_posto_fabrica.fabrica = {$fabrica}
					AND   tbl_admin.ativo
					AND (tbl_admin.nao_disponivel IS NULL OR tbl_admin.nao_disponivel = '')
					AND tbl_posto_fabrica.posto = {$posto}";
			$res = @pg_query($con,$sql);
			if ( is_resource($res) && pg_num_rows($res) > 0 ) {
				if ($login_fabrica == 3) {
					$atendente = pg_fetch_result($res, 0, 'admin_sap_especifico');
				} else {
					$atendente = pg_fetch_result($res, 0, 'admin_sap');
				}
			}


			if ( empty($atendente) ) {

				//Pega o admin que atende a cidade
			$sql = "SELECT tbl_ibge.cod_ibge
					FROM tbl_ibge
						JOIN tbl_posto_fabrica ON UPPER(fn_retira_especiais(tbl_posto_fabrica.contato_cidade)) = UPPER(fn_retira_especiais(tbl_ibge.cidade))
					WHERE tbl_posto_fabrica.fabrica = $fabrica
						AND tbl_posto_fabrica.posto = $posto";
			$res = pg_query($con, $sql);

			$cod_ibge = pg_result($res, 0, "cod_ibge");

			if (strlen($cod_ibge) > 0)
			{
				$sql = "SELECT tbl_admin_atendente_estado.admin
						FROM tbl_admin
						JOIN tbl_admin_atendente_estado ON tbl_admin_atendente_estado.admin = tbl_admin.admin
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = tbl_admin.fabrica
						WHERE tbl_admin.fabrica = $fabrica
						AND (tbl_admin.nao_disponivel IS NULL OR tbl_admin.nao_disponivel = '')
						AND tbl_admin_atendente_estado.cod_ibge = $cod_ibge
						AND tbl_posto_fabrica.posto = $posto
						LIMIT 1";
				$res = @pg_query($con,$sql);
				if ( is_resource($res) && pg_num_rows($res) > 0 ) {
					return $admin = pg_result($res,0,0);
				}
			}

			//Pega o admin que atende aquele estado!
			if (pg_num_rows($res) == 0 or strlen($cod_ibge) == 0)
			{
				$sql = "SELECT tbl_admin_atendente_estado.admin
						FROM tbl_admin_atendente_estado
						JOIN tbl_admin ON tbl_admin.fabrica = $login_fabrica
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE tbl_admin_atendente_estado.fabrica = $login_fabrica
						AND tbl_posto_fabrica.posto = $posto
						AND tbl_admin_atendente_estado.estado = tbl_posto_fabrica.contato_estado
						AND tbl_admin_atendente_estado.cod_ibge IS NULL
						AND (tbl_admin.nao_disponivel IS NULL OR tbl_admin.nao_disponivel = '')
						LIMIT 1";
				$res = @pg_query($con,$sql);
				if ( is_resource($res) && pg_num_rows($res) > 0 ) {
					return $admin = pg_result($res,0,0);
				}
			}

				// Se nao houver nenhum atendente cadastrado para atender posto, retornar o MASTER
				$params   = array();
				$params[] = "tbl_admin.ativo IS true";
				$params[] = "tbl_admin.privilegios ILIKE '%*%'";
				$params[] = "tbl_admin.admin_sap IS TRUE";
				$aMasters = hdBuscarAdmin($params);
				if ( is_array($aMasters) && count($aMasters) > 0 ) {
					$aMaster = array_shift($aMasters);
					return $aMaster['admin'];
				}

				$params = array();
				if ( isset($admin) ) {
					$params[] = "tbl_admin.admin != {$admin}";
				}
				$aAdmins = hdBuscarAtendentes($params);

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

			}else {
				return $atendente;
			}
		}else {
			return $atendente;
		}
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
function hdCadastrarResposta($hd_chamado, $resposta, $interno, $status, $admin = null, $posto = null,$xtransferir = null) {
	global $con, $login_admin, $login_fabrica, $login_login, $login_posto;

	$resposta = pg_escape_string($resposta);
	$manterHtml = '<strong><b><em><br /><br><span><u><table><thead><tr><th><tbody><td><ul><li><ol><u><a>';
	$resposta = strip_tags(html_entity_decode($resposta),$manterHtml);

	$status   = pg_escape_string($status);
	$interno  = ( (boolean) $interno ) ? 'true' : 'false' ;
	$admin    = ( is_null($admin) || empty($admin) ) ? 'null' : $admin ;
	$posto    = ( is_null($posto) || empty($posto) ) ? 'null' : $posto ;

	/*if($login_fabrica == 3 && strlen($resposta) == 0){
		return true;
	}*/

	if (strlen($xtransferir) > 0 AND ($login_admin <> $xtransferir) AND $interno != false) {

		$sql = "UPDATE tbl_hd_chamado set atendente = $xtransferir
				WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				and tbl_hd_chamado.hd_chamado = $hd_chamado";

		$res = pg_query($con,$sql);

		$sql = "SELECT login from tbl_admin where admin = $login_admin";
		$res = pg_query($con, $sql);

		$nome_ultimo_atendente = pg_fetch_result($res, 0, 'login');

		$sql = "SELECT login from tbl_admin where admin = $xtransferir";
		$res = pg_query($con, $sql);

		$nome_atendente = pg_fetch_result($res,0,login);
		
		$sql = "INSERT INTO tbl_hd_chamado_item(
					hd_chamado   ,
					data         ,
					comentario   ,
					admin        ,
					interno      ,
					status_item
				) values (
					$hd_chamado       ,
					current_timestamp ,
					'Atendimento transferido por <b>$login_login</b> de <b>$nome_ultimo_atendente</b> para <b>$nome_atendente</b><br />{$resposta}',
					$login_admin      ,
					't'  ,
					'$status'
				) RETURNING hd_chamado_item";

		$res = pg_query($con,$sql);
	}else{
		$sql = "INSERT INTO tbl_hd_chamado_item (
				hd_chamado_item,
				hd_chamado,
				comentario,
				admin,
				posto,
				interno,
				status_item,
				enviar_email
			) VALUES (
				DEFAULT,
				{$hd_chamado},
				'{$resposta}',
				$admin,
				$posto,
				$interno,
				'{$status}',
				false
			) RETURNING hd_chamado_item";
		$res = pg_query($con,$sql);
	}

	$hd_chamado_item = pg_fetch_result($res, 0, 'hd_chamado_item');

	if ($login_fabrica == 1) {

		if (!class_exists('PHPMailer')) {
		    include_once __DIR__.'/class/email/mailer/class.phpmailer.php';
		}

		$mailerLogBlack = new PHPMailer();
		$mailerLogBlack->AddAddress("logs.blackedecker@telecontrol.com.br");

		if (!empty($login_admin)) {
			$sql = "SELECT login from tbl_admin where admin = $login_admin";
			$res = pg_query($con, $sql);

			$nome_admin = pg_fetch_result($res, 0, 'login');

			$informacaoAdmin = "<p>Admin: $nome_admin</p>";
		}

		if (!empty($login_posto)) {
			$sql = "SELECT tbl_posto_fabrica.codigo_posto,
						   tbl_posto.nome 
					FROM tbl_posto_fabrica
					JOIN tbl_posto USING(posto) 
					WHERE posto = $login_posto 
					AND fabrica = $login_fabrica";
			$res = pg_query($con, $sql);

			$codigo_posto = pg_fetch_result($res, 0, 'codigo_posto');
			$nome_posto = pg_fetch_result($res, 0, 'nome');

			$informacaoPosto = "<p>Posto: $codigo_posto - $nome_posto</p>";
		}

		if (pg_last_error()) {
			$subjectResposta = "Interação não inserida {$hd_chamado} - ".date("d/m/Y")." Black&Decker";
			$corpoResposta   = "
				<h3>Erro ao interagir no chamado {$hd_chamado}</h3>
				<p>Data e hora: ".date("d/m/Y H:i:s")."</p>
				$informacaoAdmin
				$informacaoPosto
				<p>Interação: {$resposta} </p>
			";
		} else {
			$subjectResposta = "{$hd_chamado} - ".date("d/m/Y")." Black&Decker";
			$corpoResposta   = "
				<h3>Interação no chamado {$hd_chamado}</h3>
				<p>Data e hora: ".date("d/m/Y H:i:s")."</p>
				$informacaoAdmin
				$informacaoPosto
				<p>Interação: {$resposta} </p>
			";
		}

        $mailerLogBlack->IsHTML();
        $mailerLogBlack->Subject = $subjectResposta;
        $mailerLogBlack->Body = $corpoResposta;
        $mailerLogBlack->Send();

	}

	if ( ! is_resource($res) ) {
		echo 'Erro resposta: '.pg_last_error($con);
		return false;
	}

	return $hd_chamado_item;
}

/**
 * Retorna as respostas de determinado chamado
 *
 * @param int $hd_chamado
 * @return array
 */
function hdBuscarRespostas($hd_chamado, $ultima = false, $sort = false, $ligacao = false, $integracao = false) {
	global $con,$login_fabrica;

	if ($login_fabrica == 3 and $ultima == true) {
		$limit = "LIMIT 1";
	}
	if($login_fabrica == 24){
		$tincaso = " tincaso, ";
	}

	$hd_chamado = (int) $hd_chamado;
	$fabrica    = pg_escape_string($login_fabrica);

	if ($ligacao == true) {
		$colunaligacao = ", tbl_hd_chamado_item_externo.id_ligacao";
		$joinligacao = "LEFT JOIN tbl_hd_chamado_item_externo ON tbl_hd_chamado_item_externo.hd_chamado_item = tbl_hd_chamado_item.hd_chamado_item";
	}

	if ($integracao === true) {
		$colunaIntegracao = ", tbl_hd_chamado_item_externo.id_integracao";
		$joinIntegracao = " LEFT JOIN tbl_hd_chamado_item_externo ON tbl_hd_chamado_item_externo.hd_chamado_item = tbl_hd_chamado_item.hd_chamado_item";
	}

	$sql = "SELECT
			tbl_hd_chamado_item.hd_chamado_item    ,
			to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY HH24:MI:SS') as data,
			tbl_hd_chamado_item.comentario         ,
			tbl_admin.login as atendente   		   ,
			tbl_admin.nome_completo as nome_completo   		   ,
			tbl_posto.posto						   ,
			tbl_posto.nome as posto_nome		   ,
			tbl_hd_chamado_item.interno            ,
			tbl_hd_chamado_item.status_item        ,
			$tincaso
			tbl_hd_chamado_item.enviar_email
			$colunaligacao
			{$colunaIntegracao}
		FROM tbl_hd_chamado_item
		LEFT JOIN tbl_admin USING (admin)
		LEFT JOIN tbl_posto ON (tbl_hd_chamado_item.posto = tbl_posto.posto)
		INNER JOIN tbl_hd_chamado ON (tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado)
		$joinligacao
		{$joinIntegracao}
		WHERE tbl_hd_chamado_item.hd_chamado = {$hd_chamado}
		AND   tbl_hd_chamado.fabrica_responsavel = {$fabrica}
		AND   tbl_hd_chamado_item.comentario is not  null
		/* a fabrica fricon quer ver os chamados internos */
		AND   (tbl_hd_chamado_item.interno IS NOT TRUE  OR tbl_hd_chamado.fabrica_responsavel = {$fabrica} )
		ORDER BY tbl_hd_chamado_item.data ASC, tbl_hd_chamado_item.hd_chamado_item ASC
		$limit";
	$res  = pg_query($con,$sql);
	$rows = array();
	if ( ! is_resource($res) || pg_num_rows($res) <= 0 ) {
		return $rows;
	}

	while ($row = pg_fetch_assoc($res)) {
		$rows[] = $row;
	}

	if ($sort === true) {
		krsort($rows);
	}

	return $rows;
}

function hdUltimaResposta($hd_chamado){
	global $con,$login_fabrica;

	$sql = "SELECT status_item
				FROM tbl_hd_chamado_item
				WHERE hd_chamado = $hd_chamado
				AND interno IS NOT TRUE
				ORDER BY hd_chamado_item DESC LIMIT 1";
	$res  = pg_query($con,$sql);

	if ( pg_num_rows($res) > 0 ) {

		$resp = pg_fetch_result($res,0,0);

		if($resp == "Resp.Conclusiva"){

			$sql = "SELECT hd_chamado_anterior FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$hd_chamado_aux = pg_result($res,0,0);
				if(empty($hd_chamado_aux)){
					$hd_chamado_aux = $hd_chamado;
				}
			} else{
				$hd_chamado_aux = $hd_chamado;
			}
			$sql = "INSERT INTO tbl_hd_chamado (fabrica, fabrica_responsavel, atendente, admin, posto, categoria,status, titulo,hd_chamado_anterior,duracao)
					(SELECT fabrica, fabrica_responsavel, atendente, admin, posto, categoria,'Ag. Fábrica', titulo,$hd_chamado_aux,duracao FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado) RETURNING hd_chamado";
			$res  = pg_query($con,$sql);
			$hd_chamado_novo = pg_result($res,0,0);

			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, data, comentario, admin, posto, interno, status_item, enviar_email)
			(SELECT $hd_chamado_novo, data, comentario, admin, posto, interno, status_item, enviar_email FROM tbl_hd_chamado_item WHERE hd_chamado = $hd_chamado)";
			$res = pg_query($con,$sql);

			if ($login_fabrica == 1) {
				$sql = "INSERT INTO tbl_hd_chamado_extra (hd_chamado, nome, endereco, numero, complemento, cep, fone,
							email, cpf,cidade,produto,os,pedido,garantia,array_campos_adicionais)
							(SELECT $hd_chamado_novo, nome, endereco, numero, complemento, cep, fone,
							email, cpf,cidade,produto,os,pedido,garantia,array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado)";
				$res = pg_query($con,$sql);				
			} else {
				$sql = "INSERT INTO tbl_hd_chamado_extra (hd_chamado, nome, endereco, numero, complemento, cep, fone,
								email, cpf,cidade,produto,os,pedido,garantia)
								(SELECT $hd_chamado_novo, nome, endereco, numero, complemento, cep, fone,
								email, cpf,cidade,produto,os,pedido,garantia FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado)";
				$res = pg_query($con,$sql);
			}

			$sql = "INSERT INTO tbl_hd_chamado_posto
					(hd_chamado,tipo,fone,email,nome_cliente,
					 atendente,banco,agencia,conta,data_pedido,peca_faltante,linha_atendimento,hd_chamado_sac)
					(SELECT $hd_chamado_novo,tipo,fone,email,nome_cliente,
					 atendente,banco,agencia,conta,data_pedido,peca_faltante,linha_atendimento,hd_chamado_sac
					 FROM tbl_hd_chamado_posto WHERE hd_chamado = $hd_chamado)";
			$res = pg_query($con,$sql);

			$sql = " UPDATE tbl_hd_chamado SET status = 'Resolvido',data_resolvido = current_timestamp
						WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con,$sql);

			 if ($login_fabrica == 3) {
			 	$sql_ref_id = "
			 		SELECT referencia_id AS hd_chamado_item_velho FROM tbl_tdocs
				 		WHERE referencia_id IN (
				 			SELECT hd_chamado_item FROM tbl_hd_chamado_item WHERE hd_chamado = $hd_chamado
				 		)
				 	";
				 $res_ref_id = pg_query($con, $sql_ref_id);

				 while ($fetch_ref_id = pg_fetch_assoc($res_ref_id)) {
				 	$hd_chamado_item_velho = $fetch_ref_id['hd_chamado_item_velho'];

				 	$sql_hd_chamado_item_novo = "
				 		SELECT hd_chamado_item AS hd_chamado_item_novo
				 		FROM tbl_hd_chamado_item
				 		WHERE comentario = (
				 			SELECT comentario FROM tbl_hd_chamado_item WHERE hd_chamado_item = $hd_chamado_item_velho
			 			)
			 			AND hd_chamado = $hd_chamado_novo"; 
			 		$res_hd_chamado_item_novo = pg_query($con, $sql_hd_chamado_item_novo);
		 			$hd_chamado_item_novo = pg_fetch_result($res_hd_chamado_item_novo, 0, 'hd_chamado_item_novo');

		 			$begin = pg_query($con, "BEGIN");
		 			$commit = true;

		 			$link_anexo_novo = "<br><br>Para visualizar o anexo, <a href=\"helpdesk_cadastrar.php?hd_chamado=$hd_chamado_novo\" target=\"_blank\">clique aqui.</a>";

		 			$up_chamado_item = "
		 				UPDATE tbl_hd_chamado_item 
		 					SET comentario = comentario || '{$link_anexo_novo}'
		 				WHERE hd_chamado_item = $hd_chamado_item_velho";
		 			$res_chamado_item = pg_query($con, $up_chamado_item);

		 			if (pg_affected_rows($res_chamado_item) > 1) {
		 				$rollback = pg_query($con, "ROLLBACK");
		 				$commit = false;
		 			}

		 			$up_tdocs = "UPDATE tbl_tdocs SET referencia_id = $hd_chamado_item_novo WHERE referencia_id = $hd_chamado_item_velho";
		 			$res_tdocs = pg_query($con, $up_tdocs);

		 			if (pg_affected_rows($res_tdocs) > 1) {
		 				$rollback = pg_query($con, "ROLLBACK");
		 				$commit = false;
		 			}

		 			if (true === $commit) {
		 				$commit = pg_query($con, "COMMIT");
		 			}
				 }

			 }

			return $hd_chamado_novo;

		} else {
			return null;
		}
	} else {
		return null;
	}
}

function hdChamadoAnterior($hd_chamado,$hd_chamado_anterior){
	global $con,$login_fabrica;

	if(!empty($hd_chamado_anterior)){
		$sql = "SELECT hd_chamado,hd_chamado_anterior
			FROM tbl_hd_chamado
			WHERE hd_chamado_anterior = $hd_chamado_anterior
			AND fabrica = $login_fabrica
			AND fabrica_responsavel = $login_fabrica
			ORDER BY data";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0 ){
			for($i = 0; $i < pg_num_rows($res); $i++){

				$hd_chamado_aux = pg_result($res,$i,'hd_chamado');
				$hd_chamado_anterior_aux = pg_result($res,$i,'hd_chamado_anterior')."-".($i+1);

				if($hd_chamado_aux == $hd_chamado){
					return $hd_chamado_anterior_aux;
				}
			}
		}
	}
	return $hd_chamado;
}

function hdChamadoAnterior2($hd_chamado){
	global $con,$login_fabrica;

	if(!empty($hd_chamado)){
		$cont_sac = strripos($hd_chamado,'SAC');
		if ($cont_sac === false ) {
			$sql = "SELECT hd_chamado_anterior from tbl_hd_chamado where hd_chamado = $hd_chamado AND hd_chamado_anterior IS NOT NULL";
		}else{
			$sql = "SELECT hd_chamado_anterior from tbl_hd_chamado where protocolo_cliente = '$hd_chamado' AND hd_chamado_anterior IS NOT NULL";
		}

		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0 ){
			$hd_chamado_anterior = pg_result($res,0,0);

			$sql = "SELECT hd_chamado,hd_chamado_anterior
				FROM tbl_hd_chamado WHERE hd_chamado_anterior = $hd_chamado_anterior
				AND fabrica = $login_fabrica
				AND fabrica_responsavel = $login_fabrica
				ORDER BY data";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0 ){
				for($i = 0; $i < pg_num_rows($res); $i++){

					$hd_chamado_aux = pg_result($res,$i,'hd_chamado');
					$hd_chamado_anterior_aux = pg_result($res,$i,'hd_chamado_anterior')."-".($i+1);

					if($hd_chamado_aux == $hd_chamado){
						return $hd_chamado_anterior_aux;
					}
				}
			}
		}
	}
	return $hd_chamado;
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


if(!function_exists('retira_acentos')){
	function retira_acentos( $texto ){
		$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@" );
		$array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" );
		return str_replace( $array1, $array2, $texto );
	}
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
 * @param string &$erro_msg. (default: '') Se existe algum array que trata os erros do formulário, fornece-a aqui, e os erros serão acrescentados a ele
 * @return boolean TRUE caso o upload dê certo
 */
function hdCadastrarUpload($name, $hd_resposta_item, $erro_msg = '') {
	if ( ! isset($_FILES[$name]) ) { return false; }
	$file = $_FILES[$name];

	if ( $file['error'] != 0 ) {
		$erro_msg[] = 'Ocorreu um erro no upload do anexo!';
	} else if ( $file['size'] > 2048000 ) {
		$erro_msg[] = 'Arquivo de anexo muito grande!';
	}
	$dir     = TC_HD_UPLOAD_DIR;

	if ( ! is_writeable($dir) ) {
		$erro_msg[] = "Não foi possível salvar o anexo, diretório de destino sem permissão de escrita!";
	}
	if ( ! empty($erro_msg) ) { return false; }
	$filename = hdNomeArquivoUpload($hd_resposta_item,$file['name']);
	if ( ! move_uploaded_file($file['tmp_name'],$dir.$filename) ) {
		$erro_msg[] = "Erro ao salvar anexo !";
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
	 	#echo "<p><b>$fieldname</b>: $data</p>\n";
		return ((!is_array($data) and strlen($data)==0) or (is_array($data) and count($data)==0)) ? $returns : $data;
	}
}

if (!function_exists('CreateHTMLOption')) {
	function CreateHTMLOption($valor,$nome,$valor_sel='') {
		if (!$valor) $valor = $nome;
		$sel = ($valor_sel == $valor) ? ' SELECTED':'';
		return "\t\t\t\t<option value='$valor'$sel>$nome</option>\n";
	}
}

if (!function_exists('CreateHTMLOptionHelpdesk')) {
	function CreateHTMLOptionHelpdesk($valor,$nome,$valor_sel='') {
		if (!$valor) $valor = $nome;
		$sel = ($valor_sel == $valor) ? ' SELECTED':'';
		return "\t\t\t\t<option value='$valor'$sel>$nome</option>\n";
	}
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


if (!function_exists('convData')) {
    function convData($data) {
        if(substr($data,2,1) == "/") {
                $dd = substr($data, 0,2);
                $mm = substr($data, 3,2);
                $aa = substr($data, 6,4);
                $time = substr($data, 11,8);
                if($time != "")
                        $data = $aa."-".$mm."-".$dd." ".$time;
                else
                        $data = $aa."-".$mm."-".$dd;
        }else {
                $dd = substr($data, 8,2);
                $mm = substr($data, 5,2);
                $aa = substr($data, 0,4);
                $time = substr($data, 11,8);
                if($time != "")
                        $data = $dd."/".$mm."/".$aa." ".$time;
                else
                        $data = $dd."/".$mm."/".$aa;
        }

        if($data=='//') {
                $data = '';
        }

        return $data;
    }
}

$status_array = array(
	'EM ACOMPANHAMENTO'    => 'status_amarelo.gif',
	'EM ACOMPANHAMENTO5'   => 'status_vermelho.gif',
	'RESPOSTA CONCLUSIVA'  => 'status_azul.gif',
	'RESOLVIDO'            => 'status_verde.gif',
	'RESOLVIDO POSTO'      => 'status_verde.gif',
	'CANCELADO'            => 'status_preto.png',
	'AGUARDANDO FáBRICA'   => 'status_cinza.gif',
	'AGUARDANDO POSTO'     => 'status_laranja.png',
	'INTERNO'              => 'status_salmao.png',
	'AGUARDANDO INTERAçãO' => 'status_amarelo.gif',
	'DADOS POSTO'          => 'status_amarelo.gif',
);

$status_array_helpdesk = array(
	'EM ACOMPANHAMENTO'   => 'status_amarelo.gif',
	'EM ACOMPANHAMENTO5'  => 'status_vermelho.gif',
	'RESPOSTA CONCLUSIVA' => 'status_azul.gif',
	'RESPOSTA PENDENTE'   => 'status_verde.gif',
	'RESOLVIDO'           => 'status_verde.gif',
	'RESOLVIDO POSTO'     => 'status_verde.gif',
	'CANCELADO'           => 'status_vermelho.gif',
	'AGUARDANDO FáBRICA'  => 'status_preto.png',
	'AGUARDANDO POSTO'    => 'status_laranja.png',
	'INTERNO'             => 'status_salmao.png',
	'AG. INTERA'          => 'status_amarelo.gif',
	'DADOS POSTO'         => 'status_amarelo.gif',
);

#Mostra os dias e horas
function calculaTempoAtendimento($tempo){

	$tempo = date('d-H:i:s', $tempo - date('Z'));

	list($dias,$horas) = explode('-',$tempo);
	$dias--;
	if($dias > 0){
		$dias = ($dias == 1) ? $dias." dia" : $dias." dias";
	} else {
		$dias = "";
	}

	$tempo_calculado = "$dias $horas";
	return $tempo_calculado;
}

#Mostra apenas as horas
function calculaHorasAtendimento($tempo){

	$tempo = date('d-H:i:s', $tempo - date('Z'));

	list($dias,$horas) = explode('-',$tempo);
	$dias--;
	if($dias > 0){
		list($h,$m,$s) = explode(':',$horas);
		$h = $h + ($dias * 24);
		$horas = implode(':',array($h,$m,$s));
	}

	$tempo_calculado = "$horas";
	return $tempo_calculado;
}

function geraExcel($aChamados){
	global $con,$login_fabrica;

   	if($login_fabrica == 3){
        $titulos1 = "<th bgcolor='#596D9B'>OS</th><th bgcolor='#596D9B'>Produto</th><th bgcolor='#596D9B'>Garantia</th>";
        $titulos2 = "<th bgcolor='#596D9B'>Defeito</th><th bgcolor='#596D9B'>Solução</th>";
   	}

   	if ($login_fabrica == 1) {
   		$titulos0 = "<th bgcolor='#596D9B'>Código do Posto</th><th bgcolor='#596D9B'>Nome do Posto</th>";
   	} else {
   		$titulos0 = "<th bgcolor='#596D9B'>Posto</th>";
   	}

	$resultado = "
				<table border='1'>
				<thead>
					<tr>
						<th bgcolor='#596D9B'>Chamado</th>
						{$titulos0}
						{$titulos1}
						<th bgcolor='#596D9B'>Abertura</th>
						<th bgcolor='#596D9B'>Fechamento</th>".
						(($login_fabrica == 1)?"<th bgcolor='#596D9B'>Data Último Retorno Suporte</th><th bgcolor='#596D9B'>Data último Retorno Posto</th>":"")
						."
						<th bgcolor='#596D9B'>Resolvido</th>
						{$titulos2}
						<th bgcolor='#596D9B'>Tipo Resposta</th>
						<th bgcolor='#596D9B'>Tempo Atendimento Parcial</th>
						<th bgcolor='#596D9B'>Tempo Atendimento Total</th>
						<th bgcolor='#596D9B'>Tipo Solicitação</th>
						<th bgcolor='#596D9B'>Atendente</th>".
                        (($login_fabrica == 1)?"<th bgcolor='#596D9B'>Avaliação</th><th bgcolor='#596D9B'>Observação</th><th bgcolor='#596D9B' colspan='2'>Informações Adicionais</th>":"")
						."<th bgcolor='#596D9B'>Status</th>
					</tr>
				</thead>
				<tbody>";

	foreach ($aChamados as $i=>$linha){
		if ($login_fabrica == 1) {
			$aux_hd                = $linha["hd_chamado"];
			$campos_extras         = "";
			$sub_categoria         = "";
			$descricao_atendimento = "";

			$aux_sql = "SELECT array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = $aux_hd LIMIT 1";
			$aux_res = pg_query($con, $aux_sql);

			$array_campos_adicionais = json_decode(pg_fetch_result($aux_res, 0, 'array_campos_adicionais'), true);

			if (isset($array_campos_adicionais["pedidos"]) && !empty($array_campos_adicionais["pedidos"])) {
				$pedidos       = $array_campos_adicionais["pedidos"];
				$duvida_pedido = $array_campos_adicionais["pedidos"][0]["duvida_pedido"];

				$descricao_atendimento = "Dúvidas sobre Pedido";
				$sub_categoria         = "Pedido(s)";

				for ($z=0; $z < count($pedidos); $z++) { 
					if (isset($pedidos[$z]["distribuidor"]) && !empty($pedidos[$z]["distribuidor"])) {
						$campos_extras .= $pedidos[$z]["numero_pedido"] . " - " . $pedidos[$z]["data_pedido"] . " - " . $pedidos[$z]["distribuidor"] . "<br>";
					} else {
						$campos_extras .= $pedidos[$z]["numero_pedido"] . " - " . $pedidos[$z]["data_pedido"] . "<br>";
					}
				}
			} else if (isset($array_campos_adicionais["pecas"]) && !empty($array_campos_adicionais["pecas"])) {
				$pecas        = $array_campos_adicionais["pecas"];
				$duvida_pecas = $array_campos_adicionais["pecas"][0]["duvida_pecas"];

				$descricao_atendimento = "Dúvida sobre peças";
				$sub_categoria         = "Peça(s)";

				for ($z=0; $z < count($pecas); $z++) { 
					if (isset($pecas[$z]["codigo_peca"]) && !empty($pecas[$z]["codigo_peca"])) {
						$campos_extras .= utf8_decode($pecas[$z]["codigo_peca"]) . " - " . utf8_decode($pecas[$z]["descricao_peca"])  . "<br>";
					} else if (isset($pecas[$z]["descricao_peca"]) && !empty($pecas[$z]["descricao_peca"])) {
						$campos_extras .= utf8_decode($pecas[$z]["descricao_peca"]) . "<br>";
					}
				}
			} else if (isset($array_campos_adicionais["produtos"]) && !empty($array_campos_adicionais["produtos"])) {
				$produtos        = $array_campos_adicionais["produtos"];
				$duvida_produto = $array_campos_adicionais["produtos"][0]["duvida_produto"];

				$descricao_atendimento = "Dúvidas sobre produtos";
				$sub_categoria         = "Produto(s)";

				for ($z=0; $z < count($produtos); $z++) { 
					if (isset($produtos[$z]["codigo_produto"]) && !empty($produtos[$z]["codigo_produto"])) {
						$campos_extras .= $produtos[$z]["codigo_produto"] . " - " . $produtos[$z]["descricao_produto"] . "<br>";
					} else {
						$campos_extras .= $produtos[$z]["descricao_produto"] . "<br>";
					}
				}
			} else if (isset($array_campos_adicionais["ordem_servico"]) && !empty($array_campos_adicionais["ordem_servico"])) {
				$ordem_servico         = $array_campos_adicionais["ordem_servico"];
				$descricao_atendimento = "Problemas no fechamento da O.S.";
				$sub_categoria         = "O.S.(s)";

				for ($z=0; $z < count($ordem_servico); $z++) {
				 	$campos_extras .= $ordem_servico[$z]["ordem_servico"] . "<br>";
				}
			}
		}

		$cor = ($i%2)?'#91C8FF':'#F1F4FA';
		if(!empty($linha['hd_chamado'])) {
			$sql="SELECT tbl_admin.nome_completo,
						 tbl_admin.admin,
						 tbl_hd_chamado.atendente,
						 to_char(tbl_hd_chamado_item.data, 'DD/MM/YYYY HH24:MI') as data,
						 tbl_hd_chamado_item.interno
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_item USING(hd_chamado)
					JOIN tbl_admin ON tbl_hd_chamado_item.admin = tbl_admin.admin
					WHERE tbl_hd_chamado.hd_chamado = ".$linha['hd_chamado']."
					LIMIT 1";
			//echo nl2br($sql);
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$admin = pg_fetch_result($res,0,'admin');
				$xatendente = pg_fetch_result($res,0,'atendente');
				$interno = pg_fetch_result($res,0,'interno');

				if($interno =='t' and !empty($admin) and $admin != $xatendente) {
					$cor = '#FF0000';
				}
			}
		}


		$resultado .= "<tr class='conteudo' align='center'>";
		$resultado .= "<td bgcolor='$cor' nowrap>".hdChamadoAnterior2($linha['hd_chamado'])."</td>";

		if (!in_array($login_fabrica,[1])) {
			$resultado .= "<td bgcolor='$cor' nowrap>".$linha['codigo_posto']." - ".$linha['posto_nome']."</td>";
		} else {
			$resultado .= "<td bgcolor='$cor' nowrap>".$linha['codigo_posto']."</td>";
			$resultado .= "<td bgcolor='$cor' nowrap>".$linha['posto_nome']."</td>";
		}

		
		

        if($login_fabrica == 3){
                $garantia = ($linha['garantia'] == "t") ? "Sim" : "Não";
                $resultado .= "<td bgcolor='$cor'>".$linha['sua_os']."</td>";
                $resultado .= "<td bgcolor='$cor'>".$linha['referencia']."</td>";
                $resultado .= "<td bgcolor='$cor'>".$garantia."</td>";
        }

		$resultado .= "<td bgcolor='$cor'>".$linha['data']."</td>";

		$resolvido_data = !empty($linha['data_resolvido']) ? $linha['data_resolvido'] : $linha['resolvido'];
		
		if(!empty($linha['resolvido']) && $login_fabrica != 3) {
			list($dtr,$hr) = explode(" ", $linha['resolvido']);
			list($ar,$mr,$dr) = explode("-", $dtr);

			if (strlen(trim($ar)) == 4) {
				$data_r = $dr."/".$mr."/".$ar;
				list($hora_r,$mls) = explode(".", $hr);
				$resolvido_data = $data_r." ".$hora_r;
				$resolvido_novo = $resolvido_data;
			} else {
				$resolvido_data = $linha['resolvido'];
				$resolvido_novo = $resolvido_data;
			}
		}

		if ($login_fabrica == 3) {
			$resolvido_novo = $linha['resolvido'];
		}

		$resultado .= (in_array($login_fabrica, [1,3])) ? "<td bgcolor='$cor'>".$resolvido_data."</td>" : "";
		$resultado .= "<td bgcolor='$cor'>".$resolvido_novo."</td>";

		if ($login_fabrica == 1) {
			$aux_sql = "SELECT TO_CHAR(data, 'DD/MM/YYYY') AS ultima_admin FROM tbl_hd_chamado_item WHERE hd_chamado = " . $linha['hd_chamado'] . " AND posto IS NULL ORDER BY data DESC LIMIT 1";
			$aux_res = pg_query($con, $aux_sql);
			$aux_val = pg_fetch_result($aux_res, 0, 'ultima_admin');
			$resultado .= "<td bgcolor='$cor'>".$aux_val."</td>";

			$aux_sql = "SELECT TO_CHAR(data, 'DD/MM/YYYY') AS ultima_posto FROM tbl_hd_chamado_item WHERE hd_chamado = " . $linha['hd_chamado'] . " AND posto IS NOT NULL ORDER BY data DESC LIMIT 1";
			$aux_res = pg_query($con, $aux_sql);
			$aux_val = pg_fetch_result($aux_res, 0, 'ultima_posto');
			$resultado .= "<td bgcolor='$cor'>".$aux_val."</td>";
		}

		if($login_fabrica == 3){
			$resultado .= "<td bgcolor='$cor'>".$linha['defeito']."</td>";
			$resultado .= "<td bgcolor='$cor'>".$linha['solucao']."</td>";
		}


		if(strlen($linha['categoria']) > 0) {
			$categoria = $linha['categoria_desc'];
		}

		if(strlen($linha['status']) > 0){
			switch($linha['status']) {
				case ('Ag. Posto')	: $status	= "Aguardando Posto"; break;
				case ('Ag. Fábrica'): $status	= "Aguardando Fábrica"; break;
				default:			  $status	= $linha['status'];
			}
		}

		$tempo_atendimento = str_replace("day","dia",$linha['tempo_atendimento']);
		$tempo_atendimento = explode('.',$tempo_atendimento);

		if(!empty($linha['duracao'])){
			$tempo_atendimento_total = calculaHorasAtendimento($linha['duracao']);
			if(in_array($linha['ultima_resposta'],array("Resolvido Posto","Resolvido")) OR strpos($linha['ultima_resposta'],'Resolvido Posto')){
				$tempo_atendimento[0] = $tempo_atendimento_total;
			}else {
				if($linha['ultima_resposta'] == "encerrar_acomp"){
					$tempo_atendimento[0] = $tempo_atendimento_total;
				}else{
					if($linha['ultima_resposta'] != "Resp.Conclusiva"){

						$abertura = explode('.',$linha['data_abertura']);
						$tempo_parcial = strtotime('now') - strtotime($abertura[0]);
						$tempo_parcial += $linha['duracao'];
						$tempo_atendimento[0] = calculaHorasAtendimento($tempo_parcial);
						$tempo_atendimento_total = "";
						
					}else{
						$tempo_atendimento_total = "";
					}
				}
			}

		}else{
			$abertura = explode('.',$linha['data_abertura']);
			$tempo_parcial = strtotime('now') - strtotime($abertura[0]);
			$tempo_parcial += $linha['duracao'];
			$tempo_atendimento[0] = calculaHorasAtendimento($tempo_parcial);
		}

		if (in_array($login_fabrica, [3])) {

			//buscar a data anterior
			$sqlUltimaTransferencia = " SELECT data::timestamp(0) as data
										FROM tbl_hd_chamado_item
										WHERE hd_chamado = ".$linha['hd_chamado']."
										AND comentario ~~ 'Atendimento transferido por%'
										LIMIT 1";
			$resUltimaTransferencia = pg_query($con, $sqlUltimaTransferencia);

			if (!empty($linha['duracao']) && (in_array($linha['ultima_resposta'],array("Resolvido Posto","Resolvido","encerrar_acomp")) OR strpos($linha['ultima_resposta'],'Resolvido Posto'))) {

				$tempo_atendimento_total = calculaHorasAtendimento($linha['duracao']);

			} else if (pg_num_rows($resUltimaTransferencia) > 0) {

				$tempo_atendimento_total = strtotime('now') - strtotime(pg_fetch_result($resUltimaTransferencia, 0, "data"));
				$tempo_atendimento_total = calculaHorasAtendimento($tempo_atendimento_total);

			} else {
				
				$abertura = explode('.',$linha['data_abertura']);

				$tempo_atendimento_total = strtotime('now') - strtotime($abertura[0]);
				$tempo_atendimento_total = calculaHorasAtendimento($tempo_atendimento_total);
				
			}
			
		}

		$sqlResp = "SELECT status_item FROM tbl_hd_chamado_item WHERE hd_chamado = ".$linha['hd_chamado']." AND admin IS NOT NULL ORDER BY hd_chamado_item DESC LIMIT 1";
		$resResp = pg_query($con,$sqlResp);
		if(pg_num_rows($resResp) > 0){

			$resposta_tipo = pg_result($resResp,0,0);
			switch($resposta_tipo) {
				case 'Em Acomp.' : $tipo_resposta ="Em Acompanhamento"; break;
				case 'Resp.Conclusiva' :
				case 'Resolvido Posto 1':
				case 'Resolvido Posto 2':
				case 'Resolvido Posto 3':
				case 'Resolvido':
				case 'encerrar_acomp':
					$tipo_resposta ="Resposta Conclusiva";
				break;
			}
			list($ultima_interacao,$restante) = explode(' ',$linha['data_ultima_interacao']);

			if(strtotime($ultima_interacao.'+5 days') < strtotime('today') AND $tipo_resposta == "Em Acompanhamento"){
				$tipo_resposta = "Em Acompanhamento com mais de 120 horas sem interação";
			}
		}

		if($login_fabrica == 3 and $categoria == "peca_defeito"){
			$categoria = "peca_recebida_quebrada";
		}

		if ($login_fabrica == 1 && strlen($descricao_atendimento) > 0) {
			$categoria = $descricao_atendimento;
		}

		if (!in_array($login_fabrica, [3])) {
			$resultado .= "<td bgcolor='$cor'>".$linha['data_resolvido']."</td>";
		}
		
		$resultado .= "<td bgcolor='$cor'>".$tipo_resposta."</td>";
		$resultado .= "<td bgcolor='$cor'>".$tempo_atendimento[0]."</td>";
		$resultado .= "<td bgcolor='$cor'>".$tempo_atendimento_total."</td>";
		$resultado .= "<td bgcolor='$cor'>".str_replace('[]', ', ', $categoria)."</td>";
		$resultado .= "<td bgcolor='$cor'>".$linha['atendente_ultimo_login']."</td>";
        if($login_fabrica == 1){
            $jsonCamposAdicionais = json_decode($linha['array_campos_adicionais'],true);
            $avaliacaoPontuacao = isset($jsonCamposAdicionais['avaliacao_pontuacao'])?$jsonCamposAdicionais['avaliacao_pontuacao']:'-';
            $avaliacaoMensagem = empty($jsonCamposAdicionais['avaliacao_mensagem'])?'-':utf8_decode($jsonCamposAdicionais['avaliacao_mensagem']);
            $resultado .= "<td bgcolor='$cor'>".$avaliacaoPontuacao."</td>";
            $resultado .= "<td bgcolor='$cor'>".$avaliacaoMensagem."</td>";
            $resultado .= "<td bgcolor='$cor'>".$sub_categoria."</td>";
            $resultado .= "<td bgcolor='$cor'>".$campos_extras."</td>";
        }

        if($login_fabrica == 3){
           if(strlen($linha["sua_os"]) > 0 && strlen($linha["defeito"]) > 0 && strlen($linha["solucao"]) > 0 && strlen($linha["resolvido"]) > 0){
                   $status = "Automatico";
           }
        }

		$resultado .= "<td bgcolor='$cor' nowrap>".$status."</td>";

		$resultado .= "</tr>";
		if ($linha['status'] == 'Cancelado') {
			$aRespostas = hdBuscarRespostas($linha['hd_chamado']);
			$ultima_resposta = end($aRespostas);
			$motivo_cancelado = $ultima_resposta['comentario'];
			$resultado .= "<tr>";
			$resultado .= "<td colspan='9' bgcolor='$cor'>".$motivo_cancelado."</td>";
			$resultado .= "</tr>";
			unset($aRespostas, $ultima_resposta, $motivo_cancelado);
		}

		$tipo_resposta = "";
	}
	$resultado .= "</tbody>";
	$resultado .= "</table>";
	$data = date("Y-m-d-H-i");
	$fp = fopen("xls/relatorio-helpdesk-$data.xls","w");
	fwrite($fp,$resultado);
	fclose($fp);

	$botao = "<center><input type='button' value='Download Excel' onclick='javascript:window.open(\"xls/relatorio-helpdesk-$data.xls\")'></center>";

	return $botao;

}
