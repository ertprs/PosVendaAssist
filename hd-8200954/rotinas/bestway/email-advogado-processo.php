<?php
try{

	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    $fabrica = 81;

	$sql = "SELECT DISTINCT processo,
							tbl_processo.numero_processo,
							tbl_admin.nome_completo as nome,
							tbl_admin.email as email,
							to_char(tbl_processo.data_audiencia1, 'DD/MM/YYYY') as data_audiencia1,
							to_char(tbl_processo.data_audiencia2, 'DD/MM/YYYY') as data_audiencia2,
							(tbl_processo.data_audiencia1 - current_date) as data_audienciax1,
							(tbl_processo.data_audiencia2 - current_date) as data_audienciax2
					FROM tbl_processo
					JOIN tbl_admin on (tbl_processo.admin = tbl_admin.admin)
					WHERE tbl_processo.fabrica = $fabrica
					AND ((date(data_audiencia1) = current_date + interval '1 day')
							OR (date(data_audiencia2) = current_date + interval '1 day'))
					ORDER BY processo;";

	$res = pg_query($con,$sql);

	$total = pg_num_rows($res);

	if($total > 0){

		for ($i=0; $i < $total; $i++) {
			$num_processo		= pg_fetch_result($res,$i, 'processo');
			$nome				= pg_fetch_result($res,$i, 'nome');
			$data_audiencia1 	= pg_fetch_result($res,$i, 'data_audiencia1');
			$numero_processo 	= pg_fetch_result($res, $i, 'numero_processo');
			$data_audienciax1 	= pg_fetch_result($res,$i, 'data_audienciax1');

			$data_audiencia2  	= pg_fetch_result($res,$i, 'data_audiencia2');

			$data_audienciax2  	= pg_fetch_result($res,$i, 'data_audienciax2');

			//$vet['dest'][]		= pg_fetch_result($res,$i, 'email');

			$vet['dest'][] = 'guilherme.monteiro@telecontrol.com.br';

			$titulo_email = "Audiência Processo $numero_processo";

			if ($data_audienciax1 == 1) {
				$msg = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
									Prezado ".$nome.",<br><br><br>
									A audiência 1 do processo $numero_processo está marcada para o dia: $data_audiencia1.<br><br>
									Favor confirmar presença do preposto.";
				Log::envia_email($vet,$titulo_email,$msg);
			}
			if ($data_audienciax2 == 1) {
				$msg = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
									Prezado".$nome.",<br><br><br>
									A audiência 2 do processo $numero_processo está marcada para o dia: $data_audiencia2.<br><br>
									Favor confirmar presença do preposto.";
				Log::envia_email($vet,$titulo_email,$msg);
			}
		}
	}
}catch(Exception $e){
	echo $e->getMessage();
}
