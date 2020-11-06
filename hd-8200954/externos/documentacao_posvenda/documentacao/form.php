
<div class="container-fluid">
<div class="row-fluid">
    <div class="span10">
        <h3>Gerador de Formulário</h3>
        <div class="row-fluid">
            <div class="span10">
                <h4>Forma de uso</h4>
                <p>O gerador de formulário funciona com um array de entrada levando consigo os atributos de cada elemento.</p>
                <p>Por padrão a função recebe dois arrays não obrigatórios <code>$inputs</code> e <code>$hiddens</code></p>
                
                <h5>Hiddens</h5>
                <p>Para os inputs do tipo hidden é necessário 2 chaves no array, seu <b>nome</b> e seu <b>value</b>, sendo que seu nome será utilizado como <b>id</b></p>
                <textarea class="span10" rows="6" cols="12" disabled="disable">
    //simples
    $hiddens = array('hidden1','hidden2','hidden3','hidden4');

    //com values
    $hiddens = array('hidden1' => array("values" => "ES"),'hidden2' => array("values" => "DS"));
                </textarea>
                
                <h5>Inputs</h5>
                <p>Para inputs é necessário um array com as opções</p>
                <textarea class="span10" rows="3" cols="12" disabled="disable">    
    $inputs = array('inp1' => $opcoes1,'inp2' => $opcoes2);    
                </textarea>
                    
                <h5>Opções</h5>
                <ul>
                    <li><code>$key</code> - será o id e name do campo, todo name de checkbox será array name[]</li>
                    <li><code>$config</code> - array de configuração do campo, pode conter as seguintes configurações</li>
                    <ul>
                        <li><b>span</b> - Espaço ocupado em tela pelo campo (1 a 12)</li>
                        <li>label</b> - texto que irá aparecer no elemento &lt;label&gt; do campo</li>
                        <li><b>type</b> - tipo do campo, input/(types do elemento input), select, option, checkbox, radio</li>
			<li><b>width</b> - tamanho do input (1 a 12)</li>
			<li><b>inptc</b> - tamanho do input que utiliza a class inptc que é uma class para tamanhos especificos (1 a 12) substitui o tamanho normal</li>
			<li><b>required</b> - se for true irá colocar o * na frente do campo</li>
			<li><b>maxlength</b> - coloca o valor para o atributo maxlength</li>
			<li><b>readonly</b> - se for true coloca o atributo readonly no campo</li>
			<li><b>class</b> - classes adicionais para o campo deve ser uma string</li>
			<li><b>title</b> - atributo title do elemento </li>
                    </ul>
                    
                    <ul>
                        <li><i>Específico para selects</i></li>
                        <li><b>options</b> - array que armazena os options do select a key será o value do option e o valor será o texto do option</li>
                    </ul>
                    
                    <ul>
                        <li><i>Específico para checkbox</i></li>
                        <li><b>checks</b> - array que armazena os checkboxs desta familia de checkbox a key será o value do checkbox e o valor será o label do checkbox</li>
                    </ul>
                    
                    <ul>
                        <li><i>Especí­fico para radios</i></li>
                        <li><b>radios</b> - array que armazena os radios desta familia de radio a key será o value do radio e o valor será o label do radio</li>
                    </ul>
                    <ul>
                        
                        <li><b>icon-append</b> - adiciona um icone ou texto no formato de icone ao final do campo
			deve ser um array, a key deve ser text ou icon, se for text o valor pode ser o que desejar
			se for icon deve olhar o nome do icon na pagina de icones na doc</li>
                        <li><b>icon-prepend</b> - adiciona um icone ou texto no formato de icone no inicio do campo
			deve ser um array, a key deve ser text ou icon, se for text o valor pode ser o que desejar
			se for icon deve olhar o nome do icon na pagina de icones na doc</li>
                        <li><b>extra</b> - array de configuração de atributos extras, a key sera o nome do atributo e o value o valor do atributo</li>
                    </ul>
                    <li><code>"lupa"</code> - monta html da lupa no campo, deve ser uma array com as seguintes configurações</li>
                    <ul>
                        <li><b>name</b>   - nome da lupa irá no rel do span do icone da lupa</li>
                        <li><b>tipo</b>   - define pelo o que quer pesquisar (produto, posto, peça)</li>
                        <li><b>parametro</b> -> define pelo o que esta pequisando (referencia, nome, cpf)</li>
                        <li><b>extra</b>  - parametros extras da lupa deve ser uma array a key sera o nome do atributo extra e o value do valor do atributo extra</li>                            
                    </ul>
                </ul>
            </div>
        </div>
    </div>
</div>
</div>