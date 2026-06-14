<?php
declare(strict_types=1);

namespace App\Service;

/**
 * TODO:
 * - HashKey for POST request hardcoded? private? what is it? seems required
 */

use App\Exception\InvalidTAContentException;
use App\Trait\DebuggerTrait;
use CurlHandle;
use DOMDocument;
use DOMXPath;

use function curl_exec;
use function curl_init;
use function curl_setopt_array;

/**
 * This service scrapes the TA gamecollection pages for a given xbox gamertag,
 * and returns the pages as an array of strings.
 */
class GameScraperService
{
    use DebuggerTrait;
    private string $baseUrl = 'https://www.trueachievements.com/gamer/mrbellek/gamecollection?executeformfunction&function=AjaxList&params=';

    /**
     * @var array<string, string|int>
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

    private CurlHandle $curl;
    private bool $debug = false;
    
    public function __construct()
    {
        //prevent DOMDocument from choking on invalid html
        libxml_use_internal_errors(true);
        $this->curl = $this->initCurl();
    }

    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;

        return $this;
    }

    private function log(string $message): void
    {
        print($message . PHP_EOL);
    }

    /**
     * @return array<string>
     */
    public function scrape(string $gamertag): array
    {
        //return cached pages when debugging
        if ($this->debug === true && is_readable('scrape001.html')) {
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
        $url = $this->buildUrl($gamerId);
        $this->log('Importing TA game collection page 1/?..');

        //get number of games from first page 'total' line and calculate page count
        $result = $this->urlPost($url, $gamerId);
        $scrapedPages[] = $result;
        $totalPages = $this->getPageCountFromFirstPage($result);
        file_put_contents('scrape001.html', $result);

        for ($page = 2; $page <= $totalPages; $page++) {
            $url = $this->buildUrl($gamerId, $page);

            $this->log(sprintf('Importing TA game collection page %d/%d..', $page, $totalPages));
            $result = $this->urlPost($url, $gamerId);
            $scrapedPages[] = $result;

            //cache scraped pages for when debugging
            file_put_contents(sprintf('scrape%03d.html', $page), $result);
        }

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

        $gamerHomepage = $this->urlGet(sprintf('https://www.trueachievements.com/gamer/%s', $gamertag));
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

    private function urlGet(string $url, ?string $gamerId = null): string
    {
        curl_setopt($this->curl, CURLOPT_POST, false);

        return $this->urlFetch($url, $gamerId);
    }

    private function urlPost(string $url, ?string $gamerId = null): string
    {
        curl_setopt($this->curl, CURLOPT_POST, true);

        return $this->urlFetch($url, $gamerId);
    }

    private function urlFetch(string $url, ?string $gamerId = null): string
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        if ($gamerId !== null) {
            curl_setopt(
                $this->curl,
                CURLOPT_HTTPHEADER,
                [
                    sprintf('Cookie: GamerID=%s; HashKey=a00bc2497d9d467f94903b6a16a3489a;', $gamerId),
                    //'Content-Type: application/json; charset=UTF-8',
                    //'Content-Length: ' . strlen(json_encode($json)),
                ],
            );
        }

        $result = curl_exec($this->curl);

        if ($this->debug) {
            $info = curl_getinfo($this->curl);
            $this->log(sprintf(
                '%s %s: HTTP %d, %d bytes',
                $info['effective_method'],
                $url,
                $info['http_code'],
                $info['size_download'],
            ));
            //var_dump($result);
            //if ($info['http_code'] !== 200) die();
        }

        return $result;
    }

    private function initCurl(): CurlHandle
    {
        //TA page uses the parameters in query string as well as post body,
        //but just the query string seems to work fine for us
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36 OPR/51.0.2830.55',

        ]);

        return $curl;
    }
}