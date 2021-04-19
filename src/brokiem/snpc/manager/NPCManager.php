<?php /** @noinspection MkdirRaceConditionInspection */

declare(strict_types=1);

namespace brokiem\snpc\manager;

use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;
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
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class NPCManager {

    /** @var array */
    public static $npcs = [BatNPC::class => ["bat_snpc", "simplenpc:bat"], BlazeNPC::class => ["blaze_snpc", "simplenpc:blaze"], ChickenNPC::class => ["chicken_snpc", "simplenpc:chicken"], CowNPC::class => ["cow_snpc", "simplenpc:cow"], CreeperNPC::class => ["creeper_snpc", "simplenpc:creeper"], EndermanNPC::class => ["enderman_snpc", "simplenpc:enderman"], HorseNPC::class => ["horse_snpc", "simplenpc:horse"], OcelotNPC::class => ["ocelot_snpc", "simplenpc:ocelot"], PigNPC::class => ["pig_snpc", "simplenpc:pig"], PolarBearNPC::class => ["polar_bear_snpc", "simplenpc:polarbear"], SheepNPC::class => ["sheep_snpc", "simplenpc:sheep"], ShulkerNPC::class => ["shulker_snpc", "simplenpc:shulker"], SkeletonNPC::class => ["skeleton_snpc", "simplenpc:skeleton"], SlimeNPC::class => ["slime_snpc", "simplenpc:slime"], SnowGolem::class => ["snow_golem_snpc", "simplenpc:snowgolem"], SpiderNPC::class => ["spider_snpc", "simplenpc:spider"], VillagerNPC::class => ["villager_snpc", "simplenpc:villager"], WitchNPC::class => ["witch_snpc", "simplenpc:witch"], WolfNPC::class => ["wolf_snpc", "simplenpc:wolf"], ZombieNPC::class => ["zombie_snpc", "simplenpc:zombie"]];

    public static function registerAllNPC(): void{
        foreach(self::$npcs as $class => $saveNames){
            SimpleNPC::registerEntity($class, array_shift($saveNames), true, $saveNames);
        }
    }

    public static function createNPC(string $type, Player $player, ?string $nametag = null, CompoundTag $commands = null, Location $customPos = null): bool{
        $nbt = Entity::createBaseNBT($player, null, $player->getYaw(), $player->getPitch());
        if($customPos !== null){
            $nbt = Entity::createBaseNBT($customPos, null, $customPos->getYaw(), $customPos->getPitch());
        }

        $nbt->setTag($commands ?? new CompoundTag("Commands", []));
        $nbt->setShort("Walk", 0);
        $position = $customPos ?? $player;
        $nbt->setString("Identifier", self::saveNPC($type, ["type" => $type, "nametag" => $nametag, "world" => $player->getLevelNonNull()->getFolderName(), "showNametag" => $nametag !== null, "skinId" => null, "skinData" => null, "capeData" => "", "geometryName" => "", "geometryData" => "", "walk" => 0, "commands" => $commands === null ? [] : $commands->getValue(), "position" => [$position->getX(), $position->getY(), $position->getZ(), $position->getYaw(), $position->getPitch()]]));

        $entity = self::createEntity($type, $player->getLevelNonNull(), $nbt);

        if($entity === null){
            $player->sendMessage(TextFormat::RED . "Entity is null or entity $type is invalid, make sure you register the entity first!");
            return false;
        }

        if($nametag !== null){
            $entity->setNameTag($nametag);
            $entity->setNameTagAlwaysVisible();
        }

        $entity->setGenericFlag(Entity::DATA_FLAG_SILENT, true);
        $entity->spawnToAll();
        $player->sendMessage(TextFormat::GREEN . "NPC " . ucfirst($type) . " created successfully! ID: " . $entity->getId());
        return true;
    }

    public static function saveNPC(string $type, array $saves): string{
        if(!is_dir(SimpleNPC::getInstance()->getDataFolder() . "npcs")){
            mkdir(SimpleNPC::getInstance()->getDataFolder() . "npcs");
        }

        $identifier = uniqid("$type-", true);
        $path = SimpleNPC::getInstance()->getDataFolder() . "npcs/" . "$identifier.json";

        $npcConfig = new Config($path, Config::JSON);
        $npcConfig->set("version", SimpleNPC::getInstance()->getDescription()->getVersion());
        $npcConfig->set("identifier", $identifier);
        foreach($saves as $save => $value){
            $npcConfig->set($save, $value);
        }

        $npcConfig->save();
        return $identifier;
    }

    public static function createEntity(string $type, Level $world, CompoundTag $nbt): ?Entity{
        if(isset(SimpleNPC::$entities[$type])){
            /** @var Entity $class */
            $class = SimpleNPC::$entities[$type];

            return new $class($world, $nbt);
        }

        return null;
    }

    public static function removeNPC(string $identifier, Entity $entity): bool{
        if($entity instanceof BaseNPC or $entity instanceof CustomHuman){
            if(!$entity->isFlaggedForDespawn()){
                $entity->flagForDespawn();
            }
            
            if(is_file($path = SimpleNPC::getInstance()->getDataFolder() . "npcs/$identifier.json")){
                unlink($path);
                SimpleNPC::getInstance()->getLogger()->debug("Removed NPC File: $path");
                return true;
            }
        }

        return false;
    }

    /**
     * @return null|BaseNPC[]|CustomHuman[]
     */
    public static function getAllNPCs(): ?array{
        $npcs = null;
        foreach(SimpleNPC::getInstance()->getServer()->getLevels() as $world){
            $npcs = array_map(static function (Entity $entity): Entity{
                return $entity;
            }, array_filter($world->getEntities(), static function (Entity $entity): bool{
                return $entity instanceof BaseNPC or $entity instanceof CustomHuman;
            }));
        }

        return $npcs;
    }
}