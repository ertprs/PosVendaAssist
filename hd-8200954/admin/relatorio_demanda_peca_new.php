<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";
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

if($btn_acao=="Consultar"){
	/*$data_inicial 		= $_REQUEST['data_inicial'];
	$data_final   		= $_REQUEST['data_final'];*/

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

	if(empty($mes_inicio) OR empty($ano_inicio) OR empty($mes_fim) OR empty($ano_fim)){
        $msg_erro = "Informe o período";
    }else{
    	
    	$mes_fim = ($mes_fim < 10) ? "0$mes_fim" : $mes_fim;
    	$mes_inicio = ($mes_inicio < 10) ? "0$mes_inicio" : $mes_inicio;

        $aux_data_inicial = "$ano_inicio-$mes_inicio-01";        
        
    	$aux_data_final = "$ano_fim-$mes_fim-01";
    	$sql = "SELECT ('$aux_data_final'::date + interval '1 month' - interval '1 day')::date;";
    	$res = pg_query($con,$sql);
    	$aux_data_final = pg_fetch_result($res, 0, 0);
        
        if (strtotime($aux_data_inicial.'+1 year') < strtotime($aux_data_final) ) {
            $msg_erro = 'O intervalo entre as datas não pode ser maior que 1 ano';
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
			$msg_erro .= " Peça Inválida ";
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
			$msg_erro .= " Posto Inválido ";
		}else{
			$posto = pg_fetch_result($res,0,0);
			if(strlen($posto)==0){
				$msg_erro .= " Selecione o Posto! ";
			}else{
				$cond_3 = " AND tmp_produto_peca.posto = $posto";
			}
		}
	}
}


include 'cabecalho.php';
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

<style type="text/css">
	@import "../plugins/jquery/datepick/telecontrol.datepick.css";
</style>

<script src="js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">

<script language="javascript">
	var hora = new Date();
	var engana = hora.getTime();
	$().ready(function() {

		$("#data_inicial").maskedinput("99/9999");
		$("#data_final").maskedinput("99/9999");

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
	});

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
	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
        gravaDados("posto_referencia",codigo_posto);
        gravaDados("posto_descricao",nome);
        gravaDados("posto",posto);
        $('#uf_posto').val(estado);
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

function retorna_dados_peca (peca, referencia, descricao, ipi, origem, estoque, unidade, ativo, posicao)
{
	gravaDados("peca_referencia", referencia);
	gravaDados("peca_descricao", descricao);
}


function gravaDados(name, valor){
    try {
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
}

function mostraLinha(linha){
	if($("#linha_"+linha).is(':visible')){
		$("#linha_"+linha).hide('slow');
	}else{
		$("#linha_"+linha).show('slow');
	}
}



</script>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
	<br>
	<table width='700px' class='formulario' border='0' align='center'>
		<?
			if(strlen($msg_erro)>0){
				echo "<tr >";
					echo "<td colspan='3' class='msg_erro'>$msg_erro</td>";
				echo "</tr>";
			}
		?>
		<tr class="titulo_tabela">
			<td colspan='3'align='center'>
				Parâmetros de Pesquisa
			</td>
		</tr>
		
		<!-- <tr>
			<td width='40'>&nbsp;</td>
			<td>
				Data Inicial <br />
				<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value= "<?=$data_inicial?>" >
			</td>
			<td>
				Data Final <br />
				<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<?=$data_final?>" >
			</td>
		</tr> -->

		<tr>
			<td width='40'>&nbsp;</td>
			<td>
				Data Inicial <br />
				<select name="mes_inicio" size="1" class='frm'>
					<option value=''>Mês</option>
					<?
					for ($i = 1 ; $i <= count($array_meses) ; $i++) {
						echo "<option value='$i'";
						if ($mes_inicio == $i) echo " selected";
						echo ">" . $array_meses[$i] . "</option>";
					}
					?>
				</select>

				<select name="ano_inicio" size="1" class='frm'>
					<option value=''>Ano</option>
					<?
					for ($i = 2003 ; $i <= date("Y") ; $i++) {
						echo "<option value='$i'";
						if ($ano_inicio == $i) echo " selected";
						echo ">$i</option>";
					}
					?>
				</select>
			</td>

			<td>
				Data Final <br />
				<select name="mes_fim" size="1" class='frm'>
					<option value=''>Mês</option>
					<?
					for ($i = 1 ; $i <= count($array_meses) ; $i++) {
						echo "<option value='$i'";
						if ($mes_fim == $i) echo " selected";
						echo ">" . $array_meses[$i] . "</option>";
					}
					?>
				</select>

				<select name="ano_fim" size="1" class='frm'>
					<option value=''>Ano</option>
					<?
					for ($i = 2003 ; $i <= date("Y") ; $i++) {
						echo "<option value='$i'";
						if ($ano_fim == $i) echo " selected";
						echo ">$i</option>";
					}
					?>
				</select>
			</td>
		</tr>	

		<tr>
			<td width='40'>&nbsp;</td>
			<td>
				Ref. Posto <br />
				<input type="text" name="posto_referencia" id="posto_referencia" style="width:80%" class="frm" value="<?=$posto_referencia?>" >
				<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="fnc_pesquisa_posto_novo(document.frm_relatorio.posto_referencia,'')" >
			</td>
			<td>
				Desc. Posto <br />
				<input type="text" name="posto_descricao" id="posto_descricao" class="frm" value="<?=$posto_descricao?>" size="45" >
				<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="fnc_pesquisa_posto_novo('',document.frm_relatorio.posto_descricao)" >
			</td>
			
		</tr>			
																
		<tr>
			<td width='40'>&nbsp;</td>
			<td align='left'>
				Ref. Peça <br />
				<input type="text" id="peca_referencia" name="peca_referencia" style="width:80%" class='frm' maxlength="20" value="<? echo $peca_referencia ?>" >
				<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_peca_2 ($('input[name=peca_referencia]').val(), '')" >
			</td>
			<td  align='left'>
				Descrição Peça <br />
				<input type="text" id="peca_descricao" name="peca_descricao" size="45" class='frm' value="<? echo $peca_descricao ?>" >
				<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_peca_2 ('', $('input[name=descricao]').val())" >
			</td>			
		</tr>

		<tr>
			<td width='40'>&nbsp;</td>						
			<td>
				Linha <br />
				<select name="linha" id="linha" class="frm">
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
				
			</td>

			<td>
				Familia <br />
				<select name="familia" id="familia" class="frm">
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
			</td>
		</tr>

		<tr>
			<td width='40'>&nbsp;</td>
			<td>
				Tipo do Pedido <br />
				<select name="tipo_pedido" id="tipo_pedido" class="frm">
					<option value=""></option>
					<?php 
					
						$sql = "SELECT tipo_pedido, descricao FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica";
						$res = pg_query($con,$sql);
						foreach (pg_fetch_all($res) as $key) {

							$selected_tipo_pedido = ( isset($tipo_pedido) and ($tipo_pedido == $key['tipo_pedido']) ) ? "SELECTED" : '' ;

						?>
							<option value="<?php echo $key['tipo_pedido']?>" <?php echo $selected_tipo_pedido ?> > 
								<?php echo $key['descricao']?>
							</option>


						<?php
						}
						
					?>
				</select>
			</td>

			<td>
				Estado <br />				
				<select name="estado" class="frm" id="estado"><?php
				    foreach ($array_estado as $k => $v) {
				    echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
				    }?>
				</select>
			</td>
		</tr>

	<!-- 	<tr>
			<td width='40'>&nbsp;</td>
			<td>
				Número Pedido <br />
				<input type='text' name='pedido' value="<?=$pedido?>" size='15' class='frm'>
			</td>
			<td>
				Status da OS <br />
				<select name="status_os" style="width: 100px" class="frm" >
					<option <?if ($status_os == "aberta") echo " selected ";?> value='aberta'>Aberta</option>
					<option <?if ($status_os == "fechada" OR strlen($status_os)==0) echo " selected ";?> value='fechada'>Fechada</option>
					<?if (($login_fabrica == 43) or ($login_fabrica == 14) or $login_fabrica == 30){?>
					<option <?if ($status_os == "todas" OR strlen($status_os)==0) echo " selected ";?> value=''>Todas</option>
					<?}?>
				</select>
			</td>

		</tr> -->
				
		<tr>
			<td colspan='3' align='center'>
					<input type="button" style="cursor:pointer;" value="Pesquisar" onclick="document.frm_relatorio.btn_acao.value='Consultar'; document.frm_relatorio.submit();"  alt="Preencha as opções e clique aqui para pesquisar">
					<input type='hidden' name='btn_acao' value='<?=$acao?>'>
			</td>
		</tr>
	</table>
</form>
	<? if($btn_acao=="Consultar" AND strlen($msg_erro) == 0){

		if($pedido){
			$cond_pedido = " AND tbl_pedido.pedido = $pedido";
		}

		if ($linha) {
			$cond_linha = " AND tbl_produto.linha = $linha";
		}
		
		if(strlen($estado) > 0){
			$cond_estado = " AND tbl_posto_fabrica.contato_estado = '".$estado."' " ;
		}
	
		if ($posto) {
			$cond_posto = " AND tbl_pedido.posto = $posto ";
		}else{
			$cond_posto = " AND tbl_pedido.posto <> 6359 ";
		}

		if ($linha) {
			$cond_linha = " AND tbl_produto.linha = $linha";
		}

		if ($familia) {
			$cond_familia = " AND tbl_produto.familia = $familia";
		}

		if ($tipo_pedido) {
			$cond_tipo_pedido = " AND tbl_pedido.tipo_pedido = $tipo_pedido";
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
			$joins = " JOIN tbl_lista_basica ON tbl_lista_basica.peca = tmp_produto_peca.peca AND tbl_lista_basica.fabrica = $login_fabrica
				JOIN tbl_produto ON tbl_lista_basica.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica ";
		}
	

		$sql = "SELECT  tbl_peca.referencia as referencia_peca,
						tbl_peca.peca, 
						tbl_peca.descricao as descricao_peca,
						tbl_pedido.pedido,
						to_char(tbl_pedido.data,'YYYY-MM') AS mes,
						to_char(tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
						tbl_pedido.data,
						tbl_pedido_item.qtde,
						tbl_os.os,
						tbl_os.sua_os
				INTO TEMP tmp_produto_peca
				FROM tbl_pedido
				JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
				JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica				
				LEFT JOIN tbl_os_item ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_os_item.pedido_item = tbl_pedido_item.pedido_item AND tbl_os_item.fabrica_i = $login_fabrica
				LEFT JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				LEFT JOIN tbl_os ON tbl_os_produto.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
				JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_pedido.fabrica = $login_fabrica
				$cond_pedido
				$cond_peca
				$cond_posto				
				$cond_tipo_pedido
				$cond_estado
				$cond_status_os
				AND   NOT (tbl_pedido.status_pedido = 14)
				AND   tbl_pedido.data BETWEEN '$aux_data_inicial' AND '$aux_data_final' AND tbl_os.excluida IS NOT TRUE;";  
		//echo nl2br($sql); exit;
		
		$sql .=" SELECT DISTINCT mes FROM tmp_produto_peca 
					$joins 
					$cond_linha
					$cond_familia ORDER BY mes;";

		$res = pg_query($con,$sql);
		
		if (pg_num_rows($res) > 0) {
			$meses = pg_fetch_all($res);
			
			echo "<br>";
			$relatorio = "<table border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center'>";
			$relatorio .= "<tr class='titulo_coluna' height='25'>";
			
			$relatorio .= "<th>Referência</th>";
			$relatorio .= "<th>Descrição</th>";
			$mes = intval($mes_inicio);
			$ano = $ano_inicio;
			for($x = 1; $x <= $total_meses + 1; $x++){
				$relatorio .= "<th>".$array_meses[$mes]." / $ano</th>";
				if($mes == 12){
					$ano = $ano + 1;
				}
				$mes = ($mes == 12) ? 1 : $mes + 1;
			}
			$relatorio .= "<th>Total</th>";
			$relatorio .= "</tr>";
			
			$sql = "SELECT  DISTINCT referencia_peca, 
							descricao_peca,
							peca
						FROM tmp_produto_peca";
			$resPecas = pg_query($con,$sql);

			for ($i = 0; $i < pg_num_rows($resPecas); $i++){
				$peca     			= trim(pg_fetch_result($resPecas,$i,'peca'));
				$referencia_peca    = trim(pg_fetch_result($resPecas,$i,'referencia_peca'));
				$descricao_peca     = trim(pg_fetch_result($resPecas,$i,'descricao_peca'));
				
				$cor = ($i%2) ? "#F1F4FA" : "#F7F5F0";
				
				$relatorio .= "<tr bgcolor='$cor' rel='$peca'>";
				$relatorio .= "<td align='left' class='toggle_peca'> $referencia_peca</td>";
				$relatorio .= "<td align='left' class='toggle_peca'>$descricao_peca</td>";

				$mes = intval($mes_inicio);
				$ano = $ano_inicio;

				$total_peca = "";
				for($x = 1; $x <= $total_meses + 1; $x++){
					$novo_mes = ($mes > 9) ? $mes : "0$mes";
					$data = "$ano-$novo_mes";
					$sqlT = "SELECT sum(qtde) AS total
								FROM tmp_produto_peca
								WHERE peca = $peca
								AND mes = '$data'"; 
					$resT = pg_query($con,$sqlT);

					$relatorio .= "<td align='center' class='toggle_peca'>".pg_fetch_result($resT, 0, 'total')."</td>";

					if($mes == 12){
						$ano = $ano + 1;
					}
					$mes = ($mes == 12) ? 1 : $mes + 1;
					$total_peca += pg_fetch_result($resT, 0, 'total');
					
					
				}
				$relatorio .= "<td>$total_peca</td>";
				$relatorio .= "</tr>";

				$sqlP = "SELECT DISTINCT tmp_produto_peca.pedido,
								data_pedido,
								tmp_produto_peca.data,
								os,
								sua_os,
								tbl_pedido_item.qtde,
								(tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)) AS qtde_pendente
							FROM tmp_produto_peca
							JOIN tbl_pedido_item ON tmp_produto_peca.pedido = tbl_pedido_item.pedido AND tbl_pedido_item.peca = $peca
							WHERE tmp_produto_peca.peca = $peca
							ORDER BY tmp_produto_peca.data";
				$resP = pg_query($con,$sqlP);

				$relatorio .= "<tr  class='hidden toggle_peca_$peca'>";
				$relatorio .= "<td colspan='100%'>";
				$relatorio .= "<table width='100%'>";
				$relatorio .= "<tr class='titulo_coluna'>";
				$relatorio .= "<th>OS / Venda</th>";
				$relatorio .= "<th>Pedido</th>";
				$relatorio .= "<th>Data Pedido</th>";
				$relatorio .= "<th>Qtde Pedido</th>";
				$relatorio .= "<th>Qtde Pendente</th>";
				$relatorio .= "</tr>";
				for($y = 0; $y < pg_num_rows($resP); $y++){
					$pedido 		= pg_fetch_result($resP, $y, 'pedido');
					$data_pedido 	= pg_fetch_result($resP, $y, 'data_pedido');
					$os 			= pg_fetch_result($resP, $y, 'os');
					$sua_os 		= pg_fetch_result($resP, $y, 'sua_os');
					$qtde 			= pg_fetch_result($resP, $y, 'qtde');
					$qtde_pendente	= pg_fetch_result($resP, $y, 'qtde_pendente');

					$toggle_os = (empty($sua_os)) ? "" : "toggle_os";
					$sua_os = (empty($sua_os)) ? "VENDA ASSISTENCIA TECNICA" : $sua_os;					

					$relatorio .= "<tr>";
					$relatorio .= "<td class='{$toggle_os}' rel='$os'>$sua_os</td>";
					$relatorio .= "<td class='toggle_pedido' rel='$pedido'>$pedido</td>";
					$relatorio .= "<td>$data_pedido</td>";					
					$relatorio .= "<td>$qtde</td>";
					$relatorio .= "<td>$qtde_pendente</td>";
					$relatorio .= "</tr>";
				}
				$relatorio .= "</table>";
				$relatorio .= "</td>";
				$relatorio .= "</tr>";
			}
			$relatorio .= "</table>";

			echo $relatorio;

			$relatorio = str_replace("class='titulo_coluna'", "bgcolor='#596d9b'", $relatorio);
			$relatorio = str_replace("<th>", "<th align='center'><font color='#FFFFFF'><b>", $relatorio);
			$relatorio = str_replace("</th>", "</b></font></th>", $relatorio);
			$arquivo = "xls/relatorio-peca-pendente-{$login_fabrica}-".date('Y-m-d').".xls";
			$fp = fopen($arquivo, "w");
			fwrite($fp, $relatorio);
			fclose($fp);

			echo "<br> 
				  <center>
				  	<input type='button' value='Download Excel' onclick=\"window.open('$arquivo');\">
				  </center>";
			
		}else{
			echo "<P style='font-size: 12px; text-align=center; '>Nenhum resultado encontrado</P>";
		}
		
	}
	include 'rodape.php';
