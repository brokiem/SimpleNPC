<?php

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
use brokiem\snpc\event\SNPCCreationEvent;
use brokiem\snpc\SimpleNPC;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use slapper\entities\SlapperEntity;
use slapper\entities\SlapperHuman;

class NPCManager {
    use SingletonTrait;

    /** @var array */
    private static $npcs = [
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

    public const LEGACY_ID_MAP_BC = [
        EntityIds::NPC => "simplenpc:npc",
        EntityIds::PLAYER => "simplenpc:player",
        EntityIds::WITHER_SKELETON => "simplenpc:wither_skeleton",
        EntityIds::HUSK => "simplenpc:husk",
        EntityIds::STRAY => "simplenpc:stray",
        EntityIds::WITCH => "simplenpc:witch",
        EntityIds::ZOMBIE_VILLAGER => "simplenpc:zombie_villager",
        EntityIds::BLAZE => "simplenpc:blaze",
        EntityIds::MAGMA_CUBE => "simplenpc:magma_cube",
        EntityIds::GHAST => "simplenpc:ghast",
        EntityIds::CAVE_SPIDER => "simplenpc:cave_spider",
        EntityIds::SILVERFISH => "simplenpc:silverfish",
        EntityIds::ENDERMAN => "simplenpc:enderman",
        EntityIds::SLIME => "simplenpc:slime",
        EntityIds::ZOMBIE_PIGMAN => "simplenpc:zombie_pigman",
        EntityIds::SPIDER => "simplenpc:spider",
        EntityIds::SKELETON => "simplenpc:skeleton",
        EntityIds::CREEPER => "simplenpc:creeper",
        EntityIds::ZOMBIE => "simplenpc:zombie",
        EntityIds::SKELETON_HORSE => "simplenpc:skeleton_horse",
        EntityIds::MULE => "simplenpc:mule",
        EntityIds::DONKEY => "simplenpc:donkey",
        EntityIds::DOLPHIN => "simplenpc:dolphin",
        EntityIds::TROPICALFISH => "simplenpc:tropicalfish",
        EntityIds::WOLF => "simplenpc:wolf",
        EntityIds::SQUID => "simplenpc:squid",
        EntityIds::DROWNED => "simplenpc:drowned",
        EntityIds::SHEEP => "simplenpc:sheep",
        EntityIds::MOOSHROOM => "simplenpc:mooshroom",
        EntityIds::PANDA => "simplenpc:panda",
        EntityIds::SALMON => "simplenpc:salmon",
        EntityIds::PIG => "simplenpc:pig",
        EntityIds::VILLAGER => "simplenpc:villager",
        EntityIds::COD => "simplenpc:cod",
        EntityIds::PUFFERFISH => "simplenpc:pufferfish",
        EntityIds::COW => "simplenpc:cow",
        EntityIds::CHICKEN => "simplenpc:chicken",
        EntityIds::BALLOON => "simplenpc:balloon",
        EntityIds::LLAMA => "simplenpc:llama",
        EntityIds::IRON_GOLEM => "simplenpc:iron_golem",
        EntityIds::RABBIT => "simplenpc:rabbit",
        EntityIds::SNOW_GOLEM => "simplenpc:snow_golem",
        EntityIds::BAT => "simplenpc:bat",
        EntityIds::OCELOT => "simplenpc:ocelot",
        EntityIds::HORSE => "simplenpc:horse",
        EntityIds::CAT => "simplenpc:cat",
        EntityIds::POLAR_BEAR => "simplenpc:polar_bear",
        EntityIds::ZOMBIE_HORSE => "simplenpc:zombie_horse",
        EntityIds::TURTLE => "simplenpc:turtle",
        EntityIds::PARROT => "simplenpc:parrot",
        EntityIds::GUARDIAN => "simplenpc:guardian",
        EntityIds::ELDER_GUARDIAN => "simplenpc:elder_guardian",
        EntityIds::VINDICATOR => "simplenpc:vindicator",
        EntityIds::WITHER => "simplenpc:wither",
        EntityIds::ENDER_DRAGON => "simplenpc:ender_dragon",
        EntityIds::SHULKER => "simplenpc:shulker",
        EntityIds::ENDERMITE => "simplenpc:endermite",
        EntityIds::MINECART => "simplenpc:minecart",
        EntityIds::HOPPER_MINECART => "simplenpc:hopper_minecart",
        EntityIds::TNT_MINECART => "simplenpc:tnt_minecart",
        EntityIds::CHEST_MINECART => "simplenpc:chest_minecart",
        EntityIds::COMMAND_BLOCK_MINECART => "simplenpc:command_block_minecart",
        EntityIds::ARMOR_STAND => "simplenpc:armor_stand",
        EntityIds::ITEM => "simplenpc:item",
        EntityIds::TNT => "simplenpc:tnt",
        EntityIds::FALLING_BLOCK => "simplenpc:falling_block",
        EntityIds::XP_BOTTLE => "simplenpc:xp_bottle",
        EntityIds::XP_ORB => "simplenpc:xp_orb",
        EntityIds::EYE_OF_ENDER_SIGNAL => "simplenpc:eye_of_ender_signal",
        EntityIds::ENDER_CRYSTAL => "simplenpc:ender_crystal",
        EntityIds::SHULKER_BULLET => "simplenpc:shulker_bullet",
        EntityIds::FISHING_HOOK => "simplenpc:fishing_hook",
        EntityIds::DRAGON_FIREBALL => "simplenpc:dragon_fireball",
        EntityIds::ARROW => "simplenpc:arrow",
        EntityIds::SNOWBALL => "simplenpc:snowball",
        EntityIds::EGG => "simplenpc:egg",
        EntityIds::PAINTING => "simplenpc:painting",
        EntityIds::THROWN_TRIDENT => "simplenpc:thrown_trident",
        EntityIds::FIREBALL => "simplenpc:fireball",
        EntityIds::SPLASH_POTION => "simplenpc:splash_potion",
        EntityIds::ENDER_PEARL => "simplenpc:ender_pearl",
        EntityIds::LEASH_KNOT => "simplenpc:leash_knot",
        EntityIds::WITHER_SKULL => "simplenpc:wither_skull",
        EntityIds::WITHER_SKULL_DANGEROUS => "simplenpc:wither_skull_dangerous",
        EntityIds::BOAT => "simplenpc:boat",
        EntityIds::LIGHTNING_BOLT => "simplenpc:lightning_bolt",
        EntityIds::SMALL_FIREBALL => "simplenpc:small_fireball",
        EntityIds::LLAMA_SPIT => "simplenpc:llama_spit",
        EntityIds::AREA_EFFECT_CLOUD => "simplenpc:area_effect_cloud",
        EntityIds::LINGERING_POTION => "simplenpc:lingering_potion",
        EntityIds::FIREWORKS_ROCKET => "simplenpc:fireworks_rocket",
        EntityIds::EVOCATION_FANG => "simplenpc:evocation_fang",
        EntityIds::EVOCATION_ILLAGER => "simplenpc:evocation_illager",
        EntityIds::VEX => "simplenpc:vex",
        EntityIds::AGENT => "simplenpc:agent",
        EntityIds::ICE_BOMB => "simplenpc:ice_bomb",
        EntityIds::PHANTOM => "simplenpc:phantom",
        EntityIds::TRIPOD_CAMERA => "simplenpc:tripod_camera"
    ];

    public function getNPCs(): array {
        return self::$npcs;
    }

    public function registerAllNPC(): void {
        foreach (self::$npcs as $class => $saveNames) {
            SimpleNPC::registerEntity($class, array_shift($saveNames), true, $saveNames);
        }
    }

    public function spawnNPC(string $type, Player $player, ?string $nametag = null, ?CompoundTag $commands = null, ?Location $customPos = null, ?string $skinData = null, bool $canWalk = false): bool {
        $nbt = Entity::createBaseNBT($player, null, $player->getYaw(), $player->getPitch());

        if ($customPos !== null) {
            $nbt = Entity::createBaseNBT($customPos, null, $customPos->getYaw(), $customPos->getPitch());
        }

        if ($type === SimpleNPC::ENTITY_HUMAN) {
            if ($skinData !== null) {
                $nbt->setTag(new CompoundTag("Skin", [
                    "Name" => new StringTag("Name", $player->getSkin()->getSkinId()),
                    "Data" => new ByteArrayTag("Data", $skinData),
                    "CapeData" => new ByteArrayTag("CapeData", $player->getSkin()->getCapeData()),
                    "GeometryName" => new StringTag("GeometryName", $player->getSkin()->getGeometryName()),
                    "GeometryData" => new ByteArrayTag("GeometryData", $player->getSkin()->getGeometryData())
                ]));
            } else {
                /** @phpstan-ignore-next-line */
                $nbt->setTag($player->namedtag->getTag("Skin"));
            }
        }

        if ($type === SimpleNPC::ENTITY_HUMAN and $canWalk) {
            $type = SimpleNPC::ENTITY_WALKING_HUMAN;
        }

        $nbt->setTag($commands ?? new CompoundTag("Commands", []));
        $nbt->setShort("Walk", (int)$canWalk);

        $pos = $customPos ?? $player;
        $nbt->setString("Identifier", $this->saveNPC($type, [
            "type" => $type,
            "nametag" => $nametag,
            "world" => $player->getLevelNonNull()->getFolderName(),
            "enableRotate" => true,
            "showNametag" => $nametag !== null,
            "scale" => 1.0,
            "walk" => $canWalk,
            "commands" => $commands === null ? [] : $commands->getValue(),
            "position" => [$pos->getX(), $pos->getY(), $pos->getZ(), $pos->getYaw(), $pos->getPitch()]
        ]));

        $entity = $this->createEntity($type, $player->getLevelNonNull(), $nbt);

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

        (new SNPCCreationEvent($entity))->call();

        if ($type === SimpleNPC::ENTITY_HUMAN || $type === SimpleNPC::ENTITY_WALKING_HUMAN) {
            $this->saveSkinTag($entity, $nbt);
        }
        $this->saveChunkNPC($entity);
        return true;
    }

    public function saveSkinTag(Entity $entity, CompoundTag $nbt): void {
        $skinTag = $nbt->getCompoundTag("Skin");

        if ($skinTag === null) {
            $skinTag = $nbt;
        }

        $file = SimpleNPC::getInstance()->getDataFolder() . "npcs/" . $entity->namedtag->getString("Identifier") . ".dat";
        file_put_contents($file, (new LittleEndianNBTStream())->writeCompressed($skinTag));
    }

    /**
     * @return \pocketmine\nbt\tag\NamedTag|\pocketmine\nbt\tag\NamedTag[]|null
     */
    public function getSkinTag(CustomHuman $human) {
        $file = SimpleNPC::getInstance()->getDataFolder() . "npcs/" . $human->namedtag->getString("Identifier") . ".dat";

        if (is_file($file)) {
            /** @phpstan-ignore-next-line */
            return (new LittleEndianNBTStream())->readCompressed(file_get_contents($file));
        }

        return null;
    }

    public function saveChunkNPC(Entity $entity): void {
        $chunk = $entity->chunk;
        if ($chunk !== null) {
            if (($chunk->hasChanged() or count($chunk->getSavableEntities()) > 0) and $chunk->isGenerated()) {
                $entity->getLevelNonNull()->getProvider()->saveChunk($chunk);
                $chunk->setChanged(false);
            }
        }
    }

    public function saveNPC(string $type, array $saves): string {
        if (!is_dir(SimpleNPC::getInstance()->getDataFolder() . "npcs")) {
            mkdir(SimpleNPC::getInstance()->getDataFolder() . "npcs");
        }

        $identifier = uniqid("$type-", true);
        $path = SimpleNPC::getInstance()->getDataFolder() . "npcs/$identifier.json";

        $npcConfig = new Config($path, Config::JSON);
        $npcConfig->set("version", SimpleNPC::getInstance()->getDescription()->getVersion());
        $npcConfig->set("identifier", $identifier);
        foreach ($saves as $save => $value) {
            $npcConfig->set($save, $value);
        }

        $npcConfig->save();
        return $identifier;
    }

    public function createEntity(string $type, Level $world, CompoundTag $nbt): ?Entity {
        if (isset(SimpleNPC::$entities[$type])) {
            /** @var Entity $class */
            $class = SimpleNPC::$entities[$type];

            return new $class($world, $nbt);
        }

        return null;
    }

    public function removeNPC(string $identifier, Entity $entity): bool {
        if ($entity instanceof BaseNPC || $entity instanceof CustomHuman) {
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

    public function migrateNPC(Player $sender, array $args): bool {
        $plugin = SimpleNPC::getInstance();

        if ($plugin->getServer()->getPluginManager()->getPlugin("Slapper") !== null) {
            if (!isset($args[1]) && !isset($plugin->migrateNPC[$sender->getName()])) {
                $plugin->migrateNPC[$sender->getName()] = true;

                $plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($plugin, $sender): void {
                    if (isset($plugin->migrateNPC[$sender->getName()])) {
                        unset($plugin->migrateNPC[$sender->getName()]);
                        $sender->sendMessage(TextFormat::YELLOW . "Migrating NPC Cancelled! (10 seconds)");
                    }
                }), 10 * 20);

                $sender->sendMessage(TextFormat::RED . " \nAre you sure want to migrate your NPC from Slapper to SimpleNPC? \nThis will replace the slapper NPCs with the new Simple NPCs\n\nIf yes, run /migrate confirm, if no, run /migrate cancel\n\n ");
                $sender->sendMessage(TextFormat::RED . "NOTE: Make sure all the worlds with the Slapper NPC have been loaded!");
                return true;
            }

            if (isset($plugin->migrateNPC[$sender->getName()], $args[1]) && $args[1] === "confirm") {
                unset($plugin->migrateNPC[$sender->getName()]);
                $sender->sendMessage(TextFormat::DARK_GREEN . "Migrating NPC... Please wait...");

                foreach ($plugin->getServer()->getLevels() as $level) {
                    $entities = array_map(static function(Entity $entity) {
                    }, array_filter($level->getEntities(), static function(Entity $entity): bool {
                        return $entity instanceof SlapperHuman or $entity instanceof SlapperEntity;
                    }));

                    if (count($entities) === 0) {
                        $sender->sendMessage(TextFormat::RED . "Migrating failed: No Slapper NPC found!");
                        return true;
                    }

                    $error = 0;
                    foreach ($level->getEntities() as $entity) {
                        if ($entity instanceof SlapperEntity) {
                            /** @phpstan-ignore-next-line */
                            if ($this->spawnNPC(self::LEGACY_ID_MAP_BC[$entity::TYPE_ID], $sender, $entity->getNameTag(), $entity->namedtag->getCompoundTag("Commands"), $entity->getLocation())) {
                                if (!$entity->isFlaggedForDespawn()) {
                                    $entity->flagForDespawn();
                                }
                            } else {
                                ++$error;
                            }
                        } elseif ($entity instanceof SlapperHuman) {
                            $this->spawnNPC(SimpleNPC::ENTITY_HUMAN, $sender, $entity->getNameTag(), $entity->namedtag->getCompoundTag("Commands"), $entity->getLocation(), $entity->getSkin()->getSkinData());

                            if (!$entity->isFlaggedForDespawn()) {
                                $entity->flagForDespawn();
                            }
                        }
                    }

                    if ($error === 0) {
                        $sender->sendMessage(TextFormat::GREEN . "The migration was successful, you can safely remove the Slapper plugin now");
                    } else {
                        $sender->sendMessage(TextFormat::RED . "(" . $error . " error found) " . TextFormat::YELLOW . "It seems that the migration is not going well, please fix the error so that it can be fully migrated. Don't delete Slapper Plugin now");
                    }
                }

                return true;
            }

            if (isset($plugin->migrateNPC[$sender->getName()], $args[1]) && $args[1] === "cancel") {
                $sender->sendMessage(TextFormat::GREEN . "Migrating NPC cancelled!");
                unset($plugin->migrateNPC[$sender->getName()]);
                return true;
            }
        } else {
            $sender->sendMessage(TextFormat::RED . "Slapper plugin is missing, cannnot migrating.");
        }

        return false;
    }

    public function interactToNPC(Entity $entity, Player $player): void {
        $plugin = SimpleNPC::getInstance();

        if (isset($plugin->idPlayers[$player->getName()])) {
            $player->sendMessage(TextFormat::GREEN . "NPC ID: " . $entity->getId());
            unset($plugin->idPlayers[$player->getName()]);
            return;
        }

        if (isset($plugin->removeNPC[$player->getName()]) && !$entity->isFlaggedForDespawn()) {
            if ($this->removeNPC($entity->namedtag->getString("Identifier"), $entity)) {
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
        if (($commands = $entity->namedtag->getCompoundTag("Commands")) !== null) {
            foreach ($commands as $stringTag) {
                $plugin->getServer()->getCommandMap()->dispatch(new ConsoleCommandSender(), str_replace("{player}", '"' . $player->getName() . '"', $stringTag->getValue()));
            }
        }
    }

    /**
     * @return null|BaseNPC[]|CustomHuman[]
     */
    public function getAllNPCs(): ?array {
        $npcs = null;
        foreach (SimpleNPC::getInstance()->getServer()->getLevels() as $world) {
            $npcs = array_map(static function(Entity $entity): Entity {
                return $entity;
            }, array_filter($world->getEntities(), static function(Entity $entity): bool {
                return $entity instanceof BaseNPC or $entity instanceof CustomHuman;
            }));
        }

        return $npcs;
    }
}