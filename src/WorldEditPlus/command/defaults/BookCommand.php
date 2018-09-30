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
use WorldEditPlus\Language;
use WorldEditPlus\WorldEditPlus;

use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\{
	Enchantment,
	EnchantmentInstance
};
use pocketmine\item\{
	Item,
	ItemIds,
};
use pocketmine\utils\TextFormat;
use pocketmine\Player;

class BookCommand extends WorldEditPlusCommand {

	/**
	 * @param WorldEditPlus $owner
	 */
	public function __construct(WorldEditPlus $owner) {
		parent::__construct('book', $owner);
		$this->setUsage('command.book.usage');
		$this->setDescription('command.book.description');
		$this->setPermission('worldeditplus.command.book');
	}

	/**
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, array $args) : bool {
		if($sender instanceof Player){
			$item = Item::get(ItemIds::BOOK);
			$enchant = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION));
			$item->addEnchantment($enchant);
			$item->setCustomName(Language::get('book.name'));
			$item->setLore([Language::get('book.description')]);
			$sender->getInventory()->addItem($item);
			$sender->sendMessage(Language::get('command.book'));
		}else{
			$sender->sendMessage(TextFormat::RED . Language::get('command.console.error'));
		}
		return true;
	}

}