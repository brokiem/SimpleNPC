<?php

declare(strict_types=1);

namespace brokiem\snpc\commands;

use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\entity\WalkingHuman;
use brokiem\snpc\manager\NPCManager;
use brokiem\snpc\SimpleNPC;
use brokiem\snpc\task\async\SpawnHumanNPCTask;
use brokiem\snpc\task\async\URLToCapeTask;
use EasyUI\element\Button;
use EasyUI\element\Dropdown;
use EasyUI\element\Input;
use EasyUI\element\Option;
use EasyUI\utils\FormResponse;
use EasyUI\variant\CustomForm;
use EasyUI\variant\SimpleForm;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use slapper\entities\SlapperEntity;
use slapper\entities\SlapperHuman;

class Commands extends PluginCommand {
    public function __construct(string $name, Plugin $owner){
        parent::__construct($name, $owner);
        $this->setDescription("SimpleNPC commands");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
        if(!$this->testPermission($sender)){
            return true;
        }

        /** @var SimpleNPC $plugin */
        $plugin = $this->getPlugin();

        if(isset($args[0])){
            switch(strtolower($args[0])){
                case "ui":
                    if(!$sender->hasPermission("simplenpc.ui") or (!$sender instanceof Player)){
                        return true;
                    }

                    $form = new SimpleForm("Manage NPC");
                    $simpleForm = new SimpleForm("Manage NPC");
                    $cusForm = new CustomForm("Manage NPC");

                    $buttons = ["Reload Config" => ["text" => "Reload Config", "icon" => null, "command" => "snpc reload", "function" => null], "Spawn NPC" => ["text" => "Spawn NPC", "icon" => null, "command" => null, "function" => "spawnNPC"], "Edit NPC" => ["text" => "Edit NPC", "icon" => null, "command" => null, "function" => "editNPC"], "Get NPC ID" => ["text" => "Get NPC ID", "icon" => null, "command" => "snpc id", "function" => null], "Migrate NPC" => ["text" => "Migrate NPC", "icon" => null, "command" => "snpc migrate", "function" => null], "Remove NPC" => ["text" => "Remove NPC", "icon" => null, "command" => "snpc remove", "function" => null], "List NPC" => ["text" => "List NPC", "icon" => null, "command" => null, "function" => "npcList"],];

                    foreach($buttons as $button){
                        $form->addButton(new Button($button["text"], $button["icon"], function (Player $sender) use ($simpleForm, $cusForm, $button, $plugin){
                            if($button["function"] !== null){
                                switch($button["function"]){
                                    case "spawnNPC":
                                        $simpleForm->addButton(new Button("Human NPC", null, function (Player $player) use ($cusForm){
                                            $dropdown = new Dropdown("Selected NPC:");
                                            $dropdown->addOption(new Option("human", "Human NPC"));
                                            $cusForm->addElement("type", $dropdown);

                                            $cusForm->addElement("nametag", new Input("NPC Nametag: [string]\n" . 'Note: Use (" ") if nametag has space'));
                                            $dropdown = new Dropdown("NPC Can Walk? [Yes/No]");
                                            $dropdown->addOption(new Option("choose", "Choose"));
                                            $dropdown->addOption(new Option("true", "Yes"));
                                            $dropdown->addOption(new Option("false", "No"));
                                            $cusForm->addElement("walk", $dropdown);
                                            $cusForm->addElement("skin", new Input("NPC Skin URL: [null/string]"));
                                            $player->sendForm($cusForm);
                                        }));

                                        foreach(NPCManager::$npcs as $class => $saveNames){
                                            $simpleForm->addButton(new Button(ucfirst(str_replace("_snpc", " NPC", $saveNames[0])), null, function (Player $player) use ($saveNames, $cusForm){
                                                $dropdown = new Dropdown("Selected NPC:");
                                                $dropdown->addOption(new Option(str_replace("_snpc", "", $saveNames[0]), ucfirst(str_replace("_snpc", " NPC", $saveNames[0]))));
                                                $cusForm->addElement("type", $dropdown);

                                                $cusForm->addElement("nametag", new Input("NPC Nametag: [string]\n" . 'Note: Use (" ") if nametag has space'));
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
                                        if($sender->hasPermission("simplenpc.list")){
                                            $list = "";
                                            foreach($plugin->getServer()->getLevels() as $world){
                                                $entityNames = array_map(static function (Entity $entity): string{
                                                    return TextFormat::YELLOW . "ID: (" . $entity->getId() . ") " . TextFormat::GREEN . $entity->getNameTag() . " §7-- §b" . $entity->getLevel()->getFolderName() . ": " . $entity->getFloorX() . "/" . $entity->getFloorY() . "/" . $entity->getFloorZ();
                                                }, array_filter($world->getEntities(), static function (Entity $entity): bool{
                                                    return $entity instanceof BaseNPC or $entity instanceof CustomHuman;
                                                }));

                                                $list .= "§cNPC List and Location: (" . count($entityNames) . ")\n §f- " . implode("\n - ", $entityNames);
                                            }

                                            $simpleForm->setHeaderText($list);
                                            $simpleForm->addButton(new Button("Print", null, function (Player $sender) use ($list){
                                                $sender->sendMessage($list);
                                            }));
                                            $sender->sendForm($simpleForm);
                                        }
                                        break;
                                }
                            }else{
                                $plugin->getServer()->getCommandMap()->dispatch($sender, $button["command"]);
                            }
                        }));
                    }

                    $sender->sendForm($form);
                    $cusForm->setSubmitListener(function (Player $player, FormResponse $response) use ($plugin){
                        $type = strtolower($response->getDropdownSubmittedOptionId("type"));
                        $nametag = $response->getInputSubmittedText("nametag") === "" ? $player->getName() : $response->getInputSubmittedText("nametag");
                        $walk = $response->getDropdownSubmittedOptionId("walk");
                        $skin = $response->getInputSubmittedText("skin") === "null" ? "" : $response->getInputSubmittedText("skin");
                        $npcEditId = $response->getInputSubmittedText("snpcid_edit");

                        if($npcEditId !== ""){
                            $plugin->getServer()->getCommandMap()->dispatch($player, "snpc edit $npcEditId");
                            return;
                        }
                        if($type === ""){
                            $player->sendMessage(TextFormat::YELLOW . "Please enter a valid NPC type");
                            return;
                        }
                        if($walk === "choose" && strtolower($type) === "human"){
                            $player->sendMessage(TextFormat::YELLOW . "Please select whether NPC can walk or not.");
                            return;
                        }
                        $plugin->getServer()->getCommandMap()->dispatch($player, "snpc add $type $nametag $walk $skin");
                    });
                    break;
                case "reload":
                    if(!$sender->hasPermission("simplenpc.reload")){
                        return true;
                    }

                    $plugin->reloadConfig();
                    $sender->sendMessage(TextFormat::GREEN . "SimpleNPC Config reloaded successfully!");
                    break;
                case "id":
                    if(!$sender->hasPermission("simplenpc.id")){
                        return true;
                    }

                    if(!isset($plugin->idPlayers[$sender->getName()])){
                        $plugin->idPlayers[$sender->getName()] = true;
                        $sender->sendMessage(TextFormat::DARK_GREEN . "Hit the npc that you want to see the ID");
                    }else{
                        unset($plugin->idPlayers[$sender->getName()]);
                        $sender->sendMessage(TextFormat::GREEN . "Tap to get NPC ID has been canceled");
                    }
                    break;
                case "spawn":
                case "add":
                    if(!$sender instanceof Player){
                        $sender->sendMessage("Only player can run this command!");
                        return true;
                    }

                    if(!$sender->hasPermission("simplenpc.spawn")){
                        return true;
                    }

                    if(isset($args[1])){
                        if(in_array(strtolower($args[1]) . "_snpc", SimpleNPC::$npcType, true)){
                            if(strtolower($args[1]) . "_snpc" === SimpleNPC::ENTITY_HUMAN){
                                if(isset($args[4])){
                                    if(!preg_match('/https?:\/\/[^?]*\.png(?![\w.\-_])/', $args[4])){
                                        $sender->sendMessage(TextFormat::RED . "Invalid skin url file format! (Only PNG Supported)");
                                        return true;
                                    }
                                    $plugin->getServer()->getAsyncPool()->submitTask(new SpawnHumanNPCTask($args[2], $sender->getName(), $plugin->getDataFolder(), $args[3] === "true", $args[4]));
                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC with nametag $args[2] for you...");
                                    return true;
                                }
                                if(isset($args[3])){
                                    $plugin->getServer()->getAsyncPool()->submitTask(new SpawnHumanNPCTask($args[2], $sender->getName(), $plugin->getDataFolder(), $args[3] === "true"));
                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC with nametag $args[2] for you...");
                                    return true;
                                }
                                if(isset($args[2])){
                                    $plugin->getServer()->getAsyncPool()->submitTask(new SpawnHumanNPCTask($args[2], $sender->getName(), $plugin->getDataFolder()));
                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC with nametag $args[2] for you...");
                                    return true;
                                }
                                $plugin->getServer()->getAsyncPool()->submitTask(new SpawnHumanNPCTask(null, $sender->getName(), $plugin->getDataFolder()));
                            }else{
                                if(isset($args[2])){
                                    NPCManager::createNPC(strtolower($args[1]) . "_snpc", $sender, $args[2]);
                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC with nametag $args[2] for you...");
                                    return true;
                                }
                                NPCManager::createNPC(strtolower($args[1]) . "_snpc", $sender);
                            }
                            $sender->sendMessage(TextFormat::DARK_GREEN . "Creating " . ucfirst($args[1]) . " NPC without nametag for you...");
                        }else{
                            $sender->sendMessage(TextFormat::RED . "Invalid entity type or entity not registered!");
                        }
                    }else{
                        $sender->sendMessage(TextFormat::RED . "Usage: /snpc spawn <type> optional: <nametag> <canWalk> <skinUrl>");
                    }
                    break;
                case "delete":
                case "remove":
                    if(!$sender->hasPermission("simplenpc.remove")){
                        return true;
                    }
                    if(isset($args[1]) and is_numeric($args[1])){
                        $entity = $plugin->getServer()->findEntity((int)$args[1]);

                        if($entity instanceof BaseNPC || $entity instanceof CustomHuman){
                            if(NPCManager::removeNPC($entity->namedtag->getString("Identifier"), $entity)){
                                $sender->sendMessage(TextFormat::GREEN . "The NPC was successfully removed!");
                            }else{
                                $sender->sendMessage(TextFormat::YELLOW . "The NPC was failed removed! (File not found)");
                            }
                            return true;
                        }

                        $sender->sendMessage(TextFormat::YELLOW . "SimpleNPC Entity with ID: " . $args[1] . " not found!");
                        return true;
                    }

                    if(!isset($plugin->removeNPC[$sender->getName()])){
                        $plugin->removeNPC[$sender->getName()] = true;
                        $sender->sendMessage(TextFormat::DARK_GREEN . "Hit the npc that you want to delete or remove");
                        return true;
                    }

                    unset($plugin->removeNPC[$sender->getName()]);
                    $sender->sendMessage(TextFormat::GREEN . "Remove npc by hitting has been canceled");
                    break;
                case "edit":
                case "manage":
                    if(!$sender->hasPermission("simplenpc.edit") or !$sender instanceof Player){
                        return true;
                    }

                    if(!isset($args[1]) or !is_numeric($args[1])){
                        $sender->sendMessage(TextFormat::RED . "Usage: /snpc edit <id>");
                        return true;
                    }

                    $entity = $plugin->getServer()->findEntity((int)$args[1]);

                    $customForm = new CustomForm("Manage NPC");
                    $simpleForm = new SimpleForm("Manage NPC");

                    if($entity instanceof BaseNPC || $entity instanceof CustomHuman){
                        $npcConfig = new Config($plugin->getDataFolder() . "npcs/" . $entity->namedtag->getString("Identifier") . ".json", Config::JSON);
                        $editUI = new SimpleForm("Manage NPC", "§aID:§2 $args[1]\n§aClass: §2" . get_class($entity) . "\n§aNametag: §2" . $entity->getNameTag() . "\n§aPosition: §2" . $entity->getFloorX() . "/" . $entity->getFloorY() . "/" . $entity->getFloorZ());

                        $buttons = ["Add Command" => ["text" => "Add Command", "icon" => null, "element" => ["id" => "addcmd", "element" => new Input("Enter the command here")], "additional" => []], "Remove Command" => ["text" => "Remove Command", "icon" => null, "element" => ["id" => "removecmd", "element" => new Input("Enter the command here")], "additional" => []], "Change Nametag" => ["text" => "Change Nametag", "icon" => null, "element" => ["id" => "changenametag", "element" => new Input("Enter the new nametag here")], "additional" => []], "Change Skin" => ["text" => "Change Skin\n(Only Human NPC)", "icon" => null, "element" => ["id" => "changeskin", "element" => new Input("Enter the skin URL or online player name")], "additional" => []], "Change Cape" => ["text" => "Change Cape\n(Only Human NPC)", "icon" => null, "element" => ["id" => "changecape", "element" => new Input("Enter the Cape URL or online player name")], "additional" => []], "Show Nametag" => ["text" => "Show Nametag", "icon" => null, "element" => [], "additional" => ["form" => "editUI", "button" => ["text" => "Show Nametag", "icon" => null, "function" => "showNametag", "force" => true]]], "Hide Nametag" => ["text" => "Hide Nametag", "icon" => null, "element" => [], "additional" => ["form" => "editUI", "button" => ["text" => "Hide Nametag", "icon" => null, "function" => "hideNametag", "force" => true]]], "Command List" => ["text" => "Command List", "icon" => null, "element" => [], "additional" => ["form" => "", "button" => ["text" => null, "icon" => null, "function" => "commandList", "force" => false]]], "Teleport" => ["text" => "Teleport", "icon" => null, "element" => [], "additional" => ["form" => "", "button" => ["text" => null, "icon" => null, "function" => "teleport", "force" => false]]]];

                        foreach($buttons as $button){
                            if(empty($button["element"]) && !empty($button["additional"]) && $button["additional"]["button"]["force"]){
                                $editUI->addButton(new Button($button["additional"]["button"]["text"], $button["additional"]["button"]["icon"], function (Player $sender) use ($entity, $npcConfig, $button){
                                    switch($button["additional"]["button"]["function"]){
                                        case "showNametag":
                                            $npcConfig->set("showNametag", true);
                                            $npcConfig->save();
                                            $entity->setNameTag($npcConfig->get("nametag"));
                                            $entity->setNameTagAlwaysVisible(true);
                                            $entity->setNameTagVisible(true);
                                            $sender->sendMessage(TextFormat::GREEN . "Successfully removed NPC nametag (NPC ID: " . $entity->getId() . ")");
                                            break;
                                        case "hideNametag":
                                            $npcConfig->set("showNametag", false);
                                            $npcConfig->save();
                                            $entity->setNameTag("");
                                            $entity->setNameTagAlwaysVisible(false);
                                            $entity->setNameTagVisible(false);
                                            $sender->sendMessage(TextFormat::GREEN . "Successfully removed NPC nametag (NPC ID: " . $entity->getId() . ")");
                                            break;
                                    }
                                }));

                                continue;
                            }

                            $editUI->addButton(new Button($button["text"], $button["icon"], function (Player $sender) use ($npcConfig, $entity, $simpleForm, $editUI, $customForm, $button){
                                if(!empty($button["element"]) && empty($button["additional"])){
                                    $customForm->addElement($button["element"]["id"], $button["element"]["element"]);
                                    $sender->sendForm($customForm);
                                }elseif(empty($button["element"]) && !empty($button["additional"])){
                                    if($button["additional"]["button"]["text"] === null){
                                        switch($button["additional"]["button"]["function"]){
                                            case "commandList":
                                                $cmds = "This NPC (ID: {$entity->getId()}) does not have any commands.";
                                                if(!empty($npcConfig->get("commands"))){
                                                    $cmds = TextFormat::AQUA . "NPC ID: {$entity->getId()} Command list (" . count($npcConfig->get("commands")) . ")\n";

                                                    foreach($npcConfig->get("commands") as $cmd){
                                                        $cmds .= TextFormat::GREEN . "- " . $cmd . "\n";
                                                    }
                                                }

                                                $simpleForm->setHeaderText($cmds);
                                                $simpleForm->addButton(new Button("Print", null, function (Player $sender) use ($cmds){
                                                    $sender->sendMessage($cmds);
                                                }));
                                                $simpleForm->addButton(new Button("< Back", null, function (Player $sender) use ($editUI){
                                                    $sender->sendForm($editUI);
                                                }));
                                                $sender->sendForm($simpleForm);
                                                break;
                                            case "teleport":
                                                $simpleForm->addButton(new Button("You to Entity", null, function (Player $sender) use ($entity): void{
                                                    $sender->teleport($entity->getLocation());
                                                    $sender->sendMessage(TextFormat::GREEN . "Teleported!");
                                                }));
                                                $simpleForm->addButton(new Button("Entity to You", null, function (Player $sender) use ($npcConfig, $entity): void{
                                                    $entity->teleport($sender->getLocation());
                                                    if($entity instanceof WalkingHuman){
                                                        $entity->randomPosition = $entity->asVector3();
                                                    }
                                                    $npcConfig->set("position", [$entity->getX(), $entity->getY(), $entity->getZ(), $entity->getYaw(), $entity->getPitch()]);
                                                    $npcConfig->save();
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

                        $customForm->setSubmitListener(function (Player $player, FormResponse $response) use ($plugin, $entity){
                            $addcmd = $response->getInputSubmittedText("addcmd");
                            $rmcmd = $response->getInputSubmittedText("removecmd");
                            $chnmtd = $response->getInputSubmittedText("changenametag");
                            $skin = $response->getInputSubmittedText("changeskin");
                            $cape = $response->getInputSubmittedText("changecape");
                            $npcConfig = new Config($plugin->getDataFolder() . "npcs/" . $entity->namedtag->getString("Identifier") . ".json", Config::JSON);

                            if($rmcmd !== ""){
                                if(!in_array($rmcmd, $npcConfig->get("commands"), true)){
                                    $player->sendMessage(TextFormat::RED . "Command '$rmcmd' not found in command list.");
                                    return true;
                                }

                                $commands = $entity->namedtag->getCompoundTag("Commands") ?? new CompoundTag("Commands");
                                $commands->removeTag($rmcmd);
                                $entity->namedtag->setTag($commands);

                                $commands = $npcConfig->get("commands");
                                unset($commands[array_search($rmcmd, $commands, true)]);
                                $npcConfig->set("commands", $commands);
                                $npcConfig->save();
                                $player->sendMessage(TextFormat::GREEN . "Successfully remove command '$rmcmd' (NPC ID: " . $entity->getId() . ")");
                            }elseif($addcmd !== ""){
                                if(in_array($addcmd, $npcConfig->get("commands"), true)){
                                    $player->sendMessage(TextFormat::RED . "Command '$addcmd' has already been added.");
                                    return true;
                                }

                                $commands = $entity->namedtag->getCompoundTag("Commands") ?? new CompoundTag("Commands");
                                $commands->setString($addcmd, $addcmd);
                                $entity->namedtag->setTag($commands);

                                $npcConfig->set("commands", array_merge([$addcmd], $npcConfig->getNested("commands")));
                                $npcConfig->save();
                                $player->sendMessage(TextFormat::GREEN . "Successfully added command '$addcmd' (NPC ID: " . $entity->getId() . ")");
                            }elseif($chnmtd !== ""){
                                $player->sendMessage(TextFormat::GREEN . "Successfully change npc nametag from '{$entity->getNameTag()}' to '$chnmtd'  (NPC ID: " . $entity->getId() . ")");

                                $entity->setNameTag($chnmtd);
                                $entity->setNameTagAlwaysVisible();

                                $npcConfig->set("nametag", $chnmtd);
                                $npcConfig->save();
                            }elseif($cape !== ""){
                                if(!$entity instanceof CustomHuman){
                                    $player->sendMessage(TextFormat::RED . "Only human NPC can change cape!");
                                    return true;
                                }

                                $pCape = $player->getServer()->getPlayerExact($cape);

                                if($pCape instanceof Player){
                                    $capeSkin = new Skin($entity->getSkin()->getSkinId(), $entity->getSkin()->getSkinData(), $player->getSkin()->getCapeData(), $entity->getSkin()->getGeometryName(), $entity->getSkin()->getGeometryData());
                                    $entity->setSkin($capeSkin);
                                    $entity->sendSkin();

                                    $npcConfig->set("capeData", base64_encode($player->getSkin()->getCapeData()));
                                    $npcConfig->save();
                                    $player->sendMessage(TextFormat::GREEN . "Successfully change npc skin (NPC ID: " . $entity->getId() . ")");
                                    return true;
                                }

                                $plugin->getServer()->getAsyncPool()->submitTask(new URLToCapeTask($cape, $plugin->getDataFolder(), $entity, $player->getName()));
                            }elseif($skin !== ""){
                                if(!$entity instanceof CustomHuman){
                                    $player->sendMessage(TextFormat::RED . "Only human NPC can change skin!");
                                    return true;
                                }

                                $pSkin = $player->getServer()->getPlayerExact($skin);

                                if($pSkin instanceof Player){
                                    $entity->setSkin($pSkin->getSkin());
                                    $entity->sendSkin();

                                    $npcConfig->set("skinId", $player->getSkin()->getSkinId());
                                    $npcConfig->set("skinData", base64_encode($player->getSkin()->getSkinData()));
                                    $npcConfig->save();
                                    $player->sendMessage(TextFormat::GREEN . "Successfully change npc skin (NPC ID: " . $entity->getId() . ")");
                                    return true;
                                }

                                if(!preg_match('/https?:\/\/[^?]*\.png(?![\w.\-_])/', $skin)){
                                    $player->sendMessage(TextFormat::RED . "Invalid skin url file format! (Only PNG Supported)");
                                    return true;
                                }

                                $plugin->getServer()->getAsyncPool()->submitTask(new SpawnHumanNPCTask($entity->getNameTag(), $player->getName(), $plugin->getDataFolder(), !($entity->namedtag->getShort("Walk") === 0), $skin, $entity->namedtag->getCompoundTag("Commands"), null, $entity->getLocation()));
                                NPCManager::removeNPC($entity->namedtag->getString("Identifier"), $entity);
                                $player->sendMessage(TextFormat::GREEN . "Successfully change npc skin (NPC ID: " . $entity->getId() . ")");
                            }else{
                                $player->sendMessage(TextFormat::RED . "Please enter a valid value!");
                            }
                            return true;
                        });

                        $sender->sendForm($editUI);
                        return true;
                    }
                    $sender->sendMessage(TextFormat::YELLOW . "SimpleNPC Entity with ID: " . $args[1] . " not found!");
                    break;
                case "migrate":
                    if(!$sender->hasPermission("simplenpc.migrate")){
                        return true;
                    }

                    if(!$sender instanceof Player){
                        return true;
                    }

                    if($plugin->getServer()->getPluginManager()->getPlugin("Slapper") !== null){
                        if(!isset($args[1]) && !isset($plugin->migrateNPC[$sender->getName()])){
                            $plugin->migrateNPC[$sender->getName()] = true;

                            $plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($plugin, $sender): void{
                                if(isset($plugin->migrateNPC[$sender->getName()])){
                                    unset($plugin->migrateNPC[$sender->getName()]);
                                    $sender->sendMessage(TextFormat::YELLOW . "Migrating NPC Cancelled! (10 seconds)");
                                }
                            }), 10 * 20);

                            $sender->sendMessage(TextFormat::RED . " \nAre you sure want to migrate your NPC from Slapper to SimpleNPC? \nThis will replace the slapper NPCs with the new Simple NPCs\n\nIf yes, run /migrate confirm, if no, run /migrate cancel\n\n ");
                            $sender->sendMessage(TextFormat::RED . "NOTE: Make sure all the worlds with the Slapper NPC have been loaded!");
                            return true;
                        }

                        if(isset($plugin->migrateNPC[$sender->getName()], $args[1]) && $args[1] === "confirm"){
                            unset($plugin->migrateNPC[$sender->getName()]);
                            $sender->sendMessage(TextFormat::DARK_GREEN . "Migrating NPC... Please wait...");

                            foreach($plugin->getServer()->getLevels() as $level){
                                $entity = array_map(static function (Entity $entity){
                                }, array_filter($level->getEntities(), static function (Entity $entity): bool{
                                    return $entity instanceof SlapperHuman or $entity instanceof SlapperEntity;
                                }));

                                if(count($entity) === 0){
                                    $sender->sendMessage(TextFormat::RED . "Migrating failed: No Slapper-NPC found!");
                                    return true;
                                }

                                $error = 0;
                                foreach($level->getEntities() as $entity){
                                    if($entity instanceof SlapperEntity){
                                        /** @phpstan-ignore-next-line */
                                        if(NPCManager::createNPC(AddActorPacket::LEGACY_ID_MAP_BC[$entity::TYPE_ID], $sender, $entity->getNameTag(), $entity->namedtag->getCompoundTag("Commands"))){
                                            if(!$entity->isFlaggedForDespawn()){
                                                $entity->flagForDespawn();
                                            }
                                        }else{
                                            ++$error;
                                        }
                                    }elseif($entity instanceof SlapperHuman){
                                        $plugin->getServer()->getAsyncPool()->submitTask(new SpawnHumanNPCTask($entity->getNameTag(), $sender->getName(), $plugin->getDataFolder(), false, null, $entity->namedtag->getCompoundTag("Commands"), $entity->getSkin(), $entity->getLocation()));
                                        // TODO: QueueSytem (don't spam async task)
                                        if(!$entity->isFlaggedForDespawn()){
                                            $entity->flagForDespawn();
                                        }
                                    }
                                }

                                if($error === 0){
                                    $sender->sendMessage(TextFormat::GREEN . "The migration was successful, you can safely remove the Slapper plugin now");
                                }else{
                                    $sender->sendMessage(TextFormat::RED . "(" . $error . " error found) " . TextFormat::YELLOW . "It seems that the migration is not going well, please fix the error so that it can be fully migrated. Don't delete Slapper Plugin now");
                                }
                            }

                            return true;
                        }

                        if(isset($plugin->migrateNPC[$sender->getName()], $args[1]) && $args[1] === "cancel"){
                            $sender->sendMessage(TextFormat::GREEN . "Migrating NPC cancelled!");
                            unset($plugin->migrateNPC[$sender->getName()]);
                            return true;
                        }
                    }else{
                        $sender->sendMessage(TextFormat::RED . "Slapper plugin is missing, cannnot migrating.");
                    }
                    break;
                case "list":
                    if(!$sender->hasPermission("simplenpc.list")){
                        return true;
                    }

                    foreach($plugin->getServer()->getLevels() as $world){
                        $entityNames = array_map(static function (Entity $entity): string{
                            return TextFormat::YELLOW . "ID: (" . $entity->getId() . ") " . TextFormat::GREEN . $entity->getNameTag() . " §7-- §b" . $entity->getLevel()->getFolderName() . ": " . $entity->getFloorX() . "/" . $entity->getFloorY() . "/" . $entity->getFloorZ();
                        }, array_filter($world->getEntities(), static function (Entity $entity): bool{
                            return $entity instanceof BaseNPC or $entity instanceof CustomHuman;
                        }));

                        $sender->sendMessage("§csNPC List and Location: (" . count($entityNames) . ")\n §f- " . implode("\n - ", $entityNames));
                    }
                    break;
                case "help":
                    $sender->sendMessage("\n§7---- ---- ---- - ---- ---- ----\n§eCommand List:\n§2» /snpc spawn <type> <nametag> <canWalk> <skinUrl>\n§2» /snpc edit <id>\n§2» /snpc reload	\n§2» /snpc ui\n§2» /snpc remove <id>\n§2» /snpc migrate <confirm | cancel>\n§2» /snpc list\n§7---- ---- ---- - ---- ---- ----");
                    break;
                default:
                    $sender->sendMessage(TextFormat::RED . "Subcommand '$args[0]' not found! Try '/snpc help' for help.");
                    break;
            }
        }else{
            $sender->sendMessage("§7---- ---- [ §3SimpleNPC§7 ] ---- ----\n§bAuthor: @brokiem\n§3Source Code: github.com/brokiem/SimpleNPC\nVersion " . $this->getPlugin()->getDescription()->getVersion() . "\n§7---- ---- ---- - ---- ---- ----");
        }

        return parent::execute($sender, $commandLabel, $args);
    }
}