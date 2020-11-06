<?php

#namespace Posvenda;

use Posvenda\Model\Extrato as ExtratoModel;

class ExtratoRoca {

    public $_model;
    public $_erro;

    protected $_fabrica;

    private $_extrato;

    public function __construct($fabrica, $extrato) {
        if (!empty($extrato)) {
            $this->_extrato = $extrato;
        }

        $this->_fabrica = $fabrica;

        if(!empty($this->_fabrica)){
            $this->_model = new ExtratoModel($this->_fabrica);
        }

    }

    public function getExtrato(){
        return $this->_extrato;
	}

	public function calcula($extrato,$posto){

        $pdo = $this->_model->getPDO();



 		$sql = "
 			SELECT 
 				os, 
 				tbl_os.sua_os, 
 				tbl_produto.linha, 
 				tbl_linha.campos_adicionais,
 				tbl_os.posto,
				tbl_os_revenda.os_revenda,
				tbl_os_revenda.qtde_km,
				tbl_os_campo_extra.valores_adicionais,
 				(
 					(DATE_PART('year', tbl_os.data_abertura) - DATE_PART('year', tbl_os.data_nf)) * 12 +
              		(DATE_PART('month', tbl_os.data_abertura) - DATE_PART('month', tbl_os.data_nf))
              	) as qtde_mes,
 				CASE WHEN tbl_posto_fabrica.valor_km <> 0 THEN tbl_posto_fabrica.valor_km ELSE tbl_fabrica.valor_km END as valor_km,
				tbl_tipo_atendimento.fora_garantia
			FROM tbl_os
			JOIN tbl_os_extra using(os)
			JOIN tbl_os_campo_extra using(os)
			JOIN tbl_produto using(produto)
			JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			JOIN tbl_posto_fabrica  ON tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = tbl_os.fabrica
			JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica
			JOIN tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_campo_extra.os_revenda
			JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
			WHERE extrato = $extrato ORDER BY os_revenda, tbl_os.sua_os" ;
        $query  = $pdo->query($sql);
        $res    = $query->fetchAll(\PDO::FETCH_ASSOC);

		$os_revenda_ant = 0 ;
        foreach ($res as $dadosLinha) {
            $os 					= $dadosLinha['os'];
			$sua_os     			= $dadosLinha['sua_os'];
			$linha      			= $dadosLinha['linha'];
			$campos_adicionais      = $dadosLinha['campos_adicionais'];
			$posto      			= $dadosLinha['posto'];
			$os_revenda 			= $dadosLinha['os_revenda'];
			$qtde_km    			= $dadosLinha['qtde_km'];
			$valor_km    			= $dadosLinha['valor_km'];
			$qtde_mes 				= $dadosLinha['qtde_mes'];
			$valores_adicionais		= $dadosLinha['valores_adicionais'];
			$fora_garantia			= $dadosLinha['fora_garantia'];

			$valores_adicionais = json_decode($valores_adicionais);

			$total_va = 0 ;

			foreach($valores_adicionais[0] as $k => $valor) {
				$valor = str_replace(",",".", $valor);
				$total_va = ($valor > 0) ? $valor : 0;
			}


			$campos_adicionais = json_decode($campos_adicionais , true);
			$valor_visita = $campos_adicionais['valor_visita'];
			unset($campos_adicionais['valor_visita']);
		
			$valor_mo = 0;
			if($os_revenda <> $os_revenda_ant) {
				$data_geracao_ant = null;
				$sqlx = "select to_char(data_geracao,'YYYY-MM-DD') as data_geracao_ant from tbl_extrato join tbl_os_extra using(extrato) join tbl_os_campo_extra using(os) where extrato < $extrato and posto = $posto and tbl_extrato.fabrica = ".$this->_fabrica ." and os_revenda = $os_revenda order by 1 desc limit 1 ";
				$queryx  = $pdo->query($sqlx);
				$res = $queryx->fetch(\PDO::FETCH_ASSOC);
				$data_geracao_ant = $res['data_geracao_ant'];

				$sqlx = "select to_char(data_geracao,'YYYY-MM-DD') as data_geracao, data_geracao AS data_extrato from tbl_extrato where extrato = $extrato";
				$queryx  = $pdo->query($sqlx);
				$res = $queryx->fetch(\PDO::FETCH_ASSOC);
				$data_geracao = $res['data_geracao'];
				$data_extrato = $res['data_extrato'];

				if(!empty($data_geracao_ant)) {
					$sql = "select count(1) as qtde_visita from tbl_tecnico_agenda where os_revenda = $os_revenda and  confirmado between '$data_geracao_ant 00:00' and '$data_geracao 23:59' and data_cancelado isnull";
				}else{
					$sql = "select count(1) as qtde_visita from tbl_tecnico_agenda where os_revenda = $os_revenda and  confirmado < '$data_extrato' and data_cancelado isnull";
				}
				$queryx  = $pdo->query($sql);

				$res = $queryx->fetch(\PDO::FETCH_ASSOC);
				$qtde_visita = $res['qtde_visita'];
				if($valor_visita > 0 and $qtde_visita > 0) {
					$qtde_km = ($qtde_km  > 60) ? $qtde_km - 60 : 0;
					$valor_km = ($valor_km > 0) ? $valor_km : 0;
					if ($qtde_mes > 12){
						$total_va = 0 ;
					}

					$sqlx = "update tbl_os set mao_de_obra = ($valor_visita * $qtde_visita) , qtde_km_calculada = $qtde_km * $valor_km * $qtde_visita , qtde_visitas = $qtde_visita, valores_adicionais = $total_va where os = $os and(tbl_os.mao_de_obra is null or tbl_os.mao_de_obra = 0 or tbl_os.qtde_visitas is null)";
					$queryx = $pdo->query($sqlx);
				}

				$sqlx = "select tbl_os.os  from tbl_os join tbl_os_extra using(os) join tbl_os_campo_extra using(os) where posto = $posto and extrato <= $extrato and os_revenda = $os_revenda order by 1";
				$queryx  = $pdo->query($sqlx);

		        $resc    = $queryx->fetchAll(\PDO::FETCH_ASSOC);
				$c = 1;
				foreach ($resc as $oss) {
					$os = $oss['os'];
					foreach($campos_adicionais as $key => $value) {
						if(in_array($c, range($value['qtde_min'], $value['qtde_max']))) {
							$valor_mo  = $value['valor'];
							if($valor_mo > 0) {
								$sqlmo = "UPDATE tbl_os SET mao_de_obra = $valor_mo from tbl_os_extra join tbl_os_campo_extra using(os) WHERE posto = $posto and tbl_os_extra.os = tbl_os.os and tbl_os.os = $os and extrato = $extrato and qtde_visitas isnull";
								$querymo  = $pdo->query($sqlmo);
							}
						}
					}
					$c++;
				}
				if ($qtde_mes > 12){
					$total_va = 0 ;
				}

				if($total_va > 0) {
					$sql_va = "update tbl_os set valores_adicionais = $total_va where os = $os ";
					$res_va = $pdo->query($sql_va);
				}

				if($fora_garantia == 't') {
					$sql_mo = "update tbl_os set mao_de_obra = 0 where os = $os ";
					$res_mo = $pdo->query($sql_mo);
				}
			}else{
				$valor_mo = 0 ;
			}
			$os_revenda_ant = $os_revenda;
        }
    }
}

?>
