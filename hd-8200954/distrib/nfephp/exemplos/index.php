<style>
#Menu 
{
	position:absolute;
	width:95%;
	height:44px;
	z-index:3;
	left: 4px;
	top: 175px;
} .tabbedmenu
{
    padding: 3px 0;
    margin-left: 0;                
    font: bold 12px Verdana;
    border-bottom: 2px groove #F16C0A;
    list-style-type: none;
    text-align: left;
}
.tabbedmenu ul 
{
    margin: 0;
    padding: 0;
    float: left;
}
.tabbedmenu li
{
    display: inline;
    position: relative;
    margin: 0;
}
.tabbedmenu li li{display: block;}
.tabbed menu li li:hover{display: inline;}
 
.tabbedmenu li a
{
    text-decoration: none;
    padding: 3px 7px;
    margin-right: 3px;
    border: 3px double gray;
    border-bottom: none;
    background-color: #000000;
    color: #FFFFFF;
}    
.tabbedmenu li a:hover
{
    background-color: #F16C0A;
    color: black;
}
.tabbedmenu li a:active {color: #FFFFFF;}
.tabbedmenu li a.selected
{
    position: relative;
    top: 1px;
    padding-top: 4px;
    background-color: #F16C0A;
    color: white;
}
</style>
<?php

echo "<p><h2> Menu de exemplos do NFePHP</h2></p>";

function writeMenu($selected="index")
{
	$links   = false;
	$sel = 'class="selected"'; //change the class for selected page here
	switch($selected)
	{
		case 'Converte TXT':
			$convertetxt = true;
			break;
		case 'Converte XML':
			$convertexml = true;
			break;
		case 'Adiciona Protocolo':
			$adicionaprotocolo = true;
			break;
		case 'Assina':
			$assina = true;
			break;
		case 'Imprime DANFE':
			$imprimedanfe = true;
			break;
		case 'Exmplo DANFE Telecontrol':
			$imprimedanfe = true;
			break;
		default: 
		case 'index':
			$index = true;
			break;
	}
	$pages = array("index", "converterTXT", "converterXML", "adicionaProtocolo", "assina", "printDANFE" , "printDANFE");   
 
	echo '<div id="Menu">'."\n\t".'<ul class="tabbedmenu">';
 
	for($i=0;$i<count($pages);$i++)
	{
		$html = "\n\t\t".'<li><a href="'.$pages[$i].'.php';
		if($pages[$i] == "printDANFE" and $i == 5){
			$html .= "?nfe=35100258716523000119550000000033453539003003-nfe.xml";
		}
		if($pages[$i] == "printDANFE" and $i == 6){
			$html .= "?nfe=35100507881054000152550030000035789999964211-nfe.xml";
		}
		$html .= '" ';
		$html .= ($$pages[$i]==true)?$sel:""; 
		$pages[0]= "home"; //pagename override
		$html .='>'.ucfirst($pages[$i]).'</a></li>';
		echo $html;
	}
	$html .= '</ul>'."\n".'</div>'."\n";
}

writeMenu();

php?>
