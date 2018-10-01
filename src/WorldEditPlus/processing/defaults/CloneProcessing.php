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
use pocketmine\Server;

class CloneProcessing extends Processing {

	public const NAME = '/clone';

	public const MASK = ['replace', 'filtered', 'masked'];
	public const CLONE = ['normal', 'force', 'move'];

	public function __construct(CommandSender $sender, Position $pos1, Position $pos2, Position $pos3, string $mask, string $clone, string $replace) {
		parent::__construct($sender, $pos1, $pos2);
		$this->pos3 = $pos3;
		$this->mask = $mask;
		$this->clone = $clone;
		$this->replace = $this->fromString($replace);
		$this->setMeter($this->side_x);
	}

	public function onCheck(CommandSender $sender) : bool {
		if (! in_array($this->mask, self::MASK)) {
			$sender->sendMessage(TextFormat::RED . Language::get('processing.clone.mask.error'));
			return false;
		} elseif (! in_array($this->clone, self::CLONE)) {
			$sender->sendMessage(TextFormat::RED . Language::get('processing.clone.clone.error'));
			return false;
		} elseif ($this->mask === self::MASK[1] and $this->replace === null) {
			$sender->sendMessage(TextFormat::RED . Language::get('processing.block.one.error'));
			return false;
		}
		return true;
	}

	public function onRun() : iterable {
		$this->startMessage(self::NAME);

		$pos3_x = parent::changeInteger($this->pos3->x);
		$pos3_y = parent::changeInteger($this->pos3->y);
		$pos3_z = parent::changeInteger($this->pos3->z);

		$new_level = $this->pos3->getLevel();

		$this->normal = $this->level == $new_level;

		$mask = $this->mask;
		$clone = $this->clone;

		for($a = 0; abs($a) < $this->side_x; $a += $this->next_x) {
			$old_x = $this->pos1_x + $a;
			$new_x = $pos3_x + $a;
			for($b = 0; abs($b) < $this->side_y; $b += $this->next_y) {
				$old_y = $this->pos1_y + $b;
				$new_y = $pos3_y + $b;
				if($this->hasHeightLimit($old_y) or $this->hasHeightLimit($new_y))
					break;
				for($c = 0; abs($c) < $this->side_z; $c += $this->next_z){
					$old_z = $this->pos1_z + $c;
					$new_z = $pos3_z + $c;

					if($this->hasBlockRestriction())
						yield false;

					$this->checkChunkLoaded($this->level, $old_x, $old_z);
					$this->checkChunkLoaded($new_level, $new_x, $new_z);

					$old_vector3 = new Vector3($old_x, $old_y, $old_z);
					$old_block = $this->level->getBlock($old_vector3);
					if(! $this->$mask($old_block))
						continue;
					$new_vector3 = new Vector3($new_x, $new_y, $new_z);
					if($this->$clone($old_vector3, $new_vector3))
						$new_level->setBlock($new_vector3, $old_block, false, false);
				}
			}
			$this->addMeter();
		}
		$this->endMessage(self::NAME);
		yield true;
	}

	public function replace(Block $block) : bool {
		return true;
	}

	public function filtered(Block $block) : bool {
		return isset($this->replace[(string) $block]);
	}

	public function masked(Block $block) : bool {
		return (string) $block !== (string) $this->air;
	}

	public function normal(Vector3 $old, Vector3 $new) : bool {
		if($this->normal) {
			$x = $new->x;
			$y = $new->y;
			$z = $new->z;
			if($x >= $this->min_x and $x <= $this->max_x)
				if($y >= $this->min_y and $y <= $this->max_y)
					if($z >= $this->min_z and $z <= $this->max_z)
						return false;
		}
		return true;
	}

	public function force(Vector3 $old, Vector3 $new) : bool {
		return true;
	}

	public function move(Vector3 $old, Vector3 $new) : bool {
		return $this->level->setBlock($old, $this->air, false, false);
	}

}