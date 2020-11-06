<?php 
/*************************************************************************** 
*  Funções: ipe_xor ipe_emboss TC_cript TC_dcript 
* 
*  Conjunto de funções que criptografam e descriptografam 
*  uma string informada, as funções ipe_xor e ipe_emboss são 
*  auxiliares das funções TC_cript(criptografa) e TC_dcript(descriptografa) 
* 
*  Criptografia fraca, não utilize para missões críticas 
* 
*  Essas funções fazem parte da framework IPE(IPE Poral Engine) 
*  Interessados em entrar no projeto, contacte o autor 
* 
*  Autor: Flávio Gonçalves Garcia 
*  Contato: flavio@viacerrado.com 
*  Colaboração: * 
*  Tipo: PHP4 - Função 
*  Arquivo: ipe_global.functions.php 
*  Versão: 2.0.5 
*  Data de Criação:    15/06/2002 14:37:00 ViaCerrado 
*  Última atualizaçõa: 29/09/2003 15:16:14 
*  Licença: GNU, se for alterar, me avisa... 
* 
*                                            Desenvolvimento - Montess 
****************************************************************************/ 


//Essas funções fazem parte de uma framework que estou desenvolvendo, por isso o nome ipe. 



//Essas chaves definirão a criptografia, mude esses valores na sua página, se eu tiver suas chaves eu entro na sua casa (lógico né!?) 
$EncC1  = 6588;
$EncC2  = 6589;
$EncKey = 9009;

//Esse função faz um deslocamento no número informado, não se preocupe com elas, ela é usda nas funções cript e dcript 
function ipe_emboss($Numero, $BShr){ 
    $Result = ($Numero/(pow(2,$BShr))); 
    settype($Result,"integer"); 
    return $Result; 
} 

//Implementação de um xor(na mão), antigamente o xor do php não funcionava para fazer deslocamento de bits, anda continuarei utilizando minha função, fique a vontade para detonar com ela, se tiver outra opção 
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

//Função que criptografa uma string 

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

//Função que descriptografa uma string 

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


/* Forma de utilização */ 
/*

$string = "Eu sou uma string secreta!!!"; 

$crpt_string = TC_cript($string ); 

echo "String criptografada:  " . $crpt_string . "<br>"; 

echo "String descriptografada:  " . TC_dcript($crpt_string) . "<br>"; 

*/
?>