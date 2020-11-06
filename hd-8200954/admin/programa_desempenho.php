<?php
/**
*
* Funcao que nao deixa o programa ser chamado mais de uma vez em menos de X minutos
*
**/

//error_reporting(E_ALL);
$ipString=@getenv("HTTP_X_FORWARDED_FOR"); 
$addr = explode(",",$ipString); 
$ip   = $addr[sizeof($addr)-1];

$programa = $_SERVER['PHP_SELF'];
//echo $programa ;

$sql = "SELECT TO_CHAR (ultimo_acesso,'YYYY-MM-DD HH24:MI') AS ultimo_acesso, admin FROM tbl_programa_desempenho WHERE fabrica = $login_fabrica AND programa = '$programa' ORDER BY ultimo_acesso DESC LIMIT 1";
$res = pg_exec ($con,$sql);
if (pg_numrows($res) == 1)
{
    $ultimo_acesso = pg_result ($res,0,ultimo_acesso);
    echo $ultimo_acesso;
    $ultimo_acesso = new DateTime ($ultimo_acesso);
    print_r( $ultimo_acesso);
    $proximo_acesso = $ultimo_acesso->add(new DateInterval('PT5M'));
    echo "==>";
    print_r ( $proximo_acesso );
    $proximo_acesso = $ultimo_acesso->format ("Y-m-d H:i");
    echo "==>##";
    echo ( $proximo_acesso );
    $agora = date ("Y-m-d H:i");
    echo "***agora $agora ***";

    if ($agora < $proximo_acesso)
    {
	echo "Ainda falta...";
	exit;
    }
}



/**
// la embaixo, quando for dar o select para rodar o relatorio
// Tem que por a hora na mao por causa da diferenca do TimeZone no PHP pro Banco
$agora = date ("Y-m-d H:i");
$sql = "INSERT INTO tbl_programa_desempenho (fabrica, admin, programa,ultimo_acesso) VALUES ($login_fabrica, $login_admin, '$programa','$agora')";

echo $sql;
$res = pg_exec ($con,$sql);
**/
