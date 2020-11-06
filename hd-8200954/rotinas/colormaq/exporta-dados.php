<?php
try {
    include __DIR__."/../../dbconfig.php";
    include __DIR__."/../../includes/dbconnect-inc.php";

    if ($_serverEnvironment == "development") {
        exit("\nNão executar\n\n");
    }

    //Banco de produção cuidado
    $dbcolormaq = pg_connect("host=postgres-telecontrol.cej5gqiwq3dv.us-east-1.rds.amazonaws.com dbname=tc_03470 user=root password=tele6588");

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
     * OS - vw_os_colormaq > os_colormaq
     */
    $errors = array();

    echo "Table: os_colormaq\n";

    $sql = "SELECT * FROM vw_os_colormaq";
    $res = pg_query($con, $sql);

    echo "Total: ".pg_num_rows($res)."\n";
    echo "--------------------------------------\n";

    if (pg_num_rows($res) > 0) {
        $columns = getColumns("vw_os_colormaq");

        $newOSs = 0;
        $updatedOSs = 0;

        while ($row = pg_fetch_array($res)) {
            echo $row["os"]." -> ";

            $data = prepareData($row, $columns);

            unset($data["status_checkpoint_dias"], $data["protocolo"], $data["classificacao_os"]);

            $sqlOsExists = "SELECT os FROM os_colormaq WHERE os = {$row['os']}";
            $qryOsExists = pg_query($dbcolormaq, $sqlOsExists);

            if (pg_num_rows($qryOsExists) > 0) {
                $update = array();

                foreach ($data as $key => $value) {
                    $update[] = "$key = $value";
                }

                $sqlOs = "
                    UPDATE os_colormaq SET
                        ".implode(", ", $update)."
                    WHERE os = {$row['os']}
                ";
                pg_query($dbcolormaq, $sqlOs);

                $updatedOSs++;
                echo "atualizada\n";
            } else {
                $sqlOs = "
                    INSERT INTO os_colormaq 
                    (".implode(", ", array_keys($data)).")
                    VALUES 
                    (".implode(", ", $data).")
                ";
                pg_query($dbcolormaq, $sqlOs);

                $newOSs++;
                echo "nova\n";
            }
            
            if (strlen(pg_last_error($dbcolormaq)) > 0) {
                $errors[$row["os"]] = pg_last_error($dbcolormaq);
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
     * OS Item - vw_os_item_colormaq > os_item_colormaq
     */
    $errors = array();

    echo "Table: os_item_colormaq\n";

    $sql = "SELECT * FROM vw_os_item_colormaq";
    $res = pg_query($con, $sql);

    echo "Total: ".pg_num_rows($res)."\n";
    echo "--------------------------------------\n";

    if (pg_num_rows($res) > 0) {
        $columns = getColumns("vw_os_item_colormaq");

        $newItens = 0;
        $updatedITens = 0;

        while ($row = pg_fetch_array($res)) {
            echo $row["os"]." / ".$row["referencia_peca"]." -> ";

            $data = prepareData($row, $columns);

            $sqlItemExists = "SELECT os, referencia_peca FROM os_item_colormaq WHERE os = {$row['os']} AND referencia_peca = '{$row['referencia_peca']}'";
            $qryItemExists = pg_query($dbcolormaq, $sqlItemExists);

            if (pg_num_rows($qryItemExists) > 0) {
                $update = array();

                foreach ($data as $key => $value) {
                    $update[] = "$key = $value";
                }

                $sqlItem = "
                    UPDATE os_item_colormaq SET
                        ".implode(", ", $update)."
                    WHERE os = {$row['os']}
                    AND referencia_peca = '{$row['referencia_peca']}'
                ";
                pg_query($dbcolormaq, $sqlItem);

                $updatedITens++;
                echo "atualizado\n";
            } else {
                $sqlItem = "
                    INSERT INTO os_item_colormaq 
                    (".implode(", ", array_keys($data)).")
                    VALUES 
                    (".implode(", ", $data).")
                ";
                pg_query($dbcolormaq, $sqlItem);

                $newItens++;
                echo "novo\n";
            }
            
            if (strlen(pg_last_error($dbcolormaq)) > 0) {
                $errors[$row["os"]."_".$row["referencia_peca"]] = pg_last_error($dbcolormaq);
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
     * Atendimento - vw_callcenter_colormaq > callcenter_colormaq
     */
    $errors = array();

    echo "Table: callcenter_colormaq\n";

    $sql = "SELECT * FROM vw_callcenter_colormaq";
    $res = pg_query($con, $sql);

    echo "Total: ".pg_num_rows($res)."\n";
    echo "--------------------------------------\n";

    if (pg_num_rows($res) > 0) {
        $columns = getColumns("vw_callcenter_colormaq");

        $newHds = 0;
        $updatedHds = 0;

        while ($row = pg_fetch_array($res)) {
            echo $row["callcenter"]." -> ";

            $data = prepareData($row, $columns);

            $sqlHdExists = "SELECT callcenter FROM callcenter_colormaq WHERE callcenter = {$row['callcenter']}";
            $qryHdExists = pg_query($dbcolormaq, $sqlHdExists);

            if (pg_num_rows($qryHdExists) > 0) {
                $update = array();

                foreach ($data as $key => $value) {
                    $update[] = "$key = $value";
                }

                $sqlHd = "
                    UPDATE callcenter_colormaq SET
                        ".implode(", ", $update)."
                    WHERE callcenter = {$row['callcenter']}
                ";
                pg_query($dbcolormaq, $sqlHd);

                $updatedHds++;
                echo "atualizado\n";
            } else {
                $sqlHd = "
                    INSERT INTO callcenter_colormaq 
                    (".implode(", ", array_keys($data)).")
                    VALUES 
                    (".implode(", ", $data).")
                ";
                pg_query($dbcolormaq, $sqlHd);

                $newHds++;
                echo "novo\n";
            }
            
            if (strlen(pg_last_error($dbcolormaq)) > 0) {
                $errors[$row["callcenter"]] = pg_last_error($dbcolormaq);
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

    /**
     * Atendimento Interações - vw_callcenter_item_colormaq > callcenter_item_colormaq
     */
    $errors = array();

    echo "Table: callcenter_item_colormaq\n";

    $sql = "SELECT * FROM vw_callcenter_item_colormaq";
    $res = pg_query($con, $sql);

    echo "Total: ".pg_num_rows($res)."\n";
    echo "--------------------------------------\n";

    if (pg_num_rows($res) > 0) {
        $columns = getColumns("vw_callcenter_item_colormaq");

        $newItens = 0;

        while ($row = pg_fetch_array($res)) {
            echo $row["callcenter"]." -> ";

            $data = prepareData($row, $columns);

            $sqlItem = "
                INSERT INTO callcenter_item_colormaq 
                (".implode(", ", array_keys($data)).")
                VALUES 
                (".implode(", ", $data).")
            ";
            pg_query($dbcolormaq, $sqlItem);

            $newItens++;
            echo "novo\n";
            
            if (strlen(pg_last_error($dbcolormaq)) > 0) {
                $errors[$row["callcenter"]][] = pg_last_error($dbcolormaq);
            }
        }

        echo "--------------------------------------\n";
        echo "Novos Itens: $newItens\n";
        echo "Erros ".count($errors)."\n";

        if (count($errors) > 0) {
            print_r($errors);
        }
    } else {
        echo "Sem resultado\n";
    }

    echo "======================================\n\n";

    $log = ob_get_contents();
    ob_end_flush();

    //mail("guilherme.curcio@telecontrol.com.br", "Colormaq - Exportação de Dados", $log);
    mail("marisa.silvana@telecontrol.com.br, joao.junior@telecontrol.com.br", "Colormaq - Exportação de Dados", $log);
} catch(Exception $e) {
    echo "\n\nERRO NA EXECUÇÃO DA ROTINA\n";
    echo $e->getMessage()."\n";

    $log = ob_get_contents();
    ob_end_flush();

    //mail("guilherme.curcio@telecontrol.com.br", "Colormaq - Exportação de Dados", $log);
    mail("marisa.silvana@telecontrol.com.br, joao.junior@telecontrol.com.br", "Colormaq - Exportação de Dados", $log);
}
