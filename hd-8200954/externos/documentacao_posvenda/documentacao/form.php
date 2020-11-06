
<div class="container-fluid">
<div class="row-fluid">
    <div class="span10">
        <h3>Gerador de Formul�rio</h3>
        <div class="row-fluid">
            <div class="span10">
                <h4>Forma de uso</h4>
                <p>O gerador de formul�rio funciona com um array de entrada levando consigo os atributos de cada elemento.</p>
                <p>Por padr�o a fun��o recebe dois arrays n�o obrigat�rios <code>$inputs</code> e <code>$hiddens</code></p>
                
                <h5>Hiddens</h5>
                <p>Para os inputs do tipo hidden � necess�rio 2 chaves no array, seu <b>nome</b> e seu <b>value</b>, sendo que seu nome ser� utilizado como <b>id</b></p>
                <textarea class="span10" rows="6" cols="12" disabled="disable">
    //simples
    $hiddens = array('hidden1','hidden2','hidden3','hidden4');

    //com values
    $hiddens = array('hidden1' => array("values" => "ES"),'hidden2' => array("values" => "DS"));
                </textarea>
                
                <h5>Inputs</h5>
                <p>Para inputs � necess�rio um array com as op��es</p>
                <textarea class="span10" rows="3" cols="12" disabled="disable">    
    $inputs = array('inp1' => $opcoes1,'inp2' => $opcoes2);    
                </textarea>
                    
                <h5>Op��es</h5>
                <ul>
                    <li><code>$key</code> - ser� o id e name do campo, todo name de checkbox ser� array name[]</li>
                    <li><code>$config</code> - array de configura��o do campo, pode conter as seguintes configura��es</li>
                    <ul>
                        <li><b>span</b> - Espa�o ocupado em tela pelo campo (1 a 12)</li>
                        <li>label</b> - texto que ir� aparecer no elemento &lt;label&gt; do campo</li>
                        <li><b>type</b> - tipo do campo, input/(types do elemento input), select, option, checkbox, radio</li>
			<li><b>width</b> - tamanho do input (1 a 12)</li>
			<li><b>inptc</b> - tamanho do input que utiliza a class inptc que � uma class para tamanhos especificos (1 a 12) substitui o tamanho normal</li>
			<li><b>required</b> - se for true ir� colocar o * na frente do campo</li>
			<li><b>maxlength</b> - coloca o valor para o atributo maxlength</li>
			<li><b>readonly</b> - se for true coloca o atributo readonly no campo</li>
			<li><b>class</b> - classes adicionais para o campo deve ser uma string</li>
			<li><b>title</b> - atributo title do elemento </li>
                    </ul>
                    
                    <ul>
                        <li><i>Espec�fico para selects</i></li>
                        <li><b>options</b> - array que armazena os options do select a key ser� o value do option e o valor ser� o texto do option</li>
                    </ul>
                    
                    <ul>
                        <li><i>Espec�fico para checkbox</i></li>
                        <li><b>checks</b> - array que armazena os checkboxs desta familia de checkbox a key ser� o value do checkbox e o valor ser� o label do checkbox</li>
                    </ul>
                    
                    <ul>
                        <li><i>Espec�fico para radios</i></li>
                        <li><b>radios</b> - array que armazena os radios desta familia de radio a key ser� o value do radio e o valor ser� o label do radio</li>
                    </ul>
                    <ul>
                        
                        <li><b>icon-append</b> - adiciona um icone ou texto no formato de icone ao final do campo
			deve ser um array, a key deve ser text ou icon, se for text o valor pode ser o que desejar
			se for icon deve olhar o nome do icon na pagina de icones na doc</li>
                        <li><b>icon-prepend</b> - adiciona um icone ou texto no formato de icone no inicio do campo
			deve ser um array, a key deve ser text ou icon, se for text o valor pode ser o que desejar
			se for icon deve olhar o nome do icon na pagina de icones na doc</li>
                        <li><b>extra</b> - array de configura��o de atributos extras, a key sera o nome do atributo e o value o valor do atributo</li>
                    </ul>
                    <li><code>"lupa"</code> - monta html da lupa no campo, deve ser uma array com as seguintes configura��es</li>
                    <ul>
                        <li><b>name</b>   - nome da lupa ir� no rel do span do icone da lupa</li>
                        <li><b>tipo</b>   - define pelo o que quer pesquisar (produto, posto, pe�a)</li>
                        <li><b>parametro</b> -> define pelo o que esta pequisando (referencia, nome, cpf)</li>
                        <li><b>extra</b>  - parametros extras da lupa deve ser uma array a key sera o nome do atributo extra e o value do valor do atributo extra</li>                            
                    </ul>
                </ul>
            </div>
        </div>
    </div>
</div>
</div>