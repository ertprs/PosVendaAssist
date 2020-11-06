<?php

namespace Posvenda\Fabricas\_138;
use Posvenda\Model\Os as OsModel;

class AbreAtendimentoCallcenter{


	private $os;
    private $fabrica;

    public function __construct($os, $fabrica,$conn = null)
    {

        $this->os = $os;
        $this->fabrica = $fabrica;

        parent::__construct($this->_fabrica, $this->_os, $conn);
        
    }

    public function abreAtendimento(){

    	if(empty($this->os)){

    		throw new \Exception("Informe a Ordem de Serviço para abrir o Atendimento");

    	}

    	$pdo = $this->_model->getPDO(); 

    	$sql_dados_os = "
        SELECT
            tbl_os.sua_os,
            tbl_os.posto,
            tbl_os.data_abertura,
            tbl_os.data_nf,
            tbl_os.consumidor_nome,
            tbl_os.consumidor_cpf,
            tbl_os.consumidor_endereco,
            tbl_os.consumidor_numero,
            tbl_os.consumidor_cep,
            tbl_os.consumidor_complemento,
            tbl_os.consumidor_bairro,
            tbl_os.consumidor_cidade,
            tbl_os.consumidor_estado,
            tbl_os.consumidor_fone,
            tbl_os.revenda_cnpj,
            tbl_os.revenda_nome,
            tbl_os.revenda_fone,
            tbl_os.defeito_reclamado_descricao,
            tbl_os.revenda,
            tbl_os.consumidor_revenda,
            tbl_os.tipo_atendimento,
            tbl_os.nota_fiscal,
            tbl_os.data_nf,
            regexp_replace(tbl_os.obs,'\\s+',' ', 'g') as obs,
            tbl_posto.nome AS posto_nome,
            tbl_posto_fabrica.codigo_posto
            FROM tbl_os
            JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$this->fabrica}
            WHERE tbl_os.os  = {$this->os}";    		
    	$query = $pdo->prepare($sql_dados_os);

    	if (!$query->execute()) {
    		throw new \Exception("Ordem de Serviço {$this->os} não encontrada");
    	}else{

		        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

		        foreach ($res as $key) {
		        	$sua_os                      = $key['sua_os'];
			        $posto                       = $key['posto'];
			        $data_abertura               = $key['data_abertura'];
			        $data_nf                     = $key['data_nf'];
			        $consumidor_nome             = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['consumidor_nome']));
			        $consumidor_cpf              = $key['consumidor_cpf'];
			        $consumidor_endereco         = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['consumidor_endereco']));
			        $consumidor_endereco         = str_replace('\\','' ,$consumidor_endereco);
			        $consumidor_numero           = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['consumidor_numero']));
			        $consumidor_cep              = pg_fetch_result($res_dados_os, 0, 'consumidor_cep');
			        $consumidor_complemento      = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['consumidor_complemento']));
			        $consumidor_bairro           = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['consumidor_bairro']));
			        $consumidor_cidade           = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['consumidor_cidade']));
			        $consumidor_estado           = $key['consumidor_estado'];
			        $consumidor_fone             = $key['consumidor_fone'];
			        $consumidor_email            = $key['consumidor_email'];
			        $revenda_cnpj                = $key['revenda_cnpj');
			        $revenda_nome                = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['revenda_nome']));
			        $revenda_fone                = $key['revenda_fone'];
			        $defeito_reclamado_descricao = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['defeito_reclamado_descricao']));
			        $revenda                     = $key['revenda'];
			        $consumidor_revenda          = $key['consumidor_revenda'];
			        $nota_fiscal                 = $key['nota_fiscal'];
			        $data_nf                 	 = $key['data_nf'];
			        $tipo_atendimento            = $key['tipo_atendimento'];
			        $cod_ibge                    = $key['cod_ibge'];
			        $obs                         = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['obs']));
			        $posto_nome                  = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['posto_nome']));
			        $codigo_posto                = $key['codigo_posto'];
		        }
		        
		        if(!empty($cod_ibge)){
		            $sql_cidade = "SELECT cidade
		                           FROM tbl_cidade
		                           WHERE UPPER(fn_retira_especiais(nome)) = (SELECT UPPER(fn_retira_especiais(cidade)) FROM tbl_ibge WHERE cod_ibge = $cod_ibge) AND UPPER(estado) = (SELECT UPPER(estado) FROM tbl_ibge WHERE cod_ibge = $cod_ibge)";
		            $query = $pdo->prepare($sql_cidade);

		            if($query->execute()){
			            $res_cidade = $query->fetchAll(\PDO::FETCH_ASSOC);

			            if(count($res_cidade) > 0){
			                $cod_ibge = $res_cidade[0]['cidade'];
			            }else{
			                $cod_ibge = "null";
			            }
			        }else{
			            $cod_ibge = "null";
			        }

		        }else{
		            $cod_ibge = "null";
		        }

				$sql_os = "SELECT hd_chamado FROM tbl_hd_chamado_extra WHERE os = $os";
				$query = $pdo->prepare($sql_os);

				if($query->execute()){

					$res_os = $query->fetchAll(\PDO::FETCH_ASSOC);

					if(count($res_os) == 0) {

						$sql_abre_chamado = "
							INSERT INTO tbl_hd_chamado
							(
								posto,
								titulo,
								status,
								atendente,
								categoria,
								admin,
								fabrica_responsavel,
								fabrica
							)
							VALUES
							(
								$posto,
								'Atendimento interativo',
								'Aberto',
								$login_admin,
								'reclamacao_produto',
								$login_admin,
								$login_fabrica,
								$login_fabrica
							) RETURNING hd_chamado
							";
						$query = $pdo->prepare($sql_abre_chamado);

						if(!$query->execute()){
							throw new \Exception("Falha ao abrir atendimento #1");
						}

						$res_abre_chamado = $query->fetchAll(\PDO::FETCH_ASSOC);

						$hd_chamado = $res_abre_chamado[0]['hd_chamado'];

						if(!empty($hd_chamado)){

							$sql = "UPDATE tbl_os SET hd_chamado = $hd_chamado WHERE os = $os";
							$query = $pdo->prepare($sql);

							if(!$query->execute()){
								throw new \Exception("Falha ao abrir atendimento #2");
							}

							$sql_extra = "
								INSERT INTO tbl_hd_chamado_extra
								(
									hd_chamado,
									produto,
									revenda_nome,
									posto,
									os,
									serie,
									data_nf,
									nota_fiscal,
									defeito_reclamado_descricao,
									nome,
									endereco,
									numero,
									complemento,
									bairro,
									cep,
									fone,
									email,
									cpf,
									cidade,
									revenda_cnpj
								)
								VALUES
								(
									$hd_chamado,
									$produto,
									'$revenda_nome',
									$posto,
									$os,
									'$serie',
									'$data_nf',
									'$nota_fiscal',
									'$defeito_reclamado_descricao',
									'$consumidor_nome',
									'$consumidor_endereco',
									'$consumidor_numero',
									'$consumidor_complemento',
									'$consumidor_bairro',
									'$consumidor_cep',
									'$consumidor_fone',
									'$consumidor_email',
									'$consumidor_cpf',
									$cod_ibge,
									'$revenda_cnpj'
								)
								";
							$query = $pdo->prepare($sql_extra);

							if(!$query->execute()){
								throw new \Exception("Falha ao abrir atendimento #3");
							}else{

								$sqlOsProduto = "SELECT produto,serie FROM tbl_os_produto WHERE os = {$this->os}";
								$query = $pdo->prepare($sqlOsProduto);

								if(!$query->execute()){
									throw new \Exception("Falha ao abrir atendimento #4");
								}

								$resOsProduto = $query->fetchAll(\PDO::FETCH_ASSOC);

								if(count($resOsProduto) > 0){

									foreach ($resOsProduto as $key) {
										
										$produto = $key['produto'];
										$serie   = $key['serie'];

										$sql_hd_item = "INSERT INTO tbl_hd_chamado_item(
								                            hd_chamado          ,
								                            data                ,
								                            interno             ,
								                            status_item         ,
								                            produto             ,
								                            serie               ,
								                            nota_fiscal         ,
								                            data_nf            
								                        ) values (
								                            $hd_chamado         ,
								                            current_timestamp   ,
								                            't'  				,
								                            'Aberto'            ,
								                            {$produto}          ,
								                            '{$serie}'          ,
								                            '{$nota_fiscal}'    ,
								                            '{$data_nf}'
								                        )";
								        $query = $pdo->prepare($sql_hd_item);

								        if(!$query->execute()){
											throw new \Exception("Falha ao abrir atendimento #5");
										}
									}

								}

							}

						}else{
							throw new \Exception("Falha ao abrir atendimento #6");
						}
					}

				}
		    
    	}

    	

    }

    public function tira_acentos ($texto) {
        $acentos = array(
            "com" => "áâàãäéêèëíîìïóôòõúùüçñÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇÑ",
            "sem" => "aaaaaeeeeiiiioooouuucnAAAAAEEEEIIIIOOOOUUUCn"
        );
        return strtr($texto,$acentos['com'], $acentos['sem']);
    }

}