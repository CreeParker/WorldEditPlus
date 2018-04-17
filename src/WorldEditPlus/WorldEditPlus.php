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
use pocketmine\Player;

use pocketmine\Server;
use pocketmine\scheduler\PluginTask;
use pocketmine\scheduler\ServerScheduler;
use pocketmine\item\ItemIds;
use pocketmine\level\Position;
use pocketmine\level\Level;

use RuinPray\ui\UI;
use RuinPray\ui\elements\Dropdown;
use RuinPray\ui\elements\Input;
use RuinPray\ui\elements\Label;
use RuinPray\ui\elements\Slider;
use RuinPray\ui\elements\StepSlider;
use RuinPray\ui\elements\Toggle;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;

class WorldEditPlus extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		define('SET', 'set');
		define('REPLACE', 'replace');
		define('OUTLINE', 'outline');
		define('HOLLOW', 'hollow');
		define('KEEP', 'keep');

		define('FILTERED', 'filtered');
		define('MASKED', 'masked');

		define('FORCE', 'force');
		define('MOVE', 'move');
		define('NORMAL', 'normal');

		$this->fill = [SET, OUTLINE, HOLLOW, KEEP, REPLACE];
		$this->mask = [REPLACE, FILTERED, MASKED];
		$this->clone = [NORMAL, FORCE, MOVE];

		$this->rand = [
			'fill' => mt_rand(),
			'clone' => mt_rand()
		];
	}

	public function onDataPacket(DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		if($packet instanceof ModalFormResponsePacket){
			$data = json_decode($packet->formData);
			if(isset($data)){
				$id = $packet->formId;
				$player = $event->getPlayer();
				$name = $player->getLowerCaseName();
				if($id == $this->rand['fill']){
					$position = $this->setting[$name]['position'];
					$this->fill($player, $position['start'], $position['end'], $data[0], $this->fill[$data[1]], $data[2]);
				}elseif($id == $this->rand['clone']){
					$position = $this->setting[$name]['position'];
					$level = $this->getServer()->getLevelByName($this->setting[$name]['list'][$data[3]]);
					if(isset($level)){
						$destination = new Position($data[0], $data[1], $data[2], $level);
						$this->clone($player, $position['start'], $position['end'], $destination, $this->mask[$data[4]], $this->clone[$data[5]], $data[6]);
					}else{
						$player->sendMessage('ワールドが存在しません');
					}
				}
			}
		}
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		#var_dump($args[0], $args[1], $args[0] <= $args[1]);
 		if($sender instanceof Player){
			$name = $sender->getLowerCaseName();
 			switch($label){
 				case 'fill':
					if(!isset($this->setting[$name]['scheduler'])){
						if(isset($this->setting[$name]['position']['start'], $this->setting[$name]['position']['end'])){
	 						$form = UI::createCustomForm($this->rand['fill']);
	 						$form->setTitle('/fill コントローラー');
	 						$form->addContent((new Input)->text('設置ブロック')->placeholder('ID 名前(カンマ(,)区切りでランダム設置)'));
							$form->addContent((new Dropdown)->text(
								"オプション\n".
								"* set : 全て設置ブロックにする\n".
								"* outline : 外側を設置ブロックにする\n".
								"* hollow : 内側を空気に外側を設置ブロックにする\n".
								"* keep : 空気を設置ブロックにする\n".
								"* replace : 置き換えブロックを設置ブロックにする"
							)->options($this->fill));
	 						$form->addContent((new Input)->text('置き換えブロック(replaceの時のみ)')->placeholder('ID 名前(カンマ(,)区切りで複数指定)'));
							UI::sendForm($sender, $form);
						}else{
							$sender->sendMessage('範囲を指定してください');
						}
					}else{
						$sender->sendMessage('実行中です');
					}
 					break;
 				case 'clone':
					if(!isset($this->setting[$name]['scheduler'])){
						if(isset($this->setting[$name]['position']['start'], $this->setting[$name]['position']['end'])){
		 					$form = UI::createCustomForm($this->rand['clone']);
		 					$form->setTitle('/clone コントローラー');
		 					$floor = $sender->floor();
		 					$form->addContent((new Input)->text('コピー先 X')->placeholder('int')->default($floor->x));
		 					$form->addContent((new Input)->text('コピー先 Y')->placeholder('int')->default($floor->y));
		 					$form->addContent((new Input)->text('コピー先 Z')->placeholder('int')->default($floor->z));
		 					$level = $sender->getLevel();
		 					$list = $this->getLevelList($level);
		 					$this->setting[$name]['list'] = $list;
		 					$form->addContent((new Dropdown)->text('コピー先 ワールド')->options($list));
							$form->addContent((new Dropdown)->text(
								"マスクモード\n".
								"* replace : 全てをコピー\n".
								"* filtered : 指定ブロックのみコピー\n".
								"* masked : 空気以外をコピー"
							)->options($this->mask));
							$form->addContent((new Dropdown)->text(
								"クローンモード\n".
								"* normal : 通常のモード\n".
								"* force : コピー元に重なっても強制実行\n".
								"* move : クローンしてコピー元を空気にします"
							)->options($this->clone));
		 					$form->addContent((new Input)->text('指定ブロック(マスクモードのfilteredの時のみ)')->placeholder('ID 名前(カンマ(,)区切りで複数指定)'));
							UI::sendForm($sender, $form);
						}else{
							$sender->sendMessage('範囲を指定してください');
						}
					}else{
						$sender->sendMessage('実行中です');
					}
 					break;
				case 'undo':
					if(!isset($this->setting[$name]['scheduler'])){
						if(!isset($args[0])) return false;
						$this->undo($sender, $args[0]);
					}else{
						$sender->sendMessage('実行中です');
					}
					break;
				case 'cancel':
					if(isset($this->setting[$name]['scheduler'])){
						$this->setting[$name]['scheduler']->cancel();
						$sender->addTitle('キャンセルされました');
						unset($this->setting[$name]['scheduler']);
					}else{
						$sender->sendMessage('実行中の処理はありません');
					}
					break;
				case 'e':
					unset($this->setting[$name]['position']);
					$sender->sendMessage('始点と終点を消去しました');
					break;
 			}
		}else{
			$sender->sendMessage('コンソールからは操作できません');
		}
		return true;
	}

	public function getLevelList($level){
		$levels = $this->getServer()->getLevels();
		$key = array_search($level, $levels);
		unset($levels[$key]);
		$list[] = $level->getName();
		foreach($levels as $value)
			$list[] = $value->getName();
		return $list;
	}

	public function BlockBreak(BlockBreakEvent $event){
		$this->setPosition($event);
	}

	public function BlockPlace(BlockPlaceEvent $event){
		$this->setPosition($event);
	}

	public function setPosition($event){
		$player = $event->getPlayer();
		if($player->isOp()){
			$id = $event->getItem()->getId();
			if($id == 19){
				$name = $player->getLowerCaseName();
				$setting = &$this->setting[$name]['position'];
				$point = isset($setting['start']) ? isset($setting['end']) ? false : 'end' : 'start';
				if($point !== false){
					$event->setCancelled();
					$position = $event->getBlock()->asPosition();
					$setting[$point] = $position;
					$x = $position->x;
					$y = $position->y;
					$z = $position->z;
					if($point == 'start'){
						$player->sendMessage("始点が設定されました $x, $y, $z");
					}elseif($point == 'end'){
						$range = $this->getRange($setting['start'], $setting['end']);
						$player->sendMessage("終点が設定されました $x, $y, $z ({$range['count']['total']}ブロック)");
					}
				}
			}
		}
	}

	public function getRange(Position $start, Position $end){
		$range['max']['x'] = max($start->x, $end->x);
		$range['max']['y'] = max($start->y, $end->y);
		$range['max']['z'] = max($start->z, $end->z);
		$range['min']['x'] = min($start->x, $end->x);
		$range['min']['y'] = min($start->y, $end->y);
		$range['min']['z'] = min($start->z, $end->z);
		$range['count']['x'] = ($range['max']['x'] - $range['min']['x']) + 1;
		$range['count']['y'] = ($range['max']['y'] - $range['min']['y']) + 1;
		$range['count']['z'] = ($range['max']['z'] - $range['min']['z']) + 1;
		$range['count']['total'] = $range['count']['x'] * $range['count']['y'] * $range['count']['z'];
		$range['next']['x'] = $range['count']['x'] == 1 ? 1 : $start->x <=> $end->x;
		$range['next']['y'] = $range['count']['y'] == 1 ? 1 : $start->y <=> $end->y;
		$range['next']['z'] = $range['count']['z'] == 1 ? 1 : $start->z <=> $end->z;
		return $range;
	}

	public function fromString(string $string){
		$explode = explode(',', $string);
		if($explode === false)
			return false;
		try{
			foreach($explode as $value){
				$item = Item::fromString($value);
				$block = $item->getBlock();
				$item_name = $item->getName();
				$block_name = $block->getName();
				if($item_name != $block_name) return false;
				$blocks[$block_name] = $block;
			}
			return $blocks;
		}catch(\Exception $e){
			return false;
		}
	}

	public function fill(Player $player, Position $start, Position $end, string $block, string $option = SET, string $replace = ''){
		switch($option){
			case SET:
			case REPLACE:
			case OUTLINE:
			case HOLLOW:
			case KEEP:
				$level_start = $start->getLevel();
				$level_end   = $end->getLevel();
				if($level_start == $level_end){
					$block = $this->fromString($block);
					$replace = $option == REPLACE ? $this->fromString($replace) : true; 
					if($block !== false and $replace !== false){
						$range = $this->getRange($start, $end);
						$name = $player->getLowerCaseName();
						$scheduler = new class($this, $player, $start, $end, $block, $option, $replace, $range) extends PluginTask{

							public $a = 0;

							public function __construct($owner, $player, $start, $end, $block, $option, $replace, $range){
								parent::__construct($owner);
								$this->player = $player;
								$this->start = $start;
								$this->block = $block;
								$this->option = $option;
								$this->replace = $replace;

								$name = $player->getLowerCaseName();
								$this->setting = &$owner->setting[$name];

								$number = isset($this->setting['undo']) ? count($this->setting['undo']) : 0;
								$this->undo = &$this->setting['undo'][$number];
								$this->undo['start'] = $start;
								$this->undo['end'] = $end;

								$this->level = $start->getLevel();
								$this->max = $range['max'];
								$this->min = $range['min'];
								$this->count = $range['count'];
								$this->next = $range['next'];

								$this->meter = 100 / $range['count']['x'];
								$this->gage = 0;

								$this->air = Block::get(0);

								$owner->getServer()->broadcastMessage("{$name}がブロック変更を実行");
								$player->sendMessage("バックアップナンバー($number)");
							}

							public function onRun(int $tick){
								if(abs($this->a) < $this->count['x']){
									$x = $this->start->x + $this->a;
									for($b = 0; abs($b) < $this->count['y']; $b -= $this->next['y']){
										$y = $this->start->y + $b;
										if($y < 0 or $y > Level::Y_MAX)
											continue;
										for($c = 0; abs($c) < $this->count['z']; $c -= $this->next['z']){
											$z = $this->start->z + $c;
											if(!$this->level->isChunkLoaded($x >> 4, $z >> 4))
												$this->level->loadChunk($x >> 4, $z >> 4, true);
											$vector = new Vector3($x, $y, $z);
											$block_old = $this->level->getBlock($vector);
											$option = $this->option;
											$block_new = $this->$option($block_old);
											if($block_new !== false){
												$name_old = $block_old->getName();
												$name_new = $block_new->getName();
												if($name_old != $name_new)
													$this->level->setBlock($vector, $block_new, true, false);

												$this->undo['backup'][$x][] = $block_old;

											}
										}
									}
									$this->a -= $this->next['x'];
									$round = round($this->gage += $this->meter);
									$this->player->addTitle($round.'% 完了');
								}else{
									$this->getHandler()->cancel();
									unset($this->setting['scheduler']);
								}
							}

							public function set($block = null){
								$rand = array_rand($this->block);
								return $this->block[$rand];
							}

							public function replace($block){
								$name = $block->getName();
								return isset($this->replace[$name]) ? $this->set() : false;
							}

							public function outline($block){
								$x = $block->x;
								$y = $block->y;
								$z = $block->z;
								if($x != $this->max['x'] and $x != $this->min['x'])
									if($y != $this->max['y'] and $y != $this->min['y'])
										if($z != $this->max['z'] and $z != $this->min['z'])
											return false;
								return $this->set();
							}

							public function hollow($block){
								$x = $block->x;
								$y = $block->y;
								$z = $block->z;
								if($x != $this->max['x'] and $x != $this->min['x'])
									if($y != $this->max['y'] and $y != $this->min['y'])
										if($z != $this->max['z'] and $z != $this->min['z'])
											return $this->air;
								return $this->set();
							}

							public function keep($block){
								$name = $block->getName();
								$air_name = $this->air->getName();
								return $name == $air_name ? $this->set() : false;
							}

						};
						$ceil = ceil(($range['count']['y'] * $range['count']['z']) / 300);
						$this->setting[$name]['scheduler'] = $this->getServer()->getScheduler()->scheduleRepeatingTask($scheduler, $ceil);
					}else{
						$player->sendMessage('無効なブロックが含まれています');
					}
				}else{
					$player->sendMessage('同じワールドで指定してください');
				}
				break;
			default:
				$player->sendMessage('無効なオプションです');
				break;
		}
	}

	public function clone(Player $player, Position $start, Position $end, Position $destination, string $mask = REPLACE, string $clone = NORMAL, string $replace = ''){
		switch($mask){
			case FILTERED:
			case MASKED:
			case REPLACE:
				switch($clone){
					case FORCE:
					case MOVE:
					case NORMAL:
						$level_start = $start->getLevel();
						$level_end = $end->getLevel();
						if($level_start == $level_end){
							$replace = $mask == FILTERED ? $this->fromString($replace) : true; 
							if($replace !== false){
								$range = $this->getRange($start, $end);
								$name = $player->getLowerCaseName();
								$scheduler = new class($this, $player, $start, $end, $destination, $mask, $clone, $replace, $range) extends PluginTask{

									public $a = 0;

									public function __construct($owner, $player, $start, $end, $destination, $mask, $clone, $replace, $range){
										parent::__construct($owner);
										$this->player = $player;
										$this->start = $start;
										$this->destination = $destination->floor();
										$this->mask = $mask;
										$this->clone = $clone;
										$this->replace = $replace;

										$name = $player->getLowerCaseName();
										$this->setting = &$owner->setting[$name];

										$number = isset($this->setting['undo']) ? count($this->setting['undo']) : 0;
										$this->undo = &$this->setting['undo'][$number];
										$this->undo['start'] = $start;
										$this->undo['end'] = $end;

										$this->level_old = $start->getLevel();
										$this->level_new = $destination->getLevel();
										$this->max = $range['max'];
										$this->min = $range['min'];
										$this->count = $range['count'];
										$this->next = $range['next'];

										$this->meter = 100 / $range['count']['x'];
										$this->gage = 0;

										$this->air = Block::get(0);

										$owner->getServer()->broadcastMessage("{$name}がクローンを実行");
										$player->sendMessage("バックアップナンバー($number)");
									}

									public function onRun(int $tick){
										if(abs($this->a) < $this->count['x']){
											$x = $this->start->x + $this->a;
											$xd = $this->destination->x + $this->a;
											for($b = 0; abs($b) < $this->count['y']; $b -= $this->next['y']){
												$y = $this->start->y + $b;
												$yd = $this->destination->y + $b;
												if($y < 0 or $y > Level::Y_MAX or $yd < 0 or $yd > Level::Y_MAX)
													continue;
												for($c = 0; abs($c) < $this->count['z']; $c -= $this->next['z']){
													$z = $this->start->z + $c;
													$zd = $this->destination->z + $c;
													if(!$this->level_old->isChunkLoaded($x >> 4, $z >> 4))
														$this->level_old->loadChunk($x >> 4, $z >> 4, true);
													if(!$this->level_new->isChunkLoaded($xd >> 4, $zd >> 4))
														$this->level_new->loadChunk($xd >> 4, $zd >> 4, true);
													$vector_old = new Vector3($x, $y, $z);
													$block_new = $this->level_old->getBlock($vector_old);
													$mask = $this->mask;
													if($this->$mask($block_new)){
														$clone = $this->clone;
														if($this->$clone($vector_old, $xd, $yd, $zd)){
															$vector_new = new Vector3($xd, $yd, $zd);

															$block_old = $this->level_new->getBlock($vector_new);
															$this->undo['backup'][$x][] = $block_old;

															$this->level_new->setBlock($vector_new, $block_new, true, false);
														}
													}

												}
											}
											$this->a -= $this->next['x'];
											$round = round($this->gage += $this->meter);
											$this->player->addTitle($round.'% 完了');
										}else{
											$this->getHandler()->cancel();
											unset($this->setting['scheduler']);
										}
									}

									public function replace($block){
										return true;
									}

									public function filtered($block){
										$name = $block->getName();
										return isset($this->replace[$name]);
									}

									public function masked($block){
										$name = $block->getName();
										$air_name = $this->air->getName();
										return $name != $air_name;
									}

									public function normal($vector, $x, $y, $z){
										if($this->level_old == $this->level_new){
											if($x >= $this->min['x'] and $x <= $this->max['x'])
												if($y >= $this->min['y'] and $y <= $this->max['y'])
													if($z >= $this->min['z'] and $z <= $this->max['z'])
														return false;
										}
										return true;
									}

									public function force($vector, $x, $y, $z){
										return true;
									}

									public function move($vector, $x, $y, $z){
										$this->level_old->setBlock($vector, $this->air, true, false);
										return true;
									}

								};
								$ceil = ceil(($range['count']['y'] * $range['count']['z']) / 300);
								$this->setting[$name]['scheduler'] = $this->getServer()->getScheduler()->scheduleRepeatingTask($scheduler, $ceil);
							}else{
								$player->sendMessage('無効なブロックが含まれています');
							}
						}else{
							$player->sendMessage('同じワールドで指定してください');
						}
						break;
					default:
						$player->sendMessage('無効なクローンモードです');
						break;
				}
				break;
			default:
				$player->sendMessage('無効なマスクモードです');
				break;
		}
	}

	public function undo(Player $player, $number){
		$name = $player->getLowerCaseName();
		$undo = $this->setting[$name]['undo'][$number] ?? false;
		if($undo !== false){
			$range = $this->getRange($undo['start'], $undo['end']);
			$scheduler = new class($this, $player, $undo, $range) extends PluginTask{

				public function __construct($owner, $player, $undo, $range){
					parent::__construct($owner);
					$this->player = $player;
					$this->backup = $undo['backup'];

					$name = $player->getLowerCaseName();
					$this->setting = &$owner->setting[$name];

					$this->meter = 100 / count($this->backup);
					$this->gage = 0;

					$owner->getServer()->broadcastMessage("{$name}が復元を実行");
				}

				public function onRun(int $tick){
					if(!empty($this->backup)){
						$shift = array_shift($this->backup);
						foreach($shift as $value){
							$level = $value->getLevel();
							$vector = $value->asVector3();
							$level->setBlock($vector, $value, true, false);
						}
						$round = round($this->gage += $this->meter);
						$this->player->addTitle($round.'% 完了');
					}else{
						$this->getHandler()->cancel();
						unset($this->setting['scheduler']);
					}
				}

			};

			$ceil = ceil(($range['count']['y'] * $range['count']['z']) / 300);
			$this->setting[$name]['scheduler'] = $this->getServer()->getScheduler()->scheduleRepeatingTask($scheduler, $ceil);
		}else{
			$player->sendMessage($number.'のバックアップがありません');
		}
	}

	public function PlayerInteract(PlayerInteractEvent $event){
		$action = $event->getAction();
		if($action === 0 or $action === 1){
			$player = $event->getPlayer();
			if($player->isOp()){
				$id = $event->getItem()->getId();
				$block = $event->getBlock();
				if($id === 345){
					$x = $block->x;
					$y = $block->y;
					$z = $block->z;
					$level = $block->getLevel()->getName();
					$player->sendMessage("このブロックの座標を取得しました $x, $y, $z ($level)");
				}elseif($id === 347){
					$name = $block->getName();
					$id = $block->getId();
					$meta = $block->getDamage();
					$player->sendMessage("このブロックの情報を取得しました $name ($id:$meta)");
				}
			}
		}
	}

}