<?php

declare(strict_types=1);

namespace brokiem\snpc\manager;

use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\entity\npc\AxolotlNPC;
use brokiem\snpc\entity\npc\BatNPC;
use brokiem\snpc\entity\npc\BlazeNPC;
use brokiem\snpc\entity\npc\ChickenNPC;
use brokiem\snpc\entity\npc\CowNPC;
use brokiem\snpc\entity\npc\CreeperNPC;
use brokiem\snpc\entity\npc\EndermanNPC;
use brokiem\snpc\entity\npc\GlowsquidNPC;
use brokiem\snpc\entity\npc\GoatNPC;
use brokiem\snpc\entity\npc\HorseNPC;
use brokiem\snpc\entity\npc\OcelotNPC;
use brokiem\snpc\entity\npc\PigNPC;
use brokiem\snpc\entity\npc\PolarBearNPC;
use brokiem\snpc\entity\npc\SheepNPC;
use brokiem\snpc\entity\npc\ShulkerNPC;
use brokiem\snpc\entity\npc\SkeletonNPC;
use brokiem\snpc\entity\npc\SlimeNPC;
use brokiem\snpc\entity\npc\SnowGolem;
use brokiem\snpc\entity\npc\SpiderNPC;
use brokiem\snpc\entity\npc\VillagerNPC;
use brokiem\snpc\entity\npc\WitchNPC;
use brokiem\snpc\entity\npc\WolfNPC;
use brokiem\snpc\entity\npc\ZombieNPC;
use brokiem\snpc\event\SNPCCreationEvent;
use brokiem\snpc\event\SNPCDeletionEvent;
use brokiem\snpc\SimpleNPC;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

class NPCManager {
    use SingletonTrait;

    private static array $npcs = [
        GoatNPC::class => ["goat_snpc", "simplenpc:goat"],
        AxolotlNPC::class => ["axolotl_snpc", "simplenpc:axolotl"],
        GlowsquidNPC::class => ["glowsquid_snpc", "simplenpc:glowsquid"],
        BatNPC::class => ["bat_snpc", "simplenpc:bat"],
        BlazeNPC::class => ["blaze_snpc", "simplenpc:blaze"],
        ChickenNPC::class => ["chicken_snpc", "simplenpc:chicken"],
        CowNPC::class => ["cow_snpc", "simplenpc:cow"],
        CreeperNPC::class => ["creeper_snpc", "simplenpc:creeper"],
        EndermanNPC::class => ["enderman_snpc", "simplenpc:enderman"],
        HorseNPC::class => ["horse_snpc", "simplenpc:horse"],
        OcelotNPC::class => ["ocelot_snpc", "simplenpc:ocelot"],
        PigNPC::class => ["pig_snpc", "simplenpc:pig"],
        PolarBearNPC::class => ["polar_bear_snpc", "simplenpc:polarbear"],
        SheepNPC::class => ["sheep_snpc", "simplenpc:sheep"],
        ShulkerNPC::class => ["shulker_snpc", "simplenpc:shulker"],
        SkeletonNPC::class => ["skeleton_snpc", "simplenpc:skeleton"],
        SlimeNPC::class => ["slime_snpc", "simplenpc:slime"],
        SnowGolem::class => ["snow_golem_snpc", "simplenpc:snowgolem"],
        SpiderNPC::class => ["spider_snpc", "simplenpc:spider"],
        VillagerNPC::class => ["villager_snpc", "simplenpc:villager"],
        WitchNPC::class => ["witch_snpc", "simplenpc:witch"],
        WolfNPC::class => ["wolf_snpc", "simplenpc:wolf"],
        ZombieNPC::class => ["zombie_snpc", "simplenpc:zombie"]
    ];

    public function getNPCs(): array {
        return self::$npcs;
    }

    public function registerAllNPC(): void {
        foreach (self::$npcs as $class => $saveNames) {
            SimpleNPC::registerEntity($class, array_shift($saveNames), $saveNames);
        }
    }

    public function spawnNPC(string $type, Player $player, ?string $nametag = null, ?array $commands = [], ?Location $customPos = null, ?string $skinData = null, bool $canWalk = false): bool {
        $nbt = EntityDataHelper::createBaseNBT($player->getLocation(), null, $player->getLocation()->getYaw(), $player->getLocation()->getPitch());

        if ($customPos !== null) {
            $nbt = EntityDataHelper::createBaseNBT($customPos, null, $customPos->getYaw(), $customPos->getPitch());
        }

        $nbt->setTag("Skin", CompoundTag::create()
            ->setString("Name", $player->getSkin()->getSkinId())
            ->setByteArray("Data", $skinData ?? $player->getSkin()->getSkinData())
            ->setByteArray("CapeData", $player->getSkin()->getCapeData())
            ->setString("GeometryName", $player->getSkin()->getGeometryName())
            ->setByteArray("GeometryData", $player->getSkin()->getGeometryData())
        );

        if ($type === SimpleNPC::ENTITY_HUMAN and $canWalk) {
            $type = SimpleNPC::ENTITY_WALKING_HUMAN;
        }

        $pos = $customPos ?? $player->getLocation();
        $nbt->setString("Identifier", $this->saveNPCData($type, [
            "version", SimpleNPC::getInstance()->getDescription()->getVersion(),
            "type" => $type,
            "nametag" => $nametag,
            "world" => $player->getWorld()->getFolderName(),
            "enableRotate" => true,
            "showNametag" => $nametag !== null,
            "scale" => 1.0,
            "walk" => $canWalk,
            "commands" => $commands ?? [],
            "position" => [$pos->getX(), $pos->getY(), $pos->getZ(), $pos->getYaw(), $pos->getPitch()]
        ]));

        $entity = $this->createEntity($type, $pos, $nbt);

        if ($entity === null) {
            $player->sendMessage(TextFormat::RED . "Entity is null or entity $type is invalid");
            return false;
        }

        if ($nametag !== null) {
            $entity->setNameTag(str_replace("{line}", "\n", $nametag));
            $entity->setNameTagAlwaysVisible();
        }

        $entity->spawnToAll();
        $player->sendMessage(TextFormat::GREEN . "NPC " . ucfirst($type) . " created successfully! ID: " . $entity->getId());

        (new SNPCCreationEvent($entity, $player))->call();

        if ($entity instanceof CustomHuman) {
            $this->saveSkinTag($entity);
        }
        return true;
    }

    public function saveSkinTag(CustomHuman $entity): void {
        $file = SimpleNPC::getInstance()->getDataFolder() . "npcs/" . $entity->getIdentifier() . ".dat";
        file_put_contents($file, zlib_encode((new LittleEndianNbtSerializer())->write(new TreeRoot($entity->getSkinTag())), ZLIB_ENCODING_GZIP));
    }

    public function getSkinTag(CustomHuman $human): ?CompoundTag {
        $file = SimpleNPC::getInstance()->getDataFolder() . "npcs/" . $human->getIdentifier() . ".dat";

        if (is_file($file)) {
            $decompressed = @zlib_decode(file_get_contents($file));
            return (new LittleEndianNbtSerializer())->read($decompressed)->mustGetCompoundTag();
        }

        return null;
    }

    public function saveNPCData(string $type, array $saves): string {
        if (!is_dir(SimpleNPC::getInstance()->getDataFolder() . "npcs")) {
            mkdir(SimpleNPC::getInstance()->getDataFolder() . "npcs");
        }

        $identifier = uniqid("$type-", true);
        $path = SimpleNPC::getInstance()->getDataFolder() . "npcs/$identifier.json";

        $npcConfig = new Config($path, Config::JSON);
        $npcConfig->set("identifier", $identifier);
        foreach ($saves as $save => $value) {
            $npcConfig->set($save, $value);
        }

        $npcConfig->save();
        return $identifier;
    }

    public function createEntity(string $type, Location $location, CompoundTag $nbt): ?Entity {
        if (isset(SimpleNPC::$entities[$type])) {
            /** @var Entity $class */
            $class = SimpleNPC::$entities[$type];

            if (is_a($class, BaseNPC::class, true)) {
                return new $class($location, $nbt);
            }

            if (is_a($class, CustomHuman::class, true)) {
                $skin = Human::parseSkinNBT($nbt);
                return new $class($location, $skin, $nbt);
            }
        }

        return null;
    }

    public function removeNPC(string $identifier, Entity $entity): bool {
        if ($entity instanceof BaseNPC || $entity instanceof CustomHuman) {
            (new SNPCDeletionEvent($entity))->call();

            if (!$entity->isFlaggedForDespawn()) {
                $entity->flagForDespawn();
            }

            $jsonPath = SimpleNPC::getInstance()->getDataFolder() . "npcs/$identifier.json";
            $datPath = SimpleNPC::getInstance()->getDataFolder() . "npcs/$identifier.dat";

            if (is_file($jsonPath)) {
                unlink($jsonPath);
            }

            if (is_file($datPath)) {
                unlink($datPath);
            }

            return true;
        }

        return false;
    }

    public function applyArmorFrom(Player $player, CustomHuman $npc): void {
        $npc->getArmorInventory()->setContents($player->getArmorInventory()->getContents());
    }

    public function sendHeldItemFrom(Player $player, CustomHuman $npc): void {
        $npc->getInventory()->setItemInHand($player->getInventory()->getItemInHand());
    }

    /**
     * @param CustomHuman|BaseNPC $entity
     */
    public function interactToNPC($entity, Player $player): void {
        $plugin = SimpleNPC::getInstance();

        if (isset($plugin->idPlayers[$player->getName()])) {
            $player->sendMessage(TextFormat::GREEN . "NPC ID: " . $entity->getId());
            unset($plugin->idPlayers[$player->getName()]);
            return;
        }

        if (isset($plugin->removeNPC[$player->getName()]) && !$entity->isFlaggedForDespawn()) {
            if ($this->removeNPC($entity->getIdentifier(), $entity)) {
                $player->sendMessage(TextFormat::GREEN . "The NPC was successfully removed!");
            } else {
                $player->sendMessage(TextFormat::YELLOW . "The NPC was failed removed! (File not found)");
            }
            unset($plugin->removeNPC[$player->getName()]);
            return;
        }

        if ($plugin->settings["enableCommandCooldown"] ?? true) {
            if (!isset($plugin->lastHit[$player->getName()][$entity->getId()])) {
                $plugin->lastHit[$player->getName()][$entity->getId()] = microtime(true);
                goto execute;
            }

            $coldown = $plugin->settings["commandExecuteCooldown"] ?? 1.0;
            if (($coldown + (float)$plugin->lastHit[$player->getName()][$entity->getId()]) > microtime(true)) {
                return;
            }

            $plugin->lastHit[$player->getName()][$entity->getId()] = microtime(true);
        }

        execute:
        if (($commands = $entity->getCommandManager()->getAll()) !== null) {
            foreach ($commands as $command) {
                $plugin->getServer()->getCommandMap()->dispatch(new ConsoleCommandSender($player->getServer(), $player->getLanguage()), str_replace("{player}", $player->getName(), $command));
            }
        }
    }

    /**
     * @return null|BaseNPC[]|CustomHuman[]
     */
    public function getAllNPCs(): ?array {
        $npcs = null;
        foreach (SimpleNPC::getInstance()->getServer()->getWorldManager()->getWorlds() as $world) {
            $npcs = array_map(static function(Entity $entity): Entity {
                return $entity;
            }, array_filter($world->getEntities(), static function(Entity $entity): bool {
                return $entity instanceof BaseNPC or $entity instanceof CustomHuman;
            }));
        }

        return $npcs;
    }
}