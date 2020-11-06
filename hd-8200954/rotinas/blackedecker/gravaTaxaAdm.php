<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../../funcoes.php';
require dirname(__FILE__) . '/../../class/json.class.php';

$sql = "SELECT tbl_posto_fabrica.posto,
	tbl_extrato.extrato,
        tbl_posto_fabrica.reembolso_peca_estoque,
        COALESCE(tbl_posto_fabrica.parametros_adicionais, '{}') AS parametros_adicionais
FROM    tbl_extrato
JOIN    tbl_posto_fabrica USING(posto,fabrica)
WHERE   fabrica = 1
and extrato=3697498
-- AND     tbl_posto_fabrica.parametros_adicionais IS NOT NULL

ORDER BY posto";
echo $sql;
$res = pg_query($con,$sql);

while ($dados = pg_fetch_object($res)) {

    echo "Rodando posto: ".$dados->posto."\n";

    $postoPA = new Json($dados->parametros_adicionais);

$extrato = $dados->extrato; 
var_dump($postoPA);
    pg_query($con,"BEGIN TRANSACTION");
    if ($postoPA->recebeTaxaAdm == 'sim') {
		$sqlOs = "
			update tbl_os set data_conserto = data_fechamento from tbl_os_extra where tbl_os.os = tbl_os_extra.os and data_conserto isnull and extrato = $extrato  and tipo_atendimento isnull; 

			update tbl_os_campo_extra set campos_adicionais = campos_adicionais::jsonb -'TxAdmGrad' from tbl_os_extra join tbl_os using(os) left join tbl_os_produto using(os) where tbl_os_campo_extra.os = tbl_os_extra.os  and extrato = $extrato  and (tipo_atendimento in  (17,18,35) or tbl_os_produto.os isnull) ;

            SELECT  DISTINCT
                    tbl_os.os,
                    tbl_os.sua_os
            FROM    tbl_os
            JOIN    tbl_os_extra    USING(os)
            JOIN    tbl_os_produto  USING(os)
            JOIN    tbl_os_item     USING(os_produto)
            JOIN    tbl_peca        USING(peca)
            WHERE   tbl_os.fabrica = 1
            AND     tbl_os.posto = ".$dados->posto."
            AND     tbl_os.data_conserto        IS NOT NULL
            AND     tbl_os.data_fechamento      IS NOT NULL
and tbl_peca.produto_acabado is not true
            AND     tbl_os_extra.extrato        = $extrato
            AND     tbl_os.data_abertura > '2017-01-01'
            AND     tbl_os.os NOT IN (SELECT os FROM tbl_os_excluida WHERE fabrica = 1)
      ORDER BY      tbl_os.os
        ";
		echo $sqlOs;
        $resOs = pg_query($con,$sqlOs);

        while($osCalculaTaxa = pg_fetch_object($resOs)) {
            if(!calculaTaxaAdministrativa($con,1,$dados->posto,$osCalculaTaxa->os)) {
                pg_query($con,"ROLLBACK TRANSACTION");
                echo "Problemas na OS: ".$dados->posto.$osCalculaTaxa->sua_os;
            }
        }
    }

    pg_query($con,"COMMIT TRANSACTION");
}

echo "Rodou tudo. Conferir\n";
exit;
