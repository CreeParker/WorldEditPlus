<?php

namespace WorldEditPlus;

use pocketmine\block\Block;
use pocketmine\command\{Command, CommandSender as Sender};
use pocketmine\event\block\{BlockBreakEvent, BlockPlaceEvent};
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\level\{Position, Level};
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\scheduler\{Task, AsyncTask};

class WorldEditPlus extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);


	}

	//WorldEditPlusで使用しているグローバル変数
	//Playerオブジェクト
	//- wep_scheduler = スケジューラーが動いているかに使う
	//  保存内容 : schedulerオブジェクトの有無
	//- wep_start = 始点の情報保存に使う
	//  保存内容 : [
	//    x => 始点X,
	//    y => 始点Y,
	//    z => 始点Z,
	//    level => 始点ワールド
	//  ]
	//- wep_end = 終点の情報保存に使う
	//  保存内容 : [
	//    x => 終点X,
	//    y => 終点Y,
	//    z => 終点Z,
	//    level => 終点ワールド
	//  ]

	//fill cloneのモード(フォームとエラー防止に必須)
	public $option = ['set', 'outline', 'hollow', 'keep', 'replace'];
	public $mask = ['replace', 'filtered', 'masked'];
	public $clone = ['normal', 'force', 'move'];
	public $direction = ['x', 'y', 'z'];


#####################################################################


	public function onCommand(Sender $sender, Command $command, string $label, array $args) : bool{
		switch($label){
			case 'fill':
				//他の処理が実行してないか確認する
				if(!isset($sender->wep_scheduler)){
					if(isset($args[0])){
						if(!isset($args[6])) return false;
						//送られてきた座標が正しく入力されているかチェックする(エラーが出たらfalseを返します)
						$check_start = $this->checkPosition($args[0], $args[1], $args[2]);
						$check_end = $this->checkPosition($args[3], $args[4], $args[5]);
						if($check_start and $check_end){
							//プレイヤーのワールドを取得する(コンソールはデフォルトワールドを取得します)
							$sender_level = $sender instanceof Player ? $sender->getLevel() : $this->getServer()->getDefaultLevel();
							//送られてきた座標をPositionオブジェクトに変換する
							$position_start = new Position($args[0], $args[1], $args[2], $sender_level);
							$position_end = new Position($args[3], $args[4], $args[5], $sender_level);
							//入力されてない項目をデフォルトに設定します
							if(!isset($args[7])) $args[7] = 'set';
							if(!isset($args[8])) $args[8] = '';
							//fillを実行する
							$this->fill($sender, $position_start, $position_end, $args[6], $args[7], $args[8]);
						}else{
							$sender->sendMessage('座標の入力に誤りがあります');
						}
					}elseif($sender instanceof Player){
						//プレイヤーが送信した後の処理(コールバック)
						$callback = function($player, $data){
							if(!isset($data)) return;
							//送られてきた座標が正しく入力されているかチェックする(エラーが出たらfalseを返します)
							$check_start = $this->checkPosition($data[0], $data[1], $data[2]);
							$check_end = $this->checkPosition($data[3], $data[4], $data[5]);
							if($check_start and $check_end){
								//座標を取得したワールドを取得する(存在しない場合は現在地点のワールドを取得)
								$level_start = $player->wep_start['level'] ?? $player->getLevel();
								$level_end = $player->wep_end['level'] ?? $player->getLevel();
								//送られてきた座標をPositionオブジェクトに変換する
								$position_start = new Position($data[0], $data[1], $data[2], $level_start);
								$position_end = new Position($data[3], $data[4], $data[5], $level_end);
								//fillを実行する
								$this->fill($player, $position_start, $position_end, $data[6], $this->option[$data[7]], $data[8]);
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
				}else{
					$sender->sendMessage('他の処理が実行中です');
				}
				break;
 			case 'clone':
				if(!isset($sender->wep_scheduler)){
					if(isset($args[0])){
						if(!isset($args[8])) return false;
						//送られてきた座標が正しく入力されているかチェックする(エラーが出たらfalseを返します)
						$check_start = $this->checkPosition($args[0], $args[1], $args[2]);
						$check_end = $this->checkPosition($args[3], $args[4], $args[5]);
						$check_clone = $this->checkPosition($args[6], $args[7], $args[8]);
						if($check_start and $check_end and $check_clone){
							//プレイヤーのワールドを取得する(コンソールはデフォルトワールドを取得します)
							$sender_level = $sender instanceof Player ? $sender->getLevel() : $this->getServer()->getDefaultLevel();
							//送られてきた座標をPositionオブジェクトに変換する
							$position_start = new Position($args[0], $args[1], $args[2], $sender_level);
							$position_end = new Position($args[3], $args[4], $args[5], $sender_level);
							$destination = new Position($args[6], $args[7], $args[8], $sender_level);
							//入力されてない項目をデフォルトに設定します
							if(!isset($args[9])) $args[9] = 'replace';
							if(!isset($args[10])) $args[10] = 'normal';
							if(!isset($args[11])) $args[11] = '';
							//cloneを実行する
							$this->clone($sender, $position_start, $position_end, $destination, $args[9], $args[10], $args[11]);
						}else{
							$sender->sendMessage('座標の入力に誤りがあります');
						}
					}elseif($sender instanceof Player){
						//プレイヤーが送信した後の処理(コールバック)
						$callback = function($player, $data){
							if(!isset($data)) return;
							//送られてきた座標が正しく入力されているかチェックする(エラーが出たらfalseを返します)
							$check_start = $this->checkPosition($data[0], $data[1], $data[2]);
							$check_end = $this->checkPosition($data[3], $data[4], $data[5]);
							$check_clone = $this->checkPosition($data[6], $data[7], $data[8]);
							if($check_start and $check_end and $check_clone){
								$level_player = $player->getLevel();
								//座標を取得したワールドを取得する(存在しない場合は現在地点のワールドを取得)
								$level_start = $player->wep_start['level'] ?? $level_player;
								$level_end = $player->wep_end['level'] ?? $level_player;
								//送られてきた座標をPositionオブジェクトに変換する
								$position_start = new Position($data[0], $data[1], $data[2], $level_start);
								$position_end = new Position($data[3], $data[4], $data[5], $level_end);
								$destination = new Position($data[6], $data[7], $data[8], $level_player);
								//cloneを実行する
								$this->clone($player, $position_start, $position_end, $destination, $this->mask[$data[9]], $this->clone[$data[10]], $data[11]);
							}else{
								$player->sendMessage('座標の入力に誤りがあります');
							}
						};
						$form = $this->getDefaultForm($callback, $sender);
						$form->addInput('§cクローンX', 'int', floor((string) $sender->x));
						$form->addInput('§aクローンY', 'int', floor((string) $sender->y));
						$form->addInput('§bクローンZ', 'int', floor((string) $sender->z));
						$form->addDropdown(
							"マスクモード\n".
							"* replace : 全てのブロックをクローンする\n".
							"* filtered : ①ブロックのみクローンする\n".
							"* masked : 空気以外をクローンする"
						, $this->mask);
						$form->addDropdown(
							"クローンモード\n".
							"* normal : コピーする\n".
							"* force : コピー元に重なっても強制実行する\n".
							"* move : 移動する"
						, $this->clone);
						$form->addInput('①ブロック', 'string');
						$form->sendToPlayer($sender);
					}else{
						return false;
					}
				}else{
					$player->sendMessage('他の処理が実行中です');
				}
				break;
			case 'cylinder':
				//他の処理が実行してないか確認する
				if(!isset($sender->wep_scheduler)){
					if(isset($args[0])){
						if(!isset($args[7])) return false;
						//送られてきた座標が正しく入力されているかチェックする(エラーが出たらfalseを返します)
						$check_start = $this->checkPosition($args[0], $args[1], $args[2]);
						$check_end = $this->checkPosition($args[3], $args[4], $args[5]);
						if($check_start and $check_end){
							//プレイヤーのワールドを取得する(コンソールはデフォルトワールドを取得します)
							$sender_level = $sender instanceof Player ? $sender->getLevel() : $this->getServer()->getDefaultLevel();
							//送られてきた座標をPositionオブジェクトに変換する
							$position_start = new Position($args[0], $args[1], $args[2], $sender_level);
							$position_end = new Position($args[3], $args[4], $args[5], $sender_level);
							//cylinderを実行する
							$this->cylinder($sender, $position_start, $position_end, $args[6], $args[7]);
						}else{
							$sender->sendMessage('座標の入力に誤りがあります');
						}
					}elseif($sender instanceof Player){
						//プレイヤーが送信した後の処理(コールバック)
						$callback = function($player, $data){
							if(!isset($data)) return;
							//送られてきた座標が正しく入力されているかチェックする(エラーが出たらfalseを返します)
							$check_start = $this->checkPosition($data[0], $data[1], $data[2]);
							$check_end = $this->checkPosition($data[3], $data[4], $data[5]);
							if($check_start and $check_end){
								//座標を取得したワールドを取得する(存在しない場合は現在地点のワールドを取得)
								$level_start = $player->wep_start['level'] ?? $player->getLevel();
								$level_end = $player->wep_end['level'] ?? $player->getLevel();
								//送られてきた座標をPositionオブジェクトに変換する
								$position_start = new Position($data[0], $data[1], $data[2], $level_start);
								$position_end = new Position($data[3], $data[4], $data[5], $level_end);
								//cylinderを実行する
								$this->cylinder($player, $position_start, $position_end, $data[6], $this->direction[$data[7]]);
							}else{
								$player->sendMessage('座標の入力に誤りがあります');
							}
						};
						//始点終点を含めたフォームを取得 or コールバックを登録する
						$form = $this->getDefaultForm($callback, $sender);
						$form->addInput('①ブロック', 'string');
						$form->addDropdown('円柱を作成する方向', $this->direction);
						$form->sendToPlayer($sender);
					}else{
						return false;
					}
				}else{
					$sender->sendMessage('他の処理が実行中です');
				}
				break;
			case 'sphere':
				//他の処理が実行してないか確認する
				if(!isset($sender->wep_scheduler)){
					if(isset($args[0])){
						if(!isset($args[6])) return false;
						//送られてきた座標が正しく入力されているかチェックする(エラーが出たらfalseを返します)
						$check_start = $this->checkPosition($args[0], $args[1], $args[2]);
						$check_end = $this->checkPosition($args[3], $args[4], $args[5]);
						if($check_start and $check_end){
							//プレイヤーのワールドを取得する(コンソールはデフォルトワールドを取得します)
							$sender_level = $sender instanceof Player ? $sender->getLevel() : $this->getServer()->getDefaultLevel();
							//送られてきた座標をPositionオブジェクトに変換する
							$position_start = new Position($args[0], $args[1], $args[2], $sender_level);
							$position_end = new Position($args[3], $args[4], $args[5], $sender_level);
							//sphereを実行する
							$this->sphere($sender, $position_start, $position_end, $args[6]);
						}else{
							$sender->sendMessage('座標の入力に誤りがあります');
						}
					}elseif($sender instanceof Player){
						//プレイヤーが送信した後の処理(コールバック)
						$callback = function($player, $data){
							if(!isset($data)) return;
							//送られてきた座標が正しく入力されているかチェックする(エラーが出たらfalseを返します)
							$check_start = $this->checkPosition($data[0], $data[1], $data[2]);
							$check_end = $this->checkPosition($data[3], $data[4], $data[5]);
							if($check_start and $check_end){
								//座標を取得したワールドを取得する(存在しない場合は現在地点のワールドを取得)
								$level_start = $player->wep_start['level'] ?? $player->getLevel();
								$level_end = $player->wep_end['level'] ?? $player->getLevel();
								//送られてきた座標をPositionオブジェクトに変換する
								$position_start = new Position($data[0], $data[1], $data[2], $level_start);
								$position_end = new Position($data[3], $data[4], $data[5], $level_end);
								//sphereを実行する
								$this->sphere($player, $position_start, $position_end, $data[6]);
							}else{
								$player->sendMessage('座標の入力に誤りがあります');
							}
						};
						//始点終点を含めたフォームを取得 or コールバックを登録する
						$form = $this->getDefaultForm($callback, $sender);
						$form->addInput('①ブロック', 'string');
						$form->sendToPlayer($sender);
					}else{
						return false;
					}
				}else{
					$sender->sendMessage('他の処理が実行中です');
				}
				break;
			case 'cancel':
				if(isset($sender->wep_scheduler)){
					$sender->wep_scheduler->cancel();
					unset($sender->wep_scheduler);
					$sender->sendMessage('実行中の処理をキャンセルしました');
				}else{
					$sender->sendMessage('実行中の処理はありません');
				}
				break;
			case 'wand':
				$axe = Item::get(271);
				$axe->setCustomName('wand');
				$sender->getInventory()->addItem($axe);
				$sender->sendMessage('始点と終点を決める斧を付与しました');
				break;
			case 'pos1':
			case 'pos2':
				if($sender instanceof Player){
					$wich = $label === 'pos1' ? 'wep_start' : 'wep_end';
					if(isset($args[0])){
						if(!isset($args[2])) return false;
						if($this->checkPosition($args[0], $args[1], $args[2])){
							$sender_level = $sender->getLevel();
							$position = new Position($args[0], $args[1], $args[2], $sender_level);
							$this->setPosition($sender, $position, $wich);
						}else{
							$sender->sendMessage('座標の入力に誤りがあります');
						}
					}else{
						$position = $sender->asPosition();
						$this->setPosition($sender, $position, $wich);
					}
				}else{
					$sender->sendMessage('コンソールからは実行できません');
				}
				break;
		}
		return true;
	}

	//数字かどうかを判別する(文字列の数字にも有効)
	public function checkPosition($x, $y, $z){
		return is_numeric($x) and is_numeric($y) and is_numeric($z);
	}

	//フォームを作り座標入力欄を追加する
	public function getDefaultForm($callback, $sender){
		$form = $this->getServer()->getPluginManager()->getPlugin('FormAPI')->createCustomForm($callback);
		$form->setTitle('コマンドアシスト');
		$form->addInput('§c始点X', 'int', $sender->wep_start['x'] ?? '');
		$form->addInput('§a始点Y', 'int', $sender->wep_start['y'] ?? '');
		$form->addInput('§b始点Z', 'int', $sender->wep_start['z'] ?? '');
		$form->addInput('§c終点X', 'int', $sender->wep_end['x'] ?? '');
		$form->addInput('§a終点Y', 'int', $sender->wep_end['y'] ?? '');
		$form->addInput('§b終点Z', 'int', $sender->wep_end['z'] ?? '');
		return $form;
	}


#####################################################################


	//始点設定
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
					$player->sendMessage("＊$name ($id:$meta) [§c{$x}§r, §a{$y}§r, §b{$z}§r]");
				}
			}
		}
	}


#####################################################################


	public function fill(Sender $player, Position $start, Position $end, string $block, string $option = 'set', string $replace = ''){
		$level_start = $start->getLevel();
		$level_end = $end->getLevel();
		//同じワールドか確認する
		if($level_start == $level_end){
			//指定されたブロックのオブジェクトを取得します(エラーが出たらfalseを返します)
			$block = $this->fromString($block);
			if($block !== false){
				//オプションが存在するか確認します
				if(in_array($option, $this->option)){
					//オプションがreplaceの時に、指定されたブロックのオブジェクトを取得します(エラーが出たらfalseを返します)
					$replace = $option === 'replace' ? $this->fromString($replace) : true;
					if($replace !== false){
						//スケジューラーで処理をする無名クラスです
						$task = new class($this, $player, $start, $end, $block, $option, $replace) extends Task{

							public function __construct($owner, Sender $player, Position $start, Position $end, array $block, string $option, $replace){
								//ジェネレーターを作成します
								$this->generator = $this->generator($owner, $player, $start, $end, $option);
								//設置するブロックのオブジェクト
								$this->block = $block;
								//置き換えるブロックのオブジェクト
								$this->replace = $replace;
								//x, y ,zの最小値、最大値
								$this->min = $owner->getMin($start, $end);
								$this->max = $owner->getMax($start, $end);
								//空気ブロックのオブジェクト
								$this->air = Block::get(0);
							}

							public function onRun(int $tick){
								//ジェネレータの次の処理を実行します
								$this->generator->next();
							}

							public function generator($owner, Sender $player, Position $start, Position $end, string $option){
								//始点のx, y, zを設定します
								$start_x = $start->x;
								$start_y = $start->y;
								$start_z = $start->z;
								//処理をするワールドを設定します
								$level = $start->getLevel();
								//x, y ,zの長さを取得します
								$side = $owner->getSide($start, $end);
								//始点から終点に向かう方向を取得します
								$next = $owner->getNext($start, $end);
								//進行ゲージを設定します
								$meter = 100 / $side['x'];
								$gage = 0;
								//実行開始のメッセージ
								$player_name = $player->getName();
								$owner->getServer()->broadcastMessage($player_name.'が/fillを実行しました (§e'.$side['x'] * $side['y'] * $side['z'].'ブロック§r)');
								//ジェネレーターを一時停止する
								yield;
								for($a = 0; abs($a) < $side['x']; $a += $next['x']){
									//処理するX座標
									$x = $start_x + $a;
									for($b = 0; abs($b) < $side['y']; $b += $next['y']){
										//処理するY座標
										$y = $start_y + $b;
										//Y座標の高低制限を超えてたら下の処理を無視する
										if($y < 0 or $y > Level::Y_MAX)
											continue;
										for($c = 0; abs($c) < $side['z']; $c += $next['z']){
											//処理するZ座標
											$z = $start_z + $c;
											//チャンクが読み込まれていなかったら読み込む
											if(!$level->isChunkLoaded($x >> 4, $z >> 4))
												$level->loadChunk($x >> 4, $z >> 4, true);
											//x, y, zのVector3オブジェクトを取得する
											$vector = new Vector3($x, $y, $z);
											//変更前のブロックオブジェクトを取得します
											$old_block = $level->getBlock($vector);
											//オプションに応じた処理のブロックオブジェクトを取得します(置き換えない場合はfalseを返します)
											$new_block = $this->$option($old_block);
											if($new_block !== false){
												//変更前のブロックが同じでなかったら置き換えます
												if((string) $old_block !== (string) $new_block)
													$level->setBlock($vector, $new_block, true, false);
												#バックアップ変数設置予定
											}
											//一度に設置するブロックを制限します(1000ブロック以上)
											$restriction = isset($restriction) ? ++$restriction : 0;
											if($restriction > 1000){
												yield;
												unset($restriction);
											}
										}
									}
									//進行ゲージを進めます
									$round = round($gage += $meter);
									$owner->getServer()->broadcastTip("§l$player_name : §a{$round} ％ 完了");
									#バックアップ返す予定
									#yield;
								}
								//スケジューラーを止めます
								$this->getHandler()->cancel();
								unset($player->wep_scheduler);
								$owner->getServer()->broadcastMessage($player_name.'の/fillが終了しました');
							}

							//setの処理
							public function set($block = null){
								$rand = array_rand($this->block);
								return $this->block[$rand];
							}

							//replaceの処理
							public function replace($block){
								return isset($this->replace[(string) $block]) ? $this->set() : false;
							}

							//outlineの処理
							public function outline($block){
								$x = $block->x;
								$y = $block->y;
								$z = $block->z;
								if($x != $this->min['x'] and $x != $this->max['x'])
									if($y != $this->min['y'] and $y != $this->max['y'])
										if($z != $this->min['z'] and $z != $this->max['z'])
											return false;
								return $this->set();
							}

							//hollowの処理
							public function hollow($block){
								$x = $block->x;
								$y = $block->y;
								$z = $block->z;
								if($x != $this->min['x'] and $x != $this->max['x'])
									if($y != $this->min['y'] and $y != $this->max['y'])
										if($z != $this->min['z'] and $z != $this->max['z'])
											return $this->air;
								return $this->set();
							}

							//keepの処理
							public function keep($block){
								return (string) $block === (string) $this->air ? $this->set() : false;
							}

						};
						//スケジューラーを実行します
						$player->wep_scheduler = $this->getScheduler()->scheduleRepeatingTask($task, 1);
					}else{
						$player->sendMessage('②ブロックに無効なブロックが含まれています');
					}
				}else{
					$player->sendMessage('無効なオプションです');
				}
			}else{
				$player->sendMessage('①ブロックに無効なブロックが含まれています');
			}
		}else{
			$player->sendMessage('同じワールドで指定してください');
		}
	}

	public function clone(Sender $player, Position $start, Position $end, Position $destination, string $mask = 'replace', string $clone = 'normal', string $replace = ''){
		$level_start = $start->getLevel();
		$level_end = $end->getLevel();
		//同じワールドか確認する
		if($level_start == $level_end){
			//マスクモードが存在するか確認します
			if(in_array($mask, $this->mask)){
				//クローンモードが存在するか確認します
				if(in_array($clone, $this->clone)){
					//マスクモードがfilteredの時に、指定されたブロックのオブジェクトを取得します(エラーが出たらfalseを返します)
					$replace = $mask === 'filtered' ? $this->fromString($replace) : true;
					if($replace !== false){
						//スケジューラーで処理をする無名クラスです
						$task = new class($this, $player, $start, $end, $destination, $mask, $clone, $replace) extends Task{

							public function __construct($owner, Sender $player, Position $start, Position $end, Position $destination, string $mask, string $clone, $replace){
								//ジェネレーターを作成します
								$this->generator = $this->generator($owner, $player, $start, $end, $destination, $mask, $clone);
								//指定したブロックのオブジェクト
								$this->replace = $replace;
								//x, y ,zの最小値、最大値
								$this->min = $owner->getMin($start, $end);
								$this->max = $owner->getMax($start, $end);
								//空気ブロックのオブジェクト
								$this->air = Block::get(0);
							}

							public function onRun(int $tick){
								//ジェネレータの次の処理を実行します
								$this->generator->next();
							}

							public function generator($owner, Sender $player, Position $start, Position $end, Position $destination, string $mask, string $clone){
								//始点のx, y, zを設定します
								$start_x = $start->x;
								$start_y = $start->y;
								$start_z = $start->z;
								//クローン先のx, y, zを設定します
								$destination_x = $destination->x;
								$destination_y = $destination->y;
								$destination_z = $destination->z;
								//処理をするワールドを設定します
								$start_level = $this->level = $start->getLevel();
								$destination_level = $destination->getLevel();
								//クローン元とクローン先のワールドが同じかどうか
								$this->normal = $start_level == $destination_level;
								//x, y ,zの長さを取得します
								$side = $owner->getSide($start, $end);
								//始点から終点に向かう方向を取得します
								$next = $owner->getNext($start, $end);
								//進行ゲージを設定します
								$meter = 100 / $side['x'];
								$gage = 0;
								//実行開始のメッセージ
								$player_name = $player->getName();
								$owner->getServer()->broadcastMessage($player_name.'が/cloneを実行しました (§e'.$side['x'] * $side['y'] * $side['z'].'ブロック§r)');
								//ジェネレーターを一時停止する
								yield;
								for($a = 0; abs($a) < $side['x']; $a += $next['x']){
									//処理するクローン元のX座標
									$old_x = $start_x + $a;
									//処理するクローン先のX座標
									$new_x = $destination_x + $a;
									for($b = 0; abs($b) < $side['y']; $b += $next['y']){
										//処理するクローン元のY座標
										$old_y = $start_y + $b;
										//処理するクローン先のX座標
										$new_y = $destination_y + $b;
										//Y座標の高低制限を超えてたら下の処理を無視する
										if($old_y < 0 or $old_y > Level::Y_MAX or $new_y < 0 or $new_y > Level::Y_MAX)
											continue;
										for($c = 0; abs($c) < $side['z']; $c += $next['z']){
											//処理するクローン元のX座標
											$old_z = $start_z + $c;
											//処理するクローン先のX座標
											$new_z = $destination_z + $c;
											//クローン元のチャンクが読み込まれていなかったら読み込む
											if(!$start_level->isChunkLoaded($old_x >> 4, $old_z >> 4))
												$start_level->loadChunk($old_x >> 4, $old_z >> 4, true);
											//クローン先のチャンクが読み込まれていなかったら読み込む
											if(!$destination_level->isChunkLoaded($new_x >> 4, $new_z >> 4))
												$destination_level->loadChunk($new_x >> 4, $new_z >> 4, true);
											//クローン元のx, y, zのVector3オブジェクトを取得する
											$old_vector = new Vector3($old_x, $old_y, $old_z);
											//クローン元のブロックオブジェクトを取得します
											$old_block = $start_level->getBlock($old_vector);
											//クローンするブロックを判別します
											if($this->$mask($old_block)){
												//クローン先のブロックオブジェクトを取得します
												$new_vector = new Vector3($new_x, $new_y, $new_z);
												//クローンするか確認します
												if($this->$clone($old_vector, $new_vector)){
													#$old_block = $destination_level->getBlock($new_vector);
													#バックアップ変数設置予定
													$destination_level->setBlock($new_vector, $old_block, true, false);
												}
											}
											//一度に設置するブロックを制限します(1000ブロック以上)
											$restriction = isset($restriction) ? ++$restriction : 0;
											if($restriction > 1000){
												yield;
												unset($restriction);
											}
										}
									}
									//進行ゲージを進めます
									$round = round($gage += $meter);
									$owner->getServer()->broadcastTip("§l$player_name : §a{$round} ％ 完了");
									#バックアップ返す予定
									#yield;
								}
								//スケジューラーを止めます
								$this->getHandler()->cancel();
								unset($player->wep_scheduler);
								$owner->getServer()->broadcastMessage($player_name.'の/cloneが終了しました');
							}

							//マスクモードのreplaceの処理
							public function replace(Block $block) : bool{
								return true;
							}

							//マスクモードのfilteredの処理
							public function filtered(Block $block) : bool{
								return isset($this->replace[(string) $block]);
							}

							//マスクモードのmaskedの処理
							public function masked(Block $block) : bool{
								return (string) $block !== (string) $this->air;
							}

							//クローンモードのnormalの処理
							public function normal(Vector3 $old, Vector3 $new) : bool{
								if($this->normal){
									$x = $new->x;
									$y = $new->y;
									$z = $new->z;
									if($x >= $this->min['x'] and $x <= $this->max['x'])
										if($y >= $this->min['y'] and $y <= $this->max['y'])
											if($z >= $this->min['z'] and $z <= $this->max['z'])
												return false;
								}
								return true;
							}

							//クローンモードのforceの処理
							public function force(Vector3 $old, Vector3 $new) : bool{
								return true;
							}

							//クローンモードのmoveの処理
							public function move(Vector3 $old, Vector3 $new) : bool{
								return $this->level->setBlock($old, $this->air, true, false);
							}

						};
						//スケジューラーを実行します
						$player->wep_scheduler = $this->getScheduler()->scheduleRepeatingTask($task, 1);
					}else{
						$player->sendMessage('①ブロックに無効なブロックが含まれています');
					}
				}else{
					$player->sendMessage('無効なクローンモードです');
				}
			}else{
				$player->sendMessage('無効なマスクモードです');
			}
		}else{
			$player->sendMessage('同じワールドで指定してください');
		}
	}

	//未完成(現在setのみ可能です)
	public function cylinder(Sender $player, Position $start, Position $end, string $block, string $direction){
		$level_start = $start->getLevel();
		$level_end = $end->getLevel();
		//同じワールドか確認する
		if($level_start == $level_end){
			//指定されたブロックのオブジェクトを取得します(エラーが出たらfalseを返します)
			$block = $this->fromString($block);
			if($block !== false){
				//方向が存在するか確認します
				if(in_array($direction, $this->direction)){
					//処理する方向を設定します
					$direction_key = array_search($direction, $this->direction);
					$direction_clone = $this->direction;
					unset($direction_clone[$direction_key]);
					array_unshift($direction_clone, $this->direction[$direction_key]);
					//スケジューラーで処理をする無名クラスです
					$task = new class($this, $player, $start, $end, $block, $direction_clone) extends Task{

						public function __construct($owner, Sender $player, Position $start, Position $end, array $block, array $direction){
							//ジェネレーターを作成します
							$this->generator = $this->generator($owner, $player, $start, $end, $direction);
							//設置するブロックのオブジェクト
							$this->block = $block;
							//空気ブロックのオブジェクト
							$this->air = Block::get(0);
						}

						public function onRun(int $tick){
							//ジェネレータの次の処理を実行します
							$this->generator->next();
						}

						public function generator($owner, Sender $player, Position $start, Position $end, array $direction){
							//向き設定 0=進行方向, 1と2=円を作成する向き
							$direction_0 = $direction[0];
							$direction_1 = $direction[1];
							$direction_2 = $direction[2];
							//始点の進行方向を設定します
							$start_0 = $start->$direction_0;
							//処理をするワールドを設定します
							$level = $start->getLevel();
							//x, y ,zの長さを取得します
							$side = $owner->getSide($start, $end);
							//始点から終点に向かう方向を取得します
							$next = $owner->getNext($start, $end);
							//半径を調べます
							$radius_1 = ($side[$direction_1] - 1) / 2;
							$radius_2 = ($side[$direction_2] - 1) / 2;
							//中心点を調べます
							$center_1 = $start->$direction_1 + ($radius_1 * $next[$direction_1]);
							$center_2 = $start->$direction_2 + ($radius_2 * $next[$direction_2]);
							//進行ゲージを設定します
							$meter = 100 / $side[$direction_0];
							$gage = 0;
							//実行開始のメッセージ
							$player_name = $player->getName();
							$owner->getServer()->broadcastMessage($player_name.'が/cylinderを実行しました (§e'.$side['x'] * $side['y'] * $side['z'].'ブロック§r)');
							//ジェネレーターを一時停止する
							yield;
							//円の作成処理
							for($b = 0; $b < 360; $b += 0.01){
								//ラジアンを取得します
								$radian = deg2rad($b);
								//設置する座標を計算します
								$x = round($center_1 + ($radius_1 * sin($radian)));
								$z = round($center_2 + ($radius_2 * cos($radian)));
								//同じ座標だったらスキップする
								if(isset($memory))
									if([$x, $z] == $memory)
										continue;
								$memory = [$x, $z];
								$cylinder[] = $memory;
							}
							//ジェネレーターを一時停止する
							yield;
							//進行方向
							for($a = 0; abs($a) < $side[$direction_0]; $a += $next[$direction_0]){
								$y = $start_0 + $a;
								//計算した場所に設置します
								foreach($cylinder as $value){
									//向きにあったVector3を取得します
									if($direction_0 === 'x'){
										$vector = new Vector3($y, $value[0], $value[1]);
									}elseif($direction_0 === 'y'){
										$vector = new Vector3($value[0], $y, $value[1]);
									}elseif($direction_0 === 'z'){
										$vector = new Vector3($value[0], $value[1], $y);
									}
									#ここら辺が未完成
									$block = $this->set();
									$level->setBlock($vector, $block, true, false);
								}
								//進行ゲージを進めます
								$round = round($gage += $meter);
								$owner->getServer()->broadcastTip("§l$player_name : §a{$round} ％ 完了");
								#バックアップ返す予定
								yield;
							}
							//スケジューラーを止めます
							$this->getHandler()->cancel();
							unset($player->wep_scheduler);
							$owner->getServer()->broadcastMessage($player_name.'の/cylinderが終了しました');
						}

						//setの処理
						public function set($block = null){
							$rand = array_rand($this->block);
							return $this->block[$rand];
						}

					};
					//スケジューラーを実行します
					$player->wep_scheduler = $this->getScheduler()->scheduleRepeatingTask($task, 1);
				}else{
					$player->sendMessage('無効な方向です');
				}
			}else{
				$player->sendMessage('①ブロックに無効なブロックが含まれています');
			}
		}else{
			$player->sendMessage('同じワールドで指定してください');
		}
	}

	//未完成(現在setのみ可能です)
	public function sphere(Sender $player, Position $start, Position $end, string $block){
		$level_start = $start->getLevel();
		$level_end = $end->getLevel();
		//同じワールドか確認する
		if($level_start == $level_end){
			//指定されたブロックのオブジェクトを取得します(エラーが出たらfalseを返します)
			$block = $this->fromString($block);
			if($block !== false){
				//スケジューラーで処理をする無名クラスです
				$task = new class($this, $player, $start, $end, $block) extends Task{
					public function __construct($owner, Sender $player, Position $start, Position $end, array $block){
						//ジェネレーターを作成します
						$this->generator = $this->generator($owner, $player, $start, $end);
						//設置するブロックのオブジェクト
						$this->block = $block;
						//空気ブロックのオブジェクト
						$this->air = Block::get(0);
					}
					public function onRun(int $tick){
						//ジェネレータの次の処理を実行します
						$this->generator->next();
					}
					public function generator($owner, Sender $player, Position $start, Position $end){
						//処理をするワールドを設定します
						$level = $start->getLevel();
						//x, y ,zの長さを取得します
						$side = $owner->getSide($start, $end);
						//始点から終点に向かう方向を取得します
						$next = $owner->getNext($start, $end);
						//半径を調べます		$sx = ($side['x'] - 1) / 2;
						$radius_x = ($side['x'] - 1) / 2;
						$radius_y = ($side['y'] - 1) / 2;
						$radius_z = ($side['z'] - 1) / 2;
						//中心点を調べます
						$center_x = $start->x + ($radius_x * $next['x']);
						$center_y = $start->y + ($radius_y * $next['y']);
						$center_z = $start->z + ($radius_z * $next['z']);
						//進行ゲージを設定します
						$meter = 100 / ((180 / 0.5) * (360 / 0.5));
						$gage = 0;
						$player_name = $player->getName();
						$owner->getServer()->broadcastMessage($player_name.'が/sphereを実行しました (§e'.$side['x'] * $side['y'] * $side['z'].'ブロック§r)');
						//ジェネレーターを一時停止する
						yield;
						for($a = 270; $a > 90; $a -= 0.5){
							$radian_1 = deg2rad($a);
							$sin_1 = sin($radian_1);
							$cos_1 = cos($radian_1);
							$x = round($center_x + ($radius_x * $sin_1));
							$radius_next_y = $radius_y * $cos_1;
							$radius_next_z = $radius_z * $cos_1;
							for($b = 0; $b < 360; $b += 0.5){
								$radian_2 = deg2rad($b);
								$y = round($center_y + ($radius_next_y * sin($radian_2)));
								$z = round($center_z + ($radius_next_z * cos($radian_2)));
								$vector = new Vector3($x, $y, $z);
								$block = $this->set();
								$level->setBlock($vector, $block, true, false);
								//一度に設置するブロックを制限します(1000ブロック以上)
								$restriction = isset($restriction) ? ++$restriction : 0;
								if($restriction > 360){
									yield;
									unset($restriction);
								}
								//進行ゲージを進めます
								$round = round($gage += $meter);
								$owner->getServer()->broadcastTip("§l$player_name : §a{$round} ％ 完了");
							}
							#バックアップ返す予定
							#yield;
						}
						//スケジューラーを止めます
						$this->getHandler()->cancel();
						unset($player->wep_scheduler);
						$owner->getServer()->broadcastMessage($player_name.'の/sphereが終了しました');
					}
					//setの処理
					public function set($block = null){
						$rand = array_rand($this->block);
						return $this->block[$rand];
					}
				};
				//スケジューラーを実行します
				$player->wep_scheduler = $this->getScheduler()->scheduleRepeatingTask($task, 1);
			}else{
				$player->sendMessage('①ブロックに無効なブロックが含まれています');
			}
		}else{
			$player->sendMessage('同じワールドで指定してください');
		}
	}

	//stringのブロックオブジェクトを取得します
	public function fromString(string $string){
		try{
			$items = Item::fromString($string, true);
			foreach($items as $item){
				$item_name = $item->getName();
				$block = $item->getBlock();
				$block_name = $block->getName();
				if($item_name !== $block_name) return false;
				$blocks[(string) $block] = $block;
			}
			return $blocks;
		}catch(\Exception $e){
			return false;
		}
	}


#####################################################################


	//x, y, zの最小値を取得します
	public function getMin(Vector3 $start, Vector3 $end) : array{
		return [
			'x' => min($start->x, $end->x),
			'y' => min($start->y, $end->y),
			'z' => min($start->z, $end->z)
		];
	}

	//x, y, zの最大値を取得します
	public function getMax(Vector3 $start, Vector3 $end) : array{
		return [
			'x' => max($start->x, $end->x),
			'y' => max($start->y, $end->y),
			'z' => max($start->z, $end->z)
		];
	}

	//x, y, zの長さを調べて取得します
	public function getSide(Vector3 $start, Vector3 $end) : array{
		$min = $this->getMin($start, $end);
		$max = $this->getMax($start, $end);
		return [
			'x' => ($max['x'] - $min['x']) + 1,
			'y' => ($max['y'] - $min['y']) + 1,
			'z' => ($max['z'] - $min['z']) + 1
		];
	}

	//始点から終点に向かう方向を取得します
	public function getNext(Vector3 $start, Vector3 $end) : array{
		return [
			'x' => $start->x < $end->x ? +1 : -1,
			'y' => $start->y < $end->y ? +1 : -1,
			'z' => $start->z < $end->z ? +1 : -1
		];
	}


#####################################################################


}