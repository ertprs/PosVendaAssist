<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

$msg = "";
//9/1/2008 HD 7667 and $login_posto <> "6359"
if ($login_fabrica == 1 or ( $login_fabrica ==15)) {
	header ("Location: os_consulta_avancada.php");
	exit;
}
// pega os valores das variaveis dadas como parametros de pesquisa e coloca em um cookie
setcookie("cookredirect", $_SERVER["REQUEST_URI"]); // expira qdo fecha o browser

$os = $_GET["excluir"];

if (in_array($login_fabrica, array(137,141))) {// Verifica se o posto é Interno

    $sql = "SELECT posto
            FROM tbl_posto_fabrica
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica AND tbl_tipo_posto.posto_interno
            WHERE tbl_posto_fabrica.fabrica = " . $login_fabrica . "
            AND tbl_posto_fabrica.posto = " . $login_posto;
    $res = pg_query($con,$sql);

    if( pg_num_rows($res) > 0) {

        $posto_interno = true;

    }else{

        $posto_interno = false;

    }

}

if(isset($_POST["alterar_codigo_rastreio"]) && $_POST["alterar_codigo_rastreio"] == "ok"){

    $os_revenda = trim($_POST["os_revenda"]);
    $codigo_rastreio = utf8_decode(trim($_POST["codigo_rastreio"]));

    $sql = "SELECT
                tbl_os.os AS os
            FROM tbl_os_revenda_item
            JOIN tbl_produto ON tbl_os_revenda_item.produto = tbl_produto.produto
            JOIN tbl_os ON tbl_os.os = tbl_os_revenda_item.os_lote
            WHERE os_revenda = {$os_revenda}
            AND tbl_os.status_checkpoint NOT IN(1, 14)
            AND tbl_os.data_fechamento IS NULL
            AND tbl_os.finalizada IS NULL
            ORDER BY tbl_os.os ASC";
    $resOs = pg_query($con, $sql);

    if(pg_num_rows($resOs) > 0){
        pg_query($con, "BEGIN");
        $erro = false;

        $rows = pg_num_rows($resOs);

        for($i = 0; $i < $rows; $i++){
            $os = pg_fetch_result($resOs, $i, "os");

            $sql = "SELECT os FROM tbl_os_extra WHERE os = {$os}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $sql = "UPDATE tbl_os_extra SET pac = '{$codigo_rastreio}' WHERE os = {$os}";
                $res = pg_query($con, $sql);
            } else {
                $sql = "INSERT INTO tbl_os_extra (os, pac) VALUES ({$os}, '{$codigo_rastreio}')";
                $res = pg_query($con, $sql);
            }

            $sql = "SELECT data_conserto FROM tbl_os WHERE os = {$os}";
            $res = pg_query($con, $sql);

            $data_conserto = pg_fetch_result($res, 0, "data_conserto");

            if (empty($data_conserto)) {
                $sql = "UPDATE tbl_os SET data_conserto = CURRENT_TIMESTAMP WHERE os = {$os}";
                $res = pg_query($con, $sql);
            }

            $sqlStatus = "SELECT fn_os_status_checkpoint_os({$os}) AS status;";
            $resStatus = pg_query($con, $sqlStatus);

            $statusCheckpoint = pg_fetch_result($resStatus, 0, "status");

            $updateStatus = "UPDATE tbl_os SET status_checkpoint = {$statusCheckpoint} WHERE fabrica = {$login_fabrica} AND os = {$os}";
            $resStatus = pg_query($con, $updateStatus);

            if (strlen(pg_last_error()) > 0) {
                $erro = true;
                break;
            }
        }

        $sql = "UPDATE tbl_os_revenda SET obs_causa = '{$codigo_rastreio}' WHERE os_revenda = {$os_revenda}";
        $res = pg_query($con, $sql);

        if($erro === false){
            echo traduz("Código de Rastreio inserido com Sucesso!");
            pg_query($con, "COMMIT");
        }else{
            echo traduz("Erro ao inserir o Código de Rastreio");
            pg_query($con, "ROLLBACK");
        }

    }else{
        echo traduz("Não há nenhuma OS para esse Código de Rastreio - Por favor clique no Botão Explodir para gerar OS");
    }

    exit;

}

if (strlen($os) > 0) {
	$sql =	"SELECT sua_os
			FROM tbl_os
			WHERE os = $os;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (@pg_numrows($res) == 1) {
		$sua_os = @pg_result($res,0,0);
		$sua_os_explode = explode("-", $sua_os);
		$xsua_os = $sua_os_explode[0];
	}

	$sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen($msg_erro) == 0) {

		$xsua_os = strtoupper($xsua_os);

		$sql =	"SELECT sua_os FROM tbl_os WHERE sua_os LIKE '$xsua_os-%' AND posto = $login_posto AND fabrica = $login_fabrica ";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (@pg_numrows($res) == 0) {
			$sql = "DELETE FROM tbl_os_revenda
					WHERE  tbl_os_revenda.sua_os  = '$xsua_os'
					AND    tbl_os_revenda.fabrica = $login_fabrica
					AND    tbl_os_revenda.posto   = $login_posto";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$url = $_COOKIE["cookredirect"];
		header("Location: $url");
		exit;
	}
}


if (strlen($_REQUEST["acao"]) > 0)  $acao = strtoupper($_REQUEST["acao"]);

if ($acao == "PESQUISAR") {
    $data_inicial       = trim($_REQUEST['data_inicial']);
    $data_final         = trim($_REQUEST['data_final']);
    $revenda_cnpj       = trim(strtoupper($_POST['revenda_cnpj']));
    $revenda_nome       = trim(strtoupper($_POST['revenda_nome']));
    $produto_referencia = trim(strtoupper($_POST['produto_referencia']));
    $$produto_descricao = trim(strtoupper($_POST['$produto_descricao']));
    $numero_os          = trim(strtoupper($_POST['numero_os']));
    $numero_serie       = trim(strtoupper($_POST['numero_serie']));
    $tipo_atendimento   = trim(strtoupper($_POST['tipo_atendimento']));

    if($login_fabrica == 137 && $posto_interno == true){
        $nota_fiscal = trim($_POST['nota_fiscal']);
    }

    if ( in_array($login_fabrica, array(11,172)) ) {
        $extrato = trim($_POST['extrato']);

        if (!empty($extrato) and empty($numero_os) ){
            $msg = traduz("Informe o número da OS de Revenda para pesquisar por Extrato");
        }
    }

    if(empty($numero_os)){
        if(!empty($data_inicial) AND !empty($data_final)){
            list($di, $mi, $yi) = explode("/", $data_inicial);
            if(!checkdate($mi,$di,$yi))
                $msg = traduz("data.invalida",$con,$cook_idioma);

            list($df, $mf, $yf) = explode("/", $data_final);
            if(!checkdate($mf,$df,$yf))
                $msg = traduz("data.invalida",$con,$cook_idioma);

            $aux_data_inicial = "$yi-$mi-$di";
            $aux_data_final = "$yf-$mf-$df";

            if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
                $msg = traduz("data.invalida",$con,$cook_idioma);
            }

            if(empty($msg)){
                if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -60 day')) {
                    $msg = traduz("periodo.nao.pode.ser.maior.que.60.dias",$con,$cook_idioma);
                }
            }

        }else{
            $msg = traduz("data.invalida",$con,$cook_idioma);
        }

        if (!empty($revenda_cnpj) AND !empty($revenda_nome) AND empty($msg)) {
            $revenda_cnpj = preg_replace("/\D/","",$revenda_cnpj);
            $sql =	"SELECT revenda , cnpj , nome
                    FROM tbl_revenda
                    WHERE cnpj = '$revenda_cnpj';";
            $res = pg_query($con,$sql);
            if (pg_num_rows($res) == 1) {
                $revenda      = pg_result($res,0,revenda);
                $revenda_cnpj = pg_result($res,0,cnpj);
                $revenda_nome = pg_result($res,0,nome);
            }else{
                $msg = traduz("revenda.nao.encontrada",$con,$cook_idioma);
            }
        }

		if (!empty($produto_referencia) AND  !empty($produto_descricao) AND empty($msg)){
			$sql =	"SELECT tbl_produto.produto    ,
							tbl_produto.referencia ,
							tbl_produto.descricao  ,
							tbl_produto.voltagem
					FROM tbl_produto
					JOIN tbl_linha USING (linha)
					WHERE tbl_linha.fabrica    = $login_fabrica
					AND   tbl_produto.referencia = '$produto_referencia'";
			if ($login_fabrica == 1) $sql .= " AND tbl_produto.voltagem = '$produto_voltagem';";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) == 1) {
				$produto            = pg_result($res,0,produto);
				$produto_referencia = pg_result($res,0,referencia);
				$produto_descricao  = pg_result($res,0,descricao);
				$produto_voltagem   = pg_result($res,0,voltagem);
			}else{
				$msg .= traduz("produto.nao.encontrado",$con,$cook_idioma);
			}
		}

        if (!empty($numero_serie) AND strlen($numero_serie) < 3 AND empty($msg)){
            $msg = traduz("digite.o.numero.de.serie.com.o.minimo.de.3.numeros",$con,$cook_idioma);
        }
    }else{
        $data_inicial       = null;
        $data_final         = null;
        $revenda_cnpj       = null;
        $revenda_nome       = null;
        $produto_referencia = null;
        $$produto_descricao = null;
        $numero_serie       = null;

    }
}

$layout_menu = "os";
$title       = traduz("relacao.de.ordens.de.servicos.de.revenda.lancadas",$con,$cook_idioma);

include "cabecalho.php";
?>
<? include "javascript_calendario_new.php";
    include 'js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>

<script type="text/javascript">
    $(document).ready(function()
    {
        $('input[name=extrato]').numeric();
    	$('#data_inicial').datepick({startdate:'01/01/2000'});
    	$('#data_final').datepick({startDate:'01/01/2000'});
        $("#data_inicial").mask("99/99/9999");
    	$("#data_final").mask("99/99/9999");
        $("#revenda_cnpj").mask("99.999.999/9999-99");
        $("#img_help_extrato").click(function(){
            alert("<?= traduz('Disponibiliza a opção de imprimir os produtos das OSs que entraram no extrato consultado') ?>");
        });
    });

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
	janela.nome			= document.frm_os.revenda_nome;
	janela.cnpj			= document.frm_os.revenda_cnpj;
	janela.fone			= document.frm_os.revenda_fone;
	janela.cidade		= document.frm_os.revenda_cidade;
	janela.estado		= document.frm_os.revenda_estado;
	janela.endereco		= document.frm_os.revenda_endereco;
	janela.numero		= document.frm_os.revenda_numero;
	janela.complemento	= document.frm_os.revenda_complemento;
	janela.bairro		= document.frm_os.revenda_bairro;
	janela.cep			= document.frm_os.revenda_cep;
	janela.email		= document.frm_os.revenda_email;
	janela.focus();
}

</script>

<?php
    if($login_fabrica == 141 && $posto_interno == true){
        ?>

        <script src="plugins/shadowbox_lupa/shadowbox.js" ></script>
        <link rel="stylesheet" href="plugins/shadowbox_lupa/shadowbox.css" type="text/css" />

        <script>

            $(function() {
                Shadowbox.init();

                $("select.tipo_entrega").change(function() {
                    var value = $(this).val();

                    if (value.length > 0 && value != "balcão") {
                        $(this).parent("td").parent("tr").find("input.codigo_rastreio").prop({ readonly: false }).val("");
                        $(this).parent("td").parent("tr").find("button.btn_gravar").show();
                    } else if (value == "balcão") {
                        $(this).parent("td").parent("tr").find("input.codigo_rastreio").prop({ readonly: true }).val("balcão");
                        $(this).parent("td").parent("tr").find("button.btn_gravar").show();
                    } else {
                        $(this).parent("td").parent("tr").find("input.codigo_rastreio").prop({ readonly: true }).val("");
                        $(this).parent("td").parent("tr").find("button.btn_gravar").hide();
                    }
                });
            });

            function listaOS(os_revenda){

                var url = "lista_os_revendas.php?os_revenda=" + os_revenda;

                Shadowbox.open({
                    content: url,
                    player: "iframe",
                    width: 800,
                    height: 300,
                    options: {
                        modal: true,
                        enableKeys: false,
                        displayNav: false
                    }
                });

            }

            function inserirCodigoRastreio(os_revenda, id){

                var codigo_rastreio = $('#codigo_rastreio_'+id).val();

                if(codigo_rastreio == ""){
                    alert("<?= traduz('Por favor insira o Código de Rastreio') ?>");
                    $('#codigo_rastreio_'+id).focus();
                    return;
                }

                $.ajax({
                    url : "<?php echo $_SERVER['PHP_SERVER'] ?>",
                    type: "POST",
                    data: {
                        os_revenda : os_revenda,
                        codigo_rastreio : codigo_rastreio,
                        alterar_codigo_rastreio : "ok"
                    },
                    complete: function(data){
                        data = data.responseText;
                        console.log(data);
                        alert(data);
                    }
                });

            }

        </script>

        <?php
    }
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
    text-align: left;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<br>

<form name="frm_os" method="POST" action="<?echo $PHP_SELF?>">
    <input type="hidden" name="acao">
    <table width="700px" align="center" border="0" cellspacing="0" cellpadding="4">
        <?php if(!empty($msg) > 0) { ?>
            <tr>
                <td class="error" colspan='4'><?echo $msg?></td>
            </tr>
        <?php } ?>
        <tr class="Titulo">
            <td colspan="4"><? fecho("preencha.os.campos.para.realizar.a.pesquisa",$con,$cook_idioma); ?></td>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF">
            <td width='80'>&nbsp;</td>
            <td width='220'>&nbsp;</td>
            <td width='220'>&nbsp;</td>
            <td width='80'>&nbsp;</td>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF">
            <td width='80'>&nbsp;</td>
            <td>
                <?php (in_array($login_fabrica, array(157))) ? fecho("os.interna",$con,$cook_idioma) : fecho("numero.da.os.revenda",$con,$cook_idioma); ?> <br />
                <input type="text" name="numero_os" size="10" value="<?php echo $numero_os;?>" />
            </td>
            <td>
                <? fecho("numero.serie",$con,$cook_idioma); ?><br>
                <input type="text" name="numero_serie" size="10" value="<?php echo $numero_serie?>" />
            </td>
            <td width='80'>&nbsp;</td>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF">
            <td width='80'>&nbsp;</td>
            <td>
                <? fecho("data.inicial",$con,$cook_idioma); ?><br>
                <input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? echo substr($data_inicial,0,10);?>" class="frm" />
            </td>
            <td>
                <? fecho("data.final",$con,$cook_idioma); ?><br>
                <input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? echo substr($data_final,0,10);?>" class="frm" />
            </td>
            <td width='80'>&nbsp;</td>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF">
            <td width='80'>&nbsp;</td>
            <td>
                <? fecho("cnpj.da.revenda",$con,$cook_idioma); ?><br>
                <input type="text" name="revenda_cnpj" id="revenda_cnpj" size="20" value="<?echo $revenda_cnpj?>">
                <img border="0" src="imagens/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="<? fecho("clique.aqui.para.pesquisar.revendas.pelo.codigo",$con,$cook_idioma); ?>" onclick="javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, 'cnpj');">
            </td>
            <td colspan='2'>
                 <? fecho("nome.da.revenda",$con,$cook_idioma); ?><br>
                <input type="text" id='revenda_nome' name="revenda_nome" size="22" value="<?echo $revenda_nome?>">
                <img border="0" src="imagens/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="<? fecho("clique.aqui.para.pesquisar.pelo.nome.da.revenda",$con,$cook_idioma); ?>" onclick="javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, 'nome');">
                <input type='hidden' name ='revenda_fone' id='revenda_fone' />
                <input type='hidden' name ='revenda_cidade' id='revenda_cidade' />
                <input type='hidden' name ='revenda_estado' id='revenda_estado' />
                <input type='hidden' name ='revenda_endereco' id='revenda_endereco' />
                <input type='hidden' name ='revenda_numero' id='revenda_numero' />
                <input type='hidden' name ='revenda_complemento' id='revenda_complemento' />
                <input type='hidden' name ='revenda_bairro' id='revenda_bairro' />
                <input type='hidden' name ='revenda_cep' id='revenda_cep' />
                <input type='hidden' name ='revenda_email' id='revenda_email' />
            </td>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF">
            <td width='80'>&nbsp;</td>
            <td>
                <? fecho("referencia.do.produto",$con,$cook_idioma); ?><br>
                <input type="text" name="produto_referencia" size="20" value="<?echo $produto_referencia?>"> <img src="imagens/btn_lupa.gif" width='20' height='18'  width='20' height='18' style="cursor: hand;" align='absmiddle' alt="<? fecho("clique.aqui.para.pesquisar.postos.pelo.codigo",$con,$cook_idioma); ?>" onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia, document.frm_os.produto_descricao, 'referencia', document.frm_os.produto_voltagem)" />
            </td>
            <td colspan='2'>
                <? fecho("descricao.do.produto",$con,$cook_idioma); ?><br>
                <input type="text" name="produto_descricao" size="22" value="<?echo $produto_descricao?>"> <img src="imagens/btn_lupa.gif" width='20' height='18'  style="cursor: hand;" align='absmiddle' alt="<? fecho("clique.aqui.para.pesquisas.pela.referencia.do.aparelho",$con,$cook_idioma); ?>" onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia, document.frm_os.produto_descricao, 'descricao', document.frm_os.produto_voltagem)" />
                <input type='hidden' name='produto_voltagem' size='5' value="<?echo $produto_voltagem?>" />
            </td>
        </tr>
        <?php
        if (in_array($login_fabrica, [169, 170])){ ?>
            <tr class="Conteudo" bgcolor="#D9E2EF">
                <td width='80'>&nbsp;</td>
                <td colspan='3'>
                    <? fecho("Tipo de Atendimento",$con,$cook_idioma); ?><br>
                    <select id="tipo_atendimento" name="tipo_atendimento">
                        <option value="">Selecione</option>
                        <?php
                        $sqlTipoAtendimento = "SELECT tipo_atendimento, descricao
                                               FROM tbl_tipo_atendimento
                                               WHERE fabrica = {$login_fabrica}
                                               AND ativo
                                               AND grupo_atendimento = 'R'";
                        $resTipoAtendimento = pg_query($con, $sqlTipoAtendimento);

                        while ($dados = pg_fetch_object($resTipoAtendimento)) {

                            $selected = ($dados->tipo_atendimento == $tipo_atendimento) ? "selected" : "";

                            echo "<option value='{$dados->tipo_atendimento}' {$selected}>{$dados->descricao}</option>";

                        }

                        ?>
                    </select>
                </td>
            </tr>
        <?php
        }  

        if(($login_posto == 6359 OR $login_posto == 4311) && $login_fabrica < 137){?>
            <tr class="Conteudo" bgcolor="#D9E2EF">
                <td width='80'>&nbsp;</td>
                <td>
                    <? fecho("rg.do.produto",$con,$cook_idioma); ?><br>
                    <input type="text" name="rg_produto" size="15" value="<?echo $rg_produto?>" />
                </td>
                <td>&nbsp;</td>
                 <td>&nbsp;</td>
            </tr>
        <?php }

        if ( in_array($login_fabrica, array(11,172)) ) {?>
            <tr class="Conteudo" bgcolor="#D9E2EF">
                <td width='80'>&nbsp;</td>
                <td>
                   <?= traduz('Extrato') ?> <br>
                    <input type="text" name="extrato" id="extrato" size="10" value="<?php echo $extrato ?>">
                    <img src="imagens/help.png" title="Disponibiliza a opção de imprimir os produtos das OSs que entraram no extrato consultado" id="img_help_extrato" style="cursor:pointer" >
                </td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>

        <?php
        }

        if ($login_fabrica == 137 && $posto_interno == true) {
        ?>
            <tr class="Conteudo" bgcolor="#D9E2EF">
                <td width='80'>&nbsp;</td>
                <td>
                    <?= traduz('NF de Entrada') ?> <br>
                    <input type="text" name="nota_fiscal" id="nota_fiscal" size="10" value="<?php echo $nota_fiscal; ?>">
                </td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>

        <?php
            }
        ?>

        <tr bgcolor="#D9E2EF" s>
            <td colspan="4" style='padding: 20px; text-align: center'>
                <input type='button' value='<? fecho("pesquisar",$con,$cook_idioma); ?>'  onclick="document.frm_os.acao.value='PESQUISAR'; document.frm_os.submit();" />
            </td>
        </tr>
    </table>
</form>
<br />
<?
if (strlen($acao) > 0 && strlen($msg) == 0) {

    if(!empty($aux_data_inicial)){
        $data_inicial = $aux_data_inicial." 00:00:00";
        $data_final   = $aux_data_final." 23:59:59";
    }

	//HD 54310 OR $login_fabrica == 15
	if ($login_fabrica == 1 OR $login_fabrica == 14) {
		$resX = pg_exec($con,"SELECT tbl_posto_fabrica.codigo_posto FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto.posto = $login_posto;");
		$posto_codigo = pg_result($resX,0,0);

		$sql =	"SELECT DISTINCT
						A.os_revenda ,
						A.abertura                ,
						A.sua_os              ,
						SUBSTRING(A.sua_os,1,5) ,
						A.revenda_nome        ,
						A.revenda_cnpj        ,
						A.os_reincidente      ,
						A.explodida           ,
						A.consumidor_revenda  ,
						A.data_fechamento     ,
						A.motivo_atraso       ,
						A.impressa            ,
						A.extrato             ,
						A.excluida            ,
						A.qtde_item           ,
						A.tipo_atendimento
				FROM (
				(
					SELECT  DISTINCT
							tbl_os_revenda.os_revenda                                                ,
							tbl_os_revenda.sua_os                                                    ,
							tbl_os_revenda.explodida                                                 ,
							TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS abertura           ,
							tbl_os_revenda.digitacao                           AS digitacao          ,
							tbl_revenda.nome                                   AS revenda_nome       ,
							tbl_revenda.cnpj                                   AS revenda_cnpj       ,
							NULL                                               AS consumidor_revenda ,
							current_date                                       AS data_fechamento    ,
							TRUE                                               AS excluida           ,
							NULL                                               AS motivo_atraso      ,
							tbl_os_revenda_item.serie                                                ,
							tbl_os_revenda.os_reincidente                                            ,
							tbl_os_revenda_item.produto                                              ,
							tbl_produto.referencia                             AS produto_referencia ,
							tbl_produto.descricao                              AS produto_descricao  ,
							current_date                                       AS impressa           ,
							0                                                  AS extrato            ,
							0                                                  AS qtde_item          ,
							tbl_os_revenda.tipo_atendimento
					FROM      tbl_os_revenda
					JOIN      tbl_os_revenda_item ON  tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
					JOIN      tbl_produto         ON  tbl_produto.produto            = tbl_os_revenda_item.produto
					LEFT JOIN tbl_revenda         ON  tbl_revenda.revenda            = tbl_os_revenda.revenda
					WHERE tbl_os_revenda.fabrica = $login_fabrica
					AND   tbl_os_revenda.posto   = $login_posto
					AND   tbl_os_revenda.os_manutencao IS FALSE ";

//--=== VALIDAÇÕES ESPECÍFICAS - OTIMIZAÇÃO DE SQL ===================================================
					if (strlen($data_inicial) > 0 && strlen($data_final) > 0) $sql .= " AND tbl_os_revenda.data_abertura BETWEEN '$data_inicial' AND '$data_final'";
					if (strlen($revenda) > 0) {
						if (strlen($revenda_cnpj) > 0) $sql .= " AND tbl_revenda.nome = '$revenda_cnpj'";
						if (strlen($revenda_nome) > 0) $sql .= " AND tbl_revenda.cnpj = '$revenda_nome'";
					}
					if (strlen($produto) > 0) $sql .= " AND tbl_os_revenda_item.produto = $produto";
					if (strlen($numero_os) > 0) {
						$pos = strpos($numero_os, "-");

						if ($pos === false) {
							if (strlen($numero_os) > 6) $numero_os = substr($numero_os, strlen($posto_codigo), strlen($numero_os));
						}else{
							if (strlen($numero_os) > 7) $numero_os = substr($numero_os, strlen($posto_codigo), strlen($numero_os));
						}
						$numero_os = strtoupper($numero_os);
						$sql .= " AND tbl_os_revenda.sua_os LIKE '%$numero_os%'";
					}
					$numero_serie = strtoupper($numero_serie);
					if (strlen($numero_serie) > 0) $sql .= " AND tbl_os_revenda_item.serie      LIKE '%$numero_serie%'";
					#SISTEMA RG
					if (strlen($rg_produto) > 0) $sql .= " AND tbl_os_revenda_item.rg_produto LIKE '%$rg_produto%'";
//--==================================================================================================


				$sql .= " ) UNION (
					SELECT  tbl_os.os                                  AS os_revenda         ,
							tbl_os.sua_os                                                    ,
							NULL                                       AS explodida          ,
							TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura           ,
							tbl_os.data_digitacao                      AS digitacao          ,
							tbl_os.revenda_nome                                              ,
							tbl_os.revenda_cnpj                                              ,
							tbl_os.consumidor_revenda                                        ,
							tbl_os.data_fechamento                                           ,
							tbl_os.excluida                                                  ,
							tbl_os.motivo_atraso                                             ,
							tbl_os.serie                                                     ,
							tbl_os.os_reincidente                                            ,
							tbl_os.produto                                                   ,
							tbl_produto.referencia                     AS produto_referencia ,
							tbl_produto.descricao                      AS produto_descricao  ,
							tbl_os_extra.impressa                                            ,
							tbl_os_extra.extrato                                             ,
							(
								SELECT COUNT(tbl_os_item.*) AS qtde_item
								FROM   tbl_os_item
								JOIN   tbl_os_produto USING (os_produto)
								WHERE  tbl_os_produto.os = tbl_os.os
							)                                          AS qtde_item,
							tbl_os.tipo_atendimento
					FROM tbl_os
					JOIN tbl_os_extra       ON  tbl_os_extra.os           = tbl_os.os
					JOIN tbl_produto        ON  tbl_produto.produto       = tbl_os.produto
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.posto   = $login_posto
					AND   tbl_os.consumidor_revenda = 'R' ";

//--=== VALIDAÇÕES ESPECÍFICAS - OTIMIZAÇÃO DE SQL ===================================================
					if (strlen($data_inicial) > 0 && strlen($data_final) > 0) $sql .= " AND tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'";
					if (strlen($revenda) > 0) {
						if (strlen($revenda_cnpj) > 0) $sql .= " AND tbl_os.revenda_cnpj = '$revenda_cnpj'";
						if (strlen($revenda_nome) > 0) $sql .= " AND tbl_os.revenda_nome = '$revenda_nome'";
					}
					if (strlen($produto) > 0) $sql .= " AND tbl_os.produto = $produto";
					if (strlen($numero_os) > 0) {
						$pos = strpos($numero_os, "-");

						if ($pos === false) {
							if (strlen($numero_os) > 6) $numero_os = substr($numero_os, strlen($posto_codigo), strlen($numero_os));
						}else{
							if (strlen($numero_os) > 7) $numero_os = substr($numero_os, strlen($posto_codigo), strlen($numero_os));
						}
						$numero_os = strtoupper($numero_os);
						$sql .= " AND tbl_os.sua_os LIKE '%$numero_os%'";
					}
					$numero_serie = strtoupper($numero_serie);
					if (strlen($numero_serie) > 0) $sql .= " AND tbl_os.serie LIKE '%$numero_serie%'";
					#SISTEMA RG
					if (strlen($rg_produto) > 0)   $sql .= " AND tbl_os.rg_produto LIKE '%$rg_produto%'";
//--==================================================================================================

					$sql .="
				)
			) AS A
			WHERE (1=1 ";

		if (strlen($data_inicial) > 0 && strlen($data_final) > 0) $sql .= " AND A.digitacao BETWEEN '$data_inicial' AND '$data_final'";

		if (strlen($revenda) > 0) {
			if (strlen($revenda_cnpj) > 0) $sql .= " AND A.revenda_cnpj = '$revenda_cnpj'";
			if (strlen($revenda_nome) > 0) $sql .= " AND A.revenda_nome = '$revenda_nome'";
		}

		if (strlen($produto) > 0) {
			$sql .= " AND A.produto = $produto";
		}

		if (strlen($numero_os) > 0) {
			$pos = strpos($numero_os, "-");

			if ($pos === false) {
				if (strlen($numero_os) > 6) $numero_os = substr($numero_os, strlen($posto_codigo), strlen($numero_os));
			}else{
				if (strlen($numero_os) > 7) $numero_os = substr($numero_os, strlen($posto_codigo), strlen($numero_os));
			}

			$numero_os = strtoupper($numero_os);

			$sql .= " AND A.sua_os LIKE '%$numero_os%'";
		}

		$numero_serie = strtoupper($numero_serie);

        if (strlen($numero_serie) > 0) $sql .= " AND A.serie LIKE '%$numero_serie%'";

		$sql .= ") ORDER BY SUBSTRING(A.sua_os,1,5) ASC, A.os_revenda ASC;";
	}else{

        $cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_os_revenda.fabrica IN (11,172) " : " tbl_os_revenda.fabrica = $login_fabrica ";

		$sql =	"SELECT DISTINCT
						tbl_os_revenda.os_revenda                                          ,
						tbl_os_revenda.sua_os                                              ,
                        tbl_os_revenda.explodida                                           ,
                        tbl_os_revenda.consumidor_nome                                     ,
                        tbl_os_revenda.consumidor_cpf                                     ,
                        tbl_os_revenda.consumidor_revenda AS tipo_os                       , 
                        tbl_os_revenda.posto                                               ,
						tbl_os_revenda.obs_causa AS codigo_rastreio                        ,
						TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS abertura     ,
						tbl_os_revenda.revenda                                             ,
						tbl_os_revenda.os_reincidente                                      ,
                        tbl_revenda.cnpj                                   AS revenda_cnpj ,
						tbl_revenda.nome                                   AS revenda_nome
			FROM		tbl_os_revenda
			LEFT JOIN	tbl_os_revenda_item ON  tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
			LEFT JOIN	tbl_produto         ON  tbl_produto.produto            = tbl_os_revenda_item.produto
			LEFT JOIN	tbl_revenda         ON  tbl_revenda.revenda            = tbl_os_revenda.revenda
			WHERE		{$cond_pesquisa_fabrica}
			AND			tbl_os_revenda.posto   = $login_posto
			AND			tbl_os_revenda.os_manutencao IS FALSE ";

		if (strlen($data_inicial) > 0 && strlen($data_final) > 0) $sql .= " AND tbl_os_revenda.digitacao BETWEEN '$data_inicial' AND '$data_final'";

		if (strlen($revenda) > 0) $sql .= " AND tbl_os_revenda.revenda = $revenda";

        if (strlen($nota_fiscal) > 0) $sql .= " AND tbl_os_revenda.nota_fiscal LIKE '%$nota_fiscal%'";

        if (strlen($tipo_atendimento) > 0) $sql .= " AND tbl_os_revenda_item.tipo_atendimento = {$tipo_atendimento}";

		if (strlen($produto) > 0) $sql .= " AND tbl_os_revenda_item.produto = $produto";
		$numero_os = strtoupper($numero_os);
		if (strlen($numero_os) > 0){

            if (!in_array($login_fabrica, [169,170])) {
    			$pos = strpos($numero_os, "-");
    			if($pos === false){
    				$sql .= " AND tbl_os_revenda.sua_os LIKE '%$numero_os%'";
    			}else{
    			$numero_os = substr($numero_os, 0, $pos);
     				$sql .= " AND tbl_os_revenda.sua_os LIKE '%$numero_os%'";
    			}
            } else {

                $sql .= " AND tbl_os_revenda.os_revenda = {$numero_os}";

            }

		}
		$numero_serie = strtoupper($numero_serie);
		if (strlen($numero_serie) > 0) $sql .= " AND tbl_os_revenda_item.serie LIKE '%$numero_serie%'";

		$sql .= " ORDER BY tbl_os_revenda.os_revenda DESC;";
	}

    $res = pg_query($con,$sql);

    //para gerar a tabela que separa por cores
	if (pg_num_rows($res) > 0) {
		$total_registro = pg_numrows($res);

		if ($login_fabrica == 3 OR $login_fabrica == 14) {
			echo "<table width='700' border='0' cellspacing='2' cellpadding='0' align='center'>";
			echo "<tr>";
			echo "<td align='center' width='10' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'>&nbsp;"; fecho("excluidas.do.sistema",$con,$cook_idioma); echo "</font></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center' width='10' bgcolor='#91C8FF'>&nbsp;</td>";
			echo "<td align='left'><font size='1'>&nbsp;"; fecho("oss.sem.fechamento.ha.mais.de.20.dias.informar.motivo",$con,$cook_idioma); echo "</font></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center' width='10' bgcolor='#FFCC66'>&nbsp;</td>";
			echo "<td align='left'><font size='1'>&nbsp;"; fecho("oss.sem.lancamento.de.itens.ha.mais.de.5.dias.efetue.o.lancamento",$con,$cook_idioma); echo "</font></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center' width='10' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size='1'>&nbsp;"; fecho("oss.que.excederam.o.prazo.limite.de.30.dias.para.fechamento,.informar.motivo",$con,$cook_idioma); echo "</font></td>";
			echo "</tr>";
			if($login_fabrica == 1){
				echo "<tr>";
				echo "<td align='center' width='10' bgcolor='#FF9900'>&nbsp;</td>";
				echo "<td align='left'><font size='1'>&nbsp;"; fecho("oss.reincidentes.que.tem.uma.os.no.sistema.lancada.a.menos.de.90.dias.com.o.mesmo.cnpj.e.o.mesmo.numero.de.nota.fiscal",$con,$cook_idioma); echo "</font></td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<br>";
			flush();
		}

        $tamanho = ($login_fabrica == 141 && $posto_interno == true) ? 900 : 700;
        $alterar_os = false;
                
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center' width='{$tamanho}px;'>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i == 0) {
				echo "<tr class='Titulo' height='15'>";

				//HD 214236: Legendas para auditoria de OS da Intelbras. Como vão ser auditadas todas as OS, criei uma coluna nova para que sinalize o status da auditoria
				if ($login_fabrica == 14 || $login_fabrica == 43) {
					echo "<td>Status</td>";
				}

				echo "<td>"; fecho("os",$con,$cook_idioma); echo "</td>";
				echo "<td>"; fecho("data",$con,$cook_idioma); echo "</td>";
                echo "<td>"; fecho("revenda",$con,$cook_idioma); echo "</td>";

                if($login_fabrica == 141 && $posto_interno == true){
                    echo "<td>Tipo de Entrega</td>";
                    echo "<td>Código Rastreio</td>";
                }

				//hd 7667 10/1/2008 colocada fabrica 15
				if ($login_fabrica == 1  OR $login_fabrica == 14) {
					echo "<td>ITEM</td>";
					echo "<td><img border='0' src='imagens/img_impressora.gif' alt='"; fecho("os.que.ja.foi.impressa",$con,$cook_idioma); echo"'></td>";
					$colspan = "6";
				}if( $login_fabrica == 15){
					echo "<td><img border='0' src='imagens/img_impressora.gif' alt='"; fecho("os.que.ja.foi.impressa",$con,$cook_idioma); echo"'></td>";
					$colspan = "6";
				}else{
					if (in_array($login_fabrica, array(14,178))) {
						$colspan = "4";
					}else {
						$colspan = "3";
					}
				}
                if($login_fabrica == 141 && $posto_interno == true){
                    $colspan = 4;
                }
                
                echo "<td colspan='$colspan'>"; fecho("acoes",$con,$cook_idioma); echo"</td>";
                echo ( in_array($login_fabrica, array(11,172)) && $extrato) ? "<td> OS do Extrato </td>" : "" ;
				echo "</tr>";
				flush();
			}

			$os_revenda     = trim(pg_result($res,$i,os_revenda));
			$sua_os         = empty(trim(pg_result($res,$i,sua_os))) ? $os_revenda : trim(pg_result($res,$i,sua_os));

			$explodida      = trim(pg_result($res,$i,explodida));
			$abertura       = trim(pg_result($res,$i,abertura));
			$revenda_cnpj   = trim(pg_result($res,$i,revenda_cnpj));
			$revenda_nome   = trim(pg_result($res,$i,revenda_nome));
			$os_reincidente = trim(pg_result($res,$i,os_reincidente));
            $posto_id = pg_fetch_result($res, $i, posto);
            $codigo_rastreio = trim(pg_result($res, $i, "codigo_rastreio"));
            $consumidor_nome = trim(pg_fetch_result($res, $i, 'consumidor_nome'));
            $consumidor_cpf  = trim(pg_fetch_result($res, $i, 'consumidor_cpf'));
            $tipo_os         = trim(pg_fetch_result($res, $i, 'tipo_os'));

			//HD 54310 OR $login_fabrica == 15
			if ($login_fabrica == 1 OR $login_fabrica == 14) {
				$consumidor_revenda = trim(pg_result($res,$i,consumidor_revenda));
				$data_fechamento    = trim(pg_result($res,$i,data_fechamento));
				$motivo_atraso      = trim(pg_result($res,$i,motivo_atraso));
				$impressa           = trim(pg_result($res,$i,impressa));
				$extrato            = trim(pg_result($res,$i,extrato));
				$excluida           = trim(pg_result($res,$i,excluida));
				$qtde_item          = trim(pg_result($res,$i,qtde_item));
				if($login_fabrica ==1){
					$tipo_atendimento   = trim(pg_result($res,$i,tipo_atendimento));
				}else{
					$tipo_atendimento = '' ;
				}
				if (strlen($consumidor_revenda) > 0) {
					if ($excluida == "t") $cor = "#FFE1E1";

					// verifica se nao possui itens com 5 dias de lancamento...
					$aux_data_abertura = fnc_formata_data_pg($abertura);

					$sqlX = "SELECT to_char (current_date + INTERVAL '5 days', 'YYYY-MM-DD')";
					$resX = pg_exec ($con,$sqlX);
					$data_hj_mais_5 = pg_result($resX,0,0);

					$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '5 days', 'YYYY-MM-DD')";
					$resX = pg_exec ($con,$sqlX);
					$data_consultar = pg_result($resX,0,0);

					$sql = "SELECT COUNT(tbl_os_item.*) as total_item
							FROM tbl_os_item
							JOIN tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
							JOIN tbl_os on tbl_os.os = tbl_os_produto.os
							WHERE tbl_os.os = $os_revenda
							AND tbl_os.data_abertura::date >= '$data_consultar'";
					$resItem = pg_exec($con,$sql);

					$itens = pg_result($resItem,0,total_item);

					if ($itens == 0 and $data_consultar > $data_hj_mais_5) $cor = "#FFCC66";

					$mostra_motivo = 2;

					// verifica se está sem fechamento ha 20 dias ou mais da data de abertura...
					if (strlen($data_fechamento) == 0 AND $mostra_motivo == 2 AND $login_fabrica == 1 ){
						$aux_data_abertura = fnc_formata_data_pg($abertura);

						$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '20 days', 'YYYY-MM-DD')";
						$resX = pg_exec ($con,$sqlX);
						$data_consultar = pg_result($resX,0,0);

						$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";

						$resX = pg_exec ($con,$sqlX);
						$data_atual = pg_result ($resX,0,0);

						if ($data_consultar < $data_atual AND strlen($data_fechamento) == 0) {
							$mostra_motivo = 1;
							$cor = "#91C8FF";
						}
					}

					// Se estiver acima dos 30 dias, nao exibira os botoes...
					if (strlen($data_fechamento) == 0 AND $login_fabrica == 1) {
						$aux_data_abertura = fnc_formata_data_pg($abertura);

						$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '30 days', 'YYYY-MM-DD')";
						$resX = pg_exec($con,$sqlX);
						$data_consultar = pg_result($resX,0,0);

						$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";

						$resX = pg_exec($con,$sqlX);
						$data_atual = pg_result($resX,0,0);

						if ($data_consultar < $data_atual AND strlen($data_fechamento) == 0) {
							$mostra_motivo = 1;
							$cor = "#ff0000";
						}
					}

				}
			}

            if ($login_fabrica == 178){
                $sqlExtrato = "
                    SELECT tbl_os_campo_extra.os
                    FROM tbl_os_campo_extra
                    JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os_campo_extra.os
                    WHERE tbl_os_campo_extra.os_revenda = $os_revenda
                    AND tbl_os_campo_extra.fabrica = {$login_fabrica}
                    AND tbl_os_extra.extrato IS NULL ";
                $resExtrato = pg_query($con, $sqlExtrato);
                if (pg_num_rows($resExtrato) > 0){
                    $alterar_os = true;
                }
            }

			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			}else{
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}
			$sua_os = strtoupper($sua_os);
			$sql =	"SELECT tbl_os.os FROM tbl_os WHERE sua_os LIKE '$sua_os-%' AND posto = $login_posto AND fabrica = $login_fabrica ";
			$resX = pg_exec($sql);

			if($os_reincidente =='t')$cor = "#FF9900";
//echo "$sql";
//aqui vai o codigo da os
			if ($login_fabrica == 1 or $login_fabrica ==14 ) $sua_os = $sua_os;
			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";

			//HD 214236: Legendas para auditoria de OS da Intelbras. Como vão ser auditadas todas as OS, criei uma coluna nova para que sinalize o status da auditoria
			$auditoria_travar_opcoes = false;

			if ($login_fabrica == 14 || $login_fabrica == 43) {
				$sql = "
				SELECT
				liberado,
				cancelada

				FROM
				tbl_os_auditar

				WHERE
				os_auditar IN (
					SELECT
					MAX(os_auditar)

					FROM
					tbl_os_auditar

					WHERE
					os=$os_revenda
				)
				";
				$res_auditoria = pg_query($con, $sql);

				if (strlen(pg_errormessage($con)) == 0 && pg_num_rows($res_auditoria)) {
					$liberado = pg_result($res_auditoria, 0, liberado);
					$cancelada = pg_result($res_auditoria, 0, cancelada);

					if ($liberado == 'f') {
						if ($cancelada == 'f') {
							$legenda_status = "em análise";
							$cor_status = "#FFFF44";
							$auditoria_travar_opcoes = true;
						}
						elseif ($cancelada == 't') {
							$legenda_status = "reprovada";
							$cor_status = "#FF7744";
						}
						else {
							$legenda_status = "";
							$cor_status = "";
						}
					}
					elseif ($liberado == 't') {
						$legenda_status = "aprovada";
						$cor_status = "#44FF44";
					}
					else {
						$legenda_status = "";
						$cor_status = "";
					}
				}
				else {
					$legenda_status = "";
					$cor_status = "";
				}

				echo "<td style='background:$cor_status' title='$legenda_status' nowrap align='center'>$legenda_status</td>";
			}
			//HD 214236::: FIM :::

			if ($login_fabrica == 1 or $login_fabrica ==14) echo $posto_codigo;

			if (strlen($explodida) > 0){
                if (isset($novaTelaOsRevenda)) {
                    echo "<td nowrap align='center'><a href='os_revenda_press.php?os_revenda=$os_revenda' target='_blank'>".$sua_os."</a></td>";
                } else {
				    echo "<td nowrap align='center'><A HREF='os_revenda_explodida.php?sua_os=$sua_os' target='_blank' style='color:#0088cc' >".$sua_os."</A>"."</td>";
                }
			}else{
                if (isset($novaTelaOsRevenda)) {

                    echo "<td nowrap align='center'><a href='os_revenda_press.php?os_revenda=$os_revenda' target='_blank'>".$sua_os."</a></td>";
                } else {
                    echo "<td nowrap> ".$sua_os . "</td>";
                }
			}
			echo "<td nowrap  align='center' >" . $abertura . "</td>";
			echo "<td nowrap  align='center'>";
                if (!empty($tipo_os) AND $tipo_os == "C"){
                    echo "<acronym title='CNPJ: $consumidor_cpf\nRAZÃO SOCIAL: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,20) . "</acronym>";
                }else{
                    echo "<acronym title='CNPJ: $revenda_cnpj\nRAZÃO SOCIAL: $revenda_nome' style='cursor: help;'>" . substr($revenda_nome,0,20) . "</acronym>";
                }
            echo "</td>";

            if($login_fabrica == 141 && $posto_interno == true){
                $sqlOs = "SELECT tbl_os.os AS os
                          FROM tbl_os_revenda_item
                          JOIN tbl_produto ON tbl_os_revenda_item.produto = tbl_produto.produto
                          JOIN tbl_os ON tbl_os.os = tbl_os_revenda_item.os_lote
                          WHERE os_revenda = {$os_revenda}
                          AND tbl_os.status_checkpoint NOT IN(1, 14)
                          AND tbl_os.data_fechamento IS NULL
                          AND tbl_os.finalizada IS NULL
                          ORDER BY tbl_os.os ASC";
                $resOs = pg_query($con, $sql);

                if (!empty($codigo_rastreio) && $codigo_rastreio != "balcão") {
                    $CRInputReadonly = "";
                    $CRButtonGravar  = "style='display: show;'";
                } else if ($codigo_rastreio == "balcão") {
                    $CRInputReadonly = "readonly='readonly'";
                    $CRButtonGravar  = "style='display: show;'";
                } else {
                    $CRInputReadonly = "readonly='readonly'";
                    $CRButtonGravar  = "style='display: none;'";
                }

                if (pg_num_rows($resOs) > 0) {
                    echo "
                    <td align='center' nowrap>
                        <select name='tipo_entrega_{$i}' class='tipo_entrega' >
                            <option value='' selected >Selecione</option>
                            <option value='balcão' ".(($codigo_rastreio == "balcão") ? "selected" : "")." >Balcão</option>
                            <option value='correios' ".((!empty($codigo_rastreio) && $codigo_rastreio != "balcão") ? "selected" : "")." >Correios</option>
                        </select>
                    </td>
                    <td align='center' nowrap>
                        <input class='codigo_rastreio' type='text' {$CRInputReadonly} placeholder='Selecione o Tipo de Entrega' name='codigo_rastreio_{$i}' id='codigo_rastreio_{$i}' value='{$codigo_rastreio}' style='width: 150px;' />
                        <button class='btn_gravar' type='button' {$CRButtonGravar} onclick='inserirCodigoRastreio({$os_revenda}, {$i})'>Gravar</button>
                    </td>
                    <td align='center'>
                        <button type='button' onclick='listaOS({$os_revenda})' style='cursor: pointer;'>NF de Saída</button>
                    </td>
                    ";
                } else {
                    echo "
                    <td align='center' nowrap>&nbsp;</td><td align='center' nowrap>&nbsp;</td>
                    ";
                }
            }

//onde vai o botao alterar e o botao excluir
			if ($login_fabrica != 1 AND $login_fabrica != 14 AND $login_fabrica != 15) {
                if (!in_array($login_fabrica, [169,170])) {
    				echo "<td width='80' align='center'>";
                    if (strlen($explodida) != 0 && in_array($login_fabrica, [141]) && $posto_interno) {
                       echo "<a href='os_revenda.php?os_revenda=$os_revenda'><img border='0' src='imagens/btn_alterar_".$botao.".gif'></a>";
                    }
    				//retirado a condição que verifica na tbl_os - Wellington 27-09-2006

                    //retirado do IF OR ($login_fabrica == 137 && $posto_interno) teste para arge.

    				if (/*pg_numrows($resX) == 0 || */strlen($explodida) == 0 OR ($login_fabrica == 137 && $posto_interno)) echo "<a href='os_revenda.php?os_revenda=$os_revenda'><img border='0' src='imagens/btn_alterar_".$botao.".gif'></a>";
    				else                                                   echo "&nbsp;";
    				echo "</td>";
    //ao clicar aqui vc vai explodir a OS
    				echo "<td width='80' align='center'>";
    				//retirado a condição que verifica na tbl_os - Wellington 27-09-2006
    				if (/*pg_numrows($resX) == 0 || */strlen($explodida) == 0){ echo "<a href='os_revenda_finalizada.php?os_revenda=$os_revenda&btn_acao=explodir'><img border='0' src='imagens/btn_explodir";
    				if($login_fabrica==19){echo "_2";}
    				echo ".gif'></a>";
    				}else{        echo "&nbsp;";}
    				echo "</td>";

                    if ($login_fabrica == 178 AND $alterar_os === true){
                        echo "
                        <td align='center'>
                        <a href='cadastro_os_revenda.php?os_revenda=$os_revenda' target='_blank' > <img border='0' src='imagens/btn_alterar_".$botao.".gif'> </a>
                        </td>";
                    }

    				echo "<td width='80' align='center'>";
    				echo "<a href='os_revenda_print.php?os_revenda=$os_revenda' target='_blank'><img border='0' src='imagens/btn_imprimir_".$botao.".gif'></a>";
    				echo "</td>";

                    if ( in_array($login_fabrica, array(11,172)) && $extrato) {
                        echo "<td width='80' align='center'>";
                        echo "<a href='os_revenda_print.php?os_revenda=$os_revenda&e=$extrato' target='_blank'><img border='0' src='imagens/btn_imprimir_".$botao.".gif'></a>";
                        echo "</td>";
                    }
                } else { ?>

                    <td align="center">

                        <a href='os_revenda_press.php?os_revenda=<?= $os_revenda ?>' target="_blank"><img src='imagens/btn_consulta.gif'></a>

                    </td>

                <?php
                }
			}else{

				if (strlen($consumidor_revenda) == 0) {
					echo "<td width='30' align='center'>&nbsp;</td>\n";
					// verifica se existem OS geradas pela OS Revenda
					$sua_os = strtoupper($sua_os);
					$sql =	"SELECT tbl_os.os FROM tbl_os WHERE sua_os LIKE '$sua_os-%' AND posto = $login_posto AND fabrica = $login_fabrica ";
					$resX = pg_exec($con,$sql);

					echo "<td width='80' align='center'>";
					if (/*pg_numrows($resX) == 0 ||*/ strlen($explodida) == 0 ) echo "<a href='os_revenda.php?os_revenda=$os_revenda'><img border='0' src='imagens/btn_alterar_".$botao.".gif'></a>";
					else                                                   echo "&nbsp;";
					echo "</td>\n";

					echo "<td width='80' align='center'>";
					if (/*pg_numrows($resX) == 0 ||*/ strlen($explodida) == 0) echo "<a href='os_revenda_finalizada.php?os_revenda=$os_revenda&btn_acao=explodir'><img src='imagens/btn_explodir.gif'></a>";
					else                                                   echo "&nbsp;";
					echo "</td>\n";

					echo "<td width='80' align='center'><a href='os_revenda_print.php?os_revenda=$os_revenda' target='_target'><img src='imagens/btn_imprimir_" . $botao . ".gif' alt='Imprimir Revenda'></a></td>\n";

					if($login_fabrica == 1){
					echo "<td width='80' align='center'>";
					echo "<a href='os_revenda_blackedecker_total_print.php?os_revenda=$os_revenda' target='_target'><img src='imagens/btn_imprimir_" . $botao . ".gif' alt='Imprimir
					Black & Decker'></a>";
					echo "</td>\n";
					}else{
						echo "<td width='80' align='center' colspan='2'>&nbsp;</td>\n";
					}
				}else{
					if($login_fabrica == 1 or $login_fabrica == 14){
					echo "<td width='30' align='center'>";
					if ($qtde_item > 0) echo"<img border='0' src='imagens/img_ok.gif' alt='OS com item'>";
					echo "</td>\n";
					}
					echo "<td width='30' align='center'>";
					if (strlen($impressa) > 0) {
						echo"<img border='0' src='imagens/img_ok.gif' alt='"; fecho("os.ja.foi.impressa",$con,$cook_idioma); echo "'>";
					}else{
						echo"<img border='0' src='imagens/img_impressora.gif' alt='"; fecho("imprimir.os",$con,$cook_idioma); echo "'>";
					}
					echo "</td>\n";

					echo "<td width='80' align='center'>";
					if ($excluida == "f" || strlen($excluida) == 0) echo "<a href='os_press.php?os=$os_revenda'><img src='imagens/btn_consulta.gif'></a>";
					if($os_reincidente =='t'){
						echo "<a href='os_motivo_atraso.php?os=$os_revenda&justificativa=ok'><br><font color='FFFFFF'>"; fecho("justificativa",$con,$cook_idioma); echo "</a>";
					}
					echo "</td>\n";
//takashi
					if($login_fabrica==1){
					echo "<td width='80' align='center'>";
					if (($excluida == "f" || strlen($excluida) == 0) && strlen($data_fechamento) == 0) echo "<a href='os_revenda_alterar.php?os=$os_revenda'><img src='imagens/btn_alterar_cinza.gif'></a>";
					echo "</td>\n";
					}
					echo "<td width='80' align='center'>";
					if ($excluida == "f" || strlen($excluida) == 0) echo "<a href='os_print.php?os=$os_revenda' target='_blank'><img src='imagens/btn_imprime.gif'></a>";
					else                                            echo "&nbsp;";
					echo "</td>";

					echo "<td width='80' align='center'>";

					//HD 214236: Travar as opções quando a OS estiver em auditoria
					if (($login_fabrica == 14 || $login_fabrica == 43) && ($auditoria_travar_opcoes)) {
					}
					else {
						if ($mostra_motivo == 1) {
							if (($excluida == "f" || strlen($excluida) == 0) and strlen($tipo_atendimento) == 0) {
								echo "<a href='os_item.php?os=$os_revenda'><img src='imagens/btn_lanca.gif'></a> &nbsp; <a href='os_motivo_atraso.php?os=$os_revenda'>"; fecho("motivo",$con,$cook_idioma); echo "</a>";
							}
						}elseif (strlen($data_fechamento) == 0) {
							if (($excluida == "f" || strlen($excluida) == 0) and strlen($tipo_atendimento) == 0) {
								echo "<a href='os_item.php?os=$os_revenda'><img src='imagens/btn_lanca.gif'></a>";
							}
						}elseif (strlen($data_fechamento) > 0 && strlen($extrato) == 0) {
							if ($excluida == "f" || strlen($excluida) == 0) {
								if( !in_array($login_fabrica, array(11,172)) ){ // HD 45935
									echo "<a href='os_item.php?os=$os_revenda&reabrir=ok' ><img src='imagens/btn_reabriros.gif'></a>";
								}else{
									echo "&nbsp;";
								}
							}
						}
					}
					echo "</td>\n";

					echo "<td width='80' align='center'>";
					//HD 214236: Travar as opções quando a OS estiver em auditoria
					if (($login_fabrica == 14 || $login_fabrica == 43) && ($auditoria_travar_opcoes)) {
					}
					else {
						if (strlen($data_fechamento) == 0 && strlen($pedido) == 0) {
							if ($excluida == "f" || strlen($excluida) == 0) {
								$sua_os_black = $posto_codigo.$sua_os;
								echo "<a href=\"javascript: if (confirm ('"; fecho("deseja.realmente.excluir.os.%",$con,$cook_idioma,array("$sua_os_black")); echo "') == true) { window.location='$PHP_SELF?excluir=$os_revenda' }\"><img src='imagens/btn_excluir.gif'></A>";
							}else{
								echo "&nbsp;";
							}
						}else{
							echo "&nbsp;";
						}
						echo "</td>\n";
					}

					if($login_fabrica == 15){
					echo "<td width='80' align='center'>";
						if($consumidor_revenda<>'R'){
							echo "<a href=\"javascript: if (confirm('"; fecho("deseja.realmente.excluir.os.%",$con,$cook_idioma,array("$sua_os_black")); echo "') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
						}else{
							echo "<a href=\"javascript: if(confirm('"; fecho("os.revenda.devera.ser.fechada.na.tela.fechamento.de.os",$con,$cook_idioma); echo "') == true) {window.location='os_fechamento.php?sua_os=$sua_os&btn_acao_pesquisa=continuar';}\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
						}
					echo "</td>\n";
					}

				}
			}
			echo "</tr>";
            if($login_fabrica ==157){
            
                $sqlosExplodidas = "SELECT tbl_os.os AS os ,
                        tbl_os.sua_os AS sua_os,
                        TO_CHAR(tbl_os.data_abertura , 'DD/MM/YYYY') as data_abertura,
                        finalizada,
                        data_fechamento,
                        cancelada,
                        excluida,
                        tbl_revenda.nome
                        FROM    tbl_os
                        join tbl_revenda on tbl_revenda.revenda = tbl_os.revenda 
                        WHERE   tbl_os.fabrica = $login_fabrica 
                        AND tbl_os.posto = $posto_id
                        AND tbl_os.consumidor_revenda = 'R'
                        AND tbl_os.sua_os LIKE '$sua_os-%'
                        ORDER BY tbl_os.os ASC";
                $resosExplodidas = pg_query($con, $sqlosExplodidas);

                for($zz=0; $zz<pg_num_rows($resosExplodidas); $zz++){
                    $sua_os             = pg_fetch_result($resosExplodidas, $zz, 'sua_os');
                    $data_abertura      = pg_fetch_result($resosExplodidas, $zz, 'data_abertura');
                    $os                 = pg_fetch_result($resosExplodidas, $zz, 'os');
                    $finalizada         = pg_fetch_result($resosExplodidas, $zz, 'finalizada');
                    $data_fechamento    = pg_fetch_result($resosExplodidas, $zz, 'data_fechamento');
                    $cancelada          = pg_fetch_result($resosExplodidas, $zz, 'cancelada');
                    $excluida           = pg_fetch_result($resosExplodidas, $zz, 'excluida');
                    $nome_revenda       = pg_fetch_result($resosExplodidas, $zz, 'nome');

                    if ($cancelada == 't' or !empty($finalizada) or !empty($data_fechamento) or $excluida == 't'){
                        $display_button_cancelado = "style='display:none'";
                    } else {
                        $display_button_cancelado = "style='display:block'";
                    }
                    echo "<tr class='Conteudo'>
                            <td align='center'><a  style='color:#0088cc' href='os_press.php?os=$os'>$sua_os</a></td>
                            <td align='center'>$data_abertura</td>
                            <td align='center'>$nome_revenda</td>
                            <td></td>
                            <td><a href='cadastro_os.php?os_id=$os' target='_blank'>
                                <img id='lancar_$i' border='0' $display_button_cancelado src='imagens/btn_lanca.gif'>
                            </a></td>
                            <td>
                                <a href='os_print.php?os=$os' target='_blank'><img border='0' src='imagens/btn_imprimir_".$botao.".gif'></a>
                            </td>
                    </tr>";
                }
            }
            
		}
		echo "</table>";
		echo "<p align='center'><b>"; fecho("total.de.%.registros",$con,$cook_idioma,array("$total_registro")); echo "</b></p>";
	}else{
		echo "<table border='0' align='center'>";
		echo "<tr>";
		echo "<td><img border='0' src='imagens/atencao.gif'></td>";
		echo "<td> &nbsp; <b>"; fecho("nao.foi.encontrado.nenhuma.os.nessa.pesquisa",$con,$cook_idioma); echo "</b></td>";
		echo "</tr>";
		echo "</table>";
	}
}
?>

<br>

<? include "rodape.php" ?>
