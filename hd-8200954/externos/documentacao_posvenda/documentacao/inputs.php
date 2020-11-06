<div class="container-fluid">
    <div class="row-fluid">
        <div class="span10">
            <h3>Inputs</h3>

            <ul class="nav nav-pills">
                <li><a href="#simples">Simples</a></li>
                <li><a href="#simpleserro">Simples com Erro</a></li>
                <li><a href="#checkbox">Check Box</a></li>      
                <li><a href="#tamanho">Tamanhos Diferenciados</a></li>            
            </ul>
        </div>
    </div>
    <div class="row-fluid">
    <div class="span10">
        <div class="row-fluid" id='simples'>
            <div class="span10">
                <h4>Campo de Entrada Simples</h4>

                <div class='control-group'> 
                    <label class='control-label' for=''>Titulo do Input</label>
                    <div class='controls controls-row'> 
                        <input type='text' name='' id=''value=""/>     
                    </div>
                </div> 

                <p>Código Input padrão:</p>        
                <div class="control-group">    

                    <textarea class="span10" rows="6" cols="12" disabled="disable">
&lt;div class='control-group'&gt; 
    &lt;label class='control-label' for=''&gt;Titulo do Input&lt;/label&gt;
    &lt;div class='controls controls-row'&gt; 
        &lt;input type='text' name='' id=''value=""/&gt;     
    &lt;/div&gt;
&lt;/div&gt; 
                    </textarea>
                </div>
            </div>
        </div>
    </div>
    </div>
    
    <div class="row-fluid">
    <div class="span10">
        <div class="row-fluid" id='simpleserro'>
            <div class="span10">
                <h4>Campo de Entrada Simples com erro</h4>
                <div class='control-group error'> 
                    <label class='control-label' for=''>Titulo do Input</label>
                    <div class='controls controls-row'> 
                        <input type='text' name='' id=''value=""/>                     
                    </div>            
                </div> 

                <p>Código Input padrão com erro:</p>
                <div class='control-group'> 
                    <textarea class="span10" rows="6" cols="12" disabled="disable">
&lt;div class='control-group error'&gt; 
    &lt;label class='control-label' for=''&gt;Titulo do Input&lt;/label&gt;
    &lt;div class='controls controls-row'&gt; 
        &lt;input type='text' name='' id=''value=""/&gt;                     
    &lt;/div&gt;            
&lt;/div&gt; 
                    </textarea>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="row-fluid">
    <div class="span10">
        <div class="row-fluid" id='checkbox'>
            <div class="span10">
                <h4>Checkbox</h4>
                <div class="row-fluid">                    
                    <div class="span4">
                        <label class="checkbox" for="">
                            <input type='checkbox' name='nome' value='valor' > Titulo do Checkbox
                        </label>
                    </div>   


                </div>

                <p>Código checkbox padrão:</p>
                <div class='control-group'> 
                    <textarea class="span10" rows="6" cols="12" disabled="disable">
    &lt;div class="span4"&gt;
        &lt;label class="checkbox" for=""&gt;
            &lt;input type='checkbox' name='nome' value='valor' &gt; Titulo do Checkbox
        &lt;/label&gt;
    &lt;/div&gt;
                    </textarea>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="row-fluid">
    <div class="span10">
        <div class="row-fluid" id='radios'>
            <div class="span10">
                <h4>Radio Button</h4>
                <div class="row-fluid">                    
                    <label class="radio">
                        <input type="radio" name="optionsRadios" id="op1" value="option1" checked>
                        Opção 1
                    </label>
                    <label class="radio">
                        <input type="radio" name="optionsRadios" id="op2" value="option2">
                        Opção 2
                    </label>


                </div>

                <p>Código Radios padrão:</p>
                <div class='control-group'> 
                    <textarea class="span10" rows="6" cols="12" disabled="disable">
    &lt;label class="radio"
        &lt;input type="radio" name="optionsRadios" id="optionsRadios1" value="option1" checked&gt;
        Opção 1
    &lt;/label&gt;

    &lt;label class="radio"
        &lt;input type="radio" name="optionsRadios" id="optionsRadios1" value="option2" checked&gt;
        Opção 2
    &lt;/label&gt;
                    </textarea>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="row-fluid">
        <div class="span10">
            <h4>Tamanhos Diferenciados</h4>
            <p>O Span1 do Bootstrap não é pequeno o suficiente, fica um campo grande para pouca informação, então foi criado um padrão da 
                Telecontrol, onde pode saer utilizado para esses casos,</p>
            <p>Segue abaixo as classes disponíveis</p>
            <table class="table table-striped table-bordered table-hover table-fixed">
                    <thead>
                        <tr class="titulo_coluna">
                            <th>Classe</th>
                            <th>Tamanho</th>
                            <th>Exemplo</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td><code>.inptc1</code></td>
                            <td>15px</td>
                            <td><input class="inptc1" type="text"/></td>
                        </tr>
                        <tr>
                            <td><code>.inptc2</code></td>
                            <td>20px</td>
                            <td><input class="inptc2" type="text"/></td>
                        </tr>
                        <tr>
                            <td><code>.inptc3</code></td>
                            <td>25px</td>
                            <td><input class="inptc3" type="text"/></td>
                        </tr>
                        <tr>
                            <td><code>.inptc4</code></td>
                            <td>30px</td>
                            <td><input class="inptc4" type="text"/></td>
                        </tr>
                        <tr>
                            <td><code>.inptc5</code></td>
                            <td>35px</td>
                            <td><input class="inptc5" type="text"/></td>
                        </tr>
                        <tr>
                            <td><code>.inptc6</code></td>
                            <td>70px</td>
                            <td><input class="inptc6" type="text"/></td>
                        </tr>
                    </tbody>
            </table>
        </div>
    </div>    
    

</div>
<script type="text/javascript">
    $.datepickerLoad(Array("data_inicial"));

    $("#maskTest").mask("99/999/9999");
</script>

