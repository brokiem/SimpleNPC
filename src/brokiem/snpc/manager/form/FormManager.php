<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\manager\form;

use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\entity\WalkingHuman;
use brokiem\snpc\manager\NPCManager;
use brokiem\snpc\SimpleNPC;
use brokiem\snpc\task\async\URLToCapeTask;
use brokiem\snpc\task\async\URLToSkinTask;
use EasyUI\element\Button;
use EasyUI\element\Dropdown;
use EasyUI\element\Input;
use EasyUI\element\Option;
use EasyUI\utils\FormResponse;
use EasyUI\variant\CustomForm;
use EasyUI\variant\SimpleForm;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

class FormManager {
    use SingletonTrait;

    public function sendUIForm(Player $sender): void {
        $form = new SimpleForm("Manage NPC");
        $simpleForm = new SimpleForm("Manage NPC");
        $cusForm = new CustomForm("Manage NPC");

        $plugin = SimpleNPC::getInstance();

        foreach (ButtonManager::getInstance()->getUIButtons() as $button) {
            $form->addButton(new Button($button["text"], $button["icon"], function(Player $sender) use ($simpleForm, $cusForm, $button, $plugin) {
                if ($button["function"] !== null) {
                    switch ($button["function"]) {
                        case "spawnNPC":
                            foreach (SimpleNPC::getInstance()->getRegisteredNPC() as $npcName => $saveNames) {
                                $simpleForm->addButton(new Button(ucfirst(str_replace("_snpc", " NPC", $npcName)), null, function(Player $player) use ($npcName, $saveNames, $cusForm) {
                                    $dropdown = new Dropdown("Selected NPC:");
                                    $dropdown->addOption(new Option(str_replace("_snpc", "", $npcName), ucfirst(str_replace("_snpc", " NPC", $npcName))));
                                    $cusForm->addElement("type", $dropdown);

                                    $cusForm->addElement("nametag", new Input("NPC Nametag: [string]\n" . 'Note: Use (" ") if nametag has space'));
                                    if (is_a($saveNames[0], CustomHuman::class, true)) {
                                        $cusForm->addElement("skin", new Input("NPC Skin URL: [null/string]"));
                                    }
                                    $player->sendForm($cusForm);
                                }));
                            }
                            $simpleForm->setHeaderText("Select NPC:");
                            $sender->sendForm($simpleForm);
                            break;
                        case "editNPC":
                            $cusForm->addElement("snpcid_edit", new Input("Enter the NPC ID"));
                            $sender->sendForm($cusForm);
                            break;
                        case "npcList":
                            if ($sender->hasPermission("simplenpc.list")) {
                                $list = "";
                                foreach ($plugin->getServer()->getLevels() as $world) {
                                    $entityNames = array_map(static function(Entity $entity): string {
                                        return TextFormat::YELLOW . "ID: (" . $entity->getId() . ") " . TextFormat::GREEN . $entity->getNameTag() . " §7-- §b" . $entity->getLevelNonNull()->getFolderName() . ": " . $entity->getFloorX() . "/" . $entity->getFloorY() . "/" . $entity->getFloorZ();
                                    }, array_filter($world->getEntities(), static function(Entity $entity): bool {
                                        return $entity instanceof BaseNPC or $entity instanceof CustomHuman;
                                    }));

                                    $list .= "§cNPC List and Location: (" . count($entityNames) . ")\n §f- " . implode("\n - ", $entityNames);
                                }

                                $simpleForm->setHeaderText($list);
                                $simpleForm->addButton(new Button("Print", null, function(Player $sender) use ($list) {
                                    $sender->sendMessage($list);
                                }));
                                $sender->sendForm($simpleForm);
                            }
                            break;
                    }
                } else {
                    $plugin->getServer()->getCommandMap()->dispatch($sender, $button["command"]);
                }
            }));
        }

        $sender->sendForm($form);
        $cusForm->setSubmitListener(function(Player $player, FormResponse $response) use ($plugin) {
            $type = strtolower($response->getDropdownSubmittedOptionId("type"));
            $nametag = $response->getInputSubmittedText("nametag") === "" ? $player->getName() : $response->getInputSubmittedText("nametag");
            $skin = $response->getInputSubmittedText("skin") === "null" ? "" : $response->getInputSubmittedText("skin");
            $npcEditId = $response->getInputSubmittedText("snpcid_edit");

            if ($npcEditId !== "") {
                $plugin->getServer()->getCommandMap()->dispatch($player, "snpc edit $npcEditId");
                return;
            }
            if ($type === "") {
                $player->sendMessage(TextFormat::YELLOW . "Please enter a valid NPC type");
                return;
            }

            $plugin->getServer()->getCommandMap()->dispatch($player, "snpc add $type $nametag $skin");
        });
    }

    public function sendEditForm(Player $sender, array $args, int $entityId): void {
        $plugin = SimpleNPC::getInstance();
        $entity = $plugin->getServer()->findEntity($entityId);

        $customForm = new CustomForm("Manage NPC");
        $simpleForm = new SimpleForm("Manage NPC");

        if ($entity instanceof BaseNPC || $entity instanceof CustomHuman) {
            if (is_file($file = $plugin->getDataFolder() . "npcs/" . $entity->namedtag->getString("Identifier") . ".json")) {
                if (empty(json_decode(file_get_contents($file), true))) { /** @phpstan-ignore-line */
                    if (!$entity->isFlaggedForDespawn()) {
                        $entity->flagForDespawn();
                    }

                    if (is_file($dat = $plugin->getDataFolder() . "npcs/" . $entity->namedtag->getString("Identifier") . ".dat")) {
                        unlink($dat);
                    }
                    unlink($file);
                    $sender->sendMessage(TextFormat::RED . "NPC Config file is empty! Please re-create the NPC.");
                    return;
                }
            } else {
                if (!$entity->isFlaggedForDespawn()) {
                    $entity->flagForDespawn();
                }
                $sender->sendMessage(TextFormat::RED . "NPC Config file not found! Please re-create the NPC.");
                return;
            }

            $npcConfig = new Config($plugin->getDataFolder() . "npcs/" . $entity->namedtag->getString("Identifier") . ".json", Config::JSON);
            $editUI = new SimpleForm("Manage NPC", "§aID:§2 $args[1]\n§aClass: §2" . get_class($entity) . "\n§aNametag: §2" . $entity->getNameTag() . "\n§aPosition: §2" . $entity->getFloorX() . "/" . $entity->getFloorY() . "/" . $entity->getFloorZ());

            foreach (ButtonManager::getInstance()->getEditButtons() as $button) {
                if (empty($button["element"]) && !empty($button["additional"]) && $button["additional"]["button"]["force"]) {
                    $editUI->addButton(new Button($button["additional"]["button"]["text"], $button["additional"]["button"]["icon"], function(Player $sender) use ($entity, $npcConfig, $button) {
                        switch ($button["additional"]["button"]["function"]) {
                            case "showNametag":
                                $npcConfig->set("showNametag", true);
                                $npcConfig->save();
                                $entity->setNameTag(str_replace("{line}", "\n", $npcConfig->get("nametag", $sender->getName())));
                                $entity->setNameTagAlwaysVisible();
                                $entity->setNameTagVisible();
                                NPCManager::getInstance()->saveChunkNPC($entity);
                                $sender->sendMessage(TextFormat::GREEN . "Successfully showing NPC nametag (NPC ID: " . $entity->getId() . ")");
                                break;
                            case "hideNametag":
                                $npcConfig->set("showNametag", false);
                                $npcConfig->save();
                                $entity->setNameTag("");
                                $entity->setNameTagAlwaysVisible(false);
                                $entity->setNameTagVisible(false);
                                NPCManager::getInstance()->saveChunkNPC($entity);
                                $sender->sendMessage(TextFormat::GREEN . "Successfully remove NPC nametag (NPC ID: " . $entity->getId() . ")");
                                break;
                            case "disableRotate":
                                $npcConfig->set("enableRotate", false);
                                $npcConfig->save();

                                $entity->namedtag->setShort("Rotate", 0, true);
                                NPCManager::getInstance()->saveChunkNPC($entity);

                                $sender->sendMessage(TextFormat::GREEN . "Successfully disable npc rotate (NPC ID: " . $entity->getId() . ")");
                                break;
                            case "enableRotate":
                                $npcConfig->set("enableRotate", true);
                                $npcConfig->save();

                                $entity->namedtag->setShort("Rotate", 1, true);
                                NPCManager::getInstance()->saveChunkNPC($entity);

                                $sender->sendMessage(TextFormat::GREEN . "Successfully enable npc rotate (NPC ID: " . $entity->getId() . ")");
                                break;
                            case "setArmor":
                                if ($entity instanceof CustomHuman) {
                                    NPCManager::getInstance()->applyArmorFrom($sender, $entity);
                                    $sender->sendMessage(TextFormat::GREEN . "Successfully send armor to npc ID: " . $entity->getId());
                                } else {
                                    $sender->sendMessage(TextFormat::RED . "Only human npc can wear armor");
                                }
                                break;
                            case "setHeld":
                                if ($entity instanceof CustomHuman) {
                                    if ($sender->getInventory()->getItemInHand()->getId() === ItemIds::AIR) {
                                        $sender->sendMessage(TextFormat::RED . "Please hold the item in your hand");
                                    } else {
                                        NPCManager::getInstance()->sendHeldItemFrom($sender, $entity);
                                        $sender->sendMessage(TextFormat::GREEN . "Successfully send held item '" . $sender->getInventory()->getItemInHand()->getVanillaName() . "' to npc ID: " . $entity->getId());
                                    }
                                } else {
                                    $sender->sendMessage(TextFormat::RED . "Only human npc can hold item");
                                }
                                break;
                        }
                    }));

                    continue;
                }

                $editUI->addButton(new Button($button["text"], $button["icon"], function(Player $sender) use ($npcConfig, $entity, $simpleForm, $editUI, $customForm, $button) {
                    if (!empty($button["element"]) && empty($button["additional"])) {
                        $customForm->addElement($button["element"]["id"], $button["element"]["element"]);
                        $sender->sendForm($customForm);
                    } elseif (empty($button["element"]) && !empty($button["additional"])) {
                        if ($button["additional"]["button"]["text"] === null) {
                            switch ($button["additional"]["button"]["function"]) {
                                case "commandList":
                                    $cmds = "This NPC (ID: {$entity->getId()}) does not have any commands.";
                                    if (!empty($npcConfig->get("commands"))) {
                                        $cmds = TextFormat::AQUA . "NPC ID: {$entity->getId()} Command list (" . count($npcConfig->get("commands")) . ")\n";

                                        foreach ($npcConfig->get("commands") as $cmd) {
                                            $cmds .= TextFormat::GREEN . "- " . $cmd . "\n";
                                        }
                                    }

                                    $simpleForm->setHeaderText($cmds);
                                    $simpleForm->addButton(new Button("Print", null, function(Player $sender) use ($cmds) {
                                        $sender->sendMessage($cmds);
                                    }));
                                    $simpleForm->addButton(new Button("< Back", null, function(Player $sender) use ($editUI) {
                                        $sender->sendForm($editUI);
                                    }));
                                    $sender->sendForm($simpleForm);
                                    break;
                                case "teleport":
                                    $simpleForm->addButton(new Button("You to NPC", null, function(Player $sender) use ($entity): void {
                                        $sender->teleport($entity->getLocation());
                                        $sender->sendMessage(TextFormat::GREEN . "Teleported!");
                                    }));
                                    $simpleForm->addButton(new Button("NPC to You", null, function(Player $sender) use ($npcConfig, $entity): void {
                                        $entity->teleport($sender->getLocation());
                                        if ($entity instanceof WalkingHuman) {
                                            $entity->randomPosition = $entity->asVector3();
                                        }
                                        $npcConfig->set("position", [$entity->getX(), $entity->getY(), $entity->getZ(), $entity->getYaw(), $entity->getPitch()]);
                                        $npcConfig->save();
                                        NPCManager::getInstance()->saveChunkNPC($entity);
                                        $sender->sendMessage(TextFormat::GREEN . "Teleported!");
                                    }));

                                    $sender->sendForm($simpleForm);
                                    break;
                            }
                            return;
                        }
                    }
                }));
            }

            $customForm->setSubmitListener(function(Player $player, FormResponse $response) use ($plugin, $entity) {
                $addcmd = $response->getInputSubmittedText("addcmd");
                $rmcmd = $response->getInputSubmittedText("removecmd");
                $chnmtd = $response->getInputSubmittedText("changenametag");
                $scale = $response->getInputSubmittedText("changescale");
                $skin = $response->getInputSubmittedText("changeskin");
                $cape = $response->getInputSubmittedText("changecape");
                $npcConfig = new Config($plugin->getDataFolder() . "npcs/" . $entity->namedtag->getString("Identifier") . ".json", Config::JSON);

                if ($rmcmd !== "") {
                    if (!in_array($rmcmd, $npcConfig->get("commands"), true)) {
                        $player->sendMessage(TextFormat::RED . "Command '$rmcmd' not found in command list.");
                        return;
                    }

                    $commands = $entity->namedtag->getCompoundTag("Commands") ?? new CompoundTag("Commands");
                    $commands->removeTag($rmcmd);
                    $entity->namedtag->setTag($commands);

                    $commands = $npcConfig->get("commands");
                    unset($commands[array_search($rmcmd, $commands, true)]);
                    $npcConfig->set("commands", $commands);
                    $npcConfig->save();
                    NPCManager::getInstance()->saveChunkNPC($entity);
                    $player->sendMessage(TextFormat::GREEN . "Successfully remove command '$rmcmd' (NPC ID: " . $entity->getId() . ")");
                } elseif ($addcmd !== "") {
                    if (in_array($addcmd, $npcConfig->get("commands"), true)) {
                        $player->sendMessage(TextFormat::RED . "Command '$addcmd' has already been added.");
                        return;
                    }

                    $commands = $entity->namedtag->getCompoundTag("Commands") ?? new CompoundTag("Commands");
                    $commands->setString($addcmd, $addcmd);
                    $entity->namedtag->setTag($commands);

                    $npcConfig->set("commands", array_merge([$addcmd], $npcConfig->getNested("commands")));
                    $npcConfig->save();
                    NPCManager::getInstance()->saveChunkNPC($entity);
                    $player->sendMessage(TextFormat::GREEN . "Successfully added command '$addcmd' (NPC ID: " . $entity->getId() . ")");
                } elseif ($chnmtd !== "") {
                    $player->sendMessage(TextFormat::GREEN . "Successfully change npc nametag from '{$entity->getNameTag()}' to '$chnmtd'  (NPC ID: " . $entity->getId() . ")");

                    $entity->setNameTag(str_replace("{line}", "\n", $chnmtd));
                    $entity->setNameTagAlwaysVisible();

                    $npcConfig->set("nametag", $chnmtd);
                    $npcConfig->save();
                } elseif ($cape !== "") {
                    if (!$entity instanceof CustomHuman) {
                        $player->sendMessage(TextFormat::RED . "Only human NPC can change cape!");
                        return;
                    }

                    $pCape = $player->getServer()->getPlayerExact($cape);

                    if ($pCape instanceof Player) {
                        $capeSkin = new Skin($entity->getSkin()->getSkinId(), $entity->getSkin()->getSkinData(), $pCape->getSkin()->getCapeData(), $entity->getSkin()->getGeometryName(), $entity->getSkin()->getGeometryData());
                        $entity->setSkin($capeSkin);
                        $entity->sendSkin();

                        $skinTag = NPCManager::getInstance()->getSkinTag($entity);

                        if ($skinTag instanceof CompoundTag) {
                            $skinTag->setByteArray("Data", $pCape->getSkin()->getSkinData(), true);
                            $skinTag->setByteArray("CapeData", $pCape->getSkin()->getCapeData(), true);

                            NPCManager::getInstance()->saveSkinTag($entity, $skinTag);
                        }

                        NPCManager::getInstance()->saveChunkNPC($entity);
                        $player->sendMessage(TextFormat::GREEN . "Successfully change npc cape (NPC ID: " . $entity->getId() . ")");
                        return;
                    }

                    $plugin->getServer()->getAsyncPool()->submitTask(new URLToCapeTask($cape, $plugin->getDataFolder(), $entity, $player->getName()));
                } elseif ($skin !== "") {
                    if (!$entity instanceof CustomHuman) {
                        $player->sendMessage(TextFormat::RED . "Only human NPC can change skin!");
                        return;
                    }

                    $pSkin = $player->getServer()->getPlayerExact($skin);

                    if ($pSkin instanceof Player) {
                        $entity->setSkin($pSkin->getSkin());
                        $entity->sendSkin();

                        $skinTag = NPCManager::getInstance()->getSkinTag($entity);
                        if ($skinTag instanceof CompoundTag) {
                            $skinTag->setString("Name", $player->getSkin()->getSkinId(), true);
                            $skinTag->setByteArray("Data", $player->getSkin()->getSkinData(), true);

                            NPCManager::getInstance()->saveSkinTag($entity, $skinTag);
                        }

                        NPCManager::getInstance()->saveChunkNPC($entity);
                        $player->sendMessage(TextFormat::GREEN . "Successfully change npc skin (NPC ID: " . $entity->getId() . ")");
                        return;
                    }

                    if (!preg_match('/https?:\/\/[^?]*\.png(?![\w.\-_])/', $skin)) {
                        $player->sendMessage(TextFormat::RED . "Invalid skin url file format! (Only PNG Supported)");
                        return;
                    }

                    $plugin->getServer()->getAsyncPool()->submitTask(new URLToSkinTask($player->getName(), $plugin->getDataFolder(), $skin, $entity));
                    $player->sendMessage(TextFormat::GREEN . "Successfully change npc skin (NPC ID: " . $entity->getId() . ")");
                } elseif ($scale !== "") {
                    if ((float)$scale <= 0) {
                        $player->sendMessage("Scale must be greater than 0");
                        return;
                    }

                    $npcConfig->set("scale", (float)$scale);
                    $npcConfig->save();
                    $entity->setScale((float)$scale);

                    NPCManager::getInstance()->saveChunkNPC($entity);
                    $player->sendMessage(TextFormat::GREEN . "Successfully change npc size to $scale (NPC ID: " . $entity->getId() . ")");
                } else {
                    $player->sendMessage(TextFormat::RED . "Please enter a valid value!");
                }
            });

            $sender->sendForm($editUI);
            return;
        }
        $sender->sendMessage(TextFormat::YELLOW . "SimpleNPC NPC with ID: " . $args[1] . " not found!");
    }
}