<?php
/**
 *  Array com a configura��o dos tipos de chamados ($categorias)
 *      key:    		nome da categoria (o que vai pro banco)
 *      descricao:		Descri��o da categoria (o que vai pra tela)
 *      atendente:  	'posto' para procurar pela fun��o hdBuscarAtendentePorPosto, ou o 'tbl_admin.admin'
 *      campos:     	array com os campos a mostrar para esta categoria
 *      campos_obrig:	array com os campos que s�o obrigat�rios
 **/
return Array(
	'atualiza_cadastro' => array (
		'descricao'    => 'Atualiza��o de cadastro',
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
		'descricao'    => 'Digita��o e/ou fechamento de OSs',
		'atendente'    => 'posto',
		'campos'       => array('usuario_sac','garantia','garantia'),
		'campos_obrig' => array('usuario_sac','referencia','os'),
		'no_fabrica'   => array(1,3)
	),
	'duvida_troca' => array (
		'descricao'    => 'D�vidas na troca de produto',
		'atendente'    => 'posto',
		'campos'       => array('garantia','referencia'),
		'campos_obrig' => array('usuario_sac','referencia'),
		'no_fabrica'   => array(42, 3)
	),
	'duvida_produto' => array (
		'descricao'     => (($login_fabrica == 42) ? 'Suporte T�cnico' : 'D�vida t�cnica sobre o produto'),
		'atendente'	 => 'posto',
		'campos'	 => array('garantia','referencia','os'),
		'campos_obrig' => array('usuario_sac','referencia'),
		'no_fabrica'    => array(3)
	),
	'duvida_revenda' => array (
		'descricao'     => 'D�vidas sobre atendimento � revenda',
		'atendente'	 => 'posto',
		'campos'	 => array('garantia','referencia','os'),
		'campos_obrig' => array('usuario_sac'),
		'no_fabrica'    => array(42, 3)
	),
	'duvida_peca_bloqueada_sempreco' => array(
		'descricao'    => 'D�vidas de pe�as bloqueadas/sem pre�o',
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 42)
	),
	'duvida_administrativa' => array(
		'descricao'    => (($login_fabrica == 3) ? 'D�vidas ADM [] NF [] ABERTURA DE OS [] PROCEDIMENTOS TELECONTROL []' : 'D�vidas Administrativas - NF, Abertura de OS, procedimentos Telecontrol'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_adm_devolucao_prod' => array(
		'descricao'    => (($login_fabrica == 3) ? 'D�vidas ADM [] DEVOLU��O DE PRODUTOS []' : 'D�vidas Adm Devolu��o de Produtos'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_financeira_duplicata' => array(
		'descricao'    => (($login_fabrica == 3) ? 'D�vidas Financeiras [] DUPLICATAS []' : 'D�vidas Financeiras Duplicatas'),
		'atendente'    => 'posto',
		'campos'       => array('duvida'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_financeira_mo' => array(
		'descricao'    => (($login_fabrica == 3) ? 'D�vidas Financeiras [] M�O DE OBRA[]' : 'D�vidas Financeiras M�o de Obra'),
		'atendente'    => 'posto',
		'campos'       => array('duvida'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_nserie_eletro_pessoal_refri_av' => array(
		'descricao'    => (($login_fabrica == 3) ? 'D�vidas N�mero de S�rie [] ELETRO [] REFRIGERA��O [] CUIDADOS PESSOAIS [] A&V []' : 'D�vidas n�meros de s�rie eletro / cuidados pessoais / refrigera��o / �udio e v�deo'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'duvida_numero_serie_informatica' => array(
		'descricao'    => (($login_fabrica == 3) ? 'D�vidas N�mero de S�rie [] INFORM�TICA []' : 'D�vidas n�mero de s�rie linha de inform�tica'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'duvida_tecnica_audio_video' => array(
		'descricao'    => (($login_fabrica == 3) ? 'D�vidas T�cnicas [] �UDIO E V�DEO []' : 'D�vidas t�cnicas �udio e v�deo'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'duvida_tecnica_celular' => array(
		'descricao'    => (($login_fabrica == 3) ? 'D�vidas T�cnicas [] CELULAR [] SMARTPHONE []' : 'D�vidas t�cnicas linha celular'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'duvida_tecnica_eletro_pessoal_refri' => array(
		'descricao'    => (($login_fabrica == 3) ? 'D�vidas T�cnicas [] ELETRO [] REFRIGERA��O [] CUIDADOS PESSOAIS []' : 'D�vidas t�cnicas linha eletro / cuidados pessoais / refrigera��o'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'duvida_tecnica_informatica' => array(
		'descricao'    => (($login_fabrica == 3) ? 'D�vidas T�cnicas [] INFORM�TICA []' : 'D�vidas t�cnicas linha de inform�tica'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'duvida_cadastro' => array(
		'descricao'    => (($login_fabrica == 3) ? 'D�vidas Cadastrais [] ALTERA��O DE DADOS [] DESCREDENCIAMENTO []' : 'D�vidas cadastrais (Altera��o de dados/Descredenciamento)'),
		'atendente'    => 'posto',
		'campos'       => array(),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_os_bloqueada' => array(
		'descricao'    => (($login_fabrica == 3) ? 'D�vidas [] OSs BLOQUEADAS []' : 'D�vidas de OSs bloqueadas'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_bloqueada_fora_linha' => array(
		'descricao'    => (($login_fabrica == 3) ? 'D�vidas [] PE�AS BLOQUEADAS [] FORA DE LINHA []' : 'D�vidas de pe�as bloqueadas (fora de linha)'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'duvida_compra_venda' => array(
		'descricao'    => (($login_fabrica == 3) ? 'D�vidas [] PE�AS SEM PRE�O [] COMPRA [] VENDA []' : 'D�vidas de pe�as sem pre�o, COMPRA/VENDA'),
		'atendente'    => 'posto',
		'campos'       => array('os','peca_faltante'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'peca_recebida_errada' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pe�a recebida [] ERRADA [] COM DEFEITO []' : 'Pe�a recebida errada'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','peca_faltante'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'peca_defeito' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pe�a recebida [] QUEBRADA []' : 'Pe�a com defeito / quebrada'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','pedido'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42)
	),
	'pendencia_pedido_audio_video' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pend�ncias de Pedidos [] �UDIO E V�DEO []' : 'Pend�ncias de pedidos linha �udios e v�deo'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','pedido'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'pendencia_pedido_pessoal' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pend�ncias de Pedidos [] CUIDADOS PESSOAIS []' : 'Pend�ncias de pedidos linha cuidados pessoais'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','pedido'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'pendencia_pedido_eletroportateis' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pend�ncias de Pedidos [] ELETROPORT�TEIS []' : 'Pend�ncias de pedidos linha eletroportateis'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','pedido'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'pendencia_pedido_informatica' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pend�ncias de Pedidos [] INFORM�TICA []' : 'Pend�ncias de pedidos linha inform�tica'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','pedido'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'pendencia_pedido_lcd_led' => array(
		'descricao'    => (($login_fabrica == 3) ? 'Pend�ncias de Pedidos [] LCD [] LED []' : 'Pend�ncias de pedidos linha LCD/LED'),
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
		'descricao'    => (($login_fabrica == 3) ? 'Solicita��o [] COLETA LGR [] DEVOLU��O OBRIGAT�RIA []' : 'Solicita��o de coleta LGR (Devolu��o obrigat�ria)'),
		'atendente'    => 'posto',
		'campos'       => ($login_fabrica == 3) ? array('garantia','os') : array('garantia','peca','produto'),
		'campos_obrig' => ($login_fabrica == 3) ? array() : array('solic_coleta'),
		'no_fabrica'   => array(1, 42)
	),
	'dnf' => array(
		'descricao'    => (($login_fabrica == 3) ? 'DNF [] NOTIFICA��O DE DIVERG�NCIA []' : 'DNF - Notifica��o de Diverg�ncia de NF/P�. Recebida com defeito/errada/divergente/faltante'),
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 42, 11)
	),
	'duvida_cobertura_garantia_informatica' => array(
		'descricao'    => 'D�vidas cobertura da garantia linha de inform�tica',
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 42, 11)
	),
	'duvida_financeiro' => array(
		'descricao'    => 'D�vidas financeiras (Duplicatas/MO)',
		'atendente'    => 'posto',
		'campos'       => array('os','garantia','duvida'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 42)
	),
	'duvida_juridica' => array(
		'descricao'    => 'D�vidas jur�dicas (Notifica��es PROCON/JEC)',
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 42)
	),
	'duvida_cobertura_garantia' => array(
		'descricao'    => 'D�vidas cobertura de garantia - todas as linhas, exceto Inform�tica',
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
		'descricao'    => 'Metais sanit�rios e Fechaduras',
		'atendente'    => 'posto',
		'campos'       => array('garantia','referencia','os','produto_de'),
		'campos_obrig' => array('usuario_sac','produto_de'),
		'no_fabrica'   => array(1,3,11,42)
	),
	'manifestacao_sac' => array (
		'descricao'    => (($login_fabrica == 1) ? 'Chamados SAC' : 'Manifesta��o sobre o SAC'),
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
		'descricao'    => (($login_fabrica == 42) ? 'Pend�ncia de pe�as / Pedidos de pe�as' : 'Pend�ncias de pe�as com a f�brica'),
		'atendente'    => 'posto',
		'campos'       => array('garantia','os','pedido','data_pedido','peca_faltante'),
		'campos_obrig' => array('usuario_sac','os','pedido','data_pedido','peca_faltante'),
		'no_fabrica'   => array(3)
	),
	'pend_pecas_dist' => array (
		'descricao'    => 'Pend�ncias de pe�as com o distribuidor',
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
		'descricao'    => 'Pe�as recebidas com defeito linha de inform�tica',
		'atendente'    => 'posto',
		'campos'       => array('os'),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 42, 11)
	),
	'solicita_informacao_tecnica' => array (
		'descricao'    => 'Solicita��o de Informa��o T�cnica',
		'atendente'    => 'posto',
		'campos'       => ($login_fabrica==42)?array('garantia','os'):array(),
		'campos_obrig' => array(),
		'no_fabrica'   => array(1, 3, 11)
	),
	'solicitacao_coleta'=> array (
		'descricao'    => (($login_fabrica == 1) ? 'Devolu��o de Pe�a / Produto' : 'Solicita��o de coleta'),
		'atendente'    => 'posto',
		'campos'       => array(),
		'campos_obrig' => array('solic_coleta'),
		'no_fabrica'   => array(3)
	),
	'sugestao_critica' => array (
		'descricao'    => 'Sugest�es, Cr�ticas, Reclama��es ou Elogios',
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
		'descricao'     => 'Duvida na utiliza��o Telecontrol',
		'atendente'     => 'posto',
		'campos'        => array('usuario_sac'),
		'campos_obrig'  => array('link_falha_duvida', 'menu_posto'),
		'no_fabrica'    => array(3,11,42)
	),
	'satisfacao_90_dewalt' => array (
		'descricao'    => 'Satisfa��o 90 dias DEWALT',
		'atendente'    => 'posto',
		'campos'       => array(),
		'campos_obrig' => array(),
		'no_fabrica'   => array(3,11,42)
	),
	'utilizacao_do_site'=> array (
		'descricao'    => ($login_fabrica == 42) ? 'D�vidas de utiliza��o do Telecontrol' : 'D�vidas na utiliza��o do site',
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
		'descricao'    => 'Servi�o de atendimento ao consumidor - SAC',
		'campos'       => array('os','os_posto','referencia','garantia'),
		'campos_obrig' => array('usuario_sac','nome_cliente','hd_chamado_sac'),
		"no_fabrica"   => array(3,11,42)
	)
);

