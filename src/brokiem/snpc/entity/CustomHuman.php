<?php

declare(strict_types=1);

namespace brokiem\snpc\entity;

use brokiem\snpc\manager\command\CommandManager;
use brokiem\snpc\SimpleNPC;
use pocketmine\entity\Human;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Config;

class CustomHuman extends Human {
    protected $gravity = 0.0;

    private string $identifier;
    protected bool $canWalk;
    protected bool $lookToPlayers;

    protected CompoundTag $skinTag;
    protected CommandManager $commandManager;

    public function getIdentifier(): string {
        return $this->identifier;
    }

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);

        $skinTag = $nbt->getCompoundTag("Skin");
        $identifier = $nbt->getString("Identifier");

        if ($skinTag === null) {
            throw new \UnexpectedValueException("Missing skin data");
        }

        $this->identifier = $identifier;
        $this->commandManager = new CommandManager($this);
        $this->skinTag = $skinTag;

        $this->canWalk = $this->getConfig()->get("walk", false);
        $this->lookToPlayers = $this->getConfig()->get("enableRotate", true);

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

    public function canWalk(): bool {
        return $this->canWalk;
    }

    public function setCanLookToPlayers(bool $value): void {
        $this->getConfig()->set("enableRotate", $value);
        $this->getConfig()->save();

        $this->lookToPlayers = $value;
    }

    public function canLookToPlayers(): bool {
        return $this->lookToPlayers;
    }

    public function setSkinTag(CompoundTag $tag): void {
        $this->skinTag = $tag;
    }

    public function getSkinTag(): CompoundTag {
        return $this->skinTag;
    }

    public function getCommandManager(): CommandManager {
        return $this->commandManager;
    }
}