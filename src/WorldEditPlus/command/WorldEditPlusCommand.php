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

use pocketmine\lang\Translatable;
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
	 * @return bool
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
	public function setUsage(string|Translatable $usage) : void {
		parent::setUsage(Language::get($usage));
	}

	/**
	 * @param string $description
	 */
	public function setDescription(string|Translatable $description) : void {
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
		$name = strtolower($sender->getName());
		$pos1_x = WorldEditPlus::$pos1[$name]->x ?? '';
		$pos1_y = WorldEditPlus::$pos1[$name]->y ?? '';
		$pos1_z = WorldEditPlus::$pos1[$name]->z ?? '';
		$pos2_x = WorldEditPlus::$pos2[$name]->x ?? '';
		$pos2_y = WorldEditPlus::$pos2[$name]->y ?? '';
		$pos2_z = WorldEditPlus::$pos2[$name]->z ?? '';
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