<div class="container-fluid">
<div class="row-fluid">
    <div class="span10">
        <h3>Bot�es</h3>
        <div class="row-fluid">
            <div class="span10">
                <h4>Bot�es de A��o</h4>
                <p>Por padr�o � utilizado dois bot�es, o bot�o de a��o normal, e o de Excel no caso de gera��o de relatorio, ser� demonstrado as demais op��es, s� que seu uso fica mediante aprova��o do analista</p>

                <table class="table table-bordered table-striped table-botoes">
                    <thead>
                        <tr class="titulo_coluna">
                            <th>Bot�o Bootstrap</th>
                            <th>Sugest�o de Uso</th>                            
                            <th>Classe</th>                            
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><button type="button" class="btn">Padr�o</button></td>
                            <td>Gravar, Pesquisar, Alterar, Inativar</td>
                            <td><code>btn</code></td>                            
                        </tr>
                        <tr>
                            <td><button type="button" class="btn btn-primary">A��o</button></td>
                            <td>A Definir...</td>
                            <td><code>btn btn-primary</code></td>                            
                        </tr>
                        <tr>
                            <td><button type="button" class="btn btn-info">A��o</button></td>
                            <td>A Definir...</td>
                            <td><code>btn btn-info</code></td>                            
                        </tr>
                        <tr>
                            <td><button type="button" class="btn btn-success">A��o</button></td>
                            <td>Ativar,Consultar (Bot�o utilizado dentro do Resultado de uma pesquisa) </td>
                            <td><code>btn btn-success</code></td>                            
                        </tr>
                        <tr>
                            <td><button type="button" class="btn btn-warning">A��o</button></td>
                            <td>Imprimir</td>
                            <td><code>btn btn-warning</code></td>                            
                        </tr>
                        <tr>
                            <td><button type="button" class="btn btn-danger">A��o</button></td>
                            <td>Excluir (Acompanhado de <a href="?doc=mensagens">Mensagem</a> de confirma��o), Apagar</td>
                            <td><code>btn btn-danger</code></td>                            
                        </tr>
                        <tr>
                            <td><button type="button" class="btn btn-inverse">A��o</button></td>
                            <td>A Definir...</td>
                            <td><code>btn btn-inverse</code></td>                            
                        </tr>
                        <tr>
                            <td><button type="button" class="btn btn-link">A��o</button></td>
                            <td>A Definir...</td>
                            <td><code>btn btn-link</code></td>                            
                        </tr>
                        <tr>
                            <td>
                                <div style="float: left" class="control-group">
                                    <div id='gerar_excel' class="btn_excel">
                                        <span><img src='http://posvenda.telecontrol.com.br/assist/admin/imagens/excel.png' /></span>
                                        <span class="txt">Gerar Arquivo Excel</span>
                                    </div>
                                </div></td>
                            <td></td>
                            <td><code>Ver c�digo abaixo</code></td>
                        </tr>
                    </tbody>
                </table>



                <p>C�digo do Bot�o</p>
                <div class='control-group'> 
                    <textarea class="span10" rows="2" cols="12" disabled="disable">
&lt;input type='button' class='btn' /&gt;  
&lt;input type='button' class='btn btn-primary' /&gt;  </textarea>            
                </div> 

                <p>C�digo do Bot�o Excel</p>
                <div class='control-group'> 
                    <textarea class="span10" rows="5" cols="12" disabled="disable">
&lt;div id='gerar_excel' class="btn_excel"&gt;
    &lt;span&gt;&lt;img src='http://posvenda.telecontrol.com.br/assist/admin/imagens/excel.png' /&gt;&lt;/span&gt;
    &lt;span class="txt"&gt;Gerar Arquivo Excel&lt;/span&gt;
&lt;/div&gt;
                    </textarea>

                </div> 
            </div>
        </div>  


        <div class="row-fluid" id="dimensionamento">
            <div class="span10">
                <h4>Dimensionamento de Bot�es</h4>
                <p>Por padr�o � utilizado o tamanho normal do Bootstrap, por�m existem situa��es onde � necess�rio um tamanho diferenciado, por exemplo, bot�es dentro de tabelas.</p>
                <p>Quanto ao tamanho a se utilizar, � necess�rio analisar a tela, e escolher o tamanho que melhor se encaixa no layout e na situa��o, essa escolha deve ser tomada juntamente com o analista.</p>

                <p>Para bot�es em resultados de pesquisas utilize o <code>btn-small</code></p>
                <p>Para bot�es em resultados de pesquisas lite utilize o <code>btn-mini</code></p>

                <table class="table table-bordered table-striped table-botoes">
                    <thead>
                        <tr class="titulo_coluna">
                            <th>Bot�o Bootstrap</th>
                            <th>Classe</th>                            
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><button type="button" class="btn">Tamanho Normal</button></td>
                            <td><code>btn</code></td>                            
                        </tr>                        
                        <tr>
                            <td><button type="button" class="btn btn-large">Tamanho Grande</button></td>
                            <td><code>btn btn-large</code></td>                            
                        </tr>                        
                        <tr>
                            <td><button type="button" class="btn btn-small">Tamanho Pequeno</button></td>
                            <td><code>btn btn-small</code></td>                            
                        </tr>                        
                        <tr>
                            <td><button type="button" class="btn btn-mini">Tamanho Mini</button></td>
                            <td><code>btn btn-mini</code></td>                            
                        </tr>                        
                    </tbody>
                </table>

            </div>
        </div>  

        <div class="row-fluid" id="dimensionamento">
            <div class="span10">
                <div id="boolean">
                    <h4>Campo de 2 estados</h4>
                    <p>Em diversas situa��es no sistema existem campo com 2 estados, booleanos, sempre que poss�vel utilizar esses
                        �cones para representar o estado true/false:</p>

                    <table class="table table-bordered table-striped table-botoes">
                        <thead>
                            <tr class="titulo_coluna">
                                <th>�cone</th>
                                <th>Fun�ao</th>                            
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><img class="offset5" src="public/img/icones/status_verde.png"/></td>
                                <td>true, verdadeiro, liberado, etc...</td>                            
                            </tr>                                                                      
                            <tr>
                                <td><img class="offset5" src="public/img/icones/status_vermelho.png"/></td>
                                <td>false, falso, recusado, etc...</td>                            
                            </tr>                                                                      
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
</div>