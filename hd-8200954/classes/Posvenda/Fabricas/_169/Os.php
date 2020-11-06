<?php
namespace Posvenda\Fabricas\_169;

use Posvenda\Model\Os as OsModel;
use Posvenda\Os as OsPosvenda;
use Posvenda\Regras;

require_once dirname(__FILE__) . '/../../../../class/tdocs.class.php';

class Os extends OsPosvenda
{
    private $_fabrica;
    private $_serverEnvironment;
    private $_urlWSDL;
    private $_TDocs;

    public function __construct($fabrica, $os = null, $conn = null)
    {

        include "/etc/telecontrol.cfg";

        $this->_fabrica = $fabrica;
        parent::__construct($fabrica, $os, $conn);
		$this->_serverEnvironment = $_serverEnvironment;

        if ($this->_serverEnvironment == 'development') {
            $this->_urlWSDL = "http://ws.carrieronline.com.br/QA6/PSA_WebService/PSA.asmx?WSDL";
        } else {
            $this->_urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/PSA.asmx?WSDL";
        }

	if($conn) {
            $this->_TDocs = new \TDocs($conn, $fabrica);
            $this->_TDocs->setContext('fabrica', 'log');
        }

    }

    public function finaliza($con, $troca_produto_api = false, $login_admin = null, $origem = null)
    {
        global $data_fechamento;
    
	if(empty($data_fechamento)){
            $data_fechamento = date("Y-m-d");
	} else if (strpos($data_fechamento, '/') !== false) {
	    list($df, $mf, $yf) = explode("/", $data_fechamento);
	    $data_fechamento = "$yf-$mf-$df";
	}

        if (empty($this->_os)) {
            throw new \Exception("Ordem de Serviço não informada");
        }

        $sqlVerAgendamento = "
	    SELECT
		tbl_tecnico_agenda.data_agendamento,
		tbl_tecnico_agenda.confirmado 
            FROM tbl_tecnico_agenda
            WHERE tbl_tecnico_agenda.os = ".$this->_os."
            AND tbl_tecnico_agenda.fabrica =  ".$this->_fabrica."
            AND confirmado IS NOT NULL
            ORDER BY tbl_tecnico_agenda.tecnico_agenda DESC
	    LIMIT 1;
	";
        $resVerAgendamento = pg_query($con, $sqlVerAgendamento);

        if(pg_num_rows($resVerAgendamento) > 0) {
            $data_agendamento = pg_fetch_result($resVerAgendamento, 0, 'data_agendamento');
            if(strtotime($data_agendamento) > strtotime($data_fechamento)) {
                throw new \Exception("Não é possível fechar a OS com data de agendamento futura. Para fechar ajuste a data de agendamento.");
            }
        }

        $sql_defeito = "SELECT
                            o.os
                        FROM tbl_os o
                        JOIN tbl_os_produto op USING(os)
                        LEFT JOIN tbl_os_item oi USING(os_produto)
						LEFT JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = oi.pedido_item
						JOIN tbl_os_defeito_reclamado_constatado odrc USING(os,fabrica)
						JOIN tbl_defeito_constatado on odrc.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                        LEFT JOIN tbl_diagnostico d ON d.defeito_constatado = odrc.defeito_constatado AND d.fabrica = {$this->_fabrica}
                        LEFT JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND (sr.gera_pedido IS TRUE OR sr.troca_produto IS TRUE OR sr.ativo IS TRUE) AND sr.fabrica = {$this->_fabrica}
                        WHERE o.os = {$this->_os}
                        AND (d.defeito = odrc.defeito
                        OR (d.defeito = oi.defeito and (tbl_pedido_item.qtde_faturada > 0 or (sr.gera_pedido is false and sr.troca_produto is false and oi.pedido isnull))) OR tbl_defeito_constatado.lista_garantia='sem_defeito')";
        $res_defeito = pg_query($con, $sql_defeito);
        if (pg_num_rows($res_defeito) == 0) {
            throw new \Exception("{$this->_sua_os} - Verificar o defeito constatado e defeito da peça na ordem de serviço");
        } else {
            $sql = "
                SELECT
                    tbl_tipo_atendimento.fora_garantia,
                    '2019-05-29' - tbl_os.data_digitacao::date AS data_corte_valida_tdocs,
                    tbl_os.cortesia,
                    tbl_tipo_atendimento.km_google,
                    tbl_tipo_atendimento.grupo_atendimento,
                    (
                        SELECT COUNT(odrc.defeito_constatado_reclamado)
                        FROM tbl_os_defeito_reclamado_constatado odrc
                        INNER JOIN tbl_defeito_constatado dc ON dc.defeito_constatado = odrc.defeito_constatado
                        WHERE odrc.os = tbl_os.os
                        AND (dc.lista_garantia = 'fora_garantia')
                    ) AS defeitos_fora_garantia
                FROM tbl_os
                    JOIN tbl_tipo_atendimento USING(tipo_atendimento)
                WHERE tbl_os.os = {$this->_os};
            ";
            $res = pg_query($con, $sql);

            $fora_garantia     = pg_fetch_result($res, 0, 'fora_garantia');
            $km_google         = pg_fetch_result($res, 0, 'km_google');
            $grupo_atendimento = pg_fetch_result($res, 0, 'grupo_atendimento');
            $defeitos_fora_garantia = pg_fetch_result($res, 0, 'defeitos_fora_garantia');
            $os_cortesia = pg_fetch_result($res, 0, 'cortesia');

            if ($fora_garantia != 't' && $defeitos_fora_garantia == 0 && $os_cortesia != 't') {
             
                // include_once __DIR__."/../../../../class/aws/anexaS3.class.php";
                // $s3_anexo_os = new \AmazonTC("os", $this->_fabrica);

                $data_corte_valida_tdocs = pg_fetch_result($res, 0, 'data_corte_valida_tdocs');
                // list($ano,$mes,$dia) = explode("-", $data_abertura_valida);
		    // $anexos = $s3_anexo_os->getObjectList("{$this->_os}_", false, $ano, $mes);

		$sql_anexo_fechamento = "SELECT  count(1) AS total_anexos FROM tbl_tdocs WHERE contexto = 'os' AND referencia_id = {$this->_sua_os} AND fabrica = {$this->_fabrica} AND situacao='ativo'"; 
		$res_anexo_fechamento = pg_query($con, $sql_anexo_fechamento);
		$total_anexos = pg_fetch_result($res_anexo_fechamento,0,'total_anexos');

		if($data_corte_valida_tdocs > 0){
			if ($grupo_atendimento == "P" && $km_google != "t"){
				if($total_anexos < 3){
					throw new \Exception("{$this->_sua_os} - É obrigatório anexar a nota fiscal do produto, foto do produto e a OS assinada pel consumidor");
				}
			}else if ($grupo_atendimento == "P" && $km_google == "t") {
				if($total_anexos < 2){
					throw new \Exception("{$this->_sua_os} - É obrigatório anexar a nota fiscal do produto e a OS assinada pelo consumidor");
				}
			}
		}else{
			$sql_anexo_fechamento = "SELECT  obs, tdocs FROM tbl_tdocs WHERE contexto = 'os' AND referencia_id = {$this->_sua_os} AND fabrica = {$this->_fabrica} AND situacao='ativo'";     

			$res_anexo_fechamento = pg_query($con, $sql_anexo_fechamento);


			while ($dados_anexos = pg_fetch_object($res_anexo_fechamento)) {
			  
			    //pegou json em formato de texto e convertou em um array
			    $array_obs_tdocs = json_decode($dados_anexos->obs,true);

			    $array_tipos_inseridos[] = $array_obs_tdocs[0]['typeId'];
			  
			}    
			  //exit(print_r($array_tipos_inseridos));
			if ($grupo_atendimento == "P" && $km_google != "t"){

			    if ((!in_array('notafiscal',$array_tipos_inseridos) || !in_array('produto',$array_tipos_inseridos) || !in_array('assinatura',$array_tipos_inseridos))) {
				throw new \Exception("{$this->_sua_os} - É obrigatório anexar a nota fiscal do produto, foto do produto e a OS assinada pel consumidor");
			    }
			    //if com nf e assinatura
			} else if ($grupo_atendimento == "P" && $km_google == "t") {

			    if ((!in_array('notafiscal',$array_tipos_inseridos) || !in_array('assinatura',$array_tipos_inseridos))) {
				throw new \Exception("{$this->_sua_os} - É obrigatório anexar a nota fiscal do produto e a OS assinada pelo consumidor");
			    }
			}
		}
            }
            parent::finaliza($con, $troca_produto_api, $login_admin, $origem);
        }
    }

    public function getDadosNotaExport($os)
    {
        $pdo = $this->_model->getPDO();

        $sql = "
            SELECT
                o.os,
		        o.sua_os,
                o.data_abertura,
                o.data_nf,
                o.nota_fiscal,
                o.consumidor_revenda,
                UPPER(fn_retira_especiais(o.consumidor_nome)) AS consumidor_nome,
                UPPER(fn_retira_especiais(o.consumidor_endereco)) AS consumidor_endereco,
                UPPER(fn_retira_especiais(o.consumidor_numero)) AS consumidor_numero,
                UPPER(fn_retira_especiais(o.consumidor_complemento)) AS consumidor_complemento,
                o.consumidor_cpf,
                UPPER(fn_retira_especiais(o.consumidor_bairro)) AS consumidor_bairro,
                UPPER(fn_retira_especiais(o.consumidor_cidade)) AS consumidor_cidade,
                o.consumidor_estado,
                o.consumidor_fone AS consumidor_telefone,
                o.consumidor_celular,
                o.consumidor_cep,
                o.consumidor_email,
                o.importacao_fabrica,
                tao.grupo_atendimento,
                CASE WHEN r.nome IS NULL OR LENGTH(r.nome) = 0 THEN UPPER(fn_retira_especiais(o.revenda_nome)) ELSE UPPER(fn_retira_especiais(r.nome)) END AS revenda_nome,
                UPPER(fn_retira_especiais(r.endereco)) AS revenda_endereco,
                UPPER(fn_retira_especiais(r.numero)) AS revenda_numero,
                UPPER(fn_retira_especiais(r.complemento)) AS revenda_complemento,
                UPPER(fn_retira_especiais(r.bairro)) AS revenda_bairro,
                r.cep AS revenda_cep,
                CASE WHEN r.cnpj IS NULL OR LENGTH(r.cnpj) = 0 THEN o.revenda_cnpj ELSE r.cnpj END AS revenda_cnpj,
                r.fone AS revenda_telefone,
                r.email AS revenda_email,
                UPPER(fn_retira_especiais(cr.nome)) AS revenda_cidade,
                cr.estado AS revenda_estado,
                op.produto,
                CASE WHEN op.serie = pf.codigo_posto THEN op.serie||o.os ELSE op.serie END AS serie,
                pf.posto,
                pf.codigo_posto AS posto_codigo,
                CASE WHEN tao.km_google = 'f' THEN o.data_abertura ELSE ta.data_agendamento END AS data_agendamento,
                p.referencia AS produto_referencia,
                p.descricao AS produto_descricao,
                dr.codigo AS defeito_reclamado_codigo,
                dr.descricao AS defeito_reclamado_descricao,
                ta.periodo
            FROM tbl_os o
            JOIN tbl_os_produto op ON op.os = o.os
            JOIN tbl_tipo_atendimento tao ON tao.tipo_atendimento = o.tipo_atendimento AND tao.fabrica = :fabrica
            JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = :fabrica
            JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = :fabrica
            LEFT JOIN tbl_defeito_reclamado dr ON dr.defeito_reclamado = o.defeito_reclamado AND dr.fabrica = :fabrica
            LEFT JOIN (
                SELECT
                    ta.data_agendamento,
                    ta.os,
                    ta.periodo
                FROM tbl_tecnico_agenda ta
                LEFT JOIN tbl_tecnico t USING(tecnico)
                WHERE ta.fabrica = :fabrica
                AND ta.os = :os
                AND ta.confirmado IS NOT NULL
                ORDER BY ta.data_input DESC
                LIMIT 1
            ) ta ON ta.os = o.os
            LEFT JOIN tbl_revenda r ON r.revenda = o.revenda
            LEFT JOIN tbl_cidade cr ON cr.cidade = r.cidade
            WHERE o.fabrica = :fabrica
            AND o.os = :os;
        ";
        $query = $pdo->prepare($sql);

        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
        $query->bindParam(':os', $os, \PDO::PARAM_INT);

        if (!$query->execute() || $query->rowCount() == 0) {
            throw new \Exception("TC/OS-GDNE-001");
        }

        $res = $query->fetch(\PDO::FETCH_ASSOC);

        return $res;
    }

    public function getDadosOSExport($os)
    {
        $pdo = $this->_model->getPDO();

        $sql = "
	       SELECT DISTINCT
                o.os,
		        o.sua_os,
                o.data_abertura,
                o.data_fechamento,
                o.consumidor_revenda,
                o.data_nf,
                o.nota_fiscal,
                UPPER(fn_retira_especiais(o.consumidor_nome)) AS consumidor_nome,
                UPPER(fn_retira_especiais(o.consumidor_endereco)) AS consumidor_endereco,
                UPPER(fn_retira_especiais(o.consumidor_numero)) AS consumidor_numero,
                o.consumidor_cep,
                UPPER(fn_retira_especiais(o.consumidor_cidade)) AS consumidor_cidade,
                o.consumidor_estado,
                o.consumidor_fone AS consumidor_telefone,
                o.consumidor_celular,
                o.consumidor_email,
                p.mao_de_obra,
                o.qtde_km_calculada,
                o.valores_adicionais,
                o.obs || '\nObservações do Callcenter:\n' || UPPER(fn_retira_especiais(o.observacao)) AS obs,
                o.os_posto,
                CASE WHEN r.nome IS NULL OR LENGTH(r.nome) = 0 THEN UPPER(fn_retira_especiais(o.revenda_nome)) ELSE UPPER(fn_retira_especiais(r.nome)) END AS revenda_nome,
                UPPER(fn_retira_especiais(r.endereco)) AS revenda_endereco,
                UPPER(fn_retira_especiais(r.numero)) AS revenda_numero,
                UPPER(fn_retira_especiais(r.complemento)) AS revenda_complemento,
                UPPER(fn_retira_especiais(r.bairro)) AS revenda_bairro,
                r.cep AS revenda_cep,
                CASE WHEN r.cnpj IS NULL OR LENGTH(r.cnpj) = 0 THEN o.revenda_cnpj ELSE r.cnpj END AS revenda_cnpj,
                r.fone AS revenda_telefone,
                r.email AS revenda_email,
                UPPER(fn_retira_especiais(cr.nome)) AS revenda_cidade,
                cr.estado AS revenda_estado,
                CASE WHEN tao.km_google = 'f'
                    THEN
                        o.data_abertura
                    ELSE (
                        SELECT
                            tbl_tecnico_agenda.data_agendamento
                        FROM tbl_tecnico_agenda
                        WHERE tbl_tecnico_agenda.os = :os
                        AND tbl_tecnico_agenda.confirmado IS NOT NULL
                        AND tbl_tecnico_agenda.fabrica = :fabrica
                        ORDER BY tbl_tecnico_agenda.data_input DESC
                        LIMIT 1
                    )
                END AS data_agendamento,
                tao.km_google,
                tao.grupo_atendimento,
                CASE WHEN UPPER(op.serie) = UPPER(pf.codigo_posto) THEN op.serie||o.os ELSE op.serie END AS serie,
                pf.posto,
                pf.codigo_posto AS posto_codigo,
                p.produto,
                p.referencia AS produto_referencia,
                p.descricao AS produto_descricao,
                f.codigo_validacao_serie AS rpi,
                l.nome AS linha_descricao,
                dc.defeito_constatado AS defeito_constatado_id,
                CASE WHEN dc.lista_garantia = 'fora_garantia' THEN 'SDC' ELSE dc.codigo END AS defeito_constatado_codigo,
                CASE WHEN dc.lista_garantia = 'fora_garantia' THEN 'Sem defeito constatado' ELSE dc.descricao END AS defeito_constatado_descricao,
                d.defeito AS defeito_peca_id,
                d.codigo_defeito AS defeito_peca_codigo,
                d.descricao AS defeito_peca_descricao,
                oi.os_item,
                pc.peca AS peca_id,
                pc.referencia AS peca_referencia,
                (oi.qtde - COALESCE(pi.qtde_cancelada, 0)) AS peca_qtde,
                sr.troca_de_peca,
		sr.ativo AS servico_ativo,
                sr.peca_estoque
            FROM tbl_os o
            JOIN tbl_os_produto op ON op.os = o.os
            JOIN tbl_tipo_atendimento tao ON tao.tipo_atendimento = o.tipo_atendimento AND tao.fabrica = :fabrica
            JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = :fabrica
            JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = :fabrica
            JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = :fabrica
            JOIN tbl_os_defeito_reclamado_constatado odrc ON odrc.os = o.os AND odrc.fabrica = :fabrica
            JOIN tbl_diagnostico di_cons ON di_cons.defeito_constatado = odrc.defeito_constatado AND di_cons.defeito IS NOT NULL AND di_cons.fabrica = :fabrica
            LEFT JOIN tbl_linha l ON l.linha = p.linha AND l.fabrica = :fabrica
            LEFT JOIN tbl_servico_realizado sr ON sr.fabrica = :fabrica
            LEFT JOIN tbl_os_item oi ON oi.os_produto = op.os_produto AND oi.servico_realizado = sr.servico_realizado
            LEFT JOIN tbl_pedido_item pi ON pi.pedido_item = oi.pedido_item
            LEFT JOIN tbl_peca pc ON pc.peca = oi.peca AND pc.fabrica = :fabrica
            LEFT JOIN tbl_diagnostico di_peca ON di_peca.defeito = oi.defeito AND di_peca.defeito_constatado IS NOT NULL AND di_peca.fabrica = :fabrica
            LEFT JOIN tbl_defeito d ON (d.defeito = di_peca.defeito OR d.defeito = odrc.defeito) AND d.fabrica = :fabrica
            LEFT JOIN tbl_defeito_constatado dc ON dc.defeito_constatado = di_cons.defeito_constatado AND dc.fabrica = :fabrica
            LEFT JOIN tbl_revenda r ON r.revenda = o.revenda
            LEFT JOIN tbl_cidade cr ON cr.cidade = r.cidade
            WHERE o.fabrica = :fabrica
            AND o.os = :os
            AND di_cons.defeito_constatado IS NOT NULL
            AND (di_peca.defeito IS NULL
            OR di_cons.defeito = di_peca.defeito)
            AND (oi.os_item IS NULL
            OR (sr.gera_pedido IS TRUE AND oi.qtde > COALESCE(pi.qtde_faturada, 0) + COALESCE(pi.qtde_cancelada, 0))
            OR (sr.gera_pedido IS TRUE AND COALESCE(pi.qtde_faturada, 0) > 0)
            OR (oi.os_item IS NOT NULL AND ((sr.ativo IS TRUE AND sr.gera_pedido IS NOT TRUE) OR sr.ativo IS NOT TRUE)))
            AND o.excluida IS NOT TRUE;
        ";
        $query = $pdo->prepare($sql);

        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
        $query->bindParam(':os', $os, \PDO::PARAM_INT);

        if (!$query->execute() || $query->rowCount() == 0) {
            throw new \Exception("TC/OS-GDOE-001");
        } else {
            $res = $query->fetchAll(\PDO::FETCH_ASSOC);

            $dados = array();

            foreach ($res as $os) {
                if (empty($dados)) {
                    if (empty($os["data_agendamento"]) && $os["km_google"] != "t") {
                        $os["data_agendamento"] = $os["data_abertura"];
                    }

                    $dados[$os['os']] = array(
                        'sua_os' => $os['sua_os'],
                        'notificacao' => $os['os'],
                        'data_abertura' => $os['data_abertura'],
                        'data_agendamento' => $os['data_agendamento'],
                        'data_fechamento' => $os['data_fechamento'],
                        'posto' => $os['posto'],
                        'posto_codigo' => $os['posto_codigo'],
                        'produto_referencia' => $os['produto_referencia'],
                        'rpi' => $os['rpi'],
                        'linha_descricao' => $os['linha_descricao'],
                        'serie' => $os['serie'],
                        'nota_fiscal' => $os['nota_fiscal'],
                        'data_nf' => $os['data_nf'],
                        'consumidor_revenda' => $os['consumidor_revenda'],
                        'consumidor_nome' => $os['consumidor_nome'],
                        'consumidor_endereco' => $os['consumidor_endereco'],
                        'consumidor_numero' => $os['consumidor_numero'],
                        'consumidor_cep' => $os['consumidor_cep'],
                        'consumidor_cidade' => $os['consumidor_cidade'],
                        'consumidor_estado' => $os['consumidor_estado'],
                        'consumidor_telefone' => $os['consumidor_telefone'],
                        'consumidor_celular' => $os['consumidor_celular'],
                        'consumidor_email' => $os['consumidor_email'],
                        'revenda_nome' => $os['revenda_nome'],
                        'revenda_endereco' => $os['revenda_endereco'],
                        'revenda_numero' => $os['revenda_numero'],
                        'revenda_cep' => $os['revenda_cep'],
                        'revenda_cidade' => $os['revenda_cidade'],
                        'revenda_estado' => $os['revenda_estado'],
                        'revenda_telefone' => $os['revenda_telefone'],
                        'revenda_email' => $os['revenda_email'],
                        'mao_de_obra' => $os['mao_de_obra'],
                        'qtde_km_calculada' => $os['qtde_km_calculada'],
                        'valores_adicionais' => $os['valores_adicionais'],
                        'grupo_atendimento' => $os['grupo_atendimento'],
                        'obs' => utf8_encode($os['obs']),
                        'os_posto' => $os['os_posto']
                    );
                }

                if (!empty(trim($os['defeito_constatado_codigo'])) && !empty(trim($os['defeito_peca_codigo']))) {
                    $dados[$os['os']]['defeitos'][$os['defeito_peca_id']] = array(
                        'defeito_constatado_codigo' => trim($os['defeito_constatado_codigo']),
                        'defeito_peca_codigo' => trim($os['defeito_peca_codigo'])
                    );
                }

                if ($os['troca_de_peca'] == 't' && $os['peca_estoque'] != 't' && $os['servico_ativo'] == 't') {
                    $dados[$os['os']]['itens'][$os['os_item']] = array(
                        'peca_referencia' => trim($os['peca_referencia']),
                        'peca_qtde' => $os['peca_qtde']
                    );
                }
            }
        }

        return $dados;
    }

    public function getDadosRPIExport($rpi, $serie)
    {

        $pdo = $this->_model->getPDO();

        $sql = "
            SELECT
                r.rpi,
                r.fabrica,
                UPPER(fn_retira_especiais(r.responsavel)) AS responsavel,
                UPPER(fn_retira_especiais(r.responsavel_funcao)) AS responsavel_funcao,
                r.data_partida,
                p.produto,
                p.referencia AS produto_referencia,
                rp.serie,
                UPPER(fn_retira_especiais(r.consumidor_nome)) AS consumidor_nome,
                r.consumidor_cpf,
                r.consumidor_cep,
                UPPER(fn_retira_especiais(c.nome)) AS consumidor_cidade,
                c.estado AS consumidor_estado,
                UPPER(fn_retira_especiais(r.consumidor_bairro)) AS consumidor_bairro,
                UPPER(fn_retira_especiais(r.consumidor_endereco)) AS consumidor_endereco,
                r.consumidor_numero,
                r.consumidor_telefone,
                UPPER(fn_retira_especiais(r.consumidor_complemento)) AS consumidor_complemento,
                UPPER(fn_retira_especiais(r.consumidor_contato)) AS consumidor_contato,
                r.obs,
                pf.codigo_posto AS posto_codigo,
                pst.posto,
                UPPER(fn_retira_especiais(pst.nome)) AS posto_nome,
                UPPER(fn_retira_especiais(pf.contato_cidade)) AS posto_cidade
            FROM tbl_rpi r
            JOIN tbl_rpi_produto rp ON rp.rpi = r.rpi AND rp.fabrica = :fabrica
            JOIN tbl_produto p ON p.produto = rp.produto AND p.fabrica_i = :fabrica
            JOIN tbl_posto_fabrica pf ON pf.posto = r.posto AND pf.fabrica = :fabrica
            JOIN tbl_posto pst ON pst.posto = pf.posto
            JOIN tbl_cidade c ON c.cidade = r.consumidor_cidade
            WHERE r.fabrica = :fabrica
            AND r.rpi = :rpi
            AND rp.serie = :serie
            AND r.exportado IS NULL;
        ";

        $query = $pdo->prepare($sql);

        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
        $query->bindParam(':rpi', $rpi, \PDO::PARAM_INT);
        $query->bindParam(':serie', $serie, \PDO::PARAM_STR);

        if (!$query->execute()) {
            throw new \Exception("Ocorreu um erro buscando dados para integração do RPI #001");
        } else {
            $res = $query->fetchAll(\PDO::FETCH_ASSOC);
            $dados = array();

            if (count($res) > 0) {
                foreach ($res as $rpi) {
                    $dados[$rpi['serie']] = array(
                        'ID_RPI_TC' => $rpi['rpi'],
                        'COD_CT_DEALER' => $rpi['posto_codigo'],
                        'NOME_DEALER' => $rpi['posto_nome'],
                        'CIDADE_DEALER' => $rpi['posto_cidade'],
                        'MATNR' => str_replace('YY', '-', $rpi['produto_referencia']),
                        'SERNR' => $rpi['serie'],
                        'DATA_PARTIDA' => $rpi['data_partida'],
                        'CONTATO' => $rpi['consumidor_contato'],
                        'RESPONSAVEL' => $rpi['responsavel'],
                        'FUNCAO' => $rpi['responsavel_funcao'],
                        'NAME1' => $rpi['consumidor_nome'],
                        'STREET' => $rpi['consumidor_endereco'],
                        'HOUSE_NUM1' => $rpi['consumidor_numero'],
                        'HOUSE_NUM2' => $rpi['consumidor_complemento'],
                        'POST_CODE1' => str_pad($rpi['consumidor_cep'], 9, "0", STR_PAD_LEFT),
                        'CITY2' => $rpi['consumidor_bairro'],
                        'CITY1' => $rpi['consumidor_cidade'],
                        'REGION' => $rpi['consumidor_estado'],
                        'TEL_NUMBER' => preg_replace("/[^0-9]/", "", $rpi['consumidor_telefone'])
                    );
                }
            } else {
                throw new \Exception("Ocorreu um erro buscando dados para integração do RPI #002");
            }
        }

        return $dados;
    }

    public function exportNotificacao($dados, $LogExportacao = null)
    {
        $pdo = $this->_model->getPDO();
        $array_dados = array();

        if (!empty($dados['os'])) {
            $array_dados[] = $dados;
        }

        foreach ($array_dados as $os) {
            $os_id = $os['os'];
	        $sua_os = $os["sua_os"];
            $posto = $os['posto'];
            $produto_descricao = utf8_encode($os['produto_descricao']);
            $produto_referencia = str_replace("YY", "-", $os['produto_referencia']);
            $revenda_nome = utf8_encode($os['revenda_nome']);

            if ($os['consumidor_revenda'] == 'C' || ($os['consumidor_revenda'] == 'R' && !in_array($os['grupo_atendimento'], array('R','G')) && !empty(trim($os['consumidor_nome'])) && !empty(trim($os['consumidor_cep'])))) {
                $request_nome = utf8_encode($os['consumidor_nome']);
                $request_cnpj_cpf = $os['consumidor_cpf'];
                $request_cep = str_pad($os['consumidor_cep'], 9, "0", STR_PAD_LEFT);
                $request_endereco = $os['consumidor_endereco'];
                $request_numero = $os['consumidor_numero'];
                $request_complemento = $os['consumidor_complemento'];
                $request_bairro = $os['consumidor_bairro'];
                $request_cidade = utf8_encode($os['consumidor_cidade']);
                $request_estado = $os['consumidor_estado'];
                $request_telefone = preg_replace("/[^0-9]/", "", $os['consumidor_telefone']);
                $request_celular = preg_replace("/[^0-9]/", "", $os['consumidor_celular']);
                $request_email = $os['consumidor_email'];
            } else {
                $request_nome = utf8_encode($os['revenda_nome']);
                $request_cnpj_cpf = $os['revenda_cnpj'];
                $request_cep = str_pad($os['revenda_cep'], 9, "0", STR_PAD_LEFT);
                $request_endereco = $os['revenda_endereco'];
                $request_numero = $os['revenda_numero'];
                $request_complemento = $os['revenda_complemento'];
                $request_bairro = $os['revenda_bairro'];
                $request_cidade = utf8_encode($os['revenda_cidade']);
                $request_estado = $os['revenda_estado'];
                $request_telefone = preg_replace("/[^0-9]/", "", $os['revenda_telefone']);
                $request_celular = "";
                $request_email = $os['revenda_email'];
            }

            if (!empty($request_email)) {
                $request_email = (filter_var($request_email, FILTER_VALIDATE_EMAIL)) ? $request_email : null;
            }

            if (!empty($os['importacao_fabrica'])) {
                throw new \Exception("notificação já exportada anteriormente", 200);
            } else {
                $opcao = "C";

                $sql = "
        		    SELECT
            			comentario,
            			os,
            			'f' AS gravacao_sap
        		    FROM (
            			SELECT *
            			FROM tbl_os_interacao
            			WHERE fabrica = :fabrica
            			AND admin = 9218
            			AND os = :os
            			AND interno IS TRUE
            			ORDER BY data DESC
            			LIMIT 1
        		    ) x
        		    WHERE comentario ~ '^(033)\s\-\s'
        		    UNION
        		    SELECT
            			comentario,
            			os,
            			't' AS gravacao_sap
        		    FROM (
            			SELECT *
            			FROM tbl_os_interacao
            			WHERE fabrica = :fabrica
            			AND admin = 9218
            			AND os = :os
            			AND interno IS TRUE
            			ORDER BY data DESC
            			LIMIT 1
        		    ) x
        		    WHERE comentario ~ '^(433|430|431|064|138|030|101)\s\-\s';
                ";
                $query = $pdo->prepare($sql);

                $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
                $query->bindParam(':os', $os_id, \PDO::PARAM_INT);

                if (!$query->execute()) {
                    throw new \Exception("TC/OS-EN-001");
                } else {
                    if ($query->rowCount() > 0) {
                        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

                        if (count($res) > 0) {
                            $gravacao_sap = $res[0]['gravacao_sap'];

                            if ($gravacao_sap == "t") {
                                $opcao = "M";
                            }
                        }
                    }
                }
            }

            if ($os['periodo'] == 'tarde'){
                $periodo = "EXTA";
            } else {
                $periodo = "EXMA";
            }

            if (!empty($os['data_abertura'])) {
                $data_abertura = date('Y-m-d', strtotime($os['data_abertura']));
            }

            if (!empty($os['data_agendamento'])) {
                $data_agendamento = date('Y-m-d', strtotime($os['data_agendamento']));
            }

            if (!empty($os['data_nf'])) {
                $data_nf = date('Y-m-d', strtotime($os['data_nf']));
            }

    	    if (empty(trim($os['nota_fiscal'])) && (in_array($os['grupo_atendimento'], array('R','G')) || $os["consumidor_revenda"] == "R")) {
        	$nota_fiscal = '99999';
    	    } else if (!empty(trim($os['nota_fiscal']))) {
		$nota_fiscal = $os['nota_fiscal'];
	    }

            if (empty($data_nf) && (in_array($os['grupo_atendimento'], array('R','G')) || $os["consumidor_revenda"] == "R")) {
                $data_nf = $data_abertura;
            }

            if ($os["consumidor_revenda"] == "R" && empty($os["defeito_reclamado_codigo"])) {
                $os["defeito_reclamado_codigo"] = "SIND";
            }

            try {
                $client = new \SoapClient($this->_urlWSDL, array('trace' => 1));
            } catch(\Exception $e) {
                $this->LogSemRespostaDoServidor($os_id, $posto);
            } catch(\Throwable $e) {
                $this->LogSemRespostaDoServidor($os_id, $posto);
            }

            $xmlRequest = "
                <ns1:xmlDoc>
                    <GOLWEB>
                        <Z_CB_SM_NOTA>
                            <PV_OPCAO>{$opcao}</PV_OPCAO>
                            <PF_NOTA_IN>
                                <QMNUM>{$os_id}</QMNUM>
                                <QMTXT>Notificacao Callcenter QUADDRA</QMTXT>
                                <QMDAT>{$data_abertura}</QMDAT>
                                <GEWRK>{$os['posto_codigo']}</GEWRK>
                                <SWERK>B111</SWERK>
                                <FEKAT>Y</FEKAT>
                                <FECOD>{$os['defeito_reclamado_codigo']}</FECOD>
                                <PSTER>{$data_agendamento}</PSTER>
                                <MNKAT>2</MNKAT>
                                <MNCOD>{$periodo}</MNCOD>
                                <MNGRP>ZB2CEXEC</MNGRP>
                                <MODELO>{$produto_referencia}</MODELO>
                                <SERIE>{$os['serie']}</SERIE>
                                <TIPO>{$produto_descricao}</TIPO>
                                <REVENDEDOR>{$revenda_nome}</REVENDEDOR>
                                <NOTA_FISCAL>{$nota_fiscal}</NOTA_FISCAL>
                                <DATA_NF>{$data_nf}</DATA_NF>
                                <NAME1>{$request_nome}</NAME1>
                                <SORT1>{$request_cnpj_cpf}</SORT1>
                                <STREET>{$request_endereco}</STREET>
                                <STR_SUPPL1>$request_complemento</STR_SUPPL1>
                                <HOUSE_NUM1>{$request_numero}</HOUSE_NUM1>
                                <POST_CODE1>{$request_cep}</POST_CODE1>
                                <CITY2>{$request_bairro}</CITY2>
                                <CITY1>{$request_cidade}</CITY1>
                                <REGION>{$request_estado}</REGION>
                                <COUNTRY>BR</COUNTRY>
                                <TEL_NUMBER>{$request_telefone}</TEL_NUMBER>
                                <MOB_NUMBER>{$request_celular}</MOB_NUMBER>
                                <SMTP_ADDR>{$request_email}</SMTP_ADDR>
                            </PF_NOTA_IN>
                        </Z_CB_SM_NOTA>
                    </GOLWEB>
                </ns1:xmlDoc>
            ";
            $xmlRequest = preg_replace("/&/", "", $xmlRequest);
            
            $params = new \SoapVar($xmlRequest, XSD_ANYXML);

            if (!is_null($LogExportacao)) {
                $LogExportacao->setXmlEnviadoNotificacao($os_id, $xmlRequest);
            }

            $array_params = array("xmlDoc" => $params);            
            $result = $client->PesquisaNota($array_params);
            
            $dados_xml = $result->PesquisaNotaResult->any;
            
            if (!is_null($LogExportacao)) {
                $LogExportacao->setXmlRecebidoNotificacao($os_id, $dados_xml);
            }

            $xml = simplexml_load_string($dados_xml);
            $xml = json_decode(json_encode((array)$xml), TRUE);

            if (strlen($xml['NewDataSet']['ZCBSM_DADOS_NOTATABLE']['QMNUM']) > 0) {
                if (empty($os['importacao_fabrica'])) {
                    $update = "
                        UPDATE tbl_os SET
                            importacao_fabrica = current_timestamp
                        WHERE os = :os
                        AND fabrica = :fabrica;
                    ";
                    $query = $pdo->prepare($update);

                    $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
                    $query->bindParam(':os', $os_id, \PDO::PARAM_INT);

                    if (!$query->execute()) {
                        throw new \Exception("TC/OS-EN-002");
                    } else {
                        if (!is_null($LogExportacao)) {
                            $LogExportacao->setStatusNotificacao($os_id, "exportado");
                        } else {
                            return true;
                        }
                    }
                } else {
                    throw new \Exception("notificação já exportada anteriormente", 200);
                }
            } else {
		        if (empty($xml)) {
		            $this->LogSemRespostaDoServidor($os_id, $posto);
		        }

                $xmlRetorno = array();

                if ($xml['NewDataSet']['ZCBSM_MENSAGEMTABLE']['MSGTY'] == "E") {
                    $xmlRetorno[] = $xml['NewDataSet']['ZCBSM_MENSAGEMTABLE'];
                } else {
                    $xmlRetorno = $xml['NewDataSet']['ZCBSM_MENSAGEMTABLE'];
                }

		        if (empty($xmlRetorno)) {
                    $this->LogSemRespostaDoServidor($os_id, $posto);
		        }

                foreach ($xmlRetorno as $retorno) {
                    $msgErro = utf8_decode($retorno['MSGNO']." - ".$retorno['MSGTX']);
                    $numErro = $retorno['MSGNO']."%";

                    $sqlInteracao = "
                        SELECT *
                        FROM tbl_os_interacao
                        WHERE os = :os
                        AND fabrica = :fabrica
                        AND comentario LIKE :erro;
                    ";
                    $query = $pdo->prepare($sqlInteracao);
                    $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
                    $query->bindParam(':os', $os_id, \PDO::PARAM_INT);
                    $query->bindParam(':erro', $numErro, \PDO::PARAM_STR);

                    if (!$query->execute()) {
                        throw new \Exception("TC/OS-EN-003");
                    }

                    if ($query->rowCount() == 0) {
                        $sql = "
                            INSERT INTO tbl_os_interacao (
                                programa,
                                fabrica,
                                os,
                                admin,
                                posto,
                                comentario,
                                interno
                            ) VALUES (
                                :programa,
                                :fabrica,
                                :os,
                                9218,
                                :posto,
                                :obs,
                                :interno
                            );
                        ";
                        $interno = 1;

                        $query = $pdo->prepare($sql);
                        $query->bindParam(':programa', $_SERVER['PHP_SELF'], \PDO::PARAM_STR);
                        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
                        $query->bindParam(':os', $os_id, \PDO::PARAM_INT);
                        $query->bindParam(':posto', $posto, \PDO::PARAM_INT);
                        $query->bindParam(':obs', $msgErro, \PDO::PARAM_STR);
                        $query->bindParam(':interno', $interno, \PDO::PARAM_BOOL);

                        if (!$query->execute()) {
                            throw new \Exception("TC/OS-EN-004");
                        }
                    }

                    if ($retorno['MSGNO'] == '030' && strlen($os['importacao_fabrica']) == 0) {
                        $update = "
                            UPDATE tbl_os SET
                                importacao_fabrica = current_timestamp
                            WHERE os = :os
                            AND fabrica = :fabrica;
                        ";
                        $query = $pdo->prepare($update);
                        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
                        $query->bindParam(':os', $os_id, \PDO::PARAM_INT);

                        if (!$query->execute()) {
                            throw new \Exception("TC/OS-EN-005");
                        }

                        throw new \Exception("notificação já exportada anteriormente", 200);
                    } else if ($retorno['MSGNO'] == '031') {
                        throw new \Exception("notificação já exportada anteriormente", 200);
                    } else {
                        throw new \Exception($msgErro);
                    }
                }
            }
        }
    }

    public function exportOS($dados, $LogExportacao = null)
    {
        $pdo = $this->_model->getPDO();
        $array_dados = array();

        if (!empty($dados['os'])) {
            $array_dados[] = $dados;
        } else {
            $array_dados = $dados;
        }

        foreach ($array_dados as $os => $dados) {
            $xmlItens = "";
            $xmlDefeitos = "";
            $xmlServicos = "";

            if (!empty($dados['os_posto'])) {
                $opcao = "M";
            } else {
                $opcao = "C";
            }

            $sua_os = $dados["sua_os"];

            $posto = $dados['posto'];
            $revenda_nome = utf8_encode($dados['revenda_nome']);
            $produto_referencia = str_replace("YY", "-", $dados['produto_referencia']);

            if ($dados['consumidor_revenda'] == 'C' || ($dados['consumidor_revenda'] == 'R' && !in_array($dados['grupo_atendimento'], array('R','G')) && !empty(trim($dados['consumidor_nome'])) && !empty(trim($dados['consumidor_cep'])))) {
                $request_nome = utf8_encode($dados['consumidor_nome']);
                $request_cnpj_cpf = $dados['consumidor_cpf'];
                $request_cep = str_pad($dados['consumidor_cep'], 9, "0", STR_PAD_LEFT);
                $request_endereco = $dados['consumidor_endereco'];
                $request_numero = $dados['consumidor_numero'];
                $request_complemento = $dados['consumidor_complemento'];
                $request_bairro = $dados['consumidor_bairro'];
                $request_cidade = utf8_encode($dados['consumidor_cidade']);
                $request_estado = $dados['consumidor_estado'];
                $request_telefone = preg_replace("/[^0-9]/", "", $dados['consumidor_telefone']);
                $request_celular = preg_replace("/[^0-9]/", "", $dados['consumidor_celular']);
            } else {
                $request_nome = utf8_encode($dados['revenda_nome']);
                $request_cnpj_cpf = $dados['revenda_cnpj'];
                $request_cep = str_pad($dados['revenda_cep'], 9, "0", STR_PAD_LEFT);
                $request_endereco = $dados['revenda_endereco'];
                $request_numero = $dados['revenda_numero'];
                $request_complemento = $dados['revenda_complemento'];
                $request_bairro = $dados['revenda_bairro'];
                $request_cidade = utf8_encode($dados['revenda_cidade']);
                $request_estado = $dados['revenda_estado'];
                $request_telefone = preg_replace("/[^0-9]/", "", $dados['revenda_telefone']);
                $request_celular = "";
            }

	    if ($dados['grupo_atendimento'] == 'R') {
		$conta_contabil = 4217065;
	    } else {
		$conta_contabil = 5111253;
	    }

            if (!empty($dados['mao_de_obra'])) {
                $mao_de_obra = $dados['mao_de_obra'];
            } else {
                $mao_de_obra = 0;
            }

            if (!empty($dados['qtde_km_calculada'])) {
                $qtde_km_calculada = number_format($dados['qtde_km_calculada'], 2, '.', '');
            } else {
                $qtde_km_calculada = null;
            }

            if (!empty($dados['valores_adicionais'])) {
                $valores_adicionais = json_decode($dados['valores_adicionais'], true);
                $valores_adicionais = (is_array($valores_adicionais)) ? array_sum($valores_adicionais) : $valores_adicionais;
            }

            if (!empty($dados['data_abertura'])) {
                $data_abertura = date('Y-m-d', strtotime($dados['data_abertura']));
            }

            try {
                $client = new \SoapClient($this->_urlWSDL, array('trace' => 1));
            } catch(\Exception $e) {
                $this->LogSemRespostaDoServidor($os, $posto);
            } catch(\Throwable $e) {
                $this->LogSemRespostaDoServidor($os, $posto);
            }

            $xmlRequest = "
                <ns1:xmlDoc>
                    <criterios>
                    <PF_ORDEM_IN>
                        <item>
                            <QMNUM>{$os}</QMNUM>
                        </item>
                    </PF_ORDEM_IN>
                    <PV_OPCAO>E</PV_OPCAO>
                </criterios>
                </ns1:xmlDoc>
            ";

            $params = new \SoapVar($xmlRequest, XSD_ANYXML);
            $array_params = array("xmlDoc" => $params);

            $result = $client->PesquisaOrdemServico($array_params);
            $dados_xml = $result->PesquisaOrdemServicoResult->any;

            $xml = simplexml_load_string($dados_xml);
    	    $xml = json_decode(json_encode((array)$xml), TRUE);

            if (empty($xml)) {
                throw new \Exception("TC/OS-EO-001");
            } else if (!empty($xml["NewDataSet"]["ZCBSM_PECASTable"])) {
                if (isset($xml["NewDataSet"]["ZCBSM_PECASTable"]["MATNR"])) {
                    $xmlRetornoItens[] = $xml["NewDataSet"]["ZCBSM_PECASTable"];
                } else {
                    $xmlRetornoItens = $xml["NewDataSet"]["ZCBSM_PECASTable"];
                }

                if (!empty($dados["data_fechamento"])) {
                    $dados["itens"] = array();
                }
        	
		foreach ($xmlRetornoItens as $peca) {
                    if (empty($dados["data_fechamento"])) {
                        $pecaSAP = true;

                        foreach($dados['itens'] as $i => $itens) {
                            if ($peca["MATNR"] == $itens["peca_referencia"]) {
                                $pecaSAP = false;
				$dados['itens'][$i]['peca_qtde'] = (double) $peca['BDMNG'];
                            }
                        }
                    }
		
                    if ($pecaSAP == true || !empty($dados["data_fechamento"])) {	
                        $dados["itens"][] = array(
                            "peca_referencia" => $peca["MATNR"],
                            "peca_qtde"       => (double) $peca["BDMNG"]
                        );
                    }
            	}
	    }

            if (!empty($dados['data_fechamento'])) {
                $data_fechamento = date('Y-m-d', strtotime($dados['data_fechamento']));
            }

            if (!empty(trim($dados['data_nf']))) {
                $data_nf = date('Y-m-d', strtotime($dados['data_nf']));
            }

    	    if (empty(trim($dados['nota_fiscal'])) && (in_array($dados['grupo_atendimento'], array('R','G')) || $dados["consumidor_revenda"] == "R")) {
        	$nota_fiscal = '99999';
    	    } else if (!empty(trim($dados['nota_fiscal']))) {
		$nota_fiscal = $dados['nota_fiscal'];
	    }

            if (empty($data_nf) && (in_array($dados['grupo_atendimento'], array('R','G')) || $dados["consumidor_revenda"] == "R")) {
                $data_nf = $data_abertura;
            }

            foreach ($dados['itens'] as $itens) {
                if (empty($itens['peca_referencia'])) {
                    continue;
                }

                $xmlItens .= "
                    <item>
                        <MATNR>{$itens['peca_referencia']}</MATNR>
                        <BDMNG>{$itens['peca_qtde']}</BDMNG>
                        <MEINS></MEINS>
                        <ZMEINS></ZMEINS>
                        <MAKTX></MAKTX>
                        <VORAB_SM></VORAB_SM>
                    </item>
                ";
            }

            foreach ($dados['defeitos'] as $linha => $defeitos) {
                $xmlDefeitos .= "
                    <item>
                        <URGRP>{$defeitos['defeito_constatado_codigo']}</URGRP>
                        <URCOD>{$defeitos['defeito_peca_codigo']}</URCOD>
                        <GRUPPETEXT></GRUPPETEXT>
                        <TXTCDUR></TXTCDUR>
                    </item>
                ";
            }

            if ($dados['rpi'] != "true") {
                $xmlServicos = "
                    <item>
                        <ASNUM>3000112</ASNUM>
                        <ASKTX>Mão de Obra</ASKTX>
                        <ASTYP>1</ASTYP>
                        <TBTWR>{$mao_de_obra}</TBTWR>
                        <WAERS></WAERS>
                    </item>
                ";

                if (!empty($dados['qtde_km_calculada'])) {
                    $xmlServicos .= "
                        <item>
                            <ASNUM>3000111</ASNUM>
                            <ASKTX>Deslocamento</ASKTX>
                            <ASTYP>1</ASTYP>
                            <TBTWR>{$qtde_km_calculada}</TBTWR>
                            <WAERS></WAERS>
                        </item>
                    ";
                }

                if (!empty($valores_adicionais)) {
                    $xmlServicos .= "
                        <item>
                            <ASNUM>3000112</ASNUM>
                            <ASKTX>Valores Adicionais</ASKTX>
                            <ASTYP>1</ASTYP>
                            <TBTWR>{$valores_adicionais}</TBTWR>
                            <WAERS></WAERS>
                        </item>
                    ";
                }
            }

            if (!empty($dados['obs'])) {
                $obs = wordwrap($dados['obs'], 72, "\n", true);
                $obs = preg_replace("/(\n\n|\r\n|\n\r)/", "\n", $obs);
                $obs = str_replace("\n\n", "\n", $obs);
                $obs = str_replace(["<URGENTE>", "<", ">"], "", $obs);
                $xmlObs = "<PT_TEXTOS><item><TXLINE>".$obs;
                $xmlObs = preg_replace("/\n/", "</TXLINE></item><item><TXLINE>", $xmlObs);
                $xmlObs = $xmlObs."</TXLINE></item></PT_TEXTOS>";
            }

            if (!empty($xmlItens)) {
                $xmlItens = "<PT_PECAS>{$xmlItens}</PT_PECAS>";
            }

            if (!empty($xmlDefeitos)) {
                $xmlDefeitos = "<PT_CAUSAS>{$xmlDefeitos}</PT_CAUSAS>";
            }

            if (!empty($xmlServicos)) {
                $xmlServicos = "<PT_SERVICOS>{$xmlServicos}</PT_SERVICOS>";
            }

            $xmlRequest = "
                <ns1:xmlDoc>
                    <criterios>
                        <PF_ORDEM_IN>
                            <item>
                                <QMNUM>{$os}</QMNUM>
                                <EMPGE>{$conta_contabil}</EMPGE>
                                <MATNR>{$produto_referencia}</MATNR>
                                <COD_COND>{$produto_referencia}</COD_COND>
                                <COD_EVAP>{$produto_referencia}</COD_EVAP>
                                <COMP_INS>1</COMP_INS>
                                <COMP_RET>1</COMP_RET>
                                <GLTRP>{$data_fechamento}</GLTRP>
                                <SERNR>{$dados['serie']}</SERNR>
                                <REVENDEDOR>{$revenda_nome}</REVENDEDOR>
                                <NOTA_FISCAL>{$nota_fiscal}</NOTA_FISCAL>
                                <DATA_NF>{$data_nf}</DATA_NF>
                                <NAME1>{$request_nome}</NAME1>
                                <SORT1>{$request_cnpj_cpf}</SORT1>
                                <STREET>{$request_endereco}</STREET>
                                <STR_SUPPL1>{$request_complemento}</STR_SUPPL1>
                                <HOUSE_NUM1>{$request_numero}</HOUSE_NUM1>
                                <POST_CODE1>{$request_cep}</POST_CODE1>
                                <CITY2>{$request_bairro}</CITY2>
                                <CITY1>{$request_cidade}</CITY1>
                                <REGION>{$request_estado}</REGION>
                                <COUNTRY>BR</COUNTRY>
                                <TEL_NUMBER>{$request_telefone}</TEL_NUMBER>
                                <MOB_NUMBER>{$request_celular}</MOB_NUMBER>
                                <SMTP_ADDR>{$request_email}</SMTP_ADDR>
                            </item>
                        </PF_ORDEM_IN>
                        {$xmlObs}
                        {$xmlDefeitos}
                        {$xmlItens}
                        {$xmlServicos}
                        <PV_OPCAO>{$opcao}</PV_OPCAO>
                    </criterios>
                </ns1:xmlDoc>
            ";

            $xmlRequest = preg_replace("/&/", "", $xmlRequest);

            if (!is_null($LogExportacao)) {
                $LogExportacao->setXmlEnviadoOS($os, $xmlRequest);
            }
            $params = new \SoapVar($xmlRequest, XSD_ANYXML);
            $array_params = array("xmlDoc" => $params);

            $result = $client->PesquisaOrdemServico($array_params);
            $dados_xml = $result->PesquisaOrdemServicoResult->any;

            if (!is_null($LogExportacao)) {
                $LogExportacao->setXmlRecebidoOS($os, $dados_xml);
            }

            $xml = simplexml_load_string($dados_xml);
            $xml = json_decode(json_encode((array)$xml), TRUE);

            if (strlen($xml['NewDataSet']['ZCBSM_DADOS_ORDEM_SERVICOTable']['QMNUM']) > 0 && empty($xml['NewDataSet']['ZCBSM_PT_MENSAGEMTABLE']['MSGTY'])) {
                $os_sap = trim($xml['NewDataSet']['ZCBSM_DADOS_ORDEM_SERVICOTable']['AUFNR']);
                
                if (!empty($os_sap)) {
                    $os_sap = str_pad($os_sap, 12, "0", STR_PAD_LEFT);

                    $update = "
                        UPDATE tbl_os SET
                            os_posto = :os_sap
                        WHERE os = :os
                        AND fabrica = :fabrica;
                    ";

                    $query = $pdo->prepare($update);

                    $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
                    $query->bindParam(':os', $os, \PDO::PARAM_INT);
                    $query->bindParam(':os_sap', $os_sap, \PDO::PARAM_STR);

                    if (!$query->execute()) {
                        throw new \Exception("TC/OS-EO-002");
                    }

                    $update = "
                        UPDATE tbl_os_extra SET
                            baixada = CURRENT_TIMESTAMP
                        WHERE os = :os;
                    ";
                    $query = $pdo->prepare($update);
                    $query->bindParam(':os', $os, \PDO::PARAM_INT);

                    if (!$query->execute()) {
                        throw new \Exception("TC/OS-EO-003");
                    }

                    if (!is_null($LogExportacao)) {
                        $LogExportacao->setStatusOS($os, "exportado");
                    } else {
                        return true;
                    }
                } else {
                    throw new \Exception("TC/OS-EO-004");
                }
            } else {
		        if (empty($xml)) {
                    $this->LogSemRespostaDoServidor($os, $posto);
		        }

                if (in_array($xml['NewDataSet']['ZCBSM_PT_MENSAGEMTABLE']['MSGTY'], array("E", "W"))) {
                    $xmlRetorno[] = $xml['NewDataSet']['ZCBSM_PT_MENSAGEMTABLE'];
                } else {
                    $xmlRetorno = $xml['NewDataSet']['ZCBSM_PT_MENSAGEMTABLE'];
                }

                if (empty($xmlRetorno)) {
                    $this->LogSemRespostaDoServidor($os, $posto);
		        }

                foreach ($xmlRetorno as $retorno) {
                    $msgErro = utf8_decode($retorno['MSGNO']." - ".$retorno['MSGTX']);
                    $numErro = $retorno['MSGNO']."%";

                    if ($retorno["MSGNO"] == "000" && preg_match("/IEQINSTALL\-TPLNR/", $msgErro)) {
                        $msgErro = "000 - Produto ainda em estoque";
                    }

                    $sqlInteracao = "
                        SELECT *
                        FROM tbl_os_interacao
                        WHERE os = :os
                        AND fabrica = :fabrica
                        AND comentario LIKE :erro;
                    ";
                    $query = $pdo->prepare($sqlInteracao);
                    $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
                    $query->bindParam(':os', $os, \PDO::PARAM_INT);
                    $query->bindParam(':erro', $numErro, \PDO::PARAM_STR);

                    if (!$query->execute()) {
                        throw new \Exception("TC/OS-EO-005");
                    }

                    if ($query->rowCount() == 0) {
                        $sql = "
                            INSERT INTO tbl_os_interacao (
                                programa,
                                fabrica,
                                os,
                                admin,
				                posto,
                                comentario,
                                interno
                            ) VALUES (
                                :programa,
                                :fabrica,
                                :os,
                                9218,
				                :posto,
                                :obs,
                                :interno
                            ) RETURNING os_interacao;
                        ";
                        $interno = 1;

                        $query = $pdo->prepare($sql);
                        $query->bindParam(':programa', $_SERVER['PHP_SELF'], \PDO::PARAM_STR);
                        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
                        $query->bindParam(':os', $os, \PDO::PARAM_INT);
                        $query->bindParam(':posto', $posto, \PDO::PARAM_INT);
                        $query->bindParam(':obs', $msgErro, \PDO::PARAM_STR);
                        $query->bindParam(':interno', $interno, \PDO::PARAM_BOOL);

                        if (!$query->execute()) {
                            throw new \Exception("TC/OS-EO-006");
                        }
                    }

                    if (in_array($retorno['MSGNO'], array('267')) || (!empty($dados["os_posto"]) && !empty($xml["NewDataSet"]["ZCBSM_DADOS_ORDEM_SERVICOTable"]["GLTRP"]) && in_array($retorno["MSGNO"], array("043")))) {
                        $data_fechamento = $xml["NewDataSet"]["ZCBSM_DADOS_ORDEM_SERVICOTable"]["GLTRP"];

                        $sql = "
                            SELECT oi.os_item
                            FROM tbl_os o
                            INNER JOIN tbl_os_produto op ON op.os = o.os
                            INNER JOIN tbl_os_item oi ON oi.os_produto = op.os_produto
                            INNER JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = :fabrica
                            LEFT JOIN tbl_os_troca ot ON ot.os = o.os AND ot.fabric = :fabrica
                            LEFT JOIN tbl_pedido_item pi ON pi.pedido_item = oi.pedido_item
                            LEFT JOIN tbl_pedido p ON p.pedido = pi.pedido AND p.fabrica = :fabrica
                            LEFT JOIN tbl_auditoria_os ao ON ao.os = o.os
                            WHERE o.fabrica = :fabrica
                            AND o.os = :os
                            AND (
                                (
                                        p.status_pedido NOT IN(14)
                                        AND pi.qtde > (COALESCE(pi.qtde_faturada, 0) + COALESCE(pi.qtde_cancelada, 0))
                                )
                                OR (
                                        sr.troca_de_peca IS TRUE
                                        AND sr.gera_pedido IS TRUE
                                        AND oi.pedido_item IS NULL
                                )
                                OR (
                                        sr.troca_produto IS TRUE
                                        AND sr.gera_pedido IS TRUE
                                        AND ot.gerar_pedido IS TRUE
                                        AND oi.pedido_item IS NULL
                                )
                                OR (
                                        ao.auditoria_os IS NOT NULL
                                        AND ao.liberada IS NULL
                                        AND ao.cancelada IS NULL
                                        AND ao.reprovada IS NULL
                                )
                            )
                        ";
                        $query = $pdo->prepare($sql);
                        $query->bindParam(':os', $os, \PDO::PARAM_INT);
                        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);

                        if (!$query->execute()) {
                            throw new \Exception("TC/OS-EO-011");
                        }

                        if ($query->rowCount() > 0) {
                            throw new \Exception($msgErro." / A OS não pode ser finalizada automaticamente, verifique se há pendências de peças ou auditoria");
                        }

			$sql = "SELECT os FROM tbl_os WHERE os = :os AND fabrica = :fabrica AND finalizada IS NOT NULL;";
			$query = $pdo->prepare($sql);
                        $query->bindParam(':os', $os, \PDO::PARAM_INT);
                        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);

			if (!$query->execute()) {
                            throw new \Exception("TC/OS-EO-012");
                        }

			if ($query->rowCount() == 0) {

                        	$update = "
                                	UPDATE tbl_os SET
                                        	data_conserto = '{$data_fechamento}'
                                	WHERE fabrica = :fabrica
                                	AND os = :os
                                	AND (data_conserto IS NULL OR data_conserto > '{$data_fechamento}'::date)
                        	";
                        	$query = $pdo->prepare($update);
                        	$query->bindParam(':os', $os, \PDO::PARAM_INT);
                        	$query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);

                        	if (!$query->execute()) {
                                	throw new \Exception("TC/OS-EO-013");
                        	}

                        	$update = "
                                	UPDATE tbl_os SET
                                        	data_fechamento = '{$data_fechamento}',
                                        	finalizada = '{$data_fechamento}'::timestamp
                                	WHERE fabrica = :fabrica
                                	AND os = :os
                                	AND data_fechamento IS NULL
                                	AND finalizada IS NULL
                        	";
                        	$query = $pdo->prepare($update);
                        	$query->bindParam(':os', $os, \PDO::PARAM_INT);
                        	$query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);

                        	if (!$query->execute()) {
                                	throw new \Exception("TC/OS-EO-014");
                        	}
			}

                        $update = "
                               	UPDATE tbl_os_extra SET
                                       	baixada = CURRENT_TIMESTAMP
                               	WHERE os = :os;
                        ";
                        $query = $pdo->prepare($update);
                        $query->bindParam(':os', $os, \PDO::PARAM_INT);

                       	if (!$query->execute()) {
                       		throw new \Exception("TC/OS-EO-015");
                       	}

                        if (!is_null($LogExportacao)) {
                                $LogExportacao->setStatusOS($os, "exportado", "OS finalizada automaticamente");
                        } else {
                                return true;
                        }
                    } else if (in_array($retorno['MSGNO'], array("053", "043")) && empty($dados['os_posto'])) {
                    	$os_sap = $retorno['MSGV1'];

                        if (!empty($os_sap)) {
                            $os_sap = str_pad($os_sap, 12, 0, STR_PAD_LEFT);

                            $update = "
                                UPDATE tbl_os SET
                                    os_posto = :os_sap
                                WHERE os = :os
                                AND fabrica = :fabrica;
                            ";
                            $query = $pdo->prepare($update);
                            $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
                            $query->bindParam(':os', $os, \PDO::PARAM_INT);
                            $query->bindParam(':os_sap', $os_sap, \PDO::PARAM_STR);

                            if (!$query->execute()) {
                                throw new \Exception("TC/OS-EO-007");
                            }

                            $update = "
                                UPDATE tbl_os_extra SET
                                    baixada = CURRENT_TIMESTAMP
                                WHERE os = :os;
                            ";
                            $query = $pdo->prepare($update);
                            $query->bindParam(':os', $os, \PDO::PARAM_INT);

                            if (!$query->execute()) {
                                throw new \Exception("TC/OS-EO-008");
                            }

                            if ($retorno['MSGNO'] == "043") {
                                if (!is_null($LogExportacao)) {
                                    $LogExportacao->setStatusOS($os, "exportado", "OS finalizada no SAP");
                                } else {
                                    return true;
                                }
                            } else {
                                if (!is_null($LogExportacao)) {
                                    $LogExportacao->setStatusOS($os, "exportado");
                                } else {
                                    return true;
                                }
                            }
                        } else {
                            throw new \Exception("TC/OS-EO-009");
                        }
                    } else if (in_array($retorno['MSGNO'], array("433","430","064","138","030","101","431"))) {
                        $update = "
                            UPDATE tbl_os SET
                                importacao_fabrica = null
                            WHERE os = :os;
                        ";

                        $query = $pdo->prepare($update);
                        $query->bindParam(':os', $os, \PDO::PARAM_INT);

                        if (!$query->execute()) {
                            throw new \Exception("TC/OS-EO-010");
                        }

                        throw new \Exception($msgErro." / Notificação adicionada a fila de exportação");
                    } else {
                        throw new \Exception($msgErro);
                    }
                }
            }
        }
    }

    public function validaRPI($dados)
    {

        $client = new \SoapClient($this->_urlWSDL, array('trace' => 1));

        $itens_rpi = "";

        foreach ($array_dados_valida as $dados) {
            $itens_rpi ="<item><MATNR>{$dados['MATNR']}</MATNR><SERNR>{$dados['SERNR']}</SERNR></item>";
        }

        $xml_request = "
            <ns1:xmlDoc>
                <criterios>
                    <PV_OPCAO>E</PV_OPCAO>
                    <PT_EQUI>
                        {$itens_rpi}
                    </PT_EQUI>
                </criterios>
            </ns1:xmlDoc>
        ";

        $params = new \SoapVar($xml_request, XSD_ANYXML);
        $array_params = array("xmlDoc" => $params);

        $result = $client->PesquisaEquipamento($array_params);
        $dados_xml = $result->PesquisaEquipamentoResult->any;

        $xml = simplexml_load_string($dados_xml);
        $xml = json_decode(json_encode((array)$xml), TRUE);

        if (strlen($xml['NewDataSet']['ZCBSM_DADOS_EQUIPAMENTOTable']['EQUNR']) > 0 && $xml['NewDataSet']['ZCBSM_DADOS_EQUIPAMENTOTable']['RPI_CADASTRADO'] == 'S') {
            return $xml;
        } else {
            return false;
        }
    }

    public function exportRPI($dados)
    {

        if (!is_array($dados)) {
            throw new \Exception("Dados não encontrado para exportar RPI");
        }

        $pdo = $this->_model->getPDO();
        $array_dados = array();

        if (!empty($dados['SERNR'])) {
            $array_dados[] = $dados;
        } else {
            $array_dados = $dados;
        }

        $client = new \SoapClient($this->_urlWSDL, array('trace' => 1));

        $xmlItens = "";
        $xmlObs = "";

        foreach ($array_dados as $serie => $dados) {

            $rpi = $dados['ID_RPI_TC'];

            $xmlItens ="
                <item>
                    <COD_CT_DEALER>{$dados['COD_CT_DEALER']}</COD_CT_DEALER>
                    <NOME_DEALER>{$dados['NOME_DEALER']}</NOME_DEALER>
                    <CIDADE_DEALER>{$dados['CIDADE_DEALER']}</CIDADE_DEALER>
                    <MATNR>{$dados['MATNR']}</MATNR>
                    <SERNR>{$dados['SERNR']}</SERNR>
                    <DATA_PARTIDA>{$dados['DATA_PARTIDA']}</DATA_PARTIDA>
                    <CONTATO>{$dados['CONTATO']}</CONTATO>
                    <RESPONSAVEL>{$dados['RESPONSAVEL']}</RESPONSAVEL>
                    <FUNCAO>{$dados['FUNCAO']}</FUNCAO>
                    <NAME1>{$dados['NAME1']}</NAME1>
                    <STREET>{$dados['STREET']}</STREET>
                    <HOUSE_NUM1>{$dados['HOUSE_NUM1']}</HOUSE_NUM1>
                    <HOUSE_NUM2>{$dados['HOUSE_NUM2']}</HOUSE_NUM2>
                    <POST_CODE1>{$dados['POST_CODE1']}</POST_CODE1>
                    <CITY2>{$dados['CITY2']}</CITY2>
                    <CITY1>{$dados['CITY1']}</CITY1>
                    <REGION>{$dados['REGION']}</REGION>
                    <COUNTRY>BR</COUNTRY>
                    <TEL_NUMBER>{$dados['TEL_NUMBER']}</TEL_NUMBER>
                </item>
            ";

            if (!empty($dados['obs'])) {
                $obs = wordwrap($dados['obs'], 72, "\n", true);
                $obs = preg_replace("/(\n\n|\r\n|\n\r)/", "\n", $obs);
                $obs = str_replace("\n\n", "\n", $obs);
                $xmlObs = "<PT_TEXTOS><item><TXLINE>".$obs;
                $xmlObs = preg_replace("/\n/", "</TXLINE></item><item><TXLINE>", $xmlObs);
                $xmlObs = $xmlObs."</TXLINE></item></PT_TEXTOS>";
            }

            $xml_request = "
                <ns1:xmlDoc>
                    <criterios>
                        <PV_OPCAO>R</PV_OPCAO>
                        <PT_EQUI>
                            {$xmlItens}
                        </PT_EQUI>
                        {$xmlObs}
                    </criterios>
                </ns1:xmlDoc>
            ";

            $params = new \SoapVar($xml_request, XSD_ANYXML);
            $array_params = array("xmlDoc" => $params);

            $result = $client->PesquisaEquipamento($array_params);
            $dados_xml = $result->PesquisaEquipamentoResult->any;

            $xml = simplexml_load_string($dados_xml);
            $xml = json_decode(json_encode((array)$xml), TRUE);

            if (strlen($xml['NewDataSet']['ZCBSM_DADOS_EQUIPAMENTOTable']['EQUNR']) > 0 && $xml['NewDataSet']['ZCBSM_DADOS_EQUIPAMENTOTable']['RPI_CADASTRADO'] == 'S') {

                $update = "
                    UPDATE tbl_rpi SET
                        exportado = now(),
                        exportado_erro = NULL
                    WHERE rpi = :rpi
                    AND fabrica = :fabrica;
                ";

                $query = $pdo->prepare($update);

                $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
                $query->bindParam(':rpi', $rpi, \PDO::PARAM_INT);

                if (!$query->execute()) {
                    throw new \Exception("Ocorreu um erro atualizando dados do RPI #001");
                }

                return true;

            } else {

                if ($xml['NewDataSet']['ZCBSM_MENSAGEMTable']['MSGTY'] == "E") {
                    $xmlRetorno[] = $xml['NewDataSet']['ZCBSM_MENSAGEMTable'];
                } else {
                    $xmlRetorno = $xml['NewDataSet']['ZCBSM_MENSAGEMTable'];
                }

                $msgErro = "";

                foreach ($xmlRetorno as $retorno) {
                    $msgErro .= utf8_decode($retorno['MSGNO']." - ".$retorno['MSGTX'])."\n";

                    if ($retorno['MSGNO'] == "038") {
                        $update = "
                            UPDATE tbl_rpi SET
                                exportado = now(),
                                exportado_erro = NULL
                            WHERE rpi = :rpi
                            AND fabrica = :fabrica;
                        ";

                        $query = $pdo->prepare($update);

                        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
                        $query->bindParam(':rpi', $rpi, \PDO::PARAM_INT);

                        if (!$query->execute()) {
                            throw new \Exception("Ocorreu um erro atualizando dados do RPI #002");
                        }

                        return true;
                    }

                }

                $update = "
                    UPDATE tbl_rpi SET
                        exportado_erro = :erro
                    WHERE rpi = :rpi
                    AND fabrica = :fabrica;
                ";

                $query = $pdo->prepare($update);

                $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
                $query->bindParam(':rpi', $rpi, \PDO::PARAM_INT);
                $query->bindParam(':erro', $msgErro, \PDO::PARAM_STR);

                if (!$query->execute()) {
                    throw new \Exception("Ocorreu um erro atualizando dados do RPI #003");
                }

                return false;
            }

        }

    }

    public function getOsPendenteExportacao($data_inicial = null, $data_final = null, $posto = null, $os = null)
    {
        $pdo = $this->_model->getPDO();

        if (!empty($os)) {
            $whereOs = "AND o.os = {$os}";
        } else if (!empty($data_inicial) && !empty($data_final)) {
            $whereData = "AND (o.data_abertura BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59')";

            if (!empty($posto)) {
                $wherePosto = "AND o.posto = {$posto}";
            }
        }

        $sql = "
	    SELECT DISTINCT
		x.*,
		(
        	    SELECT rsl.date_start AS data_primeira_integracao
        	    FROM tbl_os_auditar oa
        	    JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = oa.auditar
        	    WHERE oa.fabrica = :fabrica
        	    AND oa.os = x.os
        	    ORDER BY rsl.date_start ASC
        	    LIMIT 1
    		) AS data_primeira_integracao
	    FROM (
		SELECT
		    x_peca.*
    		FROM (
		    SELECT DISTINCT
    			o.os,
    			o.sua_os,
    			o.data_abertura,
    			o.exportado,
    			o.posto
    		    FROM tbl_os o
    		    JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = :fabrica
    		    JOIN tbl_os_extra oe ON oe.os = o.os AND i_fabrica = :fabrica
    		    JOIN tbl_os_produto op ON op.os = o.os
    		    JOIN tbl_os_item oi ON oi.os_produto = op.os_produto AND oi.pedido_item IS NOT NULL
    		    JOIN tbl_pedido_item pi ON pi.pedido_item = oi.pedido_item
    		    JOIN tbl_pedido p ON p.pedido = pi.pedido AND p.fabrica = :fabrica
    		    WHERE o.fabrica = :fabrica
    		    AND (oe.baixada IS NULL
    		    OR (o.importacao_fabrica IS NOT NULL
    		    AND o.os_posto IS NULL)
    		    OR (o.os_posto IS NOT NULL
    		    AND oe.baixada IS NULL))
    		    AND p.status_pedido NOT IN (14)
    		    AND o.cancelada IS NULL
    		    AND o.excluida IS NOT TRUE
    		    AND (ta.fora_garantia IS TRUE AND ta.grupo_atendimento IS NULL) = FALSE
    		    AND o.posto NOT IN (624161, 6359)
    		    AND o.finalizada IS NULL
		    AND o.data_abertura::DATE >= CURRENT_DATE - INTERVAL '8 MONTHS'
    		    {$whereOs}
    		    {$whereData}
    		    {$wherePosto}
		    UNION
    		    SELECT DISTINCT
		    	o.os,
		    	o.sua_os,
		    	o.data_abertura,
		    	o.exportado,
		    	o.posto
    		    FROM tbl_os o
    		    JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = :fabrica
    		    JOIN tbl_os_extra oe ON oe.os = o.os AND i_fabrica = :fabrica
    		    JOIN tbl_os_produto op ON op.os = o.os
    		    LEFT JOIN tbl_os_item oi ON oi.os_produto = op.os_produto
    		    LEFT JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = :fabrica
    		    WHERE o.fabrica = :fabrica
    		    AND o.finalizada IS NOT NULL
    		    AND o.cancelada IS NULL
    		    AND o.excluida IS NOT TRUE
    		    AND (oi.os_item IS NULL
    		    OR (sr.gera_pedido IS NOT TRUE AND sr.troca_de_peca IS NOT TRUE AND sr.troca_produto IS NOT TRUE AND sr.ativo IS TRUE))
    		    AND oe.baixada IS NULL
    		    AND (ta.fora_garantia IS TRUE AND ta.grupo_atendimento IS NULL) = FALSE
    		    AND o.posto NOT IN(624161, 6359)
		    AND o.data_abertura::DATE >= CURRENT_DATE - INTERVAL '8 MONTHS'
    		    {$whereOs}
    		    {$whereData}
    		    {$wherePosto}
		) x_peca
		LEFT JOIN tbl_auditoria_os ao ON ao.os = x_peca.os AND ao.bloqueio_pedido IS TRUE
		WHERE ao.auditoria_os IS NULL
		OR ao.liberada IS NOT NULL
    		UNION
    		SELECT DISTINCT
		    o.os,
		    o.sua_os,
		    o.data_abertura,
		    o.exportado,
		    o.posto
    		FROM tbl_os o
    		JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = :fabrica
		JOIN tbl_os_extra oe ON oe.os = o.os AND i_fabrica = :fabrica
		JOIN tbl_os_produto op ON op.os = o.os
		LEFT JOIN tbl_auditoria_os ao ON ao.os = o.os
		WHERE o.fabrica = :fabrica
		AND o.finalizada IS NOT NULL
		AND o.cancelada IS NULL
		AND o.excluida IS NOT TRUE
		AND o.status_checkpoint IN (9,48,49,50)
		AND oe.baixada IS NULL
		AND (ta.fora_garantia IS TRUE AND ta.grupo_atendimento IS NULL) = FALSE
		AND o.posto NOT IN(624161, 6359)
		AND o.data_abertura::DATE >= CURRENT_DATE - INTERVAL '8 MONTHS'
		AND (ao.auditoria_os IS NULL
		OR ao.liberada IS NOT NULL)
		{$whereOs}
		{$whereData}
		{$wherePosto}
	    ) x;
        ";
        #$sql = "SELECT os FROM tbl_os WHERE fabrica = :fabrica AND os IN (53637775)";
        $query = $pdo->prepare($sql);
        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);

        if (!$query->execute()) {
            throw new \Exception("Ocorreu um erro buscando dados para a exportação");
        }

        if ($query->rowCount() > 0){
            $res = $query->fetchAll(\PDO::FETCH_ASSOC);

            $array_os = array_filter($res, function($row) {
                if (!empty($row["finalizada"]) && $row["auditoria"] > 0) {
                    return false;
                }
                return true;
            });
        } else {
            $array_os = array();
        }

        return $array_os;
    }

    public function LogSemRespostaDoServidor($os, $posto) {
        $pdo = $this->_model->getPDO();

        $update = "
            UPDATE tbl_os SET
                importacao_fabrica = null
            WHERE os = :os;
        ";
        $query = $pdo->prepare($update);
        $query->bindParam(':os', $os, \PDO::PARAM_INT);

        if (!$query->execute()) {
            throw new \Exception("TC/OS-LSRDS-001");
        }

        $sqlInteracao = "
            SELECT comentario
            FROM tbl_os_interacao
            WHERE os = :os
            AND fabrica = :fabrica
            AND comentario ~ '^[0-9]{3}\s\-'
            ORDER BY data DESC LIMIT 1
        ";
        $query = $pdo->prepare($sqlInteracao);
        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
        $query->bindParam(':os', $os, \PDO::PARAM_INT);

        if (!$query->execute()) {
            throw new \Exception("TC/OS-LSRDS-002");
        }
        
        if ($query->rowCount() > 0) {
            $res = $query->fetch();
        }

        if ($query->rowCount() == 0 || !preg_match("/^000\s\-/", $res["comentario"])) {
            $sql = "
                INSERT INTO tbl_os_interacao 
                (programa, fabrica, os, admin, posto, comentario, interno)
                VALUES
                (:programa, :fabrica, :os, 9218, :posto, '000 - Sem resposta do webservice', true)
            ";
            $query = $pdo->prepare($sql);
            $query->bindParam(':programa', $_SERVER['PHP_SELF'], \PDO::PARAM_STR);
            $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
            $query->bindParam(':os', $os, \PDO::PARAM_INT);
            $query->bindParam(':posto', $posto, \PDO::PARAM_INT);

            if (!$query->execute()) {
                throw new \Exception("TC/OS-LSRDS-003");
            }
        }

        throw new \Exception("000 - Sem resposta do webservice");
    }

    public function flushLog($msg) {
       $arq = 'midea';
        $arquivo_log = "/tmp/exportacao-os-$arq-".date("Ymd").".txt";

        ob_start();
        if (!file_exists($arquivo_log)) {
            system("touch {$arquivo_log}");
        } else {
            echo "\n";
        }

        echo "<pre>";
        echo date('H:i')." - $msg";
        echo "</pre>";
        $b = ob_get_contents();

        file_put_contents($arquivo_log, $b, FILE_APPEND);
        ob_end_flush();
        ob_clean();
    }

    public function DelTmpLog($routine_schedule_log_id) {
        $arq = 'midea';
        $arquivo_log = "/tmp/exportacao-os-$arq-".date("Ymd").".txt";
 
        // Log //
        if(!$this->_TDocs->uploadFileS3($arquivo_log, $routine_schedule_log_id)){
            throw new \Exception("N<E3>o foi poss<ED>vel enviar o arquivo de log para o Tdocs. Erro: ".$this->_TDocs->error);
         } else {
            system("rm -f {$arquivo_log}");
         }
 
        // Log Simples//
        $arquivo_log_simpless = "/tmp/exportacao-os-simples-$arq-".date("Ymd").".txt";
        $this->_TDocs->setContext('fabrica', 'logsimples');
        if(!$this->_TDocs->uploadFileS3($arquivo_log_simpless, $routine_schedule_log_id, false)){
            throw new \Exception("N<E3>o foi poss<ED>vel enviar o arquivo de log para o Tdocs. Erro: ".$this->_TDocs->error);
        } else {
            system("rm -f {$arquivo_log_simpless}");
        }
        return true;
    }
}
