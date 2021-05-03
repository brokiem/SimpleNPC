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
use brokiem\snpc\event\SNPCCreationEvent;
use brokiem\snpc\SimpleNPC;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use slapper\entities\SlapperEntity;
use slapper\entities\SlapperHuman;

class NPCManager {

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

    public static function getNPCs(): array {
        return self::$npcs;
    }

    public static function registerAllNPC(): void {
        foreach (self::$npcs as $class => $saveNames) {
            SimpleNPC::registerEntity($class, array_shift($saveNames), true, $saveNames);
        }
    }

    public static function createNPC(string $type, Player $player, ?string $nametag = null, ?CompoundTag $commands = null, ?Location $customPos = null, ?string $skinData = null, bool $canWalk = false): bool {
        $nbt = Entity::createBaseNBT($player, null, $player->getYaw(), $player->getPitch());

        if ($customPos !== null) {
            Entity::createBaseNBT($customPos, null, $customPos->getYaw(), $customPos->getPitch());
        }

        if ($skinData !== null and $type === SimpleNPC::ENTITY_HUMAN) {
            $nbt->setTag(new CompoundTag("Skin", [
                "Name" => new StringTag("Name", $player->getSkin()->getSkinId()),
                "Data" => new ByteArrayTag("Data", $skinData),
                "CapeData" => new ByteArrayTag("CapeData", $player->getSkin()->getCapeData()),
                "GeometryName" => new StringTag("GeometryName", $player->getSkin()->getGeometryName()),
                "GeometryData" => new ByteArrayTag("GeometryData", $player->getSkin()->getGeometryData())
            ]));
        }

        $nbt->setTag($commands ?? new CompoundTag("Commands", []));
        $nbt->setShort("Walk", (int)$canWalk);

        $pos = $customPos ?? $player;
        $nbt->setString("Identifier", self::saveNPC($type, [
            "type" => $type,
            "nametag" => $nametag,
            "world" => $player->getLevelNonNull()->getFolderName(),
            "showNametag" => $nametag !== null,
            "skinId" => $player->getSkin()->getSkinId(),
            "skinData" => $skinData,
            "capeData" => $player->getSkin()->getCapeData(),
            "geometryName" => $player->getSkin()->getGeometryName(),
            "geometryData" => $player->getSkin()->getGeometryData(),
            "walk" => $canWalk,
            "commands" => $commands === null ? [] : $commands->getValue(),
            "position" => [$pos->getX(), $pos->getY(), $pos->getZ(), $pos->getYaw(), $pos->getPitch()]
        ]));

        $entity = self::createEntity($type, $player->getLevelNonNull(), $nbt);

        if ($entity === null) {
            $player->sendMessage("Entity is null or entity $type is invalid");
            return false;
        }

        if ($nametag !== null) {
            $entity->setNameTag($nametag);
            $entity->setNameTagAlwaysVisible();
        }

        $entity->spawnToAll();
        $player->sendMessage(TextFormat::GREEN . "NPC " . ucfirst($type) . " created successfully! ID: " . $entity->getId());

        (new SNPCCreationEvent($entity))->call();

        self::saveChunkNPC($entity);
        return true;
    }

    public static function saveChunkNPC(Entity $entity): void {
        $chunk = $entity->chunk;
        if ($chunk !== null) {
            if (($chunk->hasChanged() or count($chunk->getTiles()) > 0 or count($chunk->getSavableEntities()) > 0) and $chunk->isGenerated()) {
                $entity->getLevelNonNull()->getProvider()->saveChunk($chunk);
                $chunk->setChanged(false);
            }
        }
    }

    public static function saveNPC(string $type, array $saves): string {
        if (!is_dir(SimpleNPC::getInstance()->getDataFolder() . "npcs")) {
            mkdir(SimpleNPC::getInstance()->getDataFolder() . "npcs");
        }

        $identifier = uniqid("$type-", true);
        $path = SimpleNPC::getInstance()->getDataFolder() . "npcs/" . "$identifier.json";

        $npcConfig = new Config($path, Config::JSON);
        $npcConfig->set("version", SimpleNPC::getInstance()->getDescription()->getVersion());
        $npcConfig->set("identifier", $identifier);
        foreach ($saves as $save => $value) {
            $npcConfig->set($save, $value);
        }

        $npcConfig->save();
        return $identifier;
    }

    public static function createEntity(string $type, Level $world, CompoundTag $nbt): ?Entity {
        if (isset(SimpleNPC::$entities[$type])) {
            /** @var Entity $class */
            $class = SimpleNPC::$entities[$type];

            return new $class($world, $nbt);
        }

        return null;
    }

    public static function removeNPC(string $identifier, Entity $entity): bool {
        if ($entity instanceof BaseNPC || $entity instanceof CustomHuman) {
            if (!$entity->isFlaggedForDespawn()) {
                $entity->flagForDespawn();
            }

            if (is_file($path = SimpleNPC::getInstance()->getDataFolder() . "npcs/$identifier.json")) {
                unlink($path);
                SimpleNPC::getInstance()->getLogger()->debug("Removed NPC File: $path");
                return true;
            }
        }

        return false;
    }

    public static function migrateNPC(Player $sender): bool {
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
                    $entity = array_map(static function(Entity $entity) {
                    }, array_filter($level->getEntities(), static function(Entity $entity): bool {
                        return $entity instanceof SlapperHuman or $entity instanceof SlapperEntity;
                    }));

                    if (count($entity) === 0) {
                        $sender->sendMessage(TextFormat::RED . "Migrating failed: No Slapper-NPC found!");
                        return true;
                    }

                    $error = 0;
                    foreach ($level->getEntities() as $entity) {
                        if ($entity instanceof SlapperEntity) {
                            /** @phpstan-ignore-next-line */
                            if (self::createNPC(AddActorPacket::LEGACY_ID_MAP_BC[$entity::TYPE_ID], $sender, $entity->getNameTag(), $entity->namedtag->getCompoundTag("Commands"))) {
                                if (!$entity->isFlaggedForDespawn()) {
                                    $entity->flagForDespawn();
                                }
                            } else {
                                ++$error;
                            }
                        } elseif ($entity instanceof SlapperHuman) {
                            self::createNPC(SimpleNPC::ENTITY_HUMAN, $sender, $entity->getNameTag(), $entity->namedtag->getCompoundTag("Commands"), $entity->getLocation(), $entity->getSkin()->getSkinData());

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

    public static function interactToNPC(Entity $entity, Player $player): void {
        $plugin = SimpleNPC::getInstance();

        if (isset($plugin->idPlayers[$player->getName()])) {
            $player->sendMessage(TextFormat::GREEN . "NPC ID: " . $entity->getId());
            unset($plugin->idPlayers[$player->getName()]);
            return;
        }

        if (isset($plugin->removeNPC[$player->getName()]) && !$entity->isFlaggedForDespawn()) {
            if (self::removeNPC($entity->namedtag->getString("Identifier"), $entity)) {
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
    public static function getAllNPCs(): ?array {
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