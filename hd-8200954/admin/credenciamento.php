<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if($login_fabrica == 1){
    include_once '../class/communicator.class.php';
    include_once "../class/tdocs.class.php";
    include_once "../gera_contrato_posto.php";
}



# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.posto,tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			if ($tipo_busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
			}

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$posto = trim(pg_result($res,$i,posto));
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				/*Retira todos usu?rios do TIME*/
				$sql = "SELECT *
						FROM  tbl_empresa_cliente
						WHERE posto   = $posto
						AND   fabrica = $login_fabrica";
				$res2 = pg_exec ($con,$sql);
				if (pg_numrows($res2) > 0) continue;
				$sql = "SELECT *
						FROM  tbl_empresa_fornecedor
						WHERE posto   = $posto
						AND   fabrica = $login_fabrica";
				$res2 = pg_exec ($con,$sql);
				if (pg_numrows($res2) > 0) continue;

				$sql = "SELECT *
						FROM  tbl_erp_login
						WHERE posto   = $posto
						AND   fabrica = $login_fabrica";
				$res2 = pg_exec ($con,$sql);
				if (pg_numrows($res2) > 0) continue;

					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
	}
	exit;
}

$msg_erro = "";

$btn_listar = $_POST['btn_listar'];

if (strlen($btn_listar)>0 and strlen($tipo_credenciamento)== NULL) {
	$msg_erro = "Escolha o status do posto";
}

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if (strlen($_POST['posto']) > 0) $posto = $_POST['posto'];// hidden=post
if (strlen($_GET['posto']) > 0)  $posto = $_GET['posto'];

if (strlen($_GET["credenciamento"]) > 0)  $credenciamento = trim($_GET["credenciamento"]);
if (strlen($_POST["credenciamento"]) > 0) $credenciamento = trim($_POST["credenciamento"]);

if (strlen($_GET["tipo_credenciamento"]) > 0)  $tipo_credenciamento = trim($_GET["tipo_credenciamento"]);
if (strlen($_POST["tipo_credenciamento"]) > 0) $tipo_credenciamento = trim($_POST["tipo_credenciamento"]);


if(!empty($_POST['codigo_posto'])){

	$codigo = $_POST['codigo_posto'];
// 	$sql_codigo_posto = "SELECT
// 							tbl_posto.posto
// 						FROM tbl_posto
// 						JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.codigo_posto = '{$cod}' AND tbl_posto_fabrica.fabrica = {$login_fabrica}
// 						";
// 	$res_codigo_posto = pg_query($con, $sql_codigo_posto);
// 	$codigo = pg_fetch_result($res_codigo_posto, 0, 'posto');
}

if ($btn_acao == traduz("Credenciar"))
	$var = 'CREDENCIADO';
else if ($btn_acao == traduz("Descredenciar"))
	$var = "DESCREDENCIADO";
else if ($btn_acao == traduz("Cadastro Reprovado"))
	$var = "REPROVADO";
else if ($btn_acao == traduz("Em Credenciamento"))
	$var = "EM CREDENCIAMENTO";
else if ($btn_acao == traduz("Em Descredenciamento"))
	$var = "EM DESCREDENCIAMENTO";




if (strlen($btn_acao) > 0 AND strlen($var) > 0) {

	if($login_fabrica == 1){
		$categoria_posto_atual = $_POST['categoria_posto_atual'];
		$categoria_posto = $_POST['categoria_posto'];

		if(strlen(trim($categoria_posto))==0 and $categoria_posto_atual == 'Pré Cadastro'){
			$msg_erro = "Por favor informar a categoria do posto. ";
			$campos_erro = 'error';
		}		
	}
	
	if(strlen(trim($msg_erro))==0){

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($_POST["texto"]) > 0)
			$aux_texto = "'". trim($_POST["texto"]) ."'";
		else
			$aux_texto = "null";

		$manutencao_os = $_POST['manutencao_os'];
		$manutencao_pedido = $_POST['manutencao_pedido'];

		if ($login_fabrica != 1 ) {
			$manutencao_os = "f";
			$manutencao_pedido = "f";
		}
		if($login_fabrica == 1 AND $var == "EM DESCREDENCIAMENTO"){
			if($aux_texto == "null"){
				$msg_erro = traduz("O Campo Observações é Obrigatório");
			}
		}

		if ($btn_acao == "Em Credenciamento" OR $btn_acao == "Em Descredenciamento"){
			if (strlen($_POST["qtde_dias"]) > 0)
				$aux_qtde_dias = "'". trim($_POST["qtde_dias"]) ."'";
			else
				$msg_erro = traduz("Informe a Quantidade de Dias");
		}

		if(strlen($msg_erro) == 0 ){
			$sql = "INSERT INTO tbl_credenciamento (
						posto             ,
						fabrica           ,
						data              ,
						status            ,
						confirmacao_admin ,
						confirmacao 	  ,
						texto             ";
			if ($btn_acao == "Em Credenciamento" OR $btn_acao == "Em Descredenciamento")
			$sql .= ", dias";
			$sql .= ") VALUES (
						$posto            ,
						$login_fabrica    ,
						current_timestamp ,
						'$var'            ,
						$login_admin	  ,
						current_timestamp ,
						$aux_texto        ";
			if ($btn_acao == "Em Credenciamento" OR $btn_acao == "Em Descredenciamento")
			$sql .= ", $aux_qtde_dias";
			$sql .= ");";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			if(strlen($msg_erro) == 0 ){
				$sql = "UPDATE  tbl_posto_fabrica SET
								credenciamento = '$var'";

				// HD 916308 - Limpar o primeiro acesso quando o posto é descredenciado
				if ($var == 'DESCREDENCIADO')
					$sql .= ", primeiro_acesso = NULL";

				if($var == "EM DESCREDENCIAMENTO"){
					$sql .= "	, digita_os = '$manutencao_os',
								pedido_faturado = '$manutencao_pedido'";
				}
				if(strlen(trim($categoria_posto))>0 and $login_fabrica == 1){
					$sql .= " , categoria = '$categoria_posto' ";
				}

				if(in_array($login_fabrica, array(152,180,181,182))) {
					if($var == "CREDENCIADO"){
						$sql .= " , digita_os = 't' ";
					}
				}

				$sql .= " WHERE   fabrica = $login_fabrica
						AND     posto   = $posto;";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			if ($login_fabrica == 1 && $var == "CREDENCIADO") {
                $sql_status_anterior = "SELECT status 
                                        FROM tbl_credenciamento 
                                        WHERE posto = $posto 
                                        AND fabrica = $login_fabrica 
                                        ORDER BY data DESC LIMIT 2";
                $res_status_anterior = pg_query($con, $sql_status_anterior);
                $status_anterior = pg_fetch_result($res_status_anterior, 1, 'status');
                if ($status_anterior == "DESCREDENCIADO") {
    				$login_posto = $posto;
    	            $sql_dados_posto = "
    	                SELECT  tbl_posto_fabrica.codigo_posto      AS posto_codigo,
    	                        tbl_posto.nome                      AS posto_nome,
    	                        tbl_posto.cnpj                      AS posto_cnpj,
    	                        tbl_posto_fabrica.categoria         AS posto_categoria,
    	                        tbl_posto_fabrica.contato_endereco  AS posto_endereco,
    	                        tbl_posto_fabrica.contato_numero    AS posto_numero,
    	                        tbl_posto_fabrica.contato_cep       AS posto_cep,
    	                        tbl_posto_fabrica.contato_bairro    AS posto_bairro,
    	                        tbl_posto_fabrica.contato_cidade    AS posto_cidade,
    	                        tbl_posto_fabrica.contato_estado    AS posto_estado,
    	                        tbl_posto_fabrica.contato_email     AS posto_email
    	                FROM    tbl_posto
    	                JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = {$login_fabrica} AND tbl_posto_fabrica.posto = tbl_posto.posto
    	                WHERE   tbl_posto.posto = {$login_posto}";

    	            $res_dados_posto = pg_query($con, $sql_dados_posto);
    	            if (pg_num_rows($res_dados_posto) > 0) {
    		            $posto_codigo       = pg_fetch_result($res_dados_posto, 0, "posto_codigo");
    		            $posto_nome         = pg_fetch_result($res_dados_posto, 0, "posto_nome");
    		            $posto_cnpj         = pg_fetch_result($res_dados_posto, 0, "posto_cnpj");
    		            $posto_endereco     = pg_fetch_result($res_dados_posto, 0, "posto_endereco");
    		            $posto_numero       = pg_fetch_result($res_dados_posto, 0, "posto_numero");
    		            $posto_cep          = pg_fetch_result($res_dados_posto, 0, "posto_cep");
    		            $posto_bairro       = pg_fetch_result($res_dados_posto, 0, "posto_bairro");
    		            $posto_cidade       = pg_fetch_result($res_dados_posto, 0, "posto_cidade");
    		            $posto_estado       = pg_fetch_result($res_dados_posto, 0, "posto_estado");
    		            $posto_categoria    = pg_fetch_result($res_dados_posto, 0, "posto_categoria");
    		            $posto_email        = pg_fetch_result($res_dados_posto, 0, "posto_email");

    		            $posto_endereco_completo = "";

    		            if(strlen($posto_endereco) > 0){
    		                $posto_endereco_completo .= $posto_endereco;
    		            }

    		            if(strlen($posto_numero) > 0){
    		                $posto_endereco_completo .= ", ".$posto_numero;
    		            }

    		            if(strlen($posto_cep) > 0){
    		                $posto_endereco_completo .= ", ".$posto_cep;
    		            }

    		            if(strlen($posto_bairro) > 0){
    		                $posto_endereco_completo .= ", ".$posto_bairro;
    		            }

    		            if(strlen($posto_cidade) > 0){
    		                $posto_endereco_completo .= ", ".$posto_cidade;
    		            }

    		            if(strlen($posto_estado) > 0){
    		                $posto_endereco_completo .= ", ".$posto_estado;
    		            }

    		            switch ($posto_categoria) {
    		                case "Locadora":
    		                case "Autorizada":
    		                case "Compra Peca":
    		                case "mega projeto":
    		                    imprimir($con,$login_fabrica,$login_posto,$posto_codigo,$posto_nome,$posto_categoria,$posto_endereco_completo,$posto_cnpj,$posto_email);
    		                    break;
    		                case "Locadora Autorizada":
    		                    imprimir($con,$login_fabrica,$login_posto,$posto_codigo,$posto_nome,"Locadora",$posto_endereco_completo,$posto_cnpj,$posto_email);
    		                    imprimir($con,$login_fabrica,$login_posto,$posto_codigo,$posto_nome,"Autorizada",$posto_endereco_completo,$posto_cnpj,$posto_email);
    		                    break;
    		            }
    		        }
                }
			}
			header ("Location: $PHP_SELF");
			exit;
		}else{
			$qtde_dias  = $_POST["qtde_dias"];
			$texto      = $_POST["texto"];
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

$title       = traduz("CREDENCIAMENTO E DESCREDENCIAMENTO DE POSTOS");
$cabecalho   = traduz("CREDENCIAMENTO E DESCREDENCIAMENTO DE POSTOS");
$layout_menu = "cadastro";


include "cabecalho_new.php";
	$plugins = array(
	"multiselect",
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");


?>

<link rel="stylesheet" href="js/blue/relatoriostyle.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script>
<script>
	$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

		$(".cancelamento_prestacao").click(function(){

			var posto = $("input[name='posto']").val(); 
			var codigo_posto = $("input[name='codigo']").val(); 

			Shadowbox.open({
	            content:"cancela_prestacao_servico.php?posto="+posto+"&codigo_posto="+codigo_posto,
	            player: "iframe",
	            title:  "Gerar Cancelamento de Prestação de Serviço",
	            width:  500,
	            height: 310
	        });


		/*	var motivo = prompt("Informe o motivo do cancelamento:");
			if (motivo == null) {
				return false;
			}
			*/

			/*$.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                data:{"descredenciamento": true, posto:posto, motivo:motivo, codigo_posto:codigo_posto},
                type: 'POST',
                beforeSend: function () {
                    $("#loading_pre_cadastro").show();
                    $(".cancelamento_prestacao").hide();
                },
                complete: function(data) {
                data = data.responseText;
                    if(data == 'enviado'){
                        alert('Posto enviado para descredenciamento');
                        location.reload();
                    }else{
                        alert('Falha ao enviar posto para descredenciamento.');
                    }
                }
            }); */

		})

	});
</script>

<!--
<link rel="stylesheet" href="js/blue/relatoriostyle.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script>
-->
<script type="text/javascript">
function fnc_pesquisa_codigo_posto (codigo, nome) {
    var url = "";
    if (codigo != "" && nome == "") {
        url = "pesquisa_posto.php?codigo=" + codigo;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_pesquisa_nome_posto (codigo, nome) {
    var url = "";
    if (codigo == "" && nome != "") {
        url = "pesquisa_posto.php?nome=" + nome;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_pesquisa_posto (campo, campo2, campo3, tipo) {

	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (tipo == "codigo" ) {
		var xcampo = campo3;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_credenciamento.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome		= campo;
		janela.posto	= campo2;
		janela.codigo	= campo3;
		janela.focus();
	} else {
		alert("<?php echo utf8_decode('Preencha toda ou parte da informação para realizar a pesquisa!' ) ; ?>");
		return false;

	}
}

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

$(function(){
	$.dataTableLoad({ table:'#relatorio'});
	// $("#posto_cidade").alpha({allow:" "});

	$.datepickerLoad(Array("data_final", "data_inicial"));
	$.autocompleteLoad(Array("produto", "peca", "posto"));

	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});
	$("#linha").multiselect({
		selectedText: "selecionados # de #"
	});
});

</script>



<? // include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>


<!--
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
-->


<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	/* Busca pelo Código */
	$("#codigo").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	// Quebra o JS da tela
    /*$("#codigo").result(function(event, data, formatted) {
		$("#nome").val(data[1]) ;
	});*/

	/* Busca pelo Nome */
	$("#nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	// Quebra o JS da tela
    /*$("#nome").result(function(event, data, formatted) {
		$("#codigo").val(data[2]) ;
		//alert(data[2]);
	});*/

});
</script>


<? if(strlen($msg_erro)>0) { ?>
<div class='alert alert-error'>
	<h4><? echo $msg_erro; ?></h4>
</div>
<?
		$controlgrup = "control-group error";
}else{
		$controlgrup = "control-group";

}
?>
<p>

<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<form name="frm_credenciamento" method="POST" class="form-search form-inline tc_formulario">

<div class="titulo_tabela"><?=traduz('Parâmetros de pesquisa')?></div>
<br />
<div class="row-fluid">
	<div class="span1"></div>
	<div class="span3">
		<div class="<? echo $controlgrup ?>">
			<label class="control-label" for="tipo_credenciamento"><?=traduz('Status')?></label>
			<div class="controls controls-row">
				<h5 class='asteristico'>*</h5>
				<select name='tipo_credenciamento' id='tipo_credenciamento' class='span12'>
					<?php 
						$sql = "SELECT DISTINCT credenciamento FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica ORDER BY CREDENCIAMENTO";
						$res = pg_query($con,$sql);

						for($i=0; $i<pg_num_rows($res); $i++){
							$credenciamento = pg_fetch_result($res, $i, credenciamento);
							$credenciamento_value = $credenciamento;
                            $selected = "";

                            if ($credenciamento_value == $tipo_credenciamento) {
                                $selected = "selected";
                            }

							if($credenciamento == 'Descred apr'){
								$credenciamento = "DESCREDENCIAMENTO - APROVADO";	
							}
							elseif($credenciamento == 'Descred rep'){
								$credenciamento = "DESCREDENCIAMENTO - REPROVADO";	
							}
							elseif($credenciamento == 'Pr&eacute; Cad apr'){
								$credenciamento = "PRÉ CADASTRO - APROVADO";	
							}
							elseif($credenciamento == 'pre_cadastro'){
								$credenciamento = "PRÉ CADASTRO";	
							}
							elseif($credenciamento == 'Pre Cadastro em apr'){
								$credenciamento = "PRÉ CADASTRO - EM APROVAÇÃO";	
							}
							elseif($credenciamento == 'Pr&eacute; Cad rpr'){
								$credenciamento = "PRÉ CADASTRO - REPROVADO";	
							}							
							echo "<option $selected value='$credenciamento_value'>".traduz($credenciamento)."</option>";
						}
					?>					
				</select>                
			</div>
		</div>
	</div>
	<!-- Botão Código Posto-->
	<div class="span3">
		<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
		<?
			if (strlen($codigo) > 0){
				$sql = "SELECT	tbl_posto.nome                ,
								tbl_posto.posto               ,
								tbl_posto_fabrica.credenciamento
						FROM tbl_posto_fabrica
						JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
						WHERE tbl_posto_fabrica.codigo_posto = '$codigo'
						AND   tbl_posto_fabrica.fabrica = $login_fabrica";
				$res = pg_exec($con,$sql);

				if (pg_numrows($res) > 0){
					$nome           = pg_result($res,0,nome);
					$posto          = pg_result($res,0,posto);
					$tipo_credenciamento = pg_result($res,0,credenciamento);
				}
				//echo $tipo_credenciamento;
			}

			?>

			<label class='control-label' for='codigo_posto'><?=traduz('Código Posto')?></label>
			<div class='controls controls-row'>
				<div class='span10 input-append'>

					<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
					<span class='add-on' rel="lupa">
						<i class='icon-search' ></i>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</span>

					<!--
					<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_credenciamento.nome,document.frm_credenciamento.posto,document.frm_credenciamento.codigo,'codigo')">
					-->
				</div>

			</div>
		</div>
	</div>
	<!-- Fim do botão -->

	<div class="span4">

		<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
			<label class='control-label' for='descricao_posto'><?=traduz('Razão Social')?></label>
			<div class='controls controls-row'>
				<div class='span12 input-append'>
					<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
					<span class='add-on' rel="lupa">
						<i class='icon-search' ></i>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</span>


					<!--
					<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_credenciamento.nome,document.frm_credenciamento.posto,document.frm_credenciamento.codigo,'nome')">
					-->
				</div>

			</div>
		</div>
	</div>
	<div class="span1"></div>
</div>

<input type="hidden" name="credenciamento" value="<? echo traduz($credenciamento) ?>">
<input type='hidden' name='btn_acao' value=''>
<input type="hidden" name="posto" value="<? echo $posto?>">
<p>
<br />

<br />
	 <!--HD 233213 - padronização: 	-->

	<!-- <input class="btn" type="button" onclick="javascript: if( document.getElementById('tipo_credenciamento').value == '' ){ alert( 'Escolha o status do posto' ); }else{ document.frm_credenciamento.submit(); }" value="Listar">
	<button class="btn" style="cursor: hand;text-align=center" onclick="javascript: if( document.getElementById('tipo_credenciamento').value == '' ){ document.frm_credenciamento.submit(); }else{ document.frm_credenciamento.submit(); }" >Listar</button> -->

	<input type="hidden" name="btn_listar"  value=''>
	<button class="btn" name="bt" value='<?=traduz("Listar")?>' onclick="javascript:if (document.frm_credenciamento.btn_listar.value!='') alert('Aguarde Submissão'); else{document.frm_credenciamento.btn_listar.value='Listar';document.frm_credenciamento.submit();}" >Listar</button>
<br />
<br />
</form>


<?
if (strlen($codigo) > 0 and strlen($nome) > 0 and strlen($tipo_credenciamento) > 0) $listar = 1;
if (strlen($tipo_credenciamento) > 0 ) $listar = 2;
if (strlen($codigo) > 0 and strlen($nome) > 0) $listar = 1;
?>
<br>
<?
#...................................      BUSCA PELO CODIGO/NOME DO POSTO     .................................#

if ($listar == 1) {

	$sql = "SELECT	tbl_posto.nome                  ,
					tbl_posto.cnpj                  ,
					tbl_posto_fabrica.codigo_posto  ,
					tbl_posto_fabrica.contato_estado,
					tbl_posto_fabrica.categoria,
					tbl_posto_fabrica.contato_cidade,
					tbl_posto_fabrica.contato_fone_comercial as fone,
					tbl_posto_fabrica.credenciamento,
					(SELECT to_char(tbl_credenciamento.data,'DD/MM/YYYY') from tbl_credenciamento where tbl_credenciamento.posto = tbl_posto_fabrica.posto order by data DESC limit 1) AS data
			FROM tbl_posto_fabrica
			JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE tbl_posto_fabrica.codigo_posto = '$codigo'
			AND   tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);

	$nome                = pg_result($res,0,nome);
	$codigo              = pg_result($res,0,codigo_posto);
	$categoria           = pg_result($res,0,categoria);
	$estado              = pg_result($res,0,contato_estado);
	$cidade              = pg_result($res,0,contato_cidade);
	$fone                = pg_result($res,0,fone);
	$tipo_credenciamento = pg_result($res,0,credenciamento);

	if($tipo_credenciamento == 'Pr&eacute; Cad apr' AND $login_fabrica == 1) {
       $tipo_credenciamento = "PRÉ CADASTRO - APROVADO";
    }

	$cnpj                = pg_result($res,0,cnpj);

	echo "<table class='table table-striped table-bordered table-hover table-fixed'>";
	echo "<thead>";
	echo "<tr class='titulo_coluna'>";
	if($login_fabrica == 19)	echo "<td>CNPJ</td>";
	echo "<td>".traduz("Posto")."</td>";
	echo "<td>".traduz("Cidade")."</td>";
	echo "<td>".traduz("Estado")."</td>";
	echo "<td>".traduz("Fone")."</td>";
	if ($login_fabrica == 50) echo "<td>Data</td>";
	echo "</tr>";
	echo "</thead>";
	echo "<tbody>";
	//$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
	echo "<tr>";
	if($login_fabrica == 19) echo "<td>$cnpj</td>";
	echo "<td>$codigo - $nome</td>";
	echo "<td>$cidade</td>";
	echo "<td>$estado</td>";
	echo "<td>$fone</td>";
	if($login_fabrica == 50) echo "<td>$data</td>";
	echo "</tr>";
	echo "</tbody>";
	echo "</table>";
	echo "<br>";

	$sql = "SELECT	tbl_credenciamento.status,
					tbl_credenciamento.dias  ,
					tbl_credenciamento.texto ,
					tbl_admin.nome_completo,
					to_char(tbl_credenciamento.data,'DD/MM/YYYY') AS data
			FROM	tbl_credenciamento LEFT JOIN tbl_admin on tbl_credenciamento.confirmacao_admin = tbl_admin.admin
			WHERE	tbl_credenciamento.fabrica = $login_fabrica
			AND		tbl_credenciamento.posto   = $posto
			ORDER BY tbl_credenciamento.credenciamento DESC";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0){
		echo "<table class='table table-striped table-bordered table-hover table-fixed'>";
		echo "<thead>";
		echo "<tr class='titulo_tabela'>";
		echo "<td colspan='5'>".traduz("Histórico dos Status dos Postos")."</td></tr></thead><tr><td style='border:0px solid !important' colspan='5'>";
		/* hd 153618 Tulio pediu para voltar somente para a Britania */
		if ($login_fabrica != 3) {
			echo "
			<div style='margin: auto 2.5cm;text-align: justify;'>
			<p>
				".traduz("Para que sejam respeitados os acordos entre o Fabricante e o Posto
				Autorizado, a TELECONTROL deixou de executar automaticamente o
				CREDENCIAMENTO e DESCREDENCIAMENTO dos postos.  Devido ao fato de
				que  alguns contratos dependem dos Correios  ou transportadoras,  o
				trabalho de CREDENCIAMENTO dever&aacute; ser feito pelo
				respons&aacute;vel indicado pela F&aacute;brica, assim que estiver
				com toda documenta&ccedil;&atilde;o em m&atilde;os, ou no caso de
				DESCREDENCIAMENTO, quando as Ordens de Servi&ccedil;os estiverm
				resolvidas.")."
			</p>
			<p>".traduz("Contamos com a compreens&atilde;o de todos!")."</p>
			<p style='text-align:right;font-style:italic'>".traduz("Suporte Telecontrol")."</div>
			</div>
			";
		}
		echo "</td>";
		echo "</tr>";
		echo "<thead>";
		echo "<tr class='titulo_tabela' align='center'>";
		echo "<td>".traduz("Data de Gera&ccedil;&atilde;o")."</td>";
		echo "<td>".traduz("Status")."</td>";
		echo "<td>".traduz("Qtde Dias")."</td>";
		echo "<td>".traduz("Observa&ccedil;&atilde;o")."</td>";
		echo "<td width='40%'>Admin</td>";
		echo "</tr>";
		echo "</thead>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++){
			$status       = ucwords(pg_result($res, $i, 'status'));
			$mdias        = pg_result($res, $i, 'dias');
			$data_geracao = pg_result($res, $i, 'data');
			$mtexto       = pg_result($res, $i, 'texto');
            if (mb_check_encoding($mtexto, 'UTF-8')) {
                $mtexto = utf8_decode($mtexto);      
            }
			$admin        = pg_result($res, $i, 'nome_completo');
			$admin = (strlen($admin) > 0) ? $admin : "Automático";
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			echo "<tr align='center' style='background-color:$cor' >";
			echo "<td>$data_geracao</td>";
			echo "<td>".traduz($status)."</td>";
			echo "<td>$mdias</td>";
			echo "<td>$mtexto</td>";
			echo "<td>$admin</td>";
			echo "</tr>";
		}

		echo "</table>";

		echo "<br>";
	}

	$sql = "SELECT	tbl_credenciamento.status,
					tbl_credenciamento.dias  ,
					tbl_credenciamento.texto ,
					TO_CHAR(tbl_credenciamento.data,'YYYY-MM-DD') AS data,
					tbl_posto.nome
			FROM	tbl_credenciamento
			JOIN    tbl_posto ON tbl_posto.posto = tbl_credenciamento.posto
			WHERE	tbl_credenciamento.fabrica = $login_fabrica
			AND		tbl_credenciamento.posto   = $posto
			ORDER BY tbl_credenciamento.credenciamento DESC LIMIT 1";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0){
		$status       = pg_result($res,0,status);
		$xdias        = pg_result($res,0,dias);
		$data_geracao = pg_result($res,0,data);
		$xtexto       = pg_result($res,0,texto);
		$razao_social = pg_result($res,0,nome);

		if ($status == 'EM CREDENCIAMENTO' OR $status == 'EM DESCREDENCIAMENTO'){

			$sqlX = "SELECT '$data_geracao':: date + interval '$xdias days';";
			$resX = pg_exec ($con,$sqlX);
			$dt_expira = pg_result ($resX,0,0);

			$sqlX = "SELECT '$dt_expira'::date - current_date;";
			$resX = pg_exec ($con,$sqlX);

			$dt_expira = substr ($dt_expira,8,2) . "-" . substr ($dt_expira,5,2) . "-" . substr ($dt_expira,0,4);
			$dia_hoje= pg_result ($resX,0,0);

			echo "<table class='table table-striped table-bordered table-hover table-fixed'>";
			echo "<thead>";
			echo "<tr class='titulo_coluna'><td colspan='3'>".traduz("O POSTO % DEVERÁ PERMANECER % <br> ATÉ O DIA % (RESTAM % DIAS)", null,null,[$razao_social,$status,$dt_expira,$dia_hoje])."</td></tr></thead>";
			echo "</table>";
		}
	}
	echo "<form class='form-search form-inline tc_formulario' name='frm_credenciamento_2' method='POST' action='$PHP_SELF'>";
	echo "<input type='hidden' name='posto' value='$posto'>";
	echo "<input type='hidden' name='codigo' value='$codigo'>";
	echo "<input type='hidden' name='listar' value='1'>";

	if($login_fabrica == 1 and ($categoria == 'Pr&eacute; Cadastro' or $categoria == 'Pré Cadastro') and $tipo_credenciamento == "PRÉ CADASTRO - APROVADO"){
	?>
	<br>
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span4">
			<div class="control-group <?=$campos_erro?>">
			<label class="control-label" for="categoria_posto"><?=traduz('Categoria')?></label>
			<div class="controls controls-row">
				<h5 class='asteristico'>*</h5>
				<input type='hidden' name='categoria_posto_atual' value="<?=$categoria?>">
				<select name='categoria_posto' id='categoria_posto' class='span12'>
					<option value=""></option>
                    <option value="Autorizada" <?=$checkedA?>><?=traduz('Autorizada')?></option>
                    <option value="Locadora" <?=$checkedL?>><?=traduz('Locadora')?></option>
                    <option value="Locadora Autorizada" <?=$checkedAL?>><?=traduz('Locadora Autorizada')?></option>
                    <option value="Pr&eacute; Cadastro" <?=$checkedPC?> ><?=traduz('Pré Cadastro')?></option>
                    <option value="mega projeto" <?=$checkedMP?>><?=traduz('Industria/Mega Projeto')?></option>
				</select>
			</div>
			</div>
		</div>
		<div class="span4"></div>
	</div>
	<?php
	}
	if ($tipo_credenciamento == 'CREDENCIADO'){ ?>

		<div class="titulo_tabela "><?=traduz('Descredenciar / Colocar em Descredenciamento')?></div>
		<br />
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span2"><?=traduz('Qtde Dias para Descredenciar:')?></div>
			<div class="span6">
				<input class="span2" type='text' name='qtde_dias' value='<? $qtde_dias ?>' size='3' maxlength='5'>* <?=traduz('Obrigat&oacute;rio para status \"Em Descredenciamento\"')?>
			</div>

			<div class="span2"></div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span2"><?=traduz('Observações:')?></div>
			<div class="span6">
				<textarea class="span12" name='texto' rows='3' cols='50'><? $texto ?></textarea>
			</div>
			<div class="span2"></div>
		</div>
		<?
		if ($login_fabrica == 1) { ?>
		<br /><br />
			<div class="row-fluid">
				<div class="span3"></div>
				<div class="span3">
					<div class="control-group ">
						<label class="control-label" for="ie"><?=traduz('Digita OS')?></label>
						<div class="controls controls-row">
							<div class="span12 ">
								<input type='radio' name='manutencao_os' value='t' checked> <?=traduz('SIM')?>
								<input type='radio' name='manutencao_os' value='f'> <?=traduz('NÂO')?>
							</div>
						</div>
					</div>
				</div>
			<div class="span3">
				<div class="control-group ">
					<label class="control-label" for="ie"><?=traduz('Pedido Faturado (Manual)')?></label>
					<div class="controls controls-row">
						<div class="span12 ">
							<input type='radio' name='manutencao_pedido' value='t' checked> <?=traduz('SIM')?> &nbsp;
							<input type='radio' name='manutencao_pedido' value='f'> <?=traduz('NÂO')?>
						</div>
					</div>
				</div>
			</div>
			<div class="span3"></div>
			</div>
	<?php } ?>
		<p>
			<br /><br />
			<input type='submit' class='btn' name='btn_acao' value='<?=traduz("Descredenciar")?>'>
			<?php if (in_array($login_fabrica, [203])) { ?>
			<input type='submit' class='btn' name='btn_acao' value='<?=traduz("Em Credenciamento")?>'>
			<?php } ?>
			<input type='submit' class='btn' name='btn_acao' value='<?=traduz("Em Descredenciamento")?>'>
			<input type='submit' class='btn' name='btn_acao' value='<?=traduz("Cadastro Reprovado")?>'><br>
			<?php if($login_fabrica == 1){ ?>
			<input type='button' class='btn cancelamento_prestacao' name='btn_acao' value='Gerar Cancelamento de Prestação de Serviço'>
			 <img src="imagens/loading_img.gif" style="display: none; height: 20px; width: 20px;" id="loading_pre_cadastro" /> 
			<?php } ?>
		</p>
		<br />

		<?
	}else if ($tipo_credenciamento == 'DESCREDENCIADO' or $tipo_credenciamento == 'Descred apr' ){
            if ($login_fabrica == 1 && $tipo_credenciamento == 'Descred apr') {?>
                <div class="titulo_tabela "><?=traduz('Descredenciar / Colocar em Descredenciamento')?></div>
                <br />
                <div class="row-fluid">
                    <div class="span2"></div>
                    <div class="span2"><?=traduz('Qtde Dias para Descredenciar:')?></div>
                    <div class="span6">
                        <input class="span2" type='text' name='qtde_dias' value='<? $qtde_dias ?>' size='3' maxlength='5'>* <?=traduz('Obrigat&oacute;rio para status \"Em Descredenciamento\"')?>
                    </div>

                    <div class="span2"></div>
                </div>
                <div class="row-fluid">
                    <div class="span2"></div>
                    <div class="span2"><?=traduz('Observações:')?></div>
                    <div class="span6">
                        <textarea class="span12" name='texto' rows='3' cols='50'><? $texto ?></textarea>
                    </div>
                    <div class="span2"></div>
                </div>  
                <br /><br />
                    <div class="row-fluid">
                        <div class="span3"></div>
                        <div class="span3">
                            <div class="control-group ">
                                <label class="control-label" for="ie"><?=traduz('Digita OS')?></label>
                                <div class="controls controls-row">
                                    <div class="span12 ">
                                        <input type='radio' name='manutencao_os' value='t' checked> <?=traduz('SIM')?>
                                        <input type='radio' name='manutencao_os' value='f'> <?=traduz('NÂO')?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <div class="span3">
                        <div class="control-group ">
                            <label class="control-label" for="ie"><?=traduz('Pedido Faturado (Manual)')?></label>
                            <div class="controls controls-row">
                                <div class="span12 ">
                                    <input type='radio' name='manutencao_pedido' value='t' checked> <?=traduz('SIM')?> &nbsp;
                                    <input type='radio' name='manutencao_pedido' value='f'> <?=traduz('NÂO')?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span3"></div>
                    </div>  
                <p>
                    <br /><br />
                    <input type='submit' class='btn' name='btn_acao' value='<?=traduz("Descredenciar")?>'>
                    <input type='submit' class='btn' name='btn_acao' value='<?=traduz("Em Descredenciamento")?>'>      
                </p>
                <br />
            <? } else { ?>
        		<div class="titulo_tabela "><?=traduz('Credenciar / Colocar em Credenciamento')?></div>
        		<br />
        		<div class="row-fluid">
        			<div class="span1"></div>
        			<div class="span3"><?=traduz('QTDE DIAS PARA CREDENCIAR:')?></div>
        			<div class="span8">
        				<input type='text' name='qtde_dias' value='<?$qtde_dias?>' size='3' maxlength='5'>
        		* <?=traduz('Obrigatório para status "Em Credenciamento"')?>
        			</div>
        		</div>

        		<div class="row-fluid">
        			<div class="span1"></div>
        			<div class="span3"><?=traduz('Observações:')?></div>
        			<div class="span8">
        				<textarea name='texto' rows='3' cols='500'><?php $texto?></textarea>
        			</div>
        		</div>

        		<p>
        		<br /> <br />
        			<input type='submit' class='btn' name='btn_acao' value='<?=traduz("Credenciar")?>'>

        			<input type='submit' class='btn' name='btn_acao' value='<?=traduz("Em Credenciamento")?>'>
        		</p>
        		<br />
        <?  } 
	} else if ($tipo_credenciamento == 'REPROVADO' or $tipo_credenciamento =='Descred rep') {
		?>
		<div class="titulo_tabela "><?=traduz('CREDENCIAR / COLOCAR EM CREDENCIAMENTO')?></div>
		<br />
		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span2"><?=traduz('QTDE DIAS PARA CREDENCIAR:')?></div>
			<div class="span9">
				<input type='text' name='qtde_dias' value='<?$qtde_dias?>' size='3' maxlength='5'>
		(*) <?=traduz('Obrigatório para status "EM CREDENCIAMENTO"')?>
			</div>
		</div>

		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span2"><?=traduz('Observações:')?></div>
			<div class="span9">
				<textarea name='texto' rows='3' cols='500'><?php $texto?></textarea>
			</div>
		</div>

		<p>
		<br /> <br />
			<input type='submit' class='btn' name='btn_acao' value='Credenciar'>
			<input type='submit' class='btn' name='btn_acao' value='Em Credenciamento'>
		</p>
		<br />

<?
	} else if (strtoupper($tipo_credenciamento) == 'EM CREDENCIAMENTO' OR strtoupper($tipo_credenciamento) == 'EM DESCREDENCIAMENTO' OR $tipo_credenciamento == "PRÉ CADASTRO - APROVADO"){
		if($login_fabrica==45 AND $tipo_credenciamento == 'EM CREDENCIAMENTO'){ //HD 50730 19/11/2008
			?>
			<div class="titulo_tabela "><?=traduz('DESCREDENCIAR / COLOCAR EM DESCREDENCIAMENTO')?></div>
			<br />
			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span2"><?=traduz('QTDE DIAS PARA DESCREDENCIAR:')?></div>
				<div class="span9">
					<input class="span1" type='text' name='qtde_dias' value='<?$qtde_dias?>' size='3' maxlength='5'>
			(*) <?=traduz('Obrigatório para status "EM DESCREDENCIAMENTO"')?>
				</div>
			</div>

			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span2"><?=traduz('OBSERVAÇÕES:')?></div>
				<div class="span9">
					<textarea name='texto' rows='3' cols='500'><?php $texto?></textarea>
				</div>
			</div>

			<p>
			<br /> <br />
				<input type='submit' class='btn' name='btn_acao' value='<?=traduz("Credenciar")?>'>
				<input type='submit' class='btn' name='btn_acao' value='<?=traduz("Em Descredenciamento")?>'>
				<input type='submit' class='btn' name='btn_acao' value='<?=traduz("Descredenciar")?>'>
			</p>
			<br />

	<?
		}else if(in_array($login_fabrica, [45,186]) AND $tipo_credenciamento == 'EM DESCREDENCIAMENTO'){
	?>

		<div class="titulo_tabela "><?=traduz('DESCREDENCIAR / COLOCAR EM DESCREDENCIAMENTO')?></div>
		<br />
		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span2"><?=traduz('QTDE DIAS PARA DESCREDENCIAR:')?></div>
			<div class="span9">
				<input type='text' name='qtde_dias' value='<?$qtde_dias?>' size='3' maxlength='5'>
		(*) <?=traduz('Obrigatório para status "EM DESCREDENCIAMENTO"')?>
			</div>
		</div>

		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span2"><?=traduz('OBSERVAÇÕES:')?></div>
			<div class="span9">
				<textarea name='texto' rows='3' cols='500'><?php $texto?></textarea>
			</div>
		</div>

		<p>
		<br /> <br />
			<input type='submit' class='btn' name='btn_acao' value='<?=traduz("Credenciar")?>'>
			<input type='submit' class='btn' name='btn_acao' value='<?=traduz("Em Credenciamento")?>'>
			<input type='submit' class='btn' name='btn_acao' value='<?=traduz("Descredenciar")?>'>
		</p>
		<br />
	<?
		}else{
			?>
			<p>
				<br />
				<input type='submit' class='btn' name='btn_acao' value='<?=traduz("Credenciar")?>'>
				<input type='submit' class='btn' name='btn_acao' value='<?=traduz("Descredenciar")?>'>
			</p>
			<br />
		<?
		}
	}

	if($login_fabrica == 1 and $tipo_credenciamento == 'Descred rep'){ ?>
	<input type='button' class='btn cancelamento_prestacao' name='btn_acao' value='Gerar Cancelamento de Prestação de Serviço'>
	<img src="imagens/loading_img.gif" style="display: none; height: 20px; width: 20px;" id="loading_pre_cadastro" /> 
	<?php } 

}
#...................................      BUSCA PELO COMBOBOX     .................................#
else if ($listar == 2) {

	if ( $tipo_credenciamento == '' ) {
		$msg_erro = traduz('Digite o Status do Posto');
		return;
	}

	// Dados devem vir da tbl_credenciamento
	$sql = "SET DATESTYLE TO 'SQL, DMY'; -- Datas em formato europeu, dispensa TO_CHAR para campos DATE
		    SELECT tbl_posto.posto,
		           tbl_posto.cnpj,
		           tbl_posto_fabrica.codigo_posto,
		           tbl_posto.nome,
		           tbl_posto_fabrica.contato_cidade         AS cidade,
		           tbl_posto_fabrica.contato_estado         AS estado,
		           tbl_posto_fabrica.contato_fone_comercial AS fone,
		           tbl_posto_fabrica.obs                    AS obs_fabrica,
		           ultimo_admin.nome_completo               AS ultimo_admin_nome,
		           ultimo_admin.admin                       AS ultimo_admin,
		           -- Dados da tbl_credenciamento
		           CASE WHEN tbl_posto_fabrica.fabrica = 20
		                THEN tbl_credenciamento.dias - (CURRENT_DATE - tbl_credenciamento.data::DATE)
		                WHEN tbl_posto_fabrica.fabrica = 151
		                THEN CURRENT_DATE - tbl_credenciamento.data::DATE
		                ELSE tbl_credenciamento.dias
		           END AS qtde_dias,
		           tbl_credenciamento.data              AS data_geracao,
		           tbl_credenciamento.status            AS credenciamento,
		           tbl_credenciamento.confirmacao,
		           admin_credenciamento.nome_completo   AS administrador,
		           tbl_credenciamento.texto             AS obs_credenciamento
		      FROM tbl_posto_fabrica
		      JOIN tbl_posto          USING(posto)
		 LEFT JOIN tbl_credenciamento USING(posto,fabrica)
		 LEFT JOIN tbl_admin AS ultimo_admin         ON ultimo_admin.admin         = tbl_posto_fabrica.admin
		 LEFT JOIN tbl_admin AS admin_credenciamento ON admin_credenciamento.admin = tbl_credenciamento.confirmacao_admin
		     WHERE tbl_posto_fabrica.fabrica        = $login_fabrica
		       AND tbl_posto_fabrica.credenciamento = '$tipo_credenciamento'
		  ORDER BY estado, cidade, tbl_posto.nome, tbl_credenciamento.data DESC";
    $res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
        $tableFoot = '';

		$cabecalho = array(
			traduz('CNPJ'),
			traduz('Posto'),
			traduz('Razão Social'),
			traduz('Cidade'),
			traduz('Estado'),
			traduz('Fone'),
		);

		if (!isFabrica(19))
			array_shift($cabecalho);

		if ($login_fabrica == 50)
			$cabecalho[] = 'Data';

		if (isFabrica(20) and $tipo_credenciamento == 'EM DESCREDENCIAMENTO')
			$cabecalho[] = 'Qtd. Dias';

		if (isFabrica(151)) {
			$fileDate = date("d-m-Y-H-i");
			$fileName = "csv_credenciamento_{$fileDate}.csv";

			$csvHeader = array(
				"Código do Posto",
				"Razão Social",
				"Cidade",
				"Estado",
				"Data de Geração",
				"Status",
				"Qtd. Dias",
				"Observações",
				"Administrador"
			);

		}

		$prevRow      = array();
		$CNPJanterior = '';

		// Atributos da tabela, para o array2table()
		$tableAttrs = array(
			'headers' => $cabecalho,
			'tableAttrs' => "name='relatorio' id='relatorio' class='table table-striped table-bordered table-hover table-fixed'",
		);

		while ($row = pg_fetch_assoc($res)) {
			$posto              = $row['posto'];
			$codigo             = $row['codigo_posto'];
			$razao_social       = $row['nome'];
			$cidade             = $row['cidade'];
			$estado             = $row['estado'];
			$fone               = $row['fone'];
			$obs                = $row['obs'];
			$status_cred        = $row['credenciamento'];
			$cnpj               = $row['cnpj'];
			$admin              = $row['admin'];
			$ultimo_admin       = $row['ultimo_admin_nome'];
			$administrador      = $row['administrador'];
			$obs_credenciamento = $row['obs_credenciamento'];
			$data_geracao       = substr($row['data_geracao'], 0, 19);
			$data               = $data_geracao;
			$qtde_dias          = $row['qtde_dias'];

			$tableRow = [
				$cnpj, $codigo,
				sprintf(
					'<a href="?posto=%s&codigo=%s&listar=1">%s</a>',
					$posto, $codigo, $razao_social
				),
				$cidade, $estado, $fone,
			];

			// Apenas Lorenzetti mostra o CNPJ em tela
			if (!isFabrica(19))
				array_shift($tableRow);

			// Esmaltec mostra a data de geração
			if (isFabrica(50))
				$tableRow[] = $data;

			if (in_array($login_fabrica, array(20)) AND $tipo_credenciamento == "EM DESCREDENCIAMENTO") {
				$html_dias = $qtde_dias > -1
					? $qtde_dias
					: "<span style='color:red'>$qtde_dias</span>";
				$tableRow[] = $qtde_dias;
			}

			if (isFabrica(151) and $CNPJanterior !== $cnpj) {
                $csvData[] = array_combine(
                    $csvHeader, [
                        $codigo,
                        $razao_social,
                        $cidade,
                        $estado,
                        $data_geracao,
                        $status_cred,
                        $qtde_dias,
                        $obs,
                        $administrador ? : 'Automático'
                    ]
				);
			}
			$rowArray = array_combine($cabecalho, $tableRow);

			// Evita duplicados na tabela
			if (count(array_diff_assoc($rowArray, $prevRow))) {
				$tableData[] = $rowArray;
				$prevRow = $rowArray;
			}
            $CNPJanterior = $row['cnpj'];
		}

		# HD 32761 - Francisco Ambrozio (20/8/08)
		#   Incluído total de postos para Colormaq
		#   MLG 2018-02-06 - refatoração da tabela
		if ($login_fabrica == 50) {
			$totValue = count($tableData);
			$totText  = "TOTAL DE POSTOS $tipo_credenciamento";
			$totText .= $tipo_credenciamento[0] == 'E' ? '' : 'S';
			$totalRow = "
			<tr>
				<td colspan='4'>$totText</td>
				<td align='center'><strong>$totValue</strong></td>
			</tr>
			";
		}

		if (count($tableData) and isFabrica(151)) {
			file_put_contents("/tmp/$fileName", array2csv($csvData, ';', true, false));

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				$tableFoot = <<<TFOOT
            <tfoot>
                <tr>
                    <td colspan="15" class="tac">
                        <a class="btn btn-success" href="xls/$fileName" target="_blank" role="button">Gerar Arquivo CSV</a>
                    </td>
                </tr>
            </tfoot>

TFOOT;
			}

			// usar array2table e hacer un replace de '</table>' con el total e con el tfoot
		}

		$htmlTable = array2table($tableData);
		echo str_replace("</table>", $totalRow . $tableFoot . '</table>', $htmlTable);
	}
}
?>

<p>
</form>

<? include "rodape.php";

