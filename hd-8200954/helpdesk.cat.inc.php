<?php
/**
 *  Array com a configuração dos tipos de chamados ($categorias)
 *      key:    		nome da categoria (o que vai pro banco)
 *      descricao:		Descrição da categoria (o que vai pra tela)
 *      atendente:  	'posto' para procurar pela função hdBuscarAtendentePorPosto, ou o 'tbl_admin.admin'
 *      campos:     	array com os campos a mostrar para esta categoria
 *      campos_obrig:	array com os campos que são obrigatórios
 **/
return Array(
	'atualiza_cadastro' => array (
		'descricao'    => 'Atualização de cadastro',
		'atendente'    => 'posto',
		'campos'       => array('usuario_sac','tipo_atualizacao'),
		'campos_obrig' => array('usuario_sac','tipo_atualizacao'),
		'no_fabrica'   => array(3)
	),
	'comunicar_procon' => array (
		'descricao'    => 'Comunicar PROCON ou Casos Judiciais',
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','produto'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3)
	),
	'digitacao_fechamento' => array (
		'descricao'    => 'Digitação e/ou fechamento de OSs',
		'atendente'    => 'posto',
		'campos'       => array('usuario_sac','garantia','garantia'),
		'campos_obrig' => array('usuario_sac','referencia','os'),
		'no_fabrica'   => array(1,3)
	),
	'duvida_troca' => array (
		'descricao'    => 'Dúvidas na troca de produto',
		'atendente'    => 'posto',
		'campos'       => array('garantia','referencia'),
		'campos_obrig' => array('usuario_sac','referencia'),
		'no_fabrica'   => array(42, 3)
	),
	'duvida_produto' => array (
		'descricao'     => (($login_fabrica == 42) ? 'Suporte Técnico' : 'Dúvida técnica sobre o produto'),
		'atendente'	 => 'posto',
		'campos'	 => array('garantia','referencia','os'),
		'campos_obrig' => array('usuario_sac','referencia'),
		'no_fabrica'    => array(3)
	),
	'duvida_revenda' => array (
		'descricao'     => 'Dúvidas sobre atendimento à revenda',
		'atendente'	 => 'posto',
		'campos'	 => array('garantia','referencia','os'),
		'campos_obrig' => array('usuario_sac'),
		'no_fabrica'    => array(42, 3)
	),
	'duvida_peca_bloqueada_sempreco' => array(
		'descricao'    => 'Dúvidas de peças bloqueadas/sem preço',
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 42)
	),
	'duvida_administrativa' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas ADM [] NF [] ABERTURA DE OS [] PROCEDIMENTOS TELECONTROL []' : 'Dúvidas Administrativas - NF, Abertura de OS, procedimentos Telecontrol'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_adm_devolucao_prod' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas ADM [] DEVOLUÇÃO DE PRODUTOS []' : 'Dúvidas Adm Devolução de Produtos'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_financeira_duplicata' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Financeiras [] DUPLICATAS []' : 'Dúvidas Financeiras Duplicatas'),
		'atendente'    => 'posto',
		'campos'       => array('duvida'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_financeira_mo' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Financeiras [] MÃO DE OBRA[]' : 'Dúvidas Financeiras Mão de Obra'),
		'atendente'    => 'posto',
		'campos'       => array('duvida'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_nserie_eletro_pessoal_refri_av' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Número de Série [] ELETRO [] REFRIGERAÇÃO [] CUIDADOS PESSOAIS [] A&V []' : 'Dúvidas números de série eletro / cuidados pessoais / refrigeração / áudio e vídeo'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'duvida_numero_serie_informatica' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Número de Série [] INFORMÁTICA []' : 'Dúvidas número de série linha de informática'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'duvida_tecnica_audio_video' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Técnicas [] ÁUDIO E VÍDEO []' : 'Dúvidas técnicas áudio e vídeo'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'duvida_tecnica_celular' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Técnicas [] CELULAR [] SMARTPHONE []' : 'Dúvidas técnicas linha celular'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'duvida_tecnica_eletro_pessoal_refri' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Técnicas [] ELETRO [] REFRIGERAÇÃO [] CUIDADOS PESSOAIS []' : 'Dúvidas técnicas linha eletro / cuidados pessoais / refrigeração'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'duvida_tecnica_informatica' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Técnicas [] INFORMÁTICA []' : 'Dúvidas técnicas linha de informática'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'duvida_cadastro' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas Cadastrais [] ALTERAÇÃO DE DADOS [] DESCREDENCIAMENTO []' : 'Dúvidas cadastrais (Alteração de dados/Descredenciamento)'),
		'atendente'    => 'posto',
		'campos'       => array(),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_os_bloqueada' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas [] OSs BLOQUEADAS []' : 'Dúvidas de OSs bloqueadas'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_bloqueada_fora_linha' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas [] PEÇAS BLOQUEADAS [] FORA DE LINHA []' : 'Dúvidas de peças bloqueadas (fora de linha)'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_compra_venda' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Dúvidas [] PEÇAS SEM PREÇO [] COMPRA [] VENDA []' : 'Dúvidas de peças sem preço, COMPRA/VENDA'),
		'atendente'    => 'posto',
		'campos'       => array('os','peca_faltante'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'peca_recebida_errada' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Peça recebida [] ERRADA [] COM DEFEITO []' : 'Peça recebida errada'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','peca_faltante'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'peca_defeito' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Peça recebida [] QUEBRADA []' : 'Peça com defeito / quebrada'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','pedido'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'pendencia_pedido_audio_video' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pendências de Pedidos [] ÁUDIO E VÍDEO []' : 'Pendências de pedidos linha áudios e vídeo'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','pedido'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'pendencia_pedido_pessoal' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pendências de Pedidos [] CUIDADOS PESSOAIS []' : 'Pendências de pedidos linha cuidados pessoais'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','pedido'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'pendencia_pedido_eletroportateis' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pendências de Pedidos [] ELETROPORTÁTEIS []' : 'Pendências de pedidos linha eletroportateis'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','pedido'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'pendencia_pedido_informatica' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pendências de Pedidos [] INFORMÁTICA []' : 'Pendências de pedidos linha informática'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','pedido'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'pendencia_pedido_lcd_led' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pendências de Pedidos [] LCD [] LED []' : 'Pendências de pedidos linha LCD/LED'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','pedido'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'rastreamento_nf' => array(
		'descricao'    => 'Rastreamento de NF',
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'soliticacao_lgr' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Solicitação [] COLETA LGR [] DEVOLUÇÃO OBRIGATÓRIA []' : 'Solicitação de coleta LGR (Devolução obrigatória)'),
		'atendente'    => 'posto',
		'campos'       => ($login_fabrica == 3) ? array('garantia','os') : array('garantia','peca','produto'),
		'campos_obrig' => ($login_fabrica == 3) ? array() : array('solic_coleta'),
		'no_fabrica'   => array(1, 42)
	),
	'dnf' => array(
		'descricao'    => (($login_fabrica == 3) ? 'DNF [] NOTIFICAÇÃO DE DIVERGÊNCIA []' : 'DNF - Notificação de Divergência de NF/Pç. Recebida com defeito/errada/divergente/faltante'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'duvida_cobertura_garantia_informatica' => array(
		'descricao'    => 'Dúvidas cobertura da garantia linha de informática',
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 42, 11)
	),
	'duvida_financeiro' => array(
		'descricao'    => 'Dúvidas financeiras (Duplicatas/MO)',
		'atendente'    => 'posto',
		'campos'       => array('os','garantia','duvida'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 42)
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
		'no_fabrica'   => array(1, 3, 42, 11)
	),
	'erro_embarque' => array (
		'descricao'    => (($login_fabrica == 1) ? 'Problemas com embarque' : 'Erro de embarque'),
		'atendente'    => 'posto',
		'campos'       => array(),
		'campos_obrig' => array(),
		'no_fabrica'   => array(42, 3, 11)
	),
	'falha_no_site' => array (
		'descricao'    => (($login_fabrica == 42 || $login_fabrica == 1) ? 'Falha no site Telecontrol' : 'Falha no site'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','referencia','os'),
		'campos_obrig' => array('usuario_sac'),
		'no_fabrica'   => array(3)
	),
	'geo_metais'        => array(
		'descricao'    => 'Metais sanitários e Fechaduras',
		'atendente'    => 'posto',
		'campos'       => array('garantia','referencia','os','produto_de'),
		'campos_obrig' => array('usuario_sac','produto_de'),
		'no_fabrica'   => array(1,3,11,42)
	),
	'manifestacao_sac' => array (
		'descricao'    => (($login_fabrica == 1) ? 'Chamados SAC' : 'Manifestação sobre o SAC'),
		'atendente'    => 'posto',
		'campos'       => array('os','referencia','garantia'),
		'campos_obrig' => array('usuario_sac','nome_cliente','hd_chamado_sac'),
		'no_fabrica'   => array(3, 11, 42)
	),
	'outros' => array (
		'descricao'    => 'Outros',
		'atendente'    => 'posto',
		'campos'       => array('usuario_sac'),
		'campos_obrig' => array('usuario_sac'),
		'no_fabrica'   => array(1,3)
	),
	'pendencias_de_pecas'=>array (
		'descricao'    => (($login_fabrica == 42) ? 'Pendência de peças / Pedidos de peças' : 'Pendências de peças com a fábrica'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','pedido','data_pedido','peca_faltante'),
		'campos_obrig' => array('usuario_sac','os','pedido','data_pedido','peca_faltante'),
		'no_fabrica'   => array(3)
	),
	'pend_pecas_dist' => array (
		'descricao'    => 'Pendências de peças com o distribuidor',
		'atendente'    => 'posto',
		'campos'       => array('pedido','data_pedido','os'),
		'campos_obrig' => array('usuario_sac','peca_faltante','distribuidor'),
		'no_fabrica'   => array(42, 3, 11)
	),
	'pagamento_garantia'=> array (
		'descricao'    => 'Pagamento das garantias',
		'atendente'    => 'posto',
		'campos'       => array('duvida'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(3)
	),
	'peca_recebida_defeito_informatica' => array(
		'descricao'    => 'Peças recebidas com defeito linha de informática',
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 42, 11)
	),
	'solicita_informacao_tecnica' => array (
		'descricao'    => 'Solicitação de Informação Técnica',
		'atendente'    => 'posto',
		'campos'       => ($login_fabrica==42)?array('garantia','os'):array(),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 11)
	),
	'solicitacao_coleta'=> array (
		'descricao'    => (($login_fabrica == 1) ? 'Devolução de Peça / Produto' : 'Solicitação de coleta'),
		'atendente'    => 'posto',
		'campos'       => array(),
		'campos_obrig' => array('solic_coleta'),
		'no_fabrica'   => array(3)
	),
	'sugestao_critica' => array (
		'descricao'    => 'Sugestões, Críticas, Reclamações ou Elogios',
		'atendente'    => 'posto',
		'campos'       => array(),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3)
	),
	'treinamento_makita'    => array (
		'descricao'    => 'Treinamentos Makita',
		'atendente'    => 'posto',
		'campos'       => array(),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 11)
	),
	'duvidas_telecontrol' => array (
		'descricao'     => 'Duvida na utilização Telecontrol',
		'atendente'     => 'posto',
		'campos'        => array('usuario_sac'),
		'campos_obrig'  => array('link_falha_duvida', 'menu_posto'),
		'no_fabrica'    => array(3,11,42)
	),
	'satisfacao_90_dewalt' => array (
		'descricao'    => 'Satisfação 90 dias DEWALT',
		'atendente'    => 'posto',
		'campos'       => array(),
		'campos_obrig' => array(),
		'no_fabrica'   => array(3,11,42)
	),
	'utilizacao_do_site'=> array (
		'descricao'    => ($login_fabrica == 42) ? 'Dúvidas de utilização do Telecontrol' : 'Dúvidas na utilização do site',
		'atendente'    => 'posto',
		'campos'       => ($login_fabrica == 42)?array('link_falha_duvida','menu_posto'):array('usuario_sac','garantia','referencia','os'),
		'campos_obrig' => array('usuario_sac'),
		'no_fabrica'   => array(3)
	),
	'patam_filiais_makita'=> array (
		'descricao'    => "PATAM's e Filiais Makita",
		'atendente'    => 'posto',
		'campos'       => array(),
		'campos_obrig' => array('patams_filiais_makita'),
		'no_fabrica'   => array(1,3,11)
	),
	'servico_atendimeto_sac' => array(
		'descricao'    => 'Serviço de atendimento ao consumidor - SAC',
		'campos'       => array('os','os_posto','referencia','garantia'),
		'campos_obrig' => array('usuario_sac','nome_cliente','hd_chamado_sac'),
		"no_fabrica"   => array(3,11,42)
	)
);

