<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "cadastro";
$title = "CADASTRO DE CONTRATO DE POSTOS AUTORIZADOS";

if($_POST['ajax_ativa_inativa']){
	$aux_ativo 		= $_POST['ativo'];
	$aux_contrato 	= $_POST['contrato'];

	if(!empty($aux_ativo)){
		$resS = pg_query($con,"BEGIN TRANSACTION ");

		$sqlAtivo_Inativo = "UPDATE tbl_contrato SET ativo = '{$aux_ativo}' WHERE contrato = {$aux_contrato}";
		
		$resAtivo_Inativo = pg_query($con, $sqlAtivo_Inativo);

		if(strlen(pg_last_error()) > 0){
			$msg_erro = pg_last_error();
		}

		if(count($msg_erro)){
			$resS = pg_query($con," ROLLBACK TRANSACTION ");			
		} else {
			$resS = pg_query($con, " COMMIT TRANSACTION ");									
		}		
	}
}

include "cabecalho_new.php";

$plugins = array(
    "shadowbox",
    "price_format",
    "mask",
    "ckeditor",
    "autocomplete",
    "ajaxform",
    "fancyzoom",
    "multiselect",
    "dataTable"
);

include("plugin_loader.php");

$codigo_coringa = array(
	"codigo" 			=> "Código do posto",
	"cnpj" 				=> "CNPJ do posto",
	"nome" 				=> "Nome do posto",
	"endereco" 			=> "Endereço do posto",
	"numero" 			=> "Número do posto",
	"complemento" 		=> "Complemento do posto",
	"cep" 				=> "CEP do posto",
	"cidade" 			=> "Cidade do posto",
	"estado" 			=> "Estado do posto",
	"fone" 				=> "Telefone do posto",
	"ie" 				=> "Inscrição Estadual do posto",
	"bairro" 			=> "Bairro do posto",
	"pais" 				=> "País do posto",
	"responsavel" 		=> "Responsável do posto",
	"cpf_responsavel" 	=> "CPF do responsável do posto",
	"rg_responsavel" 	=> "RG do responsável do posto"
);

?>

<script>
	$(function(){
		$("#linha").multiselect({
        	selectedText: "selecionados # de #"
		});
	})
</script>

<?
if(isset($_GET['codigo_contrato'])){
	$cod_contrato_alterar = $_GET['codigo_contrato'];
	
	$sqlAlterar = "SELECT DISTINCT
						tbl_contrato.contrato,				
						tbl_contrato.numero_contrato,
						tbl_contrato.data_input,
						tbl_contrato.ativo,
						tbl_contrato.descricao,
						ARRAY_TO_STRING(ARRAY_AGG(tbl_linha.linha), ',') AS linha_contrato
						FROM tbl_contrato
						JOIN tbl_linha ON tbl_linha.fabrica = tbl_contrato.fabrica AND STRPOS(tbl_contrato.linhas::text,tbl_linha.linha::text) > 0
						WHERE tbl_contrato.fabrica = {$login_fabrica}
						AND tbl_contrato.contrato = {$cod_contrato_alterar}
						GROUP BY
						tbl_contrato.contrato,
						tbl_contrato.numero_contrato
					";

	$resAlterar = pg_query($con, $sqlAlterar);

	$num_contrato 	   = pg_fetch_result($resAlterar, 0, 'numero_contrato');
	$ativo_contrato    = pg_fetch_result($resAlterar, 0, 'ativo');
	$desc_contrato	   = pg_fetch_result($resAlterar, 0, 'descricao');	
	$linha_contrato    = pg_fetch_result($resAlterar, 0, 'linha_contrato');
	$array_linha       = explode(",", $linha_contrato);

	$desc_contrato = nl2br($desc_contrato);
	$desc_contrato = str_replace("<br />", "<br>\\", $desc_contrato);

	if($ativo_contrato == 't'){
		$ativo_contrato = true;		
	} else {
		$ativo_contrato = false;		
	}	
	
	?>
	<script>
		$(function(){
			$('#numero_contrato').val('<?=$num_contrato ?>');
			$("#ativo").prop( "checked", <?=$ativo_contrato ?> );													
			CKEDITOR.instances.descricao.setData('<?=$desc_contrato?>');			
			
			//$('select[name=linha]').empty();
			var contador;
			var linhas = '<?=$linha_contrato?>';	

			var linhas_x = linhas.split(',');		
			
			for(contador = 0; contador < linhas_x.length; contador++){					
				$('input[value=' + linhas_x[contador] + ']').attr('aria-selected', true).prop("checked", true);	
			}	
			$("#linha").multiselect({
        		selectedText: "selecionados # de #"
			});		
		})
	</script>
	<?
}

if (strlen($_POST["btn_acao"]) > 0) {
	$btnacao = trim($_POST["btn_acao"]);	
}

if($btnacao == "gravar"){	
	$numero_contrato	= trim($_POST['numero_contrato']);
	$codigo_linha		= $_POST['linha'];
	$ativo 		 		= trim($_POST['ativo']);
	$descricao			= trim($_POST['descricao']);
	$nova_descricao     = str_replace(['<p>','</p>'], '', $descricao);	

	if(count($codigo_linha) > 0){
		$linha 	= json_encode($codigo_linha);		
	} else {
		$linha 	= "";
	}

	if(empty($linha)){
		$msg_erro = "Selecione uma linha";
	}
	
	if($ativo == true){
		$ativo = 't';		
	} else {
		$ativo = 'f';		
	}

	// Verificar se contrato já esta cadastrado
	$sqlVerificar = "SELECT tbl_contrato.numero_contrato
						FROM tbl_contrato
						WHERE tbl_contrato.numero_contrato = '{$numero_contrato}'
						AND fabrica = {$login_fabrica}
					";

	$resVerificar = pg_query($con, $sqlVerificar);	

	if(pg_num_rows($resVerificar) != 0 AND empty($cod_contrato_alterar)){
		$msg_erro = "Contrato {$numero_contrato} já está cadastrado";
	} else if(pg_num_rows($resVerificar) != 0){
		$resS = pg_query($con,"BEGIN TRANSACTION ");

		$sqlUpdate = "UPDATE tbl_contrato SET ativo = '{$ativo}', descricao = '{$nova_descricao}', linhas = '$linha' WHERE contrato = $cod_contrato_alterar";	

		$resUpdate = pg_query($con, $sqlUpdate);

		if(strlen(pg_last_error()) > 0){
			$msg_erro = pg_last_error();
		}

		if(count($msg_erro)){
			$resS = pg_query($con," ROLLBACK TRANSACTION ");			
		} else {
			$resS = pg_query($con, " COMMIT TRANSACTION ");			
			$msg = "Alteração efetuada com sucesso";	
		}	

		$resAlterar = pg_query($con, $sqlAlterar);

		$desc_contrato	   = pg_fetch_result($resAlterar, 0, 'descricao');	
		$desc_contrato = nl2br($desc_contrato);
		$desc_contrato = str_replace("<br />", "<br>\\", $desc_contrato);
		
		?>
			<script>
				$(function(){
					CKEDITOR.instances.descricao.setData('<?=$desc_contrato?>');			
				})
			</script>
		<?

	} else {
		if(!empty($numero_contrato)){
			$resS = pg_query($con,"BEGIN TRANSACTION ");

			$sqlContrato = "INSERT INTO tbl_contrato (fabrica,numero_contrato,descricao,ativo,linhas) VALUES ({$login_fabrica},'{$numero_contrato}','{$nova_descricao}','{$ativo}','$linha')";

			$resContrato = pg_query($con, $sqlContrato);	

			if(strlen(pg_last_error()) > 0){
				$msg_erro = pg_last_error();
			}

			if(count($msg_erro)){
				$resS = pg_query($con," ROLLBACK TRANSACTION ");			
			} else {
				$resS = pg_query($con, " COMMIT TRANSACTION ");			
				$msg = "Contrato salvo com sucesso";
			}		
		} else {
			$msg_erro = "Número do Contrato não pode ficar em branco";
		}
	}		
}

$sqlTabela = "SELECT distinct
				tbl_contrato.contrato,				
				tbl_contrato.numero_contrato,
				tbl_contrato.data_input,
				tbl_contrato.ativo,
				tbl_contrato.descricao,
				ARRAY_TO_STRING(ARRAY_AGG(tbl_linha.nome), ',') AS linha_contrato
				FROM tbl_contrato
				JOIN tbl_linha ON tbl_linha.fabrica = tbl_contrato.fabrica AND STRPOS(tbl_contrato.linhas::text,tbl_linha.linha::text) > 0
				WHERE tbl_contrato.fabrica = {$login_fabrica}
				GROUP BY
				tbl_contrato.contrato,
				tbl_contrato.numero_contrato
				ORDER BY tbl_contrato.data_input DESC									
				";					

$resTabela = pg_query($con, $sqlTabela);	

$msg_aviso = 'Você pode utilizar caracteres coringas<br/>Ex. Se no texto conter o coringa :nome_posto , quando o posto abrir o contrato deverá substituir este coringa pelo nome do posto<br />Para visualizar os caracters coringas, clique no botão Exibir Legenda';

$display = (strlen($msg_aviso) > 0) ? 'block' : 'none'; ?>
    <div class="alert alert-warning" style="display: <?=$display; ?>">
        <h4><? echo $msg_aviso; ?></h4>
    </div>

<?
$display = (strlen($msg) > 0) ? 'block' : 'none'; ?>
	<div class="alert alert-success" style="display: <?=$display; ?>">
		<h4><? echo $msg; ?></h4>
	</div>    

<? 
$display = (strlen($msg_erro) > 0) ? 'block' : 'none'; ?>
	<div class="alert alert-error" style="display: <?=$display; ?>">
		<h4><?=$msg_erro?></h4>
    </div>    

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_contrato" method="post" action="" align="center" class='form-search form-inline tc_formulario' >

	<div class="titulo_tabela">Cadastro de Contrato</div>
	<br/>
	<div class="row-fluid">
		<div class="span2"></div>
	    <div class="span3">
            <div class='control-group'>
                <label class='control-label'>Número do Contrato</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <input class="span12 numero_contrato" type="text" id="numero_contrato" name="numero_contrato" value="" >
                </div>
            </div>
        </div>
        <div class="span3">
			<div class='control-group'>
			    <label class='control-label' for='linha'>Linha</label>
			    <div class='controls controls-row'>			    						
						<?
							if(in_array($login_fabrica , array(167,203))){
							    
							    $sqlLinha = "SELECT  
							    		linha,
							    		nome, 
							    		codigo_linha
							            FROM    tbl_linha
							            WHERE   fabrica = $login_fabrica 
   							            AND ativo IS TRUE
							            ORDER BY nome";
							    $resLinha = pg_query ($con,$sqlLinha);							    

							    if (pg_num_rows($resLinha) > 0) {
							    	echo "<select type='checkbox' name='linha[]' id='linha' value='linha' class='span12 linha' multiple='multiple'>";
							    	for ($x = 0; $x < pg_num_rows($resLinha); $x++){
							    		$linha  		= trim(pg_fetch_result($resLinha,$x,linha));
							    		$nome_l 	 	= trim(pg_fetch_result($resLinha,$x,nome));				

							    		echo "<option value='$linha' name='$linha'";
							    		if(in_array($linha, $array_linha)){
							    			echo "SELECTED";
							    		}
							    		echo ">$nome_l</option>";							    		
							    	}
							    	echo "</select>";
							    }
							}
						?>						
					</select>
			    </div>
			</div>
		</div>
        <div class="span2" style="display: flex; justify-content: center;">
			<div class='control-group'>
			    <label class='control-label'>Ativo</label>
			    <div class='controls controls-row'>
					<input type='checkbox' name='ativo' id='ativo' value='TRUE' class='ativo' />
			    </div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span8">
			<div class='control-group'>
			    <label class='control-label'>Texto do Contrato</label>
			    <div class='controls controls-row'>
		    	    <div class='span12'>
                        <textarea name="descricao" id="descricao" class="span12"></textarea>
                    </div>
			    </div>
			</div>		
		</div>
		<div class="span2"></div>
	</div><br />
	<div class="row-fluid">		
		<div class="span2"></div>		
		<div class="span8">
			<div class='control-group'>
				<div class='controls controls-row' style="display: flex; justify-content: center;">					
					<button name="btn_gravar" id="btn_gravar" class='btn' type="button" onclick="submitForm($(this).parents('form'),'gravar');" alt="Gravar formulário">Gravar</button>
					<input type='hidden' id="btn_click" name='btn_acao' value='' />	&nbsp;&nbsp;					
					<button name="btn_legenda" id="btn_legenda" class='btn btn-primary btn-toggle' type="button" data-element="#legenda" alt="Exibir Legenda">Exibir Legenda</button>					
					<button name="btn_ocultar_legenda" id="btn_ocultar_legenda" class='btn btn-danger btn-toggle' type="button" data-element="#legenda" alt="Ocultar Legenda" style="display: none;">Ocultar Legenda</button>	
				</div>
			</div>
		</div>				
		<div class="span2"></div>
	</div>
</form>

<script>
	$(function(){
        $(".btn-toggle").click(function(e){
            e.preventDefault();
            el = $(this).data('element');
            $(el).toggle();

        });
    });

    $(document).ready(function(){
    	$("button#btn_legenda").click(function(){
    		$("button#btn_legenda").hide();    		
    		$("button#btn_ocultar_legenda").show(); 
    	});
    	$("button#btn_ocultar_legenda").click(function(){
    		$("button#btn_legenda").show();    		
    		$("button#btn_ocultar_legenda").hide(); 
    	});
    });

</script>

<div class="legenda" name="legenda" id="legenda" style="display: none;">
	<table id="descricao_legenda" class="table table-striped table-bordered table-hover table-fixed">
	    <thead>
	        <tr class="titulo_tabela" >
	            <th colspan="100">
	                Legenda
	            </th>
	        </tr>
	        <tr class='titulo_coluna'>
	        	<th>Código Coringa</th>
	        	<th>Descrição</th>
	        </tr>
	    </thead>
	    <tbody>
	    	<?
	    		$total_codigo_coringa = count($codigo_coringa);
	    		for($x=0; $x<$total_codigo_coringa; $x++){
	    			$cor = ($x % 2 == 0) ? "#F7F5F0": '#F1F4FA'; 
	    			$chave = key($codigo_coringa);					

	    			switch ($chave) {
	    				case 'codigo':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["codigo"] . "</td>";		    				
		    				echo "</tr>";
	    					break;	    				
	    				case 'cnpj':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["cnpj"] . "</td>";		    				
		    				echo "</tr>";
	    					break;
	    				case 'nome':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["nome"] . "</td>";
		    				echo "</tr>";
	    					break;
	    				case 'endereco':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["endereco"] . "</td>";
		    				echo "</tr>";
	    					break;
	    				case 'numero':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["numero"] . "</td>";
		    				echo "</tr>";
	    					break;
	    				case 'complemento':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["complemento"] . "</td>";
		    				echo "</tr>";
	    					break;
	    				case 'cep':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["cep"] . "</td>";
		    				echo "</tr>";
	    					break;
	    				case 'cidade':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["cidade"] . "</td>";
		    				echo "</tr>";
	    					break;
	    				case 'estado':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["estado"] . "</td>";
		    				echo "</tr>";
	    					break;
	    				case 'fone':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["fone"] . "</td>";
		    				echo "</tr>";
	    					break;
	    				case 'ie':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["ie"] . "</td>";
		    				echo "</tr>";
	    					break;
	    				case 'bairro':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["bairro"] . "</td>";
		    				echo "</tr>";
	    					break;
	    				case 'pais':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["pais"] . "</td>";
		    				echo "</tr>";
	    					break;	    					
	    				case 'responsavel':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["responsavel"] . "</td>";
		    				echo "</tr>";
	    					break;
	    				case 'cpf_responsavel':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["cpf_responsavel"] . "</td>";
		    				echo "</tr>";
	    					break;
	    				case 'rg_responsavel':
		    				echo "<tr>";
		    				echo "<td class='tac'>:" . $chave . "_posto</td>";
		    				echo "<td class='tac'>" . $codigo_coringa["rg_responsavel"] . "</td>";
		    				echo "</tr>";
	    					break;	    						    						    					
	    				default:
	    					break;
	    			}
	    			next($codigo_coringa);
	    		}
	    	?>
	    </tbody>
	</table>
</div>
<form name="frm_listar_contratos" method="post" action="" align="center" class='form-search form-inline tc_formulario' >
	<table id="defeito_reclamado" class="table table-striped table-bordered table-hover table-fixed">
	    <thead>
	        <tr class="titulo_tabela" >
	            <th colspan="100">
	                Relação de Contratos
	            </th>
	        </tr>
	        <tr class='titulo_coluna'>
	        	<th>Número Contrato</th>
	        	<th>Linha</th>
	        	<th>Data</th>
				<th>Ações</th>
			</tr>
		</thead>		
		<tbody>
		    <? 		    
		    	for ($y = 0; $y < pg_num_rows($resTabela); $y++) {
		    		$cor = ($y % 2 == 0) ? "#F7F5F0": '#F1F4FA'; 
		    		
		    		$nome_linha  	 	 = trim(pg_result($resTabela,$y,'linha_contrato'));
		    		$data_input 	 	 = trim(pg_result($resTabela,$y,'data_input'));
					$numero_contrato 	 = trim(pg_result($resTabela,$y,'numero_contrato'));					
					$cod_contrato_alt 	 = trim(pg_result($resTabela,$y,'contrato'));				
					$ativo 				 = trim(pg_result($resTabela,$y,'ativo'));							
		    ?>
	            <tr style="background-color:<?=$cor?>">
	            	<td class="tac">
	            		<?=$numero_contrato ?>
	            	</td>
	            	<td class="tac">
	            		<?=$nome_linha ?>
	            	</td>     
	            	<td class="tac">
	            		<?
	            			echo date('d/m/Y H:i:s', strtotime($data_input));
	            		?>
	            	</td>
	                <td class="tac">
	                	<input type="hidden" name="cod_contrato_alterar_<?=$cod_contrato_alt ?>" id="cod_contrato_alterar_<?=$cod_contrato_alt ?>" value="<?=$cod_contrato_alt ?>" />
	                	<a href="manutencao_postos_autorizados.php?codigo_contrato=<?=$cod_contrato_alt?>" type="button" class="btn btn-small btn-primary" onclick="submitForm($(this).parents('form'),'alterar');" >Alterar</a>	                	
	                    <button data-contrato="<?=$cod_contrato_alt ?>" data-ativo="<?=$ativo ?>" type="button" name="btn_ativo_inativo" id="btn_ativo_inativo" class="btn_ativo_inativo btn btn-small <?=($ativo == 't') ? 'btn-success' : 'btn-danger'?>"><?=($ativo == 't') ? 'Ativo' : 'Inativo'?></button>                    
	                </td>
	            </tr>
	        <? } ?>
		</tbody>
	</table>
</form>

<script type='text/javascript' src='../js/FancyZoom.js'></script>
<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>

<script>	
	CKEDITOR.replace('descricao', {
		width: '100%',
		uiColor: '#e0e0e0'
	});	

	$(function () {
        $(".btn_ativo_inativo").click(function(){

            var btn    		= $(this);            
            var ativo 		= $(btn).data("ativo");
            var contrato	= $(btn).data("contrato");

            if(ativo == 't'){
            	ativo_aux = 'f';
            } else {
            	ativo_aux = 't';
            }
            
            $.ajax({
                url: "manutencao_postos_autorizados.php",
                type: "POST",
                data: { 
                    ajax_ativa_inativa : true,                    
                    ativo : ativo_aux,
                    contrato : contrato
                },
                beforeSend:function(){
                    $(btn).text("Alterando...");
                },
                complete: function (data) {
                	$(btn).data("ativo", ativo_aux);
                    if (data != 'erro') {
                        $(btn).toggleClass("btn-success btn-danger");

                        if (ativo_aux == "f") {
                            $(btn).text("Inativo");
                        } else {
                            $(btn).text("Ativo");
                        }

                    } else {
                        alert("Erro ao Ativar/Inativar Defeito");
                    }
                }
            });
        });
    });

	$.dataTableLoad({
	    table : "#defeito_reclamado"
	});	    

</script>

<?
include "rodape.php";
?>