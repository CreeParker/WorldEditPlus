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
use WorldEditPlus\processing\FillProcessing;
use WorldEditPlus\Language;
use WorldEditPlus\WorldEditPlus;

use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
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
		if(isset($args[0])) {
			if(! isset($args[6]))
				return false;
			if($this->checkInteger($args[0], $args[1], $args[2], $args[3], $args[4], $args[5])) {
				$level = ($sender instanceof Player) ? $sender->getLevel() : Server::getInstance()->getDefaultLevel();
				$pos1 = new Position($args[0], $args[1], $args[2], $level);
				$pos2 = new Position($args[3], $args[4], $args[5], $level);
				$args[7] = $args[7] ?? 'set';
				$args[8] = $args[8] ?? '';
				new FillProcessing($sender, $pos1, $pos2, $args[6], $args[7], $args[8]);
			}else{
				$sender->sendMessage(TextFormat::RED . Language::get('command.intval.error'));
			}
		}elseif($sender instanceof Player){
			$callable = function($player, $data) {
				if(! isset($data))
					return;
				if($this->checkInteger($data[0], $data[1], $data[2], $data[3], $data[4], $data[5])){
					$level_pos1 = $player->wep_pos1['level'] ?? $player->getLevel();
					$level_pos2 = $player->wep_pos2['level'] ?? $player->getLevel();
					$pos1 = new Position($data[0], $data[1], $data[2], $level_pos1);
					$pos2 = new Position($data[3], $data[4], $data[5], $level_pos2);
					new FillProcessing($player, $pos1, $pos2, $data[6], FillProcessing::OPTION[$data[7]], $data[8]);
				}else{
					$player->sendMessage(TextFormat::RED . Language::get('command.intval.error'));
				}
			};
			$form = $this->getDefaultForm($callable, $sender);
			if($form === null)
				return false;
			$form->addInput(Language::get('form.block.one'), 'string');
			$form->addDropdown(
				Language::get('form.option') . "\n".
				Language::get('form.option.set') . "\n".
				Language::get('form.option.outline') . "\n".
				Language::get('form.option.hollow') . "\n".
				Language::get('form.option.keep') . "\n".
				Language::get('form.option.replace') 
			, FillProcessing::OPTION);
			$form->addInput(Language::get('form.block.two'), 'string');
			$form->sendToPlayer($sender);
		}else{
			return false;
		}
		return true;
	}

}