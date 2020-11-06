<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$liberar_preco = true ;
# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	
	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}
		
		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj = trim(pg_fetch_result($res,$i,cnpj));
				$nome = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

$layout_menu = "callcenter";
$title = traduz("CONSULTA VALORES DA TABELA DE PREÇO");
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

$posto_codigo = ($_POST['codigo_posto']) ? $_POST['codigo_posto'] : $_GET['codigo'];
$posto_nome = ($_POST['descricao_posto']) ? $_POST['descricao_posto'] : $_GET['nome'];
 
$btn_acao			 = $_REQUEST['btn_acao'];

if (isset($_POST['codigo_posto']) || isset($_GET['codigo']) || isset($_POST['descricao_posto']) || isset($_GET['nome'])) {
	if (strlen($posto_codigo) == 0 || strlen($posto_nome) == 0) {
		$msg_erro = "Preencha os campos obrigatórios";
	}
}	

// seleciona o posto
if (strlen($posto_codigo) > 0){
	$sql = "SELECT posto
			FROM   tbl_posto_fabrica
			WHERE  tbl_posto_fabrica.fabrica      = $login_fabrica
			AND    tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0) $posto = pg_fetch_result($res,0,0);
}

 if($_POST['tabela'])             $tabela             = $_POST['tabela'];             
 if($_POST['referencia_produto']) $referencia_produto = $_POST['referencia_produto']; 
 if($_POST['descricao_produto'])  $descricao_produto  = $_POST['descricao_produto'];  

 if($_GET['tabela'])              $tabela             = $_GET['tabela'];              
 if($_GET['referencia_produto'])  $referencia_produto = $_GET['referencia_produto'];  
 if($_GET['descricao_produto'])   $descricao_produto  = $_GET['descricao_produto'];   

 if($_POST['referencia_peca'])    $referencia_peca    = $_POST['referencia_peca'];    
 if($_POST['descricao_peca'])     $descricao_peca     = $_POST['descricao_peca'];     

 if($_GET['referencia_peca'])     $referencia_peca    = $_GET['referencia_peca'];     
 if($_GET['descricao_peca'])      $descricao_peca     = $_GET['descricao_peca'];      

if ($login_fabrica == 3) {
	if (strlen($descricao_produto) == 0 AND strlen($referencia_produto) == 0 AND strlen($descricao_peca) == 0 AND strlen($referencia_peca) == 0) {
	}
}

if(strlen($btn_acao) > 0 AND $btn_acao != "todos"){
	if(strlen($referencia_produto)>0 ){
		
		$sql = "SELECT * from tbl_produto WHERE tbl_produto.referencia = '$referencia_produto'
					  AND tbl_produto.fabrica_i=$login_fabrica";
		

		$sql .= $cond;
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)<= 0 ){
			$msg_erro = traduz("Referência do Produto Inválida");
		}
	}
	else{
		$msg_erro = traduz("Informe a Referência do Produto");
	}
}
?>

<script language="JavaScript">

/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/
function fnc_pesquisa_produtoXXX (referencia,descricao,tabela) {
	var url = "";
	if (referencia.value != "" || descricao.value != "") {
		url = "pesquisa_tabela.php?referencia=" + referencia.value + "&descricao=" + descricao.value + "&retorno=<?echo $PHP_SELF?>";
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.referencia = referencia;
		janela.descricao  = descricao;
		janela.tabela     = tabela;
		janela.focus();
	}
}
</script>

<!--[if lt IE 8]>
<style>
table.tabela2{
	empty-cells:show;
    border-collapse:collapse;
	border-spacing: 2px;
}
</style>
<![endif]-->

<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->

<script language="JavaScript">

$(function() {
	Shadowbox.init();
	
		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});	

		$.autocompleteLoad(Array("posto","produto"));

});
		function retorna_posto(retorno){
    		$("#codigo_posto").val(retorno.codigo);
			$("#descricao_posto").val(retorno.nome);
		}

		function retorna_produto (retorno) {
			$("#produto_referencia").val(retorno.referencia);
			$("#produto_descricao").val(retorno.descricao);
		}

	function liberaTela(){
		var codigo=document.getElementById('codigo_posto').value;
		var nome=document.getElementById('descricao_posto').value;
		window.location='<? echo $PHP_SELF ?>?codigo='+codigo+'&nome='+nome;
	}



</script>

<?php if(strlen($msg_erro)>0){ ?>
		<div class="alert alert-danger"><h4><?php echo $msg_erro; ?></h4></div>
<?php } ?>
<form class="form-search form-inline tc_formulario" method='GET' action='<? echo $PHP_SELF ?>' name='frm_tabela'>
<?	if ($login_fabrica <> 6 and $login_fabrica <> 24){?>
		
	<div class="titulo_tabela"><?=traduz('Parâmetros de Pesquisa')?></div>
	<div class="row-fluid">
		<div class="alert alert-warning span12"><b><?=traduz('Para selecionar os valores das peças, primeiramente selecione o posto e clique em "Continuar".')?></b></div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
			<div class='span4'>
				<div class='control-group <?= ($msg_erro == 'Preencha os campos obrigatórios') ? 'error' : '' ?>'>
					<label class='control-label' for='codigo_posto'><?=traduz('Código Posto')?></label>
					<div class='controls controls-row'>
							<div class='span7 input-append'>
								<h5 class='asteristico'>*</h5>
								<input class="span12" type="text" name="codigo_posto" id="codigo_posto" size="15" value="<?= ($_GET['codigo_posto']) ? $_GET['codigo_posto'] : $_GET['codigo'] ?>">
								<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</div>	
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?= ($msg_erro == 'Preencha os campos obrigatórios') ? 'error' : '' ?>'>
					<label class='control-label' for='nome_posto'><?=traduz('Nome Posto')?></label>
					<div class='controls controls-row '>
						<div class='span10 input-append'>
							<h5 class='asteristico'>*</h5>
							<input class="frm" type="text" name="descricao_posto" id="descricao_posto" size="50" value="<?= ($_GET['descricao_posto']) ? $_GET['descricao_posto'] : $_GET['nome'] ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>	
			</div>
		<div class="span2"></div>
	</div>
	<br>
	<div class="row-fluid">
		<center><input class="btn" type="button" value="<?=traduz("Continuar")?>" onclick="liberaTela();"></center>
	</div>	
<? 
}
if (strlen($posto_codigo) > 0 or $login_fabrica == 6 or $login_fabrica == 24){
?>

	<div class="titulo_tabela"><?=traduz('Parâmetros de Pesquisa')?></div>
	<div class="row-fluid">
		<div class="span2"></div>
			<div class='span8'>
				<div class='control-group'>
					<label class='control-label' for='tabela'><?=traduz('Tabela')?></label>
					<div class="controls-row"> 
						<div class='span10 input-append'>
							<select name="tabela" size="1" tabindex="0"  class="frm">
									<?

											if (in_array($login_fabrica, array(151,176))){
												$joinTable = "JOIN tbl_posto_linha ON tbl_posto_linha.tabela = tbl_tabela.tabela OR tbl_posto_linha.tabela_posto = tbl_tabela.tabela";
											}else{
												$joinTable = "JOIN tbl_posto_linha USING (tabela)";
											}

											$res = pg_query ($con,"SELECT linha_pedido FROM tbl_fabrica WHERE fabrica = $login_fabrica");
											$linha_pedido = pg_fetch_result ($res,0,0);

											$sql = "SELECT      tbl_tabela.tabela      ,
																tbl_tabela.sigla_tabela,
																tbl_tabela.descricao
													FROM        tbl_tabela
													{$joinTable}
													JOIN        tbl_linha    ON tbl_linha.linha   = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
													WHERE       tbl_tabela.fabrica    = $login_fabrica
													AND         tbl_posto_linha.posto = $posto
													AND         tbl_tabela.ativa   = 't'
													GROUP BY    tbl_tabela.tabela      ,
																tbl_tabela.sigla_tabela,
																tbl_tabela.descricao
													ORDER BY    tbl_tabela.sigla_tabela";
											$res = pg_query($con,$sql);
											
											if (pg_num_rows ($res) == 0 and $linha_pedido <> 't' ) {
												$sql = "SELECT *
														FROM   tbl_tabela
														WHERE  tbl_tabela.fabrica = $login_fabrica
														AND    tbl_tabela.ativa   = 't'
														ORDER BY tbl_tabela.sigla_tabela";
												$res = pg_query($con,$sql);
											}
											for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
												$aux_tabela       = trim(pg_fetch_result($res,$i,tabela));
												$aux_sigla_tabela = trim(pg_fetch_result($res,$i,descricao));
												
												echo "<option "; if ($tabela == $aux_tabela) echo " selected "; echo " value='$aux_tabela'>$aux_sigla_tabela</option>";
											}
									?>
								</select>
							</div>
						</div>
					</div>	
				</div>
		<div class="span2"></div>		
	</div>	
	<div class="row-fluid">
		<div class="span2"></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='referencia_produto'><?=traduz('Referência do Produto')?></label>
					<div class="controls-row">
						<div class='span10 input-append'>
							<input type='text' name='referencia_produto' id='produto_referencia' size='20' maxlength='30' value='<? echo $referencia_produto ?>' class="frm">
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='nome_produto'><?=traduz('Nome do Produto')?></label>
					<div class="controls-row">
						<div class='span10 input-append'>
							<input type='text' name='descricao_produto' id='produto_descricao' size='30' maxlength='50' value='<? echo $descricao_produto ?>' class="frm">
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>					
				</div>
			</div>
		<div class="span2"></div>
	</div>
<? if ($login_fabrica == 3) { ?>
	<div class="row-fluid">
		<div class="span2"></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='nome_produto'><?=traduz('Referência da peça')?></label>
					<div class="controls-row">
						<div class='span10 input-append'>
						<input type='text' name='referencia_peca' size='20' maxlength='30' value='<? echo $referencia_peca ?>' class="frm"><img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca (document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,"referencia")' style="cursor:pointer;">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>		
					<label class='control-label' for='nome_produto'><?=traduz('ou Modelo da peça')?></label>
					<input type='text' name='descricao_peca' size='30' maxlength='50' value='<? echo $descricao_peca ?>' class="frm"><img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca(document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,"descricao")' style="cursor:pointer;">
				</div>
			</div>			
	<div class="span2"></div>	
	</div>	
<? } ?>
	<br />
	<div class="row-fluid">
	<div class="span1"></div>
		<div class='span3'>
			<div class='control-group'>
				<center>
				<input type="hidden" name="btn_acao" value="">
					<input class="btn" type="button" value="<?=traduz("Pesquisar por Referência")?>" onclick="javascript: if (document.frm_tabela.btn_acao.value == '' ) { document.frm_tabela.btn_acao.value='continuar' ; document.frm_tabela.submit() } else { alert ('<?=traduz('Aguarde submissão')?>') }" ALT="<?=traduz("Listar tabela de preços")?>" border='0' >
				</center>
			</div>
		</div>	
		<div class='span3'>
			<div class='control-group'>
				<center>
					<input class="btn btn-primary" type="button" value="<?=traduz("Listar Todas as Peças")?>" onclick="javascript: if (document.frm_tabela.btn_acao.value == '' ) { document.frm_tabela.btn_acao.value='todos' ; document.frm_tabela.submit() } else { alert ('<?=traduz('Aguarde submissão')?>') }" ALT="<?=traduz("Listar tabela de preços")?>" border='0' >
				</center>
			</div>
		</div>
		<div class='span3'>
			<div class='control-group'>
				<center>
					<input class="btn btn-primary" type="button" value="<?=traduz("Listar Todos os Produtos")?>" onclick="javascript: document.frm_tabela.referencia_produto.value='' ; document.frm_tabela.descricao_produto.value='' ; document.frm_tabela.submit()" ALT="<?=traduz("Listar tabela de preços")?>" border='0' >
				</center>
			</div>
		</div>
	<div class="span1"></div>		
	</div>
</form>
<? } // se foi setado o posto ?>

<br>
<!-- <center>
<a href="<? echo $PHP_SELF ?>?relatorio=1">Clique aqui</a> para ver relação de produtos.
<p>
 -->
<?
if (strlen ($_GET['relatorio']) > 0) {
	$sql = "SELECT tbl_produto.*
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  tbl_linha.fabrica = $login_fabrica
			AND    tbl_produto.ativo = 't'
			ORDER BY tbl_produto.descricao";
	$res = pg_query ($con,$sql);

	echo "<table align='center' border='0'>";
	
	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		$cor = '#ffffff';
		if ($i % 2 == 0) $cor = '#f8f8f8';

		echo "<tr bgcolor='$cor'>";
		
		echo "<td class='lista'>";
		echo pg_fetch_result ($res,$i,referencia);
		echo "</td>";
		
		echo "<td class='lista'>";
		echo pg_fetch_result ($res,$i,descricao);
		echo "</td>";
		
		echo "</tr>";
	}
	echo "</table>";
}

if(strlen($tabela) > 0 ){
	
	if ($btn_acao=="todos") {
		
		########## EXIBE TABELA DE PRECO
		$letra = (strlen($_GET['letra']) == 0) ? 'a' : $_GET['letra'];
		
		$sql = "SELECT  tbl_peca.referencia                                                     AS peca_referencia   ,
						tbl_peca.descricao                                                      AS peca_descricao    ,
						tbl_peca.referencia_fabrica                                    AS peca_referencia_fabrica    ,
						tbl_peca.unidade                                                                             ,
						tbl_tabela_item.preco                                                                        ,
						tbl_peca.ipi                                                                                 ,
						to_char(((tbl_tabela_item.preco * (1 + tbl_peca.ipi))/10)::double precision,('999999990.99')::text) AS total 
				FROM    tbl_peca
				JOIN    tbl_tabela_item  ON tbl_tabela_item.peca = tbl_peca.peca
				WHERE   tbl_peca.fabrica       = $login_fabrica 
				AND		tbl_tabela_item.tabela = $tabela
				AND		tbl_peca.ativo         = 't'";

		#107125---
		$sql_x = $sql." ORDER BY    tbl_peca.descricao ,
							tbl_peca.referencia";
		#---------
		
		$sql .="AND		tbl_peca.descricao ILIKE '$letra%'";
		$sql .= "ORDER BY    tbl_peca.descricao ,
							tbl_peca.referencia";
		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";
		// ##### PAGINACAO ##### //
		require "_class_paginacao.php";

		// definicoes de variaveis
		$max_links = 11;					// máximo de links à serem exibidos
		$max_res   = 100;					// máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();		// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

		// ##### PAGINACAO ##### //

		//echo nl2br($sqlCount);

		if (pg_num_rows($res) > 0) {
			#--------- Criacao do arquivo em XLS ------------
			if ($login_fabrica==140){
				$arquivo = "xls/tabela" . $tabela . ".csv";
				$fp = fopen ($arquivo,"w");

				fwrite ($fp,"Referência da peça;Descrição da peça                  ;Unidade;Preço;IPI\n");
				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
					$linha  = pg_fetch_result ($res,$i,peca_referencia);
					$linha .= ";";
					$linha .= pg_fetch_result ($res,$i,peca_descricao);
					$linha .= ";";
					$linha .= pg_fetch_result ($res,$i,unidade);
					if ($liberar_preco) {
						$linha .= ";";
						$linha .= pg_fetch_result ($res,$i,preco);
						$linha .= ";";
						$linha .= pg_fetch_result ($res,$i,ipi);
					}
					fwrite ($fp,$linha);
					fwrite ($fp,"\n");
				}

				fclose ($fp);

			}
			

			if ($login_fabrica==11 or $login_fabrica == 172 or $login_fabrica == 151){
				echo "<div id='gerar_excel' class='btn_excel'>
						<a href='tabela_precos_xls.php?downloadTabela=1&tabela=$tabela'>
					  		<span><img src='imagens/excel.png' /></span>
							<span class='txt'>".traduz("Gerar Arquivo Excel")."</span>
						</a>	
					  </div><br />";
			}
			if (pg_num_rows($res) > 0) {
				if ($login_fabrica <> 3){
					echo "<div id='gerar_excel' class='btn_excel'>
					<a href='xls/tabela$tabela.csv'>
						  <span><img src='imagens/excel.png' /></span>
						<span class='txt'>".traduz("Gerar Arquivo Excel")."</span>
					</a>	
				  </div><br />";

				}
			}
			echo "<table width='100%' align='center' cellspacing='1' border='0' cellpadding='2'>";

			echo "<tr>";
			$letras =  array(0=>'A', 'B', 'C', 'D', 'E', 
								'F', 'G', 'H', 'I', 'J', 
								'K', 'L', 'M', 'N', 'O', 
								'P', 'Q', 'R', 'S', 'T', 
								'U', 'V', 'W', 'X', 'Y', 'Z');
			$totalLetras = count($letras);
			for($j=0; $j<$totalLetras; $j++){
				//echo "<a href='$PHP_SELF?letra=a&tabela=$tabela&referencia_produto=$referencia_produto&descricao_produto=$descricao_produto'> A </a>";
				echo "<th align='center'>";
				echo "<a href='$PHP_SELF?letra=$letras[$j]&tabela=$tabela&btn_acao=todos'>&nbsp;$letras[$j]&nbsp;</a>";
				echo "</th>";
			}
			echo "</tr>";
			echo "</table>";

			echo "<table id='tabela_valores' class='table table-striped table-bordered table-fixed'>";

			echo "<thead><tr class='titulo_coluna'>";
			if ($login_fabrica == 171) {
				echo "<th align='center'>".traduz("Referência Fábrica")."</th>";
			}
			echo "<th align='center'>".traduz("Peça")."</th>";
			echo "<th align='center'>".traduz("Descrição")."</th>";
			echo "<th align='center'>".traduz("Unidade")."</th>";
			if ($liberar_preco) {
				echo "<th  align='center'>".traduz("Preço")."</th>";
				echo "<th  align='center'>".traduz("IPI")."</th>";
			}
			echo "</tr></thead>";

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
				$peca_referencia_fabrica    = trim(pg_fetch_result ($res,$i,peca_referencia_fabrica));
				$peca_referencia    = trim(pg_fetch_result ($res,$i,peca_referencia));
				$peca_descricao     = trim(pg_fetch_result ($res,$i,peca_descricao));
				$unidade            = trim(pg_fetch_result ($res,$i,unidade));
				$preco              = trim(pg_fetch_result ($res,$i,preco));
				$ipi                = trim(pg_fetch_result ($res,$i,ipi));
				
				$cor = '#F7F5F0';
				if ($i % 2 == 0) $cor = '#F1F4FA';
				
				echo "<tr bgcolor='$cor'>";
				if ($login_fabrica == 171) {
				echo "<td>";
				echo $peca_referencia_fabrica;
				echo "</td>";
				}				
				echo "<td>";
				echo $peca_referencia;
				echo "</td>";

				echo "<td align='left'>";
				echo $peca_descricao;
				echo "</td>";
				
				echo "<td>";
				echo $unidade;
				echo "</td>";
				
				if ($liberar_preco) {
				
					if ($login_fabrica == 24){
						$preco_suggar = $preco + ($preco * 0.2);
						echo "<td align='right'>".number_format ( $preco_suggar - ($preco_suggar * 0.4) ,2,",",".")."</td>";
					}else{
						echo "<td align='center'>";
						echo number_format ($preco,2,",",".");
						echo "</td>";
					}
				
					echo "<td align='right'>";
					echo $ipi;
					echo "</td>";
				}
					
				echo "</tr>";
			}
			
			echo "</table>";

			// ##### PAGINACAO ##### //

			// links da paginacao
			echo "<br>";

			echo "<div>";

			if($pagina < $max_links) { 
				$paginacao = pagina + 1;
			}else{
				$paginacao = pagina;
			}

			// paginacao com restricao de links da paginacao

			// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
			$todos_links		= $mult_pag->Construir_Links("strings", "sim");

			// função que limita a quantidade de links no rodape
			$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

			for ($n = 0; $n < count($links_limitados); $n++) {
				echo "<center><font color='#DDDDDD'><div class='btn'>".$links_limitados[$n]."</div></font>&nbsp;&nbsp;</center";
			}

			echo "</div>";

			$resultado_inicial = ($pagina * $max_res) + 1;
			$resultado_final   = $max_res + ( $pagina * $max_res);
			$registros         = $mult_pag->Retorna_Resultado();

			$valor_pagina   = $pagina + 1;
			$numero_paginas = intval(($registros / $max_res) + 1);

			if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

			if ($registros > 0){
				echo "<br><br />";
				echo "<div style='width: 100%;text-align:center;'>";
				echo traduz("Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.");
				echo "<font>";
				echo traduz(" (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)");
				echo "</font>";
				echo "</div>";
			}

			// ##### PAGINACAO ##### //

		}else{
			// SE NAO ENCONTROU REGISTROS
			echo "<center><font face='arial' size='-1'>".traduz("Peças da linha com iniciais")." <b>\"$letra\"</b>".traduz(" não encontradas.")."</font></center><br>";
			echo "<table width='700' align='center' cellspacing='1' border='0' class='tabela'>";
			echo "<tr>";
			echo "<td align='center' colspan='7'>";

			echo "<table width='100%' align='center' cellspacing='1' border='0' cellpadding='2' class='tabela'>";

			echo "<tr class='letras'>";
			$letras =  array(0=>'A', 'B', 'C', 'D', 'E', 
								'F', 'G', 'H', 'I', 'J', 
								'K', 'L', 'M', 'N', 'O', 
								'P', 'Q', 'R', 'S', 'T', 
								'U', 'V', 'W', 'X', 'Y', 'Z');
			$totalLetras = count($letras);
			for($j=0; $j<$totalLetras; $j++){
				echo "<td align='center'>";
				echo "<a href='$PHP_SELF?letra=$letras[$j]&tabela=$tabela&btn_acao=todos'>&nbsp;$letras[$j]&nbsp;</a>";
				echo "</td>";
			}
			echo "</tr>";
			echo "</table>";

			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}

	}else{
		if(strlen($msg_erro) == 0){
		########## EXIBE LISTA BÁSICA
		// SQL RETIRADO PARA MELHORAR PERFORMANCE
		$sql = "SELECT  tbl_peca.referencia                                                     AS peca_referencia   ,
						tbl_peca.descricao                                                      AS peca_descricao    ,
						tbl_peca.unidade                                                                             ,
						tbl_tabela_item.preco                                                                        ,
						tbl_peca.ipi                                                                                 ,
						to_char((tbl_tabela_item.preco * ((1 + tbl_peca.ipi))/10),'999999990.99') AS total             ,
						tbl_produto.referencia                                                  AS produto_referencia,
						tbl_produto.descricao                                                   AS produto_descricao
				FROM    tbl_peca
				JOIN    tbl_tabela_item  ON tbl_tabela_item.peca = tbl_peca.peca
				JOIN    tbl_lista_basica ON tbl_peca.peca        = tbl_lista_basica.peca
				JOIN    tbl_produto      ON tbl_produto.produto  = tbl_lista_basica.produto
				WHERE   tbl_peca.fabrica = $login_fabrica
				AND     tbl_produto.ativo = 't'
				AND     tbl_peca.ativo    = 't' ";

		if ($item_aparencia <> 't') $sql .= " AND tbl_peca.item_aparencia <> 't' ";
		
		if (strlen($descricao_produto) > 0) {
			$sql .= " AND tbl_produto.descricao ilike '%$descricao_produto%' ";
		}
		
		if (strlen($referencia_produto) > 0) {
			$sql .= "AND upper(tbl_produto.referencia) = upper('$referencia_produto') ";
		}
		
		if (strlen($descricao_peca) > 0) {
			$sql .= " AND tbl_peca.descricao ilike '%$descricao_peca%' ";
		}
		
		if (strlen($referencia_peca) > 0) {
			$sql .= "AND upper(tbl_peca.referencia) = upper('$referencia_peca') ";
		}
		
		// ORDENACAO
		if ($login_fabrica == 3){
			$sql .= "AND tbl_tabela_item.tabela = $tabela
					ORDER BY    tbl_produto.descricao ,
								tbl_peca.descricao    ,
								tbl_produto.referencia";
		}else{
			$sql .= "AND tbl_tabela_item.tabela = $tabela
					ORDER BY    tbl_produto.referencia,
								tbl_produto.descricao";
		}
		
		// SQL INSERIDO PARA MELHORAR PERFORMANCE
		$sql = "SELECT  c.produto_referencia  ,
						c.produto_descricao   ,
						c.peca_referencia     ,
						c.peca_descricao      ,
						c.peca_referencia_fabrica      ,
						c.unidade             ,
						c.ipi                 ,
						tbl_tabela_item.preco ,
						tbl_tabela_item.preco ,
						to_char((tbl_tabela_item.preco * ((1 + c.ipi))/10) ,'999999990.99') AS total
				FROM (
						SELECT  b.produto_referencia                  ,
								b.produto_descricao                   ,
								tbl_peca.peca                         ,
								tbl_peca.referencia AS peca_referencia,
								tbl_peca.descricao  AS peca_descricao ,
								tbl_peca.referencia_fabrica  AS peca_referencia_fabrica,
								tbl_peca.unidade                      ,
								tbl_peca.ipi
						FROM (
								SELECT  a.produto_referencia    ,
										a.produto_descricao     ,
										tbl_lista_basica.produto,
										tbl_lista_basica.peca
								FROM (
										SELECT  tbl_produto.produto                         ,
												tbl_produto.referencia AS produto_referencia,
												tbl_produto.descricao  AS produto_descricao
										FROM  tbl_produto
										JOIN  tbl_linha ON tbl_linha.linha = tbl_produto.linha
										WHERE tbl_produto.ativo IS TRUE
										AND   tbl_linha.fabrica = $login_fabrica ";

		if (strlen($descricao_produto) > 0) {
			
			if (strpos($descricao_produto,'110 V') > 0) {
				
				$desc_prod = substr($descricao_produto,0,strpos($descricao_produto,'110 V') - 1);
				$volt_prod = substr($descricao_produto,strpos($descricao_produto,'110 V'),strlen($descricao_produto));
				
				$sql .= "AND tbl_produto.descricao ilike '%$desc_prod%'
						AND  tbl_produto.voltagem = '$volt_prod' ";
			}
			
			if (strpos($descricao_produto,'220 V') > 0) {
				
				$desc_prod = substr($descricao_produto,0,strpos($descricao_produto,'220 V') - 1);
				$volt_prod = substr($descricao_produto,strpos($descricao_produto,'220 V'),strlen($descricao_produto));
				
				$sql .= "AND tbl_produto.descricao ilike '%$desc_prod%'
						AND  tbl_produto.voltagem = '$volt_prod' ";
			}
		}
		
		if (strlen($referencia_produto) > 0) {
			$sql .= "AND upper(tbl_produto.referencia) = upper('$referencia_produto') ";
		}
		
		$sql .= "				) AS a
								JOIN tbl_lista_basica    ON tbl_lista_basica.produto = a.produto
														AND tbl_lista_basica.fabrica = $login_fabrica
						) AS b
						JOIN tbl_peca    ON tbl_peca.peca    = b.peca
										AND tbl_peca.fabrica = $login_fabrica
										AND tbl_peca.ativo IS TRUE ";
		if ($item_aparencia <> 't') $sql .= " AND tbl_peca.item_aparencia IS FALSE ";
		
		if (strlen($descricao_peca) > 0) {
			$sql .= " WHERE tbl_peca.descricao ilike '%$descricao_peca%' ";
		}
		
		if (strlen($referencia_peca) > 0) {
			if (strlen($descricao_peca) > 0)  $sql .= "AND   upper(tbl_peca.referencia) = upper('$referencia_peca') ";
			if (strlen($descricao_peca) == 0) $sql .= "WHERE upper(tbl_peca.referencia) = upper('$referencia_peca') ";
		}
		
		$sql .= ") AS c
				JOIN tbl_tabela_item ON tbl_tabela_item.peca   = c.peca
									AND tbl_tabela_item.tabela = $tabela ";
		
		// ORDENACAO
		if ($login_fabrica == 3){
			$sql .= "ORDER BY   c.produto_descricao,
								c.peca_descricao   ,
								c.produto_referencia";
		}else{
			$sql .= "ORDER BY   c.produto_referencia,
								c.produto_descricao";
		}
		//echo nl2br($sql); exit;
		$res = pg_query ($con,$sql);

		
			
			#--------- Criacao do arquivo em XLS ------------
			#Reativado : HD 8148
			if ($login_fabrica==11 or $login_fabrica == 172 or $login_fabrica == 151 or $login_fabrica == 140){
				$arquivo = "xls/tabela" . $tabela . ".csv";
				$fp = fopen ($arquivo, 'w');
				fwrite ($fp,"Referência da peça;Descrição da peça                  ;Unidade;Preço;IPI\n");
				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
					if ($mostraTopo == 'n'){
						$linha  = pg_fetch_result ($res,$i,produto_referencia);
						$linha .= ";";
						$linha  = pg_fetch_result ($res,$i,produto_descricao);
						$linha .= ";";
					}
					$linha  = pg_fetch_result ($res,$i,peca_referencia);
					$linha .= ";";
					$linha .= pg_fetch_result ($res,$i,peca_descricao);
					$linha .= ";";
					$linha .= pg_fetch_result ($res,$i,unidade);
					if ($liberar_preco) {
						$linha .= ";";
						$linha .= number_format (pg_fetch_result ($res,$i,preco),2,",",".");
						$linha .= ";";
						$linha .= pg_fetch_result ($res,$i,ipi);
					}
					
					@fwrite ($fp,$linha);
					@fwrite ($fp,"\n");
				}

				@fclose ($fp);
				
				if (pg_num_rows($res) > 0) {
					if ($login_fabrica <> 3){
						echo "<div id='gerar_excel' class='btn_excel'>
						<a href='xls/tabela$tabela.csv'>
					  		<span><img src='imagens/excel.png' /></span>
							<span class='txt'>".traduz("Gerar Arquivo Excel")."</span>
						</a>	
					  </div><br />";

					}
				}
			}
			#---------- listagem -------------
		if (pg_num_rows($res) > 0) {	
			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
				$peca_referencia_fabrica = trim(pg_fetch_result ($res,$i,peca_referencia_fabrica));
				$produto_referencia = trim(pg_fetch_result ($res,$i,produto_referencia));
				$produto_descricao  = trim(pg_fetch_result ($res,$i,produto_descricao));
				$prox_refer         = trim(pg_fetch_result ($res,$i-1,produto_referencia));
				$prox_descr         = trim(pg_fetch_result ($res,$i-1,produto_descricao));
				$peca_referencia    = trim(pg_fetch_result ($res,$i,peca_referencia));
				$peca_descricao     = trim(pg_fetch_result ($res,$i,peca_descricao));
				$unidade            = trim(pg_fetch_result ($res,$i,unidade));
				$preco              = trim(pg_fetch_result ($res,$i,preco));
				$ipi                = trim(pg_fetch_result ($res,$i,ipi));
				
				$cor = '#F7F5F0';
				if ($i % 2 == 0) $cor = '#F1F4FA';
				if ($mostraTopo <> 'n'){
					if ($prox_refer <> $produto_referencia OR $prox_descr <> $produto_descricao) {
						flush();
						echo "<table class='table table-striped table-bordered table-fixed'>";
						echo "<tr class='titulo_coluna' style='font-size: 13pt;'>";
						echo "<th colspan='7'>$produto_referencia - $produto_descricao $volt_prod</th>";
						echo "</tr>";
						echo "<tr class='titulo_coluna'>";
						if ($login_fabrica == 171) {
						echo "<th align='center'>".traduz("Referência Fábrica")."</th>";
						}
						echo "<th>".traduz("Peça")."</th>";
						echo "<th>".traduz("Descrição")."</th>";
						echo "<th>".traduz("Unidade")."</th>";
						if ($liberar_preco) {
							echo "<th>".traduz("Preço")."</th>";
							echo "<th>".traduz("IPI")."</th>";
						}
						echo "</tr>";
					}
				}
				
				echo "<tr bgcolor='$cor'>";
				if ($login_fabrica == 171) {
				echo "<td>";
				echo $peca_referencia_fabrica;
				echo "</td>";
				}
				echo "<td>";
				echo $peca_referencia;
				echo "</td>";
				
				echo "<td align='left'>";
				echo $peca_descricao;
				echo "</td>";
				
				echo "<td>";
				echo $unidade."&nbsp;";
				echo "</td>";
				
				if ($liberar_preco) {
				
					if ($login_fabrica == 24){
						$preco_suggar = $preco + ($preco * 0.2);
						echo "<td align='right'>".number_format ( $preco_suggar - ($preco_suggar * 0.4) ,2,",",".")."</td>";
					}
					else {
						echo "<td align='right'>";
						echo number_format ($preco,2,",",".");
						echo "</td>";
					}
					echo "<td align='right'>";
					echo $ipi;
					echo "</td>";
				}

				echo "</tr>";
			}
			echo "</table>";
		}
		else{
				echo "<center><div class='alert alert-warning'><h4>".traduz("Produto informado não encontrado")."</h4></div></center>";
			}
		}
	}
}

			#HD107125
			if($login_fabrica==51 AND strlen($_GET['posto_codigo'])>0 AND strlen($_GET['tabela'])>0){
			?>
					<script>
					function envia_dados(formulario)
						{
							var janela = window.open("", "janela", "height=200, width=200" ) ;
						}
					</script>
					<form name='gera_xls' action="gera_xls_txt.php" method='post' target="janela" onSubmit="javascript:envia_dados(this)">
						<textarea name="query_gera" style="display: none">
						<?
						echo $sql_x;
						?>
						</textarea>
						<input type='hidden' name='tipo' id='tipo'>
			<?
				if(strlen($_GET['descricao_produto'])==0){
			?>
						<input class="btn" type='button' name='xls' value='<?=traduz("Gerar XLS")?>' onclick="document.getElementById('tipo').value = 'xls';this.form.submit()">
						<input class="btn" type='button' name='txt' value='<?=traduz("Gerar TXT")?>' onclick="document.getElementById('tipo').value = 'txt';this.form.submit();" alt="<?=traduz("Habilite PopUp")?>" title="<?=traduz("Habilite PopUp em seu navegador!")?>">
			<?
				}
			?>
					</form>
			<?
			}if($login_fabrica==51 AND strlen($_GET['referencia_produto'])>0){

			?>
					<script>
					function envia_dados(formulario)
						{
							var janela = window.open("", "janela", "height=200, width=200" ) ;
						}
					</script>
					<form name='gera_xls' action="gera_xls_txt.php" method='post' target="janela" onSubmit="javascript:envia_dados(this)">
						<textarea name="query_gera" style="display: none">
						<? echo $sql;?>
						</textarea>
						<input type='hidden' name='tipo' id='tipox'>
			<?
				if(1==1){
			?>
						<input class="btn" type='button' name='xls' value='<?=traduz("Gerar XLS")?>' onclick="document.getElementById('tipox').value = 'xls';this.form.submit();">
						<input type='button' name='txt' value='<?=traduz("Gerar TXT")?>' onclick="document.getElementById('tipox').value = 'txt';this.form.submit();" alt="<?traduz("Habilite PopUp")?>" title="<?traduz("Habilite PopUp em seu navegador!")?>">
			<?
				}
			?>
					</form>
			<?			
			}
?>
<p>

<? include "rodape.php"; ?>
