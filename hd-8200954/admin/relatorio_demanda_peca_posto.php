<?php 
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
// include 'autentica_admin.php';	
include 'funcoes.php';
//include "monitora.php";
$layout_menu = "gerencia";
$title = "RELATÓRIO DE DEMANDA DE PEÇAS";

$array_estado = array(""=>"","AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
"AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
"ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
"MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
"PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
"RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
"RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
"SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

$array_meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$btn_acao = $_POST['btn_acao'];
$gera_automatico = $_GET["gera_automatico"];

if($gera_automatico != 'automatico'){
	include 'autentica_admin.php';	
}else{
	$login_fabrica = $_GET['login_fabrica'];
}
function mandaemail($arquivo_xls, $admin_relatorio){
	global $login_admin, $login_fabrica, $con;

	list($pasta, $nome_arquivo) = explode("/", $arquivo_xls);
	include "../class/email/mailer/class.phpmailer.php";
	$mailer = new PHPMailer();
	 $sql = "SELECT email FROM tbl_admin WHERE admin = $admin_relatorio";
	//$sql = "SELECT email FROM tbl_admin WHERE admin = 5313";
	$res = pg_query($con, $sql);
	$email_admin = pg_fetch_result($res, 0, 'email');

	
	$email_subject = "Relatório Demanda Peças por Posto";
	
	$mailer->IsSMTP();
    $mailer->IsHTML();
    $mailer->AddAddress($email_admin);
    $mailer->Subject = $email_subject;
    $mailer->Body = "Relatório de Demanda de Peças Por Posto (Teste em produção)";
    $mailer->AddAttachment($arquivo_xls, $nome_arquivo);
    $mailer->Send();
    fopen($arquivo_xls);
    
}
if($btn_acao=="Consultar" || strlen($gera_automatico) > 0 ){

	$mes_inicio 		= $_REQUEST['mes_inicio'];
	$ano_inicio 		= $_REQUEST['ano_inicio'];
	$mes_fim 			= $_REQUEST['mes_fim'];
	$ano_fim 			= $_REQUEST['ano_fim'];
	$produto_referencia = $_REQUEST['produto_referencia'];
	$produto_descricao 	= $_REQUEST['produto_descricao'];
	$peca_referencia 	= $_REQUEST['peca_referencia'];
	$peca_descricao 	= $_REQUEST['peca_descricao'];
	$codigo_posto 		= $_REQUEST['posto_referencia'];
	$descricao_posto 	= $_REQUEST['posto_descricao'];
	$estado 			= $_REQUEST['estado'];
	$pedido 			= $_REQUEST['pedido'];
	$status_os   		= $_REQUEST['status_os'];
	$linha   			= $_REQUEST['linha'];
	if(isset($_REQUEST["login_admin"])){
		$admin_relatorio	= $_REQUEST['linha'];
	}
	if(empty($mes_inicio) OR empty($ano_inicio) OR empty($mes_fim) OR empty($ano_fim)){
		$msg_erro["msg"][]    = "Informe o Período";
		$msg_erro["campos"][] = "mes_inicio";
		$msg_erro["campos"][] = "mes_fim";
		$msg_erro["campos"][] = "ano_inicio";
		$msg_erro["campos"][] = "ano_fim";
    }else{
    	
    	$mes_fim = ($mes_fim < 10) ? "0$mes_fim" : $mes_fim;
    	$mes_inicio = ($mes_inicio < 10) ? "0$mes_inicio" : $mes_inicio;

        $aux_data_inicial = "$ano_inicio-$mes_inicio-01";        
        
    	$aux_data_final = "$ano_fim-$mes_fim-01";
    	$sql = "SELECT ('$aux_data_final'::date + interval '1 month' - interval '1 day')::date;";
    	$res = pg_query($con,$sql);
    	$aux_data_final = pg_fetch_result($res, 0, 0);
        
        if ((strtotime($aux_data_inicial.'+6 months') < strtotime($aux_data_final) )) {
            $msg_erro["msg"][] = 'O intervalo entre as datas não pode ser maior que 6 meses.';
            
        }else if ((strtotime($aux_data_inicial.'+2 month') < strtotime($aux_data_final) ) && (strlen($codigo_posto) == 0 && strlen($peca_referencia) == 0) && strlen($gera_automatico) == 0) {
        	
        	$agendar = 1;
        }else{	
        	$sql = "select extract(month from age('$aux_data_final', timestamp '$aux_data_inicial'));";
        	$res = pg_query($con,$sql);
        	$total_meses = pg_fetch_result($res, 0, 0);
        }
    }
    if($peca_referencia){
    	$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
    	$res = pg_query($con,$sql);
    	if(pg_num_rows($res)<1){
			$msg_erro["msg"][] = " Peça Inválida ";
		}else{
			$peca = pg_fetch_result($res,0,0);
		}
    }

	if(strlen($codigo_posto)>0){
		$sql = "SELECT posto
				FROM tbl_posto_fabrica
				WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
		$res = @pg_query($con,$sql);
		if(pg_num_rows($res)<1){
			$msg_erro["msg"][] = " Posto Inválido ";
		}else{
			$posto = pg_fetch_result($res,0,0);
			if(strlen($posto)==0){
				$msg_erro["msg"][] = " Selecione o Posto! ";
			}else{
				$cond_3 = " AND tmp_produto_peca_$login_admin.posto = $posto";
			}
		}
	}
}
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0 && $agendar == 1) {

	$aviso = '';

	$sql = "SELECT relatorio_agendamento FROM tbl_relatorio_agendamento
			WHERE admin = $login_admin
			AND fabrica = $login_fabrica
			AND executado IS NULL
			AND agendado IS NOT FALSE";
	$qry = pg_query($con, $sql);

	if (pg_num_rows($qry) > 0) {
		$cancela = "UPDATE tbl_relatorio_agendamento SET executado = current_date
					WHERE admin = $login_admin AND fabrica = $login_fabrica AND executado IS NULL";
		$qry_cancela = pg_query($con, $cancela);

		if (!pg_last_error()) {
			$aviso = '<br/><br/>AVISO: Os relatórios anteriores agendados pelo seu usuário foram cancelados em razão do agendamento atual.';
		}else{
			echo pg_last_error();
		}
	}

	$parametros = "";
	foreach ($_POST as $key => $value){
		$parametros .= $key."=".$value."&";
	}
	foreach ($_GET as $key => $value){
		$parametros .= $key."=".$value."&";
	}

	$sql = "INSERT INTO tbl_relatorio_agendamento (admin, fabrica, programa, parametros, titulo, agendado)
			VALUES ($login_admin, $login_fabrica, '$PHP_SELF', '$parametros', '$title', 't')";
	$res = pg_query($con,$sql);

	if (!pg_last_error()) {
		$msg_agendamento = "O relatório foi agendado e será processado nesta madrugada.<br/> Um email lhe será enviado ao final do processo.$aviso<br/>";
	}

}
if((($btn_acao=="Consultar" AND count($msg_erro) == 0) || strlen($gera_automatico)>0) && $agendar != 1){

		if (strlen($linha) > 0) {
			$cond_linha = " AND tbl_produto.linha = $linha";
		}
		
		if(strlen($estado) > 0){
			$cond_estado = " AND tbl_posto_fabrica.contato_estado = '".$estado."' " ;
		}
	
		if ($posto) {
			$cond_posto = " AND tbl_os.posto = $posto ";
		}else{
			$cond_posto = " AND tbl_os.posto <> 6359 ";
		}

		if ($familia) {
			$cond_familia = " AND tbl_produto.familia = $familia";
		}


		if ($peca) {
			$cond_peca = " AND tbl_peca.peca = $peca";
		}

		if ($status_os) {
			if ($status_os=='aberta'){
				/*Aberta*/
				$cond_status_os = " AND   tbl_os.finalizada IS NULL ";				
			}else{
				/*Fechada*/
				$cond_status_os = " AND tbl_os.finalizada IS NOT NULL ";
			}
		}

		if(!empty($linha) OR !empty($familia)){
			$joins = " JOIN tbl_lista_basica ON tbl_lista_basica.peca = tmp_produto_peca_$login_admin.peca AND tbl_lista_basica.fabrica = $login_fabrica
				JOIN tbl_produto ON tbl_lista_basica.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica ";
		}
	if($_POST['gerar_excel'] == 1 || (strlen($gera_automatico)>0)){
		$limit="";
	}else{
		$limit="limit 500";
	}

		$sql = "SELECT  DISTINCT
						tbl_os.os,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome,
						tbl_os.posto, 
						tbl_peca.referencia as referencia_peca,
						tbl_peca.peca, 
						tbl_peca.descricao as descricao_peca,
						to_char(tbl_os.data_digitacao,'YYYY-MM') AS mes,
						to_char(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_pedido,
						tbl_os_item.qtde as qtde
				INTO TEMP tmp_produto_peca_$login_admin
				FROM tbl_os
				JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
				JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
				JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				
				WHERE tbl_os.fabrica = $login_fabrica
				$cond_peca
				$cond_posto				
				$cond_estado
				$cond_status_os
				AND   tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' AND tbl_os.excluida IS NOT TRUE 
				";  
		// echo nl2br($sql); exit;
		$res = pg_query($con,$sql);

		$sqlSoma = "SELECT 	count(peca) AS total_peca_mes,nome, codigo_posto,referencia_peca, 
							descricao_peca,
							peca,
							posto,
							mes
							INTO TEMP tmp_soma_pecas_$login_admin
							FROM tmp_produto_peca_$login_admin
						GROUP BY nome, 
							codigo_posto,
							referencia_peca, 
							descricao_peca,
							peca,
							posto,
							mes";
		$resSoma = pg_query($con,$sqlSoma);

		$sqlCount = "select count(*) as count from tmp_produto_peca_$login_admin;";
		$resC = pg_query($con,$sqlCount);
		$count = trim(pg_fetch_result($resC,0,'count'));
		/*MONTA AS COLUNAS DOS MESES */
		$sqlMes =" SELECT DISTINCT mes FROM tmp_soma_pecas_$login_admin 
					$joins 
					$cond_linha
					$cond_familia ORDER BY mes;";

		$resMes = pg_query($con,$sqlMes);

		$countMes = pg_num_rows($resMes);

		if($_POST['gerar_excel'] == true || strlen($gera_automatico)>0){

			$header = "";
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_demanda_peca_posto-{$data}.csv";


			$header = "Código Posto;Nome Posto;Referência;Descrição;";
							
									$mes = intval($mes_inicio);
									$ano = $ano_inicio;
									for($x = 1; $x <= $total_meses + 1; $x++){
										$header .= $array_meses[$mes]." / $ano;";
										if($mes == 12){
											$ano = $ano + 1;
										}
										$mes = ($mes == 12) ? 1 : $mes + 1;
									}
			$header .= "Total\n";
			fwrite($file, $header);

			$sql = "SELECT  DISTINCT nome, codigo_posto,referencia_peca, 
							descricao_peca,
							peca,
							posto
						FROM tmp_produto_peca_$login_admin";
			$resPecas = pg_query($con,$sql);

			for ($i = 0; $i < pg_num_rows($resPecas); $i++){

				$file = fopen("/tmp/{$fileName}","a");

				$peca     			= trim(pg_fetch_result($resPecas,$i,'peca'));
				$referencia_peca    = trim(pg_fetch_result($resPecas,$i,'referencia_peca'));
				$descricao_peca     = trim(pg_fetch_result($resPecas,$i,'descricao_peca'));
				$referencia_peca    = str_replace(';','',$referencia_peca);
				$descricao_peca     = str_replace(',','.',$descricao_peca);
				$codigo_posto = pg_fetch_result($resPecas, $i, 'codigo_posto');				
				$posto = pg_fetch_result($resPecas, $i, 'posto');				
				$nome = pg_fetch_result($resPecas, $i, 'nome');
				$nome     = str_replace(',','',$nome);
				$cor = ($i%2) ? "#F1F4FA" : "#F7F5F0";
				$body .= "$codigo_posto;$nome;$referencia_peca;$descricao_peca;";
				
				$mes = intval($mes_inicio);
				$ano = $ano_inicio;

				$total_peca = "";

				for($x = 1; $x <= $total_meses + 1; $x++){
					
					$novo_mes = ($mes > 9) ? $mes : "0$mes";
					$data = "$ano-$novo_mes";
					$sqlT = "SELECT 
								total_peca_mes
								FROM tmp_soma_pecas_$login_admin
								WHERE peca = $peca
								AND mes = '$data'
								AND codigo_posto = '$codigo_posto'";

					$resT = pg_query($con,$sqlT);
					$total_peca_mes = ( pg_fetch_result($resT, 0, 'total_peca_mes') > 0) ? pg_fetch_result($resT, 0, 'total_peca_mes') : 0;
					$body .= $total_peca_mes .";";

					if($mes == 12){
						$ano = $ano + 1;
					}
					$mes = ($mes == 12) ? 1 : $mes + 1;
					$total_peca += pg_fetch_result($resT, 0, 'total_peca_mes');
					
					
				} 
				$body .="{$total_peca}\n";
				fwrite($file, $body);
				$body = "";
				fclose($file);
			} 

			

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");
				$arquivo_xls = "xls/{$fileName}";


				if( (isset($_GET["gera_automatico"])) && (strlen(trim($gera_automatico)) > 0) ){
					mandaemail($arquivo_xls,$admin_relatorio);
					exit;
				}
				echo $arquivo_xls;
			}
			exit;
		}
}

if (strlen($btn_acao) > 0 && count($msg_erro) == 0) {
	$aux = "relatorio_demanda_peca_posto";
	$msg_erro_aux = $msg_erro;
	include "gera_relatorio_pararelo.php";
	$msg_erro = $msg_erro_aux;
}

if ($gera_automatico != 'automatico' and count($msg_erro) == 0) {
	include "gera_relatorio_pararelo_verifica.php";
}

include "cabecalho_new.php";

$plugins = array(
				 "shadowbox",
                 "dataTable",
                 "price_format",
                 "mask"
            );

include ("plugin_loader.php");
?>

<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.hidden{
	display:none;
}

.toggle_peca,.toggle_os, .toggle_pedido{
	cursor:pointer;
}

.toggle_peca:hover,.toggle_os:hover, .toggle_pedido:hover{
	background-color: #a1a1a1;
}
</style>

<script language="javascript">
	var hora = new Date();
	var engana = hora.getTime();
	$(function() {

		$("#data_inicial").mask("99/9999");
		$("#data_final").mask("99/9999");

		$('.toggle_peca').bind('click', function(){
			var peca = $(this).parent().attr('rel');			
			$('.toggle_peca_'+peca).toggle();
		});

		$('.toggle_os').bind('click', function(){
			var os = $(this).attr('rel');
			window.open("os_press.php?os="+os);
		});

		$('.toggle_pedido').bind('click', function(){
			var pedido = $(this).attr('rel');
			window.open("pedido_admin_consulta.php?pedido="+pedido);
		});

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

	});
	function retorna_peca(retorno){
	        $("#peca_referencia").val(retorno.referencia);
			$("#peca_descricao").val(retorno.descricao);
	    }

	function retorna_posto(retorno){
        $("#posto_referencia").val(retorno.codigo);
		$("#posto_descricao").val(retorno.nome);
    }
	function fnc_pesquisa_posto_novo(codigo, nome) {
		var codigo = jQuery.trim(codigo.value);
		var nome   = jQuery.trim(nome.value);
		if (codigo.length > 2 || nome.length > 2){   
			Shadowbox.open({
				content:	"posto_pesquisa_2_nv.php?os=&codigo=" + codigo + "&nome=" + nome,
				player:	"iframe",
				title:		"Pesquisa Posto",
				width:	800,
				height:	500
			});
		}else{
			alert("Preencha toda ou parte da informação para realizar a pesquisa!");
		}

	}

    function fnc_pesquisa_peca_2 (referencia, descricao) {

	if (referencia.length > 2 || descricao.length > 2) {
		Shadowbox.open({
			content:	"peca_pesquisa_nv.php?referencia=" + referencia + "&descricao=" + descricao,
			player:	"iframe",
			title:		"Pesquisa Peça",
			width:	800,
			height:	500
		});
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

</script>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }

if(($btn_acao=="Consultar") && ($countMes == 0) && (count($msg_erro["msg"]) == 0) ){ ?>
<div class="alert">
	<h4>Nenhum resultado encontrado</h4>
</div>

<? } 

if((strlen($msg_agendamento) > 0) && (count($msg_erro["msg"]) == 0) && ($agendar==1)){ ?>
<div class="alert">
	<h4><?=$msg_agendamento?></h4>
</div>
<? } ?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>


<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>
	<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span2'>
					<div class='control-group <?=(in_array("mes_inicio", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							
							<h5 class='asteristico'>*</h5>
								<select name="mes_inicio" class='span12'>
									<option value=''>Mês</option>
									<?
									for ($i = 1 ; $i <= count($array_meses) ; $i++) {
										echo "<option value='$i'";
										if ($mes_inicio == $i) echo " selected";
										echo ">" . $array_meses[$i] . "</option>";
									}
									?>
								</select>
					
						</div>
					</div>
				</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("ano_inicio", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'></label>
					<div class='controls controls-row'>
						
							<h5 class='asteristico'>*</h5>
							<select name="ano_inicio" class='span8'>
								<option value=''>Ano</option>
								<?
								for ($i = 2003 ; $i <= date("Y") ; $i++) {
									echo "<option value='$i'";
									if ($ano_inicio == $i) echo " selected";
									echo ">$i</option>";
								}
									?>
							</select>
						
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("mes_fim", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Fim</label>
					<div class='controls controls-row'>
						
						<h5 class='asteristico'>*</h5>
							<select name="mes_fim" class='span12'>
								<option value=''>Mês</option>
								<?
								for ($i = 1 ; $i <= count($array_meses) ; $i++) {
									echo "<option value='$i'";
									if ($mes_fim == $i) echo " selected";
									echo ">" . $array_meses[$i] . "</option>";
								}
								?>
							</select>
				
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("ano_fim", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'></label>
					<div class='controls controls-row'>
						
							<h5 class='asteristico'>*</h5>
							<select name="ano_fim" class='span8'>
								<option value=''>Ano</option>
								<?
								for ($i = 2003 ; $i <= date("Y") ; $i++) {
									echo "<option value='$i'";
									if ($ano_fim == $i) echo " selected";
									echo ">$i</option>";
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
				<div class='control-group <?=(in_array("posto_referencia", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='posto_referencia'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="posto_referencia" id="posto_referencia" class='span12' value="<? echo $posto_referencia ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto_descricao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='posto_descricao'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="posto_descricao" id="posto_descricao" class='span12' value="<? echo $posto_descricao ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("peca_referencia", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_referencia'>Ref. Peças</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("peca_descricao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_descricao'>Descrição Peça</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>Linha</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="linha" id="linha">
								<option value=""></option>
								<?php
								$sql = "SELECT linha, nome
										FROM tbl_linha
										WHERE fabrica = $login_fabrica
										AND ativo";
								$res = pg_query($con,$sql);

								foreach (pg_fetch_all($res) as $key) {
									$selected_linha = ( isset($linha) and ($linha == $key['linha']) ) ? "SELECTED" : '' ;

								?>
									<option value="<?php echo $key['linha']?>" <?php echo $selected_linha ?> >

										<?php echo $key['nome']?>

									</option>
								<?php
								}
								?>
							</select>
						</div>	
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='familia'>Familia</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="familia" id="familia">
								<option value=""></option>
								<?php

									$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica and ativo order by descricao";
									$res = pg_query($con,$sql);
									foreach (pg_fetch_all($res) as $key) {

										$selected_familia = ( isset($familia) and ($familia == $key['familia']) ) ? "SELECTED" : '' ;

									?>
										<option value="<?php echo $key['familia']?>" <?php echo $selected_familia ?> >
											<?php echo $key['descricao']?>
										</option>


									<?php
									}

								?>
							</select>
						</div>
						<div class='span2'></div>
					</div>
				</div>
			</div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='familia'>Estado</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="estado" class="frm" id="estado"><?php
							    foreach ($array_estado as $k => $v) {
							    echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
							    }?>
							</select>
						</div>
						<div class='span2'></div>
					</div>
				</div>
			</div>
		</div>
		<input type="button" class="btn" value="Pesquisar" onclick="document.frm_relatorio.btn_acao.value='Consultar'; document.frm_relatorio.submit();"  alt="Preencha as opções e clique aqui para pesquisar">
		<input type='hidden' name='btn_acao' value='<?=$acao?>'>
</form>
	<? 
		if ($countMes > 0) {
			$meses = pg_fetch_all($resMes);
			
			echo "<br></div>";?>
			<table id="listagem" style="margin: 0 auto;" class="tabela_item table table-striped table-bordered table-hover table-large">
			<thead>
				<tr class='titulo_coluna' height='25'>
					<th>Código Posto</th>
					<th>Nome Posto</th>
					<th>Referência</th>
					<th>Descrição</th>
			
					<? $mes = intval($mes_inicio);
					$ano = $ano_inicio;
					for($x = 1; $x <= $total_meses + 1; $x++){
						echo "<th>".$array_meses[$mes]." / $ano</th>";
						if($mes == 12){
							$ano = $ano + 1;
						}
						$mes = ($mes == 12) ? 1 : $mes + 1;
					} ?>

					<th>Total</th>
				</tr>
			</thead>
			<?
			$sql = "SELECT  DISTINCT nome, codigo_posto,referencia_peca, 
							descricao_peca,
							peca
						FROM tmp_produto_peca_$login_admin $limit";
			$resPecas = pg_query($con,$sql);

			for ($i = 0; $i < pg_num_rows($resPecas); $i++){
				$peca     			= trim(pg_fetch_result($resPecas,$i,'peca'));
				$referencia_peca    = trim(pg_fetch_result($resPecas,$i,'referencia_peca'));
				$descricao_peca     = trim(pg_fetch_result($resPecas,$i,'descricao_peca'));
				
				$cor = ($i%2) ? "#F1F4FA" : "#F7F5F0";
				?>
				<tr bgcolor='$cor' rel='$peca'>
				<? $codigo_posto = pg_fetch_result($resPecas, $i, 'codigo_posto');?>
				<td align='center' ><?=$codigo_posto?></td>
				<td align='center' ><?=pg_fetch_result($resPecas, $i, 'nome')?></td>

				<td align='left' ><?= $referencia_peca?></td>
				<td align='left' ><?=$descricao_peca?></td>
				<?
				$mes = intval($mes_inicio);
				$ano = $ano_inicio;

				$total_peca = "";

				for($x = 1; $x <= $total_meses + 1; $x++){

					$novo_mes = ($mes > 9) ? $mes : "0$mes";
					$data = "$ano-$novo_mes";
					$sqlT = "SELECT 
								total_peca_mes
								FROM tmp_soma_pecas_$login_admin
								WHERE peca = $peca
								AND mes = '$data'
								AND codigo_posto = '$codigo_posto'
								";					
					$resT = pg_query($con,$sqlT);
					$total_peca_mes = ( pg_fetch_result($resT, 0, 'total_peca_mes') > 0) ? pg_fetch_result($resT, 0, 'total_peca_mes') : 0;
					echo  "<td align='center' >". $total_peca_mes ."</td>";

					if($mes == 12){
						$ano = $ano + 1;
					}
					$mes = ($mes == 12) ? 1 : $mes + 1;
					$total_peca += pg_fetch_result($resT, 0, 'total_peca_mes');
					
					
				} ?>
					<td><?=$total_peca?></td>
				</tr>

				
		<?	} ?>
			</table>
			<?

            if ($count > 50) { ?>
                <script>
                    $.dataTableLoad({
                        table : "#listagem"
                    });
                </script>
            <?php
            }?>
			<br/>      
			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>		    
		    <div id='gerar_excel' class="btn_excel">
		        <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		        <span><img src='imagens/excel.png' /></span>
		        <span class="txt">Gerar Arquivo Excel</span>
		    </div>
		    <?
					
		}
		

include 'rodape.php';
