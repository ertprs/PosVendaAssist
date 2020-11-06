<?php

class verificaDebitoPosto{

    private $posto;

    public function __construct($posto){
        if(empty($posto)){
            return "ERRO: Não foi possível instanciar a classe, ID do posto é obrigatório";
        }

        $this->posto = $posto;
    }

    public function retornaDebitos(){

        global $con;

        $sql = "SELECT cnpj FROM tbl_posto WHERE posto = ".$this->posto;
        $res = pg_query($con,$sql);

        if(pg_num_rows($res) > 0){
            $cnpj = pg_fetch_result($res,0,'cnpj');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://www.cristofoli.com/telecontrol-fin.php?cnpj=$cnpj");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            ));
            $result = curl_exec($ch);
            curl_close($ch);

            return $result;

        }else{
            return "ERRO: Posto Informado não encontrado";
        }
    }
}

?>