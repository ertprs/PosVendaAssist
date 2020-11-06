<div class="container-fluid">
<div class="row-fluid">
    <div class="span10">
        <h3>Botões</h3>
        <div class="row-fluid">
            <div class="span10">
                <h4>Botões de Ação</h4>
                <p>Por padrão é utilizado dois botões, o botão de ação normal, e o de Excel no caso de geração de relatorio, será demonstrado as demais opções, só que seu uso fica mediante aprovação do analista</p>

                <table class="table table-bordered table-striped table-botoes">
                    <thead>
                        <tr class="titulo_coluna">
                            <th>Botão Bootstrap</th>
                            <th>Sugestão de Uso</th>                            
                            <th>Classe</th>                            
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><button type="button" class="btn">Padrão</button></td>
                            <td>Gravar, Pesquisar, Alterar, Inativar</td>
                            <td><code>btn</code></td>                            
                        </tr>
                        <tr>
                            <td><button type="button" class="btn btn-primary">Ação</button></td>
                            <td>A Definir...</td>
                            <td><code>btn btn-primary</code></td>                            
                        </tr>
                        <tr>
                            <td><button type="button" class="btn btn-info">Ação</button></td>
                            <td>A Definir...</td>
                            <td><code>btn btn-info</code></td>                            
                        </tr>
                        <tr>
                            <td><button type="button" class="btn btn-success">Ação</button></td>
                            <td>Ativar,Consultar (Botão utilizado dentro do Resultado de uma pesquisa) </td>
                            <td><code>btn btn-success</code></td>                            
                        </tr>
                        <tr>
                            <td><button type="button" class="btn btn-warning">Ação</button></td>
                            <td>Imprimir</td>
                            <td><code>btn btn-warning</code></td>                            
                        </tr>
                        <tr>
                            <td><button type="button" class="btn btn-danger">Ação</button></td>
                            <td>Excluir (Acompanhado de <a href="?doc=mensagens">Mensagem</a> de confirmação), Apagar</td>
                            <td><code>btn btn-danger</code></td>                            
                        </tr>
                        <tr>
                            <td><button type="button" class="btn btn-inverse">Ação</button></td>
                            <td>A Definir...</td>
                            <td><code>btn btn-inverse</code></td>                            
                        </tr>
                        <tr>
                            <td><button type="button" class="btn btn-link">Ação</button></td>
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
                            <td><code>Ver código abaixo</code></td>
                        </tr>
                    </tbody>
                </table>



                <p>Código do Botão</p>
                <div class='control-group'> 
                    <textarea class="span10" rows="2" cols="12" disabled="disable">
&lt;input type='button' class='btn' /&gt;  
&lt;input type='button' class='btn btn-primary' /&gt;  </textarea>            
                </div> 

                <p>Código do Botão Excel</p>
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
                <h4>Dimensionamento de Botões</h4>
                <p>Por padrão é utilizado o tamanho normal do Bootstrap, porém existem situações onde é necessário um tamanho diferenciado, por exemplo, botões dentro de tabelas.</p>
                <p>Quanto ao tamanho a se utilizar, é necessário analisar a tela, e escolher o tamanho que melhor se encaixa no layout e na situação, essa escolha deve ser tomada juntamente com o analista.</p>

                <p>Para botões em resultados de pesquisas utilize o <code>btn-small</code></p>
                <p>Para botões em resultados de pesquisas lite utilize o <code>btn-mini</code></p>

                <table class="table table-bordered table-striped table-botoes">
                    <thead>
                        <tr class="titulo_coluna">
                            <th>Botão Bootstrap</th>
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
                    <p>Em diversas situações no sistema existem campo com 2 estados, booleanos, sempre que possível utilizar esses
                        ícones para representar o estado true/false:</p>

                    <table class="table table-bordered table-striped table-botoes">
                        <thead>
                            <tr class="titulo_coluna">
                                <th>Ícone</th>
                                <th>Funçao</th>                            
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