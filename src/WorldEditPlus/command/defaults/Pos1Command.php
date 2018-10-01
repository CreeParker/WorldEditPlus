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

namespace WorldEditPlus\command\defaults;

use WorldEditPlus\command\WorldEditPlusCommand;
use WorldEditPlus\EventListener;
use WorldEditPlus\Language;
use WorldEditPlus\WorldEditPlus;

use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

class Pos1Command extends WorldEditPlusCommand {

	/**
	 * @param WorldEditPlus $owner
	 */
	public function __construct(WorldEditPlus $owner) {
		parent::__construct('pos1', $owner);
		$this->setUsage('command.pos1.usage');
		$this->setDescription('command.pos1.description');
		$this->setPermission('worldeditplus.command.pos1');
	}

	/**
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, array $args) : bool {
		if (! $sender instanceof Player) {
			$sender->sendMessage(TextFormat::RED . Language::get('command.console.error'));
		} elseif (isset($args[0])) {
			if (! isset($args[2]))
				return false;
			if ($this->checkNumber($args[0], $args[1], $args[2])) {
				$level = $sender->getLevel();
				$pos = new Position($args[0], $args[1], $args[2], $level);
				EventListener::setWandPosition($sender, $pos, true);
			} else {
				$sender->sendMessage(TextFormat::RED . Language::get('command.intval.error'));
			}
		} else {
			EventListener::setWandPosition($sender, $sender, true);
		}
		return true;
	}

}