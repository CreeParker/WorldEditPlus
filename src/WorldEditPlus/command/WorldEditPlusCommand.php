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

use WorldEditPlus\Language;
use WorldEditPlus\WorldEditPlus;

use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\command\{
	Command,
	CommandSender
};
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

		if(! $this->owner->isEnabled())
			return false;

		if(! $this->testPermission($sender))
			return false;

		$success = $this->onCommand($sender, $args);

		if(! $success and $this->usageMessage !== "")
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
	 * @return ?CustomForm
	 */
	protected function getDefaultForm(callable $callable, CommandSender $sender) : ?object {
		$formapi = Server::getInstance()->getPluginManager()->getPlugin('FormAPI');
		if($formapi === null) {
			WorldEditPlus::$instance->getLogger()->warning(Language::get('form.api.error'));
			return null;
		}
		$form = $formapi->createCustomForm($callable);
		$form->setTitle(Language::get('form.message'));
		$form->addInput(TextFormat::RED . Language::get('form.pos.one.x'), 'int', $sender->wep_start['x'] ?? '');
		$form->addInput(TextFormat::GREEN . Language::get('form.pos.one.y'), 'int', $sender->wep_start['y'] ?? '');
		$form->addInput(TextFormat::AQUA . Language::get('form.pos.one.z'), 'int', $sender->wep_start['z'] ?? '');
		$form->addInput(TextFormat::RED . Language::get('form.pos.two.x'), 'int', $sender->wep_end['x'] ?? '');
		$form->addInput(TextFormat::GREEN . Language::get('form.pos.two.y'), 'int', $sender->wep_end['y'] ?? '');
		$form->addInput(TextFormat::AQUA . Language::get('form.pos.two.z'), 'int', $sender->wep_end['z'] ?? '');
		return $form;
	}

	/**
	 * @param string ...$number
	 *
	 * @return bool
	 */
	protected function checkNumber(string ...$number) : bool {
		foreach($number as $value)
			if(! is_numeric($value))
				return false;
		return true;
	}

}