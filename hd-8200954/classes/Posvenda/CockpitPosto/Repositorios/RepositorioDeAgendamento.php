<?php

namespace Posvenda\CockpitPosto\Repositorios;

use \PDO;
use \Ticket\Ticket;

class RepositorioDeAgendamento
{
	private $pdo;

	public function __construct(array $pdoInfo)
	{
		if ($this->pdo == null) {
			$this->pdo = new PDO(
				'pgsql:dbname=' . $pdoInfo['dbname'] . '; host=' . $pdoInfo['dbhost'] . '; port=' . $pdoInfo['dbport'],
				$pdoInfo['dbuser'], 
				$pdoInfo['dbpassword']
			);
		}
	}
	public function pendenteExportacao(int $fabricaId, int $postoId){
		/*$sql = "SELECT tbl_os_campo_extra.campos_adicionais::JSON->>'sincronizado' as sincronizado, tbl_os.os, tbl_tecnico.nome as nome_tecnico, tbl_tecnico_agenda.hora_inicio_trabalho, tbl_tecnico_agenda.hora_fim_trabalho, tbl_tecnico_agenda.tecnico_agenda  
			from tbl_tecnico_agenda 
			join tbl_tecnico on tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico 
			join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_tecnico_agenda.os 
			join tbl_os on tbl_os.os = tbl_tecnico_agenda.os 
			where tbl_tecnico_agenda.fabrica = $fabricaId 
				and tbl_os.posto = $postoId 
				and tbl_os_campo_extra.campos_adicionais::JSON->>'sincronizado' is null 
				and tbl_tecnico_agenda.data_cancelado is null 
				and tbl_os.excluida is not true 
				and tbl_os.finalizada is null 
				and (tbl_tecnico_agenda.obs <> 'Reagendado' Or tbl_tecnico_agenda.obs is null) ";*/

		$sql = " SELECT distinct  tbl_os.os, 
				 (select (tbl_tecnico_agenda.hora_inicio_trabalho, tbl_tecnico_agenda.hora_fim_trabalho, tbl_tecnico_agenda.tecnico_agenda, tbl_tecnico.nome) from tbl_tecnico_agenda join tbl_tecnico on tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico  where tbl_os.os = tbl_tecnico_agenda.os and (tbl_tecnico_agenda.obs <> 'Reagendado' Or tbl_tecnico_agenda.obs is null) order by tecnico_agenda desc limit 1) as agenda 
				 from tbl_os 
				 join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os
				 join tbl_tecnico_agenda on tbl_os.os = tbl_tecnico_agenda.os 
				 where  tbl_os.posto = $postoId 
				 and tbl_os_campo_extra.campos_adicionais::JSON->>'sincronizado' is null  
				 and tbl_os.fabrica = $fabricaId 
				 and tbl_os.excluida is not true 
				 and tbl_tecnico_agenda.fabrica = $fabricaId  
				 and (tbl_tecnico_agenda.obs <> 'Reagendado' Or tbl_tecnico_agenda.obs is null)
				 and tbl_os.finalizada is null and tbl_tecnico_agenda.data_cancelado is null order by 2 desc";
		$resultado = $this->pdo->query($sql);
		
		return $resultado->fetchAll();
	}

	public function recuperarAgendamentosPorPeriodo(int $fabricaId, string $inicio, string $fim, $busca = array(), int $postoId)
	{
		//and ta.data_cancelado is null
		//$cond_data    = " AND ta.data_agendamento BETWEEN '".$inicio." 00:00:00' AND '".$fim." 23:59:59'  ";
		$cond_tecnico = "";
		$cond_os      = "";
		$cond_conf    = "";
		if(!isset($busca['pesquisa'])){
			$cond_pesquisa = " AND ta.data_cancelado is null ";
		}
		if (!empty($busca)) {			
			if (isset($busca["os"]) && strlen($busca["os"]) > 0) {
				$cond_os  = " AND ta.os = " . $busca["os"];
			} 
			if (isset($busca["data_inicial"]) && strlen($busca["data_inicial"]) > 0 && isset($busca["data_final"]) && strlen($busca["data_final"]) > 0) {
				$cond_data    = " AND ta.data_agendamento BETWEEN '".$busca["data_inicial"]." 00:00:00' AND '".$busca["data_final"]." 23:59:59'";
			}
			if (isset($busca["tecnico"]) && strlen($busca["tecnico"]) > 0) {
				$cond_tecnico    = " AND ta.tecnico = " . $busca["tecnico"];
			}
			if (isset($busca["confirmado"]) && strlen($busca["confirmado"]) > 0) {
				$cond_conf    = " AND ta.confirmado IS NOT NULL";
			}		
		}

		if(strlen($busca['busca_tipo_atendimento']) > 0){
			$cond_tipo_atendimento = " AND tbl_os.tipo_atendimento = ". $busca['busca_tipo_atendimento'];
		}
		//var_dump($fabricaId, $inicio, $fim); die();
		$query = "SELECT 
						ta.tecnico_agenda, 
						ta.tecnico, 
						ta.os,
						tbl_os.qtde_km,
						tbl_os.qtde_hora as horimetro, 
						tbl_os.data_abertura, 
						tbl_os.finalizada, 
						tbl_status_checkpoint.descricao as descricao_status_checkpoint,
						ta.data_agendamento, 
						ta.data_cancelado,
						ta.confirmado, 
						ta.periodo, 
						ta.obs,
						ta.justificativa, 
						tt.nome, 
						ta.hora_inicio_trabalho, 
						ta.hora_fim_trabalho,
						tbl_tipo_atendimento.descricao as descricao_tipo_atendimento, 
						ta.fabrica,
						ta.data_conclusao 
				  FROM tbl_tecnico_agenda ta 
			INNER JOIN 
						(SELECT os, max(ordem) AS maxordem FROM tbl_tecnico_agenda GROUP BY os) topordem 
							ON ta.os = topordem.os AND ta.ordem = topordem.maxordem
			INNER JOIN tbl_tecnico tt ON tt.tecnico = ta.tecnico
			INNER JOIN tbl_os on tbl_os.os = ta.os and tbl_os.fabrica = $fabricaId 
			INNER JOIN tbl_tipo_atendimento on tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
			INNER JOIN tbl_status_checkpoint on tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint 
				 WHERE ta.fabrica = $fabricaId
				 	and tt.posto = $postoId
			           {$cond_data}
			           {$cond_tecnico}
			           {$cond_os}
			           {$cond_conf}
			           {$cond_tipo_atendimento}
			           {$cond_pesquisa}

			            Order by ta.os ";
		$resultado = $this->pdo->query($query);

		foreach ($resultado as $row) {
			$dados[] = $row;
		}		
		
		$dados = array_filter($dados);
		return $dados;
	}

	public function recuperarAgendamentosPorTecnico(int $fabricaId, int $tecnicoId, string $inicio, string $fim)
	{
		$query = "SELECT tta.os, tta.data_agendamento, tta.confirmado, tta.periodo, tta.obs,
			tta.justificativa, tt.nome
			FROM tbl_tecnico_agenda tta 
			INNER JOIN tbl_tecnico tt ON tt.tecnico = tta.tecnico
			WHERE tta.fabrica = $fabricaId AND tta.tecnico = $tecnicoId
			AND tta.data_agendamento BETWEEN '$inicio' AND '$fim' ";
		$resultado = $this->pdo->query($query);

		return $resultado->fetchAll();
	}

	public function recuperarAgendamentosPorOS(int $fabricaId, string $os, string $inicio, string $fim)
	{
		if(strlen($inicio) > 0 AND strlen($fim) > 0){
			$between = " AND tta.data_agendamento BETWEEN '$inicio' AND '$fim' ";
		}

		$query = "SELECT tta.os, tta.data_agendamento, tta.hora_inicio_trabalho, tta.hora_fim_trabalho, tta.confirmado, tta.periodo, tta.obs, tta.justificativa, tt.nome, tt.tecnico, tt.fabrica, tta.data_cancelado, tta.tecnico_agenda 
			FROM tbl_tecnico_agenda tta 
			INNER JOIN tbl_tecnico tt ON tt.tecnico = tta.tecnico
			WHERE tta.fabrica = $fabricaId AND tta.os = $os
			$between order by tta.tecnico_agenda desc limit 1 ";
		$resultado = $this->pdo->query($query);
		return $resultado->fetchAll();
	}

	public function recuperarTecnicosPorFabrica(int $fabricaId, int $postoId)
	{
		$query = "SELECT t.tecnico, t.nome
			FROM tbl_tecnico t 
			WHERE t.fabrica = $fabricaId
			AND t.posto = $postoId
			AND t.ativo = 't'
			and t.codigo_externo is not null
			ORDER BY t.nome";

		$resultado = $this->pdo->query($query);

		return $resultado->fetchAll();
	}

	public function recuperarTipoAtendimentosPorFabrica(int $fabricaId){
		$query = "SELECT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $fabricaId and ativo is true ORDER BY descricao";
		$resultado = $this->pdo->query($query);

		return $resultado->fetchAll();
	}

	public function recuperarOSPorFabrica(int $fabricaId, int $postoId)
	{
		if($fabricaId != 148){
			$condKmGoogle = "AND ta.km_google is true ";
		}

		//and o.consumidor_revenda = 'C'


		$query = "SELECT o.os, 
							tbl_tecnico_agenda.os as os_agendada, 
		                 pd.referencia   || ' - ' ||  pd.descricao AS produto, 
		                 o.data_abertura, 
		                 o.qtde_km, 
		                 o.consumidor_nome,
		                 o.consumidor_endereco || ',  ' ||  
		                 o.consumidor_numero  || ' - ' ||  
		                 o.consumidor_complemento  || ' - ' ||  
		                 o.consumidor_bairro  || ' - ' ||  
		                 o.consumidor_cep  || ' - ' ||  
		                 o.consumidor_cidade  || ' / ' ||  
		                 o.consumidor_estado AS consumidor_endereco_completo,
		                 ta.descricao AS tipo_atendimento
			FROM tbl_os o 
			INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento
			INNER JOIN tbl_produto pd ON pd.produto = o.produto AND pd.fabrica_i=$fabricaId
			LEFT JOIN tbl_tecnico_agenda on tbl_tecnico_agenda.os = o.os 
				and tbl_tecnico_agenda.data_cancelado is null
				and tbl_tecnico_agenda.obs <> 'Reagendado'
			WHERE o.fabrica = $fabricaId
			$condKmGoogle
			AND o.finalizada is null 
			and o.excluida <> 't'
			AND tbl_tecnico_agenda.os is null 
			AND o.posto = $postoId		

			AND o.data_digitacao > '2019-01-01 00:00:00'
			ORDER BY o.os desc ";

		$resultado = $this->pdo->query($query);

		return $resultado->fetchAll();
	}
	public function ValidaAgendamento($data_inicio, $data_fim, $tecnico, $os){
		$query = "SELECT tecnico_agenda 
					from tbl_tecnico_agenda 
					where tecnico = $tecnico 
					and '$data_inicio' between hora_inicio_trabalho and hora_fim_trabalho 
					and data_cancelado is null 
					and (obs <> trim('Reagendado') OR obs is null)
					and tbl_tecnico_agenda.os <> $os 
					and data_conclusao is null";	
		$resultado = $this->pdo->query($query);
		
		if(count($resultado->fetchAll())>0){
			return false;
		}else{
			$query = "SELECT tecnico_agenda 
					from tbl_tecnico_agenda 
					where tecnico = $tecnico
					and '$data_fim' between hora_inicio_trabalho and hora_fim_trabalho
					and data_cancelado is null 
					and (obs <> trim('Reagendado') OR obs is null)
					and tbl_tecnico_agenda.os <> $os 
					and data_conclusao is null ";
			$resultado = $this->pdo->query($query);
			if(count($resultado->fetchAll())>0){
				return false;
			}
		}

		return true;

	}
	public function setTecnicoOS(array $agendamento){
		$query = "UPDATE tbl_os SET tecnico = :tecnico WHERE os = :os AND fabrica = :fabrica ";
		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute(
				[
					':fabrica' => $agendamento['fabrica'],
					':tecnico' => $agendamento['tecnico'],
					':os' => $agendamento['os'],
				]
			);
			return $stmt->rowCount();
		} catch (Excption $e) {
			return $e->getMessage();
		}
	}

	public function limpaSincronizado($os){

		$query = "UPDATE tbl_os_campo_extra SET campos_adicionais = null WHERE os = :os ";
		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute(
				[
					':os' => $os,
				]
			);
			return $stmt->rowCount();
		} catch (Excption $e) {
			return $e->getMessage();
		}
	}

	private function gravaOsCampoExtra(array $agendamento){

		$query = "SELECT * FROM tbl_os_campo_extra WHERE os = ".$agendamento['os']." and fabrica = ".$agendamento['fabrica'];
		$resultado = $this->pdo->query($query);
		if(count($resultado->fetchAll())==0){		
			$inserir = "INSERT INTO tbl_os_campo_extra (os, fabrica) VALUES (:os, :fabrica)";
			$stmt = $this->pdo->prepare($inserir);
			$stmt->execute(
				[
					':fabrica' => $agendamento['fabrica'],					
					':os' => $agendamento['os']					
				]
			);

			if($stmt->rowCount() > 0){
				return true;
			}else{
				return false;
			}			
		}		
	}

	public function persistirAgendamento(array $agendamento)
	{
		$this->setTecnicoOS($agendamento);

		$this->gravaOsCampoExtra($agendamento);

		$query = "INSERT INTO tbl_tecnico_agenda 
			(fabrica, tecnico, os, data_agendamento, hora_inicio_trabalho, hora_fim_trabalho, ordem, confirmado, periodo, obs) 
			VALUES
			(:fabrica, :tecnico, :os, :data_agendamento, :hora_inicio_trabalho, :hora_fim_trabalho, :ordem, :confirmado, :periodo, :obs)";

		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute(
				[
					':fabrica' => $agendamento['fabrica'],
					':tecnico' => $agendamento['tecnico'],
					':os' => $agendamento['os'],
					':data_agendamento' => $agendamento['data_agendamento'],
					':hora_inicio_trabalho' => $agendamento['hora_inicio_trabalho'],
					':hora_fim_trabalho' => $agendamento['hora_fim_trabalho'],
					':ordem' => $agendamento['ordem'],
					':confirmado' => $agendamento['confirmado'],
					':periodo' => $agendamento['periodo'],
					':obs' => $agendamento['obs'],
				]
			);		

			return $stmt->rowCount();
		} catch (Excption $e) {
			return $e->getMessage();
		}
	}

	public function persistirEdicaoAgendamento(array $agendamento)
	{

		if($this->ValidaAgendamento($agendamento['hora_inicio_trabalho'], $agendamento['hora_fim_trabalho'], $agendamento['tecnico'], $agendamento['os'])){
	
			$this->pdo->beginTransaction();

			$ordem = $this->recuperarOrdemOS($agendamento['os']);

			$ordem = $ordem[0]['ordem'] != NULL ? $ordem[0]['ordem'] + 1 : 1;			

			if($agendamento['cancelar_anterior'] == 'sim'){
				$cancelar_anterior = date("Y-m-d H:i:s");
			}else{
				$cancelar_anterior = null;
			}

			$this->setTecnicoOS($agendamento);

			$query = "UPDATE tbl_tecnico_agenda
				SET confirmado = :confirmado, obs = :obs, justificativa_cancelado = :justificativa, data_cancelado = :data_cancelado			
				WHERE tecnico_agenda = :tecnico_agenda";

			try {
				$stmt = $this->pdo->prepare($query);
				$stmt->execute(
					[
						':tecnico_agenda' => $agendamento['tecnico_agenda'],					
						':confirmado' => $agendamento['confirmado'],
						':obs' => 'Reagendado',
						':justificativa' => $agendamento['justificativa'],
						':data_cancelado' => $cancelar_anterior 
					]
				);

				if(isset($stmt->errorInfo()[2])){
					$arr_erro['erro'][] = $stmt->errorInfo()[2];
				}

				$query = "INSERT INTO tbl_tecnico_agenda 
				(fabrica, tecnico, os, data_agendamento, hora_inicio_trabalho, hora_fim_trabalho, ordem, confirmado, periodo) 
				VALUES
				(:fabrica, :tecnico, :os, :data_agendamento, :hora_inicio_trabalho, :hora_fim_trabalho, :ordem, :confirmado, :periodo)";
			
				$stmt = $this->pdo->prepare($query);
				$stmt->execute(
					[
						':fabrica' => $agendamento['fabrica'],
						':tecnico' => $agendamento['tecnico'],
						':os' => $agendamento['os'],
						':data_agendamento' => $agendamento['hora_inicio_trabalho'],
						':hora_inicio_trabalho' => $agendamento['hora_inicio_trabalho'],
						':hora_fim_trabalho' => $agendamento['hora_fim_trabalho'],
						':ordem' => $ordem,
						':confirmado' => $agendamento['confirmado'],
						':periodo' => $agendamento['periodo'],						
					]
				);		
				if(isset($stmt->errorInfo()[2])){
					$arr_erro['erro'][] = $stmt->errorInfo()[2];
				}	

			if( count( array_filter($arr_erro)) == 0 and $this->verificarSincronizado($agendamento['os'], $agendamento['fabrica']) ){
				$retorno_api = $this->Reagendar($agendamento['os'], 'OS', $agendamento['hora_inicio_trabalho'], $agendamento['hora_fim_trabalho'], $agendamento['fabrica'], $agendamento['tecnico']);

				if($retorno_api['http_code'] == 404){
					$arr_erro['erro'][] = $retorno_api['exception'];
				}
			}

			if( count( array_filter($arr_erro)) > 0) {
				$this->pdo->rollback();
				return "Falha ao reagendar O.S. \n". implode(',', $arr_erro['erro']);
			}else{
				$this->pdo->commit();
				return "O.S Reagendada com sucesso.";
			}	
			
			
			} catch (Exception $e) {
				$this->pdo->rollback();   
				return $e->getMessage();
			}
		}else{
			$retorno = utf8_encode("Já existe uma agendamento para esse técnico nesse horário");
			return $retorno;
		}
	}

	public function persistirEdicaoDataAgendamento(array $agendamento)
	{
		$query = "UPDATE tbl_tecnico_agenda
			SET data_agendamento = :data_agendamento, hora_inicio_trabalho = :hora_inicio_trabalho, hora_fim_trabalho = :hora_fim_trabalho
			WHERE tecnico_agenda = :tecnico_agenda";

		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute(
				[
					':tecnico_agenda' => $agendamento['tecnico_agenda'],
					':data_agendamento' => $agendamento['data_agendamento'],
					':hora_inicio_trabalho' => $agendamento['hora_inicio_trabalho'],
					':hora_fim_trabalho' => $agendamento['hora_fim_trabalho'],
				]
			);

			return $stmt->rowCount();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	public function excluirAgendamento(int $tecnicoAgendaId)
	{
		$query = "DELETE FROM tbl_tecnico_agenda
			WHERE tecnico_agenda = :tecnico_agenda";

		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute(
				[
					':tecnico_agenda' => $tecnicoAgendaId,
				]
			);

			return $stmt->rowCount();
		} catch (Exception $e) {
			return $e->getMessage();
		}	
	}

	public function Reagendar(string $os, string $contexto, string $data_inicio, string $data_fim, int $fabricaId, int $tecnico)
	{

		if($tecnico > 0){
			$query = " SELECT codigo_externo FROM tbl_tecnico WHERE tecnico = $tecnico and fabrica = $fabricaId";					
			$resultado = $this->pdo->query($query);			 
			$dados = $resultado->fetchAll();			
			$codigo_externo = $dados[0]['codigo_externo'];			
		}

		$this->objTicket = new Ticket($fabricaId);
		$retorno = $this->objTicket->reagendarTicket($os, $contexto, $data_inicio, $data_fim, $codigo_externo);
		return $retorno;
	}

	public function cancelarAgendamento(int $tecnicoAgendaId, int $fabricaId, int $os, string $motivo_cancelamento)
	{

		$this->objTicket = new Ticket($fabricaId);
		$retorno = $this->objTicket->cancelarTicket($os);

		if($retorno['status_code']== 404){
			return utf8_decode($retorno['exception']);
		}else{

			$this->limpaSincronizado($os);

			$query = "UPDATE tbl_tecnico_agenda set data_cancelado = now(), justificativa_cancelado = :justificativa  WHERE tecnico_agenda = :tecnico_agenda";
			try {
				$stmt = $this->pdo->prepare($query);
				$stmt->execute(
					[
						':tecnico_agenda' => $tecnicoAgendaId,
						':justificativa' => $motivo_cancelamento
					]
				);

				$qtde = $stmt->rowCount();

				if($qtde > 0){
					return utf8_decode($retorno['message']);
				}else{
					return "Falha ao cancelar agendamento";
				}				
			} catch (Exception $e) {
				return $e->getMessage();
			}
		}	
	}

	public function recuperarOrdemOS(int $os)
	{
		$query = "SELECT ta.ordem
			FROM tbl_tecnico_agenda ta
			WHERE ta.os = $os
			ORDER BY ta.tecnico_agenda DESC LIMIT 1";

		$resultado = $this->pdo->query($query);

		return $resultado->fetchAll();
	}

	private function verificarSincronizado(int $os, int $fabricaId)
	{
		$query = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os and fabrica = $fabricaId";
		$resultado = $this->pdo->query($query);
		$dados = $resultado->fetch(PDO::FETCH_ASSOC); 

		$dados = json_decode($dados['campos_adicionais'], true);

		if($dados['sincronizado'] == true){
			return true;
		}else{
			return false;
		}
	}
}
