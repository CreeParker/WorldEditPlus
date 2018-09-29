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

namespace WorldEditPlus;

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
			WorldEditPlus::$instance->getLogger()->warning('言語ファイル「' . $lang . '.ini」が見つかりませんでした。');

		$fall_path .= 'resources/languages/' . self::FALLBACK_LANGUAGE . '.ini';

		if(! $this->loadLanguage($fall_path, self::$fall_language))
			WorldEditPlus::$instance->getLogger()->error('システムの言語ファイル「' . self::FALLBACK_LANGUAGE . '.ini」が見つかりませんでした。');

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
	public static function get(string $text, ...$params) : string {

		$message = self::$language[$text] ?? self::$fall_language[$text] ?? 'No Message.';

		foreach($params as $key => $value)
			$message = str_replace('{%' . $key . '}', $value, $message);

		return $message;
	}

}