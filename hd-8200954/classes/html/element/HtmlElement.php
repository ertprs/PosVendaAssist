<?php

namespace html\element;

interface HtmlElement{

	public function setAttribute($name,$value);

	public function setContent($content);

	public function mergeAttributes(Array $attributes);

	public function render();

};