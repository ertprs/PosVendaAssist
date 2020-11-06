<?php
include __DIR__."/../../dbconfig.php";
include __DIR__."/../../includes/dbconnect-inc.php";


$sql = "SELECT o.os, o.os_posto, o.data_fechamento, o.qtde_km, o.qtde_km_calculada, o.valores_adicionais, tp.descricao as tipo_posto, ta.descricao AS tipo_atendimento  FROM tbl_os o INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = 169 INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = 169 INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND (ta.fora_garantia IS NOT TRUE OR (ta.fora_garantia IS TRUE AND ta.grupo_atendimento = 'I')) WHERE o.fabrica = 169 AND o.posto != 6359 AND o.finalizada IS NOT NULL AND o.excluida IS NOT TRUE AND o.os NOT IN(47894613, 47953685, 48268368, 48278064, 47953986, 47894778, 48075006, 48510406, 48311724, 47911276, 47947061, 48227870, 48251901, 47807346, 48026526, 48031569, 47809830, 48037037, 48287733, 48310327, 48318860, 48287734, 48317559, 48251902, 48069065, 47809304, 47899365, 47923019, 47923375, 48085860, 48100939, 48133547, 48142533, 48145356, 48154570, 48160780, 48162086, 48191569, 48212617, 48216553, 48289296, 48289304)";
$res = pg_query($con, $sql);

echo "\n";
echo "os;extrato;auditoria;tipo_posto;tipo_atendimento;km;km_pago;va_pago;va;erro\n";

$ultima_os = null;

$urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/PSA.asmx?WSDL";
$client = new \SoapClient($urlWSDL, array('trace' => 1));

while ($row = pg_fetch_object($res)) {
    try {
        $ultima_os = $row->os;
        $extrato = null;

        $sql = "
            SELECT * FROM tbl_auditoria_os WHERE os = {$row->os} AND liberada IS NULL AND cancelada IS NULL AND reprovada IS NULL
        ";
        $resAuditoria = pg_query($con, $sql);

        if (pg_num_rows($resAuditoria) > 0) {
            $sql = "SELECT extrato FROM tbl_os_extra WHERE os = {$row->os}";
            $resExtrato = pg_query($con, $sql);

            if (!strlen(pg_fetch_result($resExtrato, 0, 'extrato'))) {
                $auditoria = 1;
            } else {
		$auditoria = 1;
	        $extrato = pg_fetch_result($resExtrato, 0, 'extrato');
	    }
        } else {
	    $auditoria = 0;
	}

        $sql = "
            SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = {$row->os}
        ";
        $resValoresAdicionais = pg_query($con, $sql);

        if (!strlen(pg_fetch_result($resValoresAdicionais, 0, 'valores_adicionais'))) {
            $valores_adicionais = 0;
        } else {
            $valores_adicionais = 1;
        }

        if (empty($row->os_posto)) {
            echo "{$row->os};{$extrato};{$auditoria};{$row->tipo_posto};{$row->tipo_atendimento};{$row->qtde_km};{$row->qtde_km_calculada};{$row->valores_adicionais};{$valores_adicionais};não foi para o SAP\n";
        } else {
            $response = false;

            while ($response == false) { 
                try {
                    $xmlRequest = "
                        <ns1:xmlDoc>
                            <criterios>
                                <PF_ORDEM_IN>
                                    <item>
                                        <QMNUM>{$row->os}</QMNUM>
                                    </item>
                                </PF_ORDEM_IN>
                                <PV_OPCAO>E</PV_OPCAO>
                            </criterios>
                        </ns1:xmlDoc>
                    ";
                    $params = new \SoapVar($xmlRequest, XSD_ANYXML);

                    $array_params = array("xmlDoc" => $params);
                    $result       = $client->PesquisaOrdemServico($array_params);
                    $dados_xml    = $result->PesquisaOrdemServicoResult->any;
                    $xml          = simplexml_load_string($dados_xml);
                    $xml          = json_decode(json_encode((array)$xml), TRUE);
                    $response = true;
                } catch (\Throwable $t) {
                    $response = false;
                } catch(\Exception $e) {
                    $response = false;
                }
            }

            $data_fechamento = $xml["NewDataSet"]["ZCBSM_DADOS_ORDEM_SERVICOTable"]["GLTRP"];

            if (empty($data_fechamento) || $data_fechamento == "0000-00-00") {
                echo "{$row->os};{$extrato};{$auditoria};{$row->tipo_posto};{$row->tipo_atendimento};{$row->qtde_km};{$row->qtde_km_calculada};{$row->valores_adicionais};{$valores_adicionais};não finalizada no SAP\n";
            } else if (!empty($extrato)) {
                echo "{$row->os};{$extrato};{$auditoria};{$row->tipo_posto};{$row->tipo_atendimento};{$row->qtde_km};{$row->qtde_km_calculada};{$row->valores_adicionais};{$valores_adicionais};em auditoria finalizada no SAP\n";
            }
        }
    } catch(\Exception $e) {
        echo "\nERRO\n";
        echo "OS {$ultima_os}\n";
        echo $e->getMessage()."\n";
        echo "\n";
    }
}
