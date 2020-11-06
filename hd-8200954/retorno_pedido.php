<?
//FUN��O DA TAG INICIAL DO ARQUIVO XML
function FuncInicial($parser, $elemento) {
	if($elemento == "PEDIDO-REMESSA")     echo "";
	elseif($elemento == "FABRICA")        echo "";
	elseif($elemento == "POSTO")          echo "";
	elseif($elemento == "PEDIDO")         echo "";
	elseif($elemento == "PEDIDO-CLIENTE") echo "";
	elseif($elemento == "CONDICAO")       echo "";
	elseif($elemento == "TIPO-PEDIDO")    echo "";
}//FECHA FUNCTION FUNCINICIAL


//FUN��O PARA EXIBIR OS DADOS DO DOCUMENTO XML
function FuncDados($parser, $dados) {
	echo $dados;
}//FECHA FUNCTION FUNCINICIAL


//FUN��O DA TAG INICIAL DO DOCUEMENTO XML
function FuncFinal($parser, $elemento) {
	if($elemento == "PEDIDO-REMESSA")     echo "\n";
	elseif($elemento == "FABRICA")        echo "\t";
	elseif($elemento == "POSTO")          echo "\t";
	elseif($elemento == "PEDIDO")         echo "\t";
	elseif($elemento == "PEDIDO-CLIENTE") echo "\t";
	elseif($elemento == "CONDICAO")       echo "\t";
	elseif($elemento == "TIPO-PEDIDO")    echo "";
}//FECHA FUNCTION FUNCFINAL

//CRIA O PARSER XML
$parser = xml_parser_create();

//DEFINE AS FUN��ES
xml_set_element_handler($parser, "FuncInicial", "FuncFinal");
xml_set_character_data_handler($parser, "FuncDados");

//ABRE O ARQUIVO XML PARA LEITURA
$ponteiro = fopen("pedido_remessa.xml", "r");

//INICIA A AN�LISE DO DOCUMENTO XML
while($dados = fread($ponteiro, 4096)) {
	//INICIA A AN�LISE DO DOCUMENTO XML
	xml_parse($parser, $dados);
}//FECHA WHILE

//LIBERA A MEM�RIA USADA PELO PARSER
xml_parser_free($parser);
?>