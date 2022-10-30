<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

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
use brokiem\snpc\SimpleNPC;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\NoSuchTagException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function is_string;
use function unlink;

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

    public function getDefaultNPCs(): array {
        return self::$npcs;
    }

    public function registerAllNPC(): void {
        foreach (self::$npcs as $class => $saveNames) {
            SimpleNPC::registerEntity($class, array_shift($saveNames), $saveNames);
        }
    }

    public function spawnNPC(string $type, Player $player, ?string $nametag = null, ?Location $customPos = null, ?string $skinData = null): ?int {
        $nbt = $this->createBaseNBT($player->getLocation(), null, $player->getLocation()->getYaw(), $player->getLocation()->getPitch());

        if ($customPos !== null) {
            $nbt = $this->createBaseNBT($customPos, null, $customPos->getYaw(), $customPos->getPitch());
        }

        $pos = $customPos ?? $player->getLocation();
        if (isset(SimpleNPC::$entities[$type]) && is_a(SimpleNPC::$entities[$type], CustomHuman::class, true)) {
            $nbt->setTag("Skin", CompoundTag::create()
                ->setString("Name", $player->getSkin()->getSkinId())
                ->setByteArray("Data", $skinData ?? $player->getSkin()->getSkinData())
                ->setByteArray("CapeData", $player->getSkin()->getCapeData())
                ->setString("GeometryName", $player->getSkin()->getGeometryName())
                ->setByteArray("GeometryData", $player->getSkin()->getGeometryData())
            );
        }

        $entity = $this->createEntity($type, $pos, $nbt);

        if ($entity === null) {
            $player->sendMessage(TextFormat::RED . "Entity is null or entity $type is invalid");
            return null;
        }

        if ($nametag !== null) {
            $entity->setNameTag(str_replace("{line}", "\n", $nametag));
            $entity->setNameTagAlwaysVisible();
        }

        $entity->spawnToAll();
        $player->sendMessage(TextFormat::GREEN . "NPC " . ucfirst($type) . " created successfully! ID: " . $entity->getId());

        (new SNPCCreationEvent($entity, $player))->call();

        return $entity->getId();
    }

    public function createEntity(string $type, Location $location, CompoundTag $nbt): ?Entity {
        if (isset(SimpleNPC::$entities[$type])) {
            /** @var Entity $class */
            $class = SimpleNPC::$entities[$type];

            if (is_a($class, BaseNPC::class, true)) {
                return new $class($location, $nbt);
            }

            if (is_a($class, CustomHuman::class, true)) {
                return new $class($location, Human::parseSkinNBT($nbt), $nbt);
            }
        }

        return null;
    }

    /**
     * Helper function which creates minimal NBT needed to spawn an entity.
     */
    public function createBaseNBT(Vector3 $pos, ?Vector3 $motion = null, float $yaw = 0.0, float $pitch = 0.0): CompoundTag {
        return CompoundTag::create()
            ->setTag("Pos", new ListTag([
                new DoubleTag($pos->x),
                new DoubleTag($pos->y),
                new DoubleTag($pos->z)
            ]))
            ->setTag("Motion", new ListTag([
                new DoubleTag($motion !== null ? $motion->x : 0.0),
                new DoubleTag($motion !== null ? $motion->y : 0.0),
                new DoubleTag($motion !== null ? $motion->z : 0.0)
            ]))
            ->setTag("Rotation", new ListTag([
                new FloatTag($yaw),
                new FloatTag($pitch)
            ]));
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

    public function getOldNPCData(string $identifier): array
    {
        $path = SimpleNPC::getInstance()->getDataFolder() . "npcs/$identifier.json";
        return file_exists($path)
            ? (new Config($path, Config::JSON))->getAll()
            : [];
    }

    public function updateOldNPCs(World $world): void
    {
        foreach ($world->getEntities() as $entity)
            if ($entity instanceof BaseNPC) {
                $ident = null;
                try {
                    $ident = $entity->saveNBT()->getString("Identifier");
                } catch (NoSuchTagException $e) {}

                if (is_string($ident)) {
                    $data = $this->getOldNPCData($ident);
                    if (isset($data["nametag"]))
                        $entity->setNameTag($data["nametag"]);
                    if (isset($data["showNametag"]))
                        $entity->setNameTagVisible($data["showNametag"]);
                    if (isset($data["scale"]))
                        $entity->setScale($data["scale"]);
                    if (isset($data["commands"]))
                        foreach ($data["commands"] as $command)
                            $entity->getCommandManager()->add($command);

                    unlink(SimpleNPC::getInstance()->getDataFolder() . "npcs/$ident.json");
                    $entity->respawnToAll();
                }
            }
    }
}
