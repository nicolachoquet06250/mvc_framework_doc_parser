<?php

namespace mvc_framework\core\doc_parser;


use mvc_framework\core\paths\Path;

class ScssParser {
	private $doc = [],
		$inline = ['author', 'date', 'title'];

	public function parse() {
		$scss_source = Path::get('core_doc-parser_parsers-conf_sass');
		$this->parse_part1($scss_source);
		$this->parse_part2();
		$this->parse_part3();
		$this->parse_part4();
		$this->parse_part5();
		return $this->doc;
	}

	private function parse_part1($scss_source) {
		$dir = opendir($scss_source);
		while (($file = readdir($dir)) !== false) {
			if($file !== '.' && $file !== '..') {
				$content = file_get_contents($scss_source.'/'.$file);
				$path_to_kipe = explode('/', $scss_source);
				$path_to_kipe = [
					$path_to_kipe[count($path_to_kipe)-3],
					$path_to_kipe[count($path_to_kipe)-2],
					$path_to_kipe[count($path_to_kipe)-1],
				];
				$this->doc[implode('/', $path_to_kipe).'/'.$file] = $content;
			}
		}
	}
	private function parse_part2() {
		foreach ($this->doc as $id => $doc) {
			preg_match('`\/[\*]{2,2}\n([^\*]+)\*\/`', $doc, $matches);
			if(!empty($matches)) {
				$doc_content = $matches[1];
				$doc_content = explode("\n@", $doc_content);
				$this->doc[$id] = $doc_content;
			}
			else {
				unset($this->doc[$id]);
			}
		}
	}
	private function parse_part3() {
		foreach ($this->doc as $id => $doc) {
			foreach ($doc as $_id => $doc_part) {
				if(substr($doc_part, 0, 1) === '@') {
					$this->doc[$id][$_id] = substr($doc_part, 1, strlen($doc_part)-1);
				}
			}
		}
	}
	private function parse_part4() {
		foreach ($this->doc as $id => $doc) {
			$doc_tmp = [];
			foreach ($doc as $_id => $doc_part) {
				if(in_array(explode(' ', $doc_part)[0], $this->inline)) {
					$key = explode(' ', $doc_part)[0];
					$content = explode(' ', $doc_part);
					unset($content[0]);
					$content_tmp = [];
					foreach ($content as $c) {
						if($c !== '' && $c !== null) {
							$content_tmp[] = $c;
						}
					}
					$content = $content_tmp;
					$doc_tmp[$key] = str_replace("\n", '', implode(' ', $content));
				}
				else {
					$key = explode("\n", $doc_part)[0];
					$content = explode("\n", $doc_part);
					unset($content[0]);
					$content_tmp = [];
					foreach ($content as $c) {
						if($c !== '' && $c !== null) {
							$content_tmp[] = $c;
						}
					}
					$content = $content_tmp;
					$doc_tmp[$key] = implode("\n", $content);
				}
			}
			if(!empty($doc_tmp)) {
				$this->doc[$id] = $doc_tmp;
			}
		}
	}
	private function parse_part5() {
		foreach ($this->doc as $id => $doc) {
			if(isset($doc['modifier']) && $doc['modifiers'] !== null) {
				$modifiers     = $doc['modifiers'];
				$modifiers     = explode("\n", $modifiers);
				$modifiers_tmp = [];
				foreach ($modifiers as $modifier) {
					$modifier                    = explode(' - ', $modifier);
					$modifiers_tmp[$modifier[0]] = $modifier[1];
				}
				$modifiers                   = $modifiers_tmp;
				$this->doc[$id]['modifiers'] = $modifiers;
			}
			else {
				unset($this->doc[$id]['modifiers']);
			}
		}
	}
}