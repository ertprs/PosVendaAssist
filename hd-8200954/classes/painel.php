<?php
//ini_set('display_errors', 0);
//error_reporting(false);
/**
 * Classe para o Painel do help desk
 */
class Painel
{
	/**
	 * Quantidade de horas trabalhadas no dia
	 * Usada nas funções de cálculo de dias e horas do ticket
	 */
	private $horasPorDia   = 8;
	private $mascaraHora   = "H\h";
	private $mascaraMinuto = " i\m\i\\n";

	private $json_file							 = 'documentos/chamados.json';
	private $arrayChamadoEmExecucao				 = array();
	private $arrayChamadoEmExecucaoKeys			 = array();
	private $arrayChamadoEmExecucaoValues		 = array();
	private $arrayChamadoStatus					 = array();
	private $arrayChamadoColuna					 = array();
	private $arrayImplodeChamadoEmExecucaoValues = array();

	private $grupoAdminColunas = array();

	private $con;

	private	$SELECT		=  "SELECT
								DISTINCT tbl_hd_chamado.hd_chamado,
								tbl_hd_chamado.titulo,
								case when tbl_backlog_item.desenvolvedor notnull and status in ('Aguard.Execução','Execução','Correção') then tbl_backlog_item.desenvolvedor else tbl_hd_chamado.atendente end as atendente,
								case when tbl_backlog_item.desenvolvedor notnull and status in ('Aguard.Execução','Execução','Correção') then ba.nome_completo else tbl_admin.nome_completo  end AS atendente_nome,
								tbl_fabrica.nome                          AS fabrica_nome,
								(tbl_hd_chamado.data + interval '5 HOUR') AS prazo,
								tbl_hd_chamado.prazo_horas,
								tbl_hd_chamado.horas_suporte,
								tbl_hd_chamado.horas_analise,
								tbl_hd_chamado.horas_desenvolvimento,
								tbl_hd_chamado.horas_teste,
								tbl_hd_chamado.horas_efetivacao,
								tbl_hd_chamado.hora_desenvolvimento,
								tbl_backlog_item.horas_analisadas,
								(select data_inicio from tbl_hd_chamado_atendente hda where (data_inicio notnull or data_termino notnull) and hda.hd_chamado = tbl_hd_chamado.hd_chamado order by data_inicio desc limit 1) as trabalho,
								TO_CHAR(tbl_hd_chamado.previsao_termino,'DD/MM HH24:MI')         AS previsao_termino,
								TO_CHAR(tbl_hd_chamado.previsao_termino_interna,'DD/MM HH24:MI') AS previsao_termino_interna,
								previsao_termino_interna as previsao_termino_interna2,
								CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino
									THEN 1
									ELSE 0
								END AS atrasou,
								CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino_interna
									THEN 1
									ELSE 0
								END AS atrasou_interno,
								tbl_hd_chamado.status,
								CASE
									WHEN tbl_hd_chamado.status = 'Parado' OR tbl_hd_chamado.status = 'Impedimento' THEN 2
									ELSE 1
								END AS prioridade_status,
								tbl_hd_chamado.prioridade,
								CASE
									WHEN tbl_hd_chamado.prioridade = 't' THEN 1
									ELSE 2
								END AS prioridade_ordena,
								tbl_hd_chamado.prioridade_supervisor,
								tbl_hd_chamado.data_aprovacao_fila,
								tbl_hd_chamado.tipo_chamado,
								CASE
									WHEN tbl_hd_chamado.tipo_chamado = 5 THEN 0
									ELSE tbl_hd_chamado.tipo_chamado
								END AS tipo_chamado_prioriza ";
	private $FROM		=  "FROM
								tbl_hd_chamado ";
	private $JOIN		=  "JOIN
								tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
						JOIN
							tbl_fabrica on tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
						LEFT JOIN tbl_backlog_item ON tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado
						LEFT JOIN tbl_admin ba ON tbl_backlog_item.desenvolvedor = ba.admin
						";
	private $WHERE   	=  "WHERE
								tbl_hd_chamado.fabrica_responsavel = 10 ";
	private $ORDER_BY	=  "ORDER BY
								prioridade_status,
								tipo_chamado_prioriza,
								prioridade_ordena,
								tbl_hd_chamado.prioridade_supervisor,
								trabalho desc,
								tbl_hd_chamado.data_aprovacao_fila
								 ";


	public function __construct($con) {
		session_start(); // session usada no "cache" de imagens

		$this->con = $con;

		$this->__setChamadoEmExecucao();

		$this->__setImplodeChamadoEmExecucaoValues();

		$this->__setGrupoAdminRegrasColunas();

		$this->__setChamadoTodosAtendentes();

		#$this->leJson();
		#$this->gravaJson(1630,966477,7,'');	// Ronaldo
		#$this->excluiJson(822,966864); exit;
	}

	/**
	 * Separa os valores que estão no JSON
	 * Valores usados nas consultas
	 */
	private function __setChamadoEmExecucao() {
		$this->arrayChamadoEmExecucao		= $this->leJson();
		$this->arrayChamadoEmExecucaoKeys	= array_keys($this->arrayChamadoEmExecucao);
		$this->arrayChamadoEmExecucaoValues	= $this->__array_values_painel();
	}

	/**
	 * Forma a string usada nas queries das funcoes de dadosColuna
	 * @return string
	 */
	private function __setImplodeChamadoEmExecucaoValues() {
		$this->arrayImplodeChamadoEmExecucaoValues = implode(",",$this->arrayChamadoEmExecucaoValues);
	}

	/**
	 * Retorna os chamados que estão no JSon
	 * @return string
	 */
	private function __array_values_painel() {
		$arrayHdChamado = array();
		$atendentes 	 = $this->arrayChamadoEmExecucaoKeys;
		$total_atendente = count($atendentes);

		foreach($atendentes AS $key => $val) {
			$o_atendente 				= $this->arrayChamadoEmExecucaoKeys[$key];
			$total_chamados_atendente 	= count($o_atendente);

			foreach($this->arrayChamadoEmExecucao[$o_atendente] as $keyHd => $valHd) {
				$keyAtendente = $this->arrayChamadoEmExecucaoKeys[$key];

				$arrayStatus[$o_atendente][$keyHd]	= $this->arrayChamadoEmExecucao[$keyAtendente][$keyHd][0]['status'];
				$arrayColuna[$o_atendente][$keyHd]	= $this->arrayChamadoEmExecucao[$keyAtendente][$keyHd][0]['coluna'];
				$arrayHdChamado[] 					= $keyHd;
			}
		}

		$this->arrayChamadoStatus = $arrayStatus;
		$this->arrayChamadoColuna = $arrayColuna;

		return $arrayHdChamado;
	}

	/**
	 * Monta o layout de cada ticket de chamado
	 * @param array $registro
	 * @return string $html
	 */
	function montaTicket($registro,$altera_status=0) {
		global $login_admin;

		define('DS',"\n");

		$hd_chamado		= $registro['hd_chamado'];

		$titulo			= $registro['titulo'];
		$titulo_tratado	= $this->__trataTitulo($titulo);

		$atendente_nome	= substr($registro['atendente_nome'], 0, 18);
		$atendente= explode(" ",$atendente_nome);
		$atendente_nome = $atendente[0];
		$atendente		= $registro['atendente'];

		$fabrica_nome	= substr($registro['fabrica_nome'], 0, 18);

		$img 			= $this->retornaFoto($atendente);

		$tipo_chamado	= $registro['tipo_chamado'];
		$status			= $registro['status'];

		$prazo						= $registro['prazo'];
		$horas_suporte				= $registro['horas_suporte'];
		$horas_analise				= $registro['horas_analise'];
		$horas_desenvolvimento		= $registro['horas_desenvolvimento'];
		$horas_analisadas		= $registro['horas_analisadas'];
		$horas_teste				= $registro['horas_teste'];
		$horas_efetivacao			= $registro['horas_efetivacao'];

		$hora_desenvolvimento		= $registro['hora_desenvolvimento'];
		$previsao_termino			= $registro['previsao_termino'];
		$previsao_termino_interna	= $registro['previsao_termino_interna'];
		$previsao_termino_interna2	= $registro['previsao_termino_interna2'];
		$data_inicio                      = $registro['data_inicio'];

		if (!isset($previsao_termino_interna))
			$previsao_termino_interna = $previsao_termino;

		$atrasou					= $registro['atrasou'];
		$atrasou_interno			= $registro['atrasou_interno'];

		if (!isset($atrasou_interno))
			$atrasou_interno = $atrasou;

		switch ($status) {
			case 'Requisitos':  $prazo_horas = $horas_suporte;			break;
			case 'Análise':		$prazo_horas = $horas_analise;			break;
			case 'Execução':	$prazo_horas = $horas_analisadas;	break;
			case 'Correção':	$prazo_horas = $horas_analisadas;	break;
			case 'Aguard.Execução':	$prazo_horas = $horas_analisadas;	break;
			case 'Validação':	$prazo_horas = $horas_teste;			break;
			case 'Efetivação':	$prazo_horas = $horas_efetivacao;		break;
			default:			$prazo_horas = NULL;					break;
		}
		$prazo_horas	= $this->floatToTime($prazo_horas, 2);

		/* para classes do layout */
		$classMine = ($atendente == $login_admin) ? '-mine' : '';
		if (in_array($status,array('Impedimento','Parado')) AND ($atendente == $login_admin)) $classMine = '-mine-return';

		$corPostIt 								= 'branco';
		//if($status == '')			 $corPostIt = 'verde';

		//verifica se no status execução o desenvolvedor passou do prazo estimado pelo analista
               $horas_desenvolvidas = $this->__getHorasDesenvolvidas($atendente, $hd_chamado);
               $atrasado = false;
               $corAtrasado = '#000000';
	       //se estiver atrasado
	       $data_previsao=strtotime($previsao_termino_interna2);
	       $data_agora= strtotime(date('Y-m-d H:i'));
	       if( ($status=="Execução" || $status=="Aguard.Execução" || $status =='Correção')  and !empty($previsao_termino_interna) and $data_previsao < $data_agora) {
		       $corPostIt	= 'rosa';
                       $atrasado = true;
                       //coloca tom de vermelho
                       $corAtrasado = '#e60000';
               }

		$classAtrasado = ($atrasou == 1) ? 'atrasado' : '';
		/* para classes do layout */

		$html  = '					<div class="card' . $classMine . '">'.DS;
		$html .= '						<input type="hidden" name="hd_chamado" id="hd_chamado" value="' . $hd_chamado . '">'.DS;
		$html .= '						<input type="hidden" name="altera_status" id="altera_status" value="' . $altera_status . '">'.DS;
		$html .= '						<img src="imagens_painel/post-it-' . $corPostIt . '.png" class="card-image">'.DS;
		$html .= '						<div class="content">'.DS;
		$html .= '							<h4 title="'.$titulo.'"><span class="num" id="num">' . $this->exibeNumHD($hd_chamado, $tipo_chamado) . ' - '. $titulo_tratado . '</span></h4>'.DS;
		$html .= '							<div>'.DS;
		$html .= '								<p class="top">' . $atendente_nome . '</p>'.DS;
		$html .= '								<p class="top">' . $fabrica_nome . '</p>'.DS;
		$html .= '								<div class="gravatar"><img src="' . $img . '" width="26" height="26"></div>'.DS;
		$html .= '							</div>'.DS;

		$html .= '							<table class="tblData" cellpadding="0" cellspacing="0" border="0">'.DS;
		$html .= '								<tr>'.DS;
		$html .= '									<td><h6>Prévisão</h6></td>'.DS;
		$html .= '									<td><h6>Estimado</h6></td>'.DS;
		$html .= '								</tr>'.DS;
		$html .= '								<tr>'.DS;
		$html .= '									<td><p><br>' . $previsao_termino_interna . '</p></td>'.DS;
		$html .= '									<td><p><br>' . $prazo_horas . '</p></td>'.DS;
		$html .= '								</tr>'.DS;
		$html .= '							</table>'.DS;
		$html .= '							<table class="tblData" cellpadding="0" cellspacing="0" border="0">'.DS;
		$html .= '								<tr>'.DS;
		$html .= '									<td><h6></h6></td>'.DS;
		$html .= '								</tr>'.DS;
		$html .= '								<tr>'.DS;
		$html .= '									<td><p><br/>' . $this->statusCor($status) . '</p></td>'.DS;
		$html .= '								</tr>'.DS;
		$html .= '							</table>'.DS;
		$html .= '						</div>'.DS;
		$html .= '					</div>'.DS;

		return $html;
	}


	/* Início de SQL, utilizada em todas as queries */

	/**
	 * (1) Chamados na fila de espera para entrarem os requisitos
	 * Status = 'Requisitos' | Atendente = 'Suporte' | Grupo = '6' (Suporte)
	 */
	function dadosColuna1($atendente=null) {
		$listaStatus		= array('Requisitos','Aguard.Admin');
		$listaAtendente		= array(435);
		$listaGrupoAdmin	= array(6);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = "
				AND
					(
						tbl_hd_chamado.atendente IN (".implode(",",$listaAtendente).")
					OR
						tbl_hd_chamado.atendente = {$atendente}
					) ";

			$limit			= " LIMIT 1";
		}
		else {
			$whereAtendente = "
				 AND
					(
						tbl_hd_chamado.atendente IN (".implode(",",$listaAtendente).")
					OR
						tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).")
					) ";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				"LEFT JOIN
					tbl_hd_chamado_requisito ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_requisito.hd_chamado " .
				$this->WHERE  .
				"AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')

				{$whereAtendente}

				 AND
					tbl_hd_chamado.hd_chamado NOT IN (".$this->arrayImplodeChamadoEmExecucaoValues.")
				 AND
					tbl_hd_chamado_requisito.hd_chamado_requisito IS NULL
				 AND
					tbl_hd_chamado.categoria IS NOT NULL
				 AND
					tbl_hd_chamado.categoria <> '' " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);

		return pg_fetch_all($res);
	}

	/**
	 * (2) Desenvolvendo os requisitos
	 * Status = 'Requisitos' e Atendente = Atendente designado
	 */
	function dadosColuna2($atendente=null) {
		$listaStatus		= array('Requisitos', 'Impedimento', 'Parado','Aguard.Admin');
		$listaAtendente		= array(435); // * not in
		$listaGrupoAdmin	= array(6);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = " AND (
									tbl_hd_chamado.atendente = {$atendente}
									OR
									tbl_hd_chamado.atendente IN (".implode(",",$listaAtendente).")
								) ";
			$limit			= " LIMIT 1";
		}
		else {
			$whereAtendente = " AND tbl_hd_chamado.atendente NOT IN (".implode(",",$listaAtendente).") ";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				"LEFT JOIN
					tbl_hd_chamado_requisito ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_requisito.hd_chamado " .
				$this->WHERE  .
				"AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					tbl_hd_chamado.hd_chamado IN (".$this->arrayImplodeChamadoEmExecucaoValues.")

				{$whereAtendente}

				 AND
					tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).")
				 AND
					tbl_hd_chamado_requisito.hd_chamado_requisito IS NULL " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);

		return pg_fetch_all($res);
	}

	/**
	 * (3) Requisitos - Aprovação
	 * Status = 'Requisitos' e Atendente = Atendente designado
	 */
	function dadosColuna3($atendente=null) {
		$listaStatus		= array('Requisitos');
		$listaAtendente		= array(435);
		$listaGrupoAdmin	= array(6);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = " AND tbl_hd_chamado.atendente = {$atendente} ";
			$limit			= " LIMIT 1";
		}
		else {
			$whereAtendente = " AND
					(
					tbl_hd_chamado.atendente IN (".implode(",",$listaAtendente).")
				 OR
					tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).")
					) ";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				"JOIN
					tbl_hd_chamado_requisito ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_requisito.hd_chamado " .
				$this->WHERE  .
				"AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					tbl_hd_chamado.hd_chamado NOT IN (".$this->arrayImplodeChamadoEmExecucaoValues.")

				{$whereAtendente}

				 AND
					tbl_hd_chamado_requisito.admin_requisito_aprova IS NULL	" .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);

		return pg_fetch_all($res);
	}

	/**
	 * (4) Requisitos - Finalizado
	 * Status = 'Orçamento' e Atendente = Suporte
	 */
	function dadosColuna4($atendente=null) {
		$listaStatus		= array('Orçamento');
		$listaAtendente		= array(435);
		$listaGrupoAdmin	= array(6,1,2);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = " AND tbl_hd_chamado.atendente = {$atendente} ";
			$limit			= " LIMIT 1";
		}
		else {
			$whereAtendente = " AND
					(
					tbl_hd_chamado.atendente IN (".implode(",",$listaAtendente).")
				 OR
					tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).")
					) ";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				"JOIN
					tbl_hd_chamado_requisito ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_requisito.hd_chamado " .
				$this->WHERE  .
				"AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					tbl_hd_chamado.hd_chamado NOT IN (".$this->arrayImplodeChamadoEmExecucaoValues.")

				{$whereAtendente}

				 AND
					tbl_hd_chamado_requisito.admin_requisito_aprova IS NOT NULL
				 AND
					tbl_hd_chamado.hora_desenvolvimento IS NULL " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);

		return pg_fetch_all($res);
	}

	/**
	 * (5) Análise - Orçamento
	 * Status = 'Orçamento', sem Orçamento e Atendente = Analista
	 */
	function dadosColuna5($atendente=null) {
		$listaStatus		= array('Orçamento', 'Impedimento', 'Parado');
		$listaGrupoAdmin	= array(1,2);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = " AND tbl_hd_chamado.atendente = {$atendente} ";
			$limit			= " LIMIT 1";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				"JOIN
					tbl_hd_chamado_requisito ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_requisito.hd_chamado " .
				$this->WHERE  .
				"AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					tbl_hd_chamado.hd_chamado IN (".$this->arrayImplodeChamadoEmExecucaoValues.")
				 AND
					tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).")

				{$whereAtendente}

				 AND
					tbl_hd_chamado_requisito.admin_requisito_aprova IS NOT NULL
				 AND
					tbl_hd_chamado.hora_desenvolvimento IS NULL " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);

		return pg_fetch_all($res);
	}

	/**
	 * (6) Análise - Aprovação
	 * Status = 'Orçamento', sem Orçamento e Atendente = Analista
	 */
	function dadosColuna6($atendente=null) {
		$listaStatus		= array('Orçamento','Análise');
		$listaGrupoAdmin	= array(1,2,7);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = " AND tbl_hd_chamado.atendente = {$atendente} ";
			$andExtra	=" AND ( tbl_hd_chamado.data_aprovacao NOTNULL OR tbl_hd_chamado.tipo_chamado = 5 or (tbl_hd_chamado.data_aprovacao ISNULL AND tbl_hd_chamado.fabrica = 10) ) ";
			$limit			= " LIMIT 1";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				"LEFT JOIN
					tbl_hd_chamado_requisito ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_requisito.hd_chamado " .
				$this->WHERE  .
				"AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					(tbl_hd_chamado.hd_chamado NOT IN (".$this->arrayImplodeChamadoEmExecucaoValues.") )
				 AND
					(tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).") or tbl_admin.admin = 3011)

				{$whereAtendente}
				{$andExtra}
				 AND
					(
						(
							tbl_hd_chamado.tipo_chamado = 5
						AND
						tbl_hd_chamado_requisito.admin_requisito_aprova IS NULL
						AND tbl_admin.admin =1630

						)
					OR
						(
							tbl_hd_chamado_requisito.admin_requisito_aprova IS NOT NULL
						AND
							tbl_hd_chamado.hora_desenvolvimento IS NOT NULL
						)
					or (tbl_hd_chamado.data_aprovacao ISNULL AND tbl_hd_chamado.fabrica = 10)
					)
				 AND
					tbl_hd_chamado.analise IS NULL " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);
		return pg_fetch_all($res);
	}

	/**
	 * (7) Análise - Desenvolvimento
	 * Status = 'Análise', sem Análise e Atendente = Analista
	 */
	function dadosColuna7($atendente=null) {
		$listaStatus		= array('Análise', 'Impedimento', 'Parado');
		$listaGrupoAdmin	= array(1,2,7);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = " AND tbl_hd_chamado.atendente = {$atendente} ";
			$andExtra	=" AND ( tbl_hd_chamado.tipo_chamado = 5 or (tbl_hd_chamado.data_aprovacao ISNULL AND tbl_hd_chamado.fabrica = 10) ) ";
			$limit			= " LIMIT 1";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				$this->WHERE  .
				"AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					tbl_hd_chamado.hd_chamado IN (".$this->arrayImplodeChamadoEmExecucaoValues.")
				 AND
					tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).")

				{$whereAtendente}
				{$andExtra}
				 AND
					tbl_hd_chamado.analise IS NULL " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);

		return pg_fetch_all($res);
	}

	/**
	 * (8) Análise - Finalizado
	 * Status = 'Análise', com Análise e Atendente = Analista
	 */
	function dadosColuna8($atendente=null) {
		$listaStatus		= array('Análise', 'Aguard.Execução');
		$listaGrupoAdmin	= array(1,2,7);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = " AND tbl_hd_chamado.atendente = {$atendente} ";
			$limit			= " LIMIT 1";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				$this->WHERE  .
				"AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					tbl_hd_chamado.hd_chamado NOT IN (".$this->arrayImplodeChamadoEmExecucaoValues.")
				 AND
					tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).")

				{$whereAtendente}

				 AND
					tbl_hd_chamado.analise IS NOT NULL " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);

		return pg_fetch_all($res);
	}

	/**
	 * (9) Execução - A fazer
	 * Status = 'Aguard.Execução', com Desenv. em tbl_backlog_item
	 */
	function dadosColuna9($atendente=null) {
		$listaStatus		= array('Aguard.Execução','Execução','Correção');
		$listaGrupoAdmin	= array(4);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = " AND (tbl_hd_chamado.atendente = {$atendente} or tbl_backlog_item.desenvolvedor = {$atendente})";
			$limit			= " LIMIT 1";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				$this->WHERE  .
				"AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					tbl_hd_chamado.hd_chamado NOT IN (".$this->arrayImplodeChamadoEmExecucaoValues.")
				 AND
					tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).")

				{$whereAtendente}

				AND
					tbl_backlog_item.desenvolvedor IS NOT NULL " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);

		return pg_fetch_all($res);
	}

	/**
	 * (10) Execução - Desenvolvimento
	 * Status = 'Execução', com Desenv. em tbl_backlog_item
	 */
	function dadosColuna10($atendente=null) {
		$listaStatus		= array('Aguard.Execução', 'Execução', 'Impedimento', 'Parado', 'Correção');
		$listaGrupoAdmin	= array(4);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = " AND (tbl_hd_chamado.atendente = {$atendente} or tbl_backlog_item.desenvolvedor = {$atendente})";
			$limit			= " LIMIT 1";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				$this->WHERE  .
				"AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					tbl_hd_chamado.hd_chamado IN (".$this->arrayImplodeChamadoEmExecucaoValues.")
				 AND
					tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).")

				{$whereAtendente}

				 AND
					tbl_backlog_item.desenvolvedor IS NOT NULL " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);
		return pg_fetch_all($res);
	}

	/**
	 * (11) Execução - Finalizado
	 * Status = 'Validação', com Atendente do Suporte
	 */
	function dadosColuna11($atendente=null) {
		$listaStatus		= array('Validação','Efetivação','Aguard.Verifica');
		$listaGrupoAdmin	= array(6);

		$limit = "";

		$whereAtendente = null;
		if (!empty($atendente)) {
			$whereAtendente = " AND tbl_hd_chamado.atendente = {$atendente} ";
			$limit			= " LIMIT 1";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				$this->WHERE  .
				"AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					tbl_hd_chamado.hd_chamado NOT IN (".$this->arrayImplodeChamadoEmExecucaoValues.")
				 AND
					tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).")

				{$whereAtendente}

				 AND
					tbl_backlog_item.desenvolvedor IS NOT NULL " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);

		return pg_fetch_all($res);
	}

	/**
	 * (12) Teste - Desenvolvimento
	 * Status = 'Validação', com Atendente Suporte
	 */
	function dadosColuna12($atendente=null) {
		$listaStatus		= array('Validação', 'Teste', 'Impedimento', 'Parado');
		$listaGrupoAdmin	= array(6);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = " AND tbl_hd_chamado.atendente = {$atendente} ";
			$limit			= " LIMIT 1";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				$this->WHERE  .
				"AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					tbl_hd_chamado.hd_chamado IN (".$this->arrayImplodeChamadoEmExecucaoValues.")

				{$whereAtendente}

				 AND
					tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).") " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);

		return pg_fetch_all($res);
	}

	/**
	 * (13) Teste - Finalizado
	 * Status = 'Commit', com Desenv. em tbl_backlog_item
	 */
	function dadosColuna13($atendente=null) {
		$listaStatus		= array('Commit','Efetivação');
		$listaGrupoAdmin	= array(4);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = " AND tbl_hd_chamado.atendente = {$atendente} ";
			$limit			= " LIMIT 1";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				$this->WHERE  .
				"AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					tbl_hd_chamado.hd_chamado NOT IN (".$this->arrayImplodeChamadoEmExecucaoValues.")

				{$whereAtendente}

				 AND
					tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).") " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);

		return pg_fetch_all($res);
	}

	/**
	 * (14) Commit - Desenvolvimento
	 * Status = 'Commit', com Desenvolvedor
	 */
	function dadosColuna14($atendente=null) {
		$listaStatus		= array('Commit','Efetivação');
		$listaGrupoAdmin	= array(4,7);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = " AND tbl_hd_chamado.atendente = {$atendente} ";
			$limit			= " LIMIT 1";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				$this->WHERE  .
				" AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					tbl_hd_chamado.hd_chamado IN (".$this->arrayImplodeChamadoEmExecucaoValues.")

				{$whereAtendente}

				 AND
					tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).") " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);

		return pg_fetch_all($res);
	}

	/**
	 * (15) Deploy - Desenvolvimento
	 * Status = 'Efetivação', com Infra/DBA
	 */
	function dadosColuna15($atendente=null) {
		$listaStatus		= array('Efetivação', 'Impedimento', 'Parado');
		$listaGrupoAdmin	= array(7);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = " AND tbl_hd_chamado.atendente = {$atendente} ";
			$limit			= " LIMIT 10";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				$this->WHERE  .
				" AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					tbl_hd_chamado.hd_chamado NOT IN (".$this->arrayImplodeChamadoEmExecucaoValues.")

				{$whereAtendente}

				 AND
					tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).") " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);

		return pg_fetch_all($res);
	}

	/**
	 * (16) Deploy finalizado
	 * Status = 'Efetivação'
	 */
	function dadosColuna16($atendente=null) {
		$listaStatus		= array('Efetivação');
		$listaGrupoAdmin	= array(4);

		$limit = "";

		if (!empty($atendente)) {
			$whereAtendente = " AND tbl_hd_chamado.atendente = {$atendente} ";
			$limit			= " LIMIT 1";
		}

		$sql =  $this->SELECT .
				$this->FROM   .
				$this->JOIN   .
				$this->WHERE  .
				"AND
					tbl_hd_chamado.status IN ('".implode("','",$listaStatus)."')
				 AND
					tbl_hd_chamado.hd_chamado IN (".$this->arrayImplodeChamadoEmExecucaoValues.")

				{$whereAtendente}

				 AND
					tbl_admin.grupo_admin IN (".implode(",",$listaGrupoAdmin).") " .
				$this->ORDER_BY .
				$limit;
		$res = pg_query($this->con, $sql);

		return pg_fetch_all($res);
	}

	/**
	 * Atrela chamado da fila ao atendente
	 * @param integer $login_admin
	 * @param integer $hd_chamado
	 * @param integer $atendente_anterior
	 * @return boolean
	 */
	function setChamadoAtendente($hd_chamado = NULL, $status = '', $atendente_anterior = '') {
		global $login_admin, $grupo_admin;

		if (empty($atendente_anterior)) {
			$atendente_anterior = $login_admin;
		}

		if ($hd_chamado) {
			if (!empty($status)) {
				// seta status Impedimento ou Parado
				$coluna = $this->arrayChamadoColuna[$login_admin][$hd_chamado];
				$this->gravaJson($login_admin,$hd_chamado,$coluna,$status);
			}
			else {
				// retira o chamado da fila do atendente
				$this->excluiJson($atendente_anterior, $hd_chamado);
			}
		}

		$setChamado = $this->__chamadoAguardandoNoJson($hd_chamado);

		if(!$setChamado) {
			$colunas = $this->grupoAdminColunas[$grupo_admin];

			foreach($colunas AS $key=>$value) {
				$nomeFuncao = "dadosColuna{$key}";

				$registro = $this->$nomeFuncao($login_admin);

				if (is_array($registro)) {
					$hd_chamado = $registro[0]['hd_chamado'];
					$coluna 	= $value;
					$status 	= '';

					if($key == 1) {
						$justificativa 	= "Chamado Transferido automaticamente - Kanban";
						$status			= "";

						$retorno = $this->__updateAtendenteHdChamado($hd_chamado, $login_admin);

						if ($retorno) {
							$retorno = $this->__insereHdChamadoItem($hd_chamado, $justificativa, $status, $login_admin);
						}
					}

					$this->gravaJson($login_admin,$hd_chamado,$coluna,$status);
					break;

				}
			}
		}
		else {
			$this->gravaJson($login_admin, $setChamado['hd_chamado'], $setChamado['coluna']);
		}

		return true;
	}

	/**
	 * Atrela chamado a todos os atendentes livres
	 * @return boolean
	 */
	private function __setChamadoTodosAtendentes() {
		global $login_admin;

		$grupos_admin				  = array(1,2,4,6,7);
		$atendentesNaoRecebemChamados = array(57, 435, 1819, 1961, 2363);
		$desconsiderarAtendentes 	  = array_unique(array_merge($atendentesNaoRecebemChamados, $this->__atendentesComChamados(), $this->__atendentesNaoTrabalhando() ));

		$justificativa 	= "Chamado transferido automaticamente - Kanban";
		$status			= "Requisitos";

		$sql = "SELECT
					admin,
					grupo_admin
				FROM
					tbl_admin
				WHERE
					ativo IS TRUE
				AND
					fabrica = 10
				AND
					grupo_admin IN (".implode(',',$grupos_admin).")
				AND
					admin  NOT  IN (".implode(',',$desconsiderarAtendentes).") ";
		$res = pg_query($this->con,$sql);

		$total_registros = pg_num_rows($res);

		for($i=0; $i < $total_registros; $i++) {
			$admin       = pg_result($res,$i,'admin');
			$grupo_admin = pg_result($res,$i,'grupo_admin');

			$colunas = $this->grupoAdminColunas[$grupo_admin];

			foreach($colunas AS $key=>$value) {

				$nomeFuncao = "dadosColuna{$key}";

				$registro = $this->$nomeFuncao($admin);

				if (is_array($registro)) {
					$hd_chamado = $registro[0]['hd_chamado'];
					$coluna 	= $value;
					$status 	= '';

					$retorno = $this->__updateAtendenteHdChamado($hd_chamado, $admin);

					if ($retorno) {
						$retorno = $this->__insereHdChamadoItem($hd_chamado, $justificativa, $status, $admin);

						if($retorno) {
							$this->gravaJson($admin,$hd_chamado,$coluna,$status);
							break;
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * Retorna atendentes com chamados status = "" (desconsidera Parado e Impedimento)
	 * @return array
	 */
	private function __atendentesComChamados() {
		$arrayRetorno = array();
		foreach($this->arrayChamadoStatus AS $keyAtendente=>$valueAtendente) {
			foreach($valueAtendente AS $keyChamado=>$valueChamado) {
				if($valueChamado == "") {
					$arrayRetorno[] = $keyAtendente;
				}
			}
		}
		return $arrayRetorno;
	}

	/**
	 * Retorna atendentes que não estão trabalhando (tbl_admin_online)
	 * @return array
	 */
	private function __atendentesNaoTrabalhando() {

		$sql = "SELECT
					tbl_admin.admin
				FROM
					tbl_admin
				WHERE
					tbl_admin.ativo IS TRUE
					AND tbl_admin.fabrica = 10
					AND tbl_admin.admin NOT IN (SELECT tbl_admin.admin FROM tbl_hd_chamado_atendente join tbl_admin using(admin) WHERE fabrica = 10 and data_termino isnull)
					AND fabrica = 10;";
		$res = pg_query($this->con,$sql);

		$arrayRetorno = array();
		if(pg_num_rows($res)){
			for ($i=0; $i < pg_num_rows($res); $i++) {
				$arrayRetorno[] = pg_fetch_result($res, $i, 'admin');
			}
		}

		return $arrayRetorno;
	}

	/**
	 * Verifica se atendente possui chamado disponível no json
	 * Pode ser chamado que está Parado ou que retornou do Impedimento (passou a ser "")
	 * Ao passar o $hd_chamado, ele não será incluído na comparação
	 * @param int $hd_chamado
	 * @return mixed
	 */
	private function __chamadoAguardandoNoJson($hd_chamado=null) {
		global $login_admin;

		$justificativa  = 'Sistema voltou automaticamente chamado ao status anterior';
		$status         = '';

		$chamados = $this->arrayChamadoStatus[$login_admin];

		if(is_array($chamados)) {
			foreach($chamados as $key=>$value) {
				if($value == "") {
					$arrayChamado['hd_chamado'] = $key;
					$arrayChamado['coluna']     = $this->arrayChamadoColuna[$login_admin][$key];
					return $arrayChamado;
				}
			}

			foreach($chamados as $key=>$value) {
				if($value == "Parado" AND $hd_chamado <> $key) {
					$status = $this->__selecionaStatusAnteriorItem($key);

					$retorno = $this->__updateHdChamado($key, $status, $login_admin);

					if ($retorno) {
						$retorno = $this->__insereHdChamadoItem($key, $justificativa, $status, $login_admin);
					}

					$arrayChamado['hd_chamado'] = $key;
					$arrayChamado['coluna']     = $this->arrayChamadoColuna[$login_admin][$key];

					return $arrayChamado;
				}
			}
		}

		return false;
	}

	/**
	 * Relaciona o grupo às suas colunas
	 * array(colunaFileAguardando => colunaEmDesenvolvimento)
	 */
	private function __setGrupoAdminRegrasColunas() {
		$this->grupoAdminColunas[1] = array(6=>7, 4=>5);
		$this->grupoAdminColunas[2] = array(6=>7, 4=>5);
		$this->grupoAdminColunas[4] = array(15=>16, 13=>14, 9=>10);
		$this->grupoAdminColunas[6] = array(11=>12, 1=>2);
		$this->grupoAdminColunas[7] = array(15=>15);
	}

	/**
	 * Converte valores float em horas
	 * @param float $float
	 * @param integer $mascara
	 * @return string $dia_hora_formatada
	 * Ex.:
	 * 		float	mascara	gera
	 *		8.5		1		08:30
	 *		8.5		2		8h 30min
	 *		5		1		05:00
	 *		5		2		5h
	 *		23.5	1		23:30
	 *		23.5	2		2d 7h 30min
	 */
	public function floatToTime($float, $mascara = 1) {
		$inteiro 		 = (int)$float;
		$decimal		 = $float - $inteiro;	// valor subtraindo seu valor inteiro = resta o decimal, se houver
		$decimal_minutos = $decimal * 60;		// converte decimal em minutos

		$formata_dias = '';

		switch ($mascara) {
			default:
			case 1:
				$formato 		= "H:i";
				$retorno 		= $inteiro.':'.sprintf('%02d', $decimal_minutos);
			break;
			case 2:
				$dias 		  	= $this->__diasTrabalho($inteiro);
				if ($inteiro >= $this->horasPorDia) {
					$inteiro	= $inteiro - ($dias * $this->horasPorDia);
				}
				$formato 		= $this->__formatoMascara($inteiro, $decimal_minutos);
				$formata_dias 	= ($dias) ? $dias . ' ' : '';
				$retorno 		= $formata_dias . date($formato, mktime($inteiro,$decimal_minutos,0));
			break;
		}

		return $retorno;
	}

	/**
	 * Retorna quantidade de dias de trabalho de acordo com qtd de horas
	 * A regra é que devem ser 8h/dia de trabalho
	 * @param integer $horas
	 * @return integer $dias
	 */
	private function __diasTrabalho($horas) {
		$dias = intval($horas / $this->horasPorDia);
		$retorno = '';
		if($dias > 0) {
			$complemento = ($dias == 1) ? 'd' : 'd';
			$retorno = $dias . $complemento;
		}

		return $retorno;
	}

	/**
	 * Retorna a máscara da data
	 * @param integer $horas
	 * @param integer $minutos
	 * @return string $mascara
	 */
	private function __formatoMascara($horas=0, $minutos=0) {
		$mascara = '';
		if ($horas > 0)   $mascara .= $this->mascaraHora;
		if ($minutos > 0) $mascara .= $this->mascaraMinuto;

		return $mascara;
	}

	public function statusCor($status){
		if($status == 'Correção'){
			$status = '<span class="alerta">'.$status.'</span>';
		}
		return $status;
	}
	/**
	 * Verifica se o admin possui foto.
	 * Guarda em sessão
	 * Retorna o caminho da imagem
	 * @param integer $atendente
	 * @return string $caminho
	 */
	public function retornaFoto($atendente) {
		$caminhoFotosAdmin  = '../admin/admin_fotos'; // sem a barra no final
		$caminhoFotosPainel = 'imagens_painel'; // sem a barra no final

		if (!isset($_SESSION['cacheFoto'][$atendente])) {
			if (file_exists("{$caminhoFotosAdmin}/tbl_admin.{$atendente}.jpg")) {
				$_SESSION['cacheFoto'][$atendente] = "{$caminhoFotosAdmin}/tbl_admin.{$atendente}.jpg";
			}
			else {
				$_SESSION['cacheFoto'][$atendente] = "{$caminhoFotosPainel}/NoPicture.gif";
			}
		}

		return $_SESSION['cacheFoto'][$atendente];
	}

	/**
	 * Destaca o nº do chamado
	 * Retorna o html formatado
	 * @param integer $hd_chamado
	 * @param integer $tipo_chamado
	 * @return string $html
	 */
	public function exibeNumHD($hd_chamado, $tipo_chamado) {
		$tipo_chamado_erro = 5;

		if($tipo_chamado == $tipo_chamado_erro) {
			$hd_chamado = '<span class="alerta">'.$hd_chamado.'</span>';
		}
		return $hd_chamado;
	}

	/**
	 * Altera o status do hd_chamado
	 * @param array $array
	 * @param integer $login_admin
	 * @return boolean
	 */
	public function alteraStatus($array, $login_admin) {
		$hd_chamado		= trim($array['hd_chamado']);
		$status 		= trim($array['status']);
		$justificativa  = trim($array['justificativa']);

		if ($hd_chamado AND $status AND $justificativa AND $login_admin AND is_resource($this->con)) {
			// processo de alteração de status
			$res = pg_query($this->con,"BEGIN");

			$retorno = $this->__updateHdChamado($hd_chamado, $status, $login_admin);

			if ($retorno) {
				$retorno = $this->__insereHdChamadoItem($hd_chamado, $justificativa, $status, $login_admin);

				if($retorno) {
					$res = pg_query($this->con,"COMMIT");

					$this->setChamadoAtendente($hd_chamado, $status);

					return true;
				}
			}
			$res = pg_query($this->con,"ROLLBACK");
		}

		return false;
	}

	/**
	 * Altera o status do hd_chamado
	 * @param array $array
	 * @param integer $login_admin
	 * @return boolean
	 */
	public function voltaStatus($hd_chamado, $login_admin) {
		$hd_chamado		= trim($hd_chamado);
		$justificativa  = 'Usuário voltou chamado ao status anterior';

		if ($hd_chamado AND $login_admin AND is_resource($this->con)) {

			$status_anterior = $this->__selecionaStatusAnteriorItem($hd_chamado);

			if ($status_anterior) {
				// processo de alteração de status
				$res = pg_query($this->con,"BEGIN");

				$retorno = $this->__updateHdChamado($hd_chamado, $status_anterior, $login_admin);

				if ($retorno) {
					$retorno = $this->__insereHdChamadoItem($hd_chamado, $justificativa, $status_anterior, $login_admin);

					if($retorno) {
						$res = pg_query($this->con,"COMMIT");

						$this->setChamadoAtendente();

						return true;
					}
				}
				$res = pg_query($this->con,"ROLLBACK");
			}
		}

		return false;
	}


	/**
	 * Atualiza STATUS do hd_chamado
	 * @param integer $hd_chamado
	 * @param string $status
	 * @param integer $login_admin
	 * @returno boolean
	 */
	private function __updateHdChamado($hd_chamado, $status, $login_admin) {
		$sql = "UPDATE
					tbl_hd_chamado
				SET
					status = '{$status}'
				WHERE
					hd_chamado = {$hd_chamado}
				AND
					atendente  = {$login_admin};";
		$res = pg_query($this->con,$sql);

		if ($res)
			return true;

		return false;
	}

	/**
	 * Atualiza ATENDENTE (de suporte para atendente requisitos) do hd_chamado
	 * @param integer $hd_chamado
	 * @param integer $login_admin
	 * @returno boolean
	 */
	private function __updateAtendenteHdChamado($hd_chamado, $login_admin) {
		$sql = "UPDATE
					tbl_hd_chamado
				SET
					atendente  = {$login_admin}
				WHERE
					hd_chamado = {$hd_chamado}
				AND
					atendente  = 435;";
		$res = pg_query($this->con,$sql);

		if ($res)
			return true;

		return false;
	}


	/**
	 * Grava novo hd_chamado_item
	 * @param integer $hd_chamado
	 * @param string $justificativa
	 * @param string $status
	 * @param integer $login_admin
	 * @returno boolean
	 */
	private function __insereHdChamadoItem($hd_chamado, $justificativa, $status, $login_admin) {
		$sql = "INSERT INTO tbl_hd_chamado_item (
					hd_chamado,
					comentario,
					interno,
					admin,
					status_item
				) VALUES (
					{$hd_chamado},
					'{$justificativa}',
					't',
					{$login_admin},
					'{$status}'
				);";
		$res = pg_query($this->con,$sql);

		if ($res)
			return true;

		return false;
	}

	/**
	 * Seleciona Status Anterior em hd_chamado_item
	 * @param integer $hd_chamado
	 * @return mixed
	 */
	private function __selecionaStatusAnteriorItem($hd_chamado) {
		$sql = "SELECT
					status_item
				FROM
					tbl_hd_chamado_item
				WHERE
					hd_chamado = {$hd_chamado}
				AND
					status_item NOT IN ('Impedimento', 'Parado')
				AND
					status_item IS NOT NULL
				AND
					status_item <> ''
				ORDER BY
					hd_chamado_item DESC
				LIMIT 1;";
		$res = pg_query($this->con,$sql);

		$status_anterior = pg_fetch_result($res,0,'status_item');

		if ($res)
			return $status_anterior;

		return false;
	}

	/**
	 * Trata o conteúdo do titulo
	 * @param string $titulo
	 * @return string $titulo_tratado
	 */
	private function __trataTitulo($titulo) {
		$titulo_tratado	= trim($titulo);
		$titulo_tratado	= substr($titulo_tratado, 0, 18);
		$titulo_tratado	= change_case($titulo_tratado, 'lower');
		$titulo_tratado	= ucfirst($titulo_tratado);
		$titulo_tratado	= stripslashes($titulo_tratado);

		return $titulo_tratado;
	}

	/**
	 * Grava Json em arquivo
	 * @param integer $atendente
	 * @param integer $hd_chamado
	 * @param integer $coluna
	 * @param string $status
	 * @return boolean
	 */
	public function gravaJson($atendente, $hd_chamado, $coluna=null, $status='') {
		if ($atendente AND $hd_chamado) {
			$jsonDecode = $this->leJson();

			$dados = array('coluna'=>$coluna,'status'=>$status);
			$alteraItemArray = array($dados);

			if ($this->__atendenteEstaJson($atendente) AND $this->__chamadoEstaJson($atendente,$hd_chamado)) {
				// altera
				$jsonDecode[$atendente][$hd_chamado][0]['coluna'] = $coluna;
				$jsonDecode[$atendente][$hd_chamado][0]['status'] = $status;
			}
			else {
				// inclui
				$jsonDecode[$atendente][$hd_chamado] = $alteraItemArray;
			}

			$newArray = json_encode($jsonDecode);
			file_put_contents($this->json_file, $newArray);

			$this->__setChamadoEmExecucao();
			$this->__setImplodeChamadoEmExecucaoValues();
		}
		return true;
	}

	/**
	 * Retira chamado do Json
	 * @param integer $atendente
	 * @param integer $hd_chamado
	 * @return none
	 */
	public function excluiJson($atendente, $hd_chamado) {
		if ($atendente AND $hd_chamado) {
			$jsonDecode = $this->leJson();

			if ($this->__atendenteEstaJson($atendente) AND $this->__chamadoEstaJson($atendente,$hd_chamado) AND is_array($jsonDecode[$atendente][$hd_chamado])) {
				unset($jsonDecode[$atendente][$hd_chamado]);

				$newArray = json_encode($jsonDecode);
				file_put_contents($this->json_file, $newArray);

				$this->__setChamadoEmExecucao();
				$this->__setImplodeChamadoEmExecucaoValues();
			}
		}
	}

	/**
	 * Lê Json em arquivo
	 * @return string
	 */
	public function leJson() {
		$conteudo = file_get_contents($this->json_file);
		return json_decode($conteudo, true);
	}

	/**
	 * Verifica se o atendente está no arquivo de JSON
	 * @return boolean
	 */
	private function __atendenteEstaJson($atendente) {
		if (in_array($atendente, $this->arrayChamadoEmExecucaoKeys))
			return true;

		return false;
	}

	/**
	 * Verifica se o chamado está no arquivo de JSON
	 * @return boolean
	 */
	private function __chamadoEstaJson($atendente, $hd_chamado) {
		if (in_array($hd_chamado, $this->arrayChamadoEmExecucaoValues))
			return true;

		return false;
	}


	private function __getHorasDesenvolvidas($admin, $hd_chamado){
                       $sqlH = "SELECT
                                       EXTRACT(EPOCH FROM SUM( CASE WHEN data_termino is null THEN CURRENT_TIMESTAMP ELSE data_termino END - data_inicio ))/3600 AS horas_chamado
                               FROM
                                       tbl_backlog_item
                               JOIN
                                       tbl_hd_chamado
                                       ON
                                               tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado
                               LEFT JOIN
                                       tbl_hd_chamado_atendente
                                       ON
                                               tbl_hd_chamado_atendente.hd_chamado = tbl_backlog_item.hd_chamado
                               JOIN
                                       tbl_fabrica
                                       ON
                                               tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
                               WHERE
                                       tbl_hd_chamado.status in ('Execução', 'Aguard.Execução')
                                       AND
                                               tbl_hd_chamado_atendente.admin = $admin
                                       AND
                                               tbl_hd_chamado_atendente.hd_chamado = $hd_chamado
                               GROUP BY
                                       tbl_backlog_item.backlog_item, tbl_hd_chamado_atendente.hd_chamado
                               ORDER BY
                                       tbl_hd_chamado_atendente.hd_chamado";
               // echo $sqlH;
               $resH = pg_query($this->con, $sqlH);
               while($dados=pg_fetch_object($resH)){
                       $horas_chamado=$dados->horas_chamado;
               }

               return $horas_chamado;
       }

}
?>
