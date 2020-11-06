<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {

    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $produto_referencia = $_POST['produto_referencia'];
    $produto_descricao  = $_POST['produto_descricao'];
    $natureza_chamado   = $_POST['natureza_chamado'];
    $status             = $_POST['status'];
    $nome_posto         = $_POST['descricao_posto'];
    $xatendente         = $_POST['xatendente'];
    $tipo_data          = $_POST['tipo_data'];
    $tipo_cliente       = $_POST['tipo_cliente'];
    $motivo_atendimento = $_POST['motivo_atendimento'];
    $estado             = $_POST['estado'];
    $hd_classificacao   = $_POST['hd_classificacao'];
    $tipo_protocolo     = $_POST['tipo_protocolo'];

    if($login_fabrica == 162){//HD-3352176
        $motivo_transferencia = $_POST['motivo_transferencia'];
    }

    $limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";

    if(in_array($login_fabrica, array(101,169,170))){
        $origem = $_POST["origem"];
        $cond_atend = "AND tbl_hd_chamado.atendente = $xatendente";
    }

    $cond_9 = "";
    
    if ($login_fabrica == 80) {
        
        $tipo = $_POST['tipo'];

        if ($tipo != "") {

            $cond_9 = " AND tbl_hd_chamado_extra.consumidor_revenda = '$tipo'";
        }

    }

    if($login_fabrica == 11 or $login_fabrica == 172){
        $linhaDeProduto     = $_POST['linha_produto'];
    }

    if($login_fabrica == 35){
        $tipo_atendimento = $_POST['tipo_atendimento'];
        if(strlen($tipo_atendimento) > 0){
            $sql_tipo_atendimento = ($tipo_atendimento == "1") ? " AND tbl_hd_chamado_extra.array_campos_adicionais = '{\"fale_conosco\":\"true\"}' " : " AND tbl_hd_chamado_extra.array_campos_adicionais is null ";
        }
    }

    $cond_1 = "";
    $cond_2 = "";
    $cond_3 = "";
    $cond_4 = "";
    $cond_5 = "";
    $cond_6 = "";

    if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
        $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
        $xdata_inicial = str_replace("'","",$xdata_inicial);
    }else{
        $msg_erro["msg"][]    ="Data Inválida";
        $msg_erro["campos"][] = "data_inicial";
    }

    if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
        $xdata_final =  fnc_formata_data_pg(trim($data_final));
        $xdata_final = str_replace("'","",$xdata_final);
    }else{
         $msg_erro["msg"][]    ="Data Inválida";
        $msg_erro["campos"][] = "data_final";
    }

    if(!count($msg_erro["msg"])){
        $dat = explode ("/", $data_inicial );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];

        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data_inicial";
        }
    }
    if(!count($msg_erro["msg"])){
        $dat = explode ("/", $data_final );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data_final";
        }
    }

    if($xdata_inicial > $xdata_final) {
        $msg_erro["msg"][]    ="Data Inicial maior que final";
        $msg_erro["campos"][] = "data_inicial";
    }

    if (strlen(trim($xdata_final)) > 0 AND strlen(trim($xdata_inicial)) > 0){
    	$sql = "SELECT '$xdata_final'::date - '$xdata_inicial'::date";
    	$res = pg_query($con,$sql);
        $resDias = pg_fetch_result($res,0,0);

        $qtdeDias = 186;
        $xMeses = "6 meses";

        if ($login_fabrica == 24) {

            $qtdeDias = 549;
            $xMeses = "18 meses";
        }

    	if($resDias > $qtdeDias) {
    	    $msg_erro["msg"][] = traduz("O intervalo não pode ser maior que %",null, null, $xMeses);
            $msg_erro["campos"][] = "data_inicial";
    	}
    }
    if (strlen($xatendente)>0){
        $cond_atend = "AND tbl_hd_chamado.atendente = $xatendente";
    }

    if(strlen($produto_referencia)>0){
        $sql = "SELECT produto from tbl_produto where referencia='$produto_referencia' limit 1";
        $res = pg_exec($con,$sql);
        if(pg_numrows($res)>0){
            $produto = pg_result($res,0,0);
            if ($login_fabrica == 151) {
                $cond_1 = " AND (tbl_hd_chamado_item.produto = $produto ";
                $sql_join .= "JOIN tbl_hd_chamado_item on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado";
            } else {
                $cond_1 = " and tbl_hd_chamado_extra.produto = $produto ";
            }
        }
    }
    if(strlen($natureza_chamado)>0){

        if($natureza_chamado =='Reclamação') {
            $xnatureza_chamado="('reclamacao_at','reclamacao_empresa','reclamacao_produto')";
        }elseif($natureza_chamado =='Dúvida') {
            $xnatureza_chamado="('duvida_produto')";
        }else{
            $xnatureza_chamado="('$natureza_chamado')";
        }

        $cond_2 = " and tbl_hd_chamado.categoria in $xnatureza_chamado ";
    }elseif($login_fabrica == 85 and strlen(trim($natureza_chamado))==0){
        $cond_2 = " and tbl_hd_chamado.categoria <> 'garantia_estendida' ";
    }

    if($login_fabrica == 162 AND strlen($motivo_transferencia) > 0){ //HD-3352176
        $cond_hd_situacao = "AND tbl_hd_chamado_extra.hd_situacao = $motivo_transferencia";
    }

    if(strlen($status)>0){
        $cond_3 = (in_array($login_fabrica, array(136))) ? " and tbl_hd_chamado.status = '$status'  " : " and fn_retira_especiais(tbl_hd_chamado.status) = fn_retira_especiais('$status')  ";
    }
    if($login_fabrica==6){
        $cond_4 = " and tbl_hd_chamado.status <> 'Cancelado'  ";
    }
    if(strlen($nome_posto)>0){
        $sql="SELECT posto FROM tbl_posto_fabrica join tbl_posto using(posto) where fabrica=$login_fabrica and trim(nome)='$nome_posto'";
        $res=pg_exec($con,$sql);
        $posto=pg_result($res,0,0);
        if(strlen($posto) >0 ){
            $cond_5 = " and tbl_hd_chamado_extra.posto=$posto ";
        }
    }

    if($login_fabrica==2){
        $condicoes = $produto . ";" . $natureza_chamado . ";" . $status . ";" . $posto . ";" . $xdata_inicial . ";" .$xdata_final;
    }

    $cond_6=" and tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ";

    if($login_fabrica == 11 or $login_fabrica == 172){
        $sql_join = " JOIN (select hd_chamado, max(tbl_hd_chamado_item.data) as data FROM tbl_hd_chamado_item JOIN tbl_hd_chamado USING(hd_chamado) WHERE fabrica_responsavel = $login_fabrica GROUP BY hd_chamado) hi ON hi.hd_chamado = tbl_hd_chamado.hd_chamado ";
        $cond_6 = " AND hi.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ";
    }

    if(strlen($tipo_data) > 0){
        if($tipo_data =='abertura') {
            $cond_6= " and tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ";
        } elseif($tipo_data =='interacao'){
            $sql_join = " LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado= tbl_hd_chamado_item.hd_chamado ";
            $cond_6=" and tbl_hd_chamado_item.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ";
        }
    }

    if(!empty($tipo_cliente)) {
        $cond_7 = " AND tbl_hd_chamado_extra.consumidor_revenda = '$tipo_cliente'";
    }

    if(!empty($motivo_atendimento)) {
        if ($login_fabrica == 50) {
            $motivo_atendimento = implode(",", $motivo_atendimento);
        }
        $cond_8 = " AND tbl_hd_chamado_extra.hd_motivo_ligacao in ($motivo_atendimento)";
    }

    if(!empty($linhaDeProduto)){
        $sqlJoinProdutoLinha = " join tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto and
                                                     tbl_produto.linha = $linhaDeProduto ";
    }

    if(!empty($estado)){
        $sql_join .= " JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto
                            AND tbl_posto_fabrica.fabrica = $login_fabrica
                            AND tbl_posto_fabrica.contato_estado = '$estado' ";
    }

    if($login_fabrica == 74){
        $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
    }

    if (strlen($hd_classificacao)>0){
        $cond_class = "AND tbl_hd_chamado.hd_classificacao = $hd_classificacao";
    }

    $sql = "SELECT fn_retira_especiais(tbl_hd_chamado.status) AS status_parametro,
                    tbl_hd_chamado.status,
                    count( distinct tbl_hd_chamado.hd_chamado) as qtde
            FROM tbl_hd_chamado
            JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
            $sql_join ";

        if($login_fabrica == 11 or $login_fabrica == 172){

            $sql .= $sqlJoinProdutoLinha;
        }

        if(in_array($login_fabrica, array(101,169,170)) and strlen(trim($origem))>0){
            if(in_array($login_fabrica, array(169,170))){
                $cond_origem = "and tbl_hd_chamado_extra.hd_chamado_origem = $origem ";
            }else{
                $cond_origem = "and tbl_hd_chamado_extra.origem = '$origem' ";
            }
        }

        if(in_array($login_fabrica, array(169,170)) and strlen(trim($tipo_protocolo)) > 0){
            $cond_tipo_protocolo = " and tbl_hd_chamado.hd_tipo_chamado = $tipo_protocolo ";
        }


        $sql .= "
                WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		        AND tbl_hd_chamado.status is not null
                AND tbl_hd_chamado.titulo NOT IN('Help-Desk Posto','Atendimento Revenda')
                $cond_1
                $cond_2
                $cond_3
                $cond_4
                $cond_5
                $cond_6
                $cond_7
                $cond_8
                $cond_atend
                $cond_class
                $sql_tipo_atendimento
                $cond_admin_fale_conosco
                $cond_origem
                $cond_hd_situacao
                $cond_9
                $cond_tipo_protocolo
                GROUP BY tbl_hd_chamado.status
                ORDER by qtde desc
                $limit
				";
        if(count($msg_erro) == 0) {

			$resSubmit = pg_query($con,$sql);
		}

    /*
    if ($_POST["gerar_excel"]) {
        if (pg_num_rows($resSubmit) > 0) {
            $data = date("d-m-Y-H:i");
            $fileName = "callcenter_relatorio_atendimento-{$data}.xls";

            $file = fopen("/tmp/{$fileName}", "w");

            $thead = "
            <table border='1'>
                    <thead>
                        <tr>
                            <th colspan='2' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
                                RELATÓRIO DE ATENDIMENTOS
                            </th>
                        </tr>
                        <tr>
                        </tr>
                    </thead>
                    <tbody>
            ";
            fwrite($file, $thead);

            if(strlen($nome_posto)>0){
                $body .= "<TR >
                        <td>Posto</TD>
                        <td >$nome_posto</TD>
                        </TR >";
            }
             $body .= "<TR class='titulo_coluna'>
                        <td align='left'>Status</TD>
                        <TD>Qtde</TD>
                        </TR >\n";
            for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
                $status_desc        = pg_result($resSubmit,$i,status);
                $status_parametro   = pg_result($resSubmit,$i,status_parametro);
                $qtde               = pg_result($resSubmit,$i,qtde);
                $total              = $total + $qtde;
                if ($i % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}
                $body .= "
                <TR bgcolor='$cor'>
                <TD align='left' nowrap>$status_desc</TD>
                <TD align='center' nowrap>$qtde</TD>
                </TR >
                ";
            }
            $body .= "</tbody>";
            fwrite($file,$body);

            $foot .= "
            <tfoot>
            <TR class='titulo_coluna'>
                <TD align='center' nowrap><B>Total</B></TD>
                <TD align='center' nowrap>$total</TD>
            </TR >
            </tfoot>
            </table>";
            fwrite($file,$foot);
            fclose($file);

            if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} xls/{$fileName}");

                echo "xls/{$fileName}";
            }
        }
        exit;
    }
    */
}

$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

if ($login_fabrica == 180) {
    // Argentina
    $array_estado = ['Buenos Aires','Catamarca','Chaco','Chubut','Córdoba',             'Corrientes','Entre Ríos','Formosa','Jujuy','La Pampa',             'La Rioja','Mendoza','Misiones','Neuquén','Río Negro',             'Salta','San Juan','San Luis','Santa Cruz','Santa Fe',             'Santiago del Estero','Terra do Fogo','Tucumán'];
}

if ($login_fabrica == 181) {
    //Colombia
    $array_estado = ["Distrito Capital","Amazonas","Antioquia","Arauca","Atlántico", 
            "Bolívar","Boyacá","Caldas","Caquetá","Casanare",
            "Cauca","Cesar","Chocó", "Córdoba","Cundinamarca","Guainía",
            "Guaviare","Huila","La Guajira","Magdalena","Meta","Nariño",
            "Norte de Santander","Putumayo","Quindío","Risaralda",
            "San Andrés e Providencia","Santander","Sucre","Tolima",
            "Valle del Cauca","Vaupés","Vichada"];
}

if ($login_fabrica == 182) {
    //Peru
    $array_estado = ["Amazonas","Ancash","Apurímac","Arequipa","Ayacucho",
            "Cajamarca","Callao","Cusco","Huancavelica","Huánuco",
            "Ica","Junin","La Libertad","Lambayeque","Lima","Loreto",
            "Madre de Dios","Moquegua","Pasco","Piura","Puno","San Martín",
            "Tacna","Tumbes","Ucayali"];

}

$layout_menu = "callcenter";
$title = traduz("RELATÓRIO DE ATENDIMENTO");

include "cabecalho_new.php";

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

<script type="text/javascript">
    function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo,posto,atendente,tipo_data,tipo_cliente, motivo_atendimento,linhaDeProduto,tipo_atendimento,motivo_transferencia,origem, hd_classificacao, tipo_os){
        janela = window.open("callcenter_relatorio_atendimento_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+"&tipo="+tipo+"&atendente="+atendente+"&posto="+posto+"&tipo_data="+tipo_data+"&motivo_atendimento="+motivo_atendimento+"&linhaDeProduto="+linhaDeProduto+"&tipo_atendimento="+tipo_atendimento+"&motivo_transferencia="+motivo_transferencia+"&tipo_cliente="+tipo_cliente+"&origem="+origem+"&hd_classificacao="+hd_classificacao+"&tipo_os="+tipo_os, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
        janela.focus();
    }

    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("produto", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        <? if ($login_fabrica == 50) { ?>

        $("#motivo_atendimento").multiselect({
        selectedText: "# de # opções"

		});
        <? } ?>

	});

    function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }

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

<!-- <script type="text/javascript" src="js/grafico/highcharts.js"></script> -->
<script type="text/javascript" src="js/novo_highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>

<div class="row">
    <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios ')?></b>
</div>
<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div>
    <br/>

	<div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

<?
	if($login_fabrica==59) {
?>
    <div class='row-fluid'>
        <div class='span2'></div>

        <div class='span4'>
                <label class="radio">
                <input type="radio" name="tipo_data" value="abertura" checked>
                <?=traduz('Data Abertura')?>
            </label>
        </div>
        <div class='span4'>
            <label class="radio">
                <input type="radio" name="tipo_data" value="interacao" <?php if($tipo_data == "fechamento") echo "checked"; ?> >
                <?=traduz('Data Interação')?>
            </label>
        </div>
        <div class='span2'></div>
    </div>
	<?
	}
	?>

	<div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'><?=traduz('Ref. Produto')?></label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_descricao'><?=traduz('Descrição Produto')?></label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

<?
/*			if ($login_fabrica == 11) {
				echo "<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor:pointer' onclick=\"javascript: fnc_pesquisa_produto_linha (document.frm_relatorio.linhas_produto, document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')\">";
			}else{
				echo "<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor:pointer' onclick=\"javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao','referencia')\">";
			}

			if($login_fabrica == 11){
				echo "<img src='imagens/lupa.png'  style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto_linha (document.frm_relatorio.linhas_produto, document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')\">";

			}else{
				echo "<img src='imagens/lupa.png'  style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')\">";
			}
*/
?>
    <?php if ($login_fabrica != 178){ ?>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='natureza'><?php echo ( $login_fabrica == 101) ? traduz("Natureza/Motivo de Contato"): traduz("Natureza")?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="natureza_chamado" id="natureza_chamado">
                            <option value=""></option>
                            <?php
                            $sqlx = "SELECT nome,
                                            descricao
                                    FROM    tbl_natureza
                                    WHERE fabrica=$login_fabrica
                                    AND ativo = 't'
                                    ORDER BY nome";

                            $resx = pg_exec($con,$sqlx);

                            foreach (pg_fetch_all($resx) as $key) {
                                $selected_natureza = ( isset($natureza_chamado) and ($natureza_chamado == $key['nome']) ) ? "SELECTED" : '' ;

                            ?>
                                <option value="<?php echo $key['nome']?>" <?php echo $selected_natureza ?> >

                                    <?php echo $key['descricao']?>

                                </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='status'><?=traduz('Status')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="status" id="status">
                            <option value=""></option>
                            <?php

                                $sql = "select status, status AS status_desc from tbl_hd_status where fabrica = $login_fabrica order by status";
                                $res = pg_exec($con,$sql);
                                foreach (pg_fetch_all($res) as $key) {

                                    $key['status'] = ($key['status']);
                                    $key['status_desc'] = ($key['status_desc']);

                                    $selected_status = ( isset($status) and ($status== $key['status']) ) ? "SELECTED" : '' ;

                                ?>
                                    <option value="<?php echo $key['status']?>" <?php echo $selected_status ?> >
                                        <?php echo $key['status_desc']; ?>
                                    </option>


                                <?php
                                }

                            ?>
                        </select>
                    </div>
                    <div class='span2'></div>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
        
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'><?=traduz('Código Posto')?></label>
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
                <label class='control-label' for='descricao_posto'><?=traduz('Nome Posto')?></label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
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
            <div class='control-group '>
                <label class='control-label' for='xatendente'><?=traduz('Atendente')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="xatendente" id="xatendente">
                            <option value=""></option>
						<?

                            if($login_fabrica == 74){

                                $tipo = "producao"; // teste - producao

                                $admin_fale_conosco = ($tipo == "producao") ? 6409 : 6437;

                                $cond_admin_fale_conosco = " AND tbl_admin.admin NOT IN ($admin_fale_conosco) ";

                            }

                            $sql = "SELECT admin, nome_completo
									from tbl_admin
									where fabrica = $login_fabrica
									and ativo is true
									and (privilegios like '%call_center%' or privilegios like '*')
                                    $cond_admin_fale_conosco
                                    order by login";
							$res = pg_exec($con,$sql);
							foreach (pg_fetch_all($res) as $key) {

                                    $selected_atendente = ( isset($xatendente) and ($xatendente == $key['admin']) ) ? "SELECTED" : '' ;

?>
                            <option value="<?php echo $key['admin']?>" <?php echo $selected_atendente ?> >
                                <?php echo $key['nome_completo']?>
                            </option>
<?php
                            }

?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
<?php if($login_fabrica == 101){?>
    <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='xorigin'><?=traduz('Origem')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="origem" id="xorigem">
                            <option value=''>Escolha</option>
                            <option value='Telefone' <?PHP if ($origem == 'Telefone') { echo "Selected";}?>><?=traduz('Telefone')?></option>
                            <?php if( $login_fabrica == 101 ){?>
                                <option value='ecommerce' <?PHP if ($origem == 'ecommerce') { echo "Selected";}?>>E-Commerce </option>
                            <?php } ?>
                            <option value='Email' <?PHP if ($origem == 'Email') { echo "Selected";}?>><E-mail</option>
                            <option value='whatsapp' <?PHP if ($origem == 'whatsapp'){ echo "Selected";}?>>WhatsApp</option>
                            <option value='facebook' <?PHP if ($origem == 'facebook') { echo "Selected";}?>>Facebook</option>
                            <option value='reclame_aqui' <?PHP if ($origem == 'reclame_aqui') { echo "Selected";}?>>Reclame Aqui </option>
                            <option value='procon' <?PHP if ($origem == 'procon') { echo "Selected";}?>>Procon </option>
                            <option value='jec' <?PHP if ($origem == 'jec') { echo "Selected";}?>>JEC </option>

                        </select>
                    </div>
                </div>
            </div>
        </div>
<?php } ?>
<?php if(in_array($login_fabrica, array(169,170))){ ?>
    <div class='span4'>
        <div class='control-group '>
            <label class='control-label' for='xorigin'><?=traduz('Origem')?></label>
            <div class='controls controls-row'>
                <div class='span4'>
                    <select name="origem">
                        <option value=""></option>
                        <?php
                            $sql = "SELECT hd_chamado_origem,descricao
                                        FROM tbl_hd_chamado_origem
                                        WHERE fabrica = $login_fabrica
                                        ORDER BY descricao";
                            $res = pg_query($con,$sql);

                            foreach (pg_fetch_all($res) as $key) {
                                $selected_origem = ( isset($origem) and ($origem == $key['hd_chamado_origem']) ) ? "SELECTED" : '' ;
                        ?>
                            <option value="<?php echo $key['hd_chamado_origem']?>" <?php echo $selected_origem ?> >
                                <?php echo $key['descricao']?>
                            </option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
<?php }?>

<?php
if($login_fabrica == 11 or $login_fabrica == 172){
?>
        <div class='span4'>
            <div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='linha_produto'><?=traduz('Linha')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="linha_produto" id="linha_produto">
                            <option value=""></option>
                            <?php
                            $sql = "SELECT linha, nome
                                    FROM tbl_linha
                                    WHERE fabrica = $login_fabrica
                                    AND ativo";
                            $res = pg_query($con,$sql);

                            foreach (pg_fetch_all($res) as $key) {
                                $selected_linha = ( isset($linhaDeProduto) and ($linhaDeProduto == $key['linha']) ) ? "SELECTED" : '' ;

                            ?>
                                <option value="<?php echo $key['linha']?>" <?php echo $selected_linha ?> >

                                    <?php echo $key['nome']?>

                                </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
<?php
}

if($login_fabrica == 151 || $classificacaoHD){
?>
        <div class='span4'>
            <div class='control-group <?=(in_array("hd_classificacao", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='hd_classificacao'><?=traduz('Classificação')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="hd_classificacao" id="hd_classificacao">
                            <option value=""></option>
                            <?php
                            $sql = "SELECT hd_classificacao, descricao
                                    FROM tbl_hd_classificacao
                                    WHERE fabrica = $login_fabrica";
                            $res = pg_query($con,$sql);

                            foreach (pg_fetch_all($res) as $key) {
                                $selected_classificacao = ( isset($hd_classificacao) and ($hd_classificacao == $key['hd_classificacao']) ) ? "SELECTED" : '' ;

                            ?>
                                <option value="<?php echo $key['hd_classificacao']?>" <?php echo $selected_classificacao ?> >

                                    <?php echo $key['descricao']?>

                                </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
<?php
}

if ($login_fabrica == 80) { ?>

<div class='row-fluid'>
    <div class='span2'></div>
</div>

<div class='row-fluid'>
    <div class='span2'></div>
    <div class='span4'>
        <div class='control-group '>
            <label class='control-label' for='xatendente'>Tipo</label>
            <div class='controls controls-row'>
                <div class='span4'>
                    <select id="tipo" name="tipo">
                        <option value=""></option>
                        <option value="R" <?php if ($_POST['tipo'] == "R") { echo "selected"; } ?>>Revenda</option>
                        <option value="C" <?php if ($_POST['tipo'] == "C") { echo "selected"; } ?>>Consumidor</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class='span2'></div>   
</div>

<?php }

if($login_fabrica ==86) {
?>
		<div class='span4'>
            <div class='control-group '>
				<label class='control-label' for='tipo_cliente'><?=traduz('Tipo Cliente')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="tipo_cliente" id="tipo_cliente">
                            <option value=""></option>
                            <option value='C' <? if($tipo_cliente == "C") echo "Selected" ;?>><?=traduz('Consumidor')?></option>
                            <option value='R' <? if($tipo_cliente == "R") echo "Selected" ;?>><?=traduz('Revenda')?></option>
                            <option value='T' <? if($tipo_cliente == "T") echo "Selected" ;?>><?=traduz('Representante')?></option>
                            <option value='N' <? if($tipo_cliente == "N") echo "Selected" ;?>><?=traduz('Consultor')?></option>
                            <option value='P' <? if($tipo_cliente == "P") echo "Selected" ;?>><?=traduz('PA')?></option>
						</select>
                    </div>
                </div>
            </div>
        </div>
<?
}

if(in_array($login_fabrica, [74,152,180,181,182])) {
?>
		<div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='estado'><?=traduz("Estado")?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="estado" id="estado">

                        </select>
                    </div>
                </div>
            </div>
        </div> 
<?php
}
 
if ($login_fabrica == 35) {
?>
		<div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='tipo_atendimento'><?=traduz('Origem Atendimento')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="tipo_atendimento" id="tipo_atendimento">
							<option value=''></option>
							<option value='1' <?php echo (strlen($tipo_atendimento) > 0 && $tipo_atendimento == "1") ? "selected" : ""; ?>>Interativo</option>
							<option value='2' <?php echo (strlen($tipo_atendimento) > 0 && $tipo_atendimento == "2") ? "selected" : ""; ?>>Callcenter</option>
						</select>
					</div>
                </div>
            </div>
        </div>
<?
}
if($login_fabrica == 162){ //HD-3352176
?>
    <div class='span4'>
        <div class='control-group <?=(in_array("motivo_transferencia", $msg_erro["campos"])) ? "error" : ""?>'>
            <label class='control-label' for='motivo_transferencia'><?=traduz('Motivos da Transferência')?></label>
            <div class='controls controls-row'>
                <div class='span4'>
                    <select name="motivo_transferencia" id="motivo_transferencia">
                        <option value=""></option>
                        <?php
                            $sql = "SELECT hd_situacao,descricao,ativo
                                        FROM tbl_hd_situacao
                                        WHERE fabrica = $login_fabrica
                                        ORDER BY descricao";
                            $res = pg_query($con,$sql);

                            foreach (pg_fetch_all($res) as $key) {
                                $selected_motivo_transferencia = ( isset($motivo_transferencia) and ($motivo_transferencia == $key['hd_situacao']) ) ? "SELECTED" : '' ;
                        ?>
                            <option value="<?php echo $key['hd_situacao']?>" <?php echo $selected_motivo_transferencia ?> >
                                <?php echo $key['descricao']?>
                            </option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

<?
}
?>

        <div class='span2'></div>
    </div>
		<?  if(in_array($login_fabrica,array(74,86,151,50))) {
            $titulo_combo = ($login_fabrica == 74) ? traduz("Classe do atendimento") : traduz("Motivo");
            $titulo_combo = ($login_fabrica == 151) ? traduz("Providência") : $titulo_combo;
            $titulo_combo = ($login_fabrica == 50) ? traduz("Tipo de Atendimento:") : $titulo_combo;
		?>
	<div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class='control-group '>
				<label class='control-label' for='motivo_atendimento'><?=$titulo_combo?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <?php
                            if ($login_fabrica == 50) { ?>

                                <select name='motivo_atendimento[]' id='motivo_atendimento' multiple="multiple">

                            <? } else { ?>

                                <select name='motivo_atendimento' id='motivo_atendimento'>
                                <option></option>

                            <? } ?>

<?
                    $sql = "SELECT descricao, array_to_string(array_agg(hd_motivo_ligacao), ', ') as hd_motivo_ligacao FROM tbl_hd_motivo_ligacao WHERE fabrica = $login_fabrica GROUP BY descricao ORDER BY descricao";
					$res = pg_query($con,$sql);

                    foreach (pg_fetch_all($res) as $key) {
                        $selected_motivo = ( isset($motivo_atendimento) and ($motivo_atendimento == $key['hd_motivo_ligacao']) ) ? "SELECTED" : '' ;
                    ?>
                                <option value="<?php echo $key['hd_motivo_ligacao']?>" <?php echo $selected_motivo ?> >

                                <?php echo $key['descricao']?>

                                </option>
<?php
                    }
?>

                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
<?
            }
?>
    
    <?php if ($login_fabrica == 178){ ?>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='status'>Status</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="status" id="status">
                            <option value=""></option>
                            <?php

                                $sql = "select status, status AS status_desc from tbl_hd_status where fabrica = $login_fabrica order by status";
                                $res = pg_exec($con,$sql);
                                foreach (pg_fetch_all($res) as $key) {

                                    $key['status'] = ($key['status']);
                                    $key['status_desc'] = ($key['status_desc']);

                                    $selected_status = ( isset($status) and ($status== $key['status']) ) ? "SELECTED" : '' ;

                                ?>
                                    <option value="<?php echo $key['status']?>" <?php echo $selected_status ?> >
                                        <?php echo $key['status_desc']; ?>
                                    </option>


                                <?php
                                }

                            ?>
                        </select>
                    </div>
                    <div class='span2'></div>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <?php if (in_array($login_fabrica,[169,170])){ ?>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='tipo_protocolo'>Tipo Protocolo</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="tipo_protocolo" id="tipo_protocolo">
                            <option value=""></option>
                            <?php

                                $sql = "SELECT hd_tipo_chamado, descricao FROM tbl_hd_tipo_chamado WHERE fabrica = {$login_fabrica} AND ativo ORDER BY descricao";
                                $res = pg_exec($con,$sql);
                                foreach (pg_fetch_all($res) as $key) {

                                    $key['hd_tipo_chamado'] = ($key['hd_tipo_chamado']);
                                    $key['descricao'] = ($key['descricao']);

                                    $selected_tipo = ( isset($tipo_protocolo) and ($tipo_protocolo== $key['hd_tipo_chamado']) ) ? "SELECTED" : '' ;

                                ?>
                                    <option value="<?php echo $key['hd_tipo_chamado']?>" <?php echo $selected_tipo ?> >
                                        <?php echo $key['descricao']; ?>
                                    </option>


                                <?php
                                }

                            ?>
                        </select>
                    </div>
                    <div class='span2'></div>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</FORM>

<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        echo "<br />";
        $count = pg_num_rows($resSubmit);
?>
<table id="callcenter_relatorio_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <TR class='titulo_coluna'>
<?php
			if(strlen($nome_posto)>0){
?>
            <th colspan="2"><?=traduz('Posto')?></th>
        </TR >
        <TR >
            <th colspan="2"><?=$nome_posto?></Th>
        </TR >
<?
            }
?>
        <TR class='titulo_coluna'>
			<th align='left'><?=traduz('Status')?></TD>
			<th>Qtde</th>
        </TR >
    </thead>
<?
			$grafico_topo = "<script>
					var chart;
					$(document).ready(function() {
						chart = new Highcharts.Chart({
							chart: {
								renderTo: 'container',
								plotBackgroundColor: 0,
								plotBorderWidth: 0,
								plotShadow: true,
								margin: [30, 0, 0, 250]
							},
							title: {
								text: ''
							},
							tooltip: {
								formatter: function() {
									return '<b>'+ this.point.name +'</b>: '+ this.y +' %';
								}
							},
							plotOptions: {
								pie: {
                                    size: 200,
									allowPointSelect: true,
									cursor: 'pointer',
									dataLabels: {
										enabled: false
									},
									showInLegend: true
								}
							},
							dataLabels: {
							enabled: true
							},
							legend: {
								layout: 'vertical',
								align: 'left',
								x: 0,
								verticalAlign: 'top',
								y: 0,
								floating: false,
								backgroundColor: '#FFFFFF',
								borderColor: '#CCC',
								borderWidth: 1,
								shadow: false
							},
							series: [{
								type: 'pie',
								name: 'Browser share',
								data: [";
			$ttl_resultado = pg_numrows($resSubmit);
			$ttl_resultado = $ttl_resultado - 1;

			for($x=0;pg_numrows($resSubmit)>$x;$x++){
				$qtdes   = pg_result($resSubmit,$x,qtde);

				$total_soma = $total_soma  + $qtdes;
			}

			for($y=0;pg_numrows($resSubmit)>$y;$y++){
				$status_desc = pg_result($resSubmit,$y,status);
				$status_parametro = pg_result($resSubmit,$y,status_parametro);
				$qtde   = pg_result($resSubmit,$y,qtde);
				$grafico_status[] = $status_desc;
				$grafico_qtde[] = $qtde;
				$valor_qtde[] = "25";
				$total = $total + $qtde;
				$vergula ="";

				$resultato_porc ="";



				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}
?>
    <tbody>
        <TR bgcolor='<?=$cor?>'>
            <TD class='tal'><a href="javascript: AbreCallcenter('<?=$xdata_inicial?>','<?=$xdata_final?>','<?=$produto?>','<?=$natureza_chamado?>','<?=$status_parametro?>','<?=$xperiodo?>','<?=$posto?>','<?=$xatendente?>','<?=$tipo_data?>','<?=$tipo_cliente?>','<?=$motivo_atendimento?>', '<?=$linhaDeProduto?>', '<?=$tipo_atendimento?>','<?=$motivo_transferencia?>','<?=$origem?>', '<?=$hd_classificacao?>', '<?=$tipo?>')"><?=$status_desc?></a></TD>
			<TD class='tac'><?=$qtde?></TD>
        </TR >
<?
				if($y < $ttl_resultado){
					$vergula =",";
				}
				$resultato_porc = ($qtde / $total_soma) * 100;
				$resultato_porc = number_format($resultato_porc, 2);
				$grafico_conteudo = $grafico_conteudo."['$status_desc - $resultato_porc%',$resultato_porc]$vergula";

			}

			$grafico_rodape ="],
						dataLabels: {
						enabled: true,
						color: '#000000',
						connectorColor: '#000000'
						}
					}]
				});
			});
			</script>";
?>
    </tbody>
    <tfoot>
        <TR class='titulo_coluna'>
            <TD class='tac'><B><?=traduz('Total')?></B></TD>
            <TD class='tac'><?=$total?></TD>
        </TR >
    </tfoot>
</table>

            <?php
            if ($count > 50) {
            ?>
                <script>
                    $.dataTableLoad({ table: "#callcenter_relatorio_atendimento" });
                </script>
            <?php
            }
            ?>
		<br />

            <?php
            echo $grafico_topo.$grafico_conteudo.$grafico_rodape;

		}else{
			echo "<div class='container'>
            <div class='alert'>
                    <h4>".traduz('Nenhum resultado encontrado')."</h4>
            </div>
            </div>";
		}
	}
?>
		<div id="container" style="width: 700px; height: 400px; margin: 0 auto"></div>

<? include "rodape.php" ?>
