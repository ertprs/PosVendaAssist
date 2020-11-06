<div class="container-fluid">
    <div class="row-fluid">    
        <div class="span10">
            <h3>Layout</h3>
            <h4>Limite Horizontal da p�gina</h4>
            <p>Sempre que houver limita��o de tamanho da p�gina, coloque o conteudo dentro de uma div com a classe <code>"container"</code></p>
            <blockquote>
                <code>
                    &lt;div class="container" &gt; Conte�do  &lt;/div&gt;
                </code>
            </blockquote>
            <h4>Layout em grade para formul�rios</h4>
            <p>Na montagem dos formul�rios deve seguir o <b>layout em grade</b> com div's, gerando linhas e colunas</p>
            <p>Essa estrutura pode ser montada utilizando as classes <code>"row"</code> ou <code>"row-fluid"</code> para linhas e <code>"span2"</code> at� <code>span12</code> para as colunas, sendo que cada n�mero define um tamanho para a coluna.</p>
            <p>Na montagem dos formul�rios coloque sempre uma margem lateral com tamanho 2, em ambos lados.</p>
             <p>   Abaixo um exemplo de layout para formul�rios:</p>
            <p><span class="label label-info">Info</span> A soma das colunas deve ser de 12, caso passar disso o layout pode ficar incorreto.</p> 
        </div>
    </div>
    <div class="row-fluid">
        <div class="span10">
            <div class='linha row-fluid'>
                <div class='span2' >
                    <p class="text-center">Margem</p>
                    <code>span2</code>
                </div>
                <div class='span4' >
                    <p class="text-center">Input</p>
                    <code>span4</code>
                </div>
                <div class='span4' >
                    <p class="text-center">Input</p>
                    <code>span4</code>
                </div>
                <div class='span2' >
                    <p class="text-center">Margem</p>
                    <code>span2</code>
                </div>
            </div>
            <div class='linha row-fluid'>
                <div class='span2' >
                    <p class="text-center">Margem</p>
                    <code>span2</code>
                </div>
                <div class='span2' >
                    <p class="text-center">Input</p>
                    <code>span2</code>
                </div>
                <div class='span2' >
                    <p class="text-center">Input</p>
                    <code>span2</code>
                </div>
                <div class='span2' >
                    <p class="text-center">Input</p>
                    <code>span2</code>
                </div>
                <div class='span2' >
                    <p class="text-center">Input</p>
                    <code>span2</code>
                </div>
                <div class='span2' >
                    <p class="text-center">Margem</p>
                    <code>span2</code>
                </div>
            </div>
            <div class='linha row-fluid'>
                <div class='span2' >
                    <p class="text-center">Margem</p>
                    <code>span2</code>
                </div>
                <div class='span6' >
                    <p class="text-center">Input</p>
                    <code>span6</code>
                </div>            
                <div class='span2' >
                    <p class="text-center">Input</p>
                    <code>span2</code>
                </div>
                <div class='span2' >
                    <p class="text-center">Margem</p>
                    <code>span2</code>
                </div>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span10">
            <p>Abaixo um exemplo de layout:</p>
            <div class="control-group">    

                <textarea class="span10" rows="6" cols="12" disabled="disable">
                &lt;div class='row-fluid'&gt;
                    &lt;div class='span2'&gt;
                        &lt;p class="text-center"&gt;Span2&lt;/p&gt;
                    &lt;/div&gt;
                    &lt;div class='span4' &gt;
                        &lt;p class="text-center"&gt;Input&lt;/p&gt;
                    &lt;/div&gt;
                    &lt;div class='span4' &gt;
                        &lt;p class="text-center"&gt;Input&lt;/p&gt;
                    &lt;/div&gt;
                    &lt;div class='span2' &gt;
                        &lt;p class="text-center"&gt;Span2&lt;/p&gt;
                    &lt;/div&gt;
                &lt;/div&gt;
                </textarea>

            </div>
        </div>
    </div>


    <div class="row-fluid">
        <div class="span10">
            <h4>Titulo nas p�ginas de cadastro</h4>
            <p>Nas p�ginas de cadastros, quando <b>incluir</b> colocar o titulo como <code>"cadastro"</code> e quando <b>alterar</b> o titulo como <code>"altera��o de cadastro"</code><p>                       
        </div>
    </div>
</div>
