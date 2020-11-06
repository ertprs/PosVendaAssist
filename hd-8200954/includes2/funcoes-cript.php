<?php 
/*************************************************************************** 
*  Fun��es: ipe_xor ipe_emboss TC_cript TC_dcript 
* 
*  Conjunto de fun��es que criptografam e descriptografam 
*  uma string informada, as fun��es ipe_xor e ipe_emboss s�o 
*  auxiliares das fun��es TC_cript(criptografa) e TC_dcript(descriptografa) 
* 
*  Criptografia fraca, n�o utilize para miss�es cr�ticas 
* 
*  Essas fun��es fazem parte da framework IPE(IPE Poral Engine) 
*  Interessados em entrar no projeto, contacte o autor 
* 
*  Autor: Fl�vio Gon�alves Garcia 
*  Contato: flavio@viacerrado.com 
*  Colabora��o: * 
*  Tipo: PHP4 - Fun��o 
*  Arquivo: ipe_global.functions.php 
*  Vers�o: 2.0.5 
*  Data de Cria��o:    15/06/2002 14:37:00 ViaCerrado 
*  �ltima atualiza��a: 29/09/2003 15:16:14 
*  Licen�a: GNU, se for alterar, me avisa... 
* 
*                                            Desenvolvimento - Montess 
****************************************************************************/ 


//Essas fun��es fazem parte de uma framework que estou desenvolvendo, por isso o nome ipe. 



//Essas chaves definir�o a criptografia, mude esses valores na sua p�gina, se eu tiver suas chaves eu entro na sua casa (l�gico n�!?) 
$EncC1  = 6588;
$EncC2  = 6589;
$EncKey = 9009;

//Esse fun��o faz um deslocamento no n�mero informado, n�o se preocupe com elas, ela � usda nas fun��es cript e dcript 
function ipe_emboss($Numero, $BShr){ 
    $Result = ($Numero/(pow(2,$BShr))); 
    settype($Result,"integer"); 
    return $Result; 
} 

//Implementa��o de um xor(na m�o), antigamente o xor do php n�o funcionava para fazer deslocamento de bits, anda continuarei utilizando minha fun��o, fique a vontade para detonar com ela, se tiver outra op��o 
function ipe_xor($exp1,$exp2){ 
    $bin1 = decbin($exp1); 
    $bin2 = decbin($exp2); 
    if (strlen($bin1)>strlen($bin2)){ 
        for($i=strlen($bin2);$i<strlen($bin1);$i++){ 
            $bin2 = "0".$bin2; 
        } 
    } 
    else{ 
        if (strlen($bin1)<strlen($bin2)){ 
            for($i=strlen($bin1);$i<strlen($bin2);$i++){ 
                $bin1 = "0".$bin1; 
            } 
        } 
    } 
    $str_temp = ""; 
    $exp_result = ""; 
    for($i=0;$i<strlen($bin1);$i++){ 
        $binchar1 = substr($bin1, $i, 1); 
        $binchar2 =    substr($bin2, $i, 1); 
        if ($binchar1 == $binchar2){ 
            $xor_result = 0; 
        } 
        else{ 
            $xor_result = 1; 
        } 
        $str_temp = $str_temp . $xor_result; 
    } 
    $exp_result = bindec($str_temp); 
    return $exp_result; 
} 

//Fun��o que criptografa uma string 

function TC_cript($Str){ 
    global $EncC1; 
    global $EncC2; 
    global $EncKey; 
     
    $TempStr = $Str; 
    $TempResult = ""; 
     
    $TempKey = (($EncKey * $EncC1) + $EncC2) % 65536; 
    for ($i=0;$i<strlen($TempStr);$i++){ 
        $TempNum = ipe_xor(ord(substr($TempStr, $i, 1)),ipe_emboss($TempKey, 8)) % 256; 
        $TempChar = chr($TempNum); 
        $TempKey = (((ord($TempChar) + $TempKey) * $EncC1) + $EncC2) % 65536; 
        $TempResult = $TempResult . $TempChar; 
    } 
    return $TempResult; 
} 

//Fun��o que descriptografa uma string 

Function TC_dcript($Str){ 
    global $EncC1; 
    global $EncC2; 
    global $EncKey; 
     
    $TempStr = $Str; 
    $TempResult = ""; 
     
    $TempKey = (($EncKey * $EncC1) + $EncC2) % 65536; 
     
    for ($i=0;$i<strlen($TempStr);$i++){ 
        $TempNum = ipe_xor(ord(substr($TempStr, $i, 1)),ipe_emboss($TempKey, 8)) % 256; 
        $TempChar = chr($TempNum); 
        $TempKey = (((ord(substr($TempStr, $i, 1)) + $TempKey) * $EncC1) + $EncC2) % 65536; 
        $TempResult = $TempResult . $TempChar; 
    } 
    return $TempResult; 
} 


/* Forma de utiliza��o */ 
/*

$string = "Eu sou uma string secreta!!!"; 

$crpt_string = TC_cript($string ); 

echo "String criptografada:  " . $crpt_string . "<br>"; 

echo "String descriptografada:  " . TC_dcript($crpt_string) . "<br>"; 

*/
?>