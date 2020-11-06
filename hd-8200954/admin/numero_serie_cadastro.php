<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';

$admin_privilegios="cadastro";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>3){
		$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";

		if ($busca == "codigo"){
			$sql .= " AND tbl_produto.referencia ilike '%$q%' ";
		}else{
			$sql .= " AND UPPER(tbl_produto.descricao) ilike UPPER('%$q%') ";
		}

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$produto            = trim(pg_fetch_result($res,$i,produto));
				$referencia         = trim(pg_fetch_result($res,$i,referencia));
				$descricao          = utf8_encode(trim(pg_fetch_result($res,$i,descricao)));
				echo "$produto|$descricao|$referencia";
				echo "\n";
			}
		}
	}
	exit;
}

if(isset($_POST['btn_acao']) || isset($_POST['pesquisar'])) {
    $referencia = $_POST['produto_referencia'];
    $serie      = trim($_POST['serie']);

    if(strlen($serie) == 0 && !isset($_POST['pesquisar'])) {
        $msg_erro = "Por favor, informe a regra de número de série";
    }else if(strlen($serie) > 0 && strlen($serie) < 14){
        $msg_erro = "Por favor, o nº de série deve ter exatamente 14 dígitos.";
    }
    if(strlen($referencia) == 0 && !isset($_POST['pesquisar'])) {
        $msg_erro = "Por favor, informe o produto para cadastrar";
    }
    if($login_fabrica == 24){
        $data_fabricacao    = $_POST['data_fabricacao'];
        $data_inicial       = $_POST['data_inicial'];
        $data_final         = $_POST['data_final'];
        $admin_consulta     = $_POST['admin_consulta'];
        if(!empty($data_inicial) && !empty($data_final)){
            list($di, $mi, $yi) = explode("/", $data_inicial);
            if(!checkdate($mi,$di,$yi)){
                $msg_erro = "Data inicial inválida";
            }
            if(strlen($msg_erro)==0){
                $aux_data_inicial = "$yi-$mi-$di";
                if(strtotime($aux_data_inicial) > strtotime(date('Y-m-d'))){
                    $msg_erro = "Data inicial maior que data de hoje.";
                }
            }

            list($di, $mi, $yi) = explode("/", $data_final);
            if(!checkdate($mi,$di,$yi)){
                $msg_erro = "Data final inválida";
            }
            if(strlen($msg_erro)==0){
                $aux_data_final = "$yi-$mi-$di";
                if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
                    $msg_erro = "Data final menor que data inicial.";
                }
            }
            $sqlX   = "SELECT '$aux_data_inicial'::date + interval '1 month' > '$aux_data_final'";
            $resX   = pg_query($con,$sqlX);
            $trinta = pg_fetch_result($resX,0,0);
            if($trinta == 'f'){
                $msg_erro = "AS DATAS DEVEM SER NO MÁXIMO 30 DIAS";
            }
        }
        if(!empty($data_fabricacao)){
            list($di, $mi, $yi) = explode("/", $data_fabricacao);
            if(!checkdate($mi,$di,$yi)){
                $msg_erro = "Data de fabricação inválida";
            }
            if(strlen($msg_erro)==0){
                $aux_data_fabricacao = "$yi-$mi-$di";
                if(strtotime($aux_data_fabricacao) > strtotime(date('Y-m-d'))){
                    $msg_erro = "Data de fabricação maior que data de hoje.";
                }
            }
        }else{
            if(!isset($_POST['pesquisar'])){
                $msg_erro = "É necessária o cadastro da data de fabricação.";
            }
        }
        if(empty($admin_consulta)){
            $admin_consulta = null;
        }else{
            $aux_admin = $admin_consulta;
        }

        $numero_admin = $login_admin;
    }else{
        $aux_data_fabricacao = "null";
        $numero_admin        = "null";
    }


	if(strlen($referencia) > 0){
		$sql = "SELECT produto
  				FROM tbl_produto
  				JOIN tbl_linha USING(linha)
  				WHERE fabrica = $login_fabrica
  				AND   referencia = '$referencia'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0) {
			$produto = pg_fetch_result($res,0,produto);
		}else{
			$msg_erro = "Produto não encontrado";
		}
	}else{
		$produto = 'null';
	}

    if(isset($_POST['btn_acao'])){
        if(strlen($msg_erro) == 0) {

            $sqlVal = " SELECT  tbl_numero_serie.serie,
                                tbl_numero_serie.produto
                        FROM    tbl_numero_serie
                        WHERE   tbl_numero_serie.serie = '$serie'
                        AND     tbl_numero_serie.produto = $produto
            ";
            $resVal = pg_query($con,$sqlVal);
            $duplicate = pg_num_rows($resVal);
            if($duplicate > 0){
                $msg_erro = "Já foi cadastro esse nº de série para esse produto";
            }

            if(strlen($msg_erro) == 0) {
                $res = pg_query($con,"BEGIN TRANSACTION");

                $sql = "INSERT INTO tbl_numero_serie (
                            fabrica             ,
                            serie               ,
                            produto             ,
                            referencia_produto  ,
                            data_fabricacao     ,
                            admin
                        ) values (
                            $login_fabrica          ,
                            '$serie'                ,
                            $produto                ,
                            '$referencia'           ,
                            '$aux_data_fabricacao'  ,
                            $numero_admin
                        )";

                $res = pg_query($con,$sql);
                $msg_erro =pg_last_error($con);

                if(strlen($msg_erro)==0){
                    $res = pg_query($con,"COMMIT TRANSACTION");
                    header("Location: $PHP_SELF?suc=1");
                }else{
                    $res = pg_query($con,"ROLLBACK TRANSACTION");
                }
            }
        }
    }else if(isset($_POST['pesquisar'])){
        $sqlLista = "SELECT tbl_numero_serie.referencia_produto                                         ,
                            tbl_produto.descricao                                                       ,
                            tbl_numero_serie.serie                                                      ,
                            CASE WHEN tbl_numero_serie.admin IS NOT NULL
                                 THEN tbl_admin.nome_completo
                                 ELSE 'Importado via FTP'
                            END                                                         AS nome_admin   ,
                            to_char(tbl_numero_serie.data_fabricacao,'DD/MM/YYYY')      AS data_fab     ,
                            to_char(tbl_numero_serie.data_carga,'DD/MM/YYYY HH24:MI')   AS data_criacao
                    FROM    tbl_numero_serie
                    JOIN    tbl_produto USING (produto)
               LEFT JOIN    tbl_admin   ON tbl_admin.admin = tbl_numero_serie.admin
                    WHERE   tbl_numero_serie.fabrica = $login_fabrica
        ";
        if(strlen($referencia) > 0){
            $sqlLista .= "
                    AND     tbl_numero_serie.referencia_produto = '$referencia'
            ";
        }
        if(strlen($serie) > 0){
            $sqlLista .= "
                    AND     tbl_numero_serie.serie              = '$serie'
            ";
        }
        if(strlen($aux_data_inicial) > 0 && strlen($aux_data_inicial) > 0){
            $sqlLista .= "
                    AND     tbl_numero_serie.data_carga BETWEEN '$aux_data_inicial' AND '$aux_data_final'
                    AND     tbl_numero_serie.admin IS NOT NULL
            ";
        }
        if(isset($aux_data_fabricacao)){
            $sqlLista .= "
                    AND     tbl_numero_serie.data_fabricacao = '$aux_data_fabricacao'
            ";
        }
        if(isset($admin_consulta)){
            $sqlLista .= "
                    AND     tbl_numero_serie.admin = $admin_consulta
            ";
        }
        #echo nl2br($sqlLista);
        $resLista = pg_query($con,$sqlLista);
    }
}


$layout_menu = "cadastro";
$title = "Cadastro de Número de Série";

include "cabecalho.php";

?>

<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
	background-image: url(imagens_admin/azul.gif);
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.Conteudo {
	font-family: Arial;
	font-size: 12px;
	font-weight: normal;
}

.tabela td{
    font-size:11px;
}

.botao {
	background: #FFFFFF ;
	display: inline-block;
	padding: 4px 9px 6px;
	color: #000000 important;
	-moz-border-radius: 2px 8px / 2px 10px;
	-webkit-border-top-right-radius: 8px;
	border-bottom-left-radius: 8px / 10px;
	-opera-border-bottom-left-radius: 10px;
	-webkit-border-bottom-left-radius: 8px;
	-moz-box-shadow: 0 1px 3px rgba(0,0,0,0.5);
	-webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.5);
	text-shadow: 0 -1px 1px rgba(0,0,0,0.25);
	-moz-text-shadow: 0 -1px 1px rgba(0,0,0,0.25);
	border-bottom: 1px solid rgba(0,0,0,0.25);
	cursor: pointer;
	border: 0;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_sucesso{
    background-color: green;;
    font: bold 16px "Arial";
    color: #FFFFFF;
    text-align:center;
}
.legenda{
	text-align: left;
}

</style>


<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<?
    include "javascript_pesquisas.php";
    include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007
    include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>

<script type='text/javascript'>
<?
if($login_fabrica == 24){
?>
$(document).ready(function(){
    Shadowbox.init();
    $('#data_fabricacao').datepick({startDate:'01/01/2000'});
    $('#data_inicial').datepick({startDate:'01/01/2000'});
    $('#data_final').datepick({startDate:'01/01/2000'});
    $("#data_fabricacao").mask("99/99/9999");
    $("#data_inicial").mask("99/99/9999");
    $("#data_final").mask("99/99/9999");
});
<?
}
?>
$().ready(function() {
	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	$("#produto_descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#produto_descricao").result(function(event, data, formatted) {
		$("#produto_referencia").val(data[2]) ;
		$("#produto").val(data[0]) ;
	});

	/* Busca pelo Nome */
	$("#produto_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#produto_referencia").result(function(event, data, formatted) {
		$("#produto_descricao").val(data[1]) ;
		$("#produto").val(data[0]) ;
		//alert(data[2]);
	});


});
</script>

<br />
<?
if(strlen($msg_erro) > 0) {
?>
<table width='700px' align='center' cellpadding='0' cellspacing='0' class='msg_erro'>
    <tr >
        <td align='center'><?=$msg_erro?></td>
    </tr >
</table>
<?
}
if($_GET['suc'] == 1){
?>
<table width='700px' align='center' >
    <tr>
        <td class='msg_sucesso'>Cadastro efetuado com Sucesso</td>
    </tr>
</table>
<?
}
?>
<center>
<div style="text-align:center">
<?
if ($login_fabrica != 24){
?>
Regra para cadastrar Número de Série
<ol type="1" start="1" style="text-align:center">
	<li>Letras Maiusculas - Aceita apenas letras cadastradas</li>
	<li>Números Maiusculos - Aceita apenas números cadastrados</li>
	<li>Letra l(Minúscula) - Aceita qualquer letra</li>
	<li>Letra n(Minúscula) - Aceita qualquer número</li>
</ol>
<?
}
?>
</div>
</center>
<br />
<form name="frm_cadastro" method="POST" action="<? echo $PHP_SELF ?>">
<table width='600' class='formulario' border='0' cellpadding='5' cellspacing='1' align='center' >
	<tr>
		<td class='titulo_tabela' ><?=$title?></td>
	</tr>
	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>
			<table width='100%' border='0' cellspacing='1' cellpadding='2' >
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td style="text-align:left;">
                        <label for='produto_referencia'>Referência</label>
                        <br/>
                        <input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
                        <img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_cadastro.produto_referencia, document.frm_cadastro.produto_descricao,'referencia')" />
					</td>
					<td style="text-align:left;">
                        <label for='produto_descricao'>Descrição do Produto</label>
                        <br />
                        <input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" />
                        <img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_cadastro.produto_referencia, document.frm_cadastro.produto_descricao,'descricao')" />
					</td>
				</tr>
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td colspan="100%"></td>
				<tr>
<?
if($login_fabrica == 24){
    $max = "maxlength='14'";
    $colspan = "";
}else{
    $max = "";
    $colspan = "colspan='2'";
}
?>
				<tr class="Conteudo" bgcolor="#D9E2EF">
<?
if($login_fabrica == 24){
?>
                    <td style="text-align:left;">
                        <label for="data_fabricacao">Data Fabricação</label><br/>
                        <input type="text" name="data_fabricacao" id="data_fabricacao" size="13" maxlength="10" value="<? echo (strlen($data_fabricacao) > 0) ? substr($data_fabricacao,0,10) : ""; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm" />
                    </td>
<?
}
?>
					<td <?=$colspan?> style="text-align:left;"><label for="serie">Número de Série</label><br/>
                        <input id="serie" class="frm" type="text" name="serie" value="<?=$serie?>" size="20" <?=$max?> /> <? if ($login_fabrica != 24){ ?><br/>Ex:OU9nnnnnnnll <? } ?>
                    </td>
                </tr>
<?
if($login_fabrica == 24){
    /*
    * - BUSCA DE TODOS OS ADMIN's QUE TEM ACESSO A ESSA ÁREA
    */
    $sqlAd = "  SELECT  tbl_admin.admin,
                        tbl_admin.nome_completo
                FROM    tbl_admin
                WHERE   tbl_admin.fabrica = $login_fabrica
                AND     tbl_admin.ativo IS TRUE
                AND     (
                            tbl_admin.privilegios = '*'
                        OR  tbl_admin.privilegios LIKE '%cadastros%'
                        )
    ";
    $resAd = pg_query($con,$sqlAd);
?>
                <tr>
                    <td <?=$colspan?> style="text-align:left;"><label for="admin">Admin</label><br/>
                        <select name="admin_consulta" class="frm">
                            <option value="">&nbsp;</option>
<?
    for($c=0;$c<pg_num_rows($resAd);$c++){
        $admin_consulta = pg_fetch_result($resAd,$c,admin);
        $nome_consulta  = pg_fetch_result($resAd,$c,nome_completo);
?>
                            <option value="<?=$admin_consulta?>" <? if($admin_consulta == $aux_admin){ echo "selected"; } ?>><?=$nome_consulta?></option>
<?
    }
?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="text-align:left;">
                        <label for="data_fabricacao">Data Inicial</label><br/>
                        <input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? echo (strlen($data_inicial) > 0) ? substr($data_inicial,0,10) : ""; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm" />
                    </td>
                    <td style="text-align:left;">
                        <label for="data_final">Data final</label><br/>
                        <input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? echo (strlen($data_final) > 0) ? substr($data_final,0,10) : ""; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm" />
                    </td>
                </tr>
<?
}
?>
            </table>
            <br />
            <input type='submit' name='btn_acao' value='Gravar' />
            <input type="submit" value="Pesquisar" name="pesquisar" />
		</td>
	</tr>
</table>
</form>
<?
if($login_fabrica == 24){
    if(isset($_POST['pesquisar']) && $msg_erro == ""){
        if(pg_numrows ($resLista) > 0){
?>
<br />
<input type="button" id="listar" value="Limpar" onClick="location.href='<?echo $PHP_SELF;?>'">
<br />

<table width='700px' align='center' border='0' cellpadding='2' cellspacing='1' class='tabela'>
    <thead>
        <tr class='titulo_coluna' >
            <th>Referência</th>
            <th>Produto</th>
            <th>Nº Série</th>
            <th>Data Fabricação</th>
            <th>Admin</th>
            <th>Data Cadastro</th>
        </tr>
    </thead>
    <tbody>
<?
        for ($i = 0 ; $i < pg_numrows ($resLista) ; $i++) {
            $cor = "#F1F4FA";
            if($i % 2 == 0){
                $cor = "#F7F5F0";
            }
?>
        <tr style='background-color:<?=$cor?>;'>
            <td><?=pg_fetch_result($resLista,$i,referencia_produto)?></td>
            <td nowrap><?=pg_fetch_result($resLista,$i,descricao)?></td>
            <td><?=pg_fetch_result($resLista,$i,serie)?></td>
            <td><?=pg_fetch_result($resLista,$i,data_fab)?></td>
            <td nowrap><?=pg_fetch_result($resLista,$i,nome_admin)?></td>
            <td nowrap><?=pg_fetch_result($resLista,$i,data_criacao)?></td>
        </tr>
<?
        }
?>
    </tbody>
</table>
<?
        }else{
?>
<table width='700px' align='center' >
    <tr>
        <td style="font-weight:bold;">Nenhum resultado encontrado</td>
    </tr>
</table>
<?
        }
    }

}
?>

<? include "rodape.php" ?>
