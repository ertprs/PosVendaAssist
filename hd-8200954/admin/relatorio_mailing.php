<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include "autentica_admin.php";
header("Cache-Control: no-cache, must-revalidate");
header('Pragma: no-cache');
$layout_menu = "callcenter";
$title = "RELATÓRIO DE MAILING";
include "cabecalho_new.php";

include_once 'funcoes.php';

if ( isset($_POST['gerar']) ) {

	$data_inicial = $_POST["data_inicio"];
	$data_final   = $_POST["data_fim"];

	$aux_data_inicial = dateFormat($data_inicial, 'dmy', 'y-m-d');
	$aux_data_final   = dateFormat($data_final,   'dmy', 'y-m-d');

	if (is_bool($aux_data_inicial) or is_bool($aux_data_final) or
		$aux_data_inicial > $aux_data_final) {
		$msg_erro["msg"][]    = traduz("Data Inválida");
		$msg_erro["campos"][] = "data";
	}

	if(count($msg_erro)==0 and $login_fabrica <> 81)
		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -3 month'))
			$msg_erro["msg"][] = traduz('O intervalo entre as datas não pode ser maior que 90 dias.');
}
$plugins = array(
	"datepicker",
	"maskedinput"
);

include 'plugin_loader.php';
?>

<script type="text/javascript">

$(function() {
	$.datepickerLoad(Array("data_fim", "data_inicio"));
});

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	var map = {"â":"a","Â":"A","à":"a","À":"A","á":"a","Á":"A","ã":"a","Ã":"A","ê":"e","Ê":"E","è":"e","È":"E","é":"e","É":"E","î":"i","Î":"I","ì":"i","Ì":"I","í":"i","Í":"I","õ":"o","Õ":"O","ô":"o","Ô":"O","ò":"o","Ò":"O","ó":"o","Ó":"O","ü":"u","Ü":"U","û":"u","Û":"U","ú":"u","Ú":"U","ù":"u","Ù":"U","ç":"c","Ç":"C","ñ":"n"};

    function removerAcentos(string) { 
        return string.replace(/[\W\[\] ]/g,function(a) {
            return map[a]||a}) 
    };


   /** select de provincias/estados */
    $(function() {

    	$("#estado option").remove();
    	
    	$("#estado optgroup").remove();

    	$("#estado").append("<option value=''>TODOS OS ESTADOS</option>");

        var post = "<?php echo $_POST['estado']; ?>";

 		<?php if (in_array($login_fabrica,[181])) { ?> 

            $("#estado").append('<optgroup label="Provincias">');
                
            var select = "";
            
            <?php 

			$provincias_CO = getProvinciasExterior("CO");

            foreach ($provincias_CO as $provincia) { ?>

                var provincia = '<?= $provincia ?>';

                var semAcento = removerAcentos(provincia);

				if (post == semAcento) {

					select = "selected";
				}

            	var option = "<option value='" + semAcento + "' " + select +">" + provincia + "</option>";

                $("#estado").append(option);

                select = "";

            <?php } ?>

                $("#estado").append('</optgroup>');

	  	<?php } ?>

	  	<?php if (in_array($login_fabrica,[182])) { ?>
  			
		  	
		  	$("#estado").append('<optgroup label="Provincias">');
  			
  			var select = "";
                
            <?php 

            $provincias_PE = getProvinciasExterior("PE");

            foreach ($provincias_PE as $provincia) { ?>

                var provincia = '<?= $provincia ?>';

                var semAcento = removerAcentos(provincia);

               	if (post == semAcento) {
                	
                	select = "selected";
                }

            	var option = "<option value='" + semAcento + "' " + select +">" + provincia + "</option>";

                select = "";

			<?php } ?>

			$("#estado").append(option);
		
		<?php } ?>

		<?php if (in_array($login_fabrica,[180])) {  ?>

			$("#estado").append('<optgroup label="Provincias">');

			var select = "";
                
            <?php 

            $provincias_AR = getProvinciasExterior("AR");

            foreach ($provincias_AR as $provincia) { ?>

	            var provincia = '<?= $provincia ?>';

	            var semAcento = removerAcentos(provincia);

	           	if (post == semAcento) {

	            	select = "selected";
	            } 

            	var option = "<option value='" + semAcento + "' " + select +">" + provincia + "</option>";

                $("#estado").append(option);

                select = "";

            <?php } ?>

            $("#estado").append('</optgroup>');

		<?php } ?> 

        <?php if (!in_array($login_fabrica, [180,181,182])) { ?>	

			$("#estado").append('<optgroup label="Estados">');
            
        	<?php foreach ($estados_BR as $sigla => $estado) { ?>

	            var estado = '<?= $estado ?>';
	            var sigla = '<?= $sigla ?>';

            	if (post == sigla) {

            		select = "selected";
            	}

	            var option = "<option value='" + sigla + "'" + select +">" + estado + "</option>";

                $("#estado").append(option);

        	<?php } ?>

        	$("#estado").append('</optgroup>');

		<?php } ?>       
        
    });

</script>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios ')?></b>
</div>
<FORM  METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario">	
	<div class="titulo_tabela"><?=traduz('Parâmetros de Pesquisa')?></div>
	<br />
	<div class='row-fluid'>
        <div class='span2'></div>
        <div class='span3'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicio'><?=traduz('Data Inicial')?></label>
                <div class='controls controls-row'>
                    <div class='span8'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_inicio" id="data_inicio" size="12" maxlength="10" class='span12' value= "<?=$data_inicio?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span3'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_fim'><?=traduz('Data Final')?></label>
                <div class='controls controls-row'>
                    <div class='span8'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_fim" id="data_fim" size="12" maxlength="10" class='span12' value="<?=$data_fim?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class="span3">
			<div class="control-group">
				<label class="control-label" for="estado"><?=traduz('Estado')?></label>
				<div class="controls controls-row">
					<div class="span10">
							<select id="estado" name="estado" class="span12">
								<option value="" ><?=traduz('Selecione')?></option>
							</select>
					</div>
				</div>
			</div>
		</div>

    <div class='row-fluid'>

    </div>
	<br />
	<p class="tac">
		<input type="submit" class="btn" name="gerar" value="<?=traduz("Gerar")?>" />
	</p>
	</div>
</form>

	<?php
		if ( isset($_POST['gerar']) and count($msg_erro) == 0) {

			$data_inicial = $_POST["data_inicio"];
			$data_final   = $_POST["data_fim"];

			if(strlen($msg_erro)==0) {
				$aux_data_inicial = dateFormat($data_inicial, 'dmy', 'y-m-d');
				$aux_data_final   = dateFormat($data_final,   'dmy', 'y-m-d');

				if (is_bool($aux_data_inicial) or is_bool($aux_data_final) or
					$aux_data_inicial > $aux_data_final)
					$msg_erro = traduz("Data inválida");
			}

			if(strlen($msg_erro)==0 and $login_fabrica <> 81)
				if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -3 month'))
					$msg_erro = traduz('O intervalo entre as datas não pode ser maior que 90 dias.');

			if(strlen($msg_erro)==0) {

				if ( !empty($_POST['estado']) )
					$cond = ' AND estado = \'' . $_POST['estado'] . '\'';
				else
					$cond = '';


				$sql = "SELECT DISTINCT
						tbl_hd_chamado_extra.nome,
						email,
						tbl_cidade.nome AS cidade_nome,
						tbl_cidade.estado AS cidade_estado
						$campos

						FROM
						tbl_hd_chamado
						JOIN tbl_hd_chamado_extra USING(hd_chamado)
						JOIN tbl_cidade USING(cidade)

						WHERE
						email<>''
						AND tbl_hd_chamado_extra.email IS NOT NULL
						AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						AND fn_valida_email(tbl_hd_chamado_extra.email, false)
						AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial' AND '$aux_data_final'
						$cond
						ORDER BY $order tbl_hd_chamado_extra.nome;
					";

				$query = pg_query($con,$sql);
				if (pg_numrows($query) > 0) {

					//gera xls
					$arq      = "xls/relatorio-mailing-$login_fabrica.xls";
					$arq_html = "/tmp/assist/relatorio-mailing-gera.html";
					if(file_exists($arq_html))
						exec ("rm -f $arq_html");
					if(file_exists($arq))
						exec ("rm -f $arq");
					$fp = fopen($arq_html,"w");

					fputs($fp, '
						<html>
							<head>
								<title>'.traduz("RELATÓRIO DE MAILING").'</title>
							</head>
							<body>
								<table border="1">
									<tr>
										<th>'.traduz("Nome").'</th>
										<th>'.traduz("E-Mail").'</th>
										<th>'.traduz("Cidade").'</th>
										<th>'.traduz("Estado").'</th>
										'.$colunas_rel .'
									</tr>
					');

					for ( $i=0; $i < pg_numrows($query); $i++ ) {

						$email	= trim(pg_result ($query,$i,email));
						$nome	= trim(pg_result ($query,$i,nome));
						$cidade	= trim(pg_result ($query,$i,cidade_nome));
						$estado	= trim(pg_result ($query,$i,cidade_estado));
						fputs($fp, '
							<tr>
								<td>'.$nome.'</td>
								<td>'.$email.'</td>
								<td>'.$cidade.'</td>
								<td>'.$estado.'</td>
								'.$tipo_consumidor.'
							</tr>
						');

					}

					fputs($fp, "\t\t	</table>
							</body>
						</html>" . PHP_EOL);

					rename($arq_html, $arq);

					echo '<div class="tac"><button type="button" class="btn" onclick="window.open(\'xls/relatorio-mailing-'.$login_fabrica.'.xls\')">'.traduz("Download em EXCEL").'</button></div><br />';

				}
				else
					echo '<p style="text-align:center;">
							'.traduz("Não foram encontrados resultados para esta pesquisa.").'
						  </p>';
			}
			else
				echo '<div id="erro" class="alert alert-danger" >'.$msg_erro.'</div>';
		}
	?>
</div>

<?php include 'rodape.php'; ?>
