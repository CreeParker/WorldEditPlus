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

namespace WorldEditPlus;

use WorldEditPlus\command\defaults\{
	FillCommand,
	CloneCommand,
	CylinderCommand,
	SphereCommand,
	WandCommand,
	BookCommand,
	Pos1Command,
	Pos2Command,
	CancelCommand
};
use pocketmine\plugin\PluginBase;

class WorldEditPlus extends PluginBase {

	/** @var WorldEditPlus */
	public static $instance;

	public function onEnable() : void {

		self::$instance = $this;

		$resources = $this->getResources();
		foreach($resources as $key => $value)
			$this->saveResource($key);
		$lang = $this->getConfig()->get('language');
		$path = $this->getDataFolder();
		$fall_path = $this->getFile();
		new Language($lang, $path, $fall_path);

		$this->getServer()->getPluginManager()->registerEvents((new EventListener), $this);

		$this->getServer()->getCommandMap()->registerAll('worldeditplus', [
			new FillCommand($this),
		#	new CloneCommand($this),
		#	new CylinderCommand($this),
		#	new SphereCommand($this)
			new WandCommand($this),
			new BookCommand($this),
			new Pos1Command($this),
			new Pos2Command($this),
			new CancelCommand($this),
		]);
		
	}

}