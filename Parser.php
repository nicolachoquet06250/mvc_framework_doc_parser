<?php

namespace mvc_framework\core\doc_parser;


class DocParser {
	private $type;

	public function __construct($type) {
		$this->type = $type;
	}

	public function parse() {
		if(file_exists(__DIR__.'/'.ucfirst($this->type).'.php')) {
			require_once __DIR__.'/'.ucfirst($this->type).'.php';
			$parser_class = '\mvc_framework\core\doc_parser\\'.ucfirst($this->type);
			$parser = new $parser_class();
		}
		else {
			throw new \Exception($this->type.' parser not expected !');
		}
	}
}