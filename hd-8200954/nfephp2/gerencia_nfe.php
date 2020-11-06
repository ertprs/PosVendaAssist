<!DOCTYPE html>
<html>
    <head>
        <?php

        include '../dbconfig.php';
        include '../includes/dbconnect-inc.php';
        include '../distrib/autentica_usuario.php';
        if(!empty($cook_posto)) $login_posto = $cook_posto;

        ?>
        <title>Gerenciamento de NFe</title>
        
        <link href="../admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="../admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="../admin/css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="../admin/css/tooltips.css" type="text/css" rel="stylesheet" />
        <link href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
        <link href="../admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

        <!--[if lt IE 10]>
        <link href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
        <link rel='stylesheet' type='text/css' href="../admin/bootstrap/css/ajuste_ie.css">
        <![endif]-->
            
        <style>
            body {
                font: normal 12px arial;
            }
            acronym {
                cursor: help;
            }
            .circle-green {
                border-radius: 50%;
                width: 15px;
                height: 15px;
                background-color: #00802b;
                display: inline-block;
                vertical-align: middle;
            }
            .circle-red {
                border-radius: 50%;
                width: 15px;
                height: 15px;
                background-color: #cc0000;
                display: inline-block;
                vertical-align: middle;
            }
        </style>
        
        <script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
        <script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
        <script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
        <script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
        <script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
        <script src='../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js'></script> 
        <script src='../plugins/jquery.maskedinput_new.js'></script>        

        <script src="js/gerencia_nfe.js"></script>
    </head>
    <body>
        <form name="frm_nfe" action="" method="post">
            <input type="hidden" name="operacao" id="operacao" value="enviar" />
            <!--<input type="hidden" name="faturamento" id="faturamento" />-->
            <input type="hidden" name="fat_consulta_unica" id="fat_consulta_unica" value="fat_consulta_unica" />
            <input type="hidden" name="motivo" id="motivo" />
            <?php

            $q = '';

            if (!empty($_GET["q"])) {
                $q = $_GET["q"];
            }

            $data_emissao_pesquisa = " AND emissao = '" . date('Y-m-d') . "' ";
            $nf_pesquisa = '';
            $emissao = date('d/m/Y');
            $nota_fiscal = '';

            if ($q == "todos") {
                $data_emissao_pesquisa = '';
                $emissao = '';
            }

            if (!empty($_POST["emissao"])) {
                $emissao = $_POST["emissao"];
                $data_emissao = DateTime::createFromFormat('d/m/Y', $emissao);

                if (!empty($data_emissao)) {
                    $data_emissao_pesquisa = " AND emissao = '" . $data_emissao->format('Y-m-d') . "' ";
                }
            }

            if (!empty($_POST["nota_fiscal"])) {
                $nota_fiscal = $_POST["nota_fiscal"];
                $nf_pesquisa = " AND nota_fiscal = '{$nota_fiscal}' ";
            }

            $sqlx = "SELECT faturamento,
			    tbl_posto.nome as nome, tbl_posto.cnpj,
                       	    embarque,
                            tbl_faturamento_destinatario.nome as nome_consumidor,
                            tbl_faturamento_destinatario.cpf_cnpj,
                            TO_CHAR(emissao, 'DD/MM/YYYY') as emissao,
                            nota_fiscal,
			    tbl_faturamento.cfop,
			    tbl_faturamento.total_nota,
			CASE WHEN tbl_faturamento.cfop like '%949' then 'Garantia' else 'Venda' end as tipo_nota,
			CASE WHEN tbl_faturamento.posto is not null then tbl_posto.estado else tbl_faturamento_destinatario.uf end as estado
                      FROM tbl_faturamento
		       LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_faturamento.posto
                       LEFT JOIN tbl_embarque USING(embarque)
                       LEFT JOIN tbl_faturamento_destinatario USING(faturamento)
                      WHERE tbl_faturamento.fabrica      = 10
                        AND tbl_faturamento.distribuidor in($login_posto,20682)
                        AND emissao >= '2013-01-30' /* Data de implantação desta parte... */
                        $data_emissao_pesquisa
                        $nf_pesquisa
                        AND chave_nfe    IS NULL
                      ORDER BY tbl_faturamento.faturamento DESC";
            $resx = pg_query($con, $sqlx);
            $totx = pg_num_rows($resx);
//	    echo $sqlx;
                ?>
                <br />
                <br />
                <table class='table table-striped table-bordered table-hover table-large' align='center'>
		    <thead>
                    <tr class='titulo_tabela'>
                        <th colspan="100%">INTEGRAÇÃO => SIGE CLOUD ERP</th>
                    </tr>
                    <tr style="background-color: #d9e2ef;">
                        <td colspan="13">
                            <form method="post">
                                <div class="form-group">
                                    <strong>Data do Faturamento:</strong>
                                    <input type="text" name="emissao" id="emissao" class="frm" value="<?php echo $emissao ?>" />

                                    <strong>Nota Fiscal:</strong>
                                    <input type="text" style="width: 140px;" name="nota_fiscal" id="nota_fiscal" value="<?php echo $nota_fiscal ?>" />

                                    <button name="btn_emissao" class="btn">Pesquisar</button>

                                    <button type="button" id="listar_todos" name="listar_todos" class="btn">Listar Todos</button>
                                    <button type="button" id="btn_voltar" name="btn_voltar" class="btn">Volta Menu Distrib</button>
                                </div>
                            </form>
                        </td>
                    </tr>
         <?php if ($totx) {?>
                    <tr class='titulo_coluna'>
                        <th>#</th>
                        <th>Faturamento</th>
                        <th>Embarque</th>
                        <th>Nome</th>
                        <th>Cadastro SIGE</th>
                        <th>Nota Fiscal</th>
                        <th>Valor</th>
                        <th>CFOP</th>
                        <th>Estado</th>
                        <th>Tipo</th>
                        <th>OS</th>
                        <th>Emissao</th>
                        <th>Ações</th>
			
                    </tr>
	            </thead>


		<?php

            $auth_token = "5bc00e47b1523ccfd4a05c81006d41244a77c67e078c7e3a3dc739185039e7cdf2c856cb955cff8d890a094a70f849b548d4e1bb4403fb9c4812b1c0e2646f076517c22759306d00997ad40a841544a166f3bac548a9b3987987246c274d98030f896535d6a1f89899e965fa429f0624ac95000e99af04823c1438986184feb9";

            $headers = array(
                "Authorization-Token: $auth_token",
                "User: valeria@acaciaeletro.com.br",
                "App: AcaciaEletro",
                "Content-Type: application/json; charset=utf-8"
            );

            $uri = "http://api.sigecloud.com.br/request/pessoas/pesquisar";

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
                    for ($x = 0; $x < $totx; $x++) {

                        $cor         = $x % 2 ? '#EEEEEE' : '#FFFFFF';

                        $faturamento = pg_result($resx, $x, 'faturamento');
                        $nome        = pg_result($resx, $x, 'nome');
                        $cnpj = pg_fetch_result($resx, $x, 'cnpj');
                        $dt_emissao  = pg_result($resx, $x, 'emissao');
                        $nota_fiscal = pg_result($resx, $x, 'nota_fiscal');
                        $total_nota  = pg_result($resx, $x, 'total_nota');
                        $cfop        = pg_result($resx, $x, 'cfop');
                        $estado      = pg_result($resx, $x, 'estado');
                        $tipo_nota   = pg_result($resx, $x, 'tipo_nota');
                        $embarque    = pg_result($resx, $x, 'embarque');
                        $nome_consumidor  = pg_result($resx, $x, 'nome_consumidor');
                        $cpf_cnpj = pg_fetch_result($resx, $x, 'cpf_cnpj');

                        $cpfcnpj = $cnpj;

                        if (empty($cnpj)) {
                            $cpfcnpj = $cpf_cnpj;
                        }

                        $query_str = '?nomefantasia=';
                        $query_str .= '&cpfcnpj=' . $cpfcnpj;
                        $query_str .= '&cidade=&uf=&cliente=false&fornecedor=false&pageSize=10&skip=0';

                        curl_setopt($ch, CURLOPT_URL, $uri . $query_str);
                        $response_json = curl_exec($ch);

                        $response = json_decode($response_json, true);

                        $cadastro_sige = 'circle-red';

                        if (!empty($response)) {
                            $cadastro_sige = 'circle-green';
                        }
                        
			if (empty($embarque)) {
				$embarque = $nome_consumidor;
			}
                        $sqlx2 = "SELECT os FROM tbl_faturamento_item WHERE faturamento = $faturamento limit 1";
                        $resx2 = pg_query($con, $sqlx2);
			
//			var_dump(is_int($embarque));
			
			if (preg_match('/[a-zA-Z]/',$embarque)) {
				$tipo = "C";
			} else {
				$tipo = "P";
			}
		
//			echo $tipo;

                        if (pg_num_rows($resx2) > 0) {
                            $os = trim(pg_result($resx2, 0, 'os'));
                        } else {
                            $os = '';
                        }?>

                        <tr bgcolor="<?=$cor?>">
                            <td class="tac" id="<?=$faturamento?>" title="<?=$faturamento?>">
				<input type="checkbox" class="checkEmbarque" name="faturamento[]" value="<?=$faturamento?>" />
				<input type="hidden" name="tipo" value="<?=$tipo?>">
			    </td>
                            <td class="tac" title="<?=$faturamento?>"><?=$faturamento?>&nbsp;</td>
                            <td class="tal" nowrap title="<?=$faturamento?>"><?=$embarque?>&nbsp;</td>
                            <td class="tal" nowrap title="<?=$faturamento?>"><?=$nome . $nome_consumidor?>&nbsp;</td>
                            <td class="tac">
                                <div class="<?php echo $cadastro_sige ?>"></div>
                            </td>
                            <td class="tac" title="<?=$faturamento?>"><?=$nota_fiscal?>&nbsp;</td>
                            <td class="tac" nowrap title="<?=$faturamento?>">R$ <?=$total_nota?>&nbsp;</td>
                            <td class="tac" title="<?=$faturamento?>"><?=$cfop?>&nbsp;</td>
                            <td class="tac" title="<?=$faturamento?>"><?=$estado?>&nbsp;</td>
                            <td class="tac" title="<?=$faturamento?>"><?=$tipo_nota?>&nbsp;</td>
                            <td class="tac" ><?=$os?>&nbsp;</td>
                            <td class="tac" title="<?=$faturamento?>"><?=$dt_emissao?>&nbsp;</td>
                            <td class="tac"><img src='export_database_icon.jpg' width='24' height='24' class="exporta"></td>
                        </tr><?php

                    }?>

                    <tr>
                        <td colspan="100%" align="center">
                            <input type="button" id="btn-envia-embarque" class="btn btn-success" value="Enviar" />
                            <input type="button" id="btn-retorno" class="btn btn-warning" value="Verificar Retorno" />
                        </td>
                    </tr>
                </table><?php

            } else {

                echo '<tr><td>';

                echo '<h3 style="margin:2em auto;text-align:center">Não há faturamentos pendentes para emissão de NF-e.</h3>';

                echo '</td></tr>';
                echo '</table>';

            }
?>
        </form>
    </body>
</html>
