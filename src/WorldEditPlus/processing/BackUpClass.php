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

namespace WorldEditPlus\processing;

use pocketmine\block\Block;
use pocketmine\world\world;

class BackUpClass {

	/** @var World */
	public $level;

	/** @var array */
	public $data = [];

	/**
	 * @param World $level
	 */
	public function __construct(World $level, $owner) {
		$folder = $owner->getDataFolder();
		if(!file_exists($folder)) mkdir($folder);
		$this->db = new \SQLite3($folder.'backup.sqlite3');
		$this->level = $level->getFolderName();
	}

	/**
	 * @param Block $block
	 */
	public function addData(Block $block) : void {
		$this->data[] = [
			'id'      => $block->getId(),
			'meta' => $block->getDamage(),
			'x'       => $block->getPosition()->x,
			'y'       => $block->getPosition()->y,
			'z'       => $block->getPosition()->z
		];
	}

}