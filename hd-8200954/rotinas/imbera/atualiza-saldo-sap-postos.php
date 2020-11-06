<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

echo "\n";
echo "Iniciando ".date("Y-m-d H:i")."\n";

pg_query($con, "
    CREATE TEMP TABLE tmp_imbera_saldo_sap (
        peca_id int,
        peca_referencia text,
        qtde int,
        qtde_usada int,
        saldo int,
        posto_id int,
        deposito text,
	nota_fiscal text,
	pedido text
    );
");
echo "Tabela temporária criada\n";

echo "Iniciando processamento do arquivo...\n";

$arquivo  = "entrada/saldo_sap_postos_5.csv";
$conteudo = file_get_contents($arquivo);
$linhas   = explode("\n", $conteudo);

echo count($linhas)." peças\n";

echo "Processando...";
foreach ($linhas as $linha) {
    list($peca, $qtde, $qtde_usada, $saldo, $deposito, $nota_fiscal, $pedido) = explode(";", $linha);

    $qtde = str_replace(",", ".", $qtde);
    $qtde = (int) $qtde;

    $qtde_usada = str_replace(",", ".", $qtde_usada);
    $qtde_usada = (int) $qtde_usada;

    $saldo = str_replace(",", ".", $saldo);
    $saldo = (int) $saldo;

    $peca = (int) $peca;

    pg_query($con, "
        INSERT INTO tmp_imbera_saldo_sap 
        (peca_referencia, qtde, qtde_usada, saldo, deposito, nota_fiscal, pedido)
        VALUES
        ('{$peca}', {$qtde}, {$qtde_usada}, {$saldo}, '{$deposito}', '{$nota_fiscal}', '{$pedido}');
    ");
}
echo "Arquivo processado\n";

echo "Iniciando atualização das peças...\n";

pg_query($con, "
    UPDATE tmp_imbera_saldo_sap
    SET peca_id = tbl_peca.peca
    FROM tbl_peca
    WHERE tbl_peca.fabrica = 158
    AND tmp_imbera_saldo_sap.peca_referencia = tbl_peca.referencia;
");

echo "Atualização de peças finalizadas\n";

echo "Procurando por registros que não foi possível encontrar a peça...\n";

$res = pg_query($con, "
    SELECT * FROM tmp_imbera_saldo_sap WHERE peca_id IS NULL;
");

$res = array_map(function($r) {
	        return $r["peca_referencia"];
}, pg_fetch_all($res));


echo "Registros sem peça: ".count($res)."\n";
if (count($res) > 0) {
	echo "Peças não encontradas: ".implode(", ", $res)."\n";
}

echo "Iniciando atualização dos postos...\n";

pg_query($con, "
    UPDATE tmp_imbera_saldo_sap
    SET posto_id = tbl_posto_fabrica.posto
    FROM tbl_posto_fabrica
    WHERE tbl_posto_fabrica.fabrica = 158
    AND tmp_imbera_saldo_sap.deposito = tbl_posto_fabrica.centro_custo;
");

echo "Atualização de postos finalizadas\n";

echo "Procurando por registros que não foi possível encontrar o posto...\n";

$res = pg_query($con, "
    SELECT * FROM tmp_imbera_saldo_sap WHERE posto_id IS NULL;
");

$res = array_map(function($r) {
	return $r["posto_codigo"];
}, pg_fetch_all($res));

echo "Registros sem posto: ".count($res)."\n";

if (count($res) > 0) {
	echo "Postos não encontrados: ".implode(", ", $res)."\n";
}

echo "Deletando registros sem peça ou posto...\n";

$delete = pg_query($con, "
    DELETE FROM tmp_imbera_saldo_sap WHERE peca_id IS NULL OR posto_id IS NULL;
");

echo pg_affected_rows($delete)." registros deletados\n";

echo "Iniciando atualização do saldo...\n";

$estoque = pg_query($con, "
    SELECT * 
    FROM tmp_imbera_saldo_sap;
");

/*$estoque = pg_query($con, "
	SELECT tmp.*
	FROM tbl_estoque_posto_movimento epm
	JOIN tmp_imbera_saldo_sap tmp ON tmp.posto_id = epm.posto AND tmp.peca_id = epm.peca
	WHERE epm.fabrica = 158
	AND epm.qtde_entrada IS NOT NULL
	AND epm.qtde_entrada = tmp.qtde
	AND (epm.pedido::TEXT = tmp.pedido
	OR (epm.nf::INT = tmp.nota_fiscal::INT
	AND tmp.pedido = 'INI'));
");*/

while ($e = pg_fetch_object($estoque)) {
    try {
        pg_query($con, "BEGIN");

        echo "\n";
        echo "Peça: {$e->peca_referencia}\n";
        echo "Depósito: {$e->deposito}\n";
        echo "\n";

        echo "Buscando nota fiscal mais antiga com saldo...\n";

        $qryNfAntigaSaldo = pg_query($con, "
            SELECT nota_fiscal AS nf
            FROM tmp_imbera_saldo_sap
            WHERE peca_id = {$e->peca_id}
            AND posto_id = {$e->posto_id}
	    AND saldo > 0
	    ORDER BY nota_fiscal::INT ASC
	    LIMIT 1;
        ");

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro interno #10");
        }

        if (!pg_num_rows($qryNfAntigaSaldo)) {
            echo "Nenhuma nota fiscal encontrada\n";
	    echo "Marcando todo o estoque como devolvido...";


	    $sql = "
		UPDATE tbl_estoque_posto_movimento
		SET qtde_usada = qtde_entrada
                WHERE fabrica = 158
                AND posto = {$e->posto_id}
                AND peca = {$e->peca_id}
                AND qtde_entrada IS NOT NULL;
	    ";

	    pg_query($con, $sql);

	    echo $sql."\n";

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro interno #9");
            }

            echo "Finalizado\n";
        } else {
            $nf_antiga_saldo = (int) pg_fetch_result($qryNfAntigaSaldo, 0, "nf");

            echo "Nota Fiscal mais antiga com saldo: {$nf_antiga_saldo}\n";
	    echo "Matando o saldo de todas as notas anteriores a {$nf_antiga_saldo}...";

	    $sql = "
		UPDATE tbl_estoque_posto_movimento
		SET qtde_usada = qtde_entrada
                WHERE fabrica = 158
                AND posto = {$e->posto_id}
                AND peca = {$e->peca_id}
                AND qtde_entrada IS NOT NULL
                AND nf::integer < {$nf_antiga_saldo};
	    ";

	    pg_query($con, $sql);

	    echo $sql."\n";

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro interno #8");
            }

            echo "Finalizado\n";

	    echo "Atualizando saldo fiscal do estoque...";

	    $sql = "
		SELECT
		    peca_id,
		    peca_referencia,
		    posto_id,
		    deposito,
		    nota_fiscal,
		    qtde,
		    qtde_usada,
		    saldo
                FROM tmp_imbera_saldo_sap
                WHERE posto_id = {$e->posto_id}
                AND peca_id = {$e->peca_id}
		AND nota_fiscal::integer >= {$nf_antiga_saldo}
                ORDER BY nota_fiscal::integer ASC;
	    ";

            $qryNfSaldo = pg_query($con, $sql);

	    echo $sql."\n";

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro interno #7");
            }

            foreach (pg_fetch_all($qryNfSaldo) as $nf) {
		$nf = (object) $nf;
		
		$sql = "
		    SELECT
		        epm.*
		    FROM tbl_estoque_posto_movimento epm
		    JOIN tbl_posto_fabrica pf USING(posto,fabrica)
		    JOIN tbl_tipo_posto tp USING(tipo_posto,fabrica)
		    WHERE epm.fabrica = 158
		    AND epm.posto = {$nf->posto_id}
		    AND epm.peca = {$nf->peca_id}
		    AND epm.qtde_entrada = {$nf->qtde}
		    AND epm.nf::int = {$nf->nota_fiscal}::int;
		";
		
		$sqlNfTele = pg_query($con, $sql);

		echo $sql."\n";

		if (pg_num_rows($sqlNfTele) > 0) {
		    if ($nf->saldo > 0) {

			echo "Atualizando saldo fiscal conforme saldo SAP\n"; 
			$nf_saldo = true;

			$sql = "
			    UPDATE tbl_estoque_posto_movimento
			    SET qtde_usada = qtde_entrada - {$nf->saldo}
                            WHERE fabrica = 158
                            AND posto = {$nf->posto_id}
                            AND peca = {$nf->peca_id}
                            AND nf::INTEGER = {$nf->nota_fiscal}::INTEGER
                            AND qtde_entrada IS NOT NULL;
			";

                    	$update = pg_query($con, $sql);
			echo $sql."\n";

		    } else {
		    
			$nf_saldo = false;

			$sql = "
			    UPDATE tbl_estoque_posto_movimento
			    SET qtde_usada = qtde_entrada
                            WHERE fabrica = 158
                            AND posto = {$nf->posto_id}
                            AND peca = {$nf->peca_id}
                            AND nf::INTEGER = {$nf->nota_fiscal}::INTEGER
                            AND qtde_entrada IS NOT NULL;
			";
		    
			$update = pg_query($con, $sql);
			echo $sql."\n";

                    }

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro interno #6");
                    }

		    /*if (pg_affected_rows($update) == 0 && $nf_saldo == true) {
		        $nfNaoEncontrada = pg_query($con, "
			    SELECT * FROM tbl_faturamento WHERE fabrica = 158 AND nota_fiscal::integer = {$nf->nota_fiscal}::integer
		        ");
		        echo $nf->nota_fiscal."\n";
		        var_dump(pg_fetch_assoc($nfNaoEncontrada));
                        //throw new Exception("Nota Fiscal {$nf->nota_fiscal} não encontrada, Saldo {$nf->saldo}");
		    }*/

		} else {

			echo "Nota Fiscal não foi faturada para esse Posto.\n";
			echo "Depósito: {$nf->deposito}, Nota Fiscal: {$nf->nota_fiscal}, Peça: {$nf->peca_referencia}.\n";
			$semEntrada[] = "Depósito: {$nf->deposito}, Nota Fiscal: {$nf->nota_fiscal}, Peça: {$nf->peca_referencia}.\n";

			/*echo "Verificando se peça em estoque\n";

			$sql = "
				SELECT
					*
				FROM tbl_estoque_posto
				WHERE fabrica = 158
				AND posto = {$nf->posto_id}
				AND peca = {$nf->peca_id};
			";

			$qryEstoque = pg_query($con, $sql);

			echo $sql."\n";

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Error Interno #11");
			}

			if (pg_num_rows($qryEstoque) > 0) {
				echo "Peça faz parte do estoque do técnico\n";
				echo "Inserindo nova movimentação de estoque de nota não encontrada\n";

				$sql = "
					UPDATE tbl_estoque_posto_movimento SET
						qtde_usada = qtde_entrada
					WHERE fabrica = 158
					AND posto = {$nf->posto_id}
					AND peca = {$nf->peca_id}
					AND nf::int = {$nf->nota_fiscal}
					AND qtde_entrada IS NOT NULL;
				";

				$update = pg_query($con, $sql);

				echo $sql."\n";

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Error Interno #5");
				}

				$sql = "
					INSERT INTO tbl_estoque_posto_movimento (
						qtde_saida,
						peca,
						posto,
						obs,
						fabrica
					) VALUES (
						{$nf->saldo},
						{$nf->peca_id},
						{$nf->posto_id},
						'Saída para conciliação do saldo fiscal ".date('d/m/Y')."',
						158
					);
				";

				pg_query($con, $sql);

				echo $sql."\n";
				
				if (strlen(pg_last_error()) > 0) {
					throw new Exception ("Error Interno #12");
				}

				if ($nf->saldo > 0) {

					$sql = "
						INSERT INTO tbl_estoque_posto_movimento (
							nf,
							qtde_entrada,
							peca,
							posto,
							obs,
							fabrica
						) VALUES (
							'0000{$nf->nota_fiscal}',
							{$nf->saldo},
							{$nf->peca_id},
							{$nf->posto_id},
							'Entrada para conciliação do saldo fiscal ".date('d/m/Y')."',
							158
						);
					";

					$insert = pg_query($con, $sql);

					echo $sql."\n";

					if (strlen(pg_last_error()) > 0) {
						throw new Exception("Erro Interno #4");
					}
				}
			} else {
				echo "Peça não faz parte do estoque do técnico\n";
			}*/
		}
            }

            echo "Finalizado\n";
        }

        echo "Procurando pedidos de peças aguardando faturamento...";
	
	$sql = "
	    SELECT
	    	p.pedido,
		pi.pedido_item,
		(pi.qtde - (pi.qtde_faturada + pi.qtde_cancelada)) AS qtde
            FROM tbl_pedido_item pi
            INNER JOIN tbl_pedido p ON p.pedido = pi.pedido AND p.fabrica = 158
            INNER JOIN tbl_tipo_pedido tp ON tp.tipo_pedido = p.tipo_pedido AND tp.fabrica = 158
            LEFT JOIN tbl_faturamento_item fi ON fi.pedido_item = pi.pedido_item
            WHERE tp.codigo = 'BON'
            AND p.status_pedido IN(2,5)
            AND p.exportado IS NOT NULL
            AND p.posto = {$e->posto_id}
            AND pi.peca = {$e->peca_id}
            AND fi.faturamento_item IS NULL
            AND (pi.qtde_cancelada + pi.qtde_faturada) < pi.qtde
            ORDER BY p.data ASC;
	";
        $qryPedidosPendentes = pg_query($con, $sql);

	echo $sql."\n";

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro interno #3");
        }

        if (!pg_num_rows($qryPedidosPendentes)) {
            echo "Nenhum pedido de peça encontrado\n";
        } else {
            $pedidos = pg_fetch_all($qryPedidosPendentes);

            echo count($pedidos)." pedidos encontrados\n";

            echo "Abatendo saldo do estoque de acordo com os pedidos...\n";

            foreach ($pedidos as $pedido) {
                $qtde = $pedido["qtde"];

		while ($qtde > 0) {
			
		    $sql = "
			SELECT
			    qtde_entrada AS qtde,
			    COALESCE(qtde_usada, 0) AS qtde_usada,
			    faturamento,
			    nf::INTEGER
                        FROM tbl_estoque_posto_movimento
                        WHERE fabrica = 158
                        AND posto = {$e->posto_id}
                        AND peca = {$e->peca_id}
                        AND COALESCE(qtde_usada, 0) < qtde_entrada
                        AND qtde_entrada IS NOT NULL
                        ORDER BY nf::integer ASC;
		    ";

                    $qrySaldoEstoque = pg_query($con, $sql);

		    echo $sql."\n";

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro interno #1");
                    }

		    if (!pg_num_rows($qrySaldoEstoque)) {
			break;
                        //throw new Exception("Não foi encontrado nota fiscal para abater o saldo\n");
		    }

		    $saldo_estoque = pg_fetch_assoc($qrySaldoEstoque);

                    if ($saldo_estoque["qtde_usada"] < $qtde) {
                        $qtde_update = $qtde - $saldo_estoque["qtde_usada"];
                    } else {
                        $qtde_update = $qtde;
		    }

		    $sql = "
			UPDATE tbl_estoque_posto_movimento
			SET qtde_usada = COALESCE(qtde_usada, 0) + {$qtde_update}
                        WHERE fabrica = 158
                        AND posto = {$e->posto_id}
                        AND peca = {$e->peca_id}
                        AND nf = '0000{$saldo_estoque['nf']}';
		    ";

                    $qryUpdateSaldoEstoque = pg_query($con, $sql);

		    echo $sql."\n";

		    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro interno #2");
                    }

                    $qtde -= $qtde_update;
                }
            }

            echo "Finalizado\n";
        }

	echo "Saldo atualizado!\n";

	pg_query($con, "COMMIT");
    } catch(Exception $e) {
        pg_query($con, "ROLLBACK");

        echo "\n";
	echo "Erro ao atualizar saldo: ".$e->getMessage()."\n";
    }

    echo "#############################\n";
}

echo implode(":::::", $semEntrada);
