<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include "autentica_admin.php";

if($login_fabrica <> 20) {
    header("Location: menu_callcenter.php");
    exit;
}

$layout_menu = "cadastro";
$title = "Importação BOSCH";

include "cabecalho_new.php";

##### A T U A L I Z A R #####
if (strlen($_POST["btn_acao"]) > 0) {

    $tipo     	  = $_POST["tipo"];
    $caminho  	  = "/tmp/bosch/";
    $msg_erro 	  = array();
    $msg      	  = '';
    $nome_arquivo = $tipo;

    if (empty($tipo)) {
    	$msg_erro['msg'][] = "Selecione o tipo do arquivo";
    }

    $arquivo = isset($_FILES["arquivo_zip"]) ? $_FILES["arquivo_zip"] : FALSE;

    if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none" AND strpos($arquivo["type"], 'text') === false) {
    	$msg_erro['msg'][] = "Arquivo no formato incorreto";
    }

    if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none" AND count($msg_erro)==0){

        $config["tamanho"] = 2048000;

        if ($arquivo["size"] > $config["tamanho"]){
            $msg_erro['msg'][] = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";
        }

        if (count($msg_erro) == 0) {
            system ("rm -f {$caminho}{$nome_arquivo}");

            $dat = date("yyyy-mm-dd");
            $nome_arquivo_aux = $nome_arquivo;
            $nome_arquivo = $caminho.$nome_arquivo.".txt";

            if (!copy($arquivo["tmp_name"], $nome_arquivo)) {
                $msg_erro['msg'][] = "Arquivo '".$arquivo['name']."' não foi enviado!!!";
            }else{
            	if ($tipo == "produto-pais") {
            		$retorno = system("php ../rotinas/bosch/atribui-produto-pais.php $login_admin",$ret);
            	}else{
            		if ($tipo == "peca") {
            			$tipo .= '-br';
            		}elseif($tipo == "produto"){
            			$tipo .= '-novo';
            		}elseif($tipo == "categoria-mao-obra"){
            			$tipo = 'mao-obra';
            		}elseif($tipo == "peca-preco-al"){
            			$tipo = 'peca-preco-pais';
            		}
            		$retorno = system("php ../rotinas/bosch/importa-{$tipo}.php $login_admin", $ret);
            	}

            	if (!is_string($retorno) || empty($retorno)) {
            		$msg = 'Arquivo importado com sucesso';
            	}
            }
        }
    }else{
    	if (count($msg_erro)==0) {
    		$msg_erro['msg'][] = "Arquivo não selecionado";
    		$msg_erro['campo'] = "arquivo";
    	}
    }
}
?>
<script type="text/javascript">
	var msg_preco = '<br />Preço não pode ter separador de milhar!';
	var table = '<table class="table table-large" style="height: 152px;">';

	$(function() {
		$('.radio').on('change', function() {
			var nome_arquivo, layout, exemplo;
			switch($('input[name=tipo]:checked', '#form_upload').val()){
				case 'peca':
					nome_arquivo = 'peca.txt';
					layout       = 'Referencia, Descrição, Preço, Acessório e Descrição Espanhol';
					exemplo      = '2601115057   ETIQUETA    4   f   ETIQUETA'+msg_preco;
					break;
				case 'preco':
					nome_arquivo = 'peca.txt';
					layout       = 'Referencia, Preço, Sigla Tabela de Preço e IPI';
					exemplo      = '0601190025 6259.00 8'+msg_preco;
					break;
				case 'produto':
					nome_arquivo = 'produto.txt';
					layout       = 'Referencia, Descrição, Linha, Familia, Voltagem, Status, Nome comercial, Numero Serie Obrigatorio, Origem, Referencia Fábrica, País, Garantia, Descição Espanhol e Categoria';
					exemplo      = 'REF000145   TESTE DE PRODUTO    BO  PBL 127 V   t   GSR 6-25 TE t   Imp 3601D413D0  BR  12  PRUEBA DE PRODUCTO  CATEGORIA';
					break;
				case 'produto-pais':
					nome_arquivo = 'produto-pais.txt';
					layout       = 'Referencia, País e Garantia';
					exemplo      = '0601121103  AR  12';
					break;
				case 'peca-al':
					nome_arquivo = 'peca-al.txt';
					layout       = 'Referencia, Descrição e Acessório';
					exemplo      = 'F000600113 PORTA CARBONES  f';
					break;
				case 'peca-preco-al':
					nome_arquivo = 'peca-preco-al.txt';
					layout       = 'Referencia, Preço e País';
					exemplo      = 'F000600076    43.5    GT'+msg_preco;
					break;
				case 'lbm':
					nome_arquivo = 'lbm.txt';
					layout       = 'Referencia Produto, Referencia Peça, Posição e Quantidade';
					exemplo      = '0601247612 F000610026  56  1';
					break;
				case 'custo-tempo':
					nome_arquivo = 'custo-tempo.txt';
					layout       = 'Referencia Produto, Reparo e Tempo';
					exemplo      = '0601323061    0   14';
					break;
				case 'preco-produto':
					nome_arquivo = 'preco-produto.txt';
					layout       = 'Referencia, País, Garantia e Preço';
					exemplo      = '0601824290 BO  16  3,20';
					break;
				case 'categoria-mao-obra':
					nome_arquivo = 'categoria-mao-obra.txt';
					layout       = 'Categoria, País e Valor';
					exemplo      = 'NOME DA CATEGORIA BR  55.20<br> Separador de centavos DEVE ser ponto (.)';
					break;
			}
			$('#layout').html(
				table+' <tbody>\
							<tr>\
								<td width="20%" height="47px;" style="border-bottom: solid 1px black;"><strong>Nome do arquivo:</strong></td>\
								<td style="border-bottom: solid 1px black;">'+nome_arquivo+'</td>\
							</tr>\
							<tr>\
								<td height="47px;" style="border-bottom: solid 1px black;"><strong>Layout:</strong></td>\
								<td style="border-bottom: solid 1px black;">'+layout+'</td>\
							</tr>\
							<tr>\
								<td height="47px;" style="border-bottom: solid 1px black;"><strong>Exemplo:</strong></td>\
								<td style="border-bottom: solid 1px black;">'+exemplo+'</td>\
							</tr>\
						</tbody></table>');
		});
		$('.radio').trigger('change');
	});
</script>
<?php if (count($msg_erro) > 0) { ?>
<div class='alert alert-error'>
	<h4><?=implode('<br />', $msg_erro['msg']);?></h4>
</div>
<?php }elseif (strlen($msg) > 0) { ?>
<div class='alert alert-success'>
	<h4><?=$msg;?></h4>
</div>
<?php } ?>
<div class='alert alert-warning'>
	<h4 id='msg_layout'>O formato do arquivo deverá ser .txt e os dados separados por TAB</h4>
</div>
<div class="row"><b class="obrigatorio pull-right">  * Campos obrigatórios </b></div>
<form method='POST' action='<?=$PHP_SELF;?>' enctype='multipart/form-data' class='form-search form-inline tc_formulario' id='form_upload'>
	<input type='hidden' name='btn_acao' value=''>
	<div class="titulo_tabela">Envio de Arquivos para Atualização</div>
	<div class="row-fluid">
		<div class="span6" style="text-align: center;">
			<h6>Brasil</h6>
		</div>
		<div class="span6" style="text-align: center;">
			<h6>Países da América Latina</h6>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span5">
			<input type='radio' class='radio' name='tipo' value='peca' <?=($tipo=='peca'|| empty($tipo)) ? 'checked' : '';?>> Importar Peças
		</div>
		<div class="span6">
			<input type='radio' class='radio' name='tipo' value='peca-al'<?=($tipo=='peca-al') ? 'checked' : '';?>> Importar Peças dos países da América Latina
		</div>
	</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span5">
			<input type='radio' class='radio' name='tipo' value='preco' <?=($tipo=='preco') ? 'checked' : '';?>> Importar Lista de Preço de Peças para o Brasil
		</div>
		<div class="span6">
			<input type='radio' class='radio' name='tipo' value='peca-preco-al' <?=($tipo=='peca-preco-al') ? 'checked' : '';?>> Tabela de preço para países da América Latina
		</div>
	</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span5">
			<input type='radio' class='radio' name='tipo' value='produto' <?=($tipo=='produto') ? 'checked' : '';?>> Importar produtos novos
		</div>
		<div class="span6">
			<input type='radio' class='radio' name='tipo' value='produto-pais' <?=($tipo=='produto-pais') ? 'checked' : '';?>> Atribuir produto a um determinado País
		</div>
	</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span5">
			<input type='radio' class='radio' name='tipo' value='lbm' <?=($tipo=='lbm') ? 'checked' : '';?>> Importar Lista Básica de Materiais
		</div>
		<div class="span6">
			<input type='radio' class='radio' name='tipo' value='custo-tempo' <?=($tipo=='custo-tempo') ? 'checked' : '';?>> Importar Custo Tempo
		</div>
	</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span5">
			<input type='radio' class='radio' name='tipo' value='preco-produto' <?=($tipo=='preco-produto') ? 'checked' : '';?>> Importar Preço Produto
		</div>
		<div class="span6">
			<input type='radio' class='radio' name='tipo' value='categoria-mao-obra' <?=($tipo=='categoria-mao-obra') ? 'checked' : '';?>> Importar Mão-de-obra por categoria de produto
		</div>
	</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span10">
			<div id='layout'></div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span2" style="text-align: center;">
			<div class="control-group <?=(in_array($msg_erro['campo'], array('arquivo')))? 'error' : '';?>">
                <div class="controls controls-row">
                    <div class="inptc8">
                        <h5 class="asteristico">*</h5>
                        <input type='file' name='arquivo_zip' size='30'>
                    </div>
                </div>
            </div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span12" style="text-align: center;">
			<button class='btn' onclick="javascript: if (document.forms[0].btn_acao.value == '' ) { document.forms[0].btn_acao.value='gravar'; document.forms[0].submit(); } else { alert ('Aguarde submissão') }" ALT="Gravar Formulario" >Gravar</button>
		</div>
	</div>
</form>
<?php include "rodape.php"; ?>
