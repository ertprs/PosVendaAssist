<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";
include 'funcoes.php';

if($_POST['aprOS'] == 1){
	$os = $_POST['idOS'];
	$dias = $_POST['dias'];
	$itens_soaf = $_POST['itens_soaf'];
	if($login_fabrica == 87 and !empty($itens_soaf) and $itens_soaf != "null"){
		
		$itens_soaf = explode(",",$_POST['itens_soaf']);
		
		for($i = 0; $i < count($itens_soaf); $i++){
			list($os_item,$tipo_soaf) = explode("/",$itens_soaf[$i]);
			
			$sqlI = "INSERT INTO tbl_soaf(tipo_soaf) VALUES($tipo_soaf) RETURNING soaf";
			$resI = pg_query($con,$sqlI);
			$soaf = pg_result($resI,0,soaf);

			$sqlU = "UPDATE tbl_os_item SET soaf = $soaf WHERE os_item = $os_item AND fabrica_i = $login_fabrica";
			$resU = pg_query($con,$sqlU);
		}
	}

	if($dias == 70){
		$status_os = 149;
	}
	elseif ($dias == 30) {
		$status_os = 151;
	}else {
		if($login_fabrica == 87){
			$status_os = 64 ;
		} else {
			$status_os = 103 ;
		}
	}
		$sql = "INSERT INTO tbl_os_status
							(
							os,
							status_os,
							admin,
							observacao,
							fabrica_status
							) 
						VALUES 
							(
							$os,
							$status_os,
							$login_admin,
							'OS liberada da auditoria pelo fabricante',
							$login_fabrica
							)";
	
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if($login_fabrica == 87){
		system("/usr/bin/perl /var/www/cgi-bin/jacto/gera-pedido-os.pl");
	}

	if(strlen($msg_erro)==0){
		echo "OK|OS Aprovada com Sucesso!";
	}
	else{
		echo "NO|OS não Aprovada. Erro: $msg_erro";
	}

	exit;
}

if($_GET['repOS'] == 1){
	$os = $_GET['idOS'];
	$posto = $_GET['posto'];
	$motivo = $_GET['motivo'];
	$auditoria = $_GET['auditoria'];

	$msg = ($auditoria == 67) ? 'OS Reincidente de Número de Série reprovada pelo fabricante ' : 'OS sem Número de Série reprovada pelo fabricante ';

	if ($auditoria == 67 && $login_fabrica == 74) { // HD 708057

		$sql = "SELECT email FROM tbl_posto WHERE posto = $posto";
		$res = pg_query($con,$sql);

		$email_posto = @pg_result($res,0,0);

		if (!empty($email_posto)) {

			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'To: <'.$email_posto.'>' . "\r\n";
			$headers .= 'From: Suporte Telecontrol <helpdesk@telecontrol.com.br>' . "\r\n";

			$assunto = "OS $os - Reprovada por Auditoria";
			$msg_email = "A Atlas Ind. de Eletrodomésticos LTDA informa que:<br />
					Sua OS $os foi reprovada pelo motivo:<br /><br />
					$motivo <br /><br />
					Maiores dúvidas favor entrar em contato com a Atlas Ind. de Eletrodomésticos LTDA.";

			mail ($email_posto, utf8_encode($assunto), utf8_encode($msg_email), $headers);
		}

	}

	if ($login_fabrica == 74 ) {

		$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao,admin)
				VALUES ($os,13, current_timestamp,'$msg',$login_admin)";

		$res = pg_query($con,$sql);

		$sql = "SELECT sua_os FROM tbl_os WHERE os = $os AND consumidor_revenda = 'R'";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res)){
			$sua_os = pg_result($res,0,0);
		}

		$sql = "UPDATE tbl_os SET data_fechamento = CURRENT_DATE, finalizada = CURRENT_TIMESTAMP WHERE os = $os";
		$res = pg_query($con,$sql);

	}
	else {
		$sql = "INSERT INTO tbl_os_status
				(os,status_os,data,observacao,admin)
				VALUES ($os,15,current_timestamp,'$msg',$login_admin)";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		$sql = "UPDATE tbl_os set excluida = 't' where os = $os";
		$res = pg_exec($con,$sql);
	
		$sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin)";
		$res = pg_exec($con, $sql);

		#158147 Paulo/Waldir desmarcar se for reincidente
		$sql = "SELECT fn_os_excluida_reincidente($os,$login_fabrica)";
		$res = pg_exec($con, $sql);
	}

	$msg_erro .= pg_errormessage($con);

	$os = (!empty($sua_os)) ? $sua_os : $os;
	
	$mensagem = "A OS : ".$os." foi reprovada da intervenção técnica <br> <b>Justificativa :</b> ".$motivo;
	$sql = "INSERT INTO tbl_comunicado
						(mensagem        ,
						tipo             ,
						fabrica          ,
						obrigatorio_site ,
						descricao        ,
						posto            ,
						ativo)
						VALUES
						('$mensagem',
						'Comunicado',
						$login_fabrica,
						't',
						'Reprovação Intervenção Técnica',
						$posto,
						't')";
	$res = pg_query($con,$sql);

	$msg_erro .= pg_errormessage($con);
	
	$acao = ($login_fabrica == 74) ? 'Reprovada' : 'Excluída';
	
	if(strlen($msg_erro)==0){
		echo "OK|OS $acao com Sucesso!";
	}
	else{
		echo "NO|OS não Excluída. Erro: $msg_erro";
	}
	exit;
}

if($_GET['listar'] == 1){
	$os_consulta = $_GET['idOS'];
	
	$sqlSoaf = "SELECT  tbl_tipo_soaf.tipo_soaf,
								tbl_tipo_soaf.descricao
							FROM tbl_tipo_soaf
							WHERE tbl_tipo_soaf.fabrica = $login_fabrica
							AND tbl_tipo_soaf.ativo IS TRUE";
	$resSoaf = pg_query($con,$sqlSoaf);

	
	$sql = "SELECT  tbl_os_item.os_item,
					tbl_peca.referencia,
					tbl_peca.descricao,
					 tbl_os_produto.os
				FROM tbl_os_item
				JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
				JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				WHERE tbl_os_produto.os = $os_consulta";
	$res = pg_query($con,$sql);

	if(pg_numrows($res) > 0){
		$retorno = "<td colspan='100%'>
						<table width='100%' class='tabela'>
							<tr class='titulo_coluna'>
								<td>Referencia</td>
								<td>Pe&ccedil;a</td>
								<td>SOAF</td>
							</tr>";
		for($i = 0; $i < pg_numrows($res); $i++){
			$os_item    = pg_result($res,$i,os_item);
			$referencia = pg_result($res,$i,referencia);
			$peca       = pg_result($res,$i,descricao);
			$os       = pg_result($res,$i,os);
			
			$cor = ($i % 2) ?  "#F7F5F0" : "#F1F4FA";
			
			$combo = "";
			for($x = 0; $x < pg_numrows($resSoaf); $x++){
				$tipo_soaf = pg_result($resSoaf,$x,tipo_soaf);
				$soaf      = pg_result($resSoaf,$x,descricao);
				$combo .="<option value='$os_item/$tipo_soaf'>$soaf</option>";
			}
			$retorno .= "<tr bgcolor='$cor'>
							<td  align='left'>$referencia</td>
							<td  align='left'>$peca</td>
							<td align='center'><input type='checkbox' name='check_".$os."_".$i."' id='check_".$os."_".$i."' onclick='mostraCombo(".$os.",".$i.");'> &nbsp;
								<select id='soaf_".$os."_".$i."' style='display:none;'>
									$combo
								</select>
							</td>
						</tr>";
		}
		$retorno .= "</table></td>";

		echo "OK|$retorno|$i";
	} else{
		echo "NO|<td colspan='100%' align='center'>Nenhum resultado encontrado</td>";
	}
	
	exit;
}

$btn_acao = $_POST['btn_acao'];
if($btn_acao == "Pesquisar"){
	$os = $_POST['os'];
	$posto_codigo    = $_POST['posto_codigo'];
	$posto_descricao = $_POST['posto_descricao'];
	$data_inicial    = $_POST['data_inicial'];
	$data_final      = $_POST['data_final'];
	$tipo_auditoria  = $_POST['tipo_auditoria'];
	
	if($tipo_auditoria == 148){
		$cond = " AND   tbl_os_status.status_os IN (148)
				  AND tbl_os.data_digitacao::date < current_date-interval '70 days'";
		$condStatus = "148,149";
		$status = "148";
		$intervalo = 70;
	}
	elseif($tipo_auditoria == 150){
		$cond .= " AND   tbl_os_status.status_os IN (150)
				  AND tbl_os.data_digitacao::date < current_date-interval '30 days'";
		$condStatus = "150,151";
		$status = "150";
		$intervalo = 30;
	}
	else if ($tipo_auditoria == 67) { // HD 708057
		$cond .= " AND   tbl_os_status.status_os IN (67)
				  AND tbl_os.data_digitacao::date > current_date-interval '30 days'";
		$condStatus = "131,135,67,103";
		$status = "67";
	}
	else if ($tipo_auditoria == 0) {

		 $cond .= " AND   tbl_os_status.status_os IN (67,102,148,150)
                   AND tbl_os.data_digitacao::date > current_date-interval '30 days'";
         $condStatus = "13,67,102,103,131,135,148,149,150,151";
         $status = "67,102,148,150";

	}
	else if ($tipo_auditoria == 62) { // HD 697853
		$cond .= " AND   tbl_os_status.status_os IN (62)";
		$condStatus = "62,64,81";
		$status = "62";
	}
	else{
		$cond .= " AND   tbl_os_status.status_os IN (102)
				  AND tbl_os.data_digitacao::date < current_date-interval '30 days'";
		$condStatus = "102,103,64";
		$status = "102";
	}

	if(!empty($os)){

		$campo = (strpos($os,'-') ) ? 'sua_os' : 'os';
		$os = $campo == 'sua_os' ? "'$os'" : $os;
		$sql = "SELECT os FROM tbl_os where $campo = $os AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		if(pg_numrows($res) == 0){
			$msg_erro = "OS não Cadastrada";
		}
		else{
			$condOS = " AND tbl_os.$campo = $os";
			
		}
	}

	else{
		

		if(!empty($posto_codigo)){
			$sql = "SELECT posto from tbl_posto_fabrica WHERE codigo_posto = '$posto_codigo'";
			$res = pg_query($con,$sql);
			if(pg_numrows($res) == 0){
				$msg_erro = "Posto não Encontrado";
			}
			else{
				$posto = pg_result($res,0,posto);
				$condPosto = " AND tbl_os.posto = $posto";
			}
		}

		if(!empty($data_inicial) && !empty($data_final)){
			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi)){ 
				$msg_erro = "Data Inválida";
			}
			
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf)){ 
			 $msg_erro = "Data Inválida";
			}

			if(strlen($msg_erro)==0){
				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final = "$yf-$mf-$df";

				if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
					$msg_erro = "Data Inválida";
				}
			}

			$cond .= " AND tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
		}


	}
}

$os = str_replace("'","",$os);
$layout_menu = "auditoria";
$title = "AUDITORIA DE OS ABERTA";

include "cabecalho.php";

?>

<style type="text/css">
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.espaco{
	padding: 0 0 0 100px;
}

caption{
	height:25px; 
	vertical-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>



<?
	include 'javascript_calendario.php';
?>
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script language='javascript'>
$(document).ready(function(){
	$('#data_inicial').datePicker({startDate : '01/01/2000'});
	$('#data_final').datePicker({startDate : '01/01/2000'});
	$("#data_inicial").maskedinput("99/99/9999");
	$("#data_final").maskedinput("99/99/9999");

	$("#os").numeric({allow: '-'});
});

function fnc_pesquisa_posto2(campo, campo2, tipo) {
    if (tipo == "codigo" ) {
        var xcampo = campo;
    }

    if (tipo == "nome" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url="posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
        janela.codigo  = campo;
        janela.nome    = campo2;

        if ("<? echo $pedir_sua_os; ?>" == "t") {
            janela.proximo = document.frm_os.sua_os;
        }else{
            janela.proximo = document.frm_os.data_digitacao;
        }
        janela.focus();
    }

    else{
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }
}

function aprovaOS(os,dias){

		if(confirm('Deseja APROVAR esta Ordem de Serviço?')){
			
			var qtde = $("#qtde_itens_"+os).val();
			var itens_soaf = null;
			
			for(var i = 0; i < qtde; i++){
				var check_item = $("#check_"+os+"_"+i).attr("checked");
				if(check_item == true){
					val_itens_soaf = $("#soaf_"+os+"_"+i).val();
					if(parseFloat(itens_soaf) > 0){
						itens_soaf +=  ","+val_itens_soaf;
					}else
						itens_soaf =  val_itens_soaf;
				}
			}



			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>",
				cache: false,
				type: "POST",
				data: {
					aprOS : 1,
					idOS : os,
					dias : dias,
					itens_soaf : itens_soaf
				},
				success: function(data){					
					retorno = data.split('|');
					if(retorno[0]=="OK"){
						$('#aprova_'+os).remove();
						$('#reprova_'+os).remove();
						$('#lista_itens_'+os).toggle();
						alert(retorno[1]);
					}
					else{
						alert(retorno[1]);
					}
				}
			});	
			
		}
	}

function abreMotivo(os){
	
	$("#linha_motivo_"+os).toggle();
}

function reprovaOS(os,posto,dias,auditoria){
	var motivo = $("#motivo_"+os).val();
	if(motivo == ""){
		alert("Informe uma justificativa");
	}
	else{
		if(confirm('Deseja REPROVAR esta Ordem de Serviço?')){
			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?repOS=1&idOS="+os+"&posto="+posto+"&motivo="+motivo+"&auditoria="+auditoria,
				cache: false,
				success: function(data){
						retorno = data.split('|');
						if(retorno[0]=="OK"){
							alert(retorno[1]);
							$('#'+os).remove();
							$("#linha_motivo_"+os).remove();
						}
						else{
							alert(retorno[1]);
						}
				}
			});	
			
		}
	}
}

function listarItens(os){
		
		var linha = document.getElementById('lista_itens_'+os).style;

		if(linha.display == "none"){
		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?listar=1&idOS="+os,
			cache: false,
			success: function(data){					
				retorno = data.split('|');
				if(retorno[0]=="OK"){
					$('#lista_itens_'+os).html(retorno[1]);
					$('#qtde_itens_'+os).val(retorno[2]);
					$('#lista_itens_'+os).toggle();
				}
				else{
					$('#lista_itens_'+os).html(retorno[1]);
					$('#lista_itens_'+os).toggle();
				}
			}
		});	
		} else {
			linha.display = "none";
		}
		
	}

	function mostraCombo(os,linha){
		$("#soaf_"+os+"_"+linha).toggle();
	}
</script>
<div class='texto_avulso'>
	Este Relatório considera a data de Abertura das OS
</div> <br />
<? if(strlen($msg_erro) > 0){?>
	<table align='center' width='700' class='msg_erro'>
		<tr><td><? echo $msg_erro; ?> </td></tr>
	</table>
<? } ?>
<form name='frm_pesquisa' method='post' action='<? echo $PHP_SELF; ?>'>
	<table align='center' width='700' class='formulario'>
		<caption class='titulo_tabela'>Parâmetros de Pesquisa</caption>
		<tr><td colspan='2'>&nbsp;</td></tr>
		<tr>
			<td class='espaco'>
				Nº OS <br />
				<input type='text' name='os' id='os' value='<?= $os; ?>' size='15' class="frm">
			</td>
		</tr>
		<tr>
			<td class='espaco'>
				Cod Posto <br />
				<input type="text" name="posto_codigo" id="posto_codigo" class="frm" value="<?php echo $posto_codigo; ?>" size="10" maxlength="30" />&nbsp;
				<img src="imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="javascript: fnc_pesquisa_posto2 (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_descricao,'codigo')">
			</td>

			<td>
				Nome Posto <br />
				<input type="text" name="posto_descricao" id="posto_descricao" class="frm" value="<?php echo $posto_descricao; ?>" size="50" maxlength="50" />&nbsp;
				<img src="imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="javascript: fnc_pesquisa_posto2 (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_descricao,'nome')">
			</td>
		</tr>

		<tr>
			<td class='espaco'>
				Data Inicial <br />
				<input type='text' name='data_inicial' id='data_inicial' size='12' value='<?= $data_inicial; ?>' class="frm">
			</td>

			<td>
				Data Final <br />
				<input type='text' name='data_final' id='data_final' size='12' value='<?= $data_final; ?>' class="frm">
			</td>
		</tr>
		<tr>
			<td colspan='2' class='espaco'>
				Tipo Auditoria <br />
				<select name='tipo_auditoria' class='frm'>
					<?php
						if($login_fabrica <> 87){
					?>
							<option value='150' <? if($tipo_auditoria==150) echo "selected";?>>OS Abertas entre 30 e 70 dias</option>
							<option value='148' <? if($tipo_auditoria==148) echo "selected";?>>OS Abertas a mais de 70 dias</option>
							<option value='102' <? if($tipo_auditoria==102) echo "selected";?>>OS Abertas sem Número de Série</option>
					<?php
						}
						// HD 708057
						if ($login_fabrica == 74) {
							
							$selected = ($tipo_auditoria==67) ? "selected" : '';

							echo '<option value="67" '.$selected.'>OS Reincidente N° de Série 30 dias </option>';

							$selected = ($tipo_auditoria==0) ? "selected" : '';
							echo '<option value="0" '.$selected.'>Todas em auditoria</option>';
						}

				
						// HD 697853
						if ($login_fabrica == 87) {
							
							$selected = ($tipo_auditoria==62) ? "selected" : '';

							echo '<option value="62" '.$selected.'>Intervenção da Fabrica </option>';

						}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td colspan='2' align='center' style='padding:20px 0 10px 0;'>
				<input type='hidden' name='btn_acao' value=''>
				<input type="button" value="Pesquisar" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer;" />
			</td>
		</tr>
	</table>
</form>
<br />
<?
	if(!empty($btn_acao) && empty($msg_erro)){
		
		$sql = "
		SELECT interv_reinc.os INTO temp tmp_status
			FROM (
				  SELECT
					  ultima_reinc.os,
						(SELECT status_os 
							 FROM tbl_os_status 
							 WHERE fabrica_status = $login_fabrica 
							 AND tbl_os_status.os = ultima_reinc.os AND status_os IN ($condStatus) order by data desc LIMIT 1) AS ultimo_reinc_status
					  FROM (SELECT DISTINCT os 
					   FROM tbl_os_status 
					JOIN tbl_os USING(os) 
					WHERE fabrica_status = $login_fabrica 
					$condOS
					$condPosto 
					AND tbl_os.finalizada IS NULL
					AND status_os IN ($condStatus) ) ultima_reinc
				) interv_reinc
			WHERE interv_reinc.ultimo_reinc_status IN ($status);
		
		SELECT distinct tbl_os.os, tbl_os.sua_os                                    ,
				   TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS  data_digitacao  ,
				   tbl_posto_fabrica.posto                                 ,
				   tbl_posto_fabrica.codigo_posto                                 ,
				   tbl_posto.nome                                                 ,
				   tbl_produto.descricao                                          ,
				   tbl_produto.referencia                                         ,
				   (CURRENT_DATE - tbl_os.data_digitacao::date) AS qtde_dias
				FROM tbl_os
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_produto USING(produto)
				JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				LEFT JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os.os IN(SELECT os FROM tmp_status)";
				
				if ($status <> '102')  {
					$sql .= " $cond " ;
				}
				$sql .= $condPosto." ".$condOS;

		//echo nl2br($sql);
		//die;
		$res = pg_exec($con,$sql);
		$total = pg_numrows($res);
		
		if($total > 0){ ?>
			<table align='center'  class='tabela' cellspacing='1'>
			<?php if($tipo_auditoria != 102){ ?>
				<caption class='titulo_tabela'>OS Aberta a mais de <?php echo $intervalo; ?> Dias</caption>
			<?php }
				  else{ ?>
					<caption class='titulo_tabela'>OS Abertas sem Número de Série</caption>
			<?php
				  }
			?>
			<tr class='titulo_coluna'>
				<th>OS</th>
				<?php if($login_fabrica == 87){ ?>
					<th>Itens</th>
				<?php } ?>
				<th>Data Digitacao</th>
				<th>Posto</th>
				<th>Produto</th>
				<th>Qtde Dias</th>
				<?php 
				
					if ($login_fabrica == 74 ) {
					
						echo '<th>Status</th>';
					
					}
				
				?>
				<th colspan='2'>Ação</th>
			</tr>
		<?
			for($i = 0; $i < $total; $i++){
				$os           = pg_result($res,$i,os);
				$sua_os		  = pg_result($res,$i,sua_os);
				$digitacao    = pg_result($res,$i,data_digitacao);
				$posto        = pg_result($res,$i,posto);
				$codigo_posto = pg_result($res,$i,codigo_posto);
				$nome_posto   = pg_result($res,$i,nome);
				$produto      = pg_result($res,$i,descricao);
				$referencia   = pg_result($res,$i,referencia);
				$qtde_dias    = pg_result($res,$i,qtde_dias);
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				if ($login_fabrica == 74) { // HD 708057
					$sql2 = "SELECT DISTINCT tbl_status_os.descricao
							FROM tbl_os_status
							JOIN tbl_status_os USING(status_os)
							WHERE os = $os
							AND tbl_status_os.status_os IN( 67, 102 )";
					$res2 = pg_query($con,$sql2);
					$status_os = array();
					for ($z=0;$z<pg_num_rows($res2);$z++) {
			
						$status_os[] = pg_result($res2,$z,'descricao');
			
					}
					if (!empty($status_os))
						$status_os_desc = implode (' / ',$status_os);
				}
		?>
				<tr bgcolor='<? echo $cor; ?>' id='<? echo $os; ?>'>
					<td><a href='os_press.php?os=<? echo $os; ?>' target='_blank'><? echo ($login_fabrica == 74 && !empty($sua_os)) ? $sua_os : $os; ?></td>
					<?php if($login_fabrica == 87){ ?>
							<td><a href="javascript:void(0);" onclick="javascript: listarItens(<?php echo $os;?>);">+</a></td>
					<?php } ?>
					<td><? echo $digitacao; ?></td>
					<td><? echo $codigo_posto." - ".$nome_posto; ?></td>
					<td><? echo $referencia." - ".$produto; ?></td>
					<td><? echo $qtde_dias; ?></td>
					<?php if ($login_fabrica == 74) { ?>
						<td><?php echo $status_os_desc; ?></td>
					<?php } ?>
					<? if($tipo_auditoria == 148){
						  $intervalo = 69;
						}
						else if ($tipo_auditoria == 150){
						  $intervalo = 30;
						} else {
						  $intervalo = 1001;
						}
					?>
					<td>
						<input type='button' value='Aprovar' id='aprova_<? echo $os; ?>'  onclick='aprovaOS(<? echo $os; ?>,<? echo $intervalo; ?>);'>
						<input type="hidden" name="qtde_itens_<?php echo $os;?>" id="qtde_itens_<?php echo $os;?>">
					</td>
					
					<? if($tipo_auditoria == 102 || $tipo_auditoria == 67 || $tipo_auditoria == 0 || $tipo_auditoria == 62){ ?>
						<td><input type='button' value='Reprovar' id='reprova_<? echo $os; ?>'  onclick='abreMotivo(<? echo $os; ?>);'></td>
					<?
					   }
					?>
				</tr>
				<tr style='display:none;' id='linha_motivo_<? echo $os; ?>'>
					<td colspan='7'>
						Justificativa : <input type='text' name='motivo_<? echo $os; ?>' id='motivo_<? echo $os; ?>' class='frm' size='120'> &nbsp;
						<input type='button' value='Gravar' onclick='reprovaOS(<? echo $os; ?>,<? echo $posto; ?>,<? echo $intervalo; ?>,<?echo $tipo_auditoria;?>);'>
					</td>
				</tr>
				<tr style='display:none;' id='lista_itens_<? echo $os; ?>'></tr>
			<?
			}
			echo '</table>';
		}
		else{
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}

include "rodape.php" ?>
