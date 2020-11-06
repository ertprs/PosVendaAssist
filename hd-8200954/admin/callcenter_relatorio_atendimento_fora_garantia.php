<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

$layout_menu = "callcenter";
$title = "RELATÓRIO DE ATENDIMENTO POR ESTADO";

include "cabecalho.php";

?>
<style>

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
	text-align: left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.left{
	padding-left: 220px;
}
</style>

<?php 
	include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});

	function explodeChamados(estado){
		var data_inicial = $("input[name=data_inicial]").val();
		var data_final   = $("input[name=data_final]").val();

		var url = "callcenter_relatorio_atendimento_fora_garantia_por_estado.php?estado="+estado+"&data_inicial="+data_inicial+"&data_final="+data_final;
		window.open(url);
	}

</script>


<script language='javascript' src='../ajax.js'></script>

<? include "javascript_pesquisas.php" ?>



<?
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	
	if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		$msg_erro = "Data Inválida";
	}

	if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{
		$msg_erro = "Data Inválida";
	}

		if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}

	if($xdata_inicial > $xdata_final)
		$msg_erro = "Data Inválida";

	
}

?>

<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<table width='700' class='formulario'  border='0' cellpadding='5' cellspacing='1' align='center'>
	<? if(strlen($msg_erro)>0){ ?>
		<tr class='msg_erro' colspan='2'><td><? echo $msg_erro ?></td></tr>
	<? } ?>
	<tr class='titulo_tabela'>
		<td colspan='2'>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td class='left' width='130'>
			Data Inicial <br />
			<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<?=$data_inicial?>" >
		</td>
		<td>
			Data Final <br />
			<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<?=$data_final?>" >
		</td>
	</tr>
	<tr>
		<td colspan='2' align='center'>
			<input type='submit' name='btn_acao' value='Pesquisar'>
		</td>
	</tr>
</table>
</FORM>

<br /><?php
	
	if (strlen($btn_acao)>0 and strlen($msg_erro) == 0) {

		if($login_fabrica == 74){
            $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
        }

		$sql = "SELECT count(tbl_hd_chamado.hd_chamado) AS ocorrencias,tbl_cidade.estado
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade  = tbl_cidade.cidade
				WHERE tbl_hd_chamado.data BETWEEN '$xdata_inicial' and '$xdata_final'
				AND   tbl_hd_chamado.fabrica = $login_fabrica
				AND tbl_hd_chamado_extra.garantia IS NOT TRUE 
				$cond_admin_fale_conosco 
				GROUP BY tbl_cidade.estado
				ORDER BY tbl_cidade.estado";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
	?>
			<input type='hidden' name='xdata_inicial' value='<?=$xdata_inicial?>'>
			<input type='hidden' name='xdata_final' value='<?=$xdata_final?>'>
			<table align='center' width='700' class='tabela'>
				<tr class='titulo_coluna'>
					<th>UF</th>
					<th>Nº de Ocorrências</th>
				</tr>
	<?php
			for($i = 0; $i < pg_num_rows($res); $i++){
				$uf = pg_fetch_result($res, $i, 'estado');
				$ocorrencias = pg_fetch_result($res, $i, 'ocorrencias');
				$cor = ($i % 2 == 0)? '#F1F4FA'	:"#F7F5F0";

				echo "<tr bgcolor='$cor'>
						<td>$uf</td>
						<td><a href='javascript:void(0);' onclick='explodeChamados(\"$uf\");'>$ocorrencias</a></td>
					  </tr>";
			}
			echo "</table>";
		}else{
			echo "<center>Nenhum resultado encontrado</center>";
		}
						
	}
	?>
		

<? include "rodape.php" ?>
