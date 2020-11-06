<?php

//HD 235203: Alterar campo TIPO DE CONTATO
$assuntos = array();

//$assuntos["PRODUTOS"]["Características"] = "produto_caracteristicas"; 				HD 692145 -> DE: PRODUTOS >> Caracteristicas || PARA: PRODUTOS >> Dúvidas Sobre Utilização
$assuntos["PRODUTOS"]["Dúvida sobre utilização"] = "produto_duvida_sobre_utilizacao";
//$assuntos["PRODUTOS"]["Elogio"] = "produto_elogio"; 									HD 692145 -> DE: PRODUTOS >> Elogio || PARA: Empresa >> Elogio
//$assuntos["PRODUTOS"]["Sugestão"] = "produto_sugestao"; 								HD 692145 -> DE: PROD. >> Sugestão  || PARA: PROD. >> Outros Assuntos
$assuntos["PRODUTOS"]["Reclamação"] = "produto_reclamacao";
//$assuntos["PRODUTOS"]["Defeito"] = "produto_defeito";
//$assuntos["PRODUTOS"]["Garantia"] = "produto_garantia"; 								HD 692145 -> PARA NULL
//$assuntos["PRODUTOS"]["Falta de Peças"] = "produto_falta_de_pecas"; 					HD 692145 -> DE: PROD. >> Falta de Peças || PARA: PROD. >> Reclamação
$assuntos["PRODUTOS"]["Local de Assistência"] = "produto_local_de_assistencia";
$assuntos["PRODUTOS"]["Onde comprar"] = "produto_onde_comprar";
$assuntos["PRODUTOS"]["Outros Assuntos"] = "produto_outros";
$assuntos["PRODUTOS"]["Reclame Aqui"] = "reclame_aqui";

//$assuntos["MANUAL"]["Sugestão"] = "produto_manual_sugestao"; 							HD 692145 -> DE: MANUAL >> Sugestão 			|| PARA: PRODUTO >> Outros Assuntos
//$assuntos["MANUAL"]["Dúvida"] = "produto_manual_duvida"; 								HD 692145 -> DE: MANUAL >> Dúvida 				|| PARA: PRODUTO >> Outros Assuntos
//$assuntos["MANUAL"]["Falta de informação"] = "produto_manual_falta_de_informacao"; 	HD 692145 -> DE: MANUAL >> Falta de informação 	|| PARA: PRODUTO >> Outros Assuntos
//$assuntos["MANUAL"]["Solicitação"] = "produto_manual_solicitacao"; 					HD 692145 -> DE: MANUAL >> Solicitação 			|| PARA: PRODUTO >> Outros Assuntos
//$assuntos["MANUAL"]["Outros Assuntos"] = "produto_manual_outros"; 					HD 692145 -> DE: MANUAL >> Outros Assuntos 		|| PARA: PRODUTO >> Outros Assuntos

$assuntos["EMPRESA"]["Trabalhe Conosco"] = "empresa_trabelhe_conosco";
//$assuntos["EMPRESA"]["Solicitação"] = "empresa_solicitacao";		HD 692145 -> DE: EMPRESA >> Solicitação		|| 	PARA: EMPRESA >> Outros Assuntos
$assuntos["EMPRESA"]["Elogio"] = "empresa_elogio";
//$assuntos["EMPRESA"]["Sugestão"] = "empresa_sugestao";			HD 692145 -> DE: EMPRESA >> Sugestão		|| 	PARA: EMPRESA >> Outros Assuntos
//$assuntos["EMPRESA"]["Reclamação"] = "empresa_reclamacao";		HD 692145 -> DE: EMPRESA >> Reclamação		|| 	PARA: EMPRESA >> Outros Assuntos
$assuntos["EMPRESA"]["E-COMMERCE"] = "E-COMMERCE";
$assuntos["EMPRESA"]["Outros Assuntos"] = "empresa_outros";

$assuntos["ASSISTÊNCIA TÉCNICA"]["Reclamação"] = "at_reclamacao";
$assuntos["ASSISTÊNCIA TÉCNICA"]["Demora no Atendimento"] = "at_demora_atendimento";
//$assuntos["ASSISTÊNCIA TÉCNICA"]["Elogio"] = "at_elogio";				HD 692145 -> DE: A. TECNICA >> Elogio			|| 	PARA: EMPRESA >> Elogio
//$assuntos["ASSISTÊNCIA TÉCNICA"]["Outros Assuntos"] = "at_outros";	HD 692145 -> DE: A. TECNICA >> Outros Assuntos	|| 	PARA: EMPRESA >> Outros Assuntos

$assuntos["REVENDA"]["Quero ser um revendedor"] = "revenda_quero_ser_um_revendedor";
//$assuntos["REVENDA"]["Elogio"] = "revenda_elogio";			HD 692145 -> DE: REVENDA >> Elogio			|| 	PARA: REVENDA >> Outros Assuntos
//$assuntos["REVENDA"]["Sugestão"] = "revenda_sugestao";		HD 692145 -> DE: REVENDA >> Sugestão		|| 	PARA: REVENDA >> Outros Assuntos
//$assuntos["REVENDA"]["Reclamação"] = "revenda_reclamacao";	HD 692145 -> DE: REVENDA >> Reclamação		|| 	PARA: REVENDA >> Outros Assuntos
$assuntos["REVENDA"]["Outros Assuntos"] = "revenda_outros";

$assuntos["PROCON"]["Procon"] = "procon";

$assuntos["SUGESTÃO"]["Sugestão"] = "sugestao";

$assuntos["OUTROS ASSUNTOS"]["Outros Assuntos"] = "outros_assuntos";

// Formato alternativo, para poder se usado mais facilmente para, por exemplo, um <SELECT> simples
$NaturezaSelect = array();

foreach($assuntos as $topico => $itens) {
    foreach($itens AS $label => $valor) {
        $NaturezaSelect[$valor] = "$topico >> $label";
    }
}

