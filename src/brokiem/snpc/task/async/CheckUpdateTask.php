<?php
declare(strict_types=1);

namespace brokiem\snpc\task\async;

use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\Internet;

class CheckUpdateTask extends AsyncTask
{
    private const UPDATES_URL = "https://github.com/brokiem/SimpleNPC/blob/master/updates.json";

    public function onRun(): void
    {
        $json = Internet::getURL(self::UPDATES_URL);

        if ($json !== false) {
            $updates = json_decode($json, true);

            var_dump($updates);
        }
    }
}