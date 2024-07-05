<?php
declare(strict_types=1);

namespace App\Entity;

use DateTime;
use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: "mygamecollection")]
class Game
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_DELISTED = 'delisted';
    public const STATUS_REGION_LOCKED = 'region-locked';
    public const STATUS_SALE = 'sale';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
   
    #[ORM\Column]
    private string $name;
   
    #[ORM\Column]
    private string $platform;
   
    #[ORM\Column(name: "backcompat")]
    private ?bool $backwardsCompatible;
   
    #[ORM\Column(name: "kinect_required")]
    private ?bool $kinectRequired;
   
    #[ORM\Column(name: "peripheral_required")]
    private ?bool $peripheralRequired;
   
    #[ORM\Column]
    private ?bool $onlineMultiplayer;
   
    #[ORM\Column(name: "completion_perc")]
    private int $completionPercentage;
   
    #[ORM\Column(name: "completion_estimate")]
    private string $completionEstimate;
   
    #[ORM\Column(name: "hours_played")]
    private int $hoursPlayed;
   
    #[ORM\Column(name: "achievements_won")]
    private int $achievementsWon;
   
    #[ORM\Column(name: "achievements_total")]
    private int $achievementsTotal;
   
    #[ORM\Column(name: "gamerscore_won")]
    private int $gamerscoreWon;
   
    #[ORM\Column(name: "gamerscore_total")]
    private int $gamerscoreTotal;
   
    #[ORM\Column(name: "ta_score")]
    private int $taScore;
   
    #[ORM\Column(name: "ta_total")]
    private int $taTotal;
   
    #[ORM\Column(name: "dlc")]
    private bool $hasDlc;
   
    #[ORM\Column(name: "dlc_completion")]
    private int $dlcCompletionPercentage;
   
    #[ORM\Column(name: "completion_date")]
    private ?DateTime $completionDate = null;
   
    #[ORM\Column(name: "site_rating")]
    private float $siteRating;
   
    #[ORM\Column]
    private string $format;
   
    #[ORM\Column]
    private string $status;
   
    #[ORM\Column(name: "purchased_price")]
    private ?float $purchasedPrice = null;
   
    #[ORM\Column(name: "current_price")]
    private ?float $currentPrice = null;
   
    #[ORM\Column(name: "regular_price")]
    private ?float $regularPrice = null;
   
    #[ORM\Column(name: "shortlist_order")]
    private int $shortlistOrder;
   
    #[ORM\Column(name: "walkthrough_url")]
    private string $walkthroughUrl;
   
    #[ORM\Column(name: "game_url")]
    private string $gameUrl;
   
    #[ORM\Column(name: "last_modified", type: "datetime")]
    private DateTime $lastModified;
   
    #[ORM\Column(name: "date_created", type: "datetime")]
    private DateTime $created;

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
           'Xbox 360' => 'x36',
           'Xbox One' => 'xb1',
           'Xbox Series X|S' => 'xsx',
           'Windows' => 'win',
           default => 'mob',
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
           '100-120 hours',
           '120-150 hours',
           '150-200 hours',
           '200+ hours' => 'danger',
           '40-50 hours',
           '50-60 hours',
           '60-80 hours',
           '80-100 hours' => 'warning',
           default => '',
       };
   }

   public function getHoursPlayed(): int
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
           'Sold' => 'danger',
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
}
