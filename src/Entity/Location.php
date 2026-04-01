<?php

namespace Survos\LocationBundle\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Survos\ApiGrid\Api\Filter\MultiFieldSearchFilter;
use Survos\LocationBundle\Repository\LocationRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(normalizationContext: ['skip_null_values' => false, 'groups' => ['rp', 'location.read', 'location.tree']])]
#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\Table(indexes: [new ORM\Index(name: 'location_country_code', columns: ['country_code']), new ORM\Index(name: 'location_state_code', columns: ['state_code']), new ORM\Index(name: 'location_name_idx', columns: ['name']), new ORM\Index(name: 'location_lvl_idex', columns: ['lvl'])])]
#[Gedmo\Tree(type: 'nested')]
#[UniqueEntity('code')]
#[ORM\UniqueConstraint(name: 'location_code', columns: ['code'])]
#[ApiFilter(OrderFilter::class, properties: ['code', 'stateCode', 'name'], arguments: ['orderParameterName' => 'order'])]
//#[ApiFilter(MultiFieldSearchFilter::class, properties: ["code", 'countryCode', 'stateCode', 'name'], arguments: ["searchParameterName" => "search"])]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial', 'countryCode' => 'exact', 'stateCode' => 'partial', 'code' => 'partial', 'lvl' => 'exact'])]
class Location implements \Stringable
{
    public function __construct($code = null, $name = null, ?int $lvl = null)
    {
        $this->code = $code;
        $this->name = $name;
        $this->lvl = $lvl;
    }
    public static function build(string $code, string $name, ?int $lvl): self
    {
        $location = new Location($code, $name, $lvl);
        return $location;
    }
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['location.read'])]
    private ?int $id = null;
    #[ORM\Column(type: 'string', length: 180)]
    #[Assert\NotBlank]
    #[Groups(['location.read'])]
    private string $name;
    #[ORM\Column(type: 'string', length: 180)]
    #[Assert\NotBlank]
    #[Groups(['location.read'])]
    private string $code;
    #[ORM\Column(type: 'integer', nullable: false)]
    #[Groups(['location.read'])]
    private int $lvl;
    /**
     * @var int|mixed|null
     */
    #[ORM\Column(name: 'lft', type: 'integer')]
    #[Gedmo\TreeLeft]
    private $lft;
    /**
     * @return mixed|null
     */
    public function getLft()
    {
        return $this->lft;
    }
    /**
     * @param mixed $lft
     */
    public function setLft($lft): static
    {
        $this->lft = $lft;
        return $this;
    }
    /**
     * @return mixed|null
     */
    public function getRgt()
    {
        return $this->rgt;
    }
    /**
     * @param mixed $rgt
     */
    public function setRgt($rgt): static
    {
        $this->rgt = $rgt;
        return $this;
    }
    /**
     * @return mixed|null
     */
    public function getRoot()
    {
        return $this->root;
    }
    /**
     * @param mixed $root
     */
    public function setRoot($root): static
    {
        $this->root = $root;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getParent(): ?Location
    {
        return $this->parent;
    }
    public function setParent(?Location $parent): self
    {
        $this->parent = $parent;
        return $this;
    }
    /**
     * @return mixed|null
     */
    public function getChildren(): ?Collection
    {
        return $this->children;
    }
    /**
     * @param mixed $children
     */
    public function setChildren($children): static
    {
        $this->children = $children;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getLvl(): ?int
    {
        return $this->lvl;
    }
    public function setLvl(?int $lvl): static
    {
        $this->lvl = $lvl;
        return $this;
    }
    #[ORM\Column(name: 'rgt', type: Types::INTEGER)]
    #[Gedmo\TreeRight]
    private $rgt;
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['location.read'])]
    private int $childCount = 0;
    /**
     * @return int
     */
    public function getChildCount(): int
    {
        return $this->childCount;
    }
    /**
     * @param int $childCount
     * @return Location
     */
    public function setChildCount(int $childCount): Location
    {
        $this->childCount = $childCount;
        return $this;
    }
    /**
     * @var \Survos\LocationBundle\Entity\Location|mixed|null
     */
    #[ORM\ManyToOne(targetEntity: 'Location', cascade: ['persist'], fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'tree_root', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeRoot]
    private $root;
    #[ORM\ManyToOne(targetEntity: 'Location', inversedBy: 'children', cascade: ['persist'], fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private ?Location $parent = null;
    /**
     * @var \Survos\LocationBundle\Entity\Location[]|Collection|mixed|null
     */
    #[ORM\OneToMany(targetEntity: 'Location', mappedBy: 'parent', cascade: ['persist', 'remove'], fetch: 'LAZY')]
    private ?Collection $children = null;
    #[ORM\Column(type: 'string', length: 2, nullable: true)]
    #[Groups(['location.read'])]
    private ?string $countryCode = null;
    // really the 2nd-level administrative code, unique within country
    #[ORM\Column(type: 'string', length: 3, nullable: true)]
    #[Groups(['location.read'])]
    private ?string $stateCode = null;
    /**
     * @return string|null
     */
    public function getStateCode(): ?string
    {
        return $this->stateCode;
    }
    /**
     * @param string|null $stateCode
     * @return Location
     */
    public function setStateCode(?string $stateCode): Location
    {
        $this->stateCode = $stateCode;
        return $this;
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getCode(): ?string
    {
        return $this->code;
    }
    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }
    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;
        return $this;
    }
    public function __toString(): string
    {
        return $this->getName();
    }
}
