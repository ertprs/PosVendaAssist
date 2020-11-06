<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$popup = $_GET['popup'];
if ($popup=='sim') {


?>

    <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />	
    <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="css/tooltips.css" type="text/css" rel="stylesheet" />
    <link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
    <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

    <!--[if lt IE 10]>
     <link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
    <link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
    <![endif]-->

    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>

<?php

	$plugins = array(
        "mask",
        "datepicker",
        "dataTable",
        "autocomplete",
        "shadowbox",
        "multiselect"
	);

	include "plugin_loader.php";


	$produto 	= $_GET['produto'];
	$familia 	= $_GET['familia'];
	$posto  	= $_GET['posto'];
	$data_inicial	= $_GET['data_inicial'];
	$data_final  	= $_GET['data_final'];
	$frase  	= $_GET['frase'];
	$tipo	  	= $_GET['tipo'];


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

	$cond_1 = " 1 = 1 ";
        $cond_2 = " 1 = 1 ";
        $cond_3 = " 1 = 1 ";

	if($familia<>'NULL'){
                $cond_3 = " tbl_produto.familia = $familia ";
        }

	if($produto<>'NULL' ){
                $cond_2 = " tbl_os.produto = $produto ";
        }

	if($posto<>'NULL'){
                $cond_1 = " tbl_os.posto = $posto";
        }
	
	
	
	if ($tipo <> 'os') {
		switch($frase) {

		case 'de 0 a 5':
			$titulo = "Ordens de serviço fechadas de 0 a 5";
			$sql = "select count(*) as total,nome,posto from tbl_os join tbl_posto using(posto) join tbl_produto using(produto) where fabrica = 24 and data_fechamento between '$xdata_inicial' and '$xdata_final' and finalizada is not null and (data_fechamento-data_abertura) between 0 and 5 and $cond_1 AND $cond_2 AND $cond_3 GROUP BY nome,posto ORDER BY 1 desc" ;
		break;
		case 'de 5 a 10':
			$titulo = "Ordens de serviço fechadas de 5 a 10";
		 	$sql = "select count(*) as total,nome,posto from tbl_os join tbl_posto using(posto) join tbl_produto using(produto) where fabrica = 24 and data_fechamento between '$xdata_inicial' and '$xdata_final' AND finalizada is not null and (data_fechamento-data_abertura) between 5 and 10 and $cond_1 AND $cond_2 AND $cond_3 GROUP BY nome,posto ORDER BY 1 desc" ;
		break;
		case 'de 10 a 15':
			$titulo = "Ordens de serviço fechadas de 10 a 15";
			$sql = "select count(*) as total,nome,posto from tbl_os join tbl_posto using(posto) join tbl_produto using(produto) where fabrica = 24 and data_fechamento between '$xdata_inicial' and '$xdata_final' and finalizada is not null and (data_fechamento-data_abertura) between 10 and 15 and $cond_1 AND $cond_2 AND $cond_3 GROUP BY nome,posto ORDER BY 1 desc" ;
		break;
		case 'de 15 a 30':
			$titulo = "Ordens de serviço fechadas de 15 a 30";
			$sql = "select count(*) as total,nome,posto from tbl_os join tbl_posto using(posto) join tbl_produto using(produto) where fabrica = 24 and data_fechamento between '$xdata_inicial' and '$xdata_final' AND finalizada is not null and (data_fechamento-data_abertura) between 15 and 30 and $cond_1 AND $cond_2 AND $cond_3 GROUP BY nome,posto ORDER BY 1 desc" ;
		break;
		default: 
			$titulo = "Ordens de serviço fechadas com mais de 30";
			$coment = "Ordens de serviço fechadas de 30 a 35"; //1925644
			$sql = "select count(*) as total,nome,posto from tbl_os join tbl_posto using(posto) join tbl_produto using(produto) where fabrica = 24 and data_fechamento between '$xdata_inicial' and '$xdata_final' AND finalizada is not null and (data_fechamento-data_abertura) between 30 and 35 and $cond_1 AND $cond_2 AND $cond_3 GROUP BY nome,posto ORDER BY 1 desc" ;
		break;

		}

		$res = pg_query($con,$sql);

		if (pg_num_rows($res)>0) {


		 echo "<table class='table table-striped table-bordered table-fixed' id='resultado'>";
                	echo "
                        <thead class='titulo_tabela'>
                                <tr>
					<th colspan='2'>$titulo</th>
				</tr>
				<tr>
					<th>Posto</th>
	                                <th>Total</th>
				</tr>
                        </thead>
                        <tbody>
                ";


		for ($i=0;$i<pg_num_rows($res);$i++) {
	
			$posto_nome = pg_result($res,$i,nome);
			$posto_id   = pg_result($res,$i,posto);
			$total      = pg_result($res,$i,total);
			$total_final +=$total;

			echo "<tr><td><a href=\"$PHP_SELF?popup=sim&tipo=os&frase=$frase&data_inicial=$data_inicial&data_final=$data_final&produto=$produto&familia=$familia&posto=$posto_id\">$posto_nome</a></td><td>$total</td></tr>";

		}

		echo "<tr><td>Total</td><td>$total_final</td></tr>";
	 	echo "</body>";
                echo "</table>";


		}
      } else {

	 switch($frase) {

                case 'de 0 a 5':
                        $titulo = "Ordens de serviço fechadas de 0 a 5";
                        $sql = "select os,sua_os,to_char(data_abertura,'DD/MM/YYYY') as data_abertura,to_char(data_fechamento,'DD/MM/YYYY') as data_fechamento,nome,(data_fechamento-data_abertura) as dias from tbl_os join tbl_posto using(posto) join tbl_produto using(produto) where fabrica = 24 and data_fechamento between '$xdata_inicial' and '$xdata_final' and finalizada is not null and (data_fechamento-data_abertura) between 0 and 5 and $cond_1 AND $cond_2 AND $cond_3 ORDER BY data_abertura" ;
                break;
                case 'de 5 a 10':
                        $titulo = "Ordens de serviço fechadas de 5 a 10";
                        $sql = "select os,sua_os,to_char(data_abertura,'DD/MM/YYYY') as data_abertura,to_char(data_fechamento,'DD/MM/YYYY') as data_fechamento,nome,(data_fechamento-data_abertura) as dias from tbl_os join tbl_posto using(posto) join tbl_produto using(produto) where fabrica = 24 and data_fechamento between '$xdata_inicial' and '$xdata_final' AND finalizada is not null and (data_fechamento-data_abertura) between 5 and 10 and $cond_1 AND $cond_2 AND $cond_3 ORDER BY data_abertura" ;
                break;
                case 'de 10 a 15':
                        $titulo = "Ordens de serviço fechadas de 10 a 15";
                        $sql = "select os,sua_os,to_char(data_abertura,'DD/MM/YYYY') as data_abertura,to_char(data_fechamento,'DD/MM/YYYY') as data_fechamento,nome,(data_fechamento-data_abertura) as dias from tbl_os join tbl_posto using(posto) join tbl_produto using(produto) where fabrica = 24 and data_fechamento between '$xdata_inicial' and '$xdata_final' and finalizada is not null and (data_fechamento-data_abertura) between 10 and 15 and $cond_1 AND $cond_2 AND $cond_3 ORDER BY data_abertura" ;
                break;
                case 'de 15 a 30':
                        $titulo = "Ordens de serviço fechadas de 15 a 30";
                        $sql = "select os,sua_os,to_char(data_abertura,'DD/MM/YYYY') as data_abertura,to_char(data_fechamento,'DD/MM/YYYY') as data_fechamento,nome,(data_fechamento-data_abertura) as dias from tbl_os join tbl_posto using(posto) join tbl_produto using(produto) where fabrica = 24 and data_fechamento between '$xdata_inicial' and '$xdata_final' AND finalizada is not null and (data_fechamento-data_abertura) between 15 and 30 and $cond_1 AND $cond_2 AND $cond_3 ORDER BY data_abertura" ;
                break;
                default:
                        $titulo = "Ordens de serviço fechadas com mais de 30";
						$coment = "Ordens de serviço fechadas de 30 a 35"; //1925644
                        $sql = "select os,sua_os,to_char(data_abertura,'DD/MM/YYYY') as data_abertura,to_char(data_fechamento,'DD/MM/YYYY') as data_fechamento,nome,(data_fechamento-data_abertura) as dias from tbl_os join tbl_posto using(posto) join tbl_produto using(produto) where fabrica = 24 and data_fechamento between '$xdata_inicial' and '$xdata_final' and finalizada is not null and (data_fechamento-data_abertura) between 30 and 35  and $cond_1 AND $cond_2 AND $cond_3 ORDER BY data_abertura" ;
                break;

                }

                $res = pg_query($con,$sql);

                if (pg_num_rows($res)>0) {

			$nome = pg_result($res,0,nome);
                 echo "<table class='table table-striped table-bordered' id='resultado'>";
                        echo "
                        <thead class='titulo_tabela'>
                                <tr>
                                        <th colspan='4'>$titulo</th>
                                </tr>
				<tr>
				        <th colspan='4'>$nome</th>
                                </tr>
                                <tr>
                                        <th>OS</th>
                                        <th>Data Abertura</th>
                                        <th>Data Fechamento</th>
                                        <th>Dias</th>
                                </tr>
                        </thead>
                        <tbody>
                ";


                for ($i=0;$i<pg_num_rows($res);$i++) {

    	                $sua_os 		 = pg_result($res,$i,sua_os);
    	                $os 		 	 = pg_result($res,$i,os);
    	                $data_abertura 		 = pg_result($res,$i,data_abertura);
    	                $data_fechamento	 = pg_result($res,$i,data_fechamento);
    	                $dias 		    	 = pg_result($res,$i,dias);
			
			echo "
                                <tr>
                                        <td><a href='os_press.php?os=$os' target='_blanck'>$sua_os</td>
                                        <td>$data_abertura</td>
                                        <td>$data_fechamento</td>
                                        <td>$dias</td>
                                </tr>";

		}
		echo "<tr>
			<td>Total</td>
			<td colspan=3>$i</td>
		</tr>";
                echo "</body>";
                echo "</table>";

      		}
	}




	


	




die;
}




$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial 		= $_POST['data_inicial'];
	$data_final   		= $_POST['data_final'];
	$produto_referencia 	= $_POST['produto_referencia'];
	$produto_descricao 	= $_POST['produto_descricao'];
	$posto_nome		= $_POST['posto_nome'];
	$posto_codigo 		= $_POST['posto_codigo'];
	$familia		= $_POST['familia'];

	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";

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

	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto from tbl_produto where referencia='$produto_referencia' limit 1";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
			$cond_1 = " tbl_os.produto = $produto ";
		}
	}
	
	if(strlen($posto_codigo)>0){
		$sql = "SELECT posto from tbl_posto_fabrica where codigo_posto='$posto_codigo' and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,0);
			$cond_2 = " tbl_os.posto = $posto ";
		}
	}
	
	if(strlen($familia)>0){
		$cond_3 = " tbl_produto.familia = $familia ";
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO TEMPO OS FINALIZADAS";

include "cabecalho_new.php";


$plugins = array( 
	"mask", 
	"datepicker",
	"dataTable",
	"autocomplete",
	"shadowbox",
	"multiselect"
);

include "plugin_loader.php";


?>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>

<script>





	$(function() {

		$.datepickerLoad(["data_ini", "data_fim"]);

		$.autocompleteLoad(Array("produto", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

	});


   	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

   	function retorna_posto (retorno) {
		$("#posto_codigo").val(retorno.codigo);
		$("#posto_nome").val(retorno.nome);
	}


	function abrePosto(frase,data_inicial,data_final,produto,familia,posto){
		janela = window.open("<?=$PHP_SELF?>?popup=sim&frase="+frase+"&data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&familia=" +familia+ "&posto=" +posto, "tempoOS",'scrollbars=yes,width=750,height=450,top=315,left=0');
		janela.focus();
	}

/* POP-UP IMPRIMIR */
function abrir(URL) {
	var width = 700;
	var height = 600;
	var left = 90;
	var top = 90;

	window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
}

</script>



<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" class='form-search form-inline tc_formulario'>



<div class='titulo_tabela '>Parâmetros de Pesquisa</div>

		<br />

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" id="data_ini" name="data_inicial" class='span12' maxlength="20" value="<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" id="data_fim" name="data_final" class='span12' value="<?=$data_final?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='familia'>Família</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<select name="familia" id="familia">
								<?
								$sql = "SELECT  *
										FROM    tbl_familia
										WHERE   tbl_familia.fabrica = $login_fabrica
										ORDER BY tbl_familia.descricao;";
								$res = pg_query ($con,$sql);

								if (pg_num_rows($res) > 0) {
									echo "<option value=''>ESCOLHA</option>\n";
									for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
										$aux_familia   = trim(pg_fetch_result($res,$x,familia));
										$aux_descricao = trim(pg_fetch_result($res,$x,descricao));

										echo "<option value='$aux_familia'";
										if ($familia == $aux_familia){
											echo " SELECTED ";
											$mostraMsgLinha = "<br> da FAMÍLIA $aux_descricao";
										}
										echo ">$aux_descricao</option>\n";
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='produto_referencia'>Ref. Produto</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='produto_descricao'>Descrição Produto</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>


		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'>Cod. Posto</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" id="posto_codigo" name="posto_codigo" class='span12' maxlength="20" value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='descricao_posto'>
						Nome Posto 
					</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" id="posto_nome" name="posto_nome" class='span12' value="<? echo $posto_nome; ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8 tac'>
				<input type='submit' class='btn' name='btn_acao' value='Consultar'>
			</div>
			<div class='span2'></div>
		</div>
			
</FORM>

<br />
<?

if(strlen($btn_acao)>0){

	if(strlen($msg_erro)==0){

		echo "<table class='table table-striped table-bordered' id='resultado'>";		
		echo "
			<thead class='titulo_tabela'>
				<th>Período</th>
				<th>Total</th>
			</thead>
			<tbody>
		";
		 $array_intervalos = array('0,5','5,10','10,15','15,30','30,35');
		
		 foreach($array_intervalos as $intervalos) {
		
			$array_intervalo = explode(',',$intervalos);
	
			$between = ($array_intervalo[1] == 'x') ? "and (data_fechamento-data_abertura) > $array_intervalo[0]" : "and (data_fechamento-data_abertura) between $array_intervalo[0] and $array_intervalo[1]";  


				$sql = "select count(*) from tbl_os join tbl_produto using(produto) where fabrica = $login_fabrica and  tbl_os.data_fechamento between '$xdata_inicial' and '$xdata_final' $between AND $cond_1 AND $cond_2 AND $cond_3 and finalizada is not null";

				$res = pg_query($con,$sql);

				if (pg_num_rows($res)>0) {

					$total = pg_result($res,0,0);
					
					
					$frase = ($array_intervalo[1] == 'x') ? " a mais de $array_intervalo[0] </td><td class='tac'> $total" : "de $array_intervalo[0] a $array_intervalo[1] </td><td class='tac'> $total";

					if ($login_fabrica == 24) {	
						if($array_intervalo[0]==30) {			
							$frase = "a mais de 30 dias </td><td class='tac'> $total </td>";
						}
					}

					$frase_grafico = ($array_intervalo[1] == 'x') ? " a mais de $array_intervalo[0]" : "de $array_intervalo[0] a $array_intervalo[1]";

					if (empty($produto)) $produto = "'NULL'";
					if (empty($familia)) $familia = "'NULL'";
					if (empty($posto)) $posto = "'NULL'";
					
					

					echo "<tr><td><a href=\"javascript: abrePosto('$frase_grafico','$data_inicial','$data_final',$produto,$familia,$posto)\">Ordens de serviço fechada $frase</a></td></tr>";

				}
				
				
				$array_grafico[] = "['Ordens de serviço fechada $frase_grafico',$total]";	
				
				
				if ($login_fabrica == 24) {	
					if($array_intervalo[0]==30) {
						$array_grafico[4] = "['Ordens de serviço fechada a mais de 30 dias',$total]";
					}
				}	

		}

		echo "</body>";
		echo "</table>";
			
		//print_r($array_grafico);
		$dados_grafico = implode(',',$array_grafico);


		?>

	
		<script language="javascript">

			$(function () {


	    $('#grafico').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false
        },
        title: {
            text: 'Tempo de OS'
        },
        tooltip: {
    	    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    color: '#000000',
                    connectorColor: '#000000',
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                }
            }
        },
        series: [{
            type: 'pie',
            name: 'Tempo de OS',
            data: [
		<?=$dados_grafico?>
            ]

        }]
    });
});


		</script>


	
		<div id="grafico" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
	<?
	}
}


?>

<p>

<? include "rodape.php" ?>
