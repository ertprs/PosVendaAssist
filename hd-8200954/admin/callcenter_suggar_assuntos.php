<?php

//HD 235203: Alterar campo TIPO DE CONTATO
$assuntos = array();

//$assuntos["PRODUTOS"]["Caracter�sticas"] = "produto_caracteristicas"; 				HD 692145 -> DE: PRODUTOS >> Caracteristicas || PARA: PRODUTOS >> D�vidas Sobre Utiliza��o
$assuntos["PRODUTOS"]["D�vida sobre utiliza��o"] = "produto_duvida_sobre_utilizacao";
//$assuntos["PRODUTOS"]["Elogio"] = "produto_elogio"; 									HD 692145 -> DE: PRODUTOS >> Elogio || PARA: Empresa >> Elogio
//$assuntos["PRODUTOS"]["Sugest�o"] = "produto_sugestao"; 								HD 692145 -> DE: PROD. >> Sugest�o  || PARA: PROD. >> Outros Assuntos
$assuntos["PRODUTOS"]["Reclama��o"] = "produto_reclamacao";
//$assuntos["PRODUTOS"]["Defeito"] = "produto_defeito";
//$assuntos["PRODUTOS"]["Garantia"] = "produto_garantia"; 								HD 692145 -> PARA NULL
//$assuntos["PRODUTOS"]["Falta de Pe�as"] = "produto_falta_de_pecas"; 					HD 692145 -> DE: PROD. >> Falta de Pe�as || PARA: PROD. >> Reclama��o
$assuntos["PRODUTOS"]["Local de Assist�ncia"] = "produto_local_de_assistencia";
$assuntos["PRODUTOS"]["Onde comprar"] = "produto_onde_comprar";
$assuntos["PRODUTOS"]["Outros Assuntos"] = "produto_outros";
$assuntos["PRODUTOS"]["Reclame Aqui"] = "reclame_aqui";

//$assuntos["MANUAL"]["Sugest�o"] = "produto_manual_sugestao"; 							HD 692145 -> DE: MANUAL >> Sugest�o 			|| PARA: PRODUTO >> Outros Assuntos
//$assuntos["MANUAL"]["D�vida"] = "produto_manual_duvida"; 								HD 692145 -> DE: MANUAL >> D�vida 				|| PARA: PRODUTO >> Outros Assuntos
//$assuntos["MANUAL"]["Falta de informa��o"] = "produto_manual_falta_de_informacao"; 	HD 692145 -> DE: MANUAL >> Falta de informa��o 	|| PARA: PRODUTO >> Outros Assuntos
//$assuntos["MANUAL"]["Solicita��o"] = "produto_manual_solicitacao"; 					HD 692145 -> DE: MANUAL >> Solicita��o 			|| PARA: PRODUTO >> Outros Assuntos
//$assuntos["MANUAL"]["Outros Assuntos"] = "produto_manual_outros"; 					HD 692145 -> DE: MANUAL >> Outros Assuntos 		|| PARA: PRODUTO >> Outros Assuntos

$assuntos["EMPRESA"]["Trabalhe Conosco"] = "empresa_trabelhe_conosco";
//$assuntos["EMPRESA"]["Solicita��o"] = "empresa_solicitacao";		HD 692145 -> DE: EMPRESA >> Solicita��o		|| 	PARA: EMPRESA >> Outros Assuntos
$assuntos["EMPRESA"]["Elogio"] = "empresa_elogio";
//$assuntos["EMPRESA"]["Sugest�o"] = "empresa_sugestao";			HD 692145 -> DE: EMPRESA >> Sugest�o		|| 	PARA: EMPRESA >> Outros Assuntos
//$assuntos["EMPRESA"]["Reclama��o"] = "empresa_reclamacao";		HD 692145 -> DE: EMPRESA >> Reclama��o		|| 	PARA: EMPRESA >> Outros Assuntos
$assuntos["EMPRESA"]["E-COMMERCE"] = "E-COMMERCE";
$assuntos["EMPRESA"]["Outros Assuntos"] = "empresa_outros";

$assuntos["ASSIST�NCIA T�CNICA"]["Reclama��o"] = "at_reclamacao";
$assuntos["ASSIST�NCIA T�CNICA"]["Demora no Atendimento"] = "at_demora_atendimento";
//$assuntos["ASSIST�NCIA T�CNICA"]["Elogio"] = "at_elogio";				HD 692145 -> DE: A. TECNICA >> Elogio			|| 	PARA: EMPRESA >> Elogio
//$assuntos["ASSIST�NCIA T�CNICA"]["Outros Assuntos"] = "at_outros";	HD 692145 -> DE: A. TECNICA >> Outros Assuntos	|| 	PARA: EMPRESA >> Outros Assuntos

$assuntos["REVENDA"]["Quero ser um revendedor"] = "revenda_quero_ser_um_revendedor";
//$assuntos["REVENDA"]["Elogio"] = "revenda_elogio";			HD 692145 -> DE: REVENDA >> Elogio			|| 	PARA: REVENDA >> Outros Assuntos
//$assuntos["REVENDA"]["Sugest�o"] = "revenda_sugestao";		HD 692145 -> DE: REVENDA >> Sugest�o		|| 	PARA: REVENDA >> Outros Assuntos
//$assuntos["REVENDA"]["Reclama��o"] = "revenda_reclamacao";	HD 692145 -> DE: REVENDA >> Reclama��o		|| 	PARA: REVENDA >> Outros Assuntos
$assuntos["REVENDA"]["Outros Assuntos"] = "revenda_outros";

$assuntos["PROCON"]["Procon"] = "procon";

$assuntos["SUGEST�O"]["Sugest�o"] = "sugestao";

$assuntos["OUTROS ASSUNTOS"]["Outros Assuntos"] = "outros_assuntos";

// Formato alternativo, para poder se usado mais facilmente para, por exemplo, um <SELECT> simples
$NaturezaSelect = array();

foreach($assuntos as $topico => $itens) {
    foreach($itens AS $label => $valor) {
        $NaturezaSelect[$valor] = "$topico >> $label";
    }
}

