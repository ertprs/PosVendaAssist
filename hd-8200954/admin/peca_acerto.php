<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_POST["btn_acao"]) > 0)   $btn_acao   = $_POST["btn_acao"];
else $btn_acao   = $_GET["btn_acao"];
if (strlen($_POST["produto"]) > 0)    $produto    = $_POST["produto"];
else $produto    = $_GET["produto"];
if (strlen($_POST["produto_referencia"]) > 0) $referencia = $_POST["produto_referencia"];
else $referencia = $_GET["produto_referencia"];
if (strlen($_POST["produto_descricao"]) > 0)  $descricao  = $_POST["produto_descricao"];
else $descricao  = $_GET["produto_descricao"];
if (strlen($_POST["familia"]) > 0)  $x_familia  = $_POST["familia"];
else $x_familia  = $_GET["familia"];

if (strlen($_POST["msg_ok"]) > 0) $msg_ok = $_POST["msg_ok"];
else $msg_ok = $_GET["msg_ok"];

if (empty($produto) && !empty($referencia)) {
		
	$sql_prod = "  SELECT
                    tbl_produto.produto,
                    tbl_produto.referencia,
                    tbl_produto.referencia_fabrica as referencia_fabrica_produto,
                    tbl_produto.descricao,
                    tbl_produto.voltagem
                FROM tbl_produto
                WHERE referencia = '{$referencia}'
                AND   fabrica_i  = {$login_fabrica};
            ";
            
            $res_prod = pg_query($con,$sql_prod);

            if(pg_num_rows($res_prod) == 0){
                $msg_erro['msg'][] = traduz("Referencia {$produto_referencia} não encontrada");
                $msg_erro["campos"][] = "produto";
            }else{
                $produto = pg_fetch_result($res_prod,0,'produto');
            }
}

if ($btn_acao == "gravar") {
	$peca_qtde = $_POST["peca_qtde"];

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	for ($i = 0 ; $i < $peca_qtde ; $i++) {
        $peca                       = trim($_POST["peca_".$i]);
		$peso						= trim($_POST["peso_".$i]);
		$origem						= trim($_POST["origem_".$i]);
		$garantia_diferenciada		= trim($_POST["garantia_diferenciada_".$i]);
		$devolucao_obrigatoria		= trim($_POST["devolucao_obrigatoria_".$i]);
		$item_aparencia				= trim($_POST["item_aparencia_".$i]);
		$acumular_kit				= trim($_POST["acumular_kit_".$i]);
		$retorna_conserto			= trim($_POST["retorna_conserto_".$i]);
		$mao_de_obra_diferenciada	= trim($_POST["mao_de_obra_diferenciada"]);
		$bloqueada_garantia			= trim($_POST["bloqueada_garantia_".$i]);
		$acessorio					= trim($_POST["acessorio_".$i]);
		$aguarda_inspecao			= trim($_POST["aguarda_inspecao_".$i]);
		$peca_critica				= trim($_POST["peca_critica".$i]);
		$controla_saldo				= trim($_POST["controla_saldo".$i]);

		if ($login_fabrica == 138) {
			$compressor = $_POST["compressor_$i"];
			$serpentina = $_POST["serpentina_$i"];
		}
		if(in_array($login_fabrica,array(151,153,179))){
            		$bloqueada_venda    = trim($_POST["bloqueada_venda_".$i]);
        	}
		if ($login_fabrica==11 or $login_fabrica == 172) {
			$peca_unica_os				= trim($_POST["peca_unica_os".$i]);
		}
		if(in_array($login_fabrica,array(190))){
            		$consumiveis    = trim($_POST["consumiveis_".$i]);
        	}

		$ativo     					= trim($_POST["ativo_".$i]);

if(strlen($ativo)>0){ $ativo = "'t'";}else{ $ativo = "'f'";}

		if (strlen($garantia_diferenciada) == 0) $garantia_diferenciada = 'null';
		if (strlen($devolucao_obrigatoria) == 0) $devolucao_obrigatoria = 'f';
		if (strlen($item_aparencia) == 0)        $item_aparencia        = 'f';
		if (strlen($acumular_kit) == 0)          $acumular_kit          = 'f';
        if (strlen($retorna_conserto) == 0)      $retorna_conserto      = 'f';
		$peso = strlen($peso) > 0 ? str_replace(',','.',$peso) : 'null';

		if (strlen($_POST["mao_de_obra_diferenciada"]) > 0) {
			$aux_mao_de_obra_diferenciada = "'". $_POST["mao_de_obra_diferenciada"] ."'";
		}else{
			$aux_mao_de_obra_diferenciada = "null";
		}

		if (strlen($bloqueada_garantia) == 0)    $bloqueada_garantia    = 'f';
		if (strlen($bloqueada_venda) == 0)        $bloqueada_venda    = 'f';
		if (strlen($acessorio) == 0)             $acessorio             = 'f';
		if (strlen($aguarda_inspecao) == 0)      $aguarda_inspecao      = 'f';
		if (strlen($peca_critica) == 0)          $peca_critica          = 'f';
		if (strlen($controla_saldo) == 0)        $controla_saldo        = 'f';
		if (strlen($peca_unica_os) == 0)         $peca_unica_os         = 'f';

		if (in_array($login_fabrica, [138,190])) {
			$upadateParametrosAdicionais = "";

			$sql = "SELECT parametros_adicionais FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$peca}";
			$res = pg_query($con, $sql);

			$parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");

			if (!empty($parametros_adicionais)) {
				$parametros_adicionais = json_decode($parametros_adicionais, true);

				if ($login_fabrica == 138) {
					if ($compressor == "t") {
						$parametros_adicionais["tipo_peca"] = "compressor";
					} else if ($serpentina == "t") {
						$parametros_adicionais["tipo_peca"] = "serpentina";
					} else {
						$parametros_adicionais["tipo_peca"] = "";
					}
				}
				if ($login_fabrica  == 190 && strlen($consumiveis) > 0) {
					$parametros_adicionais["consumiveis"] = $consumiveis;
				}

				$parametros_adicionais = json_encode($parametros_adicionais);
				$upadateParametrosAdicionais = ", parametros_adicionais = '{$parametros_adicionais}' ";
			}else{

				if ($login_fabrica == 138) {
					if ($compressor == "t") {
						$parametros_adicionais["tipo_peca"] = "compressor";
					} else if ($serpentina == "t") {
						$parametros_adicionais["tipo_peca"] = "serpentina";
					} else {
						$parametros_adicionais["tipo_peca"] = "";
					}
				}
				if ($login_fabrica  == 190 && strlen($consumiveis) > 0) {
					$parametros_adicionais["consumiveis"] = $consumiveis;
				}


				$parametros_adicionais = json_encode($parametros_adicionais);
				$upadateParametrosAdicionais = ", parametros_adicionais = '{$parametros_adicionais}' ";

			}
		}

		$sqlA = "select * from tbl_peca where peca = $peca and fabrica = $login_fabrica";
		$resA = pg_query($con,$sqlA);
		$auditor_antes = pg_fetch_assoc($resA);

		$sql =	"UPDATE tbl_peca SET
					origem					= '$origem'                                     ,
                    garantia_diferenciada   = $garantia_diferenciada                        ,
					data_atualizacao      	= current_timestamp                ,
					admin                 = $login_admin                     ,
					peso                   = $peso                                        ,
					devolucao_obrigatoria	= '$devolucao_obrigatoria'                      ,
					item_aparencia			= '$item_aparencia'                             ,
					acumular_kit			= '$acumular_kit'                               ,
					retorna_conserto		= '$retorna_conserto'                           ,
					mao_de_obra_troca		= fnc_limpa_moeda($aux_mao_de_obra_diferenciada),
					bloqueada_garantia		= '$bloqueada_garantia'                         ,
					bloqueada_venda		      = '$bloqueada_venda'                         ,
					acessorio				= '$acessorio'                                  , ";
if($login_fabrica==11 or $login_fabrica == 172){$sql .= " controla_saldo = '$peca_unica_os' , ";}
if($login_fabrica==74){$sql .= " controla_saldo = '$controla_saldo' , ";}
		$sql .= " ativo = $ativo , ";
		$sql .= "aguarda_inspecao		= '$aguarda_inspecao'                   ,
				peca_critica			= '$peca_critica'
				{$upadateParametrosAdicionais}
				WHERE	fabrica	= $login_fabrica
				AND		peca	= $peca;";
		$res = pg_exec ($con,$sql);
		if (isset($upadateParametrosAdicionais)) {
			//echo $sql;
			//echo "<br /><br />";
		}
		$msg_erro = pg_errormessage($con);

		if(empty($msg_erro)){
			$sqlD = "select * from tbl_peca where peca = $peca and fabrica = $login_fabrica";
			$resD = pg_query($con,$sqlD);
			$auditor_depois = pg_fetch_assoc($resD);
			$nome_servidor = $_SERVER['SERVER_NAME'];
			$nome_uri = $_SERVER['REQUEST_URI'];
			$nome_url = $nome_servidor.$nome_uri;
			auditorLog($peca,$auditor_antes,$auditor_depois,"tbl_peca",$nome_url);
		}
	}

	$pagina_atual = $_POST["pagina_atual"];
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");		
		if (strlen($produto) > 0) {			
			header ("Location: $PHP_SELF?produto=$produto&referencia=$referencia&descricao=$descricao&btn_acao=listar_por_produto");			
		}else{
			$total_pagina	= $_POST['numero_paginas'];
			$ttl_atual		= $pagina_atual + 1;
			if($total_pagina == $ttl_atual){

			}else{
				$pagina_atual = $pagina_atual + 1;
			}
			if($login_fabrica == 151){				
				header ("Location: $PHP_SELF?pagina=$pagina_atual&msg_ok=1");	
			} else {
				header ("Location: $PHP_SELF?pagina=$pagina_atual");
			}			
		}
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "cadastro";
$title = traduz("MANUTENÇÃO DE PEÇAS");
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"shadowbox"
);

include("plugin_loader.php");
?>

<style>

table > thead th {
	font-size: 12px;
}

</style>

<script type="text/javascript">

	$(function() {
		$.autocompleteLoad(Array("produto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		  $(document).on("click", "span[rel=lupa]", function () {
           $.lupa($(this),Array('posicao'));
        });
	});

	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);		
	}

</script>

<script language='javascript'>
$(function(){
	$('input[name^=serpentina_],input[name^=compressor_]').click(function(){
		var contador 	= $(this).attr('rel');
		var nome 		= $(this).attr('name');

		if(nome == 'compressor_'+contador){
			$('input[name=serpentina_'+contador+']').removeAttr('checked');
		}else{
			$('input[name=compressor_'+contador+']').removeAttr('checked');
		}
	});
});

</script>

	<?php
	if($login_fabrica == 151){			
		if(empty($msg_erro) && !empty($msg_ok)) {
			echo "<p><div id='msg_ok' class='alert alert-success'><h4>Gravação Efetuada com Sucesso</h4></div></p>";		
		}		
	} 

	if ($_POST["btn_acao"] <> 'listar_por_produto' AND strlen($produto) == 0)	{	?>

	<input type='hidden' name='produto' value='' form="frm_peca" >

	<div class="titulo_tabela">	<?=traduz('Parâmetros de Pesquisa')?>	</div>
	<br />
	<div class="container tc_container">

		<div class="row-fluid">
			<div class="span2"></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'><?=traduz('Ref. Produto')?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<h5 class="asteristico">*</h5>
							<input form="frm_peca" type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'><?=traduz('Descrição Produto')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<h5 class="asteristico">*</h5>
							<input form="frm_peca" type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<? if($login_fabrica == 151) {
			$txt_botao = "Pesquisar";
			$alt_botao = "Pesquisar"; ?>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='familia'>Família</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>	
								<?
									$sqlFamilia = "SELECT 													
													familia_peca,
													descricao
													FROM tbl_familia_peca
													WHERE fabrica = {$login_fabrica}
													AND ativo = 't'
													ORDER BY descricao";

									$resFamilia = pg_query($con, $sqlFamilia);

								?>							
								<select form="frm_peca" name='familia' id='familia' class='span12'>
								<?
									if (pg_numrows($resFamilia) > 0) {
										echo "<option value=''>Selecionar</option>";

										for ($i = 0 ; $i < pg_numrows ($resFamilia) ; $i++){
				                            $aux_cod_familia       = trim(pg_result($resFamilia, $i, 'familia_peca'));
				                            $aux_descricao_familia = trim(pg_result($resFamilia, $i, 'descricao'));
				                            echo "<option value='$aux_cod_familia'>$aux_descricao_familia</option>\n";		                            
										}
									}
								?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
		<? } else {
				$txt_botao = "Lista Básica de Materiais";
				$alt_botao = "Listar Lista Básica de Materiais por Produto";
			} ?>
		<p>
			<center>
				<input form="frm_peca" type="button" value="<?=$txt_botao?>" onclick="javascript: if (document.frm_peca.btn_acao.value == '') { document.frm_peca.btn_acao.value='listar_por_produto' ; document.frm_peca.submit() } else { alert ('Aguarde submissão') }" alt='<?=$alt_botao?>' border='0' class='btn' >
			</center>
		</p>
	</div>
	<br />
<?php
}
else
{

	if(strlen($x_familia) == 0) {
		if( (strlen($referencia)==0) || (strlen($descricao)==0) ) $msg_erro = traduz("Informe a Referência e Descrição do Produto.");
	}

	//Mensagem de erro
	if (!empty($msg_erro)) {
		if ( (strlen($referencia)==0) || (strlen($descricao)==0) ) echo "<p><div class='alert alert-error'><h4>".$msg_erro."</h4></div></p>";
	?>
	<input form="frm_peca" type='hidden' name='btn_acao' value=''>	
	<input type='hidden' name='produto' value='' form="frm_peca" >
	<input form="frm_peca" type='hidden' name='produto' value=''>
	<input form="frm_peca" type='hidden' name='produto_referencia' value=''>
	<input form="frm_peca" type='hidden' name='produto_descricao' value=''>
	<input form="frm_peca" type='hidden' name='familia_oculto' value=''>

	<div class="titulo_tabela">	<?=traduz('Parâmetros de Pesquisa')?>	</div>
	<br />
	<div class="container tc_container">

		<div class="row-fluid">
			<div class="span2"></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'><?=traduz('Ref. Produto')?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<h5 class="asteristico">*</h5>
							<input form="frm_peca" type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'><?=traduz('Descrição Produto')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<h5 class="asteristico">*</h5>
							<input form="frm_peca" type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<? if($login_fabrica == 151) {
			$txt_botao = "Pesquisar";
			$alt_botao = "Pesquisar"; ?>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='familia'>Família</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>	
								<?
									$sqlFamilia = "SELECT 													
													familia_peca,
													descricao
													FROM tbl_familia_peca
													WHERE fabrica = {$login_fabrica}
													AND ativo = 't'
													ORDER BY descricao";

									$resFamilia = pg_query($con, $sqlFamilia);

								?>							
								<select name='familia' id='familia' class='span12'>
								<?
									if (pg_numrows($resFamilia) > 0) {
										echo "<option value=''>Selecionar</option>";

										for ($i = 0 ; $i < pg_numrows ($resFamilia) ; $i++){
				                            $aux_cod_familia       = trim(pg_result($resFamilia, $i, 'familia_peca'));
				                            $aux_descricao_familia = trim(pg_result($resFamilia, $i, 'descricao'));
				                            echo "<option value='$aux_cod_familia'>$aux_descricao_familia</option>\n";		                            
										}
									}
								?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
		<? } else {
				$txt_botao = "Lista Básica de Materiais";
				$alt_botao = "Listar Lista Básica de Materiais por Produto";
			} ?>
		<p>
			<center>
				<input form="frm_peca" type="button" value="<?=$txt_botao?>" onclick="javascript: if (document.frm_peca.btn_acao.value == '') { document.frm_peca.btn_acao.value='listar_por_produto' ; document.frm_peca.submit() } else { alert ('Aguarde submissão') }" alt='<?=$alt_botao?>' border='0' class='btn' >
			</center>
		</p>
	</div>
	<br />
<?php
	} else {
 ?>
	<input form="frm_peca" type='hidden' name='produto' value='<?echo $produto?>'>
	<input form="frm_peca" type='hidden' name='produto_referencia' value='<?echo $referencia?>'>
	<input form="frm_peca" type='hidden' name='produto_descricao' value='<?echo $descricao?>'>

	<div class="titulo_tabela">	<?=traduz('Parâmetros de Pesquisa')?>	</div>
	<br />
	<div class="container tc_container">

		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span1"></div>
			<div class="span2">
				<div class="control-group">
					<label class="control-label" for="tabela"> <?=traduz('Referência')?></label>
					<?php if(strlen($msg_erro)==0)	echo $referencia;	?>
				</div>
			</div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="tabela"> <?=traduz('Descrição')?></label>
					<?php if(strlen($msg_erro)==0)	echo $descricao;	?>
				</div>
			</div>
			<div class="span2">
				<div class="control-group">
					<label class="control-label" for="tabela"> Família</label>
					<?php

						$sqlF = "SELECT 													
									familia_peca,
									descricao
									FROM tbl_familia_peca
									WHERE fabrica = {$login_fabrica}
									AND ativo = 't'
									AND familia_peca = {$familia}";

						$resF = pg_query($con, $sqlF);

						if(strlen($msg_erro)==0) echo pg_fetch_result($resF, 0, 'descricao');	
					?>
				</div>
			</div>			
			<div class='span2'></div>
		</div>
		<p>
			<center>
				<a href='lbm_cadastro.php?produto=<?echo $produto?>&btn_lista=listar' class='btn'>
					<?=traduz('Clique aqui para acessar a lista básica deste produto')?>
				</a>
			</center>
		</p>
		<p>
			<br />
			<center>
				<a href='<?echo $PHP_SELF?>?' class='btn'>
					<?=traduz('Clique aqui para localizar outro produto')?>
				</a>
			</center>
		</p>
	</div>
	<br />
<?
	}
}

if(strlen($msg_erro)==0){
	if(empty($produto) && empty($descricao)) {	
			$distinct_familia = " DISTINCT ";
	}

	$sql = "SELECT {$distinct_familia}
					tbl_peca.peca                  ,
					tbl_peca.referencia            ,
					tbl_peca.referencia_fabrica    ,
					tbl_peca.descricao             ,
					tbl_peca.origem                ,
					tbl_peca.garantia_diferenciada ,
					tbl_peca.devolucao_obrigatoria ,
					tbl_peca.item_aparencia        ,
					tbl_peca.acumular_kit          ,
					tbl_peca.retorna_conserto      ,
					tbl_peca.mao_de_obra_troca     ,
					tbl_peca.bloqueada_garantia    ,
					tbl_peca.bloqueada_venda       ,
					tbl_peca.acessorio             ,
					tbl_peca.ativo                 ,
					tbl_peca.aguarda_inspecao      ,
                    tbl_peca.peca_critica          ,
					tbl_peca.peso                  ,
					tbl_peca.peca_unica_os         ,
					tbl_peca.controla_saldo        ,
					tbl_peca.parametros_adicionais
			FROM	tbl_peca ";

	if ($btn_acao == 'listar_por_produto') {		
		if($login_fabrica == 151){			
			if (strlen($_POST["familia"]) > 0)  $x_familia  = $_POST["familia"];
			else $x_familia  = $_GET["familia"];

			if(strlen($x_familia) != 0) {
				$join_familia = " JOIN tbl_peca_familia ON tbl_peca_familia.peca = tbl_peca.peca AND tbl_peca_familia.fabrica = {$login_fabrica}
								  JOIN tbl_familia_peca ON tbl_familia_peca.familia_peca = tbl_peca_familia.familia_peca AND tbl_familia_peca.fabrica = {$login_fabrica} ";	
				$cond_familia = " AND tbl_familia_peca.familia_peca = {$x_familia} ";
			} 
		}

		if(!empty($produto) && !empty($descricao)){			
			$sql .= "JOIN tbl_lista_basica   ON tbl_lista_basica.peca    = tbl_peca.peca
											AND tbl_lista_basica.fabrica = $login_fabrica
					JOIN  tbl_produto        ON tbl_produto.produto      = tbl_lista_basica.produto
											AND tbl_produto.produto      = $produto 
					$join_familia";
		} else if(empty($produto) && empty($descricao)) {	
			$distinct_familia = " DISTINCT ";
			$sql .= "$join_familia";
		}

		if (empty($produto)) {
			$produto = 'null';
		}

	}
	if ($login_fabrica <> 11 and $login_fabrica <> 172) {
		$sql .= "WHERE tbl_peca.fabrica = $login_fabrica
					{$cond_familia}
			ORDER BY tbl_peca.descricao ";
	} else {
		/* hd 53402 - não mostrar produtos */
		$sql .= "WHERE tbl_peca.fabrica = $login_fabrica
				 AND tbl_peca.produto_acabado IS NOT TRUE
				 ORDER BY tbl_peca.referencia ";
	}

	if ($btn_acao == 'listar_por_produto') {
		//die(nl2br($sql));
		$res = pg_exec ($con,$sql);
	}

	$qtde = pg_num_rows($res); 

    if (in_array($login_fabrica,array(151,35)) OR $telecontrol_distrib) {
        $resExcel = pg_query($con,$sql);
    }

	if ($btn_acao <> 'listar_por_produto' OR $qtde > 100 ) {
		// ##### PAGINACAO ##### //
		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";

		require "_class_paginacao.php";

		// definicoes de variaveis
		$max_links = 11;				// máximo de links à serem exibidos
		$max_res   = 100;				// máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
		// ##### PAGINACAO ##### //
	}


	//die(nl2br($sql));

    if (pg_numrows($res) == 0) {
        echo "<p> <div class='alert'><h4>".traduz("Nenhum resultado encontrado").".</h4></div> </p>";
    } else {
        if (in_array($login_fabrica,array(151,35)) OR $telecontrol_distrib) {
            $data = date("d-m-Y-H:i");
            $fileName = "pecas-{$login_fabrica}-{$data}.csv";
            $thead = "REFERENCIA;DESCRICAO;ORIGEM;GAR.DIFERENCIADA;DEV.OBRIGATORIA;ITEM APARENCIA;ACUMULADA KIT;RET.CONSERTO;BLOC.GARANTIA;ACESSORIO;AG.INSPECAO;CRITICA;ATIVO\r\n";

            $file = fopen("/tmp/{$fileName}", "w");
            fwrite($file, $thead);
        }
?>
</div>
		<table id="acerto_pecas" border='0' class='table table-striped table-bordered table-hover table-large' cellpadding='2' cellspacing='1' align='center'>
		<thead>
		<tr class="titulo_tabela"> 
		<?php if (in_array($login_fabrica, array(171))){ ?>
			<th> 
				<?=traduz('Referência de FN')?>
			</th>
		<? 	} ?>
			<th>
				<?php
					if (in_array($login_fabrica, array(171))){
						echo traduz('Referência Grohe');
					}else{
						echo traduz('Referência');
					}
				?>
			</th>
			<th><?=traduz('Descrição')?></th>
			<th><?=traduz('Origem')?></th>
			<th><?=($login_fabrica == 1) ? "Desgaste<br />(Meses)" : "Garantia<br>Diferenciada"?></th>

			<?php if(in_array($login_fabrica,array(88,120,201))){	?>
				<th><?=traduz('Peso')?></th>
			<?php }	?>

			<?php if(!in_array($login_fabrica,array(179))){	?>
				<th><?=traduz('Devolução')?><br><?=traduz('Obrigatória')?></th>
			<?php }	?>
			<th><?=traduz('Item de')?><br><?=traduz('Aparência')?></th>
			<?php if(!in_array($login_fabrica,array(179))){	?>
			<th><?=traduz('Peça acumulada')?><br><?=traduz('para kit')?></th>
			<?php }	?>
			<?php if ($login_fabrica == 3) {?>
				<th><?=traduz('Intervenção')?><br><?=traduz('Técnica')?></th>
			<?php }elseif(!in_array($login_fabrica,array(179))){ ?>
				<th><?=traduz('Peça retorna')?><br><?=traduz('para conserto')?></th>
			<?php } ?>

			<?php if ($login_fabrica == 14) {?>
				<th><?=traduz('Mão-de-obra')?><br><?=traduz('Diferenciada (TROCA)')?></th>
			<?php } ?>

			<th><?=traduz('Bloqueada')?><br><?=traduz('para garantia')?></th>

			<?php if(in_array($login_fabrica,array(151,153,179,183))){?>
			<th><?=traduz('Bloqueada')?><br><?=traduz('para venda')?></th>
			<?php } ?>
			<?php if(in_array($login_fabrica,array(190))){?>
			<th><?=traduz('Consumiveis')?></th>
			<?php } ?>
			<th><?=traduz('Acessório')?></th>
			<?php if(!in_array($login_fabrica,array(179))){	?>
			<th><?=traduz('Aguarda')?><br><?=traduz('Inspeção')?></th>
			<?php } ?>
			<th><?=traduz('Peça')?><br><?=traduz('Crítica')?></th>

			<?
			if ($login_fabrica == 11 or $login_fabrica == 172) {
			?>
				<th><nobr><?=traduz('Peça crítica')?></nobr><br><nobr><?=traduz('única na OS')?></nobr></th>
			<?
			}

			if ($login_fabrica == 74) { ?>
				<th><?=traduz('Controla')?><br><?=traduz('Saldo')?></th>
			<?
			}

			if ($login_fabrica == 138) {
			?>
				<th><?=traduz('Compressor')?></th>
				<th><?=traduz('Serpentina')?></th>
			<?php
			}
			?>

			<th><?=traduz('Ativo')?></th>

			</tr>
		</thead>

		<script type="text/javascript" src="../admin/js/jquery.maskmoney.js"></script>

		<script>
		$().ready(function() {
			$('#devolucao_obrigatoria').click(function(){
				if($('#devolucao_obrigatoria').is(':checked')){
					$('.res_devolucao_obrigatoria').prop('checked', true);
				}else{
					$('.res_devolucao_obrigatoria').prop('checked', false);
				}
			});

			$('#item_aparencia').click(function(){
				if($('#item_aparencia').is(':checked')){
					$('.res_item_aparencia').prop('checked',true);
				}else{
					$('.res_item_aparencia').prop('checked',false);
				}

			});

			$('#acumular_kit').click(function(){
				if($('#acumular_kit').is(':checked')){
					$('.res_acumular_kit').prop('checked',true);
				}else{
					$('.res_acumular_kit').prop('checked',false);
				}

			});

			$('#retorna_conserto').click(function(){
				if($('#retorna_conserto').is(':checked')){
					$('.res_retorna_conserto').prop('checked',true);
				}else{
					$('.res_retorna_conserto').prop('checked',false);
				}

			});

			$('#bloqueada_garantia').click(function(){
				if($('#bloqueada_garantia').is(':checked')){
					$('.res_bloqueada_garantia').prop('checked',true);
				}else{
					$('.res_bloqueada_garantia').prop('checked',false);
				}

			});

			$('#consumivel').click(function(){
				if($('#consumivel').is(':checked')){
					$('.res_consumiveis').prop('checked',true);
				}else{
					$('.res_consumiveis').prop('checked',false);
				}

			});


			$('#bloqueada_venda').click(function(){
				if ($('#bloqueada_venda').is(':checked')) {
					$('.res_bloqueada_venda').prop('checked',true);
				} else {
					$('.res_bloqueada_venda').prop('checked',false);
				}

			});

			$('#acessorio').click(function(){
				if($('#acessorio').is(':checked')){
					$('.res_acessorio').prop('checked',true);
				}else{
					$('.res_acessorio').prop('checked',false);
				}

			});

			$('#aguarda_inspecao').click(function(){
				if($('#aguarda_inspecao').is(':checked')){
					$('.res_aguarda_inspecao').prop('checked',true);
				}else{
					$('.res_aguarda_inspecao').prop('checked',false);
				}

			});

			$('#peca_critica').click(function(){
				if($('#peca_critica').is(':checked')){
					$('.res_peca_critica').prop('checked',true);
				}else{
					$('.res_peca_critica').prop('checked',false);
				}

			});

			$('#controla_saldo').click(function(){
				if($('#controla_saldo').is(':checked')){
					$('.res_controla_saldo').prop('checked',true);
				}else{
					$('.res_controla_saldo').prop('checked',false);
				}

			});

			$('#peca_critica_os').click(function(){
				if($('#peca_critica_os').is(':checked')){
					$('.res_peca_critica_os').prop('checked',true);
				}else{
					$('.res_peca_critica_os').prop('checked',false);
				}

			});

			<?php
			if ($login_fabrica == 138) {
			?>
				$('#compressor').click(function(){
					if($('#compressor').is(':checked')){
						$('.res_compressor').prop('checked',true);
						$('.res_serpentina').prop('checked',false);
						$('#serpentina').removeAttr('checked');
					}else{
						$('.res_compressor').prop('checked',false);
					}

				});

				$('#serpentina').click(function(){
					if($('#serpentina').is(':checked')){
						$('.res_serpentina').prop('checked',true);
						$('.res_compressor').prop('checked',false);
						$('#compressor').removeAttr('checked');
					}else{
						$('.res_serpentina').prop('checked',false);
					}

				});


					$('input[name^=serpentina_],input[name^=compressor_]').click(function(){
						var contador 	= $(this).attr('rel');
						var nome 		= $(this).attr('name');

						if(nome == 'compressor_'+contador){
							$('input[name=serpentina_'+contador+']').removeAttr('checked');
						}else{
							$('input[name=compressor_'+contador+']').removeAttr('checked');
						}
					});
			<?php
			}
			?>

			$('#ativo').click(function(){
				if($('#ativo').is(':checked')){
					$('.res_ativo').prop('checked',true);
				}else{
					$('.res_ativo').prop('checked',false);
				}

			});

	        $('#garantia_diferenciada').change(function(){
	            var campo_origem = $('#garantia_diferenciada').val();
	            if(campo_origem !=''){
	                $('.res_garantia_diferenciada').val(campo_origem);
	            }else{
	                $('.res_garantia_diferenciada').val('');
	            }
	        });

			<?php if(in_array($login_fabrica,array(88,120,201))){	?>
		        $("input[id*=peso]").css("text-align","right");
		        $("input[id*=peso]").maskMoney({
		            symbol:"",
		            decimal:",",
		            thousands:'.',
		            precision:3,
		            maxlength: 15
		        });

			<?php }	?>

			$('#origem').change(function(){
				var campo_origem = $('#origem').val();
				$(".res_origem").val(campo_origem);
			});


			$('#demo10').click(function() {
				$.blockUI({
					message: '<h4>Aguarde ... Gravando Dados</h4>'
				});
			});


			$(document).keydown(function (e) {
				if(e.which == 27){
					$.unblockUI();
				}
			});
		});
		</script>

		<tbody>

		<tr class="titulo_coluna">
			<?php if (in_array($login_fabrica, array(171))){
				echo '<td><b>&nbsp;</b></td>';
			}?>
			<td><b>&nbsp;</b></td>
			<td><b>&nbsp;</b></td>
			<td>
				<select form="frm_peca" id='origem' style="width: 120px;" />
					<option value='NAC'> <?=traduz('Fabricação')?> </option>
					<option value='IMP'> <?=traduz('Importado')?> </option>
					<option value='TER'> <?=traduz('Terceiros')?> </option>
				</select>
			</td>
            <td>
                <input form="frm_peca" style="width: 40px;" type="text" id="garantia_diferenciada" maxlength="3" >
            </td>

			<?php if(in_array($login_fabrica,array(88,120,201))){	?>
				<td><!--<input class='frm' type="text" value="" size="3" id="peso"> -->&nbsp;</td>
			<?php }	?>

			<?php if(!in_array($login_fabrica,array(179))){	?>
			<td>
				<label class='checkbox' >
					<input form="frm_peca" type='checkbox' id="devolucao_obrigatoria" value='t'>
				</label>
			</td>
			<?php }	?>
			<td>
				<label class='checkbox' >
					<input form="frm_peca" type='checkbox' id="item_aparencia" value='t'>
				</label>
			</td>
			<?php if(!in_array($login_fabrica,array(179))){	?>
			<td>
				<label class='checkbox' >
					<input form="frm_peca" type='checkbox' id="acumular_kit" value='t'>
				</label>
			</td>
			<?php }	?>
			<?php if(!in_array($login_fabrica,array(179))){	?>
			<td>
				<label class='checkbox' >
					<input form="frm_peca" type='checkbox' id="retorna_conserto" value='t'>
				</label>
			</td>
			<?php }	?>
			<?php if ($login_fabrica == 14) {?>
				<td><b><?=traduz('Mão-de-obra')?><br><?=traduz('Diferenciada (TROCA)')?></b></td>
			<?php } ?>

			<td>
				<label class='checkbox' >
					<input form="frm_peca" type='checkbox' id="bloqueada_garantia" value='t'>
				</label>
			</td>
		        <?php if(in_array($login_fabrica,array(151,153,179,183))){?>			
			<td>
				<label class='checkbox' >
					<input form="frm_peca" type='checkbox' id="bloqueada_venda" value='t'>
				</label>
			</td>
			<?php }?>

			 <?php if(in_array($login_fabrica,array(190))){?>			
			<td>
				<label class='checkbox' >
					<input form="frm_peca" type='checkbox' id="consumivel" value='t'>
				</label>
			</td>
			<?php }?>


			<td>
				<label class='checkbox' >
					<input form="frm_peca" type='checkbox' id="acessorio" value='t'>
				</label>
			</td>
			<?php if(!in_array($login_fabrica,array(179))){	?>
			<td>
				<label class='checkbox' >
					<input form="frm_peca" type='checkbox' id="aguarda_inspecao" value='t'>
				</label>
			</td>
			<?php }	?>
			<td>
				<label class='checkbox' >
					<input form="frm_peca" type='checkbox' id="peca_critica" value='t'>
				</label>
			</td>

			<?php if ($login_fabrica == 11 or $login_fabrica == 172) { ?>
				<td>
					<label class='checkbox'>
						<input form="frm_peca" type='checkbox' id="peca_critica_os" value='t'>
					</label>
				</td>
			<?php } ?>

			<?php if ($login_fabrica == 74) { ?>
				<td>
					<label class='checkbox'>
						<input form="frm_peca" type='checkbox' id="controla_saldo" value='t'>
					</label>
				</td>
			<?php }

			if ($login_fabrica == 138) {
			?>
				<td>
					<label class='checkbox'>
						<input form="frm_peca" type='checkbox' id="compressor" value='t'>
					</label>
				</td>
				<td>
					<label class='checkbox'>
						<input form="frm_peca" type='checkbox' id="serpentina" value='t'>
					</label>
				</td>
			<?php
			}
			?>

			<td>
				<label class='checkbox'>
					<input form="frm_peca" type='checkbox' id="ativo" value='t'>
				</label>
			</td>
		</tr>

<?php
            for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
                $cor = ($i % 2 == 0) ? "#F7F5F0": '#F1F4FA';
                $peso = pg_result ($res,$i,peso);

                $parametros_adicionais = pg_fetch_result($res, $i, "parametros_adicionais");
                $parametros_adicionais = json_decode($parametros_adicionais);

?>

					<tr bgcolor='<? echo $cor; ?>'>

						<?php if (in_array($login_fabrica,array(171))) {?>
						<td nowrap align='left'><? echo pg_result($res,$i,referencia_fabrica); ?></td>
						<?php }	?>
						<td nowrap align='left'>
							<input form="frm_peca" type="hidden" name="peca_<? echo $i; ?>" value="<? echo pg_result($res,$i,peca); ?>">
							<?php echo pg_result($res,$i,referencia); ?>
						</td>
						<td nowrap align='left'><? echo pg_result($res,$i,descricao); ?></td>
						<td nowrap>
							<select form="frm_peca" class='res_origem' style="width: 120px;" name='origem_<? echo $i; ?>' >
								<option value='NAC' <? if (pg_result($res,$i,origem) == 'NAC' OR pg_result($res,$i,origem) == 1) echo "selected" ?>> Fabricação </option>
								<option value='IMP' <? if (pg_result($res,$i,origem) == 'IMP' OR pg_result($res,$i,origem) == 2) echo "selected" ?>> Importado </option>
								<option value='TER' <? if (pg_result($res,$i,origem) == 'TER') echo "selected" ?>> Terceiros </option>
							</select>
						</td>
			            <td nowrap align='center'>
			            	<input form="frm_peca" style="width: 40px;" type="text" name="garantia_diferenciada_<? echo $i; ?>" value="<? echo pg_result ($res,$i,garantia_diferenciada); ?>"  maxlength="3" class="res_garantia_diferenciada">
			            </td>

						<?php if(in_array($login_fabrica,array(88,120,201))){	?>
							<td nowrap align='center'><input form="frm_peca" class='frm res_peso' type="text" name="peso_<? echo $i; ?>" value="<? echo number_format($peso,3,',',''); ?>" size="3" id="res_peso"></td>
						<?php }	?>

						<?php if(!in_array($login_fabrica,array(179))){	?>
						<td nowrap align='center'>
							<label class='checkbox'>
								<input form="frm_peca" class='res_devolucao_obrigatoria' type='checkbox' name='devolucao_obrigatoria_<? echo $i; ?>' value='t' <? if (pg_result($res,$i,devolucao_obrigatoria) == 't' ) echo "checked" ?>>
							</label>
						</td>
						<?php } ?>

						<td nowrap align='center'>
							<label class='checkbox' for='item_aparencia'>
							<input form="frm_peca" class='res_item_aparencia' type='checkbox' name='item_aparencia_<? echo $i; ?>'        value='t' <? if (pg_result($res,$i,item_aparencia) == 't' )        echo "checked" ?>>
						</td>

						<?php if(!in_array($login_fabrica,array(179))){	?>
						<td nowrap align='center'>
							<label class='checkbox'>
								<input form="frm_peca" class='res_acumular_kit' type='checkbox' name='acumular_kit_<? echo $i; ?>'          value='t' <? if (pg_result($res,$i,acumular_kit) == 't' )          echo "checked" ?>>
							</label>
						</td>
						<?php } ?>

						<?php if(!in_array($login_fabrica,array(179))){	?>
						<td nowrap align='center'>
							<label class='checkbox'>
								<input form="frm_peca" class='res_retorna_conserto' type='checkbox' name='retorna_conserto_<? echo $i; ?>'      value='t' <? if (pg_result($res,$i,retorna_conserto) == 't' )      echo "checked" ?>>
							</label>
						</td>
						<?php } ?>

						<?php if ($login_fabrica == 14) {?>
							<td nowrap align='center'><input form="frm_peca" class='frm' type="text" name="mao_de_obra_diferenciada" value="<? echo $mao_de_obra_diferenciada ?>" size="5" maxlength="10"></td>
						<?php } ?>

						<td nowrap align='center'>
							<label class='checkbox'>
								<input form="frm_peca" class='res_bloqueada_garantia' type='checkbox' name='bloqueada_garantia_<? echo $i; ?>'    value='t' <? if (pg_result($res,$i,bloqueada_garantia) == 't' )    echo "checked" ?>>
							</label>
						</td>
						<?php if(in_array($login_fabrica,array(151,153,179,183))){?>
						<td nowrap align='center'>
							<label class='checkbox'>
								<input form="frm_peca" class='res_bloqueada_venda' type='checkbox' name='bloqueada_venda_<? echo $i; ?>'    value='t' <? if (pg_result($res,$i,bloqueada_venda) == 't' )    echo "checked" ?>>
							</label>
						</td>
						<?php } ?>


						<?php if(in_array($login_fabrica,array(190))){?>
						<td nowrap align='center'>
							<label class='checkbox'>
								<input form="frm_peca" class='res_consumiveis' type='checkbox' name='consumiveis_<? echo $i; ?>' value='t' <?=($parametros_adicionais->consumiveis == "t") ? "checked" : ""?> >
							</label>
						</td>
						<?php } ?>





						<td nowrap align='center'>
							<label class='checkbox'>
								<input form="frm_peca" class='res_acessorio' type='checkbox' name='acessorio_<? echo $i; ?>'             value='t' <? if (pg_result($res,$i,acessorio) == 't' )             echo "checked" ?>>
							</label>
						</td>

						<?php if(!in_array($login_fabrica,array(179))){	?>
						<td nowrap align='center'>
							<label class='checkbox'>
								<input form="frm_peca" class='res_aguarda_inspecao' type='checkbox' name='aguarda_inspecao_<? echo $i; ?>'      value='t' <? if (pg_result($res,$i,aguarda_inspecao) == 't' )      echo "checked" ?>>
							</label>
						</td>
						<?php } ?>
						<td nowrap align='center'>
							<label class='checkbox'>
								<input form="frm_peca" class='res_peca_critica' type='checkbox' name='peca_critica<? echo $i; ?>'           value='t' <? if (pg_result($res,$i,peca_critica) == 't' )          echo "checked" ?>>
							</label>
						</td>

						<?php if ($login_fabrica==11 or $login_fabrica == 172) {?>
							<td nowrap>
								<label class='checkbox'>
								<input form="frm_peca" class='res_peca_critica_os' type='checkbox' name='peca_unica_os<? echo $i; ?>'           value='t' <? if (pg_result($res,$i,peca_unica_os) == 't' )          echo "checked" ?>>
								</label>
							</td>
						<?php }?>

						<?php if ($login_fabrica==74) {?>
							<td align='center'>
								<label class='checkbox'>
								<input form="frm_peca" class='res_controla_saldo' type='checkbox' name='controla_saldo<? echo $i; ?>'           value='t' <? if (pg_result($res,$i,controla_saldo) == 't' )          echo "checked" ?>>
								</label>
							</td>
						<?php }

						if ($login_fabrica == 138) {
						?>
							<td nowrap align='center'>
								<label class='checkbox'>
									<input form="frm_peca" class='res_compressor' type='checkbox' rel='<?=$i?>' name='compressor_<?=$i?>' value='t' <?=($parametros_adicionais->tipo_peca == "compressor") ? "checked" : ""?> />
								</label>
							</td>
							<td nowrap align='center'>
								<label class='checkbox'>
									<input form="frm_peca" class='res_serpentina' type='checkbox' rel="<?=$i?>" name='serpentina_<?=$i?>' value='t' <?=($parametros_adicionais->tipo_peca == "serpentina") ? "checked" : ""?> />
								</label>
							</td>
						<?php
						}
						?>

						<td nowrap align='center'>
							<label class='checkbox'>
							<input form="frm_peca" class='res_ativo' type='checkbox' name='ativo_<? echo $i; ?>'           value='t' <? if (pg_result($res,$i,ativo) == 't' )          echo "checked" ?>>
							</label>
						</td>
					</tr>
<?php
                }
                if (in_array($login_fabrica,array(151,35)) OR $telecontrol_distrib) {
                    foreach (pg_fetch_all($resExcel) as $result) {
                        if ($result['origem'] == 'NAC') {
                            $aux = "FABRICACAO";
                        } else if ($result['origem'] == 'IMP'){
                            $aux = "IMPORTADO";
                        } else {
                            $aux = "TERCEIROS";
                        }

                        $body = utf8_encode($result['referencia']).";";
                        $body .= utf8_encode($result['descricao']).";";
                        $body .= $aux.";";
                        $body .= $result['garantia_diferenciada'].";";
                        $body .= (($result['devolucao_obrigatoria'] == 't') ? "SIM" : "").";";
                        $body .= (($result['item_aparencia'] == 't') ? "SIM" : "").";";
                        $body .= (($result['acumular_kit'] == 't') ? "SIM" : "").";";
                        $body .= (($result['retorna_conserto'] == 't') ? "SIM" : "").";";
                        $body .= (($result['bloqueada_garantia'] == 't') ? "SIM" : "").";";
                        $body .= (($result['bloqueada_venda'] == 't') ? "SIM" : "").";";
                        $body .= (($result['acessorio'] == 't') ? "SIM" : "").";";
                        $body .= (($result['aguarda_inspecao'] == 't') ? "SIM" : "").";";
                        $body .= (($result['peca_critica'] == 't') ? "SIM" : "").";";
                        $body .= (($result['ativo'] == 't') ? "SIM" : "")."\r\n";

                        fwrite($file, $body);
                    }

                    fclose($file);
                    if (file_exists("/tmp/{$fileName}")) {
                        system("mv /tmp/{$fileName} xls/{$fileName}");

                        $arquivo = "xls/{$fileName}";
                    }
                }
?>

			<input form="frm_peca" type="hidden" name="peca_qtde"           value="<? echo pg_numrows($res); ?>">
			<input form="frm_peca" type="hidden" name="pagina_atual"        value="<? echo $pagina; ?>">
			<input form="frm_peca" type='hidden' name='btn_acao' value=''>
		</tr>
    </tbody>
	</table>
		<?php if (in_array($login_fabrica,array(151,35)) OR $telecontrol_distrib) : $jsonPOSTcsv = csvPostToJson($_POST);?>
			<div id='gerar_csv' class="btn_excel" style="display:block; text-align: -webkit-center; text-align: -moz-center;">
		        <!--<input type="hidden" id="jsonPOSTcsv" value='<?=$jsonPOSTcsv?>' />
		        <span class="txt"><img src='imagens/excel.png' />Gerar Planilha</span>-->
	        	<a href="<?=$arquivo?>" role="button" class="btn btn-success"><?=traduz('Gerar Planilha')?></a>	
	    	</div>
		<?php endif; ?>
	<center>
		<br />
			<input form="frm_peca" type="button" class='btn' value='<?=traduz("Gravar")?>' onclick="javascript: if (document.frm_peca.btn_acao.value == '' ) { document.frm_peca.btn_acao.value='gravar'; document.frm_peca.submit() } else { alert ('<?=traduz("Aguarde submissão")?>');fecha_modal(); }" ALT='Gravar' id="demo10" border='0' ></center>
		<br />
	</center>

<?php
	}
}
?>

<table width='700' border='0' cellpadding='2' cellspacing='1' align='center'>
<tr>
	<td>
	<?php
	/***##### PAGINACAO #####*/
	if ($btn_acao <> 'listar_por_produto' OR $qtde  > 100)
	{
		echo "	<br>
				<div>
					<center>";

					if($pagina < $max_links)
					{
						$paginacao = pagina + 1;
					}
					else
					{
						$paginacao = pagina;
					}

					// paginacao com restricao de links da paginacao
					// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
					$todos_links = $mult_pag->Construir_Links("strings", "sim");

					// função que limita a quantidade de links no rodape
					$links_limitados = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

					for ($n = 0; $n < count($links_limitados); $n++)
					{
						echo "	<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
					}

		echo "		</center>
				</div>";

		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);
		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0)
		{
			echo "	<br />
					<div>
						<center>
							".traduz("Resultados de")." <b>$resultado_inicial</b> a <b>$resultado_final</b> ".traduz("do total de")." <b>$registros</b> ".traduz("registros").".
							<font color='#cccccc' size='1'>(".traduz("Página")." <b>$valor_pagina</b> de <b>$numero_paginas</b>)</font>
						</center>
					</div>";
		}
	} ?>

	<input form="frm_peca" type="hidden" name="numero_paginas" id="numero_paginas" value="<?php echo $numero_paginas;?>" />
	<br />
	</td>
</tr>
</table>
<form name="frm_peca" id="frm_peca" method="post" action="<? echo $PHP_SELF ?>" class="form-search form-inline tc_formulario">
</form>
<? include "rodape.php"; ?>
