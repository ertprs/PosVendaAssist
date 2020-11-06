<?php
require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

//pg_query($con, "BEGIN");

echo $sql = "SELECT posto, contato_cep, codigo_posto FROM tbl_posto_fabrica WHERE fabrica = 169 AND contato_cep IS NOT NULL AND LENGTH(contato_cep) > 0 and credenciamento = 'CREDENCIADO' and codigo_posto in ('B0819DF','B0798RB','B3753','B0269DF','B3289','B1109DF','B0123BF','B0212RF','B2057','B3834','B3776','B3367','B0780DF','B0957DF','B2074','B0649SF','B1263RB','B3401','B0077RF','B3727','B1042RF','B2049','B3398','B0539DF','B3127','B2055','B3591','B0770FG','B0197BG','B0838HD','B0874DH','B0677DH','B3001','B3729','B1226FA','B3767','B1629','B1368DA','B1095DB','B3066','B0225DA','B3599','B3045','B3351','B3335','B1873','B3680','B3710','B3702','B1107DB','B0527DA','B3378','B0785DA','B2060','B1117DB','B0645SA','B0630DA','B3437','B0323BA','B3317','B3470','B1591','B0042DA','B1519','B1392RA','B1209DA','B1865','B1319','B3410','B0403DA','B1294DA','B1440FA','B1617','B0994RA','B2084','B0570DA','B3089','B1466RA','B3584','B1848','B3632','B3827','B01016DA','B0299RA','B1855','B2082','B1111DA','B1082DA','B1405DA','B3121','B3299','B1058DA','B3167','B3436','B3515','B1845','B3388','B0756RD','B1329RG','B3331','B3457','B3375','B1432DH','B3422','B1843','B3571','B3829','B0901DB','B3534','B3761','B3686','B2040','B3362','B1053RB','B1448RB','B1453DG','B0177RC','B3000','B0081DC','B2003','B3136','B2095','B3799','B0896DB','B0842RB','B1830','B3385','B3654','B1827','B1583','B3265','B0808DB','B1818','B0647SC','B3269','B3424','B3798','B3779','B1814','B3411','B0751DC','B1810','B2102','B1812','B0784DB','B2031','B3393','B3682','B1807','B3318','B3667','B3278','B0179DB','B2061','B0429DB','B3568','B1283DB','B0319RB','B0243DB','B1375','B1407RB','B0650SC','B1100DB','B0679DB','B1052DB','B0109DB','B3225','B3404','B0012DB','B3439','B0105DB','B1507','B3491','B1243DB','B0055DB','B3514','B0653DB','B3181','B1645','B3592','B1803','B1339RB','B1072DB','B0178RC','B3526','B3377','B3544','B3820','B0078DF','B0054DF','B0002RG','B0095DA','B01015DF','B0101DE','B0335DF','B0348DH','B0543DF','B0657RG','B0594DF','B0686DC','B0954DF','B0886RB','B1249TA','B1252TA','B1075DH','B1129DA','B1314RF','B1337DA','B1334RG','B1379DA','B1367DB','B1423RA','B1417RF','B1439DG','B1497','B1613','B1661','B1721','B1727','B1903','B2100','B3092','B3260','B3314','B3408','B3459','B3481','B3461','B3532','B3541','B3577','B3588','B3638','B3685','B3828','B3692','B3504','B0723','B1588')";


$res = pg_query($con, $sql);

echo "\n";

try {
    while ($row = pg_fetch_object($res)) {
        $soapClient = new SoapClient("https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl");
        try {
            $endereco = $soapClient->consultaCEP(array("cep" => preg_replace("/\D/", "", $row->contato_cep)));
        } catch(Exception $e) {
            echo "\nerro ".$row->codigo_posto." - ".$row->contato_cep;
            continue;
        }




        if (is_object($endereco)) {
            $logradouro = utf8_decode(trim($endereco->return->end));
            $bairro     = utf8_decode($endereco->return->bairro);

	     if(!empty($logradouro)) {
		$sql = "UPDATE tbl_posto_fabrica SET
                 	   	contato_bairro = E'".addslashes($bairro)."',
                   		contato_endereco = E'".addslashes($logradouro)."'
               		 WHERE fabrica = 169
                	 AND posto = {$row->posto}";

           	 $res_posto = pg_query($con,$sql);

            	 if (strlen(pg_last_error()) > 0) {
			echo pg_last_error();
                	pg_query($con, "ROLLBACK");
                	throw new Exception("Erro no update $sql");
            	 } else {
                	echo "\natualizado ".$row->posto." - ".$row->contato_cep." - ".$logradouro."-$bairro";
            	 }
     	   } else {

		echo "\n posto com cidade cep unico";
	  }
        } else {
	    var_dump($endereco);
            echo "\nerro ".$row->posto." - ".$row->contato_cep;
        }
    }
} catch(Exception $e) {
    echo $e->getMessage();
}

//pg_query($con, "COMMIT");
