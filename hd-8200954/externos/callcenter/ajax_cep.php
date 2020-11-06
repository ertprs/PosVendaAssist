<?php
class xml2Array
{

    public $arrOutput = array();
    public $resParser;
    public $strXmlData;

    function parse($strInputXML)
    {
        $this->resParser = xml_parser_create ();

        xml_set_object($this->resParser, $this);
        xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");
        xml_set_character_data_handler($this->resParser, "tagData");

        $this->strXmlData = xml_parse($this->resParser, $strInputXML);

        if($this->strXmlData) {
            xml_parser_free($this->resParser);
        }

        return $this->arrOutput;
    }

    function tagOpen($parser, $name, $attrs)
    {
        $tag = array("name" => $name, "attrs" => $attrs);

        array_push($this->arrOutput, $tag);
    }

    function tagData($parser, $tagData)
    {
        if(trim($tagData)) {
            if(isset($this->arrOutput[count($this->arrOutput)-1]['tagData'])) {
                $this->arrOutput[count($this->arrOutput)-1]['tagData'] .= $tagData;
            } else {
                $this->arrOutput[count($this->arrOutput)-1]['tagData'] = $tagData;
            }
        }
    }

    function retiraAcentos($texto){
        $array1 = array( "á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç"
        , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
        $array2 = array( "a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c"
        , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
        return str_replace( $array1, $array2, $texto);
    }

    function tagClosed($parser, $name)
    {
        $this->arrOutput[count($this->arrOutput)-2]['children'][] = $this->arrOutput[count($this->arrOutput)-1];

        array_pop($this->arrOutput);
    }
}

include "../../class/nusoap/nusoap.php";

$cep = $_GET["cep"];

$correios = new nusoap_client('https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl');
$correios->soap_defencoding = 'utf-8';

$xml = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>
<env:Envelope xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\"
    xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
    xmlns:env=\"http://schemas.xmlsoap.org/soap/envelope/\">
    <env:Body>
        <n1:consultaCEP xmlns:n1=\"http://cliente.bean.master.sigep.bsb.correios.com.br/\">
            <cep>{$cep}</cep>
        </n1:consultaCEP>
    </env:Body>
</env:Envelope>";

$correios->send($xml, '', '');

$result = $correios->responseData;

$xmlArray = new xml2Array();
$xmlArray->parse($result);

$array = $xmlArray->arrOutput[0]["children"][0]["children"][0]["children"][0]["children"];
// var_dump($array);
if (strlen($array[2]['tagData']) > 0 && strlen($array[7]['tagData']) > 0) {
    $cidade = $xmlArray->retiraAcentos(utf8_decode($array[2]['tagData']));
    echo utf8_decode("ok;{$array[5]['tagData']};{$array[0]['tagData']};".strtoupper($cidade).";{$array[7]['tagData']}");

    if(!empty($cep)){

        include "../../dbconfig.php";
        include "../../includes/dbconnect-inc.php";

        $cep = str_replace(".", "", $cep);
        $cep = str_replace("-", "", $cep);

        $cidade = utf8_decode($array[2]['tagData']);
        $estado = utf8_decode($array[7]['tagData']);

        $sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) == 0){

            $sql = "INSERT INTO tbl_cidade (nome, estado, cep,cod_ibge) VALUES ('$cidade', '$estado', '$cep', '55555555')";
            $res = pg_query($con, $sql);

            $sql2 = "
                    INSERT INT tbl_log_conexao(
                        programa
                    ) VALUES (
                        $PHP_SELF
                    );
            ";
            $res2 = pg_query($con,$sql2);

        }

    }


} else {
    include "../../dbconfig.php";
    include "../../includes/dbconnect-inc.php";

    $cep = str_replace (".","",$cep);
    $cep = str_replace ("-","",$cep);
    $cep = str_replace (" ","",$cep);

    $sql = "SELECT * FROM tbl_cep WHERE cep = '{$cep}'";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $logradouro = pg_fetch_result($res, 0, "logradouro");
        $bairro     = pg_fetch_result($res, 0, "bairro");
        $cidade     = pg_fetch_result($res, 0, "cidade");
        $estado     = pg_fetch_result($res, 0, "estado");
        $tipo       = pg_fetch_result($res, 0, "tipo");

        switch ($tipo) {
            case "A":
                $logradouro = "Av. ".$logradouro;
                break;
            case "R":
                $logradouro = "R. " .$logradouro;
                break;
        }

        echo "ok;{$logradouro};{$bairro};{$cidade};{$estado}";
    }
}
?>
