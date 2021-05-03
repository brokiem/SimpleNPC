<?php

declare(strict_types=1);

namespace brokiem\snpc\commands;

use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\manager\form\FormManager;
use brokiem\snpc\manager\NPCManager;
use brokiem\snpc\SimpleNPC;
use brokiem\snpc\task\async\SkinURLToNPCTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class Commands extends Command implements PluginIdentifiableCommand {

    /** @var SimpleNPC */
    private $plugin;

    public function __construct(string $name, SimpleNPC $owner) {
        $this->plugin = $owner;
        parent::__construct($name, "SimpleNPC commands");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return true;
        }

        /** @var SimpleNPC $plugin */
        $plugin = $this->getPlugin();

        if (isset($args[0])) {
            switch (strtolower($args[0])) {
                case "ui":
                    if (!$sender->hasPermission("simplenpc.ui")) {
                        $sender->sendMessage(TextFormat::RED . "You don't have permission");
                        return true;
                    }

                    if (!$sender instanceof Player) {
                        $sender->sendMessage("Only player can run this command");
                        return true;
                    }

                    FormManager::sendUIForm($sender);
                    break;
                case "reload":
                    if (!$sender->hasPermission("simplenpc.reload")) {
                        return true;
                    }

                    $plugin->initConfiguration();
                    $sender->sendMessage(TextFormat::GREEN . "SimpleNPC Config reloaded successfully!");
                    break;
                case "id":
                    if (!$sender->hasPermission("simplenpc.id")) {
                        return true;
                    }

                    if (!isset($plugin->idPlayers[$sender->getName()])) {
                        $plugin->idPlayers[$sender->getName()] = true;
                        $sender->sendMessage(TextFormat::DARK_GREEN . "Hit the npc that you want to see the ID");
                    } else {
                        unset($plugin->idPlayers[$sender->getName()]);
                        $sender->sendMessage(TextFormat::GREEN . "Tap to get NPC ID has been canceled");
                    }
                    break;
                case "spawn":
                case "add":
                    if (!$sender instanceof Player) {
                        $sender->sendMessage("Only player can run this command!");
                        return true;
                    }

                    if (!$sender->hasPermission("simplenpc.spawn")) {
                        return true;
                    }

                    if (isset($args[1])) {
                        if (in_array(strtolower($args[1]) . "_snpc", SimpleNPC::$npcType, true)) {
                            if (strtolower($args[1]) . "_snpc" === SimpleNPC::ENTITY_HUMAN) {
                                if (isset($args[4])) {
                                    if (!preg_match('/https?:\/\/[^?]*\.png(?![\w.\-_])/', $args[4])) {
                                        $sender->sendMessage(TextFormat::RED . "Invalid skin url file format! (Only PNG Supported)");
                                        return true;
                                    }
                                    $plugin->getServer()->getAsyncPool()->submitTask(new SkinURLToNPCTask($args[2], $sender->getName(), $plugin->getDataFolder(), $args[3] === "true", $args[4]));
                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC with nametag $args[2] for you...");
                                } elseif (isset($args[3])) {
                                    $plugin->getServer()->getAsyncPool()->submitTask(new SkinURLToNPCTask($args[2], $sender->getName(), $plugin->getDataFolder(), $args[3] === "true"));
                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC with nametag $args[2] for you...");
                                } elseif (isset($args[2])) {
                                    $plugin->getServer()->getAsyncPool()->submitTask(new SkinURLToNPCTask($args[2], $sender->getName(), $plugin->getDataFolder()));
                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC with nametag $args[2] for you...");
                                    return true;
                                }

                                $plugin->getServer()->getAsyncPool()->submitTask(new SkinURLToNPCTask(null, $sender->getName(), $plugin->getDataFolder()));
                            } else {
                                if (isset($args[2])) {
                                    NPCManager::createNPC(strtolower($args[1]) . "_snpc", $sender, $args[2]);
                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC with nametag $args[2] for you...");
                                    return true;
                                }
                                NPCManager::createNPC(strtolower($args[1]) . "_snpc", $sender);
                            }
                            $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC without nametag for you...");
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Invalid entity type or entity not registered!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Usage: /snpc spawn <type> optional: <nametag> <canWalk> <skinUrl>");
                    }
                    break;
                case "delete":
                case "remove":
                    if (!$sender->hasPermission("simplenpc.remove")) {
                        return true;
                    }
                    if (isset($args[1]) && is_numeric($args[1])) {
                        $entity = $plugin->getServer()->findEntity((int)$args[1]);

                        if ($entity instanceof BaseNPC || $entity instanceof CustomHuman) {
                            if (NPCManager::removeNPC($entity->namedtag->getString("Identifier"), $entity)) {
                                $sender->sendMessage(TextFormat::GREEN . "The NPC was successfully removed!");
                            } else {
                                $sender->sendMessage(TextFormat::YELLOW . "The NPC was failed removed! (File not found)");
                            }
                            return true;
                        }

                        $sender->sendMessage(TextFormat::YELLOW . "SimpleNPC Entity with ID: " . $args[1] . " not found!");
                        return true;
                    }

                    if (!isset($plugin->removeNPC[$sender->getName()])) {
                        $plugin->removeNPC[$sender->getName()] = true;
                        $sender->sendMessage(TextFormat::DARK_GREEN . "Hit the npc that you want to delete or remove");
                        return true;
                    }

                    unset($plugin->removeNPC[$sender->getName()]);
                    $sender->sendMessage(TextFormat::GREEN . "Remove npc by hitting has been canceled");
                    break;
                case "edit":
                case "manage":
                    if (!$sender->hasPermission("simplenpc.edit") or !$sender instanceof Player) {
                        return true;
                    }

                    if (!isset($args[1]) || !is_numeric($args[1])) {
                        $sender->sendMessage(TextFormat::RED . "Usage: /snpc edit <id>");
                        return true;
                    }

                FormManager::sendEditForm($sender, $args, (int)$args[1]);
                    break;
                case "migrate":
                    if (!$sender->hasPermission("simplenpc.migrate")) {
                        return true;
                    }

                    if (!$sender instanceof Player) {
                        return true;
                    }

                    NPCManager::migrateNPC($sender, $args);
                    break;
                case "list":
                    if (!$sender->hasPermission("simplenpc.list")) {
                        return true;
                    }

                    foreach ($plugin->getServer()->getLevels() as $world) {
                        $entityNames = array_map(static function(Entity $entity): string {
                            return TextFormat::YELLOW . "ID: (" . $entity->getId() . ") " . TextFormat::GREEN . $entity->getNameTag() . " §7-- §b" . $entity->getLevelNonNull()->getFolderName() . ": " . $entity->getFloorX() . "/" . $entity->getFloorY() . "/" . $entity->getFloorZ();
                        }, array_filter($world->getEntities(), static function(Entity $entity): bool {
                            return $entity instanceof BaseNPC or $entity instanceof CustomHuman;
                        }));

                        $sender->sendMessage("§csNPC List and Location: (" . count($entityNames) . ")\n §f- " . implode("\n - ", $entityNames));
                    }
                    break;
                case "help":
                    $sender->sendMessage("\n§7---- ---- ---- - ---- ---- ----\n§eCommand List:\n§2» /snpc spawn <type> <nametag> <canWalk> <skinUrl>\n§2» /snpc edit <id>\n§2» /snpc reload\n§2» /snpc ui\n§2» /snpc remove <id>\n§2» /snpc migrate <confirm | cancel>\n§2» /snpc list\n§7---- ---- ---- - ---- ---- ----");
                    break;
                default:
                    $sender->sendMessage(TextFormat::RED . "Subcommand '$args[0]' not found! Try '/snpc help' for help.");
                    break;
            }
        } else {
            $sender->sendMessage("§7---- ---- [ §3SimpleNPC§7 ] ---- ----\n§bAuthor: @brokiem\n§3Source Code: github.com/brokiem/SimpleNPC\nVersion " . $this->getPlugin()->getDescription()->getVersion() . "\n§7---- ---- ---- - ---- ---- ----");
        }

        return true;
    }

    public function getPlugin(): Plugin {
        return $this->plugin;
    }
}