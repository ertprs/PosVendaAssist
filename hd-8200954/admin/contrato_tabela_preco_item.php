<?php

	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';
    include_once '../class/AuditorLog.php';

	$tabela	= trim($_REQUEST["tabela"]);

    extract(@$_POST);

    if(!empty($action)){
        if(!empty($tabela_item) AND $action == 'apagar'){
            $sql = "DELETE FROM tbl_contrato_tabela_item WHERE contrato_tabela_item = {$tabela_item}";
            if(pg_query($con, $sql)) {
                echo "ok";
            }
            exit;
        }

        if(!empty($tabela) AND $action == 'gravarDados'){
            $valor = number_format($valor,'2','.','');

            $auditorLog = new AuditorLog;
            $auditorLog->retornaDadosSelect("SELECT tbl_contrato_tabela_item.preco, tbl_produto.descricao AS produto 
                                               FROM tbl_contrato_tabela_item 
                                               JOIN tbl_produto ON tbl_produto.produto = tbl_contrato_tabela_item.produto AND tbl_produto.fabrica_i={$login_fabrica} 
                                               WHERE tbl_contrato_tabela_item.contrato_tabela_item =".$tabela_item);

            $sql = "UPDATE tbl_contrato_tabela_item SET preco = '$valor' WHERE contrato_tabela_item = {$tabela_item}";
            if(pg_query($con, $sql)) {
                $auditorLog->retornaDadosSelect()->enviarLog('update', 'tbl_contrato_tabela_item', trim($login_fabrica.'*'.$tabela));
                echo "ok";
            } else{
                echo "<script type='text/javascript'>window.alert('Ocorreu um erro ao atualizar as informação da peça!');</script>";
            }
            exit;
        }
    }

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}

            #gridRelatorio{
                margin: 20px 10px;
            }

            .frm{
                border: 1px solid #CCC
            }

            .mensagem{
                background-color: #D9E2EF;
                border: 1px solid #000;
                color: #000;  
                padding: 5px;
                width: 690px;
                margin: 5px auto;
                text-align: left;

            }

            .lp_pesquisando_por span{
                color: #F00;
                font-size: 80%;
                text-align: left;
                display: block;
            }

            .titulo_tabela{
                background-color:#596d9b;
                font: bold 12px "Arial";
                color:#FFFFFF;
                text-align:center;
            }

           
            .titulo_coluna th{
                background-color:#596d9b;
                font: bold 11px "Arial";
                color:#FFFFFF;
                text-align:center;
            }

            .msg_erro{
                background-color:#FF0000;
                font: bold 16px "Arial";
                color:#FFFFFF;
                text-align:center;
                margin: 0 10px;
                padding: 3px 0;
            }

            .formulario{
                background-color:#D9E2EF;
                font:11px Arial;
                text-align:left;
            }

            .subtitulo{
                background-color: #7092BE;
                font:bold 11px Arial;
                color: #FFFFFF;
            }

             table.tabela{
                margin-top: 20px;
             }

            table.tabela tr td{
                font-family: verdana;
                font-size: 11px;
                border-collapse: collapse;
                border:1px solid #596d9b;
                padding: 1px 2px;
            }

            table.tabela tr:hover{
                background: #CCC;
            }

            #box_gravacao{
                width: 98%;
                margin: 0 auto;
            }


            #box_gravacao #box_gravacao_toogle{
                text-align: right;
                padding-right: 10px;
                font-size: 12px;
                color: #0E2F64;
                cursor: pointer;
                font-weight: bold;
            }

        erro{
            display: none;
        }
		</style>
 		<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css" />
      <link rel="stylesheet" type="text/css" href="../js/jquery.autocomplete.css" />
		<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
		<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
        <script src="../js/jquery.autocomplete.1.1.js" type="text/javascript"></script>
        <script src="js/jquery.price_format.1.5.js" type="text/javascript"></script>
		<script type='text/javascript'>
			//função para fechar a janela caso a telca ESC seja pressionada!
			$(window).keypress(function(e) { 
				if(e.keyCode == 27) { 
					 window.parent.Shadowbox.close();
				}
			});

			$(document).ready(function() {
				$("#gridRelatorio").tablesorter();

                $("#gridRelatorio input[type='button']").click(function(){
                    var tabela_item = $(this).attr('alt');

                    if(confirm("Deseja realmente apagar este registro!")){
                        $.ajax({
                            type    : "POST",  
                            url     : "<?php echo $PHP_SELF;?>",  
                            data    : "action=apagar&tabela_item=" + tabela_item,
                            success : function(retorno){
                                if(retorno == 'ok'){
                                    $("#apagar_"+tabela_item).parent().parent().fadeOut("slow");
                                }

                                $("#gridRelatorio").tablesorter();
                            }  
                        });
                    }else
                        return false;
                });

                $("#gridRelatorio input[type='text']").blur(function(){
                    var tabela_item = $(this).attr('alt');

                    if(tabela_item.length > 0){
                       atualizaTabelaItem(tabela_item);
                    }
                });
                
                formataCampo();
                pecaAutocomplete();
			}); 

            function formataCampo(){
                $("#gridRelatorio input[type='text'], #peca_preco").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            }

            function atualizaTabelaItem(tabela_item){
                var valor_oculto    = $("#hd_preco_"+tabela_item).val();
                var valor           = $("#preco_"+tabela_item).val();
                var produto_id      = $("#produto_id_"+tabela_item).val();
                var xtabela         = $("input[name=tabela]").val();

                valor = valor.replace('.','');
                valor = valor.replace('R$','');
                valor = $.trim(valor.replace(',','.'));
                
                if(valor_oculto != valor){
                    $("#preco_"+tabela_item).attr("disabled", "disabled");
                    $("#preco_"+tabela_item).val('Atualizando!');

                    $.ajax({
                        type    : "POST",  
                        url     : "<?php echo $PHP_SELF;?>",  
                        data    : "action=gravarDados&tabela_item=" + tabela_item + "&valor=" + valor + "&tabela=" + xtabela,
                        success : function(retorno){
                            if(retorno == 'ok'){
                                $("#preco_"+tabela_item).css('border','1px solid #0E660B');
                                $("#preco_"+tabela_item).parent().parent().css('background','#D9F4D7');

                                $("#hd_preco_"+tabela_item).val(valor);
                                $("#preco_"+tabela_item).val(valor);
                            }
                            $("#preco_"+tabela_item).removeAttr("disabled");

                            formataCampo();
                        }  
                    });
                }
            }

            function pecaAutocomplete(){
                $('#peca').focus(function(){
                    if (!$(this).attr("readonly")) {
                        $('#peca').autocomplete('<?php echo $PHP_SELF;?>?action=autocompletePeca', {
                            minChars: 3,
                            delay: 300,
                            width: 350,
                            matchContains: true,
                            formatItem: function(row) {return row[1] + ' - ' + row[2];},
                            formatResult: function(row) {return row[1] + ' - ' + row[2];}
                        });

                        $('#peca').result(function(event, data, formatted) {
                            $('#peca_referencia').val(data[1]);
                            $('#peca_preco').focus();
                        });
                    }
                });
            }
		</script>
	</head>
	<body>
		<div class="lp_header">
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>
		<?php
            if (!empty($tabela)){

                $sql = "SELECT 
                            tbl_contrato_tabela_item.produto,
                            tbl_contrato_tabela_item.contrato_tabela_item,
                            tbl_produto.referencia,
                            tbl_produto.descricao,
                            tbl_contrato_tabela_item.preco
                        FROM tbl_contrato_tabela_item
                        JOIN tbl_produto ON tbl_produto.produto = tbl_contrato_tabela_item.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                       WHERE tbl_contrato_tabela_item.contrato_tabela = {$tabela}
                    ORDER BY tbl_produto.descricao DESC";
				$res = pg_query($con,$sql);
				if (pg_num_rows($res) > 0) {
                    $sqlTabela = "SELECT * FROM tbl_contrato_tabela WHERE contrato_tabela={$tabela} AND fabrica=".$login_fabrica;
                    $resTabela = pg_query($con, $sqlTabela);
                    extract(pg_fetch_array($resTabela));
                    echo "<div class='lp_pesquisando_por'>
                            Tabela: {$codigo} - {$descricao}<br />
                            <span>* Para atualizar um registro altere o valor que será atualizado automaticamente</span>
                        </div>
                        <input type='hidden' name='tabela' id='tabela' value='{$contrato_tabela}' />
                        ";
        ?>
                    
			<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
				<thead>
					<tr>
						<th>Produto</th>
						<th width="20%">Valor</th>
                        <th width="20%">Ação</th>
					</tr>
				</thead>
				<tbody>
                <?php 
					for ($i = 0 ; $i < pg_num_rows($res); $i++) {
                         extract(pg_fetch_array($res));
                         $preco = number_format($preco,'2','.','');
                        
                         $cor = $cor == '#F7F5F0' ? '#F1F4FA' : '#F7F5F0';
                         echo "<tr bgcolor='{$cor}'>";
                            echo "<td>{$referencia} - {$descricao}</td>";
                            echo "
                                <td  style='text-align: center'>
                                    <input type='text' name='preco_{$contrato_tabela_item}' id='preco_{$contrato_tabela_item}' size='12' value='{$preco}' class='frm'  alt='{$contrato_tabela_item}' />
                                    <input type='hidden' name='hd_preco_{$contrato_tabela_item}' id='hd_preco_{$contrato_tabela_item}' value='{$preco}' />
                                    <input type='hidden' name='produto_id_{$contrato_tabela_item}' id='produto_id_{$contrato_tabela_item}' value='{$produto}' />
                                </td>";
                            echo "<td style='text-align: center'>
                                    <input type='button' name='apagar_{$contrato_tabela_item}' id='apagar_{$contrato_tabela_item}' value='Apagar' alt='{$contrato_tabela_item}' />
                                  </td>";
                         echo "</tr>";
                    }
                ?>
                </tbody>
            </table>

        <?php
            }else {
                echo "<div class='lp_msg_erro'>Nehum produto encontrado!</div>";
            }

        } else {
            echo "<div class='lp_msg_erro'>Nehuma tabela encontrada!</div>";
        } 
        ?>
	</body>
</html>
