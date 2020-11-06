<?php
class Pergunta{

    public $idPergunta;
    public $descricaoPergunta;

    # objeto da class TipoResposta
    public $tipoResposta;
    private $con;
    public $resposta;
    #id tbl_resposta
    public $idResposta;
    function __construct($idPergunta, $descricaoPergunta, $resposta, $idResposta=null, $tipoResposta = null){

        global $con;
        $this->con = $con;
        $this->idPergunta = $idPergunta;
        
        $this->descricaoPergunta = $descricaoPergunta;
        $this->idResposta = $idResposta;
        $this->resposta = $resposta;
        $this->tipoResposta = $tipoResposta;
        $this->tipoResposta->setElement($this);
       
    }

    /* Retorna HTML da pergunta */
    public function returnHTML($span=null){
        if(!empty($span)){
           
            $this->tipoResposta->setElement($this, $span);
        }
        
        return $this->tipoResposta->getElement();
        
    }
    public function setResposta($resposta){
        $this->resposta = $resposta;
        #cria novamente o elemento setando a resposta
        $this->tipoResposta->setElement($this);
    }
    public function save($pesquisa, $idAuditoriaOnline, $posto, $admin){
        if(empty($this->idResposta)){

            $this->insert($pesquisa, $idAuditoriaOnline, $posto, $admin);
        }else{

            $this->update($pesquisa, $idAuditoriaOnline, $posto, $admin);
        }
    }

    public function update($pesquisa, $idAuditoriaOnline, $posto, $admin){
        if( in_array($this->tipoResposta->getTipoElemento(),array("radio", "checkbox") ) ){
            $campoResposta = "tipo_resposta_item";
            $resposta = $this->resposta;
        }else{
            $campoResposta = "txt_resposta";
            $resposta = "'$this->resposta'";
        }
        $updateResposta = "UPDATE tbl_resposta SET
                                        pergunta         = $this->idPergunta, 
                                        {$campoResposta} = $resposta,	      
                                        pesquisa         = $pesquisa,	      
                                        admin            = $admin,	      
                                        auditoria_online = $idAuditoriaOnline,
                                        posto            = $posto
                            WHERE tbl_resposta.resposta = {$this->idResposta};";

        $resUpdate = pg_query($this->con, $updateResposta);
        if(pg_last_error($this->con) > 0){
            throw new Exception("Erro ao atualizar respostas");
        }
            
    }
    public function insert($pesquisa, $idAuditoriaOnline, $posto, $admin){

        if( in_array($this->tipoResposta->getTipoElemento(), array("radio", "checkbox") ) ){
            $campoResposta = "tipo_resposta_item";
            $resposta = $this->resposta;
        }else{
            $campoResposta = "txt_resposta";
            $resposta = "'{$this->resposta}'";
        }

        $insertResposta = "INSERT into tbl_resposta(
                                           pergunta,
                                           {$campoResposta},
                                           pesquisa,
                                           admin,
                                           auditoria_online,
                                           posto
                                       ) VALUES (
                                           $this->idPergunta,
                                           $resposta,
                                           $pesquisa,
                                           $admin,
                                           $idAuditoriaOnline,
                                           $posto
                                       ) returning resposta";

        $resInsertResposta = pg_query($this->con, $insertResposta);
        if(strlen(pg_last_error()) == 0){
            $this->idResposta = pg_fetch_result($resInsertResposta, 0, "resposta");
            return true;
        }else{
            if(pg_num_rows($resInsertResposta) == 0){
                throw new Exception("Erro ao inserir respostas");
            }
            return false;
        }
    }
}

?>