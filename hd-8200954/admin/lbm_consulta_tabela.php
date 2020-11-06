<?php 
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include_once 'funcoes.php';

if(isset($_POST['lista_pecas_produto'])){

	$id_produto = $_POST['id_produto'];
	$html = "";

	if(!empty($id_produto)){

		$sql = "SELECT  tbl_lista_basica.lista_basica,
                tbl_lista_basica.posicao,
                tbl_lista_basica.ordem,
                tbl_lista_basica.qtde,
                tbl_peca.referencia_fabrica,
                tbl_peca.referencia,
                tbl_peca.descricao,
                tbl_peca.peca,
                tbl_peca.garantia_diferenciada AS desgaste,
                tbl_peca.informacoes,
                tbl_lista_basica.serie_inicial,
                tbl_lista_basica.serie_final,
                tbl_lista_basica.type
            FROM tbl_lista_basica JOIN tbl_peca USING (peca)
 		  	WHERE tbl_lista_basica.fabrica = $login_fabrica AND 
    	  	      tbl_lista_basica.produto = $id_produto";

       	$res = pg_query ($con,$sql);

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
            $aux = pg_fetch_result($res,$i,para);
            if(!empty($aux)){
                $para_comp[] = $aux;
            }
		}
		$retira_de = array_unique($para_comp);
		if(pg_num_rows($res) > 0) {

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

				$ordem          = pg_fetch_result ($res,$i,posicao);
				$serie_inicial  = pg_fetch_result ($res,$i,serie_inicial);
				$serie_final    = pg_fetch_result ($res,$i,serie_final);
				$peca           = pg_fetch_result ($res,$i,referencia);
				$referencia_fabrica = pg_fetch_result ($res,$i,referencia_fabrica);
				$peca_id        = pg_fetch_result ($res,$i,peca);
				$descricao      = pg_fetch_result ($res,$i,descricao);
				$informacao_peca = pg_fetch_result ($res,$i,informacoes);
				$qtde           = pg_fetch_result ($res,$i,qtde);
				$type           = pg_fetch_result($res, $i, type);
				$desgaste       = pg_fetch_result ($res,$i,desgaste);

			 	if(in_array($peca,$retira_de)){
                	continue;
            	}

                $sqlA = "SELECT  tbl_peca_alternativa.de,
	                            tbl_peca.descricao
	                    FROM    tbl_peca_alternativa
	                    JOIN    tbl_peca ON tbl_peca.peca = tbl_peca_alternativa.peca_de
	                    WHERE   tbl_peca_alternativa.de    = '$peca'
	                    AND     tbl_peca_alternativa.fabrica = $login_fabrica;";
                $resA = pg_query ($con,$sqlA);

                if (pg_num_rows($resA) > 0) {
                    $cor = "#91C8FF";
                    $peca = pg_fetch_result($resA,0,de);
                    $descricao = pg_fetch_result($resA,0,descricao);
                } else {
                	$cor = "";
                }

                 $sqlD = "SELECT tbl_depara.de,
                                tbl_peca.descricao,
                                tbl_peca.referencia
                        FROM    tbl_depara
                        JOIN    tbl_peca on tbl_peca.referencia = tbl_depara.de and tbl_peca.fabrica = $login_fabrica
                        WHERE   tbl_depara.de    = '$peca'
                        AND     tbl_depara.fabrica = $login_fabrica;";
                $resD = pg_query ($con,$sqlD);

                if (pg_num_rows($resD) > 0) {
                    $cor = "#E8C023";
                    $peca = pg_fetch_result($resD,0,de);
                    $descricao = pg_fetch_result($resD,0,descricao);
                } else {
                	$cor = "";
                }

                $html .= "<tr bgcolor='$cor' style='font-size:8pt'>";
                $html .= "<td align='right' nowrap>$ordem</td>";
				$html .= "<td align='left' nowrap>$peca</td>";
				$html .= "<td align='left' nowrap>$descricao</td>";
				$html .= "<td align='center' nowrap>$qtde</td>";
				$html .= "</tr>";
			}
		}
	}

	echo $html;
	exit;
}

if(isset($_POST['busca_dados_relatorio'])){

	$resultData = [];

	$produto_linha = $_POST['produto_linha'];
	$produto_familia = $_POST['produto_familia'];
	$produto_referencia = $_POST['produto_referencia'];

	if(empty($produto_linha) && empty($produto_familia) && empty($produto_referencia)){
		exit;
	}

	$sql = 
	"SELECT tbl_lista_basica.qtde,
            tbl_peca.descricao as descricao_peca,
            tbl_peca.referencia as cod_peca,
            tbl_produto.referencia as cod_produto,
            tbl_produto.descricao as descricao_produto
            FROM tbl_lista_basica 
            JOIN tbl_peca USING (peca)
			JOIN tbl_produto ON tbl_lista_basica.produto = tbl_produto.produto AND 
								tbl_produto.fabrica_i = $login_fabrica
 		  	WHERE tbl_lista_basica.fabrica = $login_fabrica";


		if(!empty($produto_linha)){
		 	$sql .= " AND tbl_produto.linha = $produto_linha";
	 	}

	 	if(!empty($produto_familia)){
		 	$sql .= " AND tbl_produto.familia = $produto_familia";
	 	}

	 	if(!empty($produto_referencia)){
	 		$sql .= " AND tbl_produto.referencia = '$produto_referencia'";
	 	}

		$res = pg_query ($con,$sql);
		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$aux = pg_fetch_result($res,$i,para);

            	if(!empty($aux)){
                	$para_comp[] = $aux;
            	}
		}


		$retira_de = array_unique($para_comp);

		if(pg_num_rows($res) > 0) {

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

				$cod_produto  = pg_fetch_result ($res,$i,cod_produto);
				$desc_produto = retira_acentos(pg_fetch_result ($res,$i,descricao_produto));
				$cod_peca     = pg_fetch_result ($res,$i,cod_peca);
				$desc_peca    = retira_acentos(pg_fetch_result ($res,$i,descricao_peca));
				$qtde         = pg_fetch_result ($res,$i,qtde);

			 	if(in_array($peca,$retira_de)){
                	continue;
            	}

				$data = array(
					'cod_produto'  => $cod_produto, 
					'desc_produto' => utf8_encode($desc_produto),
					'cod_peca'	   => $cod_peca,
					'desc_peca'	   => utf8_encode($desc_peca),
					'qtde'		   => $qtde	
				);

				$resultData[] = $data;
            }
        }

        die(json_encode($resultData));
}


?>

<style>
	.arrow-left {
 		border: solid black;
  		border-width: 0 3px 3px 0;
  		display: inline-block;
  		padding: 3px;
   		transform: rotate(135deg);
  		-webkit-transform: rotate(135deg);
	}

	#loader-relatorio {
		border: 5px solid #f3f3f3;
	    -webkit-animation: spin 1s linear infinite;
	    animation: spin 1s linear infinite;
	    border-top: 5px solid #555;
	    border-radius: 50%;
	    width: 50px;
	    height: 50px;
	    display: none;
	}

	@keyframes spin {
  		0% { transform: rotate(0deg); }
  		100% { transform: rotate(360deg); }
	}

	#btn-relatorio-produtos-wrapper{
		text-align: -webkit-center;
		text-align: -moz-center;
	    margin-bottom: 30px;
	}

	#btn-voltar-produtos-wrapper{
		margin-bottom: 15px;
		display: none;
	    background-color: #d9e2ef;
	    padding: 20px;
	    margin-bottom: 30px;
	}

	#info-produto{
		font-size: 18px;
    	font-weight: bold;
	}

	#btn-voltar-produtos-wrapper > button{
		float:right; 
	    padding-top: 2px;
    	padding-bottom: 2px;
	}

	#btn-voltar-produtos-wrapper > button > span{
		font-weight: bold;
		padding-left: 10px;
	}

	.tbl_produto > td.center{
		text-align: -webkit-center;
		text-align: -moz-center;
	}

	.btn-lista-pecas{
		cursor: pointer;
	}

</style>
<div id="btn-voltar-produtos-wrapper">
	<span id="info-produto"> </span>
	<button class="btn btn-outline-primary" id="btn-voltar-produtos" role="button">
		<i class="arrow-left"></i><span>Voltar</span>
	</button>
</div>
<div id="loader-relatorio"></div>
<div id="btn-relatorio-produtos-wrapper">
	
	<button id="btn-relatorio-produtos" data-fabrica="<?=$login_fabrica?>" data-produto-linha="<?=$produto_linha?>" data-produto-familia="<?=$produto_familia ?>" data-produto-referencia="<?=$produto_referencia?>" type="button" class="btn btn-primary"><?php echo utf8_decode("Gerar Relatório"); ?></button>
</div> 
<table border='0' width='100%' id="legendas-tabela" style="margin-bottom:20px; display:none">
	<tr style='font-size:12px; text-align:left;'>
		<td bgcolor='#91C8FF' width='20' nowrap>&nbsp;</td><td style="width:80px" nowrap><? echo traduz("Alternativa"); ?></td>
	  	<td bgcolor='#E8C023' width='20' nowrap>&nbsp;</td><td nowrap><? echo traduz("De-Para"); ?></td>
    </tr>
</table>
<table id='tabela_produtos_multiple' class='table table-bordered table-fixed'>
        <thead>
                <tr class="titulo_coluna">
                    <th><? echo utf8_decode(traduz("Produto")); ?></th>
                    <th><? echo utf8_decode(traduz("Descrição")); ?></th>
                    <th><? echo utf8_decode(traduz("Linha")); ?></th>
                    <th><? echo utf8_decode(traduz("Família")); ?></th>
                    <th><? echo utf8_decode(traduz("Vizualizar Peças")); ?></th>
                </tr>
        </thead>
        <tbody>

        <? foreach($res_produtos as $r_produto) : ?>
			<tr class="tbl_produto">
				<td class="produto_referencia" align='left' nowrap><? echo $r_produto['referencia'] ?></td>
	        	<td class="produto_descricao" align='left' nowrap><? echo $r_produto['descricao'] ?></td>
	        	<td align='left' nowrap><? echo $r_produto['descricao_linha'] ?></td>
	        	<td align='left' nowrap><? echo $r_produto['descricao_familia'] ?></td>
	        	<td class='center' nowrap><a data-produto="<?php echo $r_produto['produto'] ?>" class="btn-lista-pecas" role="button"><? echo utf8_decode("Listar Peças") ?></button></td>
	        </tr>

		<? endforeach; ?>
    	</tbody>
</table>
<table id='tabela_produto_pecas' class='table table-bordered table-fixed'>
	<thead>
		<tr class='titulo_coluna'>
		  	<th><? echo utf8_decode(traduz("Posição")); ?></th>
	   		<th><? echo utf8_decode(traduz("Peça")); ?></th>
	   		<th><? echo utf8_decode(traduz("Referência")); ?></th>
			<th><? echo utf8_decode(traduz("Qtde")) ?></th>
		</tr>
	</thead>
	<tbody>
	</tbody>
</table>
<br>


<script type='text/javascript'>

	$(document).ready(function(){

		$("#btn-voltar-produtos").click(function(){
			$('#btn-voltar-produtos-wrapper').css('display', 'none');
			$("#tabela_produto_pecas_wrapper").css('display', 'none');
			$("#tabela_produtos_multiple_wrapper").fadeIn();
			$("#btn-relatorio-produtos-wrapper").fadeIn();
			$("#legendas-tabela").css('display', 'none');
		});

		$(".btn-lista-pecas").click(function(){

			var id_produto = $(this).data('produto');
			var td = $(this).parent();
			var tr = td.parent();
			var descricao = tr.find('.produto_descricao').text();

			$.ajax({
			    url: "lbm_consulta_tabela.php",
			    data: { 
			        "lista_pecas_produto": true,
			        "id_produto" : id_produto
			    },
			    type: "POST",
			    success: function(response) {

			    	// Reinicializa o datatable
		    	 	$('#tabela_produto_pecas').dataTable().fnClearTable();
    				$('#tabela_produto_pecas').dataTable().fnDestroy();

			    	$("#btn-relatorio-produtos-wrapper").css('display', 'none');
			    	$("#tabela_produtos_multiple_wrapper").css('display', 'none');
			    	$("#legendas-tabela").fadeIn();
			    	$("#tabela_produto_pecas > tbody").html(response);
			    	$("#tabela_produto_pecas_wrapper").fadeIn();
			    	$("#btn-voltar-produtos-wrapper").fadeIn();
			    	$("#info-produto").text(descricao);
	    			$.dataTableLoad({ table: "#tabela_produto_pecas"});
			    },
			    error: function(xhr) {
			    	console.log(xhr);
			    }
			});
		})

		$('#btn-relatorio-produtos').click(function(){

			var self = $(this);
			var fileCsv = '';

			$("#btn-relatorio-produtos-wrapper").css('display', 'none');
			$("#loader-relatorio").css('display', 'block');

		 	$.ajax({
		        type: 'POST',
		        url: 'lbm_consulta_tabela.php',
	         	data: { 
			        "busca_dados_relatorio": true,
			        "produto_linha" : $(this).data('produto-linha'),
			        "produto_familia" : $(this).data('produto-familia'),
			        "produto_referencia" : $(this).data('produto-referencia')
			    },
	         	dataType: "json",
	         	success : function(response){

         			fileCsv += '<?php echo utf8_decode("Cod. Produto"); ?>' + ';';
         			fileCsv += '<?php echo utf8_decode("Desc. Produto"); ?>' + ';';
         			fileCsv += '<?php echo utf8_decode("Cod. Peca"); ?>' + ';';
         			fileCsv += '<?php echo utf8_decode("Desc. Peca"); ?>' + ';';
         			fileCsv += '<?php echo utf8_decode("Qtde"); ?>';
         			fileCsv += "\n";


				    response.forEach(function(row) {
				    	row_desc_produto = row.desc_produto.replace(",", "-");
				    	row.desc_peca = row.desc_peca.replace(",", "-");
				    	fileCsv += row.cod_produto + ";";
				    	fileCsv += row.desc_produto + ";";
				    	fileCsv += row.cod_peca + ";";
				    	fileCsv += row.desc_peca + ";";
				    	fileCsv += row.qtde + "\n";
				    });
					 

				    var link = document.createElement('a');
				    link.href = 'data:text/csv;charset=utf-8,' + encodeURI(fileCsv);
				    link.target = '_blank';
				   	link.download = 'relatorio_lista_basica_' + '<?php echo date("dmYHi"); ?>' + '.csv';
				    link.click();

				    $("#btn-relatorio-produtos-wrapper").css('display', 'block');
					$("#loader-relatorio").css('display', 'none');

	         	},
			    error: function(xhr, ext) {
			    	console.log(xhr);
			    	console.log(ext);
			    }
		    });
		});

		$.dataTableLoad({ table: "#tabela_produtos_multiple"});
		$.dataTableLoad({ table: "#tabela_produto_pecas"});
		$("#tabela_produto_pecas_wrapper").css('display', 'none');
	});
</script>

