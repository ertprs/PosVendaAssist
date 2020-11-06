<?

require_once "../../class/importa_arquivos/ImportaArquivo.php";
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

$fabrica = 74;
$vet['fabrica'] = 'atlas';
$vet['tipo']    = 'importaTitulosVencidos';
$vet['dest']    = 'helpdesk@telecontrol.com.br';
$vet['log']     = 2;

function verificaPosto($cnpj){
    global $fabrica;
    global $con;
    
    $sql = "SELECT posto
            FROM tbl_posto
            WHERE cnpj = '{$cnpj}'";

    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0 ){
        return pg_fetch_result($res, 0, "posto");
    }else{

        return 0;
    }
}

function existeTitulo($posto,$titulo){
    global $fabrica;
    global $con;
    
    $sql = "SELECT contas_receber
            FROM tbl_contas_receber
            WHERE posto = {$posto} AND
                  documento = '$titulo' ";

    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
        return true;
    }else{
        return false;
    }
}

$path = "/home/atlas/atlas-telecontrol/titulos_vencidos.txt";

$hashTableFields = array("tbl_contas_receber" =>  array(
                                                      "posto",
                                                      "documento",
                                                      "vencimento"
                                                )
  
                    );

$fileColumns = array("posto", "documento", "vencimento");
$separator = "\t";
try{
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();
    $importaArquivo = new ImportaArquivo($path, $separator, $fileColumns, $hashTableFields);

    $importaArquivo->readFile();
    $rows = $importaArquivo->getDataRows();

    pg_query($con, "BEGIN TRANSACTION");

    for($i = 0; $i < count($rows); $i++){
        $currentRow = $rows[$i];
    
        $table =  array_keys($currentRow);
        $table = $table[0];
        $cnpj = $currentRow[$table]["posto"];

        /* verifica posto */
        $currentRow[$table]["posto"] = verificaPosto($cnpj);

        /* coloca aspas na data */
        $currentRow[$table]["vencimento"] = "'".$currentRow[$table]["vencimento"]."'";
        /* monta as colunas para insert */
        $columns = implode(",", array_keys($currentRow[$table]));

        /* monta os valores para insert */
        $values = implode(",", $currentRow[$table]);

        /* se não existir posto, lança exceção */
        if($currentRow[$table]["posto"] == 0){
            /* throw new Exception("Posto não encontrado. Posto: ".$cnpj); */
            Log::envia_email($vet, 'Log - Importação de Títulos ATLAS FOGÕES', "Posto não encontrado. Posto: ".$cnpj);

        }else if(!existeTitulo($currentRow[$table]["posto"], $currentRow[$table]["documento"])){
            $insert = "INSERT into {$table} ({$columns}, fabrica, remessa, distribuidor) values ({$values}, {$fabrica}, 0, 0)";

            $resInsert=pg_query($con, $insert);
            
            if($resInsert == false){
                throw new Exception("Erro ao inserir. Posto: $cnpj - Documento: ".$currentRow[$table]['documento'] );
            }
        }else{
            echo "ja existe";
        }
    }
    $phpCron->termino();
}catch(Exception $ex){
    pg_query($con, "ROLLBACK TRANSACTION");

    Log::envia_email($vet, 'Log - Importação de Títulos ATLAS FOGÕES', $ex->getMessage());
    echo $ex->getMessage();
}
pg_query($con, "COMMIT TRANSACTION");
?>