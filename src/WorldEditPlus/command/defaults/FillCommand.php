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
use WorldEditPlus\processing\defaults\FillProcessing;
use WorldEditPlus\Language;
use WorldEditPlus\WorldEditPlus;

use pocketmine\command\CommandSender;
use pocketmine\world\Position;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;
use pocketmine\Server;

class FillCommand extends WorldEditPlusCommand {

	/**
	 * @param WorldEditPlus $owner
	 */
	public function __construct(WorldEditPlus $owner) {
		parent::__construct('fill', $owner);
		$this->setUsage('command.fill.usage');
		$this->setDescription('command.fill.description');
		$this->setPermission('worldeditplus.command.fill');
	}

	/**
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, array $args) : bool {
		if (isset($args[0])) {
			if (! isset($args[6]))
				return false;
			if ($this->checkNumber($args[0], $args[1], $args[2], $args[3], $args[4], $args[5])) {
				$level = ($sender instanceof Player) ? $sender->getWorld() : Server::getInstance()->getWorldManager()->getDefaultWorld();
				$pos1 = new Position($args[0], $args[1], $args[2], $level);
				$pos2 = new Position($args[3], $args[4], $args[5], $level);
				$args[7] = $args[7] ?? FillProcessing::OPTION[0];
				$args[8] = $args[8] ?? '';
				$fill = new FillProcessing($sender, $pos1, $pos2, $args[6], $args[7], $args[8]);
				$fill->start();
			} else {
				$sender->sendMessage(TextFormat::RED . Language::get('command.intval.error'));
			}
		} elseif ($sender instanceof Player) {
			$callable = function($player, $data) {
				if (! isset($data))
					return;
				if ($this->checkNumber($data[0], $data[1], $data[2], $data[3], $data[4], $data[5])) {
					$name = strtolower($player->getName());
					$level_pos1 = (WorldEditPlus::$pos1[$name] ?? $player)->getWorld();
					$level_pos2 = (WorldEditPlus::$pos2[$name] ?? $player)->getWorld();
					$pos1 = new Position($data[0], $data[1], $data[2], $level_pos1);
					$pos2 = new Position($data[3], $data[4], $data[5], $level_pos2);
					$fill = new FillProcessing($player, $pos1, $pos2, $data[6], FillProcessing::OPTION[$data[7]], $data[8]);
					$fill->start();
				} else {
					$player->sendMessage(TextFormat::RED . Language::get('command.intval.error'));
				}
			};
			$form = $this->getDefaultForm($callable, $sender);
			if ($form === null)
				return false;
			$form->addInput(Language::get('form.block.one'), 'string');
			$form->addDropdown(
				Language::get('form.option') . TextFormat::EOL.
				Language::get('form.option.set') . TextFormat::EOL.
				Language::get('form.option.outline') . TextFormat::EOL.
				Language::get('form.option.hollow') . TextFormat::EOL.
				Language::get('form.option.keep') . TextFormat::EOL.
				Language::get('form.option.replace') 
			, FillProcessing::OPTION);
			$form->addInput(Language::get('form.block.two'), 'string');
			$form->sendToPlayer($sender);
		} else {
			return false;
		}
		return true;
	}

}