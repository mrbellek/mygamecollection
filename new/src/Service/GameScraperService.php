<?php
declare(strict_types=1);

namespace App\Service;

/**
 * TODO:
 * - HashKey for POST request hardcoded? private? what is it? seems required
 */

use App\Exception\InvalidTAContentException;
use DOMDocument;
use DOMXPath;

use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt_array;

/**
 * This service scrapes the TA gamecollection pages for a given xbox gamertag,
 * and returns the pages as an array of strings.
 */
class GameScraperService
{
    private string $baseUrl = 'https://www.trueachievements.com/gamer/mrbellek/gamecollection?executeformfunction&function=AjaxList&params=';

    /**
     * @var array<string>
     */
    private array $parameters = [
        'ddlSortBy'                     => 'Titlename',
        'ddlDLCInclusionSetting'        => 'AllDLC',
        'ddlContestStatus'              => 'Any status',
        'ddlStartedStatus'              => '0',
        'oGameCollection_Order'         => 'Titlename',
        'oGameCollection_Page'          => '@CHANGEME',
        'oGameCollection_ItemsPerPage'  => '100', //endpoint doesn't accept more
        'txtGamerID'                    => '@CHANGEME',
        'GameView'                      => 'optListView',
        'MultiEditMode'                 => 'optSingleEdit',
        'chkColTitleimage'              => 'True',
        'chkColTitlename'               => 'True',
        'chkColPlatform'                => 'True',
        'chkColSiteScore'               => 'True',
        'chkColOfficialScore'           => 'True',
        'chkColItems'                   => 'True',
        'chkColCompletionpercentage'    => 'True',
        'chkColTimeplayed'              => 'True',
        'chkColDatecompleted'           => 'True',
        'chkColOwnershipstatus'         => 'True',
        'chkColMedia'                   => 'True',
        'chkColCompletionestimate'      => 'True',
        'chkColWalkthrough'             => 'True',
        'chkColSiteratio'               => 'True',
        'chkColSiterating'              => 'True',
    ];
    
    public function __construct(private readonly bool $debug = false)
    {
        //prevent DOMDocument from choking on invalid html
        libxml_use_internal_errors(true);
    }

    private function log(string $message): void
    {
        printf($message . PHP_EOL);
    }

    /**
     * @return array<string>
     */
    public function scrape(string $gamertag): array
    {
        //return cached pages when debugging
        if ($this->debug === true && is_readable('scrape1.html')) {
            $caches = glob('scrape*.html');
            $result = [];
            foreach ($caches as $cache) {
                $result[] = file_get_contents($cache);
            }
            return $result;
        }

        //fetch TA gamer id from TA
        $gamerId = $this->lookupGamerId($gamertag);

        //construct game collection url and fetch first page
        $scrapedPages = [];
        $url = $this->buildUrl($gamerId, 1);
        $curl = $this->initCurl($gamerId);
        curl_setopt($curl, CURLOPT_URL, $url);
        $this->log('Importing TA game collection page 1/?..');

        //get number of games from first page 'total' line and calculate page count
        $result = curl_exec($curl);
        $scrapedPages[] = $result;
        $totalPages = $this->getPageCountFromFirstPage($result);

        for ($page = 2; $page <= $totalPages; $page++) {
            $url = $this->buildUrl($gamerId, $page);
            curl_setopt($curl, CURLOPT_URL, $url);

            $this->log(sprintf('Importing TA game collection page %d/%d..', $page, $totalPages));
            $result = curl_exec($curl);
            $scrapedPages[] = $result;

            //cache scraped pages for when debugging
            file_put_contents(sprintf('scrape%d.html', $page), $result);
        }
        curl_close($curl);

        return $scrapedPages;
    }

    private function getPageCountFromFirstPage(string $html): int
    {
        $numberOfGames = 0;

        $dom = new DOMDocument();
        $dom->loadHtml($html);
        $xpath = new DOMXPath($dom);
        $totalRow = $xpath->query('//tr[@class="total"]/td[@class="left"]');
        $matches = [];
        if (preg_match('/([0-9,]+) Titles?/', $totalRow[0]->textContent, $matches) === 1) {
            $numberOfGames = intval(str_replace(',', '', $matches[1]));
        } else {
            throw new InvalidTAContentException('Failed to determine the number of pages in your game collection.');
        }

        return intval(ceil($numberOfGames / 100));
    }

    /**
     * Lookup TA gamerId based on gamertag, there's a link tag in the header that has it
     * 
     * @throws InvalidTAContentException
     */
    private function lookupGamerId(string $gamertag): string
    {
        $this->log(sprintf('Performing TA gamer id lookup by gamertag %s..', $gamertag));

        if ($this->debug === true) {
            return '71675';
        }

        $gamerHomepage = file_get_contents(sprintf('https://www.trueachievements.com/gamer/%s', $gamertag));
        $dom = new DOMDocument();
        $dom->loadHtml($gamerHomepage);

        $xpath = new DOMXPath($dom);
        $rsslink = $xpath->query('//link[@type="application/rss+xml"][2]');
        $linktarget = $rsslink[0]->getAttribute('href');
        $matches = [];
        if (preg_match('/gamerid=(.+)/', $linktarget, $matches)) {
            $gamerId = $matches[1];
        } else {
            throw new InvalidTAContentException(sprintf('Unable to find gamerid for gamertag %s.', $gamertag));
        }

        return $gamerId;
    }

    /**
     * Build the url for the game collection we're going to use, it has
     * some custom escaping
     */
    private function buildUrl(string $gamerId, int $page = 1): string
    {
        $escapeVars = [
            '=' => '%3D',
            '&' => '%26',
            '+' => '%20',
        ];
        $this->parameters['txtGamerID'] = $gamerId;
        $this->parameters['oGameCollection_Page'] = $page;

        return $this->baseUrl
            . urlencode('oGameCollection|&')
            . str_replace(
                array_keys($escapeVars),
                array_values($escapeVars),
                http_build_query($this->parameters)
            );
    }

    /**
     * @return \CurlHandle
     */
    private function initCurl(string $gamerId)
    {
        //TA page uses the parameters in query string as well as post body,
        //but just the query string seems to work fine for us
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [sprintf('Cookie: GamerID=%s; HashKey=a00bc2497d9d467f94903b6a16a3489a;', $gamerId)],
        ]);

        return $curl;
    }
}