<?php
/********************************************************************
 *  10/3/2009   Imprime etiquetas para a Britânia, HD 64220         *
 *  Pega os dados do POST e gera uma página para impressăo, usando  *
 *  CSS para colocar as etiquetas no lugar.                         *
 *******************************************************************/

function iif($condition, $val_true, $val_false = "") {
//  Devolve '$val_true' se a condiçăo for 'true' e '$val_false' se a condiçăo for 'false'
    return ($condition) ? $val_true : $val_false;
}
function is_even($num) {
//  true se for par, false se for impar, null se năo for um valor válido
    if (!is_numeric($num)) return null;
    if (is_integer($num/2)) return true;
    return false;
}
?>
<HTML>
    <HEAD>
        <META http-equiv="content-type" content="text/html; charset=windows-1250">
        <META name="generator" content="PSPad editor, www.pspad.com">
        <TITLE>Etiquetas <?=date("Y-m-d");?></TITLE>
        <LINK rel="stylesheet" href="css/etiqueta_posto_impimir.css"
             type="text/css"  media="print,screen">
    </HEAD>
<BODY>

<H2>Impress&atilde;o de Etiquetas
    <INPUT type='button' value='Imprimir' onClick='window.print();'>
    <INPUT type='button' value='Fechar'   onClick='window.close();'>
</H2>

<TABLE name='tbl_etiquetas'>
<?  /*  Cria a tabela para impressăo. A paginaçăo é automática usando CSS   */
$pagina=0;  // Para quebra de página NA TELA, ajuda visual
for ($i=0;$i < 42;$i++) {
    if ($pagina++ == 15) $pagina = 0;
	$nome      = trim($_POST["posto_nome_" . $i]);
	$endereco  = trim($_POST["endereco_" . $i]);
	$bairro    = trim($_POST["bairro_" . $i]);
	$cidade_uf = trim($_POST["cidade_uf_" . $i]);
	$cep       = trim($_POST["cep_" . $i]);
    if (strlen($nome)>0) {
        echo (is_even($i)) ? "\t<TR class='et'>\n\t\t<TD class='l'>\n":"\t\t<TD class='r'>\n";
        echo "\t\t\t<DIV>\n";   // Este DIV é para evitar a quebra de página no meio da etiqueta
        echo "\t\t\t<H1>$nome</H1>\n";
        echo "\t\t\t$endereco" . iif(($endereco=="" or $bairro==""),""," &ndash; ") . $bairro."<BR>\n";
        echo "\t\t\t$cidade_uf<BR>\n";
        echo "\t\t$cep</DIV></TD>\n";
        if (is_even($i)) {
            echo "<TD class='m'>&nbsp;</TD>\n";
        }else{
            echo "\t</TR>\n\t<TR class='".iif(($pagina==14),"newpage","sep")."'><TD colspan='3'>";
            if ($pagina==14) echo "\t<H2>".str_repeat("_", 80)."</H2>\n";
            echo "&nbsp;</TD></TR>\n";
        }
    }
}
?>
</TABLE>
</BODY>
</HTML>
<? exit;?>
