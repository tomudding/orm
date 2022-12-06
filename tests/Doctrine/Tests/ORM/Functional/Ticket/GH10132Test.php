<?php

namespace Doctrine\Tests\Functional;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    Id,
    JoinColumn,
    ManyToOne,
    OneToMany,
};
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10132Test extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            Complex::class,
            ComplexChild::class,
            SubComplexChild::class,
        );
    }

    public function testQueryBackedEnumInCompositeKeyJoin()
    {
        $complex = new Complex();
        $complex->setType(ComplexType::A);
        $complex->setNumber(1);

        $complexChild = new ComplexChild();
        $complexChild->setComplex($complex);
        $complexChild->setPoint(1);

        $subComplexChild1 = new SubComplexChild();
        $subComplexChild1->setComplexChild($complexChild);
        $subComplexChild1->setNumber(1);

        $subComplexChild2 = new SubComplexChild();
        $subComplexChild2->setComplexChild($complexChild);
        $subComplexChild2->setNumber(2);

        $subComplexChild3 = new SubComplexChild();
        $subComplexChild3->setComplexChild($complexChild);
        $subComplexChild3->setNumber(3);

        $this->_em->persist($complex);
        $this->_em->persist($complexChild);
        $this->_em->persist($subComplexChild1);
        $this->_em->persist($subComplexChild2);
        $this->_em->persist($subComplexChild3);
        $this->_em->flush();
        $this->_em->clear();

        $qb = $this->_em->createQueryBuilder();
        $qb->select('s')
            ->from(SubComplexChild::class, 's')
            ->where('s.complex_type = :complex_type')
            ->andWhere('s.complex_number = :complex_number')
            ->andWhere('s.complexChild_point = :complexChild_point')
            ->andWhere('s.number = :number');

        $qb->setParameter('complex_type', ComplexType::A);
        $qb->setParameter('complex_number', 1);
        $qb->setParameter('complexChild_point', 1);
        $qb->setParameter('number', 2);

        self::assertNotNull($qb->getQuery()->getOneOrNullResult());
    }
}

enum ComplexType: string
{
    case A = 'a';
    case B = 'b';
    case C = 'c';
}

/** @Entity */
#[Entity]
class Complex
{
    /**
     * @Id
     * @Column(type = "string", enumType = ComplexType::class)
     */
    #[Id]
    #[Column(
        type: "string",
        enumType: ComplexType::class,
    )]
    protected ComplexType $type;

    /**
     * @Id
     * @Column(type = "integer")
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $number;

    /**
     * @OneToMany(targetEntity = ComplexChild::class, mappedBy = "complex", cascade = {"persist"})
     */
    #[OneToMany(
        targetEntity: ComplexChild::class,
        mappedBy: "complex",
        cascade: ["persist"],
    )]
    protected Collection $complexChildren;

    public function __construct()
    {
        $this->complexChildren = new ArrayCollection();
    }

    public function getType(): ComplexType
    {
        return $this->type;
    }

    public function setType(ComplexType $type): void
    {
        $this->type = $type;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    public function getComplexChildren(): Collection
    {
        return $this->complexChildren;
    }

    public function addComplexChild(ComplexChild $complexChild): void
    {
        $this->complexChildren->add($complexChild);
    }
}

/** @Entity */
#[Entity]
class ComplexChild
{
    /**
     * @ManyToOne(targetEntity = Complex::class, inversedBy = "complexChildren")
     * @JoinColumn(name = "complex_type", referencedColumnName = "type", nullable = false)
     * @JoinColumn(name = "complex_number", referencedColumnName = "number", nullable = false)
     */
    #[ManyToOne(
        targetEntity: Complex::class,
        inversedBy: "complexChildren",
    )]
    #[JoinColumn(
        name: "complex_type",
        referencedColumnName: "type",
        nullable: false,
    )]
    #[JoinColumn(
        name: "complex_number",
        referencedColumnName: "number",
        nullable: false,
    )]
    protected Complex $complex;

    /**
     * @Id
     * @Column(type = "string", enumType = ComplexType::class)
     */
    #[Id]
    #[Column(
        type: "string",
        enumType: ComplexType::class,
    )]
    protected ComplexType $complex_type;

    /**
     * @Id
     * @Column(type = "integer")
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $complex_number;

    /**
     * @Id
     * @Column(type = "integer")
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $point;

    /**
     * @OneToMany(targetEntity = SubComplexChild::class, mappedBy = "childComplex", cascade = {"persist", "remove"})
     */
    #[OneToMany(
        targetEntity: SubComplexChild::class,
        mappedBy: "childComplex",
        cascade: ["persist", "remove"],
    )]
    protected Collection $subComplexChildren;

    public function setComplex(Complex $complex): void
    {
        $this->subComplexChildren = new ArrayCollection();

        $complex->addComplexChild($this);
        $this->complex_type = $complex->getType();
        $this->complex_number = $complex->getNumber();
        $this->complex = $complex;
    }

    public function getComplexType(): ComplexType
    {
        return $this->complex_type;
    }

    public function getComplexNumber(): int
    {
        return $this->complex_number;
    }

    public function getComplex(): Complex
    {
        return $this->complex;
    }

    public function setPoint(int $point): void
    {
        $this->point = $point;
    }

    public function getPoint(): int
    {
        return $this->point;
    }

    public function getSubComplexChildren(): Collection
    {
        return $this->subComplexChildren;
    }

    public function addSubComplexChild(SubComplexChild $subComplexChild): void
    {
        $this->subComplexChildren[] = $subComplexChild;
    }
}

/** @Entity */
#[Entity]
class SubComplexChild
{
    /**
     * @ManyToOne(targetEntity = ComplexChild::class, inversedBy = "complexChildren")
     * @JoinColumn(name = "complex_type", referencedColumnName = "complex_type", nullable = false)
     * @JoinColumn(name = "complex_number", referencedColumnName = "complex_number", nullable = false)
     * @JoinColumn(name = "complexChild_point", referencedColumnName = "point", nullable = false)
     */
    #[ManyToOne(
        targetEntity: ComplexChild::class,
        inversedBy: "subComplexChildren",
    )]
    #[JoinColumn(
        name: "complex_type",
        referencedColumnName: "complex_type",
    )]
    #[JoinColumn(
        name: "complex_number",
        referencedColumnName: "complex_number",
    )]
    #[JoinColumn(
        name: "complexChild_point",
        referencedColumnName: "point",
    )]
    protected ComplexChild $complexChild;

    /**
     * @Id
     * @Column(type = "string", enumType = ComplexType::class)
     */
    #[Id]
    #[Column(
        type: "string",
        enumType: ComplexType::class,
    )]
    protected ComplexType $complex_type;

    /**
     * @Id
     * @Column(type = "integer")
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $complex_number;

    /**
     * @Id
     * @Column(type = "integer")
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $complexChild_point;

    /**
     * @Id
     * @Column(type = "integer")
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $number;

    public function getComplexChild(): ComplexChild
    {
        return $this->complexChild;
    }

    public function setComplexChild(ComplexChild $complexChild): void
    {
        $complexChild->addSubComplexChild($this);
        $this->complex_type = $complexChild->getComplexType();
        $this->complex_number = $complexChild->getComplexNumber();
        $this->complexChild_point = $complexChild->getPoint();
        $this->complexChild = $complexChild;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }
}

