<?php

declare(strict_types=1);

namespace brokiem\snpc\commands;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class RcaCommand extends PluginCommand {

    public function __construct(string $name, Plugin $owner){
        parent::__construct($name, $owner);
        $this->setPermission("snpc.rca");
        $this->setDescription("Execute command by player like sudo");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
        if(!$this->testPermission($sender)){
            return true;
        }

        if(count($args) < 2){
            $sender->sendMessage(TextFormat::YELLOW . "Please enter a player and a command.");
            return true;
        }

        $player = $this->getPlugin()->getServer()->getPlayerExact(array_shift($args));
        if($player instanceof Player){
            $this->getPlugin()->getServer()->getCommandMap()->dispatch($player, trim(implode(" ", $args)));
            return true;
        }

        $sender->sendMessage(TextFormat::RED . "Player not found.");
        return parent::execute($sender, $commandLabel, $args);
    }
}