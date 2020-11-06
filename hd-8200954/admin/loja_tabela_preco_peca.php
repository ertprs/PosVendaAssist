<?php 
    include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include 'autentica_admin.php';

    $tabela     = $_REQUEST["tabela"];
    $referencia = $_REQUEST["referencia"];
    $descricao  = $_REQUEST["descricao"];
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

                valor = valor.replace('.','');
                valor = valor.replace('R$','');
                valor = $.trim(valor.replace(',','.'));
                
                if(valor_oculto != valor){
                    $("#preco_"+tabela_item).attr("disabled", "disabled");
                    $("#preco_"+tabela_item).val('Atualizando!');

                    $.ajax({
                        type    : "POST",  
                        url     : "<?php echo $PHP_SELF;?>",  
                        data    : "ajax=ok&tabela_item=" + tabela_item + "&valor=" + valor,
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
        <div class='lp_nova_pesquisa'>
            <form action='<?php echo $PHP_SELF;?>' method='POST' name='nova_pesquisa'>
                <input type='hidden' name='tabela' value='<?php echo $tabela;?>' />
                <table cellspacing='1' cellpadding='2' border='0'>
                    <tr>
                        <td>
                            <label>Peça Referência</label>
                            <input type='text' name='referencia' value='<?php echo $referencia;?>' style='width: 150px' maxlength='20' />
                        </td>
                        <td>
                            <label>Peça Descrição</label>
                            <input type='text' name='descricao' value='<?php echo $descricao;?>' style='width: 370px' maxlength='80' />
                        </td>
                        <td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar' /></td>
                    </tr>
                </table>
            </form>
        </div>
        <?php
            if (!empty($tabela)) {
                $cond = "";
                if (!empty($referencia)) {
                    $cond = " AND tbl_peca.referencia = '$referencia'";
                } elseif (!empty($descricao)) {
                    $cond = " AND tbl_peca.descricao ILIKE '%$descricao%'";
                }
                $sql = "SELECT 
                            tbl_peca.referencia,
                            tbl_peca.descricao,
                            tbl_loja_b2b_tabela_item.preco
                          FROM tbl_loja_b2b_tabela 
                          JOIN tbl_loja_b2b_tabela_item USING(loja_b2b_tabela)
                          JOIN tbl_loja_b2b_peca USING(loja_b2b_peca)
                          JOIN tbl_peca USING(peca)
                         WHERE tbl_loja_b2b_tabela.loja_b2b_tabela = $tabela
                         {$cond}
                      ORDER BY tbl_peca.descricao DESC;";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {
        ?>
                    
                <table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
                    <thead>
                        <tr>
                            <th width="80px">Referência</th>
                            <th width="*" style='text-align: left'>Peça</th>
                            <th width="80px">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                        while ($rows = pg_fetch_array($res)) {
                             $preco      = number_format($rows["preco"],'2','.','');
                             $referencia = $rows["referencia"];
                             $descricao  = $rows["descricao"];
                            
                             $cor = $cor == '#F7F5F0' ? '#F1F4FA' : '#F7F5F0';
                             echo "<tr bgcolor='{$cor}'>
                                    <td style='text-align: center'>{$referencia}</td>
                                    <td>{$descricao}</td>
                                    <td  style='text-align: center'>{$preco}</td>
                                  </tr>";
                        }
                    ?>
                    </tbody>

        <?php
                } else {
                    echo "<div class='lp_msg_erro'>Nehuma peça encontrada!</div>";
                }
            } else {
                echo "<div class='lp_msg_erro'>Nehuma tabela encontrada!</div>";
            } 
        ?>
    </body>
</html>
