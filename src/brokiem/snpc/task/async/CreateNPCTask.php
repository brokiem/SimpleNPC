<?php
declare(strict_types=1);

namespace brokiem\snpc\task\async;

use brokiem\snpc\SimpleNPC;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class CreateNPCTask extends AsyncTask
{
    /** @var string */
    private $skinUrl;
    /** @var ?string */
    private $nametag;
    /** @var bool */
    private $canWalk;

    public function __construct(?string $nametag, Player $player, bool $canWalk = false, string $skinUrl = null)
    {
        $this->nametag = $nametag;
        $this->canWalk = $canWalk;
        $this->skinUrl = $skinUrl;

        $this->storeLocal($player);
    }

    public function onRun(): void
    {
        if ($this->skinUrl === null) {
            $file = file_get_contents($this->skinUrl);
            $extension = pathinfo($file)["extension"];

            if ($extension === "png") {
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
                $this->setResult(":" . $bytes);
            } elseif ($extension === "jpg" or $extension === "jpeg") {
                $img = imagecreatefromjpeg($file);
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
                $this->setResult(":" . $bytes);
            }
        }
    }

    public function onCompletion(Server $server): void
    {
        /** @var Player $player */
        $player = $this->fetchLocal();

        $nbt = Entity::createBaseNBT($player, null, $player->getYaw(), $player->getPitch());
        $nbt->setTag(new CompoundTag("commands", []));

        $entity = Entity::createEntity(SimpleNPC::ENTITY_HUMAN, $player->getLevel(), $nbt);

        if (!$entity instanceof Human) {
            return;
        }

        if (!$this->nametag) {
            $entity->setNameTag($this->nametag);
            $entity->setNameTagAlwaysVisible();
        }

        if (!$this->getResult()) {
            $entity->setSkin(new Skin($player->getSkin()->getSkinId(), $this->getResult()));
        } else {
            $entity->setSkin($player->getSkin());
        }

        $entity->spawnToAll();
    }
}