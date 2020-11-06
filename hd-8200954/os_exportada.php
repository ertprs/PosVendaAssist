<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$btn_acao = $_POST['btn_acao'];
if($btn_acao == "Pesquisar"){

	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$os         = $_POST['os'];
	
		//INÍCIO VALIDAÇÃO DATAS
		if(empty($os)){				
			if(!empty($data_inicial) and empty($data_final)){
				$msg_erro = "Data Inválida";
			}

			if(empty($data_inicial) and !empty($data_final)){
				$msg_erro = "Data Inválida";
			}

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
						$msg_erro = "Data Inválida";
					}
				}

				if(strlen($msg_erro)==0){
					$cond = " AND data_importacao BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
				}
			}	
		}
		//FIM VALIDAÇÃO DATAS
		
		if(!empty($os)){
			$cond .= " AND os = $os";
		}

}

$layout_menu = "os";
$title = "RELATÓRIO OS EXPORTADAS";

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
	empty-cells:show;
}

.espaco{
	padding: 0 0 0 200px;
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

<? if(strlen($msg_erro) > 0){?>
	<table align='center' width='700' class='msg_erro'>
		<tr><td><? echo $msg_erro; ?> </td></tr>
	</table>
<? } ?>
<br />
<form name='frm_pesquisa' method='post' action='<? echo $PHP_SELF; ?>'>
	<table align='center' width='700' class='formulario'>
		<caption class='titulo_tabela'>Parâmetros de Pesquisa</caption>
		<tr><td colspan='2'>&nbsp;</td></tr>
		
		<tr>
			<td class='espaco' colspan='2'>
				OS <br />
				<input type='text' name='os' id='os' size='12' value='<?= $os; ?>' class="frm">
			</td>
		</tr>
		
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
		
		$sql = "SELECT 	os_externa         ,
						os                 ,
						sua_os_offline     ,
						produto_serie      ,
						produto_nota       ,
						TO_CHAR(data_abertura,'DD/MM/YYYY') as  data_abertura    ,
						TO_CHAR(data_conserto,'DD/MM/YYYY') as  data_conserto    ,
						TO_CHAR(data_fechamento,'DD/MM/YYYY') as data_fechamento ,
						cliente_nome       ,
						produto_referencia ,
						produto_nome 
						FROM tbl_os_externa
						WHERE fabrica = $login_fabrica
						AND posto = $login_posto
						$cond
				";
		$res = pg_query($con,$sql);
		$total = pg_numrows($res);
		//echo nl2br($sql);
		if($total > 0){ ?>
			<table align='center'  class='tabela' cellspacing='1' >
			<tr class='titulo_coluna'>
				<th>OS</th>
				<th>OS OFF LINE</th>
				<th>SÉRIE</th>
				<th>NF</th>
				<th>AB</th>
				<th>DC</th>
				<th>FC</th>
				<th>CONSUMIDOR</th>
				<th>PRODUTO</th>
				<th><img border='0' src='imagens/img_impressora.gif'></th>
				<th>AÇÕES</th>
			</tr>
		<?
			
			for($i = 0; $i < $total; $i++){
				$os_externa           = pg_result($res,$i,os_externa);
				$os                   = pg_result($res,$i,os);
				$os_off_line          = pg_result($res,$i,sua_os_offline);
				$serie                = pg_result($res,$i,produto_serie);
				$nota_fiscal          = pg_result($res,$i,produto_nota);
				$data_abertura        = pg_result($res,$i,data_abertura);
				$data_conserto        = pg_result($res,$i,data_conserto);
				$fechamento_conserto  = pg_result($res,$i,data_fechamento);
				$consumidor           = pg_result($res,$i,cliente_nome);
				$produto              = pg_result($res,$i,produto_referencia);
				$produto_nome         = pg_result($res,$i,produto_nome);

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
					<td><? echo $os; ?></td>
					<td><? echo $os_off_line; ?></td>
					<td><? echo $serie; ?></td>
					<td><? echo $nota_fiscal; ?></td>
					<td><? echo $data_abertura; ?></td>
					<td><? echo $data_conserto; ?></td>
					<td><? echo $fechamento_conserto; ?></td>
					<td><? echo $consumidor; ?></td>
					<td><? echo $produto." - ".$produto_nome; ?></td>
					<td><a href='os_print.php?os=$os' target='_blank'><img border='0' src='imagens/img_impressora.gif'></a></td>
					<td>
						<?php
							if(empty($os)){
						?>
								<input type='button' value='Abrir OS' onclick="window.open('os_cadastro_tudo_externa.php?os_externa=<?php echo $os_externa; ?>')">								
						<?php
							}else{
						?>
								<input type='button' value='Consultar' onclick="window.open('os_consulta_lite.php?sua_os=<?php echo $os; ?>&btn_acao=pesquisar')">
						<?php
							}
						?>
					</td>
					
				</tr>
			<?
			}
			?>
			</table>
		<?
		}
		else{
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}

include "rodape.php" ?>