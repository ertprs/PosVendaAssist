<?
//FUNÇÃO DA TAG INICIAL DO ARQUIVO XML
function FuncInicial($parser, $elemento) {
	if($elemento == "PEDIDO-REMESSA") {
		echo "<table cellpading=0 cellspacing=0 border=0 width=50%>";
		echo "<tr><td bgcolor=0099CC align=center>";
		echo "<font face=Arial size=2 color=FFFFFF><b>REMESSA DE PEDIDOS";
	}
	elseif($elemento == "DADOS-PEDIDO")
		echo "<tr><td height=20>";
	
	elseif($elemento == "FABRICA") {
		echo "<tr><td bgcolor=#C1F0FF>";
		echo "<font face=Arial size=2><b>FÁBRICA: ";
	}
	elseif($elemento == "POSTO") {
		echo "<tr><td bgcolor=#DDF7FF>";
		echo "<font face=Arial size=2>POSTO: ";
	}
	elseif($elemento == "PEDIDO") {
		echo "<tr><td bgcolor=#DDF7FF>";
		echo "<font face=Arial size=2>PEDIDO: ";
	}
	elseif($elemento == "PEDIDO-CLIENTE") {
		echo "<tr><td bgcolor=#DDF7FF>";
		echo "<font face=Arial size=2>PEDIDO CLIENTE: ";
	}
	elseif($elemento == "CONDICAO") {
		echo "<tr><td bgcolor=#DDF7FF>";
		echo "<font face=Arial size=2>CONDIÇÃO: ";
	}
	elseif($elemento == "TIPO-PEDIDO") {
		echo "<tr><td bgcolor=#DDF7FF>";
		echo "<font face=Arial size=2>TIPO PEDIDO: ";
	}
}//FECHA FUNCTION FUNCINICIAL


//FUNÇÃO PARA EXIBIR OS DADOS DO DOCUMENTO XML
function FuncDados($parser, $dados) {
	echo $dados;
}//FECHA FUNCTION FUNCINICIAL


//FUNÇÃO DA TAG INICIAL DO DOCUEMENTO XML
function FuncFinal($parser, $elemento) {
	if($elemento == "PEDIDO-REMESSA")
		echo "</b></font></td</tr></table>";
	elseif($elemento == "DADOS-PEDIDO")
		echo "</td></tr>";
	elseif($elemento == "FABRICA")
		echo "</b></font></td></tr>";
	elseif($elemento == "POSTO")
		echo "</font></td></tr>";
	elseif($elemento == "PEDIDO")
		echo "</font></td></tr>";
	elseif($elemento == "PEDIDO-CLIENTE")
		echo "</font></td></tr>";
	elseif($elemento == "CONDICAO")
		echo "</font></td></tr>";
	elseif($elemento == "TIPO-PEDIDO")
		echo "</font></td></tr>";
}//FECHA FUNCTION FUNCFINAL

//CRIA O PARSER XML
$parser = xml_parser_create();

//DEFINE AS FUNÇÕES
xml_set_element_handler($parser, "FuncInicial", "FuncFinal");
xml_set_character_data_handler($parser, "FuncDados");

//ABRE O ARQUIVO XML PARA LEITURA
$ponteiro = fopen("pedido_remessa.xml", "r");

//INICIA A ANÁLISE DO DOCUMENTO XML
while($dados = fread($ponteiro, 4096)) {
	//INICIA A ANÁLISE DO DOCUMENTO XML
	xml_parse($parser, $dados);
}//FECHA WHILE


//LIBERA A MEMÓRIA USADA PELO PARSER
xml_parser_free($parser);




echo "<br>";



function xmlPedido($filename) {
	// lê o arquivo XML
	$data = implode("", file($filename));
	$parser = xml_parser_create();
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, $data, $values, $tags);
	xml_parser_free($parser);
	
	// loop through the structures
	foreach ($tags as $key=>$val) {
		if ($key == "dados-pedido") {
			$ranges = $val;
			
			for ($i=0; $i < count($ranges); $i+=2) {
				$offset = $ranges[$i] + 1;
				$len = $ranges[$i + 1] - $offset;
				
				$x = parse(array_slice($values, $offset, $len));
				
				$fabrica        = $x["fabrica"];
				$posto          = $x["posto"];
				$pedido         = $x["pedido"];
				$pedido_cliente = $x["pedido-cliente"];
				$condicao       = $x["condicao"];
				$tipo_pedido    = $x["tipo-pedido"];
				$peca           = $x["peca"];
				if (strlen($fabrica) > 0)
					echo $fabrica ."\t". $posto ."\t". $pedido ."\t". $pedido_cliente ."\t". $condicao ."\t". $tipo_pedido ."<br>";
			}
		} else {
			continue;
		}
	}
}

function xmlItemPedido($filename) {
	// lê o arquivo XML
	$data = implode("", file($filename));
	$parser = xml_parser_create();
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, $data, $values, $tags);
	xml_parser_free($parser);
	
	// loop through the structures
	foreach ($tags as $key=>$val) {
		if ($key == "dados-item-pedido") {
			$ranges = $val;
			
			for ($i=0; $i < count($ranges); $i+=2) {
				$offset = $ranges[$i] + 1;
				$len = $ranges[$i + 1] - $offset;
				
				$x = parse(array_slice($values, $offset, $len));
				
				$pedido         = $x["pedido"];
				$peca           = $x["peca"];
				$qtde           = $x["qtde"];
				
				if (strlen($pedido) > 0)
					echo $pedido ."\t". $peca ."\t". $qtde ."<br>";
			}
		} else {
			continue;
		}
	}
}

function parse($mvalues) {
	for ($i=0; $i < count($mvalues); $i++)
	$chave[$mvalues[$i]["tag"]] = $mvalues[$i]["value"];
	return $chave;
}

echo "PEDIDOS<br>";
$db = xmlPedido("pedido_remessa.xml");
echo "<br><br>";

echo "ITENS DO PEDIDOS<br>";
$db = xmlItemPedido("item_pedido_remessa.xml");
?>