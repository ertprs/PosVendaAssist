<?

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

echo "\n";
echo "Iniciando ".date("Y-m-d H:i")."\n";

echo "Processamento do arquivo...\n";

$arquivo  = "carga/historico_ordem_2.csv";
$conteudo = file_get_contents($arquivo);
$linhas   = explode("\n", $conteudo);

echo count($linhas)." ordens...\n";

// Planilha Histórico OS Tecvoz
// os_tecvoz text,
// cliente_codigo text,
// cliente_razao text,
// produto_codigo text,
// produto_descricao text,
// qtde text,
// unidade text,
// situacao text,
// garantia text,
// doc_auxiliar text,
// data_abertura text,
// data_fechamento text,
// numero_serie text,
// status text

echo "Processando...\n";

pg_query($con, "BEGIN;");
$rollback = false;

echo "Removendo registros antigos...\n";

pg_query($con, "DELETE FROM tbl_os_tecvoz;");

if (strlen(pg_last_error()) > 0) {
    echo "Error: ".pg_last_error();
    $rollback = true;
}

echo "Iniciando inserção dos novos registros...\n";

foreach ($linhas as $linha) {

    list($os_tecvoz, $cliente_codigo, $cliente_razao, $produto_codigo, $produto_descricao, $qtde, $unidade, $situacao, $garantia, $doc_auxiliar, $data_abertura, $data_fechamento, $numero_serie, $status) = explode(";", $linha);

    if (strlen(trim($data_abertura)) == 0) {
        $data_abertura = "null";
    } else {
        $data_abertura = "'".$data_abertura."'::TIMESTAMP";
    }

    if (strlen(trim($data_fechamento)) == 0) {
        $data_fechamento = "null";
    } else {
        $data_fechamento = "'".$data_fechamento."'::TIMESTAMP";
    }

    $insert = "
        INSERT INTO tbl_os_tecvoz (
            os_tecvoz,
            cliente_codigo,
            cliente_razao,
            produto_codigo,
            produto_descricao,
            qtde,
            unidade,
            situacao,
            garantia,
            doc_auxiliar,
            data_abertura,
            data_fechamento,
            numero_serie,
            status
        ) VALUES (
            '{$os_tecvoz}',
            '{$cliente_codigo}',
            '{$cliente_razao}',
            '{$produto_codigo}',
            '{$produto_descricao}',
            '{$qtde}',
            '{$unidade}',
            '{$situacao}',
            '{$garantia}',
            '{$doc_auxiliar}',
            {$data_abertura},
            {$data_fechamento},
            '{$numero_serie}',
            '{$status}'
        );
    ";

    pg_query($con, $insert);
    echo $insert."\n";

    if (strlen(pg_last_error()) > 0) {
        echo "Error: ".pg_last_error();
        $rollback = true;
    }

}

if ($rollback == true) {
    pg_query($con, "ROLLBACK;");
    echo "Ocorreram erros durante o processamento da rotina...\n";
    echo "#############################\n";
} else {
    pg_query($con, "COMMIT;");
    $resHistorico = pg_query($con, "SELECT * FROM tbl_os_tecvoz;");
    echo "Registros Inseridos: ".pg_num_rows($resHistorico)."\n";
    echo "#############################\n";
}

?>
