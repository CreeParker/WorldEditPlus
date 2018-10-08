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
use WorldEditPlus\processing\Processing;
use WorldEditPlus\Language;
use WorldEditPlus\WorldEditPlus;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\Server;

class CancelCommand extends WorldEditPlusCommand {

	/**
	 * @param WorldEditPlus $owner
	 */
	public function __construct(WorldEditPlus $owner) {
		parent::__construct('cancel', $owner);
		$this->setUsage('command.cancel.usage');
		$this->setDescription('command.cancel.description');
		$this->setPermission('worldeditplus.command.cancel');
	}

	/**
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, array $args) : bool {
		if (! isset($args[0]))
			return false;
		$number = str_replace('#', '', $args[0]);
		$scheduler = Processing::$scheduler[$number] ?? null;
		if ($scheduler !== null) {
			$scheduler->cancel();
			$name = $sender->getName();
			Server::getInstance()->broadcastMessage(Language::get('command.cancel', $number, $name));
		} else {
			$sender->sendMessage(TextFormat::RED . Language::get('command.cancel.error'));
		}
		return true;
	}

}