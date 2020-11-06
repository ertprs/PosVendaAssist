<div class="container-fluid">
<div class="row-fluid">    
    <div class="span10">
        <h3>Primeiros Passos</h3>

        <h4>Importante</h4>
        <p>Se voce usa o editor Sublime Text é preciso alterar uma configuração para evitar alguns erros relacionados a codicação.</p>
        <p>Vá em <b>preferences/Settings-User</b> e adicione ao Json as seguintes linhas:</p>
        <p>
            <code>"default_encoding": "Western (ISO 8859-15)",</code>
            <code>"fallback_encoding": "Western (ISO 8859-15)",</code>
        </p>
        <hr/>
        <h4>Fazer o Include do cabeçalho</h4>
        <p>Para iniciar os trabalhos no novo layout faça o include do <code>"cabecalho_new.php"</code> todo css e js do novo layout está no arquivo citado</p>
        <div class="well well-small">
            &lt;?php<br /><br />
            include "cabecalho_new.php";<br /><br />
            [...]
        </div>
        <hr/>
        <h4>Plugins</h4>
        <p>Se for utilizar plugins é necessário incluir o código abaixo logo após o include do <code>"cabecalho_new.php"</code></p>
        <div class="well well-small">
            &lt;?php <br /><br />
            $plugins = array(
            "nome_plugin"
            );<br /><br />
            include "plugin_loader.php";<br /><br />
            [...]
        </div>
        <p>Acesse <a href="?doc=inputs#loader">Plugin Loader</a> para ver os plugins existentes e as suas configurações</p>
        <hr/>
        <h4>Javascript/Jquery</h4>
        <p>Nesse tópico será demonstrados algumas práticas para organizar o código Javascript na página.
        </p>

        <ul>
            <li>Se possível organize seu código js somente em uma parte da página, no começo ou no final de preferência, organizando o código em camadas simplifica o processo de manutenção.</li>
            <ul>
                <div id='exemplo-pagina'>
                    <div class='row-fluid'>
                        <div class='span2'><p><code>Página</code></p></div>
                        <div class='span10' id='cam1'><p><code>PHP</code></p><p>&lt;?php...?&gt;</p></div>                        
                    </div>
                    <div class='row-fluid'>
                        <div class='span2'></div>
                        <div class='span10'id='cam2'><p><code>Javascript</code></p><p>$(document).ready()...</p></div>
                    </div>
                    <div class='row-fluid'>
                        <div class='span2'></div>
                        <div class='span10' id='cam3'><p><code>HTML</code></p><p>&lt;html&gt...&lt;/html&gt</p></div>
                    </div>
                </div>
            </ul>
            <br />
            <li>Evite usar javascript embutido no HTML, o mesmo evento pode ser programado dentro de uma tag <code>&lt;script&gt;</code> </li>
            <ul>
                <div class="control-group">
                    <textarea class="span10" rows="6" cols="12" disabled="disable">
// JQuery
$("#idElemento").click(function(){
    // A??o
});

// Javascript
document.getElementById("idElemento).addEventListener('click',function(){
    // A??o
});
                    </textarea>

                </div>
            </ul>
            <li>Programe seus eventos de preferência dentro de eventos de inicialização por exemplo:</li>
            <ul>
                <div class="control-group">
                    <textarea class="span10" rows="6" cols="12" disabled="disable">
// JQuery
$(function(){
    // Eventos
});

// Javascript
window.onload = function(){
    // Eventos
}

document.onload = function(){
    // Eventos
}
                    </textarea>
                </div>
            </ul>            
        </ul>
        <hr />
        <h4>Implementação do código</h4>
        <p>Após essas configurações deve-se implementar buscando sempre a <a href="?doc=javascript">organização do código.</a></p>
        
        <p>Abaixo um exemplo de estrutura básica:</p>
        <div class="control-group">
            <textarea class="span10" rows="20" cols="12" disabled="disable">
&lt;?
/**
* INCLUDES NECESSÁRIOS
*/
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

/*
* Identifica qual usuário é permitido acessar esta tela de acordo com os grupos:
* gerencia, cadastros, call_center, supervisor_call_center, info_tecnica, financeiro, auditoria
*/
$admin_privilegios="financeiro,gerencia,call_center";

include 'autentica_admin.php';
include 'funcoes.php';

/**
* Tratar as ações do btn_acao.
* O btn_acao é responsável pelas ações dos botões da página (submit, gravar, apagar e etc).
*/
if ($_POST["btn_acao"] == "submit") {
	/**
	* Validação dos campos
	* A variável $msg_erro é responsável pelas mensagens dos erros que podem ocorrer durante a validação.
	*
	* $msg_erro["msg"][] = "Mensagem de Erro";
	* $msg_erro["campos"][] = "Campo do formul?rio que Ocorreu o Erro";
	*/

	/**
	* Caso haja ação de gerar excel, deve ser colocado após a execução da query. do mesmo $_POST que gera o resultado em tela sem o limit 500
	* Deverá ser utilizado a mesma variável utilizada para armazenar o retorno da consulta. Por exemplo $resSubmit
	*/
	if ($_POST["gerar_excel"]) {}
}

/**
* variáveis obrigatorias $title é o titulo da tela
* $layout_menu indica a qual menu a tela pertence
*/
$layout_menu = "gerencia";
$title = "RELATÁRIO DE OS x Atendimentos";

/* Include do cabeçalho */
include 'cabecalho_new.php';

/* Colocar na variável $plugins os Plugins Utilizados na tela */

/* A variável $plugins deve estar depois do cabecalho_new.php e antes do plugin_loader.php */
$plugins = array(
"datepicker",
"tooltip"
);

/* Include do plugin_loader */
include("plugin_loader.php");

?&gt;

&lt;!-- Aqui fica os scrips e styles --&gt;
&lt;script&gt;
&lt;/script&gt;

&lt;style&gt;
&lt;/style&gt;

&lt;!-- class [class='form-search form-inline tc_formulario'] obrigatoria para form --&gt;
&lt;form name='' METHOD='' ACTION='' align='center' class='form-search form-inline tc_formulario'&gt;

	&lt;!-- &lt;div class='titulo_tabela'&gt;  para fundo no titulo da pagina --&gt;	
	&lt;div class='titulo_tabela '&gt;Titulo&lt;/div&gt; 

	&lt;!-- CAMPO DATA --&gt;

	&lt;!-- Todo conteudo do form sempre deve estar dentro de [&lt;div class='row-fluid&gt; conteudo &lt;/div&gt;]
	para que os campos se ajustem a pagina  --&gt;
	&lt;div class='row-fluid'&gt; &lt;!-- Inicio class="row-fluid"&gt; --&gt;

		&lt;div class='span2'&gt;&lt;/div&gt; &lt;!-- espaçamento lateral esquerda da pagina --&gt; 

		&lt;div class='span4'&gt; &lt;!-- Div class="span4" define o tamanho que vai ocupar todo conteudo da primeira label e primeiro input --&gt;
			&lt;div class='control-group'&gt; &lt;!-- class control-grupo para controle dos conteudos --&gt;
				&lt;label class='control-label' for=''&gt;texto&lt;/label&gt; &lt;!-- Titulo do Input --&gt;
				&lt;div class='controls controls-row'&gt; &lt;!-- div controls controls-row para controle do input  --&gt;
					&lt;div class='span4'&gt; &lt;!-- class='span4' define o tamanho maximo que o input pode ocupar na tela --&gt; 
						&lt;!-- dentro do input é utilizada a class='span12'  para que o input ocupe 100% do espaço definido na class='span4' acima --&gt; 
						&lt;input type="" name="" id="" size="" maxlength="" class='span12' value= ""&gt; 
					&lt;/div&gt; &lt;!-- fecha span4 --&gt;
				&lt;/div&gt;&lt;!-- fecha control controls-row --&gt;
			&lt;/div&gt; &lt;!-- fecha control-group --&gt;
		&lt;/div&gt;&lt;!-- fecha span4 --&gt;

		&lt;div class='span4'&gt; &lt;!-- Div class="span4" define o tamanho que vai ocupar todo conteudo da primeira label e primeiro input --&gt;
		&lt;div class='control-group'&gt; &lt;!-- class control-grupo para controle dos conteudos --&gt;
			&lt;label class='control-label' for=''&gt;texto&lt;/label&gt; &lt;!-- Titulo do Input --&gt;
				&lt;div class='controls controls-row'&gt; &lt;!-- div controls controls-row para controle do input  --&gt;
					&lt;div class='span4'&gt; &lt;!-- class='span4' define o tamanho maximo que o input pode ocupar na tela --&gt; 
						&lt;!-- dentro do input é utilizada a class='span12'  para que o input ocupe 100% do espaço definido na class='span4' acima --&gt;
						&lt;input type="" name="" id="" size="" maxlength="" class="" value=""&gt;
					&lt;/div&gt; &lt;!-- fecha span4 --&gt;
				&lt;/div&gt; &lt;!-- fecha control controls-row --&gt;
			&lt;/div&gt; &lt;!-- control-group --&gt;
		&lt;/div&gt; &lt;!-- fecha span4 --&gt;

		&lt;div class='span2'&gt;&lt;/div&gt; &lt;!-- espaçamento lateral esquerda da pagina --&gt; 

	&lt;/div&gt; &lt;!-- fecha row-fluid --&gt;
		
&lt;/form&gt;

&lt;/div&gt;
&lt;!-- Fechamento da div container --&gt;

&lt;?php

/**
* include do rodape
*/

include("rodape.php");

?&gt;




            </textarea>
        </div>
        
            
    </div>
</div>
</div>