<?php
declare(strict_types=1);

namespace App\Service;

/**
 * TODO:
 * - HashKey for POST request hardcoded? private? what is it? seems required
 * - preview how many pages are coming for more accurate fetch + feedback to user
 */

use App\Exception\InvalidTAContentException;
use DOMDocument;
use DOMXPath;

use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt_array;

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
    
    public function __construct(private readonly bool $debug = true)
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
        //loop until no more pages, indicates by result being:
        // <div class="warningspanel">Sorry there are no matching titles</div>
        //we *could* just load the html into DOMDocument and check for 0 games,
        //but that would be loads slower.
        $page = 1;
        $scrapedPages = [];
        do {
            $url = $this->buildUrl($gamerId, $page);
            $curl = $this->initCurl($url, $gamerId);

            $this->log(sprintf('Importing TA game collection page %d..', $page));
            $result = curl_exec($curl);
            $scrapedPages[] = $result;

            //cache scraped pages for when debugging
            file_put_contents(sprintf('scrape%d.html', $page), $result);
            $page++;

            //stop whe we hit the 'no matching titles' message, or pagecount is insane
        } while (str_contains($result, 'warningspanel') === false && $page < 100);
        curl_close($curl);

        return $scrapedPages;
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
    private function initCurl(string $url, string $gamerId)
    {
        //TA page uses the parameters in query string as well as post body,
        //but just the query string seems to work fine for us
        $curl = curl_init($url);
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