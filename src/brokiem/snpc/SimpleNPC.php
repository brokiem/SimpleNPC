<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc;

use brokiem\snpc\commands\Commands;
use brokiem\snpc\commands\RcaCommand;
use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\entity\WalkingHuman;
use brokiem\snpc\manager\NPCManager;
use brokiem\snpc\task\async\CheckUpdateTask;
use EasyUI\Form;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

class SimpleNPC extends PluginBase {
    use SingletonTrait;

    public const ENTITY_HUMAN = "human_snpc";
    public const ENTITY_WALKING_HUMAN = "walking_human_snpc";
  
    private static array $registeredNPC = [];
    public static array $entities = [];
    private static SimpleNPC $i;
    public array $migrateNPC = [];
    public array $removeNPC = [];
    public array $lastHit = [];
    public array $cachedUpdate = [];
    public array $idPlayers = [];
    private bool $isDev = true;

    protected function onEnable(): void {
        if (!class_exists(Form::class)) {
            $this->getLogger()->alert("UI/Form dependency not found! Please download this plugin from poggit or install the UI/Form virion. Disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        if ($this->isDev) {
            $this->getLogger()->warning("You are using the Development version of SimpleNPC. The plugin will experience errors, crashes, or bugs. Only use this version if you are testing. Don't use the Dev version in production!");
        }

        self::setInstance($this);
        self::registerEntity(CustomHuman::class, self::ENTITY_HUMAN);
        self::registerEntity(WalkingHuman::class, self::ENTITY_WALKING_HUMAN);
        NPCManager::getInstance()->registerAllNPC();

        $this->initConfiguration();
        $this->getServer()->getCommandMap()->registerAll("SimpleNPC", [new Commands("snpc", $this), new RcaCommand("rca", $this)]);
        $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void {
            $this->checkUpdate();
        }), 864000); // 12 hours
    }

    public static function registerEntity(string $entityClass, string $name, array $saveNames = []): void {
        if (!class_exists($entityClass)) {
            throw new \RuntimeException("Class $entityClass not found.");
        }

        $refClass = new \ReflectionClass($entityClass);
        if (is_a($entityClass, BaseNPC::class, true) || is_a($entityClass, CustomHuman::class, true) and !$refClass->isAbstract()) {
            self::$entities[$entityClass] = array_merge([$name], $saveNames);
            self::$registeredNPC[$name] = array_merge([$entityClass], $saveNames);

            foreach (array_merge([$name], $saveNames) as $saveName) {
                self::$entities[$saveName] = $entityClass;
            }

            if (is_a($entityClass, CustomHuman::class, true)) {
                EntityFactory::getInstance()->register($entityClass, function(World $world, CompoundTag $nbt) use ($entityClass): Entity {
                    return new $entityClass(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
                }, [$entityClass]);
            } else {
                EntityFactory::getInstance()->register($entityClass, function(World $world, CompoundTag $nbt) use ($entityClass): Entity {
                    return new $entityClass(EntityDataHelper::parseLocation($nbt, $world), $nbt);
                }, [$entityClass]);
            }
        }
    }

    public function getRegisteredNPC(): array {
        return self::$registeredNPC;
    }

    public function initConfiguration(): void {
        if (!is_dir($this->getDataFolder() . "npcs")) {
            mkdir($this->getDataFolder() . "npcs");
        }

        if ($this->getConfig()->get("config-version", 1) !== 3) {
            $this->getLogger()->notice("Your configuration file is outdated, updating the config.yml...");
            $this->getLogger()->notice("The old configuration file can be found at config.old.yml");

            rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config.old.yml");
        }

        $this->reloadConfig();
    }

    public function checkUpdate(bool $isRetry = false): void {
        $this->getServer()->getAsyncPool()->submitTask(new CheckUpdateTask($this->getDescription()->getName(), $this->getDescription()->getVersion(), $isRetry));
    }
}