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

namespace WorldEditPlus\command;

use WorldEditPlus\level\Range;
use WorldEditPlus\Language;
use WorldEditPlus\WorldEditPlus;

use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\Server;

abstract class WorldEditPlusCommand extends Command {

	/** @var WorldEditPlus */
	private $owner;

	/**
	 * @param string $name
	 * @param WorldEditPlus $owner
	 */
	protected function __construct(string $name, WorldEditPlus $owner) {
		parent::__construct($name);
		$this->owner = $owner;
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {

		if (! $this->owner->isEnabled())
			return false;

		if (! $this->testPermission($sender))
			return false;

		$success = $this->onCommand($sender, $args);

		if (! $success and $this->usageMessage !== "")
			throw new InvalidCommandSyntaxException();

		return $success;
	}

	/**
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return bool
	 */
	abstract protected function onCommand(CommandSender $sender, array $args) : bool;

	/**
	 * @param string $usage
	 */
	public function setUsage(string $usage) : void {
		parent::setUsage(Language::get($usage));
	}

	/**
	 * @param string $description
	 */
	public function setDescription(string $description) : void {
		parent::setDescription(Language::get($description));
	}

	/**
	 * @param callable $callable
	 * @param CommandSender $sender
	 *
	 * @return ?object
	 */
	protected function getDefaultForm(callable $callable, CommandSender $sender) : ?object {
		$formapi = Server::getInstance()->getPluginManager()->getPlugin('FormAPI');
		if ($formapi === null) {
			WorldEditPlus::$instance->getLogger()->warning(Language::get('form.api.error'));
			return null;
		}
		$form = $formapi->createCustomForm($callable);
		$form->setTitle(Language::get('form.message'));
		$pos1_x = $sender->wep_pos1->x ?? '';
		$pos1_y = $sender->wep_pos1->y ?? '';
		$pos1_z = $sender->wep_pos1->z ?? '';
		$pos2_x = $sender->wep_pos2->x ?? '';
		$pos2_y = $sender->wep_pos2->y ?? '';
		$pos2_z = $sender->wep_pos2->z ?? '';
		$form->addInput(TextFormat::RED . Language::get('form.pos.one.x'), 'int', (string) $pos1_x);
		$form->addInput(TextFormat::GREEN . Language::get('form.pos.one.y'), 'int', (string) $pos1_y);
		$form->addInput(TextFormat::AQUA . Language::get('form.pos.one.z'), 'int', (string) $pos1_z);
		$form->addInput(TextFormat::RED . Language::get('form.pos.two.x'), 'int', (string) $pos2_x);
		$form->addInput(TextFormat::GREEN . Language::get('form.pos.two.y'), 'int', (string) $pos2_y);
		$form->addInput(TextFormat::AQUA . Language::get('form.pos.two.z'), 'int', (string) $pos2_z);
		return $form;
	}

	/**
	 * @param string &...$number
	 *
	 * @return bool
	 */
	protected function checkNumber(string &...$number) : bool {
		foreach ($number as $key => $value) {
			if (! is_numeric($value))
				return false;
			$number[$key] = Range::changeInteger($value);
		}
		return true;
	}

}