<?php
/**
 *
 * @author  Kaique
 * @version 2020.04.03
 *
*/
namespace Mirrors\Ri;

trait RiHelpers {

	public function formataCampos($request) {

		if ($request["codigo_aba"] == "posvenda") {

			$request["ri"]["data_abertura"] = formata_data($request["ri"]["data_abertura"]);
	        $request["ri"]["data_chegada"]  = formata_data($request["ri"]["data_chegada"]); 

	        $request["ri_posvenda"]["custo_peca"]  = str_replace(",", ".", str_replace(".", "", $request["ri_posvenda"]["custo_peca"]));
			$request["ri_posvenda"]["valor_frete"] = str_replace(",", ".", str_replace(".", "", $request["ri_posvenda"]["valor_frete"]));
			$request["ri_posvenda"]["mao_de_obra"] = str_replace(",", ".", str_replace(".", "", $request["ri_posvenda"]["mao_de_obra"]));
			$request["ri_posvenda"]["total"] 	   = str_replace(",", ".", str_replace(".", "", $request["ri_posvenda"]["total"]));

		}

		if ($request["codigo_aba"] == "acao_contencao") {

			$request["ri_analise"]["acao_contencao_data"] = formata_data($request["ri_analise"]["acao_contencao_data"]);

		}

        if ($request["codigo_aba"] == "implementacao_acoes") {

            $request["ri_acoes_corretivas"]["implementacao_permanente_data"] = formata_data($request["ri_acoes_corretivas"]["implementacao_permanente_data"]);

        }

        if ($request["codigo_aba"] == "eficacia_acoes") {

            $request["ri_acoes_corretivas"]["verificacao_eficacia_data"] = formata_data($request["ri_acoes_corretivas"]["verificacao_eficacia_data"]);

        }


		if (!empty($request["ri_posvenda"]["id"])) {
			unset($request['anexo_chave']);
		}

		unset($request["lupa_config"], $request["token_form"], $request['ri_id']);

		return $request;

	}

	public function camelToSnakeRecursivo($request) {

		$retorno = [];
		foreach ($request as $chave => $valor) {

			$novaChave = ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $chave )), '_');

			$retorno[$novaChave] = is_array($valor) ? $this->camelToSnakeRecursivo($valor) : utf8_decode($valor);

		}

		return $retorno;

    }

    public function atualizaConfigAbas($request = []) {

    	if (count($this->_abasDefault) == 0) return;

        $this->adminPermissoesAbas();

    	//posvenda
    	if (!empty($request["ri"]["id"])) {

    		$this->concluirAba([
    			"atual"   => "posvenda",
    			"proxima" => "time_analise" 
    		]);

    	}

    	//analise
    	if (!empty($request["ri_analise"]["id"])) {

    		$this->concluirAba([
    			"atual"   => "time_analise",
    			"proxima" => "acao_contencao" 
    		]);

    	}

        //contencao
        if (!empty($request["ri_analise"]["acao_contencao"])) {

            $this->concluirAba([
                "atual"   => "acao_contencao",
                "proxima" => "causa_analise"
            ]);

        }

        //causa
        if (!empty($request["ri_analise"]["causa_raiz"])) {

            $this->concluirAba([
                "atual"   => "causa_analise",
                "proxima" => "identificacao_acoes"
            ]);

        }

        //identificacao
        if (!empty($request["ri_acoes_corretivas"]["id"])) {

            $this->concluirAba([
                "atual"   => "identificacao_acoes",
                "proxima" => "implementacao_acoes"
            ]);

        }

        //implementacao
        if (!empty($request["ri_acoes_corretivas"]["implementacao_permanente"])) {

            $this->concluirAba([
                "atual"   => "implementacao_acoes",
                "proxima" => "eficacia_acoes"
            ]);

        }

        //eficacia
        if (!empty($request["ri_acoes_corretivas"]["verificacao_eficacia"])) {

            $this->concluirAba([
                "atual"   => "eficacia_acoes",
                "proxima" => "conclusao"
            ]);

        }

        //conclusao
        if (!empty($request["ri"]["conclusao"])) {

            $this->concluirAba([
                "atual"   => "conclusao",
                "proxima" => null
            ]);

        }

        if ($request["ri_transferencia"]["status"] == "Finalizado") {

            foreach ($this->_abasDefault as $codigoAba => $config) {

                $this->_abasDefault[$codigoAba]["apenas_visualiza"] = true;

            }
            
        }

    	return $this->_abasDefault;

    }

    public function getFollowupByAdmin() {

        $model = new \Posvenda\Model\GenericModel;

        $pdo = $model->getPDO();

        $sql = "SELECT followup 
                FROM tbl_ri_grupo 
                WHERE admin = {$this->_admin}";
        $query = $pdo->query($sql);

        $retorno = $query->fetchAll(\PDO::FETCH_ASSOC);

        $arrFollowups = [];
        if (count($retorno) > 0) {

            foreach ($retorno as $key => $val) {

                $arrFollowups[] = $val["followup"];

            }

        }

        return $arrFollowups;

    }

    public function getEmailsByFollowup($followup) {

        $model = new \Posvenda\Model\GenericModel;

        $pdo = $model->getPDO();

        $sql = "SELECT tbl_admin.email
                FROM tbl_ri_grupo
                JOIN tbl_admin USING(admin) 
                WHERE followup = {$followup}";
        $query = $pdo->query($sql);

        $retorno = $query->fetchAll(\PDO::FETCH_ASSOC);

        $emails = [];
        if (count($retorno) > 0) {

            foreach ($retorno as $key => $val) {

                $emails[] = $val["email"];

            }

        }

        return $emails;

    }

    public function getAdminPermissoes() {

        $model = new \Posvenda\Model\GenericModel;

        $pdo = $model->getPDO();

        $sql = "SELECT parametros_adicionais FROM tbl_admin WHERE admin = {$this->_admin}";
        $query = $pdo->query($sql);

        $res = $query->fetch(\PDO::FETCH_ASSOC);

        return json_decode($res["parametros_adicionais"], true);

    }

    private function adminPermissoesAbas() {

        $model = new \Posvenda\Model\GenericModel;

        $pdo = $model->getPDO();

        $sqlEngenharia = "SELECT tbl_ri_followup.codigo
                          FROM tbl_ri_grupo
                          JOIN tbl_ri_followup ON tbl_ri_grupo.followup = tbl_ri_followup.ri_followup
                          WHERE tbl_ri_grupo.admin = {$this->_admin}";

        $query = $pdo->query($sqlEngenharia);

        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($res as $key => $dadosEngenharia) {

            $codigosFollowup[] = $dadosEngenharia["codigo"];

        }

        if (in_array("engservico", $codigosFollowup)) {

            $this->_abasDefault["posvenda"]["apenas_visualiza"]  = false;
            $this->_abasDefault["conclusao"]["apenas_visualiza"] = false;

        }

        if (in_array("qmanaus", $codigosFollowup) || in_array("qbss", $codigosFollowup) || in_array("qfg", $codigosFollowup)) {

            foreach ($this->_abasDefault as $codigoAba => $config) {

                if ($codigoAba != "posvenda") {

                    $this->_abasDefault[$codigoAba]["apenas_visualiza"] = false;

                }

            }

        }

    }

    private function concluirAba($abas) {

    	$this->_abasDefault[$abas['atual']]["status_preenchimento"] = "preenchida";
    	$this->_abasDefault[$abas['atual']]["ativa"] 				= false;

    	if (!empty($abas['proxima'])) {
    		$this->_abasDefault[$abas['proxima']]["status_preenchimento"] = "pendente";
    		$this->_abasDefault[$abas['proxima']]["ativa"] 				  = true;
    	} else {
            $this->_abasDefault["posvenda"]["ativa"] = true;
        }

    }

    public function utf8Ansi($valor='') {

        $utf8_ansi2 = array(
            "\u00c0" =>"À",
            "\u00c1" =>"Á",
            "\u00c2" =>"Â",
            "\u00c3" =>"Ã",
            "\u00c4" =>"Ä",
            "\u00c5" =>"Å",
            "\u00c6" =>"Æ",
            "\u00c7" =>"Ç",
            "\u00c8" =>"È",
            "\u00c9" =>"É",
            "\u00ca" =>"Ê",
            "\u00cb" =>"Ë",
            "\u00cc" =>"Ì",
            "\u00cd" =>"Í",
            "\u00ce" =>"Î",
            "\u00cf" =>"Ï",
            "\u00d1" =>"Ñ",
            "\u00d2" =>"Ò",
            "\u00d3" =>"Ó",
            "\u00d4" =>"Ô",
            "\u00d5" =>"Õ",
            "\u00d6" =>"Ö",
            "\u00d8" =>"Ø",
            "\u00d9" =>"Ù",
            "\u00da" =>"Ú",
            "\u00db" =>"Û",
            "\u00dc" =>"Ü",
            "\u00dd" =>"Ý",
            "\u00df" =>"ß",
            "\u00e0" =>"à",
            "\u00e1" =>"á",
            "\u00e2" =>"â",
            "\u00e3" =>"ã",
            "\u00e4" =>"ä",
            "\u00e5" =>"å",
            "\u00e6" =>"æ",
            "\u00e7" =>"ç",
            "\u00e8" =>"è",
            "\u00e9" =>"é",
            "\u00ea" =>"ê",
            "\u00eb" =>"ë",
            "\u00ec" =>"ì",
            "\u00ed" =>"í",
            "\u00ee" =>"î",
            "\u00ef" =>"ï",
            "\u00f0" =>"ð",
            "\u00f1" =>"ñ",
            "\u00f2" =>"ò",
            "\u00f3" =>"ó",
            "\u00f4" =>"ô",
            "\u00f5" =>"õ",
            "\u00f6" =>"ö",
            "\u00f8" =>"ø",
            "\u00f9" =>"ù",
            "\u00fa" =>"ú",
            "\u00fb" =>"û",
            "\u00fc" =>"ü",
            "\u00fd" =>"ý",
            "\u00ff" =>"ÿ"
        );

        return strtr($valor, $utf8_ansi2);      

    }

}