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

use WorldEditPlus\command\defaults\BookCommand;
use WorldEditPlus\command\defaults\CancelCommand;
use WorldEditPlus\command\defaults\CloneCommand;
use WorldEditPlus\command\defaults\CylinderCommand;
use WorldEditPlus\command\defaults\FillCommand;
use WorldEditPlus\command\defaults\Pos1Command;
use WorldEditPlus\command\defaults\Pos2Command;
use WorldEditPlus\command\defaults\SphereCommand;
use WorldEditPlus\command\defaults\WandCommand;

use pocketmine\plugin\PluginBase;

class WorldEditPlus extends PluginBase {

	/** @var WorldEditPlus */
	public static $instance;

	public function onEnable() : void {

		self::$instance = $this;

		$resources = $this->getResources();
		foreach ($resources as $key => $value)
			$this->saveResource($key);
		$lang = $this->getConfig()->get('language');
		$path = $this->getDataFolder();
		$fall_path = $this->getFile();
		new Language($lang, $path, $fall_path);

		$this->getServer()->getPluginManager()->registerEvents((new EventListener), $this);

		$this->getServer()->getCommandMap()->registerAll('worldeditplus', [
			new BookCommand($this),
			new CancelCommand($this),
			new CloneCommand($this),
			#new CylinderCommand($this),
			new FillCommand($this),
			new Pos1Command($this),
			new Pos2Command($this),
			#new SphereCommand($this),
			new WandCommand($this),
		]);
		
	}

}