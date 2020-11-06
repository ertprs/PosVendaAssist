<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include '../ajax_cabecalho.php';

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';

$makita = 42;
$elgin = 117;
$layout_menu = "tecnica";
$title = "TREINAMENTO";

$treinamento_posto = $_POST["treinamento_posto"];
if(strlen($treinamento_posto)>0 AND isset($altera_tecnico)){

		$treinamento_posto = $_POST["treinamento_posto_variavel"];
		$titulo            = $_POST["titulo"];
		$data_inicio       = $_POST["data_inicio"];
		$data_fim          = $_POST["data_fim"];
		$tecnico_nome      = $_POST["tecnico_nome"];
		$tecnico_rg        = $_POST["tecnico_rg"];
		$tecnico_cpf       = $_POST["tecnico_cpf"];
		$tecnico_fone      = $_POST["tecnico_fone"];

		if($login_fabrica == $makita){
			$tecnico_email			= $_POST["tecnico_email"];
			if(filter_var($tecnico_email,FILTER_VALIDATE_EMAIL)){
				$tecnico_calcado		= $_POST["tecnico_calcado"];
				$tecnico_tipo_sanguineo = $_POST["tecnico_tipo_sanguineo"];
				$tecnico_celular		= $_POST["tecnico_celular"];
				$tecnico_doencas		= $_POST["tecnico_doencas"];
				$tecnico_medicamento	= $_POST["tecnico_medicamento"];
				$tecnico_necessidade	= $_POST["tecnico_necessidade"];

				$sql = "select tecnico from tbl_treinamento_posto where treinamento_posto = ".$treinamento_posto;
				$res = pg_exec ($con,$sql);

				$tecnico = pg_result($res,0,tecnico);
				$sql = "UPDATE  tbl_tecnico
                        SET     nome                    = '$tecnico_nome'           ,
                                rg                      = '$tecnico_rg'             ,
                                cpf                     = '$tecnico_cpf'            ,
                                telefone                = '$tecnico_fone'           ,
                                celular                 = '$tecnico_celular'        ,
                                email                   = '$tecnico_email'          ,
                                calcado                 = '$tecnico_calcado'        ,
                                doencas                 = '$tecnico_doencas'		,
                                medicamento				= '$tecnico_medicamento'	,
                                necessidade_especial    = '$tecnico_necessidade'	,
                                tipo_sanguineo			= '$tecnico_tipo_sanguineo'
                        WHERE   tecnico = $tecnico;";




				$msg_sucesso = "Dados do Técnico alterados com sucesso";
			}else {
				//header("Location: $PHP_SELF?treinamento_posto={$treinamento_posto}");
				$msg = "E-mail {$tecnico_email} inválido";
			}

		}else{

				if($treinamento_posto){
					$sql = "select tecnico from tbl_treinamento_posto where treinamento_posto = ".$treinamento_posto;

					$res = pg_exec ($con,$sql);
					$tecnico = pg_result($res,0,tecnico);

					$sql = "UPDATE tbl_tecnico SET
							nome = '$tecnico_nome',
							rg   = '$tecnico_rg'  ,
							cpf  = '$tecnico_cpf' ,
							telefone = '$tecnico_fone'
							WHERE tecnico = $tecnico;";
				}else{
					$msg_erro = "Tecnico não encontrado";
				}



			$res = pg_exec ($con,$sql);
			$msg_sucesso = "Dados do Técnico alterados com sucesso";
		}
}

include "cabecalho.php";

?>
<style>
.Titulo {
	text-align: center;
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #6699CC;
}
.Subtitulo {
	text-align: center;
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	color: #333333;
}
.Conteudo {
	font-family: Arial;
	font-size: 10px;
	font-weight: normal;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}

.Erro{
	BORDER-RIGHT: #990000 1px solid;
	BORDER-TOP: #990000 1px solid;
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid;
	BORDER-BOTTOM: #990000 1px solid;
	BACKGROUND-COLOR: #FF0000;
}

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

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
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
}
#familia{
	width: 151px;
}
</style>

<?
$aux_treinamento_posto = $_GET["treinamento_posto"];
$ajax                  = $_GET["ajax"];
if($ajax=='enviar'){

	$sql = "SELECT  treinamento           ,
					tbl_tecnico.nome as tecnico_nome,
					tbl_tecnico.rg as tecnico_rg,
					tbl_tecnico.cpf as tecnico_cpf,
					tbl_tecnico.telefone as tecnico_fone,
			tbl_posto.posto               ,
			tbl_posto.email               ,
			tbl_posto.nome                ,
			tbl_posto_fabrica.codigo_posto
		 FROM tbl_treinamento_posto
		JOIN tbl_posto         USING(posto)
		JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_treinamento_posto.tecnico
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE treinamento_posto = $aux_treinamento_posto";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$aux_treinamento  = pg_result($res,0,treinamento);
		$tecnico_nome     = pg_result($res,0,tecnico_nome);
		$tecnico_rg       = pg_result($res,0,tecnico_rg);
		$tecnico_cpf      = pg_result($res,0,tecnico_cpf);
		$tecnico_fone     = pg_result($res,0,tecnico_fone);
		$posto            = pg_result($res,0,posto);
		$nome             = pg_result($res,0,nome);
		$email            = pg_result($res,0,email);
		$codigo_posto     = pg_result($res,0,codigo_posto);
	}

	$chave1 = md5($posto);
	$chave2 = md5($aux_treinamento_posto);


	$sql=  "SELECT  titulo                            ,
			TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio,
			TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')    AS data_fim
			FROM tbl_treinamento WHERE treinamento = $aux_treinamento";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$titulo      = pg_result($res,0,titulo)     ;
		$data_inicio = pg_result($res,0,data_inicio);
		$data_fim    = pg_result($res,0,data_fim)   ;
	}

	//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO

	$email_origem  = "verificacao@telecontrol.com.br";
	$email_destino = "$email";
	$assunto       = "Confirmação de Presença no Treinamento";

	$corpo.= "Titulo: $titulo <br>\n";
	$corpo.= "Data Inicío: $data_inicio<br> \n";
	$corpo.= "Data Termino: $data_fim <p>\n";

	$corpo.="<br>Você recebeu esse email para confirmar a inscrição do técnico.\n";
	$corpo.="<br>Nome: $tecnico_nome \n";
	$corpo.="<br>RG:$tecnico_rg \n";
	$corpo.="<br>CPF: $tecnico_cpf \n";
	$corpo.="<br>Telefone de Contato: $tecnico_fone \n";
	$corpo.="<br>Email: $email\n";
	$corpo.="<br><br><a href='http://posvenda.telecontrol.com.br/assist/treinamento_confirmacao.php?key1=$chave1&key2=$posto&key3=$chave2&key4=$aux_treinamento_posto'>CLIQUE AQUI PARA CONFIRMAR PRESENÇA</a> \n\n";
	$corpo.="<br>Caso o link acima esteja com problema copie e cole este link em seu navegador: http://posvenda.telecontrol.com.br/assist/treinamento_confirmacao.php?key1=$chave1&key2=$posto&key3=$chave2&key4=$aux_treinamento_posto'\n\n";
	$corpo.="<br><br><br>Telecontrol\n";
	$corpo.="<br>www.telecontrol.com.br\n";
	$corpo.="<br>_______________________________________________\n";
	$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";


	$body_top = "MIME-Version: 1.0\r\n";
	$body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
	$body_top .= "From: $email_origem\r\n";

	if ( @mail($email_destino, stripslashes($assunto), $corpo, $body_top ) ){
		$msg = "Foi enviado um email para o posto $nome no email $email";
	}else{
		$msg = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";

	}

}

?>
<script language="javascript" src="js/jquery.js"></script>
<script language="javascript" src="js/jquery.maskedinput.js"></script>
<!--<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>-->
<script language='javascript' src='../ajax.js'></script>
<script language='javascript'>
$().ready(function(){


	$("#regiao,#estado").change(function(){
		var estados = $(this).val();
		var estados_campo = $("#estado").val();
		var regiao_campo = $("#regiao").val();
		if(estados_campo == ""){
			if (regiao_campo != "") {
				estados = regiao_campo;
			}
		}
		//if(estados == "PR, RS, SC" || estados == "AL, BA, CE, MA, PB, PE, PI, RN, SE" || estados == "ES, MG, RJ, SP" || estados == "DF, GO, MT, MS" || estados == "AC, AP, AM, PA, RO, RR, TO" || estados == ""){
			$.ajax({url: "ajax_treinamento.php",data : {
				estados : estados,
				ajax : "sim",
				acao: "consulta_estados"
			}}).done(function(response){
				response = JSON.parse(response);


				var option = "<option value=''>Selecione um estado</option>";
				$.each(response,function(index,obj){
					option += "<option value='"+obj.cod_estado+"'>"+obj.estado+"</option>";
				})

				$("#estado").html(option);
			});
		//}

		$.ajax({url: "ajax_treinamento.php",data : {
				estados : estados,
				ajax : "sim",
				acao: "consulta_cidades"
		}}).done(function(response){
			response = JSON.parse(response);
			var option = "<option value=''>Selecione uma cidade</option>";
			if (!response.messageError) {
				$.each(response,function(index,obj){
					option += "<option value='"+obj.cod_cidade+"'>"+obj.cidade+"</option>";
				})
			// }else{
			// 	$.each(response,function(index,obj){
			// 		option += "<option value='"+obj.cod_cidade+"'>"+obj.cidade+"</option>";
			// 	})
			}
			$("#cidade").html(option);
		});
	});

	$('#img_help').click(function(){
		alert("Cardíaca, hipertensíva, traumatismo, infecto-contagiosa, etc.");
	})

	<?php
	if($login_fabrica != 117){
		?>
		if ((email.length != 0) && ((email.indexOf("@") < 1) || (email.indexOf('.') < 7))){
			alert ( "O e-mail " + email + " está incorreto!");
		}
		<?php
	}
	?>


});
function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

var http_forn = new Array();


function gravar_treinamento(formulatio) {

	var acao='cadastrar';

	url = "ajax_treinamento.php?ajax=sim&acao="+acao;
	for( var i = 0 ; i < formulatio.length; i++ ){
		if (formulatio.elements[i].type=='text' || formulatio.elements[i].type=='select-one' || formulatio.elements[i].type=='textarea' || formulatio.elements[i].type=='hidden' ||  formulatio.elements[i].type=='checkbox') {
			if(formulatio.elements[i].type == "checkbox"){
				 if(formulatio.elements[i].checked == true){
				 	url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
				 }
			}else{
				url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
			}

		}
	}

	var com = document.getElementById('erro');

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);
	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4)
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
			{
				var response = http_forn[curDateTime].responseText.split("|");

				if (response[0]=="ok"){
					com.style.backgroundColor = "green";
					com.innerHTML = response[1];
					for( var i = 0 ; i < formulatio.length; i++ ){
						if (formulatio.elements[i].type=='text' || formulatio.elements[i].type=='select-one' || 	formulatio.elements[i].type=='textarea' || formulatio.elements[i].type=='hidden'){
							formulatio.elements[i].value = "";
						}
					}
					formulatio.bt_cad_forn.value='Gravar';
					com.style.visibility = "block";
					mostrar_treinamento('dados');

				}
				if (response[0]=="0"){
					// posto ja cadastrado
					com.innerHTML = response[1];
					com.style.visibility = "visible";
					formulatio.bt_cad_forn.value='Gravar';
				}
				if (response[0]=="1"){
					// dados incompletos
					com.innerHTML = response[1];
					com.style.visibility = "visible";
					formulatio.bt_cad_forn.value='Gravar';
					formulatio.bt_cad_forn.value='Gravar';
				}
				if (response[0]=="2"){
					// erro inesperado
					alert("Ocorreu um erro inesperado no momento da gravação:\n\n"+response[1]);
					formulatio.bt_cad_forn.value='Gravar';
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}

function gravar_treinamento_makita(formulatio) {

	var acao='cadastrar_makita';

	url = "ajax_treinamento.php?ajax=sim&acao="+acao;
	for( var i = 0 ; i < formulatio.length; i++ ){
		if (formulatio.elements[i].type=='text' || formulatio.elements[i].type=='select-one' || formulatio.elements[i].type=='textarea' || formulatio.elements[i].type=='hidden'  ) {
			url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
		}
	}

	var com = document.getElementById('erro');

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);
	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4)
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					com.style.backgroundColor = "green";
					com.innerHTML = response[1];
					for( var i = 0 ; i < formulatio.length; i++ ){
						if (formulatio.elements[i].type=='text' || formulatio.elements[i].type=='select-one' || 	formulatio.elements[i].type=='textarea' || formulatio.elements[i].type=='hidden'){
							formulatio.elements[i].value = "";
						}
					}
					formulatio.bt_cad_forn.value='Gravar';
					com.style.visibility = "visible";
					mostrar_treinamento('dados');

				}
				if (response[0]=="0"){
					// posto ja cadastrado
					com.innerHTML = response[1];
					com.style.backgroundColor = "red";
					com.style.visibility = "visible";
					formulatio.bt_cad_forn.value='Gravar';
				}
				if (response[0]=="1"){
					// dados incompletos
					com.innerHTML = response[1];
					com.style.backgroundColor = "red";
					com.style.visibility = "visible";
					formulatio.bt_cad_forn.value='Gravar';
					formulatio.bt_cad_forn.value='Gravar';
				}
				if (response[0]=="2"){
					// erro inesperado
					alert("Ocorreu um erro inesperado no momento da gravação:\n\n"+response[1]);
					formulatio.bt_cad_forn.value='Gravar';
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}


function mostrar_treinamento(componente) {
	var com = document.getElementById(componente);
	var acao='ver';

	url = "ajax_treinamento.php?ajax=sim&acao="+acao;

	com.innerHTML   ="Carregando<br><img src='imagens/carregar2.gif'>";

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);

	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4)
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					com.innerHTML   = response[1];

				}
				if (response[0]=="0"){
					// posto ja cadastrado
					alert(response[1]);
				}
				if (response[0]=="1"){
					// dados incompletos
					alert("Campos incompletos:\n\n"+response[1]);
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}

function ativa_desativa(treinamento,id) {

	var com = document.getElementById("ativo_"+id);
	var img = document.getElementById("img_ativo_"+id);

	com.innerHTML   ="Espere...";

	var acao='ativa_desativa';

	url = "ajax_treinamento.php?ajax=sim&acao="+acao+"&treinamento="+treinamento+"&id="+id;

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);

	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4)
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					com.innerHTML   = response[1];
					img.src = "imagens_admin/status_"+response[2]+".gif";

				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}
function ativa_desativa_tecnico(treinamento,id) {

	var com = document.getElementById("tec_ativo_"+id);
	var img = document.getElementById("tec_img_ativo_"+id);

	com.innerHTML   ="Espere...";

	var acao='ativa_desativa_tecnico';

	url = "ajax_treinamento.php?ajax=sim&acao="+acao+"&treinamento_posto="+treinamento+"&id="+id;

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);

	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4)
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					com.innerHTML   = response[1];
					img.src = "imagens_admin/status_"+response[2]+".gif";

				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}
function ativa_desativa_participou(treinamento,id) {

	var com = document.getElementById("participou_"+id);
	var img = document.getElementById("participou_img_"+id);

	com.innerHTML   ="Espere...";

	var acao='ativa_desativa_participou';

	url = "ajax_treinamento.php?ajax=sim&acao="+acao+"&treinamento_posto="+treinamento+"&id="+id;

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);

	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4)
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					com.innerHTML   = response[1];
					img.src = "imagens_admin/status_"+response[2]+".gif";

				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}
function ativa_desativa_hotel(treinamento,id) {

	var com = document.getElementById("hotel_"+id);
	var img = document.getElementById("hotel_img_"+id);

	com.innerHTML   ="Espere...";

	var acao='ativa_desativa_hotel';

	url = "ajax_treinamento.php?ajax=sim&acao="+acao+"&treinamento_posto="+treinamento+"&id="+id;

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);

	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4)
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					com.innerHTML   = response[1];
					img.src = "imagens_admin/status_"+response[2]+".gif";

				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}

function retornaTreinamento (http , componente ) {
	com = document.getElementById(componente);
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = results[1];
				}else{
					alert ('Erro ao abrir Treinamento' );
				}
			}
		}
	}
}

function pegaTreinamento (treinamento,dados,cor) {
	url = "ajax_treinamento.php?ajax=sim&acao=detalhes&treinamento=" + escape(treinamento)+"&cor="+escape(cor) ;

	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaTreinamento (http , dados) ; } ;
	http.send(null);
}

function MostraEsconde(dados,treinamento,imagem,cor){
	if (document.getElementById){
		// this is the way the standards work
		var style2 = document.getElementById(dados);
		var img    = document.getElementById(imagem);
		if (style2.style.display){
			style2.style.display = "";
			style2.innerHTML   ="";
			img.src='imagens/mais.gif';

        }else{
			style2.style.display = "block";
			img.src='imagens/menos.gif';
			pegaTreinamento(treinamento,dados,cor);
		}

	}
}

function hint( sMessage ) {
  document.getElementById("display_hint").innerHTML = sMessage;
}

</script>


<?
    include "javascript_pesquisas.php";
    include "javascript_calendario_new.php";
    include '../js/js_css.php';
?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datepick({startdate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
		<?php if($login_fabrica != 117){
			?>
			$("#tecnico_fone").maskedinput("(99)9999-9999");
			$("#tecnico_celular").maskedinput("(99)9999-9999");
			<?php
		} ?>

		$("#qtde").mask("99");
	});
</script>
<?
$treinamento       = $_GET["treinamento"];
$treinamento_posto = $_GET["treinamento_posto"];
//if(strlen($msg)>0) echo "<center><font color=blue>$msg</font></center>";
if(strlen($treinamento_posto)>0 ){

			$sql = "SELECT
			tbl_treinamento_posto.treinamento_posto                                     ,
			tbl_tecnico.nome as tecnico_nome                                          ,
			tbl_tecnico.cpf as tecnico_cpf                                           ,
			tbl_treinamento_posto.ativo                                                 ,
			tbl_treinamento_posto.participou                                            ,
			tbl_tecnico.rg as tecnico_rg                                            ,
			tbl_tecnico.telefone as tecnico_fone                                          ,
			tbl_treinamento_posto.confirma_inscricao                                    ,
			TO_CHAR(tbl_treinamento_posto.data_inscricao,'DD/MM/YYYY') AS data_inscricao,
			TO_CHAR(tbl_treinamento_posto.data_inscricao,'HH24:MI:SS') AS hora_inscricao,
			tbl_posto.nome                                             AS posto_nome    ,
			tbl_posto.estado                                                            ,
			tbl_posto_fabrica.codigo_posto                                              ,
			tbl_promotor_treinamento.nome                                               ,
			tbl_treinamento.titulo,
			tbl_treinamento.data_inicio,
			tbl_treinamento.data_fim

		FROM tbl_treinamento_posto
		JOIN tbl_treinamento                    USING(treinamento)
		LEFT JOIN      tbl_promotor_treinamento USING(promotor_treinamento)
		LEFT JOIN      tbl_posto                USING(posto)
		LEFT JOIN      tbl_posto_fabrica        ON tbl_posto_fabrica.posto     = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN      tbl_admin          ON tbl_treinamento_posto.admin = tbl_admin.admin
		LEFT JOIN      tbl_tecnico          ON tbl_treinamento_posto.tecnico = tbl_tecnico.tecnico
		WHERE tbl_treinamento_posto.treinamento_posto = $treinamento_posto
		AND   tbl_treinamento_posto.ativo IS TRUE
		ORDER BY tbl_posto.nome" ;

	$res = pg_exec ($con,$sql);
	if(pg_numrows($res)>0){
		$treinamento_posto = pg_result($res,0,treinamento_posto);
		$titulo            = pg_result($res,0,titulo);
		$data_inicio       = pg_result($res,0,data_inicio);
		$data_fim          = pg_result($res,0,data_fim);
		$tecnico_nome      = pg_result($res,0,tecnico_nome);
		$tecnico_rg        = pg_result($res,0,tecnico_rg);
		$tecnico_cpf       = pg_result($res,0,tecnico_cpf);
		$tecnico_fone      = pg_result($res,0,tecnico_fone);
		?>

		<form name='tecnico' method='POST' ACTION='<?php echo $PHP_SELF;?>'>
			<input type='hidden'name='treinamento_posto' value='$treinamento_posto'>
			<input type='hidden'name='altera_tecnico' value='$treinamento_posto'>
			<table width='700' class='formulario'  border='0' cellpadding='5' cellspacing='1' align='center'>
				<tr>
					<td class='titulo_tabela' ><b>Alterar Dados do Técnico</b></td>
					</tr>
					<tr>
					<td><b><?php echo $titulo;?> - <?php echo $data_inicio;?> a <?php echo $data_fim;?></b></td>
				</tr>


		<tr>
			<td valign='bottom'>
				<table width='100%' border='0' cellspacing='1' cellpadding='2' >
					<tr>
						<td width='150px'>&nbsp;</td>
						<td align='left' colspan=2>Nome do Técnico<br>
						<input type='text' name='tecnico_nome' id='tecnico_nome' size='60' maxlength='100' class='frm' value='<?php echo $tecnico_nome;?>'>
						</td>
						<td width='10'>&nbsp;</td>
					</tr>

					<?php
					if($login_fabrica == $makita){
						echo "<tr>";
						echo "<td width='150px'>&nbsp;</td>";
						echo "<td align='left' colspan=2>E-mail do Técnico<br/>";
						echo "<input type='text' name='tecnico_email' id='tecnico_email' size='60' maxlength='50' class='frm' value='{$tecnico_email}'>";
				  		echo "</td>";
				  		echo "<td width='10'>&nbsp;</td>";
				  		echo "</tr>";
					}
					?>


					<tr>
						<td width='150px'>&nbsp;</td>
						<td align='left' nowrap valign='top'>RG do Técnico<br>
						<input type='text' name='tecnico_rg' id='tecnico_rg' size='15' maxlength='14' class='frm' value='<?php echo $tecnico_rg;?>'>
						</td>
						<?php
							if($login_fabrica == $makita){
								echo  "<td align='left' nowrap valign='top'>Nº Calçado<br/>";
								echo  "<select name='tecnico_calcado' id='tecnico_calcado' class='frm'>
								<option value=''>Selecione</option>";
									$option_tipo.="<option value='44'"; if($tecnico_calcado == 44) $option_tipo.="selected"; $option_tipo.="> 44 </option>" ;
									$option_tipo.="<option value='43'"; if($tecnico_calcado == 43) $option_tipo.="selected"; $option_tipo.="> 43 </option>" ;
									$option_tipo.="<option value='42'"; if($tecnico_calcado == 42) $option_tipo.="selected"; $option_tipo.="> 42 </option>" ;
									$option_tipo.="<option value='41'"; if($tecnico_calcado == 41) $option_tipo.="selected"; $option_tipo.="> 41 </option>" ;
									$option_tipo.="<option value='40'"; if($tecnico_calcado == 40) $option_tipo.="selected"; $option_tipo.="> 40 </option>" ;
									$option_tipo.="<option value='39'"; if($tecnico_calcado == 39) $option_tipo.="selected"; $option_tipo.="> 39 </option>" ;
									$option_tipo.="<option value='38'"; if($tecnico_calcado == 38) $option_tipo.="selected"; $option_tipo.="> 38 </option>" ;
									$option_tipo.="<option value='37'"; if($tecnico_calcado == 37) $option_tipo.="selected"; $option_tipo.="> 37 </option>" ;
									$option_tipo.="<option value='36'"; if($tecnico_calcado == 36) $option_tipo.="selected"; $option_tipo.="> 36 </option>" ;
									$option_tipo.="<option value='35'"; if($tecnico_calcado == 35) $option_tipo.="selected"; $option_tipo.="> 35 </option>" ;
									$option_tipo.="<option value='34'"; if($tecnico_calcado == 34) $option_tipo.="selected"; $option_tipo.="> 34 </option>" ;
								$option_tipo.= "</select></td>";
								echo $option_tipo;
							}
						?>

						<td width='10'>&nbsp;</td>
					</tr>

					<tr>
						<td width='150px'>&nbsp;</td>
						<td align='left' nowrap valign='top'>CPF do Técnico<br>
						<input type='text' name='tecnico_cpf' id='tecnico_cpf' size='15' maxlength='14' class='frm' value='<?php echo $tecnico_cpf;?>'>
						</td>
						<?php
							if($login_fabrica == $makita){
								$option_tipo.= "<td align='left' nowrap valign='top'>Tipo Sanguíneo<br/>";
								$option_tipo.= "<select name='tecnico_tipo_sanguineo' id='tecnico_tipo_sanguineo'  class='frm'>";
								$option_tipo.= "<option value=''>Selecione</option>";
									$option_tipo.="<option value='"; $tecnico_tipo_sanguineo == "a1" ? $option_tipo.="{$tecnico_tipo_sanguineo}' selected" : $option_tipo.="a1'" ; $option_tipo.="> A+ </option>";
									$option_tipo.="<option value='"; $tecnico_tipo_sanguineo == "a2" ? $option_tipo.="{$tecnico_tipo_sanguineo}' selected" : $option_tipo.="a2'" ; $option_tipo.="> A- </option>" ;
									$option_tipo.="<option value='"; $tecnico_tipo_sanguineo == "b1" ? $option_tipo.="{$tecnico_tipo_sanguineo}' selected" : $option_tipo.="b1'" ; $option_tipo.="> B+ </option>" ;
									$option_tipo.="<option value='"; $tecnico_tipo_sanguineo == "b2" ? $option_tipo.="{$tecnico_tipo_sanguineo}' selected" : $option_tipo.="b2'" ; $option_tipo.="> B- </option>" ;
									$option_tipo.="<option value='"; $tecnico_tipo_sanguineo == "ab1"? $option_tipo.="{$tecnico_tipo_sanguineo}' selected" : $option_tipo.="ab1'"; $option_tipo.="> AB+</option>";
									$option_tipo.="<option value='"; $tecnico_tipo_sanguineo == "ab2"? $option_tipo.="{$tecnico_tipo_sanguineo}' selected" : $option_tipo.="ab2'"; $option_tipo.="> AB-</option>";
									$option_tipo.="<option value='"; $tecnico_tipo_sanguineo == "o1" ? $option_tipo.="{$tecnico_tipo_sanguineo}' selected" : $option_tipo.="o1'" ; $option_tipo.="> O+ </option>" ;
									$option_tipo.="<option value='"; $tecnico_tipo_sanguineo == "o2" ? $option_tipo.="{$tecnico_tipo_sanguineo}' selected" : $option_tipo.="o2'" ; $option_tipo.="> O- </option>" ;
								$option_tipo.= "</select></td>";
								print $option_tipo;
							}
						?>
						<td width='10'>&nbsp;</td>
					</tr>
					<tr>
						<td width='150px'>&nbsp;</td>
						<td align='left' nowrap valign='top'>Telefone Contato<br>
						<input type='text' name='tecnico_fone' id='tecnico_fone' size='15' maxlength='14' class='frm' value='<?php echo $tecnico_fone;?>'>
						</td>
						<?php
							if($login_fabrica == $makita){
								echo  "<td align='left' nowrap valign='top'>Celular do Técnico <br/>";
								echo  "<input type='text' name='tecnico_celular' id='tecnico_celular' size='25' maxlength='13' class='frm' value='{$tecnico_celular}'>";
						  		echo  "</td>";
							}
						?>
						<td width='10'>&nbsp;</td>
					</tr>
					<?php
					if($login_fabrica == $makita){
						echo "<tr>";
						echo "<td width='150px'>&nbsp;</td>";
						echo "<td align='left' colspan=2>O Participante sofreu ou sofre de alguma doença?<img src='imagens/help.png' name='img_help' id='img_help' class='img_help' title=' (Cardíaca, hipertensíva, traumatismo, infecto-contagiosa, etc.)' onClick='javascript:img_info();'/><br/>";
						echo "<input type='text' name='tecnico_doencas' id='tecnico_doencas' size='59' maxlength='90' class='frm' value='{$tecnico_doencas}'>";
				  		echo "</td>";
				  		echo "<td width='10'>&nbsp;</td>";
				  		echo "</tr>";

				  		echo "<tr>";
						echo "<td width='150px'>&nbsp;</td>";
						echo "<td align='left' colspan=2>Toma algum medicamento controlado? Qual?<br/>";
						echo "<input type='text' name='tecnico_medicamento' id='tecnico_medicamento' size='59' maxlength='90' class='frm' value='{$tecnico_medicamento}'>";
				  		echo "</td>";
				  		echo "<td width='10'>&nbsp;</td>";
				  		echo "</tr>";

				  		echo "<tr>";
						echo "<td width='150px'>&nbsp;</td>";
						echo "<td align='left' colspan=2>É portador de alguma necessidade especial? Qual?<br/>";
						echo "<input type='text' name='tecnico_necessidade' id='tecnico_necessidade' size='59' maxlength='90' class='frm' value='{$tecnico_necessidade_especial}'>";
				  		echo "</td>";
				  		echo "<td width='10'>&nbsp;</td>";
				  		echo "</tr>";

					}
					?>
				</table>
			<input type='submit' value='Gravar'>
			<input type='hidden' name='treinamento_posto_variavel' value='<?php echo $treinamento_posto;?>'>
			</td>
		</tr>
		</table>
		</form>
		<?php
			include "rodape.php"
		?>
		<?php
		exit;
	}
}
if((strlen($treinamento)>0)&&($login_fabrica != $makita or $login_fabrica != 117)){
	$sql = "SELECT tbl_treinamento.treinamento                                        ,
				tbl_treinamento.titulo                                                ,
				tbl_treinamento.descricao                                             ,
				tbl_treinamento.ativo                                                 ,
				tbl_treinamento.vagas                                                 ,
				tbl_treinamento.linha                                                 ,
				tbl_treinamento.familia                                               ,
				TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio      ,
				TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')    AS data_fim         ,
				tbl_treinamento.adicional,
				tbl_treinamento.local,
				tbl_treinamento.cidade,
				tbl_treinamento.palestrante,
				tbl_treinamento.visivel_portal
		FROM tbl_treinamento
		WHERE treinamento = $treinamento
		AND fabrica = $login_fabrica";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$treinamento  = trim(pg_result($res,0,treinamento));
		$titulo       = trim(pg_result($res,0,titulo))     ;
		$descricao    = trim(pg_result($res,0,descricao))  ;
		$ativo        = trim(pg_result($res,0,ativo))      ;
		$data_inicial = trim(pg_result($res,0,data_inicio));
		$data_final   = trim(pg_result($res,0,data_fim))   ;
		$linha        = trim(pg_result($res,0,linha))      ;
		$familia      = trim(pg_result($res,0,familia))    ;
		$qtde         = trim(pg_result($res,0,vagas))      ;
		$adicional    = trim(pg_result($res,0,adicional))      ;
		$local    = trim(pg_result($res,0,local))      ;
		$cidade    = trim(pg_result($res,0,cidade))      ;
		$visivel_portal    = trim(pg_result($res,0,visivel_portal));
		$palestrante    = trim(pg_result($res,0,palestrante));

		if($cidade != ""){

			$sql = "SELECT cidade,nome,estado from tbl_cidade where cidade = $cidade;";

			$res = pg_exec($con,$sql);
			if(pg_num_rows($res) > 0){
				$cidade = pg_result($res,0,cidade);
				$nome_cidade = pg_result($res,0,nome);
				$estado_cidade = pg_result($res,0,estado);
			}else{
				$cidade = "";
				$nome_cidade = "";
				$estado_cidade = "";
			}



		}

	}
}elseif((strlen($treinamento)>0)&&($login_fabrica == $makita or $login_fabrica == 117)){

		$sql = "SELECT tbl_treinamento.treinamento                                    ,
				tbl_treinamento.titulo                                                ,
				tbl_treinamento.descricao                                             ,
				tbl_treinamento.ativo                                                 ,
				tbl_treinamento.vagas                                                 ,
				tbl_treinamento.linha                                                 ,
				tbl_treinamento.local                                                 ,
				TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio      ,
				TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')    AS data_fim         ,
				tbl_treinamento.adicional											  ,
				tbl_treinamento.treinamento_tipo 									  ,
				tbl_treinamento.visivel_portal,
				tbl_treinamento.palestrante
		FROM tbl_treinamento
		WHERE treinamento = $treinamento
		AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		$treinamento  = trim(pg_result($res,0,'treinamento'));
		$titulo       = trim(pg_result($res,0,'titulo'))     ;
		$descricao    = trim(pg_result($res,0,'descricao'))  ;
		$ativo        = trim(pg_result($res,0,'ativo'))      ;
		$data_inicial = trim(pg_result($res,0,'data_inicio'));
		$data_final   = trim(pg_result($res,0,'data_fim'))   ;
		$linha        = trim(pg_result($res,0,'linha'))      ;
		$qtde         = trim(pg_result($res,0,'vagas'))      ;
		$adicional    = trim(pg_result($res,0,'adicional'))  ;
		$familia      = trim(pg_result($res,0,'treinamento_tipo' ))    ;
		$local      = trim(pg_result($res,0,'local' ))    ;
		$palestrante    = trim(pg_result($res,0,'palestrante'));
		$visivel_portal      = trim(pg_result($res,0,'visivel_portal' ))    ;

	}
}


?>

<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<?
echo "<input type='hidden' name='treinamento' id='treinamento' value='$treinamento'>";
?>
<center><div id='erro' style="visibility:hidden; opacity:.85; width:700px; font:bold 16px Arial;" class='Erro'></div></center>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando' width='150'></div>
<table width='700' class='formulario' border='0' cellpadding='5' cellspacing='1' align='center'>
	<? if(strlen($msg)>0){ ?>
		<tr align="center" bgcolor="#FF0000" style="font:bold 16px Arial; color:#FFFFFF;">
			<td><? echo $msg; ?></td>
		</tr>
	<? }elseif (strlen($msg_sucesso)>0){
		echo "<tr align='center' bgcolor='#008000' style='font:bold 16px Arial; color:#FFFFFF;'>";
		echo "<td>{$msg_sucesso}</td></tr>";
	 } ?>

	<tr>

	<tr>
		<td class='titulo_tabela'>Cadastro de Treinamento</td>
	</tr>
	<tr>
		<td valign='bottom'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' >
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap>Tema</td>
					<td align='left' colspan='3'>
						<input type="text" name="titulo" id='titulo' size="60" maxlength="70" class='frm' value="<? if (strlen($titulo) > 0) echo $titulo; ?>">
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right'nowrap>Data Inicial</td>
					<td align='left'>
						<input type="text" name="data_inicial" id='data_inicial' size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">

					</td>
					<td align='right' nowrap>Data Final</td>
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">

					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<?php
				if ($login_fabrica == 117) {
				?>
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap>Palestrante</td>
					<td align='left' colspan='3'>
						<input type="text" name="palestrante" id='palestrante' size="60" maxlength="60" class='frm' value="<? if (strlen($palestrante) > 0) echo $palestrante; ?>">
					</td>
					<td width="10">&nbsp;</td>
				</tr>

				<?php
				}

				?>
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right'>Linha</td>
					<td align='left'>
						<?

					$sql = "SELECT  *
							FROM    tbl_linha
							WHERE   tbl_linha.fabrica = $login_fabrica
							ORDER BY tbl_linha.nome;";
					$res = pg_exec ($con,$sql);

					if (pg_numrows($res) > 0) {
						echo "<select name='linha' id='linha' class='frm'>\n";
						echo "<option value=''>ESCOLHA</option>\n";

						for ($x = 0 ; $x < pg_numrows($res) ; $x++){
							$aux_linha = trim(pg_result($res,$x,linha));
							$aux_nome  = trim(pg_result($res,$x,nome));

							echo "<option value='$aux_linha'";
							if ($linha == $aux_linha){
								echo " SELECTED ";
								$mostraMsgLinha = "<br> da LINHA $aux_nome";
							}
							echo ">$aux_nome</option>\n";
						}
						echo "</select>\n";
					}
					?>

					</td>
					<td align='right'>Família</td>
					<td align='left'>
					<?


					if($login_fabrica != $makita or $login_fabrica != 117){
						$sql = "SELECT  *
								FROM    tbl_familia
								WHERE   tbl_familia.fabrica = $login_fabrica
								ORDER BY tbl_familia.descricao;";

						$res = pg_exec ($con,$sql);

						if (pg_numrows($res) > 0) {
							echo "<select name='familia' id='familia' class='frm'>\n";
							echo "<option value=''>ESCOLHA</option>\n";

							for ($x = 0 ; $x < pg_numrows($res) ; $x++){
								$aux_familia = trim(pg_result($res,$x,familia));
								$aux_nome  = trim(pg_result($res,$x,descricao));

								echo "<option value='$aux_familia'";
								if ($familia == $aux_familia){
									echo " SELECTED ";
									$mostraMsgLinha = "<br> da FAMILIA $aux_nome";
								}
								echo ">$aux_nome</option>\n";
							}
							echo "</select>\n";
						}

					}else{
						$sql = "SELECT * FROM tbl_treinamento_tipo WHERE fabrica = {$login_fabrica} ORDER BY nome";
						$res = pg_query($con,$sql);
						if (pg_numrows($res) > 0) {
							echo "<select name='familia' id='familia' class='frm'>\n";
							echo "<option value=''>ESCOLHA</option>\n";


							while ($linha = pg_fetch_array($res)){
								if($familia != $linha['treinamento_tipo']){
									echo "<option value={$linha['treinamento_tipo']}>{$linha['nome']}</option>\n";
								}else{
									echo "<option value={$linha['treinamento_tipo']} selected='selected' >{$linha['nome']}</option>\n";
								}
							}

							echo "</select>\n";
						}

					}

					?>
					</td>
					<td width="10">&nbsp;</td>
				</tr>

				<tr>
					<td width="10">&nbsp;</td>
					<td align='right'nowrap>Vagas</td>
					<td align='left'>
						<input type="text" id="qtde" name="qtde" size="10" maxlength="2" class='frm' value="<? if (strlen($qtde) > 0) echo $qtde; ?>">
					</td>
					<?php
					if($login_fabrica == 117){
						?>
						<td align='right' nowrap>Visualizar no portal</td>
						<td align='left'>
							<?php


							if(strtolower($visivel_portal) == 't'){
								$checkbox = "checked";
							}else{
								$checkbox = "";
							}
							?>
							<input type="checkbox" name="visivel_portal"  value="true" <?php echo $checkbox; ?> class='frm'>
						</td>
						<?php
					}
					?>


					<td width="10">&nbsp;</td>
				</tr>

				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap valign='top'>Informações Adicionais</td>
					<td align='left' colspan='3'>

						<input type="text" name="adicional" size="60" maxlength="200" class='frm' value="<? echo $adicional; ?>">
					<DIV ID="display_hint">Digite aqui as informações adicionais que o posto deve <br>fornecer ao inscrever um treinando. Ex: Revenda </DIV>
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="4">&nbsp;</td>
				</tr>
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap valign='top'>Descrição</td>
					<td align='left' colspan='3'>
					<TEXTAREA NAME='descricao' ROWS='7' COLS='60' class='frm'><?if (strlen($descricao) > 0) echo $descricao; ?></TEXTAREA>
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<?php if ($login_fabrica == $makita or $login_fabrica == 117){?>

		  		<tr>
		  			<td width="10">&nbsp;</td>
		  			<td align="right">
		  				Local
		  			</td>
		  			<td align="left" colspan='3'>
		  				<input type="text" name="local" id="local" value="<?php echo $local ?>" class='frm' style="width:375px;margin-left:2px">
		  			</td>
		  			<td width="10">&nbsp;</td>
		  		</tr>
		  			<?php if($login_fabrica == 117){ ?>
			  		<tr>
			  			<td width="10">&nbsp;</td>
			  			<td align="right">
			  				Região
			  			</td>
			  			<td align="left">
			  				<select id="regiao" name="regiao" onchange="" class='frm'>
			  					<option value=''>Selecione uma região</option>
			  					<?php
			  					$sql = "SELECT regiao,descricao,estados_regiao from tbl_regiao where fabrica = $login_fabrica and ativo is true";
			  					//echo $sql;exit;
			  					$res = pg_exec($con,$sql);

			  					for($i=0;$i<pg_num_rows($res);$i++){
			  						if(strstr(pg_result($res,$i,estados_regiao), $estado_cidade)){
			  							$selected = "selected";
			  							$estados_regiao_combo = pg_result($res,$i,estados_regiao);
			  						}else{
			  							$selected = "";
			  						}
			  						echo "<option $selected value='".pg_result($res,$i,estados_regiao)."'>".pg_result($res,$i,descricao)." - (".pg_result($res,$i,estados_regiao).")</option>";
			  					}
			  					?>
			  				</select>
			  			</td>
			  			<td align="right">
			  				Estado
			  			</td>
			  			<td align="left" >
			  				<select id="estado" name="estado" class='frm'>
			  					<option value=''>Selecione um estado</option>
			  					<?php

			  					if($estados_regiao_combo != ""){
			  						switch ($estados_regiao_combo) {
			  							case "PR, RS, SC":
											$estados = array("PR" => "Paraná", "RS" => "Rio Grande do Sul", "SC" => "Santa Catarina'");
											break;

										case "AL, BA, CE, MA, PB, PE, PI, RN, SE":
											$estados = array("AL" => "Alagoas", "BA" => "Bahia", "CE" => "Ceará", "MA" => "Maranhão", "PB" => "Paraíba", "PE" => "Pernambuco", "PI" => "Piaui", "RN" => "Rio Grande do Norte", "SE" => "Sergipe");
											break;

										case "ES, MG, RJ, SP":
											$estados = array("ES" => "Espirito Santo", "MG" => "Minas Gerais", "RJ" => "Rio de Janeiro", "SP" => "São Paulo");
											break;

										case "DF, GO, MT, MS":
											$estados = array("DF" => "Distrito Federal", "GO" => "Goiás", "MT" => "Mato Grosso", "MS" => "Mato Grosso do Sul");
											break;

										case "AC, AP, AM, PA, RO, RR, TO":
											$estados = array("AC" => "Acre", "AP" => "Amapá", "AM" => "Amazonas", "PA" => "Pará", "RO" => "Rondônia", "RR" => "Roraima", "TO" => "Tocantins");
											break;
			  						}
			  					}else{
				  					$estados = array(
				  						'AC' => 'Acre',
										'AL' => 'Alagoas',
										'AP' => 'Amapá',
										'AM' => 'Amazonas' ,
										'BA' => 'Bahia',
										'CE' => 'Ceará',
										'DF' => 'Distrito Federal' ,
										'GO' => 'Goiás' ,
										'ES' => 'Espirito Santo',
										'MA' => 'Maranhão',
										'MT' => 'Mato Grosso',
										'MS' => 'Mato Grosso do Sul',
										'MG' => 'Minas Gerais',
										'PA' => 'Pará',
										'PB' => 'Paraíba',
										'PR' => 'Paraná',
										'PE' => 'Pernambuco',
										'PI' => 'Piaui',
										'RJ' => 'Rio de Janeiro',
										'RN' => 'Rio Grande do Norte',
										'RS' => 'Rio Grande do Sul',
										'RO' => 'Rondônia',
										'RR' => 'Roraima',
										'SC' => 'Santa Catarina',
										'SE' => 'Sergipe',
										'SP' => 'São Paulo',
										'TO' => 'Tocantins'
							 		);
				  				}
				  				$selected = "";
						 		foreach ($estados as $key => $value) {
						 			if($key == $estado_cidade){
						 				$selected = "selected";
						 				$estado_gravado = $estado_cidade;
						 			}else{
						 				$selected = "";
						 			}
						 			echo "<option ".$selected." value='".$key."'>".$value."</option>";
						 		}
			  					?>
			  				</select>
			  			</td>
			  			<td width="10">&nbsp;</td>
			  		</tr>
			  		<tr>
			  			<td width="10">&nbsp;</td>
			  			<td align="right">
			  				Cidade
			  			</td>
			  			<td align="left">
			  				<select id="cidade" name="cidade" class='frm'>

			  					<option value=''>Selecione uma cidade</option>
			  					<?php

			  					if($estado_gravado != ""){
			  						$sql = "select distinct upper(trim(nome)) as cidade, cidade as cod_cidade from tbl_cidade where estado in('".$estado_gravado."') AND (cod_ibge is not null OR cep is not null) order by upper(trim(nome))";

									$res = pg_exec($con,$sql);
									for ($i=0; $i < pg_num_rows($res); $i++) {
										if(pg_result($res,$i,cod_cidade) == $cidade){
							 				$selected = "selected";
							 			}else{
							 				$selected = "";
							 			}
										echo "<option $selected value='".pg_result($res,$i,cod_cidade)."'>".pg_result($res,$i,cidade)."</option>";
									}

			  					}else if($cidade != ""){
			  						echo "<option selected value='".$cidade."'>".$nome_cidade."</option>";
			  					}

			  					 ?>
			  				</select>
			  			</td>
			  			<td width="10">&nbsp;</td>
			  		</tr>
		  			<?php } ?>

				<?}?>
			</table><br>
			<?php
			if($login_fabrica == $makita){
				echo "<INPUT TYPE='button' name='bt_cad_forn' id='bt_cad_forn' value='Gravar' onClick=\"if (this.value=='Gravando...'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_treinamento_makita(this.form);}\">";
			}else{
				echo "<INPUT TYPE='button' name='bt_cad_forn' id='bt_cad_forn' value='Gravar' onClick=\"if (this.value=='Gravando...'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_treinamento(this.form);}\">";
			}
			?>
		</td>
	</tr>
</table>
</FORM>

<br />

<div id='dados'></div>
<script type='text/javascript'>mostrar_treinamento('dados');</script>

<p>
<?php
	include "rodape.php"
?>
