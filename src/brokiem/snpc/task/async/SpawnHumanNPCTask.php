<?php

declare(strict_types=1);

namespace brokiem\snpc\task\async;

use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\entity\WalkingHuman;
use brokiem\snpc\event\SNPCCreationEvent;
use brokiem\snpc\manager\NPCManager;
use brokiem\snpc\SimpleNPC;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\level\Location;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use pocketmine\utils\TextFormat;

class SpawnHumanNPCTask extends AsyncTask {
    /** @var string */
    private $skinUrl;
    /** @var null|string */
    private $nametag;
    /** @var bool */
    private $canWalk;
    /** @var string */
    private $username;
    /** @var string */
    private $dataPath;

    public function __construct(?string $nametag, string $username, string $dataPath, bool $canWalk = false, ?string $skinUrl = null, CompoundTag $command = null, Skin $skin = null, Location $customPos = null) {
        $this->username = $username;
        $this->nametag = $nametag;
        $this->canWalk = $canWalk;
        $this->skinUrl = $skinUrl;
        $this->dataPath = $dataPath;

        $this->storeLocal([$command, $skin, $customPos]);
    }

    public function onRun(): void {
        if ($this->skinUrl !== null) {
            $uniqId = uniqid($this->nametag, true);
            $parse = parse_url($this->skinUrl, PHP_URL_PATH);

            if ($parse === null || $parse === false) {
                $this->setResult(null);
                return;
            }

            $extension = pathinfo($parse, PATHINFO_EXTENSION);
            $data = Internet::getURL($this->skinUrl);

            if ($data === false || $extension !== "png") {
                $this->setResult(null);
                return;
            }

            file_put_contents($this->dataPath . $uniqId . ".$extension", $data);
            $file = $this->dataPath . $uniqId . ".$extension";

            $img = @imagecreatefrompng($file);

            if (!$img) {
                $this->setResult(null);
                if (is_file($file)) {
                    unlink($file);
                }
                return;
            }

            $bytes = '';
            for ($y = 0; $y < imagesy($img); $y++) {
                for ($x = 0; $x < imagesx($img); $x++) {
                    $rgba = @imagecolorat($img, $x, $y);
                    $a = ((~($rgba >> 24)) << 1) & 0xff;
                    $r = ($rgba >> 16) & 0xff;
                    $g = ($rgba >> 8) & 0xff;
                    $b = $rgba & 0xff;
                    $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
                }
            }

            imagedestroy($img);
            $this->setResult($bytes);

            if (is_file($file)) {
                unlink($file);
            }

            return;
        }

        $this->setResult(null);
    }

    public function onCompletion(Server $server): void {
        $player = $server->getPlayerExact($this->username);
        [$commands, $skin, $customPos] = $this->fetchLocal();

        if ($player === null) {
            return;
        }

        $player->saveNBT();

        $skin = $skin instanceof Skin ? $skin->getSkinData() : $this->getResult();
        $nbt = Entity::createBaseNBT($player, null, $player->getYaw(), $player->getPitch());

        if ($customPos instanceof Location) {
            $nbt = Entity::createBaseNBT($customPos, null, $customPos->getYaw(), $customPos->getPitch());
        }

        $nbt->setTag($commands ?? new CompoundTag("Commands", []));
        $nbt->setTag(new CompoundTag("Skin", ["Name" => new StringTag("Name", $player->getSkin()->getSkinId()), "Data" => new ByteArrayTag("Data", in_array(strlen($skin ?? ""), Skin::ACCEPTED_SKIN_SIZES, true) ? $skin : $player->getSkin()->getSkinData()), "CapeData" => new ByteArrayTag("CapeData", $player->getSkin()->getCapeData()), "GeometryName" => new StringTag("GeometryName", $player->getSkin()->getGeometryName()), "GeometryData" => new ByteArrayTag("GeometryData", $player->getSkin()->getGeometryData())]));
        $nbt->setShort("Walk", $this->canWalk ? 1 : 0);
        $position = $customPos ?? $player;
        $saves = ["type" => $this->canWalk ? SimpleNPC::ENTITY_WALKING_HUMAN : SimpleNPC::ENTITY_HUMAN, "nametag" => $this->nametag, "world" => $position->getLevelNonNull()->getFolderName(), "showNametag" => $this->nametag !== null, "skinId" => $player->getSkin()->getSkinId(), "skinData" => in_array(strlen($skin ?? ""), Skin::ACCEPTED_SKIN_SIZES, true) ? base64_encode($skin) : base64_encode($player->getSkin()->getSkinData()), "capeData" => base64_encode($player->getSkin()->getCapeData()), "geometryName" => $player->getSkin()->getGeometryName(), "geometryData" => base64_encode($player->getSkin()->getGeometryData()), "walk" => $this->canWalk, "commands" => $commands ?? [], "position" => [$position->getX(), $position->getY(), $position->getZ(), $position->getYaw(), $position->getPitch()]];
        $nbt->setString("Identifier", NPCManager::saveNPC($this->canWalk ? SimpleNPC::ENTITY_WALKING_HUMAN : SimpleNPC::ENTITY_HUMAN, $saves));

        $entity = $this->canWalk ? new WalkingHuman($player->getLevelNonNull(), $nbt) : new CustomHuman($player->getLevelNonNull(), $nbt);

        if ($this->nametag !== null) {
            $entity->setNameTag(str_replace("{line}", "\n", $this->nametag));
            $entity->setNameTagAlwaysVisible();
        }

        $ev = new SNPCCreationEvent($entity);
        $ev->call();
        if (!$ev->isCancelled()) {
            $entity->spawnToAll();
            $player->sendMessage(TextFormat::GREEN . "NPC created successfully! ID: " . $entity->getId());
        }
    }
}