<?php

namespace Dark\Warp;

use pocketmine\{
	Player,
	command\Command,
	command\CommandSender,
	plugin\PluginBase,
	utils\Config,
	utils\TextFormat as C,
	level\Position,
};
class Main extends PluginBase
{
	public function onEnable(){
		$this->saveDefaultConfig();
		$this->saveResource("warp_data.yml");
		$this->updateData();
	}
	
	public function updateData():void{
		$data = new Config($this->getDataFolder()."warp_data.yml", Config::YAML);
		foreach($data->getAll() as $name => $datas){
			if(!$this->getServer()->isLevelGenerated($datas["level"])){
				$data->removeNested($name);
				$data->save();
				break;
			}
		}
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args):bool{
		if(!$sender instanceof Player) return false;
		$this->updateData();
		$data = new Config($this->getDataFolder()."warp_data.yml", Config::YAML);
		$usage = $this->getConfig()->get("usage");
		if(!isset($args[0])){
			$sender->sendMessage($usage);
			return false;
		}
		if(!in_array($args[0], ["list", "addwarp", "deletewarp"])){
			$name = strtolower($args[0]);
			if($data->exists($name)){
				if(!$sender->hasPermission("warp.command.".$name)){
					$sender->sendMessage(str_replace("{name}", $args[0], $this->getConfig()->get("cant_warp")));
					return false;
				}
				$x = $data->get($name)["x"];
				$y = $data->get($name)["y"];
				$z = $data->get($name)["z"];
				if(!$this->getServer()->isLevelLoaded($data->get($name)["level"])) $this->getServer()->loadLevel($data->get($name)["level"]);
				$level = $this->getServer()->getLevelByName($data->get($name)["level"]);
				$sender->teleport(new Position($x, $y, $z, $level));
				$sender->sendMessage(str_replace("{name}", ucfirst($name), $this->getConfig()->get("teleported")));
			}else{
				$sender->sendMessage(str_replace("{name}", $name, $this->getConfig()->get("warp_not_found")));
				return false;
			}
		}else{
			switch(strtolower($args[0])){
				case "list":
					$text = $this->getConfig()->get("list_header");
					foreach($data->getAll() as $name => $data){
						$text .= str_replace("{names}", ucfirst($name), $this->getConfig()->get("list_context"));
					}
					$sender->sendMessage($text);
				break;
				case "addwarp":
				case "createwarp":
					if(!isset($args[1])){
						$sender->sendMessage(C::YELLOW . "Usage: /warp addwarp <name>");
						return false;
					}
					if(preg_match('/[\'^£$%&*()}{@#~?><>,.|=_+¬-]/', $args[1]) || $args[1] == trim($args[1]) && strpos($args[1], ' ') !== false){
						$sender->sendMessage(C::RED . "Warp name must no space and special characters");
						return false;
					}
					if($data->exists(strtolower($args[1]))){
						$sender->sendMessage($this->getConfig()->get("warp_exists"));
						return false;
					}
					$name = strtolower($args[1]);
					$data->setNested($name.".x", $sender->getX());
					$data->setNested($name.".y", $sender->getY());
					$data->setNested($name.".z", $sender->getZ());
					$data->setNested($name.".level", $sender->getLevel()->getName());
					$data->save();
					$sender->sendMessage(str_replace("{name}", ucfirst($name), $this->getConfig()->get("warp_created")));
				break;
				case "deletewarp":
				case "delwarp":
				case "removewarp":
					if(!isset($args[1])){
						$sender->sendMessage(C::YELLOW . "Usage: /warp addwarp <name>");
						return false;
					}
					if(!$data->exists(strtolower($args[1]))){
						$sender->sendMessage($this->getConfig()->get("warp_not_found"));
						return false;
					}
					$data->removeNested(strtolower($args[1]));
					$data->save();
					$sender->sendMessage(str_replace("{name}", ucfirst(strtolower($args[1])), $this->getConfig()->get("warp_deleted")));
				break;
			}
		}
		return true;
	}
}
