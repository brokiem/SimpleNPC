<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\commands;

use brokiem\snpc\SimpleNPC;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

class RcaCommand extends Command implements PluginOwned {

    public function __construct(string $name, private SimpleNPC $owner) {
        parent::__construct($name, "Execute command by player like sudo");
        $this->setPermission("simplenpc.rca");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return true;
        }

        if (count($args) < 2) {
            $sender->sendMessage(TextFormat::YELLOW . "Please enter a player and a command.");
            return true;
        }

        $player = $this->getOwningPlugin()->getServer()->getPlayerExact(array_shift($args));
        if ($player instanceof Player) {
            $this->getOwningPlugin()->getServer()->getCommandMap()->dispatch($player, trim(implode(" ", $args)));
            return true;
        }

        $sender->sendMessage(TextFormat::RED . "Player not found.");
        return true;
    }

    public function getOwningPlugin(): Plugin {
        return $this->owner;
    }
}