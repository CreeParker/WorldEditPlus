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

		$pos1_x = 'pos1_' . $copy[0];
		$pos1_y = 'pos1_' . $copy[1];
		$pos1_z = 'pos1_' . $copy[2];

		$side_x = 'side_' . $copy[0];
		$side_y = 'side_' . $copy[1];
		$side_z = 'side_' . $copy[2];

		$next_x = 'next_' . $copy[0];
		$next_y = 'next_' . $copy[1];
		$next_z = 'next_' . $copy[2];

		$radius_y = ($this->$side_y - 1) / 2;
		$radius_z = ($this->$side_z - 1) / 2;

		$center_y = $this->$pos1_y + ($radius_y * $this->$next_y);
		$center_z = $this->$pos1_z + ($radius_z * $this->$next_z);

		for ($a = 0; $a < 360; $a += 0.01) {

			$radian = deg2rad($a);

			$x = round($center_y + ($radius_y * sin($radian)));
			$z = round($center_z + ($radius_z * cos($radian)));

			if (isset($cylinder[$x][$z]))
				continue;

			$cylinder[$x][$z] = $z;

		}

		for ($a = 0; abs($a) < $this->$side_x; $a += $this->$next_x) {

			$x = $this->$pos1_x + $a;

			foreach ($cylinder as $y => $value) {

				foreach ($value as $z) {

					if ($copy[0] === self::DIRECTION[0])
						$vector3 = new Vector3($x, $y, $z);
					elseif ($copy[0] === self::DIRECTION[1])
						$vector3 = new Vector3($y, $x, $z);
					elseif ($copy[0] === self::DIRECTION[2])
						$vector3 = new Vector3($y, $z, $x);

					$block = $this->set();
					$this->level->setBlock($vector3, $block, false, false);

					if ($this->hasBlockRestriction())
						yield false;

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

}