<?php 

namespace Ticket;

use Posvenda\Model\Os as OsModel;
use Posvenda\TcMaps;


class Ticket{

	public $_model;
    public $_erro;
    protected $_fabrica;
    public  $camposFabrica;
    private $_os;
    private $conn;
    private $oTcMaps;

	public function __construct($fabrica){
		$this->_fabrica = $fabrica;		

        if(!empty($this->_fabrica)){
            $this->_model = new OsModel($this->_fabrica);
        }
		$this->conn = $this->_model->getPDO();

		$this->oTcMaps = new TcMaps($this->_fabrica);
	}

	function removePecaOS($deleted, $numOs){

		foreach($deleted as $p){
			$sql = "SELECT tbl_os_item.os_item, tbl_os_item.peca, tbl_os_item.pedido FROM tbl_os_item 
				join tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto 
				WHERE tbl_os_produto.os = $numOs 
				AND tbl_os_item.peca = ". $p['id'];
			$query 		= $this->conn->query($sql);
	    	$arr_del   	= $query->fetch(\PDO::FETCH_ASSOC);

	    	if(!empty($arr_del['os_item'])){
		    	if(!empty($arr_del['pedido'])){
		    		$sql = "SELECT faturamento_item from tbl_faturamento_item where pedido = ".$arr_del['pedido']. " and peca = ". $arr_del['peca']; 
		    		$query 		= $this->conn->query($sql);
		    		$arr_fat   	= $query->fetch(\PDO::FETCH_ASSOC);

		    		if(count(array_filter($arr_fat))==0){
		    			$sql 		= "DELETE FROM tbl_os_item WHERE os_item = ". $arr_del['os_item'];
		    			$query 		= $this->conn->query($sql);
		    		}
		    	}else{
	    			$sql 		= "DELETE FROM tbl_os_item WHERE os_item = ". $arr_del['os_item'];
	    			$query 		= $this->conn->query($sql);
		    	}	 
	    	}   	
		}
	}

	function buscaOS($fabrica){

		$sql 		= "SELECT tbl_tecnico_agenda.os, tbl_tecnico.nome
						FROM tbl_tecnico_agenda
						JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico
						JOIN tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_tecnico_agenda.os 
						WHERE tbl_tecnico.fabrica = ".$this->_fabrica." and tbl_tecnico.codigo_externo is not null 
						and tbl_os_campo_extra.campos_adicionais::JSON->>'sincronizado' isnull ";
		$query 		= $this->conn->query($sql);
	    $arr_os   	= $query->fetchAll(\PDO::FETCH_ASSOC);

        return $arr_os;

	}

	private function calculaKm($lat_lng_origem, $lat_lng_destino, $ida_volta = false){
		$retorno_km = $this->oTcMaps->route($lat_lng_origem, $lat_lng_destino);
		$km_ida 	= $retorno_km['total_km'];

		if($ida_volta == true){
			$retorno_km_volta = $this->oTcMaps->route($lat_lng_destino,$lat_lng_origem);
			$km_volta 	=  $retorno_km_volta['total_km'];
			return $km_ida + $km_volta;
		}
		return $km_ida;
	}

	private function formata_data_hora_tela($data){

		$envio = strtotime($data);
		$datamensagem = date("d/m/Y", $envio);
		$horamensagem = date("H:i", $envio);

		return $datamensagem. ' ' .$horamensagem;

	}

	private function BuscaParamentros(){
		$sql = "SELECT parametros_adicionais FROM tbl_admin WHERE fabrica = ". $this->_fabrica ;
		$query  	= $pdo->query($sql_os);
        $retorno   = $query->fetch(\PDO::FETCH_ASSOC);

        //print_r($retorno);

	}	

	public function BuscaDadosOs($os){

		$this->_os = $os;
		$dados_os = array();

		$sql_os = "SELECT tbl_os.os,
						tbl_tecnico_agenda.data_agendamento,
						TO_CHAR(tbl_tecnico_agenda.hora_inicio_trabalho,'DD/MM/YYYY HH24:MI') AS hora_inicio_trabalho,
						TO_CHAR(tbl_tecnico_agenda.hora_fim_trabalho,'DD/MM/YYYY HH24:MI') AS hora_fim_trabalho,
						tbl_os.data_nf as data_compra, 
						tbl_os.nota_fiscal,
						tbl_os.defeito_reclamado_descricao as defeito_reclamado, 
						tbl_os.consumidor_cep ,
						tbl_os.consumidor_estado ,
						tbl_os.consumidor_cidade ,
						tbl_os.consumidor_bairro ,
						tbl_os.consumidor_endereco ,
						tbl_os.consumidor_numero ,
						tbl_os.consumidor_cpf,
						tbl_os.consumidor_nome,
						tbl_os.consumidor_fone,
						tbl_os.consumidor_celular,
						tbl_os.consumidor_email,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome as nome_posto, 
						tbl_posto_fabrica.contato_endereco,
						tbl_posto_fabrica.contato_numero,
						tbl_posto_fabrica.contato_bairro,
						tbl_posto_fabrica.contato_cidade,
						tbl_posto_fabrica.contato_estado,
						tbl_posto_fabrica.contato_cep,
						tbl_os.serie, 
						tbl_os.hora_tecnica, 
						pd.produto, 
						tbl_os.revenda_nome,
						tbl_os.revenda_cnpj,
						tbl_os.revenda_fone,
						tbl_os.qtde_km as km, 
						tbl_os.qtde_hora as horimetro,
						tbl_os_campo_extra.valores_adicionais as obs_campos_adicionais,
	                    pd.descricao as produto_descricao, 
	                    pd.referencia as produto_referencia, 
	                    pd.familia as familia, 
	                    sc.descricao as status_os, 
	                    ta.descricao as tipo_atendimento,
	                    tbl_tecnico.codigo_externo as user_external_key,
	                    tbl_posto.parametros_adicionais,
	                    ox.obs_adicionais 
	                    $camposFabrica 
				FROM tbl_os 
				join tbl_tipo_atendimento as ta on ta.tipo_atendimento = tbl_os.tipo_atendimento
				join tbl_status_checkpoint as sc on sc.status_checkpoint = tbl_os.status_checkpoint
				join tbl_produto as pd on pd.produto  = tbl_os.produto 
				join tbl_os_extra as ox on ox.os  = tbl_os.os
				join tbl_tecnico on tbl_tecnico.tecnico = tbl_os.tecnico 
				join tbl_tecnico_agenda on tbl_tecnico_agenda.os = tbl_os.os and tbl_tecnico_agenda.fabrica = $this->_fabrica 
				join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = ". $this->_fabrica ."
				join tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto
				left join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os and tbl_os_campo_extra.fabrica = " . $this->_fabrica . "
				WHERE tbl_os.fabrica = ". $this->_fabrica ." 
				AND tbl_tecnico_agenda.data_cancelado is null				
				AND tbl_os.os = ".$this->_os ;
		$query  	= $this->conn->query($sql_os);
	
        $dados_os   = $query->fetch(\PDO::FETCH_ASSOC);

        if (in_array($dados_os["tipo_atendimento"], ['Revisão', 'Entrega Técnica'])) {
            $dados_os["calcula_km"] = true;
        }

        if (strlen($dados_os["serie"]) > 0) {
        	$historico = $this->HistoricoProduto($dados_os["serie"], $this->_fabrica, $dados_os['os']);
        	$historico = array_filter($historico);
        	if(count($historico) > 0){
            	$dados_os["historico_produto"] = $historico;
        	}
        }

		$dados_os['tipo_atendimento'] = utf8_encode($dados_os['tipo_atendimento']);
		$dados_os['status_os'] = utf8_encode($dados_os['status_os']);
		$dados_os['consumidor_endereco'] = utf8_encode($dados_os['consumidor_endereco']);
		$dados_os['consumidor_bairro'] = utf8_encode($dados_os['consumidor_bairro']);
		$dados_os['produto_descricao'] = utf8_encode($dados_os['produto_descricao']);
		$dados_os['obs'] = utf8_encode($dados_os['obs']);
		$dados_os['obs_campos_adicionais'] = json_decode($dados_os['obs_campos_adicionais'], true);

		$dados_localizacao = $this->oTcMaps->tcGeocode($dados_os['consumidor_endereco'], $dados_os['consumidor_numero'], $dados_os['consumidor_cidade'], $dados_os['consumidor_estado'], $dados_os['consumidor_cep'], $debug = false);

		$dados_os['latitude'] = $dados_localizacao['latitude'];
		$dados_os['longitude'] = $dados_localizacao['longitude'];
		$dados_os['distancia_limite'] = 0;


		$dados_localizacao_posto = $this->oTcMaps->tcGeocode($dados_os['contato_endereco'], $dados_os['contato_numero'], $dados_os['contato_cidade'], $dados_os['contato_estado'], $dados_os['contato_cep'], $debug = false);


		$dados_os_posto['latitude_posto'] = $dados_localizacao_posto['latitude'];
		$dados_os_posto['longitude_posto'] = $dados_localizacao_posto['longitude'];


		$localizacao_origem = $dados_localizacao['latitude'].",".$dados_localizacao['longitude'];
		$localizacao_destino = $dados_localizacao_posto['latitude']. ",".$dados_localizacao_posto['longitude'];

		$retornoKm = $this->calculaKm($localizacao_origem, $localizacao_destino, true);		

		$dados_os['km'] = $retornoKm; 

		return $dados_os; 
	}

	private function HistoricoProduto($serie, $fabrica, $os){

		$dados_historico= array();

		$sql = "SELECT  tbl_os.os, 
                        tbl_posto.nome AS posto, 
                        tbl_os.data_abertura, 
                        tbl_tipo_atendimento.descricao AS tipo_atendimento, 
                        tbl_produto.referencia,
                        tbl_produto.descricao,
                        tbl_os.qtde_hora,
                        tbl_os.hora_tecnica,
                        tbl_defeito_constatado.descricao AS defeito_constatado                        
                from tbl_os 
                join tbl_posto on tbl_posto.posto = tbl_os.posto
				join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto
				join tbl_produto on tbl_produto.produto = tbl_os.produto 
				join tbl_tipo_atendimento on tbl_tipo_atendimento.tipo_atendimento  = tbl_os.tipo_atendimento 
				left join tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado 
                WHERE  tbl_os.serie = '$serie' 
                AND tbl_os.fabrica = $fabrica
                AND tbl_posto_fabrica.fabrica = $fabrica
                AND tbl_produto.fabrica_i =  $fabrica 
                AND tbl_tipo_atendimento.fabrica = $fabrica
                AND tbl_os.excluida =  'f'
                AND tbl_os.finalizada is not null 
                order by tbl_os.data_abertura DESC ";
        $query  					= $this->conn->query($sql);
        $dados_historico_produto   	= $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach($dados_historico_produto as $chave => $historico_produto){
	        $dados_historico[$chave]["os"] = $historico_produto["os"];
			$dados_historico[$chave]["posto"] = utf8_encode($historico_produto["posto"]);
			$dados_historico[$chave]["data_abertura"] = $historico_produto["data_abertura"];
	        $dados_historico[$chave]["referencia"] = $historico_produto["referencia"];
	        $dados_historico[$chave]["descricao"] = utf8_encode($historico_produto["descricao"]);

	        $dados_historico[$chave]["qtde_hora"] = $historico_produto["qtde_hora"];
	        $dados_historico[$chave]["hora_tecnica"] = $historico_produto["hora_tecnica"];
	        
	        $dados_historico[$chave]["defeito_constatado"] = utf8_encode($historico_produto["defeito_constatado"]);
	        
	        $dados_historico[$chave]["tipo_atendimento"] = utf8_encode($historico_produto["tipo_atendimento"]);
	        
	        $dados_historico[$chave]["serie"] = $this->getHistoricoProdutoPecas($os, $fabrica);        
        }

        return $dados_historico; 
	}

	private function getHistoricoProdutoPecas($os, $fabrica){
		$dados_historico_peca = array();

        $sql = "SELECT tbl_peca.referencia, 
                                tbl_peca.descricao, 
                                tbl_servico_realizado.descricao as servico
                FROM tbl_os 
                join tbl_os_produto on tbl_os_produto.os  =  tbl_os.os
                join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
                join tbl_peca on tbl_peca.peca = tbl_os_item.peca 
        		join tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado 
                where tbl_os_produto.os = $os 
                        AND tbl_peca.fabrica = $fabrica 
                        AND tbl_servico_realizado.fabrica = $fabrica ";
        $query  					= $this->conn->query($sql);
        $dados_historico_peca   	= $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach($dados_historico_peca as $chave => $historico_servico){
        	$dados_historico[$chave]['referencia'] = $historico_servico['referencia']; 
        	$dados_historico[$chave]['descricao'] = utf8_encode($historico_servico['descricao']); 
        	$dados_historico[$chave]['servico'] = utf8_encode($historico_servico['servico']); 
        }
        return $dados_historico;
	}

	private function getDefeitoConstatado($familia){

		$retornoDefeitoConstatado = array();

		$sql = "SELECT 
					DISTINCT tbl_defeito_constatado.defeito_constatado, 
					tbl_defeito_constatado.descricao					
				FROM tbl_diagnostico 
				JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.fabrica = ".$this->_fabrica ." 
				JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = ".$this->_fabrica ." 
				JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = ".$this->_fabrica ." 
				WHERE tbl_diagnostico.fabrica = ".$this->_fabrica ." 
				AND tbl_diagnostico.familia = ".$familia." 
				AND tbl_diagnostico.ativo IS TRUE 
				ORDER BY tbl_defeito_constatado.descricao ASC";
		$query  					= $this->conn->query($sql);
        $dados_defeito_constatado  	= $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach($dados_defeito_constatado as $chave => $defeito_constatado){
        	$retornoDefeitoConstatado[$chave]['descricao'] = utf8_encode($defeito_constatado['descricao']);
        	$retornoDefeitoConstatado[$chave]['defeito_constatado'] = $defeito_constatado['defeito_constatado'];
        }

        return $retornoDefeitoConstatado;
	}

	private function getTipoAnexo($contexto){
		$sql = "SELECT tbl_anexo_tipo.nome, tbl_anexo_tipo.codigo
				FROM tbl_anexo_tipo 
				JOIN tbl_anexo_contexto on tbl_anexo_contexto.anexo_contexto = tbl_anexo_tipo.anexo_contexto
				WHERE tbl_anexo_tipo.fabrica = ".$this->_fabrica." 
				AND tbl_anexo_contexto.nome = '$contexto' ";
		$query  			= $this->conn->query($sql);	
        $dados_tipo_anexo  	= $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach($dados_tipo_anexo as $chave => $tipo_anexo){
        	$retorno_tipo_anexo[$chave]['codigo'] = $tipo_anexo['codigo'];
        	$retorno_tipo_anexo[$chave]['nome'] = utf8_encode($tipo_anexo['nome']);
        }

        return $retorno_tipo_anexo; 

	}

	private function getServicoRealizado(){
		$retorno_servico_realizado = array();

		$sql = "SELECT servico_realizado, descricao from tbl_servico_realizado WHERE fabrica = ".$this->_fabrica ;
		$query  					= $this->conn->query($sql);	
        $dados_servico_realizado  	= $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach($dados_servico_realizado as $chave => $servico_realizado){
        	$retorno_servico_realizado[$chave]['descricao'] = utf8_encode($servico_realizado['descricao']);
        	$retorno_servico_realizado[$chave]['servico_realizado'] = $servico_realizado['servico_realizado'];
        }
        return $retorno_servico_realizado; 
	}

	private function getSolucao($produto, $usaSolucaoFamilia = false){
		$retorno_solucao = array();

		if($usaSolucaoFamilia){
			$sql = "SELECT  tbl_solucao.solucao,
	                        tbl_solucao.descricao
	                FROM    tbl_diagnostico
	                JOIN    tbl_solucao ON  tbl_solucao.solucao = tbl_diagnostico.solucao
	                                    AND tbl_solucao.fabrica = ".$this->_fabrica."
	                JOIN    tbl_familia ON  tbl_familia.familia = tbl_diagnostico.familia
	                                    AND tbl_familia.fabrica = ".$this->_fabrica."
	                JOIN    tbl_produto ON  tbl_produto.familia = tbl_familia.familia
	                                    AND tbl_produto.fabrica_i = ".$this->_fabrica."
	                WHERE   tbl_diagnostico.fabrica     = ".$this->_fabrica."
	                AND     tbl_produto.produto         = {$produto}
	                AND     tbl_diagnostico.ativo       IS TRUE
	                AND     tbl_diagnostico.diagnostico NOT IN(SELECT diagnostico FROM tbl_diagnostico_produto)
	                UNION

	                SELECT  tbl_solucao.solucao,
	                        tbl_solucao.descricao
	                FROM    tbl_diagnostico
	                JOIN    tbl_solucao             ON  tbl_solucao.solucao             = tbl_diagnostico.solucao
	                                                AND tbl_solucao.fabrica             = ".$this->_fabrica."
	                JOIN    tbl_familia             ON  tbl_familia.familia             = tbl_diagnostico.familia
	                                                AND tbl_familia.fabrica             = ".$this->_fabrica."
	                JOIN    tbl_produto             ON  tbl_produto.familia             = tbl_familia.familia
	                                                AND tbl_produto.fabrica_i           = ".$this->_fabrica."
	                                                AND tbl_produto.familia             = tbl_diagnostico.familia
	                JOIN    tbl_diagnostico_produto ON  tbl_diagnostico.diagnostico     = tbl_diagnostico_produto.diagnostico
	                                                AND tbl_diagnostico_produto.produto = tbl_produto.produto
	                WHERE   tbl_diagnostico.fabrica = ".$this->_fabrica."
	                AND     tbl_produto.produto     = {$produto}
	                AND     tbl_diagnostico.ativo   IS TRUE";
	    } else {
	        $sql = "SELECT  DISTINCT
	                        tbl_solucao.solucao,
	                        tbl_solucao.descricao
	                FROM    tbl_diagnostico
	                JOIN    tbl_solucao ON  tbl_solucao.solucao = tbl_diagnostico.solucao
	                                    AND tbl_solucao.fabrica = ".$this->_fabrica."
	                JOIN    tbl_familia ON  tbl_familia.familia = tbl_diagnostico.familia
	                                    AND tbl_familia.fabrica = ".$this->_fabrica."
	                JOIN    tbl_produto ON  tbl_produto.familia = tbl_familia.familia
	                                    AND tbl_produto.fabrica_i = ".$this->_fabrica."
	                WHERE   tbl_diagnostico.fabrica = ".$this->_fabrica."
	                AND     tbl_produto.produto = ".$produto."
	                AND     tbl_diagnostico.ativo IS TRUE
	        ORDER BY      tbl_solucao.descricao ASC";
	    }


		//$sql = "SELECT solucao, descricao FROM tbl_solucao WHERE fabrica = ".$this->_fabrica;
		$query  			= $this->conn->query($sql);	
        $dados_solucao  	= $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach($dados_solucao as $chave => $solucao){
        	$retorno_solucao[$chave]['descricao'] 	= utf8_encode($solucao['descricao']);
        	$retorno_solucao[$chave]['solucao']	 	= $solucao['solucao'];
        }
        return $retorno_solucao;
	}


	private function getListaBasica($produto){

		$sql = "SELECT tbl_lista_basica.qtde as qtde_maxima,
		 				tbl_peca.referencia, 
		 				tbl_peca.descricao 
		 		FROM tbl_lista_basica 
		 		join tbl_peca on tbl_peca.peca = tbl_lista_basica.peca
		 		WHERE tbl_lista_basica.produto = $produto 
		 		and tbl_lista_basica.fabrica = ". $this->_fabrica ;
		$query  			= $this->conn->query($sql);	
		if($query){
        	$dados_lista_basica	= $query->fetchAll(\PDO::FETCH_ASSOC);
		}

        foreach($dados_lista_basica as $chave => $lista_basica){
        	$retorno_lista_basica[$chave]['descricao'] 		= utf8_encode($lista_basica['descricao']);
        	$retorno_lista_basica[$chave]['referencia']	 	= utf8_encode($lista_basica['referencia']);
        	$retorno_lista_basica[$chave]['qtde_maxima']	 = $lista_basica['qtde_maxima'];
        }
		return $retorno_lista_basica;
	}

	private function setSinc($os){

		$sqlOS = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE  os = $os and fabrica = ".$this->_fabrica;
		$resOS = $this->conn->query($sqlOS);
		$arr   = $resOS->fetch(\PDO::FETCH_ASSOC);

		if(count($arr)==0){
			$campos_adicionais['sincronizado'] = true;
			$campos_adicionais['data_sincronizado'] = date("Y-m-d H:i:s");
			$campos_adicionais = json_encode($campos_adicionais);
			$sqlSinc = "INSERT INTO tbl_os_campo_extra (os,fabrica,campos_adicionais) values ($os, ".$this->_fabrica.", '$campos_adicionais')"; 
		}else{
			$campos_adicionais = json_decode($arr['campos_adicionais'], true);
			$campos_adicionais['sincronizado'] = true;
			$campos_adicionais['data_sincronizado'] = date("Y-m-d H:i:s");
			$campos_adicionais = json_encode($campos_adicionais);

			$sqlSinc 	= "UPDATE tbl_os_campo_extra SET campos_adicionais = '$campos_adicionais' WHERE os = $os AND fabrica = ".$this->_fabrica ;
				
		}
		$query 		= $this->conn->query($sqlSinc);

        if($query){
        	return true;
        }else{
        	return false;
        }        
	}

	public function setIntegrado($ticket, $os, $dadosTicket = null){
		$dados = json_encode(array('ticket'=>$ticket));

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api2.telecontrol.com.br/ticket-checkin/ticket-retorno",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "PUT",
		  CURLOPT_POSTFIELDS => $dados,
		  CURLOPT_HTTPHEADER => array(
		    "access-application-key: 652a4aed48fea22e13eafcfe9b3acde8fe66d8d1",
		    "access-env: PRODUCTION",
		    "cache-control: no-cache",
		    "Content-Type: application/json"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);
		if ($err) {
			return false;
		} else {
			$sqlOS = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE  os = $os and fabrica = ".$this->_fabrica;
			$resOS = $this->conn->query($sqlOS);
			$arr   = $resOS->fetch(\PDO::FETCH_ASSOC);			
			
			$campos_adicionais = json_decode($arr['campos_adicionais'], true);

			$campos_adicionais['data_integrado'] = date("Y-m-d H:i:s");
			$campos_adicionais['checkin'] = $dadosTicket['checkin'];
			$campos_adicionais['checkout'] = $dadosTicket['checkout'];
			$campos_adicionais['ticket'] = $dadosTicket['ticket'];
			
			$campos_adicionais = json_encode($campos_adicionais);

			$sqlSinc 	= "UPDATE tbl_os_campo_extra SET campos_adicionais = '$campos_adicionais' WHERE os = $os AND fabrica = ".$this->_fabrica ;	
			$query 		= $this->conn->query($sqlSinc);

			$sqlConclusao = "UPDATE tbl_tecnico_agenda set data_conclusao = now() where os = $os and fabrica = ".$this->_fabrica." and (obs <> 'Reagendado' OR obs is null) and data_cancelado is null and data_conclusao is null ";
			$queryC 		= $this->conn->query($sqlConclusao);

			return true;
		}
	}

	public function buscaPecaLancada($os = null){

		if(strlen(trim($os))>0){		
		
			$sql = "SELECT tbl_peca.referencia, tbl_os_item.qtde, (tbl_peca.referencia || ' - ' || tbl_peca.descricao) as descricao, tbl_servico_realizado.descricao as servico_realizado_descricao, tbl_servico_realizado.servico_realizado 
					 FROM tbl_os_produto 
					JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto 
					JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca
					join tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado 
					where tbl_os_produto.os = $os ";
			
			$query  			= $this->conn->query($sql);	
			if($query){
	        	$pecas			= $query->fetchAll(\PDO::FETCH_ASSOC);
			}

			foreach($pecas as $chave => $peca){
				$dados[$chave]['name'] = "lista_basica";
				$peca_referencia['peca_referencia'] = array("referencia" => $peca['referencia'], "descricao" => utf8_encode($peca["descricao"]), 'qtde_maxima' => 1, 'value' => utf8_encode($peca["descricao"]));
				$peca_referencia['quantidade'] = array("value" => $peca["qtde"]);
				$peca_referencia['servico_realizado'] = array("key" => $peca['servico_realizado'], 'value' => utf8_encode($peca['servico_realizado_descricao']));

				$dados[$chave]['valueArray'] = json_encode($peca_referencia);
			}

			if(count(array_filter($pecas))>0){
				$retorno['name'] = "lista_basica";
				$retorno['content'] = $dados;
			}else{
				$retorno = '';
			}

			return $retorno;
		}
		return '';
		
	}

	private function getConstatadoSolucaoOs($os){

		if(strlen(trim($os))>0){

			$sql = "SELECT 
					tbl_solucao.descricao as solucao_descricao, 
					tbl_solucao.solucao,  
					tbl_defeito_constatado.descricao as constatado_descricao, 
					tbl_defeito_constatado.defeito_constatado  
					FROM tbl_os_defeito_reclamado_constatado  
					LEFT JOIN tbl_solucao on tbl_solucao.solucao = tbl_os_defeito_reclamado_constatado.solucao 
					LEFT JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado  
					where os = ". $os. " AND tbl_os_defeito_reclamado_constatado.fabrica = ".$this->_fabrica;
			$query = $this->conn->query($sql);	

			if($query){
	        	$constatadoSolucao = $query->fetchAll(\PDO::FETCH_ASSOC);
			}

			foreach($constatadoSolucao as $chave => $linha){

				if(!empty($linha['defeito_constatado'])){
					$dados[$chave] = [
						"defeito_constatado" => array('defeito_constatado' => $linha['defeito_constatado'], 'descricao' => utf8_encode($linha['constatado_descricao'])),
					];
				}else{
					$dados[$chave] = [
						"solucao" => array('solucao' => $linha['solucao'], 'descricao' => utf8_encode($linha['solucao_descricao'])),
					];
				}
			}

			if(count(array_filter($dados))>0){
				$retorno = $dados;
			}else{
				$retorno = '';
			}

			return $retorno;
		}
		return '';

	}


	public function getRetornoTicket($contexto = 'OS', $os = null){
		if($os != null){
			$filtro = "referenceId/$os";
		}

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api2.telecontrol.com.br/ticket-checkin/ticket-retorno/contexto/$contexto/".$filtro,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",		  
		  CURLOPT_HTTPHEADER => array(
		    "access-application-key: 652a4aed48fea22e13eafcfe9b3acde8fe66d8d1",
		    "access-env: PRODUCTION",
		    "cache-control: no-cache",
		    "content-type: application/json"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		$response = json_decode($response, true);

		if ($err) {
		   	$retorno['error'] = $err;
		} else {
			if($response['exception']){
				$retorno['error'] = $response['exception'];
			}else{
				$retorno = $response;	
			}
		}
		return $retorno;
	}

	public function cancelarTicket($os){

		$dados = json_encode(array("reference_id" => $os));

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api2.telecontrol.com.br/ticket-checkin/ticket-cancelado",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "PUT",
		  CURLOPT_POSTFIELDS => $dados,
		  CURLOPT_HTTPHEADER => array(
		    "access-application-key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
		    "access-env: PRODUCTION",
		    "cache-control: no-cache",
		    "content-type: application/json"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);
		$info = curl_getinfo($curl);
		
		curl_close($curl);

		if ($err) {
			$returno['erro'] = $err;
		}else{
			$retorno = json_decode($response, true);
		}			  
		$retorno['http_code'] = $info['http_code'];
		return $retorno;
	}

	public function reagendarTicket($os, $contexto, $data_agendamento, $data_agendamento_fim, $tecnico){

		$dados = json_encode(array("referenceId" => $os, "contexto" => $contexto, "dataAgendamento" => $data_agendamento, "agendamentoInicio" => $this->formata_data_hora_tela($data_agendamento), "agendamentoTermino" => $this->formata_data_hora_tela($data_agendamento_fim), "tecnico" => $tecnico));
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api2.telecontrol.com.br/ticket-checkin/ticket-reagendar",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "PUT",
		  CURLOPT_POSTFIELDS => $dados,
		  CURLOPT_HTTPHEADER => array(
		    "access-application-key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
		    "access-env: PRODUCTION",
		    "cache-control: no-cache",
		    "content-type: application/json"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);
		$info = curl_getinfo($curl);
		
		curl_close($curl);

		if ($err) {
			$returno['erro'] = $err;
		}else{
			$retorno = json_decode($response, true);
		}			  
		$retorno['http_code'] = $info['http_code'];
		return $retorno;
	}
	
	public function run($os, $atualizar=false){

		$dados_os = $this->BuscaDadosOs($os);
		
		$array_defeito_constatado = [];
		if(strlen($dados_os['familia'])){
			$defeitoConstatados = $this->getDefeitoConstatado($dados_os['familia']);
		}
		if (count($defeitoConstatados) > 0) {
			foreach ($defeitoConstatados as $key => $linha) {
				$array_defeito_constatado[] = ["key" => $linha["defeito_constatado"], "value" => $linha["descricao"]];
			}
		}
		
		$array_tipo_anexo = $this->getTipoAnexo("os");		

		$array_servico_realizado = [];
		$servicoRealizado = $this->getServicoRealizado();
		if (count($servicoRealizado) > 0) {
			foreach ($servicoRealizado as $key => $linha) {
				$array_servico_realizado[] = ["key" => $linha["servico_realizado"], "value"  =>   $linha["descricao"]];
			}
		}		

		$array_pecas = [];
		$pecas = $this->getListaBasica($dados_os['produto']);
		if (count($pecas) > 0) {
			foreach ($pecas as $key => $linha) {
				$array_pecas[] = [
					"referencia"  => $linha["referencia"],
					"descricao"   => $linha["referencia"].' - '.$linha["descricao"],
					"qtde_maxima" => $linha["qtde_maxima"],
				];
			}
		}

		//campos
		require_once dirname(__FILE__).  "/regras.php";
		if(file_exists(dirname(__FILE__).  "/regras/regras_".$this->_fabrica.".php")){
			require_once dirname(__FILE__).  "/regras/regras_".$this->_fabrica.".php" ;
		}	


		if (isset($funcoes_adicionar_campos) && !empty($funcoes_adicionar_campos) && is_array($funcoes_adicionar_campos)) {
			foreach ($funcoes_adicionar_campos as $funcao) {
				if (function_exists($funcao)) {
					$retorno = call_user_func($funcao);
					$dados["request"]["checkin"][$retorno['sessao']] = $retorno['dados'];
				}
			}
		}

		$resumido = ["ticket"           => "",
                    "tipo_ticket"  		=> $dados_os["tipo_atendimento"],
                    "status_ticket"     => $dados_os["status_os"],
                    "data_ticket"       => $dados_os["data_agendamento"],
                    "external_id"       => "",
                    "endereco" => [
                                    "cep"        => $dados_os["consumidor_cep"],
                                    "estado"     => $dados_os["consumidor_estado"],
                                    "cidade"     => utf8_encode($dados_os["consumidor_cidade"]),
                                    "bairro"     => $dados_os["consumidor_bairro"],
                                    "logradouro" => $dados_os["consumidor_endereco"],
                                    "numero"     => $dados_os["consumidor_numero"],
                    ]                                
                ];
                
        $parametros_adicionais = json_decode($dados_os['parametros_adicionais'], true);        
        $companyHash = $parametros_adicionais['company_hash'];

        $dados["request"]["ticket_resumido"]  = $resumido;
		$dados['user_external_key'] = $dados_os['user_external_key'];
		$dados['external_key'] = $companyHash;
		$dados['contexto'] = 'OS'; 
		$dados['reference_id'] = $dados_os['os']; 


		if($atualizar == true){
			$resultado = $this->Atualizar($dados, $dados_os['os']);
		}else{
			$resultado = $this->Enviar($dados, $dados_os['os']);
		}		

		return $resultado; 
	}

	function Atualizar($arr, $os){

		$retorno = json_encode($arr);	

		if(strlen(trim($retorno))==0){
			$resultado['erro'] = "Erro ao gerar Json ";
		}else{
			$curl = curl_init();
			curl_setopt_array($curl, array(
			  CURLOPT_URL => "https://api2.telecontrol.com.br/ticket-checkin/ticket", 
			  CURLOPT_RETURNTRANSFER => true,	
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "PUT",
			  CURLOPT_POSTFIELDS => $retorno,
			  CURLOPT_HTTPHEADER => array(
			    "access-application-key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
			    "access-env: PRODUCTION",
			    "cache-control: no-cache",
			    "content-type: application/json"
			  ),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);
			if ($err) {
				$resultado['erro '] = "Erro - ".$err;
			} else {
				$response = json_decode($response, true);

				if($response['exception']){
					$resultado['erro'] = $response['exception'];
				}else{
					$resultado['sucesso'] = $response['message'];
				    $resultado['ticket'] = $response['ticket'];
				    $this->setSinc($os);
				}
		 	}
		 }
		return $resultado;
	}

	function Enviar($arr, $os){

		$retorno = json_encode($arr);

		if(strlen(trim($retorno))==0){
			$resultado['erro'] = "Erro ao gerar Json ";
		}else{
			$curl = curl_init();
			curl_setopt_array($curl, array(
			  CURLOPT_URL => "https://api2.telecontrol.com.br/ticket-checkin/ticket", 
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "POST",
			  CURLOPT_POSTFIELDS => $retorno,
			  CURLOPT_HTTPHEADER => array(
			    "access-application-key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
			    "access-env: PRODUCTION",
			    "cache-control: no-cache",
			    "content-type: application/json"
			  ),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);
			if ($err) {
				$resultado['erro '] = "Erro - ".$err;
			} else {
				$response = json_decode($response, true);

				if($response['exception']){
					$resultado['erro'] = $response['exception'];
				}else{
					$resultado['sucesso'] = $response['message'];
				    $resultado['ticket'] = $response['ticket'];
				    $this->setSinc($os);
				}
		 	}
		 }
		return $resultado;
	}

}


?>