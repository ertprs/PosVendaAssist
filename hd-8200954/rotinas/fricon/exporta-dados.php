<?php
try {
    include __DIR__."/../../dbconfig.php";
    include __DIR__."/../../includes/dbconnect-inc.php";

    if ($_serverEnvironment == "development") {
        exit("\nNão executar\n\n");
    }

    //Banco de produção cuidado
    $dbfricon = pg_connect("host=postgres-telecontrol.cej5gqiwq3dv.us-east-1.rds.amazonaws.com dbname=tc_fricon user=root password=tele6588");

    /**
     * Prepara os campos e valores (já tratados) para o insert/update
     * @param  array $values  pg_fetch_array/pg_fetch_assoc
     * @param  array $columns array com as colunas da tabela e seus tipos
     * @return array          contendo as colunas com os seus respectivos valores
     */
    function prepareData($values, $columns) {
        $arr = array();

        foreach ($columns as $c => $t) {
            $v = $values[$c];

            switch ($t) {
                case "numeric":
                    $v = (!strlen($v)) ? "null" : $v;
                    break;

                case "string":
                    $v = utf8_decode("E'{$v}'");
                    break;

                case "boolean":
                    $v = ($v == "t") ? "true" : "false";
                    break;
                
                case "date":
                    $v = (!strlen($v)) ? "null" : "'{$v}'";
                    break;
            }

            $arr[$c] = $v;
        }

        return $arr;
    }

    function getColumns($table) {
        global $con;

        $sql = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '{$table}'";
        $res = pg_query($con, $sql);

        $columns = array();

        foreach (pg_fetch_all($res) as $i => $column) {
            switch ($column["data_type"]) {
                case 'character':
                case 'text':
                case 'character varying':
                    $type = "string";
                    break;

                case 'double precision':
                case 'integer':
                    $type = "numeric";
                    break;

                case 'date':
                case 'timestamp without time zone':
                    $type = "date";
                    break;

                case 'boolean':
                    $type = "boolean";
                    break;
            }

            $columns[$column["column_name"]] = $type;
        }

        return $columns;
    }

    ob_start();

    /**
     * OS - vw_os_fricon > os_fricon
     */
    $errors = array();

    echo "Table: os_fricon\n";

    echo $sql = "SELECT * FROM vw_os_fricon";
    $res = pg_query($con, $sql);

    echo "Total: ".pg_num_rows($res)."\n";
    echo "--------------------------------------\n";

    if (pg_num_rows($res) > 0) {
        echo $columns = getColumns("vw_os_fricon");

        $newOSs = 0;
        $updatedOSs = 0;

        while ($row = pg_fetch_array($res)) {
            echo $row["os"]." -> ";

            $data = prepareData($row, $columns);

            #unset($data["status_checkpoint_dias"], $data["protocolo"], $data["classificacao_os"]);

            $sqlOsExists = "SELECT os FROM os_fricon WHERE os = {$row['os']}";
            $qryOsExists = pg_query($dbfricon, $sqlOsExists);

            if (pg_num_rows($qryOsExists) > 0) {
                $update = array();

                foreach ($data as $key => $value) {
                    $update[] = "$key = $value";
                }

                $sqlOs = "
                    UPDATE os_fricon SET
                        ".implode(", ", $update)."
                    WHERE os = {$row['os']}
                ";
		$sqlOs = mb_convert_encoding($sqlOs, 'latin1', 'utf8,latin1');

                pg_query($dbfricon, $sqlOs);

                $updatedOSs++;
                echo "atualizada - OS\n";
            } else {
                $sqlOs = "
                    INSERT INTO os_fricon 
                    (".implode(", ", array_keys($data)).")
                    VALUES 
                    (".implode(", ", $data).")
                ";
		$sqlOs = mb_convert_encoding($sqlOs, 'latin1', 'utf8,latin1');

                pg_query($dbfricon, $sqlOs);

                $newOSs++;
                echo "nova - OS\n";
            }
            
            if (strlen(pg_last_error($dbfricon)) > 0) {
                $errors[$row["os"]] = pg_last_error($dbfricon);
		echo $sqlOs;
		echo pg_last_error();
		echo "E R R O: os_fricon";
		die;
            }
        }

        echo "--------------------------------------\n";
        echo "Novas OSs: $newOSs\n";
        echo "OSs Atualizadas: $updatedOSs\n";
        echo "Erros ".count($errors)."\n";

        if (count($errors) > 0) {
            print_r($errors);
        }
    } else {
        echo "Sem resultado\n";
    }

    echo "======================================\n\n";
    /**
     * OS Item - vw_os_item_fricon > os_item_fricon
     */
    $errors = array();

    echo "Table: os_item_fricon\n";

    $sql = "SELECT * FROM vw_os_item_fricon";
    $res = pg_query($con, $sql);

    echo "Total: ".pg_num_rows($res)."\n";
    echo "--------------------------------------\n";

    if (pg_num_rows($res) > 0) {
        $columns = getColumns("vw_os_item_fricon");

        $newItens = 0;
        $updatedITens = 0;

        while ($row = pg_fetch_array($res)) {
            echo $row["os"]." / ".$row["peca_referencia"]." -> ";

            $data = prepareData($row, $columns);

            #echo $sqlItemExists = "SELECT os, peca_referencia FROM os_item_fricon WHERE os = {$row['os']} AND peca_referencia = '{$row['peca_referencia']}'";
	    $sqlItemExists = "SELECT os_item FROM os_item_fricon WHERE os_item = {$row['os_item']} ";
            $qryItemExists = pg_query($dbfricon, $sqlItemExists);

            if (pg_num_rows($qryItemExists) > 0) {
                $update = array();

                foreach ($data as $key => $value) {
                    $update[] = "$key = $value";
                }

                #$sqlItem = "
                #    UPDATE os_item_fricon SET
                #        ".implode(", ", $update)."
                #    WHERE os = {$row['os']}
                #    AND peca_referencia = '{$row['peca_referencia']}'
                #";
 		$sqlItem = "
                    UPDATE os_item_fricon SET
                        ".implode(", ", $update)."
                    WHERE os_item = {$row['os_item']}
                ";
                pg_query($dbfricon, $sqlItem);

                $updatedITens++;
                echo "atualizado - OSItem\n";
            } else {
                $sqlItem = "
                    INSERT INTO os_item_fricon 
                    (".implode(", ", array_keys($data)).")
                    VALUES 
                    (".implode(", ", $data).")
                ";
                pg_query($dbfricon, $sqlItem);

                $newItens++;
                echo "novo - OSItem\n";
            }
            
            if (strlen(pg_last_error($dbfricon)) > 0) {
                echo $errors[$row["os"]."_".$row["peca_referencia"]] = pg_last_error($dbfricon);
		echo "E R R O: os_item_fricon";
		die;
            }
        }

        echo "--------------------------------------\n";
        echo "Novos Itens: $newItens\n";
        echo "Itens Atualizados: $updatedITens\n";
        echo "Erros ".count($errors)."\n";

        if (count($errors) > 0) {
            print_r($errors);
        }
    } else {
        echo "Sem resultado\n";
    }

    echo "======================================\n\n";

    /**
     * Historico - vw_os_historico_fricon > os_historico_fricon
     */
    $errors = array();

    echo "Table: os_historico_fricon\n";

    $sql = "SELECT * FROM vw_os_historico_fricon";
    $res = pg_query($con, $sql);

    echo "Total: ".pg_num_rows($res)."\n";
    echo "--------------------------------------\n";

    if (pg_num_rows($res) > 0) {
        $columns = getColumns("vw_os_historico_fricon");

        $newHds = 0;
        $updatedHds = 0;

        while ($row = pg_fetch_array($res)) {
            echo $row["os"]." -> ";

            $data = prepareData($row, $columns);

            $sqlHdExists = "SELECT os FROM os_historico_fricon WHERE os = {$row['os']}";
            $qryHdExists = pg_query($dbfricon, $sqlHdExists);

            if (pg_num_rows($qryHdExists) > 0) {
                $update = array();

		$sql_his = "SELECT status_checkpoint,data FROM os_historico_fricon WHERE os = {$row['os']} order by 2 DESC LIMIT 1 ";
                $res_his = pg_query($dbfricon, $sql_his);

		$status_checkpoint = pg_fetch_result($res_his,0,status_checkpoint);

		echo "STATUS: $status_checkpoint";
		echo "STATUS ANTIGO: {$row['status_checkpoint']}";
		#die;

		if ($status_checkpoint <>  ($row['status_checkpoint'])  ){
			$sqlHd = "
			    INSERT INTO os_historico_fricon
				    (".implode(", ", array_keys($data)).")
			    VALUES
				    (".implode(", ", $data).")
			";
			pg_query($dbfricon, $sqlHd);
		$newHds++;
		echo "novo - historico\n";
		}
            } else {
                $sqlHd = "
                    INSERT INTO os_historico_fricon 
                    (".implode(", ", array_keys($data)).")
                    VALUES 
                    (".implode(", ", $data).")
                ";
                pg_query($dbfricon, $sqlHd);

                $newHds++;
                echo "novo - historicoo\n";
            }
            
            if (strlen(pg_last_error($dbfricon)) > 0) {
                $errors[$row["os_historico"]] = pg_last_error($dbfricon);
		echo "E R R O: os_historico_fricon";
		die;
            }
        }

        echo "--------------------------------------\n";
        echo "Novos HDs: $newHds\n";
        echo "HDs Atualizados: $updatedHds\n";
        echo "Erros ".count($errors)."\n";

        if (count($errors) > 0) {
            print_r($errors);
        }
    } else {
        echo "Sem resultado\n";
    }

    echo "======================================\n\n";

#    /**
#     * Atendimento Interações - vw_callcenter_item_colormaq > callcenter_item_colormaq
#     */
#    $errors = array();

#    echo "Table: callcenter_item_colormaq\n";

#    $sql = "SELECT * FROM vw_callcenter_item_colormaq";
#    $res = pg_query($con, $sql);
#
#    echo "Total: ".pg_num_rows($res)."\n";
#    echo "--------------------------------------\n";
#
#    if (pg_num_rows($res) > 0) {
#        $columns = getColumns("vw_callcenter_item_colormaq");
#
#        $newItens = 0;
#
#        while ($row = pg_fetch_array($res)) {
#            echo $row["callcenter"]." -> ";
#
#            $data = prepareData($row, $columns);
#
#            $sqlItem = "
#                INSERT INTO callcenter_item_colormaq 
#                (".implode(", ", array_keys($data)).")
#                VALUES 
#                (".implode(", ", $data).")
#            ";
#            pg_query($dbcolormaq, $sqlItem);
#
#            $newItens++;
#            echo "novo\n";
#            
#            if (strlen(pg_last_error($dbcolormaq)) > 0) {
#                $errors[$row["callcenter"]][] = pg_last_error($dbcolormaq);
#            }
#        }
#
#        echo "--------------------------------------\n";
#        echo "Novos Itens: $newItens\n";
#        echo "Erros ".count($errors)."\n";
#
#        if (count($errors) > 0) {
#            print_r($errors);
#        }
#    } else {
#        echo "Sem resultado\n";
#    }
#
#    echo "======================================\n\n";
#
    $log = ob_get_contents();
    ob_end_flush();

    //mail("guilherme.curcio@telecontrol.com.br", "Colormaq - Exportação de Dados", $log);
    mail("marisa.silvana@telecontrol.com.br, marisa.silvana@telecontrol.com.br", "Fricon - Exportação de Dados", $log);
} catch(Exception $e) {
    echo "\n\nERRO NA EXECUÇÃO DA ROTINA\n";
    echo $e->getMessage()."\n";

    $log = ob_get_contents();
    ob_end_flush();

    //mail("guilherme.curcio@telecontrol.com.br", "Colormaq - Exportação de Dados", $log);
    mail("marisa.silvana@telecontrol.com.br, marisa.silvana@telecontrol.com.br", "Fricon - Exportação de Dados", $log);
}
