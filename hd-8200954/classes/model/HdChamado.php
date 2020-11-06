<?php

namespace model;

use model\ModelHolder;
use util\NameHelper;


class HdChamado extends Model{

	public function __construct($connection = null){
		parent::__construct($connection);
	}


	const SQL_CALL_TO_OS =
	'SELECT
		tbl_hd_chamado.hd_chamado AS hd_chamado,
		tbl_hd_chamado.fabrica_responsavel AS fabrica,
		tbl_hd_chamado.titulo AS chamado,
		tbl_hd_chamado.cliente_admin AS cliente_admin,
		tbl_hd_chamado_extra.posto AS posto,
		tbl_hd_chamado_extra.serie AS serie,
		to_char(tbl_hd_chamado_extra.data_nf,\'DD/MM/YYYY\') AS data_nf,
		to_char(now(),\'DD/MM/YYYY\') AS data_abertura,
		tbl_hd_chamado_extra.nome AS consumidor_nome,
		tbl_hd_chamado_extra.revenda_cnpj AS revenda_cnpj,
		tbl_hd_chamado_extra.revenda_nome AS revenda_nome,
		tbl_hd_chamado_extra.fone AS consumidor_fone,
		tbl_hd_chamado_extra.produto AS produto,
		tbl_hd_chamado_extra.defeito_reclamado AS defeito_reclamado,
		tbl_hd_chamado_extra.defeito_reclamado_descricao AS defeito_reclamado_descricao,
		COALESCE(tbl_hd_chamado.cliente,tbl_hd_chamado_extra.cliente) AS cliente,
		tbl_hd_chamado_extra.revenda AS revenda,
		replace(replace(replace(tbl_hd_chamado_extra.cpf,\'-\',\'\'),\'.\',\'\'),\'/\',\'\') AS consumidor_cpf,
		(CASE char_length(replace(replace(replace(tbl_hd_chamado_extra.cpf,\'-\',\'\'),\'.\',\'\'),\'/\',\'\')) WHEN 11 THEN \'F\' WHEN 14 THEN \'J\' ELSE NULL END) AS fisica_juridica,
		tbl_hd_chamado_extra.consumidor_revenda AS consumidor_revenda,
		tbl_hd_chamado_extra.nota_fiscal AS nota_fiscal,
		tbl_hd_chamado_extra.tipo_atendimento AS tipo_atendimento,
		tbl_hd_chamado_extra.endereco AS consumidor_endereco,
		tbl_hd_chamado_extra.numero AS consumidor_numero,
		tbl_hd_chamado_extra.cep AS consumidor_cep,
		tbl_hd_chamado_extra.complemento AS consumidor_complemento,
		tbl_hd_chamado_extra.bairro AS consumidor_bairro,
		tbl_hd_chamado_extra.email AS consumidor_email,
		tbl_hd_chamado_extra.celular AS consumidor_celular,
		tbl_hd_chamado_extra.fone2 AS consumidor_fone_comercial,
		tbl_hd_chamado_extra.pedido AS pedido_cliente,
		tbl_admin.nome_completo AS quem_abriu_chamado,
		tbl_cidade.nome AS consumidor_cidade,
		tbl_cidade.estado AS consumidor_estado
	FROM tbl_hd_chamado 
	INNER JOIN tbl_hd_chamado_extra
		ON (tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado)
	LEFT JOIN tbl_cidade
		ON (tbl_hd_chamado_extra.cidade = tbl_cidade.cidade)
	LEFT JOIN tbl_admin
		ON (tbl_hd_chamado.login_admin = tbl_admin.admin)
	WHERE tbl_hd_chamado.hd_chamado = :hdChamado
	AND tbl_hd_chamado_extra.abre_os IS TRUE LIMIT 1;';


	public function makePreOs($hdChamado){
		$os = array();
		$result = $this->executeSql(HdChamado::SQL_CALL_TO_OS,(array(':hdChamado'=>$hdChamado)));
		if($result === false || empty($result))
			throw new \Exception('Chamado de Pré-OS não disponível');
		$os = NameHelper::prepareArray($result[0]);
		if(empty($os['produto'])){
			return $os;	
		}
		$produtoModel = ModelHolder::init('Produto');
		$produto = $produtoModel->select($os['produto']);
		$os['osProduto'] = array();
		$os['osProduto'][] = array(
			'produto' => $os['produto'],
			'referencia' => $produto['referencia'],
			'descricao' => $produto['descricao'],
			'voltagem' => $produto['voltagem'],
			'serie'	=> $os['serie']
		);
		return $os;
	}

}


