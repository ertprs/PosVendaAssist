<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";

include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "cadastro";
$title = "Mostra Valor de Troca Faturada";
if ( (strlen($_POST["listarTudo"]) > 0) && $_POST['gerar_excel'] ) { //703506
    
    $sql = "SELECT tbl_produto.produto   ,
 				tbl_produto.ativo     ,
                tbl_produto.uso_interno_ativo,
 				tbl_produto.referencia,
 				tbl_produto.descricao ,
                tbl_produto.voltagem  ,
                valor_troca*(1+(ipi/100)) AS valor_troca
        FROM    tbl_produto
        JOIN    tbl_linha     USING (linha)
        LEFT JOIN tbl_familia USING (familia)
        WHERE   tbl_linha.fabrica = $login_fabrica 
		AND tbl_produto.fabrica_i = $login_fabrica
		ORDER BY tbl_produto.referencia";

    $resList = pg_query($con,$sql);

    if ( pg_num_rows($resList) > 0) {

        $file     = "xls/relatorio-produtos-{$login_fabrica}.xls";
        $fileTemp = "/tmp/relatorio-produtos-{$login_fabrica}.xls" ;
        $fp     = fopen($fileTemp,"w");
        
        $colspan = 5;
        
        $head = "<table border='1'>
                    <thead>
                        <tr >
                            <th bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' colspan='$colspan' >Relatório Valor Troca Faturada</th>
                        </tr>
                        <tr>
                       		<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status Rede</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Referência</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Voltagem</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Valor Troca</th>
        				</tr>
                    </thead>
                    <tbody>";

        fwrite($fp, $head );

        for ( $i = 0; $i < pg_num_rows($resList); $i++ ) {
        	$ativo;
            
            pg_result($resList,$i,'ativo');
			if(pg_result($resList,$i,'ativo') == 't'){
				$ativo = 'ativo';
			}else{
				$ativo = 'inativo';
			}
			$body = '<tr>
                        <td>' . $ativo . '</td>
                        <td>' . pg_result($resList,$i,'referencia') . '</td>
                        <td>' . pg_result($resList,$i,'descricao') . '</td>
                        <td>' . pg_result($resList,$i,'voltagem') . '</td>';

                        $valor_troca = trim(pg_fetch_result($resList,$i,'valor_troca'));

                    	if(strlen($valor_troca) == 0){
                    		$valor_troca = 0;
                    	}
                    	
            $body .= 	'<td>' . number_format($valor_troca,2,",",".") . '</td>
                      </tr>';
            

            fwrite($fp, $body);

        }

        fwrite($fp, '</tbody></table>');
        fclose($fp);
        if(file_exists($fileTemp)){
            system("mv $fileTemp $file");

            if(file_exists($file)){
                echo $file;    
            }
        }
        
        
        exit;
    }

}





include "cabecalho_new.php";

$plugins = array("dataTable",
                 "price_format"
            );

include ("plugin_loader.php");

?>
</div>
<?php
 $sql = "SELECT tbl_produto.produto   ,
 				tbl_produto.ativo     ,
                tbl_produto.uso_interno_ativo,
 				tbl_produto.referencia,
 				tbl_produto.descricao ,
                tbl_produto.voltagem  ,
                valor_troca*(1+(ipi/100)) AS valor_troca
        FROM    tbl_produto
        JOIN    tbl_linha     USING (linha)
        LEFT JOIN tbl_familia USING (familia)
        WHERE   tbl_linha.fabrica = $login_fabrica 
		AND tbl_produto.fabrica_i = $login_fabrica
		ORDER BY tbl_produto.referencia
		";

    if ($login_fabrica <> 1) {
        $sql .= " limit 500";
    }
    $res = pg_query ($con,$sql);
    $count = pg_num_rows($res);
    if($count >= 500 AND $login_fabrica <> 1){
    ?>
        <div id='registro_max'>
            <h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
        </div>
    <? } ?>
     <table id="listagemProduto" style="margin: 0 auto;" class="tabela_item table table-striped table-bordered table-hover table-large">
                    <thead>
                        <tr class='titulo_coluna'>
                            <th nowrap>Status Rede</th>
                            <th nowrap>Status Interno</th>
                            <th>Referência</th>
                            <th>Descrição</th>
                            <th class='tac' >Voltagem</th>
                            <th class='tac' >Valor Troca</th>
                        </tr>
                    </thead>

                <tbody>
    <?php
    for ($i = 0; $i < $count; $i++) {
        ?>
           
                    <tr>
                        <td class='tac'>
                        <?if (pg_fetch_result($res,$i,ativo) <> 't') {?>
                             <img src='imagens/status_vermelho.png' border='0' align="center" title='Inativo' alt='Inativo'>
                        <?}else{?>
                             <img src='imagens/status_verde.png' border='0' title='Ativo' alt='Ativo'> 
                        <?}?>
                        </td>
                        <td class='tac'>
                        <?if (pg_fetch_result($res,$i,uso_interno_ativo) <> 't'){?>
                            <img src='imagens/status_vermelho.png' border='0' title='Inativo' alt='Inativo'> 
                        <?}else{?>
                             <img src='imagens/status_verde.png' border='0' title='Ativo' alt='Ativo'> 
                        <?}?>
                        </td>

                        <td align='left' nowrap>
                        <? echo pg_fetch_result($res,$i,referencia);

                        
                             if (strlen(pg_fetch_result($res,$i,voltagem)) > 0) 
                                echo " / ". pg_fetch_result($res,$i,voltagem);
                        ?>

                        </td>
                        <td align='left' nowrap>
                        
                            <a href='<?="produto_cadastro.php?produto=" . pg_fetch_result($res,$i,produto)?>'>
                                <?=pg_fetch_result($res,$i,descricao)?>
                            </a>
                        </td>
                        
                        <td align='left' nowrap>
                            <? echo pg_fetch_result($res,$i,voltagem);?>
                        </td>
                        <td align='left' nowrap>
                            <? 
                            	$valor_troca = trim(pg_fetch_result($res,$i,'valor_troca'));

                            	if(strlen($valor_troca) == 0){
                            		$valor_troca = 0;
                            	}
                            	echo number_format($valor_troca,2,",",".");

                            ?>
                        </td>
                </tr>
            <?}?>
            </tbody>
        </table>
        <?php
            if ($count > 50) {
            ?>
                <script>
                    $.dataTableLoad({
                        table : "#listagemProduto"
                    });
                </script>
            <?php
            }?>
<br/>      
    
    <div id='gerar_excel' class="btn_excel">
        <input type="hidden" id="jsonPOST" value='<?=json_encode(array("listarTudo" => "1"))?>' />
        <span><img src='imagens/excel.png' /></span>
        <span class="txt">Gerar Arquivo Excel</span>
    </div>
<div id="container">
<?php include "rodape.php"; ?>