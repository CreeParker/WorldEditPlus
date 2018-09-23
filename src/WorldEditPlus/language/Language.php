<?php

/** 
 * Copyright (c) 2018 CreeParker
 * 
 * <English>
 * This plugin is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 *
 * <日本語>
 * このプラグインは、MITライセンスのもとで公開されています。
 * http://opensource.org/licenses/mit-license.php
 */

declare(strict_types = 1);

namespace WorldEditPlus\language;

use pocketmine\utils\MainLogger\BaseLang;

class Language {

	private const DEFAULT_LANGUAGE = 'DefaultLanguage.ini';

	/** @var array */
	private static $language = [];

	/** @var array */
	private static $default = [];

	/**
	 * @param string $lang
	 * @param string $path
	 * @param string $default = DEFAULT_LANGUAGE
	 */
	public function __construct(string $lang, string $path, string $default = DEFAULT_LANGUAGE) {

		$path .= 'language/';

		if(! file_exists($path))
			mkdir($path);

		if(! $this->loadLanguage($path . $lang . '.ini', self::language))
			MainLogger::getLogger()->error("言語ファイル「{$lang}.ini」が見つかりませんでした。");

		$path = dirname(__FILE__) . '/' . $default;

		if(! $this->loadLanguage($path, self::default))
			MainLogger::getLogger()->error("システムの言語ファイル「{$default}」が見つかりませんでした。");

	}

	/**
	 * @param string $path
	 * @param array &$language
	 *
	 *@return bool
	 */
	private function loadLanguage(string $path, array &$language) : bool {
		if(! file_exists($path))
			return false;
		$decode = parse_ini_file($path, false, INI_SCANNER_RAW);
		$language = array_map('stripcslashes', $decode);
		return true;
	}

	/**
	 * @param string $text
	 * @param array $params
	 *
	 * @return string
	 */
	public static function getMessage(string $text, array $params = []) : string {

		$message = self::$language[$text] ?? self::$default[$text] ?? '';

		foreach($params as $key => $value)
			$message = str_replace("{%$i}", $value, $message);

		return $message;
	}

}