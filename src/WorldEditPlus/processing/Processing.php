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

namespace  WorldEditPlus;

use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\Server;

abstract  class WorldEditPlusAPI {

	/** @var CommandSender */
	public $sender;

	/** @var int */
	public $x1;
	/** @var int */
	public $y1;
	/** @var int */
	public $z1;
	/** @var int */
	public $x2;
	/** @var int */
	public $y2;
	/** @var int */
	public $z2;

	/** @var int */
	public $minX;
	/** @var int */
	public $minY;
	/** @var int */
	public $minZ;
	/** @var int */
	public $maxX;
	/** @var int */
	public $maxY;
	/** @var int */
	public $maxZ;

	/** @var int */
	public $sideX;
	/** @var int */
	public $sideY;
	/** @var int */
	public $sideZ;

	/** @var int */
	public $nextX;
	/** @var int */
	public $nextY;
	/** @var int */
	public $nextZ;

	/** @var Level */
	public $level1;
	/** @var Level */
	public $level2;

	/** @var float */
	public $meter;
	/** @var floatl */
	public $gage = 0;

	/** @var int */
	public $restriction = 0;

	abstract public function calculation();

	abstract public function onRun();

	public function start() : void {
		if($this->level1 != $this->level2) {
			$this->sender->sendMessage('始点と終点のワールドが違います');
			return;
		}
		$task = new Class extends Task {

			public function __construct(WorldEditPlusAPI $owner) {
				$this->generator = $owner->onRun();
			}

			public function onRun(int $tick) {
				$stop = $this->generator->next();
				if($stop === true){
					$this->getHandler()->cancel();
					unset($player->wep_task);
				}
			}

		};
		$this->sender->task = Server::getInstance()->getScheduler()->scheduleRepeatingTask($task, 1);
	}

	/**
	 * @param Position $pos1
	 * @param Position $pos2
	 */
	public function __construct(CommandSender $sender, Position $pos1, Position $pos2) {
		$this->sender = $sender;
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
		$this->id = self::$count++;
	}

	/**
	 * 整数化
	 *
	 * @param float|int $value
	 *
	 * @return int
	 */
	public function floor($value) : int {
		return (int) floor((string) $value);
	}

	/**
	 * 辺の長さを取得
	 *
	 * @param int $max
	 * @param int $min
	 *
	 * @return int
	 */
	public function getSide(int $max, int $min) : int {
		return ($max - $min) + 1;
	}

	/**
	 * 領域の進行方向を取得
	 *
	 * @param int $value1
	 * @param int $value2
	 *
	 * @return int
	 */
	public function getNext(int $value1, int $value2) : int {
		return $value1 < $value2 ? 1 : -1;
	}

	/**
	 * 進行ゲージの値を設定
	 *
	 * @param float $value
	 */
	public function setMeter(float $value) : void {
		$this->meter = 100 / $value;
	}

	/**
	 * 進行ゲージを進める
	 */
	public function addMeter() : void {
		$round = round($this->gage += $this->meter); 
		BroadcastTipList::send($this->sender, $round);
	}

	public function hasBlockRestriction() : bool {
		if($this->restriction++ > 1000){
			$this->restriction = 0;
			return true;
		}
		return false;
	}

	/**
	 * ブロックオブジェクトを複数取得
	 *
	 * @param string $string
	 *
	 * @return array|null
	 */
	public function fromString(string $string) : ?array {
		try {
			$items = Item::fromString($string, true);
			foreach($items as $item) {
				$item_name = $item->getName();
				$block = $item->getBlock();
				$block_name = $block->getName();
				if($item_name !== $block_name) return null;
				$blocks[(string) $block] = $block;
			}
			return $blocks;
		}catch(\Exception $e){
			return null;
		}
	}

	/** @var array */
	private static $message = [];

	private static $count = 0;

	/** @var int */
	private $id;

	public function sendTip(string $message) : void {
		$id = $this->id;
		self::$message[$id] = $message;
		foreach(self::$message as $value)
			$list = isset($list) ? $list . PHP_EOL . $value : $value;
		Server::getInstance()->broadcastTip($list);
		var_dump($list);
	}

	public function sendPopup(string $message) : void {
		$id = $this->id;
		self::$message[$id] = $message;
		foreach(self::$message as $value)
			$list = isset($list) ? $list . PHP_EOL . $value : $value;
		Server::getInstance()->broadcastPopup($list);
	}

}