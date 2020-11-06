
<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
$admin_privilegios="gerencia";
include "autentica_admin.php";
include "funcoes.php";

$msg_erro = array();
$msgErrorPattern01 = traduz("Preencha os campos obrigatórios.");
$msgErrorPattern02 = traduz("O intervalo entre as datas não pode ser maior que 6 meses.");

$meses = array (1 => traduz("Janeiro"), traduz("Fevereiro"), traduz("Março"), traduz("Abril"), traduz("Maio"), traduz("Junho"), traduz("Julho"), traduz("Agosto"), traduz("Setembro"), traduz("Outubro"), traduz("Novembro"), traduz("Dezembro"));

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

if (strlen($acao) > 0) {

	$tipo_os = '';
	if (!empty($_POST["tipo_os"])) { $tipo_os = $_POST["tipo_os"]; }

    if (in_array($login_fabrica, array(169,170))) { 
    	$familia = $_POST["familia"];

    	if (!empty($familia)) {
    		$cond_familia = " AND tbl_produto.familia = {$familia}";
    	}
    }

	##### Pesquisa entre datas #####
	$data_inicial = trim($_POST["data_inicial"]);
	$data_final   = trim($_POST["data_final"]);
	$estado      = trim($_POST["estado"]);

	if ($data_inicial == '' or $data_final == '') {
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "data";
	}
	if (in_array($login_fabrica, array(169,170))) {
		if ((empty($produto_referencia) || empty($produto_descricao)) && empty($familia)) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "produto";
		}
	} else {
		if (empty($produto_referencia) || empty($produto_descricao)) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "produto";
		}
	}

	if(count($msg_erro["msg"]) == 0 and !empty($data_inicial) and !empty($data_final)) {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);
	}

	## VERIFICANDO INTERVALO MENSAL
	if(count($msg_erro["msg"]) == 0  ) {
		if (pg_fetch_result(pg_query($con, "SELECT '$yi-$mi-$di'::date < '$yf-$mf-$df'::date + INTERVAL '-6 months' "), 0) == 't') {
			$msg_erro["msg"][]    = $msgErrorPattern02;
			$msg_erro["campos"][] = "data";
		}
	}

	if(strlen($estado) > 0){
		if(!in_array($login_fabrica, array(152)) && !isset($array_estado[$estado])){
			$msg_erro["msg"][]   .= traduz("Estado não encontrado");
			$msg_erro["campos"][] = "estado";
		}
	}

	if(count($msg_erro["msg"]) == 0){

		if(strlen($estado) > 0){
			if(in_array($login_fabrica, array(152))){
				$estado = str_replace(",", "','",$estado);
			}
			$condicao .= " AND tbl_posto.estado IN ('$estado')";
			$estado      = trim($_POST["estado"]);
		}
	}

	if(count($msg_erro["msg"]) == 0  )
	{
		$x_data_inicial = "$yi-$mi-$di 00:00:00";
		$x_data_final   = "$yf-$mf-$df 23:59:59";
	}

	##### Pesquisa de produto #####
	if (count($msg_erro["msg"]) == 0) {
			$produto_referencia = trim($_POST["produto_referencia"]);
			$produto_descricao  = trim($_POST["produto_descricao"]);
			$produto_referencia =str_replace(".","",$produto_referencia);
			$produto_referencia =str_replace("/","",$produto_referencia);
			$produto_referencia =str_replace(",","",$produto_referencia);
			$produto_referencia =str_replace("-","",$produto_referencia);
			$produto_referencia =str_replace(" ","",$produto_referencia);

			if (!empty($produto_referencia) && !empty($produto_descricao)) {
				 $sql =	"SELECT tbl_produto.produto    ,
								tbl_produto.referencia ,
								tbl_produto.descricao  ,
								tbl_produto.linha
						   FROM tbl_produto
						   JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
						  WHERE tbl_linha.fabrica = $login_fabrica
						  	AND tbl_produto.ativo IS TRUE";
				if (strlen($produto_referencia) > 0) $sql .= " AND (tbl_produto.referencia_pesquisa = '$produto_referencia' or tbl_produto.referencia='$produto_referencia') ";
				#if (strlen($produto_descricao) > 0)   $sql .= " AND tbl_produto.descricao = '$produto_descricao';";

				$res = pg_query($con,$sql);
				if (pg_num_rows($res) == 1) {
					$produto            = pg_result($res,0,produto);
					$produto_referencia = pg_result($res,0,referencia);
					$produto_descricao  = pg_result($res,0,descricao);
				} else {
					$showMsg = 1;
				}
			} else {
				if (!in_array($login_fabrica, array(169,170))) {
					$msg_erro["msg"][]    = $msgErrorPattern01;
			 		$msg_erro["campos"][] = "produto";
			 	}
			}
	}
}


$layout_menu = "gerencia";
$title = traduz("RELATÓRIO - FIELD CALL-RATE : LISTA BÁSICA DO PRODUTO");

include 'cabecalho_new.php';
$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
	);

include("plugin_loader.php");
?>

<script type="text/javascript" charset="utf-8">

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {	$.lupa($(this));});

		function formatItem(row) { return row[0] + " - " + row[1];}

		function formatResult(row) { return row[0]; }

		$.dataTableLoad({ table: "#relatorio" });
	});

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	function retorna_produto (retorno) {
		$("#produto").val(retorno.produto);
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

</script>

<script language="JavaScript">
function GerarRelatorio (produto, data_inicial, data_final) {
	var largura  = 350;
	var tamanho  = 200;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = '<?echo $PHP_SELF?>?acao=RELATORIO&produto=' + produto + '&data_inicial=' + data_inicial + '&data_final=' + data_final;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
</script>

<div class='alert'><h4><?=traduz('O relatório considera o mês inteiro das OS pela data da digitação.')?></h4></div>

<?php if (count($msg_erro["msg"]) > 0) {	?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php 	}	?>

<div class="row"> <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b> </div>
<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>" class="form-search form-inline tc_formulario" >
	<div class="titulo_tabela"><?=traduz('Parâmetros de Pesquisa')?></div>
	<br />
	<div class="container tc_container">
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
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
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
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
	</div>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_referencia'><?=traduz('Ref. Produto')?></label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="produto_referencia" name="produto_referencia" class='span8' maxlength="20" value="<? echo $produto_referencia ?>" >
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
					<div class='span10 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
<?php
    if($login_fabrica == 42) {
?>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <label class='checkbox'>
                <input type="checkbox" id="os_cortesia" name="os_cortesia" value="t" <?=($os_cortesia == 't') ? "checked" : ""?>>
                <?=traduz('Solicitação de Cortesia Comercial')?>
            </label>
        </div>
        <div class='span2'></div>
    </div>
<?php
    }
?>

<?php
	if($login_fabrica==24){ ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='produto_referencia'><?=traduz('Por Tipo')?></label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<select name="tipo_os" size="1">
								<?php
									switch ($tipo_os) {
										case 'C':
											$selected_c = ' selected="selected" ';
											$selected_r = '';
											break;
										case 'R':
											$selected_c = '';
											$selected_r = ' selected="selected" ';
											break;
										default:
											$selected_c = '';
											$selected_r = '';
											break;
									}
								?>
								<option value=""></option>
								<option value="C" <?php echo $selected_c ?>><?=traduz('Consumidor')?></option>
								<option value="R" <?php echo $selected_r ?>><?=traduz('Revenda')?></option>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php }
	if(in_array($login_fabrica, array(152))){ ?>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class="span6">
                <div class="control-group">
                    <label class="control-label" for="estado" ><?=traduz('Estado/Região')?></label>
                    <div class="controls control-row">
                        <select id="estado" name="estado" class="span12" >
                            <option value="" ></option>
                            <?php

     						if ($login_fabrica == 152) {
								$array_regioes = array(
									"BA,SE,AL,PE,PB,RN,CE,PI,MA,SP",
									"MG,DF,GO,MT,RO,AC,AM,RR,PA,AP,TO",
									"MS,PR,SC,RS,RJ,ES"
								);
							}

                            if (count($array_regioes) > 0) {
                            ?>
                                <optgroup label="Regiões" >
                                    <?php
                                    foreach ($array_regioes as $regiao) {
                                        $selected = ($estado == $regiao) ? "selected" : "";
                                        echo "<option value='{$regiao}'  {$selected} >{$regiao}</option>";
                                    }
                                    ?>
                                </optgroup>
                                <optgroup label="Estados" >
                            <?php
                            }

                            foreach ($array_estados() as $sigla => $estado_nome) {
                                $selected = ($estado == $regiao) ? "selected" : "";

                                echo "<option value='{$sigla}' {$selected} >{$estado_nome}</option>";
                            }

                            if (count($array_regioes) > 0) {
                            ?>
                                </optgroup>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

	<? } 
	if (in_array($login_fabrica, array(169,170))) { ?>
       <div class='row-fluid'>
       		<div class="span2"></div>
            <div class="span4">
               <div class="control-group">
                   <label class="control-label" for='familia'><?=traduz('Familia')?></label>
                   <div class='controls-row'>
	                       <select id="familia" name="familia">
	                       		<option value=""><?=traduz('Selecione')?></option>
	                       	<?
	                               $sql_familia = "SELECT  familia,
	                                                       descricao,
	                                                       ativo
	                                               FROM    tbl_familia
	                                               WHERE ativo IS TRUE
	                                               AND fabrica = $login_fabrica
	                                               ORDER BY descricao";       
	                              $res_familia = pg_query($con,$sql_familia);

	                               for($x=0;$x < pg_num_rows($res_familia);$x++) {
	                                   $familia_id           = trim(pg_result($res_familia,$x,'familia'));
	                                   $descricao_familia = trim(pg_result($res_familia,$x,'descricao'));

	                                   $selected = ($familia_id == $_POST["familia"]) ? "selected" : "";

	                                   ?>
	                                   <option value="<?= $familia_id ?>" <?= $selected ?>><?= $descricao_familia ?></option>
		                       <?        
		                           }
		                       ?>
	                       </select>
                    </div>
               </div>
            </div>
       </div>
    <?php } ?>

	<br />
	<center>
		<button type="button" class='btn' onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit(); return false;" alt="Clique AQUI para pesquisar" value="Pesquisar"><?=traduz('Pesquisar')?></button>
		<input type="hidden" name="acao">
	</center>
	<br />

</form>
<br />

<?php
if (strlen($acao) > 0 && count($msg_erro) == 0) {
	if (!empty($tipo_os)) {
		$cond_tipo_os = " AND tbl_os.consumidor_revenda = '$tipo_os' ";
	} else {
		$cond_tipo_os = '';
	}

	if ($login_fabrica == 42) {
        $os_cortesia = filter_input(INPUT_POST,"os_cortesia");
        if ($os_cortesia == 't') {
            $cond = " AND tbl_os.cortesia IS TRUE";
        } else {
            $cond = " AND tbl_os.cortesia IS NOT TRUE";
        }
	}

	if ($login_fabrica == 50) $cond_1 = " AND PE.ativo IS TRUE ";

	if (in_array($login_fabrica, array(169,170)) && empty($produto)) {
		$cond_lb_produto 	   = " ";
		$cond_familia_produto  = " AND tbl_produto.familia = $familia";
		$distinct_peca		   = " DISTINCT ON (tbl_lista_basica.peca)";
	} else {
		$cond_lb_produto 		 = " AND tbl_lista_basica.produto = $produto";
	}

	$sql = "SELECT $distinct_peca
			  PE.peca,PE.referencia,PE.descricao, tbl_familia.familia, tbl_produto.produto
			  FROM tbl_lista_basica 
			  JOIN tbl_peca PE USING(peca)
			  JOIN tbl_produto ON tbl_lista_basica.produto    = tbl_produto.produto
			  AND PE.peca = tbl_lista_basica.peca
			  JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia
			 WHERE PE.fabrica = $login_fabrica
			 	   $cond_lb_produto
			 	   $cond_familia_produto
			       $cond_1";
	$res = pg_query($con,$sql);

	######## Produto Composto - Fujitsu [138] HD 2541097 (01/10/2015)#########
	if (in_array($login_fabrica, array(138))) {
		$sql2 = "SELECT os
			   INTO TEMP tmp_fcr_lb_$login_admin
			   FROM tbl_os_produto
			   JOIN tbl_produto USING (produto)
			   JOIN tbl_os USING (os)
			  WHERE tbl_os.fabrica      = $login_fabrica
			    AND tbl_produto.produto = $produto
			        $cond_tipo_os
			    AND tbl_os.excluida IS FALSE
			    AND tbl_os.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final';

		CREATE INDEX tmp_fcr_lb_os_$login_admin ON tmp_fcr_lb_$login_admin(os); ";
	} else {
		if (in_array($login_fabrica, array(169,170)) && empty($produto)) {
			$cond_produto = " ";
		} else {
			$cond_produto = " AND tbl_produto.produto = $produto";
		}

		$sql2 = "SELECT os
			   INTO TEMP tmp_fcr_lb_$login_admin
			   FROM tbl_os
			   JOIN tbl_produto USING (produto)
			   JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia
			   JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
			   WHERE tbl_os.fabrica      = $login_fabrica
			    	$cond_produto
			        $cond_tipo_os
			        $condicao
			        $cond_familia
			    AND tbl_os.excluida IS FALSE
			    AND tbl_os.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final'
                $cond;
		CREATE INDEX tmp_fcr_lb_os_$login_admin ON tmp_fcr_lb_$login_admin(os); ";
	}
// echo nl2br($sql2);
	$res2 = pg_query($con,$sql2);

	if(in_array($login_fabrica, array(152,180,181,182))){
		$sql = "SELECT * FROM tmp_fcr_lb_$login_admin ";
		$rest= pg_query($con,$sql);
		if(pg_num_rows($rest)==0){

			echo "	<div class='alert'>
						<h4>".traduz('Nenhum resultado encontrado entre ')." $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais .</h4>
					</div>";
			include "rodape.php";
		}
	}


	if (pg_num_rows($res) > 0) {
		$total = 0;

		if ($login_fabrica==50) {
			echo "	<id class='logo'>
						<img src='imagens_admin/colormaq_.gif' border='0' width='160' height='55'>
					</id>";
		}

		echo "	<center>
				<table class='table table-striped table-bordered table-hover table-fixed'name='relatorio' id='relatorio'>
				<thead>
					<tr class='titulo_tabela'>
					<td colspan='3'>
						<center>
							<h4>".traduz('Resultado de pesquisa entre os dias ')."$data_inicial e $data_final</h4>
						</center>
					</td>
					</tr>
					<tr class='titulo_coluna'>
						<th width='100' height='15'><b>".traduz('Referência')." 	</b></th>
						<th height='15'>			<b>".traduz('Peça')." 		</b></th>
						<th width='120' height='15'><b>".traduz('Ocorrência')."  </b></th>
					</tr>
				</thead>
				<tbody>";

				for ($i=0; $i<pg_num_rows($res); $i++) {
					$peca         = trim(pg_result($res,$i,peca));
					$referencia   = trim(pg_result($res,$i,referencia));
					$descricao    = trim(pg_result($res,$i,descricao));

					if (in_array($login_fabrica, array(169,170))) {
						$xsql = "SELECT count(peca) as ocorrencia
								 FROM tbl_os_item
								 JOIN tbl_os_produto 
								 ON tbl_os_produto.os_produto = tbl_os_item.os_produto
								 JOIN tbl_os 
								 ON tbl_os.os = tbl_os_produto.os and tbl_os.fabrica = $login_fabrica
								 AND tbl_os.data_digitacao BETWEEN '$x_data_inicial'  AND '$x_data_final'
								 WHERE peca = $peca
								 ";
					} else {	
						$xsql = "SELECT count(OI.peca) as ocorrencia
							       FROM tmp_fcr_lb_$login_admin OS
							       JOIN tbl_os_produto OP ON OS.os         = OP.os
							       JOIN tbl_os_item    OI ON OI.os_produto = OP.os_produto
							  	   LEFT JOIN tbl_defeito    DE ON DE.defeito    = OI.defeito
							      WHERE OI.peca    = $peca";
					}

					$xres = pg_query($con,$xsql);
					$ocorrencia = trim(pg_fetch_result($xres,0,ocorrencia));

					echo "	<TR bgcolor='$cor'>
								<TD class='tac' align='left' nowrap> $referencia 	</TD>
								<TD align='left' nowrap> $descricao  </TD>
								<TD class='tac' align='center'> <a href='relatorio_field_call_rate_produto_lista_basica_os.php?peca=$peca&produto=$produto&data_inicial=$x_data_inicial&data_final=$x_data_final' target='_blank'>$ocorrencia</a></TD>
							</TR>";

					flush();
					$total = $ocorrencia + $total;
				}

		echo "	</tbody>
				<tfoot>
					<tr class='table_line'>
						<td colspan='2'><font size='2'><CENTER><b>".traduz('TOTAL DE PEÇAS COM DEFEITOS')."</b></CENTER></td>
						<td ><center><font size='2' color='009900'><b>$total</b></center></td>
					</tr>
				</tfoot>
				</table>
				<br />
					<hr width='600'>
				<br />";

		if (in_array($login_fabrica, array(169,170))) { ?>
			<?php 
			$data = date ("d/m/Y H:i:s");

			echo `rm -f /tmp/assist/field-call-rate-lista-basica-$login_fabrica.xls`;

			$fp = fopen ("/tmp/assist/field-call-rate-lista-basica-$login_fabrica.xls","w");

			fputs ($fp,"<html>");
			fputs ($fp,"<head>");
			fputs ($fp,"<title>FIELD CALL-RATE - $data");
			fputs ($fp,"</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs ($fp,"</head>");
			fputs ($fp,"<body>");

			fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");
			fputs ($fp,"<tr>");
			fputs ($fp,"<td bgcolor='lightblue' align='center'>REFERÊNCIA</td>");
			fputs ($fp,"<td bgcolor='lightblue' align='center'>PEÇA</td>");
			fputs ($fp,"<td bgcolor='lightblue' align='center'>OCORRÊNCIA<</td>");
			fputs ($fp,"<tr>");
			$total = 0;
			for ($i=0; $i<pg_num_rows($res); $i++) {
				$peca         = trim(pg_result($res,$i,peca));
				$referencia   = trim(pg_result($res,$i,referencia));
				$descricao    = trim(pg_result($res,$i,descricao));

				$xsql = "SELECT count(peca) as ocorrencia
						 FROM tbl_os_item
						 JOIN tbl_os_produto 
						 ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						 JOIN tbl_os 
						 ON tbl_os.os = tbl_os_produto.os and tbl_os.fabrica = $login_fabrica
						 AND tbl_os.data_digitacao BETWEEN '$x_data_inicial' AND '$x_data_final'
						 WHERE peca = $peca
						 ";

				$xres = pg_query($con,$xsql);
				$ocorrencia = trim(pg_fetch_result($xres,0,ocorrencia));

				fputs ($fp,"<tr>");
				fputs ($fp,"<td align='center'>&nbsp;" . $referencia . "&nbsp;</td>");
				fputs ($fp,"<td align='left' nowrap>" . $descricao . "</td>");
				fputs ($fp,"<td align='center'>$ocorrencia</td>");
				fputs ($fp,"</tr>");

				$total = $ocorrencia + $total;
			}

			fputs ($fp,"<tr>");
			fputs ($fp,"<td bgcolor='lightblue' colspan='2' align='center'>&nbsp;Total peças: &nbsp;</td>");
			fputs ($fp,"<td bgcolor='lightblue' align='left' nowrap>$total</td>");
			fputs ($fp,"</tr>");

			fputs ($fp,"</table>");
			fputs ($fp,"</body>");
			fputs ($fp,"</html>");
			fclose ($fp);

			$data = date("Y-m-d").".".date("H-i-s");

			$dest_dir = __DIR__;

			system("mv  /tmp/assist/field-call-rate-lista-basica-$login_fabrica.xls $dest_dir/xls/field-call-rate-lista-basica-$login_fabrica.$data.xls");
			?>

			<div id='gerar_excel' class='btn_excel'>
				<span><img src='http://posvenda.telecontrol.com.br/assist/admin/imagens/excel.png' /></span>
				<a href='xls/field-call-rate-lista-basica-<?= $login_fabrica ?>.<?= $data ?>.xls'>
				    <span class='txt'>
				    	<?=traduz('Gerar Arquivo Excel')?>
				    </span>
				</a>
			</div>

		<?
		} else {
			echo "<div id='gerar_excel' class='btn_excel'>
				    <span><img src='http://posvenda.telecontrol.com.br/assist/admin/imagens/excel.png' /></span>
				    <a href='relatorio_field_call_rate_produto-xls.php?data_inicial=$x_data_inicial&data_final=$x_data_final&linha=$linha&estado=$estado&pais=$pais&produto=$produto&criterio=$criterio&marca=$marca&tipo_os=$tipo_os'>
					    <span class='txt'>
					    	".traduz('Gerar Arquivo Excel')."
					    </span>
				    </a>
				</div>";
		}	

	} else {
		if(!empty($data_inicial) && !empty($data_final) && !empty($produto_descricao) && !empty($produto_referencia) && count($msg_erro["msg"]) == 0 && $showMsg == 1) {
			echo "	<div class='alert'>
					<h4>".traduz('Nenhum resultado encontrado entre ')."$data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais .</h4>
				</div>";
		}
	}
}
include "rodape.php";
?>
