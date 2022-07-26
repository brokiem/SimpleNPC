<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\entity;

use brokiem\snpc\event\SNPCDeletionEvent;
use brokiem\snpc\manager\command\CommandManager;
use brokiem\snpc\SimpleNPC;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

abstract class BaseNPC extends Entity {

    protected $gravity = 0.0;

    protected bool $lookToPlayers;

    protected CommandManager $commandManager;

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);

        $this->commandManager = new CommandManager($nbt);
        $this->lookToPlayers = (bool)$nbt->getByte("EnableRotation", 0);

        $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::SILENT, true);

        $this->setScale($nbt->getFloat("Scale", 1));
    }

    public function saveNBT(): CompoundTag {
        $nbt = parent::saveNBT();
        $nbt->setFloat("Scale", $this->getScale()); //pm doesn't save this to the nbt
        $nbt->setByte("EnableRotation", (int)$this->lookToPlayers);

        $listTag = new ListTag([], NBT::TAG_String); //commands
        foreach ($this->commandManager->getAll() as $command) {
            $listTag->push(new StringTag($command));
        }
        $nbt->setTag("Commands", $listTag);
        return $nbt;
    }

    public function despawn(Player $deletor = null): bool {
        (new SNPCDeletionEvent($this, $deletor))->call();

        if (!$this->isFlaggedForDespawn()) {
            $this->flagForDespawn();
        }

        return true;
    }

    public function interact(Player $player): void {
        $plugin = SimpleNPC::getInstance();

        if (isset($plugin->idPlayers[$player->getName()])) {
            $player->sendMessage(TextFormat::GREEN . "NPC ID: " . $this->getId());
            unset($plugin->idPlayers[$player->getName()]);
            return;
        }

        if (isset($plugin->removeNPC[$player->getName()]) && !$this->isFlaggedForDespawn()) {
            if ($this->despawn($player)) {
                $player->sendMessage(TextFormat::GREEN . "The NPC was successfully removed!");
            } else {
                $player->sendMessage(TextFormat::YELLOW . "The NPC was failed removed! (File not found)");
            }
            unset($plugin->removeNPC[$player->getName()]);
            return;
        }

        if ($plugin->getConfig()->get("enable-command-cooldown", true)) {
            if (!isset($plugin->lastHit[$player->getName()][$this->getId()])) {
                $plugin->lastHit[$player->getName()][$this->getId()] = microtime(true);
                goto execute;
            }

            $coldown = (float)$plugin->getConfig()->get("command-execute-cooldown", 1.0);
            if (($coldown + (float)$plugin->lastHit[$player->getName()][$this->getId()]) > microtime(true)) {
                return;
            }

            $plugin->lastHit[$player->getName()][$this->getId()] = microtime(true);
        }

        execute:
        if (!empty($commands = $this->getCommandManager()->getAll())) {
            foreach ($commands as $command) {
                $plugin->getServer()->getCommandMap()->dispatch(new ConsoleCommandSender($player->getServer(), $plugin->getServer()->getLanguage()), str_replace("{player}", '"' . $player->getName() . '"', $command));
            }
        }
    }

    public function setCanLookToPlayers(bool $value): void {
        $this->lookToPlayers = $value;
    }

    public function canLookToPlayers(): bool {
        return $this->lookToPlayers;
    }

    public function getCommandManager(): CommandManager {
        return $this->commandManager;
    }

    abstract protected function getInitialSizeInfo(): EntitySizeInfo;

    abstract public static function getNetworkTypeId(): string;
}