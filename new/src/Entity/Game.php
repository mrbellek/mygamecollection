<?php
declare(strict_types=1);

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use App\Enum\CompletionEstimate as CompletionEstimateEnum;
use App\Enum\Platform as PlatformEnum;
use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: "mygamecollection")]
class Game
{
    public function __construct(

        #[ORM\Id]
        #[ORM\Column]
        private int $id,

        #[ORM\Column]
        private string $name,
       
        #[ORM\Column]
        private string $platform,
       
        #[ORM\Column(name: "backcompat")]
        private ?bool $backwardsCompatible,
       
        #[ORM\Column(name: "kinect_required")]
        private ?bool $kinectRequired,
       
        #[ORM\Column(name: "peripheral_required")]
        private ?bool $peripheralRequired,
       
        #[ORM\Column]
        private ?bool $onlineMultiplayer,
       
        #[ORM\Column(name: "completion_perc")]
        private int $completionPercentage,
       
        #[ORM\Column(name: "completion_estimate")]
        private string $completionEstimate,
       
        #[ORM\Column(name: "hours_played")]
        private float $hoursPlayed,
       
        #[ORM\Column(name: "achievements_won")]
        private int $achievementsWon,
       
        #[ORM\Column(name: "achievements_total")]
        private int $achievementsTotal,
       
        #[ORM\Column(name: "gamerscore_won")]
        private int $gamerscoreWon,
       
        #[ORM\Column(name: "gamerscore_total")]
        private int $gamerscoreTotal,
       
        #[ORM\Column(name: "ta_score")]
        private int $taScore,
       
        #[ORM\Column(name: "ta_total")]
        private int $taTotal,
       
        #[ORM\Column(name: "dlc")]
        private bool $hasDlc,
       
        #[ORM\Column(name: "dlc_completion")]
        private int $dlcCompletionPercentage,
       
        #[ORM\Column(name: "site_rating")]
        private float $siteRating,
       
        #[ORM\Column]
        private string $format,
       
        #[ORM\Column]
        private string $status,

        #[ORM\Column(name: "shortlist_order")]
        private int $shortlistOrder,

        #[ORM\Column]
        private int $ranking,
       
        #[ORM\Column(name: "walkthrough_url")]
        private ?string $walkthroughUrl,
       
        #[ORM\Column(name: "game_url")]
        private string $gameUrl,
       
        #[ORM\Column(name: "last_modified", type: "datetime")]
        private DateTime $lastModified,
       
        #[ORM\Column(name: "date_created", type: "datetime")]
        private DateTime $created,
   
        #[ORM\Column(name: "completion_date")]
        private ?DateTime $completionDate = null,
       
        #[ORM\Column(name: "purchased_price")]
        private ?float $purchasedPrice = null,
       
        #[ORM\Column(name: "current_price")]
        private ?float $currentPrice = null,
       
        #[ORM\Column(name: "regular_price")]
        private ?float $regularPrice = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getPlatformClass(): string
    {
        return match($this->platform) {
            PlatformEnum::PLATFORM_360 => 'x36',
            PlatformEnum::PLATFORM_XB1 => 'xb1',
            PlatformEnum::PLATFORM_XSX => 'xsx',
            PlatformEnum::PLATFORM_WIN => 'win',
            PlatformEnum::PLATFORM_ANDROID,
            PlatformEnum::PLATFORM_WEB => 'mob',
            PlatformEnum::PLATFORM_SWITCH => 'switch',

            default => 'xsx',
        };
    }

    public function getPlatformImg(): string
    {
        return match($this->platform) {
            PlatformEnum::PLATFORM_360 => 'img/xbox-360.png',
            PlatformEnum::PLATFORM_XB1 => 'img/xbox-one.png',
            PlatformEnum::PLATFORM_XSX => 'img/xbox-series-x-s.png',
            PlatformEnum::PLATFORM_WIN => 'img/windows.png',
            PlatformEnum::PLATFORM_ANDROID => 'img/android.png',
            PlatformEnum::PLATFORM_WEB => 'img/web.png',
            PlatformEnum::PLATFORM_SWITCH => 'img/nintendo-switch.png',

            default => 'img/xbox-one.png',
        };
    }

    public function isBackwardsCompatible(): ?bool
    {
        return $this->backwardsCompatible;
    }

    public function getBackwardsCompatibleClass(): string
    {
        return $this->backwardsCompatible == true ? 'green' : 'red';
    }

    public function isKinectRequired(): ?bool
    {
        return $this->kinectRequired;
    }
   
    public function getKinectRequiredClass(): string
    {
        return $this->kinectRequired == true ? 'red' : 'green';
    }

    public function isPeripheralRequired(): ?bool
    {
        return $this->peripheralRequired;
    }

    public function getPeripheralRequiredClass(): string
    {
        return $this->peripheralRequired == true ? 'red' : 'green';
    }

    public function isOnlineMultiplayer(): ?bool
    {
        return $this->onlineMultiplayer;
    }

    public function getOnlineMultiplayerClass(): string
    {
        return $this->onlineMultiplayer == true ? 'red' : 'green';
    }

    public function getBackwardsCompatibleCompleteClass(): string
    {
        return match(true) {
            ($this->kinectRequired || ($this->peripheralRequired && !$this->onlineMultiplayer)) => 'warning',
            (!$this->backwardsCompatible && $this->onlineMultiplayer) => 'danger',
            ($this->backwardsCompatible) => 'success',
            default => '',
        };
    }

    public function getCompletionPercentage(): int
    {
        return $this->completionPercentage;
    }

    public function getCompletionPercentageClass(): string
    {
        return match($this->completionPercentage) {
            0 => '',
            100 => 'success',
            default => 'warning',
        };
    }

    public function getCompletionEstimate(): string
    {
        return $this->completionEstimate;
    }

    public function getCompletionEstimateClass(): string
    {
        return match($this->completionEstimate) {
            CompletionEstimateEnum::COMP_EST_1000H,
            CompletionEstimateEnum::COMP_EST_750H,
            CompletionEstimateEnum::COMP_EST_500H,
            CompletionEstimateEnum::COMP_EST_300H,
            CompletionEstimateEnum::COMP_EST_200H, 
            CompletionEstimateEnum::COMP_EST_150H, 
            CompletionEstimateEnum::COMP_EST_120H, 
            CompletionEstimateEnum::COMP_EST_100H => 'danger',

            CompletionEstimateEnum::COMP_EST_40H,
            CompletionEstimateEnum::COMP_EST_50H,
            CompletionEstimateEnum::COMP_EST_60H,
            CompletionEstimateEnum::COMP_EST_80H => 'warning',

            default => '',
        };
    }

    public function getHoursPlayed(): float
    {
        return $this->hoursPlayed;
    }

    public function getAchievementsWon(): int
    {
        return $this->achievementsWon;
    }

    public function getAchievementsTotal(): int
    {
        return $this->achievementsTotal;
    }

    public function getGamerscoreWon(): int
    {
        return $this->gamerscoreWon;
    }

    public function getGamerscoreTotal(): int
    {
        return $this->gamerscoreTotal;
    }

    public function getTaScore(): int
    {
        return $this->taScore;
    }

    public function getTaTotal(): int
    {
        return $this->taTotal;
    }
   
    public function hasDlc(): bool
    {
        return $this->hasDlc;
    }

    public function getDlcCompletion(): int
    {
        return $this->dlcCompletionPercentage;
    }

    public function getDlcCompletionClass(): string
    {
        return match($this->dlcCompletionPercentage) {
            100 => 'green',
            0 => 'red',
            default => 'orange',
        };
    }

    public function getCompletionDate(): ?DateTime
    {
        return $this->completionDate;
    }

    public function getSiteRating(): float
    {
        return $this->siteRating;
    }
   
    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function getFormatClass(): string
    {
        return match($this->format) {
            'Disc' => 'warning',
            default => '',
        };
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPurchasedPrice(): ?float
    {
        return $this->purchasedPrice;
    }
 
    public function getCurrentPrice(): ?float
    {
        return $this->currentPrice;
    }

    public function getRegularPrice(): ?float
    {
        return $this->regularPrice;
    }

    public function getShortlistOrder(): int
    {
        return $this->shortlistOrder;
    }

    public function getRanking(): int
    {
        return $this->ranking;
    }

    public function getWalkthroughUrl(): ?string
    {
        return $this->walkthroughUrl;
    }

    public function getGameUrl(): string
    {
        return $this->gameUrl;
    }

    public function getLastModified(): DateTime
    {
        return $this->lastModified;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function getRatio(): float
    {
        return $this->gamerscoreTotal > 0 ? floatval($this->taTotal / $this->gamerscoreTotal) : 1;
    }

    public function getRatioClass(): string
    {
        $ratio = $this->getRatio();
        return match(true) {
            ($ratio < 2) => 'ratio-veryeasy',
            ($ratio < 3) => 'ratio-easy',
            ($ratio < 4) => 'ratio-medium',
            ($ratio < 5) => 'ratio-hard',
            default => 'ratio-veryhard',
        };
    }

    public function setBackwardsCompatible(?bool $backcompat): self
    {
        $this->backwardsCompatible = $backcompat;

        return $this;
    }

    public function setKinectRequired(?bool $kinect): self
    {
        $this->kinectRequired = $kinect;

        return $this;
    }

    public function setPeripheralRequired(?bool $periph): self
    {
        $this->peripheralRequired = $periph;

        return $this;
    }
    
    public function setOnlineMultiplayer(?bool $online): self
    {
        $this->onlineMultiplayer = $online;

        return $this;
    }

    public function setPurchasedPrice(?float $price): self
    {
        $this->purchasedPrice = $price;

        return $this;
    }

    public function setLastModified(DateTimeInterface $timestamp): self
    {
        $this->lastModified = DateTime::createFromInterface($timestamp);

        return $this;
    }

    public function setRanking(int $ranking): self
    {
        $this->ranking = $ranking;

        return $this;
    }

    ///////////// ENCAPSULATION FUNCTIONS /////////////

    /**
     * Update existing game with new details
     */
    public function update(Game $game): void
    {
        $this->name = $game->getName();
        $this->platform = $game->getPlatform();
        $this->completionPercentage = $game->getCompletionPercentage();
        $this->completionEstimate = $game->getCompletionEstimate();
        $this->hoursPlayed = $game->getHoursPlayed();
        $this->achievementsWon = $game->getAchievementsWon();
        $this->achievementsTotal = $game->getAchievementsTotal();
        $this->gamerscoreWon = $game->getGamerscoreWon();
        $this->gamerscoreTotal = $game->getGamerscoreTotal();
        $this->taScore = $game->getTaScore();
        $this->taTotal = $game->getTaTotal();
        $this->hasDlc = $game->hasDlc;
        $this->dlcCompletionPercentage = $game->getDlcCompletion();
        $this->completionDate = $game->getCompletionDate();
        $this->siteRating = $game->getSiteRating();
        $this->format = $game->getFormat();
        $this->status = $game->getStatus();
        $this->walkthroughUrl = $game->getWalkthroughUrl();
        $this->gameUrl = $game->getGameUrl();
        $this->lastModified = $game->getLastModified();

        /** don't update these fields because they will be blank/default on import
        if ($this->platform === PlatformEnum::PLATFORM_360) {
            $this->backwardsCompatible = $game->isBackwardsCompatible();
            $this->kinectRequired = $game->isKinectRequired();
            $this->peripheralRequired = $game->isPeripheralRequired();
            $this->onlineMultiplayer = $game->isOnlineMultiplayer();
        }
        $this->purchasedPrice = $game->getPurchasedPrice();
        $this->currentPrice = $game->getCurrentPrice();
        $this->regularPrice = $game->getRegularPrice();
        $this->shortlistOrder = $game->getShortlistOrder();
        $this->ranking = $game->getRanking();
        $this->created = $game->getCreated();
         */
    }

    public function setBackwardsCompatibleByString(?string $backcompat): self
    {
        return $this->setBackwardsCompatible($this->stringToNullableBool($backcompat));
    }

    public function setKinectRequiredByString(?string $kinect): self
    {
        return $this->setKinectRequired($this->stringToNullableBool($kinect));
    }

    public function setPeripheralRequiredByString(?string $periph): self
    {
        return $this->setPeripheralRequired($this->stringToNullableBool($periph));
    }
   
    public function setOnlineMultiplayerByString(?string $online): self
    {
        return $this->setOnlineMultiplayer($this->stringToNullableBool($online));
    }

    private function stringToNullableBool(?string $input): ?bool
    {
        return match($input) {
            '0' => false,
            '1' => true,
            default => null,
        };
    }
}