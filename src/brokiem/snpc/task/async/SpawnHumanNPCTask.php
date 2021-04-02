<?php
declare(strict_types=1);

namespace brokiem\snpc\task\async;

use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\entity\WalkingHuman;
use brokiem\snpc\event\SNPCCreationEvent;
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

class SpawnHumanNPCTask extends AsyncTask
{
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

    public function __construct(?string $nametag, string $username, string $dataPath, bool $canWalk = false, ?string $skinUrl = null, CompoundTag $command = null, Skin $skin = null, Location $customPos = null)
    {
        $this->username = $username;
        $this->nametag = $nametag;
        $this->canWalk = $canWalk;
        $this->skinUrl = $skinUrl;
        $this->dataPath = $dataPath;

        $this->storeLocal([$command, $skin, $customPos]);
    }

    public function onRun(): void
    {
        if ($this->skinUrl !== null) {
            $uniqId = uniqid($this->nametag, true);
            $parse = parse_url($this->skinUrl, PHP_URL_PATH);

            if ($parse === null or $parse === false) {
                $this->setResult(null);
                return;
            }

            $extension = pathinfo($parse, PATHINFO_EXTENSION);
            $data = Internet::getURL($this->skinUrl);

            if (($data === false) or $extension !== "png") {
                $this->setResult(null);
                return;
            }

            file_put_contents($this->dataPath . $uniqId . ".$extension", $data);
            $file = $this->dataPath . $uniqId . ".$extension";

            $img = @imagecreatefrompng($file);

            if (!$img) {
                $this->setResult(null);
                unlink($file);
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

            unlink($file);
        } else {
            $this->setResult(null);
        }
    }

    public function onCompletion(Server $server): void
    {
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
        $nbt->setTag(new CompoundTag("Skin", [
                "Name" => new StringTag("Name", $player->getSkin()->getSkinId()),
                "Data" => new ByteArrayTag("Data", in_array(strlen($skin ?? "somerandomstring"), Skin::ACCEPTED_SKIN_SIZES, true) ? $skin : $player->getSkin()->getSkinData())
            ])
        );
        $nbt->setShort("Walk", $this->canWalk ? 1 : 0);

        $entity = $this->canWalk ? new WalkingHuman($player->getLevel(), $nbt) : new CustomHuman($player->getLevel(), $nbt);

        if ($this->nametag !== null) {
            $entity->setNameTag(str_replace("{line}", PHP_EOL, $this->nametag));
            $entity->setNameTagAlwaysVisible();
        }

        $entity->spawnToAll();
        (new SNPCCreationEvent($entity))->call();

        $player->sendMessage(TextFormat::GREEN . "NPC created successfully!");
    }
}