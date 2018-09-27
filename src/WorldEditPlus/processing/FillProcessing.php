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
use pocketmine\level\{Level, Position};
use pocketmine\command\CommandSender;
use WorldEditPlus\Language;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\Server;

class FillProcessing extends WorldEditPlusProcessing {

	public const OPTION = ['set', 'outline', 'hollow', 'keep', 'replace'];

	public function __construct(CommandSender $sender, Position $pos1, Position $pos2, string $block, string $option = self::OPTION[0], string $replace = '') {
		$this->block = $this->fromString($block);
		$this->option = $option;
		$this->replace = $this->fromString($replace);
		parent::__construct($sender, $pos1, $pos2);
	}

	public function calculation() : bool {
		if($this->block === null) {
			$this->sender->sendMessage(TextFormat::RED . Language::get('processing.block.one.error'));
			return false;
		}
		if(! in_array($this->option, self::OPTION)) {
			$this->sender->sendMessage(TextFormat::RED . Language::get('processing.fill.option.error'));
			return false;
		}
		if($this->option === self::OPTION[4]) {
			if($this->replace === null) {
				$this->sender->sendMessage(TextFormat::RED . Language::get('processing.block.two.error'));
				return false;
			}
		}
		$this->setMeter($this->sideX);
		return true;
	}

	public function onRun() {
		$name = $this->sender->getName();
		Server::getInstance()->broadcastMessage($name.'が/fillを実行しました (§e'. $this->sideX * $this->sideY * $this->sideZ .'ブロック§r)');
		$option = $this->option;
		for($a = 0; abs($a) < $this->sideX; $a += $this->nextX) {

			$x = $this->x1 + $a;

			for($b = 0; abs($b) < $this->sideY; $b += $this->nextY) {

				$y = $this->y1 + $b;
				if($y < 0 or $y > Level::Y_MAX)
					continue;

				for($c = 0; abs($c) < $this->sideZ; $c += $this->nextZ){

					$z = $this->z1 + $c;
					if(! $this->level1->isChunkLoaded($x >> 4, $z >> 4))
						$this->level1->loadChunk($x >> 4, $z >> 4, true);
					$vector3 = new Vector3($x, $y, $z);
					$old_block = $this->level1->getBlock($vector3);
					$new_block = $this->$option($old_block);
					if($new_block !== null)
						$this->level1->setBlock($vector3, $new_block, true, false);
					if($this->hasBlockRestriction())
						yield false;
				}
			}
			#$this->addMeter();
		}
		Server::getInstance()->broadcastMessage($name.'の/fillが終了しました');
		yield true;
	}

	public function set(Block $block) : Block {
		$rand = array_rand($this->block);
		return $this->block[$rand];
	}

	public function outline(Block $block) : ?Block {
		$x = $block->x;
		$y = $block->y;
		$z = $block->z;
		if($x != $this->minX and $x != $this->maxX)
			if($y != $this->minY and $y != $this->maxY)
				if($z != $this->minZ and $z != $this->maxZ)
					return null;
		return $this->set();
	}

	public function hollow(Block $block) : Block {
		$x = $block->x;
		$y = $block->y;
		$z = $block->z;
		if($x != $this->minX and $x != $this->maxX)
			if($y != $this->minY and $y != $this->maxY)
				if($z != $this->minZ and $z != $this->maxZ)
					return $this->air;
		return $this->set();
	}

	public function keep(Block $block) : ?Block {
		return (string) $block === (string) $this->air ? $this->set() : null;
	}

	public function replace(Block $block) : ?Block {
		return isset($this->replace[(string) $block]) ? $this->set() : null;
	}

}