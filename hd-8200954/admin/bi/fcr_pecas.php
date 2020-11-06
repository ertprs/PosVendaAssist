<?php
$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../admin/':'../');

include_once '../dbconfig.php';
include_once '../includes/dbconnect-inc.php';

if ($areaAdminCliente == true) {
    include 'autentica_admin.php';
    include_once '../funcoes.php';
} else {
    $admin_privilegios = "gerencia";
    include_once '../includes/funcoes.php';
    include '../autentica_admin.php';
    include "../monitora.php";
}

include_once '../../fn_traducao.php';

$meses = array(1 => traduz("Janeiro"), traduz("Fevereiro"), traduz("Março"), traduz("Abril"), traduz("Maio"), traduz("Junho"), traduz("Julho"), traduz("Agosto"), traduz("Setembro"), traduz("Outubro"), traduz("Novembro"), traduz("Dezembro"));

if ($btn_finalizar == 1) {

    if(strlen($_POST["mostra_peca"]) > 0)
        $mostra_peca = trim($_POST["mostra_peca"]);

    if(strlen($_POST["classificacao"]) > 0) $classificacao = trim($_POST["classificacao"]);

    if(strlen($_POST["linha"]) > 0) $linha = trim($_POST["linha"]);

    if(strlen($_POST["estado"]) > 0){
        $estado = trim($_POST["estado"]);

        switch($estado){
            case 'Norte':
                $consulta_estado = "AC','AP','AM','PA','RO','RR','TO";
                $mostraMsgEstado = "<br>na REGIÃO NORTE";
            break;

            case 'Nordeste':
                $consulta_estado = "AL','BA','CE','MA','PB','PE','PI','RN','SE";
                $mostraMsgEstado = "<br>na REGIÃO NORDESTE";
            break;

            case 'Centro_oeste':
                $consulta_estado = "DF','GO','MT','MS";
                $mostraMsgEstado = "<br>na REGIÃO CENTRO OESTE";
            break;

            case 'Sudeste':
                $consulta_estado = "ES','MG','RJ','SP";
                $mostraMsgEstado = "<br>na REGIÃO SUDESTE";
            break;

            case 'Sul':
                $consulta_estado = "PR','RS','SC";
                $mostraMsgEstado = "<br>na REGIÃO SUL";
            break;

            default: $consulta_estado = $estado;
            $mostraMsgEstado = "<br>no ESTADO $estado";
        }
    }

    if ($login_fabrica == 175){
        $componente_raiz = $_POST["componente_raiz"];
    }

    if($login_fabrica == 20 and $pais !='BR'){
        if(strlen($_POST["pais"]) > 0) $pais = trim($_POST["pais"]);
    }
    $tipo_os = trim($_POST['tipo_os']);

    $codigo_posto = "";
    if(strlen($_POST["codigo_posto"]) > 0) $codigo_posto = trim($_POST["codigo_posto"]);

    $exceto_posto = $_POST["exceto_posto"];
    $nao_produto = $_POST["nao_produto"];
    $nao_posto_interno = $_POST["nao_posto_interno"];

    $produto_referencia = trim($_POST['produto_referencia']);
    $produto_descricao  = trim($_POST['produto_descricao']) ;

    if ($login_fabrica == 94) {
        $peca_referencia = trim($_POST['peca_referencia']);
        $peca_descricao  = trim($_POST['peca_descricao']) ;

        $sql = "SELECT peca
                from tbl_peca
                where tbl_peca.fabrica = $login_fabrica
                and tbl_peca.referencia = '$peca_referencia'";
        $res = pg_exec($con,$sql);
        if(pg_numrows($res)>0){
            $peca = pg_result($res,0,peca);
        }
    }

    $multiplo           = trim($_POST['radio_qtde_produtos']);

    if(strlen($produto_referencia)>0 and strlen($produto_descricao)>0){
        $sql = "SELECT produto
                from tbl_produto
                join tbl_familia using(familia)
                where tbl_familia.fabrica = $login_fabrica
                and tbl_produto.referencia = '$produto_referencia'";
        $res = pg_exec($con,$sql);
        if(pg_numrows($res)>0){
            $produto = pg_result($res,0,produto);
        }
    }

    if (strlen($erro) == 0) {
        $fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
        if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
        if (strlen($erro) == 0)                    $aux_data_inicial = @pg_result ($fnc,0,0);
        else                                       $erro = "Data Inválida";
    }
    if (strlen($erro) == 0) {
        $fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
        if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
        if (strlen($erro) == 0)                    $aux_data_final = @pg_result ($fnc,0,0);
        else                                       $erro = "Data Inválida";
    }

    $replicar = $_POST['PickList'];



    /*
    * @author William Castro <william.castro@telecontrol.com>
    * HD-6750240
    * filtro por peças
    */
    
    $cond_12 = "";

    if ($login_fabrica == 120) {

        $pecas       = $_POST['PickListPeca'];

        $qtd_pecas   = trim($_POST['radio_qtde_pecas']);

        $array_pecas = array();

        $peca_lista  = array();

        if (count($pecas) > 0 && $qtd_pecas == 'muitos') {
            
            for ($i = 0; $i < count($pecas); $i++) {

                $peca = trim($pecas[$i]);
                    
                $sql = "SELECT DISTINCT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
                        FROM tbl_peca
                        JOIN tbl_lista_basica ON (tbl_lista_basica.peca = tbl_peca.peca) 
                        JOIN tbl_produto ON (tbl_produto.produto = tbl_lista_basica.produto) 
                        JOIN tbl_familia ON (tbl_familia.familia = tbl_produto.familia)
                        WHERE tbl_peca.fabrica = $login_fabrica
                        AND tbl_peca.referencia = '$peca'";
                    
                $res = pg_exec($con, $sql);

                if (pg_num_rows($res) > 0) {
                    
                    $multi_peca       = trim(pg_result($res, 0, peca));
                    $multi_referencia = trim(pg_result($res, 0, referencia));
                    $multi_descricao  = trim(pg_result($res, 0, descricao));

                    array_push($array_pecas, $multi_peca);
                    array_push($peca_lista, array($multi_peca, $multi_referencia, $multi_descricao));

                }
            }

            $string_pecas = "";
            
            $primeiro_valor = TRUE;
            
            foreach ($peca_lista as $value) {

                if ($primeiro_valor == FALSE) {
                    $string_pecas .= "," . $value[0];          
                } else {
                    $string_pecas .= $value[0];
                }

                $primeiro_valor = FALSE;
                
            }
   
            $cond_12 = " AND BI.peca IN ({$string_pecas})";

            $lista_pecas = implode($array_pecas,",");

        } else {

            $peca = $_POST['peca_referencia'];

            $sql = "SELECT DISTINCT tbl_peca.peca
                    FROM tbl_peca
                    JOIN tbl_lista_basica ON (tbl_lista_basica.peca = tbl_peca.peca) 
                    JOIN tbl_produto ON (tbl_produto.produto = tbl_lista_basica.produto) 
                    JOIN tbl_familia ON (tbl_familia.familia = tbl_produto.familia)
                    WHERE tbl_peca.fabrica = $login_fabrica
                    AND tbl_peca.referencia = '$peca'";
            
            $res = pg_exec($con, $sql);

            if (pg_num_rows($res) > 0) {

                $peca       = pg_result($res, 0, peca);
                
                $cond_12 = " AND BI.peca IN ({$peca})";

            }
        }
    }

    if (count($replicar)>0 and $multiplo == 'muitos'){ // HD 123856

        $array_produto = array();
        $produto_lista = array();

        for ($i=0;$i<count($replicar);$i++){
            $p = trim($replicar[$i]);
            if (strlen($p) > 0) {
                $sql = "SELECT  tbl_produto.produto,
                                tbl_produto.referencia,
                                tbl_produto.descricao
                    from tbl_produto
                    join tbl_familia using(familia)
                    where tbl_familia.fabrica = $login_fabrica
                    and tbl_produto.referencia = '$p'";
                $res = pg_exec($con,$sql);
                if(pg_numrows($res)>0){
                    $multi_produto    = trim(pg_result($res,0,produto));
                    $multi_referencia = trim(pg_result($res,0,referencia));
                    $multi_descricao  = trim(pg_result($res,0,descricao));
                    array_push($array_produto,$multi_produto);
                    array_push($produto_lista,array($multi_produto,$multi_referencia,$multi_descricao));
                }
            }
        }

        $lista_produtos = implode($array_produto,",");
    }
        
    $familia = $_POST['familia'];
    $revenda_cnpj = $_POST['revenda_cnpj'];
    $revenda_nome = strtoupper($_POST['revenda_nome']);
    if(!empty($revenda_cnpj)){
        $revenda_cnpj = str_replace('.','',$revenda_cnpj);
        $revenda_cnpj = str_replace('-','',$revenda_cnpj);
        $revenda_cnpj = str_replace('/','',$revenda_cnpj);
        $revenda_cnpj = str_replace(' ','',$revenda_cnpj);

        $sqlRev = "SELECT cnpj FROM tbl_revenda WHERE cnpj = '$revenda_cnpj'";
        $resRev = pg_query($con,$sqlRev);

        if(pg_numrows($resRev) == 0){
            $erro = traduz("Revenda não encontrada");
        }
    }

    if (strlen($erro) == 0) $listar = "ok";

    if(!empty($exceto_posto)) {
        $checked = " CHECKED ";
    }

    if (strlen($erro) > 0) {
        $data_inicial       = trim($_POST["data_inicial_01"]);
        $data_final         = trim($_POST["data_final_01"]);
        $linha              = trim($_POST["linha"]);
        $estado             = trim($_POST["estado"]);
        $tipo_pesquisa      = trim($_POST["tipo_pesquisa"]);
        $pais               = trim($_POST["pais"]);
        $origem             = trim($_POST["origem"]);
        $criterio           = trim($_POST["criterio"]);
        $produto_referencia = trim($_POST['produto_referencia']); // HD 2003 TAKASHI
        $produto_descricao  = trim($_POST['produto_descricao']) ; // HD 2003 TAKASHI
        $tipo_os            = trim($_POST['tipo_os']);
        $exceto_posto       = $_POST["exceto_posto"];
        $revenda_cnpj = $_POST['revenda_cnpj'];
        $revenda_nome = strtoupper($_POST['revenda_nome']);

        if ($login_fabrica == 42) {
            $os_cortesia = filter_input(INPUT_POST,"os_cortesia");
        }
        //$msg_erro  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
        $msg_erro = $erro;
    }
}

$layout_menu = "gerencia";
$title = traduz("RELATÓRIO - FIELD CALL-RATE : LINHA DE PEÇAS");

if ($areaAdminCliente == true) {
    require_once "cabecalho.php";
} else {
    include_once "../cabecalho.php";
}

?>

<script language="JavaScript">

function AbrePeca(peca,data_inicial,data_final,linha,estado,posto,produto,pais,marca,tipo_data,aux_data_inicial,aux_data_final,exceto_posto, tipo_os, familia, nome_revenda,tipo_atendimento,nao_posto_interno){
    janela = window.open("fcr_pecas_item.php?peca=" + peca + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado +"&posto=" + posto +"&produto="+ produto + "&pais=" + pais +"&marca=" + marca + "&tipo_data=" + tipo_data +"&aux_data_inicial="+aux_data_inicial+"&aux_data_final="+aux_data_final+"&exceto_posto="+exceto_posto+"&tipo_os="+tipo_os+"&familia="+familia+"&nome_revenda="+nome_revenda+"&tipo_atendimento="+tipo_atendimento+"&nao_posto_interno="+nao_posto_interno,"peca",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
    janela.focus();
}
    
$(function() {

    	$("#estado option").remove();
    	$("#estado optgroup").remove();
    	$("#estado").append("<option value=''>TODOS OS ESTADOS</option>");

        var post = "<?= $_POST['estado']; ?>";

	<?php if (in_array($login_fabrica, [152])) { ?>
			
		var array_regioes = [
			"BA,SE,AL,PE,PB,RN,CE,PI,MA,SP",
			"MG,DF,GO,MT,RO,AC,AM,RR,PA,AP,TO",
			"MS,PR,SC,RS,RJ,ES"
		];

		$("#estado").append('<optgroup label="Regiões">');
         	var select = "";
			
		$.each(array_regioes, function( index, value ) {
			 
                if (post == value) {
                	select = "selected";
                }

		var option = "<option value=" + value + " "+ select + ">" + value + "</option>";

		$("#estado").append(option);
			select = "";
		}); 
            $("#estado").append('</optgroup>');
				
        <?php }
	if ($login_fabrica == 152) { ?>
		$("#estado").append('<optgroup label="Estados">');
	<?php } ?>

	var estados = <?= json_encode($array_estados($pais)); ?>;

	$.each(estados, function( index, value ) {		
	       	var estado = value;
	       	var sigla = index;
		var select = "";
		
		if (post == sigla) {
            		select = "selected";
            	}

	        var option = "<option value='" + sigla + "'" + select +">" + estado + "</option>";

                $("#estado").append(option);
	});

       	<?php if ($login_fabrica == 152) { ?>
		$("#estado").append('</optgroup>');
	<?php } ?>   
});
</script>

<style type="text/css">


/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
#logo{
    BORDER-RIGHT: 1px ;
    BORDER-TOP: 1px ;
    BORDER-LEFT: 1px ;
    BORDER-BOTTOM: 1px ;
    position: absolute;
    right: 10px;
    z-index: 5;
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

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.subtitulo{

color: #7092BE
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.espaco{
    padding-left: 120px;
}
</style>


<?
include ADMCLI_BACK."javascript_pesquisas.php";
include ADMCLI_BACK."javascript_calendario_new.php";
include_once ADMCLI_BACK.'../js/js_css.php';

?>

<script type="text/javascript" charset="utf-8">
    $(function()
    {
        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});
        $("#data_inicial").mask("99/99/9999");
        $("#data_final").mask("99/99/9999");
    });

function fnc_pesquisa_revenda(campo, tipo) {
    var url = "";
    if (tipo == "nome") {
        url = "../pesquisa_revenda<?=$rv_suffix?>.php?nome=" + campo.value + "&tipo=nome&proximo=t";
    }
    if (tipo == "cnpj") {
        url = "../pesquisa_revenda<?=$rv_suffix?>.php?cnpj=" + campo.value + "&tipo=cnpj&proximo=t";
    }

    if (campo.value != "") {
        if (campo.value.length >= 3) {
            janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
            janela.nome            = document.frm_pesquisa.revenda_nome;
            janela.cnpj            = document.frm_pesquisa.revenda_cnpj;
            janela.fone            = document.frm_pesquisa.revenda_fone;
            janela.cidade          = document.frm_pesquisa.revenda_cidade;
            janela.estado          = document.frm_pesquisa.revenda_estado;
            janela.endereco        = document.frm_pesquisa.revenda_endereco;
            janela.numero          = document.frm_pesquisa.revenda_numero;
            janela.complemento     = document.frm_pesquisa.revenda_complemento;
            janela.bairro          = document.frm_pesquisa.revenda_bairro;
            janela.cep             = document.frm_pesquisa.revenda_cep;
            janela.email           = document.frm_pesquisa.revenda_email;
            janela.proximo         = document.frm_pesquisa.nota_fiscal;
            janela.focus();
        }else{
            alert('<?=traduz("Digite pelo menos 3 caracteres para efetuar a pesquisa!")?>');
        }
    }
    else{
            alert('<?=traduz("Digite pelo menos 3 caracteres para efetuar a pesquisa!")?>');
        }
}
</script>

<link rel="stylesheet" href="../js/blue/style.css" type="text/css" id="" media="print, projection, screen" />

<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.pager.js"></script>
<script type="text/javascript" src="js/chili-1.8b.js"></script>
<script type="text/javascript" src="js/docs.js"></script>
<script type="text/javascript">
// add new widget called repeatHeaders
    $(function() {
        // add new widget called repeatHeaders
        $.tablesorter.addWidget({
            // give the widget a id
            id: "repeatHeaders",
            // format is called when the on init and when a sorting has finished
            format: function(table) {
                // cache and collect all TH headers
                if(!this.headers) {
                    var h = this.headers = [];
                    $("thead th",table).each(function() {
                        h.push(
                            "<th>" + $(this).text() + "</th>"
                        );

                    });
                }

                // remove appended headers by classname.
                $("tr.repated-header",table).remove();

                // loop all tr elements and insert a copy of the "headers"
                for(var i=0; i < table.tBodies[0].rows.length; i++) {
                    // insert a copy of the table head every 10th row
                    if((i%20) == 0) {
                        if(i!=0){
                        $("tbody tr:eq(" + i + ")",table).before(
                            $("<tr></tr>").addClass("repated-header").html(this.headers.join(""))

                        );
                    }}
                }

            }
        });

        // call the tablesorter plugin and assign widgets with id "zebra" (Default widget in the core) and the newly created "repeatHeaders"
        $("table").tablesorter({
            widgets: ['zebra','repeatHeaders']
        });

    });

//#(document).ready(function(){
//  $.tablesorter.defaults.widgets = ['zebra'];
//  $("#relatorio").tablesorter();

//});


</script>

<script type="text/javascript" charset="utf-8">
    jQuery.fn.slideFadeToggle = function(speed, easing, callback) {
        return this.animate({opacity: 'toggle', height: 'toggle'}, speed, easing, callback);
    }

    function toogleProd(radio){
        var obj = document.getElementsByName('radio_qtde_produtos');
        if (obj[0].checked){
            $('#id_um').show("");
            $('#id_multi').hide("");
        }
        if (obj[1].checked){
            $('#id_um').hide("");
            $('#id_multi').show("");
        }
    }

    function tooglePeca(radio){

        var obj = document.getElementsByName('radio_qtde_pecas');

        if (obj[0].checked){

            $('#peca_id_um').show("");
            $('#peca_id_multi').hide("");
        }

        if (obj[1].checked){

            $('#peca_id_um').hide("");
            $('#peca_id_multi').show("");
        }
    }

    var singleSelect = true;
    var sortSelect = true;
    var sortPick = true;


    function initIt() {
      var pickList = document.getElementById("PickList");
      var pickOptions = pickList.options;
      pickOptions[0] = null;
    }

    function addIt() {
        if ($('#produto_referencia_multi').val()=='')
            return false;
        if ($('#produto_descricao_multi').val()=='')
            return false;

        var pickList = document.getElementById("PickList");
        var pickOptions = pickList.options;
        var pickOLength = pickOptions.length;
        pickOptions[pickOLength] = new Option($('#produto_referencia_multi').val()+" - "+ $('#produto_descricao_multi').val());
        pickOptions[pickOLength].value = $('#produto_referencia_multi').val();

        $('#produto_referencia_multi').val("");
        $('#produto_descricao_multi').val("");

        if (sortPick) {
            var tempText;
            var tempValue;
            while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
                tempText = pickOptions[pickOLength-1].text;
                tempValue = pickOptions[pickOLength-1].value;
                pickOptions[pickOLength-1].text = pickOptions[pickOLength].text;
                pickOptions[pickOLength-1].value = pickOptions[pickOLength].value;
                pickOptions[pickOLength].text = tempText;
                pickOptions[pickOLength].value = tempValue;
                pickOLength = pickOLength - 1;
            }
        }

        pickOLength = pickOptions.length;
        $('#produto_referencia_multi').focus();
    }

    function addItPeca() {

        if ($('#produto_peca_multi').val()=='')
            return false;
        if ($('#produto_peca_multi').val()=='')
            return false;

        var pickList = document.getElementById("PickListPeca");
        var pickOptions = pickList.options;
        var pickOLength = pickOptions.length;

        pickOptions[pickOLength] = new Option($('#peca_referencia_multi').val()+" - "+ $('#peca_descricao_multi').val());
        pickOptions[pickOLength].value = $('#peca_referencia_multi').val();

        $('#peca_referencia_multi').val("");
        $('#peca_descricao_multi').val("");

        if (sortPick) {
            var tempText;
            var tempValue;
            while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
                tempText = pickOptions[pickOLength-1].text;
                tempValue = pickOptions[pickOLength-1].value;
                pickOptions[pickOLength-1].text = pickOptions[pickOLength].text;
                pickOptions[pickOLength-1].value = pickOptions[pickOLength].value;
                pickOptions[pickOLength].text = tempText;
                pickOptions[pickOLength].value = tempValue;

                pickOLength = pickOLength - 1;
            }
        }

        pickOLength = pickOptions.length;
        $('#peca_referencia_multi').focus();
    }


    function delItPeca() {

        var pickList = document.getElementById("PickListPeca");
        var pickIndex = pickList.selectedIndex;
        var pickOptions = pickList.options;
        while (pickIndex > -1) {
            pickOptions[pickIndex] = null;
            pickIndex = pickList.selectedIndex;
        }
    }

    function delIt() {
      var pickList = document.getElementById("PickList");
      var pickIndex = pickList.selectedIndex;
      var pickOptions = pickList.options;
      while (pickIndex > -1) {
        pickOptions[pickIndex] = null;
        pickIndex = pickList.selectedIndex;
      }
    }

    function selIt(btn) {
        var pickList = document.getElementById("PickList");
        var pickOptions = pickList.options;
        var pickOLength = pickOptions.length;
        for (var i = 0; i < pickOLength; i++) {
            pickOptions[i].selected = true;
        }
    }

    function Pais(pais) {
       
        var array_estados;

        array_estados = ["BR"];

        <?php if (in_array($login_fabrica,[152,180,181,182])) { ?>

            array_estados = ["BR", "AR", "PE", "CO"];

        <?php } ?>

        if (array_estados.indexOf(pais) >= 0) {

            $("#estado").attr("disabled", false);
        } else {

            $("#estado").attr("disabled", true);
        }

    }

    <?php
    if (in_array($login_fabrica,[94, 120])) {
    ?>
        function fnc_pesquisa_peca (campo, campo2, tipo)
        {
            if (tipo == "referencia" ) {
                var xcampo = campo;
            }

            if (tipo == "descricao" ) {
                var xcampo = campo2;
            }

            if (xcampo.value != "") {
                var url = "";
                url = "../peca_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
                janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
                janela.referencia   = campo;
                janela.descricao    = campo2;
                janela.focus();
            }
            else{
                alert('<?=traduz("Informe toda ou parte da informação para realizar a pesquisa")?>');
            }
        }
    <?php
    }
    ?>
</script>
<DIV ID="container" style="width: 100%; ">

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<?
if (1==2){ /* Tulio solicito que fosse retirado a mensagem. Resolvi retirar tudo! - Fabio - 03/10/2008 */
    echo "<div style='background-color:#FCDB8F;width:600px;margin:0 auto;text-align:center;padding:2px 10px 2px 10px;font-size:12px'>";
    echo "<p style='text-align:left;padding:0px;'><b>ATENÇÃO: </b>Este relatório de BI considera toda  OS que está finalizada, sendo possível fazer a pesquisa com os dados abaixo. Caso queira utilizar o antigo relatório <a href='../relatorio_field_call_rate_pecas_defeitos.php'>clique aqui.</a> </p>";
    echo "<p style='text-align:left'>TELECONTROL</p>";
    echo "</div>";
}
?>

<TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="0" class="formulario" id='Formulario'>
    <? if(strlen($msg_erro) > 0){ ?>
        <tr class="msg_erro">
            <td colspan="4">
                    <? echo $msg_erro ?>

            </td>
        </tr>
    <? } ?>
    <tr class="titulo_tabela">
        <td colspan="2"><?=traduz('Parâmetros de Pesquisa')?></td>
    </tr>
        <tr>
            <td width='50%'>&nbsp;</td>
            <td width='*'>&nbsp;</td>
        </tr>
    <tbody>
    <TR>
        <TD class='espaco'>
            <?=traduz('Data Inicial')?><br>
            <INPUT size="15" maxlength="10" class="frm" TYPE="text" NAME="data_inicial" id="data_inicial" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
        </TD>
        <TD>
            <?=traduz('Data Final')?><br>
            <INPUT size="15" maxlength="10" class="frm" TYPE="text" NAME="data_final" id="data_final" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" >
        </TD>
    </TR>
    <tr><td colspan="2">&nbsp;</td></tr>
    <?php if($login_fabrica == 24){ ?>
        <TR>
            <TD colspan="2" class='espaco'>
                <fieldset style="width:420px;">
                    <legend><?=traduz('Tipo de OS')?></legend>
                    <input type='radio' name='tipo_os' value='C'<?if($tipo_os=="C") echo "CHECKED";?>> <?=traduz('OS Consumidor')?>
                    <input type='radio' name='tipo_os' value='R'<?if($tipo_os=="R") echo "CHECKED";?>> <?=traduz('OS Revenda')?>

                    <input type='radio' name='tipo_os' value='todos'<?if($tipo_os=="todos" or $tipo_os=="") echo "CHECKED";?>> <?=traduz('Todos')?>
                </fieldset>
            </TD>
        </TR>
        <tr><td colspan="2">&nbsp;</td></tr>
    <?php } ?>
    <TR>
        <td colspan="2" class='espaco'>
        <fieldset style="width:350px;">
            <legend><?=traduz('Data de Referência')?></legend>
            <table>
                <tr>
                    <td><input type='radio' name='tipo_data' value='data_fechamento'<?if($tipo_data=="data_fechamento" or $tipo_data=="") echo "CHECKED";?>> <?=traduz('Fechamento')?></td>
                    <td><input type='radio' name='tipo_data' value='data_finalizada'<?if($tipo_data=="data_finalizada") echo "CHECKED";?>> <?=traduz('Finalizada')?></td>
                    <?php
                    if ($login_fabrica == "91") {
                        echo '<td><input type="radio" name="tipo_data" value="data_abertura" ';
                        if ($tipo_data == "data_abertura") {
                            echo ' checked="CHECKED" ';
                        }
                        echo '>Abertura</td>';
                    }
                    ?>
                </tr>
                <tr>
                    <td><input type='radio' name='tipo_data' value='extrato_geracao'<?if($tipo_data=="extrato_geracao") echo "CHECKED";?>> <?=traduz('Geração de Extrato')?></td>
                    <td><input type='radio' name='tipo_data' value='extrato_aprovacao'<?if($tipo_data=="extrato_aprovacao") echo "CHECKED";?>> <?=traduz('Aprovação do Extrato')?></td>
                </tr>
                <?if($login_fabrica==20){?>
                <tr>
                    <td><input type='radio' name='tipo_data' value='extrato_exportacao'<?if($tipo_data=="extrato_exportacao") echo "CHECKED";?>> <?=traduz('Data pagamento')?></td>
                </tr>
                <?}?>
                <?if($login_fabrica==40){?>
                <tr>
                    <td><input type='radio' name='tipo_data' value='data_abertura'<?if($tipo_data=="data_abertura") echo "CHECKED";?>> <?=traduz('Data de Abertura')?></td>
                    <td><input type='radio' name='tipo_data' value='data_digitacao'<?if($tipo_data=="data_digitacao") echo "CHECKED";?>> <?=traduz('Data de Digitação')?></td>
                </tr>
                <?}?>
            </table>
        </fieldset>
        </td>
    </TR>
<?php
if($login_fabrica == 158){
?>
<TR>
        <td colspan="2" class='espaco'>
        <fieldset style="width:350px;">
            <legend>Tipo de Atendimento</legend>
            <table>
                <tr>
                    <td><input type='radio' name='tipo_atendimento' value='dentro_garantia'<?if($tipo_atendimento=="dentro_garantia") echo "CHECKED";?>> Dentro da Garantia</td>
                    <td><input type='radio' name='tipo_atendimento' value='fora_garantia'<?if($tipo_atendimento=="fora_garantia") echo "CHECKED";?>> Fora da Garantia</td>
                </tr>
            </table>
        </fieldset>
        </td>
    </TR>

<?php
}
?>
    <tr><td colspan="2" class='espaco'>&nbsp;</td></tr>
        <?
        #123856
    if(in_array($login_fabrica,[50, 120])) {
            if (count($lista_produtos)>0){
                $display_um_produto    = "display:none";
                $display_multi_produto = "";
                $display_um            = "";
                $display_multi         = " CHECKED ";
            }else{
                $display_um_produto    = "";
                $display_multi_produto = "display:none";
                $display_um            = " CHECKED ";
                $display_multi         = "";
            }
        ?>
        <TR>
            <td colspan="2" class='espaco'><?=traduz('SELECIONE ')?>&nbsp;&nbsp;&nbsp; <?=traduz('Um produto')?>
                <input type="radio" name="radio_qtde_produtos" value='um'  <?=$display_um?>  onClick='javascript:toogleProd(this)'>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <?=traduz('Vários Produtos')?>
                <input type="radio" name="radio_qtde_produtos" value='muitos' <?=$display_multi?> onClick='javascript:toogleProd(this)'>
            </td>

        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <TR>
            <TH colspan='2' nowrap align="left" class='espaco'>
                <div id='id_um' style='<?echo $display_um_produto;?>'>
                    <b><?=traduz('Ref. Produto')?></b><br><input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" > &nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'referencia')">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b><?=traduz('Descrição Produto')?></b>
                    <input class="frm" type="text" name="produto_descricao"  id="produto_descricao" size="15" value="<? echo $produto_descricao ?>" >&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'descricao')">
                </div>
                <div id='id_multi' style='<?echo $display_multi_produto;?>'>
                    <?=traduz('Ref. Produto')?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input class="frm" type="text" name="produto_referencia_multi" id="produto_referencia_multi" size="15" maxlength="20" value="" > &nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia_multi, document.frm_pesquisa.produto_descricao_multi,'referencia')">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b><?=traduz('Descrição Produto')?></b>
                    <input class="frm" type="text" name="produto_descricao_multi"  id="produto_descricao_multi" size="15" value="" >&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia_multi, document.frm_pesquisa.produto_descricao_multi,'descricao')"><input type='button' name='adicionar_produto' id='adicionar_produto' value='Adicionar' class='frm' OnClick="addIt();" alt="Adicionar Produto" title="Adicionar Produto">
                    <br>
                        <font color='grey' size=1>(Selecione o produto e clique em Adicionar)</font><br>
                    <center>
                        <select multiple=true SIZE='4' style="width:80%" ID="PickList" NAME="PickList[]">
                            <?
                            if (count($produto_lista)>0){
                                for ($i=0; $i<count($produto_lista); $i++){
                                    $linha_prod = $produto_lista[$i];
                                    echo "<option value='".$linha_prod[1]."'>".$linha_prod[1]." - ".$linha_prod[2]."</option>";
                                }
                            }
                            ?>
                        </select><input type='button' name='remover_produto' id='remover_produto' value='Remover' class='frm'OnClick="delIt();" alt="Retirar Produto" title="Retirar Produto">
                    </center>
                </div>
            </TH>
        </TR>
    <?
    }else{
    ?>
        <TR>
            <td class='espaco'>
                Ref. Produto<br>
                <input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
                <img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'referencia')">
            </td>
            <td>
                <?=traduz('Descrição Produto')?><br>
                <input class="frm" type="text" name="produto_descricao"  id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
                <img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'descricao')">
            </td>
        </TR>
    <?
    }
    ?>

    <tr><td colspan="2" class='espaco'>&nbsp;</td></tr>

    <tr><td colspan="2">&nbsp;</td></tr>
    
    <?php
    
    /**
     * @author William Castro <william.castro@telecontrol.com.br>
     * hd-
     */

    if(in_array($login_fabrica,[120, 148])) {
            if (count($lista_pecas) > 0) {
                $display_um_peca    = "display:none";
                $display_multi_peca = "";
                $display_um            = "";
                $display_multi         = " CHECKED ";
            } else {
                $display_um_peca    = "";
                $display_multi_peca = "display:none";
                $display_um            = " CHECKED ";
                $display_multi         = "";
            }
        ?>
        <TR>
            <td colspan="2" class='espaco'>SELECIONE &nbsp;&nbsp;&nbsp; 
                Uma peça
                <input type="radio" name="radio_qtde_pecas" value='um'  <?=$display_um?>  onClick='javascript:tooglePeca(this)'>
                &nbsp;&nbsp;&nbsp;&nbsp;
                Várias Peças
                <input type="radio" name="radio_qtde_pecas" value='muitos' <?=$display_multi?> onClick='javascript:tooglePeca(this)'>
            </td>

        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <TR>
            <TH colspan='2' nowrap align="left" class='espaco'>
                <div id='peca_id_um' style='<?echo $display_um_peca;?>'>
                    <b>Ref. Peça</b><br><input class="frm" type="text" name="peca_referencia" id="peca_referencia" size="15" maxlength="20" value="<? echo $peca_referencia ?>" > &nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_peca (document.frm_pesquisa.peca_referencia, document.frm_pesquisa.peca_descricao,'referencia')">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b>Descrição Peça</b>
                    <input class="frm" type="text" name="peca_descricao"  id="peca_descricao" size="15" value="<? echo $peca_descricao ?>" >&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_peca (document.frm_pesquisa.peca_referencia, document.frm_pesquisa.peca_descricao,'descricao')">
                </div>
                <div id='peca_id_multi' style='<?echo $display_multi_peca;?>'>
                    Ref. Peça&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input class="frm" type="text" name="peca_referencia_multi" id="peca_referencia_multi" size="15" maxlength="20" value="" > &nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_peca (document.frm_pesquisa.peca_referencia_multi, document.frm_pesquisa.peca_descricao_multi,'referencia')">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b>Descrição Peça</b>
                    <input class="frm" type="text" name="peca_descricao_multi"  id="peca_descricao_multi" size="15" value="" >&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_peca (document.frm_pesquisa.peca_referencia_multi, document.frm_pesquisa.peca_descricao_multi,'descricao')">
                    <input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' OnClick="addItPeca();" alt="Adicionar Peça" title="Adicionar Peça">
                    <br>
                        <font color='grey' size=1>(Selecione a peça e clique em Adicionar)</font><br>
                    <center>
                        <select multiple=true SIZE='4' style="width:80%" ID="PickListPeca" NAME="PickListPeca[]">
                            <?php

                            if (count($peca_lista)>0) {

                                for ($i=0; $i<count($peca_lista); $i++){

                                    $linha_peca = $peca_lista[$i];

                                    echo "<option value='".$linha_peca[1]."'>".$linha_peca[1]." - ".$linha_peca[2]."</option>";
                                }
                            }

                            ?>
                        </select><input type='button' name='remover_peca' id='remover_peca' value='Remover' class='frm'OnClick="delItPeca();" alt="Retirar Peça" title="Retirar Peça">
                    </center>
                </div>
            </TH>
        </TR>
    <?php } ?>

    <tr><td colspan="2" class='espaco'>&nbsp;</td></tr>

    <tr><td colspan="2">&nbsp;</td></tr>


    <TR>
        <TD class='espaco'>
            <?=traduz('Cód. Posto')?><br>
            <input type="text" name="codigo_posto" size="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
            <img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
        </TD>
        <TD>
            <?=traduz('Nome Posto')?>
            <? if ($login_fabrica == 40) { ?>
                (Exceto este Posto
                <input type='checkbox' name='exceto_posto' value='exceto_posto' <?=$checked?>>)
            <?}?><br>
            <input type="text" name="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
            <img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome,'nome')">
        </TD>
    </TR>



    <tr><td colspan="2">&nbsp;</td></tr>
    <?php if($login_fabrica == 24){ ?>
            <TR>
                <!--<TD class='espaco'>
                    CNPJ Revenda<br>
                    <input type="text" name="revenda_cnpj" size="15" value="<? echo $revenda_cnpj ?>" class="frm">
                    <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar revendas pelo código" onclick="javascript: fnc_pesquisa_revenda (document.frm_pesquisa.revenda_cnpj, 'cnpj')">
                </TD>-->
                <TD class='espaco' colspan='2'>
                    Nome Revenda <br>
                    <input type="text" name="revenda_nome" size="68" value="<?echo $revenda_nome?>" class="frm">
                    <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar revendas pelo código" onclick="javascript: fnc_pesquisa_revenda (document.frm_pesquisa.revenda_nome, 'nome')">
                </TD>
            </TR>

            <tr><td colspan="2">&nbsp;</td></tr>

    <?php } ?>
    <TR>
        <TD colspan="0" class='espaco' style="float:left;">
            Por Região<br>
            <select id="estado" name="estado" class='frm'>
                
                <?php if (isset($_POST['pais'])) { 
                            
                    $sigla = $_POST['estado'];
                    $estado = $_POST['estado'];

                    if ($_POST['pais'] == "BR") {

                        $sigla = $_POST['estado'];
                        $estado = $_POST['estado'];

                        if ($_POST['pais'] == "BR") {

                            $brasil = $array_estados();
                            $estado = $brasil[$sigla];

                            if (!isset($estado)) {
                                $estado = $sigla;
                            }
                        } 
                    } ?>

                    <option value="<?= $sigla ?>"><?= $estado ?></option>

                    <? } else { ?>
                        <option value=""   
                            <?php if (strlen($estado) == 0)    
                                echo " selected "; 
                            ?>
                            > 
                            <?php if($login_fabrica == 86) {
                                echo "TODOS OS ESTADOS e/ou Regiões";   
                            } ?>   
                        </option>
                    <?php } ?>
            </select>
        </TD>

        <td colspan="0" class=''>
            País<br>
            <?
            $sql = "SELECT  *
                    FROM    tbl_pais
                    where america_latina is TRUE
                    ORDER BY tbl_pais.nome;";
            $res = pg_exec ($con,$sql);

            if (pg_numrows($res) > 0) {
                echo "<select name='pais' id='pais' class='frm' style='width: 200px' onchange='javascript:Pais(this.value);'>\n";
               
                echo "<option value='' selected>TODOS OS PAÍSES</option>";

                for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
                    
                    $aux_pais  = trim(pg_result($res,$x,pais));
                    
                    $aux_nome  = trim(pg_result($res,$x,nome));

                    echo "<option value='$aux_pais'";

                    if (isset($_POST['pais']) && $_POST['pais'] == $aux_pais) {
                        
                        echo " SELECTED ";
                    }
                
                    echo ">$aux_nome</option>\n";

                }

                echo "</select>\n";

                $sql = "SELECT  nome 
                        FROM    tbl_pais
                        WHERE   pais = '{$_POST['pais']}'";
                    
                $res = pg_exec ($con,$sql);

                $getPaisBySigla = pg_fetch_result($res, 0, nome);

                $mostraMsgPais = "<br> do PAÍS {$getPaisBySigla}";
                } ?>
        </td>

    </TR>

    <?if ($login_fabrica==14 || $login_fabrica == 94){?>
    <tr><td colspan="2">&nbsp;</td></tr>
    <TR>
        <td colspan="2" class='espaco'>
        <?
        if ($login_fabrica == 94) {
            echo "Linha <br />";
        } else {
            echo "Linha * &nbsp;&nbsp;";
        }

        ##### INÍCIO LINHA #####
        $sql = "SELECT  *
                FROM    tbl_linha
                WHERE   tbl_linha.fabrica = $login_fabrica
                ORDER BY tbl_linha.nome;";
        $res = pg_exec ($con,$sql);

        if (pg_numrows($res) > 0) {
            echo "<select class='frm' style='width: 200px;' name='linha'>\n";
            echo "<option value=''>ESCOLHA</option>\n";

            for ($x = 0 ; $x < pg_numrows($res) ; $x++){
                $aux_linha = trim(pg_result($res,$x,linha));
                $aux_nome  = trim(pg_result($res,$x,nome));

                echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
            }
            echo "</select>\n";
        }
        ##### FIM LINHA #####
        ?>
        </td>
    </TR>
    <?
    }


    if (in_array($login_fabrica,[94])) {
    ?>
        <tr><td colspan="2">&nbsp;</td></tr>
        <TR>
            <td nowrap align="left" class='espaco'>
                Ref. Peça
                <br>
                <input class="frm" type="text" name="peca_referencia" id="peca_referencia" value="<? echo $peca_referencia ?>" > &nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_peca (document.frm_pesquisa.peca_referencia, document.frm_pesquisa.peca_descricao,'referencia')">
            </td>
            <td nowrap align="left">
                Descrição Peça
                <br>
                <input class="frm" type="text" name="peca_descricao"  id="peca_descricao" value="<? echo $peca_descricao ?>" >&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_peca (document.frm_pesquisa.peca_referencia, document.frm_pesquisa.peca_descricao,'descricao')">
            </td>
        </TR>
    <?php
    }

    
    if ($login_fabrica == 7) { ?>
    <tr><td colspan="2">&nbsp;</td></tr>
    <TR>
        <TD colspan="2" class='espaco'>Classificação de OS&nbsp;&nbsp;<br>
            <?
            $sql = "SELECT  *
                    FROM    tbl_classificacao_os
                    WHERE   fabrica = $login_fabrica
                    AND ativo is true;";
            $res = pg_exec ($con,$sql);

            if (pg_numrows($res) > 0) {
                echo "<select name='classificacao' class='frm'>\n";
                echo "<option></option>";
                for ($x = 0 ; $x < pg_numrows($res) ; $x++){
                    $aux_classificacao   = trim(pg_result($res,$x,classificacao_os));
                    $aux_descricao = trim(pg_result($res,$x,descricao));

                    echo "<option value='$aux_classificacao'";
                    if ($classificacao == $aux_classificacao){
                        echo " SELECTED ";
                        $mostraMsgLinha .= "<br> da CLASSIFICAÇÃO $aux_descricao";
                    }
                    echo ">$aux_descricao</option>\n";
                }
                echo "</select>\n&nbsp;";
            }
        ?>
        </TD>
    </TR>
<? }?>

    <tr><td colspan="2">&nbsp;</td></tr>

    <tr>
    <?php if($login_fabrica == 24){ ?>
    <td class='espaco'>
            Família <br />
            <select name='familia' class='frm'>
                <option value=''>Selecione a Família</option>
                    <?php
                    $sqlFamilia = "SELECT familia,descricao FROM tbl_familia WHERE fabrica = $login_fabrica and ativo is true ORDER BY descricao";
                    $resFamilia = pg_query($con,$sqlFamilia);
                    $totalFamilia = pg_numrows($resFamilia);
                    if($totalFamilia > 0){
                        for($i = 0; $i < $totalFamilia; $i++){
                            $cod_familia = pg_result($resFamilia,$i,familia);
                            $desc_familia = pg_result($resFamilia,$i,descricao);
                        ?>
                            <option value='<?php echo $cod_familia; ?>' <?php if($cod_familia == $familia) echo 'SELECTED'; ?> > <?php echo $desc_familia; ?> </option>
                        <?php
                        }
                    }
                ?>
            </select>
        </td>

        <?php } ?>
        <TD colspan="2" align="left" <? if($login_fabrica <> 24) echo "class='espaco'";?>>
            <fieldset style="width:200px;">
                <legend><?=traduz('Tipo Arquivo para Download')?></legend>
                <input type='radio' name='formato_arquivo' value='XLS' <?if($formato_arquivo=='XLS')echo "checked";?>> XLS
                &nbsp;&nbsp;&nbsp;
                <input type='radio' name='formato_arquivo' value='CSV' <?if($formato_arquivo!='XLS')echo "checked";?>> CSV
            </fieldset>
        </TD>
    </tr>

    <tr><td colspan="2">&nbsp;</td></tr>

    <?php if ($login_fabrica == 175){ ?>
     <tr>
        <td colspan='2' align='left' class='espaco'>
            <input type='checkbox' name='componente_raiz' <?=($componente_raiz == 't')? 'checked' : ''?> value='t'> Componente raiz
        </td>
	</tr>
	<?php } ?>
    <tr>
        <td colspan='2' align='left' class='espaco'>
            <input type='checkbox' name='mostra_peca' value='mostra_peca' <? if($login_fabrica == 30){ echo "checked";}?>> <?=traduz('Mostrar custo de peça mesmo se pedido estiver pendente!')?>
        </td>
	</TR>
	<tr>
        <td colspan='2' align='left' class='espaco'>
			<input type='checkbox' name='nao_produto' value='t' <? if($nao_produto == 't'){ echo "checked";}?>> <?=traduz('Não contabilizar produto acabado!')?>
        </td>
    </TR>
<?php
if ($login_fabrica == 42) {
?>

    <tr>
        <td colspan='2' align='left' class='espaco'>
            <input type='checkbox' name='os_cortesia' value='t' <? if($os_cortesia == 't'){ echo "checked";}?>> Solicitação de Cortesia Comercial
        </td>
    </TR>
<?php
}
if ($login_fabrica == 161) {
?>

    <tr>
        <td colspan='2' align='left' class='espaco'>
            <input type='checkbox' name='nao_posto_interno' value='t' <? if($nao_posto_interno == 't'){ echo "checked";}?>> Não contabilizar Posto Interno
        </td>
    </TR>
<?php
}
?>
    </TBODY>
    <TFOOT>

    <tr><td colspan="2" >&nbsp;</td></tr>

    <TR>
        <input type='hidden' name='btn_finalizar' value='0'>

        <TD colspan="2" align="center"><input type="button" style="cursor:pointer;" value="<?=traduz('Pesquisar')?>" onclick="javascript: 
            if (document.frm_pesquisa.btn_finalizar.value == '0' ) { 
                document.frm_pesquisa.btn_finalizar.value='1' 
                $('#PickListPeca option').attr('selected', true); 
                $('#PickList option').attr('selected', true) ; 
                document.frm_pesquisa.submit(); 
            } else { 
                alert ('<?php echo traduz("Aguarde submissão da OS..."); ?>'); 
            }" alt='Clique AQUI para pesquisar'>
        </TD>
    </TR>
    <tr><td colspan="2">&nbsp;</td></tr>
    </TFOOT>
</TABLE>

</FORM>
</DIV>

<?

if ($listar == "ok") {

    if(strlen($codigo_posto)>0){
        $sql = "SELECT  posto
                FROM    tbl_posto_fabrica
                WHERE   fabrica      = $login_fabrica
                AND     codigo_posto = '$codigo_posto';";
        $res = pg_exec ($con,$sql);
        if (pg_numrows($res) > 0) $posto = trim(pg_result($res,0,posto));
    }

    if (strlen ($linha)    > 0) $cond_1 = " AND   BI.linha   = $linha ";
    if (strlen ($estado)   > 0) $cond_2 = " AND   BI.estado  IN ('$consulta_estado') ";
    if (strlen ($posto)    > 0) $cond_3 = " AND   BI.posto   = $posto ";
    if (strlen ($posto) > 0 AND !empty($exceto_posto)) {
        $cond_3 = " AND   NOT (BI.posto   = $posto) ";
    }
    if (strlen ($produto)  > 0) $cond_4 = " AND   BI.produto = $produto "; // HD 2003 TAKASHI

    if ($login_fabrica == 94 && strlen ($peca) > 0) $cond_peca = " AND   BI.peca = $peca ";

    if (strlen ($pais)     > 0) $cond_6 = " AND   BI.pais    = '$pais' ";
    if (strlen ($marca)    > 0) $cond_7 = " AND   BI.marca   = $marca ";
    if (strlen ($familia)  > 0){
        $cond_8 = " AND   BI.familia  = $familia ";
    }
    if (strlen ($lista_produtos)  > 0) {
        $cond_10 = " AND   BI.produto in ( $lista_produtos) ";
        $cond_4 = "";
    }

    if (strlen($tipo_data) == 0 or $tipo_data=="data_fechamento") $tipo_data = 'data_fechamento';
    if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0){
        $cond_9 = "AND   BI.$tipo_data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
    }

    if (strlen($tipo_data) == 0 or $tipo_data=="data_abertura") $tipo_data = 'data_abertura';
    if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0){
        $cond_9 = "AND   BI.$tipo_data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
    }


    if (strlen($tipo_data) == 0 or $tipo_data=="data_digitacao") $tipo_data = 'data_digitacao';
    if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0){
        $cond_9 = "AND   BI.$tipo_data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
    }

    if ($login_fabrica == 175 AND $componente_raiz == "t"){
        $cond_componente_rais = " AND BI.componente_raiz IS TRUE ";
    }

    if($login_fabrica == 20 and $pais !='BR'){
        $produto_descricao   ="tbl_produto_idioma.descricao ";
        $join_produto_idioma =" LEFT JOIN tbl_produto_idioma ON tbl_produto.produto = tbl_produto_idioma.produto and tbl_produto_idioma.idioma = 'ES' ";
    }else{
        $produto_descricao   ="tbl_produto.descricao ";
        $join_produto_idioma =" ";
    }


    if ($login_fabrica == 42) {
        $join_bi = "JOIN bi_os AS B ON B.os = BI.os ";
        if ($os_cortesia == 't'){
            $join_bi .= " AND B.cortesia IS TRUE";
        } else {
            $join_bi .= "AND B.cortesia IS NOT TRUE";
        }
    }

    if($login_fabrica == 24){
        $join_bi = "JOIN bi_os AS B ON B.os = BI.os ";

        if($tipo_os != "todos"){
            $cond_11 = " AND B.consumidor_revenda = '$tipo_os' ";
        }

        if(!empty($revenda_nome)){
            $cond_11 .= " AND B.revenda_nome LIKE '$revenda_nome%' ";
        }

        if(!empty($familia)){
            $cond_11 .= " AND B.familia = $familia ";
        }

        if($tipo_data == 'extrato_geracao' OR $tipo_data == 'extrato_aprovacao'){
            $join_bi .= " JOIN tbl_tabela_item ON tbl_tabela_item.peca = BI.peca";
            $campo_peca = " , tbl_tabela_item.preco AS preco_peca";
            $group_peca = " ,tbl_tabela_item.preco";
        }
    }

    if ($login_fabrica == 85) {
        $join_bi .= " JOIN tbl_tabela_item ON tbl_tabela_item.peca = BI.peca JOIN tbl_tabela ON tbl_tabela_item.tabela = tbl_tabela.tabela and  tbl_tabela.fabrica = $login_fabrica and tabela_garantia ";
        $campo_peca = " , round(tbl_tabela_item.preco::numeric,2) AS preco_peca";
        $group_peca = " ,tbl_tabela_item.preco";

    }

    if($login_fabrica == 161){
	    $campo_peca = " , BI.custo_peca AS preco_peca";
	    $group_peca = " ,BI.custo_peca";
    }

    if ($login_fabrica == 7 and strlen($classificacao)>0) {

        $sql_tmp = "select count(*) as qtde, bi_os_item.peca
                        INTO TEMP tmp_qtde_$login_admin
                        from bi_os BI
                        JOIN bi_os_item using(os)
                        JOIN tbl_peca PE ON PE.peca = bi_os_item.peca AND PE.fabrica = $login_fabrica
                        where BI.fabrica = $login_fabrica
                        AND BI.excluida IS NOT TRUE
                        $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10
                        and classificacao_os = $classificacao
                        GROUP BY bi_os_item.peca";

        $res_tmp = pg_exec($con,$sql_tmp);
        $join_classificacao = "JOIN      tmp_qtde_$login_admin ON tmp_qtde_$login_admin.peca = BI.peca";
        $campo_classificacao = "tmp_qtde_$login_admin.qtde as classificacao,";
        $group_classificacao = "tmp_qtde_$login_admin.qtde           ,";

    }

    if($login_fabrica == 158 AND strlen($_POST['tipo_atendimento']) > 0){
    	$join_tipo_atendimento = " JOIN tbl_os ON tbl_os.os = BI.os JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica} ";

    	if($_POST['tipo_atendimento'] == 'fora_garantia'){
    		$join_tipo_atendimento .= " AND tbl_tipo_atendimento.fora_garantia IS TRUE ";
    	}else{
    		$join_tipo_atendimento .= " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
    	}
	}

	if ($areaAdminCliente) {
        $join_tipo_atendimento = " JOIN tbl_os ON tbl_os.os = BI.os JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica} ";
		$cond_cliente_admin = "AND   tbl_os.cliente_admin = $login_cliente_admin";
	}

	if($nao_produto == 't') {
		$cond_nao_produto = " AND produto_acabado is not true ";
	}

	if($nao_posto_interno == 't'){
		$join_posto_interno = " JOIN tbl_posto_fabrica ON BI.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica} AND tbl_tipo_posto.posto_interno IS NOT TRUE ";
	}

	if($login_fabrica == 161){
		$join_servico_realizado = " JOIN tbl_servico_realizado ON BI.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica} ";
		$campos_servico = ", SUM(COALESCE(BI.custo_peca,0)) FILTER(WHERE tbl_servico_realizado.troca_de_peca IS TRUE AND gera_pedido IS TRUE) AS custo_troca,
				     COUNT(BI.peca) FILTER(WHERE tbl_servico_realizado.troca_de_peca IS TRUE AND gera_pedido IS TRUE) AS total_troca ";
	}

    /*
        FABRICA 148 => //hd_chamado=3049906
        Marisa vai criar o campo cancelada na tabela bi_os
        assim que criado o campo remover o JOIN
    */
    if(in_array($login_fabrica, array(74,148))){//hd_chamado=3049906
        $join_cancelada = " JOIN tbl_os ON tbl_os.os = BI.os ";
        $cond_cancelada = " AND tbl_os.cancelada IS NOT TRUE ";
    }

    $sql = "SELECT  PE.peca                                    ,
                    PE.referencia_fabrica                      ,
                    PE.referencia                              ,
                    PE.descricao                               ,
                    $campo_classificacao
                    $campo_busca_produto
                    SUM(BI.preco)                AS total_preco,
                    SUM(BI.custo_peca * BI.qtde) AS total_cp   ,
                    SUM(BI.qtde)                 AS qtde_pecas 
					$campo_peca
					$campos_servico
        FROM      bi_os_item BI
        JOIN      tbl_peca    PE ON PE.peca    = BI.peca AND PE.fabrica = $login_fabrica
        $join_classificacao
        $join_bi
	$join_tipo_atendimento
	$join_cancelada
	$join_posto_interno
	$join_servico_realizado
        WHERE BI.fabrica = $login_fabrica
         $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11 $cond_12 $cond_peca
         $cond_cancelada $cond_nao_produto
         $cond_cliente_admin
         $cond_componente_rais
        GROUP BY    PE.peca                              ,
                    PE.referencia                        ,
                    PE.referencia_fabrica                ,
                    $campo_produto
                    $group_classificacao
                    PE.descricao
                    $group_peca
        ORDER BY qtde_pecas DESC ";  //die($sql);
       
    $res = pg_exec ($con,$sql);
    
    if (pg_numrows($res) > 0) {
        $total = 0;
        echo "<br>";

        echo "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais </b>";

        echo "<br><br>";

        $data = date("Y-m-d").".".date("H-i-s");

        $arquivo_nome     = "bi-os-pecas-$login_fabrica.$login_admin.".$formato_arquivo;
        $path             = "../xls/";
        $path_tmp         = "/tmp/";

        $arquivo_completo     = $path.$arquivo_nome;
        $arquivo_completo_tmp = $path_tmp.$arquivo_nome;

        $fp = fopen ($arquivo_completo_tmp,"w+");

        if ($formato_arquivo!='CSV'){
            fputs ($fp,"<html>");
            fputs ($fp,"<body>");
        }
        if ($login_fabrica==50) { // HD 41116
            echo "<span id='logo'><img src='../imagens_admin/colormaq_.gif' border='0' width='160' height='55'></span>";
        }
        /*echo "<p id='id_download' style='display:none'><a href='../xls/$arquivo_nome' target='_blank'><img src='/assist/imagens/excel.gif'><br><font color='#3300CC'><div style='background:white;width:130px;border:solid 1px #596d9b;cursor:pointer;'>Download em  ".strtoupper($formato_arquivo)."</div></font></a></p>";*/
        echo "<p id='id_download' style='display:none'><a href='../xls/$arquivo_nome' target='_blank'><img src='".ADMCLI_BACK."../imagens/excel.gif'></a></p>";
        $caminho_download = "../xls/".$arquivo_nome;
        ?>

        <input type='button' name='' value='Download em <?php echo strtoupper($formato_arquivo);?>' onclick="window.open('<?php echo $caminho_download;?>');"/><br><br>

        <?php
        $nome_post = "";
        $sql_busca = "SELECT   tbl_posto.*, tbl_posto_fabrica.codigo_posto
                    FROM     tbl_posto
                    JOIN     tbl_posto_fabrica USING (posto)
                    WHERE    tbl_posto_fabrica.codigo_posto ilike '%$codigo_posto%'
                    AND      tbl_posto_fabrica.fabrica = $login_fabrica
                    ORDER BY tbl_posto.nome";
        $resultado = pg_exec($con,$sql_busca);
        for ($f=0; $f<pg_numrows($resultado); $f++){
            $nome_post  = trim(pg_result($resultado,$f,nome));
        }


        if($codigo_posto !='' || $posto_nome !=''){
        ?>
        <table width='100%' border='0' cellspacing='0' cellpadding='0' align='center' class='formulario'>
            <TR class='titulo_coluna'>
                <td class="titulo_tabela">Nome do Posto<br></td>
            </tr>
            <TR class='titulo_coluna'>
                <td  class="titulo_coluna"><?php echo $nome_post;?></td>
            </tr>
        </table>
        <?php
        }

        $conteudo .="<TABLE width='700' border='0' cellspacing='0' cellpadding='0' align='center'  name='relatorio' id='relatorio' class='tablesorter tabela' style='margin-top:0px;'>";
        $conteudox .="<TABLE width='700' border='0' cellspacing='0' cellpadding='0' align='center'  name='relatorio' id='relatorio' class='tablesorter tabela' style='margin-top:0px;'>";
        
        $conteudo .="<thead>";

        $conteudo .="<TR class='titulo_coluna'>";
        $conteudox .="<TR class='titulo_coluna'>";
        if ($login_fabrica == 171) {
            $conteudo .="<th style='background-color:#596d9b;text-align:center;padding-right: 15px'>Referência FN</th>";
            $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>Referência FN</td>";
        }

        if (in_array($login_fabrica, array(171))){
            $conteudo .="<th width='100' style='background-color:#596d9b;font: bold 11px 'Arial';color:#FFFFFF;text-align:center;padding-right: 15px'>Referência Grohe</th>";
            $conteudox .="<td width='100' style='background-color:#596d9b;font: bold 11px 'Arial';color:#FFFFFF;text-align:center;padding-right: 15px'>Referência Grohe</td>";
        }else{
            $conteudo .="<th width='100' style='background-color:#596d9b;font: bold 11px 'Arial';color:#FFFFFF;text-align:center;padding-right: 15px'>Referência</th>";
            $conteudox .="<td width='100' style='background-color:#596d9b;font: bold 11px 'Arial';color:#FFFFFF;text-align:center;padding-right: 15px'>Referência</td>";
        }
        
        $conteudo .="<th style='background-color:#596d9b;text-align:center;padding-right: 15px'>Peça</th>";
        $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>Peça</td>";

        if ($login_fabrica == 85) {
            $conteudo .="<th Peçastyle='background-color:#596d9b;text-align:center;padding-right: 15px'>Vlr Unitário</th>";
            $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>Vlr Unitário</td>";

            $conteudo .="<th style='background-color:#596d9b;text-align:center;padding-right: 15px'>Vlr Total</th>";
            $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>Vlr Total</td>";
	}

	

        if ($login_fabrica == 7 and strlen($classificacao)>0) {
            $conteudo .="<th style='background-color:#596d9b;text-align:center;padding-right: 15px'>Classificação</th>";
            $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>Classificação</td>";
        }
        if($login_fabrica == 42){
            $array_datas            = "";
            $meses_subs             = array("Jan/","Fev/","Mar/","Abr/","Mai/","Jun/","Jul/","Ago/","Set/","Out/","Nov/","Dez/");
            $dia_meses_subs         = array("01-","02-","03-","04-","05-","06-","07-","08-","09-","10-","11-","12-");
            $data_dia_primeiro      = explode("-",$aux_data_inicial);
            $pega_mes_final         = explode("-",$aux_data_final);
            if($data_dia_primeiro[1] == $pega_mes_final[1]){
                $array_datas[$mes_ano]  =  "'".$aux_data_inicial."' AND '".$aux_data_final."'";
            }else{
                $aux_data_intervalo     = date('Y-m-d',strtotime("+1 month",mktime(0,0,0,$data_dia_primeiro[1],1,$data_dia_primeiro[0])));
                $mes_ano                = date('m-Y',strtotime($aux_data_inicial));
                $array_datas[$mes_ano]  =  "'".$aux_data_inicial."' AND '".$aux_data_intervalo."'::date - interval '1 day'";
    		    $conteudo .= "<th width='100' style='background-color:#596d9b;text-align:center;padding-right:35px'>".str_replace($dia_meses_subs,$meses_subs,$mes_ano)."</th>";
                $conteudox .= "<td width='100' style='background-color:#596d9b;text-align:center;padding-right:35px'>".str_replace($dia_meses_subs,$meses_subs,$mes_ano)."</td>";

    		    while(strtotime($aux_data_intervalo) < strtotime($aux_data_final)){
        			$pega_mes_intervalo = "";
        			$mes_ano = date('m-Y',strtotime($aux_data_intervalo));
        			$aux_data_intervalo_ant = $aux_data_intervalo;
        			$aux_data_intervalo = date('Y-m-d',strtotime("+1 month",strtotime($aux_data_intervalo)));
        			$pega_mes_intervalo = explode("-",$aux_data_intervalo);

        			if($aux_data_intervalo <= $aux_data_final){
        			    $array_datas[$mes_ano] = "'".$aux_data_intervalo_ant."' AND '".$aux_data_intervalo."'::date - interval '1 day'";
        			}else{
        			    $array_datas[$mes_ano] = "'".$aux_data_intervalo_ant."' AND '".$aux_data_final."'";
        			}
        			$conteudo .= "<th width='100' style='background-color:#596d9b;text-align:center;padding-right:35px'>".str_replace($dia_meses_subs,$meses_subs,$mes_ano)."</th>";
                    $conteudox .= "<td width='100' style='background-color:#596d9b;text-align:center;padding-right:35px'>".str_replace($dia_meses_subs,$meses_subs,$mes_ano)."</td>";
    		    }
            }


            $conteudo .= "<th width='100' style='background-color:#596d9b;text-align:center;padding-right:35px'>Total Peças</th>";
            $conteudox .= "<td width='100' style='background-color:#596d9b;text-align:center;padding-right:35px'>Total Peças</td>";
        }else{
            $conteudo .="<th width='100' style='background-color:#596d9b;text-align:center;padding-right:35px' >Qtde. Peças</th>";
            $conteudox .="<td width='100' style='background-color:#596d9b;text-align:center;padding-right:35px' >Qtde. Peças</td>";
        }
        $conteudo .="<th width='50' style='background-color:#596d9b;font: bold 11px 'Arial';color:#FFFFFF;text-align:center;padding-right: 15px'>%</th>";
        $conteudox .="<td width='50' style='background-color:#596d9b;font: bold 11px 'Arial';color:#FFFFFF;text-align:center;padding-right: 15px'>%</td>";
        
        if ($mostra_peca=='mostra_peca' AND $areaAdminCliente != true){
            $conteudo .="<th width='50' style='background-color:#596d9b;font: bold 11px 'Arial';color:#FFFFFF;text-align:center;padding-right: 15px'>Custo</th>";
            $conteudox .="<td width='50' style='background-color:#596d9b;font: bold 11px 'Arial';color:#FFFFFF;text-align:center;padding-right: 15px'>Custo</td>";
        }

        if($login_fabrica == 24){
            if($tipo_data == 'extrato_geracao' OR $tipo_data == 'extrato_aprovacao'){
                $conteudo .="<th width='50' style='background-color:#596d9b;font: bold 11px 'Arial';color:#FFFFFF;text-align:center;padding-right: 15px'>Valor em Extrato</th>";
                $conteudox .="<td width='50' style='background-color:#596d9b;font: bold 11px 'Arial';color:#FFFFFF;text-align:center;padding-right: 15px'>Valor em Extrato</td>";
            }
        }
	
	if($login_fabrica == 161){
		$conteudo .="<th style='background-color:#596d9b;text-align:center;padding-right: 15px'>Peças Trocadas</th>";
		$conteudo .="<th style='background-color:#596d9b;text-align:center;padding-right: 15px'>Custo Peças Trocadas</th>";

		$conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>Peças Trocadas</td>";
		$conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>Custo Peças Trocadas</td>";
	}

    if ($telecontrol_distrib || $interno_telecontrol) {
        $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>FERRAMENTA 1</td>";
        $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>FERRAMENTA 2</td>";
        $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>FERRAMENTA 3</td>";
        $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>FERRAMENTA 4</td>";
        $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>FERRAMENTA 5</td>";
        $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>FERRAMENTA 6</td>";
        $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>FERRAMENTA 7</td>";
        $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>FERRAMENTA 8</td>";
        $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>FERRAMENTA 9</td>";
        $conteudox .="<td style='background-color:#596d9b;text-align:center;padding-right: 15px'>FERRAMENTA 10</td>";
    }

        $conteudo .="</TR>";
        $conteudox .="</TR>";
        $conteudo .="</thead>";
        $conteudo .="<tbody>";

        echo $cabecalho;
        echo $conteudo;
        if ($formato_arquivo=='CSV'){
            $conteudo = "";
            $conteudo .= "REFERÊNCIA;PEÇA;";
            if($login_fabrica == 42){
                foreach($array_datas as $key => $value){
                    $conteudo .= $key.";";
                }

                $conteudo .= "TOTAL PEÇAS;";
            }else{
                $conteudo .= "QTDE. PEÇAS;";
            }

            if ($areaAdminCliente == true) {
                $conteudo .= "%";
            } else {
                $conteudo .= "%;CUSTO";
            }
            
            if($login_fabrica == 24){
                if($tipo_data == 'extrato_geracao' OR $tipo_data == 'extrato_aprovacao'){
                    $conteudo .= ";VALOR EM EXTRATO";
                }
	    }

	    if($login_fabrica == 161){
		$conteudo .= ";PEÇAS TROCADAS;CUSTO PEÇAS TROCADAS";
	    }

            if($login_fabrica == 85) {
                $conteudo = "";
                $conteudo .= "REFERÊNCIA;PEÇA;UNITÁRIO;TOTAL;QTDE. PEÇAS;%;CUSTO";
            }
            if($login_fabrica == 171) {
                $conteudo = "";
                $conteudo .= "REFERÊNCIA FÁBRICA;REFERÊNCIA;PEÇA;UNITÁRIO;TOTAL;QTDE. PEÇAS;%;CUSTO";
            }

            if ($telecontrol_distrib || $interno_telecontrol) {
                $conteudo .= ";FERRAMENTA 1;FERRAMENTA 2;FERRAMENTA 3;FERRAMENTA 4;FERRAMENTA 5;FERRAMENTA 6;FERRAMENTA 7;FERRAMENTA 8;FERRAMENTA 9;FERRAMENTA 10 ";    
            }

            $conteudo .= "\n";
            fputs ($fp,$conteudo);
        } else {
            fputs ($fp,$conteudox);
        }
        
        $total_ocorrencia == 0;
        for ($x = 0; $x < pg_numrows($res); $x++) {
            $total_ocorrencia = $total_ocorrencia + pg_result($res,$x,qtde_pecas);
        }

        $total_custo_pecas      = 0;
        $total_pecas_trocadas   = 0;

        for ($i=0; $i<pg_numrows($res); $i++){
            $conteudo = "";
            $conteudox = "";
            $referencia_fabrica   = trim(pg_result($res,$i,referencia_fabrica));
            $referencia   = trim(pg_result($res,$i,referencia));
            $descricao    = trim(pg_result($res,$i,descricao));
            if($login_fabrica == 20 and $pais !='BR' and strlen($descricao)==0){
                $descricao    = "<font color = 'red'>Tradução não cadastrada.</font>";
            }
            $peca         = trim(pg_result($res,$i,peca));
            //$valor_peca   = trim(pg_result($res,$i,total_preco));
            /* O valor da peça nao está setado, entao pegar no CUSTO_PECA - HD 43710 42363 */
            $valor_peca   = trim(pg_result($res,$i,total_cp));

            $qtde_pecas   = trim(pg_result($res,$i,qtde_pecas));

            if ($total_ocorrencia > 0) $porcentagem = (($qtde_pecas * 100) / $total_ocorrencia);

            if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';}

            $total_peca  += $valor_peca;

            if ($login_fabrica == 7 and strlen($classificacao)>0) {
                $classificacao = pg_result($res,$i,classificacao);
            }

            $porcentagem = number_format($porcentagem,2,",",".");
            $valor_peca  = number_format($valor_peca,2,",",".");

            if($login_fabrica == 24){
                if($tipo_data == 'extrato_geracao' OR $tipo_data == 'extrato_aprovacao'){
                    $valor_peca   = trim(pg_result($res,$i,preco_peca));
                    $total_pecas = $valor_peca * $qtde_pecas;
                    $totalizador_peca += $total_pecas;
                }
            }

            if(in_array($login_fabrica,array(85,161))){
                $valor_peca = 0 ;
                $valor_peca   = trim(pg_result($res,$i,preco_peca));
                $total_pecas = $valor_peca * $qtde_pecas;
                $totalizador_peca += $total_pecas;
	    }

	    if($login_fabrica == 161){
		    $custo_troca	= trim(pg_result($res,$i,custo_troca));
		    if(strlen($custo_troca) == 0) $custo_troca = "0";
		$total_troca	= trim(pg_result($res,$i,total_troca));
	    }


            if ($lista_produtos and $login_fabrica==50) $produto = $lista_produtos;

            $conteudo .="<TR>";
            $conteudox .="<TR>";

            if ($login_fabrica == 171) {
                $conteudo .="<TD align='center' nowrap>".$referencia_fabrica."</TD>";
                $conteudox .="<TD align='center' nowrap>".$referencia_fabrica."</TD>";
            }

            $conteudo .="<TD align='left' nowrap>";

            $conteudo .="<a href='javascript:AbrePeca(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$posto\",\"$produto\",\"$pais\",\"$marca\",\"$tipo_data\",\"$aux_data_inicial\",\"$aux_data_final\",\"$exceto_posto\",\"$tipo_os\",\"$familia\",\"$revenda_nome\",\"$tipo_atendimento\",\"$nao_posto_interno\");'>";
            $conteudo .="$referencia</TD>";            
            $conteudox .="<TD align='left' nowrap>$referencia</TD>";

            $conteudo .="<TD align='left' nowrap>".stripslashes($descricao)."</TD>";
            $conteudox .="<TD align='left' nowrap>".stripslashes($descricao)."</TD>";

            if ($login_fabrica == 85) {
                $conteudo .="<TD align='center' nowrap>".number_format($valor_peca,2,",",".")."</TD>";
                $conteudox .="<TD align='center' nowrap>".number_format($valor_peca,2,",",".")."</TD>";

                $conteudo .="<TD align='center' nowrap>".number_format($total_pecas,2,",",".")."</TD>";
                $conteudox .="<TD align='center' nowrap>".number_format($total_pecas,2,",",".")."</TD>";
            }
            if ($login_fabrica == 7 and strlen($classificacao)>0) {
                $conteudo .="<TD align='center' nowrap>$classificacao</TD>";
                $conteudox .="<TD align='center' nowrap>$classificacao</TD>";
            }
            if($login_fabrica == 42){
                foreach($array_datas as $intervalo=>$between){
                    $sql_parcial = "SELECT  SUM(BI.qtde)                 AS ocorrencia_mes
                                    FROM    bi_os_item BI
                                    JOIN    tbl_peca PE ON  PE.peca    = BI.peca
                                                        AND PE.fabrica = $login_fabrica
                                    $join_classificacao
                                    WHERE   BI.fabrica = $login_fabrica
                                    AND     BI.$tipo_data BETWEEN $between
                                    $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_10 $cond_11
                                    AND     PE.peca = $peca
                              GROUP BY      PE.peca                              ,
                                            PE.referencia                        ,
                                            $group_classificacao
                                            PE.descricao
                                            $group_peca
                    ";
                    #echo nl2br($sql_parcial);exit;
                    $res_parcial = pg_query($con,$sql_parcial);
                    $conteudo .= "<td align='right'>".pg_fetch_result($res_parcial,0,ocorrencia_mes)."</td>";
                    $conteudox .= "<td align='right'>".pg_fetch_result($res_parcial,0,ocorrencia_mes)."</td>";
                }
            }
            $conteudo .="<TD align='center' nowrap>$qtde_pecas</TD>";
            $conteudox .="<TD align='center' nowrap>$qtde_pecas</TD>";

            $conteudo .="<TD align='right' nowrap title=''>$porcentagem</TD>";
            $conteudox .="<TD align='right' nowrap title=''>$porcentagem</TD>";

            if ($mostra_peca=='mostra_peca' AND $areaAdminCliente != true){
                $conteudo .="<TD align='center' nowrap>$valor_peca</TD>";
                $conteudox .="<TD align='center' nowrap>$valor_peca</TD>";
	    }


            if($login_fabrica == 24){
                if($tipo_data == 'extrato_geracao' OR $tipo_data == 'extrato_aprovacao'){
                    $conteudo .="<TD align='center' nowrap>".number_format($total_pecas,2,",",".")."</TD>";
                    $conteudox .="<TD align='center' nowrap>".number_format($total_pecas,2,",",".")."</TD>";
                }
            }
    	    
            if($login_fabrica == 161){
    		    
                $total_pecas_trocadas += $total_troca;
                $total_custo_pecas    += $custo_troca;

    		    $conteudo .="<TD align='center' nowrap>$total_troca</TD>";
    		    $conteudo .="<TD align='center' nowrap>".number_format($custo_troca,2,",",".")."</TD>";

    		    $conteudox .="<TD align='center' nowrap>$total_troca</TD>";
    		    $conteudox .="<TD align='center' nowrap>".number_format($custo_troca,2,",",".")."</TD>";
    	    }

            if ($telecontrol_distrib || $interno_telecontrol) {
                $sql_lista_basica = "SELECT tbl_produto.produto || ' - ' || tbl_produto.descricao AS prod_desc 
                                     FROM tbl_lista_basica 
                                     JOIN tbl_produto USING(produto) 
                                     WHERE peca = $peca 
                                     AND fabrica = $login_fabrica";
                $res_lista_basica = pg_query($con, $sql_lista_basica);
                if (pg_num_rows($res_lista_basica) > 0) {
                    for ($l=0; $l < pg_num_rows($res_lista_basica); $l++) { 
                        $conteudox .="<TD align='left' nowrap>".pg_fetch_result($res_lista_basica, $l, 'prod_desc')."</TD>";
                    }
                }
            }
            
            $conteudo .="</TR>";
            $conteudox .="</TR>";

            echo $conteudo;

            if ($formato_arquivo=='CSV'){
                $conteudo = "";
                $conteudo .= $referencia.";".$descricao.";";
                if($login_fabrica == 42){
                    foreach($array_datas as $intervalo=>$between){
                        $sql_parcial = "SELECT  SUM(BI.qtde)                 AS ocorrencia_mes
                                        FROM    bi_os_item BI
                                        JOIN    tbl_peca PE ON  PE.peca    = BI.peca
                                                            AND PE.fabrica = $login_fabrica
                                        $join_classificacao
                                        WHERE   BI.fabrica = $login_fabrica
                                        AND     BI.$tipo_data BETWEEN $between
                                        $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_10 $cond_11
                                        AND     PE.peca = $peca
                                GROUP BY      PE.peca                              ,
                                                PE.referencia                        ,
                                                $group_classificacao
                                                PE.descricao
                                                $group_peca
                        ";
                        #echo nl2br($sql_parcial);exit;
                        $res_parcial = pg_query($con,$sql_parcial);
                         $conteudo .= pg_fetch_result($res_parcial,0,ocorrencia_mes).";";
                    }
                }
                if ($areaAdminCliente == true) {
                    $conteudo .= $qtde_pecas.";".$porcentagem;
                } else {
                    $conteudo .= $qtde_pecas.";".$porcentagem.";".$valor_peca;
                }

                if($login_fabrica == 24){
                    if($tipo_data == 'extrato_geracao' OR $tipo_data == 'extrato_aprovacao'){
                        $conteudo .= ";".$valor_extrato;
                    }
		}

		if($login_fabrica == 161){
			$conteudo .= ";".$total_troca.";".number_format($custo_troca,2,",",".");
		}

                if($login_fabrica == 85) {
                    $conteudo = "";
                    $conteudo .= $referencia.";".$descricao.";".number_format($valor_peca,2,",",".").";".number_format($total_peca,2,",",".").";".$qtde_pecas.";".$porcentagem.";".$valor_peca;
                }

                if($login_fabrica == 171) {
                    $conteudo = "";
                    $conteudo .= $referencia_fabrica.";".$referencia.";".$descricao.";".number_format($valor_peca,2,",",".").";".number_format($total_peca,2,",",".").";".$qtde_pecas.";".$porcentagem.";".$valor_peca;
                }

                if ($telecontrol_distrib || $interno_telecontrol) {
                    $sql_lista_basica = "SELECT tbl_produto.produto || ' - ' || tbl_produto.descricao AS prod_desc 
                                         FROM tbl_lista_basica 
                                         JOIN tbl_produto USING(produto) 
                                         WHERE peca = $peca 
                                         AND fabrica = $login_fabrica";
                    $res_lista_basica = pg_query($con, $sql_lista_basica);
                    if (pg_num_rows($res_lista_basica) > 0) {
                        for ($l=0; $l < pg_num_rows($res_lista_basica); $l++) { 
                            $conteudo .= ";".pg_fetch_result($res_lista_basica, $l, 'prod_desc');
                        }
                    }
                }

                $conteudo .= ";\n";
                $soma_qtd_pecas = $soma_qtd_pecas + $qtde_pecas;

                fputs ($fp,$conteudo);
            } else {
                fputs ($fp,$conteudox);
            }
        }

        $conteudo = "";
        $conteudox = "";
        $total_ocorrencia  = number_format($total_ocorrencia,0,",",".");
        $total_peca        = number_format($total_peca,2,",",".");

        $conteudo .="</tbody>";
        $conteudo .= "<tfoot>";

        $conteudo  .= "<tr class='titulo_coluna'><td colspan='2'>TOTAL</td>";
        $conteudox .= "<tr class='titulo_coluna'><td colspan='2'>TOTAL</td>";

        if($login_fabrica == 85){
            $conteudo .="<td colspan='1' align='center'>&nbsp;</td>";
            $conteudox .="<td colspan='1' align='center'>&nbsp;</td>";

            $conteudo .="<td colspan='1' align='center'>&nbsp;</td>";
            $conteudox .="<td colspan='1' align='center'>&nbsp;</td>";
        }
        if($login_fabrica == 171){
            $conteudo .="<td colspan='1' align='center'>&nbsp;</td>";
            $conteudox .="<td colspan='1' align='center'>&nbsp;</td>";
        }
        $conteudo .="<td colspan='1' align='center'>$total_ocorrencia</td>";
        $conteudox .="<td colspan='1' align='center'>$total_ocorrencia</td>";

        $conteudo .="<td colspan='1' align='center'>&nbsp;</td>";
	$conteudox .="<td colspan='1' align='center'>&nbsp;</td>";

        if ($login_fabrica == 161) {

            $conteudo .="<td colspan='1' align='center'>{$total_pecas_trocadas}</td>";
            $conteudo .="<td colspan='1' align='center'>".number_format($total_custo_pecas,2,",",".")."</td>";

            $conteudox .="<td colspan='1' align='center'>{$total_pecas_trocadas}</td>";
            $conteudox .="<td colspan='1' align='center'>".number_format($total_custo_pecas,2,",",".")."</td>";

        }

        if ($mostra_peca=='mostra_peca' && $areaAdminCliente != true){
            $conteudo .="<td align='center'>$total_peca</td>";
            $conteudox .="<td align='center'>$total_peca</td>";
        }
        if($login_fabrica == 24){
                if($tipo_data == 'extrato_geracao' OR $tipo_data == 'extrato_aprovacao'){
                    $conteudo .="<td align='right' colspan='2'>".number_format($totalizador_peca,2,",",".")."</td>";
                    $conteudox .="<td align='right' colspan='2'>".number_format($totalizador_peca,2,",",".")."</td>";
                }
            }
        $conteudo .="</tr>";
        $conteudox .="</tr>";

        $conteudo .= "</tfoot>";

        $conteudo .=" </TABLE>";
        $conteudox .=" </TABLE>";


        echo $conteudo;
        if ($formato_arquivo == 'CSV'){
            $conteudo = "";
            $conteudo .= "total: ;"."".";".number_format($soma_qtd_pecas,0,",",".").";";

            if ($login_fabrica == 161) {
                $conteudo .= ";;{$total_pecas_trocadas};{$total_custo_pecas}";
            }

            $conteudo .= "\n";

            fputs ($fp,$conteudo);
        } else {
            fputs ($fp,$conteudox);
        }

        if ($formato_arquivo!='CSV'){
            fputs ($fp,"</body>");
            fputs ($fp,"</html>");
        }
        fclose ($fp);
        flush();
        echo ` cp $arquivo_completo_tmp $path `;
        echo "<script language='javascript'>";
        echo "document.getElementById('id_download').style.display='block';";
        echo "</script>";
        echo "<br>";

    }else{
        echo "<br>";
        echo "<b>Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais</b>";
    }
}

flush();

?>

<p>

<? include "../rodape.php" ?>
