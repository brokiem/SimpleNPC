<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\entity;

use brokiem\snpc\manager\command\CommandManager;
use brokiem\snpc\SimpleNPC;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\utils\Config;

abstract class BaseNPC extends Entity {

    protected $gravity = 0.0;

    private string $identifier;
    protected bool $lookToPlayers;

    protected CommandManager $commandManager;

    public function getIdentifier(): string {
        return $this->identifier;
    }

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);

        $identifier = $nbt->getString("Identifier");

        $this->identifier = $identifier;
        $this->commandManager = new CommandManager($this);
        $this->lookToPlayers = $this->getConfig()->get("enableRotate", true);

        $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::SILENT, true);
        $this->setNameTagAlwaysVisible();

        $scale = (float)$this->getConfig()->get("scale", 1.0);
        if ($this->getScale() !== $scale) {
            $this->setScale($scale);
        }
    }

    public function saveNBT(): CompoundTag {
        $nbt = parent::saveNBT();
        $nbt->setString("Identifier", $this->identifier);
        return $nbt;
    }

    public function getConfig(): Config {
        return new Config(SimpleNPC::getInstance()->getDataFolder() . "npcs/$this->identifier.json", Config::JSON);
    }

    public function setCanLookToPlayers(bool $value): void {
        $this->getConfig()->set("enableRotate", $value);
        $this->getConfig()->save();

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