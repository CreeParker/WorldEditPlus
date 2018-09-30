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
use WorldEditPlus\{
	EventListener,
	Language,
	WorldEditPlus
};
use WorldEditPlus\processing\WorldEditPlusProcessing;

use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\{
	Enchantment,
	EnchantmentInstance
};
use pocketmine\item\{
	Item,
	ItemIds,
};
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
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
		if(! isset($args[0]))
			return false;
		$number = str_replace('#', '', $args[0]);
		$scheduler = WorldEditPlusProcessing::$scheduler[$number] ?? null;
		if($scheduler === null) {
			$sender->sendMessage(TextFormat::RED . Language::get('command.scheduler.cancel.error'));
			return true;
		}
		$scheduler->cancel();
		unset($sender->wep_scheduler);
		$name = $sender->getName();
		Server::getInstance()->broadcastMessage(Language::get('command.scheduler.cancel', $number, $name));
		return true;
	}

}