<?php
include __DIR__."/../../dbconfig.php";
include __DIR__."/../../includes/dbconnect-inc.php";

echo "\n";

$postos = array(
    61921  => "B0653DB",
    604580 => "B3801",
    63029  => "B0567BA",
    62512  => "B0323BA",
    30499  => "B3639"
);

$sql = "SELECT lancamento FROM tbl_lancamento WHERE fabrica = 169 AND descricao = 'OSs FILIAIS'";
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
    $avulso = pg_fetch_result($res, 0, 'lancamento');

    foreach ($postos as $codigo_fornecedor => $codigo_posto) {
        if (empty($codigo_posto)) {
            continue;
        }

	echo "codigo fornecedor: $codigo_fornecedor\n";
	echo "codigo posto: $codigo_posto\n";

        $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = 169 AND codigo_posto = '$codigo_posto'";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $posto = pg_fetch_result($res, 0, 'posto');

	    echo "id posto: $posto\n";

            $sql = "SELECT extrato, data_geracao FROM tbl_extrato WHERE fabrica = 169 AND posto = $posto";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $extratos = pg_fetch_all($res);

		echo "extratos: ".count($extratos)."\n";

                $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = 169 AND conta_contabil = '$codigo_fornecedor' AND posto NOT IN($posto)";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    $filiais = array();

                    while ($row = pg_fetch_object($res)) {
                        $filiais[] = $row->posto;
                    }

		    echo "filiais: ".count($filiais)."\n";

                    foreach ($filiais as $filial) {
			echo "filial: $filial\n";

                        foreach ($extratos as $extrato) {
                            $sql = "SELECT extrato FROM tbl_extrato WHERE fabrica = 169 AND posto = $filial AND data_geracao = '{$extrato['data_geracao']}'";
                            $res = pg_query($con, $sql);

                            if (pg_num_rows($res) > 0) {
                                $extrato_filial = pg_fetch_result($res, 0, 'extrato');
				echo "extrato filial: $extrato_filial\n";

                                $sql = "SELECT os, COALESCE(valor_total_hora_tecnica, 0) AS valor FROM tbl_os_extra WHERE extrato = $extrato_filial";
                                $res = pg_query($con, $sql);

                                if (pg_num_rows($res) > 0) {
                                    while ($row = pg_fetch_object($res)) {
					echo "os filial: {$row->os}";
                                        $sql = "
                                            INSERT INTO tbl_extrato_lancamento 
                                            (posto, fabrica, extrato, descricao, lancamento, debito_credito, valor)
                                            VALUES 
                                            ($posto, 169, {$extrato['extrato']},'OS FILIAL {$row->os}', $avulso, 'C', {$row->valor})
                                        ";
                                        $res = pg_query($con, $sql);
                                    }
                                }

                                $sql = "UPDATE tbl_extrato SET fabrica = 0 WHERE fabrica = 169 AND extrato = $extrato_filial";
                                $res = pg_query($con, $sql);
                            }
                        }
                    }
                }
            }
        }

	echo "\n";
    }
}
