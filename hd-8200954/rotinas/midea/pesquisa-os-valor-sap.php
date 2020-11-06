<?php
include __DIR__."/../../dbconfig.php";
include __DIR__."/../../includes/dbconnect-inc.php";

$sql = "
    SELECT 
        o.os, o.qtde_km AS km, o.qtde_km_calculada::double precision AS valor_km, o.valores_adicionais::double precision AS valor_adicional, oe.extrato, pf.codigo_posto AS ct, tp.tipo_revenda AS revenda
    FROM tbl_os o 
    INNER JOIN tbl_os_extra oe ON oe.os = o.os
    INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = 169
    INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto
    WHERE o.fabrica = 169
    AND o.finalizada IS NOT NULL
    --AND oe.extrato IS NULL
    --AND o.os NOT IN(47972543, 48000117, 47971090, 47917495, 47917547, 47829039, 47827663, 47994018, 47843465, 47977879, 47914886, 47860131, 47994913, 47887900, 47887966, 47887995, 47875322, 47830494, 47858393, 47871951, 47856982, 47974938, 47856664, 47896370, 47851677, 47967533, 47862017, 47932058, 47895205, 47962857, 47993406, 47801101, 47968438, 47948327, 47820040, 47837908, 47840012, 47876709, 47938551, 47828686, 47861414, 47956139, 47898607, 47908943, 47864828, 47906296, 47861505, 47980127, 47976645, 47869172, 47934663, 47939045, 47846530, 47853795, 47871928, 47847288, 47901013, 47892694, 47832307, 47793325, 47810471, 47794019, 47867870, 47834993, 47985277, 47842842, 47888040, 47956308, 47939971, 47916433, 47924348, 47996383, 47972868, 47846102, 47855910, 47875742, 47817448, 47838338, 47974803, 47966154, 47888897, 47901027, 47962045, 47962595, 47940879, 47802125, 47856712, 47799164, 47947532, 47966291, 47855957, 47878489, 47879485, 47992049, 47972147, 47902812, 47881611, 47946628, 47797176, 47874028, 47855354, 47886828, 47959012, 47939871, 48011449, 47957107, 47903511, 47923024, 47822712, 47957083, 47958051, 47843554, 48002224, 47948768, 47831514, 47798543, 47957988, 47832032, 47920904, 47859702, 47859754, 47859818, 47859939, 47805346, 47978704, 47899570, 47826869, 47827024, 47972909, 47952356, 47910371, 48021347, 47871389, 47818398, 47794834, 47877829, 47846447, 47856956, 47817234, 47857175, 47927135, 47902837, 47938603, 47845797, 47889521, 47829227, 47845467, 47999235, 47903382, 47981852, 47880798)
    AND (o.qtde_km_calculada > 0 OR o.valores_adicionais > 0)
    AND o.os_posto IS NOT NULL
";
$res = pg_query($con, $sql);
echo pg_last_error();

$urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/PSA.asmx?WSDL";
$client = new \SoapClient($urlWSDL, array('trace' => 1));

echo "\nos;km;valor_km;valor_adicional;extrato;ct;revenda;aprovado_sem_valor\n";

while ($row = pg_fetch_object($res)) {
    $row->valor_km = number_format($row->valor_km, 2);
    $row->valor_adicional = number_format($row->valor_adicional, 2);

    $sql = "
        SELECT * FROM tbl_auditoria_os WHERE os = {$row->os} AND liberada IS NOT NULL AND paga_mao_obra IS NOT TRUE
    ";
    $resAuditoria = pg_query($con, $sql);

    if (pg_num_rows($resAuditoria) > 0) {
        $aprovado_sem_valor = "t";
    } else {
	$aprovado_sem_valor = "f";
    }

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
        } catch(\Exception $e) {
            $response = false;
        } catch(\Throwable $t) {
            $response = false;
        }
    }

    if ($xml["NewDataSet"]["ZCBSM_DADOS_ORDEM_SERVICOTable"]["GLTRP"] == "0000-00-00" || empty($xml["NewDataSet"]["ZCBSM_DADOS_ORDEM_SERVICOTable"]["GLTRP"])) {
	continue;
    }

    $km_sap = false;
    $valor_adicional_sap = false;

    if ((double) $row->valor_km > 0) {
        foreach ($xml["NewDataSet"]["ZCBSM_SERVICOSTable"] as $valor) {
            if ($valor["ASKTX"] == "Km Rodado" && (double) $valor["TBTWR"] == (double) $row->valor_km) {
                $km_sap = true;
            }
        }
    } else {
        $km_sap = true;
    }

    $va = 0;
    if ((double) $valor_adicional_sap > 0) {
        foreach ($xml["NewDataSet"]["ZCBSM_SERVICOSTable"] as $valor) {
	    if ($valor["ASKTX"] == "Serviço e terceiros") {
		$va++;
	    }
            if ($valor["ASKTX"] == "Serviço de terceiros" && (double) $valor["TBTWR"] == (double) $row->valor_adicional) {
                $valor_adicional_sap = true;
            }
        }
    } else {
        $valor_adicional_sap = true;
    }

    if (!$km_sap || !$valor_adicional_sap || ($valor_adicional_sap && $va == 1)) {
        echo $row->os.";".$row->km.";".$row->valor_km.";".$row->valor_adicional.";".$row->extrato.";".$row->ct.";".$row->revenda.";".$aprovado_sem_valor."\n";
    }
}
