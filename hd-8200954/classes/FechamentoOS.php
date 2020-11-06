<?php

class FechamentoOs {

    private $con;
    private $fabrica;
    private $cook_idioma;
    private $primeiraOS;
    /* [0]=>primeiraOS,[1]=>segundaOS */
    private $arrOS = null;

    const SOLICITACAO_DE_PECAS = 4637;

    public function FechamentoOS(){
        global $con;
        global $login_fabrica;
        global $cook_idioma;

        $this->con = $con;
        $this->fabrica = $login_fabrica;
        $this->cook_idioma = $cook_idioma;

    }

    public function setArrOS($arr){

        $this->arrOS = $arr;
        
    }
    public function getArrOS(){
        return $this->arrOS;
    }
    /* Verifica solucao da OS */
    public function verificaSolucao($os){
        $verificaSolucaoOS = "SELECT solucao_os from tbl_os where os = {$os}";
        $resVerificaSolucaoOS = pg_query($this->con, $verificaSolucaoOS);
        if(!$resVerificaSolucaoOS){
            $msg_erro = "Erro ao fechar OS";
        }else if(pg_num_rows($resVerificaSolucaoOS) > 0){
            
            $solucao_os = pg_fetch_result($resVerificaSolucaoOS, 0, "solucao_os");
        }
        return $solucao_os;
    }

    /* Verifica se OS Ã© vinculada Ã  outra */
    public function isOsVinculada($os) {
       
        $sqlOSConsumidorVinculada = "SELECT os_troca_origem
         		FROM tbl_os 
         		JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os AND
                                           tbl_os.fabrica = tbl_os_campo_extra.fabrica
         		WHERE tbl_os.os = {$os} AND
			      tbl_os.fabrica = {$this->fabrica} AND
                  tbl_os_campo_extra.os_troca_origem is not null";
        $resSqlOSConsumidorVinculada = pg_query($this->con,$sqlOSConsumidorVinculada);
        if(pg_num_rows($resSqlOSConsumidorVinculada) > 0) {

            $this->primeiraOS = pg_fetch_result($resSqlOSConsumidorVinculada, 0, "os_troca_origem");

            $solucao_os = $this->verificaSolucao($this->primeiraOS);

            if($solucao_os == self::SOLICITACAO_DE_PECAS){
                $this->arrOS = array($this->primeiraOS, $os);
                return true;
            }else{
                return false;
            }

        }else if($this->verificaSolucao($os) == self::SOLICITACAO_DE_PECAS){
            $sqlSegundaOS = "SELECT os from tbl_os_campo_extra where os_troca_origem ={$os} and fabrica = {$this->fabrica}";
            $resSegundaOS = pg_query($this->con, $sqlSegundaOS);
            
            if(pg_num_rows($resSegundaOS) > 0){
                $segundaOS = pg_fetch_result($resSegundaOS, 0, "os");
                $this->arrOS = array($os,$segundaOS);
                return true;
            }else{

                return false;
            }
        }else{
            return false;
        }

    }

    public function validarFechamentoOS($os){
        if($this->verificaSolucao($os) == self::SOLICITACAO_DE_PECAS){
            throw new Exception("A solução desta OS é 'Solicitação de Peças'. Consulte a OS para verificar a OS vinculada.");

        }else if( $this->isOsVinculada($os) ){

            return true;
        }else{           
            $this->arrOS = array($os);
            return true;
        }

    }

    public function validaEfechaOS($os){
        
        $this->validarFechamentoOS($os);

        foreach($this->arrOS as $value){
            echo pg_last_error($this->con);
            $this->fecharOS($value);
        }
    }

    public function validaEConsertaOS($os){
        
        $this->validarFechamentoOS($os);

        foreach($this->arrOS as $value){
            echo pg_last_error($this->con);
            $this->consertaOS($value);
        }
    }

    public function consertaOS($os){
        
        $sql = "UPDATE tbl_os SET data_conserto = CURRENT_TIMESTAMP WHERE os=$os";
        $res = @pg_query($this->con,$sql);
        $msg_erro = pg_errormessage($this->con);
        if(strlen($msg_erro) > 0){
            throw new Exception($msg_erro);
        }

    }
    
    public function fecharOS($os) {

        $sql = "SELECT status_os
            FROM tbl_os_status
            WHERE os = $os
            AND status_os IN (62,64,65,72,73,87,88,116,117)
            ORDER BY data DESC
            LIMIT 1";
        $res = pg_query ($this->con,$sql);
        if (pg_num_rows($res)>0){
            $status_os = trim(pg_fetch_result($res,0,status_os));
            if ($status_os=="72" || $status_os=="62" || $status_os=="87" || $status_os=="116"){
                throw new Exception(traduz("os.com.intervencao,.nao.pode.ser.fechada.",$this->con,$this->cook_idioma));

            }
        }

        $sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $os AND fabrica = $this->fabrica;";
        $res = pg_query ($this->con,$sql);
        $msg_erro = pg_errormessage($this->con);

        if( strlen($msg_erro) > 0){
            throw new Exception($msg_erro);
        }

        $sql = "SELECT fn_finaliza_os($os, $this->fabrica)";        
        $res = @pg_query ($this->con,$sql);
        $msg_erro = pg_errormessage($this->con) ;

        if(strlen($msg_erro) > 0){
            throw new Exception($msg_erro);
        }

    }
}

