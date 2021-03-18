<?php

declare(strict_types=1);

namespace brokiem\snpc\commands;

use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\manager\NPCManager;
use brokiem\snpc\SimpleNPC;
use brokiem\snpc\task\async\SpawnHumanNPCTask;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use slapper\entities\SlapperEntity;
use slapper\entities\SlapperHuman;

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
                        if (in_array(strtolower($args[1]), SimpleNPC::$npcType, true)) {
                            if ($args[1] === SimpleNPC::ENTITY_HUMAN) {
                                if (isset($args[4])) {
                                    if (!preg_match('/https?:\/\/[^?]*\.png(?![\w.\-_])/', $args[4])) {
                                        $sender->sendMessage(TextFormat::RED . "Invalied skin file format! (Only PNG Supported)");
                                        return true;
                                    }

                                    $plugin->getServer()->getAsyncPool()->submitTask(new SpawnHumanNPCTask($args[2], $sender->getName(), $plugin->getDataFolder(), $args[3] === "true", $args[4]));
                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC with nametag $args[2] for you...");
                                    return true;
                                }

                                if (isset($args[3])) {
                                    $plugin->getServer()->getAsyncPool()->submitTask(new SpawnHumanNPCTask($args[2], $sender->getName(), $plugin->getDataFolder(), $args[3] === "true"));
                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC with nametag $args[2] for you...");
                                    return true;
                                }

                                if (isset($args[2])) {
                                    $plugin->getServer()->getAsyncPool()->submitTask(new SpawnHumanNPCTask($args[2], $sender->getName(), $plugin->getDataFolder()));
                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC with nametag $args[2] for you...");
                                    return true;
                                }

                                $plugin->getServer()->getAsyncPool()->submitTask(new SpawnHumanNPCTask(null, $sender->getName(), $plugin->getDataFolder()));
                                $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC without nametag for you...");
                            } else {
                                if (isset($args[2])) {
                                    NPCManager::createNPC($args[1], $sender, $args[2]);
                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC with nametag $args[2] for you...");
                                    return true;
                                }

                                NPCManager::createNPC($args[1], $sender);
                                $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC without nametag for you...");
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Invalid entity type or entity not registered!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Usage: /snpc spawn <type> optional: <nametag> <canWalk> <skinUrl>");
                    }
                    break;
                case "delete":
                case "remove":
                    if (!$sender->hasPermission("snpc.remove")) {
                        $sender->sendMessage($this->getPermissionMessage());
                        return true;
                    }

                    if (!isset($plugin->removeNPC[$sender->getName()])) {
                        $plugin->removeNPC[$sender->getName()] = true;
                        $sender->sendMessage(TextFormat::DARK_GREEN . "Hit the npc that you want to delete or remove");
                    } else {
                        unset($plugin->removeNPC[$sender->getName()]);
                        $sender->sendMessage(TextFormat::GREEN . "Remove npc by hitting has been canceled");
                    }
                break;
                case "edit":
                case "manage":
                    if (!$sender->hasPermission("snpc.edit")) {
                        $sender->sendMessage($this->getPermissionMessage());
                        return true;
                    }

                    break;
                case "migrate":
                    if (!$sender instanceof Player) {
                        return true;
                    }

                    if ($plugin->getServer()->getPluginManager()->getPlugin("Slapper") !== null) {
                        if (!isset($args[1]) && !isset($plugin->migrateNPC[$sender->getName()])) {
                            $plugin->migrateNPC[$sender->getName()] = true;

                            $plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($plugin, $sender): void {
                                unset($plugin->migrateNPC[$sender->getName()]);
                            }), 100);

                            $sender->sendMessage(TextFormat::RED . " \nAre you sure want to migrate your NPC from Slapper to SimpleNPC? This will replace the slapper NPCs with the new Simple NPCs\nIf yes, run /migrate confirm, if no, run /migrate cancel\n");
                            $sender->sendMessage(TextFormat::RED . "NOTE: Make sure all the worlds with the Slapper NPC have been loaded!");
                            return true;
                        }

                        if (isset($plugin->migrateNPC[$sender->getName()], $args[1]) && $args[1] === "confirm") {
                            $sender->sendMessage(TextFormat::DARK_GREEN . "Migrating NPC... Please wait...");

                            foreach ($plugin->getServer()->getLevels() as $level) {
                                foreach ($level->getEntities() as $entity) {
                                    if ($entity instanceof SlapperEntity) {
                                        NPCManager::createNPC($entity::TYPE_ID, $sender, $entity->getNameTag(), $entity->namedtag->getCompoundTag("Commands"));
                                    } elseif ($entity instanceof SlapperHuman) {
                                        $plugin->getServer()->getAsyncPool()->submitTask(new SpawnHumanNPCTask($entity->getNameTag(), $sender->getName(), $plugin->getDataFolder(), false, null, $entity->namedtag->getCompoundTag("Commands"), $entity->getSkin()));
                                        // TODO: Queue (don't spam async task)
                                    }
                                }
                            }

                            unset($plugin->migrateNPC[$sender->getName()]);
                            return true;
                        }

                        if (isset($plugin->migrateNPC[$sender->getName()], $args[1]) && $args[1] === "cancel") {
                            $sender->sendMessage(TextFormat::GREEN . "Migrating NPC cancelled!");
                            unset($plugin->migrateNPC[$sender->getName()]);
                            return true;
                        }
                    }
                    break;
                case "list":
                    if (!$sender->hasPermission("snpc.list")) {
                        $sender->sendMessage($this->getPermissionMessage());
                        return true;
                    }

                    foreach ($plugin->getServer()->getLevels() as $world) {
                        $entityNames = array_map(static function (Entity $entity): string {
                            return TextFormat::DARK_GREEN . $entity->getNameTag() . " §d-- §3X:" . $entity->getFloorX() . " Y:" . $entity->getFloorY() . " Z:" . $entity->getFloorZ();
                        }, array_filter($world->getEntities(), static function (Entity $entity): bool {
                            return $entity instanceof BaseNPC or $entity instanceof CustomHuman;
                        }));

                        $sender->sendMessage("§csNPC List and Location: (" . count($entityNames) . ")\n §3- " . implode("\n - ", $entityNames));
                    }
                    break;
            }
        }

        return parent::execute($sender, $commandLabel, $args);
    }
}