<?

namespace Posvenda\Fabricas\_158;

class ExportaPedido
{

    private $pedido;
    private $os;
    private $_fabrica;
    private $_serverEnvironment;
    private $url;
    private $url2;

    public function __construct(\Posvenda\Pedido $pedido, \Posvenda\Os $os, $fabrica)
    {

        $this->pedido = $pedido;
        $this->os = $os;
        $this->_fabrica = $fabrica;

        include "/etc/telecontrol.cfg";
	$this->_serverEnvironment = $_serverEnvironment;

	if ($this->_serverEnvironment == 'development') {
		# Verificar local para seus testes - Comentado para gerar erro e o progrmador ajustar
        # include "/www/assist/www/rotinas/imbera/funcoes.php";
        include_once "./rotinas/imbera/funcoes.php";
	} else {
		include_once "/www/assist/www/rotinas/imbera/funcoes.php";
	}

	$this->url = urlSap(true);
	$this->url2 = urlSap();  
    }

    public function getOsGeraPedido($posto, $marca = null, $os = null)
    {

        $pdo = $this->pedido->_model->getPDO();

        $auditoria_unica        = \Posvenda\Regras::get("auditoria_unica", "pedido_garantia", $this->_fabrica);
        $verifica_tbl_os_troca  = \Posvenda\Regras::get("verifica_tbl_os_troca", "pedido_garantia", $this->_fabrica);
        $posto_interno_nao_gera = \Posvenda\Regras::get("posto_interno_nao_gera", "pedido_garantia", $this->_fabrica);

        if ($marca != null) {
            $whereMarca = "AND tbl_produto.marca = :marca";
        }

        if ($os != null) {
            $whereOsNumero = "AND tbl_os.os = :os";
        }

        if ($verifica_tbl_os_troca == true) {
            $joinOsTroca  = "LEFT JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os AND tbl_os_troca.produto = tbl_os_produto.produto";
            $whereOsTroca = "AND tbl_os_troca.os_troca IS NULL";
        } else {
            $whereOsTroca = "AND tbl_os.troca_garantia IS NULL AND tbl_os.troca_garantia_admin IS NULL";
        }

        if ($posto_interno_nao_gera == true) {
            $wherePostoInterno = "AND tbl_tipo_posto.posto_interno IS NOT TRUE";
        }

        if (is_array($posto)) {
            $lista_postos = implode(",",$posto);

            $wherePosto = "AND tbl_posto_fabrica.posto IN ($lista_postos)";
        } else {
            $wherePosto = "AND tbl_posto_fabrica.posto = :posto";
        }

        $sql = "
            SELECT
                DISTINCT tbl_os.os
            FROM tbl_os
            JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            {$joinOsTroca}
            JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
            JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = :fabrica
            JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = :fabrica
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = :fabrica
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = :fabrica
            JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = :fabrica
            LEFT JOIN tbl_pedido ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_pedido.fabrica = :fabrica
            LEFT JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = :fabrica
            WHERE tbl_os.fabrica = :fabrica
            AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
            AND tbl_servico_realizado.gera_pedido IS TRUE
            AND tbl_servico_realizado.peca_estoque IS NOT TRUE
            AND tbl_os.excluida IS NOT TRUE
            AND ((tbl_os_item.pedido IS NULL
            AND tbl_os_item.pedido_item IS NULL)
            OR (tbl_pedido.status_pedido = 1
            AND tbl_tipo_pedido.garantia_antecipada IS NOT TRUE))
            AND tbl_peca.produto_acabado IS NOT TRUE
            {$wherePostoInterno}
            {$whereOsTroca}
            {$whereMarca}
            {$whereOsNumero}
            {$wherePosto};
	";
        $query = $pdo->prepare($sql);

        if ($marca != null) {
            $query->bindParam(':marca', $marca, \PDO::PARAM_INT);
        }

        if ($os != null) {
            $query->bindParam(':os', $os, \PDO::PARAM_INT);
        }

        $query->bindParam(':posto', $posto, \PDO::PARAM_INT);
        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
        $query->execute();
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if(count($res) > 0){

            $os_pedido_pedido = array();

            for($i = 0; $i < count($res); $i++){

                $os = $res[$i]["os"];

                if($auditoria_unica == true){
                    if($this->os->_model->consultaAuditoriaOS($os)){
                        $os_pedido_pedido[] = array("os" => $os);
                    }
                }else if($this->os->_model->verificaOsIntervencaoGarantia($os)){
                    $os_pedido_pedido[] = array("os" => $os);
                }
            }

            return $os_pedido_pedido;

        }else{
            return false;
        }

    }

    public function getPedidoBonGar($posto = null, $pedido = null)
    {

        if (empty($posto)) {
            throw new \Exception("Posto não informado para selecionar os pedidos");
        }

        $pdo = $this->pedido->_model->getPDO();

        $posto_interno_nao_gera = \Posvenda\Regras::get("posto_interno_nao_gera", "pedido_garantia", $this->_fabrica);

        if ($posto_interno_nao_gera == true) {
            $wherePostoInterno = "AND tp.posto_interno IS NOT TRUE";
        }

        if ($pedido != null) {
            $wherePostoInterno = "AND pd.pedido = :pedido";
        }

        if (is_array($posto)) {
            $lista_postos = implode(",",$posto);

            $wherePosto = "AND pf.posto IN ($lista_postos)";
        } else {
            $wherePosto = "AND pf.posto = :posto";
        }

        $sql = "
            SELECT
                DISTINCT pd.pedido
            FROM tbl_pedido pd
            JOIN tbl_posto_fabrica pf ON pf.posto = pd.posto AND pf.fabrica = :fabrica
            JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = :fabrica
            JOIN tbl_tipo_pedido tpd ON tpd.tipo_pedido = pd.tipo_pedido AND tpd.fabrica = :fabrica
            WHERE pd.fabrica = :fabrica
            AND pf.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
            AND pd.status_pedido = 1
            AND tpd.garantia_antecipada IS TRUE
            AND tpd.pedido_em_garantia IS TRUE
            {$wherePostoInterno}
            {$wherePedido}
            {$wherePosto};
	";

        $query = $pdo->prepare($sql);

        if ($pedido != null) {
            $query->bindParam(':pedido', $pedido, \PDO::PARAM_INT);
        }

        $query->bindParam(':posto', $posto, \PDO::PARAM_INT);
        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
        $query->execute();
        
        return $query->fetchAll(\PDO::FETCH_ASSOC);

    }

    public function getPedido($os = null, $pedido = null, $posto_interno = 'f')
    {
        $pdo = $this->pedido->_model->getPDO();

        $posto_interno_nao_gera = \Posvenda\Regras::get("posto_interno_nao_gera", "pedido_garantia", $this->_fabrica);

        if ($os != null) {
            $whereOS = "AND o.os = :os";
        }

        if ($pedido != null) {
            $select = "
                pi.pedido_item,
                pi.qtde AS qtde_pedido,
            ";
            $from = "
                FROM tbl_pedido p
                JOIN tbl_pedido_item pi ON pi.pedido = p.pedido
                JOIN tbl_posto_fabrica pf ON pf.posto = p.posto AND pf.fabrica = :fabrica
                JOIN tbl_peca pc ON pc.peca = pi.peca AND pc.fabrica = :fabrica
                LEFT JOIN tbl_os_item oi ON oi.pedido_item = pi.pedido_item
                LEFT JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = :fabrica
                LEFT JOIN tbl_os_produto op ON op.os_produto = oi.os_produto
                LEFT JOIN tbl_os o ON o.os = op.os AND o.fabrica = :fabrica
                LEFT JOIN tbl_os_campo_extra oce ON oce.os = o.os AND oce.fabrica = :fabrica
                LEFT JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = :fabrica
            ";
            $where = "
                WHERE p.fabrica = :fabrica
                AND p.pedido = :pedido
            ";
        } else {
            $select = "
                oi.pedido_item,
                oi.qtde AS qtde_pedido,
            ";
            $from = "
                FROM tbl_os o
                LEFT JOIN tbl_os_campo_extra oce ON oce.os = o.os AND oce.fabrica = :fabrica
                JOIN tbl_os_produto op ON op.os = o.os
                JOIN tbl_os_item oi ON oi.os_produto = op.os_produto
                JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = :fabrica
                JOIN tbl_peca pc ON pc.peca = oi.peca AND pc.fabrica = :fabrica
                JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = :fabrica
                JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = :fabrica
                LEFT JOIN tbl_pedido p ON p.pedido = oi.pedido AND p.fabrica = :fabrica
                LEFT JOIN tbl_pedido_item pi ON pi.pedido_item = oi.pedido_item
            ";
            $where = "
                WHERE o.fabrica = :fabrica
                AND (sr.gera_pedido IS TRUE OR tp.posto_interno IS TRUE)
            ";
            $orderBy = "ORDER BY o.os DESC";
        }

        if ($posto_interno_nao_gera == true && $posto_interno == 'f') {
            $wherePostoInterno = "AND tp.posto_interno IS NOT TRUE";
        }

        $sql = "
            SELECT
                pc.peca,
                pc.referencia,
                pc.referencia||' - '||fn_retira_especiais(pc.descricao) AS desc_peca,
                pc.unidade,
                ti.preco,
                o.os,
                pf.codigo_posto,
                pf.centro_custo,
                ta.codigo AS codigo_tipo_atendimento,
                ta.descricao AS desc_tipo_atendimento,
                oi.os_item,
                {$select}
                p.pedido,
                p.status_pedido,
                p.tipo_pedido,
                tpd.pedido_em_garantia,
                tpd.garantia_antecipada,
                tpd.descricao AS desc_tipo_pedido,
                TO_CHAR(p.data, 'YYYYMMDD') AS data_pedido,
                f.nota_fiscal AS nf,
                oce.campos_adicionais
            {$from}
            JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = :fabrica
            LEFT JOIN tbl_tipo_pedido tpd ON tpd.tipo_pedido = p.tipo_pedido AND tpd.fabrica = :fabrica
            LEFT JOIN tbl_faturamento_item fi ON fi.pedido = p.pedido AND fi.pedido_item = pi.pedido_item
            LEFT JOIN tbl_faturamento f ON f.faturamento = fi.faturamento
            LEFT JOIN tbl_tabela t ON t.tabela = p.tabela AND t.fabrica = :fabrica
            LEFT JOIN tbl_tabela_item ti ON ti.tabela = t.tabela AND ti.peca = pc.peca
            {$where}
            AND (pi.qtde - pi.qtde_cancelada > 0 OR oi.pedido_item IS NULL)
            {$wherePostoInterno}
            {$whereOS}
            {$orderBy};
        ";
        $query = $pdo->prepare($sql);

        if ($os != null) {
            $query->bindParam(':os', $os, \PDO::PARAM_INT);
        }

        if ($pedido != null) {
            $query->bindParam(':pedido', $pedido, \PDO::PARAM_INT);
        }

        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getPedidoNTP($fabrica, $pedido, $status)
    {
        $pdo = $this->pedido->_model->getPDO();
        $posto_interno_nao_gera = \Posvenda\Regras::get("posto_interno_nao_gera", "pedido_garantia", $this->_fabrica);

        if (!empty($pedido)) {
            $wherePedido = "AND p.pedido = :pedido";
        }

        if (!empty($status)) {
            $whereStatus = "AND p.status_pedido = :status";
        }

        if ($posto_interno_nao_gera == true) {
            $wherePostoInterno = "AND tp.posto_interno IS NOT TRUE";
        }

        $sql = "
            SELECT
                pc.peca,
                pc.referencia,
                pc.referencia||' - '||fn_retira_especiais(pc.descricao) AS desc_peca,
                pc.unidade,
                o.os,
                pf.codigo_posto,
                pf.centro_custo,
                ta.codigo AS codigo_tipo_atendimento,
                ta.descricao AS desc_tipo_atendimento,
                oi.os_item,
                pi.pedido_item,
                pi.qtde AS qtde_pedido,
                p.pedido,
                p.status_pedido,
                p.tipo_pedido,
                tpd.pedido_em_garantia,
                tpd.garantia_antecipada,
                TO_CHAR(p.data, 'YYYYMMDD') AS data_pedido,
                oce.campos_adicionais
            FROM tbl_pedido p
            JOIN tbl_pedido_item pi ON pi.pedido = p.pedido
            JOIN tbl_posto_fabrica pf ON pf.posto = p.posto AND pf.fabrica = :fabrica
            JOIN tbl_peca pc ON pc.peca = pi.peca AND pc.fabrica = :fabrica
            JOIN tbl_os_item oi ON oi.pedido_item = pi.pedido_item
            JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = :fabrica
            JOIN tbl_os_produto op ON op.os_produto = oi.os_produto
            JOIN tbl_os o ON o.os = op.os AND o.fabrica = :fabrica
            JOIN tbl_os_campo_extra oce ON oce.os = o.os AND oce.fabrica = :fabrica
            JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = :fabrica
            JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = :fabrica
            JOIN tbl_tipo_pedido tpd ON tpd.tipo_pedido = p.tipo_pedido AND tpd.fabrica = :fabrica AND tpd.garantia_antecipada IS NOT TRUE AND tpd.pedido_faturado IS NOT TRUE AND tpd.pedido_em_garantia IS TRUE AND tpd.uso_consumo IS NOT TRUE
            WHERE p.fabrica = :fabrica
            {$wherePedido}
            {$whereStatus}
	    {$wherePostoInterno}
	    ORDER BY o.os;
        ";

        $query = $pdo->prepare($sql);

        if (!empty($pedido)) {
            $query->bindParam(':pedido', $pedido, \PDO::PARAM_INT);
        }

        if (!empty($status)) {
            $query->bindParam(':status', $status, \PDO::PARAM_INT);
        }

        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(\PDO::FETCH_ASSOC);

    }

    public function getTipoPedido($codigo)
    {
        if (empty($this->_fabrica)) {
            throw new \Exception("Fabrica não informada para selecionar o tipo de pedido garantia");
        }

        if (empty($codigo)) {
            throw new \Exception("Informe um código para buscar o tipo do pedido");
        }

        $this->pedido->_model->select('tbl_tipo_pedido')
            ->setCampos(array('tipo_pedido'))
            ->addWhere(array('fabrica' => $this->_fabrica))
            ->addWhere("UPPER(codigo) = UPPER('{$codigo}')");

        if (!$this->pedido->_model->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar o tipo de pedido garantia");
        }

        $res = $this->pedido->_model->getPDOStatement()->fetch();

        if (!empty($res["tipo_pedido"])) {
            $tipo_pedido = $res["tipo_pedido"];
        }

        return $tipo_pedido;
    }

    public function pedidoIntegracao($pedidos, $tipo_exportacao, $faturado = false)
    {

        if (is_array($pedidos)) {
            foreach ($pedidos as $os => $dados) {
                foreach ($dados as $xpedido => $value) {
		    $dadosPedidos = ""; 
		    if (in_array($value['unidade_negocio'], array(6200,6108,6107,6103,6102,6101,6104,6105,6106)) && $tipo_exportacao != 'posto_interno') {
			     $value['unidade_negocio'] = 6004;
		    }
                    if ((!empty($value['unidade_negocio']) && $value['status_pedido'] == 1) || $faturado == true) {
                        if ($tipo_exportacao == "aumento_kit") {
                            $funcao = "MatNoKit";
			    foreach ($value['pecas'] as $peca) {
                                $dadosPedidos .= "
                                    <T_ENTRADA>
                                        <P_TELEC>".$xpedido."</P_TELEC>
                                        <CENTRO>".$value['unidade_negocio']."</CENTRO>
                                        <CLIENTE>7310</CLIENTE>
                                        <DATA>".$value['data_pedido']."</DATA>
                                        <TECNICO>".$value['centro_custo']."</TECNICO>
                                        <MATERIAL>".$peca['referencia']."</MATERIAL>
                                        <CANTIDAD>".$peca['qtde_pedido']."</CANTIDAD>
                                        <UM>".$peca['unidade']."</UM>
                                    </T_ENTRADA>\n
                                ";
                            }
                        } else if ($tipo_exportacao == "posto_interno") {
                            $funcao = "TOrdenPiso";
			    foreach ($value['pecas'] as $peca) {
                                $dadosPedidos .= "
                                    <T_ENTRADA>
                                        <CENTRO>".$value['unidade_negocio']."</CENTRO>
                                        <O_TELEC>".$os."</O_TELEC>
                                        <TIPO_O>".$value['codigo_tipo_atendimento']."</TIPO_O>
                                        <TECNICO>".$value['centro_custo']."</TECNICO>
                                        <MATERIAL>".$peca['referencia']."</MATERIAL>
                                        <CANTIDAD>".$peca['qtde_pedido']."</CANTIDAD>
                                        <UM>".$peca['unidade']."</UM>
                                        <CLIENTE>7310</CLIENTE>
                                        <DATA>".date('Ymd')."</DATA>
                                    </T_ENTRADA>\n
                                ";
                            }
                        } else if ($tipo_exportacao == "cobranca_kof") {
                            $funcao = "TConsnKit";
                            foreach ($value['pecas'] as $peca) {
                                $dadosPedidos .= "
                                    <T_ENTRADA>
                                        <P_TELEC>".$xpedido."</P_TELEC>
                                        <CENTRO>".$value['unidade_negocio']."</CENTRO>
                                        <CLIENTE>7310</CLIENTE>
                                        <DATA>".$value['data_pedido']."</DATA>
                                        <O_TELEC>".$os."</O_TELEC>
                                        <TIPO_O>".$value['codigo_tipo_atendimento']."</TIPO_O>
                                        <TECNICO>".$value['centro_custo']."</TECNICO>
                                        <MATERIAL>".$peca['referencia']."</MATERIAL>
                                        <CANTIDAD>".$peca['qtde_pedido']."</CANTIDAD>
                                        <UM>".$peca['unidade']."</UM>
                                        <NF>".$peca['nf']."</NF>
                                    </T_ENTRADA>\n
                                ";
                            }
                        } else {
                            $funcao = "MatNoKit";
                            foreach ($value['pecas'] as $peca) {
                                $dadosPedidos .= "
                                    <T_ENTRADA>
                                        <P_TELEC>".$xpedido."</P_TELEC>
                                        <CENTRO>".$value['unidade_negocio']."</CENTRO>
                                        <CLIENTE>7310</CLIENTE>
                                        <DATA>".$value['data_pedido']."</DATA>
                                        <O_TELEC>".$os."</O_TELEC>
                                        <TIPO_O>".$value['codigo_tipo_atendimento']."</TIPO_O>
                                        <TECNICO>".$value['centro_custo']."</TECNICO>
                                        <MATERIAL>".$peca['referencia']."</MATERIAL>
                                        <CANTIDAD>".$peca['qtde_pedido']."</CANTIDAD>
                                        <UM>".$peca['unidade']."</UM>
                                        <NF>".$peca['nf']."</NF>
                                    </T_ENTRADA>\n
                                ";
                            }
                        }



			if ($this->_serverEnvironment == 'development') {

			    $url = $this->url."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_{$funcao}_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

			    $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

			} else {

			    $url = $this->url."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_{$funcao}_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

			    $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

			}

			$xml_post_string = '
                            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tel="http://imbera.com/telecontrol">
                                <soapenv:Header/>
                                <soapenv:Body>
                                    <tel:MT_'.$funcao.'_Req>
                                       '.$dadosPedidos.'
                                    </tel:MT_'.$funcao.'_Req>
                                </soapenv:Body>
                            </soapenv:Envelope>
			';

			$headers = array(
                            "Content-type: text/xml;charset=\"utf-8\"",
                            "Accept: text/xml",
                            "Cache-Control: no-cache",
                            "Pragma: no-cache",
                            "Content-length: ".strlen($xml_post_string),
                            $authorization
                        );

                        $ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$retornoCurl = curl_exec($ch);
			$erroCurl = curl_error($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
 
			$retornoCurl = preg_replace(("/(<\/?)(\w+):([^>]*>)/"),"$1$2$3",$retornoCurl);

			$retornoXML = new \SimpleXMLElement(utf8_encode($retornoCurl));
			$retornoXML = $retornoXML->xpath('//T_MENSAGEM');
			$retornoSoap = json_decode(json_encode((array) $retornoXML), true);
			$retornoSoap = $retornoSoap[0];

			if ($this->_serverEnvironment == "development") {
                            $file = fopen('/tmp/imbera-ws.log','a');
                        } else {
                            $file = fopen('/mnt/webuploads/imbera/logs/imbera-ws.log','a');
			}

                        fwrite($file, 'Resquest \n\r');
			fwrite($file, "URL: {$url} \n\r");
                        fwrite($file, $xml_post_string);

			fwrite($file, 'Response \n\r');
			fwrite($file, 'Error Curl: '.$erroCurl.'\n\r');
			fwrite($file, 'Http Code: '.$httpcode.'\n\r');
			fwrite($file, 'Data Hora: '.date('Y-m-d h:i').'\n\r');
                        fwrite($file, utf8_decode($retornoCurl));
                        fclose($file);
						$pedidoExportado = false;
                        switch ($retornoSoap['ID']) {
                            case '001':
                                $obsPedido = "Técnico não possui estoque para essa peça - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                            case '002':
                                $obsPedido = "Pedido Exportado com Sucesso - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                if ($tipo_exportacao != "posto_interno") {
                                    $this->pedido->registrarPedidoExportado($xpedido);
                                }
                                $pedidoExportado = true;
                                break;

                            case '003':
                                $obsPedido = "O técnico não possui essa quantidade de peças em estoque - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                            case '004':
                                $obsPedido = "Pedido e OS já recebidos pelo SAP - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                if ($tipo_exportacao != "posto_interno") {
                                    $this->pedido->registrarPedidoExportado($xpedido);
                                }
                                $pedidoExportado = true;
                                break;

                            case '005':
                                $obsPedido = "Centro não encontrado - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                            case '006':
                                $obsPedido = "Unidade de medida incorreta - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                            case '007':
                                $obsPedido = "NF não pode ser vazia - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                            case '008':
                                $obsPedido = "NF incorreta - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                            case '012':
                                $obsPedido = "Centro/Deposito incorreto - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;
                            
                            default:
                                $obsPedido = "Ocorreu um erro durante a exportação do pedido - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                        }

                        if (in_array($tipo_exportacao, array("cobranca_kof", "posto_interno"))) {
                            $pdo = $this->pedido->_model->getPDO();

                            $sql = "
                                SELECT
                                    posto
                                FROM tbl_os
                                JOIN tbl_posto_fabrica USING(posto,fabrica)
                                WHERE os = :os
                                AND fabrica = :fabrica;
                            ";

                            $query = $pdo->prepare($sql);
                            $query->bindParam(':os', $os, \PDO::PARAM_INT);
                            $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
                            $query->execute();

                            $res = $query->fetch(\PDO::FETCH_ASSOC);

                            $posto = $res['posto'];

                            $sql = "
                                INSERT INTO tbl_os_interacao (
                                    programa,
                                    fabrica,
                                    os,
                                    posto,
                                    comentario,
                                    interno
                                ) VALUES (
                                    :programa,
                                    :fabrica,
                                    :os,
                                    :posto,
                                    :obs,
                                    :interno
                                );
                            ";

                            $interno = 1;

                            $query = $pdo->prepare($sql);
                            $query->bindParam(':programa', $_SERVER['PHP_SELF'], \PDO::PARAM_STR);
                            $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
                            $query->bindParam(':os', $os, \PDO::PARAM_INT);
                            $query->bindParam(':posto', $posto, \PDO::PARAM_INT);
                            $query->bindParam(':obs', $obsPedido, \PDO::PARAM_STR);
                            $query->bindParam(':interno', $interno, \PDO::PARAM_BOOL);
                            $query->execute();

                        } else {
                            $this->pedido->updateObservacao($xpedido, 'Retorno Exportação: '.$obsPedido);
                        }

                    }
                }
            }            
        }
        return $pedidoExportado;
    }

    public function pedidoIntegracaoSemDeposito($pedidos)
    {
        
        if (is_array($pedidos)) {
            foreach ($pedidos as $os => $dados) {
                foreach ($dados as $xpedido => $value) {
                    $cont = 1;
                    $dadosPedidos = "";

                    if (!empty($xpedido) && $value['status_pedido'] == 1) {

						$xpedido = is_numeric($xpedido) ? $xpedido : $os;
						$codigo_posto = ($value['codigo_posto'] == '1') ? $dados['codigo_posto'] : $value['codigo_posto'];
                        $dadosPedidos = "
                            <I_PEDIDOS>
                                <CABECALHO>
                                    <PEDIDO>".$xpedido."</PEDIDO>
                                    <KUNNR>".$value['codigo_posto']."</KUNNR>
                                    <TIPO_PEDIDO>ZGOB</TIPO_PEDIDO>
                                </CABECALHO>
                        ";

                        foreach ($value['pecas'] as $peca) {
                            $dadosPedidos .= "
                                <ITENS>
                                    <PEDIDO>".$xpedido."</PEDIDO>
                                    <SEQUENCIA>".$cont."</SEQUENCIA>
                                    <MATNR>".$peca['referencia']."</MATNR>
                                    <QUANTIDADE>".$peca['qtde_pedido']."</QUANTIDADE> 
                                    <PRECO>".$peca['preco']."</PRECO>
                                </ITENS>
                            ";
                            $cont++;
                        }

                        $dadosPedidos .= "</I_PEDIDOS>";

			if ($this->_serverEnvironment == 'development') {

			    $url = $this->url2."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_CriaOrdemVenda_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

			    $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

			    $file = fopen('/tmp/imbera-ws.log','a');

			} else {

			    $url = $this->url2."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_CriaOrdemVenda_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

			    $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

			    $file = fopen('/mnt/webuploads/imbera/logs/imbera-ws.log','a');

			}

                        $xml_post_string = '
			    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tel="http://imbera.com/telecontrol">
				<soapenv:Header/>
				<soapenv:Body>
				    <tel:MT_CriaOrdemVenda_Req>
					'.$dadosPedidos.'
				    </tel:MT_CriaOrdemVenda_Req>
				</soapenv:Body>
			    </soapenv:Envelope>
			';

			$headers = array(
                            "Content-type: text/xml;charset=\"utf-8\"",
                            "Accept: text/xml",
                            "Cache-Control: no-cache",
                            "Pragma: no-cache",
                            "Content-length: ".strlen($xml_post_string),
                            $authorization
                        );

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$retornoCurl = curl_exec($ch);
			$erroCurl = curl_error($ch);
                        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			
                        fwrite($file, 'Resquest \n\r');
                        fwrite($file, "URL: {$url} \n\r");
			fwrite($file, $xml_post_string);

			fwrite($file, 'Response \n\r');
			fwrite($file, 'Error Curl: '.$erroCurl.'\n\r');
			fwrite($file, 'Http Code: '.$httpcode.'\n\r');
			fwrite($file, 'Data Hora: '.date('Y-m-d h:i').'\n\r');
                        fwrite($file, utf8_decode($retornoCurl).'\n\r');

			if ($retornoCurl == false) {
				fwrite($file, 'A requisição não teve resposta do Web Service.');
			} else {

			    $retornoCurl = preg_replace(("/(<\/?)(\w+):([^>]*>)/"),"$1$2$3",$retornoCurl);
			    $retornoXML = new \SimpleXMLElement(utf8_encode($retornoCurl));
			    $retornoXML = $retornoXML->xpath('//MENSAGENS');
			    $retornoMsg = json_decode(json_encode((array) $retornoXML), true);

                            $obsPedido = "";
                            $msgSap = "";

                            /*
                            * Como a API de Criação de Ordens de venda retorna vários tipos de mensagens
                            * será necessário fazer todas essa validações do retorno para determinar corretamente
                            * gto que veio da API.
                            * A variável de validação é a $obsPedido, quando ela não estiver vazia o retorno foi encontrado
                            */

                            if (strlen($retornoMsg["NUMBER"]) > 0) {
                            	switch ($retornoMsg["NUMBER"]) {
                                    case 311:
                                        $obsPedido = "Pedido exportado com sucesso - Retorno SAP: ".utf8_decode($retornoMsg['MESSAGE']);
                                        $this->pedido->registrarPedidoExportado($xpedido);
                                        break;

                                    case '002':
                                        $obsPedido = "Pedido já havia sido exportado anteriormente - Retorno SAP: ".utf8_decode($retornoMsg['MESSAGE']);
                                        $this->pedido->registrarPedidoExportado($xpedido);
                                        break;

                                    case 200:
                                        $obsPedido = "Cliente não cadastrado no SAP - Retorno SAP: ".utf8_decode($retornoMsg['MESSAGE']);
                                        break;

                                    case 382:
                                        $obsPedido = "Peça não configurada corretamente - Retorno SAP: ".utf8_decode($retornoMsg['MESSAGE']);
                                        break;

                                    case 219:
                                        $obsPedido = "Pedido não pôde ser criado - Retorno SAP: ".utf8_decode($retornoMsg['MESSAGE']);
                                        break;
                                
                                    default:
                                        $obsPedido = "";
                                        $msgSap = " - Retorno SAP: ".utf8_decode($retornoMsg['MESSAGE']);
                                        break;
                                }
			    }

                            if (strlen($obsPedido) == 0) {
                            	foreach ($retornoMsg as $msg) {
				    $r = (array) $msg;
                                    switch ($r["NUMBER"]) {
                                        case 311:
                                            $obsPedido = "Pedido exportado com sucesso - Retorno SAP: ".utf8_decode($r['MESSAGE']);
                                            $this->pedido->registrarPedidoExportado($xpedido);
                                            break;

                                        case '002':
                                            $obsPedido = "Pedido já havia sido exportado anteriormente - Retorno SAP: ".utf8_decode($r['MESSAGE']);
                                            $this->pedido->registrarPedidoExportado($xpedido);
                                            break;

                                        case 200:
                                            $obsPedido = "Cliente não cadastrado no SAP - Retorno SAP: ".utf8_decode($r['MESSAGE']);
                                            break;

                                        case 382:
                                            $obsPedido = "Peça não configurada corretamente - Retorno SAP: ".utf8_decode($r['MESSAGE']);
                                            break;

                                        case 219:
                                            $obsPedido = "Pedido não pôde ser criado - Retorno SAP: ".utf8_decode($r['MESSAGE']);
                                            break;
                                    
                                        default:
                                            $obsPedido = "";
                                            $msgSap .= " - Retorno SAP: ".utf8_decode($r['MESSAGE'])."<br />";
                                            break;
				    }

                                    if (strlen($obsPedido) > 0) {
                                        break;
                                    }
                                }
                            }

                            if (strlen($obsPedido) == 0) {
                                $obsPedido = "Ocorreu um erro e o pedido não foi exportado".$msgSap;
                            }

                            $this->pedido->updateObservacao($xpedido, 'Retorno Exportação: '.$obsPedido);

			}

			fclose($file);

		    }
                }
            }            
        }
    }

    public function verificaExportado($pedido)
    {
        $pdo = $this->pedido->_model->getPDO();

        $sql = "SELECT exportado FROM tbl_pedido WHERE pedido = :pedido AND fabrica = :fabrica;";
        $query = $pdo->prepare($sql);
        $query->bindParam(':pedido', $pedido, \PDO::PARAM_INT);
        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
        $query->execute();
        $res = $query->fetch(\PDO::FETCH_ASSOC);

        if (!empty($res['exportado'])) {
            return true;
        } else {
            return false;
        }
    }

    public function getPostosEstados($estados)
    {
        $pdo = $this->pedido->_model->getPDO();

        if (is_array($estados)) {
            $estadosConsulta = implode("','", $estados);

            $sql = "SELECT posto,
                           codigo_posto,
                           nome,
                           estado
                    FROM tbl_posto_fabrica
                    JOIN tbl_posto USING(posto)
                    JOIN tbl_tipo_posto USING(tipo_posto,fabrica)
                    WHERE tbl_posto.estado IN ('$estadosConsulta')
                    AND tbl_tipo_posto.posto_interno IS NOT TRUE
                    AND tbl_tipo_posto.tecnico_proprio IS NOT TRUE
                    AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
                    AND tbl_posto_fabrica.fabrica = {$this->_fabrica}";
            $query = $pdo->prepare($sql);
            $query->execute();

        return $query->fetchAll(\PDO::FETCH_ASSOC);

        } else {
            return false;
        }
    }

    public function verificaTerceiroGarantia($os)
    {
	$pdo = $this->pedido->_model->getPDO();

	$sql = "	    
	    SELECT
	    	*
	    FROM tbl_os o
	    JOIN tbl_posto_fabrica pf USING(posto,fabrica)
	    JOIN tbl_tipo_posto tp USING(tipo_posto,fabrica)
	    JOIN tbl_tipo_atendimento ta USING(tipo_atendimento,fabrica)
	    WHERE o.os = :os
	    AND o.fabrica = :fabrica
	    AND ta.fora_garantia IS NOT TRUE
	    AND ta.grupo_atendimento IS NULL
	    AND tp.posto_interno IS NOT TRUE
	    AND tp.tecnico_proprio IS NOT TRUE;
	";

        $query = $pdo->prepare($sql);
        $query->bindParam(':os', $os, \PDO::PARAM_INT);
        $query->bindParam(':fabrica', $this->_fabrica, \PDO::PARAM_INT);
        $query->execute();
	$res = $query->fetch(\PDO::FETCH_ASSOC);

        if ($res != false) {
            return true;
        } else {
            return false;
        }
    }

}
