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

use pocketmine\utils\MainLogger;

class Language {

	private const FALLBACK_LANGUAGE = 'jpn';

	/** @var array */
	private static $language = [];

	/** @var array */
	private static $fall_language = [];

	/**
	 * @param string $lang
	 * @param string $path
	 * @param string $fall_path
	 */
	public function __construct(string $lang, string $path, string $fall_path) {

		$path .= 'languages/' . $lang . '.ini';

		if(! $this->loadLanguage($path, self::$language))
			MainLogger::getLogger()->error('言語ファイル「' . $lang . '.ini」が見つかりませんでした。');

		$fall_path .= 'resources/languages/' . self::FALLBACK_LANGUAGE . '.ini';

		if(! $this->loadLanguage($fall_path, self::$fall_language))
			MainLogger::getLogger()->error('システムの言語ファイル「' . self::FALLBACK_LANGUAGE . '.ini」が見つかりませんでした。');

	}

	/**
	 * @param string $path
	 * @param array &$language
	 *
	 * @return bool
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
	public static function get(string $text, array $params = []) : string {

		$message = self::$language[$text] ?? self::$fall_language[$text] ?? '';

		foreach($params as $key => $value)
			$message = str_replace('{%' . $key . '}', $value, $message);

		return $message;
	}

}