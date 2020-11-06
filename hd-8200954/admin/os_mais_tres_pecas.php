<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';
$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";

if (strlen(trim($_REQUEST["ano"])) > 0) $ano = trim($_REQUEST["ano"]);
if (strlen(trim($_REQUEST["mes"])) > 0) $mes = trim($_REQUEST["mes"]);
if (strlen(trim($_REQUEST["codigo_posto"])) > 0) $codigo_posto = trim($_REQUEST["codigo_posto"]);
if (strlen(trim($_REQUEST["posto_nome"])) > 0) $posto_nome = trim($_REQUEST["posto_nome"]);

if($_POST['ajax']){
    $os = $_POST['os'];

    $sql = "SELECT  tbl_peca.referencia,
                    tbl_peca.descricao,
                    tbl_os.sua_os
            FROM    tbl_peca
            JOIN    tbl_os_item     ON  tbl_os_item.peca            = tbl_peca.peca
            JOIN    tbl_os_produto  ON  tbl_os_produto.os_produto   = tbl_os_item.os_produto
            JOIN    tbl_os          ON  tbl_os.os                   = tbl_os_produto.os
                                    AND tbl_os.fabrica              = $login_fabrica
            WHERE   tbl_os.os = $os
    ";

    $res = pg_query($con,$sql);
    $rows = pg_num_rows($res);
    $retorno = "
            <tr>\n
                <td rowspan='$rows'>Peças da OS ".pg_fetch_result($res,0,sua_os)."</td>\n
    ";
    for($i = 0; $i < $rows; $i++){
        if($i > 0){
            $retorno .= "<tr>\n";
        }
        $retorno .= "
                <td align='left'>".pg_fetch_result($res,$i,referencia)." - ".pg_fetch_result($res,$i,descricao)."</td>\n
            </tr>\n
        ";
    }
    echo $retorno;
    exit;
}

if($_POST['acao'] == 'submit')
{
	if(in_array($login_fabrica,array(15,40,101,106,108,111,115,116,120,201,123,91))){
		$data_inicial = $_REQUEST["data_inicial"];
		$data_final = $_REQUEST["data_final"];

		if(empty($data_inicial) OR empty($data_final)){
				$msg_erro = "Data Inválida";
		}

		if(strlen($msg_erro)==0){
			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi))
				$msg_erro = "Data Inválida";
		}

		if(strlen($msg_erro)==0){
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf))
				$msg_erro = "Data Inválida";
		}

		if(strlen($msg_erro)==0){
			$aux_data_inicial = "$yi-$mi-$di";
			$aux_data_final = "$yf-$mf-$df";
		}

		if(strlen($msg_erro)==0){

            if($aux_data_final > DATE('Y-m-d')){
                $aux_data_final = DATE('Y-m-d');
                $_REQUEST["data_final"] = Date('d/m/Y');
            }

			if($aux_data_final < $aux_data_inicial){
                $msg_erro = "Data Inválida";
			}else{
				$data_inicial = $aux_data_inicial." 00:00:00";
				$data_final = $aux_data_final." 23:59:59";
			}
		}

		$linha = $_POST["linha"];
		$familia = trim($_REQUEST["familia"]);
        $produto_referencia = trim($_REQUEST["produto_referencia"]);
        $produto_descricao = trim($_REQUEST["produto_descricao"]);
        $estado = trim($_REQUEST["estado"]);
		$qtde_pecas = trim($_REQUEST["qtde_pecas"]);
        if($login_fabrica == 15){
            $os_aberta  = $_POST['os_aberta'];
            $pecas      = $_POST['pecas'];

            if(!empty($pecas)){
                if(strstr($pecas,"|") !== false){
                    $aux_pecas = str_replace("|","','",$pecas);
                    $aux_pecas = "'".$aux_pecas."'";
                }else{
                    $aux_pecas = "'".$pecas."'";
                }
            }else{
                $aux_pecas = "";
            }
        }
        if(!empty($produto_referencia)){
            $sql = "SELECT
                        produto
                    FROM tbl_produto
                        JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
                    WHERE tbl_linha.ativo IS TRUE
                        AND tbl_produto.ativo IS TRUE
                        AND tbl_produto.referencia = '$produto_referencia'
                        AND fabrica = $login_fabrica";
            $res = pg_exec($con,$sql);

            if(pg_num_rows($res) > 0){
                $produto = pg_result($res,0,0);
            }else{
                $msg_erro = "Produto inválido.";
            }
        }

		if(strlen($aux_data_inicial) > 0 && strlen($aux_data_final) > 0 && strlen($msg_erro) == 0){
			$sql = "SELECT '$aux_data_inicial'::date + interval '1 months' > '$aux_data_final'";
			$res = pg_query($con,$sql);
			$periodo = pg_fetch_result($res,0,0);
			if($periodo == 'f')
				$msg_erro = "Data Inválida - Período maior que um mês";
		}

	}else{
		if ($login_fabrica == 3){
			$linha   = $_POST['linha_total'];
			$tipo_os = $_POST['tipo_os'];
		}else{
			$linha = $_POST['linha'];
		}

		if (strlen($mes)==0){
			$msg_erro .= "Por favor escolha o mês.<br>";
		}

		if (strlen($ano)==0){
			$msg_erro .= "Por favor escolha o ano.<br>";
		}

        if(strlen($msg_erro) == 0){
            $data_inicial = date("Y-m-d", mktime(0, 0, 0, $mes, 1, $ano));
            $data_final = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));
        }
	}
}
if(in_array($login_fabrica, array(40,106,108,111)))
    $layout_menu = "Gerencia";
else
    $layout_menu = "auditoria";

if(in_array($login_fabrica, array(101,115,116,122,143))){
	$titulo = "OS com 5 peças ou mais";
	$title = "OS COM 5 PEÇAS OU MAIS";
}else{
	$titulo = "Relatório de OS com três peças ou mais";
	$title = "RELATÓRIO DE OS COM TRÊS PEÇAS OU MAIS";
}
include 'cabecalho.php';
include "javascript_pesquisas.php";
include "javascript_calendario_new.php";
include_once "../js/js_css.php";

if ($_POST['acao'] == 'submit' && strlen($msg_erro) == 0) {
	//include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}

//Funcao que faz a mascara
function mascara($val, $mask) {
    $maskared = '';
    $k = 0;

    for($i = 0; $i<=strlen($mask)-1; $i++) {
        if($mask[$i] == '#') {
            if(isset($val[$k])) $maskared .= $val[$k++];
        } else {
            if(isset($mask[$i])) $maskared .= $mask[$i];
        }
    }
    return $maskared;
}
?>

<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<script type='text/javascript'>
	$().ready(function(){
        var login_fabrica = <?=$login_fabrica?>;
        Shadowbox.init();

		$( "#data_inicial" ).datepick({startDate : "01/01/2000"});
		$( "#data_inicial" ).mask("99/99/9999");

		$( "#data_final" ).datepick({startDate : "01/01/2000"});
		$( "#data_final" ).mask("99/99/9999");

		if(login_fabrica == 15 || login_fabrica == 74 || login_fabrica == 150) {
            $("span[id^=ver_pecas_]").css({
                "color"             :"#63798d"  ,
                "font-weight"       : "bold"    ,
                "text-decoration"   : "none"    ,
                "cursor"            : "pointer"
            });

            //EXIBE PEÇAS
            $('span[id^=ver_pecas_]').click(function(){
                relBtn      = $(this).attr('id');
                quebraRel   = relBtn.split("_");
                if ($('tr#'+quebraRel[2]).hasClass('hideTr')){
                    $.ajax({
                        type:"POST",
                        url:"<?=$PHP_SELF?>",
                        dataType:"html",
                        data:{
                            ajax:true,
                            os:quebraRel[2]
                        },
                        beforeSend:function(){
                            $('span[id=ver_pecas_'+quebraRel[2]+']').text('Esconder Peças');
                            $('tr#'+quebraRel[2]).toggle('slow');
                            $('tr#'+quebraRel[2]).removeClass('hideTr');
                        }
                    })
                    .done(function(data){
                        $('tr#'+quebraRel[2]+' td table').html(data);
                    });
                }else{
                    $('tr#'+quebraRel[2]).toggle('slow');
                    $('tr#'+quebraRel[2]).addClass('hideTr');
                    $('span[id=ver_pecas_'+quebraRel[2]+']').text('Ver Peças');
                }
            });
		}
	});

    function pesquisaPosto(campo,tipo){
        var campo = campo.value;

        if (jQuery.trim(campo).length > 2){
            Shadowbox.open({
                content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
                player:	    "iframe",
                title:		"Pesquisa Posto",
                width:	    800,
                height:	    500
            });
        }else
            alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }

    function pesquisaProduto(campo,tipo){

        if (jQuery.trim(campo.value).length > 2){
            Shadowbox.open({
                content:	"produto_pesquisa_2_nv.php?"+tipo+"="+campo.value,
                player:	    "iframe",
                title:		"Pesquisa Produto",
                width:	    800,
                height:	    500
            });
        }else{
            alert("Informar toda ou parte da informação para realizar a pesquisa!");
            campo.focus();
        }
    }


    function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
        gravaDados('codigo_posto',codigo_posto);
        gravaDados('posto_nome',nome);
    }

    function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria, posicao){
        gravaDados('produto_referencia',referencia);
        gravaDados('produto_descricao',descricao);
    }

    function gravaDados(name, valor){
        try{
            $("input[name="+name+"]").val(valor);
        } catch(err){
            return false;
        }
    }

	function submit_form()
	{
		var fabrica = "<?=$login_fabrica?>";

		if (fabrica == 3)
		{
			var linha = "";

			$("input[name=linha]:checked").each(function(){
				linha += $(this).val() + ",";
			});

			$('#linha_total').val(linha);
		}

		document.getElementById('acao').value = 'submit';
		document.frm_consulta.submit();
	}

	function pesquisaPeca(peca,tipo,item){

        if (jQuery.trim(peca.value).length > 2){
            Shadowbox.open({
                content:    "peca_pesquisa_nv.php?"+tipo+"="+peca.value+"&item="+item,
                player: "iframe",
                title:      "Peça",
                width:  800,
                height: 500
            });
        }else{
            alert("Informar toda ou parte da informação para realizar a pesquisa!");
            peca.focus();
        }

    }

    function retorna_dados_peca(peca,referencia,descricao,ipi,origem,estoque,unidade,ativo,posicao,item){
        gravaDados('peca_referencia_multi_item',referencia);
        gravaDados('peca_descricao_multi_item',descricao);
    }

    function gravaDados(name, valor){
        try{
            $("input[name="+name+"]").val(valor);
        } catch(err){
            return false;
        }
    }

	function addItPecaItem() {
        var pecas = "";
        var pecasCount = $("#multi_peca_item option").length;
        if ($('#peca_referencia_multi_item').val()=='') {
            return false;
        }

        if ($('#peca_descricao_multi_item').val()==''){
            return false;
        }

        $('#multi_peca_item').append("<option value='"+$('#peca_referencia_multi_item').val()+"'>"+$('#peca_referencia_multi_item').val()+"-"+ $('#peca_descricao_multi_item').val()+"</option>");

        $("#multi_peca_item option").each(function(i){
            pecas += $(this).val();
            if(i != pecasCount){
                pecas += "|";
            }
        });
        $("#pecas").val(pecas);

        if($('.select').length ==0) {
            $('#multi_peca_item').addClass('select');
        }

        $('#peca_referencia_multi_item').val("").focus();
        $('#peca_descricao_multi_item').val("");

    }

    function delItPecaItem() {
        var pecas = "";
        $('#multi_peca_item option:selected').remove();
        var pecasCount = $("#multi_peca_item option").length;

        $("#multi_peca_item option").each(function(i){
            pecas += $(this).val();
            if(i != pecasCount - 1){
                pecas += "|";
            }
        });
        $("#pecas").val(pecas);

        if($('.select').length ==0) {
            $('#multi_peca_item').addClass('select');
        }

    }
</script>
<style type="text/css">
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
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
	text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.hideTr{
    display:none;
}
acronym
{
	text-decoration: none; !important
}
</style>
<?

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas","AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal","ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais","MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba", "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro", "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima","RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe", "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

echo "<form name='frm_consulta' id='frm_consulta' method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='acao' id='acao' value=''>";
echo "<table border='0' cellspacing='0' cellpadding='6' align='center' class='formulario' width='700'>";
if (strlen($msg_erro) > 0) {
	echo "<tr class='msg_erro'><td colspan='5'>".$msg_erro."</td></tr>";
}
	echo "<tr class='titulo_tabela'>";
		echo "<td colspan='5'>Parâmetros de Pesquisa</td>";
	echo "</tr>";
	echo "<tr >";
		echo "<td width='100'>&nbsp;</td>";
		if(in_array($login_fabrica,array(15,40,101,106,108,111,115,116,120,201,123,91))){
			echo "<td align='left'>Data Inicial<br>";
				echo "<input type='text' id='data_inicial' name='data_inicial' value='". $_REQUEST["data_inicial"]."' class='frm' size='12' />";
			echo "</td>";
			echo "<td align='left' >Data Final<br>";
				echo "<input type='text' id='data_final' name='data_final' value='". $_REQUEST["data_final"]."' class='frm' size='12' />";
			echo "</td>";
		}else{
			echo "<td align='left'>Mês<br>
					<select name='mes' size='1' class='frm'>";
						echo "<option value=''></option>";
							for ($i = 1 ; $i <= count($meses) ; $i++) {
                                echo "<option value='$i'";
                                if ($mes == $i) echo " selected";
                                echo ">" . $meses[$i] . "</option>";
							}
				echo "</select>";
			echo "</td>";
			echo "<td align='left'>Ano<br>
				<select name='ano' size='1' class='frm'>";
					echo "<option value=''></option>";
					for ($i = 2003 ; $i <= date("Y") ; $i++)
                      $anos[] = $i;

                    foreach(array_reverse($anos) as $i){
                        $selected = ($ano == $i) ? " selected='selected' " : "";
                        echo "<option value='{$i}' {$selected} >$i</option>";
                    }
				echo "</select>";
			echo "</td>";
		}
echo "</tr>";
function verificaSelect($valor1, $valor2){
    if($valor1 == $valor2)
        return " selected='selected' style='color: #F00' ";
}

if(in_array($login_fabrica,array(15,40,101,106,108,111,115,116,122))){
	if(!in_array($login_fabrica,array(15))){
        echo "<tr >";
            echo "<td width='100'>&nbsp;</td>";
            echo "<td align='left' nowrap>Linha<br>";
                $sql = "SELECT linha, nome FROM tbl_linha WHERE fabrica = $login_fabrica AND ativo = true ORDER BY nome ASC;";
                $res = pg_query($con,$sql);

                if(pg_num_rows ($res)){
                        echo "<select name='linha' class='frm'>";
                            echo "<option value='' selected ></option>";

                        for($i = 0; $i < pg_numrows ($res); $i++){
                            $codigo_linha = pg_result ($res,$i,linha);
                            $nome_linha = pg_result ($res,$i,nome);
                                    echo "<option value='$codigo_linha' ".verificaSelect($codigo_linha, $linha)." >$nome_linha</option>";

                        }
                        echo "</select>";
                }
            echo "</td>";
            echo "<td align='left' >Família<br>";
                $sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao ASC;";
                $res = pg_query($con,$sql);

                echo "<select name='familia' class='frm'>";
                    if(pg_numrows ($res) > 0){
                        echo "<option value='' selected ></option>";

                        for($i = 0; $i < pg_numrows ($res); $i++){
                            $codigo_familia= pg_result ($res,$i,familia);
                            $nome_familia = pg_result ($res,$i,descricao);

                            echo "<option value='$codigo_familia' ".verificaSelect($codigo_familia, $familia)." >$nome_familia</option>";
                        }
                    }
                echo "</select>";
            echo "</td>";
        echo "</tr>";
    }
    if(in_array($login_fabrica,array(15,40,106,108,111))){
        echo "<tr>";
            echo "<td>&nbsp;</td>";
            echo "<td align='left'>";
                echo "Produto Referência<br>";
                echo "<input type='text' name='produto_referencia' id='produto_referencia' size='15'  value='{$produto_referencia}' class='frm' />";
                echo "<img border='0' src='imagens/lupa.png' style='cursor: pointer;' align='absmiddle' alt='Clique aqui para pesquisa' onclick='pesquisaProduto(document.frm_consulta.produto_referencia, \"referencia\")' />";
            echo "</td>";
            echo "<td align='left'>";
                echo "Produto Descrição<br>";
                echo "<input type='text' name='produto_descricao' id='produto_descricao' size='30'  value='{$produto_descricao}' class='frm' />";
                echo "<img border='0' src='imagens/lupa.png' style='cursor: pointer;' align='absmiddle' alt='Clique aqui para pesquisa' onclick='pesquisaProduto(document.frm_consulta.produto_descricao, \"descricao\")' />";
            echo "</td>";
             echo "<td>&nbsp;</td>";
        echo "</tr>";
    }
}
    echo "<tr >";
        echo "<td width='30'>&nbsp;</td>";
        echo "<td align='left'>";
            echo "Código Posto <br><input type='text' name='codigo_posto' size='15' value='$codigo_posto' class='frm'>";
            echo "<img border='0' src='imagens/lupa.png' style='cursor: hand;' align='absmiddle' alt='Clique aqui para pesquisar postos pelo código' onclick='javascript: pesquisaPosto(document.frm_consulta.codigo_posto, \"codigo\")'>";
        echo "</td>";
        echo "<td align='left'>";
            echo "Nome Posto<br><input type='text' name='posto_nome' size='30' value='$posto_nome' class='frm'>";
            echo "<img border='0' src='imagens/lupa.png' style='cursor: hand;' align='absmiddle' alt='Clique aqui para pesquisar postos pelo código' onclick='javascript: pesquisaPosto (document.frm_consulta.posto_nome, \"nome\")'>";
        echo "</td>";
    echo "</tr>";
    if(in_array($login_fabrica,array(15))){
?>
    <tr >
        <td>&nbsp;</td>
        <td colspan="2">
            <table border='0'>
                <tr>
                    <td width='168'>
                        Referência <br /><input class='frm' type="text" name="peca_referencia_multi_item"  id="peca_referencia_multi_item" value="" size="15" maxlength="20">&nbsp;
                        <IMG src='imagens/lupa.png' onClick="javascript: pesquisaPeca (document.frm_consulta.peca_referencia_multi_item,'referencia','item')"  style='cursor:pointer;'>
                    </td>

                    <td width='150'>
                        Descrição <br /><input class='frm' type="text" name="peca_descricao_multi_item" id="peca_descricao_multi_item" value="" size="30" maxlength="50">&nbsp;
                        <IMG src='imagens/lupa.png' onClick="javascript: pesquisaPeca(document.frm_consulta.peca_descricao_multi_item,'descricao','item')"  style='cursor:pointer;' align='absmiddle'>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td style="text-align:right;margin-right:60px;">
                        <input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPecaItem();'>
                    </td>
                </tr>
                <tr>
                    <td colspan='3'>
                        <select multiple="multiple" SIZE='6' id='multi_peca_item' name="multi_peca_item[]" class='frm' style='width:610px'>
                        <?
                            if(strlen($pecas) > 0) {
                                $monta_pecas = explode("|",$pecas);
                                foreach($monta_pecas as $v) {
                                    $sql = " SELECT tbl_peca.referencia,
                                                    tbl_peca.descricao
                                            FROM    tbl_peca
                                            WHERE   fabrica = $login_fabrica
                                            AND     referencia  = '".$v."'";
                                    $res = pg_query($con,$sql);
                                    if(pg_num_rows($res) > 0){
                                        $referencia = pg_fetch_result($res,0,referencia);
                                        $descricao = pg_fetch_result($res,0,descricao) ;
                                        if(!empty($referencia)) {
                                            echo "<option value='".$referencia."' >".$referencia . " - " . $descricao ."</option>";
                                        }

                                    }
                                }
                            }
                        ?>
                        </select>
                        <br>
                        <input type="hidden" name="pecas" id="pecas" value="<?=$pecas?>" />
                        <input TYPE="BUTTON" VALUE="Remover" onClick="delItPecaItem();" class='frm'></input>
                        <strong style='font-weight:normal;color:gray;font-size:10px; float:right;'>(Selecione a peça e clique em 'Adicionar')</strong>

                    </td>
                </tr>
            </table>
        </td>
        <td>&nbsp;</td>
    </tr>
<?
    }
    echo "<tr >";
        echo "<td width='30'>&nbsp;</td>";
        echo "<td align='left'>";
            echo "Estado <br><select name='estado' class='frm' id='estado'>";
            echo "<option value='' selected ></option>";
            foreach ($array_estado as $k => $v) {
                echo "<option value='{$k}' ".verificaSelect($estado,$k)." >{$v}</option>\n";
            }
            echo "</selected>";
        echo "</td>";
		if (in_array($login_fabrica, array(3,91))){
			echo "<td align='left'>Qtde. Peças<br><input type='text' size='4' name='qtde_pecas' id='qtde_pecas' value='$qtde_pecas' class='frm' maxlength='4'></td>";
		}else if(in_array($login_fabrica,array(15))){
?>
            <td>
                <input type='checkbox' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> ><label for="os_aberta">Apenas OS em aberto</label>
            </td>
<?
        }else{
			 echo "<td align='left'>&nbsp;</td>";
		}
        echo "<td>&nbsp;</td>";
    echo "</tr>";

	if (in_array($login_fabrica, array(3)))
	{
		echo "<tr >";
			echo "<td>&nbsp;</td>";
		echo "</tr>";
		echo "</table>";
		echo "<center><div align='center' class='formulario' style='width: 700px; !important'>";
			echo "<div style='width:470px;'>";
				echo "<fieldset><legend>Linha</legend>";
				$sql = "SELECT linha, nome FROM tbl_linha WHERE fabrica = $login_fabrica AND ativo = true ORDER BY nome ASC;";
				$res = pg_query($con,$sql);

				if(pg_numrows ($res)){
						for($i = 0; $i < pg_numrows ($res); $i++){
							$codigo_linha = pg_result ($res,$i,linha);
							$nome_linha = pg_result ($res,$i,nome);
								$titulo_nome_linha = $nome_linha;
								switch ($titulo_nome_linha)
								{
								case 'AUDIO E VIDEO':
									$titulo_nome_linha = 'A/V';
								break;
								case 'Autoradio':
									$titulo_nome_linha = 'RAD';
								break;
								case 'Branca':
									$titulo_nome_linha = 'BRA';
								break;
								case 'Eletro-Eletrônico':
									$titulo_nome_linha = 'E/E';
								break;
								case 'Eletroportáteis':
									$titulo_nome_linha = 'ELE';
								break;
								case 'Informática':
									$titulo_nome_linha = 'INF';
								break;
								case 'LCD':
									$titulo_nome_linha = 'LCD';
								break;
								case 'Refrigeração':
									$titulo_nome_linha = 'REFR';
								break;
								case 'Split':
									$titulo_nome_linha = 'SPL';
								break;
								}
								$linha2 = explode(',',$linha);
								$check  = "";
								foreach($linha2 as $linha_check)
								{
									if ($linha_check == $codigo_linha)
									{
										$check = "CHECKED";
										break;
									}
								}
								echo "<input type='checkbox' name='linha' id='linha' value='$codigo_linha' $check>$titulo_nome_linha &nbsp;";
						}
						if ($login_fabrica == 3)
						{
							echo "<input type='hidden' name='linha_total' id='linha_total' value=''>";
						}
				}
			echo "</fieldset></div>";
		echo "</div>";
		echo "<div align='center' class='formulario' style='width: 700px; !important'><br>";
			echo "<div style='width:470px;'>";
			?>
				<fieldset>
				<legend>Tipo de OS</legend>
				<input type='radio' name='tipo_os' id='tipo_os' value='abertas' <?if($tipo_os <>'finalizadas') echo "CHECKED";?>>Abertas
				<input type='radio' name='tipo_os' id='tipo_os' value='finalizadas' <?if($tipo_os == 'finalizadas') echo "CHECKED";?>>Finalizadas
			<?
			echo "</fieldset></div>";
		echo "</div>";
	}
	echo "<tr>";
	echo "<td colspan='4'>";
	echo "<table border='0' cellspacing='0' cellpadding='6' align='center' class='formulario' width='700'>";
    echo "<tr>";
        echo "<td colspan='3' align='center'><input type='button' name='btn_acao' onclick='submit_form()' value='Pesquisar'><br><br></td>";
    echo "</tr>";

echo "</table>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</form>";
if ($_POST['acao'] == 'submit' and strlen($msg_erro)==0){

    if($login_fabrica == 15){
        if(!empty($pecas)){
            if(strstr($pecas,"|") !== false){
                $explode_pecas = explode("|",$pecas);
            }else{
                $explode_pecas[] = $pecas;
            }
            
	    foreach($explode_pecas as $v){
                $where_pecas .= "
                    JOIN    tbl_os_produto op_$v    ON  op_$v.os            = tmp_os_auditoria.os
                    JOIN    tbl_os_item oi_$v       ON  oi_$v.os_produto    = op_$v.os_produto
                                                    AND oi_$v.digitacao_item BETWEEN '$data_inicial' AND '$data_final'
                    JOIN    tbl_peca p_$v           ON  p_$v.peca           = oi_$v.peca
                                                    AND p_$v.fabrica        = $login_fabrica
                                                    AND p_$v.referencia     = '$v'
                ";
            }
        }
    }
	
	if($login_fabrica == 15){
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto ,
                        tbl_os.os,
                        tbl_posto.nome,
                        tbl_os.sua_os::text ,
                        TO_CHAR (tbl_os.data_abertura,'DD/MM/YYYY') AS abertura ,
                        TO_CHAR (tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao ,
                        tbl_posto.fone::text,
                        UPPER(tbl_posto.estado) AS estado,
                        tbl_produto.descricao,
                        tbl_produto.referencia 
                        INTO TEMP tmp_os_auditoria
                        FROM tbl_os
                        JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
                        JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto
                        AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
                        JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                        AND tbl_produto.fabrica_i = $login_fabrica
                        JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
                        JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
                        WHERE tbl_os.fabrica = $login_fabrica
                        AND tbl_os_item.digitacao_item BETWEEN '$data_inicial' AND '$data_final'
                        AND tbl_os.excluida IS NOT TRUE";

                        if(!empty($codigo_posto)){
                            $sql .=" AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
                        }

                        if(!empty($linha)){
                            $sql .=" AND tbl_produto.linha = $linha ";
                        }

                        if(!empty($os_aberta)){
                            $sql .="
                                    AND tbl_os.finalizada       IS NULL
                                    AND tbl_os.data_fechamento  IS NULL
                            ";
                        }

                        if(!empty($familia)){
                            $sql .=" AND tbl_produto.familia = $familia ";
                        }

                        if(!empty($produto)){
                            $sql .=" AND tbl_produto.produto = $produto ";
                        }

                        if(!empty($estado)){
                            $sql .=" AND tbl_posto.estado = '$estado' ";
                        }

        $sql .=" GROUP BY tbl_os.os,
                        		 tbl_posto_fabrica.codigo_posto,
                        		 tbl_posto.nome,
                        		 tbl_os.sua_os,
                        		 tbl_os.data_abertura,
                        		 tbl_os.data_digitacao,
                        		 tbl_posto.fone,
                        		 tbl_posto.estado,
                        		 tbl_produto.descricao,
                        		 tbl_produto.referencia 
                        HAVING COUNT (tbl_os_item.qtde) >= 3;

                        SELECT tmp_os_auditoria.os,
                        tmp_os_auditoria.sua_os,
                        tmp_os_auditoria.nome,
                        tmp_os_auditoria.codigo_posto,
                        tmp_os_auditoria.abertura,
                        tmp_os_auditoria.digitacao,
                        tmp_os_auditoria.fone,
                        tmp_os_auditoria.estado,
                        tmp_os_auditoria.descricao,
                        tmp_os_auditoria.referencia
                        FROM tmp_os_auditoria
                        $where_pecas
                        ";
	}else{
	    $sql = "SELECT  tbl_posto_fabrica.codigo_posto ,
                    tbl_os.os,
                    tbl_posto.nome,
                    tbl_os.sua_os::text ,
                    TO_CHAR (tbl_os.data_abertura,'DD/MM/YYYY') AS abertura ,
                    TO_CHAR (tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao ,
                    tbl_posto.fone::text,
                    UPPER(tbl_posto.estado) AS estado,
                    tbl_produto.descricao,
                    tbl_produto.referencia
			FROM    tbl_os
			JOIN    tbl_posto           ON  tbl_os.posto            = tbl_posto.posto
			JOIN    tbl_posto_fabrica   ON  tbl_os.posto            = tbl_posto_fabrica.posto
                                        AND tbl_os.fabrica          = tbl_posto_fabrica.fabrica
			JOIN    tbl_produto         ON  tbl_os.produto          = tbl_produto.produto
                                        AND tbl_produto.fabrica_i   = $login_fabrica
			WHERE   tbl_os.fabrica  = $login_fabrica
			AND     tbl_os.excluida IS NOT TRUE";

                        if(!empty($codigo_posto))
                            $sql .=" AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";

                        if($login_fabrica == 3){
							if (empty($linha)){
								$sql .= "";
							}else{
								$linha = substr($linha, 0, -1);
								$sql .= " AND tbl_produto.linha IN ($linha)";
							}
						}elseif(!empty($linha)){
                            $sql .=" AND tbl_produto.linha = $linha ";
                        }
                        if(!empty($os_aberta)){
							$sql .="
                                    AND tbl_os.finalizada       IS NULL
                                    AND tbl_os.data_fechamento  IS NULL
							";
						}

                        if(!empty($familia))
                            $sql .=" AND tbl_produto.familia = $familia ";

                        if(!empty($produto))
                            $sql .=" AND tbl_produto.produto = $produto ";

                        if(!empty($estado))
                            $sql .=" AND tbl_posto.estado = '$estado' ";
						if ($login_fabrica == 3){
							$join_tipo_os = " JOIN tbl_os ON tbl_os.os = tbl_os_produto.os";
							if ($tipo_os == 'finalizadas'){
								$sql_tipo_os  = " AND tbl_os.finalizada IS NOT NULL AND tbl_os.data_fechamento IS NOT NULL";
							}else if ($tipo_os == 'abertas'){
								$sql_tipo_os  = " AND tbl_os.finalizada IS NULL AND tbl_os.data_fechamento IS NULL";
							}
						}
			$sql .= " AND   tbl_os.os IN (
				SELECT
				tbl_os_produto.os
				FROM tbl_os_produto
				JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i=$login_fabrica
				JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
				$join_tipo_os
				WHERE tbl_os_item.digitacao_item BETWEEN '$data_inicial' AND '$data_final'
				AND tbl_servico_realizado.troca_de_peca IS TRUE
				$sql_tipo_os
				$where_pecas
				GROUP BY tbl_os_produto.os";
				if(in_array($login_fabrica, array(101,115,116,122))){
					$sql .= " HAVING COUNT (tbl_os_item.qtde) >= 5) ";
				}elseif(in_array($login_fabrica, array(106,108,111))){
                    $sql .= " HAVING COUNT (tbl_os_item.qtde) > 3) ";
				}elseif(in_array($login_fabrica, array(3,120,201,123,91))){
					if (empty($qtde_pecas)){
						$sql .= " HAVING COUNT (tbl_os_item.qtde) >= 3)";
					}else{
						$sql .= " HAVING COUNT (tbl_os_item.qtde) >= $qtde_pecas)";
						$sql .= " ORDER BY tbl_posto.nome, tbl_produto.descricao, tbl_os.sua_os";
					}
				}else{
					$sql .= " HAVING COUNT (tbl_os_item.qtde) >= 3) ";
					$sql .= " ORDER BY tbl_posto.nome, tbl_produto.descricao, tbl_os.sua_os";
				}
	}
    
	$res = pg_exec ($con,$sql);

	$qtde_os = pg_numrows ($res);

	if($qtde_os>0){
        if(in_array($login_fabrica, array(40,101,106,108,111,115,116,120,201,122,123))){
            $colspan = 7;
        }elseif(in_array($login_fabrica, array(3))){
			$colspan = 10;
        }else{
            $colspan = 5;
        }
	echo "<BR><table border='0' cellpadding='4' cellspacing='1' align='center' class='tabela' width='700'>";
    if(!in_array($login_fabrica, array(40,101,106,108,111,115,116,120,201,122,123)))
		echo "<tr class='titulo_tabela'><td colspan='$colspan'> <font style='font-size:14px;'>Relação de OS que tem 3 ou mais peças no pedido</font></td></tr>";

	echo "<tr class='subtitulo'><td colspan='$colspan'>Total de $qtde_os OS $tipo_os encontradas</td></tr>";
        echo "<tr class='titulo_coluna'>";
        echo "<td><B>OS</B></td>";
        echo ($login_fabrica == 3) ? "<td><B>Data Digitação</B></td>" : "";
        echo "<td><B>PA</B></td>";
		if($login_fabrica == 87){
			echo "<td><B>Nome do Canal</B></td>";
		} else {
			echo "<td><B>Nome do Posto</B></td>";
		}
        if(in_array($login_fabrica, array(40,106,108,111,3,115,116))){
            if (!in_array($login_fabrica, array(3)))
            {
				echo "<td><B>UF</B></td>";
			}
            echo "<td><B>Telefone</B></td>";
        }
        echo "<td><B>Produto</B></td>";
        if($login_fabrica == 15){
            echo "<td>&nbsp;</td>";
        }
		if (in_array($login_fabrica, array(3)))
		{
			echo "<td nowrap>Qtde. <br>Peças</td>";
			echo "<td nowrap>Peças <br>datas dif.</td>";
			echo "<td nowrap>1° Pedido</td>";
			echo "<td nowrap>2° Pedido</td>";
			echo "<td nowrap>3° Pedido <Br>em diante</td>";
		}
        echo "</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$os           = pg_result ($res,$i,os);
		$fone         = pg_result ($res,$i,fone);
		$sua_os       = pg_result ($res,$i,sua_os);
		$digitacao    = pg_result ($res,$i,'digitacao');
		$codigo_posto = pg_result ($res,$i,codigo_posto);
		$nome         = pg_result ($res,$i,nome);
		$descricao    = pg_result ($res,$i,descricao);
		$referencia   = pg_result ($res,$i,referencia);
        $estado       = pg_result ($res,$i,estado);

		if (in_array($login_fabrica, array(3)))
		{
			$sql_qtde = "SELECT
							count(qtde) AS qtde_pecas
						FROM
							tbl_os_produto
						JOIN
							tbl_os_item
								ON tbl_os_item.os_produto = tbl_os_produto.os_produto and tbl_os_item.fabrica_i=3
						JOIN
							tbl_servico_realizado
								ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
						WHERE
							tbl_os_item.digitacao_item
								BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_servico_realizado.troca_de_peca
								IS TRUE
						AND os = $os";
			$res_qtde = pg_query($con,$sql_qtde);
			$qtde_pecas   = pg_result($res_qtde,0,qtde_pecas);

			$sql_data_diff = "SELECT
								count(*) AS data_diff
							  FROM tbl_os_item
							  JOIN tbl_os_produto
										ON tbl_os_item.os_produto = tbl_os_produto.os_produto
										AND tbl_os_item.fabrica_i = $login_fabrica
							  JOIN tbl_servico_realizado
										ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
							  WHERE tbl_os_item.digitacao_item
										BETWEEN '$data_inicial' AND '$data_final'
							  AND tbl_os_produto.os = $os
							  AND tbl_servico_realizado.troca_de_peca
										IS TRUE
							  GROUP BY tbl_os_item.digitacao_item::date
							  ORDER BY tbl_os_item.digitacao_item::date";
			$res_data_diff = pg_query($con,$sql_data_diff);
			$row_data_diff = pg_num_rows($res_data_diff);
		}

		$cor = "#FFFFFF";
		if ($i % 2 == 0) $cor = '#D2E6FF';

		echo "<tr bgcolor='$cor'>";
            echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
            echo ($login_fabrica == 3) ? "<td align='left'>$digitacao</td>" : "";
            echo "<td align='left'>$codigo_posto</td>";
            echo "<td align='left' nowrap>$nome</td>";
            if(in_array($login_fabrica, array(40,101,106,108,111,3,115,116))){
                if (!in_array($login_fabrica, array(3)))
                {
					echo "<td align='left'>$estado</td>";
				}
				echo "<td align='left' nowrap>$fone</td>";
            }
            echo "<td align='left' nowrap>$referencia - $descricao</td>";
            if($login_fabrica == 15){
                echo "
                <td align='center'>\n
                    <span id='ver_pecas_$os'>Ver Peças</span>\n
                </td>\n
                ";
            }
			if (in_array($login_fabrica, array(3)))
			{
				echo "<td align='center'>$qtde_pecas</td>";
				echo "<td align='center'>";
				$data_diff_total = 0;
				if ($row_data_diff > 1)
				{
					for ($z = 0; $z < $row_data_diff; $z++)
					{
						$data_diff = pg_result($res_data_diff,$z,data_diff);
						if ($z > 0)
						{
							$data_diff_total = $data_diff_total + $data_diff;
						}
					}
				}

				if ($data_diff_total > 0)
				{
					echo $data_diff_total;
				}
				else
				{
					echo 0;
				}
				echo "</td>";

				for ($z = 0; $z < 1; $z++)
				{
						$limit = pg_result($res_data_diff,$z,data_diff);
						$sql_pri_ped = "SELECT
											tbl_peca.referencia AS ref,
											tbl_peca.descricao  AS desc
										FROM tbl_os_item
										JOIN tbl_os_produto
												ON tbl_os_produto.os_produto = tbl_os_item.os_produto
										JOIN tbl_servico_realizado
												ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
										JOIN tbl_peca
												ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
										WHERE tbl_servico_realizado.troca_de_peca
												IS TRUE
										AND tbl_os_item.digitacao_item
												BETWEEN '$data_inicial' AND '$data_final'
										AND tbl_os_item.fabrica_i = $login_fabrica
										AND os = $os
										LIMIT $limit;";
						$res_pri_ped = pg_query($con,$sql_pri_ped);
						$pri_rows    = @pg_num_rows($res_pri_ped);

						if ($pri_rows > 1)
						{
							$marcador = "style='border-right-style: dashed; border-right-width: 2px; border-right-color: red;'";
						}
						else
						{
							$marcador = "";
						}
						echo "<td align='left' $marcador nowrap>";
						if ($pri_rows > 1)
						{
							echo "<acronym nowrap title='";
							for ($y = 0; $y < pg_num_rows($res_pri_ped); $y++)
							{
								$pri_ped_ref  = pg_result($res_pri_ped,$y,ref);
								$pri_ped_desc = pg_result($res_pri_ped,$y,desc);
								echo "$pri_ped_ref - $pri_ped_desc \n";
							}
							echo "'>". $pri_ped_ref = pg_result($res_pri_ped,0,ref)." - ".$pri_ped_desc = pg_result($res_pri_ped,0,desc) ."</acronym>";
						}
						else if ($pri_rows > 0)
						{
							echo $pri_ped_ref = pg_result($res_pri_ped,0,ref)." - ".$pri_ped_desc = pg_result($res_pri_ped,0,desc);
						}
						echo "</td>";
				}

				for ($z = 1; $z < 2; $z++)
				{
						$limit = @pg_result($res_data_diff,$z,data_diff);
						$x = $z - 1;
						$offset = @pg_result($res_data_diff,$x,data_diff);
						$sql_seg_ped = "SELECT
											tbl_peca.referencia AS ref,
											tbl_peca.descricao  AS desc
										FROM tbl_os_item
										JOIN tbl_os_produto
												ON tbl_os_produto.os_produto = tbl_os_item.os_produto
										JOIN tbl_servico_realizado
												ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
										JOIN tbl_peca
												ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
										WHERE tbl_servico_realizado.troca_de_peca
												IS TRUE
										AND tbl_os_item.digitacao_item
												BETWEEN '$data_inicial' AND '$data_final'
										AND tbl_os_item.fabrica_i = $login_fabrica
										AND os = $os
										OFFSET $offset
										LIMIT  $limit;";
						$res_seg_ped = @pg_query($con,$sql_seg_ped);
						$seg_rows    = @pg_num_rows($res_seg_ped);

						if ($seg_rows > 1)
						{
							$marcador = "style='border-right-style: dashed; border-right-width: 2px; border-right-color: red;'";
						}
						else
						{
							$marcador = "";
						}
						echo "<td align='left' $marcador nowrap>";
						if ($seg_rows > 1)
						{
							echo "<acronym title='";
							for ($y = 0; $y < @pg_num_rows($res_seg_ped); $y++)
							{
								$seg_ped_ref = @pg_result($res_seg_ped,$y,ref);
								$seg_ped_desc = @pg_result($res_seg_ped,$y,desc);
								echo "$seg_ped_ref - $seg_ped_desc\n";
							}
							echo "'>". $seg_ped_ref = @pg_result($res_seg_ped,0,ref)." - ".$seg_ped_desc = @pg_result($res_seg_ped,0,desc) ."</acronym>";
						}
						else if ($seg_rows > 0)
						{
							echo $seg_ped_ref = @pg_result($res_seg_ped,0,ref)." - ".$seg_ped_desc = @pg_result($res_seg_ped,0,desc);
						}
						echo "</td>";
				}

				for ($z == 2; $z < 3; $z++)
				{
					$off1 = @pg_result($res_data_diff,0,data_diff);
					$off2 = @pg_result($res_data_diff,1,data_diff);
					$offset = $off1 + $off2;
					$sql_ter_ped = "SELECT
										tbl_peca.referencia AS ref,
										tbl_peca.descricao  AS desc
									FROM tbl_os_item
									JOIN tbl_os_produto
											ON tbl_os_produto.os_produto = tbl_os_item.os_produto
									JOIN tbl_servico_realizado
											ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
									JOIN tbl_peca
											ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
									WHERE tbl_servico_realizado.troca_de_peca
											IS TRUE
									AND tbl_os_item.digitacao_item
											BETWEEN '$data_inicial' AND '$data_final'
									AND tbl_os_item.fabrica_i = $login_fabrica
									AND os = $os
									OFFSET $offset;";
					$res_ter_ped = @pg_query($con,$sql_ter_ped);
					$ter_rows    = @pg_num_rows($res_ter_ped);

					if ($ter_rows > 1)
					{
						$marcador = "style='border-right-style: dashed; border-right-width: 2px; border-right-color: red;'";
					}
					else
					{
						$marcador = "";
					}
					echo "<td align='left' $marcador nowrap>";
					if ($ter_rows > 1)
					{
						echo "<acronym title='";
						for ($y = 0; $y < @pg_num_rows($res_ter_ped); $y++)
						{
							$ter_ped_ref = @pg_result($res_ter_ped,$y,ref);
							$ter_ped_desc = @pg_result($res_ter_ped,$y,desc);
							echo "$ter_ped_ref - $ter_ped_desc\n";
						}
						echo "'>" . $ter_ped_ref = @pg_result($res_ter_ped,0,ref)." - ".$ter_ped_desc = @pg_result($res_ter_ped,0,desc) . "</acronym>";
					}
					else if ($ter_rows > 0)
					{
						echo $ter_ped_ref = @pg_result($res_ter_ped,0,ref)." - ".$ter_ped_desc = @pg_result($res_ter_ped,0,desc);
					}
					echo "</td>";
				}
			}
		echo "</tr>";
		if($login_fabrica == 15){
            echo "
                <tr class='hideTr' id='$os' >\n
                    <td colspan='5'>\n
                        <table style='width:100%' border='0'>\n
                        </table>\n
                    </td>\n
                </tr>\n
            ";
		}
	}
	echo "</table>";
	// HD 16467
	flush();
		/*
		echo "<br><br>";
		echo"<table  border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
		echo "</tr>";
		echo "</table>";
		*/
		flush();

		$data = date ("dmY");

        $data_xls = date("Y-m-d_H-i-s");

        if(in_array($login_fabrica, array(101,115,116,120,201,122))){
            $arquivo_nome = "relatorio-os-mais-cinco-pecas-$login_fabrica-$data_xls.xls";
            $arquivo_nome_tmp = "relatorio-os-mais-cinco-pecas-$login_fabrica-tmp.xls";
        }else{
            $arquivo_nome = "relatorio-os-mais-tres-pecas-$login_fabrica-$data_xls.xls";
            $arquivo_nome_tmp = "relatorio-os-mais-tres-pecas-$login_fabrica-tmp.xls";
        }

        $path       = "xls/";
        $path_tmp   = "/tmp/";

        $arquivo_completo     = $path.$arquivo_nome;
        $arquivo_completo_tmp = $path_tmp.$arquivo_nome_tmp;

		if(in_array($login_fabrica, array(101,115,116,122))){
            echo `rm $arquivo_completo_tmp`;
			$fp = fopen ($arquivo_completo_tmp,"w+");
		}else{
			echo `rm $arquivo_completo_tmp`;
            $fp = fopen ($arquivo_completo_tmp,"w+");
		}

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		if(in_array($login_fabrica, array(101,115,116,122)))
			fputs ($fp,"<title>Relação de OS que tem 5 ou mais peças no pedido");
		else
			fputs ($fp,"<title>Relação de OS que tem 3 ou mais peças no pedido");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		fputs ($fp, "<table border='1' cellpadding='4' cellspacing='1'  align='center' style='font-family: verdana; font-size: 11px'>");
		fputs ($fp, "<tr>");
		fputs ($fp, "<td bgcolor='#9FB5CC'><b>OS</b></td>");
		if (in_array($login_fabrica, array(3)))
			{
				fputs ($fp, "<td bgcolor='#9FB5CC'><b>Data Digitação</b></td>");
			}

		fputs ($fp, "<td bgcolor='#9FB5CC'><b>PA</b></td>");
		if ($login_fabrica == 87) {
			fputs ($fp, "<td bgcolor='#9FB5CC'><b>Nome do Canal</b></td>");
		} else {
			fputs ($fp, "<td bgcolor='#9FB5CC'><b>Nome do Posto</b></td>");
		}
		if (in_array($login_fabrica, array(40,101,106,108,111,3,115,116))) {
			if (in_array($login_fabrica, array(3)))
			{
				fputs ($fp, "<td bgcolor='#9FB5CC'><b>UF</b></td>");
			}
            fputs ($fp, "<td bgcolor='#9FB5CC'><b>Telefone</b></td>");
        }
		fputs ($fp, "<td bgcolor='#9FB5CC'><b>Produto</b></td>");
		if (in_array($login_fabrica, array(3)))
		{
			fputs ($fp, "<td bgcolor='#9FB5CC'><b>Qtde. Peças</b></td>");
			fputs ($fp, "<td bgcolor='#9FB5CC'><b>Peças com datas diferentes</b></td>");
			fputs ($fp, "<td bgcolor='#9FB5CC'><b>1° Pedido</b></td>");
			fputs ($fp, "<td bgcolor='#9FB5CC'><b>2° Pedido</b></td>");
			fputs ($fp, "<td bgcolor='#9FB5CC'><b>3° Pedido em diante</b></td>");
		}
		fputs ($fp, "</tr>");


		for ($j = 0 ; $j < pg_numrows ($res) ; $j++)
		{
			$sua_os       = pg_result ($res,$j,sua_os);
			$os           = pg_result ($res,$j,os);
			$digitacao    = pg_result ($res,$j,'digitacao');
			$codigo_posto = pg_result ($res,$j,codigo_posto);
			$nome         = pg_result ($res,$j,nome);
			$referencia   = pg_result ($res,$j,referencia);
			$descricao    = pg_result ($res,$j,descricao);
			$fone    = pg_result ($res,$j,fone);
            $estado    = pg_result ($res,$j,estado);

		if (in_array($login_fabrica, array(3)))
		{
			$sql_qtde = "SELECT
							count(qtde) AS qtde_pecas
						FROM
							tbl_os_produto
						JOIN
							tbl_os_item
								ON tbl_os_item.os_produto = tbl_os_produto.os_produto and tbl_os_item.fabrica_i=3
						JOIN
							tbl_servico_realizado
								ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
						WHERE
							tbl_os_item.digitacao_item
								BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_servico_realizado.troca_de_peca
								IS TRUE
						AND os = $os";
			$res_qtde = pg_query($con,$sql_qtde);
			$qtde_pecas   = pg_result($res_qtde,0,qtde_pecas);

			$sql_data_diff = "SELECT
								count(*) AS data_diff
							  FROM tbl_os_item
							  JOIN tbl_os_produto
										ON tbl_os_item.os_produto = tbl_os_produto.os_produto
										AND tbl_os_item.fabrica_i = $login_fabrica
							  JOIN tbl_servico_realizado
										ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
							  WHERE tbl_os_item.digitacao_item
										BETWEEN '$data_inicial' AND '$data_final'
							  AND tbl_os_produto.os = $os
							  AND tbl_servico_realizado.troca_de_peca
										IS TRUE
							  GROUP BY tbl_os_item.digitacao_item::date
							  ORDER BY tbl_os_item.digitacao_item::date";
			$res_data_diff = pg_query($con,$sql_data_diff);
			$row_data_diff = pg_num_rows($res_data_diff);
		}


			$cor = $login_fabrica != 15 ? "#FFFFFF" : "#FCC";
			if ($j % 2 == 0) $cor = '#D2E6FF';

			fputs ($fp, "<tr>");
			fputs ($fp, "<td  bgcolor='$cor' align='center'>$sua_os</td>");
			if (in_array($login_fabrica, array(3))){
				fputs ($fp, "<td  bgcolor='$cor' align='center'>$digitacao</td>");
			}
			if(strlen($codigo_posto) == 14){
                $codigo_posto = mascara($codigo_posto,'##.###.###/####-##');
            }
			fputs ($fp, "<td bgcolor='$cor' align='center'>$codigo_posto</td>");
			fputs ($fp, "<td bgcolor='$cor' align='center' nowrap>$nome</td>");
			if(in_array($login_fabrica, array(40,101,106,108,111,3,115,116))){
				if (in_array($login_fabrica, array(3)))
				{
					fputs ($fp, "<td bgcolor='$cor' align='center'>$estado</td>");
				}
				fputs ($fp, "<td bgcolor='$cor' align='center'>$fone</td>");
            }
			fputs ($fp, "<td bgcolor='$cor' align='center' nowrap>$referencia - $descricao</td>");
			if (in_array($login_fabrica, array(3)))
			{
				fputs ($fp,"<td bgcolor='$cor' align='center'>$qtde_pecas</td>");
				$data_diff_total = 0;
				if ($row_data_diff > 1)
				{
					for ($z = 0; $z < $row_data_diff; $z++)
					{
						$data_diff = pg_result($res_data_diff,$z,data_diff);
						if ($z > 0)
						{
							$data_diff_total = $data_diff_total + $data_diff;
						}
					}
				}

				if ($data_diff_total > 0)
				{
					fputs ($fp,"<td bgcolor='$cor' align='center'>$data_diff_total</td>");
				}
				else
				{
					fputs ($fp,"<td bgcolor='$cor' align='center'>0</td>");
				}

				for ($z = 0; $z < 1; $z++)
				{
						$limit = pg_result($res_data_diff,$z,data_diff);
						$sql_pri_ped = "SELECT
											tbl_peca.referencia AS ref,
											tbl_peca.descricao  AS desc
										FROM tbl_os_item
										JOIN tbl_os_produto
												ON tbl_os_produto.os_produto = tbl_os_item.os_produto
										JOIN tbl_servico_realizado
												ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
										JOIN tbl_peca
												ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
										WHERE tbl_servico_realizado.troca_de_peca
												IS TRUE
										AND tbl_os_item.digitacao_item
												BETWEEN '$data_inicial' AND '$data_final'
										AND tbl_os_item.fabrica_i = $login_fabrica
										AND os = $os
										LIMIT $limit;";
						$res_pri_ped = pg_query($con,$sql_pri_ped);

						fputs ($fp,"<td bgcolor='$cor' align='center' nowrap>");
						for ($y = 0; $y < pg_num_rows($res_pri_ped); $y++)
						{
							fputs ($fp,"<font size='-3px'>".$pri_ped_ref = pg_result($res_pri_ped,$y,ref)." - ".$pri_ped_desc = pg_result($res_pri_ped,$y,desc)."</font><br>");
						}
						fputs ($fp,"</td>");
				}
				if(pg_num_rows($res_data_diff) > 1) {
					for ($z = 1; $z < 2; $z++)
					{
							$limit = @pg_result($res_data_diff,$z,data_diff);
							$x = $z - 1;
							$offset = @pg_result($res_data_diff,$x,data_diff);
							$sql_pri_ped = "SELECT
												tbl_peca.referencia AS ref,
												tbl_peca.descricao  AS desc
											FROM tbl_os_item
											JOIN tbl_os_produto
													ON tbl_os_produto.os_produto = tbl_os_item.os_produto
											JOIN tbl_servico_realizado
													ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
											JOIN tbl_peca
													ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
											WHERE tbl_servico_realizado.troca_de_peca
													IS TRUE
											AND tbl_os_item.digitacao_item
													BETWEEN '$data_inicial' AND '$data_final'
											AND tbl_os_item.fabrica_i = $login_fabrica
											AND os = $os
											OFFSET $offset
											LIMIT  $limit;";
							$res_pri_ped = @pg_query($con,$sql_pri_ped);

							fputs ($fp,"<td bgcolor='$cor' align='center' nowrap>");
							for ($y = 0; $y < @pg_num_rows($res_pri_ped); $y++)
							{
								fputs ($fp,"<font size='-3px'>".$pri_ped_ref = @pg_result($res_pri_ped,$y,ref)." - ".$pri_ped_desc = @pg_result($res_pri_ped,$y,desc)."</font><br>");
							}
							fputs ($fp,"</td>");
					}
				}
				if ($z == 2)
				{
					$off1 = @pg_result($res_data_diff,0,data_diff);
					$off2 = @pg_result($res_data_diff,1,data_diff);
					$offset = $off1 + $off2;
					$sql_ter_ped = "SELECT
										tbl_peca.referencia AS ref,
										tbl_peca.descricao  AS desc
									FROM tbl_os_item
									JOIN tbl_os_produto
											ON tbl_os_produto.os_produto = tbl_os_item.os_produto
									JOIN tbl_servico_realizado
											ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
									JOIN tbl_peca
											ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
									WHERE tbl_servico_realizado.troca_de_peca
											IS TRUE
									AND tbl_os_item.digitacao_item
											BETWEEN '$data_inicial' AND '$data_final'
									AND tbl_os_item.fabrica_i = $login_fabrica
									AND os = $os
									OFFSET $offset;";
					$res_ter_ped = @pg_query($con,$sql_ter_ped);

					fputs ($fp,"<td bgcolor='$cor' align='center' nowrap>");
					for ($y = 0; $y < @pg_num_rows($res_ter_ped); $y++)
					{
						fputs ($fp,"<font size='-3px'>".$ter_ped_ref = @pg_result($res_ter_ped,$y,ref)." - ".$ter_ped_desc = @pg_result($res_ter_ped,$y,desc)."</font><br>");
					}
					fputs ($fp,"</td>");
				}
			}
			//fputs ($fp, "</td>");
			fputs ($fp, "</tr>");
			if($login_fabrica == 15){
                $sqlOs = "SELECT  tbl_peca.referencia,
                                tbl_peca.descricao
                        FROM    tbl_peca
                        JOIN    tbl_os_item     ON  tbl_os_item.peca            = tbl_peca.peca
                        JOIN    tbl_os_produto  ON  tbl_os_produto.os_produto   = tbl_os_item.os_produto
                        JOIN    tbl_os          ON  tbl_os.os                   = tbl_os_produto.os
                                                AND tbl_os.fabrica              = $login_fabrica
                        WHERE   tbl_os.os = $os
                ";

                $resOs = pg_query($con,$sqlOs);
                $rows = pg_num_rows($resOs);
                $retorno = "
                        <tr>\n
                            <td rowspan='$rows' colspan='2'>Peças da OS $sua_os</td>\n
                ";
                for($o = 0; $o < $rows; $o++){
                    if($o > 0){
                        $retorno .= "<tr>\n";
                    }
                    $retorno .= "
                            <td colspan='2' align='left'>".pg_fetch_result($resOs,$o,referencia)." - ".pg_fetch_result($resOs,$o,descricao)."</td>\n
                        </tr>\n
                    ";
                }
                fputs($fp, $retorno);
			}
		}

		fputs ($fp, "</table>");
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

        if(file_exists($arquivo_completo_tmp)){
            echo `cp $arquivo_completo_tmp $arquivo_completo `;
            echo `rm $arquivo_completo_tmp `;

            echo"<br><br><table  border='0' cellspacing='2' cellpadding='2' align='center'>";
            echo"<tr>";
                echo "<td><img src='imagens/excell.gif'></td><td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='$arquivo_completo'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
            echo "</tr>";
            echo "</table>";
        }

	}else{
	echo "<center>Nenhum resultado encontrado</center>";
	}
}


include "rodape.php";


?>

