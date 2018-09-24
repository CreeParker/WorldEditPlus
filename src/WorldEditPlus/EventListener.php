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

use pocketmine\event\Listener;

class EventListener implements Listener {

	private $owner;

	public function __construct(WorldEditPlus $owner) {
		$this->owner = $owner;
	}

	public function BlockBreak(BlockBreakEvent $event){
		$this->setWand($event, 'wep_start');
	}

	//ブロックで座標を登録する
	public function setWand($event, $wich){
		$player = $event->getPlayer();
		if($player->isOp()){
			$item = $event->getItem();
			$item_id = $item->getId();
			$item_name = $item->getName();
			if($item_id === 271 and $item_name === 'wand'){
				$event->setCancelled();
				$position = $event->getBlock()->asPosition();
				$this->setPosition($player, $position, $wich);
			}
		}
	}

	//ブロックで座標を登録する
	public function setPosition(Player $player, Position $position, string $wich){
		//始点か終点の座標を登録する
		$player->$wich = [
			'x' => $x = floor((string) $position->x),
			'y' => $y = floor((string) $position->y),
			'z' => $z = floor((string) $position->z),
			'level' => $position->getLevel()
		];
		if(isset($player->wep_start, $player->wep_end)){
			$start = $player->wep_start;
			$end = $player->wep_end;
			$position_start = new Position($start['x'], $start['y'], $start['z'], $start['level']);
			$position_end = new Position($end['x'], $end['y'], $end['z'], $end['level']);
			$side = $this->getSide($position_start, $position_end);
			$side_message = '(§e'.$side['x'] * $side['y'] * $side['z'].'ブロック§r)';
		}else{
			$side_message = '';
		}
		if($wich === 'wep_start'){
			$player->sendMessage("始点が設定されました §c{$x}§r, §a{$y}§r, §b{$z}§r $side_message");
		}elseif($wich === 'wep_end'){
			$player->sendMessage("終点が設定されました §c{$x}§r, §a{$y}§r, §b{$z}§r $side_message");
		}
	}


	//ブロックの情報を表示させる
	public function PlayerInteract(PlayerInteractEvent $event){
		$action = $event->getAction();
		if($action === 0 or $action === 1){
			$player = $event->getPlayer();
			//終点設定
			$this->setWand($event, 'wep_end');
			if($player->isOp()){
				$id = $event->getItem()->getId();
				if($id === 340){
					$block = $event->getBlock();
					$x = $block->x;
					$y = $block->y;
					$z = $block->z;
					$name = $block->getName();
					$id = $block->getId();
					$meta = $block->getDamage();
					var_dump(\json_encode($block, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
					$player->sendMessage("＊$name ($id:$meta) [§c{$x}§r, §a{$y}§r, §b{$z}§r]");
				}
			}
		}
	}

}