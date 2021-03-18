<?php

declare(strict_types=1);

namespace brokiem\snpc\task\async;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;

class CheckUpdateTask extends AsyncTask
{
    private const UPDATES_URL = "https://raw.githubusercontent.com/brokiem/SimpleNPC/master/updates.json";
    /** @var string */
    private $version;

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public function onRun(): void
    {
        $data = Internet::getURL(self::UPDATES_URL);

        if ($data !== false) {
            $updates = json_decode($data, true);

            $this->setResult([$updates["latest-version"], $updates["update-date"], $updates["update-url"]]);
        }
    }

    public function onCompletion(Server $server): void
    {
        [$latestVersion, $updateDate, $updateUrl] = $this->getResult();

        if ($this->version !== $latestVersion) {
            $server->getLogger()->notice(
                "SimpleNPC v$latestVersion has been released on $updateDate. Download the new update at $updateUrl"
            );
        }
    }
}