<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';


$admin_privilegios="call_center";
include 'autentica_admin.php';
if($login_fabrica==6){
	include "lista_basica_consulta.php";
	exit;
}
include_once "../class/tdocs.class.php";
$tDocs  = new TDocs($con, $login_fabrica);
if ($S3_sdk_OK) {
	include_once S3CLASS;
	$s3 = new anexaS3('ve', (int) $login_fabrica);
	$S3_online = is_object($s3);
}
$layout_menu = "callcenter";
$title = traduz("CONSULTA DE LISTA BÁSICA");
include 'cabecalho_new.php';

$plugins = array(
	"shadowbox",
	"autocomplete",
	"dataTable"
);

include("plugin_loader.php");

$qtde_linhas = 450 ;
$msg_erro = "";

$btn_acao = trim (strtolower ($_POST['btn_acao']));
$lbm      = trim (strtolower ($_POST['lbm']));

if (strlen ($_POST['btn_lista']) > 0) {//se o botão foi clicado

	$referencia = $_POST['produto_referencia'];
	$msg_erro = "";
	$erro = false;

	if($login_fabrica == 72){

		$produto_linha = $_POST['produto_linha'];
		$produto_familia = $_POST['produto_familia'];

		if ((strlen ($referencia) == 0) && (strlen ($produto_familia) == 0) && (strlen ($produto_linha) == 0)){
			$msg_erro = traduz("Informe a referência, linha ou família do produto");
		}
		$erro = true;
	}else{

		if (strlen ($referencia) == 0) {
			$msg_erro = traduz("Preencha a referência do produto");
			$erro = true;
		}
	}

	if(!$erro){
		$sql = "SELECT produto FROM tbl_produto JOIN tbl_linha USING(linha) WHERE referencia = '$referencia' and fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) == 0){
			$msg_erro = traduz("Produto não encontrado");
			$erro = true;
		}
	}	
}

if (!empty($_GET['produto'])) {
    $produto = (int) $_GET['produto'];

	$sql = "SELECT produto FROM tbl_produto JOIN tbl_linha USING(linha) WHERE produto = $produto and fabrica = $login_fabrica";
    $res = pg_query ($con,$sql);
	if (pg_num_rows ($res) == 0){
		$msg_erro = traduz("Produto não encontrado");
	}
}

if($login_fabrica == 72){

	$sql = "SELECT linha, nome FROM tbl_linha WHERE fabrica = {$login_fabrica} AND ativo IS TRUE;";
	$res = pg_query ($con,$sql);
	$linhas = pg_fetch_all($res);

	$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$login_fabrica} AND ativo IS TRUE;";
	$res = pg_query ($con,$sql);
	$familias = pg_fetch_all($res);
}

?>
<center>

<script>

	<?php //ordena resultados de acordo com a coluna clicada ?>
	function fnc_ordena_results(){
		$("#relatorio").tablesorter();
	}


	function fnc_pesquisa_peca (campo, campo2, tipo) {
		if (tipo == "referencia" ) {
			var xcampo = campo;
		}

		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {
			var url = "";
			url = "peca_pesquisa_2_teste_mlg.php?campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
			janela.referencia	= campo;
			janela.descricao	= campo2;
			janela.focus();
		}

		else{
			alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
		}
	}

	function fnc_impresssao(produto) {
		var url = "";
		url = "lbm_cadastro_impressao.php?produto="+produto;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=920, height=500, top=18, left=0");
		janela.focus();
	}

	function pesquisaProduto(campo,tipo){

	    if (jQuery.trim(campo.value).length > 2){
	        Shadowbox.open({
	            content:	"produto_pesquisa_2_nv.php?"+tipo+"="+campo.value,
	            player:	    "iframe",
	            title:		'<?=traduz("Pesquisa Produto")?>',
	            width:	    800,
	            height:	    500
	        });
	    }else{
	        alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
	        campo.focus();
	    }
	}

	function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria, posicao){
	    gravaDados('referencia',referencia);
	    gravaDados('descricao',descricao);
	}

	function gravaDados(name, valor){
	    try{
	        $("input[name="+name+"]").val(valor);
	    } catch(err){
	        return false;
	    }
	}

	$(document).ready(function() {
	    Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$.autocompleteLoad(Array("produto"));

	});

	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

</script>
<body>
<?php if(strlen($msg_erro)>0){ ?>
	<div class="alert alert-danger"><h4><?php echo $msg_erro; ?></h4></div>
<?php } ?>
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<form name="frm_lbm" method="post" action="<?=$PHP_SELF ?>" class='form-search form-inline tc_formulario'>
	<?
	$referencia = $_POST['produto_referencia'];
	if (strlen ($referencia) > 0) {
		$sql = "SELECT produto, descricao FROM tbl_produto JOIN tbl_linha USING(linha) WHERE referencia = '$referencia' and fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) == 0) {
			$msg_erro  = traduz("Produto não Encontrado");
			$descricao = "";
			$produto   = "";
		}else{
			$descricao = pg_fetch_result ($res,0,descricao);
			$produto   = pg_fetch_result ($res,0,produto);
		}
    } elseif (!empty($produto)) {
        $qryProduto = pg_query($con, "SELECT referencia, descricao , referencia_fabrica FROM tbl_produto WHERE produto = $produto");
        $referencia = pg_fetch_result($qryProduto, 0, 'referencia');
        $referencia_fabrica = pg_fetch_result($qryProduto, 0, 'referencia_fabrica');
        $descricao = pg_fetch_result($qryProduto, 0, 'descricao');
    }
	?>

	<div class="titulo_tabela"><?=traduz('Parâmetros de Pesquisa')?></div>
	<br />
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<div class='control-group <?= (strlen($msg_erro) > 0) ? "error" : "" ?>'>
				<label class='control-label'><?=traduz('Referência Produto')?></label>
					<div class='controls controls-row input-append'>
						<? if($login_fabrica != 72) : ?><h5 class='asteristico'>*</h5><?endif;?>
						<input type="text" id="produto_referencia" name="produto_referencia" value="<? echo $referencia ?>" size="13" maxlength="20" class="frm"><span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
					</div>		
				</div>
			</div>
			<div class="span4">
				<div class='control-group <?= (strlen($msg_erro) > 0) ? "error" : "" ?>'>
					<label class='control-label'><?=traduz('Descrição Produto')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<? if($login_fabrica != 72) : ?><h5 class='asteristico'>*</h5><?endif;?>
							<input type="text" id="produto_descricao" name="produto_descricao" value="<? echo $descricao ?>" size="40" maxlength="50" class="frm">
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>	
					</div>
				</div>	
			</div>
			<? if($login_fabrica == 72) : ?>
			</div>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span4">
					<div class='control-group <?= (strlen($msg_erro) > 0) ? "error" : "" ?>'>
						<label for="produto_linha" class='control-label'><?=traduz('Linha')?></label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<select name="produto_linha" id="produto_linha" style="width:90%">
								<option></option>
									<?foreach($linhas as $linha):?>
										<?if($linha['linha'] == $produto_linha):?>
											<option selected value="<? echo $linha['linha'] ?>"><? echo $linha['nome'] ?></option>
										<?else:?>
											<option value="<? echo $linha['linha'] ?>"><? echo $linha['nome'] ?></option>
										<?endif;?>
									<?endforeach;?>
								</select>
							</div>	
						</div>
					</div>	
				</div>
				<div class="span4">
					<div class='control-group <?= (strlen($msg_erro) > 0) ? "error" : "" ?>'>
						<label for="produto_familia" class='control-label'><?=traduz('Família')?></label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<select name="produto_familia" id="produto_familia" style="width:90%">
									<option></option>
									<?foreach($familias as $familia):?>
										<?if($familia['familia'] == $produto_familia):?>
											<option selected value="<? echo $familia['familia'] ?>"><? echo $familia['descricao'] ?></option>
										<?else:?>
											<option value="<? echo $familia['familia'] ?>"><? echo $familia['descricao'] ?></option>
										<?endif;?>
									<?endforeach;?>
								</select>
							</div>	
						</div>
					</div>	
				</div>
			<? endif ?>
			<input type='hidden' name='btn_lista' value=''>
			<div class="span2"></div>
		</div>
		<?php
		if ($login_fabrica == 1 && !empty($produto)) { 
			$sql_descontinuado = "
						SELECT parametros_adicionais
						FROM tbl_produto 
						WHERE fabrica_i = $login_fabrica
						AND tbl_produto.produto = $produto";
			$res_descontinuado = pg_query($con, $sql_descontinuado);

			$parametros_adicionais = json_decode(pg_fetch_result($res_descontinuado, 0, 'parametros_adicionais'), true);
			$data_descontinuado = $parametros_adicionais['data_descontinuado'];

			if (!empty($data_descontinuado)) {
			?>
				<div class="tac" style="width: 100%;">
					<span style="color: darkred;"><?=traduz('Data de Descontinuação')?>: <span style="font-size: 15px;font-weight: bolder;"><?= $data_descontinuado ?></span></span>
				</div>
			<?php
			}
		}
		?>
		<br />
		<div class="row-fluid">
			<center>
				<input class="btn" type="button" style="cursor:pointer;" value="<?=traduz("Pesquisar")?>" onclick='document.frm_lbm.btn_lista.value="listar"; document.frm_lbm.submit();' >
			</center>
		</div>
<?
if (strlen ($produto) > 0) {
    if($login_fabrica == 1){
        $sqlBloqueio = "
            SELECT  inibir_lista_basica
            FROM    tbl_produto
            WHERE   produto = $produto
        ";
        $resBloqueio = pg_query($con,$sqlBloqueio);
        $inibe = pg_fetch_result($resBloqueio,0,inibir_lista_basica);
    }
        $sql = "Select DISTINCT comunicado,extensao
                from tbl_comunicado
                LEFT JOIN tbl_comunicado_produto USING(comunicado)
                where fabrica=$login_fabrica
                and (tbl_comunicado.produto = $produto  OR tbl_comunicado_produto.produto = $produto)
                and tipo = 'Vista Explodida'";

        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $vista_explodida = pg_fetch_result($res,0,comunicado);
            $ext             = pg_fetch_result($res,0,extensao);
        }

        if (strlen($vista_explodida) > 0) {

                $linkVE = null;
                if ($S3_online) {
                    if ($s3->temAnexos($vista_explodida))
                        $linkVE = $s3->url;
                } else {
                    if (file_exists ('../comunicados/'.$vista_explodida.'.'.$ext)) {
                        $linkVE = "../comunicados/$vista_explodida.$ext";
                    }
                }
                if ($linkVE) {

                if ($login_fabrica <> 45) {?>

                    <table align="center" width="700" class="formulario">
                        <tr>
                            <td align="center">
                                <input class="btn btn-primary" type="button" onclick="javascript: window.open('<?=$linkVE ?>')" value="<?=traduz("Ver Vista Explodida")?>">
                            </td>
                        </tr>
                        <?php if ($login_fabrica == 1) { ?>
                               <tr>
                                   <td align="center">
                                       <br />
                                       <input class="btn btn-primary" type="button" onclick="javascript: window.open('lbm_impressao.php?produto=<?=urlencode($produto)?>');" value="Download Vista Explodida">
                                   </td>
                               </tr>   
                        <?php } ?>
                    </table><?php
                }

            } else {

                if ($login_fabrica <> 45) {
                    echo "<a href='vista_explodida_cadastro.php?comunicado=$vista_explodida' target='_blank'>".traduz("Clique aqui</a> para ver a vista-explodida");
                }

            }

            if ($login_fabrica == 45) {
                echo '<br />';
                echo '<br />';
                echo "<a href='javascript:void(0)' onclick='fnc_impresssao($produto)'>".traduz("Versão para impressão")."</a>";
                echo '<br />';
                echo '<br />';
            }

        } else {
            echo "<div class='alert alert-warning' style='width: 30%;position: relative;left: 32%;'><h4>".traduz("Produto sem vista explodida")."</h4></div>";
		}
}
?>
<br />
</form>

<br>

<?
$btn_lista = $_POST['btn_lista'];

// Checagem do preenchimento dos filtros para a fábrica 72
$checkFilters = false;

if (strlen ($_POST['btn_lista']) > 0 and empty($msg_erro) and $login_fabrica == 72){

	$produto_linha = $_POST['produto_linha'];
	$produto_familia = $_POST['produto_familia'];
	$produto_referencia = $_POST['produto_referencia'];

	if(!empty($produto_linha) or !empty($produto_familia) or !empty($produto_referencia)){
		$checkFilters = true;
	}
}

if($checkFilters){

	$produto_referencia = $_POST['produto_referencia'];
    $produto_linha = $_POST['produto_linha'];
	$produto_familia = $_POST['produto_familia'];

	// Busca os produtos da respectiva linha, familia ou referência
	$sql_produtos = 
		"SELECT DISTINCT tbl_produto.produto,
						 tbl_produto.referencia,
       					 tbl_produto.descricao,
       					 tbl_produto.linha,
       					 tbl_produto.familia,
		       			 tbl_linha.nome as descricao_linha,
		       			 tbl_familia.descricao as descricao_familia
		FROM tbl_lista_basica 
		JOIN tbl_produto ON tbl_lista_basica.produto = tbl_produto.produto AND 
		                    tbl_produto.fabrica_i = $login_fabrica
        JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = tbl_produto.fabrica_i
        JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = tbl_produto.fabrica_i
		WHERE tbl_lista_basica.fabrica = $login_fabrica";

		if(!empty($produto_linha)){
		 	$sql_produtos .= " AND tbl_produto.linha = $produto_linha";
	 	}

	 	if(!empty($produto_familia)){
		 	$sql_produtos .= " AND tbl_produto.familia = $produto_familia";
	 	}

	 	if(!empty($produto_referencia)){
		 	$sql_produtos .= " AND tbl_produto.referencia = '$produto_referencia'";
	 	}

	 	$sql_produtos .= " ORDER BY tbl_produto.descricao";
		$res_produtos = pg_query ($con,$sql_produtos);

		if(pg_num_rows ($res_produtos) > 0){

			// Toda a lógica de listagem para a fábrica Mallory será feita no arquivo lbm_consulta_tabela.php
			$res_produtos = pg_fetch_all($res_produtos);
			include_once("lbm_consulta_tabela.php");
			exit;
		}else{
			echo "<div class='alert alert-warning'><h4>".traduz("Não foram Encontrados Resultados para esta Pesquisa.")."</h4></div>";
		}
}

if ((strlen ($_POST['btn_lista']) > 0 or !empty($produto)) and strlen($msg_erro) == 0 and $login_fabrica != 72){

    if (!empty($_POST['produto_referencia'])) {
        $referencia = $_POST['produto_referencia'];
    }

	if (strlen ($referencia) > 0 or !empty($produto)) {

        if (!empty($produto)) {
            $sql = "SELECT tbl_produto.produto,
                tbl_produto.troca_faturada,
				tbl_produto.troca_garantia,
				referencia_fabrica
                FROM tbl_produto JOIN tbl_linha USING(linha)
                WHERE referencia = '$referencia'
                AND fabrica = $login_fabrica";
            $res = pg_query ($con,$sql);
            if (pg_num_rows ($res) > 0) {
                $produto = pg_fetch_result ($res,0,produto);

                if($login_fabrica == 1){
                    $troca_garantia = pg_fetch_result($res, 0, troca_garantia);
                    $troca_faturada = pg_fetch_result($res, 0, troca_faturada);
                    $referencia_fabrica = pg_fetch_result($res, 0, referencia_fabrica);
                }

            }else{
                $produto = 0;
            }
        }

        if ($login_fabrica == 1) {

        	function busca_arquivo($dir, $nome) {
				if ($dirlist = glob($dir . "$nome.*")) {
					return basename($dirlist[0]);
				}
				return false;
			}
        
	        $sql_comu = "SELECT DISTINCT(comunicado), tipo
	                       FROM tbl_comunicado
	                       LEFT JOIN tbl_comunicado_produto USING(comunicado)
	                      WHERE fabrica = $login_fabrica
	                        AND (tbl_comunicado.produto = $produto  OR tbl_comunicado_produto.produto = $produto)
	                        AND tipo IN ('Foto','Esquema Elétrico','Informativo','Informativo tecnico','Manual','Manual Técnico','Vista Explodida','Alterações Técnicas') AND ativo IS TRUE";
	        $res_comu = pg_query($con,$sql_comu);

	        if (pg_num_rows($res_comu) > 0) {
                $img = [];
                for ($i = 0; $i < pg_num_rows($res_comu); $i++) {
                  $img[$i]['comunicado'] = pg_fetch_result($res_comu,$i,'comunicado');
                  $img[$i]['tipo'] = pg_fetch_result($res_comu,$i,'tipo');
                }
                $tipo = 1;
	        }

	        $destino = __DIR__ . "/comunicados/";
	        $caminho = "comunicados/";

	        $peca_abs_thumb = __DIR__ . "/imagens_pecas/$login_fabrica/pequena/";
	        $peca_rel_thumb = "imagens_pecas/$login_fabrica/pequena/";

            foreach ($img as $key => $value) {
              $imagem = "";
              $img_style = "";

              $imagem = ($S3_online and $s3->temAnexos($value['comunicado'])) ? $s3->url : $caminho . busca_arquivo($destino, $value['comunicado']);
  
              if ($imagem == $caminho) {
                continue;
              }
              
              $img_style = 'style="width: 800px; height: 600px;"';
              
            echo "<br />"; 
            echo "<h4><b>".$value['tipo']."</b></h4>"; 
            echo "<br />"; 
            echo '<center><iframe ' . $img_style . ' src="' . $imagem . '" border="0"></iframe></center>';
            }
            echo "<br />"; 
        
			if($troca_garantia == 't'){
				$troca_garantia = traduz("Troca Garantia");
			}else{
				$troca_garantia = "";
			}
			if($troca_faturada == 't'){
				$troca_faturada = traduz("Troca Faturada");
			}else{
				$troca_faturada = "";
			}
				echo "<div><strong style='font-size:14px;'>$troca_faturada</strong></div>";
				echo "<div><strong style='font-size:14px;'>$troca_garantia</strong></div>";
		}

		$sql = "SELECT  tbl_lista_basica.lista_basica                        ,
                        tbl_lista_basica.posicao                             ,
                        tbl_lista_basica.ordem                               ,
                        tbl_lista_basica.qtde                                ,
                        tbl_peca.referencia_fabrica                          ,
                        tbl_peca.referencia                                  ,
                        tbl_peca.descricao                                   ,
                        tbl_peca.peca                                        ,
                        tbl_peca.garantia_diferenciada   AS desgaste         ,
                        tbl_peca.informacoes					,
                        tbl_lista_basica.serie_inicial                       ,
                        tbl_lista_basica.serie_final,
                        tbl_lista_basica.type
                FROM    tbl_lista_basica
                JOIN    tbl_peca USING (peca)
                WHERE   tbl_lista_basica.fabrica = $login_fabrica
                AND     tbl_lista_basica.produto = $produto ";
        if ($login_fabrica == 45) {
            $sql .= " ORDER BY tbl_lista_basica.posicao";
        } else {
            $sql .= " ORDER BY tbl_peca.referencia, tbl_peca.descricao";
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
			if($login_fabrica == 1){
				echo "<div style='width: 200px;height: 80px; border-radius:5px' class='form-search form-inline tc_formulario'><br>
						<label class='control-label'>".traduz('Referência Interna')."</label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<h5 class='asteristico'>*</h5>
								<input type='text' value='$referencia_fabrica' style='width:150px;' class='frm' disabled>
							</div>	
						</div>
					</div><br>";


				echo "<TABLE class='table table-bordered table-fixed'>";
				echo "<thead>";
				echo "<tr class='titulo_coluna'><td colspan='5' style='font-size:16px; text-align:center;'>".traduz('Kits Cadastrados')."</td>";
				echo "</tr>";
				echo "</thead>";
				echo "<tr class='titulo_coluna'>";
				echo "<th></th>";
				echo "<th>".traduz('Código do Produto')."</th>";
				echo "<th>".traduz('Nome do Produto')."</th>";
				echo "<th>".traduz('Voltagem')."</th>";
				echo "<th>".traduz('Kit')."</th></tr><tbody>";
				$sql_produto_trocado = "SELECT tbl_produto.descricao, tbl_produto.referencia,
										tbl_produto.voltagem, tbl_produto_troca_opcao.kit
										from tbl_produto_troca_opcao 
										join tbl_produto on tbl_produto.produto = tbl_produto_troca_opcao.produto_opcao 
										where tbl_produto_troca_opcao.produto = $produto";

				$res_produto_trocado = pg_query($con, $sql_produto_trocado);

				if(pg_num_rows($res_produto_trocado) > 0){
					for($pr = 0; $pr < pg_num_rows($res_produto_trocado); $pr++){
						$codigo    = pg_fetch_result($res_produto_trocado, $pr, 'referencia');
						$descricao = pg_fetch_result($res_produto_trocado, $pr, 'descricao');
						$voltagem  = pg_fetch_result($res_produto_trocado, $pr, 'voltagem');
						$kit       = pg_fetch_result($res_produto_trocado, $pr, 'kit');
						echo "<td>".($pr + 1)."</td>";
						echo "<td>".$codigo."</td>";
						echo "<td>".$descricao."</td>";
						echo "<td>".$voltagem."</td>";
						echo "<td>".$kit."</td>";
					}
				}else{
					echo "<td colspan='5'>Nenhum registro encotrado</td>";
				}
				
				echo "</tbody></table>";
				echo "<br><br>";
			}
			echo "<table border='0' width='700'>";
			echo "<tr style='font-size:12px; text-align:left;'>";
			echo "<td bgcolor='#91C8FF' width='20' nowrap>&nbsp;</td><td nowrap> ".traduz("Alternativa")."</td>";
			echo "</tr>";
			echo "<tr style='font-size:12px; text-align:left;'>";
			echo "<td bgcolor='#E8C023' width='20' nowrap>&nbsp;</td><td nowrap> ".traduz("De-Para")."</td>";
			echo "</tr>";
			echo "</table>";


			echo "<br>";
			//HD 347523 Inicio 1 - MLG - Mudei o JS para ordenar a tabela pelo que uso na tela de Consultas.
			echo "<TABLE id='tabela_produtos' class='table table-bordered table-fixed'>";
			echo "<thead>";
			echo "<tr class='titulo_coluna'>";

			//hd-1115325 Lenoxx - ordenação de resultados quando clicado na coluna
			if($login_fabrica ==11 or $login_fabrica == 172){
				echo "<th>".traduz("Posição")."</th>";
			}else{
				echo "<th>";
				// HD38821
				if ($login_fabrica == 3) {
					echo traduz("Localização");
				} else if ($login_fabrica == 1) { /*HD-4074490*/
					echo traduz("Ordem");
				} else {
					echo traduz("Posição");
				}
				echo "</th>";
			}
			
			if(!in_array($login_fabrica,array(1, 152,180,181,182))){
				echo "<th>".traduz("Série Inicial")."</th>";
				echo "<th>".traduz("Série Final")."</th>";
				//echo "<th><h3>Peça</h3></th>";
			}
			if ($login_fabrica == 1) {
				echo "<th>".traduz("Status")."</th>";				
			}

			//hd-1115325 Lenoxx - ordenação de resultados quando clicado na coluna
			if($login_fabrica == 11 or $login_fabrica == 172){
				echo "<th>".traduz("Peça")."</th>";
				echo "<th>".traduz("Referência")."</th>";
			}else{
				if($login_fabrica == 171){
				echo "<th>".traduz("Referência Fábrica")."</th>";
				}
				echo "<th>".traduz("Peça")."</th>";
				echo "<th>".traduz("Referência")."</th>";
			}

			if ($login_fabrica == 1) {
				echo "<th>".traduz("Type")."</th>";
			}

            echo "<th>".traduz("Qtde")."</th>";

            if($login_fabrica == 1){
                echo "<th>".traduz("Garantia da peça/Meses")."</th>";
            }

	//  HD 113942 - Adicionar Lenoxx às fábricas que tem a imagem das peças na lista básica (cadastro, pesquisa e consulta)
			if (in_array($login_fabrica, array(11,45,172))) {
				echo "<th>".traduz("Imagem")."</th>";
			}
			//HD 347523 Fim 1
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";
		}else{
            echo "<div class='alert alert-warning'><h4>".traduz("Não foram Encontrados Resultados para esta Pesquisa.")."</h4></div>";
        }

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
				$ordem          = pg_fetch_result ($res,$i,posicao);
				$serie_inicial  = pg_fetch_result ($res,$i,serie_inicial);
				$serie_final    = pg_fetch_result ($res,$i,serie_final);
				$peca           = pg_fetch_result ($res,$i,referencia);
				$referencia_fabrica = pg_fetch_result ($res,$i,referencia_fabrica);
				$peca_id        = pg_fetch_result ($res,$i,peca);
				$descricao      = pg_fetch_result ($res,$i,descricao);
				$informacao_peca      = pg_fetch_result ($res,$i,informacoes);
				$qtde           = pg_fetch_result ($res,$i,qtde);
				$type           = pg_fetch_result($res, $i, 'type');
// 				$alternativa    = pg_fetch_result ($res,$i,alternativa);
// 				$alt_descricao  = pg_fetch_result ($res,$i,alt_descricao);
// 				$para           = pg_fetch_result ($res,$i,para);
//                 $para_descricao = pg_fetch_result ($res,$i,para_descricao);
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

                $sqlD = "SELECT  tbl_depara.de,
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


// 				if (strlen ($alternativa) > 0) {
// 					$cor = "#91C8FF";
// 					$peca = $alternativa;
// 					$descricao = $alt_descricao;
// 				}
//
// 				if (strlen ($para) > 0) {
// 					$cor = "#E8C023";
// 					$peca = $para;
// 					$descricao = $para_descricao;
// 				}


				echo "<tr bgcolor='$cor' style='font-size:8pt'>";

				if ($login_fabrica == 1) {
					$aux_sql = "SELECT ordem FROM tbl_lista_basica WHERE produto = $produto AND peca = $peca_id LIMIT 1";
					$aux_res = pg_query($con, $aux_sql);

					if (pg_num_rows($aux_res) > 0) {
						unset($ordem);
						$ordem = pg_fetch_result($aux_res, 0, 0);
					}
				}
				
				echo "<td align='right' nowrap>$ordem</td>";
				
				if(!in_array($login_fabrica,array(1, 152,180,181,182))){
					echo "<td align='left' nowrap>$serie_inicial</td>";
					echo "<td align='left' nowrap>$serie_final</td>";
				}
				if ($login_fabrica == 1) {
					$aux_sql = "SELECT informacoes FROM tbl_peca WHERE peca = $peca_id AND fabrica = $login_fabrica LIMIT 1";
                    $aux_res = pg_query($con, $aux_sql);
                    
                    $informacao_peca = pg_fetch_result($aux_res, 0, 0);
                    if (empty($informacao_peca)) {
                    	$informacao_peca = "Ativo";
                    }
					echo "<td align='left' nowrap>$informacao_peca</td>";
				}

				if($login_fabrica == 171){
				echo "<td align='left' nowrap>$referencia_fabrica</td>";
				}

				echo "<td align='left' nowrap>$peca</td>";
				echo "<td align='left' nowrap>$descricao</td>";

				if ($login_fabrica == 1) {
					echo "<td align='center'>$type</td>";
				}

				echo "<td align='center' nowrap>$qtde</td>";
				
                /*HD-4217476*/
                if($login_fabrica == 1){
					$aux_sql = "SELECT garantia_peca FROM tbl_lista_basica WHERE produto = $produto AND peca = $peca_id LIMIT 1";
					$aux_res = pg_query($con, $aux_sql);
					$garantia_meses = pg_fetch_result($aux_res, 0, 'garantia_peca');

					if (empty($garantia_meses)) {
						$aux_sql = "SELECT garantia_diferenciada FROM tbl_peca WHERE peca = $peca_id AND fabrica = $login_fabrica LIMIT 1";
						$aux_res = pg_query($con, $aux_sql);
						$garantia_meses = pg_fetch_result($aux_res, 0, 'garantia_diferenciada');

						if (empty($garantia_meses)) {
							$aux_sql = "SELECT garantia FROM tbl_produto WHERE produto = $produto AND fabrica_i = $login_fabrica LIMIT 1";
							$aux_res = pg_query($con, $aux_sql);
							$garantia_meses = pg_fetch_result($aux_res, 0, 'garantia');
						}
					}

                    echo "<td align='center' nowrap>$garantia_meses</td>";
                }
	 //  HD 113942 - Adicionar Lenoxx às fábricas que tem a imagem das peças na lista básica (cadastro, pesquisa e consulta)
			if (in_array($login_fabrica, array(11,45,172))) {
					echo "<td align='right' nowrap>";

	            $xpecas = $tDocs->getDocumentsByRef($peca_id, "peca");
	            if (!empty($xpecas->attachListInfo)) {

					$a = 1;
					foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
					    $fotoPeca = $vFoto["link"];
					    if ($a == 1){break;}
					}
					echo "<a href='$fotoPeca' target='_blank' title='$descricao' class='thickbox'>
							<img src='$fotoPeca' border='0' width='80' height='50'>
						</a>";
	            } else {


						if ($dh = opendir("../imagens_pecas/$login_fabrica/pequena/")) {
							while (false !== ($filename = readdir($dh))) {
								$xpeca = $peca_id.'.';
								if (strpos($filename,$peca_id) !== false){
									$po = strlen($xpeca);
									if(substr($filename, 0,$po)==$xpeca){
										$contador++;
										$url_img_pq = "../imagens_pecas/$login_fabrica/pequena/$filename";
										$url_img_md = "../imagens_pecas/$login_fabrica/media/$filename";
				?>
										<a href='<?=$url_img_md ?>' title='<?=$descricao?>' class='thickbox'>
											<img src='<?=$url_img_pq ?>' border='0' width='80' height='50'>
										</a>
				<?
									}
								}
							}
						}
				}

				echo "</td>";
				}
				echo "</tr>";
			}
			echo "</tbody>";
			echo "</table>";


	}
}

//HD 347523 MLG - Ordena a lista, inicialmente pela referência
?>
<br />
<script type='text/javascript'>
		$.dataTableLoad({ table: "#tabela_produtos" });
</script>
<?
include "rodape.php";
?>

</body>
</html>
