<?php

namespace WorldEditPlus;

use pocketmine\block\Block;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\scheduler\Task;
use pocketmine\level\Level;

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


#####################################################################


	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($label){
			case 'fill':
				//他の処理が実行してないか確認する
				if(!isset($sender->wep_scheduler)){
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
					$sender->sendMessage('他の処理が実行中です');
				}
				break;
 			case 'clone':
				if(!isset($sender->wep_scheduler)){
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
					$form->addInput('§cクローンX', 'int', (int) $sender->x);
					$form->addInput('§aクローンY', 'int', (int) $sender->y);
					$form->addInput('§bクローンZ', 'int', (int) $sender->z);
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
					$player->sendMessage('他の処理が実行中です');
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
			case 'e':
				unset($sender->wep_start, $sender->wep_end);
				$sender->sendMessage('始点と終点を消去しました');
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


	public function BlockBreak(BlockBreakEvent $event){
		$this->setPosition($event);
	}

	public function BlockPlace(BlockPlaceEvent $event){
		$this->setPosition($event);
	}

	//ブロックで座標を登録する
	public function setPosition($event){
		$player = $event->getPlayer();
		if($player->isOp()){
			$id = $event->getItem()->getId();
			if($id === 19){
				//始点か終点か判別して取得する(両方とも登録されていたらfalseを返します)
				$wich = isset($player->wep_start) ? isset($player->wep_end) ? false : 'wep_end' : 'wep_start';
				if($wich !== false){
					$event->setCancelled();
					$position = $event->getBlock()->asPosition();
					//始点か終点の座標を登録する
					$player->$wich = [
						'x' => $x = $position->x,
						'y' => $y = $position->y,
						'z' => $z = $position->z,
						'level' => $position->getLevel()
					];
					if($wich === 'wep_start'){
						$player->sendMessage("始点が設定されました $x, $y, $z");
					}elseif($wich === 'wep_end'){
						$start = $player->wep_start;
						$position_start = new Position($start['x'], $start['y'], $start['z'], $start['level']);
						$side = $this->getSide($position_start, $position);
						$player->sendMessage("終点が設定されました $x, $y, $z (".$side['x'] * $side['y'] * $side['z']."ブロック)");
					}
				}
			}
		}
	}


#####################################################################


	public function fill(Player $player, Position $start, Position $end, string $block, string $option = 'set', string $replace = ''){
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

							public function __construct($owner, Player $player, Position $start, Position $end, array $block, string $option, $replace){
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

							public function generator($owner, Player $player, Position $start, Position $end, string $option){
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
								$player_name = $player->getName();
								$owner->getServer()->broadcastMessage($player_name.'が/fillを実行しました ['.$side['x'] * $side['y'] * $side['z'].'ブロック]');
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
										}
									}
									//進行ゲージを進めます
									$round = round($gage += $meter);
									$owner->getServer()->broadcastTip("[$player_name] §l§a$round ％ 完了");
									#バックアップ返す予定
									yield;
								}
								//スケジューラーを止めます
								$this->getHandler()->cancel();
								unset($player->wep_scheduler);
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
						//ブロックの数で処理スピードを計算します
						$side = $this->getSide($start, $end);
						$period = ($side['y'] * $side['z']) / 300;
						//スケジューラーを実行します
						$player->wep_scheduler = $this->getScheduler()->scheduleRepeatingTask($task, $period);
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

	public function clone(Player $player, Position $start, Position $end, Position $destination, string $mask = 'replace', string $clone = 'normal', string $replace = ''){
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

							public function __construct($owner, Player $player, Position $start, Position $end, Position $destination, string $mask, string $clone, $replace){
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

							public function generator($owner, Player $player, Position $start, Position $end, Position $destination, string $mask, string $clone){
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
								$player_name = $player->getName();
								$owner->getServer()->broadcastMessage($player_name.'が/cloneを実行しました ['.$side['x'] * $side['y'] * $side['z'].'ブロック]');
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
										}
									}
									//進行ゲージを進めます
									$round = round($gage += $meter);
									$owner->getServer()->broadcastTip("[$player_name] §l§a$round ％ 完了");
									#バックアップ返す予定
									yield;
								}
								//スケジューラーを止めます
								$this->getHandler()->cancel();
								unset($player->wep_scheduler);
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
						//ブロックの数で処理スピードを計算します
						$side = $this->getSide($start, $end);
						$period = ($side['y'] * $side['z']) / 300;
						//スケジューラーを実行します
						$player->wep_scheduler = $this->getScheduler()->scheduleRepeatingTask($task, $period);
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


	//ブロックの情報を表示させる
	public function PlayerInteract(PlayerInteractEvent $event){
		$action = $event->getAction();
		if($action === 0 or $action === 1){
			$player = $event->getPlayer();
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


}