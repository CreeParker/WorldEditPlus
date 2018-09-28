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

abstract class WorldEditPlusProcessing extends RangeProcessing {

	/** @var array */
	private static $message = [];

	private static $count = 0;

	/** @var CommandSender */
	public $sender;

	/** @var float */
	public $meter;
	/** @var floatl */
	public $gage = 0;

	/** @var int */
	public $restriction = 0;

	/** @var int */
	private $id;

	/**
	 * @param Position $pos1
	 * @param Position $pos2
	 * @param CommandSender $sender
	 */
	public function __construct(Position $pos1, Position $pos2, CommandSender $sender) {
		parent::__construct($pos1, $pos2);
		$this->sender = $sender;
		$this->id = self::$count++;
	}

	abstract public function onCheck() : bool;

	abstract public function onRun() : Iterable;

	public function start() : void {
		if(! $this->isLevel()) {
			$this->sender->sendMessage(TextFormat::RED . Language::get('processing.level.error'));
			return;
		}
		$task = new class($this) extends Task {

			public function __construct(WorldEditPlusProcessing $owner) {
				$this->generator = $owner->onRun();
			}

			public function onRun(int $tick) {
				if($this->generator->current())
					$this->getHandler()->cancel();
				else
					$this->generator->next();
			}

		};
		$this->sender->task = WorldEditPlus::$instance->getScheduler()->scheduleRepeatingTask($task, 1);
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
		if(++$this->restriction >= 1000) {
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

	public function sendTip(string $message) : void {
		$id = $this->id;
		self::$message[$id] = $message;
		foreach(self::$message as $value)
			$list = isset($list) ? $list . PHP_EOL . $value : $value;
		Server::getInstance()->broadcastTip($list);
		var_dump($list);
	}

}