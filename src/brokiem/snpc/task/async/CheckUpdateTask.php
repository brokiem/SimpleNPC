<?php

declare(strict_types=1);

namespace brokiem\snpc\task\async;

use brokiem\snpc\SimpleNPC;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;

class CheckUpdateTask extends AsyncTask {
    private const UPDATES_URL = "https://raw.githubusercontent.com/brokiem/SimpleNPC/master/updates.json";
    /** @var string */
    private $version;

    public function __construct(string $version, SimpleNPC $plugin){
        $this->version = $version;
        $this->storeLocal([$plugin]);
    }

    public function onRun(): void{
        $data = Internet::getURL(self::UPDATES_URL);

        if($data !== false){
            $updates = json_decode($data, true);

            if($updates["latest-version"] != null or $updates["update-date"] != "" or $updates["update-url"] !== ""){
                $this->setResult([$updates["latest-version"], $updates["update-date"], $updates["update-url"]]);
                return;
            }
        }

        $this->setResult(null);
    }

    public function onCompletion(Server $server): void{
        if($this->getResult() === null){
            $server->getLogger()->debug("[SimpleNPC] Async update check failed");
            return;
        }

        [$latestVersion, $updateDate, $updateUrl] = $this->getResult();

        if($this->version !== $latestVersion){
            /** @var SimpleNPC $plugin */
            [$plugin] = $this->fetchLocal();

            $plugin->getLogger()->notice("SimpleNPC v$latestVersion has been released on $updateDate. Download the new update at $updateUrl");
            $plugin->cachedUpdate = [$latestVersion, $updateDate, $updateUrl];
        }
    }
}