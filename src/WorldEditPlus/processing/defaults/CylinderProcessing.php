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

class CylinderProcessing extends Processing {

	public const NAME = '/cylinder';

	public const DIRECTION = ['x', 'y', 'z'];

	public function __construct(CommandSender $sender, Position $pos1, Position $pos2, string $block, string $direction) {
		parent::__construct($sender, $pos1, $pos2);
		$this->block = $this->fromString($block);
		$this->direction = $direction;
		#$this->filled = false;
	}

	public function onCheck(CommandSender $sender) : bool {
		if ($this->block === null) {
			$sender->sendMessage(TextFormat::RED . Language::get('processing.block.one.error'));
			return false;
		} elseif (! in_array($this->direction, self::DIRECTION)) {
			$sender->sendMessage(TextFormat::RED . Language::get('processing.cylinder.direction.error'));
			return false;
		}
		return true;
	}

	public function onRun() : iterable {
		$this->startMessage(self::NAME);

		$copy = self::DIRECTION;
		$direction = $this->direction;
		$key = array_search($direction, $copy);
		unset($copy[$key]);
		array_unshift($copy, $direction);

		$direction_x = $copy[0];
		$direction_y = $copy[1];
		$direction_z = $copy[2];

		$pos1_x = 'pos1_' . $direction_x;
		$pos1_y = 'pos1_' . $direction_y;
		$pos1_z = 'pos1_' . $direction_z;

		$side_x = 'side_' . $direction_x;
		$side_y = 'side_' . $direction_y;
		$side_z = 'side_' . $direction_z;

		$next_x = 'next_' . $direction_x;
		$next_y = 'next_' . $direction_y;
		$next_z = 'next_' . $direction_z;

		$radius_y = ($this->$side_y - 1) / 2;
		$radius_z = ($this->$side_z - 1) / 2;

		$center_y = $this->$pos1_y + ($radius_y * $this->$next_y);
		$center_z = $this->$pos1_z + ($radius_z * $this->$next_z);

        $ceil_radius_y = (int) ceil($radius_y += 0.5);
        $ceil_radius_z = (int) ceil($radius_z += 0.5);

        $inv_radius_y = 1 / $radius_y;
        $inv_radius_z = 1 / $radius_z;

		for($a = 0; abs($a) < $this->$side_x; $x += $this->$next_x) {

			$x = $this->$pos1_x + $a;

			if ($direction_x === self::DIRECTION[0])
				$vector3 = new Vector3($x, $center_y, $center_z);
			elseif ($direction_x === self::DIRECTION[1])
				$vector3 = new Vector3($center_y, $x, $center_z);
			elseif ($direction_x === self::DIRECTION[2])
				$vector3 = new Vector3($center_y, $center_z, $x);

			$next_y = 0;
			$break_y = false;

			for($y = 0; $y <= $ceil_radius_y and $break_y === false; ++$y) {

				$yn = $next_y;
				$next_y = ($y + 1) * $inv_radius_y;

				$next_z = 0;

				for($z = 0; $z <= $ceil_radius_z; ++$z) {

					$zn = $next_z;
					$next_z = ($z + 1) * $inv_radius_z;

					if ($this->hasBlockRestriction(4))
						yield false;

					$distanceSq = $this->lengthSq($yn, $zn);

					if($distanceSq > 1){
						if($z === 0){
							if($y === 0){
								$break_y = true;
								break;
							}
							$break_y = true;
							break;
						}
						break;
					}
					
					#if($this->filled === false){						
					#	if($this->lengthSq($next_x, $yn, $zn) <= 1 and $this->lengthSq($xn, $next_y, $zn) <= 1 and $this->lengthSq($xn, $yn, $next_z) <= 1){
					#		continue;
					#	}
					#}
					if ($direction_x === self::DIRECTION[0]){
						$this->level->setBlock($vector3->add(0, $y, $z), $this->set(), false, false);
						$this->level->setBlock($vector3->add(0, $y, -$z), $this->set(), false, false);
						$this->level->setBlock($vector3->add(0, -$y, -$z), $this->set(), false, false);
						$this->level->setBlock($vector3->add(0, -$y, $z), $this->set(), false, false);
					} elseif ($direction_x === self::DIRECTION[1]) {
						$this->level->setBlock($vector3->add($y, 0, $z), $this->set(), false, false);
						$this->level->setBlock($vector3->add($y, 0, -$z), $this->set(), false, false);
						$this->level->setBlock($vector3->add(-$y, 0, -$z), $this->set(), false, false);
						$this->level->setBlock($vector3->add(-$y, 0, $z), $this->set(), false, false);
					} elseif ($direction_x === self::DIRECTION[2]) {
						$this->level->setBlock($vector3->add($y, $z, 0), $this->set(), false, false);
						$this->level->setBlock($vector3->add($y, -$z, 0), $this->set(), false, false);
						$this->level->setBlock($vector3->add(-$y, -$z, 0), $this->set(), false, false);
						$this->level->setBlock($vector3->add(-$y, $z, 0), $this->set(), false, false);
					}
					
				}
			}
		}

		$this->endMessage(self::NAME);
		yield true;
	}

	public function set() : Block {
		$rand = array_rand($this->block);
		return $this->block[$rand];
	}

	public static function lengthSq($y, $z){
		return ($y ** 2) + ($z ** 2);
	}

}