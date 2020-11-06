<?php

	/* Regras de valores de extrato para a Mondial */

	class RegrasExtrato{

		private $_fabrica;
		private $_classExtrato;

		public function __construct($classExtrato, $fabrica = ""){
			$this->_classExtrato = $classExtrato;
			$this->_fabrica = $fabrica;
		}

		public function run(){
			echo "regras de extrato para a fabrica ".$this->_fabrica." ativa...";
		}

		public function RegraOSDez($extrato, $fabrica){
			/*
			* Regra para atualizar tbl_os.mao_de_obra para R$ 10,00 
			* São O.S de laudo zero hora e produto com troca Obrigatória.
			*/
			$pdo = $this->_classExtrato->_model->getPDO();

			$sql = "SELECT 
						tbl_os.os, 
						tbl_os.tipo_atendimento, 
						tbl_produto.produto, 
						tbl_produto.troca_obrigatoria
					FROM tbl_os_extra 
					INNER JOIN tbl_os on tbl_os.os = tbl_os_extra.os
					INNER JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
					WHERE extrato  = $extrato 
					AND tbl_os.fabrica = $fabrica";
			$query = $pdo->query($sql);
			$qtd   = $query->rowCount();

			if($qtd > 0){
				$res    = $query->fetchAll(\PDO::FETCH_ASSOC);
				for($i=0; $i< $qtd ; $i++){
					$os 				= $res[$i]['os'];
					$troca_obrigatoria 	= $res[$i]['troca_obrigatoria'];
					$tipo_atendimento 	= $res[$i]['tipo_atendimento'];

					if($tipo_atendimento == "243" or $troca_obrigatoria == 1){
						$sql_upd = "UPDATE tbl_os SET mao_de_obra = '10.00' WHERE os = $os";
						$query  = $pdo->query($sql_upd);
					}
				}
			}			
		}

		public function verificaLGR($extrato = "", $posto = "", $data_15 = "", $fabrica = "", $lgr_troca_produto = false){

	        if(empty($extrato)){
	            $desc_posto = (!empty($posto)) ? "- Posto : {$posto}" : "";
	            throw new \Exception("Extrato não informado para a verificação de LGR {$desc_posto}");
	        }

	        if(empty($posto)){
	            throw new \Exception("Posto não informado para a verificação de LGR - Extrato : {$extrato}");
	        }

	        if(empty($data_15)){
	            throw new \Exception("Período de geração não informado para a verificação de LGR - Extrato : {$extrato}");
	        }

	        if(empty($fabrica)){
	            $fabrica = $this->_fabrica;
	        }

	        $pdo = $this->_classExtrato->_model->getPDO();

	        /* 1 */

	        // if ($lgr_troca_produto == true) {
	        //      $sql = "UPDATE tbl_faturamento_item SET
	        //             extrato_devolucao = $extrato
	        //             FROM tbl_os_item,tbl_faturamento,tbl_extrato, tbl_peca
	        //             WHERE tbl_os_item.os_item = tbl_faturamento_item.os_item
	        //             AND tbl_faturamento.posto = tbl_extrato.posto
	        //             AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
	        //             AND tbl_faturamento.fabrica in(10, $fabrica)
	        //             AND tbl_faturamento.emissao >='2010-01-01'
	        //             AND tbl_faturamento.emissao <='$data_15'
	        //             AND tbl_faturamento.cancelada IS NULL
	        //             AND tbl_faturamento_item.extrato_devolucao IS NULL
	        //             AND tbl_peca.peca = tbl_os_item.peca
	        //             AND (tbl_os_item.peca_obrigatoria OR tbl_peca.produto_acabado IS TRUE)
	        //             AND tbl_os_item.fabrica_i=$fabrica
	        //             AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
	        //             AND tbl_extrato.extrato = $extrato";
	        // } else {
	            $sql = "UPDATE tbl_faturamento_item SET
	                    extrato_devolucao = $extrato
	                    FROM tbl_os_item,tbl_faturamento,tbl_extrato, tbl_peca
	                    WHERE tbl_os_item.os_item = tbl_faturamento_item.os_item
	                    AND tbl_faturamento.posto = tbl_extrato.posto
	                    AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
	                    AND tbl_faturamento.fabrica in(10, $fabrica)
	                    AND tbl_faturamento.emissao >='2010-01-01'
	                    AND tbl_faturamento.emissao <='$data_15'
	                    AND tbl_faturamento.cancelada IS NULL
	                    AND tbl_faturamento_item.extrato_devolucao IS NULL
	                    AND (tbl_os_item.peca_obrigatoria OR tbl_peca.produto_acabado IS TRUE)
	                    AND tbl_os_item.fabrica_i=$fabrica
	                    AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
	                    AND tbl_extrato.extrato = $extrato";
	        // }
	        $query  = $pdo->query($sql);

	        if(!$query){
	            $this->_erro = $pdo->errorInfo();
	            throw new \Exception("Erro ao verificar o LGR do extrato {$extrato} - /* SQL 1 */");
	        }

	        /* 2 */

	       $sql = "UPDATE tbl_faturamento SET extrato_devolucao = $extrato
	                FROM tbl_faturamento_item
	                WHERE tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
	                AND tbl_faturamento.posto = $posto
	                AND tbl_faturamento.fabrica in(10, $fabrica)
	                AND tbl_faturamento.emissao >='2010-01-01'
	                AND tbl_faturamento.emissao <='$data_15'
	                AND tbl_faturamento_item.extrato_devolucao = $extrato";
	        $query  = $pdo->query($sql);

	        if(!$query){
	            $this->_erro = $pdo->errorInfo();
	            throw new \Exception("Erro ao verificar o LGR do extrato {$extrato} - /* SQL 2 */");
	        }

	        /* 3 */

	       $sql = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde)
                SELECT
	                tbl_extrato.extrato,
	                tbl_extrato.posto,
	                tbl_faturamento_item.peca,
	                SUM (tbl_faturamento_item.qtde)
                FROM tbl_extrato
                	JOIN tbl_faturamento_item ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao
                	JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
                		AND tbl_peca.produto_acabado IS NOT TRUE
                WHERE tbl_extrato.fabrica = $fabrica
                	AND tbl_extrato.extrato = $extrato
                GROUP BY tbl_extrato.extrato,
                	tbl_extrato.posto,
                	tbl_faturamento_item.peca";
	        $query  = $pdo->query($sql);

	        if(!$query){
	            $this->_erro = $pdo->errorInfo();
	            throw new \Exception("Erro ao verificar o LGR do extrato {$extrato} - /* SQL 3 */");
	        }

	        /* TROCA DE PRODUTO */
	        $sql = "SELECT 
	        		tbl_extrato.extrato, 
	        		tbl_extrato.posto, 
	        		tbl_peca.peca
        		FROM tbl_extrato 
        			INNER JOIN (
        				SELECT extrato_devolucao, os FROM tbl_faturamento_item 
        					INNER JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
        						AND tbl_peca.fabrica = $fabrica
        						AND tbl_peca.produto_acabado IS TRUE
						WHERE tbl_faturamento_item.extrato_devolucao = $extrato
        			)AS tbl_faturamento_item ON tbl_faturamento_item.extrato_devolucao = tbl_extrato.extrato
        			INNER JOIN tbl_os ON tbl_os.os = tbl_faturamento_item.os 
        			INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto 
        			INNER JOIN tbl_peca ON tbl_peca.referencia = tbl_produto.referencia 
        				AND tbl_peca.descricao = tbl_produto.descricao
        				AND tbl_peca.fabrica = $fabrica 
    				INNER JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os
    					AND tbl_os_troca.ressarcimento IS NOT TRUE
				WHERE tbl_extrato.fabrica = $fabrica 
					AND tbl_extrato.extrato = $extrato";
			$query = $pdo->query($sql);
			$qtd   = $query->rowCount();

			if($qtd > 0){
				$res = $query->fetchAll(\PDO::FETCH_ASSOC);

				foreach ($res as $key => $value) {
					if(empty($value["peca"]) || $value["peca"] == NULL || $value["peca"] == 0){
						$sql = "INSERT INTO tbl_peca
								(fabrica, referencia, descricao, ipi, origem, produto_acabado)
								SELECT 
									153 AS fabrica, 
									referencia, 
									descricao, 
									(CASE WHEN ipi IS NULL THEN 0 ELSE ipi END) AS IPI, 
									'NAC' AS origem, 
									true AS PRODUTO_ACABADO 
								FROM tbl_produto 
								WHERE produto = ".$value["produto"]."
							RETURNING peca";
						$query  = $pdo->query($sql);

					 	if(!$query){
				            $this->_erro = $pdo->errorInfo();
				            throw new \Exception("Erro ao cadastrar peça do LGR do extrato {$extrato} - /* SQL 3 */");
				        }else{
				        	$value["peca"] = $query->fetch(\PDO::FETCH_ASSOC);
				        }

					}

			        $sql = " INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde)
		                VALUES ($extrato, ".$value['posto'].", ".$value['peca'].", 1)";
			        $query  = $pdo->query($sql);
			        if(!$query){
			            break;
			        }
			    }

			    if(!$query){
		            $this->_erro = $pdo->errorInfo();
		            throw new \Exception("Erro ao registrar LGR de Troca de Produto do extrato {$extrato} - /* SQL 3 */");
		        }
		    }
	    }

	    function verificarTotalPeca($extrato = "", $posto = "", $data_15 = "", $fabrica = ""){
            $fabrica = $this->_fabrica;

	        $pdo = $this->_classExtrato->_model->getPDO();

	        $sql = "SELECT SUM(tbl_faturamento_item.preco*tbl_faturamento_item.qtde) AS total_peca
				FROM tbl_faturamento
					JOIN tbl_faturamento_item using(faturamento)
					JOIN tbl_os_item using(os_item)
					JOIN tbl_extrato using(posto) 
				WHERE tbl_faturamento.fabrica in(10, $fabrica)
				AND tbl_faturamento.emissao >='2010-01-01'
				AND tbl_faturamento.emissao <='$data_15'
				AND tbl_faturamento.cancelada IS NULL
				AND tbl_faturamento_item.extrato_devolucao IS NULL
				AND tbl_os_item.peca_obrigatoria
				AND tbl_os_item.fabrica_i = $fabrica
				AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
				AND tbl_extrato.extrato = $extrato";
			$query = $pdo->query($sql);
			$qtd   = $query->rowCount();
		
			if($qtd > 0){
				$res = $query->fetchAll(\PDO::FETCH_ASSOC);

				if((float) $res[0]["total_peca"] >= 50.0){
					return true;
				}else{
					return false;
				}
			}
	    }
	}
?>