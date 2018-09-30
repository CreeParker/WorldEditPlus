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
use WorldEditPlus\processing\{
	CloneProcessing,
	RangeProcessing
};
use WorldEditPlus\Language;
use WorldEditPlus\WorldEditPlus;

use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;

class CloneCommand extends WorldEditPlusCommand {

	/**
	 * @param WorldEditPlus $owner
	 */
	public function __construct(WorldEditPlus $owner) {
		parent::__construct('clone', $owner);
		$this->setUsage('command.clone.usage');
		$this->setDescription('command.clone.description');
		$this->setPermission('worldeditplus.command.clone');
	}

	/**
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, array $args) : bool {
		if(isset($args[0])) {
			if(! isset($args[8]))
				return false;
			if($this->checkNumber($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8])){
				$level = ($sender instanceof Player) ? $sender->getLevel() : Server::getInstance()->getDefaultLevel();
				$pos1 = new Position($args[0], $args[1], $args[2], $level);
				$pos2 = new Position($args[3], $args[4], $args[5], $level);
				$pos3 = new Position($args[6], $args[7], $args[8], $level);
				$args[9] = $args[9] ?? CloneProcessing::MASK[0];
				$args[10] = $args[10] ?? CloneProcessing::CLONE[0];
				$args[11] = $args[11] ?? '';
				$clone = new CloneProcessing($sender, $pos1, $pos2, $pos3, $args[9], $args[10], $args[11]);
				$clone->start();
			}else{
				$sender->sendMessage(TextFormat::RED . Language::get('command.intval.error'));
			}
		}elseif($sender instanceof Player){
			$callable = function($player, $data) {
				if(! isset($data))
					return;
				if($this->checkNumber($data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8])){
					$level_pos1 = ($player->wep_pos1 ?? $player)->getLevel();
					$level_pos2 = ($player->wep_pos2 ?? $player)->getLevel();
					$level_pos3 = $player->getLevel();
					$pos1 = new Position($data[0], $data[1], $data[2], $level_pos1);
					$pos2 = new Position($data[3], $data[4], $data[5], $level_pos2);
					$pos3 = new Position($data[6], $data[7], $data[8], $level_pos3);
					$clone = new CloneProcessing($player, $pos1, $pos2, $pos3, CloneProcessing::MASK[$data[9]], CloneProcessing::CLONE[$data[10]], $data[11]);
					$clone->start();
				}else{
					$player->sendMessage(TextFormat::RED . Language::get('command.intval.error'));
				}
			};
			$form = $this->getDefaultForm($callable, $sender);
			if($form === null)
				return false;
			$x = (string) RangeProcessing::changeInteger($sender->x);
			$y = (string) RangeProcessing::changeInteger($sender->y);
			$z = (string) RangeProcessing::changeInteger($sender->z);
			$form->addInput(TextFormat::RED . Language::get('form.pos.clone.x'), 'int', $x);
			$form->addInput(TextFormat::GREEN . Language::get('form.pos.clone.y'), 'int', $y);
			$form->addInput(TextFormat::AQUA . Language::get('form.pos.clone.z'), 'int', $z);
			$form->addDropdown(
				Language::get('form.mask') . TextFormat::EOL.
				Language::get('form.mask.replace') . TextFormat::EOL.
				Language::get('form.mask.filtered') . TextFormat::EOL.
				Language::get('form.mask.masked')
			, CloneProcessing::MASK);
			$form->addDropdown(
				Language::get('form.clone') . TextFormat::EOL.
				Language::get('form.clone.normal') . TextFormat::EOL.
				Language::get('form.clone.force') . TextFormat::EOL.
				Language::get('form.clone.move')
			, CloneProcessing::CLONE);
			$form->addInput(Language::get('form.block.one'), 'string');
			$form->sendToPlayer($sender);
		}else{
			return false;
		}
		return true;
	}

}