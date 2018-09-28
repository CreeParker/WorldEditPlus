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

use WorldEditPlus\WorldEditPlus;
use WorldEditPlus\Language;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\scheduler\Task;

class RangeProcessing {

	/** @var int */
	public $x1;
	public $y1;
	public $z1;
	/** @var int */
	public $x2;
	public $y2;
	public $z2;
	/** @var int */
	public $minX;
	public $minY;
	public $minZ;
	/** @var int */
	public $maxX;
	public $maxY;
	public $maxZ;
	/** @var int */
	public $sideX;
	public $sideY;
	public $sideZ;
	/** @var int */
	public $nextX;
	public $nextY;
	public $nextZ;
	/** @var Level */
	public $level_pos1;
	public $level_pos2;

	/**
	 * @param Position $pos1
	 * @param Position $pos2
	 */
	public function __construct(Position $pos1, Position $pos2) {
		$this->x1 = $this->floor($pos1->x);
		$this->y1 = $this->floor($pos1->y);
		$this->z1 = $this->floor($pos1->z);
		$this->x2 = $this->floor($pos2->x);
		$this->y2 = $this->floor($pos2->y);
		$this->z2 = $this->floor($pos2->z);
		$this->minX = min($this->x1, $this->x2);
		$this->minY = min($this->y1, $this->y2);
		$this->minZ = min($this->z1, $this->z2);
		$this->maxX = max($this->x1, $this->x2);
		$this->maxY = max($this->y1, $this->y2);
		$this->maxZ = max($this->z1, $this->z2);
		$this->sideX = $this->getSide($this->maxX, $this->minX);
		$this->sideY = $this->getSide($this->maxY, $this->minY);
		$this->sideZ = $this->getSide($this->maxZ, $this->minZ);
		$this->nextX = $this->getNext($this->x1, $this->x2);
		$this->nextY = $this->getNext($this->y1, $this->y2);
		$this->nextZ = $this->getNext($this->z1, $this->z2);
		$this->level1 = $pos1->getLevel();
		$this->level2 = $pos2->getLevel();
	}

	/**
	 * @param int|float|string $value
	 *
	 * @return int
	 */
	public function floor($value) : int {
		return (int) floor((string) $value);
	}

	/**
	 * @param int $max
	 * @param int $min
	 *
	 * @return int
	 */
	public function getLength(int $max, int $min) : int {
		return ($max - $min) + 1;
	}

	/**
	 * @param int $value1
	 * @param int $value2
	 *
	 * @return int
	 */
	public function getNext(int $value1, int $value2) : int {
		return $value1 < $value2 ? 1 : -1;
	}

}