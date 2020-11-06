<?

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../autentica_admin.php';

include_once '../api/persys.php';

$acao = $_REQUEST['acao'];

switch ($acao) {
    case 'salvar':

        try{
            $token = generateToken($applicationKey);

            $jsonData = stripcslashes($_POST["json"]);

            $arrData = json_decode($jsonData,true);
            $data= putData($arrData,$applicationKey, $token, $accessEnv, $_POST["ticket"]);

            $json = array('success' => true);
        } catch(Exception $ex) {
            $msg_erro = $ex->getMessage();
            $json = array('success' => false, 'message' => $msg_erro);
        }

        echo json_encode($json);

        break;

    case 'exportar':

        try {
            $token = generateToken($applicationKey);
            $jsonData = stripcslashes($_POST["json"]);

            $arrData = json_decode($jsonData,true);
            $hdChamadoData = exportData($arrData,$applicationKey, $token, $accessEnv);

            $cockpitData   = setHdChamadoCockpit(array('hd_chamado_cockpit'=>$arrData['hd_chamado_cockpit'], 'hdChamado' => $hdChamadoData['hd_chamado']), $applicationKey, $token, $accessEnv);

            $json = array('success' => true, "hdChamado" => $hdChamadoData['hd_chamado'], "hdChamadoCockpit" => $arrData['hd_chamado_cockpit']);

        } catch(Exception $ex) {
            $msg_erro = $ex->getMessage();
            $json = array('success' => false, 'message' => $msg_erro);

        }

        echo json_encode($json);

        break;

    case 'validar':

        try {
            $token = generateToken($applicationKey);
            $jsonData = stripcslashes($_POST["json"]);

            $arrData = json_decode($jsonData,true);
            $hdChamadoData = validateData($arrData,$applicationKey, $token, $accessEnv);

            $json = array('success' => true);

        } catch(Exception $ex) {
            $msg_erro = $ex->getMessage();

            $json = array('success' => false, 'message' => utf8_decode($msg_erro));

        }

        echo json_encode($json);

        break;

    default:
            echo 'A?o n? permitida!';
            header("Location: ../../conferencia_integracao.php");
        break;
}

?>