<?php 

    class Finaliza_os
    {
        private $fabrica;
        private $con;

        function __construct($fabrica, $conexao){
            $this->fabrica = $fabrica;
            $this->con = $conexao;
        }

        function buscarOs($qtdeDias){
            $sql = "SELECT os FROM tbl_os WHERE fabrica = ".$this->fabrica." AND data_abertura + INTERVAL '".$qtdeDias." days' < CURRENT_DATE AND  finalizada IS NULL AND excluida IS NOT TRUE ";
            $res = pg_query($this->con, $sql);
            for($i=0; $i<pg_num_rows($res); $i++){
                $os = pg_fetch_result($res, $i, 'os');
                $dados[] = $os;
            }            
            return $dados;
        }

        function finalizar($os){
            $sql = "UPDATE tbl_os SET data_fechamento = now(), finalizada = now() where os = $os and fabrica = ".$this->fabrica;
            $res = pg_query($this->con, $sql);
            if(strlen(pg_last_error($this->con)) > 0){
                return false;
            }else{
                return true;
            }           
        }

        function tempoReparar($os, $tempo_reparo){
            $sql_busca_reparo = "SELECT defeito_constatado_reclamado from tbl_os_defeito_reclamado_constatado where os = $os AND(tempo_reparo is null or tempo_reparo = 0) AND tbl_os_defeito_reclamado_constatado.fabrica = $this->fabrica limit 1"; 
            $res_busca_reparo = pg_query($this->con, $sql_busca_reparo);
            if(pg_num_rows($res_busca_reparo)){
                $defeito_constatado_reclamado = pg_fetch_result($res_busca_reparo, 0, defeito_constatado_reclamado);
                $sql_reparo = "UPDATE tbl_os_defeito_reclamado_constatado SET tempo_reparo = $tempo_reparo 
                    Where os = $os 
                    and defeito_constatado_reclamado = $defeito_constatado_reclamado 
                    AND(tempo_reparo is null or tempo_reparo = 0) 
                    AND fabrica = ".$this->fabrica ;
                $res_reparo = pg_query($this->con, $sql_reparo);                
            }
            if(strlen(pg_last_error($this->con)) > 0){
                return false;
            }else{
                return true;
            }                   
        }
    }

?>