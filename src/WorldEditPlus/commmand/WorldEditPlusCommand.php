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

use WorldEditPlus\WorldEditPlus;
use WorldEditPlus\language\Language as Lang;
use pocketmine\command\{Command, CommandSender, PluginIdentifiableCommand};
use pocketmine\utils\MainLogger;

abstract class WorldEditPlusCommand extends Command {

	/** @var WorldEditPlus */
	private $owner;

	/**
	 * @param string $name
	 * @param WorldEditPlus $owner
	 */
	public function __construct(string $name, WorldEditPlus $owner) {
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
	abstract public function onCommand(CommandSender $sender, array $args) : bool;

	/**
	 * @return WorldEditPlus
	 */
	public function getOwner() : WorldEditPlus {
		return $this->owner;
	}

	public function getServer() : Server {
		return $this->owner->getServer();
	}

	public function setUsage(string $usage) : void {
		parent::setUsage(Lang::get($usage));
	}

	public function setDescription(string $description) : void {
		parent::setDescription(Lang::get($description));
	}

	public function getDefaultForm(callable $callback, CommandSender $sender) : ?CustomForm {
		$formapi = Server::getInterface()->getPluginManager()->getPlugin('FormAPI');
		if($fromapi === null) {
			MainLogger::getLogger()->warning(Lang::get('formapi.null.message'));
			return null;
		}
		$form = $formapi->createCustomForm($callback);
		$form->setTitle(Lang::get('form.message'));
		$form->addInput(Lang::get('form.start.x'), 'int', $sender->wep_start['x'] ?? '');
		$form->addInput(Lang::get('form.start.y'), 'int', $sender->wep_start['y'] ?? '');
		$form->addInput(Lang::get('form.start.z'), 'int', $sender->wep_start['z'] ?? '');
		$form->addInput(Lang::get('form.end.x'), 'int', $sender->wep_end['x'] ?? '');
		$form->addInput(Lang::get('form.end.y'), 'int', $sender->wep_end['y'] ?? '');
		$form->addInput(Lang::get('form.end.z'), 'int', $sender->wep_end['z'] ?? '');
		return $form;
	}

	public function checkIntval($x, $y, $z) : bool {
		return is_numeric($x) and is_numeric($y) and is_numeric($z);
	}

}