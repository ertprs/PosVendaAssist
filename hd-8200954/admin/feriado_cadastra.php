<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


$title = "CADASTRO DE FERIADOS";
$layout_menu = "cadastro";
include 'funcoes.php';
$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

if($_POST['ajax']){

$feriado = $_POST['feriado'];
$acao    = $_POST['acao'];

	if(strlen($feriado)>0 and strlen($acao)>0){
		if($acao=="inativar"){
			$sql = "UPDATE tbl_feriado set ativo='f' where feriado = $feriado and fabrica =  $login_fabrica";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(strlen($msg_erro)==0){
				echo "ok";
			}
		}
		if($acao=="ativar"){
			$sql = "UPDATE tbl_feriado set ativo='t' where feriado = $feriado and fabrica =  $login_fabrica";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(strlen($msg_erro)==0){
				echo "ok";			
			}
		}
	}
	exit;
}
$btn_acao = $_POST['btn_acao'];

if(strlen($btn_acao)>0){
	$data_inicial_01 = $_POST['data_inicial_01'];

//Início Validação de Datas
if($data_inicial_01){
$dat = explode ("/", $data_inicial_01 );//tira a barra
$d = $dat[0];
$m = $dat[1];
$y = $dat[2];
if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
}

if(strlen($msg_erro)==0){
$d_ini = explode ("/", $data_inicial);//tira a barra
$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


$d_fim = explode ("/", $data_final);//tira a barra
$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

$nova_data_inicial = mktime(0,0,0,intval($d_ini[1]),intval($d_ini[0]),intval($d_ini[2])); // timestamp da data inicial
$cont = 0;
while($nova_data_inicial <= $nova_data_final){//enquanto uma data for inferior a outra {
$nova_data_inicial += 86400; // adicionando mais 1 dia (em segundos) na data inicial
$cont++;
}

//Fim Validação de Datas
}

//////////////////////////////////////////////////////////


	$descricao       = $_POST['descricao'];
	if(strlen($data_inicial_01)==0 or $data_inicial_01 == "dd/mm/aaaa"){
		$msg_erro = "Por favor insira a data";
	}
	$data_inicial_01 = fnc_formata_data_pg(trim($data_inicial_01));
	if ($data_inicial_01 == 'null') $msg_erro = "Por favor insira a data";

	if(strlen($descricao)==0){
		$msg_erro = traduz("Por favor insira a descrição");
	}

	if ($msg_erro == traduz('Por favor insira a data') or $msg_erro == traduz('Por favor insira a descrição')) {
		$controlgrup = "control-group error";
	}else{
		$controlgrup = "control-group";
	}


	if(strlen($msg_erro)==0){
		$sql = "INSERT INTO tbl_feriado(
							fabrica,
							data,
							descricao,
							ativo
						)values(
							$login_fabrica,
							$data_inicial_01,
							'$descricao',
							't'
						)";

		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		if(strlen($msg_erro)==0){
			$msg_sucesso = traduz("Gravado com Sucesso!");
		}
	}

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
	$(function() {
		// $.dataTableLoad();
		// $.datepickerLoad("data_inicial_01");
		$("#data_inicial_01").datepicker().mask('99/99/9999');

		$(document).on("click", "button[name=ativar]", function () {
			if (ajaxAction()) {
				var feriado = $(this).parent().find("input[name=feriado]").val();
				var that     = $(this);
				
				$.ajax({
					url: "feriado_cadastra.php",
	            	type:"POST",
					data: {
                		ajax:true,
						acao: "ativar", 
						feriado: feriado 
					},
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;

						if (data == "ok") {
							$(that).removeClass("btn-success").addClass("btn-danger");
							$(that).attr({ "name": "inativar", "title": '<?=traduz("Inativa o Feriado")?>' });
							$(that).text('<?=traduz("Inativar")?>');
							$(that).parents("tr").find("font").attr("color","#336633").text('<?=traduz("Ativo")?>');;
						}

						loading("hide");
					}
				});
			}
		});

		$(document).on("click", "button[name=inativar]", function () {
			if (ajaxAction()) {
				var feriado = $(this).parent().find("input[name=feriado]").val();
				var that     = $(this);
				
				$.ajax({
					url: "feriado_cadastra.php",
	            	type:"POST",
					data:{
                		ajax:true,
						acao: "inativar", 
						feriado: feriado 
					},
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;

						if (data == "ok") {
							$(that).removeClass("btn-danger").addClass("btn-success");
							$(that).attr({ "name": "ativar", "title": '<?=traduz("Ativa o Feriado")?>' });
							$(that).text('<?=traduz("Ativar")?>');
							$(that).parents("tr").find("font").attr("color","#CC0033").text('<?=traduz("Inativo")?>');
							//$(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_vermelho.png" });
						}

						loading("hide");
					}
				});
			}
		});
	});
</script>

<? if(strlen($msg_erro)>0) { ?>
<div class='alert alert-error'>
	<h4><? echo $msg_erro; ?></h4>
</div>
<?
}

if( strlen( $msg_sucesso ) > 0 ) 
{ 
?>
<div class="alert alert-success">
	<h4><? echo $msg_sucesso; ?></h4>
</div>

<? 
} 
?>

<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<FORM name='frm_pesquisa' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario">
<div class="titulo_tabela"><?=traduz('Cadastro de Feriados')?></div>
<br>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span2">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'><?=traduz('Data')?></label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<input type="text" name="data_inicial_01" id="data_inicial_01" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
				</div>
			</div>
		</div>
		<div class="span1"></div>
		<div class="span5">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'><?=traduz('Descrição')?></label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
   					<textarea type='text' class="span12" name='descricao' size='30' maxlength='255' value=''></textarea>
				</div>	
   			</div>
   		</div>
   		<div class="span2"></div>
   	</div> 
<br />
<br />
	<div class="row-fluid">
            <!-- margem -->
            <div class="span4"></div>

            <div class="span4">
                <div class="control-group">
                    <div class="controls controls-row tac">
                        <input type="hidden" name="btn_acao"  value=''>
                        <button class="btn" name="bt" value='Gravar' onclick="javascript:if (document.frm_pesquisa.btn_acao.value!='') alert('Aguarde Submissão'); else{document.frm_pesquisa.btn_acao.value='Gravar';document.frm_pesquisa.submit();}" ><?=traduz('Gravar')?></button>
                    </div>
                </div>
            </div>

            <!-- margem -->
            <div class="span4"> </div>
        </div>
</form>
<br />
<?	$sql = "SELECT tbl_feriado.data,
				to_char(data,'dd/mm/yyyy') as data_br,
				tbl_feriado.descricao,
				tbl_feriado.feriado ,
				tbl_feriado.ativo
			from tbl_feriado
			where fabrica=$login_fabrica
			order by tbl_feriado.data desc";
	$res = pg_exec($con,$sql);
	if(pg_num_rows($res)>0){

		?>
	<div id="DataTables_Table_0_wrapper" class="dataTables_wrapper form-inline" role="grid" style="width: 850px;">
	<table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
	<thead>
	<tr class='titulo_coluna'>
		<th style="display: none;"></th>
		<th class='date_column'><?=traduz('Data')?></th>
		<th><?=traduz('Status')?></TH>
		<th><?=traduz('Descrição')?></th>
		<th><?=traduz('Ações')?></th>
    </tr>
    </thead>
<?
		for($x=0;pg_numrows($res)>$x;$x++){
			$cor       = ($x % 2) ? "#F7F5F0" : "#F1F4FA";
			$data      = pg_result($res,$x,data);
			$data_br   = pg_result($res,$x,data_br);
			$descricao = pg_result($res,$x,descricao);
			$feriado   = pg_result($res,$x,feriado);
			$ativo     = pg_result($res,$x,ativo);
			?>
			<tr>
				<td style="display: none;"><?=$data?></td>
				<td><?=$data_br?></td>
			<?php
			if($ativo=="t"){ 
			    echo "<td><font color='#336633'>".traduz("Ativo")."</font></td>\n";
			}else{
				echo "<td><font color='#CC0033'>".traduz("Inativo")."</font></td>\n";
			}
			
			echo "<td>$descricao</TD>\n";
			?>
			<td class="tac">
				<input type="hidden" name="feriado" value="<?=$feriado?>" />
				<?php
				if ($ativo == "f") {
					echo "<button type='button' name='ativar' class='btn btn-small btn-success' title='".traduz("Ativa o Feriado")."' >".traduz("Ativar")."</button>";
				} else {
					echo "<button type='button' name='inativar' class='btn btn-small btn-danger' title='".traduz("Inativa o Feriado")."' >".traduz("Inativar")."</button>";
				}	
				?>
			</td>
			<?
			//echo "<td><a href='$PHP_SELF?acao=ativar&feriado=$feriado'><img border='0' src='imagens_admin/btn_ativar.gif' alt='Ativar Feriado'></a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href='$PHP_SELF?acao=apagar&feriado=$feriado'><img border='0' src='imagens_admin/btn_fechar2.gif' alt='Apagar Feriado'></a></TD>\n";
			
			echo "</tr>\n";
		}
		?>
	</table>
	</div>
	<?

	}else{
		echo "<center>".traduz("Nenhum feriado cadastrado")."</center>";
	}
?>
	<script type="text/javascript">
		$.dataTableLoad({table: "#tabela"});
	</script>
<?
include "rodape.php";

?>
