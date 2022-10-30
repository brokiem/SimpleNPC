<?php /** @noinspection TypeUnsafeComparisonInspection */

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\manager\form;

use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\entity\EmoteIds;
use brokiem\snpc\entity\WalkingHuman;
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
use InvalidArgumentException;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
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
                                $simpleForm->addButton(new Button(ucfirst(str_replace(["_snpc", "_"], [" NPC", " "], $npcName)), null, function(Player $player) use ($saveNames, $npcName, $cusForm) {
                                    $dropdown = new Dropdown("Selected NPC:");
                                    $dropdown->addOption(new Option(str_replace("_snpc", "", $npcName), ucfirst(str_replace(["_snpc", "_"], [" NPC", " "], $npcName))));
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
                                foreach ($plugin->getServer()->getWorldManager()->getWorlds() as $world) {
                                    $entityNames = array_map(static function(Entity $entity): string {
                                        return TextFormat::YELLOW . "ID: (" . $entity->getId() . ") " . TextFormat::GREEN . $entity->getNameTag() . " §7-- §b" . $entity->getWorld()->getFolderName() . ": " . $entity->getLocation()->getFloorX() . "/" . $entity->getLocation()->getFloorY() . "/" . $entity->getLocation()->getFloorZ();
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
            $type = $response->getDropdownSubmittedOptionId("type") === null ? "" : strtolower($response->getDropdownSubmittedOptionId("type"));
            $nametag = $response->getInputSubmittedText("nametag") === "" ? $player->getName() : $response->getInputSubmittedText("nametag");
            $skin = $response->getInputSubmittedText("skin") === "null" ? "" : $response->getInputSubmittedText("skin");
            $npcEditId = $response->getInputSubmittedText("snpcid_edit");

            if ($npcEditId != "") {
                $plugin->getServer()->getCommandMap()->dispatch($player, "snpc edit $npcEditId");
                return;
            }

            if ($type == "") {
                $player->sendMessage(TextFormat::YELLOW . "Please enter a valid NPC type");
                return;
            }

            $plugin->getServer()->getCommandMap()->dispatch($player, "snpc add $type $nametag $skin");
        });
    }

    public function sendEditForm(Player $sender, array $args, int $entityId): void {
        $plugin = SimpleNPC::getInstance();
        $entity = $plugin->getServer()->getWorldManager()->findEntity($entityId);

        $customForm = new CustomForm("Manage NPC");
        $simpleForm = new SimpleForm("Manage NPC");

        if ($entity instanceof BaseNPC || $entity instanceof CustomHuman) {
            $editUI = new SimpleForm("Manage NPC", "§aID:§2 $args[1]\n§aClass: §2" . get_class($entity) . "\n§aNametag: §2" . $entity->getNameTag() . "\n§aPosition: §2" . $entity->getLocation()->getFloorX() . "/" . $entity->getLocation()->getFloorY() . "/" . $entity->getLocation()->getFloorZ());

            foreach (ButtonManager::getInstance()->getEditButtons() as $button) {
                if (empty($button["element"]) && !empty($button["additional"]) && $button["additional"]["button"]["force"]) {
                    $editUI->addButton(new Button($button["additional"]["button"]["text"], $button["additional"]["button"]["icon"], function(Player $sender) use ($entity, $button) {
                        switch ($button["additional"]["button"]["function"]) {
                            case "showNametag":
                                $entity->setNameTagAlwaysVisible();
                                $entity->setNameTagVisible();
                                $sender->sendMessage(TextFormat::GREEN . "Successfully showing NPC nametag (NPC ID: " . $entity->getId() . ")");
                                break;
                            case "hideNametag":
                                $entity->setNameTagAlwaysVisible(false);
                                $entity->setNameTagVisible(false);
                                $sender->sendMessage(TextFormat::GREEN . "Successfully remove NPC nametag (NPC ID: " . $entity->getId() . ")");
                                break;
                            case "disableRotate":
                                $entity->setCanLookToPlayers(false);
                                $sender->sendMessage(TextFormat::GREEN . "Successfully disable npc rotate (NPC ID: " . $entity->getId() . ")");
                                break;
                            case "EnableRotation":
                                $entity->setCanLookToPlayers(true);
                                $sender->sendMessage(TextFormat::GREEN . "Successfully enable npc rotate (NPC ID: " . $entity->getId() . ")");
                                break;
                            case "setClickEmote":
                                $clickEmoteUI = new SimpleForm("Edit Click-Emote", "Please choose a new click-emote.");
                                if ($entity->getClickEmoteId() !== null) $clickEmoteUI->addButton(new Button(
                                    "§cRemove Click-Emote", null,
                                    function (Player $player) use ($entity) {
                                        $entity->setClickEmoteId(null);
                                        $player->sendMessage("§aSuccessfully removed the click-emote of NPC ID: " . $entity->getId());
                                    }));
                                foreach (EmoteIds::EMOTES as $emoteName => $emoteId)
                                    $clickEmoteUI->addButton(new Button($emoteName, null,
                                        function (Player $player) use ($entity, $emoteId, $emoteName) {
                                            $entity->setClickEmoteId($emoteId);
                                            $player->sendMessage("§aSuccessfully set the click-emote of NPC ID: " . $entity->getId() . " to §7" . $emoteName);
                                        }));
                                $sender->sendForm($clickEmoteUI);
                                break;
                            case "setEmote":
                                $emoteUI = new SimpleForm("Edit Emote", "Please choose a new emote.");
                                if ($entity->getEmoteId() !== null) $emoteUI->addButton(new Button(
                                    "§cRemove Emote", null,
                                    function (Player $player) use ($entity) {
                                        $entity->setEmoteId(null);
                                        $player->sendMessage("§aSuccessfully removed the emote of NPC ID: " . $entity->getId());
                                    }));
                                foreach (EmoteIds::EMOTES as $emoteName => $emoteId)
                                    $emoteUI->addButton(new Button($emoteName, null,
                                        function (Player $player) use ($entity, $emoteId, $emoteName) {
                                            $entity->setEmoteId($emoteId);
                                            $player->sendMessage("§aSuccessfully set the emote of NPC ID: " . $entity->getId() . " to §7" . $emoteName);
                                        }));
                                $sender->sendForm($emoteUI);
                                break;
                            case "setArmor":
                                if ($entity instanceof CustomHuman) {
                                    $entity->applyArmorFrom($sender);
                                    $sender->sendMessage(TextFormat::GREEN . "Successfully set your armor to NPC ID: " . $entity->getId());
                                } else {
                                    $sender->sendMessage(TextFormat::RED . "Only human npc can wear armor");
                                }
                                break;
                            case "setHeld":
                                if ($entity instanceof CustomHuman) {
                                    if ($sender->getInventory()->getItemInHand()->getId() === ItemIds::AIR) {
                                        $sender->sendMessage(TextFormat::RED . "Please hold the item in your hand");
                                    } else {
                                        $entity->sendHeldItemFrom($sender);
                                        $sender->sendMessage(TextFormat::GREEN . "Successfully set held item '" . $sender->getInventory()->getItemInHand()->getVanillaName() . "' to npc ID: " . $entity->getId());
                                    }
                                } else {
                                    $sender->sendMessage(TextFormat::RED . "Only human npc can hold item");
                                }
                                break;
                        }
                    }));

                    continue;
                }

                $editUI->addButton(new Button($button["text"], $button["icon"], function(Player $sender) use ($entity, $simpleForm, $editUI, $customForm, $button) {
                    if (!empty($button["element"]) && empty($button["additional"])) {
                        $customForm->addElement($button["element"]["id"], $button["element"]["element"]);
                        $sender->sendForm($customForm);
                    } elseif (empty($button["element"]) && !empty($button["additional"])) {
                        if ($button["additional"]["button"]["text"] === null) {
                            switch ($button["additional"]["button"]["function"]) {
                                case "commandList":
                                    $cmds = "This NPC (ID: {$entity->getId()}) does not have any commands.";
                                    $commands = $entity->getCommandManager()->getAll();
                                    if (!empty($commands)) {
                                        $cmds = TextFormat::AQUA . "NPC ID: {$entity->getId()} Command list (" . count($commands) . ")\n";

                                        foreach ($commands as $cmd) {
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
                                    $simpleForm->addButton(new Button("NPC to You", null, function(Player $sender) use ($entity): void {
                                        $entity->teleport($sender->getLocation());
                                        if ($entity instanceof WalkingHuman) {
                                            $entity->randomPosition = $entity->getLocation()->asVector3();
                                        }
                                        $sender->sendMessage(TextFormat::GREEN . "Teleported!");
                                    }));

                                    $sender->sendForm($simpleForm);
                                    break;
                            }
                        }
                    }
                }));
            }

            $customForm->setSubmitListener(function(Player $player, FormResponse $response) use ($plugin, $entity) {
                try {
                    $addcmd = $response->getInputSubmittedText("addcmd");
                } catch (InvalidArgumentException) {
                    $addcmd = null;
                }
                try {
                    $rmcmd = $response->getInputSubmittedText("removecmd");
                } catch (InvalidArgumentException) {
                    $rmcmd = null;
                }
                try {
                    $chnmtd = $response->getInputSubmittedText("changenametag");
                } catch (InvalidArgumentException) {
                    $chnmtd = null;
                }
                try {
                    $scale = $response->getInputSubmittedText("changescale");
                } catch (InvalidArgumentException) {
                    $scale = null;
                }
                try {
                    $skin = $response->getInputSubmittedText("changeskin");
                } catch (InvalidArgumentException) {
                    $skin = null;
                }
                try {
                    $cape = $response->getInputSubmittedText("changecape");
                } catch (InvalidArgumentException) {
                    $cape = null;
                }

                if ($rmcmd !== null) {
                    if (!in_array($rmcmd, $entity->getCommandManager()->getAll(), true)) {
                        $player->sendMessage(TextFormat::RED . "Command '$rmcmd' not found in command list.");
                        return;
                    }

                    $entity->getCommandManager()->remove($rmcmd);
                    $player->sendMessage(TextFormat::GREEN . "Successfully remove command '$rmcmd' (NPC ID: " . $entity->getId() . ")");
                } elseif ($addcmd !== null) {
                    if (in_array($addcmd, $entity->getCommandManager()->getAll(), true)) {
                        $player->sendMessage(TextFormat::RED . "Command '$addcmd' has already been added.");
                        return;
                    }

                    $entity->getCommandManager()->add($addcmd);
                    $player->sendMessage(TextFormat::GREEN . "Successfully added command '$addcmd' (NPC ID: " . $entity->getId() . ")");
                } elseif ($chnmtd !== null) {
                    $player->sendMessage(TextFormat::GREEN . "Successfully change npc nametag from '{$entity->getNameTag()}' to '$chnmtd'  (NPC ID: " . $entity->getId() . ")");

                    $entity->setNameTag(str_replace("{line}", "\n", $chnmtd));
                    $entity->setNameTagAlwaysVisible();
                } elseif ($cape !== null) {
                    if (!$entity instanceof CustomHuman) {
                        $player->sendMessage(TextFormat::RED . "Only human NPC can change cape!");
                        return;
                    }

                    $pCape = $player->getServer()->getPlayerExact($cape);

                    if ($pCape instanceof Player) {
                        $capeSkin = new Skin($entity->getSkin()->getSkinId(), $entity->getSkin()->getSkinData(), $pCape->getSkin()->getCapeData(), $entity->getSkin()->getGeometryName(), $entity->getSkin()->getGeometryData());
                        $entity->setSkin($capeSkin);
                        $entity->sendSkin();

                        $player->sendMessage(TextFormat::GREEN . "Successfully change npc cape (NPC ID: " . $entity->getId() . ")");
                        return;
                    }

                    $plugin->getServer()->getAsyncPool()->submitTask(new URLToCapeTask($cape, $plugin->getDataFolder(), $entity, $player->getName()));
                } elseif ($skin !== null) {
                    if (!$entity instanceof CustomHuman) {
                        $player->sendMessage(TextFormat::RED . "Only human NPC can change skin!");
                        return;
                    }

                    $pSkin = $player->getServer()->getPlayerExact($skin);

                    if ($pSkin instanceof Player) {
                        $entity->setSkin($pSkin->getSkin());
                        $entity->sendSkin();

                        $player->sendMessage(TextFormat::GREEN . "Successfully change npc skin (NPC ID: " . $entity->getId() . ")");
                        return;
                    }

                    if (!preg_match('/https?:\/\/[^?]*\.png(?![\w.\-_])/', $skin)) {
                        $player->sendMessage(TextFormat::RED . "Invalid skin url file format! (Only PNG Supported)");
                        return;
                    }

                    $plugin->getServer()->getAsyncPool()->submitTask(new URLToSkinTask($player->getName(), $plugin->getDataFolder(), $skin, $entity));
                    $player->sendMessage(TextFormat::GREEN . "Successfully change npc skin (NPC ID: " . $entity->getId() . ")");
                } elseif ($scale !== null) {
                    if ((float)$scale <= 0) {
                        $player->sendMessage("Scale must be greater than 0");
                        return;
                    }

                    $entity->setScale((float)$scale);

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
