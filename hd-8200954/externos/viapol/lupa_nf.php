<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
$login_fabrica = 189;


if ($_POST['ajax_retorna'] == true) {

	$nf = $_POST['nf'];
	$pedido = $_POST['pedido'];


    $sql = "SELECT  DISTINCT ON (tbl_pedido.pedido) 
                                tbl_posto.nome as nome,
                                tbl_posto_fabrica.codigo_posto as codigo_posto, 
                                tbl_posto_fabrica.contato_endereco as endereco, 
                                tbl_posto_fabrica.contato_numero as numero,
                                tbl_posto_fabrica.contato_complemento as complemento,
                                tbl_posto_fabrica.contato_bairro as bairro,
                                tbl_posto_fabrica.contato_cep as cep,
                                tbl_posto_fabrica.contato_fone_residencial as fone,
                                tbl_posto_fabrica.contato_fone_comercial as fone2,
                                tbl_posto_fabrica.contato_cel as fone3,
                                tbl_posto.cnpj as cpf_cnpj, 
                                CASE WHEN tbl_posto.email is null
                                THEN 
                                tbl_posto_fabrica.contato_email
                                ELSE 
                                tbl_posto.email
                                END AS email,
                                tbl_posto_fabrica.contato_cidade as nome_cidade, 
                                tbl_posto_fabrica.contato_estado as estado,
                                tbl_pedido.pedido_cliente,
                                tbl_peca.peca AS produto_id,
                                tbl_peca.descricao AS produto_descricao,
                                tbl_peca.referencia AS produto_referencia,
                                tbl_posto.posto as posto,
                                TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data_atendimento, 
                                tbl_pedido.pedido ,
                                tbl_faturamento_item.qtde
    FROM tbl_pedido 
    JOIN tbl_posto_fabrica      ON tbl_pedido.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = {$login_fabrica} 
    JOIN tbl_posto              ON tbl_posto_fabrica.posto = tbl_posto.posto  
    JOIN tbl_faturamento_item   ON tbl_faturamento_item.pedido = tbl_pedido.pedido 
    JOIN tbl_faturamento        ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica={$login_fabrica} 
    JOIN tbl_peca               ON tbl_faturamento_item.peca=tbl_peca.peca AND tbl_peca.fabrica= {$login_fabrica}
   WHERE tbl_faturamento.nota_fiscal = '$nf'                    
     AND tbl_pedido.fabrica = {$login_fabrica} 
     AND tbl_pedido.pedido = {$pedido} 
    ";
    $res = pg_query($con, $sql);
    if (pg_last_error()) {
	exit(json_encode(["erro" => true, "msg" => "Erro ao buscar nota fiscal"]));
    }
 
    $dados_faturamentos = pg_fetch_assoc($res);

            if (strlen(trim($dados_faturamentos["nome"])) > 0) {
                $nome = str_replace("'","\'",$dados_faturamentos["nome"]);
            }
            if (strlen(trim($dados_faturamentos["email"])) > 0) {
                $email = $dados_faturamentos["email"];
            }
            if (strlen(trim($dados_faturamentos["fone"])) > 0) {
                $fone = $dados_faturamentos["fone"];
            }
            if (strlen(trim($dados_faturamentos["fone2"])) > 0) {
                $fone2 = $dados_faturamentos["fone2"];
            }
            if (strlen(trim($dados_faturamentos["pedido_cliente"])) > 0) {
                $pedido_cliente = $dados_faturamentos["pedido_cliente"];
            }

            $codigo_posto = $dados_faturamentos["codigo_posto"];
            $endereco = str_replace("'","\'",$dados_faturamentos["endereco"]);
            $numero = $dados_faturamentos["numero"];
            $complemento = $dados_faturamentos["complemento"];
            $bairro = str_replace("'","\'",$dados_faturamentos["bairro"]);
            $cep = $dados_faturamentos["cep"];
            $cpf_cnpj = $dados_faturamentos["cpf_cnpj"];
            $nome_cidade = str_replace("'","\'",$dados_faturamentos["nome_cidade"]);
            $estado = $dados_faturamentos["estado"];

            if (strlen(trim($dados_faturamentos["pedido"])) > 0) {
                $pedido = $dados_faturamentos["pedido"];

                $sqlPecas = "SELECT tbl_faturamento.emissao,tbl_peca.referencia,tbl_faturamento_item.qtde,tbl_pedido_item.preco
                               FROM tbl_faturamento  
                               JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                               JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_faturamento_item.pedido_item
                               JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca AND tbl_peca.fabrica=$login_fabrica
                              WHERE tbl_faturamento.fabrica={$login_fabrica}
                                AND tbl_faturamento_item.pedido=$pedido";

                $resPecas = pg_query($con, $sqlPecas);
                $xprodutos = [];
                if (pg_num_rows($resPecas) > 0) {
                    $contador = 1;
                    $total_produto = [];
                    foreach (pg_fetch_all($resPecas) as $key => $rows) {
                        
                        $sqlProduto = "SELECT * 
                               FROM tbl_produto  
                              WHERE tbl_produto.fabrica_i={$login_fabrica}
                                AND tbl_produto.referencia='".$rows['referencia']."'";
                        $resProduto = pg_query($con, $sqlProduto);
                        if (pg_num_rows($resProduto) > 0) {
                            $total_produto[] = 1;
				                    
			    $retorna_dados['it']['contador'][] = $contador;
                            $xproduto     = pg_fetch_result($resProduto, 0, 'produto');
                            $xreferencia  = pg_fetch_result($resProduto, 0, 'referencia');
                            $xdescricao   = utf8_encode(pg_fetch_result($resProduto, 0, 'descricao'));
                            $xqtde        = $rows['qtde'];
                            $xpreco        = $rows['preco'];
                            $xemissao        = $rows['emissao'];

			    $retorna_dados['it']['produto_referencia'][] = $xreferencia;
			    $retorna_dados['it']['produto_descricao'][] = $xdescricao;
			    $retorna_dados['it']['produto'][] = $xproduto;
			    $retorna_dados['it']['qtde'][] = $xqtde;
			    $retorna_dados['it']['preco'][] = $xpreco;
			    $retorna_dados['it']['emissao'][] = $xemissao;


                            $contador++;
                        }

                    }
		    $retorna_dados['total_produtos'] = array_sum($total_produto);
                    
                }
            }

            $retorna_dados['cb'] = [
				'nome' =>	$nome,
				'email' =>	$email,
				'fone' =>	$fone,
				'fone2' =>	$fone2,
				'pedido' =>	$pedido,
				'endereco' =>	$endereco,
				'numero' =>	$numero,
				'complemento' =>	$complemento,
				'bairro' =>	$bairro,
				'cep' =>	$cep,
				'cpf_cnpj' =>	$cpf_cnpj,
				'nome_cidade' =>	$nome_cidade,
				'estado' =>	$estado, 
				'codigo_posto' =>	$codigo_posto
			];

	exit(json_encode($retorna_dados));

}


if ($_GET["nf"]) {
    $nf = $_GET["nf"];

    $sql = "SELECT  DISTINCT ON (tbl_pedido.pedido) 
                                tbl_posto.nome as nome,
                                tbl_posto_fabrica.codigo_posto as codigo_posto, 
                                tbl_posto_fabrica.contato_endereco as endereco, 
                                tbl_posto_fabrica.contato_numero as numero,
                                tbl_posto_fabrica.contato_complemento as complemento,
                                tbl_posto_fabrica.contato_bairro as bairro,
                                tbl_posto_fabrica.contato_cep as cep,
                                tbl_posto_fabrica.contato_fone_residencial as fone,
                                tbl_posto_fabrica.contato_fone_comercial as fone2,
                                tbl_posto_fabrica.contato_cel as fone3,
                                tbl_posto.cnpj as cpf_cnpj, 
                                CASE WHEN tbl_posto.email is null
                                THEN 
                                tbl_posto_fabrica.contato_email
                                ELSE 
                                tbl_posto.email
                                END AS email,
                                tbl_posto_fabrica.contato_cidade as nome_cidade, 
                                tbl_posto_fabrica.contato_estado as estado,
                                tbl_pedido.pedido_cliente,
                                tbl_peca.peca AS produto_id,
                                tbl_peca.descricao AS produto_descricao,
                                tbl_peca.referencia AS produto_referencia,
                                tbl_posto.posto as posto,
                                TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data_atendimento, 
                                tbl_pedido.pedido ,
                                tbl_faturamento_item.qtde
    FROM tbl_pedido 
    JOIN tbl_posto_fabrica      ON tbl_pedido.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = {$login_fabrica} 
    JOIN tbl_posto              ON tbl_posto_fabrica.posto = tbl_posto.posto  
    JOIN tbl_faturamento_item   ON tbl_faturamento_item.pedido = tbl_pedido.pedido 
    JOIN tbl_faturamento        ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica={$login_fabrica} 
    JOIN tbl_peca               ON tbl_faturamento_item.peca=tbl_peca.peca AND tbl_peca.fabrica= {$login_fabrica}
   WHERE tbl_faturamento.nota_fiscal = '$nf'                    
     AND tbl_pedido.fabrica = {$login_fabrica} 
    ";
    $res = pg_query($con, $sql);


    $msg_erro = "";
    if (pg_last_error($con)) {
        $msg_erro = "Erro ao realizar a consulta";
    }

    $conteudo = "";
    if (strlen($msg_erro) == 0) {
        if (pg_num_rows($res) == 0) {
            //$msg_erro = "<h2>Nota Fiscal <b style='color:#81AD19'>{$nf}</b> não encontrada.</h2>
            //<h2>Por favor entrar em contato com o nosso SAC através dos telefones <b style='color:#81AD19'>(12) 3221-3086</b> ou <b style='color:#81AD19'>(12) 3221-3113</b></h2>";

		$msg_erro = "<h2>Nota Fiscal <b style='color:#81AD19'>{$nf}</b> não encontrada.</h2>
            <h2>Por favor entrar em contato com o nosso SAC através do telefone <b style='color:#81AD19'>0800 494 0777</b></h2>";


        } elseif (pg_num_rows($res) > 1) {
            $conteudo = "<div class='alert alert-info'><h4>Selecione abaixo a nota fiscal desejada</h4></div><br>
			<table class='table table-bordered'>
				<thead>
					<tr>
						<th class='titulo_coluna'>Nota Fiscal</th>
						<th class='titulo_coluna tal'>Titular da Nota Fiscal</th>
						<th class='titulo_coluna'></th>
					</tr>
				</thead>
				<tbody>";
				foreach(pg_fetch_all($res) as $k => $rows){
   	                               $conteudo .="	
					<tr>
						<td class='tac'>".$nf."</td>
						<td class='tal'>".str_replace("'","\'",$rows["nome"])."</td>
						<td class='tac'><button type='button' onclick='retornaNota(\"".$rows["pedido"]."\",\"".$nf."\")' class='btn btn-success btn-xs'>Selecionar</button></td>
					</tr>";
	    
				}
		$conteudo .="				
				</tbody>


			</table>";
        } else {



            $dados_faturamentos = pg_fetch_assoc($res);

    echo "<script>";

            if (strlen(trim($dados_faturamentos["nome"])) > 0) {
                $nome = str_replace("'","\'",$dados_faturamentos["nome"]);
            }
            if (strlen(trim($dados_faturamentos["email"])) > 0) {
                $email = $dados_faturamentos["email"];
            }
            if (strlen(trim($dados_faturamentos["fone"])) > 0) {
                $fone = $dados_faturamentos["fone"];
            }
            if (strlen(trim($dados_faturamentos["fone2"])) > 0) {
                $fone2 = $dados_faturamentos["fone2"];
            }
            if (strlen(trim($dados_faturamentos["pedido_cliente"])) > 0) {
                $pedido_cliente = $dados_faturamentos["pedido_cliente"];
            }

            $codigo_posto = $dados_faturamentos["codigo_posto"];
            $endereco = str_replace("'","\'",$dados_faturamentos["endereco"]);
            $numero = $dados_faturamentos["numero"];
            $complemento = $dados_faturamentos["complemento"];
            $bairro = str_replace("'","\'",$dados_faturamentos["bairro"]);
            $cep = $dados_faturamentos["cep"];
            $cpf_cnpj = $dados_faturamentos["cpf_cnpj"];
            $nome_cidade = str_replace("'","\'",$dados_faturamentos["nome_cidade"]);
            $estado = $dados_faturamentos["estado"];

            if (strlen(trim($dados_faturamentos["pedido"])) > 0) {
                $pedido = $dados_faturamentos["pedido"];

                $sqlPecas = "SELECT tbl_faturamento.emissao,tbl_peca.referencia,tbl_faturamento_item.qtde,tbl_pedido_item.preco
                               FROM tbl_faturamento  
                               JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                               JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_faturamento_item.pedido_item
                               JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca AND tbl_peca.fabrica=$login_fabrica
                              WHERE tbl_faturamento.fabrica={$login_fabrica}
                                AND tbl_faturamento_item.pedido=$pedido";

                $resPecas = pg_query($con, $sqlPecas);
                $xprodutos = [];
                if (pg_num_rows($resPecas) > 0) {
                    $contador = 1;
                    $total_produto = [];
                    echo  'window.parent.limpa_produtos();';
                    foreach (pg_fetch_all($resPecas) as $key => $rows) {
                        
                        $sqlProduto = "SELECT * 
                               FROM tbl_produto  
                              WHERE tbl_produto.fabrica_i={$login_fabrica}
                                AND tbl_produto.referencia='".$rows['referencia']."'";
                        $resProduto = pg_query($con, $sqlProduto);
                        if (pg_num_rows($resProduto) > 0) {
                            $total_produto[] = 1;
                            echo 'window.parent.add_linha('.$contador.');';
                            $xproduto     = pg_fetch_result($resProduto, 0, 'produto');
                            $xreferencia  = pg_fetch_result($resProduto, 0, 'referencia');
                            $xdescricao   = utf8_encode(pg_fetch_result($resProduto, 0, 'descricao'));
                            $xqtde        = $rows['qtde'];
                            $xpreco        = $rows['preco'];
                            $xemissao        = $rows['emissao'];
                            echo 'window.parent.$(".produto_referencia_'.$contador.'").val("'.$xreferencia.'");';
                            echo 'window.parent.$(".produto_descricao_'.$contador.'").val("'.$xdescricao.'");';
                            echo 'window.parent.$(".produto_'.$contador.'").val("'.$xproduto.'");';
                            echo 'window.parent.$(".qtde_produto_'.$contador.'").val("'.$xqtde.'");';
                            echo 'window.parent.$(".preco_produto_'.$contador.'").val("'.$xpreco.'");';
                            echo 'window.parent.$(".emissao_'.$contador.'").val("'.$xemissao.'");';
                            $contador++;
                        }

                    }
                    echo 'window.parent.contactForm.qtde_total_produtos.value="'.array_sum($total_produto).'";';
                    
                }
            }

            echo "window.parent.retorna_dados('$nome','$email','$fone','$fone2','$pedido','$endereco','$numero','$complemento','$bairro','$cep','$cpf_cnpj','$nome_cidade','$estado', '$codigo_posto');";
            echo 'window.parent.Shadowbox.close();';
        echo "</script>";

        }
    }

?>

<!doctype html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap3/css/bootstrap.min.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="../../bootstrap/css/extra.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="../../bootstrap/css/ajuste.css" />
        <script src="../../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
       
        <script language="javascript">
			
            $(function() {
            
            	
            });

		function retornaNota(pedido, nf) {

			if (pedido == '' || nf == '') {

				alert('NF invalida')
				return false;
			}

			$.ajax({
			    url: 'lupa_nf.php',
			    type: 'POST',
			    dataType:"JSON",
			    data: {
				ajax_retorna: true,
				nf: nf,
				pedido:pedido
			    },
			    timeout: 8000
			}).fail(function(erro){
			    console.log(erro)
			    alert('Ocorreu um erro ao tentar selecione a NF '+nf);
			}).done(function(data){
				if (data.erro) {
					alert(data.msg);
					return false;
				}

				window.parent.limpa_produtos();


				for (var i = 0; i < data.it.contador.length; i++) {

					window.parent.add_linha(data.it.contador[i]);
                            		window.parent.$(".produto_referencia_"+data.it.contador[i]).val(data.it.produto_referencia[i]);
	                           	window.parent.$(".produto_descricao_"+data.it.contador[i]).val(data.it.produto_descricao[i]);
                           	 	window.parent.$(".produto_"+data.it.contador[i]).val(data.it.produto[i]);
                            		window.parent.$(".qtde_produto_"+data.it.contador[i]).val(data.it.qtde[i]);
                            		window.parent.$(".preco_produto_"+data.it.contador[i]).val(data.it.preco[i]);
                            		window.parent.$(".emissao_"+data.it.contador[i]).val(data.it.emissao[i]);

				}

				window.parent.retorna_dados(data.cb.nome,data.cb.email,data.cb.fone,data.cb.fone2,data.cb.pedido,data.cb.endereco,data.cb.numero,data.cb.complemento,data.cb.bairro,data.cb.cep,data.cb.cpf_cnpj,data.cb.nome_cidade,data.cb.estado, data.cb.codigo_posto);


				window.parent.Shadowbox.close();



	

				console.log(data)
			});
		}
        </script>
        <style>
            body{
                font-family: "Arial";
            }

	    .tac {
		text-align: center !important;
	    }

	    .tal {
		text-align: left !important;
	    }

        </style>
    </head>
    <body>
        <div class='titulo_coluna'>
            <h2 style='color:#fff;font-size:18px;margin-top: 10px;padding-bottom: 10px;'>Pesquisando Nota Fiscal Nº <?php echo $nf;?></h2>
        </div>
        <div style="width: 80%;margin: 0 auto">
            <?php if (strlen($msg_erro) > 0) {?>
                <div class='alert alert-warning' style="margin-top: 50px;height: 400px;background: #fff;z-index: 11111;"><?php echo $msg_erro;?></div>
	    <?php }?>
     	    <?php if (strlen($conteudo) > 0) {?>
                <?php echo $conteudo;?>
	    <?php }?>

	</div>
    </body>
</html>


<?php } else {
    exit("<div class='alert alert-danger'> Nenhum dados cadastrados</div>");
}
?>

