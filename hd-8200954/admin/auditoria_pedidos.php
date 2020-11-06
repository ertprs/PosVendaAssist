<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";
include 'funcoes.php';

if($_GET['aprOS'] == 1){
	$os = $_GET['idOS'];
	$tipo = $_GET['auditoria'];
	if ($tipo == 'ped') {
		
		$status_os 	= 19;
		$obs 		= 'Pedido Aprovado pelo Fabricante';

	}
	$sql = "UPDATE tbl_os_item
			SET liberacao_pedido = 't'
			FROM tbl_os, tbl_os_produto
			WHERE 
			tbl_os.os = $os
			AND tbl_os.os = tbl_os_produto.os
			AND tbl_os_produto.os_produto = tbl_os_item.os_produto;
			
			INSERT INTO tbl_os_status
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
				'$obs',
				$login_fabrica
				)";
	
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

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

	if ($auditoria == 'ped') {
		
		$status_os = 81;

	}

	$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao,admin)
			VALUES ($os,$status_os, current_timestamp,'$motivo',$login_admin)";

	$res = pg_query($con,$sql);

	$sql = "SELECT sua_os FROM tbl_os WHERE os = $os AND consumidor_revenda = 'R'";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res)){
		$sua_os = pg_result($res,0,0);
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
	
	$acao = 'Reprovada';
	
	if(strlen($msg_erro)==0){
		echo "OK|OS $acao com Sucesso!";
	}
	else{
		echo "NO|OS não Excluída. Erro: $msg_erro";
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
	
	if($tipo_auditoria == 'ped'){
		$cond = " AND  tbl_os_item.liberacao_pedido IS NOT TRUE
				  AND  tbl_os_item.pedido IS NULL
				  AND  (tbl_os.status_os_ultimo <> 81 or tbl_os.status_os_ultimo is null)";

		$sqlJoin = "JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado= tbl_servico_realizado.servico_realizado AND gera_pedido AND troca_de_peca";
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
$title = "AUDITORIA DE OS ABERTA COM PEDIDO EM GARANTIA";

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

function aprovaOS(os,numero,auditoria){
		if(confirm('Deseja APROVAR esta Ordem de Serviço?')){
			
			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?aprOS=1&idOS="+os+"&auditoria="+auditoria,
				cache: false,
				success: function(data){					
					retorno = data.split('|');
					if(retorno[0]=="OK"){
						alert(retorno[1]);
						$('#aprova_'+numero).remove();
						$('#reprova_'+numero).remove();
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
					<option value='ped' <? if($tipo_auditoria=='ped') echo "selected";?>>Liberação de Pedido</option>
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
		SELECT tbl_os.os
		INTO TEMP tmp_status
		FROM tbl_os

		JOIN tbl_os_produto USING(os)
		JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto AND tbl_os_item.fabrica_i=tbl_os.fabrica
		$sqlJoin

		WHERE tbl_os.fabrica = $login_fabrica
		$cond $condPosto $condOS;
		
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
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=tbl_os.fabrica
				JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				LEFT JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os AND tbl_os_status.fabrica_status=$login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os.os IN(SELECT os FROM tmp_status)";
				
				$sql .= $condPosto." ".$condOS;

	#	echo nl2br($sql); die;
		$res = pg_exec($con,$sql);
		$total = pg_numrows($res);
		
		if($total > 0){ ?>
			<table align='center'  class='tabela' cellspacing='1'>
			<?php if($tipo_auditoria == 'ped'){ ?>
				<caption class='titulo_tabela'>OS Abertas com Pedido em Garantia</caption>
			<?php } ?>
			<tr class="titulo_coluna">
				<th>OS</th>
				<th>Data Digitacao</th>
				<th>Posto</th>
				<th>Produto</th>
				<th>Qtde Dias</th>
				<?php 
					echo '<th>Status</th>';
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
				
				$sql2 = "SELECT DISTINCT tbl_status_os.descricao
						FROM tbl_os_status
						JOIN tbl_status_os USING(status_os)
						WHERE os = $os";
				$res2 = pg_query($con,$sql2);
				$status_os = array();
				for ($z=0;$z<pg_num_rows($res2);$z++) {
		
					$status_os[] = pg_result($res2,$z,'descricao');
		
				}
				if (!empty($status_os))
					$status_os_desc = implode (' / ',$status_os);
				
		?>
				<tr bgcolor='<? echo $cor; ?>' id='<? echo $os; ?>'>
					<td><a href='os_press.php?os=<? echo $os; ?>' target='_blank'><? echo ($login_fabrica == 74 && !empty($sua_os)) ? $sua_os : $os; ?></td>
					<td><? echo $digitacao; ?></td>
					<td><? echo $codigo_posto." - ".$nome_posto; ?></td>
					<td><? echo $referencia." - ".$produto; ?></td>
					<td><? echo $qtde_dias; ?></td>
					<td><?php echo $status_os_desc; ?></td>
					<? if($tipo_auditoria == 148){
						  $intervalo = 69;
						}
						else if ($tipo_auditoria == 150){
						  $intervalo = 30;
						} else {
						  $intervalo = 1001;
						}
					?>
					<td><input type='button' value='Aprovar' id='aprova_<? echo $os; ?>'  onclick="aprovaOS(<? echo $os; ?>,<? echo $os; ?>,'<? echo $tipo_auditoria; ?>');"></td>
					
					<? if($tipo_auditoria == 102 || $tipo_auditoria == 67 || $tipo_auditoria == 0){ ?>
						<td><input type='button' value='Reprovar' id='reprova_<? echo $os; ?>'  onclick='abreMotivo(<? echo $os; ?>);'></td>
					<?
					   }
					?>
				</tr>
				<tr style='display:none;' id='linha_motivo_<? echo $os; ?>'>
					<td colspan='7'>
						Justificativa : <input type='text' name='motivo_<? echo $os; ?>' id='motivo_<? echo $os; ?>' class='frm' size='120'> &nbsp;
						<input type='button' value='Gravar' onclick="reprovaOS(<? echo $os; ?>,<? echo $posto; ?>,<? echo $intervalo; ?>,'<?echo $tipo_auditoria;?>');">
					</td>
				</tr>
			<?
			}
			echo '</table>';
		}
		else{
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}

include "rodape.php" ?>
