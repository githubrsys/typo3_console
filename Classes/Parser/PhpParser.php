<?php
namespace Helhum\Typo3Console\Parser;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class PhpParser
 */
class PhpParser implements PhpParserInterface {

	/**
	 * @param string $classFile Path to PHP class file
	 * @return ParsedClass
	 * @throws ParsingException
	 */
	public function parseClassFile($classFile) {
		if (!file_exists($classFile)) {
			throw new ParsingException('Class File does not exist', 1399284080);
		}
		try {
			return $this->parseClass(file_get_contents($classFile));
		} catch (ParsingException $e) {
			throw new ParsingException($e->getMessage() . ' File: ' . $classFile, 1399291432);
		}
	}

	/**
	 * @param string $classContent
	 * @return ParsedClass
	 * @throws ParsingException
	 */
	public function parseClass($classContent) {
		$parsedClass = new ParsedClass();

		$parsedClass->setNamespace($this->parseNamespace($classContent));
		$parsedClass->setClassName($this->parseClassName($classContent));
		$parsedClass->setInterface($this->isInterface($classContent));
		$parsedClass->setAbstract($this->isAbstract($classContent));

		if ($this->parseNamespace($classContent) === '') {
			$parsedClass->setNamespaceSeparator('');
		} else {
			$parsedClass->setNamespaceSeparator($this->parseNamespaceRaw($classContent) ? '\\' : '_');
		}

		return $parsedClass;
	}

	/**
	 * @param string $classContent
	 * @return bool
	 * @throws ParsingException
	 */
	protected function parseClassName($classContent) {
		$className = $this->parseClassNameRaw($classContent);
		if (!$this->parseNamespaceRaw($classContent)) {
			$classParts = explode('_', $className);
			$className = array_pop($classParts);
		}

		return $className;
	}

	/**
	 * @param string $classContent
	 * @return string
	 */
	protected function parseNamespace($classContent) {
		$phpNamespace = $this->parseNamespaceRaw($classContent);
		if (!$phpNamespace) {
			$className = $this->parseClassNameRaw($classContent);
			$classParts = explode('_', $className);
			array_pop($classParts);
			$namespace = implode('_', $classParts);
		}

		return isset($namespace) ? $namespace : $phpNamespace;
	}

	/**
	 * @param string $classContent
	 * @return string
	 * @throws ParsingException
	 */
	protected function parseClassNameRaw($classContent) {
		preg_match('/^\\s*(abstract)*\\s*(class|interface) ([a-zA-Z_\x7f-\xff][a-zA-Z0-9\\\\_\x7f-\xff]*)/ims', $classContent, $matches);
		if (!isset($matches[2])) {
			throw new ParsingException('Class file does not contain a class or interface definition', 1399285302);
		}
		return $matches[3];
	}

	/**
	 * @param string $classContent
	 * @return string
	 */
	protected function isInterface($classContent) {
		preg_match('/^\\s*interface ([a-zA-Z_\x7f-\xff][a-zA-Z0-9\\\\_\x7f-\xff]*)/ims', $classContent, $matches);
		return isset($matches[1]);
	}

	/**
	 * @param string $classContent
	 * @return string
	 */
	protected function isAbstract($classContent) {
		preg_match('/^\\s*(abstract)*\\s*(class|interface) ([a-zA-Z_\x7f-\xff][a-zA-Z0-9\\\\_\x7f-\xff]*)/ims', $classContent, $matches);
		return isset($matches[1]) && trim($matches[1]) === 'abstract';
	}

	/**
	 * @param string $classContent
	 * @return bool|string
	 */
	protected function parseNamespaceRaw($classContent) {
		preg_match('/^\\s*namespace ([^ ;]*)/ims', $classContent, $matches);
		return isset($matches[1]) ? trim($matches[1]) : FALSE;
	}
}