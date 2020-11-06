<?php

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

use model\ModelHolder;
use action\ActionHolder;
use action\IsSetFilter;
use action\NotEmptyFilter;

use rules\process\os\CalculaOs;
use rules\process\os\calculos\CalculaKm;
use rules\process\os\calculos\CalculaMaoDeObra;
use rules\process\os\calculos\CalculaExcecaoMaoDeObraPostoProduto;
use rules\process\os\calculos\CalculaExcecaoMaoDeObraPosto;
use rules\process\os\calculos\CalculaMaoDeObraProduto;
use rules\process\os\calculos\CalculaValoresAdicionaisMaoDeObra;
use rules\process\os\calculos\CalculaExcecaoMaoDeObraLinhaServico;

$this->methods['repairInFactory'] = function($self,$os,$message=''){
	global $login_admin;
	$self->begin();
	try{
		$os = $self->select($os);
		if(empty($os))
			throw new \Exception();
		$comunicado = array(
			'mensagem' => 'O produto da OS '.$os['os'].' será reparado na fábrica. Favor entrar em contato com a fábrica.',
			'descricao' => 'Reparo em Fábrica',
			'tipo' => 'Comunicado',
			'posto' => $os['posto'],
			'fabrica' => $os['fabrica'],
			'pais' => 'BR',
			'ativo' => true
		);
		$model = ModelHolder::init('Comunicado');
		$model->insert($comunicado);
		$osStatus = array(
			'os' => $os['os'],
			'status_os' => 65,
			'observacao' => $message,
			'admin' => $login_admin,
		);
		$model = ModelHolder::init('OsStatus');
		$model->insert($osStatus);
		$retorno = array(
			'os' => $os['os']
		);
		$model = ModelHolder::init('OsRetorno');
		$model->insert($retorno,'os');
		$sql = 'SELECT servico_realizado,LOWER(descricao) ~ E\'ajust(e|ar)\' AS likeAjuste
				FROM tbl_servico_realizado
				WHERE fabrica = 138 AND gera_pedido IS NOT TRUE
				ORDER BY likeAjuste DESC
				LIMIT 1';
		$result = $self->executeSql($sql);
		$idAjuste = $result[0]['servico_realizado'];
		$sql = 'UPDATE tbl_os_item SET
					servico_realizado = :idAjuste,
					liberacao_pedido = FALSE,
					liberacao_pedido_analisado = FALSE,
					data_liberacao_pedido = NULL
				WHERE os_item IN (
					SELECT os_item
					FROM tbl_os_item 
					INNER JOIN tbl_os_produto
						ON (tbl_os_item.os_produto = tbl_os_produto.os_produto)
					INNER JOIN tbl_servico_realizado
						ON (tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado)
					WHERE tbl_os_produto.os = :os
					AND tbl_servico_realizado.gera_pedido IS NOT FALSE
				)';
		$params = array(':os' => $os['os'],':idAjuste'=>$idAjuste);
		$self->executeSql($sql,$params);
		$self->commit();
	}
	catch(Exception $ex){
		$self->rollback();
		throw $ex;
	}
};

$this->actionBeforeInsert[] = function($event){	
	$event['element']['fabrica'] = 138;
};

$this->actionBeforeInsert[] = function($event){
	foreach ($event['element']['osProduto'] as $key => $osProduto) {
		if(empty($osProduto['produto'])){
			unset($event['element']['osProduto'][$key]);
		}
	}
};

$this->actionBeforeInsert[] = new ActionHolder(
	function ($event){
		$event['element']['produto'] = $event['element']['osProduto'][0]['produto'];
	},
	new NotEmptyFilter('osProduto')
);

$this->actionBeforeInsert[] = function($event){
	if(empty($event['element']['tipoAtendimento']))
		return;
	$tipoAtendimento = $event['element']['tipoAtendimento'];
	$tipoAtendimentoModel = ModelHolder::init('tipoAtendimento');
	$kmGoogle = $tipoAtendimentoModel->field('kmGoogle',array('tipoAtendimento' => $tipoAtendimento));
	if($kmGoogle)
		return;
	$event['element']['qtdeKm'] = 0;
};


$this->actionBeforeInsert[] = function($event){
	$model = $event['source'];
	$posto = $model->getPost();
	if(isset($event['element']['posto']) && !empty($event['element']['posto'])){
		return;
	}
	if(!empty($posto) && !empty($posto)){
		$event['element']['posto'] = $posto;
	}
};

$this->actionBeforeInsert[] = new ActionHolder(
	function($event){
		$cep = $event['element']['consumidorCep'];
		$event['element']['consumidorCep'] = preg_replace('@[-./]+@','',$cep);
	},
	new IsSetFilter('consumidorCep')
);

$this->actionBeforeInsert[] = new ActionHolder(
	function($event){
		$cnpj = $event['element']['revendaCnpj'];
		$event['element']['revendaCnpj'] = preg_replace('@[-./]+@','',$cnpj);
	},
	new IsSetFilter('revendaCnpj')
);


$this->actionBeforeInsert[] = new ActionHolder(
	function($event){
		$dataAbertura = DateTime::createFromFormat('d/m/Y',$event['element']['dataAbertura']);
		$dataNf = DateTime::createFromFormat('d/m/Y',$event['element']['dataNf']);
		if(!$dataNf)
			return;
		if(!$dataAbertura)
			return;
		if($dataNf->getTimestamp() > $dataAbertura->getTimestamp()){
			throw new rules\exceptions\CheckException('dataNf',"Data de compra não pode ser após data de abertura");
		}
	},
	new IsSetFilter(array('dataAbertura','dataNf'))
);

$this->actionBeforeInsert[] = new ActionHolder(
	function($event){
		$dataAbertura = DateTime::createFromFormat('d/m/Y',$event['element']['dataAbertura']);
		$dataNf = DateTime::createFromFormat('d/m/Y',$event['element']['dataNf']);
		$produtoModel = ModelHolder::init('Produto');
		$months = $produtoModel->field('garantia',$event['element']['osProduto'][0]['produto']);
		$months = $months?$months:'0';
		$interval = new DateInterval('P'.$months.'M');
		$dataNf->add($interval);
		if($dataAbertura < $dataNf)
			return;
		throw new rules\exceptions\CheckException('dataNf',"Produto fora de Garantia");
	},
	new NotEmptyFilter(array('dataAbertura','dataNf','osProduto'))
);

$this->actionBeforeInsert[] = new ActionHolder(
	function($event){
		$model = $event['source'];
		$cidade = $event['element']['consumidorCidade'];
		$estado = $event['element']['consumidorEstado'];
		$sql = 'SELECT nome AS cidade_nome FROM tbl_cidade WHERE cidade = :cidade AND estado = :estado LIMIT 1;';
		$params = array(':cidade'=>$cidade,':estado'=>$estado);
		$result = $model->executeSql($sql,$params);
		if(empty($result)){
			throw new rules\exceptions\CheckException('consumidorCidade',"A cidade selecionada não pertence ao estado $estado");
		}
		$event['element']['consumidorCidade'] = $result[0]['cidade_nome'];
	},
	new NotEmptyFilter(array('consumidorCidade','consumidorEstado'))
);

$this->actionBeforeInsert[] = new ActionHolder(
	function($event){
		$solucaoOs = $event['element']['solucaoOs'];
		$model = ModelHolder::init('solucao');
		$solucaoOs = $model->select($solucaoOs);
		if(!$solucaoOs['trocaPeca'])
			return;

		$troca = array();
		if(!preg_match('@serpentina|compressor@',strtolower($solucaoOs['descricao']),$troca)){
			return;
		}
		$troca = $troca[0];

		$pecas = array();
		foreach($event['element']['osProduto'] as $osProduto){
			if(empty($osProduto['osItem']))
				continue;
			foreach ($osProduto['osItem'] as $osItem) {
				if(empty($osItem['peca']) || !$osItem['geraPedido'])
					continue;
				$pecas[] = $osItem['peca'];
			}
		}
		if(empty($pecas))
			throw new rules\exceptions\CheckException('solucaoOs',"Está solução necessita que seja lançado um(a) $troca");
		
		$sql = 'SELECT COUNT(*) > 0 AS ok FROM tbl_peca WHERE peca IN ('.implode(',',$pecas).') AND parametros_adicionais LIKE \'%'.$troca.'%\';';
		$fetch = $model->executeSql($sql);
		if(!$fetch[0]['ok'])
			throw new rules\exceptions\CheckException('solucaoOs',"Está solução necessita que seja lançado um(a) $troca");
	},
	new NotEmptyFilter('solucaoOs')
);
/**
* Validaçao consensadora e evaporadora
*/
//$this->actionBeforeInsert[] = new ActionHolder(
//	function($event){
//		$model = $event['source'];
//		$osProduto = $event['element']['osProduto'][0];
//		$osSubproduto = $event['element']['osProduto'][1];
//		$sql = "SELECT
//					COUNT(*) > 0 = SUM(CASE WHEN subproduto.produto = :subproduto THEN 1 ELSE 0 END) > 0 AS ok
//				FROM tbl_produto produto
//				INNER JOIN tbl_subproduto
//					ON (tbl_subproduto.produto_pai = produto.produto)
//				INNER JOIN tbl_produto subproduto
//					ON (tbl_subproduto.produto_filho = subproduto.produto)
//				WHERE produto.fabrica_i = :fabrica AND subproduto.fabrica_i = :fabrica
//				AND (UPPER(produto.descricao) LIKE '%EVAPORADORA%' OR UPPER(produto.descricao) LIKE '%EVAPORADOR%')
//				AND (UPPER(subproduto.descricao) LIKE '%CONDENSADORA%' OR UPPER(subproduto.descricao) LIKE '%CONDENSADOR%')
//				AND produto.produto = :produto";
//		$params = array(':fabrica'=>138,':produto'=>$osProduto['produto'],':subproduto'=>$osSubproduto['produto']);
//		$result = $model->executeSql($sql,$params);
//	},
//	new NotEmptyFilter('osProduto')
//);

$this->actionBeforeInsert[] = function($event){
	$dateTime = new DateTime('now');
	$event['element']['validada'] = $dateTime->format(DateTime::ISO8601);
};

$this->actionAfterInsert[] = new rules\interventions\CriticalPieceIntervention();
$this->actionAfterInsert[] = new rules\interventions\SurplusPartsIntervention(3);
$this->actionAfterInsert[] = new rules\interventions\KmIntervention(200); 
$this->actionAfterInsert[] = new rules\interventions\RepeatIntervention(90); 

$nf = $_FILES['anexo_nf'];

if($nf['size'] > 0){
	$this->actionAfterInsert[] = new ActionHolder(
		function($event){
			if($nf['size'] <= 0){
				return;
			}
			$s3 = new AmazonTC('os',138);
			$data_abertura = $event['element']['dataAbertura'];
			list($dia, $mes, $ano) = explode("/", $data_abertura);
	        $os 	= $event['result'];
	        $file 	= $nf;
	        $s3->upload($os, $file, $ano, $mes);
		},
		new IsSetFilter('anexoNf')
	);
}

$this->actionAfterInsert[] = new ActionHolder(
	function($event){

		$model = ModelHolder::init();
		$os = $event['result'];
		$sql = "INSERT INTO tbl_os_extra (os) VALUES ($os)";
		$model->executeSql($sql);

	}
);

/*

'fieldName' => array(
	'required' => boolean|message,
	'notEmpty' => boolean|message,
	'maxlength' => int,
	'regex' => array(regex,...),
	'group' => string, //is not a rule
)
*/

$this->methods["calculaOs"] = new CalculaOs(
	    array("CalculaMaoDeObra" =>   array(
			new CalculaExcecaoMaoDeObraLinhaServico(1)
		)
	)
);

array(
	'fabrica' => array(
		'notEmpty' => true,
	),
	'anexoNf' => array(
		'regex' => array(
			'@^(.*\.png)|(.*\.jpg)|(.*\.jpeg)|(.*\.bmp)|(.*\.pdf)$@' => 'Formato inválido, são aceitos os seguintes formatos: png, jpg, jpeg, bmp, pdf'
		),
	),
	'posto' => array(
		'required' => 'Preencha todos os campos obrigatórios',
		'notEmpty' => 'OS sem posto',
	),
	'osProduto' => array(
		'notEmpty' => 'OS sem produto',
	),
	'solucaoOs' => array(
		'required' => 'Preencha todos os campos obrigatórios',
		'notEmpty' => 'Preencha todos os campos obrigatórios',
	),
	'dataAbertura' => array(
		'required' => 'Preencha todos os campos obrigatórios',
		'not_empty' => 'Preencha todos os campos obrigatórios',
		'dateType' => 'A data de abertura não é uma data válida',
	),
	'tipoAtendimento' =>array(
		'required' => 'Preencha todos os campos obrigatórios',
		'notEmpty' => 'Selecione um tipo de atendimento'
	),
	'notaFiscal' => array(
		'required' => 'Preencha todos os campos obrigatórios',
		'regex' => array(
			'@^.{0,20}$@' => 'A nota fiscal deve ter no máximo 20 caracteres'
		),
	),
	'dataNf' => array(
		'required' => 'Preencha todos os campos obrigatórios',
		'dateType' => 'A data de compra não é uma data válida'
	),
	'defeitoReclamadoDescricao' => array(
		'required' => 'Preencha todos os campos obrigatórios',
		'notEmpty' => 'Preencha todos os campos obrigatórios',
	),

	'consumidorNome' => array(
		'required' => 'Preencha todos os campos obrigatórios',
		'notEmpty' => 'Preencha todos os campos obrigatórios',
	),
	'consumidorEstado' => array(
		'required' => 'Preencha todos os campos obrigatórios',
		'notEmpty' => 'Preencha todos os campos obrigatórios',
	),
	'consumidorCidade' => array(
		'required' => 'Preencha todos os campos obrigatórios',
		'notEmpty' => 'Preencha todos os campos obrigatórios',
	),

	'revendaNome' => array(
		'required' => 'Preencha todos os campos obrigatórios',
		'notEmpty' => 'Preencha todos os campos obrigatórios',
	),
	'revendaCnpj' => array(
		'required' => 'Preencha todos os campos obrigatórios',
		'notEmpty' => 'Preencha todos os campos obrigatórios',
	),

	'qtdeKm' => array(
		'default' => 0,
	)
);
