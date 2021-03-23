<?php

declare(strict_types=1);

namespace brokiem\snpc;

use brokiem\snpc\commands\Commands;
use brokiem\snpc\commands\RcaCommand;
use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\entity\WalkingHuman;
use brokiem\snpc\manager\NPCManager;
use brokiem\snpc\task\async\CheckUpdateTask;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use ReflectionClass;

class SimpleNPC extends PluginBase
{
    public const ENTITY_HUMAN = "human";
    public const ENTITY_WALKING_HUMAN = "walking_human";

    /** @var array */
    public $migrateNPC = [];
    /** @var array */
    public static $npcType = [];
    /** @var array */
    private static $entities = [];
    /** @var array */
    public $removeNPC = [];
    /** @var array */
    public $settings = [];
    /** @var array */
    public $lastHit = [];

    public function onEnable(): void
    {
        self::registerEntity(CustomHuman::class, self::ENTITY_HUMAN, true);
        self::registerEntity(WalkingHuman::class, self::ENTITY_WALKING_HUMAN, true);
        NPCManager::registerAllNPC();

        $this->initConfiguration();
        $this->getServer()->getCommandMap()->registerAll("SimpleNPC", [
            new Commands("snpc", $this),
            new RcaCommand("rca", $this)
        ]);
        $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);
        $this->getServer()->getAsyncPool()->submitTask(new CheckUpdateTask($this->getDescription()->getVersion()));
    }

    private function initConfiguration(): void
    {
        if ($this->getConfig()->get("config-version") !== 2) {
            $this->getLogger()->notice("Your configuration file is outdated, updating the config.yml...");
            $this->getLogger()->notice("The old configuration file can be found at config.old.yml");

            rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config.old.yml");

            $this->reloadConfig();
        }

        $this->saveDefaultConfig();

        $this->settings["lookToPlayersEnabled"] = $this->getConfig()->get("enable-look-to-players", true);
        $this->settings["maxLookDistance"] = $this->getConfig()->get("max-look-distance", 8);
        $this->settings["commandExecuteColdown"] = (float)$this->getConfig()->get("command-execute-coldown", 2.0);
    }

    public static function registerEntity(string $entityClass, string $name, bool $force = true, array $saveNames = []): bool
    {
        $class = new ReflectionClass($entityClass);
        if (is_a($entityClass, BaseNPC::class, true) or is_a($entityClass, CustomHuman::class, true) and !$class->isAbstract()) {
            self::$entities[$entityClass] = array_merge($saveNames, [$name]);
            self::$npcType[] = $name;

            return Entity::registerEntity($entityClass, $force, array_merge($saveNames, [$name]));
        }

        return false;
    }
}