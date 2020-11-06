<?php
/**
 * TDocs
 * ===
 * Documentação:
 *  Da classe:
 *		./TDocs.md
 *		https://github.com/telecontrol/PosVendaAssist/wiki/Class-TDocs-API-Interface
    Da API:
 *		https://tdocs.docs.apiary.io/#reference
 * Classe para utilizar a API Tc TDocs para gerenciar arquivos (anexos, fotos)
 * Requisitos:
 * - API2
 * - simpleREST
 * - mlg_funciones
 *
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'abstractAPI2.class.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'fn_sql_cmd.php';

class TDocs extends API2
{
	protected $application='TDOCS';
	static private $conn; // recurso de conexão ao banco de dados
	private
		$actions = array(1=>'anexar', 'substituir', 'renomear', 'excluir'), // CRUD, mais ou menos
		$objectId,
		$tDocsId,
		$referencia,
		$referencia_id,
		$campo_referencia,
		$fabrica,
		$context,
		$situacao,
		$sent_id,
		$sentData,
		$sent_filename,
		$obs;

	public
		$attachListInfo=array(),
		$url=null;

	const TDOCS_URI = '/tdocs/';
	const TDOCS_BASE_LINK = '/tdocs/document/id/';

	/**
	 * mapeia os "tipos" de sempre (AnexaS3) com contexto e referencia
	 */
	private static $attTypes = array(
		'os' => array(
			'contexto'    => 'os',
			'referencia'  => array(
				'os'        => 'tbl_os.os',
				'ositem'    => 'tbl_os_item.os_item',
				'ossedex'   => 'tbl_os_sedex.os_sedex',
				'osextrato' => 'tbl_extrato.extrato',
				'revenda'   => 'tbl_os_revenda.os_revenda',
			),
			'subcontexto' => array(
				'revenda'            => array('r', 'osr', 'osrevenda'),
				'ossedex'            => array('s', 'oss', 'ossedex', 'sedex'),
				'osextrato'          => array('e', 'ose', 'osextrato', 'extrato', 'osx'),
				'garantia_estendida' => array('ge', 'garantiaestendida'),
				'ositem'             => array('itemos', 'ospeca', 'pecaos'),
				'osproduto'          => array('pd', 'osproduto'),
				'laudotecnico'       => array('lt', 'laudo', 'laudoanexo', 'laudotecnico'),
				'notafiscal'         => array('od', 'nf', 'osdigitalizada', 'nfdigitalizada'),
				'tj'                 => array('trocajudicial'),
				'osserie'            => array('produtoserie', 'ns'),
				'datafechamento'     => array('fechamento')
			),
		),
		'call center' => array(
			'contexto'    => 'call center',
			'referencia'  => array(
				'tbl_hd_chamado_item.hd_chamado_item',
				'preos'         => 'tbl_hd_chamado.hd_chamado',
				'ressarcimento' => 'tbl_ressarcimento.ressarcimento',
				'comprovante'   => 'tbl_ressarcimento.ressarcimento'
			),
			'alternate'   => array('atendimento'),
			'subcontexto' => array(
				'preos'         => array('preos'),
				'ressarcimento' => array('ressarcimento'),
				'comprovante'   => array('comprovante')
			),
		),
		'callcenter' => array(
            'contexto'    => 'callcenter',
            'referencia'  => array(
                'tbl_hd_chamado.hd_chamado',
                'preos'         => 'tbl_hd_chamado.hd_chamado',
                'ressarcimento' => 'tbl_ressarcimento.ressarcimento',
                'comprovante'   => 'tbl_ressarcimento.ressarcimento'
            ),
            'alternate'   => array('atendimento'),
            'subcontexto' => array(
                'preos'         => array('preos'),
                'ressarcimento' => array('ressarcimento'),
                'comprovante'   => array('comprovante')
            ),
        ),
		'help desk' => array(
			'contexto'    => 'help desk',
			'referencia'  => array(
				'tbl_hd_chamado_item.hd_chamado_item',
				'hdrequisito' => 'tbl_hd_chamado_requisito.hd_chamado_requisito',
				'hdanalise'   => 'tbl_hd_chamado_analise.hd_chamado_analise',
				'hdcorrecao'  => 'tbl_hd_chamado_correcao.hd_chamado_correcao',
				'hdmelhoria'  => 'tbl_hd_chamado_melhoria.hd_chamado_melhoria',
				'hdposto'     => 'tbl_hd_chamado.hd_chamado',
			),
			'alternate'   => array('hd', 'hdchamado', 'hdchamadoitem', 'chamado'),
			'subcontexto' => array(
				'hdrequisito' => array('requisito'),
				'hdanalise'   => array('analise'),
				'hdcorrecao'  => array('correcao'),
				'hdmelhoria'  => array('melhoria'),
				'hdposto'     => array('helpdeskposto', 'hdpa', 'helpdesk_pa'),
				'hdpostoitem' => array('hdpitem'),
				'hditem'      => array('hditem')
			),
		),
		'comunicados' => array(
			'contexto'   => 'comunicados',
			'referencia' => array('tbl_comunicado.comunicado', 'vefamilia' => 'tbl_familia.familia'),
			'alternate'  => array(
				'acessorio', 'ajudasuportetecnico', 'alteracoestecnicas', 'analisegarantia',
				'apresentacaodoproduto', 'arvoredefalhas', 'atualizacaodesoftware', 'boletim',
				'boletimtecnico', 'co', 'comunicoposto', 'comunicado', 'comunicadoadministrativo',
				'comunicadodenaoconformidade', 'comunicadoportela', 'descritivotecnico',
				'diagramadeservicos', 'esquemaeletrico', 'estruturadoproduto', 'extrato', 'foto',
				'informativo', 'informativoadministrativo', 'informativopromocional',
				'informativos', 'informativotecnico', 'lancamentos', 'manual', 'manualdeproduto',
				'manualdeservico', 'manualdetrabalho', 'manualdousuario', 'manualtecnico',
				'orientacaodeservico', 'pecasalternativas', 'pecasdereposicao',
				'politicademanutencao', 'procedimentodemanutencao', 'procedimentos', 'produtos',
				'promocao', 'promocoes', 'recall', 'tabeladepreco', 'testeredeautorizada',
				'tipodeproduto', 'treinamentodeproduto', 'trocapendente', 've', 'versoeseprom',
				'video', 'vista', 'vistaexplodida','contrato',
			),
			'subcontexto' => array('vefamilia' => array('familia', 'zipfamilia'))
		),
		'fabrica' => array(
			'contexto' => 'fabrica',
			'referencia' => array(
				'fabrica'      => 'tbl_fabrica.fabrica',
				'adminfoto'    => 'tbl_admin.admin',
				'assinatura'   => 'tbl_admin.admin',
				'log'          => 'tbl_routine_schedule_log.routine_schedule_log',
				'logsimples'  => 'tbl_routine_schedule_log.routine_schedule_log',
				'cheque'	   => 'tbl_solicitacao_cheque.solicitacao_cheque',
				'fornecedor'  => 'tbl_fornecedor.fornecedor',
			),
			'subcontexto' => array(
				'logo'         => array('logotipo', 'logomarca'),
				'adminfoto'    => array('fa', 'admin', 'fotoadmin'),
				'docposvenda'  => array('doc', 'documentacao'),
				'log'          => array('logrotina', 'logrotinas'),
				'logsimples'  => array('logrotinasimples', 'logrotinassimples'),
				'assinatura'   => array('assinaextrato', 'assinaturaadmin'),
				'cheque'	   => array('cheque'),
				'fornecedor' => array('fornecedor')

			),
		),
		'assinatura' => array(
			'contexto' => 'assinatura',
			'referencia' => array(
				'adminfoto'    => 'tbl_admin.admin',
				'assinatura'   => 'tbl_admin.admin',
				'log'          => 'tbl_routine_schedule_log.routine_schedule_log',
				'logsimples'  => 'tbl_routine_schedule_log.routine_schedule_log',
				'cheque'	   => 'tbl_solicitacao_cheque.solicitacao_cheque'
			),
			'subcontexto' => array(
				'logo'         => array('logotipo', 'logomarca'),
				'adminfoto'    => array('fa', 'admin', 'fotoadmin'),
				'docposvenda'  => array('doc', 'documentacao'),
				'log'          => array('logrotina', 'logrotinas'),
				'logsimples'  => array('logrotinasimples', 'logrotinassimples'),
				'assinatura'   => array('assinaextrato', 'assinaturaadmin'),
				'cheque'	   => array('cheque')
			),
		),
		'posto' => array(
			'contexto'    => 'posto',
			'referencia'  => array(
				'posto' => 'tbl_posto_fabrica.posto',
				'comprovanterf' => 'tbl_posto.posto',
			),
			'alternate'   => array('fotoposto', 'pafoto'),
			'subcontexto' => array(
				'contrato' => array('paco','pace','postocomprovanteendereco'),
				'logomarcaposto' => array('logo_posto','logomarca_posto'),
				'comprovanterf' => array('inscricaoreceitafederal'),
			),
		),
		'produto' => array(
			'contexto' => 'produto',
			'referencia'  => array(
				'produto'     => 'tbl_produto.produto',
				'diagnostico' => 'tbl_diagnostico.diagnostico'
			),
			'subcontexto' => array(
				'diagnostico' => 'diagnostico'
			)
		),
		'lgr' => array(
			'contexto'   => 'lgr',
			'referencia' => array(
				'lgr' => 'tbl_faturamento.faturamento',
				'ocorrencia' => 'tbl_faturamento_interacao.faturamento_interacao',
				'extrato' => 'tbl_extrato.extrato',
				'nfservico' => 'tbl_extrato_pagamento.extrato',
			),
			'alternate'  => array('faturamentocodigo', 'nfdevolucao'),
			'subcontexto' => array(
				'ocorrencia' => array('ocorrencia'),
				'exstatus' => array('extratoaprovado'),
				'extrato' => array('comprovantelgr'),
				'nfservico' => array('nfservico'),
			)
		),
		'pedido' => array(
			'contexto'   => 'pedido',
			'referencia' => array('pedido' => 'tbl_pedido.pedido')
		),
		'loja' => array(
			'contexto'    => 'loja',
			'referencia'  => array(
				'loja' => 'tbl_loja_b2b.loja_b2b',
				'banner' => 'tbl_loja_b2b_banner.loja_b2b_banner',
				'lojapeca' => 'tbl_loja_b2b_peca.loja_b2b_peca',
				'lojapecakit' => 'tbl_loja_b2b_kit_peca.loja_b2b_kit_peca'
			),
			'subcontexto' => array(
				'banner'  => array('banner'),
				'lojapeca' => array('lojapeca'),
				'lojalogo' => array('lojalogo'),
				'lojapecakit' => array('lojapecakit'),
			),
		),

		'peca' => array(
			'contexto'    => 'peca',
			'referencia'  => array('peca' => 'tbl_peca.peca'),
			'subcontexto' => array(
				'distrib' => array('infopeca'),
				'lv' => array('loja', 'lojavirtual')
			)
		),
		'rotina' => array(
			'contexto' => 'rotina',
			'referencia' => array('rotina' => 'tbl_routine.routine')
		),
		'extrato' => array(
            'contexto' => 'extrato',
            'referencia' => array('protocolo' => 'tbl_extrato_agrupado.codigo','avulso' => 'tbl_extrato_lancamento.extrato_lancamento', 'nfautorizacao' => 'tbl_extrato_pagamento.extrato'),
            'subcontexto' => array(
                'protocolo' => array('protocolo'),
                'avulso' => array('avulso'),
                'nfautorizacao' => array('nfautorizacao')
            )
		),
		'ferramenta' => array(
			'contexto' => 'ferramenta',
			'referencia' => array('ferramenta' => 'tbl_posto_ferramenta.posto_ferramenta')
		),
		'revenda' => array(
            'contexto' => 'revenda',
            'referencia' => array('revenda' => 'tbl_revenda.revenda'),
            'subcontexto' => array(
                'revenda' => array('revenda')
            )
		),
		'roteiro' => array(
			'contexto'   => 'roteiro',
			'referencia' => array('roteiro' => 'tbl_roteiro.roteiro')
		),
		'postforum' => array(
            'contexto' => 'postforum',
            'referencia' => array('postforum' => 'tbl_forum.forum'),

		),
		'marcatc' => array(
            'contexto' => 'marcatc',
            'referencia' => array('marcatc' => 'tbl_fabrica.fabrica'),

		),
		'oscancela' => array(
            'contexto' => 'oscancela',
            'referencia' => array('oscancela' => 'tbl_os.os'),

		),
		// 'pedido',
		// 'distrib',
		// 'relatorios',
	);

	public function __construct(&$conn, $fabrica, $contexto=null) {
		if (!is_resource($conn))
			throw new Exception ('Sem conexão ao banco de dados!');

		ini_set('post_max_size', '64M');
		ini_set('upload_max_filesize', '64M');
		$this->fabrica = $fabrica;

		// poderia ter uma validação de fabricante ativo, se for ser usado
		// em algum lugar sem autentica.... mas é responsabilidade do script
		// que o usa de fornecer valroes "usáveis".
		if (!is_numeric($fabrica))
			throw new Exception("Valor '$fabrica' inválido para o fabricante!");

		$this->environment = 'PRODUCTION'; // TDocs não tem DEVEL env.
		$this->setDbConn($conn);

		parent::__construct();

		if (!is_null($contexto))
			$this->setContext($contexto);

	}

	public function __get($varname) {
		$varName = str_replace('_', '', strtolower($varname));

		switch ($varName):
			case 'hasattachment':
			case 'hasattachments':
				return count($this->attachListInfo) > 0;
			break;

			case 'sentname':
			case 'sentfilename':
				return $this->sentData['name'];
			break;

			case 'sentdata':
				return $this->sentData;
			break;

			case 'sentid':
			case 'tdocsid':
			case 'senttdocsid':
				return $this->tDocsId;
			break;

			case 'camporef':
			case 'camporeferencia':
				return $this->campo_referencia;
			break;

			case 'temanexo':
			case 'temanexos':
			case 'attachcount':
			case 'attachmentcount':
				return count($this->attachListInfo);
			break;

			case 'error';
				return mb_check_encoding($this->error, 'UTF8') ?
					utf8_decode($this->error) :
					$this->error;
			break;

		endswitch;

		return null;
	}

	/**
	 * Consulta o banco de dados para localizar documentos do TDocs
	 * associados ao PosVenda.
	 * ==== ESTE É UM MÉTODO **PRIVADO** usado por outros métodos ====
	 * Se for necessário fazer alguma modificação, é melhor chamar ele
	 * e alterar o que precisar em outro método.
	 *
	 * O segundo parâmetro é para recuperar apenas a consulta SQL.
	 * Retorna o próprio objeto, deixando as informações dos anexos no
	 * atributo `attachListInfo`.
	 */
	private function findDocuments(array $filter, $getSql=false) {

		if (!isset($filter['fabrica'])) {
			$filter['fabrica'] = $this->fabrica;
		}

		if (!isset($filter['situacao'])) {
			$filter['situacao'] = 'ativo';
		}

		$arrIDs = array();
		$sql = sql_cmd('tbl_tdocs', '*', $filter);

		if ($sql[0] == 'S') {
			if ($getSql)
				return $sql;

			$res = pg_query(self::$conn, $sql . "\n ORDER BY data_input");

			$this->url = null;
			if (pg_num_rows($res)) {
				$rows = pg_fetch_all($res);

				foreach ($rows as $row) {
					$extra = reset(json_decode($row['obs'], true));
					$id = $row['tdocs'];
					$arrIDs[$id] = array(
						'tdocs_id'      => $row['tdocs_id'],
						'contexto'      => $row['referencia'],
						'referencia'    => $row['campo_referencia'],
						'referencia_id' => $row['referencia_id'],
						'situacao'      => $row['situacao'],
						'filename'      => $extra['filename'],
						'link'          => $this->getDocumentLocation($row['tdocs']),
						'extra'         => $extra,
					);
				}
			}
		}

		$this->attachListInfo = $arrIDs;
		return $this;
	}

	/**
	 * @method getDocumentsByRef(int $objId[, string $ctx, string $s_ctx])
	 * @return $this
	 * @description
	 *     objID é o ID da tabela de referência. Por exemplo:
	 *
	 *     getDocumentsByRef(12345674[, 'os'])
	 *
	 *     irá procurar:
	 *     SELECT tdocs_id
	 *       FROM tbl_docs
	 *      WHERE referencia_id = 12345674
	 *        AND referencia = 'os'
	 *        AND situacao = 'ativo'
	 *
	 *     objID é o TDocsID (hash). Por exemplo:
	 *
	 *     getDocumentsByRef('35359d7919103dfbfcccf31e441ebfba69eb95cda275a95184a7978982c3368b', 'comunicados')
	 *
	 *     irá procurar:
	 *     SELECT tdocs_id
	 *       FROM tbl_docs
	 *      WHERE tdocs_id = '35359d7919103dfbfcccf31e441ebfba69eb95cda275a95184a7978982c3368b'
	 *        AND referencia = 'comunicado'
	 *        AND situacao = 'ativo'
	 */
	public function getDocumentsByRef($objId, $ctx=null, $s_ctx=null) {

		if ($ctx) {
			$this->setContext($ctx, $s_ctx);
		}

		$where = array(
			'fabrica'  => $this->fabrica,
			'contexto' => $this->context,
			'situacao' => 'ativo'
		);

		if (is_numeric($objId)) {
			$where['referencia_id'] = (int)$objId;
		} elseif (preg_match("/^[0-9a-f]{64}$/", $objId)) {
			$where['tdocs_id'] = $objId;
		} elseif (preg_match('/^[a-zA-Z0-9_.-]+/', $name)) {
			$where['obs='] = " ~* E'.*\"filename[^[:alnum:]_]+$name"."[^\"]*\"'";
		} else {
			$this->error = "Nome do arquivo inválido!";
			return false;
		}

		// Se tiver um subcontexto, procurar apenas os anexos desse subtipo...
		//if ($this->referencia != $this->context)
			$where['referencia'] = $this->referencia;

		return $this->findDocuments($where);
	}

	/**
	 * @method getDocumentsByName(string $name[, string $context])
	 * @param $name  string  Required  Parte do nome do arquivo (desde o início)
	 * @param $ctx   string  Optional  (sub)contexto do objeto
	 * @description
	 * Permite localizar o arquivo pelo "nome original", fábrica e opcionalmente
	 * pelo contexto.
	 */
	public function getDocumentsByName($name, $ctx=null, $id=null) {
		if (!preg_match('/^[a-zA-Z0-9_.-]+/', $name)) {
			$this->error = "Nome do arquivo inválido!";
			return false;
		}

		// código para recuperar o nome do JSON.
		$where = array(
			'fabrica' => $this->fabrica,
			'situacao' => 'ativo',
			'obs=' => " ~* E'.*\"filename[^[:alnum:]_]+$name"."[^\"]*\"'"
		);

		if ($ctx) {
			$this->setContext($ctx);
			// Se tiver um subcontexto, procurar apenas os anexos desse subtipo...
			if ($this->referencia != $this->context) {
				$where['referencia'] = $this->referencia;
			}else{
				$where['contexto'] = $ctx;
			}
		}

		if($id){
			$where['referencia_id'] = $id;
		}

		return $this->findDocuments($where);
	}

	/**
	 * @method  getDocumentById()
	 * @param   int  $tdocs  ID da tabela tbl_tdocs, obrigatório
	 * @param   $ctx   string  Optional  (sub)contexto do objeto
	 * @description
	 * Retorna as informações do documento referenciado pelo ID DA TABELA
	 */
	public function getDocumentById($tdocs, $ctx=null) {

		if (!is_numeric($tdocs))
			return false;

		if ($ctx) $this->setContext($ctx);

		return $this->findDocuments(array('tdocs' => abs((int)$tdocs)));
	}

	/**
	 * @method  getDocumentHistory()
	 * @param   int  $tdocs  Required ID da tabela tbl_tdocs, obrigatório
	 * @param   int  $max    Optional Máximo de entradas do histórico (NULL: todas)
	 * @description
	 * Retorna o histórico de alterações do documento referenciado pelo ID DA TABELA
	 */
	public function getDocumentHistory($tdocs, $max=null) {

		if ($this->getDocumentById($tdocs)->hasAttachment) {
			$sql = sql_cmd('tbl_tdocs', 'tdocs_id, obs', $tdocs);
			$res = pg_query(self::$conn, $sql);

			if (!is_resource($res)) {
				$this->error    = "Erro durante a consulta do histórico do documento";
				$this->sqlError = pg_last_error(self::$conn);
				return false;
			}

			list($curHash, $json) = pg_fetch_row($res, 0);

			$history = json_decode($json, true); // array

			foreach ($history as $idx => $rec) {
				if (is_numeric($max) and count($hist) > $max) break;

				if ($rec['acao'] == 'renomear') {
					$newname = $rec['filename'];
					$oldname = $rec['oldName'];
					continue;
				}

				if (!in_array($rec['acao'], array('anexar','substituir')))
					continue;

				if ($rec['acao'] == 'substituir') {
					$hash = $nextHash ? : $curHash;
					$nextHash = $rec['oldHash'];

					$filename = ($newname and $oldname == $rec['filename']) ?
						$newname : $rec['filename'];

					$hist[] = array(
						'tdocs_id' => $hash,
						'date'     => $rec['date'],
						'link'     => self::API2 . self::TDOCS_BASE_LINK . $hash . '/file/' . $filename,
						'filename' => $filename,
						'filesize' => $filesize
					);
				}

				if ($rec['acao'] == 'anexar') {
					$filename = ($newname and $oldname == $rec['filename']) ?
						$newname : $rec['filename'];

					$hist[] = array(
						'hash'     => ($nextHash) ? : $curHash,
						'date'     => $rec['date'],
						'link'     => self::API2 . self::TDOCS_BASE_LINK . $hash . '/file/' . $filename,
						'filename' => $filename,
						'filesize' => $filesize
					);
				}
				unset($newname, $oldname, $hash);
			}
		}
		return $hist;
	}

	public function getDocumentInfo($tdocs, $idx=0) {

		$where = array(
			'fabrica'  => $this->fabrica,
			'contexto' => $this->context
		);

		if (is_numeric($tdocs) and strlen($tdocs) < 24) {
			$where['tdocs'] = $tdocs;
		} else {
			$where['tdocs_id'] = $tdocs;
		}

		$sql = sql_cmd('tbl_tdocs', '*', $where);

		$res = pg_query(self::$conn, $sql);

		if (!pg_num_rows($res)) {
			return false;
		}

		$row = pg_fetch_assoc($res, 0);

		$ref = strpos($row['referencia'], '.') ? $row['contexto'] : $row['referencia'];
		$this->setContext($ref);

		$extra = json_decode($row['obs'], true);
		$id = $row['tdocs'];

		$obsID = isset($extra[$idx]) ? $idx : 0;

		// user data
		$x = $extra[$obsID]['usuario'];

		if (isset($x['posto'])) {
			$user = array(
				'accessType'   => 'posto',
				'posto'        => (int)$x['posto']['posto'],
				'codigo_posto' => $x['posto']['codigo'],
			);
			if (isset($x['posto']['login_unico'])) {
				$user['login_unico'] = $x['posto']['login_unico'];
			}
		}

		if (isset($x['admin'])) {
			$user = array(
				'accessType' => 'admin',
				'login'      => $x['admin']['login'],
				'admin'      => (int)$x['admin']['admin']
			);
		}

		$a = array(
			'tDocsId'      => $row['tdocs_id'],
			'insertDate'   => $row['data_input'],
			'context'      => $this->getContext($tdocs),
			'referenciaId' => (int)$row['referencia_id'],
			'lastModified' => $extra[$obsID]['date'],
			'fileName'     => $extra[$obsID]['filename']? : 'NONAME',
			'situacao'     => $row['situacao'],
			'user'         => $user
		);
		if ($extra[$obsID]['filename'])
			$a['link'] = $this->getDocumentLocation($row['tdocs']);

		return $a;

	}

	public function checkDocumentId($ref) {

        $distinct = FALSE;
        
        if (!$this->campo_referencia) {
			throw new Exception("Contexto do documento não informado ou inválido!");
		}

		list($tabela, $campo) = explode('.', $this->campo_referencia);

		switch ($tabela):
			case 'tbl_produto':    $campo_fabrica = 'fabrica_i'; break;
			case 'tbl_hd_chamado': $campo_fabrica = 'fabrica_responsavel'; break;
			case 'tbl_hd_chamado_item':
			case 'tbl_hd_chamado_analise':
			case 'tbl_hd_chamado_correcao':
			case 'tbl_hd_chamado_melhoria':
			case 'tbl_hd_chamado_requisito':
				$campo_fabrica = 'fabrica_responsavel';
				$tabela       .= "\n  JOIN tbl_hd_chamado USING(hd_chamado)";
				break;
			case 'tbl_routine_schedule_log':
				$campo_fabrica = "tbl_routine.factory";
				$tabela .= "
					INNER JOIN tbl_routine_schedule USING(routine_schedule)
					INNER JOIN tbl_routine USING(routine)
				";
				break;
			case 'tbl_loja_b2b_kit_peca' :
			case 'tbl_loja_b2b_banner' :
			case 'tbl_loja_b2b_peca' :
				$campo_fabrica = "tbl_loja_b2b.fabrica";
				$tabela       .= "\n  JOIN tbl_loja_b2b USING(loja_b2b)";
				break;
			// não têm campo fábrica
			case 'tbl_posto':
				$campo_fabrica = null;
				break;
			case 'tbl_routine':
				$campo_fabrica = 'factory';
				break;
			case 'tbl_extrato_pagamento':
				$campo_fabrica = null;
				break;
            case 'tbl_extrato_agrupado':
                $distinct = TRUE;
                $campo_fabrica = " tbl_extrato.fabrica";
                $tabela .= "\nJOIN tbl_extrato USING(extrato)";
                break;
            case 'tbl_fornecedor':
                $distinct = TRUE;
                $campo_fabrica = " tbl_fornecedor_fabrica.fabrica";
                $tabela .= "\nJOIN tbl_fornecedor_fabrica USING(fornecedor)";
                break;


			default: $campo_fabrica = 'fabrica';
		endswitch;

		$where = array($campo => $ref);

		if (!is_null($campo_fabrica)) {
			$where[$campo_fabrica] = $this->fabrica;
		}

        if ($distinct) {
            $campo = "DISTINCT $campo";
        }

		$sql = sql_cmd($tabela, $campo, $where);

		if (isCLI and DEBUG === true || pg_last_error(self::$conn))
			pecho("Check ID: $sql");

		$res = pg_query(self::$conn, $sql);
		return pg_num_rows($res) === 1;
	}

	/**
	 * @method getDocumentLocation()
	 * @param  int  $tdocs  ID da tabela tbl_tdocs, obrigatório
	 * @param  bool $perm   Opcional, tenta solicitar ao TDocs uma imagem menor
	 * @return string       URL do arquivo, com o nome no final, para que possa ser
	 *                      interpretado como um arquivo pelo navegador ou outro client
	 */
	public function getDocumentLocation($tdocs, $perm=false) {

		if (!is_numeric($tdocs) and $perm) {
			return $this->getPermalink($tdocs);
		}

		$sql = sql_cmd(
			'tbl_tdocs',
			'tdocs_id, obs',
			array(
				'tdocs' => (int)$tdocs,
				'fabrica' => $this->fabrica
			)
		);

		if (!$perm and pg_num_rows($res = pg_query(self::$conn, $sql))) {
			$obs = json_decode(pg_fetch_result($res, 0, 'obs'), true);
			$fileName = $obs[0]['filename'];

			$this->url = self::API2 .
				self::TDOCS_BASE_LINK . pg_fetch_result($res, 0, 'tdocs_id') .
				(($thumbs) ? '/size/thumb/' : '') .
				'/file/' . $fileName;

			return $this->url;
		}

		return null;
		return "javascript:alert('Arquivo não encontrado')";
	}

	private function getPermalink($tDocsId) {
		if (!preg_match("/^[0-9a-f]{64}$/i", $tDocsId)) {
			return null;
		}

		$this->api
			->setUrl(self::API2 . self::TDOCS_URI . 'link')
			->setMethod('GET')
			->addParam(
				array(
					'id' => $tDocsId,
					'permaLink' => 'true'
				)
			)
			->send();

		if ($this->api->statusCode == 200) {
			$info = json_decode($this->api->response['body'], true);
			if (array_key_exists('link', $info)) {
				return $info['link'];
			}
		}
	}

	public function getUrl($tDocsID, $type=null) {

		if ($type === true)
			$type = 'thumb';

		if (preg_match("/^[0-9a-f]{64}$/", $tDocsID)) {
			$info = $this->getDocumentInfo($tDocsID);
			// pre_echo($info, 'DATA', true);
			return $info['link'];
		}
		return null;
	}

	/**
	 * ### Solicitar tag &lt;A&gt; para o documento
	 * `TDocs::getDocumentHtmlLink(string $tDocs, string $text)`
	 * Retorna a tag `<a>` apontando para a URL do documento, e com o `$texto` como
	 * `innerHTML`. Usa o método [getDocumentLocation()](#getDocLoc), portanto
	 * precisa do ID da tabela como primeiro parâmetro.
	 */
	/* public function getDocumentHtmlLink($tDocsID, $lnkStr) { */
	/* 	if ($url = $this->getDocumentLocation($tDocsID)) */
	/* 		return '<a target="_blank" href="' . $url . '">' . "$lnkStr</a>"; */
	/* 	return ''; */
	/* } */

	public function setDocumentFileName($tdocs, $newName) {
		if ($this->getDocumentsByRef($tdocs) == 1) {
			$docData = reset($this->attachListInfo);

			$obsData = array(
				'acao'     => 'renomear',
				'filename' => $newName,
				'oldName'  => $docData['filename'],
				'date'     => is_date('agora'),
			);

			// Adiciona os dados adicionais no campo de observações
			$this->appendObs($tdocs, $this->getUserInfo($obsData));
		}

		return $this;
	}

	/**
	 * Configura context e referência de acordo com o "tipo anexo"
	 * usado na class anexaS3/AmazonTC
	 * Permite passar
	 *	Contexto
	 *	Contexto e Subcontexto
	 *	Subcontexto
	 *
	 *	No último caso, infere o contexto e reconfigura o objeto de
	 *	acordo com o tipo e subtipo, conforme estiver no array de
	 *	configuração.
	 */
	public function setContext($ctx, $sCtx='') {
		// self::$attTypes = include_once(__DIR__ . DIRECTORY_SEPARATOR . 'tdocs_att_types.php');
		$tipo    = preg_replace('/[^a-z]/', '', strtolower(API2::utf8_ascii7($ctx)));
		$subtipo = preg_replace('/[^a-z]/', '', strtolower(API2::utf8_ascii7($sCtx)));

		$tipos = array_keys(self::$attTypes);

		// Primeiro vê se existe tipo e subtipo
		if (in_array($tipo, $tipos)) {
			$Ctx = self::$attTypes[$tipo];
			$referencia = reset($Ctx['referencia']);

			$this->context = $tipo;
			$this->referencia = $tipo;
			$this->campo_referencia = $referencia;

			if ($subtipo and isset($Ctx['subcontexto'])) {
				if (DEBUG===true)
					pre_echo($Ctx['subcontexto'], "Procurando um subcontexto $subtipo em");

				$subtipos = array_keys($Ctx['subcontexto']);

				if (in_array($subtipo, $subtipos)) {
					$this->context = $tipo;
					$this->referencia = $subtipo;
					$this->campo_referencia = isset($Ctx['referencia'][$subtipo]) ?
						$Ctx['referencia'][$subtipo] :
						reset($Ctx['referencia']);
					return $this;
				}

				foreach ($Ctx['subcontexto'] as $subCtxId => $subtipos) {
					if (in_array($subtipo, $subtipos)) {
						$this->context = $tipo;
						$this->referencia = $subCtxId;
						$this->campo_referencia = isset($Ctx['referencia'][$subCtxId]) ?
							$Ctx['referencia'][$subCtxId] :
							reset($Ctx['referencia']);
						return $this;
					}
				}
			}
			return $this;
		}

		foreach (self::$attTypes as $contexto => $Ctx) {
			$alias = isset($Ctx['alternate']) ? $Ctx['alternate'] : array();

			if (in_array($tipo, $alias)) {
				$this->context = $contexto;
				$this->referencia = $contexto;
				$this->campo_referencia = reset($Ctx['referencia']);
				// pecho("Alias encontrado! Tipo $contexto");

				if ($subtipo and isset($Ctx['subcontexto'])) {
					// pecho("Alias encontrado! Tipo $contexto");
					$subtipos = array_keys($Ctx['subcontexto']);
					if (in_array($subtipo, $subtipos)) {
						$this->referencia = $subtipo;
						$this->campo_referencia = isset($Ctx['referencia'][$subtipo]) ?
							$Ctx['referencia'][$subtipo] :
							reset($Ctx['referencia']);
						return $this;
					}

					foreach ($Ctx['subcontexto'] as $subCtxId => $subtipos) {
						if ($subtipo == $subCtxId or in_array($subtipo, $subtipos)) {
							$this->context = $tipo;
							$this->referencia = $subCtxId;
							$this->campo_referencia = isset($Ctx['referencia'][$subCtxId]) ?
								$Ctx['referencia'][$subCtxId] :
								reset($Ctx['referencia']);
							return $this;
						}
					}
				}
				return $this;
			}
		}

		// última tentativa: procurar o tipo como um subtipo, por exemplo:
		// revenda é um subtipo de os, assim, tipo=os subtipo=revenda
		foreach (self::$attTypes as $ctxID => $Ctx) {
			if (isset($Ctx['subcontexto']))
				foreach ($Ctx['subcontexto'] as $subCtxId => $subtipos) {
					if ($tipo == $subCtxId or in_array($tipo, $subtipos)) {
						$this->context = $ctxID;
						$this->referencia = $subCtxId;
						$this->campo_referencia = isset($Ctx['referencia'][$subCtxId]) ?
							$Ctx['referencia'][$subCtxId] :
							reset($Ctx['referencia']);
						return $this;
					}
			}
		}

		return $this; // avaliar se não deveria jogar uma exeção se não encontra o tipo.
	}

	/**
	 * @method resetContext()
	 * @desc   se em algum momento é necessário, permite limpar os atributos referentes
	 * a contexto, subcontexto e campo_referencia.
	 */
	public function resetContext() {
		$this->context = null;
		$this->referencia = null;
		$this->campo_referencia = null;

		return $this;
	}

	public function getContext() {
		if ($this->context)
			return $this->context .';'. $this->referencia . ';' . $this->campo_referencia;
		return null;
	}

	/**
	 * @method uploadFileS3(mixed $arquivo, int $ref_id[, string $ctx, string, $sCtx])
	 * @return TRUE se TDocs retornou 201, joga uma exeção em caso de erro retornado
	 *         pela API.
	 * @description
	 * Envia o arquivo (pode ser String como path completo, ou um array tipo $_FILES['file'])
	 * e salva as informações pertinentes na tabela tbl_tdocs
	 *
	 */
	public function uploadFileS3($arquivo, $refId, $overwrite=true, $ctx=null, $ref=null) {

		if ($ctx) {
			// echo "CONTEXTO ANTES: " . $this->getContext() . PHP_EOL;
			$this->setContext($ctx, $ref);
			// echo "CONTEXTO: " . $this->getContext() . PHP_EOL;
		}
		$acao  = 'anexar';
		$tdocs = null;
		$overWriteInfo = null;

		if (!$this->sendFile($arquivo)) {
			return false;
		}

		return $this->setDocumentReference($this->sentData, $refId, 'anexar', $overwrite, $ctx);

	}

	/**
	 * @method sendFile(mixed $arquivo)
	 * @param  $arquivo  mixed   Path completo ao arquivo, ou array tipo $_FILES['file']
	 * @return String            Hash que identifica o arquivo no TDocs
	 * @description
	 * Permite enviar um arquivo ao TDocs e recuperar seu ID.
	 * Também mantém os dados originais do arquivo, juntamente com o TDocs ID,
	 * na propriedade TDocs::sentData, para assim poder usar ele mais para
     * a frente. O array contém o nome, nome temporário se tinha, tamanho,
     * e se teve sucesso, o ID no TDocs.
	 */
	public function sendFile($arquivo) {

		if (!is_array($arquivo)) {
			if (!is_readable($arquivo)) {
				$this->error = "Arquivo $arquivo não encontrado!";
				return false;
			}

			$fileName = self::utf8_ascii7(pathinfo($arquivo, PATHINFO_BASENAME));
			$fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
			$fileSize = filesize($arquivo);

			$arquivo = array(
				'tmp_name' => $arquivo,
				'name'     => $fileName,
				'size'     => $fileSize,
				'type'     => mime_content_type($arquivo),
				'error'    => null
			);
		} else {
			$fileName = self::utf8_ascii7($arquivo['name']);
			$fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
			$fileSize = $arquivo['size'];
			$arquivo['name'] = $fileName;
		}
		if (DEBUG === true)
			pre_echo($arquivo, "Subindo arquivo $fileName para o TDocs...");

		if (!file_exists($arquivo['tmp_name'])) {
			$this->error = "O arquivo {$arquivo['tmp_name']} não está disponÃ­vel";
			return false;
		}

		$this->getAppKeys('TDOCS', 'PRODUCTION');

		$this->api
			->setUrl(self::API2 . self::TDOCS_URI . 'document')
			// ->setUrl('http://ww2.telecontrol.com.br/mlg/teste/file.php')
			// ->addParam(array('test'=>'valor 1', 'param2'=>'valor 2'))
			->clearParams()
			->addFile($arquivo['tmp_name'], 'file')
			->setMethod('POST')
			->send();

		if ($this->api->statusCode == 201) {
			$resp = $this->api->response['body'];
			$this->tDocsId = preg_replace('/.*"([a-z0-9]{48,})".*/', '$1', $resp);

			$arquivo['tdocs_id'] = $this->tDocsId;
			$this->sentData = $arquivo;

			$this->url = self::API2 .
				self::TDOCS_BASE_LINK . $this->tDocsId."/file/$fileName";

			$this->thumb = self::API2 .
				self::TDOCS_BASE_LINK . $this->tDocsId."/size/thumb/file/$fileName";

			return $this->tDocsId;
		}

		$this->error = $this->api->erro;
		return false;
	}

	/**
	 * @method  setDocumentReference()
	 * @alias   setDocumentRef(), vincularAnexo()
	 * @param   $fileInfo  array  InformaçÃµes do arquivo: nome, tamanho, etc ($_FILES[])
	 * @param   $refId     int    ID do campo referencia do contexto (os, comunicado, etc.)
	 * @param   $action    String Default 'anexar', ação a ser salva nas observaçÃµes
	 * @param   $overwrite bool   TRUE  para substituir uma referência pela nova,
	 *                            FALSE para criar um novo registro para a mesma referência
	 * @param   $contexto  string Optional, contexto, se for diferente do atual do objeto
	 * @return  Object $this
	 * @description
	 * Permite associar um arquivo do TDocs com um "objeto" do PósVenda através do contexto
	 * e a referência. Assim, é possÃ­vel subir um arquivo no TDocs SEM ter a referência, e só
	 * depois associar o arquivo a uma OS, comunicado ou outro tipo de "objeto".
	 * O rimeiro parÃ¢metro é um array que deve conter as chaves:
	 *   tdocs_id => string(64), // hash do arquivo
	 *   name     => string,     // nome do arquivo
	 *   size     => integer,    // tamanho em bytes do arquivo
	 * A princípio este método NÃO VALIDA se o documento TDocs existe ou não. Se usado da maneira
	 * correta, não deveria haver a necessidade.
	 */
	public function vincularAnexo(array $fileInfo, $refId, $action='anexar', $overwrite=true, $ctx=null) {
		return $this->setDocumentReference($fileInfo, $refId, $action, $overwrite, $ctx);
	}
	public function setDocumentRef(array $fileInfo, $refId, $action='anexar', $overwrite=true, $ctx=null) {
		return $this->setDocumentReference($fileInfo, $refId, $action, $overwrite, $ctx);
	}

	public function setDocumentReference(array $fileInfo, $refId, $action='anexar', $overwrite=true, $ctx=null, $typeID = null) {

		if ($ctx)
			$this->setContext($ctx);

		$tDocsId = $fileInfo['tdocs_id'];

		if (!preg_match("/^[0-9a-f]{64}$/i", $tDocsId)) {
			$msg = 'ID do arquivo inválido!';
			if (isCLI or DEBUG===true)
				$msg .= ": '$tDocsId'";
			throw new Exception($msg);
		}

		if (empty($ctx) OR $ctx != "logomarca_posto"){
			if ($ctx != 'osrevenda' || (!isset($fileInfo['termo_entrega']) && !isset($fileInfo['termo_devolucao']))) {
				if (!preg_match("/[a-z0-9]{64}/", $refId) and !$this->checkDocumentId($refId)) {
					$this->error = 'Identificador '.$refId.' não existe para o documento';
					return false;
				}
			}
		}		

		$tDocs = null;
		if ($overwrite === true or preg_match("/[a-z0-9]{64}/", $refId)) {
			if ($this->getDocumentsByRef($refId)->hasAttachment) {
				$action = 'substituir';
				$currAtt = reset($this->attachListInfo);
				$curHash = $currAtt['tdocs_id'];
				$tDocs = key($this->attachListInfo);
			}
		}

		$insData = array(
			'tdocs_id'      => $fileInfo['tdocs_id'],
			'fabrica'       => $this->fabrica,
			'contexto'      => $this->context,
			'referencia'    => $this->referencia,
		);

		if (strlen($refId) != 64 and is_numeric($refId)) {
			$insData['referencia_id'] = $refId;
		}

		$sql = sql_cmd('tbl_tdocs', $insData, $tDocs);

		if ($sql[0] === 'I') {
			$sql .= ' RETURNING tdocs';
		}

		if ($sql[0] === 'I' or $sql[0] == 'U') {
			$res = pg_query(self::$conn, $sql);

			if (!is_resource($res)) {
				$this->error    = 'Erro ao gravar as infromaçÃµes do anexo! ';
				$this->sqlError = pg_last_error(self::$conn);

				if (isCLI or DEBUG === true)
					echo pg_last_error(self::$conn).PHP_EOL.$sql;

				return false;
			}

			$newID = ($sql[0] === 'I') ? pg_fetch_result($res, 0, 'tdocs') : $tDocs;

			// Parametro para validar se o termo foi anexado. HD-6175294
			if (isset($fileInfo['termo_entrega'])) {
				$attachmentInfo = array(
					'acao'        		=> $action,
					'filename'    		=> $fileInfo['name'],
					'filesize'    		=> $fileInfo['size'],
					'date'        		=> is_date('agora'),
					'termo_entrega'     => 'ok',
				);				
			} else if (isset($fileInfo['termo_devolucao'])) {
				$attachmentInfo = array(
					'acao'        		=> $action,
					'filename'    		=> $fileInfo['name'],
					'filesize'    		=> $fileInfo['size'],
					'date'        		=> is_date('agora'),
					'termo_devolucao'   => 'ok',
				);
			} else {
				$attachmentInfo = array(
					'acao'        => $action,
					'filename'    => $fileInfo['name'],
					'filesize'    => $fileInfo['size'],
					'date'        => is_date('agora'),
				);
			}

			if (isset($fileInfo['tipo_anexo'])) {
				$attachmentInfo['tipo_anexo'] = $fileInfo["tipo_anexo"];
			}

			if ($overwrite and $action != 'anexar') {
				$attachmentInfo['oldHash'] = $curHash;
			}

			if (strlen($typeID) > 0) {
				$attachmentInfo["typeId"] = $typeID;
			}

			// Adiciona os dados adicionais no campo de observações
			$this->appendObs(
				$newID,
				$this->getUserInfo($attachmentInfo)
			);

			$this->referencia_id = $refId;
			$this->getDocumentsByRef($newID);
			return true;
		}
		return false;
    }

	/**
	 * @method   deleteFileById()
	 * @param    $id    required  mixed  TDocsID / tDocs
	 * @return   boolean
	 * @also     $this->error  com a mensagem de erro da API
	 * @desc
	 * Exclui um arquivo do S3, não poderá mais ser acessado.
	 * pode ser o id da tabela ou o hash do TDocs.
	 */
	public function deleteFileById($tDocsID) {
		$where = array(
			'fabrica' => $this->fabrica,
			'contexto' => $this->context
		);

		if (is_numeric($tDocsID) and strlen($tDocsID) < 16) {
			$where['tdocs'] = $tDocsID;
			$podeExcluir = false;
		} else {
			$where['tdocs_id'] = $tDocsID;
			$podeExcluir = true;
			$excluirDoBanco = false;
		}

		$sql = sql_cmd('tbl_tdocs', 'tdocs, tdocs_id', $where);

		$res = pg_query(self::$conn, $sql);

		if (pg_num_rows($res) or $podeExcluir) {
			if (pg_num_rows($res)) {
				$tDocsID = pg_fetch_result($res, 0, 'tdocs_id');
				$tdocs   = pg_fetch_result($res, 0, 'tdocs');
				$excluirDoBanco = true;
			}

			$this->api
				->setUrl(self::API2 . self::TDOCS_URI . 'document')
				->addParam(
					array(
						'id' => $tDocsID,
						'appKey' => $this->appKey
					)
				)->send('DELETE');

			if ($this->api->statusCode == 204) {
				if ($excluirDoBanco) {
					$sql = sql_cmd(
						'tbl_tdocs',
						array('situacao' => 'excluido'),
						$tdocs
					);

					if (DEBUG===true)
						pre_echo($sql);

					if ($sql[0] == 'U') {
						$res = pg_query(self::$conn, $sql);
					}

					if (pg_affected_rows($res) === 1) {
						$this->appendObs(
							$tdocs,
							$this->getUserInfo(
								array(
									'acao'=>'eliminar',
									'date' => is_date()
								)
							)
						);
						// Retira o "arquivo" da lista.
						if (isset($this->attachListInfo[$tdocs])) {
							unset($this->attachListInfo[$tdocs]);
						}
						return true;
					}
					return false;
				}
				return true;
			}
			$this->error = $this->api->error;
		}
		return false;
	}

	/**
	 * @method removeDocumentByType()
	 * @param $ref  ID da tabela do contexto (os, os_item, laudo_tecnico...)
	 * @param $ctx  Contexto (ou subcontexto) do objeto
	 * @param $sCtx Subcontexto (maior segurança)
	 * @return mixed
	 *         FALSE se não encontrou nenhum arquivo com o filtro
	 *         int   quantidade de ítens "excluídos"
	 * Este método permite "excluir" todos os anexos de um contexto/id
	 * Para excluir um elemento específico, tem que usar o removeDocumentById()
	 */
	public function removeDocumentsByType($ref, $ctx, $sCtx=null) {
		$list = $this->getDocumentsByRef($ref, $ctx, $sCtx);

		if (!$list) {
			$this->errorMsg = 'Nenhum anexo para esta referência.';
			return false;
		}

		$IDs = array_keys($list);
		foreach ($IDs as $doc) {
			if (false === $this->removeDocumentById($doc['tdocs'])) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Exclui um arquivo (muda seu status/situação para 'inativo'),
	 * pode ser o id da tabela ou o hash do TDocs.
	 */
	public function removeDocumentById($tDocsID) {
		$where = array(
			'fabrica' => $this->fabrica,
			'contexto' => $this->context
		);

		if (is_numeric($tDocsID) and strlen($tDocsID) < 16) {
			$where['tdocs'] = $tDocsID;
		} else {
			$where['tdocs_id'] = $tDocsID;
		}

		$sql = sql_cmd('tbl_tdocs', 'tdocs, tdocs_id', $where);

		$res = pg_query(self::$conn, $sql);

		if (pg_num_rows($res)) {
			$tDocsID = pg_fetch_result($res, 0, 'tdocs_id');
			$tdocs   = pg_fetch_result($res, 0, 'tdocs');

			$sql = sql_cmd(
				'tbl_tdocs',
				array('situacao' => 'inativo'),
				$tdocs
			);

			if (DEBUG===true)
				pre_echo($sql);

			if ($sql[0] == 'U') {
				$res = pg_query(self::$conn, $sql);
			}

			if (pg_affected_rows($res) === 1) {
				$this->appendObs(
					$tdocs,
					$this->getUserInfo(
						array(
							'acao'=>'eliminar',
							'date' => is_date('now')
						)
					)
				);
				// Retira o "arquivo" da lista.
				if (isset($this->attachListInfo[$tdocs])) {
					unset($this->attachListInfo[$tdocs]);
				}
				return true;
			}
		}
		return false;
	}

	private function getUserInfo(array $extra=array()) {
		global $login_admin, $login_posto, $login_unico,
			$login_fabrica_nome, $cook_login_unico, $login_login,
			$cook_admin, $cook_posto_fabrica;

		$scriptName = basename($_SERVER['PHP_SELF'] ? : $argv[0]);

		$clientIP = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ?
			$_SERVER['HTTP_X_FORWARDED_FOR'] :
			$_SERVER['REMOTE_ADDR'] ? :
			'LOCALHOST';

		$ui = array(
			'fabrica'   => (int)$this->fabrica,
			'usuario'   => array(),
			'page'      => $scriptName,
			'access_IP' => $clientIP
		);

		$ui = array_merge($extra, $ui);

		if ($_COOKIE['cook_admin']) {
			$ui['usuario']['admin'] = array(
				'admin' => (int)$_COOKIE['cook_admin'],
				'login' => $login_login
			);
			if (isset($GLOBALS['admin_cliente']))
				$ui['usuario']['admin']['admin_cliente'] = $_COOKIE['cook_cliente_admin'];
		}

		if ($GLOBALS['login_posto']) {
			$ui['usuario']['posto'] = array(
				'posto'  => (int)$GLOBALS['login_posto'],
				'codigo' => $_COOKIE['cook_posto_fabrica'],
			);
			if ($_COOKIE['cook_login_unico'])
				$ui['usuario']['posto']['login_unico'] = (int)$_COOKIE['cook_login_unico'];
		}
		return $ui;
	}

	/**
	 * @method appendObs()
	 * Adiciona ao registro do banco uma observação no campo `obs`
	 * que é tratado como um array JSON.
	 * Retorna FALSE se não foi possÃ­vel adicionar a informação
	 */
	private function appendObs($tdocs, array $extra) {
		$res = pg_query(self::$conn, sql_cmd('tbl_tdocs', 'obs', $tdocs));
		$obs = pg_fetch_result($res, 0, 'obs');

		if (!pg_num_rows($res)) {
			$this->error = "ID $tdocs não existe!";
			$this->sqlError = pg_last_error(self::$conn);
			return false;
		}

		// 2016-09-26: Acrescenta flag se o anexo não é em produção
		if (SERVER_ENV == 'DEVELOPMENT') {
			$extra['development'] = true;
		}

		/**
		 * Informações são salvas em data decrescente, de forma que o primeiro
		 * elemento do 'array' seja o mais atual
		 */
		$aObs = json_decode($obs, true) ? : array();

		if ($aObs[0]['filename'] == $extra['filename'] and $aObs[0]['filesize'] == $extra['filesize']) {
			return false;
		}

		$newObs = array(0 => $extra);

		if (count($aObs)) {
			foreach ($aObs as $oldObs) {
				$newObs[] = $oldObs;
			}
		}

		$jObs = json_encode($newObs);

		$sqlUpdObs = sql_cmd('tbl_tdocs', array('obs'=>$jObs), $tdocs);

		if (DEBUG === true)
			pre_echo($sqlUpdObs, 'UPDATE');

		$resUpd = pg_query(self::$conn, $sqlUpdObs);

		if (!is_resource($resUpd)) {
			throw new Exception('Erro ao atualizar o objeto!');
		}

		if (!pg_affected_rows($resUpd)) {
			$this->error = "Erro ao atualizar as observaçÃµes!";
			$this->sqlError = pg_last_error(self::$conn);
			return false;
		}
		return true;
	}

	/**
	 * @method  getSql()
	 * @param   $Ctx    Array     with named parameters
	 *          OR
	 *                  String    Contexto ou Subcontexto
	 * @param   $redIDs int|Array ID ou lista de IDs (ref_id)
	 * @param   $fabs   int|Array Fabricante ou lista de fabricantes
	 */
	public function getSql($Ctx, $refIDs=null, $fabs=null) {

		if (!is_array($Ctx)) {
			$this->setContext($Ctx);
		}

		if (is_array($Ctx)) {
			return $this->findDocuments($Ctx, true);
		}

		$filtros = array(
			'fabrica' => $fabs,
			'referencia_id' => $refIDs
		);
	}

	/**
	 * Verifica se já existe uma conexão antes de associar a nova.
	 * As conexões são ponteiros para um recurso, então não duplica.
	 */
	private function setDbConn(&$con) {
		if (!is_resource(self::$conn) and is_resource($con)) {
			self::$conn = $con;
		}
	}

	public function getByHashTemp($hash_temp = null) {
		if (empty($hash_temp)) {
			throw new \Exception("hash_temp não informado");
		}

		$res = pg_query(self::$conn, "
			SELECT *
			FROM tbl_tdocs
			WHERE fabrica = {$this->fabrica}
			AND contexto = '{$this->context}'
			AND hash_temp = '{$hash_temp}'
			AND situacao = 'ativo'
		");

		if (!pg_num_rows($res)) {
			return array();
		} else {
			return pg_fetch_all($res);
		}
	}
	
	public function updateHashTemp($hash_temp, $new_hash, $context=null) {
		
		if ($context == null){
			$context = $this->context;
		}
		
		$res = pg_query(self::$conn, "
			UPDATE tbl_tdocs SET
				referencia_id = {$new_hash},
				hash_temp = NULL
			WHERE fabrica = {$this->fabrica}
			AND contexto = '{$context}'
			AND situacao = 'ativo'
			AND hash_temp = '{$hash_temp}'
		");
		
		if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) == 0) {
			return false;
		} else {
			return true;
		}
	}

	public function insertAnexoRevenda($os, $os_revenda, $nota_fiscal=null) {
		
		$sql = "SELECT 
                    tdocs_id,
                    situacao,
                    obs,
                    data_input,
                    JSON_FIELD('descricao',tbl_tdocs.obs) AS notas_tdocs
                FROM tbl_tdocs
                WHERE fabrica = {$this->fabrica}
                AND referencia = 'revenda'
                AND referencia_id = $os_revenda";
        $res = pg_query(self::$conn, $sql);
    	
    	if (pg_num_rows($res) > 0){
        	$notas_tdocs = pg_fetch_all($res);
            foreach ($notas_tdocs as $key => $value) {
                unset($notas_fiscais);
                $notas_fiscais  = preg_replace("/[^0-9]/", "", $value["notas_tdocs"]);
                $tdocs_id       = $value["tdocs_id"];
                $situacao       = $value["situacao"];
                $obs            = $value["obs"];
                $data_input     = $value["data_input"];
        	    if (!empty($notas_fiscais)){
                    if ($notas_fiscais == $nota_fiscal){
                        $insert = "INSERT INTO tbl_tdocs (
                                    tdocs_id,
                                    fabrica,
                                    contexto,
                                    situacao,
                                    obs,
                                    data_input,
                                    referencia,
                                    referencia_id
                                )VALUES(
                                    '{$tdocs_id}',
                                    $this->fabrica,
                                    'os',
                                    'ativo',
                                    '{$obs}',
                                    '{$data_input}',
                                    'os',
                                    {$os}
                                );";
                        $res_insert = pg_query(self::$conn, $insert);
                    }         
                }else{
                	$insert = "INSERT INTO tbl_tdocs (
                        tdocs_id,
                        fabrica,
                        contexto,
                        situacao,
                        obs,
                        data_input,
                        referencia,
                        referencia_id
                    )VALUES(
                        '{$tdocs_id}',
                        {$this->fabrica},
                        'os',
                        'ativo',
                        '{$obs}',
                        '{$data_input}',
                        'os',
                        {$os}
                    );";
                    $res_insert = pg_query(self::$conn, $insert);
                }
            }
        }
        
		if (strlen(pg_last_error()) > 0) {
			return false;
		} else {
			return true;
		}
	}

}

