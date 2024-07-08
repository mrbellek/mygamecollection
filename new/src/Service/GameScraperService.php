<?php
declare(strict_types=1);

namespace App\Service;

use DOMDocument;
use DOMXPath;
use RuntimeException;

use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt_array;

class GameScraperService
{
    private string $baseUrl = 'https://www.trueachievements.com/gamer/mrbellek/gamecollection?executeformfunction&function=AjaxList&params=';
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
    
    public function __construct()
    {
        //prevent DOMDocument from choking on invalid html
        libxml_use_internal_errors(true);
    }

    public function scrape(string $gamertag): array
    {
        //@TODO cache temp, remove
        if (is_readable('scrape.html')) {
            return [file_get_contents('scrape.html')];
        }

        //fetch TA gamer id from TA
        $gamerId = $this->lookupGamerId($gamertag);

        //construct game collection url and fetch first page
        //@TODO loop until no more pages
        $url = $this->buildUrl($gamerId);
        $curl = $this->initCurl($url, $gamerId);
        $result = curl_exec($curl);
        curl_close($curl);

        //@TODO cache temp, remove
        file_put_contents('scrape.html', $result);
        dd(realpath('scrape.html'));

        return [$result];
    }
    
    /**
     * Lookup TA gamerId based on gamertag, there's a link tag in the header that has it
     * 
     * @throws RuntimeException
     */
    private function lookupGamerId(string $gamertag): string
    {
        //@TODO cache, remove
        return '71675';

        $gamerHomepage = file_get_contents(sprintf('https://www.trueachievements.com/gamer/%s', $gamertag));
        $dom = new DOMDocument();
        $dom->loadHtml($gamerHomepage);

        $xpath = new DOMXPath($dom);
        $rsslink = $xpath->query('//link[@type="application/rss+xml"][2]');
        $linktarget = $rsslink[0]->getAttribute('href');
        if (preg_match('/gamerid=(.+)/', $linktarget, $matches)) {
            $gamerId = $matches[1];
        } else {
            throw new RuntimeException(sprintf('Unable to find gamerid for gamertag %s.', $this->gamertag));
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

    private function initCurl(string $url, string $gamerId)
    {
        //@TODO post data is missing, should be in querystring as well as post
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