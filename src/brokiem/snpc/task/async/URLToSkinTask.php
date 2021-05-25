<?php

declare(strict_types=1);

namespace brokiem\snpc\task\async;

use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\manager\NPCManager;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;

class URLToSkinTask extends AsyncTask {

    /** @var string */
    private $username;
    /** @var string */
    private $dataPath;
    /** @var string */
    private $skinUrl;

    public function __construct(string $username, string $dataPath, string $skinURL, CustomHuman $human) {
        $this->username = $username;
        $this->dataPath = $dataPath;
        $this->skinUrl = $skinURL;

        $this->storeLocal($human);
    }

    public function onRun(): void {
        if ($this->skinUrl !== null) {
            $uniqId = uniqid("skin-change", true);
            $parse = parse_url($this->skinUrl, PHP_URL_PATH);

            if ($parse === null || $parse === false) {
                return;
            }

            $extension = pathinfo($parse, PATHINFO_EXTENSION);
            $data = Internet::getURL($this->skinUrl);

            if ($data === false || $extension !== "png") {
                return;
            }

            file_put_contents($this->dataPath . $uniqId . ".$extension", $data);
            $file = $this->dataPath . $uniqId . ".$extension";

            $img = @imagecreatefrompng($file);

            if (!$img) {
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
        }
    }

    public function onCompletion(Server $server): void {
        $player = $server->getPlayerExact($this->username);
        /** @var CustomHuman $human */
        $human = $this->fetchLocal();

        if ($player === null) {
            return;
        }

        $player->saveNBT();

        /** @var string $skinData */
        $skinData = $this->getResult();

        $human->setSkin(new Skin($human->getSkin()->getSkinId(), $skinData, $human->getSkin()->getCapeData(), $human->getSkin()->getGeometryName(), $human->getSkin()->getGeometryData()));
        $human->sendSkin();

        $skinTag = NPCManager::getInstance()->getSkinTag($human);
        if ($skinTag instanceof CompoundTag) {
            $skinTag->setByteArray("Data", $skinData, true);

            NPCManager::getInstance()->saveSkinTag($human, $skinTag);
        }

        NPCManager::getInstance()->saveChunkNPC($human);
    }
}