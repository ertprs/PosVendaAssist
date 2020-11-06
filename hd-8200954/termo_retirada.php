<?php 
    include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include 'autentica_usuario.php';

    header("Content-type: text/html; charset=iso-8859-1");

    if (isset($_GET['os']) && $_GET['os'] > 0) {
        $os = $_GET['os'];
        $conteudo = '';
        unset($array_pecas);
        $pecas = '';
        $data_hj = date ("d-m-Y");

        $sql_fabrica = "SELECT tbl_fabrica.nome, 
                               tbl_posto_fabrica.contato_endereco, 
                               tbl_posto_fabrica.contato_numero, 
                               tbl_posto_fabrica.contato_complemento, 
                               tbl_posto_fabrica.contato_bairro, 
                               tbl_posto_fabrica.contato_cidade, 
                               tbl_posto_fabrica.contato_cep, 
                               tbl_posto_fabrica.contato_estado, 
                               tbl_posto_fabrica.contato_email, 
                               tbl_posto_fabrica.contato_fone_comercial, 
                               tbl_posto.nome AS nome_posto
                        FROM tbl_os 
                        JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica 
                        JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
                        JOIN tbl_fabrica ON tbl_os.fabrica = tbl_fabrica.fabrica
                        WHERE tbl_os.fabrica = $login_fabrica 
                        AND tbl_os.os = $os";
        $res_fabrica = pg_query($con, $sql_fabrica);
        if (pg_num_rows($res_fabrica) > 0) {
            $fabrica_nome      = pg_fetch_result($res_fabrica, 0, 'nome');  
            $posto_endereco    = pg_fetch_result($res_fabrica, 0, 'contato_endereco');
            $posto_numero      = pg_fetch_result($res_fabrica, 0, 'contato_numero');
            $posto_complemento = pg_fetch_result($res_fabrica, 0, 'contato_complemento');
            $posto_bairro      = pg_fetch_result($res_fabrica, 0, 'contato_bairro');
            $posto_cidade      = pg_fetch_result($res_fabrica, 0, 'contato_cidade');
            $posto_cep         = pg_fetch_result($res_fabrica, 0, 'contato_cep');
            $posto_estado      = pg_fetch_result($res_fabrica, 0, 'contato_estado');
            $posto_email       = pg_fetch_result($res_fabrica, 0, 'contato_email');
            $posto_fone        = pg_fetch_result($res_fabrica, 0, 'contato_fone_comercial');
            $posto_nome        = pg_fetch_result($res_fabrica, 0, 'nome_posto');  
        }

        $end_1 = "<p>";
        $end_2 = "<p>";
        $end_3 = "<p>";

        if (!empty($posto_endereco)) {
            $end_1 .= "<b>Rua:</b> $posto_endereco";
        }

        if (!empty($posto_numero)) {
            $end_1 .= ", <b>Número:</b> $posto_numero";   
        }

        if (!empty($posto_bairro)) {
            $end_1 .= ", <b>Bairro:</b> $posto_bairro";    
        }

        if (!empty($posto_complemento)) {
            $end_1 .= ", <b>Complemento:</b> $posto_complemento";
        }

        if (!empty($posto_cep)) {
            $end_2 .="<b>Cep:</b> $posto_cep";
        }

        if (!empty($posto_cidade)) {
            $end_2 .= ", <b>Cidade:</b> $posto_cidade";
        }

        if (!empty($posto_estado)) {
            $end_2 .= ", <b>Estado:</b> $posto_estado";
        }

        if (!empty($posto_fone)) {
            $end_3 .= "<b>Fone:</b> $posto_fone";    
        }
        
        if (!empty($posto_email)) {
            $end_3 .= ", <b>Email:</b> $posto_email";
        }

        $end_1 .= "</p>";
        $end_2 .= "</p>";
        $end_3 .= "</p>";

        $sql_troca = "	SELECT DISTINCT tbl_os.consumidor_nome,
        								tbl_peca.referencia, 
					        			tbl_peca.descricao 
					    FROM tbl_os 
					    JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
					    JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
					    JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os
					    JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca 
					    JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
					    WHERE tbl_os.os = {$os} 
					    AND tbl_os.fabrica = {$login_fabrica}
					    AND tbl_servico_realizado.troca_produto IS TRUE";		
	$res_troca = pg_query($con, $sql_troca);
		if (pg_num_rows($res_troca) > 0) {
			$consumidor_nome = pg_fetch_result($res_troca, 0, 'consumidor_nome');
			$conteudo = "Declaro que na data de hoje: $data_hj, estive na Assistência Técnica mencionada acima, e referente a ferramenta deixada para conserto, estou recebendo a solução seguinte:
                    <br / ><br / >
        			Produto trocado por um Novo.
        			<br /><br /><br />";
		} else {			
	        $sql = "SELECT DISTINCT tbl_os.consumidor_nome,
	        						tbl_peca.referencia, 
				        			tbl_peca.descricao,
								tbl_servico_realizado.servico_realizado as servico_realizado 
				    FROM tbl_os 
				    JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
				    JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
				    JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca 
				    JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
				    WHERE tbl_os.os = {$os} 
				    AND tbl_os.fabrica = {$login_fabrica}";
	        $res = pg_query($con, $sql);
	        if (pg_num_rows($res) > 0) {
                unset($array_pecas);
	        	for ($p=0; $p < pg_num_rows($res); $p++) { 
                    $pecas = '';
		            $referencia = pg_fetch_result($res, $p, 'referencia');
		            $descricao = pg_fetch_result($res, $p, 'descricao');
		            $pecas = $referencia.' - '.$descricao; 
			        $servico_realizado = pg_fetch_result($res, $p, 'servico_realizado'); 
                    if (in_array($servico_realizado, [10739, 11209, 11212])) {
                        $array_pecas['troca_peca'][] = $pecas;
                    } else if (strtolower($servico_realizado) == 'ajuste') {
                        $array_pecas['ajuste'][] = $pecas;
                    } else {
                        $array_pecas[] = $pecas;
                    }
                }
                
	        	$consumidor_nome = pg_fetch_result($res, 0, 'consumidor_nome');
                
                if (array_key_exists("troca_peca",$array_pecas) && array_key_exists("ajuste",$array_pecas)) {
                    $pecas_troca  = implode(', ', $array_pecas['troca_peca']);
                    $pecas_ajuste = implode(', ', $array_pecas['ajuste']);
                    $frase_peca = "com a troca da(s) peça(s): $pecas_troca e ajuste da(s) peça(s): $pecas_ajuste.";
                } else if (array_key_exists("troca_peca",$array_pecas)) {
                    $pecas_troca  = implode(', ', $array_pecas['troca_peca']);
                    $frase_peca = "com a troca da(s) peça(s): $pecas_troca.";
                } else if (array_key_exists("ajuste",$array_pecas)) {
                    $pecas_ajuste = implode(', ', $array_pecas['ajuste']);
                    $frase_peca = "com o ajuste da(s) peça(s): $pecas_ajuste.";
                } else {
                    $pecas = implode(', ', $array_pecas);
                    $frase_peca = "com uso da(s) seguinte(s) peça(s): $pecas.";
                }
                
		    	$conteudo = " Declaro que na data de hoje: $data_hj, estive na Assistência Técnica mencionada acima, e referente a ferramenta deixada para conserto, estou recebendo a solução seguinte:
		                    <br / ><br / >
		                  Ferramenta Consertada, $frase_peca
		                  <br /><br /><br />";
	        }
		}			    
    }

    if (isset($_POST['imprimir_retirada']) && $_POST['imprimir_retirada'] != '') {
        if (isset($_POST['os'])) {
            $os = $_POST['os'];
            $data = date ('Y-m-d H:i:s');

            $sql_finalizada = "SELECT data_conserto, finalizada FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
            $res_finalizada = pg_query($con, $sql_finalizada);
            if (empty(pg_fetch_result($res_finalizada, 0, 'data_conserto')) || empty(pg_fetch_result($res_finalizada, 0, 'finalizada'))) {
                $sql_campos_add = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica";
                $res_campos_add = pg_query($con, $sql_campos_add);
                if (pg_num_rows($res_campos_add) > 0) {
                	$campos_adicionais = pg_fetch_result($res_campos_add, 0, 'campos_adicionais');
                	$campos_adicionais = json_decode($campos_adicionais, true);
                	$campos_adicionais['termo_retirada_produto'] = $data;
                	$campos_adicionais = json_encode($campos_adicionais);
                	
                	$sql_update = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$campos_adicionais' WHERE os = {$os} AND fabrica = {$login_fabrica}";
                	$res_update = pg_query($con, $sql_update);
                	if (pg_last_error($con) > 0) {
                    	echo 'erro';
    	            } else {
    	                echo 'ok';
    	            }
                } else {
                	echo 'erro_termo';
                }

            } else {
                echo 'finalizada';    
            }
        }
        exit;
    }  
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    

    <!-- Bootstrap -->
    <link rel="stylesheet" href="plugins/bootstrap3/css/bootstrap.min.css">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

        <style>
           body {
                font-family: arial, verdana;
                color: #444;
                background-color: #fff;
            }

            .text_padrao {
                font-size: 14px !important;
            }
           
            h1{
                font-size: 20px !important;
            }

            h3{
                font-weight: bold;
                font-size: 14px !important;
            }

            h4{
                font-weight: bold;
                font-size: 10px !important;
            }

            p{
                font-size: 14px !important;
            }

            @media print {
                .imprimir { display: none; }
            .col-sm-1, .col-sm-2, .col-sm-3, .col-sm-4, .col-sm-5, .col-sm-6, .col-sm-7, .col-sm-8, .col-sm-9, .col-sm-10, .col-sm-11, .col-sm-12 {
                    float: left;
               }
               .col-sm-12 {
                    width: 100%;
               }
               .col-sm-11 {
                    width: 91.66666667%;
               }
               .col-sm-10 {
                    width: 83.33333333%;
               }
               .col-sm-9 {
                    width: 75%;
               }
               .col-sm-8 {
                    width: 66.66666667%;
               }
               .col-sm-7 {
                    width: 58.33333333%;
               }
               .col-sm-6 {
                    width: 50%;
               }
               .col-sm-5 {
                    width: 41.66666667%;
               }
               .col-sm-4 {
                    width: 33.33333333%;
               }
               .col-sm-3 {
                    width: 25%;
               }
               .col-sm-2 {
                    width: 16.66666667%;
               }
               .col-sm-1 {
                    width: 8.33333333%;
               }

               .ajuste_left {
                    margin-left: -60px;
                }
            }

            .tac {
                text-align: center !important;
            }

	    hr {
		border-color: #000;
	    }

        </style>
        <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
        <script type="text/javascript">
            $(function() {
                $('#btn_imprimir_retirada').click(function(){
                	var os = '<?=$os?>';
                    window.print();
                    $.ajax({
                        async: true,
                        url:"<?=$PHP_SELF?>",
                        type:"POST",
                        data:{
                            imprimir_retirada: 'sim',
                            os               : os
                        },
                        complete: function(data){
                            var dados = data.responseText;

                            if(dados == 'ok'){
                                alert('Você será direcionado para tela de fechamento de OS');
                                $("#frm_fechar_os").submit();
                            } else if (dados == 'finalizada'){
                                alert('OS finalizada, você será direcionado para tela da OS');
                                window.location.href="os_press.php?os="+os;
                            } else {
                                alert('Erro na inserção dos dados, verificar se o termo de entrega foi gerado.');
                            }
                        }
                    });
                });
            });
        </script>
    </head>
    <body>
        <div class="row-fluid">
        <br />
        <div class='row '>
            <div class="tac col-sm-12 imprimir">
                <button class="tac btn btn-primary" type="submit" id="btn_imprimir_retirada" name="imprimir">Imprimir</button>
            </div>
        </div>
        <div class='container-fluid'>
            <div class='row '>
                <div class="col-sm-12"> 
                    <h1>
                        Termo de Retirada de Produto
                    </h1>
                    <h3>
                        OS: <?=$os?>
                    </h3>
                </div>
            </div>
            <br />
            <div class='row '>
                <div class="col-sm-12">    
                    <p>
                        <?=$conteudo?>
                    </p>
                </div>
            </div>
            <div class='row '>
                <div class="col-sm-12">    
                    <p>
                    	Produto testado no ato da entrega.
    					<br /><br />
    					Declaro que recebi uma cópia desse termo.
                    </p>
                </div>
            </div>
            <br /><br /><br />
            <div class='row '>
                <div class="col-sm-4"></div>
                <div class="col-sm-8">    
                    <hr />
                    <p>
                        <?=$consumidor_nome?>
                    </p>
                </div>
            </div>
            <br /><br/><br /><br/><br /><br /><br/>
            <div class='row '>
                <div class="col-sm-8">    
                    <hr />
                    <h3>
                        <p>
                            <?=$posto_nome?>
                        </p>
                    </h3>
                </div>
            </div>
            <div class='row '>
                <div class="col-sm-8">
                    <?=$end_1?>
                    <?=$end_2?>
                    <?=$end_3?>  
                </div>
            </div>
        </div>
    </body>
    
    <form name='frm_os_pesquisa' id="frm_fechar_os" action='os_fechamento.php' method='post' >
        <input type='hidden' name='sua_os' size='10' value='<? echo $os ?>'>
        <input type='hidden' name='btn_acao_pesquisa' value='continuar'>
    </form>
</html>
