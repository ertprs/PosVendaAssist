<div class="container-fluid">
    <div class="row-fluid">    
        <div class="span10">
            <h3>Tabelas</h3>
        
            <p>Os tamanhos das tabelas são definidos através de 3 classes:</p>
    
            <table class='table table-bordered table-striped table-fixed'>
                <thead>
                    <tr class="titulo_coluna">
                        <th >Classe</th>
                        <th>Definição</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>table-large</td>
                        <td>max-width: 1024px</td>
                    </tr>
                    <tr>
                        <td>table-normal</td>
                        <td>max-width: 850px</td>
                    </tr>
                    <tr>
                        <td>table-fixed</td>
                        <td>width: 850px</td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>
    
    <div class="row-fluid">
        <div class="span10">
            <p>Estrutura Html de uma Tabela:</p>
            <div class="control-group">
                <textarea class="span10" rows="20" cols="12" disabled="disable">    &lt;table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-large' &gt;
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
            <h4>Exemplo:</h4>

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
                
        </div>
    </div>   

    
</div>