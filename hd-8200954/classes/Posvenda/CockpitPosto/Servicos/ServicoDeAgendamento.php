<?php

namespace Posvenda\CockpitPosto\Servicos;

use Posvenda\CockpitPosto\Repositorios\RepositorioDeAgendamento;

class ServicoDeAgendamento
{
	public $repositorioDeAgendamento;

	public function __construct($dbname, $dbhost, $dbport, $dbuser, $dbpassword)
	{
		$pdoInfo = $this->retornaConfiguracoesPdo($dbname, $dbhost, $dbport, $dbuser, $dbpassword);

		$this->repositorioDeAgendamento = new RepositorioDeAgendamento($pdoInfo);
	}

	public function pendenteExportacao(int $fabricaId, int $postoId)
	{
		return $exp_pendente = $this->repositorioDeAgendamento->pendenteExportacao($fabricaId, $postoId);
	}

	public function obtemAgendamentosPorFabrica(int $fabricaId, string $periodoInicio, string $periodoFim, $busca = array(), int $postoId = 0)
	{
		$agendamentos = $this->repositorioDeAgendamento->recuperarAgendamentosPorPeriodo($fabricaId, $periodoInicio, $periodoFim,  $busca, $postoId);
		return $this->tranformarResultadosEmJson($this->gerarObjetosAgenda($agendamentos));
	}

	public function obtemAgendamentosPorTecnico(int $fabricaId, int $tecnicoId, string $periodoInicio, string $periodoFim)
	{
		$agendamentos = $this->repositorioDeAgendamento->recuperarAgendamentosPorTecnico($fabricaId, $tecnicoId, $periodoInicio, $periodoFim);
		
		return $this->tranformarResultadosEmJson($this->gerarObjetosAgenda($agendamentos));
	}

	public function obtemAgendamentosPorOs(int $fabricaId, string $os, string $periodoInicio = '' , string $periodoFim = '')
	{
		$agendamentos = $this->repositorioDeAgendamento->recuperarAgendamentosPorOS($fabricaId, $os, $periodoInicio, $periodoFim);
		
		return $this->tranformarResultadosEmJson($this->gerarObjetosAgenda($agendamentos));		 	
	}

	public function adicionarAgendamento(int $fabricaId, int $tecnicoId = null, int $os, string $dataAgendamento, string $dataInicio, string $dataFim, string $dataConfirmacao = null, string $perido = null, string $obs)
	{

		$agendamento = [
			'fabrica' => $fabricaId,
			'tecnico' => $tecnicoId,
			'os' => $os,
			'data_agendamento' => $dataAgendamento,
			'hora_inicio_trabalho' => $dataInicio,
			'hora_fim_trabalho' => $dataFim,
			'confirmado' => $dataConfirmacao,
			'periodo' => $periodo,
			'obs' => utf8_decode($obs),
		];

		if(strlen($this->validaPeriodoAgendamento($agendamento))>0){
			$retorno['mensagem'] = $this->validaPeriodoAgendamento($agendamento);
			return $retorno;
		}

		if ($this->validarDadosAgendamento($agendamento)) {
			$agendamento['ordem'] = $this->obtemProximaOrdemDaOS($os);

			if(!$this->repositorioDeAgendamento->ValidaAgendamento($agendamento['hora_inicio_trabalho'], $agendamento['hora_fim_trabalho'], $agendamento['tecnico'], $agendamento["os"] ) ){
				$retorno['erro'] = $agendamento["os"];
				$retorno['mensagem'] = "O técnico já possui um agendamento nesse intervalo de data e hora!";
				return $retorno;
			}

			if($this->repositorioDeAgendamento->persistirAgendamento($agendamento)){
				$retorno['sucesso'] = $agendamento["os"];
				return $retorno;
			}			
			//return  $this->repositorioDeAgendamento->persistirAgendamento($agendamento);
		}
		$retorno['erro'] = $agendamento["os"];
		return $retorno;
	}

	public function editarAgendamento(int $tecnicoAgenda, int $tecnicoId, int $os, string $dataConfirmacao = null, string $data_inicio_ag, string $data_fim_ag, int $fabrica, string $cancelar_anterior, string $justificativa_agendamento)
	{
		$agendamento = [
				'tecnico_agenda' => $tecnicoAgenda,
				'tecnico' => $tecnicoId,
				'os' => $os,
				'confirmado' => $dataConfirmacao,
				'hora_inicio_trabalho' => $data_inicio_ag,
				'hora_fim_trabalho' => $data_fim_ag,
				'fabrica' => $fabrica,
				'cancelar_anterior' => $cancelar_anterior,
				'justificativa' => $justificativa_agendamento
			];

			
			if(strlen($this->validaPeriodoAgendamento($agendamento))>0){
				$retorno = $this->validaPeriodoAgendamento($agendamento);
				return $retorno;
			}

			if ($this->validarDadosEditadosAgendamento($agendamento))
			{
				return $this->repositorioDeAgendamento->persistirEdicaoAgendamento($agendamento);
			}

			return false;
		
	}

	public function editarDataAgendamento(int $tecnicoAgendaId, string $dataInicio, string $dataFim)
	{
		$agendamento = [
			'tecnico_agenda' => $tecnicoAgendaId,
			'data_agendamento' => $dataInicio,
			'hora_inicio_trabalho' => $dataInicio,
			'hora_fim_trabalho' => $dataFim,
		];

		if ($this->validarDataAgendamento($agendamento))
		{
			return $this->repositorioDeAgendamento->persistirEdicaoDataAgendamento($agendamento);
		}

		return false;
	}

	public function cancelarAgendamento(int $tecnicoAgendaId, int $fabricaId, int $os, string $motivo_cancelamento)
	{
		return $this->repositorioDeAgendamento->cancelarAgendamento($tecnicoAgendaId, $fabricaId, $os, $motivo_cancelamento);
	}

	public function Reagendamento(string $os, string $contexto, string $data_inicio, int $fabrica)
	{
		return $this->repositorioDeAgendamento->Reagendar($os, $contexto, $data_inicio, $fabrica);
	}

	public function obtemTipoAtendimentoPorFabrica(int $fabricaId){
		return $this->repositorioDeAgendamento->recuperarTipoAtendimentosPorFabrica($fabricaId);	
	}

	public function obtemTecnicosPorFabrica(int $fabricaId, int $postoId)
	{
		return $this->repositorioDeAgendamento->recuperarTecnicosPorFabrica($fabricaId, $postoId);
	}

	public function obtemOsPorFabrica(int $fabricaId, int $postoId)
	{
		return $this->repositorioDeAgendamento->recuperarOSPorFabrica($fabricaId, $postoId);
	}

	private function gerarObjetosAgenda($resultados)
	{
		$objetosAgenda = [];

		foreach ($resultados as $resultado) {
			$dataAgendamento = $this->retornaPeriodoAgendamento($resultado['data_agendamento'], $resultado['periodo'], $resultado['hora_inicio_trabalho'], $resultado['hora_fim_trabalho']);
//descricao antiga $this->gerarDescricao($resultado['obs'], $resultado['nome'], $resultado['os']),
			$objetosAgenda[] = [
				'fabrica_id' => $resultado['fabrica'],
				'title' => "OS: ". $resultado['os'],
				'start' => $dataAgendamento['start'],

				'horimetro' => $resultado['horimetro'],
				'qtde_km' => $resultado['qtde_km'],

				'data_abertura' => $resultado['data_abertura'],
				'finalizada' => $resultado['finalizada'],
				'descricao_status_checkpoint' => $resultado['descricao_status_checkpoint'],

				'end' => $dataAgendamento['end'],
				'color' => (empty($resultado['data_conclusao']))? '#4682B4' : "green",
				'description' => $resultado['obs'],
				
				'editable' => false, // permite drag and drop no calendário
				'os' => $resultado['os'],
				'usuario' => $resultado['nome'],
				'informacoes' => ($resultado['obs']),
				'tecnico_id' => $resultado['tecnico'],
				'tecnico_agenda' => $resultado['tecnico_agenda'],
				'confirmado' => $resultado['confirmado'] ? 1 : 0,
				'cancelado' => $resultado['data_cancelado'], 
				'descricao_tipo_atendimento' => utf8_encode($resultado['descricao_tipo_atendimento']),
			];
		}

		return $objetosAgenda;
	}

	private function gerarDescricao(string $obs, string $tecnico, int $os)
	{
		return utf8_decode("Descrição:") . " $obs - " . utf8_decode("Técnico:") . " $tecnico - OS: $os";
	}

	private function tranformarResultadosEmJson($resultados)
	{
		$retorno = [];
		foreach ($resultados as $key => $value) {
			$retorno[$key]["fabrica_id"] = $value["fabrica_id"];
			$retorno[$key]["title"] = utf8_encode($value["title"]);
			$retorno[$key]["start"] = $value["start"];

			$retorno[$key]["horimetro"] = $value["horimetro"];
			$retorno[$key]["km"] = $value["qtde_km"];

			$retorno[$key]["data_abertura"] = $value["data_abertura"];
			$retorno[$key]["finalizada"] = $value["finalizada"];
			$retorno[$key]["descricao_status_checkpoint"] = utf8_encode($value["descricao_status_checkpoint"]);

			$retorno[$key]["end"] = $value["end"];
			$retorno[$key]["color"] = $value["color"];
			$retorno[$key]["description"] = utf8_encode($value["description"]);
			$retorno[$key]["editable"] = $value["editable"];
			$retorno[$key]["os"] = $value["os"];
			$retorno[$key]["usuario"] = utf8_encode($value["usuario"]);
			$retorno[$key]["informacoes"] = utf8_encode($value["informacoes"]);
			$retorno[$key]["tecnico_id"] = $value["tecnico_id"];
			$retorno[$key]["tecnico_agenda"] = $value["tecnico_agenda"];
			$retorno[$key]["confirmado"] = $value["confirmado"];
			$retorno[$key]["cancelado"] = $value["cancelado"];
			$retorno[$key]['descricao_tipo_atendimento'] = $value['descricao_tipo_atendimento'];
		}

		return json_encode($retorno);
	}

	private function retornaConfiguracoesPdo($dbname, $dbhost, $dbport, $dbuser, $dbpassword): array
	{
		return [
			'dbname' => $dbname,
			'dbhost' => $dbhost,
			'dbport' => $dbport,
			'dbuser' => $dbuser,
			'dbpassword' => $dbpassword,
		];
	}

	private function retornaPeriodoAgendamento(string $dataAgendamento, string $periodo = null, string $periodoInicio = null, string $periodoFim = null): array
	{
		$dataAgendamento = substr($dataAgendamento, 0, 10);
		if ($periodoInicio && $periodoFim) {
			return [
				'start' => $periodoInicio,
				'end' => $periodoFim,
			];
		}

		$periodo = $periodo ? $periodo : 'manhã'; 

		$periodoInicio = ' 08:00:00';
		$periodoFim = ' 12:00:00';

		if ($periodo == 'tarde') {
			$periodoInicio = ' 14:00:00';
			$periodoFim = ' 18:00:00';	
		}		

		return [
			'start' => $dataAgendamento . $periodoInicio,
			'end' => $dataAgendamento . $periodoFim,
		];
	}

	private function validarDadosAgendamento(array $agendamento): bool
	{
		if (!is_int($agendamento['fabrica']) || $agendamento['fabrica'] == 0) {
			return false;
		}

		if (!is_int($agendamento['tecnico']) || $agendamento['tecnico'] == 0) {
			return false;
		}

		if (!is_int($agendamento['os']) || $agendamento['os'] == 0) {
			return false;
		}

		if (!$agendamento['data_agendamento'] || $agendamento['data_agendamento'] == '') {
			return false;
		}

		return true;
	}

	private function validarDadosEditadosAgendamento(array $agendamento): bool
	{

		if (!is_int($agendamento['tecnico']) || $agendamento['tecnico'] == 0) {
			return false;
		}

		if (!is_int($agendamento['os']) || $agendamento['os'] == 0) {
			return false;
		}

		return true;
	}

	private function validarDataAgendamento(array $agendamento): bool
	{
		if (!is_int($agendamento['tecnico_agenda']) || $agendamento['tecnico_agenda'] == 0) {
			return false;
		}

		if (!$agendamento['data_agendamento'] || $agendamento['data_agendamento'] == '') {
			return false;
		}

		if (!$agendamento['hora_inicio_trabalho'] || $agendamento['hora_fim_trabalho'] == '') {
			return false;
		}

		if (!$agendamento['hora_fim_trabalho'] || $agendamento['hora_fim_trabalho'] == '') {
			return false;
		}

		return true;
	}

	private function obtemProximaOrdemDaOS(int $os)
	{
		$resultado = $this->repositorioDeAgendamento->recuperarOrdemOS($os);

		return $resultado[0]['ordem'] != null ? $resultado[0]['ordem'] + 1 : 1;
	}

	private function validaPeriodoAgendamento($agendamento){
		$inicio = $agendamento['hora_inicio_trabalho'];
		$fim 	= $agendamento['hora_fim_trabalho'];

		/*$inicio = explode(" ", $agendamento['hora_inicio_trabalho']);
		$fim 	= explode(" ", $agendamento['hora_fim_trabalho']);
		$datainicio = $inicio[0];
		$horainicio = $inicio[1];
		$datafim = $fim[0];
		$horafim = $fim[1];
		list($di, $mi, $yi) = explode("/", $datainicio);
		list($df, $mf, $yf) = explode("/", $datafim);
		$aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";*/

        $aux_data_inicial = $inicio;
        $aux_data_final = $fim;

        if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
            $msg = "Data inicial maior do que a data final do agendamento";
        }
        if(strtotime($aux_data_final) == strtotime($aux_data_inicial)){
            $msg = "Data inicial igual a data final do agendamento";
        }
		return $msg;
	}
}
