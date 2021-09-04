<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\task\async;

use brokiem\snpc\entity\CustomHuman;
use pocketmine\entity\Skin;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use pocketmine\utils\TextFormat;

class URLToCapeTask extends AsyncTask {

    public function __construct(private string $url, private string $dataPath, CustomHuman $npc, private string $player) {
        $this->storeLocal("snpc_urltocape", [$npc]);
    }

    public function onRun(): void {
        $uniqId = uniqid('cape', true);
        $parse = parse_url($this->url, PHP_URL_PATH);

        if ($parse === null || $parse === false) {
            return;
        }

        $extension = pathinfo($parse, PATHINFO_EXTENSION);
        $data = Internet::getURL($this->url);

        if ($data === null || $extension !== "png") {
            return;
        }

        file_put_contents($this->dataPath . $uniqId . ".$extension", $data->getBody());
        $file = $this->dataPath . $uniqId . ".$extension";

        $img = @imagecreatefrompng($file);

        if (!$img) {
            if (is_file($file)) {
                unlink($file);
            }
            return;
        }

        $rgba = "";
        for ($y = 0; $y < imagesy($img); $y++) {
            for ($x = 0; $x < imagesx($img); $x++) {
                $argb = imagecolorat($img, $x, $y);
                $rgba .= chr(($argb >> 16) & 0xff) . chr(($argb >> 8) & 0xff) . chr($argb & 0xff) . chr(((~($argb >> 24)) << 1) & 0xff);
            }
        }
        $this->setResult($rgba);
        imagedestroy($img);

        if (is_file($file)) {
            unlink($file);
        }
    }

    public function onCompletion(): void {
        /** @var CustomHuman $npc */
        [$npc] = $this->fetchLocal("snpc_urltocape");
        $player = Server::getInstance()->getPlayerExact($this->player);

        if ($player === null) {
            return;
        }

        if ($this->getResult() === null) {
            $player->sendMessage(TextFormat::RED . "Set Cape failed! Invalid link detected (the link doesn't contain images)");
            return;
        }

        if (strlen($this->getResult()) !== 8192) {
            $player->sendMessage(TextFormat::RED . "Set Cape failed! Invalid cape detected [bytes=" . strlen($this->getResult()) . "] [supported=8192]");
            return;
        }

        $capeSkin = new Skin($npc->getSkin()->getSkinId(), $npc->getSkin()->getSkinData(), (string)$this->getResult(), $npc->getSkin()->getGeometryName(), $npc->getSkin()->getGeometryData());
        $npc->setSkin($capeSkin);
        $npc->sendSkin();

        $npc->getSkinTag()->setByteArray("CapeData", $this->getResult());
        $npc->saveSkinTag();

        $player->sendMessage(TextFormat::GREEN . "Successfull set cape to npc (ID: " . $npc->getId() . ")");
    }
}