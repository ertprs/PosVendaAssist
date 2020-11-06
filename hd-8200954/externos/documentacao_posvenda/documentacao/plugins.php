<div class="container-fluid">
    
    <!-- Plugins -->
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="span10">
                <h3>Plugins</h3>
                <ul class="nav nav-pills">
                <li><a href="#autocomplete">AutoComplete</a></li>
                <li><a href="#datepicker">DatePicker</a></li>
                <li><a href="#multiselect">MultiSelect</a></li>
                <li><a href="#shadowbox">ShadowBox</a></li>
                <li><a href="#maskinput">MaskInput</a></li>
                <li><a href="#priceformat">PriceFormat</a></li>
                <li><a href="#tooltip">Tooltip & Popover</a></li>
                <li><a href="#datatable">DataTable</a></li>
                <li><a href="#alphanumeric">AlphaNumeric</a></li>
                <li><a href="#">InformaçãoCompleta</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid">
        <div class="span10">
            <h4>Plugins </h4>
            <p>Para utilizar os plugins é necessário incluir o código abaixo logo após o include do <code>"cabecalho_new.php"</code></p>
            <div class="well well-small">
                &lt;?php <br /><br />
                $plugins = array(
                "nome_plugin"
                );<br /><br />
                include "plugin_loader.php";<br /><br />
                [...]
            </div>
            <hr/>
        </div>
        </div>
    </div>
    <!-- Autocomplete -->
    <div class="container-fluid" id="autocomplete">
        <div class="row-fluid">
            <div class="span10">
                <h4>Autocomplete </h4>
                <script>
                    $(function() {

                        $("#estado").autocomplete({
                            //(arquivo, array, objeto) que irá buscar os resultados
                            source: "estado_autocomplete.php",
                            //Caracteres necessarios para começar a pesquisa
                            minLength: 2,
                            //Função de select do autocomplete
                            select: function(event, ui) {
                                $("#estado").val(ui.item["estado"]);
                                $("#estado_sigla").val(ui.item["sigla"]);
                                //Para a função de select
                                return false;
                            }
                        }).data("uiAutocomplete")._renderItem = function(ul, item) {
                            //Função para modificar a forma de mostrar

                            //Joga para dentro da var o que será mostrado
                            var text = item["sigla"] + " - " + item["estado"];
                            return $("<li></li>").data("item.autocomplete", item).append("<a>" + text + "</a>").appendTo(ul);
                        };
                    });
                </script>
                <p>Plugin que dá a opção de autocompletar o input com informações do banco ou não, <a href="http://api.jqueryui.com/autocomplete/" target="_blank">documentação do autocomplete</a></p>
                <h6>Dependência </h6>
                <ul>
                    <li>Nenhuma</li>
                </ul>
                <h6>Carregamento PHP </h6>
                <div class="well well-small">
                    &lt;?php <br /><br />
                    $plugins = array(
                    "autocomplete"
                    );<br /><br />
                    include "plugin_loader.php";<br /><br />
                    [...]
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
                <h6>Uso Normal </h6>
                <textarea class="span10" rows="28" readonly="readonly">
                    &lt;script&gt;
                    $(function () {
                        $(&quot;#estado&quot;).autocomplete({
                            //(arquivo, array, objeto) que irá buscar os resultados
                            source: &quot;estado_autocomplete.php&quot;,
                            //Função de select do autocomplete
                            //Caracteres necessarios para começar a pesquisa
                            minLength: 2,
                            select: function (event, ui) {
                                $(&quot;#estado&quot;).val(ui.item[&quot;estado&quot;]);
                                $(&quot;#estado_sigla&quot;).val(ui.item[&quot;sigla&quot;]);
                                //Para a função de select
                                return false;
                            }
                        }).data(&quot;uiAutocomplete&quot;)._renderItem = function (ul, item) {
                            //Função para modificar a forma de mostrar

                            //Joga para dentro da var o que será mostrado
                            var text = item[&quot;sigla&quot;] + &quot; - &quot; + item[&quot;estado&quot;];
                            return $(&quot;&lt;li&gt;&lt;/li&gt;&quot;).data(&quot;item.autocomplete&quot;, item).append(&quot;&lt;a&gt;&quot;+text+&quot;&lt;/a&gt;&quot;).appendTo(ul);
                        };
                    });
                    &lt;/script&gt;

                    &lt;input type=&quot;text&quot; id=&quot;estado&quot; /&gt;
                    &lt;input type=&quot;text&quot; id=&quot;estado_sigla&quot; /&gt;
                </textarea>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
                <b>estado_autocomplete.php </b>
                <textarea class="span10" rows="23" readonly="readonly">
                        &lt;?php
                        $array_estados = array(&quot;AC&quot; =&gt; &quot;Acre&quot;, &quot;AL&quot; =&gt; &quot;Alagoas&quot;, &quot;AM&quot; =&gt; &quot;Amazonas&quot;, &quot;AP&quot; =&gt; &quot;Amapá&quot;, &quot;BA&quot; =&gt; &quot;Bahia&quot;, &quot;CE&quot; =&gt; &quot;Ceará&quot;, &quot;DF&quot; =&gt; &quot;Distrito Federal&quot;, &quot;ES&quot; =&gt; &quot;Espírito Santo&quot;, &quot;GO&quot; =&gt; &quot;Goiás&quot;, &quot;MA&quot; =&gt; &quot;Maranhão&quot;, &quot;MG&quot; =&gt; &quot;Minas Gerais&quot;, &quot;MS&quot; =&gt; &quot;Mato Grosso do Sul&quot;, &quot;MT&quot; =&gt; &quot;Mato Grosso&quot;, &quot;PA&quot; =&gt; &quot;Pará&quot;, &quot;PB&quot; =&gt; &quot;Paraíba&quot;,&quot;PE&quot; =&gt; &quot;Pernambuco&quot;, &quot;PI&quot; =&gt; &quot;Piauí&quot;, &quot;PR&quot; =&gt; &quot;Paraná&quot;,&quot;RJ&quot; =&gt; &quot;Rio de Janeiro&quot;,  &quot;RN&quot; =&gt; &quot;Rio Grande do Norte&quot;,&quot;RO&quot;=&gt;&quot;Rondônia&quot;,&quot;RR&quot; =&gt; &quot;Roraima&quot;, &quot;RS&quot; =&gt; &quot;Rio Grande do Sul&quot;,&quot;SC&quot; =&gt; &quot;Santa Catarina&quot;,&quot;SE&quot; =&gt; &quot;Sergipe&quot;, &quot;SP&quot; =&gt; &quot;São Paulo&quot;, &quot;TO&quot; =&gt; &quot;Tocantins&quot;);

                        $term = $_GET[&quot;term&quot;];

                        $array_pesquisados = array();

                        foreach ($array_estado as $sigla =&gt; $estado) {
                            if (preg_match(&quot;/$term/&quot;, $sigla) || preg_match(&quot;/$term/&quot;, $estado)) {
                                $array_pesquisados[] = array(&quot;sigla&quot; =&gt; $sigla, &quot;estado&quot; =&gt; utf8_encode($estado));
                            }
                        }

                        echo json_encode($array_pesquisados);
                        exit;
                        ?&gt;
                </textarea>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span12">
                <h5>Exemplo (o autocomplete só irá funcionar no campo estado) </h5>
                <label>Estado</label> <input type="text" id="estado" /> 
                <label>Sigla</label> <input type="text" id="estado_sigla" />
                <h6>Uso Função Genérica </h6>
                <p>A função genérica já vem com alguns autocompletes prontos para uso, facilitando assim seu uso caso precise usar o autocomplete para algumas das funções abaixo</p>
                <ul>
                    <li>Produto</li>
                    <ul>
                        <li>Necessário os campos: #produto_descricao #produto_referencia</li>
                    </ul>
                    <li>Peça</li>
                    <ul>
                        <li>Necessário os campos: #peca_descricao #peca_referencia</li>
                    </ul>
                    <li>Posto</li>
                    <ul>
                        <li>Necessário os campos: #codigo_posto #descricao_posto</li>
                    </ul>
                    <li>Revenda</li>
                    <ul>
                        <li>Necessário os campos: #cnpj #razao_social</li>
                    </ul>
                </ul>
                <textarea class="span10" rows="15" readonly="readonly">
                    &lt;script&gt;
                    $(function () {
                        $.autocompleteLoad(Array(&quot;produto&quot;, &quot;peca&quot;, &quot;posto&quot;));
                    });
                    &lt;/script&gt;

                    &lt;input type=&quot;text&quot; id=&quot;produto_referencia&quot; /&gt;
                    &lt;input type=&quot;text&quot; id=&quot;produto_descricao&quot; /&gt;

                    &lt;input type=&quot;text&quot; id=&quot;peca_referencia&quot; /&gt;
                    &lt;input type=&quot;text&quot; id=&quot;peca_descricao&quot; /&gt;

                    &lt;input type=&quot;text&quot; id=&quot;codigo_posto&quot; /&gt;
                    &lt;input type=&quot;text&quot; id=&quot;descricao_posto&quot; /&gt;
                </textarea>
                <hr/>
            </div>
        </div>
    </div>

    <!-- Datepicker -->
    <div class="container-fluid" id="datepicker">
        <div class="row-fluid">
            <div class="span10">
                <h4>Datepicker </h4>
                <script>
                    $(function() {
                        $("#data").datepicker().mask("99/99/9999");
                    });
                </script>
                <p>Plugin que adiciona um calendario ao campo, <a href="http://api.jqueryui.com/datepicker/" target="_blank">documentação do datepicker</a></p>
                <h6>Dependência </h6>
                <ul>
                    <li>Maskinput</li>
                </ul>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
                <h6>Carregamento PHP </h6>
                <div class="well well-small">
                    &lt;?php <br /><br />
                    $plugins = array(
                        "mask",
                        "datepicker"
                    );<br /><br />
                    include "plugin_loader.php";<br /><br />
                    [...]
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
                <h6>Uso Normal </h6>
                <textarea class="span10" rows="8" readonly="readonly">
                    &lt;script&gt;
                    $(function() {
                        $("#data").datepicker().mask("99/99/9999");
                    });
                    &lt;/script&gt;

                    &lt;input type=&quot;text&quot; id=&quot;data&quot; /&gt;
                </textarea>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
                <h5>Exemplo </h5>
                <label>Data</label><input type="text" id="data" />
                <h6>Uso Função Genérica </h6>
                <p>A função genérica já adiciona datepicker e mask ao campos que possuem as id's passadas no array para a função</p>
                <textarea class="span10" rows="8" readonly="readonly">
                    &lt;script&gt;
                    $(function() {
                        $.datepickerLoad(["data"]);
                    });
                    &lt;/script&gt;

                    &lt;input type=&quot;text&quot; id=&quot;data&quot; /&gt;
                </textarea>
                <hr/>
            </div>
        </div>
    </div>

    <!-- MultiSelect -->
    <div class="container-fluid" id="multiselect">
        <div class="row-fluid">
            <div class="span10">
                <h4>MultiSelect </h4>
                <script>
                    $(function() {
                        $("#dia_semana").multiselect({
                            selectedText: "selecionados # de #"
                        });
                    });
                </script>
                <p>Plugin que permite múltiplas seleções no select, <a href="http://www.erichynds.com/jquery/jquery-ui-multiselect-widget/" target="_blank">documentação do multi-select</a></p>
                <h6>Dependência </h6>
                <ul>
                    <li>Nenhuma</li>
                </ul>
                <h6>Carregamento PHP </h6>
                <div class="well well-small">
                    &lt;?php <br /><br />
                    $plugins = array(
                    "multiselect"                      
                    );<br /><br />
                    include "plugin_loader.php";<br /><br />
                    [...]
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
                <h6>Uso Normal </h6>
                <textarea class="span10" rows="18" readonly="readonly">
                    &lt;script&gt;
                    $(function() {
                        $("#dia_semana").multiselect({
                           selectedText: "selecionados # de #"
                        });
                    });
                    &lt;/script&gt;

                    &lt;select name=&quot;dia_semana[]&quot; id=&quot;dia_semana&quot; multiple=&quot;multiple&quot; /&gt;
                        &lt;option&gt;Segunda-Feira&lt;/option&gt;
                        &lt;option&gt;Terça-Feira&lt;/option&gt;
                        &lt;option&gt;Quarta-Feira&lt;/option&gt;
                        &lt;option&gt;Quinta-Feira&lt;/option&gt;
                        &lt;option&gt;Sexta-Feira&lt;/option&gt;
                        &lt;option&gt;Sábado&lt;/option&gt;
                        &lt;option&gt;Domingo&lt;/option&gt;
                    &lt;/select&gt;
                </textarea>
                <h5>Exemplo </h5>
                <label>Dia Semana</label>
                <select name="dia_semana[]" id="dia_semana" multiple="multiple" >
                    <option value="1">Segunda-Feira</option>
                    <option value="2">Terça-Feira</option>
                    <option value="3">Quarta-Feira</option>
                    <option value="4">Quinta-Feira</option>
                    <option value="5">Sexta-Feira</option>
                    <option value="6">Sábado</option>
                    <option value="7">Domingo</option>
                <select/>
                <hr/>
            </div>
        </div>
    </div>

    <!-- Shadowbox -->
    <div class="container-fluid" id="shadowbox">
        <div class="row-fluid">
            <div class="span10">
                <h4>ShadowBox </h4>

                <p>Plugin que permite abrir outras telas dentro da página, <a href="http://www.shadowbox-js.com/usage.html" target="_blank">documentação do ShadowBox</a>.</p>

                <h6>Dependência </h6>

                <ul>
                    <li>Nenhuma</li>
                </ul>
                <h6>Carregamento PHP </h6>
                <div class="well well-small">
                    &lt;?php <br /><br />
                    $plugins = array(
                    "shadowbox"
                    );<br /><br />
                    include "plugin_loader.php";<br /><br />
                    [...]
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
                <h6>Uso Normal</h6>
                <div class="control-group">            
                    <textarea class="span10" rows="20" cols="12" readonly="readonly">
                        &lt;input type="button" id='abrirShadow' class='btn' value='Abrir Exemplo'/&gt;

                        &lt;script&gt;
                            $("#abrirShadow").click(function() {
                                Shadowbox.init();
                                Shadowbox.open({
                                    content: 'http://localhost/docposvenda/#',
                                    player: "iframe",
                                    title: "ShadowBox",
                                    width: 600,
                                    height: 500
                                });

                                // No caso de utilizar Iframe, para fechar o ShadowBox via Javascript            
                                window.parent.Shadowbox.close();

                            });

                        &lt;/script&gt;
                    </textarea>
                </div>

                <h5>Exemplo </h5>

                <div class='control-group'>
                    <input type="button" id='abrirShadow' class='btn' value='Abrir Exemplo'/>
                </div>

                <script>
                    $("#abrirShadow").click(function() {
                        Shadowbox.init();
                        Shadowbox.open({
                            content: 'http://telecontrol.com.br',
                            player: "iframe",
                            title: "ShadowBox",
                            width: 800,
                            height: 500
                        });

                        /* window.parent.function();
                         Shadowbox.close(); */
                    });

                    $('#fecharShadow').click(function() {
                        Shadowbox.close();
                    });

                </script>
            </div>
        </div>
    </div>

    <!-- Maskinput -->
    <div class="container-fluid" id="maskinput">
        <div class="row-fluid">
            <div class="span10">
            <h4>Maskinput</h4>

            <p>Plugin que adiciona mascara ao campo, <a href="http://digitalbush.com/projects/masked-input-plugin/" target="_blank">documentação do Maskinput</a>.</p>

            <h6>Dependência </h6>
            <ul>
                <li>Nenhuma</li>
            </ul>
            <h6>Carregamento PHP </h6>
            <div class="well well-small">
                &lt;?php <br /><br />
                $plugins = array(
                "mask"
                );<br /><br />
                include "plugin_loader.php";<br /><br />
                [...]
            </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
            <h6>Uso Normal</h6>
            <div class="control-group">            
                <textarea class="span10" rows="10" cols="12" readonly="readonly">
                    &lt;input type="text" id='telefone'/&gt;

                    &lt;script&gt;
                     $(function(){

                        $("#telefone").mask("(99) 9999-9999");

                    });
                    &lt;/script&gt;
                </textarea>
            </div>
            <h5>Exemplo </h5>
            <div class='control-group'>
                <input type="text" id="telefone"/>
            </div>

            <script>
                $(function() {

                    $("#telefone").mask("(99) 9999-9999");

                });
            </script>
            </div>
        </div>
    </div>

    <!-- Priceformat -->
    <div class="container-fluid" id="priceformat">
        <div class="row-fluid">
            <div class="span10">
                <h4>PriceFormat</h4>

                <p>Plugin que formata o campo Moeda/Valor, <a href="http://jquerypriceformat.com/" target="_blank">documentação do Priceformat</a>.</p>

                <h6>Dependência </h6>

                <ul>
                    <li>Nenhuma</li>
                </ul>
                <h6>Carregamento PHP </h6>
                <div class="well well-small">
                &lt;?php <br /><br />
                $plugins = array(
                "price_format"
                );<br /><br />
                include "plugin_loader.php";<br /><br />
                [...]
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
                <h6>Uso Normal</h6>
                <div class="control-group">            
                <textarea class="span10" rows="1" cols="12" readonly="readonly">
                    &lt;input type="text" id='preco' price="true"/&gt;
                </textarea>
                </div>

                <h5>Exemplo </h5>

                <div class='span4'>
                    <input type="text" id="preco" price="true" size="12">
                </div>

                <script>
                $(function() {

                    $("#preco").priceFormat({
                        prefix: '',
                        centsSeparator: ',',
                        thousandsSeparator: '.'
                    });
                });
                </script>
            </div>
        </div>
    </div>

    <!-- Tooltip Popover -->
    <div class="container-fluid" id="tooltip">
        <div class="row-fluid">
            <div class="span10">
            <h4>Tooltip & Popover</h4>

            <p>Plugin utilizado para colocar pequenas mensagens, avisos, alertas, dicas ou comentários em elementos das páginas, como inputs, botoes, etc.</p>
            <p><a href="http://getbootstrap.com/2.3.2/javascript.html#tooltips" target="_blank">documentação dos plugins</a></p>

            <h6>Popover</h6>
            <h6>Dependência </h6>

            <ul>
                <li>Nenhuma</li>
            </ul>
            <h6>Plugin carregado automaticamente no bootstrap.js</h6>

            <div class="control-group">            
                <textarea class="span10" rows="12" cols="12" readonly="readonly">
                    &lt;div class="control-group"&gt;                
                        &lt;label class="control-label" for=""&gt;Cód. Validação de Número de Série&lt;/label&gt;
                        &lt;div class="input-append"&gt;
                            &lt;input type='text' class="span3" id='codigo_validacao_serie' name='codigo_validacao_serie' value="&lt;?php echo $codigo_validacao_serie; ?&gt;" maxlength='30'&gt;
                            &lt;span class="add-on"&gt;&lt;i id="btnPopover" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="Info" data-content="Caso a família tenha mais de um código, separar por virgula. Ex: TS,TK" class="icon-question-sign"&gt;&lt;/i&gt;	&lt;/span&gt;
                        &lt;/div&gt;
                    &lt;/div&gt;
                </textarea>
            </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
            <h5>Exemplo Popover</h5>
            <div class="control-group">                
                <label class="control-label" for="">Cód. Validação de Número de Série</label>
                <div class="input-append">
                    <input type='text' class="span12" id='codigo_validacao_serie' name='codigo_validacao_serie' value="<?php echo $codigo_validacao_serie; ?>" maxlength='30'>
                    <span class="add-on"><i id="btnPopover" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="Info" data-content="Caso a família tenha mais de um código, separar por virgula. Ex: TS,TK" class="icon-question-sign"></i>	</span>
                </div>
            </div>

            <h6>Tooltip</h6>
            <h6>Dependência </h6>

            <ul>
                <li>Nenhuma</li>
            </ul>

            <h6>Carregamento</h6>
            <div class="well well-small">
                &lt;?php <br /><br />
                $plugins = array(
                "tooltip"
                );<br /><br />
                include "plugin_loader.php";<br /><br />
                [...]
            </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
            <div class="control-group">            
                <textarea class="span10" rows="5" cols="12" readonly="readonly">
                    &lt;div class="controls"&gt;
                        &lt;button id='tooltip' class='btn' data-toggle="tooltip" type="button" title="Esse botão serve para pesquisar"&gt;Passe o mouse&lt;/button&gt;
                    &lt;/div&gt;
                </textarea>
            </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
            <h5>Exemplo de Tooltip</h5>
                        
            <div class="control-group">                           
                <div class="controls">                                          
                    <a href="#" id="tooltipee" data-toggle="tooltip" data-placement="top" title="Isto é um tooltip">Passe o mouse para ver um tooltip</a>
                </div>
            </div>
                        
            <script>
                $('#btnPopover').popover();
            </script>
            </div>
        </div>
    </div>

    <!-- Datatable -->
    <div class="container-fluid" id="datatable">
        <div class="row-fluid">
            <div class="span10">
                <h4>Datatable</h4>

                <p><b>Data Table</b> é um plugin para manipular dados em uma <code>&lt;table&gt;</code>, com ele é possível fazer paginação
                    , definir quantos registros serão mostrados em tela, e fazer pesquisas no conteúdo impresso. Sua configuração é simples e será demonstrada abaixo.</p>

                <p><a href="http://datatables.net/blog/Twitter_Bootstrap" target="_blank">documentação do Datatable</a></p>

                <h6>Dependência </h6>

                <ul>
                    <li>Nenhuma</li>
                </ul>
                <h6>Carregamento PHP </h6>
                <div class="well well-small">
                    &lt;?php <br /><br />
                    $plugins = array(
                    "dataTable"
                    );<br /><br />
                    include "plugin_loader.php";<br /><br />
                    [...]
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
                <h6>Uso normal (Html)</h6>
                <div class="control-group">            
                    <textarea class="span10" rows="33" cols="12" readonly="readonly">    &lt;table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-large' &gt;
                            &lt;table&gt; 
                                &lt;thead&gt;
                                        &lt;tr class='titulo_tabela' &gt;
                                                &lt;th colspan="9" &gt;OS&lt;/th&gt;
                                        &lt;/tr&gt;
                                        &lt;tr class='titulo_coluna' &gt;
                                                &lt;th&gt;OS&lt;/th&gt;
                                                &lt;th&gt;Abertura&lt;/th&gt;
                                                &lt;th&gt;Fechamento&lt;/th&gt;
                                                &lt;th&gt;Atendimento&lt;/th&gt;
                                                &lt;th&gt;Produto&lt;/th&gt;
                                                &lt;th&gt;Série&lt;/th&gt;
                                                &lt;th&gt;Defeito&lt;/th&gt;
                                                &lt;th&gt;Peça&lt;/th&gt;
                                                &lt;th&gt;Serviço&lt;/th&gt;
                                        &lt;/tr&gt;
                                &lt;/thead&gt;
                                &lt;tbody&gt;
                                        &lt;tr&gt;
                                                &lt;td class='tac'&gt;&lt;a href='pag.php' target='_blank' &gt;123456&lt;/a&gt;&lt;/td&gt;
                                                &lt;td class='tac'&gt;01/01/2013&lt;/td&gt;
                                                &lt;td class='tac'&gt;05/01/2013&lt;/td&gt;
                                                &lt;td class='tac'&gt;&lt;a href='pag.php' target='_blank' &gt;321654&lt;/a&gt;&lt;/td&gt;
                                                &lt;td class='tal'&gt;PAC&lt;/td&gt;
                                                &lt;td class='tac'&gt;MAN&lt;/td&gt;
                                                &lt;td class='tal'&gt;Danos&lt;/td&gt;
                                                &lt;td class='tal'&gt;112121&lt;/td&gt;
                                                &lt;td class='tal'&gt;52558&lt;/td&gt;
                                        &lt;/tr&gt;
                                &lt;/tbody&gt;
                        &lt;/table&gt;    
                    </textarea>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
              <h5>Exemplo Tabela Default</h5>
                <div class="control-group">            
                    <textarea class="span10" rows="7" cols="12" readonly="readonly">
                        $(function() {
                            var table = new Object();
                            table['table'] = '#resultado_os_atendimento';
                            table['type'] = 'basic';
                            $.dataTableLoad(table);
                        });
                    </textarea>                        
                </div>
            </div>
        </div>
        <table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class='titulo_tabela' >
                    <th colspan="9" >Tabela Básica</th>
                </tr>
                <tr class='titulo_coluna' >
                    <th>OS</th>
                    <th>Abertura</th>
                    <th>Fechamento</th>
                    <th>Atendimento</th>
                    <th>Produto</th>
                    <th>Série</th>
                    <th>Defeito</th>
                    <th>Peça</th>
                    <th>Serviço</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class='tac'><a href='pag.php' target='_blank' >123456</a></td>
                    <td class='tac'>01/01/2013</td>
                    <td class='tac'>05/01/2013</td>
                    <td class='tac'><a href='pag.php' target='_blank' >321654</a></td>
                    <td class='tal'>PAC</td>
                    <td class='tac'>MAN</td>
                    <td class='tal'>Danos</td>
                    <td class='tal'>112121</td>
                    <td class='tal'>52558</td>
                </tr>
                <tr>
                    <td class='tac'><a href='pag.php' target='_blank' >123456</a></td>
                    <td class='tac'>01/01/2013</td>
                    <td class='tac'>05/01/2013</td>
                    <td class='tac'><a href='pag.php' target='_blank' >321654</a></td>
                    <td class='tal'>PAC</td>
                    <td class='tac'>MAN1</td>
                    <td class='tal'>Danos</td>
                    <td class='tal'>112121</td>
                    <td class='tal'>52558</td>
                </tr>
                <tr>
                    <td class='tac'><a href='pag.php' target='_blank' >123456</a></td>
                    <td class='tac'>01/01/2013</td>
                    <td class='tac'>05/01/2013</td>
                    <td class='tac'><a href='pag.php' target='_blank' >321654</a></td>
                    <td class='tal'>PAC</td>
                    <td class='tac'>2</td>
                    <td class='tal'>Danos</td>
                    <td class='tal'>112121</td>
                    <td class='tal'>52558</td>
                </tr>
            </tbody>
        </table>    
        <script>
            $(function() {
                var table = new Object();
                table['table'] = '#resultado_os_atendimento';
                table['type'] = 'basic';
                $.dataTableLoad(table);
            });
        </script>
     
        <div class="row-fluid">
            <div class="span10">
                <h5>Exemplo Tabela Full (Todos Componentes)</h5>
                <div class="control-group">            
                    <textarea class="span10" rows="8" cols="12" readonly="readonly">
                        $(function() {
                            var table = new Object();
                            table['table'] = '#resultado_os_atendimento2';
                            table['type'] = 'full';
                            $.dataTableLoad(table);
                        });
                    </textarea>                        
                </div>
            </div>
        </div>
        <table id="resultado_os_atendimento2" class='table table-striped table-bordered table-hover table-fixed'>
            <thead>
                <tr class='titulo_tabela' >
                    <th colspan="9" >Tabela Full</th>
                </tr>
                <tr class='titulo_coluna' >
                    <th>OS</th>
                    <th>Abertura</th>
                    <th>Fechamento</th>
                    <th>Atendimento</th>
                    <th>Produto</th>
                    <th>Série</th>
                    <th>Defeito</th>
                    <th>Peça</th>
                    <th>Serviço</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class='tac'><a href='pag.php' target='_blank' >123456</a></td>
                    <td class='tac'>01/01/2013</td>
                    <td class='tac'>05/01/2013</td>
                    <td class='tac'><a href='pag.php' target='_blank' >321654</a></td>
                    <td class='tal'>PAC</td>
                    <td class='tac'>MAN</td>
                    <td class='tal'>Danos</td>
                    <td class='tal'>112121</td>
                    <td class='tal'>52558</td>
                </tr>
                <tr>
                    <td class='tac'><a href='pag.php' target='_blank' >123456</a></td>
                    <td class='tac'>01/01/2013</td>
                    <td class='tac'>05/01/2013</td>
                    <td class='tac'><a href='pag.php' target='_blank' >321654</a></td>
                    <td class='tal'>PAC</td>
                    <td class='tac'>MAN1</td>
                    <td class='tal'>Danos</td>
                    <td class='tal'>112121</td>
                    <td class='tal'>52558</td>
                </tr>
                <tr>
                    <td class='tac'><a href='pag.php' target='_blank' >123456</a></td>
                    <td class='tac'>01/01/2013</td>
                    <td class='tac'>05/01/2013</td>
                    <td class='tac'><a href='pag.php' target='_blank' >321654</a></td>
                    <td class='tal'>PAC</td>
                    <td class='tac'>2</td>
                    <td class='tal'>Danos</td>
                    <td class='tal'>112121</td>
                    <td class='tal'>52558</td>
                </tr>
            </tbody>
        </table>    
        
        <script>
            $(function() {
                var table = new Object();
                table['table'] = '#resultado_os_atendimento2';
                table['type'] = 'full';
                $.dataTableLoad(table);
            });
        </script>
       
        <div class="row-fluid">
            <div class="span10">
                <h5>Exemplo Tabela Custom</h5>
                <div class="control-group">            
                    <textarea class="span10" rows="8" cols="12" readonly="readonly">
                        $(function() {
                            var table = new Object();
                            table['table'] = '#resultado_os_atendimento3';
                            table['type'] = 'custom';
                            table['config'] = Array('paginacao', 'resultados_por_pagina');
                            $.dataTableLoad(table);
                        });
                    </textarea>                        
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
                <div class="hero-unit">
                    <small>Para tabeas customizadas podem ser colocados no array de opções os seguintes itens:</small>
                    <ul>
                        <li><small>pesquisa</small></li>
                        <li><small>resultados_por_pagina</small></li>
                        <li><small>paginacao</small></li>
                        <li><small>info</small></li>
                    </ul>
                </div>
            </div>
        </div>
        <table id="resultado_os_atendimento3" class='table table-striped table-bordered table-hover table-large' >
            <thead>
                <tr class='titulo_tabela' >
                    <th colspan="9" >Tabela Custom</th>
                </tr>
                <tr class='titulo_coluna' >
                    <th>OS</th>
                    <th>Abertura</th>
                    <th>Fechamento</th>
                    <th>Atendimento</th>
                    <th>Produto</th>
                    <th>Série</th>
                    <th>Defeito</th>
                    <th>Peça</th>
                    <th>Serviço</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class='tac'><a href='pag.php' target='_blank' >123456</a></td>
                    <td class='tac'>01/01/2013</td>
                    <td class='tac'>05/01/2013</td>
                    <td class='tac'><a href='pag.php' target='_blank' >321654</a></td>
                    <td class='tal'>PAC</td>
                    <td class='tac'>MAN</td>
                    <td class='tal'>Danos</td>
                    <td class='tal'>112121</td>
                    <td class='tal'>52558</td>
                </tr>
                <tr>
                    <td class='tac'><a href='pag.php' target='_blank' >123456</a></td>
                    <td class='tac'>01/01/2013</td>
                    <td class='tac'>05/01/2013</td>
                    <td class='tac'><a href='pag.php' target='_blank' >321654</a></td>
                    <td class='tal'>PAC</td>
                    <td class='tac'>MAN1</td>
                    <td class='tal'>Danos</td>
                    <td class='tal'>112121</td>
                    <td class='tal'>52558</td>
                </tr>
                <tr>
                    <td class='tac'><a href='pag.php' target='_blank' >123456</a></td>
                    <td class='tac'>01/01/2013</td>
                    <td class='tac'>05/01/2013</td>
                    <td class='tac'><a href='pag.php' target='_blank' >321654</a></td>
                    <td class='tal'>PAC</td>
                    <td class='tac'>2</td>
                    <td class='tal'>Danos</td>
                    <td class='tal'>112121</td>
                    <td class='tal'>52558</td>
                </tr>
            </tbody>
        </table>    
                                   
        <script>
            $(function() {
                var table = new Object();
                table['table'] = '#resultado_os_atendimento3';
                table['type'] = 'custom';
                table['config'] = Array('paginacao', 'resultados_por_pagina');
                $.dataTableLoad(table);
            });
        </script>
    </div>
   
    <!-- Priceformat -->
    <div class="container-fluid" id="alphanumeric">
        <div class="row-fluid">
            <div class="span10">
                <h4>AlphaNumeric</h4>

                <p>AlphaNumeric, é um plugin para criar restrições de caracteres digitados, podem escolher o que pode ser digitado, somente números, somente letras etc <a href="http://www.shiguenori.com/material/alphanumeric/" target="_blank">documentação do AlphaNumeric</a>.</p>

                <h6>Dependência </h6>

                <ul>
                    <li>Nenhuma</li>
                </ul>
                <h6>Carregamento PHP </h6>
                <div class="well well-small">
                    &lt;?php <br /><br />
                    $plugins = array(
                    "alphanumeric"
                    );<br /><br />
                    include "plugin_loader.php";<br /><br />
                    [...]
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">
                <h6>Uso Normal</h6>
                <div class="control-group">            
                    <textarea class="span10" rows="13" cols="12" readonly="readonly">
                        &lt;script&gt;
                            $(function() {
                                $("#numeros").numeric();
                                $("#letras").alpha();
                                $("#numeros_letras").alphanumeric();
                                $("#numeros_letras_pontuacao").alphanumeric({allow: ",.;!?-"});
                            });
                        &lt;/script&gt;
                        &lt;input type="text" id='numeros' "/&gt;
                        &lt;input type="text" id='letras' "/&gt;
                        &lt;input type="text" id='numeros_letras' "/&gt;
                        &lt;input type="text" id='numeros_letras_pontuacao' "/&gt;
                    </textarea>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span10">

                <h5>Exemplo </h5>

                <div class='span4'>
                    Números <input type="text" id="numeros" size="12">
                    Letras <input type="text" id="letras" size="12">
                    Números e Letras<input type="text" id="numeros_letras" size="12">
                    Números, Letras e (, . ; ! ? -)<input type="text" id="numeros_letras_pontuacao" size="12">
                </div>

                <script>
                    $(function() {
                        $("#numeros").numeric();
                        $("#letras").alpha();
                        $("#numeros_letras").alphanumeric();
                        $("#numeros_letras_pontuacao").alphanumeric({allow: ",.;!?-"});
                    });
                </script>
            </div>
        </div>
    </div>
            
</div>