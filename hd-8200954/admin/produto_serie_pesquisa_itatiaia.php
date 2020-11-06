<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$mapa_linha = trim (strtolower ($_REQUEST['mapa_linha']));
$tipo       = trim (strtolower ($_REQUEST['tipo']));
$serie      = trim($_REQUEST["campo"]);
$pos        = trim($_REQUEST["pos"]);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title> Pesquisa Produto... </title>
    <meta name="Author" content="">
    <meta name="Keywords" content="">
    <meta name="Description" content="">
    <meta http-equiv=pragma content=no-cache>
    <link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
    <script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
    <script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
    <script src="js/thickbox.js" type="text/javascript"></script>
    <style type="text/css">
    @import "../css/lupas/lupas.css";
    body {
        margin: 0;
        font-family: Arial, Verdana, Times, Sans;
        background: #fff;
    }
    </style>
    <script type="text/javascript">
        $(document).ready(function() {
        $("#gridRelatorio").tablesorter();
    });
    </script>
</head>
<body>
<div class="lp_header">
    <a href='' onclick='window.parent.Shadowbox.close();' style='border: 0;'>
        <img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
    </a>
</div>

<div class='lp_nova_pesquisa' style="text-align: center;">
    <form action="<?=$_SERVER["PHP_SELF"]?>" method='POST' name='nova_pesquisa'>
        <input type="hidden" name="mapa_linha" value="<?=$mapa_linha?>" />
        <input type="hidden" name="tipo" value="<?=$tipo?>" />
        <input type="hidden" name="pos" value="<?=$pos?>" />
        <label>Série: </label><input type="text" name="campo" value="<?=$serie?>" placeholder="Digite a série..." />
        <input type="submit" value="Pesquisar" />
    </form>
</div>
<?php 
if ($tipo == "serie") {
    if(strlen($serie) > 0) {
        $serie = strtoupper($serie); 
        ?>
        <div class='lp_pesquisando_por'>Pesquisando por série: <?=$serie?></div>
        <?php
            $conteudo = array(
                "Registros" => array(
                    array("NumSerie" => $serie)
                )
            );

            $curl = curl_init();

            if ($_serverEnvironment == "development") {
                $curlUrl = "https://piqas.cozinhasitatiaia.com.br/RESTAdapter/BuscarSerial";
                $curlPass = "aXRhYWJhcDpBYmFwMjAxOA==";
            } else {
                $curlUrl = "https://pi.cozinhasitatiaia.com.br/RESTAdapter/BuscarSerial";
                $curlPass = "UElTVVBFUjppdGExMjM0NQ==";
            }
            #0057014469000455
            curl_setopt_array($curl, array(
                CURLOPT_URL => $curlUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POSTFIELDS => json_encode($conteudo),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic {$curlPass}",
                    "Content-Type: application/json"
                ),
            ));
        
            $response = curl_exec($curl);
            $err = curl_error($curl);
        
            $response = json_decode($response,1);
            $referencia_produto = $response["MT_Telecontrol_BuscarSerial_response"]["Produto"]["NomProduto"];
            $referencia_produto = ltrim($referencia_produto, "0");

            $serie_produto = $response["MT_Telecontrol_BuscarSerial_response"]["Produto"]["NumSerie"];
            $serie_produto = ltrim($serie_produto, "0");
	    
	    if (strlen(trim($referencia_produto)) > 0){
            	$sql = "
                    SELECT 
                    	tbl_produto.referencia,
                    	tbl_produto.descricao,
                    	tbl_produto.voltagem,
                    	tbl_produto.produto,
                    	tbl_produto.linha
                     FROM tbl_produto
               	     WHERE tbl_produto.fabrica_i = {$login_fabrica}
		     AND tbl_produto.ativo IS TRUE
                     AND tbl_produto.referencia ILIKE '%$referencia_produto%'";
            	$res = pg_query($con, $sql);
	 
                if(pg_num_rows($res) >= 1) {
           	 ?>
            		<table style='width:100%; border: 0;' cellspacing='1' class='lp_tabela' id='gridRelatorio'>
               	 		<thead>
                    			<tr>
                       				<th>Série</th>
                        			<th>Referência</th>
                        			<th>Descrição</th>
                        			<th>Linha</th>
                        			<th>Voltagem</th>
                    			</tr>
                		</thead>
                		<tbody>
                    		<?php
                    		for($i = 0; $i < pg_num_rows($res); $i++) {
                        		$cor        = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
                        		$produto    = pg_fetch_result($res, $i, 'produto');
                        		$ordem      = pg_fetch_result($res, $i, 'ordem');
                        		$referencia = pg_fetch_result($res, $i, 'referencia');
                       			$descricao  = pg_fetch_result($res, $i, 'descricao');
                        		$descricao = utf8_decode($descricao);
                        
                       			$linha      = pg_fetch_result($res, $i, 'linha');
                       			$voltagem   = pg_fetch_result($res, $i, 'voltagem');
                    
                        		$onclick = (trim($descricao)  != '' ? "'$descricao'"    : "''")   .
                                   	(trim($referencia) != '' ? ", '$referencia'" : ", ''") .
                                   	(trim($serie)      != '' ? ", '$serie'"      : ", ''") .
                                  	(trim($voltagem)   != '' ? ", '$voltagem'"   : ", ''") .
                                   	(trim($produto)    != '' ? ", $produto"      : ", ''") .
                                   	(trim($ordem)      != '' ? ", '$ordem'"      : ", ''") .
                                   	(($mapa_linha == 't')    ? ", $linha"        : ", ''") .
                                   	((strlen($pos) > 0)      ? ", '$pos'"        : ", ''");

                        		$mostraDefeitos = " window.parent.mostraDefeitos('Reclamado', '".$referencia."');";
                        
                        		echo "<tr style='background: $cor' onclick=\"window.parent.retorna_serie($onclick);$mostraDefeitos window.parent.Shadowbox.close();\">
                                		<td style='text-align: center;'>$serie</td>
                                		<td style='text-align: center;'>$referencia</td>
                                		<td style='text-align: center;'>$descricao</td>
                                		<td style='text-align: center;'>$linha</td>
                                		<td style='text-align: center;'>$voltagem</td>
                            		</tr>";
                    		}
                    		?>
               			</tbody>
            		</table>
        	<?php
        	}
	 }else{?>
		<div class='lp_msg_erro'>Produto com a série '<?=$serie?>' não encontrado</div>	
	<?php
	}
    } else { ?>
        <div class='lp_msg_erro'>Informar toda ou parte da informação para realizar a pesquisa!</div>
<?php 
    } 
} 
?>
</body>
</html>
