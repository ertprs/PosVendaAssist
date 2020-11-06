<?php
try {
    include __DIR__."/../../dbconfig.php";
    include __DIR__."/../../includes/dbconnect-inc.php";

    if ($_serverEnvironment == "development") {
        exit("\nNão executar\n\n");
    }

    if (empty($argv[1])) {
        echo "É NECESSÁRIO PASSAR A FABRICA";
        die();
    }

    $fabrica = $argv[1];
 
    $num=3420+$fabrica;
    $banco = 'tc_0'. $num;
    echo "fabrica: ".$fabrica."banco: ".$banco;
    
    //Banco de produção cuidado
    #$dbfabrica = pg_connect("host=postgres-telecontrol.cej5gqiwq3dv.us-east-1.rds.amazonaws.com dbname=tc_03455 user=root password=tele6588");
    $dbfabrica = pg_connect("host=postgres-telecontrol.cej5gqiwq3dv.us-east-1.rds.amazonaws.com dbname=".$banco." user=root password=tele6588");

    /**
     * Prepara os campos e valores (já tratados) para o insert/update
     * @param  array $values  pg_fetch_array/pg_fetch_assoc
     * @param  array $columns array com as colunas da tabela e seus tipos
     * @return array          contendo as colunas com os seus respectivos valores
     */

    function prepareData($values, $columns) {
        $arr = array();

        foreach ($columns as $c => $t) {
            $v = pg_escape_string($values[$c]);

       
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
     * OS - vw_os_replica > os_replica
     */
    $errors = array();

    echo "Table: os_fabrica\n";

    $sql = "SELECT * FROM vw_os_replica where fabrica=$fabrica";
    $res = pg_query($con, $sql);

    echo "Total: ".pg_num_rows($res)."\n";
    echo "--------------------------------------\n";

    if (pg_num_rows($res) > 0) {
        echo $columns = getColumns("vw_os_replica");

        $newOSs = 0;
        $updatedOSs = 0;

        while ($row = pg_fetch_array($res)) {
            echo $row["os"]." -> ";

            $data = prepareData($row, $columns);

            #unset($data["status_checkpoint_dias"], $data["protocolo"], $data["classificacao_os"]);

            $sqlOsExists = "SELECT os FROM os_fabrica WHERE fabrica = $fabrica and os = {$row['os']}";
            $qryOsExists = pg_query($dbfabrica, $sqlOsExists);

            if (pg_num_rows($qryOsExists) > 0) {
                $update = array();

                foreach ($data as $key => $value) {
                    $update[] = "$key = $value";
                }

                $sqlOs = "
                    UPDATE os_fabrica SET
                        ".implode(", ", $update)."
                    WHERE fabrica = $fabrica and os = {$row['os']}
                ";
		$sqlOs = mb_convert_encoding($sqlOs, 'latin1', 'utf8,latin1');

                pg_query($dbfabrica, $sqlOs);

                $updatedOSs++;
                echo "atualizada - OS\n";
            } else {
                $sqlOs = "
                    INSERT INTO os_fabrica 
                    (".implode(", ", array_keys($data)).")
                    VALUES 
                    (".implode(", ", $data).")
                ";
		$sqlOs = mb_convert_encoding($sqlOs, 'latin1', 'utf8,latin1');

                pg_query($dbfabrica, $sqlOs);

                $newOSs++;
                echo "nova - OS\n";
            }
            
            if (strlen(pg_last_error($dbfabrica)) > 0) {
                $errors[$row["os"]] = pg_last_error($dbfabrica);
		echo $sqlOs;
		echo pg_last_error();
		echo "E R R O: os_fabrica";
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
     * OS Item - vw_os_item_replica > os_item_fabrica
     */
    $errors = array();

    echo "Table: os_item_fabrica\n";

    $sql = "SELECT * FROM vw_os_item_replica where fabrica = $fabrica";
    $res = pg_query($con, $sql);

    echo "Total: ".pg_num_rows($res)."\n";
    echo "--------------------------------------\n";

    if (pg_num_rows($res) > 0) {
        $columns = getColumns("vw_os_item_replica");

        $newItens = 0;
        $updatedITens = 0;

        while ($row = pg_fetch_array($res)) {
            echo $row["os"]." / ".$row["peca_referencia"]." -> ";

            $data = prepareData($row, $columns);
            echo "OS ITEM: ";
            echo $row['os_item'];

            #echo $sqlItemExists = "SELECT os, peca_referencia FROM os_item_cadence WHERE os = {$row['os']} AND peca_referencia = '{$row['peca_referencia']}'";
	    $sqlItemExists = "SELECT os_item FROM os_item_fabrica WHERE fabrica = $fabrica and os_item = {$row['os_item']} ";
            $qryItemExists = pg_query($dbfabrica, $sqlItemExists);

            if (pg_num_rows($qryItemExists) > 0) {
                $update = array();

                foreach ($data as $key => $value) {
                    $update[] = "$key = $value";
                }

                #$sqlItem = "
                #    UPDATE os_item_cadence SET
                #        ".implode(", ", $update)."
                #    WHERE os = {$row['os']}
                #    AND peca_referencia = '{$row['peca_referencia']}'
                #";
 		$sqlItem = "
                    UPDATE os_item_fabrica SET
                        ".implode(", ", $update)."
                    WHERE fabrica = $fabrica and os_item = {$row['os_item']}
                ";
                pg_query($dbfabrica, $sqlItem);

                $updatedITens++;
                echo "atualizado - OSItem\n";
            } else {
                $sqlItem = "
                    INSERT INTO os_item_fabrica
                    (".implode(", ", array_keys($data)).")
                    VALUES 
                    (".implode(", ", $data).")
                ";
                pg_query($dbfabrica, $sqlItem);

                $newItens++;
                echo "novo - OSItem\n";
            }
            
            if (strlen(pg_last_error($dbfabrica)) > 0) {
                echo $errors[$row["os"]."_".$row["peca_referencia"]] = pg_last_error($dbfabrica);
		echo "E R R O: os_item_fabrica";
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
     * Historico - vw_os_historico_replica > os_historico_fabrica
     */
    $errors = array();

    echo "Table: os_historico_fabrica\n";

    $sql = "SELECT * FROM vw_os_historico_replica where fabrica = $fabrica";
    $res = pg_query($con, $sql);

    echo "Total: ".pg_num_rows($res)."\n";
    echo "--------------------------------------\n";

    if (pg_num_rows($res) > 0) {
        $columns = getColumns("vw_os_historico_replica");

        $newHds = 0;
        $updatedHds = 0;

        while ($row = pg_fetch_array($res)) {
            echo $row["os"]." -> ";

            $data = prepareData($row, $columns);

            $sqlHdExists = "SELECT os FROM os_historico_fabrica WHERE fabrica = $fabrica and os = {$row['os']}";
            $qryHdExists = pg_query($dbfabrica, $sqlHdExists);

            if (pg_num_rows($qryHdExists) > 0) {
                $update = array();

		$sql_his = "SELECT status_checkpoint,data FROM os_historico_fabrica WHERE fabrica = $fabrica and os = {$row['os']} order by 2 DESC LIMIT 1 ";
                $res_his = pg_query($dbfabrica, $sql_his);

		$status_checkpoint = pg_fetch_result($res_his,0,status_checkpoint);

		#echo  "STATUS: $status_checkpoint";
		#echo "STATUS ANTIGO: {$row['status_checkpoint']}";
		#die;

		#if ($status_checkpoint <>  ($row['status_checkpoint'])  ){
		if ((($row['status_checkpoint']) <> '9') or ($status_checkpoint <>  ($row['status_checkpoint'])) ){
		#if ((($row['status_checkpoint']) <> '9') ){
			$sqlHd = "
			    INSERT INTO os_historico_fabrica
				    (".implode(", ", array_keys($data)).")
			    VALUES
				    (".implode(", ", $data).")
			";
			pg_query($dbfabrica, $sqlHd);
		$newHds++;
		echo "novo ---- historico\n";
		}
            } else {
                $sqlHd = "
                    INSERT INTO os_historico_fabrica
                    (".implode(", ", array_keys($data)).")
                    VALUES 
                    (".implode(", ", $data).")
                ";
                pg_query($dbfabrica, $sqlHd);

                $newHds++;
                echo "novo - historicoo\n";
            }
            
            if (strlen(pg_last_error($dbfabrica)) > 0) {
                $errors[$row["os_historico"]] = pg_last_error($dbfabrica);
		echo "E R R O: os_historico_fabrica";
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
    mail("marisa.silvana@telecontrol.com.br, marisa.silvana@telecontrol.com.br", "Fabrica: $fabrica - Exportação de Dados", $log);
} catch(Exception $e) {
    echo "\n\nERRO NA EXECUÇÃO DA ROTINA\n";
    echo $e->getMessage()."\n";

    $log = ob_get_contents();
    ob_end_flush();

    //mail("guilherme.curcio@telecontrol.com.br", "Colormaq - Exportação de Dados", $log);
    mail("marisa.silvana@telecontrol.com.br, marisa.silvana@telecontrol.com.br", "Fabrica: $fabrica - Exportação de Dados", $log);
}
