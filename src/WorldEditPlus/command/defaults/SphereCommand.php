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
use WorldEditPlus\processing\SphereProcessing;
use WorldEditPlus\Language;
use WorldEditPlus\WorldEditPlus;

use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;

class SphereCommand extends WorldEditPlusCommand {

	/**
	 * @param WorldEditPlus $owner
	 */
	public function __construct(WorldEditPlus $owner) {
		parent::__construct('sphere', $owner);
		$this->setUsage('command.sphere.usage');
		$this->setDescription('command.sphere.description');
		$this->setPermission('worldeditplus.command.sphere');
	}

	/**
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, array $args) : bool {
		if(isset($args[0])) {
			if(! isset($args[7]))
				return false;
			if($this->checkNumber($args[0], $args[1], $args[2], $args[3], $args[4], $args[5])) {
				$level = ($sender instanceof Player) ? $sender->getLevel() : Server::getInstance()->getDefaultLevel();
				$pos1 = new Position($args[0], $args[1], $args[2], $level);
				$pos2 = new Position($args[3], $args[4], $args[5], $level);
				$sphere = new SphereProcessing($sender, $pos1, $pos2, $args[6]);
				$sphere->start();
			}else{
				$sender->sendMessage(TextFormat::RED . Language::get('command.intval.error'));
			}
		}elseif($sender instanceof Player){
			$callable = function($player, $data) {
				if(! isset($data))
					return;
				if($this->checkNumber($data[0], $data[1], $data[2], $data[3], $data[4], $data[5])) {
					$level_pos1 = ($player->wep_pos1 ?? $player)->getLevel();
					$level_pos2 = ($player->wep_pos2 ?? $player)->getLevel();
					$pos1 = new Position($data[0], $data[1], $data[2], $level_pos1);
					$pos2 = new Position($data[3], $data[4], $data[5], $level_pos2);
					$sphere = new SphereProcessing($player, $pos1, $pos2, $data[6]);
					$sphere->start();
				}else{
					$player->sendMessage(TextFormat::RED . Language::get('command.intval.error'));
				}
			};
			$form = $this->getDefaultForm($callable, $sender);
			if($form === null)
				return false;
			$form->addInput(Language::get('form.block.one'), 'string');
			$form->sendToPlayer($sender);
		}else{
			return false;
		}
		return true;
	}

}