<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';

    $login_fabrica = trim($_GET['login_fabrica']);
	$produto_referencia = trim($_GET['produto_referencia']);

    $sql = "SELECT
                tbl_produto.produto,
                tbl_linha.deslocamento
            FROM
               tbl_produto
               JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
            WHERE tbl_produto.referencia = '$produto_referencia'
            AND tbl_linha.fabrica = $login_fabrica
            AND tbl_linha.deslocamento IS TRUE";
     $res = pg_query($con, $sql);
   
    if(pg_num_rows($res) > 0){
       
        $deslocamento = pg_fetch_result($res, 0, "deslocamento");
        
        if($deslocamento === 't'){
            $com_deslocamento = 1;
        }
    }
    echo $com_deslocamento;
?>