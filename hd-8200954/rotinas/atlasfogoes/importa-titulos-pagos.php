<?

require_once "../../class/importa_arquivos/ImportaArquivo.php";
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

$fabrica = 74;
$vet['fabrica'] = 'atlas';
$vet['tipo']    = 'importaTitulosPagos';
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

/* Verifica se existem títulos vencidos à <$dias> dias e não estão pagos */
function verificaVencidos ($posto){
    global $con;
    global $fabrica;

    $data = new DateTime("now");

    $sqlVencidos = "SELECT posto
                  FROM tbl_contas_receber

                  WHERE fabrica = {$fabrica} AND
                        /*vencimento + interval '10 day' >= '". $data->format("Y-m-d")."' AND*/
                        (vencimento - current_date) <= -10 AND
                        recebimento is null and
                        posto = $posto;
                        ";
    $res = pg_query($con, $sqlVencidos);

    return pg_num_rows($res);

}

$path = "/home/atlas/atlas-telecontrol/titulos_pagos.txt";

$hashTableFields = array("tbl_contas_receber" =>  array(
                                                      "posto",
                                                      "documento"
                                                )
  
                    );

$fileColumns = array("posto", "documento");
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

        $where = " posto = {$currentRow[$table]['posto']} AND
                   documento = '".$currentRow[$table]["documento"]."' ";
        $currentDate = new DateTime("now");

        /* se não existir posto, envia email */
        if($currentRow[$table]["posto"] == 0){
            Log::envia_email($vet, "Log - Importação de Títulos Pagos ATLAS FOGÕES","Posto não encontrado. CNPJ Posto: ".$cnpj);

        }else{
            

             $update = "UPDATE {$table} SET 
                              recebimento ='".$currentDate->format('Y-m-d H:i:s')."' 
                       WHERE fabrica = {$fabrica} AND
                             $where";

            $resUpdate = pg_query($con, $update);
            
            $arrVencidos = verificaVencidos($currentRow[$table]['posto']);

            if ($arrVencidos == 0) {
                $update = "UPDATE tbl_posto_fabrica set pedido_faturado = true where fabrica = {$fabrica} AND posto = {$currentRow[$table]['posto']}";
                $res = pg_query($con, $update);

                if(!$res){
                    Log::envia_email($vet, "Log - Verifica Vencimento de Títulos ATLAS FOGÕES","Erro ao desbloquear pedido faturado  Erro: ".pg_last_error($con). " Posto: ".$posto);
                }
            }

            if($resUpdate == false){
                Log::envia_email($vet,"Log - Importação de Títulos Pagos ATLAS FOGÕES" , "Erro ao atualizar recebimento. Posto: $cnpj - Documento: ".$currentRow[$table]['documento'] );

            }
        }
    }
    pg_query($con, "COMMIT TRANSACTION");
    
    $phpCron->termino();
}catch(Exception $ex){
    pg_query($con, "ROLLBACK TRANSACTION");

    Log::envia_email($vet, 'Log - Importação de Títulos Pagos ATLAS FOGÕES', $ex->getMessage());
    echo $ex->getMessage();
}


?>