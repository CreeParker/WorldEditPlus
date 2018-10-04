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

class SphereProcessing extends Processing {

	public const NAME = '/sphere';

	public function __construct(CommandSender $sender, Position $pos1, Position $pos2, string $block) {
		parent::__construct($sender, $pos1, $pos2);
		$this->block = $this->fromString($block);
		#$this->filled = false;
	}

	public function onCheck(CommandSender $sender) : bool {
		if ($this->block === null) {
			$sender->sendMessage(TextFormat::RED . Language::get('processing.block.one.error'));
			return false;
		}
		return true;
	}

	public function onRun() : iterable {
		$this->startMessage(self::NAME);
		$radius_x = ($this->side_x - 1) / 2;
		$radius_y = ($this->side_y - 1) / 2;
		$radius_z = ($this->side_z - 1) / 2;

		$center_x = $this->pos1_x + ($radius_x * $this->next_x);
		$center_y = $this->pos1_y + ($radius_y * $this->next_y);
		$center_z = $this->pos1_z + ($radius_z * $this->next_z);

		$vector3 = new Vector3($center_x, $center_y, $center_z);

        $ceil_radius_x = (int) ceil($radius_x += 0.5);
        $ceil_radius_y = (int) ceil($radius_y += 0.5);
        $ceil_radius_z = (int) ceil($radius_z += 0.5);

        $inv_radius_x = 1 / $radius_x;
        $inv_radius_y = 1 / $radius_y;
        $inv_radius_z = 1 / $radius_z;

        $next_x = 0;
		$break_x = false;

		for($x = 0; $x <= $ceil_radius_x and $break_x === false; ++$x) {

			$xn = $next_x;
			$next_x = ($x + 1) * $inv_radius_x;

			$next_y = 0;
			$break_y = false;

			for($y = 0; $y <= $ceil_radius_y and $break_y === false; ++$y) {

				$yn = $next_y;
				$next_y = ($y + 1) * $inv_radius_y;

				$next_z = 0;

				for($z = 0; $z <= $ceil_radius_z; ++$z) {

					$zn = $next_z;
					$next_z = ($z + 1) * $inv_radius_z;

					if ($this->hasBlockRestriction(8))
						yield false;

					$distanceSq = $this->lengthSq($xn, $yn, $zn);

					if($distanceSq > 1){
						if($z === 0){
							if($y === 0){
								$break_x = true;
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

					$this->level->setBlock($vector3->add($x, $y, $z), $this->set(), false, false);
					$this->level->setBlock($vector3->add(-$x, $y, $z), $this->set(), false, false);
					$this->level->setBlock($vector3->add($x, -$y, $z), $this->set(), false, false);
					$this->level->setBlock($vector3->add($x, $y, -$z), $this->set(), false, false);
					$this->level->setBlock($vector3->add(-$x, -$y, $z), $this->set(), false, false);
					$this->level->setBlock($vector3->add($x, -$y, -$z), $this->set(), false, false);
					$this->level->setBlock($vector3->add(-$x, $y, -$z), $this->set(), false, false);
					$this->level->setBlock($vector3->add(-$x, -$y, -$z), $this->set(), false, false);
					
				}
			}
		}

		$this->endMessage(self::NAME);
		yield true;
	}

	public function set($xn = null, $yn = null, $zn = null, $next_x = null, $next_y = null, $next_z = null, $block = null) : Block {
		$rand = array_rand($this->block);
		return $this->block[$rand];
	}

	public static function lengthSq($x, $y, $z){
		return ($x ** 2) + ($y ** 2) + ($z ** 2);
	}

}