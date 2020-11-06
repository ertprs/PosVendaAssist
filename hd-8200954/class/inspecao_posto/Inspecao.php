<?php 

class Inspecao{
    private $con;

    public $fabrica;
    public $idPesquisa;
    public $pesquisaDescricao;
    public $posto;
    public $dataVisita;
    public $cnpjPosto;
    public $nomePosto;
    #coluna tbl_auditoria_online.concorda_relatorio
    public $concordaRelatorio;
    public $admin;
    public $idAuditoriaOnline;


    #array contendo objetos da classe Pergunta
    public $perguntas;
    public $pAnt = "";
    public $elPorLinha = 0;
    public $br;
    #utilizado para setar tamanho dos elementos que ficam sozinho na row-fluid
    private $span = 8;
    function __construct($fabrica, $idAuditoriaOnline=null){

        global $con;
        $this->con = $con;
        $this->fabrica = $fabrica;
        $this->idAuditoriaOnline = $idAuditoriaOnline;

        if(empty($idAuditoriaOnline)){
            
            $res = $this->execQueryPerguntas();
        }else{

            $res = $this->execQueryPerguntasRespondidas($this->idAuditoriaOnline);

        }
        $this->perguntas = $this->criaPergunta($res);
        
        /* 
           
           Fazer consulta
           Separar os dados da consulta
           Verificar se o tipo da resposta tem opções para resposta (radio, chekbox, option)
           Instanciar as classes
        */
    }

    public function getPergunta ($idPergunta){
        $pergunta;
        foreach($this->perguntas as $p){
            if($p->idPergunta == $idPergunta){
                return $p;
            }
        }
    }

    public function insert($parcial){

       $insertAuditoriaOnline = "INSERT INTO tbl_auditoria_online(
                                                posto,
                                                fabrica,
                                                data_visita,
                                                data_pesquisa,
                                                admin,
                                                pesquisa,
                                                concorda_relatorio
                                              )VALUES(
                                                  $this->posto,
                                                  $this->fabrica,
                                                  '$this->dataVisita',
                                                  '$this->dataVisita',
                                                  $this->admin,
                                                  $this->idPesquisa,
                                                  '$parcial'
                                              ) returning auditoria_online";

        $resInsertAuditoriaOnline = pg_query($this->con, $insertAuditoriaOnline);

        if(strlen(pg_last_error($this->con)) == 0){
            return pg_fetch_result($resInsertAuditoriaOnline,0,"auditoria_online");
        }else{
            return 0;
        }
    }
    
    private function update($parcial){
        $sqlUpdate = " UPDATE tbl_auditoria_online SET
								posto			   = $this->posto,		 
								fabrica			   = $this->fabrica,	 
								data_visita		   = '$this->dataVisita',
								data_pesquisa	   = '$this->dataVisita',
								admin			   = $this->admin,		 
								pesquisa		   = $this->idPesquisa,	 
                                concorda_relatorio = '$parcial'
                       WHERE tbl_auditoria_online.auditoria_online = $this->idAuditoriaOnline ";
        $resUpdate = pg_query($this->con, $sqlUpdate);
        if(strlen(pg_last_error($this->con)) == 0){
            return true;
        }else{
            if(pg_num_rows($resUpdate) == 0){
                throw new Exception("Erro ao atualizar Auditoria");
            }
            return false;
        }
    }

    public function save($posto){
        pg_query($this->con, "BEGIN TRANSACTION");
        try{
            if(empty($posto)){

                throw new Exception("Preencha os campos Obrigat&oacute;rios");

            }
            if($this->concordaRelatorio == 't'){
                throw new Exception("Inspe&ccedil;&atilde;o j&aacute; Finalizada");
            }
            #verifica se é gravar parcial ou nao
            if($_POST["btn_acao"]=="parcial"){
                $concorda_auditoria = 'f';
            }else{
                $concorda_auditoria = 't';
            }
            #seta atributos da inspecao
            $this->posto = $posto;
            list($d,$m,$y) = explode("/", $_POST["data"]);
            $this->dataVisita = $y."-".$m."-".$d;

            $this->admin = $_POST["inspetor"];
            #se não houver numero da auditoria -> faz insert senão ->update
            if(empty($this->idAuditoriaOnline)){
                $this->idAuditoriaOnline = $this->insert($concorda_auditoria);
            }else{
                $this->update($concorda_auditoria);
            }

            foreach($_POST as $pergunta => $respostaItem){
                $p = $this->getPergunta($pergunta);

                if(!empty($p)){
                    $p->setResposta($respostaItem);

                    $p->save($this->idPesquisa, $this->idAuditoriaOnline, $this->posto, $this->admin);

                }
            }
            pg_query($this->con, "COMMIT TRANSACTION");
        }catch(Exception $ex){
            pg_query($this->con, "ROLLBACK TRANSACTION");
            global $msg_erro;
            $msg_erro["msg"][]= $ex->getMessage();
            if(strstr($ex->getMessage(), "campos")){
                $msg_erro["campos"][] = "posto";
            }
        }


    }
    
    private function execQueryPerguntasRespondidas($idAuditoriaOnline){
      $sqlPesquisaRespondida = " SELECT    tbl_pesquisa.pesquisa,
                        					tbl_pesquisa.descricao as pesquisa_descricao,
                        					tbl_pergunta.pergunta,
                        					tbl_pergunta.descricao as pergunta_descricao,
                        					tbl_tipo_resposta.tipo_resposta,
                        					tbl_tipo_resposta.tipo_descricao as tipo_elemento,
                                            tbl_resposta.resposta,
                        					tbl_resposta.txt_resposta as resposta_texto,
                                            tbl_resposta.tipo_resposta_item as resposta_opcao,
                                            tbl_posto.nome as nome_posto,
                                            tbl_posto_fabrica.codigo_posto as cnpj_posto,
                                          
                                            tbl_auditoria_online.data_visita,
                                            tbl_auditoria_online.concorda_relatorio
                        		      FROM tbl_auditoria_online
                                      INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_auditoria_online.posto AND
                                                                      tbl_posto_fabrica.fabrica = tbl_auditoria_online.fabrica
                                      INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto

                                      INNER JOIN tbl_pesquisa ON tbl_pesquisa.pesquisa = tbl_auditoria_online.pesquisa AND
                                                                                 tbl_pesquisa.fabrica = tbl_auditoria_online.fabrica

                        			  INNER JOIN tbl_pesquisa_pergunta ON	 tbl_pesquisa_pergunta.pesquisa = tbl_pesquisa.pesquisa

                        			  INNER JOIN tbl_pergunta ON tbl_pergunta.pergunta = tbl_pesquisa_pergunta.pergunta AND
                                                                 tbl_pergunta.fabrica = {$this->fabrica}

                        			  INNER JOIN tbl_tipo_pergunta on tbl_tipo_pergunta.tipo_pergunta = tbl_pergunta.tipo_pergunta AND
                                                                      tbl_tipo_pergunta.fabrica = tbl_pergunta.fabrica

                        			  INNER JOIN tbl_tipo_resposta ON tbl_tipo_resposta.tipo_resposta = tbl_pergunta.tipo_resposta AND
                                                                      tbl_tipo_resposta.fabrica = tbl_pergunta.fabrica
                     
                                      LEFT JOIN tbl_resposta ON tbl_resposta.pergunta = tbl_pergunta.pergunta AND
                                                                tbl_resposta.pesquisa = tbl_pesquisa.pesquisa AND
                                                                tbl_resposta.auditoria_online = tbl_auditoria_online.auditoria_online AND
                                                                tbl_resposta.posto = tbl_auditoria_online.posto                     

                        			  WHERE tbl_auditoria_online.auditoria_online = {$idAuditoriaOnline} AND
                                                            tbl_auditoria_online.fabrica = {$this->fabrica}
                                                                
                        			  ORDER by tbl_pesquisa_pergunta.ordem ASC ";
        return  pg_query($this->con, $sqlPesquisaRespondida);
    }
    /* Executa query para montar as perguntas */
    private function execQueryPerguntas(){
        $sqlPesquisa = "SELECT tbl_pesquisa.pesquisa,
						tbl_pesquisa.descricao as pesquisa_descricao,
						tbl_pergunta.pergunta,
						tbl_pergunta.descricao as pergunta_descricao,
						tbl_tipo_resposta.tipo_resposta,
						tbl_tipo_resposta.tipo_descricao as tipo_elemento
						
			      FROM tbl_pesquisa
				  INNER JOIN tbl_pesquisa_pergunta ON	 tbl_pesquisa_pergunta.pesquisa = tbl_pesquisa.pesquisa
				  INNER JOIN tbl_pergunta ON tbl_pergunta.pergunta = tbl_pesquisa_pergunta.pergunta AND
                                             tbl_pergunta.fabrica = {$this->fabrica}
				  INNER JOIN tbl_tipo_pergunta on tbl_tipo_pergunta.tipo_pergunta = tbl_pergunta.tipo_pergunta AND
                                                  tbl_tipo_pergunta.fabrica = tbl_pergunta.fabrica
				  INNER JOIN tbl_tipo_resposta ON tbl_tipo_resposta.tipo_resposta = tbl_pergunta.tipo_resposta AND
                                                  tbl_tipo_resposta.fabrica = tbl_pergunta.fabrica
				  WHERE tbl_pesquisa.fabrica = {$this->fabrica}
				  ORDER by tbl_pesquisa_pergunta.ordem ASC ";
   
        return  pg_query($this->con, $sqlPesquisa);

    }
    public function criaTipoResposta($obj){
        
        $itens = array();
        
        ## seleciona os tipos de resposta
        $sqlOptions = "SELECT tipo_resposta_item,
                              descricao
                       FROM tbl_tipo_resposta_item
                       WHERE tipo_resposta = {$obj->tipo_resposta} ";

        $resOptions = pg_query($this->con, $sqlOptions);
        $optionsNumRows = pg_num_rows($resOptions);

        # os tipos radio e checkbox necessita consultar no banco as opções relacionado a cada pergunta
        if(($optionsNumRows > 0) and (in_array($obj->tipo_elemento, array("radio","checkbox"))) ){

        for($j = 0; $j < $optionsNumRows; $j++){
            $option = pg_fetch_object($resOptions, $j);
            ## monta array ("id" => descricao) para as opções das respostas
            $itens["$option->tipo_resposta_item"] = $option->descricao;
        }
    }

        ## cria objeto do tipo da resposta da pergunta
        return new TipoResposta($obj->tipo_resposta, $obj->tipo_elemento, $itens, $obj->concorda_relatorio);
    }

public function criaPergunta($res){
    $numRows = pg_num_rows($res);

    $arrPergunta = array();
    $obj_inspecao = pg_fetch_object($res, 0);
    $this->idPesquisa = $obj_inspecao->pesquisa;
    $this->nomePosto = $obj_inspecao->nome_posto;
    $this->cnpjPosto = $obj_inspecao->cnpj_posto;
    $this->dataVisita = $obj_inspecao->data_visita;
    $this->concordaRelatorio = $obj_inspecao->concorda_relatorio;
    for($i = 0; $i < $numRows; $i++){
        $obj = pg_fetch_object($res, $i);
        
        $tipoResposta = $this->criaTipoResposta($obj);

        #se há opções de resposta, pega resposta da tbl_resposta.tipo_resposta_item; senão pega da tbl_resposta.resposta_texto
        if(count($tipoResposta->itens) > 0){
            $resposta = $obj->resposta_opcao;
        }else{
            $resposta = $obj->resposta_texto;
        }

        $arrPergunta[] = new Pergunta($obj->pergunta, $obj->pergunta_descricao, $resposta, $obj->resposta, $tipoResposta );

    }

    return $arrPergunta;
}

public function drawForm(){
    $tipoAnt = "";
    $tipoAtual = "";

    foreach($this->perguntas as $key => $p){
        $tipoAtual = $p->tipoResposta->getTipoResposta();
        if( $tipoAtual != $tipoAnt) {

            if(!empty($tipoAnt) ){
                
                $this->drawCloseTags($this->pAnt);
                $this->elPorLinha = 0;
            }
            $this->isTextArea();
            $this->drawOpenTags($p);

            /* se o proximo elemento for igual ao atual, é feito o echo utilizando span2 */
            if(isset($this->perguntas[++$key])){
                $tipoProx = $this->perguntas[++$key]->tipoResposta->getTipoResposta();
            }else{
                $tipoProx = "0";
            }


            if($tipoProx == $tipoAtual ){
                $this->drawElement($p);
            }else{
                $this->drawSingleElement($p);
            }
            /* adiciona +1 no contador */
            $this->addElRow($p);

            $tipoAnt = $tipoAtual;
            $this->pAnt = $p; 
            
            
        }else{
            if($this->checkQtdEl()){
                $this->elPorLinha = 0;
                $this->drawCloseTags($p);
                $this->drawOpenTags($p);
            }
            /* se não existir proximo elemento  */
            if(isset($this->perguntas[++$key] )){
                $vazio = false;
            }else{
                $vazio = true;
            }

            if(!$vazio ){
                $this->drawElement($p);
            }else{
                $this->drawSingleElement($p);
            }

            $this->addElRow($p);
            $this->pAnt = $p;
        }
    }
    $this->drawCloseTags($this->pAnt,true);


}

/* Verifica se é textArea para colocar <br/> */
public function isTextArea(){

    if($this->pAnt instanceof Pergunta){
        
        if($this->pAnt->tipoResposta->getTipoElemento() == "textarea"){
            $this->br = "<br/><br/><br/><br/>";
        }else{
            $this->br = "";
        }
        return true;
    }else{
        return false;
    }
}
public function getSpan(){
    return $this->span;
}
public function setSpan($n){
    $this->span = $n;
}
/* adiciona +1 no contador de elementos por linha */
public function addElRow($p){
    #qtd elementos por linha válido somente para elementos text, pois elementos de opção são colocados dentro de table
    if(!in_array($p->tipoResposta->getTipoElemento(), array('radio', 'checkbox') ) ){
        $this->elPorLinha++;
    }
}
/* verifica se ja tem 2 elementos na linha (row-fluid) */
public function checkQtdEl(){
        

    if($this->elPorLinha == 2){
        return true;
    }else{
        return false;
    }

}

/* echo da abertura de tags para cada elemento*/
public function drawOpenTags($pergunta){
  #  echo "</br>OPEN ->".$pergunta->tipoResposta->getTipoResposta();
    switch($pergunta->tipoResposta->getTipoElemento()){
        case 'text':
            echo "{$this->br}
                  <div class='row-fluid'>
                      <div class='span2'></div>";
        break;
     
        case 'textarea':
            #para corrigir bug do bootstrap quando é utilizado text area, tem que colocar br no row-fluid
            echo " {$this->br}<br/><br/><br/>
                   <div class='row-fluid'>
                      <div class='span2'></div>";

        break;
     
        case 'radio':
            $openTable = "{$this->br}
                          <table class='table table-striped table-hover table-bordered'>";
            $thead = "<thead>
                          <tr><th>Perguntas</th>";
            foreach($pergunta->tipoResposta->itens as $selectOptions => $descricao){
                $thead .= "<th> {$descricao} </th>";
            }
            $thead .= "    </tr>
                      </thead><tbody>";
            
            echo $openTable.$thead;

        break;

        
    }
}
/* echo do elemento para ficar sozinho na row-fluid (utiliza span8) */
public function drawSingleElement($pergunta){
        echo $pergunta->returnHTML($this->span);
}

/* echo do elemento (span2)*/
public function drawElement($pergunta){
    echo $pergunta->returnHTML();

}
/* echo do fechamento de tag de cada elemento */
public function drawCloseTags($pergunta, $ultimo=false){

    switch($pergunta->tipoResposta->getTipoElemento()){
        case 'text':
            echo "    <div class='span2'></div>
                  </div><br/>";
        break;
     
        case 'textarea':
            if($ultimo){
                echo "   <div class='span2'></div>
                  </div><br/><br/><br/><br/>";
            }else{
                echo "   <div class='span2'></div>
                  </div>";
            }
        break;
     
        case 'radio':
            $closeTags = "    </tbody>
                          </table><br/>";
            echo $closeTags;

        break;

        
    }
   # echo "CLOSE ->".$pergunta->tipoResposta->getTipoResposta();
}
}

?>