<?php /** @noinspection NotOptimalIfConditionsInspection */
declare(strict_types=1);

namespace brokiem\snpc\task\async;

use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\event\SNPCCreationEvent;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;
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

    public function __construct(?string $nametag, string $username, string $dataPath, bool $canWalk = false, ?string $skinUrl = null)
    {
        $this->username = $username;
        $this->nametag = $nametag;
        $this->canWalk = $canWalk;
        $this->skinUrl = $skinUrl;
        $this->dataPath = $dataPath;
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

            if ($data === false) {
                $this->setResult(null);
                return;
            }

            file_put_contents($this->dataPath . $uniqId . ".$extension", $data);
            $file = $this->dataPath . $uniqId . ".$extension";

            $img = imagecreatefrompng($file);
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

            @imagedestroy($img);
            $this->setResult($bytes);

            @unlink($file);
        } else {
            $this->setResult(null);
        }
    }

    public function onCompletion(Server $server): void
    {
        $player = $server->getPlayerExact($this->username);

        if ($player === null) {
            return;
        }

        $player->saveNBT();
        $nbt = Entity::createBaseNBT($player, null, $player->getYaw(), $player->getPitch());
        $nbt->setTag($player->namedtag->getTag("Skin"));
        $nbt->setTag(new CompoundTag("commands", []));
        $nbt->setShort("Walk", 0);

        if ($this->canWalk) {
            $nbt->setShort("Walk", 1);
        }

        $entity = new CustomHuman($player->getLevel(), $nbt);

        if ($this->nametag !== null) {
            $entity->setNameTag($this->nametag);
            $entity->setNameTagAlwaysVisible();
        }

        if ($this->getResult() !== null) {
            $entity->setSkin(new Skin($player->getSkin()->getSkinId(), $this->getResult()));
        } else {
            $entity->setSkin($player->getSkin());
        }

        $entity->spawnToAll();
        (new SNPCCreationEvent($entity))->call();

        $player->sendMessage(TextFormat::GREEN . "NPC created successfully!");
    }
}