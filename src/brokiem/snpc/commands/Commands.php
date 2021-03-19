<?php

declare(strict_types=1);

namespace brokiem\snpc\commands;

use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\manager\NPCManager;
use brokiem\snpc\SimpleNPC;
use brokiem\snpc\task\async\SpawnHumanNPCTask;
use EasyUI\element\Button;
use EasyUI\element\Input;
use EasyUI\utils\FormResponse;
use EasyUI\variant\CustomForm;
use EasyUI\variant\SimpleForm;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
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
        $this->setDescription("SimpleNPC commands");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$this->testPermission($sender)) {
            return true;
        }

        /** @var SimpleNPC $plugin */
        $plugin = $this->getPlugin();

        if (isset($args[0])) {
            switch ($args[0]) {
                case "help":
                    $sender->sendMessage("§7---- ---- [ §3SimpleNPC§7 ] ---- ----\n§bAuthor: @brokiem\n§3Source Code: github.com/brokiem/SimpleNPC\nVersion " . $this->getPlugin()->getDescription()->getVersion() . "\n\n§eCommand List:\n§2» /snpc spawn <type> <nametag> <canWalk> <skinUrl>\n§2» /snpc edit <id>\n§2» /snpc remove <id>\n§2» /snpc migrate <confirm | cancell>\n§2» /snpc list\n§7---- ---- ---- - ---- ---- ----");
                    break;
                case "spawn":
                case "add":
                    if (!$sender instanceof Player) {
                        $sender->sendMessage("Only player can run this command!");
                        return true;
                    }

                    if (!$sender->hasPermission("snpc.spawn")) {
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
                        return true;
                    }

                    if (isset($args[1]) and is_numeric($args[1])) {
                        $entity = $plugin->getServer()->findEntity((int)$args[1]);

                        if (!$entity instanceof BaseNPC or !$entity instanceof CustomHuman) {
                            $sender->sendMessage(TextFormat::YELLOW . "SimpleNPC Entity with ID: " . $args[1] . " not found!");
                            return true;
                        }

                        if (!$entity->isFlaggedForDespawn()) {
                            $entity->flagForDespawn();
                            $sender->sendMessage(TextFormat::GREEN . "The NPC was successfully removed!");
                        }
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
                    if (!$sender->hasPermission("snpc.edit") or !$sender instanceof Player) {
                        return true;
                    }

                    if (!isset($args[1]) or !is_numeric($args[1])) {
                        $sender->sendMessage(TextFormat::RED . "Usage: /snpc edit <id>");
                        return true;
                    }

                    $entity = $plugin->getServer()->findEntity((int)$args[1]);
                    if (!$entity instanceof BaseNPC or !$entity instanceof CustomHuman) {
                        $sender->sendMessage(TextFormat::YELLOW . "SimpleNPC Entity with ID: " . $args[1] . " not found!");
                        return true;
                    }

                    $addcmdForm = new CustomForm("Add Command");
                    $addcmdForm->addElement("addcmd", new Input("Enter the command here"));

                    $addcmdForm->setSubmitListener(function (Player $player, FormResponse $response) use ($entity, $addcmdForm) {
                        $submittedText = $response->getInputSubmittedText("addcmd");

                        if ($submittedText === "") {
                            $player->sendMessage(TextFormat::RED . "Plese enter a valid command!");
                            $player->sendForm($addcmdForm);
                        } else {
                            $commands = $entity->namedtag->getCompoundTag("Commands") ?? new CompoundTag("Commands");

                            if ($commands->hasTag($submittedText)) {
                                $player->sendMessage(TextFormat::RED . "'" . $submittedText . "' command has already been added.");
                                return;
                            }

                            $commands->setString($submittedText, $submittedText);
                            $entity->namedtag->setTag($commands);
                            $player->sendMessage(TextFormat::GREEN . "Successfully added command " . $submittedText . " to entity ID: " . $entity->getId());
                        }
                    });

                    $editForm = new SimpleForm("Manage NPC", "§aID:§2 $args[1]\nClass: §2" . get_class($entity) . "\nNametag: §2" . $entity->getNameTag() . "\nPosition: §2" . $entity->getX() . "/" . $entity->getY() . "/" . $entity->getZ());
                    $editForm->addButton(new Button("Add Command", null, function (Player $sender) use ($addcmdForm) {
                        $sender->sendForm($addcmdForm);
                    }));

                    $sender->sendForm($editForm);
                    break;
                case "migrate":
                    if (!$sender->hasPermission("snpc.migrate")) {
                        return true;
                    }

                    if (!$sender instanceof Player) {
                        return true;
                    }

                    if ($plugin->getServer()->getPluginManager()->getPlugin("Slapper") !== null) {
                        if (!isset($args[1]) && !isset($plugin->migrateNPC[$sender->getName()])) {
                            $plugin->migrateNPC[$sender->getName()] = true;

                            $plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($plugin, $sender): void {
                                if (isset($plugin->migrateNPC[$sender->getName()])) {
                                    unset($plugin->migrateNPC[$sender->getName()]);
                                    $sender->sendMessage(TextFormat::YELLOW . "Migrating NPC Cancelled! (10 seconds)");
                                }
                            }), 10 * 20);

                            $sender->sendMessage(TextFormat::RED . " \nAre you sure want to migrate your NPC from Slapper to SimpleNPC? \nThis will replace the slapper NPCs with the new Simple NPCs\n\nIf yes, run /migrate confirm, if no, run /migrate cancel\n\n ");
                            $sender->sendMessage(TextFormat::RED . "NOTE: Make sure all the worlds with the Slapper NPC have been loaded!");
                            return true;
                        }

                        if (isset($plugin->migrateNPC[$sender->getName()], $args[1]) && $args[1] === "confirm") {
                            unset($plugin->migrateNPC[$sender->getName()]);
                            $sender->sendMessage(TextFormat::DARK_GREEN . "Migrating NPC... Please wait...");

                            foreach ($plugin->getServer()->getLevels() as $level) {
                                $entity = array_map(static function (Entity $entity) {
                                }, array_filter($level->getEntities(), static function (Entity $entity): bool {
                                    return $entity instanceof SlapperHuman or $entity instanceof SlapperEntity;
                                }));

                                if (count($entity) === 0) {
                                    $sender->sendMessage(TextFormat::RED . "Migrating failed: No Slapper entity found!");
                                    return true;
                                }

                                $error = 0;
                                foreach ($level->getEntities() as $entity) {
                                    if ($entity instanceof SlapperEntity) {
                                        /** @phpstan-ignore-next-line */
                                        if (NPCManager::createNPC(AddActorPacket::LEGACY_ID_MAP_BC[$entity::TYPE_ID], $sender, $entity->getNameTag(), $entity->namedtag->getCompoundTag("Commands"))) {
                                            if (!$entity->isFlaggedForDespawn()) {
                                                $entity->flagForDespawn();
                                            }
                                        } else {
                                            ++$error;
                                        }
                                    } elseif ($entity instanceof SlapperHuman) {
                                        $plugin->getServer()->getAsyncPool()->submitTask(new SpawnHumanNPCTask($entity->getNameTag(), $sender->getName(), $plugin->getDataFolder(), false, null, $entity->namedtag->getCompoundTag("Commands"), $entity->getSkin(), $entity->getLocation()));
                                        // TODO: QueueSytem (don't spam async task)
                                        if (!$entity->isFlaggedForDespawn()) {
                                            $entity->flagForDespawn();
                                        }
                                    }
                                }

                                if ($error === 0) {
                                    $sender->sendMessage(TextFormat::GREEN . "The migration was successful, you can safely remove the Slapper plugin now");
                                } else {
                                    $sender->sendMessage(TextFormat::RED . "(" . $error . " error found) " . TextFormat::YELLOW . "It seems that the migration is not going well, please fix the error so that it can be fully migrated. Don't delete Slapper Plugin now");
                                }
                            }

                            return true;
                        }

                        if (isset($plugin->migrateNPC[$sender->getName()], $args[1]) && $args[1] === "cancel") {
                            $sender->sendMessage(TextFormat::GREEN . "Migrating NPC cancelled!");
                            unset($plugin->migrateNPC[$sender->getName()]);
                            return true;
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Slapper plugin is missing, cannnot migrating.");
                    }
                    break;
                case "list":
                    if (!$sender->hasPermission("snpc.list")) {
                        return true;
                    }

                    foreach ($plugin->getServer()->getLevels() as $world) {
                        $entityNames = array_map(static function (Entity $entity): string {
                            return TextFormat::YELLOW . "ID: (" . $entity->getId() . ") " . TextFormat::DARK_GREEN . $entity->getNameTag() . " §7-- §3" . $entity->getFloorX() . "/" . $entity->getFloorY() . "/" . $entity->getFloorZ();
                        }, array_filter($world->getEntities(), static function (Entity $entity): bool {
                            return $entity instanceof BaseNPC or $entity instanceof CustomHuman;
                        }));

                        $sender->sendMessage("§csNPC List and Location: (" . count($entityNames) . ")\n §3- " . implode("\n - ", $entityNames));
                    }
                    break;
            }
        } else {
            $sender->sendMessage("§7---- ---- [ §3SimpleNPC§7 ] ---- ----\n§bAuthor: @brokiem\n§3Source Code: github.com/brokiem/SimpleNPC\nVersion " . $this->getPlugin()->getDescription()->getVersion() . "\n\n§eCommand List:\n§2» /snpc spawn <type> <nametag> <canWalk> <skinUrl>\n§2» /snpc edit <id>\n§2» /snpc remove <id>\n§2» /snpc migrate <confirm | cancell>\n§2» /snpc list\n§7---- ---- ---- - ---- ---- ----");
        }

        return parent::execute($sender, $commandLabel, $args);
    }
}