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

use WorldEditPlus\command\{
	FillProcessing,
	CloneCommand,
	CylinderCommand,
	SphereCommand
};
use WorldEditPlus\language\Language;
use pocketmine\plugin\PluginBase;

class WorldEditPlus extends PluginBase {

	public function onEnable() : void {

		$resources = $this->getResources();
		foreach($resources as $key => $value)
			$this->saveResource($key);
		$lang = $this->getConfig()->get('language');
		$path = $this->getDataFolder();
		$fall_path = $this->getFile();
		new Language($lang, $path, $fall_path);

		var_dump(Language::get('wand.pos1', [1, 2, 3]));

		/*$event = new EventListener($this);
		$this->getServer()->getPluginManager()->registerEvents($event, $this);
		$this->getServer()->getCommandMap()->registerAll('worldeditplus', [
			new FillProcessing($this),
			new CloneCommand($this),
			new CylinderCommand($this),
			new SphereCommand($this)
		]);*/
	}


}