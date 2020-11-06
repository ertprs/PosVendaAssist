<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

#Lista de todas as siglas de estagos Brasileiros
$estadosBrasil = array("AC"=>"Acre", "AL"=>"Alagoas", "AM"=>"Amazonas", "AP"=>"Amapá","BA"=>"Bahia","CE"=>"Ceará","DF"=>"Distrito Federal","ES"=>"Espírito Santo","GO"=>"Goiás","MA"=>"Maranhão","MT"=>"Mato Grosso","MS"=>"Mato Grosso do Sul","MG"=>"Minas Gerais","PA"=>"Pará","PB"=>"Paraíba","PR"=>"Paraná","PE"=>"Pernambuco","PI"=>"Piauí","RJ"=>"Rio de Janeiro","RN"=>"Rio Grande do Norte","RO"=>"Rondônia","RS"=>"Rio Grande do Sul","RR"=>"Roraima","SC"=>"Santa Catarina","SE"=>"Sergipe","SP"=>"São Paulo","TO"=>"Tocantins");

function getAcrescimosOrigem($acrescimos_origem){
    $acrescimos_origem = str_replace("{","",$acrescimos_origem);
    $acrescimos_origem = str_replace("}","",$acrescimos_origem);
    if(strlen($acrescimos_origem) > 0){
		$valores = explode(",", $acrescimos_origem);
		return $valores;
    } else{
		return array(0,0);
    }
}


#Recebe as variáveis 
if (strlen($_GET["acrescimo_tributario"]) > 0)  $acrescimo_tributario = trim($_GET["acrescimo_tributario"]);
if (strlen($_POST["acrescimo_tributario"]) > 0) $acrescimo_tributario = trim($_POST["acrescimo_tributario"]);

if (strlen($_POST["acrescimo"]) > 0) $acrescimo = trim($_POST["acrescimo"]);

if ($_POST["acrescimo_cod_origem_1_2_3_8"]) {
    $acrescimo_cod_origem_1_2_3_8 = trim($_POST["acrescimo_cod_origem_1_2_3_8"]);
}else{
    $acrescimo_cod_origem_1_2_3_8 = 0;
}

if ($_POST["acrescimo_cod_origem_0_4_5"]) {
    $acrescimo_cod_origem_0_4_5 = trim($_POST["acrescimo_cod_origem_0_4_5"]);
}else{
    $acrescimo_cod_origem_0_4_5 = 0;
}

if (strlen($_POST["estado"]) > 0) $estado = trim($_POST["estado"]);

if (strlen($_GET["del"]) == 1) $remover = $_GET["del"];

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}
if ($remover == 1 && $acrescimo_tributario > 0) {
	$res = pg_query ($con,"BEGIN TRANSACTION");

	$sql = "UPDATE 	tbl_acrescimo_tributario
			SET  	data_final = NOW(),
					admin = ".$login_admin."
			WHERE 	acrescimo_tributario = ".$acrescimo_tributario." 
			AND 	fabrica = ".$login_fabrica.";";
	
	$res = pg_query ($con,$sql);
	
	if(pg_last_error()){
		$msg_erro["msg"][] = pg_last_error();
	}

	if (count($msg_erro["msg"]) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		$msg_success["msg"][] = "Registro excluído com sucesso!";
	} else {
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
	$acrescimo                    = "0,000";
	$acrescimo_cod_origem_1_2_3_8 = "0,000";
	$acrescimo_cod_origem_0_4_5   = "0,000";

}

if ($btnacao == "gravar") {

	$acrescimo = str_replace('.','',$acrescimo);
	$acrescimo = str_replace(",",".",$acrescimo);

    if($login_fabrica == 1){
		$acrescimo_cod_origem_1_2_3_8 = str_replace(".","",$acrescimo_cod_origem_1_2_3_8);
		$acrescimo_cod_origem_1_2_3_8 = str_replace(",",".",$acrescimo_cod_origem_1_2_3_8);
	    
		$acrescimo_cod_origem_0_4_5 = str_replace(".","",$acrescimo_cod_origem_0_4_5);
		$acrescimo_cod_origem_0_4_5 = str_replace(",",".",$acrescimo_cod_origem_0_4_5);
    }
	
	if(strlen($acrescimo) == 0 || $acrescimo == "0.000") {
		$msg_erro["msg"][] = "Favor informar o valor do Acréscimo Tributário";
		$msg_erro["campos"][] = "acrescimo";
	}

	if (count($msg_erro["msg"]) == 0) {

		$res = pg_query ($con,"BEGIN TRANSACTION");

		if($login_fabrica == 1){
			$acrescimos_origem = ", '{".$acrescimo_cod_origem_1_2_3_8 ." , ".$acrescimo_cod_origem_0_4_5."}'";
			$colunaAcrescimosOrigem = ", acrescimos_origem ";
		}else{
			$acrescimos_origem = "";
			$colunaAcrescimosOrigem = "";
		}

		###INSERE NOVO REGISTRO
		$sql = "INSERT INTO tbl_acrescimo_tributario
			(estado,fabrica,acrescimo,data_inicio,data_final,admin {$colunaAcrescimosOrigem})
			VALUES
			('$estado',$login_fabrica,$acrescimo,now(),null,$login_admin {$acrescimos_origem});";
		$res = pg_query ($con,$sql);
		
		if(pg_last_error()){
			$msg_erro["msg"][] = "Erro: " . pg_last_error();	
		}
	}

	if (count($msg_erro["msg"]) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		$msg_success["msg"][] = "Registro salvo com sucesso";
	} else {
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
	
	$acrescimo                    = "0,000";
	$acrescimo_cod_origem_1_2_3_8 = "0,000";
	$acrescimo_cod_origem_0_4_5   = "0,000";
}

if($acrescimo_tributario > 0){
    
	$sql = 'SELECT acrescimo, estado, acrescimos_origem FROM tbl_acrescimo_tributario WHERE acrescimo_tributario='.$acrescimo_tributario.'AND fabrica = '.$login_fabrica;
	$res = pg_query ($con,$sql);
	$total = pg_numrows($res);

	if($total>0){
		$acrescimo  = pg_result($res,0,'acrescimo');
		$estado  = pg_result($res,0,'estado');
		if($login_fabrica == 1){
			$acrescimos_origem = pg_fetch_result($res, 0, "acrescimos_origem");
			list($acrescimo_cod_origem_1_2_3_8, $acrescimo_cod_origem_0_4_5) = getAcrescimosOrigem($acrescimos_origem);

	                
		}
	}
	
}

$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE ACRÉSCIMO TRIBUTÁRIO";
include 'cabecalho_new.php';

?>

<?php if (count($msg_erro["msg"]) > 0) {?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }

if (count($msg_success["msg"]) > 0) { ?>
    <div class="alert alert-success">
		<h4><?=implode("<br />", $msg_success["msg"])?></h4>
    </div>
<?php } ?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_acrescimo" method="post" action="<?=$PHP_SELF?>" align='center' class='form-search form-inline tc_formulario'>
	<input type="hidden" name="acrescimo_tributario" value="<?=$acrescimo_tributario?>" />
	<div class='titulo_tabela '>Cadastro de Acréscimo Tributário</div>
	<br/>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class="control-label" for=''>Estado</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class="asteristico">*</h5>
						    <select name="estado" id="estado" class="frm">
								<?php
								foreach($estadosBrasil as $sigla => $est){
									?>
									<option value="<?php echo $sigla;?>" <?php echo ($sigla == $estado) ? 'selected="selected"' : null;?>>
										<?php echo $est;?>
									</option>
									<?
								}
								?>
							</select>
						</div>
				    </div>
				</div>
			</div>
			<div class="span4">
				<div class='control-group <?=(in_array("acrescimo", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class="control-label" for="">Acréscimo (%)</label>
					<div class="controls controls-row">
						<div class='span12'>
							<h5 class="asteristico">*</h5>
							<input  type="text" id="acrescimo" name="acrescimo" value="<?=number_format($acrescimo,3,',','')?>" maxlength="8" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
	<?php if($login_fabrica == 1) {?>
		<br/>
		<div class='titulo_tabela '>Acréscimo (%) por Regra de ICM</div>
		<br/>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span4'>
                <div class='control-group'>
					<label class="control-label" for=''>Cód. de Origem 1, 2, 3 e 8 </label>
                    <div class='controls controls-row'>
                        <div class='span12'>
				    		<input  type="text" id="acrescimo_cod_origem_1_2_3_8" name="acrescimo_cod_origem_1_2_3_8" value="<?=number_format($acrescimo_cod_origem_1_2_3_8,3,',','')?>" maxlength="3" />
                        </div>
                    </div>
                </div>
            </div>
			<div class="span4">
				<div class='control-group'>
					<label class="control-label" for="">Cód. de Origem 0, 4, e 5</label>
					<div class="controls controls-row">
						<div class='span12'>
							<input  type="text" id="acrescimo_cod_origem_0_4_5" name="acrescimo_cod_origem_0_4_5" value="<?=number_format($acrescimo_cod_origem_0_4_5,3,',','')?>" maxlength="3" />
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>
		<p><br/>
            <input type='hidden' name='btnacao' value=''>
			<input type="button" value="Gravar" class="btn btn-success" onclick="if(document.frm_acrescimo.btnacao.value == ''){ document.frm_acrescimo.btnacao.value='gravar';document.frm_acrescimo.submit()}else{alert('Aguarde submissão') }" alt="Gravar formulário" border='0' style='cursor:pointer'> &nbsp;
			<input type="button" value="Limpar" class="btn btn-warning" onclick="window.location='<? echo $PHP_SELF ?>';return false;" alt="Limpar campos" border='0' style='cursor:pointer'>
        </p><br/>
</form>


<table class="table table-striped table-bordered table-hover table-fixed">
	<thead>
		<tr class='titulo_tabela'>
			<th colspan='6'>Relação dos Acréscimos por Estado</th>
		</tr>
		<tr class="titulo_tabela">
			<th width="50%">Estado</th>
			<th>Acréscimo Tributário</th>
			
			<?php if($login_fabrica == 1) {?>
				<th colspan="2">Acréscimos (%) por Regra de ICM</th>
			<?php } ?>

			<th rowspan="2">Ação</th>
		</tr>
		
		<?php if($login_fabrica == 1) {?>
			<tr class="titulo_tabela">
				<th colspan="2"></th>
				<th nowrap>Cód. de Origem 1, 2, 3 e 8 </th>
				<th nowrap>Cód. de Origem 0, 4, e 5 </th>
			</tr>
		<?php } ?>

	</thead>
	<tbody>
		<?php
			$sql = "SELECT  acrescimo_tributario    ,
					estado     ,
					acrescimo,
			acrescimos_origem
			FROM    tbl_acrescimo_tributario
			WHERE   fabrica = $login_fabrica
			AND 	data_final IS NULL
			ORDER BY estado";

			$res = pg_query ($con,$sql);
			$total = pg_numrows($res);

			for ($i=0;$i<$total;$i++) {
				$acrescimo_tributario  = pg_result($res, $i, 'acrescimo_tributario');
				$estado                = pg_result($res, $i, 'estado');
				$acrescimo             = pg_result($res, $i, 'acrescimo');
				$acrescimos_origem     = pg_fetch_result($res, $i, "acrescimos_origem");

				list($acrescimo_cod_origem_1_2_3_8, $acrescimo_cod_origem_0_4_5) = getAcrescimosOrigem($acrescimos_origem);

				?>
					<tr>
						<td>
							<a href="<?=$PHP_SELF;?>?acrescimo_tributario=<?=$acrescimo_tributario;?>"><?=$estadosBrasil[$estado];?></a>
						</td>
						<td>
							<a href="<?=$PHP_SELF;?>?acrescimo_tributario=<?=$acrescimo_tributario;?>"><?=number_format($acrescimo,3,',','.');?></a>
						</td>

						<?php if($login_fabrica == 1) {?>
							<td><?=number_format($acrescimo_cod_origem_1_2_3_8,3,',','.');?></td>
							<td><?=number_format($acrescimo_cod_origem_0_4_5,3,',','.');?></td>
						<?php } ?>
						
						<td>
							<input type="button" value="Excluir" class='btn btn-danger' onclick="window.location.href='<?=$PHP_SELF;?>?acrescimo_tributario=<?=$acrescimo_tributario;?>&del=1'">
						</td>
					</tr>
				<?php
			}
		?>
	</tbody>
</table>
<br>

<? include "rodape.php"; ?>
