<?php

namespace mvc_framework\core\doc_parser;


class Parser {
	private $type;

	public function __construct($type) {
		$this->type = $type;
	}

	/**
	 * @return mixed
	 * @throws \Exception
	 */
	public function parse() {
		if(file_exists(__DIR__.'/'.ucfirst($this->type).'Parser.php')) {
			require_once __DIR__.'/'.ucfirst($this->type).'Parser.php';
			$parser_class = '\mvc_framework\core\doc_parser\\'.ucfirst($this->type).'Parser';
			/**
			 * @var Parser $parser;
			 */
			$parser = new $parser_class();
			return $parser->parse();
		}
		else {
			throw new \Exception($this->type.' parser not expected !');
		}
	}
}