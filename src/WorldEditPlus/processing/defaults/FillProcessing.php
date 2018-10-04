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

namespace WorldEditPlus\processing\defaults;

use WorldEditPlus\Language;
use WorldEditPlus\processing\Processing;

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

class FillProcessing extends Processing {

	public const NAME = '/fill';

	public const OPTION = ['set', 'outline', 'hollow', 'keep', 'replace'];

	public function __construct(CommandSender $sender, Position $pos1, Position $pos2, string $block, string $option, string $replace) {
		parent::__construct($sender, $pos1, $pos2);
		$this->block = $this->fromString($block);
		$this->option = $option;
		$this->replace = $this->fromString($replace);
		$this->setMeter($this->side_x);
	}

	public function onCheck(CommandSender $sender) : bool {
		if ($this->block === null) {
			$sender->sendMessage(TextFormat::RED . Language::get('processing.block.one.error'));
			return false;
		} elseif (! in_array($this->option, self::OPTION)) {
			$sender->sendMessage(TextFormat::RED . Language::get('processing.fill.option.error'));
			return false;
		} elseif ($this->option === self::OPTION[4] and $this->replace === null) {
			$sender->sendMessage(TextFormat::RED . Language::get('processing.block.two.error'));
			return false;
		}
		return true;
	}

	public function onRun() : iterable {
		$this->startMessage(self::NAME);
		$option = $this->option;
		for ($a = 0; abs($a) < $this->side_x; $a += $this->next_x) {
			$x = $this->pos1_x + $a;
			for ($b = 0; abs($b) < $this->side_y; $b += $this->next_y) {
				$y = $this->pos1_y + $b;
				if ($this->hasHeightLimit($y))
					break;
				for ($c = 0; abs($c) < $this->side_z; $c += $this->next_z){
					$z = $this->pos1_z + $c;
					if ($this->hasBlockRestriction())
						yield false;
					$this->checkChunkLoaded($this->level, $x, $z);
					$vector3 = new Vector3($x, $y, $z);
					$old_block = $this->level->getBlock($vector3);
					$new_block = $this->$option($old_block);
					if ($new_block === null)
						continue;
					if ((string) $old_block === (string) $new_block)
						continue;
					$this->level->setBlock($vector3, $new_block, false, false);
				}
			}
			$this->addMeter();
		}
		$this->endMessage(self::NAME);
		yield true;
	}

	public function set(?Block $block = null) : Block {
		$rand = array_rand($this->block);
		return $this->block[$rand];
	}

	public function outline(Block $block) : ?Block {
		$x = $block->x;
		$y = $block->y;
		$z = $block->z;
		if ($x != $this->min_x and $x != $this->max_x)
			if ($y != $this->min_y and $y != $this->max_y)
				if ($z != $this->min_z and $z != $this->max_z)
					return null;
		return $this->set();
	}

	public function hollow(Block $block) : Block {
		$x = $block->x;
		$y = $block->y;
		$z = $block->z;
		if ($x != $this->min_x and $x != $this->max_x)
			if ($y != $this->min_y and $y != $this->max_y)
				if ($z != $this->min_z and $z != $this->max_z)
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