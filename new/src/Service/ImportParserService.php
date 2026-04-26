<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Trait\DebuggerTrait;
use DateTime;

/**
 * Note: the following export fields are not available on the TA Game Collection webpage:
 * - achievements won/total in base game
 * - gamerscore won/total in base game
 * - trueachievement score won/total in base game
 * - challenges won/total
 */
class ImportParserService
{
    use DebuggerTrait;

    private const int NAME = 0;
    private const int PLATFORM = 1;
    private const int TA_URL = 2;
    private const int ACH_WON_BASE = 3;
    private const int ACH_MAX_BASE = 4;
//    private const int ACH_WON_W_DLC = 5;
    private const int ACH_MAX_W_DLC = 6;
    private const int GS_WON_BASE = 7;
    private const int GS_MAX_BASE = 8;
//    private const int GS_WON_W_DLC = 9;
//    private const int GS_MAX_W_DLC = 10;
    private const int TA_WON_BASE = 11;
    private const int TA_MAX_BASE = 12;
    private const int TA_WON_W_DLC = 13;
    private const int TA_MAX_W_DLC = 14;
    private const int COMP_PERC = 15;
    private const int COMP_DATE = 16;
//    private const int CHALLENGES_WON = 17;
//    private const int CHALLENGES_MAX = 18;
    private const int HOURS_PLAYED = 19;
//    private const int MY_RATING = 20;
    private const int SITE_RATING = 21;
//    private const int MY_RATIO = 22;
//    private const int SITE_RATIO = 23;
//    private const int OWNERSHIP_STATUS = 24;
    private const int PLAY_STATUS = 25;
    private const int FORMAT = 26; //'media' in TA game collection
    private const int COMP_EST = 27;
//    private const int COMP_EST_W_DLC = 28;
    private const int WALKTHROUGH_URL = 29;
//    private const int NOTES = 30;
//    private const int ROLE = 31; //'not for contests'

    /**
     * @return Game[]
     */
    public function parseCsvContents(string $csvContent): array
    {
        $lines = explode("\n", $csvContent);
        array_shift($lines);
        $games = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            //@var array<int, string> $fields
            $fields = explode('","', trim($line, '"'));

            $hasDlc = $this->getHasDlc($fields);
            $dlcCompletion = $this->getDlcCompletionPerc($fields);
            $completionDate = $this->getCompletionDate($fields);

            $games[] = new Game(
                $fields[self::NAME],
                $fields[self::PLATFORM],
                null,
                null,
                null,
                null,
                (int)$fields[self::COMP_PERC],
                $fields[self::COMP_EST],
                (float)$fields[self::HOURS_PLAYED],
                (int)$fields[self::ACH_WON_BASE],
                (int)$fields[self::ACH_MAX_BASE],
                (int)$fields[self::GS_WON_BASE],
                (int)$fields[self::GS_MAX_BASE],
                (int)$fields[self::TA_WON_BASE],
                (int)$fields[self::TA_MAX_BASE],
                $hasDlc,
                $dlcCompletion,
                (float)$fields[self::SITE_RATING],
                $fields[self::FORMAT],
                $fields[self::PLAY_STATUS],
                0,
                0,
                $fields[self::WALKTHROUGH_URL],
                $fields[self::TA_URL],
                new DateTime(),
                new DateTime(),
                $completionDate,
                null,
                null,
                null,
                null,
            );
        }
        return $games;
    }

    /**
     * @param Game[] $importGames
     * @param Game[] $libraryGames
     * @return array<string, Game[]>
     */
    public function getUpdatedGames(array $importGames, array $libraryGames): array
    {
        $importNames = array_map(static fn (Game $game) => $game->getName(), $importGames);
        $libraryNames = array_map(static fn (Game $game) => $game->getName(), $libraryGames);

        $newGames = array_diff($importGames, $libraryGames);
        $deletedGames = array_diff($libraryGames, $importGames);
        $updatedGames = [];

        //Sometimes 360/XB1 games get rereleased on new platforms, and TA changes the name of the old game by
        //adding 'Xbox 360' or 'Xbox One' - make sure those are picked up as updated
        //
        //Other changes that might happen to game names, that we can't pick up automatically:
        //- capitalization (Final Fantasy XV -> FINAL FANTASY XV)
        //- spelling (Kingdom: Two Crowns -> Kingdom Two Crowns)
        foreach ($deletedGames as $i => $deletedGame) {
            $indexNew = $this->gameIsRenamedLastGenGame($deletedGame, $newGames);
            if ($indexNew > 0) {
                $updatedGames[] = $newGames[$indexNew];
                unset($newGames[$indexNew], $deletedGames[$i]);
            }
        }

        //Make list of games that were updated (see function for criteria)
        foreach ($importGames as $importGame) {
            foreach ($libraryGames as $game) {
                if ($importGame->getName() === $game->getName() && $this->hasGameChanged($game, $importGame)) {
                    $updatedGames[] = $importGame;
                }
            }
        }

        //TODO: this should be lists of games, not a list of game names
        return [
            'new' => $newGames,// array_map(static fn (Game $game) => $game->getName(), $newGames),
            'deleted' => $deletedGames, // array_map(static fn (Game $game) => $game->getName(), $deletedGames),
            'updated' => $updatedGames, // array_map(static fn (Game $game) => $game->getName(), $updatedGames),
        ];
    }

    /**
     * A game has changed if:
     * - more hours played (note that CSV export rounds hours_played to int)
     * - more achievements unlocked
     * - more dlc appeared
     * - game name changed (usually '(Xbox 360)' is suffixed)
     */
    private function hasGameChanged(Game $libraryGame, Game $importGame): bool
    {
        return
            $libraryGame->getHoursPlayed() < $importGame->getHoursPlayed() ||
            $libraryGame->getAchievementsWon() < $importGame->getAchievementsWon() ||
            $libraryGame->getAchievementsTotal() < $importGame->getAchievementsTotal();
    }

    /**
     * @param array<int, string> $fields
     */
    private function getHasDlc(array $fields): bool
    {
        return $fields[self::ACH_MAX_BASE] !== $fields[self::ACH_MAX_W_DLC];
    }

    /**
     * @param array<int, string> $fields
     */
    private function getDlcCompletionPerc(array $fields): int
    {
        $baseTa = (int)$fields[self::TA_MAX_BASE];
        $baseTaWon = (int)$fields[self::TA_WON_BASE];
        $allTa = (int)$fields[self::TA_MAX_W_DLC];
        $allTaWon = (int)$fields[self::TA_WON_W_DLC];

        $dlcTa = $allTa - $baseTa;
        if ($dlcTa === 0) {
            return 0;
        }
        $dlcTaWon = $allTaWon - $baseTaWon;

        return (int)floor(100 * $dlcTaWon / $dlcTa);
    }

    /**
     * @param array<int, string> $fields
     */
    private function getCompletionDate(array $fields): ?DateTime
    {
        return $fields[self::COMP_DATE] === '' ? null : DateTime::createFromFormat('d/m/Y H:i:s', $fields[self::COMP_DATE]);
    }

    /**
     * @param Game[] $newGames
     * @return int game index in newGames array
     */
    private function gameIsRenamedLastGenGame(Game $deletedGame, array $newGames): int
    {
        foreach ($newGames as $i => $newGame) {
            if (
                $newGame->getName() === $deletedGame->getName() . ' (Xbox 360)' ||
                $newGame->getName() === $deletedGame->getName() . ' (Xbox One)'
            ) {
                return $i;
            }
        }

        return 0;
    }
}