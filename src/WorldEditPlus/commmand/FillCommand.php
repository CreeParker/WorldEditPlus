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
use WorldEditPlus\processing\FillProcessing;
use pocketmine\command\CommandSender;
use pocketmine\level\{Position, Level};
use pocketmine\Player;

class FillCommand extends WorldEditPlusCommand {

	/**
	 * @param WorldEditPlus $owner
	 */
	public function __construct(WorldEditPlus $owner) {
		parent::__construct('fill', $owner);
		$this->setUsage('command.fill.usage');
		$this->setDescription('command.fill.description');
		$this->setPermission('worldeditplus.command.fill');
	}

	public function onCommand(CommandSender $sender, array $args) : bool {
		if(isset($args[0])){
			if(! isset($args[6])) return false;
			//送られてきた座標が正しく入力されているかチェックする(エラーが出たらfalseを返します)
			$check_start = $this->checkIntval($args[0], $args[1], $args[2]);
			$check_end = $this->checkIntval($args[3], $args[4], $args[5]);
			if($check_start and $check_end){
				//プレイヤーのワールドを取得する(コンソールはデフォルトワールドを取得します)
				$sender_level = $sender instanceof Player ? $sender->getLevel() : $sender->getServer()->getDefaultLevel();
				//送られてきた座標をPositionオブジェクトに変換する
				$position_start = new Position($args[0], $args[1], $args[2], $sender_level);
				$position_end = new Position($args[3], $args[4], $args[5], $sender_level);
				//入力されてない項目をデフォルトに設定します
				if(! isset($args[7])) $args[7] = 'set';
				if(! isset($args[8])) $args[8] = '';
				//fillを実行する
				$this->fill($sender, $position_start, $position_end, $args[6], $args[7], $args[8]);
			}else{
				$sender->sendMessage('座標の入力に誤りがあります');
			}
		}elseif($sender instanceof Player){
			//プレイヤーが送信した後の処理(コールバック)
			$callback = function($player, $data){
				if(! isset($data)) return;
				//送られてきた座標が正しく入力されているかチェックする(エラーが出たらfalseを返します)
				$check_start = $this->checkIntval($data[0], $data[1], $data[2]);
				$check_end = $this->checkIntval($data[3], $data[4], $data[5]);
				if($check_start and $check_end){
					//座標を取得したワールドを取得する(存在しない場合は現在地点のワールドを取得)
					$level_start = $player->wep_start['level'] ?? $player->getLevel();
					$level_end = $player->wep_end['level'] ?? $player->getLevel();
					//送られてきた座標をPositionオブジェクトに変換する
					$position_start = new Position($data[0], $data[1], $data[2], $level_start);
					$position_end = new Position($data[3], $data[4], $data[5], $level_end);
					//fillを実行する
					new FillProcessing($player, $position_start, $position_end, $data[6], FillProcessing::OPTION[$data[7]], $data[8]);
				}else{
					$player->sendMessage('座標の入力に誤りがあります');
				}
			};
			//始点終点を含めたフォームを取得 or コールバックを登録する
			$form = $this->getDefaultForm($callback, $sender);
			$form->addInput('①ブロック', 'string');
			$form->addDropdown(
				"オプション\n".
				"* set : 全てを①ブロックにする\n".
				"* outline : 外側を①ブロックにする\n".
				"* hollow : 内側を空気に外側を①ブロックにする\n".
				"* keep : 空気を①ブロックにする\n".
				"* replace : ②ブロックを①ブロックにする"
			, $this->option);
			$form->addInput('②ブロック', 'string');
			$form->sendToPlayer($sender);
		}else{
			return false;
		}
	}

}