<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
?>

<!DOCTYPE html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Transportadora.. </title>
<meta http-equiv=pragma content=no-cache>

<style type="text/css">
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font: bold 11px "Arial";
    border-collapse: collapse;
    border:1px solid #596d9b;
}


</style>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_transportadora.gif" />

<?
if (strlen($_GET["tipo"]) > 0) $tipo = $_GET["tipo"];


if(in_array($login_fabrica,array(150,98))) {

    if ($tipo == "nome") {
        $nome = strtoupper (trim ($_GET["campo"]));

        echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome da Transportadora</b>: <i>$nome</i></font>";
        echo "<p>";
        
        $sql = "SELECT   distinct   tbl_transportadora.*
                FROM        tbl_transportadora
                WHERE      tbl_transportadora.nome ILIKE '%$nome%'
                ORDER BY    tbl_transportadora.nome";
        $res = pg_query ($con,$sql);
        
        if (pg_num_rows ($res) == 0) {
            echo "<h1>Transportadora '$nome' não encontrada</h1>";
            echo "<script language='javascript'>";
            echo "setTimeout('transportadora.value=\"\"; codigo.value=\"\"; nome.value=\"\"; cnpj.value=\"\"; window.close();',2500);";
            echo "</script>";
            exit;
        }

    }elseif ($tipo == "cnpj") {
        $cnpj = strtoupper (trim ($_GET["campo"]));
        $cnpj = str_replace (" ","",$cnpj);
        $cnpj = str_replace (".","",$cnpj);
        $cnpj = str_replace ("/","",$cnpj);
        $cnpj = str_replace ("-","",$cnpj);

        echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ da Transportadora</b>: <i>$cnpj</i></font>";
        echo "<p>";
        
        $sql = "SELECT      tbl_transportadora.*
                FROM        tbl_transportadora
                WHERE       tbl_transportadora.cnpj LIKE '%$cnpj%'
                ORDER BY    tbl_transportadora.nome";
        $res = pg_query ($con,$sql);
        
        if (pg_num_rows ($res) == 0) {
            echo "<h1>CNPJ $cnpj não encontrado</h1>";
            echo "<script language='javascript'>";
            echo "setTimeout('transportadora.value=\"\"; codigo.value=\"\"; nome.value=\"\"; cnpj.value=\"\"; window.close();',2500);";
            echo "</script>";
            exit;
        }
    }
}elseif ($tipo == "nome") {
    $nome = strtoupper (trim ($_GET["campo"]));

        $sql = "SELECT      tbl_transportadora.*                        ,
                            tbl_transportadora_fabrica.codigo_interno
                FROM        tbl_transportadora
                JOIN        tbl_transportadora_fabrica ON tbl_transportadora_fabrica.transportadora = tbl_transportadora.transportadora
                WHERE       tbl_transportadora_fabrica.fabrica = $login_fabrica
                AND         tbl_transportadora.nome ILIKE '%$nome%'
                ORDER BY    tbl_transportadora.nome";

    $res = pg_query ($con,$sql);

    if (pg_num_rows ($res) == 0) {
?>
    <h1>Transportadora <?=$nome?> não encontrada</h1>
    <script type="text/javascript">
        setTimeout('transportadora.value="",nome.value="",cnpj.value="",window.close();',2500);
    </script>
<?
        exit;
    }

}elseif ($tipo == "cnpj") {
    $cnpj = strtoupper (trim ($_GET["campo"]));

    $cnpj = str_replace (" ","",$cnpj);
    $cnpj = str_replace (".","",$cnpj);
    $cnpj = str_replace ("/","",$cnpj);
    $cnpj = str_replace ("-","",$cnpj);

        $sql = "SELECT  tbl_transportadora.*                        ,
                        tbl_transportadora_fabrica.codigo_interno
                FROM    tbl_transportadora
                JOIN    tbl_transportadora_fabrica ON tbl_transportadora_fabrica.transportadora = tbl_transportadora.transportadora
                WHERE   tbl_transportadora_fabrica.fabrica = $login_fabrica
                AND     tbl_transportadora.cnpj LIKE '%$cnpj%'
          ORDER BY      tbl_transportadora.nome";

    $res = pg_query ($con,$sql);

    if (pg_num_rows ($res) == 0) {
?>
        <h1>CNPJ <?=$cnpj?> não encontrado</h1>
        <script type="text/javascript">
            setTimeout('transportadora.value="",nome.value="",cnpj.value="",window.close();',2500);
        </script>
<?
        exit;
    }
}elseif ($tipo == "codigo") {
    $codigo = strtoupper (trim ($_GET["campo"]));

    //echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CODIGO da Transportadora</b>: <i>$cnpj</i></font>";
    //echo "<p>";
        $sql = "SELECT  tbl_transportadora.*                        ,
                        tbl_transportadora_fabrica.codigo_interno
                FROM    tbl_transportadora
                JOIN    tbl_transportadora_fabrica ON tbl_transportadora_fabrica.transportadora = tbl_transportadora.transportadora
                WHERE   tbl_transportadora_fabrica.fabrica          = $login_fabrica
                AND     tbl_transportadora_fabrica.codigo_interno   = '$codigo'
          ORDER BY      tbl_transportadora.nome";

    $res = pg_query ($con,$sql);
    if (pg_num_rows ($res) == 0) {
?>
        <h1>CODIGO <?=$codigo?> não encontrado</h1>
        <script type="text/javascript">
            setTimeout('transportadora.value="",nome.value="",cnpj.value="",window.close();',2500);
        </script>
<?
        exit;
    }
}else{
    //echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Transportadora</b>: <i>$cnpj</i></font>";
    //echo "<p>";

    $sql = "SELECT      tbl_transportadora.*                        ,
                        tbl_transportadora_fabrica.codigo_interno
            FROM        tbl_transportadora
            JOIN        tbl_transportadora_fabrica ON tbl_transportadora_fabrica.transportadora = tbl_transportadora.transportadora
            WHERE       tbl_transportadora_fabrica.fabrica = $login_fabrica
            ORDER BY    tbl_transportadora.nome";

    $res = pg_query ($con,$sql);

    if (pg_num_rows ($res) == 0) {
        echo "<h1>Transportadora não encontrada</h1>";
        echo "<script language='javascript'>";
        echo "setTimeout('transportadora.value=\"\",nome.value=\"\",cnpj.value=\"\",window.close();',2500);";
        echo "</script>";
        exit;
    }

}

if (pg_num_rows ($res) > 0 ) {
?>
    <script type="text/javascript">
    <!--
        this.focus();
     -->
    </script>

    <table width="100%" border="0" cellspacing="1" class="tabela">
<?
    if($tipo=="nome"){
?>
        <tr class="titulo_tabela">
            <td colspan="<?if($login_fabrica != 98){ echo "3"; }else{ echo "2";}?>" style="font-size:14px;">
                Pesquisando por <b>nome da Transportadora</b>: <?=$nome?>
            </td>
        </tr>
<?
    }
    elseif($tipo=="cnpj"){
?>
        <tr class="titulo_tabela">
            <td colspan="<?if($login_fabrica != 98){ echo "3"; }else{ echo "2";}?>" style='font-size:14px;">
                Pesquisando por <b>CNPJ da Transportadora</b>: <?=$cnpj?>
            </td>
        </tr>
<?
    }
    elseif($tipo=="codigo"){
?>
        <tr class="titulo_tabela">
            <td colspan="<?if($login_fabrica != 98){ echo "3"; }else{ echo "2";}?>" style="font-size:14px;">
                Pesquisando por <b>CODIGO da Transportadora</b>: <?=$cnpj?>
            </td>
        </tr>
<?
    }else{
?>
        <tr class="titulo_tabela">
            <td colspan="<?if($login_fabrica != 98){ echo "3"; }else{ echo "2";}?>" style="font-size:14px;">
                Pesquisando por <b>Transportadora</b>: <?=$cnpj?>
            </td>
        </tr>
<?
    }
?>
    <tr class="titulo_coluna">
        <td>CNPJ</td>
<?
    if($login_fabrica != 98){
?>
        <td>Código</td>
<?
}
?>
        <td>Nome</td>
    </tr>
<?
    for ( $i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
        if(!in_array($login_fabrica,array(98,150))){
            $codigo_interno   = trim(pg_fetch_result($res,$i,codigo_interno));
        }
        $transportadora   = trim(pg_fetch_result($res,$i,transportadora));
        $nome             = trim(pg_fetch_result($res,$i,nome));
        $cnpj             = trim(pg_fetch_result($res,$i,cnpj));

        if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
?>
    <tr style="background-color:<?=$cor?>">
        <td>
<?
        if(strlen($cnpj) > 0){
            echo $cnpj;
        }else{
            echo "&nbsp;";
        }
?>
        </td>
<?
        if(!in_array($login_fabrica,array(98,150))){
?>
        <td><?=$codigo_interno?></td>
<?
        }
?>
        <td>
<?
        if ($_GET['forma'] == 'reload') {
?>
            <a href="javascript: opener.document.location = retorno + '?transportadora=<?=$transportadora?>' ; this.close() ;" >
<?
        }else{
?>
            <a href="javascript: transportadora.value='<?=$transportadora?>'; <? if (!in_array($login_fabrica,array(98,150))){ ?>codigo.value='<?=$codigo_interno?>';<? }?> nome.value='<?=$nome?>'; cnpj.value = '<?=$cnpj?>'; window.close();">
            <?=$nome?>
            </a>
<?
        }
?>
        </td>
    </tr>
<?
    }
?>
</table>
<?
}
?>

</body>
</html>