<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$btn_acao = $_POST['btn_acao'];
if($btn_acao == "Pesquisar"){

	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$tipo         = $_POST['tipo'];
	
		//INÍCIO VALIDAÇÃO DATAS
			
			if(empty($data_inicial) and empty($data_final)){
				$msg_erro = "Data Inválida";
			}

			if(!empty($data_inicial) and empty($data_final)){
				$msg_erro = "Data Inválida";
			}

			if(empty($data_inicial) and !empty($data_final)){
				$msg_erro = "Data Inválida";
			}
		if(strlen($msg_erro) == 0){
			if(!empty($data_inicial) and !empty($data_final)){
				if(strlen($msg_erro)==0){
					list($di, $mi, $yi) = explode("/", $data_inicial);
					if(!checkdate($mi,$di,$yi)) 
						$msg_erro = "Data Inválida";
				}
				
				if(strlen($msg_erro)==0){
					list($df, $mf, $yf) = explode("/", $data_final);
					if(!checkdate($mf,$df,$yf)) 
						$msg_erro = "Data Inválida";
				}

				if(strlen($msg_erro)==0){
					$aux_data_inicial = "$yi-$mi-$di";
					$aux_data_final = "$yf-$mf-$df";
				}
				if(strlen($msg_erro)==0){
					if(strtotime($aux_data_final) < strtotime($aux_data_inicial) 
					or strtotime($aux_data_final) > strtotime('today')){
						$msg_erro = "Data Final não pode ser maior do que a Data Atual";
					}
				}

				if(strlen($msg_erro)==0){
					$cond = " AND data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
				}
			}	
		}
		//FIM VALIDAÇÃO DATAS
		
		

}

$layout_menu = "os";
$title = "MOVIMENTAÇÃO FINANCEIRA";

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
	padding: 0 0 0 230px;
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

	$("#os").numeric();
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

function aprovaOS(os,numero,dias){
		if(confirm('Deseja APROVAR esta Ordem de Serviço?')){
			
			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?aprOS=1&idOS="+os+"&dias="+dias,
				cache: false,
				success: function(data){					
					retorno = data.split('|');
					if(retorno[0]=="OK"){
						alert(retorno[1]);
						$('#aprova_'+numero).remove();
					}
					else{
						alert(retorno[1]);
					}
				}
			});	
			
		}
	}

function reprovaOS(os,numero,dias){
	if(confirm('Deseja REPROVAR esta Ordem de Serviço?')){		
		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?repOS=1&idOS="+os,
			cache: false,
			success: function(data){
					retorno = data.split('|');
					if(retorno[0]=="OK"){
						alert(retorno[1]);
						$('#'+os).remove();
					}
					else{
						alert(retorno[1]);
					}
			}
		});	
		
	}
}
</script>
<br />
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
			<td class='espaco' width='130'>
				Data Inicial <br />
				<input type='text' name='data_inicial' id='data_inicial' size='12' value='<?= $data_inicial; ?>' class="frm">
			</td>

			<td>
				Data Final <br />
				<input type='text' name='data_final' id='data_final' size='12' value='<?= $data_final; ?>' class="frm">
			</td>
		</tr>
		<!--
		<tr>
			<td class='espaco' colspan='2'>
				Tipo <br />
				<select name='tipo' id='tipo' class='frm'>
					<option value=''></option>
					<option value='1' <? if($tipo==1) echo "SELECTED"; ?>>A Receber</option>
					<option value='2' <? if($tipo==2) echo "SELECTED"; ?>>A Pagar</option>
				</select>
			</td>
		</tr>
		-->
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
		
		$sql = "SELECT TO_CHAR(tbl_movimento_financeiro.data,'DD/MM/YYYY') as data,
		               tbl_movimento_financeiro.tipo,
					   tbl_movimento_financeiro.titulo as nota_fiscal,
					   tbl_posto.nome,
					   tbl_movimento_financeiro.valor,
					   tbl_movimento_financeiro.total_pago,
					   tbl_movimento_financeiro.saldo
					 FROM tbl_movimento_financeiro
					 JOIN tbl_posto USING(posto)
					WHERE tbl_movimento_financeiro.fabrica = $login_fabrica
					AND tbl_movimento_financeiro.posto = $login_posto
					$cond
				";
		$res = pg_query($con,$sql);
		$total = pg_numrows($res);
		//echo nl2br($sql);
		if($total > 0){ ?>
			<table align='center'  class='tabela' cellspacing='1' width='700'>
			<tr class='titulo_coluna'>
				<th>Emissão</th>
				<th>Tipo</th>
				<th>Nota Fiscal</th>
				<th>Posto</th>
				<th>Total</th>
				<th>Saldo Pendente</th>
				<th>Saldo Final</th>
			</tr>
		<?
			$total_geral = 0;
			$total_receber = 0; 
			$valor_pagar = 0;

			for($i = 0; $i < $total; $i++){
				$data        = pg_result($res,$i,data);
				$tipo        = pg_result($res,$i,tipo);
				$nota_fiscal = pg_result($res,$i,nota_fiscal);
				$nome_posto  = pg_result($res,$i,nome);
				$valor       = pg_result($res,$i,valor);
				$total_pago  = pg_result($res,$i,total_pago);
				$saldo       = pg_result($res,$i,saldo);

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				
				if($tipo == 1){
					$valor_receber += $saldo;
					$aux_tipo = 'A receber';
				}else{
					$valor_pagar += $saldo;
					$aux_tipo = 'A pagar';
				}

				?>
				<tr bgcolor='<? echo $cor; ?>' id='<? echo $os; ?>'>
					<td><? echo $data; ?></td>
					<td><? echo $aux_tipo; ?></td>
					<td><? echo $nota_fiscal; ?></td>
					<td><? echo $nome_posto; ?></td>
					<td align='right'><? echo number_format($valor,2,',','.'); ?></td>
					<td align='right'><? echo number_format($total_pago,2,',','.'); ?></td>
					<td align='right'><? echo number_format($saldo,2,',','.'); ?></td>
				</tr>
			<?
				

			}
			$total_geral = $valor_receber - $valor_pagar;

			if($valor_receber < $valor_pagar){
				$valor_receber = 0;
				$total_geral = $valor_pagar * -1;
			}

			if($valor_receber > $valor_pagar){
				$valor_pagar = 0;
				$total_geral = $valor_receber;

			}

			if($valor_receber == $valor_pagar){
				$valor_receber = 0;
				$valor_pagar = 0;
				$total_geral = 0;
			}

			?>
			<tr class='titulo_coluna'>
				<td colspan='5' align='right'>Total A Receber</td>
				<td>Total A Pagar</td>
				<td>Total Geral</td>
			</tr>
			<tr>
				<td colspan='5' align='right'><? echo number_format($valor_receber,2,',','.'); ?></td>
				<td align='right'><? echo number_format($valor_pagar,2,',','.'); ?></td>
				<td align='right'>
					<? 
						if($total_geral < 0){
							echo "<font color='red'>";
						}
						echo number_format($total_geral,2,',','.'); 

						if($total_geral < 0){
							echo "</font>";
						}
					?>
				</td>
			</tr>
			</table>
		<?
		}
		else{
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}

include "rodape.php" ?>