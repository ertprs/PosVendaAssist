<?php
class TipoResposta{
    #id da tbl_tipo_resposta
    private $tipoResposta;

    #radio, text, textarea, etc
    private $tipoElemento;

    #estrutura html do elemento utilizando bootstrap
    private $elementoHTML;

    public $readonly;
    
  /* 
     Utilizado para quando a resposta houver opções de seleção. 
     Deve ser utilizado como array("chave" => value) onde a chave é o id (tbl_tipo_resposta_item.tipo_resposta_item) e o value a descricao (label) do item 
     ex: array("71"=>"SIM")
  */
    public $itens = array();

    function __construct($tipoResposta, $tipoElemento, $itens, $notEditable=false){
        $this->tipoElemento = $tipoElemento;
        $this->tipoResposta = $tipoResposta;
        $this->itens = $itens;
        if($notEditable == 'f' || empty($notEditable)){
            $this->readonly = "";
        }else{
            $this->readonly = "disabled='disabled'";
        }
                
    }

    public function getTipoResposta(){
        return $this->tipoResposta;
    }

    public function getTipoElemento(){
        return $this->tipoElemento;
    }
    function getElement(){
        return $this->elementoHTML;
    }
    function setElement($pergunta, $span=4){
        switch ($this->tipoElemento) {
         	case 'text':
                $this->makeTextEl($pergunta, $span);
            break;
     
        	case 'textarea':
        	    $this->makeTextAreaEl($pergunta, $span);
        	break;
     
        	case 'radio':
                $this->makeRadioEl($pergunta, $this->itens);
            break;
        
        }



}

    public function makeTextAreaEl($pergunta, $span){

        $this->elementoHTML ='<div class="span'.$span.'">
									<div class="control-group">				
										<label class="control-label">'.$pergunta->descricaoPergunta.'</label>
										<div class="controls controls-row">
											<textarea '. $this->readonly.'  name="'.$pergunta->idPergunta.'" id="'.$pergunta->idPergunta.'" class="span12">'.$pergunta->resposta.'</textarea> 
										</div>									
									</div>
							   </div>';
    }

    public function makeTextEl($pergunta, $span){
        
        $this->elementoHTML = '	<div class="span'.$span.'">
					                	<div class="control-group">				
					                		<label class="control-label">'.$pergunta->descricaoPergunta.'</label>
					                		<div class="controls controls-row">
					                			<input '. $this->readonly.' type="text" id="'.$pergunta->idPergunta.'" name="'.$pergunta->idPergunta.'" class="span12" value="'.$pergunta->resposta.'"> 
					                		</div>									
					                	</div>
				                </div>	';
    }
    public function makeRadioEl($pergunta, $selectOptions){
        #coluna da pergunta
        $ops = "<td>".$pergunta->descricaoPergunta."</td>";
        
        #monta as colunas de radios
        foreach ($selectOptions as $id => $descricao) {

            if($id == $pergunta->resposta){
                $checked = "checked";
            }else{
                $checked = "";
            }

            $ops .= '<td style="text-align:center">
					    <input '.$this->readonly.'  type="radio" name="'.$pergunta->idPergunta.'" id="'.$id.'" value="'.$id.'" '.$checked.' >
                     </td>';
						 

        }
        #cada pergunta que tem option vai em uma linha da tabela
        $this->elementoHTML = "<tr>".$ops."</tr>";
    }
}

?>