<div class="container-fluid">
	<div class="row-fluid">
	    <div class="span10">
	        <h3>Lupa</h3>

	        <div class='row-fluid'>
	            <div class='span10'>
	                <p>A Lupa é um componente utilizado na maioria das telas do sistema, consiste em um botão que abre outra tela para o usuário selecionar um item, e as informações selecionadas retornam para uso em alguma ação.</p>
	                <p>Para que a lupa funcione corretamente sua configuração deve ser feita da seguinte forma:</p>
	            </div>            
	        </div>
	    </div>
	</div>

	<div class='row-fluid'>
	    <div class='span10'>
	        <h4>Estrutura Html</h4>
	        <ul>
	                    <li><p>A estrutura da lupa deve conter um <code>span</code> com o atributo <code>rel="lupa"</code></p></li>
	                    <li><p>Deve existir um <code>&lt;input type="hidden" /&gt;</code> abaixo do span da definição anterior, nele deve conter os seguintes atributos:</p></li>
	                    <ul>
	                        <li><p>name: lupa_config</p></li>
	                        <li><p>tipo: Nome da Lupa a ser utilizada</p></li>
	                        <li><p>parametro: Campo a ser pesquisado na lupa (referência, descrição, etc)</p></li>
	                    </ul>
	                    <li><p>Caso exista algum parâmetro adicional, deve ser criado um atributo com seu respectivo valor, e passar esses atributos quando fazer o script Js: </p></li>
	        </ul>
	    </div>
	</div>
    
    <div class="row-fluid">
    	<div class='span12'>
            <div class="well well-small ">
                <div class='span5'>
    	            <small>Exemplo:</small>
                    <div class='control-group'>
                        <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                        <div class='controls controls-row'>
                            <div class='span7 input-append'>
                                <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                                <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                            </div>
                        </div>
                    </div>
                </div>
				<div class="control-group">    

                    <textarea class="span10" rows="6" cols="12" disabled="disable">
    &lt;div class='control-group'&gt;
        &lt;label class='control-label' for='produto_referencia'&gt;Ref. Produto&lt;/label&gt;
        &lt;div class='controls controls-row'&gt;
            &lt;div class='span7 input-append'&gt;
                &lt;input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="" &gt;
                &lt;span class='add-on' rel="nome da lupa" &gt;&lt;i class='icon-search'&gt;&lt;/i&gt;&lt;/span&gt;
                &lt;input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" /&gt;
            &lt;/div&gt;
        &lt;/div&gt;
    &lt;/div&gt;
                    </textarea>
                </div><!-- control-group -->
            </div> <!-- well -->
        </div> <!-- well -->
    </div> <!-- span12 -->       
    

    <div class="row-fluid">
    	<div class="span10">
        	<h4>Javascript</h4>

        	<div class="control-group">    

                <textarea class="span10" rows="6" cols="12" disabled="disable">
              
//Configuração de lupa padrão
&lt;script&gt;
	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});
&lt;/script&gt;

//Configuração de lupa com parametros extras
&lt;script&gt;
	$("span[rel=lupa]").click(function () {
		$.lupa($(this),array("extra1","extra2","extra3"));
	});
&lt;/script&gt;
                </textarea>  
            </div> <!-- control-group -->
        </div> <!-- span12 -->
    </div> <!-- row-fluid -->

    <hr/>

    <div class="row-fluid">
    	<div class="span10">
            <h4>Exemplo de funcionamento de uma lupa</h4>
            <p>Na ocasião em que o desenvolvedor tiver que criar uma lupa, deverá seguir esse arquivo padrão.</p>
            <div class="control-group">    
                <textarea class="span10" rows="20" cols="20" disabled="disable">
&lt;?php
/* Includes necessarios para funcionamento da lupa */
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$pecas = array(
	"pcx1" =&gt; "Peça x 1",
	"pcx2" =&gt; "Peça x 2",
	"pcx3" =&gt; "Peça x 3",
	"pcx4" =&gt; "Peça x 4",
	"pcx5" =&gt; "Peça x 5",
	"pcx6" =&gt; "Peça x 6",
	"pcx7" =&gt; "Peça x 7",
	"pcx8" =&gt; "Peça x 8",
	"pcx9" =&gt; "Peça x 9"
);

/* Padrão para pegar os valores passados, parametro, valor e extras */
if ($_REQUEST["posicao"]) {
	$posicao   = $_REQUEST["posicao"];
}

$parametro = $_REQUEST["parametro"];
$valor     = trim($_REQUEST["valor"]);

if ($_GET["valor"]) {
	/* Obrigatorio das o utf8_decode pois o js da um uft8_encode */
	$valor = utf8_decode($valor);
}
?&gt;
&lt;!DOCTYPE html /&gt;
&lt;html&gt;
	&lt;head&gt;
		&lt;meta http-equiv=pragma content=no-cache&gt;

		&lt;!-- CSS necessario para funcionamento da lupa --&gt;
		&lt;link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" /&gt;
                &lt;link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" /&gt;
                &lt;link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" /&gt;
                &lt;link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" /&gt;
		&lt;link href="plugins/dataTable.css" type="text/css" rel="stylesheet" /&gt;

		&lt;!-- Scripts necessarios para funcionamento da lupa --&gt;
		&lt;script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"&gt;&lt;/script&gt;
		&lt;script src="bootstrap/js/bootstrap.js"&gt;&lt;/script&gt;
		&lt;script src="plugins/dataTable.js"&gt;&lt;/script&gt;
		&lt;script src="plugins/resize.js"&gt;&lt;/script&gt;
		&lt;script src="plugins/shadowbox_lupa/lupa.js"&gt;&lt;/script&gt;

		&lt;!-- O resultado da lupa deve usar o dataTable porém um dataTable modificado para a lupa --&gt;
		&lt;script&gt;
			$(function () {
				$.dataTableLupa();
			});
		&lt;/script&gt;

	&lt;/head&gt;

	&lt;body&gt;

		&lt;!-- Div container da lupa --&gt;
		&lt;div id="container_lupa" style="overflow-y:auto;"&gt;

			&lt;!-- Topo --&gt;
			&lt;div id="topo"&gt;
				&lt;img class="espaco" src="../imagens/logo_new_telecontrol.png"&gt;
				&lt;img class="lupa_img pull-right" src="../imagens/lupa_new.png"&gt;
			&lt;/div&gt;

			&lt;!-- Separação do topo com o corpo --&gt;
			&lt;br /&gt;&lt;hr /&gt;

			&lt;!-- Corpo --&gt;
			&lt;div class="row-fluid"&gt;

				&lt;!-- Form da re-pesquisa --&gt;
				&lt;form action="&lt;?=$_SERVER['PHP_SELF']?&gt;" method='POST' &gt;

					&lt;!-- Margem --&gt;
					&lt;div class="span1"&gt;&lt;/div&gt;

					&lt;!-- Campo de parametros e campos de valores extras --&gt;
					&lt;div class="span4"&gt;

						&lt;!-- Valores extras deve ficar em um input hidden --&gt;
						&lt;input type="hidden" name="posicao" class="span12" value='&lt;?=$posicao?&gt;' /&gt;

						&lt;!-- o parametro pesquisado deve ser um select dentro da lupa --&gt;
						&lt;select name="parametro" &gt;
							&lt;option value="referencia" &lt;?=($parametro == "referencia") ? "SELECTED" : ""?&gt; &gt;Referência&lt;/option&gt;
							&lt;option value="descricao" &lt;?=($parametro == "descricao") ? "SELECTED" : ""?&gt; &gt;Descrição&lt;/option&gt;
						&lt;/select&gt;

					&lt;/div&gt;

					&lt;!-- Campo do valor digitado para pesquisar --&gt;
					&lt;div class="span4"&gt;
						&lt;input type="text" name="valor" class="span12" value="&lt;?=$valor?&gt;" /&gt;
					&lt;/div&gt;

					&lt;!-- Botão para re-submitar --&gt;
					&lt;div class="span2"&gt;
						&lt;button type="button" class="btn pull-right" onclick="$(this).parents('form').submit();"&gt;Pesquisar&lt;/button&gt;
					&lt;/div&gt;

					&lt;!-- Margem --&gt;
					&lt;div class="span1"&gt;&lt;/div&gt;

				&lt;/form&gt;
			&lt;/div&gt;

			&lt;?
			/* Verificação da quantidade minima de caracteres digitados para realozizar a pesquisa */
			if (strlen($valor) &gt;= 3) {

				/* Verificação do parametro usado para pesquisar */
				switch ($parametro) {
					case 'referencia':
						foreach ($pecas as $referencia =&gt; $descricao) {
							if (strpos("/{$valor}/", $referencia)) {
								$achadas[$referencia] = $descricao;
							}
						}
						break;
					
					case 'descricao':
						foreach ($pecas as $referencia =&gt; $descricao) {
							if (strpos("/{$valor}/", $descricao)) {
								$achadas[$referencia] = $descricao;
							}
						}
						break;
				}

				/* Verificação se achou resultado */
				if (count($achadas) &gt; 0) {
				
				?&gt;

				&lt;!-- Div da tabela de resultado --&gt;
				&lt;div id="border_table"&gt;

					&lt;!-- Tabela da lupa com a class tabela-lupa feita especialmente para esta tabela --&gt;
					&lt;table class="table table-striped table-bordered table-hover table-lupa" &gt;

						&lt;!-- Titulos da tabela  --&gt;
						&lt;thead&gt;
							&lt;tr class='titulo_coluna'&gt;
								&lt;th&gt;Referência&lt;/th&gt;
								&lt;th&gt;Descrição&lt;/th&gt;
							&lt;/tr&gt;
						&lt;/thead&gt;

						&lt;!-- Corpo da tabela --&gt;
						&lt;tbody&gt;
							&lt;?php
							foreach ($achadas as $referencia =&gt; $descricao) {
								$r = array(
									"descricao" =&gt; utf8_encode($descricao),
									"referencia" =&gt; $referencia
								);

								echo "&lt;tr onclick='window.parent.retorna_peca(".json_encode($r)."); window.parent.Shadowbox.close();' &gt;";
									echo "&lt;td class='cursor_lupa'&gt;{$referencia}&lt;/td&gt;";
									echo "&lt;td class='cursor_lupa'&gt;{$descricao}&lt;/td&gt;";
								echo "&lt;/tr&gt;";
							}
							?&gt;
						&lt;/tbody&gt;

					&lt;/table&gt;

					&lt;/div&gt;
				&lt;?php
				} else {
					/* Mensagem de erro para nenhum resultado encontrado */
					echo '
					&lt;div class="alert alert_shadobox"&gt;
					    &lt;h4&gt;Nenhum resultado encontrado&lt;/h4&gt;
					&lt;/div&gt;';
				}
			} else {
				/* Mensagem de erro para digitar mais caracteres */		
				echo '
				&lt;div class="alert alert_shadobox"&gt;
				    &lt;h4&gt;Informe toda ou parte da informação para pesquisar!&lt;/h4&gt;
				&lt;/div&gt;';
			}
			?&gt;
	&lt;/div&gt;

	&lt;/body&gt;
&lt;/html&gt;

                </textarea>
            </div>
        </div> <!-- span12 -->
	</div> <!-- row-fluid -->
</div> <!-- container-fluid -->


