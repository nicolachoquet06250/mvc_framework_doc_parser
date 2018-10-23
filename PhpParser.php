<?php

namespace mvc_framework\core\doc_parser;


use mvc_framework\core\paths\Path;

class PhpParser {
	private $doc = [],
		$clean_string_table = ["\n", "\r", "\t", " "];

	/**
	 * @throws \ReflectionException
	 */
	public function parse() {
		$this->parse_part1(Path::get('core_doc-parser_parsers-conf_php'));
		$this->parse_part2();
		$this->parse_part3();
		$this->parse_part4();
		$this->parse_part5();
		$this->parse_part6();
		return $this->doc;
	}

	public function parse_part1($php_source) {
		$dir = opendir($php_source);
		while (($directory = readdir($dir)) !== false) {
			if($directory !== '.' && $directory !== '..' && !strstr($directory, 'cache')) {
				$path_to_kipe = explode('/', $php_source);
				$path_to_kipe = [
					__DIR__.'/../../..',
					$path_to_kipe[count($path_to_kipe)-4],
					$path_to_kipe[count($path_to_kipe)-3],
					$path_to_kipe[count($path_to_kipe)-2],
					$path_to_kipe[count($path_to_kipe)-1],
				];
				$this->doc[realpath(implode('/', $path_to_kipe).'/'.$directory)] = [];
			}
		}
	}

	public function parse_part2() {
		foreach ($this->doc as $path => $_) {
			$dir = opendir($path);
			while (($file = readdir($dir)) !== false) {
				if($file !== '.' && $file !== '..' && !strstr($file, 'cache')) {
					if(isset($this->doc[$path])) {
						unset($this->doc[$path]);
					}
					$this->doc[$path.'/'.$file] = [];
				}
			}
		}
	}

	/**
	 * @throws \ReflectionException
	 */
	public function parse_part3() {
		foreach ($this->doc as $path => $_) {
			require_once $path;
			$content = file_get_contents($path);
			preg_match('`namespace\ ([^;]+)\;[^µ]+class\ ([a-zA-Z\_]+)`', $content, $matches);
			if($matches) {
				$matches[0] = $matches[1];
				$matches[1] = $matches[2];
				unset($matches[2]);
				$class = '\\'.$matches[0].'\\'.$matches[1];
				$reflexion_class = new \ReflectionClass($class);
				$doc_class = $reflexion_class->getDocComment();

				$this->doc[$path] = [
					'class'   => [
						'namespace' => $matches[0],
						'name'      => $matches[1],
					],
				];

				if($doc_class) {
					$this->doc[$path]['class']['doc'] = $doc_class;
				}

				if(($methods = $reflexion_class->getMethods()) && !empty($methods)) {
					$this->doc[$path]['methods'] = [];
					foreach ($methods as $method) {
						if (($doc_method = $method->getDocComment()) !== false && in_array($method->getName(), ['Get', 'Post', 'Put', 'Delete'])) {
							$this->doc[$path]['methods'][$class.'::'.$method->getName().'()'] = $doc_method;
						}
					}
				}

				if(!$doc_class && empty($this->doc[$path]['method'])) {
					unset($this->doc[$path]);
				}
			}
			else {
				preg_match('`function\ ([a-zA-Z\_]+)`', $content, $matches);
				if($matches) {
					$reflexion = new \ReflectionFunction($matches[1]);
					$this->doc[$path] = $reflexion->getDocComment();
				}
				else {
					unset($this->doc[$path]);
				}
			}
		}
	}

	public function parse_part4() {
		foreach ($this->doc as $path => $doc) {
			if(is_array($doc)) {
				if(isset($this->doc[$path]['class']['doc'])) {
					$this->doc[$path]['class']['doc'] = str_replace(["/**\n", "*/", ' * ', ' *'], '', $doc['class']['doc']);
					if(isset($this->doc[$path]['methods'])) {
						foreach ($this->doc[$path]['methods'] as $method => $_doc) {
							$this->doc[$path]['methods'][$method] = str_replace(["/**\n", "*/", "\t * ", "\t *"], '', $_doc);
						}
					}
				}
			}
			else {
				$this->doc[$path] = str_replace(["/**\n", "*/", ' * ', ' *'], '', $doc);
			}
		}
	}

	public function parse_part5() {
		$regex_for_explode_decorator_title_and_content = "`\@([a-zA-Z\-\_]+)[\ ]?([^\@]+)`";
		foreach ($this->doc as $path => $doc) {
			if(is_array($doc)) {
				if(isset($doc['class']['doc']) && $doc['class']['doc'] !== false) {
					preg_match_all($regex_for_explode_decorator_title_and_content, $doc['class']['doc'], $matches, PREG_SET_ORDER, 0);
					if($matches) {
						$doc_tmp = [];
						foreach ($matches as $id => $match) {
							$this->clean_end_string($matches[$id][2]);
							$this->clean_start_string($matches[$id][2]);
							unset($matches[$id][0]);
							if($matches[$id][2] === '') {
								$matches[$id][2] = true;
							}
							$doc_tmp[$matches[$id][1]] = $matches[$id][2];
						}
						$this->doc[$path]['class']['doc'] = $doc_tmp;
					}
				}
				if(isset($doc['methods'])) {
					foreach ($doc['methods'] as $method => $_doc) {
						preg_match_all($regex_for_explode_decorator_title_and_content, $_doc, $matches, PREG_SET_ORDER, 0);
						if($matches) {
							$doc_tmp = [];
							foreach ($matches as $id => $match) {
								$this->clean_start_string($matches[$id][2]);
								$this->clean_end_string($matches[$id][2]);
								unset($matches[$id][0]);
								if($matches[$id][2] === '') {
									$matches[$id][2] = true;
								}
								$doc_tmp[$matches[$id][1]] = $matches[$id][2];
							}
							$this->doc[$path]['methods'][$method] = $doc_tmp;
						}
					}
				}
			}
			else {
				preg_match_all($regex_for_explode_decorator_title_and_content, $doc, $matches, PREG_SET_ORDER, 0);
				if($matches) {
					$doc_tmp = [];
					foreach ($matches as $id => $match) {
						$this->clean_start_string($matches[$id][2]);
						$this->clean_end_string($matches[$id][2]);
						unset($matches[$id][0]);
						if($matches[$id][2] === '') {
							$matches[$id][2] = true;
						}
						$doc_tmp[$matches[$id][1]] = $matches[$id][2];
					}
					$this->doc[$path] = $doc_tmp;
				}
			}
		}
	}

	public function parse_part6() {
		foreach ($this->doc as $path => $doc) {
			if(isset($doc['class']) && isset($doc['class']['doc']) && isset($doc['class']['doc']['code-demo'])) {
				preg_match("`type:([a-zA-Z\_\-]+)\\n`", $doc['class']['doc']['code-demo'], $matches);
				if($matches) {
					$code = str_replace($matches[0], '', $doc['class']['doc']['code-demo']);
					$type = $matches[1];
				}
				else {
					$code = $doc['class']['doc']['code-demo'];
					$type = 'code';
				}
				$this->doc[$path]['class']['doc']['code-demo'] = [
					'type' => $type,
					'code' => $code,
				];
			}
			if(isset($doc['methods'])) {
				foreach ($doc['methods'] as $method => $_doc) {
					if(isset($_doc['code-demo'])) {
						preg_match("`type:([a-zA-Z\_\-]+)\\n`", $_doc['code-demo'], $matches);
						if($matches) {
							$code = str_replace($matches[0], '', $_doc['code-demo']);
							$type = $matches[1];
						}
						else {
							$code = $_doc['code-demo'];
							$type = 'code';
						}
						$this->doc[$path]['methods'][$method]['code-demo'] = [
							'type' => $type,
							'code' => $code,
						];
					}
				}
			}
			elseif (isset($doc['route']) && $doc['route'] === true) {
				if(isset($doc['code-demo'])) {
					preg_match("`type:([a-zA-Z\_\-]+)\\n`", $doc['code-demo'], $matches);
					if($matches) {
						$code = str_replace($matches[0], '', $doc['code-demo']);
						$type = $matches[1];
					}
					else {
						$code = $doc['code-demo'];
						$type = 'code';
					}
					$this->doc[$path]['code-demo'] = [
						'type' => $type,
						'code' => $code,
					];
				}
			}
		}
	}

	private function clean_start_string(&$str) {
		if(in_array(substr($str, 0, 1), $this->clean_string_table)) {
			$str = substr($str, 1, strlen($str));
			$this->clean_start_string($str);
		}
	}

	private function clean_end_string(&$str) {
		if(in_array(substr($str, strlen($str)-1, 1), $this->clean_string_table)) {
			$str = substr($str, 0, strlen($str)-1);
			$this->clean_end_string($str);
		}
	}
}