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

use WorldEditPlus\level\Range;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use pocketmine\item\ItemIds;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

class EventListener implements Listener {

	/**
	 * @param BlockBreakEvent $event
	 */
	public function BlockBreakEvent(BlockBreakEvent $event){
		$this->WandEvent($event, true);
	}

	/**
	 * @param BlockBreakEvent|PlayerInteractEvent $event
	 * @param bool $boolean
	 */
	public function WandEvent($event, bool $boolean) : void {
		$player = $event->getPlayer();
		if (! $player->isOp())
			return;
		$item = $event->getItem();
		$id = $item->getId();
		$name = $item->getName();
		if ($id === ItemIds::WOODEN_AXE and $name === Language::get('wand.name')) {
			$event->setCancelled();
			$position = $event->getBlock()->asPosition();
			self::setWandPosition($player, $position, $boolean);
		}
	}

	/**
	 * @param Player $player
	 * @param Position $position
	 * @param bool $boolean
	 */
	public static function setWandPosition(Player $player, Position $position, bool $boolean) : void {
		$branch = $boolean ? 'wep_pos1' : 'wep_pos2';
		$player->$branch = $position;
		if (isset($player->wep_pos1, $player->wep_pos2)) {
			$range = new Range($player->wep_pos1, $player->wep_pos2);
			$size = $range->getSize();
			$message_size = Language::get('wand.size', $size);
		} else {
			$message_size = '';
		}
		$x = TextFormat::RED . Range::changeInteger($position->x) . TextFormat::RESET;
		$y = TextFormat::GREEN . Range::changeInteger($position->y) . TextFormat::RESET;
		$z = TextFormat::AQUA . Range::changeInteger($position->z) . TextFormat::RESET;
		$message = Language::get($boolean ? 'wand.pos1' : 'wand.pos2', $x, $y, $z);
		$player->sendMessage($message . $message_size);
	}

	/**
	 * @param PlayerInteractEvent $event
	 */
	public function PlayerInteractEvent(PlayerInteractEvent $event) : void {
		$action = $event->getAction();
		if ($action !== PlayerInteractEvent::LEFT_CLICK_BLOCK and $action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK)
			return;
		$player = $event->getPlayer();
		if ($this->loopInteractMeasures($player))
			return;
		$this->WandEvent($event, false);
		$item = $event->getItem();
		$id = $item->getId();
		$name = $item->getName();
		if ($id !== ItemIds::BOOK and $name !== Language::get('book.name'))
			return;
		$block = $event->getBlock();
		$name = $block->getName();
		$id = $block->getId();
		$meta = $block->getDamage();
		$x = TextFormat::RED . $block->x . TextFormat::RESET;
		$y = TextFormat::GREEN . $block->y . TextFormat::RESET;
		$z = TextFormat::AQUA . $block->z . TextFormat::RESET;
		$player->sendMessage(Language::get('book.block', $name, $id, $meta, $x, $y, $z));
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function loopInteractMeasures(Player $player) : bool {
		$time = $player->wep_time ?? 0;
		$difference = $time - microtime(true);
		$player->wep_time = microtime(true);
		return $difference > -0.1;
	}

}