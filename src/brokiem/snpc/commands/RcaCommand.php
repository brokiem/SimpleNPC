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
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class RcaCommand extends Command implements PluginIdentifiableCommand {

    private SimpleNPC $plugin;

    public function __construct(string $name, SimpleNPC $owner) {
        $this->plugin = $owner;
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

        $player = $this->getPlugin()->getServer()->getPlayerExact(array_shift($args));
        if ($player instanceof Player) {
            $this->getPlugin()->getServer()->getCommandMap()->dispatch($player, trim(implode(" ", $args)));
            return true;
        }

        $sender->sendMessage(TextFormat::RED . "Player not found.");
        return true;
    }

    public function getPlugin(): Plugin {
        return $this->plugin;
    }
}