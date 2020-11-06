<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include '../funcoes.php';



if($_GET['ajax_gerar_balanco']){
	$valores = utf8_encode($_POST["valores"]);
	$valores = json_decode($valores,true);

	foreach (array_keys($valores) as $key => $value) {
		$peca 					= $valores[$value]['peca'];
		$localizacao 			= $valores[$value]['localizacao'];
		$localizacao_anterior 	= $valores[$value]['localizacao_anterior'];
		$qtde_estoque 			= $valores[$value]['qtde_estoque'];
		$qtde_estoque_anterior 	= $valores[$value]['qtde_estoque_anterior'];

		$motivo = "Localização DE: $localizacao_anterior. PARA: $localizacao. Quantidade DE: $qtde_estoque_anterior. PARA: $qtde_estoque.";

		$sql = "INSERT INTO tbl_posto_estoque_acerto (posto, peca, qtde, motivo, login_unico) VALUES ($login_posto, $peca, $qtde_estoque, '$motivo', $login_unico)";
		if(!pg_exec ($con,$sql))
			print_r(pg_last_error($con));
	}
	echo "ok";
	exit;
}

if($_GET['ajax_etiqueta']){
	$pecas = utf8_encode($_GET['pecas']);
	$pecas = str_replace("\\","",$pecas);
	$pecas = json_decode($pecas,true);
	$pecas = implode(',', $pecas);
	$res = pg_query($con,"DELETE FROM tmp_etiqueta_contagem_4311;");
        $msg_erro = pg_last_error($con);

        $sql = "INSERT INTO tmp_etiqueta_contagem_4311(peca,referencia,descricao,localizacao,qtde,data)
                SELECT  tbl_peca.peca,
                tbl_peca.referencia, 
                tbl_peca.descricao, 
                tbl_posto_estoque_localizacao.localizacao, 
                tbl_posto_estoque.qtde, 
                TO_CHAR (CURRENT_DATE,'DD/MM/YYYY')
                FROM tbl_peca
                JOIN tbl_posto_estoque_localizacao ON tbl_posto_estoque_localizacao.posto = 4311 
                AND tbl_peca.peca = tbl_posto_estoque_localizacao.peca
                JOIN tbl_posto_estoque             ON tbl_posto_estoque.posto = 4311 
                AND tbl_peca.peca = tbl_posto_estoque.peca 
                WHERE tbl_peca.peca IN($pecas)";
	$res = pg_query($con,$sql);
	if(!pg_last_error($con)){
		echo "ok";
	}else{
		echo "no";
	}

	exit;
}


if($_POST['ajax'] == 'ajax'){

	//atualiza os dados via ajax
	if(!empty($_POST['peca']) AND $_POST['acao'] == 'atualizaEstoque'){
		$retorno 	     		= 1;
		$peca            		= $_POST['peca'];
		$qtde_estoque     		= intval($_POST['qtde_estoque']);
		$embarcado        		= intval($_POST['embarcado']);
		$localizacao      		= strtoupper(trim($_POST['localizacao']));

		if(intval($qtde_estoque)  == 0)
			$qtde_estoque  = 0;

		pg_exec ($con,"BEGIN TRANSACTION");

		$sql = "SELECT qtde FROM tbl_posto_estoque WHERE peca = $peca AND posto = $login_posto";
		$res = pg_exec ($con,$sql);
		$qtde = pg_result ($res,0,0);

		$qtde_acerto = $qtde_estoque - $embarcado - $qtde ;

		if ($qtde_acerto <> 0) {
				$sql = "INSERT INTO tbl_posto_estoque_acerto (posto, peca, qtde, motivo, login_unico) VALUES ($login_posto, $peca, $qtde_acerto,'*** Balanco Realizado ***', $login_unico)";
				if(!pg_exec ($con,$sql))
					$retorno = 0;
		}

		if(!valida_mascara_localizacao($localizacao)){
			$retorno = 2;
		}
			
		if($retorno == 1){

			$sql_fab_peca = "SELECT fabrica, referencia FROM tbl_peca WHERE peca = $peca";
			$res_fab_peca = pg_query($con, $sql_fab_peca);
			$fab_peca = pg_fetch_result($res_fab_peca, 0, 'fabrica');
			$ref_peca = pg_fetch_result($res_fab_peca, 0, 'referencia');

			if (in_array($fab_peca, [11,172])) {
				atualiza_localizacao_lenoxx($peca, $localizacao, $login_posto);
			} else {
				$sql = "UPDATE tbl_posto_estoque_localizacao SET localizacao = '$localizacao' WHERE posto = $login_posto AND peca = $peca ";
				if(!pg_exec ($con,$sql))
					$retorno = 0;
			}
		}

		if($retorno == 1)
			pg_exec ($con,"COMMIT TRANSACTION");
		else
			pg_exec ($con,"ROLLBACK TRANSACTION");

		echo $retorno;
	}
	exit;
}

$btn_acao       = trim ($_POST['btn_acao']);
$ordenar        = trim ($_POST['ordenar']);
$listar_somente = trim ($_POST['listar_somente']);
$local_inicial  = trim ($_POST['local_inicial']);
$local_final    = trim ($_POST['local_final']);
$peca_referencia = trim($_POST['referencia']);
$peca_descricao = trim($_POST['descricao']);

if (strlen ($btn_acao) > 0) {

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	#-------------- Confirma conferência atual ----------#
	$qtde_item   = $_POST['qtde_item'];

	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$peca             = $_POST['peca_' . $i];
		$qtde_estoque     = $_POST['qtde_estoque_'  . $i];
		$embarcado        = $_POST['embarcado_'     . $i];
		$localizacao      = $_POST['localizacao_'   . $i];

		$localizacao = strtoupper (trim ($localizacao));

		if (strlen ($qtde_estoque)  == 0) $qtde_estoque  = "0";

#		$sql = "SELECT qtde_balanco FROM tbl_posto_estoque WHERE peca = $peca AND posto = $login_posto";
		$sql = "SELECT qtde FROM tbl_posto_estoque WHERE peca = $peca AND posto = $login_posto";
		$res = pg_exec ($con,$sql);
		$qtde = pg_result ($res,0,0);

		$qtde_acerto = $qtde_estoque - $embarcado - $qtde ;

		if ($qtde_acerto <> 0) {
			$sql = "INSERT INTO tbl_posto_estoque_acerto (posto, peca, qtde, motivo, login_unico) VALUES ($login_posto, $peca, $qtde_acerto,'*** Balanco Realizado ***', $login_unico)";
			$res = pg_exec ($con,$sql);

		}

		$sql_fab_peca = "SELECT fabrica, referencia FROM tbl_peca WHERE peca = $peca";
		$res_fab_peca = pg_query($con, $sql_fab_peca);
		$fab_peca = pg_fetch_result($res_fab_peca, 0, 'fabrica');
		$ref_peca = pg_fetch_result($res_fab_peca, 0, 'referencia');

		if (in_array($fab_peca, [11,172])) {
			atualiza_localizacao_lenoxx($peca, $localizacao, $login_posto);
		} else {
			$sql = "UPDATE tbl_posto_estoque_localizacao SET localizacao = '$localizacao' WHERE posto = $login_posto AND peca = $peca ";
			$res = pg_exec ($con,$sql);
		}
	}
	
	$res = pg_exec ($con,"COMMIT TRANSACTION");

	header ("Location: $PHP_SELF");
	exit;
}

$title = "Conferência de Itens do Estoque";
?>

<html>
<head>
<title><?php echo $title ?></title>
<link type="text/css" rel="stylesheet" href="css/css.css">
<style>
@media print {
	.Matricial{
		font-size:11pt;
		height: 30px;
		background: none;
		border: none;
	}
	.linha {
		font-size:11pt;
		border-bottom: 1px solid #000000;
	}

	.linha0 {
	}

	.linha1 {
	background-color: #DDDDDD;
	}

	.nao_imprime {
		visibility: hidden;
	}
	
	.nao_imprime_none {
		display: none;
		background: #FF0000;
	}
}

@media screen {
	.Matricial{
		font-size:10px;
	}

	.linha0 {
	}

	.linha1 {
	background-color: #98C7D3;
	}
}

.white {
	color: #FFF;
}
.white:hover {
	color: #FFF;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

#loading {
   width: 100%;
   height: 100%;
   top: 0;
   left: 0;
   position: fixed;
   display: block;
   opacity: 0.7;
   background-color: #fff;
   z-index: 99;
   text-align: center;
}

#loading-image {
  position: absolute;
  top: 50%;
  left: 50%;
  z-index: 100;
 }
</style>
</head>

<body>
	<div class="nao_imprime_none">
	<? include 'menu.php' ?>
		<center><h1>Conferência de Itens do Estoque</h1></center>
		<center>
			<form name="frm_conferencia" action="<? echo $PHP_SELF ?>" method="post">
				<table border='0' cellspacing='1' cellpadding='2' class='formulario' style='width: 600px'>
					<tr style='display:none' id='erro' class='msg_erro'>
						<td colspan='4'>Erro ao separar os itens</td>
					</tr>

					<tr style='display:none' id='success' class='sucesso'>
						<td colspan='4'>Itens separados com sucesso</td>
					</tr>

					<tr>
						<td colspan='4' class='titulo_coluna'>Parâmetro de Pesquisa</td>
					</tr>
					<tr>
						<td width='100px'>&nbsp;</td>
						<td width='200px'>&nbsp;</td>
						<td width='200px'>&nbsp;</td>
						<td width='100px'>&nbsp;</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>
							Fabricante <br />
							<select style='width:200px;' name='fabrica' id='fabrica' class='frm' >
								<option value=''>Selecionar</option>
								<?  
									//Se adicionar mais  uma fabrica aqui, colocar tambem no select de pesquisa da tela
									$sql = "SELECT fabrica,nome 
											FROM tbl_fabrica 
											WHERE
											fabrica in ($telecontrol_distrib)
											ORDER BY nome";
									$res = pg_query($con,$sql);
									if(pg_num_rows($res)>0){
										for($x = 0; $x < pg_num_rows($res);$x++) {
											$aux_fabrica = pg_fetch_result($res,$x,fabrica);
											$aux_nome    = pg_fetch_result($res,$x,nome);
											echo "<option value='$aux_fabrica'" ;if($fabrica==$aux_fabrica) echo "selected"; echo ">$aux_nome</option>";
										}
									} ?>
							</select>
						</td>
						<td>
							Ordenar por<br />
							<label for='ordenar' style='padding-left:30px'>Localização</label>&nbsp;
							<input type='radio' name='ordenar' id='ordenar' value='localizacao' checked='checked'>
						</td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td colspan='2'><br />
							Listar somente<br />
							<label name='listar_somente' style='padding-left:30px'>Peça</label>
							<input type='radio' name='listar_somente' value='peca' <?if ($listar_somente=='peca') echo "checked"?>>
							<label name='listar_somente' style='padding-left:30px'>Produto</label>
							<input type='radio' name='listar_somente' value='produto_acabado' <?if ($listar_somente=='produto_acabado') echo "checked"?>>
							<label name='listar_somente' style='padding-left:30px'>Todos</label>
							<input type='radio' name='listar_somente' value='todos' <?if ($listar_somente=='todos' or $listar_somente=='') echo "checked"?>>
						</td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td><br />
							Local Inicial<br />
							<input type='text' name='local_inicial' id='local_inicial' value='<? echo $local_inicial ?>' size='10' class='Matricial frm'>
						</td>
						<td><br />
							Local Final<br />
							<input type='text' name='local_final' id='local_final' value='<? echo $local_final ?>' size='10' class='Matricial frm'>
						</td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td><br />
							Referência da Peça<br />
							<input type='text' size='10' name='referencia' id='referencia' value='<? echo $peca_referencia ?>' class="frm">
						</td>
						
						<td><br />
							Descrição da Peça<br />
							<input type='text' size='20' name='descricao'   id='descricao' value='<? echo $peca_descricao ?>' class="frm">
						</td>
						<td>&nbsp;</td>
					</tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>
                        <?php
                        $chkd = '';
                        if (!empty($_POST['nao_exibir_qte'])) {
                            $chkd = 'checked="checked"';
                        }
                        ?>
                            <input type="checkbox" name="nao_exibir_qte" <?php echo $chkd ?> />Não exibir quantidade
                        </td>
                    </tr>
					<tr>
						<td colspan='4' style='padding: 20px 0; text-align: center'>
							<input type='submit' name='btn_listar' value='Listar / Pesquisar'>
						</td>
					</tr>
				</table>
			</form>
		</center>
	</div>
  	<center>
		<div class="span12" id="loading">
	  		<img id="loading-image" src="../admin/imagens/loading_img.gif" alt="Loading..." />
		</div>
	</center>

    <?php
    $arq_csv = 'xls/estoque_contagem-' . date('Ymd') . '.csv';
    $f = fopen($arq_csv, 'w');

    $csv_header = 'Localização;Peça;Descrição;Embarcado';
    ?>

	<table border='0' cellspacing='1' cellpadding='2' class='formulario' style='width: 700px'>
		<tr class='titulo_coluna'>
			<? // <td align='center'>Fábrica</td> ?>
			<? // <td align='center'>Localização</td> ?>
			<td class='linha'><input type="checkbox" name="todos" value="t"></td>
			<td class='linha'>Localização</td>
			<td class='linha'>Peça</td>
			<td class='linha'>Descrição</td>
			<td class='linha'><a href="javascript:void(0);" title="Embarcado" alt="Embarcado" class="white linha">Emb.</a></td>
            <?php if (empty($_POST['nao_exibir_qte'])): ?>
            <?php $csv_header.= ';Disponível;Estoque'; ?>
			<td class='linha'><a href="javascript:void(0);" title="Disponível" alt="Disponível" class="white linha">Disp.</a></td>
			<td class='linha'>Estoque</td>
            <?php endif ?>
		</tr>
		<?php
			if(strlen(trim($_POST['btn_listar']))>0) {
                fwrite($f, $csv_header . "\n");
				$fabrica = $_POST['fabrica'];
				$sql = "SELECT tbl_posto_estoque_localizacao.localizacao, 
								tbl_peca.peca, 
								tbl_peca.referencia, 
								tbl_peca.descricao, 
								tbl_posto_estoque.qtde AS disponivel ,
								(SELECT SUM (tbl_embarque_item.qtde) 
								FROM tbl_embarque JOIN tbl_embarque_item USING (embarque) 
								WHERE tbl_embarque.distribuidor = $login_posto
								AND   tbl_embarque.faturar IS NULL
								AND   tbl_embarque_item.peca = tbl_peca.peca) AS embarcado,
								tbl_peca.fabrica
						FROM tbl_peca
						JOIN tbl_posto_estoque ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
						JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
						WHERE 1 = 1 
						AND tbl_peca.fabrica IN($telecontrol_distrib)
						AND tbl_posto_estoque_localizacao.localizacao NOT IN('FL')	
						";
						if($listar_somente=='peca'){
							$sql .= " AND tbl_peca.produto_acabado is not TRUE ";
						}
						if($listar_somente=='produto_acabado'){
							$sql .= " AND tbl_peca.produto_acabado = TRUE ";
						}
						if(strlen($fabrica)>0){
							$sql .= " AND tbl_peca.fabrica = $fabrica ";
						}
						if(strlen($local_inicial)>0 or strlen($local_final)>0){
							$sql .=" AND tbl_posto_estoque_localizacao.localizacao between '%$local_inicial%' AND '%$local_final%' ";
						}
						if(strlen($peca_referencia) > 0){
							$sql .= " AND (tbl_peca.referencia = '$peca_referencia' OR tbl_peca.referencia_pesquisa = '$peca_referencia') ";
						}
						if(strlen($ordenar)>0){
							if($ordenar=='localizacao'){
								$sql .= " ORDER BY tbl_posto_estoque_localizacao.localizacao, tbl_peca.referencia";
							}elseif($ordenar=='peca'){
								$sql .= " ORDER BY tbl_peca.referencia, tbl_posto_estoque_localizacao.localizacao";
							}elseif($ordenar=='produto_acabado'){
								$sql .= " ORDER BY tbl_peca.referencia, tbl_posto_estoque_localizacao.localizacao";
							}else{
								$sql .= " ORDER BY tbl_posto_estoque_localizacao.localizacao, tbl_peca.referencia";
							}
						}else{
							$sql .= " ORDER BY tbl_posto_estoque_localizacao.localizacao, tbl_peca.referencia";
						}
				#echo nl2br($sql);
				$res = pg_exec ($con,$sql);

				// echo "<form method='post' action='$PHP_SELF' name='frm_nf_entrada_item'>";

				for ($i = 0 ; $i < pg_numrows ($res); $i++) {
					$referencia       = trim(pg_result($res,$i,referencia)) ;
					$descricao        = trim(pg_result($res,$i,descricao));
					$peca             = trim(pg_result($res,$i,peca));
					$embarcado        = trim(pg_result($res,$i,embarcado));
					$embarcado		  = strlen($embarcado) > 0 ? $embarcado : "&nbsp;";
					$disponivel       = trim(pg_result($res,$i,disponivel));
					$localizacao      = trim(pg_result($res,$i,localizacao));
					$fabrica          = trim(pg_result($res,$i,fabrica));

					$qtde = $disponivel + $embarcado ;

					if (strlen ($msg_erro) > 0) $qtde_estoque = $_POST['qtde_estoque_' . $i];
					if (strlen ($msg_erro) > 0) $localizacao  = $_POST['localizacao_' . $i];

					$cor = "linha" . $i % 2;

                    $csv_linha = "$localizacao;$referencia;$descricao;$embarcado";

                    $localizacao = (strlen($localizacao) == 0) ? "SL" : $localizacao;

					echo "<input type='hidden' name='peca_$i' value='$peca'>";
					
					echo "<tr style='font-size: 12px' class='$cor' rel='{$i}'>\n";
						//echo "<td align='left'  nowrap>$fabrica</td>\n";
						//echo "<td align='left'  nowrap>$localizacao</td>\n";
						echo "<td align='center'><input type='checkbox' value='$peca' id='check_balanco_$i' name='check[]'></td>\n";
						//echo "<input type='hidden' name='localizacao_anterior_$i' value='$localizacao'>";
						//echo "<input type='hidden' name='qtde_estoque_anterior_$i' value='$qtde'></td>\n";
						echo "<td align='right' class='linha' nowrap><input id='local' type='text' class='Matricial frm atualizaDados localizacao'  name='localizacao_$i' onkeyup='alteraMaiusculo(this)'  value='$localizacao'   size='11' maxlength='12'></td>\n";
						echo "<td align='left' class='linha' nowrap>$referencia</td>\n";
						echo "<td align='left' class='linha' nowrap>$descricao</td>\n";
						echo "<td align='right' class='linha' nowrap>$embarcado</td>\n";
                        if (empty($_POST['nao_exibir_qte'])) {
                            $csv_linha.= ";$disponivel";
                            echo "<td align='right' class='linha' nowrap>$disponivel</td>\n";
                        }
						echo "<input type='hidden' name='embarcado_$i' value='$embarcado'>\n";
                        if (empty($_POST['nao_exibir_qte'])) {
                            $csv_linha.= ";$qtde";
                            echo "<td align='right' class='linha' nowrap><input type='text' class='Matricial nao_imprime frm atualizaDados'  name='qtde_estoque_$i' value='$qtde'   size='5' maxlength='10'></td>\n";
                        }
                    echo "</tr>\n";
                    fwrite($f, str_replace("&nbsp;", "", $csv_linha) . "\n");
				}

                fclose($f);
	
				echo "<tr>";
					echo "<td colspan='12' align='center' style='padding: 20px;'>";
						echo "<input type='button' name='btn_acao' value='Etiquetas' onclick='javascript:carregaEtiqueta()'>";
							echo "<input style='margin-left: 20px' type='button' name='btn_gravar_balanco' onclick='javascript:gerarBalanco()' value='Gravar Balanço'>";
					echo "</td>";
				echo "</tr>";
				// echo "<tr>";
				// 	echo "<td colspan='5' align='center' style='padding: 20px;'>";
				// 		echo "<input type='hidden' name='qtde_item' value='$i'>";
				// 		echo "<input type='submit' name='btn_acao' class='nao_imprime_none' value='Conferida !'>";
				// 	echo "</td>";
				// echo "</tr>";

				// echo "</form>";
			echo "</table>\n";

            echo '<p>
                <div id="gerar_excel" class="btn_excel" style="cursor: pointer">
                  <a href="' . $arq_csv . '" target="_blank">
                    <img src="../admin/imagens/excel.png" height="25" /><br/>
                    Download arquivo excel
                  </a>
                </div></p>';
			}?>
<p>

<? include "rodape.php"; ?>

<style>

@media print {
	body, a, td, th, h1, h6, form, input, .Matricial {
		font-family: verdana;
		font-size: 10pt;
	}

	td {
		padding-left: 10px;
	}

	input {
		border: none;
		overflow: visible;
	}
}

</style>
<?include "javascript_calendario_new.php"; ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<!-- <script type='text/javascript' src='../js/jquery.alphanumeric.js'></script> -->
<script type="text/javascript">

function alteraMaiusculo(valor){	
	var novoTexto = valor.value.toUpperCase();
	valor.value = novoTexto;
}
// Retirado, pois não estava validando com grande volume de dados
//$(".localizacao").alphanumeric({allow:'-'});

$(document).ready(function (){

	$('.localizacao').keypress(function (e) {
    	var regex = new RegExp("^[a-zA-Z0-9-]+$");
    	var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
    		if (regex.test(str)) {
        		return true;
    		}
    	e.preventDefault();
    	return false;
	});

	$('.atualizaDados').blur(function(){
		var linha 					= $(this).parent().parent().attr('rel');
		var peca  					= $('input[name*="peca_'+linha+'"]');
		var qtde_estoque  			= $('input[name*="qtde_estoque_'+linha+'"]');
		var embarcado  				= $('input[name*="embarcado_'+linha+'"]');
		var localizacao  			= $('input[name*="localizacao_'+linha+'"]');
		var campo 		    		= $(this);

		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF'];?>",
			type: "POST",
			beforeSend: function() {
				localizacao.attr("disabled", true);
				qtde_estoque.attr("disabled", true);
				campo.css('background-color','#EDAABC');
			},
			data: "ajax=ajax&peca="+peca.val()+"&qtde_estoque="+qtde_estoque.val()+"&embarcado="+embarcado.val()+"&localizacao="+localizacao.val()+"&acao=atualizaEstoque",
			success: function(resposta){
				localizacao.attr("disabled", false);
				qtde_estoque.attr("disabled", false);
				
				if(resposta == 1){
					campo.css('background-color','#AAEDB1');
					$("#check_balanco_"+linha).attr("checked",true);
				}
				if(resposta == 2){
					alert('É preciso que o formato corresponda ao exigido: \n Formato Válido: LL-LNN-LNN, LNN-LNN, LLL-LNNN, LNNN-LNN');
				}
			}
		});
	});

	$("input[name=todos]").click(function(){
		if( $(this).is(":checked")){
			$("input[name^=check]").attr("checked",true);
		}else{
			$("input[name^=check]").attr("checked",false);
		}
	});

	function formatItem(row) {
		return row[0] + " - " + row[1] + " - " + row[2];
	}

	function formatResult(row) {
		return row[0];
	}

	$("#referencia").autocomplete("<?echo 'peca_consulta_ajax.php?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1]; return row[2];}
	});

	$("#referencia").result(function(event, data, formatted) {
		$("#referencia").val(data[1]) ;
		$("#descricao").val(data[2]) ;
	});

	$("#descricao").autocomplete("<?echo 'peca_consulta_ajax.php?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1]; return row[2];}
	});

	$("#descricao").result(function(event, data, formatted) {
		$("#referencia").val(data[1]) ;
		$("#descricao").val(data[2]) ;
	});
});

function carregaEtiqueta(){
	var json = {};
    var pecas = {};

	$("table[class=formulario]").find("input[name^=check]").each(function(){
		var i = $(this).val();
		if( $(this).is(":checked")){
			pecas[i] = $(this).val();
		}

	});

	json = pecas;
	pecas = JSON.stringify(json);
	$.ajax({
		url: "estoque_contagem.php?ajax_etiqueta=sim&pecas="+pecas,
		complete: function(data){
			if(data.responseText == "ok"){
				$("#erro").hide();
				$("#success").show();
				$("input[name^=check]").attr("checked",false);
			}else{
				$("#success").hide();
				$("#erro").show();
			}
		}
	});
}

function gerarBalanco(){
	var json = {};
    var valores = {};


	$("table[class=formulario]").find("input[name^=check]").each(function(){
		var i = $(this).val();
		if( $(this).is(":checked")){
			var linha = $(this).attr("id").replace(/[^0-9]/g,'');
			valores[linha] = {
							"peca" : $(this).val(), 
							"localizacao" : $("input[name=localizacao_"+linha+"]").val(),
							"localizacao_anterior" : $("input[name=localizacao_"+linha+"]").attr('defaultValue'),
							"qtde_estoque" : $("input[name=qtde_estoque_"+linha+"]").val(),
							"qtde_estoque_anterior" : $("input[name=qtde_estoque_"+linha+"]").attr('defaultValue'),
						};
		}
	});

	json = valores;
	valores = JSON.stringify(json);
	$.ajax({
		type: "POST",
		url: "estoque_contagem.php?ajax_gerar_balanco=sim",
		data:"valores="+valores,
		beforeSend: function(data){
			$("#loading").attr("style","");
		}, 
		success: function(data){			
			$("#erro").hide();
			$("#success").show();
			$("input[name^=check]").attr("checked",false);
			$("#loading").attr("style","display: none;");

			if(data == 'ok'){
            	alert('Balanço realizado com Sucesso !');
            }else{
                alert('Erro no balanço !');
            }
		}
	});
}

</script>

<script language="javascript" type="text/javascript">
     $(window).load(function() {
     $('#loading').hide();
  });
</script>
</body>
