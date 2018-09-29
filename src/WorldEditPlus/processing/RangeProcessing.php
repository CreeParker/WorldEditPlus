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
use pocketmine\level\{
	Level,
	Position
};
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\scheduler\Task;

class RangeProcessing {

	/** @var int */
	public $x_pos1;
	public $y_pos1;
	public $z_pos1;
	/** @var int */
	public $x_pos2;
	public $y_pos2;
	public $z_pos2;
	/** @var Level */
	public $level_pos1;
	public $level_pos2;
	/** @var int */
	public $min_x;
	public $min_y;
	public $min_z;
	/** @var int */
	public $max_x;
	public $max_y;
	public $max_z;
	/** @var int */
	public $side_x;
	public $side_y;
	public $side_z;
	/** @var int */
	public $next_x;
	public $next_y;
	public $next_z;

	/**
	 * @param Position $pos1
	 * @param Position $pos2
	 */
	public function __construct(Position $pos1, Position $pos2) {

		$this->pos1_x = $this->changeInteger($pos1->x);
		$this->pos1_y = $this->changeInteger($pos1->y);
		$this->pos1_z = $this->changeInteger($pos1->z);

		$this->pos2_x = $this->changeInteger($pos2->x);
		$this->pos2_y = $this->changeInteger($pos2->y);
		$this->pos2_z = $this->changeInteger($pos2->z);

		$this->pos1_level = $pos1->getLevel();
		$this->pos2_level = $pos2->getLevel();

		$this->min_x = min($this->pos1_x, $this->pos2_x);
		$this->min_y = min($this->pos1_y, $this->pos2_y);
		$this->min_z = min($this->pos1_z, $this->pos2_z);

		$this->max_x = max($this->pos1_x, $this->pos2_x);
		$this->max_y = max($this->pos1_y, $this->pos2_y);
		$this->max_z = max($this->pos1_z, $this->pos2_z);

		$this->side_x = $this->getSide($this->max_x, $this->min_x);
		$this->side_y = $this->getSide($this->max_y, $this->min_y);
		$this->side_z = $this->getSide($this->max_z, $this->min_z);

		$this->next_x = $this->getNext($this->pos1_x, $this->pos2_x);
		$this->next_y = $this->getNext($this->pos1_y, $this->pos2_y);
		$this->next_z = $this->getNext($this->pos1_z, $this->pos2_z);
	}

	/**
	 * @param int|float|string $number
	 *
	 * @return int
	 */
	public function changeInteger($number) : int {
		return (int) floor((string) $number);
	}

	/**
	 * @param int $max
	 * @param int $min
	 *
	 * @return int
	 */
	public function getSide(int $max, int $min) : int {
		return ($max - $min) + 1;
	}

	/**
	 * @param int $start
	 * @param int $end
	 *
	 * @return int
	 */
	public function getNext(int $start, int $end) : int {
		return ($start < $end) ? 1 : -1;
	}

	/**
	 * @return int
	 */
	public function getSize() : int {
		return $this->side_x * $this->side_y * $this->side_z;
	}

	/**
	 * @return Level
	 */
	public function getLevel() : Level {
		return $this->pos1_level;
	}

	/**
	 * @return bool
	 */
	public function isLevel() : bool {
		return $this->pos1_level == $this->pos2_level;
	}

}