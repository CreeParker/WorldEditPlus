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

namespace  WorldEditPlus\command;

use WorldEditPlus\WorldEditPlus;
use WorldEditPlus\language\Language;
use pocketmine\command\{Command, CommandSender, PluginIdentifiableCommand};

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

	public function getMessage(string $text, array $params = []) : string {
		Language::getMessage($text, $params);
	}

	public function setUsage(string $usage) : void {
		$message = $this->getMessage($usage);
		parent::setUsage($message);
	}

	public function setDescription(string $usage) : void {
		$message = $this->getMessage($usage);
		parent::setDescription($message);
	}

}