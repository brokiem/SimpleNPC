<?php

declare(strict_types=1);

namespace brokiem\snpc\manager;

use brokiem\snpc\entity\npc\BatNPC;
use brokiem\snpc\entity\npc\BlazeNPC;
use brokiem\snpc\entity\npc\ChickenNPC;
use brokiem\snpc\entity\npc\CowNPC;
use brokiem\snpc\entity\npc\CreeperNPC;
use brokiem\snpc\entity\npc\EndermanNPC;
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
use brokiem\snpc\SimpleNPC;
use pocketmine\entity\Entity;
use pocketmine\level\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class NPCManager
{

    private static $npcs = [
        BatNPC::class => ["bat_npc", "simplenpc:bat"],
        BlazeNPC::class => ["blaze_npc", "simplenpc:blaze"],
        ChickenNPC::class => ["chicken_npc", "simplenpc:chicken"],
        CowNPC::class => ["cow_npc", "simplenpc:cow"],
        CreeperNPC::class => ["creeper_npc", "simplenpc:creeper"],
        EndermanNPC::class => ["enderman_npc", "simplenpc:enderman"],
        HorseNPC::class => ["horse_npc", "simplenpc:horse"],
        OcelotNPC::class => ["ocelot_npc", "simplenpc:ocelot"],
        PigNPC::class => ["pig_npc", "simplenpc:pig"],
        PolarBearNPC::class => ["polar_bear_npc", "simplenpc:polarbear"],
        SheepNPC::class => ["sheep_npc", "simplenpc:sheep"],
        ShulkerNPC::class => ["shulker_npc", "simplenpc:shulker"],
        SkeletonNPC::class => ["skeleton_npc", "simplenpc:skeleton"],
        SlimeNPC::class => ["slime_npc", "simplenpc:slime"],
        SnowGolem::class => ["snow_golem_npc", "simplenpc:snowgolem"],
        SpiderNPC::class => ["spider_npc", "simplenpc:spider"],
        VillagerNPC::class => ["villager_npc", "simplenpc:villager"],
        WitchNPC::class => ["witch_npc", "simplenpc:witch"],
        WolfNPC::class => ["wolf_npc", "simplenpc:wolf"],
        ZombieNPC::class => ["zombie_npc", "simplenpc:zombie"]
    ];

    public static function registerAllNPC(): void {
        foreach (self::$npcs as $class => $saveNames) {
            SimpleNPC::registerEntity($class, array_shift($saveNames), true, $saveNames);
        }
    }

    public static function createNPC(string $type, Player $player, ?string $nametag = null, CompoundTag $commands = null, Location $customPos = null): bool
    {
        $nbt = Entity::createBaseNBT($player, null, $player->getYaw(), $player->getPitch());
        $nbt->setTag($commands ?? new CompoundTag("Commands", []));
        $nbt->setShort("Walk", 0);

        if ($customPos !== null) {
            $nbt = Entity::createBaseNBT($customPos, null, $customPos->getYaw(), $customPos->getPitch());
        }

        $entity = Entity::createEntity($type, $player->getLevel(), $nbt); // TODO: get rid of this function (doesn't exist in PM4)

        if ($entity === null) {
            $player->sendMessage(TextFormat::RED . "Entity is null or entity $type is invalid, make sure you register the entity first!");
            return false;
        }

        if ($nametag !== null) {
            $entity->setNameTag($nametag);
            $entity->setNameTagAlwaysVisible();
        }

        $entity->spawnToAll();
        $player->sendMessage(TextFormat::GREEN . "NPC " . ucfirst($type) . " created successfully!");
        return true;
    }
}