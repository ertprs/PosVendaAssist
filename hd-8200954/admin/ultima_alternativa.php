<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$sql="SELECT tbl_posto.posto , tbl_posto.nome , tbl_posto.cnpj , tbl_posto_fabrica.codigo_posto , tbl_posto_fabrica.distribuidor , tbl_tipo_posto.descricao AS tipo_posto , tbl_extrato.extrato , tbl_extrato.liberado , tbl_extrato.aprovado , lpad (tbl_extrato.protocolo,5,'0') AS protocolo , to_char (tbl_extrato.data_geracao,'dd/mm/yyyy') as data_geracao , tbl_extrato.total , count (tbl_os.os) AS qtde_os , to_char (tbl_extrato_pagamento.data_pagamento,'dd/mm/yyyy') as baixado , tbl_extrato_pagamento.valor_liquido FROM tbl_extrato JOIN tbl_posto USING (posto) JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = 1 JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = 1 LEFT JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato LEFT JOIN tbl_os ON tbl_os.os = tbl_os_extra.os AND tbl_os.posto = tbl_extrato.posto AND tbl_os.fabrica = tbl_extrato.fabrica LEFT JOIN tbl_extrato_pagamento ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato WHERE tbl_extrato.fabrica = 1 AND tbl_posto_fabrica.distribuidor IS NULL AND tbl_extrato.aprovado IS NULL AND tbl_extrato.data_geracao BETWEEN '2006-09-18 00:00:00' AND '2006-09-25 23:59:59' GROUP BY tbl_posto.posto , tbl_posto.nome , tbl_posto.cnpj , tbl_posto_fabrica.codigo_posto , tbl_posto_fabrica.distribuidor , tbl_tipo_posto.descricao , tbl_extrato.extrato , tbl_extrato.liberado , tbl_extrato.total, tbl_extrato.aprovado, lpad (tbl_extrato.protocolo,5,'0'), tbl_extrato.data_geracao, tbl_extrato_pagamento.data_pagamento, tbl_extrato_pagamento.valor_liquido ORDER BY tbl_posto_fabrica.codigo_posto, tbl_extrato.data_geracao";

$res = pg_exec ($con,$sql);

if (pg_numrows ($res) == 0) {
	echo "<center><h2>Nenhum extrato encontrado</h2></center>";
}
// echo "$sql";
$j=0;
if (pg_numrows ($res) > 0) {

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$posto          = trim(pg_result($res,$i,posto));
		$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
		$nome           = trim(pg_result($res,$i,nome));
		$tipo_posto     = trim(pg_result($res,$i,tipo_posto));
		$extrato        = trim(pg_result($res,$i,extrato));
		$data_geracao   = trim(pg_result($res,$i,data_geracao));
		$qtde_os        = trim(pg_result($res,$i,qtde_os));
		$total          = trim(pg_result($res,$i,total));
		$baixado        = trim(pg_result($res,$i,baixado));
		$extrato        = trim(pg_result($res,$i,extrato));
		$distribuidor   = trim(pg_result($res,$i,distribuidor));
		$total	        = number_format ($total,2,',','.');
		$liberado       = trim(pg_result($res,$i,liberado));
		$aprovado       = trim(pg_result($res,$i,aprovado));
		$protocolo      = trim(pg_result($res,$i,protocolo));
		if($data_geracao=='25/09/2006')$j++;

		if($posto == $posto_anterior){
			if($data_geracao =='25/09/2006' AND$data_anterior =='18/09/2006' ){
				//echo $nome;
				$sql = "
				BEGIN;<br>
				UPDATE tbl_os_extra SET extrato = $extrato WHERE extrato =  $extrato_anterior;<br>
				update tbl_extrato_lancamento set extrato = $extrato WHERE extrato =  $extrato_anterior;<br>
				DELETE FROM tbl_extrato WHERE extrato = $extrato_anterior;<br>
				SELECT fn_calcula_extrato(1,$extrato);<br>
				COMMIT;<br>";
				//echo "<br>$nome $sql<br><br>";

			}
		}
//		echo "$posto - $data<br>";
//		echo "$posto_anterior - $data_anterior";
		$extrato_anterior = $extrato ;
		$posto_anterior   = $posto;
		$data_anterior    =  $data_geracao; 
	}
}

echo $j;

