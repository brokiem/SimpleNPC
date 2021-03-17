<?php
declare(strict_types=1);

namespace brokiem\snpc\commands;

use brokiem\snpc\manager\NPCManager;
use brokiem\snpc\SimpleNPC;
use brokiem\snpc\task\async\CreateNPCTask;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class Commands extends PluginCommand
{
    public function __construct(string $name, Plugin $owner)
    {
        parent::__construct($name, $owner);
        $this->setPermission("snpc.*");
        $this->setDescription("SimpleNPC commands");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$this->testPermission($sender)) {
            $sender->sendMessage($this->getPermissionMessage());
            return true;
        }

        /** @var SimpleNPC $plugin */
        $plugin = $this->getPlugin();

        if (isset($args[0])) {
            switch ($args[0]) {
                case "spawn":
                case "add":
                    if (!$sender instanceof Player) {
                        $sender->sendMessage("Only player can run this command!");
                        return true;
                    }

                    if (!$sender->hasPermission("snpc.spawn")) {
                        $sender->sendMessage($this->getPermissionMessage());
                        return true;
                    }

                    if (isset($args[1])) {
                        if (in_array(strtolower($args[1]), $plugin->npcType, true)) {
                            if ($args[1] === SimpleNPC::ENTITY_HUMAN) {
                                if (isset($args[2])) {
                                    $plugin->getServer()->getAsyncPool()->submitTask(new CreateNPCTask($args[2], $sender));
                                } elseif (isset($args[3])) {
                                    $plugin->getServer()->getAsyncPool()->submitTask(new CreateNPCTask($args[2], $sender, $args[3]));
                                } elseif (isset($args[4])) {
                                    $plugin->getServer()->getAsyncPool()->submitTask(new CreateNPCTask($args[2], $sender, $args[3], $args[4]));
                                } else {
                                    $plugin->getServer()->getAsyncPool()->submitTask(new CreateNPCTask($args[2], $sender));
                                }
                            } else {
                                if (isset($args[2])) {
                                    NPCManager::createNPC($args[1], $sender, $args[2]);
                                } elseif (isset($args[3])) {
                                    NPCManager::createNPC($args[1], $sender, $args[2], $args[3]);
                                } else {
                                    NPCManager::createNPC($args[1], $sender);
                                }
                            }
                        } else {
                            $sender->sendMessage("Invalid entity type or entity not registered!");
                        }
                    } else {
                        $sender->sendMessage("Usage: /snpc spawn <type> optional: <nametag> <canWalk> <skinUrl>");
                    }
                    break;
                case "delete":
                case "remove":
                    if (!$sender->hasPermission("snpc.remove")) {
                        $sender->sendMessage($this->getPermissionMessage());
                        return true;
                    }

                    break;
                case "edit":
                case "manage":
                    if (!$sender->hasPermission("snpc.edit")) {
                        $sender->sendMessage($this->getPermissionMessage());
                        return true;
                    }

                    break;
                case "list":
                    if (!$sender->hasPermission("snpc.list")) {
                        $sender->sendMessage($this->getPermissionMessage());
                        return true;
                    }

                    break;
            }

        }

        return parent::execute($sender, $commandLabel, $args);
    }
}