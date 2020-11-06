<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

if ($trava_cliente_admin) {
	$admin_privilegios="call_center";
	$layout_menu = "callcenter";
}
else {
	$admin_privilegios="gerencia";
	$layout_menu = "gerencia";
}
include 'autentica_admin.php';

$excel = $_GET["excel"];

$title = traduz("RELATÓRIO DE TEMPO DE PERMANÊNCIA EM CONSERTO");

if ($login_fabrica == 117) {
    include_once('carrega_macro_familia.php');
}

function retorna_intervalo($posto = ''){
    global $con, $data_referencia, $mes;

    if (!empty($posto)) {
        $where_posto = " AND posto = $posto";
    }

    $sqld = "SELECT
                count(os) AS total,
                SUM(($data_referencia - data_abertura)) AS data_diferenca
            FROM temp_rtc_$mes
            WHERE $data_referencia::date-data_abertura <= 5
                $where_posto;";

    $resd = pg_query($con, $sqld);
    $info_os[5] = array(
        'total' => pg_fetch_result($resd, 0, 'total'),
        'data_diferenca' => pg_fetch_result($resd, 0, 'data_diferenca')
    );

    for ($x = 6; $x < 91; $x++) {
        $x_aux = ($x >= 29) ? 30 : 4;
        $soma  = ($x+$x_aux > 90) ? 90 : $x+$x_aux;

        $sqld = "SELECT
                    count(os) AS total,
                    SUM(($data_referencia - data_abertura)) AS data_diferenca
                FROM temp_rtc_$mes
                WHERE $data_referencia::date-data_abertura BETWEEN $x AND $soma $where_posto";

        $resd = pg_query($con, $sqld);
        $info_os[$x] = array(
            'total' => pg_fetch_result($resd, 0, 'total'),
            'data_diferenca' => pg_fetch_result($resd, 0, 'data_diferenca')
        );
        $x = $soma;
    }
    $sqld = "SELECT
                count(os) AS total,
                SUM(($data_referencia - data_abertura)) AS data_diferenca
            FROM temp_rtc_$mes
            WHERE $data_referencia::date-data_abertura > 90
                $where_posto;";
    $resd = pg_query($con, $sqld);
    $info_os[90] = array(
        'total' => pg_fetch_result($resd, 0, 'total'),
        'data_diferenca' => pg_fetch_result($resd, 0, 'data_diferenca')
    );

    return $info_os;
}

if ($excel) {
	ob_start();
} else {
	include "cabecalho_new.php";
	$plugins = array( 	"multiselect",
						"lupa",
						"autocomplete",
						"datepicker",
						"mask",
						"dataTable",
						"shadowbox"
				);
	include "plugin_loader.php";
}

$meses = array(1 => traduz("Janeiro"), traduz("Fevereiro"), traduz("Março"), traduz("Abril"), traduz("Maio"), traduz("Junho"), traduz("Julho"), traduz("Agosto"), traduz("Setembro"), traduz("Outubro"), traduz("Novembro"), traduz("Dezembro"));

$nomemes = array(1=> traduz("JANEIRO"), traduz("FEVEREIRO"), traduz("MARÇO"), traduz("ABRIL"), traduz("MAIO"), traduz("JUNHO"), traduz("JULHO"), traduz("AGOSTO"), traduz("SETEMBRO"), traduz("OUTUBRO"), traduz("NOVEMBRO"), traduz("DEZEMBRO"));

?>
<!-- HD 216470: Acrescentando opções de busca com autocomplete -->
<?php
if ($excel) {
	ob_start();
}
?>

<script type="text/javascript">
$(function(){
            var login_fabrica = <?=$login_fabrica?>;
            $.datepickerLoad(Array("data_final", "data_inicial"));
            $("span[rel=lupa]").click(function () {
                        $.lupa($(this));
            });
            Shadowbox.init();
            if(login_fabrica == 86){
                        $("#linha").multiselect({
                                    selectedText: "selecionados # de #"
                        });
            }

            if ($.inArray(login_fabrica, ["52", "30", "85"]) > -1) {

		//Busca pelo NOME DO CLIENTE ADMIN - FRICON
		$("#cliente_nome_admin").autocomplete("relatorio_tempo_conserto_mes_ajax.php?tipo_busca=cliente_admin&busca=nome", {
			minChars: 3,
			delay: 150,
			width: 350,
			max: 30,
			matchContains: true,
			formatItem: formatCliente,
			formatResult: function(row) {
			return row[3];
			}
		});

		$("#cliente_nome_admin").result(function(event, data, formatted) {
			$("#cliente_admin").val(data[0]) ;
			$("#cliente_nome_admin").val(data[3]) ;
		});
    }

    function formatCidade(row) {
        return row[0] + " - " + row[1];
    }

    if ($("select[name=estado]").val() != ""){
        $("#cidade_consumidor").show();
        $("input[name=cidade]").removeAttr("readonly");
    }else{
        $("#cidade_consumidor").hide();
        $("input[name=cidade]").attr("readonly");
    }

    $("select[name=estado]").change(function(){
        var regiao = $("select[name=estado]").val();
        if (regiao != ""){
            $("#cidade_consumidor").show();
            $("input[name=cidade]").val("");
            $("input[name=cidade]").removeAttr("readonly");
        }else{
            $("#cidade_consumidor").hide();
            $("input[name=cidade]").val("");
            $("input[name=cidade]").attr("readonly");
        }
    });

    function regiao(){
        return $("select[name=estado]").val();
    }

});


    function retorna_posto(posto){
        gravaDados('codigo_posto',posto.codigo);
        gravaDados('posto_nome',posto.nome);
    }

    function retorna_produto (retorno) {
        $("#produto").val(retorno.produto);
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }

    function gravaDados(name, valor){
        try {
                $("input[name="+name+"]").val(valor);
        } catch(err){
                return false;
        }
    }

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatCliente(row) {
		return row[2] + " - " + row[3] + " - Cidade: " + row[4];
	}

	function maiuscula(valor)
	{
		var valor = $("input[name=cidade]").val();
		var valor = valor.toUpperCase();
		$("input[name=cidade]").val(valor);
	}

	var map = {"â":"a","Â":"A","à":"a","À":"A","á":"a","Á":"A","ã":"a","Ã":"A","ê":"e","Ê":"E","è":"e","È":"E","é":"e","É":"E","î":"i","Î":"I","ì":"i","Ì":"I","í":"i","Í":"I","õ":"o","Õ":"O","ô":"o","Ô":"O","ò":"o","Ò":"O","ó":"o","Ó":"O","ü":"u","Ü":"U","û":"u","Û":"U","ú":"u","Ú":"U","ù":"u","Ù":"U","ç":"c","Ç":"C","ñ":"n"};

	function removerAcentos(string) { 
		return string.replace(/[\W\[\] ]/g,function(a) {
			return map[a]||a}) 
	};

	/** select de provincias/estados */
	$(function() {

	    $("#pais").change(function(){
	        
	        var pais = this.value;
	        
	        var array_reg = ["AR","CO","PE","BR"];
	        	        
	        if (array_reg.indexOf(pais) > 0) {

	            $("#estado").show();

	        } else {

	            $("#estado").hide();
	        }
	    });

		$("#pais").change(function() {
		
			var pais = this.value;

			$("#estado option").remove();
			
			$("#estado optgroup").remove();

			$("#estado").append("<option value=''>TODOS OS ESTADOS</option>");
		
		<?php if (in_array($login_fabrica,[152,180,181,182])) { ?>

			if (pais == "CO") {

				var colombia = ["Distrito Capital","Amazonas","Antioquia","Arauca",
					"Atlántico", "Bolívar","Boyacá","Caldas","Caquetá","Casanare",
					"Cauca","Cesar","Chocó", "Córdoba","Cundinamarca","Guainía",
					"Guaviare","Huila","La Guajira","Magdalena","Meta","Nariño",
					"Norte de Santander","Putumayo","Quindío","Risaralda",
					"San Andrés e Providencia","Santander","Sucre","Tolima",
					"Valle del Cauca","Vaupés","Vichada"];

				$("#estado").append('<optgroup label="Provincias">');
	      		
	      		$.each(colombia, function( index, value ) {

					var semAcento = removerAcentos(value);

					var o = new Option("option text", semAcento);

					$(o).html(value, semAcento);
					$("#estado").append(o);
				});	

				$("#estado").show();
				
			} else if (pais == "PE") {

				var peru = ["Amazonas","Ancash","Apurímac","Arequipa","Ayacucho",
		  				"Cajamarca","Callao","Cusco","Huancavelica","Huánuco",
	      				"Ica","Junin","La Libertad","Lambayeque","Lima","Loreto",
	      				"Madre de Dios","Moquegua","Pasco","Piura","Puno","San Martín",
	      				"Tacna","Tumbes","Ucayali"];

				$("#estado").append('<optgroup label="Provincias">');
	      		
	      		$.each(peru, function( index, value ) {

					var semAcento = removerAcentos(value);

					var option = new Option("option text", semAcento);

					$(option).html(value, semAcento);
					$("#estado").append(option);
				});	

				$("#estado").show();

			} else if (pais == "AR") {

				var argentina = ['Buenos Aires','Catamarca','Chaco','Chubut','Córdoba',					 'Corrientes','Entre Ríos','Formosa','Jujuy','La Pampa',    			 'La Rioja','Mendoza','Misiones','Neuquén','Río Negro',     			 'Salta','San Juan','San Luis','Santa Cruz','Santa Fe',      		'Santiago del Estero','Terra do Fogo','Tucumán'];
				
				$("#estado").append('<optgroup label="Provincias">');
	      		
	      		$.each(argentina, function( index, value ) {
					
					var semAcento = removerAcentos(value);
					
					var option = new Option("option text", semAcento);
					
					$(option).html(value, semAcento);
					$("#estado").append(option);
				});

				$("#estado").show();	

			} 
		<?php } ?>
	      	
	      	if (pais == "BR") { 

	      		brasil = [ 
					"AC - Acre",
					"AL - Alagoas",
					"AM - Amazonas",
					"AP - Amapá"  ,
					"BA - Bahia",
					"CE - Ceará"  ,
					"DF - Distrito Federal",
					"ES - Espírito Santo" ,
					"GO - Goiás",
					"MA - Maranhão", 
					"MG - Minas Gerais",
					"MS - Mato Grosso do Sul",
					"MT - Mato Grosso",
					"PA - Pará",
					"PB - Paraíba",
					"PE - Pernambuco",
					"PI - Piauí",
					"PR - Paraná",
					"RJ - Rio de Janeiro",
					"RN - Rio Grande do Norte",
					"RO - Rondônia", 
					"RR - Roraima",
					"RS - Rio Grande do Sul", 
					"SC - Santa Catarina",
					"SE - Sergipe", 
					"SP - São Paulo",
					"TO - Tocantins" ];

				var array_regioes = {
					"centro-oeste" : "Região Centro-Oeste (GO,MT,MS,DF)",
					"nordeste"     : "Região Nordeste (MA,PI,CE,RN,PB,PE,AL,SE,BA)",
					"norte"  	   : "Região Norte (AC,AM,RR,RO,PA,AP,TO)",
					"norte"        : "Região Norte (AC,AM,RR,RO,PA,AP,TO)",
					"sudeste"      : "Região Sudeste (MG,ES,RJ,SP)",
					"sul"          : "Região Sul (PR,SC,RS)"
				};

				$("#estado").append('<optgroup label="Regioes">');

				$.each(array_regioes, function( index, regioes ) {
			
					var opRegiao = new Option("option text", regioes);
					
					$(opRegiao).html(regioes, regioes);
					$("#estado").append(opRegiao);
				});	

				$("#estado").append('</optgroup>');

				$("#estado").append('<optgroup label="Estados">');
	      		
	      		$.each(brasil, function( index, value ) {

					var sigla = value.split(" - ");

					var optEstado = new Option("option text", sigla[0]);
					$(optEstado).html(value, value);
					$("#estado").append(optEstado);
				});

				<? if ($login_fabrica == 43) {	?>

					var sp_capital = new Option("option text", "SP-capital");
					
					$(sp_capital).html("SÃO PAULO - CAPITAL", "SP-capital");
					$("#estado").append(sp_capital);

					var sp_interior = new Option("option text", "SP-interior");
					
					$(sp_interior).html("SÃO PAULO - INTERIOR", "SP-interior");
					$("#estado").append(sp_interior);

				<?php } ?>	
			}

			$("#estado").append('</optgroup>');

		});
	});

</script>

<?
//HD 216470: Adicionando algumas validações básicas
if ($_POST['btn_acao']) {
	$mes = $_GET["mes"];
	if (strlen($mes) == 0) $mes = $_POST["mes"];

	$ano = $_GET["ano"];
	if (strlen($ano) == 0) $ano = $_POST["ano"];

	$pais = $_GET["pais"];
	if (strlen($pais) == 0) $pais = $_POST["pais"];

	$estado = $_GET["estado"];
	if (strlen($estado) == 0) $estado = $_POST["estado"];

    $familia = $_GET["familia"];
    if (strlen($familia) == 0) $familia = $_POST["familia"];

	$marca = $_GET["marca"];
	if (strlen($marca) == 0) $marca= $_POST["marca"];

	if(isset($_GET["linha"])){
		$linha = $_GET["linha"];
	}
	if(isset($_POST["linha"])){
		if($login_fabrica == 86){
			if(count($linha)>0){
				$linha = $_POST["linha"];
			}
		}else{
			if (strlen($linha) == 0) {
				$linha = $_POST["linha"];
			}
		}
	}


	$produto_referencia = $_GET["produto_referencia"];
	if (strlen($produto_referencia) == 0) $produto_referencia = $_POST["produto_referencia"];

	$produto_descricao = $_GET["produto_descricao"];
	if (strlen($produto_descricao) == 0) $produto_descricao = $_POST["produto_descricao"];

	$codigo_posto = $_GET["codigo_posto"];
	if (strlen($codigo_posto) == 0) $codigo_posto = $_POST["codigo_posto"];

	$posto_nome = $_GET["posto_nome"];
	if (strlen($posto_nome) == 0) $posto_nome = $_POST["posto_nome"];

	$data_referencia = $_GET["data_referencia"];
	if (strlen($data_referencia) == 0) $data_referencia = $_POST["data_referencia"];

	$cliente_admin = $_GET["cliente_admin"];
	if (strlen($cliente_admin) == 0) $cliente_admin = $_POST["cliente_admin"];

	$cliente_nome_admin = $_GET["cliente_nome_admin"];
	if (strlen($cliente_nome_admin) == 0) $cliente_nome_admin = $_POST["cliente_nome_admin"];

	$cidade = $_GET["cidade"];
	if (strlen($cidade) == 0) $cidade = $_POST["cidade"];
	$cidade = strtoupper($cidade);

	if ($trava_cliente_admin) {
		$cliente_admin = $trava_cliente_admin;
		$sql = "SELECT nome FROM tbl_cliente_admin WHERE cliente_admin=$cliente_admin";
		$res = pg_query($con, $sql);
		$cliente_nome_admin = pg_result($res, 0, nome);
	}

	if (strlen($familia)) {
		$familia = intval($familia);
		$sql = "SELECT familia FROM tbl_familia WHERE fabrica=$login_fabrica";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			$msg_erro["msg"][] = traduz("Família escolhida não existe");
			$msg_erro["campos"][] = traduz("familia");
		}
	}
	if ($login_fabrica ==86){
		if (count($linha)) {
			$whereLinha = " AND tbl_linha.linha IN (";
			for($i = 0; $i < count($linha); $i++){
				if($i == count($linha)-1 ){
					$whereLinha .= $linha[$i].")";
				}else {
					$whereLinha .= $linha[$i].", ";
				}
			}
			$sql = "SELECT linha FROM tbl_linha WHERE fabrica=$login_fabrica $whereLinha";

			$res = pg_query($con, $sql);
			if (pg_num_rows($res) == 0) {
				$msg_erro["msg"][] = traduz("Linha escolhida não existe");
				$msg_erro["campos"][] = traduz("linha");
			}
		}
	}else{
		if (strlen($linha)) {
			$linha = intval($linha);
			$sql = "SELECT linha FROM tbl_linha WHERE fabrica=$login_fabrica";
			$res = pg_query($con, $sql);
			if (pg_num_rows($res) == 0) {
				$msg_erro["msg"][] = traduz("Linha escolhida não existe");
				$msg_erro["campos"][] = traduz("linha");
			}
		}
	}
	if (strlen($produto_referencia)) {
		$sql = "SELECT produto FROM tbl_produto JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha WHERE tbl_linha.fabrica=$login_fabrica AND tbl_produto.referencia ilike '$produto_referencia'";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			$msg_erro["msg"][] = traduz("Produto") . " " .  $produto_referencia . " " . traduz("inexistente");
			$msg_erro["campos"][] = traduz("produto");
		}
	}

	if (strlen($codigo_posto)) {
		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica=$login_fabrica AND codigo_posto='$codigo_posto'";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			$msg_erro["msg"][] = traduz("Posto") . " " .  $codigo_posto . " " . traduz("inexistente");
			$msg_erro["campos"][] = traduz("posto");
		}
		else {
			$posto_id = pg_result($res, 0, 0);
		}
	}

	if ($data_referencia == "") {
		if ($login_fabrica == 52) {
			$data_referencia = "data_conserto";
		}
		else {
			$data_referencia = "data_fechamento";
		}
	}

	if (strlen($cliente_admin)) {
		$cliente_admin = intval($cliente_admin);
		$sql = "SELECT cliente_admin FROM tbl_cliente_admin WHERE fabrica=$login_fabrica AND cliente_admin=$cliente_admin";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			$msg_erro["msg"][] = traduz("Cliente ADM selecionado inexistente");
		}
	}

	if (strlen($cliente_admin) == 0 && strlen($cliente_nome_admin) > 0){
		$msg_erro["msg"][] = traduz("Para efetuar uma busca por Cliente ADM, digite o nome desejado no campo e SELECIONE UMA OPÇÃO DA LISTA");
		$cliente_nome_admin = "";
	}
	elseif (strlen($cliente_admin) > 0 && strlen($cliente_nome_admin) == 0) {
		$cliente_admin = "";
	}

	// Verifica se é um acesso da fricon
	if($login_fabrica == 56) {
		// Recupera a data inicial
		if (strlen($_GET['data_inicial']) > 0)
			$data_inicial = $_GET['data_inicial'];
		else
			$data_inicial = $_POST['data_inicial'];

		// Recupera a data final
		if (strlen($_GET['data_final']) > 0)
			$data_final   = $_GET['data_final'];
		else
			$data_final   = $_POST['data_final'];

		$aux_data_inicial = $data_inicial;
		$aux_data_final   = $data_final;

		// Verifica se a data está vazia
		if(empty($data_inicial) and count($msg_erro)==0){
	        $msg_erro["msg"][] = traduz("Data inicial inválida");
	        $msg_erro["campos"][] = "data_inicial";
	    }

	    if(empty($data_final) and count($msg_erro)==0){
	        $msg_erro["msg"][] = traduz("Data final inválida");
	        $msg_erro["campos"][] = "data_final";
	    }

	    // Valida a data inicial
	    if(count($msg_erro)==0){
	        list($di, $mi, $yi) = explode("/", $data_inicial);
	        if(!checkdate($mi,$di,$yi))
	            $msg_erro["msg"][] = traduz("Data Inválida");
	        	$msg_erro["campos"][] = "data_inicial";
	    }

	    // Valida a data final
	    if(count($msg_erro)==0){
	        list($df, $mf, $yf) = explode("/", $data_final);
	        if(!checkdate($mf,$df,$yf))
	            $msg_erro["msg"][] = traduz("Data Inválida");
	        	$msg_erro["campos"][] = "data_final";
	    }

	    // Se não der erro, formata a data
	    if(count($msg_erro)==0){
			$data_inicial     = "$yi-$mi-$di";
			$data_final       = "$yf-$mf-$df";
	    }

	    // Verifica se as datas são válidas
	    if(count($msg_erro)==0){
	        if(strtotime($data_final) < strtotime($data_inicial)
	        or strtotime($data_final) > strtotime('today')){
	            $msg_erro["msg"][] = traduz("Data Inválida");
	        	$msg_erro["campos"][] = "data_inicial";
	        	$msg_erro["campos"][] = "data_final";
	        }
	    }

	    // Verifica se o intervalo é menor que 6 meses
	    if (strtotime($data_inicial) < strtotime($data_final . ' -6 months') and count($msg_erro)==0) {
			$msg_erro["msg"][] = traduz("O intervalo entre as datas não pode ser maior que 6 meses.");
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["campos"][] = "data_final";
		}

	} else {

		// Efetua validação de mês e ano apenas se não foi informado a data inicial e a data final
		if(!strlen($data_inicial) and !strlen($data_final)) {
			if (strlen($mes)) {
				$mes = intval($mes);
				if ($mes < 1 || $mes > 12) {
					$msg_erro["msg"][] = traduz("O mês deve ser um número entre 1 e 12");
					$msg_erro["campos"][] = "mes";
					$mes = "";
				}
			}

			if (strlen($ano) != 4) {
				$msg_erro["msg"][] = traduz("O ano deve conter 4 dígitos");
				$ano = "";
			}

			if ($mes == "" || $ano == "")  {
				$msg_erro["msg"][] = traduz("Selecione o mês e o ano para a pesquisa");
				$msg_erro["campos"][] = traduz("mes");
				$msg_erro["campos"][] = traduz("ano");
			}
		}

		$sql          = "SELECT fn_dias_mes('$ano-$mes-01',0)";
		$res3         = pg_query($con,$sql);
		$data_inicial = pg_fetch_result($res3,0,0);

		$sql          = "SELECT fn_dias_mes('$ano-$mes-01',1)";
		$res3         = pg_query($con,$sql);
		$data_final   = pg_fetch_result($res3,0,0);
	}
}
//HD 216470: Acrescentada a validação de dados ($msg_erro)

if (((strlen($mes) > 0 AND strlen($ano) > 0) or $login_fabrica == 52) && count($msg_erro) == 0){
	$add_11 = "";
	if(strlen($_POST["pais"]) > 0 or strlen($_GET["pais"]) > 0){
		$add_1 = "AND tbl_os.posto IN (SELECT posto FROM tbl_posto JOIN tbl_posto_fabrica USING(posto) WHERE tbl_posto.pais = '$pais' and fabrica=$login_fabrica ";
		$add_11 = " )";
	}

	if(strlen($_GET["estado"])>0 or strlen($_POST["estado"]) > 0) {
		$add_11 = "";
		if(strlen($_POST["pais"]) > 0 or strlen($_GET["pais"]) > 0){
			$add_2 =" AND ";
		}else{
			$add_2 = "AND tbl_os.posto IN (SELECT posto FROM tbl_posto JOIN tbl_posto_fabrica USING(posto) WHERE ";
		}
		if($estado == "centro-oeste") $add_2 .= " tbl_posto.estado in ('GO','MT','MS','DF') ";
		if($estado == "nordeste")     $add_2 .= " tbl_posto.estado in ('MA','PI','CE','RN','PB','PE','AL','SE','BA') ";
		if($estado == "norte")        $add_2 .= " tbl_posto.estado in ('AC','AM','RR','RO','PA','AP','TO') ";
		if($estado == "sudeste")      $add_2 .= " tbl_posto.estado in ('MG','ES','RJ','SP') ";
		if($estado == "sul")          $add_2 .= " tbl_posto.estado in ('PR','SC','RS') ";
		if(strlen($estado) == 2)      $add_2 .= " tbl_posto.estado = '$estado' ";
		if ($estado == "SP-capital") {
			$add_2 .= " tbl_posto.estado = 'SP'
                                 AND tbl_posto.cidade ~* 's.o paulo|s.o bernardo do campo|S.o Caetano do Sul|Guarulhos|Santo Andr.'";
		}
		if ($estado == "SP-interior") {
			$add_2 .= " tbl_posto.estado = 'SP'
                                 AND tbl_posto.cidade !~* 's.o paulo|s.o bernardo do campo|S.o Caetano do Sul|Guarulhos|Santo Andr.'";
		}

		if(strlen($_POST["pais"]) > 0 or strlen($_GET["pais"]) > 0){
			$add_2 .=" )";
		}else{
			$add_2 .= "  and fabrica = $login_fabrica)";
		}
	}


	if (!empty($cidade) and $login_fabrica == 52)
	{
		$add_cidade = " AND TO_ASCII(tbl_os.consumidor_cidade,'LATIN1') = TO_ASCII('".mb_strtoupper($cidade)."','LATIN1')";
	}

	if (!empty($marca_logo) and $login_fabrica == 52)
	{
		$add_marca_logo = " AND tbl_os.marca = $marca_logo ";
	}

	if(strlen($familia) > 0){
		$sql = "SELECT produto
				INTO TEMP temp_rtc_familia
				FROM tbl_produto PR
				JOIN tbl_familia FA USING(familia)
				WHERE FA.fabrica    = $login_fabrica
				AND   FA.familia    = $familia;
				CREATE INDEX temp_rtc_familia_produto ON temp_rtc_familia(produto);";
		$res = pg_query($con,$sql);

                        if (in_array($login_fabrica, array(138))) {
                                    $join_1  =" JOIN temp_rtc_familia FF ON FF.produto = tbl_os_produto.produto AND tbl_os.fabrica = $login_fabrica";
                        } else {
                                    $join_1  =" JOIN temp_rtc_familia FF ON FF.produto = tbl_os.produto AND tbl_os.fabrica = $login_fabrica";
                        }
	}

	//HD 216470: Acrescentada busca por Linha
	if($login_fabrica == 86){
		if (count($linha)) {
			$whereLinha = " AND tbl_linha.linha IN (";
			for($i = 0; $i < count($linha); $i++){
				if($i == count($linha)-1 ){
					$whereLinha .= $linha[$i].")";
				}else {
					$whereLinha .= $linha[$i].", ";
				}
			}
			$sql = "SELECT linha FROM tbl_linha WHERE fabrica=$login_fabrica $whereLinha";

			$res = pg_query($con, $sql);
			if (pg_num_rows($res) == 0) {
				$msg_erro["msg"][] = traduz("Linha escolhida não existe");

			}
		}
		if (count($linha)) {
			$whereLinha = " AND tbl_linha.linha IN (";
			for($i = 0; $i < count($linha); $i++){
				if($i == count($linha)-1 ){
					$whereLinha .= $linha[$i].")";
				}else {
					$whereLinha .= $linha[$i].", ";
				}
			}
			$sql = "
			SELECT produto
			INTO TEMP temp_rtc_linha
			FROM tbl_produto
			JOIN tbl_linha USING(linha)
			WHERE tbl_linha.fabrica = $login_fabrica
			$whereLinha;
			CREATE INDEX temp_rtc_linha_produto ON temp_rtc_linha(produto);
			";

			$res = pg_query($con,$sql);

                                    $join_linha = " JOIN temp_rtc_linha LI ON LI.produto = tbl_os.produto AND tbl_os.fabrica = $login_fabrica";

		}
	} else {
                        if ($linha) {
                                    $sql = "
                                    SELECT
                                    produto
                                    INTO TEMP temp_rtc_linha

                                    FROM
                                    tbl_produto
                                    JOIN tbl_linha USING(linha)

                                    WHERE
                                    tbl_linha.fabrica = $login_fabrica
                                    AND tbl_linha.linha = $linha;

                                    CREATE INDEX temp_rtc_linha_produto ON temp_rtc_linha(produto);
                                    ";
                                    //echo $sql;
                                    $res = pg_query($con,$sql);

                                    if (in_array($login_fabrica, array(138))) {
                                                $join_linha = " JOIN temp_rtc_linha LI ON LI.produto = tbl_os_produto.produto AND tbl_os.fabrica = $login_fabrica";
                                    } else {
                                                $join_linha = " JOIN temp_rtc_linha LI ON LI.produto = tbl_os.produto AND tbl_os.fabrica = $login_fabrica";
                                    }
                        }
	}

            if ($marca) {
                        $sql = "
                        SELECT
                        produto
                        INTO TEMP temp_rtc_marca

                        FROM
                        tbl_produto

                        WHERE
                        tbl_produto.fabrica_i = $login_fabrica
                        AND	tbl_produto.marca = $marca;

                        CREATE INDEX temp_rtc_marca_produto ON temp_rtc_marca(produto);
                        ";
                        $res = pg_query($con,$sql);

                        if (in_array($login_fabrica, array(138))) {
                                    $join_marca = " JOIN temp_rtc_marca LI ON LI.produto = tbl_os_produto.produto AND tbl_os.fabrica = $login_fabrica";
                        } else {
                                    $join_marca = " JOIN temp_rtc_marca LI ON LI.produto = tbl_os.produto AND tbl_os.fabrica = $login_fabrica";
                        }
            }

	if(strlen($produto_referencia) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				JOIN tbl_linha USING(linha)
				WHERE fabrica    = $login_fabrica
				AND   referencia = '$produto_referencia' ;";
		$res = pg_query($con,$sql);
		$produto = pg_fetch_result($res,0,0);
                        if (in_array($login_fabrica, array(138))) {
                                    $add_3 = "AND tbl_os_produto.produto = $produto";
                        } else {
                                    $add_3 = "AND tbl_os.produto = $produto";
                        }
	}

	//HD 216470: Acrescentada busca por Cliente ADM
	if ($cliente_admin) {
		$cont_cliente_admin = "AND tbl_os.cliente_admin = $cliente_admin";
	}

	//HD 216470: Acrescentada busca por Linha e Cliente ADM
	if ((strlen($data_inicial) > 0) and (strlen($data_final) > 0)) {
		if ($login_fabrica == 96) {
			$sql = "SELECT os,data_abertura,$data_referencia::date,posto
				INTO TEMP temp_rtc_$mes
				FROM    tbl_os
				$join_1
				$join_linha
				WHERE   tbl_os.fabrica = $login_fabrica
				AND     tbl_os.data_fechamento BETWEEN '$data_inicial' AND '$data_final'
				AND     tbl_os.excluida IS NOT TRUE
				$cont_cliente_admin
				$add_1 $add_11 $add_2 $add_3 ; ";
		} else if (in_array($login_fabrica, array(138))) {
            $sql = "SELECT tbl_os.os, tbl_os.data_abertura,$data_referencia::date,posto
                        INTO TEMP temp_rtc_$mes
                        FROM    tbl_os_produto
                        JOIN  tbl_os USING(os)
                        $join_1
                        $join_linha
                        $join_marca
                        WHERE   tbl_os.fabrica = $login_fabrica
                        AND     tbl_os.finalizada   BETWEEN '$data_inicial' AND '$data_final'
                        AND     tbl_os.excluida IS NOT TRUE
                        $cont_cliente_admin
                        $add_1 $add_11 $add_2 $add_3 $add_marca_logo $add_cidade; ";
        } else {

            if (in_array($login_fabrica, array(152, 180, 181, 182))) {
            	$left_join = " LEFT JOIN tbl_tipo_atendimento on tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento ";
            	$cond_entrega_tecnica = " AND 	tbl_tipo_atendimento.entrega_tecnica is not true ";
            }

            if($login_fabrica == 163){
            	$join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
           		$cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
            }

            $cond_169_170 = '';
            if (in_array($login_fabrica, array(169,170))) {
                if (strlen($consumidor_revenda_pesquisa)) {
                    $cond_169_170 .= " AND tbl_os.consumidor_revenda='$consumidor_revenda_pesquisa'";
                }
                if (strlen($tipo_atendimento)) {
                    $cond_169_170 .= " AND tbl_os.tipo_atendimento = $tipo_atendimento";
                }
            }

			$sql = "SELECT os,data_abertura,$data_referencia::date,posto
				INTO TEMP temp_rtc_$mes
				FROM    tbl_os
				$left_join
				$join_1
				$join_linha
				$join_163
				$join_marca
				WHERE   tbl_os.fabrica = $login_fabrica
				AND     tbl_os.finalizada   BETWEEN '$data_inicial' AND '$data_final'
				AND     tbl_os.excluida IS NOT TRUE
				$cond_entrega_tecnica
				$cond_163
                $cond_169_170
				$cont_cliente_admin
				$add_1 $add_11 $add_2 $add_3 $add_marca_logo $add_cidade; ";
		}

		if ($login_fabrica == 30) {
			$sql.= " ALTER TABLE temp_rtc_$mes ADD dias float; ";
			$sql.= " update temp_rtc_$mes set dias = case when $data_referencia = data_abertura then 0 else (select count(1) from fn_calendario(data_abertura,$data_referencia) where nome_dia not in ('Domingo') and data <> data_abertura) end; ";
		}
                        //echo $sql;
		$res2 = pg_query($con,$sql);
	}
}
if ($excel) {
} else {
            if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<form name='frm_percentual' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '><?php echo traduz("Parâmetros de Pesquisa"); ?></div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<?if($login_fabrica == 56 ){ ?>
			<div class='span4'>
				<div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?php echo traduz("Data Inicial"); ?></label>
					<div class='controls controls-row'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial_aux?>">
					</div>
				</div>
			</div>

			<div class='span2'>
			<div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'><?php echo traduz("Data Final"); ?></label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final_aux?>" >
				</div>
			</div>
		</div>
		<? } else{?>
			<div class='span4'>
				<div class='control-group <?=(in_array("mes", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='mes'><?php echo traduz("Mês"); ?></label>
					<div class='controls controls-row'>
						<h5 class='asteristico'>*</h5>
						<select name="mes" class='span7'>
							<option value=''></option>
							<?
							for ($i = 1 ; $i <= count($meses) ; $i++) {
								echo "<option value='$i'";
								if ($mes == $i) echo " selected";
								echo ">" . $meses[$i] . "</option>";
							}
							?>
						</select>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("ano", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='mes'><?php echo traduz("Ano"); ?></label>
					<div class='controls controls-row'>
						<h5 class='asteristico'>*</h5>
						<select name="ano" class="span7" >
							<option value=''></option>
							<?
							for ($i = date('Y'); $i >= 2003; $i--) {
								echo "<option value='$i'";
								if ($ano == $i) echo " selected";
								echo ">$i</option>";
							}
							?>
						</select>
					</div>
				</div>
			</div>
		<? } ?>
		<div class='span2'></div>
	</div>
	<?if ($login_fabrica != 30 || !$trava_cliente_admin) { ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
			<? if ($login_fabrica == 117) {
			?>
				<div class='control-group <?=(in_array("macro_linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='macro_linha'><?php echo traduz("Linha"); ?></label>
					<div class='controls controls-row'>
						<?
							$sql = "SELECT 
                                        DISTINCT tbl_macro_linha.macro_linha, 
                                        tbl_macro_linha.descricao
                                    FROM tbl_macro_linha
                                        JOIN tbl_macro_linha_fabrica ON tbl_macro_linha.macro_linha = tbl_macro_linha_fabrica.macro_linha
                                    WHERE  tbl_macro_linha_fabrica.fabrica = {$login_fabrica}
                                        AND     tbl_macro_linha.ativo = TRUE
                                    ORDER BY tbl_macro_linha.descricao;";
                            $res = pg_query ($con,$sql);

							if (pg_numrows($res) > 0) {
								echo "<select class='frm' style='width:200px;' name='macro_linha' id='macro_linha'>\n";
								echo "<option value=''>" . traduz("ESCOLHA") . "</option>\n";

								for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
									$aux_linha = trim(pg_fetch_result($res,$x,macro_linha));
									$aux_descricao  = trim(pg_fetch_result($res,$x,descricao));

									echo "<option value='$aux_linha'"; if ($macro_linha == $aux_linha) echo " SELECTED "; echo ">$aux_descricao</option>\n";
								}
								echo "</select>\n";
							}
						?>					
					</div>
				</div>			
			<?
			}else{
			?>
				<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='familia'><?php echo traduz("Família"); ?></label>
					<div class='controls controls-row'>
						<?
							$sqlf = "SELECT  *
									FROM    tbl_familia
									WHERE   tbl_familia.fabrica = $login_fabrica
									ORDER BY tbl_familia.descricao;";
							$resf = pg_query($con,$sqlf);

							if (pg_numrows($resf) > 0) {
								echo "<select class='frm' style='width:200px;' name='familia'>\n";
								echo "<option value=''>ESCOLHA</option>\n";

								for ($x = 0 ; $x < pg_num_rows($resf) ; $x++){
									$aux_familia = trim(pg_fetch_result($resf,$x,familia));
									$aux_descricao  = trim(pg_fetch_result($resf,$x,descricao));

									echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
								}
								echo "</select>\n";
							}
						?>
					</div>
				</div>
			<? } ?>
			</div>

			<div class='span4'>
				<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'><?=($login_fabrica == 117)? traduz("Macro - Família"): traduz("Linha")?></label>
					<div class='controls controls-row'>
						<?
							/*$join_elgin = ($login_fabrica == 117)?"  JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha ":"";*/
							if (!in_array($login_fabrica, array(117))) {
							$sql_linha = "SELECT DISTINCT
												tbl_linha.linha,
												tbl_linha.nome
										  FROM tbl_linha
										  WHERE tbl_linha.fabrica = $login_fabrica
										  ORDER BY tbl_linha.nome ";
							$res_linha = pg_query($con, $sql_linha); 
							}
							if($login_fabrica != 86){ ?>
								<select name='linha' id='linha'>

								<? if (pg_numrows($res_linha) > 0) { ?>
									<option value=''><?php echo traduz("ESCOLHA"); ?></option> <?

									for ($x = 0 ; $x < pg_num_rows($res_linha) ; $x++){
										$aux_linha = trim(pg_fetch_result($res_linha, $x, linha));
										$aux_nome = trim(pg_fetch_result($res_linha, $x, nome));
										if ($linha == $aux_linha) {
											$selected = "SELECTED";
										}
										else {
											$selected = "";
										}?>

										<option value='<?=$aux_linha?>' <?=$selected?>><?=$aux_nome?></option> <?
									}
								}
								elseif($login_fabrica != 117) { ?>
									<option value=''><?php echo traduz("Não existem linhas cadastradas"); ?></option><?
								} ?>

								</select> <?
							}else { ?>
								<select name="linha[]" id="linha" multiple="multiple" class='span12'>
									<?php

									$selected_linha = array();
									foreach (pg_fetch_all($res_linha) as $key) {
										if(isset($linha)){
											foreach ($linha as $id) {
												if ( isset($linha) && ($id == $key['linha']) ){
													$selected_linha[] = $id;
												}
											}
										} ?>


										<option value="<?php echo $key['linha']?>" <?php if( in_array($key['linha'], $selected_linha)) echo "SELECTED"; ?> >

											<?php echo $key['nome']?>

										</option>
							  <?php } ?>
								</select>

						<? } ?>
					</div>
				</div>
			</div>
		</div>
<?php
        }

        if($login_fabrica == 1){

            $sqlMarca = "
                SELECT  marca,
                        nome
                FROM    tbl_marca
                WHERE   fabrica = $login_fabrica;
            ";
            $resMarca = pg_query($con,$sqlMarca);
            $marcas = pg_fetch_all($resMarca);
?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group'>
                    <label class='control-label' for='marca'><?php echo traduz("Marca"); ?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select name="marca" id="marca">
                                <option value=""><?php echo traduz("ESCOLHA"); ?></option>
<?
                            foreach($marcas as $chave => $valor){
?>
                                <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $marca) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
                            }
?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?
        }
?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto_referencia", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_referencia'><?php echo traduz("Ref. Produto"); ?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="produto_referencia" name="produto_referencia" class='span9' maxlength="20" value="<? echo $produto_referencia ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto_descricao", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_descricao'><?php echo traduz("Descrição Produto"); ?></label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="produto_descricao" name="produto_descricao" class='span11' value="<? echo $produto_descricao ?>" >
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
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'><?php echo traduz("Código Posto"); ?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='posto_nome'><?php echo traduz("Nome Posto"); ?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="posto_nome" id="posto_nome" class='span12' value="<? echo $posto_nome ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'><?php echo traduz("País"); ?></label>
					<div class='controls controls-row'>
						<?
							$sql = "SELECT  *
									FROM    tbl_pais
									$w
									ORDER BY tbl_pais.nome;";
							$res = pg_exec ($con,$sql);

							if (pg_numrows($res) > 0) {
								echo "<select id='pais' name='pais' class='frm'>\n
								<option value='' selected>TODOS OS PAÍSES</option>";

								for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
									$aux_pais  = trim(pg_fetch_result($res,$x,pais));
									$aux_nome  = trim(pg_fetch_result($res,$x,nome));

									echo "<option value='$aux_pais'";
									if ($pais == $aux_pais){
										echo " SELECTED ";
										$mostraMsgPais = "<br>" . traduz("do PAÍS") .  $aux_nome;
									}
									echo ">$aux_nome</option>\n";
								}
								echo "</select>\n";
							}
							?>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='estado'><?php echo traduz("Por Região"); ?></label>
					<div class='controls controls-row'>
						<select name="estado" class='frm' id="estado">

							<?php if (isset($_POST['pais'])) { 
						 	
						 	$sigla = $_POST['estado'];
						 	$estado = $_POST['estado'];

						 	if ($_POST['pais'] == "BR") {

							 	$brasil = [ 
			      					"AC" => "AC - Acre",
			      					"AL" => "AL - Alagoas",
			      					"AM" => "AM - Amazonas",
			      					"AP" => "AP - Amapá",
			      					"BA" => "BA - Bahia",
			      					"CE" => "CE - Ceará"  ,
			      					"DF" => "DF - Distrito Federal",
			      					"ES" => "ES - Espírito Santo" ,
			      					"GO" => "GO - Goiás","MA - Maranhão", 
									"MG" => "MG - Minas Gerais",
									"MS" => "MS - Mato Grosso do Sul",
									"MT" => "MT - Mato Grosso",
									"PA" => "PA - Pará","PB - Paraíba",
									"PE" => "PE - Pernambuco",
									"PI" => "PI - Piauí","PR - Paraná",
									"RJ" => "RJ - Rio de Janeiro",
									"RN" => "RN - Rio Grande do Norte",
									"RO" => "RO - Rondônia", 
									"RN" => "RR - Roraima",
									"RS" => "RS - Rio Grande do Sul", 
									"SC" => "SC - Santa Catarina",
									"SE" => "SE - Sergipe", 
									"SP" => "SP - São Paulo",
									"TO" => "TO - Tocantins" 
								];

								$estado = $brasil[$sigla];
							} ?>

						 	<option value="<?= $sigla ?>"><?= $estado ?></option>
							 <? } ?>
						</select>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
        <?php if (in_array($login_fabrica, array(169,170))) {
            $sql_status = "SELECT status_checkpoint,descricao,cor FROM tbl_status_checkpoint WHERE status_checkpoint IN (0,1,2,3,4,8,9,28,14,30)";
            $res_status = pg_query($con,$sql_status);
            $total_status = pg_num_rows($res_status);

            $sql_tipo_atendimento = "SELECT DISTINCT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
            $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);
        ?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='estado'><?php echo traduz("Tipo de Atendimento"); ?></label>
                    <div class='controls controls-row'>
                        <select id="tipo_atendimento" name="tipo_atendimento" class='frm'>
                        <?php
                            if(pg_num_rows($res_tipo_atendimento)>0){
                                echo '<option value="" selected></option>';
                                for ($x=0; $x < pg_num_rows($res_tipo_atendimento); $x++) { 
                                    $descricao = pg_fetch_result($res_tipo_atendimento,$x,'descricao');
                                    $tipo_atendimento_consulta = pg_fetch_result($res_tipo_atendimento,$x,'tipo_atendimento');

                                    $selected = ($tipo_atendimento_consulta == $tipo_atendimento) ? 'selected' : '';

                                    echo "<option value='{$tipo_atendimento_consulta}' $selected>{$descricao}</option>";
                                }
                            }
                        ?>
                        </select>
                    </div>
                </div>
            </div>
            <?php
            switch ($consumidor_revenda_pesquisa) {
                case "C":
                    $selected_c = "SELECTED";
                break;

                case "R":
                    $selected_r = "SELECTED";
                break;
            }
            ?>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='estado'><?php echo traduz("Tipo de OS"); ?></label>
                    <div class='controls controls-row'>
                        <select id="consumidor_revenda_pesquisa" name="consumidor_revenda_pesquisa" class='frm'>
                            <option value="">Todas</option>
                            <option value="C" <?php echo $selected_c; ?>><?php echo traduz("Consumidor"); ?></option>
                            <option value="R" <?php echo $selected_r; ?>><?php echo traduz("Revenda"); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <?
        }
		if (in_array($login_fabrica, array(30, 52, 85))) {
			switch ($data_referencia) {
				case "data_fechamento":
					$selected_data_fechamento = "selected";
				break;

				case "data_conserto":
					$selected_data_conserto = "selected";
				break;
			}
			?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='codigo_posto'><?php echo traduz("Referência"); ?></label>
						<div class='controls controls-row'>
							<select id="data_referencia" name="data_referencia" class="frm">
								<option value="data_fechamento" <? echo $selected_data_fechamento; ?>><?php echo traduz("Data do Fechamento"); ?></option>
								<option value="data_conserto" <? echo $selected_data_conserto; ?>><?php echo traduz("Data do Conserto"); ?></option>
							</select>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='cliente_admin'><?php echo traduz("Cliente ADM"); ?></label>
						<div class='controls controls-row'>
						<?
							if ($trava_cliente_admin) {
								$desabilita = "disabled";
							}
							?>
							<input type='hidden' name='cliente_admin' id='cliente_admin'  value="<? echo $cliente_admin; ?>">
							<input name="cliente_nome_admin" id="cliente_nome_admin" class="frm" <? echo $desabilita; ?> value='<?echo $cliente_nome_admin ;?>' class='input_req' type="text" size="30" maxlength="50">
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
	 <? }
	 if($login_fabrica == 52) {?>
	 	<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='familia'><?php echo traduz("Família"); ?></label>
					<div class='controls controls-row'>
	 					<input class="frm" type="text" name="cidade" id="cidade" value="<?=$cidade?>" onkeypress="maiuscula(this.value)"   readonly>
	 				</div>
	 			</div>
	 		</div>
	 		<div class='span4'>
					<div class='control-group <?=(in_array("marca_logo", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='cliente_admin'><?php echo traduz("Marca"); ?></label>
						<div class='controls controls-row'>
							<?
							$sql_fricon = "SELECT marca, nome
											FROM tbl_marca
											WHERE tbl_marca.fabrica = $login_fabrica
											ORDER BY tbl_marca.nome ";

							$res_fricon = pg_query($con, $sql_fricon); ?>

							<select name='marca_logo' id='marca_logo' class="frm">
								<?
								if (pg_num_rows($res_fricon) > 0) { ?>
									<option value=''>ESCOLHA</option> <?
									for ($x = 0 ; $x < pg_num_rows($res_fricon) ; $x++){
										$marca_aux = trim(pg_fetch_result($res_fricon, $x, marca));
										$nome_aux = trim(pg_fetch_result($res_fricon, $x, nome));
										if ($marca_logo == $marca_aux) {
											$selected = "SELECTED";
										}else {
											$selected = "";
											}?>
										<option value='<?=$marca_aux?>' <?=$selected?>><?=$nome_aux?></option> <?
									}
								}else { ?>
									<option value=''><?php echo traduz("Não existem linhas cadastradas"); ?></option>
								<?php } ?>
								</select>
						</div>
					</div>
				</div>
			<div class='span2'></div>
	 	</div> <?
	 }?>
	<input type='hidden' id='btn_click' name='btn_acao' value=''><br/>
	<div class="row-fluid">
            <!-- margem -->
            <div class="span4"></div>

            <div class="span4">
                <div class="control-group">
                    <div class="controls controls-row tac">
                        <button type="button" class="btn" value="Gravar" alt="Gravar formulário" onclick="submitForm($(this).parents('form'),'ok');" ><?php echo traduz("Pesquisar"); ?></button>
                    </div>
                </div>
            </div>

            <!-- margem -->
            <div class="span4"></div>
        </div>
</form>
	<br>

	<?
	flush();
}

	echo "<center><font size='1'></font></center>";

	if($_POST) { ?>
	</div>
	<table id="resultado_pesquisa" class='table table-striped table-bordered table-hover table-large' >
	<thead>
			<tr class='titulo_coluna'>
			<th><?php echo traduz("Posto"); ?></th> <?

			if ($login_fabrica == 30) {?>

				<th>D+1</th>
				<th>D+2</th>
				<th>D+3</th>
				<th>D+4</th>
				<th><?php echo traduz("ACIMA");?> D+4</th>

		<?	} else if(in_array($login_fabrica,array(141,144))){ ?>
				<th>0 <?php echo traduz("a"); ?> 10</th>
				<th>11 <?php echo traduz("a"); ?> 20</th>
				<th>21 <?php echo traduz("a"); ?> 30</th>
				<th><?php echo traduz("mais de");?> 30</th><?
            }elseif(in_array($login_fabrica, array(169,170))){?>
                <th>0 <?php echo traduz("a"); ?> 5</th>
                <th>6 <?php echo traduz("a"); ?> 10</th>
                <th>11 <?php echo traduz("a"); ?> 15</th>
                <th>16 <?php echo traduz("a"); ?> 20</th>
                <th>21 <?php echo traduz("a"); ?> 25</th>
                <th>26 <?php echo traduz("a"); ?> 30</th>
                <th>31 <?php echo traduz("a"); ?> 60</th>
                <th>61 <?php echo traduz("a"); ?> 90</th>
                <th><?php echo traduz("mais de");?> 90</th>
            <?
			}else { ?>
				 <? 
           		 if (in_array($login_fabrica, array(152, 180, 181, 182))) {
				 	echo "<th>" . traduz("Até 10 Dia") . "</th>";
				 }else{
				 	echo "<th>" . traduz("Até 1 Dia") . "</th>";
				 }

				if ($login_fabrica == 96) { ?>
				<th> <?php echo traduz("Até 5 Dias"); ?></th>
				<th> <?php echo traduz("Até 15 Dias");?></th>
				<th> <?php echo traduz("Mais que 15 Dias"); ?> </th> <?
				}else{
					
            		if (in_array($login_fabrica, array(152, 180, 181, 182))) {
						echo "<th>" . traduz("de 11 até 25 dias") . "</th>";
						echo "<th>" . traduz("Mais que 25 Dias") . "</th>";
					}else{
						echo "<th>" . traduz("Até 2 Dias") . "</th>";
						echo "<th>" . traduz("Mais que 2 Dias") . "</th>";
					}
					?>
				 <?
				}
			}


			if(in_array($login_fabrica,array(14, 43, 66))){ ?>
				<th>Média<?php echo traduz("Média"); ?></th> <?
			}?>
			<th><?php echo traduz("OS em Aberto"); ?></th>
			<th><?php echo traduz("Total de OS"); ?></th>
			</tr>
		<?
			//HD 216470: Acrescentada busca por Posto
			if ($posto_id) {
			} else {
				if ($login_fabrica == 30) {
					$sql = "
            					SELECT
            					COUNT(os) AS total, dias

            					FROM
            					temp_rtc_$mes

            					WHERE dias <= 4

            					GROUP BY dias
            					";
		//			echo(nl2br($sql));
					$res_totais = pg_query($con, $sql);

					$totais = array();

					for ($i = 0; $i < pg_num_rows($res_totais); $i++) {
						$dias = pg_fetch_result($res_totais, $i, "dias");
						$totais[$dias] = pg_fetch_result($res_totais, $i, "total");
					}
					$sql = "
					SELECT
					COUNT(os) AS total

					FROM
					temp_rtc_$mes

					WHERE dias > 4
					";
					$res_totais = pg_query($con, $sql);

					$totais["ACIMA"] = pg_fetch_result($res_totais, 0, "total");
				} else {
					if(in_array($login_fabrica,array(141,144))){
						$sqld = "SELECT count(os) AS total,
							 SUM(($data_referencia - data_abertura)) AS data_diferenca
							 FROM temp_rtc_$mes
							 WHERE $data_referencia::date-data_abertura <= 10";
						$resd = pg_query($con,$sqld);
						$total_1 = pg_fetch_result($resd,0,0);
						$total_1d = pg_fetch_result($resd,0,data_diferenca);
					} else {

						if (in_array($login_fabrica, array(152, 180, 181, 182))) {
							$qtde_dias_total = 10;
						}else{
							$qtde_dias_total = 1;
						}

						$sqld = "SELECT count(os) AS total,
							 SUM(($data_referencia - data_abertura)) AS data_diferenca
							 FROM temp_rtc_$mes
							 WHERE $data_referencia::date-data_abertura <= $qtde_dias_total";
						$resd = pg_query($con,$sqld);
						$total_1 = pg_fetch_result($resd,0,0);
						$total_1d = pg_fetch_result($resd,0,data_diferenca);

					}

					if($login_fabrica == 96){
						$sqle = "SELECT count(os) AS total,
            							SUM(($data_referencia - data_abertura)) AS data_diferenca
            							FROM temp_rtc_$mes
            							WHERE $data_referencia::date-data_abertura <= 5
            							AND   $data_referencia::date-data_abertura >= 2;";
                                                                        $rese = pg_query($con,$sqle);
                                                                        $total_5 = pg_fetch_result($rese,0,0);
                                                                        $total_1e = pg_fetch_result($rese,0,data_diferenca);
					}

					if($login_fabrica == 96){
						$sqld = "SELECT count(os) AS total,
								SUM(($data_referencia - data_abertura)) AS data_diferenca
								FROM temp_rtc_$mes
								WHERE $data_referencia::date-data_abertura <= 15
								AND   $data_referencia::date-data_abertura >= 6;
								";
						$resd = pg_query($con,$sqld);
						$total_2 = pg_fetch_result($resd,0,0);
						$total_2d = pg_fetch_result($resd,0,data_diferenca);
					}else if(in_array($login_fabrica,array(141,144))){
						$sqld = "SELECT count(os) AS total,
                                                                                                SUM(($data_referencia - data_abertura)) AS data_diferenca
                                                                                                FROM temp_rtc_$mes
                                                                                                WHERE $data_referencia::date-data_abertura <= 20
                                                                                                AND   $data_referencia::date-data_abertura >  10";
                                                                        $resd = pg_query($con,$sqld);
                                                                        $total_2 = pg_fetch_result($resd,0,0);
                                                                        $total_2d = pg_fetch_result($resd,0,data_diferenca);
					}else{

						if (in_array($login_fabrica, array(152, 180, 181, 182))) {
							$qtde_max = 25;
							$qtde_min = 10;
						}else{
							$qtde_max = 2;
							$qtde_min = 1;
						}

						$sqld = "SELECT count(os) AS total,
            							SUM(($data_referencia - data_abertura)) AS data_diferenca
            							FROM temp_rtc_$mes
            							WHERE $data_referencia::date-data_abertura <= $qtde_max
            							AND   $data_referencia::date-data_abertura >  $qtde_min;";
						$resd = pg_query($con,$sqld);
						$total_2 = pg_fetch_result($resd,0,0);
						$total_2d = pg_fetch_result($resd,0,data_diferenca);
					}

					if($login_fabrica == 96){
						$sqld = "SELECT count(os) AS total,
            							SUM(($data_referencia - data_abertura)) AS data_diferenca
            							FROM temp_rtc_$mes
            							WHERE $data_referencia::date-data_abertura >= 16;";
						$resd = pg_query($con,$sqld);
						$total_3 = pg_fetch_result($resd,0,0);
						$total_3d = pg_fetch_result($resd,0,data_diferenca);
					}else if(in_array($login_fabrica,array(141,144))){
						$sqld = "SELECT count(os) AS total,
                                                                                                SUM(($data_referencia - data_abertura)) AS data_diferenca
                                                                                                FROM temp_rtc_$mes
                                                                                                WHERE $data_referencia::date-data_abertura <= 30
                                                                                                AND   $data_referencia::date-data_abertura > 20;";
                                                                        $resd = pg_query($con,$sqld);
                                                                        $total_3 = pg_fetch_result($resd,0,0);
                                                                        $total_3d = pg_fetch_result($resd,0,data_diferenca);

					}else{

						if (in_array($login_fabrica, array(152, 180, 181, 182))) {
							$qtde_max = 25;
						}else{
							$qtde_max = 2;
						}

						$sqld = "SELECT count(os) AS total,
            							SUM(($data_referencia - data_abertura)) AS data_diferenca
            							FROM temp_rtc_$mes
            							WHERE $data_referencia::date-data_abertura > $qtde_max;";
						$resd = pg_query($con,$sqld);
						$total_3 = pg_fetch_result($resd,0,0);
						$total_3d = pg_fetch_result($resd,0,data_diferenca);
					}

					if(in_array($login_fabrica,array(141,144))){
                                                                        $sqld = "SELECT count(os) AS total,
                                                                                                SUM(($data_referencia - data_abertura)) AS data_diferenca
                                                                                                FROM temp_rtc_$mes
                                                                                                WHERE $data_referencia::date-data_abertura > 30;";
                                                                        $resd = pg_query($con,$sqld);
                                                                        $total_5 = pg_fetch_result($resd,0,0);
                                                                        $total_5d = pg_fetch_result($resd,0,data_diferenca);

                                                            }
				}

				if ($login_fabrica == 96){
				            $sqld = "SELECT count(os) AS total
            						FROM tbl_os
            						$join_linha
            						WHERE   tbl_os.fabrica = $login_fabrica
            						AND     tbl_os.posto IN (SELECT POSTO FROM temp_rtc_$mes)
            						AND     tbl_os.data_abertura   BETWEEN '$data_inicial' AND '$data_final'
            						AND     $data_referencia::date IS NULL
            						$cont_cliente_admin
            						$add_1 $add_11 $add_2 $add_3 ; ";
				} else {
                            if (in_array($login_fabrica, array(138))) {
                                        $sqld = "SELECT count(tbl_os.os) AS total
                                                    FROM tbl_os_produto
                                                    JOIN tbl_os USING (os)
                                                    $join_linha
                                                    $join_marca
                                                    $join_1
                                                    WHERE   tbl_os.fabrica = $login_fabrica
                                                    AND     tbl_os.posto IN (SELECT POSTO FROM temp_rtc_$mes)
                                                    AND     tbl_os.data_abertura   BETWEEN '$data_inicial' AND '$data_final'
                                                    AND     tbl_os.excluida IS NOT TRUE
                                                    AND     $data_referencia::date IS NULL
                                                    $cont_cliente_admin
                                                    $add_1 $add_11 $add_2 $add_3 $add_cidade; ";
                            } else {

                            	if($login_fabrica == 163){
					            	$join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
					           		$cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
					            }

		                        $sqld = "SELECT count(os) AS total
            						FROM tbl_os
            						$join_linha
            						$join_marca
            						$join_1
            						$join_163
            						WHERE   tbl_os.fabrica = $login_fabrica
            						AND     tbl_os.posto IN (SELECT POSTO FROM temp_rtc_$mes)
            						AND     tbl_os.data_abertura   BETWEEN '$data_inicial' AND '$data_final'
            						AND     tbl_os.excluida IS NOT TRUE
            						AND     $data_referencia::date IS NULL
            						$cont_cliente_admin
            						$cond_163
            						$add_1 $add_11 $add_2 $add_3 $add_cidade; ";
            	                                            }
				}
				#if($ip=='201.76.83.168') { echo nl2br($sqld); }

				$resd = pg_query($con,$sqld);
				$total_4 = pg_fetch_result($resd,0,0);
				$total_4d = pg_fetch_result($resd,0,data_diferenca);

				if ($excel) {
				} else {
					$link_abre = "<a style='color: #596d9b' title='". traduz("Clique para ver o Relatório de Tempo de Permanência em Conserto / Posto")."' href='relatorio_tempo_conserto_postos.php?mes=$mes&ano=$ano&estado=$estado&pais=$pais&familia=$familia&produto_referencia=$produto_referencia'>";
					$link_fecha = "</a>";
				}?>
		</thead>
		<tbody>
			<tr bgcolor='#F7F5F0'>
			<td><?=$link_abre . traduz("TOTAIS POSTOS") . $link_fecha ?></td> <?
			if ($login_fabrica == 30) { ?>
				<td class='tac'><?=(($totais[0] + $totais[1]) == 0 ? "" : $totais[0] + $totais[1])?></td>
				<td class='tac'><?=$totais[2]?></td>
				<td class='tac'><?=$totais[3]?></td>
				<td class='tac'><?=$totais[4]?></td>
				<td class='tac'><?=$totais["ACIMA"]?></td>
                                    <? } else {
				if ($login_fabrica == 96){ ?>
					<td class='tac' title='<?=$total_1d?>'><?=$total_1?></td>
					<td class='tac' title='<?=$total_5e?>'><?=$total_5?></td>
					<td class='tac' title='<?=$total_2d?>'><?=$total_2?></td>
					<td class='tac' title='<?=$total_3d?>'><?=$total_3?></td>
                                                <? } else if (in_array($login_fabrica,array(141,144))){ ?>
					<td class='tac' title='<?=$total_1d?>'><?=$total_1?></td>
                                                            <td class='tac' title='<?=$total_5e?>'><?=$total_2?></td>
                                                            <td class='tac' title='<?=$total_2d?>'><?=$total_3?></td>
                                                            <td class='tac' title='<?=$total_3d?>'><?=$total_5?></td>
                    <? }elseif(in_array($login_fabrica, array(169,170))){
                        $info_os = retorna_intervalo();
                        foreach ($info_os as $intervalos) {
                            echo "<td class='tac' title='".$intervalos['total']."'>".$intervalos['total']."</td>";
                        }
                    ?>
                                                <? } else { ?>
					<td class='tac' title='<?=$total_1d?>'><?=$total_1?></td>
					<td class='tac' title='<?=$total_2d?>'><?=$total_2?></td>
					<td class='tac' title='<?=$total_3d?>'><?=$total_3?></td><?
				}
			}
			if(in_array($login_fabrica,array(14, 43, 66))){
				if ($total_1+$total_2+$total_3 > 0) {
					$exibetotal = (($total_1d+$total_2d+$total_3d) / ($total_1+$total_2+$total_3));
					$exibetotalX = number_format($exibetotal,2,'.','');?>
					<td><?=$exibetotalX?></td> <?
				}
			}?>
			<td class='tac' title='<?=$total_4d?>'><?=$total_4?></td> <?
			if ($login_fabrica == 30) { ?>
				<td class='tac'><?=(intval($totais[0]) + intval($totais[1]) + intval($totais[2]) + intval($totais[3]) + intval($totais[4]) + intval($totais["ACIMA"]) + intval($total_4)) ?></td> <?
			} else {
				$total_de_os = (intval($total_1) + intval($total_2) + intval($total_5) + intval($total_3) + intval($total_4));?>
				<td class='tac'><?=$total_de_os ?></td> <?
			}?>
			</tr>

                                    <?
			#HD 822153
			if ($login_fabrica == 96) {
				$porc_total_1 = ($total_1*100) / $total_de_os;
				$porc_total_5 = ($total_5*100) / $total_de_os;
				$porc_total_2 = ($total_2*100) / $total_de_os;
				$porc_total_3 = ($total_3*100) / $total_de_os;
				$porc_total_4 = ($total_4*100) / $total_de_os;

				$porc_total_1 = round($porc_total_1,2);
				$porc_total_5 = round($porc_total_5,2);
				$porc_total_2 = round($porc_total_2,2);
				$porc_total_3 = round($porc_total_3,2);
				$porc_total_4 = round($porc_total_4,2);


			}
			$grafico = "<script>
							var chart;

							$(document).ready(function() {
							    chart = new Highcharts.Chart({
									chart: {
										renderTo: 'container',
										plotBackgroundColor: null,
										plotBorderWidth: null,
										plotShadow: false
									},
									title: {
										text:'". traduz("Total de OS:") . $total_de_os ."'
									},
									tooltip: {
										formatter: function() {
											return '<b>'+ this.point.name +'</b>: '+ Highcharts.numberFormat(this.y, 2, '.') +' %';
										}
									},
									plotOptions: {
										pie: {
											allowPointSelect: true,
											cursor: 'pointer',
											dataLabels: {
												enabled: true,
												color: '#000000',
												connectorColor: '#000000',
												formatter: function() {
													return '<b>'+ this.point.name +'</b>: '+ Highcharts.numberFormat(this.y, 2, '.') +' %';
												}
											}
										}
									},
									series: [{
										type: 'pie',
										name: 'Browser share',
										data: [
											['Até 1 dia',   $porc_total_1],
											['Até 5 dias',       $porc_total_5],
											['Até 15 dias',       $porc_total_2],

											['Mais que 15 dias',    $porc_total_3],
											['Os em aberto',     $porc_total_4],
										]
									}]
								});


							});

						</script>";
			if ($login_fabrica == 96) {

				echo $grafico;

			}

		}

		flush();

		//HD 216470: Acrescentada busca por Posto
		if ($posto_id) {
			$whereSql = "WHERE XX.posto = $posto_id";
		}
		$sql = "SELECT DISTINCT  XX.posto,codigo_posto,nome,PF.contato_cidade AS cidade,PF.contato_estado AS estado
			FROM temp_rtc_$mes     XX
			JOIN tbl_posto         PO ON XX.posto = PO.posto
			JOIN tbl_posto_fabrica PF ON XX.posto = PF.posto AND PF.fabrica = $login_fabrica
			$whereSql
			ORDER BY nome;";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			for( $i=0;$i<pg_num_rows($res);$i++ ) {
				$posto        = pg_fetch_result($res,$i,posto);
				$codigo_posto = pg_fetch_result($res,$i,codigo_posto);
				$nome_posto   = pg_fetch_result($res,$i,nome);
				$cidade_posto = pg_fetch_result($res,$i,cidade);
				$estado_posto = pg_fetch_result($res,$i,estado);

				if ($login_fabrica == 30) {
					$sql = "SELECT
                        					COUNT(os) AS total, dias

                        					FROM
                        					temp_rtc_$mes

                        					WHERE dias <= 4
                        					AND posto = $posto

                        					GROUP BY dias";
					//echo(nl2br($sql));
					$res_totais = pg_query($con, $sql);

					$totais = array();

					for ($j = 0; $j < pg_num_rows($res_totais); $j++) {
						$dias = pg_fetch_result($res_totais, $j, "dias");
						$totais[$dias] = pg_fetch_result($res_totais, $j, "total");
					}

					$sql = "SELECT
                        					COUNT(os) AS total

                        					FROM
                        					temp_rtc_$mes

                        					WHERE dias > 4
                        					AND posto = $posto;";
					$res_totais = pg_query($con, $sql);

					$totais["ACIMA"] = pg_fetch_result($res_totais, 0, "total");
				}else {
					if(in_array($login_fabrica,array(141,144))){
						$sqld = "SELECT count(os) AS total,
                                                                                    	 SUM(($data_referencia - data_abertura)) AS data_diferenca
                                                                                    	 FROM temp_rtc_$mes
                                                                                    	 WHERE $data_referencia::date-data_abertura <= 10
                                                                                    	 AND   $data_referencia::date-data_abertura >= 0
                                                                                    	 AND   posto = $posto; ";
                    }elseif (in_array($login_fabrica, array(169,170))) {
                        $info_os = retorna_intervalo($posto);
					}else{
						if (in_array($login_fabrica, array(152, 180, 181, 182))) {
							$qtde = 10;
						}else{
							$qtde = 1;
						}
						$sqld = "SELECT count(os) AS total,
            							SUM(($data_referencia - data_abertura)) AS data_diferenca
            							FROM temp_rtc_$mes
            							WHERE $data_referencia::date-data_abertura<=$qtde
            							AND   posto = $posto;";
					}
					$resd = pg_query($con,$sqld);
					$total_1 = pg_fetch_result($resd,0,0);
					$total_1d = pg_fetch_result($resd,0,data_diferenca);

					if($login_fabrica == 96){
						$sqle = "SELECT count(os) AS total,
            							SUM(($data_referencia - data_abertura)) AS data_diferenca
            							FROM temp_rtc_$mes
            							WHERE $data_referencia::date-data_abertura <= 5
            							AND   $data_referencia::date-data_abertura >= 2
            							AND   posto = $posto;";
						$rese = pg_query($con,$sqle);
						$total_5 = pg_fetch_result($rese,0,0);
						$total_5e = pg_fetch_result($rese,0,data_diferenca);
					}

					if(in_array($login_fabrica,array(141,144))){
						$sqle = "SELECT count(os) AS total,
                                                                                                SUM(($data_referencia - data_abertura)) AS data_diferenca
                                                                                                FROM temp_rtc_$mes
                                                                                                WHERE $data_referencia::date-data_abertura > 30
                                                                                                AND   posto = $posto;";
                                                                        $rese = pg_query($con,$sqle);
                                                                        $total_5 = pg_fetch_result($rese,0,0);
                                                                        $total_5e = pg_fetch_result($rese,0,data_diferenca);
					}

					if($login_fabrica == 96){
						$sqld = "SELECT count(os) AS total,
								SUM(($data_referencia - data_abertura)) AS data_diferenca
								FROM temp_rtc_$mes
								WHERE $data_referencia::date-data_abertura <= 15
								AND   $data_referencia::date-data_abertura >= 6
								AND   posto = $posto;";
						$resd = pg_query($con,$sqld);
						$total_2 = pg_fetch_result($resd,0,0);
						$total_2d = pg_fetch_result($resd,0,data_diferenca);
					}else if(in_array($login_fabrica,array(141,144))){
						$sqld = "SELECT count(os) AS total,
                                SUM(($data_referencia - data_abertura)) AS data_diferenca
                                FROM temp_rtc_$mes
                                WHERE $data_referencia::date-data_abertura <= 20
                                AND   $data_referencia::date-data_abertura >= 11
                                AND   posto = $posto;";
                                                                        $resd = pg_query($con,$sqld);
                                                                        $total_2 = pg_fetch_result($resd,0,0);
                                                                        $total_2d = pg_fetch_result($resd,0,data_diferenca);
					}else{

						if (in_array($login_fabrica, array(152, 180, 181, 182))) {
							$qtde_max = 25;
							$qtde_min = 10;
						}else{
							$qtde_max = 2;
							$qtde_min = 1;
						}

						$sqld = "SELECT count(os) AS total,
            							SUM(($data_referencia - data_abertura)) AS data_diferenca
            							FROM temp_rtc_$mes
            							WHERE $data_referencia::date-data_abertura <= $qtde_max
            							AND   $data_referencia::date-data_abertura >  $qtde_min
            							AND   posto = $posto;";
						$resd = pg_query($con,$sqld);
						$total_2 = pg_fetch_result($resd,0,0);
						$total_2d = pg_fetch_result($resd,0,data_diferenca);
					}

					if($login_fabrica == 96){
						$sqld = "SELECT count(os) AS total,
								SUM(($data_referencia - data_abertura)) AS data_diferenca
								FROM temp_rtc_$mes
								WHERE $data_referencia::date-data_abertura > 15
								AND   posto = $posto;";
						$resd = pg_query($con,$sqld);
						$total_3 = pg_fetch_result($resd,0,0);
						$total_3d = pg_fetch_result($resd,0,data_diferenca);
					}else if(in_array($login_fabrica,array(141,144))){
                                                                        $sqld = "SELECT count(os) AS total,
                                                                                                SUM(($data_referencia - data_abertura)) AS data_diferenca
                                                                                                FROM temp_rtc_$mes
                                                                                                WHERE $data_referencia::date-data_abertura <= 30
                                                                                                AND   $data_referencia::date-data_abertura >= 21
                                                                                                AND   posto = $posto;";
                                                                        $resd = pg_query($con,$sqld);
                                                                        $total_3 = pg_fetch_result($resd,0,0);
                                                                        $total_3d = pg_fetch_result($resd,0,data_diferenca);
                                                            }else{

						if (in_array($login_fabrica, array(152, 180, 181, 182))) {
							$qtde_max = 25;
						}else{
							$qtde_max = 2;
						}

						$sqld = "SELECT count(os) AS total,
								SUM(($data_referencia - data_abertura)) AS data_diferenca
								FROM temp_rtc_$mes
								WHERE $data_referencia::date-data_abertura > $qtde_max
								AND   posto = $posto;";
						$resd = pg_query($con,$sqld);
						$total_3 = pg_fetch_result($resd,0,0);
						$total_3d = pg_fetch_result($resd,0,data_diferenca);

					}
				}

				$sqld = "SELECT count(os) AS total,
						SUM(($data_referencia - data_abertura)) AS data_diferenca
						FROM temp_rtc_$mes
						WHERE $data_referencia::date IS NULL
						AND   posto = $posto;";

				if ($login_fabrica == 96){
				            $sqld = "SELECT count(os) AS total
            						FROM tbl_os
            						$join_linha
            						WHERE   tbl_os.fabrica = $login_fabrica
            						AND     tbl_os.data_abertura   BETWEEN '$data_inicial' AND '$data_final'
            						AND     posto = $posto
            						AND     $data_referencia::date IS NULL
            						$cont_cliente_admin
            						$add_1 $add_11 $add_2 $add_3 ; ";
				} else if (in_array($login_fabrica, array(138))) {
                                                            $sqld = "SELECT count(tbl_os.os) AS total
                                                                                    FROM tbl_os_produto
                                                                                    JOIN tbl_os USING (os)
                                                                                    $join_linha
                                                                                    $join_marca
                                                                                    $join_1
                                                                                    WHERE   tbl_os.fabrica = $login_fabrica
                                                                                    AND     tbl_os.data_abertura   BETWEEN '$data_inicial' AND '$data_final'
                                                                                    AND     tbl_os.excluida IS NOT TRUE
                                                                                    AND     $data_referencia::date IS NULL
                                                                                    AND     posto = $posto
                                                                                    $cont_cliente_admin
                                                                                    $add_1 $add_11 $add_2 $add_3; ";
                } else {

                	if($login_fabrica == 163){
		            	$join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
		           		$cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
		            }

					$sqld = "SELECT count(os) AS total
            						FROM tbl_os
            						$join_linha
            						$join_marca
            						$join_1
            						$join_163
            						WHERE   tbl_os.fabrica = $login_fabrica
            						AND     tbl_os.data_abertura   BETWEEN '$data_inicial' AND '$data_final'
            						AND     tbl_os.excluida IS NOT TRUE
            						AND     $data_referencia::date IS NULL
            						AND     posto = $posto
            						$cont_cliente_admin
            						$cond_163
            						$add_1 $add_11 $add_2 $add_3; ";
				}

				$resd = pg_query($con,$sqld);
				$total_4  = pg_fetch_result($resd,0,0);
				$total_4d = pg_fetch_result($resd,0,data_diferenca);

				flush();
				if ($i % 2 == 0) {
					$cor = "#F1F4FA";
				}else{
					$cor = "#F7F5F0";
				}

				$linhas = implode(",",$linha);
				if (!$excel) {
					$link_abre = "<a href='relatorio_tempo_conserto_os.php?". ($login_fabrica == 52 ? "data_inicial=$data_inicial&data_final=$data_final" : "mes=$mes&ano=$ano") . "&estado=$estado&pais=$pais&familia=$familia&linha=$linhas&produto_referencia=$produto_referencia&posto=$posto&cliente_admin=$cliente_admin&periodo=mes_atual&tipo_os=todas&data_referencia=$data_referencia&cidade=$cidade&marca_logo=$marca_logo' title='". traduz("Clique para ver as OSs")."'>";
					$link_fecha = "</a>";
				}?>

				<tr bgcolor='<?=$cor?>'>
				<td align='left' nowrap><? echo "$link_abre$codigo_posto - $nome_posto ($cidade_posto-$estado_posto)$link_fecha";?></td> <?
				if ($login_fabrica == 30) {?>
					<td class='tac'><?=($totais[0] + $totais[1])?></td>
					<td class='tac'><?=$totais[2]?></td>
					<td class='tac'><?=$totais[3]?></td>
					<td class='tac'><?=$totais[4]?></td>
					<td class='tac'><?=($totais["ACIMA"] == 0 ? "" : $totais["ACIMA"])?></td><?
                }elseif (in_array($login_fabrica, array(169,170))) {
                    $total_aux = 0;
                    foreach ($info_os as $dados) {
                        $total_aux += $dados['total'];
                        echo "<td class='tac'>".$dados['total']."</td>";
                    }
                    $total_aux += intval($total_4);
				} else {
					if($login_fabrica == 96){ ?>
						<td class='tac' title='<?=$total_1d?>'><?=$total_1?></td>
						<td class='tac' title='<?=$total_5e?>'><?=$total_5?></td>
						<td class='tac' title='<?=$total_2d?>'><?=$total_2?></td>
						<td class='tac' title='<?=$total_3d?>'><?=$total_3?></td><?
					}else if(in_array($login_fabrica,array(141,144))){ ?>
						<td class='tac' title='<?=$total_1d?>'><?=$total_1?></td>
                                                <td class='tac' title='<?=$total_5e?>'><?=$total_2?></td>
                                                <td class='tac' title='<?=$total_2d?>'><?=$total_3?></td>
                                                <td class='tac' title='<?=$total_3d?>'><?=$total_5?></td><?
					}else{ ?>
						<td class='tac' title='<?=$total_1d?>'><?=$total_1?></td>
						<td class='tac' title='<?=$total_2d?>'><?=$total_2?></td>
						<td class='tac' title='<?=$total_3d?>'><?=$total_3?></td><?
					}
				}
				if($login_fabrica == 14 or $login_fabrica == 43 or $login_fabrica == 66){
					if ($total_1+$total_2+$total_3 > 0) {
						$exibetotal = (($total_1d+$total_2d+$total_3d) / ($total_1+$total_2+$total_3));
						$exibetotalX = number_format($exibetotal,2,'.','');?>
						<td><?=$exibetotalX?></td><?
					}
				}?>
				<td class='tac' title='<?=$total_4d?>'><?=$total_4?></td><?
				if ($login_fabrica == 30) {?>
					<td class='tac'><?=(intval($totais[0]) + intval($totais[1]) + intval($totais[2]) + intval($totais[3]) + intval($totais[4]) + intval($totais["ACIMA"]) + intval($total_4)) ?></td><?
                }elseif(in_array($login_fabrica, array(169, 170))){
                    echo "<td class='tac'>$total_aux</td>";
				}else {?>
					<td class='tac'><?=(intval($total_1) + intval($total_5) + intval($total_2) + intval($total_3) + intval($total_4))?></td><?
				} ?>
				</tr></tbody><?
		}
	}?>
	</body>
	</table><?
	}



if ($excel) {
	$conteudo_excel = ob_get_clean();
	$arquivo = fopen("xls/relatorio_tempo_conserto_mes_$login_fabrica$login_admin.xls", "w+");
	fwrite($arquivo, $conteudo_excel);
	fclose($arquivo);
	header("location:xls/relatorio_tempo_conserto_mes_$login_fabrica$login_admin.xls");
	echo "<br><br>";
	echo "<a href='" . $PHP_SELF . "?" . $_SERVER["QUERY_STRING"] . "&excel=1' style='font-size: 10pt;'><img src='imagens/excell.gif'>". traduz("Clique aqui para download do relatório em Excel") . "</a>";
	echo "<br><br>"; 
}
else {

	include "rodape.php";
}

?>
