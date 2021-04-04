<?php

declare(strict_types=1);

namespace brokiem\snpc\task\async;

use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\SimpleNPC;
use pocketmine\entity\Skin;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\Internet;
use pocketmine\utils\TextFormat;

class URLToCapeTask extends AsyncTask
{

    /** @var string */
    private $url;
    /** @var string */
    private $dataPath;
    /** @var string */
    private $player;

    public function __construct(string $url, string $dataPath, CustomHuman $npc, string $player)
    {
        $this->dataPath = $dataPath;
        $this->url = $url;
        $this->player = $player;

        $this->storeLocal([$npc]);
    }

    public function onRun(): void
    {
        $uniqId = uniqid('', true);
        $parse = parse_url($this->url, PHP_URL_PATH);

        if ($parse === null || $parse === false) {
            $this->setResult(null);
            return;
        }

        $extension = pathinfo($parse, PATHINFO_EXTENSION);
        $data = Internet::getURL($this->url);

        if (($data === false) || $extension !== "png") {
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

        $img = imagecreatefrompng($file);
        $rgba = "";
        for ($y = 0; $y < imagesy($img); $y++) {
            for ($x = 0; $x < imagesx($img); $x++) {
                $argb = imagecolorat($img, $x, $y);
                $rgba .= chr(($argb >> 16) & 0xff) .
                    chr(($argb >> 8) & 0xff) . chr($argb & 0xff) .
                    chr(((~($argb >> 24)) << 1) & 0xff);
            }
        }
        $this->setResult($rgba);
        imagedestroy($img);

        if (is_file($file)) {
            unlink($file);
        }
    }

    public function onCompletion(Server $server): void
    {
        /** @var CustomHuman $npc */
        [$npc] = $this->fetchLocal();
        $player = $server->getPlayerExact($this->player);

        if (strlen($this->getResult()) !== 8192) {
            $player->sendMessage(TextFormat::RED . "Set Cape failed! Invalid cape detected [bytes=" . strlen($this->getResult()) . "] [supported=8192]");
            return;
        }

        $npcConfig = new Config(SimpleNPC::getInstance()->getDataFolder() . "npcs/" . $npc->namedtag->getString("Identifier") . ".json", Config::JSON);
        $capeSkin = new Skin(
            $npc->getSkin()->getSkinId(), $npc->getSkin()->getSkinData(),
            $this->getResult(), $npc->getSkin()->getGeometryName(),
            $npc->getSkin()->getGeometryData()
        );
        $npc->setSkin($capeSkin);
        $npc->sendSkin();

        $npcConfig->set("capeData", base64_encode($this->getResult()));
        $npcConfig->save();
        $player->sendMessage(TextFormat::GREEN . "Successfull set cape to npc (ID: " . $npc->getId() . ")");
    }
}