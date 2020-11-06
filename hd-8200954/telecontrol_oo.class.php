<?

include_once("javascript_calendario.php");

class grupo {

	var
	$campos,				//Array com campos que compõe o grupo
	$dados_campos,			//Referência para o array externo que contém definições dos campos
	$header_labels,			//Array para adicionar labels no início do grupo
	$html_before,			//HTML que deve ser colocado no início do grupo
	$html_after,			//HTML que deve ser colocado no fim do grupo
	$label					//Label para o grupo
	;
	
	//Ao colocar o "&" antes do parâmetro faz com que a variável seja referenciada e não copiada por inteiro, economizando memória
	function __construct($id, &$dados_campos, $label = "") {
		$this->id = $id;
		$this->label = $label;
		$this->dados_campos =& $dados_campos;
	}
	
	function add_field($tabela, $campo, $id="") {
		$n = count($this->campos)/2;
		$id = $id == "" ? $id = $campo : $id;
		
		extract($this->dados_campos[$tabela][$campo]);
		$tipo = $tipo == "" ? $tipo = "texto" : $tipo = $tipo;
		
		//Instancia objetos
		switch($tipo) {
			case 'ajax_upload':
				$this->campos[$n] = new ajax_upload($id, $label);
			break;
			
			case 'texto':
				$this->campos[$n] = new input_text($id, $label);
				if ($max_length > 0) $this->campos[$n]->set_maxlength($max_length);
				elseif ($tamanho > 0) $this->campos[$n]->set_maxlength($tamanho);
				
				if ($tipo_dados == 'int' && $tamanho > 0 && strlen($mascara) == 0) {
					$mascara = "?" . str_pad('', $tamanho, '9');
				}
			break;
			
			case 'textarea':
				$this->campos[$n] = new textarea($id, $label);
			break;
			
			case 'radio':
				$this->campos[$n] = new input_radio($id, $label);
			break;
			
			case 'select':
				$this->campos[$n] = new select($id, $label);
			break;
		}
		
		//Define máscaras para input
		switch($tipo_dados) {
			case 'date':
				if (strlen($mascara) == 0) {
					$mascara = "99/99/9999";
				}
				
				if ($bloqueia_edicao != 1) {
					echo "
					<script language='javascript'>
					$().ready(function(){
						$('#{$id}').datePicker({startDate : '01/01/2000'});
					});
					</script>
					";
				}
				$this->campos[$n]->add_css_class("div_text_date", "div");
				$this->campos[$n]->add_css_class("input_text_date", "input");
			break;
			
			case 'float':
				echo "
				<script language='javascript'>
				$().ready(function(){
					$('#{$id}').numeric({ decimal: ',', negative: false });
				});
				</script>
				";
			break;
		}
		
		if (strlen($mascara) > 0) {
			echo "
			<script language='javascript'>
			$().ready(function(){
				$( '#{$id}' ).maskedinput('$mascara');
			});
			</script>
			";
		}
		
		if ($autocomplete == 1) {
			$this->campos[$n]->add_css_class("autocomplete");
			$this->campos[$n]->set_autocomplete_values($valor_id, $valor_last);
			
			$autocomplete_function = isset($autocomplete_function) ? $autocomplete_function : 1;
			
			if ($autocomplete_function == 1) {
				echo "
				<script language='javascript'>
				$().ready(function(){
					$('#{$id}').focus(function(){
						if (!$('#{$id}').attr('readonly')) {
							$('#{$id}').autocomplete('os_cadastro_unico_autocomplete.php?tipo={$campo}{$autocomplete_url_params}', {
								minChars: 3,
								delay: 150,
								width: 350,
								matchContains: true,
								formatItem: function(row) {return row[1] + ' - ' + row[2];},
								formatResult: function(row) {return row[0];}
							});

							$('#{$id}').result(function(event, data, formatted) {
								$('#{$id}_id').val(data[0]) ;
								$('#{$id}').val(data[1] + ' - ' + data[2]) ;
								$('#{$id}_last').val($('#{$id}').val()) ;
							});
						}
					});
				});
				</script>
				";
			}
		}
		
		if ($obrigatorio == 1) $this->campos[$n]->set_required();
		if (strlen($label) > 0) $this->campos[$n]->set_label($label);
		if (strlen($ajuda) > 0) $this->campos[$n]->set_ajuda($ajuda);
		if ($bloqueia_edicao == 1 && strlen($valor) > 0) {
			$this->campos[$n]->set_read_only();
			$this->campos[$n]->add_css_class("bloqueado", "input");
		}
		$this->campos[$n]->set_value($valor);
		
		$this->campos[$id] =& $this->campos[$n];
	}
	
	function add_element(&$element) {
		$n = count($this->campos)/2;
		$this->campos[$n] =& $element;
		$this->campos[$element->id] =& $element;
	}
	
	function set_header_labels($header_labels) {
		$this->header_labels = $header_labels;
	}
	
	function set_html_before($html_before) {
		$this->html_before = $html_before;
	}
	
	function set_html_after($html_after) {
		$this->html_after = $html_after;
	}
	
	function draw() {
		$n = count($this->campos)/2;
		
		echo "<div id='{$this->id}' class='div_grupo'><label class='label_grupo label_{$this->id}'>{$this->label}</label>";
		
		echo $this->html_before;
		
		if (count($this->header_labels) > 0) {
			echo "<div id='{$this->id}_header_labels'>";
			
			foreach($this->header_labels as $index => $value) {
				echo "<label id='{$this->id}_header_label_{$index}' class='header_label'>{$value}</label>";
			}
			
			echo "</div>";
		}
		
		for($i = 0; $i < $n; $i++) {
			$this->campos[$i]->draw();
		}
		
		echo $this->html_after;
		
		echo "</div>";
	}

}

class input {

	var
	$ajuda,					//Texto para auxiliar o usuário
	$attr,					//Atributos adicionais em html do input
	$css_classes,			//Array com classes adicionais
	$html_sufix,			//Html a ser adicionado depois do input, dentro da div
	$id,					//Atributo "id" dos inputs
	$label,					//Label do campo
	$name,					//Atributo "name" dos inputs, por padrão é igual a thia->id
	$value,					//Valor atual do objeto
	$read_only,				//Define se o campo será readonly ou não (true ou false), padrão false
	$required				//Obrigatoriedade ou não de um campo (true ou false), padrão false
	;

	function __construct($id, $label) {
		if ($label == '') $label = ucfirst(str_replace('_', ' ', $id));
		
		$this->label = $label;
		$this->id = $id;
		$this->name = $id;
		$this->required = false;
		$this->css_classes = array();
		$this->read_only = false;
	}
	
	function set_attr($attr) {
		$this->attr = $attr;
	}
	
	function add_css_class($class, $position="div") {
		$this->css_classes[$position][] = $class;
	}
	
	function get_classes_string($position) {
		$classes = is_array($this->css_classes[$position]) ? implode(" ", $this->css_classes[$position]) : "";
		return($classes);
	}
	
	function set_ajuda($ajuda) {
		$this->ajuda = $ajuda;
	}
	
	function set_html_sufix($html_sufix) {
		$this->html_sufix .= $html_sufix;
	}
	
	function set_label($label) {
		$this->label = $label;
	}
	
	function set_read_only() {
		$this->read_only = true;
	}
	
	function set_required() {
		$this->required = true;
	}
	
	function set_value($value) {
		$this->value = $value;
	}
	
}

class textarea extends input {

	var
	$cols,					//Colunas
	$rows					//Linhas
	;
	
	function __construct($id, $label='') {
		parent::__construct($id, $label);
	}
	
	function set_cols($cols) {
		$this->cols = $cols;
	}
	
	function set_rows($rows) {
		$this->rows = $rows;
	}
	
	function draw() {
		$cols = intval($this->cols) > 0 ? $cols = "cols='{$this->cols}'" : "";
		$rows = intval($this->rows) > 0 ? $rows = "rows='{$this->rows}'" : "";
		$required = strlen($this->required) > 0 ? "obrigatorio" : "";
		$div_classes = $this->get_classes_string("div");
		$input_classes = $this->get_classes_string("input");
		$label = strlen($this->label) > 0 ? $label = "<label class='$required'>{$this->label}</label>" : "";
		$read_only = $this->read_only ? "readonly='readonly'" : "";
		
		echo "<div id='div_{$this->id}' class='div_campo div_textarea $required $div_classes'>";
		echo "{$label}<textarea id='{$this->id}' name='{$this->name}' class='$input_classes' $rows $cols $read_only {$this->attr}>{$this->value}</textarea><label class='ajuda'>{$this->ajuda}</label>";
		echo $this->html_sufix;
		echo "</div>";
	}
}

class input_hidden extends input {
	function __construct($id, $label='', $value='') {
		parent::__construct($id, $label);
		$this->value = $value;
	}
	
	function draw() {
		$required = strlen($this->required) > 0 ? "obrigatorio" : "";
		$div_classes = $this->get_classes_string("div");
		$input_classes = $this->get_classes_string("input");
		
		echo "<div id='div_{$this->id}' class='div_campo div_input_hidden $required $div_classes'>";
		echo "<input type='hidden' id='{$this->id}' name='{$this->name}' value='{$this->value}' class='$input_classes' $maxlength $size {$this->attr} />";
		echo $this->html_sufix;
		echo "</div>";
	}
}

class input_text extends input {

	var
	$autocomplete,			//Diz se o campo tem autocomplete ou não, padrão false
	$color_picker,			//Define se o campo deve ter o plugin color picker (https://github.com/meta100/mColorPicker), padrão false
	$maxlength,				//Se preenchido define o atributo maxlength do input
	$size,					//Atributo "size" dos inputs
	$value_id,				//Atributo para preencher o hidden "id" para autocomplete
	$value_last				//Atributo para preencher o hidden "last" para autocomplete
	;
	
	function __construct($id, $label='') {
		parent::__construct($id, $label);
		
		$this->autocomplete = false;
		$this->color_picker = false;
	}
	
	function enable_autocomplete() {
		$this->autocomplete = true;
	}
	
	function enable_color_picker() {
		$this->color_picker = true;
	}
	
	function set_maxlength($maxlength) {
		$this->maxlength = $maxlength;
	}
	
	function set_size($size) {
		$this->size = $size;
	}
	
	function set_autocomplete_values($id, $last) {
		$this->value_id = $id;
		$this->value_last = $last;
		$this->enable_autocomplete();
	}
	
	function draw() {
		$maxlength = intval($this->maxlength) > 0 ? "maxlength='{$this->maxlength}'" : "";
		$size = intval($this->size) > 0 ? $this->size : (intval($this->maxlength) > 0 ? $this->maxlength : 0);
		$size = $size > 50 ? 50 : $size;
		$size = intval($size) > 0 ? "size='{$size}'" : "";
		$required = strlen($this->required) > 0 ? "obrigatorio" : "";
		$div_classes = $this->get_classes_string("div");
		$input_classes = $this->get_classes_string("input");
		$label = strlen($this->label) > 0 ? $label = "<label class='$required'>{$this->label}</label>" : "";
		$read_only = $this->read_only ? "readonly='readonly'" : "";
		$type = $this->color_picker ? "color" : "text";
		
		if ($this->autocomplete) {
			$autocomplete = "
		<input type='hidden' id='{$this->id}_id' name='{$this->id}_id' value='{$this->value_id}' />
		<input type='hidden' id='{$this->id}_last' name='{$this->id}_last' value='{$this->value_last}' />";
		}
		else {
			$autocomplete = "";
		}

		echo "<div id='div_{$this->id}' class='div_campo div_input_text $required $div_classes'>";
		echo "{$label}<input type='{$type}' id='{$this->id}' name='{$this->name}' value='{$this->value}' class='$input_classes' $maxlength $size $read_only {$this->attr} /><label class='ajuda'>{$this->ajuda}</label>";
		echo $this->html_sufix;
		echo $autocomplete;
		echo "</div>";
	}

}

class select extends input {
	
	var
	$options						//Array com as opções do combo
	;
	
	function __construct($id, $label='') {
		parent::__construct($id, $label);
		$this->options = array();
	}
	
	function add_option($value, $label) {
		$this->options[$value] = $label;
	}
	
	function draw() {
		$required = strlen($this->required) > 0 ? "obrigatorio" : "";
		$div_classes = $this->get_classes_string("div");
		$input_classes = $this->get_classes_string("input");
		$label = strlen($this->label) > 0 ? $label = "<label class='$required'>{$this->label}</label>" : "";
		
		echo "<div id='div_{$this->id}' class='div_campo div_select $required $div_classes'>";
		echo "{$label}<select id='{$this->id}' name='{$this->name}' class='$input_classes'>";
		foreach($this->options as $value => $label) {
			$selected = $this->value == $value ? "selected" : "";
			
			if ($this->read_only == true && $selected == "selected" || $this->read_only == false) {
				echo "<option value='{$value}' $selected>{$label}</option>";
			}
		}
		echo "</select>";
		echo $this->html_sufix;
		echo "</div>";
	}
}

class input_radio extends input {

	var
	$options						//Array com as opções do radio
	;
	
	function __construct($id, $label='') {
		parent::__construct($id, $label);
		$this->options = array();
	}
	
	function add_option($value, $label) {
		$this->options[$value] = $label;
	}
	
	function draw() {
		$required = strlen($this->required) > 0 ? "obrigatorio" : "";
		$div_classes = $this->get_classes_string("div");
		$input_classes = $this->get_classes_string("input");
		$label = strlen($this->label) > 0 ? $label = "<label class='$required'>{$this->label}</label>" : "";
		
		echo "<div id='div_{$this->id}' class='div_campo div_input_radio $required $div_classes'>";
		echo "{$label}";
		foreach($this->options as $value => $label) {
			$checked = $this->value == $value ? "checked" : "";
			echo "<input type='radio' id='{$this->id}' name='{$this->name}' value='{$value}' class='$input_classes' $checked />{$label} ";
		}
		echo $this->html_sufix;
		echo "</div>";
	}

}

class ajax_upload extends input {
	function draw() {
		$required = strlen($this->required) > 0 ? "obrigatorio" : "";
		$div_classes = $this->get_classes_string("div");
		$input_classes = $this->get_classes_string("input");
		$label = strlen($this->label) > 0 ? $label = "<label class='$required'>{$this->label}</label>" : "";
		$read_only = $this->read_only ? "readonly='readonly'" : "";
		
		echo "<div id='div_{$this->id}' class='div_campo div_ajax_upload $required $div_classes'>";
		echo "{$label}<div id='{$this->id}' name='{$this->name}' class='ajax_upload $input_classes' {$this->attr}></div><label class='ajuda'>{$this->ajuda}</label>";
		echo "<div id='img_{$this->id}' class='div_ajax_upload_miniatura' style='background-image: url({$this->value});'></div>";
		echo "<input type='hidden' id='{$this->id}' name='{$this->name}' value='{$this->value}' />";
		echo $this->html_sufix;
		echo "<input type='button' name='limpar_imagem_{$this->id}' id='limpar_imagem_{$this->id}' value='Limpar Imagem' class='limpar_ajax_upload' />";
		echo "</div>";
	}
}


//////////////////////// FUNÇÕES AUXILIARES

//Esta função recebe o nome do campo no POST e valida se foi preenchido corretamente
function valida_campo_autocomplete($campo, $label='') {
	global $campos_telecontrol;
	global $login_fabrica;
	global $_POST;
	
	if (strlen($label) == 0) {
		$label = $campos_telecontrol[$login_fabrica]['tbl_os'][$campo]['label'];
	}
	
	$valor = $_POST[$campo];
	$valor_id = $_POST["{$campo}_id"];
	$valor_last = $_POST["{$campo}_last"];
	
	if ((strlen($valor) > 0 && ($valor != $valor_last || strlen($valor_id) == 0 || strlen($valor_last) == 0))) {
		return "No campo {$label} você deve começar a digitar um valor e selecionar um item da lista";
	}
	
	return true;
}

?>