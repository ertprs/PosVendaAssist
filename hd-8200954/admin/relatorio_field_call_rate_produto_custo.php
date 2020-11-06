<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if($login_fabrica == 20) $admin_privilegios="gerencia";
else $admin_privilegios="financeiro";

include "autentica_admin.php";
include 'funcoes.php';

#Validação da data
	function checkDateTc($data){
		global $con;

		$d = explode ("/", $data);//tira a barra
		$nova_data = "$d[2]-$d[1]-$d[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		$sql = "SELECT '$nova_data'::date";
		@$res = pg_query($con, $sql);
		if (pg_errormessage($con)) {
			return 0;
		}
		else {
			return 1;
		}
	}
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

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}



/*if(1==1){
	header("Location: menu_callcenter.php");
exit;
}
*/


if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0) $btn_acao = trim($_GET["btn_acao"]);



if(strlen($btn_acao) > 0 AND count($msg_erro) == 0) {


	if (strlen(trim($_POST["data_inicial"])) > 0) $data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0) $data_inicial = trim($_GET["data_inicial"]);

	$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

	if (strlen ( pg_errormessage ($con) ) > 0) {
		$msg_erro["msg"][] = "Erro ao processar relatório" ;
	}

	if(count($msg_erro) == 0){
		if(checkDateTc($data_inicial)==0){
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["campos"][] = "data_final";
			$msg_erro["msg"][] = traduz("Data Inválida");
		}
	}

	if(count($msg_erro) == 0){
		if(checkDateTc($data_final)==0){
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["campos"][] = "data_final";
			$msg_erro["msg"][] = traduz("Data Inválida");
		}
	}

	if (count($msg_erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);

	if (strlen(trim($_POST["data_final"])) > 0) $data_final = trim($_POST["data_final"]);
	if (strlen(trim($_GET["data_final"])) > 0) $data_final = trim($_GET["data_final"]);

	$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");

	if (strlen ( pg_errormessage ($con) ) > 0) {
		$msg_erro["msg"][] = "Erro ao processar relatório" ;
	}

	if (count($msg_erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);

	if (strlen(trim($_POST["linha"])) > 0) $linha = trim($_POST["linha"]);
	if (strlen(trim($_GET["linha"])) > 0) $linha = trim($_GET["linha"]);
	if(isset($_GET["linha"])){
		$linha = $_GET["linha"];
	}
	if(isset($_POST["linha"])){
			if(count($linha)>0){
				$linha = $_POST["linha"];
			}
	}

	if(strlen($_GET["estado"]) > 0){

		if (strlen(trim($_POST["estado"])) > 0) $estado = trim($_POST["estado"]);
		if (strlen(trim($_GET["estado"])) > 0) $estado = trim($_GET["estado"]);

		$mostraMsgEstado = "<br>no ESTADO $estado";
	}

	if (strlen(trim($_POST["produto_referencia"])) > 0) $produto_referencia = trim($_POST["produto_referencia"]);
	if (strlen(trim($_GET["produto_referencia"])) > 0) $produto_referencia = trim($_GET["produto_referencia"]);

	if (strlen(trim($_POST["produto_descricao"])) > 0) $produto_descricao = trim($_POST["produto_descricao"]);
	if (strlen(trim($_GET["produto_descricao"])) > 0) $produto_descricao = trim($_GET["produto_descricao"]);


	if(strlen($produto_referencia)>0 and strlen($produto_descricao)>0){ // HD 2003 TAKASHI
		$sql = "SELECT produto
				from tbl_produto
				join tbl_familia using(familia)
				where tbl_familia.fabrica = $login_fabrica
				and tbl_produto.referencia = '$produto_referencia'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,produto);
		}
	}

	if(count($msg_erro) == 0){
		if(strlen($aux_data_incial)==0 AND strlen($aux_data_final)==0){
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["campos"][] = "data_final";
			$msg_erro["msg"][] = traduz("Data Inválida");
		}

	}

	if(strlen($aux_data_incial)>0 AND strlen($aux_data_final)>0){
		$sql = "select '$aux_data_final'::date - '$aux_data_inicial'::date ";
		$res = pg_exec($con,$sql);
		if(pg_result($res,0,0)>30){
			$msg_erro["msg"][] = traduz("Período não pode ser maior que 30 dias");
		}
	}

	//Converte data para comparação
	$d_ini = explode ("/", $data_inicial);//tira a barra
	$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

	$d_fim = explode ("/", $data_final);//tira a barra
	$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

	if(count($msg_erro) == 0){
		if($nova_data_inicial > $nova_data_final){
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["campos"][] = "data_final";
			$msg_erro["msg"][]= traduz("Data Inválida");
		}
	}

	$codigo_posto = trim($_GET['codigo_posto']);
	if(strlen($codigo_posto)>0 ){
		$sql = "SELECT posto
			FROM tbl_posto_fabrica
			WHERE fabrica = $login_fabrica
			AND codigo_posto = '$codigo_posto'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,posto);
		}
	}

	if (strlen(trim($_POST["criterio"])) > 0) $criterio = trim($_POST["criterio"]);
	if (strlen(trim($_GET["criterio"])) > 0) $criterio = trim($_GET["criterio"]);

	if (strlen(trim($_POST["tipo_atendimento"])) > 0) $tipo_atendimento = trim($_POST["tipo_atendimento"]);
	if (strlen(trim($_GET["tipo_atendimento"])) > 0) $tipo_atendimento = trim($_GET["tipo_atendimento"]);

	if (strlen(trim($_POST["familia"])) > 0) $familia = trim($_POST["familia"]);
	if (strlen(trim($_GET["familia"])) > 0) $familia = trim($_GET["familia"]);

	if (strlen(trim($_POST["origem"])) > 0) $origem = trim($_POST["origem"]);
	if (strlen(trim($_GET["origem"])) > 0) $origem = trim($_GET["origem"]);

	if (strlen(trim($_POST["serie_inicial"])) > 0) $serie_inicial = trim($_POST["serie_inicial"]);
	if (strlen(trim($_GET["serie_inicial"])) > 0) $serie_inicial = trim($_GET["serie_inicial"]);

	if (strlen(trim($_POST["serie_final"])) > 0) $serie_final = trim($_POST["serie_final"]);
	if (strlen(trim($_GET["serie_final"])) > 0) $serie_final = trim($_GET["serie_final"]);


	if(strlen($serie_inicial) > 0 AND strlen($serie_final)>0 AND ($serie_final - $serie_inicial < 13)) {

		for($x = $serie_inicial ; $x <= $serie_final ; $x++){
			if($x == $serie_final) $aux = "$aux'$x'";
			else                   $aux = "'$x',".$aux;

		}
	}
	// HD 16694
	if (strlen(trim($_POST["extrato"])) > 0) $extrato = trim($_POST["extrato"]);
	if (strlen(trim($_GET["extrato"])) > 0) $extrato = trim($_GET["extrato"]);

}

$layout_menu = "financeiro";
if ($login_fabrica == 117) {
	$title = traduz("RELATÓRIO CUSTO - FIELD CALL-RATE : Macro-Família DE PRODUTO");
}else{
	$title = traduz("RELATÓRIO CUSTO - FIELD CALL-RATE : LINHA DE PRODUTO");
}


include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"multiselect"
);

include("plugin_loader.php");

?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$.datepickerLoad(Array("data_final", "data_inicial"));

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		Shadowbox.init();

		$("#linha").multiselect({
        	selectedText: "selecionados # de #"
        });
	});
</script>

<script language="JavaScript">
	$(function() {
		

		function formatItem(row) {
			return row[2] + " - " + row[1];
		}

		function formatResult(row) {
			return row[2];
		}

	});

	function retorna_produto (retorno) {
		$("#produto").val(retorno.produto);
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}
</script>


<script language='javascript' src='../ajax.js'></script>
<script language='javascript'>

function retornaPesquisa (http,componente,componente_erro,componente_carregando) {
	var com = document.getElementById(componente);
	var com2 = document.getElementById(componente_erro);
	var com3 = document.getElementById(componente_carregando);
	if (http.readyState == 1) {

		Page.getPageCenterX() ;
		com3.style.top = (Page.top + Page.height/2)-100;
		com3.style.left = Page.width/2-75;
		com3.style.position = "absolute";

		com3.innerHTML   = "&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='../imagens/carregar_os.gif' >";
		com3.style.visibility = "visible";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			//alert(http.responseText);
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = " "+results[1];
					com2.innerHTML  = " ";
					com2.style.visibility = "hidden";
					com3.innerHTML = "<br>&nbsp;&nbsp;Dados carregadas com sucesso!&nbsp;&nbsp;<br>&nbsp;&nbsp;";
					setTimeout('esconde_carregar()',3000);
				}
				if (results[0] == 'no') {
					com2.innerHTML   = " "+results[1];
					com.innerHTML   = " ";
					com2.style.visibility = "visible";
					com3.style.visibility = "hidden";
				}

			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}
function esconde_carregar(componente_carregando) {
	document.getElementById('carregando').style.visibility = "hidden";
}

function Exibir (componente,componente_erro,componente_carregando) {

	var1 = document.frm_relatorio.data_inicial.value;
	var2 = document.frm_relatorio.data_final.value;
	var3 = document.frm_relatorio.linha.value;
	var4 = document.frm_relatorio.estado.value;
	var5 = document.frm_relatorio.produto_referencia.value;
	var6 = document.frm_relatorio.produto_descricao.value;
	//var12 = document.frm_relatorio.codigo_posto.value;
<?if($login_fabrica == 20){?>
	var7 = document.frm_relatorio.tipo_atendimento.value;
	var8 = document.frm_relatorio.familia.value;
	var9 = document.frm_relatorio.origem.value;
	var10= document.frm_relatorio.serie_inicial.value;
	var11= document.frm_relatorio.serie_final.value;
<?}?>

/*parametros = 'data_inicial_01='+var1+'&data_final_01='+var2+'&linha='+var3+'&estado='+var4;*/
	parametros = '';
	parametros = 'data_inicial_01='+var1+'&data_final_01='+var2+'&linha='+var3+'&estado='+var4+'&produto_referencia='+var5+'&produto_descricao='+var6+'&ajax=sim';
<?if($login_fabrica ==20){?>
	parametros = parametros + '&tipo_atendimento='+var7+'&familia='+var8+'&origem='+var9+'&serie_inicial='+var10+'&serie_final='+var10;
<?}?>
	url = "<?=$PHP_SELF?>?ajax=sim&"+parametros;

	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaPesquisa (http,componente,componente_erro,componente_carregando) ; } ;
	http.send(null);
}

var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.getPageCenterX = function (){

	var fWidth;
	var fHeight;
	//For old IE browsers
	if(document.all) {
		fWidth = document.body.clientWidth;
		fHeight = document.body.clientHeight;
	}
	//For DOM1 browsers
	else if(document.getElementById &&!document.all){
			fWidth = innerWidth;
			fHeight = innerHeight;
		}
		else if(document.getElementById) {
				fWidth = innerWidth;
				fHeight = innerHeight;
			}
			//For Opera
			else if (is.op) {
					fWidth = innerWidth;
					fHeight = innerHeight;
				}
				//For old Netscape
				else if (document.layers) {
						fWidth = window.innerWidth;
						fHeight = window.innerHeight;
					}
	Page.width = fWidth;
	Page.height = fHeight;
	Page.top = window.document.body.scrollTop;
}


function AbrePeca(produto,data_inicial,data_final,linha,estado){
	janela = window.open("relatorio_field_call_rate_pecas_custo.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado,"produto",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
	janela.focus();
}
</script>

<?

$msg_erro2 = $msg_erro;

if (strlen($btn_acao) > 0 && count($msg_erro) == 0) {
}

if ($gera_automatico != 'automatico' and count($msg_erro)==0){
}

if (count($msg_erro2["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro2["msg"])?></h4>
    </div>
<?php
} 
?>

<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div>
	<br/>

	<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data_inicial", $msg_erro2["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
									<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data_final", $msg_erro2["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro2["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'><?=traduz('Ref. Produto')?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro2["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'><?=traduz('Descrição Produto')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>

			<div class='span4'>
				<div class='control-group <?=(in_array("linha", $msg_erro2["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'><?=($login_fabrica == 117)? traduz("Macro - Família"): traduz("Linha")?></label>
					<div class='controls controls-row'>
						<?
							if ($login_fabrica == 117) {
                                $sql_linha = "SELECT DISTINCT tbl_linha.linha,
                                                       tbl_linha.nome
                                                    FROM tbl_linha
                                                        JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                                                        JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
                                                    WHERE tbl_macro_linha_fabrica.fabrica = $login_fabrica
                                                        AND     tbl_linha.ativo = TRUE
                                                    ORDER BY tbl_linha.nome;";
                            } else {
								$sql_linha = "SELECT
													linha,
													nome
											  FROM tbl_linha
											  WHERE tbl_linha.fabrica = $login_fabrica
											  ORDER BY tbl_linha.nome ";
							}
							$res_linha = pg_query($con, $sql_linha); ?>
								<select name="linha[]" id="linha" multiple="multiple" class='span12'>
									<?php
							
									$selected_linha = array();
									foreach (pg_fetch_all($res_linha) as $key) {
										if(isset($linha)){
											foreach ($linha as $id) {
												if ( isset($linha) && ($id == $key['linha']) ){
													$selected_linha[] = $id;
												}
											}
										} ?>

									
										<option value="<?php echo $key['linha']?>" <?php if( in_array($key['linha'], $selected_linha)) echo "SELECTED"; ?> >

											<?php echo $key['nome']?>

										</option>
							  <?php } ?>
								</select>
					</div>
				</div>
			</div>

			<? if(!in_array($login_fabrica, [180,181,182])) { ?>
			<div class='span4'>
				<div class='control-group <?=(in_array("estados", $msg_erro2["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'>Estado</label>
					<div class='controls controls-row'>
						<select name="estado" size="1" class='frm'>
							<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
							<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
							<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
							<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
							<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
							<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
							<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
							<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
							<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
							<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
							<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
							<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
							<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
							<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
							<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
							<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
							<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
							<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
							<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
							<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
							<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
							<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
							<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
							<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
							<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
							<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
							<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
							<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
						</select>
					</div>
				</div>
			</div>
		<? } ?>

			<div class='span2'></div>
		</div>

        <? if($login_fabrica == 20){?>
        	<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("tipo_atendimento", $msg_erro2["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='produto_referencia'>Tipo Atendimento</label>
						<div class='controls controls-row'>
							<select name="tipo_atendimento" size="1" class="frm">
								<option <? if (strlen ($tipo_atendimento) == 0) echo " selected " ?> ></option>
								<?
								$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY tipo_atendimento";
								$res = pg_exec ($con,$sql) ;
					
								for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
									echo "<option ";
									if ($tipo_atendimento == pg_result ($res,$i,tipo_atendimento) ) echo " selected ";
									echo " value='" . pg_result ($res,$i,tipo_atendimento) . "'" ;
									echo " > ";
									echo pg_result ($res,$i,codigo) . " - " . pg_result ($res,$i,descricao) ;
									echo "</option>\n";
								}
								?>
							</select>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("familia", $msg_erro2["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='familia'>Família</label>
						<div class='controls controls-row'>
							<select name="familia" size="1" class="frm">
								<option <? if (strlen ($familia) == 0) echo " selected " ?> ></option>
								<?
								$sql = "SELECT * FROM tbl_familia WHERE fabrica = $login_fabrica ORDER BY familia";
								$res = pg_exec ($con,$sql) ;
					
								for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
									echo "<option ";
									if ($familia == pg_result ($res,$i,familia) ) echo " selected ";
									echo " value='" . pg_result ($res,$i,familia) . "'" ;
									echo " > ";
									echo pg_result ($res,$i,descricao) ;
									echo "</option>\n";
								}
								?>
							</select>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>

			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("origem", $msg_erro2["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='origem'>Origem</label>
						<div class='controls controls-row'>
							<select name="origem" class="frm">
								<option value="">ESCOLHA</option>
								<option value="Nac" <? if ($origem == "Nac") echo " SELECTED "; ?>>Nacional</option>
								<option value="Imp" <? if ($origem == "Imp") echo " SELECTED "; ?>>Importado</option>
							</select>
						</div>
					</div>
				</div>

				<div class='span4'>
					<div class='control-group <?=(in_array("serie_inicial", $msg_erro2["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='serie_inicial'>Série Inicial</label>
						<div class='controls controls-row'>
							<input type="text" name="serie_inicial" size="3" class='frm' value="<? echo $serie_inicial ?>" maxlength='3'>
						</div>
					</div>
				</div>

				<div class='span2'></div>
			</div>

			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("serie_final", $msg_erro2["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='serie_final'>Série Final</label>
						<div class='controls controls-row'>
							<input type="text" name="serie_final" size="3" class='frm' value="<? echo $serie_final ?>" maxlength='3'>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
       <? } ?>

      

        <? if($login_fabrica == 6 or $login_fabrica==20) { // HD16694 ?>
        	<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro2["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='codigo_posto'>Código Posto</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
								<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</div>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro2["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='descricao_posto'>Nome Posto</label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
								<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
			<? } ?>
			<? if($login_fabrica != 6){ ?>
				<div class='row-fluid'>
					<div class='span2'></div>
					<div class='span4'>
						<div class='control-group <?=(in_array("extrato", $msg_erro2["campos"])) ? "error" : ""?>'>
							<label class='control-label' for='extrato'>Número do Extrato</label>
							<div class='controls controls-row'>
								<div class='span7 input-append'>
										<input type="text" name="extrato" size="8"  value="<? echo $extrato ?>" >
								</div>
							</div>
						</div>
					</div>
				</div>
			<? } ?>	
			 <input type='hidden' id='btn_click' name='btn_acao' value=''><br/>
			
			<div class="row-fluid">
	            <!-- margem -->
	            <div class="span4"></div>

	            <div class="span4">
	                <div class="control-group">
	                    <div class="controls controls-row tac">
	                        <button type="button" class="btn" value="Pesquisar" alt="Gravar formulário" onclick="submitForm($(this).parents('form'),'Pesquisar');" ><?=traduz('Pesquisar')?></button>
	                    </div>
	                </div>
	            </div>


	            <!-- margem -->
	            <div class="span4"></div>
	        </div>
</form>

<?

if(strlen($btn_acao) > 0 AND count($msg_erro) == 0){
	$cond_1 = " 1=1 ";
	$cond_2 = " 1=1 ";
	$cond_3 = " 1=1 ";
	$cond_4 = " 1=1 "; // HD 2003 TAKASHI
	$cond_5 = " 1=1 "; // HD 2003 TAKASHI
	$cond_6 = " 1=1 "; // HD 2003 TAKASHI
	$cond_7 = " 1=1 "; // HD 2003 TAKASHI
	$cond_8 = " 1=1 "; // HD 2003 TAKASHI
	$cond_9 = " 1=1 "; // HD 16694

	if (count($linha) > 0 ) {

			$condJoinLinha = " IN (";
			for($i = 0; $i < count($linha); $i++){
				if($i == count($linha)-1 ){
					$condJoinLinha .= $linha[$i].")";
				}else {
					$condJoinLinha .= $linha[$i].", ";
				}
			}
			if(strlen($condJoinLinha) > 0) {
				$cond_1 .=	" AND tbl_produto.linha {$condJoinLinha} ";
			}
	}

	if (strlen ($estado)          > 0 ) $cond_2 = " tbl_posto.estado        = '$estado' ";
	if (strlen ($posto)           > 0 ) $cond_3 = " tbl_posto.posto         = $posto ";
	if (strlen ($produto)         > 0)  $cond_4 = " tbl_os.produto          = $produto "; // HD 2003 TAKASHI
	if (strlen ($tipo_atendimento)> 0 ) $cond_5 = " tbl_os.tipo_atendimento = $tipo_atendimento";
	if (strlen ($familia)         > 0 ) $cond_6 = " tbl_produto.familia     = $familia ";
	if (strlen ($origem)          > 0 ) $cond_7 = " tbl_produto.origem      = '$origem' ";
	if (strlen ($aux)             > 0 ) $cond_8 = " substr(serie,0,4) IN ($aux)";
	if (strlen ($extrato)         > 0 ) $cond_9 = " tbl_extrato.extrato=$extrato ";

	if($login_fabrica == 20)$tipo_data = " tbl_extrato_extra.exportado ";
	else $tipo_data = " tbl_extrato.data_geracao ";
	if ($login_fabrica == 14) $sql_14 = " AND   tbl_extrato.liberado IS NOT NULL";
	if ($login_fabrica == 6)  $sql_6  = " AND tbl_extrato.aprovado IS NOT NULL ";
	$sql = "
		SELECT tbl_os_extra.os
		INTO TEMP temp_fcrpc_os
		FROM tbl_os_extra
		JOIN (
		SELECT tbl_os_extra.os ,
		(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
		FROM tbl_os_extra) fcr ON tbl_os_extra.os = fcr.os
		JOIN tbl_extrato       USING(extrato)
		JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
		JOIN tbl_posto         USING(posto)
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND   $tipo_data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
		AND tbl_posto.pais='BR'
		AND (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		AND $cond_3
		AND $cond_9
		$sql_14
		$sql_6;

		CREATE INDEX temp_fcrpc_os_OS ON temp_fcrpc_os(os);

		SELECT tbl_os.produto, COUNT(*) AS qtde, sum(pecas + mao_de_obra) as total_os
		INTO TEMP temp_fcrpc_produto
		FROM tbl_os
		JOIN temp_fcrpc_os fcr ON tbl_os.os = fcr.os
		JOIN tbl_posto         ON tbl_os.posto = tbl_posto.posto
		WHERE tbl_os.excluida IS NOT TRUE
		AND   tbl_os.fabrica = $login_fabrica
		AND $cond_2
		AND $cond_3
		AND $cond_4
		AND $cond_5
		AND $cond_8
		GROUP BY tbl_os.produto;

		CREATE INDEX temp_fcrpc_produto_PRODUTO ON temp_fcrpc_produto(produto);

		SELECT DISTINCT tbl_produto.produto, tbl_produto.ativo, tbl_produto.referencia, tbl_produto.referencia_fabrica, tbl_produto.descricao, fcr1.qtde AS ocorrencia, tbl_produto.familia, tbl_produto.linha, fcr1.total_os
		FROM tbl_produto
		JOIN temp_fcrpc_produto fcr1 ON tbl_produto.produto = fcr1.produto
		WHERE $cond_1
		AND $cond_6
		AND $cond_7
		ORDER BY fcr1.total_os DESC,fcr1.qtde DESC; " ;

//if ($ip="201.76.86.11") {echo nl2br($sql);}

#echo nl2br($sql);
#exit;

	$res = @pg_exec ($con,$sql);
	$msg_db = pg_errormessage($con);
	if (pg_numrows($res) > 0) {
		$total = 0;
		$colspan2 = 3;
		if ($login_fabrica == 171) {
			$colspan = 6;
			$colspan2 = 4;
		}
	//	$resposta .= nl2br($sql);
		echo traduz("Resultado de pesquisa entre os dias $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado");

		echo "<br><br>";
		echo "<table class='table table-bordered table-striped'>";
		echo "<thead>";
			echo "<TR class='titulo_tabela' height='25'>";
				echo "<TD colspan='100%' style='text-align: center;'>".traduz("Resultado pesquisa")."</TD>";
			echo "</TR>";
			echo "<TR class='titulo_coluna' height='25'>";
				if ($login_fabrica == 171) {
					echo "<TD>Referência Fábrica</TD>";
				}
				echo "<TD>".traduz("Referência")."</TD>";
				echo "<TD>".traduz("Produto")."</TD>";
				echo "<TD>".traduz("Ocorrência")."</TD>";

				if ($login_fabrica != 120 and $login_fabrica != 201) {
					echo "<TD>".traduz("Custo")."</TD>";
				}

				if ($login_fabrica == 120 or $login_fabrica == 201) {
					echo "<TD>Total M.O.</TD>";
					echo "<TD>Total KM</TD>";
					echo "<TD>Valores Adicionais</TD>";
					echo "<TD>Outros Valores</TD>";
				}
				if ($login_fabrica <> 171) {
				echo "<TD></TD>";
				}

			echo "</TR>";
		echo "</thead>";
		echo "<tbody>";

		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_pago = $total_pago + pg_result($res,$x,total_os);
		}

		for ($i=0; $i<pg_numrows($res); $i++){

			$referencia_fabrica = trim(pg_result($res,$i,referencia_fabrica));
			$referencia = trim(pg_result($res,$i,referencia));
			$ativo      = trim(pg_result($res,$i,ativo))     ;
			$descricao  = trim(pg_result($res,$i,descricao)) ;
			$produto    = trim(pg_result($res,$i,produto))   ;
			$linha      = trim(pg_result($res,$i,linha))     ;
			$ocorrencia = trim(pg_result($res,$i,ocorrencia));
			$total_os   = trim(pg_result($res,$i,total_os))  ;

			if ($total_pago > 0) $porcentagem = (($total_os * 100) / $total_pago);

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			// Todo produto que for inativo estará com um (*) na frente para indicar se está Inativo ou Ativo.
			if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';}

			echo  "<TR bgcolor='$cor'>";
			if ($login_fabrica == 171) {
				echo  "<TD align='left'>$referencia_fabrica</TD>";
			}
			echo  "<TD align='left'nowrap>$ativo<a href='javascript:AbrePeca(\"$produto\",\"$aux_data_inicial\",\"$aux_data_final\",\"$linha\",\"$estado\");'>$referencia</a></TD>";
			echo  "<TD align='left'>$descricao</TD>";
			echo  "<TD >$ocorrencia</TD>";
			if ($login_fabrica != 120 and $login_fabrica != 201) {
				echo  "<TD align='right'> ". $real . number_format($total_os,2,",",".") ." </TD>";
			}
			if ($login_fabrica == 120 or $login_fabrica == 201) {

				$sqlTotais = "SELECT SUM(tbl_os.mao_de_obra) as total_mo,
									 SUM(tbl_os.qtde_km_calculada) as total_km,
									 SUM(tbl_os.valores_adicionais) as valores_adicionais
							  FROM tbl_os
							  JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
							  JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
							  AND tbl_extrato.fabrica = {$login_fabrica}
							  JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
							  JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
							  AND tbl_produto.fabrica_i = {$login_fabrica}
							  WHERE tbl_extrato.data_geracao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'
							  AND tbl_os.produto = {$produto}
							  AND {$cond_2}
							  AND {$cond_4}
							  AND {$cond_1}
							  AND {$cond_9}";

				$resTotais = pg_query($con, $sqlTotais);

				$sqlOutros = "SELECT tbl_os_campo_extra.valores_adicionais
							  FROM tbl_os
							  JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
							  JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
							  AND tbl_os_campo_extra.valores_adicionais <> '[{}]'
							  AND tbl_os_campo_extra.valores_adicionais IS NOT NULL
							  JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
							  AND tbl_extrato.fabrica = {$login_fabrica}
							  JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
							  JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
							  WHERE tbl_extrato.data_geracao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'
							   AND tbl_os.produto = {$produto}
							   AND {$cond_2}
							   AND {$cond_4}
							   AND {$cond_1}
							   AND {$cond_9}
							  ";
				$resOutros = pg_query($con, $sqlOutros);

				$outros_valores = 0;
				while ($result = pg_fetch_array($resOutros)) {

					$val_adicionais = json_decode($result['valores_adicionais'], true);
					
					foreach ($val_adicionais as $chave => $adicional) {
						foreach ($adicional as $descricao => $valor) {

							if (in_array(trim($descricao), ['BALSA','PEDAGIO']) || !in_array(trim($descricao), ['TROCA DE GAS', 'TAXA EXTRA VIA VAREJO','VLR EXTRA VIA VAREJO'])) {

								$outros_valores += str_replace(",", ".", $valor);

							}
							
						}
					}

				}

				$total_mo 		  = pg_fetch_result($resTotais, 0, 'total_mo');
				$total_km 		  = pg_fetch_result($resTotais, 0, 'total_km');
				$total_adicionais = pg_fetch_result($resTotais, 0, 'valores_adicionais');

				$total_geral_mo 		+= $total_mo;
				$total_geral_km 		+= $total_km;
				$total_geral_adicionais += $total_adicionais;
				$total_geral_outros 	+= $outros_valores;

				echo  "<TD align='right'> ". $real .number_format($total_mo,2,",",".")."</TD>";
				echo  "<TD align='right'> ". $real .number_format($total_km,2,",",".")."</TD>";
				echo  "<TD align='right'> ". $real .number_format($total_adicionais,2,",",".")."</TD>";
				echo  "<TD align='right'> ". $real .number_format($outros_valores,2,",",".")."</TD>";
			}

			echo  "<TD align='right'>". number_format($porcentagem,2,",",".") ." %</TD>";
			echo  "</TR>";

			$total = $total_os + $total;

		}

			echo "<tr><td colspan='$colspan2'><font size='2'><b><CENTER>".traduz("VALOR CUSTO TOTAL")."</b></td>
			      ";
			     	if ($login_fabrica == 120 or $login_fabrica == 201) {

				      echo "
				      <td><font size='2' color='009900'><b> ". $real . number_format($total_geral_mo,2,",",".") ." </b></td>
				      <td><font size='2' color='009900'><b> ". $real . number_format($total_geral_km,2,",",".") ." </b></td>
				      <td><font size='2' color='009900'><b> ". $real . number_format($total_geral_adicionais,2,",",".") ." </b></td>
				      <td><font size='2' color='009900'><b> ". $real . number_format($total_geral_outros,2,",",".") ." </b></td>
				      <td></td>";
			  		} else {
			  			echo "<td colspan='2'><font size='2' color='009900'><b> ". $real . number_format($total,2,",",".") ." </b></td>";
			  		}
			      echo "</tr>";
		echo "</tbody>";
		echo " </TABLE>";
		echo "<br>";
	}else{ ?>
		<div class="alert">
			<h4><?=traduz("Nenhum resultado encontrado entre")." $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado"?></h4>
    	</div>
	<? }
}

?>


<? include "rodape.php" ?>
