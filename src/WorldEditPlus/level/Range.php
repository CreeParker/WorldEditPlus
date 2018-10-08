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

namespace WorldEditPlus\level;

use pocketmine\level\Level;
use pocketmine\level\Position;

class Range {

	/** @var int */
	protected $pos1_x;
	protected $pos1_y;
	protected $pos1_z;

	/** @var int */
	protected $pos2_x;
	protected $pos2_y;
	protected $pos2_z;

	/** @var Level */
	protected $pos1_level;
	protected $pos2_level;

	/** @var int */
	protected $min_x;
	protected $min_y;
	protected $min_z;

	/** @var int */
	protected $max_x;
	protected $max_y;
	protected $max_z;

	/** @var int */
	protected $side_x;
	protected $side_y;
	protected $side_z;
	
	/** @var int */
	protected $next_x;
	protected $next_y;
	protected $next_z;

	/**
	 * @param Position $pos1
	 * @param Position $pos2
	 */
	public function __construct(Position $pos1, Position $pos2) {

		$this->pos1_x = self::changeInteger($pos1->x);
		$this->pos1_y = self::changeInteger($pos1->y);
		$this->pos1_z = self::changeInteger($pos1->z);

		$this->pos2_x = self::changeInteger($pos2->x);
		$this->pos2_y = self::changeInteger($pos2->y);
		$this->pos2_z = self::changeInteger($pos2->z);

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
	public static function changeInteger($number) : int {
		return (int) floor((string) $number);
	}

	/**
	 * @param int $max
	 * @param int $min
	 *
	 * @return int
	 */
	private function getSide(int $max, int $min) : int {
		return ($max - $min) + 1;
	}

	/**
	 * @param int $start
	 * @param int $end
	 *
	 * @return int
	 */
	private function getNext(int $start, int $end) : int {
		return $start < $end ? 1 : -1;
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